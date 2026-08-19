<?php

/**
 * Recalculo de la columna `Estado` de `programa_consolidado` con los calculadores canonicos.
 *
 * DRY-RUN POR DEFECTO. El `--apply` del recalculo exige el si explicito del usuario sobre el
 * resultado del dry-run: ni el visto de la coordinadora ni una autorizacion relatada lo
 * habilitan. La regla de gobierno del 2026-08-19 cubre publicar en `main` y excluye por texto
 * propio las migraciones.
 *
 * Modos:
 *   php database/migrations/20260819_recalculo_estados.php                      dry-run completo
 *   php database/migrations/20260819_recalculo_estados.php --solo-respaldo      informa el respaldo
 *   php database/migrations/20260819_recalculo_estados.php --solo-respaldo --apply   lo crea
 *   php database/migrations/20260819_recalculo_estados.php --restaurar --apply  deshace el apply
 *   php database/migrations/20260819_recalculo_estados.php --apply              RECALCULA (bloqueado)
 */

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../src/Core/Database.php';
require_once __DIR__ . '/../../src/Legacy/estado_programa_general.php';

const TABLA_RESPALDO = 'programa_consolidado_estado_respaldo_20260819';

$apply = in_array('--apply', $argv, true);
$soloRespaldo = in_array('--solo-respaldo', $argv, true);
$restaurar = in_array('--restaurar', $argv, true);
$db = Database::getInstance();

/**
 * Respalda SOLO lo necesario para restaurar la columna: la clave primaria real y el Estado.
 * No copia la tabla entera porque restaurar no necesita mas, y un respaldo pequeno es un
 * respaldo que se puede verificar entero.
 *
 * La clave es `(project_id, Consecutivo)`, la PK real. NO `unique_id`, que esta vacio en 7.686
 * filas: `(project_id, unique_id, Semana)` agrupa 704 filas en una sola en el proyecto 65.
 *
 * @return array<string, mixed>
 */
function respaldar(Database $db, bool $apply): array
{
    $existe = (int) $db->query(
        "SELECT COUNT(*) FROM information_schema.tables
         WHERE table_schema = DATABASE() AND table_name = ?",
        [TABLA_RESPALDO],
    )->fetchColumn();

    $origen = (int) $db->query("SELECT COUNT(*) FROM programa_consolidado")->fetchColumn();

    if (!$apply) {
        return ['modo' => 'DRY-RUN', 'respaldo_ya_existe' => (bool) $existe,
                'filas_a_respaldar' => $origen];
    }

    $db->query('DROP TABLE IF EXISTS ' . TABLA_RESPALDO);
    $db->query(
        'CREATE TABLE ' . TABLA_RESPALDO . ' AS
         SELECT project_id, Consecutivo, Semana, Estado FROM programa_consolidado',
    );
    $copiadas = (int) $db->query('SELECT COUNT(*) FROM ' . TABLA_RESPALDO)->fetchColumn();

    return ['modo' => 'APPLY', 'filas_origen' => $origen, 'filas_respaldadas' => $copiadas,
            'coinciden' => $origen === $copiadas];
}

/**
 * Cuenta en cuantas filas difiere `programa_consolidado` del respaldo.
 *
 * Compara con `<=>` y no con `=`: 7.705 filas tienen `Estado` vacio o nulo, y `NULL = NULL` es
 * NULL, no verdadero. Con `=` esas filas no se comparan y el respaldo parece correcto sin serlo.
 */
function diferenciasContraRespaldo(Database $db): int
{
    return (int) $db->query(
        'SELECT COUNT(*) FROM programa_consolidado p
         JOIN ' . TABLA_RESPALDO . ' r
           ON r.project_id = p.project_id AND r.Consecutivo = p.Consecutivo
         WHERE NOT (p.Estado <=> r.Estado)',
    )->fetchColumn();
}

/** Devuelve la columna `Estado` al contenido del respaldo. @return array<string, mixed> */
function restaurar(Database $db, bool $apply): array
{
    $difieren = diferenciasContraRespaldo($db);

    if (!$apply) {
        return ['modo' => 'DRY-RUN', 'filas_que_se_restaurarian' => $difieren];
    }

    $db->query(
        'UPDATE programa_consolidado p
         JOIN ' . TABLA_RESPALDO . ' r
           ON r.project_id = p.project_id AND r.Consecutivo = p.Consecutivo
         SET p.Estado = r.Estado
         WHERE NOT (p.Estado <=> r.Estado)',
    );

    return ['modo' => 'APPLY', 'filas_restauradas' => $difieren,
            'diferencias_tras_restaurar' => diferenciasContraRespaldo($db)];
}

echo "=== recalculo de estados · " . ($apply ? 'APPLY' : 'DRY-RUN') . " ===\n\n";

if ($restaurar) {
    foreach (restaurar($db, $apply) as $k => $v) {
        printf("  %-32s %s\n", $k, is_bool($v) ? ($v ? 'si' : 'no') : $v);
    }
    exit(0);
}

foreach (respaldar($db, $apply && $soloRespaldo) as $k => $v) {
    printf("  respaldo.%-22s %s\n", $k, is_bool($v) ? ($v ? 'si' : 'no') : $v);
}

if ($soloRespaldo) {
    exit(0);
}

echo "\n  (el recalculo llega en la Task 3; este script todavia no lo implementa)\n";
