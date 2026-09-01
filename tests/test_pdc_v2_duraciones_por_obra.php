<?php
// tests/test_pdc_v2_duraciones_por_obra.php — duraciones por obra: tabla, servicio y resolución.
declare(strict_types=1);
// @requiere: datos-proyecto

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';

$failures = [];
$assert = static function (bool $c, string $m) use (&$failures): void {
    if ($c) { fwrite(STDOUT, "PASS: {$m}\n"); return; }
    $failures[] = $m; fwrite(STDERR, "FAIL: {$m}\n");
};

$db = Database::getInstance();

echo "=== la tabla existe con su clave única ===\n";
$tabla = (int) $db->query(
    'SELECT COUNT(*) FROM information_schema.TABLES
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
    ['pdc_proyecto_duraciones'],
)->fetchColumn();
$assert($tabla === 1, 'Existe la tabla pdc_proyecto_duraciones.');

$unica = (int) $db->query(
    'SELECT COUNT(*) FROM information_schema.STATISTICS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ? AND NON_UNIQUE = 0',
    ['pdc_proyecto_duraciones', 'uq_ppd_obra_ref_col'],
)->fetchColumn();
$assert($unica === 3, 'La clave única cubre las tres columnas (project_id, duracion_ref, columna). Dio ' . $unica);

echo $failures === [] ? "\nOK\n" : "\n" . count($failures) . " fallo(s)\n";
exit($failures === [] ? 0 : 1);
