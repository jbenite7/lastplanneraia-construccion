<?php

declare(strict_types=1);

/**
 * 20260729_pdc_v2_equipo_sin_clasificar.php
 *
 * PDC v2 / Ola 2 — los equipos que ya existen pasan a «EQUIPO (SIN CLASIFICAR)».
 *
 * Decisión deliberada del usuario, contra la opción barata de mandarlos todos a «comprado»: nadie
 * afirma lo que no sabe. Genera un tapón de decisiones humanas, y se asume — «sin clasificar» hereda
 * el cuadro de compatibilidad del viejo «EQUIPO» (ver `PaquetesService::tiposCompatibles()`), así que
 * el módulo se usa igual con el tapón puesto, ni mejor ni peor que hoy.
 *
 * REGLA, NO LISTA DE NOMBRES: mueve las filas cuyo `tipo_recurso` es el genérico `EQUIPO`. Cuando el
 * bucket de indirectos se partió en A3.2 se reetiquetó por lista y aparecieron paquetes arrastrando el
 * tipo viejo (`docs/pdc-v2.md` §deudas de datos saldadas).
 *
 * NO TOCA las filas ya clasificadas: las que SINCO trae como `ALQUILER EQUIPOS` se quedan como están
 * —ya tienen la respuesta, degradarlas sería perder dato.
 *
 * NO necesita re-enganchar la cola de vínculos: `MaestroInsumosService::reengancharPendientes()` y el
 * auto-match emparejan por `descripcion_norm` + `unidad`, y `pdc_insumo_vinculos` no tiene columna
 * `tipo_recurso`. Reetiquetar equipos no puede alterar un vínculo. Verificado en
 * `tests/test_pdc_v2_equipo_clasificacion.php`.
 *
 * Uso:
 *   php database/migrations/20260729_pdc_v2_equipo_sin_clasificar.php              # dry-run
 *   php database/migrations/20260729_pdc_v2_equipo_sin_clasificar.php --apply
 *   php database/migrations/20260729_pdc_v2_equipo_sin_clasificar.php --revertir   # vuelta atrás
 *
 * Idempotente: la segunda corrida de --apply escribe 0 filas.
 *
 * Requiere `20260729_pdc_v2_equipo_alquilado_comprado.sql` aplicada antes (columnas de auditoría).
 */

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../src/Core/Database.php';

use App\Services\Pdc\TipoRecursoEquipo;

$apply = in_array('--apply', $argv, true);
$revertir = in_array('--revertir', $argv, true);

if ($apply && $revertir) {
    fwrite(STDERR, "--apply y --revertir son excluyentes.\n");
    exit(1);
}

$db = Database::getInstance();

$GEN = TipoRecursoEquipo::GENERICO;
$SIN = TipoRecursoEquipo::SIN_CLASIFICAR;

$columnas = (int) $db->query(
    "SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'general_maestro_insumos'
       AND COLUMN_NAME IN ('clasificado_por','clasificado_at')",
)->fetchColumn();
if ($columnas !== 2) {
    fwrite(STDERR, "Falta aplicar 20260729_pdc_v2_equipo_alquilado_comprado.sql (columnas de auditoría).\n");
    exit(1);
}

if ($revertir) {
    // Los «sin clasificar» regresan al genérico. Los que un humano YA clasificó se conservan a
    // propósito: revertir la migración no es borrar trabajo humano.
    $n = (int) $db->query('SELECT COUNT(*) FROM general_maestro_insumos WHERE tipo_recurso = ?', [$SIN])->fetchColumn();
    $clasificados = (int) $db->query(
        'SELECT COUNT(*) FROM general_maestro_insumos WHERE tipo_recurso IN (?, ?) AND clasificado_at IS NOT NULL',
        TipoRecursoEquipo::CLASIFICADOS,
    )->fetchColumn();

    echo "REVERTIR: {$n} filas «{$SIN}» → «{$GEN}».\n";
    echo "Se CONSERVAN {$clasificados} clasificadas por una persona (revertir no borra su trabajo).\n";

    $db->query(
        'UPDATE general_maestro_insumos SET tipo_recurso = ?, updated_at = NOW() WHERE tipo_recurso = ?',
        [$GEN, $SIN],
    );

    $quedan = (int) $db->query('SELECT COUNT(*) FROM general_maestro_insumos WHERE tipo_recurso = ?', [$SIN])->fetchColumn();
    echo "Hecho. En tránsito restantes: {$quedan} (debe ser 0).\n";
    exit($quedan === 0 ? 0 : 1);
}

// ---- Dry-run / apply -------------------------------------------------------------------------

$aMover = (int) $db->query(
    'SELECT COUNT(*) FROM general_maestro_insumos WHERE UPPER(TRIM(tipo_recurso)) = ?',
    [$GEN],
)->fetchColumn();

$yaEnTransito = (int) $db->query('SELECT COUNT(*) FROM general_maestro_insumos WHERE tipo_recurso = ?', [$SIN])->fetchColumn();

$yaClasificados = (int) $db->query(
    'SELECT COUNT(*) FROM general_maestro_insumos WHERE tipo_recurso IN (?, ?)',
    TipoRecursoEquipo::CLASIFICADOS,
)->fetchColumn();

// Cuánto del tapón trae la respuesta escrita en `agrupacion`. NO se aplica: se informa, para que
// quien mire la cola sepa cuánto trabajo es de verdad ciego.
$conPista = 0;
$agrupaciones = $db->query(
    'SELECT agrupacion FROM general_maestro_insumos WHERE UPPER(TRIM(tipo_recurso)) = ?',
    [$GEN],
)->fetchAll(PDO::FETCH_COLUMN);
foreach ($agrupaciones as $agr) {
    if (TipoRecursoEquipo::pistaSinco(is_string($agr) ? $agr : null) !== null) {
        $conPista++;
    }
}

echo ($apply ? "APLICANDO" : "DRY-RUN (usa --apply para escribir)") . "\n";
echo "  A mover a «{$SIN}»: {$aMover}\n";
echo "  Ya en tránsito (no se tocan): {$yaEnTransito}\n";
echo "  Ya clasificados (no se tocan): {$yaClasificados}\n";
echo "  Del tapón, con pista SINCO en `agrupacion`: {$conPista} de {$aMover} (se muestra en la cola, NO se escribe)\n";

if (!$apply) {
    exit(0);
}

$db->beginTransaction();
try {
    // `clasificado_por`/`clasificado_at` quedan NULL a propósito: nadie ha clasificado nada todavía.
    // Eso es justo lo que le dice al importador SINCO que puede pisar esta fila.
    $db->query(
        'UPDATE general_maestro_insumos SET tipo_recurso = ?, updated_at = NOW()
         WHERE UPPER(TRIM(tipo_recurso)) = ?',
        [$SIN, $GEN],
    );
    $db->commit();
} catch (\Throwable $t) {
    $db->rollBack();
    throw $t;
}

$quedan = (int) $db->query(
    'SELECT COUNT(*) FROM general_maestro_insumos WHERE UPPER(TRIM(tipo_recurso)) = ?',
    [$GEN],
)->fetchColumn();
$enTransito = (int) $db->query('SELECT COUNT(*) FROM general_maestro_insumos WHERE tipo_recurso = ?', [$SIN])->fetchColumn();

echo "Hecho. Genéricos restantes: {$quedan} (debe ser 0). En tránsito: {$enTransito}.\n";
exit($quedan === 0 ? 0 : 1);
