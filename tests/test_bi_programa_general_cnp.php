<?php

/**
 * CNP contract for Programa General.
 *
 * Expected values deliberately come from programacion_semanal and semanas_activas,
 * never from ControlTowerService. This keeps the regression useful when either the
 * source population, a BI filter, or the presentation mapping drifts.
 */
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Services\ControlTowerService;

$db = \Database::getInstance();
$bi = new ControlTowerService();
$failures = [];

function cnpFail(array &$failures, string $message): void
{
    $failures[] = $message;
}

function cnpAssert(array &$failures, bool $condition, string $message): void
{
    if (!$condition) {
        cnpFail($failures, $message);
    }
}

function cnpCanonicalCategory(string $value): array
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
    ];

    return ['canonical' => $aliases[$key] ?? ($original ?: 'Sin categoría'), 'known' => isset($aliases[$key])];
}

function cnpStartState(?string $startDate, ?string $cutoff): array
{
    if (!$startDate || !$cutoff) {
        return ['days_to_start' => null, 'start_status' => 'unknown'];
    }

    try {
        $start = new DateTimeImmutable($startDate);
        $cut = new DateTimeImmutable($cutoff);
    } catch (Throwable) {
        return ['days_to_start' => null, 'start_status' => 'unknown'];
    }

    $days = (int) $cut->diff($start)->format('%r%a');
    return [
        'days_to_start' => $days,
        'start_status' => $days < 0 ? 'overdue' : ($days === 0 ? 'due_today' : ($days <= 7 ? 'next_7_days' : 'future')),
    ];
}

function cnpDirectRows(\Database $db, array $projectIds, string $semana, array $filters = []): array
{
    $projectIds = array_values(array_unique(array_filter(array_map('intval', $projectIds), static fn(int $id): bool => $id > 0)));
    if (!$projectIds) {
        return [];
    }

    $where = [
        'ps.project_id IN (' . implode(',', array_fill(0, count($projectIds), '?')) . ')',
        "ps.Activa = '0'",
        "COALESCE(TRIM(ps.CNP), '') <> ''",
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

    $sql = 'SELECT
            ps.project_id, ps.Semana, ps.Consecutivo, ps.Actividad, ps.Ubicacion, ps.Categoria_CNP, ps.CNP,
            ps.Fecha_Inicio, ps.Fecha_Fin, ps.Critica, ps.Responsable_AIA, ps.Sub_Contratista,
            COALESCE(sa.Fecha_Fin_Sem, sa.Fecha_Inicio_Sem) AS cutoff
        FROM programacion_semanal ps
        LEFT JOIN semanas_activas sa ON sa.project_id = ps.project_id AND sa.Semana = ps.Semana
        WHERE ' . implode(' AND ', $where) . '
        ORDER BY ps.project_id, ps.Semana, ps.Consecutivo';
    $statement = $db->prepare($sql);
    $statement->execute($params);
    return $statement->fetchAll(PDO::FETCH_ASSOC);
}

function cnpExpected(array $rows): array
{
    $activities = [];
    $categories = [];
    $projectBreakdown = [];
    $summary = [
        'total' => 0,
        'critical_count' => 0,
        'overdue_start_count' => 0,
        'due_next_7_days_count' => 0,
        'unassigned_responsible_count' => 0,
        'unassigned_subcontractor_count' => 0,
        'unknown_category_count' => 0,
    ];

    foreach ($rows as $row) {
        $key = implode(':', [(int) $row['project_id'], (int) $row['Semana'], (int) $row['Consecutivo']]);
        $category = cnpCanonicalCategory((string) ($row['Categoria_CNP'] ?? ''));
        $start = cnpStartState($row['Fecha_Inicio'] ?? null, $row['cutoff'] ?? null);
        $projectId = (int) $row['project_id'];
        $critical = (int) ($row['Critica'] ?? 0) === 1;
        $responsible = trim((string) ($row['Responsable_AIA'] ?? ''));
        $subcontractor = trim((string) ($row['Sub_Contratista'] ?? ''));

        $summary['total']++;
        $summary['critical_count'] += $critical ? 1 : 0;
        $summary['overdue_start_count'] += $start['start_status'] === 'overdue' ? 1 : 0;
        $summary['due_next_7_days_count'] += in_array($start['start_status'], ['due_today', 'next_7_days'], true) ? 1 : 0;
        $summary['unassigned_responsible_count'] += $responsible === '' ? 1 : 0;
        $summary['unassigned_subcontractor_count'] += $subcontractor === '' ? 1 : 0;
        $summary['unknown_category_count'] += $category['known'] ? 0 : 1;
        $categories[$category['canonical']] = ($categories[$category['canonical']] ?? 0) + 1;
        $projectMetrics = ['total', 'critical_count', 'overdue_start_count', 'unassigned_responsible_count', 'unassigned_subcontractor_count'];
        $projectBreakdown[$projectId] ??= ['project_id' => $projectId] + array_fill_keys($projectMetrics, 0);
        foreach ($projectMetrics as $metric) {
            $projectBreakdown[$projectId][$metric] += $metric === 'total' ? 1 : (
                $metric === 'critical_count' ? ($critical ? 1 : 0) : (
                    $metric === 'overdue_start_count' ? ($start['start_status'] === 'overdue' ? 1 : 0) : (
                        $metric === 'unassigned_responsible_count' ? ($responsible === '' ? 1 : 0) : (
                            $subcontractor === '' ? 1 : 0
                        )
                    )
                )
            );
        }
        $activities[$key] = [
            'source_row_key' => $key,
            'cutoff' => (string) ($row['cutoff'] ?? ''),
            'start_status' => $start['start_status'],
            'days_to_start' => $start['days_to_start'],
            'critical' => $critical,
            'responsible' => $responsible,
            'subcontractor' => $subcontractor,
        ];
    }

    arsort($categories);
    return ['summary' => $summary, 'categories' => $categories, 'project_breakdown' => $projectBreakdown, 'activities' => $activities];
}

function cnpProjectBreakdownById(mixed $breakdown): array
{
    $items = is_array($breakdown) && array_is_list($breakdown) ? $breakdown : (is_array($breakdown) ? array_values($breakdown) : []);
    $indexed = [];
    foreach ($items as $item) {
        if (is_array($item) && isset($item['project_id'])) {
            $indexed[(int) $item['project_id']] = $item;
        }
    }
    return $indexed;
}

function cnpCategoryCounts(mixed $categories): array
{
    if (!is_array($categories)) {
        return [];
    }
    if (!array_is_list($categories)) {
        return array_map('intval', $categories);
    }
    $counts = [];
    foreach ($categories as $category) {
        if (is_array($category) && isset($category['category'])) {
            $counts[(string) $category['category']] = (int) ($category['count'] ?? 0);
        }
    }
    arsort($counts);
    return $counts;
}

function cnpAssertScenario(
    array &$failures,
    ControlTowerService $bi,
    \Database $db,
    string $label,
    array $projectIds,
    string $semana,
    array $filters,
): void {
    $sourceRows = cnpDirectRows($db, $projectIds, $semana, $filters);
    $expected = cnpExpected($sourceRows);
    $brief = $bi->getBrief('programa-general', $projectIds, $semana, 'R', $filters);
    $chart = $brief['charts']['programa-cnp'] ?? [];
    $metrics = $chart['metrics'] ?? [];

    cnpAssert($failures, ($chart['source_relations'] ?? []) === ['programacion_semanal', 'semanas_activas'], "{$label}: CNP must declare its activity and weekly-cutoff sources");
    cnpAssert($failures, ($chart['grain'] ?? '') === 'project_id + Semana + Consecutivo; universo CNP/CNC por estado real y urgencia al corte semanal', "{$label}: CNP grain changed");
    cnpAssert($failures, ($metrics['metric_key'] ?? '') === 'pg_cnp_activity_count', "{$label}: metric_key is missing or incorrect");
    cnpAssert($failures, ($metrics['source_relations'] ?? []) === ['programacion_semanal', 'semanas_activas'], "{$label}: CNP metrics omit the weekly cutoff source");
    foreach ($expected['summary'] as $field => $value) {
        cnpAssert($failures, (int) ($metrics[$field] ?? -1) === $value, "{$label}: {$field} does not reconcile to programacion_semanal");
    }
    cnpAssert($failures, cnpCategoryCounts($metrics['categories'] ?? null) === $expected['categories'], "{$label}: categories do not reconcile to the independent CNP query");
    cnpAssert($failures, (int) array_sum($chart['datasets'][0]['data'] ?? []) === $expected['summary']['total'], "{$label}: chart total does not reconcile to programacion_semanal");

    $actualBreakdown = cnpProjectBreakdownById($metrics['project_breakdown'] ?? []);
    foreach ($expected['project_breakdown'] as $projectId => $projectMetrics) {
        foreach ($projectMetrics as $field => $value) {
            cnpAssert($failures, (int) ($actualBreakdown[$projectId][$field] ?? -1) === $value, "{$label}: project {$projectId} {$field} does not reconcile");
        }
    }

    $detail = $bi->getProgramaCnpDetail($projectIds, $semana, $filters, '', 1, 0);
    cnpAssert($failures, ($detail['respuesta'] ?? '') === 'BIEN', "{$label}: CNP detail did not return BIEN");
    cnpAssert($failures, (int) ($detail['summary']['total'] ?? -1) === $expected['summary']['total'], "{$label}: detail summary total differs from chart/source total");
    cnpAssert($failures, (int) ($detail['pagination']['total'] ?? -1) === $expected['summary']['total'], "{$label}: detail pagination total differs from source total");
    cnpAssert($failures, (int) ($detail['pagination']['returned_count'] ?? -1) === min(1, $expected['summary']['total']), "{$label}: detail returned_count ignores limit");
    cnpAssert($failures, (int) ($detail['pagination']['next_offset'] ?? -1) === min(1, $expected['summary']['total']), "{$label}: detail next_offset is not deterministic");
    cnpAssert($failures, ($detail['pagination']['has_more'] ?? null) === ($expected['summary']['total'] > 1), "{$label}: detail has_more does not match total and page size");
    if ($expected['summary']['total'] > 1) {
        $nextPage = $bi->getProgramaCnpDetail($projectIds, $semana, $filters, '', 1, 1, false);
        cnpAssert($failures, (int) ($nextPage['pagination']['total'] ?? -1) === $expected['summary']['total'], "{$label}: SQL-paginated detail lost the total");
        cnpAssert($failures, count($nextPage['activities'] ?? []) === 1, "{$label}: SQL-paginated detail ignored limit/offset");
        $firstKey = (string) ($detail['activities'][0]['source_row_key'] ?? '');
        $nextKey = (string) ($nextPage['activities'][0]['source_row_key'] ?? '');
        cnpAssert($failures, $nextKey !== '' && $nextKey !== $firstKey, "{$label}: SQL-paginated detail duplicated the first page");
        cnpAssert($failures, isset($expected['activities'][$nextKey]), "{$label}: SQL-paginated detail escaped the filtered source population");
    }

    $leadingCategory = array_key_first($expected['categories']);
    if ($leadingCategory !== null) {
        $categoryDetail = $bi->getProgramaCnpDetail($projectIds, $semana, $filters, $leadingCategory, 1, 0, true);
        cnpAssert($failures, (int) ($categoryDetail['pagination']['total'] ?? -1) === (int) $expected['categories'][$leadingCategory], "{$label}: category segment total does not reconcile");
        foreach ($categoryDetail['activities'] ?? [] as $activity) {
            cnpAssert($failures, ($activity['category_canonical'] ?? '') === $leadingCategory, "{$label}: category segment leaked a different CNP category");
        }
        if (($categoryDetail['pagination']['has_more'] ?? false) === true) {
            $categoryNext = $bi->getProgramaCnpDetail($projectIds, $semana, $filters, $leadingCategory, 1, 1, false);
            cnpAssert($failures, (int) ($categoryNext['pagination']['total'] ?? -1) === (int) $expected['categories'][$leadingCategory], "{$label}: incremental category total does not reconcile");
            cnpAssert($failures, (int) ($categoryNext['pagination']['offset'] ?? -1) === 1, "{$label}: incremental category page ignored its SQL offset");
            foreach ($categoryNext['activities'] ?? [] as $activity) {
                cnpAssert($failures, ($activity['category_canonical'] ?? '') === $leadingCategory, "{$label}: incremental category page leaked a different CNP category");
            }
        }
    }

    $allActivities = [];
    $offset = 0;
    do {
        $page = $bi->getProgramaCnpDetail($projectIds, $semana, $filters, '', 100, $offset);
        $pageActivities = is_array($page['activities'] ?? null) ? $page['activities'] : [];
        array_push($allActivities, ...$pageActivities);
        $nextOffset = (int) ($page['pagination']['next_offset'] ?? $offset);
        $hasMore = (bool) ($page['pagination']['has_more'] ?? false);
        cnpAssert($failures, !$hasMore || $nextOffset > $offset, "{$label}: CNP pagination did not advance");
        $offset = $nextOffset;
    } while ($hasMore && $offset <= $expected['summary']['total']);

    cnpAssert($failures, count($allActivities) === $expected['summary']['total'], "{$label}: paginated CNP detail does not contain every source activity");
    $seen = [];
    foreach ($allActivities as $activity) {
        foreach (['source_row_key', 'cutoff', 'start_status', 'days_to_start', 'priority', 'impact', 'recommended_action', 'responsible', 'subcontractor', 'critical'] as $field) {
            cnpAssert($failures, array_key_exists($field, $activity), "{$label}: CNP detail activity is missing {$field}");
        }
        $key = (string) ($activity['source_row_key'] ?? '');
        $seen[$key] = true;
        $source = $expected['activities'][$key] ?? null;
        cnpAssert($failures, $source !== null, "{$label}: detail leaked a CNP activity outside the independent source scope");
        if ($source === null) continue;
        cnpAssert($failures, (string) ($activity['cutoff'] ?? '') === $source['cutoff'], "{$label}: detail cutoff mismatch for {$key}");
        cnpAssert($failures, ($activity['start_status'] ?? null) === $source['start_status'], "{$label}: detail start_status mismatch for {$key}");
        cnpAssert($failures, ($activity['days_to_start'] ?? null) === $source['days_to_start'], "{$label}: detail days_to_start mismatch for {$key}");
        cnpAssert($failures, (bool) ($activity['critical'] ?? false) === $source['critical'], "{$label}: detail critical mismatch for {$key}");
        cnpAssert($failures, trim((string) ($activity['responsible'] ?? '')) === $source['responsible'], "{$label}: detail responsible mismatch for {$key}");
        cnpAssert($failures, trim((string) ($activity['subcontractor'] ?? '')) === $source['subcontractor'], "{$label}: detail subcontractor mismatch for {$key}");
        foreach (['priority', 'impact', 'recommended_action'] as $field) {
            cnpAssert($failures, trim((string) ($activity[$field] ?? '')) !== '', "{$label}: detail {$field} is not actionable for {$key}");
        }
    }
    cnpAssert($failures, count($seen) === $expected['summary']['total'], "{$label}: full CNP detail contains duplicate or missing source keys");
}

$jmcRows = cnpDirectRows($db, [68], '6');
cnpAssert($failures, count($jmcRows) === 33, 'JMC project 68 week 6 must retain exactly 33 CNP source rows');

$duplicateGrains = $db->query(
    "SELECT COUNT(*) FROM (
        SELECT project_id, Semana, Consecutivo
        FROM programacion_semanal
        WHERE Activa = '0' AND COALESCE(TRIM(CNP), '') <> ''
        GROUP BY project_id, Semana, Consecutivo
        HAVING COUNT(*) > 1
    ) AS duplicate_cnp_grains"
)->fetchColumn();
cnpAssert($failures, (int) $duplicateGrains === 0, 'CNP source must be unique at project_id + Semana + Consecutivo');

$secondProject = $db->query(
    "SELECT DISTINCT project_id FROM programacion_semanal
     WHERE Activa = '0' AND COALESCE(TRIM(CNP), '') <> '' AND project_id <> 68
     ORDER BY project_id LIMIT 1"
)->fetchColumn();
cnpAssert($failures, $secondProject !== false, 'fixture requires a second project with CNP for the multi-project contract');

$week = $db->prepare('SELECT Fecha_Inicio_Sem, Fecha_Fin_Sem FROM semanas_activas WHERE project_id = 68 AND Semana = 6 LIMIT 1');
$week->execute();
$weekRange = $week->fetch(PDO::FETCH_ASSOC) ?: [];
$responsible = '';
$subcontractor = '';
$stage = '';
foreach ($jmcRows as $row) {
    $responsible = $responsible ?: trim((string) ($row['Responsable_AIA'] ?? ''));
    $subcontractor = $subcontractor ?: trim((string) ($row['Sub_Contratista'] ?? ''));
    $stageSource = trim((string) (($row['Ubicacion'] ?? '') ?: ($row['Actividad'] ?? '')));
    if ($stage === '' && $stageSource !== '') {
        $stage = function_exists('mb_substr') ? mb_substr($stageSource, 0, 24, 'UTF-8') : substr($stageSource, 0, 24);
    }
}
cnpAssert($failures, $responsible !== '', 'fixture requires one JMC week 6 CNP row with Responsable AIA');
cnpAssert($failures, $subcontractor !== '', 'fixture requires one JMC week 6 CNP row with Sub-Contratista');
cnpAssert($failures, $stage !== '', 'fixture requires one JMC week 6 CNP row with activity or location text');

cnpAssertScenario($failures, $bi, $db, 'JMC single project/week', [68], '6', []);
if ($secondProject !== false) {
    cnpAssertScenario($failures, $bi, $db, 'CNP multi-project/week', [68, (int) $secondProject], '6', []);
}
if (($weekRange['Fecha_Inicio_Sem'] ?? '') !== '' && ($weekRange['Fecha_Fin_Sem'] ?? '') !== '') {
    cnpAssertScenario($failures, $bi, $db, 'JMC date range', [68], '', ['desde' => $weekRange['Fecha_Inicio_Sem'], 'hasta' => $weekRange['Fecha_Fin_Sem']]);
}
if ($responsible !== '') {
    cnpAssertScenario($failures, $bi, $db, 'JMC responsible filter', [68], '6', ['resp' => $responsible]);
}
if ($subcontractor !== '') {
    cnpAssertScenario($failures, $bi, $db, 'JMC subcontractor filter', [68], '6', ['sub' => $subcontractor]);
}
if ($stage !== '') {
    cnpAssertScenario($failures, $bi, $db, 'JMC stage/intervention filter', [68], '6', ['etapa' => $stage]);
}

$cncContexts = $db->query(
    "SELECT DISTINCT project_id, Semana
     FROM programacion_semanal
     WHERE Activa IN ('1', 'NA') AND COALESCE(TRIM(CNC), '') <> ''
     ORDER BY project_id, Semana
     LIMIT 20"
)->fetchAll(PDO::FETCH_ASSOC);
cnpAssert($failures, $cncContexts !== [], 'fixture requires CNC rows to protect the shared causal narrative');
$cncActivitiesChecked = 0;
foreach ($cncContexts as $context) {
    $cncDetail = $bi->getProgramaCncDetail([(int) $context['project_id']], (string) $context['Semana']);
    foreach ($cncDetail['activities'] ?? [] as $activity) {
        $cncActivitiesChecked++;
        $narrative = strtolower((string) ($activity['impact'] ?? '') . ' ' . (string) ($activity['recommended_action'] ?? ''));
        cnpAssert($failures, !str_contains($narrative, 'no programada'), 'CNC narrative incorrectly describes an unprogrammed activity');
        cnpAssert($failures, !str_contains($narrative, 'fuera del compromiso'), 'CNC narrative incorrectly uses the CNP commitment state');
    }
}
cnpAssert($failures, $cncActivitiesChecked > 0, 'shared causal narrative regression did not inspect any CNC activity');

if ($failures) {
    fwrite(STDERR, "=== BI Programa General CNP contract: FAILED ===\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}

echo "=== BI Programa General CNP contract: PASS ===\n";
