<?php

declare(strict_types=1);
// @requiere: db


require_once __DIR__ . '/../vendor/autoload.php';

use App\Services\Bi\ActionRecommendationService;
use App\Services\Bi\RiskScoringService;
use App\Security\DataScope\MultiProjectScope;
use App\Security\DataScope\ProjectScope;
use App\Security\DataScope\SystemScopeRunner;

function governanceScope(array $ids, string $case): MultiProjectScope
{
    return new MultiProjectScope($ids, 'fixture-bi-governance', 'R', 'test:test_bi_multi_project_governance:' . $case);
}

function governanceGlobalRead(\Database $db, string $case, callable $read): mixed
{
    return (new SystemScopeRunner($db->dataScope()))->run(
        'test:test_bi_multi_project_governance:discovery:' . $case,
        $read,
    );
}

function governanceProjectRead(\Database $db, int $projectId, callable $read): mixed
{
    $db->dataScope()->bind(new ProjectScope($projectId, 'fixture-bi-governance', 'R'));
    try {
        return $read();
    } finally {
        $db->dataScope()->clear();
    }
}

$failures = [];
$db = \Database::getInstance();

$pair = governanceGlobalRead($db, 'pair', static fn() => $db->query(
    "SELECT a.project_id AS first_project_id, a.Semana AS first_week,
            b.project_id AS second_project_id,
            COALESCE(a.Fecha_Fin_Sem, a.Fecha_Inicio_Sem) AS first_cutoff,
            COALESCE(b.Fecha_Fin_Sem, b.Fecha_Inicio_Sem) AS second_cutoff
     FROM semanas_activas a
     INNER JOIN semanas_activas b
        ON a.project_id < b.project_id
       AND ABS(DATEDIFF(
            COALESCE(a.Fecha_Fin_Sem, a.Fecha_Inicio_Sem),
            COALESCE(b.Fecha_Fin_Sem, b.Fecha_Inicio_Sem)
       )) <= 31
     WHERE COALESCE(a.Fecha_Fin_Sem, a.Fecha_Inicio_Sem) IS NOT NULL
       AND COALESCE(b.Fecha_Fin_Sem, b.Fecha_Inicio_Sem) IS NOT NULL
       AND COALESCE(a.Fecha_Fin_Sem, a.Fecha_Inicio_Sem)
           <> COALESCE(b.Fecha_Fin_Sem, b.Fecha_Inicio_Sem)
       AND EXISTS (
           SELECT 1
           FROM bi_riesgos first_risk
           WHERE first_risk.project_id = a.project_id
             AND DATE(first_risk.computed_at) = COALESCE(a.Fecha_Fin_Sem, a.Fecha_Inicio_Sem)
       )
       AND EXISTS (
           SELECT 1
           FROM bi_riesgos second_risk
           WHERE second_risk.project_id = b.project_id
             AND DATE(second_risk.computed_at) = COALESCE(b.Fecha_Fin_Sem, b.Fecha_Inicio_Sem)
       )
     ORDER BY a.project_id, b.project_id, a.Semana DESC, b.Semana DESC
     LIMIT 1"
)->fetch(\PDO::FETCH_ASSOC));

if ($pair === false) {
    $riskService = new RiskScoringService();
    $context = governanceGlobalRead($db, 'fallback-activity', static fn() => $db->query(
        "SELECT r.project_id, r.Semana, pc.Sub_Contratista, pc.Responsable_AIA,
                COALESCE(NULLIF(pc.Estado, ''), pc.Actividad) AS etapa
         FROM bi_riesgos r INNER JOIN programa_consolidado pc
            ON pc.project_id = r.project_id AND pc.Semana = r.Semana
           AND pc.Consecutivo_en_Programa = r.entity_id
         WHERE r.risk_type = 'actividad' AND COALESCE(pc.Sub_Contratista, '') <> ''
           AND COALESCE(pc.Responsable_AIA, '') <> ''
           AND COALESCE(NULLIF(pc.Estado, ''), pc.Actividad) <> '' LIMIT 1",
    )->fetch(\PDO::FETCH_ASSOC));
    if ($context === false) {
        $failures[] = 'missing real activity risk context fixture';
    } else {
        $filters = ['sub' => $context['Sub_Contratista'], 'resp' => $context['Responsable_AIA'], 'etapa' => $context['etapa']];
        $matchesActivity = $db->prepare(
            "SELECT 1 FROM programa_consolidado WHERE project_id = ? AND Semana = ?
               AND Consecutivo_en_Programa = ? AND LOWER(COALESCE(Sub_Contratista, '')) LIKE ?
               AND LOWER(COALESCE(Responsable_AIA, '')) LIKE ?
               AND (LOWER(COALESCE(Actividad, '')) LIKE ? OR LOWER(COALESCE(Estado, '')) LIKE ?)",
        );
        foreach ($riskService->getTopRisks(governanceScope([(int) $context['project_id']], 'fallback-activity'), 'programa-general', (string) $context['Semana'], 100, $filters) as $risk) {
            $matches = governanceProjectRead($db, (int) $context['project_id'], static function () use ($matchesActivity, $context, $risk, $filters): mixed {
                $matchesActivity->execute([
                    $context['project_id'], $context['Semana'], $risk['entity_id'],
                    '%' . strtolower($filters['sub']) . '%', '%' . strtolower($filters['resp']) . '%',
                    '%' . strtolower($filters['etapa']) . '%', '%' . strtolower($filters['etapa']) . '%',
                ]);
                return $matchesActivity->fetchColumn();
            });
            if ($matches === false) {
                $failures[] = 'activity risks escaped their contextual filters';
                break;
            }
        }
    }
    $unsupportedContext = ['resp' => '__risk-context-without-source__'];
    foreach (['cic' => 'contratista'] as $reportKey => $riskType) {
        $risk = governanceGlobalRead($db, 'fallback-' . $reportKey, static fn() => $db->query("SELECT project_id, Semana FROM bi_riesgos WHERE risk_type = '{$riskType}' LIMIT 1")->fetch(\PDO::FETCH_ASSOC));
        if ($risk !== false && $riskService->getTopRisks(governanceScope([(int) $risk['project_id']], 'fallback-' . $reportKey), $reportKey, (string) $risk['Semana'], 100, $unsupportedContext) !== []) {
            $failures[] = "{$riskType} risks ignored an active unsupported context filter";
        }
    }
    if ($failures !== []) {
        foreach (array_unique($failures) as $failure) {
            echo "FAIL: {$failure}\n";
        }
        exit(1);
    }
    echo "PASS: BI risks respect contextual filters without a multi-project date fixture\n";
    exit(0);
}

$projectIds = [(int) $pair['first_project_id'], (int) $pair['second_project_id']];
$filters = [
    'desde' => min((string) $pair['first_cutoff'], (string) $pair['second_cutoff']),
    'hasta' => max((string) $pair['first_cutoff'], (string) $pair['second_cutoff']),
];

$cutoffStmt = $db->queryForProjects(
    governanceScope($projectIds, 'oracle-cutoffs'),
    'SELECT project_id, MAX(COALESCE(Fecha_Fin_Sem, Fecha_Inicio_Sem)) AS cutoff
     FROM semanas_activas
     WHERE project_id IN (?, ?)
       AND COALESCE(Fecha_Fin_Sem, Fecha_Inicio_Sem) BETWEEN ? AND ?
     GROUP BY project_id',
    [$projectIds[0], $projectIds[1], $filters['desde'], $filters['hasta']],
);
$cutoffs = [];
foreach ($cutoffStmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
    $cutoffs[(int) $row['project_id']] = (string) $row['cutoff'];
}

if (count($cutoffs) !== 2) {
    $failures[] = 'date-range fixture did not resolve an effective cutoff for each project';
}

$riskService = new RiskScoringService();
$unknownRisks = $riskService->getTopRisks(governanceScope([$projectIds[0]], 'unknown'), 'not-a-bi-report', (string) $pair['first_week']);
if ($unknownRisks !== []) {
    $failures[] = 'unknown report key returned risks instead of an empty list';
}

$overviewRisks = $riskService->getTopRisks(governanceScope([$projectIds[0]], 'overview-single'), 'overview', (string) $pair['first_week'], 100);
$overviewTypes = array_values(array_unique(array_column($overviewRisks, 'risk_type')));
$expectedTypesStmt = $db->prepare(
    'SELECT DISTINCT risk_type FROM bi_riesgos WHERE project_id = ? AND Semana = ?'
);
$expectedOverviewTypes = governanceProjectRead($db, $projectIds[0], static function () use ($expectedTypesStmt, $projectIds, $pair): array {
    $expectedTypesStmt->execute([$projectIds[0], $pair['first_week']]);
    return array_column($expectedTypesStmt->fetchAll(\PDO::FETCH_ASSOC), 'risk_type');
});
sort($overviewTypes);
sort($expectedOverviewTypes);
if ($overviewTypes !== $expectedOverviewTypes) {
    $failures[] = 'overview did not return every risk type for its project/week';
}

$rangeRisks = $riskService->getTopRisks(governanceScope($projectIds, 'overview-range'), 'overview', '', 100, $filters);
if ($rangeRisks === []) {
    $failures[] = 'overview date range returned no real risks';
}
foreach ($rangeRisks as $risk) {
    $riskProjectId = (int) ($risk['project_id'] ?? 0);
    $cutoff = substr((string) ($risk['computed_at'] ?? ''), 0, 10);
    if (!in_array($riskProjectId, $projectIds, true)) {
        $failures[] = 'range risk escaped the requested project scope';
    }
    if ($cutoff < $filters['desde'] || $cutoff > $filters['hasta']) {
        $failures[] = 'range risk ignored the effective cutoff filter';
    }
}

$assertRiskIds = static function (string $label, array $risks, array $expectedIds) use (&$failures): void {
    $actualIds = array_values(array_unique(array_map('strval', array_column($risks, 'entity_id'))));
    $expectedIds = array_values(array_unique(array_map('strval', $expectedIds)));
    sort($actualIds);
    sort($expectedIds);
    if ($actualIds !== $expectedIds) {
        $failures[] = "{$label} risks escaped their contextual filters";
    }
};

$activityContext = governanceGlobalRead($db, 'activity-context', static fn() => $db->query(
    "SELECT r.project_id, r.Semana, pc.Sub_Contratista, pc.Responsable_AIA,
            COALESCE(NULLIF(pc.Estado, ''), pc.Actividad) AS etapa
     FROM bi_riesgos r
     INNER JOIN programa_consolidado pc
        ON pc.project_id = r.project_id
       AND pc.Semana = r.Semana
       AND pc.Consecutivo_en_Programa = r.entity_id
     WHERE r.risk_type = 'actividad'
       AND COALESCE(pc.Sub_Contratista, '') <> ''
       AND COALESCE(pc.Responsable_AIA, '') <> ''
       AND COALESCE(NULLIF(pc.Estado, ''), pc.Actividad) <> ''
     LIMIT 1",
)->fetch(\PDO::FETCH_ASSOC));

if ($activityContext === false) {
    $failures[] = 'missing real activity risk context fixture';
} else {
    $activityFilters = [
        'sub' => $activityContext['Sub_Contratista'],
        'resp' => $activityContext['Responsable_AIA'],
        'etapa' => $activityContext['etapa'],
    ];
    $expectedActivity = $db->prepare(
        "SELECT r.entity_id FROM bi_riesgos r
         INNER JOIN programa_consolidado pc
            ON pc.project_id = r.project_id
           AND pc.Semana = r.Semana
           AND pc.Consecutivo_en_Programa = r.entity_id
         WHERE r.project_id = ? AND r.Semana = ? AND r.risk_type = 'actividad'
           AND LOWER(COALESCE(pc.Sub_Contratista, '')) LIKE ?
           AND LOWER(COALESCE(pc.Responsable_AIA, '')) LIKE ?
           AND (LOWER(COALESCE(pc.Actividad, '')) LIKE ? OR LOWER(COALESCE(pc.Estado, '')) LIKE ?)",
    );
    $likeFilters = array_map(static fn(string $value): string => '%' . strtolower($value) . '%', $activityFilters);
    $expectedActivityIds = governanceProjectRead($db, (int) $activityContext['project_id'], static function () use ($expectedActivity, $activityContext, $likeFilters): array {
        $expectedActivity->execute([
            $activityContext['project_id'], $activityContext['Semana'],
            $likeFilters['sub'], $likeFilters['resp'], $likeFilters['etapa'], $likeFilters['etapa'],
        ]);
        return $expectedActivity->fetchAll(\PDO::FETCH_COLUMN);
    });
    $actualActivity = $riskService->getTopRisks(
        governanceScope([(int) $activityContext['project_id']], 'activity'),
        'programa-general',
        (string) $activityContext['Semana'],
        100,
        $activityFilters,
    );
    $assertRiskIds('activity', $actualActivity, $expectedActivityIds);
}

$contractorContext = governanceGlobalRead($db, 'contractor-context', static fn() => $db->query(
    "SELECT r.project_id, r.Semana, cic.subcontratista,
            COALESCE(NULLIF(cic.alcance, ''), cic.tipo_proveedor) AS etapa
     FROM bi_riesgos r
     INNER JOIN bi_cic_contratistas cic
        ON cic.project_id = r.project_id AND cic.Semana = r.Semana
       AND CONVERT(cic.subcontratista USING utf8mb4) COLLATE utf8mb4_0900_ai_ci
           = CONVERT(r.entity_id USING utf8mb4) COLLATE utf8mb4_0900_ai_ci
     WHERE r.risk_type = 'contratista' AND COALESCE(cic.subcontratista, '') <> ''
       AND COALESCE(NULLIF(cic.alcance, ''), cic.tipo_proveedor) <> '' LIMIT 1",
)->fetch(\PDO::FETCH_ASSOC));

if ($contractorContext === false) {
    $failures[] = 'missing real contractor risk context fixture';
} else {
    $contractorFilters = ['sub' => $contractorContext['subcontratista'], 'etapa' => $contractorContext['etapa']];
    $expectedContractor = $db->prepare(
        "SELECT r.entity_id FROM bi_riesgos r INNER JOIN bi_cic_contratistas cic
            ON cic.project_id = r.project_id AND cic.Semana = r.Semana
           AND CONVERT(cic.subcontratista USING utf8mb4) COLLATE utf8mb4_0900_ai_ci
               = CONVERT(r.entity_id USING utf8mb4) COLLATE utf8mb4_0900_ai_ci
         WHERE r.project_id = ? AND r.Semana = ? AND r.risk_type = 'contratista'
           AND LOWER(COALESCE(cic.subcontratista, '')) LIKE ?
           AND (LOWER(COALESCE(cic.alcance, '')) LIKE ? OR LOWER(COALESCE(cic.tipo_proveedor, '')) LIKE ?)",
    );
    $contractorLike = array_map(static fn(string $value): string => '%' . strtolower($value) . '%', $contractorFilters);
    $expectedContractorIds = governanceProjectRead($db, (int) $contractorContext['project_id'], static function () use ($expectedContractor, $contractorContext, $contractorLike): array {
        $expectedContractor->execute([
            $contractorContext['project_id'], $contractorContext['Semana'],
            $contractorLike['sub'], $contractorLike['etapa'], $contractorLike['etapa'],
        ]);
        return $expectedContractor->fetchAll(\PDO::FETCH_COLUMN);
    });
    $actualContractor = $riskService->getTopRisks(
        governanceScope([(int) $contractorContext['project_id']], 'contractor'),
        'cic',
        (string) $contractorContext['Semana'],
        100,
        $contractorFilters,
    );
    $assertRiskIds('contractor', $actualContractor, $expectedContractorIds);
}

// La cobertura del contexto de riesgo 'pdc' se retiró el 2026-08-04: se apoyaba en la tabla
// `pdc` del PDC v1, eliminada, y `bi_riesgos` ya no emite filas de ese tipo.

$actions = (new ActionRecommendationService())->recommend(governanceScope($projectIds, 'actions'), 'programa-general', [
    [
        'project_id' => $projectIds[0],
        'Actividad' => 'Actividad crítica del proyecto uno',
        'is_critical_late' => 1,
    ],
    [
        'project_id' => $projectIds[1],
        'Actividad' => 'Actividad crítica del proyecto dos',
        'is_critical_late' => 1,
    ],
], '', $filters);

foreach ($actions as $action) {
    $projectId = (int) ($action['project_id'] ?? 0);
    $expectedDueDate = isset($cutoffs[$projectId])
        ? (new \DateTimeImmutable($cutoffs[$projectId]))->modify('+3 days')->format('Y-m-d')
        : null;

    if (!isset($cutoffs[$projectId])) {
        $failures[] = 'recommended action did not preserve the project_id from its source row';
    }
    if (($action['due_date'] ?? null) !== $expectedDueDate) {
        $failures[] = 'recommended action did not use its project effective cutoff for due_date';
    }
    if (($action['project_ids'] ?? null) !== $projectIds) {
        $failures[] = 'recommended action reduced the multi-project scope';
    }
}

if (count($actions) !== 2) {
    $failures[] = 'fixture did not produce one recommended action per project';
}

$consolidatedActions = (new ActionRecommendationService())->recommend(governanceScope($projectIds, 'consolidated-actions'), 'overview', [[
    'hard_restriction_blocked_count' => 1,
    'weekly_commitments_at_risk_count' => 0,
]], '', $filters);
$consolidated = $consolidatedActions[0] ?? [];
if (($consolidated['scope'] ?? null) !== 'consolidated'
    || !array_key_exists('project_id', $consolidated)
    || $consolidated['project_id'] !== null) {
    $failures[] = 'consolidated action does not declare its multi-project scope explicitly';
}
if (array_keys($consolidated['due_dates_by_project'] ?? []) !== $projectIds) {
    $failures[] = 'consolidated action does not expose an auditable due date for each project';
}

if ($failures !== []) {
    foreach (array_unique($failures) as $failure) {
        echo "FAIL: {$failure}\n";
    }
    exit(1);
}

echo "PASS: BI multi-project risks and actions respect report keys, ranges and project cutoffs\n";
