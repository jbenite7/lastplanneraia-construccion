---
tipo: trampa
estado: vigente
fecha: 2026-08-03
areas: [design-system, qa]
fuente: sesion
resumen: "El guard de «un matiz por estado» lee state-semantics.json y comprueba que no se repita hue dentro de ese mismo archivo; nunca abre el CSS, así que siempre está verde — la divergencia que lo destapó se pagó el 2026-08-06, el guard no se enteró de ninguna de las dos cosas"
---
`tests/design-system/state-tint-ladder.test.mjs:170` («ningun modulo asigna el mismo
matiz a dos estados») recorre `semantics.moduleMappings` y cuenta repeticiones de `hue`
**dentro del propio `state-semantics.json`**. Es la declaración validándose contra sí
misma: mientras el JSON esté bien escrito, el test pasa aunque el CSS pinte lo que
quiera.

La divergencia que lo destapó, medida el 2026-08-03: el contrato declara para
`programa-general` «Actividad Futura» → `green` y «En Curso» → `blue`
(`docs/design-system/state-semantics.json:199-212`, que sigue igual), y la grilla
pintaba **las dos** con `--ds-cell-state-ok-bg` / `-fg` — el mismo color, 8,88:1 en
ambas.

**Esa divergencia concreta ya está pagada** (verificado el 2026-08-07, y el guard siguió
verde todo el tiempo, que es justo el punto de esta nota): `51ccd5ca` llevó el chip de
estado al canal de matiz, `public/js/modules/programa_general/hot.js:804` emite
`data-aia-hue="<hue>"` sobre el `<span class="ops-state-chip">` que pinta `:1658`, y
`public/css/design-system/adapters/legacy-bridge.css:114-127` da a `blue` y a `green`
fondos distintos (`--ds-state-tint-blue` vs. `--ds-state-tint-green`). Las citas viejas
`styles.css:3516` y `:3541` ya no existen.

Queda vivo el tercer sitio: `public/js/modules/shared/cell-state-vocabulary.mjs:18-22`
sigue mandando los dos alias a `CELL_STATE.OK`. Matiz nuevo del pase: la clase que ese
mapa produce, `ds-cell-ok`, **no la declara ninguna hoja** — es el único sitio del repo
donde aparece ese literal, así que hoy no pinta nada.

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
