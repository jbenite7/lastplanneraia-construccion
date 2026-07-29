<?php

// 20260728_pdc_v2_rama_frente.php
// PDC v2 / Fase A4.2 — EL PUENTE ENTRE PRESUPUESTO Y CRONOGRAMA.
//
// El problema medido en Da Porto (2026-07-28): de 96 paquetes que generan proceso, 85 no tienen
// frente y a 45 el motor no les ofrecía ninguna propuesta. La causa no era el umbral de parecido:
// los paquetes hablan de oficios (CIELOS RASOS, CEMENTO) y el cronograma habla de fases (ACABADOS,
// ESTRUCTURA), así que no comparten ni una palabra. Comparar sus nombres no podía funcionar.
//
// Lo que sí funciona es la RAMA del presupuesto donde viven los insumos del paquete. 25 ramas
// cubren el 100 % de los 85, y los 11 amarres que una persona ya hizo siguen exactamente ese patrón
// (ESTRUCTURA→ESTRUCTURA, MAMPOSTERIA Y REVOQUE→MAMPOSTERÍA, CARPINTERIA METALICA→VENTANERÍA...).
//
// Dos decisiones de modelo que no son obvias:
//
// 1) La correspondencia guarda el NOMBRE del nodo del cronograma, no su `unique_id`. El unique_id
//    pertenece a una obra concreta; el nombre («ESTRUCTURA», «MAMPOSTERÍA») se repite entre obras.
//    En cada proyecto se resuelve nombre → unique_id de esa obra. Sin esto, el catálogo global sería
//    imposible y cada proyecto tendría que redescubrir el mismo conocimiento.
//
// 2) El ancla puede ser una HOJA del cronograma, no solo un encabezado (`Titulo = 1`), y la rama
//    puede ser un GRUPO, no solo un subcapítulo. Ambas cosas están medidas, no supuestas:
//      · El subcapítulo CUBIERTA no tiene frente propio. Su ancla correcta es la hoja
//        «LOSA AÉREA CUBIERTA» (arranca 2027-07-27). Colgarlo del frente ESTRUCTURA (2026-08-18)
//        daría 11 meses y 9 días de adelanto: se contrataría la cubierta casi un año antes de que
//        exista la losa sobre la que va.
//      · REVOQUES es el grupo 01.05.06 y ancla en «REVOQUE TRADICIONAL»; heredar el frente de su
//        subcapítulo padre (MAMPOSTERÍA) lo adelantaría un mes. IMPERMEABILIZACION FILTROS (grupo
//        01.06.02) ancla en ESTRUCTURA, casi un año antes que su hermano IMPERMEABILIZACIONES.
//    Por eso la tabla se llama `rama_frente` y no `subcapitulo_frente`, y por eso el ancla es un
//    nombre de nodo cualquiera y no «un frente».
//
// La siembra sale de `database/seeds/sembrado_ramas_frentes.json`: 26 reglas curadas con criterio de
// obra por la sesión de amarre insumo↔cronograma. Nacen `confirmado_humano = 1` porque las confirmó
// una persona, no un algoritmo. Las 8 ramas que casan solas por nombre (PRELIMINARES, ESTRUCTURA,
// MAMPOSTERIA Y REVOQUE, ...) NO se siembran a propósito: el motor las resuelve en caliente por
// coincidencia exacta, y guardarlas sería confundir memoria con conocimiento.
//
// Uso:  php database/migrations/20260728_pdc_v2_rama_frente.php [--apply]

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../src/Core/Database.php';

$apply = in_array('--apply', $argv, true);
$db = Database::getInstance();

$existeTabla = static function (Database $db, string $t): bool {
    return (int) $db->query(
        'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
        [$t],
    )->fetchColumn() > 0;
};
$tipoColumna = static function (Database $db, string $t, string $c): ?string {
    $r = $db->query(
        'SELECT COLUMN_TYPE FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
        [$t, $c],
    )->fetchColumn();
    return $r === false ? null : (string) $r;
};

$pasos = [];

// ---------------------------------------------------------------- 1. catálogo global
// Sin `project_id`: es conocimiento de la empresa, no de una obra (docs/global-tables-architecture.md).
if (!$existeTabla($db, 'general_rama_frente')) {
    $pasos[] = ['crear general_rama_frente', "CREATE TABLE general_rama_frente (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        rama_norm VARCHAR(255) NOT NULL COMMENT 'descripcion normalizada del subcapitulo o grupo',
        ancla_nombre VARCHAR(500) NOT NULL COMMENT 'nombre del nodo del cronograma (encabezado u hoja)',
        confirmado_humano TINYINT(1) NOT NULL DEFAULT 0,
        nota VARCHAR(500) NOT NULL DEFAULT '',
        creado_por VARCHAR(100) NOT NULL DEFAULT '',
        actualizado_por VARCHAR(100) NOT NULL DEFAULT '',
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uk_rama (rama_norm)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"];
}

// ---------------------------------------------------------------- 2. excepción por proyecto
// Gana sobre la global. Índice liderado por project_id, como exige la arquitectura de tablas
// operativas multiproyecto.
if (!$existeTabla($db, 'pdc_rama_frente')) {
    $pasos[] = ['crear pdc_rama_frente', "CREATE TABLE pdc_rama_frente (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        project_id INT NOT NULL,
        rama_norm VARCHAR(255) NOT NULL,
        ancla_nombre VARCHAR(500) NOT NULL,
        confirmado_humano TINYINT(1) NOT NULL DEFAULT 1,
        nota VARCHAR(500) NOT NULL DEFAULT '',
        asignado_por VARCHAR(100) NOT NULL DEFAULT '',
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uk_proj_rama (project_id, rama_norm)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"];
}

// ---------------------------------------------------------------- 3. la capa nueva en el amarre
// `origen` es un enum cerrado; sin este ALTER, un amarre nacido de una correspondencia se guardaría
// como 'humano' y el acierto del motor quedaría sin acreditar (A3.3 fijó que origen y confirmación
// son ortogonales).
$tipo = $tipoColumna($db, 'pdc_paquete_frente', 'origen');
if ($tipo !== null && !str_contains($tipo, 'correspondencia')) {
    $pasos[] = ['ampliar pdc_paquete_frente.origen', "ALTER TABLE pdc_paquete_frente
        MODIFY COLUMN origen ENUM('similitud','rama','correspondencia','humano') NOT NULL"];
}

// ---------------------------------------------------------------- 4. correcciones del motor
// `pdc_correcciones_motor` no sirve: está atada a (descripcion_norm, unidad) de insumos. Esta es su
// gemela para frentes, y es lo único que permitirá medir si el motor acierta.
if (!$existeTabla($db, 'pdc_correcciones_frente')) {
    $pasos[] = ['crear pdc_correcciones_frente', "CREATE TABLE pdc_correcciones_frente (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        project_id INT NOT NULL,
        paquete_id BIGINT NOT NULL,
        unique_id_sugerido INT NULL,
        unique_id_elegido INT NOT NULL,
        capa_sugerida VARCHAR(20) NOT NULL DEFAULT '',
        confianza_sugerida VARCHAR(10) NULL,
        usuario VARCHAR(100) NOT NULL DEFAULT '',
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_proj_paquete (project_id, paquete_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"];
}

echo "== A4.2 · puente rama del presupuesto -> nodo del cronograma ==\n";
echo $apply ? "MODO: APLICAR\n\n" : "MODO: SIMULACION (dry-run). Añade --apply para escribir.\n\n";

foreach ($pasos as [$titulo, $sql]) {
    echo "- {$titulo}\n";
    if ($apply) {
        $db->query($sql);
        echo "  OK\n";
    }
}
if ($pasos === []) {
    echo "- el esquema ya estaba al dia; nada que crear\n";
}

// ---------------------------------------------------------------- 5. siembra
$ruta = __DIR__ . '/../seeds/sembrado_ramas_frentes.json';
if (!is_file($ruta)) {
    echo "\n! No se encontro {$ruta}: se omite la siembra.\n";
    exit(0);
}
/** @var array{reglas: array<string, array{frente: string, alcance: string, nota?: string}>} $seed */
$seed = json_decode((string) file_get_contents($ruta), true, 512, JSON_THROW_ON_ERROR);
$reglas = $seed['reglas'] ?? [];
echo "\n== siembra: " . count($reglas) . " correspondencias curadas ==\n";

$nuevas = 0;
$yaEstaban = 0;
foreach ($reglas as $rama => $r) {
    $existe = $apply && $existeTabla($db, 'general_rama_frente')
        ? (int) $db->query('SELECT COUNT(*) FROM general_rama_frente WHERE rama_norm = ?', [$rama])->fetchColumn() > 0
        : false;
    if ($existe) {
        $yaEstaban++;
        continue;
    }
    $nuevas++;
    if ($apply) {
        $db->query(
            'INSERT INTO general_rama_frente (rama_norm, ancla_nombre, confirmado_humano, nota, creado_por, actualizado_por)
             VALUES (?, ?, 1, ?, ?, ?)
             ON DUPLICATE KEY UPDATE ancla_nombre = VALUES(ancla_nombre), nota = VALUES(nota),
                confirmado_humano = 1, actualizado_por = VALUES(actualizado_por)',
            [$rama, $r['frente'], mb_substr((string) ($r['nota'] ?? ''), 0, 500), 'migracion-a42', 'migracion-a42'],
        );
    }
    printf("  %-34s -> %s\n", mb_substr($rama, 0, 33), $r['frente']);
}
printf("\nresumen: %d a insertar · %d ya estaban\n", $nuevas, $yaEstaban);
echo $apply ? "siembra aplicada.\n" : "(simulacion: no se escribio nada)\n";
