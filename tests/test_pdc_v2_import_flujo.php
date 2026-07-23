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

echo "=== PDC v2: flujo de import (confirmar + versiones) ===\n";

// Confirmar el preview válido de arriba.
$c1 = $service->confirmar($p['importToken'], PDC_TEST_PROJECT_A);
$assert($c1['ok'] === true && $c1['versionId'] > 0, 'Confirmación crea la versión.');
$v = $db->query('SELECT * FROM pdc_presupuesto_versiones WHERE project_id = ? AND activa = 1', [PDC_TEST_PROJECT_A])->fetchAll(PDO::FETCH_ASSOC);
$assert(count($v) === 1 && $v[0]['version_label'] === 'PI_TEST_1', 'Única versión activa con label correcto.');
$nItems = (int) $db->query('SELECT COUNT(*) FROM pdc_presupuesto_items WHERE project_id = ? AND version_id = ?', [PDC_TEST_PROJECT_A, $c1['versionId']])->fetchColumn();
$nIns = (int) $db->query('SELECT COUNT(*) FROM pdc_presupuesto_apu_insumos WHERE project_id = ? AND version_id = ?', [PDC_TEST_PROJECT_A, $c1['versionId']])->fetchColumn();
$assert($nItems === 8 && $nIns === 4, 'Items (8) e insumos (4) persistidos.');
// item_id de los insumos apunta a la actividad correcta
$row = $db->query(
    'SELECT i.codigo FROM pdc_presupuesto_apu_insumos a JOIN pdc_presupuesto_items i ON i.id = a.item_id WHERE a.project_id = ? AND a.version_id = ? AND a.descripcion = ?',
    [PDC_TEST_PROJECT_A, $c1['versionId'], 'TEJA DE ZINC'],
)->fetchColumn();
$assert($row === '01.01.01.01', 'Insumo amarrado por item_id a su actividad.');

// Token de un solo uso.
$c2 = $service->confirmar($p['importToken'], PDC_TEST_PROJECT_A);
$assert($c2['ok'] === false && $c2['code'] === 'TOKEN_EXPIRED', 'El token no se puede reutilizar.');

// Segundo import → la primera versión queda inactiva, ambas se conservan.
$tmp2 = sys_get_temp_dir() . '/pdc_flujo_valido2.xlsx';
pdcFixturePresupuestoValido($tmp2);
$p2 = $service->previewDesdeArchivo($tmp2, 'presupuesto-v2.xlsx', PDC_TEST_PROJECT_A, 'tester');
$assert($p2['advertencias'] !== [], 'Re-import de contenido idéntico advierte.');
$c3 = $service->confirmar($p2['importToken'], PDC_TEST_PROJECT_A);
$versiones = $service->versiones(PDC_TEST_PROJECT_A);
$assert(count($versiones) === 2, 'Se conservan las 2 versiones.');
$activas = array_values(array_filter($versiones, fn ($x) => (int) $x['activa'] === 1));
$assert(count($activas) === 1 && $activas[0]['id'] === $c3['versionId'], 'Solo la nueva versión queda activa.');

// Aislamiento por proyecto: B no ve nada.
$assert($service->versiones(PDC_TEST_PROJECT_B) === [], 'Proyecto B no ve versiones de A.');

// Transaccionalidad: token de proyecto distinto no confirma nada en B.
$tmp3 = sys_get_temp_dir() . '/pdc_flujo_valido3.xlsx';
pdcFixturePresupuestoValido($tmp3);
$p3 = $service->previewDesdeArchivo($tmp3, 'x.xlsx', PDC_TEST_PROJECT_A, 'tester');
$cB = $service->confirmar($p3['importToken'], PDC_TEST_PROJECT_B);
$assert($cB['ok'] === false && $cB['code'] === 'TOKEN_EXPIRED', 'Token de otro proyecto se rechaza.');
foreach ([$tmp2, $tmp3] as $f) { @unlink($f); }

@unlink($tmp);
@unlink($tmpBad);
$limpiar();
echo $failures === [] ? "=== OK ===\n" : '=== ' . count($failures) . " FAILED ===\n";
exit($failures === [] ? 0 : 1);
