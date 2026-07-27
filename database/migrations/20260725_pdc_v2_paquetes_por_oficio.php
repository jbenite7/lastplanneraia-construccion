<?php

// 20260725_pdc_v2_paquetes_por_oficio.php
// PDC v2 / Fase A3.1 — granularidad de paquetes por OFICIO CONTRATABLE (feedback del usuario 2026-07-25):
// «un subcontratista de mano de obra de pisos cerámicos no tiene por qué ser experto en instalación de
// pisos en madera». El paquete «M. de O INSTALACION DE PISOS» era un cajón de sastre incontratable
// (mezclaba enchapador, instalador de laminado, carpintero de deck, plomero, ventanero y señalizador):
// se retira y su alcance se reparte en paquetes por oficio.
//
// Idempotente. Uso:
//   php database/migrations/20260725_pdc_v2_paquetes_por_oficio.php            (dry-run)
//   php database/migrations/20260725_pdc_v2_paquetes_por_oficio.php --apply

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../src/Core/Database.php';

use App\Services\Pdc\MaestroInsumosService;
use App\Services\Pdc\PaquetesService;

$apply = in_array('--apply', $argv, true);
$db = Database::getInstance();
$svc = new PaquetesService($db);

// 1) Paquetes nuevos por oficio.
$nuevos = [
    ['M. de O INSTALACIÓN DE PISOS CERÁMICOS', 'mano_obra'],
    ['M. de O INSTALACIÓN DE PISOS EN MADERA', 'mano_obra'],
    ['M. de O INSTALACIÓN DE PISO PARA DECK', 'mano_obra'],
    // El catálogo solo tenía prefabricados de urbanismo/exteriores; los de interior (lagrimales,
    // sillares) son otro suministro (criterio del usuario 2026-07-25).
    ['Suministro PREFABRICADOS INTERIORES', 'suministro'],
    // Curadores, desmoldantes y aditivos: proveedor propio (químicos), no vienen con el mixer de
    // concreto ni con la formaleta (criterio del usuario 2026-07-25).
    ['Suministro ADITIVOS DE CONCRETO', 'suministro'],
    // El enchape va DESAGREGADO (pegante, material y mano de obra son compras distintas): el adhesivo
    // y la boquilla son de otro proveedor que el porcelanato (criterio del usuario 2026-07-25).
    ['Suministro PEGANTES Y BOQUILLAS', 'suministro'],
];

// 2) Cajón de sastre a retirar (crearPaquete lo reactivaría por nombre_norm: no volver a sembrarlo).
$retirar = 'M. de O INSTALACION DE PISOS';
$normRetirar = mb_substr(MaestroInsumosService::normalizar($retirar), 0, 200);

if (!$apply) {
    fwrite(STDOUT, '[DRY-RUN] crearía ' . count($nuevos) . " paquetes por oficio y retiraría «{$retirar}».\n");
    $afectados = (int) $db->query(
        'SELECT COUNT(*) FROM pdc_insumo_paquete a JOIN general_paquetes_contratacion p ON p.id = a.paquete_id WHERE p.nombre_norm = ?',
        [$normRetirar],
    )->fetchColumn();
    fwrite(STDOUT, "          {$afectados} asignaciones quedarían huérfanas y deben re-sembrarse.\n");
    exit(0);
}

$creados = 0;
$existentes = 0;
foreach ($nuevos as [$nombre, $tipo]) {
    $r = $svc->crearPaquete($nombre, $tipo, 'seed-oficio');
    if ($r['ok'] !== true) {
        fwrite(STDERR, "FALLO al crear «{$nombre}».\n");
        exit(1);
    }
    ((int) ($r['paquete']['existente'] ?? 0) === 1) ? $existentes++ : $creados++;
}

// 3) Liberar las asignaciones del cajón de sastre ANTES de desactivarlo: insumosDeVersion/resumen unen
//    el catálogo SIN filtrar `activo`, así que si no se liberan seguirían contando como «asignados» a un
//    paquete muerto y sugerencias() (filtro sin_asignar) nunca los volvería a proponer.
$liberadas = 0;
$paqueteId = $db->query('SELECT id FROM general_paquetes_contratacion WHERE nombre_norm = ?', [$normRetirar])->fetchColumn();
if ($paqueteId !== false) {
    $stmt = $db->query('DELETE FROM pdc_insumo_paquete WHERE paquete_id = ?', [(int) $paqueteId]);
    $liberadas = $stmt->rowCount();
    $db->query('UPDATE general_paquetes_contratacion SET activo = 0, updated_at = NOW() WHERE id = ?', [(int) $paqueteId]);
}

fwrite(STDOUT, "[APLICADO] paquetes por oficio: creados {$creados}, ya existían {$existentes}.\n");
fwrite(STDOUT, "           «{$retirar}» retirado (activo=0); {$liberadas} asignaciones liberadas para re-sembrado.\n");
exit(0);
