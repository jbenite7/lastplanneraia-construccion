import assert from 'node:assert/strict';
import { execFileSync, spawnSync } from 'node:child_process';
import { copyFileSync, mkdirSync, mkdtempSync, readdirSync, readFileSync, rmSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import test from 'node:test';

const ROOT = new URL('../../', import.meta.url);
const MANIFEST = 'goals/design-system-nucleo-gobernanza/worktree-preservation.json';

const createRepositoryFixture = (classificationEntries, mutate = ({ directory }) => {
  writeFileSync(join(directory, 'work.txt'), 'after\n');
}) => {
  const directory = mkdtempSync(join(tmpdir(), 'ds-preservation-'));
  const scriptPath = join(directory, 'scripts', 'design-system-worktree-preservation.mjs');
  const manifestPath = join(directory, 'worktree-preservation.json');
  const runGit = (...args) => execFileSync('git', args, { cwd: directory });

  mkdirSync(join(directory, 'scripts'));
  copyFileSync(new URL('../../scripts/design-system-worktree-preservation.mjs', import.meta.url), scriptPath);
  writeFileSync(join(directory, 'goal.md'), 'fixture control surface\n');
  writeFileSync(join(directory, 'unsafe.json'), 'preserve me\n');
  writeFileSync(join(directory, 'work.txt'), 'before\n');
  writeFileSync(manifestPath, `${JSON.stringify({
    schemaVersion: 1,
    capturedAt: '2026-01-01T00:00:00.000Z',
    ignoredControlSurfaces: [{ path: 'goal.md', sha256: '' }],
    classification: {
      fixture: {
        count: classificationEntries.length,
        pathSetSha256: '',
        entries: classificationEntries,
      },
    },
  }, null, 2)}\n`);
  runGit('init', '--initial-branch=main');
  runGit('add', '.');
  runGit('-c', 'user.name=Preservation Test', '-c', 'user.email=test@example.invalid', 'commit', '-m', 'fixture');
  runGit('update-ref', 'refs/remotes/origin/main', 'HEAD');
  mutate({ directory, runGit });
  return { directory, manifestPath };
};

test('worktree preservation verifier rejects a stale snapshot', () => {
  const temporary = mkdtempSync(join(tmpdir(), 'ds-preservation-'));
  const tamperedPath = join(temporary, 'worktree-preservation.json');

  try {
    const manifest = JSON.parse(readFileSync(new URL(`../../${MANIFEST}`, import.meta.url)));
    manifest.repository.head = '0000000000000000000000000000000000000000';
    writeFileSync(tamperedPath, `${JSON.stringify(manifest, null, 2)}\n`);

    const result = spawnSync(
      process.execPath,
      ['scripts/design-system-worktree-preservation.mjs', 'check', tamperedPath],
      {
        cwd: ROOT,
        encoding: 'utf8',
      },
    );

    assert.equal(result.status, 1);
    assert.match(result.stderr, /Worktree preservation: FAIL/);
    assert.match(result.stderr, /repository changed/);
  } finally {
    rmSync(temporary, { recursive: true, force: true });
  }
});

test('worktree preservation capture creates a snapshot that immediately verifies', () => {
  // Given: a repository with a reviewed classification and a tracked canonical manifest.
  const { directory, manifestPath } = createRepositoryFixture([{ code: ' M', path: 'work.txt' }]);

  try {
    // When: the CLI atomically refreshes the tracked manifest in place.
    const captureResult = spawnSync(
      process.execPath,
      ['scripts/design-system-worktree-preservation.mjs', 'capture', manifestPath, manifestPath],
      {
        cwd: directory,
        encoding: 'utf8',
      },
    );

    // Then: the capture succeeds and the same CLI accepts the resulting snapshot.
    assert.equal(captureResult.status, 0, captureResult.stderr);
    const manifest = JSON.parse(readFileSync(manifestPath, 'utf8'));
    assert.equal(manifest.schemaVersion, 1);
    assert.match(manifest.capturedAt, /^\d{4}-\d{2}-\d{2}T/);

    const checkResult = spawnSync(
      process.execPath,
      ['scripts/design-system-worktree-preservation.mjs', 'check', manifestPath],
      {
        cwd: directory,
        encoding: 'utf8',
      },
    );
    assert.equal(checkResult.status, 0, checkResult.stderr);
    assert.match(checkResult.stdout, /Worktree preservation: PASS/);
    assert.deepEqual(readdirSync(directory).filter((path) => path.includes('.tmp-')), []);
  } finally {
    rmSync(directory, { recursive: true, force: true });
  }
});

test('worktree preservation capture surfaces new paths for explicit review', () => {
  // Given: a current path absent from every reviewed classification category.
  const { directory, manifestPath } = createRepositoryFixture([]);

  try {
    // When: the CLI captures the current worktree.
    const result = spawnSync(
      process.execPath,
      ['scripts/design-system-worktree-preservation.mjs', 'capture', manifestPath, manifestPath],
      { cwd: directory, encoding: 'utf8' },
    );

    // Then: capture succeeds but makes the unreviewed path explicit.
    assert.equal(result.status, 0, result.stderr);
    const manifest = JSON.parse(readFileSync(manifestPath, 'utf8'));
    assert.deepEqual(manifest.classification['unclassified-needs-review'].entries, [
      { code: ' M', path: 'work.txt' },
    ]);
  } finally {
    rmSync(directory, { recursive: true, force: true });
  }
});

test('worktree preservation verifier rejects unreviewed classification entries', () => {
  // Given: capture has surfaced a current path in the unreviewed bucket.
  const { directory, manifestPath } = createRepositoryFixture([]);

  try {
    const captureResult = spawnSync(
      process.execPath,
      ['scripts/design-system-worktree-preservation.mjs', 'capture', manifestPath, manifestPath],
      { cwd: directory, encoding: 'utf8' },
    );
    assert.equal(captureResult.status, 0, captureResult.stderr);

    // When: the verifier checks that structurally current snapshot.
    const result = spawnSync(
      process.execPath,
      ['scripts/design-system-worktree-preservation.mjs', 'check', manifestPath],
      { cwd: directory, encoding: 'utf8' },
    );

    // Then: it fails closed until a human assigns every path to a reviewed category.
    assert.equal(result.status, 1);
    assert.match(result.stderr, /classification has unreviewed paths/);
    assert.doesNotMatch(result.stdout, /Worktree preservation: PASS/);
  } finally {
    rmSync(directory, { recursive: true, force: true });
  }
});

test('worktree preservation capture rejects malformed arguments without success output', () => {
  // Given: extra arguments that do not match either CLI command contract.
  // When: the CLI parses the invocation.
  const result = spawnSync(
    process.execPath,
    ['scripts/design-system-worktree-preservation.mjs', 'capture', MANIFEST],
    { cwd: ROOT, encoding: 'utf8' },
  );

  // Then: it emits usage on stderr and never claims capture success.
  assert.equal(result.status, 2);
  assert.match(result.stderr, /^Usage:/);
  assert.doesNotMatch(result.stdout, /CAPTURED|PASS/);
});

test('worktree preservation capture refuses a different tracked destination', () => {
  // Given: a tracked JSON file that is not the canonical template.
  const { directory, manifestPath } = createRepositoryFixture([{ code: ' M', path: 'work.txt' }]);
  const destination = join(directory, 'unsafe.json');
  const before = readFileSync(destination, 'utf8');

  try {
    // When: capture is asked to overwrite that repository path.
    const result = spawnSync(
      process.execPath,
      ['scripts/design-system-worktree-preservation.mjs', 'capture', manifestPath, destination],
      { cwd: directory, encoding: 'utf8' },
    );

    // Then: it refuses before writing and leaves the prior artifact intact.
    assert.equal(result.status, 2);
    assert.match(result.stderr, /repository destination must be ignored or the template itself/);
    assert.doesNotMatch(result.stdout, /CAPTURED|PASS/);
    assert.equal(readFileSync(destination, 'utf8'), before);
  } finally {
    rmSync(directory, { recursive: true, force: true });
  }
});

test('worktree preservation capture leaves a prior manifest intact for malformed input', () => {
  // Given: malformed input and a pre-existing destination artifact.
  const temporary = mkdtempSync(join(tmpdir(), 'ds-preservation-'));
  const source = join(temporary, 'malformed.json');
  const destination = join(temporary, 'prior.json');
  writeFileSync(source, '{ malformed');
  writeFileSync(destination, 'prior manifest\n');

  try {
    // When: capture attempts to parse the source.
    const result = spawnSync(
      process.execPath,
      ['scripts/design-system-worktree-preservation.mjs', 'capture', source, destination],
      { cwd: ROOT, encoding: 'utf8' },
    );

    // Then: it fails without replacing the destination or leaving a temporary write.
    assert.equal(result.status, 2);
    assert.match(result.stderr, /Worktree preservation: CAPTURE REFUSED/);
    assert.doesNotMatch(result.stdout, /CAPTURED|PASS/);
    assert.equal(readFileSync(destination, 'utf8'), 'prior manifest\n');
    assert.deepEqual(readdirSync(temporary).sort(), ['malformed.json', 'prior.json']);
  } finally {
    rmSync(temporary, { recursive: true, force: true });
  }
});

test('worktree preservation capture represents a staged rename as one structural entry', () => {
  // Given: a staged rename and a legacy classification that covers its fabricated second token.
  const legacyEntries = [
    { code: 'R ', path: 'safe.json' },
    { code: 'un', path: 'afe.json' },
  ];
  const { directory, manifestPath } = createRepositoryFixture(legacyEntries, ({ runGit }) => {
    runGit('mv', 'unsafe.json', 'safe.json');
  });

  try {
    // When: capture and check run through the real porcelain-v1 -z adapter.
    const captureResult = spawnSync(
      process.execPath,
      ['scripts/design-system-worktree-preservation.mjs', 'capture', manifestPath, manifestPath],
      { cwd: directory, encoding: 'utf8' },
    );
    assert.equal(captureResult.status, 0, captureResult.stderr);
    const checkResult = spawnSync(
      process.execPath,
      ['scripts/design-system-worktree-preservation.mjs', 'check', manifestPath],
      { cwd: directory, encoding: 'utf8' },
    );

    // Then: the old false PASS cannot hide an extra fabricated status entry.
    assert.equal(checkResult.status, 0, checkResult.stderr);
    const manifest = JSON.parse(readFileSync(manifestPath, 'utf8'));
    assert.deepEqual(manifest.classification.fixture.entries, [
      { code: 'R ', path: 'safe.json', originalPath: 'unsafe.json' },
    ]);
    assert.equal(manifest.status.entryCount, 1);
    assert.equal(manifest.staged.count, 1);
    assert.equal(manifest.unstaged.trackedCount, 0);
  } finally {
    rmSync(directory, { recursive: true, force: true });
  }
});

test('worktree preservation capture consumes a staged copy original-path token', () => {
  // Given: Git copy detection emits C plus the source file's staged modification.
  const legacyEntries = [
    { code: 'C ', path: 'copy.json' },
    { code: 'un', path: 'afe.json' },
    { code: 'M ', path: 'unsafe.json' },
  ];
  const { directory, manifestPath } = createRepositoryFixture(legacyEntries, ({ directory: root, runGit }) => {
    runGit('config', 'status.renames', 'copies');
    copyFileSync(join(root, 'unsafe.json'), join(root, 'copy.json'));
    writeFileSync(join(root, 'unsafe.json'), 'changed source content\n');
    runGit('add', 'copy.json', 'unsafe.json');
  });

  try {
    // When: capture and check traverse the copy record.
    const captureResult = spawnSync(
      process.execPath,
      ['scripts/design-system-worktree-preservation.mjs', 'capture', manifestPath, manifestPath],
      { cwd: directory, encoding: 'utf8' },
    );
    assert.equal(captureResult.status, 0, captureResult.stderr);
    const checkResult = spawnSync(
      process.execPath,
      ['scripts/design-system-worktree-preservation.mjs', 'check', manifestPath],
      { cwd: directory, encoding: 'utf8' },
    );

    // Then: the copy and source modification are the only two status entries.
    assert.equal(checkResult.status, 0, checkResult.stderr);
    const manifest = JSON.parse(readFileSync(manifestPath, 'utf8'));
    assert.deepEqual(manifest.classification.fixture.entries, [
      { code: 'C ', path: 'copy.json', originalPath: 'unsafe.json' },
      { code: 'M ', path: 'unsafe.json' },
    ]);
    assert.equal(manifest.status.entryCount, 2);
    assert.equal(manifest.staged.count, 2);
    assert.equal(manifest.unstaged.trackedCount, 0);
  } finally {
    rmSync(directory, { recursive: true, force: true });
  }
});
