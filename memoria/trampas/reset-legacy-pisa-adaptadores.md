---
capa: wiki
tipo: trampa
estado: vigente
fecha: 2026-07-29
areas: [design-system]
fuente: memoria-claude
origen: lps-aia-browser-qa-pitfalls
resumen: "Reset legacy pisa adaptadores: el spacing de adaptadores del design system debe ir en @layer legacy-overrides, no en components"
---
**Reset legacy pisa adaptadores**: `styles.css` entra como `layer(module)` y su
`* {margin/padding:0}` (capa `module.reset`) gana a `@layer components`; el spacing de adaptadores
del design system debe ir en `@layer legacy-overrides` (patrón de `bi-figure.css:303`).

**Why:** un adaptador que declara su spacing en `components` pierde silenciosamente contra el reset
legacy. **How to apply:** al adaptar un módulo legacy al design system, declarar el spacing en
`@layer legacy-overrides`, siguiendo el patrón de `bi-figure.css`. El otro ejemplo que citaba esta
nota, `semi-auto-review.css`, se retiró el 2026-08-04 con el PDC v1 y ya no existe.

Relacionado: [[pdc-legend-item-clase-compartida]], [[gate-visual-tolerancia-enganosa]].
