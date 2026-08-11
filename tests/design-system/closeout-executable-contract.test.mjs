import assert from 'node:assert/strict';
import test from 'node:test';

import {
  createCloseoutFixture, removeFixture, runContracts, updateJson,
} from './closeout-contract-fixture.mjs';

function runFixture(mutate) {
  const fixtureRoot = createCloseoutFixture();
  mutate(fixtureRoot);
  const result = runContracts(fixtureRoot);
  removeFixture(fixtureRoot);
  return result;
}

test('executable contract rejects a reordered or duplicate closeout array', () => {
  for (const mutate of [
    (gates) => gates.reverse(),
    (gates) => gates.splice(-1, 1, { ...gates[0] }),
  ]) {
    const result = runFixture((fixtureRoot) => updateJson(
      fixtureRoot,
      'docs/design-system/closeout-evidence.json',
      (closeout) => mutate(closeout.gates),
    ));
    assert.notEqual(result.status, 0);
    assert.match(result.stderr, /closeout: gates must be the exact ordered blocking set/);
  }
});

test('executable contract rejects passed gates without fresh evidence metadata', () => {
  for (const invalidMetadata of [
    { verifiedAt: null, evidence: [] },
    { verifiedAt: '2026-07-14T23:59:59Z', evidence: ['Fresh final evidence.'] },
    { verifiedAt: '2026-07-15T23:00:00Z', evidence: ['Superseded historical result.'] },
  ]) {
    const result = runFixture((fixtureRoot) => updateJson(
      fixtureRoot,
      'docs/design-system/closeout-evidence.json',
      (closeout) => Object.assign(closeout.gates[0], {
        status: 'passed',
        ...invalidMetadata,
      }),
    ));
    assert.notEqual(result.status, 0);
    assert.match(
      result.stderr,
      /static: (?:passed requires fresh verifiedAt and structured evidence|evidence receipt must be an object)/,
    );
  }
});

test('executable contract requires version and stable API to activate together', () => {
  // Antes este test comprobaba que version + API + TODOS los gates activaran a
  // la vez. Ese acoplamiento se retiro el 2026-08-11 (D-F1b-5): obligaba a
  // declarar `passed` los ocho gates para tener la suite verde, y fue el
  // incentivo que produjo quince recibos `passed` sin ejecutar. Lo que queda
  // acoplado —y sigue siendo cierto— es version y API estable entre si.
  const result = runFixture((fixtureRoot) => {
    updateJson(fixtureRoot, 'docs/design-system/version.json', (version) => {
      version.version = '1.0.0';
      version.status = 'stable';
    });
    updateJson(fixtureRoot, 'docs/design-system/stable-api-1.0.0.json', (stableApi) => {
      stableApi.releaseStatus = 'pending-gates';
    });
  });
  assert.notEqual(result.status, 0);
  assert.match(result.stderr, /activation: version and stable API must activate together/);
});

test('un gate en `blocked` sigue poniendo rojo su propio carril', () => {
  // La otra mitad del desacoplamiento, y la que lo sostiene: si al soltar la
  // activacion los gates dejaran de fallar por su cuenta, el contrato habria
  // pasado a mentir de otra forma. Un gate sin resolver que conserve
  // `verifiedAt` es exactamente esa mentira, y tiene que seguir cayendo con su
  // nombre.
  const result = runFixture((fixtureRoot) => {
    updateJson(fixtureRoot, 'docs/design-system/closeout-evidence.json', (closeout) => {
      // Se elige un gate que HOY esta `passed` con su fecha puesta: es donde la
      // mentira es posible. Uno que ya estaba sin resolver no tiene fecha que
      // conservar, y el test pasaria sin comprobar nada.
      const gate = closeout.gates.find(({ verifiedAt }) => verifiedAt !== null);
      gate.status = 'blocked';
      // `verifiedAt` se deja puesto a proposito: un gate sin resolver no puede
      // conservar la fecha de una verificacion que ya no sostiene.
    });
  });
  assert.notEqual(result.status, 0);
  assert.match(result.stderr, /unresolved gate must have null verifiedAt/);
});

test('executable contract rejects a gate with the wrong kind', () => {
  const result = runFixture((fixtureRoot) => updateJson(
    fixtureRoot,
    'docs/design-system/closeout-evidence.json',
    (closeout) => {
      closeout.gates.find(({ id }) => id === 'full-app-flow').kind = 'manual';
    },
  ));
  assert.notEqual(result.status, 0);
  assert.match(result.stderr, /full-app-flow: invalid kind manual/);
});
