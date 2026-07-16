import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';

const hot = readFileSync('public/js/modules/programa_general/hot.js', 'utf8');
const view = readFileSync('views/programa-general/programa_general.view.php', 'utf8');

test('first entry does not schedule the navigation-return refresh', () => {
  const decision = hot.match(/function shouldAutoUpdateOnEntry\(\) \{[\s\S]*?\n  \}/)?.[0] ?? '';

  assert.match(decision, /flag === '1'[\s\S]*?return true;/);
  assert.match(decision, /return false;\s*\n\s*\}$/);
});

test('the first paint does not expose the removed legacy page loader', () => {
  const biAccess = view.indexOf('/js/modules/bi-access.js');
  const sharedLoader = view.indexOf('/js/cargarDatosGeneralesPagina2.js');

  assert.notEqual(biAccess, -1);
  assert.equal(sharedLoader, -1);
});
