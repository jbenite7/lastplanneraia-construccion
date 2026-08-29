<?php

declare(strict_types=1);

// @requiere: db

function executeFixtureSql(object $pdo, string $sql): void
{
    $pdo->exec($sql);
}

function executeNestedFixtureSql(object $pdo, string $statement): void
{
    executeFixtureSql($pdo, $statement);
}

executeNestedFixtureSql($pdo, <<<'SQL'
CREATE VIEW fixture_runtime_view AS SELECT 1
SQL);
echo "OK: este fixture nunca debe ejecutarse\n";
