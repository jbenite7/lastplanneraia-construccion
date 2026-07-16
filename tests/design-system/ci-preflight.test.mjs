import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';

import {
  assertDbInitDockerfile,
  assertSafeCiComposeConfig,
  assertWorktreeProvenance,
} from '../../scripts/design-system-ci-preflight.mjs';

const SAFE_ENV = {
  CI_RUN_ID: 'run-20260715-a1',
  COMPOSE_PROJECT_NAME: 'lps-aia-design-system-ci-run-20260715-a1',
  COMPOSE_FILE: 'docker-compose.yml:docker-compose.ci.yml',
  CI_GIT_SHA: '3a139499d848b488beb0caa271adf92608437340',
  CI_WORKTREE_FINGERPRINT: 'a'.repeat(64),
  CI_FIXTURE_SHA256: 'b'.repeat(64),
  APP_URL: 'http://127.0.0.1:18081',
  E2E_BASE_URL: 'http://127.0.0.1:18081',
  E2E_PROJECT_KEYS: 'construction',
  E2E_REQUIRE_ISOLATED_DB: '1',
  E2E_ALLOW_DB_MUTATION: 'design-system-ci',
};

const WORKTREE_ROOT = process.cwd();

const INIT_COPIES = [
  ['database/migrations/20260630_global_tables_contract.sql', '001-global-schema.sql'],
  ['database/patches/001_create_new_tables.sql', '002-rbac-schema.sql'],
  ['database/fixtures/design-system-ci.sql', '003-design-system-ci.sql'],
  ['database/migrations/20260702_semi_auto_global_tables.sql', '004-semi-auto-global.sql'],
  ['database/migrations/20260704_semi_auto_assistant_tables.sql', '005-semi-auto-assistant.sql'],
  ['database/migrations/20260703_contratos_slot_quantities_traceability.sql', '006-contract-quantities.sql'],
  ['database/migrations/20260705_actividad_programa_fuentes.sql', '007-activity-sources.sql'],
  ['database/migrations/002_bi_forecast_tables.sql', '008-bi-forecast.sql'],
  ['database/migrations/003_bi_action_queue.sql', '009-bi-action-queue.sql'],
  ['database/patches/20260612_pdc_familias_maestro.sql', '010-family-catalog-base.sql'],
  ['database/patches/20260701_da_porto_feedback_semi_auto.sql', '011-family-catalog-feedback.sql'],
  ['database/migrations/20260706_family_catalog_refactor.sql', '012-family-catalog-refactor.sql'],
  ['database/migrations/20260707_da_porto_jmc_family_patterns.sql', '013-family-patterns.sql'],
  ['database/migrations/20260708_contract_defaults_feedback.sql', '014-contract-defaults.sql'],
  ['database/migrations/20260709_inactivate_alias_contractual_families.sql', '015-contractual-aliases.sql'],
  ['database/migrations/20260710_equipment_families_require_review.sql', '016-equipment-review.sql'],
  ['database/migrations/20260711_apply_human_family_decisions.sql', '017-human-decisions.sql'],
  ['database/fixtures/design-system-ci-normalize.sql', '018-design-system-ci-normalize.sql'],
  ...Array.from({ length: 10 }, (_, index) => [
    `database/bi/${String(index + 1).padStart(3, '0')}_bi_${[
      'pg_semana', 'pi_restricciones', 'ps_compromisos', 'pdc_general',
      'cic_contratistas', 'cip_responsables', 'curva_s_duracion', 'riesgos',
      'control_tower_summary', 'lineage',
    ][index]}.sql`,
    `${String(index + 101).padStart(3, '0')}-bi-view.sql`,
  ]),
];

function safeConfig() {
  return {
    services: {
      app: {
        image: 'lps-aia-design-system-ci:run-20260715-a1',
        build: {
          context: WORKTREE_ROOT,
          dockerfile: `${WORKTREE_ROOT}/docker/php/Dockerfile`,
        },
        labels: {
          'aia.ci.fixture-sha256': 'b'.repeat(64),
          'aia.ci.git-sha': '3a139499d848b488beb0caa271adf92608437340',
          'aia.ci.run-id': 'run-20260715-a1',
          'aia.ci.worktree-fingerprint': 'a'.repeat(64),
        },
        environment: {
          APP_ENV: 'testing',
          DB_HOST: 'db',
          DB_PORT: '3306',
          DB_NAME: 'lastplanneraia_ci',
          DB_USER: 'root',
          DB_PASS: 'ci-only-password',
          USE_GLOBAL_TABLES: 'true',
        },
        ports: [{ target: 80, published: '18081' }],
      },
      db: {
        image: 'lps-aia-design-system-ci-db:run-20260715-a1',
        build: {
          context: WORKTREE_ROOT,
          dockerfile: `${WORKTREE_ROOT}/database/fixtures/design-system-ci.Dockerfile`,
        },
        labels: {
          'aia.ci.fixture-sha256': 'b'.repeat(64),
          'aia.ci.git-sha': '3a139499d848b488beb0caa271adf92608437340',
          'aia.ci.run-id': 'run-20260715-a1',
          'aia.ci.worktree-fingerprint': 'a'.repeat(64),
        },
        environment: {
          MYSQL_DATABASE: 'lastplanneraia_ci',
          MYSQL_ROOT_PASSWORD: 'ci-only-password',
        },
        ports: [{ target: 3306, published: '13307' }],
        volumes: [
          { type: 'volume', source: 'db_data', target: '/var/lib/mysql' },
        ],
      },
    },
    volumes: {
      db_data: { name: 'lps-aia-design-system-ci-run-20260715-a1_db' },
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
      COMPOSE_PROJECT_NAME: 'lps-aia-design-system-ci-reused',
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

test('rejects a reused run identity, volume, image tag or provenance label', () => {
  const reusedVolume = safeConfig();
  reusedVolume.volumes.db_data.name = 'lps_aia_design_system_ci_db';
  assert.throws(() => assertSafeCiComposeConfig(reusedVolume, SAFE_ENV), /volume/);

  const staleImage = safeConfig();
  staleImage.services.app.image = 'lps-aia-design-system-ci:latest';
  assert.throws(() => assertSafeCiComposeConfig(staleImage, SAFE_ENV), /image/);

  const staleLabel = safeConfig();
  staleLabel.services.app.labels['aia.ci.fixture-sha256'] = 'c'.repeat(64);
  assert.throws(() => assertSafeCiComposeConfig(staleLabel, SAFE_ENV), /fixture-sha256/);
});

test('rejects malformed run ids and unproven worktree contents', () => {
  assert.throws(
    () => assertSafeCiComposeConfig(safeConfig(), { ...SAFE_ENV, CI_RUN_ID: '../shared' }),
    /CI_RUN_ID/,
  );
  assert.throws(
    () => assertWorktreeProvenance(
      { gitSha: SAFE_ENV.CI_GIT_SHA, worktreeFingerprint: 'c'.repeat(64), fixtureSha256: SAFE_ENV.CI_FIXTURE_SHA256 },
      SAFE_ENV,
    ),
    /CI_WORKTREE_FINGERPRINT/,
  );
  assert.equal(
    assertWorktreeProvenance(
      {
        gitSha: SAFE_ENV.CI_GIT_SHA,
        worktreeFingerprint: SAFE_ENV.CI_WORKTREE_FINGERPRINT,
        fixtureSha256: SAFE_ENV.CI_FIXTURE_SHA256,
      },
      SAFE_ENV,
    ),
    true,
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

test('rejects dumps, broad SQL copies and undeclared database mounts', () => {
  const dump = safeConfig();
  dump.services.db.volumes.push({
    type: 'bind',
    source: '/workspace/database/backups/production_dump.sql',
    target: '/docker-entrypoint-initdb.d/999-dump.sql',
    read_only: true,
  });
  assert.throws(() => assertSafeCiComposeConfig(dump, SAFE_ENV), /mount/);

  const broadCopy = 'FROM mysql:8.0.40\nCOPY database/ /docker-entrypoint-initdb.d/';
  assert.throws(() => assertDbInitDockerfile(broadCopy), /init image/);
});

test('accepts SQL baked into a uniquely tagged database image when host binds cannot preserve files', () => {
  const dockerfile = [
    'FROM mysql:8.0.40',
    ...INIT_COPIES.map(([source, target]) => (
      `COPY ${source} /docker-entrypoint-initdb.d/${target}`
    )),
  ].join('\n');
  assert.equal(assertSafeCiComposeConfig(safeConfig(), SAFE_ENV), true);
  assert.equal(assertDbInitDockerfile(dockerfile), true);
});

test('rejects foreign build contexts, app mounts and undeclared services or volumes', () => {
  const foreignApp = safeConfig();
  foreignApp.services.app.build = {
    context: '/tmp/stale-tree',
    dockerfile: '/tmp/stale-tree/docker/php/Dockerfile',
  };
  assert.throws(() => assertSafeCiComposeConfig(foreignApp, SAFE_ENV), /app build context/);

  const foreignDb = safeConfig();
  foreignDb.services.db.build = {
    context: '/tmp/stale-tree',
    dockerfile: '/tmp/stale-tree/database/fixtures/design-system-ci.Dockerfile',
  };
  assert.throws(() => assertSafeCiComposeConfig(foreignDb, SAFE_ENV), /db build context/);

  const appBind = safeConfig();
  appBind.services.app.volumes = [{
    type: 'bind',
    source: '/tmp/other-tree',
    target: '/var/www/html',
  }];
  assert.throws(() => assertSafeCiComposeConfig(appBind, SAFE_ENV), /app mounts/);

  const extraService = safeConfig();
  extraService.services.exfil = {
    image: 'alpine:3.20',
    volumes: [{ type: 'bind', source: '/', target: '/host' }],
  };
  assert.throws(() => assertSafeCiComposeConfig(extraService, SAFE_ENV), /service allowlist/);

  const extraVolume = safeConfig();
  extraVolume.volumes.evidence = { name: 'shared-evidence' };
  assert.throws(() => assertSafeCiComposeConfig(extraVolume, SAFE_ENV), /volume allowlist/);
});

test('Docker context excludes ignored credentials and local evidence', () => {
  const dockerignore = readFileSync('.dockerignore', 'utf8');

  for (const pattern of [
    '.omo',
    '.env*',
    'test-output',
    'playwright-report',
    'blob-report',
    'test-results',
    '.debug-journal*.md',
    '.compose.*.override.yml',
  ]) {
    assert.equal(
      dockerignore.split('\n').includes(pattern),
      true,
      `missing Docker context exclusion: ${pattern}`,
    );
  }
});
