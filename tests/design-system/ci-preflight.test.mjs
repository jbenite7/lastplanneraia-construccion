import assert from 'node:assert/strict';
import test from 'node:test';

import { assertSafeCiComposeConfig } from '../../scripts/design-system-ci-preflight.mjs';

const SAFE_ENV = {
  COMPOSE_PROJECT_NAME: 'lps-aia-design-system-ci',
  COMPOSE_FILE: 'docker-compose.yml:docker-compose.ci.yml',
  APP_URL: 'http://127.0.0.1:18081',
  E2E_BASE_URL: 'http://127.0.0.1:18081',
  E2E_PROJECT_KEYS: 'construction',
  E2E_REQUIRE_ISOLATED_DB: '1',
  E2E_ALLOW_DB_MUTATION: 'design-system-ci',
};

function safeConfig() {
  return {
    services: {
      app: {
        environment: {
          APP_ENV: 'testing',
          DB_HOST: 'db',
          DB_PORT: '3306',
          DB_NAME: 'lastplanneraia_ci',
        },
        ports: [{ target: 80, published: '18081' }],
      },
      db: {
        environment: {
          MYSQL_DATABASE: 'lastplanneraia_ci',
          MYSQL_ROOT_PASSWORD: 'ci-only-password',
        },
        ports: [{ target: 3306, published: '13307' }],
        volumes: [
          { type: 'volume', source: 'db_data', target: '/var/lib/mysql' },
          {
            type: 'bind',
            source: '/workspace/database/migrations/20260630_global_tables_contract.sql',
            target: '/docker-entrypoint-initdb.d/001-global-schema.sql',
            read_only: true,
          },
          {
            type: 'bind',
            source: '/workspace/database/fixtures/design-system-ci.sql',
            target: '/docker-entrypoint-initdb.d/002-design-system-ci.sql',
            read_only: true,
          },
        ],
      },
    },
    volumes: {
      db_data: { name: 'lps_aia_design_system_ci_db' },
    },
  };
}

test('accepts only the deterministic isolated CI target', () => {
  assert.equal(assertSafeCiComposeConfig(safeConfig(), SAFE_ENV), true);
});

test('rejects a local or production database and volume', () => {
  const localDb = safeConfig();
  localDb.services.app.environment.DB_NAME = 'lastplanneraia';
  assert.throws(() => assertSafeCiComposeConfig(localDb, SAFE_ENV), /DB_NAME/);

  const externalVolume = safeConfig();
  externalVolume.volumes.db_data = { name: 'htdocs_db_data', external: true };
  assert.throws(() => assertSafeCiComposeConfig(externalVolume, SAFE_ENV), /volume/);
});

test('rejects a wrong compose project or unexpected compose file', () => {
  assert.throws(
    () => assertSafeCiComposeConfig(safeConfig(), {
      ...SAFE_ENV,
      COMPOSE_PROJECT_NAME: 'lps-aia',
    }),
    /COMPOSE_PROJECT_NAME/,
  );
  assert.throws(
    () => assertSafeCiComposeConfig(safeConfig(), {
      ...SAFE_ENV,
      COMPOSE_FILE: 'docker-compose.yml:docker-compose.override.yml',
    }),
    /COMPOSE_FILE/,
  );
});

test('rejects a missing mutation consent or pilot project selector', () => {
  assert.throws(
    () => assertSafeCiComposeConfig(safeConfig(), {
      ...SAFE_ENV,
      E2E_ALLOW_DB_MUTATION: '',
    }),
    /E2E_ALLOW_DB_MUTATION/,
  );
  assert.throws(
    () => assertSafeCiComposeConfig(safeConfig(), {
      ...SAFE_ENV,
      E2E_PROJECT_KEYS: 'unknown',
    }),
    /E2E_PROJECT_KEYS/,
  );
});

test('rejects dumps, writable fixtures and undeclared database mounts', () => {
  const dump = safeConfig();
  dump.services.db.volumes.push({
    type: 'bind',
    source: '/workspace/database/backups/production_dump.sql',
    target: '/docker-entrypoint-initdb.d/999-dump.sql',
    read_only: true,
  });
  assert.throws(() => assertSafeCiComposeConfig(dump, SAFE_ENV), /mount/);

  const writableFixture = safeConfig();
  writableFixture.services.db.volumes[2].read_only = false;
  assert.throws(() => assertSafeCiComposeConfig(writableFixture, SAFE_ENV), /read-only/);
});
