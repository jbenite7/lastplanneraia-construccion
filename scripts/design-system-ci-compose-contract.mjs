import { realpathSync } from 'node:fs';
import path from 'node:path';

const PROJECT_PREFIX = 'lps-aia-design-system-ci-';
const EXPECTED_COMPOSE_FILES = ['docker-compose.ci.yml', 'docker-compose.yml'];
const EXPECTED_DATABASE = 'lastplanneraia_ci';
const DB_DOCKERFILE_PATH = 'database/fixtures/design-system-ci.Dockerfile';

// Credenciales efimeras del proyecto Compose desechable de cada corrida. Son los defaults
// `ci-*-only-*` de docker-compose.ci.yml (docs/security/runtime-db-user.md, «CI»): la app
// conecta como la cuenta runtime, DML-only, y `db` conserva la contraseña administrativa solo
// para inicializar y para la lane `admin-db`, que la inyecta en su unico paso.
//
// Hasta el 2026-09-02 este contrato exigia `DB_USER=root` y `ci-only-password`: eran los valores
// del compose ANTERIOR al endurecimiento de 48e06072 (2026-08-29), que separo runtime de
// administracion y nunca actualizo este archivo. El resultado fue que «Verify isolated runtime
// target» rechazaba la configuracion correcta (`DB_USER must be root; received lps_runtime_ci`)
// y el job de runtime aparecia `skipped` detras del estatico -- sin ejecutar un gate visual desde
// esa fecha. Exigir root aqui era ademas lo contrario de lo que el resto del repo ya demuestra:
// tests/design-system/visual-ci-contract.test.mjs prohibe root fuera de la lane admin-db, y el
// config `runtime_db_least_privilege` de docker-compose.yml rehusa inicializar si MYSQL_USER es
// root. El guardarrail sigue siendo exacto -- valores literales, no «cualquier cosa» -- pero
// ahora vigila la intencion vigente y rechaza root de forma explicita.
const EXPECTED_RUNTIME_USER = 'lps_runtime_ci';
const EXPECTED_RUNTIME_PASSWORD = 'ci-runtime-only-password';
const EXPECTED_ADMIN_PASSWORD = 'ci-admin-only-password';

function reject(detail) {
  throw new Error(`Unsafe design-system CI target: ${detail}`);
}

function requireEqual(actual, expected, label) {
  if (String(actual ?? '') !== String(expected)) {
    reject(`${label} must be ${expected}; received ${String(actual ?? '<missing>')}`);
  }
}

function requireDigest(value, label, length = 64) {
  if (!new RegExp(`^[a-f0-9]{${length}}$`).test(String(value ?? ''))) {
    reject(`${label} must be a lowercase ${length}-character hexadecimal digest`);
  }
}

function assertSinglePort(service, target, published, label) {
  const ports = service?.ports ?? [];
  if (ports.length !== 1) reject(`${label} must publish exactly one isolated port`);
  requireEqual(ports[0]?.target, target, `${label} target port`);
  requireEqual(ports[0]?.published, published, `${label} published port`);
}

function composeFileNames(rawComposeFile) {
  return String(rawComposeFile ?? '')
    .split(path.delimiter)
    .filter(Boolean)
    .map((entry) => path.basename(entry))
    .sort();
}

function canonicalBuildPath(value, base, label) {
  try {
    const candidate = path.isAbsolute(String(value ?? ''))
      ? String(value)
      : path.join(base, String(value ?? ''));
    return realpathSync(candidate);
  } catch {
    reject(`${label} must resolve to an existing path inside the current worktree`);
  }
}

function assertServiceAllowlist(services) {
  const unexpected = Object.keys(services)
    .filter((name) => !['adminer', 'app', 'db'].includes(name));
  if (unexpected.length > 0) {
    reject(`service allowlist contains unexpected services: ${unexpected.join(', ')}`);
  }
  const adminer = services.adminer;
  if (adminer && (
    JSON.stringify(adminer.profiles ?? []) !== JSON.stringify(['manual'])
    || (adminer.volumes ?? []).length > 0
  )) {
    reject('adminer must remain manual-only and mount-free');
  }
}

function assertLabels(service, env, runId, prefix = '') {
  requireEqual(service.labels?.['aia.ci.run-id'], runId, `${prefix}aia.ci.run-id`);
  requireEqual(service.labels?.['aia.ci.git-sha'], env.CI_GIT_SHA, `${prefix}aia.ci.git-sha`);
  requireEqual(
    service.labels?.['aia.ci.worktree-fingerprint'],
    env.CI_WORKTREE_FINGERPRINT,
    `${prefix}aia.ci.worktree-fingerprint`,
  );
  requireEqual(
    service.labels?.['aia.ci.fixture-sha256'],
    env.CI_FIXTURE_SHA256,
    `${prefix}aia.ci.fixture-sha256`,
  );
}

export function assertSafeCiComposeConfig(config, env = process.env) {
  const runId = String(env.CI_RUN_ID ?? '');
  if (!/^run-[a-z0-9][a-z0-9-]{5,48}$/.test(runId)) {
    reject('CI_RUN_ID must be a unique run-* slug containing only lowercase letters, digits and hyphens');
  }
  const expectedProject = `${PROJECT_PREFIX}${runId}`;
  requireEqual(env.COMPOSE_PROJECT_NAME, expectedProject, 'COMPOSE_PROJECT_NAME');
  requireDigest(env.CI_GIT_SHA, 'CI_GIT_SHA', 40);
  requireDigest(env.CI_WORKTREE_FINGERPRINT, 'CI_WORKTREE_FINGERPRINT');
  requireDigest(env.CI_FIXTURE_SHA256, 'CI_FIXTURE_SHA256');
  requireEqual(env.APP_URL, 'http://127.0.0.1:18081', 'APP_URL');
  requireEqual(env.E2E_BASE_URL, 'http://127.0.0.1:18081', 'E2E_BASE_URL');
  requireEqual(env.E2E_PROJECT_KEYS, 'construction', 'E2E_PROJECT_KEYS');
  requireEqual(env.E2E_REQUIRE_ISOLATED_DB, 1, 'E2E_REQUIRE_ISOLATED_DB');
  requireEqual(env.E2E_ALLOW_DB_MUTATION, 'design-system-ci', 'E2E_ALLOW_DB_MUTATION');

  const composeFiles = composeFileNames(env.COMPOSE_FILE);
  if (JSON.stringify(composeFiles) !== JSON.stringify(EXPECTED_COMPOSE_FILES)) {
    reject(`COMPOSE_FILE must contain only ${EXPECTED_COMPOSE_FILES.join(' and ')}`);
  }

  const app = config?.services?.app;
  const db = config?.services?.db;
  if (!app || !db) reject('app and db services are required');
  assertServiceAllowlist(config.services);
  const worktreeRoot = realpathSync(process.cwd());

  requireEqual(app.environment?.APP_ENV, 'testing', 'APP_ENV');
  requireEqual(app.environment?.DB_HOST, 'db', 'DB_HOST');
  requireEqual(app.environment?.DB_PORT, 3306, 'DB_PORT');
  requireEqual(app.environment?.DB_NAME, EXPECTED_DATABASE, 'DB_NAME');
  if (String(app.environment?.DB_USER ?? '') === 'root') {
    reject('DB_USER must be the DML-only runtime account, never root: the app of the isolated CI '
      + 'target connects with least privilege and only the admin-db lane may use the admin account');
  }
  requireEqual(app.environment?.DB_USER, EXPECTED_RUNTIME_USER, 'DB_USER');
  requireEqual(app.environment?.DB_PASS, EXPECTED_RUNTIME_PASSWORD, 'DB_PASS');
  requireEqual(app.environment?.USE_GLOBAL_TABLES, 'true', 'USE_GLOBAL_TABLES');
  requireEqual(app.image, `lps-aia-design-system-ci:${runId}`, 'app image');
  assertLabels(app, env, runId);
  if (!app.build?.context || !app.build?.dockerfile) reject('app must build from this worktree');
  const appContext = canonicalBuildPath(app.build.context, worktreeRoot, 'app build context');
  requireEqual(appContext, worktreeRoot, 'app build context');
  const appDockerfile = canonicalBuildPath(app.build.dockerfile, appContext, 'app Dockerfile');
  requireEqual(appDockerfile, path.join(worktreeRoot, 'docker/php/Dockerfile'), 'app Dockerfile');
  if ((app.volumes ?? []).length > 0) reject('app mounts are forbidden in isolated CI');

  requireEqual(db.image, `lps-aia-design-system-ci-db:${runId}`, 'db image');
  assertLabels(db, env, runId, 'db ');
  if (!db.build?.context || !db.build?.dockerfile) {
    reject('db must build an exact init image from this worktree');
  }
  const dbContext = canonicalBuildPath(db.build.context, worktreeRoot, 'db build context');
  requireEqual(dbContext, worktreeRoot, 'db build context');
  const dbDockerfile = canonicalBuildPath(db.build.dockerfile, dbContext, 'db Dockerfile');
  requireEqual(dbDockerfile, path.join(worktreeRoot, DB_DOCKERFILE_PATH), 'db Dockerfile');
  requireEqual(db.environment?.MYSQL_DATABASE, EXPECTED_DATABASE, 'MYSQL_DATABASE');
  // La cuenta que la imagen oficial crea al inicializar debe ser la misma con la que la app
  // conecta: si divergieran, la app entraria con una cuenta que el config de minimo privilegio
  // nunca recorto.
  requireEqual(db.environment?.MYSQL_USER, EXPECTED_RUNTIME_USER, 'MYSQL_USER');
  requireEqual(db.environment?.MYSQL_PASSWORD, EXPECTED_RUNTIME_PASSWORD, 'MYSQL_PASSWORD');
  requireEqual(db.environment?.MYSQL_ROOT_PASSWORD, EXPECTED_ADMIN_PASSWORD, 'MYSQL_ROOT_PASSWORD');
  if (String(db.environment?.MYSQL_PASSWORD ?? '') === String(db.environment?.MYSQL_ROOT_PASSWORD ?? '')) {
    reject('runtime and admin passwords must differ: sharing them collapses the two lanes into one');
  }
  assertSinglePort(app, 80, 18081, 'app');
  assertSinglePort(db, 3306, 13307, 'db');

  const volumeNames = Object.keys(config?.volumes ?? {}).sort();
  if (JSON.stringify(volumeNames) !== JSON.stringify(['db_data'])) {
    reject('volume allowlist must contain only db_data');
  }
  const volume = config.volumes.db_data;
  const expectedVolume = `${expectedProject}_db`;
  if (volume.external === true || volume.name !== expectedVolume) {
    reject(`database volume must be the non-external unique volume ${expectedVolume}`);
  }
  const mounts = db.volumes ?? [];
  if (mounts.length !== 1) reject('database mount allowlist must contain only the data volume');
  const dataMount = mounts.find((mount) => mount.target === '/var/lib/mysql');
  if (dataMount?.type !== 'volume' || dataMount.source !== 'db_data') {
    reject('database data mount must use the isolated db_data volume');
  }
  return true;
}
