# Plan — ocultar las etiquetas contadoras que marcan cero

- Spec: [`2026-08-11-contadores-cero-design.md`](../specs/2026-08-11-contadores-cero-design.md)
- Frente: `contadores-cero` · sesión 06e4383d · worktree `beautiful-blackwell-414f09`
- Sha de arranque: `de02471a`

## Archivos que se tocan (dos, y nada más)

- `public/js/modules/programacion_intermedia/hot.js`
- `public/css/programacion-intermedia.css`

No se toca la vista: las ocho etiquetas siguen en el HTML. No se toca el manifiesto ni ningún
golden (D-CERO-1, escalado y sin resolver).

## Paso 1 — la constante y la condición (hot.js)

Junto a `var activeFilters = [];` (línea 18), el único punto de reversión:

```js
/* Frente contadores-cero. En false vuelve el atenuado de C-24 y nada mas. */
var OCULTAR_CONTADORES_EN_CERO = true;
```

En `setLegendCount` (línea ~2886), que ya calcula `count` y ya pone `is-zero`, se añade la
segunda clase, con la guarda que distingue vacío de cero-bajo-filtro:

```js
function setLegendCount(key, value) {
  var count = Number(value) || 0;
  /* `is-zero` atenua (C-24). `is-empty` ademas oculta, y solo cuando el cero
     significa vacio de verdad: sin filtros activos el conteo cubre el conjunto
     entero, con un filtro puesto solo cubre la vista y ocultar encerraria al
     usuario sin forma de cambiar de filtro. */
  var esVacioReal = count === 0 && activeFilters.length === 0;
  $('#count-' + key)
    .text('(' + value + ')')
    .closest('.pdc-legend-item')
    .toggleClass('is-zero', count === 0)
    .toggleClass('is-empty', OCULTAR_CONTADORES_EN_CERO && esVacioReal);
}
```

## Paso 2 — la regla (programacion-intermedia.css)

Una sola regla, pegada a la de C-24 y con su porqué. Mismo `@layer components` + `!important`
que ya usa C-24 en este archivo, por el contrato `unlayered-delivery`:

```css
html.aia-theme-dark body.pi-page .pi-legend .pdc-legend-item.is-empty {
    display: none !important;
}
```

`display:none` y no `visibility`/`opacity`: el objetivo es que **no ocupe sitio** y que el botón
salga del orden de tabulación. Atenuar ya lo hace `is-zero`, y confundir ambos estados es
justamente lo que este frente viene a deshacer.

## Paso 3 — reconteo al cambiar de filtro

`toggleLegendFilter` ya dispara el recálculo que llama a `setLegendCount`, así que la guarda se
reevalúa sola al poner y quitar filtros. **Se verifica, no se supone:** paso 4, caso b.

## Paso 4 — verificación (1180×820 dark, sesión real por la puerta de servicio)

Con contenedor propio montando **este** worktree — el `docker compose` del repo sirve el árbol
principal y enseñaría el archivo viejo (`memoria/trampas/aislar-stack-docker-por-worktree.md`).

- **a. Sin filtros:** el instrumento de conteo devuelve `legendVisibles: 1` y el total de
  controles visibles baja desde 63.
- **b. Con un filtro activo:** `legendVisibles: 8`, ninguno oculto, y se puede pasar de un filtro
  a otro. Este es el caso que prueba la guarda.
- **c. Mutación que lo pone rojo, ejecutada:** con `OCULTAR_CONTADORES_EN_CERO = false` el
  conteo vuelve a 8 y a 63. Demuestra que la reversión es real y que el instrumento sabe fallar.
- **d.** `npm run test:design-system:static` comparado contra la base **7/8** de `de02471a`
  (`node-tests` ya rojo por un test de mtime, no por contenido).
- **e.** `npm run check:frontend` comparado contra su base preexistente en rojo (~863 errores),
  no leído como regresión propia.

## Lo que queda fuera

D-CERO-1 (goldens, bloqueante y escalado), D-CERO-2 (`0 selec.`), D-CERO-3 (las otras dos
pantallas). Todo en `decisiones/contadores-cero.md`.

## Condición de hecho

Menos elementos en pantalla, medido con número antes y después; la guarda del filtro verificada;
la reversión verificada; y `static` sin regresión contra 7/8.
