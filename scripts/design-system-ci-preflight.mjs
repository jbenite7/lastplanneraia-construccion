import { execFileSync } from 'node:child_process';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const EXPECTED_PROJECT = 'lps-aia-design-system-ci';
const EXPECTED_COMPOSE_FILES = ['docker-compose.ci.yml', 'docker-compose.yml'];
const EXPECTED_DATABASE = 'lastplanneraia_ci';
const EXPECTED_VOLUME = 'lps_aia_design_system_ci_db';

function reject(detail) {
  throw new Error(`Unsafe design-system CI target: ${detail}`);
}

function requireEqual(actual, expected, label) {
  if (String(actual ?? '') !== String(expected)) {
    reject(`${label} must be ${expected}; received ${String(actual ?? '<missing>')}`);
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

export function assertSafeCiComposeConfig(config, env = process.env) {
  requireEqual(env.COMPOSE_PROJECT_NAME, EXPECTED_PROJECT, 'COMPOSE_PROJECT_NAME');
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

  requireEqual(app.environment?.APP_ENV, 'testing', 'APP_ENV');
  requireEqual(app.environment?.DB_HOST, 'db', 'DB_HOST');
  requireEqual(app.environment?.DB_PORT, 3306, 'DB_PORT');
  requireEqual(app.environment?.DB_NAME, EXPECTED_DATABASE, 'DB_NAME');
  requireEqual(db.environment?.MYSQL_DATABASE, EXPECTED_DATABASE, 'MYSQL_DATABASE');
  requireEqual(db.environment?.MYSQL_ROOT_PASSWORD, 'ci-only-password', 'MYSQL_ROOT_PASSWORD');
  assertSinglePort(app, 80, 18081, 'app');
  assertSinglePort(db, 3306, 13307, 'db');

  const volume = config?.volumes?.db_data;
  if (!volume || volume.external === true || volume.name !== EXPECTED_VOLUME) {
    reject(`database volume must be the non-external ${EXPECTED_VOLUME} volume`);
  }

  const mounts = db.volumes ?? [];
  if (mounts.length !== 3) reject('database mount allowlist must contain exactly three mounts');

  const dataMount = mounts.find((mount) => mount.target === '/var/lib/mysql');
  if (dataMount?.type !== 'volume' || dataMount.source !== 'db_data') {
    reject('database data mount must use the isolated db_data volume');
  }

  const expectedBinds = new Map([
    ['20260630_global_tables_contract.sql', '/docker-entrypoint-initdb.d/001-global-schema.sql'],
    ['design-system-ci.sql', '/docker-entrypoint-initdb.d/002-design-system-ci.sql'],
  ]);
  const binds = mounts.filter((mount) => mount.type === 'bind');
  if (binds.length !== expectedBinds.size) reject('database bind mount allowlist is incomplete');

  for (const mount of binds) {
    const sourceName = path.basename(String(mount.source ?? ''));
    const expectedTarget = expectedBinds.get(sourceName);
    if (!expectedTarget || mount.target !== expectedTarget) {
      reject(`undeclared database mount: ${String(mount.source ?? '<missing>')}`);
    }
    if (mount.read_only !== true) reject(`${sourceName} must be mounted read-only`);
  }

  return true;
}

function runCli() {
  const rawConfig = execFileSync('docker', ['compose', 'config', '--format', 'json'], {
    cwd: process.cwd(),
    encoding: 'utf8',
    stdio: ['ignore', 'pipe', 'pipe'],
  });
  assertSafeCiComposeConfig(JSON.parse(rawConfig));
  process.stdout.write('Design-system CI preflight: PASS (isolated testing target)\n');
}

const currentFile = fileURLToPath(import.meta.url);
if (process.argv[1] && path.resolve(process.argv[1]) === currentFile) runCli();
