import assert from 'node:assert/strict';
import test from 'node:test';

import { assertE2EMutationConsent } from './restoration.mjs';
import { isolatedComposeArgs } from './databaseSnapshotSupport.mjs';
import { DatabaseSnapshot } from './dbSnapshot.mjs';
import { ContainerFileSnapshot } from './containerFileSnapshot.mjs';

const consent = {
  E2E_REQUIRE_ISOLATED_DB: '1',
  E2E_ALLOW_DB_MUTATION: 'design-system-ci',
};

test('requires the exact isolated database mutation consent', () => {
  const authorized = {
    ...consent,
    COMPOSE_PROJECT_NAME: 'lps-aia-design-system-ci-run-restoration-test',
    COMPOSE_FILE: 'docker-compose.yml:docker-compose.ci.yml',
  };

  assert.doesNotThrow(() => assertE2EMutationConsent(authorized));
  for (const env of [
    {},
    { E2E_REQUIRE_ISOLATED_DB: '1' },
    { E2E_ALLOW_DB_MUTATION: 'design-system-ci' },
    { E2E_REQUIRE_ISOLATED_DB: 'true', E2E_ALLOW_DB_MUTATION: 'design-system-ci' },
    { E2E_REQUIRE_ISOLATED_DB: '1 ', E2E_ALLOW_DB_MUTATION: 'design-system-ci' },
    { E2E_REQUIRE_ISOLATED_DB: '1', E2E_ALLOW_DB_MUTATION: 'yes' },
    { E2E_REQUIRE_ISOLATED_DB: '1', E2E_ALLOW_DB_MUTATION: 'design-system-ci ' },
  ]) {
    assert.throws(() => assertE2EMutationConsent(env), /isolated E2E database mutation consent/i);
  }
});

test('rejects consent that would resolve Docker Compose to the shared default stack', () => {
  for (const env of [
    consent,
    { ...consent, COMPOSE_PROJECT_NAME: 'last-planner-aia', COMPOSE_FILE: 'docker-compose.yml:docker-compose.ci.yml' },
    { ...consent, COMPOSE_PROJECT_NAME: 'lps-aia-design-system-ci-run-safe' },
    { ...consent, COMPOSE_PROJECT_NAME: 'lps-aia-design-system-ci-run-safe', COMPOSE_FILE: 'docker-compose.yml' },
    { ...consent, COMPOSE_PROJECT_NAME: 'lps-aia-design-system-ci-run-safe', COMPOSE_FILE: 'docker-compose.yml:docker-compose.override.yml' },
  ]) {
    assert.throws(() => assertE2EMutationConsent(env), /isolated E2E Compose context/i);
  }
});

test('pins every Docker invocation to the authorized project and compose files', () => {
  const env = {
    ...consent,
    COMPOSE_PROJECT_NAME: 'lps-aia-design-system-ci-run-pinned',
    COMPOSE_FILE: '/repo/docker-compose.yml:/repo/docker-compose.ci.yml',
  };

  assert.deepEqual(isolatedComposeArgs(['exec', '-T', 'db'], env), [
    'compose', '-p', 'lps-aia-design-system-ci-run-pinned',
    '-f', '/repo/docker-compose.yml', '-f', '/repo/docker-compose.ci.yml',
    'exec', '-T', 'db',
  ]);
});

test('database and container snapshots reject an unpinned default executor before I/O', () => {
  assert.throws(() => new DatabaseSnapshot({ env: {} }), /isolated E2E Compose context/i);
  assert.throws(
    () => new ContainerFileSnapshot('/var/www/html/public/storage', { env: {} }),
    /isolated E2E Compose context/i,
  );
});
