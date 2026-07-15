import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';

import {
  compareRuntimeBudget,
  validateRuntimeBudgetArtifact,
} from '../../scripts/design-system-runtime-budget.mjs';

const APPROVED_BASELINE = {
  schemaVersion: 1,
  kind: 'baseline',
  measurementKind: 'original',
  status: 'approved',
  designSystemVersion: '0.3.3',
  route: '/programa-general',
  viewport: '1440x900',
  theme: 'dark',
  fixture: 'sanitized-pilot-v1',
  sourceRef: '0123456789abcdef0123456789abcdef01234567',
  sourceTreeHash: '0'.repeat(64),
  recordedAt: '2026-07-13T12:00:00.000Z',
  approval: {
    status: 'approved',
    approvedBy: 'user',
    approvalRef: 'runtime-baseline-0.3.3',
  },
  metrics: {
    cssGzipBytes: 1000,
    jsGzipBytes: 2000,
    adapterAssets: [
      '/public/css/design-system/adapters/handsontable.css',
      '/public/css/design-system/adapters/programa-general-handsontable.css',
    ],
    duplicateRequestCount: 0,
    themeFlashCount: 0,
    initializationMs: 500,
    handsontableInteractionMs: 120,
    laboratoryAssets: [],
  },
  tolerances: {
    cssGzipBytes: 50,
    jsGzipBytes: 100,
    addedAdapterAssets: 0,
    duplicateRequestCount: 0,
    themeFlashCount: 0,
    initializationMs: 50,
    handsontableInteractionMs: 20,
  },
};

const CURRENT_MEASUREMENT = {
  schemaVersion: 1,
  kind: 'measurement',
  measurementKind: 'current',
  status: 'measured',
  designSystemVersion: '0.3.6',
  route: '/programa-general',
  viewport: '1440x900',
  theme: 'dark',
  fixture: 'sanitized-pilot-v1',
  sourceRef: 'fedcba9876543210fedcba9876543210fedcba98',
  sourceTreeHash: 'f'.repeat(64),
  recordedAt: '2026-07-14T12:00:00.000Z',
  metrics: {
    cssGzipBytes: 1050,
    jsGzipBytes: 2100,
    adapterAssets: [
      '/public/css/design-system/adapters/handsontable.css',
      '/public/css/design-system/adapters/programa-general-handsontable.css',
    ],
    duplicateRequestCount: 0,
    themeFlashCount: 0,
    initializationMs: 550,
    handsontableInteractionMs: 140,
    laboratoryAssets: [],
  },
};

test('accepts a measured report inside every explicit 0.3.3 tolerance', () => {
  assert.equal(validateRuntimeBudgetArtifact(APPROVED_BASELINE), true);
  assert.equal(validateRuntimeBudgetArtifact(CURRENT_MEASUREMENT), true);

  const result = compareRuntimeBudget(APPROVED_BASELINE, CURRENT_MEASUREMENT);
  assert.equal(result.pass, true, JSON.stringify(result.violations, null, 2));
  assert.deepEqual(result.violations, []);
});

test('accepts a measured retrospective 0.3.3 artifact without treating it as approved', () => {
  const retrospective = {
    ...CURRENT_MEASUREMENT,
    measurementKind: 'retrospective',
    designSystemVersion: '0.3.3',
    sourceRef: '25f2787332117ed93416ffc42e6fac8b037dce94',
  };

  assert.equal(validateRuntimeBudgetArtifact(retrospective), true);
  assert.throws(
    () => compareRuntimeBudget(retrospective, CURRENT_MEASUREMENT),
    /approved 0\.3\.3 runtime baseline is required/,
  );
});

test('rejects a pending or unapproved baseline instead of inventing values', () => {
  const pending = {
    ...APPROVED_BASELINE,
    status: 'missing-approved-measurement',
    measurementKind: 'retrospective',
    sourceRef: null,
    sourceTreeHash: null,
    recordedAt: null,
    approval: { status: 'missing', approvedBy: null, approvalRef: null },
    metrics: null,
    tolerances: null,
    reason: 'No approved runtime measurement was recorded for 0.3.3.',
    recovery: {
      checkpointRef: 'refs/codex/turn-diffs/checkpoints/example',
      sourceTree: '25f2787332117ed93416ffc42e6fac8b037dce94',
      capturedAt: '2026-07-13T15:17:35.000Z',
      measurementPath: 'docs/design-system/runtime-measurements/0.3.3-retrospective.json',
      measurementSha256: '0'.repeat(64),
    },
  };

  assert.equal(validateRuntimeBudgetArtifact(pending), true);
  assert.throws(
    () => compareRuntimeBudget(pending, CURRENT_MEASUREMENT),
    /approved 0\.3\.3 runtime baseline is required/,
  );
});

test('canonical 0.3.3 artifact is recoverable, machine-readable and fail-closed', async () => {
  const artifact = JSON.parse(await readFile(
    new URL('../../docs/design-system/runtime-baseline-0.3.3.json', import.meta.url),
    'utf8',
  ));

  assert.equal(validateRuntimeBudgetArtifact(artifact), true);
  assert.equal(artifact.status, 'missing-approved-measurement');
  assert.equal(artifact.recovery.sourceTree, '25f2787332117ed93416ffc42e6fac8b037dce94');
  assert.equal(
    artifact.recovery.measurementPath,
    'docs/design-system/runtime-measurements/0.3.3-retrospective.json',
  );
  assert.equal(artifact.metrics, null);
  assert.equal(artifact.tolerances, null);
  assert.throws(
    () => compareRuntimeBudget(artifact, CURRENT_MEASUREMENT),
    /approved 0\.3\.3 runtime baseline is required/,
  );
});

test('fails closed for budget growth, undeclared adapters, duplicates, flash and lab assets', () => {
  const regression = {
    ...CURRENT_MEASUREMENT,
    metrics: {
      ...CURRENT_MEASUREMENT.metrics,
      cssGzipBytes: 1051,
      jsGzipBytes: 2101,
      adapterAssets: [
        ...CURRENT_MEASUREMENT.metrics.adapterAssets,
        '/public/css/design-system/adapters/select2.css',
      ],
      duplicateRequestCount: 1,
      themeFlashCount: 1,
      initializationMs: 551,
      handsontableInteractionMs: 141,
      laboratoryAssets: ['/public/css/design-system/lab.css'],
    },
  };

  const result = compareRuntimeBudget(APPROVED_BASELINE, regression);
  assert.equal(result.pass, false);
  assert.deepEqual(
    result.violations.map(({ metric }) => metric).sort(),
    [
      'adapterAssets',
      'cssGzipBytes',
      'duplicateRequestCount',
      'handsontableInteractionMs',
      'initializationMs',
      'jsGzipBytes',
      'laboratoryAssets',
      'themeFlashCount',
    ].sort(),
  );
});

test('requires zero theme flash even when the historical measurement contained one', () => {
  const historicalWithFlash = {
    ...APPROVED_BASELINE,
    metrics: {
      ...APPROVED_BASELINE.metrics,
      themeFlashCount: 1,
    },
  };
  const currentWithFlash = {
    ...CURRENT_MEASUREMENT,
    metrics: {
      ...CURRENT_MEASUREMENT.metrics,
      themeFlashCount: 1,
    },
  };

  const result = compareRuntimeBudget(historicalWithFlash, currentWithFlash);
  assert.equal(result.pass, false);
  assert.equal(
    result.violations.some(({ metric, maximum }) => metric === 'themeFlashCount' && maximum === 0),
    true,
  );
});

test('rejects missing, negative, non-finite or context-mismatched measurements', () => {
  assert.throws(
    () => validateRuntimeBudgetArtifact({ ...CURRENT_MEASUREMENT, route: '/pdc' }),
    /route must be \/programa-general/,
  );
  assert.throws(
    () => validateRuntimeBudgetArtifact({
      ...CURRENT_MEASUREMENT,
      metrics: { ...CURRENT_MEASUREMENT.metrics, initializationMs: Number.NaN },
    }),
    /initializationMs must be a finite non-negative number/,
  );
  assert.throws(
    () => validateRuntimeBudgetArtifact({
      ...CURRENT_MEASUREMENT,
      metrics: { ...CURRENT_MEASUREMENT.metrics, laboratoryAssets: null },
    }),
    /laboratoryAssets must be an array/,
  );
});

test('compares only equivalent route, viewport, theme and fixture measurements', () => {
  assert.throws(
    () => compareRuntimeBudget(APPROVED_BASELINE, {
      ...CURRENT_MEASUREMENT,
      viewport: '1180x820',
    }),
    /runtime budget context mismatch: viewport/,
  );
});
