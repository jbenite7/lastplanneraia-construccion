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
  assert.match(source, /querySelector\((['"])\.ht_master > \.wtHolder\1\)/);
  assert.match(source, /region\.tabIndex = 0/);
  assert.match(source, /region\.setAttribute\((['"])role\1, (['"])region\2\)/);
  assert.match(source, /(['"])aria-label\1, (['"])Tabla desplazable\2/);
});

test('canonical filter, help and warning icons use distinct line glyphs', async () => {
  const [component, css] = await Promise.all([
    readFile(new URL('../../src/View/Components/DesignSystemComponent.php', import.meta.url), 'utf8'),
    readFile(new URL('../../public/css/design-system/components/primitives.css', import.meta.url), 'utf8'),
  ]);
  const glyphs = ['filter', 'help', 'warning'].map((name) => (
    component.match(new RegExp(`'${name}'\\s*=>\\s*'([^']+)'`))?.[1]
  ));

  assert.ok(glyphs.every(Boolean), 'every governed icon needs an explicit glyph');
  assert.equal(new Set(glyphs).size, glyphs.length, 'governed glyphs must be visually distinct');
  assert.match(component, /<svg class="aia-icon__glyph"/);
  assert.match(css, /\.aia-icon__glyph\s*{/);
  assert.doesNotMatch(css, /\.aia-icon::before/);
});
