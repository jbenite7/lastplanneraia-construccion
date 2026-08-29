<?php

declare(strict_types=1);
// @requiere: datos-proyecto


require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/support/BiContractFixture.php';

use App\Services\ControlTowerService;
use App\Security\DataScope\MultiProjectScope;
use App\Security\DataScope\ProjectScope;
use App\Security\DataScope\SystemScopeRunner;

function timelineGlobalRead(Database $db, string $case, callable $read): mixed
{
    return (new SystemScopeRunner($db->dataScope()))->run(
        'test:test_bi_programa_general_activity_timeline:discovery:' . $case,
        $read,
    );
}

function timelineProjectRead(Database $db, int $projectId, callable $read): mixed
{
    $db->dataScope()->bind(new ProjectScope($projectId, 'fixture-bi-timeline', 'R'));
    try {
        return $read();
    } finally {
        $db->dataScope()->clear();
    }
}

/**
 * Regression contract for the Programa General activity timeline.
 *
 * The SQL oracle deliberately does not use ControlTowerService internals: it
 * selects the current weekly snapshot directly from programa_consolidado and
 * semanas_activas, then calculates the published duration-weighted metrics.
 */

function timelineAssert(array &$failures, bool $condition, string $message): void
{
    if (!$condition) {
        $failures[] = $message;
    }
}

function timelineAssertClose(array &$failures, string $label, mixed $actual, float $expected, float $tolerance = 0.05): void
{
    if (!is_numeric($actual) || abs((float) $actual - $expected) > $tolerance) {
        $failures[] = sprintf('%s: expected %.4f, got %s', $label, $expected, json_encode($actual));
    }
}

function timelineDate(string $value): DateTimeImmutable
{
    return new DateTimeImmutable($value);
}

function timelineInclusiveDays(string $start, string $finish): int
{
    return (int) timelineDate($start)->diff(timelineDate($finish))->format('%a') + 1;
}

function timelinePlannedPct(string $start, string $finish, string $cutoff): float
{
    $startDate = timelineDate($start);
    $finishDate = timelineDate($finish);
    $cutoffDate = timelineDate($cutoff);
    if ($cutoffDate < $startDate) {
        return 0.0;
    }
    if ($cutoffDate >= $finishDate) {
        return 100.0;
    }

    return (timelineInclusiveDays($start, $cutoff) / timelineInclusiveDays($start, $finish)) * 100.0;
}

function timelineDisplayText(string $value): string
{
    $value = trim($value);
    for ($pass = 0; $pass < 3 && $value !== ''; $pass++) {
        $decoded = preg_replace_callback('/(?:Ã.|Â.|â..)+/u', static function (array $match): string {
            $candidate = mb_convert_encoding($match[0], 'Windows-1252', 'UTF-8');
            if (!mb_check_encoding($candidate, 'UTF-8')) {
                return $match[0];
            }

            return str_contains($candidate, '?') && !str_contains($match[0], '?')
                ? $match[0]
                : $candidate;
        }, $value);
        if (!is_string($decoded) || $decoded === $value) {
            break;
        }
        $value = trim($decoded);
    }

    return $value;
}

function timelineHasMojibake(string $value): bool
{
    return preg_match('/(?:Ã.|Â.|â..)+/u', $value) === 1;
}

/**
 * Independent source oracle. A date range selects weekly snapshots that
 * overlap it; otherwise the requested week is the maximum eligible snapshot.
 */
function timelineOracle(Database $db, array $projectIds, string $semana, array $filters = []): array
{
    $projectIds = array_values(array_filter(array_map('intval', $projectIds), static fn(int $id): bool => $id > 0));
    $placeholders = implode(',', array_fill(0, count($projectIds), '?'));
    $where = ["pc.project_id IN ({$placeholders})", 'COALESCE(pc.Titulo, 0) = 0', 'pc.Fecha_Inicio IS NOT NULL', 'pc.Fecha_Fin IS NOT NULL', 'pc.Fecha_Fin >= pc.Fecha_Inicio'];
    $params = $projectIds;
    $desde = trim((string) ($filters['desde'] ?? ''));
    $hasta = trim((string) ($filters['hasta'] ?? ''));
    if ($desde !== '' || $hasta !== '') {
        $where[] = "EXISTS (
            SELECT 1 FROM semanas_activas sa_filter
            WHERE sa_filter.project_id IN ({$placeholders})
              AND sa_filter.project_id = pc.project_id
              AND sa_filter.Semana = pc.Semana
              AND sa_filter.Fecha_Inicio_Sem <= ?
              AND sa_filter.Fecha_Fin_Sem >= ?
        )";
        array_push($params, ...$projectIds);
        $params[] = $hasta !== '' ? $hasta : '9999-12-31';
        $params[] = $desde !== '' ? $desde : '1000-01-01';
    } elseif ($semana !== '') {
        $where[] = 'pc.Semana <= ?';
        $params[] = $semana;
    }
    foreach (['sub' => 'Sub_Contratista', 'resp' => 'Responsable_AIA'] as $filter => $column) {
        $value = trim((string) ($filters[$filter] ?? ''));
        if ($value !== '') {
            $where[] = "LOWER(COALESCE(pc.{$column}, '')) LIKE ?";
            $params[] = '%' . strtolower($value) . '%';
        }
    }

    $statement = $db->queryForProjects(
        new MultiProjectScope($projectIds, 'fixture-bi-timeline', 'R', 'test:test_bi_programa_general_activity_timeline:oracle'),
        'SELECT pc.project_id, pc.Semana, pc.Consecutivo_en_Programa AS unique_id, pc.Actividad, pc.Fecha_Inicio, pc.Fecha_Fin,
                pc.Ruta_Critica, pc.Ejecutado, pc.Estado, pc.Sub_Contratista, pc.Responsable_AIA,
                COALESCE(sa.Fecha_Fin_Sem, sa.Fecha_Inicio_Sem) AS cutoff
         FROM programa_consolidado pc
         LEFT JOIN semanas_activas sa ON sa.project_id = pc.project_id AND sa.Semana = pc.Semana
         WHERE ' . implode(' AND ', $where) . '
         ORDER BY pc.project_id, pc.Semana, pc.unique_id',
        $params,
    );
    $rows = $statement->fetchAll(PDO::FETCH_ASSOC);

    $snapshots = [];
    foreach ($rows as $row) {
        $projectId = (int) $row['project_id'];
        $uniqueId = (int) $row['unique_id'];
        $cutoff = (string) ($row['cutoff'] ?? '');
        if ($projectId <= 0 || $uniqueId <= 0 || $cutoff === '') {
            continue;
        }
        $snapshots[$projectId][$cutoff][$projectId . ':' . $uniqueId] = $row;
    }

    $baseline = [];
    foreach ($snapshots as $projectId => $byCutoff) {
        ksort($byCutoff, SORT_STRING);
        $latestCutoff = array_key_last($byCutoff);
        foreach ($byCutoff[$latestCutoff] as $key => $row) {
            $baseline[$key] = $row;
        }
    }

    $totalDuration = array_sum(array_map(
        static fn(array $row): int => timelineInclusiveDays((string) $row['Fecha_Inicio'], (string) $row['Fecha_Fin']),
        $baseline,
    ));
    $activities = [];
    foreach ($baseline as $key => $row) {
        $duration = timelineInclusiveDays((string) $row['Fecha_Inicio'], (string) $row['Fecha_Fin']);
        $realPct = min(100.0, max(0.0, (float) ($row['Ejecutado'] ?? 0) * 100.0));
        $plannedPct = timelinePlannedPct((string) $row['Fecha_Inicio'], (string) $row['Fecha_Fin'], (string) $row['cutoff']);
        $weightPct = $totalDuration > 0 ? ($duration / $totalDuration) * 100.0 : 0.0;
        $late = (string) $row['Fecha_Fin'] < (string) $row['cutoff'] && $realPct < 100.0;
        $activities[$key] = [
            'activity_key' => $key,
            'project_id' => (int) $row['project_id'],
            'unique_id' => (int) $row['unique_id'],
            'planned_start' => (string) $row['Fecha_Inicio'],
            'planned_finish' => (string) $row['Fecha_Fin'],
            'cutoff' => (string) $row['cutoff'],
            'duration_days' => $duration,
            'real_pct' => $realPct,
            'planned_pct' => $plannedPct,
            'gap_pp' => $realPct - $plannedPct,
            'weight_pct' => $weightPct,
            'real_contribution_pp' => ($realPct * $weightPct) / 100.0,
            'planned_contribution_pp' => ($plannedPct * $weightPct) / 100.0,
            'recoverable_pp' => (max(0.0, $plannedPct - $realPct) * $weightPct) / 100.0,
            'critical' => (int) ($row['Ruta_Critica'] ?? 0) === 1,
            'late' => $late,
            'observed_delay_days' => $late ? timelineInclusiveDays((string) $row['Fecha_Fin'], (string) $row['cutoff']) - 1 : 0,
            'responsible' => timelineDisplayText((string) ($row['Responsable_AIA'] ?? '')) ?: 'Sin asignar',
            'subcontractor' => timelineDisplayText((string) ($row['Sub_Contratista'] ?? '')) ?: 'Sin asignar',
        ];
    }

    $summary = ['total' => count($activities), 'total_duration' => $totalDuration];
    foreach (['real_contribution_pp' => 'real_pct', 'planned_contribution_pp' => 'theoretical_pct'] as $contribution => $field) {
        $summary[$field] = round(array_sum(array_column($activities, $contribution)), 1);
    }
    $summary['gap_pp'] = round($summary['real_pct'] - $summary['theoretical_pct'], 1);

    return compact('activities', 'summary');
}

function timelineAssertDetail(array &$failures, array $detail, array $oracle, string $label, bool $assertAllPages = false): void
{
    $requiredTopLevel = ['summary', 'activities', 'pagination'];
    foreach ($requiredTopLevel as $field) {
        timelineAssert($failures, array_key_exists($field, $detail), "{$label}: missing top-level {$field}");
    }
    timelineAssert($failures, ($detail['source_relations'] ?? null) === ['programa_consolidado', 'semanas_activas'], "{$label}: source relations changed");
    timelineAssert($failures, ($detail['grain'] ?? null) === 'project_id + Semana + unique_id', "{$label}: grain changed");
    timelineAssert($failures, (int) ($detail['pagination']['total'] ?? -1) === $oracle['summary']['total'], "{$label}: pagination total differs from independent SQL universe");
    foreach (['real_pct', 'theoretical_pct', 'gap_pp'] as $field) {
        timelineAssertClose($failures, "{$label}: summary {$field}", $detail['summary'][$field] ?? null, $oracle['summary'][$field]);
    }

    $requiredActivityFields = ['activity_key', 'project_id', 'project', 'unique_id', 'activity', 'planned_start', 'planned_finish', 'cutoff', 'duration_days', 'real_pct', 'planned_pct', 'gap_pp', 'weight_pct', 'real_contribution_pp', 'planned_contribution_pp', 'recoverable_pp', 'state', 'critical', 'late', 'observed_delay_days', 'responsible', 'subcontractor'];
    $actualTotals = ['weight_pct' => 0.0, 'real_contribution_pp' => 0.0, 'planned_contribution_pp' => 0.0];
    foreach ($detail['activities'] ?? [] as $activity) {
        foreach ($requiredActivityFields as $field) {
            timelineAssert($failures, array_key_exists($field, $activity), "{$label}: activity missing {$field}");
        }
        foreach (['project', 'activity', 'stage', 'responsible', 'subcontractor'] as $field) {
            timelineAssert(
                $failures,
                !timelineHasMojibake((string) ($activity[$field] ?? '')),
                "{$label}: activity {$field} still contains mojibake",
            );
        }
        $key = (string) ($activity['activity_key'] ?? '');
        $expected = $oracle['activities'][$key] ?? null;
        timelineAssert($failures, $expected !== null, "{$label}: activity {$key} escaped the SQL universe");
        if ($expected === null) {
            continue;
        }
        foreach (['project_id', 'unique_id', 'planned_start', 'planned_finish', 'cutoff', 'duration_days', 'critical', 'late', 'observed_delay_days', 'responsible', 'subcontractor'] as $field) {
            timelineAssert($failures, ($activity[$field] ?? null) === $expected[$field], "{$label}: {$key} {$field} differs from SQL oracle");
        }
        foreach (['real_pct', 'planned_pct', 'gap_pp', 'weight_pct', 'real_contribution_pp', 'planned_contribution_pp', 'recoverable_pp'] as $field) {
            timelineAssertClose($failures, "{$label}: {$key} {$field}", $activity[$field] ?? null, round($expected[$field], $field === 'weight_pct' || str_contains($field, 'contribution') || $field === 'recoverable_pp' ? 2 : 1));
            $actualTotals[$field] = ($actualTotals[$field] ?? 0.0) + (float) ($activity[$field] ?? 0);
        }
    }

    if ($assertAllPages) {
        $roundingTolerance = (count($detail['activities'] ?? []) * 0.005) + 0.05;
        timelineAssertClose($failures, "{$label}: weight sum", $actualTotals['weight_pct'], 100.0, $roundingTolerance);
        timelineAssertClose($failures, "{$label}: real contribution sum", $actualTotals['real_contribution_pp'], (float) ($detail['summary']['real_pct'] ?? 0), $roundingTolerance);
        timelineAssertClose($failures, "{$label}: planned contribution sum", $actualTotals['planned_contribution_pp'], (float) ($detail['summary']['theoretical_pct'] ?? 0), $roundingTolerance);
    }
}

$db = Database::getInstance();
BiContractFixture::seedProgramSnapshots($db);
$bi = new ControlTowerService();
$scope = static fn(array $ids, string $case): MultiProjectScope => new MultiProjectScope(
    $ids,
    'fixture-bi-timeline',
    'R',
    'test:test_bi_programa_general_activity_timeline:' . $case,
);
$failures = [];
$jmcProjectId = BiContractFixture::PROYECTO_A;
$jmcWeek = '3';
$jmcOracle = timelineOracle($db, [$jmcProjectId], $jmcWeek);

timelineAssert($failures, timelineDisplayText('Proyecto regional CI') === 'Proyecto regional CI', 'valid UTF-8 display text must remain unchanged');
timelineAssert($failures, timelineDisplayText('FabricaciÃ³n') === 'Fabricación', 'known mojibake must be repaired for display');
timelineAssert($failures, timelineDisplayText('CapÃƒÂ­tulo') === 'Capítulo', 'double-encoded mojibake must be repaired for display');
timelineAssert($failures, timelineDisplayText('FabricaciÃ³n 🚧') === 'Fabricación 🚧', 'mixed mojibake and valid UTF-8 must preserve the valid fragment');
timelineAssert($failures, timelineDisplayText('Comilla â€™') === 'Comilla ’', 'Windows-1252 punctuation mojibake must be repaired');

timelineAssert($failures, $jmcOracle['summary']['total'] > 2, 'canonical CI snapshot must expose a paginable non-title activity universe');
timelineAssert($failures, $jmcOracle['summary']['total_duration'] > 0, 'canonical CI snapshot must retain valid inclusive durations');
timelineAssert($failures, count($jmcOracle['activities']) === $jmcOracle['summary']['total'], 'canonical SQL grain must be unique by project_id + unique_id at the weekly cutoff');

$limit = 1;
$offset = 0;
$seen = [];
$allActivities = [];
$firstDetail = null;
do {
    $page = $bi->getProgramaProgressDetail($scope([$jmcProjectId], 'pagination'), $jmcWeek, [], $limit, $offset);
    timelineAssertDetail($failures, $page, $jmcOracle, "canonical snapshot offset {$offset}");
    if ($firstDetail === null) {
        $firstDetail = $page;
    }
    foreach ($page['activities'] ?? [] as $activity) {
        $key = (string) ($activity['activity_key'] ?? '');
        timelineAssert($failures, $key !== '' && !isset($seen[$key]), "canonical snapshot pagination repeated {$key}");
        $seen[$key] = true;
        $allActivities[] = $activity;
    }
    $nextOffset = (int) ($page['pagination']['next_offset'] ?? -1);
    $hasMore = (bool) ($page['pagination']['has_more'] ?? false);
    timelineAssert($failures, !$hasMore || $nextOffset > $offset, "canonical snapshot pagination did not advance from {$offset}");
    $offset = $nextOffset;
} while ($hasMore && $offset <= $jmcOracle['summary']['total']);

timelineAssert($failures, count($seen) === $jmcOracle['summary']['total'], 'canonical snapshot pagination lost activities from the SQL universe');
if ($firstDetail !== null) {
    $sumDetail = $firstDetail;
    $sumDetail['activities'] = $allActivities;
    timelineAssertDetail($failures, $sumDetail, $jmcOracle, 'canonical snapshot full activity contributions', true);
}

$asymmetricProjects = [$jmcProjectId, BiContractFixture::PROYECTO_B];
$asymmetricOracle = timelineOracle($db, $asymmetricProjects, $jmcWeek);
timelineAssert(
    $failures,
    $asymmetricOracle['summary']['total'] > $jmcOracle['summary']['total'],
    'canonical multi-project report must include the latest eligible snapshot from both projects',
);
$asymmetricBrief = $bi->getBrief($scope($asymmetricProjects, 'asymmetric-brief'), 'programa-general', $jmcWeek);
$asymmetricSnapshot = is_array($asymmetricBrief['activity_snapshot'] ?? null) ? $asymmetricBrief['activity_snapshot'] : [];
$asymmetricDetail = $bi->getProgramaProgressDetail($scope($asymmetricProjects, 'asymmetric-detail'), $jmcWeek, [], 25, 0);
timelineAssertDetail($failures, $asymmetricSnapshot, $asymmetricOracle, 'asymmetric multi-project initial snapshot');
timelineAssertDetail($failures, $asymmetricDetail, $asymmetricOracle, 'asymmetric multi-project first detail page');
timelineAssert(
    $failures,
    array_column($asymmetricSnapshot['activities'] ?? [], 'activity_key') === array_column($asymmetricDetail['activities'] ?? [], 'activity_key'),
    'initial snapshot and first detail page must publish the same ordered activity keys',
);
timelineAssert(
    $failures,
    ($asymmetricSnapshot['summary'] ?? []) === ($asymmetricDetail['summary'] ?? []),
    'initial snapshot and detail endpoint must use the same multiproject denominator',
);

$filterContext = timelineProjectRead($db, $jmcProjectId, static function () use ($db, $jmcProjectId, $jmcWeek): array|false {
    $statement = $db->prepare(
    "SELECT Sub_Contratista AS sub, Responsable_AIA AS resp
     FROM programa_consolidado
     WHERE project_id = ? AND Semana = ? AND COALESCE(Titulo, 0) = 0
       AND TRIM(COALESCE(Sub_Contratista, '')) <> '' AND TRIM(COALESCE(Responsable_AIA, '')) <> ''
     GROUP BY Sub_Contratista, Responsable_AIA
     ORDER BY COUNT(*) DESC, Sub_Contratista, Responsable_AIA LIMIT 1",
    );
    $statement->execute([$jmcProjectId, $jmcWeek]);
    return $statement->fetch(PDO::FETCH_ASSOC);
});
timelineAssert($failures, is_array($filterContext), 'canonical CI snapshot needs a subcontractor/responsible filter context');
if (is_array($filterContext)) {
    $filters = ['sub' => (string) $filterContext['sub'], 'resp' => (string) $filterContext['resp']];
    $filteredOracle = timelineOracle($db, [$jmcProjectId], $jmcWeek, $filters);
    timelineAssert($failures, $filteredOracle['summary']['total'] > 0, 'canonical sub/responsible oracle must not be empty');
    $filteredDetail = $bi->getProgramaProgressDetail($scope([$jmcProjectId], 'filtered'), $jmcWeek, $filters, 100, 0);
    timelineAssertDetail($failures, $filteredDetail, $filteredOracle, 'canonical sub/responsible filters');
}

$emptyDetail = $bi->getProgramaProgressDetail(
    $scope([$jmcProjectId], 'empty'),
    '',
    ['desde' => '1900-01-01', 'hasta' => '1900-01-07'],
    100,
    0,
);
timelineAssert($failures, ($emptyDetail['respuesta'] ?? '') === 'BIEN', 'a valid empty timeline must return a structured success response');
timelineAssert($failures, (int) ($emptyDetail['pagination']['total'] ?? -1) === 0, 'a valid empty timeline must publish zero total records');
timelineAssert($failures, (float) ($emptyDetail['summary']['real_pct'] ?? -1) === 0.0, 'a valid empty timeline must publish zero real progress');
timelineAssert($failures, (float) ($emptyDetail['summary']['theoretical_pct'] ?? -1) === 0.0, 'a valid empty timeline must publish zero theoretical progress');
timelineAssert($failures, ($emptyDetail['activities'] ?? null) === [], 'a valid empty timeline must publish an empty activity list');

$earnedDetail = $bi->getProgramaProgressDetail($scope([$jmcProjectId], 'earned'), $jmcWeek, [], 50, 0, 'earned');
$earnedActivities = $earnedDetail['activities'] ?? [];
$earnedContributions = array_column($allActivities, 'real_contribution_pp');
$expectedTopEarned = $earnedContributions === [] ? 0.0 : max($earnedContributions);
timelineAssert($failures, $earnedActivities !== [], 'earned mode must return activities that contribute real progress');
timelineAssertClose($failures, 'earned mode first row', $earnedActivities[0]['real_contribution_pp'] ?? null, (float) $expectedTopEarned, 0.001);
timelineAssert($failures, array_reduce(
    $earnedActivities,
    static fn(bool $valid, array $activity): bool => $valid && (float) ($activity['real_contribution_pp'] ?? 0) > 0,
    true,
), 'earned mode must paginate only real contributors');

$criticalMissing = $bi->getProgramaProgressDetail($scope([$jmcProjectId], 'critical-missing'), $jmcWeek, [], 50, 0, 'missing', true);
timelineAssert($failures, array_reduce(
    $criticalMissing['activities'] ?? [],
    static fn(bool $valid, array $activity): bool => $valid && !empty($activity['critical']) && (float) ($activity['recoverable_pp'] ?? 0) > 0,
    true,
), 'critical-only missing mode must filter the complete universe before pagination');

$secondProjectId = (int) timelineGlobalRead($db, 'second-project', static function () use ($db, $jmcProjectId, $jmcWeek): mixed {
    $statement = $db->prepare(
    "SELECT project_id FROM programa_consolidado
     WHERE project_id <> ? AND Semana = ? AND COALESCE(Titulo, 0) = 0
       AND Fecha_Inicio IS NOT NULL AND Fecha_Fin IS NOT NULL AND Fecha_Fin >= Fecha_Inicio
     GROUP BY project_id ORDER BY project_id LIMIT 1",
    );
    $statement->execute([$jmcProjectId, $jmcWeek]);
    return $statement->fetchColumn();
});
if ($secondProjectId > 0) {
    $multiOracle = timelineOracle($db, [$jmcProjectId, $secondProjectId], $jmcWeek);
    $multiDetail = $bi->getProgramaProgressDetail($scope([$jmcProjectId, $secondProjectId], 'second-project'), $jmcWeek, [], 100, 0);
    timelineAssertDetail($failures, $multiDetail, $multiOracle, 'canonical plus second project');
    timelineAssert($failures, $multiOracle['summary']['total'] > $jmcOracle['summary']['total'], 'multi-project SQL universe must include the second project');
}

$range = timelineProjectRead($db, $jmcProjectId, static function () use ($db, $jmcProjectId, $jmcWeek): array {
    $statement = $db->prepare('SELECT Fecha_Inicio_Sem AS desde, Fecha_Fin_Sem AS hasta FROM semanas_activas WHERE project_id = ? AND Semana = ?');
    $statement->execute([$jmcProjectId, $jmcWeek]);
    return $statement->fetch(PDO::FETCH_ASSOC) ?: [];
});
timelineAssert($failures, !empty($range['desde']) && !empty($range['hasta']), 'canonical CI snapshot needs explicit weekly cutoff dates');
if (!empty($range['desde']) && !empty($range['hasta'])) {
    $rangeFilters = ['desde' => (string) $range['desde'], 'hasta' => (string) $range['hasta']];
    $rangeOracle = timelineOracle($db, [$jmcProjectId], $jmcWeek, $rangeFilters);
    $rangeDetail = $bi->getProgramaProgressDetail($scope([$jmcProjectId], 'date-range'), $jmcWeek, $rangeFilters, 100, 0);
    timelineAssertDetail($failures, $rangeDetail, $rangeOracle, 'canonical date range');
    timelineAssert($failures, ($rangeDetail['filters']['semana'] ?? null) === '', 'date range must override the week filter in the published detail');
}

if ($failures !== []) {
    foreach ($failures as $failure) {
        echo "FAIL: {$failure}\n";
    }
    exit(1);
}

echo "PASS: BI Programa General activity timeline matches the independent sanitized-fixture SQL oracle\n";
