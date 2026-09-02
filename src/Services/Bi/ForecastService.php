<?php

declare(strict_types=1);

namespace App\Services\Bi;

/**
 * BI Forecast Service.
 *
 * Computes transparent, rule-based predictions (no ML).
 * 5 dimensions: PAC expected, avance, restricciones, PDC, fecha final.
 *
 * Doc Sections 6.4-6.8.
 */
class ForecastService
{
    private \Database $db;

    private const PAC_REQUIRED_FEATURES = [
        'contractor_pac_4w', 'responsible_pac_4w', 'is_critical',
        'hard_restrictions_ready', 'current_progress', 'recent_cnc_4w',
    ];
    private const MINIMUM_PAC_SAMPLE_SIZE = 3;

    public function __construct()
    {
        $this->db = \Database::getInstance();
    }

    /**
     * Estimate PAC expected for a commitment using transparent features.
     *
     * Features used:
     *   - contractor_pac_4w: PAC histórico del contratista (últimas 4 semanas)
     *   - responsible_pac_4w: PAC histórico del responsable
     *   - is_critical: actividad crítica o no
     *   - hard_restrictions_ready: restricciones listas
     *   - current_progress: avance actual vs compromiso
     *   - recent_cnc_4w: CNC en últimas 4 semanas
     *
     * @return array { pac_expected, confidence, features }
     */
    public function forecastPacExpected(array $features): array
    {
        $weights = [
            'contractor_pac'     => 0.25,
            'responsible_pac'    => 0.20,
            'is_critical'        => 0.15,
            'restrictions_ready' => 0.20,
            'current_progress'   => 0.10,
            'recent_cnc'         => 0.10,
        ];

        $missingFeatures = [];
        foreach (self::PAC_REQUIRED_FEATURES as $feature) {
            if (!array_key_exists($feature, $features) || $features[$feature] === null) {
                $missingFeatures[] = $feature;
            }
        }

        foreach (['contractor_pac_sample_size_4w', 'responsible_pac_sample_size_4w'] as $sampleFeature) {
            if (!array_key_exists($sampleFeature, $features)
                || !is_numeric($features[$sampleFeature])
                || (int) $features[$sampleFeature] < self::MINIMUM_PAC_SAMPLE_SIZE) {
                $missingFeatures[] = $sampleFeature;
            }
        }

        $invalidFeatures = $this->invalidPacFeatures($features);
        if ($missingFeatures !== [] || $invalidFeatures !== []) {
            return $this->pacUnavailable($features, $missingFeatures, $invalidFeatures, $weights);
        }

        $score = 0.0;
        $confidenceFactors = [];

        // Contractor PAC
        $cp = (float) $features['contractor_pac_4w'];
        $score += $weights['contractor_pac'] * $cp;
        $confidenceFactors[] = 1.0;

        // Responsible PAC
        $rp = (float) $features['responsible_pac_4w'];
        $score += $weights['responsible_pac'] * $rp;
        $confidenceFactors[] = 1.0;

        // Critical path
        $critical = (bool) $features['is_critical'];
        $score += $weights['is_critical'] * ($critical ? 0.6 : 0.85);
        $confidenceFactors[] = 1.0;

        // Restrictions ready
        $ready = (bool) $features['hard_restrictions_ready'];
        $score += $weights['restrictions_ready'] * ($ready ? 0.90 : 0.40);
        $confidenceFactors[] = $ready ? 1.0 : 0.7;

        // Current progress
        $progress = (float) $features['current_progress'];
        $score += $weights['current_progress'] * min(1.0, $progress);
        $confidenceFactors[] = 1.0;

        // Recent CNC
        $cnc = (int) $features['recent_cnc_4w'];
        $cncFactor = $cnc > 2 ? 0.3 : ($cnc > 0 ? 0.6 : 0.9);
        $score += $weights['recent_cnc'] * $cncFactor;
        $confidenceFactors[] = 1.0;

        $confidence = round(array_sum($confidenceFactors) / count($confidenceFactors), 2);

        return [
            'pac_expected' => round($score, 2),
            'projection_available' => true,
            'confidence'   => $confidence,
            'features'     => $features,
            'model_version' => 'PAC_BASELINE_1.0',
            'sample' => [
                'contractor_pac_4w' => (int) $features['contractor_pac_sample_size_4w'],
                'responsible_pac_4w' => (int) $features['responsible_pac_sample_size_4w'],
            ],
            'metadata' => ['weights' => $weights, 'minimum_sample_size' => self::MINIMUM_PAC_SAMPLE_SIZE],
        ];
    }

    /**
     * Forecast avance for a horizon.
     *
     * @param string $horizon '1w', '2w', '4w', '6w', 'end'
     * @param float  $currentProgress Current % completion
     * @param float  $recentVelocity  Avg progress per week (last 4 weeks)
     * @param int    $remainingWeeks  Weeks remaining to target
     */
    public function forecastAvance(string $horizon, mixed $currentProgress, mixed $recentVelocity, mixed $remainingWeeks): array
    {
        [$missingFeatures, $invalidFeatures] = $this->classifyAvanceFeatures(
            $currentProgress,
            $recentVelocity,
            $remainingWeeks,
        );
        if ($missingFeatures !== [] || $invalidFeatures !== []) {
            return $this->avanceUnavailable(
                $horizon,
                $currentProgress,
                $recentVelocity,
                null,
                $missingFeatures,
                $invalidFeatures,
                'Los datos de avance son incompletos o inválidos para proyectar.',
            );
        }

        $weeks = match ($horizon) {
            '1w' => 1, '2w' => 2, '4w' => 4, '6w' => 6,
            'end' => $remainingWeeks > 0 ? $remainingWeeks : null,
            default => null,
        };

        if ($recentVelocity <= 0 || $weeks === null) {
            return $this->avanceUnavailable(
                $horizon,
                $currentProgress,
                $recentVelocity,
                $weeks,
                [],
                [],
                $recentVelocity <= 0
                    ? 'Se requiere velocidad reciente positiva para proyectar avance.'
                    : 'El horizonte solicitado no es válido para proyectar avance.',
            );
        }

        $projected = min(1.0, $currentProgress + ($recentVelocity * $weeks));
        $gap = 1.0 - $projected;

        return [
            'status'              => 'available',
            'projection_available' => true,
            'horizon'             => $horizon,
            'weeks'               => $weeks,
            'current_progress'    => round($currentProgress, 4),
            'recent_velocity'     => round($recentVelocity, 4),
            'projected_progress'  => round($projected, 4),
            'gap_to_completion'   => round($gap, 4),
            'on_track'            => $projected >= 0.95,
        ];
    }

    /**
     * Simple forecast of project end date based on recent pace.
     *
     * @param string $fechaFinPlanned   Planned end date
     * @param float  $currentProgress   Current % (0-1)
     * @param float  $recentVelocity    Avg progress/week
     * @param int    $diasRetraso       Current accumulated delay days
     * @param string|null $fechaCorte   Explicit reporting cutoff date
     */
    public function forecastFechaFinal(
        string $fechaFinPlanned,
        mixed $currentProgress,
        mixed $recentVelocity,
        int $diasRetraso,
        ?string $fechaCorte = null,
    ): array
    {
        $planned = new \DateTime($fechaFinPlanned);
        [$missingFeatures, $invalidFeatures] = $this->classifyFechaFinalFeatures(
            $currentProgress,
            $recentVelocity,
            $fechaCorte,
        );
        if ($missingFeatures !== [] || $invalidFeatures !== []) {
            return $this->fechaFinalUnavailable(
                $planned,
                $recentVelocity,
                $missingFeatures,
                $invalidFeatures,
                'Los datos requeridos para proyectar la fecha final son incompletos o inválidos.',
            );
        }

        if ($recentVelocity <= 0) {
            return $this->fechaFinalUnavailable(
                $planned,
                $recentVelocity,
                [],
                [],
                'Se requiere velocidad reciente positiva para proyectar la fecha final.',
            );
        }

        $remaining = 1.0 - $currentProgress;
        $weeksNeeded = (int) ceil($remaining / $recentVelocity);
        $diasAdicionales = (int) ($weeksNeeded * 7);

        $cutoff = new \DateTime($fechaCorte);
        $forecast = (clone $cutoff)->modify("+{$diasAdicionales} days");

        return [
            'status'                => 'available',
            'projection_available'   => true,
            'fecha_fin_planeada'   => $planned->format('Y-m-d'),
            'fecha_corte'           => $cutoff->format('Y-m-d'),
            'fecha_fin_forecast'   => $forecast->format('Y-m-d'),
            'dias_desplazamiento'  => $diasAdicionales,
            'semanas_restantes'    => $weeksNeeded,
            'ritmo_semanal'        => round($recentVelocity, 4),
            'confianza'            => $recentVelocity > 0 ? 'Media' : 'Baja',
        ];
    }

    // -----------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------

    private function invalidPacFeatures(array $features): array
    {
        $invalidFeatures = [];
        foreach (['contractor_pac_4w', 'responsible_pac_4w', 'current_progress'] as $feature) {
            if (array_key_exists($feature, $features) && $features[$feature] !== null
                && !$this->isUnitInterval($features[$feature])) {
                $invalidFeatures[] = $feature;
            }
        }

        foreach (['is_critical', 'hard_restrictions_ready'] as $feature) {
            if (array_key_exists($feature, $features) && $features[$feature] !== null && !is_bool($features[$feature])) {
                $invalidFeatures[] = $feature;
            }
        }

        if (array_key_exists('recent_cnc_4w', $features) && $features['recent_cnc_4w'] !== null
            && !$this->isNonNegativeInteger($features['recent_cnc_4w'])) {
            $invalidFeatures[] = 'recent_cnc_4w';
        }

        return $invalidFeatures;
    }

    private function classifyAvanceFeatures(mixed $currentProgress, mixed $recentVelocity, mixed $remainingWeeks): array
    {
        $missingFeatures = [];
        $invalidFeatures = [];
        foreach (['current_progress' => $currentProgress, 'recent_velocity' => $recentVelocity] as $feature => $value) {
            if ($value === null) {
                $missingFeatures[] = $feature;
            } elseif (!$this->isUnitInterval($value)) {
                $invalidFeatures[] = $feature;
            }
        }

        if ($remainingWeeks === null) {
            $missingFeatures[] = 'remaining_weeks';
        } elseif (!$this->isNonNegativeInteger($remainingWeeks)) {
            $invalidFeatures[] = 'remaining_weeks';
        }

        return [$missingFeatures, $invalidFeatures];
    }

    private function classifyFechaFinalFeatures(
        mixed $currentProgress,
        mixed $recentVelocity,
        ?string $fechaCorte,
    ): array
    {
        [$missingFeatures, $invalidFeatures] = $this->classifyAvanceFeatures(
            $currentProgress,
            $recentVelocity,
            0,
        );

        if ($fechaCorte === null || $fechaCorte === '') {
            $missingFeatures[] = 'fecha_corte';
        } elseif (!$this->isIsoDate($fechaCorte)) {
            $invalidFeatures[] = 'fecha_corte';
        }

        return [$missingFeatures, $invalidFeatures];
    }

    private function isUnitInterval(mixed $value): bool
    {
        return (is_int($value) || is_float($value))
            && is_finite((float) $value)
            && $value >= 0
            && $value <= 1;
    }

    private function isNonNegativeInteger(mixed $value): bool
    {
        return is_int($value) && $value >= 0;
    }

    private function isIsoDate(string $value): bool
    {
        $date = \DateTime::createFromFormat('!Y-m-d', $value);
        $errors = \DateTime::getLastErrors();

        return $date !== false
            && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0));
    }

    private function avanceUnavailable(
        string $horizon,
        mixed $currentProgress,
        mixed $recentVelocity,
        ?int $weeks,
        array $missingFeatures,
        array $invalidFeatures,
        string $reason,
    ): array
    {
        return [
            'status' => 'unavailable',
            'projection_available' => false,
            'horizon' => $horizon,
            'weeks' => $weeks,
            'current_progress' => $this->roundedNumberOrNull($currentProgress),
            'recent_velocity' => $this->roundedNumberOrNull($recentVelocity),
            'projected_progress' => null,
            'gap_to_completion' => null,
            'on_track' => null,
            'missing_features' => $missingFeatures,
            'invalid_features' => $invalidFeatures,
            'reason' => $reason,
        ];
    }

    private function fechaFinalUnavailable(
        \DateTime $planned,
        mixed $recentVelocity,
        array $missingFeatures,
        array $invalidFeatures,
        string $reason,
    ): array
    {
        return [
            'status' => 'unavailable',
            'projection_available' => false,
            'fecha_fin_planeada' => $planned->format('Y-m-d'),
            'fecha_fin_forecast' => null,
            'dias_desplazamiento' => null,
            'semanas_restantes' => null,
            'ritmo_semanal' => $this->roundedNumberOrNull($recentVelocity),
            'confianza' => 'Baja',
            'missing_features' => $missingFeatures,
            'invalid_features' => $invalidFeatures,
            'reason' => $reason,
        ];
    }

    private function roundedNumberOrNull(mixed $value): ?float
    {
        return (is_int($value) || is_float($value)) && is_finite((float) $value)
            ? round((float) $value, 4)
            : null;
    }

    private function pacUnavailable(array $features, array $missingFeatures, array $invalidFeatures, array $weights): array
    {
        return [
            'pac_expected' => null,
            'projection_available' => false,
            'confidence' => null,
            'features' => $features,
            'missing_features' => $missingFeatures,
            'invalid_features' => $invalidFeatures,
            'reason' => $invalidFeatures === []
                ? 'Faltan variables obligatorias o evidencia histórica mínima para proyectar PAC.'
                : 'Las variables obligatorias contienen valores inválidos para proyectar PAC.',
            'model_version' => 'PAC_BASELINE_1.0',
            'sample' => [
                'contractor_pac_4w' => array_key_exists('contractor_pac_sample_size_4w', $features) ? (int) $features['contractor_pac_sample_size_4w'] : null,
                'responsible_pac_4w' => array_key_exists('responsible_pac_sample_size_4w', $features) ? (int) $features['responsible_pac_sample_size_4w'] : null,
            ],
            'metadata' => ['weights' => $weights, 'minimum_sample_size' => self::MINIMUM_PAC_SAMPLE_SIZE],
        ];
    }

    /**
     * Get contractor PAC for the last 4 weeks.
     */
    public function getContractorPac4W(int $projectId, string $subcontratista): ?float
    {
        $stmt = $this->db->prepare(
            // Alias distinto por referencia: `cic` se nombra dos veces —la externa y la de la
            // subconsulta— y sin alias las dos se llaman igual. ProjectSqlGuard aborta ahi con
            // «Alias de tabla de proyecto ambiguo», porque con dos raices homonimas no puede decidir
            // a cual pertenece cada `project_id = ?`. `prepare()` no lo salva: cuando la consulta
            // toca una tabla de proyecto devuelve una sentencia diferida que pasa por el guard en el
            // execute(), asi que el fallo aparece al ejecutar y no al preparar.
            "SELECT AVG(CAST(REPLACE(c.PAC, ',', '.') AS DECIMAL(10,4))) as avg_pac
             FROM cic c
             WHERE c.project_id = ? AND c.subcontratista = ?
             AND c.Semana >= (SELECT MAX(v.Semana) - 4 FROM cic v WHERE v.project_id = ?)
             AND c.PAC != 'NA'",
        );
        $stmt->execute([$projectId, $subcontratista, $projectId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row && $row['avg_pac'] !== null ? round((float) $row['avg_pac'], 4) : null;
    }

    /**
     * Get responsible PAC for the last 4 weeks.
     */
    public function getResponsiblePac4W(int $projectId, string $responsable): ?float
    {
        $stmt = $this->db->prepare(
            // Mismo caso que getContractorPac4W: dos referencias a `cip`, una por alias.
            "SELECT AVG(CAST(REPLACE(c.PAC_Consolidado, ',', '.') AS DECIMAL(10,4))) as avg_pac
             FROM cip c
             WHERE c.project_id = ? AND c.profesional = ?
             AND c.Semana >= (SELECT MAX(v.Semana) - 4 FROM cip v WHERE v.project_id = ?)
             AND c.PAC_Consolidado != 'NA'",
        );
        $stmt->execute([$projectId, $responsable, $projectId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row && $row['avg_pac'] !== null ? round((float) $row['avg_pac'], 4) : null;
    }
}
