<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';
require_once __DIR__ . '/support/pdc_fixture_presupuesto.php';

use App\Services\Pdc\PresupuestoExcelParser;
use App\Services\Pdc\PresupuestoImportService;
use App\Services\Pdc\PresupuestoImportStore;

const PDC_TEST_PROJECT_A = 999901;
const PDC_TEST_PROJECT_B = 999902;

$failures = [];
$assert = static function (bool $condition, string $message) use (&$failures): void {
    if ($condition) { fwrite(STDOUT, "PASS: {$message}\n"); return; }
    $failures[] = $message;
    fwrite(STDERR, "FAIL: {$message}\n");
};

$db = Database::getInstance();
$limpiar = static function () use ($db): void {
    foreach ([PDC_TEST_PROJECT_A, PDC_TEST_PROJECT_B] as $pid) {
        $db->query('DELETE FROM pdc_presupuesto_versiones WHERE project_id = ?', [$pid]); // CASCADE borra items/insumos
    }
};
$limpiar();

echo "=== PDC v2: flujo de import (preview) ===\n";
$storeDir = sys_get_temp_dir() . '/pdc-imports-test-' . getmypid();
$store = new PresupuestoImportStore($storeDir);
$service = new PresupuestoImportService($db, $store, new PresupuestoExcelParser());

$tmp = sys_get_temp_dir() . '/pdc_flujo_valido.xlsx';
pdcFixturePresupuestoValido($tmp);

// Preview válido: token + resumen, archivo persistido en el store, nada en BD.
$p = $service->previewDesdeArchivo($tmp, 'presupuesto.xlsx', PDC_TEST_PROJECT_A, 'tester');
$assert($p['ok'] === true, 'Preview válido responde ok.');
$assert(preg_match('/^[a-f0-9]{32}$/', $p['importToken']) === 1, 'Token de 32 hex.');
$assert($p['resumen']['actividades'] === 2 && $p['resumen']['insumos'] === 4, 'Resumen con conteos correctos.');
$assert($p['advertencias'] === [], 'Sin advertencias en el primer import.');
$assert($store->ruta($p['importToken']) !== null, 'El archivo quedó en el store.');
$enBd = (int) $db->query('SELECT COUNT(*) FROM pdc_presupuesto_versiones WHERE project_id = ?', [PDC_TEST_PROJECT_A])->fetchColumn();
$assert($enBd === 0, 'Preview NO escribe en BD.');

// Preview inválido: errores y nada en el store.
$tmpBad = sys_get_temp_dir() . '/pdc_flujo_invalido.xlsx';
pdcFixturePresupuestoInvalido($tmpBad);
$pb = $service->previewDesdeArchivo($tmpBad, 'malo.xlsx', PDC_TEST_PROJECT_A, 'tester');
$assert($pb['ok'] === false && count($pb['errores']) >= 3, 'Preview inválido responde errores.');

// Store: token inválido y TTL.
$assert($store->ruta('zzzz') === null, 'Token con formato inválido → null (sin path traversal).');
$assert(is_file($storeDir . '/.htaccess'), 'El store crea su .htaccess de denegación.');

echo "--- casos de confirmación se agregan en Task 5 ---\n";
$limpiar();
echo $failures === [] ? "=== OK ===\n" : '=== ' . count($failures) . " FAILED ===\n";
exit($failures === [] ? 0 : 1);
