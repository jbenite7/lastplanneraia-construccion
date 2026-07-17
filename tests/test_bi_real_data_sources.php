<?php

/**
 * Test: every BI report and chart declares real database-backed sources.
 */
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Services\ControlTowerService;

$db = \Database::getInstance();
$bi = new ControlTowerService();

$context = $db->query(
    "SELECT project_id, Semana FROM bi_pg_semana WHERE project_id > 0 ORDER BY project_id, Semana LIMIT 1",
)->fetch(PDO::FETCH_ASSOC) ?: ['project_id' => 73, 'Semana' => 1];

$projectId = (int) $context['project_id'];
$semana = (string) $context['Semana'];

$reports = [
    'overview',
    'programa-general',
    'intermedia',
    'semanal',
    'pdc',
    'cic',
    'cip',
    'curva-s',
];

$expectedCharts = [
    'overview' => ['chart-ppc-semanal', 'chart-pac-prog'],
    'programa-general' => [
        'programa-curva-ejecucion',
        'programa-gauge',
        'programa-compliance',
        'programa-dias-retraso',
        'programa-cnp',
        'programa-cnc',
        'programa-radar-productividad',
    ],
    'intermedia' => ['chart-intermedia'],
    'semanal' => ['chart-semanal-pac'],
    'curva-s' => ['chart-curva-s'],
];

$passed = 0;
$failed = 0;

function biPass(string $message): void
{
    global $passed;
    echo "  PASS: {$message}\n";
    $passed++;
}

function biFail(string $message): void
{
    global $failed;
    echo "  FAIL: {$message}\n";
    $failed++;
}

function assertRelationExists(\Database $db, string $relation, string $label): void
{
    if (!preg_match('/^[A-Za-z0-9_]+$/', $relation)) {
        biFail("{$label}: relation name is unsafe ({$relation})");
        return;
    }

    try {
        $db->query("SELECT 1 FROM {$relation} LIMIT 0");
        biPass("{$label}: source relation {$relation} exists");
    } catch (Throwable $e) {
        biFail("{$label}: source relation {$relation} missing ({$e->getMessage()})");
    }
}

echo "=== Testing BI Real Data Sources ===\n\n";
echo "Context: project_id={$projectId}, semana={$semana}\n\n";

foreach ($reports as $report) {
    $brief = $bi->getBrief($report, [$projectId], $semana, 'R');
    $sourceRelations = $brief['data_source']['source_relations'] ?? [];

    if (($brief['respuesta'] ?? '') === 'BIEN') {
        biPass("{$report}: endpoint contract returns BIEN");
    } else {
        biFail("{$report}: endpoint contract did not return BIEN");
    }

    if (!empty($sourceRelations)) {
        biPass("{$report}: declares source relations");
    } else {
        biFail("{$report}: missing source relations");
    }

    foreach ($sourceRelations as $relation) {
        assertRelationExists($db, (string) $relation, $report);
    }

    $charts = $brief['charts'] ?? [];
    foreach ($expectedCharts[$report] ?? [] as $chartId) {
        if (!isset($charts[$chartId])) {
            biFail("{$report}: missing chart {$chartId}");
            continue;
        }

        $chart = $charts[$chartId];
        $chartSources = $chart['source_relations'] ?? [];
        $datasets = $chart['datasets'] ?? [];

        !empty($chartSources)
            ? biPass("{$report}/{$chartId}: declares chart source relations")
            : biFail("{$report}/{$chartId}: missing chart source relations");

        foreach ($chartSources as $relation) {
            assertRelationExists($db, (string) $relation, "{$report}/{$chartId}");
        }

        !empty($datasets)
            ? biPass("{$report}/{$chartId}: declares datasets")
            : biFail("{$report}/{$chartId}: missing datasets");

        foreach ($datasets as $index => $dataset) {
            $values = $dataset['data'] ?? null;
            if (!is_array($values)) {
                biFail("{$report}/{$chartId}: dataset {$index} is not an array");
                continue;
            }
            $allNumeric = count(array_filter($values, fn($value) => $value === null || is_numeric($value))) === count($values);
            $allNumeric
                ? biPass("{$report}/{$chartId}: dataset {$index} is numeric or null")
                : biFail("{$report}/{$chartId}: dataset {$index} contains non-numeric/non-null data");
        }
    }
}

echo "\n---\nResult: {$passed} passed, {$failed} failed\n";
exit($failed > 0 ? 1 : 0);
