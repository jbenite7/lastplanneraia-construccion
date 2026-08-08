import assert from 'node:assert/strict';
import { existsSync } from 'node:fs';
import { readFile } from 'node:fs/promises';
import test from 'node:test';

const helperPath = new URL('../browser/support/accessibility.mjs', import.meta.url);
const readJson = async (file) => JSON.parse(await readFile(
  new URL(`../../docs/design-system/${file}`, import.meta.url), 'utf8',
));

// RETIRADAS el 2026-08-05: Programa General ya no tiene ninguna excepcion de accesibilidad.
//
// Entro al carril pilot el 2026-08-03 con nueve `.pdc-header` marcadas `color-contrast|
// incomplete|serious`, justificadas por «fondo translucido». Esa justificacion era FALSA: los
// nueve selectores empiezan por `.ht__row_even:nth-child(2)`, o sea la fila PAR, y lo que axe no
// sabia componer era la funcion de color de la cebra (`--ds-table-zebra`, un
// `color-mix(in oklch, ...)`), no el alfa —la superficie que la sustituye tambien es translucida
// y axe la mide sin problema—.
//
// El encabezado sobrio de la campana de dark mode las dejo sin objeto (Task 36: 9 -> 0), y se
// remidio el 2026-08-05 sobre `/programa-general` con datos reales, dark, en los DOS viewports
// permitidos (1180x820 y 1440x900), con el mismo `withTags(WCAG_TAGS)` del helper: 0 violaciones,
// 0 incompletos, 202 nodos `color-contrast` en `passes` sobre 48 celdas `.pdc-header` presentes
// —no es un escaneo vacio—. El tratamiento sobrio no relajo la accesibilidad: la arreglo.
//
// El choque que bloqueo la retirada entonces —«rehacer el registro exige el escenario aprobado
// completo, que incluye 390x844, viewport prohibido por AGENTS.md»— no existia. (Esa prohibicion
// de AGENTS.md se retiro ademas el 2026-08-07: 390x844 quedo permitido pero no exigido, sin
// evidencia todavia para ninguna familia.) `a11y-exceptions
// .json` es un contrato escrito a mano: ningun script lo genera, asi que no hay «regeneracion»
// que arrastre viewports. Los 390x844 salen de `approvedAccessibilityScenarios(homologation)`,
// que alimenta al LABORATORIO (superficies `lab/...`) y es otra lista de entradas. Estas nueve
// vivian en la superficie `programa-general:wide-desktop:dark`, producida por
// `tests/browser/programa-general-design-system.mjs`, cuyos unicos viewports son 1180x820 y
// 1440x900. Retirarlas solo exigia remedir esos dos, dentro del alcance desktop-dark.
//
// Los dos tests de abajo afirman la AUSENCIA por prefijo de superficie: si alguien vuelve a colar
// una excepcion para Programa General —con este nombre de superficie o con otro—, salta aqui en
// vez de envejecer callada.
const programaGeneralExceptions = (exceptions) => exceptions.filter(
  ({ surface }) => surface.startsWith('programa-general'),
);

// RETIRADAS el 2026-08-06: `lab/bi-primitives` ya no tiene ninguna excepcion de accesibilidad.
//
// Las 28 (14 selectores x 2 viewports) entraron marcadas `color-contrast|incomplete|serious`,
// justificadas por «axe no puede calcular texto SVG sobre nodos graficos». Igual que con Programa
// General, `incomplete` significa que axe no pudo medir, no que hubiera un defecto (memoria/
// trampas/axe-incomplete-cuenta-como-violacion.md).
//
// Remedido el 2026-08-06 contra `/internal/design-system?family=bi-primitives`, dark, con la
// puerta de desarrollo (`test.A`), en los DOS viewports permitidos (1180x820 y 1440x900), con una
// sonda adaptada de `tests/browser/support/contrast.mjs`: mismo compuesto alpha sobre ancestros
// via canvas (soporta `color()`/`color-mix`), pero leyendo `fill` en vez de `color` y arrancando
// la cadena de ancestros en el primer nodo FUERA del <svg> (el propio <svg> no pinta fondo). Los
// 14 nodos son etiquetas del radar (`aia-bi-radar__label`) y del ranking (`aia-bi-ranked__label`,
// `aia-bi-ranked__value`, mas tres textos sin clase). Cada uno tiene exactamente un match; se
// comprobo que un `rect` vecino (la barra del ranking) NUNCA se solapa con el texto de valor —el
// texto vive fuera de la barra—, asi que el fondo real siempre es la tarjeta `<figure class=
// "aia-card aia-bi ...">` compuesta sobre lo que hay detras, nunca el relleno de la barra.
//
// Resultado: identico en los dos viewports (el layout no cambia el color, solo el tamano).
// Etiquetas (fill rgb(199,212,204) sobre fondo compuesto rgb(27,35,30)): 10.48:1. Valores (fill
// rgb(247,250,248) sobre fondo compuesto rgb(27,35,30) o rgb(30,41,35) segun la tarjeta): entre
// 14.30:1 y 15.27:1. El minimo de las 28 mediciones es 10.48:1, mas del doble del piso AA de
// 4.5:1 para texto normal. Cero defectos que arreglar.
const biPrimitivesExceptions = (exceptions) => exceptions.filter(
  ({ surface }) => surface.startsWith('lab/bi-primitives'),
);

test('the shared axe helper exists', () => {
  assert.equal(existsSync(helperPath), true);
});

test('approved accessibility scenarios cover every theme and required viewport', async () => {
  const { approvedAccessibilityScenarios } = await import(helperPath);
  const homologation = await readJson('homologation.json');
  const approvals = await readJson('family-approvals.json');
  const scenarios = approvedAccessibilityScenarios(homologation);

  assert.deepEqual([...new Set(scenarios.map(({ family }) => family))], [
    'foundations', 'shell-navigation', 'page-structure', 'actions', 'forms-filters',
    'states-feedback', 'data-display', 'overlays', 'vendor-adapters', 'bi-primitives',
  ]);
  for (const family of [...new Set(scenarios.map(({ family }) => family))]) {
    const familyScenarios = scenarios.filter((scenario) => scenario.family === family);
    assert.deepEqual([...new Set(familyScenarios.map(({ theme }) => theme))], ['dark']);

    // homologation.json declara los viewports de la familia; family-approvals.json declara los
    // viewports que cubrio la aprobacion humana firmada de esa familia. Son dos archivos
    // distintos que deben coincidir — si divergen, hay cobertura declarada sin aprobar o una
    // aprobacion que ya no coincide con lo declarado. Una familia puede tener varias candidatas
    // `approved` a la vez (p. ej. shell-navigation tiene adaptive-shell y sidebar-shell): elegir
    // una por orden implicito del JSON deja el resultado sin determinar y puede contrastar la
    // candidata equivocada. Se comprueban TODAS las aprobaciones firmadas de las candidatas
    // aprobadas de la familia, no solo la primera.
    const homologatedFamily = homologation.families.find(({ id }) => id === family);
    const approvedCandidateIds = (homologatedFamily.candidates || [])
      .filter(({ status }) => status === 'approved')
      .map(({ id }) => id);
    assert.ok(approvedCandidateIds.length > 0, `${family} has no approved candidate in homologation.json`);
    for (const approvedCandidateId of approvedCandidateIds) {
      const approval = approvals.approvals.find(
        ({ familyId, candidateId }) => familyId === family && candidateId === approvedCandidateId,
      );
      assert.ok(approval, `no signed approval found for ${family}/${approvedCandidateId}`);
      assert.deepEqual(homologatedFamily.viewports, approval.viewports);
    }
  }
});

test('axe fingerprints are stable and surface-specific', async () => {
  const { fingerprintViolations } = await import(helperPath);
  const results = { violations: [{ id: 'color-contrast', impact: 'serious', nodes: [
    { target: ['.summary', 'button'] }, { target: ['#primary'] },
  ] }] };
  assert.deepEqual(fingerprintViolations(results, 'lab/page-structure'), [
    'color-contrast|serious|violation|lab/page-structure|#primary',
    'color-contrast|serious|violation|lab/page-structure|.summary > button',
  ]);
});

test('critical and serious findings block while lower impacts are reported', async () => {
  const { evaluateAccessibility } = await import(helperPath);
  const results = { violations: [
    { id: 'button-name', impact: 'critical', nodes: [{ target: ['button'] }] },
    { id: 'color-contrast', impact: 'serious', nodes: [{ target: ['.copy'] }] },
    { id: 'landmark', impact: 'moderate', nodes: [{ target: ['main'] }] },
  ] };
  const outcome = evaluateAccessibility(results, {
    surface: 'lab/actions', exceptions: [], now: '2026-07-12',
  });
  assert.deepEqual(outcome.blocking.map(({ impact }) => impact), ['critical', 'serious']);
  assert.deepEqual(outcome.reported.map(({ impact }) => impact), ['moderate']);
});

test('serious axe incomplete findings are reported, not blocked', async () => {
  const { evaluateAccessibility, fingerprintViolations } = await import(helperPath);
  const results = {
    violations: [],
    incomplete: [{
      id: 'color-contrast',
      impact: 'serious',
      nodes: [{ target: ['.aia-card', '.aia-copy'] }],
    }],
  };
  const outcome = evaluateAccessibility(results, {
    surface: 'lab/data-display', exceptions: [], now: '2026-07-12',
  });

  assert.deepEqual(outcome.blocking, []);
  assert.deepEqual(outcome.reported.map(({ rule, kind }) => ({ rule, kind })), [{
    rule: 'color-contrast', kind: 'incomplete',
  }]);
  assert.deepEqual(fingerprintViolations(results, 'lab/data-display'), [
    'color-contrast|serious|incomplete|lab/data-display|.aia-card > .aia-copy',
  ]);
});

test('the baseline distinguishes existing fingerprints from new findings', async () => {
  const { evaluateAccessibility } = await import(helperPath);
  const results = { violations: [
    { id: 'landmark', impact: 'moderate', nodes: [{ target: ['main'] }] },
    { id: 'button-name', impact: 'critical', nodes: [{ target: ['button'] }] },
  ] };
  const outcome = evaluateAccessibility(results, {
    surface: 'lab/actions', exceptions: [], now: '2026-07-12',
    baseline: ['landmark|moderate|violation|lab/actions|main'],
  });
  assert.deepEqual(outcome.existing.map(({ rule }) => rule), ['landmark']);
  assert.deepEqual(outcome.newFindings.map(({ rule }) => rule), ['button-name']);
});

test('only an exact active exception can suppress a blocking fingerprint', async () => {
  const { evaluateAccessibility } = await import(helperPath);
  const results = { violations: [
    { id: 'button-name', impact: 'critical', nodes: [{ target: ['#save'] }] },
  ] };
  const exception = {
    fingerprint: 'button-name|critical|violation|pilot/programa-general|#save',
    surface: 'pilot/programa-general', rule: 'button-name', impact: 'critical',
    kind: 'violation', selector: '#save',
    owner: 'Design System', reason: 'Remediación trazada', milestone: '1.0.0',
    expiresAt: '2026-08-01',
  };
  const outcome = evaluateAccessibility(results, {
    surface: 'pilot/programa-general', exceptions: [exception], now: '2026-07-12',
  });
  assert.equal(outcome.blocking.length, 0);
  assert.equal(outcome.excepted.length, 1);
});

test('an incomplete review exception cannot suppress a later violation', async () => {
  const { evaluateAccessibility } = await import(helperPath);
  const results = { violations: [
    { id: 'color-contrast', impact: 'serious', nodes: [{ target: ['svg text'] }] },
  ] };
  const exception = {
    fingerprint: 'color-contrast|serious|incomplete|lab/bi-primitives|svg text',
    surface: 'lab/bi-primitives', rule: 'color-contrast', impact: 'serious',
    kind: 'incomplete', selector: 'svg text', owner: 'AIA', reason: 'Revisión manual',
    milestone: '1.0.0', expiresAt: '2026-08-01',
  };
  const outcome = evaluateAccessibility(results, {
    surface: 'lab/bi-primitives', exceptions: [exception], now: '2026-07-12',
  });
  assert.equal(outcome.blocking.length, 1);
  assert.equal(outcome.excepted.length, 0);
});

test('expired and wildcard accessibility exceptions are rejected', async () => {
  const { validateAccessibilityExceptions } = await import(helperPath);
  const valid = {
    fingerprint: 'button-name|critical|violation|lab/actions|#save', owner: 'AIA',
    surface: 'lab/actions', rule: 'button-name', impact: 'critical', kind: 'violation', selector: '#save',
    reason: 'Pendiente', milestone: '1.0.0', expiresAt: '2026-07-01',
  };
  assert.throws(
    () => validateAccessibilityExceptions([valid], '2026-07-12'),
    /expired accessibility exception/,
  );
  assert.throws(
    () => validateAccessibilityExceptions([{ ...valid, expiresAt: '2026-08-01', fingerprint: '*' }], '2026-07-12'),
    /exact fingerprint/,
  );
  assert.throws(
    () => validateAccessibilityExceptions([{ ...valid, expiresAt: '2026-08-01', surface: '' }], '2026-07-12'),
    /requires surface/,
  );
  assert.throws(
    () => validateAccessibilityExceptions([{ ...valid, expiresAt: '2026-08-01', rule: 'different-rule' }], '2026-07-12'),
    /must match declared fields/,
  );
});

test('axe baseline and exceptions are separate versioned contracts', async () => {
  for (const file of ['a11y-baseline.json', 'a11y-exceptions.json']) {
    assert.equal(existsSync(new URL(`../../docs/design-system/${file}`, import.meta.url)), true, file);
  }
  const baseline = await readJson('a11y-baseline.json');
  const exceptions = await readJson('a11y-exceptions.json');
  // Contra version.json, no contra un literal: lo que se afirma es que los dos
  // contratos van SINCRONIZADOS con la version viva. Fijar '1.0.0' a mano hacia
  // que el test se rompiera en cada bump sin detectar ninguna desincronizacion.
  const { version: publicada } = await readJson('version.json');
  assert.equal(baseline.designSystemVersion, publicada);
  assert.deepEqual(baseline.fingerprints, []);
  assert.equal(exceptions.designSystemVersion, publicada);
  assert.deepEqual(biPrimitivesExceptions(exceptions.exceptions), []);
  assert.deepEqual(programaGeneralExceptions(exceptions.exceptions), []);
});

test('the shared helper loads the versioned baseline and exceptions', async () => {
  const helper = await import(helperPath);
  assert.equal(typeof helper.loadAccessibilityGovernance, 'function');
  const governance = await helper.loadAccessibilityGovernance();
  const { version: publicada } = await readJson('version.json');
  assert.equal(governance.designSystemVersion, publicada);
  assert.deepEqual(governance.baseline, []);
  // Lo que se afirma es que el helper carga el contrato versionado tal cual, no un
  // total escrito a mano que caduca con cada superficie nueva.
  const exceptions = await readJson('a11y-exceptions.json');
  assert.deepEqual(governance.exceptions, exceptions.exceptions);
  assert.deepEqual(biPrimitivesExceptions(governance.exceptions), []);
  assert.deepEqual(programaGeneralExceptions(governance.exceptions), []);
});

test('axe schemas prohibit undeclared fields and broad exclusions', async () => {
  for (const file of ['a11y-baseline.schema.json', 'a11y-exceptions.schema.json']) {
    assert.equal(existsSync(new URL(`../../docs/design-system/${file}`, import.meta.url)), true, file);
  }
  const baselineSchema = await readJson('a11y-baseline.schema.json');
  const exceptionSchema = await readJson('a11y-exceptions.schema.json');
  assert.equal(baselineSchema.additionalProperties, false);
  assert.equal(exceptionSchema.additionalProperties, false);
  assert.equal(exceptionSchema.properties.exceptions.items.additionalProperties, false);
  assert.doesNotMatch(JSON.stringify(exceptionSchema), /exclude|disable/i);
});

test('the root package exposes a non-regenerating laboratory axe gate', async () => {
  const packageJson = JSON.parse(await readFile(
    new URL('../../package.json', import.meta.url), 'utf8',
  ));
  assert.equal(
    packageJson.scripts?.['test:a11y:lab'],
    'playwright test tests/browser/design-system-lab.a11y.mjs --workers=1',
  );
  const a11yScripts = Object.fromEntries(
    Object.entries(packageJson.scripts).filter(([name]) => name.startsWith('test:a11y:')),
  );
  assert.doesNotMatch(JSON.stringify(a11yScripts), /update|baseline|regener/i);
});

test('keyboard and desktop layout have one non-blocking laboratory command outside runtime', async () => {
  const packageJson = JSON.parse(await readFile(
    new URL('../../package.json', import.meta.url), 'utf8',
  ));
  assert.equal(
    packageJson.scripts?.['test:keyboard'],
    'playwright test tests/browser/design-system-lab-keyboard.mjs --workers=1',
  );
  assert.equal(
    packageJson.scripts?.['test:design-system:evidence'],
    'playwright test tests/browser/design-system-lab-keyboard.mjs tests/browser/design-system-lab-desktop-layout.mjs --workers=1',
  );
  assert.doesNotMatch(packageJson.scripts?.['test:design-system:runtime'], /keyboard|reflow|desktop-layout/);
});

test('incomplete never blocks, even with critical impact', async () => {
  const { evaluateAccessibility } = await import(helperPath);
  const results = {
    violations: [],
    incomplete: [{ id: 'color-contrast', impact: 'critical', nodes: [{ target: ['.glass'], failureSummary: 'no se pudo medir sobre fondo translucido' }] }],
  };
  const outcome = evaluateAccessibility(results, { surface: 'lab' });
  assert.equal(outcome.blocking.length, 0);
  assert.equal(outcome.reported.length, 1);
  assert.equal(outcome.reported[0].kind, 'incomplete');
});
