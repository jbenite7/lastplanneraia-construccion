import assert from 'node:assert/strict';
import { createHash } from 'node:crypto';
import { readFileSync } from 'node:fs';
import test from 'node:test';

import { aggregateRuntimeBudgetSamples } from '../../scripts/design-system-runtime-budget-aggregate.mjs';
import { compareRuntimeBudget } from '../../scripts/design-system-runtime-budget.mjs';
import { RUNTIME_CONTEXT, withRuntimeEnvironment } from './runtime-budget-fixtures.mjs';

const BASELINE = {
  ...JSON.parse(readFileSync(
    new URL('../../docs/design-system/runtime-baseline-0.3.3.json', import.meta.url),
    'utf8',
  )),
  density: 'compact',
};
const SOURCE_REF = RUNTIME_CONTEXT.gitHead;
const ASSETS = [{
  path: '/css/design-system.css',
  type: 'css',
  rawBytes: 4096,
  gzipBytes: 1000,
  sha256: 'a'.repeat(64),
}];
const ASSET_INVENTORY_SHA256 = createHash('sha256').update(JSON.stringify(ASSETS)).digest('hex');
const SOURCE_TREE_HASH = RUNTIME_CONTEXT.sourceTreeHash;
const FIXTURE_SHA256 = RUNTIME_CONTEXT.fixtureSha256;

function currentSample(index) {
  return {
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
    recordedAt: new Date(Date.now() - (4 - index) * 1000).toISOString(),
    metrics: {
      ...BASELINE.metrics,
      themeFlashCount: 0,
    },
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
  };
}

const validMeasurement = () => withRuntimeEnvironment(() => aggregateRuntimeBudgetSamples([
  currentSample(1), currentSample(2), currentSample(3),
]));

test('comparator accepts a recent aggregate with baseline recovery provenance', () => {
  // Given / When
  const result = withRuntimeEnvironment(() => compareRuntimeBudget(BASELINE, validMeasurement()));

  // Then
  assert.equal(result.pass, true, JSON.stringify(result.violations));
});

test('comparator rejects missing baseline or aggregate provenance', () => {
  // Given
  const { recovery, ...baselineWithoutProvenance } = BASELINE;
  const measurementWithoutProvenance = { ...validMeasurement() };
  delete measurementWithoutProvenance.provenance;

  // When / Then
  assert.throws(
    () => withRuntimeEnvironment(() => compareRuntimeBudget(baselineWithoutProvenance, validMeasurement())),
    /baseline recovery must be an object/,
  );
  assert.throws(
    () => withRuntimeEnvironment(() => compareRuntimeBudget(BASELINE, measurementWithoutProvenance)),
    /measurement provenance must be an object/,
  );
});

test('comparator rejects fake sources, stale samples and aggregate tampering', () => {
  // Given
  const fakeSource = validMeasurement();
  fakeSource.sourceRef = '0'.repeat(40);
  fakeSource.provenance.sampling.samples = fakeSource.provenance.sampling.samples.map((sample) => ({
    ...sample,
    sourceRef: '0'.repeat(40),
  }));
  const stale = validMeasurement();
  stale.recordedAt = '2000-01-01T00:00:03.000Z';
  stale.provenance.sampling.samples = stale.provenance.sampling.samples.map((sample, index) => ({
    ...sample,
    recordedAt: `2000-01-01T00:00:0${index + 1}.000Z`,
  }));
  const tampered = validMeasurement();
  tampered.metrics = { ...tampered.metrics, cssGzipBytes: tampered.metrics.cssGzipBytes - 1 };

  // When / Then
  assert.throws(() => withRuntimeEnvironment(() => compareRuntimeBudget(BASELINE, fakeSource)), /raw sample receipt mismatch/);
  assert.throws(() => withRuntimeEnvironment(() => compareRuntimeBudget(BASELINE, stale)), /raw sample receipt mismatch/);
  assert.throws(() => withRuntimeEnvironment(() => compareRuntimeBudget(BASELINE, tampered)), /aggregate metrics do not match sample medians/);
});
