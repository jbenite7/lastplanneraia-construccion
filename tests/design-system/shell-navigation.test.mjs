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
  assert.deepEqual(adaptiveApproval.themes, ['dark']);
  assert.deepEqual(adaptiveApproval.viewports, family.viewports);
  const sidebarApproval = approvals.approvals.find(({ familyId, candidateId }) => familyId === family.id && candidateId === 'sidebar-shell');
  assert.ok(sidebarApproval, 'sidebar-shell must have its own approval entry');
  assert.deepEqual(sidebarApproval.themes, ['dark']);
  const requiredViewports = ['1180x820', '1440x900'];
  const supportedViewports = [...requiredViewports, '390x844'];
  const sidebarLabel = `${family.id}/sidebar-shell`;
  for (const viewport of requiredViewports) {
    assert.ok(
      sidebarApproval.viewports.includes(viewport),
      `${sidebarLabel} no cubre ${viewport}`,
    );
  }
  for (const viewport of sidebarApproval.viewports) {
    assert.ok(
      supportedViewports.includes(viewport),
      `${sidebarLabel} declara el viewport no soportado ${viewport}`,
    );
  }
  assert.match(decisions, /DS-026.*Sidebar desktop.*approved/);
  for (const label of ['Obra', 'Programa General', 'Programación Intermedia', 'Programación Semanal', 'Compras', 'Plan de Compras', 'Control Tower - Informes', 'Profesionales', 'Subcontratistas']) {
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
  // 15rem / 4rem desde `72093c6` (2026-08-01). El contrato decia 17.5rem / 4.5rem, que fueron los
  // valores originales de `321b095`, y este assert llevaba en rojo desde entonces: aquel commit
  // —un lote de 211 archivos— estrecho ambos tokens sin actualizar este test ni recapturar un solo
  // golden. Resuelto el 2026-08-03 a favor del token, por decision del usuario: la barra lleva
  // dias servida a 15rem sin queja, el commit si tocaba `views/partials/shell_sidebar.php` (no fue
  // un reemplazo ciego), y 240px en vez de 280 dan 40px mas de tabla en una aplicacion densa.
  //
  // Las baselines visuales SIGUEN retratando la barra ancha: se recapturan junto con la linea G
  // del reparto, no antes. Ver docs/superpowers/specs/2026-08-03-reparto-trabajo-pendiente-design.md
  assert.match(tokens, /--ds-sidebar-width-expanded:\s*15rem/);
  assert.match(tokens, /--ds-sidebar-width-collapsed:\s*4rem/);
});
