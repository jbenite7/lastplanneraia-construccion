<?php

declare(strict_types=1);

/**
 * El auto-match vivía sólo dentro de `generarVinculos()`, que se dispara desde el lado del
 * presupuesto. Cualquier escritura del lado del maestro —importar el catálogo de SINCO, reactivar un
 * insumo retirado— añadía la fila que hacía resoluble un pendiente, y nadie volvía a mirar.
 *
 * La asimetría estaba en el propio código: `desactivar()` sí propagaba (devolvía los `auto` a
 * `pendiente`, y sin filtro de proyecto), pero añadir no propagaba nada. Quitar se enteraba, poner no.
 *
 * Reportado por el dueño del producto el 2026-07-30: «cuando se carga el maestro desde SINCO, no se
 * recalculan los pendientes por vincular».
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';

use App\Services\Pdc\MaestroInsumosService;

const PDC_RE_PROJECT = 999940;
const PDC_RE_MARCA = 'test-reenganche';

$failures = [];
$assert = static function (bool $condition, string $message) use (&$failures): void {
    if ($condition) { fwrite(STDOUT, "PASS: {$message}\n"); return; }
    $failures[] = $message;
    fwrite(STDERR, "FAIL: {$message}\n");
};

$db = Database::getInstance();

$limpiar = static function () use ($db): void {
    $db->query('DELETE FROM pdc_insumo_vinculos WHERE project_id = ?', [PDC_RE_PROJECT]);
    $db->query('DELETE FROM pdc_presupuesto_versiones WHERE project_id = ?', [PDC_RE_PROJECT]);
    $db->query('DELETE FROM general_maestro_insumos WHERE creado_por = ?', [PDC_RE_MARCA]);
};
$limpiar();

// Los vínculos cuelgan de una versión por clave foránea, así que hace falta una de verdad.
$db->query(
    "INSERT INTO pdc_presupuesto_versiones
        (project_id, version_label, version_numero, archivo_nombre, archivo_hash, total_actividades,
         total_insumos, costo_total, activa, obsoleta, importado_por, created_at)
     VALUES (?, 'Versión de prueba', 1, 'reenganche.xlsx', REPEAT('0', 64), 0, 0, 0, 1, 0, ?, NOW())",
    [PDC_RE_PROJECT, PDC_RE_MARCA],
);
$versionId = (int) $db->lastInsertId();

$servicio = new MaestroInsumosService($db);

/** Un vínculo pendiente, como los que deja `generarVinculos()` cuando no encuentra el insumo. */
$sembrarPendiente = static function (string $norm, string $unidad) use ($db, $versionId): int {
    $db->query(
        "INSERT INTO pdc_insumo_vinculos
            (project_id, version_id, descripcion_original, descripcion_norm, unidad, tipo_insumo, cantidad_total, valor_total, apariciones, estado)
         VALUES (?, ?, ?, ?, ?, 'material', 1, 1000, 1, 'pendiente')",
        [PDC_RE_PROJECT, $versionId, $norm, $norm, $unidad],
    );
    return (int) $db->lastInsertId();
};

/** Una fila del catálogo, como la que crea el import de SINCO. */
$sembrarMaestro = static function (string $norm, string $unidad, int $activo = 1) use ($db): int {
    $db->query(
        'INSERT INTO general_maestro_insumos
            (descripcion, descripcion_norm, unidad, tipo_insumo, activo, creado_por, created_at)
         VALUES (?, ?, ?, ?, ?, ?, NOW())',
        [$norm, $norm, $unidad, 'material', $activo, PDC_RE_MARCA],
    );
    return (int) $db->lastInsertId();
};

$estado = static function (int $vinculoId) use ($db): string {
    return (string) $db->query('SELECT estado FROM pdc_insumo_vinculos WHERE id = ?', [$vinculoId])->fetchColumn();
};

try {
    // --- El caso que se reportó -------------------------------------------------------------------
    $v = $sembrarPendiente('ZZREENGANCHE CEMENTO GRIS TIPO I', 'KG');
    $assert($estado($v) === 'pendiente', 'Sembrado: el vínculo arranca en pendiente, sin insumo que le case.');

    $reenganchados = $servicio->reengancharPendientes();
    $assert($reenganchados === 0, 'Sin insumo en el catálogo no engancha nada: no inventa parejas.');
    $assert($estado($v) === 'pendiente', 'Y el vínculo sigue pendiente, que es lo correcto.');

    // Llega el maestro (esto es lo que hace el import de SINCO: escribe en general_maestro_insumos).
    $m = $sembrarMaestro('ZZREENGANCHE CEMENTO GRIS TIPO I', 'KG');

    $reenganchados = $servicio->reengancharPendientes();
    $assert($reenganchados === 1, 'Con el insumo ya en el catálogo, el re-enganche lo resuelve.');
    $assert($estado($v) === 'auto', 'El vínculo pasa a auto: es exactamente lo que fallaba.');
    $maestroId = (int) $db->query('SELECT maestro_id FROM pdc_insumo_vinculos WHERE id = ?', [$v])->fetchColumn();
    $assert($maestroId === $m, 'Y queda apuntando al insumo correcto, no a cualquiera.');

    // --- Idempotencia ----------------------------------------------------------------------------
    $assert($servicio->reengancharPendientes() === 0, 'Correrlo dos veces no vuelve a tocar lo ya enganchado.');

    // --- Lo confirmado por una persona no se pisa ------------------------------------------------
    $v2 = $sembrarPendiente('ZZREENGANCHE ARENA DE RIO', 'M3');
    $otro = $sembrarMaestro('ZZREENGANCHE ARENA DE RIO', 'M3');
    $db->query("UPDATE pdc_insumo_vinculos SET estado = 'confirmado', maestro_id = ? WHERE id = ?", [$otro, $v2]);
    $servicio->reengancharPendientes();
    $assert($estado($v2) === 'confirmado', 'Un vínculo confirmado es una decisión humana y el re-enganche no la toca.');

    // --- Un insumo retirado no engancha ----------------------------------------------------------
    $v3 = $sembrarPendiente('ZZREENGANCHE MALLA ELECTROSOLDADA', 'M2');
    $retirado = $sembrarMaestro('ZZREENGANCHE MALLA ELECTROSOLDADA', 'M2', 0);
    $assert($servicio->reengancharPendientes() === 0, 'Un insumo con activo=0 no engancha: retirado es retirado.');
    $assert($estado($v3) === 'pendiente', 'El vínculo sigue pendiente mientras su insumo esté retirado.');

    // --- Y reactivar sí lo engancha, que es la otra mitad de la asimetría ------------------------
    $r = $servicio->reactivar($retirado, PDC_RE_MARCA);
    $assert(($r['ok'] ?? false) === true, 'Reactivar responde ok.');
    $assert(($r['reenganchados'] ?? 0) === 1, 'Reactivar informa de cuántos vínculos volvió a enganchar.');
    $assert($estado($v3) === 'auto', 'Reactivar engancha el pendiente: simétrico a desactivar, que lo soltaba.');

    // --- El acotado por proyecto sigue existiendo para quien lo necesite -------------------------
    $v4 = $sembrarPendiente('ZZREENGANCHE ADITIVO ACELERANTE', 'LT');
    $sembrarMaestro('ZZREENGANCHE ADITIVO ACELERANTE', 'LT');
    $assert($servicio->reengancharPendientes(PDC_RE_PROJECT + 1) === 0, 'Acotado a otro proyecto no toca este.');
    $assert($estado($v4) === 'pendiente', 'Y el vínculo del proyecto ajeno se queda como estaba.');
    $assert($servicio->reengancharPendientes(PDC_RE_PROJECT) === 1, 'Acotado al suyo, sí lo engancha.');
} finally {
    $limpiar();
}

if ($failures !== []) {
    fwrite(STDERR, "\n" . count($failures) . " fallo(s).\n");
    exit(1);
}
fwrite(STDOUT, "\nOK\n");
