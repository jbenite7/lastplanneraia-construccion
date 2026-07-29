<?php
/**
 * Deuda de datos A1.8 — marca las versiones de presupuesto que importó el parser defectuoso.
 *
 * Antes del fix A1.8 el importador ignoraba la columna `Cant APU` (el coeficiente de consumo del
 * insumo dentro del APU) y calculaba la cantidad como `Rend × cantidad_actividad`. Como en el export
 * real de AIA `Rend` es siempre 1, el efecto es que cada insumo quedó multiplicado por `1/Cant APU`:
 * los de coeficiente pequeño se dispararon (un insumo con `Cant APU = 0,002` quedó ×500).
 *
 * En DAPORTO eso dejó dos versiones del MISMO archivo con el mismo conteo (403 actividades, 820
 * insumos) y costos que difieren ×2,54 — $74.974.013.394,31 contra $29.492.804.353,65. Quien las
 * compare sin saberlo concluye que el presupuesto bajó $45 mil millones.
 *
 * La detección NO usa ids fijos: se recalcula fila por fila cuál de las dos fórmulas explica la
 * `cantidad_total` guardada. Solo cuentan las filas donde las dos fórmulas dan resultados que se
 * distinguen por encima de la tolerancia — es decir, donde `Rend × cantidad × (1 − Cant APU)` supera
 * el margen de redondeo. Eso descarta a la vez las filas con `Cant APU = 1` (ambas fórmulas
 * coinciden) y las de actividades con cantidad 0 (ambas dan 0): en DAPORTO eran 442 y 53
 * respectivamente, y contarlas hacía pasar por «ambigua» una versión que no lo es. Una versión se
 * marca obsoleta solo si TODAS sus filas discriminantes cuadran con la fórmula defectuosa y NINGUNA
 * con la correcta. Así la migración sirve para cualquier proyecto de AIA con el mismo problema.
 *
 * No borra nada: la versión se conserva para trazabilidad y queda advertida en el comparador.
 *
 * Idempotente: las columnas se agregan solo si faltan y el UPDATE reescribe la misma marca.
 *
 * Uso:  php database/migrations/20260728_pdc_v2_versiones_obsoletas.php [--apply]
 */

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../src/Core/Database.php';

$apply = in_array('--apply', $argv, true);
$db = Database::getInstance();

/** Tolerancia de comparación: absoluta por el redondeo de decimal(18,4), más un término relativo. */
const TOLERANCIA_SQL = '(0.01 + 0.000001 * ABS(%s))';

/**
 * Una fila solo sirve de evidencia si las dos fórmulas se separan más del doble de la tolerancia;
 * si no, cuadraría con ambas y no dice nada. Cubre `Cant APU = 1` y las actividades de cantidad 0.
 */
const FILA_DISCRIMINANTE_SQL =
    'ABS(ai.rendimiento * it.cantidad * (1 - ai.cant_apu))
       > (0.02 + 0.000002 * ABS(ai.cant_apu * ai.rendimiento * it.cantidad))';

echo ($apply ? '=== APLICANDO' : '=== DRY-RUN (usa --apply para escribir)') . " ===\n\n";

// ── 1. Columnas de la marca ───────────────────────────────────────────────────────────────────
const MOTIVO_MAX = 600;

$columnas = [
    'obsoleta' => 'ADD COLUMN obsoleta tinyint NOT NULL DEFAULT 0 AFTER activa',
    'obsoleta_motivo' => 'ADD COLUMN obsoleta_motivo varchar(' . MOTIVO_MAX . ') NULL AFTER obsoleta',
    'obsoleta_marcada_at' => 'ADD COLUMN obsoleta_marcada_at datetime NULL AFTER obsoleta_motivo',
];

echo "-- columnas de la marca --\n";
foreach ($columnas as $nombre => $ddl) {
    $actual = $db->query(
        "SELECT CHARACTER_MAXIMUM_LENGTH FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pdc_presupuesto_versiones' AND COLUMN_NAME = ?",
        [$nombre],
    )->fetch(PDO::FETCH_NUM);

    if ($actual === false) {
        echo "  · {$nombre}: se agrega\n";
        if ($apply) {
            $db->query('ALTER TABLE pdc_presupuesto_versiones ' . $ddl);
        }
        continue;
    }
    // Una corrida anterior pudo dejar el motivo corto: el texto explicativo no cabía en 400.
    if ($nombre === 'obsoleta_motivo' && $actual[0] !== null && (int) $actual[0] < MOTIVO_MAX) {
        echo "  · {$nombre}: existe con varchar({$actual[0]}), se amplía a varchar(" . MOTIVO_MAX . ")\n";
        if ($apply) {
            $db->query('ALTER TABLE pdc_presupuesto_versiones MODIFY obsoleta_motivo varchar(' . MOTIVO_MAX . ') NULL');
        }
        continue;
    }
    echo "  · {$nombre}: ya existe\n";
}

if (!$apply) {
    $faltan = 0;
    foreach (array_keys($columnas) as $nombre) {
        $faltan += (int) $db->query(
            "SELECT COUNT(*) = 0 FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pdc_presupuesto_versiones' AND COLUMN_NAME = ?",
            [$nombre],
        )->fetchColumn();
    }
    if ($faltan > 0) {
        echo "\n(dry-run: faltan columnas, el diagnóstico de abajo igual corre porque solo lee)\n";
    }
}

// ── 2. Diagnóstico: ¿qué fórmula explica cada versión? ────────────────────────────────────────
$tolCorrecta = sprintf(TOLERANCIA_SQL, 'ai.cant_apu * ai.rendimiento * it.cantidad');
$tolBug = sprintf(TOLERANCIA_SQL, 'ai.rendimiento * it.cantidad');

$diag = $db->query(
    "SELECT v.id, v.project_id, v.version_numero, v.archivo_nombre, v.activa, v.costo_total,
            COUNT(*) AS discriminantes,
            SUM(ABS(ai.cantidad_total - (ai.cant_apu * ai.rendimiento * it.cantidad)) <= {$tolCorrecta}) AS cuadra_correcta,
            SUM(ABS(ai.cantidad_total - (ai.rendimiento * it.cantidad)) <= {$tolBug}) AS cuadra_bug
     FROM pdc_presupuesto_versiones v
     JOIN pdc_presupuesto_apu_insumos ai ON ai.version_id = v.id
     JOIN pdc_presupuesto_items it ON it.id = ai.item_id
     WHERE ai.cant_apu IS NOT NULL AND ai.rendimiento IS NOT NULL AND it.cantidad IS NOT NULL
       AND " . FILA_DISCRIMINANTE_SQL . "
     GROUP BY v.id, v.project_id, v.version_numero, v.archivo_nombre, v.activa, v.costo_total
     ORDER BY v.project_id, v.version_numero",
)->fetchAll(PDO::FETCH_ASSOC);

echo "\n-- diagnóstico por versión (solo filas donde las dos fórmulas se distinguen) --\n";
$aMarcar = [];
$aLimpiar = [];

foreach ($diag as $d) {
    $id = (int) $d['id'];
    $disc = (int) $d['discriminantes'];
    $ok = (int) $d['cuadra_correcta'];
    $bug = (int) $d['cuadra_bug'];
    $etiqueta = sprintf('#%d (proyecto %d · versión %d)', $id, (int) $d['project_id'], (int) $d['version_numero']);

    if ($disc > 0 && $bug === $disc && $ok === 0) {
        // Cuantificar el inflado con la versión sana del mismo archivo, si la hay.
        $sana = $db->query(
            'SELECT costo_total FROM pdc_presupuesto_versiones
             WHERE project_id = ? AND archivo_nombre = ? AND id <> ? AND costo_total > 0
             ORDER BY version_numero DESC LIMIT 1',
            [(int) $d['project_id'], (string) $d['archivo_nombre'], $id],
        )->fetchColumn();

        $factor = ($sana !== false && (float) $sana > 0)
            ? sprintf(' El costo total quedó inflado ×%s frente a la reimportación sana del mismo archivo.', number_format((float) $d['costo_total'] / (float) $sana, 2, ',', '.'))
            : '';

        $motivo = sprintf(
            'Importada con el bug A1.8: el importador ignoraba «Cant APU», así que cada insumo quedó '
            . 'multiplicado por 1/Cant APU. Las %d filas que permiten distinguir las dos fórmulas '
            . 'cuadran con la defectuosa (Rend × cantidad) y ninguna con la correcta '
            . '(Cant APU × Rend × cantidad).%s '
            . 'No usar para comparar: las diferencias que muestre no son cambios del presupuesto.',
            $disc,
            $factor,
        );

        echo sprintf("  !! %s: OBSOLETA — %d/%d filas cuadran con la fórmula defectuosa, 0 con la correcta\n", $etiqueta, $bug, $disc);
        if ((int) $d['activa'] === 1) {
            echo "     ATENCIÓN: además es la versión ACTIVA del proyecto. Reimportar el archivo.\n";
        }
        $aMarcar[] = ['id' => $id, 'motivo' => mb_substr($motivo, 0, MOTIVO_MAX)];
        continue;
    }

    if ($disc > 0 && $ok === $disc && $bug < $disc) {
        echo sprintf("  · %s: sana — %d/%d filas cuadran con la fórmula correcta\n", $etiqueta, $ok, $disc);
        $aLimpiar[] = $id;
        continue;
    }

    echo sprintf(
        "  ?? %s: ambigua — %d discriminantes, %d cuadran con la correcta, %d con la defectuosa. Se deja sin tocar.\n",
        $etiqueta,
        $disc,
        $ok,
        $bug,
    );
}

$sinFilas = $db->query(
    'SELECT COUNT(*) FROM pdc_presupuesto_versiones v
     WHERE NOT EXISTS (
        SELECT 1 FROM pdc_presupuesto_apu_insumos ai JOIN pdc_presupuesto_items it ON it.id = ai.item_id
        WHERE ai.version_id = v.id
          AND ai.cant_apu IS NOT NULL AND ai.rendimiento IS NOT NULL AND it.cantidad IS NOT NULL
          AND ' . FILA_DISCRIMINANTE_SQL . '
     )',
)->fetchColumn();
if ((int) $sinFilas > 0) {
    echo sprintf("  · %d versión(es) sin ninguna fila discriminante: no se pueden diagnosticar, se dejan sin marcar\n", (int) $sinFilas);
}

// ── 3. Escritura ──────────────────────────────────────────────────────────────────────────────
echo "\n" . ($apply ? 'marcadas obsoletas: ' : 'a marcar obsoletas: ') . count($aMarcar) . "\n";

if ($apply) {
    foreach ($aMarcar as $m) {
        $db->query(
            'UPDATE pdc_presupuesto_versiones
             SET obsoleta = 1, obsoleta_motivo = ?, obsoleta_marcada_at = NOW()
             WHERE id = ?',
            [$m['motivo'], $m['id']],
        );
    }
    // Una versión que el diagnóstico da por sana no debe quedar marcada por una corrida anterior.
    foreach ($aLimpiar as $id) {
        $db->query(
            'UPDATE pdc_presupuesto_versiones
             SET obsoleta = 0, obsoleta_motivo = NULL, obsoleta_marcada_at = NULL
             WHERE id = ? AND obsoleta = 1',
            [$id],
        );
    }
}

exit(0);
