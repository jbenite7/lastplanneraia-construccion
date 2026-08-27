<?php

declare(strict_types=1);

namespace App\Services\Bi;

/**
 * Provides legacy BI lineage output from the code-first metric catalog.
 */
class LineageService
{
    private MetricDictionaryService $dictionary;

    /** @var array<string, list<string>> */
    private const REPORT_METRICS = [
        'overview' => [
            'pg_activities_to_do', 'pi_hard_restrictions_ready_rate',
            'ps_weekly_fulfillment', 'pdc_at_risk', 'cic_cal_integral',
            'cip_fulfillment_alert', 'curva_s_desviacion',
        ],
        'programa-general' => [
            'pg_activities_to_do',
            'pg_activity_progress_contribution',
            'pg_finish_variance_days_p50',
            'pg_observed_activity_delay_days',
            'pg_cnp_activity_count',
            'pg_cnc_activity_count',
            'pg_radar_productividad',
            'pg_radar_eficiencia',
            'pg_radar_desempeno',
        ],
        'intermedia' => ['pi_hard_restrictions_ready_rate', 'pi_restriction_pareto'],
        'semanal' => ['ps_pac_expected', 'ps_weekly_fulfillment'],
        'pdc' => ['pdc_at_risk'],
        'cic' => ['cic_cal_integral', 'cic_aprobacion_status'],
        'cip' => ['cip_fulfillment_alert'],
        'curva-s' => ['curva_s_desviacion'],
        'riesgos' => ['riesgo_score_100'],
    ];

    public function __construct()
    {
        $this->dictionary = new MetricDictionaryService();
    }

    /**
     * Get compatible lineage output for all metrics displayed by a report.
     */
    public function getForReport(string $reportKey): array
    {
        $lineage = [];
        foreach (self::REPORT_METRICS[$reportKey] ?? [] as $metricKey) {
            $definition = $this->getForMetric($metricKey);
            if ($definition !== []) {
                $lineage[] = $definition;
            }
        }

        return $lineage;
    }

    /**
     * Get compatible lineage output for a metric key.
     */
    public function getForMetric(string $metricKey): array
    {
        $definition = $this->dictionary->getDefinition($metricKey);
        if ($definition === []) {
            return [];
        }

        return [
            'metric_key' => $definition['metric_key'],
            'metric_name' => $definition['metric_name'],
            'definition' => $definition['definition'],
            'formula' => $definition['formula'],
            'source_view' => $definition['execution_source'],
            'source_tables' => implode(', ', $definition['source_relations']),
            'grain' => $definition['grain'],
            'cutoff_policy' => $definition['cutoff_policy'],
            'filters' => implode(', ', $definition['filters']),
            'version' => $definition['version'],
            'last_updated' => $definition['last_updated'] ?? '2026-07-10 00:00:00',
            'known_limitations' => $definition['known_limitations'],
        ];
    }

    /**
     * List metric keys in the same alphabetical order as the former view query.
     */
    public function listAllMetricKeys(): array
    {
        $metrics = array_map(
            static fn(array $definition): array => [
                'metric_key' => $definition['metric_key'],
                'metric_name' => $definition['metric_name'],
            ],
            $this->dictionary->exportDictionary(),
        );
        usort($metrics, static fn(array $left, array $right): int => $left['metric_key'] <=> $right['metric_key']);

        return $metrics;
    }
}
