import { execFileSync } from 'node:child_process';
import { createHash } from 'node:crypto';
import {
  cpSync, mkdirSync, readFileSync, readdirSync, writeFileSync,
} from 'node:fs';
import path from 'node:path';

import { canonicalGateCommand } from '../../scripts/design-system-gate-command-registry.mjs';
import {
  createCloseoutFixture, repositoryRoot, updateJson,
} from './closeout-contract-fixture.mjs';

const fixtureGateIds = new Set([
  'runtime', 'runtime-budgets', 'global-table-safety', 'full-app-flow',
]);

export function git(fixtureRoot, args) {
  return execFileSync('git', args, { cwd: fixtureRoot, encoding: 'utf8' }).trim();
}

export function sha256(value) {
  return createHash('sha256').update(value).digest('hex');
}

export function writeArtifact(fixtureRoot, relativePath, value) {
  const file = path.join(fixtureRoot, relativePath);
  mkdirSync(path.dirname(file), { recursive: true });
  writeFileSync(file, `${JSON.stringify(value, null, 2)}\n`);
}

function writeCandidateArtifacts(fixtureRoot) {
  updateJson(fixtureRoot, 'docs/design-system/closeout-evidence.json', (closeout) => {
    for (const gate of closeout.gates) {
      const artifact = `docs/design-system/evidence/${gate.id}.json`;
      writeArtifact(fixtureRoot, artifact, { gateId: gate.id, result: 'passed' });
    }
  });
}

function writeActivationDocuments(fixtureRoot, source) {
  const docsRoot = path.join(fixtureRoot, 'docs/design-system');
  for (const relativePath of readdirSync(docsRoot, { recursive: true })) {
    if (!relativePath.endsWith('.json')) continue;
    updateJson(fixtureRoot, path.join('docs/design-system', relativePath), (document) => {
      if (Object.hasOwn(document, 'designSystemVersion')) document.designSystemVersion = '1.0.0';
    });
  }
  updateJson(fixtureRoot, 'docs/design-system/version.json', (version) => {
    version.version = '1.0.0';
    version.status = 'stable';
  });
  updateJson(fixtureRoot, 'docs/design-system/stable-api-1.0.0.json', (stableApi) => {
    stableApi.releaseStatus = 'guaranteed';
  });
  updateJson(fixtureRoot, 'docs/design-system/closeout-evidence.json', (closeout) => {
    for (const gate of closeout.gates) {
      gate.status = 'passed';
      gate.verifiedAt = '2026-07-15T12:00:00Z';
      const artifact = `docs/design-system/evidence/${gate.id}.json`;
      const canonicalCommand = canonicalGateCommand(gate.id);
      gate.evidence = [{
        summary: `Objective receipt for ${gate.id}`,
        commandId: canonicalCommand.commandId,
        command: canonicalCommand.command,
        exitCode: 0,
        artifact,
        artifactSha256: sha256(readFileSync(path.join(fixtureRoot, artifact))),
        sourceRef: source.ref,
        sourceFingerprint: source.fingerprint,
        ...(fixtureGateIds.has(gate.id) ? { fixtureSha256: source.fixtureSha256 } : {}),
      }];
    }
  });
}

export function createActivatedFixture() {
  const fixtureRoot = createCloseoutFixture();
  const fixturePath = 'database/fixtures/design-system-ci.sql';
  mkdirSync(path.dirname(path.join(fixtureRoot, fixturePath)), { recursive: true });
  cpSync(path.join(repositoryRoot, fixturePath), path.join(fixtureRoot, fixturePath));
  writeCandidateArtifacts(fixtureRoot);
  git(fixtureRoot, ['init', '-b', 'main']);
  git(fixtureRoot, ['config', 'user.name', 'Closeout Test']);
  git(fixtureRoot, ['config', 'user.email', 'closeout@ci.invalid']);
  git(fixtureRoot, ['add', '.']);
  git(fixtureRoot, ['commit', '-m', 'candidate evidence']);
  const ref = git(fixtureRoot, ['rev-parse', 'HEAD']);
  writeActivationDocuments(fixtureRoot, {
    ref,
    fingerprint: sha256(execFileSync('git', ['ls-tree', '-r', '--full-tree', ref], { cwd: fixtureRoot })),
    fixtureSha256: sha256(readFileSync(path.join(fixtureRoot, fixturePath))),
  });
  return fixtureRoot;
}
