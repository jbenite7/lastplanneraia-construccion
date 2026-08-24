---
capa: fuente
tipo: goal
estado: vigente
fecha: 2026-08-18
areas: [proceso]
tags: [proyecto]
fuente: sesión de coordinación 2026-08-18 (inventario de planes, specs y sesiones + 22 decisiones del usuario); consolidación de fases 2026-08-18
resumen: "Fuente única de pendientes: las 22 fases de los cuatro programas, su orden y su estado"
project: lps-aia
type: tasks
status: activo
updated: 2026-08-24
---

# Tareas

**Fuente única de pendientes.** El trabajo corre en un enjambre de sesiones sobre
`.claude/worktrees/` (ver [[docs/coordinacion-sesiones]]); cada frente tiene su
`goals/<slug>/goal.md` y su registro en `decisiones/`. Esta lista es la vista para retomar sin
releer el chat de cada sesión.

Para **en qué fase va cada programa**, el detalle por bloques al final de esta misma página: la
cola vivía en `memoria/goals/cola-de-pendientes.md` y se migró aquí el 2026-08-19, por decisión de
Felipe, para no sostener dos fuentes únicas. Para el **estado de cada goal**, [[goals/estado]].

> Releído el 2026-08-19 contra `origin/main`. La versión anterior de este archivo se escribió desde
> un árbol 114 commits atrasado y daba por activos cinco frentes que ya habían cerrado y publicado.
> **Es el modo de fallo a vigilar aquí:** este archivo se escribe desde lo que una sesión ve, y una
> sesión ve su worktree.

## Bloqueantes

Ninguno. El único que había —«abrir una coordinadora nueva»— quedó resuelto el 2026-08-19 cuando
Felipe declaró el reparto y consolidó el repo en una sola sesión. **Y estaba mal planteado desde el
principio:** `docs/coordinacion-sesiones.md:18` dice que «el reparto lo declara el usuario, no lo
reclama nadie», así que no tener coordinadora no es una carencia que haya que subsanar — es el
estado por defecto mientras Felipe no reparta.

## Ahora

- [ ] **Auditoría de specs 2026-08-20 — pendientes nuevos que no estaban en esta lista.** Las 61
  specs vigentes se verificaron contra el código; el informe completo, con evidencia y cada
  pendiente atado a su plan, está en
  [[docs/superpowers/reports/2026-08-20-auditoria-estado-specs]]. Lo que no estaba anotado en
  ningún lado:
  1. ~~`organizar-la-casa` sin ejecutar~~ — **HECHO (2026-08-20)**: vistos en
     `decisiones/vistos/`, historial de sesiones versionado, plantillas borradas, las siete
     reglas en `docs/coordinacion-sesiones.md` y `AGENTS.md` las referencia.
     Ver [[goals/organizar-la-casa/goal]].
  2. ~~`estados-severidad-contrato` bajo 3 niveles~~ — **HECHO (2026-08-20)**: spec reescrita
     con notas de revisión fechadas. La ejecución del frente **también cerró el mismo día** (ver
     el cierre de `ds-f1a-estados-severidad` más abajo): la contención se midió, los frentes de
     `bold-neumann` ya habían terminado, y la saturación del filete en Intermedia **no se confirmó
     en pantalla** — capturas en `goals/ds-f1a-estados-severidad/evidence/` para veto de Felipe.
  3. ~~Verificación de `/indicadores` y CNP/CNC/CIC~~ — **HECHO (2026-08-20)**: `/indicadores`
     está migrada (pilot; su contenido es un iframe). **CNP/CNC/CIC son legacy real**: el shell es
     `aia-*` pero `legacyCards.js` pinta todo con clases legacy. F0-022 (mayor) lo detectó sin
     tarea que lo cierre → la migración entra a **DS-F2** con dueño (fila nueva abajo) y los dos
     planes de UI-audit (2026-07-31, 2026-08-01) quedan **superados como vehículo**.
  4. **Humo del PDC v2 en `prueba-lps`** — la mitad anónima **HECHA (2026-08-20)** con códigos
     crudos: `/plan-compras` 302→login (enrutado y protegido), bundle `pdc.js` 200, `/dev/entrar`
     302→login (candado puesto). **La mitad autenticada quedó HECHA el mismo día**: Felipe
     abrió sesión (`test.R`) en el navegador integrado y sobre ella se verificó SPA con datos
     reales, APIs en 200, RBAC permitido/denegado y consola limpia. El paso previo de CP-F-E
     está cumplido. Evidencia:
     [[docs/superpowers/reports/2026-08-20-cierre-pendientes-auditoria]].

- [ ] **apply-recalculo-estados en PRODUCCIÓN** — el apply sobre **desarrollo** ya se ejecutó
  (`aa965bf5`, 2026-08-19 13:40): 40.664 filas migradas, acta en
  `goals/apply-recalculo-estados/acta-del-apply.md`, reconciliación exacta. **Producción sigue sin
  tocar y necesita su propia autorización explícita** — publicar en `main` no la concede. Cuando
  llegue, la lección del apply de desarrollo aplica: **el respaldo probado horas antes ya no cubría
  la base** (8 filas nuevas sin respaldo), así que se rehace y se vuelve a probar la restauración
  inmediatamente antes, no la víspera.
- [ ] **runtime-budgets-al-ci** — Fase 1 de `docs/superpowers/plans/2026-08-19-runtime-budgets-al-ci.md`,
  sha verificado `c23b1c6a`. Desbloquea el único gate `blocked` de los nueve de
  `closeout-evidence.json`. Andamio declarado, no inversión: DS-F3 lo reemplaza.
- [ ] **DS-F1, lo que queda del contrato** — la escala de estado cerró (F1a). Faltan tokens,
  primitivas `aia-*`, escala de severidad y escala de z-index. Arranca con brainstorming: el
  contrato es decisión de negocio. Entrada lista: los 68 hallazgos de DS-F0.
- [ ] **Cerrar antes de integrar: `linea-base-contractual`.** Entró en el `main` local el
  2026-08-19 sin haber declarado su condición de hecho, y **Felipe ordenó sacarlo: «que se cierre
  primero»**. Su trabajo está intacto en `claude/elated-golick-e27253`, 10 cambios. Lo que le falta
  no es código: es la sección `## Cierre` con la verificación que demuestre que terminó.

  **`semanal-fondo-por-matiz` ya cerró y está publicado** (`2fc5998e`, 2026-08-19): las dos fases
  con cinco fondos distintos, filete solo en `urgent` y `attention`, capturas miradas a 1180×820
  dark, suite del gate 4/4 en `RC=0` **después** de integrar. Su cierre destapó que la sonda de la
  fase Calificación **no forzaba la fase** y lo declaraba igual, porque comprobaba su propia
  sustitución de texto: ver `## Cierre` en [[goals/semanal-fondo-por-matiz/goal]].

- [x] 2026-08-19 — **cola de estados, severidad y color**: los siete pendientes atendidos. Cuatro
  ejecutados (remapeo de Programa General con `Fuera de Ventana` —el 39,3 % de la tabla, que se
  pintaba igual que `Actividad Futura`—, la crema de la leyenda de Intermedia, y dos guards que antes
  no podían ponerse rojos) y cinco decisiones medidas y elevadas en [[DECISIONES_PENDIENTES]].
  Registro: [[goals/cola-estados-severidad/goal]].

- [x] 2026-08-19 — **repaso de TODOS los specs y frentes**: de 13 `goals` sin cerrar quedaron **6**.
  Siete se cerraron con verificación de hoy: `adopcion-logo-construccion`, `pdc-tanda2-plan-verdad`,
  `apply-recalculo-estados` (solo desarrollo), `contadores-cero`, `semana-fija-visual`,
  `repaso-usabilidad-no-tablas` y `contrato-estados-modulo-fantasma`. Los dos últimos estaban
  cerrados y firmados en prosa, **sin el encabezado `## Cierre` que el mapa de estado lee**.

- [x] 2026-08-19 — **el CI llevaba 40 corridas sin pasar** (23 `failure`, 17 `cancelled`, ni una
  verde) por una sola aserción: `full-app-flow` exigía en móvil que el `body` reservara sitio para
  el carril, justo lo que la spec del menú flotante derogó el 2026-08-14. Arreglado en `ab2c34f1`.
  Y el lint de wiki denegaba publicaciones por basura que git ignora: arreglado preguntándole a git.

- [ ] **`runtime-budgets-al-ci` y `gates-al-ci` están encadenados a una corrida verde de CI.**
  Fase 1 reproducida: el gate `runtime-budgets` no es un baseline caducado — su medición **solo
  puede producirse dentro de GitHub Actions**, porque exige `CI_RUN_ID`, `CI_GIT_SHA` y dos huellas
  más contra un worktree limpio. La Fase 2 del plan no tiene nada que arreglar. En cuanto CI pase,
  la Fase 3 puede tomar la procedencia y los dos frentes cierran.

- [x] 2026-08-20 — **Las once decisiones pendientes, RESUELTAS** en sesión dedicada con Felipe
  (D-1 a D-9, D-11 y los plugins de Obsidian). Ninguna queda abierta; el detalle y el porqué de cada
  una, en [[DECISIONES_PENDIENTES]] §«Ronda de decisiones del 2026-08-20». Lo que destraba: **D-11
  es el único paso rojo del CI**, y D-7/D-8/D-9 sacan de la parálisis a tres frentes cuyas
  condiciones contaban artefactos que ya no existen. Los plugins quedaron instalados y verificados
  en pantalla; **Iconize se excluyó** por estar declarado como descontinuado por su autor.

  <details><summary>La redacción anterior de este punto</summary>

- [ ] ~~Las nueve decisiones de [[DECISIONES_PENDIENTES]] esperan a Felipe.~~ D-1 realces por
  condición del dato · D-2 la excepción crítica del chip · D-3 los 30 estados sin `key` · D-4
  `foundation-shell` y sus 20 rutas sin escenario · D-5 la variante de pestañas que le falta a
  `navigation` · **D-6** el vocabulario de la cascada, cuyo objetivo numérico ya se cumplió solo
  (25 cadenas contra las 29 que pedía) · **D-7** `bi-control-tower-gemini`, parado mes y medio por
  una condición imposible · **D-8** `design-system-nucleo-gobernanza`, que exige quince gates donde
  el archivo declara nueve · **D-9** hasta dónde llega la reapertura móvil y el tema claro.
  Cada una lleva su medición hecha; ninguna necesita más trabajo antes de decidirse.

  </details>

- [ ] **Ficha de trampa pendiente: «el guard que valida su declaración, no su efecto».** Es la
  tercera vez que se mide la misma familia en este repo —hermana de
  [[memoria/trampas/guard-de-texto-no-ve-el-parseo]] y
  [[memoria/trampas/guard-valida-declaracion-contra-si-misma]]— y le falta ficha propia en
  `memoria/trampas/`. El caso nuevo, con su medición, está en el `## Cierre` de
  [[goals/semanal-fondo-por-matiz/goal]]: no se escribió allí porque ese frente no declaraba la
  ruta `memoria/trampas/**`.

- [ ] **linea-base-contractual** — sembrado por migración SQL, con `database/migrations/**`
  autorizado explícitamente por Felipe para este frente. **No tiene `goals/<slug>/` propio**: su
  registro vive solo en `decisiones/linea-base-contractual-coordinadora.md`.

  **Dos hallazgos RELATADOS y sin verificar por esta sesión** (2026-08-19, de la sesión que integró
  las ramas). Verificarlos es el primer paso del frente, no un trámite:
  1. **La migración se corrió contra desarrollo y no modificó ni una fila.** De 30 proyectos sin
     línea base, ninguno tiene cronograma consolidado usable, así que el `JOIN` no alcanza a
     ninguno. Se ejecuta, sale en verde y no hace nada — el mismo patrón que
     [[el-contador-no-mide-el-archivo]]: una herramienta que ante «no hay nada que hacer» devuelve
     algo con forma de resultado. Respaldo en `~/Documents/respaldo-lineabase-20260819-2037.sql`.
  2. **`test_bi_programa_general_chart_values.php` se pone rojo con el merge** (los `FALLA` de nivel
     `datos-proyecto` pasan de 12 a 13). No es regresión: el frente movió el origen de la fecha
     contractual y el test todavía afirma lo viejo. Si el comportamiento nuevo es el correcto, el
     test hay que actualizarlo — y eso es parte de cerrar, no algo aparte.

  Ese test **se arma sus propios fixtures** (34 inserts en el archivo), así que ninguna migración
  sobre datos existentes lo va a tocar.
- [ ] **bi-control-tower-gemini** — bloqueado desde el 2026-08-10 por causa mal diagnosticada: no
  es «falta aprobación visual», es que pide aprobar 6 modos y 3 usan el tema `linen`, retirado el
  2026-07-25. Hay que rehacer la condición de hecho, no correr los tests. **Ya NO depende de
  MO-F3** (D-9, 2026-08-20): la condición se recorta a los tres modos dark y el frente puede
  cerrar sin esperar a ningún tema claro.

- [ ] **Ordenar `CHANGELOG.md`.** No está en orden cronológico inverso: `[1.1.1]` y `[1.1.0]`
  aparecen antes que `[Sin publicar]` y que `[1.2.0]`. Detectado el 2026-08-19 y **no corregido en
  el mismo turno a propósito**: reordenar 400 líneas de historia ajena a mano arriesga perder
  contenido, y eso pide su propia pasada con verificación.

## Diferibles

- [ ] **PI · test de teclado para el recorrido del globo (ArrowUp/ArrowDown)** — la Task 8 de
  [[docs/superpowers/plans/2026-08-21-habilitacion-en-una-columna]] verificó el binding manualmente
  contra el navegador real; falta el Playwright dedicado (los botones sí tienen prueba, en
  `tests/browser/pi-globo-recorrido.mjs`).
- [ ] **PI · reponer el tooltip «?» educativo por restricción** — `hot.js:4564-4605` quedó código
  muerto al vaciar `headerIndexToRestrictionProp` (fix final de
  [[docs/superpowers/plans/2026-08-21-habilitacion-en-una-columna]]): ya no hay forma de abrir el
  texto explicativo de una restricción desde la cabecera. El globo cubre la consulta al hacer clic
  en la celda, pero no es lo mismo que la ayuda educativa que existía antes.
- [ ] **PI · unificar `construirCuadrito`** — sigue duplicado entre `hot.js` (IIFE, contrato
  `item.prop`) y `readiness-popover.js` (módulo ES, contrato `item.key`). Ambos pintan lo mismo
  (relleno, visto, N/A) tras el fix final del frente, pero unificarlos exige diseñar un puente entre
  los dos sistemas de módulos — no es un movimiento mecánico.
- [ ] **PG · `alerta-restricciones` sigue en tinte de color** — no tiene `hue` asignado en
  `docs/design-system/state-semantics.json` (`moduleMappings.programa-general`), así que la Task 1
  de [[docs/superpowers/plans/2026-08-21-habilitacion-en-una-columna]] no pudo migrarlo a la familia
  sólida como al resto de la leyenda de Programa General. Arreglar el contrato primero.

- [ ] **BI · 336 filas huérfanas en `programacion_semanal`** — sin `unique_id` que exista en
  `programa` (verificado en `lastplanneraia_dev` con `LEFT JOIN`). Destapado el 2026-08-20 al
  aplicar el arreglo de mojibake de F0 (Control Tower): una fila huérfana bloqueó el `UPDATE` con
  un error de llave foránea. No se investigó el origen ni si están en producción — solo se
  confirmó que existen y que no se tocaron. Origen:
  [[docs/superpowers/plans/2026-08-20-control-tower-f0-higiene-datos]], Task 4.
- [ ] **BI · `tests/test_causas_codificacion.php` tiene un punto ciego de colación** — usa
  `SELECT DISTINCT` sin `BINARY`; bajo `utf8mb4_general_ci`, un texto roto («Diseńos») y su
  versión ya reparada («Diseños») colapsan al mismo grupo `DISTINCT` y MySQL puede devolver el
  representante correcto, escondiendo la fila rota. Confirmado el 2026-08-20: el test reporta
  PASA con 2 filas todavía rotas (las huérfanas de arriba), verificado con `LIKE BINARY` directo.
  Arreglo: reescribir la detección con `LIKE BINARY` o comparar bytes, no `DISTINCT` normal.
- [ ] **BI · `tests/test_cip_poblado.php` no prueba realmente el arreglo del backfill** —
  solo comprueba `COUNT(DISTINCT profesional) > 0`, que pasaría igual con el código viejo si
  coincide una sola semana. Debería aseverar cobertura multi-semana (`COUNT(DISTINCT Semana)`).
  Hallazgo de la revisión final de F0, 2026-08-24. Origen:
  [[docs/superpowers/plans/2026-08-20-control-tower-f0-higiene-datos]], Task 1.
- [ ] **BI · el backfill de `cip` no tiene guarda de costo** — `updateCICProyectos()` repite
  ~4 consultas por semana por proyecto en cada corrida, incluidas semanas ya completas que no
  cambian. Un proyecto en semana 60 son ~240 consultas por corrida solo para reconfirmar lo ya
  hecho. No medido bajo carga real. Guarda barata propuesta: saltar la semana si
  `COUNT(*) FROM cip WHERE Semana = ?` ya iguala el número de responsables de esa semana.
  Hallazgo de la revisión final de F0, 2026-08-24.
- [ ] **BI · `scripts/higiene/reparar-mojibake-causas.php` no está acotado por `project_id`** —
  escribe a través de todos los proyectos. Defendible para higiene global de catálogo, pero
  contradice la regla general de aislamiento del repo; falta un comentario que lo declare
  explícito. Hallazgo de la revisión final de F0, 2026-08-24.
- [ ] **BI · dos tests de F0 salen "sospechosos" para el runner por el mismo patrón** —
  `tests/test_causa_atribucion.php` y `tests/test_causas_codificacion.php` imprimen `"PASA: ..."`,
  y `PASA` (con A) no contiene ninguna señal reconocida por `SENALES_DE_COMPROBACION` (`pass`,
  `ok`, `comprobacion`, `comprobación`, `✓`, `correcto`) — difiere de `pass` en la cuarta letra.
  Los dos comprueban algo de verdad (ejecutan y pasan directamente, rc=0), pero el runner no los
  reconoce y los marca sospechosos. Arreglo: cambiar el texto de éxito de los dos a algo que
  incluya una señal reconocida, p. ej. `"PASA (correcto): ..."`. Preexistentes de las Tareas 3 y 4
  de F0 (ambas ya cerradas con revisión limpia); confirmado por el controller el 2026-08-24 que
  son idénticos contra el commit previo a la ronda de arreglos de la revisión final — no los
  introdujo esa ronda. Origen:
  [[docs/superpowers/plans/2026-08-20-control-tower-f0-higiene-datos]], Tasks 3 y 4.

- [ ] **A11y · el gemelo callado del filtro de cabecera (Programa General)** — de 24 botones de
  filtro idénticos, `markDecorativeHeaderTriggers` marca 12 con `aria-hidden` y deja 12 sin marcar.
  Son 12 columnas por 2 contenedores (`ht_master` y `ht_clone_top`), así que cada columna tiene un
  botón anunciado y su gemelo callado — el mismo defecto que el comentario de esa función venía a
  cerrar, reaparecido por el otro lado. Medido en vivo el 2026-08-24; anotado en
  `public/js/modules/programa_general/hot.js:2411`. Con `navigableHeaders: true` el camino de
  teclado NO pasa por esos botones, así que marcarlos los 24 es lo coherente.

- [ ] **DS · dos salidas del sistema en las tablas** — `handsontable-module.css:579` usa
  `font-family: monospace` literal en vez de `--ds-font-mono`, y
  `handsontable-header-global.css:167` llama a «Font Awesome 5 Free» directamente en vez de una
  primitiva de ícono. Los dos son de una línea; van juntos porque son el mismo tipo de fuga.

- [ ] **CI · el mismo SQL declarado en cuatro listas que deben coincidir** — al sembrar
  `general_flags` (2026-08-24) hubo que tocar `database/fixtures/design-system-ci.Dockerfile`,
  `scripts/design-system-ci-preflight.mjs`, `tests/design-system/ci-preflight.test.mjs` y
  `tests/design-system/visual-ci-contract.test.mjs`. El gate rechazó **tres veces seguidas**, una
  por lista, así que la red funciona; lo caro es que el comentario que advertía de esto hablaba de
  «las dos listas» y ya son cuatro. Evaluar si una sola fuente derivada las sustituye.

- [ ] **CI · regenerar la baseline de presupuesto de runtime** — `runtime-baseline-0.3.5.json` se
  grabó el 2026-08-18 y desde entonces entraron **56 commits de código** a `main`, incluidas las
  cuatro olas del replanteo de coloreado. El gate no lo cazó porque estaba apagado detrás del
  fallo de `general_flags` (2026-08-21 → 2026-08-24). Al destrabarlo aparecen dos excesos:
  `jsGzipBytes` 643.832 contra un techo de 638.380, e `initializationMs` 596,5 contra 301,9.
  **No son del frente de tablas:** su delta de JS son 2.203 B gzip sobre un exceso de 5.452 B, e
  `initializationMs` mide `performance.now()` de carga de página completa. Regenerar la baseline
  es **aprobación designada de Felipe**, igual que los goldens: no se toma de paso.

- [ ] **BI · `status-critical` usado como color de serie en `bi-spa.js:3704`** — es la mitad
  `-text` de un par de estado (`#ffcdc8`, rosa pálido para tinta), no un color de dato. Mismo error
  de rol que se corrigió el 2026-08-24 en los botones de Programación Semanal. Ya estaba anotado
  como «trampa medida, sin auditar» en [[docs/PDC-AUDIT]] §Trampa medida; queda aquí para que salga
  de ese pie de página. El rojo de series es `critical` (`oklch(65% 0.18 26.3)`). Contexto y receta:
  [[docs/archive/superpowers/specs/2026-07-28-paleta-estado-oscura-design]] §Lo que se rompió después.

- [ ] **Tablas · retirar DataTables, el tercer motor** — quedan cinco superficies en
  DataTables 1.10.21 (2020, con jQuery detrás): `views/programacion-semanal/CIC|CNC|CNP.view.php`,
  `views/control-cambios/controlCambios.view.php` y las tablas del panel `admin/`. El destino es
  AG Grid, ya en uso en Plan de Compras. **Sin frente propio y sin fecha:** ninguna de esas
  pantallas duele hoy, así que la regla es «quien entre a una de ellas por otra razón, sale con
  AG Grid». Al hacerlo, llevarse también las cifras tabulares, que ese carril no las tiene
  (`font-variant-numeric: tabular-nums`, ya aplicado en Handsontable y en el PDC). Decisión de
  rumbo del 2026-08-24 en [[ROADMAP]].

- [ ] **Deploy · limpiar drift residual en producción** — stash `pre-deploy-20260820-185447`
  (SmtpMailer, ya superado por `21243c7e` versionado) y 7 `.bak` de `indicadores.view.php` del
  2026-07-23 en `public_html`. Confirmar y borrar.

- [ ] **CI · G4 path filters** — excluir de los triggers lo que ningún gate lee (`memoria/**`,
  `.md` de raíz); `docs/design-system/` es contractual y NO se excluye. Origen:
  [[docs/superpowers/specs/2026-08-20-deuda-ci-design]].
- [ ] **CI · renombrar `design-system.yml` → `ci.yml`** — el nombre quedó pequeño: el workflow
  custodia el repo entero (suite PHP, RBAC, E2E, presupuestos), no solo el design system. Exige
  barrido de referencias por ruta (`visual-ci-contract.test.mjs`, scripts, docs, comandos
  `gh run list --workflow=`) y parte el historial de corridas. Hacerlo como micro-frente propio,
  idealmente junto a G4, que también toca los triggers. Decisión de Felipe 2026-08-20.
- [ ] **CI · G7 paralelización** — medir duración por paso primero; candidato: PHPStan como job
  paralelo (no necesita la app levantada). Origen: spec 2026-08-20-deuda-ci-design.
- [ ] **CI · G8 job summaries** — volcar recibos y presupuestos ya generados a
  `GITHUB_STEP_SUMMARY`. Origen: spec 2026-08-20-deuda-ci-design.
- [ ] **CI · zizmor** — auditoría de seguridad del YAML complementaria a actionlint; exige tooling
  extra. Origen: spec 2026-08-20-deuda-ci-design.
- [x] 2026-08-20 — **CI · Frente 2 (G2, cache de capas Docker)**: ejecutado en alcance A
  (cache buildx `type=gha` de la capa base, Dockerfile intacto). Medido en caliente: build del
  estático 81 s → 20 s (−75 %); runtime 93 s → 72 s (−23 %). Cierre en
  [[goals/deuda-ci-frente-2/goal]].
- [ ] **DECISIÓN (Felipe) · G6 branch protection / merge queue** — cambia el flujo de publicación
  de todas las sesiones (`publicar.sh` → PRs). No aplicar sin visto explícito. Origen: spec
  2026-08-20-deuda-ci-design.
- [ ] **PROPUESTA (Felipe) · hook `task-completed-verify.sh`** — corre `composer test` en el host,
  donde composer no existe (repo Docker-only): rojo falso en toda tarea sin código. Es `~/.claude`:
  proponer el fix, no aplicarlo.
- [ ] **Escribir el cierre de dos goals ya ejecutados** — `pdc-tanda2-plan-verdad` y
  `adopcion-logo-construccion` tienen el trabajo hecho y ninguna sección `## Cierre`, así que la
  regla de lectura los cuenta como abiertos. Es escribir el cierre, no re-ejecutar.
- [ ] **Enchufar `--estricto` a `npm run test:wiki`** — hoy el gate corre en estricto por línea de
  comandos, pero la decisión de hacerlo obligatorio es de contrato: a partir de ahí toda fuente
  nueva nace con frontmatter o el gate se pone rojo. El hueco ya se midió: una fuente entró sin
  declarar por un merge y el gate no lo detectó.
- [x] 2026-08-20 — **Plugins de Obsidian instalados y verificados en pantalla** (Dataview, Tasks,
  Kanban, Excalidraw, Homepage y el tema Minimal), publicado en `2888ab77`. El bloqueo original
  —«no se puede verificar sin abrir Obsidian»— se resolvió abriéndolo. **Iconize quedó fuera**: su
  autor lo declara descontinuado. **Kanban entró con advertencia**: funciona, pero busca quien lo
  mantenga. Hallazgo de paso: **el vault de `lps-aia` no estaba registrado** en la app, y
  `visor-gantt` sigue apuntando al disco Crucial X6 — roto desde la mudanza.
- [ ] **Grupos de color del grafo** (`.obsidian/graph.json`) — sigue pendiente, es lo único que
  quedó de la Fase 0b.
- [ ] **Proponer verificación de tests en contenedor como config por proyecto.** La vía Docker se
  quitó del gate global de `~/.claude` el 2026-08-19; este repo es 100% dockerizado y su
  `verify.quick` en `.claude/gate.yaml` evita PHP/Docker por costo, pero el resto de la suite sí
  necesita el contenedor. Afecta config global, no solo este repo.
- [ ] **Fusionar contenido solapado de `AGENTS.md` / `GEMINI.md` / `CLAUDE.md`** con lo que ahora
  vive en [[README]] y [[ROADMAP]]. No se tocó su contenido en el bootstrap, solo se enlazó.
- [ ] **Plan espacio SiteGround** — tareas 1–5 de
  `docs/superpowers/plans/2026-08-18-espacio-cuenta-siteground.md`.
- [ ] **Dropdown PS sobre selector de semana** — diagnóstico del stacking en
  `/programacion-semanal`, con `systematic-debugging`.
- [ ] **Backlog Fase 7-10** (notificaciones por rol, QA sistemático, despliegue gradual, shared
  schema): sin frente abierto. Ver [[ROADMAP]].
- [ ] **Realces sin declarar** (r0 de Programa General y ruta crítica de Programación Semanal) como
  decisión única de producto — en la cola de [[docs/decisiones-pendientes]], sin prisa.

- [ ] **Rediseñar el proxy de la alarma de veracidad.** Hoy cuenta commits y **no sabe de qué habla
  la wiki**: pesa igual un commit en un área con quince páginas que uno en un área sin ninguna.
  Ahora sería afinable —las 13 áreas tienen mapa y las fuentes declaran su `areas`—, pero es
  cambiar el proxy entero, no recortarlo. Los tres descuentos del 2026-08-19 ya exprimieron el atajo.
- [ ] **Versionar el estado de coordinación.** `.claude/vistos/` está en `.gitignore:219` y
  `decisiones/gobierno-relato-de-autorizaciones.md` está sin commitear, así que ninguna sesión que
  trabaje en un worktree los ve. Precedente medido el 2026-08-11: un archivo de estado compartido
  sin versionar se llevó doce hallazgos sin diff y sin rastro.

## Lo que no está aquí a propósito

**El despliegue a producción** (CP-F-E, ~1.255 commits de retraso) no es una tarea de esta lista:
necesita autorización propia y explícita de Felipe, siempre, y publicar en `main` no la concede.

## Hechas (últimas 10)

- [x] 2026-08-24 — **Habilitación en una columna — Programación Intermedia**: 15 commits, ejecutado
  con `subagent-driven-development`. Fusiona 7 columnas de restricción + `% Liberación` en una
  columna de cuadritos con globo de liberación (abrir/cerrar/foco/teclado/recorrido/guardado
  idéntico al de hoy), tabla cabe a 1100 sin scroll (antes 1490), leyenda de PI y PG con color
  sólido, tarjeta móvil comparte pieza con el globo. Revisión final encontró y corrigió 5 hallazgos
  (2 Critical) antes de publicar; goldens de `programacion-intermedia.visual.mjs` regenerados con
  aprobación visual de Felipe. Spec: [[docs/superpowers/specs/2026-08-20-habilitacion-en-una-columna-design]]
  · plan: [[docs/superpowers/plans/2026-08-21-habilitacion-en-una-columna]] · procedencia del golden:
  [[docs/design-system/manifests/programacion-intermedia.goldens]].
- [x] 2026-08-20 — **Los tres pendientes restantes de la auditoría de specs**: spec de severidad
  reescrita a 3 niveles, veredicto de indicadores/CNP-CNC-CIC (legacy real → DS-F2), humo anónimo
  de `prueba-lps` en verde. [[docs/superpowers/reports/2026-08-20-cierre-pendientes-auditoria]].
- [x] 2026-08-20 — **Auditoría de estado real de las 61 specs** contra el código, publicada en
  `3144ca5e`: 44 ejecutadas · 16 parciales · 1 pendiente · 12 cerradas. Informe con evidencia:
  [[docs/superpowers/reports/2026-08-20-auditoria-estado-specs]].
- [x] 2026-08-20 — **organizar-la-casa** ejecutada: coordinación versionada y las siete reglas
  escritas ([[docs/coordinacion-sesiones]]).
- [x] 2026-08-19 — **El gate de publicación frena las integraciones**: publicar con merges exige
  `--con-merges` y lista qué frentes entran y cuáles declaran cierre. Nace del cuarto choque del
  día: dos frentes a medio terminar entraron en `main` y solo los detectó una revisión a mano.
  **No bloquea por «goal.md sin cierre» a secas** —un frente activo commitea su goal mientras
  trabaja, y eso serían falsos positivos a diario— así que frena por el hecho comprobable y enseña
  el estado para decidir con datos.

- [x] 2026-08-19 — **Los nueve goals en plantilla, cerrados** (`697978ec`): los nueve habían
  corrido; ocho reciben objetivo y cierre con evidencia re-medida hoy, y `a187ccda` se borra por
  ser un id de sesión, no un frente. De ahí sale por qué `runtime-budgets` sigue `blocked`.

- [x] 2026-08-19 — **DS-F0 cerrada y publicada** (`567e566e`): `docs/design-system/auditoria/` con
  68 hallazgos clasificados sobre un censo de 257 rutas, sin tocar código de producto.
- [x] 2026-08-20 — `replanteo-coloreado-estados` cerrado: el chip solido pasa a portar la identidad
  (paleta auditada WCAG AA + manual AIA), el filete queda homogeneo en los tres modulos con el
  marcador `ready`, y **nada se recorta en silencio** — tres olas en workflows dependientes
  erradicaron elipsis, `overflow-x: hidden` irreversible, palabras partidas y ~20 tamanos fuera de
  rampa. Efecto no previsto: Intermedia muestra sus 9 filas donde antes cabian 5. Censo de las 22
  tablas de la app en `goals/replanteo-coloreado-estados/censo-tablas.md`.
  **Pendientes que dejo, cada uno frente propio:** cabeceras de grilla desalineadas (PI 0.75rem vs
  PS 0.72rem, decision de producto); `overflow-wrap: anywhere` en el chip de PI; `1.75rem` del boton
  de cierre de modal en PS; siete `console.log('[PI-DEBUG]')` tras flag; y el resto del censo
  (Admin y vistas HTML) fuera de estas olas por alcance.
- [x] 2026-08-20 — `ds-f1a-estados-severidad` cerrado: maquinaria adaptada al contrato de 3
  niveles (filete apagado en `Controlado` `1ff946f8`, PG remapeado con `Fuera de Ventana`
  `8418449a`), publicada, y verificada en pantalla post-fix a 1180×820 dark (sondas y capturas en
  `goals/ds-f1a-estados-severidad/evidence/`). Los pendientes que sobreviven son frentes propios
  (`r0` de PG, fantasmas de `/plan-compras`, `states-feedback.css:162`).
- [x] 2026-08-19 — **Fase 0b, wiki v2**: las seis tandas cerradas y publicadas, lint estricto verde.
- [x] 2026-08-19 — `ds-f1a-estado` (`4a152a54`): la escala de estado del contrato, medida contra
  50.966 actividades reales.
- [x] 2026-08-19 — `estados-fuera-de-ventana` (`aeaa7a77`): los dos calculadores producen
  `Fuera de Ventana` desde la séptima semana, y por primera vez tienen pruebas.
- [x] 2026-08-19 — `migracion-estados`: dry-run, respaldo probado restaurando 2.024 filas, y guarda
  que deniega el `--apply` con `RC=1`. Prepara, no aplica.
- [x] 2026-08-19 — `bug-coloreado-severidad` cerrado.
- [x] 2026-08-19 — Bootstrap de la wiki LLM de 5 archivos en la raíz.
- [x] 2026-08-18 — Fuente única de las 22 fases; lo verificado se archiva (`fc098810`).
- [x] 2026-08-18 — Los goals dejan de escaparse del control de versiones (`9711ae3f`): regla general
  al final del `.gitignore` en vez de lista blanca a mano.
- [x] 2026-08-18 — El correo sale por el MTA local del hosting, no por relay externo (`21243c7e`).

## El detalle, por bloques

**Esta página manda.** Es el único sitio donde se mira qué está pendiente y en qué orden. El
detalle de cada decisión sigue en `decisiones/<frente>.md` y en cada `goals/<slug>/goal.md`, pero
el **estado y la prioridad** se leen aquí y no se deducen de ningún otro lado.

Se actualiza al cerrar o reordenar, no se deja derivar. Nada de lo que hay aquí es contrato:
precedencia **código > `AGENTS.md` > `memoria/`**.

## Por qué existe esta consolidación

El proyecto tenía sus fases repartidas en cuatro planes que **numeran igual sin ser lo mismo**: hay
tres cosas distintas llamadas «F0» y dos llamadas «F1». Nadie podía responder «¿dónde quedó la
fase X?» sin abrir cuatro archivos y adivinar a cuál se refería. Consolidado el 2026-08-18.

Segundo hallazgo de esa consolidación, que vale por sí solo: **las casillas de los planes no miden
nada.** De 435 casillas repartidas en 17 planes, hay **0 marcadas** — incluidos planes cuyo trabajo
está en producción. Es el mismo defecto que `coordinating-agent-sessions` tiene medido en su propio
plan. Para saber si algo está hecho, se verifica **contra el código**, no contra su casilla.

## Bloque 0 — Arranque (bloquea todo lo demás)

Orden del usuario, 2026-08-18: los frentes y chips no arrancan hasta cerrar estas dos.

| Fase | Qué es | Estado |
|---|---|---|
| **Fase 0** | Mudanza del repositorio a `~/Developer/lps-aia` | **HECHA** (2026-08-18). Copia verificada (fsck limpio, 2.7G), 6 worktrees reparados, montaje Docker actualizado, web 200, PHP 24/24. Respaldo en `/Volumes/Crucial X6/Developer/lps-aia.pre-mudanza-2026-08-18`; borrarlo es decisión aparte. La BD no se movió: vive en el volumen Docker `htdocs_db_data` |
| **Fase 0b** | Replanteo completo de la wiki: metodología Karpathy intacta, Obsidian visual, vault entero etiquetado y frontmatter en todas las fuentes (solo metadato; el cuerpo sigue intocable) | **HECHA** (2026-08-19), las seis tandas, publicadas. `wiki-lint.mjs --estricto` verde sobre 156 páginas y 414 de 417 fuentes. **Con dos salvedades declaradas:** los plugins de comunidad quedaron fuera por decisión del usuario, y los grupos de color del grafo quedaron pendientes por no poder verificarse sin abrir Obsidian |

Las seis tandas de la 0b, en `docs/superpowers/plans/2026-08-18-wiki-v2-visual.md` (~2 jornadas;
cada tanda cierra en verde antes de la siguiente):

| Tanda | Qué hizo | Cerrada en |
|---|---|---|
| **1 · Esquema y herramientas** | `wiki-operacion.md` a v2, lint v2, `wiki-frontmatter.mjs`, 5 moldes | `7208edf9` |
| **2 · Frontmatter a las fuentes** | 413 archivos por lotes, con revisión entre uno y otro. **Cero borrados**: solo se añadió metadato | `e5c540c3` |
| **3 · Retag fino** | `capa: wiki` en las 151 páginas, `generado` en 26, `trampa` en 4 | `26a8fe80` |
| **5 · MOCs completos** | 5 mapas nuevos; las 13 áreas tienen MOC. `moc` sale del vocabulario | `58240c2c` |
| **4 · Capa visual** | 13 vistas Bases, 3 canvas, dashboard, snippet de severidad. **Sin plugins** | `66012929` |
| **6 · Cierre** | Regeneración, línea `ingest`, esta tabla | esta tanda |

La 5 se cerró antes que la 4 porque el usuario reordenó: la 4 tocaba plugins de terceros y quedó
esperando su decisión.

**Lo que la Fase 0b deja pendiente**, para que no se pierda al marcarla hecha:

| Pendiente | Por qué quedó fuera |
|---|---|
| Plugins de comunidad (Dataview, Tasks, Kanban, Excalidraw, Iconize, Homepage, tema Minimal) | Decisión del usuario: los decide aparte. Con ellos quedan fuera el Kanban de esta cola y el arranque automático del dashboard |
| Grupos de color del grafo (`.obsidian/graph.json`) | No hay forma de comprobar que la consulta hace lo que dice sin abrir Obsidian, y el criterio de la tanda fue que sin verificación no se escribe |
| Enchufar `--estricto` a `npm run test:wiki` | Es decisión de contrato: a partir de ahí toda fuente nueva nace con frontmatter o el gate se pone rojo. **Ya se midió el hueco**: una fuente entró sin declarar por un merge y el gate no lo detuvo |
| 3 archivos del design system sin frontmatter | Están congelados por sha256 en `goal-provenance.json`. Ratificado por el usuario |
| 8 `goal.md` que son andamiajes sin objetivo escrito | Salen ahora en el catálogo con un resumen que lo dice. Hay que decidir cuáles siguen vivos |

## Bloque 1 — Programa Design System (cuatro fases)

Decisión del usuario del 2026-08-18, en [[programa-design-system-en-cuatro-fases]]: «el design
system no está bien definido, ni bien implementado, ni bien controlado». **Es el programa que
manda sobre los gates.**

| Fase | Qué es | Estado |
|---|---|---|
| **DS-F0 · Auditoría total** | Toda la app: módulo, objeto, variable y escenario. Absorbe como semilla las 48 decisiones del 3-ago y F-4…F-9 de `docs/DESIGN-AUDIT.md`. Entregable: inventario por severidad «Crítico → Sin problema», verificando de paso el bug de coloreado que el usuario sospecha | No empezada |
| **DS-F1 · Redefinición del contrato** | Tokens, primitivas `aia-*`, escalas de estado/severidad y escala de stacking (z-index). Arranca con brainstorming con el usuario: el contrato es decisión de negocio | No empezada |
| **DS-F2 · Reimplementación por adaptadores** | Primero Handsontable y DataTables, que concentran la deuda; luego módulo a módulo según DS-F0. **Entrada añadida el 2026-08-20:** CNP/CNC/CIC (`legacyCards.js` entero es legacy bajo shell `aia-*`, hallazgo F0-022 sin dueño hasta hoy) | No empezada |
| **DS-F3 · Control** | Gates nuevos derivados del contrato. **Los 15 actuales se reemplazan, no se arreglan.** Cinco principios: pocos y atados a contratos que duelan; nunca bloquean el flujo local, solo el merge; actualizar un baseline cuesta un comando con diff visible; todo rojo dice qué archivo y qué hacer; cuarentena explícita para gates ruidosos | No empezada |

Consecuencia de secuencia ya decidida: **la Torre de Control BI no se recaptura**, se reconstruye
con enfoque data storytelling sobre el contrato de DS-F1; hacerlo antes sería construirla dos veces.

## Bloque 2 — Cierre hasta producción (cinco fases)

`docs/superpowers/plans/2026-08-11-cierre-hasta-produccion.md`.

| Fase | Frente | Estado |
|---|---|---|
| **CP-F0 · Poner el CI en verde** | `ci-en-verde` | Añadida el 2026-08-12 delante de todo (`6d82f723`), porque `design-system-runtime` lleva `needs: design-system-static` y el static llevaba rojo desde el 2026-07-17 |
| **CP-F-AB · Cablear los dos gates al CI** | `gates-al-ci` | **PAUSADO.** Sus dos decisiones ya confirmadas por el usuario (añadir `test.C` a `DEV_DOOR_USERS` en `docker-compose.ci.yml`, y el baseline 0.3.4), sin ejecutar |
| **CP-F-C · Cada módulo declara dónde pinta sus estados** | `superficie-de-estados` | Pendiente. Decisión del usuario: opción (a), obligatoria |
| **CP-F-D** | — | **RETIRADA** el 2026-08-12: su premisa estaba caducada, ya estaba hecha |
| **CP-F-E · Despliegue a producción** | `despliegue` | Pendiente. ~1.255 commits de retraso. **Necesita autorización propia y explícita, siempre** |

## Bloque 3 — Móvil, tablet y tema claro (siete fases)

`goals/reapertura-movil-y-tema-claro/goal.md`.

| Fase | Qué es | Estado |
|---|---|---|
| **MO-F1 · Destrabar** | `390x844` vuelve a ser soportado y no requerido | **CERRADA** (2026-08-07, DS-032) |
| **MO-F2a-1 · Precondiciones** | El gate valida los 15 manifiestos (miraba 4) y ata cada golden a su tema, viewport y contenido | **CERRADA** (2026-08-07) |
| **MO-F2a-2a · Deudas de arranque** | El golden mide exactamente su viewport salvo recorte declarado; los 17 manifiestos en `1.1.0` | **CERRADA** (2026-08-07, DS-033) |
| **MO-F2a-2b · Piloto móvil** | Handsontable deja de instanciarse bajo el umbral (0 nodos en 390×844); el sidebar pasa a menú flotante — era la causa raíz de que móvil fuera inusable: se comía 240 de 390 px y nunca colapsaba | **CERRADA** (2026-08-14) |
| **MO-F2b · Resto de módulos** | Los 13 restantes, con el coste ya medido en el piloto | Pendiente |
| **MO-F3 · Tema claro** | Paleta clara nueva y conmutador con preferencia guardada. Ojo: `linen` se retiró del producto el 2026-07-25 (DS-030), así que es **reconstruir, no reactivar**: paleta nueva, conmutador con preferencia guardada y revalidar todas las superficies | **Pendiente — arranca al cerrar MO-F2b.** Orden de Felipe (2026-08-20), revisando D-9: no queda estacionada, va **justo detrás de móvil**. Sigue **sin bloquear a `bi-control-tower-gemini`**, que cierra en dark por decisión propia (D-7) |
| **MO-F4 · Matriz diagonal** | Los gates adoptan la matriz de D6 y los candados se reinstalan | Pendiente — **absorbida por DS-F3**, ver «El solape de los gates» |

## El solape de los gates, y cómo se resuelve

Tres bloques empujaban la misma pieza: **DS-F3** dice que los 15 gates se reemplazan, **CP-F-AB**
está cableando dos de esos mismos gates, y **MO-F4** quiere cambiarles la matriz.

**Resolución (2026-08-18): manda DS-F3.** Los otros dos se subordinan:

- **MO-F4 se retira como fase propia** y entra como requisito dentro de DS-F3: la matriz de D6 es
  una entrada del contrato nuevo, no un trabajo aparte sobre los gates viejos.
- **CP-F-AB se recorta a lo mínimo que desbloquea el CI** y no se amplía. Cablear dos gates que
  DS-F3 va a reemplazar solo se justifica porque sin CI verde no hay forma de medir nada de DS-F0.
  Es andamio declarado, no inversión.

## Frentes en espera (no arrancan hasta cerrar el bloque 0)

- [[goals/gates-al-ci/goal|gates-al-ci]] — CP-F-AB recortado: `test.C` en CI + baseline, re-medir 8/8, publicar.
- [[goals/contadores-cero/goal|contadores-cero]] — visto concedido; localizar rama, re-verificar, publicar.
- **Plan espacio SiteGround** — tareas 1–5 de `docs/superpowers/plans/2026-08-18-espacio-cuenta-siteground.md`.
- **Dropdown PS sobre selector de semana** — diagnóstico (`systematic-debugging`) del stacking en `/programacion-semanal`.
- **Higiene de coordinación** — sesiones zombi, `cas-log.*` de la raíz, triaje de goals.

## Habilitación en una columna (en curso, sesión propia)

Plan `docs/superpowers/plans/2026-08-21-habilitacion-en-una-columna.md` (once tareas), desde la spec
v2 aprobada el 2026-08-21. Lanzado en sesión propia el 2026-08-21. Cubre los dos pendientes que
quedaron vivos del frente de replanteo de coloreado:

- **Desborde de Programación Intermedia** — 17 columnas piden 1490 px en 1100. Lo cierra la Task 5,
  con un guardián que falla solo si alguien vuelve a ensanchar.
- **Contadores de leyenda del color equivocado** — consumen `--ds-state-tint-*` mientras los chips
  usan `--ds-state-solid-*`. Lo cierra la Task 1, que es independiente del resto.

Pendiente propio derivado: **Programación Semanal hereda la pieza en la ola siguiente**, con
Intermedia ya rodado una semana en obra. Comparte las mismas cinco restricciones duras
(`programacion_semanal/hot.js:570`), así que dejarla distinta indefinidamente reintroduce el
problema que el frente vino a corregir.

## Replanteo antes de ejecutar

- [[goals/vocabulario-estados-cascada/goal|vocabulario-estados-cascada]] — el usuario pidió
  replantear D-VOC-1; su aclaración clave está en
  [[programa-general-actualizar-es-otra-herramienta]]. D-VOC-4 exige análisis profundo. D-1 de
  `contrato-estados-modulo-fantasma` se ajusta al censo que salga del replanteo, en un solo
  movimiento.

## Apuestas planificadas (tras lo anterior)

Torre de Control reconstruida con data storytelling (tras DS-F1 y el tema claro, que vuelve a la
secuencia detrás de móvil) · semana fija en
el resto de módulos con corte semanal · extensión de contadores-cero a todos los módulos · backlog
del 3-ago (48 decisiones; accesibilidad primero, absorbido por DS-F0).
