import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';

test('the required Sprint 00 primitives are visually approved with explicit maturity', async () => {
  const catalog = JSON.parse(await readFile(
    new URL('../../docs/design-system/component-catalog.json', import.meta.url), 'utf8',
  ));
  const required = [
    'shell', 'navigation', 'section', 'icon', 'search', 'pagination',
    'progress', 'live-region', 'menu', 'popover', 'toast-confirm', 'bi-tooltip',
  ];
  for (const id of required) {
    const component = catalog.components.find((candidate) => candidate.id === id);
    assert.ok(component, id);
    assert.notEqual(component.kind, 'missing', id);
    assert.equal(component.visualApproval.status, 'approved', id);
    assert.ok(['stable', 'candidate'].includes(component.maturity), id);
  }
});

test('the shared component runtime makes vendor scroll regions keyboard reachable', async () => {
  const source = await readFile(
    new URL('../../public/js/modules/aia_ui/components.js', import.meta.url), 'utf8',
  );
  assert.match(source, /function ensureScrollableRegions\(root\)/);
  assert.match(source, /querySelector\('\.ht_master > \.wtHolder'\)/);
  assert.match(source, /region\.tabIndex = 0/);
  assert.match(source, /region\.setAttribute\('role', 'region'\)/);
  assert.match(source, /aria-label', 'Tabla desplazable'/);
});
