import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';

import { ACTIVATED_VERSION_PATTERN } from '../../scripts/design-system-activation-git.mjs';
import { closeoutGateIds } from '../../scripts/design-system-closeout-contract.mjs';

const read = (path) => readFile(new URL(`../../${path}`, import.meta.url), 'utf8');
const readJson = async (path) => JSON.parse(await read(path));

test('the 1.0.0 release candidate enumerates exactly the stable API', async () => {
  const [version, catalog, release] = await Promise.all([
    readJson('docs/design-system/version.json'),
    readJson('docs/design-system/component-catalog.json'),
    readJson('docs/design-system/stable-api-1.0.0.json'),
  ]);

  assert.equal(release.schemaVersion, 1);
  assert.equal(release.designSystemVersion, version.version);
  assert.equal(release.targetVersion, '1.0.0');
  assert.match(release.releaseStatus, /^(pending-gates|guaranteed)$/);
  assert.equal(release.guaranteeScope, 'stable-only');
  assert.equal(release.activationGate, 'all-closeout-gates-passed');

  const expected = catalog.components
    .filter(({ maturity }) => maturity === 'stable')
    .map(({ id, family, api }) => ({
      id,
      family,
      api,
      evidenceSurfaces: ['laboratory', 'programa-general'],
    }))
    .sort((left, right) => left.id.localeCompare(right.id));
  const actual = [...release.components]
    .sort((left, right) => left.id.localeCompare(right.id));
  assert.deepEqual(actual, expected);
});

test('the stable API artifact has no fields outside its fail-closed schema', async () => {
  const [schema, release] = await Promise.all([
    readJson('docs/design-system/stable-api.schema.json'),
    readJson('docs/design-system/stable-api-1.0.0.json'),
  ]);
  assert.equal(schema.additionalProperties, false);
  assert.equal(schema.$defs.component.additionalProperties, false);
  assert.deepEqual(
    Object.keys(release).sort(),
    Object.keys(schema.properties).sort(),
  );
  for (const field of schema.required) assert.ok(Object.hasOwn(release, field), field);
  for (const component of release.components) {
    assert.deepEqual(
      Object.keys(component).sort(),
      Object.keys(schema.$defs.component.properties).sort(),
      component.id,
    );
    for (const field of schema.$defs.component.required) {
      assert.ok(Object.hasOwn(component, field), `${component.id}.${field}`);
    }
  }
});

test('la activacion es equivalente entre version y API, y NO depende del estado de los gates', async () => {
  const [version, closeout, release] = await Promise.all([
    readJson('docs/design-system/version.json'),
    readJson('docs/design-system/closeout-evidence.json'),
    readJson('docs/design-system/stable-api-1.0.0.json'),
  ]);
  // La lista tiene que estar completa y ser bloqueante — eso es lo que hace
  // verificable el cierre— pero YA NO se exige que los ocho esten `passed`.
  // D-F1b-5 (2026-08-11) retiro ese acoplamiento del contrato: con la version
  // estable en 1.x, exigirlo aqui obligaba a declarar aprobados gates que no lo
  // estan, y fue el incentivo que produjo quince recibos `passed` sin ejecutar.
  // Que cada gate diga la verdad sobre si mismo lo comprueban sus propias
  // pruebas, con su nombre.
  // 2026-08-14: nueve desde que entro `semanal-roles-phases`. La cifra se lee del contrato en
  // vez de repetirse aqui, para que anadir o retirar un gate no deje esta prueba midiendo un
  // numero que ya nadie mantiene.
  const listaCompleta = closeout.gates.length === closeoutGateIds.length
    && closeout.gates.every(({ blocking, evidence }) => blocking === true && evidence.length > 0);
  assert.equal(listaCompleta, true, 'el cierre debe declarar todos los gates, todos bloqueantes');
  const releaseActivated = release.releaseStatus === 'guaranteed';
  // D2 (spec 2026-08-04): la activacion fue un hito unico cumplido en 1.0.0, asi
  // que cualquier SemVer con major >= 1 y status stable cuenta como activada. Se
  // reusa el patron de los gates para que test y gate no puedan divergir.
  const versionActivated = ACTIVATED_VERSION_PATTERN.test(version.version)
    && version.status === 'stable';

  // Lo unico que sigue teniendo que activar junto: version y API estable.
  assert.equal(releaseActivated, versionActivated);
});

test('the changelog names every component in the stable guarantee candidate', async () => {
  const [changelog, release] = await Promise.all([
    read('docs/design-system/CHANGELOG.md'),
    readJson('docs/design-system/stable-api-1.0.0.json'),
  ]);
  const heading = '### API candidata para la garantía 1.0.0';
  const start = changelog.indexOf(heading);
  assert.notEqual(start, -1, heading);
  const nextHeading = changelog.indexOf('\n## ', start + heading.length);
  const section = changelog.slice(start, nextHeading === -1 ? undefined : nextHeading);
  for (const component of release.components) {
    assert.match(section, new RegExp(`\\b${component.id.replaceAll('-', '\\-')}\\b`), component.id);
  }
  assert.match(section, /no\s+activa la garantía SemVer/i);
});

test('DS-GOV-001 is a post-release task owned by Felipe', async () => {
  const governance = await read('docs/design-system/contracts/governance.md');
  assert.match(governance, /DS-GOV-001/);
  assert.match(governance, /owner:\s*Felipe/i);
  assert.match(governance, /primer push autorizado de [`]?1\.0\.0[`]?/i);
  assert.match(governance, /required checks/i);
  assert.match(governance, /no se ejecuta durante Sprint 00/i);
});
