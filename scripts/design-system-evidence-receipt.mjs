import { spawnSync } from 'node:child_process';
import { createHash } from 'node:crypto';
import {
  existsSync, readFileSync, realpathSync, statSync,
} from 'node:fs';
import path from 'node:path';

import { canonicalGateCommand } from './design-system-gate-command-registry.mjs';

const receiptFields = [
  'artifact', 'artifactSha256', 'command', 'commandId', 'exitCode', 'sourceFingerprint',
  'sourceRef', 'summary',
];
const evidencePrefix = 'docs/design-system/evidence/';
const fixturePath = 'database/fixtures/design-system-ci.sql';

function sha256(value) {
  return createHash('sha256').update(value).digest('hex');
}

function sameFields(value, fields) {
  return value && typeof value === 'object' && !Array.isArray(value)
    && JSON.stringify(Object.keys(value).sort()) === JSON.stringify([...fields].sort());
}

function git(root, args) {
  return spawnSync('git', args, {
    cwd: root,
    encoding: null,
    stdio: ['ignore', 'pipe', 'ignore'],
  });
}

function resolveCommit(root, sourceRef) {
  if (!/^[a-f0-9]{40}$/.test(String(sourceRef ?? ''))) return null;
  const result = git(root, ['rev-parse', '--verify', `${sourceRef}^{commit}`]);
  return result.status === 0 ? result.stdout.toString('utf8').trim() : null;
}

function committedFile(root, commit, relativePath) {
  const result = git(root, ['show', `${commit}:${relativePath}`]);
  return result.status === 0 ? result.stdout : null;
}

function expectedSourceFingerprint(root, commit) {
  const result = git(root, ['ls-tree', '-r', '--full-tree', commit]);
  return result.status === 0 ? sha256(result.stdout) : null;
}

function validEvidencePath(relativePath) {
  if (typeof relativePath !== 'string' || relativePath.includes('\\')) return false;
  if (!/^[a-zA-Z0-9._/-]+\.json$/.test(relativePath)) return false;
  if (path.posix.normalize(relativePath) !== relativePath) return false;
  if (!relativePath.startsWith(evidencePrefix)) return false;
  return !relativePath.split('/').some((segment) => segment === '.omo' || segment === '.env');
}

function validateAccessibilityArtifact(content, surface, failures) {
  let artifact;
  try {
    artifact = JSON.parse(content.toString('utf8'));
  } catch (error) {
    if (!(error instanceof SyntaxError)) throw error;
    failures.push(`accessibility-insights: ${surface} artifact must be valid JSON`);
    return;
  }
  const valid = artifact?.reviewKind === 'basic-automated-review'
    && artifact?.surface === surface
    && artifact?.failedRules === 0
    && artifact?.failedInstances === 0
    && !/FastPass|WCAG/i.test(JSON.stringify(artifact));
  if (!valid) failures.push(`accessibility-insights: ${surface} artifact violates the basic automated review contract`);
}

export function evidenceReceiptFailures(root, gate, receipt) {
  const { id: gateId, surface, fixtureRequired } = gate;
  const failures = [];
  if (!receipt || typeof receipt !== 'object' || Array.isArray(receipt)) {
    failures.push(`${gateId}: evidence receipt must be an object`);
    return failures;
  }
  const expectedFields = [
    ...receiptFields,
    ...(fixtureRequired ? ['fixtureSha256'] : []),
    ...(surface ? ['surface'] : []),
  ];
  if (!sameFields(receipt, expectedFields)) {
    failures.push(`${gateId}: evidence receipt fields are incomplete or unexpected`);
    return failures;
  }
  if (typeof receipt.summary !== 'string' || receipt.summary.trim().length < 12) {
    failures.push(`${gateId}: evidence summary is insufficient`);
  }
  const canonicalCommand = canonicalGateCommand(gateId);
  if (!canonicalCommand || receipt.commandId !== canonicalCommand.commandId
    || receipt.command !== canonicalCommand.command) {
    failures.push(`${gateId}: evidence command is not canonical`);
  }
  if (receipt.exitCode !== 0) failures.push(`${gateId}: evidence exitCode must be zero`);
  if (!validEvidencePath(receipt.artifact)) {
    failures.push(`${gateId}: invalid persistent evidence path`);
    return failures;
  }
  const absoluteArtifact = path.join(root, receipt.artifact);
  if (!existsSync(absoluteArtifact) || !statSync(absoluteArtifact).isFile()) {
    failures.push(`${gateId}: evidence artifact is missing`);
    return failures;
  }
  const evidenceRoot = path.join(root, evidencePrefix);
  if (!realpathSync(absoluteArtifact).startsWith(`${realpathSync(evidenceRoot)}${path.sep}`)) {
    failures.push(`${gateId}: invalid persistent evidence path`);
    return failures;
  }
  const artifact = readFileSync(absoluteArtifact);
  if (!/^[a-f0-9]{64}$/.test(receipt.artifactSha256)
    || sha256(artifact) !== receipt.artifactSha256) {
    failures.push(`${gateId}: artifactSha256 does not match the evidence artifact`);
    return failures;
  }
  const commit = resolveCommit(root, receipt.sourceRef);
  if (!commit) {
    failures.push(`${gateId}: sourceRef must resolve to a Git commit`);
    return failures;
  }
  if (expectedSourceFingerprint(root, commit) !== receipt.sourceFingerprint) {
    failures.push(`${gateId}: sourceFingerprint does not match sourceRef`);
  }
  const committedArtifact = committedFile(root, commit, receipt.artifact);
  if (!committedArtifact || sha256(committedArtifact) !== receipt.artifactSha256) {
    failures.push(`${gateId}: evidence artifact is stale relative to sourceRef`);
  }
  if (fixtureRequired) {
    const currentFixture = existsSync(path.join(root, fixturePath))
      ? readFileSync(path.join(root, fixturePath)) : null;
    const committedFixture = committedFile(root, commit, fixturePath);
    if (!currentFixture || !committedFixture
      || sha256(currentFixture) !== receipt.fixtureSha256
      || sha256(committedFixture) !== receipt.fixtureSha256) {
      failures.push(`${gateId}: fixtureSha256 does not match the current and committed fixture`);
    }
  }
  if (surface) validateAccessibilityArtifact(artifact, surface, failures);
  return failures;
}
