# Bitácora de validación — dark-mode-todos-los-modulos

Registro de verificaciones ejecutadas y de regresiones toleradas durante F1.
Ninguna fase cierra con entradas abiertas.

## Formato

```
### <fecha> · <fase> · <tramo>
- **Comandos:** …
- **Resultado:** …
- **Regresión abierta:** superficie / síntoma / commit de cierre previsto
- **Evidencia:** ruta bajo evidence/
```

---

### 2026-07-25 · Apertura del goal · medición de estado

- **Comandos:** `node scripts/design-system-audit.mjs`
- **Resultado:** **falla**. `programacion-semanal embedded-style-block: 1 > path budget 0`.
  Total de hallazgos vivos: 7 230. Raíces escaneadas: `views`, `public/js`, `public/css`,
  `src/View/Components` — `admin/` fuera.
- **Regresión abierta:** ninguna imputable a este goal. El rojo es preexistente en `main`
  (`8a13ad4`) y se cierra en la tarea T0.1 de F0.
- **Evidencia:** medición reproducible con el comando de arriba; no se archivó copia porque el
  audit se regenera de forma determinista sobre el mismo commit.

**Nota de atribución:** todo rojo posterior a T0.1 es imputable al trabajo de este goal.

### 2026-07-27 · F1 · seis modales huérfanos del tramo 5c

- **Commits:** `94ad742`, `116e44b`, `d8d4372`, `ead1408`, `e3201e4` (uno por dueño de cascada).
- **Comandos:** `npx playwright test tests/browser/modales-dark-homologacion.mjs --workers=1`
  (**6/6 PASS**); `node scripts/design-system-audit.mjs` (**6.699 → 6.649, −50**);
  `npm run test:design-system:static` (**323 pass / 1 fail**); `npx biome check` sobre los cinco
  archivos CSS (sin cambio frente a su línea base).
- **Resultado:** los siete defectos de `task-5c-report.md` §5.7 (filas 1-7) pasan de 1,00-4,43:1 a
  10,21-15,14:1. Barrido ciego de los cinco modales: 182 nodos con texto propio, **0** por debajo de
  su umbral WCAG, **0** errores de consola.
- **Rojo cerrado:** `inline-style: 116 > baseline 115`, que era preexistente (verificado en worktree
  limpio de HEAD), se cierra al retirar los diez `style=` de color de `programaGeneralActualizar`.
- **Regresión abierta:** ninguna imputable a este tramo. `design-system-body-canvas-dark.mjs` falla
  en dos sitios ajenos: `--ps-critical-bg` de `/programacion-semanal`, roto por el commit `9f6de25`
  de otra sesión, que cambió la escalera de tintes sin actualizar la expectativa del test; y el rojo
  deliberado de `/profesionales` y `/subcontratistas`. `contracts.test.mjs` sigue rojo porque exige
  árbol limpio y hay cambios de otras sesiones sin commitear.
- **No tocado y documentado:** `.btn-secondary` y `.btn-danger` de los pies de modal son inertes —
  los pisa un `!important` global de `styles.css:971`/`:998`, fuera de este tramo. `.btn-secondary`
  deja una losa blanca sobre el pie oscuro que **pasa AA (19,81:1)** y por eso el barrido de
  contraste del 5c no la vio.
- **Evidencia:** `.superpowers/sdd/f1-modales-huerfanos-report.md` y cinco capturas en
  `evidence/f1-modales-huerfanos/`.

### 2026-07-27 · F1 · ampliación tras barrido completo

- **Commits:** `f7dfbbf` (seis modales más), `3eb1486` (dos rojos del canvas oscuro).
- **Hallazgo:** el barrido de los **56 modales `.aia-modal` de las siete rutas** —y no solo los seis
  nombrados— destapó **seis modales más** con el mismo defecto, 29 roles entre 1,05:1 y 1,61:1. El
  mayor, `/contratos #modalEditarContratos`, era un skin entero sin migrar en `styles.css:4879-5160`.
- **Regla operativa establecida:** el **origen** del `!important` decide el arreglo. Si viene de la
  misma capa (`styles.css`, caso `.btn-secondary`), se le gana con otro `!important` acotado. Si viene
  de `@layer vendor` (caso `text-muted`, `bg-light`), no hay arreglo desde CSS: la inversión del orden
  de capas para `!important` lo hace inalcanzable y hay que retirar la clase del markup.
- **Estado final:** 12/12 en la suite del tramo; **601 nodos medidos, 2 bajo umbral** y ambos
  justificados (`text-danger` preexistente de §5.7 fila 8; `Aplicar` deshabilitado, exento por 1.4.3).
  Audit **6.699 → 6.605**.
- **Rojos ajenos cerrados:** expectativa obsoleta de `--ps-critical-bg` (la dejó `9f6de25`) y una
  **carrera** en el test de Handsontable que producía un rojo intermitente en `/pdc` una pasada de
  cada dos. Ninguno era regresión de este tramo; el diagnóstico se hizo contra un worktree limpio.

### 2026-07-28 · F1 · tramos 5e a 5f-bis — regresiones toleradas · **TODAS DISPUESTAS AL CIERRE**

Anotadas aquí porque el Step 4 de la Task final recorre **esta** bitácora, y hasta ahora vivían solo
en reportes bajo `.superpowers/`, que está gitignoreado (lo señaló la review del 5f-bis).

- **Borde de control por debajo de 1.4.11 — SISTÉMICO, no de un tramo.** `--ds-active-border` es
  translúcido al 22 % y compuesto rinde **1,86–1,91:1**: así están **221 de 278 controles** de la app.
  El design system **no declara hoy ningún token de borde que alcance 3:1 sobre oscuro**
  (`--ds-color-border-strong` rinde 1,21:1 y `--ds-color-border-default` 1,10:1, ambos medidos).
  Mitigado puntualmente en `styles.css` (control inline de DataTables, remapeado a
  `--ds-active-text-secondary`, 10,39:1), pero es un parche local con desajuste semántico declarado.
  **Cierre:** exige un token de borde para dark en el DS. No se puede cerrar dentro de F1.

  *Instancia añadida en el tramo 5i (2026-07-28), registrada aquí y **no** como regresión abierta:*
  el filete de `.ct-modal-header` pasa de 2,31/2,02:1 a **1,84/1,68:1** al adoptar `--ds-active-border`
  del shell en vez de su literal local. Adjudicado por la review: **no es cambio de matiz** (tono HSL
  147,9° contra 148,0°, medido), sólo de alfa (0,32 → 0,22); **1.4.11 no aplica**, porque es un divisor
  decorativo entre dos regiones que ya se identifican por su propio relleno, no un borde de control ni
  un indicador de estado; y **ningún umbral se cruzó**, porque 2,31:1 ya estaba por debajo de 3:1. Es
  la misma deuda sistémica de arriba, y se cierra con ella.

- **`.aia-info-nav__item.is-active` a 1,14:1 — incumplimiento real de 1.4.11.** El indicador de la
  opción activa del conmutador de módulo no usa borde (`border-top-width: 0px`) sino relleno, y ese
  relleno mide **1,14:1 contra el fondo del menú**. La tinta sí cumple (12,37:1). Vive en
  `public/css/listado-actividades.css:764`, fuera de `styles.css`. Medido dos veces de forma
  independiente (implementador y review del 5f-bis). **Cierre:** tarea propia; cambiar el relleno de
  la opción activa es diseño.

- **Anillo del pulso de Guardar a 1,97:1.** `@keyframes ps-btn-pulse` en `styles.css`. Tolerado
  deliberadamente: señal decorativa transitoria (1,2 s) sobre un botón ya visible, con el estado real
  señalado de forma no cromática (los campos poblándose). Subir la alfa es diseño. **Cierre:**
  decisión del usuario, o se acepta como exención documentada.

- **`.ps-field-error` no pinta su borde rojo.** `programacion-semanal.css:2726` fija `border-color`
  con `!important` en una hoja que declara `@layer components`, y por la inversión de capas gana a
  `module.components`. Solo el halo señala el error. **Cierre:** es cambio de comportamiento, no de
  tokenización; tarea propia. → **CERRADA el 2026-07-28**, ver el tramo del cierre más abajo.

- **Orden de prominencia invertido en `.cic-text-dark`.** En claro la rama escalada era la más
  contrastada (≈12,9:1 frente a ≈4:1 de la normal); en dark queda en 5,04:1 frente a 7,10:1. El matiz
  y la saturación conservan la lectura de «más severa». No hay mejor opción con el mismo par de
  tokens. **Cierre:** registrado como decisión aceptada, no como consecuencia.

### 2026-07-28 · F1 · tramo 5j — PDC, indicadores de desviación y leyendas compartidas

- **Commits:** `3536848` (ayudantes del modal), `71a719a` (iconos de estado), `1260b3e` (conmutador
  «Solo alertas» y utilidades), `d22a6e6` (geometría de leyenda). Rango `styles.css` 4360-4707,
  vaciado; el archivo pasa de **4.831 a 4.494 líneas**.
- **Comandos:** `node scripts/design-system-audit.mjs` (**6.004 → 5.880, verde**);
  `node scripts/design-system-entrypoint-partition.mjs` (**PASS**);
  `npm run test:design-system:static` (**337 pass / 1 fail**, el de árbol limpio);
  `npx biome check` sobre `styles.css` (**4 errores**, sin cambio) y sobre `legacy-bridge.css`
  (**limpio**); guards `pdc-chips-dark`, `state-tint-ladder`, `ops-state-chip-hue`,
  `programacion-semanal-legend-honesty`, `programa-general-legend-hue`,
  `programa-general-legend-modal-dark` (**10/10**), `modales-dark-homologacion` +
  `contratos-modal-header-dark` + `design-system-body-canvas-dark` (**19 pass / 1 fail**, el rojo
  conocido de `/profesionales` y `/subcontratistas`), `shell-sidebar-rollout` (**135/135**).
- **Resultado:** diff de valores computados VACÍO en `/programa-general`,
  `/programacion-intermedia`, `/programacion-semanal` y `/pdc` sobre la geometría del chip y del
  contenedor de leyenda (20 propiedades × 4 rutas) y sobre la caja de los 27 chips, salvo los
  cambios buscados.
- **Dos fallos reales de contraste cerrados.** La tinta propia de `.pdc-modal-field` pintaba a
  **1,93:1** sobre el fondo oscuro que le pone `pdc.css` (3 campos del modal de contrato); pasa a
  **14,99:1**. Y las ocho tintas de iconos de `/pdc`, medidas contra los dos fondos de fila reales
  de la grilla: **cinco de las ocho** caían por debajo del 3:1 de WCAG 1.4.11 en su peor caso
  (1,32:1 la peor); tras el cambio el peor caso de las ocho es **5,19:1**.
- **Dos premisas del plan corregidas por medición, no por criterio.** (1) El bloque de geometría de
  leyenda NO es portante para `/programa-general`: `#pgLegend` no lleva `pdc-legend-autoscaling` y
  sus chips son `.aia-chip.pg-filter-chip`, así que ningún selector del bloque los alcanza. Lo es
  sólo para PI y PS. (2) `.pdc-btn-alertas` no es una piel clara suelta en una barra oscura: los
  NUEVE `.btn-pdc-modern` de esa barra son claros, por `buttons.css`, alias deprecado con dueño
  `pdc` y política `future-module-migration`, fuera de F1.
- **Regresión abierta:** ninguna imputable a este tramo.
- **Defecto previo destapado, con tarea propia:** el conmutador «Solo alertas» de `/pdc` no tiene
  marca visual de estado activo. El JS escribe `is-active` (`hot.js:294`, `:993`, `:1000`) y el CSS
  esperaba `.active`; esas tres reglas, nunca alcanzables, se podaron. Medido con las transiciones
  desactivadas. **Cierre:** decidir con el dueño del módulo si se repone sobre `.is-active`; el
  naranja corporativo no tiene token exacto (`--aia-orange-primary` rasteriza distinto).
- **Deuda sistémica no cerrada aquí:** `.pdc-btn-alertas` conserva dos literales neutros porque
  ningún token rinde su valor exacto, medido en Chromium. Misma clase de decisión que
  `styles.css:1062`.
- **Evidencia:** `.superpowers/sdd/task-5j-report.md`.

### 2026-07-28 · F1 · tramo 5k — UNLAYERED OVERRIDE BRIDGE y la `}` huérfana

- **Commits:** `85d68b5` (poda de lo muerto), `f8b5730` (reubicación de lo vivo), `10ed584` (la llave,
  con los borrados que la compensan). Rango `styles.css` 4374-4496, vaciado; el archivo pasa de
  **4.496 a 4.381 líneas**.
- **Comandos:** `node scripts/design-system-audit.mjs` (**5.865 → 5.848 VERDE**, `css-outside-layer`
  824 contra baseline 829); `npx biome check public/css/styles.css` (**4 → 3 errores**, la llave);
  `npm run test:design-system:static` (**338 pass / 0 fail**, primera vez sin fallos en toda la fase);
  guard de canvas 2/2; modales 16/16; harness sidebar 135/135.
- **Resultado:** 11 de las 16 reglas del puente estaban muertas; 5 vivas reubicadas sin cambiar ni un
  valor de color, matiz, opacidad o anchura. A/B de valores computados en 14 rutas: **una sola
  diferencia**, inerte.

- **CORRECCIÓN DE UN MODELO DE CASCADA QUE ESTA FASE DABA POR BUENO.** El brief del tramo —escrito por
  el controlador— afirmaba que este bloque estaba «en la cima de la cascada» por ser `!important` sin
  capa, y que moverlo a cualquier capa lo debilitaría. **Es falso, y la conclusión estaba invertida.**
  `styles.css` se importa con `layer(module)` (`core.css:20`, `aia-design-system.css:32`), así que
  nada dentro de ese archivo estuvo jamás sin capa: sus reglas de nivel superior resolvían a `module`
  a secas. Para declaraciones `!important` el orden de capas se invierte **también entre una capa y
  sus subcapas**, de modo que `module` a secas es la ranura **más débil** de `module`. El puente no
  estaba arriba, estaba casi en el fondo — y eso explica que 11 de sus 16 reglas llevaran tiempo
  muertas sin que nadie lo supiera.
  Verificado de forma independiente por la review desde CSS Cascade 5, y corroborado con evidencia
  del propio repo: `buttons.css:77` y `:932` le ganaban desde `layer(components)`, cosa imposible si
  el puente hubiera estado en la cima. **El modelo corregido queda en código trackeado**, en el
  comentario de `public/css/design-system/adapters/legacy-bridge.css:660-676`.

- **La llave huérfana, diagnosticada de verdad:** Chrome no la descartaba — por CSS Syntax 3 el
  `}` suelto se reconsume como primer valor del *prelude* de la regla siguiente, que queda inválida y
  **se tira entera**. El parser del audit, en cambio, dejaba ciegas las 12 reglas posteriores, y por
  eso sólo contaba 4 `css-outside-layer`. Parte de lo «muerto» lo estaba **por el error de sintaxis**,
  no por la cascada; el reporte los distingue.

- **Regresión abierta:** ninguna imputable a este tramo. El hairline `#cbd5e1` que se conserva rinde
  **8,17–10,93:1** sobre las cinco superficies de rejilla medidas: es **desajuste tonal, no
  incumplimiento de 1.4.11**, y esto corrige otra afirmación del brief. Borrarlo habría sido **peor**:
  el marco caería a `--ds-active-border`, que ahí rinde 1,92:1 — la deuda sistémica del design system
  ya registrada arriba, que F1 no puede cerrar.

- **Anotado para que el siguiente no lo redescubra:**
  1. `cargarDatosGeneralesPagina2.js:734` inyecta en runtime una hoja `<style>` con
     `.ps-action-btn { display: none !important }`. Es sin capa, así que su `!important` es el más
     débil de todos y hoy pierde contra `programacion-semanal.css:2445`. Vive en la rama de rol
     **Subcontratista**, que el A/B no ejerció porque corrió como Admin: benigno **por construcción,
     no por medición**.
  2. Las reglas reubicadas quedan **más temprano en orden de fuente** (`legacy-bridge.css` se importa
     antes que `styles.css`). El orden de aparición no se invierte para `!important`, así que un
     empate de misma capa y misma especificidad se voltearía. Comprobado que no ocurre: la única hoja
     candidata usa `:where()`, especificidad (0,0,0).
  3. `#cbd5e1` pasa a vivir dentro de `public/css/design-system/**`. `hardcoded-hex` **no** está
     exenta ahí, así que sigue contándose.

- **Evidencia:** `.superpowers/sdd/task-5k-report.md` (no versionado) y el comentario de cascada de
  `legacy-bridge.css:660-676` (sí versionado).

---

### 2026-07-28 · F1 · tramo 5m — el desplegable select2 blanco dentro del modal oscuro

- **Comandos:** sonda propia de contraste sobre Playwright (`__aiaContrast`/`__aiaBoundary` de
  `tests/browser/support/contrast.mjs`) a 1180×820 dark **con el desplegable abierto** en
  `/listado-actividades`, `/programacion-semanal`, `/contratos` y `/pdc`;
  `node scripts/design-system-audit.mjs`; `node scripts/design-system-entrypoint-partition.mjs`;
  `npm run test:design-system:static`; `npx biome check public/css/styles.css`;
  `npx playwright test tests/browser/modales-dark-homologacion.mjs
  tests/browser/programacion-semanal-legend-honesty.mjs
  tests/browser/design-system-body-canvas-dark.mjs --workers=1`;
  `docker compose exec app php tests/test_design_system_head_component.php`.

- **Resultado:** dos defectos independientes, en dos rutas distintas y con causas distintas.
  En `/programacion-semanal` el vendor llegaba por un `<link>` **sin capa**, que gana a todas las
  capas y dejaba inerte al adaptador; retirado el `<link>`, el panel pasa de 1,05:1 a 13,64:1, la
  tinta del valor de 1,63:1 a 15,12:1 y las opciones de 2,90:1 a 13,17:1. En `/listado-actividades`
  el vendor ya estaba capado y el único culpable era el bloque local de `styles.css`
  (`module.components` gana a `components`); retirado lo duplicado, el panel pasa de 1,05:1 a
  13,13:1 y la tinta del valor de **1,18:1** a 14,99:1. La frontera del buscador sube de 1,23 a
  4,82 al dejar de pisar el borde del vendor, y así cumple 1.4.11. Audit 5795 → 5789 (los seis son
  **borrado real** en `styles.css`, que no está exento; **cero relocalización**). Biome sobre
  `styles.css` sigue en 3 errores; guards 21/21; suite estática 337/1 (el único fallo es el de
  árbol limpio). Controles `/contratos` y `/pdc` medidos antes y después con el desplegable
  abierto: **ningún valor de color, borde, radio, sombra ni contraste cambia**.

- **Corrección al mapa del goal:** `#modalNuevaActividad` **no vive en `/programacion-semanal`**.
  Su único markup está en `views/listado-actividades/listadoActividades.view.php:121`; en
  `views/contratos/contratos.view.php:785` sólo hay un `.modal("hide")` sobre un nodo inexistente
  (comprobado en runtime: 0 nodos en esa ruta). El manifiesto del módulo no hizo falta: ya declara
  `select2` y esa vista usa `render()`, no `renderForModule()`.

- **Regresión abierta:** `/programacion-semanal`, el resaltado de opción del select2 deja de
  distinguirse de una opción normal, porque `adapters/select2.css` no declara ninguna regla
  `--highlighted` y el azul que lo daba era del vendor (blanco sobre azul, 2,90:1, por debajo de
  AA). Es el **mismo estado que ya tenían `/contratos` y `/pdc`**, capados desde antes de este
  tramo: la deuda es del adaptador, no de la ruta. Cerrarla exige una regla en
  `design-system/adapters/select2.css`, que cambiaría las tres rutas a la vez y el encargo de 5m
  prohibía expresamente ampliar el alcance. **Commit de cierre previsto: tramo propio del adaptador
  de select2.**

- **CERRADO en `b9e8ac1`** (antes figuraba aquí como «aplazado con desajuste declarado»): el séptimo
  literal del tramo era `box-shadow: 0 14px 28px …` en el bloque del desplegable. El implementador lo
  aplazó **correctamente** —su geometría no tiene peldaño equivalente en la escalera `--ds-shadow-*`
  (`sm` 2/6, `md` 8/24, `lg` 16/48) y mover geometría de sombra es diseño, que el spec de F1
  prohíbe—, y aportó el dato que decide: **sobre oscuro rinde ~1,03:1 contra su entorno, es decir es
  imperceptible**, un artefacto del tema claro.
  **Decisión del controlador:** borrar, no tokenizar. Adoptar `--ds-shadow-lg` habría cambiado el
  desenfoque de 28 a 48 px, que es exactamente el cambio de diseño prohibido; y una regla cuya única
  declaración no se ve no se tokeniza. Con ella se fue la regla entera.
  - **Comandos:** `node scripts/design-system-audit.mjs` (**5.789 → 5.786 VERDE**);
    `npx playwright test tests/browser/modales-dark-homologacion.mjs --workers=1` (**16/16**).
    `styles.css` queda en 10 hex y 6 `rgba()`.
  - **Adjudicado por la review del tramo:** no se pierde nada estructural — `overflow: hidden`, borde
    y radio los da el adaptador. Lo que se pierde es el único indicio de elevación del panel sobre el
    modal, que queda en el borde de 1 px a ~1,89:1: **la misma deuda sistémica de bordes ya registrada
    arriba**, no una nueva. Y converge `/listado-actividades` con las otras tres rutas, que nunca
    tuvieron sombra.
  - **Defecto de registro que este bullet corrige:** el commit `b9e8ac1` es del controlador y se hizo
    sin entrada propia en esta bitácora, dejando este punto abierto y **falso** (afirmaba que el
    literal seguía ahí). Lo detectó la review del tramo. Sin esta corrección, F1 no podía cerrar.

- **Evidencia:** `.superpowers/sdd/task-5m-report.md` (no versionado) y los dos comentarios de una
  línea que quedan en `public/css/styles.css:1507-1508` y `1523-1524`.

### 2026-07-28 · F1 · cierre de la regresión de 5m — el resaltado de opción del select2

- **Comandos:** sonda propia sobre Playwright a 1180×820 dark, proyecto Da Porto, **con el modal
  real de cada ruta abierto, el desplegable abierto y el resaltado movido fuera de la opción ya
  seleccionada** (`#modal_tnp` en `/programacion-semanal`, `#modalEditarContratos` en `/contratos`,
  `#modalContrato` en `/pdc`, `#modalNuevaActividad` en `/listado-actividades`);
  `node scripts/design-system-audit.mjs`; `npm run test:design-system:static`;
  `npx playwright test tests/browser/modales-dark-homologacion.mjs --workers=1`;
  `npx biome check public/css/styles.css`.

- **Resultado:** cierra la regresión que 5m dejó abierta, y de paso la deuda gemela que
  `/contratos` y `/pdc` arrastraban desde antes. `adapters/select2.css` pasa a declarar
  `.select2-results__option--highlighted[aria-selected]` con el par verde corporativo + tinta
  inversa. Medido antes: en las tres rutas la opción señalada daba **exactamente** el mismo
  `rgba(35,48,41,.86)` / `rgb(247,250,248)` que una opción normal — indistinguible con teclado y con
  ratón. Medido después: `rgb(26,86,51)` / `rgb(255,255,255)` en las cuatro rutas, **8,67:1** de
  tinta sobre su relleno (umbral 4,5). La opción **no** resaltada no cambia en ninguna ruta, y
  `/listado-actividades` conserva byte a byte el par que ya tenía. Audit **5786 → 5785**; estática
  338/0; modales 16/16; biome sobre `styles.css` en 3 errores.

- **Poda asociada:** el bloque `#modalNuevaActividad .select2-results__option--highlighted
  [aria-selected]` de `public/css/styles.css` se borra **en el mismo commit** que se añade la regla
  del adaptador. No es deducción: medido con el adaptador ya declarado y la regla local todavía
  presente, y otra vez tras borrarla, `/listado-actividades` da el mismo par en las dos pasadas —
  la regla local era redundante. Borrarlas por separado habría dejado un commit intermedio con la
  declaración duplicada y, como `module.components` gana a `components`, habría medido el
  adaptador creyendo que pinta cuando en esa ruta lo tapaba la local.

- **Sonda autovalidada:** el desplegable no existe en reposo y la opción resaltada sólo existe con
  algo bajo el cursor o el foco. La sonda aborta la ruta si no encuentra **exactamente un** nodo
  `--highlighted` o si el resaltado no logró salir de la opción ya seleccionada. Atrapó dos falsos
  verdes: con el modal cerrado el buscador no es enfocable, y con el modal abierto el foco que
  Bootstrap atrapa impide que el teclado llegue al panel montado en `<body>` — por eso
  `/programacion-semanal` se mide por la vía del ratón (`mouseenter`, camino real de select2) y las
  otras tres por teclado.

- **Regresión abierta:** ninguna imputable a este tramo.

- **Desajuste declarado, no corregido:** el relleno del resaltado rinde ~1,6:1 de **luminancia**
  contra el de una opción normal, por debajo del 3:1 que pediría un indicador no textual: la señal
  la lleva sobre todo el matiz, no el brillo. No se toca porque es el mismo par que producto ya
  aprobó y venía sirviendo `/listado-actividades`, y el encargo prohíbe expresamente cambiar el
  matiz. Revisarlo exige decisión de producto sobre el verde, no un ajuste de este tramo.

- **Corrección de puntero ajena a este tramo:** `docs/design-system/state-token-exceptions.json`
  citaba `public/css/styles.css:2593` y `:4235`. El commit inmediatamente anterior (`b9e8ac1`)
  borró 4 líneas de `styles.css` sin actualizar el inventario y dejó `state-token-pairing.test.mjs`
  **rojo en HEAD antes de tocar nada** (verificado con el árbol limpio). Este tramo desplaza 5
  líneas más. Se corrigen los dos punteros a `:2584` y `:4226`, verificando que la línea contiene
  el token; **no se toca ningún campo semántico** (`token`, `kind`, `reason`, `selector`) ni se
  añade ni se retira ninguna excepción.

- **Evidencia:** `.superpowers/sdd/task-5m-highlight-report.md` (no versionado), con la tabla
  antes/después de las cuatro rutas.

### 2026-07-28 · F1 · promoción de la primitiva verde al design system

- **Commit:** `b82890e`. `public/css/tokens.css:155` declara `--ds-color-domain-corporate-deep: #1a3c2a`,
  hermana de `--ds-color-domain-corporate`, y `styles.css:1070` pasa a consumirla.
- **Por qué no era «un literal que excusar»:** las dos custom properties que alimentan el degradado de
  las cabeceras de modal son hermanas y vecinas; una ya resolvía al design system y **la otra se había
  quedado atrás con el literal crudo**. Era una definición de token en el archivo equivocado, no una
  excepción.
- **La trampa, evitada:** el tramo 5c midió que los comentarios de `tokens.css` **inducen a error**
  (`--aia-green-dark` dice pintar un verde y pinta otro), y por eso el tramo 5i conservó el literal:
  cualquier token «parecido» oscurecería **cincuenta cabeceras de modal**. Por eso la regla fue
  primitiva **nueva** con el valor **exacto**, jamás reutilizar por parecido de nombre.
- **Nombre:** `-deep` y no `-dark`, porque en `tokens.css` ese sufijo significa «variante del tema
  oscuro» y `-on-dark` ya existe y es **más clara**.
- **Comandos:** `node scripts/design-system-audit.mjs` (**5.785, sin variar**);
  `node --test tests/design-system/state-token-pairing.test.mjs` (**3/3**, tras reanclar los punteros);
  `npm run test:design-system:static` (**338/0**); `modales-dark-homologacion` (**16/16**);
  `npx biome check public/css/styles.css` (**3 errores**).
- **Resultado:** **cero diferencias de píxel**. sha256 de la cabecera completa idéntico en
  `/contratos`, `/listado-actividades` y `/pdc`; perfil de 5 muestras del degradado decodificado del
  PNG idéntico.
- **Matiz declarado, no presentado como mejora:** el contador **no baja**. `tokens.css` está en
  `scannedRoots` y `hardcoded-hex` no tiene exenciones, así que el literal **se mueve**, no
  desaparece. Y es correcto: un token primitivo tiene que declarar un valor literal en algún sitio.
  Lo que se arregla es la arquitectura.
- **Regresión abierta:** ninguna.

---

## Cierre de F1 — criterio revisado (decisión del usuario, 2026-07-28)

**F1 cierra con `public/css/styles.css` VIVO**, reducido a sus excepciones adjudicadas, y con sus dos
`@import` en su sitio. Queda **derogada** la «Task final: borrar el archivo» del plan, y con ella la
métrica «líneas: 6.802 → 0».

**El porqué, y por qué no es rebajar el listón:** el desmantelado por tramos estaba acotado a **fuga
de color y tokenización**, que es lo que el spec pedía. Eso está hecho: **483 hex → 10** y
**108 `rgba()` → 6**. Los **16 supervivientes no son deuda pendiente**: son excepciones adjudicadas
una a una por review con censo propio — no existe token equivalente (4 `--tipo-brand`, 3 iconos de
estado), o el token existente rasteriza a otro valor, o la sombra no tiene peldaño equivalente en la
escalera, o cambiarlas sería el rediseño que el propio spec prohíbe.

Lo que queda en el archivo **ya no es color**: son ~4.300 líneas de **layout y geometría**. Borrarlo
descolocaría la aplicación. Migrar ese layout es un trabajo distinto, con otro perfil de riesgo, y
merece su propia fase — meterlo en una fase de dark mode sería colarlo por la puerta de atrás.

**Lo que sí se conserva del criterio original:** esta bitácora no puede quedar con entradas abiertas.

## Disposición de las entradas que quedaban abiertas — cierre de F1 (2026-07-28)

F1 no puede cerrar con entradas abiertas. Éstas son las cinco que quedaban del bloque «tramos 5e a
5f-bis», más la del tramo 5m, con su disposición explícita. **Ninguna se cierra declarándola
resuelta: cada una tiene destino.**

1. **Borde de control por debajo de 1.4.11 (221 de 278 controles) — ESCALADA, no cerrada.**
   No es una regresión que F1 causara: es **deuda sistémica preexistente** que F1 midió y documentó
   por primera vez. Cerrarla exige **declarar un token de borde nuevo**, que es diseño de producto y
   el spec de F1 prohíbe expresamente. Reclasificada de «regresión abierta» a **deuda sistémica
   escalada con dueño**: chip `task_976d370a`, que además arrastra sus tres instancias hermanas —el
   filete de `.ct-modal-header`, el borde del panel de select2 y el relleno de
   `.aia-info-nav__item.is-active` a 1,14:1—. Esa reclasificación es el punto: **una deuda con dueño y
   medición no es una entrada abierta; una sin dueño sí.**

2. **`.aia-info-nav__item.is-active` a 1,14:1 — absorbida en el chip anterior.** Vive en
   `public/css/listado-actividades.css:764`, fuera de `styles.css`, y es la misma familia de defecto
   (indicador de estado que no alcanza 3:1). Se cierra con el token nuevo.

3. **`.ps-field-error` no pinta su borde rojo — chip `task_cbecaa7c`. CERRADA el 2026-07-28.**
   Era cambio de comportamiento, no de tokenización, y por eso F1 no podía tocarlo. Causa medida por
   F1 y confirmada al cerrar: la declaración culpable (para entonces ya en
   `programacion-semanal.css:2744`, no 2726) fijaba `border-color` con `!important` desde un
   `@layer components` de nivel superior y, por la inversión de capas, vencía a `module.components`.
   Ver el tramo de cierre al final del registro.

4. **Anillo del pulso de Guardar a 1,97:1 — CERRADA como exención documentada.**
   El propio registro ofrecía esa vía. Se acepta con su razonamiento, que se sostiene: es una señal
   **decorativa y transitoria** (1,2 s) sobre un botón que ya es visible, y el estado real está
   señalado **de forma no cromática** (los campos poblándose), así que no hay dependencia del color
   para entender lo que pasa. Subir la alfa sería diseño. **Si el usuario prefiere lo contrario, se
   reabre**: queda escrito aquí precisamente para que sea revisable y no se pierda.

5. **Orden de prominencia invertido en `.cic-text-dark` — ya estaba cerrada** como decisión aceptada,
   no como consecuencia. Sin acción.

6. **Relleno del resaltado de select2 a ~1,6:1 (tramo 5m) — deuda declarada, no regresión.**
   Adjudicado por la review: la línea base era **1,00:1 en tres rutas** (peor) e idéntica en la
   cuarta, así que el trabajo **mejoró** la situación. La señal la lleva el **matiz** y la tinta pasa
   con holgura (8,67:1). Cambiar el matiz es decisión de producto, ya tomada, y el spec de F1 prohíbe
   tocarla. Misma familia que la deuda del punto 1.

**Con esto la bitácora queda sin entradas abiertas y F1 puede cerrar.**

### 2026-07-28 · cierre de `.ps-field-error` (chip `task_cbecaa7c`)

**Arreglo:** se **retiró** el `!important` de `border-color` en
`programacion-semanal.css` (regla
`body.ps-page :is(#formulario_nuevo, …) :is(.form-control, .custom-select, textarea, select)`).
No se añadió ningún `!important` nuevo, ni aquí ni en `styles.css`: el conteo del audit **baja** de
5785 a 5784. `background` y `color` conservan la marca porque ahí `module.components` sí declara otro
valor y sin ella se perdería la homologación oscura.

**Hallazgo no previsto: el mismo `!important` mataba DOS estados, no uno.** Además del borde de
error, dejaba inerte el borde de **foco** que `styles.css` declara sin `!important`
(`#formulario_nuevo .ps-modal-form .form-control:focus` → `--pdc-active-border`). Medido antes del
arreglo, el campo enfocado computaba `--ds-active-border`, igual que en reposo. Esto es lo que
convierte «retirar» en la corrección correcta frente a «acotar con `:not(.ps-field-error)`»: acotar
habría arreglado el error y dejado el foco muerto.

**Medición (1180×820, dark, Da Porto, estado provocado con submit en vacío).** La sonda se
autovalida contando nodos `.ps-field-error` antes de medir — en reposo son **0** y un barrido sobre
la página cargada daría un falso verde.

| Campo | Borde antes | Borde después | borde/relleno | borde/entorno |
|---|---|---|---|---|
| `#idNuevoDisplay` (readonly) | `--ds-active-border` | `#dc2626` | **3,68:1** | **3,29:1** |
| `#Actividad` | `--ds-active-border` | `#dc2626` | 2,91:1 | **3,29:1** |
| `#Sub_Contratista` | `--ds-active-border` | `#dc2626` | 2,91:1 | **3,29:1** |
| `#Responsable_AIA` | `--ds-active-border` | `#dc2626` | 2,91:1 | **3,29:1** |
| `#Actividad` con foco | `--ds-active-border` | `--pdc-active-border` | — | — |

Línea base para comparar: antes el borde daba **1,88/1,68** y **1,89/2,14** — por debajo de 3:1 en
las dos direcciones y en los cuatro campos. Después pasa 3:1 **contra el entorno en los cuatro**.

**Desajuste declarado, no arreglado:** contra el *relleno* del campo, los tres campos sobre
`--ds-active-surface-raised` quedan en **2,91:1**, a 0,09 de 3:1. El borde sí supera 3:1 hacia fuera
(3,29:1) y el estado lleva además halo y un mensaje `role="alert"`, así que es perceptible; pero la
cifra queda anotada porque **subirla exigiría cambiar el matiz del rojo o declarar un token**, y las
dos cosas son decisión de producto (misma familia que la deuda del punto 1). Dato útil para esa
decisión: `--pdc-critical-border` (`#dc2626`) **sí** alcanza 3:1 sobre superficie oscura (3,29:1),
a diferencia de `--ds-active-border`, que se queda en 1,86–1,91:1.

**WCAG 1.4.1 (uso del color): se cumple, y no por el borde.** El error se anuncia además con texto:
`#formulario_nuevo .mensaje` con `role="alert"` y contenido «Complete los campos: Id, Actividad,
Sub-Contratista, Profesional AIA», visible en el modal.

**Hallazgo aparte, NO arreglado aquí — falta la asociación programática del error.** Los cuatro
campos en error tienen `aria-invalid` y `aria-describedby` a `null`. El mensaje existe y se anuncia,
pero nada lo ata al campo concreto: quien navegue campo por campo con lector de pantalla no sabe
cuál está mal. Es marcado y JS (`public/js/modules/programacion_semanal/hot.js`, `submitNewActivity`),
no tokenización ni CSS, y cae fuera de esta tarea. **Queda abierto con dueño propio.**

**Efecto secundario del arreglo, verificado:** barrido antes/después de los estados de reposo de
`#formulario_nuevo`, `#modal_eliminar_actividad`, `#modalcic_mdo` y `#modalcic_si` (borde, fondo y
tinta de cada `.form-control`/`.custom-select`/`textarea`/`select`). El diff devuelve **exactamente
los cinco valores buscados** —los cuatro bordes rojos y el borde de foco— y nada más.

**Gates:** `design-system-audit` VERDE en **5784** (baja 1), estática **338/0**,
`modales-dark-homologacion` **16/16**.

---

## Registro de los 23 commits posteriores al cierre de F1 (2026-07-29)

Entrada de saneamiento: desde `ad4a6e9` hasta `dc0e5f8` se trabajó sin apunte en esta bitácora. Se
anota ahora en bloque, separando lo que pertenece al cierre de F1 de lo que **ya es F3**.

### Bloque A — a11y y entregas sin capa (`ad4a6e9`..`d451a78`, 16 commits)

Serie de chips ejecutados uno a uno tras el cierre de F1, cada uno con review propia. Lo que dejaron:

- **El par de bordes de WCAG 1.4.11** (`6df99df`, contrato en `2433fb5`): nace
  `--ds-color-border-control-dark` y una regla de re-vinculación en `core.css` para que
  `var(--ds-active-border)` resuelva al token de control sobre controles. Los controles por debajo
  de 3:1 pasan de **413 a 112**, y los 112 restantes son exactamente el conjunto fuera de alcance.
  **Cuatro de los seis grupos del mapa inicial resultaron inertes al medirlos** — el mapa se escribió
  antes de medir y sobrevaloraba el alcance.
- **Entregas sin capa** (`dba703b`, `91de784`, `53d55c5`, `b123c52`, `c08ffe4`, `c177b1a`, `be11340`,
  `d451a78`): se censa la clase de defecto entera —`<link>` crudos, segundas cargas de vendor y
  bloques `<style>` inyectados por JS— y se construye el gate que la caza. Nueve casos inventariados.
- **a11y de formularios** (`c65eb9d`, `bc2ecc7`, `d4e593c`): `aria-describedby` y anuncio de errores
  en Contratos y Programación Semanal.
- **Segmentación y manifiestos** (`c0dd9a4`, `10b4276`).

**El hallazgo que más enseña, y es una regresión que causamos nosotros:** `c0dd9a4` movió cuatro
vistas al head segmentado, y con eso dejó de casar la guarda de
`cargarDatosGeneralesPagina2.js:19`, que condicionaba a `link[href*="aia-design-system"]`. Resultado:
una **segunda copia completa de Font Awesome, 1436 reglas sin capa**. La introdujimos y la cazó **el
gate que habíamos construido ese mismo día**. Corregida en `be11340`. Es el argumento a favor de los
gates: no valen por lo que documentan, valen por lo que atrapan.

**Regresiones abiertas de este bloque:** ninguna. Quedan declarados dos límites del gate espejo (no
ve assets inyectados por JS; el hueco noveno está escrito en su docblock) y el `fadeOut(5000)` de la
rama de error de servidor de Contratos, que es la misma clase de defecto que se decidió no importar.

### Bloque B — `/bi/*`: esto ya es F3, no F1 (`dce3fac`..`dc0e5f8`, 7 commits)

**Apunte para quien tome F3: este trabajo YA ESTÁ HECHO. No rehacerlo ni deshacerlo.**

Caso 9 del inventario de entregas sin capa: las 8 rutas `/bi/*` cargaban el **Play CDN de Tailwind**,
que no es una hoja sino un `<script>` que genera CSS en el navegador y lo inyecta **sin capa** —
132-135 reglas que vencían a todo el design system. Hoy las 8 rutas dan `unlayered: []`.

Qué se hizo: se retiró el CDN y se internalizaron **sólo las 97 utilidades** en `@layer utilities`
(`public/css/design-system/adapters/bi-utilities.css`), sin el preflight. Se borraron el config
inline muerto y los 4 bloques `!important` que repintaban la paleta clara. Se añadió un gate
anti-podredumbre. La superficie pasó a la escala tipográfica del DS.

**Tres premisas cayeron al medir, y conviene que consten:**

1. *(mía)* El `window.tailwind.config` inline no duplicaba los colores de marca: **estaba muerto**.
   Se declara antes de que cargue el CDN, y el CDN sobreescribe `window.tailwind`. Las 14 clases
   `*-aia-*`, con 38 usos, no pintaban nada. Nunca fue una segunda fuente de verdad.
2. *(del agente, declarada por él)* «Ninguna solución tiene que entregar el preflight» era falsa. El
   preflight **tapaba dos defectos reales del repo**: 13 `<button>` sin estilo de autor caían al
   estilo nativo del navegador, y Bootstrap se apoderaba de los encabezados desde `@layer vendor`.
3. Retirar el CDN a secas **rompe la aplicación**: `.hidden` (60 usos) es la API de `showView()`, no
   decoración. La página crece a 11.671 px y muestra todo lo oculto.

**Hallazgo transversal que excede a `/bi/*` y merece tarea propia: el design system fija peso y color
de `h1..h6` pero NO EL TAMAÑO.** En estas 8 rutas lo tapaba Tailwind por accidente; en el resto del
producto el hueco está a la intemperie.

**Verificación:** las **9** specs de BI que fallan se compararon contra `d451a78` —mismo mensaje,
misma línea, mismo locator— - **cero regresiones**. La #6 cubre «landscape tablet» y no se tocó, por
la prohibición de `AGENTS.md`. Audit **5785 → 5781**, estática **359/359**.

**Cerrada en `577ddf8`:** `lucide` se cargaba desde `unpkg` con `@latest`, sin pin ni SRI. Ya está
vendorizado en `public/vendor/lucide/` con la versión fijada en el querystring (1.27.0, lo que unpkg
resuelve hoy), replicando el patrón que `chart.js` seguía en ese mismo archivo.

**Corrección a lo que se supuso al detectarlo:** la consola avisaba `icon name was not found:
list-search`, pedido 4 veces en `control-tower.php`, y se atribuyó a una deriva de `@latest`. **Era
falso.** `list-search` **no existe en 1.27.0 ni en el `main` de lucide**: el nombre estuvo mal desde
el commit que lo introdujo y fallaba en silencio. Los 4 iconos rotos eran reales; su causa, no. El
riesgo de `@latest` sigue siendo riesgo, no daño consumado. Sustituidos por `list-filter`.
Verificado con unpkg bloqueado en las 8 rutas: 52 svg, cero sin resolver.

**Censo de CDN, medido:** **37 referencias en 15 archivos**, no las ~14 estimadas. Todas **pinneadas
por versión**; **ninguna con SRI** (`grep -rl 'integrity=' views/` sin resultados). Ninguna comparte
el riesgo de `lucide`: sin `@latest`, no hay rotura silenciosa por auto-actualización. Su riesgo
residual es otro —CDN comprometido sirviendo contenido alterado sin detección— y merece decisión
propia.

### Estado de `styles.css` al cierre de esta entrada

**4.382 líneas, 10 hex, 6 `rgba()`** — las 16 excepciones adjudicadas. De los 23 commits, sólo
`ad4a6e9` toca el archivo, y es una corrección de atribución en un comentario: el recuento de líneas
es idéntico antes y después. Desde ahí, ningún commit lo ha vuelto a tocar.

**Corrección a la tabla de métricas del plan:** decía **4.369** y el archivo tiene **4.382**. El
valor bueno es el medido; la tabla queda corregida en `plans/F1-styles-css.plan.md`.
