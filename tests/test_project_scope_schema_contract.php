<?php
// @requiere: db

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';
require_once __DIR__ . '/../scripts/lib/php-test-ddl-inventory.php';
require_once __DIR__ . '/../scripts/security/audit-runtime-db-grants.php';

use App\Security\DataScope\TableScopeKind;

$mode = $argv[1] ?? '--audit';
if (!in_array($mode, ['--audit', '--enforce'], true)) {
    fwrite(STDERR, "Uso: php tests/test_project_scope_schema_contract.php [--audit|--enforce]\n");
    exit(2);
}

$checks = 0;
$failures = [];

/** @param mixed $actual */
function schemaContractSame(mixed $expected, mixed $actual, string $message): void
{
    global $checks, $failures;
    $checks++;
    if ($actual !== $expected) {
        $failures[] = $message;
    }
}

function schemaContractTrue(bool $condition, string $message): void
{
    schemaContractSame(true, $condition, $message);
}

/** @return array{code: int, output: string} */
function schemaContractRunGrantAudit(
    string $script,
    string $grant,
    bool $stdin = false,
    array $arguments = [],
    array $environment = ['DB_NAME' => 'lps'],
): array
{
    $fixture = null;
    if (!$stdin) {
        $fixture = tempnam(sys_get_temp_dir(), 'lps-grants-');
        if ($fixture === false || file_put_contents($fixture, $grant . "\n") === false) {
            throw new RuntimeException('No se pudo crear el fixture temporal de grants.');
        }
    }

    $command = [PHP_BINARY, $script];
    foreach ($arguments as $argument) {
        $command[] = $argument;
    }
    if ($fixture !== null) {
        $command[] = '--grants-file=' . $fixture;
    }
    $pipes = [];
    $process = proc_open(
        $command,
        [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
        $pipes,
        null,
        $environment,
    );

    if (!is_resource($process)) {
        if ($fixture !== null) {
            @unlink($fixture);
        }
        throw new RuntimeException('No se pudo iniciar el auditor de grants.');
    }

    fwrite($pipes[0], $stdin ? $grant . "\n" : '');
    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $code = proc_close($process);
    if ($fixture !== null) {
        @unlink($fixture);
    }

    return ['code' => $code, 'output' => (string) $stdout . (string) $stderr];
}

final class SchemaContractGrantStatement extends PDOStatement
{
    /** @var list<array<int, string>> */
    private array $rows;

    /** @param list<array<int, string>> $rows */
    public function __construct(array $rows)
    {
        $this->rows = $rows;
    }

    public function fetch(
        int $mode = PDO::FETCH_DEFAULT,
        int $cursorOrientation = PDO::FETCH_ORI_NEXT,
        int $cursorOffset = 0,
    ): mixed {
        unset($cursorOrientation, $cursorOffset);
        $row = array_shift($this->rows);
        if ($row === null) {
            return false;
        }

        return match ($mode) {
            PDO::FETCH_COLUMN => $row[0],
            PDO::FETCH_NUM, PDO::FETCH_DEFAULT => $row,
            default => $row,
        };
    }
}

final class SchemaContractGrantPdo extends PDO
{
    public string $queriedSql = '';

    /** @param list<array<int, string>> $rows */
    public function __construct(private array $rows, private ?Throwable $failure = null)
    {
    }

    public function query(
        string $query,
        ?int $fetchMode = null,
        mixed ...$fetchModeArgs,
    ): PDOStatement|false {
        unset($fetchMode, $fetchModeArgs);
        $this->queriedSql = $query;
        if ($this->failure !== null) {
            throw $this->failure;
        }

        return new SchemaContractGrantStatement($this->rows);
    }
}

$grantAudit = __DIR__ . '/../scripts/security/audit-runtime-db-grants.php';
if (!is_file($grantAudit)) {
    $failures[] = 'Falta scripts/security/audit-runtime-db-grants.php.';
} else {
    schemaContractSame(
        ['grants_file' => null, 'live' => true],
        runtimeGrantAuditParseArguments(['--live']),
        'El auditor no reconoce --live.',
    );
    schemaContractSame(
        ['grants_file' => 'fixture.txt', 'live' => false],
        runtimeGrantAuditParseArguments(['--grants-file=fixture.txt']),
        'El parser dejó de reconocer --grants-file.',
    );
    try {
        runtimeGrantAuditParseArguments(['--live', '--grants-file=fixture.txt']);
        $failures[] = 'El auditor aceptó --live junto con --grants-file.';
    } catch (InvalidArgumentException) {
        $checks++;
    }

    $previousDatabase = getenv('DB_NAME');
    putenv('DB_NAME=lps');
    $liveGrantCases = [
        'live DML exacto más USAGE' => [
            [
                ['GRANT USAGE ON *.* TO `lps_runtime`@`%`'],
                ['GRANT SELECT, INSERT, UPDATE, DELETE ON `lps`.* TO `lps_runtime`@`%`'],
            ],
            true,
        ],
        'live root' => [
            [['GRANT SELECT ON `lps`.* TO `root`@`%`']],
            false,
        ],
        'live DDL' => [
            [['GRANT CREATE ON `lps`.* TO `lps_runtime`@`%`']],
            false,
        ],
        'live target global' => [
            [['GRANT SELECT ON *.* TO `lps_runtime`@`%`']],
            false,
        ],
    ];
    foreach ($liveGrantCases as $label => [$rows, $expectedOk]) {
        $pdo = new SchemaContractGrantPdo($rows);
        ob_start();
        $result = runtimeGrantAuditLive($pdo);
        $liveOutput = (string) ob_get_clean();
        schemaContractSame($expectedOk, $result['ok'], "PDO doble: {$label} devolvió estado inesperado.");
        schemaContractSame(
            'SHOW GRANTS FOR CURRENT_USER',
            $pdo->queriedSql,
            "PDO doble: {$label} usó una consulta distinta.",
        );
        schemaContractTrue(
            stripos($liveOutput, 'GRANT ') === false,
            "PDO doble: {$label} imprimió el contenido de grants.",
        );
    }
    if ($previousDatabase === false) {
        putenv('DB_NAME');
    } else {
        putenv('DB_NAME=' . $previousDatabase);
    }

    $liveUnavailable = schemaContractRunGrantAudit(
        $grantAudit,
        '',
        false,
        ['--live'],
        [
            'DB_HOST' => '127.0.0.1',
            'DB_PORT' => '1',
            'DB_NAME' => 'lps',
            'DB_USER' => 'runtime',
            'DB_PASS' => '',
        ],
    );
    schemaContractSame(1, $liveUnavailable['code'], 'Auditor live sin conexión devolvió RC 0.');
    schemaContractTrue(
        str_contains($liveUnavailable['output'], 'runtime_db_grants=fail reason=unavailable grants_checked=0'),
        'Auditor live sin conexión no reportó reason=unavailable.',
    );
    schemaContractTrue(
        stripos($liveUnavailable['output'], 'GRANT ') === false,
        'Auditor live sin conexión imprimió contenido de grants.',
    );

    $grantCases = [
        'DML explícito sobre lps.*' => [
            'GRANT SELECT, INSERT, UPDATE, DELETE ON `lps`.* TO `lps_runtime`@`%`',
            0,
        ],
        'SHOW GRANTS con USAGE sin capacidad' => [
            "GRANT USAGE ON *.* TO `lps_runtime`@`%`\n"
                . 'GRANT SELECT, INSERT, UPDATE, DELETE ON `lps`.* TO `lps_runtime`@`%`',
            0,
        ],
        'DML exacto acepta case, orden y quoting alternos' => [
            "grant delete, update, insert, select on lps.* to 'lps_runtime'@'%'",
            0,
        ],
        'USAGE sin DML exacto' => [
            'GRANT USAGE ON *.* TO `lps_runtime`@`%`',
            1,
        ],
        'DML incompleto' => [
            'GRANT SELECT, INSERT, UPDATE ON `lps`.* TO `lps_runtime`@`%`',
            1,
        ],
        'sufijo no permitido' => [
            'GRANT SELECT, INSERT, UPDATE, DELETE ON `lps`.* TO `lps_runtime`@`%`;',
            1,
        ],
        'segundo GRANT DML' => [
            "GRANT SELECT, INSERT, UPDATE, DELETE ON `lps`.* TO `lps_runtime`@`%`\n"
                . 'GRANT SELECT, INSERT, UPDATE, DELETE ON `lps`.* TO `lps_runtime`@`%`',
            1,
        ],
        'USAGE y DML para cuentas distintas' => [
            "GRANT USAGE ON *.* TO `otra_runtime`@`%`\n"
                . 'GRANT SELECT, INSERT, UPDATE, DELETE ON `lps`.* TO `lps_runtime`@`%`',
            1,
        ],
        'usuario root' => [
            'GRANT SELECT ON `lps`.* TO `root`@`%`',
            1,
        ],
        'ALL PRIVILEGES' => [
            'GRANT ALL PRIVILEGES ON `lps`.* TO `lps_runtime`@`%`',
            1,
        ],
        'GRANT OPTION' => [
            'GRANT SELECT ON `lps`.* TO `lps_runtime`@`%` WITH GRANT OPTION',
            1,
        ],
        'CREATE DDL' => [
            'GRANT CREATE ON `lps`.* TO `lps_runtime`@`%`',
            1,
        ],
        'ALTER DDL' => [
            'GRANT ALTER ON `lps`.* TO `lps_runtime`@`%`',
            1,
        ],
        'DROP DDL' => [
            'GRANT DROP ON `lps`.* TO `lps_runtime`@`%`',
            1,
        ],
        'FILE administrativo' => [
            'GRANT FILE ON `lps`.* TO `lps_runtime`@`%`',
            1,
        ],
        'SUPER administrativo' => [
            'GRANT SUPER ON `lps`.* TO `lps_runtime`@`%`',
            1,
        ],
        'PROCESS administrativo' => [
            'GRANT PROCESS ON `lps`.* TO `lps_runtime`@`%`',
            1,
        ],
        'CREATE USER administrativo' => [
            'GRANT CREATE USER ON `lps`.* TO `lps_runtime`@`%`',
            1,
        ],
        'scope server-global' => [
            'GRANT SELECT ON *.* TO `lps_runtime`@`%`',
            1,
        ],
        'otra base' => [
            'GRANT SELECT ON `otra_base`.* TO `lps_runtime`@`%`',
            1,
        ],
    ];

    foreach ($grantCases as $label => [$grant, $expectedCode]) {
        $result = schemaContractRunGrantAudit($grantAudit, $grant);
        schemaContractSame($expectedCode, $result['code'], "Auditor de grants: {$label} devolvió RC inesperado.");
        schemaContractTrue(
            stripos($result['output'], 'GRANT ') === false,
            "Auditor de grants: {$label} imprimió el grant recibido.",
        );
    }

    $stdinResult = schemaContractRunGrantAudit(
        $grantAudit,
        "grant select, insert, update, delete on `lps`.* to 'lps_runtime'@'%'",
        true,
    );
    schemaContractSame(0, $stdinResult['code'], 'Auditor de grants: stdin válido devolvió RC inesperado.');
    schemaContractTrue(
        stripos($stdinResult['output'], 'GRANT ') === false,
        'Auditor de grants: stdin válido imprimió el grant recibido.',
    );
}

$scannerFixture = <<<'PHP'
<?php
$expected = 'ALTER TABLE expected_only ADD COLUMN x INT';
$pdo->exec('CREATE TABLE fixture_omitida (id INT)');
PHP;
schemaContractSame(
    [['call' => 'exec', 'line' => 3]],
    phpTestExecutableDdlCalls($scannerFixture),
    'El inventario no detectó DDL ejecutable o confundió SQL esperado con ejecución.',
);
schemaContractSame(
    [],
    phpTestExecutableDdlCalls("<?php \$expected = 'ALTER TABLE only_expected ADD x INT';"),
    'El inventario marcó como ejecutable un SQL esperado que nunca se invoca.',
);

$testInventory = array_merge(
    glob(__DIR__ . '/test_*.php') ?: [],
    glob(__DIR__ . '/unit/*Test.php') ?: [],
);
sort($testInventory);
$testLevels = [];
foreach ($testInventory as $testPath) {
    $source = @file_get_contents($testPath);
    schemaContractTrue($source !== false, basename($testPath) . ' no se pudo inventariar.');
    if ($source === false) {
        continue;
    }
    $declaredLevel = phpTestDeclaredLevel($source);
    if ($declaredLevel !== null) {
        $testLevels[$testPath] = $declaredLevel;
    }
}
$ddlViolations = phpTestDdlLevelViolations($testLevels);
schemaContractSame(
    [],
    $ddlViolations,
    'El inventario encontró DDL ejecutable fuera de admin-db: ' . json_encode($ddlViolations),
);

$migration = __DIR__ . '/../database/migrations/20260828_project_scope_contract.php';
if (!is_file($migration)) {
    $failures[] = 'Falta database/migrations/20260828_project_scope_contract.php.';
} else {
    require_once $migration;

    schemaContractSame(false, projectScopeParseApply([]), 'La migración no es dry-run por defecto.');
    schemaContractSame(true, projectScopeParseApply(['--apply']), 'La migración no reconoce --apply.');
    try {
        projectScopeParseApply(['--force']);
        $failures[] = 'La migración aceptó una opción no declarada.';
    } catch (InvalidArgumentException) {
        $checks++;
    }
    schemaContractSame('`programa`', projectScopeQuoteIdentifier('programa'), 'El quoting allowlisted cambió.');

    try {
        projectScopeQuoteIdentifier('programa;DROP TABLE programa');
        $failures[] = 'La migración aceptó un identificador fuera de allowlist.';
    } catch (InvalidArgumentException) {
        $checks++;
    }

    $plan = projectScopeBuildPlan(
        [
            'cic' => [
                'TABLE_TYPE' => 'BASE TABLE',
                'COLUMN_TYPE' => 'int',
                'project_id_nullable' => 0,
                'has_leading_index' => 1,
            ],
            'programa' => [
                'TABLE_TYPE' => 'BASE TABLE',
                'COLUMN_TYPE' => 'int',
                'project_id_nullable' => 1,
                'has_leading_index' => 0,
            ],
        ],
        static fn(string $table): int => 0,
    );
    schemaContractSame(
        [
            'ALTER TABLE `programa` MODIFY `project_id` INT NOT NULL',
            'ALTER TABLE `programa` ADD INDEX `idx_programa_project_scope` (`project_id`)',
        ],
        $plan['sql'],
        'El plan dry-run no propone el SQL exacto esperado.',
    );
    schemaContractSame(
        'tables_checked=2 null_rows=0 columns_changed=1 indexes_added=1',
        projectScopeFormatSummary($plan),
        'El resumen del dry-run no conserva el contrato exacto.',
    );

    $blocked = projectScopeBuildPlan(
        ['programa' => [
            'TABLE_TYPE' => 'BASE TABLE',
            'COLUMN_TYPE' => 'int',
            'project_id_nullable' => 1,
            'has_leading_index' => 0,
        ]],
        static fn(string $table): int => $table === 'programa' ? 3 : 0,
    );
    schemaContractSame(['programa' => 3], $blocked['null_tables'], 'El plan no registra la tabla con NULLs.');
    schemaContractSame([], $blocked['sql'], 'El plan propone DDL pese a encontrar project_id NULL.');
    schemaContractSame(
        'tables_checked=1 null_rows=3 columns_changed=0 indexes_added=0',
        projectScopeFormatSummary($blocked),
        'El resumen bloqueado no conserva el contrato exacto.',
    );

    $converged = projectScopeBuildPlan(
        ['programa' => [
            'TABLE_TYPE' => 'BASE TABLE',
            'COLUMN_TYPE' => 'int',
            'project_id_nullable' => 0,
            'has_leading_index' => 1,
        ]],
        static fn(string $table): int => 0,
    );
    schemaContractSame([], $converged['sql'], 'Una segunda planificación idempotente aún propone DDL.');

    $viewRows = [];
    for ($view = 1; $view <= 9; $view++) {
        $viewRows['bi_view_' . $view] = [
            'TABLE_TYPE' => 'VIEW',
            'COLUMN_TYPE' => 'int',
            'project_id_nullable' => 1,
            'has_leading_index' => 0,
        ];
    }
    $viewRows['programa'] = [
        'TABLE_TYPE' => 'BASE TABLE',
        'COLUMN_TYPE' => 'int unsigned',
        'project_id_nullable' => 1,
        'has_leading_index' => 1,
    ];
    $countedTables = [];
    $viewsSkipped = projectScopeBuildPlan(
        $viewRows,
        static function (string $table) use (&$countedTables): int {
            $countedTables[] = $table;
            return 0;
        },
    );
    schemaContractSame(['programa'], $countedTables, 'El preflight contó NULLs sobre una VIEW.');
    schemaContractSame(
        ['ALTER TABLE `programa` MODIFY `project_id` INT UNSIGNED NOT NULL'],
        $viewsSkipped['sql'],
        'El plan no preservó el entero seguro o propuso DDL para una VIEW.',
    );
    schemaContractSame(
        'tables_checked=1 null_rows=0 columns_changed=1 indexes_added=0',
        projectScopeFormatSummary($viewsSkipped),
        'El resumen incluyó VIEWs como tablas físicas.',
    );

    $nullCounters = 0;
    try {
        projectScopeBuildPlan(
            [
                'programa' => [
                    'TABLE_TYPE' => 'BASE TABLE',
                    'COLUMN_TYPE' => 'int',
                    'project_id_nullable' => 0,
                    'has_leading_index' => 1,
                ],
                'tabla_incompatible' => [
                    'TABLE_TYPE' => 'BASE TABLE',
                    'COLUMN_TYPE' => 'varchar(100)',
                    'project_id_nullable' => 1,
                    'has_leading_index' => 0,
                ],
            ],
            static function (string $table) use (&$nullCounters): int {
                $nullCounters++;
                return 0;
            },
        );
        $failures[] = 'La migración aceptó project_id varchar en una BASE TABLE Project.';
    } catch (RuntimeException) {
        $checks++;
    }
    schemaContractSame(0, $nullCounters, 'El lote no se prevalidó completo antes de consultar o aplicar cambios.');

    ob_start();
    $preflightCode = projectScopeMigrationMain(
        [],
        static function (bool $apply): PDO {
            throw new RuntimeException('connection-refused-for-test');
        },
    );
    ob_end_clean();
    schemaContractSame(1, $preflightCode, 'Un fallo de conexión/preflight terminó con RC 0.');
}

$catalog = Database::getInstance()->tableScopeCatalog();
$schemaFindings = [];

foreach ($catalog->schemaRows() as $table => $row) {
    $kind = $catalog->kind($table);

    if ($kind === TableScopeKind::Project) {
        if (($row['TABLE_TYPE'] ?? null) !== 'BASE TABLE') {
            continue;
        }
        $problems = [];
        try {
            projectScopeIntegerColumnType($row['COLUMN_TYPE'] ?? null);
        } catch (RuntimeException) {
            $problems[] = 'project_id tiene tipo incompatible';
        }
        if ((int) ($row['project_id_nullable'] ?? 0) === 1) {
            $problems[] = 'project_id permite NULL';
        }
        if ((int) ($row['has_leading_index'] ?? 0) === 0) {
            $problems[] = 'project_id no es índice líder';
        }
        if ($problems !== []) {
            $schemaFindings[] = "{$table}: " . implode('; ', $problems);
        }
    }

    if ($kind === TableScopeKind::Unclassified) {
        $schemaFindings[] = "{$table}: denied (sin definición explícita de alcance)";
    }
}

$label = $mode === '--audit' ? 'AUDIT' : 'ENFORCE';
echo "=== Project Scope Schema Contract: {$label} ===\n";
echo "Pure contracts: {$checks} checks.\n";
foreach ($schemaFindings as $finding) {
    echo " - {$finding}\n";
}

if ($failures !== []) {
    foreach ($failures as $failure) {
        echo " - FAIL: {$failure}\n";
    }
    echo 'Fallos de contrato: ' . count($failures) . ".\n";
    exit(1);
}

if ($schemaFindings === []) {
    echo "Sin hallazgos.\n";
} elseif ($mode === '--audit') {
    echo 'Hallazgos auditados: ' . count($schemaFindings) . " (audit no bloquea).\n";
} else {
    echo 'Hallazgos bloqueantes: ' . count($schemaFindings) . ".\n";
    exit(1);
}
