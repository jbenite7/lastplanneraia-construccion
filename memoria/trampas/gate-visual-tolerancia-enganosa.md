---
tipo: trampa
estado: vigente
fecha: 2026-07-29
areas: [qa, design-system]
fuente: memoria-claude
origen: lps-aia-browser-qa-pitfalls
resumen: "El gate visual puede pasar en verde con un rediseño real si la tolerancia es amplia; hoy maxDiffPixelRatio está en 0.002 por este mismo hallazgo, y tras un cambio visual hay que regenerar goldens con --update-snapshots=all"
---
**El gate visual puede pasar en verde con un rediseño real cuando la tolerancia es amplia**
(medido con `maxDiffPixelRatio` en 0.03): el rediseño
completo de la toolbar/leyenda del PG (2026-07-22) midió solo 2,6% de píxeles distintos (el fondo
oscuro uniforme domina) — un golden obsoleto no siempre falla. Tras un cambio visual intencional,
regenerar goldens deliberadamente con `--update-snapshots=all` (el default `changed` NO reescribe si
el diff cae dentro de tolerancia) y actualizar los `sha256` del manifiesto.

**Corregido después, sin invalidar la lección** (verificado el 2026-08-06): la tolerancia bajó de
0.03 → 0.005 → **0.002**, y el propio archivo cita este hallazgo como motivo
(`playwright.config.mjs:25`, y los dos specs de rejilla `programa-general.visual.mjs:95` y
`programacion-intermedia.visual.mjs:95`). La cifra 0.03 es histórica; lo que sigue vigente es que
un verde del gate no prueba que el golden esté al día.

**Medido el 2026-08-06: 0.002 ya está cerca del suelo de ruido, no sobra margen.** El comentario de
`playwright.config.mjs:22-24` afirma que «con la tolerancia en 0, tres corridas seguidas sin tocar
nada no produjeron ni un pixel de diferencia». Se comprobó bajando el piso a `0` y corriendo dos
veces `design-system-lab.visual.mjs` + `lps-drawer-design-system.mjs`: **dos escenarios sí difieren
sin que nadie toque el código** — `data-display-dark-1180x820` con 141 px (ratio 0,000146) y
`shell-navigation-dark-1440x900` con 1720 px (ratio 0,001327). El piso vigente de 0,002 deja apenas
1,5× de holgura sobre ese peor caso, así que **apretarlo más produce falsos rojos**, no más rigor.
La deuda real no es la cifra: es averiguar por qué esos dos escenarios no son deterministas.

**Why:** un gate visual en verde no prueba que el golden esté al día, y un gate demasiado apretado
tampoco prueba nada porque falla solo. **How to apply:** tras un cambio visual intencional,
regenerar con `--update-snapshots=all` explícitamente y actualizar los `sha256` del manifiesto; y
antes de proponer bajar `maxDiffPixelRatio`, medir el ruido con el piso en `0` — el número correcto
depende del peor escenario no determinista, no del gusto.

Relacionado: [[bitacora-drawer-sin-profesional]], [[reset-legacy-pisa-adaptadores]].
