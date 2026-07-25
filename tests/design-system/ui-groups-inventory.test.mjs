import assert from 'node:assert/strict';
import { existsSync } from 'node:fs';
import { readFile, readdir } from 'node:fs/promises';
import { fileURLToPath } from 'node:url';
import test from 'node:test';

const read = (path) => readFile(new URL(`../../${path}`, import.meta.url), 'utf8');
const repoPath = (path) => fileURLToPath(new URL(`../../${path}`, import.meta.url));

test('every application UI group is governed for dark', async () => {
  const inventory = JSON.parse(await read('docs/design-system/ui-groups-inventory.json'));
  const catalog = JSON.parse(await read('docs/design-system/component-catalog.json'));
  const ids = new Set(catalog.components.map(({ id }) => id));
  assert.ok(inventory.groups.length >= 40, 'inventory must cover the complete application taxonomy');
  for (const group of inventory.groups) {
    assert.deepEqual(group.themes, ['dark'], `${group.id}: incomplete themes`);
    assert.ok(group.sources.length > 0, `${group.id}: missing source evidence`);
    group.sources.forEach((source) => assert.ok(existsSync(repoPath(source)), `${group.id}: missing source file ${source}`));
    assert.ok(group.catalogIds.length > 0, `${group.id}: unmapped group`);
    assert.ok(group.styleApi.length > 0, `${group.id}: missing canonical style API`);
    group.catalogIds.forEach((id) => assert.ok(ids.has(id), `${group.id}: unknown ${id}`));
    assert.match(group.labSelector, /^\[data-ui-group=/, `${group.id}: missing lab specimen`);
  }
});

test('every inventory style API exists in the governed CSS', async () => {
  const inventory = JSON.parse(await read('docs/design-system/ui-groups-inventory.json'));
  const cssFiles = await readdir(new URL('../../public/css/design-system/', import.meta.url), { recursive: true });
  const css = (await Promise.all(cssFiles.filter((file) => file.endsWith('.css'))
    .map((file) => read(`public/css/design-system/${file}`)))).join('\n')
    + await read('public/css/aia-design-system.css') + await read('public/css/tokens.css');
  for (const group of inventory.groups) {
    for (const api of group.styleApi) {
      const needle = api.endsWith('*') ? api.slice(0, -1) : api;
      assert.ok(css.includes(needle), `${group.id}: undefined style API ${api}`);
    }
  }
});
