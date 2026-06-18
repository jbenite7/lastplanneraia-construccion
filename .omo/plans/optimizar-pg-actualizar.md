# Optimización de Renderizado - Programa General Actualizar

## TL;DR

> **Quick Summary**: Alinear la configuración de Handsontable en `hot_actualizar.js` con los patrones comprobados de PG/PI/PS para eliminar re-renders innecesarios, mejorar fluidez del scroll, y cambiar el guardado inmediato por debounced sequential saves.
>
> **Deliverables**:
> - `hot_actualizar.js` refactorizado con config de performance alineada a PG/PI/PS
> - `programaGeneralActualizar.view.php` con jQuery deduplicado
> - Debounced save handler reemplazando auto-save inmediato
> - Row metadata cache para evitar recomputación en `cells()` callback
>
> **Estimated Effort**: Medium
> **Parallel Execution**: YES - 3 waves
> **Critical Path**: Task 1 → Task 3 → Task 5 → F1-F4 → user okay

---

## Context

### Original Request
El usuario reportó que el renderizado de la tabla en el módulo `/programa-general-actualizar` es "pésimo y lento". Pidió revisar cómo se ha configurado en PG, PI y PS para seguir las mejores prácticas y que quede "súper fluido".

### Interview Summary
**Key Discussions**:
- Se comparó `hot_actualizar.js` contra PG (`programa_general/hot.js`), PI (`programacion_intermedia/hot.js`), PS (`programacion_semanal/hot.js`)
- Se identificaron 7 problemas concretos de performance
- Usuario eligió alcance completo y cambio a batch save
- Se descubrió que el API no soporta batch payloads (el endpoint `updateBatch` es para recálculo de estados), se usa debounce secuencial

**Research Findings**:
- PG/PI usan `stretchH: 'none'` + función `colWidths` porcentual vs. Actualizar con `stretchH: 'all'` + anchos fijos en px
- PG/PI tienen `viewportColumnRenderingOffset: 10` (virtualización de columnas) — Actualizar no lo tiene
- PI tiene `_rowMetaCache` y `getPIRowMeta()` para cachear metadata de celdas — Actualizar recalcula todo por celda
- PG/PI usan `manualColumnResize: false` — Actualizar usa `true`
- La vista PHP carga jQuery 2 veces (líneas 4 y 350)
- El API endpoint `/api/general/update` solo acepta guardado individual, no batch

### Metis Review
**Identified Gaps** (addressed):
- Falta objetivo de una oración claro → Añadido al draft
- Falta estrategia de testing → Definida como Playwright smoke + agent QA
- API batch payload no validado → Verificado: no existe batch endpoint para este caso
- Falta `beforeunload` handler para guardar cambios pendientes → Añadido al plan
- Timing de debounce no especificado → Fijado en 800ms (balance entre latencia y reducción de requests)

---

## Work Objectives

### Core Objective
Optimizar el renderizado del módulo Actualizar del Programa General alineando la configuración de Handsontable (`hot_actualizar.js`) con los patrones comprobados de PG/PI/PS para reducir re-renders innecesarios, mejorar la fluidez del scroll, y cambiar el guardado inmediato por celda a un patrón debounced.

### Concrete Deliverables
- `public/js/modules/programa_actualizar/hot_actualizar.js` — refactorizado
- `views/programa-general-actualizar/programaGeneralActualizar.view.php` — jQuery deduplicado
- `public/css/handsontable-module.css` — ajustes menores si necesario

### Definition of Done
- [ ] Tabla renderiza sin lag visible al hacer scroll horizontal/vertical
- [ ] Ediciones de celda se guardan con debounce de 800ms (no inmediato)
- [ ] Cambios pendientes se guardan al cerrar/recargar página (`beforeunload`)
- [ ] Columnas se adaptan al ancho del contenedor (porcentuales, no fijas)
- [ ] jQuery cargado una sola vez en la vista
- [ ] Todos los renderers, editors y validadores existentes funcionan igual

### Must Have
- `stretchH: 'none'` con función `colWidths` porcentual (patrón PG)
- `viewportColumnRenderingOffset: 10` (virtualización de columnas)
- `rowMetaCache` para el callback `cells()` (patrón PI)
- `manualColumnResize: false`
- `language: 'es-MX'`
- `colHeaderHeight: 48`
- Debounced save con `beforeunload` flush
- jQuery deduplicado en la vista PHP

### Must NOT Have (Guardrails)
- NO modificar el PHP controller (`GeneralApiController`)
- NO cambiar el contrato del endpoint `/api/general/update`
- NO tocar módulos PG/PI/PS
- NO refactorizar `hot_actualizar.js` a class/module pattern (tarea separada)
- NO agregar features nuevas (undo/redo, nuevas columnas, nuevos cell types)
- NO cambiar el orden de columnas ni los headers
- NO cambiar la lógica de negocio del mapeo de actividades

---

## Verification Strategy

> **ZERO HUMAN INTERVENTION** - ALL verification is agent-executed. No exceptions.

### Test Decision
- **Infrastructure exists**: YES (Playwright 25+ tests en `tests/browser/`)
- **Automated tests**: None for this specific task (tests are module-specific E2E)
- **Framework**: Playwright (existing)

### QA Policy
Every task MUST include agent-executed QA scenarios.
Evidence saved to `.omo/evidence/task-{N}-{scenario-slug}.{ext}`.

- **Frontend/UI**: Use Playwright — Navigate, interact, assert DOM, screenshot
- **API/Backend**: Use Bash (curl) — Send requests, assert status + response fields

---

## Execution Strategy

### Parallel Execution Waves

```
Wave 1 (Start Immediately - foundation):
├── Task 1: Fix jQuery duplication in PHP view [quick]
├── Task 2: Add viewportColumnRenderingOffset + language + colHeaderHeight [quick]
└── Task 3: Replace fixed colWidths with percentage-based function [deep]

Wave 2 (After Wave 1 - core performance):
├── Task 4: Add row metadata cache for cells() callback [deep]
├── Task 5: Implement debounced save handler [deep]
└── Task 6: Change manualColumnResize to false + minor config alignment [quick]

Wave 3 (After Wave 2 - integration):
├── Task 7: Add beforeunload flush handler [quick]
└── Task 8: CSS adjustments if needed [quick]

Wave FINAL (After ALL tasks — 4 parallel reviews, then user okay):
├── Task F1: Plan compliance audit (oracle)
├── Task F2: Code quality review (unspecified-high)
├── Task F3: Real manual QA (unspecified-high)
└── Task F4: Scope fidelity check (deep)
-> Present results -> Get explicit user okay

Critical Path: Task 1 → Task 3 → Task 4 → Task 5 → Task 7 → F1-F4 → user okay
Parallel Speedup: ~60% faster than sequential
Max Concurrent: 3 (Waves 1 & 2)
```

### Dependency Matrix

| Task | Depends On | Blocks |
|------|-----------|--------|
| 1 | None | None |
| 2 | None | None |
| 3 | None | Task 7 |
| 4 | None | None |
| 5 | None | Task 7 |
| 6 | None | None |
| 7 | Task 3, Task 5 | None |
| 8 | None | None |

### Agent Dispatch Summary

- **Wave 1**: 3 tasks — T1 → `quick`, T2 → `quick`, T3 → `deep`
- **Wave 2**: 3 tasks — T4 → `deep`, T5 → `deep`, T6 → `quick`
- **Wave 3**: 2 tasks — T7 → `quick`, T8 → `quick`
- **FINAL**: 4 tasks — F1 → `oracle`, F2 → `unspecified-high`, F3 → `unspecified-high`, F4 → `deep`

---

## TODOs

- [x] 1. Fix jQuery duplication in PHP view

  **What to do**:
  - Open `views/programa-general-actualizar/programaGeneralActualizar.view.php`
  - Remove the duplicate jQuery load at line 350 (`<script type="text/javascript" charset="utf8" src="https://code.jquery.com/jquery-1.12.4.js"></script>`)
  - Keep only the jQuery 3.6.0 load at line 4 in the `<head>`
  - Verify no other scripts depend on jQuery 1.12.4 specifically (check `cargarDatosGeneralesPagina2.js`, `funcionesGenerales6.js`)

  **Must NOT do**:
  - Do not change any other scripts or HTML structure
  - Do not upgrade jQuery version
  - Do not touch the `<head>` section's jQuery 3.6.0 load

  **Recommended Agent Profile**:
  - **Category**: `quick`
  - **Skills**: []

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 1 (with Tasks 2, 3)
  - **Blocks**: None
  - **Blocked By**: None

  **References**:
  - `views/programa-general-actualizar/programaGeneralActualizar.view.php:4` — jQuery 3.6.0 load (KEEP)
  - `views/programa-general-actualizar/programaGeneralActualizar.view.php:350` — jQuery 1.12.4 load (REMOVE)
  - `views/programa-general-actualizar/programaGeneralActualizar.view.php:354` — Bootstrap 4.3.1 depends on jQuery

  **Acceptance Criteria**:
  - [ ] Only one jQuery `<script>` tag in the file
  - [ ] Page loads without console errors related to jQuery
  - [ ] Bootstrap modals and dropdowns still work

  **QA Scenarios**:

  ```
  Scenario: jQuery deduplication verification
    Tool: Bash (grep)
    Steps:
      1. Run: grep -c 'code.jquery.com/jquery' views/programa-general-actualizar/programaGeneralActualizar.view.php
      2. Assert output is exactly "1"
    Expected Result: Count = 1
    Evidence: .omo/evidence/task-1-jquery-count.txt

  Scenario: Page loads without JS errors
    Tool: Playwright
    Steps:
      1. Navigate to http://localhost:8081/login
      2. Login with jbenitez / Jbe#1106z
      3. Navigate to http://localhost:8081/programa-general-actualizar
      4. Wait for page load (timeout: 10s)
      5. Check console for jQuery-related errors
    Expected Result: No "jQuery is not defined" or "$ is not defined" errors
    Evidence: .omo/evidence/task-1-page-load.png
  ```

  **Commit**: YES (groups with Wave 1)
  - Message: `fix(pg-actualizar): remove duplicate jQuery load from view`
  - Files: `views/programa-general-actualizar/programaGeneralActualizar.view.php`

- [x] 2. Add viewportColumnRenderingOffset, language, and colHeaderHeight

  **What to do**:
  - In `public/js/modules/programa_actualizar/hot_actualizar.js`, add these settings to the `hotConfig` object inside `initHandsontable()`:
    - `viewportColumnRenderingOffset: 10` — enables virtual column rendering (same as PG/PI)
    - `language: 'es-MX'` — sets Spanish locale for Handsontable (same as PG/PI)
    - `colHeaderHeight: 48` — consistent header height (same as PG)
  - These are pure additions, no existing settings need to change

  **Must NOT do**:
  - Do not change any existing config values in this task
  - Do not modify any column definitions

  **Recommended Agent Profile**:
  - **Category**: `quick`
  - **Skills**: []

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 1 (with Tasks 1, 3)
  - **Blocks**: None
  - **Blocked By**: None

  **References**:
  - `public/js/modules/programa_actualizar/hot_actualizar.js:544-640` — hotConfig object where settings must be added
  - `public/js/modules/programa_general/hot.js:2294-2296` — reference: PG has `viewportRowRenderingOffset: 20, viewportColumnRenderingOffset: 10, colHeaderHeight: 48`
  - `public/js/modules/programacion_intermedia/hot.js:3417-3418` — reference: PI has `viewportRowRenderingOffset: 20, viewportColumnRenderingOffset: 10`

  **Acceptance Criteria**:
  - [ ] `hotConfig` contains `viewportColumnRenderingOffset: 10`
  - [ ] `hotConfig` contains `language: 'es-MX'`
  - [ ] `hotConfig` contains `colHeaderHeight: 48`

  **QA Scenarios**:

  ```
  Scenario: Config settings present in source
    Tool: Bash (grep)
    Steps:
      1. Run: grep -c "viewportColumnRenderingOffset: 10" public/js/modules/programa_actualizar/hot_actualizar.js
      2. Assert output is "1"
      3. Run: grep -c "language: 'es-MX'" public/js/modules/programa_actualizar/hot_actualizar.js
      4. Assert output is "1"
      5. Run: grep -c "colHeaderHeight: 48" public/js/modules/programa_actualizar/hot_actualizar.js
      6. Assert output is "1"
    Expected Result: All three counts = 1
    Evidence: .omo/evidence/task-2-config-check.txt

  Scenario: Table renders with new config
    Tool: Playwright
    Steps:
      1. Navigate to http://localhost:8081/programa-general-actualizar (after login)
      2. Wait for Handsontable to render (selector: `.handsontable`)
      3. Verify column headers are visible
      4. Take screenshot
    Expected Result: Table renders normally, column headers visible at 48px height
    Evidence: .omo/evidence/task-2-table-render.png
  ```

  **Commit**: YES (groups with Wave 1)
  - Message: `fix(pg-actualizar): add viewportColumnRenderingOffset, language, colHeaderHeight`
  - Files: `public/js/modules/programa_actualizar/hot_actualizar.js`

- [x] 3. Replace fixed colWidths with percentage-based function

  **What to do**:
  - In `public/js/modules/programa_actualizar/hot_actualizar.js`, replace the fixed `width` property on each column definition with a single `colWidths` function at the `hotConfig` level
  - Calculate percentage ratios based on current column widths relative to total (100%)
  - Current columns and their px widths: Consecutivo(40), Id(40), Actividad(350), programaAnteriorAsociar(300), Fecha_Inicio(90), Fecha_Fin(90), unidad(60), cantidad_ppto(80), Estado_Restricciones(100), Ejecutado(140)
  - Total = 1290px. Ratios: [0.031, 0.031, 0.271, 0.233, 0.070, 0.070, 0.047, 0.062, 0.078, 0.109]
  - Add `colWidths: function(index) { ... }` that calculates width from container width × ratio, with min 20px
  - Remove `width` property from each individual column definition
  - Set `stretchH: 'none'` (replacing current `'all'`)
  - Remove `manualColumnResize: true` (set to `false`)

  **Must NOT do**:
  - Do not change column order or data properties
  - Do not change column types or renderers
  - Do not add new columns

  **Recommended Agent Profile**:
  - **Category**: `deep`
  - **Skills**: []

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 1 (with Tasks 1, 2)
  - **Blocks**: Task 7
  - **Blocked By**: None

  **References**:
  - `public/js/modules/programa_actualizar/hot_actualizar.js:544-640` — current hotConfig with fixed column widths
  - `public/js/modules/programa_general/hot.js:2280-2290` — reference: PG's `colWidths` function with percentage ratios
  - `public/js/modules/programacion_intermedia/hot.js:3361-3381` — reference: PI's `colWidths` function with min/max constraints
  - `public/js/modules/programa_general/hot.js:2262` — reference: PG uses `stretchH: 'none'`
  - `public/js/modules/programacion_intermedia/hot.js:3356` — reference: PI uses `manualColumnResize: false`

  **Acceptance Criteria**:
  - [ ] `stretchH: 'none'` in hotConfig
  - [ ] `colWidths` is a function (not array) that calculates from container width
  - [ ] No individual column has a `width` property
  - [ ] `manualColumnResize: false`
  - [ ] Column proportions visually match original layout

  **QA Scenarios**:

  ```
  Scenario: Column widths adapt to container
    Tool: Playwright
    Steps:
      1. Navigate to http://localhost:8081/programa-general-actualizar (after login)
      2. Wait for Handsontable to render
      3. Resize browser window to 800px wide
      4. Take screenshot — columns should shrink proportionally
      5. Resize browser window to 1400px wide
      6. Take screenshot — columns should expand proportionally
    Expected Result: Column widths change proportionally with container, no horizontal overflow
    Evidence: .omo/evidence/task-3-responsive-800.png, .omo/evidence/task-3-responsive-1400.png

  Scenario: No fixed width properties on columns
    Tool: Bash (grep)
    Steps:
      1. Run: grep -c "width:" public/js/modules/programa_actualizar/hot_actualizar.js
      2. Count should be 0 (no individual column width properties)
      3. Run: grep -c "stretchH: 'none'" public/js/modules/programa_actualizar/hot_actualizar.js
      4. Assert output is "1"
    Expected Result: No fixed column widths, stretchH = 'none'
    Evidence: .omo/evidence/task-3-no-fixed-widths.txt
  ```

  **Commit**: YES (groups with Wave 1)
  - Message: `perf(pg-actualizar): replace fixed column widths with percentage-based function`
  - Files: `public/js/modules/programa_actualizar/hot_actualizar.js`

- [x] 4. Add row metadata cache for cells() callback

  **What to do**:
  - In `public/js/modules/programa_actualizar/hot_actualizar.js`, add a `_rowMetaCache` object (similar to PI's pattern)
  - Create a `getRowMeta(physicalRow, rowData)` function that computes and caches row metadata (isMapped, canEdit, classification)
  - Modify the `cells()` callback to use `_rowMetaCache` instead of recomputing per-cell
  - Invalidate cache entries when `afterChange` fires (clear the specific physical row's cache)
  - Also cache the `_canEditGlobal` result per-row to avoid repeated `isUserAllowedToEdit()` calls

  **Must NOT do**:
  - Do not change the logic of what cells are editable/readOnly
  - Do not change cell renderers or editors
  - Do not introduce complex cache invalidation — simple per-row clear on change is sufficient

  **Recommended Agent Profile**:
  - **Category**: `deep`
  - **Skills**: []

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 2 (with Tasks 5, 6)
  - **Blocks**: None
  - **Blocked By**: None

  **References**:
  - `public/js/modules/programa_actualizar/hot_actualizar.js:605-625` — current `cells()` callback (THE target for optimization)
  - `public/js/modules/programacion_intermedia/hot.js:3382-3416` — reference: PI's `cells()` callback using `_rowMetaCache` and `getPIRowMeta()`
  - `public/js/modules/programacion_intermedia/hot.js` — search for `_rowMetaCache` initialization and `getPIRowMeta` function
  - `public/js/modules/programa_general/hot.js` — search for `_rowClassCache` pattern

  **Acceptance Criteria**:
  - [ ] `_rowMetaCache` object exists in module scope
  - [ ] `getRowMeta(physicalRow, rowData)` function exists and returns cached results
  - [ ] `cells()` callback calls `getRowMeta()` instead of inline computation
  - [ ] Cache is invalidated in `afterChange` for modified rows
  - [ ] Cell editability/read-only behavior is identical to before

  **QA Scenarios**:

  ```
  Scenario: Cells callback uses cache
    Tool: Bash (grep)
    Steps:
      1. Run: grep -c "_rowMetaCache" public/js/modules/programa_actualizar/hot_actualizar.js
      2. Assert output >= 3 (declaration, getRowMeta usage, invalidation)
    Expected Result: Cache pattern present in at least 3 locations
    Evidence: .omo/evidence/task-4-cache-pattern.txt

  Scenario: Cell editability preserved
    Tool: Playwright
    Steps:
      1. Navigate to http://localhost:8081/programa-general-actualizar (after login)
      2. Wait for table render
      3. Click on a cell in "Actividad Nueva" column (should be read-only)
      4. Verify no editor opens
      5. Click on a cell in "Asociar con..." column (should be editable)
      6. Verify TomSelect editor opens
    Expected Result: Read-only cells don't open editors, editable cells open TomSelect
    Evidence: .omo/evidence/task-4-cell-editability.png
  ```

  **Commit**: YES (groups with Wave 2)
  - Message: `perf(pg-actualizar): add row metadata cache for cells() callback`
  - Files: `public/js/modules/programa_actualizar/hot_actualizar.js`

- [x] 5. Implement debounced save handler

  **What to do**:
  - In `public/js/modules/programa_actualizar/hot_actualizar.js`, replace the immediate `autoSaveRow()` calls in `afterChange` with a debounced queue
  - Add a `_pendingChanges` object that queues changes by `(visualRow)` key
  - Add a `_saveTimer` variable for debounce timing
  - Create a `flushPendingChanges()` function that:
    1. Iterates `_pendingChanges`
    2. Calls `autoSaveRow()` for each queued row
    3. Clears `_pendingChanges` and `_saveTimer`
  - In `afterChange`, instead of calling `autoSaveRow()` directly:
    1. Merge changes into `_pendingChanges[visualRow]`
    2. Clear existing timer: `clearTimeout(_saveTimer)`
    3. Set new timer: `_saveTimer = setTimeout(flushPendingChanges, 800)`
  - Update the `$saveStatus` badge to show "N cambios pendientes..." during debounce
  - Add a `getPendingChangesCount()` helper for the badge

  **Must NOT do**:
  - Do not change the `autoSaveRow()` function itself
  - Do not change the API endpoint or payload format
  - Do not change validation logic in `normalizeCellValue()`
  - Do not change the debounce timing from 800ms without user confirmation

  **Recommended Agent Profile**:
  - **Category**: `deep`
  - **Skills**: []

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 2 (with Tasks 4, 6)
  - **Blocks**: Task 7
  - **Blocked By**: None

  **References**:
  - `public/js/modules/programa_actualizar/hot_actualizar.js:656-711` — current `afterChange` handler that calls `autoSaveRow()` per row
  - `public/js/modules/programa_actualizar/hot_actualizar.js:364-535` — `autoSaveRow()` function (NOT to be changed)
  - `public/js/modules/programa_actualizar/hot_actualizar.js:382-388` — `$saveStatus` badge logic
  - `public/js/modules/programa_general/hot.js:2307-2359` — reference: PG's `afterChange` handler pattern

  **Acceptance Criteria**:
  - [ ] `_pendingChanges` object exists
  - [ ] `_saveTimer` variable exists
  - [ ] `flushPendingChanges()` function exists
  - [ ] `afterChange` does NOT call `autoSaveRow()` directly
  - [ ] After 5 rapid cell edits, network tab shows ≤2 AJAX requests (not 5)
  - [ ] Status badge shows "Guardando..." during pending changes

  **QA Scenarios**:

  ```
  Scenario: Debounced save reduces network requests
    Tool: Playwright (with network monitoring)
    Steps:
      1. Navigate to http://localhost:8081/programa-general-actualizar (after login)
      2. Open Network tab / enable request logging
      3. Click on "Asociar con..." cell and select an option
      4. Quickly click on "F. Inicio" cell and type a date
      5. Quickly click on "F. Fin" cell and type a date
      6. Wait 2 seconds for debounce to fire
      7. Count POST requests to /api/general/update
    Expected Result: 1-3 POST requests (not 3 individual requests)
    Evidence: .omo/evidence/task-5-network-requests.txt

  Scenario: Status badge shows pending count
    Tool: Playwright
    Steps:
      1. Navigate to the module
      2. Edit a cell
      3. Immediately check the #save-status badge text
    Expected Result: Badge shows "Guardando..." or "1 cambio pendiente" during debounce
    Evidence: .omo/evidence/task-5-status-badge.png
  ```

  **Commit**: YES (groups with Wave 2)
  - Message: `perf(pg-actualizar): replace immediate auto-save with debounced handler`
  - Files: `public/js/modules/programa_actualizar/hot_actualizar.js`

- [x] 6. Change manualColumnResize to false + minor config alignment

  **What to do**:
  - In `public/js/modules/programa_actualizar/hot_actualizar.js`:
    - Change `manualColumnResize: true` to `manualColumnResize: false`
    - Verify `outsideClickDeselects: false` is preserved (critical for TomSelect)
    - Verify `hiddenColumns` config is preserved
    - Verify all column `type`, `editor`, `renderer`, `className` properties are unchanged

  **Must NOT do**:
  - Do not change any column definitions
  - Do not change renderers or editors
  - Do not change the `hiddenColumns` configuration

  **Recommended Agent Profile**:
  - **Category**: `quick`
  - **Skills**: []

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 2 (with Tasks 4, 5)
  - **Blocks**: None
  - **Blocked By**: None

  **References**:
  - `public/js/modules/programa_actualizar/hot_actualizar.js:640` — current `manualColumnResize: true`
  - `public/js/modules/programa_general/hot.js:2264` — reference: PG uses `manualColumnResize: false`
  - `public/js/modules/programacion_intermedia/hot.js:3356` — reference: PI uses `manualColumnResize: false`
  - `public/js/modules/programa_actualizar/hot_actualizar.js:650` — `outsideClickDeselects: false` (MUST preserve)

  **Acceptance Criteria**:
  - [ ] `manualColumnResize: false` in hotConfig
  - [ ] `outsideClickDeselects: false` still present
  - [ ] No other config values changed

  **QA Scenarios**:

  ```
  Scenario: manualColumnResize disabled
    Tool: Bash (grep)
    Steps:
      1. Run: grep "manualColumnResize" public/js/modules/programa_actualizar/hot_actualizar.js
      2. Verify output contains "manualColumnResize: false"
      3. Verify output does NOT contain "manualColumnResize: true"
    Expected Result: manualColumnResize = false
    Evidence: .omo/evidence/task-6-manual-resize.txt

  Scenario: TomSelect still works with outsideClickDeselects preserved
    Tool: Playwright
    Steps:
      1. Navigate to the module
      2. Click on "Asociar con..." cell
      3. Verify TomSelect dropdown opens
      4. Click outside the cell
      5. Verify dropdown closes but cell stays selected
    Expected Result: TomSelect functions correctly
    Evidence: .omo/evidence/task-6-tomselect-test.png
  ```

  **Commit**: YES (groups with Wave 2)
  - Message: `chore(pg-actualizar): disable manualColumnResize for consistency`
  - Files: `public/js/modules/programa_actualizar/hot_actualizar.js`

- [x] 7. Add beforeunload flush handler

  **What to do**:
  - In `public/js/modules/programa_actualizar/hot_actualizar.js`, add a `beforeunload` event handler that calls `flushPendingChanges()` if there are pending changes
  - Register the handler when Handsontable is initialized
  - Remove the handler when the module is destroyed (if applicable)
  - Use `navigator.sendBeacon()` as fallback for the final save (more reliable than fetch during page unload)

  **Must NOT do**:
  - Do not add complex "are you sure?" dialogs
  - Do not change the debounce timing

  **Recommended Agent Profile**:
  - **Category**: `quick`
  - **Skills**: []

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 3 (with Task 8)
  - **Blocks**: None
  - **Blocked By**: Task 3, Task 5

  **References**:
  - `public/js/modules/programa_actualizar/hot_actualizar.js:723-727` — current window event bindings
  - Task 5 output — `flushPendingChanges()` function (must be created first)

  **Acceptance Criteria**:
  - [ ] `beforeunload` handler registered
  - [ ] Handler calls `flushPendingChanges()` if pending changes exist
  - [ ] No "are you sure?" dialog (browser native is sufficient)

  **QA Scenarios**:

  ```
  Scenario: Pending changes saved on page unload
    Tool: Playwright
    Steps:
      1. Navigate to the module
      2. Edit a cell (triggers debounce)
      3. Immediately navigate to a different page (before debounce fires)
      4. Check network tab for final POST request
    Expected Result: At least one POST request fires during navigation
    Evidence: .omo/evidence/task-7-beforeunload.txt

  Scenario: No error on clean exit
    Tool: Playwright
    Steps:
      1. Navigate to the module
      2. Make no edits
      3. Navigate away
    Expected Result: No JS errors, no unnecessary AJAX requests
    Evidence: .omo/evidence/task-7-clean-exit.png
  ```

  **Commit**: YES (groups with Wave 3)
  - Message: `fix(pg-actualizar): add beforeunload handler to flush pending saves`
  - Files: `public/js/modules/programa_actualizar/hot_actualizar.js`

- [x] 8. CSS adjustments if needed

  **What to do**:
  - Review `public/css/handsontable-module.css` for any styles specific to `stretchH: 'all'` or fixed column widths
  - Check if the `.hot-full-bleed` class or `#hot-container` styles need adjustment for the new percentage-based layout
  - Verify the responsive media query at line 79 of the view still works
  - If no CSS changes needed, document "no changes required" and skip

  **Must NOT do**:
  - Do not add new CSS classes
  - Do not change the AIA brand colors or modal styles
  - Do not touch other CSS files

  **Recommended Agent Profile**:
  - **Category**: `quick`
  - **Skills**: []

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 3 (with Task 7)
  - **Blocks**: None
  - **Blocked By**: None

  **References**:
  - `public/css/handsontable-module.css` — main Handsontable CSS file
  - `views/programa-general-actualizar/programaGeneralActualizar.view.php:22-80` — inline styles for `.hot-full-bleed`, `#hot-container`

  **Acceptance Criteria**:
  - [ ] No horizontal overflow on typical screen sizes
  - [ ] Table fills available width without gaps
  - [ ] Responsive behavior at 991px breakpoint still works

  **QA Scenarios**:

  ```
  Scenario: No horizontal overflow
    Tool: Playwright
    Steps:
      1. Navigate to the module at 1200px width
      2. Verify no horizontal scrollbar on the page
      3. Navigate to the module at 800px width
      4. Verify no horizontal scrollbar
    Expected Result: No horizontal overflow at any width
    Evidence: .omo/evidence/task-8-no-overflow.png

  Scenario: Responsive breakpoint works
    Tool: Playwright
    Steps:
      1. Navigate to the module
      2. Set viewport to 800px wide (below 991px breakpoint)
      3. Verify table height adjusts (calc(100vh - 250px))
    Expected Result: Table height changes at breakpoint
    Evidence: .omo/evidence/task-8-responsive.png
  ```

  **Commit**: YES (groups with Wave 3)
  - Message: `fix(pg-actualizar): CSS adjustments for percentage-based layout`
  - Files: `public/css/handsontable-module.css` (if changes needed)

---

## Final Verification Wave

> 4 review agents run in PARALLEL. ALL must APPROVE. Present consolidated results to user and get explicit "okay" before completing.

- [x] F1. **Plan Compliance Audit** — `oracle`
  Read the plan end-to-end. For each "Must Have": verify implementation exists (read file, run command). For each "Must NOT Have": search codebase for forbidden patterns — reject with file:line if found. Check evidence files exist in .omo/evidence/. Compare deliverables against plan.
  Output: `Must Have [N/N] | Must NOT Have [N/N] | Tasks [N/N] | VERDICT: APPROVE/REJECT`

- [x] F2. **Code Quality Review** — `unspecified-high`
  Run lint checks on changed files. Review all changed files for: type suppression, empty catches, debug logging in prod, commented-out code, unused imports. Check AI slop: excessive comments, over-abstraction, generic names.
  Output: `Files [N clean/N issues] | VERDICT`

- [x] F3. **Real Manual QA** — `unspecified-high` (+ `playwright` skill)
  Start from clean state. Execute EVERY QA scenario from EVERY task — follow exact steps, capture evidence. Test cross-task integration. Test edge cases: empty state, invalid input, rapid actions. Save to `.omo/evidence/final-qa/`.
  Output: `Scenarios [N/N pass] | Integration [N/N] | Edge Cases [N tested] | VERDICT`

- [x] F4. **Scope Fidelity Check** — `deep`
  For each task: read "What to do", read actual diff. Verify 1:1 — everything in spec was built (no missing), nothing beyond spec was built (no creep). Check "Must NOT do" compliance. Flag unaccounted changes.
  Output: `Tasks [N/N compliant] | Unaccounted [CLEAN/N files] | VERDICT`

---

## Commit Strategy

- **After Wave 1**: `fix(pg-actualizar): deduplicate jQuery + add viewportColumnRenderingOffset + percentage colWidths`
- **After Wave 2**: `perf(pg-actualizar): add row metadata cache + debounced save handler`
- **After Wave 3**: `fix(pg-actualizar): add beforeunload flush + CSS adjustments`

---

## Success Criteria

### Verification Commands
```bash
# Verify jQuery loaded once
grep -c 'jquery' views/programa-general-actualizar/programaGeneralActualizar.view.php
# Expected: 1 (or 0 if using shared head)

# Verify Handsontable config
grep -c "stretchH: 'none'" public/js/modules/programa_actualizar/hot_actualizar.js
# Expected: 1

grep -c "viewportColumnRenderingOffset" public/js/modules/programa_actualizar/hot_actualizar.js
# Expected: 1

grep -c "manualColumnResize: false" public/js/modules/programa_actualizar/hot_actualizar.js
# Expected: 1

grep -c "language: 'es-MX'" public/js/modules/programa_actualizar/hot_actualizar.js
# Expected: 1
```

### Final Checklist
- [ ] All "Must Have" present
- [ ] All "Must NOT Have" absent
- [ ] Table renders without visible lag
- [ ] Debounced save works (monitor network tab: 1 request per 800ms, not per keystroke)
- [ ] `beforeunload` saves pending changes
- [ ] Column widths adapt to container
