<?php

declare(strict_types=1);
// @requiere: db


require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';
require_once __DIR__ . '/support/pdc_fixture_presupuesto.php';

use App\Services\Pdc\PresupuestoExcelParser;
use App\Services\Pdc\PresupuestoImportService;
use App\Services\Pdc\PresupuestoImportStore;

const PDC_TAM = 999921;

$failures = [];
$assert = static function (bool $c, string $m) use (&$failures): void {
    if ($c) { fwrite(STDOUT, "PASS: {$m}\n"); return; }
    $failures[] = $m; fwrite(STDERR, "FAIL: {$m}\n");
};

$db = Database::getInstance();
$db->query('DELETE FROM pdc_presupuesto_versiones WHERE project_id = ?', [PDC_TAM]);

echo "=== PDC v2: tamiz del presupuesto (avisos del visor) ===\n";
$store = new PresupuestoImportStore(sys_get_temp_dir() . '/pdc-tam-store-' . getmypid());
$service = new PresupuestoImportService($db, $store, new PresupuestoExcelParser());

// Un presupuesto de juguete con los tres fenómenos y nada más:
//  · SIN CUANTIFICAR (cantidad 0) arrastra sus 2 insumos a cero          → 1 actividad, 2 líneas
//  · LOSA MACIZA tiene cantidad, pero MOLDURA CHAFLAN va con Cant APU 0  → 1 insumo en cero (residuo)
//  · RED CONTRA INCENDIO: unidad SG, un solo insumo caro                 → 1 candidato a partida global
//  · CAMPAMENTO: normal, un solo insumo pero unidad M2 → no es partida global
//  · TEJA DE ZINC aparece en dos APU → 6 apariciones, 5 insumos distintos
$fx = sys_get_temp_dir() . '/pdc_tam.xlsx';
pdcFixtureEscribir($fx, [
    ['01',          'PRELIMINARES',        '',         '',   null, '', 102, 'TAM_1', '',        null, null, null, null,      '',              ''],
    ['01.01',       'CAMPAMENTOS',         '01',       '',   null, '', 102, 'TAM_1', '',        null, null, null, null,      '',              ''],
    ['01.01.01',    'INSTALACIONES',       '01.01',    '',   null, '', 102, 'TAM_1', '',        null, null, null, null,      '',              ''],
    ['01.01.01.01', 'CAMPAMENTO 18M2',     '01.01.01', 'M2', 18,   '', 102, 'TAM_1', 'APU-001', null, null, null, null,      '',              ''],
    ['',            'TEJA DE ZINC',        '',         'M2', null, '', 102, 'TAM_1', '',        1.0,  1.0,  19,   30000,     'MAT-CUBIERTAS', ''],
    // Los ceros van como cadena '0' y no como int 0: `fromArray()` de PhpSpreadsheet omite las
    // celdas cuyo valor es `== $nullValue`, y en PHP `0 == null` es verdadero — con el int, la celda
    // se quedaba vacía y el parser rechazaba la fila. El presupuesto real de Da Porto sí trae ceros
    // numéricos (47 actividades y 10 líneas de APU), y esos se importan sin problema.
    ['01.01.01.02', 'SIN CUANTIFICAR',     '01.01.01', 'M2', '0',  '', 102, 'TAM_1', 'APU-002', null, null, null, null,      '',              ''],
    ['',            'TEJA DE ZINC',        '',         'M2', null, '', 102, 'TAM_1', '',        1.0,  1.0,  19,   30000,     'MAT-CUBIERTAS', ''],
    ['',            'ALAMBRE NEGRO',       '',         'KG', null, '', 102, 'TAM_1', '',        2.0,  1.0,  19,   5000,      'MAT-VARIOS',    ''],
    ['02',          'ESTRUCTURA',          '',         '',   null, '', 102, 'TAM_1', '',        null, null, null, null,      '',              ''],
    ['02.01',       'CONCRETOS',           '02',       '',   null, '', 102, 'TAM_1', '',        null, null, null, null,      '',              ''],
    ['02.01.01',    'LOSAS',               '02.01',    '',   null, '', 102, 'TAM_1', '',        null, null, null, null,      '',              ''],
    ['02.01.01.01', 'LOSA MACIZA',         '02.01.01', 'M3', 40,   '', 102, 'TAM_1', 'APU-003', null, null, null, null,      '',              ''],
    ['',            'CONCRETO 3000PSI',    '',         'M3', null, '', 102, 'TAM_1', '',        1.0,  1.0,  19,   520000,    'MAT-CONCRETOS', ''],
    ['',            'MOLDURA CHAFLAN',     '',         'ML', null, '', 102, 'TAM_1', '',        '0',  1.0,  19,   6499.78,   'MAT-VARIOS',    ''],
    ['03',          'REDES',               '',         '',   null, '', 102, 'TAM_1', '',        null, null, null, null,      '',              ''],
    ['03.01',       'CONTRA INCENDIO',     '03',       '',   null, '', 102, 'TAM_1', '',        null, null, null, null,      '',              ''],
    ['03.01.01',    'RCI',                 '03.01',    '',   null, '', 102, 'TAM_1', '',        null, null, null, null,      '',              ''],
    ['03.01.01.01', 'RED CONTRA INCENDIO TODO COSTO', '03.01.01', 'SG', 1, '', 102, 'TAM_1', 'APU-004', null, null, null, null, '',          ''],
    ['',            'RCI TODO COSTO',      '',         'SG', null, '', 102, 'TAM_1', '',        1.0,  1.0,  19,   548000000, 'SUBCONTRATOS',  ''],
]);
$p = $service->previewDesdeArchivo($fx, 'tamiz.xlsx', PDC_TAM, 'tester');
$assert($p['ok'] === true, 'El fixture del tamiz es un presupuesto válido (los ceros no son errores).');
if ($p['ok'] !== true) { fwrite(STDERR, print_r($p['errores'], true)); exit(1); }
$c = $service->confirmar($p['importToken'], PDC_TAM);
$arbol = $service->arbol(PDC_TAM, $c['versionId']);
$assert($arbol !== null && isset($arbol['avisos']), 'El árbol del visor trae sus avisos (no hay endpoint aparte).');
$a = $arbol['avisos'] ?? [];

// Cifras honestas: las dos magnitudes, cada una con su número.
$assert(($a['aparicionesApu'] ?? 0) === 6, 'aparicionesApu = 6 (líneas de insumo del presupuesto).');
$assert(($a['insumosDistintos'] ?? 0) === 5, 'insumosDistintos = 5 (TEJA DE ZINC cuenta una vez, aunque aparece en dos APU).');
$assert(($a['aparicionesApu'] ?? 0) > ($a['insumosDistintos'] ?? 0), 'Las dos magnitudes son distintas y por eso hay que nombrarlas: es el 820 contra el 396 de Da Porto en pequeño.');

// Aviso 1: actividades sin cantidad, con las líneas que arrastran.
$assert(($a['actividadesSinCantidad']['cantidad'] ?? 0) === 1, 'Una actividad sin cantidad (SIN CUANTIFICAR).');
$assert(($a['actividadesSinCantidad']['lineasEnCero'] ?? 0) === 2, 'Esa actividad arrastra sus 2 líneas de insumo a cero.');
$assert(($a['actividadesSinCantidad']['detalle'][0]['descripcion'] ?? '') === 'SIN CUANTIFICAR', 'El detalle nombra la actividad sin cantidad.');
$assert(($a['actividadesSinCantidad']['detalle'][0]['codigo'] ?? '') === '01.01.01.02', 'El detalle da su código, que es con lo que se busca en el presupuesto de origen.');

// Aviso 2: el residuo real, sin doble conteo con el aviso 1.
$assert(($a['insumosEnCero']['cantidad'] ?? 0) === 1, 'Un insumo en cero por su propia línea de APU (MOLDURA CHAFLAN).');
$assert(($a['insumosEnCero']['detalle'][0]['descripcion'] ?? '') === 'MOLDURA CHAFLAN', 'El detalle nombra el insumo en cero.');
$assert(($a['insumosEnCero']['detalle'][0]['actividad'] ?? '') === 'LOSA MACIZA', 'El detalle dice en qué actividad está.');
$nombresEnCero = array_column($a['insumosEnCero']['detalle'] ?? [], 'descripcion');
$assert(!in_array('ALAMBRE NEGRO', $nombresEnCero, true), 'Las líneas que arrastra una actividad sin cantidad NO se cuentan otra vez aquí.');

// Aviso 3: candidatos a partida global, todos, sin umbral aplicado.
$assert(count($a['partidasGlobales']['candidatos'] ?? []) === 1, 'Un candidato a partida global.');
$assert(($a['partidasGlobales']['candidatos'][0]['codigo'] ?? '') === '03.01.01.01', 'El candidato es la RED CONTRA INCENDIO (unidad SG, un solo insumo).');
$assert(($a['partidasGlobales']['candidatos'][0]['insumos'] ?? 0) === 1, 'El candidato dice con cuántos insumos se resuelve el APU.');
$assert(abs(($a['partidasGlobales']['candidatos'][0]['valorTotal'] ?? 0) - 548000000.0) < 1.0, 'El candidato trae su valor, que es lo que la vista compara contra el umbral.');
$assert(in_array('SG', $a['partidasGlobales']['unidades'] ?? [], true), 'Las unidades globales viajan con el aviso.');
$codigosGlobales = array_column($a['partidasGlobales']['candidatos'] ?? [], 'codigo');
$assert(!in_array('01.01.01.01', $codigosGlobales, true), 'CAMPAMENTO (unidad M2, un solo insumo) no es partida global: el criterio de ≤2 insumos solo no basta.');
$assert(($a['costoTotal'] ?? 0) > 0, 'El costo total de la versión viaja con los avisos (base del umbral por defecto).');

// Los avisos NO bloquean: con avisos abiertos, el árbol se sirve entero.
$assert(count($arbol['items'] ?? []) === 13 && count($arbol['insumos'] ?? []) === 6, 'Con avisos abiertos el árbol se sirve completo: los avisos no esconden ni bloquean nada.');

$db->query('DELETE FROM pdc_presupuesto_versiones WHERE project_id = ?', [PDC_TAM]);
@unlink($fx);

echo $failures === [] ? "\n=== OK ===\n" : "\n" . count($failures) . " FAIL\n";
exit($failures === [] ? 0 : 1);
