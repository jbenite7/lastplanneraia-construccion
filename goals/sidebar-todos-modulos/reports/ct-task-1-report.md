---
capa: fuente
tipo: reporte
estado: vigente
fecha: 2026-08-02
areas: [proceso]
fuente: goals/sidebar-todos-modulos/reports/ct-task-1-report.md
resumen: node tests/browser/shell-sidebar-rollout.mjs → 60/60 checks OK, exit 0 (los 5 checks de /bi/control-tower pasan: default colapsado, toggle, cero-scroll del…
---

# Control Tower — Tarea 1: chrome del shell + tabs de hojas

**Status:** DONE
**Commit:** `6b0e34e` — feat(control-tower): consume el shell dark + tabs de hojas (retira bi-sidebar)

## Resumen del test

`node tests/browser/shell-sidebar-rollout.mjs` → **60/60 checks OK**, exit 0 (los 5 checks de `/bi/control-tower` pasan: default colapsado, toggle, cero-scroll del nav, sin overflow horizontal, `aria-current` en `control-tower`; sin regresión en las 11 rutas ya migradas).

## Cambios

- `views/bi/_layout.php`: retira `<aside class="bi-sidebar">`, `require` de `shell_sidebar.php`, `body.aia-shell aia-shell--sidebar bi-control-tower-page`, tema dark forzado (hardcode, sin lectura de localStorage/query), `window.__AIA_SHELL_SIDEBAR__ = true` + `sidebar_navigation.js`. Filtros quedan temporalmente en un bloque simple (`.bi-filters-block`) sobre el contenido, tal como pide la tarea (Task 2 los mueve al cajón).
- `views/bi/_nav.php`: lista vertical + `<select>` móvil → tira de tabs horizontal (`role="tablist"`/`role="tab"`), conservando exactamente los 8 `key` (`torre-control…cip`), los `id="nav-<key>"` y `class="nav-item"` que `bi-spa.js` usa en tiempo de ejecución (`switchView`).
- `src/Controllers/Bi/BiViewController.php`: `renderView()` ahora provee `$shellActive='control-tower'`, `$shellModuleLabel` (mapa por `reportKey`, fallback "Control Tower - Informes") y `$shellWeeks` vía `loadShellWeeks()` (mismo patrón que `ProgramacionSemanalController`: `semanas_activas` por `project_id`, guard regex sobre `dbName`, prepared statement, try/catch → `[]`).
- `tests/browser/shell-sidebar-rollout.mjs`: agrega `/bi/control-tower` a `MIGRATED`.
- `public/css/bi-control-tower.css`: **fix no listado originalmente en el brief pero necesario** — elimina la regla `@media (min-width:768px) { .bi-control-tower-page { flex-direction: row; flex-wrap: nowrap; } }`. Sin la bi-sidebar, esa regla (heredada del layout de 2 columnas) forzaba a `body` (ya `display:flex` de forma global vía `handsontable-module.css`, capa `vendor`) a distribuir la context-bar, la tira de tabs, el bloque de filtros y el contenido como columnas lado a lado en vez de apilarlos — rompía visualmente la página. Sin este fix, Task 1 no cumplía su propio criterio de aceptación (tabs operativas, sin overflow).

## Concerns / hallazgos

- **Auditoría `/api/bi/*` (paso 6):** revisé los 18 endpoints registrados bajo `/api/bi/*` (`BiControlTowerApiController`) — todos son `GET` y hacen únicamente `SELECT` (confirmado leyendo el controlador). No hay precedente tipo `/api/cic/list` en este módulo. Tampoco existe ningún caller de `ActionRecommendationService::createAction()/closeAction()` (los únicos métodos con `INSERT`/`UPDATE` en el árbol de servicios de BI) desde ninguna ruta ni desde `getBrief()`. **No agregué interceptores** al harness porque no hay nada que mutar al cargar `/bi/control-tower`.
- Tuve que ajustar además la estructura flex del `<body>` (`flex-shrink-0` en la tira de tabs y en el bloque de filtros, `flex-1 min-h-0 overflow-hidden` en `.bi-main-shell`) para evitar que el algoritmo de flexbox colapsara la tira de tabs a altura 0 (el "automatic minimum size" de flexbox la reducía a 0 por tener `overflow-x-auto` sin `flex-shrink:0`, dado que `body` es `height:100%; overflow:hidden` global). Verificado en vivo: sin overflow horizontal, contenido de `#views-container` alcanzable vía scroll interno de `.bi-main-content` (no se pierde/clip contenido).
- **Verificación visual con mouse real limitada:** en esta sesión el navegador integrado (`mcp__Claude_Browser__computer`) tuvo un descalibre de coordenadas (clicks con `coordinate`/`ref` aterrizaban en puntos muy distintos a los reportados — verificado instrumentando `pointerdown`/`click` con listeners). Confirmé el comportamiento de los tabs por vías equivalentes: `elementFromPoint` sobre las coordenadas reales del botón (sin overlay bloqueando), `document.getElementById('nav-<key>').click()` (dispara `switchView`, cambia título/`aria-current`/sección visible correctamente), sin errores de consola, y el harness Playwright (no afectado por este problema de calibración) en verde. Screenshots 1180×820 dark confirman: una sola sidebar, tira de tabs visible con los 8 items, sin overflow horizontal, colapsado por defecto.
- Los 19 hallazgos del hook de diseño en `bi-control-tower.css` (font-size fuera de la rampa, líneas 171+) son preexistentes al archivo, muy por debajo de la única sección que edité (línea ~88-97); no los toqué (fuera de alcance de esta tarea).

## Archivos relevantes

- `/Volumes/Crucial X6/Developer/lps-aia/views/bi/_layout.php`
- `/Volumes/Crucial X6/Developer/lps-aia/views/bi/_nav.php`
- `/Volumes/Crucial X6/Developer/lps-aia/src/Controllers/Bi/BiViewController.php`
- `/Volumes/Crucial X6/Developer/lps-aia/tests/browser/shell-sidebar-rollout.mjs`
- `/Volumes/Crucial X6/Developer/lps-aia/public/css/bi-control-tower.css`
