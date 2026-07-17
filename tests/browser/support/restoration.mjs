import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import {
  collectFileEntries,
  fileEntryDepth,
  filePathType,
  fingerprintFileEntries,
} from './fileSnapshotSupport.mjs';
import { assertIsolatedComposeEnvironment } from './databaseSnapshotSupport.mjs';

export function assertE2EMutationConsent(env = process.env) {
  if (
    env.E2E_REQUIRE_ISOLATED_DB !== '1'
    || env.E2E_ALLOW_DB_MUTATION !== 'design-system-ci'
  ) {
    throw new Error(
      'Missing isolated E2E database mutation consent: set '
      + 'E2E_REQUIRE_ISOLATED_DB=1 and E2E_ALLOW_DB_MUTATION=design-system-ci.',
    );
  }
  assertIsolatedComposeEnvironment(env);
}

export function maybeInjectE2EFailure(checkpoint, env = process.env) {
  if (env.E2E_INJECT_FAILURE_AT === checkpoint) {
    throw new Error(`E2E_INJECTED_FAILURE checkpoint=${checkpoint}`);
  }
}

export class ScopedFileSnapshot {
  constructor(scopes, options = {}) {
    this.cwd = path.resolve(options.cwd || process.cwd());
    this.scopes = scopes.map((scope) => {
      if (!scope || path.isAbsolute(scope)) {
        throw new Error(`Scoped E2E artifact path must be relative: ${scope}`);
      }
      const normalized = path.normalize(scope);
      if (normalized === '..' || normalized.startsWith(`..${path.sep}`)) {
        throw new Error(`Scoped E2E artifact path escapes the worktree: ${scope}`);
      }
      return normalized;
    });
    this.backupRoot = fs.mkdtempSync(path.join(os.tmpdir(), 'lps-aia-e2e-files-'));
    this.baselineEntries = [];
    this.beforeFingerprint = null;
    this.captured = false;
  }

  currentEntries() {
    return this.scopes.flatMap((scope) => collectFileEntries(path.join(this.cwd, scope), scope));
  }

  capture() {
    if (this.captured) throw new Error('Scoped E2E artifact snapshot was already captured.');
    this.baselineEntries = this.currentEntries();
    this.beforeFingerprint = fingerprintFileEntries(this.baselineEntries);
    this.scopes.forEach((scope, index) => {
      const source = path.join(this.cwd, scope);
      if (fs.existsSync(source)) {
        fs.cpSync(source, path.join(this.backupRoot, String(index)), {
          recursive: true,
          preserveTimestamps: true,
          verbatimSymlinks: true,
        });
      }
    });
    this.captured = true;
    return this;
  }

  restoreBaselineEntry(entry, scopeIndex, restored) {
    if (entry.type === 'missing') return;
    const scope = this.scopes[scopeIndex];
    const suffix = path.relative(scope, entry.path);
    const destination = path.join(this.cwd, entry.path);
    const backup = path.join(this.backupRoot, String(scopeIndex), suffix);
    const currentType = fs.existsSync(destination) ? filePathType(fs.lstatSync(destination)) : 'missing';

    if (entry.type === 'directory') {
      if (currentType !== 'directory' && currentType !== 'missing') {
        fs.rmSync(destination, { recursive: true, force: true });
      }
      fs.mkdirSync(destination, { recursive: true, mode: entry.mode });
      fs.chmodSync(destination, entry.mode);
      return;
    }

    fs.mkdirSync(path.dirname(destination), { recursive: true });
    const matches = currentType === entry.type
      && (entry.type === 'file'
        ? collectFileEntries(destination, entry.path)[0].digest === entry.digest
        : fs.readlinkSync(destination) === entry.target);
    if (!matches) {
      if (currentType !== 'missing') fs.rmSync(destination, { recursive: true, force: true });
      if (entry.type === 'file') fs.copyFileSync(backup, destination);
      if (entry.type === 'symlink') fs.symlinkSync(fs.readlinkSync(backup), destination);
      restored.push(entry.path);
    }
    if (entry.type === 'file') fs.chmodSync(destination, entry.mode);
  }

  restore() {
    if (!this.captured) throw new Error('Scoped E2E artifact snapshot was not captured.');
    const baselineByPath = new Map(this.baselineEntries.map((entry) => [entry.path, entry]));
    const current = this.currentEntries();
    const removed = current
      .filter((entry) => {
        const baseline = baselineByPath.get(entry.path);
        return entry.type !== 'missing' && (!baseline || baseline.type === 'missing');
      })
      .sort((left, right) => fileEntryDepth(right.path) - fileEntryDepth(left.path))
      .map((entry) => {
        fs.rmSync(path.join(this.cwd, entry.path), { recursive: true, force: true });
        return entry.path;
      });

    const restored = [];
    this.scopes.forEach((scope, scopeIndex) => {
      this.baselineEntries
        .filter((entry) => entry.path === scope || entry.path.startsWith(`${scope}${path.sep}`))
        .sort((left, right) => fileEntryDepth(left.path) - fileEntryDepth(right.path))
        .forEach((entry) => this.restoreBaselineEntry(entry, scopeIndex, restored));
    });

    const afterFingerprint = fingerprintFileEntries(this.currentEntries());
    if (afterFingerprint !== this.beforeFingerprint) {
      throw new Error(
        `Scoped E2E artifact restoration mismatch: before=${this.beforeFingerprint} after=${afterFingerprint}`,
      );
    }
    return { beforeFingerprint: this.beforeFingerprint, afterFingerprint, removed, restored };
  }

  dispose() {
    fs.rmSync(this.backupRoot, { recursive: true, force: true });
  }
}

export class E2ERestorationScope {
  constructor(databaseSnapshot, fileSnapshot) {
    this.databaseSnapshot = databaseSnapshot;
    this.fileSnapshot = fileSnapshot;
    this.beforeDatabaseFingerprint = null;
    this.captured = false;
  }

  capture() {
    this.databaseSnapshot.capture();
    this.beforeDatabaseFingerprint = this.databaseSnapshot.fingerprint();
    this.fileSnapshot.capture();
    this.captured = true;
    return this;
  }

  restore() {
    if (!this.captured) throw new Error('E2E restoration scope was not captured.');
    const errors = [];
    let afterDatabaseFingerprint = null;
    let files = null;
    try {
      this.databaseSnapshot.restore();
      afterDatabaseFingerprint = this.databaseSnapshot.fingerprint();
      if (afterDatabaseFingerprint !== this.beforeDatabaseFingerprint) {
        throw new Error(
          'E2E database restoration mismatch: '
          + `before=${this.beforeDatabaseFingerprint} after=${afterDatabaseFingerprint}`,
        );
      }
    } catch (error) {
      errors.push(error instanceof Error ? error : new Error(String(error)));
    }
    try {
      files = this.fileSnapshot.restore();
    } catch (error) {
      errors.push(error instanceof Error ? error : new Error(String(error)));
    }
    if (errors.length > 0) {
      throw new AggregateError(
        errors,
        `E2E restoration failed: ${errors.map((error) => error.message).join(' | ')}`,
      );
    }
    return {
      database: {
        beforeFingerprint: this.beforeDatabaseFingerprint,
        afterFingerprint: afterDatabaseFingerprint,
      },
      files,
    };
  }

  dispose() {
    const errors = [];
    for (const resource of [this.fileSnapshot, this.databaseSnapshot]) {
      try {
        resource.dispose();
      } catch (error) {
        errors.push(error instanceof Error ? error : new Error(String(error)));
      }
    }
    if (errors.length > 0) {
      throw new AggregateError(
        errors,
        `E2E snapshot disposal failed: ${errors.map((error) => error.message).join(' | ')}`,
      );
    }
  }
}
