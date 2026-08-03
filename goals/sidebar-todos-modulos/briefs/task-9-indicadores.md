# Task 9 — Migrar Indicadores al shell sidebar

## Objetivo
Que `/indicadores` use el shell sidebar canónico en ambos estados, suprimiendo su navbar superior legacy, y **reanclando el embed de Power BI** para que coexista con el rail izquierdo sin overflow horizontal.

## Plantilla (recipe validada)
Cableado del shell igual que los anteriores (mira `git show 3a968dd` para supresión de navbar). La vista usa `DesignSystemHeadComponent::render()`.
1. Body con `aia-shell aia-shell--sidebar` (+ clase existente).
2. `require __DIR__ . '/../partials/shell_sidebar.php';` al inicio del `<body>`.
3. `window.__AIA_SHELL_SIDEBAR__ = true;` **antes** de `/js/cargarDatosGeneralesPagina2.js`.
4. `DesignSystemHeadComponent::renderScript('/js/modules/aia_ui/sidebar_navigation.js')`.
5. `$shellActive = 'indicadores';`, `$shellModuleLabel = 'Indicadores LPS';`, `$shellWeeks` con la misma fuente/forma (paridad; aunque el embed no es week-scoped).

## Delta CRÍTICO — el embed Power BI
- Archivos: `views/indicadores/indicadores.view.php`, `src/Controllers/Gestion/IndicadoresController.php`. La función clave es `ajustarInformePowerBI()` (en la vista, ~líneas 113-129) y el contenedor `#contenedorInformePowerBI`.
- Hoy el contenedor rompe el ancho a pantalla completa con CSS inline: `width:100vw; position:relative; left:50%; margin-left:-50vw;` — eso asume que el viewport completo es el área de contenido (sin sidebar). Con el shell, el `body.aia-shell--sidebar` tiene `padding-left` (rail izquierdo), así que ese hack `100vw/-50vw` hace que el iframe se salga bajo/sobre la sidebar → **overflow horizontal**.
- `ajustarInformePowerBI()` calcula el ancho máximo con `window.innerWidth * 0.95` y la altura con `window.innerHeight - getBoundingClientRect().top`. El `innerWidth` NO descuenta el ancho de la sidebar, y el `getBoundingClientRect().top` asumía el navbar/breadcrumb encima.
- **Qué hacer (cambio mínimo, scoped al shell):**
  - Reemplaza el full-bleed `100vw/margin-left:-50vw` por un layout que respete el área de contenido a la derecha del rail (que el contenedor ocupe el ancho disponible del `<main>`/área de contenido, no el viewport completo). Puedes hacerlo scoped con `body.aia-shell--sidebar #contenedorInformePowerBI { … }`.
  - Ajusta `ajustarInformePowerBI()` para dimensionar por el ancho del **contenedor/área de contenido** (`contenedor.clientWidth` o el ancho del main), no por `window.innerWidth`, y verifica que la altura siga correcta con la context-bar del shell (mide `getBoundingClientRect().top` real).
  - Objetivo verificable: en ambos estados (colapsado/expandido) NO hay overflow horizontal del documento y el embed queda dentro del área de contenido, re-dimensionándose al alternar el estado (dispara `ajustarInformePowerBI()` en el toggle si hace falta, o ya se re-engancha en `resize`).
- No es week-scoped funcionalmente; el embed es un reporte público "publish to web".
- No tiene cajón LPS (no lo agregues).

## Harness
- `/indicadores` ya está en `ALL_ROUTES` (`active: 'indicadores'`). Agrega `'/indicadores'` a `MIGRATED`.

## Restricciones
- 1180×820 dark, desktop only. No mobile/tablet/linen.
- Cambios acotados a vista + controlador de Indicadores + harness. No toques partial/CSS/JS canónicos ni PDC.
- Directo en main; `git add` explícito; verifica staging — nada de PDC/DESIGN.md/.impeccable. Default colapsado.

## Verificación (ejecuta y reporta)
1. `docker compose exec -T app php -l views/indicadores/indicadores.view.php` (+ controlador si lo tocas).
2. `node tests/browser/shell-sidebar-rollout.mjs` → las 10 rutas previas + Indicadores en PASS (Control Tower PENDING), exit 0. El check de "sin overflow horizontal en ambos estados" es el juez del reanclaje del embed.
3. **Recomendado screenshot** colapsado + expandido 1180×820 dark: el embed Power BI dentro del área de contenido, sin salirse bajo la sidebar, sin overflow.
4. Commit: `feat(shell-sidebar): Indicadores usa el shell sidebar y reancla el embed Power BI (ambos estados)` + trailer `Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>`.

## Reporte
`goals/sidebar-todos-modulos/reports/task-9-report.md`. Devuelve SOLO: status, hash del commit, resumen de test en una línea, concerns (incluido cómo reanclaste el embed). Si el reanclaje no se resuelve con cambio mínimo, reporta BLOCKED con detalle.
