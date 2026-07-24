# Control Tower en el shell dark — Plan de implementación

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Integrar Control Tower (SPA BI) al shell sidebar canónico en dark, reubicando su nav de hojas y sus filtros, sin tocar la lógica de la SPA (`bi-spa.js`/`switchView`/`/api/bi/*`).

**Architecture:** Se reescribe el chrome de `views/bi/_layout.php` para consumir el shell (`shell_sidebar.php` + context-bar), se retira la `bi-sidebar` propia, la nav de hojas pasa a una tira de tabs horizontal y los filtros a un cajón derecho propio; todo forzado a tema dark, incluido el re-tematizado de los dashboards Chart.js.

**Tech Stack:** PHP 8.3 (Docker `app`), el shell canónico (`views/partials/shell_sidebar.php`, `public/js/modules/aia_ui/sidebar_navigation.js`), Tailwind CDN (config mapea a tokens DS) + Chart.js 4.4.1 (interior del dashboard, sin reescribir), harness Playwright `tests/browser/shell-sidebar-rollout.mjs`.

## Global Constraints

- Viewport de validación: **1180×820, dark**. Desktop only. Prohibido mobile/tablet/linen (AGENTS.md).
- No mutar la BD compartida en tests: interceptar en el harness cualquier endpoint `/api/bi/*` que escriba (auditar; precedente: `/api/cic/list` mutaba pese al nombre).
- No tocar la lógica de datos de la SPA: `public/js/modules/bi-spa.js` (`switchView`, `applyFilters`, `resetFilters`, `toggleProjectDropdown`), endpoints `/api/bi/*`. Conservar los `id` de campos de filtro y los `key` de hojas.
- Directo en main; commits atómicos con `git add` explícito; nunca incluir el trabajo ajeno del árbol (`DESIGN.md`, `.impeccable/`, PDC).
- `$shellWeeks`: misma fuente/forma segura que el resto (query `semanas_activas` por `project_id`, guard regex de `dbName`, prepared, try/catch → `[]`).
- Default de la sidebar: colapsado. Tema: dark forzado (retirar el default linen de Control Tower).
- Cambios de tokens/componentes primero; sin hex/estilos inline nuevos en superficies del DS (usar `--ds-*`). Seguir `DESIGN.md` y las skills `impeccable` (visual) y `dataviz` (paleta de charts).

---

### Task 1: Chrome del shell + tabs de hojas (retirar la bi-sidebar), en dark

**Files:**
- Modify: `views/bi/_layout.php` (reescribir el chrome)
- Modify: `views/bi/_nav.php` (lista vertical + `<select>` → tira de tabs horizontal)
- Modify: `src/Controllers/Bi/BiViewController.php` (proveer variables del shell)
- Modify: `tests/browser/shell-sidebar-rollout.mjs` (agregar `/bi/control-tower` a `MIGRATED`)
- Reference: `views/programacion-intermedia/programacion_intermedia.view.php` (receta shell), commit `3a968dd` (supresión navbar), `views/partials/shell_sidebar.php`.

**Interfaces:**
- Consumes: `shell_sidebar.php` (partial, sin cambios); `sidebar_navigation.js`; `switchView(key)` de `bi-spa.js` (sin cambios).
- Produces: `_layout.php` con `<body class="aia-shell aia-shell--sidebar bi-control-tower-page ...">`, `data-aia-theme="dark"` forzado, `require shell_sidebar.php`, `$shellActive='control-tower'`; `_nav.php` como tira de tabs con `role="tablist"`/`aria-current`. Los filtros quedan TEMPORALMENTE en un bloque simple del contenido (Task 2 los mueve al cajón).

- [ ] **Step 1: Escribir el assert que falla (harness route)**

En `tests/browser/shell-sidebar-rollout.mjs`, agregar `'/bi/control-tower'` al `Set MIGRATED` (ya está en `ALL_ROUTES` con `active: 'control-tower'`).

- [ ] **Step 2: Correr el harness y verlo fallar**

Run: `node tests/browser/shell-sidebar-rollout.mjs`
Expected: FAIL en los checks de `/bi/control-tower` (aún no tiene `[data-shell-pattern="sidebar"]`).

- [ ] **Step 3: Controlador provee variables del shell**

En `BiViewController::renderView(...)` (o el punto común antes del `require _layout.php`), añadir `$shellActive='control-tower'`, `$shellModuleLabel` = título de la hoja actual (o "Control Tower — Informes"), y `$shellWeeks` con la query estándar (`semanas_activas` por `project_id`, guard `dbName`, try/catch → `[]`), copiando el patrón de `ProgramacionSemanalController::loadShellWeeks()`.

- [ ] **Step 4: Reescribir `_layout.php` (chrome del shell, dark, retirar bi-sidebar)**

- `<body class="aia-shell aia-shell--sidebar bi-control-tower-page antialiased">` (retirar `h-screen flex bg-aia-neutral-bg`; el shell aporta el layout).
- Forzar `document.documentElement.setAttribute('data-aia-theme','dark')` (retirar el default linen; conservar el resto del boot script si hace falta, pero fijo en dark).
- `require __DIR__ . '/../partials/shell_sidebar.php';` al inicio del `<body>`.
- `window.__AIA_SHELL_SIDEBAR__ = true;` y `DesignSystemHeadComponent::renderScript('/js/modules/aia_ui/sidebar_navigation.js')`.
- Retirar el `<aside class="bi-sidebar">`. El contenido (header de vista, `#views-container`, footer) queda como área de contenido del shell.
- Insertar `require _nav.php` (tabs) bajo la context-bar del shell, sobre `#views-container`. `require _filters.php` temporalmente en un bloque simple sobre el contenido (Task 2 lo mueve al cajón).

- [ ] **Step 5: Convertir `_nav.php` en tira de tabs horizontal**

Reemplazar la lista vertical `.bi-sheet-nav-list` y el `<select>` móvil por una tira horizontal de tabs (`role="tablist"`, cada tab `role="tab"` con `onclick="switchView('<key>')"` y `aria-current="page"` en la activa según `$reportKey`). Conservar los 8 `key` exactos: `torre-control, programa-general, curva-s, intermedia, semanal, pdc, cic, cip`. Estilos con tokens `--ds-*` (dark); la tira scrollea horizontalmente dentro de su contenedor si no caben (nunca overflow del documento).

- [ ] **Step 6: Auditar e interceptar mutaciones de `/api/bi/*` en el harness**

Con el navegador integrado o `page.on('request')`, cargar `/bi/control-tower` y listar los POST que dispara. Para cada endpoint que ESCRIBA (verificar el controlador en `src/Controllers/Api/`), añadir `page.route('**/<endpoint>*', r => r.fulfill({...}))` en el harness devolviendo una respuesta inocua que no rompa el render. Documentar cuáles.

- [ ] **Step 7: Correr el harness y verlo pasar**

Run: `node tests/browser/shell-sidebar-rollout.mjs`
Expected: PASS — `/bi/control-tower` en los 5 checks (default colapsado, toggle, cero-scroll, sin overflow horizontal, ítem activo `control-tower`), resto sin regresión, exit 0.

- [ ] **Step 8: QA visual + `php -l` + commit**

`docker compose exec -T app php -l views/bi/_layout.php` y el controlador. Screenshot 1180×820 dark colapsado/expandido: una sola sidebar, tabs operativas (`switchView` cambia de hoja), sin overflow.
```bash
git add views/bi/_layout.php views/bi/_nav.php src/Controllers/Bi/BiViewController.php tests/browser/shell-sidebar-rollout.mjs
git commit -m "feat(control-tower): consume el shell dark + tabs de hojas (retira bi-sidebar)

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

### Task 2: Filtros en cajón derecho (componente propio)

**Files:**
- Modify: `views/bi/_filters.php` (envolver en un cajón derecho)
- Create: `public/css/bi-filter-drawer.css` (estilos del cajón, tokens DS) — o sección en `bi-control-tower.css`
- Create: `public/js/modules/bi_filter_drawer.js` (abrir/cerrar, foco, Escape, contador) — sin jQuery
- Modify: `views/bi/_layout.php` (mover el `require _filters.php` al cajón; cargar el JS/CSS)

**Interfaces:**
- Consumes: `applyFilters()`, `resetFilters()`, `toggleProjectDropdown()` de `bi-spa.js` (sin cambios); los `id` de campos existentes (`filter-semana`, `filter-desde`, …, `btn-project-dropdown`).
- Produces: un trigger "Filtros (N)" en el chrome del contenido y un cajón derecho `[data-bi-filter-drawer]` que contiene el form `#filters-form`. El contador N lo actualiza el nuevo JS a partir de los filtros activos.

- [ ] **Step 1: Assert del cajón en el harness**

Añadir al bloque de `/bi/control-tower` un check: el trigger de filtros existe y, tras click, el cajón `[data-bi-filter-drawer]` queda visible (aria-expanded/estado), y tras Escape se cierra. (Extender el harness con estos asserts específicos de CT.)

- [ ] **Step 2: Correr y ver fallar**

Run: `node tests/browser/shell-sidebar-rollout.mjs`
Expected: FAIL (no existe el cajón).

- [ ] **Step 3: Cajón + JS**

Envolver `_filters.php` en `<div data-bi-filter-drawer hidden>…</div>` + un trigger `<button data-bi-filter-trigger>Filtros <strong id="bi-filter-count">0</strong></button>` en el header del contenido. Escribir `bi_filter_drawer.js` (vanilla): abrir/cerrar por el trigger, cerrar con Escape/overlay, atrapar foco, y recalcular el contador N (nº de campos con valor). Retirar el `#bi-mobile-filter-toggle`/`.bi-mobile-filter-panel` (desktop-only). Estilos con el lenguaje del cajón LPS (overlay, panel derecho) usando tokens `--ds-*` dark.

- [ ] **Step 4: Cablear en `_layout.php`**

Mover `require _filters.php` al cajón; `renderScript('/js/modules/bi_filter_drawer.js')` + `renderStylesheet` del CSS del cajón.

- [ ] **Step 5: Correr y ver pasar**

Run: `node tests/browser/shell-sidebar-rollout.mjs`
Expected: PASS — cajón abre/cierra, `applyFilters()`/`resetFilters()` siguen operando (interceptados), sin overflow.

- [ ] **Step 6: QA + commit**

Screenshot dark: contenido limpio, cajón derecho con los filtros, contador correcto.
```bash
git add views/bi/_filters.php views/bi/_layout.php public/js/modules/bi_filter_drawer.js public/css/bi-filter-drawer.css tests/browser/shell-sidebar-rollout.mjs
git commit -m "feat(control-tower): filtros en cajón derecho propio (patrón LPS)

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

### Task 3: Chart.js dark — defaults globales + paleta de series DS

**Files:**
- Create: `public/js/modules/bi_chart_theme.js` (defaults Chart.js + paleta de series desde tokens DS)
- Modify: `views/bi/_layout.php` (cargar `bi_chart_theme.js` antes de `bi-spa.js`)
- Reference: skill `dataviz` (paleta categórica accesible), `public/css/tokens.css` (tokens de color).

**Interfaces:**
- Consumes: `Chart` global (Chart.js 4.4.1) y las CSS custom properties `--ds-*`.
- Produces: `window.BiChartTheme` con la paleta de series y la aplicación de `Chart.defaults` (color de texto, `borderColor`/grid, tooltip). `bi-spa.js` sigue creando los charts; los defaults ya son dark.

- [ ] **Step 1: Definir la paleta y defaults (seguir `dataviz`)**

Invocar la skill `dataviz` para derivar una paleta categórica de series válida en dark (contraste, orden). Escribir `bi_chart_theme.js`: leer tokens del DS vía `getComputedStyle(document.documentElement).getPropertyValue('--ds-…')`, fijar `Chart.defaults.color`, `Chart.defaults.borderColor`, `Chart.defaults.plugins.tooltip.*`, y exponer la paleta para las series.

- [ ] **Step 2: Cargar antes de `bi-spa.js`**

En `_layout.php`, `renderScript('/js/modules/bi_chart_theme.js')` antes de `bi-spa.js`. Si `bi-spa.js` fija colores de series por dataset, adaptarlo mínimamente para consumir `window.BiChartTheme` (sin tocar su lógica de datos) — o aplicar la paleta vía `Chart.defaults.datasets`.

- [ ] **Step 3: Verificar en el navegador (2 hojas con charts)**

Cargar `/bi/control-tower` (Resumen) y una hoja con charts (p.ej. Curva S) en dark. Screenshot: ejes/labels/tooltips/series legibles en dark, contraste AA.

- [ ] **Step 4: Commit**

```bash
git add public/js/modules/bi_chart_theme.js views/bi/_layout.php
git commit -m "feat(control-tower): tema dark de Chart.js (defaults + paleta de series DS)

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

### Task 4: Dark polish del dashboard (CSS) + QA de las 8 hojas

**Files:**
- Modify: `public/css/bi-control-tower.css` (superficies/cards/KPIs/estados/footer → dark con tokens)
- Modify (si necesario): `views/bi/*.php` (vistas de hoja) para clases/estructura dark
- Reference: skill `impeccable`, `DESIGN.md`, `docs/design-system/`.

**Interfaces:**
- Consumes: tokens `--ds-*` (superficies, texto, bordes, estados).
- Produces: `bi-control-tower.css` dark-coherente; ningún hardcode light residual.

- [ ] **Step 1: Auditar hardcodes light**

`grep` en `bi-control-tower.css` y vistas de hoja por colores/fondos light hardcodeados; listar. Invocar `impeccable` (audit) sobre la superficie.

- [ ] **Step 2: Re-tematizar a dark con tokens**

Reemplazar superficies/cards/KPIs/bordes/estados vacíos/"filtros activos"/footer por tokens `--ds-*` dark. Sin hex nuevos; primitivas del DS. Iterar con `impeccable`.

- [ ] **Step 3: QA visual de las 8 hojas**

Recorrer las 8 hojas vía `switchView` a 1180×820 dark; screenshot de cada una; confirmar legibilidad (texto ≥4.5:1), sin overflow horizontal, sin restos light. (Si el volumen es alto, priorizar Resumen + las que tengan charts; documentar cuáles quedan pendientes.)

- [ ] **Step 4: `npm run check:frontend` (biome CSS) + commit**

Run: `npm run check:frontend`
Expected: sin deuda nueva (comparar contra baseline).
```bash
git add public/css/bi-control-tower.css views/bi/*.php
git commit -m "feat(control-tower): dashboards en dark coherente (tokens DS)

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

### Task 5: Registrar las 8 rutas `/bi/*` + gates finales

**Files:**
- Modify: `docs/design-system/manifests/foundation-shell.json` (`routes` += las 8 `/bi/*`)
- Modify: `tests/browser/shell-sidebar-rollout.mjs` (asegurar las 8 rutas `/bi/*` en `ALL_ROUTES`+`MIGRATED` con `active: 'control-tower'`)

**Interfaces:**
- Consumes: el modelo de `foundation-shell.json` (agregar rutas es consistente; el check `renderForModule` solo aplica a `consumerContract==='v1'`, que este manifiesto no es — verificado en el rollout).
- Produces: manifiesto con las 8 rutas; harness cubriendo las 8.

- [ ] **Step 1: Agregar las 8 rutas al harness**

`/bi/control-tower, /bi/programa-general, /bi/intermedia, /bi/semanal, /bi/pdc, /bi/contratistas, /bi/responsables, /bi/curva-s` en `ALL_ROUTES` (todas `active: 'control-tower'`) y `MIGRATED`.

- [ ] **Step 2: Correr el harness (8 rutas /bi/*)**

Run: `node tests/browser/shell-sidebar-rollout.mjs`
Expected: PASS en las 8 rutas /bi/* (comparten `_layout.php`), sin regresión, exit 0.

- [ ] **Step 3: Registrar en el manifiesto**

Agregar las 8 rutas a `foundation-shell.json → routes` (ordenadas).

- [ ] **Step 4: Gates del foundation-shell**

Run (todos deben quedar verdes):
- `node tests/browser/shell-sidebar-rollout.mjs`
- `node --test tests/design-system/shell-navigation.test.mjs`
- `docker compose exec -T app php tests/test_shell_sidebar_partial.php`
- `node tests/browser/shell-week-admin.mjs`
- `node tests/test_foundation_shell_contract.mjs`
- `node scripts/design-system-router.mjs docs/design-system/manifests/foundation-shell.json`

- [ ] **Step 5: Commit**

```bash
git add docs/design-system/manifests/foundation-shell.json tests/browser/shell-sidebar-rollout.mjs
git commit -m "chore(control-tower): declarar las 8 rutas /bi/* en foundation-shell.json

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

## Notas de ejecución
- Tras Task 5, hacer review final de rama (whole-branch) y, con aprobación, push a origin/main (como en el rollout).
- Si el dark de las 8 hojas (Task 4) resulta demasiado grande, fasear: entregar Resumen + hojas con charts primero, y documentar en el ledger las hojas pendientes (no marcar el goal completo hasta cerrarlas).
