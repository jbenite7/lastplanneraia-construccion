---
capa: fuente
tipo: plan
estado: vigente
fecha: 2026-07-22
areas: [proceso]
fuente: goals/segmentacion-entrypoint-css/plan.md
resumen: Partir public/css/aia-design-system.css en un core sin vendors de grilla más 5 adjuntos por vendor, servidos vía renderForModule(moduleId) según el manifiesto…
---

# Segmentación del entrypoint CSS — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Partir `public/css/aia-design-system.css` en un core sin vendors de grilla más 5 adjuntos por vendor, servidos vía `renderForModule(moduleId)` según el manifiesto de cada superficie, migrando project-selector y auth con dry-run + evidencia, sin tocar el agregador ni Programa General.

**Architecture:** El agregador queda intacto (superficies no migradas). Archivos nuevos bajo `public/css/design-system/entrypoints/` replican sus imports partidos en core + adjuntos; `DesignSystemHeadComponent::renderForModule()` lee `docs/design-system/manifests/{id}.json` (caché por request, fallback fail-safe a `render()`), y `DesignSystemAssetController` sirve los entrypoints nuevos con la misma reescritura de `?v=`. Un gate nuevo (`scripts/design-system-entrypoint-partition.mjs`) verifica partición exacta y coherencia vista↔manifiesto↔PHP, encadenado a `test:design-system:static`.

**Tech Stack:** CSS `@layer`/`@import`, PHP 8.2 (sin framework), Node `node:test`, Playwright (+`@axe-core/playwright`), Biome, PHPStan.

## Global Constraints

- `public/css/aia-design-system.css` no se modifica: `git diff` sobre ese archivo debe quedar vacío al cierre del goal.
- Programa General no se toca: ni `views/programa-general*/**`, ni `public/css/programa-general.css`, ni sus adapters, ni sus goldens. Si un golden de PG cambia, el goal está mal.
- Alcance visual: solo desktop dark `1180x820` (canónico) y `1440x900`. Nada de mobile/tablet/linen.
- Prohibido en superficies migradas: hex sueltos, estilos inline, bloques `<style>` nuevos, CDN nuevas, `!important` fuera de `public/css/design-system/adapters/`.
- Gates que deben quedar verdes: `npm run check:design-system:biome`, `npm run test:design-system:static`, `npm run test:design-system:phpstan`. Único rojo preexistente tolerado: `laboratory-hardening` (doc-drift); no puede crecer.
- Evidencia, manifiesto y tests de cada superficie migrada van en el mismo commit que su migración.
- Los CDN legacy de auth (AdminLTE, Font Awesome 5, SweetAlert2 v11) no se tocan.
- Commits frecuentes, uno por task como mínimo, mensajes en español con prefijo del goal.
- Autoridad del goal: `goals/segmentacion-entrypoint-css/goal.md` y `facts.md` (commit `0c08755`).

**Levantar el stack para tareas de navegador** (igual que CI, puerto 8081, usuario `test.A`/`aia2026`):

```bash
docker compose -f docker-compose.yml -f docker-compose.ci.yml up -d --build db app
```

---

### Task 1: Baseline de equivalencia y evidencia "before"

Captura el estado actual servido ANTES de cualquier cambio: hash normalizado del agregador runtime y evidencia visual/red de project-selector y auth. Sin este task no hay dry-run comparable.

**Files:**
- Create: `tests/browser/entrypoint-segmentation-dryrun.mjs`
- Create: `docs/design-system/evidence/entrypoint-segmentation/` (artefactos generados)
- Create: `goals/segmentacion-entrypoint-css/validation-log.md` (esqueleto)

**Interfaces:**
- Produces: spec Playwright parametrizado por `DRYRUN_SURFACE` (`project-selector` | `auth`) y `DRYRUN_PHASE` (`before` | `after`), que escribe en `docs/design-system/evidence/entrypoint-segmentation/<surface>/<phase>/`: `<route>-<viewport>.png`, `stylesheets.json` (hrefs de `link[rel=stylesheet]` + CSS requests), `console.json`, `axe.json`. Tasks 5–7 lo reutilizan con `DRYRUN_PHASE=after`.

- [ ] **Step 1: Escribir el spec de dry-run**

```js
// tests/browser/entrypoint-segmentation-dryrun.mjs
import { mkdir, writeFile } from 'node:fs/promises';
// fileURLToPath y no URL.pathname: la ruta del repo contiene un espacio
// ("Crucial X6") y pathname lo percent-encodea, rompiendo el path en disco.
import { fileURLToPath } from 'node:url';
import { test, expect } from '@playwright/test';
import AxeBuilder from '@axe-core/playwright';
import { PROJECTS } from './fixtures/projects.mjs';
import { login, logout } from './support/session.mjs';

const SURFACES = {
  'project-selector': { routes: ['/proyectos'], authenticated: true },
  auth: { routes: ['/login', '/password/forgot', '/password/reset'], authenticated: false },
};
const VIEWPORTS = [
  { width: 1180, height: 820 },
  { width: 1440, height: 900 },
];
const CI_ADMIN = { username: 'test.A', password: 'aia2026' };
const surfaceId = process.env.DRYRUN_SURFACE ?? 'project-selector';
const phase = process.env.DRYRUN_PHASE ?? 'before';
const surface = SURFACES[surfaceId];
const outDir = new URL(
  `../../docs/design-system/evidence/entrypoint-segmentation/${surfaceId}/${phase}/`,
  import.meta.url,
);
const slug = (route) => route.replaceAll('/', '-').replace(/^-/, '') || 'root';

test(`dry-run ${surfaceId} (${phase})`, async ({ page }) => {
  await mkdir(outDir, { recursive: true });
  const consoleEntries = [];
  page.on('console', (message) => {
    if (['error', 'warning'].includes(message.type())) {
      consoleEntries.push({ type: message.type(), text: message.text() });
    }
  });
  const cssRequests = new Set();
  page.on('request', (request) => {
    if (request.resourceType() === 'stylesheet' || request.url().includes('.css')) {
      cssRequests.add(new URL(request.url()).pathname);
    }
  });

  if (surface.authenticated) {
    const project = PROJECTS.find(({ key }) => key === 'construction');
    expect(project, 'construction project fixture required').toBeTruthy();
    await login(page, CI_ADMIN);
  }
  const report = {};
  try {
    for (const route of surface.routes) {
      report[route] = {};
      for (const viewport of VIEWPORTS) {
        await page.setViewportSize(viewport);
        const response = await page.goto(route, { waitUntil: 'networkidle' });
        expect(response?.status(), `${route} must respond`).toBeLessThan(400);
        const links = await page
          .locator('link[rel="stylesheet"]')
          .evaluateAll((nodes) => nodes.map((node) => new URL(node.href).pathname));
        report[route][`${viewport.width}x${viewport.height}`] = { links };
        await page.screenshot({
          path: fileURLToPath(new URL(`${slug(route)}-${viewport.width}x${viewport.height}.png`, outDir)),
          fullPage: false,
        });
      }
      const axe = await new AxeBuilder({ page }).analyze();
      report[route].axeViolations = axe.violations.map(({ id, impact, nodes }) => ({
        id,
        impact,
        nodes: nodes.length,
      }));
    }
  } finally {
    if (surface.authenticated) await logout(page).catch(() => {});
  }
  report.cssRequests = [...cssRequests].sort();
  await writeFile(new URL('stylesheets.json', outDir), `${JSON.stringify(report, null, 2)}\n`);
  await writeFile(new URL('console.json', outDir), `${JSON.stringify(consoleEntries, null, 2)}\n`);
});
```

Nota: `login(page, CI_ADMIN)` debe existir en `tests/browser/support/session.mjs`; verifica su firma real (`grep -n "export async function login" tests/browser/support/session.mjs`) y ajusta la llamada si difiere (p. ej. `login(page, { username, password })`).

- [ ] **Step 2: Levantar el stack y correr el before de ambas superficies**

```bash
docker compose -f docker-compose.yml -f docker-compose.ci.yml up -d --build db app
DRYRUN_SURFACE=project-selector DRYRUN_PHASE=before npx playwright test tests/browser/entrypoint-segmentation-dryrun.mjs --workers=1
DRYRUN_SURFACE=auth DRYRUN_PHASE=before npx playwright test tests/browser/entrypoint-segmentation-dryrun.mjs --workers=1
```

Expected: 2 runs PASS; aparecen `docs/design-system/evidence/entrypoint-segmentation/{project-selector,auth}/before/` con PNGs, `stylesheets.json` y `console.json`. En ambos `stylesheets.json` debe verse `/runtime/css/aia-design-system.css` en `links`.

- [ ] **Step 3: Capturar el hash normalizado del agregador servido**

```bash
curl -s "http://localhost:8081/runtime/css/aia-design-system.css" | sed -E 's/\?v=[0-9]+/?v=X/g' | shasum -a 256
```

Expected: una línea `<hash>  -`. Guárdala: es el hash "before" de equivalencia.

- [ ] **Step 4: Iniciar validation-log.md**

```markdown
# Validation log — segmentación entrypoint CSS

## Baseline (Task 1)

- Fecha: <fecha>
- Hash normalizado de /runtime/css/aia-design-system.css (before): `<hash>`
- Evidencia before: docs/design-system/evidence/entrypoint-segmentation/{project-selector,auth}/before/
- Axe violations before (project-selector /proyectos): <n>
- Axe violations before (auth /login, /password/forgot, /password/reset): <n>, <n>, <n>
```

- [ ] **Step 5: Commit**

```bash
git add tests/browser/entrypoint-segmentation-dryrun.mjs docs/design-system/evidence/entrypoint-segmentation goals/segmentacion-entrypoint-css/validation-log.md
git commit -m "test(segmentacion-entrypoint-css): dry-run harness y baseline before de project-selector y auth"
```

---

### Task 2: Gate de partición + archivos CSS core y adjuntos

TDD: primero el gate que define qué significa "partición exacta", luego los archivos CSS que lo satisfacen.

**Files:**
- Create: `scripts/design-system-entrypoint-partition.mjs`
- Create: `tests/design-system/entrypoint-partition.test.mjs`
- Create: `public/css/design-system/entrypoints/core.css`
- Create: `public/css/design-system/entrypoints/theme-overrides.css`
- Create: `public/css/design-system/entrypoints/attach-jquery-ui.css`
- Create: `public/css/design-system/entrypoints/attach-anychart.css`
- Create: `public/css/design-system/entrypoints/attach-select2.css`
- Create: `public/css/design-system/entrypoints/attach-sweetalert2.css`
- Create: `public/css/design-system/entrypoints/attach-handsontable.css`
- Modify: `package.json:8` (encadenar el gate a `test:design-system:static`)

**Interfaces:**
- Produces: `partitionFailures({ root })` → `string[]` (vacío = OK) exportada desde `scripts/design-system-entrypoint-partition.mjs`; constante exportada `ENTRYPOINT_FILES = { aggregator, core, themeOverrides, attachments: { 'jquery-ui', anychart, select2, sweetalert2, handsontable } }` con rutas relativas al repo. Task 4 extiende este mismo script con `coherenceFailures`.

- [ ] **Step 1: Escribir el test del gate (falla: los archivos no existen)**

```js
// tests/design-system/entrypoint-partition.test.mjs
import assert from 'node:assert/strict';
import { fileURLToPath } from 'node:url';
import test from 'node:test';
import { partitionFailures } from '../../scripts/design-system-entrypoint-partition.mjs';

const root = fileURLToPath(new URL('../..', import.meta.url));

test('la partición core + adjuntos reproduce exactamente el agregador', () => {
  assert.deepEqual(partitionFailures({ root }), []);
});

test('el gate detecta un import faltante en la partición', () => {
  const failures = partitionFailures({
    root,
    coreOverride: '@layer reset, vendor, theme, base, layout, components, utilities, module, legacy-overrides;\n',
  });
  assert.ok(failures.some((f) => f.includes('missing-from-partition')));
});

test('el gate detecta un import duplicado entre core y un adjunto', () => {
  const failures = partitionFailures({
    root,
    attachmentOverrides: {
      'jquery-ui': '@import url("/public/vendor/font-awesome/css/all.css") layer(vendor);\n@import url("/public/vendor/jquery-ui.css") layer(vendor);\n',
    },
  });
  assert.ok(failures.some((f) => f.includes('duplicated-in-partition')));
});

test('el gate exige identidad textual de los bloques theme/legacy-overrides', () => {
  const failures = partitionFailures({ root, themeOverridesOverride: '@layer theme {}\n' });
  assert.ok(failures.some((f) => f.includes('theme-overrides-drift')));
});
```

- [ ] **Step 2: Correr el test y verificar que falla**

```bash
node --test tests/design-system/entrypoint-partition.test.mjs
```

Expected: FAIL (`Cannot find module .../design-system-entrypoint-partition.mjs`).

- [ ] **Step 3: Escribir el gate**

```js
#!/usr/bin/env node
// scripts/design-system-entrypoint-partition.mjs
import { readFileSync } from 'node:fs';
import { join } from 'node:path';
import process from 'node:process';
import { pathToFileURL } from 'node:url';

export const ENTRYPOINT_FILES = {
  aggregator: 'public/css/aia-design-system.css',
  core: 'public/css/design-system/entrypoints/core.css',
  themeOverrides: 'public/css/design-system/entrypoints/theme-overrides.css',
  attachments: {
    'jquery-ui': 'public/css/design-system/entrypoints/attach-jquery-ui.css',
    anychart: 'public/css/design-system/entrypoints/attach-anychart.css',
    select2: 'public/css/design-system/entrypoints/attach-select2.css',
    sweetalert2: 'public/css/design-system/entrypoints/attach-sweetalert2.css',
    handsontable: 'public/css/design-system/entrypoints/attach-handsontable.css',
  },
};

// Import propio de la partición, ausente del agregador por diseño.
const THEME_OVERRIDES_IMPORT = '/css/design-system/entrypoints/theme-overrides.css';
const IMPORT_PATTERN = /@import url\("([^"]+)"\)(?: layer\(([a-z-]+)\))?;/g;

function parseImports(css) {
  return [...css.matchAll(IMPORT_PATTERN)].map(([, url, layer]) => ({
    url: url.replace(/\?v=[0-9.]+$/, ''),
    layer: layer ?? null,
  }));
}

function readOrFail(root, file, failures) {
  try {
    return readFileSync(join(root, file), 'utf8');
  } catch {
    failures.push(`missing-file: ${file}`);
    return '';
  }
}

export function partitionFailures({
  root,
  coreOverride = null,
  themeOverridesOverride = null,
  attachmentOverrides = {},
}) {
  const failures = [];
  const aggregator = readOrFail(root, ENTRYPOINT_FILES.aggregator, failures);
  const core = coreOverride ?? readOrFail(root, ENTRYPOINT_FILES.core, failures);
  const themeOverrides = themeOverridesOverride
    ?? readOrFail(root, ENTRYPOINT_FILES.themeOverrides, failures);
  const attachments = Object.fromEntries(
    Object.entries(ENTRYPOINT_FILES.attachments).map(([vendor, file]) => [
      vendor,
      attachmentOverrides[vendor] ?? readOrFail(root, file, failures),
    ]),
  );
  if (failures.length) return failures;

  const aggregatorImports = parseImports(aggregator);
  const partitionMembers = [
    ['core', parseImports(core).filter(({ url }) => url !== THEME_OVERRIDES_IMPORT)],
    ...Object.entries(attachments).map(([vendor, css]) => [vendor, parseImports(css)]),
  ];

  const seen = new Map();
  for (const [owner, imports] of partitionMembers) {
    for (const entry of imports) {
      if (seen.has(entry.url)) {
        failures.push(`duplicated-in-partition: ${entry.url} (${seen.get(entry.url)} y ${owner})`);
      }
      seen.set(entry.url, owner);
    }
  }

  const aggregatorUrls = new Set(aggregatorImports.map(({ url }) => url));
  for (const { url } of aggregatorImports) {
    if (!seen.has(url)) failures.push(`missing-from-partition: ${url}`);
  }
  for (const url of seen.keys()) {
    if (!aggregatorUrls.has(url)) failures.push(`extra-in-partition: ${url}`);
  }

  // Cada miembro conserva el orden relativo y la capa que sus imports tienen en el agregador.
  const aggregatorIndex = new Map(aggregatorImports.map(({ url }, index) => [url, index]));
  const aggregatorLayer = new Map(aggregatorImports.map(({ url, layer }) => [url, layer]));
  for (const [owner, imports] of partitionMembers) {
    let last = -1;
    for (const { url, layer } of imports) {
      const index = aggregatorIndex.get(url);
      if (index === undefined) continue;
      if (index < last) failures.push(`order-drift: ${url} en ${owner}`);
      last = index;
      if (aggregatorLayer.get(url) !== layer) {
        failures.push(`layer-drift: ${url} en ${owner} (agregador: ${aggregatorLayer.get(url)}, partición: ${layer})`);
      }
    }
  }

  // La declaración de capas de core debe ser la canónica del agregador.
  const layerDeclaration = aggregator.match(/^@layer [^;]+;/m)?.[0];
  if (!core.startsWith(layerDeclaration ?? '@layer')) {
    failures.push('layer-declaration-drift: core.css no abre con la declaración canónica de capas');
  }

  // Bloques inline del agregador == theme-overrides.css, textualmente.
  const inlineStart = aggregator.indexOf('@layer theme {');
  const inlineBlocks = inlineStart === -1 ? '' : aggregator.slice(inlineStart).trim();
  if (inlineBlocks !== themeOverrides.trim()) {
    failures.push('theme-overrides-drift: los bloques inline del agregador y theme-overrides.css difieren');
  }

  return failures;
}

if (import.meta.url === pathToFileURL(process.argv[1]).href) {
  const failures = partitionFailures({ root: process.cwd() });
  if (failures.length) {
    console.error('Design system entrypoint partition: FAIL');
    failures.forEach((failure) => console.error(`- ${failure}`));
    process.exit(1);
  }
  console.log('Design system entrypoint partition: PASS');
}
```

- [ ] **Step 4: Correr el test y verificar que sigue fallando (ahora por archivos CSS ausentes)**

```bash
node --test tests/design-system/entrypoint-partition.test.mjs
```

Expected: FAIL con `missing-file: public/css/design-system/entrypoints/core.css` (y los demás).

- [ ] **Step 5: Crear theme-overrides.css**

Copiar VERBATIM desde `public/css/aia-design-system.css` todo el texto desde la línea `@layer theme {` (línea 35) hasta el final del archivo (línea 149), sin editar nada. El archivo resultante contiene los dos bloques `@layer theme { … }` y `@layer legacy-overrides { … }` exactamente como están en el agregador.

- [ ] **Step 6: Crear core.css**

```css
/* public/css/design-system/entrypoints/core.css */
@layer reset, vendor, theme, base, layout, components, utilities, module, legacy-overrides;
@import url("/public/vendor/font-awesome/css/all.css") layer(vendor);
@import url("/public/vendor/bootstrap/bootstrap.min.css") layer(vendor);
@import url("/css/design-system/fonts.css?v=1.0.0");
@import url("/css/design-system/foundation.css?v=1.0.0");
@import url("/css/design-system/core.css?v=1.0.0");
@import url("/css/design-system/components/navigation.css?v=1.0.0");
@import url("/css/design-system/components/page-header.css?v=1.0.0");
@import url("/css/design-system/components/action-group.css?v=1.0.0");
@import url("/css/design-system/components/filter-form.css?v=1.0.0");
@import url("/css/design-system/components/states-feedback.css?v=1.0.0");
@import url("/css/design-system/components/data-display.css?v=1.0.0");
@import url("/css/design-system/components/dialog.css?v=1.0.0");
@import url("/css/design-system/components/bi-figure.css?v=1.0.0");
@import url("/css/design-system/components/primitives.css?v=1.0.0");
@import url("/css/design-system/adapters/legacy-bridge.css?v=1.0.0");
@import url("/css/design-system/adapters/semi-auto-review.css?v=1.0.0");
@import url("/css/design-system/adapters/lps-drawer.css?v=1.0.0");
@import url("/css/styles.css?v=1.0.0") layer(module);
@import url("/css/buttons.css?v=1.0.0") layer(components);
@import url("/css/access.css?v=1.0.0") layer(utilities);
@import url("/css/design-system/entrypoints/theme-overrides.css?v=1.0.0");
```

- [ ] **Step 7: Crear los cinco adjuntos**

```css
/* public/css/design-system/entrypoints/attach-jquery-ui.css */
@import url("/public/vendor/jquery-ui.css") layer(vendor);
```

```css
/* public/css/design-system/entrypoints/attach-anychart.css */
@import url("/public/vendor/anychart/anychart-ui.min.css") layer(vendor);
@import url("/public/vendor/anychart/anychart-font.min.css") layer(vendor);
```

```css
/* public/css/design-system/entrypoints/attach-select2.css */
@import url("/public/vendor/select2/select2.min.css") layer(vendor);
@import url("/css/design-system/adapters/select2.css?v=1.0.0");
```

```css
/* public/css/design-system/entrypoints/attach-sweetalert2.css */
@import url("/public/vendor/sweetalert2.min.css") layer(vendor);
@import url("/css/design-system/adapters/sweetalert2.css?v=1.0.0");
```

```css
/* public/css/design-system/entrypoints/attach-handsontable.css */
@import url("/public/vendor/handsontable/handsontable.full.min.css") layer(vendor);
@import url("/css/handsontable-module.css?v=1.0.0") layer(vendor);
@import url("/css/handsontable-header-global.css?v=1.0.0") layer(vendor);
@import url("/css/design-system/adapters/handsontable.css?v=1.0.0");
@import url("/css/design-system/adapters/programa-general-handsontable.css?v=1.0.0");
```

Nota: los comentarios `/* … */` de cabecera son opcionales; si el parser del gate los tolera (no matchean el patrón de import), pueden quedarse. El orden y las capas replican el agregador; el gate lo verifica.

- [ ] **Step 8: Correr el test y verificar que pasa**

```bash
node --test tests/design-system/entrypoint-partition.test.mjs
```

Expected: PASS (4 tests).

- [ ] **Step 9: Encadenar el gate al static y correr biome**

En `package.json` línea 8, añadir al final de la cadena de `test:design-system:static`:

```
&& node scripts/design-system-entrypoint-partition.mjs
```

```bash
npm run check:design-system:biome
npm run test:design-system:static
```

Expected: biome PASS (los archivos nuevos bajo `public/css/design-system` entran a su alcance); static PASS terminando con `Design system entrypoint partition: PASS`. Si `laboratory-hardening` está rojo por doc-drift preexistente, es el único rojo tolerado.

- [ ] **Step 10: Commit**

```bash
git add scripts/design-system-entrypoint-partition.mjs tests/design-system/entrypoint-partition.test.mjs public/css/design-system/entrypoints package.json
git commit -m "feat(segmentacion-entrypoint-css): partición core + adjuntos con gate de equivalencia exacta"
```

---

### Task 3: Runtime PHP — renderForModule, allowlist del controller y rutas

**Files:**
- Modify: `src/View/Components/DesignSystemHeadComponent.php`
- Modify: `src/Controllers/Core/DesignSystemAssetController.php`
- Modify: `public/index.php:46` (`$publicRoutes`) y `public/index.php:250-252` (rutas runtime)
- Test: `tests/test_design_system_head_component.php`

**Interfaces:**
- Consumes: `ENTRYPOINT_FILES` (rutas CSS creadas en Task 2, como URLs `/css/design-system/entrypoints/…`).
- Produces: `DesignSystemHeadComponent::renderForModule(string $moduleId): string` (emite `<script theme-bootstrap>` + `<link core>` + `<link attach-*>` declarados + `<link tokens>`; fallback exacto a `render()`); constantes `DesignSystemHeadComponent::CORE_VENDORS` y `DesignSystemHeadComponent::VENDOR_ATTACHMENTS` (públicas: el gate de Task 4 las parsea); `DesignSystemAssetController` con métodos `core()`, `attachJqueryUi()`, `attachAnychart()`, `attachSelect2()`, `attachSweetalert2()`, `attachHandsontable()`.

- [ ] **Step 1: Escribir el test PHP (falla: método inexistente)**

```php
<?php
// tests/test_design_system_head_component.php

require_once __DIR__ . '/../vendor/autoload.php';

use App\View\Components\DesignSystemHeadComponent;

$failures = [];
$check = static function (bool $condition, string $label) use (&$failures): void {
    if (!$condition) {
        $failures[] = $label;
    }
};

// 1. Módulo con manifiesto sin vendors de adjunto (project-selector): core + tokens, sin attach-*.
$selector = DesignSystemHeadComponent::renderForModule('project-selector');
$check(str_contains($selector, 'theme-bootstrap.js'), 'selector: falta theme-bootstrap.js');
$check(str_contains($selector, '/runtime/css/design-system/entrypoints/core.css?v='), 'selector: falta core runtime');
$check(str_contains($selector, '/css/tokens.css?v='), 'selector: falta tokens');
$check(!str_contains($selector, 'attach-'), 'selector: no debe emitir adjuntos');
$check(!str_contains($selector, 'aia-design-system.css'), 'selector: no debe emitir el agregador');
$check(
    strpos($selector, 'theme-bootstrap.js') < strpos($selector, 'core.css'),
    'selector: theme-bootstrap debe preceder al CSS',
);

// 2. Módulo inexistente: fallback exacto a render().
$check(
    DesignSystemHeadComponent::renderForModule('no-such-module') === DesignSystemHeadComponent::render(),
    'fallback: módulo inexistente debe emitir render()',
);

// 3. moduleId inválido (path traversal): fallback exacto a render().
$check(
    DesignSystemHeadComponent::renderForModule('../secrets') === DesignSystemHeadComponent::render(),
    'fallback: moduleId inválido debe emitir render()',
);

// 4. Todo adjunto declarado en PHP existe en disco y tiene URL runtime.
$root = dirname(__DIR__);
foreach (DesignSystemHeadComponent::VENDOR_ATTACHMENTS as $vendor => $url) {
    $check(is_file($root . '/public' . $url), "attachment $vendor: no existe $url");
}

if ($failures !== []) {
    fwrite(STDERR, "DesignSystemHeadComponent: FAIL\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}
echo "DesignSystemHeadComponent: PASS\n";
```

- [ ] **Step 2: Correr el test y verificar que falla**

```bash
docker compose -f docker-compose.yml -f docker-compose.ci.yml exec -T app php tests/test_design_system_head_component.php
```

Expected: FAIL con `Call to undefined method …::renderForModule()`.

- [ ] **Step 3: Implementar renderForModule en DesignSystemHeadComponent**

Añadir a `src/View/Components/DesignSystemHeadComponent.php` (y actualizar `RUNTIME_ENTRYPOINTS`/`assetVersion` como se indica):

```php
    /** Vendors ya cubiertos por el core; declararlos no añade adjuntos. */
    public const CORE_VENDORS = ['bootstrap', 'jquery', 'font-awesome', 'aia-fonts'];

    /** Adjuntos por vendor, en el orden canónico del agregador. */
    public const VENDOR_ATTACHMENTS = [
        'jquery-ui' => '/css/design-system/entrypoints/attach-jquery-ui.css',
        'anychart' => '/css/design-system/entrypoints/attach-anychart.css',
        'select2' => '/css/design-system/entrypoints/attach-select2.css',
        'sweetalert2' => '/css/design-system/entrypoints/attach-sweetalert2.css',
        'handsontable' => '/css/design-system/entrypoints/attach-handsontable.css',
    ];

    private const CORE_ENTRYPOINT = '/css/design-system/entrypoints/core.css';

    /**
     * Emite el head segmentado según el manifiesto del módulo. Ante cualquier
     * problema (manifiesto ausente, JSON inválido, vendor desconocido) degrada
     * a render() completo: siempre "cargar de más", nunca "cargar de menos".
     */
    public static function renderForModule(string $moduleId): string
    {
        $vendors = self::moduleVendors($moduleId);
        if ($vendors === null) {
            return self::render();
        }
        $assets = [self::CORE_ENTRYPOINT];
        foreach (self::VENDOR_ATTACHMENTS as $vendor => $attachment) {
            if (in_array($vendor, $vendors, true)) {
                $assets[] = $attachment;
            }
        }
        $assets[] = '/css/tokens.css';

        return implode("\n", array_merge(
            [self::renderScript('/js/modules/aia_ui/theme-bootstrap.js')],
            array_map([self::class, 'renderStylesheet'], $assets),
        ));
    }

    /** @return list<string>|null null = fallback a render() */
    private static function moduleVendors(string $moduleId): ?array
    {
        /** @var array<string, list<string>|null> $cache */
        static $cache = [];
        if (array_key_exists($moduleId, $cache)) {
            return $cache[$moduleId];
        }
        if (preg_match('/^[a-z0-9-]+$/', $moduleId) !== 1) {
            error_log("design-system: moduleId inválido '$moduleId', fallback al agregador");

            return $cache[$moduleId] = null;
        }
        $file = dirname(__DIR__, 3) . '/docs/design-system/manifests/' . $moduleId . '.json';
        if (!is_file($file)) {
            error_log("design-system: manifiesto ausente para '$moduleId', fallback al agregador");

            return $cache[$moduleId] = null;
        }
        $manifest = json_decode((string) file_get_contents($file), true);
        $vendors = is_array($manifest) ? ($manifest['vendors'] ?? null) : null;
        if (!is_array($vendors)) {
            error_log("design-system: manifiesto ilegible para '$moduleId', fallback al agregador");

            return $cache[$moduleId] = null;
        }
        foreach ($vendors as $vendor) {
            if (!is_string($vendor)
                || (!in_array($vendor, self::CORE_VENDORS, true)
                    && !isset(self::VENDOR_ATTACHMENTS[$vendor]))
            ) {
                error_log("design-system: vendor desconocido '" . var_export($vendor, true) . "' en '$moduleId', fallback al agregador");

                return $cache[$moduleId] = null;
            }
        }

        return $cache[$moduleId] = $vendors;
    }
```

Reemplazar la constante `RUNTIME_ENTRYPOINTS` para cubrir los entrypoints nuevos:

```php
    private const RUNTIME_ENTRYPOINTS = [
        '/css/aia-design-system.css' => '/runtime/css/aia-design-system.css',
        '/css/design-system/lab-entrypoint.css' => '/runtime/css/design-system/lab-entrypoint.css',
        '/css/design-system/entrypoints/core.css' => '/runtime/css/design-system/entrypoints/core.css',
        '/css/design-system/entrypoints/attach-jquery-ui.css' => '/runtime/css/design-system/entrypoints/attach-jquery-ui.css',
        '/css/design-system/entrypoints/attach-anychart.css' => '/runtime/css/design-system/entrypoints/attach-anychart.css',
        '/css/design-system/entrypoints/attach-select2.css' => '/runtime/css/design-system/entrypoints/attach-select2.css',
        '/css/design-system/entrypoints/attach-sweetalert2.css' => '/runtime/css/design-system/entrypoints/attach-sweetalert2.css',
        '/css/design-system/entrypoints/attach-handsontable.css' => '/runtime/css/design-system/entrypoints/attach-handsontable.css',
    ];
```

Y en `assetVersion()`, reemplazar el `in_array($url, [...], true)` por la condición equivalente generalizada, escaneando siempre `public/css` completo (los entrypoints viven en un subdirectorio, pero sus imports cruzan a `public/css/**`):

```php
    private static function assetVersion(string $file, string $url): string
    {
        $version = is_file($file) ? (int) filemtime($file) : 0;
        if (!isset(self::RUNTIME_ENTRYPOINTS[$url])) {
            return (string) $version;
        }
        $root = dirname(__DIR__, 3) . '/public/css';
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root));
        foreach ($files as $dependency) {
            if ($dependency->isFile() && $dependency->getExtension() === 'css') {
                $version = max($version, $dependency->getMTime());
            }
        }
        return (string) $version;
    }
```

(Para el agregador y el laboratorio esto escanea `public/css` igual que hoy — `dirname` del agregador ya era `public/css`; para el laboratorio amplía el alcance del escaneo, lo cual solo puede subir la versión: cache-busting más agresivo, nunca stale.)

- [ ] **Step 4: Generalizar DesignSystemAssetController**

Reemplazar `ENTRYPOINTS` y añadir los métodos:

```php
    private const ENTRYPOINTS = [
        'main' => '/css/aia-design-system.css',
        'laboratory' => '/css/design-system/lab-entrypoint.css',
        'core' => '/css/design-system/entrypoints/core.css',
        'attach-jquery-ui' => '/css/design-system/entrypoints/attach-jquery-ui.css',
        'attach-anychart' => '/css/design-system/entrypoints/attach-anychart.css',
        'attach-select2' => '/css/design-system/entrypoints/attach-select2.css',
        'attach-sweetalert2' => '/css/design-system/entrypoints/attach-sweetalert2.css',
        'attach-handsontable' => '/css/design-system/entrypoints/attach-handsontable.css',
    ];

    public function core(): void
    {
        $this->serve(self::ENTRYPOINTS['core']);
    }

    public function attachJqueryUi(): void
    {
        $this->serve(self::ENTRYPOINTS['attach-jquery-ui']);
    }

    public function attachAnychart(): void
    {
        $this->serve(self::ENTRYPOINTS['attach-anychart']);
    }

    public function attachSelect2(): void
    {
        $this->serve(self::ENTRYPOINTS['attach-select2']);
    }

    public function attachSweetalert2(): void
    {
        $this->serve(self::ENTRYPOINTS['attach-sweetalert2']);
    }

    public function attachHandsontable(): void
    {
        $this->serve(self::ENTRYPOINTS['attach-handsontable']);
    }
```

`serve()` no cambia (su regex ya reescribe los `@import url("/css/…?v=…")` internos; los imports `/public/vendor/…` no se reescriben, igual que hoy en el agregador).

- [ ] **Step 5: Registrar rutas en public/index.php**

Tras la línea 252 (`…lab-entrypoint.css'…`), añadir:

```php
$router->get('/runtime/css/design-system/entrypoints/core.css', [\App\Controllers\Core\DesignSystemAssetController::class, 'core']);
$router->get('/runtime/css/design-system/entrypoints/attach-jquery-ui.css', [\App\Controllers\Core\DesignSystemAssetController::class, 'attachJqueryUi']);
$router->get('/runtime/css/design-system/entrypoints/attach-anychart.css', [\App\Controllers\Core\DesignSystemAssetController::class, 'attachAnychart']);
$router->get('/runtime/css/design-system/entrypoints/attach-select2.css', [\App\Controllers\Core\DesignSystemAssetController::class, 'attachSelect2']);
$router->get('/runtime/css/design-system/entrypoints/attach-sweetalert2.css', [\App\Controllers\Core\DesignSystemAssetController::class, 'attachSweetalert2']);
$router->get('/runtime/css/design-system/entrypoints/attach-handsontable.css', [\App\Controllers\Core\DesignSystemAssetController::class, 'attachHandsontable']);
```

Y en `$publicRoutes` (línea 46), añadir las seis URLs runtime nuevas junto a las dos existentes (login las necesita pre-autenticación; son CSS público sin datos):

```php
'/runtime/css/design-system/entrypoints/core.css',
'/runtime/css/design-system/entrypoints/attach-jquery-ui.css',
'/runtime/css/design-system/entrypoints/attach-anychart.css',
'/runtime/css/design-system/entrypoints/attach-select2.css',
'/runtime/css/design-system/entrypoints/attach-sweetalert2.css',
'/runtime/css/design-system/entrypoints/attach-handsontable.css',
```

- [ ] **Step 6: Correr el test PHP y verificar que pasa**

```bash
docker compose -f docker-compose.yml -f docker-compose.ci.yml exec -T app php tests/test_design_system_head_component.php
```

Expected: `DesignSystemHeadComponent: PASS`.

- [ ] **Step 7: Verificar el serving runtime y PHPStan**

```bash
curl -s -o /dev/null -w "%{http_code}\n" "http://localhost:8081/runtime/css/design-system/entrypoints/core.css"
curl -s "http://localhost:8081/runtime/css/design-system/entrypoints/core.css" | head -3
npm run test:design-system:phpstan
```

Expected: `200`; las primeras líneas muestran `@layer …` y `@import url("/public/vendor/font-awesome…`; los imports `/css/…` llevan `?v=<mtime numérico>` (no `1.0.0`). PHPStan sin hallazgos nuevos.

- [ ] **Step 8: Commit**

```bash
git add src/View/Components/DesignSystemHeadComponent.php src/Controllers/Core/DesignSystemAssetController.php public/index.php tests/test_design_system_head_component.php
git commit -m "feat(segmentacion-entrypoint-css): renderForModule con fallback fail-safe y serving runtime de core y adjuntos"
```

---

### Task 4: Gate de coherencia + contrato de consumidor con renderForModule

El gate de partición aprende a validar la coherencia vista↔manifiesto↔PHP, y el contrato de consumidor v1 acepta `renderForModule` como forma canónica de consumo.

**Files:**
- Modify: `scripts/design-system-entrypoint-partition.mjs` (añadir `coherenceFailures`)
- Modify: `tests/design-system/entrypoint-partition.test.mjs`
- Modify: `scripts/design-system-consumer-contract.mjs:27-33`
- Modify: `tests/design-system/project-selector-contract.test.mjs` (caso nuevo)

**Interfaces:**
- Consumes: `DesignSystemHeadComponent::CORE_VENDORS` y `::VENDOR_ATTACHMENTS` (constantes PHP públicas, Task 3); `ENTRYPOINT_FILES` (Task 2).
- Produces: `coherenceFailures({ root })` → `string[]`; el CLI del script corre partición + coherencia. `consumerContractFailures` acepta vistas que consumen vía `renderForModule('<moduleId>')` en lugar de los literales `/css/tokens.css` + `/css/aia-design-system.css`.

- [ ] **Step 1: Escribir los tests de coherencia (fallan: función inexistente)**

Añadir a `tests/design-system/entrypoint-partition.test.mjs`:

```js
import { coherenceFailures } from '../../scripts/design-system-entrypoint-partition.mjs';

test('coherencia: el árbol real de vistas y manifiestos es coherente', () => {
  assert.deepEqual(coherenceFailures({ root }), []);
});

test('coherencia: una vista con renderForModule sin manifiesto falla', () => {
  const failures = coherenceFailures({
    root,
    viewsOverride: [{ file: 'views/fake.view.php', content: "<?= DesignSystemHeadComponent::renderForModule('missing-module') ?>" }],
  });
  assert.ok(failures.some((f) => f.includes('missing-manifest: missing-module')));
});

test('coherencia: un vendor no resoluble contra PHP falla', () => {
  const failures = coherenceFailures({
    root,
    manifestsOverride: [{ moduleId: 'fake', vendors: ['definitely-not-a-vendor'] }],
  });
  assert.ok(failures.some((f) => f.includes('unknown-vendor: definitely-not-a-vendor')));
});
```

- [ ] **Step 2: Correr y verificar que fallan**

```bash
node --test tests/design-system/entrypoint-partition.test.mjs
```

Expected: FAIL (`coherenceFailures is not a function` o export ausente).

- [ ] **Step 3: Implementar coherenceFailures**

Añadir a `scripts/design-system-entrypoint-partition.mjs` (fusionar el import con el `node:fs` existente):

```js
import { readdirSync, readFileSync, statSync } from 'node:fs';

const HEAD_COMPONENT = 'src/View/Components/DesignSystemHeadComponent.php';

// PHP es la fuente de verdad de vendors core y adjuntos; el gate los parsea
// para que no exista una segunda copia que mantener sincronizada a mano.
export function phpVendorRegistry(root) {
  const php = readFileSync(join(root, HEAD_COMPONENT), 'utf8');
  const coreBlock = php.match(/const CORE_VENDORS = \[([^\]]*)\]/s)?.[1] ?? '';
  const attachmentsBlock = php.match(/const VENDOR_ATTACHMENTS = \[([^\]]*)\]/s)?.[1] ?? '';
  const coreVendors = [...coreBlock.matchAll(/'([a-z0-9-]+)'/g)].map(([, v]) => v);
  const attachments = [...attachmentsBlock.matchAll(/'([a-z0-9-]+)' => '([^']+)'/g)]
    .map(([, vendor, url]) => ({ vendor, url }));
  return { coreVendors, attachments };
}

function* phpViews(root) {
  const stack = [join(root, 'views')];
  while (stack.length) {
    const dir = stack.pop();
    for (const name of readdirSync(dir)) {
      const path = join(dir, name);
      if (statSync(path).isDirectory()) stack.push(path);
      else if (name.endsWith('.php')) {
        yield { file: path.slice(root.length).replace(/^\//, ''), content: readFileSync(path, 'utf8') };
      }
    }
  }
}

export function coherenceFailures({ root, viewsOverride = null, manifestsOverride = null }) {
  const failures = [];
  const { coreVendors, attachments } = phpVendorRegistry(root);
  if (coreVendors.length === 0 || attachments.length === 0) {
    return ['php-registry-unreadable: no se pudieron extraer CORE_VENDORS/VENDOR_ATTACHMENTS'];
  }
  const known = new Set([...coreVendors, ...attachments.map(({ vendor }) => vendor)]);

  // 1. Todo adjunto PHP apunta a un archivo de la partición y existe.
  const partitionUrls = new Set(
    Object.values(ENTRYPOINT_FILES.attachments).map((file) => file.replace('public', '')),
  );
  for (const { vendor, url } of attachments) {
    if (!partitionUrls.has(url)) failures.push(`attachment-url-drift: ${vendor} → ${url}`);
  }

  // 2. Toda vista que llama renderForModule('X') tiene manifiesto X válido.
  const views = viewsOverride ?? [...phpViews(root)];
  const usedModules = new Set();
  for (const { file, content } of views) {
    for (const match of content.matchAll(/renderForModule\('([^']+)'\)/g)) {
      const moduleId = match[1];
      usedModules.add(moduleId);
      const manifestPath = join(root, 'docs/design-system/manifests', `${moduleId}.json`);
      try {
        JSON.parse(readFileSync(manifestPath, 'utf8'));
      } catch {
        failures.push(`missing-manifest: ${moduleId} (usado por ${file})`);
      }
    }
  }

  // 3. Todo vendors[] de los manifiestos usados (o inyectados) resuelve contra PHP.
  const manifests = manifestsOverride ?? [...usedModules].flatMap((moduleId) => {
    try {
      return [JSON.parse(readFileSync(join(root, 'docs/design-system/manifests', `${moduleId}.json`), 'utf8'))];
    } catch {
      return [];
    }
  });
  for (const manifest of manifests) {
    for (const vendor of manifest.vendors ?? []) {
      if (!known.has(vendor)) {
        failures.push(`unknown-vendor: ${vendor} (manifiesto ${manifest.moduleId})`);
      }
    }
  }

  return failures;
}
```

Y en el bloque CLI final, correr ambas familias:

```js
if (import.meta.url === pathToFileURL(process.argv[1]).href) {
  const root = process.cwd();
  const failures = [...partitionFailures({ root }), ...coherenceFailures({ root })];
  if (failures.length) {
    console.error('Design system entrypoint partition: FAIL');
    failures.forEach((failure) => console.error(`- ${failure}`));
    process.exit(1);
  }
  console.log('Design system entrypoint partition: PASS');
}
```

- [ ] **Step 4: Correr y verificar que pasan**

```bash
node --test tests/design-system/entrypoint-partition.test.mjs
```

Expected: PASS (7 tests). Nota: `coherenceFailures({ root })` real pasa hoy porque aún ninguna vista llama `renderForModule`.

- [ ] **Step 5: Contrato de consumidor — test del caso renderForModule (falla)**

Añadir a `tests/design-system/project-selector-contract.test.mjs`:

```js
test('consumer contract accepts renderForModule as canonical consumption', () => {
  const view = "<?= \\App\\View\\Components\\DesignSystemHeadComponent::renderForModule('project-selector') ?>"
    + '<div class="aia-shell aia-card aia-input aia-btn aia-chip aia-empty aia-alert"></div>';
  const css = '.ok { color: var(--ds-active-text-primary); }';
  const failures = consumerContractFailures({ root, manifest, viewOverride: view, cssOverride: css });
  assert.ok(!failures.some((failure) => failure.includes('canonical asset missing')));
});
```

```bash
node --test tests/design-system/project-selector-contract.test.mjs
```

Expected: FAIL (el contrato exige los literales y reporta `canonical asset missing`).

- [ ] **Step 6: Aceptar renderForModule en el contrato**

En `scripts/design-system-consumer-contract.mjs`, reemplazar el bloque `required` (líneas 27-33) por:

```js
  const usesRenderForModule = view.includes(`renderForModule('${manifest.moduleId}')`);
  if (!usesRenderForModule) {
    const required = [
      '/css/tokens.css',
      '/css/aia-design-system.css',
    ];
    for (const asset of required) {
      if (!view.includes(asset)) failures.push(`${manifest.moduleId}: canonical asset missing ${asset}`);
    }
  }
```

- [ ] **Step 7: Correr los tests del contrato y el static completo**

```bash
node --test tests/design-system/project-selector-contract.test.mjs
npm run test:design-system:static
```

Expected: PASS ambos (mismo único rojo tolerado si aplica).

- [ ] **Step 8: Commit**

```bash
git add scripts/design-system-entrypoint-partition.mjs tests/design-system/entrypoint-partition.test.mjs scripts/design-system-consumer-contract.mjs tests/design-system/project-selector-contract.test.mjs
git commit -m "feat(segmentacion-entrypoint-css): gate de coherencia vista-manifiesto-PHP y contrato con renderForModule"
```

---

### Task 5: Migrar project-selector + smoke extendido + dry-run after

**Files:**
- Modify: `views/core/project_selector.view.php:10-13`
- Modify: `docs/design-system/manifests/project-selector.json` (`sources`, `tests`, `evidence`)
- Modify: `tests/browser/design-system-consumer-smoke.mjs` (fix del selector stale + asserts por superficie)
- Create: `docs/design-system/evidence/entrypoint-segmentation/project-selector/after/` (generado)

**Interfaces:**
- Consumes: `renderForModule('project-selector')` (Task 3); dry-run spec (Task 1).
- Produces: vista migrada; helper `expectSegmentedHead(page, { attachments })` definido en el propio smoke, que el test de auth del Task 7 (mismo archivo) reutiliza.

- [ ] **Step 1: Fix del selector stale y asserts de superficie migrada en el smoke (falla)**

En `tests/browser/design-system-consumer-smoke.mjs`, el assert actual usa `link[href^="/css/aia-design-system.css"]`, pero desde el serving runtime el href real es `/runtime/css/aia-design-system.css` (test stale, anterior a ese cambio). Reemplazar el contenido del archivo por:

```js
import { test, expect } from '@playwright/test';
import { PROJECTS } from './fixtures/projects.mjs';
import { loginAndSelectProject, logout } from './support/session.mjs';

const project = PROJECTS.find(({ key }) => key === 'construction');
const CI_ADMIN = { username: 'test.A', password: 'aia2026' };
const routes = [
  '/contratos', '/control-cambios', '/dashboard/escalamientos', '/indicadores',
  '/listado-actividades', '/pdc', '/profesionales', '/programa-general-actualizar',
  '/programa-general', '/programacion-intermedia', '/programacion-semanal/cic',
  '/programacion-semanal/cnc', '/programacion-semanal/cnp',
  '/programacion-semanal', '/subcontratistas',
];

const AGGREGATOR = 'link[href^="/runtime/css/aia-design-system.css"]';
const CORE = 'link[href^="/runtime/css/design-system/entrypoints/core.css"]';

// Superficies migradas: core + adjuntos declarados, nunca el agregador ni CSS de grilla.
async function expectSegmentedHead(page, { attachments }) {
  await expect(page.locator(CORE)).toHaveCount(1);
  await expect(page.locator(AGGREGATOR)).toHaveCount(0);
  for (const vendor of ['jquery-ui', 'anychart', 'select2', 'sweetalert2', 'handsontable']) {
    const locator = page.locator(`link[href^="/runtime/css/design-system/entrypoints/attach-${vendor}.css"]`);
    await expect(locator, `attach-${vendor}`).toHaveCount(attachments.includes(vendor) ? 1 : 0);
  }
  await expect(page.locator('link[href*="handsontable-module.css"]')).toHaveCount(0);
}

test('the 15 shared-head consumers load the canonical entrypoint', async ({ page }) => {
  test.skip(!project, 'Construction project required');
  await loginAndSelectProject(page, project, CI_ADMIN);
  try {
    for (const route of routes) {
      const response = await page.goto(route, { waitUntil: 'domcontentloaded' });
      expect(response?.status(), `${route} must respond`).toBeLessThan(400);
      await expect(
        page.locator(AGGREGATOR),
        `${route} must load the canonical entrypoint once`,
      ).toHaveCount(1);
      await expect(page.locator(CORE), `${route} must not load the segmented core`).toHaveCount(0);
      expect(await page.locator('body').innerText()).not.toContain('Fatal error');
    }
  } finally {
    await logout(page).catch(() => {});
  }
});

test('project selector loads the segmented core without grid vendors', async ({ page }) => {
  test.skip(!project, 'Construction project required');
  await loginAndSelectProject(page, project, CI_ADMIN);
  try {
    await page.goto('/proyectos', { waitUntil: 'domcontentloaded' });
    await expectSegmentedHead(page, { attachments: [] });
  } finally {
    await logout(page).catch(() => {});
  }
});
```

```bash
npx playwright test tests/browser/design-system-consumer-smoke.mjs --workers=1
```

Expected: el test de project selector FALLA (la vista aún carga el agregador); el de los 15 consumidores PASA (validando de paso que el fix del selector stale es correcto).

- [ ] **Step 2: Migrar la vista**

En `views/core/project_selector.view.php`, reemplazar las líneas 10-13 (comentario + `renderStylesheet('/css/tokens.css')` + `renderStylesheet('/css/aia-design-system.css')`) por:

```php
    <!-- Head segmentado: core del design system + adjuntos declarados en
         docs/design-system/manifests/project-selector.json (sin vendors de grilla). -->
    <?= \App\View\Components\DesignSystemHeadComponent::renderForModule('project-selector') ?>
```

Las líneas de `dark-mode.css` y `project-selector.css` quedan como están. (El orden pasa a ser core→tokens→dark-mode→project-selector; tokens después del core es el mismo orden que los 15 consumidores de `render()` ya usan en producción.)

- [ ] **Step 3: Actualizar el manifiesto**

En `docs/design-system/manifests/project-selector.json`:
- `tests`: añadir `"tests/browser/design-system-consumer-smoke.mjs"` y `"tests/browser/entrypoint-segmentation-dryrun.mjs"`.
- `evidence`: añadir `"docs/design-system/evidence/entrypoint-segmentation/project-selector/"`.
- `sources` y `vendors` no cambian.

- [ ] **Step 4: Correr smoke + static y verificar que pasan**

```bash
npx playwright test tests/browser/design-system-consumer-smoke.mjs --workers=1
npm run test:design-system:static
```

Expected: PASS ambos. El static valida además la coherencia nueva (vista↔manifiesto) y el contrato v1 vía `renderForModule`.

- [ ] **Step 5: Dry-run after + comparación con before**

```bash
DRYRUN_SURFACE=project-selector DRYRUN_PHASE=after npx playwright test tests/browser/entrypoint-segmentation-dryrun.mjs --workers=1
```

Comparar manualmente (y registrar en validation-log.md):
- `after/stylesheets.json`: `links` contiene `/runtime/css/design-system/entrypoints/core.css` y NO contiene el agregador; `cssRequests` no contiene `handsontable`, `anychart`, `select2`, `sweetalert2` ni `jquery-ui`.
- PNGs before vs after por viewport: sin diferencia visual perceptible. Comando auxiliar:

```bash
node -e "
const { readFileSync } = require('node:fs');
for (const v of ['1180x820', '1440x900']) {
  const a = readFileSync('docs/design-system/evidence/entrypoint-segmentation/project-selector/before/proyectos-' + v + '.png');
  const b = readFileSync('docs/design-system/evidence/entrypoint-segmentation/project-selector/after/proyectos-' + v + '.png');
  console.log(v, a.equals(b) ? 'IDÉNTICOS' : 'DIFIEREN (revisar visualmente)');
}"
```

Si difieren, abrir ambos PNG y evaluar: anti-aliasing/scrollbar aceptable; cualquier cambio de layout, color o tipografía = STOP, investigar antes de seguir (no reconciliar nada sin aprobación humana).
- `console.json` sin errores nuevos vs before; `axeViolations` sin violaciones serias nuevas.

- [ ] **Step 6: Commit**

```bash
git add views/core/project_selector.view.php docs/design-system/manifests/project-selector.json tests/browser/design-system-consumer-smoke.mjs docs/design-system/evidence/entrypoint-segmentation/project-selector goals/segmentacion-entrypoint-css/validation-log.md
git commit -m "feat(segmentacion-entrypoint-css): project-selector consume core segmentado con evidencia before/after"
```

---

### Task 6: Retirar compensaciones de scroll inertes en project-selector.css

Solo después de la evidencia del Task 5: sin `handsontable-module.css`, el des-bloqueo del selector queda inerte y se retira con prueba de scroll funcional.

**Files:**
- Modify: `public/css/project-selector.css:1-25` (aprox.)
- Test: evidencia de scroll en el dry-run + goldens del selector intactos

**Interfaces:**
- Consumes: vista migrada (Task 5).
- Produces: `project-selector.css` sin reglas compensatorias del lock global.

- [ ] **Step 1: Verificar en vivo qué compensaciones quedaron inertes**

```bash
node -e "
const { chromium } = require('@playwright/test');
(async () => {
  const browser = await chromium.launch();
  const page = await browser.newPage({ viewport: { width: 1180, height: 820 } });
  await page.goto('http://localhost:8081/login');
  await page.fill('input[name=\"username\"]', 'test.A');
  await page.fill('input[name=\"password\"]', 'aia2026');
  await page.click('button[type=\"submit\"]');
  await page.waitForURL('**/proyectos');
  const result = await page.evaluate(() => {
    const html = getComputedStyle(document.documentElement);
    const body = getComputedStyle(document.body);
    return {
      htmlOverflowY: html.overflowY,
      bodyOverflowY: body.overflowY,
      bodyDisplay: body.display,
      bodyHeight: body.height,
      scrollable: document.documentElement.scrollHeight >= document.documentElement.clientHeight,
    };
  });
  console.log(JSON.stringify(result, null, 2));
  await browser.close();
})();"
```

Ajustar los selectores de login a los reales de la vista (`grep -n 'name=' views/auth/login.view.php`). Expected: `htmlOverflowY` ≠ `hidden`, `bodyDisplay: block`.

- [ ] **Step 2: Retirar las reglas compensatorias**

En `public/css/project-selector.css`, dentro de `@layer components`:
- Eliminar el bloque `html:has(body.project-selector-page) { height: auto; overflow-y: auto; }` y su comentario explicativo (el lock que compensaba ya no llega a esta superficie).
- En `body.project-selector-page`: eliminar `overflow-y: visible;` y `height: auto;` (compensaban el `overflow: hidden`/`height: 100%` del lock). Conservar `display: block` (neutraliza el `body { display: flex }` que aún llega vía `styles.css`/otros globales si existiera — verificar contra el Step 1: si `bodyDisplay` ya es `block` sin la regla, puede retirarse también), `min-height: 100vh`, `font-family`, `color` y `background`.
- Añadir en su lugar un comentario breve:

```css
  /* Sin handsontable-module.css en el head segmentado, el documento scrollea
   por defecto; aquí solo queda la composición propia del selector. */
```

- [ ] **Step 3: Verificar scroll y contrato tras el retiro**

Re-ejecutar el script del Step 1 (mismo expected) y además:

```bash
DRYRUN_SURFACE=project-selector DRYRUN_PHASE=after npx playwright test tests/browser/entrypoint-segmentation-dryrun.mjs --workers=1
npm run test:design-system:static
```

Expected: dry-run PASS con PNGs sin cambio visual vs los del Task 5; static PASS (el contrato v1 del selector sigue verde: sin hex, sin `!important`, radios por token). Los goldens de `tests/browser/__screenshots__/project-selector/` no se tocan y sus sha256 del manifiesto siguen válidos.

- [ ] **Step 4: Commit**

```bash
git add public/css/project-selector.css docs/design-system/evidence/entrypoint-segmentation/project-selector goals/segmentacion-entrypoint-css/validation-log.md
git commit -m "fix(segmentacion-entrypoint-css): retirar el des-bloqueo de scroll ya inerte del selector con evidencia"
```

---

### Task 7: Manifiesto auth + migración de las tres vistas de autenticación

**Files:**
- Create: `docs/design-system/manifests/auth.json`
- Modify: `docs/design-system/manifests/inventory.json` (registrar `auth.json`)
- Modify: `views/auth/login.view.php:17-18`, `views/auth/password-forgot.view.php:13-14`, `views/auth/password-reset.view.php:13-14`
- Modify: `tests/browser/design-system-consumer-smoke.mjs` (test de auth)
- Create: `tests/browser/__screenshots__/auth/login-dark-1180x820.png` (golden desde evidencia after aprobada)

**Interfaces:**
- Consumes: `renderForModule('auth')` (Task 3), `expectSegmentedHead` (Task 5), dry-run spec (Task 1).
- Produces: manifiesto `auth` schema-v2 completo; vistas auth migradas.

- [ ] **Step 1: Verificar el uso real de vendors en auth**

```bash
grep -n "Swal\|select2\|handsontable\|anychart\|jquery-ui\|\$(" views/auth/login.view.php views/auth/password-forgot.view.php views/auth/password-reset.view.php views/auth/partials/*.php
```

Expected: `Swal` aparece en las tres vistas (o al menos login) → declarar `sweetalert2` (mantiene el adapter vendored que hoy llega vía agregador; paridad con el estado actual). Ninguna otra referencia de grilla → no declarar jquery-ui/anychart/select2/handsontable. Si el grep muestra algo distinto, ajustar `vendors` según el uso real y anotarlo en validation-log.md.

- [ ] **Step 2: Test smoke de auth (falla)**

Añadir a `tests/browser/design-system-consumer-smoke.mjs`:

```js
test('auth surfaces load the segmented core with only their declared attachments', async ({ page }) => {
  for (const route of ['/login', '/password/forgot', '/password/reset']) {
    const response = await page.goto(route, { waitUntil: 'domcontentloaded' });
    expect(response?.status(), `${route} must respond`).toBeLessThan(400);
    await expectSegmentedHead(page, { attachments: ['sweetalert2'] });
  }
});
```

(Si el Step 1 cambió la lista de vendors, ajustar `attachments` aquí y en el manifiesto del Step 3 en consecuencia.)

```bash
npx playwright test tests/browser/design-system-consumer-smoke.mjs --workers=1
```

Expected: el test de auth FALLA (las vistas aún cargan el agregador).

- [ ] **Step 3: Crear docs/design-system/manifests/auth.json**

```json
{
  "$schema": "../module-manifest.schema.json",
  "schemaVersion": 2,
  "designSystemVersion": "1.0.0",
  "moduleId": "auth",
  "routes": [
    "/login",
    "/password/forgot",
    "/password/reset"
  ],
  "sources": [
    "views/auth/login.view.php",
    "views/auth/password-forgot.view.php",
    "views/auth/password-reset.view.php",
    "views/auth/partials/auth-theme-switch.php",
    "public/css/login-brand-unified.css"
  ],
  "components": [
    "shell",
    "card",
    "field",
    "button",
    "state",
    "feedback"
  ],
  "vendors": [
    "bootstrap",
    "font-awesome",
    "aia-fonts",
    "sweetalert2"
  ],
  "layouts": [
    "desktop"
  ],
  "states": [
    "normal",
    "error",
    "focus"
  ],
  "roles": [
    "anonymous"
  ],
  "persistence": {
    "session": "none (pre-authentication surfaces)",
    "theme": "localStorage via theme.js"
  },
  "exceptions": [],
  "tests": [
    "tests/browser/design-system-consumer-smoke.mjs",
    "tests/browser/entrypoint-segmentation-dryrun.mjs"
  ],
  "evidence": [
    "docs/design-system/evidence/entrypoint-segmentation/auth/"
  ],
  "scenarios": [
    {
      "id": "auth-login-dark-1180x820",
      "family": "page-structure",
      "route": "/login",
      "fixture": "anonymous",
      "theme": "dark",
      "viewport": {
        "width": 1180,
        "height": 820
      },
      "density": "touch",
      "state": "normal",
      "role": "anonymous",
      "golden": "tests/browser/__screenshots__/auth/login-dark-1180x820.png",
      "sha256": "PENDIENTE-STEP-6"
    }
  ]
}
```

Notas: NO declarar `consumerContract: "v1"` — auth conserva CDN legacy (AdminLTE/FA5/Swal v11) que v1 prohíbe; ese saneamiento es un goal posterior. El campo es opcional en el schema y así el contrato v1 no aplica (mismo patrón que programa-general).

En `docs/design-system/manifests/inventory.json`: añadir `"auth.json"` a `manifests[]` y actualizar la entrada de `modules[]` de `auth` a `{ "moduleId": "auth", "status": "pilot", "manifest": "auth.json" }`. `sharedHeadConsumers` no cambia (auth nunca estuvo ahí; `foundation.test.mjs` exige exactamente 15).

- [ ] **Step 4: Migrar las tres vistas**

En cada vista de auth, reemplazar las DOS líneas del head (el `<link>` crudo de tokens con `filemtime` y la línea `renderStylesheet('/css/aia-design-system.css')`) por una sola:

```php
    <?= \App\View\Components\DesignSystemHeadComponent::renderForModule('auth') ?>
```

- `views/auth/login.view.php`: líneas 17-18.
- `views/auth/password-forgot.view.php`: líneas 13-14.
- `views/auth/password-reset.view.php`: líneas 13-14.

No tocar: los `<link>` de Google Fonts/FA5/AdminLTE/Swal-CDN, `login-brand-unified.css`, ni los `<script>` del final. Efectos deliberados de la migración (documentar en validation-log.md): auth gana `theme-bootstrap.js` (antes del primer CSS, elimina flash de tema; antes solo tenía `theme.js` al final) y pierde el lock de scroll de handsontable-module que hoy le llega por el agregador.

- [ ] **Step 5: Correr smoke + static**

```bash
npx playwright test tests/browser/design-system-consumer-smoke.mjs --workers=1
npm run test:design-system:static
```

Expected: smoke PASS completo (15 consumidores + selector + auth). Static: el consumer-contract no valida auth (sin `consumerContract`), la coherencia sí (manifiesto existe, vendors resolubles); PASS. Si `design-system-contracts.mjs` valida schema de manifiestos, auth.json debe pasar; el `sha256` placeholder del Step 3 se resuelve en el Step 6 ANTES del commit (si algún gate hash-valida escenarios de manifiestos sin `consumerContract`, correr `npm run test:design-system:static` de nuevo tras el Step 6).

- [ ] **Step 6: Dry-run after de auth + golden**

```bash
DRYRUN_SURFACE=auth DRYRUN_PHASE=after npx playwright test tests/browser/entrypoint-segmentation-dryrun.mjs --workers=1
```

Verificar y registrar en validation-log.md:
- `after/stylesheets.json` por ruta: core presente, agregador ausente, `attach-sweetalert2` presente, ningún otro attach, `cssRequests` sin handsontable/anychart/select2/jquery-ui.
- PNGs before/after por ruta y viewport: sin cambio de layout/color/tipografía (puede aparecer scrollbar si el contenido excede el viewport: es el efecto buscado del des-bloqueo; anotarlo).
- `console.json` sin errores nuevos; Axe sin violaciones serias nuevas.

Luego fijar el golden desde la evidencia aprobada:

```bash
mkdir -p tests/browser/__screenshots__/auth
cp docs/design-system/evidence/entrypoint-segmentation/auth/after/login-1180x820.png tests/browser/__screenshots__/auth/login-dark-1180x820.png
shasum -a 256 tests/browser/__screenshots__/auth/login-dark-1180x820.png
```

Escribir el hash resultante en `scenarios[0].sha256` de `auth.json` (reemplaza `PENDIENTE-STEP-6`).

- [ ] **Step 7: Commit**

```bash
git add docs/design-system/manifests/auth.json docs/design-system/manifests/inventory.json views/auth/login.view.php views/auth/password-forgot.view.php views/auth/password-reset.view.php tests/browser/design-system-consumer-smoke.mjs tests/browser/__screenshots__/auth docs/design-system/evidence/entrypoint-segmentation/auth goals/segmentacion-entrypoint-css/validation-log.md
git commit -m "feat(segmentacion-entrypoint-css): auth migra al core segmentado con manifiesto, golden y evidencia"
```

---

### Task 8: Equivalencia del agregador, suite completa de gates y cierre

**Files:**
- Modify: `goals/segmentacion-entrypoint-css/validation-log.md` (cierre)
- Verify-only: todo lo demás.

**Interfaces:**
- Consumes: hash before (Task 1), todo lo anterior.

- [ ] **Step 1: Equivalencia byte-a-byte del agregador**

La base de comparación es `0c08755` (commit del diseño del goal, estado previo a toda implementación) — no `main`, que puede divergir por trabajo ajeno a este goal.

```bash
git diff --stat 0c08755 -- public/css/aia-design-system.css
curl -s "http://localhost:8081/runtime/css/aia-design-system.css" | sed -E 's/\?v=[0-9]+/?v=X/g' | shasum -a 256
```

Expected: diff vacío; hash normalizado IDÉNTICO al hash before del Task 1. Registrar ambos en validation-log.md.

- [ ] **Step 2: Programa General intacto**

```bash
git diff --stat 0c08755 -- views/programa-general views/programa-general-actualizar public/css/programa-general.css public/css/design-system/adapters/programa-general-handsontable.css docs/design-system/manifests/programa-general.json 'tests/browser/__screenshots__/programa-general*'
node --test tests/design-system/programa-general-runtime-requests.test.mjs
```

Expected: diff vacío en todos; test PASS.

- [ ] **Step 3: Suite completa**

```bash
npm run check:design-system:biome
npm run test:design-system:static
npm run test:design-system:phpstan
node scripts/design-system-router.mjs
npx playwright test tests/browser/design-system-consumer-smoke.mjs tests/browser/project-selector-sidebar.spec.mjs --workers=1
docker compose -f docker-compose.yml -f docker-compose.ci.yml exec -T app php tests/test_design_system_head_component.php
```

Expected: todo PASS (único rojo tolerado: `laboratory-hardening` doc-drift preexistente, sin crecer). El router debe listar las superficies declaradas (`projects`, `auth`) sin advertencias de superficie sin manifiesto para los archivos del diff.

- [ ] **Step 4: Cerrar validation-log.md**

Completar con: hash equivalencia before/after, resumen de evidencia por superficie (rutas, viewports, resultado visual, Axe, consola), efectos deliberados documentados (theme-bootstrap en auth, scrollbar), doble carga SweetAlert2 en auth registrada como deuda observada (CDN v11 + vendored; se resuelve en la migración visual completa de auth, goal posterior), y la lista de superficies que permanecen en el agregador.

- [ ] **Step 5: Commit de cierre**

```bash
git add goals/segmentacion-entrypoint-css/validation-log.md
git commit -m "docs(segmentacion-entrypoint-css): validation log de cierre con equivalencia y evidencia por superficie"
```

---

## Riesgos y reglas de reversa

- **Cualquier diferencia visual no explicable** en un dry-run after → STOP: no reconciliar goldens ni aprobar evidencia sin decisión humana explícita.
- **Revertir una superficie** = restaurar sus líneas de head originales (el fallback y el agregador siguen intactos); no requiere tocar infraestructura.
- **Si `design-system-contracts.mjs` o algún test estático valida cosas no previstas** (p. ej. schema estricto de inventory.json), leer el fallo, ajustar el dato — nunca el gate — y anotar en validation-log.md.
- **El worktree debe quedar limpio antes de `test:design-system:static`** en cada task (el gate de contratos lo exige): commitear antes de correr la suite completa.
