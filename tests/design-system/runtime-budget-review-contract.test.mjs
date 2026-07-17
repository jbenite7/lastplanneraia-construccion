import assert from 'node:assert/strict';
import { createHash } from 'node:crypto';
import { readFileSync } from 'node:fs';
import test from 'node:test';

import {
  aggregateRuntimeBudgetSamples,
  validateRuntimeBudgetMeasurementProvenance,
} from '../../scripts/design-system-runtime-budget-aggregate.mjs';
import { validateRuntimeBudgetArtifact } from '../../scripts/design-system-runtime-budget.mjs';
import { currentSamples, withRuntimeEnvironment } from './runtime-budget-fixtures.mjs';

const aggregate = () => withRuntimeEnvironment(
  () => aggregateRuntimeBudgetSamples(currentSamples()),
);

test('current aggregate carries canonical ordered raw and aggregate receipts', () => {
  const measurement = aggregate();
  const sampling = measurement.provenance.sampling;

  assert.equal(sampling.rawSamplesPreserved, true);
  assert.equal(sampling.rawReceipts.length, 3);
  assert.match(sampling.aggregateReceiptSha256, /^[a-f0-9]{64}$/);
  assert.equal(withRuntimeEnvironment(
    () => validateRuntimeBudgetMeasurementProvenance(measurement),
  ), true);
});

test('receipts detect partial raw, median or receipt alteration when receipts are not fully rewritten', () => {
  const partialAlteration = structuredClone(aggregate());
  partialAlteration.provenance.sampling.samples[0].metrics.cssGzipBytes += 30;
  partialAlteration.metrics.cssGzipBytes += 30;

  assert.throws(
    () => withRuntimeEnvironment(
      () => validateRuntimeBudgetMeasurementProvenance(partialAlteration),
    ),
    /raw sample receipt mismatch/,
  );

  const receiptTampered = structuredClone(aggregate());
  receiptTampered.provenance.sampling.rawReceipts[1].sha256 = 'f'.repeat(64);
  receiptTampered.provenance.sampling.aggregateReceiptSha256 = createHash('sha256')
    .update(JSON.stringify(receiptTampered.provenance.sampling.rawReceipts))
    .digest('hex');
  assert.throws(
    () => withRuntimeEnvironment(
      () => validateRuntimeBudgetMeasurementProvenance(receiptTampered),
    ),
    /raw sample receipt mismatch/,
  );
});

test('production aggregation accepts the recomputed clean hermetic runtime identity', () => {
  assert.equal(aggregate().kind, 'measurement');
});

test('historical recovery is explicitly incomplete and cannot masquerade as current evidence', () => {
  const retrospective = JSON.parse(readFileSync(
    new URL('../../docs/design-system/runtime-measurements/0.3.3-retrospective.json', import.meta.url),
    'utf8',
  ));
  assert.equal(retrospective.provenance.sampling.rawSamplesPreserved, false);
  assert.equal(retrospective.provenance.sampling.samples, undefined);
  assert.equal(validateRuntimeBudgetArtifact(retrospective), true);

  const mislabeled = structuredClone(retrospective);
  mislabeled.provenance.sampling.rawSamplesPreserved = true;
  assert.throws(() => validateRuntimeBudgetArtifact(mislabeled), /rawSamplesPreserved/);
});

test('schema constrains all sampling items and has no provenance-free measurement branch', () => {
  const schema = JSON.parse(readFileSync(
    new URL('../../docs/design-system/runtime-budget.schema.json', import.meta.url),
    'utf8',
  ));
  const serialized = JSON.stringify(schema);
  assert.match(serialized, /rawSamplesPreserved/);
  assert.match(serialized, /rawReceipts/);
  assert.match(serialized, /aggregateReceiptSha256/);
  assert.match(serialized, /incompleteSummaries/);
  assert.doesNotMatch(serialized, /"items":\{\}/);
  assert.equal(schema.oneOf.length, 4);

  const baseline = JSON.parse(readFileSync(
    new URL('../../docs/design-system/runtime-baseline-0.3.3.json', import.meta.url),
    'utf8',
  ));
  assert.throws(
    () => validateRuntimeBudgetArtifact({ ...baseline, measurementKind: 'original' }),
    /baseline measurementKind must be retrospective/,
  );
  const provenanceMismatch = aggregate();
  provenanceMismatch.provenance.unconstrained = [];
  assert.throws(
    () => validateRuntimeBudgetArtifact(provenanceMismatch),
    /unexpected properties/,
  );
});
