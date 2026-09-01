<?php
// tests/test_pdc_v2_duraciones_por_obra.php — duraciones por obra: tabla, servicio y resolución.
declare(strict_types=1);
// @requiere: datos-proyecto

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';

use App\Services\Pdc\DuracionesObraService;

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

echo "=== el servicio guarda, lee y borra ===\n";
$P = 999906;
$REF = 1;
$svcObra = new DuracionesObraService($db);
$limpiar = static function () use ($db, $P): void {
    $db->query('DELETE FROM pdc_proyecto_duraciones WHERE project_id = ?', [$P]);
};
$limpiar();

$assert($svcObra->deProyecto($P) === [], 'Una obra sin correcciones devuelve un mapa vacío.');

$r = $svcObra->guardar($P, $REF, ['diasFabricacion' => 120], null);
$assert($r['ok'] === true, 'Guardar una corrección válida responde ok.');
$assert($svcObra->deProyecto($P) === [$REF => ['diasFabricacion' => 120]],
    'La corrección se lee indexada por duracionRef y columna.');

$r = $svcObra->guardar($P, $REF, ['diasFabricacion' => 90], null);
$assert($r['ok'] === true && $svcObra->deProyecto($P)[$REF]['diasFabricacion'] === 90,
    'Guardar dos veces la misma columna actualiza en vez de duplicar.');

$r = $svcObra->guardar($P, $REF, ['columnaInventada' => 5], null);
$assert($r['ok'] === false && $r['code'] === 'COLUMNA_INVALIDA',
    'Una columna fuera de la lista blanca se rechaza.');

$r = $svcObra->guardar($P, $REF, ['diasFabricacion' => -1], null);
$assert($r['ok'] === false && $r['code'] === 'DIAS_INVALIDOS',
    'Un número de días negativo se rechaza.');

$assert($svcObra->deProyecto($P)[$REF]['diasFabricacion'] === 90,
    'Un rechazo no deja el dato a medias: sigue valiendo 90.');

$r = $svcObra->borrar($P, $REF, ['diasFabricacion']);
$assert($r['ok'] === true && $svcObra->deProyecto($P) === [],
    'Borrar la corrección devuelve la obra al catálogo de la empresa.');

$limpiar();

echo $failures === [] ? "\nOK\n" : "\n" . count($failures) . " fallo(s)\n";
exit($failures === [] ? 0 : 1);
