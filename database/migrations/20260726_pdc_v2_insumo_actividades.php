<?php

// 20260726_pdc_v2_insumo_actividades.php
// PDC v2 / Fase A3.3 — MAPA COMPLETO insumo ↔ actividades que lo consumen.
//
// Hasta ahora el motor solo miraba la actividad DOMINANTE de cada insumo y la calculaba al vuelo.
// Dos razones para persistir el mapa entero:
//
//  1. Seguimiento lo exige (decisión del usuario, 2026-07-26): «es obligatorio que se tengan
//     mapeadas todas las actividades entre las que se reparte, porque en la fase de seguimiento se
//     necesitan las fechas de la que arranca primero». Para una orden de compra el plan garantiza la
//     PRIMERA entrega, y la primera entrega la fija la primera actividad del cronograma que consume
//     el insumo — no la de mayor cuantía.
//  2. La dominante es débil más veces de lo que parece: en DAPORTO v292, 99 insumos (53 % del valor)
//     se reparten entre varias actividades y 39 de ellos no tienen ninguna que concentre el 60 %.
//     Con el mapa completo el motor puede decir «esto se reparte» en vez de elegir por décimas.
//
// `unique_id` nace NULL: es el amarre a `programa_consolidado` que llenará A4. Se deja la columna
// desde ya para que A4 solo tenga que rellenarla, no migrar de nuevo.
//
// Uso:  php database/migrations/20260726_pdc_v2_insumo_actividades.php [--apply]

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../src/Core/Database.php';

$apply = in_array('--apply', $argv, true);
$db = Database::getInstance();

$existe = (int) $db->query(
    'SELECT COUNT(*) FROM information_schema.TABLES
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
    ['pdc_insumo_actividades'],
)->fetchColumn() > 0;

if (!$apply) {
    fwrite(STDOUT, '[DRY-RUN] tabla pdc_insumo_actividades: ' . ($existe ? 'ya existe' : 'FALTA (se creará)') . "\n");
    fwrite(STDOUT, "Ejecuta con --apply.\n");
    exit(0);
}

if (!$existe) {
    $db->query(
        "CREATE TABLE pdc_insumo_actividades (
            id bigint NOT NULL AUTO_INCREMENT,
            project_id int NOT NULL,
            version_id bigint NOT NULL,
            descripcion_norm varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
            unidad varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
            item_id bigint NOT NULL,
            codigo varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
            actividad varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
            cantidad decimal(18,4) NOT NULL DEFAULT 0,
            valor decimal(18,2) NOT NULL DEFAULT 0,
            unique_id bigint NULL DEFAULT NULL,
            PRIMARY KEY (id),
            KEY idx_pia_insumo (project_id, version_id, descripcion_norm(150), unidad),
            KEY idx_pia_item (project_id, item_id),
            KEY idx_pia_unique (unique_id)
         ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    );
}

fwrite(STDOUT, "[APLICADO] pdc_insumo_actividades lista (unique_id queda NULL hasta A4).\n");
exit(0);
