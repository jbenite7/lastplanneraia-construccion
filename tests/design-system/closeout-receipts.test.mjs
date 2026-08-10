import assert from 'node:assert/strict';
import {
  readFileSync, rmSync, writeFileSync,
} from 'node:fs';
import path from 'node:path';
import test from 'node:test';

import {
  closeoutContractFailures, closeoutGateIds,
} from '../../scripts/design-system-closeout-contract.mjs';
import { evidenceReceiptFailures } from '../../scripts/design-system-evidence-receipt.mjs';
import {
  gateCommandRegistry,
} from '../../scripts/design-system-gate-command-registry.mjs';
import {
  removeFixture, runContracts, updateJson,
} from './closeout-contract-fixture.mjs';
import {
  createActivatedFixture, git, sha256, writeArtifact,
} from './activated-closeout-fixture.mjs';

let fixtureRoot;
let canonicalCloseout;
let canonicalStaticArtifact;
let canonicalFixture;

test.before(() => {
  fixtureRoot = createActivatedFixture();
  canonicalCloseout = readFileSync(
    path.join(fixtureRoot, 'docs/design-system/closeout-evidence.json'),
  );
  canonicalStaticArtifact = readFileSync(
    path.join(fixtureRoot, 'docs/design-system/evidence/static.json'),
  );
  canonicalFixture = readFileSync(
    path.join(fixtureRoot, 'database/fixtures/design-system-ci.sql'),
  );
});

test.beforeEach(() => {
  writeFileSync(path.join(fixtureRoot, 'docs/design-system/closeout-evidence.json'), canonicalCloseout);
  writeFileSync(path.join(fixtureRoot, 'docs/design-system/evidence/static.json'), canonicalStaticArtifact);
  writeFileSync(path.join(fixtureRoot, 'database/fixtures/design-system-ci.sql'), canonicalFixture);
});

test.after(() => removeFixture(fixtureRoot));

function mutateFirstReceipt(fixtureRoot, mutate) {
  updateJson(fixtureRoot, 'docs/design-system/closeout-evidence.json', (closeout) => {
    mutate(closeout.gates[0].evidence[0]);
  });
}

function readFixtureJson(relativePath) {
  return JSON.parse(readFileSync(path.join(fixtureRoot, relativePath)));
}

function gateReceiptFailures(id, fixtureRequired = false) {
  const closeout = readFixtureJson('docs/design-system/closeout-evidence.json');
  const gate = closeout.gates.find((candidate) => candidate.id === id);
  return evidenceReceiptFailures(
    fixtureRoot,
    { id, surface: null, fixtureRequired },
    gate.evidence[0],
  );
}

const staticReceiptFailures = () => gateReceiptFailures('static');
const fixtureReceiptFailures = () => gateReceiptFailures('runtime', true);

function closeoutFailures() {
  return closeoutContractFailures({
    root: fixtureRoot,
    closeout: readFixtureJson('docs/design-system/closeout-evidence.json'),
    stableApi: readFixtureJson('docs/design-system/stable-api-1.0.0.json'),
    versionDocument: readFixtureJson('docs/design-system/version.json'),
    now: new Date('2026-07-15T22:00:00Z'),
  });
}

test('uncommitted activation cannot pass in a temporary Git repository', () => {
  const result = runContracts(fixtureRoot);
  assert.notEqual(result.status, 0);
  assert.match(result.stderr, /activation: worktree and index must be clean/);
  assert.match(result.stderr, /activation: docs\/design-system\/closeout-evidence.json must match HEAD exactly/);
  assert.match(result.stderr, /activation: HEAD must contain the complete passed activation/);
});

test('una version posterior a 1.0.0 sigue contando como sistema activado', () => {
  // D2 (spec 2026-08-04): la activacion fue un hito UNICO cumplido en 1.0.0. Los
  // gates no deben volver a exigirla en cada bump, pero SI deben seguir rechazando
  // versiones pre-1.0.0 o no estables.
  const versionDocument = readFixtureJson('docs/design-system/version.json');
  const conVersion = (version, status = 'stable') => closeoutContractFailures({
    root: fixtureRoot,
    closeout: readFixtureJson('docs/design-system/closeout-evidence.json'),
    stableApi: readFixtureJson('docs/design-system/stable-api-1.0.0.json'),
    versionDocument: { ...versionDocument, version, status },
    now: new Date('2026-07-15T22:00:00Z'),
  });
  const noActivada = /gates, version and stable API must activate together/;

  assert.deepEqual(conVersion('1.1.0'), conVersion('1.0.0'));
  assert.deepEqual(conVersion('2.3.4'), conVersion('1.0.0'));
  assert.ok(!conVersion('1.1.0').some((f) => noActivada.test(f)));

  // El gate no se ha vuelto permisivo: lo que no es activacion sigue fallando.
  assert.ok(conVersion('0.9.0').some((f) => noActivada.test(f)));
  assert.ok(conVersion('1.1.0', 'draft').some((f) => noActivada.test(f)));
});

test('committed structured receipts activate in a clean temporary Git repository', () => {
  const sourceRef = readFixtureJson('docs/design-system/closeout-evidence.json').gates[0].evidence[0].sourceRef;
  git(fixtureRoot, ['add', '.']);
  git(fixtureRoot, ['commit', '-m', 'activate release']);
  assert.notEqual(sourceRef, git(fixtureRoot, ['rev-parse', 'HEAD']));
  const result = runContracts(fixtureRoot);
  assert.equal(result.status, 0, result.stderr);
});

test('the canonical command registry covers the exact closeout gates', () => {
  assert.deepEqual([...gateCommandRegistry.keys()], closeoutGateIds);
});

test('arbitrary string evidence cannot activate a gate', () => {
  updateJson(fixtureRoot, 'docs/design-system/closeout-evidence.json', (closeout) => {
    closeout.gates[0].evidence = ['trust me'];
  });
  assert.match(staticReceiptFailures().join('\n'), /static: evidence receipt must be an object/);
});

test('a nonexistent artifact cannot activate a gate', () => {
  mutateFirstReceipt(fixtureRoot, (receipt) => { receipt.artifact = 'docs/design-system/evidence/missing.json'; });
  assert.match(staticReceiptFailures().join('\n'), /static: evidence artifact is missing/);
});

test('an artifact path traversal cannot activate a gate', () => {
  mutateFirstReceipt(fixtureRoot, (receipt) => { receipt.artifact = 'docs/design-system/evidence/../../.env'; });
  assert.match(staticReceiptFailures().join('\n'), /static: invalid persistent evidence path/);
});

test('an artifact hash mismatch cannot activate a gate', () => {
  mutateFirstReceipt(fixtureRoot, (receipt) => { receipt.artifactSha256 = 'f'.repeat(64); });
  assert.match(staticReceiptFailures().join('\n'), /static: artifactSha256 does not match/);
});

test('a nonzero command exit cannot activate a gate', () => {
  mutateFirstReceipt(fixtureRoot, (receipt) => { receipt.exitCode = 1; });
  assert.match(staticReceiptFailures().join('\n'), /static: evidence exitCode must be zero/);
});

test('an arbitrary command cannot activate a gate', () => {
  mutateFirstReceipt(fixtureRoot, (receipt) => { receipt.command = 'trust me'; });
  assert.match(staticReceiptFailures().join('\n'), /static: evidence command is not canonical/);
});

test('an arbitrary command registry ID cannot activate a gate', () => {
  mutateFirstReceipt(fixtureRoot, (receipt) => { receipt.commandId = 'trust-me.v1'; });
  assert.match(staticReceiptFailures().join('\n'), /static: evidence command is not canonical/);
});

test('a dirty source tree cannot pass with committed activation documents', () => {
  const dirtyPath = path.join(fixtureRoot, 'dirty-source.txt');
  const previousStrict = process.env.DS_ACTIVATION_STRICT;
  process.env.DS_ACTIVATION_STRICT = '1';
  try {
    writeFileSync(dirtyPath, 'uncommitted source change\n');
    assert.match(closeoutFailures().join('\n'), /activation: worktree and index must be clean/);
  } finally {
    rmSync(dirtyPath, { force: true });
    if (previousStrict === undefined) {
      delete process.env.DS_ACTIVATION_STRICT;
    } else {
      process.env.DS_ACTIVATION_STRICT = previousStrict;
    }
  }
});

test('a staged source change cannot pass with committed activation documents', () => {
  writeFileSync(path.join(fixtureRoot, 'staged-source.txt'), 'staged source change\n');
  git(fixtureRoot, ['add', 'staged-source.txt']);
  const previousStrict = process.env.DS_ACTIVATION_STRICT;
  process.env.DS_ACTIVATION_STRICT = '1';
  try {
    assert.match(closeoutFailures().join('\n'), /activation: worktree and index must be clean/);
  } finally {
    git(fixtureRoot, ['commit', '-m', 'preserve staged test source']);
    if (previousStrict === undefined) {
      delete process.env.DS_ACTIVATION_STRICT;
    } else {
      process.env.DS_ACTIVATION_STRICT = previousStrict;
    }
  }
});

test('a fake sourceRef cannot activate a gate', () => {
  mutateFirstReceipt(fixtureRoot, (receipt) => { receipt.sourceRef = '0'.repeat(40); });
  assert.match(staticReceiptFailures().join('\n'), /static: sourceRef must resolve to a Git commit/);
});

test('a source fingerprint mismatch cannot activate a gate', () => {
  mutateFirstReceipt(fixtureRoot, (receipt) => { receipt.sourceFingerprint = 'f'.repeat(64); });
  assert.match(staticReceiptFailures().join('\n'), /static: sourceFingerprint does not match sourceRef/);
});

test('a changed fixture cannot activate an applicable gate', () => {
  const fixture = 'database/fixtures/design-system-ci.sql';
  writeFileSync(path.join(fixtureRoot, fixture), '-- changed fixture\n');
  assert.match(fixtureReceiptFailures().join('\n'), /runtime: fixtureSha256 does not match/);
});

test('a future verification timestamp cannot activate a gate', () => {
  updateJson(fixtureRoot, 'docs/design-system/closeout-evidence.json', (closeout) => {
    closeout.gates[0].verifiedAt = '2099-01-01T00:00:00Z';
  });
  assert.match(closeoutFailures().join('\n'), /static: verifiedAt is too far in the future/);
});

test('an artifact changed after its sourceRef cannot activate a gate', () => {
  const artifact = 'docs/design-system/evidence/static.json';
  writeArtifact(fixtureRoot, artifact, { gateId: 'static', result: 'changed' });
  mutateFirstReceipt(fixtureRoot, (receipt) => {
    receipt.artifactSha256 = sha256(readFileSync(path.join(fixtureRoot, artifact)));
  });
  assert.match(staticReceiptFailures().join('\n'), /static: evidence artifact is stale relative to sourceRef/);
});

test('a receipt missing a required field cannot activate a gate', () => {
  mutateFirstReceipt(fixtureRoot, (receipt) => { delete receipt.command; });
  assert.match(staticReceiptFailures().join('\n'), /static: evidence receipt fields are incomplete or unexpected/);
});
