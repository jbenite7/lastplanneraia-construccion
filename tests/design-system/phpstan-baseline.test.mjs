import assert from 'node:assert/strict';
import { spawnSync } from 'node:child_process';
import { existsSync } from 'node:fs';
import { mkdtemp, readFile, writeFile } from 'node:fs/promises';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import { fileURLToPath } from 'node:url';
import test from 'node:test';

import { parseJobSteps } from './workflow-contract-parser.mjs';

const root = new URL('../../', import.meta.url);
const read = async (path) => readFile(new URL(path, root), 'utf8');

test('PHPStan legacy debt is enforced by an executable baseline gate', async () => {
  const pkg = JSON.parse(await read('package.json'));
  const workflow = await read('.github/workflows/design-system.yml');
  const closeout = JSON.parse(await read('docs/design-system/closeout-evidence.json'));
  assert.equal(pkg.scripts['test:design-system:phpstan'], 'node scripts/design-system-phpstan-baseline.mjs');
  assert.match(workflow, /npm run test:design-system:phpstan/);
  assert.ok(existsSync(new URL('scripts/design-system-phpstan-baseline.mjs', root)));
  assert.ok(existsSync(new URL('docs/design-system/phpstan-baseline.json', root)));
  const gate = closeout.gates.find(({ id }) => id === 'phpstan-global');
  assert.equal(gate.blocking, true);
  if (gate.status === 'passed') {
    assert.match(gate.verifiedAt, /^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/);
    assert.ok(gate.evidence.length > 0);
  } else {
    assert.equal(gate.verifiedAt, null);
  }
});

test('CI PHPStan targets the isolated compose project', async () => {
  const workflow = await read('.github/workflows/design-system.yml');
  const steps = parseJobSteps(workflow, 'design-system-runtime');
  const provenanceIndex = steps.findIndex(({ id }) => id === 'runtime-provenance');
  const phpstanIndex = steps.findIndex(({ name }) => name === 'Enforce PHPStan baseline');
  assert.ok(provenanceIndex >= 0 && provenanceIndex < phpstanIndex);
  assert.match(steps[provenanceIndex].run, /COMPOSE_PROJECT_NAME=lps-aia-design-system-ci-\$\{CI_RUN_ID\}/);
  assert.match(steps[provenanceIndex].run, /COMPOSE_FILE=docker-compose\.yml:docker-compose\.ci\.yml/);
  assert.equal(steps[phpstanIndex].run, 'npm run test:design-system:phpstan');
});

test('PHPStan baseline comparison rejects a new fingerprint', async () => {
  let module;
  try {
    module = await import('../../scripts/design-system-phpstan-baseline.mjs');
  } catch (error) {
    assert.fail(`baseline module must be importable: ${error.message}`);
  }
  assert.equal(typeof module.comparePhpstanReport, 'function');
  const baselineReport = { files: { '/var/www/html/src/Legacy.php': { messages: [
    { line: 10, identifier: 'class.notFound', message: 'Legacy class is not found.' },
  ] } } };
  const baseline = module.fingerprintsFromReport(baselineReport);
  const current = { files: { '/var/www/html/src/Legacy.php': { messages: [
    { line: 40, identifier: 'class.notFound', message: 'Legacy class is not found.' },
    { line: 41, identifier: 'method.notFound', message: 'Legacy method is not found.' },
  ] } } };
  const result = module.comparePhpstanReport(current, baseline);
  assert.equal(result.newFingerprints.length, 1);
  assert.equal(result.newFingerprints[0].file, 'src/Legacy.php');
  assert.equal(result.newFingerprints[0].identifier, 'method.notFound');
  assert.equal(result.newFingerprints[0].line, undefined);
  assert.match(result.newFingerprints[0].messageHash, /^[a-f0-9]{64}$/);
  assert.deepEqual(result.globalErrors, []);
});

test('PHPStan semantic fingerprints ignore line drift but preserve duplicate counts', async () => {
  const module = await import('../../scripts/design-system-phpstan-baseline.mjs');
  const message = 'Instantiated class App\\Services\\InvalidArgumentException not found.';
  const baselineReport = { files: { '/var/www/html/src/Services/SemiAutoService.php': { messages: [
    { line: 10, identifier: 'class.notFound', message },
    { line: 20, identifier: 'class.notFound', message },
  ] } } };
  const baseline = module.fingerprintsFromReport(baselineReport);
  const movedReport = { files: { '/var/www/html/src/Services/SemiAutoService.php': { messages: [
    { line: 110, identifier: 'class.notFound', message },
    { line: 120, identifier: 'class.notFound', message },
  ] } } };
  assert.deepEqual(module.comparePhpstanReport(movedReport, baseline).newFingerprints, []);

  const extraReport = { files: { '/var/www/html/src/Services/SemiAutoService.php': { messages: [
    { line: 110, identifier: 'class.notFound', message },
    { line: 120, identifier: 'class.notFound', message },
    { line: 130, identifier: 'class.notFound', message },
  ] } } };
  assert.equal(module.comparePhpstanReport(extraReport, baseline).newFingerprints.length, 1);
});

test('PHPStan fingerprints keep admin/src distinct and reject global errors', async () => {
  const module = await import('../../scripts/design-system-phpstan-baseline.mjs');
  const report = {
    files: {
      '/var/www/html/src/Service.php': { messages: [{ line: 1, identifier: 'x', message: 'src' }] },
      '/var/www/html/admin/src/Service.php': { messages: [{ line: 1, identifier: 'x', message: 'admin' }] },
    },
    errors: ['Internal PHPStan failure'],
  };
  assert.deepEqual(module.fingerprintsFromReport(report).map(({ file }) => file), [
    'src/Service.php',
    'admin/src/Service.php',
  ]);
  assert.deepEqual(module.comparePhpstanReport(report, module.fingerprintsFromReport(report)).globalErrors, [
    'Internal PHPStan failure',
  ]);
});

test('PHPStan baseline CLI exits non-zero when a report adds debt', async () => {
  const dir = await mkdtemp(join(tmpdir(), 'ds-phpstan-'));
  const reportPath = join(dir, 'report.json');
  const baselinePath = join(dir, 'baseline.json');
  const report = { files: { '/var/www/html/src/New.php': { messages: [
    { line: 5, identifier: 'class.notFound', message: 'New class is missing.' },
  ] } } };
  await writeFile(reportPath, JSON.stringify(report));
  await writeFile(baselinePath, JSON.stringify({ fingerprints: [] }));
  const result = spawnSync(process.execPath, [
    fileURLToPath(new URL('../../scripts/design-system-phpstan-baseline.mjs', import.meta.url)),
    '--report', reportPath, '--baseline', baselinePath,
  ], { encoding: 'utf8' });
  assert.equal(result.status, 1, result.stdout + result.stderr);
  assert.match(result.stderr, /New PHPStan findings: 1/);
});

test('PHPStan gate invokes the global analysis without progress noise', async () => {
  const module = await import('../../scripts/design-system-phpstan-baseline.mjs');
  assert.equal(typeof module.phpstanInvocation, 'function');
  assert.deepEqual(module.phpstanInvocation(), [
    'compose', 'exec', '-T', 'app', 'vendor/bin/phpstan',
    'analyse', 'src', 'admin/src', '--memory-limit=1G',
    '--no-progress', '--error-format=json',
  ]);
});

test('optional CLI paths stay undefined when their flags are absent', async () => {
  const module = await import('../../scripts/design-system-phpstan-baseline.mjs');
  assert.equal(typeof module.flagValue, 'function');
  assert.equal(module.flagValue(['node', 'script'], '--report'), undefined);
  assert.equal(module.flagValue(['node', 'script', '--report', 'out.json'], '--report'), 'out.json');
});

test('PHPStan baseline CLI reports the number of tolerated findings', async () => {
  const dir = await mkdtemp(join(tmpdir(), 'ds-phpstan-ok-'));
  const reportPath = join(dir, 'report.json');
  const baselinePath = join(dir, 'baseline.json');
  const finding = { line: 10, identifier: 'class.notFound', message: 'Legacy class is missing.' };
  const report = { files: { '/var/www/html/src/Legacy.php': { messages: [finding] } } };
  const module = await import('../../scripts/design-system-phpstan-baseline.mjs');
  const fingerprints = module.fingerprintsFromReport(report);
  await writeFile(reportPath, JSON.stringify(report));
  await writeFile(baselinePath, JSON.stringify({ fingerprints }));
  const result = spawnSync(process.execPath, [
    fileURLToPath(new URL('../../scripts/design-system-phpstan-baseline.mjs', import.meta.url)),
    '--report', reportPath, '--baseline', baselinePath,
  ], { encoding: 'utf8' });
  assert.equal(result.status, 0, result.stderr);
  assert.match(result.stdout, /PHPStan baseline OK: 1 known, 0 new/);
});
