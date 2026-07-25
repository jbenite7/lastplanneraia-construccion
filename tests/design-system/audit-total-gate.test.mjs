import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';

const read = (path) => readFile(new URL(`../../${path}`, import.meta.url), 'utf8');

test('el baseline declara un tope para el total de hallazgos', async () => {
  const baseline = JSON.parse(await read('docs/design-system/audit-baseline.json'));
  assert.equal(typeof baseline.totalViolations, 'number');
  assert.ok(baseline.totalViolations > 0);
});

test('el audit compara el total contra el baseline', async () => {
  const source = await read('scripts/design-system-audit.mjs');
  assert.match(source, /baseline\.totalViolations/);
  assert.match(source, /total de hallazgos/);
});
