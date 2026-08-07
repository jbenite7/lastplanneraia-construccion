# Barrido completo de la aplicación — 2026-08-07

Encargo del usuario: barrer la app entera, «módulo por módulo, función por función, escenario por
escenario», tras el cierre de la campaña de dark mode. Este documento **reporta**; sólo se arregló lo
trivial, por decisión suya.

## Cómo se midió

| | |
|---|---|
| Datos | **Copia fiel de la base real** (volcado `--single-transaction` de 36 MB), verificada contra el original: 58 proyectos, 191 usuarios, 537 membresías, 6.913 filas de programa, 3.876 de programación semanal — **idénticas** |
| Stack A | `barrido-lps` (8096), volumen propio, con esos datos reales |
| Stack B | `lps-aia-design-system-ci-run-barrido-20260807` (18081), el stack de CI del repo con su fixture versionado |
| Viewport | 1180×820, tema oscuro; sesión por la puerta de servicio |
| Alcance | 23 superficies de la app + 8 de `admin/` + 339 pruebas de navegador |

**Aviso de aislamiento, y no es menor:** `docker-compose.yml:1` declara `db_data` como volumen
**externo** (`htdocs_db_data`, 1,3 GB). Levantar «un stack propio» sin sobrescribir ese volumen
arranca **un segundo MySQL sobre los mismos archivos** que usa el humano. La instrucción habitual de
aislamiento («usa otro `COMPOSE_PROJECT_NAME` y otro puerto») **no basta** y habría puesto en riesgo
la base. Hace falta también `volumes: !override` — y `ports: !override`, porque los puertos se
acumulan en vez de sustituirse.

## Resultado de las pruebas

**195 pasan · 111 fallan · 22 omitidas · 11 sin ejecutar** (1,1 h).

**Los 111 fallos NO son 111 defectos.** Clasificados por causa:

| Causa | Aprox. | Qué es |
|---|---|---|
| La interfaz migró y la prueba mira al sitio viejo | ~75 | Deuda de pruebas |
| Rutas del PDC v1 borradas el 2026-08-04 | ~7 | Deuda de pruebas |
| El fixture de CI no tiene el proyecto que la prueba fija | ~16 | Entorno |
| **Defectos reales** | **~6** | Ver B-3, B-6, B-7 |

---

## B-1 · `npx playwright test` devuelve VERDE habiendo ejecutado CERO pruebas — severidad 4

Tres archivos de `tests/browser/` no son specs sino **scripts autoejecutables** terminados en
`process.exit()`: `handsontable-ancho-tabla.mjs:100`, `shell-sidebar-rollout.mjs:221`,
`shell-week-admin.mjs:212`. Viven dentro del `testDir` de `playwright.config.mjs`, así que Playwright
los **importa al recolectar** y ese `exit(0)` **mata la corrida entera antes del primer spec**,
devolviendo código 0.

Medido: `npx playwright test --list` sobre la config base reporta `Total: 0 tests in 0 files`.

**Es la causa raíz de todo lo demás de este informe.** Un verde falso perfecto: quien «corra la suite
completa» para dar por buena una entrega obtiene éxito sin haber probado nada. Por eso B-2 y B-3
llevan días sin detectarse.

**Arreglo:** sacarlos del `testDir` o del `testMatch`. No se aplicó — cambia la configuración de
pruebas del repo y merece decisión propia.

## B-2 · Un tercio de la suite mira a una interfaz que ya no existe — severidad 3

La migración al design system movió la UI a primitivas `aia-*` y a contenedores nuevos; buena parte
de la suite sigue consultando los selectores legados. Tres casos verificados en vivo:

- **Barra de Programación Semanal** (`roles-phases`, `subviews`, `dark-density`, `sprint` — ~47
  fallos): las pruebas buscan `.ps-hot-toolbar-actions .btn-pdc-modern`. Medido a 1180 px: el
  contenedor existe, pero sus botones son hoy `aia-btn aia-btn--secondary`. Coincidencias del
  selector viejo: **0 visibles**.
- **BI Control Tower** (16 fallos): esperan `#btn-project-dropdown` suelto. Los filtros se mudaron a
  un cajón (`views/bi/_layout.php:25`, `role="dialog"`, disparador `[data-bi-filter-trigger]`), que
  arranca en `display:none`. Reproducido **idéntico en las dos bases**.
- **Modales y matices** (`modales-dark-homologacion` 6, `state-tint-ladder` 1): prueban `/pdc`,
  `/contratos` y `/listado-actividades`, **rutas borradas con el PDC v1** el 2026-08-04. Hoy
  responden **404**; de ahí el `window.jQuery is not a function`.

Es la misma forma que ya cazó la Task 32 («el shell lateral dejó huérfano el indicador de fase»),
pero a escala de suite. **La aplicación está bien en los tres casos; la red que debía protegerla, no.**

## B-3 · Los goldens de PG y PI llevan en rojo desde el 2026-08-06, y siguen así en `main` — severidad 3

`programa-general.visual.mjs` y `programacion-intermedia.visual.mjs` fallan con **959 píxeles**
distintos (ratio 0,01, cinco veces la tolerancia de 0,002), en los dos viewports.

**El diff señala un solo elemento: el logo.** El resto de la pantalla es idéntico píxel a píxel.

Cronología, toda del 2026-08-06:

| Hora | Commit | Qué pasó |
|---|---|---|
| 10:30 | `03d0871a` | Se recapturan los goldens de PG y PI |
| **12:25** | `4437fcfa` | **Cambia el logo de marca** — invalida esos dos goldens |
| 14:15 | `6b618964` | Se rehace el golden de `/login` por el logo… y sólo ése |

Verificado que sigue vivo: el blob del golden es **idéntico en `main` y en esta rama**
(`b08664eb`), sin tocar desde las 10:30. **El gate visual de las dos rejillas principales lleva más
de un día caído.** No se recapturó: mover una línea base exige aprobación explícita del usuario.

## B-4 · La cifra «28/28 con `<main>` y `h1`» del cierre de campaña es FALSA — severidad 3

`docs/superpowers/barrido-diseno-2026-08-03.md:335` afirma «**28/28 con `<main>` y con un `h1` real**
—lo que cierra C-30 medido en producto, no en diff—», siendo las 28 «20 de la app + 8 de `admin/`».

Medido: **app 23/23 ✅ · `admin/` 0/8 ❌**. Confirmado en la fuente:
`grep -rn "<main\|role=\"main\"" admin/views/` devuelve **cero**, frente a 19 archivos en `views/`.

C-30 está cerrado **para la app**, no para `admin/`. La Task 18 acotó bien su censo; lo que sobrepasó
fue la frase del informe de cierre.

## B-5 · Chips en cero: atenuados en PI y PS, no en Programa General — severidad 2

Medido con `getComputedStyle` sobre `.pdc-legend-item` (Da Porto, semana 4):

| Módulo | Chips en cero | Clase | Color |
|---|---|---|---|
| Prog. Intermedia | atenuados (7) | `is-zero` | `rgb(199,212,204)` |
| Prog. Semanal | atenuados (4) | `is-zero` | `rgb(199,212,204)` |
| **Programa General** | **no atenuados (4)** | — | `rgb(247,250,248)` |

La Task 11 (C-24) listaba en sus *Files* sólo las hojas de PI y PS: se ejecutó fielmente y dejó fuera
a PG. En pantalla, «Atrasada 0» pesa lo mismo que «Actividad Futura 238». Los formatos también
divergen (`238` suelto en PG; `(0)` entre paréntesis en PI y PS).

## B-6 · `/control-cambios` carga desde OCHO CDN externos — severidad 3

Hosts observados: `code.jquery.com`, `cdnjs.cloudflare.com`, `stackpath.bootstrapcdn.com`,
`cdn.datatables.net`, `gyrocode.github.io`, `unpkg.com`, `www.gstatic.com`, `cdn.anychart.com`.
Ninguno con `integrity`.

Contraste medido el mismo día: `/plan-compras` carga **cero** hosts externos, y las vistas de acceso
se capàron a cero el 2026-08-06. **El patrón bueno existe y está aplicado en otras superficies; ésta
se quedó fuera.**

Riesgo doble: **disponibilidad** —una obra sin internet, o un CDN caído, rompe la pantalla— y
**cadena de suministro**: `gyrocode.github.io` es una GitHub Pages personal y `unpkg.com` sirve lo
que publique el paquete.

## B-7 · Tres rutas públicas entregan CSS sin capa y sin declarar — severidad 2

`design-system-unlayered-delivery.mjs` falla con `undeclared-unlayered-delivery` en **`/login`,
`/password/forgot` y `/password/reset`**: entregan `/runtime/css/design-system/entrypoints/core.css`
por un `<link>` plano, sin capa ni declaración.

Confirmado sirviendo la página. **`npm run test:design-system:static` NO lo ve** —da 8/8— porque su
gate homónimo es estático; sólo el de runtime lo caza. Es otro caso de un verde que no cubre lo que
parece cubrir.

## B-8 · `/control-cambios` declara dos `<h1>` y salta niveles — severidad 2

Encabezados servidos: `H3 Información`, `H3 Obra`, `H3 Compras`, `H3 Crear Semana 5`,
`H3 Eliminar Semana 4`, **`H1 Control de Cambios`**, `H4 …`, **`H1 Orden de Cambio`** (modal), `H3 …`.
Dos `h1`, y el primer encabezado del documento es un `h3` del carril.

---

## Lo que se verificó SANO

- **23/23 superficies de la app**: HTTP 200, `<main>`, un `h1`, **0 errores de consola**, **0
  desbordamiento horizontal** a 1180 px. **0 cabeceras recortadas** en toda la app; 4 celdas
  recortadas en total (1 dashboard, 1 PS, 2 CIC).
- **8/8 de `admin/`**: 200, un `h1`, sin errores ni desbordamiento (les falta `<main>`, B-4).
- **`/plan-compras`**: cero peticiones externas.
- **En pantalla, con datos reales, la campaña se sostiene:** capítulos por peso y filete sin bloque
  naranja (Tasks 7/36), separadores sutiles (Task 5), numéricas a la derecha (Task 34), chips de
  estado distinguibles, «Recargar» y «BI Semanal» en la barra con menú «Más» (Task 12).
- **195 pruebas en verde**, incluidas las de PDC v2, escalamientos, selector de proyecto y la mayor
  parte del laboratorio.

## Lo que este barrido NO cubrió

- **Las specs que mutan datos** (`full-app-flow.spec.mjs`): su candado exige el stack de CI exacto.
  Se **excluyeron** en vez de falsear el consentimiento.
- **Los 17 specs de `e2e/`** (la biblia de flujos): comparten base con la corrida y habrían chocado.
- **Roles denegados**: se barrió con `test.R` y `test.A`; no se cubrió `test.V`.
- **Revalidación contra `main`**: esta rama va por detrás. B-3 sí se verificó en `main` (blob
  idéntico); el resto se midió sobre `10969800`.
