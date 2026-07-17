import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';

const readJson = async (file) => JSON.parse(await readFile(
  new URL(`../../docs/design-system/${file}`, import.meta.url), 'utf8',
));

test('responsive modal and drawer are one approved canonical dialog', async () => {
  const homologation = await readJson('homologation.json');
  const approvals = await readJson('family-approvals.json');
  const catalog = await readJson('component-catalog.json');
  const family = homologation.families.find(({ id }) => id === 'overlays');
  const approval = approvals.approvals.find(({ familyId }) => familyId === 'overlays');
  const overlay = catalog.components.find(({ id }) => id === 'overlay');

  assert.deepEqual(family.candidates.filter(({ status }) => status === 'approved').map(({ id }) => id), ['modal-drawer']);
  assert.equal(approval.candidateId, 'modal-drawer');
  assert.equal(overlay.maturity, 'stable');
  assert.equal(overlay.visualApproval.status, 'approved');
  assert.ok(overlay.api.includes('DesignSystemComponent::dialog'));
  assert.ok(overlay.api.includes('window.AiaComponents.init'));
});
