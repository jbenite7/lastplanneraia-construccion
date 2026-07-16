#!/usr/bin/env node

import { execFileSync } from 'node:child_process';
import { createHash, randomUUID } from 'node:crypto';
import { existsSync, lstatSync, readFileSync, readlinkSync, realpathSync, renameSync, unlinkSync, writeFileSync } from 'node:fs';
import { basename, dirname, extname, isAbsolute, join, relative, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const ROOT = realpathSync(resolve(dirname(fileURLToPath(import.meta.url)), '..'));
const MAX_BUFFER = 128 * 1024 * 1024;
const GIT_TIMEOUT_MS = 10_000;
const UNREVIEWED_CATEGORY = 'unclassified-needs-review';

const sha256 = (value) => createHash('sha256').update(value).digest('hex');

const gitBuffer = (args) => execFileSync('git', args, {
  cwd: ROOT, encoding: null, maxBuffer: MAX_BUFFER, timeout: GIT_TIMEOUT_MS,
});

const gitText = (args) => execFileSync('git', args, {
  cwd: ROOT, encoding: 'utf8', maxBuffer: MAX_BUFFER, timeout: GIT_TIMEOUT_MS,
}).trim();

const gitSucceeds = (args) => {
  try {
    execFileSync('git', args, { cwd: ROOT, stdio: 'ignore', timeout: GIT_TIMEOUT_MS });
    return true;
  } catch { return false; }
};

const parseStatus = (buffer) => {
  const tokens = buffer.toString('utf8').split('\0').filter(Boolean);
  const entries = [];
  for (let index = 0; index < tokens.length; index += 1) {
    const token = tokens[index]; const entry = { code: token.slice(0, 2), path: token.slice(3) };
    if (/[RC]/.test(entry.code)) {
      const originalPath = tokens[index += 1];
      if (originalPath === undefined) throw new TypeError('rename/copy status record is incomplete');
      entries.push({ ...entry, originalPath });
    } else entries.push(entry);
  }
  return entries;
};

const canonicalStatus = (entries) => Buffer.from(entries.map(({ code, path, originalPath }) => (
  `${code}\0${path}\0${originalPath === undefined ? '' : `${originalPath}\0`}`
)).join(''));

const porcelainStatus = (entries) => Buffer.from(entries.map(({ code, path, originalPath }) => (
  `${code} ${path}\0${originalPath === undefined ? '' : `${originalPath}\0`}`
)).join(''));

const captureUntracked = (entries) => entries
  .filter(({ code }) => code === '??')
  .map(({ path }) => {
    const absolute = resolve(ROOT, path);
    const stat = lstatSync(absolute);
    let digest = 'directory';
    if (stat.isFile()) digest = sha256(readFileSync(absolute));
    if (stat.isSymbolicLink()) digest = sha256(Buffer.from(readlinkSync(absolute)));
    return { path, mode: stat.mode.toString(8), size: stat.size, sha256: digest };
  })
  .sort((left, right) => (left.path < right.path ? -1 : left.path > right.path ? 1 : 0));

const canonicalUntracked = (entries) => Buffer.from(entries.map((entry) => (
  [entry.path, entry.mode, entry.size, entry.sha256].join('\0') + '\0'
)).join(''));

const capture = (manifest, excludedPath = null) => {
  const rawStatusBuffer = gitBuffer(['status', '--porcelain=v1', '-z', '--untracked-files=all']);
  const statusEntries = parseStatus(rawStatusBuffer)
    .filter(({ path, originalPath }) => path !== excludedPath && originalPath !== excludedPath);
  const statusBuffer = excludedPath ? porcelainStatus(statusEntries) : rawStatusBuffer;
  const untrackedEntries = captureUntracked(statusEntries);
  const commitList = gitText(['rev-list', '--reverse', 'origin/main..HEAD'])
    .split('\n')
    .filter(Boolean);
  const divergence = gitText(['rev-list', '--left-right', '--count', 'origin/main...HEAD'])
    .split(/\s+/).map(Number);
  if (!Array.isArray(manifest.ignoredControlSurfaces)) {
    throw new TypeError('ignoredControlSurfaces must be an array');
  }
  const controls = manifest.ignoredControlSurfaces.map(({ path }) => (
    { path, sha256: sha256(readFileSync(resolve(ROOT, path))) }
  ));
  const diffArgs = (cached) => [
    'diff',
    ...(cached ? ['--cached'] : []),
    '--binary',
    ...(excludedPath ? ['--', '.', `:(exclude,literal)${excludedPath}`] : []),
  ];

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
      binaryDiffSha256: sha256(gitBuffer(diffArgs(true))),
      entries: statusEntries.filter(({ code }) => code !== '??' && code[0] !== ' '),
    },
    unstaged: {
      trackedCount: statusEntries.filter(({ code }) => code !== '??' && code[1] !== ' ').length,
      binaryDiffSha256: sha256(gitBuffer(diffArgs(false))),
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

const repositoryPath = (absolute) => {
  const path = relative(ROOT, absolute);
  return path && !path.startsWith('..') && !isAbsolute(path) ? path : null;
};

const refreshClassification = (classification, statusEntries) => {
  if (!classification || typeof classification !== 'object' || Array.isArray(classification)) {
    throw new TypeError('classification must be an object');
  }
  const seenPaths = new Set();
  const ownerByPath = new Map();
  for (const [name, category] of Object.entries(classification)) {
    if (!category || !Array.isArray(category.entries)) {
      throw new TypeError(`classification.${name}.entries must be an array`);
    }
    for (const { path } of category.entries) {
      if (seenPaths.has(path)) throw new TypeError(`classification duplicates path: ${path}`);
      seenPaths.add(path);
      if (name !== UNREVIEWED_CATEGORY) ownerByPath.set(path, name);
    }
  }

  const categories = Object.keys(classification);
  if (!categories.includes(UNREVIEWED_CATEGORY)) categories.push(UNREVIEWED_CATEGORY);
  return Object.fromEntries(categories.map((name) => {
    const entries = statusEntries.filter(({ path }) => (
      name === UNREVIEWED_CATEGORY ? !ownerByPath.has(path) : ownerByPath.get(path) === name
    ));
    return [name, {
      count: entries.length,
      pathSetSha256: sha256(canonicalStatus(entries)),
      entries,
    }];
  }));
};

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
  const absolute = realpathSync(resolve(ROOT, manifestPath));
  const manifest = JSON.parse(readFileSync(absolute, 'utf8'));
  const current = capture(manifest, repositoryPath(absolute));
  const failures = [];

  if (manifest.schemaVersion !== 1) failures.push('schemaVersion must be 1');
  compareSection('repository', manifest.repository, current.repository, failures);
  compareSection('committedWork', manifest.committedWork, current.committedWork, failures);
  compareSection('staged', manifest.staged, current.staged, failures);
  compareSection('unstaged', manifest.unstaged, current.unstaged, failures);
  compareSection('untracked', manifest.untracked, current.untracked, failures);
  compareSection('status', manifest.status, current.status, failures);
  compareSection('ignoredControlSurfaces', manifest.ignoredControlSurfaces, current.ignoredControlSurfaces, failures);
  verifyClassification(manifest, current, failures);
  if ((manifest.classification?.[UNREVIEWED_CATEGORY]?.entries || []).length > 0) {
    failures.push('classification has unreviewed paths');
  }

  if (failures.length > 0) {
    console.error('Worktree preservation: FAIL');
    for (const failure of failures) console.error(`- ${failure}`);
    process.exitCode = 1;
    return;
  }

  console.log('Worktree preservation: PASS');
  console.log(`HEAD ${manifest.repository.head}`);
  console.log(`staged ${manifest.staged.count}; tracked unstaged ${manifest.unstaged.trackedCount}; untracked ${manifest.untracked.count}`);
};

const captureTo = (sourcePath, destinationPath) => {
  const sourceInput = resolve(ROOT, sourcePath);
  if (lstatSync(sourceInput).isSymbolicLink()) throw new TypeError('template must not be a symbolic link');
  const source = realpathSync(sourceInput);
  const parent = realpathSync(dirname(resolve(ROOT, destinationPath)));
  const destination = join(parent, basename(destinationPath));
  if (extname(destination) !== '.json') throw new TypeError('destination must end in .json');
  if (existsSync(destination) && !lstatSync(destination).isFile()) throw new TypeError('destination must be a regular file');

  const destinationInRepository = repositoryPath(destination);
  if (destinationInRepository && destination !== source) {
    const ignored = gitSucceeds(['check-ignore', '--quiet', '--', destinationInRepository]);
    const tracked = gitSucceeds(['ls-files', '--error-unmatch', '--', destinationInRepository]);
    if (!ignored || tracked) throw new TypeError('repository destination must be ignored or the template itself');
  }

  const template = JSON.parse(readFileSync(source, 'utf8'));
  if (template.schemaVersion !== 1) throw new TypeError('schemaVersion must be 1');
  const current = capture(template, destinationInRepository);
  const { statusEntries, ...snapshot } = current;
  const manifest = {
    ...template,
    capturedAt: new Date().toISOString(),
    ...snapshot,
    classification: refreshClassification(template.classification, statusEntries),
  };
  const temporary = `${destination}.tmp-${process.pid}-${randomUUID()}`;
  const mode = existsSync(destination) ? lstatSync(destination).mode & 0o777 : 0o644;
  try {
    writeFileSync(temporary, `${JSON.stringify(manifest, null, 2)}\n`, { flag: 'wx', mode });
    renameSync(temporary, destination);
  } finally {
    if (existsSync(temporary)) unlinkSync(temporary);
  }
  console.log('Worktree preservation: CAPTURED');
  console.log(`manifest ${destination}`);
};

const usage = () => {
  console.error('Usage: node scripts/design-system-worktree-preservation.mjs check <manifest.json>\n       node scripts/design-system-worktree-preservation.mjs capture <template.json> <destination.json>'); process.exitCode = 2;
};

const args = process.argv.slice(2);
try {
  if (args[0] === 'check' && args.length === 2) check(args[1]);
  else if (args[0] === 'capture' && args.length === 3) captureTo(args[1], args[2]);
  else usage();
} catch (error) {
  console.error(args[0] === 'capture' ? 'Worktree preservation: CAPTURE REFUSED' : 'Worktree preservation: ERROR');
  console.error(`- ${error instanceof Error ? error.message : String(error)}`);
  process.exitCode = 2;
}
