# Task 2 — Migrar Programa General al shell sidebar

## Objetivo
Que `/programa-general` renderice el shell sidebar canónico en ambos estados, igual que `/programacion-intermedia`, reemplazando/rellenando su navegación superior. Es el módulo más cercano: hoy NO tiene navbar legacy (no carga `cargarDatosGeneralesPagina2.js`), su body es `aia-shell ... pg-page` (le falta `aia-shell--sidebar`) y no tiene context-bar. Ya tiene cajón LPS derecho (conservarlo) y Handsontable.

## Referencia correcta (léela primero)
- Vista PI: `views/programacion-intermedia/programacion_intermedia.view.php` — fíjate en: `<body class="aia-shell aia-shell--sidebar pi-page">`, `require __DIR__ . '/../partials/shell_sidebar.php';` al inicio del body, `window.__AIA_SHELL_SIDEBAR__ = true;`, y `DesignSystemHeadComponent::renderScript('/js/modules/aia_ui/sidebar_navigation.js')`.
- Controlador PI: `src/Controllers/Programacion/ProgramacionIntermediaController.php` (o el que renderice PI) — mira cómo provee a la vista/partial las variables del shell: `$shellActive`, `$shellModuleLabel`, `$shellWeeks` (forma: array de `['Semana'=>int,'Fecha_Inicio_Sem'=>?, 'Fecha_Fin_Sem'=>?]`). El partial `views/partials/shell_sidebar.php` las lee como locales con fallback a `$_SESSION`.

## Archivos objetivo
- Vista: `views/programa-general/programa_general.view.php`
- Controlador: `src/Controllers/**/ProgramaGeneralController.php` (método `index`)

## Qué hacer
1. En la vista: cambiar el `<body>` para incluir `aia-shell--sidebar` (queda `class="aia-shell aia-shell--sidebar pg-page"` o el orden que use hoy + la clase nueva).
2. `require __DIR__ . '/../partials/shell_sidebar.php';` justo al inicio del `<body>` (antes del contenido del módulo), igual que PI.
3. Definir `window.__AIA_SHELL_SIDEBAR__ = true;` antes de cargar los scripts del módulo (para que el loader legacy no monte navbar si en algún punto se cargara).
4. Añadir `DesignSystemHeadComponent::renderScript('/js/modules/aia_ui/sidebar_navigation.js')`.
5. Proveer al partial: `$shellActive = 'programa-general';`, `$shellModuleLabel = 'Programa General';` y `$shellWeeks` con las semanas del proyecto en la forma que espera el partial. Usa la MISMA fuente que PI (mira su controlador). Si PG ya calcula semanas/fechas (`$maxSemana`, fechas), transfórmalas a la forma esperada; si no, replica la consulta de PI. Setéalas en el controlador (preferido) o al inicio de la vista, ANTES del `require` del partial.
6. NO quitar el cajón LPS derecho (`drawer_unificado.php` + `lps_drawer.js`): debe seguir coexistiendo.
7. Verificar coexistencia del layout: el shell añade `padding-left` al `body.aia-shell--sidebar` (rail fijo a la izquierda). El Handsontable (`#hot-container`, `PGHotModule`) debe re-renderizar/ajustar ancho al área reducida sin overflow horizontal ni solaparse con el rail LPS derecho. Si el HOT no se reajusta, dispara su re-render/`updateSettings` tras cargar (mira cómo lo hace PI si aplica). El alto full-bleed (`calc(100vh - …)`) no debería cambiar, pero valida que no haya scroll horizontal.

## Harness (fold del Minor del review de Task 1)
En `tests/browser/shell-sidebar-rollout.mjs`:
- Agrega `'/programa-general'` a `MIGRATED`.
- **Robustez**: antes de las verificaciones de CADA ruta migrada, limpia el estado persistido para que el check de "default colapsado" sea genuino: navega a la ruta, luego `await page.evaluate(() => localStorage.removeItem('aia-sidebar-state'))` y recarga (`page.reload({waitUntil:'domcontentloaded'})`) antes de medir el estado inicial. (Aplica a todas las rutas migradas, incluida PI.)

## Restricciones (Global Constraints)
- Viewport 1180×820, dark. Desktop only. No mobile/tablet/linen.
- Cambios acotados a Programa General (su vista + su controlador) y al harness. NO toques otros módulos, ni el partial/CSS/JS canónicos del shell (ya funcionan), ni el trabajo PDC del árbol.
- Directo en main; commit atómico con `git add` explícito de los archivos tocados (vista PG, controlador PG, harness). Verifica el staging: nada de PDC ajeno.
- Conserva el cajón LPS. Default colapsado (no cambies initialState).

## Verificación que debes ejecutar y reportar
1. `docker compose exec -T app php -l views/programa-general/programa_general.view.php` (y el controlador si lo tocaste) → sin errores de sintaxis.
2. `node tests/browser/shell-sidebar-rollout.mjs` → `/programacion-intermedia` y `/programa-general` en PASS (ambos estados, cero-scroll, sin overflow, ítem activo), resto PENDING, exit 0. Pega el resumen.
3. Screenshot de evidencia opcional pero recomendado (colapsado y expandido) a 1180×820 dark para confirmar que HOT + rail LPS coexisten sin overflow.
4. Commit: `feat(shell-sidebar): Programa General usa el shell sidebar (ambos estados)` + trailer `Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>`.

## Reporte
Escribe el reporte completo en `goals/sidebar-todos-modulos/reports/task-2-report.md`. Devuelve SOLO: status, hash corto del commit, resumen de test en una línea, y concerns.
