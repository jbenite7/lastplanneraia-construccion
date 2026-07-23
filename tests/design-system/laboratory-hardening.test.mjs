import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';

const read = (path) => readFile(new URL(`../../${path}`, import.meta.url), 'utf8');

test('the laboratory owns a dark-only lightweight entrypoint', async () => {
  const [view, head, entrypoint, applicationEntrypoint, core, laboratoryFoundation] = await Promise.all([
    read('views/design-system/lab.view.php'),
    read('src/View/Components/DesignSystemHeadComponent.php'),
    read('public/css/design-system/lab-entrypoint.css'),
    read('public/css/aia-design-system.css'),
    read('public/css/design-system/core.css'),
    read('public/css/design-system/laboratory-foundation.css'),
  ]);

  assert.match(view, /DesignSystemHeadComponent::renderLaboratory\(\)/);
  assert.match(view, /<html[^>]*data-aia-theme="dark"[^>]*class="aia-theme-dark"/);
  assert.doesNotMatch(view, /DesignSystemHeadComponent::render\(true\)/);
  assert.doesNotMatch(view, /data-lab-theme|\/js\/modules\/aia_ui\/theme\.js/);

  const laboratoryMethod = head.slice(
    head.indexOf('public static function renderLaboratory'),
    head.indexOf('public static function renderScript'),
  );
  assert.match(laboratoryMethod, /\/css\/tokens\.css/);
  assert.match(laboratoryMethod, /\/css\/design-system\/lab-entrypoint\.css/);
  assert.doesNotMatch(laboratoryMethod, /aia-design-system\.css|vendor-datatables-legacy/);

  assert.match(entrypoint, /^@layer reset, vendor, theme, base, layout, components, utilities, module, legacy-overrides;/);
  assert.match(entrypoint, /design-system\/core\.css/);
  assert.match(entrypoint, /design-system\/laboratory-foundation\.css/);
  assert.match(entrypoint, /design-system\/lab\.css/);
  assert.match(applicationEntrypoint, /design-system\/core\.css/);
  assert.equal((entrypoint.match(/design-system\/core\.css/g) ?? []).length, 1);
  assert.equal((applicationEntrypoint.match(/design-system\/core\.css/g) ?? []).length, 1);
  assert.match(core, /\.aia-shell\s*\{/);
  assert.match(core, /\.aia-btn\s*\{/);
  assert.match(core, /\.aia-visually-hidden\s*\{/);
  assert.doesNotMatch(applicationEntrypoint, /\.aia-shell\s*\{|\.aia-btn\s*\{/);
  assert.doesNotMatch(laboratoryFoundation, /\.aia-shell\s*\{|\.aia-btn\s*\{|\.aia-chip\s*\{/);
  for (const forbidden of [
    'font-awesome', 'bootstrap', 'jquery-ui', 'anychart', 'select2.min.css',
    'sweetalert2.min.css', 'handsontable.full.min.css', 'styles.css',
    'buttons.css', 'access.css', 'legacy-bridge.css',
    'programa-general-handsontable.css', 'semi-auto-review.css', 'lps-drawer.css',
  ]) {
    assert.doesNotMatch(entrypoint, new RegExp(forbidden.replaceAll('.', '\\.')));
  }
  assert.ok(
    [...entrypoint.matchAll(/@import\s+url\(/g)].length <= 18,
    'the laboratory CSS request fanout must remain bounded',
  );
});

test('the laboratory manifest declares only the governed desktop dark matrix', async () => {
  const manifest = JSON.parse(await read('docs/design-system/manifests/laboratory.json'));
  const families = new Set(manifest.scenarios.map(({ family }) => family));
  const scenarioKeys = new Set(manifest.scenarios.map(({ theme, viewport }) => (
    `${theme}/${viewport.width}x${viewport.height}`
  )));

  assert.deepEqual(manifest.layouts, ['desktop', 'wide']);
  assert.equal(families.size, 10);
  assert.equal(manifest.scenarios.length, 20);
  assert.deepEqual([...scenarioKeys].sort(), ['dark/1180x820', 'dark/1440x900']);
  assert.ok(manifest.sources.includes('public/css/design-system/core.css'));
  assert.ok(manifest.sources.includes('public/css/design-system/lab-entrypoint.css'));
  assert.ok(!manifest.sources.includes('public/css/aia-design-system.css'));
});

test('canonical laboratory commands stay inside desktop dark laboratory scope', async () => {
  const packageJson = JSON.parse(await read('package.json'));
  const runtime = packageJson.scripts['test:design-system:runtime'];
  const evidence = packageJson.scripts['test:design-system:evidence'];

  assert.match(runtime, /design-system-lab\.mjs/);
  assert.match(runtime, /test:a11y:lab/);
  assert.match(runtime, /test:visual:lab/);
  assert.match(runtime, /test:performance:lab/);
  assert.doesNotMatch(runtime, /pilot|programa-general|consumer-smoke|test:runtime-budget/);

  assert.match(evidence, /design-system-lab-keyboard\.mjs/);
  assert.match(evidence, /design-system-lab-desktop-layout\.mjs/);
  assert.doesNotMatch(evidence, /design-system-(?:keyboard|reflow)\.mjs|pilot|programa-general/);
});

test('laboratory performance provenance includes staged and untracked source files', async () => {
  const performance = await read('tests/browser/design-system-lab.performance.mjs');

  assert.match(performance, /\['diff',\s*'HEAD',\s*'--binary'/);
  assert.match(performance, /\['ls-files',\s*'--others',\s*'--exclude-standard',\s*'-z'/);
  assert.match(performance, /untrackedPaths/);
  assert.match(performance, /readFileSync\(sourcePath\)/);
});

test('the shared core and production dark mapping resolve visual values through tokens', async () => {
  const [tokens, core, applicationEntrypoint] = await Promise.all([
    read('public/css/tokens.css'),
    read('public/css/design-system/core.css'),
    read('public/css/aia-design-system.css'),
  ]);
  const governedTokens = [
    '--ds-shell-background',
    '--ds-content-max-width',
    '--ds-page-padding',
    '--ds-card-padding',
    '--ds-control-padding-block',
    '--ds-control-line-height',
    '--ds-field-padding-block',
    '--ds-chip-min-height',
    '--ds-chip-gap',
    '--ds-chip-font-size',
    '--ds-chip-font-weight',
    '--ds-chip-padding-inline',
    '--ds-alert-padding-inline',
    '--ds-empty-min-height',
  ];
  for (const token of governedTokens) {
    assert.match(tokens, new RegExp(`${token}:\\s*`), `missing ${token}`);
    assert.match(core, new RegExp(`var\\(${token.replaceAll('-', '\\-')}\\)`), `core does not consume ${token}`);
  }
  for (const literal of [
    /rgba\(167,\s*213,\s*193/,
    /1440px/,
    /border:\s*1px/,
    /gap:\s*0\.75rem/,
    /font-weight:\s*(?:700|800)/,
    /font-size:\s*0\.78rem/,
    /min-height:\s*(?:1\.75|12)rem/,
  ]) assert.doesNotMatch(core, literal);

  const darkThemeStart = applicationEntrypoint.search(/\[data-aia-theme=(['"])dark\1\]/);
  assert.notEqual(darkThemeStart, -1, 'missing the production dark theme mapping');
  const darkTheme = applicationEntrypoint.slice(
    darkThemeStart,
    applicationEntrypoint.indexOf('@layer legacy-overrides'),
  );
  assert.doesNotMatch(darkTheme, /#[0-9a-f]{3,8}|rgba?\(/i);
  for (const token of [
    '--ds-color-bg-canvas-dark',
    '--ds-color-bg-page-dark',
    '--ds-color-surface-dark',
    '--ds-color-surface-raised-dark',
    '--ds-color-surface-glass-dark',
    '--ds-color-text-primary-dark',
    '--ds-color-text-secondary-dark',
    '--ds-color-border-dark',
  ]) assert.match(darkTheme, new RegExp(`var\\(${token.replaceAll('-', '\\-')}\\)`));
});

test('approval status is stated once per family instead of repeated as eyebrows', async () => {
  const [view, familyFiles] = await Promise.all([
    read('views/design-system/lab.view.php'),
    Promise.all([
      'actions', 'bi-primitives', 'data-display', 'forms-filters', 'overlays',
      'page-structure', 'shell-navigation', 'states-feedback', 'vendor-adapters',
    ].map((family) => read(`views/design-system/families/${family}.php`))),
  ]);

  assert.doesNotMatch(view, /familyCandidateEyebrow|ds-lab__family-kicker|ds-lab__review-note/);
  assert.match(view, /ds-lab__family-head[\s\S]*aia-chip/);
  assert.doesNotMatch(familyFiles.join('\n'), /Patrón aprobado|Patrón en revisión/);
});

test('laboratory accessibility evidence distinguishes automation from human signoff', async () => {
  const review = await read('docs/design-system/manual-accessibility-review.md');
  assert.match(review, /2026-07-19/);
  assert.match(review, /1180x820/);
  assert.match(review, /1440x900/);
  assert.match(review, /Touch[^\n]*1180x820|1180x820[^\n]*Touch/i);
  assert.match(review, /Compacta[^\n]*1440x900|1440x900[^\n]*Compacta/i);
  assert.match(review, /teclado|keyboard/i);
  assert.match(review, /foco/i);
  assert.match(review, /contraste/i);
  // La revisión humana independiente fue otorgada el 2026-07-19; el contrato
  // ahora exige que quede registrada con trazabilidad (fecha, confirmación
  // explícita y commit cubierto), manteniendo separada la evidencia
  // automatizada de la firma humana.
  assert.match(review, /Aprobación humana independiente — 2026-07-19/);
  assert.match(review, /confirmó explícitamente \*\*Aprobado\*\*/);
  assert.match(review, /cubre el commit `[0-9a-f]{40}`/);
  assert.match(review, /Revisión local asistida/);
  assert.doesNotMatch(review, /revisión humana independiente[^\n]*pendiente/i);
});
