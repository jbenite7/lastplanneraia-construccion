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
    'wrapper de método PHPUnit' => [<<<'PHP'
<?php
final class FixtureTest {
    public function testSchemaFixture(): void {
        $this->runSql('CREATE TABLE fixture_method (id INT)');
    }

    private function runSql(string $sql): void {
        $this->pdo->exec($sql);
    }
}
PHP, 1],
    'wrappers de método PHPUnit anidados' => [<<<'PHP'
<?php
final class NestedFixtureTest {
    public function testSchemaFixture(): void {
        $this->dispatch('DROP TABLE fixture_nested_method');
    }

    private function dispatch(string $sql): void {
        $this->runSql($sql);
    }

    private function runSql(string $statement): void {
        $this->pdo->exec($statement);
    }
}
PHP, 1],
    'wrapper heredado por clase PHPUnit' => [<<<'PHP'
<?php
abstract class BaseFixtureTest {
    protected function runSql($pdo, string $sql): void {
        $pdo->exec($sql);
    }
}

final class ChildFixtureTest extends BaseFixtureTest {
    public function testSchemaFixture(): void {
        $this->runSql($this->pdo, 'DROP TABLE fixture_inherited');
    }
}
PHP, 1],
    'wrapper heredado sin nombre de sink' => [<<<'PHP'
<?php
abstract class BaseNeutralFixtureTest {
    protected function applyFixture($pdo, string $sql): void {
        $pdo->exec($sql);
    }
}

final class ChildNeutralFixtureTest extends BaseNeutralFixtureTest {
    public function testSchemaFixture(): void {
        $this->applyFixture($this->pdo, 'DROP TABLE fixture_inherited_neutral');
    }
}
PHP, 1],
    'wrapper heredado desde padre externo falla cerrado' => [<<<'PHP'
<?php
final class ImportedChildFixtureTest extends ImportedFixtureBase {
    public function testSchemaFixture(): void {
        $this->applyFixture('DROP TABLE fixture_inherited_external');
    }
}
PHP, 1],
    'call_user_func hacia wrapper local' => [<<<'PHP'
<?php
final class IndirectFixtureTest {
    public function testSchemaFixture(): void {
        call_user_func([$this, 'applyFixture'], 'DROP TABLE fixture_indirect');
    }

    private function applyFixture(string $sql): void {
        $this->pdo->exec($sql);
    }
}
PHP, 1],
    'call_user_func externo no demostrable falla cerrado' => [<<<'PHP'
<?php
call_user_func([ExternalWorker::class, 'transform'], 'SELECT 1');
PHP, 1],
    'first-class callable externo no demostrable falla cerrado' => [<<<'PHP'
<?php
$callable = ExternalWorker::transform(...);
$callable('SELECT 1');
PHP, 1],
    'Closure::fromCallable externo no demostrable falla cerrado' => [<<<'PHP'
<?php
$callable = Closure::fromCallable([ExternalWorker::class, 'transform']);
$callable('SELECT 1');
PHP, 1],
    'DataProvider con DDL ejecutable' => [<<<'PHP'
<?php
use PHPUnit\Framework\Attributes\DataProvider;

final class ProviderFixtureTest {
    #[DataProvider('rows')]
    public function testRow(int $id): void {}

    public static function rows(): array {
        global $pdo;
        $pdo->exec('DROP TABLE fixture_provider');
        return [[1]];
    }
}
PHP, 1],
    'DataProviderExternal con DDL ejecutable' => [<<<'PHP'
<?php
use PHPUnit\Framework\Attributes\DataProviderExternal;

final class ExternalRows {
    public static function rows(): array {
        global $pdo;
        $pdo->exec('DROP TABLE fixture_external_provider');
        return [[1]];
    }
}

final class ExternalProviderFixtureTest {
    #[DataProviderExternal(ExternalRows::class, 'rows')]
    public function testRow(int $id): void {}
}
PHP, 1],
    'argumentos nombrados enlazan por nombre declarado' => [<<<'PHP'
<?php
function runNamedSql(string $sql, string $tag, $pdo): void {
    $pdo->exec($sql);
}
runNamedSql(tag: 'SELECT 1', pdo: $pdo, sql: 'DROP TABLE fixture_named');
PHP, 1],
    'closure por referencia puede reemplazar SQL seguro' => [<<<'PHP'
<?php
$sql = 'SELECT 1';
$mutate = function () use (&$sql): void {
    $sql = 'DROP TABLE fixture_by_reference';
};
$mutate();
$pdo->exec($sql);
PHP, 1],
    'parámetro por referencia deja el valor posterior desconocido' => [<<<'PHP'
<?php
function replaceFixtureSql(string &$sql): void {
    $sql = 'DROP TABLE fixture_parameter_reference';
}
$sql = 'SELECT 1';
replaceFixtureSql($sql);
$pdo->exec($sql);
PHP, 1],
    'global modificado por callable deja el valor posterior desconocido' => [<<<'PHP'
<?php
function replaceGlobalFixtureSql(): void {
    global $sql;
    $sql = 'DROP TABLE fixture_global_reference';
}
$sql = 'SELECT 1';
replaceGlobalFixtureSql();
$pdo->exec($sql);
PHP, 1],
    'closure DDL invocada' => [<<<'PHP'
<?php
$runSql = static function ($pdo, string $sql): void {
    $pdo->exec($sql);
};
$runSql($pdo, 'DROP TABLE fixture_closure');
PHP, 1],
    'closure DML invocada' => [<<<'PHP'
<?php
$runSql = static function ($pdo, string $sql): void {
    $pdo->exec($sql);
};
$runSql($pdo, 'SELECT id FROM fixture_closure');
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
    'interpolación dinámica con prefijo SELECT' => [<<<'PHP'
<?php
$suffix = getenv('FIXTURE_SQL_SUFFIX');
$pdo->exec("SELECT id FROM fixture {$suffix}");
PHP, 1],
    'cadena de aliases dinámica con prefijo SELECT' => [<<<'PHP'
<?php
$runtime = getenv('FIXTURE_SQL_SUFFIX');
$alias = $runtime;
$suffix = $alias;
$pdo->query('SELECT id FROM fixture ' . $suffix);
PHP, 1],
    'coalesce dinámico no se reduce a escalar seguro' => [<<<'PHP'
<?php
$sql = getenv('FIXTURE_SQL') ?? 'SELECT id FROM fixture';
$pdo->query($sql);
PHP, 1],
    'concatenación totalmente constante DML' => [<<<'PHP'
<?php
$sql = 'SELECT id ' . 'FROM fixture';
$pdo->query($sql);
PHP, 0],
    'if else conserva ruta DDL aunque SELECT sea lexicalmente último' => [<<<'PHP'
<?php
if (getenv('USE_DDL')) {
    $sql = 'DROP TABLE fixture_branch';
} else {
    $sql = 'SELECT id FROM fixture_branch';
}
$pdo->exec($sql);
PHP, 1],
    'switch conserva ruta DDL aunque SELECT sea lexicalmente último' => [<<<'PHP'
<?php
switch (getenv('FIXTURE_MODE')) {
    case 'ddl':
        $sql = 'ALTER TABLE fixture_switch ADD COLUMN x INT';
        break;
    default:
        $sql = 'SELECT id FROM fixture_switch';
}
$pdo->exec($sql);
PHP, 1],
    'loop conserva ruta DDL previa aunque el body asigne SELECT' => [<<<'PHP'
<?php
$sql = 'TRUNCATE TABLE fixture_loop';
foreach (getenv('ROWS') ?: [] as $row) {
    $sql = 'SELECT id FROM fixture_loop';
}
$pdo->exec($sql);
PHP, 1],
    'ternario conserva alternativa DDL' => [<<<'PHP'
<?php
$sql = getenv('USE_DDL')
    ? 'RENAME TABLE fixture_a TO fixture_b'
    : 'SELECT id FROM fixture_a';
$pdo->exec($sql);
PHP, 1],
    'fragmentos alternativos por rama pueden formar DDL' => [<<<'PHP'
<?php
$prefix = getenv('USE_DDL') ? 'DR' : 'SELECT ';
$pdo->exec($prefix . 'OP TABLE fixture_branch_fragments');
PHP, 1],
    'fragmentos alternativos por foreach pueden formar DDL' => [<<<'PHP'
<?php
$prefixes = ['DR', 'SELECT '];
foreach ($prefixes as $prefix) {
    $pdo->exec($prefix . 'OP TABLE fixture_foreach_fragments');
}
PHP, 1],
    'doble guion sin whitespace no oculta segundo statement DDL' => [<<<'PHP'
<?php
$pdo->exec('SELECT 1--2; DROP TABLE fixture_split');
PHP, 1],
    'comentario versionado inspecciona statements posteriores' => [<<<'PHP'
<?php
$pdo->exec('/*!50003 SET @x = 1; DROP TABLE fixture_versioned_comment */');
PHP, 1],
    'comentario versionado con SET solamente permanece seguro' => [<<<'PHP'
<?php
$pdo->exec('/*!50003 SET @x = 1 */');
PHP, 0],
    'trigger envuelto en directivas DELIMITER falla cerrado' => [<<<'PHP'
<?php
$pdo->exec(<<<'SQL'
DELIMITER $$
CREATE TRIGGER fixture_trigger BEFORE INSERT ON fixture
FOR EACH ROW
BEGIN
    SET @x = 1;
END$$
DELIMITER ;
SQL
);
PHP, 1],
    'transformador insertProjectId conserva DML constante' => [<<<'PHP'
<?php
$sql = 'INSERT INTO fixture (id) VALUES (?)';
$params = [1];
[$sql, $params] = $db->insertProjectId($sql, 7, $params);
$db->query($sql, $params);
PHP, 0],
    'transformador insertProjectId no sanea SQL dinámico' => [<<<'PHP'
<?php
$sql = getenv('FIXTURE_SQL');
$params = [];
[$sql, $params] = $db->insertProjectId($sql, 7, $params);
$db->query($sql, $params);
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
