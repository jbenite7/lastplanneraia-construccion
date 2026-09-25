import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';

const source = await readFile(
  new URL('../../e2e/tests/workflows/pg-interactions.spec.mjs', import.meta.url),
  'utf8',
);

test('pilot E2E uses explicit role credentials', () => {
  assert.match(source, /test\.A/);
  assert.match(source, /test\.R/);
  assert.match(source, /test\.C/);
  assert.match(source, /test\.V/);
});

test('pilot persistence asserts UI, API, DB and exact restoration', () => {
  assert.match(source, /guardarUnidad/);
  assert.match(source, /guardarDrawerPg/);
  assert.match(source, /page\.reload/);
  assert.match(source, /leerCampoFilaPg/);
  assert.match(source, /beforeFingerprint/);
  assert.match(source, /toBe\(beforeFingerprint\)/);
  assert.match(source, /testValue/);
  assert.match(source, /API.*target\.testValue/s);
  assert.match(source, /DB.*target\.testValue/s);
});

test('pilot persistence never writes a synthetic unit outside the domain', () => {
  assert.doesNotMatch(source, /E2E_PC_TEST/);
  assert.match(source, /PROJECT_PC[\s\S]*?escogerActividadConUnidad/);
  assert.match(source, /PROJECT_PC[\s\S]*?unidad='\$\{target\.testValue\}'/s);
});

test('pilot role contract verifies a manipulated write is rejected', () => {
  assert.match(source, /postFormJson/);
  assert.match(source, /\[403, 422\]/);
  assert.match(source, /Viewer drawer must not permit editing/);
  assert.match(source, /getByRole\('alert'\)/);
});

test('pilot persistence fails closed without isolated mutation consent', () => {
  assert.match(source, /E2E_REQUIRE_ISOLATED_DB/);
  assert.match(source, /E2E_ALLOW_DB_MUTATION/);
  assert.match(source, /design-system-ci/);
  assert.match(source, /Da Porto.*required/s);
});
