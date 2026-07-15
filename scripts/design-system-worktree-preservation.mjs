#!/usr/bin/env node

import { execFileSync } from 'node:child_process';
import { createHash } from 'node:crypto';
import { lstatSync, readFileSync, readlinkSync } from 'node:fs';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const ROOT = resolve(dirname(fileURLToPath(import.meta.url)), '..');
const MAX_BUFFER = 128 * 1024 * 1024;

const sha256 = (value) => createHash('sha256').update(value).digest('hex');

const gitBuffer = (args) => execFileSync('git', args, {
  cwd: ROOT,
  encoding: null,
  maxBuffer: MAX_BUFFER,
});

const gitText = (args) => execFileSync('git', args, {
  cwd: ROOT,
  encoding: 'utf8',
  maxBuffer: MAX_BUFFER,
}).trim();

const parseStatus = (buffer) => buffer
  .toString('utf8')
  .split('\0')
  .filter(Boolean)
  .map((entry) => ({ code: entry.slice(0, 2), path: entry.slice(3) }));

const canonicalStatus = (entries) => Buffer.from(
  entries.map(({ code, path }) => `${code}\0${path}\0`).join(''),
);

const captureUntracked = (entries) => entries
  .filter(({ code }) => code === '??')
  .map(({ path }) => {
    const absolute = resolve(ROOT, path);
    const stat = lstatSync(absolute);
    let digest = 'directory';
    if (stat.isFile()) digest = sha256(readFileSync(absolute));
    if (stat.isSymbolicLink()) digest = sha256(Buffer.from(readlinkSync(absolute)));
    return {
      path,
      mode: stat.mode.toString(8),
      size: stat.size,
      sha256: digest,
    };
  })
  .sort((left, right) => (left.path < right.path ? -1 : left.path > right.path ? 1 : 0));

const canonicalUntracked = (entries) => Buffer.from(entries.map((entry) => [
  entry.path,
  entry.mode,
  entry.size,
  entry.sha256,
].join('\0') + '\0').join(''));

const capture = (manifest) => {
  const statusBuffer = gitBuffer([
    'status',
    '--porcelain=v1',
    '-z',
    '--untracked-files=all',
  ]);
  const statusEntries = parseStatus(statusBuffer);
  const untrackedEntries = captureUntracked(statusEntries);
  const commitList = gitText(['rev-list', '--reverse', 'origin/main..HEAD'])
    .split('\n')
    .filter(Boolean);
  const divergence = gitText([
    'rev-list',
    '--left-right',
    '--count',
    'origin/main...HEAD',
  ]).split(/\s+/).map(Number);
  const controls = manifest.ignoredControlSurfaces.map(({ path }) => ({
    path,
    sha256: sha256(readFileSync(resolve(ROOT, path))),
  }));

  return {
    repository: {
      branch: gitText(['branch', '--show-current']),
      head: gitText(['rev-parse', 'HEAD']),
      headTree: gitText(['rev-parse', 'HEAD^{tree}']),
      originMain: gitText(['rev-parse', 'origin/main']),
      divergence: { behind: divergence[0], ahead: divergence[1] },
    },
    committedWork: {
      count: commitList.length,
      sha256: sha256(Buffer.from(commitList.join('\n') + (commitList.length ? '\n' : ''))),
      commits: commitList,
    },
    staged: {
      count: statusEntries.filter(({ code }) => code !== '??' && code[0] !== ' ').length,
      binaryDiffSha256: sha256(gitBuffer(['diff', '--cached', '--binary'])),
      entries: statusEntries.filter(({ code }) => code !== '??' && code[0] !== ' '),
    },
    unstaged: {
      trackedCount: statusEntries.filter(({ code }) => code !== '??' && code[1] !== ' ').length,
      binaryDiffSha256: sha256(gitBuffer(['diff', '--binary'])),
    },
    untracked: {
      count: untrackedEntries.length,
      contentManifestSha256: sha256(canonicalUntracked(untrackedEntries)),
      entries: untrackedEntries,
    },
    status: {
      entryCount: statusEntries.length,
      porcelainV1ZUntrackedAllSha256: sha256(statusBuffer),
    },
    ignoredControlSurfaces: controls,
    statusEntries,
  };
};

const stableJson = (value) => JSON.stringify(value);

const verifyClassification = (manifest, current, failures) => {
  const categories = Object.entries(manifest.classification || {});
  const classified = categories.flatMap(([, category]) => category.entries || []);
  const expectedStatus = [...current.statusEntries].sort((left, right) => (
    left.path.localeCompare(right.path) || left.code.localeCompare(right.code)
  ));
  const actualStatus = [...classified].sort((left, right) => (
    left.path.localeCompare(right.path) || left.code.localeCompare(right.code)
  ));

  if (stableJson(actualStatus) !== stableJson(expectedStatus)) {
    failures.push('classification does not cover the current status exactly once');
  }

  const unique = new Set(classified.map(({ code, path }) => `${code}\0${path}`));
  if (unique.size !== classified.length) {
    failures.push('classification contains duplicate status entries');
  }

  for (const [name, category] of categories) {
    if (category.count !== category.entries.length) {
      failures.push(`classification.${name}.count is stale`);
    }
    if (category.pathSetSha256 !== sha256(canonicalStatus(category.entries))) {
      failures.push(`classification.${name}.pathSetSha256 is stale`);
    }
  }
};

const compareSection = (name, expected, actual, failures) => {
  if (stableJson(expected) !== stableJson(actual)) failures.push(`${name} changed`);
};

const check = (manifestPath) => {
  const absolute = resolve(ROOT, manifestPath);
  const manifest = JSON.parse(readFileSync(absolute, 'utf8'));
  const current = capture(manifest);
  const failures = [];

  if (manifest.schemaVersion !== 1) failures.push('schemaVersion must be 1');
  compareSection('repository', manifest.repository, current.repository, failures);
  compareSection('committedWork', manifest.committedWork, current.committedWork, failures);
  compareSection('staged', manifest.staged, current.staged, failures);
  compareSection('unstaged', manifest.unstaged, current.unstaged, failures);
  compareSection('untracked', manifest.untracked, current.untracked, failures);
  compareSection('status', manifest.status, current.status, failures);
  compareSection(
    'ignoredControlSurfaces',
    manifest.ignoredControlSurfaces,
    current.ignoredControlSurfaces,
    failures,
  );
  verifyClassification(manifest, current, failures);

  if (failures.length > 0) {
    console.error('Worktree preservation: FAIL');
    for (const failure of failures) console.error(`- ${failure}`);
    process.exitCode = 1;
    return;
  }

  console.log('Worktree preservation: PASS');
  console.log(`HEAD ${manifest.repository.head}`);
  console.log(
    `staged ${manifest.staged.count}; tracked unstaged ${manifest.unstaged.trackedCount}; untracked ${manifest.untracked.count}`,
  );
};

const [command = '', manifestPath = ''] = process.argv.slice(2);
if (command !== 'check' || !manifestPath) {
  console.error('Usage: node scripts/design-system-worktree-preservation.mjs check <manifest.json>');
  process.exitCode = 2;
} else {
  check(manifestPath);
}
