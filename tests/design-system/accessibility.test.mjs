import assert from 'node:assert/strict';
import { existsSync } from 'node:fs';
import { readFile } from 'node:fs/promises';
import test from 'node:test';

const helperPath = new URL('../browser/support/accessibility.mjs', import.meta.url);
const readJson = async (file) => JSON.parse(await readFile(
  new URL(`../../docs/design-system/${file}`, import.meta.url), 'utf8',
));

// Cada superficie con excepciones `color-contrast|incomplete` se afirma sola: sus
// selectores exactos, su naturaleza y la coherencia del fingerprint.
const assertScopedContrastExceptions = (exceptions, surface, expectedSelectors) => {
  const scoped = exceptions.filter((exception) => exception.surface === surface);
  assert.deepEqual(scoped.map(({ selector }) => selector).sort(), [...expectedSelectors].sort(), surface);
  assert.equal(scoped.every((exception) => (
    exception.kind === 'incomplete'
    && exception.rule === 'color-contrast'
    && exception.impact === 'serious'
    && exception.fingerprint === [exception.rule, exception.impact, exception.kind,
      exception.surface, exception.selector].join('|')
  )), true, surface);
};

// Programa General entro al carril pilot el 2026-08-03: nueve celdas `.pdc-header`
// que axe no puede medir por su fondo translucido (memoria/trampas/axe-incomplete-cuenta-como-violacion.md).
const programaGeneralSelectors = [
  '.ht__row_even:nth-child(2) > .htLeft.pdc-header.force-wrap > b',
  '.ht__row_even:nth-child(2) > .htLeft.pdc-header.force-wrap > small',
  '.ht__row_even:nth-child(2) > .pdc-header.force-wrap.pg-cell-readonly:nth-child(11)',
  '.ht__row_even:nth-child(2) > .pdc-header.pg-cell-readonly.htDimmed:nth-child(1)',
  '.ht__row_even:nth-child(2) > .pdc-header.pg-cell-readonly.htDimmed:nth-child(12)',
  '.ht__row_even:nth-child(2) > .pdc-header.pg-cell-readonly.htDimmed:nth-child(3)',
  '.ht__row_even:nth-child(2) > .pdc-header.pg-cell-readonly.htDimmed:nth-child(6)',
  '.ht__row_even:nth-child(2) > .pdc-header.pg-date-cell.htAutocomplete:nth-child(4)',
  '.ht__row_even:nth-child(2) > .pdc-header.pg-date-cell.htAutocomplete:nth-child(5)',
];

test('the shared axe helper exists', () => {
  assert.equal(existsSync(helperPath), true);
});

test('approved accessibility scenarios cover every theme and required viewport', async () => {
  const { approvedAccessibilityScenarios } = await import(helperPath);
  const homologation = await readJson('homologation.json');
  const scenarios = approvedAccessibilityScenarios(homologation);

  assert.equal(scenarios.length, 30);
  assert.deepEqual([...new Set(scenarios.map(({ family }) => family))], [
    'foundations', 'shell-navigation', 'page-structure', 'actions', 'forms-filters',
    'states-feedback', 'data-display', 'overlays', 'vendor-adapters', 'bi-primitives',
  ]);
  for (const family of [...new Set(scenarios.map(({ family }) => family))]) {
    const familyScenarios = scenarios.filter((scenario) => scenario.family === family);
    assert.deepEqual([...new Set(familyScenarios.map(({ theme }) => theme))], ['dark']);
    assert.deepEqual([...new Set(familyScenarios.map(({ viewport }) => viewport))], [
      '390x844', '1180x820', '1440x900',
    ]);
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

test('serious axe findings that require review still block the gate', async () => {
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

  assert.deepEqual(outcome.blocking.map(({ rule, kind }) => ({ rule, kind })), [{
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
  assert.equal(baseline.designSystemVersion, '1.0.0');
  assert.deepEqual(baseline.fingerprints, []);
  assert.equal(exceptions.designSystemVersion, '1.0.0');
  const reviewedSelectors = [
    'text[x="1"]', 'text[x="109"]', 'text[x="116"]', 'text[x="118"]',
    'text[x="164"]', 'text[x="36"]', 'text[x="39"]', 'text[x="76"]',
    'text[x="88"]', 'text[y="31"]', 'text[y="42"]', 'text[y="53"]',
    'text[y="7"]', 'text[y="9"]',
  ];
  // Cada superficie documentada se comprueba por separado: el total global no se
  // afirma, porque acoplarlo a una superficie concreta rompe el test cada vez que
  // entra otra al carril (fue lo que paso con Programa General).
  for (const viewport of ['1180x820', '1440x900']) {
    assertScopedContrastExceptions(
      exceptions.exceptions, `lab/bi-primitives/dark/${viewport}`, reviewedSelectors,
    );
  }
  assertScopedContrastExceptions(
    exceptions.exceptions, 'programa-general:wide-desktop:dark', programaGeneralSelectors,
  );
});

test('the shared helper loads the versioned baseline and exceptions', async () => {
  const helper = await import(helperPath);
  assert.equal(typeof helper.loadAccessibilityGovernance, 'function');
  const governance = await helper.loadAccessibilityGovernance();
  assert.equal(governance.designSystemVersion, '1.0.0');
  assert.deepEqual(governance.baseline, []);
  // Lo que se afirma es que el helper carga el contrato versionado tal cual, no un
  // total escrito a mano que caduca con cada superficie nueva.
  const exceptions = await readJson('a11y-exceptions.json');
  assert.deepEqual(governance.exceptions, exceptions.exceptions);
  for (const viewport of ['1180x820', '1440x900']) {
    assert.equal(
      governance.exceptions.filter((e) => e.surface === `lab/bi-primitives/dark/${viewport}`).length, 14,
    );
  }
  assert.equal(
    governance.exceptions.filter((e) => e.surface === 'programa-general:wide-desktop:dark').length,
    programaGeneralSelectors.length,
  );
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
