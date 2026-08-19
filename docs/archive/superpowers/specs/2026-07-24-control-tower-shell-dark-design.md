---
capa: fuente
tipo: spec
estado: cerrado
fecha: 2026-07-24
areas: [rbac, bi]
tags: [archivo]
fuente: docs/archive/superpowers/specs/2026-07-24-control-tower-shell-dark-design.md
resumen: unificación al shell dark (rediseño), no solo un enlace de regreso. 2. Layout A: sidebar de app (izq) + context-bar (top) + tira de tabs horizontal para las 8…
---

# Control Tower en el shell dark — diseño

**Fecha:** 2026-07-24 · **Estado:** aprobado en decisiones clave (chat), pendiente revisión del spec escrito · **Alcance:** desktop ≥1180px, dark. Sub-goal derivado del rollout `sidebar-todos-modulos` (Control Tower quedó diferido allí por ser un rediseño, no una migración mecánica).

## Problema / contexto

Los otros 10 módulos ya usan el shell sidebar canónico (`views/partials/shell_sidebar.php`). Control Tower NO: es una SPA autónoma (`views/bi/_layout.php`) con su **propia `<aside class="bi-sidebar w-72">`** (marca "Torre de Control" + nav de hojas `_nav.php` + filtros `_filters.php`), stack **Tailwind CDN + Lucide + Chart.js**, y **tema por defecto `linen`**. No consume el `render()` del DS ni el shell. Resultado: navegación de app inconsistente (no se puede saltar a otros módulos) y dos lenguajes visuales.

## Decisiones (del brainstorming)

1. **Objetivo:** unificación al shell dark (rediseño), no solo un enlace de regreso.
2. **Layout A:** sidebar de app (izq) + context-bar (top) + **tira de tabs horizontal** para las 8 hojas + **cajón derecho** para los filtros + dashboards en el contenido.
3. **Tema:** Control Tower pasa a **dark** (deja el default `linen`; el shell es dark-only por contrato del DS/AGENTS.md).
4. **Dark de los dashboards Chart.js INCLUIDO en este spec** (paleta de series, cards, ejes, tooltips), no diferido.
5. **Cajón de filtros propio** (mismo lenguaje visual/interacción que el cajón LPS, pero componente independiente — no acoplar filtros BI a `drawer_unificado.php`/`lps_drawer.js`).

## Arquitectura

Control Tower deja de ser un documento HTML autónomo y pasa a **consumir el shell canónico**, igual que los otros módulos. La SPA por dentro (`public/js/modules/bi-spa.js`, `switchView()`, endpoints `/api/bi/*`) **no cambia**: solo se reemplaza el chrome (contenedor) y se aplica el tema dark. Es un cambio de *layout + tema*, no de lógica de datos.

- El controlador `src/Controllers/Bi/BiViewController.php` (`renderView`) sigue resolviendo `$reportKey`, `$semana`, `$initialData`, `$viewFile`. Se añade la provisión de `$shellActive='control-tower'`, `$shellModuleLabel`, `$shellWeeks` (misma fuente/forma que el resto: `semanas_activas` por `project_id`).
- `views/bi/_layout.php` se reescribe para: body `aia-shell aia-shell--sidebar`, `require shell_sidebar.php`, `window.__AIA_SHELL_SIDEBAR__ = true`, `renderScript('/js/modules/aia_ui/sidebar_navigation.js')`, y forzar `data-aia-theme="dark"` (retirar el default linen). Se retira el `<aside class="bi-sidebar">`.
- Las **8 rutas `/bi/*`** (`control-tower`, `programa-general`, `intermedia`, `semanal`, `pdc`, `contratistas`, `responsables`, `curva-s`) comparten `_layout.php` → migrar el layout las cubre todas.

## Componentes

### C1 — Consumo del shell (chrome de app)
- **Qué hace:** pone la sidebar de app + context-bar alrededor del contenido BI.
- **Interfaz:** el partial `shell_sidebar.php` (sin cambios); variables `$shellActive/$shellModuleLabel/$shellWeeks` desde el controlador.
- **Depende de:** `sidebar_navigation.js`, `shell_sidebar.php`, tokens del DS.

### C2 — Tira de tabs de hojas
- **Qué hace:** reemplaza el `_nav.php` (lista vertical + `<select>` móvil) por una **tira horizontal de tabs** bajo la context-bar, dentro del área de contenido. Cada tab llama al `switchView(key)` existente; `aria-current="page"` en la activa.
- **Interfaz:** mismo contrato JS (`switchView`), mismos 8 keys. Se retira el `<select>` móvil (desktop-only).
- **Depende de:** `bi-spa.js` (sin cambios en su API); estilos DS para tabs.
- **Nota UX:** 8 tabs a ~900px de contenido (1180 − sidebar) caben; si se aprietan, tira con scroll horizontal interno propio (nunca overflow del documento).

### C3 — Cajón de filtros (componente propio)
- **Qué hace:** mueve el formulario `_filters.php` (Proyectos multi-select, Semana/rango, Sub, Responsable, Etapa, Aplicar/Limpiar) a un **cajón derecho** con trigger "Filtros (N)". Mismo lenguaje visual/interacción que el cajón LPS (overlay, foco atrapado, Escape) pero **componente independiente** (no reutiliza `lps_drawer.js`).
- **Interfaz:** conserva `applyFilters()`, `resetFilters()`, `toggleProjectDropdown()` y los ids de campos existentes para no romper `bi-spa.js`. El contador "(N)" refleja filtros activos.
- **Depende de:** los mismos handlers JS de `bi-spa.js`; un nuevo pequeño módulo/patrón de cajón (o reutilizar el mecanismo de `[data-aia-component]` del DS si aplica) + estilos DS.
- **Se retira:** el toggle "móvil" de filtros del `_filters.php` (el cajón lo sustituye en desktop).

### C4 — Tema dark (chrome + dashboards)
- **Chrome (sidebar/context-bar/tabs/cajón):** primitivas `--ds-*`, dark.
- **Dashboards Chart.js:** re-tematizado dark **incluido**:
  - Defaults globales de Chart.js: color de texto, grid, tooltip → tokens dark del DS.
  - **Paleta de series categórica** desde el DS (seguir la skill `dataviz` para una paleta accesible y consistente en dark).
  - Cards/paneles/KPIs (`bi-control-tower.css` + variantes por hoja) → superficies/texto/bordes dark del DS; retirar hardcodes light.
  - Estados vacíos, filtros activos, footer → dark.
- **Depende de:** `docs/design-system/`, `DESIGN.md`, la skill `impeccable` (implementación visual) y `dataviz` (paleta de charts).

## Flujo de datos
Sin cambios: `/api/bi/*` y `bi-spa.js` siguen hidratando las hojas vía `switchView`. `$initialData` se inyecta igual. El cambio es de presentación (chrome + tema).

## Verificación
- Agregar las 8 rutas `/bi/*` al harness `tests/browser/shell-sidebar-rollout.mjs` (`ALL_ROUTES` + `MIGRATED`, `active: 'control-tower'`) con los 5 checks (default colapsado, toggle, cero-scroll, sin overflow horizontal, ítem activo) a 1180×820 dark.
- Checks adicionales específicos: `switchView` cambia de hoja (una hoja no-default queda activa), el cajón de filtros abre/cierra y "Aplicar/Limpiar" siguen operando (interceptando `/api/bi/*` que muten — auditar cuáles escriben), y el dashboard renderiza en dark sin overflow en ambos estados.
- Registrar las 8 rutas en `docs/design-system/manifests/foundation-shell.json`.
- QA visual dark 1180×820: chrome + al menos 2 hojas (Resumen + una con charts) legibles en dark.

## Fuera de alcance / riesgos
- **No** se migra el stack de Tailwind CDN a primitivas DS del interior del dashboard (se conserva Tailwind mapeado a tokens); solo el chrome usa primitivas DS y el dark aplica a ambos.
- **Riesgo:** el re-tematizado dark de N hojas con charts es la parte más grande; puede necesitar iteración visual con `impeccable`. Si crece demasiado, se puede fasear por hoja (Resumen primero) — pero el spec pide el dark completo.
- **Riesgo:** endpoints `/api/bi/*` — auditar si alguno muta (como pasó con `/api/cic/list` en el rollout) para interceptarlo en el harness.
- **Linen:** se retira como default de Control Tower; queda dark-only (coherente con el resto del shell y con la prohibición de linen en AGENTS.md).

## Archivos (previsible)
- `views/bi/_layout.php` (reescritura del chrome), `views/bi/_nav.php` (→ tabs), `views/bi/_filters.php` (→ cajón), vistas de hoja `views/bi/*.php` (dark).
- `src/Controllers/Bi/BiViewController.php` (variables del shell).
- `public/css/bi-control-tower.css` (dark), posible nuevo CSS/JS del cajón de filtros y de la paleta de charts.
- `public/js/modules/bi-spa.js` (solo si el cambio de contenedor exige ajustes de selectores; evitar tocar su lógica).
- `tests/browser/shell-sidebar-rollout.mjs` (+8 rutas), `docs/design-system/manifests/foundation-shell.json` (+8 rutas).
