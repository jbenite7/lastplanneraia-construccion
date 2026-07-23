<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';
require_once __DIR__ . '/support/pdc_fixture_presupuesto.php';

use App\Services\Pdc\PresupuestoExcelParser;
use App\Services\Pdc\PresupuestoImportService;
use App\Services\Pdc\PresupuestoImportStore;

const PDC_ARBOL_PROJECT_A = 999901;
const PDC_ARBOL_PROJECT_B = 999902;

$failures = [];
$assert = static function (bool $condition, string $message) use (&$failures): void {
    if ($condition) { fwrite(STDOUT, "PASS: {$message}\n"); return; }
    $failures[] = $message;
    fwrite(STDERR, "FAIL: {$message}\n");
};

$db = Database::getInstance();
$limpiar = static function () use ($db): void {
    foreach ([PDC_ARBOL_PROJECT_A, PDC_ARBOL_PROJECT_B] as $pid) {
        $db->query('DELETE FROM pdc_presupuesto_versiones WHERE project_id = ?', [$pid]);
    }
};
$limpiar();

echo "=== PDC v2: arbol() del visor ===\n";
$store = new PresupuestoImportStore(sys_get_temp_dir() . '/pdc-arbol-store-' . getmypid());
$service = new PresupuestoImportService($db, $store, new PresupuestoExcelParser());

// Sin versiones → null.
$assert($service->arbol(PDC_ARBOL_PROJECT_A) === null, 'Proyecto sin versiones → null.');

// Importar el fixture válido (8 items / 4 insumos) y pedir el árbol de la activa.
$tmp = sys_get_temp_dir() . '/pdc_arbol_v1.xlsx';
pdcFixturePresupuestoValido($tmp);
$p1 = $service->previewDesdeArchivo($tmp, 'v1.xlsx', PDC_ARBOL_PROJECT_A, 'tester');
$c1 = $service->confirmar($p1['importToken'], PDC_ARBOL_PROJECT_A);

$a = $service->arbol(PDC_ARBOL_PROJECT_A);
$assert($a !== null && $a['version']['id'] === $c1['versionId'], 'Sin versionId devuelve la versión activa.');
$assert(count($a['items']) === 8 && count($a['insumos']) === 4, 'Árbol con 8 items y 4 insumos.');
$assert($a['items'][0]['codigo'] === '01' && $a['items'][0]['tipoFila'] === 'capitulo', 'Primer item = capítulo 01 (orden del Excel).');
$act = array_values(array_filter($a['items'], fn ($i) => $i['codigo'] === '01.01.01.01'))[0];
$assert($act['tipoFila'] === 'actividad' && abs($act['cantidad'] - 18.0) < 0.001, 'Actividad con cantidad 18.');
$insumosAct = array_values(array_filter($a['insumos'], fn ($i) => $i['itemId'] === $act['id']));
$assert(count($insumosAct) === 2 && $insumosAct[0]['descripcion'] === 'TEJA DE ZINC', 'Insumos amarrados por itemId, en orden.');
$assert(abs($insumosAct[0]['valorTotal'] - 540000.0) < 0.01, 'valorTotal del insumo (21.6 × 25000).');

// Segunda versión → la activa cambia; la histórica sigue consultable por versionId.
$tmp2 = sys_get_temp_dir() . '/pdc_arbol_v2.xlsx';
pdcFixturePresupuestoValido($tmp2);
$p2 = $service->previewDesdeArchivo($tmp2, 'v2.xlsx', PDC_ARBOL_PROJECT_A, 'tester');
$c2 = $service->confirmar($p2['importToken'], PDC_ARBOL_PROJECT_A);
$assert($service->arbol(PDC_ARBOL_PROJECT_A)['version']['id'] === $c2['versionId'], 'La activa es la nueva.');
$hist = $service->arbol(PDC_ARBOL_PROJECT_A, $c1['versionId']);
$assert($hist !== null && $hist['version']['id'] === $c1['versionId'] && (int) $hist['version']['activa'] === 0, 'Versión histórica consultable por id.');

// Aislamiento: B no ve la versión de A ni por id.
$assert($service->arbol(PDC_ARBOL_PROJECT_B) === null, 'Proyecto B sin árbol.');
$assert($service->arbol(PDC_ARBOL_PROJECT_B, $c1['versionId']) === null, 'Proyecto B no accede a versión de A por id.');

foreach ([$tmp, $tmp2] as $f) { @unlink($f); }
$limpiar();
echo $failures === [] ? "=== OK ===\n" : '=== ' . count($failures) . " FAILED ===\n";
exit($failures === [] ? 0 : 1);
