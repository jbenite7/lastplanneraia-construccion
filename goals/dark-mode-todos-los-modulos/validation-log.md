---
capa: fuente
tipo: evidencia
estado: vigente
fecha: 2026-07-29
areas: [proceso]
fuente: goals/dark-mode-todos-los-modulos/validation-log.md
resumen: Registro de verificaciones ejecutadas y de regresiones toleradas durante F1. Ninguna fase cierra con entradas abiertas.
---

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

---

## 2026-07-29 · F2 · Paso 1 del patrón: manifiestos de las superficies del agregador

**Entregado: 6 manifiestos de 7.** Falta `pdc`, deliberadamente fuera de esta tanda. Las otras
dos superficies que el spec contaba —`/listado-actividades` y `/contratos`— dejaron de existir
para el plan el mismo día: ver más abajo.

### Lo que cambió

- Seis manifiestos nuevos en `docs/design-system/manifests/`: `profesionales.json`,
  `subcontratistas.json`, `control-cambios.json`, `programa-general-actualizar.json`,
  `indicadores.json`, `escalamientos.json`.
- Seis goldens dark 1180×820 capturados contra el contenedor, en
  `tests/browser/__screenshots__/<moduleId>/`, con su sha256 en el escenario del manifiesto.
- `inventory.json`: los seis quedan en `manifests[]` y en `modules[]` con estado `pilot`.
- `tests/design-system/contracts.test.mjs:249`: el censo cerrado de manifiestos pasa de 7 a 13.

**No se tocó el agregador.** Ninguna vista cambió: `DesignSystemHeadComponent::render()` sigue
sirviendo las seis. La migración a `renderForModule()` es el paso 2 del patrón y va aparte.

### Corrección al spec F2: el paso 1 no se puede hacer «en seco»

El spec ordena manifiesto (1) primero y evidencia visual (6) al final. **No es posible.**
`module-manifest.schema.json` exige `scenarios` con `minItems: 1`, y cada escenario exige un
`golden` que exista en disco y un `sha256` que case con él (verificado en
`design-system-contracts.mjs:314-336` y `design-system-consumer-contract.mjs:90-98`). Un
manifiesto sin captura no es un manifiesto válido. Los pasos 1 y 6 son un solo paso.

**Decisión del usuario sobre dónde viven los goldens:** `tests/browser/__screenshots__/<moduleId>/`,
donde ya viven los de los siete manifiestos existentes, y no `evidence/F2/` como pedía el spec.
Un solo sitio, y el sha256 del manifiesto apunta al mismo archivo que sirve de evidencia.

**`consumerContract` se omite a propósito en los seis.** El contrato v1 exige que la vista no
tenga `<style>`, ni `style=`, ni hex, y que `exceptions[]` esté vacío — es exactamente el
estado al que llega la superficie *después* del paso 3. Declararlo hoy pondría los seis en
rojo el mismo día de nacer. Se añade al cerrar cada vista.

### Resuelto por decisión, no por arreglo: `/listado-actividades` y `/contratos` salen del plan

**Decisión del usuario, mismo día:** las dos páginas están **deprecadas** y se retiran del plan
entero. No reciben manifiesto, presupuesto ni evidencia. El grupo B del goal pasa de 9 superficies
a 7, y el criterio de cierre de F2 pasa de nueve manifiestos a siete.

Actualizado en consecuencia: `goal.md` (§Fuera de alcance), `specs/F2-superficies-agregador.md`
(cabecera, tabla, notas y criterio de cierre), `specs/F6-vendors.md` (T6.3.c retirado, T6.3.b y
T6.3.d corregidos) e `inventory.json` (las dos pasan a estado `deprecated`, que no es lo mismo
que pendiente).

**Retirada real abierta como goal propio:** `goals/retiro-listado-contratos/goal.md`, con el radio
de impacto medido y cuatro etapas. Resumen de por qué no cabía aquí: `SemiAutoService` (~3000
líneas) tiene ramas por módulo intercaladas y lo comparte `/pdc`; el RBAC está repartido por una
docena de arrays de roles; y `docs/pdc-v2.md:44` pone el apagado del modelo de familias en la fase
**C1**, que depende de que A+B estén validados en producción. Este goal es de capa visual y no
podía ejecutar nada de eso.

**Lo que NO se hizo, a propósito:** no se tocaron sus `pathBudgets` de `exceptions.json`. Las
rutas siguen servidas y sus archivos siguen vivos; aflojar un gate no es parte de deprecar. Y no
se corrigió el 500 descrito abajo — con las páginas fuera del plan deja de bloquear nada, pero
sigue siendo un 500 en producción hasta que alguien decida borrarlas de verdad.

**F6 hereda dos preguntas abiertas** (anotadas en su spec): T6.3 se queda sin el punto de partida
que tenía designado, y **retirar Select2 no se abarata** — deprecar una página no borra su código,
así que los 16 usos de Select2 de estas dos vistas seguirán impidiendo borrar `public/vendor/select2/`.

### El 500 que lo motivó, para el registro

No tienen manifiesto porque **hoy no renderizan**. Las dos rutas mueren a media página con
«Error Interno del Servidor».

Causa, medida en el log de Apache y no supuesta: `views/partials/shell_sidebar.php:88-110`
retiró del rail los ítems `listado-actividades` y `contratos` el 2026-07-29 (son la interfaz del
PDC viejo). Pero las dos vistas siguen pasando su `$shellActive`, y
`DesignSystemComponent.php:393` lanza `InvalidArgumentException: unknown active sidebar
destination` cuando el activo no está entre los ítems. El `http_response_code(500)` de
`public/index.php:386` ni siquiera llega a aplicarse — las cabeceras ya salieron, así que la
respuesta es un 200 con media página.

**Contradice su propio comentario:** el docblock de `shell_sidebar.php` dice que las rutas «siguen
servidas y accesibles escribiendo la dirección». No lo están. La guarda «nunca ocultar el módulo
en el que el usuario ya está» (línea 61) sólo cubre el ocultamiento por rol y área, no esta
retirada incondicional.

No se corrigió: es del frente de PDC V2 y la retirada fue deliberada. Queda como decisión
pendiente del usuario.

### Fuera de esta tanda por decisión del usuario

`/pdc` — se cruza con PDC V2, que está moviendo las fuentes que el manifiesto tendría que
declarar. Sigue `inventory-only` hasta que ese frente fije dónde viven.

### Hallazgos de las capturas, no corregidos

- **`/dashboard/escalamientos` se pinta en claro** con `data-aia-theme="dark"` aplicado. Su
  `<style>` embebido hardcodea una paleta OKLCH clara que gana a los tokens. Es la superficie
  con más distancia al dark de las nueve. El golden congela ese estado real, no el deseado.
- **Desbordamiento horizontal a 1180 px** en `/profesionales` y `/programa-general-actualizar`:
  el contenido arranca recortado por la izquierda. Persiste tras forzar `scrollTo(0,0)`, así que
  es de layout, no de scroll heredado de Handsontable.
- **El golden de `/indicadores` congela el iframe de Power BI cargando** (spinner). Es contenido
  de otro origen y no determinista; el manifiesto lo declara como excepción permanente
  `indicadores-powerbi-iframe`.

### Verificación

- **Comandos:** `node scripts/design-system-audit.mjs` · `node scripts/design-system-entrypoint-partition.mjs`
  · `node scripts/design-system-consumer-contract.mjs` · `npm run test:design-system:static`
- **Resultado:** audit **pasa contra baseline**; partición de entrypoint **PASS**; contratos de
  consumidor **PASS (1 manifiesto v1)**; estática **358/359**.
- **El único rojo** es `contracts.test.mjs:55` con `activation: worktree and index must be clean`
  (`design-system-activation-git.mjs:42`): exige árbol limpio, así que es imposible verlo verde
  con trabajo sin commitear. Ya estaba rojo al abrir la sesión por un archivo sin seguimiento en
  `docs/superpowers/specs/`. No es regresión de este cambio.
- **Navegador:** las ocho rutas recorridas autenticadas contra `localhost:8081` a 1180×820 dark,
  con `data-aia-theme=dark` confirmado en las ocho.

---

## 2026-07-29 · F2 · Pasos 2-4 del patrón: las cinco superficies restantes

**Entregado: las cinco.** `escalamientos`, `profesionales`, `subcontratistas`, `control-cambios` e
`indicadores` migraron su head, vaciaron su vista y declararon presupuesto cero. Con
`programa-general-actualizar`, ya cerrada, son seis de las siete de F2; sigue faltando `pdc`,
fuera de esta tanda por la misma razón de siempre.

### Lo que cambió, por superficie

| Superficie | Head | Vista | Hoja del módulo |
|---|---|---|---|
| `escalamientos` | `renderForModule` | −1 `<style>` (33 `rgba()`, 7 radios), −3 `style=` | `public/css/escalamientos.css` (nueva) |
| `profesionales` | `renderForModule` | −1 `<style>`, −todos los `style=` | `public/css/profesionales.css` (nueva) |
| `subcontratistas` | `renderForModule` | −1 `<style>`, −todos los `style=`, −CDN de Handsontable | `public/css/subcontratistas.css` (nueva) |
| `control-cambios` | `renderForModule` | −1 `<style>` vacío, −8 `style="resize: none"` | `public/css/control-cambios.css` (existente) |
| `indicadores` | `renderForModule` | −4 `style=` (2 de markup, 2 de plantillas JS) | `public/css/indicadores.css` (nueva) |

Cinco entradas nuevas en `pathBudgets` de `exceptions.json`, con cero en las seis reglas duras.
`inventory.sharedHeadConsumers` baja de 10 a **5**, y `foundation.test.mjs` con ella.

**`escalamientos` dejó de pintarse en claro.** Su `<style>` embebido hardcodeaba una paleta OKLCH
clara que, al entrar sin capa, ganaba a todos los tokens. Verificado tras el cambio: `data-aia-theme=dark`
y `body background: rgb(17, 26, 21)`.

**`profesionales` dejó de desbordar horizontalmente.** Causa confirmada, idéntica a la de
`programa-general-actualizar`: el `padding: 0` sobre `html, body` del bloque embebido le ganaba al
`padding-inline-start` con que el shell reserva el rail. `scrollWidth - clientWidth = 0` en las cinco.

### Decisión del usuario: la vista de tarjetas móvil se retira

`profesionales` y `subcontratistas` traían un `#mobile-card-view` con su renderer, sus helpers y
parches al navbar legacy —que ya no se monta, desde que ambas usan el shell sidebar—. Era la mitad
del `<style>` y casi todos los `style=`, y no se podía tokenizar sin diseñar para móvil, que
`AGENTS.md` prohíbe. **El usuario autorizó retirarla entera**, por ser código que a 1180 px nunca se
muestra. Se borró el contenedor, `renderMobileCards`, `updateMobileRow`, `createMobileRow`,
`deleteMobileRow`, `addMobileSubcontratista` y `buildCargoOptionsHtml`, más las media queries de
móvil/tablet del bloque embebido. Es lo único que permitió el presupuesto cero real en las dos.

### Verificado, contra la hipótesis del spec: el bloque de neutralización SÍ era innecesario

El spec pedía comprobarlo antes de reescribirlo. Retirado por completo de `profesionales`, la grilla
renderiza igual y el `wtHider` ocupa el ancho del contenedor (1061 de 1062 px a 1180×820). Sus reglas
apuntaban a `mobile-table-fix.js`, que ya no existe; el par fondo/tinta de celda, la rejilla y
`.status-active`/`.status-inactive` los declara `handsontable-module.css` desde `layer(vendor)`; y los
propios comentarios de la vista daban por INERTES la mitad de las demás.

### El fallo que el head segmentado destapó: el drawer vive en la hoja equivocada

Al pasar `escalamientos` a `renderForModule`, el Cajón Contextual LPS perdió su `position: fixed` y
cayó al flujo del documento, debajo del tablero. Causa medida: **toda la geometría de `.lps-drawer` y
su overlay vive dentro de `public/css/handsontable-module.css`** (líneas 377+), que el agregador
importaba y `core.css` no. La vista no monta ninguna grilla —emula Handsontable con un objeto dummy—,
así que declara el vendor `handsontable` sólo para recuperar esa hoja, con la excepción
`escalamientos-drawer-en-hoja-de-handsontable` en su manifiesto explicándolo.

**La deuda real es la ubicación de esas reglas, no la declaración:** pertenecen a
`adapters/lps-drawer.css`, que ya está en `core.css`. Mover ~250 líneas entre capas
(`vendor` → `components`) altera la cascada de todas las vistas que hoy cargan ambas hojas y excede
F2. Queda abierto.

### Entrega sin capa cerrada de paso

`subcontratistas` servía Handsontable por la CDN de jsdelivr (`@14.6.1`), **la misma versión que ya
está vendorizada** en `public/vendor/handsontable/`. Se cambió CSS y JS al vendor local, y con el head
segmentado la hoja llega por `attach-handsontable.css` en `layer(vendor)`: la entrega sin capa
desaparece. Retirada su entrada de `unlayered-delivery-inventory.json` y anotado en el caso
`2-handsontable-doble-carga`, que sigue ABIERTO para las demás vistas —`/profesionales` incluida, que
conserva su `<link>` crudo al vendor local—.

### Regresiones abiertas y límites

- **La grilla de `profesionales` y `subcontratistas` no ocupa todo el ancho del contenedor.** Medido:
  contenedor 1062 px, `wtHider` 1061, pero el `table.htCore` del master 631. **Es preexistente y ajeno
  a este cambio**: `subcontratistas` con su bloque `<style>` intacto exhibía el mismo patrón (1134 /
  1134 / 827). Antes lo tapaba el desbordamiento horizontal. Se intentaron dos arreglos —reinyectar
  `colWidths` por `updateSettings` tras el primer render, y forzar `width: 100%` + `table-layout:
  fixed` sobre `table.htCore`— y **ninguno cambió nada**; ambos revertidos para no dejar código que no
  arregla. Es virtualización de Handsontable y merece diagnóstico propio.
- **`/dashboard/escalamientos` lanza `hot.addHook is not a function`** en consola al iniciar el
  drawer: su `dummyHot` no implementa `addHook`. Preexistente, ningún cambio de esta tanda toca ese
  JS. Es el único ruido de consola de las cinco.
- **Las tarjetas de crisis de `escalamientos` no son alcanzables por teclado**: son `<div onclick>`
  sin `tabindex` ni `role`. Añadir foco sin activación por teclado empeora la situación, así que no se
  tocó. Necesita convertirlas en botones, que es cambio de markup y de alcance.
- **`escalamientos` pasa a cuatro columnas fijas.** Su tablero era mobile-first y sólo llegaba a
  cuatro desde `min-width: 1200px`, de modo que en el viewport canónico (1180) mostraba **dos**. Las
  media queries se retiraron y el tablero declara su forma real. Es un cambio visible a 1180, y va
  dicho aquí a propósito.
- **`consumerContract: "v1"` sigue sin declararse en las seis.** Las vistas ya están limpias, pero
  `escalamientos` tiene `exceptions[]` no vacío y v1 lo prohíbe; declararlo en las otras cinco es una
  decisión de contrato que merece su propia pasada.

### Verificación

- **Comandos:** `node scripts/design-system-audit.mjs` · `node scripts/design-system-entrypoint-partition.mjs`
  · `node scripts/design-system-consumer-contract.mjs` · `npm run test:design-system:static`
- **Resultado:** audit **pasa contra baseline**; partición **PASS**; contrato de consumidor **PASS
  (1 manifiesto v1)**; entregas sin capa **PASS (17 declaradas)**; estática **358/359**.
- **El único rojo** vuelve a ser `contracts.test.mjs:55` con `activation: worktree and index must be
  clean`: exige árbol limpio y es imposible verlo verde con trabajo sin commitear. No es regresión.
- **Dos gates atraparon errores míos, y conviene que conste:** `state-token-pairing.test.mjs` rechazó
  un uso invertido del par `critical` (fondo con el token de texto) en el botón de borrado de
  `subcontratistas`; y `design-system-unlayered-delivery.mjs` detectó la entrada obsoleta del
  inventario en cuanto retiré la CDN.
- **Navegador:** las cinco rutas recorridas autenticadas contra `localhost:8081` a 1180×820 dark,
  sobre la ruta afectada. En las cinco se comprobó en `document.styleSheets` que la lista de hojas es
  la reducida y **el agregador no aparece** —la única prueba de que `renderForModule` no degradó—,
  más `data-aia-theme=dark`, cero desbordamiento horizontal, red sin 404 y consola limpia salvo el
  `hot.addHook` anotado arriba.

### Goldens recapturados

Los seis del paso 1 retrataban el estado ANTERIOR. Se recapturaron los cinco de esta tanda a 1180×820
dark contra el contenedor y se actualizó su `sha256` en el manifiesto. **No es una regeneración para
forzar verde:** el gate visual del laboratorio no consume estos goldens, y su cambio es exactamente el
efecto buscado (dark real en `escalamientos`, layout sin recorte en `profesionales`).

| Superficie | sha256 anterior → nuevo |
|---|---|
| `escalamientos` | `8a352eaa…` → `b71ba043…` |
| `profesionales` | `cf7bf667…` → `c0318ee3…` |
| `subcontratistas` | `bd84eae8…` → `1e3c6fab…` |
| `control-cambios` | `84136b41…` → `f493f453…` |
| `indicadores` | `d50cdc9f…` → `29d308f6…` |

El de `indicadores` sigue congelando el iframe de Power BI cargando: contenido de otro origen, no
determinista, declarado como excepción permanente `indicadores-powerbi-iframe`.

---

## 2026-07-29 · F2 · Arreglado: la grilla no ocupaba el ancho del contenedor

Quedaba anotado como regresión abierta en la entrada anterior, con dos intentos fallidos y la
conclusión de que era «virtualización de Handsontable». **Esa conclusión era incorrecta.** La causa
real, medida:

### Causa raíz

`public/css/handsontable-module.css:127-131` fuerza `table-layout: auto !important` sobre
`#hot-container table` desde `layer(vendor)`, **anulando el `table-layout: fixed` que el propio
vendor declara** en `.handsontable table.htCore`. Con `auto`, el navegador trata los `<col width>`
del colgroup como sugerencias y colapsa la tabla al ancho de su contenido. El `width: 100%
!important` de esa misma regla no lo compensa: el padre directo de la tabla es `.wtSpreader`, que
Handsontable mantiene a `width: 0`, así que el 100% resuelve a cero.

Por eso el `wtHider` medía lo correcto (1061 px) y la tabla de dentro no (631).

**Por qué mis dos intentos anteriores fallaron, y por qué eso mismo era la pista:** ninguno podía
funcionar. Para declaraciones `!important` el orden de capas se **invierte**, así que
`layer(vendor)` gana a `layer(module)` y a cualquier hoja sin capa. La regla
`table-layout: fixed !important` que el bloque `<style>` embebido traía tampoco alcanzaba —era
inerte, como sus propios comentarios sospechaban de otras—. **El estrechamiento era de origen y
llevaba ahí desde siempre**, no lo introdujo F2: lo tapaba el desbordamiento horizontal.

### El escape ya existía en el repo

`handsontable-module.css:134-138` devuelve `table-layout: fixed !important` y el ancho de
`--hot-table-width` cuando `#hot-container` lleva la clase `hot-fixed-columns`. **Cinco módulos ya
lo aplican** (programa-general, programacion-intermedia, programacion-semanal, listado-actividades,
contratos). Los tres que no lo hacían —`profesionales`, `subcontratistas`,
`programa-general-actualizar`— eran exactamente los tres que se veían estrechos.

### Lo que se hizo

- **`public/js/modules/aia_ui/hot_table_width.js`** (nuevo): `window.AIA.sincronizarAnchoTabla()`
  aplica la clase y publica `--hot-table-width` con la suma real del colgroup que Handsontable acaba
  de escribir. Lee la cifra medida en vez de reconstruirla desde las constantes de cada vista, que
  es lo que hacen los otros cinco. Idempotente.
- Invocado desde las tres superficies, **antes** del render final: al fijar el ancho cambian los
  saltos de línea y con ellos la altura de las filas.
- **`handsontable-module.css:134`**: el selector pasa de `.handsontable table.htCore` a
  `.ht_master table.htCore, .ht_clone_top table.htCore`. Ver abajo.

### Un efecto colateral, encontrado y corregido

Al activar `hot-fixed-columns` en `/profesionales` **desaparecieron los encabezados y los números de
fila**. La regla alcanzaba a los cuatro clones, incluidos `ht_clone_left` y
`ht_clone_top_left_corner`, que cubren sólo las columnas congeladas: con `rowHeaders: true` tienen
una única columna, y forzarle el ancho total la estiraba de 50 a 1061 px hasta tapar la grilla.

No se había notado nunca porque **las cinco vistas que ya activaban la clase tienen el clon
izquierdo vacío** — verificado una por una antes de tocar el selector compartido, para medir el
radio del cambio. El arreglo del selector no altera ninguna de las cinco, y sus anchos siguen
idénticos (1020 y 1290 px).

### Verificación

- **Test nuevo:** `tests/browser/handsontable-ancho-tabla.mjs` compara `table.htCore` contra su
  `wtHider` en las cinco rutas con grilla. Escrito **antes** del arreglo: **2/5 en rojo**
  (profesionales 631/1061, subcontratistas 828/1059, programa-general-actualizar 361/989). Tras el
  arreglo, **5/5 con desfase 0 px**. Añadido a la allowlist de `.gitignore`.
- **Gates:** audit pasa contra baseline; partición PASS; contrato de consumidor PASS; entregas sin
  capa PASS; estática **358/359** con el único rojo esperado (árbol sucio).
- **Navegador:** las tres rutas a 1180×820 dark, `overflowX = 0`, sin errores de página ni 404.
  Capturas de control de `/programa-general` y `/programacion-intermedia` revisadas: sin cambios.
- **Goldens** de las tres superficies recapturados y su `sha256` actualizado.

### Sigue abierto: el clon de números de fila desalinea

`ht_clone_left` dibuja sus filas más altas que las del master —en `/profesionales`, +16 px
constantes por fila—, así que los números no coinciden con sus filas.

**No lo introdujo este arreglo:** medido con el helper neutralizado y con él activo, las alturas de
`/profesionales` son idénticas en ambos casos (`master [60,41,59,41…]` vs `left [76,57,75,57…]`).
Tampoco es CSS: tipografía, `line-height`, `padding` y bordes son idénticos en las celdas de ambos
(`13px / 21px / 8px 12px / 1px`). Es Handsontable dibujando el clon con alturas propias. En
`/subcontratistas` el desajuste **se hace más visible** tras el arreglo, porque al ensanchar la tabla
el master deja de envolver texto y sus filas encogen mientras el clon conserva las suyas.

Se detuvo la investigación aquí a propósito: son dos hipótesis descartadas con medición, el defecto
es preexistente y del vendor, y excede lo que este arreglo perseguía. Merece diagnóstico propio.

---

## 2026-07-29 · F2 · Alineación del clon de encabezados de fila — a la raíz

Continuación del arreglo de ancho. Quedaba anotado que el clon desalineaba y que era «defecto propio
del vendor». **También esa conclusión era incorrecta: es CSS del design system.**

### Causa raíz

Handsontable calcula la altura de cada fila y la escribe como `height` **inline** en las celdas de
sus clones. Ese número no contempla el relleno vertical que
`public/css/design-system/adapters/handsontable.css` añadía a todas las celdas
(`padding: var(--ds-space-2) var(--ds-space-3) !important`, desde `@layer reset`). El master crecía
con el relleno; los clones, que llevan la altura fija, no. Resultado: **16 px de desvío por fila,
acumulativos** — 111 px a la octava fila de `/profesionales`, medido a 1180×820.

Diagnosticado con `CSS.getMatchedStylesForNode` por CDP, que señaló la regla exacta y su capa. Los
`8px 12px` computados eran literalmente `--ds-space-2` y `--ds-space-3`.

**Por qué ganaba a todo:** `@layer reset` es la capa más temprana, y para declaraciones `!important`
el orden de capas se **invierte**. Vencía al `padding: 0 !important` que `handsontable-module.css`
declara desde `layer(vendor)`.

### La corrección

**La densidad de una grilla no se expresa con relleno de celda**, porque el vendor mide la altura de
fila y no lo ve. El adaptador pasa a declarar sólo relleno horizontal (`padding-block: 0` +
`padding-inline: var(--ds-space-3)`), que no participa del cálculo de altura. Alinea los cuatro
clones por construcción, y de paso compacta la grilla —la dirección que `DESIGN.md` pide para
superficies de datos densas—.

### Resultado, medido a 1180×820

| Ruta | Desvío antes | Después | Cabecera antes → después |
|---|---|---|---|
| `/programa-general` | 0 px | **0 px** | 48 → 32 px (una fila visible más: 5 → 6) |
| `/programacion-intermedia` | 0 px | **0 px** | 162 → 146 px |
| `/profesionales` | **111 px** | **1 px** | 56 → 24 px |
| `/subcontratistas` | 35 px | **13 px** | 32 → 22 px |
| `/programa-general-actualizar` | 0 px | **0 px** | 207 → 175 px |

Capturas antes/después de las cinco revisadas: ninguna empeora, todas ganan densidad sin perder
legibilidad. Los 14 números de fila de `/profesionales` quedan exactamente sobre sus filas.

### Cinco hipótesis descartadas con medición, para que nadie las repita

1. **Acotar el relleno al cuerpo del clon** (`tbody th`): bajó de 111 a 17 px, pero la cabecera del
   clon quedaba 16 px descuadrada por la misma razón. Insuficiente.
2. **Retirar `box-sizing: content-box` de `.ht_clone_left`** (`handsontable-module.css:197`), para
   igualar el modelo de caja de los cuatro clones: **empeora** —18 px frente a 13—. Probado y
   revertido; la regla se queda, con nota.
3. **`hot.refreshDimensions()` al final del render:** sin efecto.
4. **`recalculateAllRowsHeight()` en un `requestAnimationFrame` posterior**, por si medía el ancho
   antes del reflujo: sin efecto. Revertido.
5. **Antes de todo esto**, se había descartado que fuera tipografía: `font-size`, `line-height`,
   `padding` y bordes son idénticos en las celdas del master y del clon.

### Sigue abierto: 13 px en `/subcontratistas`

Handsontable escribe en el clon alturas que no corresponden a las del master **en esa vista**: 28, 42
y 21 px para filas que renderizan a 44, 43 y 29. Sólo la segunda coincide. Es su `autoRowSize`
midiendo mal con `wordWrap` y contenido que envuelve; ya no es CSS ni orden de llamadas.

Se detuvo aquí a propósito, tras cinco hipótesis descartadas: el skill de depuración marca ese punto
como señal de que el problema es de otra naturaleza. Visualmente los tres números caen ya casi sobre
sus filas.

### Verificación

- **`tests/browser/handsontable-ancho-tabla.mjs`** amplía su aserción a la alineación del clon.
  **Se deja deliberadamente ROJO en `/subcontratistas`** (umbral 2 px, desvío 13): `AGENTS.md`
  prohíbe adaptar una prueba para ocultar un defecto, y un rojo que documenta un defecto real vale
  más que un umbral inflado. Las otras cuatro pasan.
- **Gates:** audit pasa contra baseline; contrato de consumidor PASS; entregas sin capa PASS.
- **Goldens** de las tres superficies recapturados y su `sha256` actualizado.

### Aviso: dos rojos de la suite NO son de este trabajo

Al cerrar, `npm run test:design-system:static` falla por trabajo de otras dos sesiones activas en
este mismo worktree:

- `inventory: missing manifest bi-runtime.json` — `views/bi/_layout.php` ya está migrado a
  `renderForModule('bi-runtime')` pero su manifiesto aún no existe (F3/T3.1 en curso).
- `state-token-pairing`: tres usos descompensados en
  `public/css/design-system/adapters/admin-lte.css` (F4 en curso).

Ninguno se tocó.

### Pendiente de decisión: dos goldens quedan retratando el estado anterior

El cambio del adaptador es compartido, así que `/programa-general` y `/programacion-intermedia`
también se ven más compactas. **Sus goldens NO se recapturaron**: `DESIGN.md` prohíbe tocar Programa
General y sus archivos desde la migración de otra superficie, y regenerar una baseline ajena exige
aprobación explícita. Sus `sha256` siguen casando porque los PNG no se tocaron, pero ya no
corresponden a lo que se sirve. Requiere decisión.

---

## 2026-07-29 · F6 · T6.1 y T6.2: las dos islas de color sin tokens

`change-monitor.css` y `tom-select-premium-aia.css` no usaban una sola variable del sistema (23 y
16 hex, cero `var(--ds-*)`). Quedan tokenizadas, cada una en su capa, y la primera entra ya por el
head canónico. **T6.3 no se tocó**, por decisión del usuario.

### `change-monitor.css` — T6.1

- Pasa a `@layer module`, junto a `styles.css` y a las hojas de superficie de F2, y declara el orden
  canónico de capas antes de abrirla. Entraba **sin capa**, que gana a todas las capas en
  declaraciones normales: por eso su paleta clara derrotaba al design system con el documento en
  dark.
- `programacion_semanal.view.php:42` la enlaza con `DesignSystemHeadComponent::renderStylesheet`, no
  con un `<link>` de `?v=` escrito a pulso. Verificado servido: `?v=1785349991` (mtime).
- Retiradas sus **dos** entradas de `unlayered-delivery-inventory.json` —la estática y la de runtime
  de `/programacion-semanal`—: dejarlas habría hecho fallar el gate por `stale-inventory-entry`.
- Los acentos ámbar (indicador de filtro, badge de restricciones) y el rojo del icono «solo
  restricciones» se derivan en OKLCH desde `--ds-color-state-warning-text` /
  `--ds-color-state-critical-text` preservando su matiz. El par `--ds-color-state-*` es claro y como
  tinta sobre penumbra no se leería. Mismo patrón que `escalamientos.css` y
  `programa-general-actualizar.css`. **No se inventó ningún color.**
- Los custom properties se declaran **en la regla que los usa**, no en `:root`: un selector global
  desde una hoja de módulo dispara `global-module-selector`, y los modales de SweetAlert2 cuelgan de
  `<body>`, fuera de cualquier contenedor de la vista.
- Añadido foco visible al toggle «Solo restricciones», que era un `<label>` clicable sin ninguno.

### `tom-select-premium-aia.css` — T6.2

- Pasa de `@layer components` a **`@layer vendor`**, que es donde entran los demás skins de librería,
  y declara el orden canónico. Antes abría capas sin fijar el orden y se lo imponía a quien la
  cargara.
- Colores contra `--ds-active-domain-construction` (el naranja de dominio en su variante on-dark),
  `--ds-state-tint-orange`, `--ds-active-bg-canvas`, `--ds-active-text-primary` y
  `--ds-active-border`. Radios, tipografía, espaciado, sombras, motion y `z-index` a la escala
  semántica. Los `-1px` de solape pasan a `calc(var(--ds-border-width) * -1)`.

#### Hallazgo: tres bloques de reglas llevaban tiempo sin casar con nada

Medido en `/programa-general-actualizar` con Tom Select montado de verdad: la librería marca cada
opción como **`div.option`**, no `.ts-option`, y la de crear como `div.create`, no `.ts-create-option`.
Los tres bloques del skin eran **selectores muertos**, y la consecuencia en dark era visible: las
opciones conservaban el skin claro de `tom-select.bootstrap4.min.css` y la opción activa se pintaba
**blanca sobre desplegable oscuro**. Se añaden las clases reales conservando las antiguas.

Dos correcciones de contraste al hacerlo, medidas y no estimadas: el naranja de dominio sobre
`--ds-state-tint-orange` da **3.6:1**, por debajo del piso 4.5:1, y lo llevaban el texto del chip
(0.75 rem) y el de la opción activa. En ambos la tinta pasa a `--ds-active-text-primary`; el naranja
se queda en el borde y en el tinte, que son decorativos.

#### Límite conocido, dicho a propósito

El botón «Limpiar selección» mantiene `--ds-active-domain-construction` sobre
`--ds-active-surface-raised`: **3.7:1**. Es el uso que el propio token declara para on-dark, así que
no se corrigió aquí — subirlo es una decisión del sistema de tokens, no de este skin.

### Presupuestos de ruta

Dos entradas nuevas en `pathBudgets` de `exceptions.json`, con cero en las seis reglas duras:
`change-monitor` (hoja + `programacion_semanal.view.php`) y `tom-select-skin` (la hoja). Ambas en
cero real, no por excepción.

### De paso

`programaGeneralActualizar.view.php` enlazaba `tom-select-premium-aia.css` **dos veces** (líneas 22
y 25). Retirado el duplicado; verificado en el navegador que ahora aparece una sola vez en
`document.styleSheets`.

### Verificación

- **Comandos:** `node scripts/design-system-audit.mjs` · `…-entrypoint-partition.mjs` ·
  `…-consumer-contract.mjs` · `…-unlayered-delivery.mjs` · `npm run test:design-system:static`.
- **Resultado:** audit **pasa contra baseline** (total 5483 → 5481); partición **PASS**; contrato de
  consumidor **PASS (1 manifiesto v1)**; entregas sin capa **PASS (17 → 16 declaradas)**; estática
  **357/359**.
- **Los dos rojos no son de esta tanda.** Uno es el de siempre, `contracts.test.mjs` con
  `activation: worktree and index must be clean`, imposible de ver verde con trabajo sin commitear.
  El otro es `state-token-pairing.test.mjs` señalando tres usos descompensados en
  `public/css/design-system/adapters/admin-lte.css`, un archivo **sin versionar de otra sesión** que
  no toca este trabajo.
- **Navegador:** `/programacion-semanal` y `/programa-general-actualizar` autenticadas contra
  `localhost:8081`, a 1180×820 dark. En las dos: `data-aia-theme=dark`, `body` en `rgb(17, 26, 21)`,
  `scrollWidth - clientWidth = 0`, **consola sin errores** y **red sin respuestas ≥ 400**.
  `change-monitor.css` ya no figura entre las hojas sin capa de `/programacion-semanal`.
- **Modal real del Change Monitor** abierto con `window.ChangeMonitor.openModal()`: cabecera, toggle,
  badge, indicador y footer coherentes con el tema; foco del toggle con anillo visible
  (`2px solid rgb(44, 170, 159)`); filtro activado y re-renderizado sin ruido.
- **Tom Select real** montado en `/programa-general-actualizar` con el `TomSelect` de la página
  dentro de un `.htTomSelectWrapper`, para leer el DOM que produce la librería y no una imitación.

### Queda abierto

- **No se pudo abrir el editor Tom Select desde una celda de Handsontable**: la grilla de
  `/programa-general-actualizar` tiene **cero filas** en Da Porto, el único proyecto sembrado, y el
  skin sólo aplica dentro de `.htTomSelectWrapper`. La verificación se hizo montando la librería de
  verdad sobre la página servida; falta el paso por el ciclo de vida del editor de la grilla, que
  pide un proyecto con cronograma cargado.
- `listado-actividades` sigue enlazando el skin con `?v=` a pulso. Es vista **deprecada** y fuera del
  plan del goal, así que no se tocó.
- `handsontable-module.css` —que el spec numera como T6.2— **sigue con sus 54 hex y 35 `rgba()`**.
  Esta tanda ejecutó lo que pidió el usuario: `change-monitor.css` y `tom-select-premium-aia.css`.
  La numeración del spec y el alcance real de la sesión no coinciden, y conviene reconciliarlos antes
  de dar F6 por cerrado.

---

## 2026-07-29 · F3 · T3.1: head canónico de las ocho rutas `/bi/*`

**Entregado.** `views/bi/_layout.php` pasa de `renderStylesheet` crudo (tokens + agregador) a
`DesignSystemHeadComponent::renderForModule('bi-runtime')`. `bi-runtime` deja de ser
`deferred-last` y entra como `pilot` con manifiesto propio, presupuesto de ruta y tres goldens.
T3.2 (Tailwind) y T3.3 (lucide vendorizado) ya estaban hechas y no se tocaron.

### Lo que cambió

| Archivo | Cambio |
|---|---|
| `views/bi/_layout.php` | `renderForModule('bi-runtime')`; retirado el `<link>` crudo a `access.css` (ya viaja en `core.css`, `@layer utilities`) |
| `docs/design-system/manifests/bi-runtime.json` | nuevo: 8 rutas, 3 escenarios con golden y sha256 |
| `docs/design-system/manifests/inventory.json` | `deferred-last` → `pilot`, y `bi-runtime.json` añadido a `manifests[]` |
| `docs/design-system/exceptions.json` | dos `pathBudgets` nuevos y una nota |
| `public/css/bi-control-tower.css` | la caja a pantalla completa, que venía prestada (ver abajo) |
| `src/View/Components/DesignSystemHeadComponent.php` | nueva categoría `SCRIPT_ONLY_VENDORS` |
| `scripts/design-system-entrypoint-partition.mjs` | candado de esa categoría |
| `tests/design-system/contracts.test.mjs` | censo `deepEqual` de `inventory.manifests`: 13 → 14 |

`sharedHeadConsumers` **no cambia**: sigue en 5 y `views/bi/_layout.php` nunca estuvo en la lista,
así que `foundation.test.mjs` no se toca.

### `chartjs` no cabía en ninguna categoría del registro, y el silencio se paga caro

El gate de infra-declaración exige que el manifiesto declare `chartjs` (la vista carga
`/vendor/chart.js/chart.umd.min.js`), pero `moduleVendors()` en PHP sólo conoce `CORE_VENDORS`,
`VIEW_OWNED_VENDORS` y `VENDOR_ATTACHMENTS`: un vendor fuera de las tres **degrada al agregador con
un `error_log` y nada más**. Meterlo en `CORE_VENDORS` sería mentir sobre lo que importa `core.css`,
y `VIEW_OWNED_VENDORS` exige que alguna vista enlace su hoja —Chart.js pinta en Canvas y no tiene
una sola regla en `public/css/`—.

Se añadió `SCRIPT_ONLY_VENDORS = ['chartjs']`: vendors sin CSS, de los que el head emite exactamente
nada. Con candado verificable en el gate de partición, como el que F2 puso a `VIEW_OWNED_VENDORS`:
ningún miembro puede tener adjunto ni asset `.css` en `vendors.json`, y alguna vista debe cargar su
script.

### La trampa del adjunto ausente, otra vez: BI vivía de una hoja de Handsontable

Igual que el drawer de `escalamientos`. **La caja a pantalla completa de `/bi/*` la daba
`html, body { height: 100%; overflow: hidden; display: flex }` de
`public/css/handsontable-module.css`**, que sólo llegaba por el agregador. BI no monta ninguna
grilla. Medido tras el cambio: el shell colapsaba a la altura del contenido y el pie quedaba a media
página, con el lienzo vacío debajo. La geometría se declara ahora en `bi-control-tower.css`, en
`@layer components` y acotada a `.bi-control-tower-page` — que es donde pertenece, en vez de
declarar un vendor que la vista no usa.

**Efecto lateral visible y deliberado:** con la hoja del vendor se colaban también las variables
`--lps-rail-*`, que dejaban una franja muerta de unos 46 px a la derecha del shell. Ahora el
contenido llega al borde del viewport a 1180 px. Es un cambio visible y va dicho a propósito.

### La excepción de los charts es permanente, no deuda

`bi_chart_theme.js` conserva **15 hex y 3 `rgba()`** — `SERIES_FALLBACKS` y `TEXT_FALLBACKS`. El
camino vivo ya lee tokens (`getComputedStyle` sobre `--ds-active-*` y las seis series); el hex sólo
entra si el token no resuelve, caso en que Chart.js pintaría transparente. Van en un `pathBudget`
propio (`bi-chart-theme-fallbacks`) que los **congela** en su conteo auditado, con la razón escrita
en `notes` y en `exceptions[]` del manifiesto. La superficie (`bi-runtime`) queda en **cero en las
seis reglas duras**. Nota de mecánica: `exceptions[]` de `exceptions.json` exige `selector` no vacío
y las violaciones `hardcoded-hex` no lo llevan, así que documenta pero no suprime; quien pone el
techo es el presupuesto.

### Verificación

- **Comandos:** `node scripts/design-system-audit.mjs` · `node scripts/design-system-entrypoint-partition.mjs`
  · `node scripts/design-system-consumer-contract.mjs` · `node scripts/design-system-unlayered-delivery.mjs`
  · `npm run test:design-system:static` · `npx playwright test tests/browser/bi_control_tower.spec.mjs tests/browser/bi_control_tower_access.spec.mjs --workers=1`
- **Resultado:** audit **pasa contra baseline**; partición **PASS**; contrato de consumidor **PASS
  (1 manifiesto v1)**; entregas sin capa **PASS (16 declaradas, una menos: se fue el `<link>` crudo
  a `access.css`)**; estática **357/359**; Playwright **39 pasan, 9 fallan**, el mismo número de
  rojos preexistentes que traía BI antes de esta tanda —entre ellos el de *landscape tablet*, que
  `AGENTS.md` prohíbe tocar—.
- **Los dos rojos de la estática, ninguno de esta tanda:**
  - `contracts.test.mjs` con `activation: worktree and index must be clean` — el de siempre, imposible
    de ver verde sin commitear. Comprobado aparte que el resto del gate pasa: se copió el árbol a un
    repo temporal con estos cambios commiteados y las únicas quejas restantes son los `sourceRef`
    del propio repo de prueba. Ahí se destapó que `inventory.manifests[]` también hay que declararlo
    (no basta con cambiar el estado del módulo), y se corrigió.
  - `state-token-pairing.test.mjs` por tres usos sin declarar en
    `public/css/design-system/adapters/admin-lte.css`, **archivo sin versionar de otra sesión** (F6).
    Lo de esta tanda en ese inventario sí quedó bien: las dos anclas de `bi-control-tower.css`
    apuntaban a líneas equivocadas —ya lo estaban antes— y ahora apuntan a la línea real del token.
- **Navegador**, autenticado contra `localhost:8081`, 1180×820 dark, **las ocho rutas `/bi/*`**:
  en las ocho, `document.styleSheets` da la lista reducida (`core.css`, `tokens.css`,
  `bi-utilities.css`, `bi-control-tower.css`, `bi-filter-drawer.css`) y **el agregador no aparece**;
  `data-aia-theme=dark`, fondo `rgb(11, 16, 13)`, cero desbordamiento horizontal, **consola limpia**
  y **cero respuestas ≥400**. Foco del disparador de filtros con anillo visible
  (`2px solid rgb(44, 170, 159)`). Charts pintados y cajón de filtros operativo.
- **Goldens** (`tests/browser/__screenshots__/bi-runtime/`): `control-tower` con el cajón abierto,
  `curva-s` y `semanal` con sus charts. Capturados contra el contenedor; no se reconcilió ninguna
  baseline previa porque el módulo no tenía.

### Queda abierto

- **`lucide` no está en `docs/design-system/vendors.json`.** La vista carga
  `/vendor/lucide/lucide.min.js?v=1.27.0` (vendorizado y con pin por T3.3), pero al no tener entrada
  en el catálogo tampoco tiene huella, así que ningún gate lo ve y el manifiesto no lo declara.
  Registrarlo arrastra decidir su categoría (`SCRIPT_ONLY_VENDORS`, previsiblemente) y toca el
  catálogo compartido; excede T3.1.
- **`consumerContract: "v1"` sigue sin declararse.** `bi-runtime` tiene `exceptions[]` no vacío, que
  v1 prohíbe; y en todo caso es la misma decisión de contrato aparte que dejó abierta F2.
- **El markup de BI conserva nombres de utilidad de estilo Tailwind** (`flex`, `text-xl`,
  `text-gray-800`…). Los sirve `bi-utilities.css` desde `@layer utilities` con tokens, así que no hay
  deuda de color, pero el vocabulario del markup sigue sin ser el de las primitivas `aia-*`. Es
  cosmética de nombres y no bloquea nada.
- **Los avisos del hook de Impeccable sobre `bi-control-tower.css`** (18 `font-size` fuera de la
  rampa) son **preexistentes**: ninguna de las líneas añadidas aquí declara tipografía.

## F4 · Panel admin — 2026-07-29

### Alcance ejercido

Se respetó la decisión vinculante: **AdminLTE permanece como framework de `admin/`**. No se
reescribió ninguna de las 14 vistas sobre el shell canónico ni se migró nada a primitivas `aia-*`.
`admin/` queda **en dark y tokenizado pero NO migrado al design system**, que es la desviación
deliberada ya registrada en el spec y en `goal.md`.

### Qué se hizo

- **T4.1 Vendorizar.** `main.php` y las tres vistas de auth pasan de CDN a `/public/vendor`, con
  versión fija: AdminLTE 3.2.0 (que ya trae Bootstrap 4.6.1 dentro), Bootstrap 4.6.1 bundle,
  DataTables 1.10.21 + responsive 2.2.7 + buttons 1.7.0, jszip 3.5.0, pdfmake 0.1.70,
  icheck-bootstrap 3.0.1, Select2 4.0.13. Se reutilizan los ya servidos (jQuery 3.6.0, Font Awesome,
  Toastr 2.1.3, SweetAlert2) y las fuentes locales vía `design-system/fonts.css`, con lo que `admin/`
  entra en el contrato DS-007. Todos los `url()` de AdminLTE son `data:`; no hay assets colgando.
- **T4.2 Unificar tokens.** `admin/public/css/tokens.css` **eliminado**. El canónico
  `public/css/tokens.css` ya era un superconjunto de sus 37 hex, verificado token a token. Con él
  desaparece por construcción el `--aia-bg-linen` que F0 dejó suelto.
- **T4.3 Aplicar el tema.** Las cuatro vistas con `<html>` propio cargan `theme-bootstrap.js` y
  además llevan `data-aia-theme="dark"` escrito en el `<html>` (cinturón y tirantes, sin flash).
  `navbar-white navbar-light` → `navbar-dark`.
- **T4.4 Adaptador.** Nuevo `public/css/design-system/adapters/admin-lte.css`, y dos entrypoints
  propios de `admin/` (`admin-entrypoint.css`, `admin-auth-entrypoint.css`) que importan cada vendor
  con `layer(vendor)` y luego tokens, tema y adaptadores.
- **T4.5 Limpiar vistas.** Cero `<style>` y cero `style="…"` en `admin/views/`.
- **T4.6 Presupuesto.** Presupuesto de ruta `admin` en `exceptions.json` con **cero** en las seis
  reglas, cubriendo también el adaptador.

### Decisiones que conviene no volver a discutir

- **Por qué `admin/` tiene entrypoint propio y no `renderForModule('admin')`.**
  `DesignSystemHeadComponent::VIEW_OWNED_VENDORS` declara `adminlte` con un candado explícito: ningún
  miembro de esa lista puede tener `attach-<vendor>.css` en la partición. Crear
  `entrypoints/attach-admin-lte.css` habría roto el gate de partición. El aislamiento que AGENTS.md
  exige para `admin/` es de PHP, no de CSS, así que lo que sí se comparte son tokens, tema y
  adaptadores.
- **Estado en `inventory.json`: `observed-frozen` → `inventory-only`.** No es cosmético.
  `observed-frozen` significa por definición del README «sin presupuesto de rutas — el contador solo
  puede bajar», y T4.6 declara presupuesto, así que dejaba de ser cierto. `inventory-only` describe
  lo que `admin/` es: catalogado, con presupuesto, **sin manifiesto ni cobertura golden, y sin
  intención de tenerlos**. La razón queda escrita en el propio `note` del módulo.
- **La `hardcoded-radius` que el spec preveía excepcionar no hizo falta.** Los radios de AdminLTE
  viven en `public/vendor/`, que no está en `scanRoots`, y el adaptador solo usa `var(--ds-radius-*)`.
  Se declara cero y no se registra excepción.
- **Los `!important` del adaptador están en `@layer reset` a propósito.** Las utilidades de color de
  Bootstrap declaran `!important` dentro de `layer(vendor)`, y para `!important` el orden de capas se
  invierte: solo una capa **anterior** a `vendor` puede ganarles. `components` no puede. Son 29
  declaraciones, frente a las 83 que `admin/` tenía antes: saldo neto negativo.
- **No se remapearon `.text-warning`, `.text-success`, `.text-info` ni `.text-primary`.** Los 22
  fallos medidos al abrir F4 eran de esos colores **sobre blanco**. Sobre superficie oscura cumplen
  AA sin tocarlos, y remapearlos solo habría desplazado su significado.

### Verificación (salida real de esta sesión)

- `node scripts/design-system-audit.mjs` → **PASS**. Hallazgos totales **5 613 → 5 239 (−374)**.
  `admin/` pasa de 271 hallazgos a **cero en todas las reglas**; el presupuesto `admin` marca 0/0 en
  las seis. Bajaron 13 reglas, ninguna subió.
- `node scripts/design-system-unlayered-delivery.mjs` → **PASS** (16 entregas declaradas, sin cambios:
  el gate solo escanea `views/` y `public/js/`, así que `admin/` nunca entró en su inventario).
- `npm run test:design-system:static` → **358 pass / 1 fail**. El único rojo es
  `contracts.test.mjs` por «worktree and index must be clean», que es el rojo esperado con trabajo sin
  commitear. `entrypoint partition`, `unlayered delivery` y `BI utilities` en PASS.
- `docker compose exec app vendor/bin/phpstan analyse src admin/src --memory-limit=1G` → **No errors**.
- `docker compose exec app php tests/test_design_system_head_component.php` → **PASS**.
- `docker compose exec app php tests/test_global_table_safety.php` → **OK**.
- **Navegador**, Playwright contra el contenedor, 1180×820 dark, **las 13 rutas de `admin/`**
  (las 14 vistas: `layouts/main.php` se ejerce en las diez autenticadas):
  **5 163 elementos de texto medidos, 0 fallos de contraste**; peor ratio por ruta entre 4,50 y 5,39.
  **Cero peticiones a dominios externos**, consola limpia y **cero respuestas ≥400** en las 13.
  `body` en `rgb(11, 16, 13)`. Ninguna ruta desborda horizontalmente.
  Punto de partida para comparar: `/admin/` **1,63:1 con 22 fallos → 0 fallos**; `/admin/usuarios`
  2,13:1 con 99 fallos → 0; `/admin/proyectos` 3,13:1 con 15 fallos → 0.
- **Foco visible**: `#nombre` en `/admin/usuarios/crear` da `outline: 2px solid rgb(44, 170, 159)`
  más `box-shadow` de 4px del mismo anillo.
- **Control de acceso**: sin sesión, `/admin/usuarios` redirige a `/admin/login`; usuario activo entra
  a `/admin/`; usuario inactivo es rechazado («Tu cuenta está inactiva»). **No hay ruta con
  restricción por rol en `admin/`**: `AdminController::userCan()` existe pero no se invoca en ningún
  sitio, así que la única puerta es sesión + `activo=1`. No se tocaron rutas, RBAC, sesión ni modelos.
- **Evidencia**: 13 capturas en `evidence/F4/`.

### Datos tocados y restaurados

Para el QA se creó un usuario temporal (`f4_qa_admin`, activo, rol `A` en el proyecto 22) y uno
inactivo (`f4_qa_off`). **Ambos, y su fila en `project_members`, fueron eliminados al terminar**;
verificado con `SELECT COUNT(*)` = 0 en las dos tablas. No se modificó ninguna fila preexistente.

### Hallazgos de paso (no eran del alcance, pero estaban rotos)

- **Dos `<link>`/`<script>` a CDN devolvían 404 en producción hoy mismo**, comprobado con `curl`:
  `datatables.net-buttons-bs4/1.7.0/js/buttons.bootstrap4.min.js` y
  `select2-bootstrap4-theme/1.5.2/select2-bootstrap4.min.css`. El primero se vendorizó desde jsdelivr
  y ahora sí carga. El segundo **no se vendorizó**: llevaba tiempo sin cargar, es una piel clara que
  el adaptador del design system sobrescribiría igualmente, y traerlo habría sido un cambio visual
  nuevo fuera del alcance de F4. Se retiró el `<link>` muerto y se conservó `theme:'bootstrap4'` en
  el JS; Select2 lo estiliza ahora `adapters/select2.css`.

### Queda abierto

- **Font Awesome local es 5.11.2 y el CDN retirado servía 5.15.4.** Se reutilizó el ya vendorizado,
  como pedía T4.1 («apuntar a los que ya están servidos»). Los iconos que usa `admin/` son básicos y
  ninguno salió vacío en las 13 capturas, pero un icono introducido entre 5.11 y 5.15 no existiría.
  Actualizar el paquete compartido excede F4 porque lo consume toda la app.
- **`views/auth/*` sigue cargando AdminLTE por CDN.** Ahora existe la copia local en
  `/public/vendor/admin-lte/`, así que apuntarlas es trivial, pero son del grupo A y no de F4.
- **Los iconos gigantes de `.small-box`** siguen siendo las manchas claras de AdminLTE sobre el
  acento (`.small-box .icon`, decorativo). No afecta contraste de texto y no se tocó para no ampliar
  el adaptador.
- **Los interruptores de Bootstrap** (`custom-control-label::before`) siguen con el borde de
  separador en vez del de control. Es el límite conocido que `DESIGN.md` ya documenta: el mecanismo
  del par de bordes actúa sobre elementos y no alcanza pseudo-elementos. Deuda compartida, no de F4.

---

## 2026-07-29 · F2 · Reconciliación de seis goldens tras el cambio del adaptador de Handsontable

**Aprobación explícita del usuario, 2026-07-29.** `DESIGN.md` exige aprobación humana antes de
reconciliar goldens y `AGENTS.md` prohíbe regenerar baselines para forzar verde. Esa aprobación
existe y es el motivo de esta tanda: se registra aquí como la autorización que la habilita.

### Por qué

`public/css/design-system/adapters/handsontable.css` dejó de aplicar relleno vertical a las celdas
(`padding-block: 0` + `padding-inline`, donde había `padding: var(--ds-space-2) var(--ds-space-3)`).
Ver la entrada «Alineación del clon de encabezados de fila — a la raíz» de este mismo día para la
causa raíz. Efecto secundario buscado y aceptado: **las grillas se compactan** —cabecera de
`/programa-general` de 48 a 32 px, una fila visible más; `/programacion-intermedia` de 162 a 146 px—.
Los tres goldens de `profesionales`, `subcontratistas` y `programa-general-actualizar` ya se habían
recapturado entonces; quedaban estos seis, que la entrada anterior dejó anotados como «pendiente de
decisión».

### Los seis, con su sha256 antes y después

| Escenario | sha256 antes | sha256 ahora |
|---|---|---|
| `programa-general-dark-1180x820` | `5c9ce074…80a3b0a2` | `35e79cff…166182b3` |
| `programa-general-dark-1440x900` | `2af6f19e…15c378f8` | `7fae2a38…218f59d58` |
| `programacion-intermedia-dark-1180x820` | `3aa6cca9…cb9ec5bd` | `b2d8174b…898c8ee45f` |
| `programacion-intermedia-dark-1440x900` | `7385d0a6…3813da6c` | `1e8a6805…0395acdb` |
| `programacion-semanal-dark-1180x820` | `c11bc8fb…175e53dd` | `d31cc841…977a8c78` |
| `programacion-semanal-dark-1440x900` | `a667c670…5920c7fd0` | `36f91822…0d985bea4477` |

`1440×900` es el viewport desktop secundario que `DESIGN.md` permite; entra en alcance.

### Cómo se capturaron

- **`programa-general` y `programacion-intermedia`:** con sus propias specs y
  `--update-snapshots`, para que el golden sea byte a byte el que la prueba compara (mismo
  `deviceScaleFactor`, mismos mocks deterministas, misma espera de `#save-status`). No se tocó
  ninguna aserción ni umbral.
- **`programacion-semanal` no tiene spec visual** —sus goldens son evidencia del manifiesto, no
  baseline de Playwright—, así que se capturó con un script temporal en la raíz del repo, borrado al
  terminar: login `test.A`, proyecto Da Porto, semana 4 (la misma del golden anterior), dark,
  `#loading` oculto y `ht_master` montado. **El POST de autoprogramación que la vista dispara al
  cargar se interceptó** (`**/api/semanal/auto-program**` → `{success:true, log:[]}`) para no
  escribir datos y para que la captura fuera estable: no quedó dato alterado.
- Las seis capturas se **revisaron una a una**: grilla compacta, encabezados de fila sobre sus filas,
  sin spinner y sin tabla a media anchura. `overflowX = 0` en las dos de semanal.

### El séptimo escenario NO se tocó: `programa-general-dark-390x844`

`programa-general.json` declara un tercer escenario a **390×844, que es mobile**. `AGENTS.md`
prohíbe de forma explícita trabajar, validar o generar evidencia para mobile o tablet, así que
**quedó intacto** —su PNG no se abrió y su `sha256` sigue casando—. Pero eso deja el manifiesto
retratando en ese escenario un estado que ya no se sirve.

**Que un manifiesto del design system declare un escenario mobile contradice el alcance visual
vigente** (desktop ≥1180 px, dark). No es un olvido: es una decisión pendiente del usuario. Las
salidas son retirar el escenario del manifiesto, o levantar la restricción para ese caso. Hasta que
se decida, ese golden envejece.

### Verificación, con salida real

- `node scripts/design-system-audit.mjs` → **pasa contra baseline**.
- `node scripts/design-system-consumer-contract.mjs` → **PASS (1 manifiesto v1)**. Es la comprobación
  que importa aquí: valida que cada `golden` exista y que su `sha256` case.
- `node scripts/design-system-entrypoint-partition.mjs` → **PASS**.
- `npm run test:design-system:static` → **358/359**.
- `npx playwright test …/handsontable-ancho-tabla.mjs` → **4/5**.
- Las dos specs visuales re-corridas **sin** `--update-snapshots` contra los goldens nuevos:
  **4/4 verdes**.

**Los dos rojos son los ya documentados y no son de esta tanda:** `contracts.test.mjs` con
`activation: worktree and index must be clean`, imposible de ver verde con trabajo sin commitear; y
`handsontable-ancho-tabla.mjs` en `/subcontratistas` (13 px de desvío), defecto abierto del
`autoRowSize` del vendor. Ninguno se persiguió ni se tapó. `state-token-pairing.test.mjs`, que la
tanda anterior reportaba en rojo por `adapters/admin-lte.css`, **ya pasa**: lo cerró F4.

### Queda abierto

- **T6.3 de F6 sigue abierto** (consolidar Select2 en Tom Select), y `programacion-semanal` es hoy su
  candidato de arranque. Cuando se ejecute, este golden vuelve a quedar obsoleto.
- **En `/programacion-semanal` el botón «Ver Secciones» de la barra queda recortado** por el riel
  «CONCURRENCIA LPS» a 1180 px; a 1440 px cabe entero. Es del shell con sidebar, no del adaptador de
  Handsontable, y es anterior a esta tanda. La captura lo congela porque retrata lo que se sirve.
- **Otra sesión dejó conflictos de merge sin resolver en el worktree** durante esta tanda
  (`pdc-app/src/styles.css`, `public/pdc-app/assets/pdc.css`, `public/pdc-app/assets/pdc.js`, en
  estado `UU`), además de varios archivos del PDC en el índice. **No se tocaron.**

## Auditoría de cobertura de iconos tras la vendorización FA de F4 (2026-07-29)

F4 dejó `admin/` consumiendo la copia local `public/vendor/font-awesome/css/all.css`, que es
**Font Awesome Free 5.11.2**, en lugar del CDN 5.15.4. Se auditó si algún icono usado en el repo
existe solo en 5.15 y por tanto quedaría vacío.

**Comandos (salida real de esta sesión):**

- Clases usadas: `grep -rhoE '\bfa-[a-z0-9]+(-[a-z0-9]+)*' admin/views views public/js src
  --include='*.php' --include='*.js' --include='*.html' | sort -u` → **104** clases distintas.
- Clases declaradas por el vendor: `grep -ohE '\.fa-[a-z0-9]+(-[a-z0-9]+)*'
  public/vendor/font-awesome/css/all.css | sed 's/^\.//' | sort -u` → **1425**.
- Diferencia (`comm -23`) → **3** clases no declaradas por 5.11.2:
  - `fa-arrow-circle-bottom` — `admin/views/pages/dashboard.php:74`
  - `fa-check-shield` — `admin/views/pages/dashboard.php:253`
  - `fa-m` — `views/listado-actividades/listadoActividades.view.php:763-764`,
    `public/js/cargarDatosGeneralesPagina2.js:333,403`

**Las tres tampoco existen en 5.15.4** (verificado contra
`https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css`: los tres selectores
devuelven «no»). No son nombres de FA5: lo correcto sería `fa-arrow-circle-down`, `fa-shield-alt`, y
`fa-m` no es una clase de tamaño de FA5 (las válidas son `fa-xs`/`fa-sm`/`fa-lg`/`fa-2x`…; `fa-m`
solo existe como icono-letra en FA6).

**Conclusión: no falta ningún icono por el downgrade 5.15.4 → 5.11.2.** No hace falta actualizar
`public/vendor/font-awesome/`, y por tanto no se tocó el paquete que consume toda la app vía
`public/css/design-system/entrypoints/core.css` (`layer(vendor)`). No se corrieron los gates del DS
porque no hubo cambio que verificar.

### Queda abierto (de esta auditoría)

- **Tres clases de icono inválidas, preexistentes y ajenas a F4** (no renderizaban nada ni con el CDN
  5.15.4): las dos de `admin/views/pages/dashboard.php` y el modificador `fa-m` en Listado de
  Actividades y `cargarDatosGeneralesPagina2.js`. Fuera del alcance de F4; corregirlas es un cambio
  cosmético independiente.

---

## F2 · Séptima superficie: `/pdc` (2026-07-29)

Cierra F2. Era la última de las siete que colgaba del agregador.

### Corrección de partida: `/pdc` NO se pintaba en claro

El plan arrancó con esa premisa, heredada de una nota vieja, y **es falsa**. Medido a 1180×820 dark
sobre el contenedor servido, revirtiendo los cambios para obtener el antes:

| | antes | después |
|---|---|---|
| `data-aia-theme` | `dark` | `dark` |
| fondo de `body` | `rgb(11, 16, 13)` | igual |
| fondo de la grilla | `rgba(28, 36, 31, 0.92)` | igual |
| overflow horizontal | no | no |
| `.pdc-message-neutral` | **`rgb(20, 28, 24)`** | `rgb(247, 250, 248)` |
| `.pdc-row-action--delete` | `rgb(143, 29, 29)` | `oklch(0.42 0.187 26.35)` |

La página ya estaba oscura porque el grueso de `pdc.css` ya usaba `--ds-active-*`; lo que quedaba
sin tokenizar eran sombras, bordes y acentos, que no dominan la superficie. **El único defecto
visible de verdad era `.pdc-message-neutral`: tinta casi negra sobre canvas oscuro**, invisible.

Comparadas las dos capturas, las filas naranja y violeta de la grilla son tintes de estado
preexistentes (las siete anclas que /pdc eligió y midió), no un efecto de este cambio.

### Qué se hizo

1. **`public/css/pdc.css` entra en `@layer module`.** Eran **124 reglas sin capa**, que le ganaban a
   todo el design system. El cuerpo no se reindenta a propósito: son ~900 líneas y el sangrado
   habría enterrado el cambio real bajo un diff de fichero completo.
2. **22 de los 23 `rgba()` tokenizados.** Sombras → `--ds-shadow-*` (esto además deja
   `off-scale-shadow` en 0, de 9); bordes y superficies → `--ds-active-*`; transparencias →
   `color-mix()`. También 7 de 9 `raw-token-in-module`.
3. **`.pdc-row-action--delete`**, el cruce `at-risk` que `state-token-exceptions.json` marcaba como
   el más urgente de los ocho. Usaba el token de TEXTO del par critical como relleno sólido y ese
   par no tiene inversión dark: al invertir caía a 1,42:1.
   - Primer intento: `--ds-state-tint-red`, el ancla de /pdc para crítico. Sobrevive la inversión,
     pero **medido en pantalla el botón retrocedía** contra los tintes de fila: es un tono de fondo,
     no de acción.
   - Solución: `oklch(from var(--ds-color-state-critical-text) 0.42 calc(c * 1.25) h)`, que **fija**
     la luminosidad. El relleno es el mismo rojo pase lo que pase con el par. La entrada del
     inventario se retiró: al dejar de ser sustitución cruda, el escáner deja de verla.
4. **`.pdc-message-*`** elevados con el mismo recurso de color relativo de
   `programa-general-actualizar.css:30-33`.
5. **Head a `renderForModule('pdc')`** con 8 vendors declarados. Se retiraron los dos `<link>` crudos
   a Handsontable (caso `2/5-handsontable-doble-carga`): `attach-handsontable.css` los importa con
   `layer(vendor)`. Verificado que las hojas servidas pasan del agregador a `core.css` + 4 adjuntos
   **sin ninguna diferencia en las métricas visuales**.
6. Manifiesto, golden, presupuesto, inventario y los **cuatro censos cerrados** que había que tocar.

### Trampas que costaron una vuelta

- **El audit ve los hex y los `rgba()` dentro de los comentarios.** Documentar el cambio citando los
  valores puso el gate en rojo tres veces. Los comentarios describen el color con palabras.
- **`renderForModule` degrada en silencio.** La primera medición mostraba el agregador entero: el
  manifiesto aún no existía. La lista de hojas servidas es la única prueba de que la migración
  surtió efecto; el aspecto de la página no lo demuestra.
- **`escalamientos` perdió su drawer al migrar, aquí no aplica.** `attach-handsontable.css` sí
  importa `handsontable-module.css`, y además `/pdc` no tiene Cajón Contextual (verificado en el DOM).
- Cuatro censos cerrados rompen si se añade un manifiesto: `contracts.test.mjs:249`,
  `foundation.test.mjs` (`sharedHeadConsumers`, 5 → 4), `router.test.mjs` (usaba `/pdc` como ejemplo
  de superficie sin declarar) y el propio `inventory.json` por partida doble.

### Verificado

- Navegador, 1180×820 dark, contenedor servido: dark, sin overflow horizontal, grilla 1062×635,
  **consola limpia**, modal de contrato íntegramente en oscuro (superficie, secciones, inputs,
  labels, badge de bloqueo y botón de guardar medidos uno a uno).
- `design-system-audit` PASS — presupuesto `pdc` en 0/0/0/0/0 y **1** en `hardcoded-color-function`.
- `design-system-entrypoint-partition` PASS · `design-system-unlayered-delivery` (estático) PASS ·
  `state-token-pairing` 3/3.
- `npm run test:design-system:static`: el **único** rojo es
  `activation: worktree and index must be clean`, el falso rojo conocido con trabajo sin commitear.

### Queda abierto

- **`hardcoded-color-function` en 1, no en 0.** El borde en verde de marca dentro de
  `@media (max-width: 767.98px)`. `AGENTS.md` prohíbe implementar y validar móvil en esta línea de
  trabajo. Deuda contada, no presupuesto laxo: cualquier función de color nueva en escritorio pone
  el gate en rojo igual que con cero.
- **Las 9 librerías por CDN de la vista** (jQuery, popper, Bootstrap, jQuery UI ×2, Google Charts,
  AnyChart, select2, numeral) **más Google Fonts**, por decisión explícita del usuario: F2 es
  normalización de color y contrato, no de dependencias. Es lo que impide declarar
  `consumerContract: "v1"`, que prohíbe URL externas.
- **El skin de select2 se tokenizó tal cual y T6.3 lo rehará** al sustituirlo por Tom Select,
  también por decisión explícita. No se pudo ejercitar un widget select2 vivo: no se instancia en el
  estado vacío del modal.
- **`!important` (84), `off-scale-spacing` (54) y `off-scale-typography` (26)** en `pdc.css`, intactos:
  son otras reglas del audit, fuera del presupuesto de esta tanda.
- **Deriva preexistente del gate de runtime, AJENA a `/pdc`.** `design-system-unlayered-delivery`
  (runtime) da 13 hallazgos, ninguno de `/pdc`: `/dashboard/escalamientos`, `/profesionales`,
  `/subcontratistas` y `/programa-general-actualizar` declaran un bloque `<style>` que ya no existe
  (lo retiraron al migrar en F2 sin actualizar el inventario de runtime), `/listado-actividades` y
  `/subcontratistas` declaran hojas de CDN que ya no cargan, y `/plan-compras` declara 16 bloques
  donde hay 19. Es deuda de las seis superficies hermanas; no se tocó para no mezclar.

---

## Deriva del inventario de entregas sin capa — y dos rutas caídas (2026-07-29)

Encargo: sanear los 13 hallazgos del gate de runtime, deriva contable heredada de F2. Lo que
apareció por el camino es más importante que el encargo.

### `/listado-actividades` y `/contratos` devolvían «Error Interno del Servidor»

**Causa raíz.** El 2026-07-29 se retiraron «Familias de Actividades» y «Paquetes de Contratación»
del rail del sidebar —decisión deliberada y documentada en `views/partials/shell_sidebar.php:90-105`,
por ser la interfaz del PDC viejo—, dejando las rutas servidas y accesibles por su dirección. Pero
`ListadoActividadesController` y `ContratosController` seguían pidiendo `$shellActive =
'listado-actividades'` / `'contratos'`, y `DesignSystemComponent.php:392` **lanza**
`InvalidArgumentException` cuando el ítem activo no está entre los renderizados.

El propio comentario del rail previó el problema para `/pdc` («El id se conserva ('plan-compras')
para que el `$shellActive` del controlador viejo siga casando») pero no para estas dos.

**Por qué nadie lo vio.** Tres capas de camuflaje a la vez:

1. **El error sale con status 200.** `public/index.php:391` hace `http_response_code(500)`, pero el
   `<head>` de la vista ya se emitió, así que PHP avisa «headers already sent» y la respuesta se
   queda en 200 con `<h1>Error Interno del Servidor</h1>` en el cuerpo. Un monitor por código de
   estado no lo ve.
2. **El gate de entregas sin capa las daba por limpias.** Una página que muere justo después del
   `<head>` entrega exactamente las hojas declaradas y ninguna de más. Su censo era el de la
   cabecera, no el de la página.
3. **`shell-sidebar-rollout.mjs` moría antes de llegar.** Reventaba por timeout esperando el
   sidebar en `/listado-actividades`, que es la ruta 12 de 23, así que **las 11 siguientes nunca se
   probaron** — incluida `/pdc`.

**Arreglo** (autorizado explícitamente): `$shellActive = ''` en los dos controladores. Una ruta que a
propósito no tiene entrada en el rail no debe marcar ninguna como actual, y la excepción solo salta
con `$active !== ''`. Medido después: 107 201 y 103 278 bytes (venían de 1 277 y 1 175), sidebar
presente, sin overflow horizontal, ningún ítem marcado.

**Red de seguridad** (autorizada): el gate de runtime ahora asierta que el cuerpo no contiene
«Error Interno del Servidor». Convierte un fallo invisible en uno ruidoso, en las 25 rutas que
recorre.

**Consecuencia para el encargo original:** la entrada de tom-select en `/listado-actividades`
**no estaba obsoleta**. Solo lo parecía porque la página moría antes del `<link>`, que vive en el
cuerpo. Al resucitar la ruta, el CDN vuelve a cargar y la entrada es correcta. **No se tocó.**

### La contabilidad que sí era contabilidad

Medido con el propio gate, que persiste lo observado en
`test-results/design-system-unlayered-delivery-authenticated.json`:

- `/dashboard/escalamientos`, `/profesionales`, `/programa-general-actualizar`, `/subcontratistas`:
  declaraban un bloque `<style>` sin capa que **ya no existe** (0 medidos). Lo retiraron al migrar en
  F2 sin actualizar la sección `runtime`. Entradas eliminadas.
- `/subcontratistas` declaraba además el CDN de Handsontable, que ya no está en la vista. Eliminada.
- `/pdc` sale **OK**: valida el cierre de F2 de esta misma jornada.

### `/plan-compras`: 19 bloques, y ninguno es de autor

La única deriva **al alza**, y por eso la única que podía esconder una regresión. Enumerados los 19
bloques uno a uno en el navegador: **los 19 son AG Grid 36.0.2**, cada uno autoidentificado con
`data-ag-css` (`shared`, `core`, diez `module-*`, `component-sb` y siete `ag-theme-*`). **Cero
bloques de CSS propio.** El salto 16 → 19 es registro de módulos del vendor, que sube y baja con las
funciones de grilla que active la SPA: ese número es frágil por construcción y así queda anotado.

También creció, sin que ningún gate lo vigile, `/pdc-app/assets/pdc.css`: **140 → 268 reglas sin
capa**. El gate compara el conjunto de hojas y el número de bloques, no el de reglas.

**Que AG Grid entre sin capa sí es un hallazgo real** —derrota al design system en esa ruta, que es
exactamente la clase de problema para la que existe el gate—, pero es **alcance de F5**, que además
se implementa en el repositorio externo `plan-de-compras`. `specs/F5-plan-compras.md` documenta que
el CSS de la SPA está fuera del alcance de los gates, pero **no menciona la inyección de AG Grid**:
conviene que F5 la absorba.

### Verificado

- `npx playwright test tests/browser/design-system-unlayered-delivery.mjs --workers=1` → **2 passed**
  (de 13 hallazgos a 0).
- `node tests/browser/shell-sidebar-rollout.mjs` → **141/141 checks OK**, y por primera vez recorre
  las 23 rutas enteras. Se corrigieron dos expectativas caducas del propio harness: exigía
  `aria-current` en las dos rutas que ya no tienen ítem en el rail. Ahora `active: null` asierta lo
  contrario —que no haya ninguno marcado—, que es el fallo real que las tumbó.
- `npm run test:design-system:static`: único rojo `activation: worktree and index must be clean`, el
  falso rojo conocido con trabajo sin commitear.

### Queda abierto

- **AG Grid sin capa en `/plan-compras`** (19 bloques, 347 reglas) y **`pdc.css` de la SPA** (268
  reglas sin capa). Alcance F5, repositorio externo.
- **El gate no compara número de reglas**, solo hojas y bloques. Por eso `pdc.css` pudo casi doblar
  su deuda sin poner nada en rojo. Es una decisión consciente documentada en el propio test
  (el conteo de reglas sube con cualquier edición), pero conviene saberlo.
