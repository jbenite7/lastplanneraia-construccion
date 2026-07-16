import assert from 'node:assert/strict';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import { spawnSync } from 'node:child_process';
import test from 'node:test';

import * as dbSnapshots from './dbSnapshot.mjs';
import {
  E2ERestorationScope,
  ScopedFileSnapshot,
  maybeInjectE2EFailure,
} from './restoration.mjs';

const DatabaseSnapshot = dbSnapshots.DatabaseSnapshot;

test('injects a failure only at the requested checkpoint', () => {
  // Given: one explicit post-mutation checkpoint.
  const env = { E2E_INJECT_FAILURE_AT: 'semi-auto-review:preview:listado-actividades' };

  // When: other checkpoints and the selected checkpoint are evaluated.
  // Then: only the selected checkpoint fails with a stable machine-readable label.
  assert.doesNotThrow(() => maybeInjectE2EFailure('full-app-flow:module:listadoActividades', env));
  assert.throws(
    () => maybeInjectE2EFailure('semi-auto-review:preview:listado-actividades', env),
    /E2E_INJECTED_FAILURE.*semi-auto-review:preview:listado-actividades/,
  );
});

test('restores pre-existing files and removes only artifacts created after capture', () => {
  // Given: a stale evidence tree that predates the current run.
  const cwd = fs.mkdtempSync(path.join(os.tmpdir(), 'lps-aia-file-snapshot-'));
  const scope = path.join(cwd, 'public', 'storage', 'reports');
  const stale = path.join(scope, 'pre-existing.xlsx');
  const nested = path.join(scope, 'nested', 'keep.txt');
  fs.mkdirSync(path.dirname(nested), { recursive: true });
  fs.writeFileSync(stale, 'original evidence');
  fs.writeFileSync(nested, 'keep me');
  const snapshot = new ScopedFileSnapshot(['public/storage/reports'], { cwd }).capture();

  try {
    // When: a failed flow overwrites evidence, removes a file, and creates new artifacts.
    fs.writeFileSync(stale, 'overwritten');
    fs.rmSync(nested);
    fs.writeFileSync(path.join(scope, 'current-run.xlsx'), 'temporary');
    fs.mkdirSync(path.join(scope, 'current-run-dir'));

    const receipt = snapshot.restore();

    // Then: baseline content and fingerprint are exact, while only run-created paths are removed.
    assert.equal(fs.readFileSync(stale, 'utf8'), 'original evidence');
    assert.equal(fs.readFileSync(nested, 'utf8'), 'keep me');
    assert.equal(fs.existsSync(path.join(scope, 'current-run.xlsx')), false);
    assert.equal(fs.existsSync(path.join(scope, 'current-run-dir')), false);
    assert.equal(receipt.beforeFingerprint, receipt.afterFingerprint);
    assert.deepEqual(receipt.removed.sort(), [
      'public/storage/reports/current-run-dir',
      'public/storage/reports/current-run.xlsx',
    ]);
  } finally {
    snapshot.dispose();
    fs.rmSync(cwd, { recursive: true, force: true });
  }
});

test('removes a scoped directory created after a missing-path capture', () => {
  // Given: a scoped report directory that does not exist before the run.
  const cwd = fs.mkdtempSync(path.join(os.tmpdir(), 'lps-aia-missing-scope-'));
  const scope = path.join(cwd, 'public', 'storage');
  const snapshot = new ScopedFileSnapshot(['public/storage'], { cwd }).capture();

  try {
    // When: the application creates the directory and a report.
    fs.mkdirSync(scope, { recursive: true });
    fs.writeFileSync(path.join(scope, 'current-run.xlsx'), 'temporary');
    snapshot.restore();

    // Then: the whole run-created scope is absent again.
    assert.equal(fs.existsSync(scope), false);
  } finally {
    snapshot.dispose();
    fs.rmSync(cwd, { recursive: true, force: true });
  }
});

test('restores container files without deleting pre-existing evidence', async () => {
  // Given: a real filesystem standing in for the app container root.
  const containerSnapshots = await import('./containerFileSnapshot.mjs').catch(() => ({}));
  const ContainerFileSnapshot = containerSnapshots.ContainerFileSnapshot;
  assert.equal(typeof ContainerFileSnapshot, 'function');
  const root = fs.mkdtempSync(path.join(os.tmpdir(), 'lps-aia-container-root-'));
  const containerPath = '/var/www/html/public/storage';
  const storage = path.join(root, containerPath.slice(1));
  const preExisting = path.join(storage, 'pre-existing.xlsx');
  fs.mkdirSync(storage, { recursive: true });
  fs.writeFileSync(preExisting, 'original container evidence');
  const run = (_command, args, options) => {
    const shellScript = args[args.indexOf('-lc') + 1] || '';
    if (shellScript.includes('tar -C / -cf -')) {
      if (!fs.existsSync(storage)) return { status: 44, signal: null, stdout: Buffer.alloc(0), stderr: Buffer.alloc(0) };
      return spawnSync('tar', ['-C', root, '-cf', '-', containerPath.slice(1)], {
        encoding: null,
        maxBuffer: options.maxBuffer,
      });
    }
    if (shellScript.includes('rm -rf')) {
      const target = args.at(-1);
      fs.rmSync(path.join(root, target.slice(1)), { recursive: true, force: true });
      return { status: 0, signal: null, stdout: Buffer.alloc(0), stderr: Buffer.alloc(0) };
    }
    if (args.includes('-xf')) {
      return spawnSync('tar', ['-C', root, '-xf', '-'], {
        input: options.input,
        encoding: null,
        maxBuffer: options.maxBuffer,
      });
    }
    throw new Error(`Unexpected container command: ${args.join(' ')}`);
  };
  const snapshot = new ContainerFileSnapshot(containerPath, { run }).capture();

  try {
    // When: the app overwrites evidence and generates a new report before failing.
    fs.writeFileSync(preExisting, 'overwritten');
    fs.writeFileSync(path.join(storage, 'current-run.xlsx'), 'temporary');
    const receipt = snapshot.restore();

    // Then: the old file is restored and only the newly generated report is removed.
    assert.equal(fs.readFileSync(preExisting, 'utf8'), 'original container evidence');
    assert.equal(fs.existsSync(path.join(storage, 'current-run.xlsx')), false);
    assert.equal(receipt.beforeFingerprint, receipt.afterFingerprint);
    assert.deepEqual(receipt.removed, ['/var/www/html/public/storage/current-run.xlsx']);
  } finally {
    snapshot.dispose();
    fs.rmSync(root, { recursive: true, force: true });
  }
});

test('database snapshot restores every base table without a project filter and resets sequences', () => {
  // Given: two database tables and their pre-run AUTO_INCREMENT values.
  assert.equal(typeof DatabaseSnapshot, 'function');
  const spawned = [];
  const mysql = (sql) => {
    if (sql.startsWith('SHOW FULL TABLES')) {
      return 'actividades\tBASE TABLE\ngeneral_auditoria_acciones\tBASE TABLE\n';
    }
    if (sql.includes('information_schema.tables')) {
      return 'actividades\t9007199254740993\ngeneral_auditoria_acciones\t9\n';
    }
    throw new Error(`Unexpected SQL: ${sql}`);
  };
  const snapshot = new DatabaseSnapshot({
    mysql,
    spawnSync: (_command, args, options) => {
      spawned.push({ args, options });
      if (args.some((arg) => arg.includes('mysqldump'))) {
        return {
          status: 0,
          signal: null,
          stdout: 'INSERT INTO `actividades` VALUES (1);\n',
          stderr: '',
        };
      }
      return { status: 0, signal: null, stdout: '', stderr: '' };
    },
  });

  try {
    // When: a full-database snapshot is captured and restored.
    snapshot.capture();
    snapshot.restore();

    // Then: the dump has no project filter and restoration resets all rows and sequences with a timeout.
    const dump = spawned[0];
    const restore = spawned[1];
    assert.equal(dump.args.some((arg) => arg.startsWith('--where=')), false);
    assert.match(dump.args.join(' '), /--skip-disable-keys/);
    assert.match(dump.args.join(' '), /timeout -s TERM 110 mysqldump/);
    assert.match(restore.args.join(' '), /timeout -s TERM 110 mysql/);
    assert.match(restore.options.input, /DELETE FROM `actividades`;/);
    assert.match(restore.options.input, /DELETE FROM `general_auditoria_acciones`;/);
    assert.match(restore.options.input, /ALTER TABLE `actividades` AUTO_INCREMENT = 9007199254740993;/);
    assert.match(restore.options.input, /ALTER TABLE `general_auditoria_acciones` AUTO_INCREMENT = 9;/);
    assert.equal(dump.options.timeout, 120_000);
    assert.equal(restore.options.timeout, 120_000);
  } finally {
    snapshot.dispose();
  }
});

test('database snapshot rejects misleading restore success text when the command fails', () => {
  // Given: a captured database snapshot whose restore process exits nonzero.
  assert.equal(typeof DatabaseSnapshot, 'function');
  let calls = 0;
  const snapshot = new DatabaseSnapshot({
    mysql: (sql) => (sql.startsWith('SHOW FULL TABLES') ? 'actividades\tBASE TABLE\n' : ''),
    spawnSync: (_command, args) => {
      calls += 1;
      if (args.some((arg) => arg.includes('mysqldump'))) {
        return { status: 0, signal: null, stdout: '', stderr: '' };
      }
      return { status: 1, signal: null, stdout: 'RESTORE SUCCESS', stderr: '' };
    },
    sleep: () => { throw new Error('deterministic failures must not retry'); },
  });

  try {
    snapshot.capture();

    // When: restoration reports success text with a failing exit status.
    // Then: the boundary fails closed on status and does not retry.
    assert.throws(() => snapshot.restore(), /status=1.*RESTORE SUCCESS/);
    assert.equal(calls, 2);
  } finally {
    snapshot.dispose();
  }
});

test('restoration scope verifies database fingerprints and emits cleanup receipts', () => {
  // Given: in-memory database and file restoration resources.
  const database = {
    capture: () => database,
    dispose: () => {},
    fingerprint: () => 'database-fingerprint',
    restore: () => {},
  };
  const files = {
    capture: () => files,
    dispose: () => {},
    restore: () => ({
      beforeFingerprint: 'files-fingerprint',
      afterFingerprint: 'files-fingerprint',
      removed: ['new.xlsx'],
      restored: [],
    }),
  };
  const scope = new E2ERestorationScope(database, files).capture();

  // When: cleanup restores and verifies both resources.
  const receipt = scope.restore();

  // Then: the receipt proves identical database and file fingerprints.
  assert.deepEqual(receipt.database, {
    beforeFingerprint: 'database-fingerprint',
    afterFingerprint: 'database-fingerprint',
  });
  assert.deepEqual(receipt.files.removed, ['new.xlsx']);
  scope.dispose();
});

test('restoration scope still restores files after a database restoration interruption', () => {
  // Given: a database restore that is interrupted and a healthy file resource.
  let fileRestores = 0;
  const database = {
    capture: () => database,
    dispose: () => {},
    fingerprint: () => 'before',
    restore: () => { throw new Error('database interrupted'); },
  };
  const files = {
    capture: () => files,
    dispose: () => {},
    restore: () => {
      fileRestores += 1;
      return {
        beforeFingerprint: 'same',
        afterFingerprint: 'same',
        removed: [],
        restored: [],
      };
    },
  };
  const scope = new E2ERestorationScope(database, files).capture();

  // When: composite restoration runs after an interrupted flow.
  // Then: it reports the database failure only after restoring scoped files.
  assert.throws(() => scope.restore(), /database interrupted/);
  assert.equal(fileRestores, 1);
  scope.dispose();
});
