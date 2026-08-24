# Task 8 — recorrer actividades sin cerrar el globo

## Qué se hizo

- `public/js/design-system/readiness-popover.js`:
  - `construirCabecera()` ahora agrega `construirNavegacion()`: dos botones
    `.aia-readiness-popover__anterior` / `.aia-readiness-popover__siguiente`
    dentro de la cabecera del globo.
  - `irA(direccion)` implementado: si hay `estado` (globo abierto), invoca
    `estado.datosFila.anterior()` o `estado.datosFila.siguiente()` según el
    signo. Si esas funciones no existen o no hacen nada (no hay destino), el
    globo se queda como está — nunca se cierra ni se vacía.
  - `alTeclado()` gana un caso para `ArrowUp` / `ArrowDown` que llama a
    `irA(-1)` / `irA(1)`.
  - `emparejarAncla()` corregido: **bug real encontrado durante la
    verificación** (ver abajo) — ahora reasigna `celda.style.anchorName` en
    cada apertura, no solo la primera vez.
- `public/js/modules/programacion_intermedia/hot.js`:
  - `encontrarFilaHabilitacion(visualRowInicial, direccion)`: recorre filas
    visuales en la dirección pedida, usando la MISMA `getPIRowMeta(...).isHeader`
    que ya usa el renderer de la Task 4 para saltarse capítulos. Devuelve
    `null` en el extremo de la tabla o si solo quedan capítulos.
  - `abrirGloboHabilitacion()` gana `irAFila(direccion)`: busca la fila
    destino, la trae a la vista (`hot.scrollViewportTo` + `hot.render()` +
    `celdaNueva.scrollIntoView(...)`, ver bug abajo) y reabre el globo entero
    sobre la celda nueva llamando de nuevo a `abrirGloboHabilitacion` — así
    hereda intacto lector de estado, permisos, guardado y deshacer de la fila
    nueva sin duplicar esa lógica.
  - `datosFila.siguiente` / `datosFila.anterior` añadidos al paquete que ya
    arma la Task 7, apuntando a `irAFila(1)` / `irAFila(-1)`.
- `public/css/design-system/components/readiness-popover.css`: estilos con
  tokens para `.aia-readiness-popover__nav`, `__anterior`, `__siguiente`
  (mismo patrón que `__reintentar`, ya existente). Cero hex, cero inline.
- `tests/browser/pi-globo-recorrido.mjs`: prueba tal como la trae el brief,
  con una nota — igual que la Task 7 en `pi-globo-guardado.mjs` — cambiando
  el proyecto de `PDC Sandbox E2E` (una sola fila, sin segunda actividad a la
  que saltar) a `Optimización Aeropuerto JMC` (39 actividades reales, mismo
  `test.R`), documentada en el propio archivo.

## Cómo se saltan las filas de capítulo

`encontrarFilaHabilitacion` recorre filas visuales una a una en la dirección
pedida y, para cada una, resuelve `getPIRowMeta(physicalRow, rowData).isHeader`
(la misma detección que usa `piHabilitacionRenderer` desde la Task 4 — no hay
una segunda regla de "qué es capítulo"). La primera fila no-capítulo que
encuentra es el destino; si llega al extremo de la tabla sin encontrar
ninguna, devuelve `null` y el globo no se mueve.

## Bugs reales encontrados y corregidos durante la verificación (no en el brief)

1. **Anchor CSS perdido al reciclar `<td>`.** Con `renderAllRows: false`
   (`hot.js:4448`), Handsontable recicla el mismo nodo `<td>` para otra fila
   al hacer scroll, y su `TextRenderer` de base limpia `style` en cada
   reciclado (comentario ya existente junto a `piRestrictionRenderer`,
   `hot.js:3542`). `emparejarAncla()` solo asignaba `celda.style.anchorName`
   la PRIMERA vez que veía esa celda (guardaba el nombre en
   `celda.dataset.aiaAncla`, que sí sobrevive porque no es `style`, y
   reusaba ese nombre sin reponer el `anchor-name` real). Tras 12-13 saltos,
   el `<td>` reciclado ya no tenía `anchor-name` en su `style`, así que el
   globo apuntaba a un ancla inexistente. Corregido: `emparejarAncla()`
   reasigna `celda.style.anchorName = nombre` en cada apertura.
2. **Fila destino fuera de la vista de página, no solo del viewport virtual
   de Handsontable.** Esta tabla no tiene su propio contenedor con scroll
   interno para las filas que importan aquí: la página crece y el scroll es
   de documento. `hot.scrollViewportTo()` puede devolver `false` (la fila
   "ya cabe" en el viewport virtual de Handsontable) mientras la fila sigue
   por debajo del borde inferior de la ventana. Cuando el `<td>` ancla queda
   clippeado fuera de vista, Chrome oculta automáticamente el popover
   anclado a él (comportamiento del motor de anchor-positioning) aunque
   `:popover-open` siga siendo verdadero y `opacity` sea `1` — parecía un
   clic interceptado por la grilla y era el globo invisible. Corregido:
   tras `hot.scrollViewportTo` + `hot.render()`, se llama además a
   `celdaNueva.scrollIntoView({ block: 'nearest', inline: 'nearest' })`
   sobre el `<td>` real antes de reabrir el globo.

Ambos se detectaron reproduciendo el loop de 60 clics del propio test contra
el navegador real (no solo leyendo código), inspeccionando
`getBoundingClientRect`, `elementFromPoint` y capturas de pantalla en los
puntos de falla.

## Salida real de la prueba

**Antes (Step 2, con el proyecto del brief, `PDC Sandbox E2E`):**
```
$ node tests/browser/pi-globo-recorrido.mjs
locator.click: Timeout 30000ms exceeded.
Call log:
  - waiting for locator('.aia-readiness-popover__siguiente')
```
(el botón no existía — `irA()` estaba vacío, Step 3 sin implementar)

**Tras cambiar el proyecto pero antes del Step 3 (para confirmar que la
prueba rojo era por falta de implementación y no por datos):** mismo timeout,
el selector `.aia-readiness-popover__siguiente` no existe en el DOM.

**Tras implementar Step 3, primera vuelta (con el bug #1 sin corregir):**
```
$ node tests/browser/pi-globo-recorrido.mjs
AssertionError [ERR_ASSERTION]: el globo no cambio de actividad
```
(usando el proyecto sin datos suficientes — se corrigió cambiando de proyecto,
igual que hizo la Task 7)

**Con el proyecto correcto, aún con bugs #1 y #2 sin corregir:**
```
locator.click: Timeout 30000ms exceeded.
  - <span data-restriccion="MdeO" class="aia-readiness__box aia-readiness__box--met">…</span>
    from <main class="hot-full-bleed">…</main> subtree intercepts pointer events
```

**Final, tras corregir ambos bugs (Step 4, tres corridas seguidas):**
```
$ node tests/browser/pi-globo-recorrido.mjs
OK: recorre sin cerrarse y no se vacia al final

$ node tests/browser/pi-globo-recorrido.mjs   (repetido 2 veces más)
OK: recorre sin cerrarse y no se vacia al final
```
Exit code 0 en las tres corridas.

**Regresión de la Task 7 (mismo módulo, no debía romperse):**
```
$ node tests/browser/pi-globo-guardado.mjs
OK: una peticion, mismo endpoint, avance en vivo y globo abierto
OK: Ctrl+Z deshace lo que el globo guardo
```

**Sanity manual de teclado (no cubierto por el test del brief, verificado
aparte):** `ArrowDown` cambia el título, `ArrowUp` vuelve al original,
`.aia-readiness-popover__anterior` en la primera actividad no cierra el
globo (no hay destino).

## Dudas / notas para quien revise

- El brief traía `PDC Sandbox E2E` como proyecto, igual que el brief de la
  Task 7. Ese proyecto solo tiene UNA fila de PI real (`Actividad: ''`), así
  que la prueba de recorrido no puede pasar ahí con ninguna implementación.
  Reutilicé el mismo cambio de proyecto que ya documentó la Task 7
  (`Optimización Aeropuerto JMC`, 39 actividades, mismo `test.R`) — no es una
  decisión nueva, es aplicar el mismo precedente ya aceptado.
- No toqué el desacuerdo preexistente de comillas simples/dobles que reporta
  `biome check` sobre `readiness-popover.js` (2 errores, 3 warnings, 1 info):
  es formato de todo el archivo desde antes de esta tarea, no algo que
  introduje, y está fuera del alcance de "flechas para recorrer actividades".
- No agregué una prueba de teclado (ArrowUp/ArrowDown) como archivo separado
  porque el brief no la pidió como Step 1 — la verifiqué manualmente contra
  el navegador real y quedó documentada arriba. Si se quiere blindarla con
  Playwright, es un pendiente menor, no bloqueante.
