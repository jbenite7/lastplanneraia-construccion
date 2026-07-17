import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';

const read = (path) => readFile(new URL(`../../${path}`, import.meta.url), 'utf8');

const expected = new Map([
  ['project-selector', ['P1', 'page-structure', 'control-room']],
  ['auth-credentials', ['P1', 'forms-filters', 'contract-explorer']],
  ['context-week', ['P1', 'shell-navigation', 'control-room']],
  ['editable-grid', ['P1', 'vendor-adapters', 'object-workbench']],
  ['datatables-legacy', ['P1', 'vendor-adapters', 'contract-explorer']],
  ['notifications-center', ['P1', 'shell-navigation', 'control-room']],
  ['lps-context-drawer', ['P1', 'overlays', 'contract-explorer']],
  ['semi-auto-review', ['P1', 'actions', 'object-workbench']],
  ['admin-operations', ['P1', 'shell-navigation', 'control-room']],
  ['bi-runtime-drilldown', ['P1', 'bi-primitives', 'control-room']],
  ['tom-select-advanced', ['P2', 'vendor-adapters', 'object-workbench']],
  ['enriched-datepicker', ['P2', 'forms-filters', 'object-workbench']],
]);

test('operational fixture contract covers every approved P1 and P2 object', async () => {
  const contract = JSON.parse(await read('docs/design-system/operational-fixtures.json'));
  assert.equal(contract.schemaVersion, 1);
  assert.equal(contract.fixtures.length, expected.size);
  for (const fixture of contract.fixtures) {
    assert.deepEqual(
      [fixture.priority, fixture.family, fixture.topology],
      expected.get(fixture.id),
      `unexpected contract for ${fixture.id}`,
    );
    assert.ok(fixture.consumers.length > 0, `${fixture.id} needs real consumers`);
    assert.ok(fixture.states.includes('default'), `${fixture.id} needs a default state`);
    assert.ok(fixture.states.includes('error'), `${fixture.id} needs an error state`);
  }
  assert.deepEqual(new Set(contract.fixtures.map(({ id }) => id)), new Set(expected.keys()));
});

test('the laboratory renders operational fixtures through one shared state contract', async () => {
  const [view, partial, script] = await Promise.all([
    read('views/design-system/lab.view.php'),
    read('views/design-system/operational-fixtures.php'),
    read('public/js/modules/aia_ui/design_system_lab.js'),
  ]);
  assert.match(view, /operational-fixtures\.php/);
  assert.match(partial, /data-operational-fixture/);
  assert.match(partial, /aria-pressed/);
  assert.match(partial, /role="status"/);
  assert.match(script, /data-contract-state-action/);
  assert.match(script, /data-operational-state-output/);
});

test('every operational specimen is semantic source rather than a rasterized mock', async () => {
  const source = await read('views/design-system/operational-fixtures.php');
  for (const id of expected.keys()) assert.match(source, new RegExp(`case '${id}'`));
  assert.doesNotMatch(source, /<img|background-image|data:image\//i);
  assert.match(source, /<table/);
  assert.match(source, /<form/);
  assert.match(source, /<ol/);
});
