---
capa: fuente
tipo: goal-doc
estado: vigente
fecha: 2026-08-02
areas: [proceso]
fuente: goals/sidebar-todos-modulos/briefs/task-3-profesionales.md
resumen: Que /profesionales use el shell sidebar canónico en ambos estados, suprimiendo su navbar superior legacy.
---

# Task 3 — Migrar Profesionales al shell sidebar

## Objetivo
Que `/profesionales` use el shell sidebar canónico en ambos estados, suprimiendo su navbar superior legacy.

## Plantilla (recipe ya validada)
Sigue exactamente el patrón de la migración de **Programa General** (commit `da792e8`): vista `views/programa-general/programa_general.view.php` + su controlador. Cablea:
1. Body con `aia-shell aia-shell--sidebar` (+ la clase de página que ya tenga).
2. `require __DIR__ . '/../partials/shell_sidebar.php';` al inicio del `<body>`.
3. `window.__AIA_SHELL_SIDEBAR__ = true;` **antes** de que se cargue `/js/cargarDatosGeneralesPagina2.js`. En este módulo ese flag es clave: **suprime el navbar superior legacy** que hoy inyecta ese loader (guardas en el propio JS). Verifica que tras el flag NO se monte el navbar.
4. `DesignSystemHeadComponent::renderScript('/js/modules/aia_ui/sidebar_navigation.js')`.
5. Proveer `$shellActive = 'profesionales';`, `$shellModuleLabel = 'Profesionales';`, y `$shellWeeks` con las semanas del proyecto en la misma forma/fuente que PG (para que los flyouts de semana de PG/PI/PS y el chip de semana funcionen también aquí). Setéalo en el controlador `ProfesionalesController::index` o al inicio de la vista, antes del `require`.

## Deltas específicos de Profesionales (del inventario)
- Archivos: `views/profesionales/profesionales.view.php`, `src/Controllers/Gestion/ProfesionalesController.php`.
- **No es week-scoped** funcionalmente, pero igual pasa `$shellWeeks` (paridad de flyouts).
- **Handsontable inline** (`new Handsontable(...)` en `updateOrInitHandsontable`, ~línea 534) con recálculo de `colWidths` por `#hot-container.offsetWidth` en `resize`. Con el rail izquierdo el ancho del área se reduce → asegura que el HOT re-renderice al ancho correcto (dispara su resize/`updateSettings` tras cargar si hace falta) SIN overflow horizontal.
- CSS full-viewport inline: `html,body{height:100%;overflow:hidden}`, `body{display:flex;flex-direction:column}`, `#hot-container{height: calc(100vh - 180px)}`. El `180px` asumía el navbar legacy; ahora hay context-bar del shell en su lugar. Verifica que el HOT no se corte ni deje scroll vertical raro y que no haya overflow horizontal; ajusta el `calc(...)` si la geometría cambió.
- Hay un bloque CSS inline (~líneas 238-313) que estiliza el navbar legacy (`#navbarSupportedContent`, `.navbar-nav`, `.navbar-brand`). Al suprimir el navbar ese CSS queda muerto: puedes eliminarlo si es claramente solo-navbar y no afecta otra cosa; si dudas, déjalo (no over-refactorices). Prioriza que ambos estados funcionen sin overflow.
- No tiene cajón LPS (no lo agregues).

## Harness
- Agrega `'/profesionales'` a `MIGRATED` en `tests/browser/shell-sidebar-rollout.mjs`. (El fold de localStorage ya está.)

## Restricciones
- 1180×820 dark, desktop only. No mobile/tablet/linen.
- Cambios acotados a la vista + controlador de Profesionales + el harness. No toques el partial/CSS/JS canónicos ni trabajo PDC del árbol.
- Directo en main; `git add` explícito de los archivos tocados; verifica staging (`git diff --cached --name-only`) — nada de PDC.
- Default colapsado. No cambies initialState.

## Verificación (ejecuta y reporta)
1. `docker compose exec -T app php -l views/profesionales/profesionales.view.php` (+ controlador si lo tocas).
2. `node tests/browser/shell-sidebar-rollout.mjs` → PI, PG y Profesionales en PASS, resto PENDING, exit 0. Pega el resumen.
3. Recomendado: screenshot colapsado + expandido 1180×820 dark confirmando HOT sin overflow y navbar legacy ausente.
4. Commit atómico: `feat(shell-sidebar): Profesionales usa el shell sidebar (ambos estados)` + trailer `Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>`.

## Reporte
`goals/sidebar-todos-modulos/reports/task-3-report.md`. Devuelve SOLO: status, hash del commit, resumen de test en una línea, concerns.
