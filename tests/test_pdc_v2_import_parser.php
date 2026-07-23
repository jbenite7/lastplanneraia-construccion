<?php
// tests/test_pdc_v2_import_parser.php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/support/pdc_fixture_presupuesto.php';

use App\Services\Pdc\PresupuestoExcelParser;

$failures = [];
$assert = static function (bool $condition, string $message) use (&$failures): void {
    if ($condition) { fwrite(STDOUT, "PASS: {$message}\n"); return; }
    $failures[] = $message;
    fwrite(STDERR, "FAIL: {$message}\n");
};

echo "=== PDC v2: parser del presupuesto ===\n";
$tmpDir = sys_get_temp_dir();
$valido = $tmpDir . '/pdc_fixture_valido.xlsx';
$invalido = $tmpDir . '/pdc_fixture_invalido.xlsx';
pdcFixturePresupuestoValido($valido);
pdcFixturePresupuestoInvalido($invalido);

$parser = new PresupuestoExcelParser();

// Archivo válido
$r = $parser->parse($valido);
$assert($r['valido'] === true, 'Fixture válido parsea sin errores.');
$assert($r['versionLabel'] === 'PI_TEST_1', 'versionLabel sale de la columna VERSION.');
$assert($r['resumen']['capitulos'] === 2 && $r['resumen']['subcapitulos'] === 2 && $r['resumen']['grupos'] === 2, 'Conteo de jerarquía (2/2/2).');
$assert($r['resumen']['actividades'] === 2 && $r['resumen']['insumos'] === 4, '2 actividades y 4 insumos.');
$assert(count($r['items']) === 8 && count($r['insumos']) === 4, 'Listas items/insumos completas.');
$acts = array_values(array_filter($r['items'], fn ($i) => $i['tipo_fila'] === 'actividad'));
$assert($acts[0]['codigo'] === '01.01.01.01' && $acts[0]['id_apu'] === 'APU-001', 'Actividad detectada por ID APU.');
// cantidad_total = rend × cantidad de actividad: TEJA = 1.2 × 18 = 21.6
$teja = array_values(array_filter($r['insumos'], fn ($i) => $i['descripcion'] === 'TEJA DE ZINC'))[0];
$assert(abs($teja['cantidad_total'] - 21.6) < 0.0001, 'cantidad_total = rendimiento × cantidad de la actividad.');
$assert(abs($teja['valor_total'] - (21.6 * 25000)) < 0.01, 'valor_total = cantidad_total × valor_unitario.');
$assert($teja['codigo_actividad'] === '01.01.01.01', 'Insumo amarrado a su actividad.');
// costoTotal = suma de valor_total de los 4 insumos
$esperado = (1.2 * 18 * 25000) + (0.5 * 18 * 9500) + (1.05 * 40 * 620000) + (1.0 * 40 * 28000);
$assert(abs($r['resumen']['costoTotal'] - $esperado) < 0.01, 'costoTotal es la suma de valor_total.');

// Archivo inválido: 3 errores esperados (huérfano, VrUnit, UM) y valido=false
$b = $parser->parse($invalido);
$assert($b['valido'] === false, 'Fixture inválido reporta valido=false.');
$motivos = array_map(fn ($e) => $e['motivo'], $b['errores']);
$assert(count($b['errores']) >= 3, 'Reporta al menos 3 errores (huérfano, VrUnit no numérico, UM vacía).');
$assert($b['errores'][0]['fila'] === 2, 'El error del insumo huérfano apunta a la fila 2 del Excel.');
// Padre inexistente para 01.01.01.01 (no se importó 01.01 ni 01.01.01)
$assert(count(array_filter($motivos, fn ($m) => str_contains($m, 'padre'))) >= 1, 'Detecta código padre inexistente.');

// Formato real AIA: ID APU vacío, actividad detectada por CANTIDAD numérica (calibración DAPORTO).
$sinIdApu = $tmpDir . '/pdc_fixture_sin_idapu.xlsx';
pdcFixturePresupuestoSinIdApu($sinIdApu);
$s = $parser->parse($sinIdApu);
$assert($s['valido'] === true, 'Fixture sin ID APU parsea sin errores.');
$assert($s['resumen']['actividades'] === 1 && $s['resumen']['insumos'] === 1, 'Actividad detectada por CANTIDAD aunque ID APU esté vacío.');
$actSinIdApu = array_values(array_filter($s['items'], fn ($i) => $i['tipo_fila'] === 'actividad'))[0];
$assert($actSinIdApu['id_apu'] === null, 'id_apu queda null cuando la columna viene vacía.');
$insumoX = $s['insumos'][0];
$assert(abs($insumoX['cantidad_total'] - 10.0) < 0.0001, 'cantidad_total = rendimiento(2.0) × cantidad de la actividad(5) = 10.');
@unlink($sinIdApu);

// Nivel archivo: hoja faltante → RuntimeException
$sinHoja = $tmpDir . '/pdc_fixture_sinhoja.xlsx';
$book = new PhpOffice\PhpSpreadsheet\Spreadsheet();
$book->getActiveSheet()->setTitle('Otra');
(new PhpOffice\PhpSpreadsheet\Writer\Xlsx($book))->save($sinHoja);
try {
    $parser->parse($sinHoja);
    $assert(false, 'Hoja faltante lanza RuntimeException.');
} catch (\RuntimeException $e) {
    $assert(str_contains($e->getMessage(), 'Presupuesto'), 'Hoja faltante lanza RuntimeException con mensaje claro.');
}

foreach ([$valido, $invalido, $sinHoja] as $f) { @unlink($f); }
echo $failures === [] ? "=== OK ===\n" : '=== ' . count($failures) . " FAILED ===\n";
exit($failures === [] ? 0 : 1);
