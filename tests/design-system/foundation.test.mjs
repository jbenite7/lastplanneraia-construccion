import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';
import { readFile } from 'node:fs/promises';
import { existsSync, statSync } from 'node:fs';
import test from 'node:test';

const read = (path) => readFile(new URL(`../../${path}`, import.meta.url), 'utf8');
const repositoryRoot = new URL('../..', import.meta.url);

const runPhpInApp = (script) => {
  const composeOptions = { cwd: repositoryRoot };
  const runningServices = execFileSync(
    'docker',
    ['compose', 'ps', '--status', 'running', '--services'],
    composeOptions,
  ).toString().split(/\r?\n/);
  const appCommand = runningServices.includes('app')
    ? ['compose', 'exec', '-T', 'app']
    : ['compose', 'run', '--rm', '--no-deps', 'app'];

  return execFileSync('docker', [...appCommand, 'php', '-r', script], composeOptions).toString();
};

test('semantic tokens cover every governed foundation', async () => {
  const css = await read('public/css/tokens.css');
  for (const token of [
    '--ds-space-', '--ds-type-', '--ds-z-', '--ds-breakpoint-',
    '--ds-density-compact-', '--ds-density-touch-', '--ds-motion-',
  ]) {
    assert.match(css, new RegExp(token), `missing ${token}`);
  }
});

test('the canonical radius scale matches the AIA brand contract', async () => {
  const css = await read('public/css/tokens.css');
  const expected = new Map([
    ['--ds-radius-none', '0'],
    ['--ds-radius-xs', '0.25rem'],
    ['--ds-radius-sm', '0.5rem'],
    ['--ds-radius-md', '0.75rem'],
    ['--ds-radius-lg', '1rem'],
    ['--ds-radius-xl', '1.25rem'],
    ['--ds-radius-2xl', '1.5rem'],
    ['--ds-radius-3xl', '2rem'],
    ['--ds-radius-pill', '9999px'],
    ['--ds-radius-control', 'var(--ds-radius-md)'],
    ['--ds-radius-control-sm', 'var(--ds-radius-sm)'],
    ['--ds-radius-card', 'var(--ds-radius-lg)'],
    ['--ds-radius-panel', 'var(--ds-radius-2xl)'],
    ['--ds-radius-popover', 'var(--ds-radius-lg)'],
    ['--ds-radius-modal', 'var(--ds-radius-2xl)'],
    ['--ds-radius-table', 'var(--ds-radius-lg)'],
    ['--ds-radius-search', 'var(--ds-radius-md)'],
  ]);

  for (const [token, value] of expected) {
    const declaration = css.match(new RegExp(`${token}:\\s*([^;]+);`));
    assert.equal(declaration?.[1].trim(), value, `${token} must equal ${value}`);
  }
});

test('responsive density defaults are encoded in the shared token API', async () => {
  const css = await read('public/css/tokens.css');
  assert.match(css, /--ds-density-active-visual:\s*var\(--ds-density-touch-control\)/);
  assert.match(css, /@media\s*\(min-width:\s*75rem\)[\s\S]*--ds-density-active-visual:\s*var\(--ds-density-compact-visual\)/);
  assert.match(css, /--ds-density-active-control:\s*var\(--ds-target-min\)/);
  assert.match(css, /\[data-density=["']touch["']\][\s\S]*--ds-density-active-gap:\s*var\(--ds-density-touch-gap\)/);
  assert.match(css, /\[data-density=["']compact["']\][\s\S]*--ds-density-active-gap:\s*var\(--ds-density-compact-gap\)/);
});

test('the foundations specimen explains its internal governance terms', async () => {
  const contract = JSON.parse(await read('docs/design-system/homologation.json'));
  const foundations = contract.families.find(({ id }) => id === 'foundations');
  const view = await read('views/design-system/lab.view.php');
  assert.equal(foundations.label, 'Fundamentos de marca');
  assert.match(foundations.description, /color|tipograf/i);
  assert.match(view, /En revisión/);
  assert.match(view, /data-lab-family-link/);
  assert.match(view, /Familias del design system/);
  assert.doesNotMatch(view, />candidate</);
});

test('the laboratory server renders only the requested valid family on first paint', async () => {
  const [controller, view] = await Promise.all([
    read('src/Controllers/Internal/DesignSystemLabController.php'),
    read('views/design-system/lab.view.php'),
  ]);

  assert.match(controller, /\$requestedFamilyId\s*=\s*is_string\(\$_GET\['family'\]/);
  assert.match(controller, /in_array\(\$requestedFamilyId,\s*\$familyIds,\s*true\)/);
  assert.match(view, /\$familyId === \$initialFamilyId\s*\?\s*' aria-current="page"'/);
  assert.match(view, /\$familyId !== \$initialFamilyId\s*\?\s*' hidden'/);
});

test('the rendered foundations specimen does not inherit approval from a different baseline', async () => {
  const contract = JSON.parse(await read('docs/design-system/homologation.json'));
  const foundations = contract.families.find(({ id }) => id === 'foundations');
  const active = foundations.candidates.find(({ id }) => id === foundations.activeCandidate);
  const view = await read('views/design-system/lab.view.php');

  assert.equal(foundations.activeCandidate, 'foundation-inventory-action-color');
  assert.equal(active?.status, 'candidate');
  assert.match(view, /data-active-candidate/);
  assert.match(view, /data-family-status/);
});

test('the foundations card follows the brand manual spacing', async () => {
  const css = await read('public/css/design-system/lab.css');
  assert.match(css, /\.ds-lab__family\s*{[^}]*padding:\s*var\(--ds-space-4\)/s);
  assert.match(css, /min-width:\s*48\.01rem[\s\S]*\.ds-lab__family\s*{[^}]*padding:\s*var\(--ds-space-6\)/);
});

test('the foundations specimen separates AIA brand domains from operational states', async () => {
  const tokens = await read('public/css/tokens.css');
  const specimen = await read('views/design-system/families/foundations.php');
  for (const token of [
    '--ds-color-domain-corporate', '--ds-color-domain-construction',
    '--ds-color-domain-real-estate', '--ds-color-domain-architecture',
  ]) assert.match(tokens, new RegExp(token), `missing ${token}`);
  assert.match(specimen, /Paleta de marca/);
  assert.match(specimen, /Corporativo/);
  assert.match(specimen, /Inmobiliario/);
  assert.match(specimen, /Arquitectura/);
  assert.match(specimen, /Jerarquía para lectura operativa/);
  assert.doesNotMatch(specimen, /aria-current/);
  assert.doesNotMatch(specimen, />Éxito</);
});

test('the laboratory navigation uses native links and density radios', async () => {
  const [view, script] = await Promise.all([
    read('views/design-system/lab.view.php'),
    read('public/js/modules/aia_ui/design_system_lab.js'),
  ]);
  assert.match(view, /<nav class="ds-lab__rail" aria-label="Familias del design system">/);
  assert.match(view, /type="radio" name="lab-density" value="compact" data-lab-density/);
  assert.match(view, /type="radio" name="lab-density" value="touch" data-lab-density/);
  assert.doesNotMatch(view, /id="lab-family"/);
  assert.match(script, /window\.history\[[^\]]*historyMode[^\]]*\]/);
  assert.match(script, /window\.addEventListener\(["']popstate["']/);
});

test('dark controls expose a semantic high-contrast focus token', async () => {
  const [tokens, core, laboratoryTheme] = await Promise.all([
    read('public/css/tokens.css'),
    read('public/css/design-system/core.css'),
    read('public/css/design-system/laboratory-foundation.css'),
  ]);
  assert.match(tokens, /--ds-color-focus-ring-dark:\s*#2caa9f/);
  assert.match(laboratoryTheme, /--ds-active-focus-ring:\s*var\(--ds-color-focus-ring-dark\)/);
  assert.match(core, /outline:\s*var\(--ds-outline-width\) solid var\(--ds-active-focus-ring\)/);
});

test('the Handsontable rail only reserves space where the rail is mounted', async () => {
  const css = await read('public/css/handsontable-module.css');
  const rule = css.match(/(body:not\([^{]*)\{[^}]*padding-right:\s*var\(--lps-rail-safe-width\) !important/);
  assert.ok(rule, 'the rail-safe reservation rule must exist');
  // Surfaces that never mount the LPS rail must be excluded, or they inherit dead
  // right-hand gutter. Assert the exclusions, not one exact selector string.
  for (const excluded of ['.ds-lab', '.project-selector-page']) {
    assert.ok(
      rule[1].includes(`:not(${excluded})`),
      `${excluded} must be excluded from the rail-safe reservation`,
    );
  }
});

test('every brand domain has an accessible dark-appearance variant', async () => {
  const css = await read('public/css/tokens.css');
  const variants = ['#6c9077', '#c57247', '#2caa9f', '#877cd1'];
  const luminance = (hex) => {
    const channels = hex.match(/[\da-f]{2}/gi).map((value) => parseInt(value, 16) / 255)
      .map((value) => value <= 0.04045 ? value / 12.92 : ((value + 0.055) / 1.055) ** 2.4);
    return 0.2126 * channels[0] + 0.7152 * channels[1] + 0.0722 * channels[2];
  };
  const label = luminance('#141c18');
  for (const color of variants) {
    assert.match(css, new RegExp(color, 'i'));
    const ratio = (luminance(color) + 0.05) / (label + 0.05);
    assert.ok(ratio >= 4.5, `${color} contrast is ${ratio.toFixed(2)}:1`);
  }
});

// F0/Task 7 retiro el tema linen: la asercion sobre
// `[data-aia-theme="linen"] ... --ds-color-domain-corporate` quedo sin
// objeto (ya no hay ningun selector linen que mapear) y se elimino. La
// asercion sobre dark, y las dos de .aia-btn consumiendo los tokens
// --ds-active-*, siguen intactas: siguen protegiendo lo mismo que antes.
test('primary actions remap to the canonical corporate color in the dark theme', async () => {
  const [entrypoint, core] = await Promise.all([
    read('public/css/aia-design-system.css'),
    read('public/css/design-system/core.css'),
  ]);
  assert.match(entrypoint, /\[data-aia-theme=["']dark["']\][\s\S]*--ds-active-action-primary:\s*var\(--ds-color-domain-corporate-on-dark\)/);
  assert.match(core, /\.aia-btn\s*\{[\s\S]*background:\s*var\(--ds-active-action-primary\)/);
  assert.match(core, /\.aia-btn\s*\{[\s\S]*color:\s*var\(--ds-active-action-text\)/);
});

test('the entrypoint declares the deterministic cascade', async () => {
  const css = await read('public/css/aia-design-system.css');
  assert.match(css, /^@layer reset, vendor, theme, base, layout, components, utilities, module, legacy-overrides;/);
  assert.match(css, /bootstrap\.min\.css["']\) layer\(vendor\);/);
  assert.match(css, /styles\.css\?v=\d+\.\d+\.\d+["']\) layer\(module\);/);
  assert.match(css, /@import url\(["']\/css\/design-system\/foundation\.css\?v=\d+\.\d+\.\d+["']\);/);
  assert.match(css, /@import url\(["']\/css\/design-system\/core\.css\?v=\d+\.\d+\.\d+["']\);/);
  assert.match(css, /@import url\(["']\/css\/design-system\/adapters\/legacy-bridge\.css\?v=\d+\.\d+\.\d+["']\);/);
  assert.doesNotMatch(css, /@import url\(["']\.\//, 'axe CSSOM preload requires absolute internal imports');
});

test('the common loader keeps JavaScript compatibility only', async () => {
  const js = await read('public/js/linksComunesHead2.js');
  assert.doesNotMatch(js, /injectStylesheet|createElement\(['"]style['"]\)/);
  assert.match(js, /loadScript\('/);
});

test('the PHP head component owns static shared assets', async () => {
  const php = await read('src/View/Components/DesignSystemHeadComponent.php');
  assert.match(php, /final class DesignSystemHeadComponent/);
  assert.match(php, /public static function render/);
  assert.match(php, /\/css\/aia-design-system\.css/);
  assert.match(php, /\/css\/tokens\.css/);
  assert.match(php, /vendor-datatables-legacy\.css/);
  assert.doesNotMatch(php, /\/public\/vendor\/bootstrap\/bootstrap\.min\.css/);
  assert.doesNotMatch(php, /https?:\/\//);
});

test('the shared head applies the persisted theme before the first stylesheet can paint', async () => {
  const [php, bootstrap] = await Promise.all([
    read('src/View/Components/DesignSystemHeadComponent.php'),
    read('public/js/modules/aia_ui/theme-bootstrap.js'),
  ]);
  assert.match(php, /theme-bootstrap\.js/);
  assert.match(php, /renderScript/);
  assert.ok(
    php.indexOf('theme-bootstrap.js') < php.indexOf('/css/aia-design-system.css'),
    'theme bootstrap must precede the design-system stylesheet',
  );
  // F0/Task 8 retiro el conmutador de tema: el bootstrap ya no lee
  // localStorage.aia-theme (una clave obsoleta heredada de un usuario que
  // uso el toggle antes de esta tarea no debe poder devolver ninguna ruta a
  // claro) ni conoce "linen" — aplica dark de forma incondicional.
  assert.doesNotMatch(bootstrap, /localStorage/);
  assert.doesNotMatch(bootstrap, /linen/);
  assert.match(bootstrap, /setAttribute\(['"]data-aia-theme['"], ['"]dark['"]\)/);
  assert.match(bootstrap, /classList\.add\(['"]aia-theme-dark['"]\)/);
  assert.doesNotMatch(bootstrap, /DOMContentLoaded|requestAnimationFrame|setTimeout/);

  const render = 'require "src/View/Components/DesignSystemHeadComponent.php";'
    + ' echo App\\View\\Components\\DesignSystemHeadComponent::render(true);';
  const html = runPhpInApp(render);
  assert.match(html, /<script src="\/js\/modules\/aia_ui\/theme-bootstrap\.js\?v=\d+"><\/script>/);
  assert.ok(
    html.indexOf('theme-bootstrap.js') < html.indexOf('aia-design-system.css'),
    'rendered bootstrap must precede the rendered design-system stylesheet',
  );
});

test('versioned tokens are not hidden behind an unversioned CSS import', async () => {
  const css = await read('public/css/aia-design-system.css');
  assert.doesNotMatch(css, /@import url\('\.\/tokens\.css'\)/);
});

test('local entrypoint imports share the published design-system version', async () => {
  const css = await read('public/css/aia-design-system.css');
  const { version } = JSON.parse(await read('docs/design-system/version.json'));
  const imports = [...css.matchAll(/@import url\((["'])\/css\/([^"']+)\1\)/g)]
    .map((match) => match[2]);
  assert.ok(imports.length > 0, 'expected local design-system imports');
  for (const url of imports) {
    assert.match(url, new RegExp(`\\?v=${version.replaceAll('.', '\\.')}$`), `unversioned import: ${url}`);
  }
});

test('stylesheet versions follow nested CSS changes', () => {
  const php = 'require "src/View/Components/DesignSystemHeadComponent.php";'
    + ' echo App\\View\\Components\\DesignSystemHeadComponent::render(true);';
  const html = runPhpInApp(php);
  const version = Number(html.match(/aia-design-system\.css\?v=(\d+)/)?.[1]);
  const tokensMtime = Math.floor(statSync(new URL('../../public/css/tokens.css', import.meta.url)).mtimeMs / 1000);
  assert.ok(version >= tokensMtime, `entrypoint ${version} is older than tokens ${tokensMtime}`);
  // The entrypoint is served through PHP so its nested imports can carry real
  // file mtimes; the static file keeps the published semver untouched.
  assert.match(html, /href="\/runtime\/css\/aia-design-system\.css\?v=\d+"/);
});

test('runtime-served entrypoints stamp nested imports with file mtimes', () => {
  const php = 'require "src/Controllers/Core/DesignSystemAssetController.php";'
    + ' (new App\\Controllers\\Core\\DesignSystemAssetController())->main();';
  const css = runPhpInApp(php);
  assert.doesNotMatch(css, /\?v=1\.0\.0/, 'served entrypoint must not keep the static semver');
  assert.match(css, /navigation\.css\?v=\d{9,}/, 'imports must carry unix-mtime versions');
  assert.match(css, /^@layer reset, vendor, theme, base, layout, components, utilities, module, legacy-overrides;/, 'layer order must survive the rewrite');
  assert.match(css, /@import url\("\/public\/vendor\/bootstrap\/bootstrap\.min\.css"\) layer\(vendor\)/, 'vendor imports stay untouched');
});

test('the laboratory stylesheet has its own cache version', async () => {
  const view = await read('views/design-system/lab.view.php');
  assert.match(view, /DesignSystemHeadComponent::renderLaboratory\(\)/);

  const php = 'require "src/View/Components/DesignSystemHeadComponent.php";'
    + ' echo App\\View\\Components\\DesignSystemHeadComponent::renderLaboratory();';
  const html = runPhpInApp(php);
  assert.match(html, /tokens\.css\?v=\d+/);
  assert.match(html, /href="\/runtime\/css\/design-system\/lab-entrypoint\.css\?v=\d+"/);
});

test('the laboratory document explicitly enables vertical scrolling', async () => {
  const css = await read('public/css/design-system/lab.css');
  assert.match(css, /\.ds-lab\s*{[^}]*overflow-y:\s*auto/s);
});

test('legacy common-head views render the static head component', async () => {
  const inventory = JSON.parse(await read('docs/design-system/manifests/inventory.json'));
  const views = inventory.sharedHeadConsumers;
  // No se fija un conteo exacto (se rompe cada vez que cambia el
  // inventario, como paso al retirar el PDC v1); se exige solo que la
  // lista no este vacia, porque un array vacio haria que el bucle de abajo
  // pasara en vano sin comprobar nada.
  assert.ok(views.length > 0, 'sharedHeadConsumers no debe estar vacio');
  for (const view of views) {
    // Una vista declarada que no existe en disco es el inventario mintiendo,
    // no un caso tolerable: el `if (existsSync(...))` que habia aqui vaciaba la
    // prueba por dentro (si las vistas desaparecieran sin salir del inventario,
    // el bucle pasaba sin comprobar nada). Ahora falta = fallo.
    assert.ok(
      existsSync(new URL(`../../${view}`, import.meta.url)),
      `sharedHeadConsumers declara una vista inexistente: ${view}`,
    );
    assert.match(await read(view), /DesignSystemHeadComponent::render\((?:true)?\)/);
  }
});

test('shared JavaScript does not generate visual CSS', async () => {
  const notices = await read('public/js/core/AiaAlertInterceptor.js');
  assert.doesNotMatch(notices, /injectStyles|createElement\(['"]style['"]\)/);
  // semi_auto_review.js se eliminó el 2026-08-04 con el PDC v1.
  const drawer = await read('public/js/modules/lps_drawer.js');
  assert.doesNotMatch(drawer, /innerHTML\s*=\s*[`'"][^\n]*style=/);
});

test('shared generated styles have governed adapter files', async () => {
  const css = await read('public/css/aia-design-system.css');
  for (const adapter of [
    'sweetalert2', 'lps-drawer',
  ]) {
    assert.match(css, new RegExp(`adapters\\/${adapter}\\.css`));
    await read(`public/css/design-system/adapters/${adapter}.css`);
  }
});

test('canonical vendor adapters are loaded by the shared entrypoint', async () => {
  const css = await read('public/css/aia-design-system.css');
  for (const adapter of ['handsontable', 'select2']) {
    assert.match(css, new RegExp(`adapters\\/${adapter}\\.css`));
    const adapterCss = await read(`public/css/design-system/adapters/${adapter}.css`);
    assert.match(adapterCss, /@layer components/);
    assert.match(adapterCss, /--ds-active-/);
  }
});

test('the approved page header loads a token-only canonical stylesheet', async () => {
  const entrypoint = await read('public/css/aia-design-system.css');
  assert.match(entrypoint, /components\/page-header\.css/);
  const css = await read('public/css/design-system/components/page-header.css');
  assert.match(css, /@layer components/);
  assert.match(css, /\.aia-page-header/);
  assert.match(css, /--ds-active-text-primary/);
  assert.doesNotMatch(css, /#[\da-f]{3,8}\b|\b(?:px|rem)\b/i);
});
