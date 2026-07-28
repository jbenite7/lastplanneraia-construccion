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

test('laboratory state section headings inherit canonical theme text', async () => {
  const css = await readFile('public/css/design-system/lab.css', 'utf8');
  assert.match(css, /\.ds-lab__header h1,\s*\.ds-lab__family h2,\s*\.ds-lab__family h3,\s*\.ds-lab__family h4\s*\{[^}]*color:\s*var\(--ds-active-text-primary\)/s);
});

test('loading spinner is rendered by the canonical states laboratory specimen', async () => {
  const view = await readFile('views/design-system/families/states-feedback.php', 'utf8');
  assert.match(view, /data-ui-group="loading-spinner"[^>]*role="status"[^>]*aria-live="polite"/);
  assert.match(view, /data-ui-group="loading-spinner"[\s\S]*class="aia-spinner"[^>]*aria-hidden="true"/);
  assert.match(view, /Carga indeterminada/);
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
  // Cuatro de estos ocho usan una familia de matiz distinta a la que dicta su
  // nivel. No es una contradiccion: el nivel es prioridad de accion y el matiz
  // es identidad, y son canales distintos. Declararlo aqui convierte lo que
  // parecia una divergencia silenciosa en una eleccion explicita.
  assert.deepEqual(intermediate.states, [
    { label: 'RC inicio vencido', level: 'urgent', hue: 'red' },
    { label: 'Inicio vencido', level: 'urgent', hue: 'red' },
    { label: 'Inicio por Habilitar', level: 'attention', hue: 'amber' },
    { label: 'Alistamiento Urgente', level: 'urgent', hue: 'amber' },
    { label: 'Alistamiento en Riesgo', level: 'attention', hue: 'amber' },
    { label: 'Alistamiento Pendiente', level: 'attention', hue: 'green' },
    { label: 'En Ejecución Pendiente', level: 'attention', hue: 'red' },
    { label: 'Listo para Comprometer', level: 'healthy', hue: 'teal' },
  ]);
  assert.match(view, /data-state-module="programacion-intermedia"/);
  assert.match(view, /Programación Intermedia · 8 estados/);
});

test('el contrato declara matiz e identidad como un eje aparte del nivel', async () => {
  const semantics = await readJson('state-semantics.json');
  // Ocho matices para cuatro niveles. Hacen falta los dos ejes porque un solo
  // canal no puede decirlo todo: en /pdc, `Informacion pendiente` (violeta) y
  // `Contratacion cerrada tarde` (ambar) son ambos `attention`, y
  // `Inicio de contratacion vencido` (rojo) y `Contratacion atrasada` (naranja)
  // son ambos `urgent`.
  assert.deepEqual(
    semantics.hues.map(({ id }) => id).sort(),
    ['amber', 'blue', 'green', 'neutral', 'orange', 'red', 'teal', 'violet'],
  );
  // El matiz por defecto de cada nivel sigue siendo su token: un estado que no
  // declare `hue` se comporta exactamente como antes de existir este eje.
  const defaults = Object.fromEntries(semantics.levels.map(({ id, token }) => [id, token]));
  assert.deepEqual(defaults, {
    neutral: 'info', healthy: 'success', attention: 'warning', urgent: 'critical',
  });
  // Todo matiz declarado por un modulo tiene que existir en el catalogo.
  const known = new Set(semantics.hues.map(({ id }) => id));
  const unknown = semantics.moduleMappings.flatMap(({ module, states }) => states
    .filter(({ hue }) => hue !== undefined && !known.has(hue))
    .map(({ label, hue }) => `${module}/${label}: ${hue}`));
  assert.deepEqual(unknown, []);
});

test('pdc declara los siete estados que su leyenda pinta', async () => {
  const semantics = await readJson('state-semantics.json');
  const pdc = semantics.moduleMappings.find(({ module }) => module === 'pdc');
  // El contrato declaraba seis y la vista renderiza siete: faltaba
  // `Inicio de contratacion vencido`, que el JSON habia fundido con
  // `Contratacion atrasada` en un unico `urgent`. La UI lleva anios mostrando
  // los dos filtros por separado, asi que el que estaba mal era el JSON.
  assert.deepEqual(pdc.states, [
    { label: 'Información pendiente', level: 'attention', hue: 'violet' },
    { label: 'Inicio de contratación vencido', level: 'urgent', hue: 'red' },
    { label: 'Contratación atrasada', level: 'urgent', hue: 'orange' },
    { label: 'Contratación cerrada tarde', level: 'attention', hue: 'amber' },
    { label: 'Contratación cerrada a tiempo', level: 'healthy', hue: 'green' },
    { label: 'Contratación en curso', level: 'healthy', hue: 'blue' },
    { label: 'Contratación pendiente de inicio', level: 'attention', hue: 'neutral' },
  ]);
});

test('programación semanal declara las etiquetas de sus dos fases', async () => {
  const semantics = await readJson('state-semantics.json');
  const weekly = semantics.moduleMappings.find(({ module }) => module === 'programacion-semanal');
  // El mapping anterior no estaba incompleto sino obsoleto: declaraba seis
  // etiquetas de las que solo dos existian en la UI. Las reales viven en
  // WEEKLY_ALERT_MODEL (public/js/modules/programacion_semanal/hot.js) y son
  // diez, cinco por cada fase del modulo.
  assert.deepEqual(weekly.states, [
    { label: 'RC con restricciones', level: 'urgent', hue: 'red' },
    { label: 'Ejecución con restricciones', level: 'urgent', hue: 'amber' },
    { label: 'Condiciones Pendientes', level: 'attention', hue: 'amber' },
    { label: 'Por Comprometer', level: 'attention', hue: 'amber' },
    { label: 'Lista para Confirmar', level: 'healthy', hue: 'green' },
    { label: 'Incumplida (RC)', level: 'urgent', hue: 'red' },
    { label: 'Incumplida', level: 'attention', hue: 'amber' },
    { label: 'Sin Calificar', level: 'attention', hue: 'amber' },
    { label: 'Cumplida Control', level: 'healthy', hue: 'green' },
    { label: 'Trabajo No Planificado', level: 'neutral', hue: 'teal' },
  ]);
});
