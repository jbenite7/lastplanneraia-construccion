import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';

const readJson = async (file) => JSON.parse(await readFile(
  new URL(`../../docs/design-system/${file}`, import.meta.url), 'utf8',
));
const readCss = (file) => readFile(new URL(`../../public/css/${file}`, import.meta.url), 'utf8');

test('responsive table and cards are approved from one canonical component', async () => {
  const homologation = await readJson('homologation.json');
  const approvals = await readJson('family-approvals.json');
  const catalog = await readJson('component-catalog.json');
  const family = homologation.families.find(({ id }) => id === 'data-display');
  const approval = approvals.approvals.find(({ familyId }) => familyId === 'data-display');
  const card = catalog.components.find(({ id }) => id === 'card');
  const table = catalog.components.find(({ id }) => id === 'table-shell');

  assert.deepEqual(family.candidates.filter(({ status }) => status === 'approved').map(({ id }) => id), ['responsive-shell']);
  assert.equal(approval.candidateId, 'responsive-shell');
  assert.equal(card.maturity, 'stable');
  assert.equal(card.visualApproval.status, 'approved');
  assert.equal(table.maturity, 'stable');
  assert.equal(table.visualApproval.status, 'approved');
  assert.ok(table.api.includes('DesignSystemComponent::dataDisplay'));
});

test('Programa General cell metadata inherits the semantic state contrast', async () => {
  const css = await readCss('design-system/adapters/programa-general-handsontable.css');
  assert.match(
    css,
    /@layer legacy-overrides\s*\{[\s\S]*?\.pg-page #hot-container \.pg-cell-meta\)\s*\{[^}]*color:\s*inherit\s*;/,
  );
  assert.doesNotMatch(css, /\.pg-cell-meta\)\s*\{[^}]*!important;/s);
});
