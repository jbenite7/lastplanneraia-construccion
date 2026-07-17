import { readFileSync } from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

import {
  validateApprovedBaselineProvenance,
  validateRuntimeBudgetMeasurementProvenance,
} from './design-system-runtime-budget-aggregate.mjs';
import {
  validateArtifactSourceIdentity,
  validateAssets,
  validateCurrentSampleShape,
  parseRfc3339Utc,
  validateRetrospectiveSummary,
} from './design-system-runtime-budget-provenance.mjs';

const NUMERIC_METRICS = [
  'cssGzipBytes',
  'jsGzipBytes',
  'duplicateRequestCount',
  'themeFlashCount',
  'initializationMs',
  'handsontableInteractionMs',
];

const NUMERIC_TOLERANCES = [
  'cssGzipBytes',
  'jsGzipBytes',
  'addedAdapterAssets',
  'duplicateRequestCount',
  'themeFlashCount',
  'initializationMs',
  'handsontableInteractionMs',
];
const TIMING_METRIC_EPSILON_MS = 0.5;

const CONTEXT_KEYS = ['route', 'viewport', 'theme', 'density', 'fixture'];
const SUPPORTED_VIEWPORTS = ['390x844', '1180x820', '1440x900'];
const SUPPORTED_THEMES = ['dark', 'linen'];
const SUPPORTED_DENSITIES = ['compact', 'touch'];
const SEMVER = /^\d+\.\d+\.\d+$/;
const COMMON_ARTIFACT_KEYS = [
  '$schema', 'schemaVersion', 'kind', 'measurementKind', 'status', 'designSystemVersion',
  'route', 'viewport', 'theme', 'density', 'fixture', 'sourceRef', 'sourceTreeHash',
  'recordedAt', 'metrics',
];

function fail(message) {
  throw new Error(`Runtime budget contract: ${message}`);
}

function requireEqual(actual, expected, label) {
  if (actual !== expected) fail(`${label} must be ${expected}`);
}

function requireOnlyKeys(value, allowed, label) {
  const unexpected = Object.keys(value).filter((key) => !allowed.includes(key));
  if (unexpected.length > 0) fail(`${label} contains unexpected properties: ${unexpected.join(', ')}`);
}

function requireFiniteNonNegative(value, label) {
  if (!Number.isFinite(value) || value < 0) {
    fail(`${label} must be a finite non-negative number`);
  }
}

function validateStringArray(value, label) {
  if (!Array.isArray(value)) fail(`${label} must be an array`);
  const unique = new Set(value);
  if (unique.size !== value.length) fail(`${label} must not contain duplicates`);
  for (const entry of value) {
    if (typeof entry !== 'string' || !entry.startsWith('/')) {
      fail(`${label} entries must be absolute application paths`);
    }
    if (entry.includes('?') || entry.includes('#')) {
      fail(`${label} entries must not contain query strings or fragments`);
    }
  }
}

function validateMetrics(metrics) {
  if (!metrics || typeof metrics !== 'object' || Array.isArray(metrics)) {
    fail('metrics must be an object');
  }
  requireOnlyKeys(metrics, [...NUMERIC_METRICS, 'adapterAssets', 'laboratoryAssets'], 'metrics');
  for (const metric of NUMERIC_METRICS) {
    requireFiniteNonNegative(metrics[metric], metric);
  }
  validateStringArray(metrics.adapterAssets, 'adapterAssets');
  validateStringArray(metrics.laboratoryAssets, 'laboratoryAssets');
}

function validateTolerances(tolerances) {
  if (!tolerances || typeof tolerances !== 'object' || Array.isArray(tolerances)) {
    fail('tolerances must be an object');
  }
  requireOnlyKeys(tolerances, NUMERIC_TOLERANCES, 'tolerances');
  for (const tolerance of NUMERIC_TOLERANCES) {
    requireFiniteNonNegative(tolerances[tolerance], `tolerances.${tolerance}`);
  }
}

export function validateRuntimeBudgetArtifact(artifact) {
  if (!artifact || typeof artifact !== 'object' || Array.isArray(artifact)) {
    fail('artifact must be an object');
  }
  requireEqual(artifact.schemaVersion, 1, 'schemaVersion');
  if (!['baseline', 'measurement', 'sample'].includes(artifact.kind)) {
    fail('kind must be baseline, measurement or sample');
  }
  if (!['original', 'retrospective', 'current'].includes(artifact.measurementKind)) {
    fail('measurementKind must be original, retrospective or current');
  }
  const branchKeys = artifact.kind === 'baseline'
    ? ['approval', 'recovery', 'tolerances', 'reason']
    : artifact.measurementKind === 'current'
      ? ['ciRunId', 'fixtureSha256', 'provenance']
      : ['provenance'];
  requireOnlyKeys(artifact, [...COMMON_ARTIFACT_KEYS, ...branchKeys], 'artifact');
  if (!SEMVER.test(String(artifact.designSystemVersion ?? ''))) {
    fail('designSystemVersion must be SemVer');
  }
  requireEqual(artifact.route, '/programa-general', 'route');
  if (!SUPPORTED_VIEWPORTS.includes(artifact.viewport)) {
    fail(`viewport must be one of ${SUPPORTED_VIEWPORTS.join(', ')}`);
  }
  if (!SUPPORTED_THEMES.includes(artifact.theme)) {
    fail(`theme must be one of ${SUPPORTED_THEMES.join(', ')}`);
  }
  if (!SUPPORTED_DENSITIES.includes(artifact.density)) {
    fail(`density must be one of ${SUPPORTED_DENSITIES.join(', ')}`);
  }
  requireEqual(artifact.fixture, 'sanitized-pilot-v1', 'fixture');

  if (artifact.kind === 'sample') {
    try {
      validateCurrentSampleShape(artifact);
    } catch (error) {
      fail(error instanceof Error ? error.message.replace(/^Runtime budget aggregation: /, '') : String(error));
    }
    return true;
  }

  if (artifact.kind === 'baseline') {
    requireEqual(artifact.designSystemVersion, '0.3.3', 'baseline designSystemVersion');
    requireEqual(artifact.viewport, '1440x900', 'baseline viewport');
    requireEqual(artifact.theme, 'dark', 'baseline theme');
    requireEqual(artifact.measurementKind, 'retrospective', 'baseline measurementKind');
    if (!artifact.approval || typeof artifact.approval !== 'object' || Array.isArray(artifact.approval)) {
      fail('baseline approval must be an object');
    }
    requireOnlyKeys(artifact.approval, ['status', 'approvedBy', 'approvalRef'], 'baseline approval');
    if (!artifact.recovery || typeof artifact.recovery !== 'object' || Array.isArray(artifact.recovery)) {
      fail('baseline recovery must be an object');
    }
    requireOnlyKeys(artifact.recovery, [
      'manifestPath', 'manifestSha256', 'measurementPath', 'measurementSha256',
    ], 'baseline recovery');
    if ('reason' in artifact && typeof artifact.reason !== 'string') {
      fail('baseline reason must be a string');
    }
    requireEqual(
      artifact.recovery.manifestPath,
      'docs/design-system/runtime-measurements/0.3.3-recovery-manifest.json',
      'recovery.manifestPath',
    );
    if (!/^[a-f0-9]{64}$/.test(String(artifact.recovery.manifestSha256 ?? ''))) {
      fail('baseline recovery requires the portable manifest checksum');
    }
    requireEqual(
      artifact.recovery.measurementPath,
      'docs/design-system/runtime-measurements/0.3.3-retrospective.json',
      'recovery.measurementPath',
    );
    if (!/^[a-f0-9]{64}$/.test(String(artifact.recovery.measurementSha256 ?? ''))) {
      fail('baseline recovery requires the retrospective measurement checksum');
    }
    if (artifact.status === 'missing-approved-measurement') {
      requireEqual(artifact.measurementKind, 'retrospective', 'pending baseline measurementKind');
      if (artifact.sourceRef !== null || artifact.sourceTreeHash !== null || artifact.recordedAt !== null) {
        fail('pending baseline source identity must be null');
      }
      if (artifact.metrics !== null || artifact.tolerances !== null) {
        fail('pending baseline cannot contain invented metrics or tolerances');
      }
      if (artifact.approval?.status !== 'missing') {
        fail('pending baseline approval status must be missing');
      }
      if (typeof artifact.reason !== 'string' || artifact.reason.length < 24) {
        fail('pending baseline requires a reason');
      }
      return true;
    }
    requireEqual(artifact.status, 'approved', 'baseline status');
    requireEqual(artifact.approval?.status, 'approved', 'baseline approval status');
    if (typeof artifact.approval?.approvedBy !== 'string' || !artifact.approval.approvedBy) {
      fail('approved baseline requires approvedBy');
    }
    if (typeof artifact.approval?.approvalRef !== 'string' || !artifact.approval.approvalRef) {
      fail('approved baseline requires approvalRef');
    }
    if (artifact.sourceRef !== null) fail('retrospective baseline cannot claim an origin Git commit');
    if (!/^[a-f0-9]{64}$/.test(String(artifact.sourceTreeHash ?? ''))) fail('baseline sourceTreeHash must be a SHA-256 checksum');
    try {
      parseRfc3339Utc(artifact.recordedAt, 'recordedAt');
    } catch (error) {
      fail(error instanceof Error ? error.message.replace(/^Runtime budget aggregation: /, '') : String(error));
    }
    validateMetrics(artifact.metrics);
    validateTolerances(artifact.tolerances);
    return true;
  }

  requireEqual(artifact.status, 'measured', 'measurement status');
  if (!['current', 'retrospective'].includes(artifact.measurementKind)) {
    fail('measurement measurementKind must be current or retrospective');
  }
  if (artifact.measurementKind === 'retrospective') {
    requireEqual(artifact.designSystemVersion, '0.3.3', 'retrospective designSystemVersion');
  }
  validateMetrics(artifact.metrics);
  if (!artifact.provenance || typeof artifact.provenance !== 'object' || Array.isArray(artifact.provenance)) {
    fail('measurement provenance must be an object');
  }
  try {
    validateAssets(artifact.provenance.assets);
  } catch (error) {
    fail(error instanceof Error ? error.message.replace(/^Runtime budget aggregation: /, '') : String(error));
  }
  if (!/^[a-f0-9]{64}$/.test(String(artifact.provenance.assetInventorySha256 ?? ''))) {
    fail('measurement assetInventorySha256 must be a SHA-256 checksum');
  }
  if (artifact.measurementKind === 'retrospective') {
    if (artifact.sourceRef !== null) fail('retrospective measurement cannot claim an origin Git commit');
    if (!/^[a-f0-9]{64}$/.test(String(artifact.sourceTreeHash ?? ''))) fail('retrospective sourceTreeHash must be a SHA-256 checksum');
    try {
      parseRfc3339Utc(artifact.recordedAt, 'recordedAt');
    } catch (error) {
      fail(error instanceof Error ? error.message.replace(/^Runtime budget aggregation: /, '') : String(error));
    }
    try {
      validateRetrospectiveSummary(artifact);
    } catch (error) {
      fail(error instanceof Error ? error.message.replace(/^Runtime budget aggregation: /, '') : String(error));
    }
    return true;
  }
  validateArtifactSourceIdentity(artifact);
  requireOnlyKeys(artifact.provenance, ['assets', 'assetInventorySha256', 'sampling'], 'current measurement provenance');
  if (!/^run-[a-z0-9][a-z0-9-]{5,48}$/.test(String(artifact.ciRunId ?? ''))) {
    fail('current measurement CI_RUN_ID provenance is required');
  }
  if (!/^[a-f0-9]{64}$/.test(String(artifact.fixtureSha256 ?? ''))) {
    fail('current measurement fixtureSha256 is required');
  }
  const sampling = artifact.provenance.sampling;
  if (sampling?.rawSamplesPreserved !== true || sampling.sampleCount !== 3
    || sampling.aggregation !== 'median-of-three' || sampling.samples?.length !== 3
    || sampling.rawReceipts?.length !== 3
    || !/^[a-f0-9]{64}$/.test(String(sampling.aggregateReceiptSha256 ?? ''))) {
    fail('current measurement complete three-sample provenance is required');
  }
  requireOnlyKeys(sampling, [
    'rawSamplesPreserved', 'sampleCount', 'aggregation', 'rawReceipts',
    'aggregateReceiptSha256', 'samples',
  ], 'current measurement sampling');
  sampling.rawReceipts.forEach((receipt, index) => {
    if (!receipt || receipt.index !== index + 1 || !/^[a-f0-9]{64}$/.test(String(receipt.sha256 ?? ''))
      || Object.keys(receipt).sort().join(',') !== 'index,sha256') {
      fail(`current measurement raw receipt ${index + 1} has an invalid shape`);
    }
  });
  sampling.samples.forEach((sample, index) => {
    try {
      validateCurrentSampleShape(sample, index);
    } catch (error) {
      fail(error instanceof Error ? error.message.replace(/^Runtime budget aggregation: /, '') : String(error));
    }
  });
  return true;
}

function metricAllowance(metric) {
  return metric === 'initializationMs' || metric === 'handsontableInteractionMs'
    ? TIMING_METRIC_EPSILON_MS
    : 0;
}

function numericViolation(metric, baseline, measurement, tolerance) {
  const maximum = baseline + tolerance;
  const effectiveMaximum = maximum + metricAllowance(metric);
  if (measurement <= effectiveMaximum) return null;
  return { metric, baseline, tolerance, maximum: effectiveMaximum, actual: measurement };
}

function compareRuntimeBudgetWithValidator(baseline, measurement, validateMeasurement) {
  validateRuntimeBudgetArtifact(baseline);
  validateRuntimeBudgetArtifact(measurement);
  if (baseline.kind !== 'baseline' || baseline.status !== 'approved') {
    fail('an approved 0.3.3 runtime baseline is required');
  }
  if (measurement.kind !== 'measurement') fail('current artifact must be a measurement');
  validateApprovedBaselineProvenance(baseline);
  validateMeasurement(measurement);

  for (const key of CONTEXT_KEYS) {
    if (baseline[key] !== measurement[key]) {
      fail(`runtime budget context mismatch: ${key}`);
    }
  }

  const violations = [];
  for (const metric of NUMERIC_METRICS) {
    if (metric === 'themeFlashCount') continue;
    const violation = numericViolation(
      metric,
      baseline.metrics[metric],
      measurement.metrics[metric],
      baseline.tolerances[metric],
    );
    if (violation) violations.push(violation);
  }

  if (measurement.metrics.themeFlashCount > 0) {
    violations.push({
      metric: 'themeFlashCount',
      baseline: baseline.metrics.themeFlashCount,
      tolerance: 0,
      maximum: 0,
      actual: measurement.metrics.themeFlashCount,
    });
  }

  const baselineAdapters = new Set(baseline.metrics.adapterAssets);
  const addedAdapters = measurement.metrics.adapterAssets.filter((asset) => !baselineAdapters.has(asset));
  if (addedAdapters.length > baseline.tolerances.addedAdapterAssets) {
    violations.push({
      metric: 'adapterAssets',
      baseline: baseline.metrics.adapterAssets,
      tolerance: baseline.tolerances.addedAdapterAssets,
      actual: measurement.metrics.adapterAssets,
      added: addedAdapters,
    });
  }

  if (measurement.metrics.laboratoryAssets.length > 0) {
    violations.push({
      metric: 'laboratoryAssets',
      baseline: [],
      tolerance: 0,
      actual: measurement.metrics.laboratoryAssets,
    });
  }

  return {
    pass: violations.length === 0,
    baselineVersion: baseline.designSystemVersion,
    measurementVersion: measurement.designSystemVersion,
    violations,
  };
}

export function compareRuntimeBudget(baseline, measurement) {
  return compareRuntimeBudgetWithValidator(
    baseline,
    measurement,
    validateRuntimeBudgetMeasurementProvenance,
  );
}

function runCli() {
  const [, , command, baselinePath, measurementPath] = process.argv;
  if (command !== 'check' || !baselinePath || !measurementPath) {
    fail('usage: node scripts/design-system-runtime-budget.mjs check <baseline.json> <measurement.json>');
  }
  const baseline = JSON.parse(readFileSync(path.resolve(baselinePath), 'utf8'));
  const measurement = JSON.parse(readFileSync(path.resolve(measurementPath), 'utf8'));
  const result = compareRuntimeBudget(baseline, measurement);
  if (!result.pass) {
    process.stderr.write(`${JSON.stringify(result, null, 2)}\n`);
    process.exitCode = 1;
    return;
  }
  process.stdout.write(`Design-system runtime budget: PASS (${result.baselineVersion} -> ${result.measurementVersion})\n`);
}

const currentFile = fileURLToPath(import.meta.url);
if (process.argv[1] && path.resolve(process.argv[1]) === currentFile) {
  try {
    runCli();
  } catch (error) {
    process.stderr.write(`${error.message}\n`);
    process.exitCode = 1;
  }
}
