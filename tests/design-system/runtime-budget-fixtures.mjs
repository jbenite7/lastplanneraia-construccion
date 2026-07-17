import { execFileSync } from 'node:child_process';
import { createHash } from 'node:crypto';
import {
  mkdirSync,
  mkdtempSync,
  readFileSync,
  rmSync,
  writeFileSync,
} from 'node:fs';
import { tmpdir } from 'node:os';
import path from 'node:path';

import { aggregateRuntimeBudgetSamples } from '../../scripts/design-system-runtime-budget-aggregate.mjs';
import { readWorktreeProvenance } from '../../scripts/design-system-ci-preflight.mjs';

export const APPROVED_BASELINE = JSON.parse(readFileSync(
  new URL('../../docs/design-system/runtime-baseline-0.3.3.json', import.meta.url),
  'utf8',
));
const RETROSPECTIVE_MEASUREMENT = readFileSync(
  new URL('../../docs/design-system/runtime-measurements/0.3.3-retrospective.json', import.meta.url),
);
const RECOVERY_MANIFEST = readFileSync(
  new URL('../../docs/design-system/runtime-measurements/0.3.3-recovery-manifest.json', import.meta.url),
);

const TEMPORARY_ROOT = mkdtempSync(path.join(tmpdir(), 'runtime-budget-fixtures-'));
const RUNTIME_REPO = path.join(TEMPORARY_ROOT, 'repo');
mkdirSync(path.join(RUNTIME_REPO, 'database/fixtures'), { recursive: true });
mkdirSync(path.join(RUNTIME_REPO, 'docs/design-system/runtime-measurements'), { recursive: true });
writeFileSync(path.join(RUNTIME_REPO, 'database/fixtures/design-system-ci.sql'), 'SELECT 73;\n');
writeFileSync(
  path.join(RUNTIME_REPO, 'docs/design-system/runtime-measurements/0.3.3-retrospective.json'),
  RETROSPECTIVE_MEASUREMENT,
);
writeFileSync(
  path.join(RUNTIME_REPO, 'docs/design-system/runtime-measurements/0.3.3-recovery-manifest.json'),
  RECOVERY_MANIFEST,
);
writeFileSync(path.join(RUNTIME_REPO, 'runtime-input.txt'), 'committed runtime input\n');
execFileSync('git', ['init', '--quiet'], { cwd: RUNTIME_REPO });
execFileSync('git', ['add', '.'], { cwd: RUNTIME_REPO });
execFileSync('git', [
  '-c', 'user.name=Runtime Fixtures',
  '-c', 'user.email=runtime-fixtures@example.invalid',
  'commit', '--quiet', '-m', 'temporary fixture',
], { cwd: RUNTIME_REPO });
process.once('exit', () => rmSync(TEMPORARY_ROOT, { recursive: true, force: true }));
const WORKTREE = readWorktreeProvenance(RUNTIME_REPO);
const SOURCE_REF = WORKTREE.gitSha;
const ASSETS = [{
  path: '/css/design-system.css',
  type: 'css',
  rawBytes: 4096,
  gzipBytes: 1000,
  sha256: 'a'.repeat(64),
}];
const ASSET_INVENTORY_SHA256 = createHash('sha256').update(JSON.stringify(ASSETS)).digest('hex');
const SOURCE_TREE_HASH = WORKTREE.worktreeFingerprint;
const FIXTURE_SHA256 = WORKTREE.fixtureSha256;

export const RUNTIME_CONTEXT = {
  ciRunId: 'run-runtime-contract-a1',
  gitHead: SOURCE_REF,
  worktreeClean: true,
  sourceTreeHash: SOURCE_TREE_HASH,
  fixtureSha256: FIXTURE_SHA256,
};

export function withRuntimeEnvironment(callback) {
  const previousCwd = process.cwd();
  const keys = ['CI_RUN_ID', 'CI_GIT_SHA', 'CI_WORKTREE_FINGERPRINT', 'CI_FIXTURE_SHA256'];
  const previousEnvironment = Object.fromEntries(keys.map((key) => [key, process.env[key]]));
  process.chdir(RUNTIME_REPO);
  Object.assign(process.env, {
    CI_RUN_ID: RUNTIME_CONTEXT.ciRunId,
    CI_GIT_SHA: RUNTIME_CONTEXT.gitHead,
    CI_WORKTREE_FINGERPRINT: RUNTIME_CONTEXT.sourceTreeHash,
    CI_FIXTURE_SHA256: RUNTIME_CONTEXT.fixtureSha256,
  });
  try {
    return callback();
  } finally {
    process.chdir(previousCwd);
    for (const key of keys) {
      if (previousEnvironment[key] === undefined) delete process.env[key];
      else process.env[key] = previousEnvironment[key];
    }
  }
}

export function currentSamples(overrides = {}) {
  const now = Date.now();
  const metrics = overrides.metrics ?? {
    cssGzipBytes: APPROVED_BASELINE.metrics.cssGzipBytes + APPROVED_BASELINE.tolerances.cssGzipBytes,
    jsGzipBytes: APPROVED_BASELINE.metrics.jsGzipBytes + APPROVED_BASELINE.tolerances.jsGzipBytes,
    adapterAssets: [...APPROVED_BASELINE.metrics.adapterAssets],
    duplicateRequestCount: APPROVED_BASELINE.metrics.duplicateRequestCount,
    themeFlashCount: 0,
    initializationMs: APPROVED_BASELINE.metrics.initializationMs + APPROVED_BASELINE.tolerances.initializationMs,
    handsontableInteractionMs: APPROVED_BASELINE.metrics.handsontableInteractionMs
      + APPROVED_BASELINE.tolerances.handsontableInteractionMs,
    laboratoryAssets: [],
  };
  return [1, 2, 3].map((index) => ({
    schemaVersion: 1,
    kind: 'sample',
    measurementKind: 'current',
    status: 'sampled',
    designSystemVersion: '0.3.6',
    route: '/programa-general',
    viewport: '1440x900',
    theme: 'dark',
    density: 'compact',
    fixture: 'sanitized-pilot-v1',
    ciRunId: RUNTIME_CONTEXT.ciRunId,
    sourceRef: SOURCE_REF,
    sourceTreeHash: SOURCE_TREE_HASH,
    fixtureSha256: FIXTURE_SHA256,
    recordedAt: new Date(now - (4 - index) * 1000).toISOString(),
    metrics,
    provenance: {
      assets: ASSETS,
      assetInventorySha256: ASSET_INVENTORY_SHA256,
      htmlSha256: String(index).repeat(64),
      runtime: {
        ciRunId: RUNTIME_CONTEXT.ciRunId,
        gitHead: SOURCE_REF,
        worktreeClean: true,
        sourceTreeSha256: SOURCE_TREE_HASH,
        fixtureSha256: FIXTURE_SHA256,
      },
    },
    ...overrides.context,
  }));
}

export function currentMeasurement(overrides = {}) {
  const context = overrides.context ?? {};
  const metrics = {
    cssGzipBytes: APPROVED_BASELINE.metrics.cssGzipBytes + APPROVED_BASELINE.tolerances.cssGzipBytes,
    jsGzipBytes: APPROVED_BASELINE.metrics.jsGzipBytes + APPROVED_BASELINE.tolerances.jsGzipBytes,
    adapterAssets: [...APPROVED_BASELINE.metrics.adapterAssets],
    duplicateRequestCount: APPROVED_BASELINE.metrics.duplicateRequestCount,
    themeFlashCount: 0,
    initializationMs: APPROVED_BASELINE.metrics.initializationMs + APPROVED_BASELINE.tolerances.initializationMs,
    handsontableInteractionMs: APPROVED_BASELINE.metrics.handsontableInteractionMs
      + APPROVED_BASELINE.tolerances.handsontableInteractionMs,
    laboratoryAssets: [],
    ...overrides.metrics,
  };
  const samples = currentSamples({ metrics, context });
  return withRuntimeEnvironment(() => aggregateRuntimeBudgetSamples(samples));
}
