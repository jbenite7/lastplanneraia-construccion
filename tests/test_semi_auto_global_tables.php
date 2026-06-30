<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';

$db = Database::getInstance();
$failed = 0;

function ok(string $message): void
{
    echo "  PASS: {$message}\n";
}

function bad(string $message): void
{
    global $failed;
    echo "  FAIL: {$message}\n";
    $failed++;
}

echo "=== Semi-auto global tables ===\n";

$requiredTables = [
    'semi_auto_runs',
    'semi_auto_suggestions',
    'semi_auto_decisions',
    'semi_auto_feedback',
    'semi_auto_project_config',
    'general_pdc_chapter_category_map',
];

foreach ($requiredTables as $table) {
    $exists = (int) $db->query(
        "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?",
        [$table],
    )->fetchColumn();
    $exists === 1 ? ok("{$table} exists") : bad("{$table} is missing");
}

foreach (array_slice($requiredTables, 0, 5) as $table) {
    $hasProjectId = (int) $db->query(
        "SELECT COUNT(*) FROM information_schema.columns
         WHERE table_schema = DATABASE() AND table_name = ? AND column_name = 'project_id'",
        [$table],
    )->fetchColumn();
    $hasProjectId === 1 ? ok("{$table}.project_id exists") : bad("{$table}.project_id is missing");
}

$strategyProject = (int) $db->query(
    "SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = DATABASE()
       AND table_name = 'general_pdc_project_family_strategy'
       AND column_name = 'project_id'",
)->fetchColumn();
$strategyProject === 1
    ? ok('general_pdc_project_family_strategy.project_id exists')
    : bad('general_pdc_project_family_strategy.project_id is missing');

$mapRows = (int) $db->query(
    "SELECT COUNT(*) FROM general_pdc_chapter_category_map WHERE activa = 1",
)->fetchColumn();
$mapRows > 0 ? ok('chapter category map has active rows') : bad('chapter category map has no active rows');

$permissions = $db->query(
    "SELECT role_code, permission_key
     FROM rbac_role_permissions
     WHERE role_code IN ('A', 'D', 'R', 'OT')
       AND permission_key IN (
         'lps.listado_actividades.editar',
         'lps.contratos.auto_definir',
         'lps.pdc.auto_generar'
       )
       AND allowed = 1",
)->fetchAll(PDO::FETCH_ASSOC);
$grants = [];
foreach ($permissions as $row) {
    $grants[$row['role_code'] . ':' . $row['permission_key']] = true;
}

foreach (['A', 'D', 'R', 'OT'] as $role) {
    foreach (['lps.listado_actividades.editar', 'lps.contratos.auto_definir', 'lps.pdc.auto_generar'] as $permission) {
        isset($grants[$role . ':' . $permission])
            ? ok("{$role} can {$permission}")
            : bad("{$role} cannot {$permission}");
    }
}

echo $failed === 0 ? "=== Semi-auto global tables: OK ===\n" : "=== Semi-auto global tables: FAIL ===\n";
exit($failed === 0 ? 0 : 1);
