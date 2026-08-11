<?php
// tests/test_pdc_v2_maestro_sinco_parser.php

declare(strict_types=1);
// @requiere: puro


require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/support/pdc_fixture_maestro_sinco.php';

use App\Services\Pdc\MaestroSincoParser;

$failures = [];
$assert = static function (bool $c, string $m) use (&$failures): void {
    if ($c) { fwrite(STDOUT, "PASS: {$m}\n"); return; }
    $failures[] = $m; fwrite(STDERR, "FAIL: {$m}\n");
};

echo "=== PDC v2: parser maestro SINCO ===\n";
$tmp = sys_get_temp_dir();
$ok = $tmp . '/sinco_ok.xlsx';
$bad = $tmp . '/sinco_bad.xlsx';
pdcFixtureMaestroSinco($ok);
pdcFixtureMaestroSincoInvalido($bad);

$parser = new MaestroSincoParser();

// Archivo válido: 5 activos, 1 omitido, sin errores.
$r = $parser->parse($ok);
$assert($r['valido'] === true, 'Fixture válido parsea sin errores.');
$assert($r['resumen']['total'] === 6, 'total = 6 filas.');
$assert($r['resumen']['activos'] === 5 && $r['resumen']['omitidos'] === 1, '5 activos, 1 INACTIVO omitido.');
$assert(count($r['insumos']) === 5, 'insumos solo activos.');
$assert($r['resumen']['agrupaciones'] === 4, '4 agrupaciones distintas entre activos.');
$assert($r['resumen']['tiposRecurso'] === 3, '3 tipos de recurso (MATERIAL/MANO DE OBRA/EQUIPO).');
$piso = array_values(array_filter($r['insumos'], fn ($i) => $i['codigoSinco'] === 'TEST-101'))[0];
$assert($piso['descripcionNorm'] === 'PISO CERAMICO 30X30', 'descripcionNorm normalizada.');
$assert($piso['agrupacion'] === 'MAT-ACABADOS' && $piso['tipoInsumo'] === 'MAT-ACABADOS', 'tipoInsumo = agrupacion.');
$assert($piso['tipoRecurso'] === 'MATERIAL', 'tipoRecurso viene de Tipo Descripcion.');
$assert(abs($piso['valorUnitario'] - 25000) < 0.001 && abs($piso['iva'] - 19) < 0.001, 'valor e IVA numéricos.');

// Archivo inválido: 3 errores, valido=false.
$b = $parser->parse($bad);
$assert($b['valido'] === false, 'Fixture inválido reporta valido=false.');
$assert(count($b['errores']) >= 3, 'Reporta ≥3 errores (código, unidad, valor).');
$assert($b['errores'][0]['fila'] === 2, 'El primer error apunta a la fila 2 del Excel.');

// Hoja faltante → RuntimeException.
$sinHoja = $tmp . '/sinco_sinhoja.xlsx';
$book = new PhpOffice\PhpSpreadsheet\Spreadsheet();
$book->getActiveSheet()->setTitle('Otra');
(new PhpOffice\PhpSpreadsheet\Writer\Xlsx($book))->save($sinHoja);
try {
    $parser->parse($sinHoja);
    $assert(false, 'Hoja faltante lanza RuntimeException.');
} catch (\RuntimeException $e) {
    $assert(str_contains($e->getMessage(), 'Maestro Insumos'), 'Mensaje claro de hoja faltante.');
}

foreach ([$ok, $bad, $sinHoja] as $f) { @unlink($f); }
echo $failures === [] ? "=== OK ===\n" : '=== ' . count($failures) . " FAILED ===\n";
exit($failures === [] ? 0 : 1);
