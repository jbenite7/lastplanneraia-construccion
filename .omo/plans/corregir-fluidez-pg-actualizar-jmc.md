# Corregir Fluidez - Programa General Actualizar con JMC

## TL;DR

> **Quick Summary**: Corregir la falta de fluidez real de `/programa-general-actualizar` usando `Optimización Aeropuerto JMC` como dataset canónico (~1475 actividades no-título en semana objetivo 3), empezando por medición runtime antes/después y aplicando solo optimizaciones verificadas.
>
> **Deliverables**:
> - Baseline Playwright/performance para JMC antes del cambio.
> - `hot_actualizar.js` con menos renders redundantes, widths cacheadas, `cells()`/renderers más livianos y guardado unload correcto.
> - `programaGeneralActualizar.view.php` sin carga duplicada de jQuery UI.
> - Evidencia antes/después en `.omo/evidence/pg-actualizar-jmc/`.
>
> **Estimated Effort**: Medium
> **Parallel Execution**: YES - 3 waves + final verification
> **Critical Path**: 1 → 4/5/6 → 8 → F1-F4 → user okay

---

## Context

### Original Request
El usuario indicó que la UI de `/programa-general-actualizar` sigue siendo muy poco fluida después del trabajo anterior.

### Confirmed Symptoms
- Scroll vertical lento.
- Carga inicial lenta.
- Edición/dropdowns lentos.
- Guardado poco fluido.

### Runtime/Data Findings
- Los proyectos usados en QA anterior devolvían `data: []`, así que no validaron fluidez real.
- Usuario pidió revisar `Optimización Aeropuerto JMC`.
- `general_proyectos_procesos`: `Optimización Aeropuerto JMC`, `Base_de_Datos = optimizacionJMC`.
- `optimizacionJMC_programa_consolidado`: semana 1 = 1449 actividades no-título; semana 2 = 1449; semana 3 = 1475.
- `optimizacionJMC_semanas_activas`: semana 2 existe y no está confirmada; el módulo debería cargar semana objetivo 3.

### Technical Findings
- `refreshHotLayout()` puede disparar renders redundantes (`updateSettings` + `refreshDimensions` + `render`).
- `colWidths()` lee DOM por columna/render y sus ratios suman ~0.932, no 1.0.
- `cells()` sigue siendo hot path: `toPhysicalRow()`, `getSourceData()`, `getSettings().columns[col]` por celda.
- Renderers usan `innerHTML`, inline styles y `getSourceData()` por celda.
- `beforeunload`/`sendBeacon` parece defectuoso: puede enviar pending vacío y formato incorrecto.
- jQuery UI 1.10.1 sigue cargado dos veces.

---

## Work Objectives

### Core Objective
Lograr que `/programa-general-actualizar` sea perceptiblemente fluido con el dataset real de `Optimización Aeropuerto JMC`, reduciendo renders/reflows innecesarios y validando con evidencia runtime antes/después.

### Concrete Deliverables
- `public/js/modules/programa_actualizar/hot_actualizar.js` optimizado.
- `views/programa-general-actualizar/programaGeneralActualizar.view.php` sin jQuery UI duplicado.
- Evidencia Playwright/performance en `.omo/evidence/pg-actualizar-jmc/`.

### Must Have
- Baseline antes de tocar código usando JMC y semana objetivo 3.
- Optimización de `refreshHotLayout()` con guardas de ancho/alto y sin render redundante.
- `colWidths` cacheado/normalizado, sin DOM read por columna en cada render.
- `cells()` reducido: evitar `getSettings().columns[col]` y `getSourceData()` por celda cuando sea posible.
- Renderers con menos `innerHTML`/source lookups en hot path.
- Guardado debounced preservado, con `beforeunload` corregido o simplificado de forma segura.
- jQuery UI cargado una sola vez.
- Métrica antes/después: carga inicial, tiempo hasta grid con filas, scroll vertical, toggle, apertura de editor/dropdown, requests de guardado.

### Must NOT Have
- NO cambiar contrato de `/api/general/update`.
- NO modificar schema ni datos productivos.
- NO tocar PG/PI/PS salvo como referencia read-only.
- NO agregar features nuevas.
- NO ocultar errores con `catch` silenciosos nuevos.
- NO declarar éxito si JMC no carga filas reales.

---

## Verification Strategy

> **ZERO HUMAN INTERVENTION** - ALL verification is agent-executed.

### Test Decision
- **Infrastructure exists**: YES, Playwright/browser scripts existentes.
- **Automated tests**: Tests-after/runtime QA.
- **Primary QA dataset**: `Optimización Aeropuerto JMC` (`optimizacionJMC`), target week 3.

### Required Evidence
- `.omo/evidence/pg-actualizar-jmc/baseline.json`
- `.omo/evidence/pg-actualizar-jmc/after.json`
- Screenshots before/after.
- Network request counts before/after.
- Console logs/errors before/after.

---

## Execution Strategy

### Parallel Execution Waves

```
Wave 1 (Instrumentation + safe small fixes):
├── Task 1: Baseline runtime measurement with JMC [unspecified-high]
├── Task 2: Remove duplicate jQuery UI load [quick]
└── Task 3: Audit current diff/scope to isolate unrelated local changes [quick]

Wave 2 (Core rendering fixes after baseline):
├── Task 4: Optimize refreshHotLayout render cycle [deep]
├── Task 5: Cache/normalize colWidths [deep]
├── Task 6: Optimize cells() hot path [deep]
└── Task 7: Simplify hot renderers safely [deep]

Wave 3 (Save/unload + integrated runtime QA):
├── Task 8: Fix beforeunload/sendBeacon or replace with safe flush behavior [deep]
├── Task 9: After-change runtime measurement with JMC [unspecified-high]
└── Task 10: Regression pass for empty-data projects [quick]

Wave FINAL:
├── F1: Plan compliance audit [oracle]
├── F2: Code quality review [unspecified-high]
├── F3: Real manual QA with JMC [unspecified-high]
└── F4: Scope fidelity check [deep]
```

---

## TODOs

- [x] 1. Baseline runtime measurement with JMC

  **What to do**:
  - Use Playwright/browser tooling to login and select `Optimización Aeropuerto JMC`.
  - Navigate to `/programa-general-actualizar`.
  - Verify API request uses `db=optimizacionJMC` and target week 3.
  - Capture row count rendered, API response length, DOMContentLoaded, time until `.handsontable` has data rows, console logs/errors, request counts.
  - Exercise scroll vertical, toggle Pendientes/Todas, open an editable dropdown/date cell if rows exist.

  **Must NOT do**:
  - Do not edit code.
  - Do not modify DB data.

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high`
  - **Skills**: [`debugging`, `e2e`]

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 1
  - **Blocks**: Tasks 4-9
  - **Blocked By**: None

  **References**:
  - `views/programa-general-actualizar/programaGeneralActualizar.view.php` - route view and hidden context inputs.
  - `public/js/modules/programa_actualizar/hot_actualizar.js` - grid initialization and performance config.
  - DB verified: `general_proyectos_procesos.Base_de_Datos = optimizacionJMC`.

  **Acceptance Criteria**:
  - [ ] `.omo/evidence/pg-actualizar-jmc/baseline.json` exists.
  - [ ] Baseline includes row count > 0 or explicit blocker if JMC selection fails.
  - [ ] Baseline includes console errors/warnings and request counts.

  **QA Scenarios**:
  ```
  Scenario: Baseline with real rows
    Tool: Playwright
    Preconditions: Docker app running; user can login.
    Steps:
      1. Login at http://localhost:8081/login.
      2. Select project text containing "Optimización Aeropuerto JMC".
      3. Navigate to /programa-general-actualizar.
      4. Wait for `.handsontable` and API `/api/general/list`.
      5. Record rows, timings, console, network.
    Expected Result: API returns >1000 records or diagnostic blocker is recorded.
    Evidence: .omo/evidence/pg-actualizar-jmc/baseline.json
  ```

- [x] 2. Remove duplicate jQuery UI load

  **What to do**:
  - In `views/programa-general-actualizar/programaGeneralActualizar.view.php`, remove one of the two jQuery UI 1.10.1 script loads.
  - Keep datepicker behavior working.

  **Must NOT do**:
  - Do not remove jQuery base 3.6.0.
  - Do not change Bootstrap/TomSelect/Handsontable loads.

  **Recommended Agent Profile**:
  - **Category**: `quick`
  - **Skills**: []

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 1
  - **Blocks**: None
  - **Blocked By**: None

  **References**:
  - `views/programa-general-actualizar/programaGeneralActualizar.view.php:5` - head jQuery UI load.
  - `views/programa-general-actualizar/programaGeneralActualizar.view.php` body scripts section - duplicate jQuery UI load.

  **Acceptance Criteria**:
  - [ ] Exactly one `code.jquery.com/ui/1.10.1/jquery-ui.js` remains.
  - [ ] Datepicker opens in the import modal when needed.

  **QA Scenarios**:
  ```
  Scenario: Script dedupe
    Tool: Bash + Playwright
    Steps:
      1. Count jQuery UI script occurrences in the view.
      2. Open Cargar desde Excel modal in browser.
      3. If date field appears, verify datepicker can initialize without console error.
    Expected Result: One jQuery UI load, no datepicker errors.
    Evidence: .omo/evidence/pg-actualizar-jmc/jquery-ui-dedupe.txt
  ```

- [x] 3. Audit current diff/scope to isolate unrelated local changes

  **What to do**:
  - Run `git diff --name-only` and classify changed files.
  - Confirm this plan only edits `hot_actualizar.js` and PG Actualizar view.
  - Document unrelated files already present in the working tree so implementers do not stage or modify them.

  **Must NOT do**:
  - Do not revert user changes.
  - Do not stage/commit.

  **Recommended Agent Profile**:
  - **Category**: `quick`
  - **Skills**: [`git-master`]

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 1
  - **Blocks**: Final scope review
  - **Blocked By**: None

  **References**:
  - Git working tree status.
  - `.omo/plans/corregir-fluidez-pg-actualizar-jmc.md` guardrails.

  **Acceptance Criteria**:
  - [ ] Unrelated changes are listed in `.omo/evidence/pg-actualizar-jmc/scope-baseline.txt`.
  - [ ] Implementer knows only intended files for this work.

  **QA Scenarios**:
  ```
  Scenario: Scope baseline
    Tool: Bash
    Steps:
      1. Run git diff --name-only.
      2. Write allowed vs unrelated files to evidence.
    Expected Result: Scope baseline exists and identifies intended files.
    Evidence: .omo/evidence/pg-actualizar-jmc/scope-baseline.txt
  ```

- [x] 4. Optimize refreshHotLayout render cycle

  **What to do**:
  - Add `lastAppliedContainerHeight` and avoid `hot.updateSettings({height})` when height unchanged.
  - Avoid explicit `hot.render()` immediately after `updateSettings`/`refreshDimensions` unless evidence shows it is required.
  - Preserve resize/orientation behavior.

  **Must NOT do**:
  - Do not change data loading logic.
  - Do not remove layout refresh entirely.

  **Recommended Agent Profile**:
  - **Category**: `deep`
  - **Skills**: []

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 2
  - **Blocks**: Task 9
  - **Blocked By**: Task 1

  **References**:
  - `public/js/modules/programa_actualizar/hot_actualizar.js:137-153` - current `refreshHotLayout`.
  - `public/js/modules/programacion_semanal/hot.js:1771-1789` - cached layout refresh pattern.

  **Acceptance Criteria**:
  - [ ] Layout refresh skips if height unchanged.
  - [ ] No explicit redundant render remains without comment/evidence.
  - [ ] Resize still updates grid height.

  **QA Scenarios**:
  ```
  Scenario: Layout refresh reduced
    Tool: Playwright console/performance
    Steps:
      1. Load JMC module.
      2. Trigger resize.
      3. Observe no repeated layout loops or console errors.
    Expected Result: Grid height updates once per resize debounce.
    Evidence: .omo/evidence/pg-actualizar-jmc/layout-refresh-after.json
  ```

- [x] 5. Cache and normalize colWidths

  **What to do**:
  - Normalize column ratios so they sum to 1.0.
  - Avoid DOM width reads per column per render: compute container width once and reuse until resize.
  - Preserve original visual proportions.

  **Must NOT do**:
  - Do not restore `stretchH: 'all'` blindly.
  - Do not change column order/headers.

  **Recommended Agent Profile**:
  - **Category**: `deep`
  - **Skills**: []

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 2
  - **Blocks**: Task 9
  - **Blocked By**: Task 1

  **References**:
  - `public/js/modules/programa_actualizar/hot_actualizar.js` - `colWidths` function.
  - `public/js/modules/programacion_semanal/hot.js` - cached/constrained width pattern.

  **Acceptance Criteria**:
  - [ ] Ratio set sums to 1.0 ± 0.001.
  - [ ] Container width is not queried per column call.
  - [ ] JMC grid has no horizontal flicker/blank gap in after screenshot.

  **QA Scenarios**:
  ```
  Scenario: Width stability
    Tool: Playwright
    Steps:
      1. Load JMC module at 1400px and 900px width.
      2. Capture screenshots and column widths.
    Expected Result: Columns fill container proportionally without blank gaps.
    Evidence: .omo/evidence/pg-actualizar-jmc/width-stability-after.json
  ```

- [x] 6. Optimize cells() hot path

  **What to do**:
  - Avoid `this.instance.getSettings().columns[col]` inside every cell call; use a stable columns array reference.
  - Avoid repeated `getSourceData()` where Handsontable can provide source row or metadata cache safely.
  - Keep editability behavior identical.

  **Must NOT do**:
  - Do not change permissions or which columns are editable.
  - Do not change mapped-row business rules.

  **Recommended Agent Profile**:
  - **Category**: `deep`
  - **Skills**: []

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 2
  - **Blocks**: Task 9
  - **Blocked By**: Task 1

  **References**:
  - `public/js/modules/programa_actualizar/hot_actualizar.js` - `cells()` callback.
  - `public/js/modules/programacion_intermedia/hot.js` - PI cell metadata/cache pattern.

  **Acceptance Criteria**:
  - [ ] `cells()` no longer calls `getSettings().columns[col]` per cell.
  - [ ] Read-only/editable behavior remains unchanged in JMC QA.

  **QA Scenarios**:
  ```
  Scenario: Editability preserved
    Tool: Playwright
    Steps:
      1. Load JMC module.
      2. Try readonly activity cell.
      3. Try editable association/date cell.
    Expected Result: Same editability rules; no console errors.
    Evidence: .omo/evidence/pg-actualizar-jmc/editability-after.json
  ```

- [x] 7. Simplify hot renderers safely

  **What to do**:
  - Reduce heavy `innerHTML`/inline style writes where text-only rendering is enough.
  - Cache source row access in `pgEjecutadoRealRenderer` or avoid repeated full `getSourceData()` calls.
  - Preserve visual states/chips for mapped/unmapped rows.

  **Must NOT do**:
  - Do not remove visual status indicators.
  - Do not change displayed values or percentage formulas.

  **Recommended Agent Profile**:
  - **Category**: `deep`
  - **Skills**: [`frontend-ui-ux`]

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 2
  - **Blocks**: Task 9
  - **Blocked By**: Task 1

  **References**:
  - `ActivityMappingRenderer`, `ReadOnlyRenderer`, `pgEjecutadoRealRenderer` in `hot_actualizar.js`.

  **Acceptance Criteria**:
  - [ ] Visual mapping states still visible.
  - [ ] Renderer hot path avoids unnecessary source array retrieval.

  **QA Scenarios**:
  ```
  Scenario: Renderer visual parity
    Tool: Playwright screenshots
    Steps:
      1. Capture before/after row states for mapped and unmapped rows in JMC if present.
      2. Compare visible labels/chips/status.
    Expected Result: Visual state preserved with less renderer overhead.
    Evidence: .omo/evidence/pg-actualizar-jmc/renderer-parity-after.png
  ```

- [x] 8. Fix beforeunload/sendBeacon or replace with safe flush behavior

  **What to do**:
  - Verify current `beforeunload` actually sends pending changes before clearing them.
  - If using `sendBeacon`, build payload before clearing and use backend-compatible format.
  - If not reliable with current endpoint, remove dead `sendBeacon` and use documented best-effort `flushPendingChanges()` without false fallback.

  **Must NOT do**:
  - Do not change `/api/general/update` contract.
  - Do not add blocking custom confirmation dialogs.

  **Recommended Agent Profile**:
  - **Category**: `deep`
  - **Skills**: []

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 3
  - **Blocks**: Task 9
  - **Blocked By**: Task 1, Task 5

  **References**:
  - `hot_actualizar.js` - `_pendingChanges`, `flushPendingChanges`, `beforeunload.hotActualizar`.
  - `src/Controllers/Api/GeneralApiController.php:update` - expected payload format.

  **Acceptance Criteria**:
  - [ ] No code path sends empty pending data as successful fallback.
  - [ ] No unsupported JSON payload is sent to x-www-form-urlencoded endpoint.

  **QA Scenarios**:
  ```
  Scenario: Pending save safety
    Tool: Playwright network monitoring
    Steps:
      1. Edit one cell in JMC.
      2. Navigate away before debounce fires.
      3. Inspect final network request and console.
    Expected Result: No failed bogus sendBeacon; pending handling is explicit and safe.
    Evidence: .omo/evidence/pg-actualizar-jmc/unload-save-after.json
  ```

- [x] 9. After-change runtime measurement with JMC

  **What to do**:
  - Repeat Task 1 measurements after fixes.
  - Compare baseline vs after for: time to rows, scroll frame smoothness, toggle, editor open, request count, console errors.
  - Store comparison summary.

  **Must NOT do**:
  - Do not claim success without comparing to baseline.

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high`
  - **Skills**: [`debugging`, `e2e`]

  **Parallelization**:
  - **Can Run In Parallel**: NO
  - **Parallel Group**: Wave 3
  - **Blocks**: Final wave
  - **Blocked By**: Tasks 4-8

  **References**:
  - `.omo/evidence/pg-actualizar-jmc/baseline.json`.
  - Updated `hot_actualizar.js`.

  **Acceptance Criteria**:
  - [ ] `.omo/evidence/pg-actualizar-jmc/after.json` exists.
  - [ ] After metrics show no regression and meaningful improvement in at least two symptom areas.
  - [ ] Console has 0 blocking errors.

  **QA Scenarios**:
  ```
  Scenario: Before/after performance comparison
    Tool: Playwright
    Steps:
      1. Repeat baseline steps on JMC.
      2. Compare metrics with baseline.
    Expected Result: Measurable improvement or explicit rollback recommendation.
    Evidence: .omo/evidence/pg-actualizar-jmc/comparison.md
  ```

- [x] 10. Regression pass for empty-data projects

  **What to do**:
  - Test `Prueba` or another project with empty target data.
  - Verify no JS errors and empty-grid UX still works.

  **Must NOT do**:
  - Do not change empty-data business behavior.

  **Recommended Agent Profile**:
  - **Category**: `quick`
  - **Skills**: [`e2e`]

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 3
  - **Blocks**: Final wave
  - **Blocked By**: Tasks 4-8

  **References**:
  - Prior runtime finding: empty projects returned `data: []` and rendered header-only grid.

  **Acceptance Criteria**:
  - [ ] Empty project still renders without console errors.
  - [ ] Toggle and buttons still work.

  **QA Scenarios**:
  ```
  Scenario: Empty dataset regression
    Tool: Playwright
    Steps:
      1. Select Prueba.
      2. Navigate to /programa-general-actualizar.
      3. Verify no blocking errors.
    Expected Result: Header-only empty grid remains stable.
    Evidence: .omo/evidence/pg-actualizar-jmc/empty-regression.json
  ```

---

## Final Verification Wave

- [x] F1. **Plan Compliance Audit** — `oracle`
  Verify all Must Have and Must NOT Have items against code and evidence. Output: `Must Have [N/N] | Must NOT Have [N/N] | VERDICT`.

- [x] F2. **Code Quality Review** — `unspecified-high`
  Review changed files for debug artifacts, dead code, over-abstraction, silent catches, scope creep. Output: `Files [N clean/N issues] | VERDICT`.

- [x] F3. **Real Manual QA with JMC** — `unspecified-high`
  Execute full Playwright workflow on `Optimización Aeropuerto JMC`, including scroll, toggle, editor/dropdown, save, screenshots, console/network. Output: `Scenarios [N/N pass] | VERDICT`.

- [x] F4. **Scope Fidelity Check** — `deep`
  Compare actual diff to plan; ensure no unrelated local changes are included. Output: `Tasks [N/N compliant] | Unaccounted [CLEAN/N files] | VERDICT`.

---

## Commit Strategy

- Commit only intended files after final approval:
  - `public/js/modules/programa_actualizar/hot_actualizar.js`
  - `views/programa-general-actualizar/programaGeneralActualizar.view.php`
- Suggested message: `perf(pg-actualizar): smooth Handsontable rendering with JMC baseline`

---

## Success Criteria

- JMC loads real rows (>1000 non-title records expected for target week 3).
- Initial grid load and scroll are measurably smoother than baseline.
- Dropdown/editor opens without visible stall on JMC.
- Debounced save remains correct and does not send bogus unload payloads.
- Empty-data projects still behave safely.
- No controller/API/schema changes.
