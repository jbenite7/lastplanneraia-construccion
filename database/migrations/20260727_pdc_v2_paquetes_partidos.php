<?php

// 20260727_pdc_v2_paquetes_partidos.php
// PDC v2 / Fase A3.4 — paquetes que faltaban para separar suministro de mano de obra.
//
// Sale de dos fuentes que dicen lo mismo:
//
//  1. La reunión con la dirección de obra (2026-06-30): «yo tengo 2 contratos, uno que es por
//     fabricación y suministro, y otro contrato que es por mano de obra. El de mano de obra tiene
//     [IVA de servicios] y el de suministro tiene IVA pleno… ellos tienen 2 razones sociales».
//  2. La revisión del sembrado por el usuario, que repite el mismo criterio insumo a insumo:
//     «Suministro solo, la MO la ejecutan los de carpintería», «Topografía es una MO»,
//     «M.O. INSTALACION TOPELLANTAS aparte del urbanismo, y el suministro por aparte también».
//
// Los «Sum + Inst» equivalentes NO se retiran (decisión del usuario en el grilleo): la misma obra
// puede contratar la carpintería partida y la impermeabilización a todo costo, y el año que viene
// otra obra puede decidir al revés.
//
// Uso:  php database/migrations/20260727_pdc_v2_paquetes_partidos.php [--apply]

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../src/Core/Database.php';

use App\Services\Pdc\MaestroInsumosService;
use App\Services\Pdc\PaquetesService;

$apply = in_array('--apply', $argv, true);
$db = Database::getInstance();
$svc = new PaquetesService($db);

/** @var array<int, array{0:string,1:string,2:string,3:string}> nombre, tipo, modalidad, por qué */
$nuevos = [
    // Puertas por tipo. El usuario pidió partir las tres categorías vigentes; quedó advertido que la
    // certificación de una cortafuego suele exigir un único responsable de suministro e instalación.
    ['Suministro PUERTAS EN MADERA', 'suministro', 'contrato', 'fabricación y suministro, IVA pleno'],
    ['M. de O CARPINTERÍA DE MADERA', 'mano_obra', 'contrato', 'instalación de puertas y carpintería, IVA de servicios'],
    ['Suministro PUERTAS METÁLICAS', 'suministro', 'contrato', 'el usuario lo pidió explícitamente en la revisión'],
    ['M. de O PUERTAS METÁLICAS', 'mano_obra', 'contrato', 'instalación por separado del suministro'],
    ['Suministro PUERTAS CORTAFUEGO', 'suministro', 'contrato', 'misma doctrina que las demás puertas'],
    ['M. de O PUERTAS CORTAFUEGO', 'mano_obra', 'contrato', 'misma doctrina que las demás puertas'],

    // Anclajes: el epóxico es el consumible del anclaje químico. Un paquete por referencia comercial
    // («Sum EPOXICO HILTI HY-200») no casaría en la siguiente obra.
    ['Suministro ANCLAJES', 'suministro', 'contrato', 'epóxicos y anclajes químicos, sin marca'],

    // Dotación de cocina: «Suministro solo. La MO la ejecutan los de la carpintería de madera».
    ['Suministro DOTACIÓN COCINAS Y LAVADEROS', 'suministro', 'contrato', 'campana, asador y electrodomésticos empotrables'],

    // «Topografía es una MO»: hoy solo existía el paquete a todo costo.
    ['M. de O TOPOGRAFÍA', 'mano_obra', 'contrato', 'comisión de topografía, localización y replanteo'],

    // «M.O. INSTALACION TOPELLANTAS aparte del urbanismo, y el suministro por aparte también».
    ['M. de O TOPELLANTAS', 'mano_obra', 'contrato', 'instalación separada del suministro, que ya tenía paquete'],

    // «Contrato aparte de alquiler de transporte de personal», distinto del transporte de materiales.
    ['Alquiler de transporte de personal', 'suministro', 'contrato', 'buses y busetas de personal de obra'],
];

if (!$apply) {
    foreach ($nuevos as [$nombre, $tipo, $modalidad, $porQue]) {
        $existe = (int) $db->query(
            'SELECT COUNT(*) FROM general_paquetes_contratacion WHERE nombre_norm = ?',
            [mb_substr(MaestroInsumosService::normalizar($nombre), 0, 200)],
        )->fetchColumn() > 0;
        fwrite(STDOUT, sprintf("[DRY-RUN] %-42s %-11s %s — %s\n", $nombre, $tipo, $existe ? 'ya existe' : 'se creará', $porQue));
    }
    fwrite(STDOUT, "Ejecuta con --apply.\n");
    exit(0);
}

$creados = 0;
foreach ($nuevos as [$nombre, $tipo, $modalidad, $porQue]) {
    $r = $svc->crearPaquete($nombre, $tipo, 'seed-a34', $modalidad);
    if (!$r['ok']) {
        fwrite(STDERR, "ERROR creando «{$nombre}»: {$r['code']}\n");
        exit(1);
    }
    $db->query(
        'UPDATE general_paquetes_contratacion SET modalidad_contratacion = ?, activo = 1 WHERE id = ?',
        [$modalidad, (int) $r['paquete']['id']],
    );
    $creados += ($r['paquete']['existente'] ?? 0) === 1 ? 0 : 1;
}

fwrite(STDOUT, "[APLICADO] {$creados} paquete(s) nuevo(s); " . (count($nuevos) - $creados) . " ya existían.\n");
exit(0);
