#!/usr/bin/env node

import { spawnSync } from 'node:child_process';
import { createHash } from 'node:crypto';
import { readFile } from 'node:fs/promises';
import { fileURLToPath, pathToFileURL } from 'node:url';

const normalizeFile = (file) => {
  const normalized = file.replaceAll('\\', '/');
  const scoped = normalized.match(/(?:^|\/)(admin\/src\/.*|src\/.*)$/);
  return scoped?.[1] ?? normalized;
};

const messageHash = (message = '') => createHash('sha256')
  .update(message.trim().replace(/\s+/g, ' '))
  .digest('hex');

export function phpstanInvocation() {
  return [
    'compose', 'exec', '-T', 'app', 'vendor/bin/phpstan',
    'analyse', 'src', 'admin/src', '--memory-limit=1G',
    '--no-progress', '--error-format=json',
  ];
}

export function flagValue(argv, flag) {
  const index = argv.indexOf(flag);
  return index >= 0 ? argv[index + 1] : undefined;
}

export function fingerprintsFromReport(report) {
  return Object.entries(report.files ?? {}).flatMap(([file, details]) =>
    (details.messages ?? []).map(({ identifier, message }) => ({
      file: normalizeFile(file),
      identifier,
      messageHash: messageHash(message),
    }))
  );
}

export function comparePhpstanReport(report, baseline) {
  const expected = new Map();
  for (const item of baseline) {
    const key = JSON.stringify(item);
    expected.set(key, (expected.get(key) ?? 0) + 1);
  }
  const current = fingerprintsFromReport(report);
  const newFingerprints = [];
  for (const item of current) {
    const key = JSON.stringify(item);
    const remaining = expected.get(key) ?? 0;
    if (remaining > 0) expected.set(key, remaining - 1);
    else newFingerprints.push(item);
  }
  return {
    newFingerprints,
    globalErrors: Array.isArray(report.errors) ? report.errors : [],
  };
}

function runPhpstan() {
  const result = spawnSync('docker', phpstanInvocation(), {
    cwd: fileURLToPath(new URL('../', import.meta.url)),
    encoding: 'utf8',
  });
  if (![0, 1].includes(result.status)) {
    throw new Error(result.stderr || `PHPStan execution failed: ${result.status}`);
  }
  if (!result.stdout?.trim()) {
    throw new Error(`PHPStan returned empty output (status ${result.status}): ${result.stderr}`);
  }
  return JSON.parse(result.stdout);
}

async function main() {
  const reportPath = flagValue(process.argv, '--report');
  const baselinePath = flagValue(process.argv, '--baseline')
    ? flagValue(process.argv, '--baseline')
    : fileURLToPath(new URL('../docs/design-system/phpstan-baseline.json', import.meta.url));
  const report = reportPath ? JSON.parse(await readFile(reportPath, 'utf8')) : runPhpstan();
  const baseline = JSON.parse(await readFile(baselinePath, 'utf8'));
  const result = comparePhpstanReport(report, baseline.fingerprints);
  if (result.newFingerprints.length > 0 || result.globalErrors.length > 0) {
    console.error(`New PHPStan findings: ${result.newFingerprints.length}`);
    if (result.globalErrors.length > 0) {
      console.error(`PHPStan global errors: ${result.globalErrors.length}`);
    }
    process.exitCode = 1;
  } else {
    console.log(`PHPStan baseline OK: ${fingerprintsFromReport(report).length} known, 0 new`);
  }
}

if (process.argv[1] && pathToFileURL(process.argv[1]).href === import.meta.url) await main();
