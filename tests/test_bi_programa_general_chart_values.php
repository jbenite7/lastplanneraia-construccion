<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/support/BiContractFixture.php';

use App\Services\ControlTowerService;

$db = \Database::getInstance();
BiContractFixture::seedCausalRows($db);
BiContractFixture::seedProgramSnapshots($db);
$bi = new ControlTowerService();
$failures = [];

if (!method_exists($bi, 'semanticMetricRange')) {
    $failures[] = 'semantic range classifier is missing';
} else {
    $rangeMethod = new ReflectionMethod($bi, 'semanticMetricRange');
    foreach ([[69.9, 'performance'], [70.0, 'performance'], [89.9, 'compliance'], [90.0, 'compliance']] as [$value, $vocabulary]) {
        if ($rangeMethod->invoke($bi, $value, $vocabulary) !== biExpectedSemanticRange($value, $vocabulary)) {
            $failures[] = "semantic range boundary mismatch at {$value}";
        }
    }
}
if (!method_exists($bi, 'schedulePerformanceRange')) {
    $failures[] = 'schedule performance range classifier is missing';
} else {
    $rangeMethod = new ReflectionMethod($bi, 'schedulePerformanceRange');
    foreach ([[94.9, 100.0], [95.0, 100.0], [105.0, 100.0], [105.1, 100.0]] as [$real, $planned]) {
        if ($rangeMethod->invoke($bi, $real, $planned) !== biExpectedScheduleRange($real, $planned)) {
            $failures[] = "schedule performance boundary mismatch at {$real}/{$planned}";
        }
    }
}
if (!method_exists($bi, 'causalCategoryMeta')) {
    $failures[] = 'causal category normalizer is missing';
} else {
    $categoryMethod = new ReflectionMethod($bi, 'causalCategoryMeta');
    foreach ([['Diseño', 'Diseños'], ['M de O', 'Mano de Obra'], ['causa exogena', 'Causas Exógenas']] as [$original, $canonical]) {
        $meta = $categoryMethod->invoke($bi, $original);
        if (($meta['original'] ?? '') !== $original || ($meta['canonical'] ?? '') !== $canonical || ($meta['known'] ?? false) !== true) {
            $failures[] = "causal category alias normalization mismatch for {$original}";
        }
    }
}

function biFail(array &$failures, string $message): void
{
    $failures[] = $message;
}

function biAssertSeries(array &$failures, string $label, array $actual, array $expected): void
{
    if (count($actual) !== count($expected)) {
        biFail($failures, "{$label}: length mismatch actual=" . json_encode($actual) . ' expected=' . json_encode($expected));
        return;
    }
    foreach ($expected as $index => $value) {
        if ($value === null || ($actual[$index] ?? null) === null) {
            if (($actual[$index] ?? null) !== $value) {
                biFail($failures, "{$label}: mismatch actual=" . json_encode($actual) . ' expected=' . json_encode($expected));
                return;
            }
            continue;
        }
        if (abs((float) ($actual[$index] ?? 0) - (float) $value) > 0.05) {
            biFail($failures, "{$label}: mismatch actual=" . json_encode($actual) . ' expected=' . json_encode($expected));
            return;
        }
    }
}

function biExpectedSemanticRange(float $value, string $vocabulary): array
{
    if ($value < 70) {
        return ['key' => 'critical', 'label' => $vocabulary === 'compliance' ? 'No Cumple' : 'Inaceptable', 'color_token' => 'status-critical'];
    }
    if ($value < 90) {
        return ['key' => 'warning', 'label' => $vocabulary === 'compliance' ? 'Cumple Parcialmente' : 'Aceptable', 'color_token' => 'status-warning'];
    }
    return ['key' => 'success', 'label' => $vocabulary === 'compliance' ? 'Cumple' : 'Excelente', 'color_token' => 'status-success'];
}

function biExpectedScheduleRange(float $real, float $planned): array
{
    $performance = $planned > 0 ? round(($real / $planned) * 100, 1) : ($real > 0 ? 150.0 : 100.0);
    if ($performance < 95.0) return ['key' => 'critical', 'label' => 'Atrasado', 'color_token' => 'status-critical'];
    if ($performance > 105.0) return ['key' => 'success', 'label' => 'Adelantado', 'color_token' => 'status-success'];
    return ['key' => 'warning', 'label' => 'A Tiempo', 'color_token' => 'status-warning'];
}

function biAssertDateLabelsChronological(array &$failures, string $label, array $labels): void
{
    if ($labels && (string) $labels[0] !== 'Inicio') {
        biFail($failures, "{$label}: first label is not Inicio");
        return;
    }
    $lastDate = null;
    foreach ($labels as $chartLabel) {
        $chartLabel = (string) $chartLabel;
        if ($chartLabel === 'Inicio' || $chartLabel === '') {
            continue;
        }
        $date = DateTimeImmutable::createFromFormat('d/m/y', $chartLabel);
        if (!$date instanceof DateTimeImmutable) {
            biFail($failures, "{$label}: invalid date label {$chartLabel}");
            return;
        }
        if ($lastDate !== null && $date < $lastDate) {
            biFail($failures, "{$label}: date labels are not chronological");
            return;
        }
        $lastDate = $date;
    }
}

function biWhere(array $projectIds, string $semana, array $filters, string $alias, array $columns, bool $trend = false): array
{
    $projectIds = array_values(array_filter(array_map('intval', $projectIds), fn($id) => $id > 0));
    $where = [$alias . '.project_id IN (' . implode(',', array_fill(0, count($projectIds), '?')) . ')'];
    $params = $projectIds;
    $weekColumn = $columns['week'];

    if (($filters['desde'] ?? '') !== '' || ($filters['hasta'] ?? '') !== '') {
        $where[] = "EXISTS (
            SELECT 1 FROM semanas_activas sa
            WHERE sa.project_id = {$alias}.project_id
              AND sa.Semana = {$alias}.{$weekColumn}
              AND sa.Fecha_Inicio_Sem <= ?
              AND sa.Fecha_Fin_Sem >= ?
        )";
        $params[] = $filters['hasta'] ?: '9999-12-31';
        $params[] = $filters['desde'] ?: '1000-01-01';
    } elseif ($semana !== '') {
        $where[] = $trend ? "{$alias}.{$weekColumn} <= ?" : "{$alias}.{$weekColumn} = ?";
        $params[] = $semana;
    }

    foreach (['sub' => 'sub', 'resp' => 'resp'] as $filterKey => $columnKey) {
        $column = $columns[$columnKey] ?? '';
        $value = trim((string) ($filters[$filterKey] ?? ''));
        if ($column !== '' && $value !== '') {
            $where[] = "LOWER(COALESCE({$alias}.{$column}, '')) LIKE ?";
            $params[] = '%' . strtolower($value) . '%';
        }
    }

    return [implode(' AND ', $where), $params];
}

function biFetchPg(\Database $db, array $projectIds, string $semana, array $filters, bool $trend): array
{
    [$where, $params] = biWhere($projectIds, $semana, $filters, 'pc', [
        'week' => 'Semana',
        'sub' => 'Sub_Contratista',
        'resp' => 'Responsable_AIA',
    ], $trend);
    $dateClause = $trend
        ? ' AND pc.Fecha_Inicio IS NOT NULL AND pc.Fecha_Fin IS NOT NULL AND DATEDIFF(pc.Fecha_Fin, pc.Fecha_Inicio) >= 0'
        : '';
    $duration = '(DATEDIFF(pc.Fecha_Fin, pc.Fecha_Inicio) + 1)';
    $cutoff = 'COALESCE(sa.Fecha_Fin_Sem, sa.Fecha_Inicio_Sem)';
    $elapsed = "(DATEDIFF({$cutoff}, pc.Fecha_Inicio) + 1)";
    $sql = "SELECT
            pc.project_id,
            pc.Semana,
            pc.Consecutivo_en_Programa AS unique_id,
            pc.Id,
            pc.Actividad,
            pc.Titulo,
            pc.Fecha_Inicio,
            pc.Fecha_Fin,
            CASE
                WHEN pc.Fecha_Inicio IS NULL OR pc.Fecha_Fin IS NULL THEN NULL
                WHEN DATEDIFF(pc.Fecha_Fin, pc.Fecha_Inicio) < 0 THEN NULL
                ELSE {$duration}
            END AS duration_days,
            CASE
                WHEN pc.Fecha_Inicio IS NULL OR pc.Fecha_Fin IS NULL THEN 1
                WHEN DATEDIFF(pc.Fecha_Fin, pc.Fecha_Inicio) < 0 THEN 1
                ELSE {$duration}
            END AS curve_weight,
            pc.Ruta_Critica,
            pc.Ejecutado,
            pc.Estado,
            pc.Semanas_Inicio,
            pc.Estado_Restricciones,
            pc.D_y_E,
            pc.Materiales,
            pc.MdeO,
            pc.Equipos,
            pc.Predecesora,
            pc.Sub_Contratista AS sub_contratista,
            pc.Responsable_AIA AS responsable_aia,
            CASE
                WHEN pc.Ruta_Critica = 1
                 AND pc.Fecha_Fin IS NOT NULL
                 AND {$cutoff} IS NOT NULL
                 AND pc.Fecha_Fin < {$cutoff}
                 AND (pc.Ejecutado IS NULL OR pc.Ejecutado < 1)
                THEN 1 ELSE 0
            END AS is_critical_late,
            CASE
                WHEN pc.Fecha_Inicio IS NULL OR pc.Fecha_Fin IS NULL OR {$cutoff} IS NULL THEN NULL
                WHEN DATEDIFF(pc.Fecha_Fin, pc.Fecha_Inicio) < 0 THEN NULL
                WHEN {$elapsed} <= 0 THEN 0
                WHEN {$elapsed} >= {$duration} THEN 1
                ELSE {$elapsed} / {$duration}
            END AS theoretical_progress_by_duration,
            sa.Fecha_Inicio_Sem,
            sa.Fecha_Fin_Sem
        FROM programa_consolidado pc
        LEFT JOIN semanas_activas sa
            ON pc.project_id = sa.project_id
           AND pc.Semana = sa.Semana
        WHERE {$where}
          AND COALESCE(pc.Titulo, 0) = 0
          {$dateClause}
        ORDER BY pc.Semana, pc.Consecutivo_en_Programa";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function biFetchPs(\Database $db, array $projectIds, string $semana, array $filters): array
{
    [$where, $params] = biWhere($projectIds, $semana, $filters, 'ps', [
        'week' => 'Semana',
        'sub' => 'subcontractor',
        'resp' => 'responsible',
    ]);
    $stmt = $db->prepare("SELECT * FROM bi_ps_compromisos ps WHERE {$where} AND ps.Activa IN ('1', 'NA')");
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function biFetchRadar(\Database $db, array $projectIds, string $semana, array $filters): array
{
    [$where, $params] = biWhere($projectIds, $semana, $filters, 'ps', [
        'week' => 'Semana',
        'sub' => 'Sub_Contratista',
        'resp' => 'Responsable_AIA',
    ]);
    $stage = trim((string) ($filters['etapa'] ?? ''));
    if ($stage !== '') {
        $where .= " AND (LOWER(COALESCE(ps.Actividad, '')) LIKE ? OR LOWER(COALESCE(ps.Ubicacion, '')) LIKE ?)";
        $params[] = '%' . strtolower($stage) . '%';
        $params[] = '%' . strtolower($stage) . '%';
    }
    $stmt = $db->prepare("SELECT ps.* FROM programacion_semanal ps WHERE {$where} AND ps.Activa IN ('1', 'NA') ORDER BY ps.project_id, ps.Semana, ps.Consecutivo, ps.row_id");
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function biFetchPsUniverse(\Database $db, array $projectIds, string $semana, array $filters): array
{
    [$where, $params] = biWhere($projectIds, $semana, $filters, 'ps', [
        'week' => 'Semana',
        'sub' => 'subcontractor',
        'resp' => 'responsible',
    ]);
    $stmt = $db->prepare("SELECT * FROM bi_ps_compromisos ps WHERE {$where}");
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function biCurveWeight(array $row): float
{
    $weight = (float) ($row['curve_weight'] ?? 0);
    if ($weight > 0) {
        return $weight;
    }
    $duration = (float) ($row['duration_days'] ?? 0);
    return $duration > 0 ? $duration : 1.0;
}

function biPlannedProgressAtCutoff(array $row, string $cutoff): float
{
    $start = (string) ($row['Fecha_Inicio'] ?? '');
    $finish = (string) ($row['Fecha_Fin'] ?? '');
    if ($start === '' || $finish === '' || $cutoff === '') {
        return 0.0;
    }

    $startDate = new DateTimeImmutable($start);
    $finishDate = new DateTimeImmutable($finish);
    $cutoffDate = new DateTimeImmutable($cutoff);
    if ($cutoffDate < $startDate) {
        return 0.0;
    }
    if ($cutoffDate >= $finishDate) {
        return 1.0;
    }

    $totalDays = max(1, (int) $startDate->diff($finishDate)->format('%a') + 1);
    $elapsedDays = max(0, (int) $startDate->diff($cutoffDate)->format('%a') + 1);
    return min(1.0, max(0.0, $elapsedDays / $totalDays));
}

function biOwnCutoff(array $row): string
{
    $cutoff = trim((string) ($row['Fecha_Fin_Sem'] ?? ''));
    return $cutoff !== '' ? $cutoff : trim((string) ($row['Fecha_Inicio_Sem'] ?? ''));
}

function biSnapshotRowKey(array $row): string
{
    return (int) ($row['project_id'] ?? 0) . ':' . (int) ($row['unique_id'] ?? 0);
}

function biSnapshotIndex(array $rows): array
{
    $snapshots = [];
    foreach ($rows as $row) {
        $projectId = (int) ($row['project_id'] ?? 0);
        $cutoff = biOwnCutoff($row);
        if ($projectId <= 0 || biDate($cutoff) === null) {
            continue;
        }
        $snapshots[$projectId][$cutoff][biSnapshotRowKey($row)] = $row;
    }
    foreach ($snapshots as &$projectSnapshots) {
        ksort($projectSnapshots, SORT_STRING);
    }
    unset($projectSnapshots);
    return $snapshots;
}

function biLatestProjectBaselines(array $snapshots): array
{
    $byProject = [];
    $all = [];
    foreach ($snapshots as $projectId => $projectSnapshots) {
        $latest = $projectSnapshots ? end($projectSnapshots) : [];
        $byProject[$projectId] = $latest;
        foreach ($latest as $key => $row) {
            $all[$key] = $row;
        }
    }
    return [$byProject, $all];
}

function biObservedCutoffDates(array $snapshots): array
{
    $dates = [];
    foreach ($snapshots as $projectSnapshots) {
        $dates += array_combine(array_keys($projectSnapshots), array_keys($projectSnapshots)) ?: [];
    }
    ksort($dates, SORT_STRING);
    return array_values($dates);
}

function biSnapshotAtOrBefore(array $snapshots, string $pointDate): array
{
    $active = [];
    foreach ($snapshots as $cutoff => $rows) {
        if ($cutoff > $pointDate) {
            break;
        }
        $active = ['cutoff' => $cutoff, 'rows' => $rows];
    }
    return $active;
}

function biCurveTotalWeight(array $baseline): float
{
    return array_sum(array_map('biCurveWeight', $baseline));
}

function biAggregateExpectedPoint(array $context, string $pointDate, bool $observed): array
{
    $real = 0.0;
    $planned = 0.0;
    foreach ($context['baseline_by_project'] as $projectId => $baselineRows) {
        $snapshot = $observed ? biSnapshotAtOrBefore($context['snapshots'][$projectId] ?? [], $pointDate) : [];
        $snapshotRows = $snapshot['rows'] ?? [];
        $projectCutoff = $observed ? (string) ($snapshot['cutoff'] ?? '') : $pointDate;
        foreach ($baselineRows as $key => $baselineRow) {
            $weight = biCurveWeight($baselineRow);
            $real += $weight * min(1.0, max(0.0, (float) ($snapshotRows[$key]['Ejecutado'] ?? 0)));
            $planned += $weight * biPlannedProgressAtCutoff($baselineRow, $projectCutoff);
        }
    }
    return ['real' => $real, 'planned' => $planned];
}

function biProgressExpected(array $rows): array
{
    $context = biCurveContext($rows);
    $labels = $context['labels'] ?? [];
    $real = [];
    $theoretical = [];
    $lastReal = 0.0;
    $lastTheoretical = 0.0;
    $lastPointDate = null;
    $weight = max(1.0, (float) ($context['total_weight'] ?? 0));
    foreach ($context['point_dates'] ?? [] as $pointIndex => $pointDate) {
        $isObservedPoint = $pointIndex <= (int) ($context['current_week_index'] ?? -1);
        $aggregate = biAggregateExpectedPoint($context, $pointDate, $isObservedPoint);
        $pointDateObject = biDate($pointDate);
        if ($pointDateObject !== null) {
            $lastPointDate = $pointDateObject;
        }
        $theoreticalPoint = max($lastTheoretical, round(($aggregate['planned'] / $weight) * 100, 1));
        $lastTheoretical = $theoreticalPoint;
        $theoretical[] = $theoreticalPoint;
        if ($isObservedPoint) {
            $realPoint = max($lastReal, round(($aggregate['real'] / $weight) * 100, 1));
            $lastReal = $realPoint;
            $real[] = $realPoint;
        } else {
            $real[] = null;
        }
    }

    $projection = biProgressProjection($real, $context['current_week_index'] ?? null, count($labels));
    while (count($labels) < count($projection['likely'])) {
        if ($lastPointDate === null) {
            break;
        }
        $lastPointDate = $lastPointDate->modify('+7 days');
        $labels[] = biCurveDateLabel($lastPointDate, count($labels));
        $context['point_dates'][] = $lastPointDate->format('Y-m-d');
        $aggregate = biAggregateExpectedPoint($context, $lastPointDate->format('Y-m-d'), false);
        $theoreticalPoint = max($lastTheoretical, round(($aggregate['planned'] / $weight) * 100, 1));
        $lastTheoretical = $theoreticalPoint;
        $theoretical[] = $theoreticalPoint;
        $real[] = null;
    }
    $completionPointDates = $context['point_dates'];
    $completionLastDate = biDate((string) (end($completionPointDates) ?: ''));
    $maxCompletionIndex = ($projection['completion_week_samples'] ?? [])
        ? max($projection['completion_week_samples'])
        : -1;
    while ($completionLastDate !== null && count($completionPointDates) <= $maxCompletionIndex) {
        $completionLastDate = $completionLastDate->modify('+7 days');
        $completionPointDates[] = $completionLastDate->format('Y-m-d');
    }

    return [
        'labels' => $labels,
        'point_dates' => $context['point_dates'],
        'completion_point_dates' => $completionPointDates,
        'real' => $real,
        'theoretical' => $theoretical,
        'pessimistic' => $projection['pessimistic'],
        'likely' => $projection['likely'],
        'optimistic' => $projection['optimistic'],
        'completion_week_samples' => $projection['completion_week_samples'] ?? [],
    ];
}

function biLastNumericPoint(array $values): float
{
    for ($i = count($values) - 1; $i >= 0; $i--) {
        if (is_numeric($values[$i] ?? null)) {
            return (float) $values[$i];
        }
    }

    return 0.0;
}

function biLastNumericIndex(array $values): ?int
{
    for ($i = count($values) - 1; $i >= 0; $i--) {
        if (is_numeric($values[$i] ?? null)) {
            return $i;
        }
    }

    return null;
}

function biScoreValue(array $scorecard, string $kpi): float
{
    foreach ($scorecard as $row) {
        if (($row['kpi'] ?? '') === $kpi) {
            return (float) ($row['value'] ?? 0);
        }
    }

    return 0.0;
}

function biCurveDateLabel(?DateTimeImmutable $date, int $week): string
{
    if ($week === 0) {
        return 'Inicio';
    }

    if ($date !== null) {
        return $date->format('d/m/y');
    }

    return '';
}

function biEarnedExpected(array $rows): array
{
    $context = biCurveContext($rows);
    $labels = $context['labels'] ?? [];
    $earned = [];
    $planned = [];
    $lastEarned = 0.0;
    $lastPlanned = 0.0;
    foreach ($context['point_dates'] ?? [] as $pointIndex => $pointDate) {
        $isObservedPoint = $pointIndex <= (int) ($context['current_week_index'] ?? -1);
        $aggregate = biAggregateExpectedPoint($context, $pointDate, $isObservedPoint);
        $plannedPoint = max($lastPlanned, round($aggregate['planned'], 2));
        $lastPlanned = $plannedPoint;
        $planned[] = $plannedPoint;
        if ($isObservedPoint) {
            $earnedPoint = max($lastEarned, round($aggregate['real'], 2));
            $lastEarned = $earnedPoint;
            $earned[] = $earnedPoint;
        } else {
            $earned[] = null;
        }
    }

    return [
        'labels' => $labels,
        'earned' => $earned,
        'planned' => $planned,
    ];
}

function biCurveContext(array $rows): array
{
    $snapshots = biSnapshotIndex($rows);
    [$baselineByProject, $baseline] = biLatestProjectBaselines($snapshots);
    $contractualByProject = [];
    foreach ($snapshots as $projectId => $projectSnapshots) {
        $contractualByProject[$projectId] = $projectSnapshots ? reset($projectSnapshots) : [];
    }
    [$start, $finish] = biBaselineDates($baseline);
    $observedDates = biObservedCutoffDates($snapshots);
    $currentProjectCutoffs = [];
    foreach ($snapshots as $projectId => $projectSnapshots) {
        $projectCutoffs = array_keys($projectSnapshots);
        $latestCutoff = $projectCutoffs ? (string) end($projectCutoffs) : '';
        if ($latestCutoff !== '') {
            $currentProjectCutoffs[$projectId] = $latestCutoff;
        }
    }

    $pointDates = [];
    $labels = [];
    if ($start !== null) {
        $pointDates[] = $start->modify('-1 day')->format('Y-m-d');
        $labels[] = 'Inicio';
    }
    foreach ($observedDates as $pointDate) {
        $pointDates[] = $pointDate;
        $labels[] = biCurveDateLabel(biDate($pointDate), count($labels));
    }
    $currentWeekIndex = $observedDates ? count($pointDates) - 1 : null;
    $currentCutoff = $currentProjectCutoffs ? max($currentProjectCutoffs) : '';
    $lastPointDate = biDate($currentCutoff) ?? ($pointDates ? biDate((string) end($pointDates)) : null);
    while ($lastPointDate !== null && $finish !== null && $lastPointDate < $finish) {
        $nextPointDate = $lastPointDate->modify('+7 days');
        if ($nextPointDate > $finish) {
            $nextPointDate = $finish;
        }
        $pointDates[] = $nextPointDate->format('Y-m-d');
        $labels[] = biCurveDateLabel($nextPointDate, count($labels));
        $lastPointDate = $nextPointDate;
    }

    return [
        'labels' => $labels,
        'point_dates' => $pointDates,
        'snapshots' => $snapshots,
        'baseline_by_project' => $baselineByProject,
        'baseline' => $baseline,
        'contractual_by_project' => $contractualByProject,
        'total_weight' => biCurveTotalWeight($baseline),
        'observed_dates' => $observedDates,
        'current_project_cutoffs' => $currentProjectCutoffs,
        'current_cutoff' => $currentCutoff,
        'start' => $start,
        'finish' => $finish,
        'current_week_index' => $currentWeekIndex,
    ];
}

function biBaselineDates(array $baseline): array
{
    $start = null;
    $finish = null;
    foreach ($baseline as $row) {
        $startDate = biDate((string) ($row['Fecha_Inicio'] ?? ''));
        $finishDate = biDate((string) ($row['Fecha_Fin'] ?? ''));
        if ($startDate !== null && ($start === null || $startDate < $start)) {
            $start = $startDate;
        }
        if ($finishDate !== null && ($finish === null || $finishDate > $finish)) {
            $finish = $finishDate;
        }
    }

    return [$start, $finish];
}

function biDate(string $value): ?DateTimeImmutable
{
    if ($value === '') {
        return null;
    }
    return new DateTimeImmutable($value);
}

function biProgressProjection(array $real, ?int $currentIndex, int $totalPoints): array
{
    $empty = array_fill(0, $totalPoints, null);
    if ($currentIndex === null || $currentIndex < 0 || $totalPoints === 0) {
        return ['pessimistic' => $empty, 'likely' => $empty, 'optimistic' => $empty];
    }

    $observed = [];
    for ($i = 0; $i <= $currentIndex && $i < count($real); $i++) {
        if (is_numeric($real[$i])) {
            $observed[] = (float) $real[$i];
        }
    }
    $current = $observed ? (float) end($observed) : 0.0;
    if (biPositiveProductionIncrementCount($observed) < 3) {
        return biUnavailableProjection($current, $currentIndex, $totalPoints);
    }
    $stats = biProjectionStats($observed, $currentIndex);
    $projection = biMonteCarloSCurveProjection($current, $currentIndex, $totalPoints, $stats, $observed);

    return [
        'pessimistic' => $projection['pessimistic'],
        'likely' => $projection['likely'],
        'optimistic' => $projection['optimistic'],
        'completion_week_samples' => $projection['completion_week_samples'],
    ];
}

function biPositiveProductionIncrementCount(array $observed): int
{
    $count = 0;
    for ($i = 1; $i < count($observed); $i++) {
        if (((float) $observed[$i] - (float) $observed[$i - 1]) > 0.05) {
            $count++;
        }
    }

    return $count;
}

function biUnavailableProjection(float $current, int $currentIndex, int $totalPoints): array
{
    $series = array_fill(0, $totalPoints, null);
    if ($currentIndex >= 0 && $currentIndex < $totalPoints) {
        $series[$currentIndex] = round(biClampPercent($current), 1);
    }

    return ['pessimistic' => $series, 'likely' => $series, 'optimistic' => $series];
}

function biMonteCarloSCurveProjection(float $current, int $currentIndex, int $totalPoints, array $stats, array $observed): array
{
    $simulationCount = 240;
    $seed = biProjectionSeed($observed, $currentIndex);
    $trajectories = [];

    for ($i = 0; $i < $simulationCount; $i++) {
        $rate = biSampleWeeklyProductionRate($seed, $stats);
        $initialRate = max($stats['initial_rate'], min($rate, $stats['last_week_rate']));
        $trajectories[] = biInterpolatedSCurveProjectionSeries($current, $currentIndex, $totalPoints, $rate, $initialRate);
    }

    $projectionPoints = $totalPoints;
    foreach ($trajectories as $trajectory) {
        $projectionPoints = max($projectionPoints, count($trajectory));
    }

    $pessimistic = $likely = $optimistic = array_fill(0, $projectionPoints, null);
    for ($index = 0; $index < $projectionPoints; $index++) {
        if ($index < $currentIndex) {
            continue;
        }

        $values = [];
        foreach ($trajectories as $trajectory) {
            $values[] = (float) ($trajectory[$index] ?? 100.0);
        }

        $pessimistic[$index] = round(biPercentile($values, 0.1), 1);
        $likely[$index] = round(biPercentile($values, 0.5), 1);
        $optimistic[$index] = round(biPercentile($values, 0.9), 1);
    }
    $projectionPoints = max(
        $totalPoints,
        (biProjectionCompletionWeek($pessimistic) ?? $projectionPoints - 1) + 1,
        (biProjectionCompletionWeek($likely) ?? $projectionPoints - 1) + 1,
        (biProjectionCompletionWeek($optimistic) ?? $projectionPoints - 1) + 1,
    );

    return [
        'pessimistic' => array_slice($pessimistic, 0, $projectionPoints),
        'likely' => array_slice($likely, 0, $projectionPoints),
        'optimistic' => array_slice($optimistic, 0, $projectionPoints),
        'completion_week_samples' => array_map('biProjectionCompletionWeek', $trajectories),
    ];
}

function biProjectionCompletionWeek(array $series): ?int
{
    foreach ($series as $index => $value) {
        if (is_numeric($value) && (float) $value >= 100.0) {
            return (int) $index;
        }
    }

    return null;
}

function biSampleWeeklyProductionRate(int &$seed, array $stats): float
{
    $min = max(0.0, (float) ($stats['pessimistic_rate'] ?? 0.0));
    $mode = max($min, (float) ($stats['likely_rate'] ?? $min));
    $max = max($mode, (float) ($stats['optimistic_rate'] ?? $mode));
    if (($max - $min) <= 0.000001) {
        return $mode;
    }
    $u = biSeededUniform($seed);
    $pivot = ($mode - $min) / ($max - $min);

    if ($u < $pivot) {
        return $min + sqrt($u * ($max - $min) * ($mode - $min));
    }

    return $max - sqrt((1.0 - $u) * ($max - $min) * ($max - $mode));
}

function biInterpolatedSCurveProjectionSeries(float $current, int $currentIndex, int $totalPoints, float $targetRate, float $initialRate): array
{
    $series = array_fill(0, max($totalPoints, $currentIndex + 1), null);
    $current = biClampPercent($current);
    $series[$currentIndex] = round($current, 1);

    if ($current >= 100.0) {
        for ($i = $currentIndex + 1; $i < count($series); $i++) {
            $series[$i] = 100.0;
        }
        return $series;
    }

    $remaining = max(0.0, 100.0 - $current);
    if ($remaining <= 0.0) {
        return $series;
    }

    $initialRate = max(0.0, $initialRate);
    $targetRate = max(0.000001, $targetRate);
    $completionHorizon = max(2, (int) ceil($remaining / $targetRate));
    $slopeRatio = ($initialRate * $completionHorizon) / $remaining;
    $slopeRatio = min(1.35, max(0.25, $slopeRatio));

    for ($horizon = 1; $horizon <= $completionHorizon; $horizon++) {
        $t = $horizon / $completionHorizon;
        $shape = biHermiteSInterpolation($t, $slopeRatio);
        $index = $currentIndex + $horizon;
        $series[$index] = round(biClampPercent($current + ($remaining * $shape)), 1);
    }
    $series[$currentIndex + $completionHorizon] = 100.0;

    return $series;
}

function biHermiteSInterpolation(float $t, float $initialSlopeRatio): float
{
    $t = min(1.0, max(0.0, $t));
    $t2 = $t * $t;
    $t3 = $t2 * $t;
    $shape = (3.0 * $t2) - (2.0 * $t3) + ($initialSlopeRatio * ($t3 - (2.0 * $t2) + $t));

    return min(1.0, max(0.0, $shape));
}

function biProjectionSeed(array $observed, int $currentIndex): int
{
    $seed = crc32(json_encode([$observed, $currentIndex], JSON_THROW_ON_ERROR));
    return max(1, (int) ($seed % 2147483646));
}

function biSeededUniform(int &$seed): float
{
    $seed = (int) (($seed * 48271) % 2147483647);
    return max(0.000001, min(0.999999, $seed / 2147483647));
}

function biSeededNormal(int &$seed): float
{
    $u1 = biSeededUniform($seed);
    $u2 = biSeededUniform($seed);

    return sqrt(-2.0 * log($u1)) * cos(2.0 * M_PI * $u2);
}

function biPercentile(array $values, float $percentile): float
{
    $values = array_values(array_filter($values, 'is_numeric'));
    if (!$values) {
        return 0.0;
    }

    sort($values, SORT_NUMERIC);
    $position = (count($values) - 1) * min(1.0, max(0.0, $percentile));
    $lower = (int) floor($position);
    $upper = (int) ceil($position);
    if ($lower === $upper) {
        return (float) $values[$lower];
    }

    $weight = $position - $lower;
    return ((float) $values[$lower] * (1.0 - $weight)) + ((float) $values[$upper] * $weight);
}

function biProjectionStats(array $observed, int $currentIndex): array
{
    $increments = [];
    for ($i = 1; $i < count($observed); $i++) {
        $increments[] = max(0.0, (float) $observed[$i] - (float) $observed[$i - 1]);
    }
    $current = $observed ? (float) end($observed) : 0.0;
    $sustainedRate = $current > 0.0 ? $current / max(1, $currentIndex) : 0.0;
    $recentRate = biRecentWeightedRate($increments);
    $recentPositive = count(array_filter(array_slice($increments, -3), fn($value) => $value > 0.05));
    $sampleSize = count($increments);
    $lastWeekRate = $increments ? (float) end($increments) : 0.0;
    $activeWeekCount = count(array_filter($increments, fn($value) => $value > 0.05));
    $activeWeekRatio = $sampleSize > 0 ? $activeWeekCount / $sampleSize : 0.0;
    $historicalReliability = min(1.0, max(0.55, 0.65 + ($activeWeekRatio * 0.35)));
    $mean = $increments ? array_sum($increments) / count($increments) : 0.0;
    $stddev = biSampleStddev($increments);
    if ($stddev <= 0.0001) {
        $stddev = max($mean, $sustainedRate) * 0.15;
    }
    $productionRate = max(0.0, max($sustainedRate, $recentRate));
    $momentumRate = max($recentRate, $lastWeekRate);
    $initialRate = max(0.0, max($sustainedRate, min($lastWeekRate, $recentRate > 0.0 ? $recentRate : $lastWeekRate)));
    $standardError = $sampleSize > 1 ? $stddev / sqrt($sampleSize) : $stddev;
    $rawHalfWidth = 1.28155 * $standardError;
    if ($rawHalfWidth <= 0.0001) {
        $rawHalfWidth = $productionRate * 0.15;
    }
    $intervalHalfWidth = min($rawHalfWidth, $productionRate * 0.25);
    $likelyRate = $productionRate;
    $pessimisticRate = max(0.0, min($sustainedRate, $mean) * $historicalReliability);
    $momentumLift = max(0.0, $lastWeekRate - $sustainedRate);
    $optimisticRate = max(
        $likelyRate + $intervalHalfWidth,
        $momentumRate + $intervalHalfWidth + ($momentumLift * 0.65),
        $likelyRate * 1.25,
    );
    $accelerationCap = min(1.0, $likelyRate * 0.1);
    return [
        'mean' => $mean,
        'stddev' => $stddev,
        'z' => 1.28155,
        'sample_size' => $sampleSize,
        'sustained_rate' => $sustainedRate,
        'recent_rate' => $recentRate,
        'last_week_rate' => $lastWeekRate,
        'momentum_rate' => $momentumRate,
        'active_week_ratio' => $activeWeekRatio,
        'historical_reliability' => $historicalReliability,
        'production_rate' => $productionRate,
        'initial_rate' => $initialRate,
        'interval_half_width' => $intervalHalfWidth,
        'acceleration_cap' => $accelerationCap,
        'pessimistic_rate' => $pessimisticRate,
        'likely_rate' => $likelyRate,
        'optimistic_rate' => $optimisticRate,
    ];
}

function biRecentWeightedRate(array $increments): float
{
    $recent = array_slice($increments, -3);
    if (!$recent) {
        return 0.0;
    }

    $weights = array_slice([0.2, 0.3, 0.5], -count($recent));
    $weighted = 0.0;
    $weightTotal = 0.0;
    foreach (array_values($recent) as $index => $value) {
        $weight = $weights[$index] ?? 1.0;
        $weighted += (float) $value * $weight;
        $weightTotal += $weight;
    }

    return $weightTotal > 0.0 ? $weighted / $weightTotal : 0.0;
}

function biSampleStddev(array $values): float
{
    if (count($values) < 2) {
        return 0.0;
    }
    $mean = array_sum($values) / count($values);
    $variance = array_sum(array_map(fn($value) => (($value - $mean) ** 2), $values)) / (count($values) - 1);
    return sqrt(max(0.0, $variance));
}

function biClampPercent(float $value): float
{
    return min(100.0, max(0.0, $value));
}

function biCurrentProgressExpected(array $rows): array
{
    $duration = 0.0;
    $real = 0.0;
    $planned = 0.0;
    foreach ($rows as $row) {
        $rowDuration = max(0.0, (float) ($row['duration_days'] ?? 0));
        $duration += $rowDuration;
        $real += min(1.0, max(0.0, (float) $row['Ejecutado'])) * $rowDuration;
        $planned += min(1.0, max(0.0, (float) $row['theoretical_progress_by_duration'])) * $rowDuration;
    }
    $realPct = $duration > 0 ? round(($real / $duration) * 100, 1) : 0.0;
    $plannedPct = $duration > 0 ? round(($planned / $duration) * 100, 1) : 0.0;
    $compliance = $plannedPct > 0 ? round(min(150.0, max(0.0, ($realPct / $plannedPct) * 100)), 1) : ($realPct > 0 ? 100.0 : 0.0);
    return ['real' => $realPct, 'planned' => $plannedPct, 'compliance' => $compliance];
}

function biDateDiffDays(string $from, string $to): int
{
    return (int) (new DateTimeImmutable($from))->diff(new DateTimeImmutable($to))->format('%r%a');
}

function biActivityName(array $row): string
{
    $activity = html_entity_decode(strip_tags((string) ($row['Actividad'] ?? '')), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $activity = preg_replace('/\s+/u', ' ', trim($activity)) ?: 'Actividad sin nombre';
    return $activity;
}

function biComplianceActivitiesAtOwnCutoffExpected(array $rows): array
{
    $totalWeight = 0.0;
    foreach ($rows as $row) {
        $totalWeight += biCurveWeight($row);
    }
    $totalWeight = max(1.0, $totalWeight);

    $activities = [];
    foreach ($rows as $row) {
        $planned = round(min(1.0, max(0.0, (float) ($row['theoretical_progress_by_duration'] ?? 0))) * 100, 1);
        $real = round(min(1.0, max(0.0, (float) ($row['Ejecutado'] ?? 0))) * 100, 1);
        $gap = round($real - $planned, 1);
        if ($gap >= -0.05) {
            continue;
        }

        $critical = (int) ($row['Ruta_Critica'] ?? 0) === 1;
        $finish = (string) ($row['Fecha_Fin'] ?? '');
        $cutoff = (string) ($row['Fecha_Fin_Sem'] ?? '');
        $late = $cutoff !== '' && $finish !== '' && $finish < $cutoff && $real < 100.0;
        $delayDays = $late ? max(0, biDateDiffDays($finish, $cutoff)) : 0;
        $cause = $critical && $late
            ? 'Ruta crítica atrasada'
            : ($late ? 'Actividad vencida al corte' : ($critical ? 'Ruta crítica bajo plan' : 'Avance menor al plan'));
        $implication = $critical && $late
            ? 'Puede desplazar la fecha final; requiere plan de recuperación.'
            : ($critical
                ? 'Reduce la holgura de la ruta crítica.'
                : ($late
                    ? 'Acumula trabajo vencido en las semanas siguientes.'
                    : 'Exige recuperar producción para cerrar la brecha del corte.'));
        $activities[] = [
            'project_id' => (int) ($row['project_id'] ?? 0),
            'unique_id' => (int) ($row['unique_id'] ?? 0),
            'activity' => biActivityName($row),
            'planned_finish' => $finish,
            'cutoff' => $cutoff,
            'planned_pct' => $planned,
            'real_pct' => $real,
            'gap_pp' => $gap,
            'contribution_pp' => round(($gap * biCurveWeight($row)) / $totalWeight, 2),
            'delay_days' => $delayDays,
            'critical' => $critical,
            'late' => $late,
            'responsible' => trim((string) ($row['responsable_aia'] ?? '')),
            'subcontractor' => trim((string) ($row['sub_contratista'] ?? '')),
            'cause' => $cause,
            'implication' => $implication,
        ];
    }

    usort($activities, static function (array $a, array $b): int {
        if ($a['critical'] !== $b['critical']) {
            return $a['critical'] ? -1 : 1;
        }
        if ($a['contribution_pp'] !== $b['contribution_pp']) {
            return $a['contribution_pp'] <=> $b['contribution_pp'];
        }
        return $b['delay_days'] <=> $a['delay_days'];
    });

    return $activities;
}

function biActivityKey(array $activity): string
{
    return (int) ($activity['project_id'] ?? 0) . ':' . (int) ($activity['unique_id'] ?? 0);
}

function biAssertComplianceActivitiesAtOwnCutoff(array &$failures, string $label, array $actual, array $expected): void
{
    $actualKeys = array_map('biActivityKey', $actual);
    $expectedKeys = array_map('biActivityKey', $expected);
    if ($actualKeys !== $expectedKeys) {
        biFail($failures, "{$label}: detail activity order/scope mismatch");
        return;
    }

    foreach ($actual as $index => $activity) {
        $expectedActivity = $expected[$index] ?? [];
        biAssertSeries($failures, "{$label}: detail activity metrics {$actualKeys[$index]}", [
            $activity['planned_pct'] ?? null,
            $activity['real_pct'] ?? null,
            $activity['gap_pp'] ?? null,
            $activity['contribution_pp'] ?? null,
            $activity['delay_days'] ?? null,
        ], [
            $expectedActivity['planned_pct'] ?? null,
            $expectedActivity['real_pct'] ?? null,
            $expectedActivity['gap_pp'] ?? null,
            $expectedActivity['contribution_pp'] ?? null,
            $expectedActivity['delay_days'] ?? null,
        ]);
        if (($activity['cutoff'] ?? '') !== ($expectedActivity['cutoff'] ?? '')) {
            biFail($failures, "{$label}: detail cutoff mismatch for {$actualKeys[$index]}");
        }
        if (($activity['critical'] ?? null) !== ($expectedActivity['critical'] ?? null)) {
            biFail($failures, "{$label}: detail critical flag mismatch for {$actualKeys[$index]}");
        }
        if (($activity['late'] ?? null) !== ($expectedActivity['late'] ?? null)) {
            biFail($failures, "{$label}: detail late flag mismatch for {$actualKeys[$index]}");
        }
    }
}

function biDelayExpected(array $rows): float
{
    $context = biCurveContext($rows);
    $maxDelay = 0;
    $nearestAhead = null;
    foreach ($context['baseline'] ?? [] as $row) {
        $projectId = (int) ($row['project_id'] ?? 0);
        $cutoff = (string) (($context['current_project_cutoffs'][$projectId] ?? '') ?: biOwnCutoff($row));
        if ((float) ($row['Ejecutado'] ?? 0) >= 1 || empty($row['Fecha_Fin']) || $cutoff === '') {
            continue;
        }
        $days = biDateDiffDays((string) $row['Fecha_Fin'], $cutoff);
        if ($days > 0) {
            $maxDelay = max($maxDelay, $days);
        } else {
            $nearestAhead = $nearestAhead === null ? $days : max($nearestAhead, $days);
        }
    }
    return (float) ($maxDelay > 0 ? $maxDelay : ($nearestAhead ?? 0));
}

function biFinishDateAtProjectionIndex(array $progress, int $datasetIndex): ?string
{
    $completion = biProjectionCompletionWeek($progress['projections'][$datasetIndex] ?? []);
    $date = $completion === null ? null : biDate((string) ($progress['point_dates'][$completion] ?? ''));
    return $date?->format('Y-m-d');
}

function biCompletionDateSamples(array $progress): array
{
    $samples = [];
    foreach ($progress['completion_week_samples'] ?? [] as $week) {
        $date = biDate((string) ($progress['completion_point_dates'][$week] ?? ''));
        if ($date !== null) {
            $samples[] = $date->format('Y-m-d');
        }
    }
    return $samples;
}

function biDatePercentile(array $dates, float $percentile): ?string
{
    sort($dates, SORT_STRING);
    if (!$dates) return null;
    $index = (int) floor((count($dates) - 1) * $percentile);
    return $dates[$index] ?? null;
}

function biForecastTrendExpected(
    \Database $db,
    array $projectIds,
    string $semana,
    array $filters,
): array {
    $selectionRows = array_values(array_filter(
        biFetchPg($db, $projectIds, $semana, $filters, false),
        static function (array $row): bool {
            $start = biDate((string) ($row['Fecha_Inicio'] ?? ''));
            $finish = biDate((string) ($row['Fecha_Fin'] ?? ''));
            return $start !== null && $finish !== null && $finish >= $start;
        },
    ));
    $selectionContext = biCurveContext($selectionRows);
    $cohortByProject = [];
    foreach ($selectionContext['baseline'] ?? [] as $row) {
        $projectId = (int) ($row['project_id'] ?? 0);
        $uniqueId = (int) ($row['unique_id'] ?? 0);
        if ($projectId > 0 && $uniqueId > 0) {
            $cohortByProject[$projectId][$uniqueId] = true;
        }
    }
    $cutoffs = $selectionContext['current_project_cutoffs'] ?? [];

    return array_values(array_filter(
        biFetchPg($db, $projectIds, '', [], true),
        static function (array $row) use ($cohortByProject, $cutoffs): bool {
            $projectId = (int) ($row['project_id'] ?? 0);
            $uniqueId = (int) ($row['unique_id'] ?? 0);
            $cutoff = biOwnCutoff($row);
            $selectedCutoff = (string) ($cutoffs[$projectId] ?? '');
            return isset($cohortByProject[$projectId][$uniqueId])
                && $selectedCutoff !== ''
                && $cutoff !== ''
                && $cutoff <= $selectedCutoff;
        },
    ));
}

function biContractualBaselineForFilteredCohort(array $unfilteredTrendRows, array $filteredTrendRows, array $projectIds): array
{
    $cohortByProject = [];
    foreach (biCurveContext($filteredTrendRows)['baseline'] ?? [] as $row) {
        $projectId = (int) ($row['project_id'] ?? 0);
        $uniqueId = (int) ($row['unique_id'] ?? 0);
        if ($projectId > 0 && $uniqueId > 0) {
            $cohortByProject[$projectId][$uniqueId] = true;
        }
    }

    $contractual = biCurveContext($unfilteredTrendRows)['contractual_by_project'] ?? [];
    $baseline = [];
    foreach ($projectIds as $projectId) {
        $projectId = (int) $projectId;
        $cohort = $cohortByProject[$projectId] ?? [];
        $baseline[$projectId] = array_values(array_filter(
            $contractual[$projectId] ?? [],
            static fn(array $row): bool => isset($cohort[(int) ($row['unique_id'] ?? 0)]),
        ));
    }

    return $baseline;
}

function biProgramaForecastExpected(array $trendRows, array $unfilteredTrendRows, array $projectIds): array
{
    $rowsByProject = [];
    foreach ($trendRows as $row) {
        $rowsByProject[(int) ($row['project_id'] ?? 0)][] = $row;
    }
    $contractualBaseline = biContractualBaselineForFilteredCohort($unfilteredTrendRows, $trendRows, $projectIds);
    $projects = [];
    foreach (array_values(array_unique(array_map('intval', $projectIds))) as $projectId) {
        $projectRows = $rowsByProject[$projectId] ?? [];
        $progress = biProgressExpected($projectRows);
        $observed = array_values(array_filter($progress['real'], 'is_numeric'));
        $available = biPositiveProductionIncrementCount($observed) >= 3;
        [, $contractual] = biBaselineDates($contractualBaseline[$projectId] ?? []);
        $completionDates = biCompletionDateSamples($progress);
        $available = $available && $contractual !== null && count($completionDates) === 240;
        $projects[$projectId] = [
            'contractual_finish' => $contractual?->format('Y-m-d'),
            'available' => $available,
            'completion_dates' => $completionDates,
        ];
    }
    $available = $projects !== [] && !array_filter($projects, fn(array $project): bool => !$project['available']);
    $contractual = biPortfolioMaxDate(array_column($projects, 'contractual_finish'));
    $portfolioSamples = [];
    if ($available) {
        for ($simulation = 0; $simulation < 240; $simulation++) {
            $portfolioSamples[] = max(array_map(
                static fn(array $project): string => $project['completion_dates'][$simulation],
                $projects,
            ));
        }
    }
    $finishes = [10 => biDatePercentile($portfolioSamples, 0.1), 50 => biDatePercentile($portfolioSamples, 0.5), 90 => biDatePercentile($portfolioSamples, 0.9)];
    return [
        'available' => $available,
        'contractual_finish' => $contractual,
        'finishes' => $finishes,
        'variation_p50_days' => $available && $contractual !== null && $finishes[50] !== null
            ? (float) biDateDiffDays($contractual, $finishes[50])
            : null,
        'projects' => $projects,
    ];
}

function biPortfolioMaxDate(array $dates): ?string
{
    $dates = array_values(array_filter($dates, static fn($date): bool => biDate((string) $date) !== null));
    return $dates ? max($dates) : null;
}

function biAssertProgramaForecastContract(
    array &$failures,
    string $label,
    array $chart,
    array $trendRows,
    array $unfilteredTrendRows,
    array $projectIds,
): void
{
    $expected = biProgramaForecastExpected($trendRows, $unfilteredTrendRows, $projectIds);
    $metrics = $chart['metrics'] ?? [];
    $expectedValue = $expected['variation_p50_days'];
    biAssertSeries($failures, "{$label}: forecast delay value", $chart['datasets'][0]['data'] ?? [], [$expectedValue]);
    if (($chart['availability'] ?? null) !== $expected['available']) {
        biFail($failures, "{$label}: forecast availability mismatch");
    }
    if (($metrics['contractual_finish'] ?? null) !== $expected['contractual_finish']) {
        biFail($failures, "{$label}: contractual finish must remain on the first snapshot baseline");
    }
    if (($metrics['contractual_finish_basis'] ?? '') !== 'first_available_snapshot_per_project') {
        biFail($failures, "{$label}: contractual baseline basis is not documented");
    }
    if (array_key_exists('observed_days', $metrics)) {
        biFail($failures, "{$label}: forecast contract must not mix observed activity delay");
    }
    if (($metrics['metric_key'] ?? '') !== 'pg_finish_variance_days_p50') {
        biFail($failures, "{$label}: forecast metric key mismatch");
    }
    if (($chart['interaction']['detail_endpoint'] ?? '') !== '/api/bi/report/programa-general/delay-detail') {
        biFail($failures, "{$label}: forecast detail endpoint mismatch");
    }
    foreach ([10, 50, 90] as $percentile) {
        $key = "p{$percentile}_finish";
        if (($metrics['forecast'][$key] ?? null) !== $expected['finishes'][$percentile]) {
            biFail($failures, "{$label}: {$key} date mismatch");
        }
    }
    if ($expected['available'] && count(array_filter($expected['finishes'])) !== 3) {
        biFail($failures, "{$label}: forecast finish percentiles are incomplete");
    }
    if ($expected['available'] && !($expected['finishes'][10] <= $expected['finishes'][50] && $expected['finishes'][50] <= $expected['finishes'][90])) {
        biFail($failures, "{$label}: completion dates must satisfy P10 <= P50 <= P90");
    }
    if (($metrics['forecast_distribution_basis'] ?? '') !== 'completion_date_samples_by_simulation') {
        biFail($failures, "{$label}: finish percentiles must come from completion-date samples");
    }
    if (count($expected['projects']) > 1 && ($metrics['portfolio_aggregation'] ?? '') !== 'max_completion_date_per_simulation_then_percentiles') {
        biFail($failures, "{$label}: portfolio must aggregate each simulation before percentiles");
    }
    if (($metrics['variation_days']['p50'] ?? null) !== $expectedValue) {
        biFail($failures, "{$label}: P50 variation must be forecast minus contractual finish");
    }
    if (($metrics['method'] ?? '') !== 'monte_carlo_s_curve_current_production_prediction_interval') {
        biFail($failures, "{$label}: forecast method mismatch");
    }
    if (($metrics['simulation_count'] ?? null) !== ($expected['available'] ? 240 : 0)) {
        biFail($failures, "{$label}: simulation count mismatch");
    }
    if (($metrics['probable_range_80']['confidence_level'] ?? null) !== 0.8) {
        biFail($failures, "{$label}: probable range must state 80% confidence");
    }
    $context = biCurveContext($trendRows);
    if (($metrics['cutoff'] ?? null) !== ($context['current_cutoff'] ?? null)) {
        biFail($failures, "{$label}: forecast cutoff must match Curva S point dates");
    }
    if (($metrics['scope']['project_ids'] ?? null) !== array_values($projectIds)) {
        biFail($failures, "{$label}: forecast scope project ids mismatch");
    }
    if (count($metrics['project_breakdown'] ?? []) !== count($expected['projects'])) {
        biFail($failures, "{$label}: forecast must preserve every selected project in its breakdown");
    }
    if (!$expected['available'] && (($chart['status'] ?? '') !== 'unavailable' || ($metrics['reason'] ?? '') === '')) {
        biFail($failures, "{$label}: unavailable forecast needs explicit status and reason");
    }
}

function biCategoryExpected(array $rows, string $categoryField, string $flagField): array
{
    $counts = [];
    foreach ($rows as $row) {
        if (($row[$flagField] ?? 0) != 1) {
            continue;
        }
        $category = trim((string) ($row[$categoryField] ?? 'Sin categoría')) ?: 'Sin categoría';
        $key = strtr(strtolower($category), ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n']);
        $category = [
            'programacion' => 'Programación',
            'diseno' => 'Diseños',
            'disenos' => 'Diseños',
            'm de o' => 'Mano de Obra',
            'mano obra' => 'Mano de Obra',
            'mano de obra' => 'Mano de Obra',
            'material' => 'Materiales',
            'materiales' => 'Materiales',
            'equipo' => 'Equipos',
            'equipos' => 'Equipos',
            'administrativa' => 'Administrativas',
            'administrativas' => 'Administrativas',
            'causa exogena' => 'Causas Exógenas',
            'causas exogenas' => 'Causas Exógenas',
        ][$key] ?? $category;
        $counts[$category] = ($counts[$category] ?? 0) + 1;
    }
    arsort($counts);
    $counts = $counts ?: ['Sin registros' => 0];
    return ['labels' => array_keys($counts), 'values' => array_map('floatval', array_values($counts))];
}

function biAssertCausalDrilldownContract(
    array &$failures,
    ControlTowerService $bi,
    array $projectIds,
    string $semana,
    array $filters,
    string $kind,
    string $label
): void {
    $method = 'getPrograma' . strtoupper($kind) . 'Detail';
    $chartKey = 'programa-' . $kind;
    $endpoint = '/api/bi/report/programa-general/' . $kind . '-detail';
    $brief = $bi->getBrief('programa-general', $projectIds, $semana, 'R', $filters);
    $interaction = $brief['charts'][$chartKey]['interaction'] ?? [];
    if (($interaction['detail_endpoint'] ?? '') !== $endpoint
        || ($interaction['desktop_action'] ?? '') !== 'dblclick'
        || ($interaction['mobile_action'] ?? '') !== 'button') {
        biFail($failures, "{$label}: {$kind} chart drill-down interaction metadata mismatch");
    }
    if (!method_exists($bi, $method)) {
        biFail($failures, "{$label}: {$kind} drill-down service is missing");
        return;
    }

    $detail = $bi->{$method}($projectIds, $semana, $filters);
    if (($detail['respuesta'] ?? '') !== 'BIEN' || ($detail['empty'] ?? null) !== (($detail['summary']['total'] ?? -1) === 0)) {
        biFail($failures, "{$label}: {$kind} detail must report an explicit empty state");
    }
    $chartTotal = array_sum($brief['charts'][$chartKey]['datasets'][0]['data'] ?? []);
    if (abs((float) ($detail['summary']['total'] ?? -1) - $chartTotal) > 0.05) {
        biFail($failures, "{$label}: {$kind} detail total must equal chart total");
    }
    if (array_sum($detail['summary']['categories'] ?? []) !== (int) ($detail['summary']['total'] ?? -1)) {
        biFail($failures, "{$label}: {$kind} category summary must add up to total");
    }
    $firstCategory = array_key_first($detail['summary']['categories'] ?? []);
    if ($firstCategory !== null) {
        $categoryDetail = $bi->{$method}($projectIds, $semana, $filters, $firstCategory);
        if ((int) ($categoryDetail['summary']['total'] ?? -1) !== (int) ($detail['summary']['categories'][$firstCategory] ?? -1)) {
            biFail($failures, "{$label}: {$kind} optional category filter must match its summary bucket");
        }
    }
    foreach ($detail['activities'] ?? [] as $activity) {
        foreach (['project_id', 'semana', 'consecutivo', 'project', 'activity', 'location', 'category_original', 'category_canonical', 'cause', 'observations', 'responsible', 'subcontractor', 'start_date', 'finish_date', 'critical', 'action_available'] as $field) {
            if (!array_key_exists($field, $activity)) {
                biFail($failures, "{$label}: {$kind} activity is missing {$field}");
                break 2;
            }
        }
        if (($activity['read_only'] ?? false) !== true || ($activity['action_available'] ?? true) !== false) {
            biFail($failures, "{$label}: {$kind} detail must remain read-only until the operational module consumes project/week context");
            break;
        }
    }
}

function biRadarExpected(array $rows): array
{
    $axes = [
        'productividad' => ['numerator' => 0.0, 'denominator' => 0],
        'eficiencia' => ['numerator' => 0.0, 'denominator' => 0],
        'desempeno' => ['numerator' => 0.0, 'denominator' => 0],
    ];
    foreach ($rows as $row) {
        if ((int) ($row['Es_TNP'] ?? 0) === 1) {
            continue;
        }
        foreach (['P_Completado' => 'productividad', 'PAC' => 'desempeno'] as $field => $axis) {
            $value = $row[$field] ?? null;
            if (!is_numeric($value)) {
                continue;
            }
            $value = (float) $value;
            $valid = $field === 'P_Completado' ? $value >= 0.0 : ($value === 0.0 || $value === 1.0);
            if ($valid) {
                $axes[$axis]['numerator'] += $field === 'P_Completado' ? min(1.0, $value) : $value;
                $axes[$axis]['denominator']++;
            }
        }
        if (is_numeric($row['Compromiso'] ?? null) && is_numeric($row['Ejecutado_Real'] ?? null)) {
            $commitment = (float) $row['Compromiso'];
            $executed = (float) $row['Ejecutado_Real'];
            if ($commitment > 0.0 && $executed >= 0.0) {
                $axes['eficiencia']['numerator'] += $executed / $commitment;
                $axes['eficiencia']['denominator']++;
            }
        }
    }
    foreach ($axes as $key => &$axis) {
        $axis['sample_size'] = $axis['denominator'];
        $axis['available'] = $axis['denominator'] >= 3;
        $axis['raw_value'] = $axis['available'] ? round(($axis['numerator'] / $axis['denominator']) * 100, 1) : null;
        $axis['display_value'] = $axis['raw_value'] === null ? null : min(100.0, $axis['raw_value']);
    }
    unset($axis);
    return $axes;
}

function biAssertActivitySnapshot(array &$failures, string $label, array $brief, array $currentPg): void
{
    $snapshot = $brief['activity_snapshot'] ?? null;
    if (!is_array($snapshot)) {
        biFail($failures, "{$label}: activity snapshot is missing");
        return;
    }
    $projectCutoffs = [];
    foreach ($currentPg as $row) {
        $projectId = (int) ($row['project_id'] ?? 0);
        $cutoff = (string) (($row['Fecha_Fin_Sem'] ?? '') ?: ($row['Fecha_Inicio_Sem'] ?? ''));
        if ($cutoff !== '' && $cutoff > (string) ($projectCutoffs[$projectId] ?? '')) {
            $projectCutoffs[$projectId] = $cutoff;
        }
    }
    $latest = [];
    foreach ($currentPg as $row) {
        $projectId = (int) ($row['project_id'] ?? 0);
        $cutoff = (string) (($row['Fecha_Fin_Sem'] ?? '') ?: ($row['Fecha_Inicio_Sem'] ?? ''));
        if ($cutoff !== (string) ($projectCutoffs[$projectId] ?? '')) {
            continue;
        }
        $key = $projectId . ':' . (int) ($row['unique_id'] ?? 0);
        $latest[$key] = $row;
    }
    if (($snapshot['total'] ?? null) !== count($latest)) {
        biFail($failures, "{$label}: activity snapshot total does not match filtered current activities");
    }
    $activities = $snapshot['activities'] ?? [];
    if (!is_array($activities) || count($activities) !== min(25, count($latest))) {
        biFail($failures, "{$label}: activity timeline initial limit must be 25 records");
        return;
    }
    foreach ($activities as $activity) {
        foreach (['activity_key', 'project_id', 'project', 'activity', 'planned_start', 'planned_finish', 'cutoff', 'duration_days', 'real_pct', 'planned_pct', 'gap_pp', 'weight_pct', 'real_contribution_pp', 'planned_contribution_pp', 'recoverable_pp', 'critical', 'late', 'responsible', 'subcontractor'] as $field) {
            if (!array_key_exists($field, $activity)) {
                biFail($failures, "{$label}: activity timeline is missing {$field}");
                return;
            }
        }
    }
    $pagination = $snapshot['pagination'] ?? [];
    if (($pagination['total'] ?? null) !== count($latest)
        || ($pagination['returned_count'] ?? null) !== count($activities)
        || ($pagination['next_offset'] ?? null) !== count($activities)) {
        biFail($failures, "{$label}: activity timeline pagination mismatch");
    }
}

function biAssertMomentumProjection(array &$failures, array $chart, string $label): void
{
    $planned = $chart['datasets'][0]['data'] ?? [];
    $optimistic = $chart['datasets'][4]['data'] ?? [];
    $meta = $chart['projection_meta'] ?? [];
    $plannedCompletion = biProjectionCompletionWeek($planned);
    $optimisticCompletion = biProjectionCompletionWeek($optimistic);
    $lastRate = (float) ($meta['last_weekly_rate_pct'] ?? 0.0);
    $sustainedRate = (float) ($meta['sustained_weekly_rate_pct'] ?? 0.0);

    if (
        $lastRate > $sustainedRate + 5.0
        && $plannedCompletion !== null
        && $optimisticCompletion !== null
        && $optimisticCompletion >= $plannedCompletion
    ) {
        biFail($failures, "{$label}: optimistic projection does not beat planned finish despite strong last-week momentum");
    }
}

function biValidateProgramaGeneral(\Database $db, ControlTowerService $bi, array &$failures, array $projectIds, string $semana, array $filters, string $label): void
{
    $brief = $bi->getBrief('programa-general', $projectIds, $semana, 'R', $filters);
    $currentPg = biFetchPg($db, $projectIds, $semana, $filters, false);
    $trendPg = biFetchPg($db, $projectIds, $semana, $filters, true);
    $forecastTrendPg = biForecastTrendExpected($db, $projectIds, $semana, $filters);
    $unfilteredTrendPg = biFetchPg($db, $projectIds, '', [], true);
    $radarRows = biFetchRadar($db, $projectIds, $semana, $filters);
    $psUniverse = biFetchPsUniverse($db, $projectIds, $semana, $filters);

    if (($brief['raw_row_count'] ?? null) !== count($currentPg)) {
        biFail($failures, "{$label}: raw row count mismatch");
    }
    biAssertActivitySnapshot($failures, $label, $brief, $currentPg);

    $progress = biProgressExpected($trendPg);
    $chart = $brief['charts']['programa-curva-ejecucion'] ?? [];
    if (($chart['labels'] ?? []) !== $progress['labels']) {
        biFail($failures, "{$label}: curva ejecucion labels mismatch");
    }
    biAssertDateLabelsChronological($failures, "{$label}: curva ejecucion", $chart['labels'] ?? []);
    $projectionLabels = array_map(
        fn($dataset) => (string) ($dataset['label'] ?? ''),
        array_slice($chart['datasets'] ?? [], 2, 3)
    );
    foreach ($projectionLabels as $projectionLabel) {
        if (str_contains($projectionLabel, 'IC')) {
            biFail($failures, "{$label}: projection label uses ambiguous IC wording");
        }
    }
    if (
        !str_contains($projectionLabels[0] ?? '', 'Rango probable 80%')
        || !str_contains($projectionLabels[2] ?? '', 'Rango probable 80%')
    ) {
        biFail($failures, "{$label}: projection labels do not explain the 80% probable range");
    }
    biAssertSeries($failures, "{$label}: curva ejecucion teorica", $chart['datasets'][0]['data'] ?? [], $progress['theoretical']);
    biAssertSeries($failures, "{$label}: curva ejecucion real", $chart['datasets'][1]['data'] ?? [], $progress['real']);
    biAssertSeries($failures, "{$label}: curva ejecucion pesimista", $chart['datasets'][2]['data'] ?? [], $progress['pessimistic']);
    biAssertSeries($failures, "{$label}: curva ejecucion probable", $chart['datasets'][3]['data'] ?? [], $progress['likely']);
    biAssertSeries($failures, "{$label}: curva ejecucion optimista", $chart['datasets'][4]['data'] ?? [], $progress['optimistic']);

    if (isset($brief['charts']['programa-curva-valor-ganado'])) {
        biFail($failures, "{$label}: valor ganado should remain hidden until financial budget data exists");
    }

    $curveRealProgress = biLastNumericPoint($progress['real']);
    biAssertSeries($failures, "{$label}: avance obra", $brief['charts']['programa-gauge']['datasets'][0]['data'] ?? [], [$curveRealProgress, max(0, 100 - $curveRealProgress)]);
    if (abs((float) biScoreValue($brief['scorecard'] ?? [], '% Avance físico') - $curveRealProgress) > 0.05) {
        biFail($failures, "{$label}: scorecard avance fisico differs from curva ejecucion real");
    }
    $curveCutoffIndex = biLastNumericIndex($progress['real']);
    $curveTheoreticalProgress = $curveCutoffIndex === null ? 0.0 : (float) ($progress['theoretical'][$curveCutoffIndex] ?? 0.0);
    biAssertSeries($failures, "{$label}: avance teorico gauge", $brief['charts']['programa-gauge']['datasets'][1]['data'] ?? [], [$curveTheoreticalProgress, max(0, 100 - $curveTheoreticalProgress)]);
    $curveCompliance = $curveTheoreticalProgress > 0
        ? round(min(150.0, max(0.0, ($curveRealProgress / $curveTheoreticalProgress) * 100)), 1)
        : ($curveRealProgress > 0 ? 100.0 : 0.0);
    biAssertSeries($failures, "{$label}: cumplimiento cronograma", $brief['charts']['programa-compliance']['datasets'][0]['data'] ?? [], [$curveCompliance, max(0, 100 - $curveCompliance)]);
    $expectedGap = round($curveRealProgress - $curveTheoreticalProgress, 1);
    $gaugeMetrics = $brief['charts']['programa-gauge']['metrics'] ?? [];
    biAssertSeries($failures, "{$label}: avance metrics", [
        $gaugeMetrics['real_pct'] ?? null,
        $gaugeMetrics['theoretical_pct'] ?? null,
        $gaugeMetrics['gap_pp'] ?? null,
    ], [$curveRealProgress, $curveTheoreticalProgress, $expectedGap]);
    $gaugeInteraction = $brief['charts']['programa-gauge']['interaction'] ?? [];
    if (($gaugeInteraction['detail_endpoint'] ?? '') !== '/api/bi/report/programa-general/progress-detail') {
        biFail($failures, "{$label}: progress detail endpoint metadata mismatch");
    }
    if (($gaugeInteraction['desktop_action'] ?? '') !== 'dblclick' || ($gaugeInteraction['mobile_action'] ?? '') !== 'button') {
        biFail($failures, "{$label}: progress detail interaction metadata mismatch");
    }
    $complianceMetrics = $brief['charts']['programa-compliance']['metrics'] ?? [];
    biAssertSeries($failures, "{$label}: compliance metrics", [
        $complianceMetrics['compliance_pct'] ?? null,
        $complianceMetrics['gap_pp'] ?? null,
    ], [$curveCompliance, $expectedGap]);
    $performanceIndex = $curveTheoreticalProgress > 0
        ? round(($curveRealProgress / $curveTheoreticalProgress) * 100, 1)
        : ($curveRealProgress > 0 ? 150.0 : 100.0);
    $expectedProgressRange = array_merge(biExpectedScheduleRange($curveRealProgress, $curveTheoreticalProgress), [
        'basis' => 'real_vs_theoretical_pct', 'basis_value' => $performanceIndex, 'tolerance_pct' => 5.0,
    ]);
    if (($gaugeMetrics['range'] ?? null) !== $expectedProgressRange) {
        biFail($failures, "{$label}: avance semantic range mismatch");
    }
    if (($complianceMetrics['range'] ?? null) !== biExpectedSemanticRange($curveCompliance, 'compliance')) {
        biFail($failures, "{$label}: compliance semantic range mismatch");
    }
    if (($brief['charts']['programa-gauge']['datasets'][0]['color'] ?? '') !== ($gaugeMetrics['range']['color_token'] ?? '')) {
        biFail($failures, "{$label}: avance chart color does not use semantic token");
    }
    if (($brief['charts']['programa-compliance']['datasets'][0]['color'] ?? '') !== ($complianceMetrics['range']['color_token'] ?? '')) {
        biFail($failures, "{$label}: compliance chart color does not use semantic token");
    }
    $complianceInteraction = $brief['charts']['programa-compliance']['interaction'] ?? [];
    if (($complianceInteraction['detail_endpoint'] ?? '') !== '/api/bi/report/programa-general/compliance-detail') {
        biFail($failures, "{$label}: compliance detail endpoint metadata mismatch");
    }
    if (($complianceInteraction['desktop_action'] ?? '') !== 'dblclick') {
        biFail($failures, "{$label}: compliance desktop action metadata mismatch");
    }
    if (($complianceInteraction['mobile_action'] ?? '') !== 'button') {
        biFail($failures, "{$label}: compliance mobile action metadata mismatch");
    }
    $delayExpected = biDelayExpected($currentPg);
    $detail = null;
    if (!method_exists($bi, 'getProgramaComplianceDetail')) {
        biFail($failures, "{$label}: compliance drill-down service is missing");
    } else {
        $detail = $bi->getProgramaComplianceDetail($projectIds, $semana, $filters, 100);
        biAssertSeries($failures, "{$label}: compliance detail summary", [
            $detail['summary']['real_pct'] ?? null,
            $detail['summary']['theoretical_pct'] ?? null,
            $detail['summary']['compliance_pct'] ?? null,
            $detail['summary']['gap_pp'] ?? null,
            $detail['summary']['delay_days'] ?? null,
        ], [$curveRealProgress, $curveTheoreticalProgress, $curveCompliance, $expectedGap, $delayExpected]);
        foreach ($detail['activities'] ?? [] as $activity) {
            if (($activity['gap_pp'] ?? 0) >= 0 || empty($activity['activity']) || str_contains($activity['activity'], '<')) {
                biFail($failures, "{$label}: drill-down contains an activity without a negative gap");
                break;
            }
        }
    }
    biAssertProgramaForecastContract(
        $failures,
        $label,
        $brief['charts']['programa-dias-retraso'] ?? [],
        $forecastTrendPg,
        $unfilteredTrendPg,
        $projectIds,
    );
    if (!method_exists($bi, 'getProgramaDelayDetail')) {
        biFail($failures, "{$label}: delay detail service is missing");
    } else {
        $delayDetail = $bi->getProgramaDelayDetail($projectIds, $semana, $filters, 100, 0);
        if (($delayDetail['forecast']['metric_key'] ?? '') !== 'pg_finish_variance_days_p50') {
            biFail($failures, "{$label}: delay detail forecast contract mismatch");
        }
        if (($delayDetail['observed']['metric_key'] ?? '') !== 'pg_observed_activity_delay_days') {
            biFail($failures, "{$label}: delay detail observed contract mismatch");
        }
        $delayActivities = $delayDetail['activities'] ?? [];
        foreach ($delayActivities as $activity) {
            if (
                (float) ($activity['observed_delay_days'] ?? 0) <= 0
                || (float) ($activity['progress_pct'] ?? 100) >= 100
                || (string) ($activity['planned_finish'] ?? '') >= (string) ($activity['cutoff'] ?? '')
            ) {
                biFail($failures, "{$label}: observed delay detail includes a non-overdue or completed activity");
                break;
            }
            foreach (['project_id', 'project', 'responsible', 'subcontractor', 'critical', 'implication'] as $field) {
                if (!array_key_exists($field, $activity)) {
                    biFail($failures, "{$label}: observed delay activity is missing {$field}");
                    break 2;
                }
            }
        }
        $observedSummary = $delayDetail['observed'] ?? [];
        if ((int) ($observedSummary['delayed_activity_count'] ?? -1) !== (int) ($delayDetail['pagination']['total'] ?? -2)) {
            biFail($failures, "{$label}: observed delay summary and pagination total diverge");
        }
        if (($delayDetail['pagination']['returned_count'] ?? null) !== count($delayActivities)) {
            biFail($failures, "{$label}: observed delay returned count mismatch");
        }
        if ((int) ($delayDetail['pagination']['total'] ?? 0) > 1) {
            $firstPage = $bi->getProgramaDelayDetail($projectIds, $semana, $filters, 1, 0);
            $secondPage = $bi->getProgramaDelayDetail($projectIds, $semana, $filters, 1, 1);
            $firstActivity = $firstPage['activities'][0] ?? [];
            $secondActivity = $secondPage['activities'][0] ?? [];
            if (($firstPage['pagination']['has_more'] ?? false) !== true
                || ($secondPage['pagination']['offset'] ?? null) !== 1
                || ($secondPage['pagination']['returned_count'] ?? null) !== 1) {
                biFail($failures, "{$label}: observed delay pagination does not expose a valid second page");
            }
            $firstKey = ($firstActivity['project_id'] ?? '') . ':' . ($firstActivity['unique_id'] ?? '');
            $secondKey = ($secondActivity['project_id'] ?? '') . ':' . ($secondActivity['unique_id'] ?? '');
            if ($firstKey === ':' || $secondKey === ':' || $firstKey === $secondKey) {
                biFail($failures, "{$label}: observed delay second page repeats the first activity");
            }
        }
    }
    if ($detail !== null && abs((float) ($detail['summary']['delay_days'] ?? 0) - $delayExpected) > 0.05) {
        biFail($failures, "{$label}: compliance detail observed delay mismatch");
    }
    if (!method_exists($bi, 'getProgramaProgressDetail')) {
        biFail($failures, "{$label}: progress drill-down service is missing");
    } else {
        $progressDetail = $bi->getProgramaProgressDetail($projectIds, $semana, $filters, 100);
        biAssertSeries($failures, "{$label}: progress detail summary", [
            $progressDetail['summary']['real_pct'] ?? null,
            $progressDetail['summary']['theoretical_pct'] ?? null,
            $progressDetail['summary']['gap_pp'] ?? null,
        ], [$curveRealProgress, $curveTheoreticalProgress, $expectedGap]);
        foreach ($progressDetail['activities'] ?? [] as $activity) {
            foreach (['weight_pct', 'real_contribution_pp', 'planned_contribution_pp', 'recoverable_pp'] as $field) {
                if (!array_key_exists($field, $activity)) {
                    biFail($failures, "{$label}: progress activity is missing {$field}");
                    break 2;
                }
            }
        }
        foreach (['project', 'responsible', 'subcontractor'] as $groupKey) {
            if (!array_key_exists($groupKey, $progressDetail['groups'] ?? [])) {
                biFail($failures, "{$label}: progress detail is missing {$groupKey} grouping");
            }
        }
    }

    $cnp = biCategoryExpected($psUniverse, 'Categoria_CNP', 'is_cnp_population');
    $cnc = biCategoryExpected($psUniverse, 'Categoria_CNC', 'is_cnc_population');
    foreach (['cnp' => $cnp, 'cnc' => $cnc] as $kind => $expectedCause) {
        $causeChart = $brief['charts']['programa-' . $kind] ?? [];
        $actualCounts = array_combine($causeChart['labels'] ?? [], $causeChart['datasets'][0]['data'] ?? []) ?: [];
        $expectedCounts = array_combine($expectedCause['labels'], $expectedCause['values']) ?: [];
        ksort($actualCounts);
        ksort($expectedCounts);
        if ($actualCounts !== $expectedCounts) {
            biFail($failures, "{$label}: " . strtoupper($kind) . ' category counts mismatch');
        }
    }
    biAssertCausalDrilldownContract($failures, $bi, $projectIds, $semana, $filters, 'cnp', $label);
    biAssertCausalDrilldownContract($failures, $bi, $projectIds, $semana, $filters, 'cnc', $label);
    $radar = $brief['charts']['programa-radar-productividad'] ?? [];
    $expectedRadar = biRadarExpected($radarRows);
    $expectedValues = array_values(array_map(static fn(array $axis): ?float => $axis['display_value'], $expectedRadar));
    biAssertSeries($failures, "{$label}: radar", $radar['datasets'][0]['data'] ?? [], $expectedValues);
    foreach ($expectedRadar as $axisKey => $expectedAxis) {
        $actualAxis = $radar['axes'][$axisKey] ?? [];
        foreach (['numerator', 'denominator', 'sample_size', 'raw_value', 'display_value'] as $field) {
            biAssertSeries($failures, "{$label}: radar {$axisKey} {$field}", [$actualAxis[$field] ?? null], [$expectedAxis[$field]]);
        }
        if (($actualAxis['available'] ?? null) !== $expectedAxis['available']) {
            biFail($failures, "{$label}: radar {$axisKey} availability mismatch");
        }
    }
    if (!method_exists($bi, 'getProgramaRadarDetail')) {
        biFail($failures, "{$label}: radar drill-down service is missing");
    } else {
        $radarDetail = $bi->getProgramaRadarDetail($projectIds, $semana, $filters, 'productividad', 100, 0);
        if ((int) ($radarDetail['summary']['total_population'] ?? -1) !== count($radarRows)
            || count($radarDetail['records'] ?? []) !== min(100, count($radarRows))) {
            biFail($failures, "{$label}: radar detail rows do not match filtered operational source");
        }
        foreach ($radarDetail['records'] ?? [] as $record) {
            foreach (['project', 'semana', 'cutoff', 'activity', 'unit', 'commitment', 'executed', 'p_completed', 'pac', 'responsible', 'subcontractor', 'critical', 'tnp', 'eligibility', 'exclusion_reasons'] as $field) {
                if (!array_key_exists($field, $record)) {
                    biFail($failures, "{$label}: radar record is missing {$field}");
                    break 2;
                }
            }
            if (($record['tnp'] ?? false) === true && (($record['eligibility']['productividad']['reason'] ?? '') !== 'Excluido porque Es_TNP=1.')) {
                biFail($failures, "{$label}: radar TNP row does not explain its exclusion");
                break;
            }
        }
        $eligibleProductivity = (int) ($expectedRadar['productividad']['denominator'] ?? -1);
        if (($radarDetail['summary']['axis'] ?? '') !== 'productividad'
            || (int) ($radarDetail['summary']['eligible_count'] ?? -1) !== $eligibleProductivity
            || (int) ($radarDetail['summary']['denominator'] ?? -1) !== (int) ($expectedRadar['productividad']['denominator'] ?? -2)) {
            biFail($failures, "{$label}: radar detail summary does not reconcile selected axis population");
        }
    }
}

function biValidateProgramaGeneralDistinctCutoffsSameWeek(
    \Database $db,
    ControlTowerService $bi,
    array &$failures,
    array $projectIds,
    string $semana,
    string $label
): void {
    $brief = $bi->getBrief('programa-general', $projectIds, $semana, 'R', []);
    $currentPg = biFetchPg($db, $projectIds, $semana, [], false);
    if (!$currentPg) {
        biFail($failures, "{$label}: missing current PG rows");
        return;
    }

    $cutoffsByProject = [];
    foreach ($currentPg as $row) {
        $projectId = (int) ($row['project_id'] ?? 0);
        $cutoff = (string) ($row['Fecha_Fin_Sem'] ?? '');
        if ($projectId > 0 && $cutoff !== '') {
            $cutoffsByProject[$projectId] = $cutoff;
        }
    }
    if (count(array_unique(array_values($cutoffsByProject))) < 2) {
        biFail($failures, "{$label}: scenario lost distinct project cutoffs");
        return;
    }

    $currentProgress = biCurrentProgressExpected($currentPg);
    $expectedGap = round($currentProgress['real'] - $currentProgress['planned'], 1);
    $delayExpected = biDelayExpected($currentPg);
    $gaugeMetrics = $brief['charts']['programa-gauge']['metrics'] ?? [];
    $complianceMetrics = $brief['charts']['programa-compliance']['metrics'] ?? [];
    biAssertSeries($failures, "{$label}: mixed-cutoff gauge metrics", [
        $gaugeMetrics['real_pct'] ?? null,
        $gaugeMetrics['theoretical_pct'] ?? null,
        $gaugeMetrics['gap_pp'] ?? null,
    ], [$currentProgress['real'], $currentProgress['planned'], $expectedGap]);
    biAssertSeries($failures, "{$label}: mixed-cutoff compliance metrics", [
        $complianceMetrics['compliance_pct'] ?? null,
        $complianceMetrics['gap_pp'] ?? null,
    ], [$currentProgress['compliance'], $expectedGap]);
    $trendPg = biFetchPg($db, $projectIds, $semana, [], true);
    $forecastTrendPg = biForecastTrendExpected($db, $projectIds, $semana, []);
    $unfilteredTrendPg = biFetchPg($db, $projectIds, '', [], true);
    biAssertProgramaForecastContract(
        $failures,
        $label,
        $brief['charts']['programa-dias-retraso'] ?? [],
        $forecastTrendPg,
        $unfilteredTrendPg,
        $projectIds,
    );

    $detail = $bi->getProgramaComplianceDetail($projectIds, $semana, [], 100);
    biAssertSeries($failures, "{$label}: mixed-cutoff detail summary", [
        $detail['summary']['real_pct'] ?? null,
        $detail['summary']['theoretical_pct'] ?? null,
        $detail['summary']['compliance_pct'] ?? null,
        $detail['summary']['gap_pp'] ?? null,
        $detail['summary']['delay_days'] ?? null,
    ], [$currentProgress['real'], $currentProgress['planned'], $currentProgress['compliance'], $expectedGap, $delayExpected]);

    $expectedActivities = biComplianceActivitiesAtOwnCutoffExpected($currentPg);
    $projectsExplainingGap = array_values(array_unique(array_map(
        static fn(array $activity): int => (int) ($activity['project_id'] ?? 0),
        $expectedActivities
    )));
    sort($projectsExplainingGap);
    $sortedProjectIds = array_values(array_map('intval', $projectIds));
    sort($sortedProjectIds);
    if ($projectsExplainingGap !== $sortedProjectIds) {
        biFail($failures, "{$label}: expected both projects to explain the gap");
        return;
    }

    biAssertComplianceActivitiesAtOwnCutoff(
        $failures,
        "{$label}: mixed-cutoff detail activities",
        $detail['activities'] ?? [],
        $expectedActivities
    );
}

function biCurvaSDirectExpected(array $rows): array
{
    $context = biCurveContext($rows);
    $totalWeight = max(1.0, (float) ($context['total_weight'] ?? 0));
    $labels = $real = $planned = [];
    foreach ($context['observed_dates'] ?? [] as $pointDate) {
        $aggregate = biAggregateExpectedPoint($context, $pointDate, true);
        $date = biDate($pointDate);
        if ($date === null) {
            continue;
        }
        $labels[] = $date->format('d/m/y');
        $real[] = round(($aggregate['real'] / $totalWeight) * 100, 2);
        $planned[] = round(($aggregate['planned'] / $totalWeight) * 100, 2);
    }

    return ['labels' => $labels, 'real' => $real, 'planned' => $planned];
}

$context = $db->query(
    "SELECT project_id, Semana, sub_contratista, responsable_aia, COUNT(*) AS rows_count
     FROM bi_pg_semana
     WHERE COALESCE(sub_contratista, '') <> ''
       AND COALESCE(responsable_aia, '') <> ''
     GROUP BY project_id, Semana, sub_contratista, responsable_aia
     ORDER BY rows_count DESC
     LIMIT 1",
)->fetch(PDO::FETCH_ASSOC);

if (!$context) {
    echo "FAIL: no BI context with subcontractor and responsible data\n";
    exit(1);
}

biValidateProgramaGeneral($db, $bi, $failures, [(int) $context['project_id']], (string) $context['Semana'], [
    'sub' => (string) $context['sub_contratista'],
    'resp' => (string) $context['responsable_aia'],
], 'single-project-week-sub-resp');

biValidateProgramaGeneral($db, $bi, $failures, [BiContractFixture::PROYECTO_A, BiContractFixture::PROYECTO_B], '', [
    'desde' => '2026-07-06',
    'hasta' => '2026-07-27',
], 'canonical-multi-project-date-range');

biValidateProgramaGeneral($db, $bi, $failures, [BiContractFixture::PROYECTO_A, BiContractFixture::PROYECTO_B], '', [
    'desde' => '2026-07-06',
    'hasta' => '2026-07-27',
], 'multi-project-date-range');

$baselineDrift = $db->query(
    "WITH weekly_finish AS (
        SELECT project_id, Semana, MAX(Fecha_Fin) AS finish
        FROM programa_consolidado
        WHERE COALESCE(Titulo, 0) = 0
        GROUP BY project_id, Semana
    ), bounds AS (
        SELECT project_id, MIN(Semana) AS first_week, MAX(Semana) AS last_week
        FROM weekly_finish GROUP BY project_id
    )
    SELECT b.project_id, b.last_week, first.finish AS first_finish, latest.finish AS latest_finish
    FROM bounds b
    JOIN weekly_finish first ON first.project_id = b.project_id AND first.Semana = b.first_week
    JOIN weekly_finish latest ON latest.project_id = b.project_id AND latest.Semana = b.last_week
    WHERE first.finish <> latest.finish
    ORDER BY b.project_id LIMIT 1"
)->fetch(PDO::FETCH_ASSOC);
if (!$baselineDrift) {
    biFail($failures, 'baseline-drift: no reprogrammed project scenario found');
} else {
    $driftBrief = $bi->getBrief('programa-general', [(int) $baselineDrift['project_id']], (string) $baselineDrift['last_week'], 'R', []);
    $driftMetrics = $driftBrief['charts']['programa-dias-retraso']['metrics'] ?? [];
    if (($driftMetrics['contractual_finish'] ?? '') !== (string) $baselineDrift['first_finish']) {
        biFail($failures, 'baseline-drift: contractual finish moved with latest reprogramming');
    }
    if (($driftMetrics['contractual_finish'] ?? '') === (string) $baselineDrift['latest_finish']) {
        biFail($failures, 'baseline-drift: contractual finish incorrectly uses latest snapshot');
    }
}

$multiFiltered = $db->query(
    "SELECT sub_contratista, responsable_aia, MIN(Fecha_Fin_Sem) AS desde, MAX(Fecha_Fin_Sem) AS hasta
     FROM bi_pg_semana
     WHERE COALESCE(sub_contratista, '') <> ''
       AND COALESCE(responsable_aia, '') <> ''
     GROUP BY sub_contratista, responsable_aia
     HAVING COUNT(DISTINCT project_id) >= 2
     ORDER BY COUNT(*) DESC
     LIMIT 1",
)->fetch(PDO::FETCH_ASSOC);
if ($multiFiltered) {
    $multiFilteredProjectIds = $db->query(
        "SELECT DISTINCT project_id
         FROM bi_pg_semana
         WHERE sub_contratista = ?
           AND responsable_aia = ?
         ORDER BY project_id",
        [(string) $multiFiltered['sub_contratista'], (string) $multiFiltered['responsable_aia']],
    )->fetchAll(PDO::FETCH_COLUMN);
    biValidateProgramaGeneral($db, $bi, $failures, array_map('intval', $multiFilteredProjectIds), '', [
        'desde' => (string) $multiFiltered['desde'],
        'hasta' => (string) $multiFiltered['hasta'],
        'sub' => (string) $multiFiltered['sub_contratista'],
        'resp' => (string) $multiFiltered['responsable_aia'],
    ], 'multi-project-date-range-sub-resp');
}

biValidateProgramaGeneral($db, $bi, $failures, [BiContractFixture::PROYECTO_A], '3', [
    'sub' => 'Proveedor CI Nuevo',
], 'mutable-subcontractor-cohort-canonical-fixture');

$staleFilterProjectIds = [BiContractFixture::PROYECTO_A];
$staleFilterWeek = '3';
$staleFilterFilters = ['sub' => 'Proveedor CI Construccion'];
$staleCurrentRows = biFetchPg($db, $staleFilterProjectIds, $staleFilterWeek, $staleFilterFilters, false);
$staleHistoricalRows = biFetchPg($db, $staleFilterProjectIds, $staleFilterWeek, $staleFilterFilters, true);
if ($staleCurrentRows !== [] || $staleHistoricalRows === []) {
    biFail($failures, 'stale-filtered-cohort: real regression fixture no longer isolates historical-only matches');
} else {
    $staleBrief = $bi->getBrief('programa-general', $staleFilterProjectIds, $staleFilterWeek, 'R', $staleFilterFilters);
    $staleChart = $staleBrief['charts']['programa-dias-retraso'] ?? [];
    $staleProject = $staleChart['metrics']['project_breakdown'][0] ?? [];
    if (($staleChart['availability'] ?? true) !== false
        || ($staleChart['status'] ?? '') !== 'unavailable'
        || ($staleProject['availability'] ?? true) !== false
        || !str_contains((string) ($staleProject['reason'] ?? ''), 'No hay actividades')) {
        biFail($failures, 'stale-filtered-cohort: historical text matches must not fabricate a current forecast');
    }
}

$missingFilteredProjectIds = [BiContractFixture::PROYECTO_A, BiContractFixture::PROYECTO_B];
$missingFilteredWeek = '3';
$missingFilteredFilters = ['sub' => 'Proveedor CI Nuevo'];
$missingFilteredRows = biFetchPg(
    $db,
    $missingFilteredProjectIds,
    $missingFilteredWeek,
    $missingFilteredFilters,
    false,
);
$presentFilteredProjectIds = array_values(array_unique(array_map(
    static fn(array $row): int => (int) ($row['project_id'] ?? 0),
    $missingFilteredRows,
)));
if (count($presentFilteredProjectIds) >= count($missingFilteredProjectIds)) {
    biFail($failures, 'missing-filtered-project: real regression fixture no longer excludes a selected project');
} else {
    $missingFilteredBrief = $bi->getBrief(
        'programa-general',
        $missingFilteredProjectIds,
        $missingFilteredWeek,
        'R',
        $missingFilteredFilters,
    );
    $missingFilteredChart = $missingFilteredBrief['charts']['programa-dias-retraso'] ?? [];
    $missingFilteredMetrics = $missingFilteredChart['metrics'] ?? [];
    $breakdown = $missingFilteredMetrics['project_breakdown'] ?? [];
    if (($missingFilteredChart['availability'] ?? true) !== false
        || ($missingFilteredChart['status'] ?? '') !== 'unavailable') {
        biFail($failures, 'missing-filtered-project: portfolio forecast must be unavailable when one selected project has no matching data');
    }
    if (count($breakdown) !== count($missingFilteredProjectIds)
        || ($missingFilteredMetrics['scope']['project_count'] ?? null) !== count($missingFilteredProjectIds)) {
        biFail($failures, 'missing-filtered-project: forecast silently removed a selected project');
    }
    foreach (array_diff($missingFilteredProjectIds, $presentFilteredProjectIds) as $missingProjectId) {
        $project = array_values(array_filter(
            $breakdown,
            static fn(array $row): bool => (int) ($row['project_id'] ?? 0) === (int) $missingProjectId,
        ))[0] ?? [];
        if (($project['availability'] ?? true) !== false
            || !str_contains((string) ($project['reason'] ?? ''), 'No hay actividades')) {
            biFail($failures, "missing-filtered-project: project {$missingProjectId} lacks an explicit unavailable reason");
        }
    }
}

$distinctCutoffWeeks = $db->query(
    "SELECT Semana,
            MIN(Fecha_Fin_Sem) AS min_cutoff,
            MAX(Fecha_Fin_Sem) AS max_cutoff,
            COUNT(*) AS rows_count
     FROM bi_pg_semana
     WHERE Fecha_Fin_Sem IS NOT NULL
     GROUP BY Semana
     HAVING COUNT(DISTINCT project_id) >= 2
        AND MIN(Fecha_Fin_Sem) <> MAX(Fecha_Fin_Sem)
     ORDER BY ABS(DATEDIFF(MAX(Fecha_Fin_Sem), MIN(Fecha_Fin_Sem))) DESC,
              rows_count DESC,
              Semana DESC
     LIMIT 20",
)->fetchAll(PDO::FETCH_ASSOC);
$distinctCutoffScenario = null;
foreach ($distinctCutoffWeeks as $candidateWeek) {
    $projectsForWeek = $db->query(
        "SELECT project_id, MIN(Fecha_Fin_Sem) AS cutoff, COUNT(*) AS rows_count
         FROM bi_pg_semana
         WHERE Semana = ?
           AND Fecha_Fin_Sem IS NOT NULL
         GROUP BY project_id
         HAVING COUNT(*) > 0
         ORDER BY cutoff ASC, rows_count DESC, project_id ASC",
        [(string) $candidateWeek['Semana']],
    )->fetchAll(PDO::FETCH_ASSOC);
    if (count($projectsForWeek) < 2) {
        continue;
    }

    $first = $projectsForWeek[0];
    $last = $projectsForWeek[count($projectsForWeek) - 1];
    if (($first['cutoff'] ?? '') === ($last['cutoff'] ?? '')) {
        continue;
    }

    $candidateProjectIds = [(int) $first['project_id'], (int) $last['project_id']];
    $candidateRows = biFetchPg($db, $candidateProjectIds, (string) $candidateWeek['Semana'], [], false);
    $candidateActivities = biComplianceActivitiesAtOwnCutoffExpected($candidateRows);
    $projectsExplainingGap = array_values(array_unique(array_map(
        static fn(array $activity): int => (int) ($activity['project_id'] ?? 0),
        $candidateActivities
    )));
    sort($projectsExplainingGap);
    $sortedCandidateProjectIds = $candidateProjectIds;
    sort($sortedCandidateProjectIds);
    if ($projectsExplainingGap !== $sortedCandidateProjectIds) {
        continue;
    }

    $distinctCutoffScenario = [
        'project_ids' => $candidateProjectIds,
        'semana' => (string) $candidateWeek['Semana'],
    ];
    break;
}
if ($distinctCutoffScenario === null) {
    biFail($failures, 'multi-project-same-week-distinct-cutoffs: no real context found with both projects explaining the gap');
} else {
    biValidateProgramaGeneralDistinctCutoffsSameWeek(
        $db,
        $bi,
        $failures,
        $distinctCutoffScenario['project_ids'],
        $distinctCutoffScenario['semana'],
        'multi-project-same-week-distinct-cutoffs'
    );

    $directRows = biFetchPg(
        $db,
        $distinctCutoffScenario['project_ids'],
        $distinctCutoffScenario['semana'],
        [],
        false
    );
    $directExpected = biCurvaSDirectExpected($directRows);
    $directBrief = $bi->getBrief(
        'curva-s',
        $distinctCutoffScenario['project_ids'],
        $distinctCutoffScenario['semana'],
        'R',
        []
    );
    $directChart = $directBrief['charts']['chart-curva-s'] ?? [];
    if (($directChart['labels'] ?? []) !== $directExpected['labels']) {
        biFail($failures, 'multi-project-same-week-distinct-cutoffs: Curva S direct labels must use effective cutoff dates');
    }
    biAssertSeries($failures, 'multi-project-same-week-distinct-cutoffs: Curva S direct real', $directChart['datasets'][1]['data'] ?? [], $directExpected['real']);
    biAssertSeries($failures, 'multi-project-same-week-distinct-cutoffs: Curva S direct planned', $directChart['datasets'][0]['data'] ?? [], $directExpected['planned']);
}

$noProductionFiltered = $db->query(
    "SELECT sub_contratista, responsable_aia, MIN(Fecha_Fin_Sem) AS desde, MAX(Fecha_Fin_Sem) AS hasta
     FROM bi_pg_semana
     WHERE COALESCE(sub_contratista, '') <> ''
       AND COALESCE(responsable_aia, '') <> ''
     GROUP BY sub_contratista, responsable_aia
     HAVING COUNT(DISTINCT project_id) >= 2
        AND MAX(COALESCE(Ejecutado, 0)) <= 0
     ORDER BY COUNT(*) DESC
     LIMIT 1",
)->fetch(PDO::FETCH_ASSOC);
if ($noProductionFiltered) {
    $noProductionProjectIds = $db->query(
        "SELECT DISTINCT project_id
         FROM bi_pg_semana
         WHERE sub_contratista = ?
           AND responsable_aia = ?
         ORDER BY project_id",
        [(string) $noProductionFiltered['sub_contratista'], (string) $noProductionFiltered['responsable_aia']],
    )->fetchAll(PDO::FETCH_COLUMN);
    $noProductionFilters = [
        'desde' => (string) $noProductionFiltered['desde'],
        'hasta' => (string) $noProductionFiltered['hasta'],
        'sub' => (string) $noProductionFiltered['sub_contratista'],
        'resp' => (string) $noProductionFiltered['responsable_aia'],
    ];
    biValidateProgramaGeneral(
        $db,
        $bi,
        $failures,
        array_map('intval', $noProductionProjectIds),
        '',
        $noProductionFilters,
        'multi-project-date-range-sub-resp-no-production'
    );
    $noProductionBrief = $bi->getBrief('programa-general', array_map('intval', $noProductionProjectIds), '', 'R', $noProductionFilters);
    $noProductionChart = $noProductionBrief['charts']['programa-curva-ejecucion'] ?? [];
    $noProductionMeta = $noProductionChart['projection_meta'] ?? [];
    if (($noProductionMeta['projection_available'] ?? true) !== false) {
        biFail($failures, 'multi-project-date-range-sub-resp-no-production: projection should be marked unavailable');
    }
    if (($noProductionMeta['simulation_count'] ?? -1) !== 0) {
        biFail($failures, 'multi-project-date-range-sub-resp-no-production: should not run simulations without production history');
    }
    foreach ([2 => 'pessimistic', 3 => 'likely', 4 => 'optimistic'] as $datasetIndex => $projectionLabel) {
        $completion = biProjectionCompletionWeek($noProductionChart['datasets'][$datasetIndex]['data'] ?? []);
        if ($completion !== null) {
            biFail($failures, "multi-project-date-range-sub-resp-no-production: {$projectionLabel} projection fabricates a 100% finish");
        }
    }
}

if ($failures) {
    foreach ($failures as $failure) {
        echo "FAIL: {$failure}\n";
    }
    exit(1);
}

echo "PASS: Programa General mandatory charts match independent SQL for one project/week/sub/responsible\n";
echo "PASS: Programa General mandatory charts match independent SQL for the canonical multi-project date range\n";
echo "PASS: Programa General mandatory charts match independent SQL for multi-project date range\n";
echo "PASS: Programa General mandatory charts match independent SQL for multi-project date range/sub/responsible filters\n";
