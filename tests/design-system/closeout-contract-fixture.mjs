import { spawnSync } from 'node:child_process';
import {
  cpSync, mkdirSync, mkdtempSync, readFileSync, rmSync, symlinkSync, writeFileSync,
} from 'node:fs';
import { tmpdir } from 'node:os';
import path from 'node:path';

export const repositoryRoot = path.resolve(import.meta.dirname, '../..');

// Derivada del inventario, igual que contracts.test.mjs (ver runFixture ahi):
// el gate ahora valida los 15 manifiestos declarados en
// docs/design-system/manifests/inventory.json, no solo un subconjunto, asi
// que las pruebas de test/archivo referenciadas por cualquiera de ellos deben
// existir tambien en este fixture o el gate falla por "missing test".
function referencedTestFiles() {
  const dsRoot = path.join(repositoryRoot, 'docs/design-system');
  const homologation = JSON.parse(readFileSync(path.join(dsRoot, 'homologation.json'), 'utf8'));
  const inventory = JSON.parse(readFileSync(path.join(dsRoot, 'manifests/inventory.json'), 'utf8'));
  const files = new Set(homologation.tests || []);
  for (const name of inventory.manifests) {
    if (['inventory.json', 'goal-provenance.json'].includes(name)) continue;
    const manifest = JSON.parse(readFileSync(path.join(dsRoot, 'manifests', name), 'utf8'));
    for (const file of manifest.tests || []) files.add(file);
  }
  return [...files];
}

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
  symlinkSync(path.join(repositoryRoot, 'pdc-app'), path.join(fixtureRoot, 'pdc-app'), 'dir');
  for (const file of referencedTestFiles()) {
    const source = path.join(repositoryRoot, file);
    mkdirSync(path.dirname(path.join(fixtureRoot, file)), { recursive: true });
    cpSync(source, path.join(fixtureRoot, file));
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
    { cwd: fixtureRoot, encoding: 'utf8', env: { ...process.env, DS_ACTIVATION_STRICT: '1' } },
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
