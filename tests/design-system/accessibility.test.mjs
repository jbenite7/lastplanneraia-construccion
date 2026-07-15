import assert from 'node:assert/strict';
import { existsSync } from 'node:fs';
import { readFile } from 'node:fs/promises';
import test from 'node:test';

const helperPath = new URL('../browser/support/accessibility.mjs', import.meta.url);
const readJson = async (file) => JSON.parse(await readFile(
  new URL(`../../docs/design-system/${file}`, import.meta.url), 'utf8',
));

test('the shared axe helper exists', () => {
  assert.equal(existsSync(helperPath), true);
});

test('approved accessibility scenarios cover every theme and required viewport', async () => {
  const { approvedAccessibilityScenarios } = await import(helperPath);
  const homologation = await readJson('homologation.json');
  const scenarios = approvedAccessibilityScenarios(homologation);

  assert.equal(scenarios.length, 60);
  assert.deepEqual([...new Set(scenarios.map(({ family }) => family))], [
    'foundations', 'shell-navigation', 'page-structure', 'actions', 'forms-filters',
    'states-feedback', 'data-display', 'overlays', 'vendor-adapters', 'bi-primitives',
  ]);
  for (const family of [...new Set(scenarios.map(({ family }) => family))]) {
    const familyScenarios = scenarios.filter((scenario) => scenario.family === family);
    assert.deepEqual([...new Set(familyScenarios.map(({ theme }) => theme))], ['linen', 'dark']);
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
    'color-contrast|serious|lab/page-structure|#primary',
    'color-contrast|serious|lab/page-structure|.summary > button',
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

test('the baseline distinguishes existing fingerprints from new findings', async () => {
  const { evaluateAccessibility } = await import(helperPath);
  const results = { violations: [
    { id: 'landmark', impact: 'moderate', nodes: [{ target: ['main'] }] },
    { id: 'button-name', impact: 'critical', nodes: [{ target: ['button'] }] },
  ] };
  const outcome = evaluateAccessibility(results, {
    surface: 'lab/actions', exceptions: [], now: '2026-07-12',
    baseline: ['landmark|moderate|lab/actions|main'],
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
    fingerprint: 'button-name|critical|pilot/programa-general|#save',
    surface: 'pilot/programa-general', rule: 'button-name', impact: 'critical', selector: '#save',
    owner: 'Design System', reason: 'Remediación trazada', milestone: '1.0.0',
    expiresAt: '2026-08-01',
  };
  const outcome = evaluateAccessibility(results, {
    surface: 'pilot/programa-general', exceptions: [exception], now: '2026-07-12',
  });
  assert.equal(outcome.blocking.length, 0);
  assert.equal(outcome.excepted.length, 1);
});

test('expired and wildcard accessibility exceptions are rejected', async () => {
  const { validateAccessibilityExceptions } = await import(helperPath);
  const valid = {
    fingerprint: 'button-name|critical|lab/actions|#save', owner: 'AIA',
    surface: 'lab/actions', rule: 'button-name', impact: 'critical', selector: '#save',
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
  assert.equal(baseline.designSystemVersion, '0.3.6');
  assert.deepEqual(baseline.fingerprints, []);
  assert.equal(exceptions.designSystemVersion, '0.3.6');
  assert.deepEqual(exceptions.exceptions, []);
});

test('the shared helper loads the versioned baseline and exceptions', async () => {
  const helper = await import(helperPath);
  assert.equal(typeof helper.loadAccessibilityGovernance, 'function');
  const governance = await helper.loadAccessibilityGovernance();
  assert.equal(governance.designSystemVersion, '0.3.6');
  assert.deepEqual(governance.baseline, []);
  assert.deepEqual(governance.exceptions, []);
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

test('the root package exposes a dedicated keyboard precheck', async () => {
  const packageJson = JSON.parse(await readFile(
    new URL('../../package.json', import.meta.url), 'utf8',
  ));
  assert.equal(
    packageJson.scripts?.['test:keyboard'],
    'playwright test tests/browser/design-system-keyboard.mjs --workers=1',
  );
  assert.match(packageJson.scripts?.['test:design-system:runtime'], /test:keyboard/);
});
