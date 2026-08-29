<?php
// @requiere: db

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';

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
function schemaContractRunGrantAudit(string $script, string $grant): array
{
    $fixture = tempnam(sys_get_temp_dir(), 'lps-grants-');
    if ($fixture === false || file_put_contents($fixture, $grant . "\n") === false) {
        throw new RuntimeException('No se pudo crear el fixture temporal de grants.');
    }

    $command = [PHP_BINARY, $script, '--grants-file=' . $fixture];
    $pipes = [];
    $process = proc_open(
        $command,
        [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
        $pipes,
        null,
        ['DB_NAME' => 'lps'],
    );

    if (!is_resource($process)) {
        @unlink($fixture);
        throw new RuntimeException('No se pudo iniciar el auditor de grants.');
    }

    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $code = proc_close($process);
    @unlink($fixture);

    return ['code' => $code, 'output' => (string) $stdout . (string) $stderr];
}

$grantAudit = __DIR__ . '/../scripts/security/audit-runtime-db-grants.php';
if (!is_file($grantAudit)) {
    $failures[] = 'Falta scripts/security/audit-runtime-db-grants.php.';
} else {
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
}

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
            'cic' => ['project_id_nullable' => 0, 'has_leading_index' => 1],
            'programa' => ['project_id_nullable' => 1, 'has_leading_index' => 0],
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
        ['programa' => ['project_id_nullable' => 1, 'has_leading_index' => 0]],
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
        ['programa' => ['project_id_nullable' => 0, 'has_leading_index' => 1]],
        static fn(string $table): int => 0,
    );
    schemaContractSame([], $converged['sql'], 'Una segunda planificación idempotente aún propone DDL.');
}

$catalog = Database::getInstance()->tableScopeCatalog();
$schemaFindings = [];

foreach ($catalog->schemaRows() as $table => $row) {
    $kind = $catalog->kind($table);

    if ($kind === TableScopeKind::Project) {
        $problems = [];
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
