import assert from 'node:assert/strict';
import test from 'node:test';

import {
  createCloseoutFixture, removeFixture, runContracts, updateJson,
} from './closeout-contract-fixture.mjs';

function runFixture(mutate) {
  const fixtureRoot = createCloseoutFixture();
  mutate(fixtureRoot);
  const result = runContracts(fixtureRoot);
  removeFixture(fixtureRoot);
  return result;
}

test('executable contract rejects a reordered or duplicate closeout array', () => {
  for (const mutate of [
    (gates) => gates.reverse(),
    (gates) => gates.splice(-1, 1, { ...gates[0] }),
  ]) {
    const result = runFixture((fixtureRoot) => updateJson(
      fixtureRoot,
      'docs/design-system/closeout-evidence.json',
      (closeout) => mutate(closeout.gates),
    ));
    assert.notEqual(result.status, 0);
    assert.match(result.stderr, /closeout: gates must be the exact ordered blocking set/);
  }
});

test('executable contract rejects passed gates without fresh evidence metadata', () => {
  for (const invalidMetadata of [
    { verifiedAt: null, evidence: [] },
    { verifiedAt: '2026-07-14T23:59:59Z', evidence: ['Fresh final evidence.'] },
    { verifiedAt: '2026-07-15T23:00:00Z', evidence: ['Superseded historical result.'] },
  ]) {
    const result = runFixture((fixtureRoot) => updateJson(
      fixtureRoot,
      'docs/design-system/closeout-evidence.json',
      (closeout) => Object.assign(closeout.gates[0], {
        status: 'passed',
        ...invalidMetadata,
      }),
    ));
    assert.notEqual(result.status, 0);
    assert.match(
      result.stderr,
      /static: (?:passed requires fresh verifiedAt and structured evidence|evidence receipt must be an object)/,
    );
  }
});

test('executable contract requires simultaneous release activation', () => {
  const result = runFixture((fixtureRoot) => {
    updateJson(fixtureRoot, 'docs/design-system/version.json', (version) => {
      version.version = '1.0.0';
      version.status = 'stable';
    });
    updateJson(fixtureRoot, 'docs/design-system/stable-api-1.0.0.json', (stableApi) => {
      stableApi.releaseStatus = 'guaranteed';
    });
  });
  assert.notEqual(result.status, 0);
  assert.match(result.stderr, /activation: gates, version and stable API must activate together/);
});

test('executable contract rejects a gate with the wrong kind', () => {
  const result = runFixture((fixtureRoot) => updateJson(
    fixtureRoot,
    'docs/design-system/closeout-evidence.json',
    (closeout) => {
      closeout.gates.find(({ id }) => id === 'full-app-flow').kind = 'manual';
    },
  ));
  assert.notEqual(result.status, 0);
  assert.match(result.stderr, /full-app-flow: invalid kind manual/);
});
