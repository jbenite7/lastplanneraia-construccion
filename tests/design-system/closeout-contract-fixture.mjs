import { spawnSync } from 'node:child_process';
import {
  cpSync, mkdirSync, mkdtempSync, readFileSync, rmSync, symlinkSync, writeFileSync,
} from 'node:fs';
import { tmpdir } from 'node:os';
import path from 'node:path';

// Derivada del inventario y compartida con contracts.test.mjs: el gate valida
// los 15 manifiestos declarados en docs/design-system/manifests/inventory.json,
// no solo un subconjunto, asi que las pruebas referenciadas por cualquiera de
// ellos deben existir tambien en este fixture o el gate falla por "missing test".
import { referencedTestFiles, repositoryRoot } from './manifest-sources.mjs';

export { repositoryRoot };

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
    // `timeout` es una red, no un limite de rendimiento: el gate tarda menos de
    // dos segundos. Existe porque un cuelgue del gate al salir colgaba la suite
    // entera para siempre (ver el comentario del `process.exitCode` en
    // scripts/design-system-contracts.mjs); asi al menos se ve en rojo.
    {
      cwd: fixtureRoot,
      encoding: 'utf8',
      timeout: 120_000,
      env: { ...process.env, DS_ACTIVATION_STRICT: '1' },
    },
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
