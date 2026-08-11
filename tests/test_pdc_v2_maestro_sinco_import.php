<?php
// tests/test_pdc_v2_maestro_sinco_import.php

declare(strict_types=1);
// @requiere: db


require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';
require_once __DIR__ . '/support/pdc_fixture_maestro_sinco.php';

use App\Services\Pdc\MaestroSincoImportService;
use App\Services\Pdc\MaestroSincoParser;
use App\Services\Pdc\PresupuestoImportStore;

$failures = [];
$assert = static function (bool $c, string $m) use (&$failures): void {
    if ($c) { fwrite(STDOUT, "PASS: {$m}\n"); return; }
    $failures[] = $m; fwrite(STDERR, "FAIL: {$m}\n");
};

$db = Database::getInstance();
// Cleanup por marca de test (catálogo global): códigos TEST-% y las filas huérfanas de prueba.
$limpiar = static function () use ($db): void {
    $db->query("DELETE FROM general_maestro_insumos WHERE codigo_sinco LIKE 'TEST-%' OR creado_por = 'test-a25'");
};
$limpiar();

echo "=== PDC v2: import maestro SINCO ===\n";
$storeDir = sys_get_temp_dir() . '/pdc-sinco-store-' . getmypid();
$store = new PresupuestoImportStore($storeDir);
$svc = new MaestroSincoImportService($db, $store, new MaestroSincoParser());

$tmp = sys_get_temp_dir() . '/sinco_import.xlsx';
pdcFixtureMaestroSinco($tmp);

// Preview válido: token + resumen, nada en BD.
$p = $svc->preview($tmp, 'maestro.xlsx', 'test-a25');
$assert($p['ok'] === true && preg_match('/^[a-f0-9]{32}$/', $p['importToken']) === 1, 'Preview ok con token.');
$antes = (int) $db->query("SELECT COUNT(*) FROM general_maestro_insumos WHERE codigo_sinco LIKE 'TEST-%'")->fetchColumn();
$assert($antes === 0, 'Preview NO escribe en BD.');

// Confirmar: 5 insumos creados.
$c = $svc->confirmar($p['importToken']);
$assert($c['ok'] === true && $c['creados'] === 5, 'Confirmar crea 5 insumos.');
$fila = $db->query("SELECT descripcion, agrupacion, tipo_recurso, valor_unitario FROM general_maestro_insumos WHERE codigo_sinco = 'TEST-101'")->fetch(PDO::FETCH_ASSOC);
$assert($fila['agrupacion'] === 'MAT-ACABADOS' && $fila['tipo_recurso'] === 'MATERIAL', 'Columnas SINCO persistidas.');
$assert(abs((float) $fila['valor_unitario'] - 25000) < 0.001, 'valor_unitario persistido.');

// Token de un solo uso.
$c2 = $svc->confirmar($p['importToken']);
$assert($c2['ok'] === false && $c2['code'] === 'TOKEN_EXPIRED', 'Token no reutilizable.');

// Re-import idempotente: 0 creados, 5 actualizados.
$tmp2 = sys_get_temp_dir() . '/sinco_import2.xlsx';
pdcFixtureMaestroSinco($tmp2);
$p2 = $svc->preview($tmp2, 'maestro.xlsx', 'test-a25');
$c3 = $svc->confirmar($p2['importToken']);
$assert($c3['ok'] === true && $c3['creados'] === 0 && $c3['actualizados'] === 5, 'Re-import no duplica (5 actualizados).');
$totalTest = (int) $db->query("SELECT COUNT(*) FROM general_maestro_insumos WHERE codigo_sinco LIKE 'TEST-%'")->fetchColumn();
$assert($totalTest === 5, 'Sigue habiendo 5 filas de prueba.');

// Enriquecimiento: una fila huérfana de A2 (sin codigo_sinco) con misma norma+unidad se completa.
// Borramos la TEST-101 PRIMERO (comparte norma+unidad con la huérfana → si no, choca con uq_gmi_norm_unidad).
$db->query("DELETE FROM general_maestro_insumos WHERE codigo_sinco = 'TEST-101'");
$db->query(
    "INSERT INTO general_maestro_insumos (descripcion, descripcion_norm, unidad, tipo_insumo, activo, creado_por, created_at)
     VALUES ('Piso ceramico 30x30', 'PISO CERAMICO 30X30', 'M2', 'MAT-ACABADOS', 1, 'test-a25', NOW())",
);
$tmp3 = sys_get_temp_dir() . '/sinco_import3.xlsx';
pdcFixtureMaestroSinco($tmp3);
$p3 = $svc->preview($tmp3, 'maestro.xlsx', 'test-a25');
$c4 = $svc->confirmar($p3['importToken']);
$assert(($c4['enriquecidos'] ?? 0) >= 1, 'Fila huérfana por norma+unidad se enriquece con el código SINCO.');
$enr = $db->query("SELECT codigo_sinco FROM general_maestro_insumos WHERE descripcion_norm = 'PISO CERAMICO 30X30' AND unidad = 'M2'")->fetchColumn();
$assert($enr === 'TEST-101', 'La fila huérfana quedó con codigo_sinco = TEST-101.');

// Conflicto: otra fila (con OTRO codigo_sinco) ya ocupa la norma+unidad de TEST-102.
$db->query("DELETE FROM general_maestro_insumos WHERE codigo_sinco IN ('TEST-102', 'TEST-901')");
$db->query(
    "INSERT INTO general_maestro_insumos (codigo_sinco, descripcion, descripcion_norm, unidad, tipo_insumo, activo, creado_por, created_at)
     VALUES ('TEST-901', 'Piso porcelanato 60x60', 'PISO PORCELANATO 60X60', 'M2', 'MAT-ACABADOS', 1, 'test-a25', NOW())",
);
$tmp4 = sys_get_temp_dir() . '/sinco_import4.xlsx';
pdcFixtureMaestroSinco($tmp4);
$p4 = $svc->preview($tmp4, 'maestro.xlsx', 'test-a25');
$c5 = $svc->confirmar($p4['importToken']);
$assert(count($c5['conflictos']) === 1, 'Conflicto reportado: TEST-102 choca con TEST-901.');
$assert($c5['conflictos'][0]['codigoSinco'] === 'TEST-102' && $c5['conflictos'][0]['chocaCon'] === 'TEST-901', 'Detalle del conflicto correcto.');
$sigue = $db->query("SELECT codigo_sinco FROM general_maestro_insumos WHERE descripcion_norm = 'PISO PORCELANATO 60X60' AND unidad = 'M2'")->fetchColumn();
$assert($sigue === 'TEST-901', 'La fila existente NO fue pisada.');

foreach ([$tmp, $tmp2, $tmp3, $tmp4] as $f) { @unlink($f); }
$limpiar();
echo $failures === [] ? "=== OK ===\n" : '=== ' . count($failures) . " FAILED ===\n";
exit($failures === [] ? 0 : 1);
