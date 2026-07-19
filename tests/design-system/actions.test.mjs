import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';

const readJson = async (file) => JSON.parse(await readFile(
  new URL(`../../docs/design-system/${file}`, import.meta.url), 'utf8',
));

test('the integrated action group is approved and canonical', async () => {
  const homologation = await readJson('homologation.json');
  const approvals = await readJson('family-approvals.json');
  const catalog = await readJson('component-catalog.json');
  const family = homologation.families.find(({ id }) => id === 'actions');
  const approved = family.candidates.filter(({ status }) => status === 'approved');
  const approval = approvals.approvals.find(({ familyId }) => familyId === 'actions');
  const toolbar = catalog.components.find(({ id }) => id === 'toolbar');
  const button = catalog.components.find(({ id }) => id === 'button');

  assert.deepEqual(approved.map(({ id }) => id), ['solid-outline']);
  assert.equal(approval.candidateId, 'solid-outline');
  assert.equal(toolbar.maturity, 'stable');
  assert.equal(toolbar.visualApproval.status, 'approved');
  assert.equal(button.maturity, 'stable');
  assert.equal(button.visualApproval.status, 'approved');
  assert.ok(toolbar.api.includes('DesignSystemComponent::actionGroup'));
});

test('the active action candidate applies theme color changes without chromatic interpolation', async () => {
  const homologation = await readJson('homologation.json');
  const family = homologation.families.find(({ id }) => id === 'actions');
  const active = family.candidates.find(({ id }) => id === family.activeCandidate);
  const specimen = await readFile(
    new URL('../../views/design-system/families/actions.php', import.meta.url), 'utf8',
  );
  const css = await readFile(
    new URL('../../public/css/design-system/core.css', import.meta.url), 'utf8',
  );
  const labCss = await readFile(
    new URL('../../public/css/design-system/lab.css', import.meta.url), 'utf8',
  );

  assert.equal(family.activeCandidate, 'theme-adaptive-primary');
  assert.equal(active?.status, 'candidate');
  assert.match(specimen, /data-action-candidate="<\?= htmlspecialchars\(\$activeCandidateId/);
  assert.doesNotMatch(specimen, /familyCandidateEyebrow|Patrón aprobado|Patrón en revisión/);
  const buttonRule = css.match(/\.aia-btn\s*\{([\s\S]*?)\n\s*\}/)?.[1] ?? '';
  assert.match(buttonRule, /transition:\s*box-shadow var\(--ds-motion-fast\),\s*transform var\(--ds-motion-fast\)/);
  assert.doesNotMatch(buttonRule, /(?:background|border-color|color)\s+var\(--ds-motion-fast\)/);
  assert.match(labCss, /\.ds-actions-comparison\s*\{[^}]*grid-template-columns:\s*minmax\(0, 1fr\)/s);
});
