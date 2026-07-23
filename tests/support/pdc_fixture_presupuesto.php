<?php
// tests/support/pdc_fixture_presupuesto.php
// Genera archivos .xlsx sintéticos con la estructura de la hoja "Presupuesto"
// del software de presupuestos de AIA (ver spec A1).

declare(strict_types=1);

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

const PDC_FIXTURE_HEADERS = ['Código', 'Descripción', 'Padre', 'UM', 'CANTIDAD', 'SUBCAPITULO', 'ID PROYECTO', 'VERSION', 'ID APU', 'Cant APU', 'Rend', 'IVA', 'VrUnit', 'Tipo Insumo', 'Agrupacion'];

function pdcFixtureEscribir(string $path, array $rows): void
{
    $book = new Spreadsheet();
    $sheet = $book->getActiveSheet();
    $sheet->setTitle('Presupuesto');
    $sheet->fromArray(PDC_FIXTURE_HEADERS, null, 'A1');
    $sheet->fromArray($rows, null, 'A2');
    (new Xlsx($book))->save($path);
    $book->disconnectWorksheets();
}

function pdcFixturePresupuestoValido(string $path): void
{
    //           Código, Descripción,             Padre,   UM,   CANT, SUBCAP, IDP, VERSION,        ID APU, CantAPU, Rend, IVA, VrUnit,  Tipo Insumo,               Agrup
    pdcFixtureEscribir($path, [
        ['01',          'PRELIMINARES',           '',      '',   null, '',  102, 'PI_TEST_1', '',     null,  null, null, null,   '',                        ''],
        ['01.01',       'CAMPAMENTO',             '01',    '',   null, '',  102, 'PI_TEST_1', '',     null,  null, null, null,   '',                        ''],
        ['01.01.01',    'INSTALACIONES',          '01.01', '',   null, '',  102, 'PI_TEST_1', '',     null,  null, null, null,   '',                        ''],
        ['01.01.01.01', 'CAMPAMENTO 18M2',        '01.01.01', 'M2', 18, '', 102, 'PI_TEST_1', 'APU-001', null, null, null, null, '',                        ''],
        ['',            'TEJA DE ZINC',           '',      'M2', null, '', 102, 'PI_TEST_1', '',     1.05,  1.2, 19, 25000, 'MAT-CUBIERTAS',            ''],
        ['',            'AYUDANTE',               '',      'HC', null, '', 102, 'PI_TEST_1', '',     8.0,   0.5, null, 9500, 'MANO DE OBRA',             ''],
        ['02',          'ESTRUCTURA',             '',      '',   null, '',  102, 'PI_TEST_1', '',     null,  null, null, null,   '',                        ''],
        ['02.01',       'CONCRETOS',              '02',    '',   null, '',  102, 'PI_TEST_1', '',     null,  null, null, null,   '',                        ''],
        ['02.01.01',    'LOSAS',                  '02.01', '',   null, '',  102, 'PI_TEST_1', '',     null,  null, null, null,   '',                        ''],
        ['02.01.01.01', 'LOSA MACIZA E=12',       '02.01.01', 'M3', 40, '', 102, 'PI_TEST_1', 'APU-002', null, null, null, null, '',                        ''],
        ['',            'CONCRETO 4000PSI',       '',      'M3', null, '', 102, 'PI_TEST_1', '',     1.0,   1.05, 19, 620000, 'MAT-CONCRETOS',           ''],
        ['',            'SERVICIO BOMBEO',        '',      'M3', null, '', 102, 'PI_TEST_1', '',     1.0,   1.0, null, 28000, 'EQUIPOS',                  ''],
    ]);
}

// Reproduce el formato real de exportación de AIA: ID APU siempre vacío;
// la actividad se reconoce por tener CANTIDAD numérica (ver calibración DAPORTO).
function pdcFixturePresupuestoSinIdApu(string $path): void
{
    pdcFixtureEscribir($path, [
        ['01',          'CAPITULO',         '',         '',  null, '', 102, '', '', null, null, null, null,  '',      ''],
        ['01.01',       'SUBCAPITULO',      '01',       '',  null, '', 102, '', '', null, null, null, null,  '',      ''],
        ['01.01.01',    'GRUPO',            '01.01',    '',  null, '', 102, '', '', null, null, null, null,  '',      ''],
        ['01.01.01.01', 'ACTIVIDAD SIN IDAPU', '01.01.01', 'M2', 5, '', 102, '', '', null, null, null, null,  '',      ''],
        ['',            'INSUMO X',         '',         'UN', null, '', 102, '', '', 1.0,  2.0,  null, 100,  'MAT',   ''],
    ]);
}

function pdcFixturePresupuestoInvalido(string $path): void
{
    pdcFixtureEscribir($path, [
        // Insumo sin actividad previa (fila 2) → error.
        ['',   'INSUMO HUERFANO', '', 'UN', null, '', 102, 'PI_TEST_BAD', '', 1.0, 1.0, null, 100, 'MAT-VARIOS', ''],
        ['01', 'CAPITULO',        '', '',   null, '', 102, 'PI_TEST_BAD', '', null, null, null, null, '', ''],
        ['01.01.01.01', 'ACTIVIDAD SIN PADRE', '01.01.01', 'M2', 10, '', 102, 'PI_TEST_BAD', 'APU-X', null, null, null, null, '', ''],
        // VrUnit no numérico (fila 5) y UM vacía (misma fila) → 2 errores.
        ['',   'INSUMO ROTO',     '', '',   null, '', 102, 'PI_TEST_BAD', '', 1.0, 1.0, null, 'abc', 'MAT-VARIOS', ''],
    ]);
}
