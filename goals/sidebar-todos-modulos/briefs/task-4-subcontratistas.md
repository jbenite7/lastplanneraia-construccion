# Task 4 — Migrar Subcontratistas al shell sidebar

## Objetivo
Que `/subcontratistas` use el shell sidebar canónico en ambos estados, suprimiendo su navbar superior legacy. Es casi idéntico a Profesionales.

## Plantilla (recipe validada)
Replica exactamente la migración de **Profesionales** (commit `3a968dd`): mira `git show 3a968dd` (vista + controlador + harness). Cablea:
1. Body con `aia-shell aia-shell--sidebar` (+ clase de página existente).
2. `require __DIR__ . '/../partials/shell_sidebar.php';` al inicio del `<body>`.
3. `window.__AIA_SHELL_SIDEBAR__ = true;` **antes** de `/js/cargarDatosGeneralesPagina2.js` (suprime el navbar legacy).
4. `DesignSystemHeadComponent::renderScript('/js/modules/aia_ui/sidebar_navigation.js')`.
5. `$shellActive = 'subcontratistas';`, `$shellModuleLabel = 'Subcontratistas';`, y `$shellWeeks` con la MISMA fuente/forma que Profesionales/PG (query `semanas_activas` por `project_id`, guard de `dbName`, try/catch → `[]`). Setéalo en `SubcontratistasController::index` o al inicio de la vista antes del `require`.

## Deltas específicos (del inventario)
- Archivos: `views/subcontratistas/subcontratistas.view.php`, `src/Controllers/Gestion/SubcontratistasController.php`.
- No es week-scoped, pero igual pasa `$shellWeeks` (paridad de flyouts).
- **Handsontable 14.6.1 por CDN** (jsdelivr, distinto que otros módulos). NO cambies la versión ni el origen en esta tarea (fuera de alcance); solo asegura que el HOT no desborde horizontalmente con el rail izquierdo (dispara resize/updateSettings si hace falta).
- Tiene branch `$isPreConstruccion` (deriva de `$area`) que cambia etiquetas ("Interesados Externos" vs "Subcontratistas"). No afecta el shell; `$shellActive` sigue siendo `'subcontratistas'`. No toques esa lógica.
- Mismo CSS full-viewport (`overflow:hidden`, flex column, `#hot-container` calc de alto) que Profesionales: verifica ambos estados sin overflow ni scroll vertical raro.
- No tiene cajón LPS (no lo agregues). CSS muerto del navbar legacy: déjalo si dudas.

## Harness
- Agrega `'/subcontratistas'` a `MIGRATED` en `tests/browser/shell-sidebar-rollout.mjs`.

## Restricciones
- 1180×820 dark, desktop only. No mobile/tablet/linen.
- Cambios acotados a vista + controlador de Subcontratistas + harness. No toques partial/CSS/JS canónicos ni PDC.
- Directo en main; `git add` explícito; verifica staging (`git diff --cached --name-only`) — nada de PDC.
- Default colapsado.

## Verificación (ejecuta y reporta)
1. `docker compose exec -T app php -l views/subcontratistas/subcontratistas.view.php` (+ controlador si lo tocas).
2. `node tests/browser/shell-sidebar-rollout.mjs` → PI, PG, Profesionales y Subcontratistas en PASS, resto PENDING, exit 0. Pega el resumen.
3. Commit: `feat(shell-sidebar): Subcontratistas usa el shell sidebar (ambos estados)` + trailer `Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>`.

## Reporte
`goals/sidebar-todos-modulos/reports/task-4-report.md`. Devuelve SOLO: status, hash del commit, resumen de test en una línea, concerns.
