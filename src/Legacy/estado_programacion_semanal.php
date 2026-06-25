<?php

use App\Services\RestrictionConfigResolver;

if (!function_exists('ps_weekly_phase_key')) {
    function ps_weekly_phase_key($semanalConfirmada): string
    {
        return ((int) $semanalConfirmada === 1) ? 'calificacion' : 'programacion';
    }

    function ps_is_blank($value): bool
    {
        if ($value === null) {
            return true;
        }

        $text = trim((string) $value);
        if ($text === '') {
            return true;
        }

        $lower = strtolower($text);
        return $lower === 'null';
    }

    function ps_to_float($value, ?float $fallback = null): ?float
    {
        if (ps_is_blank($value)) {
            return $fallback;
        }

        $normalized = preg_replace('/\s+/', '', trim((string) $value));

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

        if (!is_numeric($normalized)) {
            return $fallback;
        }

        return (float) $normalized;
    }

    function ps_is_active_row(array $row): bool
    {
        $activaRaw = $row['Activa'] ?? '';
        $activa = strtoupper(trim((string) $activaRaw));

        if ($activa === '' || $activa === 'NA' || $activa === '0' || $activa === 'N' || $activa === 'NO' || $activa === 'FALSE') {
            return false;
        }

        return true;
    }

    function ps_has_cnc_complete(array $row): bool
    {
        return !ps_is_blank($row['Categoria_CNC'] ?? null) && !ps_is_blank($row['CNC'] ?? null);
    }

    function ps_requires_cnc($compromiso, $ejecutadoReal): bool
    {
        $compromisoNum = ps_to_float($compromiso, null);
        $ejecutadoRealNum = ps_to_float($ejecutadoReal, null);

        if ($compromisoNum === null || $ejecutadoRealNum === null) {
            return false;
        }

        if ($compromisoNum <= 0) {
            return false;
        }

        return ($ejecutadoRealNum + 0.0001) < $compromisoNum;
    }

    function ps_missing_required_cnc($compromiso, $ejecutadoReal, $categoriaCnc, $cnc): bool
    {
        if (!ps_requires_cnc($compromiso, $ejecutadoReal)) {
            return false;
        }

        return ps_is_blank($categoriaCnc) || ps_is_blank($cnc);
    }

    function ps_has_pending_commit_conditions(array $row, string $projectArea = 'Construccion'): bool
    {
        $notApplicableOrAtLeast = function (string $col, float $min) use ($row): bool {
            $raw = trim((string) ($row[$col] ?? ''));
            if (strtoupper($raw) === 'N/A' || strtoupper($raw) === 'NO APLICA') {
                return true;
            }
            $normalized = str_replace(['%', ' ', ','], ['', '', '.'], $raw);
            if (!is_numeric($normalized)) {
                return false;
            }
            $value = (float) $normalized;
            while ($value > 1 && $value <= 10000) {
                $value /= 100;
            }
            return ($value + 0.0001) >= $min;
        };

        $hardColumns = RestrictionConfigResolver::getHardRestrictionColumns($projectArea);
        $thresholds  = RestrictionConfigResolver::getThresholds($projectArea);

        return !(
            array_reduce($hardColumns, function (bool $carry, string $col) use ($notApplicableOrAtLeast, $thresholds): bool {
                return $carry && $notApplicableOrAtLeast($col, $thresholds[$col] ?? 1.0);
            }, true)
        );
    }

    function ps_classify_state(array $row, string $phaseKey, string $projectArea = 'Construccion'): string
    {
        if (!ps_is_active_row($row)) {
            return 'ps-no-activa';
        }

        $ejecutado = ps_to_float($row['Ejecutado'] ?? null, 0.0);
        $compromiso = ps_to_float($row['Compromiso'] ?? null, null);
        $ejecutadoReal = ps_to_float($row['Ejecutado_Real'] ?? null, null);

        $hasCommitment = $compromiso !== null && $compromiso > 0;
        $isIncomplete = $ejecutado < 0.999;
        $tieneEjecucion = $ejecutado > 0.001;

        $libFlag = ps_to_float($row['Prog_Sin_Restricciones_100'] ?? null, null);
        $sinLiberacion = $libFlag !== null ? ($libFlag > 0) : false;
        if (!$sinLiberacion) {
            $sinLiberacion = ps_has_pending_commit_conditions($row, $projectArea);
        }

        $critica = ps_to_float($row['Critica'] ?? null, 0.0);
        $isCriticalRoute = ($critica >= 1);
        $missingSubcontractor = ps_is_blank($row['Sub_Contratista'] ?? null);
        $missingResponsible = ps_is_blank($row['Responsable_AIA'] ?? null);
        $missingAssignments = $missingSubcontractor || $missingResponsible;

        if ($phaseKey === 'programacion') {
            if (!$isIncomplete) {
                return 'ps-no-activa';
            }

            if ($tieneEjecucion && $sinLiberacion) {
                return 'prog-ejecucion-con-restricciones';
            }

            if ($sinLiberacion && $isCriticalRoute) {
                return 'prog-bloqueo-critico-sin-compromiso';
            }

            if ($sinLiberacion) {
                return 'prog-condiciones-pendientes';
            }

            if (!$hasCommitment || $missingAssignments) {
                return 'prog-sin-compromiso';
            }

            return 'prog-lista-para-confirmar';
        }

        if (!$hasCommitment || $ejecutadoReal === null) {
            return 'cal-sin-calificar';
        }

        if (($ejecutadoReal + 0.0001) < $compromiso) {
            return $isCriticalRoute
                ? 'cal-incumplida-critica'
                : 'cal-incumplida';
        }

        return 'cal-cumplida-control';
    }
}
