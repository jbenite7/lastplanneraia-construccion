<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../src/Core/Database.php';

$apply = in_array('--apply', $argv, true);
$db = Database::getInstance();

$projects = $db->query(
    "SELECT ID, Base_de_Datos
     FROM general_proyectos_procesos
     WHERE Base_de_Datos IS NOT NULL
       AND CHAR_LENGTH(Base_de_Datos) > 0
     ORDER BY ID"
)->fetchAll(PDO::FETCH_ASSOC);

$summary = [];

function commonColumns(Database $db, string $sourceTable, string $targetTable, array $exclude = []): array
{
    $exclude = array_flip($exclude);
    $sourceColumns = $db->query(
        "SELECT COLUMN_NAME
         FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?
         ORDER BY ORDINAL_POSITION",
        [$sourceTable]
    )->fetchAll(PDO::FETCH_COLUMN);
    $targetColumns = $db->query(
        "SELECT COLUMN_NAME
         FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?
         ORDER BY ORDINAL_POSITION",
        [$targetTable]
    )->fetchAll(PDO::FETCH_COLUMN);

    return array_values(array_filter(
        array_intersect($targetColumns, $sourceColumns),
        static fn($column) => !isset($exclude[$column])
    ));
}

foreach ($projects as $project) {
    $projectId = (int) $project['ID'];
    $prefix = (string) $project['Base_de_Datos'];

    $keyedTables = [
        'pdc' => 'consecutivo',
        'programa_consolidado' => 'Consecutivo',
        'programacion_semanal' => 'Consecutivo',
    ];

    foreach ($keyedTables as $table => $keyColumn) {
        $archive = "zleg_{$prefix}_{$table}";
        if (!$db->tableExists($archive)) {
            continue;
        }

        $columns = commonColumns($db, $archive, $table, ['project_id']);
        $columnList = implode(', ', array_map(static fn($column) => "`{$column}`", $columns));
        $selectList = implode(', ', array_map(static fn($column) => "src.`{$column}`", $columns));
        $sql = "INSERT INTO {$table} (project_id, {$columnList})
                SELECT ?, {$selectList}
                FROM `{$archive}` src
                WHERE NOT EXISTS (
                    SELECT 1 FROM {$table} dst
                    WHERE dst.project_id = ? AND dst.`{$keyColumn}` = src.`{$keyColumn}`
                )";

        if ($apply) {
            $inserted = $db->query($sql, [$projectId, $projectId])->rowCount();
        } else {
            $inserted = (int) $db->query(
                "SELECT COUNT(*)
                 FROM `{$archive}` src
                 WHERE NOT EXISTS (
                     SELECT 1 FROM {$table} dst
                     WHERE dst.project_id = ? AND dst.`{$keyColumn}` = src.`{$keyColumn}`
                 )",
                [$projectId]
            )->fetchColumn();
        }

        if ($inserted > 0) {
            $summary[] = "{$prefix}: {$table} {$inserted}";
        }
    }

    $logArchive = "zleg_{$prefix}_auto_program_log";
    if ($db->tableExists($logArchive)) {
        $sql = "INSERT INTO auto_program_log (project_id, semana, consecutivo, accion, detalle, categoria_cnp, cnp, creado_en)
                SELECT
                    ?,
                    src.semana,
                    src.consecutivo,
                    src.accion,
                    MAX(src.detalle),
                    MAX(src.categoria_cnp),
                    MAX(src.cnp),
                    MIN(src.creado_en)
                FROM `{$logArchive}` src
                WHERE NOT EXISTS (
                    SELECT 1 FROM auto_program_log dst
                    WHERE dst.project_id = ?
                      AND dst.semana = src.semana
                      AND dst.consecutivo = src.consecutivo
                      AND dst.accion = src.accion
                )
                GROUP BY src.semana, src.consecutivo, src.accion";

        if ($apply) {
            $inserted = $db->query($sql, [$projectId, $projectId])->rowCount();
        } else {
            $inserted = (int) $db->query(
                "SELECT COUNT(*) FROM (
                    SELECT src.semana, src.consecutivo, src.accion
                    FROM `{$logArchive}` src
                    WHERE NOT EXISTS (
                        SELECT 1 FROM auto_program_log dst
                        WHERE dst.project_id = ?
                          AND dst.semana = src.semana
                          AND dst.consecutivo = src.consecutivo
                          AND dst.accion = src.accion
                    )
                    GROUP BY src.semana, src.consecutivo, src.accion
                ) pending",
                [$projectId]
            )->fetchColumn();
        }

        if ($inserted > 0) {
            $summary[] = "{$prefix}: auto_program_log {$inserted}";
        }
    }
}

$mode = $apply ? 'APPLY' : 'DRY-RUN';
echo "=== {$mode} zleg backfill ===\n";
if ($summary === []) {
    echo "No hay filas faltantes para backfill.\n";
} else {
    foreach ($summary as $line) {
        echo $line . "\n";
    }
}
