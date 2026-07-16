import assert from 'node:assert/strict';
import { createHash } from 'node:crypto';
import test from 'node:test';

import { aggregateRuntimeBudgetSamples } from '../../scripts/design-system-runtime-budget-aggregate.mjs';
import { RUNTIME_CONTEXT, withRuntimeEnvironment } from './runtime-budget-fixtures.mjs';

const ASSETS = [
  {
    path: '/css/design-system.css',
    type: 'css',
    rawBytes: 4096,
    gzipBytes: 1000,
    sha256: 'a'.repeat(64),
  },
  {
    path: '/js/design-system.js',
    type: 'js',
    rawBytes: 8192,
    gzipBytes: 2000,
    sha256: 'b'.repeat(64),
  },
];
const ASSET_INVENTORY_SHA256 = createHash('sha256').update(JSON.stringify(ASSETS)).digest('hex');
const SOURCE_TREE_HASH = RUNTIME_CONTEXT.sourceTreeHash;
const FIXTURE_SHA256 = RUNTIME_CONTEXT.fixtureSha256;
const SOURCE_REF = RUNTIME_CONTEXT.gitHead;

const aggregateSamples = (samples) => withRuntimeEnvironment(
  () => aggregateRuntimeBudgetSamples(samples),
);

function rawSample(index) {
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
      cssGzipBytes: [1100, 900, 1000][index - 1],
      jsGzipBytes: [2100, 1900, 2000][index - 1],
      adapterAssets: [],
      duplicateRequestCount: [4, 2, 3][index - 1],
      themeFlashCount: [1, 0, 0][index - 1],
      initializationMs: [330, 110, 220][index - 1],
      handsontableInteractionMs: [90, 50, 70][index - 1],
      laboratoryAssets: [],
    },
    provenance: {
      assets: ASSETS,
      assetInventorySha256: ASSET_INVENTORY_SHA256,
      htmlSha256: String(index).repeat(64),
      sampleLabel: `sample-${index}`,
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

test('aggregates exactly three compatible raw samples with numeric medians', () => {
  // Given
  const samples = [rawSample(1), rawSample(2), rawSample(3)];

  // When
  const aggregate = aggregateSamples(samples);

  // Then
  assert.deepEqual(aggregate.metrics, {
    adapterAssets: [],
    laboratoryAssets: [],
    cssGzipBytes: 1000,
    jsGzipBytes: 2000,
    duplicateRequestCount: 3,
    themeFlashCount: 0,
    initializationMs: 220,
    handsontableInteractionMs: 70,
  });
  assert.equal(aggregate.kind, 'measurement');
  assert.equal(aggregate.status, 'measured');
  assert.equal(aggregate.provenance.sampling.sampleCount, 3);
  assert.equal(aggregate.provenance.sampling.aggregation, 'median-of-three');
  assert.deepEqual(aggregate.provenance.sampling.samples, samples);
});

test('rejects aggregation unless exactly three raw samples are provided', () => {
  // Given
  const samples = [rawSample(1), rawSample(2)];

  // When / Then
  assert.throws(
    () => aggregateSamples(samples),
    /exactly three raw samples are required/,
  );
});

test('rejects repeated raw reports presented as separate samples', () => {
  // Given
  const sample = rawSample(1);

  // When / Then
  assert.throws(
    () => aggregateSamples([sample, sample, sample]),
    /raw samples must have unique recordedAt values/,
  );
});

test('rejects source, asset and context mismatches between raw samples', () => {
  // Given
  const sourceMismatch = { ...rawSample(2), sourceRef: '0'.repeat(40) };
  const changedAssets = [{ ...ASSETS[0], gzipBytes: 1001 }, ASSETS[1]];
  const assetMismatch = {
    ...rawSample(2),
    provenance: {
      ...rawSample(2).provenance,
      assets: changedAssets,
      assetInventorySha256: createHash('sha256').update(JSON.stringify(changedAssets)).digest('hex'),
    },
  };
  const contextMismatch = { ...rawSample(2), theme: 'linen' };

  // When / Then
  assert.throws(
    () => aggregateSamples([rawSample(1), sourceMismatch, rawSample(3)]),
    /sourceRef mismatch/,
  );
  assert.throws(
    () => aggregateSamples([rawSample(1), assetMismatch, rawSample(3)]),
    /assets provenance mismatch/,
  );
  assert.throws(
    () => aggregateSamples([rawSample(1), contextMismatch, rawSample(3)]),
    /theme mismatch/,
  );
});

test('rejects malformed raw samples and an asset hash that does not match provenance', () => {
  // Given
  const malformed = { ...rawSample(2), metrics: null };
  const badHash = {
    ...rawSample(2),
    provenance: { ...rawSample(2).provenance, assetInventorySha256: 'f'.repeat(64) },
  };

  // When / Then
  assert.throws(
    () => aggregateSamples([rawSample(1), malformed, rawSample(3)]),
    /metrics must be an object/,
  );
  assert.throws(
    () => aggregateSamples([rawSample(1), badHash, rawSample(3)]),
    /asset inventory digest mismatch/,
  );
  assert.throws(
    () => aggregateSamples([1, 2, 3].map((index) => ({
      ...rawSample(index),
      route: '/pdc',
    }))),
    /route must be \/programa-general/,
  );
});

test('rejects fake Git refs, missing density and invalid asset hashes', () => {
  // Given
  const fakeRefSamples = [1, 2, 3].map((index) => ({ ...rawSample(index), sourceRef: '0'.repeat(40) }));
  const missingDensitySamples = [1, 2, 3].map((index) => {
    const { density, ...sample } = rawSample(index);
    return sample;
  });
  const invalidAssetHash = rawSample(2);
  invalidAssetHash.provenance = {
    ...invalidAssetHash.provenance,
    assets: [{ ...ASSETS[0], sha256: 'invalid' }, ASSETS[1]],
  };

  // When / Then
  assert.throws(() => aggregateSamples(fakeRefSamples), /top-level runtime identity/);
  assert.throws(() => aggregateSamples(missingDensitySamples), /density must be compact or touch/);
  assert.throws(
    () => aggregateSamples([rawSample(1), invalidAssetHash, rawSample(3)]),
    /asset sha256 must be 64 lowercase hex characters/,
  );
});

test('rejects stale, invalid and over-wide collection timestamps', () => {
  // Given
  const staleSamples = [1, 2, 3].map((index) => ({
    ...rawSample(index),
    recordedAt: `2000-01-01T00:00:0${index}.000Z`,
  }));
  const invalidTimestampSamples = [1, 2, 3].map((index) => ({
    ...rawSample(index),
    recordedAt: `2026-07-14 12:00:0${index}Z`,
  }));
  const wideWindowSamples = [
    { ...rawSample(1), recordedAt: '2026-07-14T11:45:00.000Z' },
    { ...rawSample(2), recordedAt: '2026-07-14T11:55:00.000Z' },
    { ...rawSample(3), recordedAt: '2026-07-14T12:00:00.000Z' },
  ];

  // When / Then
  assert.throws(() => aggregateSamples(staleSamples), /samples must be recent/);
  assert.throws(() => aggregateSamples(invalidTimestampSamples), /valid RFC3339 UTC timestamp/);
  assert.throws(() => aggregateSamples(wideWindowSamples), /collection window must not exceed/);
});
