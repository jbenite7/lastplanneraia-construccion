import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';

const readJson = async (file) => JSON.parse(await readFile(file, 'utf8'));

test('shell navigation approval freezes the hybrid 1200px contract', async () => {
  const homologation = await readJson('docs/design-system/homologation.json');
  const approvals = await readJson('docs/design-system/family-approvals.json');
  const decisions = await readFile('docs/design-system/decisions.md', 'utf8');
  const family = homologation.families.find(({ id }) => id === 'shell-navigation');
  assert.deepEqual(family.candidates, [{
    id: 'adaptive-shell',
    intent: 'Contexto visible desde 1200 px y drawer táctil en anchos menores',
    status: 'approved',
  }]);
  const approval = approvals.approvals.find(({ familyId }) => familyId === family.id);
  assert.equal(approval.candidateId, 'adaptive-shell');
  assert.deepEqual(approval.themes, ['linen', 'dark']);
  assert.deepEqual(approval.viewports, family.viewports);
  assert.match(decisions, /DS-011.*1200 px.*approved/);
});

test('the global navigation bar keeps padding on all four sides', async () => {
  const css = await readFile('public/css/design-system/components/navigation.css', 'utf8');
  const component = await readFile('src/View/Components/DesignSystemComponent.php', 'utf8');
  const block = css.match(/\.aia-navigation__global\s*\{([^}]+)\}/)?.[1] ?? '';
  assert.match(block, /padding:\s*var\(--ds-space-4\)/);
  assert.match(block, /grid-template-columns:/);
  assert.match(component, /aia-navigation__brand/);
  assert.match(component, /aia-navigation__context/);
  assert.match(css, /\.aia-navigation__context\s*\{[\s\S]*min-width:\s*0[\s\S]*text-overflow:\s*ellipsis/);
  assert.match(css, /@layer components\s*\{[\s\S]*\.aia-navigation__global\s*\{[\s\S]*padding:\s*var\(--ds-space-4\)/);
});
