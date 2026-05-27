<?php

if (!defined('PG_STATUS_EPS')) {
    define('PG_STATUS_EPS', 0.001);
}

if (!defined('PG_STATUS_AHEAD_TOL')) {
    define('PG_STATUS_AHEAD_TOL', 0.05);
}

if (!defined('PG_STATUS_DONE_THRESHOLD')) {
    define('PG_STATUS_DONE_THRESHOLD', 0.999);
}

if (!defined('PG_LOOKAHEAD_DAYS')) {
    define('PG_LOOKAHEAD_DAYS', 42);
}

function pg_normalize_numeric($value): string
{
    if ($value === null) {
        return '';
    }

    $normalized = preg_replace('/\s+/', '', trim((string)$value));
    if ($normalized === '' || strtolower($normalized) === 'nulo') {
        return '';
    }

    $commaPos = strrpos($normalized, ',');
    $dotPos = strrpos($normalized, '.');

    if ($commaPos !== false && $dotPos !== false) {
        if ($commaPos > $dotPos) {
            $normalized = str_replace('.', '', $normalized);
            $normalized = str_replace(',', '.', $normalized);
        } else {
            $normalized = str_replace(',', '', $normalized);
        }
    } elseif ($commaPos !== false) {
        $normalized = str_replace(',', '.', $normalized);
    }

    return $normalized;
}

function pg_to_float($value, float $default = 0.0): float
{
    $normalized = pg_normalize_numeric($value);
    if ($normalized === '' || !is_numeric($normalized)) {
        return $default;
    }

    return (float)$normalized;
}

function pg_to_timestamp($dateValue): ?int
{
    if ($dateValue === null) {
        return null;
    }

    $value = trim((string)$dateValue);
    if ($value === '') {
        return null;
    }

    $timestamp = strtotime($value);
    if ($timestamp === false) {
        return null;
    }

    return strtotime(date('Y-m-d', $timestamp));
}

function pg_clamp(float $value, float $min, float $max): float
{
    if ($value < $min) {
        return $min;
    }

    if ($value > $max) {
        return $max;
    }

    return $value;
}

function pg_calculate_week_offset($fechaInicioActividad, $fechaInicioSemana): ?int
{
    $fiTs = pg_to_timestamp($fechaInicioActividad);
    $fsTs = pg_to_timestamp($fechaInicioSemana);

    if ($fiTs === null || $fsTs === null) {
        return null;
    }

    $dias = (int)floor(($fiTs - $fsTs) / 86400);
    return (int)floor($dias / 7);
}

function pg_calculate_theoretical_progress($fechaInicioActividad, $fechaFinActividad, $fechaInicioSemana): float
{
    $fiTs = pg_to_timestamp($fechaInicioActividad);
    $ffTs = pg_to_timestamp($fechaFinActividad);
    $fsTs = pg_to_timestamp($fechaInicioSemana);

    if ($fiTs === null || $ffTs === null || $fsTs === null) {
        return 0.0;
    }

    if ($ffTs < $fiTs) {
        $ffTs = $fiTs;
    }

    $duracionDias = max(1, (int)floor(($ffTs - $fiTs) / 86400) + 1);
    $diasTranscurridos = (int)floor(($fsTs - $fiTs) / 86400);

    if ($diasTranscurridos < 1) {
        return 0.0;
    }

    if ($diasTranscurridos > $duracionDias) {
        return 1.0;
    }

    return pg_clamp($diasTranscurridos / $duracionDias, 0.0, 1.0);
}

function pg_calculate_status(
    $titulo,
    $ejecutado,
    $fechaInicioActividad,
    $fechaFinActividad,
    $fechaInicioSemana,
    $fechaFinSemana = null,
    float $eps = PG_STATUS_EPS,
    float $aheadTolerance = PG_STATUS_AHEAD_TOL,
    float $doneThreshold = PG_STATUS_DONE_THRESHOLD
): string {
    if ((int)$titulo === 1) {
        return 'Capítulo';
    }

    $ej = pg_to_float($ejecutado, 0.0);
    $ej = pg_clamp($ej, 0.0, 1.0);

    if ($ej >= $doneThreshold) {
        return 'Terminada';
    }

    $fiTs = pg_to_timestamp($fechaInicioActividad);
    $ffTs = pg_to_timestamp($fechaFinActividad);
    $fsTs = pg_to_timestamp($fechaInicioSemana);

    if ($fiTs === null || $ffTs === null || $fsTs === null) {
        if ($ej > $eps) {
            return 'En Curso';
        }

        return 'Sin Datos';
    }

    if ($ffTs < $fiTs) {
        $ffTs = $fiTs;
    }

    $feTs = pg_to_timestamp($fechaFinSemana);
    if ($feTs === null) {
        $feTs = $fsTs + (6 * 86400);
    }

    if ($ej > $eps) {
        $ejecutadoTeorico = pg_calculate_theoretical_progress($fechaInicioActividad, $fechaFinActividad, $fechaInicioSemana);
        $delta = round($ejecutadoTeorico - $ej, 3);

        if ($delta > $eps) {
            return 'Atrasada';
        }

        return 'En Curso';
    }

    if ($fiTs < $fsTs) {
        return 'Atrasada';
    }

    if ($fiTs <= $feTs) {
        return 'Debe Iniciar';
    }

    return 'Actividad Futura';
}
