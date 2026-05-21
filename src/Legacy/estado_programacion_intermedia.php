<?php

if (!function_exists('pi_filter_state_keys')) {
    function pi_filter_state_keys(): array
    {
        return [
            'blocked-overdue-critical',
            'blocked-overdue',
            'blocked-due',
            'alert-1-week',
            'alert-2-3-weeks',
            'alert-4-6-weeks',
            'execution-blocked',
            'liberated-control',
        ];
    }

    function pi_filter_alias_map(): array
    {
        return [
            'lookahead' => ['alert-1-week', 'alert-2-3-weeks', 'alert-4-6-weeks'],
            'no_iniciadas' => ['blocked-overdue-critical', 'blocked-overdue', 'blocked-due'],
            'en_ejecucion_pendientes' => ['execution-blocked'],
            'en_ejecucion_terminadas' => ['liberated-control'],
        ];
    }

    function pi_state_session_key(string $state): string
    {
        return 'pi_state_' . str_replace('-', '_', $state);
    }

    function pi_to_lower(string $text): string
    {
        if (function_exists('mb_strtolower')) {
            return mb_strtolower($text, 'UTF-8');
        }

        return strtolower($text);
    }

    function pi_resolve_filter_targets(string $rawClass): array
    {
        $class = trim(strtolower($rawClass));
        if ($class === '') {
            return [];
        }

        $normalized = str_replace('_', '-', $class);
        $states = pi_filter_state_keys();
        if (in_array($normalized, $states, true)) {
            return [$normalized];
        }

        $aliases = pi_filter_alias_map();
        if (isset($aliases[$class])) {
            return $aliases[$class];
        }

        return [];
    }

    function pi_to_float($value, float $fallback = 0.0): float
    {
        if ($value === null) {
            return $fallback;
        }

        $normalized = preg_replace('/\s+/', '', trim((string)$value));
        if ($normalized === '' || strtolower($normalized) === 'null') {
            return $fallback;
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

        if (!is_numeric($normalized)) {
            return $fallback;
        }

        return (float)$normalized;
    }

    function pi_normalize_estado($value): string
    {
        if ($value === null) {
            return '';
        }

        $text = trim((string)$value);
        if ($text === '') {
            return '';
        }

        $text = pi_to_lower($text);
        $text = strtr($text, [
            'á' => 'a',
            'é' => 'e',
            'í' => 'i',
            'ó' => 'o',
            'ú' => 'u',
            'ü' => 'u',
            'ñ' => 'n',
        ]);

        return $text;
    }

    function pi_is_critical_route($value): bool
    {
        $normalized = trim(pi_to_lower((string)$value));
        return $normalized === '1' || $normalized === 'si' || $normalized === 'sí';
    }

    function pi_is_not_applicable($value): bool
    {
        $normalized = strtoupper(trim((string)($value ?? '')));
        return $normalized === 'N/A' || $normalized === 'NO APLICA';
    }

    function pi_restriction_ratio($value): ?float
    {
        if ($value === null || $value === '' || pi_is_not_applicable($value)) {
            return null;
        }

        $raw = trim((string)$value);
        $hasPercent = strpos($raw, '%') !== false;
        $normalized = str_replace('%', '', preg_replace('/\s+/', '', $raw));
        $commaPos = strrpos($normalized, ',');
        $dotPos = strrpos($normalized, '.');

        if ($commaPos !== false && $dotPos !== false) {
            $normalized = $commaPos > $dotPos
                ? str_replace(',', '.', str_replace('.', '', $normalized))
                : str_replace(',', '', $normalized);
        } elseif ($commaPos !== false) {
            $normalized = str_replace(',', '.', $normalized);
        }

        if (!is_numeric($normalized)) {
            return null;
        }

        $ratio = (float)$normalized;
        if ($hasPercent) {
            $ratio /= 100;
        }
        while ($ratio > 1 && $ratio <= 10000) {
            $ratio /= 100;
        }

        return max(0.0, min(1.0, $ratio));
    }

    function pi_restriction_meets(array $row, string $column, float $minimum): bool
    {
        $value = $row[$column] ?? null;
        if (pi_is_not_applicable($value)) {
            return true;
        }

        $ratio = pi_restriction_ratio($value);
        return $ratio !== null && ($ratio + 0.0001) >= $minimum;
    }

    function pi_is_ready_to_commit(array $row): bool
    {
        return pi_restriction_meets($row, 'D_y_E', 1.0)
            && pi_restriction_meets($row, 'Materiales', 1.0)
            && pi_restriction_meets($row, 'MdeO', 1.0)
            && pi_restriction_meets($row, 'Equipos', 1.0)
            && pi_restriction_meets($row, 'Predecesora', 0.5);
    }

    function pi_classify_state(array $row): string
    {
        if ((int)($row['Titulo'] ?? 0) !== 0) {
            return 'header';
        }

        $si = (int)round(pi_to_float($row['Semanas_Inicio'] ?? null, 999.0));
        $ej = pi_to_float($row['Ejecutado'] ?? null, 0.0);
        $isCritical = pi_is_critical_route($row['Ruta_Critica'] ?? '');

        $isLiberated = pi_is_ready_to_commit($row);
        $isStarted = $ej > 0 && $ej < 0.999;
        $isNotStarted = $ej <= 0;
        $isOverdueSignal = $si < 0;

        if ($isStarted) {
            return $isLiberated ? 'liberated-control' : 'execution-blocked';
        }

        if ($si <= 0 && $isNotStarted) {
            if ($isLiberated) {
                return 'liberated-control';
            }

            if ($isOverdueSignal) {
                return $isCritical ? 'blocked-overdue-critical' : 'blocked-overdue';
            }

            return 'blocked-due';
        }

        if ($si === 1 && $isNotStarted && !$isLiberated) {
            return 'alert-1-week';
        }

        if ($si >= 2 && $si <= 3 && $isNotStarted && !$isLiberated) {
            return 'alert-2-3-weeks';
        }

        if ($si >= 4 && $si <= 6 && $isNotStarted && !$isLiberated) {
            return 'alert-4-6-weeks';
        }

        if ($isNotStarted && $isLiberated && $si > 0 && $si <= 6) {
            return 'liberated-control';
        }

        return 'neutral';
    }
}
