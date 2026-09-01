import { spawnSync } from 'node:child_process';
import {
  cpSync, existsSync, mkdirSync, mkdtempSync, readFileSync, rmSync, symlinkSync, writeFileSync,
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
  symlinkSync(path.join(repositoryRoot, 'ct-app'), path.join(fixtureRoot, 'ct-app'), 'dir');
  // Tarea 11 (S01): auth.json es el primer manifiesto con `sources` bajo `frontend/`
  // (login React); sin este symlink el fixture no tiene ese directorio.
  symlinkSync(path.join(repositoryRoot, 'frontend'), path.join(fixtureRoot, 'frontend'), 'dir');
  for (const file of referencedTestFiles()) {
    const source = path.join(repositoryRoot, file);
    const dest = path.join(fixtureRoot, file);
    // Un archivo bajo un directorio ya symlinkeado (pdc-app, ct-app) resuelve al
    // mismo inodo que su fuente -- copiarlo encima de si mismo revienta con
    // ERR_FS_CP_EINVAL. El symlink ya lo deja visible, asi que no hace falta copia.
    if (!existsSync(source) || existsSync(dest)) continue;
    mkdirSync(path.dirname(dest), { recursive: true });
    cpSync(source, dest);
  }
  symlinkSync(
    path.join(repositoryRoot, 'tests/browser/__screenshots__'),
    path.join(fixtureRoot, 'tests/browser/__screenshots__'),
    'dir',
  );
  return fixtureRoot;
}

export function runContracts(fixtureRoot) {
  // `timeout` es una red, no un limite de rendimiento: el gate tarda menos de
  // dos segundos. Existe porque un cuelgue del gate al salir colgaba la suite
  // entera para siempre (ver el comentario del `process.exitCode` en
  // scripts/design-system-contracts.mjs); asi al menos se ve en rojo.
  const timeout = 120_000;
  const result = spawnSync(
    process.execPath,
    [path.join(repositoryRoot, 'scripts/design-system-contracts.mjs')],
    {
      cwd: fixtureRoot,
      encoding: 'utf8',
      timeout,
      env: { ...process.env, DS_ACTIVATION_STRICT: '1' },
    },
  );
  // Cuando el `timeout` salta, spawnSync devuelve `status: null` en vez de un
  // codigo distinto de 0 -- eso pasaba `assert.notEqual(result.status, 0)` en
  // los consumidores y el cuelgue se reportaba como si faltara un texto en
  // stderr. Mismo criterio que `runGate` en contracts.test.mjs: distinguir el
  // timeout explicitamente en vez de dejar que parezca un fallo normal del gate.
  if (result.error?.code === 'ETIMEDOUT') {
    throw new Error(`el gate no termino en ${timeout} ms y hubo que matarlo`);
  }
  return result;
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
