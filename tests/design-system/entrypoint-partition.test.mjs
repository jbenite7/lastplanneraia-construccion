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
