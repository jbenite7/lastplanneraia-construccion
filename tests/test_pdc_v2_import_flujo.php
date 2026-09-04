<?php

declare(strict_types=1);
// @requiere: db


require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';
require_once __DIR__ . '/support/pdc_fixture_presupuesto.php';
require_once __DIR__ . '/support/ScopeFixture.php';

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
    // Limpieza obra por obra, cada una bajo su propio alcance.
    foreach ([PDC_TEST_PROJECT_A, PDC_TEST_PROJECT_B] as $pid) {
        ScopeFixture::enProyecto($db, $pid, static function () use ($db, $pid): void {
            $db->query('DELETE FROM pdc_presupuesto_versiones WHERE project_id = ?', [$pid]); // CASCADE borra items/insumos
        });
    }
};
$limpiar();

// El test importa y lee en la obra A; los dos tramos que miran a B abren su propio alcance.
ScopeFixture::abrir($db, PDC_TEST_PROJECT_A, 'test-pdc-import');

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
    'SELECT i.codigo FROM pdc_presupuesto_apu_insumos a
       JOIN pdc_presupuesto_items i ON i.id = a.item_id AND i.project_id = a.project_id
      WHERE a.project_id = ? AND a.version_id = ? AND a.descripcion = ?',
    [PDC_TEST_PROJECT_A, $c1['versionId'], 'TEJA DE ZINC'],
)->fetchColumn();
$assert($row === '01.01.01.01', 'Insumo amarrado por item_id a su actividad.');

// Retry idempotente: reusar el token tras confirmar responde la MISMA versión, sin duplicar.
$c2 = $service->confirmar($p['importToken'], PDC_TEST_PROJECT_A);
$assert($c2['ok'] === true && $c2['versionId'] === $c1['versionId'], 'Retry con el mismo token responde la versión existente (idempotencia).');
$assert(($c2['idempotente'] ?? false) === true, 'El retry idempotente se marca como tal.');
$totalTrasRetry = (int) $db->query('SELECT COUNT(*) FROM pdc_presupuesto_versiones WHERE project_id = ?', [PDC_TEST_PROJECT_A])->fetchColumn();
$assert($totalTrasRetry === 1, 'El retry no crea una versión duplicada.');
// Un token jamás visto sí expira.
$cInventado = $service->confirmar(str_repeat('f', 32), PDC_TEST_PROJECT_A);
$assert($cInventado['ok'] === false && $cInventado['code'] === 'TOKEN_EXPIRED', 'Token desconocido → TOKEN_EXPIRED.');

// Segundo import con contenido idéntico → anti-duplicado (A1.7): sin cambios, NO crea versión nueva.
//
// El aviso de «ya lo importaste» compara `archivo_hash`, el hash de los BYTES, así que hay que
// reimportar el mismo archivo. Antes se regeneraba uno nuevo con `pdcFixturePresupuestoValido()`
// dando por hecho que saldría idéntico, y solo salía idéntico si las dos generaciones caían en el
// mismo segundo: el XLSX fija sus propiedades (`setCreated(0)`) pero el contenedor ZIP se sella con
// la hora del sistema. Corriendo el test suelto pasaba; dentro de la suite completa, con la máquina
// cargada, pasaba más de un segundo entre ambas generaciones y el aviso no saltaba. De ahí el falso
// rojo intermitente.
$p2 = $service->previewDesdeArchivo($tmp, 'presupuesto-v2.xlsx', PDC_TEST_PROJECT_A, 'tester');
$assert($p2['advertencias'] !== [], 'Re-import del mismo archivo advierte (hash de archivo).');
$c3 = $service->confirmar($p2['importToken'], PDC_TEST_PROJECT_A);
$assert($c3['ok'] === true && $c3['sinCambios'] === true && $c3['versionId'] === $c1['versionId'], 'Confirmar contenido idéntico: sin cambios, NO crea versión nueva.');
$versiones = $service->versiones(PDC_TEST_PROJECT_A);
$assert(count($versiones) === 1, 'Sigue habiendo 1 sola versión (el anti-duplicado no crea una 2ª).');
$activas = array_values(array_filter($versiones, fn ($x) => (int) $x['activa'] === 1));
$assert(count($activas) === 1 && $activas[0]['id'] === $c1['versionId'], 'La única versión sigue activa.');

// Y ahora la otra mitad del anti-duplicado, la que de verdad importa: un archivo con BYTES
// distintos pero el mismo contenido tampoco crea versión. `sinCambios` sale de `contenido_hash`
// —construido desde los items y los insumos parseados—, así que no depende de la hora a la que se
// generó el .xlsx y se puede comprobar sin depender del reloj.
$tmp2 = sys_get_temp_dir() . '/pdc_flujo_valido2.xlsx';
pdcFixturePresupuestoValido($tmp2);
$p2b = $service->previewDesdeArchivo($tmp2, 'presupuesto-v3.xlsx', PDC_TEST_PROJECT_A, 'tester');
$c3b = $service->confirmar($p2b['importToken'], PDC_TEST_PROJECT_A);
$assert(
    $c3b['ok'] === true && $c3b['sinCambios'] === true && $c3b['versionId'] === $c1['versionId'],
    'Archivo distinto con el mismo contenido: sin cambios, NO crea versión nueva.',
);
$assert(count($service->versiones(PDC_TEST_PROJECT_A)) === 1, 'Sigue habiendo 1 sola versión tras el re-import equivalente.');

// Aislamiento por proyecto: B no ve nada. Es la aserción, así que se mira desde el alcance de B.
$assert(
    ScopeFixture::enProyecto($db, PDC_TEST_PROJECT_B, static fn () => $service->versiones(PDC_TEST_PROJECT_B)) === [],
    'Proyecto B no ve versiones de A.',
);

// Transaccionalidad: token de proyecto distinto no confirma nada en B.
$tmp3 = sys_get_temp_dir() . '/pdc_flujo_valido3.xlsx';
pdcFixturePresupuestoValido($tmp3);
$p3 = $service->previewDesdeArchivo($tmp3, 'x.xlsx', PDC_TEST_PROJECT_A, 'tester');
$cB = ScopeFixture::enProyecto($db, PDC_TEST_PROJECT_B, static fn () => $service->confirmar($p3['importToken'], PDC_TEST_PROJECT_B));
$assert($cB['ok'] === false && $cB['code'] === 'TOKEN_EXPIRED', 'Token de otro proyecto se rechaza.');
foreach ([$tmp2, $tmp3] as $f) { @unlink($f); }

ScopeFixture::cerrar($db);

@unlink($tmp);
@unlink($tmpBad);
$limpiar();
echo $failures === [] ? "=== OK ===\n" : '=== ' . count($failures) . " FAILED ===\n";
exit($failures === [] ? 0 : 1);
