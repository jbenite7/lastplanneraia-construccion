import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';

const read = (path) => readFile(new URL(`../../${path}`, import.meta.url), 'utf8');
const readJson = async (path) => JSON.parse(await read(path));

const DESKTOP_VIEWPORTS = ['1180x820', '1440x900'];

const SCHEMAS = [
  'docs/design-system/runtime-budget.schema.json',
  'docs/design-system/family-approvals.schema.json',
];

for (const schema of SCHEMAS) {
  test(`${schema} no admite el viewport 390x844`, async () => {
    assert.equal((await read(schema)).includes('390x844'), false);
  });
}

test('todas las familias homologadas declaran solo los viewports desktop', async () => {
  const homologation = await readJson('docs/design-system/homologation.json');
  for (const family of homologation.families) {
    assert.deepEqual(
      family.viewports, DESKTOP_VIEWPORTS,
      `familia ${family.id} declara ${JSON.stringify(family.viewports)}`,
    );
  }
});

test('todas las aprobaciones de familia cubren solo los viewports desktop', async () => {
  const { approvals } = await readJson('docs/design-system/family-approvals.json');
  for (const approval of approvals) {
    assert.deepEqual(
      approval.viewports, DESKTOP_VIEWPORTS,
      `aprobacion ${approval.familyId}/${approval.candidateId} declara ` +
      `${JSON.stringify(approval.viewports)}`,
    );
  }
});

test('ningun manifiesto de modulo declara un escenario movil', async () => {
  const inventory = await readJson('docs/design-system/manifests/inventory.json');
  assert.ok(inventory.manifests.length >= 1);
  for (const file of inventory.manifests) {
    const manifest = await readJson(`docs/design-system/manifests/${file}`);
    for (const scenario of manifest.scenarios ?? []) {
      assert.ok(
        scenario.viewport.width >= 1180,
        `${manifest.moduleId}/${scenario.id} usa ${scenario.viewport.width}px`,
      );
    }
  }
});

test('los gates no iteran sobre el viewport movil', async () => {
  for (const script of [
    'scripts/design-system-contracts.mjs',
    'scripts/design-system-runtime-budget.mjs',
    'scripts/design-system-runtime-budget-provenance.mjs',
  ]) {
    const source = await read(script);
    const offenders = source
      .split('\n')
      .filter((line) => line.includes('390x844') && !line.trimStart().startsWith('//'));
    assert.deepEqual(offenders, [], `${script} aun usa 390x844`);
  }
});

test('el contrato de migracion no exige un viewport retirado', async () => {
  const contract = await read('docs/design-system/contracts/module-migration.md');
  assert.equal(contract.includes('390x844'), false);
});
