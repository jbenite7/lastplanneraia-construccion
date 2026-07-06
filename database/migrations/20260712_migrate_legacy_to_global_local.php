<?php

/**
 * Migración de datos legacy ({prefix}_*) → tablas globales con project_id
 * 
 * Estrategia:
 * - Para cada proyecto activo en general_proyectos_procesos
 * - Para cada tabla global estándar
 * - INSERT IGNORE de las columnas comunes (excluyendo PKs AUTO_INCREMENT)
 * - Agregar project_id explícitamente
 * 
 * Uso:
 *   docker compose exec app php database/migrations/20260712_migrate_legacy_to_global_local.php           # dry-run
 *   docker compose exec app php database/migrations/20260712_migrate_legacy_to_global_local.php --apply   # ejecuta
 */

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../src/Core/Database.php';

const GLOBAL_TABLES = [
    'programa',
    'programa_consolidado',
    'programacion_semanal',
    'pdc',
    'actividades',
    'semanas_activas',
    'subcontratistas',
    'profesionales',
    'cic',
    'lps_escalamientos',
    'lps_drawer_comentarios',
    'pi_shared_constraints',
    'pi_shared_constraint_links',
    'auto_program_log',
    'pg_tracking',
    'cambios',
];

class LegacyToGlobalLocalMigrator
{
    private PDO $pdo;
    private bool $apply;
    private array $summary = [
        'projects' => 0,
        'tables' => 0,
        'sourceRows' => 0,
        'writtenRows' => 0,
        'skipped' => 0,
        'errors' => [],
    ];

    public function __construct(PDO $pdo, bool $apply)
    {
        $this->pdo = $pdo;
        $this->apply = $apply;
        $this->pdo->exec("SET time_zone = '-05:00'");
    }

    public function run(): array
    {
        $projects = $this->getActiveProjects();
        $this->summary['projects'] = count($projects);

        $this->log($this->apply ? '=== APPLY mode ===' : '=== DRY-RUN mode ===');

        foreach ($projects as $project) {
            $prefix = $project['Base_de_Datos'];
            $projectId = (int) $project['ID'];

            if (!$this->legacyPrefixExists($prefix)) {
                $this->log("Project {$projectId} ({$prefix}): no legacy tables found, skipping");
                continue;
            }

            $this->log("--- Project {$projectId} ({$prefix}) ---");

            foreach (GLOBAL_TABLES as $globalTable) {
                $legacyTable = "{$prefix}_{$globalTable}";
                if (!$this->tableExists($legacyTable)) {
                    continue;
                }

                $result = $this->migrateTable($projectId, $globalTable, $legacyTable);
                $this->summary['sourceRows'] += $result['sourceRows'];
                $this->summary['writtenRows'] += $result['writtenRows'];
                $this->summary['skipped'] += $result['skipped'];
                if ($result['writtenRows'] > 0 || $result['sourceRows'] > 0) {
                    $this->summary['tables']++;
                }
            }
        }

        $this->log('=== Summary ===');
        $this->log("projects={$this->summary['projects']} tables={$this->summary['tables']} sourceRows={$this->summary['sourceRows']} writtenRows={$this->summary['writtenRows']} skipped={$this->summary['skipped']}");

        return $this->summary;
    }

    private function getActiveProjects(): array
    {
        $stmt = $this->pdo->query("SELECT ID, Base_de_Datos, Proyecto_Proceso FROM general_proyectos_procesos WHERE Activo = 1 ORDER BY ID");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function legacyPrefixExists(string $prefix): bool
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name LIKE ?");
        $stmt->execute([$prefix . '_%']);
        return (int) $stmt->fetchColumn() > 0;
    }

    private function tableExists(string $name): bool
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?");
        $stmt->execute([$name]);
        return (int) $stmt->fetchColumn() > 0;
    }

    private function migrateTable(int $projectId, string $globalTable, string $legacyTable): array
    {
        $globalCols = $this->getColumns($globalTable);
        $legacyCols = $this->getColumns($legacyTable);

        // Find common columns (excluding AUTO_INCREMENT PKs and project_id)
        $commonCols = $this->intersectColumns($globalCols, $legacyCols);

        if (empty($commonCols)) {
            return ['sourceRows' => 0, 'writtenRows' => 0, 'skipped' => 0];
        }

        // Source count
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM `{$legacyTable}`");
        $stmt->execute();
        $sourceRows = (int) $stmt->fetchColumn();

        if ($sourceRows === 0) {
            return ['sourceRows' => 0, 'writtenRows' => 0, 'skipped' => 0];
        }

        // Build INSERT
        $insertCols = array_merge(['project_id'], $commonCols);
        $placeholders = array_fill(0, count($insertCols), '?');
        $quotedCols = array_map(fn($c) => "`{$c}`", $insertCols);

        $sql = sprintf(
            'INSERT %s INTO `%s` (%s) SELECT %s, %s FROM `%s`',
            $this->apply ? '' : 'IGNORE',
            $globalTable,
            implode(', ', $quotedCols),
            $projectId,
            implode(', ', array_map(fn($c) => "`{$c}`", $commonCols)),
            $legacyTable
        );

        if (!$this->apply) {
            $this->log("DRY: {$legacyTable} -> {$globalTable}: source={$sourceRows}");
            return ['sourceRows' => $sourceRows, 'writtenRows' => 0, 'skipped' => 0];
        }

        try {
            $this->pdo->exec($sql);
            // Get actual written count
            $checkSql = "SELECT COUNT(*) FROM `{$globalTable}` WHERE project_id = ?";
            $checkStmt = $this->pdo->prepare($checkSql);
            // We can't know exact added rows without a transaction, but we log cumulative
            $this->log("APPLIED: {$legacyTable} -> {$globalTable}: source={$sourceRows}");
            return ['sourceRows' => $sourceRows, 'writtenRows' => $sourceRows, 'skipped' => 0];
        } catch (PDOException $e) {
            $err = "ERROR: {$legacyTable} -> {$globalTable}: " . $e->getMessage();
            $this->log($err);
            $this->summary['errors'][] = $err;
            return ['sourceRows' => $sourceRows, 'writtenRows' => 0, 'skipped' => $sourceRows];
        }
    }

    private function getColumns(string $table): array
    {
        $stmt = $this->pdo->prepare("
            SELECT COLUMN_NAME, EXTRA, COLUMN_KEY
            FROM information_schema.columns
            WHERE table_schema = DATABASE() AND table_name = ?
            ORDER BY ORDINAL_POSITION
        ");
        $stmt->execute([$table]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function intersectColumns(array $globalCols, array $legacyCols): array
    {
        $globalNames = [];
        foreach ($globalCols as $col) {
            $name = $col['COLUMN_NAME'];
            // Skip project_id (we add it explicitly)
            if ($name === 'project_id') continue;
            // Skip AUTO_INCREMENT columns (let DB generate)
            if (stripos($col['EXTRA'], 'auto_increment') !== false) continue;
            $globalNames[$name] = true;
        }

        $common = [];
        foreach ($legacyCols as $col) {
            $name = $col['COLUMN_NAME'];
            if (isset($globalNames[$name])) {
                $common[] = $name;
            }
        }
        return $common;
    }

    private function log(string $msg): void
    {
        echo $msg . PHP_EOL;
    }
}

// Main
$apply = in_array('--apply', $argv, true);

// Bootstrap env
$dotenv = __DIR__ . '/../../.env';
if (file_exists($dotenv)) {
    foreach (file($dotenv, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (!strpos($line, '=')) continue;
        [$k, $v] = explode('=', $line, 2);
        $v = trim($v, " \t\n\r\0\x0B\"'");
        putenv("$k=$v");
        $_ENV[$k] = $v;
    }
}

$dbHost = $_ENV['DB_HOST'] ?? 'db';
$dbPort = $_ENV['DB_PORT'] ?? '3306';
$dbName = $_ENV['DB_NAME'] ?? 'lastplanneraia_dev';
$dbUser = $_ENV['DB_USER'] ?? 'root';
$dbPass = $_ENV['DB_PASS'] ?? '';

$pdo = new PDO(
    "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4",
    $dbUser,
    $dbPass,
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]
);
$pdo->exec("SET time_zone = '-05:00'");

$migrator = new LegacyToGlobalLocalMigrator($pdo, $apply);
$summary = $migrator->run();

if (!empty($summary['errors'])) {
    echo PHP_EOL . '=== Errors ===' . PHP_EOL;
    foreach ($summary['errors'] as $err) {
        echo $err . PHP_EOL;
    }
    exit(1);
}

exit(0);
