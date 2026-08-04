---
tipo: trampa
estado: vigente
fecha: 2026-07-27
areas: [qa, design-system]
fuente: memoria-claude
origen: lps-aia-lab-desktop-layout-suite
resumen: design-system-lab-desktop-layout.mjs corre en el carril evidence/reflow, fuera de runtime; volvio a estar roja en la asercion de 44px hasta el 2026-08-04
---
`tests/browser/design-system-lab-desktop-layout.mjs` NO lo ejecuta
`npm run test:design-system:runtime`: `tests/design-system/accessibility.test.mjs:273`
asierta explicitamente que runtime **no** casa con `/keyboard|reflow|desktop-layout/`.
Vive solo en `test:design-system:evidence` y `test:reflow`. Por eso estuvo roja
meses sin aparecer en ninguna lista de rojos conocidos: ningun gate habitual la
corre.

Nacio en `750e24a` (2026-07-19) ya roja en su asercion de target 44px, y se puso
roja tambien en la de sticky con `5134ae2`. Verde 4/4 el 2026-07-27, pero el
2026-08-04 se la encontro **otra vez roja** en la asercion de 44px, en los dos
viewports: el rail colapsado dejaba 38px utiles y ningun objetivo podia llegar
a 44 (causa y arreglo en [[rail-colapsado-no-llega-a-44]]). Nadie lo habia
reportado, coherente con lo de arriba: ningun gate habitual la corre. Verde 4/4
de nuevo desde el 2026-08-04.

**Why:** una suite fuera del carril de gates puede commitearse roja y quedarse
roja indefinidamente.

**How to apply:**
- Antes de tratar un rojo de esta suite como regresion, comprueba si la asercion
  fue verde alguna vez: una nacio roja y otra si regreso
  (ver [[lab-sticky-body-overflow]]).
- Al correrla, acuerdate de anadirla al carril que uses: `test:design-system:runtime`
  por contrato no la incluye.
- El nombre del test dice "97px laboratory header" pero el header mide 104px; el
  offset ya no sale de una formula de tokens sino medido por ResizeObserver en
  `design_system_lab.js` (ver [[lab-header-offset-medido]]).
