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

**Why:** un gate visual en verde no prueba que el golden esté al día. **How to apply:** tras un
cambio visual intencional, regenerar con `--update-snapshots=all` explícitamente y actualizar los
`sha256` del manifiesto; no confiar en que el gate lo detecte solo.

Relacionado: [[bitacora-drawer-sin-profesional]], [[reset-legacy-pisa-adaptadores]].
