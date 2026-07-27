<?php

// 20260726_pdc_v2_admite_materiales.php
// PDC v2 / Fase A3.3 — excepción explícita para que un paquete «a todo costo» absorba materiales.
//
// Decisión del usuario (grilleo 2026-07-26): «Prohibido salvo excepción marcada en el paquete».
// El razonamiento es de obra: si se contrata suministro + instalación, el material lo pone el
// contratista, así que un insumo MATERIAL del presupuesto asignado a ese mismo paquete se está
// contando dos veces. El propio usuario lo señaló con el enchape: «pegacor, pisos y MO son insumos
// separados».
//
// Pero hay paquetes que sí absorben materiales por naturaleza — la dotación de zonas comunes o de
// cocinas se compra como producto terminado, no se descompone en insumos —, y para esos la
// prohibición sería falsa. De ahí la columna en vez de una regla ciega: la excepción queda escrita,
// visible y auditable en el catálogo, y cualquier doble conteo salta a la vista.
//
// Uso:  php database/migrations/20260726_pdc_v2_admite_materiales.php [--apply]

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../src/Core/Database.php';

use App\Services\Pdc\MaestroInsumosService;

$apply = in_array('--apply', $argv, true);
$db = Database::getInstance();

// Paquetes que compran producto terminado: el material ES el alcance, no un insumo aparte.
$excepciones = [
    'Sum + Inst DOTACIÓN ZONAS COMUNES',
    'Sum + Inst DOTACIÓN COCINAS Y LAVADEROS',
    'Sum + Inst PLANTA ELÉCTRICA',
    'Sum + Inst TANQUES (ALMACENAMIENTO AGUAS LLUVIAS Y AGUA POTABLE)',
    'Sum + Inst SISTEMA DE CALENTAMIENTO AGUA',
];

$falta = (int) $db->query(
    'SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
    ['general_paquetes_contratacion', 'admite_materiales'],
)->fetchColumn() === 0;

if (!$apply) {
    fwrite(STDOUT, '[DRY-RUN] columna admite_materiales: ' . ($falta ? 'FALTA (se añadirá)' : 'ya existe') . "\n");
    fwrite(STDOUT, '          excepciones a marcar: ' . count($excepciones) . "\n");
    fwrite(STDOUT, "Ejecuta con --apply.\n");
    exit(0);
}

if ($falta) {
    $db->query(
        'ALTER TABLE general_paquetes_contratacion
         ADD COLUMN admite_materiales tinyint NOT NULL DEFAULT 0 AFTER modalidad_contratacion',
    );
}

$marcados = 0;
foreach ($excepciones as $nombre) {
    $stmt = $db->query(
        'UPDATE general_paquetes_contratacion SET admite_materiales = 1 WHERE nombre_norm = ?',
        [mb_substr(MaestroInsumosService::normalizar($nombre), 0, 200)],
    );
    $marcados += $stmt->rowCount() > 0 ? 1 : 0;
}

fwrite(STDOUT, "[APLICADO] columna lista; {$marcados} de " . count($excepciones) . " paquetes marcados como absorbedores de material.\n");
exit(0);
