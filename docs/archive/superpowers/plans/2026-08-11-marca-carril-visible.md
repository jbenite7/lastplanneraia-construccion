# Plan — la aserción de la marca comprueba que se vea

- Spec: [`2026-08-11-marca-carril-visible-design.md`](../specs/2026-08-11-marca-carril-visible-design.md)
- Frente: `severidad-runtime` · sesión 06e4383d · sha de arranque `b509e90e`

## Archivos

- `tests/browser/design-system-lab.mjs` — la aserción y su porqué.
- `docs/EXPERIMENTS.md` — cerrar la ficha de la línea 88.

**No se toca** `navigation.css` (el diseño ya decidió), ni `closeout-evidence.json` (el recibo es
de la coordinadora), ni nada del carril `runtime` más allá de este caso.

## Paso 1 — sustituir la línea 448

Fuera:

```js
await expect(logo).not.toHaveCSS('filter', 'none');
```

Dentro, con el porqué pegado:

```js
// La marca ya NO se tiñe con el tema, y eso es diseño, no regresion: `4437fcfa`
// cambio `filter: var(--ds-active-nav-mark-filter)` por `filter: none` en
// navigation.css:172-178 porque «el icono Construccion es a color; no se tiñe
// con el tema». Exigir el filtro era exigir el MEDIO viejo en vez del fin.
//
// El fin es el que da nombre a este test: que la marca este y se reconozca. Se
// comprueba en PANTALLA y no en el DOM -`querySelector` encuentra igual de bien
// lo que se ve y lo que no-, y cubre lo que la asercion vieja no cubria: un SVG
// roto que no pinta nada declaraba su `filter` igual y pasaba en verde.
const marca = await logo.evaluate((el, railSel) => {
  const r = el.getBoundingClientRect();
  const cs = getComputedStyle(el);
  const centro = document.elementFromPoint(r.left + r.width / 2, r.top + r.height / 2);
  const carril = el.closest(railSel).getBoundingClientRect();
  return {
    cargo: el.naturalWidth > 0 && el.naturalHeight > 0,
    ancho: r.width, alto: r.height,
    display: cs.display, visibility: cs.visibility, opacidad: Number(cs.opacity),
    destapada: centro === el || el.contains(centro) || centro?.contains(el),
    dentroDelCarril: r.left >= carril.left - 1 && r.right <= carril.right + 1
      && r.top >= carril.top - 1 && r.bottom <= carril.bottom + 1,
  };
}, '[data-shell-pattern="sidebar"]');

await expect(logo).toHaveCount(1);
expect(marca.cargo, 'la marca no cargó: el SVG está vacío o no resuelve').toBe(true);
expect(marca.ancho, 'la marca no ocupa ancho').toBeGreaterThan(0);
expect(marca.alto, 'la marca no ocupa alto').toBeGreaterThan(0);
expect(marca.display, 'la marca está en display:none').not.toBe('none');
expect(marca.visibility, 'la marca está en visibility:hidden').not.toBe('hidden');
expect(marca.opacidad, 'la marca es transparente').toBeGreaterThan(0.1);
expect(marca.destapada, 'algo tapa la marca en el centro de su caja').toBe(true);
expect(marca.dentroDelCarril, 'la marca cae fuera de la caja del carril').toBe(true);
```

## Paso 2 — cerrar la ficha de `EXPERIMENTS.md:88`

Su última columna pasa de `abierto` a cerrado, con la respuesta a la pregunta que ella misma
exigía —«medir si la marca se ve mal de verdad»— y las tres mitades: se lee; su silueta está en
1,67:1 contra un piso de 3:1; y ese piso no le aplica porque el `<img>` está declarado decorativo
(`alt=""` + `aria-hidden="true"`, `DesignSystemComponent.php:431`). Con el sha del cierre y la
nota de que lo decidió el usuario **viendo las capturas**.

## Paso 3 — mutación, ejecutada y en pantalla

Dos mutaciones, porque prueban cosas distintas:

- **a. La marca desaparece:** ocultarla en el fixture y correr. Debe **fallar**. Si pasa, la
  aserción mira el DOM y no la pantalla, y no vale.
- **b. La marca queda tapada:** superponerle un elemento opaco. Debe **fallar** por `destapada`.
  Es la que distingue «está» de «se ve», que es justo lo que la vieja no separaba.

Ambas se revierten y se vuelve a correr para ver el verde. Se entrega con la salida real de las
tres corridas, no con la promesa.

## Paso 4 — verificación

- El caso, en verde: `npx playwright test tests/browser/design-system-lab.mjs -g "sidebar shell"`.
- La primera etapa entera de `runtime`, para declarar cuánto queda medido y cuánto no.
- Estática y `test:wiki`, re-ejecutadas **después** de integrar.

## Condición de hecho

La aserción comprueba que la marca se ve —seis comprobaciones donde había una—, su porqué queda
escrito al lado, la ficha cierra con la respuesta, y las dos mutaciones se ejecutan y fallan.
**No se pretende poner `runtime` en verde**, y se declara qué queda sin medir por el `&&`.
