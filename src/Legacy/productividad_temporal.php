<?php

/**
 * TEMPORARY FEATURE FLAG (2026-02):
 * Desactiva la medicion de productividad para TODOS los proyectos del esquema actual.
 *
 * Contexto:
 * - Se requiere apagar temporalmente la funcionalidad de "medir_productividad".
 * - Mientras este activo, cualquier valor NULL o 1 se fuerza a 0.
 * - Aplica sobre todas las tablas por proyecto que contienen esa columna,
 *   principalmente *_programacion_semanal y *_programa_consolidado.
 *
 * Retiro futuro:
 * - Eliminar este archivo y sus invocaciones cuando se reactive la funcionalidad.
 */

if (!function_exists('disable_productivity_measurement_temporarily')) {
    function disable_productivity_measurement_temporarily($db, ?int $projectId = null): array
    {
        if ($projectId === null && method_exists($db, 'getCurrentProjectId')) {
            $projectId = $db->getCurrentProjectId();
        }
        static $alreadyRunInRequest = false;

        if ($alreadyRunInRequest) {
            return [
                'status' => 'SKIPPED_ALREADY_RUN',
                'tables' => 0,
                'rows' => 0,
            ];
        }

        $alreadyRunInRequest = true;

        $summary = [
            'status' => 'OK',
            'tables' => 0,
            'rows' => 0,
        ];

        if (method_exists($db, 'isUsingGlobalTables') && $db->isUsingGlobalTables()) {
            $candidateTables = ['programacion_semanal', 'programa_consolidado'];
            $placeholders = implode(',', array_fill(0, count($candidateTables), '?'));
            $queryTables = "SELECT c.TABLE_NAME
                            FROM information_schema.COLUMNS c
                            WHERE c.TABLE_SCHEMA = DATABASE()
                              AND c.COLUMN_NAME = 'medir_productividad'
                              AND c.TABLE_NAME IN ({$placeholders})";
            $stmtTables = $db->query($queryTables, $candidateTables);
        } else {
            $queryTables = "SELECT c.TABLE_NAME
                            FROM information_schema.COLUMNS c
                            WHERE c.TABLE_SCHEMA = DATABASE()
                              AND c.COLUMN_NAME = 'medir_productividad'
                              AND (c.TABLE_NAME LIKE ? ESCAPE '\\\\' OR c.TABLE_NAME LIKE ? ESCAPE '\\\\')";
            $stmtTables = $db->query($queryTables, ['%\\_programacion\\_semanal', '%\\_programa\\_consolidado']);
        }

        $tables = $stmtTables->fetchAll(PDO::FETCH_COLUMN);

        foreach ($tables as $tableName) {
            // Defensa adicional: solo nombres seguros para evitar SQL injection en identificadores.
            if (!preg_match('/^[a-zA-Z0-9_]+$/', (string) $tableName)) {
                continue;
            }

            $where = 'medir_productividad IS NULL OR medir_productividad <> 0';
            $params = [];
            if ($db->isUsingGlobalTables()) {
                if ($projectId === null || $projectId <= 0) {
                    continue;
                }
                $where = "project_id = ? AND ({$where})";
                $params[] = $projectId;
            }
            $sqlDisable = "UPDATE `{$tableName}`
                           SET medir_productividad = 0
                           WHERE {$where}";

            $stmtUpdate = $db->query($sqlDisable, $params);
            $summary['tables'] += 1;
            $summary['rows'] += (int) $stmtUpdate->rowCount();
        }

        return $summary;
    }
}
