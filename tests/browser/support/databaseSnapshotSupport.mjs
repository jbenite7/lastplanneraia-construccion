export const DATABASE_COMMAND_TIMEOUT_MS = 120_000;
export const DATABASE_CONTAINER_TIMEOUT_SECONDS = 110;

const ISOLATED_PROJECT_PREFIX = 'lps-aia-design-system-ci-';
const ISOLATED_COMPOSE_FILES = ['docker-compose.yml', 'docker-compose.ci.yml'];

function composeFiles(env) {
  return (env.COMPOSE_FILE || '').split(':').map((file) => file.trim()).filter(Boolean);
}

export function boundedDatabaseCommand(command) {
  return `timeout -s TERM ${DATABASE_CONTAINER_TIMEOUT_SECONDS} ${command}`;
}

export function databaseDumpScript(allTables, fingerprint = false) {
  const outputFlags = fingerprint
    ? '--skip-comments --compact --skip-extended-insert'
    : '--skip-add-locks --skip-disable-keys';
  const scope = allTables ? '' : '--where="$where" ';
  const command = boundedDatabaseCommand(
    `mysqldump -uroot -p"$MYSQL_ROOT_PASSWORD" --no-create-info --skip-triggers ${outputFlags} `
    + `--single-transaction ${scope}"$MYSQL_DATABASE" "$@"`,
  );
  return allTables ? command : `where="project_id=$1"; shift; ${command}`;
}

export function assertIsolatedComposeEnvironment(env = process.env) {
  const project = env.COMPOSE_PROJECT_NAME || '';
  const composeFileNames = composeFiles(env)
    .map((file) => file.replaceAll('\\', '/').split('/').at(-1));
  const expectedFiles = JSON.stringify(ISOLATED_COMPOSE_FILES);

  if (
    !project.startsWith(ISOLATED_PROJECT_PREFIX)
    || project.length === ISOLATED_PROJECT_PREFIX.length
    || JSON.stringify(composeFileNames) !== expectedFiles
  ) {
    throw new Error(
      'Missing isolated E2E Compose context: COMPOSE_PROJECT_NAME must start with '
      + `${ISOLATED_PROJECT_PREFIX} and COMPOSE_FILE must contain only `
      + ISOLATED_COMPOSE_FILES.join(' and '),
    );
  }
}

export function isolatedComposeArgs(args, env = process.env) {
  assertIsolatedComposeEnvironment(env);
  return [
    'compose', '-p', env.COMPOSE_PROJECT_NAME,
    ...composeFiles(env).flatMap((file) => ['-f', file]),
    ...args,
  ];
}

export function listDatabaseTables(mysqlCommand) {
  return mysqlCommand("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE';")
    .trim()
    .split('\n')
    .filter(Boolean)
    .map((row) => row.split('\t')[0]);
}

export function readAutoIncrementRows(mysqlCommand) {
  return mysqlCommand(
    "SELECT TABLE_NAME, AUTO_INCREMENT FROM information_schema.tables "
    + "WHERE table_schema = DATABASE() AND TABLE_TYPE = 'BASE TABLE' ORDER BY TABLE_NAME;",
  ).trim();
}

export function parseAutoIncrements(rows) {
  return new Map(rows
    .split('\n')
    .filter(Boolean)
    .map((row) => row.split('\t'))
    .filter(([, value]) => value && value !== 'NULL')
    .map(([table, value]) => [table, value]));
}
