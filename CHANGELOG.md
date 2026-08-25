---
capa: fuente
tipo: reporte
estado: vigente
fecha: 2026-03-09
areas: [proceso]
tags: [proyecto, generado]
fuente: CHANGELOG.md
resumen: Todos los cambios notables en este proyecto serán documentados en este archivo.
project: lps-aia
type: changelog
status: activo
updated: 2026-08-25
---

# Registro de Cambios (Changelog)

Todos los cambios notables en este proyecto serán documentados en este archivo.

El formato se basa en [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
y este proyecto se adhiere a [Semantic Versioning](https://semver.org/spec/v2.0.0/).

Nota de bootstrap 2026-08-19: `[Sin publicar]` estaba archivado entre `[1.1.0]` y `[1.2.0]` — se
movió aquí arriba (posición correcta de Unreleased) sin tocar su contenido. Desde esa fecha, el
detalle día a día de sesiones/frentes vive en `docs/superpowers/reports/` y `decisiones/`; este
archivo registra solo cambios de producto liberados o por liberar. Ver [[IMPLEMENTATION_PLAN_INVENTORY]]
para el estado de los planes en curso.

## [Sin publicar]

### Depuración de la documentación: el tipado de `docs/` decía lo que no era (2026-08-25)

Encargo de Felipe: dejar la wiki y `docs/` sin ruido ni documentos obsoletos, reflejando la
realidad de hoy. El hallazgo que ordena el resto: **el frontmatter de `docs/` lo puso un backfill
que dedujo el tipo desde la ruta**, y `guia` acabó siendo su valor por defecto. Había **90
documentos declarados `guia`** —el tipo que *manda*, y que cuenta como autoridad en la alarma de
veracidad— cuando la mitad eran reportes, planes o la biblia de dominio. Un reporte mal tipado se
lee como si ordenara.

#### Changed
- **72 documentos reclasificados** por lo que de verdad son: 38 a `reporte` (auditorías DS-F0,
  `DESIGN-AUDIT`, `PDC-AUDIT`, `MIGRATION_FINDINGS`, los cinco barridos del 2026-08-03…), 11 a
  `plan`, 22 a `biblia` (los doce `docs/flujos/`, `PRODUCT`, `CUSTOMER`, los diccionarios de RBAC y
  estados) y `docs/DESIGN.md` a `contrato`. **De 90 «guías» a 18**, y las 18 sí explican cómo se
  hace algo.
- **13 specs y planes sellados como `cerrado`**, aplicando por primera vez a `docs/superpowers/` la
  regla que el repo ya usa para los goals: cerrado es tener `## Cierre` con contenido, no casillas
  marcadas ni antigüedad. Los ~120 restantes se dejan como están a propósito: `vigente` en ellos no
  afirma nada, y marcarlos `abierto` sí afirmaría algo sin verificar.

#### Fixed
- **`docs/pdc-v2.md` mandaba a un disco muerto y contradecía al `docker-compose.override.yml`.** Es
  `tipo: guia` y `CLAUDE.md:304` obliga a leerlo antes de tocar el PDC. Decía tres cosas falsas:
  (1) trabajar en `/Volumes/Crucial X6/…/lps-aia-pdc`, ruta caducada con la mudanza del 2026-08-18;
  (2) que el override monta `./` relativo «así que docker compose desde el worktree monta el
  worktree» —`docker-compose.override.yml:27` monta `${LPS_CODE_ROOT:-/Users/felipebenitez/Developer/lps-aia}`,
  **ruta absoluta a la raíz**—; y (3) que no hay PHPUnit, falso desde el 2026-08-11. La segunda es
  la cara: es la trampa de medir en un árbol y concluir sobre otro, la que produjo tres verdes
  falsos el 2026-08-18. Sección reescrita entera conservando el porqué de la decisión.
- **`CLAUDE.md` daba «103 loose scripts, 1 PHPUnit class»**; medido hoy, son **117 y 5**.

#### Added
- `docs/qa/evidence/ARCHIVO.md` sellado como **historia, no estado actual**: casi todo es de julio
  y documenta el PDC v1, eliminado el 2026-08-04. Se deja constancia de por qué **no se borra** —
  cuatro pruebas PHP dependen de `catalog-goal-audit-20260702/`, el peso ya está en la historia de
  git y el precedente del repo es sacar a disco con `sha256`— y de que el respaldo externo sigue
  **completo: 282 MB y 46 archivos**, verificado. Matiz que la documentación confundía: lo que se
  mudó el 2026-08-18 fue el repositorio, no el disco `Crucial X6`, que sigue montado.

**Verificado:** `npm run test:wiki` 98/98 sin hallazgos (165 páginas, 501/504 fuentes en estricto);
`npm run test:design-system:static` **8/8**. Los cuatro tests PHP que citan archivos tocados solo
los nombran en comentarios —comprobado con `grep`—, así que no se movió el contenedor compartido,
que otra sesión está usando.

### El CI vuelve a completar una corrida entera (2026-08-24, Plan P2)

Cuatro causas encadenadas, cada una escondida detrás de la anterior — el mismo patrón que ya había
mordido tres veces esa jornada. Condición de hecho del plan cumplida: corrida de Actions
[32787664690](https://github.com/jbenite7/lastplanneraia-construccion/actions/runs/32787664690)
con los 9 gates de `closeout-evidence.json` en `passed`, sin ninguno `blocked`.

#### Fixed
- El golden **Linux** de `programa-general.visual.mjs` estaba congelado en el 2026-08-12 mientras el
  golden macOS del mismo test se recapturó tres veces después con cambios reales aprobados por
  Felipe. La hipótesis de no-determinismo de fuente que se había anotado antes era falsa: el estado
  **Fuera de Ventana** entró al vocabulario el 2026-08-19 y su chip nuevo partía la leyenda en dos
  filas, empujando toda la tabla. Recapturado con evidencia real de una corrida de Actions, no una
  captura local.
- `tests/browser/programa-general-state-hue.mjs` no tenía ninguna fila con `Estado: 'Fuera de
  Ventana'`, así que no podía comprobar que el matiz `teal` se pinte distinto — el mismo vocabulario
  nuevo del punto anterior, otro fixture sin propagar.
- Los gates de `design-system-runtime` corrían en serie sin `continue-on-error`: el primero en
  romperse cancelaba todos los posteriores. Ahora cada gate corre siempre y un paso final
  («Summarize gate results») decide si el job queda en rojo mirando todos los resultados juntos, y
  vuelca la tabla a `GITHUB_STEP_SUMMARY` junto con la duración y las métricas de presupuesto que ya
  se generaban.
- El gate `runtime-budgets` no podía pasar de `blocked` a `passed` sin editar su evidencia a mano:
  el `check` no dejaba recibo. Envuelto en `gate-receipt.mjs`, igual que `full-app-flow` y
  `semanal-roles-phases`.
- Dos hallazgos de `zizmor` (auditoría de seguridad del YAML, nueva en este repo): los checkout no
  fijaban `persist-credentials: false`.

#### Changed
- El workflow ya no dispara por cambios en `memoria/**` o en los `.md` de la raíz del repo —
  ningún gate los lee. `docs/design-system/` queda fuera de la exclusión a propósito: es
  contractual.

### El runner de pruebas deja de juzgar en un idioma y reportar en otro (2026-08-24)

#### Fixed
- `scripts/run-php-tests.php` marcaba SOSPECHOSO («salió 0 sin comprobar nada») a tests que sí
  comprueban, solo porque anuncian su verde con `PASA:` en español mientras el detector buscaba
  `pass` en inglés. Tuvo `main` en rojo casi una hora por `test_causa_atribucion.php`, cuyo mensaje
  de hallazgo además mandaba al sitio equivocado —«dale aserciones»— cuando las aserciones ya
  estaban puestas. Otros **seis** tests de la suite dicen `PASA` y se salvaban de rebote, por
  imprimir alguna otra línea con una señal reconocida: la trampa seguía armada para el siguiente.
- Se añade `pasa:` a las señales, **con los dos puntos**: a secas también casaría dentro de «no
  pasa» y un test que saliera 0 anunciando su propio fallo quedaría verde. Con los dos puntos cubre
  los siete casos reales sin inventar ninguno. Cubierto por `tests/fixtures/runner/espanol/` y tres
  comprobaciones nuevas en `test_php_test_runner.php`.

### El guard de laboratorio se alinea con el replanteo B (2026-08-24)

#### Fixed
- `tests/browser/design-system-lab.mjs` exigía que un estado crítico con matiz conservara el fondo
  crítico. Esa excepción se retiró del CSS el 2026-08-20 con el replanteo B (`b7d5dd18`): el chip
  pinta sólido por familia y la gravedad vive completa en el filete. El test se había quedado en su
  versión del 2026-08-11 y llevaba **cuatro días en rojo sin que se viera**, porque era el paso 24
  del job y el check de presupuesto, en el 23, lo dejaba `skipped` — el mismo patrón que había
  tapado el fallo de `general_flags`. Lo destapó la regeneración de la baseline a 0.4.0.
- Al caer la excepción, el nivel crítico deja de ser un caso aparte y entra en la regla general del
  eje de matiz: el test **comprueba más que antes**, porque ahora también exige que dos estados
  críticos de matiz distinto no compartan fondo.

### El presupuesto de runtime pasa a la generación 0.4.0 y se mide donde el gate lo verifica (2026-08-24)

#### Changed
- `test:runtime-budget:check` apunta a `runtime-baseline-0.4.0.json`. La generación anterior
  (0.3.5) se había medido en la máquina local mientras el gate corre en runners de GitHub Actions,
  y `initializationMs` agrupa por máquina antes que por código: 191-268 ms en local contra
  596-1.071 en Actions. Un baseline tomado en la máquina rápida y verificado en la lenta produce
  rojos que no corresponden a ningún cambio. Desde 0.4.0 la referencia se toma en CI.
- La tolerancia de `handsontableInteractionMs` sube de 45 a 60 ms: con 45 el techo caía a 11 ms del
  máximo ya observado del mismo código, es decir dentro del ruido medido.

#### Fixed
- El gate estaba en rojo por `jsGzipBytes` (+9.548 B) e `initializationMs`. El JS queda atribuido
  al byte y sin residuo —`hot.js` +4.990 y `state-tooltip.js` +2.304 del frente de coloreado,
  `tablet-viewport-scale.js` +727, `view-switch.js` +395— más 1.132 B que **no son código**: 42
  archivos de sha256 idéntico comprimidos por versiones distintas de zlib. El total servido baja
  59 KB gzip, porque el CSS cae 68.467 B. Sin regresión atribuible al código; los límites de esa
  afirmación están escritos en el informe de atribución.
### Cierre de pendientes del frente de tablas (2026-08-24)

#### Fixed
- Tipografía: `handsontable-module.css` usaba `monospace` literal en vez de `--ds-font-mono`. Se
  creó el token `--ds-font-icon` en `tokens.css` y se aplicó en los dos sitios que llamaban a
  «Font Awesome 5 Free» a mano (`handsontable-header-global.css`,
  `design-system/components/table-filter-trigger.css`). Gate estático 8/8. Commit `3ce994fc`.
- Color de BI: `ControlTowerService.php` (`semanticMetricRange`, `schedulePerformanceRange`) y el
  fallback de `bi-spa.js` dejaron de usar `status-*` — la mitad `-text` de un par de estado,
  pensada para tinta, no para relleno — como color de las dos donas de progreso. Ahora resuelven a
  colores de dato: crítico → `critical`, alerta → `brand-construction`, bien → `brand-primary`.
  PHPStan sin errores; 51 tests PHPUnit OK. Commit `880e9d4a`.

#### Changed
- CI: de cuatro listas de SQL que fingían ser la misma quedaron tres con roles distintos: la lista
  blanca de `scripts/design-system-ci-preflight.mjs` (guardarraíl), su derivada en
  `ci-preflight.test.mjs`, y la de `visual-ci-contract.test.mjs` conservada duplicada a propósito
  como segundo testigo. Verificado ensuciando el Dockerfile: los dos archivos fallan. Commit
  `a9366f3b`.

#### Investigated
- A11y: el gemelo callado del filtro de cabecera de Programa General (12 de 24 botones sin
  `aria-hidden`) no se reprodujo. Medido 24/24 marcados en las dos mitades, sostenido tras
  `render()`, `updateSettings()`, `loadData()`, resize y recarga. Se actualizó solo el comentario,
  sin arreglo de código. Hipótesis que queda: la medición original se tomó contra un contenedor
  que montaba otro árbol. Commit `ee875efb`.
### Cierre del plan del sidebar canónico, 35 días después de ejecutarse (2026-08-24)

#### Changed
- `docs/superpowers/plans/2026-07-20-sidebar-canonico-laboratorio.md` recibe su sección `## Cierre`.
  El trabajo estaba hecho desde julio y en producción —aprobado por Felipe el 2026-07-22, migrado en
  `goals/sidebar-todos-modulos` el 2026-07-31, hoy en 16 vistas—; lo que faltaba era el acta, y sin
  ella la regla de lectura lo contaba como abierto. No se ejecutó nada: ejecutarlo habría sido
  construir en el laboratorio el prototipo de algo que la app ya usa.

### Cierre de P1: pase de veracidad y la fecha contractual del informe (2026-08-24)

#### Fixed
- `tests/test_bi_programa_general_chart_values.php` pasa de **15 `FAIL` a 0**; llevaba rojo desde el
  2026-08-14. Afirmaba `contractual_finish_basis === 'first_available_snapshot_per_project'` cuando
  la spec de `linea-base-contractual` derogó ese contrato: la fecha sale de la línea base declarada
  del proyecto. `BiContractFixture` pasa a registrar sus proyectos y declararles la línea base
  derivándola con el mismo `UPDATE` de la migración, no con una fecha literal.
- Docstring de `BiPreviewAccessPolicy.php`: decía «solo las abre Admin» tras dos ampliaciones de rol.

#### Changed
- Noveno pase de veracidad de la wiki sobre `a4f19884`: 17 hallazgos, 15 páginas corregidas. El de
  más fondo estaba en cinco páginas — `closeout-evidence.json` declara **nueve** gates desde el
  2026-08-14 y todas decían ocho.

### Consolidación de trece ramas en main y retiro de worktrees (2026-08-24)

#### Changed
- Por orden de Felipe se integraron en `main` las **trece** ramas con trabajo fuera de él
  (`aa6f0b74..6c736d91`) y se retiraron todas las ramas y worktrees salvo la sesión viva. En el
  remoto quedan `main` y las tres ramas de dependabot, que no son de sesiones y no se tocaron.
- El runner de pruebas PHP sale del rojo en `main`: 29 corridos, 29 pasaron, **0 sospechosos**.

#### Fixed
- Las migraciones `general_flags` y `sembrar_linea_base_contractual` reclamaban ambas el slot `121`
  del fixture de CI; la segunda pasa a `122` y las dos quedan declaradas en la lista blanca.
- Revertida la edición en sitio del baseline `0.3.3`, que está anclado por hash: era el intento de
  D-GAC-6 ya fallido el 2026-08-12, cuyo sucesor legítimo son `0.3.4` y `0.4.0`.
- Un solo `@import` del adaptador de DataTables, el versionado.
- `memoria/log.md`: retirada una redacción rival del octavo pase de veracidad.

### Consolidación del estado del repositorio y reparto en seis planes (2026-08-24)

#### Added
- `docs/superpowers/specs/2026-08-24-estado-consolidado-del-repo-design.md` — spec única con el
  censo medido de las 12 sesiones simultáneas (solo 4 eran de este repositorio), el estado de las
  ramas contra `origin/main` y el reparto de todo el pendiente vivo.
- Seis planes de ejecución con dependencias declaradas: `2026-08-24-p1-desague-y-consolidacion`,
  `p2-ci-en-verde-y-presupuestos`, `p3-design-system-contrato-y-control`, `p4-movil-y-tema-claro`,
  `p5-cierre-hasta-produccion` y `p6-higiene-documental-y-coordinacion`.

#### Changed
- `TASKS.md` deja de declarar «sin bloqueantes»: el gate de cierre de frente está activo con tres
  frentes terminados sin publicar (+8, +9 y +11 commits en tres ramas).

### Habilitación en una columna — Programación Intermedia (2026-08-24)

#### Changed
- Las siete columnas de restricción de `/programacion-intermedia` (Diseños y Especificaciones,
  Procedimiento Constructivo, Modelación BIM, Materiales, Mano de Obra, Equipos y Herramienta,
  Actividad Predecesora) se funden en una sola columna «Habilitación» de cuadritos (relleno + visto
  + tachado para N/A, sin depender solo del color), con hasta 7 visibles y «+N» para el resto. La
  tabla pasa de pedir 1490 px a caber en 1100 sin scroll horizontal, con guardián automático
  (`tests/browser/pi-ancho-presupuesto.mjs`).
- Liberar una restricción se hace desde un globo (Popover API) anclado a la fila, con el mismo
  selector, guardado y validación de hoy — nada nuevo del lado del servidor. El globo se abre por
  clic o teclado, se navega entre actividades con flechas sin cerrarlo, se cierra con Escape o clic
  afuera, y devuelve el foco a la celda. Sin permiso de edición, se ve todo con los selectores
  desactivados y el motivo explicado.
- El `% Liberación` (antes columna `Estado_Restricciones`) se muda al globo. El filtro por
  restricción, que vivía en cada columna, ahora es un menú propio en la cabecera de Habilitación.
- La tarjeta móvil de Programación Intermedia comparte la misma pieza visual del globo (mismos
  cuadritos, mismo orden, mismo selector).
- Los contadores de la leyenda de Programa General e Intermedia toman el color sólido de su estado
  (antes tinte apagado, no se parecían al chip de su fila).
  Spec: [[docs/superpowers/specs/2026-08-20-habilitacion-en-una-columna-design]] · plan:
  [[docs/superpowers/plans/2026-08-21-habilitacion-en-una-columna]].

#### Fixed
- El recorrido del globo por teclado (ArrowUp/ArrowDown) queda cubierto por Playwright, no solo
  verificado a mano.
- Se repone el tooltip «?» educativo de las siete restricciones en la cabecera de Habilitación —se
  había perdido al fundir las columnas de restricción en una sola— como un solo trigger con las
  siete concatenadas, sin volver al mapa índice→columna que causó un hallazgo de la revisión final.
- El chip «Con Alerta Restricciones» de Programa General toma el ámbar sólido, igual que el resto
  de la leyenda; se mantiene fuera del contrato de estados a propósito, porque es una insignia que
  puede coexistir con cualquier fila y no un `Estado_PG` propio.
- `construirCuadrito` (el cuadrito de habilitación) queda en un solo módulo compartido
  (`readiness-box.js`), consumido tanto por la columna de la tabla como por el globo y la tarjeta
  móvil — dejó de estar duplicado entre un script clásico y un módulo ES.

### CI: la imagen de pruebas siembra `general_flags` (2026-08-24)

#### Fixed
- `main` llevaba en rojo desde el 2026-08-21 con «Table `lastplanneraia_ci.general_flags`
  doesn't exist». El interruptor del Control Tower trajo su migración, su servicio y sus dos
  suites, pero la imagen de base de CI nunca sembró la tabla. El paso de la suite PHP moría ahí
  —74 de 76— y con él **todo lo que venía después: el piloto visual, la accesibilidad y el matiz
  del piloto llevaban tres días sin correr en CI.** Se añade la migración a
  `database/fixtures/design-system-ci.Dockerfile`, siguiendo el criterio que ese archivo ya
  documenta: se aplica la migración, no un `CREATE TABLE` a mano, para que cada build compruebe
  de paso que la migración hace lo que dice.
- **Fuera del alcance de este frente**, y se hace igual porque bloqueaba su cierre y el de
  cualquiera: mientras ese paso muera, ningún gate de navegador corre en CI para nadie.

### La columna Acciones de Programación Semanal vuelve a verse (2026-08-24)

#### Fixed
- La celda de Acciones se quedaba con el **blanco opaco del vendor**, así que la columna entera se
  veía como una franja clara pegada al borde derecho de una tabla oscura. Causa: `psActionsRenderer`
  se arma la lista de clases a mano en vez de heredar la que construye `cells()`, y se saltaba
  `ps-state-<estado>`, que es la que pinta el fondo de la fila. La regla del vendor le gana por
  especificidad (0,1,1 contra el 0,0,0 del `:where(...)` del adaptador). Ahora la celda toma el
  color de su propia fila; verificado con scroll, que recicla las celdas: 0 blancas y 0 estados
  duplicados en seis posiciones distintas.
- El chip de los botones «duplicar» y «eliminar» usaba la mitad `-text` de un par de estado
  **como fondo**, con blanco encima. En tema claro funcionaba; desde que la paleta de estado se
  invirtió a oscuro (spec del 2026-07-28) esa mitad resuelve a un tono pálido, así que quedaba
  blanco sobre claro: **1,37:1 y 1,42:1** contra el piso de 3:1 de WCAG 2.2 SC 1.4.11. El glifo se
  dibujaba —9,3 px dentro de un chip de 32— pero era invisible, y los botones se leían como
  cuadros vacíos. Restituido el par que la spec ya había medido: **8,88:1** y **10,99:1**, sin
  tocar geometría (chip 32×32 dentro de un destino táctil de 44×44).
- Lo reportó el usuario mirando una captura, no un gate: no estaba en el baseline de axe, no había
  excepción de accesibilidad que lo cubriera, y los tres hallazgos del ledger sobre esos mismos
  botones (C-18, C-48, F-3/F-4) miran otra cosa. El porqué y la receta quedaron anotados en la
  propia spec de la paleta, §«Lo que se rompió después», junto al hermano sin auditar de
  `bi-spa.js:3704`.

### Tablas: cifras alineadas, filtros alcanzables por teclado e identidad de fila (2026-08-24)

#### Added
- Cifras tabulares en las rejillas Handsontable (`font-variant-numeric: tabular-nums` en la celda
  y en el editor). Inter sirve figuras proporcionales por defecto: medido sobre el subconjunto que
  servimos, el «1» avanza 833 y el «4» 1323 sobre 2048, nueve anchos para diez dígitos. Verificado
  en `/programa-general`: la columna de Id pasa de 9,62 px de desviación a 0, y las fechas de 8,28
  a 1,15. Sin desbordes: los anchos de columna se calculan por proporción del contenedor.
- `navigableHeaders: true` en las seis rejillas Handsontable. Viene apagado por defecto desde la
  14.0, y con la opción apagada NO hay camino de teclado hasta el embudo de la cabecera —
  Handsontable lo emite con `tabindex="-1"` y `aria-hidden="true"` a propósito. Antes de esto,
  quien no usaba ratón no podía filtrar ninguna tabla de la aplicación.
- `getRowId` en las tres rejillas de Plan de Compras que no lo tenían (paquetes de contratación,
  plan de fechas y seguimiento). Sin él AG Grid identifica las filas por posición y pierde
  selección y estado de edición en cada recarga.

#### Changed
- `FilaPlan` y `FilaSeguimiento` declaran `subpaqueteId`. El servidor ya lo emitía
  (`PlanFechasService`, `SeguimientoService`); el tipo no lo declaraba, y sin él la pareja
  paquete-lote no podía usarse como identidad de fila en un paquete partido.

### Interruptor del Control Tower desde /admin (2026-08-20)

#### Added
- Tabla `general_flags` (clave-valor global con auditoría del último cambio) y
  `App\Core\FlagsService`, lectura con fail-safe: un flag ilegible se comporta como apagado.
- Pantalla `/admin/modulos` (solo rol A, con CSRF y lista blanca de claves) para prender y apagar
  el Control Tower sin deploy.

#### Changed
- `BiPreviewAccessPolicy` obedece el flag `bi.control_tower.visible` para los roles no-Admin con
  `internal.bi.preview`. El Admin entra siempre, esté el interruptor como esté.
  Spec: [[docs/superpowers/specs/2026-08-20-interruptor-control-tower-admin-design]].


### Control Tower abierto al Director de Obra (2026-08-20)

#### Changed
- La capacidad `internal.bi.preview` pasa de solo `A` a `A` y `D`: el Director de Obra ya ve los
  accesos y abre `/bi/*` mientras el módulo sigue oculto para el resto de roles. Decisión de
  Felipe 2026-08-20; desplegado a pruebas y producción el mismo día (`828483a4`).


### Deuda del CI — Frente 2 (2026-08-20)

#### Changed
- CI: la imagen PHP se pre-construye con buildx y cache `type=gha`; compose ya no la
  reconstruye. Medido en caliente: 81 s → 20 s (estático, −75 %) y 93 s → 72 s (runtime,
  −23 %). Alcance A del spec [[docs/superpowers/specs/2026-08-20-deuda-ci-design]] — el
  Dockerfile no se tocó.

### Deuda del CI — Frente 1 (2026-08-20)

#### Security
- CI: las actions quedan ancladas por SHA de commit (con Dependabot vigilándolas por PR semanal),
  ambos jobs con `timeout-minutes`, y el propio workflow pasa por actionlint con binario fijado
  por checksum. El contrato visual-ci ahora exige la forma pineada. Origen:
  [[docs/superpowers/specs/2026-08-20-deuda-ci-design]], Frente 1.

### Wiki v2 — esquema, lint y migración de la wiki (2026-08-19)

#### Added
- Esquema v2 de la wiki: campo `capa`, diecisiete `tipo`, ocho `tags` de vocabulario cerrado, y
  frontmatter en las 431 fuentes del vault. Detalle en [[docs/wiki-operacion]].
- `scripts/wiki-frontmatter.mjs` — backfill idempotente del frontmatter, en ensayo por defecto.
- `scripts/wiki-esquema.mjs` — el vocabulario cerrado en un solo sitio.
- `scripts/wiki-vistas.mjs` — censa qué lista cada vista de `paginas.base` y falla si alguna cambia.
  Existe porque el lint en verde **no** prueba que una página siga en el catálogo.
- 13 mapas de área (antes 7), 13 vistas Bases por área, 3 canvas y un dashboard en la portada.
- `npm run test:wiki:forma` — la mitad de la comprobación que un gate puede bloquear.

#### Changed
- **`scripts/publicar.sh` bloquea por la forma de la wiki** y solo avisa por la alarma de
  veracidad. Antes toda la wiki avisaba, y por eso tres veces entró una fuente sin declarar sin
  que la publicación se detuviera.
- `npm run test:wiki` corre en modo estricto.
- La alarma de veracidad deja de contar tres cosas que no son deriva de código: commits de solo
  frontmatter, merges que solo unen, y commits que solo tocan documentos de intención
  (`spec`, `plan`, `reporte`, `evidencia`, `goal-doc`).
- Los cinco archivos de la wiki del proyecto viven en la raíz: `memoria/goals/cola-de-pendientes.md`
  pasó a [[TASKS]] y `memoria/registro-de-trabajo.md` a [[IMPLEMENTATION_PLAN_INVENTORY]].

#### Fixed
- `campo()` del lint devolvía **la línea siguiente** como valor de un campo vacío: `\s*` incluye el
  salto de línea. Defecto heredado del lint v1, invisible hasta que hubo campos vacíos.
- El lint daba por roto un enlace a un `.canvas` y un alias con barra escapada (`[[x\|Alias]]`),
  que es la forma correcta dentro de una tabla.

#### Removed
- El tag `moc`: `tipo: mapa` significa MOC, y un tag que repite el tipo no discrimina nada.

### Añadido

- **Wiki de proyecto al día y depurada (2026-08-19):** `memoria/goals/estado.md` releído contra
  `origin/main` —iba nueve días y treinta goals atrasado— y `memoria/goals/cola-de-pendientes.md`
  actualizado con DS-F0 cerrada y los cuatro frentes de estados. `TASKS.md` reescrito: la versión
  anterior se escribió desde un árbol 114 commits atrasado y daba por activos cinco frentes ya
  publicados. `cola-de-pendientes` y `TASKS.md` decían ambos ser «la fuente única»; ahora se
  reparten por pregunta —fases y su orden en la cola, pendientes en `TASKS.md`—.
- **`openspec/` y `backups/` salen del vault de Obsidian:** son artefactos ignorados por git
  (tooling vendorizado y volcados locales de BD) y metían 12 hallazgos falsos en el lint de la
  wiki. Excluidos en `.obsidian/app.json`, que es donde se define el universo que el lint recorre.
- **Arquitectura global-only documentada:** La BD activa queda formalizada como tablas globales con `project_id`; `{prefix}_*` queda limitado a respaldos, fuentes de migración o deuda aislada sin dependencia runtime.
- **Asistente semi-automático compartido:** Listado de Actividades, Contratos y PDC comparten `preview/apply/undo/feedback/metrics`, trazabilidad global y bandeja guiada por seguridad.
- **Trazabilidad semi-auto:** Nuevas tablas globales para corridas, sugerencias, decisiones, feedback y configuración por proyecto.
- **Servicio de Normalización de Capítulos PG:** Nuevo `ProgramaConsolidadoNormalizationService` que auto-corrige capítulos con `Ejecutado > 0` y `Estado !== 'Capítulo'`. Integrado en GeneralApiController, SemanalApiController, creación de semana y cierre semanal.
- **Guard Server-Side para Capítulos PG:** Rechazo con 403 de ediciones directas a filas de tipo capítulo (`Titulo=1`) en `GeneralApiController::update()`.
- **LPS Contextual Drawers**: Paneles laterales deslizables (Bottom-Sheet responsive) integrados en Programa General, Programación Intermedia y Programación Semanal para guías y ayudas operativas contextuales.
- **Matriz de severidad del Cajón Contextual LPS:** Nueva documentación operativa en `docs/matriz-severidad-cajon-contextual-lps.md` para normalizar severidades PG/PI/PS, restricciones duras/blandas, colores y comportamiento de sidebar.
- **Motor de Escalamiento Semanal Inteligente**: Sistema de notificaciones express mitigador de spam con "Modo Simulación" (al portapapeles y Liquid Glass), Weekly Digest consolidador por subcontratista, y filtro automático para restringir las notificaciones individuales a tareas de Ruta Crítica (P1).
- **Estética Liquid Glassmorphism**: Rediseño CSS modular con efectos traslúcidos Liquid Glass, animaciones fluidas spring y optimización mobile-first para resoluciones de obra.
- **Bypass de Caché Agresivo**: Implementación de cache-busters de scripts (`?v=hot35` y `?v=hot45`) en PI y PS para invalidar el almacenamiento del cliente y forzar la inicialización reactiva del drawer.
- **Unificación Cromática y Contraste Premium (Fase 3.1):** Reemplazo de colores en duro ad-hoc del disparador lateral fijo (Desktop) por las variables de marca oficiales `--aia-green-dark` (reposo) y `--aia-green` (hover). Corrección del contraste de headings del drawer y aprobación de la suite de pruebas del servidor en Docker.

### Cambiado

- **UX de automatización no técnica:** La UI oculta IDs, fuentes internas, payloads y nombres técnicos para usuarios normales; Admin conserva “Detalle técnico”.
- **PDC sin aplicación automática legacy:** El endpoint antiguo de aplicar PDC desde actividades deja de ejecutar cambios directos; el camino soportado es el asistente guiado con preview, selección y confirmación.
- **Confianza normalizada:** La escala visible y backend queda en `0-100`: `80-100` listo, `50-79` revisión y `<50` no recomendado.
- **Cajón Contextual LPS:** Centralización de severidad `normal`, `attention`, `critical`, `info` y `neutral` en `lps_drawer.js`, con `isCrisis` reservado solo para estados críticos.
- **Programación Semanal:** `Por Comprometer`, `Condiciones Pendientes` e incumplimientos no RC quedan como atención operativa sin escalamiento directivo.

### Corregido

- **Fix Color PG con Filtro + Scroll:** Se agregó hook `beforeRenderer` en `hot.js` de Programa General que intercepta cada celda ANTES del render del DOM y sobrescribe `cellProperties.className` con el valor correcto del estado. Esto reemplaza el enfoque anterior (`afterRender` + `applyPGCellDomClass`) que fallaba en virtual scrolling por race conditions con el ciclo interno de renderizado de Handsontable 14.6.1. También se agregó `afterLoadData` hook para invalidar el cache de clasificación al recargar datos. Test E2E `test-pg-color-fix.mjs` valida que filas filtradas mantienen clases de estado correctas tras scroll a 8000px.
- **Fix Edición Ejecutado Real en PG (Consecutivo_en_Programa=0):** Resuelto bug donde el valor `Ejecutado Real` de la actividad "INICIO DEL PROYECTO (ESTIMADO)" se revertía al refrescar. Causa raíz: `Consecutivo_en_Programa=0` era falsey en JS, cayendo al `Id=1` del capítulo PREOPERATIVOS. Se corrigió `hot.js` y `GeneralApiController::update()` para usar `Consecutivo_en_Programa` como identificador canónico.
- **Render estable de Actualización de Cronograma:** Corrección del mapeo de columnas en Handsontable usando `propToCol`, refresco explícito de dimensiones tras `loadData()` y uso de `semana_objetivo` para evitar que las peticiones al borrador cambien la semana base de sesión.
- **Actualización de Cronograma estable:** La vista `/programa-general-actualizar` separa la semana base de la semana objetivo del borrador para evitar alternancia vacío/con datos al refrescar y mantener las peticiones siempre sobre el cronograma actualizado correcto.
- **Plantilla de Listado de Actividades:** La descarga de `listadoActividades.csv` usa un endpoint MVC dedicado para no depender de reglas estáticas del servidor en `/archivosBase`.
- **Actualización de Cronograma desde Excel:** Corrección integral del flujo `/programa-general-actualizar`: la plantilla base vuelve a descargarse desde `/archivosBase`, el selector `Asociar con...` carga la semana activa correcta, los roles A/D pueden mapear sobre borradores aunque la semana esté cerrada y las fechas importadas desde Excel se normalizan a `yyyy-mm-dd` priorizando seriales numéricos.
- **Disparador lateral LPS:** Corrección visual del badge de atención/crisis para evitar recuadros o iconos desalineados en desktop y móvil.
- **Asimetría de asignación en "Aplicar Restricción Compartida" (PI):** Corregido bug donde el `SET Responsable_AIA` del SQL de `applySharedConstraints` se aplicaba a todas las filas del lote aunque el usuario no hubiera marcado "Aplicar asignaciones comunes". Ahora tanto `Sub_Contratista` como `Responsable_AIA` se rigen por la misma guarda `if ($applyAssignments && $valor !== '')`, evitando sobreescritura silenciosa de asignaciones.
- **Validación confusa de Responsable AIA en restricciones (PI):** Eliminada la regla dura que exigía Responsable AIA para aplicar restricciones en lote. Ahora el campo es opt-in: solo se solicita cuando el usuario activa explícitamente el checkbox de "Aplicar asignaciones comunes", y debe seleccionar Sub-Contratista o Responsable AIA; o desactiva el check.
- **Detección de conflictos de asignación en Preview (PI):** El preview del modal "Aplicar Restricción Compartida" calcula conflictos por actividad (Sub-Contratista o Responsable AIA distintos al objetivo) y los muestra como panel de badges, KPI en rojo y resaltado de filas. No bloquea la operación; sirve como guía visual antes de aplicar.
- **Confirmación contextual al aplicar lote PI (PI):** Al hacer click en "Aplicar en Lote" se despliega `AIA.Notice.confirm` con 4 variantes según el estado del Preview: (1) **sin alerta** cuando el check "Aplicar asignaciones comunes" está desactivado, ya que no puede haber sobreescritura de Sub-Contratista/Responsable AIA; (2) "Conflictos de asignación" cuando el Preview previo detectó diferencias, listando conteo por columna con el texto literal acordado; (3) "Aplicar restricción compartida" cuando el Preview fue limpio; (4) "Preview desactualizado" o "No se validaron conflictos" según si la configuración cambió desde el último Preview o nunca se ejecutó. Todos los textos están redactados a prueba de bobos: mencionan cuántas actividades se modificarán, qué columnas se sobreescribirán y ofrecen cancelar para ejecutar "Ver Conflictos" antes de continuar. El botón Aplicar **nunca se bloquea**; el usuario siempre decide.
- **Botón renombrado a "Ver Conflictos" (PI):** El botón antes llamado "Preview" en el modal "Aplicar Restricción Compartida" ahora se llama "Ver Conflictos" para comunicar con precisión que su función es detectar sobreescrituras de Sub-Contratista o Responsable AIA, no solo previsualizar restricciones.

## [2026-08-19] — Repaso de todos los specs pendientes

### Fixed
- **El CI llevaba 40 corridas sin pasar** (23 `failure`, 17 `cancelled`, ni una verde) por una sola
  aserción: `full-app-flow` exigía en móvil que el `body` reservara sitio para el carril, justo lo
  que la spec del menú flotante derogó el 2026-08-14. Comprobado antes de tocar el test que no
  escondía una regresión: a 390 px el carril está fuera de pantalla, no tapa contenido y no hay
  desbordamiento.
- El lint de wiki denegaba publicaciones por basura que git ignora (un residuo de Playwright en
  `test-results/`). Ahora le pregunta a git en vez de parsear `.gitignore`, que se saltaba las reglas
  negativas y podaba las 57 páginas de `goals/`.

### Changed
- Siete `goals` cerrados con verificación de hoy; de 13 abiertos quedan 6. Dos de ellos estaban
  cerrados y firmados **en prosa**, sin el encabezado `## Cierre` del que el mapa de estado deriva:
  dieciséis y ocho días contando como abiertos con el trabajo hecho.
- `DECISIONES_PENDIENTES.md` pasa de cinco a nueve decisiones, todas medidas.

## [2026-08-19] — Cola de estados, severidad y color

### Added
- `tests/design-system/state-key-consumption.test.mjs`: un estado declarado que nadie pinta ahora se
  pone rojo. Censo que lo motivó: 25 estados con `key` (los 25 consumidos) y 30 sin ella, en siete de
  los diez módulos. Deuda congelada en `docs/design-system/state-key-debt.json`.
- `tests/design-system/coverage-closure.test.mjs`: una pantalla que ningún manifiesto declara ya no
  pasa desapercibida. 32 pantallas reales, 3 sin manifiesto, y `foundation-shell` con 20 rutas y cero
  escenarios. Deuda en `docs/design-system/coverage-debt.json`.
- `DECISIONES_PENDIENTES.md`: las cinco decisiones de producto/diseño que la cola dejó abiertas, cada
  una con su medición y su recomendación.
- Programa General gana el chip de filtro «Fuera de Ventana», que no tenía pese a ser el 39,3 % de la
  tabla.

### Changed
- Programa General pasa al vocabulario vivo: entra `Fuera de Ventana` (25.778 filas, se pintaba igual
  que `Actividad Futura`) y sale `Con Alerta Restricciones` (cero filas; sigue vivo como filtro, que
  es lo que siempre fue: un realce por condición del dato, no un estado).
- `Terminada` y `Sin Datos` dejan de compartir fondo: declaraban matices distintos y ambos tokens
  resolvían a `var(--ds-active-surface)`.
- `rowClassMap` se deriva de `statePresentation` en vez de repetirlo. Había dos listas del mismo
  vocabulario y una se quedaba atrás.

### Fixed
- La leyenda de Programación Intermedia deja de tener una mancha crema sobre tema oscuro: el
  muestrario de «Restricciones blandas» reservaba `#fef3c7` para dos variables inexistentes, así que
  la reserva se aplicaba siempre. Luminancia 0,893 → 0,0263.
- La sonda de la fase Calificación de Semanal, que declaraba forzar la fase sin forzarla: comprobaba
  su propia sustitución de texto y no el efecto.

## [1.1.1] - 2026-03-31

### Añadido

- **Indicadores de Desviación (Delta) en PDC:** Nuevo motor de cálculo asíncrono en `PdcApiController` que determina el retraso o avance real de cada paquete de contratación frente a su línea base teórica.
- **Visualización de Estatus Premium (PDC):** Refactorización de los renderizadores de celda en `pdc.view.php` para inyectar badges informativos (`deltaHtml`) y tooltips de desviación directamente en los iconos de estado.
- **Refuerzo de Estilos PDC:** Nuevas utilidades CSS en `styles.css` para soportar la visualización de deltas con colores semánticos (peligro para retrasos, info para avances).

## [1.1.0] - 2026-03-31

### Añadido

- **IA Agile Operative OS 2026:** Integración completa de la "Constitución IA" en `GEMINI.md`, definiendo el Protocolo Sniper, Kill Switch y planificación por fases (SDD/PDCA) para una operación de ingeniería de alta precisión.
- **Apple-Style Design System (Fase 1):** Implementación de una arquitectura de diseño premium basada en CSS nativo y variables OKLCH/HSL en `styles.css` y `buttons.css`.
- **Modernización de Vista PDC:** Refactorización estética y funcional de `views/pdc/pdc.view.php` con tipografías premium (Montserrat/Inter) y componentes visuales alineados al manual de marca AIA.
- **Documentación de Flujo IA:** Incorporación de nuevos walkthroughs estratégicos para la operación del agente en entornos complejos.

### Cambiado

- **Refactor CSS Modular:** Migración de estilos hacia un motor de variables centralizado, facilitando la consistencia estética entre los módulos MVC y Legacy.
- **Actualización de Gobernanza:** Endurecimiento de las reglas de edición y validación del agente para garantizar la integridad del código fuente.

## [1.2.0] - 2026-05-21

### Añadido

- **Asignaciones opcionales en Restricción Compartida PI:** El lote de Programación Intermedia permite aplicar Sub-Contratista y Responsable AIA comunes mediante checks explícitos, manteniendo por defecto la actualización exclusiva de restricciones para evitar sobrescrituras accidentales.
- **Panel "Solo Alertas" en PDC:** Botón rápido en la barra de herramientas que exprime el Plan de Compras filtrando en la grilla únicamente los paquetes críticos, retrasados o sin configurar.
- **Iconografía de Riesgo PDC:** Insignias de semáforo (check-circle, cog, exclamation-triangle) inyectadas directamente en los botones de acción para rápida identificación de cuellos de botella en adquisiciones.
- **Documentación Stitch:** Guías de conexión, operación MCP, brief de design system AIA y catálogo técnico de módulos visuales.
- **Sistema de Recuperación de Contraseña:** Implementación completa del flujo "Olvidé mi contraseña" con envío de correos (MailService), tokens de un solo uso (PasswordService) y vistas dedicadas tanto en la aplicación principal como en el panel administrativo.
- **Infraestructura SMTP para Recuperación:** Integración de `phpmailer/phpmailer`, variables `APP_URL` + `MAIL_*` y plantilla Docker de entorno para soportar el envío de enlaces de restablecimiento.
- **Protección CSRF para Auth:** Nuevo `CsrfTokenManager` para blindar los formularios de inicio de sesión y recuperación de credenciales contra ataques de falsificación de petición en sitios cruzados.
- **Expansión de Capacidades RBAC:** Adición de 7 nuevos flags de capacidad en `RbacManager` (`canManageGeneralProgram`, `canEditPastGeneralProgram`, `canManageWeeklyProgram`, etc.) para un control granular más preciso en los módulos LPS y Contratos.
- **Soporte de Utilidades Globales:** Nuevo namespace `App\Support` con `ModuleRequestContext` para la resolución segura y centralizada de parámetros de contexto (Proyecto, Base de Datos, Semana) en módulos legacy y modernos.

### Cambiado

- **Shell Unificado de Modales en Compras:** Listado de Actividades, Contratos y PDC ahora comparten una base visual consistente para headers, formularios, acciones y estados responsive, reduciendo desalineaciones entre modales operativos.
- **Refactor de Seguridad PDC:** El script `actualizar_pdc.php` ahora utiliza `ModuleRequestContext` para resolver la base de datos y semana, e integra `rbac_guard_require_permission` para validar permisos antes de cualquier operación de escritura.
- **Hardening de APIs LPS:** Actualización de `PdcApiController`, `ContratosApiController` y `ListadoActividadesApiController` para mejorar la resolución de contexto y el manejo de excepciones mediante bloques `Throwable`.
- **Blindaje de Escrituras por Semana Activa:** Las operaciones sensibles en Contratos, Listado de Actividades y PDC ahora se acotan al contexto operativo resuelto por `ModuleRequestContext` para reducir cruces de semana/proyecto.
- **UI de Contratos Más Clara y Responsive:** El modal de edición de contratos fue remaquetado con una grilla consistente, mejor integración de Select2 y una presentación más estable en desktop y móvil.
- **Listado de Actividades Alineado con la Semana Vigente:** La vista y sus flujos AJAX ahora priorizan `Max_Semana`, reutilizan un único set de opciones para la actividad de inicio y endurecen la edición inline según el rol operativo.
- **Mejoras en Login UX:** Integración de enlaces de recuperación de contraseña y avisos de éxito tras el restablecimiento de credenciales en las vistas de acceso.
- **Refactor de LoginController:** Simplificación del método `updatePassword` delegando la lógica de validación y hash al nuevo `PasswordService`.

### Corregido

- **Estabilización de Popovers Ricos (PI)**: Recuperación del renderizado de tooltip enriquecido en las cabeceras de restricciones de Programación Intermedia e inyección unificada del template `.pi-help-tooltip`. Se solucionó el bug de parpadeo (titileo) al interactuar o pasar el cursor entre el trigger y el cuerpo del popover.
- **Fix Falsos Duplicados en Subcontratistas:** La validación de unicidad ahora compara contra el snapshot persistido cargado desde la API, en lugar de las filas transitorias de Handsontable, evitando falsos duplicados al crear registros por nombre, correo y NIT.
- **Programación Intermedia:** Persistencia de filtros nativos de Handsontable tras guardar, validación correcta de responsables con filtros activos y desbloqueo de edición en filas filtradas.
- **Filtros Handsontable con HTML:** Los filtros nativos de Programa General, Programación Intermedia, Programación Semanal y Actualizar Cronograma muestran texto plano en columnas con render jerárquico HTML.
- **Select2 Desanclado en Nueva Actividad:** El selector de tarea inicial vuelve a desplegarse ligado al campo correcto dentro del modal, con dropdown estable y sin desorganizar el layout al escribir o elegir opciones largas.
- **Autoactualización de PDC al Navegar Entre Módulos:** El flujo hacia `/pdc` ahora conserva la semana operativa válida, marca el origen de navegación y ejecuta una sincronización one-shot del plan de compras al llegar desde Programa General, Actividades o Contratos.
- **Compatibilidad SQL en Detección de Tablas:** Corrección del uso de placeholders en `SHOW TABLES LIKE` mediante el uso de `quote()` nativo de PDO, asegurando compatibilidad con el driver de base de datos en entornos SiteGround/Docker.
- **Error 500 en Remoción de Proyectos:** Implementación de manejo de excepciones (`try-catch`) en `UserController::removeProject` y `ProjectController::removeMember` para evitar fallos catastróficos si el servicio de sincronización falla, devolviendo ahora errores descriptivos.
- **Estabilidad en Gestión de Usuarios y Miembros:** Resolución de Error 500 al intentar eliminar usuarios o miembros, unificando el uso de `tableExists` seguro en el Core de la Base de Datos y blindando controladores con `try-catch`.
- **Sincronización:** Refactorización de `ProjectProfessionalsSyncService` eliminando el uso inseguro de `SHOW TABLES LIKE`, previniendo errores en esquemas de proyectos incompletos.
- **Gestión Flexible (Zero Projects):** Eliminación de restricciones de base de datos y frontend que impedían dejar usuarios sin proyectos asignados.
- **Sobreposición de Miembros en Admin:** Resolución de desbordamiento horizontal en la tabla de miembros asignados mediante contenedor responsivo y quiebre de palabras forzado para strings largos.
- **Actividad de Inicio en Listado:** La API y la vista de Listado de Actividades vuelven a resolver `actividadInicio` por `Consecutivo_en_Programa`, sincronizan la fecha desde la semana activa real y evitan inconsistencias al registrar, editar o consultar actividades.
- **Consolidación de PDC por Tipo de Contrato:** `actualizar_pdc.php` ahora agrupa y calcula fechas con alias explícitos de subactividades y usa `general_dias_procesos_contratacion` filtrado por tipo de paquete para evitar cruces y resultados erróneos.

- **Runtime Frontend Config Global:** Nuevo endpoint `'/runtime/frontend-config.js'`, servicio `src/Services/FeatureFlagService.php` y documento `docs/20260325_general_feature_flags.md` para exponer feature flags publicos sin acoplar las vistas al backend.
- **Cambio Obligatorio de Contraseña:** Nuevo flujo de seguridad con bandera `force_password_change`, endpoint `'/password/update'`, modal bloqueante en login con `AIA.Notice` y accion administrativa para forzar la rotacion masiva de credenciales desde el dashboard.
- **Sincronización de Profesionales por Proyecto:** Nuevo servicio `src/Services/ProjectProfessionalsSyncService.php` para reconciliar `admin/` contra `*_profesionales`, mapear roles `A/D/DCV/G/OT/R/S/SG`, consolidar duplicados por correo y preservar el historial por proyecto.
- **Flujo de Timeout de Sesion (Fase 1):** La expiracion por inactividad ahora redirige al login con un aviso visual en `AIA.Notice`, mejorando la experiencia tras sesiones vencidas.
- **AIA Notice Global:** Nuevo core `public/js/core/AiaAlertInterceptor.js` para centralizar alertas, toasts y badges de guardado sobre SweetAlert2 en Admin, login y modulos LPS.
- **TomSelect Premium AIA:** Implementación de arquitectura de estilos corporativos (Naranja Construcción) en `tom-select-premium-aia.css`. Incluye tipografía Montserrat/Inter, chips adaptables con word-wrap y un botón de limpieza elegante integrado siguiendo el manual de marca 2026.
- **Herencia de Restricciones (Manual/Excel):** Sincronización automática de las 7 restricciones individuales (D y E, Materiales, MdeO, etc.) tanto en procesos de importación masiva como en la asociación manual por dropdown. (AIA 2026).
- **Persistencia de Mapeo Físico:** El botón "Eliminar Actualización" ahora realiza un `DELETE` físico de borradores en la base de datos, garantizando un flujo de trabajo limpio y permitiendo reintentar mapeos desde cero.
- **Página de Mantenimiento AIA:** Nueva página HTML standalone (`public/mantenimiento-aia.html`) con identidad corporativa, spinner animado y tagline de marca.
- **Plan de Desacoplamiento Visual CSS:** Documento técnico (`docs/css-desacoplamiento-plan.md`) para migración aditiva de estilos CSS sin tocar lógica ni romper legacy.

### Eliminado

- **Plan de implementación obsoleto:** Remoción de `implementation_plan.md`, reemplazado por el seguimiento vigente en `ROADMAP.md` y documentación operativa actual.

### Cambiado

- **Sincronización Productiva SiteGround:** La rama `main` quedó desplegada en `prueba-lps.lastplanneraia.com` y la base de datos remota fue clonada desde local sobre `dbhif4pdimjtxe`, preservando estructura y datos de prueba para validación operativa.
- **Gestión de Usuarios sin Borrado Físico:** El admin ya no elimina usuarios de `general_usuarios`; ahora los conserva, bloquea acceso con `Activo/Inactivo`, invalida sesiones de cuentas desactivadas y mantiene la trazabilidad histórica aunque se revoquen todos sus proyectos.
- **Contexto Inteligente de Aterrizaje por Proyecto:** Refinamiento del servicio `src/Services/ProjectLandingService.php` para resolver la semana operativa con búsqueda descendente (priorizando semanas más recientes), invirtiendo la prioridad para favorecer semanas abiertas sobre semanas confirmadas con calificaciones pendientes. Esto mejora la experiencia al aterrizar directamente en la semana de trabajo actual. `DashboardController`, `ProjectSelectorController`, `ProgramaGeneralController` y `ProgramacionSemanalController` sanean el contexto semanal antes de redirigir o renderizar vistas.
- **Switch Global de Console Logs:** El dashboard admin ahora permite activar o silenciar `console.log` en todo el frontend, con persistencia centralizada y recarga uniforme de la configuracion en login, selector de proyecto y vistas legacy/MVC.
- **Profesionales Gobernados desde Admin:** El módulo `Profesionales` ahora usa el correo como identidad real, permite nombres repetidos, sincroniza cargos desde `admin/`, bloquea edición de nombre/correo/cargo y deja `Activo` como control local solo para miembros vigentes del proyecto.
- **Normalización Canónica de Nombres en Profesionales:** Las tablas `*_profesionales` ahora guardan el `nombre` oficial de `general_usuarios` cuando el correo tiene una coincidencia única en Admin, tanto en la sincronización automática como en el alta/edición manual; si no existe match confiable, se conserva el nombre local capturado.
- **Bajas y Trazabilidad de Profesionales:** Retirar un miembro del proyecto o eliminarlo desde `admin/` ya no destruye su historial operativo; el sistema bloquea el registro local y evita el borrado del usuario maestro cuando existe trazabilidad en proyectos.
- **Subcontratistas Live Edit:** Desktop, mobile y PDC ahora exigen filas completas antes de crear registros y validan el estado final completo antes de guardar cambios parciales.
- **Autoguardado Unificado al Estilo PI:** Programa General, Programación Semanal, Programa General Actualizar, Subcontratistas y Profesionales ahora muestran el badge inline de `AIA.Notice` en lugar de `toastr` o fades locales, alineando el feedback visual con Programación Intermedia.
- **Estandarización Final de AIA.Notice:** La capa `AIA.Notice` ahora cubre confirmaciones, diálogos y mensajes multilínea en Admin y módulos LPS, reemplazando `Swal.fire`, `window.confirm` y fallbacks `alert()` residuales con una API consistente.
- **Fase 2 de Unificacion de Notificaciones:** Se completo la migracion de `alert()` a `AIA.Notice` en `funcionesGenerales6.js`, `ContextManager.js` y `cargarDatosGeneralesPagina2.js`, unificando bloqueos de negocio y errores AJAX en helpers compartidos.
- **Validacion Universal de Sesion:** El front controller verifica timeout en rutas protegidas antes de despachar la aplicacion.
- **Unificacion de Notificaciones:** `programaGeneralActualizar.view.php` continua la migracion desde `alert()` hacia `AIA.Notice` con fallback seguro.
- **Motor de Herencia Robusto y Unificado:** Re-ingeniería del método `getPreviousWeekData` en `GeneralApiController.php` para implementar una lógica de priorización inteligente: el sistema ahora identifica y prefiere registros con datos reales (Unidad/Cantidad) sobre registros anómalos o vacíos en caso de duplicidad.
- **Herencia Agnóstica a HTML:** Refactorización de la lógica en `nueva_semana.php` y `GeneralApiController.php` para que el sistema ignore etiquetas como `<b>` y `<small>` al comparar nombres de actividades, asegurando la herencia correcta de PDC y Responsables tanto en importación como en mapeo manual.
- **Robustez en Carga de Parámetros:** Inyección de protecciones `try-catch` y logs descriptivos en `cargarDatosGeneralesPagina2.js` para interceptar fallos en la función `cargaParametros()`, mejorando la observabilidad en producción.
- **Unificación de Notificaciones:** Inicio formal de la eliminación total de `toastr` en el repositorio. Los modulos principales y vistas administrativas ahora consumen `AIA.Notice` como capa oficial para toasts, errores y badges de guardado.
- **Ajuste de Timeout de Sesión:** Incrementado el tiempo de inactividad de 10 segundos (test) a 3600 segundos (1 hora) para despliegue productivo, alineando el comportamiento del backend con la configuración global de la aplicación.

### Corregido

- **Fix 404 Añadir Miembros:** Se renombró el endpoint de `/admin/proyectos/miembros/añadir` a `/agregar` para prevenir fallos 404 ocasionados por codificación de la `ñ`.
- **Carryover PS -> PG al Crear Semana:** La nueva semana ahora arrastra `Ejecutado_Real`, `Responsable_AIA`, `Sub_Contratista`, `unidad` y `cantidad_ppto` desde Programación Semanal hacia Programa General, respetando subdivisiones, mapeo por `programaAnteriorAsociar` y normalizando a `%` cuando las medidas son inconsistentes.
- **Programación Semanal - Bloqueo por Asignaciones Incompletas:** El estado operativo ya no marca una actividad como `Lista para Confirmar` si falta `Responsable_AIA` o `Sub_Contratista`; el chip, el cierre semanal y la API ahora tratan esos casos como bloqueantes operativos.
- **Fix CNP al Abrir la Vista:** La columna `¿Liberada?` en `views/programacion-semanal/CNP.view.php` ahora tolera valores `null` de `Prog_Sin_Restricciones_100` y la autoprogramacion semanal vuelve a recalcular ese flag para evitar warnings TN/4 de DataTables al abrir `/programacion-semanal/cnp`.
- **Fix Falsos Duplicados en Profesionales:** La fila borrador ya no se valida contra sí misma, los homónimos dejan de bloquear el alta y el guardado local solo rechaza correos realmente repetidos.
- **Fix Carga de Profesionales al Renombrar Dependencias:** La sincronización de `*_profesionales` ahora verifica correctamente las tablas dependientes al propagar nombres canonizados, evitando el error SQL 1064 que impedía abrir `/profesionales`.
- **Fix Integridad de Subcontratistas y PDC:** Se reemplazaron alertas SQL crudas por validaciones de negocio para campos obligatorios y duplicados por nombre, correo y NIT.
- **Fix Consistencia Admin/Proyecto:** Los roles `A` ahora sincronizan como `Administrador` en `Profesionales`, manteniendo el mismo criterio operativo entre `admin/` y el proyecto.
- **Recuperación Global de AIA.Notice:** Se restauró la carga de SweetAlert2 y `AiaAlertInterceptor.js` en legacy, admin y login, corrigiendo la regresión que dejaba sin alertas de guardado, cambios de semana y avisos de sesión a varias vistas operativas.
- **Programa General - Cambio de Unidad a Porcentaje:** Al convertir actividades con unidad fisica a `%` (o vacio), el sistema ahora preserva el ratio canonico de `Ejecutado`, limpia `cantidad_ppto` y reconstruye `Ejecutado Real` como porcentaje persistente tras guardar y recargar.
- **Fix Tecla ESC en Grilla:** Resolución del `TypeError` y pérdida accidental de valores al presionar ESC en los editores TomSelect. Se sustituyó el método inexistente por `cancelEditing()`, restaurando el estado previo de la celda de forma segura.
- **Sincronización de Assets:** Actualización de headers y links de fuentes en las vistas de actualización para garantizar la carga de tipografías premium y el bypass de caché mediante versionamiento de scripts (`v=tomselect30`).
- **Alineación Vertical y Filas:** Eliminación de la columna de numeración redundante en Handsontable y ajuste de `line-height` en `handsontable-module.css` para resolver el desalineamiento visual de las celdas.
- **Fix Sincronización TomSelect:** Ajuste de selectores de clase (`.ts-option` → `.option`) y habilitación de `closeAfterSelect` para un comportamiento intuitivo del dropdown.

### Cambiado

- **Migración de Vistas al MVC Moderno (Fase 2):** 17 vistas distribuidas en los subdirectorios legacy `construccion/*/views/` fueron resituadas dentro del patrón arquitectónico en el nuevo directorio raíz `views/`. Los controladores de `src/Controllers/` fueron paralelamente recompilados para resolver los path dinámicos hacia sus equivalentes modernos.
- **Importación de Cronogramas (Fase 4):**
  - **Selector de Fecha Inicial:** Implementación de un selector de fecha dinámico en el modal de importación exclusivamente para proyectos nuevos (Semana 0), permitiendo alinear el cronograma con el calendario real.
  - **Notificación y Redirección AIA:** Diseño de un flujo de éxito premium (Manual de Marca AIA) que confirma la creación del cronograma y redirige automáticamente al Programa General (Semana 1) para una visualización inmediata.
  - **Detección Dinámica de Esquema:** Motor de búsqueda inteligente para la columna de jerarquía (WBS) en Excel, eliminando la dependencia de un orden estricto de columnas.
- **Sanitización de Invocaciones:** Se blindó el indexado de assets (css, imagenes, archivos bases) en los archivos views migrados desde `../` hacia paths absolutos en `/construccion/…` para evitar crashes por niveles de directorios variables.
- **Apificación de Módulos (Fase 3):**
  - [x] Contratos (`ContratosApiController`)
  - [x] Listado de Actividades (`ListadoActividadesApiController`)
  - [x] Plan de Compras (`PdcApiController`)
  - [x] Profesionales (`ProfesionalesApiController`)
  - [x] Control de Cambios (`ControlCambiosApiController`)
  - [x] Subcontratistas (`SubcontratistasApiController`)
- **Migración LPS Core (Fase 4):**
  - [x] Programación Semanal (`SemanalApiController`) — list, save, autoprogramar
  - [x] CNC (`CncApiController`) — listado y guardado
  - [x] CNP (`CnpApiController`) — listado y guardado
  - [x] CIC (`CicApiController`) — listado UNION, guardado con cálculo de disciplinas
  - [x] Programación Intermedia (`ProgramacionIntermediaController`) — list y save
  - [x] Programación General (`GeneralApiController`) — list, update, updateBatch y codigos
- **UI & Bugs (Scroll Lock):** Se sustituyó completamente el plugin Select2 (jQuery) por Tom Select (Vanilla JS) en la grilla de Programación Intermedia. El uso de la nueva clase `HandsontableTomSelectEditor.js` aísla los eventos del DOM previniendo el secuestro global del `wheel` que congelaba el scroll tras cerrar los menús desplegables.
- **Kill Switch Legacy (Fases 5 y 6):** Culminación de la erradicación del código heredado.
  - **Assets:** Se migraron masivamente imágenes, CSS y JS desde la carpeta `/construccion/` a `/public/`. Además, se actualizaron con `sed` los paths relativos en las vistas renderizadas.
  - **Endpoints Huerfanos:** Se mudaron scripts POST/GET solitarios como `actualizar_pdc.php` o `cambiar_pagina.php` al sandbox `/src/Legacy/Endpoints/` interceptándolos a través del Front Controller vía `$router->post` fallback rules.
  - **Eliminación Física:** Borrado definitivo de la mega-carpeta `/construccion/` limpiando el footprint fundamental del sistema viejo sin breaking changes.

- **Unificación Script PDC:** Reintegración y refactorización del script legacy `actualizar_pdc.php`. Se fusionó la validación esencial de negocio (`pdcActivo`) extraída del código original de 2022 con la eficiencia de sentencias preparadas (PDO) y manejo de excepciones JSON desarrolladas recientemente, consolidando todo en `src/Legacy/actualizar_pdc.php` y eliminando la versión redundante `actualizar_pdc_nueva_semana.php`.

### Corregido

- **Fix Error 500 en Admin Login (Database Path):** Se resolvió un error fatal que impedía acceder al panel administrativo integrado moderno (`/admin/login`). El controlador frontal de la consola de administración (`admin/public/index.php`) y el mapa de autoloader de Composer (`composer.json`) seguían apuntando a la ruta legacy de `Database.php` (`construccion/src/Database.php`) que fue eliminada por el Kill Switch. Se actualizó el entrypoint hacia la ubicación actual `src/Core/Database.php` y se regeneró el mapa de clases.
- **Fix Assets 404 en Admin Panel (Global):** Corrección masiva de dependencias estáticas (logo AIA `florAIA.png`, `tablet-viewport-scale.js` y `login-brand-unified.css`) tanto en la vista de login como en el layout principal (`admin/views/layouts/main.php`). Se mutaron los paths huérfanos `/construccion/` por el directorio absoluto `/public/`.
- **Fix JS ReferenceError en Tabla de Usuarios:** Se corrigió un error de ejecución en `admin/views/pages/users/index.php` donde el objeto `table` no estaba definido al intentar inicializar los botones de exportación de DataTables.
- **Update Seed Script:** Se actualizó `seed_test_users.php` para apuntar a la nueva infraestructura de base de datos en `src/Core/Database.php` eliminando la dependencia de `construccion/conexion.php`.

- **Fix Error 500 Rutas Duplicadas:** Se purgó el enrutador principal (`public/index.php`) de un bloque de rutas obsoletas y fantasmas dirigidas a `src/Legacy/Endpoints/` que causaba colisión de declaración y caída total del servidor al intentar resolver la ruta de actualización del PDC.

- **Fix Desalineamiento de Tom Select:** Corrección de la posición y ancho en `HandsontableTomSelectEditor.js` para que el input del dropdown solape perfectamente la celda de Handsontable (`top`/`left` relativos exactos y `width` dinámico) pero permitiendo a su vez que la lista emergente se expanda horizontalmente (`min-width: max(300px, tdRect.width)`) para no truncar los nombres largos de empresas.

- **Fix DataTables POST vs GET Methods (APIs):** Se modificó `public/index.php` para resolver un Error 405 (Method Not Allowed) en las tablas heredadas de jQuery DataTables (CNP, CNC, Contratos, etc.). Se restauró la definición a `POST` (estándar requerido por el AJAX interno de los listados legacy), manteniendo la dualidad `GET`/`POST` exclusivamente para la nueva grilla de Handsontable en `/api/semanal/list`.
- **Refactor SemanalApiController (Proyecciones):** Se delegó el cálculo asintótico del remanente al iterador de listado de Programación Semanal a través del `LpsService::calculateWeeklyProjections` eliminando código duplicado en el controlador.
- **Fix Error 404 Control de Cambios:** Se corrigieron referencias residuales a scripts legacy (`listar_controlCambios.php` y `guardar_controlCambios.php`) en `controlCambios.js` que causaban fallos en la carga de la tabla y obtención del director de obra tras la migración a la API.
- **Fix CIC Evaluaciones No Persistidas:** Se reescribió `CicApiController::updateMetrics()` que era un stub incompleto: no calculaba los promedios por disciplina (Calidad, GSA, SST, ADM), no ejecutaba el segundo UPDATE de disciplinas, y buscaba `$_POST['Observaciones']` cuando el frontend envía `mdo_Observaciones`/`si_Observaciones`.
- **Fix CIC Listado (Campo semanasEnProyecto):** Se corrigió `CicApiController::list()` que hacía un `SELECT *` simple en vez de la consulta UNION del legacy que calcula `semanasEnProyecto`, selecciona la última semana con datos por subcontratista y filtra proveedores de suministro.
- **Cache-Busting Dinámico:** Se implementó un parámetro de versión `?v=<?= time() ?>` en la carga de `controlCambios.js` dentro de la vista para asegurar que los usuarios reciban las actualizaciones de las rutas AJAX inmediatamente sin intervención manual en el navegador.
- **Fix Error 403 y Migración a Front Controller Puro (Fase 1/2):** Al eliminar `construccion/index.php` en la Fase 1, las visitas al dominio principal caían en un Error 403 Forbidden por parte de Apache (`DirectoryIndex` ausente + `RewriteCond %{REQUEST_FILENAME} -d`). Para solucionarlo bajo arquitectura moderna, se eliminó la excepción de compilación física de carpetas en archivo `.htaccess`. Se erradicaron los archivos dinámicos obsoletos (`index.php` y `construccion/index.php`) y en su lugar el enrutador (`public/index.php`) aprendió a engullir todo intento de acceso global, asignando `/` correctamente al `LoginController`. Así logramos un **Front Controller Puro** en el servidor sin interrupciones ni redirects heredados.
- **Fix Navegación Select2 (Causa Raíz):** Corregida la referencia `this.instance` → `this.hot` en `HandsontableSelect2Editor.js`. En Handsontable 14.6.1 la instancia del grid se expone como `this.hot`, por lo que toda la lógica de navegación por teclado (Tab, flechas) nunca se ejecutaba al estar accediendo una propiedad inexistente.
- **Fix Paridad Local (HTTP 404 Assets):** Tras la erradicación del prefijo `/construccion/`, los assets de imagen, css y js quedaban huérfanos del `DocumentRoot` en entornos locales Docker (donde la raíz es el proyecto entero, no `/public/`). Se inyectó una reescritura silenciosa en el `.htaccess` global para reenviar llamadas estáticas transparentemente a `/public/` logrando así funcionalidad 1:1 con SiteGround sin alterar sintaxis HTML.
- **Fix Error 405 Method Not Allowed (Modal Nueva Semana):** Corrección logística de métodos en el Front Controller (`public/index.php`). Las URL como `/legacy/funciones_generales/php/nueva_semana.php` y `verificarCICActualizada` estaban registradas usando `$router->get()`, pero el JavaScript heredado las despachaba vía Ajax en método `method: 'POST'`, forzando el rechazo por método no admitido. Se realinearon las rutas del enrutador.

### Añadido

- **Auto-apertura de Dropdowns (PI):** Al navegar con Tab o flechas a cualquier celda con dropdown (Select2 o nativo), el desplegable se abre automáticamente sin necesidad de doble click. Funciona tanto para navegación nativa de Handsontable como para la que viene desde un editor Select2 abierto.


## [1.0.0-rc4] - 2026-03-04

### Añadido

- **Bloqueo Inteligente de Ceros Virtuales (PS):** Implementación de una validación estricta en el hook `beforeChange` de Handsontable y en `guardar_programacion_semanal.php` para impedir la inyección de cantidades `< 0.001` en el Compromiso. Esto previene evasiones y fuerza obligatoriamente el desencadenamiento del flujo "Causa de No Programación (CNP)".
- **Sistema de Alertas de Restricciones (PS):** Implementación de una segunda compuerta de validación en la autoprogramación. Ahora, tras la ejecución, el sistema detecta y notifica mediante un modal informativo las actividades que no se programaron por tener restricciones pendientes (< 95%), detallando específicamente los rubros (Diseño, Materiales, etc.) que bloquean el inicio.

### Cambiado

- **Leyenda Programa General:** Se removió la aclaración "(Last Planner 6 semanas)" del título del modal de leyenda visual de la grilla principal para simplificar la interfaz.

### Corregido

- **Fix Redondeo Botones (PS):** Resolución nuclear de especificidad CSS en las vistas principales (CNC, CNP y CIC). Se forzó quirúrgicamente el uso de selectores ID combinados y `border-radius: 4px !important` junto a `-webkit-appearance: none` y `appearance: none` para romper la herencia de "pill shapes" nativos, garantizar los botones estilo bloque cuadrado (Apple-style design system) y asegurar compatibilidad estándar cross-browser eliminando advertencias de linter.
- **Autoprogramación con Subcontratistas Múltiples:** Se reparó un bug crítico en `autoprogramar_actividades.php` donde el proceso de autoprogramación fallaba al intentar insertar actividades que habían sido divididas previamente entre múltiples subcontratistas en la Programación Intermedia. Ahora el sistema respeta la asignación individual del subcontratista durante la inserción masiva.
- **Handsontable Select2 Tab Navigation:** Se corrigió la interrupción del flujo ("focus trap") al presionar `Tab` dentro del editor múltiple Select2 (`HandsontableSelect2Editor.js`). Ahora la tecla navega intuitiva y correctamente hacia la siguiente celda adyacente sin perder el foco en la grilla.
- **Navegación Avanzada Select2:** Afinamiento del motor de `HandsontableSelect2Editor.js` para restaurar explícitamente el foco al `textarea` interno de Handsontable tras el cierre rápido del dropdown, habilitando navegación bidireccional continua con teclado y flechas direccionales.
- **Techo de Esfuerzo en Autoprogramación Semanal:** Se ajustó la validación en `hot.js` para asegurar que la sumatoria total del "Ejecutado Fin Semana" (y el avance real) considerando todas las porciones asignadas a distintos contratistas nunca exceda el 100% o la "Cantidad PPTO" de la tarea madre.
- **Validación Cruzada de Sobreasignación (PS):** Implementación de validación híbrida en la grilla de Programación Semanal que suma dinámicamente el `Compromiso` y `Ejecutado_Real` de todas las filas hermanas de una misma actividad. Impide estrictamente que la asignación combinada supere matemáticamente el 100% o la Cantidad PPTO.
- **Bloqueo Backend de Ceros Virtuales:** Se blindó el endpoint PHP (`guardar_programacion_semanal.php`) rechazando cualquier guardado con `Compromiso <= 0`, exigiendo que cualquier desprogramación fluya mandatariamente por el panel de Causas de No Programación (CNP).
- **Condición de Carrera en Creación de Semanas:** Se erradicó un bug de *Double Submit* en el UI de `funcionesGenerales.js` que registraba múltiples listeners al botón "Guardar". Esto causaba una falsa alarma visual de "semana no confirmada" tras insertar lógicamente la semana de forma exitosa.
- **Stale State en Navegación Menú (PS):** Se eliminó el uso de variables renderizadas estáticas (`+ semana`) en los dropdowns HTML de CNP, CNC y CIC en favor de interpolación en tiempo de clic. Esto previene que la navegación secundaria retroceda al inicio al perder estado.
- **Highlight Activo en Dropdown de Semanas (PS):** Se amplió el mapeo de URL en `cargarDatosGeneralesPagina2.js`, de forma que al navegar en los submódulos (CNP, CNC, CIC), el sistema de menús siga reconociéndolos como parentescos de `programacion_semanal` y resalte correctamente la semana actual en la barra de navegación del sitio.

## [1.0.0-rc3] - 2026-03-04

### Cambiado

- **Limpieza de Código Muerto:** Eliminación masiva de artefactos residuales de IA (`~/.gemini/brain`), librerías de prueba sin uso (tutoriales FPDF) y directorios estériles marcados como `_DEAD_PLAN_CALIDAD`. Se conservaron controladores explícitos de descargas y vistas secundarias con referencias dinámicas.
- **Erradicación Visual de DataTables:** Desacoplamiento final de DataTables y consolidación de Handsontable como el standard-bearer de las 3 programaciones principales (General, Intermedia, Semanal). Los archivos de la librería permanecen aislados solo para módulos legacy menores.

### Corregido

- **Validación Autoprogramación:** Removida la obligación en `guardar_programacion_semanal.php` de exigir el campo `Cantidad PPTO` cuando la unidad técnica asignada de la actividad de origen era exactamente `%`.
- **Interferencias Visuales en Unidades:** Se suprimió la dependencia estructural de `programa_general/hot.js` que forzaba visualmente la inyección de `%` a pesar de que el registro maestro original poseía vacíos estructurales.
- **Handsontable + Select2 Conflict:** Se resolvió de raíz la colisión de eventos tipo "outside click". Handsontable cerraba abruptamente las grillas múltiples de Select2 al hacer clic en opciones o chips. El `$wrapper` de `HandsontableSelect2Editor.js` ahora muta el DOM y se encapsula dinámicamente **dentro del TD activo** cada vez que se abre la lista, aislando el comportamiento de captura de click externo.
- **Integración Select Múltiple (PI):** Reestructurados los validadores `afterChange` en la vista de Programación Intermedia para iterar correctamente arreglos separados por coma y rescatar pills elegidos del estado deshecho si el usuario gatillaba las anclas estáticas de creación (`+ Crear Subcontratista/Responsable`).
- **Handsontable Select2 UI/UX:** Corrección intensiva de estilos inyectados CSS en tiempo de ejecución. Resolvimos el solapamiento de tags y limitación de altura (`max-height`, `flex-wrap`), así como los gaps absolutos indeseados entre el input container y el dropdown. Adición formal del editor derivado `Select2Single` y refactorización forzosa por Cache-Busting (`v=hotcustom3`).

### Añadido

- **Auto-Corrección Transversal de Unidades (%):** Inyección en el Endpoint de listado global (`construccion/api/program/list.php`). Ahora, y de manera automatizada, cuando cualquier miembro accede al listado maestro de Programa General, el sistema detecta actividades sin asignación de `unidad` y las auto-configura implícitamente a `%` sobre la base de datos para erradicar inconsistencias transaccionales futuras.

## [1.0.0-rc2] - 2026-03-03

### Añadido

- **Estrategia CSS 2026 (Refactor):** Fase 1 de la adopción del esquema de cascada `@layer`. Se aislaron `tokens.css` (`@layer theme`) y `access.css` (`@layer utilities`) para reducir colisiones de especificidad con los assets legados de Bootstrap.
- **UI Modal Premium:** Recuperación del formulario "Nueva Actividad" con formato avanzado de doble columna. Integración de la "Bandeja de Excepciones No Autoprogramadas" con enlace AJAX (JS) para autocompletar actividades pendientes.
- **Responsive Navigation:** Implementado el punto de quiebre `xl` (1200px) y tipografía fluida `clamp()` en Navbar para prevenir colapsos horizontales (overflow) del menú principal en resoluciones de tablet o pantallas intermedias.
- **Visual:** Nuevo diseño premium para el Sidebar Móvil (Drawer) usando capa CSS `aia-premium-drawer` con efecto Glassmorphism y animaciones spring para reemplazar el menú móvil genérico.
- **UX:** "Isla de Usuario" flotante (Thumb Zone UI) incorporada en el límite inferior del Drawer móvil para un acceso rápido y cómodo al Perfil, Cambio de Proyecto, Notificaciones y Cierre de Sesión.
- **Notificaciones PI (Fases 1-5):** Implementado un ecosistema completo para emitir asíncronamente (Upsert en la tabla `system_notifications`) los eventos críticos del ciclo de vida PI a través del `NotificationService`. Abarca Notificaciones de Semáforo (`blocked-overdue`, `execution-blocked`, etc.), Modificaciones de Restricciones explícitas, Asignaciones Manuales de Subcontratistas/Responsables y aplicaciones de Restricciones Compartidas (Lote MVC). Se incrustaron en `guardar_programacion_intermedia.php` (AJAX Legacy) y en `ProgramacionIntermediaController.php` (MVC Moderno).
- **Badge Notificaciones UI:** Actualización de `notifications.js` para sumar conteos internos `item_count` en tiempo real y mostrar dinámicamente notificaciones contraíbles en la campana sin necesidad de recargar la vista, incluyendo el ruteo seguro a `/api/notifications/unread`.
- **Limpieza de Deuda Técnica (DataTables):** Eliminación física de los archivos base legacy (`*.view.nuevaBarra.php`) de Programación General, Programación Intermedia y Programación Semanal, certificando a Handsontable como el único motor de renderizado de cuadrícula en el _Core_ de la aplicación.

### Corregido

- **Fix Navegación Select2:** Se solucionó el `"focus trap"` en `HandsontableSelect2Editor.js` interceptando las teclas `Tab`, `Esc` y `Flechas Horizontales`, devolviendo el foco correctamente a la grilla y permitiendo una navegación fluida entre celdas.
- **Cálculo Ejecutado Fin Semana (Prog. Semanal):** Se reemplazó la proyección estática (`7 / diasTotales`) por un cálculo asintótico en `listar_programacion_semanal.php`, de forma que el esfuerzo máximo sugerido y el `Ejecutado Fin Semana` respeten el remanente real y jamás superen el 100%.
- **Notificaciones Legacy/Race Condition:** Arreglado bug donde el dropdown del Navbar (componente Outbox) se quedaba en "Cargando..." en vistas Legacy (`Programa General`, etc.) debido a inyecciones asíncronas de HTML fallidas. Componente de JS adaptado para inicializarse independientemente vía `document.readyState`.
- **UI Elementos:** Reparado modal de leyenda (ReferenceError `renderLegendModal`) para la grilla Handsontable en Programa General.
- **Viewport Tablet:** Relajamiento del responsive scale para tablets (de `0.7` agresivo a `0.85` con escalado permitido), junto con breaking points intermedios (`xl`) en Navbar.
- **Compresión Vista Modal:** Refactor CSS Grid con estructura de 12 columnas en `programacion_semanal.view.handsontable.php`, empacando inputs para eliminar el scroll y mantener el botón guardar expuesto.
- **Sidebar Glitch iPad Air:** Corrección de breakpoints al colapsar el menú de navegación al modo hamburguesa para que el fondo asuma el _AIA Green_ y no negro.

### Cambiado

- **Ajuste Terminología LPS:** Renombrado el estado "No activa" a "Programada Manualmente" en Programación Semanal para diferenciar claramente las actividades de creación manual de las autoprogramadas.

## [1.0.0-rc1] - 2026-03-02

### Añadido

- **Sistema RBAC & Seguridad:** Implementación completa de Arquitectura Híbrida RBAC (Roles, Capacidades, `RbacService`), validación estricta server-side para `guardar_programacion_semanal`, normalización de cargos legacy y visualización del rol en el Navbar.
- **Migración de Endpoints API (Fase 3):** Consolidación de 6 módulos (Contratos, Actividades, PDC, Profesionales, Subcontratistas y Control de Cambios) bajo controladores MVC.
- **UI/UX 2026:** Migración exitosa de grillas a **Handsontable** iterativo con autoguardado, adición de paleta OKLCH corporativa (AIA Brand) y alertas dinámicas Toast.
- **Documentación Extendida:** Creación de diccionario `GLOSARIO.md` (100 conceptos clave) y mapa maestro de APIs `ROUTES.md`.

### Cambiado

- **Arquitectura Backend & Reportes:** Centralización absoluta del Output de inteligencia hacia `ReportController`, eliminando 11 scripts PHP obsoletos. Apunte del autoloader legacy a la raíz.
- **Documentos Maestros Mnemotécnicos:** Reestructuración profunda de `README.md` (El Viaje del Héroe), `ROADMAP.md` interactivo y `CHANGELOG.md`.
- **Mantenimiento y Deuda Técnica:** Purga global del "FilterManager" deprecado, refactor CSS Mobile-First de vistas `.view.handsontable.php`, y versionamiento de DB volcados.

### Corregido

- **Foco GUI & Estabilidad:** Corrección global de pérdida de foco en modales interactivos sobre grillas HOT y estabilización del Autoupdate on-cell-change.
- **Estandarización UTF-8 Fix:** Parche transversal de codificación de caracteres `Ń`/`ñ` a lo largo de registros SQL clave e interfaces LPS UI.
- **Linting de Core MD:** Formateo intensivo Prettier para superar los umbrales de caracteres máximos y listados mal compuestos.

### Eliminado

- Remoción total del antiguo módulo `PaquetesContratacion` en favor del gestor canónico moderno `/contratos`.
- Descarte formal documentado del POC de arquitectura SPA Lite.

## [0.5.0] - 2026-02-03

### Añadido

- **Estandarización de Código (PSR-12):**
  - Implementación de `php-cs-fixer` en todo el proyecto.
  - Formateo automático de 159 archivos para cumplir con estándares internacionales.
  - Creación de configuración personalizada `.php-cs-fixer.dist.php`.

- **Análisis Estático (PHPStan):**
  - Instalación y configuración de `PHPStan` (Nivel 1).
  - Generación de línea base de errores (599 reportes) para guiar la refactorización arquitectónica.
  - Creación de configuración `phpstan.neon`.

- **Documentación:**
  - Cierre formal de la Fase 1 en `ROADMAP.md`.
  - Creación de `walkthrough.md` con evidencia de pruebas de humo.

## [0.4.0] - 2026-01-08

### Añadido

- **Gestión de Miembros:** Implementación completa del sistema de membresía para vincular usuarios únicos a múltiples proyectos.
- **Inteligencia de Roles:**
  - Motor de normalización de cargos (limpieza de acentos, géneros y artículos).
  - Búsqueda difusa (Fuzzy Matching) mediante algoritmo de Levenshtein para tolerancia a errores de ortografía.
  - Sistema de aprendizaje persistente en la tabla `role_intelligence` que evoluciona con el uso del administrador.
- **UI Proyectos:** Nueva interfaz para asignar y revocar acceso a proyectos con sugerencias inteligentes en tiempo real.
- **Seguridad:** Protocolo de "Seguridad por Defecto" que asigna rol de Visualizador ante cargos desconocidos.

### Cambiado

- **Normalización de Datos:** Unificación de la tabla `general_usuarios` eliminando más de 100 registros duplicados y consolidando sus accesos en la nueva tabla `project_members`.
- **Arquitectura:** Centralización de la lógica de permisos en la clase `RoleManager`.

## [0.3.0] - 2026-01-08

### Añadido

- **Integridad de Datos:** Creación automática de 10 tablas relacionales por cada proyecto nuevo.
- **Gestión de Prefijos:** Renombrado atómico de tablas de base de datos cuando se modifica el prefijo del proyecto.
- **Respaldos:** Funcionalidad para exportar y descargar un volcado SQL completo de las tablas de un proyecto.
- **Eliminación Segura:** Flujo de trabajo con SweetAlert2 que descarga un respaldo antes de eliminar físicamente las tablas.
- **UI Proyectos:** Integración completa de DataTables con traducción al español, Toastr para feedback asíncrono y corrección de solapamiento en el layout.

### Corregido

- Error de espacio de nombres en la generación del token CSRF en el diseño principal (`\Admin\Core\Security`).
- Delegación de eventos en DataTables para asegurar que los botones funcionen tras búsquedas o cambios de página.

## [0.2.0] - 2026-01-08

### Añadido

- **Mejoras en Gestión de Proyectos:**
  - Esquema de proyecto ampliado con nuevos campos: Área (Construcción/PI), Control de Acceso, Estado de PDC, Fechas de Línea Base (Inicio/Fin), Costo de Retraso y URL de Control de Cambios.
  - Generación Automática de Nombres de Base de Datos: Implementada una lógica robusta de `slugify` que:
    - Elimina palabras vacías en español (el, de la, la, etc.).
    - Convierte números (1-10) a números romanos (i, ii, iii...).
    - Maneja la transliteración y separa las palabras con guiones bajos.
    - Añade automáticamente el sufijo `_pi` para proyectos del área PI.
  - Implementación completa de CRUD:
    - Nueva vista de creación de proyectos con campos avanzados.
    - Vista de edición de proyectos con capacidad de anulación manual del nombre de la base de datos.
    - Lista de proyectos actualizada con columna de Área y estilo mejorado.
  - Enrutamiento seguro para todas las operaciones CRUD de proyectos con protección CSRF.

## [0.1.0] - 2026-01-08

- Versión inicial del proyecto.
