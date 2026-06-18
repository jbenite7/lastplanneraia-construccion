# Learnings

## 2026-06-18 Session Start
- Plan: corregir-fluidez-pg-actualizar-jmc
- Dataset: Optimización Aeropuerto JMC (`optimizacionJMC`)
- Target week: 3 (~1475 non-title rows)
- Key files: `hot_actualizar.js`, `programaGeneralActualizar.view.php`
- Reference modules: PG, PI, PS (read-only)

## 2026-06-18 jQuery UI Dedupe
- Removed duplicate jquery-ui.js at line 355 (body) in `programaGeneralActualizar.view.php`
- Kept the head occurence at line 5 (required by datepicker in "Cargar desde Excel" modal)
- Before: 2 occurrences | After: 1 occurrence
- Evidence: `.omo/evidence/pg-actualizar-jmc/jquery-ui-dedupe.txt`

## 2026-06-18 Baseline Runtime Measurement (JMC)
- **Baseline file**: `.omo/evidence/pg-actualizar-jmc/baseline.json`
- **Screenshots**: `baseline-full-page.png`, `baseline-viewport.png`, `baseline-after-toggle.png`

### Key Metrics
| Metric | Value |
|--------|-------|
| DOMContentLoaded | 69ms |
| API response time (first) | 52ms |
| API response time (second) | 40ms |
| API response size | 1.47MB |
| API data rows | 1475 |
| Rendered rows (Pendientes) | 348 |
| DOM rows visible | 32 (virtualized) |
| Toggle Pendientes→Todas | **12,553ms** |
| Toggle Todas→Pendientes | 2,934ms |
| Cell click (editor) | 503ms |
| Scroll to 500px | 0ms (instant) |
| Total network requests | 61 |
| Handsontable version | 14.6.1 |

### Critical Findings
1. **Toggle is 12.5s on first switch** — CRITICAL performance bottleneck
2. **Duplicate API calls** — HOTActualizarModule.init() called twice, fetching 1.47MB twice
3. **1.47MB API payload** — large response for `/api/general/list`
4. **Double init detected** — module init runs twice (fallback path + normal path)
5. **No editor overlay** — cell click doesn't show date picker (custom implementation)
6. **Fast initial load** — DOMContentLoaded 69ms, but grid interaction is slow

## 2026-06-18 Performance Optimization (hot_actualizar.js)

### Optimizations Applied
| Area | Change | Expected Impact |
|------|--------|-----------------|
| A. refreshHotLayout | Height guard (`_lastAppliedContainerHeight`), removed redundant `hot.render()` | Fewer render cycles on resize/orientation |
| B. colWidths | Ratios normalized to sum 1.0, container width cached (`_colContainerWidth`), widths cached (`_colWidthCache`) | Eliminates 10 DOM reads per render + fixes right-side gap |
| C. cells() | Cached columns array (`_cachedColumns`), cached source data (`_cachedSourceData`), avoided per-cell `getSettings().columns[col]` | Eliminates 14,750 `getSettings()` calls per full render |
| D. renderers | `pgPercentRenderer` uses `textContent` instead of `innerHTML`; `pgEjecutadoRealRenderer` uses `_cachedSourceData` | Reduced DOM manipulation overhead |
| E. beforeunload | Removed broken `sendBeacon` fallback (it sent empty JSON after `flushPendingChanges()` cleared `_pendingChanges`) | Eliminated silent data loss bug |
| F. double-init | Closure variable `_initDone` + `_loadDataFetched` guard prevents duplicate `loadData()` fetches | Prevents duplicate 1.47MB API calls |

### Key Findings
- **Raw col ratios actually sum to 1.002**, not 0.932 as stated in the baseline task. Normalized by dividing each by 1.002.
- **sendBeacon was doubly broken**: (1) `_pendingChanges` was already cleared by `flushPendingChanges()` before the beacon attempt, (2) endpoint expects `x-www-form-urlencoded` but beacon sent `JSON.stringify`.
- **`_initialized` guard moved to closure** (`_initDone`): The old approach set `window.HOTActualizarModule._initialized` as a property on the returned object. The view file still checks `window.HOTActualizarModule._initialized` (line 528) but it's now `undefined`, so the fallback always tries `init()` — however the closure guard makes it a no-op.
- **`_loadDataFetched` is a belt-and-suspenders guard**: Even if init somehow runs twice, `loadData()` itself won't fetch twice.

### Verification
- `node -c` syntax check: PASS
- `sendBeacon` grep: 0 matches (removed)
- `hot.render()` grep: only in comment explaining removal
- `getSettings().columns` in cells(): only in fallback path when `_cachedColumns` is null
- Editability rules: UNCHANGED (same `editableProps`, `isMapped`, `unidad='%'` checks)
- Visual output: UNCHANGED (all renderers preserved, colors/chips/formulas intact)
## 2026-06-18 Empty-Data Regression Pass (Prueba project)
- **Evidence**: `.omo/evidence/pg-actualizar-jmc/empty-regression.json`
- **Screenshot**: `empty-regression.png`
- **Verdict**: PASS
- **Grid rendering**: Header-only (9 columns, 0 data rows) — correct empty state
- **Console errors**: 0 blocking errors
- **Console warnings**: 3× "[MapeoManual] Sin datos para mostrar" + 1× "[Fallback] cargaParametros" — expected for empty project
- **Toggle**: Works correctly, toggled from "Mostrando Pendientes" → "Mostrando Todas" without crash
- **Buttons**: All available (Cargar desde Excel, Eliminar Actualización, Alternar visualización)
- **Caching variables** (`_cachedColumns`, `_cachedSourceData`, etc.): Handle empty arrays gracefully
- **_loadDataFetched guard**: Does not prevent empty data from loading (guards duplicate fetches, not empty results)
