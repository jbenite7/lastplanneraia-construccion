---
tipo: trampa
estado: vigente
fecha: 2026-07-29
areas: [design-system, qa]
fuente: memoria-claude
origen: lps-aia-manifiesto-ds-exige-golden
resumen: un manifiesto de módulo del DS no se puede crear «en seco» — el schema exige un scenario con golden real y sha256 que case
---
En lps-aia, `docs/design-system/module-manifest.schema.json` exige `scenarios` con `minItems: 1`, y
cada escenario exige `golden` (PNG que exista en disco) y `sha256` que case con el archivo
(verificado en `scripts/design-system-contracts.mjs` y `scripts/design-system-consumer-contract.mjs`).

**Why:** el spec F2 del goal [[goal-dark-mode-todos-modulos]] ordena «crear el manifiesto»
como paso 1 y «evidencia visual» como paso 6, y eso no se puede ejecutar en ese orden.

**How to apply:** crear un manifiesto es siempre manifiesto + captura dark 1180×820 contra el
contenedor, en el mismo paso. Los goldens viven en `tests/browser/__screenshots__/<moduleId>/`
(decisión del usuario, 2026-07-29), no en `evidence/`.

Dos trampas más al añadir manifiestos:
- `tests/design-system/contracts.test.mjs` tiene un **censo cerrado** de `inventory.manifests` con
  `deepEqual`: cada manifiesto nuevo obliga a actualizar esa lista.
- **No declares `consumerContract: "v1"` en una superficie sin migrar**: v1 prohíbe `<style>`,
  `style=`, hex y `exceptions[]` no vacío. Se añade después de limpiar la vista.
