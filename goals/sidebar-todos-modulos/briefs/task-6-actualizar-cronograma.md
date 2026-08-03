# Task 6 — Migrar Actualizar Cronograma al shell sidebar

## Objetivo
Que `/programa-general-actualizar` use el shell sidebar canónico en ambos estados, suprimiendo su navbar superior legacy. El delta clave: **recalcular la geometría** del Handsontable (hoy hardcodea la altura del navbar).

## Plantilla (recipe validada)
Mismo cableado del shell ya aplicado 4 veces (mira `git show 3a968dd`):
1. Body con `aia-shell aia-shell--sidebar` (+ su clase `pg-page` u otra existente).
2. `require __DIR__ . '/../partials/shell_sidebar.php';` al inicio del `<body>`.
3. `window.__AIA_SHELL_SIDEBAR__ = true;` **antes** de `/js/cargarDatosGeneralesPagina2.js` (suprime navbar legacy).
4. `DesignSystemHeadComponent::renderScript('/js/modules/aia_ui/sidebar_navigation.js')`.
5. `$shellActive = 'actualizar-cronograma';`, `$shellModuleLabel = 'Actualizar Cronograma';`, `$shellWeeks` con la misma fuente/forma (query `semanas_activas` por `project_id`). En `ProgramaGeneralActualizarController::index` o al inicio de la vista antes del `require`.

## Deltas específicos (del inventario — CUIDADO con la geometría)
- Archivos: `views/programa-general-actualizar/programaGeneralActualizar.view.php`, `src/Controllers/**/ProgramaGeneralActualizarController.php`.
- **Geometría hardcodeada**: hay un `<style>` inline con `body.pg-page{overflow:hidden}` y `.hot-full-bleed{height: calc(100vh - 80px)}` — ese `80px` asumía la altura del navbar legacy. Al suprimir el navbar y añadir la **context-bar del shell** (altura distinta), ese cálculo queda mal → el Handsontable se cortará o dejará hueco. **Recalcula** ese alto para la nueva geometría (mide la altura real de la context-bar del shell / usa el mismo enfoque que PI o Programa General, que ya conviven bien). Objetivo: HOT llena el área sin cortarse, sin scroll vertical raro y **sin overflow horizontal** con el rail izquierdo.
- Handsontable se inicializa vía AJAX legacy (`cargaParametros`) con fallback que inicializa HOT directo. Asegura que re-renderice al ancho reducido por el rail.
- Carga **jQuery/jQuery-UI + TomSelect por CDN**. `sidebar_navigation.js` es vanilla (no jQuery), así que la sidebar no se ve afectada; verifica igual toggle/flyouts.
- Es week-scoped (semana base de actualización): pasa `$shellWeeks` real.
- No tiene cajón LPS (no lo agregues). Vista grande (~982 líneas) con lógica inline — cambio mínimo, no refactorices.

## Harness
- Agrega `'/programa-general-actualizar'` a `MIGRATED` en `tests/browser/shell-sidebar-rollout.mjs` (ya está registrado con `active: 'actualizar-cronograma'`).

## Restricciones
- 1180×820 dark, desktop only. No mobile/tablet/linen.
- Cambios acotados a vista + controlador + harness. No toques partial/CSS/JS canónicos ni PDC.
- Directo en main; `git add` explícito; verifica staging — nada de PDC.
- Default colapsado.

## Verificación (ejecuta y reporta)
1. `docker compose exec -T app php -l views/programa-general-actualizar/programaGeneralActualizar.view.php` (+ controlador si lo tocas).
2. `node tests/browser/shell-sidebar-rollout.mjs` → los 6 módulos migrados en PASS (resto PENDING, exit 0). Pega el resumen.
3. **Recomendado screenshot** colapsado + expandido 1180×820 dark: confirma que el HOT llena el área sin cortarse ni dejar hueco tras recalcular la altura, y sin overflow.
4. Commit: `feat(shell-sidebar): Actualizar Cronograma usa el shell sidebar (ambos estados)` + trailer `Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>`.

## Reporte
`goals/sidebar-todos-modulos/reports/task-6-report.md`. Devuelve SOLO: status, hash del commit, resumen de test en una línea, concerns (incluido cómo recalculaste la altura). Si la geometría no se resuelve con cambio mínimo, reporta BLOCKED con detalle.
