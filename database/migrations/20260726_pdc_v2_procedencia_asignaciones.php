<?php

// 20260726_pdc_v2_procedencia_asignaciones.php
// PDC v2 / Fase A3.3 — PROCEDENCIA de cada asignación insumo→paquete + registro de correcciones.
//
// Hasta A3.2, `pdc_insumo_paquete` solo guardaba `asignado_por` y `updated_at`: en el momento de
// aplicar el sembrado se perdía si la fila la puso una regla, un override curado o el dedo de un
// humano, y con qué argumento. Consecuencias medidas en DAPORTO v292:
//   · no se puede auditar por qué se compró lo que se compró (lo va a pedir Seguimiento),
//   · no se puede medir si el motor acierta — la cobertura del 82 % mide sobre todo trabajo manual
//     (el 71,4 % del valor asignado venía de la lista curada a mano),
//   · un re-sembrado no sabe qué puede tocar y qué es decisión humana intocable.
//
// Este DDL añade esa memoria:
//   · origen             — capa que produjo la fila; 'humano' cuando la puso una persona.
//   · confianza          — la del motor (alta/media/baja). NULL en las humanas: no son una apuesta.
//   · evidencia          — el argumento legible que se le mostró al usuario.
//   · confirmado_humano  — 1 cuando una persona la puso o la aceptó. El re-sembrado NUNCA pisa estas.
//
// Y crea `pdc_correcciones_motor`: cada vez que alguien mueve de paquete algo que propuso el motor
// se registra el par (sugerido → elegido) con la capa que falló. Es la señal que alimenta la tasa de
// acierto y la cola de candidatas a regla.
//
// BACKFILL deliberado: las filas existentes quedan origen='humano', confirmado_humano=1. Es la
// verdad — el usuario revisó y aceptó ese sembrado en A3.1 —, y además protege ese trabajo de
// cualquier re-sembrado posterior. Cero regresión.
//
// Uso:  php database/migrations/20260726_pdc_v2_procedencia_asignaciones.php [--apply]

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../src/Core/Database.php';

$apply = in_array('--apply', $argv, true);
$db = Database::getInstance();

$existeColumna = static function (Database $db, string $tabla, string $columna): bool {
    return (int) $db->query(
        'SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
        [$tabla, $columna],
    )->fetchColumn() > 0;
};
$existeTabla = static function (Database $db, string $tabla): bool {
    return (int) $db->query(
        'SELECT COUNT(*) FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
        [$tabla],
    )->fetchColumn() > 0;
};

$faltaOrigen = !$existeColumna($db, 'pdc_insumo_paquete', 'origen');
$faltaTabla = !$existeTabla($db, 'pdc_correcciones_motor');
$porBackfill = $faltaOrigen
    ? (int) $db->query('SELECT COUNT(*) FROM pdc_insumo_paquete')->fetchColumn()
    : (int) $db->query("SELECT COUNT(*) FROM pdc_insumo_paquete WHERE origen = '' OR origen IS NULL")->fetchColumn();

if (!$apply) {
    fwrite(STDOUT, '[DRY-RUN] columnas de procedencia: ' . ($faltaOrigen ? 'FALTAN (se añadirán)' : 'ya existen') . "\n");
    fwrite(STDOUT, '          tabla pdc_correcciones_motor: ' . ($faltaTabla ? 'FALTA (se creará)' : 'ya existe') . "\n");
    fwrite(STDOUT, "          filas a marcar como humano/confirmado: {$porBackfill}\n");
    fwrite(STDOUT, "Ejecuta con --apply.\n");
    exit(0);
}

if ($faltaOrigen) {
    $db->query(
        "ALTER TABLE pdc_insumo_paquete
         ADD COLUMN origen enum('ia','exacta','reglas','tokens','indirectos','agrupacion','humano')
             NOT NULL DEFAULT 'humano' AFTER omitido,
         ADD COLUMN confianza enum('alta','media','baja') NULL DEFAULT NULL AFTER origen,
         ADD COLUMN evidencia varchar(500) NOT NULL DEFAULT '' AFTER confianza,
         ADD COLUMN confirmado_humano tinyint NOT NULL DEFAULT 1 AFTER evidencia",
    );
    // El default 1 vale para lo ya existente (trabajo revisado por el usuario); a partir de aquí el
    // servicio escribe el valor correcto en cada INSERT, por eso el default no dicta el futuro.
    $db->query("UPDATE pdc_insumo_paquete SET origen = 'humano', confirmado_humano = 1 WHERE origen = ''");
}

if ($faltaTabla) {
    $db->query(
        "CREATE TABLE pdc_correcciones_motor (
            id bigint NOT NULL AUTO_INCREMENT,
            project_id int NOT NULL,
            descripcion_norm varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
            unidad varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
            paquete_sugerido bigint NULL,
            paquete_elegido bigint NULL,
            capa_sugerida varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
            usuario varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
            created_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY idx_pcm_proyecto (project_id, descripcion_norm(150), unidad),
            KEY idx_pcm_capa (capa_sugerida)
         ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    );
}

$humanas = (int) $db->query("SELECT COUNT(*) FROM pdc_insumo_paquete WHERE confirmado_humano = 1")->fetchColumn();
fwrite(STDOUT, "[APLICADO] procedencia lista; {$humanas} asignaciones quedan marcadas como decisión humana confirmada.\n");
exit(0);
