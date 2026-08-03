---
tipo: trampa
estado: vigente
fecha: 2026-07-29
areas: [qa, design-system]
fuente: memoria-claude
origen: lps-aia-browser-qa-pitfalls
resumen: "El gate visual (maxDiffPixelRatio 0.03) puede pasar en verde con un rediseño real; regenerar goldens con --update-snapshots=all"
---
**El gate visual (maxDiffPixelRatio 0.03) puede pasar en verde con un rediseño real**: el rediseño
completo de la toolbar/leyenda del PG (2026-07-22) midió solo 2,6% de píxeles distintos (el fondo
oscuro uniforme domina) — un golden obsoleto no siempre falla. Tras un cambio visual intencional,
regenerar goldens deliberadamente con `--update-snapshots=all` (el default `changed` NO reescribe si
el diff cae dentro de tolerancia) y actualizar los `sha256` del manifiesto.

**Why:** un gate visual en verde no prueba que el golden esté al día. **How to apply:** tras un
cambio visual intencional, regenerar con `--update-snapshots=all` explícitamente y actualizar los
`sha256` del manifiesto; no confiar en que el gate lo detecte solo.

Relacionado: [[bitacora-drawer-sin-profesional]], [[reset-legacy-pisa-adaptadores]].
