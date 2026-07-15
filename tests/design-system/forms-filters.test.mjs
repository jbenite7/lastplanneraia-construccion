import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';

const readJson = async (file) => JSON.parse(await readFile(
  new URL(`../../docs/design-system/${file}`, import.meta.url), 'utf8',
));

test('always-visible filters are approved and canonical', async () => {
  const homologation = await readJson('homologation.json');
  const approvals = await readJson('family-approvals.json');
  const catalog = await readJson('component-catalog.json');
  const family = homologation.families.find(({ id }) => id === 'forms-filters');
  const approved = family.candidates.filter(({ status }) => status === 'approved');
  const approval = approvals.approvals.find(({ familyId }) => familyId === 'forms-filters');
  const field = catalog.components.find(({ id }) => id === 'field');
  const filter = catalog.components.find(({ id }) => id === 'filter');

  assert.deepEqual(approved.map(({ id }) => id), ['inline-fields']);
  assert.equal(approval.candidateId, 'inline-fields');
  assert.equal(field.maturity, 'stable');
  assert.equal(field.visualApproval.status, 'approved');
  assert.equal(filter.kind, 'canonical');
  assert.equal(filter.maturity, 'candidate');
  assert.equal(filter.visualApproval.status, 'approved');
  assert.ok(filter.api.includes('DesignSystemComponent::filterForm'));
});

test('form controls reserve internal padding and include a Select2 multi-select reference', async () => {
  const css = await readFile('public/css/design-system/components/filter-form.css', 'utf8');
  const view = await readFile('views/design-system/families/forms-filters.php', 'utf8');
  assert.match(css, /\.aia-input,\s*\.aia-select,\s*\.aia-textarea\s*\{[\s\S]*box-sizing:\s*border-box[\s\S]*padding-block:\s*var\(--ds-space-3\)[\s\S]*padding-inline:\s*var\(--ds-space-4\)/);
  assert.match(css, /\.aia-input\[type='file'\]\s*\{[\s\S]*display:\s*flex[\s\S]*align-items:\s*center/);
  assert.match(css, /\.aia-input\[type='file'\]::file-selector-button\s*\{[\s\S]*padding:/);
  assert.match(css, /@layer components\s*\{[\s\S]*\.aia-input,\s*\.aia-select,\s*\.aia-textarea\s*\{[\s\S]*padding-block:\s*var\(--ds-space-3\)[\s\S]*padding-inline:\s*var\(--ds-space-4\)/);
  assert.match(view, /select2-container--multiple/);
  assert.match(view, /data-select2-multi/);
});
