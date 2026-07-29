<?php
/**
 * Deuda de datos A3.2 — el `tipo_negociacion` de los buckets no contratables era falso.
 *
 * `tipo_negociacion` responde QUÉ se compra; `modalidad_contratacion`, CÓMO. Cuando A3.2 partió el
 * bucket «Indirectos / Administración» por naturaleza, los pedazos que no se le compran a nadie
 * (nómina propia, imprevistos, provisiones) heredaron el tipo `consumibles` del padre. Es falso: a
 * la nómina no se le compran consumibles. El único consumible de verdad del catálogo —«Ferretería y
 * consumibles de obra»— está bien tipado como `suministro` (sí se suministra; solo que a demanda,
 * sin proceso), así que la regla NO es «modalidad sin proceso» sino estrictamente `no_contratable`.
 *
 * Ninguno de los cuatro valores del enum describía ese caso, así que se agrega un quinto:
 * `no_aplica`. El campo no admite NULL y se lee en la UI, de modo que «vacío» no era una opción.
 *
 * Cero regresión medida: los dos únicos puntos del motor que leen `tipo_negociacion`
 * (`PaquetesService::tipoRecursoAdmitido()` y `::resolverPaquete()`) hacen bypass ANTES por
 * `MODALIDADES_SIN_PROCESO`, así que para estos paquetes el campo hoy no decide nada. El cambio es
 * inerte para el motor y para el plan de fechas (que excluye lo no contratable por modalidad).
 *
 * PENDIENTE deliberado: `PaquetesService::TIPOS` no lista `no_aplica`, así que el formulario de
 * crear paquete todavía no lo ofrece. Se dejó fuera de esta migración porque ese archivo está en
 * manos de otra tarea en curso; no afecta a los paquetes ya existentes, que es lo que aquí se corrige.
 *
 * Idempotente: el MODIFY del enum se puede repetir y el UPDATE ya no encuentra filas la segunda vez.
 *
 * Uso:  php database/migrations/20260728_pdc_v2_tipo_no_aplica.php [--apply]
 */

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../src/Core/Database.php';

$apply = in_array('--apply', $argv, true);
$db = Database::getInstance();

const TIPO_DESTINO = 'no_aplica';
const ENUM_NUEVO = "enum('a_todo_costo','mano_obra','suministro','consumibles','no_aplica')";

echo ($apply ? '=== APLICANDO' : '=== DRY-RUN (usa --apply para escribir)') . " ===\n\n";

// ── 1. El enum admite el valor nuevo ──────────────────────────────────────────────────────────
$col = $db->query(
    "SELECT COLUMN_TYPE FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'general_paquetes_contratacion'
       AND COLUMN_NAME = 'tipo_negociacion'",
)->fetchColumn();

if ($col === false) {
    fwrite(STDERR, "!! no existe general_paquetes_contratacion.tipo_negociacion\n");
    exit(1);
}

$yaTiene = str_contains((string) $col, "'" . TIPO_DESTINO . "'");
echo "-- enum actual: {$col}\n";
if ($yaTiene) {
    echo "  · ya admite '" . TIPO_DESTINO . "', no se altera la columna\n";
} else {
    echo "  · se agrega '" . TIPO_DESTINO . "' al enum\n";
    if ($apply) {
        $db->query(
            'ALTER TABLE general_paquetes_contratacion
             MODIFY tipo_negociacion ' . ENUM_NUEVO . " NOT NULL DEFAULT 'a_todo_costo'",
        );
    }
}

// ── 2. Los paquetes no contratables pasan a 'no_aplica' ───────────────────────────────────────
// La regla es la modalidad, no una lista de nombres: cualquier paquete que no se le compre a nadie
// —hoy o en otro proyecto de AIA— queda bien tipado sin tener que volver aquí.
$afectados = $db->query(
    "SELECT id, nombre, tipo_negociacion, activo
     FROM general_paquetes_contratacion
     WHERE modalidad_contratacion = 'no_contratable' AND tipo_negociacion <> ?
     ORDER BY id",
    [TIPO_DESTINO],
)->fetchAll(PDO::FETCH_ASSOC);

echo "\n-- paquetes no contratables con tipo que no corresponde --\n";
if ($afectados === []) {
    echo "  · ninguno (ya están todos en '" . TIPO_DESTINO . "')\n";
}
foreach ($afectados as $p) {
    $estado = ((int) $p['activo'] === 1) ? 'activo' : 'retirado';
    echo sprintf("  · «%s» (#%d, %s): %s → %s\n", $p['nombre'], $p['id'], $estado, $p['tipo_negociacion'], TIPO_DESTINO);
}

if ($apply && $afectados !== []) {
    $db->query(
        "UPDATE general_paquetes_contratacion
         SET tipo_negociacion = ?, updated_at = NOW()
         WHERE modalidad_contratacion = 'no_contratable' AND tipo_negociacion <> ?",
        [TIPO_DESTINO, TIPO_DESTINO],
    );
}

echo "\n" . ($apply ? 'corregidos: ' : 'a corregir: ') . count($afectados) . "\n";

// ── 3. Control: nadie más debería quedar con el tipo viejo si no es un consumible de verdad ───
if ($apply) {
    $quedan = $db->query(
        "SELECT id, nombre, tipo_negociacion, modalidad_contratacion
         FROM general_paquetes_contratacion
         WHERE tipo_negociacion = 'consumibles' AND activo = 1 ORDER BY id",
    )->fetchAll(PDO::FETCH_ASSOC);
    echo "\n-- siguen tipados 'consumibles' (activos) --\n";
    if ($quedan === []) {
        echo "  · ninguno\n";
    }
    foreach ($quedan as $q) {
        echo sprintf("  · «%s» (#%d) · modalidad %s\n", $q['nombre'], $q['id'], $q['modalidad_contratacion']);
    }
}

exit(0);
