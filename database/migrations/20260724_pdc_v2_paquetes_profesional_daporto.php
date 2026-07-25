<?php

// 20260724_pdc_v2_paquetes_profesional_daporto.php
// PDC v2 / Fase A3.1: paquetes propios del profesional de presupuestos (del empaquetado real de
// DAPORTO en la app de Tomás) que no estaban en el catálogo de 188. Idempotente (crearPaquete dedupe
// por nombre_norm). Los overrides de database/seeds/sembrado_ia_overrides.json los referencian por nombre.
// Uso:  php database/migrations/20260724_pdc_v2_paquetes_profesional_daporto.php --apply

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../src/Core/Database.php';

use App\Services\Pdc\PaquetesService;

$apply = in_array('--apply', $argv, true);
$svc = new PaquetesService(Database::getInstance());

$paquetes = [
    ['Suministro MORTEROS', 'suministro'],
    ['Suministro ALIGERANTES LOSAS', 'suministro'],
    ['Sum + Inst RED ELECTRICA', 'a_todo_costo'],
    ['Sum + Inst MESONES', 'a_todo_costo'],
    ['Sum + Inst TORRE GRUA', 'a_todo_costo'],
    ['Sum + Inst REVOQUE SECO', 'a_todo_costo'],
    ['M. de O INSTALACION DE PISOS', 'mano_obra'],
];

if (!$apply) {
    fwrite(STDOUT, '[DRY-RUN] crearía/verificaría ' . count($paquetes) . " paquetes del profesional. Ejecuta con --apply.\n");
    exit(0);
}
$creados = 0; $existentes = 0;
foreach ($paquetes as [$nombre, $tipo]) {
    $r = $svc->crearPaquete($nombre, $tipo, 'seed-profesional');
    if ($r['ok'] !== true) { fwrite(STDERR, "FALLO: {$nombre}\n"); continue; }
    ((int) ($r['paquete']['existente'] ?? 0) === 1) ? $existentes++ : $creados++;
}
fwrite(STDOUT, "[APLICADO] creados {$creados}, ya existían {$existentes}.\n");
exit(0);
