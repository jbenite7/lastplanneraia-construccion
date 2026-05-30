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
    function disable_productivity_measurement_temporarily($db): array
    {
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

        $queryTables = "SELECT c.TABLE_NAME
                        FROM information_schema.COLUMNS c
                        WHERE c.TABLE_SCHEMA = DATABASE()
                          AND c.COLUMN_NAME = 'medir_productividad'
                          AND (c.TABLE_NAME LIKE ? ESCAPE '\\\\' OR c.TABLE_NAME LIKE ? ESCAPE '\\\\')";

        $stmtTables = $db->query($queryTables, ['%\\_programacion\\_semanal', '%\\_programa\\_consolidado']);
        $tables = $stmtTables->fetchAll(PDO::FETCH_COLUMN);

        foreach ($tables as $tableName) {
            // Defensa adicional: solo nombres seguros para evitar SQL injection en identificadores.
            if (!preg_match('/^[a-zA-Z0-9_]+$/', (string) $tableName)) {
                continue;
            }

            $sqlDisable = "UPDATE `{$tableName}`
                           SET medir_productividad = 0
                           WHERE medir_productividad IS NULL OR medir_productividad <> 0";

            $stmtUpdate = $db->query($sqlDisable);
            $summary['tables'] += 1;
            $summary['rows'] += (int) $stmtUpdate->rowCount();
        }

        return $summary;
    }
}
