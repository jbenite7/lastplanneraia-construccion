import assert from 'node:assert/strict';
import { readdir, readFile } from 'node:fs/promises';
import test from 'node:test';

const repositoryRoot = new URL('../..', import.meta.url);
const read = (path) => readFile(new URL(`../../${path}`, import.meta.url), 'utf8');
const readJson = async (path) => JSON.parse(await read(path));

const SCHEMAS = [
  'docs/design-system/module-manifest.schema.json',
  'docs/design-system/ui-groups-inventory.schema.json',
  'docs/design-system/runtime-budget.schema.json',
  'docs/design-system/family-approvals.schema.json',
];

for (const schema of SCHEMAS) {
  test(`${schema} no admite el tema linen`, async () => {
    assert.equal(/linen/i.test(await read(schema)), false);
  });
}

test('todos los grupos de UI declaran unicamente el tema dark', async () => {
  const inventory = await readJson('docs/design-system/ui-groups-inventory.json');
  for (const group of inventory.groups) {
    assert.deepEqual(group.themes, ['dark'], `grupo ${group.id} declara ${JSON.stringify(group.themes)}`);
  }
});

test('los contratos de familia y catalogo no mencionan linen', async () => {
  for (const contract of [
    'docs/design-system/family-approvals.json',
    'docs/design-system/homologation.json',
    'docs/design-system/component-catalog.json',
  ]) {
    assert.equal(/linen/i.test(await read(contract)), false, `${contract} menciona linen`);
  }
});

test('ninguna hoja de estilo declara el tema linen', async () => {
  const entries = await readdir(new URL('public/css', repositoryRoot), {
    withFileTypes: true, recursive: true,
  });
  const offenders = [];
  for (const entry of entries) {
    if (!entry.isFile() || !entry.name.endsWith('.css')) continue;
    const file = `${entry.parentPath ?? entry.path}/${entry.name}`;
    if (/linen/i.test(await readFile(file, 'utf8'))) offenders.push(entry.name);
  }
  assert.deepEqual(offenders, [], `hojas con linen: ${offenders.join(', ')}`);
});

test('el runtime de tema no ofrece conmutacion', async () => {
  const source = await read('public/js/modules/aia_ui/theme.js');
  for (const symbol of ['toggleTheme', 'bindThemeSwitches', 'setTheme']) {
    assert.equal(source.includes(symbol), false, `theme.js aun expone ${symbol}`);
  }
});

test('ninguna vista incluye el conmutador de tema', async () => {
  const entries = await readdir(new URL('views', repositoryRoot), {
    withFileTypes: true, recursive: true,
  });
  const offenders = [];
  for (const entry of entries) {
    if (!entry.isFile() || !entry.name.endsWith('.php')) continue;
    const file = `${entry.parentPath ?? entry.path}/${entry.name}`;
    if (/aia-theme-switch|auth-theme-switch/.test(await readFile(file, 'utf8'))) {
      offenders.push(entry.name);
    }
  }
  assert.deepEqual(offenders, [], `vistas con conmutador: ${offenders.join(', ')}`);
});
