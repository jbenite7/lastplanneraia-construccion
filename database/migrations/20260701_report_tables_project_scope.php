<?php

require_once __DIR__ . '/../../src/Core/Database.php';

$db = Database::getInstance();
$ref = new ReflectionClass($db);
$prop = $ref->getProperty('pdo');
$prop->setAccessible(true);
$pdo = $prop->getValue($db);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$apply = in_array('--apply', $argv ?? [], true);

function reportScalar(PDO $pdo, string $sql, array $params = []): int
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return (int) $stmt->fetchColumn();
}

function reportTableExists(PDO $pdo, string $table): bool
{
    return reportScalar($pdo, 'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?', [$table]) > 0;
}

function reportColumnExists(PDO $pdo, string $table, string $column): bool
{
    return reportScalar($pdo, 'SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?', [$table, $column]) > 0;
}

function reportIndexExists(PDO $pdo, string $table, string $index): bool
{
    return reportScalar($pdo, 'SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?', [$table, $index]) > 0;
}

function reportExec(PDO $pdo, string $sql, bool $apply): void
{
    echo ($apply ? 'APPLY ' : 'DRY   ') . $sql . PHP_EOL;
    if ($apply) {
        $pdo->exec($sql);
    }
}

function reportAddColumn(PDO $pdo, string $table, string $column, string $definition, bool $apply): void
{
    if (reportTableExists($pdo, $table) && !reportColumnExists($pdo, $table, $column)) {
        reportExec($pdo, "ALTER TABLE `{$table}` ADD COLUMN {$definition}", $apply);
    }
}

function reportAddIndex(PDO $pdo, string $table, string $index, string $definition, bool $apply): void
{
    if (reportTableExists($pdo, $table) && !reportIndexExists($pdo, $table, $index)) {
        reportExec($pdo, "ALTER TABLE `{$table}` ADD {$definition}", $apply);
    }
}

$tables = [
    'general_curvas',
    'general_curvas_pdc',
    'general_informe_consolidado',
    'general_informe_pdc',
    'general_informe_restricciones_consolidado',
    'general_informe_subcontratistas',
];

foreach ($tables as $table) {
    reportAddColumn($pdo, $table, 'project_id', '`project_id` int DEFAULT NULL AFTER `id`', $apply);
}

reportAddColumn($pdo, 'general_curvas_pdc', 'maxSemana', '`maxSemana` int DEFAULT NULL AFTER `semana`', $apply);

foreach ($tables as $table) {
    if (!reportTableExists($pdo, $table) || !reportColumnExists($pdo, $table, 'project_id')) {
        continue;
    }

    reportExec(
        $pdo,
        "UPDATE `{$table}` r
         JOIN `general_proyectos_procesos` p ON p.`Proyecto_Proceso` = r.`Proyecto`
         SET r.`project_id` = p.`Id`
         WHERE r.`project_id` IS NULL",
        $apply,
    );
}

foreach ([
    ['general_curvas', 'idx_general_curvas_project_week', 'KEY `idx_general_curvas_project_week` (`project_id`, `semana`)'],
    ['general_curvas_pdc', 'idx_general_curvas_pdc_project_week', 'KEY `idx_general_curvas_pdc_project_week` (`project_id`, `semana`)'],
    ['general_informe_consolidado', 'idx_general_informe_consolidado_project_week', 'KEY `idx_general_informe_consolidado_project_week` (`project_id`, `Semana`)'],
    ['general_informe_pdc', 'idx_general_informe_pdc_project_week', 'KEY `idx_general_informe_pdc_project_week` (`project_id`, `semana`)'],
    ['general_informe_restricciones_consolidado', 'idx_general_informe_restricciones_project_week', 'KEY `idx_general_informe_restricciones_project_week` (`project_id`, `Semana`)'],
    ['general_informe_subcontratistas', 'idx_general_informe_sub_project_week', 'KEY `idx_general_informe_sub_project_week` (`project_id`, `Semana`)'],
] as [$table, $index, $definition]) {
    reportAddIndex($pdo, $table, $index, $definition, $apply);
}

echo $apply ? "Migracion de reporterias aplicada.\n" : "Dry-run de migracion de reporterias terminado. Usa --apply para aplicar.\n";
