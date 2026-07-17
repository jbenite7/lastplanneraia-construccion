import { spawnSync } from 'node:child_process';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import {
  collectFileEntries,
  fileEntryDepth,
  fingerprintFileEntries,
} from './fileSnapshotSupport.mjs';
import {
  assertIsolatedComposeEnvironment,
  isolatedComposeArgs,
} from './databaseSnapshotSupport.mjs';

const COMMAND_TIMEOUT_MS = 120_000;
const MAX_ARCHIVE_BYTES = 200 * 1024 * 1024;
const MISSING_PATH_STATUS = 44;

function detail(result) {
  return [
    `status=${result.status ?? 'null'}`,
    `signal=${result.signal || 'none'}`,
    result.error?.message || '',
    result.stderr ? String(result.stderr) : '',
  ].filter(Boolean).join(' | ');
}

function entriesMatch(baseline, current) {
  if (!baseline || !current || baseline.type !== current.type || baseline.mode !== current.mode) {
    return false;
  }
  if (baseline.type === 'file') return baseline.digest === current.digest;
  if (baseline.type === 'symlink') return baseline.target === current.target;
  return true;
}

export class ContainerFileSnapshot {
  constructor(containerPath, options = {}) {
    const normalized = path.posix.normalize(containerPath);
    if (!path.posix.isAbsolute(containerPath) || normalized === '/') {
      throw new Error(`Container E2E artifact path must be an absolute non-root path: ${containerPath}`);
    }
    this.requireComposeIsolation = !options.run;
    this.environment = options.env || process.env;
    if (this.requireComposeIsolation) assertIsolatedComposeEnvironment(this.environment);
    this.composeArgs = (args) => (this.requireComposeIsolation
      ? isolatedComposeArgs(args, this.environment)
      : ['compose', ...args]);
    this.containerPath = normalized;
    this.relativePath = normalized.slice(1);
    this.run = options.run || spawnSync;
    this.backupRoot = fs.mkdtempSync(path.join(os.tmpdir(), 'lps-aia-e2e-container-'));
    this.baselineArchive = null;
    this.baselineEntries = [];
    this.beforeFingerprint = null;
    this.captured = false;
    this.stateSequence = 0;
  }

  captureArchive() {
    if (this.requireComposeIsolation) assertIsolatedComposeEnvironment(this.environment);
    const result = this.run('docker', [
      ...this.composeArgs(['exec', '-T', 'app', 'sh', '-lc',
      'root=$1; rel=${root#/}; if [ ! -e "$root" ]; then exit 44; fi; tar -C / -cf - "$rel"',
      'container-file-snapshot', this.containerPath]),
    ], {
      cwd: process.cwd(),
      encoding: null,
      maxBuffer: MAX_ARCHIVE_BYTES,
      timeout: COMMAND_TIMEOUT_MS,
    });
    if (result.status === MISSING_PATH_STATUS) return null;
    if (result.status !== 0) throw new Error(`Container artifact capture failed: ${detail(result)}`);
    return Buffer.from(result.stdout || Buffer.alloc(0));
  }

  unpackArchive(archive, label) {
    const destination = path.join(this.backupRoot, label);
    fs.mkdirSync(destination, { recursive: true });
    const result = spawnSync('tar', ['-C', destination, '-xf', '-'], {
      input: archive,
      encoding: null,
      maxBuffer: MAX_ARCHIVE_BYTES,
      timeout: COMMAND_TIMEOUT_MS,
    });
    if (result.status !== 0) throw new Error(`Container artifact archive extraction failed: ${detail(result)}`);
    return path.join(destination, this.relativePath);
  }

  readState(label) {
    const archive = this.captureArchive();
    if (archive === null) {
      return {
        archive: null,
        entries: [{ path: this.containerPath, type: 'missing' }],
      };
    }
    const extracted = this.unpackArchive(archive, label);
    return { archive, entries: collectFileEntries(extracted, this.containerPath) };
  }

  capture() {
    if (this.captured) throw new Error('Container E2E artifact snapshot was already captured.');
    const baseline = this.readState('baseline');
    this.baselineArchive = baseline.archive;
    this.baselineEntries = baseline.entries;
    this.beforeFingerprint = fingerprintFileEntries(this.baselineEntries);
    this.captured = true;
    return this;
  }

  removeContainerPath(containerPath) {
    if (this.requireComposeIsolation) assertIsolatedComposeEnvironment(this.environment);
    const result = this.run('docker', [
      ...this.composeArgs(['exec', '-T', 'app', 'sh', '-lc',
        'rm -rf -- "$1"', 'container-file-snapshot', containerPath]),
    ], {
      cwd: process.cwd(),
      encoding: null,
      maxBuffer: MAX_ARCHIVE_BYTES,
      timeout: COMMAND_TIMEOUT_MS,
    });
    if (result.status !== 0) throw new Error(`Container artifact removal failed: ${detail(result)}`);
  }

  restoreArchive() {
    if (this.requireComposeIsolation) assertIsolatedComposeEnvironment(this.environment);
    if (this.baselineArchive === null) return;
    const result = this.run('docker', [
      ...this.composeArgs(['exec', '-T', 'app', 'tar', '-C', '/', '-xf', '-']),
    ], {
      cwd: process.cwd(),
      input: this.baselineArchive,
      encoding: null,
      maxBuffer: MAX_ARCHIVE_BYTES,
      timeout: COMMAND_TIMEOUT_MS,
    });
    if (result.status !== 0) throw new Error(`Container artifact restoration failed: ${detail(result)}`);
  }

  restore() {
    if (!this.captured) throw new Error('Container E2E artifact snapshot was not captured.');
    this.stateSequence += 1;
    const current = this.readState(`current-${this.stateSequence}`);
    const baselineByPath = new Map(this.baselineEntries.map((entry) => [entry.path, entry]));
    const currentByPath = new Map(current.entries.map((entry) => [entry.path, entry]));
    const removed = current.entries
      .filter((entry) => {
        const baseline = baselineByPath.get(entry.path);
        return entry.type !== 'missing'
          && (!baseline || baseline.type === 'missing' || baseline.type !== entry.type);
      })
      .sort((left, right) => fileEntryDepth(right.path) - fileEntryDepth(left.path))
      .map((entry) => {
        this.removeContainerPath(entry.path);
        return entry.path;
      });
    const restored = this.baselineEntries
      .filter((entry) => entry.type !== 'missing' && !entriesMatch(entry, currentByPath.get(entry.path)))
      .map((entry) => entry.path);

    this.restoreArchive();
    this.stateSequence += 1;
    const after = this.readState(`after-${this.stateSequence}`);
    const afterFingerprint = fingerprintFileEntries(after.entries);
    if (afterFingerprint !== this.beforeFingerprint) {
      throw new Error(
        `Container E2E artifact restoration mismatch: before=${this.beforeFingerprint} after=${afterFingerprint}`,
      );
    }
    return { beforeFingerprint: this.beforeFingerprint, afterFingerprint, removed, restored };
  }

  dispose() {
    fs.rmSync(this.backupRoot, { recursive: true, force: true });
  }
}
