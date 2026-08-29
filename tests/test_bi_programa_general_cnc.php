<?php

/**
 * CNC contract for Programa General.
 *
 * Expected values are calculated directly from programacion_semanal and
 * semanas_activas so this test remains independent from ControlTowerService.
 */
declare(strict_types=1);
// @requiere: db


require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/support/BiContractFixture.php';

use App\Services\ControlTowerService;
use App\Security\DataScope\MultiProjectScope;
use App\Security\DataScope\ProjectScope;
use App\Security\DataScope\SystemScopeRunner;

function cncGlobalRead(\Database $db, string $case, callable $read): mixed
{
    return (new SystemScopeRunner($db->dataScope()))->run(
        'test:test_bi_programa_general_cnc:discovery:' . $case,
        $read,
    );
}

function cncProjectRead(\Database $db, int $projectId, callable $read): mixed
{
    $db->dataScope()->bind(new ProjectScope($projectId, 'fixture-bi-cnc', 'R'));
    try {
        return $read();
    } finally {
        $db->dataScope()->clear();
    }
}

$db = \Database::getInstance();
BiContractFixture::seedCausalRows($db);
$bi = new ControlTowerService();
$failures = [];

function cncFail(array &$failures, string $message): void
{
    $failures[] = $message;
}

function cncAssert(array &$failures, bool $condition, string $message): void
{
    if (!$condition) {
        cncFail($failures, $message);
    }
}

function cncCanonicalCategory(string $value): array
{
    $original = trim((string) preg_replace('/\s+/', ' ', $value));
    $lower = function_exists('mb_strtolower') ? mb_strtolower($original, 'UTF-8') : strtolower($original);
    $key = strtr($lower, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n']);
    $aliases = [
        'programacion' => 'Programación',
        'diseno' => 'Diseños',
        'disenos' => 'Diseños',
        'mano de obra' => 'Mano de Obra',
        'mano obra' => 'Mano de Obra',
        'm de o' => 'Mano de Obra',
        'material' => 'Materiales',
        'materiales' => 'Materiales',
        'equipo' => 'Equipos',
        'equipos' => 'Equipos',
        'administrativa' => 'Administrativas',
        'administrativas' => 'Administrativas',
        'causa exogena' => 'Causas Exógenas',
        'causas exogenas' => 'Causas Exógenas',
        'rendimiento' => 'Rendimiento',
    ];

    return ['canonical' => $aliases[$key] ?? ($original ?: 'Sin categoría'), 'known' => isset($aliases[$key])];
}

function cncDirectRows(\Database $db, array $projectIds, string $semana, array $filters = []): array
{
    $projectIds = array_values(array_unique(array_filter(array_map('intval', $projectIds), static fn(int $id): bool => $id > 0)));
    if ($projectIds === []) {
        return [];
    }

    $where = [
        'ps.project_id IN (' . implode(',', array_fill(0, count($projectIds), '?')) . ')',
        "ps.Activa IN ('1', 'NA')",
        "COALESCE(TRIM(ps.CNC), '') <> ''",
    ];
    $params = $projectIds;

    if (($filters['desde'] ?? '') !== '' || ($filters['hasta'] ?? '') !== '') {
        $where[] = 'sa.Fecha_Inicio_Sem <= ? AND sa.Fecha_Fin_Sem >= ?';
        $params[] = $filters['hasta'] ?: '9999-12-31';
        $params[] = $filters['desde'] ?: '1000-01-01';
    } elseif ($semana !== '') {
        $where[] = 'ps.Semana = ?';
        $params[] = $semana;
    }

    foreach (['resp' => 'Responsable_AIA', 'sub' => 'Sub_Contratista'] as $filter => $column) {
        $value = trim((string) ($filters[$filter] ?? ''));
        if ($value !== '') {
            $where[] = "LOWER(COALESCE(ps.{$column}, '')) LIKE ?";
            $params[] = '%' . strtolower($value) . '%';
        }
    }

    $stage = trim((string) ($filters['etapa'] ?? ''));
    if ($stage !== '') {
        $where[] = "(LOWER(COALESCE(ps.Actividad, '')) LIKE ? OR LOWER(COALESCE(ps.Ubicacion, '')) LIKE ?)";
        $params[] = '%' . strtolower($stage) . '%';
        $params[] = '%' . strtolower($stage) . '%';
    }

    $statement = $db->queryForProjects(
        new MultiProjectScope($projectIds, 'fixture-bi-cnc', 'R', 'test:test_bi_programa_general_cnc:oracle'),
        'SELECT ps.project_id, ps.Semana, ps.Consecutivo, ps.Actividad, ps.Ubicacion,
                ps.Categoria_CNC, ps.CNC, ps.Observaciones_CNC, ps.Compromiso,
                ps.Ejecutado_Real, ps.P_Completado, ps.PAC, ps.Unidad, ps.Critica,
                ps.Responsable_AIA, ps.Sub_Contratista,
                COALESCE(sa.Fecha_Fin_Sem, sa.Fecha_Inicio_Sem) AS cutoff
         FROM programacion_semanal ps
         LEFT JOIN semanas_activas sa ON sa.project_id = ps.project_id AND sa.Semana = ps.Semana
         WHERE ' . implode(' AND ', $where) . '
         ORDER BY ps.project_id, ps.Semana, ps.Consecutivo',
        $params,
    );

    return $statement->fetchAll(PDO::FETCH_ASSOC);
}

function cncExpected(array $rows): array
{
    $activities = [];
    foreach ($rows as $row) {
        $key = implode(':', [(int) $row['project_id'], (int) $row['Semana'], (int) $row['Consecutivo']]);
        $commitment = is_numeric($row['Compromiso'] ?? null) ? (float) $row['Compromiso'] : null;
        $executed = is_numeric($row['Ejecutado_Real'] ?? null) ? (float) $row['Ejecutado_Real'] : null;
        $completion = $commitment !== null && $commitment > 0 && $executed !== null
            ? round(max(0.0, ($executed / $commitment) * 100), 1)
            : null;
        $shortfall = $commitment !== null && $executed !== null ? round(max(0.0, $commitment - $executed), 2) : null;
        $shortfallPct = $commitment !== null && $commitment > 0 && $shortfall !== null
            ? round(($shortfall / $commitment) * 100, 1)
            : null;
        $category = cncCanonicalCategory((string) ($row['Categoria_CNC'] ?? ''));
        $activities[$key] = [
            'project_id' => (int) $row['project_id'],
            'category' => $category,
            'critical' => (int) ($row['Critica'] ?? 0) === 1,
            'responsible' => trim((string) ($row['Responsable_AIA'] ?? '')),
            'subcontractor' => trim((string) ($row['Sub_Contratista'] ?? '')),
            'observation' => trim((string) ($row['Observaciones_CNC'] ?? '')),
            'commitment' => $commitment,
            'executed' => $executed,
            'completion_pct' => $completion,
            'shortfall' => $shortfall,
            'shortfall_pct' => $shortfallPct,
            'unit' => trim((string) ($row['Unidad'] ?? '')),
        ];
    }

    $summary = [
        'total' => count($activities),
        'critical_count' => 0,
        'zero_execution_count' => 0,
        'partial_execution_count' => 0,
        'severe_gap_count' => 0,
        'missing_observation_count' => 0,
        'unassigned_responsible_count' => 0,
        'unassigned_subcontractor_count' => 0,
        'unknown_category_count' => 0,
        'completion_sample_size' => 0,
        'average_completion_pct' => null,
    ];
    $categories = [];
    $projectBreakdown = [];
    $completionValues = [];
    foreach ($activities as $activity) {
        $category = $activity['category']['canonical'];
        $categories[$category] = ($categories[$category] ?? 0) + 1;
        $summary['critical_count'] += $activity['critical'] ? 1 : 0;
        $summary['zero_execution_count'] += $activity['executed'] !== null && $activity['executed'] <= 0 ? 1 : 0;
        $summary['partial_execution_count'] += $activity['completion_pct'] !== null && $activity['completion_pct'] > 0 && $activity['completion_pct'] < 100 ? 1 : 0;
        $summary['severe_gap_count'] += $activity['shortfall_pct'] !== null && $activity['shortfall_pct'] >= 50 ? 1 : 0;
        $summary['missing_observation_count'] += $activity['observation'] === '' ? 1 : 0;
        $summary['unassigned_responsible_count'] += $activity['responsible'] === '' ? 1 : 0;
        $summary['unassigned_subcontractor_count'] += $activity['subcontractor'] === '' ? 1 : 0;
        $summary['unknown_category_count'] += $activity['category']['known'] ? 0 : 1;
        if ($activity['completion_pct'] !== null) {
            $completionValues[] = $activity['completion_pct'];
        }

        $projectId = $activity['project_id'];
        $projectBreakdown[$projectId] ??= [
            'project_id' => $projectId,
            'total' => 0,
            'critical_count' => 0,
            'zero_execution_count' => 0,
            'partial_execution_count' => 0,
            'severe_gap_count' => 0,
        ];
        $projectBreakdown[$projectId]['total']++;
        $projectBreakdown[$projectId]['critical_count'] += $activity['critical'] ? 1 : 0;
        $projectBreakdown[$projectId]['zero_execution_count'] += $activity['executed'] !== null && $activity['executed'] <= 0 ? 1 : 0;
        $projectBreakdown[$projectId]['partial_execution_count'] += $activity['completion_pct'] !== null && $activity['completion_pct'] > 0 && $activity['completion_pct'] < 100 ? 1 : 0;
        $projectBreakdown[$projectId]['severe_gap_count'] += $activity['shortfall_pct'] !== null && $activity['shortfall_pct'] >= 50 ? 1 : 0;
    }
    arsort($categories);
    $summary['completion_sample_size'] = count($completionValues);
    $summary['average_completion_pct'] = $completionValues === []
        ? null
        : round(array_sum($completionValues) / count($completionValues), 1);

    return compact('activities', 'summary', 'categories', 'projectBreakdown');
}

function cncCategoryCounts(mixed $categories): array
{
    $counts = [];
    foreach (is_array($categories) ? $categories : [] as $category) {
        if (is_array($category) && isset($category['category'])) {
            $counts[(string) $category['category']] = (int) ($category['count'] ?? 0);
        }
    }
    arsort($counts);

    return $counts;
}

function cncBreakdownByProject(mixed $items): array
{
    $indexed = [];
    foreach (is_array($items) ? $items : [] as $item) {
        if (is_array($item) && isset($item['project_id'])) {
            $indexed[(int) $item['project_id']] = $item;
        }
    }

    return $indexed;
}

function cncAssertScenario(
    array &$failures,
    ControlTowerService $bi,
    \Database $db,
    string $label,
    array $projectIds,
    string $semana,
    array $filters,
): void {
    $scope = new MultiProjectScope($projectIds, 'fixture-bi-cnc', 'R', 'test:test_bi_programa_general_cnc:' . $label);
    $expected = cncExpected(cncDirectRows($db, $projectIds, $semana, $filters));
    $brief = $bi->getBrief($scope, 'programa-general', $semana, 'R', $filters);
    $chart = $brief['charts']['programa-cnc'] ?? [];
    $metrics = $chart['metrics'] ?? [];

    cncAssert($failures, ($metrics['metric_key'] ?? '') === 'pg_cnc_activity_count', "{$label}: metric_key missing");
    cncAssert($failures, ($metrics['population_definition'] ?? '') === "Activa IN ('1', 'NA') y CNC registrada", "{$label}: population definition changed");
    cncAssert($failures, ($metrics['source_relations'] ?? []) === ['programacion_semanal', 'semanas_activas'], "{$label}: source contract incomplete");
    cncAssert($failures, (int) ($metrics['total'] ?? -1) === $expected['summary']['total'], "{$label}: chart total differs from source");
    foreach ($expected['summary'] as $field => $value) {
        if ($value === null) {
            cncAssert($failures, ($metrics[$field] ?? null) === null, "{$label}: {$field} must remain null without a valid sample");
        } elseif (is_float($value)) {
            cncAssert($failures, abs((float) ($metrics[$field] ?? -999) - $value) <= 0.05, "{$label}: {$field} differs from source");
        } else {
            cncAssert($failures, (int) ($metrics[$field] ?? -1) === $value, "{$label}: {$field} differs from source");
        }
    }
    cncAssert($failures, cncCategoryCounts($metrics['categories'] ?? []) === $expected['categories'], "{$label}: categories differ from source");
    cncAssert($failures, (int) array_sum($chart['datasets'][0]['data'] ?? []) === $expected['summary']['total'], "{$label}: doughnut total differs from deduplicated source");

    $breakdown = cncBreakdownByProject($metrics['project_breakdown'] ?? []);
    foreach ($expected['projectBreakdown'] as $projectId => $expectedProject) {
        foreach ($expectedProject as $field => $value) {
            cncAssert($failures, (int) ($breakdown[$projectId][$field] ?? -1) === $value, "{$label}: project {$projectId} {$field} differs from source");
        }
    }

    $detail = $bi->getProgramaCncDetail($scope, $semana, $filters, '', 2, 0);
    cncAssert($failures, (int) ($detail['pagination']['total'] ?? -1) === $expected['summary']['total'], "{$label}: detail total differs from chart/source");
    $all = [];
    $offset = 0;
    do {
        $page = $bi->getProgramaCncDetail($scope, $semana, $filters, '', 2, $offset, $offset === 0);
        array_push($all, ...($page['activities'] ?? []));
        $hasMore = (bool) ($page['pagination']['has_more'] ?? false);
        $next = (int) ($page['pagination']['next_offset'] ?? $offset);
        cncAssert($failures, !$hasMore || $next > $offset, "{$label}: pagination did not advance");
        $offset = $next;
    } while ($hasMore && $offset <= $expected['summary']['total']);

    cncAssert($failures, count($all) === $expected['summary']['total'], "{$label}: detail lost or duplicated records");
    $seen = [];
    foreach ($all as $activity) {
        $key = (string) ($activity['source_row_key'] ?? '');
        $source = $expected['activities'][$key] ?? null;
        cncAssert($failures, $source !== null, "{$label}: detail leaked a row outside the source population");
        cncAssert($failures, !isset($seen[$key]), "{$label}: duplicate detail key {$key}");
        $seen[$key] = true;
        if ($source === null) {
            continue;
        }
        foreach (['committed_quantity', 'executed_quantity', 'completion_pct', 'shortfall_quantity', 'shortfall_pct', 'unit', 'execution_status', 'priority', 'impact', 'recommended_action'] as $field) {
            cncAssert($failures, array_key_exists($field, $activity), "{$label}: detail missing {$field}");
        }
        foreach (['commitment' => 'committed_quantity', 'executed' => 'executed_quantity', 'completion_pct' => 'completion_pct', 'shortfall' => 'shortfall_quantity', 'shortfall_pct' => 'shortfall_pct'] as $sourceField => $actualField) {
            $actual = $activity[$actualField] ?? null;
            $expectedValue = $source[$sourceField];
            cncAssert($failures, $expectedValue === null ? $actual === null : abs((float) $actual - (float) $expectedValue) <= 0.05, "{$label}: {$actualField} differs for {$key}");
        }
        cncAssert($failures, (string) ($activity['unit'] ?? '') === (string) $source['unit'], "{$label}: unit differs for {$key}");
        cncAssert($failures, trim((string) ($activity['impact'] ?? '')) !== '', "{$label}: impact must be actionable");
        cncAssert($failures, trim((string) ($activity['recommended_action'] ?? '')) !== '', "{$label}: action must be actionable");
    }

    $leadingCategory = array_key_first($expected['categories']);
    if ($leadingCategory !== null) {
        $categoryPage = $bi->getProgramaCncDetail($scope, $semana, $filters, $leadingCategory, 1, 0);
        cncAssert($failures, (int) ($categoryPage['pagination']['total'] ?? -1) === (int) $expected['categories'][$leadingCategory], "{$label}: category total differs");
        foreach ($categoryPage['activities'] ?? [] as $activity) {
            cncAssert($failures, ($activity['category_canonical'] ?? '') === $leadingCategory, "{$label}: category filter leaked another category");
        }
    }
}

$context = cncGlobalRead($db, 'context', static fn() => $db->query("SELECT project_id, Semana FROM programacion_semanal WHERE Activa IN ('1','NA') AND COALESCE(TRIM(CNC), '') <> '' ORDER BY project_id, Semana, Consecutivo LIMIT 1")->fetch(PDO::FETCH_ASSOC)) ?: [];
$projectId = (int) ($context['project_id'] ?? 0);
$week = (string) ($context['Semana'] ?? '');
$canonicalRows = cncDirectRows($db, [$projectId], $week);
cncAssert($failures, $projectId > 0 && $canonicalRows !== [], 'canonical CI fixture must expose a CNC population');

$duplicateGroups = cncGlobalRead($db, 'duplicate-groups', static fn() => $db->query(
    "SELECT COUNT(*) FROM (
        SELECT project_id, Semana, Consecutivo
        FROM programacion_semanal
        WHERE Activa IN ('1', 'NA') AND COALESCE(TRIM(CNC), '') <> ''
        GROUP BY project_id, Semana, Consecutivo
        HAVING COUNT(*) > 1
    ) duplicate_cnc",
)->fetchColumn());
cncAssert($failures, (int) $duplicateGroups === 0, 'CNC source grain contains duplicates');

$weekRange = cncProjectRead($db, $projectId, static function () use ($db, $projectId, $week): array {
    $statement = $db->prepare('SELECT Fecha_Inicio_Sem, Fecha_Fin_Sem FROM semanas_activas WHERE project_id = ? AND Semana = ? LIMIT 1');
    $statement->execute([$projectId, $week]);
    return $statement->fetch(PDO::FETCH_ASSOC) ?: [];
});
$firstRow = $canonicalRows[0] ?? [];
$responsible = trim((string) ($firstRow['Responsable_AIA'] ?? ''));
$subcontractor = trim((string) ($firstRow['Sub_Contratista'] ?? ''));
$stage = trim((string) (($firstRow['Ubicacion'] ?? '') ?: ($firstRow['Actividad'] ?? '')));
$stage = function_exists('mb_substr') ? mb_substr($stage, 0, 24, 'UTF-8') : substr($stage, 0, 24);

cncAssertScenario($failures, $bi, $db, 'canonical empty week', [$projectId], (string) ((int) $week + 100), []);
cncAssertScenario($failures, $bi, $db, 'canonical CNC context', [$projectId], $week, []);
cncAssertScenario($failures, $bi, $db, 'CNC multi-project range', [BiContractFixture::PROYECTO_A, BiContractFixture::PROYECTO_B], '', ['desde' => '2026-07-06', 'hasta' => '2026-07-26']);
if (($weekRange['Fecha_Inicio_Sem'] ?? '') !== '' && ($weekRange['Fecha_Fin_Sem'] ?? '') !== '') {
    cncAssertScenario($failures, $bi, $db, 'canonical CNC date range', [$projectId], '', ['desde' => $weekRange['Fecha_Inicio_Sem'], 'hasta' => $weekRange['Fecha_Fin_Sem']]);
}
if ($responsible !== '') {
    cncAssertScenario($failures, $bi, $db, 'canonical CNC responsible filter', [$projectId], $week, ['resp' => $responsible]);
}
if ($subcontractor !== '') {
    cncAssertScenario($failures, $bi, $db, 'canonical CNC subcontractor filter', [$projectId], $week, ['sub' => $subcontractor]);
}
if ($stage !== '') {
    cncAssertScenario($failures, $bi, $db, 'canonical CNC stage filter', [$projectId], $week, ['etapa' => $stage]);
}

if ($failures !== []) {
    fwrite(STDERR, "=== BI Programa General CNC contract: FAILED ===\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}

echo "=== BI Programa General CNC contract: PASS ===\n";
