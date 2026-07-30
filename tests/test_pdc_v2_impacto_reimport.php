<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';
require_once __DIR__ . '/support/pdc_fixture_presupuesto.php';

use App\Services\Pdc\PresupuestoExcelParser;
use App\Services\Pdc\PresupuestoImportService;
use App\Services\Pdc\PresupuestoImportStore;

const PDC_IMP_A = 999911;

$failures = [];
$assert = static function (bool $c, string $m) use (&$failures): void {
    if ($c) { fwrite(STDOUT, "PASS: {$m}\n"); return; }
    $failures[] = $m; fwrite(STDERR, "FAIL: {$m}\n");
};

$db = Database::getInstance();
$limpiar = static function () use ($db): void {
    $db->query('DELETE FROM pdc_insumo_paquete WHERE project_id IN (?, ?)', [PDC_IMP_A, PDC_IMP_A + 1]);
    $db->query('DELETE FROM pdc_presupuesto_versiones WHERE project_id = ?', [PDC_IMP_A]);
    $db->query('DELETE FROM general_paquetes_contratacion WHERE nombre_norm = ?', ['CUBIERTAS IMP TEST']);
};
$limpiar();

echo "=== PDC v2: impacto de reimportar sobre el trabajo hecho ===\n";
$store = new PresupuestoImportStore(sys_get_temp_dir() . '/pdc-imp-store-' . getmypid());
$service = new PresupuestoImportService($db, $store, new PresupuestoExcelParser());

// V1: dos actividades, cuatro insumos. TEJA y AYUDANTE reciben paquete; el resto no.
$v1 = sys_get_temp_dir() . '/pdc_imp_v1.xlsx';
pdcFixtureEscribir($v1, [
    ['01',          'PRELIMINARES',    '',         '',    null, '', 102, 'IMP_1', '',        null, null, null, null,   '',              ''],
    ['01.01',       'CAMPAMENTO',      '01',       '',    null, '', 102, 'IMP_1', '',        null, null, null, null,   '',              ''],
    ['01.01.01',    'INSTALACIONES',   '01.01',    '',    null, '', 102, 'IMP_1', '',        null, null, null, null,   '',              ''],
    ['01.01.01.01', 'CAMPAMENTO 18M2', '01.01.01', 'M2',  18,   '', 102, 'IMP_1', 'APU-001', null, null, null, null,   '',              ''],
    ['',            'TEJA DE ZINC',    '',         'M2',  null, '', 102, 'IMP_1', '',        1.0,  1.0,  19,   30000,  'MAT-CUBIERTAS', ''],
    ['',            'AYUDANTE',        '',         'DIA', null, '', 102, 'IMP_1', '',        1.0,  1.0,  null, 80000,  'MANO DE OBRA',  ''],
    ['02',          'ESTRUCTURA',      '',         '',    null, '', 102, 'IMP_1', '',        null, null, null, null,   '',              ''],
    ['02.01',       'CONCRETOS',       '02',       '',    null, '', 102, 'IMP_1', '',        null, null, null, null,   '',              ''],
    ['02.01.01',    'LOSAS',           '02.01',    '',    null, '', 102, 'IMP_1', '',        null, null, null, null,   '',              ''],
    ['02.01.01.01', 'LOSA MACIZA',     '02.01.01', 'M3',  40,   '', 102, 'IMP_1', 'APU-002', null, null, null, null,   '',              ''],
    ['',            'CONCRETO 3000PSI', '',        'M3',  null, '', 102, 'IMP_1', '',        1.0,  1.0,  19,   520000, 'MAT-CONCRETOS', ''],
    ['',            'SERVICIO BOMBEO', '',         'M3',  null, '', 102, 'IMP_1', '',        1.0,  1.0,  null, 63000,  'EQUIPOS',       ''],
]);
$p1 = $service->previewDesdeArchivo($v1, 'v1.xlsx', PDC_IMP_A, 'tester');
$assert(isset($p1['impacto']), 'El preview trae un bloque de impacto.');
// `array_key_exists` y no `?? 'x'`: aquí el null es el valor esperado, y el coalescente lo taparía.
$assert(array_key_exists('versionActiva', $p1['impacto'] ?? []) && $p1['impacto']['versionActiva'] === null, 'Sin versión activa: versionActiva = null.');
$assert(($p1['impacto']['valorAfectado'] ?? -1) === 0.0, 'Sin versión activa: valor afectado = 0 (no hay trabajo que perder).');
$c1 = $service->confirmar($p1['importToken'], PDC_IMP_A);

// Un paquete real y dos asignaciones: TEJA y AYUDANTE tienen destino.
$db->query(
    'INSERT INTO general_paquetes_contratacion (nombre, nombre_norm, creado_por, created_at) VALUES (?, ?, ?, NOW())',
    ['CUBIERTAS IMP TEST', 'CUBIERTAS IMP TEST', 'tester'],
);
$paqueteId = (int) $db->lastInsertId();
foreach ([['TEJA DE ZINC', 'M2'], ['AYUDANTE', 'DIA']] as [$desc, $und]) {
    $db->query(
        'INSERT INTO pdc_insumo_paquete (project_id, descripcion_norm, unidad, paquete_id, omitido, asignado_por, updated_at)
         VALUES (?, ?, ?, ?, 0, ?, NOW())',
        [PDC_IMP_A, $desc, $und, $paqueteId, 'tester'],
    );
}

// Condición de hecho 1 — candidata idéntica a la activa: las cuatro cifras dan cero.
$pIgual = $service->previewDesdeArchivo($v1, 'v1-otra-vez.xlsx', PDC_IMP_A, 'tester');
$i0 = $pIgual['impacto'];
$assert($i0['nuevosSinPaquete']['cantidad'] === 0, 'Idéntica: 0 insumos nuevos sin paquete.');
$assert($i0['desaparecenConPaquete']['cantidad'] === 0, 'Idéntica: 0 insumos que desaparecen con paquete.');
$assert($i0['cambianTipo']['cantidad'] === 0, 'Idéntica: 0 insumos que cambian de tipo.');
$assert($i0['valorAfectado'] === 0.0, 'Idéntica: valor afectado = 0.');
$assert(($i0['versionActiva']['id'] ?? 0) === $c1['versionId'], 'Idéntica: informa contra la versión activa.');

// V2: MALLA es nueva y sin paquete · AYUDANTE (con paquete) desaparece · SERVICIO BOMBEO
// cambia de tipo de insumo (EQUIPOS → SUBCONTRATOS) · TEJA y CONCRETO siguen igual.
$v2 = sys_get_temp_dir() . '/pdc_imp_v2.xlsx';
pdcFixtureEscribir($v2, [
    ['01',          'PRELIMINARES',    '',         '',   null, '', 102, 'IMP_2', '',        null, null, null, null,   '',              ''],
    ['01.01',       'CAMPAMENTO',      '01',       '',   null, '', 102, 'IMP_2', '',        null, null, null, null,   '',              ''],
    ['01.01.01',    'INSTALACIONES',   '01.01',    '',   null, '', 102, 'IMP_2', '',        null, null, null, null,   '',              ''],
    ['01.01.01.01', 'CAMPAMENTO 18M2', '01.01.01', 'M2', 18,   '', 102, 'IMP_2', 'APU-001', null, null, null, null,   '',              ''],
    ['',            'TEJA DE ZINC',    '',         'M2', null, '', 102, 'IMP_2', '',        1.0,  1.0,  19,   30000,  'MAT-CUBIERTAS', ''],
    ['02',          'ESTRUCTURA',      '',         '',   null, '', 102, 'IMP_2', '',        null, null, null, null,   '',              ''],
    ['02.01',       'CONCRETOS',       '02',       '',   null, '', 102, 'IMP_2', '',        null, null, null, null,   '',              ''],
    ['02.01.01',    'LOSAS',           '02.01',    '',   null, '', 102, 'IMP_2', '',        null, null, null, null,   '',              ''],
    ['02.01.01.01', 'LOSA MACIZA',     '02.01.01', 'M3', 40,   '', 102, 'IMP_2', 'APU-002', null, null, null, null,   '',              ''],
    ['',            'CONCRETO 3000PSI', '',        'M3', null, '', 102, 'IMP_2', '',        1.0,  1.0,  19,   520000, 'MAT-CONCRETOS', ''],
    ['',            'SERVICIO BOMBEO', '',         'M3', null, '', 102, 'IMP_2', '',        1.0,  1.0,  null, 63000,  'SUBCONTRATOS',  ''],
    ['',            'MALLA ELECTROSOLDADA', '',    'KG', null, '', 102, 'IMP_2', '',        1.0,  1.0,  19,   6000,   'MAT-ACEROS',    ''],
]);
$p2 = $service->previewDesdeArchivo($v2, 'v2.xlsx', PDC_IMP_A, 'tester');
$i = $p2['impacto'];

// Condición de hecho 2 — 1 · 1 · 1, y el detalle nombra exactamente esos tres.
$assert($i['nuevosSinPaquete']['cantidad'] === 1, 'V2: 1 insumo nuevo sin paquete.');
$assert(($i['nuevosSinPaquete']['detalle'][0]['descripcion'] ?? '') === 'MALLA ELECTROSOLDADA', 'V2: el nuevo es MALLA ELECTROSOLDADA.');
$assert($i['desaparecenConPaquete']['cantidad'] === 1, 'V2: 1 insumo con paquete desaparece.');
$assert(($i['desaparecenConPaquete']['detalle'][0]['descripcion'] ?? '') === 'AYUDANTE', 'V2: el que desaparece es AYUDANTE.');
$assert(($i['desaparecenConPaquete']['detalle'][0]['paquete'] ?? '') === 'CUBIERTAS IMP TEST', 'V2: el detalle dice a qué paquete estaba asignado.');
$assert($i['cambianTipo']['cantidad'] === 1, 'V2: 1 insumo cambia de tipo.');
$assert(($i['cambianTipo']['detalle'][0]['descripcion'] ?? '') === 'SERVICIO BOMBEO', 'V2: el que cambia de tipo es SERVICIO BOMBEO.');
$assert(($i['cambianTipo']['detalle'][0]['tipoInsumoAnterior'] ?? '') === 'EQUIPOS', 'V2: el detalle dice de qué tipo venía.');
$assert(($i['cambianTipo']['detalle'][0]['tipoInsumo'] ?? '') === 'SUBCONTRATOS', 'V2: el detalle dice a qué tipo va.');
$assert(count($i['nuevosSinPaquete']['detalle']) === 1 && count($i['desaparecenConPaquete']['detalle']) === 1 && count($i['cambianTipo']['detalle']) === 1, 'V2: ningún grupo arrastra insumos que no cambiaron (TEJA y CONCRETO fuera).');

// Condición de hecho 3 — el valor afectado es la suma de los tres grupos.
$suma = $i['nuevosSinPaquete']['valor'] + $i['desaparecenConPaquete']['valor'] + $i['cambianTipo']['valor'];
$assert(abs($i['valorAfectado'] - round($suma, 2)) < 0.01, 'V2: valorAfectado = suma de los tres grupos.');
$assert($i['valorAfectado'] > 0, 'V2: el valor afectado no es cero.');

// Un insumo asignado que se conserva no entra en ningún grupo (TEJA sigue existiendo).
$nombres = array_merge(
    array_column($i['nuevosSinPaquete']['detalle'], 'descripcion'),
    array_column($i['desaparecenConPaquete']['detalle'], 'descripcion'),
    array_column($i['cambianTipo']['detalle'], 'descripcion'),
);
$assert(!in_array('TEJA DE ZINC', $nombres, true), 'V2: un insumo asignado que se conserva no aparece como impacto.');

// Condición de hecho 4 — cancelar no escribe nada.
$activaAntes = $db->query('SELECT id FROM pdc_presupuesto_versiones WHERE project_id = ? AND activa = 1', [PDC_IMP_A])->fetchColumn();
$asignadosAntes = (int) $db->query('SELECT COUNT(*) FROM pdc_insumo_paquete WHERE project_id = ?', [PDC_IMP_A])->fetchColumn();
$service->previewDesdeArchivo($v2, 'v2-que-se-cancela.xlsx', PDC_IMP_A, 'tester'); // preview y nunca confirmar
$activaDespues = $db->query('SELECT id FROM pdc_presupuesto_versiones WHERE project_id = ? AND activa = 1', [PDC_IMP_A])->fetchColumn();
$asignadosDespues = (int) $db->query('SELECT COUNT(*) FROM pdc_insumo_paquete WHERE project_id = ?', [PDC_IMP_A])->fetchColumn();
$assert($activaAntes === $activaDespues, 'Cancelar: la versión activa queda intacta.');
$assert($asignadosAntes === $asignadosDespues, 'Cancelar: las asignaciones a paquete quedan intactas.');

// Aislamiento entre proyectos: el impacto no ve asignaciones de otro proyecto.
$db->query(
    'INSERT INTO pdc_insumo_paquete (project_id, descripcion_norm, unidad, paquete_id, omitido, asignado_por, updated_at)
     VALUES (?, ?, ?, ?, 0, ?, NOW())',
    [PDC_IMP_A + 1, 'MALLA ELECTROSOLDADA', 'KG', $paqueteId, 'tester'],
);
$p3 = $service->previewDesdeArchivo($v2, 'v2-aislamiento.xlsx', PDC_IMP_A, 'tester');
$assert($p3['impacto']['nuevosSinPaquete']['cantidad'] === 1, 'Aislamiento: una asignación de otro proyecto no da destino a MALLA aquí.');

// Condición de hecho 5 — confirmar conserva las asignaciones de lo que sigue existiendo.
$c2 = $service->confirmar($p2['importToken'], PDC_IMP_A);
$tejaSigue = (int) $db->query(
    'SELECT COUNT(*) FROM pdc_insumo_paquete WHERE project_id = ? AND descripcion_norm = ? AND paquete_id IS NOT NULL',
    [PDC_IMP_A, 'TEJA DE ZINC'],
)->fetchColumn();
$assert($c2['ok'] === true && $tejaSigue === 1, 'Confirmar conserva la asignación de TEJA (contrato de herencia de A3 intacto).');

$limpiar();
foreach ([$v1, $v2] as $f) { @unlink($f); }

echo $failures === [] ? "\n=== OK ===\n" : "\n" . count($failures) . " FAIL\n";
exit($failures === [] ? 0 : 1);
