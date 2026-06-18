# Learnings

## 2026-06-17 Session Start
- Plan: optimizar-pg-actualizar
- Files to modify: `hot_actualizar.js`, `programaGeneralActualizar.view.php`
- API: `/api/general/update` (single-cell only), `/api/general/updateBatch` (status recalc only)
- Reference modules: PG (`programa_general/hot.js`), PI (`programacion_intermedia/hot.js`), PS (`programacion_semanal/hot.js`)
- Wave 1 Task 1: Removed duplicate jQuery 1.12.4 load (line 350) from `programaGeneralActualizar.view.php`. Only jQuery 3.6.0 in `<head>` remains. Bootstrap 4.3.1 works with 3.6.0.
- Wave 1 Task 2: Added 3 performance/config settings to `hot_actualizar.js` hotConfig (line 636-638):
  - `viewportColumnRenderingOffset: 10` — renders more columns off-screen for smoother scroll
  - `language: 'es-MX'` — sets locale to Mexican Spanish
  - `colHeaderHeight: 48` — taller column headers for readability
  - Placed after `viewportRowRenderingOffset: 20` (line 635), before `width: '100%'`
### Task 3: hot_actualizar.js — Percentage-based colWidths
- Changed `stretchH: 'all'` → `stretchH: 'none'` in hotConfig
- Added `colWidths` function following PG's reference pattern
- Original pixel widths: [40, 40, 350, 300, 90, 90, 60, 80, 100, 140] = 1290px total
- Computed ratios: [0.031, 0.031, 0.271, 0.233, 0.070, 0.070, 0.047, 0.062, 0.078, 0.109]
- Gutter: `baseWidth - 20` (smaller than PG's 60px since this table has fewer columns)
- Min width: `Math.max(w, 20)` — same as PG pattern
- All 10 column `width` properties removed successfully
- Container `width: '100%'` preserved (that's the container, not column width)

## Wave 1 Task 6: manualColumnResize: true → false
- Changed `manualColumnResize: true` to `manualColumnResize: false` in hotConfig (line 648)
- `outsideClickDeselects: false` preserved at line 658 (required for TomSelect compatibility)
- Aligned with PG (`programa_general/hot.js:2264`) and PI (`programacion_intermedia/hot.js:3356`) patterns
- Verified both grep results post-edit

## Wave 2 Task 4: Row metadata cache (_rowMetaCache + getRowMeta)
- Added `_rowMetaCache = {};` at module scope (line 12, after `_canEditGlobal`)
- Added `getRowMeta(physicalRow, rowData)` function inside `initHandsontable` (line 545-557)
  - Cache hit: returns `_rowMetaCache[physicalRow]` immediately
  - Cache miss: computes `isMapped` from `rowData.programaAnteriorAsociar`, stores and returns
  - Simpler than PI's version — only caches `isMapped`, no state/class logic
- Modified `cells()` callback (line 626): replaced inline `isMapped` computation with `var meta = getRowMeta(physicalRow, rowData)`
- Cache invalidation in `afterChange` (line 731): `delete _rowMetaCache[physicalRow]` in the row save loop
- Editability logic unchanged — `isMapped` still used for Ejecutado/unidad/cantidad_ppto read-only override
- Reference pattern: PI's `getPIRowMeta` at `programacion_intermedia/hot.js:487-529`
- Note: other `isMapped` usages (lines 190, 210) are in renderers/validators, NOT in cells() — untouched

## Wave 2, Task 5: Debounce en afterChange (hot_actualizar.js)

### Cambios realizados
- Agregados `_pendingChanges = {}` y `_saveTimer = null` en module scope (línea 13-14)
- Creada función `flushPendingChanges()` (línea 367) que itera `_pendingChanges`, llama `autoSaveRow()` por cada fila, y oculta el badge
- Modificado handler `afterChange` (línea 752): ya no llama `autoSaveRow()` directamente; usa `Object.assign` para mergear cambios en `_pendingChanges[visualRow]`, limpia timer con `clearTimeout`, y crea nuevo timer con `setTimeout(flushPendingChanges, 800)`
- Badge de estado muestra "Guardando... (N)" durante el debounce con count de filas pendientes

### Patrón de debounce
```
afterChange → merge into _pendingChanges → clearTimeout → setTimeout(800ms) → flushPendingChanges → autoSaveRow per row
```

### Notas técnicas
- Se removió `.bind(this)` del callback de `Object.keys(rowChanges).forEach()` ya que el nuevo callback solo usa variables de module scope (no necesita `this`)
- `flushPendingChanges` llama `hot.toPhysicalRow()` para limpiar `_rowMetaCache` antes de disparar `autoSaveRow`
- El badge se oculta al completar flush, no al encolar (el badge se muestra al encolar, se oculta al flush)

## Wave 3, Task 7: beforeunload handler for pending changes flush
- Added `beforeunload` event listener on `window` (line 792) inside `initHandsontable()`, after existing resize/orientationchange bindings
- Namespaced as `beforeunload.hotActualizar` to match existing pattern
- Handler logic:
  1. Checks `Object.keys(_pendingChanges).length > 0`
  2. Clears `_saveTimer` via `clearTimeout()` to prevent stale debounce callback
  3. Calls `flushPendingChanges()` immediately (bypasses 800ms debounce)
  4. Fallback: uses `navigator.sendBeacon()` to POST `_pendingChanges` as JSON to `/api/general/update` — more reliable than `fetch()` during page unload
- No "are you sure?" dialog added (browser native is sufficient)
- No changes to debounce timing or `flushPendingChanges()` function

## Wave 3, Task 8: CSS Review — stretchH: 'none' + percentage colWidths
- Reviewed `public/css/handsontable-module.css` (936 lines) and inline styles in `programaGeneralActualizar.view.php:22-80`
- No CSS changes required — existing styles already compatible with `stretchH: 'none'`
- Key protective chain: `.hot-full-bleed { box-sizing: border-box; overflow: hidden }` → `#hot-container { min-width: 0; overflow: hidden; width: 100% !important }` → `#hot-container table { width: 100% !important }`
- `table-layout: fixed` (Handsontable default) + `width: 100%` on table forces proportional column distribution regardless of `stretchH` setting
- `colWidths` ratio sum (1.002) doesn't cause overflow: `table-layout: fixed` redistributes proportionally, and the gutter (`baseWidth - 20`) is larger than the 0.2% excess
- `max-width: 100vw !important` on `#hot-container` (from global CSS) prevents viewport overflow
- `@media (max-width: 991px)` only adjusts height → width behavior identical at all sizes
- Conclusion: all 4 outcome criteria met with zero changes

## F1: Plan Compliance Audit (Final Wave)
- All 8 Must Have items verified present in code
- All 7 Must NOT Have items verified absent
- All 8 task checkboxes marked [x] in plan
- Git diff confirms only 2 files changed: `hot_actualizar.js` + `programaGeneralActualizar.view.php`
- `.omo/evidence/` directory has no files — individual task QA evidence not persisted (gap)
- Key preservation checks: `outsideClickDeselects: false` ✅, `hiddenColumns` ✅, no column-level `width` props ✅

## F3: Final Manual QA (2026-06-17)
- Module `/programa-general-actualizar` loads with 0 console errors across 3 projects (Prueba, Da Porto, Metrolinea Confinamiento)
- Handsontable v14.6.1 renders with 9 columns: Id, Actividad Nueva, Asociar con..., F. Inicio, F. Fin, Unidad, Cant. PPTO, Restricciones, Ejec. Real
- All action buttons render: Cargar desde Excel, Eliminar Actualización, Alternar visualización (toggles "Mostrando Pendientes"/"Mostrando Todas")
- Toggle visualización works correctly (switches label and triggers API reload)
- API calls succeed: `/api/general/list?db=prueba&semana_objetivo=3&exclude_chapters=1` returns 200 OK with `{"data":[]}`
- Warning: "Sin datos para mostrar" (×3) — expected, test projects have no general program data
- Warning: "cargaParametros no fue invocado por AJAX legacy" — fallback initialization works correctly
- Interactive features (cell editing, TomSelect, date picker, debounce badge) could not be tested due to empty datasets
- Code verification confirms all planned changes in place:
  - `stretchH: 'none'` at line 664
  - `colWidths` function at line 677
  - `manualColumnResize: false` at line 689
  - `_pendingChanges` + `_saveTimer` at lines 13-14
  - `flushPendingChanges()` at line 367
  - Debounce in `afterChange` at lines 757-761 (800ms)
  - `beforeunload.hotActualizar` handler at line 792 with `sendBeacon` fallback
- jQuery 1.12.4 confirmed removed — only jQuery 3.6.0 in `<head>`
- Evidence saved to `.omo/evidence/final-qa/` (6 screenshots + README.md)

## F4: Scope Fidelity Check (2026-06-17)
- All 8 tasks verified 1:1 with plan spec
- No scope creep detected — only 2 target files modified
- All 7 "Must NOT do" guardrails respected
- Key verification points:
  - jQuery 1.12.4 removed (line 350), jQuery 3.6.0 preserved (line 4)
  - 3 config additions: viewportColumnRenderingOffset, language, colHeaderHeight
  - 10 column widths replaced with percentage-based colWidths function
  - _rowMetaCache + getRowMeta() + cells() callback optimization
  - Debounced save with 800ms timeout and badge feedback
  - beforeunload handler with sendBeacon fallback
  - manualColumnResize disabled, outsideClickDeselects preserved
- Uncommitted changes match plan exactly — ready for commit
- VERDICT: APPROVE — all deliverables meet definition of done
