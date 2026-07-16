import { readFileSync, writeFileSync } from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

import {
  canonicalJson,
  NUMERIC_METRICS,
  sha256Canonical,
  validateApprovedBaselineProvenance,
  validateRuntimeBudgetSamples,
} from './design-system-runtime-budget-provenance.mjs';

function fail(message) {
  throw new Error(`Runtime budget aggregation: ${message}`);
}

function rawReceipts(samples) {
  return samples.map((sample, index) => ({
    index: index + 1,
    sha256: sha256Canonical(sample),
  }));
}

function aggregateValidatedSamples(samples, validation) {
  const { reference, latestTimestamp } = validation;
  const metrics = {
    adapterAssets: [...reference.metrics.adapterAssets],
    laboratoryAssets: [...reference.metrics.laboratoryAssets],
  };
  for (const metric of NUMERIC_METRICS) {
    metrics[metric] = samples.map((sample) => sample.metrics[metric]).sort((left, right) => left - right)[1];
  }
  const receipts = rawReceipts(samples);

  return {
    $schema: reference.$schema,
    schemaVersion: 1,
    kind: 'measurement',
    measurementKind: 'current',
    status: 'measured',
    designSystemVersion: reference.designSystemVersion,
    route: reference.route,
    viewport: reference.viewport,
    theme: reference.theme,
    density: reference.density,
    fixture: reference.fixture,
    ciRunId: reference.ciRunId,
    sourceRef: reference.sourceRef,
    sourceTreeHash: reference.sourceTreeHash,
    fixtureSha256: reference.fixtureSha256,
    recordedAt: new Date(latestTimestamp).toISOString(),
    metrics,
    provenance: {
      assets: structuredClone(reference.provenance.assets),
      assetInventorySha256: reference.provenance.assetInventorySha256,
      sampling: {
        rawSamplesPreserved: true,
        sampleCount: 3,
        aggregation: 'median-of-three',
        rawReceipts: receipts,
        aggregateReceiptSha256: sha256Canonical(receipts),
        samples: structuredClone(samples),
      },
    },
  };
}

export function aggregateRuntimeBudgetSamples(samples) {
  return aggregateValidatedSamples(samples, validateRuntimeBudgetSamples(samples));
}

function validateMeasurementProvenance(measurement, reconstruct) {
  const sampling = measurement?.provenance?.sampling;
  if (sampling?.rawSamplesPreserved !== true || sampling.sampleCount !== 3
    || sampling.aggregation !== 'median-of-three' || sampling.samples?.length !== 3
    || sampling.rawReceipts?.length !== 3) {
    fail('measurement complete three-sample provenance is required');
  }
  const expectedReceipts = rawReceipts(sampling.samples);
  for (let index = 0; index < expectedReceipts.length; index += 1) {
    if (canonicalJson(sampling.rawReceipts[index]) !== canonicalJson(expectedReceipts[index])) {
      fail(`raw sample receipt mismatch at index ${index + 1}`);
    }
  }
  if (sampling.aggregateReceiptSha256 !== sha256Canonical(expectedReceipts)) {
    fail('aggregate receipt does not match the ordered raw receipts');
  }
  const reconstructed = reconstruct(sampling.samples);
  for (const key of [
    'schemaVersion', 'kind', 'measurementKind', 'status', 'designSystemVersion', 'route', 'viewport',
    'theme', 'density', 'fixture', 'ciRunId', 'sourceRef', 'sourceTreeHash', 'fixtureSha256', 'recordedAt',
  ]) {
    if (measurement[key] !== reconstructed[key]) fail(`aggregate ${key} does not match raw samples`);
  }
  if (canonicalJson(measurement.metrics) !== canonicalJson(reconstructed.metrics)) {
    fail('aggregate metrics do not match sample medians');
  }
  for (const key of ['assets', 'assetInventorySha256']) {
    if (canonicalJson(measurement.provenance[key]) !== canonicalJson(reconstructed.provenance[key])) {
      fail(`aggregate ${key} does not match raw samples`);
    }
  }
  return true;
}

export function validateRuntimeBudgetMeasurementProvenance(measurement) {
  return validateMeasurementProvenance(measurement, aggregateRuntimeBudgetSamples);
}

export { validateApprovedBaselineProvenance };

function runCli() {
  const [, , outputPath, ...inputPaths] = process.argv;
  if (!outputPath || inputPaths.length !== 3) {
    fail('usage: node scripts/design-system-runtime-budget-aggregate.mjs <output.json> <sample-1.json> <sample-2.json> <sample-3.json>');
  }
  const resolvedOutput = path.resolve(outputPath);
  const resolvedInputs = inputPaths.map((inputPath) => path.resolve(inputPath));
  if (resolvedInputs.includes(resolvedOutput)) fail('output must not overwrite a raw sample');
  const samples = resolvedInputs.map((inputPath) => JSON.parse(readFileSync(inputPath, 'utf8')));
  const aggregate = aggregateRuntimeBudgetSamples(samples);
  writeFileSync(resolvedOutput, `${JSON.stringify(aggregate, null, 2)}\n`, { flag: 'wx' });
  process.stdout.write(`Design-system runtime aggregation: PASS (3 samples -> ${resolvedOutput})\n`);
}

const currentFile = fileURLToPath(import.meta.url);
if (process.argv[1] && path.resolve(process.argv[1]) === currentFile) {
  try {
    runCli();
  } catch (error) {
    process.stderr.write(`${error instanceof Error ? error.message : String(error)}\n`);
    process.exitCode = 1;
  }
}
