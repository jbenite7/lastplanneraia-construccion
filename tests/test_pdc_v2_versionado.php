<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';
require_once __DIR__ . '/support/pdc_fixture_presupuesto.php';

use App\Services\Pdc\PresupuestoExcelParser;
use App\Services\Pdc\PresupuestoImportService;
use App\Services\Pdc\PresupuestoImportStore;

const PDC_VER_A = 999901;
const PDC_VER_B = 999902;

$failures = [];
$assert = static function (bool $c, string $m) use (&$failures): void {
    if ($c) { fwrite(STDOUT, "PASS: {$m}\n"); return; }
    $failures[] = $m; fwrite(STDERR, "FAIL: {$m}\n");
};

$db = Database::getInstance();
$limpiar = static function () use ($db): void {
    foreach ([PDC_VER_A, PDC_VER_B] as $pid) {
        $db->query('DELETE FROM pdc_presupuesto_versiones WHERE project_id = ?', [$pid]);
    }
};
$limpiar();

// v2: mismas actividades pero un insumo cambia de precio (contenido distinto → nueva versión).
$fixtureV2 = static function (string $path): void {
    pdcFixtureEscribir($path, [
        ['01','PRELIMINARES','','',null,'',102,'PI_V2','',null,null,null,null,'',''],
        ['01.01','CAMPAMENTO','01','',null,'',102,'PI_V2','',null,null,null,null,'',''],
        ['01.01.01','INSTALACIONES','01.01','',null,'',102,'PI_V2','',null,null,null,null,'',''],
        ['01.01.01.01','CAMPAMENTO 18M2','01.01.01','M2',18,'',102,'PI_V2','APU-001',null,null,null,null,'',''],
        ['','TEJA DE ZINC','','M2',null,'',102,'PI_V2','',1.05,1.2,19,30000,'MAT-CUBIERTAS',''],
        ['','AYUDANTE','','HC',null,'',102,'PI_V2','',8.0,0.5,null,9500,'MANO DE OBRA',''],
        ['02','ESTRUCTURA','','',null,'',102,'PI_V2','',null,null,null,null,'',''],
        ['02.01','CONCRETOS','02','',null,'',102,'PI_V2','',null,null,null,null,'',''],
        ['02.01.01','LOSAS','02.01','',null,'',102,'PI_V2','',null,null,null,null,'',''],
        ['02.01.01.01','LOSA MACIZA E=12','02.01.01','M3',40,'',102,'PI_V2','APU-002',null,null,null,null,'',''],
        ['','CONCRETO 4000PSI','','M3',null,'',102,'PI_V2','',1.0,1.05,19,620000,'MAT-CONCRETOS',''],
        ['','SERVICIO BOMBEO','','M3',null,'',102,'PI_V2','',1.0,1.0,null,28000,'EQUIPOS',''],
    ]);
};

echo "=== PDC v2: versionamiento inteligente ===\n";
$store = new PresupuestoImportStore(sys_get_temp_dir() . '/pdc-ver-store-' . getmypid());
$service = new PresupuestoImportService($db, $store, new PresupuestoExcelParser());

// --- Hash de contenido: estable ante reordenamiento de filas ---
$items = [
    ['codigo' => '02', 'tipo_fila' => 'capitulo', 'unidad' => null, 'cantidad' => null],
    ['codigo' => '01', 'tipo_fila' => 'capitulo', 'unidad' => null, 'cantidad' => null],
];
$itemsRev = array_reverse($items);
$insumos = [
    ['codigo_actividad' => '01.01.01.01', 'descripcion' => 'Teja de Zinc', 'unidad' => 'M2', 'cantidad_total' => 21.6, 'valor_total' => 540000],
    ['codigo_actividad' => '02.01.01.01', 'descripcion' => 'Concreto 4000PSI', 'unidad' => 'M3', 'cantidad_total' => 42, 'valor_total' => 26040000],
];
$insumosRev = array_reverse($insumos);
$assert($service->hashContenido($items, $insumos) === $service->hashContenido($itemsRev, $insumosRev), 'hashContenido estable ante reordenamiento.');
$assert($service->hashContenido($items, $insumos) !== $service->hashContenido($items, array_slice($insumos, 0, 1)), 'hashContenido distingue contenidos distintos.');

// --- Primer cargue → Versión 1 ---
$v1 = sys_get_temp_dir() . '/pdc_ver_v1.xlsx';
pdcFixturePresupuestoValido($v1);
$p1 = $service->previewDesdeArchivo($v1, 'v1.xlsx', PDC_VER_A, 'tester');
$assert($p1['sinCambios'] === false && $p1['versionActiva'] === null, 'Primer preview: sin activa, sin "sin cambios".');
$c1 = $service->confirmar($p1['importToken'], PDC_VER_A);
$assert($c1['ok'] === true && $c1['versionNumero'] === 1 && $c1['versionIdAnterior'] === null && $c1['sinCambios'] === false, 'Confirmar 1 → Versión 1, sin anterior.');

// --- Re-cargue idéntico → sin cambios, NO crea versión ---
$v1b = sys_get_temp_dir() . '/pdc_ver_v1b.xlsx';
pdcFixturePresupuestoValido($v1b);
$p2 = $service->previewDesdeArchivo($v1b, 'v1b.xlsx', PDC_VER_A, 'tester');
$assert($p2['sinCambios'] === true && (int) $p2['versionActiva']['numero'] === 1, 'Preview idéntico avisa "sin cambios" (Versión 1 activa).');
$c2 = $service->confirmar($p2['importToken'], PDC_VER_A);
$assert($c2['ok'] === true && $c2['sinCambios'] === true && $c2['versionId'] === $c1['versionId'], 'Confirmar idéntico NO crea versión (retorna la activa).');
$total = (int) $db->query('SELECT COUNT(*) FROM pdc_presupuesto_versiones WHERE project_id = ?', [PDC_VER_A])->fetchColumn();
$assert($total === 1, 'Sigue habiendo 1 sola versión tras el re-cargue idéntico.');

// --- Cargue con contenido distinto → Versión 2, con anterior ---
$v2 = sys_get_temp_dir() . '/pdc_ver_v2.xlsx';
$fixtureV2($v2);
$p3 = $service->previewDesdeArchivo($v2, 'v2.xlsx', PDC_VER_A, 'tester');
$assert($p3['sinCambios'] === false, 'Preview con contenido distinto: no "sin cambios".');
$c3 = $service->confirmar($p3['importToken'], PDC_VER_A);
$assert($c3['ok'] === true && $c3['versionNumero'] === 2 && $c3['versionIdAnterior'] === $c1['versionId'] && $c3['sinCambios'] === false, 'Confirmar distinto → Versión 2 con versionIdAnterior = V1.');

// --- versiones() incluye versionNumero; aislamiento por proyecto ---
$vers = $service->versiones(PDC_VER_A);
$assert(isset($vers[0]['versionNumero']) && $vers[0]['versionNumero'] === 2, 'versiones() trae versionNumero (la más reciente = 2).');
$assert($service->versiones(PDC_VER_B) === [], 'Aislamiento: proyecto B sin versiones.');

foreach ([$v1, $v1b, $v2] as $f) { @unlink($f); }
$limpiar();
echo $failures === [] ? "=== OK ===\n" : '=== ' . count($failures) . " FAILED ===\n";
exit($failures === [] ? 0 : 1);
