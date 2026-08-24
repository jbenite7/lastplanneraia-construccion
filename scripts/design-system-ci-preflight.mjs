import { execFileSync } from 'node:child_process';
import { createHash } from 'node:crypto';
import { readFileSync } from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { assertSafeCiComposeConfig } from './design-system-ci-compose-contract.mjs';

const FIXTURE_PATH = 'database/fixtures/design-system-ci.sql';
const DB_DOCKERFILE_PATH = 'database/fixtures/design-system-ci.Dockerfile';
export const EXPECTED_INIT_COPIES = [
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
  ['database/fixtures/design-system-ci-pdc-v2.sql', '019-pdc-v2-schema.sql'],
  ['database/bi/001_bi_pg_semana.sql', '101-bi-view.sql'],
  ['database/bi/002_bi_pi_restricciones.sql', '102-bi-view.sql'],
  ['database/bi/003_bi_ps_compromisos.sql', '103-bi-view.sql'],
  // 104 retirado el 2026-08-04 con el PDC v1 (ver database/fixtures/design-system-ci.Dockerfile).
  ['database/bi/005_bi_cic_contratistas.sql', '105-bi-view.sql'],
  ['database/bi/006_bi_cip_responsables.sql', '106-bi-view.sql'],
  ['database/bi/007_bi_curva_s_duracion.sql', '107-bi-view.sql'],
  ['database/bi/008_bi_riesgos.sql', '108-bi-view.sql'],
  ['database/bi/009_bi_control_tower_summary.sql', '109-bi-view.sql'],
  ['database/bi/010_bi_lineage.sql', '110-bi-view.sql'],
  // B-9 (2026-08-07): la migracion que crea/repara `general_proyectos_procesos`. Se aplica
  // DESPUES del fixture para arreglar su deriva (PK sin AUTO_INCREMENT y 3 columnas de
  // menos), y de paso cada build de CI comprueba que la migracion hace lo que dice.
  ['database/migrations/20260807_proyectos_lineabase_columns.sql', '120-proyectos-lineabase.sql'],
  // 2026-08-24: `general_flags`, que llego con el interruptor del Control Tower y nunca se
  // sembro en CI. La lista blanca hizo su trabajo — el primer intento cambio el Dockerfile sin
  // tocarla y el gate lo rechazo con «must COPY exactly 29 allowlisted SQL files».
  ['database/migrations/20260820_general_flags.sql', '121-general-flags.sql'],
  // 2026-08-24: la siembra de la linea base contractual, que llego con el merge de
  // `linea-base-contractual`. Va DESPUES de la 120 por dependencia — aquella crea las columnas y
  // esta las rellena — y despues de la 121 porque ese slot ya estaba tomado: la rama pedia el 121
  // y se renumero al integrarla, no se eligio entre las dos.
  ['database/migrations/20260819_sembrar_linea_base_contractual.sql', '122-sembrar-linea-base.sql'],
];

function reject(detail) {
  throw new Error(`Unsafe design-system CI target: ${detail}`);
}

function requireEqual(actual, expected, label) {
  if (String(actual ?? '') !== String(expected)) {
    reject(`${label} must be ${expected}; received ${String(actual ?? '<missing>')}`);
  }
}

export { assertSafeCiComposeConfig };

export function assertDbInitDockerfile(source) {
  const initDockerfile = String(source);
  if (!/^FROM mysql:8\.0\.40$/m.test(initDockerfile)) {
    reject('db init image must pin mysql:8.0.40');
  }
  const copyLines = initDockerfile.match(/^COPY /gm) ?? [];
  if (copyLines.length !== EXPECTED_INIT_COPIES.length) {
    reject(`db init image must COPY exactly ${EXPECTED_INIT_COPIES.length} allowlisted SQL files`);
  }
  for (const [copySource, target] of EXPECTED_INIT_COPIES) {
    const instruction = `COPY ${copySource} /docker-entrypoint-initdb.d/${target}`;
    if (!initDockerfile.split('\n').includes(instruction)) {
      reject(`db init image is incomplete: missing ${copySource}`);
    }
  }
  return true;
}

export function assertWorktreeProvenance(actual, env = process.env) {
  requireEqual(actual.gitSha, env.CI_GIT_SHA, 'CI_GIT_SHA');
  requireEqual(
    actual.worktreeFingerprint,
    env.CI_WORKTREE_FINGERPRINT,
    'CI_WORKTREE_FINGERPRINT',
  );
  requireEqual(actual.fixtureSha256, env.CI_FIXTURE_SHA256, 'CI_FIXTURE_SHA256');
  return true;
}

function sha256(value) {
  return createHash('sha256').update(value).digest('hex');
}

export function readWorktreeProvenance(root = process.cwd()) {
  const gitSha = execFileSync('git', ['rev-parse', 'HEAD'], {
    cwd: root,
    encoding: 'utf8',
  }).trim();
  const listedFiles = execFileSync(
    'git',
    ['ls-files', '--cached', '--others', '--exclude-standard', '-z'],
    { cwd: root, encoding: 'buffer' },
  ).toString('utf8').split('\0').filter(Boolean).sort();
  const worktreeHash = createHash('sha256');
  for (const relativePath of listedFiles) {
    worktreeHash.update(`${Buffer.byteLength(relativePath)}:${relativePath}:`);
    worktreeHash.update(readFileSync(path.join(root, relativePath)));
  }
  const fixture = readFileSync(path.join(root, FIXTURE_PATH));
  return {
    gitSha,
    worktreeFingerprint: worktreeHash.digest('hex'),
    fixtureSha256: sha256(fixture),
  };
}

export function assertFixtureContract(source) {
  const requiredTokens = [
    "(73, 'Da Porto'",
    "(75, 'Aeropuerto Regional PC'",
    'CREATE TABLE `zleg_da_porto_programa`',
    'INSERT INTO `zleg_da_porto_programa`',
    'INSERT INTO `programacion_semanal`',
    'INSERT INTO `actividades`',
    'INSERT INTO `pdc`',
    'INSERT INTO `rbac_role_permissions`',
  ];
  for (const token of requiredTokens) {
    if (!source.includes(token)) reject(`fixture is incomplete: missing ${token}`);
  }
  if (/\b(?:NOW|RAND|UUID)\s*\(/i.test(source)) reject('fixture must use deterministic literal values');
  if (/mysqldump|production_dump|@aia\.com\.co/i.test(source)) reject('fixture must not contain real/production data');
  return true;
}

function runCli() {
  const root = process.cwd();
  const provenance = readWorktreeProvenance(root);
  if (process.argv.includes('--print-provenance')) {
    process.stdout.write(`CI_GIT_SHA=${provenance.gitSha}\n`);
    process.stdout.write(`CI_WORKTREE_FINGERPRINT=${provenance.worktreeFingerprint}\n`);
    process.stdout.write(`CI_FIXTURE_SHA256=${provenance.fixtureSha256}\n`);
    return;
  }
  assertWorktreeProvenance(provenance);
  assertFixtureContract(readFileSync(path.join(root, FIXTURE_PATH), 'utf8'));
  assertDbInitDockerfile(readFileSync(path.join(root, DB_DOCKERFILE_PATH), 'utf8'));
  const composeArgs = [
    'compose',
    '-p', process.env.COMPOSE_PROJECT_NAME,
    '-f', path.join(root, 'docker-compose.yml'),
    '-f', path.join(root, 'docker-compose.ci.yml'),
    'config', '--format', 'json',
  ];
  const rawConfig = execFileSync('docker', composeArgs, {
    cwd: root,
    encoding: 'utf8',
    stdio: ['ignore', 'pipe', 'pipe'],
  });
  assertSafeCiComposeConfig(JSON.parse(rawConfig));
  process.stdout.write(
    `Design-system CI preflight: PASS (${process.env.COMPOSE_PROJECT_NAME}, ${process.env.CI_WORKTREE_FINGERPRINT})\n`,
  );
}

const currentFile = fileURLToPath(import.meta.url);
if (process.argv[1] && path.resolve(process.argv[1]) === currentFile) runCli();
