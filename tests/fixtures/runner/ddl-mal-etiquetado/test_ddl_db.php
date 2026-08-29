<?php

declare(strict_types=1);

// @requiere: db

$pdo->exec('CREATE TABLE fixture_omitida (id INT)');
echo "OK: este fixture nunca debe ejecutarse\n";
