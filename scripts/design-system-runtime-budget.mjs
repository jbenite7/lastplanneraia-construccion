import { readFileSync } from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

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

const CONTEXT_KEYS = ['route', 'viewport', 'theme', 'fixture'];
const SUPPORTED_VIEWPORTS = ['390x844', '1180x820', '1440x900'];
const SUPPORTED_THEMES = ['dark', 'linen'];
const SHA1 = /^[a-f0-9]{40}$/;
const SHA256 = /^[a-f0-9]{64}$/;
const SEMVER = /^\d+\.\d+\.\d+$/;

function fail(message) {
  throw new Error(`Runtime budget contract: ${message}`);
}

function requireEqual(actual, expected, label) {
  if (actual !== expected) fail(`${label} must be ${expected}`);
}

function requireFiniteNonNegative(value, label) {
  if (!Number.isFinite(value) || value < 0) {
    fail(`${label} must be a finite non-negative number`);
  }
}

function requireIsoTimestamp(value, label) {
  if (typeof value !== 'string' || Number.isNaN(Date.parse(value))) {
    fail(`${label} must be an ISO timestamp`);
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
  for (const tolerance of NUMERIC_TOLERANCES) {
    requireFiniteNonNegative(tolerances[tolerance], `tolerances.${tolerance}`);
  }
}

function validateSourceIdentity(artifact) {
  if (!SHA1.test(String(artifact.sourceRef ?? ''))) {
    fail('sourceRef must be a 40-character Git object id');
  }
  if (!SHA256.test(String(artifact.sourceTreeHash ?? ''))) {
    fail('sourceTreeHash must be a 64-character SHA-256 checksum');
  }
  requireIsoTimestamp(artifact.recordedAt, 'recordedAt');
}

export function validateRuntimeBudgetArtifact(artifact) {
  if (!artifact || typeof artifact !== 'object' || Array.isArray(artifact)) {
    fail('artifact must be an object');
  }
  requireEqual(artifact.schemaVersion, 1, 'schemaVersion');
  if (!['baseline', 'measurement'].includes(artifact.kind)) {
    fail('kind must be baseline or measurement');
  }
  if (!['original', 'retrospective', 'current'].includes(artifact.measurementKind)) {
    fail('measurementKind must be original, retrospective or current');
  }
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
  requireEqual(artifact.fixture, 'sanitized-pilot-v1', 'fixture');

  if (artifact.kind === 'baseline') {
    requireEqual(artifact.designSystemVersion, '0.3.3', 'baseline designSystemVersion');
    requireEqual(artifact.viewport, '1440x900', 'baseline viewport');
    requireEqual(artifact.theme, 'dark', 'baseline theme');
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
      if (typeof artifact.recovery?.checkpointRef !== 'string'
        || !artifact.recovery.checkpointRef.startsWith('refs/codex/turn-diffs/checkpoints/')) {
        fail('pending baseline requires the recoverable checkpointRef');
      }
      if (!SHA1.test(String(artifact.recovery?.sourceTree ?? ''))) {
        fail('pending baseline requires a recoverable sourceTree');
      }
      requireIsoTimestamp(artifact.recovery?.capturedAt, 'recovery.capturedAt');
      requireEqual(
        artifact.recovery?.measurementPath,
        'docs/design-system/runtime-measurements/0.3.3-retrospective.json',
        'recovery.measurementPath',
      );
      if (!SHA256.test(String(artifact.recovery?.measurementSha256 ?? ''))) {
        fail('pending baseline requires the retrospective measurement checksum');
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
    validateSourceIdentity(artifact);
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
  validateSourceIdentity(artifact);
  validateMetrics(artifact.metrics);
  return true;
}

function numericViolation(metric, baseline, measurement, tolerance) {
  const maximum = baseline + tolerance;
  if (measurement <= maximum) return null;
  return { metric, baseline, tolerance, maximum, actual: measurement };
}

export function compareRuntimeBudget(baseline, measurement) {
  validateRuntimeBudgetArtifact(baseline);
  validateRuntimeBudgetArtifact(measurement);
  if (baseline.kind !== 'baseline' || baseline.status !== 'approved') {
    fail('an approved 0.3.3 runtime baseline is required');
  }
  if (measurement.kind !== 'measurement') fail('current artifact must be a measurement');

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
