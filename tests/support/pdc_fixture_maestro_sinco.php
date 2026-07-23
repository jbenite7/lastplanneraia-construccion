<?php
// tests/support/pdc_fixture_maestro_sinco.php
// Genera un .xlsx con la estructura de la hoja "Maestro Insumos" del export SINCO.

declare(strict_types=1);

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

const PDC_SINCO_HEADERS = [
    'Empresa', 'Codigo Insumo', 'Insumo Descripcion', 'Agrupacion', 'Agrupacion Descripcion',
    'Tipo', 'Tipo Descripcion', 'Unidad', 'Descripcion Unidad', 'Estado', 'Valor Unitario', 'Porcentaje IVA',
];

function pdcFixtureMaestroSincoEscribir(string $path, array $rows): void
{
    $book = new Spreadsheet();
    $book->getProperties()->setCreated(0)->setModified(0); // fixture determinista
    $sheet = $book->getActiveSheet();
    $sheet->setTitle('Maestro Insumos');
    $sheet->fromArray(PDC_SINCO_HEADERS, null, 'A1');
    $sheet->fromArray($rows, null, 'A2');
    (new Xlsx($book))->save($path);
    $book->disconnectWorksheets();
}

/** 5 activos (2 comparten agrupacion) + 1 INACTIVO. Códigos con prefijo TEST- para el cleanup. */
function pdcFixtureMaestroSinco(string $path): void
{
    pdcFixtureMaestroSincoEscribir($path, [
        //Empresa, Codigo,      Insumo Descripcion,          Agrup, Agrup Desc,        Tipo, Tipo Desc,      Und, Desc Und, Estado,   VrUnit, IVA
        ['AIA', 'TEST-101', 'PISO CERAMICO 30X30',        '03', 'MAT-ACABADOS',       'M', 'MATERIAL',      'M2', 'METRO2', 'ACTIVO',  25000, 19],
        ['AIA', 'TEST-102', 'PISO PORCELANATO 60X60',     '03', 'MAT-ACABADOS',       'M', 'MATERIAL',      'M2', 'METRO2', 'ACTIVO',  48000, 19],
        ['AIA', 'TEST-103', 'ACERO DE REFUERZO 60000PSI', '05', 'MAT-ACEROS',         'M', 'MATERIAL',      'KG', 'KILO',   'ACTIVO',   4200, 19],
        ['AIA', 'TEST-104', 'AYUDANTE DE OBRA',           '10', 'SUBCONTRATACION',    'S', 'MANO DE OBRA',  'HC', 'HORA',   'ACTIVO',   9500,  0],
        ['AIA', 'TEST-105', 'ALQUILER ANDAMIO',           '21', 'ALQUILER MAQUINARIA','E', 'EQUIPO',        'DIA','DIA',    'ACTIVO', 130506, 19],
        ['AIA', 'TEST-106', 'INSUMO OBSOLETO',            '99', 'OTROS',              'M', 'MATERIAL',      'UN', 'UNIDAD', 'INACTIVO', 100,  0],
    ]);
}

/** Variante con 3 filas activas inválidas (código vacío, unidad vacía, valor no numérico). */
function pdcFixtureMaestroSincoInvalido(string $path): void
{
    pdcFixtureMaestroSincoEscribir($path, [
        ['AIA', '',         'SIN CODIGO',   '03', 'MAT-ACABADOS', 'M', 'MATERIAL', 'M2', 'METRO2', 'ACTIVO', 100, 19],
        ['AIA', 'TEST-201', 'SIN UNIDAD',   '03', 'MAT-ACABADOS', 'M', 'MATERIAL', '',   '',       'ACTIVO', 100, 19],
        ['AIA', 'TEST-202', 'VALOR ROTO',   '03', 'MAT-ACABADOS', 'M', 'MATERIAL', 'UN', 'UNIDAD', 'ACTIVO', 'abc', 19],
    ]);
}
