import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';

const readJson = async (file) => JSON.parse(await readFile(file, 'utf8'));

test('shell navigation registers the sidebar candidate as approved', async () => {
  const homologation = await readJson('docs/design-system/homologation.json');
  const approvals = await readJson('docs/design-system/family-approvals.json');
  const decisions = await readFile('docs/design-system/decisions.md', 'utf8');
  const fixture = await readFile('views/design-system/families/shell-navigation.php', 'utf8');
  const family = homologation.families.find(({ id }) => id === 'shell-navigation');
  assert.equal(family.activeCandidate, 'sidebar-shell');
  assert.deepEqual(family.candidates, [
    {
      id: 'adaptive-shell',
      intent: 'Contexto visible desde 1200 px y drawer táctil en anchos menores',
      status: 'approved',
    },
    {
      id: 'sidebar-shell',
      intent: 'Rail persistente desktop con contexto de proyecto, grupos operativos y colapso accesible',
      status: 'approved',
    },
  ]);
  const adaptiveApproval = approvals.approvals.find(({ familyId, candidateId }) => familyId === family.id && candidateId === 'adaptive-shell');
  assert.equal(adaptiveApproval.candidateId, 'adaptive-shell');
  assert.deepEqual(adaptiveApproval.themes, ['linen', 'dark']);
  assert.deepEqual(adaptiveApproval.viewports, family.viewports);
  const sidebarApproval = approvals.approvals.find(({ familyId, candidateId }) => familyId === family.id && candidateId === 'sidebar-shell');
  assert.ok(sidebarApproval, 'sidebar-shell must have its own approval entry');
  assert.deepEqual(sidebarApproval.themes, ['dark']);
  assert.deepEqual(sidebarApproval.viewports, ['1180x820', '1440x900']);
  assert.match(decisions, /DS-026.*Sidebar desktop.*approved/);
  for (const label of ['Obra', 'Programa General', 'Programación Intermedia', 'Programación Semanal', 'Compras', 'Familias de Actividades', 'Paquetes de Contratación', 'Plan de Compras', 'Control Tower - Informes', 'Profesionales', 'Subcontratistas']) {
    assert.match(fixture, new RegExp(label.replace(/[+]/g, '\\+')));
  }
  assert.doesNotMatch(fixture, /'label' => 'Integración'/);
});

test('the global navigation bar keeps padding on all four sides', async () => {
  const css = await readFile('public/css/design-system/components/navigation.css', 'utf8');
  const component = await readFile('src/View/Components/DesignSystemComponent.php', 'utf8');
  const tokens = await readFile('public/css/tokens.css', 'utf8');
  const block = css.match(/\.aia-navigation__global\s*\{([^}]+)\}/)?.[1] ?? '';
  assert.match(block, /padding:\s*var\(--ds-space-4\)/);
  assert.match(block, /grid-template-columns:/);
  assert.match(component, /aia-navigation__brand/);
  assert.match(component, /aia-navigation__context/);
  assert.match(css, /\.aia-navigation__context\s*\{[\s\S]*min-width:\s*0[\s\S]*text-overflow:\s*ellipsis/);
  assert.match(css, /@layer components\s*\{[\s\S]*\.aia-navigation__global\s*\{[\s\S]*padding:\s*var\(--ds-space-4\)/);
  assert.match(component, /presentation.*sidebar/);
  assert.match(component, /data-shell-pattern="sidebar/);
  assert.match(css, /\.aia-navigation--sidebar/);
  assert.match(tokens, /--ds-sidebar-width-expanded:\s*17\.5rem/);
  assert.match(tokens, /--ds-sidebar-width-collapsed:\s*4\.5rem/);
});
