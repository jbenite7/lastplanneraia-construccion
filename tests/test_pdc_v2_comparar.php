<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';
require_once __DIR__ . '/support/pdc_fixture_presupuesto.php';

use App\Services\Pdc\PresupuestoExcelParser;
use App\Services\Pdc\PresupuestoImportService;
use App\Services\Pdc\PresupuestoImportStore;

const PDC_CMP_A = 999901;
const PDC_CMP_B = 999902;

$failures = [];
$assert = static function (bool $c, string $m) use (&$failures): void {
    if ($c) { fwrite(STDOUT, "PASS: {$m}\n"); return; }
    $failures[] = $m; fwrite(STDERR, "FAIL: {$m}\n");
};

$db = Database::getInstance();
$limpiar = static function () use ($db): void {
    foreach ([PDC_CMP_A, PDC_CMP_B] as $pid) {
        $db->query('DELETE FROM pdc_presupuesto_versiones WHERE project_id = ?', [$pid]);
    }
};
$limpiar();

// V2 del mismo presupuesto: TEJA sube de precio, AYUDANTE se elimina, se agrega MALLA a la losa, el resto igual.
$fixtureV2 = static function (string $path): void {
    pdcFixtureEscribir($path, [
        ['01',          'PRELIMINARES',     '',         '',   null, '', 102, 'PI_TEST_2', '',        null, null, null, null,   '',              ''],
        ['01.01',       'CAMPAMENTO',       '01',       '',   null, '', 102, 'PI_TEST_2', '',        null, null, null, null,   '',              ''],
        ['01.01.01',    'INSTALACIONES',    '01.01',    '',   null, '', 102, 'PI_TEST_2', '',        null, null, null, null,   '',              ''],
        ['01.01.01.01', 'CAMPAMENTO 18M2',  '01.01.01', 'M2', 18,   '', 102, 'PI_TEST_2', 'APU-001', null, null, null, null,   '',              ''],
        ['',            'TEJA DE ZINC',     '',         'M2', null, '', 102, 'PI_TEST_2', '',        1.05, 1.2,  19,   30000,  'MAT-CUBIERTAS', ''],
        ['02',          'ESTRUCTURA',       '',         '',   null, '', 102, 'PI_TEST_2', '',        null, null, null, null,   '',              ''],
        ['02.01',       'CONCRETOS',        '02',       '',   null, '', 102, 'PI_TEST_2', '',        null, null, null, null,   '',              ''],
        ['02.01.01',    'LOSAS',            '02.01',    '',   null, '', 102, 'PI_TEST_2', '',        null, null, null, null,   '',              ''],
        ['02.01.01.01', 'LOSA MACIZA E=12', '02.01.01', 'M3', 40,   '', 102, 'PI_TEST_2', 'APU-002', null, null, null, null,   '',              ''],
        ['',            'CONCRETO 4000PSI', '',         'M3', null, '', 102, 'PI_TEST_2', '',        1.0,  1.05, 19,   620000, 'MAT-CONCRETOS', ''],
        ['',            'SERVICIO BOMBEO',  '',         'M3', null, '', 102, 'PI_TEST_2', '',        1.0,  1.0,  null, 28000,  'EQUIPOS',       ''],
        ['',            'MALLA ELECTROSOLDADA', '',     'KG', null, '', 102, 'PI_TEST_2', '',        1.0,  1.0,  19,   6000,   'MAT-ACEROS',    ''],
    ]);
};

echo "=== PDC v2: comparar() de versiones ===\n";
$store = new PresupuestoImportStore(sys_get_temp_dir() . '/pdc-cmp-store-' . getmypid());
$service = new PresupuestoImportService($db, $store, new PresupuestoExcelParser());

// Importar dos versiones en el proyecto A.
$v1 = sys_get_temp_dir() . '/pdc_cmp_v1.xlsx';
$v2 = sys_get_temp_dir() . '/pdc_cmp_v2.xlsx';
pdcFixturePresupuestoValido($v1);
$fixtureV2($v2);
$p1 = $service->previewDesdeArchivo($v1, 'v1.xlsx', PDC_CMP_A, 'tester');
$c1 = $service->confirmar($p1['importToken'], PDC_CMP_A);
$p2 = $service->previewDesdeArchivo($v2, 'v2.xlsx', PDC_CMP_A, 'tester');
$c2 = $service->confirmar($p2['importToken'], PDC_CMP_A);

// Versión inexistente → null.
$assert($service->comparar(PDC_CMP_A, $c1['versionId'], 999999) === null, 'Versión inexistente → null.');
$assert($service->comparar(PDC_CMP_B, $c1['versionId'], $c2['versionId']) === null, 'Aislamiento: proyecto B no compara versiones de A.');

$r = $service->comparar(PDC_CMP_A, $c1['versionId'], $c2['versionId']);
$assert($r !== null, 'Comparación válida devuelve resultado.');

$insPorNorm = [];
foreach ($r['insumos'] as $i) { $insPorNorm[$i['descripcionNorm']] = $i; }

$assert(($insPorNorm['TEJA DE ZINC']['estado'] ?? '') === 'modificado' && $insPorNorm['TEJA DE ZINC']['deltaValor'] > 0, 'TEJA: modificado con sobrecosto (subió el vr. unitario).');
$assert(($insPorNorm['AYUDANTE']['estado'] ?? '') === 'eliminado' && $insPorNorm['AYUDANTE']['deltaValor'] < 0, 'AYUDANTE: eliminado (ahorro).');
$assert(($insPorNorm['AYUDANTE']['valorB'] ?? -1) === 0.0, 'AYUDANTE: valorB = 0 en la versión nueva.');
$assert(($insPorNorm['MALLA ELECTROSOLDADA']['estado'] ?? '') === 'nuevo' && $insPorNorm['MALLA ELECTROSOLDADA']['valorA'] === 0.0, 'MALLA: nuevo (no existía en v1).');
$assert(($insPorNorm['CONCRETO 4000PSI']['estado'] ?? '') === 'igual', 'CONCRETO: igual (sin cambios).');

$assert($r['resumen']['nuevos'] === 1 && $r['resumen']['eliminados'] === 1 && $r['resumen']['modificados'] === 1, 'Resumen: 1 nuevo, 1 eliminado, 1 modificado.');
$assert($r['resumen']['sobrecostos'] > 0 && $r['resumen']['ahorros'] < 0, 'Resumen: hay sobrecostos y ahorros.');
$assert(abs(($r['resumen']['costoB'] - $r['resumen']['costoA']) - $r['resumen']['delta']) < 0.01, 'delta = costoB - costoA.');
$assert(abs(($r['resumen']['sobrecostos'] + $r['resumen']['ahorros']) - $r['resumen']['delta']) < 0.01, 'delta = sobrecostos + ahorros (auto-consistente).');

// Actividades: la losa (donde se agregó MALLA) aparece modificada; el orden lleva jerarquía.
$actPorCodigo = [];
foreach ($r['actividades'] as $a) { $actPorCodigo[$a['codigo']] = $a; }
$assert(($actPorCodigo['02.01.01.01']['estado'] ?? '') === 'modificado', 'Actividad de la losa: modificada (se agregó un insumo).');
$assert(($actPorCodigo['02']['tipoFila'] ?? '') === 'capitulo' && $actPorCodigo['02']['valorB'] > $actPorCodigo['02']['valorA'], 'Capítulo 02 agrega el sobrecosto de sus hijos (roll-up).');
$mags = array_map(static fn ($i) => max($i['valorA'], $i['valorB']), $r['insumos']);
$magsOrden = $mags; rsort($magsOrden);
$assert($mags === $magsOrden, 'Insumos ordenados por magnitud del valor (desc).');

foreach ([$v1, $v2] as $f) { @unlink($f); }
$limpiar();
echo $failures === [] ? "=== OK ===\n" : '=== ' . count($failures) . " FAILED ===\n";
exit($failures === [] ? 0 : 1);
