<?php

declare(strict_types=1);

// @requiere: puro

require_once __DIR__ . '/../scripts/lib/php-test-ddl-inventory.php';

$checks = 0;
$failures = [];

/** @param list<array{call: string, line: int}> $actual */
function inventoryExpectCount(string $label, int $expected, array $actual): void
{
    global $checks, $failures;
    $checks++;
    if (count($actual) !== $expected) {
        $failures[] = "{$label}: esperaba {$expected} hallazgo(s), obtuvo " . json_encode($actual);
    }
}

$probes = [
    'literal directo' => [<<<'PHP'
<?php
$pdo->exec('CREATE TABLE fixture_directa (id INT)');
PHP, 1],
    'literal directo con comillas dobles' => [<<<'PHP'
<?php
$pdo->exec("DROP TABLE fixture_doble");
PHP, 1],
    'SQL esperado no ejecutado' => [<<<'PHP'
<?php
$expected = 'ALTER TABLE solo_esperado ADD COLUMN x INT';
PHP, 0],
    'helper execSql equivalente' => [<<<'PHP'
<?php
execSql($pdo, 'DROP TABLE fixture_helper');
PHP, 1],
    'wrapper local runSql' => [<<<'PHP'
<?php
function runSql($pdo, string $sql): void { $pdo->exec($sql); }
runSql($pdo, 'ALTER TABLE fixture_wrapper ADD COLUMN x INT');
PHP, 1],
    'wrappers locales anidados' => [<<<'PHP'
<?php
function innerSql($pdo, string $statement): void { $pdo->prepare($statement); }
function outerSql($pdo, string $sql): void { innerSql($pdo, $sql); }
outerSql($pdo, 'DROP VIEW vista_fixture');
PHP, 1],
    'heredoc directo' => [<<<'PHP'
<?php
$pdo->exec(<<<SQL
CREATE TRIGGER fixture_trigger BEFORE INSERT ON fixture FOR EACH ROW SET @x = 1
SQL
);
PHP, 1],
    'nowdoc directo' => [<<<'PHP'
<?php
$pdo->query(<<<'SQL'
RENAME TABLE fixture_a TO fixture_b
SQL
);
PHP, 1],
    'cadena de aliases' => [<<<'PHP'
<?php
$ddl = 'CREATE VIEW fixture_view AS SELECT 1';
$alias = $ddl;
$sql = $alias;
$pdo->exec($sql);
PHP, 1],
    'CREATE VIEW directo' => [<<<'PHP'
<?php
$pdo->exec('CREATE VIEW fixture_view AS SELECT 1');
PHP, 1],
    'segundo statement DDL' => [<<<'PHP'
<?php
$pdo->exec('SELECT 1; DROP VIEW fixture_view');
PHP, 1],
    'verbo DDL dentro de literal SQL' => [<<<'PHP'
<?php
$pdo->exec("SELECT '; DROP VIEW fixture_view' AS expected_text");
PHP, 0],
    'DML seguro por wrapper' => [<<<'PHP'
<?php
function runSafeSql($pdo, string $sql): void { $pdo->exec($sql); }
runSafeSql($pdo, 'SELECT id FROM fixture');
PHP, 0],
    'valor dinámico no resuelto en sink' => [<<<'PHP'
<?php
$sql = getenv('FIXTURE_SQL');
$pdo->exec($sql);
PHP, 1],
    'valor dinámico no resuelto por wrapper' => [<<<'PHP'
<?php
function runDynamicSql($pdo, string $sql): void { $pdo->exec($sql); }
$runtimeSql = getenv('FIXTURE_SQL');
runDynamicSql($pdo, $runtimeSql);
PHP, 1],
    'foreach asociativo de SQL DML literal' => [<<<'PHP'
<?php
$checks = [
    'a' => 'SELECT id FROM fixture_a',
    'b' => 'SELECT id FROM fixture_b',
];
foreach ($checks as $name => $sql) {
    $pdo->query($sql);
}
PHP, 0],
    'foreach destructurado dentro de wrapper local' => [<<<'PHP'
<?php
function assertRows($db): void {
    $checks = [
        ['SELECT COUNT(*) FROM fixture_a', []],
        ['SELECT COUNT(*) FROM fixture_b', []],
    ];
    foreach ($checks as [$sql, $params]) {
        $db->query($sql, $params);
    }
}
assertRows($db);
PHP, 0],
];

foreach ($probes as $label => [$source, $expected]) {
    inventoryExpectCount($label, $expected, phpTestExecutableDdlCalls($source));
}

$ddlFamilies = [
    'DROP INDEX fixture_idx ON fixture',
    'ALTER VIEW fixture_view AS SELECT 2',
    'TRUNCATE TABLE fixture',
    'RENAME TABLE fixture TO fixture_old',
    'CREATE TRIGGER fixture_trigger BEFORE INSERT ON fixture FOR EACH ROW SET @x = 1',
    'GRANT SELECT ON fixture.* TO fixture_user',
    'REVOKE SELECT ON fixture.* FROM fixture_user',
    'CREATE USER fixture_user IDENTIFIED BY \'irrelevant-test-value\'',
];
foreach ($ddlFamilies as $sql) {
    $source = "<?php\n\$pdo->exec(" . var_export($sql, true) . ");\n";
    inventoryExpectCount($sql, 1, phpTestExecutableDdlCalls($source));
}

if ($failures !== []) {
    foreach ($failures as $failure) {
        fwrite(STDERR, "FAIL: {$failure}\n");
    }
    fwrite(STDERR, 'FAIL: ' . count($failures) . " de {$checks} comprobaciones fallaron\n");
    exit(1);
}

echo "OK: {$checks} comprobaciones pasaron\n";
