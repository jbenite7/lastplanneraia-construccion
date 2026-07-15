<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Services\Bi\LineageService;
use App\Services\Bi\MetricDictionaryService;

$dictionary = new MetricDictionaryService();
$lineage = new LineageService();
$failures = [];

$requiredFields = [
    'metric_key',
    'report_key',
    'metric_name',
    'definition',
    'formula',
    'unit',
    'execution_source',
    'source_relations',
    'grain',
    'cutoff_policy',
    'filters',
    'aggregation_policy',
    'supports_multi_project',
    'supports_date_range',
    'synthetic_defaults_allowed',
    'forecast_policy',
    'version',
    'known_limitations',
];

$expectedMetricKeys = [
    'cic_aprobacion_status',
    'cic_cal_integral',
    'cip_fulfillment_alert',
    'curva_s_desviacion',
    'pdc_at_risk',
    'pg_activities_to_do',
    'pg_activity_progress_contribution',
    'pg_cnc_activity_count',
    'pg_cnp_activity_count',
    'pg_finish_variance_days_p50',
    'pg_observed_activity_delay_days',
    'pg_radar_desempeno',
    'pg_radar_eficiencia',
    'pg_radar_productividad',
    'pi_hard_restrictions_ready_rate',
    'pi_restriction_pareto',
    'ps_pac_expected',
    'ps_weekly_fulfillment',
    'riesgo_score_100',
];

$definitions = $dictionary->exportDictionary();
$actualMetricKeys = array_column($definitions, 'metric_key');
sort($actualMetricKeys);
if ($actualMetricKeys !== $expectedMetricKeys) {
    $failures[] = 'catalog metric keys differ from the governed BI contract';
}

foreach ($definitions as $definition) {
    $metricKey = (string) ($definition['metric_key'] ?? 'unknown');
    foreach ($requiredFields as $field) {
        if (!array_key_exists($field, $definition)) {
            $failures[] = "{$metricKey}: missing required field {$field}";
        }
    }

    if (!is_array($definition['source_relations'] ?? null) || $definition['source_relations'] === []) {
        $failures[] = "{$metricKey}: source_relations must be a non-empty array";
    }
    if (!is_array($definition['filters'] ?? null)) {
        $failures[] = "{$metricKey}: filters must be an array";
    }
    if (str_contains(strtoupper(json_encode($definition['cutoff_policy'] ?? '')), 'CURDATE')) {
        $failures[] = "{$metricKey}: cutoff_policy must not depend on CURDATE";
    }
    if (($definition['synthetic_defaults_allowed'] ?? null) !== false) {
        $failures[] = "{$metricKey}: synthetic defaults must be disabled";
    }
}

$pacDefinition = $dictionary->getDefinition('ps_pac_expected');
$expectedPacFormula = '0.25*PAC contratista + 0.20*PAC responsable + 0.15*criticidad + 0.20*restricciones + 0.10*avance + 0.10*CNC';
if (($pacDefinition['formula'] ?? null) !== $expectedPacFormula) {
    $failures[] = 'ps_pac_expected: documented weights differ from ForecastService';
}
if (($pacDefinition['execution_source'] ?? null) !== 'ForecastService::forecastPacExpected') {
    $failures[] = 'ps_pac_expected: execution source is not the real forecast service';
}
if (($pacDefinition['integration_status'] ?? null) !== 'planned_for_programacion_semanal') {
    $failures[] = 'ps_pac_expected: integration status must not imply that the current brief publishes the forecast';
}

foreach (['pg_radar_productividad', 'pg_radar_eficiencia', 'pg_radar_desempeno'] as $metricKey) {
    $radarDefinition = $dictionary->getDefinition($metricKey);
    if (!in_array("Activa IN ('1','NA')", $radarDefinition['filters'] ?? [], true)) {
        $failures[] = "{$metricKey}: metric contract must declare the active commitment population";
    }
    if (($radarDefinition['execution_source'] ?? null) !== 'programacion_semanal') {
        $failures[] = "{$metricKey}: execution source must match the operational Radar query";
    }
}

foreach (['pg_finish_variance_days_p50', 'pg_observed_activity_delay_days'] as $metricKey) {
    $definition = $dictionary->getDefinition($metricKey);
    if (($definition['execution_source'] ?? '') === '' || !in_array('programa_consolidado', $definition['source_relations'] ?? [], true)) {
        $failures[] = "{$metricKey}: execution source and operational relation must be explicit";
    }
    if (($definition['supports_multi_project'] ?? false) !== true || ($definition['supports_date_range'] ?? false) !== true) {
        $failures[] = "{$metricKey}: project and range support must be governed";
    }
}

$cnpDefinition = $dictionary->getDefinition('pg_cnp_activity_count');
if (($cnpDefinition['execution_source'] ?? '') !== 'programacion_semanal'
    || !in_array("Activa='0'", $cnpDefinition['filters'] ?? [], true)
    || !in_array("TRIM(CNP)<>''", $cnpDefinition['filters'] ?? [], true)) {
    $failures[] = 'pg_cnp_activity_count: source and population contract must match the operational CNP query';
}

$cncDefinition = $dictionary->getDefinition('pg_cnc_activity_count');
if (($cncDefinition['execution_source'] ?? '') !== 'programacion_semanal'
    || !in_array("Activa IN ('1','NA')", $cncDefinition['filters'] ?? [], true)
    || !in_array("TRIM(CNC)<>''", $cncDefinition['filters'] ?? [], true)) {
    $failures[] = 'pg_cnc_activity_count: source and population contract must match the operational CNC query';
}

$expectedReports = [
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

foreach ($expectedReports as $reportKey => $expectedKeys) {
    $actual = $lineage->getForReport($reportKey);
    $actualKeys = array_column($actual, 'metric_key');
    if ($actualKeys !== $expectedKeys) {
        $failures[] = "{$reportKey}: metric mapping changed";
    }
    foreach ($actual as $definition) {
        foreach (['source_view', 'source_tables', 'last_updated'] as $legacyField) {
            if (!array_key_exists($legacyField, $definition)) {
                $failures[] = "{$reportKey}: legacy field {$legacyField} is missing";
            }
        }
        if (!is_string($definition['last_updated'] ?? null) || $definition['last_updated'] === '') {
            $failures[] = "{$reportKey}: legacy last_updated is not a timestamp string";
        }
        if (in_array(($definition['metric_key'] ?? ''), ['pg_cnp_activity_count', 'pg_cnc_activity_count'], true)
            && ($definition['last_updated'] ?? '') !== '2026-07-14 00:00:00') {
            $failures[] = "{$reportKey}: causal lineage timestamp does not match the governed metric publication";
        }
    }
}

if ($failures !== []) {
    foreach ($failures as $failure) {
        echo "FAIL: {$failure}\n";
    }
    exit(1);
}

echo "PASS: BI metric catalog contract is complete and report lineage remains compatible\n";
