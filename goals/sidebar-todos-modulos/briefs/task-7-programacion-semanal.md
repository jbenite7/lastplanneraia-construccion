# Task 7 — Migrar Programación Semanal (vista base) al shell sidebar

## Objetivo
Que `/programacion-semanal` (la vista BASE) use el shell sidebar canónico en ambos estados, suprimiendo su navbar superior legacy. Las subvistas CIC/CNC/CNP son OTRA tarea (Task 8) — aquí solo la base.

## Plantilla (recipe validada)
Es muy similar a Programa General (Handsontable + cajón LPS + week-scoped), pero con navbar legacy a suprimir. Mismo cableado (mira `git show da792e8` para PG y `git show 3a968dd` para la supresión del navbar):
1. Body con `aia-shell aia-shell--sidebar ps-page` (conserva `ps-page`).
2. `require __DIR__ . '/../partials/shell_sidebar.php';` al inicio del `<body>`.
3. `window.__AIA_SHELL_SIDEBAR__ = true;` **antes** de `/js/cargarDatosGeneralesPagina2.js` (suprime navbar legacy).
4. `DesignSystemHeadComponent::renderScript('/js/modules/aia_ui/sidebar_navigation.js')`.
5. `$shellActive = 'programacion-semanal';`, `$shellModuleLabel = 'Programación Semanal';`, `$shellWeeks` con la misma fuente/forma (query `semanas_activas` por `project_id`). Setéalo en `ProgramacionSemanalController::index` (lee `$_SESSION` directo hoy) o al inicio de la vista antes del `require`.

## Deltas específicos (del inventario)
- Archivos: `views/programacion-semanal/programacion_semanal.view.php`, `src/Controllers/**/ProgramacionSemanalController.php` (método `index`).
- **Tiene cajón LPS derecho** (`drawer_unificado.php` + `lps_drawer.js`): CONSÉRVALO, coexiste como en PG/PI.
- Handsontable (`programacion_semanal/hot.js` + `changeMonitor.js`): asegura re-render al ancho reducido por el rail; sin overflow horizontal.
- Fuertemente week-scoped (semana activa, compromisos): `$shellWeeks` real es importante aquí (los flyouts de semana deben poblar).
- Si hay geometría hardcodeada de altura del HOT (tipo `calc(100vh - Npx)` asumiendo navbar), recalcúlala como en Task 6 (regla `body.aia-shell--sidebar` más específica midiendo la context-bar = 49px). Verifica que no se corte.
- NO toques la lógica del módulo (subcontratistas/profesionales/categoriasCnc que carga el controlador) — solo el cableado del shell.

## Harness
- Agrega `'/programacion-semanal'` a `MIGRATED` en `tests/browser/shell-sidebar-rollout.mjs`.
- NOTA: el landing por defecto del proyecto pasa por /programacion-semanal y dispara save/auto-program; el harness ya intercepta `api/semanal/save` y `api/semanal/auto-program`. Bien.

## Restricciones
- 1180×820 dark, desktop only. No mobile/tablet/linen.
- Cambios acotados a vista + controlador de Prog. Semanal base + harness. No toques partial/CSS/JS canónicos, ni las subvistas CIC/CNC/CNP (van en Task 8), ni PDC.
- Conserva el cajón LPS. Directo en main; `git add` explícito; verifica staging — nada de PDC. Default colapsado.

## Verificación (ejecuta y reporta)
1. `docker compose exec -T app php -l views/programacion-semanal/programacion_semanal.view.php` (+ controlador si lo tocas).
2. `node tests/browser/shell-sidebar-rollout.mjs` → los 7 módulos migrados en PASS (resto PENDING, exit 0). Pega el resumen.
3. Recomendado: screenshot ambos estados 1180×820 dark (HOT + cajón LPS coexistiendo sin overflow).
4. Commit: `feat(shell-sidebar): Programación Semanal usa el shell sidebar (ambos estados)` + trailer `Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>`.

## Reporte
`goals/sidebar-todos-modulos/reports/task-7-report.md`. Devuelve SOLO: status, hash del commit, resumen de test en una línea, concerns. Si te bloqueas, reporta BLOCKED con detalle.
