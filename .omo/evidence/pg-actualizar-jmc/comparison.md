# Performance Comparison: Before vs After (FINAL)

**Project:** Optimización Aeropuerto JMC  
**Module:** Programa General Actualizar  
**Date:** 2026-06-18  
**Baseline:** 14:00 UTC | **After:** 14:57 UTC  

## Metrics Comparison

| Metric | Baseline | After | Change | Status |
|--------|----------|-------|--------|--------|
| Toggle Pendientes→Todas | 12,553ms | 604ms | -11,949ms (-95.2%) | ✅ MASSIVE improvement |
| Toggle Todas→Pendientes | 2,934ms | 615ms | -2,319ms (-79.0%) | ✅ MASSIVE improvement |
| API calls to /api/general/list | 2 (duplicate) | 1 | -1 | ✅ FIXED |
| API calls to /api/general/codigos | 2 (duplicate) | 1 | -1 | ✅ FIXED |
| Console errors | 0 | 0 | 0 | ✅ No change |
| Console warnings | 1 (fallback) | 0 | -1 | ✅ FIXED |
| _initialized flag | undefined | true | — | ✅ FIXED |
| DOMContentLoaded | 69ms | 74ms | +5ms | ✅ Nearly same |
| API response size | 1.47MB | 1.47MB | 0 | ⚠️ Unchanged (expected) |
| DOM rows rendered | 32 | 32 | 0 | ✅ No change |
| API data length | 1,475 | 1,475 | 0 | ✅ No change |
| Rendered after filter | 348 | 348 | 0 | ✅ No change |

## Summary

| Category | Verdict |
|----------|---------|
| **Double-Init Fix** | ✅ **FIXED** — 1 API call (was 2), _initialized=true, no fallback warning |
| **Toggle Performance** | ✅ **MASSIVE improvement** — 95.2% faster Pendientes→Todas, 79% faster Todas→Pendientes |
| **Initial Load** | ✅ **STABLE** — DOMContentLoaded 74ms (was 69ms) |
| **Console Health** | ✅ **CLEAN** — 0 errors, 0 warnings (was 1 warning) |
| **Overall** | ✅ **SUCCESS** — All critical issues resolved, massive performance improvement |

## Root Cause Analysis

The primary performance bottleneck was the **double-initialization** of `HOTActualizarModule.init()`. The module's init function was called twice:
1. By `cargaParametros()` (AJAX legacy callback)
2. By the fallback timeout in the view file (which checked `window.HOTActualizarModule._initialized`)

The original code set `_initialized` as a property on the module object, but the optimized code initially only used a closure variable `_initDone`, which the view file's fallback couldn't see. This caused the fallback to always trigger, calling `init()` a second time.

The fix involved:
1. Setting `window.HOTActualizarModule._initialized = true` in the init function (line 842)
2. Adding `_loadDataFetched` guard in `loadData()` to prevent duplicate API fetches
3. Updating the cache buster from `?v=20260526a` to `?v=20260618a` to ensure the browser fetches the updated JS file
4. Creating a symlink from `last-planner-aia-legacy-permisos` to `last-planner-aia` to fix the Docker volume mount

## Optimizations Applied

1. **refreshHotLayout**: Height-change guard, removed redundant `hot.render()` call
2. **colWidths**: Cached container width, normalized ratios to sum 1.0, no per-column DOM reads
3. **cells()**: Cached columns array and source data, no per-cell `getSettings()` calls
4. **Renderers**: `textContent` for `pgPercentRenderer`, cached source data for `pgEjecutadoRealRenderer`
5. **beforeunload**: Removed broken `sendBeacon` fallback (was sending empty JSON to URL-encoded endpoint)
6. **Double-init**: Closure `_initDone` flag + `window.HOTActualizarModule._initialized` + `_loadDataFetched` guard
