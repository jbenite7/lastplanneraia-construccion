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

function projectScopeIntegerColumnType(mixed $columnType): string
{
    $normalized = strtolower(trim((string) $columnType));
    if (preg_match(
        '/^(?:tinyint|smallint|mediumint|int|integer|bigint)(?:\([0-9]+\))?(?: unsigned)?$/',
        $normalized,
    ) !== 1) {
        throw new RuntimeException(sprintf(
            "project_id tiene un tipo incompatible: '%s'.",
            $normalized === '' ? '(vacío)' : $normalized,
        ));
    }

    return strtoupper($normalized);
}

/**
 * @param array<string, array<string, mixed>> $schemaRows
 * @param callable(string): int $nullCounter
 * @return array{
 *   tables_checked: int,
 *   null_rows: int,
 *   columns_changed: int,
 *   indexes_added: int,
 *   views_skipped: int,
 *   null_tables: array<string, int>,
 *   sql: list<string>
 * }
 */
function projectScopeBuildPlan(array $schemaRows, callable $nullCounter): array
{
    ksort($schemaRows);
    $baseTables = [];
    $columnTypes = [];
    $viewsSkipped = 0;
    foreach ($schemaRows as $table => $row) {
        projectScopeQuoteIdentifier($table);
        $tableType = strtoupper(trim((string) ($row['TABLE_TYPE'] ?? 'BASE TABLE')));
        if ($tableType === 'VIEW') {
            $viewsSkipped++;
            continue;
        }
        if ($tableType !== 'BASE TABLE') {
            throw new RuntimeException("Tipo de objeto no soportado para {$table}: {$tableType}.");
        }

        try {
            $columnTypes[$table] = projectScopeIntegerColumnType($row['COLUMN_TYPE'] ?? null);
        } catch (RuntimeException $exception) {
            throw new RuntimeException("{$table}: {$exception->getMessage()}", previous: $exception);
        }
        $baseTables[$table] = $row;
    }

    $plan = [
        'tables_checked' => count($baseTables),
        'null_rows' => 0,
        'columns_changed' => 0,
        'indexes_added' => 0,
        'views_skipped' => $viewsSkipped,
        'null_tables' => [],
        'sql' => [],
    ];

    foreach ($baseTables as $table => $row) {
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

    foreach ($baseTables as $table => $row) {
        $quotedTable = projectScopeQuoteIdentifier($table);
        if ((int) ($row['project_id_nullable'] ?? 0) === 1) {
            $plan['sql'][] = sprintf(
                'ALTER TABLE %s MODIFY `project_id` %s NOT NULL',
                $quotedTable,
                $columnTypes[$table],
            );
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

function projectScopeEnvironment(string $name, ?string $default = null): string
{
    $value = $_ENV[$name] ?? $_SERVER[$name] ?? getenv($name);
    if ($value === false || $value === '') {
        if ($default !== null) {
            return $default;
        }
        throw new RuntimeException("Falta la variable requerida {$name}.");
    }

    return (string) $value;
}

function projectScopeConnect(bool $apply): PDO
{
    $host = projectScopeEnvironment('DB_HOST', 'localhost');
    $port = projectScopeEnvironment('DB_PORT', '3306');
    $database = projectScopeEnvironment('DB_NAME');
    $userVariable = $apply ? 'DB_MIGRATION_ADMIN_USER' : 'DB_USER';
    $passVariable = $apply ? 'DB_MIGRATION_ADMIN_PASS' : 'DB_PASS';
    $user = projectScopeEnvironment($userVariable);
    $password = projectScopeEnvironment($passVariable);

    if ($apply) {
        $runtimeUser = $_ENV['DB_USER'] ?? $_SERVER['DB_USER'] ?? getenv('DB_USER');
        if (is_string($runtimeUser) && $runtimeUser !== '' && hash_equals($runtimeUser, $user)) {
            throw new RuntimeException('El canal admin de migración no puede reutilizar DB_USER runtime.');
        }
    }

    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $database);

    return new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
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

function projectScopeMigrationMain(array $arguments, ?callable $connector = null): int
{
    try {
        $apply = projectScopeParseApply($arguments);
    } catch (InvalidArgumentException) {
        fwrite(STDERR, "Uso: php database/migrations/20260828_project_scope_contract.php [--apply]\n");
        return 2;
    }

    require_once __DIR__ . '/../../vendor/autoload.php';
    try {
        $pdo = ($connector ?? 'projectScopeConnect')($apply);
        if (!$pdo instanceof PDO) {
            throw new RuntimeException('La conexión PDO no está disponible.');
        }
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $catalog = \App\Security\DataScope\TableScopeCatalog::fromPdo($pdo);
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
    } catch (PDOException) {
        fwrite(STDERR, "ABORT: database connection/preflight failed.\n");
        return 1;
    } catch (Throwable $exception) {
        fwrite(STDERR, "ABORT: migration preflight failed: {$exception->getMessage()}\n");
        return 1;
    }
}

if (realpath((string) ($_SERVER['SCRIPT_FILENAME'] ?? '')) === __FILE__) {
    exit(projectScopeMigrationMain(array_slice($argv ?? [], 1)));
}
