import { spawnSync } from 'node:child_process';
import { readFileSync } from 'node:fs';
import path from 'node:path';

const activationPaths = [
  'docs/design-system/closeout-evidence.json',
  'docs/design-system/version.json',
  'docs/design-system/stable-api-1.0.0.json',
];

function git(root, args) {
  return spawnSync('git', args, {
    cwd: root,
    encoding: null,
    stdio: ['ignore', 'pipe', 'ignore'],
  });
}

function committedFile(root, relativePath) {
  const result = git(root, ['show', `HEAD:${relativePath}`]);
  return result.status === 0 ? result.stdout : null;
}

function committedActivation(root, failures) {
  const documents = new Map();
  for (const relativePath of activationPaths) {
    const current = readFileSync(path.join(root, relativePath));
    const committed = committedFile(root, relativePath);
    if (!committed || !current.equals(committed)) {
      failures.push(`activation: ${relativePath} must match HEAD exactly`);
      continue;
    }
    documents.set(relativePath, JSON.parse(committed.toString('utf8')));
  }
  return documents;
}

// Version que cuenta como "sistema activado": cualquier SemVer con major >= 1.
// La activacion fue un hito unico cumplido en 1.0.0 (D2 del spec 2026-08-04); las
// versiones posteriores lo heredan en vez de volver a pedirlo. Vive aqui y se
// comparte con design-system-closeout-contract.mjs para que los dos gates no
// puedan divergir.
export const ACTIVATED_VERSION_PATTERN = /^([1-9]\d*)\.\d+\.\d+$/;

export function activationGitFailures(root, gateIds) {
  const failures = [];
  const status = git(root, ['status', '--porcelain=v1', '--untracked-files=all']);
  if (status.status !== 0 || status.stdout.length !== 0) {
    if (process.env.DS_ACTIVATION_STRICT === '1') {
      failures.push('activation: worktree and index must be clean');
    } else {
      console.error('[activation] aviso: worktree sucio (no bloquea en local; CI usa DS_ACTIVATION_STRICT=1)');
    }
  }
  const documents = committedActivation(root, failures);
  const closeout = documents.get(activationPaths[0]);
  const version = documents.get(activationPaths[1]);
  const stableApi = documents.get(activationPaths[2]);
  const committedGateIds = closeout?.gates?.map(({ id }) => id);
  const committedPassed = JSON.stringify(committedGateIds) === JSON.stringify(gateIds)
    && closeout.gates.every(({ status: gateStatus }) => gateStatus === 'passed');
  if (!committedPassed || !ACTIVATED_VERSION_PATTERN.test(version?.version ?? '')
    || version?.status !== 'stable'
    || stableApi?.releaseStatus !== 'guaranteed') {
    failures.push('activation: HEAD must contain the complete passed activation (SemVer major >= 1, stable)');
  }
  return failures;
}
