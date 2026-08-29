<?php

declare(strict_types=1);
// @requiere: db


require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/support/BiContractFixture.php';

use App\Services\ControlTowerService;
use App\Security\DataScope\MultiProjectScope;
use App\Security\DataScope\SystemScopeRunner;

$db = \Database::getInstance();
BiContractFixture::seedCausalRows($db);
// Los briefs de programa-general se arman sobre `programa_consolidado`, así que el escenario
// multi-proyecto por rango de fechas necesita también las instantáneas. Antes apuntaba a Da Porto
// y se cumplía con las filas reales del proyecto; contra los proyectos sacrificables hay que
// sembrarlas explícitamente.
BiContractFixture::seedProgramSnapshots($db);
$bi = new ControlTowerService();
$scope = static fn(array $ids, string $case): MultiProjectScope => new MultiProjectScope(
    $ids,
    'fixture-bi-filters',
    'R',
    'test:test_bi_filters_apply_to_charts:' . $case,
);
$globalRead = static function (callable $read) use ($db): mixed {
    return (new SystemScopeRunner($db->dataScope()))->run('test:bi-filters:discovery', $read);
};

function lastNumericPoint(array $values): float
{
    for ($i = count($values) - 1; $i >= 0; $i--) {
        if (is_numeric($values[$i] ?? null)) {
            return (float) $values[$i];
        }
    }

    return 0.0;
}

// Los descubrimientos de escenario se anclan a los proyectos sacrificables del fixture: sobre la
// base compartida de dev, cualquier restauración ajena cambia la fila elegida por el LIMIT 1 y el
// test oscila sin relación con el código bajo prueba (medido el 2026-08-19). La mecánica de
// descubrimiento se conserva — solo el universo es determinista.
$fixtureProjectsSql = BiContractFixture::PROYECTO_A . ', ' . BiContractFixture::PROYECTO_B;

$context = $globalRead(static fn() => $db->query(
    "SELECT project_id, Semana, sub_contratista, responsable_aia, COUNT(*) AS rows_count
     FROM bi_pg_semana
     WHERE project_id IN ({$fixtureProjectsSql})
       AND COALESCE(sub_contratista, '') <> ''
       AND COALESCE(responsable_aia, '') <> ''
     GROUP BY project_id, Semana, sub_contratista, responsable_aia
     ORDER BY rows_count DESC, project_id, Semana, sub_contratista, responsable_aia
     LIMIT 1",
)->fetch(PDO::FETCH_ASSOC));

if (!$context) {
    echo "FAIL: no BI context with subcontractor and responsible data\n";
    exit(1);
}

$projectId = (int) $context['project_id'];
$semana = (string) $context['Semana'];
$filters = [
    'sub' => (string) $context['sub_contratista'],
    'resp' => (string) $context['responsable_aia'],
];

$brief = $bi->getBrief($scope([$projectId], 'programa'), 'programa-general', $semana, 'R', $filters);
$expectedStmt = $db->queryForProjects(
    $scope([$projectId], 'oracle-programa'),
    "SELECT COUNT(*) AS total,
            SUM(CASE
                WHEN pc.Ruta_Critica = 1
                 AND pc.Fecha_Fin IS NOT NULL
                 AND COALESCE(sa.Fecha_Fin_Sem, sa.Fecha_Inicio_Sem) IS NOT NULL
                 AND pc.Fecha_Fin < COALESCE(sa.Fecha_Fin_Sem, sa.Fecha_Inicio_Sem)
                 AND (pc.Ejecutado IS NULL OR pc.Ejecutado < 1)
                THEN 1 ELSE 0
            END) AS critical_late,
            SUM(CASE
                WHEN pc.Fecha_Inicio IS NOT NULL
                 AND pc.Fecha_Fin IS NOT NULL
                 AND DATEDIFF(pc.Fecha_Fin, pc.Fecha_Inicio) >= 0
                THEN DATEDIFF(pc.Fecha_Fin, pc.Fecha_Inicio) + 1 ELSE 0
            END) AS total_duration,
            SUM(CASE
                WHEN pc.Fecha_Inicio IS NOT NULL
                 AND pc.Fecha_Fin IS NOT NULL
                 AND DATEDIFF(pc.Fecha_Fin, pc.Fecha_Inicio) >= 0
                THEN LEAST(1, GREATEST(0, COALESCE(pc.Ejecutado, 0))) * (DATEDIFF(pc.Fecha_Fin, pc.Fecha_Inicio) + 1)
                ELSE 0
            END) AS weighted_real,
            SUM(CASE
                WHEN pc.Fecha_Inicio IS NULL
                  OR pc.Fecha_Fin IS NULL
                  OR COALESCE(sa.Fecha_Fin_Sem, sa.Fecha_Inicio_Sem) IS NULL
                  OR DATEDIFF(pc.Fecha_Fin, pc.Fecha_Inicio) < 0
                THEN 0
                WHEN DATEDIFF(COALESCE(sa.Fecha_Fin_Sem, sa.Fecha_Inicio_Sem), pc.Fecha_Inicio) + 1 <= 0 THEN 0
                WHEN DATEDIFF(COALESCE(sa.Fecha_Fin_Sem, sa.Fecha_Inicio_Sem), pc.Fecha_Inicio) + 1 >= DATEDIFF(pc.Fecha_Fin, pc.Fecha_Inicio) + 1
                THEN DATEDIFF(pc.Fecha_Fin, pc.Fecha_Inicio) + 1
                ELSE DATEDIFF(COALESCE(sa.Fecha_Fin_Sem, sa.Fecha_Inicio_Sem), pc.Fecha_Inicio) + 1
            END) AS weighted_theoretical
     FROM programa_consolidado pc
     LEFT JOIN semanas_activas sa
       ON sa.project_id = pc.project_id
      AND sa.Semana = pc.Semana
     WHERE pc.project_id = ?
       AND pc.Semana = ?
       AND COALESCE(pc.Titulo, 0) = 0
       AND LOWER(COALESCE(pc.Sub_Contratista, '')) LIKE ?
       AND LOWER(COALESCE(pc.Responsable_AIA, '')) LIKE ?",
    [
    $projectId,
    $semana,
    '%' . strtolower($filters['sub']) . '%',
    '%' . strtolower($filters['resp']) . '%',
    ],
);
$expected = $expectedStmt->fetch(PDO::FETCH_ASSOC) ?: [];

$gauge = $brief['charts']['programa-gauge']['datasets'][0]['data'] ?? [];
$curveReal = $brief['charts']['programa-curva-ejecucion']['datasets'][1]['data'] ?? [];
$compliance = $brief['charts']['programa-compliance']['datasets'][0]['data'] ?? [];
$totalDuration = (float) ($expected['total_duration'] ?? 0);
$realPct = $totalDuration > 0 ? round(((float) $expected['weighted_real'] / $totalDuration) * 100, 1) : 0.0;
$theoreticalPct = $totalDuration > 0 ? round(((float) $expected['weighted_theoretical'] / $totalDuration) * 100, 1) : 0.0;
$curveRealPct = lastNumericPoint($curveReal);
$expectedGauge = [$curveRealPct, max(0, 100 - $curveRealPct)];
$scheduleCompliance = $theoreticalPct > 0 ? round(min(150.0, max(0.0, ($realPct / $theoreticalPct) * 100)), 1) : ($realPct > 0 ? 100.0 : 0.0);
$expectedCompliance = [$scheduleCompliance, max(0, 100 - $scheduleCompliance)];

$failures = [];
if (($brief['raw_row_count'] ?? null) !== (int) $expected['total']) {
    $failures[] = 'raw_row_count does not match filtered SQL';
}
if ($gauge !== $expectedGauge) {
    $failures[] = 'programa-gauge dataset does not match Curva S real acumulado';
}
if ($compliance !== $expectedCompliance) {
    $failures[] = 'programa-compliance dataset does not match filtered SQL';
}
if ((float) ($brief['scorecard'][3]['value'] ?? -1) !== (float) ($expected['critical_late'] ?? 0)) {
    $failures[] = 'critical late scorecard does not match filtered SQL';
}
$mandatoryCharts = [
    'programa-curva-ejecucion',
    'programa-gauge',
    'programa-compliance',
    'programa-dias-retraso',
    'programa-cnp',
    'programa-cnc',
    'programa-radar-productividad',
];
foreach ($mandatoryCharts as $chartId) {
    if (empty($brief['charts'][$chartId]['datasets'])) {
        $failures[] = "mandatory Programa General chart missing dataset: {$chartId}";
    }
}

$rangeProjects = [BiContractFixture::PROYECTO_A, BiContractFixture::PROYECTO_B];
$rangeFilters = ['desde' => '2026-07-06', 'hasta' => '2026-07-26'];
$rangeBrief = $bi->getBrief($scope($rangeProjects, 'range-brief'), 'programa-general', '', 'R', $rangeFilters);
if (($rangeBrief['filters']['date_range_overrides_semana'] ?? false) !== true) {
    $failures[] = 'date range did not override semana';
}
if (($rangeBrief['raw_row_count'] ?? 0) <= 0) {
    $failures[] = 'multi-project date range returned no BI rows';
}

$options = $bi->getFilterOptions($scope([$projectId], 'filter-options'), $semana, []);
if (!in_array($filters['sub'], $options['subcontratistas'] ?? [], true)) {
    $failures[] = 'subcontractor options do not include filtered subcontractor';
}
if (!in_array($filters['resp'], $options['responsables'] ?? [], true)) {
    $failures[] = 'responsible options do not include filtered responsible';
}

$causalContext = $globalRead(static fn() => $db->query(
    "SELECT ps.project_id, ps.Semana, ps.Sub_Contratista, ps.Responsable_AIA, ps.Actividad, ps.Ubicacion
     FROM programacion_semanal ps
     WHERE ps.project_id IN ({$fixtureProjectsSql})
       AND ps.Activa = '0' AND COALESCE(TRIM(ps.CNP), '') <> ''
       AND COALESCE(TRIM(ps.Sub_Contratista), '') <> ''
       AND COALESCE(TRIM(ps.Responsable_AIA), '') <> ''
       AND COALESCE(TRIM(ps.Actividad), '') <> ''
     ORDER BY ps.project_id, ps.Semana, ps.Consecutivo
     LIMIT 1",
)->fetch(PDO::FETCH_ASSOC));
if (!$causalContext) {
    $failures[] = 'no CNP causal context with sub/responsible/activity filters';
} else {
    $causalActivityText = trim(strip_tags(html_entity_decode((string) $causalContext['Actividad'], ENT_QUOTES | ENT_HTML5, 'UTF-8')));
    $causalFilters = ['sub' => $causalContext['Sub_Contratista'], 'resp' => $causalContext['Responsable_AIA'], 'etapa' => substr($causalActivityText, 0, 12)];
    $causalScope = $scope([(int) $causalContext['project_id']], 'causal');
    $cnpDetail = $bi->getProgramaCnpDetail($causalScope, (string) $causalContext['Semana'], $causalFilters);
    $cnpChart = $bi->getBrief($causalScope, 'programa-general', (string) $causalContext['Semana'], 'R', $causalFilters)['charts']['programa-cnp']['datasets'][0]['data'] ?? [];
    if ((int) ($cnpDetail['summary']['total'] ?? -1) !== (int) array_sum($cnpChart)) $failures[] = 'CNP detail total does not match chart under semana/sub/resp/etapa filters';
    foreach ($cnpDetail['activities'] ?? [] as $activity) {
        $matchesStage = str_contains(strtolower($activity['activity']), strtolower($causalFilters['etapa']))
            || str_contains(strtolower($activity['location'] ?? ''), strtolower($causalFilters['etapa']));
        if ((int) $activity['semana'] !== (int) $causalContext['Semana'] || !str_contains(strtolower($activity['subcontractor']), strtolower($causalFilters['sub'])) || !str_contains(strtolower($activity['responsible']), strtolower($causalFilters['resp'])) || !$matchesStage) {
            $failures[] = 'CNP detail ignored semana/sub/responsible/etapa filter'; break;
        }
    }
}

$rangeCnp = $bi->getProgramaCnpDetail($scope($rangeProjects, 'range-cnp-detail'), '', $rangeFilters);
$rangeCnpChart = $bi->getBrief($scope($rangeProjects, 'range-cnp-chart'), 'programa-general', '', 'R', $rangeFilters)['charts']['programa-cnp']['datasets'][0]['data'] ?? [];
if ((int) ($rangeCnp['summary']['total'] ?? -1) !== (int) array_sum($rangeCnpChart)) {
    $failures[] = 'CNP multi-project date-range detail total does not match chart';
}

$radarContext = $globalRead(static fn() => $db->query(
    "SELECT ps.project_id, ps.Semana, ps.Sub_Contratista, ps.Responsable_AIA, ps.Actividad, ps.Ubicacion
     FROM programacion_semanal ps
     WHERE ps.project_id IN ({$fixtureProjectsSql})
       AND ps.Activa IN ('1', 'NA')
       AND COALESCE(TRIM(ps.Sub_Contratista), '') <> ''
       AND COALESCE(TRIM(ps.Responsable_AIA), '') <> ''
       AND COALESCE(TRIM(ps.Actividad), '') <> ''
     ORDER BY ps.project_id, ps.Semana, ps.Consecutivo
     LIMIT 1",
)->fetch(PDO::FETCH_ASSOC));
if (!$radarContext) {
    $failures[] = 'no operational context available to verify radar filters';
} else {
    $stage = substr(trim(strip_tags(html_entity_decode((string) $radarContext['Actividad'], ENT_QUOTES | ENT_HTML5, 'UTF-8'))), 0, 12);
    $radarFilters = [
        'sub' => (string) $radarContext['Sub_Contratista'],
        'resp' => (string) $radarContext['Responsable_AIA'],
        'etapa' => $stage,
    ];
    $radarDetail = $bi->getProgramaRadarDetail($scope([(int) $radarContext['project_id']], 'radar'), (string) $radarContext['Semana'], $radarFilters);
    foreach ($radarDetail['records'] ?? [] as $record) {
        // El contrato del filtro `etapa` en el radar es actividad O ubicación (igual que en el
        // detalle CNP de arriba); espejarlo solo contra la actividad daba falsos rojos.
        $matchesStage = str_contains(strtolower((string) $record['activity']), strtolower($stage))
            || str_contains(strtolower((string) ($record['location'] ?? '')), strtolower($stage));
        if (
            (int) ($record['semana'] ?? 0) !== (int) $radarContext['Semana']
            || !str_contains(strtolower((string) $record['subcontractor']), strtolower($radarFilters['sub']))
            || !str_contains(strtolower((string) $record['responsible']), strtolower($radarFilters['resp']))
            || !$matchesStage
        ) {
            $failures[] = 'radar detail ignored semana/sub/responsable/etapa filter';
            break;
        }
    }
}

$rangeRadarExpectedStatement = $db->queryForProjects(
    $scope($rangeProjects, 'range-radar-oracle'),
    "SELECT COUNT(*)
     FROM programacion_semanal ps
     WHERE ps.project_id IN (?, ?)
       AND ps.Activa IN ('1', 'NA')
       AND EXISTS (
           SELECT 1 FROM semanas_activas sa
           WHERE sa.project_id IN (?, ?)
             AND sa.project_id = ps.project_id
             AND sa.Semana = ps.Semana
             AND sa.Fecha_Inicio_Sem <= ?
             AND sa.Fecha_Fin_Sem >= ?
       )",
    [
        $rangeProjects[0],
        $rangeProjects[1],
        $rangeProjects[0],
        $rangeProjects[1],
        $rangeFilters['hasta'],
        $rangeFilters['desde'],
    ],
);
$rangeRadarExpected = $rangeRadarExpectedStatement->fetchColumn();
$rangeRadar = $bi->getProgramaRadarDetail($scope($rangeProjects, 'range-radar'), '', $rangeFilters, 'productividad', 100, 0);
if ((int) ($rangeRadar['summary']['total_population'] ?? -1) !== (int) $rangeRadarExpected) {
    $failures[] = 'radar detail did not apply the multi-project date range to operational rows';
}
$expectedReturned = min(100, (int) $rangeRadarExpected);
if (count($rangeRadar['records'] ?? []) !== $expectedReturned) {
    $failures[] = 'radar detail did not enforce its bounded page size';
}
if ((bool) ($rangeRadar['pagination']['has_more'] ?? false) !== ((int) $rangeRadarExpected > $expectedReturned)) {
    $failures[] = 'radar detail pagination does not report remaining active commitments';
}

$cncNa = $globalRead(static fn() => $db->query(
    "SELECT project_id, Semana, Consecutivo FROM programacion_semanal
     WHERE project_id IN ({$fixtureProjectsSql})
       AND Activa = 'NA' AND COALESCE(TRIM(CNC), '') <> '' LIMIT 1",
)->fetch(PDO::FETCH_ASSOC));
if (!$cncNa) {
    $failures[] = 'no CNC NA context available to verify inclusion';
} else {
    $cncDetail = $bi->getProgramaCncDetail($scope([(int) $cncNa['project_id']], 'cnc-na'), (string) $cncNa['Semana']);
    $included = array_filter($cncDetail['activities'] ?? [], static fn(array $row): bool => (int) $row['consecutivo'] === (int) $cncNa['Consecutivo']);
    if (!$included) $failures[] = 'CNC detail excluded an Activa=NA row with CNC cause';
}

$excludedCnp = $globalRead(static fn() => $db->query(
    "SELECT project_id, Semana, Consecutivo FROM programacion_semanal
     WHERE project_id IN ({$fixtureProjectsSql})
       AND Activa IN ('1', 'NA') AND COALESCE(TRIM(CNP), '') <> '' LIMIT 1",
)->fetch(PDO::FETCH_ASSOC));
if (!$excludedCnp) {
    // El fixture siembra 'CI.CNP.STALE.A' justo para este escenario: si no aparece, el chequeo
    // quedaría vacío en silencio.
    $failures[] = 'no active row with stale CNP available to verify causal universe exclusion';
} else {
    $detail = $bi->getProgramaCnpDetail($scope([(int) $excludedCnp['project_id']], 'excluded-cnp'), (string) $excludedCnp['Semana']);
    foreach ($detail['activities'] ?? [] as $activity) {
        if ((int) $activity['consecutivo'] === (int) $excludedCnp['Consecutivo']) {
            $failures[] = 'CNP detail included an active row that is outside its causal universe'; break;
        }
    }
}

if ($failures) {
    foreach ($failures as $failure) {
        echo "FAIL: {$failure}\n";
    }
    exit(1);
}

echo "PASS: BI chart filters match SQL for project {$projectId}, semana {$semana}, sub {$filters['sub']}, resp {$filters['resp']}\n";
echo "PASS: BI multi-project date range and filter options are backed by real data\n";
echo "PASS: Radar detail applies semana, range, subcontratista, responsable y etapa to operational rows\n";
