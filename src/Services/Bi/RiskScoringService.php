<?php

declare(strict_types=1);

namespace App\Services\Bi;

use App\Security\DataScope\MultiProjectScope;

/**
 * BI Risk Scoring Service.
 *
 * Computes risk_score_100 using the weighted sum formula (Doc Section 8.7):
 *   risk_score_100 = 35*probability + 25*impact + 20*urgency
 *                  + 10*criticality + 10*data_confidence
 *
 * Levels: Bajo 0-30, Medio 31-60, Alto 61-80, Crítico 81-100.
 */
class RiskScoringService
{
    private \Database $db;

    public function __construct()
    {
        $this->db = \Database::getInstance();
    }

    /**
     * Get top risks for a report, ordered by risk_score descending.
     */
    public function getTopRisks(
        MultiProjectScope $scope,
        string $reportKey,
        string $semana,
        int $limit = 10,
        array $filters = [],
    ): array
    {
        if (!$this->isSupportedReport($reportKey)) {
            return [];
        }

        $riskType = $this->mapReportToRiskType($reportKey);
        $projectIds = $scope->projectIds();

        $placeholders = implode(',', array_fill(0, count($projectIds), '?'));
        $riskWhere = $riskType === null ? '' : ' AND risk_type = ?';
        $sqlLimit = max(1, min(100, $limit));
        $filters = $this->normalizeFilters($filters);
        $rangeWhere = '';
        $params = $projectIds;
        if ($this->hasDateRange($filters)) {
            $rangeWhere = " AND EXISTS (
                SELECT 1 FROM semanas_activas sa_filter
                WHERE sa_filter.project_id = bi_riesgos.project_id
                  AND sa_filter.Semana = bi_riesgos.Semana
                  AND COALESCE(sa_filter.Fecha_Fin_Sem, sa_filter.Fecha_Inicio_Sem) BETWEEN ? AND ?
            )";
            $params[] = $filters['desde'] ?: '1000-01-01';
            $params[] = $filters['hasta'] ?: '9999-12-31';
        } elseif ($semana !== '') {
            $rangeWhere = ' AND Semana = ?';
            $params[] = $semana;
        }

        if ($riskType !== null) {
            $params[] = $riskType;
        }
        $contextWhere = $this->contextualRiskWhere($filters, $params);
        $rows = $this->db->queryForProjects(
            $scope,
            "SELECT * FROM bi_riesgos WHERE project_id IN ({$placeholders}){$rangeWhere}{$riskWhere}{$contextWhere} ORDER BY risk_score_100 DESC LIMIT {$sqlLimit}",
            $params,
        );

        $risks = [];
        foreach ($rows->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $risks[] = [
                'project_id'     => (int) $row['project_id'],
                'risk'          => $row['entity_name'] ?? $row['entity_id'],
                'entity_type'   => $row['entity_type'],
                'entity_id'     => $row['entity_id'],
                'risk_type'     => $row['risk_type'],
                'risk_score'    => (int) $row['risk_score_100'],
                'risk_level'    => $this->level((int) $row['risk_score_100']),
                'probability'   => round((float) $row['probability_score'], 2),
                'impact'        => $this->impactLabel((float) $row['impact_score']),
                'confidence'    => $this->confidenceLabel((float) $row['data_confidence_score']),
                'source_view'   => $row['source_view'],
                'computed_at'   => $row['computed_at'],
            ];
        }
        return $risks;
    }

    /**
     * Compute risk score for a single entity manually (for testing / API).
     */
    public function computeRisk(array $features): array
    {
        $p = $features['probability_score'] ?? 0;
        $i = $features['impact_score'] ?? 0;
        $u = $features['urgency_score'] ?? 0;
        $c = $features['criticality_score'] ?? 0;
        $d = $features['data_confidence_score'] ?? 0;

        $score = (int) round(35 * $p + 25 * $i + 20 * $u + 10 * $c + 10 * $d);

        return [
            'risk_score'           => $score,
            'risk_level'           => $this->level($score),
            'probability_score'    => round($p, 2),
            'impact_score'         => round($i, 2),
            'urgency_score'        => round($u, 2),
            'criticality_score'    => round($c, 2),
            'data_confidence_score' => round($d, 2),
            'drivers'              => $features['drivers'] ?? [],
        ];
    }

    // -----------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------

    private function mapReportToRiskType(string $reportKey): ?string
    {
        return match ($reportKey) {
            'overview'            => null, // all types, handled separately
            'programa-general'    => 'actividad',
            'intermedia'          => 'actividad',
            'semanal'             => 'actividad',
            'cic'                 => 'contratista',
            'cip'                 => 'actividad',
            'curva-s'             => 'actividad',
            default               => null,
        };
    }

    private function isSupportedReport(string $reportKey): bool
    {
        return in_array($reportKey, [
            'overview', 'programa-general', 'intermedia', 'semanal',
            'cic', 'cip', 'curva-s',
        ], true);
    }

    private function normalizeFilters(array $filters): array
    {
        return [
            'desde' => $this->dateOrBlank($filters['desde'] ?? $filters['fecha_desde'] ?? ''),
            'hasta' => $this->dateOrBlank($filters['hasta'] ?? $filters['fecha_hasta'] ?? ''),
            'sub' => trim((string) ($filters['sub'] ?? $filters['subcontratista'] ?? '')),
            'resp' => trim((string) ($filters['resp'] ?? $filters['responsable'] ?? '')),
            'etapa' => trim((string) ($filters['etapa'] ?? '')),
        ];
    }

    private function hasDateRange(array $filters): bool
    {
        return $filters['desde'] !== '' || $filters['hasta'] !== '';
    }

    private function contextualRiskWhere(array $filters, array &$params): string
    {
        if ($filters['sub'] === '' && $filters['resp'] === '' && $filters['etapa'] === '') {
            return '';
        }

        $branches = [];
        $activity = ['pc_filter.project_id = bi_riesgos.project_id', 'pc_filter.Semana = bi_riesgos.Semana', 'pc_filter.Consecutivo_en_Programa = bi_riesgos.entity_id'];
        $this->appendContextLike($activity, $params, 'pc_filter.Sub_Contratista', $filters['sub']);
        $this->appendContextLike($activity, $params, 'pc_filter.Responsable_AIA', $filters['resp']);
        $this->appendAnyContextLike($activity, $params, ['pc_filter.Actividad', 'pc_filter.Estado'], $filters['etapa']);
        $branches[] = "(bi_riesgos.risk_type = 'actividad' AND EXISTS (SELECT 1 FROM programa_consolidado pc_filter WHERE " . implode(' AND ', $activity) . '))';

        if ($filters['resp'] === '') {
            $this->appendContractorBranch($branches, $params, $filters);
            // La rama de riesgos 'pdc' se retiró el 2026-08-04: filtraba contra la tabla `pdc` del
            // PDC v1, eliminada con el módulo, y `bi_riesgos` ya no emite filas de ese tipo.
        }

        return $branches === [] ? ' AND 1 = 0' : ' AND (' . implode(' OR ', $branches) . ')';
    }

    private function appendContractorBranch(array &$branches, array &$params, array $filters): void
    {
        $conditions = [
            'cic_filter.project_id = bi_riesgos.project_id',
            'cic_filter.Semana = bi_riesgos.Semana',
            "CONVERT(cic_filter.subcontratista USING utf8mb4) COLLATE utf8mb4_unicode_ci = CONVERT(bi_riesgos.entity_id USING utf8mb4) COLLATE utf8mb4_unicode_ci",
        ];
        $this->appendContextLike($conditions, $params, 'cic_filter.subcontratista', $filters['sub']);
        $this->appendAnyContextLike($conditions, $params, ['cic_filter.alcance', 'cic_filter.tipo_proveedor'], $filters['etapa']);
        $branches[] = "(bi_riesgos.risk_type = 'contratista' AND EXISTS (SELECT 1 FROM bi_cic_contratistas cic_filter WHERE " . implode(' AND ', $conditions) . '))';
    }

    private function appendContextLike(array &$conditions, array &$params, string $column, string $value): void
    {
        if ($value === '') {
            return;
        }

        $conditions[] = "LOWER(COALESCE({$column}, '')) LIKE ?";
        $params[] = '%' . strtolower($value) . '%';
    }

    private function appendAnyContextLike(array &$conditions, array &$params, array $columns, string $value): void
    {
        if ($value === '') {
            return;
        }

        $conditions[] = '(' . implode(' OR ', array_map(
            static fn(string $column): string => "LOWER(COALESCE({$column}, '')) LIKE ?",
            $columns,
        )) . ')';
        foreach ($columns as $_column) {
            $params[] = '%' . strtolower($value) . '%';
        }
    }

    private function dateOrBlank(mixed $value): string
    {
        $value = trim((string) $value);
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : '';
    }

    public function level(int $score): string
    {
        return match (true) {
            $score >= 81 => 'Crítico',
            $score >= 61 => 'Alto',
            $score >= 31 => 'Medio',
            default     => 'Bajo',
        };
    }

    private function impactLabel(float $score): string
    {
        return match (true) {
            $score >= 0.80 => 'Crítico',
            $score >= 0.60 => 'Alto',
            $score >= 0.40 => 'Medio',
            default       => 'Bajo',
        };
    }

    private function confidenceLabel(float $score): string
    {
        return match (true) {
            $score >= 0.80 => 'Alta',
            $score >= 0.50 => 'Media',
            default       => 'Baja',
        };
    }
}
