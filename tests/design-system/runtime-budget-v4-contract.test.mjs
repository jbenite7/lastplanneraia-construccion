import assert from 'node:assert/strict';
import { execFileSync, spawnSync } from 'node:child_process';
import {
  mkdirSync,
  mkdtempSync,
  existsSync,
  readFileSync,
  rmSync,
  writeFileSync,
} from 'node:fs';
import { tmpdir } from 'node:os';
import path from 'node:path';
import test from 'node:test';
import { fileURLToPath } from 'node:url';

import { readWorktreeProvenance } from '../../scripts/design-system-ci-preflight.mjs';
import {
  validateAssets,
  validateRetrospectiveRecoveryManifest,
} from '../../scripts/design-system-runtime-budget-provenance.mjs';
import { validateRuntimeBudgetArtifact } from '../../scripts/design-system-runtime-budget.mjs';
import { currentSamples, RUNTIME_CONTEXT } from './runtime-budget-fixtures.mjs';

const BASELINE = JSON.parse(readFileSync(
  new URL('../../docs/design-system/runtime-baseline-0.3.3.json', import.meta.url),
  'utf8',
));
const AGGREGATE_CLI = fileURLToPath(new URL(
  '../../scripts/design-system-runtime-budget-aggregate.mjs',
  import.meta.url,
));
const PROVENANCE_MODULE_URL = new URL(
  '../../scripts/design-system-runtime-budget-provenance.mjs',
  import.meta.url,
).href;

function runtimeContext(provenance) {
  return {
    ciRunId: RUNTIME_CONTEXT.ciRunId,
    gitHead: provenance.gitSha,
    worktreeClean: true,
    sourceTreeHash: provenance.worktreeFingerprint,
    fixtureSha256: provenance.fixtureSha256,
  };
}

function samplesBoundTo(context) {
  return structuredClone(currentSamples()).map((sample) => ({
    ...sample,
    ciRunId: context.ciRunId,
    sourceRef: context.gitHead,
    sourceTreeHash: context.sourceTreeHash,
    fixtureSha256: context.fixtureSha256,
    provenance: {
      ...sample.provenance,
      runtime: {
        ciRunId: context.ciRunId,
        gitHead: context.gitHead,
        worktreeClean: true,
        sourceTreeSha256: context.sourceTreeHash,
        fixtureSha256: context.fixtureSha256,
      },
    },
  }));
}

function ciEnvironment(provenance, overrides = {}) {
  return {
    ...process.env,
    CI_RUN_ID: RUNTIME_CONTEXT.ciRunId,
    CI_GIT_SHA: provenance.gitSha,
    CI_WORKTREE_FINGERPRINT: provenance.worktreeFingerprint,
    CI_FIXTURE_SHA256: provenance.fixtureSha256,
    ...overrides,
  };
}

function runAggregation(repo, artifacts, name, samplePaths, env) {
  return spawnSync(
    process.execPath,
    [AGGREGATE_CLI, path.join(artifacts, `${name}.json`), ...samplePaths],
    { cwd: repo, env, encoding: 'utf8' },
  );
}

test('production CLI derives clean and dirty state from a no-local clean clone', (t) => {
  const temporary = mkdtempSync(path.join(tmpdir(), 'runtime-budget-contract-'));
  const origin = path.join(temporary, 'origin');
  const repo = path.join(temporary, 'repo');
  const artifacts = path.join(temporary, 'artifacts');
  t.after(() => rmSync(temporary, { recursive: true, force: true }));
  mkdirSync(path.join(origin, 'database/fixtures'), { recursive: true });
  mkdirSync(path.join(origin, 'docs/design-system/runtime-measurements'), { recursive: true });
  mkdirSync(artifacts, { recursive: true });
  writeFileSync(path.join(origin, 'database/fixtures/design-system-ci.sql'), 'SELECT 73;\n');
  writeFileSync(path.join(origin, 'runtime-input.txt'), 'committed runtime input\n');
  for (const relativePath of [
    'docs/design-system/runtime-baseline-0.3.3.json',
    'docs/design-system/runtime-measurements/0.3.3-retrospective.json',
    'docs/design-system/runtime-measurements/0.3.3-recovery-manifest.json',
  ]) {
    writeFileSync(
      path.join(origin, relativePath),
      readFileSync(new URL(`../../${relativePath}`, import.meta.url)),
    );
  }
  execFileSync('git', ['init', '--quiet'], { cwd: origin });
  execFileSync('git', ['add', '.'], { cwd: origin });
  execFileSync('git', [
    '-c', 'user.name=Runtime Contract',
    '-c', 'user.email=runtime-contract@example.invalid',
    'commit', '--quiet', '-m', 'temporary fixture',
  ], { cwd: origin });
  execFileSync('git', ['clone', '--quiet', '--no-local', origin, repo]);

  const hiddenCheckpoint = spawnSync(
    'git', ['cat-file', '-e', '25f2787332117ed93416ffc42e6fac8b037dce94^{tree}'],
    { cwd: repo, encoding: 'utf8' },
  );
  assert.notEqual(hiddenCheckpoint.status, 0, 'clean clone must not contain the local checkpoint tree');
  const portableRecovery = spawnSync(process.execPath, ['--input-type=module', '-e', `
    import { readFileSync } from 'node:fs';
    const { validateApprovedBaselineProvenance } = await import(${JSON.stringify(PROVENANCE_MODULE_URL)});
    const baseline = JSON.parse(readFileSync('docs/design-system/runtime-baseline-0.3.3.json', 'utf8'));
    validateApprovedBaselineProvenance(baseline);
  `], { cwd: repo, encoding: 'utf8' });
  assert.equal(portableRecovery.status, 0, `${portableRecovery.stdout}\n${portableRecovery.stderr}`);

  const clean = readWorktreeProvenance(repo);
  const samples = samplesBoundTo(runtimeContext(clean));
  const samplePaths = samples.map((sample, index) => {
    const samplePath = path.join(artifacts, `sample-${index + 1}.json`);
    writeFileSync(samplePath, `${JSON.stringify(sample, null, 2)}\n`);
    return samplePath;
  });

  const cleanResult = runAggregation(
    repo,
    artifacts,
    'clean-aggregate',
    samplePaths,
    ciEnvironment(clean),
  );
  assert.equal(cleanResult.status, 0, cleanResult.stderr);

  const cleanFixtureMismatch = runAggregation(
    repo,
    artifacts,
    'clean-forged-fixture',
    samplePaths,
    ciEnvironment(clean, { CI_FIXTURE_SHA256: 'f'.repeat(64) }),
  );
  assert.equal(cleanFixtureMismatch.status, 1);
  assert.match(cleanFixtureMismatch.stderr, /CI_FIXTURE_SHA256 must match the current clean worktree/);

  writeFileSync(path.join(repo, 'runtime-input.txt'), 'forged dirty runtime input\n');
  const dirty = readWorktreeProvenance(repo);
  const forgedCleanResult = runAggregation(
    repo,
    artifacts,
    'dirty-forged-clean',
    samplePaths,
    ciEnvironment(clean),
  );
  assert.equal(forgedCleanResult.status, 1);
  assert.match(forgedCleanResult.stderr, /CI_WORKTREE_FINGERPRINT must match the current clean worktree/);

  const actualDirtyResult = runAggregation(
    repo,
    artifacts,
    'dirty-actual-fingerprint',
    samplePaths,
    ciEnvironment(dirty),
  );
  assert.equal(actualDirtyResult.status, 1);
  assert.match(actualDirtyResult.stderr, /current release measurements require a clean worktree/);
});

test('production modules expose no injectable ForTest runtime identity seam', async () => {
  const moduleUrls = [
    new URL('../../scripts/design-system-runtime-budget-aggregate.mjs', import.meta.url),
    new URL('../../scripts/design-system-runtime-budget.mjs', import.meta.url),
    new URL('../../scripts/design-system-runtime-budget-provenance.mjs', import.meta.url),
  ];
  const modules = await Promise.all(moduleUrls.map((url) => import(url)));
  for (const module of modules) {
    assert.deepEqual(Object.keys(module).filter((name) => name.includes('ForTest')), []);
  }

  const spoofProbe = spawnSync(process.execPath, ['--input-type=module', '-e', `
    const modules = await Promise.all(${JSON.stringify(moduleUrls.map(({ href }) => href))}.map((url) => import(url)));
    const injectable = modules.flatMap((module) => Object.keys(module).filter((name) => name.includes('ForTest')));
    if (injectable.length) throw new Error('injectable exports: ' + injectable.join(','));
  `], {
    encoding: 'utf8',
    env: { ...process.env, NODE_TEST_CONTEXT: 'child-v8' },
  });
  assert.equal(spoofProbe.status, 0, `${spoofProbe.stdout}\n${spoofProbe.stderr}`);
});

test('manual baseline and recovery manifest validation reject malformed fields', () => {
  const manifest = JSON.parse(readFileSync(
    new URL('../../docs/design-system/runtime-measurements/0.3.3-recovery-manifest.json', import.meta.url),
    'utf8',
  ));
  assert.throws(
    () => validateRetrospectiveRecoveryManifest({ ...manifest, versionEvidence: 42 }),
    /versionEvidence must be a string/,
  );
  assert.throws(
    () => validateRuntimeBudgetArtifact({ ...BASELINE, reason: 42 }),
    /reason must be a string/,
  );
  assert.throws(
    () => validateRuntimeBudgetArtifact({
      ...BASELINE,
      approval: { ...BASELINE.approval, reason: 42 },
    }),
    /baseline approval contains unexpected properties: reason/,
  );
});

test('schema and manual asset validation share identical-entry uniqueness semantics', () => {
  const schema = JSON.parse(readFileSync(
    new URL('../../docs/design-system/runtime-budget.schema.json', import.meta.url),
    'utf8',
  ));
  assert.equal(schema.$defs.assets.uniqueItems, true);

  const assets = currentSamples()[0].provenance.assets;
  assert.throws(
    () => validateAssets([...assets, structuredClone(assets[0])]),
    /duplicate asset entries/,
  );
});

test('receipt schema states integrity scope without claiming authenticity', () => {
  const schema = JSON.parse(readFileSync(
    new URL('../../docs/design-system/runtime-budget.schema.json', import.meta.url),
    'utf8',
  ));
  const receiptText = JSON.stringify(schema.$defs.currentSampling.properties);
  assert.match(receiptText, /partial or accidental alteration/i);
  assert.match(receiptText, /external CI attestation/i);
});

test('retrospective baseline uses a portable receipt and makes no origin Git recovery claim', () => {
  const manifestUrl = new URL(
    '../../docs/design-system/runtime-measurements/0.3.3-recovery-manifest.json',
    import.meta.url,
  );
  assert.equal(BASELINE.sourceRef, null);
  assert.equal(existsSync(manifestUrl), true);
  const manifest = JSON.parse(readFileSync(manifestUrl, 'utf8'));
  assert.equal(manifest.status, 'retrospective-incomplete');
  assert.equal(manifest.sourceHistory.originCommitAvailable, false);
  assert.equal(manifest.sourceHistory.recoveryFromOriginGitHistory, false);
  assert.equal(manifest.rawSamplesPreserved, false);
});
