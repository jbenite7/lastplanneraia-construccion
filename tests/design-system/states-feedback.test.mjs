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
    // Igualdad, no suelo. Hasta el 2026-08-11 esto era `>= 13`, y un suelo solo caza que falte un
    // modulo: no caza que se cuele uno inventado. Uno se colo —`programa-general-actualizar`
    // declaraba seis estados que ninguna pantalla pinta, retirado ese dia— y el suelo lo dejo
    // pasar un mes. Con `===` y la lista literal de abajo el conjunto queda cerrado por los dos
    // lados. Si algun dia entra un modulo nuevo, este numero se sube a mano a proposito: ese es
    // el punto donde alguien tiene que mirar si el modulo pinta de verdad sus estados.
    // 2026-08-12 (D-CEF-1): de 12 a 10. Se retiran `contratos` y
    // `listado-actividades`, que eran la interfaz del PDC v1: sus rutas,
    // controladores y datos se eliminaron el 2026-08-04 y hoy dan `grep -c` 0 en
    // `public/index.php`. Entre las dos declaraban **7 estados** que ya no pinta
    // nadie: esa es la cobertura que se pierde, y se pierde porque no cubria nada.
    // Confirmado por `views/partials/shell_sidebar.php:93-96`, no por el conteo de
    // rutas, que es un proxy: dos de sus etiquetas —«Cambios guardados», «Error de
    // conexion»— si existen en el repo, pero en Profesionales, Subcontratistas y el
    // laboratorio. Por la etiqueta sola no se sabe de quien es un estado; por eso
    // ahora cada modulo declara su `surface`.
    const modules = semantics.moduleMappings.map(({ module }) => module);
    const esperados = ['programacion-semanal', 'programa-general', 'programacion-intermedia', 'auth', 'bi', 'pdc', 'control-cambios', 'dashboard', 'profesionales', 'subcontratistas'];
    // deepEqual sobre el conjunto ordenado, no `length` mas un bucle de `includes`:
    // asi la mutacion util —cambiar QUE se cuenta— tambien cae. Con `length === N`
    // y `includes`, sustituir un modulo real por uno inventado mantenia el numero
    // y solo fallaba por el que faltaba; ahora falla nombrando al intruso.
    assert.deepEqual([...modules].sort(), [...esperados].sort());
  assert.equal(semantics.moduleMappings.find(({ module }) => module === 'programa-general').states.find(({ label }) => label === 'Atrasada').level, 'urgent');
  assert.match(css, /\[data-aia-severity="high"\]\[data-aia-urgency="now"\]/);
});

test('programación intermedia exposes its eight real states with action priority', async () => {
  const semantics = await readJson('state-semantics.json');
  const view = await readFile('views/design-system/families/states-feedback.php', 'utf8');
  const intermediate = semantics.moduleMappings.find(({ module }) => module === 'programacion-intermedia');
  // `key` es el vocabulario con el que el modulo nombra sus estados; sin el, unir
  // el contrato con el renderer exige comparar etiquetas, que no coinciden.
  //
  // Varios de estos ocho usan una familia de matiz distinta a la que dicta su
  // nivel. No es una contradiccion: el nivel es prioridad de accion y el matiz
  // es identidad, y son canales distintos. Declararlo aqui convierte lo que
  // parecia una divergencia silenciosa en una eleccion explicita.
  //
  // Los ocho matices son distintos entre si desde la reasignacion de 2026-07-28:
  // la paleta publica un solo tinte por matiz, asi que dos estados que
  // compartieran matiz pintarian el mismo fondo. Antes habia tres rojos y tres
  // ambares aqui, y `Alistamiento Urgente` y `Alistamiento en Riesgo` -dos
  // filtros de la leyenda- eran bit-identicos en pantalla. La justificacion de
  // cada asignacion vive en public/css/programacion-intermedia.css.
  assert.deepEqual(intermediate.states, [
    { label: 'RC inicio vencido', key: 'blocked-overdue-critical', level: 'urgent', hue: 'red' },
    { label: 'Inicio vencido', key: 'blocked-overdue', level: 'urgent', hue: 'orange' },
    { label: 'Inicio por Habilitar', key: 'blocked-due', level: 'attention', hue: 'violet' },
    { label: 'Alistamiento Urgente', key: 'alert-1-week', level: 'urgent', hue: 'amber' },
    { label: 'Alistamiento en Riesgo', key: 'alert-2-3-weeks', level: 'attention', hue: 'teal' },
    { label: 'Alistamiento Pendiente', key: 'alert-4-6-weeks', level: 'attention', hue: 'neutral' },
    { label: 'En Ejecución Pendiente', key: 'execution-blocked', level: 'attention', hue: 'blue', note: 'Ratificado 2026-08-03 por el propietario del producto: attention/blue para actividad en ejecución sin liberar (stateMachine.js getState). El mapeo a ok que registró el inventario G0 quedó obsoleto antes del repaso.' },
    { label: 'Listo para Comprometer', key: 'liberated-control', level: 'healthy', hue: 'green' },
  ]);
  assert.equal(
    new Set(intermediate.states.map(({ hue }) => hue)).size,
    8,
    'los ocho estados de Intermedia deben llevar ocho matices distintos',
  );
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
  //
  // Dos matices se corrigieron despues: `Ejecucion con restricciones` es NARANJA
  // y `Trabajo No Planificado` es AZUL. Se habian deducido del nombre de la
  // clase (`ps-alert-high`, `ps-alert-tnp`) en vez de la paleta clara que el
  // modulo tenia antes de pasar a dark, que es la que dice la intencion:
  // `--aia-orange-very-light` y `--aia-blue-very-light`. Con `high` en ambar era
  // indistinguible de `medium`.
  // `key` es el vocabulario con el que el modulo nombra sus estados -las
  // claves de `WEEKLY_ALERT_MODEL` en hot.js-, igual que en Intermedia: sin
  // el, unir el renderer con el contrato exige comparar etiquetas.
  assert.deepEqual(weekly.states, [
    { label: 'RC con restricciones', key: 'prog-bloqueo-critico-sin-compromiso', level: 'urgent', hue: 'red' },
    { label: 'Ejecución con restricciones', key: 'prog-ejecucion-con-restricciones', level: 'urgent', hue: 'orange' },
    { label: 'Condiciones Pendientes', key: 'prog-condiciones-pendientes', level: 'attention', hue: 'amber' },
    { label: 'Por Comprometer', key: 'prog-sin-compromiso', level: 'attention', hue: 'amber' },
    { label: 'Lista para Confirmar', key: 'prog-lista-para-confirmar', level: 'healthy', hue: 'green' },
    { label: 'Incumplida (RC)', key: 'cal-incumplida-critica', level: 'urgent', hue: 'red' },
    { label: 'Incumplida', key: 'cal-incumplida', level: 'attention', hue: 'amber' },
    { label: 'Sin Calificar', key: 'cal-sin-calificar', level: 'attention', hue: 'amber' },
    { label: 'Cumplida Control', key: 'cal-cumplida-control', level: 'healthy', hue: 'green' },
    { label: 'Trabajo No Planificado', key: 'cal-tnp', level: 'neutral', hue: 'blue' },
  ]);
});

// D-CEF-1 (2026-08-12): cada modulo declara donde se pintan sus estados, y el
// gate comprueba que esa superficie EXISTE en el front controller. Sin esto,
// `programa-general-actualizar` declaro seis estados que ninguna pantalla
// pintaba y estuvo en verde desde el 2026-07-15: el esquema es
// `additionalProperties: false` con solo `module` y `states`, asi que no habia
// nada contra lo que contrastar. El contrato vigilaba la resta -retirar un
// modulo rompia el censo- y no la suma.
test('cada modulo de estados declara una superficie que existe en el front controller', async () => {
  const semantics = await readJson('state-semantics.json');
  const frontController = await readFile(
    new URL('../../public/index.php', import.meta.url), 'utf8',
  );

  assert.ok(semantics.moduleMappings.length > 0, 'no hay modulos que comprobar');

  for (const entry of semantics.moduleMappings) {
    assert.ok(
      typeof entry.surface === 'string' && entry.surface.length > 0,
      `${entry.module} no declara superficie`,
    );
    // La comprobacion util no es que el campo exista, es que apunte a algo real.
    assert.ok(
      frontController.includes(`'${entry.surface}'`),
      `${entry.module} declara la superficie ${entry.surface}, que no esta en public/index.php`,
    );
  }
});
