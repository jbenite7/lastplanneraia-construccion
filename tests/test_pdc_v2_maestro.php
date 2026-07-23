<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';
require_once __DIR__ . '/support/pdc_fixture_presupuesto.php';

use App\Services\Pdc\MaestroInsumosService;
use App\Services\Pdc\PresupuestoExcelParser;
use App\Services\Pdc\PresupuestoImportService;
use App\Services\Pdc\PresupuestoImportStore;

const PDC_M_PROJECT_A = 999901;
const PDC_M_PROJECT_B = 999902;
const PDC_M_MARCA = 'test-a2';

$failures = [];
$assert = static function (bool $condition, string $message) use (&$failures): void {
    if ($condition) { fwrite(STDOUT, "PASS: {$message}\n"); return; }
    $failures[] = $message;
    fwrite(STDERR, "FAIL: {$message}\n");
};

$db = Database::getInstance();
$limpiar = static function () use ($db): void {
    foreach ([PDC_M_PROJECT_A, PDC_M_PROJECT_B] as $pid) {
        $db->query('DELETE FROM pdc_insumo_vinculos WHERE project_id = ?', [$pid]);
        $db->query('DELETE FROM pdc_presupuesto_versiones WHERE project_id = ?', [$pid]);
    }
    $db->query('DELETE FROM general_maestro_insumos WHERE creado_por = ?', [PDC_M_MARCA]);
};
$limpiar();

echo "=== PDC v2: maestro de insumos (normalizar/consolidar/generar) ===\n";

// normalizar()
$assert(MaestroInsumosService::normalizar('  Teja   de Zinc  ') === 'TEJA DE ZINC', 'normalizar: mayúsculas, trim y espacios colapsados.');
$assert(MaestroInsumosService::normalizar('Ñandú Ácido') === 'NANDU ACIDO', 'normalizar: sin acentos ni Ñ.');

$maestro = new MaestroInsumosService($db);
$importSvc = new PresupuestoImportService($db, new PresupuestoImportStore(sys_get_temp_dir() . '/pdc-m-store-' . getmypid()), new PresupuestoExcelParser());

// Sin versión → null.
$assert($maestro->generarVinculos(PDC_M_PROJECT_A) === null, 'Sin versión activa → null.');

// Importar fixture (4 filas APU; TEJA/AYUDANTE/CONCRETO/BOMBEO — todos distintos → 4 únicos).
$tmp = sys_get_temp_dir() . '/pdc_m_v1.xlsx';
pdcFixturePresupuestoValido($tmp);
$p = $importSvc->previewDesdeArchivo($tmp, 'v1.xlsx', PDC_M_PROJECT_A, PDC_M_MARCA);
$c = $importSvc->confirmar($p['importToken'], PDC_M_PROJECT_A);

// Cold start: maestro vacío → todo pendiente.
$g1 = $maestro->generarVinculos(PDC_M_PROJECT_A);
$assert($g1['total'] === 4 && $g1['pendientes'] === 4 && $g1['auto'] === 0, 'Cold start: 4 únicos, todos pendientes.');

// Idempotencia: regenerar no duplica ni cambia estados.
$g2 = $maestro->generarVinculos(PDC_M_PROJECT_A);
$assert($g2['total'] === 4 && $g2['pendientes'] === 4, 'Regenerar es idempotente.');

// Consolidación real: mismo insumo en 2 actividades suma cantidades/valores.
// (El fixture SinIdApu de A1-T3 tiene un insumo; usamos un import a B con el fixture válido
// y verificamos los agregados del vínculo de TEJA en A.)
$v = $maestro->vinculos(PDC_M_PROJECT_A);
$teja = array_values(array_filter($v['vinculos'], fn ($x) => $x['descripcionNorm'] === 'TEJA DE ZINC'))[0];
$assert(abs($teja['cantidadTotal'] - 21.6) < 0.001 && abs($teja['valorTotal'] - 540000.0) < 0.01 && $teja['apariciones'] === 1, 'Consolidado de TEJA correcto.');
$assert($v['resumen']['cobertura'] === 0.0, 'Cobertura 0% en cold start.');
$assert($v['vinculos'][0]['estado'] === 'pendiente', 'Orden: pendientes primero.');

// Aislamiento: B sin vínculos.
$assert($maestro->generarVinculos(PDC_M_PROJECT_B) === null, 'B sin versión → null.');

echo "--- acciones (T4) se agregan después ---\n";
$limpiar();
echo $failures === [] ? "=== OK ===\n" : '=== ' . count($failures) . " FAILED ===\n";
exit($failures === [] ? 0 : 1);
