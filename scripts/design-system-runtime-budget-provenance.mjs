import { execFileSync } from 'node:child_process';
import { createHash } from 'node:crypto';
import { readFileSync } from 'node:fs';
import path from 'node:path';

import { readWorktreeProvenance } from './design-system-ci-preflight.mjs';

export const NUMERIC_METRICS = [
  'cssGzipBytes',
  'jsGzipBytes',
  'duplicateRequestCount',
  'themeFlashCount',
  'initializationMs',
  'handsontableInteractionMs',
];

const IDENTITY_KEYS = [
  'schemaVersion', 'measurementKind', 'designSystemVersion', 'route', 'viewport',
  'theme', 'density', 'fixture', 'ciRunId', 'sourceRef', 'sourceTreeHash', 'fixtureSha256',
];
const SHA1 = /^[a-f0-9]{40}$/;
const SHA256 = /^[a-f0-9]{64}$/;
const CI_RUN_ID = /^run-[a-z0-9][a-z0-9-]{5,48}$/;
const SEMVER = /^\d+\.\d+\.\d+$/;
const RFC3339_UTC = /^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2}):(\d{2})(?:\.(\d{1,3}))?Z$/;
const VIEWPORTS = ['390x844', '1180x820', '1440x900'];
const THEMES = ['dark'];
const DENSITIES = ['compact', 'touch'];
const MAX_SAMPLE_AGE_MS = 15 * 60 * 1000;
const MAX_COLLECTION_WINDOW_MS = 10 * 60 * 1000;
const MAX_FUTURE_SKEW_MS = 5 * 1000;
const BASELINE_MEASUREMENT_PATH = 'docs/design-system/runtime-measurements/0.3.3-retrospective.json';
const BASELINE_MANIFEST_PATH = 'docs/design-system/runtime-measurements/0.3.3-recovery-manifest.json';

function fail(message) {
  throw new Error(`Runtime budget aggregation: ${message}`);
}

function requireOnlyKeys(value, allowed, label) {
  const unexpected = Object.keys(value).filter((key) => !allowed.includes(key));
  if (unexpected.length > 0) fail(`${label} contains unexpected properties: ${unexpected.join(', ')}`);
}

export function canonicalJson(value) {
  if (Array.isArray(value)) return `[${value.map(canonicalJson).join(',')}]`;
  if (value && typeof value === 'object') {
    const entries = Object.keys(value).sort().map((key) => `${JSON.stringify(key)}:${canonicalJson(value[key])}`);
    return `{${entries.join(',')}}`;
  }
  return JSON.stringify(value);
}

export function sha256Canonical(value) {
  return createHash('sha256').update(canonicalJson(value)).digest('hex');
}

function requireFiniteNonNegative(value, label) {
  if (!Number.isFinite(value) || value < 0) fail(`${label} must be a finite non-negative number`);
}

function validatePaths(value, label) {
  if (!Array.isArray(value)) fail(`${label} must be an array`);
  if (new Set(value).size !== value.length) fail(`${label} must not contain duplicates`);
  for (const entry of value) {
    if (typeof entry !== 'string' || !entry.startsWith('/') || entry.includes('?') || entry.includes('#')) {
      fail(`${label} entries must be absolute application paths`);
    }
  }
}

export function validateAssets(assets) {
  if (!Array.isArray(assets) || assets.length === 0) fail('provenance.assets must be a non-empty array');
  const entries = new Set();
  for (const asset of assets) {
    if (!asset || typeof asset !== 'object' || Array.isArray(asset)) fail('provenance.assets entries must be objects');
    requireOnlyKeys(asset, ['path', 'type', 'rawBytes', 'gzipBytes', 'sha256'], 'provenance asset');
    const canonicalAsset = canonicalJson(asset);
    if (entries.has(canonicalAsset)) fail('provenance.assets must not contain duplicate asset entries');
    entries.add(canonicalAsset);
    if (typeof asset.path !== 'string' || !asset.path.startsWith('/')) fail('provenance asset paths must be absolute application paths');
    if (!['css', 'js'].includes(asset.type)) fail('provenance asset type must be css or js');
    requireFiniteNonNegative(asset.rawBytes, `provenance.assets.${asset.path}.rawBytes`);
    requireFiniteNonNegative(asset.gzipBytes, `provenance.assets.${asset.path}.gzipBytes`);
    if (!SHA256.test(String(asset.sha256 ?? ''))) fail('provenance asset sha256 must be 64 lowercase hex characters');
  }
}

export function parseRfc3339Utc(value, label) {
  if (typeof value !== 'string') fail(`${label} must be a valid RFC3339 UTC timestamp`);
  const match = RFC3339_UTC.exec(value);
  if (!match) fail(`${label} must be a valid RFC3339 UTC timestamp`);
  const timestamp = Date.parse(value);
  const date = new Date(timestamp);
  const expected = [date.getUTCFullYear(), date.getUTCMonth() + 1, date.getUTCDate(), date.getUTCHours(), date.getUTCMinutes(), date.getUTCSeconds()];
  const actual = match.slice(1, 7).map(Number);
  if (!Number.isFinite(timestamp) || expected.some((part, index) => part !== actual[index])) {
    fail(`${label} must be a valid RFC3339 UTC timestamp`);
  }
  return timestamp;
}

export function validateArtifactSourceIdentity(artifact) {
  if (!SHA1.test(String(artifact.sourceRef ?? ''))) fail('sourceRef must be a 40-character Git object id');
  if (!SHA256.test(String(artifact.sourceTreeHash ?? ''))) fail('sourceTreeHash must be a 64-character SHA-256 checksum');
  parseRfc3339Utc(artifact.recordedAt, 'recordedAt');
}

function gitHead() {
  return execFileSync('git', ['rev-parse', 'HEAD'], { encoding: 'utf8' }).trim();
}

function validateGitObject(objectId, objectType) {
  if (!SHA1.test(String(objectId ?? ''))) fail(`sourceRef must be a 40-character Git ${objectType}`);
  try {
    execFileSync('git', ['cat-file', '-e', `${objectId}^{${objectType}}`], { stdio: 'ignore' });
  } catch {
    fail(`sourceRef must resolve to a real Git ${objectType}`);
  }
}

export function readCurrentRuntimeContext(root = process.cwd(), env = process.env) {
  const actual = readWorktreeProvenance(root);
  const worktreeClean = execFileSync(
    'git', ['status', '--porcelain', '--untracked-files=all'], { cwd: root, encoding: 'utf8' },
  ).trim() === '';
  const context = {
    ciRunId: env.CI_RUN_ID,
    gitHead: actual.gitSha,
    worktreeClean,
    sourceTreeHash: actual.worktreeFingerprint,
    fixtureSha256: actual.fixtureSha256,
  };
  for (const [environmentKey, actualKey] of [
    ['CI_GIT_SHA', 'gitHead'],
    ['CI_WORKTREE_FINGERPRINT', 'sourceTreeHash'],
    ['CI_FIXTURE_SHA256', 'fixtureSha256'],
  ]) {
    if (env[environmentKey] !== context[actualKey]) fail(`${environmentKey} must match the current clean worktree`);
  }
  return context;
}

function validateRuntimeContext(context) {
  if (!context || !CI_RUN_ID.test(String(context.ciRunId ?? ''))) fail('CI_RUN_ID provenance is required');
  if (!SHA1.test(String(context.gitHead ?? '')) || context.gitHead !== gitHead()) {
    fail('current sourceRef must be the exact HEAD commit');
  }
  validateGitObject(context.gitHead, 'commit');
  if (context.worktreeClean !== true) fail('current release measurements require a clean worktree after local commits');
  if (!SHA256.test(String(context.sourceTreeHash ?? ''))) fail('full source/runtime-input tree digest is required');
  if (!SHA256.test(String(context.fixtureSha256 ?? ''))) fail('fixture digest is required');
  return context;
}

function validateRuntimeBinding(sample, context, label) {
  const runtime = sample.provenance?.runtime;
  if (!runtime || typeof runtime !== 'object' || Array.isArray(runtime)) fail(`${label} runtime provenance is required`);
  const bindings = {
    ciRunId: context.ciRunId,
    gitHead: context.gitHead,
    worktreeClean: true,
    sourceTreeSha256: context.sourceTreeHash,
    fixtureSha256: context.fixtureSha256,
  };
  for (const [key, expected] of Object.entries(bindings)) {
    if (runtime[key] !== expected) fail(`${label} runtime.${key} does not match the CI runtime`);
  }
  if (sample.ciRunId !== context.ciRunId || sample.sourceRef !== context.gitHead
    || sample.sourceTreeHash !== context.sourceTreeHash || sample.fixtureSha256 !== context.fixtureSha256) {
    fail(`${label} top-level runtime identity does not match the CI runtime`);
  }
}

export function validateCurrentSampleShape(sample, index = 0) {
  const label = `sample ${index + 1}`;
  if (!sample || typeof sample !== 'object' || Array.isArray(sample)) fail(`${label} must be an object`);
  requireOnlyKeys(sample, [
    '$schema', 'schemaVersion', 'kind', 'measurementKind', 'status', 'designSystemVersion',
    'route', 'viewport', 'theme', 'density', 'fixture', 'ciRunId', 'sourceRef',
    'sourceTreeHash', 'fixtureSha256', 'recordedAt', 'metrics', 'provenance',
  ], label);
  if (sample.schemaVersion !== 1) fail(`${label} schemaVersion must be 1`);
  if ('$schema' in sample && typeof sample.$schema !== 'string') fail(`${label} $schema must be a string`);
  if (sample.kind !== 'sample' || sample.status !== 'sampled' || sample.measurementKind !== 'current') {
    fail(`${label} must be a current raw sampled artifact`);
  }
  if (!SEMVER.test(String(sample.designSystemVersion ?? ''))) fail(`${label} designSystemVersion must be SemVer`);
  if (sample.route !== '/programa-general') fail(`${label} route must be /programa-general`);
  if (!VIEWPORTS.includes(sample.viewport)) fail(`${label} viewport is not supported`);
  if (!THEMES.includes(sample.theme)) fail(`${label} theme is not supported`);
  if (!DENSITIES.includes(sample.density)) fail(`${label} density must be compact or touch`);
  if (sample.fixture !== 'sanitized-pilot-v1') fail(`${label} fixture must be sanitized-pilot-v1`);
  if (!CI_RUN_ID.test(String(sample.ciRunId ?? ''))) fail(`${label} CI_RUN_ID provenance is required`);
  if (!SHA1.test(String(sample.sourceRef ?? ''))) fail(`${label} sourceRef must be a 40-character Git commit`);
  if (!SHA256.test(String(sample.sourceTreeHash ?? ''))) fail(`${label} sourceTreeHash must be a SHA-256 checksum`);
  if (!SHA256.test(String(sample.fixtureSha256 ?? ''))) fail(`${label} fixtureSha256 must be a SHA-256 checksum`);
  parseRfc3339Utc(sample.recordedAt, `${label} recordedAt`);
  if (!sample.metrics || typeof sample.metrics !== 'object' || Array.isArray(sample.metrics)) fail(`${label} metrics must be an object`);
  requireOnlyKeys(sample.metrics, [...NUMERIC_METRICS, 'adapterAssets', 'laboratoryAssets'], `${label} metrics`);
  for (const metric of NUMERIC_METRICS) requireFiniteNonNegative(sample.metrics[metric], `${label}.${metric}`);
  validatePaths(sample.metrics.adapterAssets, `${label}.adapterAssets`);
  validatePaths(sample.metrics.laboratoryAssets, `${label}.laboratoryAssets`);
  if (!sample.provenance || typeof sample.provenance !== 'object' || Array.isArray(sample.provenance)) fail(`${label} provenance must be an object`);
  requireOnlyKeys(sample.provenance, [
    'assets', 'assetInventorySha256', 'runtime', 'cacheMode', 'htmlSha256', 'duplicateRequests',
    'themeProbe', 'interactionKind', 'node', 'playwrightProject', 'sampleLabel',
  ], `${label} provenance`);
  if (sample.provenance.sampling) fail(`${label} must be raw, not previously aggregated`);
  const runtime = sample.provenance.runtime;
  if (!runtime || typeof runtime !== 'object' || Array.isArray(runtime)
    || !CI_RUN_ID.test(String(runtime.ciRunId ?? ''))
    || !SHA1.test(String(runtime.gitHead ?? ''))
    || runtime.worktreeClean !== true
    || !SHA256.test(String(runtime.sourceTreeSha256 ?? ''))
    || !SHA256.test(String(runtime.fixtureSha256 ?? ''))) {
    fail(`${label} runtime provenance has an invalid shape`);
  }
  requireOnlyKeys(runtime, ['ciRunId', 'gitHead', 'worktreeClean', 'sourceTreeSha256', 'fixtureSha256'], `${label} runtime provenance`);
  validateAssets(sample.provenance.assets);
  const assetInventorySha256 = createHash('sha256').update(JSON.stringify(sample.provenance.assets)).digest('hex');
  if (sample.provenance.assetInventorySha256 !== assetInventorySha256) fail(`${label} asset inventory digest mismatch`);
  if ('htmlSha256' in sample.provenance && !SHA256.test(String(sample.provenance.htmlSha256))) {
    fail(`${label} htmlSha256 must be a SHA-256 checksum`);
  }
  for (const key of ['cacheMode', 'interactionKind', 'node', 'playwrightProject', 'sampleLabel']) {
    if (key in sample.provenance && typeof sample.provenance[key] !== 'string') fail(`${label} provenance.${key} must be a string`);
  }
  if ('duplicateRequests' in sample.provenance) {
    if (!Array.isArray(sample.provenance.duplicateRequests)) fail(`${label} duplicateRequests must be an array`);
    for (const request of sample.provenance.duplicateRequests) {
      if (!request || typeof request !== 'object' || Array.isArray(request)) fail(`${label} duplicateRequests entries must be objects`);
      requireOnlyKeys(request, ['request', 'count'], `${label} duplicate request`);
      if (typeof request.request !== 'string' || !request.request || !Number.isInteger(request.count) || request.count < 2) {
        fail(`${label} duplicate request has an invalid shape`);
      }
    }
  }
  if ('themeProbe' in sample.provenance) {
    const probe = sample.provenance.themeProbe;
    if (!probe || typeof probe !== 'object' || Array.isArray(probe)) fail(`${label} themeProbe must be an object`);
    requireOnlyKeys(probe, ['firstPaintTheme', 'observations', 'attached'], `${label} themeProbe`);
    if (!(probe.firstPaintTheme === null || typeof probe.firstPaintTheme === 'string') || !Array.isArray(probe.observations)) {
      fail(`${label} themeProbe has an invalid shape`);
    }
    if ('attached' in probe && typeof probe.attached !== 'boolean') fail(`${label} themeProbe.attached must be boolean`);
    for (const observation of probe.observations) {
      if (!observation || typeof observation !== 'object' || Array.isArray(observation)) fail(`${label} theme observation must be an object`);
      requireOnlyKeys(observation, ['phase', 'theme', 'at'], `${label} theme observation`);
      if (typeof observation.phase !== 'string' || !(observation.theme === null || typeof observation.theme === 'string')
        || !Number.isFinite(observation.at) || observation.at < 0) fail(`${label} theme observation has an invalid shape`);
    }
  }
}

function requireCompatible(samples) {
  const reference = samples[0];
  for (let index = 1; index < samples.length; index += 1) {
    for (const key of IDENTITY_KEYS) {
      if (samples[index][key] !== reference[key]) fail(`sample ${index + 1} ${key} mismatch`);
    }
    for (const key of ['assets', 'runtime']) {
      if (canonicalJson(samples[index].provenance[key]) !== canonicalJson(reference.provenance[key])) {
        fail(`sample ${index + 1} ${key} provenance mismatch`);
      }
    }
    for (const metric of ['adapterAssets', 'laboratoryAssets']) {
      if (canonicalJson(samples[index].metrics[metric]) !== canonicalJson(reference.metrics[metric])) {
        fail(`sample ${index + 1} ${metric} mismatch`);
      }
    }
  }
}

export function validateRuntimeBudgetSamples(samples, now = new Date()) {
  if (!Array.isArray(samples) || samples.length !== 3) fail('exactly three raw samples are required');
  samples.forEach(validateCurrentSampleShape);
  requireCompatible(samples);
  const context = readCurrentRuntimeContext();
  validateRuntimeContext(context);
  samples.forEach((sample, index) => validateRuntimeBinding(sample, context, `sample ${index + 1}`));
  const timestamps = samples.map(({ recordedAt }, index) => parseRfc3339Utc(recordedAt, `sample ${index + 1} recordedAt`));
  if (new Set(timestamps).size !== 3) fail('raw samples must have unique recordedAt values');
  const nowMs = now instanceof Date ? now.getTime() : parseRfc3339Utc(now, 'aggregation now');
  const earliest = Math.min(...timestamps);
  const latest = Math.max(...timestamps);
  if (latest - earliest > MAX_COLLECTION_WINDOW_MS) fail(`collection window must not exceed ${MAX_COLLECTION_WINDOW_MS / 60000} minutes`);
  if (earliest < nowMs - MAX_SAMPLE_AGE_MS || latest > nowMs + MAX_FUTURE_SKEW_MS) {
    fail(`samples must be recent within ${MAX_SAMPLE_AGE_MS / 60000} minutes of aggregation`);
  }
  return { reference: samples[0], latestTimestamp: latest, context };
}

export function validateRetrospectiveSummary(measurement) {
  const sampling = measurement?.provenance?.sampling;
  if (sampling?.rawSamplesPreserved !== false) fail('retrospective rawSamplesPreserved must be false');
  if ('samples' in sampling || 'rawReceipts' in sampling || 'aggregateReceiptSha256' in sampling) {
    fail('retrospective summary cannot claim complete raw samples or receipts');
  }
  requireOnlyKeys(sampling, [
    'rawSamplesPreserved', 'historicalSummaryCount', 'reportedAggregation', 'incompleteSummaries',
  ], 'retrospective sampling');
  if (sampling.reportedAggregation !== 'median-of-three'
    || sampling.historicalSummaryCount !== 3
    || !Array.isArray(sampling.incompleteSummaries)
    || sampling.incompleteSummaries.length !== 3) {
    fail('retrospective incomplete summaries must describe the three historical summaries');
  }
  for (const [index, summary] of sampling.incompleteSummaries.entries()) {
    const keys = Object.keys(summary).sort();
    const expected = ['handsontableInteractionMs', 'htmlSha256', 'initializationMs', 'recordedAt'];
    if (canonicalJson(keys) !== canonicalJson(expected.sort())) fail(`retrospective summary ${index + 1} has an invalid shape`);
    parseRfc3339Utc(summary.recordedAt, `retrospective summary ${index + 1} recordedAt`);
    if (!SHA256.test(String(summary.htmlSha256 ?? ''))) fail(`retrospective summary ${index + 1} htmlSha256 is invalid`);
    requireFiniteNonNegative(summary.initializationMs, `retrospective summary ${index + 1} initializationMs`);
    requireFiniteNonNegative(summary.handsontableInteractionMs, `retrospective summary ${index + 1} handsontableInteractionMs`);
  }
  return true;
}

export function validateRetrospectiveRecoveryManifest(manifest) {
  if (!manifest || typeof manifest !== 'object' || Array.isArray(manifest)) {
    fail('retrospective recovery manifest must be an object');
  }
  requireOnlyKeys(manifest, [
    'schemaVersion', 'kind', 'designSystemVersion', 'status', 'recordedAt',
    'measurementPath', 'measurementSha256', 'assetInventorySha256', 'metricsSha256',
    'rawSamplesPreserved', 'versionEvidence', 'sourceHistory',
  ], 'retrospective recovery manifest');
  if (manifest.schemaVersion !== 1
    || manifest.kind !== 'retrospective-recovery-receipt'
    || manifest.designSystemVersion !== '0.3.3'
    || manifest.status !== 'retrospective-incomplete') {
    fail('retrospective recovery manifest identity is invalid');
  }
  parseRfc3339Utc(manifest.recordedAt, 'retrospective recovery manifest recordedAt');
  if (manifest.measurementPath !== BASELINE_MEASUREMENT_PATH
    || !SHA256.test(String(manifest.measurementSha256 ?? ''))
    || !SHA256.test(String(manifest.assetInventorySha256 ?? ''))
    || !SHA256.test(String(manifest.metricsSha256 ?? ''))) {
    fail('retrospective recovery manifest checksums are invalid');
  }
  if (manifest.rawSamplesPreserved !== false) {
    fail('retrospective recovery manifest cannot claim preserved raw samples');
  }
  if (typeof manifest.versionEvidence !== 'string' || !manifest.versionEvidence) {
    fail('retrospective recovery manifest versionEvidence must be a string');
  }
  const sourceHistory = manifest.sourceHistory;
  if (!sourceHistory || typeof sourceHistory !== 'object' || Array.isArray(sourceHistory)) {
    fail('retrospective recovery manifest sourceHistory must be an object');
  }
  requireOnlyKeys(sourceHistory, [
    'originCommitAvailable', 'recoveryFromOriginGitHistory',
    'recordedLocalCheckpointTree', 'note',
  ], 'retrospective recovery manifest sourceHistory');
  if (sourceHistory.originCommitAvailable !== false
    || sourceHistory.recoveryFromOriginGitHistory !== false
    || !SHA1.test(String(sourceHistory.recordedLocalCheckpointTree ?? ''))
    || typeof sourceHistory.note !== 'string'
    || !sourceHistory.note.toLowerCase().includes('not recoverable from origin git history')) {
    fail('retrospective recovery manifest must explicitly deny origin Git recovery');
  }
  return true;
}

export function validateApprovedBaselineProvenance(baseline) {
  const recovery = baseline.recovery;
  if (!recovery
    || recovery.manifestPath !== BASELINE_MANIFEST_PATH
    || !SHA256.test(String(recovery.manifestSha256 ?? ''))
    || recovery.measurementPath !== BASELINE_MEASUREMENT_PATH
    || !SHA256.test(String(recovery.measurementSha256 ?? ''))) {
    fail('approved baseline recovery provenance is required');
  }

  const rawManifest = readFileSync(path.resolve(recovery.manifestPath), 'utf8');
  const manifestChecksum = createHash('sha256').update(rawManifest).digest('hex');
  if (manifestChecksum !== recovery.manifestSha256) fail('approved baseline manifest checksum mismatch');
  const manifest = JSON.parse(rawManifest);
  validateRetrospectiveRecoveryManifest(manifest);

  const rawMeasurement = readFileSync(path.resolve(recovery.measurementPath), 'utf8');
  const checksum = createHash('sha256').update(rawMeasurement).digest('hex');
  if (checksum !== recovery.measurementSha256) fail('approved baseline recovery checksum mismatch');
  if (manifest.measurementPath !== recovery.measurementPath
    || manifest.measurementSha256 !== recovery.measurementSha256) {
    fail('approved baseline recovery manifest does not identify the recovery measurement');
  }
  const measurement = JSON.parse(rawMeasurement);
  for (const key of [
    'designSystemVersion', 'route', 'viewport', 'theme', 'density', 'fixture',
    'sourceRef', 'sourceTreeHash', 'recordedAt',
  ]) {
    if (measurement[key] !== baseline[key]) fail(`approved baseline ${key} does not match recovery measurement`);
  }
  if (manifest.recordedAt !== measurement.recordedAt
    || manifest.metricsSha256 !== sha256Canonical(measurement.metrics)
    || canonicalJson(manifest.sourceHistory) !== canonicalJson(measurement.provenance?.sourceHistory)) {
    fail('approved baseline recovery manifest does not match the retrospective measurement');
  }
  validateRetrospectiveSummary(measurement);
  validateAssets(measurement.provenance?.assets);
  const assetHash = createHash('sha256').update(JSON.stringify(measurement.provenance.assets)).digest('hex');
  if (assetHash !== baseline.sourceTreeHash
    || measurement.provenance.assetInventorySha256 !== assetHash
    || manifest.assetInventorySha256 !== assetHash) {
    fail('approved baseline asset inventory checksum mismatch');
  }
  return true;
}
