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
    // Limpieza obra por obra, cada una bajo su propio alcance: el DELETE ya venía acotado por
    // project_id, así que el gate lo reescribe al mismo valor y no borra de más.
    foreach ([PDC_ARBOL_PROJECT_A, PDC_ARBOL_PROJECT_B] as $pid) {
        ScopeFixture::enProyecto($db, $pid, static function () use ($db, $pid): void {
            $db->query('DELETE FROM pdc_presupuesto_versiones WHERE project_id = ?', [$pid]);
        });
    }
};
$limpiar();

// v2: mismo presupuesto pero el precio de TEJA cambia (contenido distinto → versión nueva real;
// con el anti-duplicado de A1.7 un segundo import IDÉNTICO ya no crea versión, y este test
// necesita una 2ª versión genuina para probar que la histórica queda inactiva pero consultable).
$fixtureArbolV2 = static function (string $path): void {
    pdcFixtureEscribir($path, [
        ['01',          'PRELIMINARES',           '',      '',   null, '',  102, 'PI_TEST_1', '',     null,  null, null, null,   '',                        ''],
        ['01.01',       'CAMPAMENTO',             '01',    '',   null, '',  102, 'PI_TEST_1', '',     null,  null, null, null,   '',                        ''],
        ['01.01.01',    'INSTALACIONES',          '01.01', '',   null, '',  102, 'PI_TEST_1', '',     null,  null, null, null,   '',                        ''],
        ['01.01.01.01', 'CAMPAMENTO 18M2',        '01.01.01', 'M2', 18, '', 102, 'PI_TEST_1', 'APU-001', null, null, null, null, '',                        ''],
        ['',            'TEJA DE ZINC',           '',      'M2', null, '', 102, 'PI_TEST_1', '',     1.05,  1.2, 19, 26000, 'MAT-CUBIERTAS',            ''],
        ['',            'AYUDANTE',               '',      'HC', null, '', 102, 'PI_TEST_1', '',     8.0,   0.5, null, 9500, 'MANO DE OBRA',             ''],
        ['02',          'ESTRUCTURA',             '',      '',   null, '',  102, 'PI_TEST_1', '',     null,  null, null, null,   '',                        ''],
        ['02.01',       'CONCRETOS',              '02',    '',   null, '',  102, 'PI_TEST_1', '',     null,  null, null, null,   '',                        ''],
        ['02.01.01',    'LOSAS',                  '02.01', '',   null, '',  102, 'PI_TEST_1', '',     null,  null, null, null,   '',                        ''],
        ['02.01.01.01', 'LOSA MACIZA E=12',       '02.01.01', 'M3', 40, '', 102, 'PI_TEST_1', 'APU-002', null, null, null, null, '',                        ''],
        ['',            'CONCRETO 4000PSI',       '',      'M3', null, '', 102, 'PI_TEST_1', '',     1.0,   1.05, 19, 620000, 'MAT-CONCRETOS',           ''],
        ['',            'SERVICIO BOMBEO',        '',      'M3', null, '', 102, 'PI_TEST_1', '',     1.0,   1.0, null, 28000, 'EQUIPOS',                  ''],
    ]);
};

echo "=== PDC v2: arbol() del visor ===\n";
$store = new PresupuestoImportStore(sys_get_temp_dir() . '/pdc-arbol-store-' . getmypid());
$service = new PresupuestoImportService($db, $store, new PresupuestoExcelParser());

// El tramo que sigue transcurre entero dentro de la obra A: se importa su presupuesto y se lee
// su árbol. El alcance de B se abre más abajo, justo para las aserciones de aislamiento.
ScopeFixture::abrir($db, PDC_ARBOL_PROJECT_A, 'test-pdc-arbol');

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
$assert(abs($insumosAct[0]['valorTotal'] - 567000.0) < 0.01, 'valorTotal del insumo (22.68 × 25000).');

// Segunda versión (contenido distinto) → la activa cambia; la histórica sigue consultable por versionId.
$tmp2 = sys_get_temp_dir() . '/pdc_arbol_v2.xlsx';
$fixtureArbolV2($tmp2);
$p2 = $service->previewDesdeArchivo($tmp2, 'v2.xlsx', PDC_ARBOL_PROJECT_A, 'tester');
$c2 = $service->confirmar($p2['importToken'], PDC_ARBOL_PROJECT_A);
$assert($service->arbol(PDC_ARBOL_PROJECT_A)['version']['id'] === $c2['versionId'], 'La activa es la nueva.');
$hist = $service->arbol(PDC_ARBOL_PROJECT_A, $c1['versionId']);
$assert($hist !== null && $hist['version']['id'] === $c1['versionId'] && (int) $hist['version']['activa'] === 0, 'Versión histórica consultable por id.');

// Aislamiento: B no ve la versión de A ni por id. Estas dos son la aserción, no preparación, así
// que van bajo el alcance de B: mirarlas desde el alcance de A haría que el gate reescribiera el
// project_id y el test comprobaría que A se ve a sí mismo, que no prueba nada.
ScopeFixture::abrir($db, PDC_ARBOL_PROJECT_B, 'test-pdc-arbol');
$assert($service->arbol(PDC_ARBOL_PROJECT_B) === null, 'Proyecto B sin árbol.');
$assert($service->arbol(PDC_ARBOL_PROJECT_B, $c1['versionId']) === null, 'Proyecto B no accede a versión de A por id.');

ScopeFixture::cerrar($db);

foreach ([$tmp, $tmp2] as $f) { @unlink($f); }
$limpiar();
echo $failures === [] ? "=== OK ===\n" : '=== ' . count($failures) . " FAILED ===\n";
exit($failures === [] ? 0 : 1);
