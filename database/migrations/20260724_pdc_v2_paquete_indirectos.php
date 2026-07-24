<?php

// 20260724_pdc_v2_paquete_indirectos.php
// PDC v2 / Fase A3.1: paquete «Indirectos / Administración» para insumos no empaquetables
// (imprevistos de obra, nómina, dotación, papelería, aseo/vigilancia, honorarios, admin).
// Idempotente: reusa PaquetesService::crearPaquete (dedupe por nombre_norm).
// Uso:  php database/migrations/20260724_pdc_v2_paquete_indirectos.php --apply

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../src/Core/Database.php';

use App\Services\Pdc\PaquetesService;

$apply = in_array('--apply', $argv, true);
$svc = new PaquetesService(Database::getInstance());

if (!$apply) {
    fwrite(STDOUT, "[DRY-RUN] crearía/verificaría el paquete «Indirectos / Administración» (consumibles). Ejecuta con --apply.\n");
    exit(0);
}

$r = $svc->crearPaquete('Indirectos / Administración', 'consumibles', 'seed-tomas');
if ($r['ok'] !== true) {
    fwrite(STDERR, "FALLO al crear el paquete Indirectos.\n");
    exit(1);
}
$estado = ((int) ($r['paquete']['existente'] ?? 0) === 1) ? 'ya existía' : 'creado';
fwrite(STDOUT, "[APLICADO] Paquete «{$r['paquete']['nombre']}» id={$r['paquete']['id']} ({$estado}).\n");
exit(0);
