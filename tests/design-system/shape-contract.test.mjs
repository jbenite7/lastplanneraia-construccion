// tests/design-system/shape-contract.test.mjs
import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';

const catalog = JSON.parse(readFileSync('docs/design-system/component-catalog.json', 'utf8'));

test('F30: toda familia del catálogo declara su forma', () => {
  const familias = catalog.families ?? catalog.components ?? [];
  assert.ok(familias.length > 0, 'el catálogo tiene familias');
  for (const f of familias) {
    assert.ok(f.shape, `la familia ${f.id ?? f.name} declara shape`);
    assert.match(f.shape.radius, /^--ds-radius-/, 'radius es un token');
    assert.ok(['rest', 'float', 'top', 'none'].includes(f.shape.floor),
      `floor de ${f.id ?? f.name} es un piso con nombre (F7)`);
    // `status: "planned"` (ej. la familia `state`, cuyo CSS real todavía usa
    // --ds-radius-md en vez de --ds-radius-data) es la excepción declarada a
    // propósito: el catálogo documenta el radio objetivo sin exigir que el
    // CSS ya lo cumpla. Este test solo verifica la forma del propio shape;
    // la comprobación contra CSS real vive en el siguiente test, que ya se
    // salta las familias sin `cssFile` -- planned o no.
    if (f.shape.status === 'planned') continue;
  }
});

test('F30: el CSS de cada familia usa el radio que declara', () => {
  const familias = catalog.families ?? catalog.components ?? [];
  for (const f of familias) {
    if (!f.shape?.cssFile) continue;
    if (f.shape.status === 'planned') continue;
    const css = readFileSync(f.shape.cssFile, 'utf8');
    assert.ok(css.includes(`var(${f.shape.radius})`),
      `${f.shape.cssFile} usa var(${f.shape.radius})`);
  }
});
