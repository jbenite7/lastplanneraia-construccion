import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { readFile } from 'node:fs/promises';
import test from 'node:test';

import {
  compareRuntimeBudget,
  validateRuntimeBudgetArtifact,
} from '../../scripts/design-system-runtime-budget.mjs';
import {
  APPROVED_BASELINE,
  currentMeasurement,
  withRuntimeEnvironment,
} from './runtime-budget-fixtures.mjs';

const compare = (baseline, measurement) => withRuntimeEnvironment(
  () => compareRuntimeBudget(baseline, measurement),
);

const CURRENT_MEASUREMENT = currentMeasurement();

test('accepts a measured report inside every explicit 0.3.3 tolerance', () => {
  assert.equal(validateRuntimeBudgetArtifact(APPROVED_BASELINE), true);
  assert.equal(validateRuntimeBudgetArtifact(CURRENT_MEASUREMENT), true);

  const result = compare(APPROVED_BASELINE, CURRENT_MEASUREMENT);
  assert.equal(result.pass, true, JSON.stringify(result.violations, null, 2));
  assert.deepEqual(result.violations, []);
});

test('accepts a measured retrospective 0.3.3 artifact without treating it as approved', () => {
  const retrospective = JSON.parse(readFileSync(
    new URL('../../docs/design-system/runtime-measurements/0.3.3-retrospective.json', import.meta.url),
    'utf8',
  ));

  assert.equal(validateRuntimeBudgetArtifact(retrospective), true);
  assert.throws(
    () => compare(retrospective, CURRENT_MEASUREMENT),
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
    recovery: { ...APPROVED_BASELINE.recovery },
  };

  assert.equal(validateRuntimeBudgetArtifact(pending), true);
  assert.throws(
    () => compare(pending, CURRENT_MEASUREMENT),
    /approved 0\.3\.3 runtime baseline is required/,
  );
});

test('canonical 0.3.3 artifact is approved only with the user-approved caps', async () => {
  const artifact = JSON.parse(await readFile(
    new URL('../../docs/design-system/runtime-baseline-0.3.3.json', import.meta.url),
    'utf8',
  ));

  assert.equal(validateRuntimeBudgetArtifact(artifact), true);
  assert.equal(artifact.status, 'approved');
  assert.equal(artifact.approval.status, 'approved');
  assert.deepEqual(artifact.tolerances, {
    cssGzipBytes: 2048,
    jsGzipBytes: 4096,
    addedAdapterAssets: 0,
    duplicateRequestCount: 0,
    themeFlashCount: 0,
    initializationMs: 110,
    handsontableInteractionMs: 45,
  });
  assert.equal(artifact.metrics.themeFlashCount, 1);
  assert.deepEqual(artifact.metrics.laboratoryAssets, []);
});

test('fails closed for budget growth, undeclared adapters, duplicates, flash and lab assets', () => {
  const regression = currentMeasurement({
    metrics: {
      cssGzipBytes: APPROVED_BASELINE.metrics.cssGzipBytes + APPROVED_BASELINE.tolerances.cssGzipBytes + 1,
      jsGzipBytes: APPROVED_BASELINE.metrics.jsGzipBytes + APPROVED_BASELINE.tolerances.jsGzipBytes + 1,
      adapterAssets: [
        ...APPROVED_BASELINE.metrics.adapterAssets,
        '/public/css/design-system/adapters/select2.css',
      ],
      duplicateRequestCount: APPROVED_BASELINE.metrics.duplicateRequestCount + 1,
      themeFlashCount: 1,
      initializationMs: APPROVED_BASELINE.metrics.initializationMs
        + APPROVED_BASELINE.tolerances.initializationMs + 1,
      handsontableInteractionMs: APPROVED_BASELINE.metrics.handsontableInteractionMs
        + APPROVED_BASELINE.tolerances.handsontableInteractionMs + 1,
      laboratoryAssets: ['/public/css/design-system/lab.css'],
    },
  });

  const result = compare(APPROVED_BASELINE, regression);
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

test('allows a tiny timing overshoot on Programa General measurements', () => {
  const noisyMeasurement = currentMeasurement({
    metrics: {
      initializationMs: APPROVED_BASELINE.metrics.initializationMs
        + APPROVED_BASELINE.tolerances.initializationMs + 0.3,
      handsontableInteractionMs: APPROVED_BASELINE.metrics.handsontableInteractionMs
        + APPROVED_BASELINE.tolerances.handsontableInteractionMs + 0.3,
    },
  });

  const result = compare(APPROVED_BASELINE, noisyMeasurement);
  assert.equal(result.pass, true, JSON.stringify(result.violations, null, 2));
  assert.deepEqual(result.violations, []);
});

test('requires zero theme flash even when the historical measurement contained one', () => {
  const currentWithFlash = currentMeasurement({ metrics: { themeFlashCount: 1 } });

  const result = compare(APPROVED_BASELINE, currentWithFlash);
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
    () => compare(APPROVED_BASELINE, currentMeasurement({
      context: { viewport: '1180x820' },
    })),
    /runtime budget context mismatch: viewport/,
  );
});

test('el contrato de presupuesto acepta el viewport movil', async () => {
  const source = await readFile(
    new URL('../../scripts/design-system-runtime-budget.mjs', import.meta.url), 'utf8',
  );
  assert.match(source, /SUPPORTED_VIEWPORTS = \[[^\]]*'390x844'/);
});

test('el esquema de presupuesto admite el viewport movil', async () => {
  const schema = JSON.parse(await readFile(
    new URL('../../docs/design-system/runtime-budget.schema.json', import.meta.url), 'utf8',
  ));
  const viewport = schema["$defs"]["artifactBase"]["properties"]["viewport"];
  assert.ok(viewport, 'no se encontro el enum de viewport');
  assert.ok(viewport.enum.includes('390x844'));
});
