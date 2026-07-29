import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { join } from 'node:path';
import { fileURLToPath } from 'node:url';
import test from 'node:test';
import { partitionFailures } from '../../scripts/design-system-entrypoint-partition.mjs';
import {
  coherenceFailures,
  manifestIdentityFailures,
  manifestVendorFailures,
} from '../../scripts/design-system-entrypoint-partition.mjs';

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

// El gate anterior solo mira los manifiestos que una vista usa hoy. Por ese
// hueco entraron tres vendors fantasma que nadie veía porque sus módulos siguen
// en render()/renderLaboratory(): se volverían un fallback silencioso al
// agregador el día que se migren. Estos tres cubren el directorio completo.
test('todo vendor de TODO manifiesto resuelve contra el registro de PHP', () => {
  assert.deepEqual(manifestVendorFailures({ root }), []);
});

test('un vendor fantasma en un manifiesto NO cableado a renderForModule falla', () => {
  const failures = manifestVendorFailures({
    root,
    manifestsOverride: [{
      file: 'docs/design-system/manifests/nunca-cableado.json',
      manifest: { moduleId: 'nunca-cableado', vendors: ['bootstrap', 'vendor-fantasma'] },
    }],
  });
  assert.deepEqual(failures, [
    'unresolvable-vendor: "vendor-fantasma" en docs/design-system/manifests/nunca-cableado.json',
  ]);
});

// `moduleVendors()` resuelve `manifests/{moduleId}.json`: un manifiesto cuyo
// nombre de archivo no coincide con su moduleId es un fallback silencioso al
// agregador esperando a que alguien cablee la vista.
test('el nombre de archivo de todo manifiesto de módulo coincide con su moduleId', () => {
  assert.deepEqual(manifestIdentityFailures({ root }), []);
});

test('un manifiesto cuyo moduleId no coincide con su archivo falla', () => {
  const failures = manifestIdentityFailures({
    root,
    manifestsOverride: [{
      file: 'docs/design-system/manifests/laboratorio.json',
      manifest: { moduleId: 'design-system-laboratorio', vendors: [] },
    }],
  });
  assert.equal(failures.length, 1);
  assert.match(failures[0], /manifest-id-mismatch: docs\/design-system\/manifests\/laboratorio\.json/);
  assert.match(failures[0], /design-system-laboratorio/);
});

test('los archivos sin moduleId no disparan el gate de identidad', () => {
  assert.deepEqual(
    manifestIdentityFailures({
      root,
      manifestsOverride: [
        { file: 'docs/design-system/manifests/inventory.json', manifest: { manifests: [] } },
        { file: 'docs/design-system/manifests/goal-provenance.json', manifest: { goals: [] } },
        { file: 'docs/design-system/manifests/roto.json', manifest: null },
      ],
    }),
    [],
  );
});

test('los manifiestos sin moduleId (inventory, goal-provenance) no son de módulo', () => {
  const failures = manifestVendorFailures({
    root,
    manifestsOverride: [
      { file: 'docs/design-system/manifests/inventory.json', manifest: { manifests: [] } },
      { file: 'docs/design-system/manifests/roto.json', manifest: null },
      { file: 'docs/design-system/manifests/sin-vendors.json', manifest: { moduleId: 'x' } },
    ],
  });
  assert.deepEqual(failures, [
    'manifest-unparseable: docs/design-system/manifests/roto.json',
    'manifest-vendors-missing: docs/design-system/manifests/sin-vendors.json',
  ]);
});
