import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { join } from 'node:path';
import { fileURLToPath } from 'node:url';
import test from 'node:test';
import { partitionFailures } from '../../scripts/design-system-entrypoint-partition.mjs';
import {
  coherenceFailures,
  manifestIdentityFailures,
  manifestUnderDeclarationFailures,
  manifestVendorFailures,
  vendorViewFootprints,
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

// Gate espejo: `manifestVendorFailures` caza el vendor declarado de MÁS; éste
// caza el declarado de MENOS, que es la dirección que rompe el contrato
// «siempre cargar de más, nunca de menos» de renderForModule().
test('ninguna vista cableada carga un vendor que su manifiesto no declara', () => {
  assert.deepEqual(manifestUnderDeclarationFailures({ root }), []);
});

test('el gate caza el vendor que la vista carga y el manifiesto calla', () => {
  const failures = manifestUnderDeclarationFailures({
    root,
    viewsOverride: [{
      file: 'views/fake/fake.view.php',
      content: '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">'
        + "<?= DesignSystemHeadComponent::renderForModule('fake') ?>",
    }],
    manifestsOverride: [{
      file: 'docs/design-system/manifests/fake.json',
      manifest: { moduleId: 'fake', vendors: ['bootstrap'] },
    }],
  });
  assert.equal(failures.length, 1);
  assert.match(failures[0], /^undeclared-vendor: adminlte lo carga views\/fake\/fake\.view\.php/);
  assert.match(failures[0], /docs\/design-system\/manifests\/fake\.json no lo declara/);
});

// Declarar de menos un CORE_VENDOR no pierde nada: renderForModule() no emite
// adjunto por ellos y su CSS ya viaja incondicional dentro de core.css. Hoy es
// el caso real de `jquery` en las tres vistas de auth.
test('un CORE_VENDOR sin declarar no dispara el gate espejo', () => {
  const failures = manifestUnderDeclarationFailures({
    root,
    viewsOverride: [{
      file: 'views/fake/fake.view.php',
      content: '<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>'
        + "<?= DesignSystemHeadComponent::renderForModule('fake') ?>",
    }],
    manifestsOverride: [{
      file: 'docs/design-system/manifests/fake.json',
      manifest: { moduleId: 'fake', vendors: [] },
    }],
  });
  assert.deepEqual(failures, []);
});

test('las huellas de vendor cubren asset local y CDN', () => {
  const footprints = vendorViewFootprints(root);
  assert.ok(footprints.toastr.includes('/vendor/toastr.min.css'));
  // adminlte tiene las dos: la huella de CDN se conserva aunque ninguna vista
  // la use ya (DS-006, 2026-08-06) para cazar una reintroducción del <link>.
  assert.deepEqual(
    footprints.adminlte,
    ['admin-lte@', '/vendor/admin-lte/css/adminlte.min.css'],
  );
});

// Contraejemplo medido: mover a VIEW_OWNED_VENDORS un vendor que SÍ tiene
// adjunto dejaba los tres gates en verde mientras renderForModule() dejaba de
// emitir su attach-*.css.
test('un VIEW_OWNED_VENDORS con adjunto propio falla', () => {
  const failures = manifestVendorFailures({
    root,
    registryOverride: {
      coreVendors: ['bootstrap'],
      viewOwnedVendors: ['select2'],
      attachments: [{ vendor: 'select2', url: '/css/design-system/entrypoints/attach-select2.css' }],
    },
    manifestsOverride: [],
  });
  assert.deepEqual(failures, [
    'view-owned-with-attachment: select2 está en VIEW_OWNED_VENDORS pero tiene adjunto; '
    + 'renderForModule() dejaría de emitir su adaptador oscuro',
  ]);
});

test('un VIEW_OWNED_VENDORS que ninguna vista enlaza falla', () => {
  const failures = manifestVendorFailures({
    root,
    registryOverride: {
      coreVendors: ['bootstrap'],
      viewOwnedVendors: ['toastr'],
      attachments: [{ vendor: 'jquery-ui', url: '/css/design-system/entrypoints/attach-jquery-ui.css' }],
    },
    viewsOverride: [{ file: 'views/fake.view.php', content: '<link rel="stylesheet" href="/css/tokens.css">' }],
    manifestsOverride: [],
  });
  assert.equal(failures.length, 1);
  assert.match(failures[0], /^view-owned-without-link: toastr/);
});

test('los VIEW_OWNED_VENDORS reales (toastr) cumplen el criterio', () => {
  assert.deepEqual(manifestVendorFailures({ root }), []);
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
