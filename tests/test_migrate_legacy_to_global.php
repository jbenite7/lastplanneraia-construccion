<?php
// @requiere: db


require_once __DIR__ . '/../vendor/autoload.php';

$root = dirname(__DIR__);
$script = $root . '/database/migrations/20260701_migrate_legacy_to_global.php';
if (!is_file($script)) {
    echo "=== Legacy To Global Migrator: FAIL ===\n";
    echo " - Migrator script missing: {$script}\n";
    exit(1);
}

require_once $script;

$host = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: 'db';
$port = $_ENV['DB_PORT'] ?? getenv('DB_PORT') ?: '3306';
$dbName = $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: '';
$user = $_ENV['DB_USER'] ?? getenv('DB_USER') ?: 'root';
$pass = $_ENV['DB_PASS'] ?? getenv('DB_PASS') ?: '';

$pdo = new PDO(
    "mysql:host={$host};port={$port};dbname={$dbName};charset=utf8mb4",
    $user,
    $pass,
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ],
);

$projectId = 990001;
$prefix = 'e2emig';

function execSql(PDO $pdo, string $sql): void
{
    $pdo->exec($sql);
}

function cleanupMigratorFixture(PDO $pdo, int $projectId, string $prefix): void
{
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
    foreach (['auto_program_log', 'cic', 'programa_consolidado', 'programa', 'subcontratistas', 'semanas_activas'] as $table) {
        $pdo->exec("DELETE FROM `{$table}` WHERE project_id = {$projectId}");
        $pdo->exec("DROP TABLE IF EXISTS `{$prefix}_{$table}`");
        $pdo->exec("DROP TABLE IF EXISTS `zleg_{$prefix}_{$table}`");
    }
    $pdo->exec("DELETE FROM general_proyectos_procesos WHERE Id = {$projectId}");
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
}

function assertSameValue($expected, $actual, string $message): void
{
    if ($expected !== $actual) {
        throw new RuntimeException($message . " Expected {$expected}, got {$actual}");
    }
}

function runMigrator(PDO $pdo, array $options): array
{
    $migrator = new LegacyToGlobalMigrator($pdo, static function (string $line): void {
    });
    return $migrator->run($options);
}

try {
    cleanupMigratorFixture($pdo, $projectId, $prefix);

    $pdo->prepare(
        'INSERT INTO general_proyectos_procesos
            (Id, Proyecto_Proceso, Base_de_Datos, Area, Activo, Acceso, pdcActivo)
         VALUES (?, ?, ?, ?, 1, 1, 0)'
    )->execute([$projectId, 'E2E Migrator', $prefix, 'Construccion']);

    execSql($pdo, "CREATE TABLE `{$prefix}_subcontratistas` (
        `Id` int NOT NULL,
        `subcontratista` varchar(200) NOT NULL,
        `correo_contacto` varchar(200) NOT NULL,
        `NIT` bigint NOT NULL,
        `alcance` varchar(200) NOT NULL,
        `tipo_proveedor` varchar(200) NOT NULL,
        `activo` int NOT NULL DEFAULT 1,
        PRIMARY KEY (`Id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    execSql($pdo, "INSERT INTO `{$prefix}_subcontratistas`
        (`Id`, `subcontratista`, `correo_contacto`, `NIT`, `alcance`, `tipo_proveedor`, `activo`)
        VALUES
        (7, 'E2E Sub A', 'a@example.test', 1001, 'Obra', 'Contratista', 1),
        (8, 'E2E Sub B', 'b@example.test', 1002, 'Diseno', 'Proveedor', 1)");

    execSql($pdo, "CREATE TABLE `{$prefix}_semanas_activas` (
        `Id` int NOT NULL,
        `Semana` int NOT NULL,
        `Fecha_Inicio_Sem` date NOT NULL,
        `Fecha_Fin_Sem` date NOT NULL,
        `Semanal_Confirmada` int DEFAULT 0,
        `reprogramacion` int NOT NULL DEFAULT 0,
        `diferenciaEstructuraCron` int NOT NULL DEFAULT 0,
        PRIMARY KEY (`Id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    execSql($pdo, "INSERT INTO `{$prefix}_semanas_activas`
        (`Id`, `Semana`, `Fecha_Inicio_Sem`, `Fecha_Fin_Sem`, `Semanal_Confirmada`)
        VALUES (3, 11, '2026-01-05', '2026-01-11', 1)");

    execSql($pdo, "CREATE TABLE `{$prefix}_programa_consolidado` (
        `Consecutivo` int NOT NULL,
        `Semana` int NOT NULL,
        `Consecutivo_en_Programa` int NOT NULL,
        `Actividad` varchar(500) DEFAULT NULL,
        `D_y_E` varchar(9) DEFAULT NULL,
        PRIMARY KEY (`Consecutivo`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    execSql($pdo, "INSERT INTO `{$prefix}_programa_consolidado`
        (`Consecutivo`, `Semana`, `Consecutivo_en_Programa`, `Actividad`, `D_y_E`)
        VALUES (21, 11, 7001, 'E2E Consolidado sin padre', 'NR')");

    execSql($pdo, "CREATE TABLE `{$prefix}_cic` (
        `Id` int NOT NULL,
        `Semana` int DEFAULT NULL,
        `subcontratista` varchar(200) DEFAULT NULL,
        PRIMARY KEY (`Id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    execSql($pdo, "INSERT INTO `{$prefix}_cic`
        (`Id`, `Semana`, `subcontratista`)
        VALUES (31, 11, 'E2E Sub Sintetico')");

    execSql($pdo, "CREATE TABLE `{$prefix}_auto_program_log` (
        `id` int NOT NULL,
        `semana` int NOT NULL,
        `consecutivo` int NOT NULL,
        `accion` enum('comprometer','descomprometer','insert_cnp') NOT NULL,
        `detalle` text,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    execSql($pdo, "INSERT INTO `{$prefix}_auto_program_log`
        (`id`, `semana`, `consecutivo`, `accion`, `detalle`)
        VALUES
        (1, 11, 7001, 'comprometer', 'detalle uno'),
        (2, 11, 7001, 'comprometer', 'detalle dos')");

    $dryRun = runMigrator($pdo, ['apply' => false, 'projectId' => $projectId, 'strict' => true]);
    assertSameValue(8, $dryRun['pendingRows'], 'Dry-run must detect all pending rows, including synthetic parents.');
    assertSameValue(0, (int) $pdo->query("SELECT COUNT(*) FROM subcontratistas WHERE project_id = {$projectId}")->fetchColumn(), 'Dry-run must not write target rows.');

    $apply = runMigrator($pdo, ['apply' => true, 'projectId' => $projectId, 'strict' => true]);
    assertSameValue(8, $apply['writtenRows'], 'Apply must write pending rows.');
    assertSameValue(3, (int) $pdo->query("SELECT COUNT(*) FROM subcontratistas WHERE project_id = {$projectId}")->fetchColumn(), 'Subcontractors must be migrated.');
    assertSameValue(1, (int) $pdo->query("SELECT COUNT(*) FROM semanas_activas WHERE project_id = {$projectId}")->fetchColumn(), 'Weeks must be migrated.');
    assertSameValue(1, (int) $pdo->query("SELECT COUNT(*) FROM programa WHERE project_id = {$projectId} AND Consecutivo = 7001")->fetchColumn(), 'Missing program parents must be synthesized.');
    assertSameValue(1, (int) $pdo->query("SELECT COUNT(*) FROM programa_consolidado WHERE project_id = {$projectId}")->fetchColumn(), 'Consolidated rows must be migrated after synthetic parents.');
    assertSameValue(1, (int) $pdo->query("SELECT COUNT(*) FROM subcontratistas WHERE project_id = {$projectId} AND subcontratista = 'E2E Sub Sintetico'")->fetchColumn(), 'Missing subcontractors must be synthesized.');
    assertSameValue(1, (int) $pdo->query("SELECT COUNT(*) FROM cic WHERE project_id = {$projectId}")->fetchColumn(), 'CIC rows must be migrated after synthetic subcontractors.');
    assertSameValue(1, (int) $pdo->query("SELECT COUNT(*) FROM auto_program_log WHERE project_id = {$projectId}")->fetchColumn(), 'Duplicate source log keys must migrate once.');

    $secondApply = runMigrator($pdo, ['apply' => true, 'projectId' => $projectId, 'strict' => true]);
    assertSameValue(0, $secondApply['writtenRows'], 'Second apply must be idempotent.');

    execSql($pdo, "CREATE TABLE `zleg_{$prefix}_subcontratistas` LIKE `{$prefix}_subcontratistas`");
    execSql($pdo, "INSERT INTO `zleg_{$prefix}_subcontratistas`
        (`Id`, `subcontratista`, `correo_contacto`, `NIT`, `alcance`, `tipo_proveedor`, `activo`)
        VALUES (9, 'E2E Divergent', 'z@example.test', 1009, 'Otro', 'Proveedor', 1)");

    try {
        runMigrator($pdo, ['apply' => false, 'projectId' => $projectId, 'strict' => true]);
        throw new RuntimeException('Strict mode must fail when direct and zleg sources diverge.');
    } catch (LegacyToGlobalMigrationException $e) {
        if (!str_contains($e->getMessage(), 'divergent')) {
            throw $e;
        }
    }

    echo "=== Legacy To Global Migrator: OK ===\n";
    echo "Dry-run, apply, idempotency and strict divergence checks passed.\n";
} catch (Throwable $e) {
    echo "=== Legacy To Global Migrator: FAIL ===\n";
    echo ' - ' . $e->getMessage() . "\n";
    exit(1);
} finally {
    cleanupMigratorFixture($pdo, $projectId, $prefix);
}
