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

/**
 * Recorre las filas y compara el Estado guardado con el que producen los calculadores canonicos.
 * En dry-run NO escribe: acumula las transiciones y las devuelve.
 *
 * Usa `pg_calculate_status()` y no `LpsService`: son la misma clasificacion y su paridad la
 * vigila `tests/unit/EstadoProgramaGeneralTest.php`, asi que basta con una — y la del legacy es
 * la que declara sus umbrales como constantes con nombre.
 *
 * @return array<string, mixed>
 */
function recalcular(Database $db, bool $apply): array
{
    $filas = $db->query(
        "SELECT p.project_id, p.Consecutivo, p.Semana, p.Estado, p.Titulo, p.Ejecutado,
                p.Fecha_Inicio, p.Fecha_Fin, s.Fecha_Inicio_Sem, s.Fecha_Fin_Sem
         FROM programa_consolidado p
         LEFT JOIN semanas_activas s
           ON s.project_id = p.project_id AND s.Semana = p.Semana
         ORDER BY p.project_id, p.Consecutivo",
    );

    $transiciones = [];
    $porProyecto = [];
    $sinSemana = 0;
    $cambios = 0;
    $iguales = 0;

    foreach ($filas as $f) {
        if ($f["Fecha_Inicio_Sem"] === null) {
            // Sin semana activa no hay contra que calcular: se cuenta y se deja intacta.
            $sinSemana++;
            continue;
        }

        $nuevo = pg_calculate_status(
            $f["Titulo"], $f["Ejecutado"], $f["Fecha_Inicio"], $f["Fecha_Fin"],
            $f["Fecha_Inicio_Sem"], $f["Fecha_Fin_Sem"],
        );
        $viejo = (string) ($f["Estado"] ?? "");

        if ($nuevo === $viejo) {
            $iguales++;
            continue;
        }

        $cambios++;
        $clave = ($viejo === "" ? "(vacio)" : $viejo) . " -> " . $nuevo;
        $transiciones[$clave] = ($transiciones[$clave] ?? 0) + 1;
        $porProyecto[$f["project_id"]] = ($porProyecto[$f["project_id"]] ?? 0) + 1;

        if ($apply) {
            // Clave: la PK real (project_id, Consecutivo). Ver la cabecera de respaldar().
            $db->query(
                "UPDATE programa_consolidado SET Estado = ?
                 WHERE project_id = ? AND Consecutivo = ?",
                [$nuevo, $f["project_id"], $f["Consecutivo"]],
            );
        }
    }

    arsort($transiciones);
    ksort($porProyecto);

    return ["modo" => $apply ? "APPLY" : "DRY-RUN", "cambios" => $cambios, "iguales" => $iguales,
            "sin_semana_activa" => $sinSemana, "transiciones" => $transiciones,
            "por_proyecto" => $porProyecto];
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

// EL APPLY DEL RECALCULO ESTA BLOQUEADO EN ESTE FRENTE. Exige el si explicito del usuario
// sobre el resultado del dry-run, y ni el visto de la coordinadora ni una autorizacion
// relatada lo habilitan: la regla de gobierno del 2026-08-19 cubre publicar y excluye las
// migraciones. Quitar esta guarda es una decision del usuario, no del implementador.
if ($apply) {
    fwrite(STDERR, "\nDENEGADO: el --apply del recalculo esta bloqueado en este frente.\n"
        . "Exige el si explicito del usuario sobre el resultado del dry-run.\n");
    exit(1);
}

$r = recalcular($db, false);
echo "\n";
printf("  filas que cambiarian     %s\n", $r["cambios"]);
printf("  filas que quedan igual   %s\n", $r["iguales"]);
printf("  sin semana activa        %s\n", $r["sin_semana_activa"]);
echo "\n  transiciones:\n";
foreach ($r["transiciones"] as $k => $v) {
    printf("    %-56s %6d\n", $k, $v);
}
echo "\n  por proyecto:\n";
foreach ($r["por_proyecto"] as $k => $v) {
    printf("    proyecto %-6s %6d\n", $k, $v);
}
