import { spawnSync } from 'node:child_process';
import {
  cpSync, mkdirSync, mkdtempSync, readFileSync, rmSync, symlinkSync, writeFileSync,
} from 'node:fs';
import { tmpdir } from 'node:os';
import path from 'node:path';

export const repositoryRoot = path.resolve(import.meta.dirname, '../..');

const referencedTests = [
  'tests/test_programa_general_sprint_contract.mjs',
  'tests/test_design_system_lab_access.php',
  'tests/browser/design-system-lab.mjs',
  'tests/browser/design-system-lab.a11y.mjs',
  'tests/browser/design-system-lab.visual.mjs',
  'tests/browser/design-system-lab-keyboard.mjs',
  'tests/browser/design-system-lab-desktop-layout.mjs',
  'tests/browser/design-system-lab.performance.mjs',
  'tests/design-system/laboratory-hardening.test.mjs',
  'tests/browser/programa-general-design-system.mjs',
  'tests/browser/programa-general.visual.mjs',
  'tests/browser/design-system-compliance.mjs',
  'tests/design-system/operational-fixtures.test.mjs',
  'tests/browser/operational-fixtures.mjs',
  'e2e/tests/workflows/pg-interactions.spec.mjs',
];

export function createCloseoutFixture() {
  const fixtureRoot = mkdtempSync(path.join(tmpdir(), 'aia-closeout-contract-'));
  cpSync(
    path.join(repositoryRoot, 'docs/design-system'),
    path.join(fixtureRoot, 'docs/design-system'),
    { recursive: true },
  );
  cpSync(
    path.join(repositoryRoot, 'goals/design-system-nucleo-gobernanza'),
    path.join(fixtureRoot, 'goals/design-system-nucleo-gobernanza'),
    { recursive: true },
  );
  symlinkSync(path.join(repositoryRoot, 'public'), path.join(fixtureRoot, 'public'), 'dir');
  symlinkSync(path.join(repositoryRoot, 'views'), path.join(fixtureRoot, 'views'), 'dir');
  for (const file of referencedTests) {
    mkdirSync(path.dirname(path.join(fixtureRoot, file)), { recursive: true });
    cpSync(path.join(repositoryRoot, file), path.join(fixtureRoot, file));
  }
  symlinkSync(
    path.join(repositoryRoot, 'tests/browser/__screenshots__'),
    path.join(fixtureRoot, 'tests/browser/__screenshots__'),
    'dir',
  );
  return fixtureRoot;
}

export function runContracts(fixtureRoot) {
  return spawnSync(
    process.execPath,
    [path.join(repositoryRoot, 'scripts/design-system-contracts.mjs')],
    { cwd: fixtureRoot, encoding: 'utf8' },
  );
}

export function removeFixture(fixtureRoot) {
  rmSync(fixtureRoot, { recursive: true, force: true });
}

export function updateJson(fixtureRoot, relativePath, mutate) {
  const file = path.join(fixtureRoot, relativePath);
  const value = JSON.parse(readFileSync(file, 'utf8'));
  mutate(value);
  writeFileSync(file, `${JSON.stringify(value, null, 2)}\n`);
}
