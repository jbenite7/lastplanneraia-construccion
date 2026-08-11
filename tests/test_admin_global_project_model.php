<?php
// @requiere: db


require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';
require_once __DIR__ . '/../src/Core/TableResolver.php';
require_once __DIR__ . '/../admin/src/Models/Project.php';

$db = Database::getInstance();
$model = new Admin\Models\Project($db);
$failed = 0;

function okAdmin(bool $condition, string $message): void
{
    global $failed;
    echo ($condition ? '  PASS: ' : '  FAIL: ') . $message . PHP_EOL;
    if (!$condition) {
        $failed++;
    }
}

function tableExistsAdmin(Database $db, string $table): bool
{
    return (int) $db->query(
        'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
        [$table]
    )->fetchColumn() > 0;
}

echo "=== Admin global project model ===" . PHP_EOL;

$suffix = substr(str_shuffle('abcdefghijklmnopqrstuvwxyz'), 0, 8);
$projectName = "E2E Admin Global {$suffix}";

$created = $model->create([
    'nombre' => $projectName,
    'area' => 'Pre-Construccion',
    'activo' => 1,
    'acceso' => 1,
    'pdc_activo' => 0,
    'fecha_inicio_lb' => '2026-07-01',
    'fecha_fin_lb' => '2026-07-31',
    'costo_retraso' => 8000000,
    'url_cambios' => null,
    'pc_restr_2_nombre' => 'Permisos',
    'pc_restr_3_nombre' => 'Disenos',
    'pc_restr_4_nombre' => 'Presupuesto',
]);
okAdmin((bool) $created, 'admin creates project metadata');

$project = $db->query(
    'SELECT Id, Base_de_Datos FROM general_proyectos_procesos WHERE Proyecto_Proceso = ? LIMIT 1',
    [$projectName]
)->fetch(PDO::FETCH_ASSOC);
okAdmin((bool) $project, 'created project can be found');

$projectId = (int) ($project['Id'] ?? 0);
$prefix = (string) ($project['Base_de_Datos'] ?? '');
okAdmin(str_ends_with($prefix, '_pc'), 'pre-construction prefix keeps compatibility suffix');
okAdmin(!tableExistsAdmin($db, "{$prefix}_programa"), 'admin does not create legacy prefixed tables');

$weeks = $projectId > 0
    ? (int) $db->query('SELECT COUNT(*) FROM semanas_activas WHERE project_id = ?', [$projectId])->fetchColumn()
    : 0;
okAdmin($weeks === 1, 'admin creates initial global week');

$dump = $projectId > 0 ? $model->exportToSql($projectId) : false;
okAdmin(is_string($dump) && str_contains($dump, 'INSERT INTO `semanas_activas`'), 'backup exports global project rows');
okAdmin(is_string($dump) && !str_contains($dump, "{$prefix}_programa"), 'backup does not export legacy prefixed tables');

if ($projectId > 0) {
    okAdmin((bool) $model->delete($projectId), 'admin deletes project metadata and global rows');
}

$remainingProject = (int) $db->query(
    'SELECT COUNT(*) FROM general_proyectos_procesos WHERE Id = ?',
    [$projectId]
)->fetchColumn();
$remainingWeeks = (int) $db->query(
    'SELECT COUNT(*) FROM semanas_activas WHERE project_id = ?',
    [$projectId]
)->fetchColumn();
okAdmin($remainingProject === 0, 'project metadata removed');
okAdmin($remainingWeeks === 0, 'global rows removed');

echo $failed === 0 ? "=== Admin global project model: OK ===\n" : "=== Admin global project model: FAIL ===\n";
exit($failed > 0 ? 1 : 0);
