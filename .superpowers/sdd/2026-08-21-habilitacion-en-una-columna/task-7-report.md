# Task 7 — informe

## Qué se hizo

- `public/js/design-system/readiness-popover.js`: contenido real del globo (Step 3). Cabecera
  (actividad, semana, responsable), marcador de avance (`.aia-readiness-popover__avance`), chip de
  estado (reusa el HTML de `ops-state-chip` que ya arma `hot.js`, no un chip propio), grupo
  «Obligatorias» y grupo «De seguimiento», una fila por restricción con su cuadrito
  (`aia-readiness__box`, mismas clases/tokens que la columna fundida), su nombre y su `<select>`.
  Modo lectura (Step 6): `select.disabled = !canEdit` + línea con la razón. Fallo dentro del globo
  (Step 5): la fila se marca (`--error`), muestra el mismo texto que ya usa `saveRow` y un botón
  «Reintentar» que repite el mismo `guardar`. Ctrl+Z (Step 7): forwarding a un deshacer propio del
  globo (ver «Ctrl+Z» abajo) y resincroniza los `<select>` leyendo el dato ya revertido.
- `public/js/modules/programacion_intermedia/hot.js`: `abrirGloboHabilitacion` arma el paquete
  `datosFila` (grupos duras/blandas, canEdit, razón de solo lectura, `guardar`, `deshacer`,
  `leerValor`) con datos y reglas YA existentes (nada nuevo se calcula fuera de este archivo). El
  registro `_saveResultListeners` (`visualRow|prop` -> callback) es el puente entre `saveRow` —que
  no puede recibir un callback porque lo dispara `afterChange`, no una llamada directa— y el globo;
  se consulta y borra en los TRES desenlaces de `saveRow` (éxito, error de servidor, fallo de red),
  reusando literalmente los mismos mensajes que ya usa `showFeedback`. También:
  `recalculateRestrictionStateForVisualRow` (nombre real, no `Ratio`) sigue siendo la única que
  recalcula, sin tocarla. `beforeUndoStackChange` nuevo en las opciones de Handsontable (ver
  «Ctrl+Z»).
- `public/css/design-system/components/readiness-popover.css`: reglas para las clases nuevas, todas
  con tokens (`var(--ds-*)`), cero hex, único inline permitido (`fill.style.height`, mismo patrón que
  ya existía en `readiness-squares.css`, reusado aquí sin inventar uno nuevo).
- `tests/browser/pi-globo-guardado.mjs`: nuevo (Step 1/2/8), con tres desviaciones del literal del
  brief, todas documentadas en el propio archivo con comentario `// Nota (Task 7)`:
  1. **Proyecto**: `PDC Sandbox E2E` (test.R) tiene una sola actividad y sin Responsable AIA
     asignado — verificado en vivo, no es un bug: es la regla N-1 (sin responsable, restricción
     bloqueada) más datos mínimos de sandbox (`PI_HOT_OPTIONS.profesionales` llega vacío, ni
     siquiera se puede asignar un responsable desde la UI ahí). El globo SÍ abre bien en modo
     lectura contra ese proyecto (de hecho es la prueba viva del Step 6), pero no sirve para probar
     guardado. Se usa `Optimización Aeropuerto JMC` (mismo test.R, 39 actividades, las 39 con
     responsable) solo para este archivo.
  2. **Regex del endpoint**: el brief traía `/programacion-intermedia|restriccion/i`. El único
     endpoint real de guardado de PI es `POST /api/pi/save` (`public/index.php:127`) — ninguna de
     esas dos palabras aparece ahí. Corregido a `/\/api\/pi\/save/`, verificado contra el router real.
  3. **Reinicio del contador de peticiones**: el `pagina.on('request', ...)` se registra antes de
     cualquier navegación, así que ya venía contando el POST de `/programacion-intermedia/filtros` y
     dos de `datosGeneralesPagina.php` (cabecera legacy) que dispara la CARGA de la página, no el
     guardado. Sin `peticiones.length = 0` justo antes de abrir el globo, la aserción "una sola
     petición" fallaba siempre, con cualquier proyecto e implementación.

## Grupos duras/blandas y opciones del selector

Salen de `hardRestrictionProps`/`softRestrictionProps` (ya calculados por `applyRestrictionConfig`
desde `/api/general/restriction-config`, con fallback a `CONSTRUCTION_DEFAULTS`) y de
`_activeRestrictions` (`_findRestrictionByKey`), que trae `.label`, `.options` y `.threshold` por
restricción — exactamente la config que ya usaba la columna vieja de cada restricción antes de la
Task 4. `construirGrupoRestricciones(claves, rowData)` solo empaca `{ key, label, options: [''].
concat(restriccion.options), value: rowData[key], umbralRatio: threshold/100 }` para cada clave. No
se inventó ninguna opción ni endpoint nuevo.

## Ctrl+Z (Step 7) — lo que cambió respecto al plan original

El brief y mi primer intento asumían que reenviar el atajo a `hot.undo()` (la pila nativa de
Handsontable) bastaba. **Verificado en vivo, no bastaba**: `ChangeAction.undo()` de Handsontable
replica el cambio con `instance.setDataAtCell(row, COLUMNA)`, convirtiendo el prop a índice de
columna con `propToCol()`. Las siete props de restricción (`D_y_E`, `Materiales`, ...) dejaron de
ser columnas propias de la grilla desde que la Task 4 las fundió en la columna `__habilitacion` —
`propToCol('D_y_E')` no resuelve a nada, y Handsontable lanza en consola: *"Method `setDataAtCell`
accepts row and column number as its parameters..."* sin revertir el dato. Confirmado con logging
temporal (revertido, no quedó en el código) mostrando la pila (`doneActions`) con la acción correcta
pero el `pageerror` disparándose al llamar `.undo()`.

Esto no es un defecto que el globo introduzca: es estructural desde la Task 4 — CUALQUIER escritura
futura a esas props (hoy solo el globo escribe ahí) heredaría la misma pila rota si se apoyara en el
mecanismo nativo de Handsontable.

**Solución implementada, mínima y dentro del alcance de esta tarea:**
1. `beforeUndoStackChange` en las opciones de Handsontable: bloquea que las escrituras derivadas de
   `saveRow` (`source === 'internal-update'`, ej. `Estado_Restricciones`, `estado_operativo`) se
   apilen como acciones de deshacer — sin esto, incluso si el undo nativo funcionara, un solo Ctrl+Z
   habría deshecho el último campo calculado en vez de la restricción que la persona acababa de
   marcar.
2. El globo lleva su **propio único nivel de deshacer** (`ultimaEdicionGlobo` en el closure de
   `abrirGloboHabilitacion`, no una variable de módulo): guarda `{ prop, valorAnterior }` justo antes
   de escribir, y `deshacer()` escribe el valor anterior por la MISMA ruta
   (`hot.setDataAtRowProp(..., 'edit')` → mismo `afterChange` → mismo `saveRow`) — no un camino de
   guardado nuevo. Simplemente no pasa por `hot.undo()`.

Esto cumple el requisito del Step 7 tal como lo mide la prueba (un Ctrl+Z revierte la liberación
hecha desde el globo), sin tocar ni reparar la pila nativa de Handsontable en general — eso queda
anotado como límite conocido, no arreglado, porque arreglarlo de raíz (recuperar columnas reales
para las props fundidas, o parchear `ChangeAction`) es una tarea propia, fuera del alcance de esta.

## Salida real

RED (Step 2, antes de implementar — verificado con `git stash` para aislar el estado previo a esta
tarea):
```
locator.innerText: Timeout 30000ms exceeded.
  - waiting for locator('.aia-readiness-popover__avance')
```

RED específico del Step 7 (verificado por separado, simulando el bypass que describe el brief —
escribir con source `'internal-update'` en vez de `'edit'` y llamar `saveRow` aparte):
```
OK: una peticion, mismo endpoint, avance en vivo y globo abierto
AssertionError [ERR_ASSERTION]: Ctrl+Z no deshizo la liberacion hecha desde el globo
    actual: '33%', expected: '33%' (notStrictEqual)
```

GREEN (Step 8, implementación final, corrido dos veces para confirmar estabilidad):
```
$ node tests/browser/pi-globo-guardado.mjs
OK: una peticion, mismo endpoint, avance en vivo y globo abierto
OK: Ctrl+Z deshace lo que el globo guardo
```

Sin regresión en las pruebas hermanas del mismo frente:
```
$ node tests/browser/pi-globo-teclado.mjs
OK: abre, enfoca, cierra y devuelve el foco
OK: el clic afuera cierra el globo y devuelve el foco

$ node tests/browser/pi-ancho-presupuesto.mjs
OK: 1100 px en 1100, 0 celdas recortadas
```

## Dudas / límites conocidos

- **La pila nativa de deshacer de Handsontable (`hot.undo()`/Ctrl+Z desde la tabla) sigue rota para
  las props de restricción** por la razón explicada arriba (no son columnas desde la Task 4). Hoy no
  se ejerce porque la única vía de edición de esas props es el globo (que usa su propio deshacer), y
  `beforeUndoStackChange` evita que se acumulen entradas rotas en la pila por las escrituras
  derivadas de `saveRow`. Pero si en el futuro algo más escribe esas props con source `'edit'`
  (código nuevo, no el globo), la entrada queda en la pila y explota si alguien invoca `hot.undo()`
  desde la tabla (por ejemplo, deshacer una edición de OTRA columna justo después de haber tocado una
  restricción). Vale la pena una tarea aparte si se retoma el uso de undo nativo en esta grilla.
- `PDC Sandbox E2E` para `test.R` es un proyecto de una sola actividad, sin Responsable AIA y sin
  profesionales seedeados — sirve para probar apertura/modo lectura del globo, no para probar
  guardado. Si otra tarea de este mismo frente necesita probar edición de restricciones con esa
  combinación de cuenta/proyecto, se topará con lo mismo.
- No corrí `npm run test:design-system:*` completos (fuera del alcance de esta tarea puntual;
  `biome check` sobre `hot.js` reporta cientos de avisos preexistentes de estilo `var`/scoping en
  código legado que esta tarea no tocó ni se le pidió limpiar).

## Fix post-revisión (2026-08-24)

Revisión encontró dos hallazgos:

**Important — `beforeUndoStackChange` es global, faltaba verificar Ctrl+Z en columna normal.**
Verificado en el navegador real (`Optimización Aeropuerto JMC`, test.R, columna `Observaciones`
—columna de texto libre, real, no fundida por la Task 4—): edición desde la celda (doble clic,
escribir, Enter) sigue guardando por el mismo `saveRow`/`POST /api/pi/save` de siempre, y la pila
de deshacer (`getPlugin('undoRedo').doneActions`) sigue registrando la acción y revirtiéndola
correctamente con `hot.undo()` — la misma función que invoca el atajo nativo `Ctrl+Z`
(`gridContext.addShortcuts([{ keys: [['Control/Meta','z']], callback: () => this.undo() }])`,
`node_modules/handsontable/dist/handsontable.js:92388`). Repetido contra el commit previo a esta
tarea (`03ee2380`, `hot.js` sin `beforeUndoStackChange`) con idéntico resultado: el mecanismo no
cambió.

**Nota metodológica, no ocultada:** el keydown sintético de Ctrl+Z de Playwright
(`page.keyboard.press('Control+z')`, probado también con `down/press/up` con retardos, con foco
real en la celda vía clic, y con `locator.press` apuntado directo a la celda) **no llega al
`ShortcutManager` de Handsontable en este entorno headless** — reproducido de forma idéntica en el
commit previo a la Task 7, así que es una limitación de la herramienta de prueba en este entorno,
no algo introducido por `beforeUndoStackChange` ni por ningún cambio de esta tarea. Por eso la
prueba nueva (`tests/browser/pi-undo-columna-normal.mjs`) ejerce `hot.undo()` directamente —la
misma función que ese atajo invoca— en vez de simular la tecla; es la verificación honesta y
determinista de lo mismo que pediría un Ctrl+Z real. (El Ctrl+Z del globo, en `pi-globo-guardado.mjs`,
sí funciona vía keydown sintético porque el globo escucha `keydown` a nivel de `document`
directamente, sin pasar por el `ShortcutManager`/contexto `grid` de Handsontable — por eso ese
camino no tiene el mismo problema de enrutamiento.)

El guard en sí ya era preciso desde la implementación original
(`return source !== 'internal-update';` — deja pasar cualquier otro source sin tocarlo), así que no
hizo falta cambiarlo; lo que faltaba era la verificación explícita en navegador real, ahora hecha y
con prueba propia.

**Minor — comentario desalineado en `readiness-popover.js:296`.** Decía que Ctrl+Z "reenvía a
`hot.undo()`", pero el código llama a `estado.datosFila.deshacer()` (el deshacer propio de un solo
nivel del globo, no la pila nativa). Corregido para describir lo que el código realmente hace y por
qué: la limitación de `propToCol` sobre props fundidas por la Task 4, ya explicada en la sección
"Ctrl+Z (Step 7)" de este informe.

### Archivos tocados en el fix
- `public/js/design-system/readiness-popover.js` (comentario corregido, sin cambio de comportamiento)
- `tests/browser/pi-undo-columna-normal.mjs` (nuevo)

### Verificación

```
$ node tests/browser/pi-globo-guardado.mjs
OK: una peticion, mismo endpoint, avance en vivo y globo abierto
OK: Ctrl+Z deshace lo que el globo guardo

$ node tests/browser/pi-undo-columna-normal.mjs
OK: hot.undo() nativo sigue revirtiendo una columna normal (Observaciones) tras beforeUndoStackChange

$ node tests/browser/pi-globo-teclado.mjs
OK: abre, enfoca, cierra y devuelve el foco
OK: el clic afuera cierra el globo y devuelve el foco

$ node tests/browser/pi-ancho-presupuesto.mjs
OK: 1100 px en 1100, 0 celdas recortadas
```
