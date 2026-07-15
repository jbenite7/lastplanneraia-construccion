import assert from 'node:assert/strict';
import { existsSync } from 'node:fs';
import { readFile } from 'node:fs/promises';
import test from 'node:test';

const readJson = async (file) => JSON.parse(await readFile(
  new URL(`../../docs/design-system/${file}`, import.meta.url), 'utf8',
));
const read = (file) => readFile(new URL(`../../${file}`, import.meta.url), 'utf8');

test('central vendor skins are approved without changing frozen vendors', async () => {
  const homologation = await readJson('homologation.json');
  const approvals = await readJson('family-approvals.json');
  const catalog = await readJson('component-catalog.json');
  const family = homologation.families.find(({ id }) => id === 'vendor-adapters');
  const approval = approvals.approvals.find(({ familyId }) => familyId === 'vendor-adapters');

  assert.deepEqual(family.candidates.filter(({ status }) => status === 'approved').map(({ id }) => id), ['canonical-skin']);
  assert.equal(approval.candidateId, 'canonical-skin');
  const catalogHandsontable = catalog.components.find(({ id }) => id === 'handsontable-adapter');
  const select = catalog.components.find(({ id }) => id === 'select-adapter');
  assert.equal(catalogHandsontable.maturity, 'stable');
  assert.equal(catalogHandsontable.visualApproval.status, 'approved');
  assert.equal(select.maturity, 'candidate');
  assert.equal(select.visualApproval.status, 'approved');
  for (const file of ['handsontable.css', 'select2.css', 'sweetalert2.css']) {
    assert.equal(existsSync(new URL(`../../public/css/design-system/adapters/${file}`, import.meta.url)), true, file);
  }
});

test('vendor specimens expose theme-safe surfaces, options and control padding', async () => {
  const handsontable = await read('public/css/design-system/adapters/handsontable.css');
  const select2 = await read('public/css/design-system/adapters/select2.css');
  const sweetalert2 = await read('public/css/design-system/adapters/sweetalert2.css');
  const specimen = await read('views/design-system/families/vendor-adapters.php');
  assert.match(handsontable, /^@layer reset\s*\{/);
  assert.match(handsontable, /\.handsontable tbody tr[\s\S]*display:\s*table-row !important/);
  assert.match(select2, /select2-selection__rendered[\s\S]*padding-inline:\s*var\(--ds-space-4\)/);
  assert.match(sweetalert2, /\.aia-glass-confirm-btn[\s\S]*background:\s*var\(--ds-active-action-primary\)/);
  assert.ok((specimen.match(/role="option"/g) ?? []).length >= 3);
});
