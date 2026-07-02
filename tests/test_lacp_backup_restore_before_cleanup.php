<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';

$failed = 0;
$projectId = 987657;
$prefix = 'lacp_backup_test';
$backupDir = dirname(__DIR__) . '/docs/qa/evidence/catalog-goal-audit-20260702/backup-restore-smoke';
$backupFile = $backupDir . '/lacp-backup-restore-smoke.sql';
$checksumFile = $backupFile . '.sha256';

function lbrPass(string $message): void
{
    echo "  PASS: {$message}\n";
}

function lbrFail(string $message): void
{
    global $failed;
    echo "  FAIL: {$message}\n";
    $failed++;
}

function lbrCleanup(Database $db, int $projectId, string $prefix): void
{
    foreach ([
        'pdc',
        'actividad_programa_fuentes',
        'actividades',
        'programa_consolidado',
        'programa',
        'semanas_activas',
    ] as $table) {
        $db->query("DELETE FROM {$table} WHERE project_id = ?", [$projectId]);
    }
    $db->query('DELETE FROM general_proyectos_procesos WHERE Id = ? OR Base_de_Datos = ?', [$projectId, $prefix]);
}

function lbrSeed(Database $db, int $projectId, string $prefix): void
{
    $db->query(
        "INSERT INTO general_proyectos_procesos (Id, Proyecto_Proceso, Base_de_Datos, Area, Activo, Acceso, pdcActivo)
         VALUES (?, 'LACP Backup Restore Test', ?, 'Construccion', 1, 1, 1)",
        [$projectId, $prefix],
    );
    $db->query(
        "INSERT INTO semanas_activas (project_id, Id, Semana, Fecha_Inicio_Sem, Fecha_Fin_Sem)
         VALUES (?, 1, 1, '2030-03-01', '2030-03-07')",
        [$projectId],
    );
    $db->query(
        "INSERT INTO programa
         (project_id, unique_id, Consecutivo, Id, Actividad, Titulo, Fecha_Inicio, Fecha_Fin)
         VALUES (?, 1101, 1101, '1.1', 'Actividad backup base', 0, '2030-03-10', '2030-03-12')",
        [$projectId],
    );
    $db->query(
        "INSERT INTO programa_consolidado
         (project_id, row_id, Consecutivo, Semana, unique_id, Consecutivo_en_Programa, Id, Actividad, Titulo, Fecha_Inicio, Fecha_Fin, Activa)
         VALUES (?, 1, 1, 1, 1101, 1101, '1.1', 'Actividad backup base', 0, '2030-03-10', '2030-03-12', 1)",
        [$projectId],
    );
    $db->query(
        "INSERT INTO actividades
         (project_id, Id, codigo, actividad, descripcionActividad, actividadInicio, nombreActividadInicio, fechaInicio,
          tipoContrato, semanaActualizacion, SI1, paqueteSI1, numeroSubcontratos)
         VALUES (?, 1, 1, 'E2E Backup Actividad', 'Actividad para prueba de backup.', 1101,
                 'Actividad backup base', '2030-03-10', 'SI', 1, NULL, 'E2E BACKUP SI', 1)",
        [$projectId],
    );
    $db->query(
        "INSERT INTO actividad_programa_fuentes
         (project_id, actividad_id, semana, programa_unique_id, source_activity, source_start_date, context,
          location_hint, intervention_hint, family_name, match_rule, confidence, risk_flags)
         VALUES (?, 1, 1, 1101, 'Actividad backup base', '2030-03-10', 'Capitulo backup',
                 'Zona backup', 'Intervencion backup', 'Familia backup', 'smoke', 90, JSON_ARRAY())",
        [$projectId],
    );
    $db->query(
        "INSERT INTO pdc
         (project_id, consecutivo, semana, titulo, tipoPaquete, paqueteContratacion, contratos, numeroSubcontratos,
          subcontratoPaquete, estado, fechaElaboracionPliegos, diasElaboracionPliegos, fechaInicio)
         VALUES (?, 1, 1, 0, 'Suministro e Instalación', 'E2E BACKUP SI', 'E2E Backup Actividad', 1,
                 1, 'Proceso de contratación no iniciado', '2030-02-20', 2, '2030-03-10')",
        [$projectId],
    );
}

function lbrProjectFingerprint(Database $db, int $projectId, string $prefix): string
{
    $parts = [];
    foreach (lbrBackupTables() as $table => $where) {
        $param = $table === 'general_proyectos_procesos' ? $prefix : $projectId;
        $rows = $db->query("SELECT * FROM {$table} WHERE {$where} ORDER BY 1", [$param])->fetchAll(PDO::FETCH_ASSOC);
        $rows = array_map('lbrStableRow', $rows);
        $parts[$table] = $rows;
    }

    return hash('sha256', json_encode($parts, JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION));
}

function lbrStableRow(array $row): array
{
    unset($row['created_at'], $row['updated_at']);

    return $row;
}

function lbrCounts(Database $db, int $projectId, string $prefix): array
{
    $counts = [];
    foreach (lbrBackupTables() as $table => $where) {
        $param = $table === 'general_proyectos_procesos' ? $prefix : $projectId;
        $counts[$table] = (int) $db->query("SELECT COUNT(*) FROM {$table} WHERE {$where}", [$param])->fetchColumn();
    }

    return $counts;
}

function lbrBackupTables(): array
{
    return [
        'general_proyectos_procesos' => 'Base_de_Datos = ?',
        'semanas_activas' => 'project_id = ?',
        'programa' => 'project_id = ?',
        'programa_consolidado' => 'project_id = ?',
        'actividades' => 'project_id = ?',
        'actividad_programa_fuentes' => 'project_id = ?',
        'pdc' => 'project_id = ?',
    ];
}

function lbrWriteBackup(Database $db, int $projectId, string $prefix, string $path): void
{
    $sql = [
        '-- LACP backup/restore smoke evidence',
        'SET FOREIGN_KEY_CHECKS=0;',
    ];
    foreach (lbrBackupTables() as $table => $where) {
        $param = $table === 'general_proyectos_procesos' ? $prefix : $projectId;
        $rows = $db->query("SELECT * FROM {$table} WHERE {$where} ORDER BY 1", [$param])->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
            unset($row['created_at'], $row['updated_at']);
            $columns = array_keys($row);
            $values = array_map(
                static fn($value): string => $value === null ? 'NULL' : $db->quote((string) $value),
                array_values($row),
            );
            $sql[] = sprintf(
                'INSERT INTO `%s` (`%s`) VALUES (%s);',
                $table,
                implode('`, `', $columns),
                implode(', ', $values),
            );
        }
    }
    $sql[] = 'SET FOREIGN_KEY_CHECKS=1;';
    $sql[] = '';

    if (!is_dir(dirname($path))) {
        mkdir(dirname($path), 0775, true);
    }
    file_put_contents($path, implode("\n", $sql));
}

function lbrRestoreBackup(Database $db, string $path): void
{
    $sql = file_get_contents($path);
    if ($sql === false) {
        throw new RuntimeException('No se pudo leer el backup de prueba.');
    }
    foreach (preg_split('/;\s*(?:\r?\n|$)/', $sql) ?: [] as $statement) {
        $statement = trim($statement);
        if ($statement === '' || str_starts_with($statement, '--')) {
            continue;
        }
        $db->query($statement);
    }
}

echo "=== LACP backup restore before cleanup ===\n";

try {
    $db = Database::getInstance();
    lbrCleanup($db, $projectId, $prefix);
    lbrSeed($db, $projectId, $prefix);

    $beforeCounts = lbrCounts($db, $projectId, $prefix);
    $beforeFingerprint = lbrProjectFingerprint($db, $projectId, $prefix);
    lbrWriteBackup($db, $projectId, $prefix, $backupFile);
    file_put_contents($checksumFile, hash_file('sha256', $backupFile) . '  ' . basename($backupFile) . "\n");

    is_file($backupFile) && filesize($backupFile) > 0
        ? lbrPass('backup externo local creado')
        : lbrFail('backup externo local no fue creado');
    is_file($checksumFile) && trim((string) file_get_contents($checksumFile)) !== ''
        ? lbrPass('checksum del backup creado')
        : lbrFail('checksum del backup no fue creado');

    lbrCleanup($db, $projectId, $prefix);
    array_sum(lbrCounts($db, $projectId, $prefix)) === 0
        ? lbrPass('limpieza temporal deja el proyecto sin filas')
        : lbrFail('limpieza temporal dejó filas antes de restaurar');

    lbrRestoreBackup($db, $backupFile);
    $afterCounts = lbrCounts($db, $projectId, $prefix);
    $afterFingerprint = lbrProjectFingerprint($db, $projectId, $prefix);

    $afterCounts === $beforeCounts
        ? lbrPass('restore conserva conteos por tabla')
        : lbrFail('restore no conserva conteos: ' . json_encode(['before' => $beforeCounts, 'after' => $afterCounts]));
    $afterFingerprint === $beforeFingerprint
        ? lbrPass('restore conserva huella de datos')
        : lbrFail('restore no conserva huella de datos');
} catch (Throwable $e) {
    lbrFail($e->getMessage());
} finally {
    if (isset($db)) {
        lbrCleanup($db, $projectId, $prefix);
    }
}

echo "=== LACP backup restore before cleanup: {$failed} failed ===\n";
exit($failed === 0 ? 0 : 1);
