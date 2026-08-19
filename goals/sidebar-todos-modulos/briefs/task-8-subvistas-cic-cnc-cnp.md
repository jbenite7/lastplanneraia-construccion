---
capa: fuente
tipo: goal-doc
estado: vigente
fecha: 2026-08-02
areas: [proceso]
fuente: goals/sidebar-todos-modulos/briefs/task-8-subvistas-cic-cnc-cnp.md
resumen: Que las 3 subvistas de Programación Semanal —/programacion-semanal/cic, /cnc, /cnp— usen el shell sidebar canónico en ambos estados, suprimiendo su navbar…
---

# Task 8 — Migrar subvistas CIC / CNC / CNP al shell sidebar

## Objetivo
Que las 3 subvistas de Programación Semanal —`/programacion-semanal/cic`, `/cnc`, `/cnp`— usen el shell sidebar canónico en ambos estados, suprimiendo su navbar superior legacy. Son páginas HTML completas e independientes (cada una con su propio `<!DOCTYPE>/head/body class="ps-page"`), servidas por `ProgramacionSemanalController::cic()/cnc()/cnp()` (cada método hace `require` de su vista). Usan DataTables (no Handsontable).

## Plantilla (recipe validada)
Mismo cableado del shell aplicado 6 veces (la supresión de navbar en módulo DataTables ya se validó en Control de Cambios, commit `30e0a17`). Para CADA una de las 3 vistas (`views/programacion-semanal/CIC.view.php`, `CNC.view.php`, `CNP.view.php`):
1. Body con `aia-shell aia-shell--sidebar ps-page` (conserva `ps-page`).
2. `require __DIR__ . '/../partials/shell_sidebar.php';` al inicio del `<body>`.
3. `window.__AIA_SHELL_SIDEBAR__ = true;` **antes** de `/js/cargarDatosGeneralesPagina2.js` (suprime navbar legacy).
4. `DesignSystemHeadComponent::renderScript('/js/modules/aia_ui/sidebar_navigation.js')`.
5. `$shellActive = 'programacion-semanal';` (las 3 son sub-páginas de Prog. Semanal → resaltan ese ítem del nav), `$shellModuleLabel` = el nombre de la subvista ('CIC' / 'CNC' / 'CNP' o su título completo — usa el que ya muestre la vista), y `$shellWeeks` con la misma fuente/forma. Setéalo en los métodos `cic()/cnc()/cnp()` del controlador o al inicio de cada vista antes del `require`.

## Deltas específicos (del inventario)
- Controlador: `src/Controllers/Programacion/ProgramacionSemanalController.php` métodos `cic()`, `cnc()`, `cnp()`. Ojo: `cic()` hoy pasa MENOS variables (no `area`/`permiso`); el partial cae a `$_SESSION` para RBAC, así que asegúrate de que la sesión tenga `permiso`/`area` (normalmente sí). Si el partial falla por falta de datos, provéelos desde el controlador.
- DataTables (`#dt_cliente.dt_programacionSemanal`, jQuery/jQuery-UI CDN): riesgo de overflow horizontal con el rail izquierdo. El harness verifica cero-scroll y sin overflow horizontal — úsalo como juez; resuelve overflow con cambio mínimo.
- Son week-scoped: `$shellWeeks` real.
- Reutilizan CSS compartido con la vista base (`programacion-semanal.css`): NO lo toques. Si hay geometría a corregir, hazlo scoped por `body.aia-shell--sidebar` inline en cada vista (como Task 7).

## Harness
- En `tests/browser/shell-sidebar-rollout.mjs`: agrega al array `ALL_ROUTES` las 3 subvistas y a `MIGRATED`:
  - `{ route: '/programacion-semanal/cic', active: 'programacion-semanal', label: 'CIC' }`
  - `{ route: '/programacion-semanal/cnc', active: 'programacion-semanal', label: 'CNC' }`
  - `{ route: '/programacion-semanal/cnp', active: 'programacion-semanal', label: 'CNP' }`
- **Interceptación de mutaciones**: navegar a estas subvistas puede disparar guardados propios. El harness ya intercepta `api/semanal/save`, `api/semanal/auto-program`, `nueva_semana.php`, `eliminar_semana.php`, `verificarCICActualizada.php`. Si al correr el harness observas otras llamadas de mutación (POST que escriben) desde estas subvistas, añádelas a la interceptación con `page.route` devolviendo una respuesta OK/inocua, para NO mutar la BD compartida. Documenta cuáles añadiste.

## Restricciones
- 1180×820 dark, desktop only.
- Cambios acotados a: las 3 vistas CIC/CNC/CNP + el controlador (métodos cic/cnc/cnp) + el harness. No toques la vista base ya migrada, ni el CSS compartido, ni partial/CSS/JS canónicos, ni PDC.
- Directo en main; `git add` explícito; verifica staging — nada de PDC. Default colapsado.

## Verificación (ejecuta y reporta)
1. `docker compose exec -T app php -l` en las 3 vistas (+ controlador).
2. `node tests/browser/shell-sidebar-rollout.mjs` → 7 módulos base + las 3 subvistas en PASS (Indicadores y Control Tower PENDING), exit 0. Pega el resumen y confirma que NINGUNA mutación real llegó al backend (todas interceptadas).
3. Commit: `feat(shell-sidebar): subvistas CIC/CNC/CNP usan el shell sidebar (ambos estados)` + trailer `Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>`.

## Reporte
`goals/sidebar-todos-modulos/reports/task-8-report.md`. Devuelve SOLO: status, hash del commit, resumen de test en una línea (incluye cuántas rutas PASS), concerns (incluidas mutaciones interceptadas añadidas). Si alguna subvista se bloquea, reporta BLOCKED con detalle.
