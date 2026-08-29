<?php

declare(strict_types=1);

/**
 * @param list<string> $arguments
 */
function projectScopeParseApply(array $arguments): bool
{
    if ($arguments === []) {
        return false;
    }
    if ($arguments === ['--apply']) {
        return true;
    }

    throw new InvalidArgumentException('Opción no reconocida.');
}

function projectScopeQuoteIdentifier(string $identifier): string
{
    if (preg_match('/^[A-Za-z0-9_]+$/', $identifier) !== 1 || strlen($identifier) > 64) {
        throw new InvalidArgumentException('Identificador fuera de allowlist.');
    }

    return '`' . $identifier . '`';
}

/**
 * @param array<string, array<string, mixed>> $schemaRows
 * @param callable(string): int $nullCounter
 * @return array{
 *   tables_checked: int,
 *   null_rows: int,
 *   columns_changed: int,
 *   indexes_added: int,
 *   null_tables: array<string, int>,
 *   sql: list<string>
 * }
 */
function projectScopeBuildPlan(array $schemaRows, callable $nullCounter): array
{
    ksort($schemaRows);
    $plan = [
        'tables_checked' => count($schemaRows),
        'null_rows' => 0,
        'columns_changed' => 0,
        'indexes_added' => 0,
        'null_tables' => [],
        'sql' => [],
    ];

    foreach ($schemaRows as $table => $row) {
        projectScopeQuoteIdentifier($table);
        $nullRows = $nullCounter($table);
        if ($nullRows < 0) {
            throw new RuntimeException('El conteo de NULL no puede ser negativo.');
        }
        $plan['null_rows'] += $nullRows;
        if ($nullRows > 0) {
            $plan['null_tables'][$table] = $nullRows;
        }
    }

    if ($plan['null_tables'] !== []) {
        return $plan;
    }

    foreach ($schemaRows as $table => $row) {
        $quotedTable = projectScopeQuoteIdentifier($table);
        if ((int) ($row['project_id_nullable'] ?? 0) === 1) {
            $plan['sql'][] = "ALTER TABLE {$quotedTable} MODIFY `project_id` INT NOT NULL";
            $plan['columns_changed']++;
        }
        if ((int) ($row['has_leading_index'] ?? 0) === 0) {
            $index = projectScopeQuoteIdentifier('idx_' . $table . '_project_scope');
            $plan['sql'][] = "ALTER TABLE {$quotedTable} ADD INDEX {$index} (`project_id`)";
            $plan['indexes_added']++;
        }
    }

    return $plan;
}

/** @param array{tables_checked: int, null_rows: int, columns_changed: int, indexes_added: int} $plan */
function projectScopeFormatSummary(array $plan): string
{
    return sprintf(
        'tables_checked=%d null_rows=%d columns_changed=%d indexes_added=%d',
        $plan['tables_checked'],
        $plan['null_rows'],
        $plan['columns_changed'],
        $plan['indexes_added'],
    );
}

function projectScopeMigrationMain(array $arguments): int
{
    try {
        $apply = projectScopeParseApply($arguments);
    } catch (InvalidArgumentException) {
        fwrite(STDERR, "Uso: php database/migrations/20260828_project_scope_contract.php [--apply]\n");
        return 2;
    }

    require_once __DIR__ . '/../../vendor/autoload.php';
    require_once __DIR__ . '/../../src/Core/Database.php';

    $database = Database::getInstance();
    $reflection = new ReflectionClass($database);
    $property = $reflection->getProperty('pdo');
    $property->setAccessible(true);
    $pdo = $property->getValue($database);
    if (!$pdo instanceof PDO) {
        throw new RuntimeException('La conexión PDO no está disponible.');
    }
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $catalog = $database->tableScopeCatalog();
    $schemaRows = [];
    foreach ($catalog->projectScopedTables() as $table) {
        $schemaRows[$table] = $catalog->schemaRows()[$table];
    }

    $plan = projectScopeBuildPlan(
        $schemaRows,
        static function (string $table) use ($pdo): int {
            $quotedTable = projectScopeQuoteIdentifier($table);
            $statement = $pdo->prepare("SELECT COUNT(*) FROM {$quotedTable} WHERE `project_id` IS NULL");
            $statement->execute();

            return (int) $statement->fetchColumn();
        },
    );

    echo '=== Project Scope Schema Migration: ' . ($apply ? 'APPLY' : 'DRY-RUN') . " ===\n";
    if ($plan['null_tables'] !== []) {
        foreach ($plan['null_tables'] as $table => $nullRows) {
            echo "BLOCKED table={$table} null_rows={$nullRows}\n";
        }
        echo projectScopeFormatSummary($plan) . "\n";
        echo "ABORT: project_id contiene NULL; no se generó ni ejecutó DDL.\n";

        return 1;
    }

    if ($plan['sql'] === []) {
        echo "No schema changes proposed.\n";
    } else {
        foreach ($plan['sql'] as $sql) {
            echo ($apply ? 'APPLY SQL: ' : 'DRY-RUN SQL: ') . $sql . "\n";
        }
    }

    if ($apply) {
        foreach ($plan['sql'] as $sql) {
            $pdo->exec($sql);
        }
    }

    echo projectScopeFormatSummary($plan) . "\n";
    if (!$apply) {
        echo "No statements executed. Use --apply only after the data-change gate.\n";
    }

    return 0;
}

if (realpath((string) ($_SERVER['SCRIPT_FILENAME'] ?? '')) === __FILE__) {
    exit(projectScopeMigrationMain(array_slice($argv ?? [], 1)));
}
