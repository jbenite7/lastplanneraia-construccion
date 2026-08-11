<?php
// @requiere: db


require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';

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

$keyColumns = [
    'auto_program_log' => ['semana', 'consecutivo', 'accion'],
    'cambios' => ['id'],
    'cic' => ['Id'],
    'cip' => ['Semana', 'profesional'],
    'indicadores_generales' => ['Semana', 'subcontratista_profesional', 'rol'],
    'lps_drawer_comentarios' => ['id'],
    'lps_escalamientos' => ['id'],
    'pg_tracking' => ['consecutivo_en_programa', 'semana'],
    'pi_shared_constraint_links' => ['Id'],
    'pi_shared_constraints' => ['Id'],
    'profesionales' => ['id'],
    'programa' => ['Consecutivo'],
    'programa_consolidado' => ['Consecutivo'],
    'programacion_semanal' => ['Consecutivo'],
    'semanas_activas' => ['Id'],
    'subcontratistas' => ['Id'],
];

function tableExists(PDO $pdo, string $table): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?'
    );
    $stmt->execute([$table]);
    return ((int) $stmt->fetchColumn()) > 0;
}

function tableColumns(PDO $pdo, string $table): array
{
    $stmt = $pdo->prepare(
        'SELECT COLUMN_NAME
         FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?'
    );
    $stmt->execute([$table]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

$projectsStmt = $pdo->query(
    "SELECT ID, Base_de_Datos
     FROM general_proyectos_procesos
     WHERE Base_de_Datos IS NOT NULL
       AND CHAR_LENGTH(Base_de_Datos) > 0
     ORDER BY ID"
);
$projects = $projectsStmt->fetchAll(PDO::FETCH_ASSOC);

$failures = [];
$checked = 0;

foreach ($projects as $project) {
    $projectId = (int) $project['ID'];
    $prefix = (string) $project['Base_de_Datos'];

    foreach ($keyColumns as $table => $columns) {
        $legacy = "{$prefix}_{$table}";
        $archive = "zleg_{$prefix}_{$table}";
        $source = tableExists($pdo, $legacy) ? $legacy : (tableExists($pdo, $archive) ? $archive : null);
        if ($source === null) {
            continue;
        }

        $sourceColumns = array_flip(tableColumns($pdo, $source));
        $missingColumns = array_values(array_filter($columns, static fn(string $column): bool => !isset($sourceColumns[$column])));
        if ($missingColumns !== []) {
            $failures[] = "{$prefix}.{$table}: fuente {$source} sin columnas clave " . implode(',', $missingColumns);
            continue;
        }

        $conditions = ['dst.project_id = ?'];
        foreach ($columns as $column) {
            $conditions[] = "CAST(dst.`{$column}` AS CHAR) <=> CAST(src.`{$column}` AS CHAR)";
        }
        $groupBy = implode(', ', array_map(static fn(string $column): string => "src.`{$column}`", $columns));

        $stmt = $pdo->prepare(
            "SELECT COUNT(*)
             FROM (
                SELECT 1
                FROM `{$source}` src
                WHERE NOT EXISTS (
                    SELECT 1 FROM `{$table}` dst
                    WHERE " . implode(' AND ', $conditions) . "
                )
                GROUP BY {$groupBy}
             ) missing_keys"
        );
        $stmt->execute([$projectId]);
        $missing = (int) $stmt->fetchColumn();
        $checked++;

        if ($missing > 0) {
            $failures[] = "{$prefix}.{$table}: {$missing} claves {$source} sin equivalente global";
        }
    }
}

if ($failures !== []) {
    echo "=== Global Table Reconciliation: FAIL ===\n";
    foreach ($failures as $failure) {
        echo " - {$failure}\n";
    }
    exit(1);
}

echo "=== Global Table Reconciliation: OK ===\n";
echo "Tablas legacy verificadas: {$checked}\n";
echo "No hay claves legacy sin equivalente global por project_id.\n";
