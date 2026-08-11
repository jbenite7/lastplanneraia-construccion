<?php
// @requiere: datos-proyecto


require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$migration = __DIR__ . '/../database/migrations/20260703_program_unique_id_refactor.php';
passthru('php ' . escapeshellarg($migration) . ' --apply', $code);
if ($code !== 0) {
    echo "=== Program Unique ID Refactor: FAIL ===\n";
    echo " - No se pudo aplicar la migración unique_id.\n";
    exit(1);
}

$db = Database::getInstance();
$ref = new ReflectionClass($db);
$prop = $ref->getProperty('pdo');
$prop->setAccessible(true);
$pdo = $prop->getValue($db);

function failUniqueId(string $message): void
{
    echo "=== Program Unique ID Refactor: FAIL ===\n";
    echo " - {$message}\n";
    exit(1);
}

function assertColumn(PDO $pdo, string $table, string $column): void
{
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?');
    $stmt->execute([$table, $column]);
    if ((int) $stmt->fetchColumn() !== 1) {
        failUniqueId("Falta columna {$table}.{$column}");
    }
}

foreach ([
    ['programa', 'unique_id'],
    ['programa_consolidado', 'row_id'],
    ['programa_consolidado', 'unique_id'],
    ['programacion_semanal', 'row_id'],
    ['programacion_semanal', 'unique_id'],
    ['lps_drawer_comentarios', 'unique_id'],
    ['lps_escalamientos', 'unique_id'],
    ['pg_tracking', 'unique_id'],
    ['pi_shared_constraint_links', 'unique_id'],
    ['auto_program_log', 'unique_id'],
] as [$table, $column]) {
    assertColumn($pdo, $table, $column);
}

$checks = [
    'programa' => 'SELECT COUNT(*) FROM programa WHERE unique_id IS NULL OR unique_id <> Consecutivo',
    'programa_consolidado' => 'SELECT COUNT(*) FROM programa_consolidado WHERE row_id IS NULL OR row_id <> Consecutivo OR unique_id IS NULL OR unique_id <> Consecutivo_en_Programa',
    'programacion_semanal' => 'SELECT COUNT(*) FROM programacion_semanal WHERE row_id IS NULL OR row_id <> Consecutivo OR unique_id IS NULL OR unique_id <> Consecutivo_En_Programa',
    'lps_drawer_comentarios' => 'SELECT COUNT(*) FROM lps_drawer_comentarios WHERE unique_id IS NULL OR unique_id <> consecutivo_en_programa',
    'lps_escalamientos' => 'SELECT COUNT(*) FROM lps_escalamientos WHERE unique_id IS NULL OR unique_id <> consecutivo_en_programa',
    'pg_tracking' => 'SELECT COUNT(*) FROM pg_tracking WHERE unique_id IS NULL OR unique_id <> consecutivo_en_programa',
    'pi_shared_constraint_links' => 'SELECT COUNT(*) FROM pi_shared_constraint_links WHERE unique_id IS NULL OR unique_id <> ConsecutivoEnPrograma',
    'auto_program_log' => 'SELECT COUNT(*) FROM auto_program_log l WHERE (l.unique_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM programa p WHERE p.project_id <=> l.project_id AND p.unique_id = l.unique_id)) OR (l.consecutivo > 0 AND EXISTS (SELECT 1 FROM programa p WHERE p.project_id <=> l.project_id AND p.unique_id = l.consecutivo) AND (l.unique_id IS NULL OR l.unique_id <> l.consecutivo)) OR (l.consecutivo <= 0 AND l.unique_id IS NOT NULL)',
];

foreach ($checks as $table => $sql) {
    $bad = (int) $pdo->query($sql)->fetchColumn();
    if ($bad !== 0) {
        failUniqueId("Backfill inconsistente en {$table}: {$bad} filas");
    }
}

$orphanSql = [
    'programa_consolidado' => 'SELECT COUNT(*) FROM programa_consolidado c LEFT JOIN programa p ON p.project_id = c.project_id AND p.unique_id = c.unique_id WHERE p.unique_id IS NULL',
    'programacion_semanal' => 'SELECT COUNT(*) FROM programacion_semanal s LEFT JOIN programa p ON p.project_id = s.project_id AND p.unique_id = s.unique_id WHERE p.unique_id IS NULL',
    'lps_drawer_comentarios' => 'SELECT COUNT(*) FROM lps_drawer_comentarios c LEFT JOIN programa p ON p.project_id = c.project_id AND p.unique_id = c.unique_id WHERE p.unique_id IS NULL',
    'lps_escalamientos' => 'SELECT COUNT(*) FROM lps_escalamientos e LEFT JOIN programa p ON p.project_id = e.project_id AND p.unique_id = e.unique_id WHERE p.unique_id IS NULL',
    'pg_tracking' => 'SELECT COUNT(*) FROM pg_tracking t LEFT JOIN programa p ON p.project_id = t.project_id AND p.unique_id = t.unique_id WHERE p.unique_id IS NULL',
    'pi_shared_constraint_links' => 'SELECT COUNT(*) FROM pi_shared_constraint_links l LEFT JOIN programa p ON p.project_id = l.project_id AND p.unique_id = l.unique_id WHERE p.unique_id IS NULL',
    'auto_program_log' => 'SELECT COUNT(*) FROM auto_program_log l LEFT JOIN programa p ON p.project_id = l.project_id AND p.unique_id = l.unique_id WHERE l.unique_id IS NOT NULL AND p.unique_id IS NULL',
];

foreach ($orphanSql as $table => $sql) {
    $orphans = (int) $pdo->query($sql)->fetchColumn();
    if ($orphans !== 0) {
        failUniqueId("Hay {$orphans} huérfanos unique_id en {$table}");
    }
}

$pid = 987654;
$_SESSION['project_id'] = $pid;
$_SESSION['db'] = 'unique_id_test';
$db->setProjectContext($pid);

$pdo->prepare('DELETE FROM programa WHERE project_id = ?')->execute([$pid]);
$pdo->prepare('DELETE FROM program_unique_id_sequences WHERE project_id = ?')->execute([$pid]);
$pdo->prepare('INSERT INTO programa (project_id, unique_id, Consecutivo, Id, Actividad, Titulo, Fecha_Inicio, Fecha_Fin) VALUES (?, ?, ?, ?, ?, 0, ?, ?)')->execute([
    $pid,
    1,
    1,
    '1',
    'Actividad temporal eliminada',
    '2026-01-01',
    '2026-01-02',
]);
$pdo->prepare('INSERT INTO program_unique_id_sequences (project_id, next_unique_id) VALUES (?, ?)')->execute([$pid, 2]);
$pdo->prepare('DELETE FROM programa WHERE project_id = ? AND unique_id = ?')->execute([$pid, 1]);

$db->query(
    'INSERT INTO programa (Id, Actividad, Titulo, Fecha_Inicio, Fecha_Fin) VALUES (?, ?, 0, ?, ?)',
    ['2', 'Actividad temporal nueva', '2026-01-03', '2026-01-04']
);

$row = $pdo->prepare('SELECT unique_id, Consecutivo, Id, Actividad FROM programa WHERE project_id = ? ORDER BY unique_id DESC LIMIT 1');
$row->execute([$pid]);
$created = $row->fetch(PDO::FETCH_ASSOC);
if (!$created || (int) $created['unique_id'] !== 2 || (int) $created['Consecutivo'] !== 2) {
    failUniqueId('La generación automática recicló o desincronizó unique_id/Consecutivo.');
}

$pdo->prepare('UPDATE programa SET Id = ?, Actividad = ? WHERE project_id = ? AND unique_id = ?')->execute(['9.9', 'Actividad renombrada', $pid, 2]);
$afterUpdate = $pdo->prepare('SELECT unique_id FROM programa WHERE project_id = ? AND Id = ?');
$afterUpdate->execute([$pid, '9.9']);
if ((int) $afterUpdate->fetchColumn() !== 2) {
    failUniqueId('Cambiar WBS/nombre modificó la identidad unique_id.');
}

$pdo->prepare('DELETE FROM programa WHERE project_id = ?')->execute([$pid]);
$pdo->prepare('DELETE FROM program_unique_id_sequences WHERE project_id = ?')->execute([$pid]);

echo "=== Program Unique ID Refactor: OK ===\n";
echo "Columnas, backfill, FKs lógicas, no-reciclaje e inmutabilidad verificados.\n";
