<?php

declare(strict_types=1);

// @requiere: admin-db

function executeAdminFixtureSql(object $pdo, string $sql): void
{
    $pdo->exec($sql);
}

executeAdminFixtureSql($pdo, <<<'SQL'
CREATE VIEW fixture_admin_view AS SELECT 1
SQL);
echo "OK: este fixture solo se inventaría con --solo-listar\n";
