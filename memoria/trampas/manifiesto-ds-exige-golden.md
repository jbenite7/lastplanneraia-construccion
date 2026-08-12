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

**Matiz del 2026-08-12, y no es menor:** esa regla es hoy la **rama por defecto**, no la única. El
schema (`module-manifest.schema.json:105`) explica que el mínimo **no** se fija con `minItems`
porque depende de `visualEvidence`: sin `visualEvidence` el gate exige al menos un escenario; **con**
`visualEvidence` el módulo delega su evidencia en una familia de `homologation.json` y `scenarios`
debe quedar **vacío**. Las dos ramas las aplica `scripts/design-system-contracts.mjs:727,769-811`,
con su lista blanca de delegación. O sea: puede haber manifiesto sin golden propio, pero solo por
delegación declarada — no «en seco».

Dos trampas más al añadir manifiestos:
- `tests/design-system/contracts.test.mjs` tiene un **censo cerrado** de `inventory.manifests` con
  `deepEqual`: cada manifiesto nuevo obliga a actualizar esa lista.
- **No declares `consumerContract: "v1"` en una superficie sin migrar**: v1 prohíbe `<style>`,
  `style=`, hex y `exceptions[]` no vacío. Se añade después de limpiar la vista.
