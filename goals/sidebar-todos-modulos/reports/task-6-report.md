# Task 6 — Actualizar Cronograma usa el shell sidebar

## Cableado
Mismo patrón de las 4 migraciones previas (`git show 3a968dd`): body con
`aia-shell aia-shell--sidebar pg-page`, `require .../partials/shell_sidebar.php` al
inicio del `<body>`, `window.__AIA_SHELL_SIDEBAR__ = true` antes de
`cargarDatosGeneralesPagina2.js`, `renderScript('/js/modules/aia_ui/sidebar_navigation.js')`,
y en `ProgramaGeneralActualizarController::index` los mismos `$shellWeeks` (query
`semanas_activas` por `project_id`), `$shellActive = 'actualizar-cronograma'`,
`$shellModuleLabel = 'Actualizar Cronograma'` que ya usan Programa General/Profesionales.

## Geometría del Handsontable (el delta clave)
El `<style>` inline original tenía `.hot-full-bleed { height: calc(100vh - 80px) }`,
calculado para el navbar legacy (~80px). Al suprimir ese navbar y usar la context-bar
sticky del shell, dejaba un hueco de 31px al fondo del HOT (verificado con
`getBoundingClientRect()` en el navegador integrado a 1180×820 dark: `.hot-full-bleed`
terminaba en `bottom: 789` contra un viewport de `820`).

Medí en runtime los elementos reales entre `<body>` y `.hot-full-bleed`:
- `#encabezado` (solo inputs ocultos): `height: 0`.
- `#shellContextBar` (la context-bar del shell, chip de semana incluido): `height: 49px`.

Añadí una regla más específica `body.aia-shell--sidebar .hot-full-bleed { height: calc(100vh - 49px) }`
(deja intacta la regla genérica `calc(100vh - 80px)` como fallback si algún día se
renderiza sin el shell). Con esto `.hot-full-bleed` termina exactamente en
`bottom: 820` = `innerHeight`, sin hueco y sin corte, verificado en ambos estados
(colapsado y expandido) vía `getBoundingClientRect()` — mismo enfoque usado por
Programa General (`calc(100vh - 315px)`) y PI (`calc(100vh - 280px)`), que también
hardcodean un número medido en vez de un cálculo dinámico. No toqué la media query
`@media (max-width: 991px)` (fuera del alcance desktop-only).

## Verificación
1. `php -l` sobre la vista y el controlador: sin errores de sintaxis.
2. `node tests/browser/shell-sidebar-rollout.mjs`: **30/30 checks OK**, exit 0 — los 6
   módulos migrados (Programación Intermedia, Programa General, Profesionales,
   Subcontratistas, Control de Cambios, Actualizar Cronograma) en PASS; `/programacion-semanal`,
   `/indicadores`, `/bi/control-tower` en PENDING.
3. Screenshot + medición JS en el navegador integrado (1180×820 dark), colapsado y
   expandido: HOT llena el área exacta (`bottom === innerHeight` en ambos estados),
   sin overflow horizontal (`scrollWidth === clientWidth === 1180`), ítem "Actualizar
   Cronograma" con `aria-current` correcto.

## Archivos tocados
- `views/programa-general-actualizar/programaGeneralActualizar.view.php`
- `src/Controllers/Programacion/ProgramaGeneralActualizarController.php`
- `tests/browser/shell-sidebar-rollout.mjs`

## Concerns
- El valor `49px` es un pixel medido a 1180×820 (viewport canónico de validación de
  esta app), no un cálculo dinámico — mismo enfoque que PG/PI. Si el contenido de la
  context-bar cambia de altura (p. ej. se agregan más elementos al chip de semana),
  este número necesitará remedirse.
- No se tocó CSS/JS canónico del design system ni PDC — cambio acotado a vista +
  controlador + harness, confirmado con `git diff --cached --name-only`.
