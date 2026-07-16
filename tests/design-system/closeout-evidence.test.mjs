import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';

const readJson = async (path) => JSON.parse(await readFile(
  new URL(`../../${path}`, import.meta.url),
  'utf8',
));
const read = (path) => readFile(new URL(`../../${path}`, import.meta.url), 'utf8');

const REQUIRED_GATES = [
  'static', 'runtime', 'runtime-budgets', 'phpstan-scoped', 'phpstan-global',
  'global-table-safety', 'pg-roles', 'pg-persistence', 'data-restoration',
  'accessibility-insights',
  'consolidated-lab', 'consolidated-pilot', 'git-preservation',
  'review', 'atomic-commit',
];

function assertExactGateIds(gates) {
  const ids = gates.map(({ id }) => id);
  assert.equal(gates.length, 15);
  assert.equal(new Set(ids).size, 15, 'gate ids must be unique');
  assert.deepEqual(ids, REQUIRED_GATES);
}

test('closeout evidence declares the exact ordered set of 15 blocking gates', async () => {
  const evidence = await readJson('docs/design-system/closeout-evidence.json');
  assert.equal(evidence.schemaVersion, 1);
  assert.match(evidence.designSystemVersion, /^\d+\.\d+\.\d+$/);
  assertExactGateIds(evidence.gates);
  for (const gate of evidence.gates) {
    assert.deepEqual(
      Object.keys(gate).sort(),
      ['blocking', 'evidence', 'id', 'kind', 'status', 'verifiedAt'],
      gate.id,
    );
    assert.match(gate.kind, /^(automatic|manual|human)$/);
    assert.match(gate.status, /^(passed|pending|blocked)$/);
    assert.equal(gate.blocking, true, gate.id);
    assert.ok(Array.isArray(gate.evidence), gate.id);
  }
});

test('accessibility gate declares the basic automated review contract', async () => {
  const evidence = await readJson('docs/design-system/closeout-evidence.json');
  const gate = evidence.gates.find(({ id }) => id === 'accessibility-insights');

  assert.equal(gate.kind, 'automatic');
  assert.deepEqual(evidence.accessibilityReview, {
    kind: 'basic-automated-review',
    surfaces: ['laboratory', 'pilot', 'revealed-states'],
    requiredFailedRules: 0,
    requiredFailedInstances: 0,
  });
  assert.doesNotMatch(JSON.stringify({
    review: evidence.accessibilityReview,
    evidence: gate.evidence,
  }), /FastPass|WCAG/i);
});

test('malformed, reordered, duplicate and missing gate arrays fail closed', () => {
  const gates = REQUIRED_GATES.map((id) => ({ id }));
  assert.throws(() => assertExactGateIds(gates.slice(0, -1)));
  assert.throws(() => assertExactGateIds([...gates, { id: 'unexpected' }]));
  assert.throws(() => assertExactGateIds([...gates.slice(0, -1), { id: 'static' }]));
  assert.throws(() => assertExactGateIds([...gates].reverse()));
});

test('passed gates require fresh timestamps and non-historical evidence', async () => {
  const evidence = await readJson('docs/design-system/closeout-evidence.json');
  const generatedAt = Date.parse(`${evidence.generatedAt}T00:00:00Z`);

  for (const gate of evidence.gates) {
    if (gate.status === 'passed') {
      assert.match(gate.verifiedAt, /^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/);
      assert.ok(Date.parse(gate.verifiedAt) >= generatedAt, gate.id);
      assert.ok(gate.evidence.length > 0, gate.id);
      assert.doesNotMatch(gate.evidence.join(' '), /superseded|historical/i);
    } else {
      assert.equal(gate.verifiedAt, null, gate.id);
    }
  }
  assert.doesNotMatch(JSON.stringify(evidence.gates), /\bPASS\b/);
});

test('git-preservation is passed in the activated closeout', async () => {
  const evidence = await readJson('docs/design-system/closeout-evidence.json');
  const gate = evidence.gates.find(({ id }) => id === 'git-preservation');
  assert.equal(gate.status, 'passed');
  assert.equal(gate.evidence.length, 1);
  assert.equal(gate.evidence[0].commandId, 'ds.git-preservation.v1');
  assert.equal(gate.evidence[0].artifact, 'docs/design-system/evidence/git-preservation.json');
  assert.match(gate.evidence[0].summary, /Objective receipt/i);
});

test('closeout surfaces defer transient numeric success claims to the final closer', async () => {
  const [closeout, validationLog] = await Promise.all([
    read('docs/design-system/closeout-evidence.json'),
    read('goals/design-system-nucleo-gobernanza/validation-log.md'),
  ]);
  const activeEvidence = `${closeout}\n${validationLog}`;
  assert.doesNotMatch(activeEvidence, /\b\d+\s*(?:\/|of)\s*\d+\b/i);
  assert.doesNotMatch(activeEvidence, /\bPASS\b/);
  assert.doesNotMatch(
    activeEvidence,
    /(?:completed|completaron|reported|reportó|exited|terminó|terminaron)[^.\n|]{0,48}(?:without failure|sin fallos?|zero|cero)/i,
  );
});

test('keyboard and reflow remain evidence and are not closeout gate objects', async () => {
  const evidence = await readJson('docs/design-system/closeout-evidence.json');
  const ids = evidence.gates.map(({ id }) => id);
  assert.equal(ids.includes('keyboard'), false);
  assert.equal(ids.includes('voiceover'), false);
  assert.equal(ids.includes('zoom-reflow'), false);
  assert.equal(ids.includes('plannotator'), false);
});

test('stable 1.0.0 cannot be declared while a closeout gate is unresolved', async () => {
  const [version, evidence] = await Promise.all([
    readJson('docs/design-system/version.json'),
    readJson('docs/design-system/closeout-evidence.json'),
  ]);
  const unresolved = evidence.gates.filter(({ status }) => status !== 'passed');
  if (version.status === 'stable' || version.version === '1.0.0') {
    assert.deepEqual(unresolved, [], JSON.stringify(unresolved, null, 2));
  } else {
    assert.ok(unresolved.length > 0, 'construction must identify unresolved gates');
  }
});
