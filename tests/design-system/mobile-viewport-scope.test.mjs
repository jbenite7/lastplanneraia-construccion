import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';

// Candado de alcance de viewports. Sustituye al candado de retirada de DS-031:
// aquella prohibia el ancho movil; esta exige que todo viewport declarado venga
// con evidencia que lo sostenga. La intencion protegida es la misma —que no
// exista viewport declarado sin golden— y sobrevive a que F2 empiece a declarar
// 390x844 familia por familia.
const read = (path) => readFile(new URL(`../../${path}`, import.meta.url), 'utf8');
const readJson = async (path) => JSON.parse(await read(path));

const REQUIRED_VIEWPORTS = ['1180x820', '1440x900'];
const SUPPORTED_VIEWPORTS = [...REQUIRED_VIEWPORTS, '390x844'];

const SCHEMAS = [
  'docs/design-system/runtime-budget.schema.json',
  'docs/design-system/family-approvals.schema.json',
];

for (const schema of SCHEMAS) {
  test(`${schema} admite el viewport movil`, async () => {
    assert.ok(
      (await read(schema)).includes('390x844'),
      `${schema} volvio a cerrar el viewport movil`,
    );
  });
}

test('toda familia homologada cubre los viewports requeridos y ninguno ajeno', async () => {
  const homologation = await readJson('docs/design-system/homologation.json');
  for (const family of homologation.families) {
    for (const viewport of REQUIRED_VIEWPORTS) {
      assert.ok(
        family.viewports.includes(viewport),
        `familia ${family.id} no cubre ${viewport}`,
      );
    }
    for (const viewport of family.viewports) {
      assert.ok(
        SUPPORTED_VIEWPORTS.includes(viewport),
        `familia ${family.id} declara el viewport no soportado ${viewport}`,
      );
    }
  }
});

test('toda aprobacion de familia cubre los viewports requeridos y ninguno ajeno', async () => {
  const { approvals } = await readJson('docs/design-system/family-approvals.json');
  for (const approval of approvals) {
    const label = `${approval.familyId}/${approval.candidateId}`;
    for (const viewport of REQUIRED_VIEWPORTS) {
      assert.ok(approval.viewports.includes(viewport), `${label} no cubre ${viewport}`);
    }
    for (const viewport of approval.viewports) {
      assert.ok(
        SUPPORTED_VIEWPORTS.includes(viewport),
        `${label} declara el viewport no soportado ${viewport}`,
      );
    }
  }
});

test('todo escenario declarado en un manifiesto trae evidencia', async () => {
  const inventory = await readJson('docs/design-system/manifests/inventory.json');
  assert.ok(inventory.manifests.length >= 1);
  for (const file of inventory.manifests) {
    const manifest = await readJson(`docs/design-system/manifests/${file}`);
    for (const scenario of manifest.scenarios ?? []) {
      const label = `${manifest.moduleId}/${scenario.id}`;
      assert.ok(scenario.golden, `${label} declara un escenario sin golden`);
      assert.match(scenario.sha256 ?? '', /^[a-f0-9]{64}$/, `${label} sin sha256`);
    }
  }
});

test('el gate de contratos distingue soportado de requerido', async () => {
  const source = await read('scripts/design-system-contracts.mjs');
  assert.match(source, /const SUPPORTED_VIEWPORTS = new Set\(\[/);
  assert.match(source, /const REQUIRED_VIEWPORTS = \['1180x820', '1440x900'\]/);
});
