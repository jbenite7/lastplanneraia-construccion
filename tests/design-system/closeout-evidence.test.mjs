import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';

const readJson = async (path) => JSON.parse(await readFile(
  new URL(`../../${path}`, import.meta.url),
  'utf8',
));

const REQUIRED_GATES = [
  'static', 'runtime', 'phpstan-scoped', 'phpstan-global',
  'runtime-budgets',
  'global-table-safety', 'pg-roles', 'pg-persistence', 'data-restoration',
  'accessibility-insights', 'keyboard', 'voiceover', 'zoom-reflow',
  'consolidated-lab', 'consolidated-pilot', 'git-preservation',
  'plannotator', 'atomic-commit',
];

test('closeout evidence declares every blocking automatic and manual gate', async () => {
  const evidence = await readJson('docs/design-system/closeout-evidence.json');
  assert.equal(evidence.schemaVersion, 1);
  assert.match(evidence.designSystemVersion, /^\d+\.\d+\.\d+$/);
  const ids = evidence.gates.map(({ id }) => id);
  assert.deepEqual(ids.sort(), [...REQUIRED_GATES].sort());
  for (const gate of evidence.gates) {
    assert.match(gate.kind, /^(automatic|manual|human)$/);
    assert.match(gate.status, /^(passed|pending|blocked)$/);
    assert.equal(gate.blocking, true, gate.id);
    assert.ok(Array.isArray(gate.evidence), gate.id);
  }
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
