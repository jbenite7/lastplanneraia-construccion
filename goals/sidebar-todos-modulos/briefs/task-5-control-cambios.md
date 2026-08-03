# Task 5 — Migrar Control de Cambios al shell sidebar

## Objetivo
Que `/control-cambios` use el shell sidebar canónico en ambos estados, suprimiendo su navbar superior legacy. Es el módulo más divergente del Grupo A: usa DataTables/Tabulator/charts (no Handsontable) y layout en flujo de documento.

## Plantilla (recipe validada)
El cableado del shell es el mismo de Profesionales/Subcontratistas (commits `3a968dd`, `daae7a6` — míralos):
1. Body con `aia-shell aia-shell--sidebar` (+ clase de página existente si la hay).
2. `require __DIR__ . '/../partials/shell_sidebar.php';` al inicio del `<body>`.
3. `window.__AIA_SHELL_SIDEBAR__ = true;` **antes** de `/js/cargarDatosGeneralesPagina2.js` (suprime el navbar legacy).
4. `DesignSystemHeadComponent::renderScript('/js/modules/aia_ui/sidebar_navigation.js')`.
5. `$shellActive = 'control-cambios';`, `$shellModuleLabel = 'Control de Cambios';`, y `$shellWeeks` con la misma fuente/forma que los anteriores (query `semanas_activas` por `project_id`, guard `dbName`, try/catch → `[]`). En `ControlCambiosController::index` o al inicio de la vista antes del `require`.

## Deltas específicos (del inventario — CUIDADO)
- Archivos: `views/control-cambios/controlCambios.view.php`, `src/Controllers/Integracion/ControlCambiosController.php`.
- **NO es Handsontable**: usa DataTables (`#dt_cliente`), Tabulator (`dt_soportes`), Google Charts, AnyChart, Select2, jsPDF/html2canvas — todo por CDN. La init de la grilla vive en JS de sección externo, no en la vista.
- **Doble jQuery/Bootstrap**: la vista carga al FINAL del body una segunda copia de jQuery 1.12.4 + Popper + Bootstrap 4.3.1 (además del jQuery del DS en el head). NO intentes arreglar ese conflicto en esta tarea (fuera de alcance). `sidebar_navigation.js` es vanilla JS (no depende de jQuery), así que el flyout/toggle no se ve afectado — pero **verifica** que la sidebar funciona (toggle, flyouts) pese al doble jQuery.
- **Layout en flujo de documento** (no full-viewport flex como los HOT): `.encabezado`/`.direccionSeccion`/`.tabla` legacy. Con el shell, el `body.aia-shell--sidebar` gana `padding-left` (rail izquierdo) → el contenido se corre a la derecha. **Verifica que no haya overflow horizontal** en ambos estados (la tabla ancha y los modales XL `#modalordenDeCambio` son el riesgo). Ajusta anchos/overflow SOLO si es necesario para eliminar overflow, con el mínimo cambio.
- **Context-bar vs `.encabezado` legacy**: el partial del shell añade su propia context-bar (proyecto/módulo/semana). Si la vista tiene un `.encabezado`/título legacy redundante, decide: si duplica lo que muestra la context-bar, ocúltalo/retíralo; si aporta info propia del módulo, déjalo. Documenta la decisión en el reporte.
- No es week-scoped (órdenes de cambio a nivel proyecto), pero igual pasa `$shellWeeks` (paridad).
- No tiene cajón LPS (no lo agregues).

## Harness
- Agrega `'/control-cambios'` a `MIGRATED` en `tests/browser/shell-sidebar-rollout.mjs`.

## Restricciones
- 1180×820 dark, desktop only. No mobile/tablet/linen.
- Cambios acotados a vista + controlador de Control de Cambios + harness. No toques partial/CSS/JS canónicos ni PDC. No refactorices el stack de librerías CDN.
- Directo en main; `git add` explícito; verifica staging — nada de PDC.
- Default colapsado.

## Verificación (ejecuta y reporta)
1. `docker compose exec -T app php -l views/control-cambios/controlCambios.view.php` (+ controlador si lo tocas).
2. `node tests/browser/shell-sidebar-rollout.mjs` → PI, PG, Profesionales, Subcontratistas y Control de Cambios en PASS, resto PENDING, exit 0. Pega el resumen. (El harness ya verifica cero-scroll del nav y sin overflow horizontal del documento en ambos estados — clave aquí.)
3. Recomendado: screenshot colapsado + expandido 1180×820 dark confirmando tabla/modales sin overflow y sidebar operativa.
4. Commit: `feat(shell-sidebar): Control de Cambios usa el shell sidebar (ambos estados)` + trailer `Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>`.

## Reporte
`goals/sidebar-todos-modulos/reports/task-5-report.md`. Devuelve SOLO: status, hash del commit, resumen de test en una línea, concerns (incluida la decisión sobre `.encabezado`). Si el layout en flujo genera overflow que no puedes resolver con cambio mínimo, reporta BLOCKED con detalle.
