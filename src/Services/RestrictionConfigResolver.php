<?php

namespace App\Services;

use InvalidArgumentException;

/**
 * Single source of truth for restriction columns and thresholds
 * based on project Area (Construccion vs Pre-Construccion).
 *
 * All methods are static — no instantiation needed.
 */
class RestrictionConfigResolver
{
    private const AREA_CONSTRUCCION = 'Construccion';
    private const AREA_PRECONSTRUCCION = 'Pre-Construccion';

    private const VALID_AREAS = [
        self::AREA_CONSTRUCCION,
        self::AREA_PRECONSTRUCCION,
    ];

    /** @var array<string, array> Static cache keyed by dbPrefix */
    private static array $cache = [];

    // ─── Public API ────────────────────────────────────────────────────

    /**
     * Resolve restriction config by querying the database for the project's Area.
     *
     * @param string $dbPrefix The Base_de_Datos value from general_proyectos_procesos
     * @return array{area: string, isPreConstruccion: bool, hardRestrictions: string[], softRestrictions: string[], allRestrictions: string[], thresholds: array<string, float>}
     */
    public static function resolve(string $dbPrefix): array
    {
        if (isset(self::$cache[$dbPrefix])) {
            return self::$cache[$dbPrefix];
        }

        $area = self::queryAreaFromDb($dbPrefix);
        $config = self::resolveByArea($area);
        $config['dbPrefix'] = $dbPrefix;

        self::$cache[$dbPrefix] = $config;

        return $config;
    }

    /**
     * Resolve restriction config from an Area string (no DB query).
     *
     * @throws InvalidArgumentException for invalid area values
     */
    public static function resolveByArea(string $area): array
    {
        $area = self::normalizeArea($area);

        return [
            'area' => $area,
            'isPreConstruccion' => $area === self::AREA_PRECONSTRUCCION,
            'hardRestrictions' => self::getHardRestrictionColumns($area),
            'softRestrictions' => self::getSoftRestrictionColumns($area),
            'allRestrictions' => self::getAllRestrictionColumns($area),
            'thresholds' => self::getThresholds($area),
        ];
    }

    /**
     * Hard restriction column names for the given area.
     */
    public static function getHardRestrictionColumns(string $area): array
    {
        $area = self::normalizeArea($area);

        return match ($area) {
            self::AREA_PRECONSTRUCCION => ['restriccion_pc_1'],
            default => ['D_y_E', 'Materiales', 'MdeO', 'Equipos', 'Predecesora'],
        };
    }

    /**
     * Soft restriction column names for the given area.
     */
    public static function getSoftRestrictionColumns(string $area): array
    {
        $area = self::normalizeArea($area);

        return match ($area) {
            self::AREA_PRECONSTRUCCION => ['restriccion_pc_2', 'restriccion_pc_3', 'restriccion_pc_4'],
            default => ['Pdto_Cons', 'Modelo'],
        };
    }

    /**
     * All restriction columns (hard + soft) for the given area.
     */
    public static function getAllRestrictionColumns(string $area): array
    {
        return array_merge(
            self::getHardRestrictionColumns($area),
            self::getSoftRestrictionColumns($area),
        );
    }

    /**
     * Threshold map (column => float) for the given area.
     *
     * Thresholds are used in the Estado_Restricciones formula:
     * valor_normalizado = min(round(floatval(valor), 5) / threshold, 1.0)
     */
    public static function getThresholds(string $area): array
    {
        $area = self::normalizeArea($area);

        return match ($area) {
            self::AREA_PRECONSTRUCCION => [
                'restriccion_pc_1' => 0.5,
                'restriccion_pc_2' => 1.0,
                'restriccion_pc_3' => 1.0,
                'restriccion_pc_4' => 1.0,
            ],
            default => [
                'D_y_E' => 1.0,
                'Materiales' => 1.0,
                'MdeO' => 1.0,
                'Equipos' => 1.0,
                'Predecesora' => 0.5,
                'Pdto_Cons' => 1.0,
                'Modelo' => 1.0,
            ],
        };
    }

    /**
     * Calculate Estado_Restricciones from row data using the correct
     * columns and thresholds for the given area.
     *
     * Ported from modificar_sem_estado.php:42-53.
     */
    public static function calculateEstadoRestricciones(array $rowData, string $area): float
    {
        $thresholds = self::getThresholds($area);
        $conteoRest = 0;
        $sumaRest = 0.0;

        foreach ($thresholds as $column => $threshold) {
            $valor = $rowData[$column] ?? null;

            if ($valor === null || $valor === 'N/A') {
                continue;
            }

            $conteoRest++;
            $normalized = min(round((float) $valor, 5) / $threshold, 1.0);
            $sumaRest += $normalized;
        }

        if ($conteoRest === 0) {
            return 1.0;
        }

        return round($sumaRest / $conteoRest, 5);
    }

    /**
     * Clear the static cache (useful for testing).
     */
    public static function clearCache(): void
    {
        self::$cache = [];
    }

    // ─── Internal helpers ──────────────────────────────────────────────

    /**
     * Normalize and validate area value.
     *
     * @throws InvalidArgumentException
     */
    private static function normalizeArea(string $area): string
    {
        $area = trim($area);

        if (in_array($area, self::VALID_AREAS, true)) {
            return $area;
        }

        throw new InvalidArgumentException(
            sprintf('Invalid area "%s". Expected one of: %s', $area, implode(', ', self::VALID_AREAS))
        );
    }

    /**
     * Query general_proyectos_procesos for the project Area.
     * Defaults to 'Construccion' if not found or NULL.
     */
    private static function queryAreaFromDb(string $dbPrefix): string
    {
        $dbInstance = \Database::getInstance();

        $stmt = $dbInstance->query(
            'SELECT Area FROM general_proyectos_procesos WHERE Base_de_Datos = ? LIMIT 1',
            [$dbPrefix]
        );
        $data = $stmt->fetch();

        $area = $data['Area'] ?? null;

        if ($area === null || trim($area) === '') {
            return self::AREA_CONSTRUCCION;
        }

        return trim($area);
    }
}
