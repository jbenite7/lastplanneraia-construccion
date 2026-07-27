<?php

// 20260726_pdc_v2_paquetes_cola_larga.php
// PDC v2 / Fase A3.3 — paquetes para la cola larga de DAPORTO (71 insumos sin destino, $653M).
//
// Decisiones del usuario (grilleo 2026-07-26). Solo se crean los paquetes que NO existen ya en el
// catálogo de 204: al revisarlo aparecieron destinos válidos para buena parte de la cola larga
// (DOTACIÓN ZONAS COMUNES, DOTACIÓN COCINAS Y LAVADEROS, TOPOGRAFÍA, FILTROS, CUNETA TALUD, PLANTA
// ELÉCTRICA, ENGRAMADO…), así que esos se resuelven con reglas y no inflando el catálogo.
//
// Lo que sí faltaba:
//   · Equipos y maquinaria de obra   — vibradores, canguro, plancha vibratoria, bomba sumergible,
//     alquiler de compresor y martillo, reposición de equipo alquilado. Orden de compra: se compra y
//     se repone a lo largo de la obra, y lo que importa en el plan es tenerlo antes de arrancar.
//   · Tecnología y software de obra  — computadores, tablets, radios, impresoras, licencias,
//     internet. Consumo directo: se pide a necesidad y lo que se controla es el gasto.
//   · Transporte y acarreos          — acarreos, transporte interno, alquiler de buseta. Orden de
//     compra por la misma razón que los commodities.
//   · Provisiones y partidas globales— DETALLE CASAS, RESANES APARTAMENTO, PARTIDA PRESUPUESTAL
//     PORTERÍA: bolsas de presupuesto sin alcance definido. No contratable, porque si entran como
//     contrato contaminan A4 con procesos que no existen. Cuando se concreten, el presupuesto las
//     abrirá como actividades reales.
//   · Sum + Inst PAISAJISMO Y ZONAS VERDES — arborización y especies vegetales. La grama tiene su
//     propio paquete (ENGRAMADO) y gana por especificidad; este recoge el resto del oficio.
//
// Uso:  php database/migrations/20260726_pdc_v2_paquetes_cola_larga.php [--apply]

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../src/Core/Database.php';

use App\Services\Pdc\PaquetesService;

$apply = in_array('--apply', $argv, true);
$db = Database::getInstance();
$svc = new PaquetesService($db);

/** @var array<int, array{0:string,1:string,2:string}> nombre, tipo_negociacion, modalidad */
$nuevos = [
    ['Equipos y maquinaria de obra', 'suministro', 'orden_compra'],
    ['Tecnología y software de obra', 'suministro', 'consumo_directo'],
    ['Transporte y acarreos', 'suministro', 'orden_compra'],
    ['Provisiones y partidas globales', 'consumibles', 'no_contratable'],
    ['Sum + Inst PAISAJISMO Y ZONAS VERDES', 'a_todo_costo', 'contrato'],
];

if (!$apply) {
    foreach ($nuevos as [$nombre, $tipo, $modalidad]) {
        $existe = (int) $db->query(
            'SELECT COUNT(*) FROM general_paquetes_contratacion WHERE nombre_norm = ?',
            [\App\Services\Pdc\MaestroInsumosService::normalizar($nombre)],
        )->fetchColumn() > 0;
        fwrite(STDOUT, sprintf("[DRY-RUN] %-40s %-13s %-16s %s\n", $nombre, $tipo, $modalidad, $existe ? 'ya existe' : 'se creará'));
    }
    fwrite(STDOUT, "Ejecuta con --apply.\n");
    exit(0);
}

$creados = 0;
foreach ($nuevos as [$nombre, $tipo, $modalidad]) {
    $r = $svc->crearPaquete($nombre, $tipo, 'seed-a33', $modalidad);
    if (!$r['ok']) {
        fwrite(STDERR, "ERROR creando «{$nombre}»: {$r['code']}\n");
        exit(1);
    }
    // crearPaquete es idempotente por nombre_norm: si ya existía devuelve el existente sin tocarlo,
    // así que la modalidad se fija aquí para que re-ejecutar la migración no la deje a medias.
    $db->query(
        'UPDATE general_paquetes_contratacion SET modalidad_contratacion = ?, activo = 1 WHERE id = ?',
        [$modalidad, (int) $r['paquete']['id']],
    );
    $creados += ($r['paquete']['existente'] ?? 0) === 1 ? 0 : 1;
}

fwrite(STDOUT, "[APLICADO] {$creados} paquete(s) nuevo(s); " . (count($nuevos) - $creados) . " ya existían (modalidad reafirmada).\n");
exit(0);
