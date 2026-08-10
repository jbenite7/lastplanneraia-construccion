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

> **Al intentar arreglarlo (2026-08-07) resultó no ser un intercambio, sino una migración.** Son
> **18 scripts**, no ocho. La mayoría ya está vendorizada en `public/vendor/` (jquery, jquery-ui,
> popper, bootstrap, datatables, select2, anychart), pero **la vista carga jQuery 1.12.4 y la copia
> local es 3.6.0**: dos versiones mayores. Esta pantalla es legado (jQuery UI 1.10.1, Bootstrap
> 4.3.1) y jQuery 3 retiró APIs que muy probablemente usa, así que el cambio la rompería en
> silencio. Faltan además 5 librerías por vendorizar (`numeral`, `jspdf`, `html2canvas`,
> `tabulator`, el plugin de checkboxes de gyrocode), lo que implica descargar dependencias nuevas.
>
> **Parcialmente cerrado el 2026-08-07: 8 hosts → 7, con medición.**
>
> **Lo hecho, riesgo cero:** se localizaron las dos librerías cuya copia en `public/vendor/` es
> **exactamente la misma versión** que pedía el CDN —`jquery-ui 1.10.1` y `bootstrap 4.3.1`,
> verificado en el banner de cada archivo—. Además `jquery-ui` **se cargaba dos veces**, una a cada
> lado de Google Charts; queda una. Fuera `stackpath.bootstrapcdn.com` entero.
> Medido antes y después: 3 tablas, 1 fila de DataTable, `DataTable` y `select2` presentes,
> **0 errores de consola**. Idéntico.
>
> **El experimento que decide lo que falta.** En vez de suponer el riesgo de jQuery, se midió:
> se apuntó la vista a la copia local 3.6.0, se reconstruyó y se cargó la página.
>
> | | jQuery 1.12.4 (hoy) | jQuery 3.6.0 |
> |---|---|---|
> | Tablas en el DOM | 3 | **2** |
> | Filas del DataTable | 1 | **0** |
> | Errores de consola | 0 | **0** |
>
> **Se rompe en silencio**: la tabla desaparece sin un solo error en consola. Revertido y
> reverificado en la línea base. Queda demostrado que no es un intercambio.
>
> **Lo que falta, y qué necesita:** los 7 hosts restantes sirven librerías **sin copia local**
> (`jquery` 1.12.4, el núcleo de `dataTables` 1.11.4, `select2` —local 4.0.13 vs 4.0.6-rc.0 pedido—,
> `numeral`, `jspdf`, `html2canvas`, `tabulator`, el plugin de checkboxes de gyrocode, Google Charts
> y AnyChart). Traerlas exige **descargar dependencias nuevas**, que necesita el visto bueno del
> usuario con la lista y las versiones exactas delante. Y para jQuery hay antes una decisión de
> producto: **vendorizar 1.12.4 tal cual** (congela la página, riesgo nulo, deuda perpetua) o
> **migrar la vista a jQuery 3** (la saca del legado, pero exige prueba funcional de DataTables,
> select2, los filtros, la exportación a PDF y las gráficas).
>
> Nota aparte: `www.gstatic.com` (Google Charts) y `cdn.anychart.com` **no tienen versión
> descargable equivalente** en el modelo de uso actual —AnyChart va con `hcode` de licencia y Google
> Charts exige su loader—, así que esos dos no se resuelven vendorizando: piden decidir si se
> sustituyen por una librería de gráficos ya vendorizada.

## B-7 · CSS sin capa y sin declarar en las 25 rutas de la app — severidad 3

> **Corregido al arreglarlo (2026-08-07): no son tres rutas, son TODAS.** La primera redacción
> (abajo) se quedó con las tres públicas que salían en el mensaje de error. Al correr el gate entero:
> **25 rutas**, públicas y autenticadas, todas con
> `undeclared-unlayered-delivery: /runtime/css/design-system/entrypoints/core.css`.
>
> **Diagnóstico exacto:** `docs/design-system/unlayered-delivery-inventory.json` no menciona
> `/runtime/` **ni una sola vez**, ni `entrypoints/core.css`. `DesignSystemHeadComponent:220` mapea
> `/css/design-system/entrypoints/core.css` → `/runtime/css/...`, y esa entrega servida en runtime
> nunca se declaró.
>
> **La salida correcta es declararla, no eliminarla**, y esto es lo que lo sostiene: `core.css`
> tiene **cero reglas sin capa** — su línea 1 es el `@layer reset, vendor, theme, …` que declara el
> orden de la cascada, y una hoja que declara el orden de capas **no puede ir dentro de una capa**.
> Es la entrega sancionada del propio design system.
>
> **No se aplicó**, y no por dificultad: declarar una hoja en 25 rutas del inventario es **ratificar
> un contrato del design system**, no arreglar un bug, y ese archivo es justo el área que la sesión
> de cierre de la 1.1.0 tiene abierta (`scripts/design-system-contracts.mjs` y
> `tests/design-system/contracts.test.mjs` siguen sin commitear en el worktree principal). Se deja
> el diagnóstico hecho para que el arreglo sea mecánico para quien posee el contrato.
>
> Nota de método: `npm run test:design-system:static` da 8/8 y **no ve esto**; su gate homónimo es
> estático y sólo el de runtime lo caza. Otro verde que cubre menos de lo que parece.

### Redacción original (incompleta)

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

## B-10 · La recuperación de contraseña dice «enviaremos un enlace» aunque el envío falle — severidad 3

Hallado el 2026-08-08 al probar el correo tras subir a PHPMailer 7, con un capturador local.

`POST /password/forgot` con `admin@ci.invalid` devuelve **200** y pinta:

> «Si el correo existe y está habilitado, enviaremos un enlace de restablecimiento en unos minutos.»

Pero el envío **falló**. Sólo consta en el log del servidor:

```
PasswordResetService::request SMTP Error: Could not connect to SMTP host.
STARTTLS command failed Command not implemented
```

Y el token **se creó igual** (1 fila en `password_reset_tokens`), así que queda un token vivo para
un enlace que nunca salió.

El mensaje genérico es correcto **por seguridad**: no revela si esa dirección tiene cuenta, que es
la defensa estándar contra enumeración de usuarios. El problema es otro: **una caída total del
correo se ve exactamente igual que un envío correcto**. Quien pide recuperar su contraseña espera
indefinidamente algo que no va a llegar, y nadie se entera salvo que alguien lea el log.

No se arregla aquí por decisión del usuario: tocar ese mensaje es equilibrar dos cosas legítimas
—no filtrar qué correos existen y no mentirle a quien espera— y eso es decisión de producto. La
salida que no rompe el equilibrio: conservar el texto genérico cuando el envío sale bien, y mostrar
un fallo técnico honesto («no pudimos enviarlo, inténtalo de nuevo») **sólo** cuando `send()` lanza.

### Y de paso, otro hueco del mismo patrón que B-9

`password_reset_tokens` **no existe en el fixture de CI**, y por eso el primer intento dio 500. Su
DDL vive en `database/patches/20260329_create_password_reset_tokens.sql` —un **patch**, no una
migración— y el `Dockerfile` del fixture no lo copia. **Ninguna migración la menciona.**

Consecuencia: **el flujo de recuperación de contraseña no se puede probar en CI**, lo que explica
que B-10 llevara ahí sin que ninguna suite lo viera. Es el mismo defecto de fondo que B-9: DDL de
tablas vivas fuera del control de migraciones.

### Límite de la prueba con capturador local

`SmtpMailer` **siempre exige TLS** —no tiene camino sin cifrar: si `MAIL_ENCRYPTION` no es `ssl`,
fuerza `ENCRYPTION_STARTTLS`—. Un capturador local sin TLS no puede completar la conversación, y uno
con certificado autofirmado tampoco, porque el código no desactiva la verificación del certificado.
**Este camino sólo se verifica de extremo a extremo contra un SMTP con TLS real.**

Lo que sí quedó probado bajo PHPMailer 7: la ruta, el CSRF, la creación del token, y que la librería
**conecta y habla SMTP** (llegó a emitir `STARTTLS`, así que conexión y saludo funcionan).

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

## Cobertura: lo que faltaba y se cerró después

La primera entrega de este informe declaró cuatro huecos. Al preguntarlos el usuario, **uno resultó
falso y dos no eran impedimentos sino trabajo sin hacer**. Se cerraron los cuatro:

### 1. Las specs que mutan datos — ya estaban cubiertas (la declaración era errónea)

`full-app-flow.spec.mjs` **sí corrió** en la tanda principal (pruebas 150 y 151, 2 fallos): el
entorno de esa corrida ya llevaba `E2E_REQUIRE_ISOLATED_DB=1`, `E2E_ALLOW_DB_MUTATION=design-system-ci`
y el stack de CI exacto que exige el candado. La exclusión fue **sólo** en la re-corrida contra la
copia de datos reales, donde el candado sí bloqueaba y no se falseó.

### 2. Los 17 specs de `e2e/` — ejecutados: 53 pasan, 3 fallan

Primera corrida: 41 pasan, **14 fallan por una sola causa** —falta `E2E_ADMIN_USERNAME`, que la
suite exige y se niega a inventar—. Repetida con las credenciales sembradas que el propio repo
declara en sus fixtures, sobre el contenedor desechable: **12 pasan, 3 fallan**.

**Las cinco suites de la biblia de flujos pasaron enteras** (`transversal`, `cascada-lps`, `pdc`,
`soporte`, `lectura`), igual que smoke y workflows. Son las que cubren «escenario por escenario».

Los 3 fallos restantes destaparon B-9.

## B-9 · Una columna que el código necesita no la crea ninguna migración — severidad 4

Los tres fallos de `admin/proyectos-crud.spec.mjs` son un 500 al crear proyecto. Reproducido a mano:
`POST /admin/proyectos/guardar` con CSRF válido devuelve **HTTP 500**, y `admin/logs/php_error.log`
lo explica:

```
PHP Fatal error: Uncaught PDOException: SQLSTATE[42S22]:
Column not found: 1054 Unknown column 'fechaInicioLineaBase' in 'field list'
  #1 admin/src/Models/Project.php(356): Database->query('INSERT INTO gen...')
  #2 admin/src/Controllers/ProjectController.php(333): Project->create(Array)
```

**No es un defecto de la aplicación, y por poco lo reporto como tal.** La comprobación decisiva fue
mirar las dos bases:

| Base | `fechaInicioLineaBase` / `fechaFinLineaBase` |
|---|---|
| Base real del proyecto | **existen** |
| Fixture de CI (construido desde `database/migrations/`) | **no existen** |

El código está bien; la base de pruebas se quedó atrás. **Pero la causa de fondo es peor que el
síntoma:** `grep -rln "fechaInicioLineaBase" database/` no encuentra **ninguna migración** que cree
esas columnas — sólo semillas las nombran. Existen en la base real porque se añadieron fuera del
control de migraciones.

Consecuencia: **cualquier entorno reconstruido desde `database/migrations/` nace roto.** Crear
proyectos en el panel de admin y `src/Services/Pdc/FlujoCajaService.php` fallan con 500. Afecta a un
alta de entorno, a una restauración desde cero y al propio CI, que por eso no puede cubrir esa área.

También significa que **un verde del fixture de CI prueba menos de lo que aparenta** en todo lo que
toque estas columnas.

### Corrección del 2026-08-07, al verificar el arreglo: el fondo es peor

Se escribió la migración (`database/migrations/20260807_proyectos_lineabase_columns.sql`,
idempotente, aplicada tres veces sobre el fixture sin error) y **crear proyecto seguía dando 500**.
Al no darla por buena y volver al log aparecieron dos capas más:

1. Faltaba una **tercera** columna, `costoDiaRetraso`. Se dejó de ir una a una y se comparó el censo
   completo: base real 14 columnas, fixture 11.
2. Con las 14 ya presentes, el error pasó a `Field 'Id' doesn't have a default value`.

La causa raíz real:

> **`general_proyectos_procesos` no tiene NINGUNA migración que la cree.** Su único `CREATE TABLE`
> en todo `database/` está en `database/fixtures/design-system-ci.sql:32`, que es un **fixture de
> CI**. Muchas migraciones la referencian; ninguna la crea.

Y ese fixture ha derivado del esquema real en dos ejes:

| | Base real | Fixture de CI |
|---|---|---|
| `Id` | `int AUTO_INCREMENT` | `int` (sin auto_increment) |
| Columnas | 14 | 11 |

**Dos consecuencias que exceden a la migración:**

1. **No se puede reconstruir la base desde `database/migrations/`**: la tabla núcleo del producto no
   está ahí. Un alta de entorno o una restauración desde cero no arrancan.
2. **CI corre contra un esquema estructuralmente distinto de producción**, así que su verde prueba
   menos de lo que aparenta en todo lo que toque esta tabla — y por eso
   `e2e/tests/admin/proyectos-crud.spec.mjs` no puede pasar hoy por más que se arregle el código.

**Lo hecho:** la migración de las tres columnas queda escrita y verificada (arregla la mitad que sí
afecta a un entorno reconstruido). **Lo no hecho, y por qué:** llevar el DDL de las tablas núcleo a
migraciones versionadas y regenerar el fixture desde ellas es una decisión de arquitectura de datos,
no un parche; y `AGENTS.md` exige para el esquema dry-run, gate de Plannotator, respaldo verificable
y plan de restauración. **No se aplicó nada sobre la base real** — la migración es idempotente y no
haría nada allí, porque esas columnas ya existen.

### 3. Rol denegado `test.V` — verificado, y el RBAC está sano

Contrato de AGENTS.md («un rol permitido y uno denegado») cumplido sobre
`POST /programacion-intermedia/shared-constraints/apply`:

| Rol | Respuesta |
|---|---|
| `test.V` (Visualizador) | **403 Acceso denegado** |
| `test.R` (Residente) | 200, llega a la validación de negocio |

`test.V` recibe 200 en las ocho vistas probadas, incluida `/programa-general-actualizar` — es
**lectura**, y el servidor rechaza la escritura. Sin hallazgo.

Sí confirma de paso el hallazgo `R-1` de la campaña, que sigue abierto: el 403 responde HTML pelado
(`<h1>Error 403</h1><p>Acceso denegado.</p>`), sin página de error.

### 4. Revalidación contra `main` — hecha: los seis hallazgos siguen vivos

Verificado contra `main` (`dbc3536a`), no contra la rama del barrido:

| Hallazgo | Estado en `main` |
|---|---|
| B-1 tres scripts con `process.exit` en el `testDir` | presentes |
| B-3 goldens de PG y PI sin tocar desde el 6-ago 10:30 | confirmado |
| B-4 `admin/views/` sin un solo `<main>` | cero coincidencias |
| B-5 `is-zero` en PI y PS, **ausente en PG** | confirmado |
| B-6 los ocho CDN de `/control-cambios` | presentes |
| B-7 `core.css` sin capa en `/login`, `/password/forgot`, `/password/reset` | confirmado sirviendo |
| B-8 dos `<h1>` en `controlCambios.view.php` | confirmado |

**Ninguno es un fantasma ya arreglado.**
