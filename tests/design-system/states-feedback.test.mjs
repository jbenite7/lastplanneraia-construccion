import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';

const readJson = async (file) => JSON.parse(await readFile(
  new URL(`../../docs/design-system/${file}`, import.meta.url), 'utf8',
));

test('tinted semantic states are approved and canonical', async () => {
  const homologation = await readJson('homologation.json');
  const approvals = await readJson('family-approvals.json');
  const catalog = await readJson('component-catalog.json');
  const family = homologation.families.find(({ id }) => id === 'states-feedback');
  const approval = approvals.approvals.find(({ familyId }) => familyId === 'states-feedback');
  const state = catalog.components.find(({ id }) => id === 'state');
  const feedback = catalog.components.find(({ id }) => id === 'feedback');

  assert.deepEqual(family.candidates.filter(({ status }) => status === 'approved').map(({ id }) => id), ['tinted-status']);
  assert.equal(approval.candidateId, 'tinted-status');
  assert.equal(state.maturity, 'stable');
  assert.equal(state.visualApproval.status, 'approved');
  assert.ok(state.api.includes('DesignSystemComponent::status'));
  assert.equal(feedback.kind, 'canonical');
  assert.equal(feedback.maturity, 'candidate');
  assert.equal(feedback.visualApproval.status, 'approved');
  assert.ok(feedback.api.includes('DesignSystemComponent::feedback'));
});

test('feedback messages preserve explicit lateral padding', async () => {
  const css = await readFile('public/css/design-system/components/states-feedback.css', 'utf8');
  const block = css.match(/\.aia-feedback\s*\{([^}]+)\}/)?.[1] ?? '';
  assert.match(block, /display:\s*inline-flex/);
  assert.match(block, /min-height:\s*var\(--ds-target-min\)/);
  assert.match(block, /padding:\s*var\(--ds-space-3\) var\(--ds-space-4\)/);
  assert.match(css, /@layer components\s*\{[\s\S]*\.aia-feedback\s*\{[\s\S]*padding:\s*var\(--ds-space-3\) var\(--ds-space-4\)/);
});

test('state semantics map module labels to shared urgency colors', async () => {
  const semantics = await readJson('state-semantics.json');
  const css = await readFile('public/css/design-system/components/states-feedback.css', 'utf8');
  assert.deepEqual(semantics.levels.map(({ id }) => id), ['neutral', 'healthy', 'attention', 'urgent']);
  assert.equal(semantics.levels.find(({ id }) => id === 'urgent').token, 'critical');
    assert.ok(semantics.moduleMappings.length >= 13);
    const modules = semantics.moduleMappings.map(({ module }) => module);
    for (const module of ['programacion-semanal', 'programa-general', 'programacion-intermedia', 'auth', 'bi', 'pdc', 'control-cambios', 'contratos', 'listado-actividades', 'programa-general-actualizar', 'dashboard', 'profesionales', 'subcontratistas']) {
      assert.ok(modules.includes(module), `missing state mapping for ${module}`);
    }
  assert.equal(semantics.moduleMappings.find(({ module }) => module === 'programa-general').states.find(({ label }) => label === 'Atrasada').level, 'urgent');
  assert.match(css, /\[data-aia-severity="high"\]\[data-aia-urgency="now"\]/);
});

test('programación intermedia exposes its eight real states with action priority', async () => {
  const semantics = await readJson('state-semantics.json');
  const view = await readFile('views/design-system/families/states-feedback.php', 'utf8');
  const intermediate = semantics.moduleMappings.find(({ module }) => module === 'programacion-intermedia');
  assert.deepEqual(intermediate.states, [
    { label: 'RC inicio vencido', level: 'urgent' },
    { label: 'Inicio vencido', level: 'urgent' },
    { label: 'Inicio por Habilitar', level: 'attention' },
    { label: 'Alistamiento Urgente', level: 'urgent' },
    { label: 'Alistamiento en Riesgo', level: 'attention' },
    { label: 'Alistamiento Pendiente', level: 'attention' },
    { label: 'En Ejecución Pendiente', level: 'attention' },
    { label: 'Listo para Comprometer', level: 'healthy' },
  ]);
  assert.match(view, /data-state-module="programacion-intermedia"/);
  assert.match(view, /Programación Intermedia · 8 estados/);
});
