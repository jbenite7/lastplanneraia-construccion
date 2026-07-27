<?php

// 20260725_pdc_v2_backfill_modalidades.php
// PDC v2 / Fase A3.1 — clasifica el catálogo en las 4 modalidades de contratación y parte el cajón
// de sastre «Indirectos / Administración». Migración SEPARADA del DDL y de los seeds a propósito:
// reaplicar un seed no debe revertir una reclasificación hecha a mano.
//
// Criterio de orden_compra (las tres condiciones): tipo_negociacion='suministro' + commodity de
// consumo disperso + cuantía relevante. Respaldado por el catálogo legacy de duraciones, que ya
// marcaba CONCRETO y ACERO DE REFUERZO como «Orden de Compra» con ciclos propios (87 y 104 días).
//
// Uso:  php database/migrations/20260725_pdc_v2_backfill_modalidades.php [--apply]

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../src/Core/Database.php';

use App\Services\Pdc\MaestroInsumosService;
use App\Services\Pdc\PaquetesService;

$apply = in_array('--apply', $argv, true);
$db = Database::getInstance();
$svc = new PaquetesService($db);
$norm = static fn (string $s): string => mb_substr(MaestroInsumosService::normalizar($s), 0, 200);

// ── 1. Commodities de consumo recurrente → orden_compra ───────────────────────────────────────
// Se compran a pedido, con entregas múltiples y proveedor que puede cambiar durante la obra.
$ordenCompra = [
    'Suministro CONCRETO',                  // marcado «Orden de Compra» en el catálogo legacy (87 d)
    'Suministro ACERO DE REFUERZO',         // idem (104 d)
    'Suministro CEMENTO',
    'Suministro MORTEROS',
    'Suministro AGREGADOS',
    'Suministro LADRILLO',
    'Suministro BLOQUE DE CONCRETO',
    'Suministro BASE Y SUB BASE GRANULAR',
    'Suministro ADITIVOS DE CONCRETO',
    'Suministro PEGANTES Y BOQUILLAS',
    'Suministro ALIGERANTES LOSAS',
];

// ── 2. Ferretería y consumibles a demanda → consumo_directo ───────────────────────────────────
$consumoDirecto = ['Ferretería y consumibles de obra'];

// ── 3. Nómina e imprevistos → no_contratable ──────────────────────────────────────────────────
$noContratable = ['Nómina de obra', 'Imprevistos y provisiones'];

if (!$apply) {
    fwrite(STDOUT, '[DRY-RUN] orden_compra: ' . count($ordenCompra) . ' paquetes; se crearían ' .
        (count($consumoDirecto) + count($noContratable)) . " paquetes nuevos (ferretería, nómina, imprevistos).\n");
    $ind = (int) $db->query('SELECT COUNT(*) FROM pdc_insumo_paquete a JOIN general_paquetes_contratacion p ON p.id = a.paquete_id WHERE p.nombre_norm = ?', [$norm(PaquetesService::PAQUETE_INDIRECTOS)])->fetchColumn();
    fwrite(STDOUT, "          «Indirectos / Administración» tiene hoy {$ind} asignaciones que se repartirán al re-sembrar.\n");
    exit(0);
}

// Crear los paquetes de las modalidades sin proceso (idempotente por nombre_norm).
$nuevos = [
    ['Ferretería y consumibles de obra', 'suministro', 'consumo_directo'],
    ['Nómina de obra', 'consumibles', 'no_contratable'],
    ['Imprevistos y provisiones', 'consumibles', 'no_contratable'],
];
$creados = 0;
foreach ($nuevos as [$nombre, $tipo, $modalidad]) {
    $r = $svc->crearPaquete($nombre, $tipo, 'seed-modalidad', $modalidad);
    if ($r['ok'] !== true) {
        fwrite(STDERR, "FALLO al crear «{$nombre}».\n");
        exit(1);
    }
    if ((int) ($r['paquete']['existente'] ?? 0) === 0) { $creados++; }
}

// Aplicar las modalidades al catálogo.
$aplicar = static function (Database $db, array $nombres, string $modalidad) use ($norm): int {
    $n = 0;
    foreach ($nombres as $nombre) {
        $stmt = $db->query(
            'UPDATE general_paquetes_contratacion SET modalidad_contratacion = ?, updated_at = NOW() WHERE nombre_norm = ?',
            [$modalidad, $norm($nombre)],
        );
        $n += $stmt->rowCount();
    }
    return $n;
};
$nOC = $aplicar($db, $ordenCompra, 'orden_compra');
$nCD = $aplicar($db, $consumoDirecto, 'consumo_directo');
$nNC = $aplicar($db, $noContratable, 'no_contratable');

// «Indirectos / Administración» se conserva como bucket administrativo, pero deja de ser contratable.
$nInd = $aplicar($db, [PaquetesService::PAQUETE_INDIRECTOS], 'no_contratable');

$resumen = $db->query(
    'SELECT modalidad_contratacion, COUNT(*) n FROM general_paquetes_contratacion WHERE activo = 1 GROUP BY modalidad_contratacion ORDER BY n DESC',
)->fetchAll(PDO::FETCH_ASSOC);

fwrite(STDOUT, "[APLICADO] paquetes nuevos: {$creados} | orden_compra: {$nOC} | consumo_directo: {$nCD} | no_contratable: " . ($nNC + $nInd) . "\n");
foreach ($resumen as $r) {
    fwrite(STDOUT, sprintf("           %-16s %3d paquetes\n", $r['modalidad_contratacion'], (int) $r['n']));
}
exit(0);
