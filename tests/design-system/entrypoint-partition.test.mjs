import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { join } from 'node:path';
import { fileURLToPath } from 'node:url';
import test from 'node:test';
import { partitionFailures } from '../../scripts/design-system-entrypoint-partition.mjs';
import { coherenceFailures } from '../../scripts/design-system-entrypoint-partition.mjs';

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

test('el gate exige que core importe theme-overrides.css como último import', () => {
  const realCore = readFileSync(
    join(root, 'public/css/design-system/entrypoints/core.css'),
    'utf8',
  );
  const coreWithoutThemeOverrides = realCore.replace(
    '@import url("/css/design-system/entrypoints/theme-overrides.css?v=1.0.0");',
    '',
  );
  const failures = partitionFailures({ root, coreOverride: coreWithoutThemeOverrides });
  assert.ok(failures.some((f) => f.includes('theme-overrides-missing')));
});

test('el gate rechaza un @import con sintaxis no canónica (comillas simples)', () => {
  const failures = partitionFailures({
    root,
    attachmentOverrides: {
      'jquery-ui': "@import url('/public/vendor/evil.css');\n@import url(\"/public/vendor/jquery-ui.css\") layer(vendor);\n",
    },
  });
  assert.ok(failures.some((f) => f.includes('unparseable-import')));
});

test('el gate detecta order-drift cuando dos imports de un adjunto se invierten', () => {
  const failures = partitionFailures({
    root,
    attachmentOverrides: {
      anychart: '@import url("/public/vendor/anychart/anychart-font.min.css") layer(vendor);\n@import url("/public/vendor/anychart/anychart-ui.min.css") layer(vendor);\n',
    },
  });
  assert.ok(failures.some((f) => f.includes('order-drift')));
});

test('el gate detecta layer-drift cuando un adjunto pierde su layer(vendor)', () => {
  const failures = partitionFailures({
    root,
    attachmentOverrides: {
      'jquery-ui': '@import url("/public/vendor/jquery-ui.css");\n',
    },
  });
  assert.ok(failures.some((f) => f.includes('layer-drift')));
});

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
