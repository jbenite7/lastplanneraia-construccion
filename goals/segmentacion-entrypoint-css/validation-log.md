# Validation log — segmentación entrypoint CSS

## Baseline (Task 1)

- Fecha: 2026-07-22
- Hash normalizado de /runtime/css/aia-design-system.css (before): `241ea522685bb895858fc45c5280c98934235b111eb698e029ca160ced7761a4`
- Evidencia before: docs/design-system/evidence/entrypoint-segmentation/{project-selector,auth}/before/
- Axe violations before (project-selector /proyectos): 0
- Axe violations before (auth /login, /password/forgot, /password/reset): 2, 2, 2

## Task 5 — project-selector migrado a `renderForModule`

- Fecha: 2026-07-22
- Vista: `views/core/project_selector.view.php` reemplaza `renderStylesheet('/css/tokens.css')` +
  `renderStylesheet('/css/aia-design-system.css')` por
  `DesignSystemHeadComponent::renderForModule('project-selector')`. `dark-mode.css` y
  `project-selector.css` quedan como estaban.
- Smoke (`tests/browser/design-system-consumer-smoke.mjs`):
  - Antes de migrar: `the 15 shared-head consumers…` PASS; `project selector loads the segmented
    core…` FAIL (`link[href^="/runtime/css/design-system/entrypoints/core.css"]` count 0 — la vista
    aún cargaba el agregador). Confirma que el fix del selector stale (`/runtime/css/...` en vez de
    `/css/...`) es correcto y que el nuevo assert detecta la superficie no migrada.
  - Después de migrar + rebuild del contenedor: ambos tests PASS.
- Gates estáticos:
  - `node scripts/design-system-entrypoint-partition.mjs`: PASS.
  - `node scripts/design-system-consumer-contract.mjs`: PASS (`Design system consumer contracts:
    PASS (1 manifiesto/s v1)`), valida `project-selector` contra el contrato v1 vía
    `renderForModule`.
  - `npm run test:design-system:static`: 2 rojos, ambos preexistentes/no atribuibles a esta tarea:
    `laboratory-hardening` doc-drift (tolerado, ver Task 4) y `canonical design-system contracts
    pass the executable gate` — este último falla únicamente por `activation: worktree and index
    must be clean` (árbol de trabajo con cambios sin commitear); verificado con `git stash -u` que
    sobre el HEAD limpio (605ebf4) el mismo comando produce `Design system contracts: PASS`, es
    decir el rojo es un artefacto transitorio del WIP y no una regresión.
  - Hallazgo intermedio (corregido): el manifiesto de `project-selector` ahora referencia
    `tests/browser/design-system-consumer-smoke.mjs` y
    `tests/browser/entrypoint-segmentation-dryrun.mjs`; el fixture efímero de
    `tests/design-system/closeout-contract-fixture.mjs` (lista `referencedTests`) no copiaba esos
    dos archivos, produciendo `missing test …` en
    `committed structured receipts activate in a clean temporary Git repository`. Se agregaron
    ambos paths a `referencedTests`; el test vuelve a pasar (18/18 en
    `closeout-receipts.test.mjs`).
- Dry-run after (`DRYRUN_SURFACE=project-selector DRYRUN_PHASE=after`):
  - `stylesheets.json`: `links` en `/proyectos` (ambos viewports) = `core.css`, `tokens.css`,
    `dark-mode.css`, `project-selector.css`; agregador ausente; cero `attach-*` (sin vendors de
    grilla en el manifiesto de esta superficie).
  - `cssRequests` (Set acumulado de toda la sesión, incluye `/login` sin migrar todavía —
    Task 7): comparado antes/después, la única diferencia es la adición de
    `/runtime/css/design-system/entrypoints/core.css` y
    `/css/design-system/entrypoints/theme-overrides.css` (parte del cascade del propio core);
    cero elementos removidos, cero vendors de grilla nuevos. Los vendors (`handsontable`,
    `anychart`, `select2`, `sweetalert2`, `jquery-ui`) que aparecen en la lista ya estaban
    presentes en `before` porque provienen del login sin migrar, no de `/proyectos`.
  - `console.json`: `[]` en before y after (sin errores nuevos).
  - `axeViolations`: `[]` en before y after (sin violaciones nuevas).
  - PNGs `proyectos-1180x820.png` y `proyectos-1440x900.png`: **IDÉNTICOS byte a byte** entre
    before y after (verificado con `Buffer.equals`), sin necesidad de comparación visual manual.
- Veredicto: sin regresión visual, sin errores de consola nuevos, sin violaciones de accesibilidad
  nuevas, sin vendors de grilla nuevos en `/proyectos`. Migración aprobada.
