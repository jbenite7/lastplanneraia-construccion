---
tipo: trampa
estado: vigente
fecha: 2026-08-03
areas: [design-system, qa]
fuente: sesion
resumen: "El guard de «un matiz por estado» lee state-semantics.json y comprueba que no se repita hue dentro de ese mismo archivo; nunca abre el CSS, así que siempre está verde"
---
`tests/design-system/state-tint-ladder.test.mjs:170` («ningun modulo asigna el mismo
matiz a dos estados») recorre `semantics.moduleMappings` y cuenta repeticiones de `hue`
**dentro del propio `state-semantics.json`**. Es la declaración validándose contra sí
misma: mientras el JSON esté bien escrito, el test pasa aunque el CSS pinte lo que
quiera.

La divergencia real, medida el 2026-08-03: el contrato declara para `programa-general`
«Actividad Futura» → `green` y «En Curso» → `blue`; `public/css/styles.css:3516` y
`:3541` pintan **las dos** con `--ds-cell-state-ok-bg` / `-fg`. El mismo par, 8,88:1 en
ambas porque es el mismo color. Antes del goal de tablas eran dos colores distintos.

Hay un tercer sitio con el mismo fallo: `public/js/modules/shared/cell-state-vocabulary.mjs`
manda los dos alias a `CELL_STATE.OK`.

**Why:** al migrar la grilla a `--ds-cell-state-*` se tradujo por **nivel**, y ambos
estados son `healthy`. Traducir por nivel descarta el canal de matiz — que es justo el
eje que la regla existe para conservar (ver [[design-system]] y la escala de dos canales
en `DESIGN.md` §2). Lo agrava la leyenda: `--pg-dot-future` deriva de `success` y
`--pg-dot-progress` de `info`, así que **sí** se distinguen en la leyenda. El usuario
aprende un código de color que la grilla incumple, que es peor que no distinguir en
ninguno de los dos sitios.

**How to apply:** un guard sobre un archivo de declaración solo prueba que la
declaración es coherente. Para que pruebe algo sobre el producto tiene que cruzar dos
fuentes —la declaración y el consumidor— o medir el valor resuelto en navegador. Al
escribir un guard, pregúntate qué archivo tendría que estar mal para que fallara: si la
respuesta es «el mismo que lee», no vigila nada.

Salió al dar filas reales al mock de la prueba visual de Programa General; con la grilla
vacía era invisible — el mismo filo que [[gate-estatico-no-ve-tokens-rotos]].
Emparentada también con [[comentario-de-token-afirma-uso-inexistente]]: las tres son
fuentes que se leen con buena fe y afirman algo que el código no cumple.
