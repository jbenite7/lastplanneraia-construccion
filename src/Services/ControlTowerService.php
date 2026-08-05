<?php

declare(strict_types=1);

namespace App\Services;

use App\Services\Bi\RiskScoringService;
use App\Services\Bi\StorytellingService;
use App\Services\Bi\LineageService;
use App\Services\Bi\ForecastService;
use App\Services\Bi\ActionRecommendationService;

/**
 * BI Control Tower — Central orchestrator service.
 *
 * Consumes the 10+ bi_* SQL views and composes unified briefs
 * following the 7-section template:
 *   1. Executive Brief  2. Scorecard  3. Drivers
 *   4. Risk & Forecast   5. Action Queue  6. Drill-down  7. Lineage
 */
class ControlTowerService
{
    private \Database $db;
    private RiskScoringService $riskScoring;
    private StorytellingService $storytelling;
    private LineageService $lineage;
    private ForecastService $forecast;
    private ActionRecommendationService $actionRec;

    public function __construct()
    {
        $this->db = \Database::getInstance();
        $this->riskScoring = new RiskScoringService();
        $this->storytelling = new StorytellingService();
        $this->lineage = new LineageService();
        $this->forecast = new ForecastService();
        $this->actionRec = new ActionRecommendationService();
    }

    /**
     * Compose a full BI brief for a given report key and project/week.
     *
     * @param string $reportKey One of: overview, programa-general, intermedia,
     *                          semanal, pdc, cic, cip, curva-s
     * @param array|int $projectIds Single or multiple project IDs
     * @param string $semana     Week ID (integer, from semanas_activas.Semana)
     * @param string $role       Project role or MULTI for consolidated scope
     * @return array             Full 7-section brief
     */
    public function getBrief(string $reportKey, array|int $projectIds, string $semana, string $role = 'R', array $filters = []): array
    {
        $projectIds = is_array($projectIds) ? $projectIds : [$projectIds];
        $filters = $this->normalizeFilters($filters);
        $data = $this->fetchReportData($reportKey, $projectIds, $semana, $filters);
        $activitySnapshot = $reportKey === 'programa-general'
            ? $this->programaActivitySnapshot($this->fetchProgramaGeneralSnapshot($projectIds, $semana, $filters))
            : null;
        $scorecard = $this->composeScorecard($reportKey, $data);
        $charts = $this->composeCharts($reportKey, $data, $scorecard, $projectIds, $semana, $filters);
        if ($reportKey === 'programa-general') {
            $scorecard = $this->syncProgramaScorecardFromCharts($scorecard, $charts);
        }
        $drivers = $this->composeDrivers($reportKey, $data);
        $risks = $this->composeRisks($reportKey, $projectIds, $semana, $filters);
        $actions = $this->actionRec->recommend($reportKey, $data, $projectIds, $semana, $filters);
        $lineage = $this->lineage->getForReport($reportKey);

        $firstId = $projectIds[0];
        return [
            'respuesta'             => 'BIEN',
            'project_ids'           => $projectIds,
            'project_id'            => $firstId, // backward compat
            'semana'                => $semana,
            'report_key'            => $reportKey,
            'role'                  => $role,
            'filters'               => $this->describeFilters($semana, $filters),
            'data_source'           => $this->dataSourceForReport($reportKey),
            'raw_row_count'         => count($data),
            // Fase B3: avance por paso y carga por responsable. Solo para compras; el resto de
            // informes no los tiene y mandarlos en null evita que el front adivine.
            'pdc_breakdown'         => $reportKey === 'pdc' ? $this->pdcBreakdown($projectIds) : null,
            'pdc_items'             => $reportKey === 'pdc' ? $data : null,
            'activity_snapshot'     => $activitySnapshot,
            'executive_brief'       => $this->storytelling->composeExecutiveBrief($reportKey, $data, $role),
            'scorecard'             => $scorecard,
            'charts'                => $charts,
            'drivers'               => $drivers,
            'risks'                 => $risks,
            'recommended_actions'   => $actions,
            'lineage'               => $lineage,
        ];
    }

    public function getFilterOptions(array|int $projectIds, string $semana = '', array $filters = []): array
    {
        $projectIds = is_array($projectIds) ? $projectIds : [$projectIds];
        $filters = $this->normalizeFilters($filters);
        $scopeFilters = array_merge($filters, ['sub' => '', 'resp' => '', 'etapa' => '']);

        return [
            'subcontratistas' => $this->collectFilterValues($projectIds, $semana, $scopeFilters, [
                ['bi_pg_semana', 'pg', ['week' => 'Semana'], 'sub_contratista'],
                ['bi_pi_restricciones', 'pi', ['week' => 'Semana'], 'subcontractor'],
                ['bi_ps_compromisos', 'ps', ['week' => 'Semana'], 'subcontractor'],
                ['bi_cic_contratistas', 'cic', ['week' => 'Semana'], 'subcontratista'],
            ]),
            'responsables' => $this->collectFilterValues($projectIds, $semana, $scopeFilters, [
                ['bi_pg_semana', 'pg', ['week' => 'Semana'], 'responsable_aia'],
                ['bi_pi_restricciones', 'pi', ['week' => 'Semana'], 'responsible'],
                ['bi_ps_compromisos', 'ps', ['week' => 'Semana'], 'responsible'],
                ['bi_cip_responsables', 'cip', ['week' => 'Semana'], 'Responsable_AIA'],
            ]),
        ];
    }

    public function getProgramaComplianceDetail(array|int $projectIds, string $semana, array $filters = [], int $limit = 50): array
    {
        $projectIds = is_array($projectIds) ? $projectIds : [$projectIds];
        $filters = $this->normalizeFilters($filters);
        $trend = $this->fetchProgramaGeneralTrend($projectIds, $semana, $filters);
        $payload = $this->programaCompliancePayload($trend);

        return [
            'respuesta' => 'BIEN', 'report_key' => 'programa-general-compliance-detail',
            'project_ids' => $projectIds, 'semana' => $semana, 'filters' => $this->describeFilters($semana, $filters),
            'summary' => $payload['summary'],
            'explanation' => $payload['explanation'],
            'activities' => array_slice($payload['activities'], 0, max(1, min(100, $limit))),
        ];
    }

    public function getProgramaProgressDetail(
        array|int $projectIds,
        string $semana,
        array $filters = [],
        int $limit = 50,
        int $offset = 0,
        string $sort = 'all',
        bool $criticalOnly = false,
    ): array
    {
        $projectIds = is_array($projectIds) ? $projectIds : [$projectIds];
        $filters = $this->normalizeFilters($filters);
        $limit = max(1, min(100, $limit));
        $offset = max(0, $offset);
        $snapshot = $this->fetchProgramaGeneralSnapshot($projectIds, $semana, $filters);
        $payload = $this->programaProgressDetailPayload($snapshot);
        $sort = in_array($sort, ['all', 'missing', 'earned'], true) ? $sort : 'all';
        $allActivities = array_values(array_filter(
            $payload['activities'],
            static function (array $activity) use ($sort, $criticalOnly): bool {
                if ($criticalOnly && empty($activity['critical'])) return false;
                if ($sort === 'missing') return (float) ($activity['recoverable_pp'] ?? 0) > 0;
                if ($sort === 'earned') return (float) ($activity['real_contribution_pp'] ?? 0) > 0;
                return true;
            },
        ));
        if ($sort === 'earned') {
            usort($allActivities, static fn(array $left, array $right): int =>
                $right['real_contribution_pp'] <=> $left['real_contribution_pp']
                ?: $right['weight_pct'] <=> $left['weight_pct']
                ?: $left['activity_key'] <=> $right['activity_key']
            );
        }
        $total = count($allActivities);
        $activities = array_slice($allActivities, $offset, $limit);

        return [
            'respuesta' => 'BIEN', 'report_key' => 'programa-general-progress-detail',
            'project_ids' => $projectIds, 'semana' => $semana, 'filters' => $this->describeFilters($semana, $filters),
            'summary' => $payload['summary'], 'groups' => $this->programaProgressGroups($allActivities),
            'sort' => $sort, 'critical_only' => $criticalOnly,
            'metric_key' => 'pg_activity_progress_contribution',
            'source_relations' => ['programa_consolidado', 'semanas_activas'],
            'grain' => 'project_id + Semana + unique_id',
            'activities' => $activities,
            'pagination' => [
                'limit' => $limit,
                'offset' => $offset,
                'total' => $total,
                'returned_count' => count($activities),
                'has_more' => $offset + count($activities) < $total,
                'next_offset' => $offset + count($activities),
            ],
        ];
    }

    public function getProgramaDelayDetail(
        array|int $projectIds,
        string $semana,
        array $filters = [],
        int $limit = 50,
        int $offset = 0,
    ): array {
        $projectIds = is_array($projectIds) ? $projectIds : [$projectIds];
        $filters = $this->normalizeFilters($filters);
        $limit = max(1, min(100, $limit));
        $offset = max(0, $offset);
        $trend = $this->fetchProgramaGeneralForecastTrend($projectIds, $semana, $filters);
        $context = $this->programaCurveContext($trend);
        $progress = $this->programaProgressSeries($trend, $context);
        $contractualBaseline = $this->programaContractualBaselineForCurrentCohort($projectIds, $context);
        $forecast = $this->programaDelayForecast(
            $trend,
            $context,
            $progress,
            $projectIds,
            $semana,
            $filters,
            $contractualBaseline,
        );
        $observed = $this->programaObservedDelayPayload($context);
        $activities = $observed['activities'];
        $projectNames = $this->programaProjectNames($context['baseline'] ?? []);
        $projectBreakdown = array_map(static function (array $project) use ($projectNames): array {
            $projectId = (int) ($project['project_id'] ?? 0);
            $project['project'] = (string) ($projectNames[$projectId] ?? "Proyecto {$projectId}");
            return $project;
        }, $forecast['metrics']['project_breakdown'] ?? []);

        return [
            'respuesta' => 'BIEN',
            'report_key' => 'programa-general-delay-detail',
            'metric_keys' => ['pg_finish_variance_days_p50', 'pg_observed_activity_delay_days'],
            'project_ids' => $projectIds,
            'semana' => $semana,
            'filters' => $this->describeFilters($semana, $filters),
            'forecast' => array_merge($forecast['metrics'], ['project_breakdown' => $projectBreakdown]),
            'observed' => $observed['summary'],
            'pagination' => [
                'limit' => $limit,
                'offset' => $offset,
                'returned_count' => min($limit, max(0, count($activities) - $offset)),
                'total' => count($activities),
                'has_more' => $offset + $limit < count($activities),
            ],
            'activities' => array_slice($activities, $offset, $limit),
        ];
    }

    public function getProgramaRadarDetail(
        array|int $projectIds,
        string $semana,
        array $filters = [],
        string $axis = 'productividad',
        int $limit = 50,
        int $offset = 0,
    ): array
    {
        $projectIds = is_array($projectIds) ? $projectIds : [$projectIds];
        $filters = $this->normalizeFilters($filters);
        $axis = in_array($axis, ['productividad', 'eficiencia', 'desempeno'], true) ? $axis : 'productividad';
        $limit = max(1, min(100, $limit));
        $offset = max(0, $offset);
        $rows = $this->fetchProgramaRadarPopulation($projectIds, $semana, $filters);
        $radar = $this->programaRadar($rows);
        $eligible = 0;
        foreach ($rows as $row) {
            $record = $this->programaRadarRecord($row);
            if ((bool) ($record['eligibility'][$axis]['eligible'] ?? false)) {
                $eligible++;
            }
        }
        $total = count($rows);
        $records = array_map(
            fn(array $row): array => $this->programaRadarRecord($row),
            array_slice($rows, $offset, $limit),
        );
        $axisSummary = $radar['axes'][$axis] ?? [];

        return [
            'respuesta' => 'BIEN',
            'report_key' => 'programa-general-radar-detail',
            'project_ids' => $projectIds,
            'semana' => $semana,
            'axis' => $axis,
            'filters' => $this->describeFilters($semana, $filters),
            'summary' => $axisSummary + [
                'axis' => $axis,
                'total_population' => $total,
                'eligible_count' => $eligible,
                'excluded_count' => $total - $eligible,
            ],
            'pagination' => [
                'limit' => $limit,
                'offset' => $offset,
                'returned_count' => count($records),
                'next_offset' => min($total, $offset + count($records)),
                'has_more' => $offset + count($records) < $total,
            ],
            'records' => $records,
        ];
    }

    public function getProgramaCnpDetail(
        array|int $projectIds,
        string $semana,
        array $filters = [],
        string $category = '',
        int $limit = 100,
        int $offset = 0,
        bool $includeSummary = true,
    ): array
    {
        return $this->getProgramaCausalDetail('cnp', $projectIds, $semana, $filters, $category, $limit, $offset, $includeSummary);
    }

    public function getProgramaCncDetail(
        array|int $projectIds,
        string $semana,
        array $filters = [],
        string $category = '',
        int $limit = 100,
        int $offset = 0,
        bool $includeSummary = true,
    ): array
    {
        return $this->getProgramaCausalDetail('cnc', $projectIds, $semana, $filters, $category, $limit, $offset, $includeSummary);
    }

    // -----------------------------------------------------------------
    // Data fetching — one method per report key
    // -----------------------------------------------------------------

    private function fetchReportData(string $reportKey, array $projectIds, string $semana, array $filters): array
    {
        return match ($reportKey) {
            'overview'            => $this->fetchOverview($projectIds, $semana, $filters),
            'programa-general'    => $this->fetchProgramaGeneral($projectIds, $semana, $filters),
            'intermedia'          => $this->fetchIntermedia($projectIds, $semana, $filters),
            'semanal'             => $this->fetchSemanal($projectIds, $semana, $filters),
            'pdc'                 => $this->fetchPdc($projectIds, $semana, $filters),
            'cic'                 => $this->fetchCic($projectIds, $semana, $filters),
            'cip'                 => $this->fetchCip($projectIds, $semana, $filters),
            'curva-s'             => $this->fetchCurvaS($projectIds, $semana, $filters),
            default               => [],
        };
    }

    private function fetchOverview(array $projectIds, string $semana, array $filters): array
    {
        $pg = $this->fetchProgramaGeneral($projectIds, $semana, $filters);
        $ps = $this->fetchSemanal($projectIds, $semana, $filters);
        $pdc = $this->fetchPdc($projectIds, $semana, $filters);
        $cic = $this->fetchCic($projectIds, $semana, $filters);
        $cip = $this->fetchCip($projectIds, $semana, $filters);

        return [[
            'activities_to_do_count' => count(array_filter($pg, fn($r) => ($r['is_lookahead_window'] ?? 0) == 1)),
            'activities_can_do_count' => count(array_filter($pg, fn($r) => ($r['is_lookahead_window'] ?? 0) == 1 && ($r['hard_restrictions_ready'] ?? 0) == 1)),
            'activities_will_do_count' => count(array_filter($ps, fn($r) => ($r['is_TNP'] ?? 0) == 0)),
            'critical_late_count' => count(array_filter($pg, fn($r) => ($r['is_critical_late'] ?? 0) == 1)),
            'hard_restriction_blocked_count' => count(array_filter($pg, fn($r) => ($r['is_lookahead_window'] ?? 0) == 1 && ($r['hard_restrictions_ready'] ?? 0) == 0)),
            'weekly_commitments_count' => count($ps),
            'weekly_commitments_at_risk_count' => count(array_filter($ps, fn($r) => ($r['fulfillment_alert'] ?? 0) == 1)),
            // Fase B3: la fila de compras ya no es la del PDC viejo (no tiene listo_para_iniciar).
            // Dejarlo como estaba habria devuelto 0 en silencio, que es peor que un error.
            'pdc_at_risk_count' => array_sum(array_map(fn($r) => (int) ($r['vencidos'] ?? 0), $pdc)),
            'contractors_at_risk_count' => count(array_filter($cic, fn($r) => ($r['alert_contractor_future_risk'] ?? 0) == 1)),
            'responsibles_at_risk_count' => count(array_filter($cip, fn($r) => ($r['fulfillment_alert'] ?? 0) == 1)),
            'pdc_items' => $pdc,
        ]];
    }

    private function fetchProgramaGeneral(array $projectIds, string $semana, array $filters): array
    {
        [$where, $params] = $this->buildFilteredWhere($projectIds, $semana, $filters, 'pc', [
            'week' => 'Semana', 'sub' => 'Sub_Contratista', 'resp' => 'Responsable_AIA', 'etapa' => ['Actividad', 'Estado'],
        ]);

        return $this->queryAll(
            $this->programaGeneralDirectSelect() . " WHERE {$where}
                 AND COALESCE(pc.Titulo, 0) = 0
             ORDER BY pc.Semana, pc.Consecutivo_en_Programa",
            $params,
        );
    }

    private function fetchIntermedia(array $projectIds, string $semana, array $filters): array
    {
        [$where, $params] = $this->buildFilteredWhere($projectIds, $semana, $filters, 'pi', [
            'week' => 'Semana', 'sub' => 'subcontractor', 'resp' => 'responsible', 'etapa' => ['Actividad', 'Estado'],
        ]);
        return $this->queryAll("SELECT * FROM bi_pi_restricciones pi WHERE {$where}", $params);
    }

    private function fetchSemanal(array $projectIds, string $semana, array $filters): array
    {
        [$where, $params] = $this->buildFilteredWhere($projectIds, $semana, $filters, 'ps', [
            'week' => 'Semana', 'sub' => 'Sub_Contratista', 'resp' => 'Responsable_AIA', 'etapa' => ['Actividad', 'Ubicacion'],
        ]);
        return $this->queryAll(
            "SELECT ps.*, ps.Sub_Contratista AS subcontractor, ps.Responsable_AIA AS responsible
             FROM programacion_semanal ps
             WHERE {$where} AND ps.Activa IN ('1', 'NA')",
            $params,
        );
    }

    private function fetchProgramaRadarPopulation(array $projectIds, string $semana, array $filters): array
    {
        [$where, $params] = $this->buildFilteredWhere($projectIds, $semana, $filters, 'ps', [
            'week' => 'Semana', 'sub' => 'Sub_Contratista', 'resp' => 'Responsable_AIA', 'etapa' => ['Actividad', 'Ubicacion'],
        ]);

        return $this->queryAll(
            "SELECT ps.*, COALESCE(NULLIF(gpp.Proyecto_Proceso, ''), CONCAT('Proyecto ', ps.project_id)) AS project,
                    COALESCE(sa.Fecha_Fin_Sem, sa.Fecha_Inicio_Sem) AS cutoff
             FROM programacion_semanal ps
             LEFT JOIN general_proyectos_procesos gpp ON gpp.Id = ps.project_id
             LEFT JOIN semanas_activas sa ON sa.project_id = ps.project_id AND sa.Semana = ps.Semana
             WHERE {$where} AND ps.Activa IN ('1', 'NA')
             ORDER BY ps.project_id, ps.Semana, ps.Consecutivo, ps.row_id",
            $params,
        );
    }

    private function fetchProgramaCausalPopulation(
        array $projectIds,
        string $semana,
        array $filters,
        string $kind = '',
        string $category = '',
        ?int $limit = null,
        int $offset = 0,
    ): array
    {
        [$where, $params] = $this->buildFilteredWhere($projectIds, $semana, $filters, 'ps', [
            'week' => 'Semana', 'sub' => 'Sub_Contratista', 'resp' => 'Responsable_AIA', 'etapa' => ['Actividad', 'Ubicacion'],
        ]);
        $populationPredicate = match ($kind) {
            'cnp' => "ps.Activa = '0' AND COALESCE(TRIM(ps.CNP), '') <> ''",
            'cnc' => "ps.Activa IN ('1', 'NA') AND COALESCE(TRIM(ps.CNC), '') <> ''",
            default => "ps.Activa IN ('0', '1', 'NA')",
        };
        $categoryPredicate = $this->causalCategorySqlPredicate($kind, $category, $params);
        $limitClause = $limit === null ? '' : sprintf(' LIMIT %d OFFSET %d', max(1, min(100, $limit)), max(0, $offset));
        return $this->queryAll(
            "SELECT ps.*, COALESCE(NULLIF(gpp.Proyecto_Proceso, ''), CONCAT('Proyecto ', ps.project_id)) AS project,
                    ps.Sub_Contratista AS subcontractor, ps.Responsable_AIA AS responsible,
                    COALESCE(sa.Fecha_Fin_Sem, sa.Fecha_Inicio_Sem) AS cutoff,
                    CASE WHEN ps.Activa = '0' AND COALESCE(TRIM(ps.CNP), '') <> '' THEN 1 ELSE 0 END AS is_cnp_population,
                    CASE WHEN ps.Activa IN ('1', 'NA') AND COALESCE(TRIM(ps.CNC), '') <> '' THEN 1 ELSE 0 END AS is_cnc_population
             FROM programacion_semanal ps
             LEFT JOIN general_proyectos_procesos gpp ON gpp.Id = ps.project_id
             LEFT JOIN semanas_activas sa ON sa.project_id = ps.project_id AND sa.Semana = ps.Semana
             WHERE {$where} AND {$populationPredicate}{$categoryPredicate}
             ORDER BY ps.project_id, ps.Semana, ps.Consecutivo, ps.row_id{$limitClause}",
            $params,
        );
    }

    private function countProgramaCausalPopulation(array $projectIds, string $semana, array $filters, string $kind, string $category = ''): int
    {
        [$where, $params] = $this->buildFilteredWhere($projectIds, $semana, $filters, 'ps', [
            'week' => 'Semana', 'sub' => 'Sub_Contratista', 'resp' => 'Responsable_AIA', 'etapa' => ['Actividad', 'Ubicacion'],
        ]);
        $populationPredicate = $kind === 'cnp'
            ? "ps.Activa = '0' AND COALESCE(TRIM(ps.CNP), '') <> ''"
            : "ps.Activa IN ('1', 'NA') AND COALESCE(TRIM(ps.CNC), '') <> ''";
        $categoryPredicate = $this->causalCategorySqlPredicate($kind, $category, $params);
        $rows = $this->query(
            "SELECT COUNT(DISTINCT ps.project_id, ps.Semana, ps.Consecutivo) AS total
             FROM programacion_semanal ps
             WHERE {$where} AND {$populationPredicate}{$categoryPredicate}",
            $params,
        );

        return max(0, (int) ($rows[0]['total'] ?? 0));
    }

    private function getProgramaCausalDetail(
        string $kind,
        array|int $projectIds,
        string $semana,
        array $filters,
        string $category,
        int $limit,
        int $offset,
        bool $includeSummary,
    ): array
    {
        $projectIds = is_array($projectIds) ? $projectIds : [$projectIds];
        $filters = $this->normalizeFilters($filters);
        $limit = max(1, min(100, $limit));
        $offset = max(0, $offset);
        $category = trim($category);
        if ($includeSummary) {
            $rows = $this->fetchProgramaCausalPopulation($projectIds, $semana, $filters, $kind, $category);
            $payload = $this->programaCausalDetailPayload($rows, $kind, $category);
            $total = count($payload['activities']);
            $activities = array_slice($payload['activities'], $offset, $limit);
            $summary = $payload['summary'];
        } else {
            $total = $this->countProgramaCausalPopulation($projectIds, $semana, $filters, $kind, $category);
            $rows = $this->fetchProgramaCausalPopulation($projectIds, $semana, $filters, $kind, $category, $limit, $offset);
            $payload = $this->programaCausalDetailPayload($rows, $kind, $category);
            $activities = $payload['activities'];
            $summary = ['total' => $total];
        }
        $empty = $total === 0;

        return [
            'respuesta' => 'BIEN', 'report_key' => "programa-general-{$kind}-detail",
            'project_ids' => $projectIds, 'semana' => $semana,
            'filters' => $this->describeFilters($semana, $filters), 'category' => $category,
            'summary' => $summary, 'empty' => $empty,
            'empty_reason' => $empty ? 'No hay causas para los filtros seleccionados.' : null,
            'pagination' => [
                'limit' => $limit,
                'offset' => $offset,
                'total' => $total,
                'returned_count' => count($activities),
                'next_offset' => min($total, $offset + count($activities)),
                'has_more' => $offset + count($activities) < $total,
            ],
            'activities' => $activities,
        ];
    }

    /**
     * Fase B3: el informe de compras se alimenta del PDC v2, no de bi_pdc_general (PDC viejo).
     *
     * El parámetro $semana se acepta y se IGNORA a propósito (Decisión 5 del spec): los
     * vencimientos se calculan contra hoy, con la fecha puesta por el servidor, para que este
     * panel y la pestaña del módulo no puedan discrepar el mismo día. El rótulo de la tarjeta
     * lo dice, para que no se lea como un fallo.
     */
    private function fetchPdc(array $projectIds, string $semana, array $filters): array
    {
        $seguimiento = new \App\Services\Pdc\SeguimientoService($this->db);
        $paquetes    = new \App\Services\Pdc\PaquetesService($this->db);

        $agg = $seguimiento->vencimientosAgregados($projectIds);
        $nombres = $this->nombresDeProyecto($projectIds);

        $filas = [];
        foreach ($projectIds as $pid) {
            $pid = (int) $pid;
            $obra = $agg['por_obra'][$pid] ?? ['conteos' => [], 'destinos' => 0, 'pasos' => 0];
            $c = $obra['conteos'];
            $resumen = $paquetes->resumen($pid) ?? [];

            $filas[] = [
                'project_id'      => $pid,
                'obra'            => $nombres[$pid] ?? ('Obra ' . $pid),
                'cobertura'       => (float) ($resumen['cobertura'] ?? 0.0),
                'cobertura_valor' => (float) ($resumen['coberturaValor'] ?? 0.0),
                'vencidos'        => (int) ($c['vencido'] ?? 0),
                'en_riesgo'       => (int) ($c['sem1'] ?? 0) + (int) ($c['sem2'] ?? 0) + (int) ($c['sem3'] ?? 0),
                'destinos'        => (int) $obra['destinos'],
                'pasos'           => (int) $obra['pasos'],
                'sin_mirar'       => count($seguimiento->paquetesDesactualizados($pid)),
                'hoy'             => $agg['hoy'],
            ];
        }

        return $filas;
    }

    /**
     * Avance por paso y carga por responsable, para el panel de compras (fase B3).
     *
     * @param int[] $projectIds
     * @return array{por_paso:array<string,array{pendientes:int,vencidos:int}>,por_responsable:list<array{nombre:string,pendientes:int,vencidos:int}>}
     */
    private function pdcBreakdown(array $projectIds): array
    {
        $agg = (new \App\Services\Pdc\SeguimientoService($this->db))->vencimientosAgregados($projectIds);

        return [
            'por_paso' => $agg['por_paso'],
            // Se reindexa porque las claves son ids de usuario y el JSON las convertiría en un
            // objeto con huecos; al front le sirve una lista ya ordenada.
            'por_responsable' => array_values($agg['por_responsable']),
        ];
    }

    /**
     * @param int[] $projectIds
     * @return array<int,string>
     */
    private function nombresDeProyecto(array $projectIds): array
    {
        $ids = array_values(array_unique(array_map('intval', $projectIds)));
        if ($ids === []) {
            return [];
        }

        $ph = implode(',', array_fill(0, count($ids), '?'));
        $rows = $this->queryAll(
            "SELECT ID, Proyecto_Proceso FROM general_proyectos_procesos WHERE ID IN ({$ph})",
            $ids,
        );

        $out = [];
        foreach ($rows as $r) {
            $out[(int) $r['ID']] = (string) $r['Proyecto_Proceso'];
        }

        return $out;
    }

    private function fetchCic(array $projectIds, string $semana, array $filters): array
    {
        [$where, $params] = $this->buildFilteredWhere($projectIds, $semana, $filters, 'cic', [
            'week' => 'Semana', 'sub' => 'subcontratista', 'etapa' => ['alcance', 'tipo_proveedor'],
        ]);
        return $this->queryAll("SELECT * FROM bi_cic_contratistas cic WHERE {$where}", $params);
    }

    private function fetchCip(array $projectIds, string $semana, array $filters): array
    {
        [$where, $params] = $this->buildFilteredWhere($projectIds, $semana, $filters, 'cip', [
            'week' => 'Semana', 'resp' => 'Responsable_AIA',
        ]);
        return $this->queryAll("SELECT * FROM bi_cip_responsables cip WHERE {$where}", $params);
    }

    private function fetchCurvaS(array $projectIds, string $semana, array $filters): array
    {
        [$where, $params] = $this->buildFilteredWhere($projectIds, $semana, $filters, 'pc', [
            'week' => 'Semana', 'sub' => 'Sub_Contratista', 'resp' => 'Responsable_AIA', 'etapa' => ['Actividad', 'Estado'],
        ]);

        return $this->query($this->curvaSFilteredSql($where), $params);
    }

    // -----------------------------------------------------------------
    // Section composers
    // -----------------------------------------------------------------

    private function composeScorecard(string $reportKey, array $data): array
    {
        $scorecard = match ($reportKey) {
            'overview' => $this->scorecardOverview($data),
            'programa-general' => $this->scorecardPG($data),
            'intermedia' => $this->scorecardPI($data),
            'semanal' => $this->scorecardPS($data),
            'pdc' => $this->scorecardPDC($data),
            'cic' => $this->scorecardCIC($data),
            'cip' => $this->scorecardCIP($data),
            'curva-s' => $this->scorecardCurvaS($data),
            default => [],
        };

        // Return only top 8
        return array_slice($scorecard, 0, 8);
    }

    private function scorecardOverview(array $data): array
    {
        $s = $data[0] ?? [];
        return [
            $this->kpi('¿Qué hacer?', (int) ($s['activities_to_do_count'] ?? 0), 'count', 'Priorizar frentes'),
            $this->kpi('¿Podemos?', (int) ($s['activities_can_do_count'] ?? 0), 'count', 'Actividades listas'),
            $this->kpi('¿Se hará?', (int) ($s['activities_will_do_count'] ?? 0), 'count', 'Compromisos activos'),
            $this->kpi('Críticas atrasadas', (int) ($s['critical_late_count'] ?? 0), 'count', 'Escalar'),
            $this->kpi('Bloqueadas (restricciones)', (int) ($s['hard_restriction_blocked_count'] ?? 0), 'count', 'Liberar'),
            $this->kpi('Compromisos en riesgo', (int) ($s['weekly_commitments_at_risk_count'] ?? 0), 'count', 'Revisar'),
            $this->kpi('PDC en riesgo', (int) ($s['pdc_at_risk_count'] ?? 0), 'count', 'Revisar compras'),
            $this->kpi('Contratistas en alerta', (int) ($s['contractors_at_risk_count'] ?? 0), 'count', 'Intervenir'),
        ];
    }

    private function scorecardPG(array $data): array
    {
        $criticalLate = count(array_filter($data, fn($r) => ($r['is_critical_late'] ?? 0) == 1));
        $totalDuration = 0.0;
        $weightedReal = 0.0;
        $weightedTheoretical = 0.0;
        $fallbackReal = [];
        $fallbackTheoretical = [];

        foreach ($data as $row) {
            $duration = max(0.0, $this->number($row['duration_days'] ?? 0));
            $real = min(1.0, max(0.0, $this->number($row['Ejecutado'] ?? 0)));
            $theoretical = min(1.0, max(0.0, $this->number($row['theoretical_progress_by_duration'] ?? 0)));
            $fallbackReal[] = $real;
            $fallbackTheoretical[] = $theoretical;
            if ($duration > 0) {
                $totalDuration += $duration;
                $weightedReal += $real * $duration;
                $weightedTheoretical += $theoretical * $duration;
            }
        }

        $realPct = $totalDuration > 0
            ? round(($weightedReal / $totalDuration) * 100, 1)
            : $this->averagePct($fallbackReal);
        $theoreticalPct = $totalDuration > 0
            ? round(($weightedTheoretical / $totalDuration) * 100, 1)
            : $this->averagePct($fallbackTheoretical);
        $deviation = round($realPct - $theoreticalPct, 1);

        return [
            $this->kpi('% Avance físico', $realPct, '%', null),
            $this->kpi('% Avance teórico', $theoreticalPct, '%', null),
            $this->kpi('Desviación vs plan', $deviation, 'pp', $deviation < -5 ? 'Alto riesgo' : ($deviation < 0 ? 'Medio' : 'OK')),
            $this->kpi('Críticas atrasadas', $criticalLate, 'count', 'Escalar'),
            $this->kpi('Total actividades', count($data), 'count', null),
        ];
    }

    private function scorecardPI(array $data): array
    {
        $hardNotReady = count(array_filter($data, fn($r) => ($r['is_hard'] ?? 0) == 1 && ($r['is_ready'] ?? 0) == 0));
        $totalHard = count(array_filter($data, fn($r) => ($r['is_hard'] ?? 0) == 1));
        $readyPct = $totalHard > 0 ? round((1 - $hardNotReady / $totalHard) * 100) : 0;
        return [
            $this->kpi('Restricciones no listas', $hardNotReady, 'count', 'Liberar'),
            $this->kpi('% Restricciones listas', $readyPct, '%', $readyPct < 50 ? 'Crítico' : 'OK'),
            $this->kpi('Total restricciones duras', $totalHard, 'count', null),
        ];
    }

    private function scorecardPS(array $data): array
    {
        $total = count($data);
        $atRisk = count(array_filter($data, fn($r) => ($r['fulfillment_alert'] ?? 0) == 1));
        $pac = $total > 0 ? round(count(array_filter($data, fn($r) => ($r['PAC'] ?? 0) == 1)) / $total * 100) : 0;
        $cnc = count(array_filter($data, fn($r) => ($r['has_CNC'] ?? 0) == 1));
        return [
            $this->kpi('Compromisos activos', $total, 'count', null),
            $this->kpi('PAC', $pac, '%', $pac < 60 ? 'Alto riesgo' : ($pac < 80 ? 'Medio' : 'OK')),
            $this->kpi('En riesgo (fulfillment)', $atRisk, 'count', $atRisk > 0 ? 'Revisar' : null),
            $this->kpi('CNC esta semana', $cnc, 'count', $cnc > 0 ? 'Análisis CNC' : null),
        ];
    }

    private function scorecardPDC(array $data): array
    {
        $vencidos = array_sum(array_map(fn($r) => (int) ($r['vencidos'] ?? 0), $data));
        $enRiesgo = array_sum(array_map(fn($r) => (int) ($r['en_riesgo'] ?? 0), $data));
        $destinos = array_sum(array_map(fn($r) => (int) ($r['destinos'] ?? 0), $data));
        $sinMirar = array_sum(array_map(fn($r) => (int) ($r['sin_mirar'] ?? 0), $data));

        // Cobertura promedio ponderada por destinos: la media simple le daria el mismo peso a una
        // obra de tres paquetes que a una de noventa.
        $peso = static fn(string $campo): float => $destinos > 0
            ? array_sum(array_map(
                fn($r) => (float) ($r[$campo] ?? 0) * (int) ($r['destinos'] ?? 0),
                $data,
            )) / $destinos
            : 0.0;

        return [
            // Los dos numeros de cobertura van SIEMPRE juntos: cada uno por separado cuenta media verdad.
            $this->kpi('Cobertura (conteo)', round($peso('cobertura'), 1), '%', null),
            $this->kpi('Cobertura (valor)', round($peso('cobertura_valor'), 1), '%', null),
            $this->kpi('Vencidos', $vencidos, 'count', $vencidos > 0 ? 'Escalar' : null),
            $this->kpi('En riesgo (3 semanas)', $enRiesgo, 'count', $enRiesgo > 0 ? 'Revisar' : null),
            $this->kpi('Destinos con pasos abiertos', $destinos, 'count', null),
            // Un tablero vacio y un tablero ciego se ven igual. Esta cifra es la diferencia.
            $this->kpi('Paquetes sin mirar', $sinMirar, 'count', $sinMirar > 0 ? 'Actualizar cronograma' : null),
        ];
    }

    private function scorecardCIC(array $data): array
    {
        $atRisk = count(array_filter($data, fn($r) => ($r['alert_contractor_future_risk'] ?? 0) == 1));
        return [
            $this->kpi('Contratistas evaluados', count($data), 'count', null),
            $this->kpi('En alerta futura', $atRisk, 'count', $atRisk > 0 ? 'Intervenir' : null),
        ];
    }

    private function scorecardCIP(array $data): array
    {
        $atRisk = count(array_filter($data, fn($r) => ($r['fulfillment_alert'] ?? 0) == 1));
        return [
            $this->kpi('Responsables evaluados', count($data), 'count', null),
            $this->kpi('En alerta cumplimiento', $atRisk, 'count', $atRisk > 0 ? 'Revisar carga' : null),
        ];
    }

    private function scorecardCurvaS(array $data): array
    {
        $s = $data ? $data[array_key_last($data)] : [];
        return [
            $this->kpi('% Avance Real', round(($s['pct_avance_real'] ?? 0) * 100), '%', null),
            $this->kpi('% Avance Teórico', round(($s['pct_avance_teorico'] ?? 0) * 100), '%', null),
            $this->kpi(
                '% Desviación',
                round(($s['pct_desviacion'] ?? 0) * 100, 1),
                '%',
                ($s['pct_desviacion'] ?? 0) < -0.05 ? 'Alto riesgo' : (($s['pct_desviacion'] ?? 0) < 0 ? 'Medio' : 'OK'),
            ),
            $this->kpi('Críticas atrasadas', (int) ($s['critical_late'] ?? 0), 'count', 'Escalar'),
        ];
    }

    // -----------------------------------------------------------------
    // Drivers composer
    // -----------------------------------------------------------------

    private function composeDrivers(string $reportKey, array $data): array
    {
        // Drivers are extracted from the raw data per report type
        $drivers = [];

        if ($reportKey === 'intermedia' || $reportKey === 'overview') {
            $byType = [];
            foreach ($data as $row) {
                $t = $row['restriction_type'] ?? null;
                if ($t && ($row['is_ready'] ?? 1) == 0) {
                    $byType[$t] = ($byType[$t] ?? 0) + 1;
                }
            }
            arsort($byType);
            foreach (array_slice($byType, 0, 5) as $type => $count) {
                $drivers[] = [
                    'driver' => $type,
                    'impact' => $count > 5 ? 'Alto' : ($count > 2 ? 'Medio' : 'Bajo'),
                    'evidence' => "{$count} actividades bloqueadas",
                    'action' => "Liberar {$type}",
                ];
            }
        }

        if ($reportKey === 'semanal') {
            $cncByCat = [];
            foreach ($data as $row) {
                $cat = $row['Categoria_CNC'] ?? null;
                if ($cat && ($row['has_CNC'] ?? 0) == 1) {
                    $cncByCat[$cat] = ($cncByCat[$cat] ?? 0) + 1;
                }
            }
            arsort($cncByCat);
            foreach (array_slice($cncByCat, 0, 5) as $cat => $count) {
                $drivers[] = [
                    'driver' => $cat,
                    'impact' => $count > 2 ? 'Alto' : 'Medio',
                    'evidence' => "{$count} CNC en esta categoría",
                    'action' => "Análisis causa raíz: {$cat}",
                ];
            }
        }

        return $drivers;
    }

    // -----------------------------------------------------------------
    // Risks composer (delegated to RiskScoringService)
    // -----------------------------------------------------------------

    private function composeRisks(string $reportKey, array $projectIds, string $semana, array $filters): array
    {
        return $this->riskScoring->getTopRisks($reportKey, $projectIds, $semana, 10, $filters);
    }

    private function composeCharts(string $reportKey, array $data, array $scorecard, array $projectIds, string $semana, array $filters): array
    {
        $source = $this->dataSourceForReport($reportKey);
        $scoreLabels = array_map(fn($row) => (string) ($row['kpi'] ?? 'Métrica'), $scorecard);
        $scoreValues = array_map(fn($row) => $this->number($row['value'] ?? 0), $scorecard);

        return match ($reportKey) {
            'overview' => [
                'chart-ppc-semanal' => $this->chart('bar', $scoreLabels, [
                    $this->dataset('Indicadores LPS', $scoreValues, 'brand-primary'),
                ], $source),
                'chart-pac-prog' => $this->chart('bar', array_slice($scoreLabels, 0, 3), [
                    $this->dataset('Flujo real LPS', array_slice($scoreValues, 0, 3), 'brand-aqua'),
                ], $source),
            ],
            'programa-general' => $this->composeProgramaGeneralCharts($projectIds, $semana, $filters, $data),
            'curva-s' => [
                'chart-curva-s' => $this->chart('line', $this->curvaSLabels($data), [
                    $this->dataset('Curva teórica', $this->curvaSValues($data, 'pct_avance_teorico'), 'neutral-muted', [6, 3]),
                    $this->dataset('Curva real', $this->curvaSValues($data, 'pct_avance_real'), 'brand-aqua'),
                ], $source),
            ],
            'intermedia' => [
                'chart-intermedia' => $this->chart('bar', $scoreLabels, [
                    $this->dataset('Restricciones', $scoreValues, 'brand-primary-medium'),
                ], $source),
            ],
            'semanal' => [
                'chart-semanal-pac' => $this->chart('doughnut', ['PAC', 'Pendiente'], [
                    $this->dataset('PAC', [
                        $this->scoreValue($scorecard, 'PAC'),
                        max(0, 100 - $this->scoreValue($scorecard, 'PAC')),
                    ], 'brand-primary'),
                ], $source),
            ],
            default => [],
        };
    }

    private function dataSourceForReport(string $reportKey): array
    {
        return match ($reportKey) {
            'overview' => [
                'source_relations' => [
                    'bi_pg_semana',
                    'programacion_semanal',
                    'bi_ps_compromisos',
                    'bi_cic_contratistas',
                    'bi_cip_responsables',
                ],
                'grain' => 'project_id + Semana',
            ],
            'programa-general' => [
                'source_relations' => ['programa_consolidado', 'semanas_activas', 'programacion_semanal'],
                'grain' => 'programa: project_id + Semana + unique_id; Curva S ponderada por duración calendario; causas/radar: project_id + Semana + row_id',
            ],
            'intermedia' => [
                'source_relations' => ['bi_pi_restricciones'],
                'grain' => 'project_id + Semana + unique_id + restriction_type',
            ],
            'semanal' => [
                'source_relations' => ['bi_ps_compromisos'],
                'grain' => 'project_id + Semana + row_id',
            ],
            'pdc' => [
                'source_relations' => ['pdc_plan_paso', 'pdc_plan_paquete', 'pdc_subpaquete', 'general_paquetes_contratacion'],
                'grain' => 'project_id + paquete_id + subpaquete_id (destino), contra la fecha de hoy',
            ],
            'cic' => [
                'source_relations' => ['bi_cic_contratistas'],
                'grain' => 'project_id + Semana + subcontratista',
            ],
            'cip' => [
                'source_relations' => ['bi_cip_responsables'],
                'grain' => 'project_id + Semana + Responsable_AIA',
            ],
            'curva-s' => [
                'source_relations' => ['bi_pg_semana'],
                'grain' => 'Semana agregada desde actividades filtradas',
            ],
            default => [
                'source_relations' => [],
                'grain' => '',
            ],
        };
    }

    private function composeProgramaGeneralCharts(array $projectIds, string $semana, array $filters, array $data): array
    {
        $pgSource = ['source_relations' => ['programa_consolidado', 'semanas_activas'], 'grain' => 'project_id + Semana + unique_id; acumulado ponderado por duración calendario y Titulo=0'];
        $commitmentSource = ['source_relations' => ['programacion_semanal'], 'grain' => "project_id + Semana + row_id; Radar usa Activa IN ('1','NA'), excluye Es_TNP=1 y conserva población válida independiente por eje"];
        $causalSource = ['source_relations' => ['programacion_semanal', 'semanas_activas'], 'grain' => 'project_id + Semana + Consecutivo; universo CNP/CNC por estado real y urgencia al corte semanal'];
        $trend = $this->fetchProgramaGeneralTrend($projectIds, $semana, $filters);
        $context = $this->programaCurveContext($trend);
        $forecastTrend = $this->fetchProgramaGeneralForecastTrend($projectIds, $semana, $filters);
        $forecastContext = $this->programaCurveContext($forecastTrend);
        $radarPopulation = $this->fetchProgramaRadarPopulation($projectIds, $semana, $filters);
        $causalPopulation = $this->fetchProgramaCausalPopulation($projectIds, $semana, $filters);
        $progress = $this->programaProgressSeries($trend, $context);
        $curveCutoffIndex = $this->lastNumericIndex($progress['real']);
        $curveRealProgress = $curveCutoffIndex === null ? 0.0 : (float) $progress['real'][$curveCutoffIndex];
        $curveTheoreticalProgress = $curveCutoffIndex === null ? 0.0 : (float) ($progress['theoretical'][$curveCutoffIndex] ?? 0.0);
        $cnpMetrics = $this->programaCnpMetrics($causalPopulation);
        $cncMetrics = $this->programaCncMetrics($causalPopulation);
        $causasNoProgramacion = $this->causalMetricSeries($cnpMetrics);
        $causasNoCumplimiento = $this->causalMetricSeries($cncMetrics);
        $compliancePayload = $this->programaCompliancePayload($trend, $context);
        $complianceSummary = $compliancePayload['summary'];
        $forecastProgress = $this->programaProgressSeries($forecastTrend, $forecastContext);
        $contractualBaseline = $this->programaContractualBaselineForCurrentCohort($projectIds, $forecastContext);
        $delayForecast = $this->programaDelayForecast(
            $forecastTrend, $forecastContext, $forecastProgress, $projectIds, $semana, $filters,
            $contractualBaseline,
        );
        $cumplimientoCronograma = $this->scheduleCompliancePct($curveRealProgress, $curveTheoreticalProgress);
        $progressRange = array_merge(
            $this->schedulePerformanceRange($curveRealProgress, $curveTheoreticalProgress),
            [
                'basis' => 'real_vs_theoretical_pct',
                'basis_value' => $cumplimientoCronograma,
                'tolerance_pct' => 5.0,
            ],
        );
        $complianceRange = $this->semanticMetricRange($cumplimientoCronograma, 'compliance');
        $progressMetrics = array_merge($complianceSummary, ['range' => $progressRange]);
        $complianceMetrics = array_merge($complianceSummary, ['range' => $complianceRange]);
        $radar = $this->programaRadar($radarPopulation);

        return [
            'programa-curva-ejecucion' => $this->withProjectionMeta($this->chart('line', $progress['labels'], [
                $this->dataset('Curva teórica total', $progress['theoretical'], 'neutral-muted', [6, 3]),
                $this->dataset('Real acumulado', $progress['real'], 'brand-aqua'),
                $this->dataset('Proyección pesimista (Rango probable 80%)', $progress['pessimistic'], 'critical', [4, 4]),
                $this->dataset('Proyección más probable', $progress['likely'], 'brand-primary', [5, 3]),
                $this->dataset('Proyección optimista (Rango probable 80%)', $progress['optimistic'], 'brand-aqua-medium', [4, 4]),
            ], $pgSource), $progress['projection_meta']),
            'programa-gauge' => $this->withMetrics($this->chart('doughnut', ['Avance físico', 'Pendiente'], [
                $this->dataset('Avance físico', [
                    $curveRealProgress,
                    max(0, 100 - $curveRealProgress),
                ], $progressRange['color_token']),
                $this->dataset('Avance teórico', [
                    $curveTheoreticalProgress,
                    max(0, 100 - $curveTheoreticalProgress),
                ], 'brand-aqua-medium'),
            ], $pgSource), $progressMetrics, [
                'detail_endpoint' => '/api/bi/report/programa-general/progress-detail',
                'desktop_action' => 'dblclick', 'mobile_action' => 'button',
            ]),
            'programa-compliance' => $this->withMetrics($this->chart('doughnut', ['Cumplimiento cronograma', 'Brecha'], [
                $this->dataset('Cumplimiento cronograma', [
                    $cumplimientoCronograma,
                    max(0, 100 - $cumplimientoCronograma),
                ], $complianceRange['color_token']),
            ], $pgSource), $complianceMetrics, [
                'detail_endpoint' => '/api/bi/report/programa-general/compliance-detail',
                'desktop_action' => 'dblclick', 'mobile_action' => 'button',
            ]),
            'programa-dias-retraso' => $this->withMetrics($this->chart('bar', ['P50'], [
                $this->dataset('Variación probable de fecha final', [$delayForecast['value']], $delayForecast['value'] > 0 ? 'critical' : 'brand-primary'),
            ], $pgSource), $delayForecast['metrics'], [
                'detail_endpoint' => '/api/bi/report/programa-general/delay-detail',
                'desktop_action' => 'dblclick', 'mobile_action' => 'button',
            ]) + [
                'status' => $delayForecast['status'],
                'availability' => $delayForecast['availability'],
            ],
            'programa-cnp' => $this->withMetrics($this->chart('doughnut', $causasNoProgramacion['labels'], [
                $this->dataset('Actividades no programadas', $causasNoProgramacion['values'], 'brand-aqua'),
            ], $causalSource), $cnpMetrics, [
                'detail_endpoint' => '/api/bi/report/programa-general/cnp-detail',
                'desktop_action' => 'dblclick', 'mobile_action' => 'button',
            ]),
            'programa-cnc' => $this->withMetrics($this->chart('doughnut', $causasNoCumplimiento['labels'], [
                $this->dataset('Causas de no cumplimiento', $causasNoCumplimiento['values'], 'brand-construction'),
            ], $causalSource), $cncMetrics, [
                'detail_endpoint' => '/api/bi/report/programa-general/cnc-detail',
                'desktop_action' => 'dblclick', 'mobile_action' => 'button',
            ]),
            'programa-radar-productividad' => $this->chart('radar', ['Productividad', 'Eficiencia', 'Desempeño'], [
                $this->dataset('Radar operativo', $radar['display_values'], 'brand-aqua'),
            ], $commitmentSource) + [
                'status' => $radar['status'],
                'sample_size' => $radar['sample_size'],
                'axes' => $radar['axes'],
                'interaction' => [
                    'detail_endpoint' => '/api/bi/report/programa-general/radar-detail',
                    'desktop_action' => 'dblclick', 'mobile_action' => 'button',
                ],
            ],
        ];
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

    private function describeFilters(string $semana, array $filters): array
    {
        return [
            'semana' => $this->hasDateRange($filters) ? '' : $semana,
            'desde' => $filters['desde'],
            'hasta' => $filters['hasta'],
            'sub' => $filters['sub'],
            'resp' => $filters['resp'],
            'etapa' => $filters['etapa'],
            'date_range_overrides_semana' => $this->hasDateRange($filters),
        ];
    }

    // -----------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------

    private function buildFilteredWhere(array $projectIds, string $semana, array $filters, string $alias, array $columns): array
    {
        $projectIds = array_values(array_filter(array_map('intval', $projectIds), fn($id) => $id > 0));
        $in = $this->inClause($projectIds ?: [0]);
        $where = ["{$alias}.project_id IN ({$in})"];
        $params = $projectIds ?: [0];
        $weekColumn = $columns['week'] ?? 'Semana';

        if ($this->hasDateRange($filters)) {
            $where[] = "EXISTS (
                SELECT 1 FROM semanas_activas sa_filter
                WHERE sa_filter.project_id = {$alias}.project_id
                  AND sa_filter.Semana = {$alias}.{$weekColumn}
                  AND sa_filter.Fecha_Inicio_Sem <= ?
                  AND sa_filter.Fecha_Fin_Sem >= ?
            )";
            $params[] = $filters['hasta'] ?: '9999-12-31';
            $params[] = $filters['desde'] ?: '1000-01-01';
        } elseif ($semana !== '') {
            $where[] = "{$alias}.{$weekColumn} = ?";
            $params[] = $semana;
        }

        $this->appendLikeFilter($where, $params, $alias, $columns['sub'] ?? '', $filters['sub']);
        $this->appendLikeFilter($where, $params, $alias, $columns['resp'] ?? '', $filters['resp']);
        $this->appendMultiColumnLikeFilter($where, $params, $alias, $columns['etapa'] ?? [], $filters['etapa']);

        return [implode(' AND ', $where), $params];
    }

    private function appendLikeFilter(array &$where, array &$params, string $alias, string $column, string $value): void
    {
        if ($column === '' || $value === '') {
            return;
        }

        $where[] = "LOWER(COALESCE({$alias}.{$column}, '')) LIKE ?";
        $params[] = '%' . strtolower($value) . '%';
    }

    private function appendMultiColumnLikeFilter(array &$where, array &$params, string $alias, array $columns, string $value): void
    {
        if (!$columns || $value === '') {
            return;
        }

        $parts = array_map(fn($column) => "LOWER(COALESCE({$alias}.{$column}, '')) LIKE ?", $columns);
        $where[] = '(' . implode(' OR ', $parts) . ')';
        foreach ($columns as $_column) {
            $params[] = '%' . strtolower($value) . '%';
        }
    }

    private function hasDateRange(array $filters): bool
    {
        return ($filters['desde'] ?? '') !== '' || ($filters['hasta'] ?? '') !== '';
    }

    private function dateOrBlank(mixed $value): string
    {
        $value = trim((string) $value);
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : '';
    }

    private function collectFilterValues(array $projectIds, string $semana, array $filters, array $sources): array
    {
        $values = [];
        foreach ($sources as [$table, $alias, $columns, $valueColumn]) {
            [$where, $params] = $this->buildFilteredWhere($projectIds, $semana, $filters, $alias, $columns);
            $rows = $this->queryAll(
                "SELECT DISTINCT {$alias}.{$valueColumn} AS value FROM {$table} {$alias}
                 WHERE {$where}
                   AND {$alias}.{$valueColumn} IS NOT NULL
                   AND {$alias}.{$valueColumn} <> ''",
                $params,
            );
            foreach ($rows as $row) {
                $value = trim((string) ($row['value'] ?? ''));
                if ($value !== '') {
                    $values[$value] = $value;
                }
            }
        }

        natcasesort($values);
        return array_slice(array_values($values), 0, 150);
    }

    private function fetchProgramaGeneralTrend(array $projectIds, string $semana, array $filters): array
    {
        [$where, $params] = $this->buildFilteredWhere($projectIds, '', $filters, 'pc', [
            'week' => 'Semana', 'sub' => 'Sub_Contratista', 'resp' => 'Responsable_AIA', 'etapa' => ['Actividad', 'Estado'],
        ]);
        if (!$this->hasDateRange($filters) && $semana !== '') {
            $where .= ' AND pc.Semana <= ?';
            $params[] = $semana;
        }

        return $this->queryAll(
            $this->programaGeneralDirectSelect() . " WHERE {$where}
                 AND COALESCE(pc.Titulo, 0) = 0
                 AND pc.Fecha_Inicio IS NOT NULL
                 AND pc.Fecha_Fin IS NOT NULL
                 AND DATEDIFF(pc.Fecha_Fin, pc.Fecha_Inicio) >= 0
             ORDER BY pc.Semana, pc.Consecutivo_en_Programa",
            $params,
        );
    }

    private function fetchProgramaGeneralSnapshot(array $projectIds, string $semana, array $filters): array
    {
        $snapshotWeeks = $this->programaSnapshotWeeks($projectIds, $semana, $filters);
        if ($snapshotWeeks === []) return [];

        [$where, $params] = $this->buildFilteredWhere($projectIds, '', $filters, 'pc', [
            'week' => 'Semana', 'sub' => 'Sub_Contratista', 'resp' => 'Responsable_AIA', 'etapa' => ['Actividad', 'Estado'],
        ]);
        $snapshotConditions = [];
        foreach ($snapshotWeeks as $projectId => $week) {
            $snapshotConditions[] = '(pc.project_id = ? AND pc.Semana = ?)';
            $params[] = $projectId;
            $params[] = $week;
        }
        $where .= ' AND (' . implode(' OR ', $snapshotConditions) . ')';

        return $this->queryAll(
            $this->programaGeneralDirectSelect() . " WHERE {$where}
                 AND COALESCE(pc.Titulo, 0) = 0
                 AND pc.Fecha_Inicio IS NOT NULL
                 AND pc.Fecha_Fin IS NOT NULL
                 AND DATEDIFF(pc.Fecha_Fin, pc.Fecha_Inicio) >= 0
             ORDER BY pc.project_id, pc.Consecutivo_en_Programa",
            $params,
        );
    }

    private function programaSnapshotWeeks(array $projectIds, string $semana, array $filters): array
    {
        $projectIds = array_values(array_filter(array_map('intval', $projectIds), static fn(int $id): bool => $id > 0));
        if ($projectIds === []) return [];

        $where = ['pc.project_id IN (' . $this->inClause($projectIds) . ')'];
        $params = $projectIds;
        if ($this->hasDateRange($filters)) {
            $where[] = "EXISTS (
                SELECT 1 FROM semanas_activas sa_snapshot
                WHERE sa_snapshot.project_id = pc.project_id
                  AND sa_snapshot.Semana = pc.Semana
                  AND sa_snapshot.Fecha_Inicio_Sem <= ?
                  AND sa_snapshot.Fecha_Fin_Sem >= ?
            )";
            $params[] = $filters['hasta'] ?: '9999-12-31';
            $params[] = $filters['desde'] ?: '1000-01-01';
        } elseif ($semana !== '') {
            $where[] = 'pc.Semana <= ?';
            $params[] = $semana;
        }
        $where[] = 'COALESCE(pc.Titulo, 0) = 0';
        $where[] = 'pc.Fecha_Inicio IS NOT NULL';
        $where[] = 'pc.Fecha_Fin IS NOT NULL';
        $where[] = 'DATEDIFF(pc.Fecha_Fin, pc.Fecha_Inicio) >= 0';

        $rows = $this->queryAll(
            'SELECT pc.project_id, MAX(pc.Semana) AS snapshot_week
             FROM programa_consolidado pc
             WHERE ' . implode(' AND ', $where) . '
             GROUP BY pc.project_id',
            $params,
        );

        $weeks = [];
        foreach ($rows as $row) {
            $projectId = (int) ($row['project_id'] ?? 0);
            if ($projectId > 0 && is_numeric($row['snapshot_week'] ?? null)) {
                $weeks[$projectId] = (int) $row['snapshot_week'];
            }
        }
        return $weeks;
    }

    private function fetchProgramaGeneralForecastTrend(array $projectIds, string $semana, array $filters): array
    {
        $selectionRows = array_values(array_filter(
            $this->fetchProgramaGeneral($projectIds, $semana, $filters),
            function (array $row): bool {
                $start = $this->dateFromString((string) ($row['Fecha_Inicio'] ?? ''));
                $finish = $this->dateFromString((string) ($row['Fecha_Fin'] ?? ''));
                return $start !== null && $finish !== null && $finish >= $start;
            },
        ));
        $selectionContext = $this->programaCurveContext($selectionRows);
        $cohortByProject = [];
        foreach ($selectionContext['baseline'] ?? [] as $row) {
            $projectId = (int) ($row['project_id'] ?? 0);
            $uniqueId = (int) ($row['unique_id'] ?? 0);
            if ($projectId > 0 && $uniqueId > 0) {
                $cohortByProject[$projectId][$uniqueId] = true;
            }
        }
        if ($cohortByProject === []) {
            return [];
        }

        $cutoffs = $selectionContext['current_project_cutoffs'] ?? [];
        $history = $this->fetchProgramaGeneralTrend($projectIds, '', $this->normalizeFilters([]));

        return array_values(array_filter($history, function (array $row) use ($cohortByProject, $cutoffs): bool {
            $projectId = (int) ($row['project_id'] ?? 0);
            $uniqueId = (int) ($row['unique_id'] ?? 0);
            if (!isset($cohortByProject[$projectId][$uniqueId])) {
                return false;
            }

            $cutoff = $this->effectiveSnapshotCutoff($row);
            $selectedCutoff = (string) ($cutoffs[$projectId] ?? '');
            return $selectedCutoff !== '' && $cutoff !== '' && $cutoff <= $selectedCutoff;
        }));
    }

    private function programaGeneralDirectSelect(): string
    {
        $duration = "(DATEDIFF(pc.Fecha_Fin, pc.Fecha_Inicio) + 1)";
        $cutoff = "COALESCE(sa.Fecha_Fin_Sem, sa.Fecha_Inicio_Sem)";
        $elapsed = "(DATEDIFF({$cutoff}, pc.Fecha_Inicio) + 1)";

        return "SELECT
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
                    WHEN CAST(COALESCE(pc.D_y_E, '0') AS DECIMAL(10,2)) >= 1.0
                     AND CAST(COALESCE(pc.Materiales, '0') AS DECIMAL(10,2)) >= 1.0
                     AND CAST(COALESCE(pc.MdeO, '0') AS DECIMAL(10,2)) >= 1.0
                     AND CAST(COALESCE(pc.Equipos, '0') AS DECIMAL(10,2)) >= 1.0
                     AND CAST(COALESCE(pc.Predecesora, '0') AS DECIMAL(10,2)) >= 0.5
                    THEN 1 ELSE 0
                END AS hard_restrictions_ready,
                CASE WHEN pc.Semanas_Inicio BETWEEN 0 AND 6 THEN 1 ELSE 0 END AS is_lookahead_window,
                CASE WHEN pc.Semanas_Inicio = 0 AND COALESCE(pc.Ejecutado, 0) < 1 THEN 1 ELSE 0 END AS should_start_this_week,
                CASE
                    WHEN pc.Fecha_Fin IS NOT NULL
                     AND {$cutoff} IS NOT NULL
                     AND pc.Fecha_Fin < {$cutoff}
                     AND (pc.Ejecutado IS NULL OR pc.Ejecutado < 1)
                    THEN 1 ELSE 0
                END AS is_late,
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
                sa.Fecha_Fin_Sem,
                sa.Semanal_Confirmada
            FROM programa_consolidado pc
            LEFT JOIN semanas_activas sa
                ON pc.project_id = sa.project_id
               AND pc.Semana = sa.Semana";
    }

    private function programaProgressSeries(array $rows, ?array $context = null): array
    {
        $context = $context ?? $this->programaCurveContext($rows);
        $labels = $context['labels'] ?? [];
        $pointDates = $context['point_dates'] ?? [];
        $real = [];
        $theoretical = [];
        $lastReal = 0.0;
        $lastTheoretical = 0.0;
        $lastPointDate = null;
        $weight = max(1.0, (float) ($context['total_weight'] ?? 0));
        foreach ($context['point_dates'] ?? [] as $pointIndex => $pointDate) {
            $isObservedPoint = $pointIndex <= (int) ($context['current_week_index'] ?? -1);
            $aggregate = $this->aggregateProgramaPoint($context, $pointDate, $isObservedPoint);
            $pointDateObject = $this->dateFromString($pointDate);
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

        $projection = $this->programaProgressProjection($real, $context['current_week_index'] ?? null, count($labels));
        while (count($labels) < count($projection['likely'])) {
            if ($lastPointDate === null) {
                break;
            }
            $lastPointDate = $lastPointDate->modify('+7 days');
            $labels[] = $this->programaCurveDateLabel($lastPointDate, count($labels));
            $pointDates[] = $lastPointDate->format('Y-m-d');
            $aggregate = $this->aggregateProgramaPoint($context, $lastPointDate->format('Y-m-d'), false);
            $theoreticalPoint = max($lastTheoretical, round(($aggregate['planned'] / $weight) * 100, 1));
            $lastTheoretical = $theoreticalPoint;
            $theoretical[] = $theoreticalPoint;
            $real[] = null;
        }
        $completionPointDates = $pointDates;
        $completionLastDate = $this->dateFromString((string) (end($completionPointDates) ?: ''));
        $completionWeekSamples = $projection['completion_week_samples'] ?? [];
        $maxCompletionIndex = $completionWeekSamples
            ? max($completionWeekSamples)
            : -1;
        while ($completionLastDate !== null && count($completionPointDates) <= $maxCompletionIndex) {
            $completionLastDate = $completionLastDate->modify('+7 days');
            $completionPointDates[] = $completionLastDate->format('Y-m-d');
        }

        return [
            'labels' => $labels,
            'point_dates' => $pointDates,
            'completion_point_dates' => $completionPointDates,
            'cutoff' => (string) ($context['current_cutoff'] ?? ''),
            'cutoff_label' => (string) ($context['current_cutoff_label'] ?? ($context['current_cutoff'] ?? '')),
            'current_week' => (int) ($context['current_week'] ?? 0),
            'real' => $real,
            'theoretical' => $theoretical,
            'pessimistic' => $projection['pessimistic'],
            'likely' => $projection['likely'],
            'optimistic' => $projection['optimistic'],
            'completion_week_samples' => $projection['completion_week_samples'] ?? [],
            'projection_meta' => $projection['meta'],
        ];
    }

    private function programaCurveDateLabel(?\DateTimeImmutable $date, int $week): string
    {
        if ($week === 0) {
            return 'Inicio';
        }

        if ($date !== null) {
            return $date->format('d/m/y');
        }

        return '';
    }

    private function programaEarnedValueSeries(array $rows, ?array $context = null): array
    {
        $context = $context ?? $this->programaCurveContext($rows);
        $labels = $context['labels'] ?? [];
        $earned = [];
        $planned = [];
        $lastEarned = 0.0;
        $lastPlanned = 0.0;
        foreach ($context['point_dates'] ?? [] as $pointIndex => $pointDate) {
            $isObservedPoint = $pointIndex <= (int) ($context['current_week_index'] ?? -1);
            $aggregate = $this->aggregateProgramaPoint($context, $pointDate, $isObservedPoint);
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

    private function programaCurveContext(array $rows): array
    {
        $projectSnapshots = [];
        $latestSnapshots = [];
        $observedCutoffs = [];
        foreach ($rows as $row) {
            $projectId = (int) ($row['project_id'] ?? 0);
            $uniqueId = (int) ($row['unique_id'] ?? 0);
            $week = (int) ($row['Semana'] ?? 0);
            if ($projectId <= 0 || $uniqueId <= 0) {
                continue;
            }

            $cutoff = $this->effectiveSnapshotCutoff($row);
            $cutoffDate = $this->dateFromString($cutoff);
            if ($cutoffDate === null) {
                continue;
            }

            $cutoffKey = $cutoffDate->format('Y-m-d');
            $key = $projectId . ':' . $uniqueId;
            if (!isset($projectSnapshots[$projectId][$cutoffKey])) {
                $projectSnapshots[$projectId][$cutoffKey] = [
                    'project_id' => $projectId,
                    'week' => $week,
                    'cutoff' => $cutoffKey,
                    'rows' => [],
                ];
            }
            $projectSnapshots[$projectId][$cutoffKey]['rows'][$key] = $row;
            $observedCutoffs[$cutoffKey] = $cutoffKey;

            $currentLatest = $latestSnapshots[$projectId] ?? null;
            if (
                $currentLatest === null
                || $cutoffKey > (string) ($currentLatest['cutoff'] ?? '')
                || ($cutoffKey === (string) ($currentLatest['cutoff'] ?? '') && $week > (int) ($currentLatest['week'] ?? 0))
            ) {
                $latestSnapshots[$projectId] = [
                    'cutoff' => $cutoffKey,
                    'week' => $week,
                ];
            }
        }

        foreach ($projectSnapshots as &$snapshots) {
            ksort($snapshots);
        }
        unset($snapshots);

        $baseline = [];
        $baselineByProject = [];
        $contractualBaselineByProject = [];
        $currentProjectCutoffs = [];
        $currentWeek = 0;
        foreach ($latestSnapshots as $projectId => $latest) {
            $firstSnapshot = reset($projectSnapshots[$projectId]);
            $contractualBaselineByProject[$projectId] = $firstSnapshot['rows'] ?? [];
            $snapshot = $projectSnapshots[$projectId][$latest['cutoff']] ?? null;
            if ($snapshot === null) {
                continue;
            }

            $currentProjectCutoffs[$projectId] = (string) $latest['cutoff'];
            $currentWeek = max($currentWeek, (int) ($latest['week'] ?? 0));
            $baselineByProject[$projectId] = $snapshot['rows'];
            foreach ($snapshot['rows'] as $key => $row) {
                $baseline[$key] = $row;
            }
        }

        $totalWeight = 0.0;
        foreach ($baseline as $row) {
            $totalWeight += $this->programaCurveWeight($row);
        }

        [$projectStart, $projectFinish] = $this->programaBaselineDates($baseline);
        $timeline = array_values($observedCutoffs);
        sort($timeline, SORT_STRING);

        $pointDates = [];
        $labels = [];
        if ($projectStart !== null) {
            $pointDates[] = $projectStart->modify('-1 day')->format('Y-m-d');
            $labels[] = 'Inicio';
        }
        foreach ($timeline as $cutoff) {
            $pointDates[] = $cutoff;
            $labels[] = $this->programaCurveDateLabel($this->dateFromString($cutoff), count($labels));
        }

        $currentPointIndex = $timeline ? count($pointDates) - 1 : null;
        $currentCutoff = $currentProjectCutoffs ? max($currentProjectCutoffs) : '';
        $lastPointDate = $this->dateFromString($currentCutoff) ?? $projectStart;
        while ($lastPointDate !== null && $projectFinish !== null && $lastPointDate < $projectFinish) {
            $nextPointDate = $lastPointDate->modify('+7 days');
            if ($nextPointDate > $projectFinish) {
                $nextPointDate = $projectFinish;
            }
            $pointDates[] = $nextPointDate->format('Y-m-d');
            $labels[] = $this->programaCurveDateLabel($nextPointDate, count($labels));
            $lastPointDate = $nextPointDate;
        }

        return [
            'labels' => $labels,
            'point_dates' => $pointDates,
            'project_snapshots' => $projectSnapshots,
            'baseline' => $baseline,
            'baseline_by_project' => $baselineByProject,
            'contractual_baseline_by_project' => $contractualBaselineByProject,
            'current_rows' => array_values($baseline),
            'current_project_cutoffs' => $currentProjectCutoffs,
            'current_cutoff' => $currentCutoff,
            'current_cutoff_label' => $this->projectCutoffSummary($currentProjectCutoffs),
            'total_weight' => $totalWeight,
            'current_week' => $currentWeek,
            'current_week_index' => $currentPointIndex,
        ];
    }

    private function effectiveSnapshotCutoff(array $row): string
    {
        $cutoff = trim((string) ($row['Fecha_Fin_Sem'] ?? ''));
        if ($cutoff !== '') {
            return $cutoff;
        }

        return trim((string) ($row['Fecha_Inicio_Sem'] ?? ''));
    }

    private function latestProjectSnapshotAtOrBefore(array $snapshots, string $pointDate): ?array
    {
        $activeSnapshot = null;
        foreach ($snapshots as $snapshotCutoff => $snapshot) {
            if ($snapshotCutoff > $pointDate) {
                break;
            }
            $activeSnapshot = $snapshot;
        }

        return $activeSnapshot;
    }

    private function aggregateProgramaPoint(array $context, string $pointDate, bool $useObservedSnapshotCutoff): array
    {
        $real = 0.0;
        $planned = 0.0;
        foreach ($context['baseline_by_project'] ?? [] as $projectId => $baselineRows) {
            $snapshots = $context['project_snapshots'][$projectId] ?? [];
            $activeSnapshot = $useObservedSnapshotCutoff
                ? $this->latestProjectSnapshotAtOrBefore($snapshots, $pointDate)
                : null;
            $projectRows = $activeSnapshot['rows'] ?? [];
            $projectCutoff = $useObservedSnapshotCutoff
                ? (string) ($activeSnapshot['cutoff'] ?? '')
                : $pointDate;

            foreach ($baselineRows as $key => $baselineRow) {
                $rowWeight = $this->programaCurveWeight($baselineRow);
                $actualRow = $projectRows[$key] ?? null;
                $real += $rowWeight * min(1.0, max(0.0, $this->number($actualRow['Ejecutado'] ?? 0)));
                $planned += $rowWeight * $this->plannedProgressAtCutoff($baselineRow, $projectCutoff);
            }
        }

        return [
            'real' => $real,
            'planned' => $planned,
        ];
    }

    private function projectCutoffSummary(array $projectCutoffs): string
    {
        $cutoffs = array_values(array_unique(array_filter(array_map('strval', $projectCutoffs))));
        sort($cutoffs, SORT_STRING);
        if ($cutoffs === []) {
            return '';
        }
        if (count($cutoffs) === 1) {
            return $cutoffs[0];
        }

        return $cutoffs[0] . ' a ' . $cutoffs[count($cutoffs) - 1];
    }

    private function withProjectionMeta(array $chart, array $meta): array
    {
        $chart['projection_meta'] = $meta;
        return $chart;
    }

    private function withMetrics(array $chart, array $metrics, array $interaction = []): array
    {
        $chart['metrics'] = $metrics;
        if ($interaction) {
            $chart['interaction'] = $interaction;
        }
        return $chart;
    }

    private function withInteraction(array $chart, string $endpoint): array
    {
        $chart['interaction'] = [
            'detail_endpoint' => $endpoint,
            'desktop_action' => 'dblclick', 'mobile_action' => 'button',
        ];
        return $chart;
    }

    private function lastNumericPoint(array $values): float
    {
        $index = $this->lastNumericIndex($values);
        return $index === null ? 0.0 : (float) $values[$index];
    }

    private function lastNumericIndex(array $values): ?int
    {
        for ($i = count($values) - 1; $i >= 0; $i--) {
            if (is_numeric($values[$i] ?? null)) {
                return $i;
            }
        }

        return null;
    }

    private function syncProgramaScorecardFromCharts(array $scorecard, array $charts): array
    {
        $metrics = $charts['programa-gauge']['metrics'] ?? [];
        $real = $metrics['real_pct'] ?? ($charts['programa-gauge']['datasets'][0]['data'][0] ?? null);
        $planned = $metrics['theoretical_pct'] ?? null;
        if (!is_numeric($real) || !is_numeric($planned)) {
            return $scorecard;
        }

        $real = round((float) $real, 1);
        $planned = round((float) $planned, 1);
        foreach ($scorecard as &$row) {
            if (($row['kpi'] ?? '') === '% Avance físico') {
                $row['value'] = $real;
            }
            if (($row['kpi'] ?? '') === '% Avance teórico') {
                $row['value'] = $planned;
            }
        }
        unset($row);

        $deviation = round($real - $planned, 1);
        foreach ($scorecard as &$row) {
            if (($row['kpi'] ?? '') === 'Desviación vs plan') {
                $row['value'] = $deviation;
                $row['status'] = $deviation < -5 ? 'Alto riesgo' : ($deviation < 0 ? 'Medio' : 'OK');
                $row['action'] = $deviation < -5 ? 'Alto riesgo' : ($deviation < 0 ? 'Medio' : null);
            }
        }
        unset($row);

        return $scorecard;
    }

    private function programaBaselineDates(array $baseline): array
    {
        $start = null;
        $finish = null;
        foreach ($baseline as $row) {
            $startDate = $this->dateFromString((string) ($row['Fecha_Inicio'] ?? ''));
            $finishDate = $this->dateFromString((string) ($row['Fecha_Fin'] ?? ''));
            if ($startDate !== null && ($start === null || $startDate < $start)) {
                $start = $startDate;
            }
            if ($finishDate !== null && ($finish === null || $finishDate > $finish)) {
                $finish = $finishDate;
            }
        }

        return [$start, $finish];
    }

    private function programaDelayForecast(
        array $trend,
        array $context,
        array $progress,
        array $projectIds,
        string $semana,
        array $filters,
        array $contractualBaselineByProject,
    ): array {
        $rowsByProject = [];
        foreach ($trend as $row) {
            $rowsByProject[(int) ($row['project_id'] ?? 0)][] = $row;
        }

        $projects = [];
        foreach (array_values(array_unique(array_map('intval', $projectIds))) as $projectId) {
            $projectRows = $rowsByProject[$projectId] ?? [];
            $projectContext = $this->programaCurveContext($projectRows);
            $projectProgress = $this->programaProgressSeries($projectRows, $projectContext);
            $projects[] = $this->programaProjectForecast(
                $projectId,
                $projectContext,
                $projectProgress,
                $contractualBaselineByProject[$projectId] ?? [],
            );
        }

        $available = $projects !== [] && count(array_filter(
            $projects,
            static fn(array $project): bool => ($project['availability'] ?? false) !== true,
        )) === 0;
        $contractualFinish = $this->maxForecastDate(array_column($projects, 'contractual_finish'));
        $portfolioSamples = [];
        if ($available) {
            for ($simulation = 0; $simulation < 240; $simulation++) {
                $portfolioSamples[] = max(array_map(
                    static fn(array $project): string => $project['_completion_date_samples'][$simulation],
                    $projects,
                ));
            }
        }
        $forecast = [
            'p10_finish' => $this->datePercentile($portfolioSamples, 0.1),
            'p50_finish' => $this->datePercentile($portfolioSamples, 0.5),
            'p90_finish' => $this->datePercentile($portfolioSamples, 0.9),
        ];
        $projectBreakdown = array_map(function (array $project): array {
            unset($project['_completion_date_samples']);
            return $project;
        }, $projects);
        $variations = $this->forecastVariations($contractualFinish, $forecast, $available);
        $value = $variations['p50'];
        $reason = $available ? null : $this->forecastUnavailableReason($projects, $progress);
        $status = !$available ? 'unavailable' : ($value > 0 ? 'delayed' : ($value < 0 ? 'ahead' : 'on_time'));
        $rangeDates = array_values(array_filter([$forecast['p10_finish'], $forecast['p90_finish']]));

        return [
            'value' => $value,
            'status' => $status,
            'availability' => $available,
            'metrics' => [
                'metric_key' => 'pg_finish_variance_days_p50',
                'definition' => 'Fecha final P50 simulada menos fecha final contractual del alcance filtrado.',
                'unit' => 'días calendario',
                'sign_convention' => [
                    'positive' => 'Terminación probable posterior al fin contractual.',
                    'negative' => 'Terminación probable anterior al fin contractual.',
                    'zero' => 'Terminación probable en la fecha contractual.',
                ],
                'status' => $status,
                'availability' => $available,
                'reason' => $reason,
                'contractual_finish' => $contractualFinish,
                'contractual_finish_basis' => 'first_available_snapshot_per_project',
                'forecast' => $forecast,
                'forecast_distribution_basis' => 'completion_date_samples_by_simulation',
                'portfolio_aggregation' => 'max_completion_date_per_simulation_then_percentiles',
                'variation_days' => $variations,
                'method' => 'monte_carlo_s_curve_current_production_prediction_interval',
                'simulation_count' => $available ? 240 : 0,
                'probable_range_80' => [
                    'confidence_level' => 0.8,
                    'earliest_finish' => $rangeDates ? min($rangeDates) : null,
                    'latest_finish' => $rangeDates ? max($rangeDates) : null,
                ],
                'cutoff' => (string) ($progress['cutoff'] ?? ''),
                'cutoff_label' => (string) ($progress['cutoff_label'] ?? ''),
                'project_cutoffs' => $context['current_project_cutoffs'] ?? [],
                'scope' => [
                    'project_ids' => array_values($projectIds),
                    'project_count' => count($projectIds),
                    'semana' => $semana,
                    'filters' => $filters,
                ],
                'project_breakdown' => $projectBreakdown,
            ],
        ];
    }

    private function programaContractualBaselineForCurrentCohort(array $projectIds, array $context): array
    {
        $cohortByProject = [];
        foreach ($context['baseline'] ?? [] as $row) {
            $projectId = (int) ($row['project_id'] ?? 0);
            $uniqueId = (int) ($row['unique_id'] ?? 0);
            if ($projectId > 0 && $uniqueId > 0) {
                $cohortByProject[$projectId][$uniqueId] = true;
            }
        }

        $contractualContext = $this->programaCurveContext(
            $this->fetchProgramaGeneralTrend($projectIds, '', $this->normalizeFilters([])),
        );
        $baseline = [];
        foreach ($contractualContext['contractual_baseline_by_project'] ?? [] as $projectId => $rows) {
            $cohort = $cohortByProject[(int) $projectId] ?? [];
            $baseline[(int) $projectId] = array_values(array_filter(
                $rows,
                static fn(array $row): bool => isset($cohort[(int) ($row['unique_id'] ?? 0)]),
            ));
        }
        foreach ($projectIds as $projectId) {
            $baseline[(int) $projectId] ??= [];
        }

        return $baseline;
    }

    private function programaProjectForecast(int $projectId, array $context, array $progress, array $contractualBaseline): array
    {
        $hasFilteredCohort = ($context['baseline'] ?? []) !== [];
        [, $contractualDate] = $this->programaBaselineDates($contractualBaseline);
        $contractualFinish = $contractualDate?->format('Y-m-d');
        $meta = $progress['projection_meta'] ?? [];
        $completionDates = $this->completionDateSamples(
            $progress['completion_week_samples'] ?? [],
            $progress['completion_point_dates'] ?? [],
        );
        $forecast = [
            'p10_finish' => $this->datePercentile($completionDates, 0.1),
            'p50_finish' => $this->datePercentile($completionDates, 0.5),
            'p90_finish' => $this->datePercentile($completionDates, 0.9),
        ];
        $available = ($meta['projection_available'] ?? false) === true
            && $contractualFinish !== null
            && count($completionDates) === (int) ($meta['simulation_count'] ?? 0);
        $variations = $this->forecastVariations($contractualFinish, $forecast, $available);
        $status = !$available
            ? 'unavailable'
            : ($variations['p50'] > 0 ? 'delayed' : ($variations['p50'] < 0 ? 'ahead' : 'on_time'));

        return [
            'project_id' => $projectId,
            'status' => $status,
            'availability' => $available,
            'reason' => $available
                ? null
                : (!$hasFilteredCohort
                    ? 'No hay actividades que coincidan con los filtros para este proyecto.'
                    : (string) ($meta['reason'] ?? 'No fue posible calcular la fecha final probabilística.')),
            'contractual_finish' => $contractualFinish,
            'p10_finish' => $available ? $forecast['p10_finish'] : null,
            'p50_finish' => $available ? $forecast['p50_finish'] : null,
            'p90_finish' => $available ? $forecast['p90_finish'] : null,
            'variation_days' => $variations,
            'cutoff' => (string) ($progress['cutoff'] ?? ''),
            'method' => (string) ($meta['method'] ?? ''),
            'simulation_count' => (int) ($meta['simulation_count'] ?? 0),
            'positive_increment_count' => (int) ($meta['positive_increment_count'] ?? 0),
            '_completion_date_samples' => $completionDates,
        ];
    }

    private function completionDateSamples(array $completionWeeks, array $pointDates): array
    {
        $dates = [];
        foreach ($completionWeeks as $index) {
            $date = $this->dateFromString((string) ($pointDates[$index] ?? ''));
            if ($date !== null) {
                $dates[] = $date->format('Y-m-d');
            }
        }
        return $dates;
    }

    private function datePercentile(array $dates, float $percentile): ?string
    {
        sort($dates, SORT_STRING);
        if ($dates === []) {
            return null;
        }
        $index = (int) floor((count($dates) - 1) * min(1.0, max(0.0, $percentile)));
        return $dates[$index] ?? null;
    }

    private function forecastVariations(?string $contractualFinish, array $forecast, bool $available): array
    {
        $variations = ['p10' => null, 'p50' => null, 'p90' => null];
        if (!$available || $contractualFinish === null) {
            return $variations;
        }
        foreach ([10, 50, 90] as $percentile) {
            $finish = $forecast["p{$percentile}_finish"] ?? null;
            if (is_string($finish) && $finish !== '') {
                $variations["p{$percentile}"] = (float) $this->dateDiffDays($contractualFinish, $finish);
            }
        }
        return $variations;
    }

    private function maxForecastDate(array $dates): ?string
    {
        $dates = array_values(array_filter($dates, fn($date): bool =>
            is_string($date) && $this->dateFromString($date) !== null
        ));
        return $dates ? max($dates) : null;
    }

    private function forecastUnavailableReason(array $projects, array $progress): string
    {
        foreach ($projects as $project) {
            if (($project['availability'] ?? false) !== true && ($project['reason'] ?? '') !== '') {
                return (string) $project['reason'];
            }
        }
        return (string) (($progress['projection_meta']['reason'] ?? '')
            ?: 'Se requieren al menos 3 incrementos positivos para calcular la fecha final probabilística.');
    }

    private function dateFromString(string $value): ?\DateTimeImmutable
    {
        if ($value === '') {
            return null;
        }

        try {
            return new \DateTimeImmutable($value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function programaProgressProjection(array $real, ?int $currentIndex, int $totalPoints): array
    {
        $empty = array_fill(0, $totalPoints, null);
        if ($currentIndex === null || $currentIndex < 0 || $totalPoints === 0) {
            return ['pessimistic' => $empty, 'likely' => $empty, 'optimistic' => $empty, 'meta' => []];
        }

        $observed = [];
        for ($i = 0; $i <= $currentIndex && $i < count($real); $i++) {
            if (is_numeric($real[$i])) {
                $observed[] = (float) $real[$i];
            }
        }
        $current = $observed ? (float) end($observed) : 0.0;
        $positiveIncrements = $this->positiveProductionIncrementCount($observed);
        if ($positiveIncrements < 3) {
            return $this->unavailableProjection($current, $currentIndex, $totalPoints, $positiveIncrements);
        }
        $stats = $this->projectionStats($observed, $currentIndex);
        $projection = $this->monteCarloSCurveProjection($current, $currentIndex, $totalPoints, $stats, $observed);

        return [
            'pessimistic' => $projection['pessimistic'],
            'likely' => $projection['likely'],
            'optimistic' => $projection['optimistic'],
            'completion_week_samples' => $projection['completion_week_samples'],
            'meta' => [
                'method' => 'monte_carlo_s_curve_current_production_prediction_interval',
                'confidence_level' => 0.8,
                'simulation_count' => $projection['simulation_count'],
                'weekly_mean_pct' => round($stats['mean'], 4),
                'weekly_stddev_pct' => round($stats['stddev'], 4),
                'sustained_weekly_rate_pct' => round($stats['sustained_rate'], 4),
                'recent_weekly_rate_pct' => round($stats['recent_rate'], 4),
                'last_weekly_rate_pct' => round($stats['last_week_rate'], 4),
                'momentum_weekly_rate_pct' => round($stats['momentum_rate'], 4),
                'active_week_ratio' => round($stats['active_week_ratio'], 4),
                'historical_reliability_factor' => round($stats['historical_reliability'], 4),
                'current_weekly_rate_pct' => round($stats['production_rate'], 4),
                'initial_projection_rate_pct' => round($stats['initial_rate'], 4),
                'confidence_half_width_pct' => round($stats['interval_half_width'], 4),
                'weekly_acceleration_cap_pct' => round($stats['acceleration_cap'], 4),
                'pessimistic_weekly_rate_pct' => round($projection['rate_quantiles']['p10'], 4),
                'likely_weekly_rate_pct' => round($projection['rate_quantiles']['p50'], 4),
                'optimistic_weekly_rate_pct' => round($projection['rate_quantiles']['p90'], 4),
                'pessimistic_completion_week' => $this->projectionCompletionWeek($projection['pessimistic']),
                'likely_completion_week' => $this->projectionCompletionWeek($projection['likely']),
                'optimistic_completion_week' => $this->projectionCompletionWeek($projection['optimistic']),
                'sample_size' => $stats['sample_size'],
                'projection_available' => true,
                'positive_increment_count' => $positiveIncrements,
                'minimum_positive_increments' => 3,
            ],
        ];
    }

    private function positiveProductionIncrementCount(array $observed): int
    {
        $count = 0;
        for ($i = 1; $i < count($observed); $i++) {
            if (((float) $observed[$i] - (float) $observed[$i - 1]) > 0.05) {
                $count++;
            }
        }

        return $count;
    }

    private function unavailableProjection(float $current, int $currentIndex, int $totalPoints, int $positiveIncrements): array
    {
        $series = array_fill(0, $totalPoints, null);
        if ($currentIndex >= 0 && $currentIndex < $totalPoints) {
            $series[$currentIndex] = round($this->clampPercent($current), 1);
        }

        return [
            'pessimistic' => $series,
            'likely' => $series,
            'optimistic' => $series,
            'completion_week_samples' => [],
            'meta' => [
                'method' => 'insufficient_historical_production_for_monte_carlo_s_curve',
                'projection_available' => false,
                'reason' => 'Se requieren al menos 3 cortes con avance real positivo para activar proyecciones.',
                'confidence_level' => 0.8,
                'simulation_count' => 0,
                'sample_size' => max(0, $currentIndex),
                'positive_increment_count' => $positiveIncrements,
                'minimum_positive_increments' => 3,
            ],
        ];
    }

    private function monteCarloSCurveProjection(float $current, int $currentIndex, int $totalPoints, array $stats, array $observed): array
    {
        $simulationCount = 240;
        $seed = $this->projectionSeed($observed, $currentIndex);
        $trajectories = [];
        $rates = [];

        for ($i = 0; $i < $simulationCount; $i++) {
            $rate = $this->sampleWeeklyProductionRate($seed, $stats);
            $rates[] = $rate;
            $initialRate = max($stats['initial_rate'], min($rate, $stats['last_week_rate']));
            $trajectories[] = $this->interpolatedSCurveProjectionSeries($current, $currentIndex, $totalPoints, $rate, $initialRate);
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

            $pessimistic[$index] = round($this->percentile($values, 0.1), 1);
            $likely[$index] = round($this->percentile($values, 0.5), 1);
            $optimistic[$index] = round($this->percentile($values, 0.9), 1);
        }
        $projectionPoints = max(
            $totalPoints,
            ($this->projectionCompletionWeek($pessimistic) ?? $projectionPoints - 1) + 1,
            ($this->projectionCompletionWeek($likely) ?? $projectionPoints - 1) + 1,
            ($this->projectionCompletionWeek($optimistic) ?? $projectionPoints - 1) + 1,
        );

        return [
            'pessimistic' => array_slice($pessimistic, 0, $projectionPoints),
            'likely' => array_slice($likely, 0, $projectionPoints),
            'optimistic' => array_slice($optimistic, 0, $projectionPoints),
            'completion_week_samples' => array_map(
                fn(array $trajectory): ?int => $this->projectionCompletionWeek($trajectory),
                $trajectories,
            ),
            'rate_quantiles' => [
                'p10' => $this->percentile($rates, 0.1),
                'p50' => $this->percentile($rates, 0.5),
                'p90' => $this->percentile($rates, 0.9),
            ],
            'simulation_count' => $simulationCount,
        ];
    }

    private function sampleWeeklyProductionRate(int &$seed, array $stats): float
    {
        $min = max(0.0, (float) ($stats['pessimistic_rate'] ?? 0.0));
        $mode = max($min, (float) ($stats['likely_rate'] ?? $min));
        $max = max($mode, (float) ($stats['optimistic_rate'] ?? $mode));
        if (($max - $min) <= 0.000001) {
            return $mode;
        }
        $u = $this->seededUniform($seed);
        $pivot = ($mode - $min) / ($max - $min);

        if ($u < $pivot) {
            return $min + sqrt($u * ($max - $min) * ($mode - $min));
        }

        return $max - sqrt((1.0 - $u) * ($max - $min) * ($max - $mode));
    }

    private function interpolatedSCurveProjectionSeries(float $current, int $currentIndex, int $totalPoints, float $targetRate, float $initialRate): array
    {
        $series = array_fill(0, max($totalPoints, $currentIndex + 1), null);
        $current = $this->clampPercent($current);
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
            $shape = $this->hermiteSInterpolation($t, $slopeRatio);
            $index = $currentIndex + $horizon;
            $series[$index] = round($this->clampPercent($current + ($remaining * $shape)), 1);
        }
        $series[$currentIndex + $completionHorizon] = 100.0;

        return $series;
    }

    private function projectionSeed(array $observed, int $currentIndex): int
    {
        $seed = crc32(json_encode([$observed, $currentIndex], JSON_THROW_ON_ERROR));
        return max(1, (int) ($seed % 2147483646));
    }

    private function seededUniform(int &$seed): float
    {
        $seed = (int) (($seed * 48271) % 2147483647);
        return max(0.000001, min(0.999999, $seed / 2147483647));
    }

    private function seededNormal(int &$seed): float
    {
        $u1 = $this->seededUniform($seed);
        $u2 = $this->seededUniform($seed);

        return sqrt(-2.0 * log($u1)) * cos(2.0 * M_PI * $u2);
    }

    private function percentile(array $values, float $percentile): float
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

    private function hermiteSInterpolation(float $t, float $initialSlopeRatio): float
    {
        $t = min(1.0, max(0.0, $t));
        $t2 = $t * $t;
        $t3 = $t2 * $t;
        $shape = (3.0 * $t2) - (2.0 * $t3) + ($initialSlopeRatio * ($t3 - (2.0 * $t2) + $t));

        return min(1.0, max(0.0, $shape));
    }

    private function padProjectionSeries(array $series, int $targetCount): array
    {
        $last = null;
        for ($i = count($series) - 1; $i >= 0; $i--) {
            if (is_numeric($series[$i] ?? null)) {
                $last = (float) $series[$i];
                break;
            }
        }

        while (count($series) < $targetCount) {
            $series[] = $last !== null && $last >= 100.0 ? 100.0 : null;
        }

        return $series;
    }

    private function projectionCompletionWeek(array $series): ?int
    {
        foreach ($series as $index => $value) {
            if (is_numeric($value) && (float) $value >= 100.0) {
                return (int) $index;
            }
        }

        return null;
    }

    private function projectionStats(array $observed, int $currentIndex): array
    {
        $increments = [];
        for ($i = 1; $i < count($observed); $i++) {
            $increments[] = max(0.0, (float) $observed[$i] - (float) $observed[$i - 1]);
        }
        $current = $observed ? (float) end($observed) : 0.0;
        $sustainedRate = $current > 0.0 ? $current / max(1, $currentIndex) : 0.0;
        $recentRate = $this->recentWeightedRate($increments);
        $recentPositive = count(array_filter(array_slice($increments, -3), fn($value) => $value > 0.05));
        $sampleSize = count($increments);
        $lastWeekRate = $increments ? (float) end($increments) : 0.0;
        $activeWeekCount = count(array_filter($increments, fn($value) => $value > 0.05));
        $activeWeekRatio = $sampleSize > 0 ? $activeWeekCount / $sampleSize : 0.0;
        $historicalReliability = min(1.0, max(0.55, 0.65 + ($activeWeekRatio * 0.35)));
        $mean = $increments ? array_sum($increments) / count($increments) : 0.0;
        $stddev = $this->sampleStddev($increments);
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

    private function recentWeightedRate(array $increments): float
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

    private function sampleStddev(array $values): float
    {
        if (count($values) < 2) {
            return 0.0;
        }
        $mean = array_sum($values) / count($values);
        $variance = array_sum(array_map(fn($value) => (($value - $mean) ** 2), $values)) / (count($values) - 1);
        return sqrt(max(0.0, $variance));
    }

    private function clampPercent(float $value): float
    {
        return min(100.0, max(0.0, $value));
    }

    private function programaCurveWeight(array $row): float
    {
        $weight = $this->number($row['curve_weight'] ?? 0);
        if ($weight > 0) {
            return $weight;
        }

        $duration = $this->number($row['duration_days'] ?? 0);
        return $duration > 0 ? $duration : 1.0;
    }

    private function plannedProgressAtCutoff(array $row, string $cutoff): float
    {
        $start = (string) ($row['Fecha_Inicio'] ?? '');
        $finish = (string) ($row['Fecha_Fin'] ?? '');
        if ($start === '' || $finish === '' || $cutoff === '') {
            return 0.0;
        }

        try {
            $startDate = new \DateTimeImmutable($start);
            $finishDate = new \DateTimeImmutable($finish);
            $cutoffDate = new \DateTimeImmutable($cutoff);
        } catch (\Throwable) {
            return 0.0;
        }

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

    private function earnedValueWeight(int $projectId, int $uniqueId): float
    {
        static $cache = [];
        $key = $projectId . ':' . $uniqueId;
        if (array_key_exists($key, $cache)) {
            return $cache[$key];
        }

        $stmt = $this->db->prepare(
            "SELECT COALESCE(NULLIF(cantidad_ppto, 0), NULLIF(DATEDIFF(Fecha_Fin, Fecha_Inicio), 0), 1) AS weight
             FROM programa_consolidado
             WHERE project_id = ? AND unique_id = ?
             ORDER BY Semana DESC
             LIMIT 1",
        );
        $stmt->execute([$projectId, $uniqueId]);
        $cache[$key] = max(1.0, $this->number($stmt->fetchColumn() ?: 1));
        return $cache[$key];
    }

    private function categoryCounts(array $rows, string $categoryField, string $flagField): array
    {
        $counts = [];
        foreach ($rows as $row) {
            if (($row[$flagField] ?? 0) != 1) {
                continue;
            }
            $category = $this->causalCategoryMeta((string) ($row[$categoryField] ?? ''))['canonical'];
            $counts[$category] = ($counts[$category] ?? 0) + 1;
        }
        arsort($counts);

        return [
            'labels' => array_keys($counts ?: ['Sin registros' => 0]),
            'values' => array_map('floatval', array_values($counts ?: ['Sin registros' => 0])),
        ];
    }

    private function causalMetricSeries(array $metrics): array
    {
        $labels = [];
        $values = [];
        foreach (($metrics['categories'] ?? []) as $category) {
            if (!is_array($category)) {
                continue;
            }
            $labels[] = (string) ($category['category'] ?? 'Sin categoría');
            $values[] = (float) ($category['count'] ?? 0);
        }

        return [
            'labels' => $labels ?: ['Sin registros'],
            'values' => $values ?: [0.0],
        ];
    }

    private function programaCnpMetrics(array $rows): array
    {
        $payload = $this->programaCausalDetailPayload($rows, 'cnp', '');
        $summary = $payload['summary'];
        $total = max(0, (int) ($summary['total'] ?? 0));
        $categories = [];
        foreach (($summary['categories'] ?? []) as $category => $count) {
            $count = (int) $count;
            $categories[] = [
                'category' => (string) $category,
                'count' => $count,
                'share_pct' => $total > 0 ? round(($count / $total) * 100, 1) : 0.0,
                'critical_count' => (int) ($summary['critical_by_category'][$category] ?? 0),
            ];
        }

        return [
            'metric_key' => 'pg_cnp_activity_count',
            'total' => $total,
            'critical_count' => (int) ($summary['critical_count'] ?? 0),
            'overdue_start_count' => (int) ($summary['overdue_start_count'] ?? 0),
            'due_next_7_days_count' => (int) ($summary['due_next_7_days_count'] ?? 0),
            'unassigned_responsible_count' => (int) ($summary['unassigned_responsible_count'] ?? 0),
            'unassigned_subcontractor_count' => (int) ($summary['unassigned_subcontractor_count'] ?? 0),
            'unknown_category_count' => (int) ($summary['unknown_category_count'] ?? 0),
            'categories' => $categories,
            'project_breakdown' => array_values($summary['project_breakdown'] ?? []),
            'population_definition' => "Activa = '0' y CNP registrada",
            'source_relation' => 'programacion_semanal',
            'source_relations' => ['programacion_semanal', 'semanas_activas'],
            'cutoff_source_relation' => 'semanas_activas',
            'grain' => 'project_id + Semana + Consecutivo',
            'category_catalog_version' => '1.0',
        ];
    }

    private function programaCncMetrics(array $rows): array
    {
        $payload = $this->programaCausalDetailPayload($rows, 'cnc', '');
        $summary = $payload['summary'];
        $total = max(0, (int) ($summary['total'] ?? 0));
        $categories = [];
        foreach (($summary['categories'] ?? []) as $category => $count) {
            $count = (int) $count;
            $categories[] = [
                'category' => (string) $category,
                'count' => $count,
                'share_pct' => $total > 0 ? round(($count / $total) * 100, 1) : 0.0,
                'critical_count' => (int) ($summary['critical_by_category'][$category] ?? 0),
            ];
        }

        return [
            'metric_key' => 'pg_cnc_activity_count',
            'total' => $total,
            'critical_count' => (int) ($summary['critical_count'] ?? 0),
            'zero_execution_count' => (int) ($summary['zero_execution_count'] ?? 0),
            'partial_execution_count' => (int) ($summary['partial_execution_count'] ?? 0),
            'severe_gap_count' => (int) ($summary['severe_gap_count'] ?? 0),
            'missing_observation_count' => (int) ($summary['missing_observation_count'] ?? 0),
            'unassigned_responsible_count' => (int) ($summary['unassigned_responsible_count'] ?? 0),
            'unassigned_subcontractor_count' => (int) ($summary['unassigned_subcontractor_count'] ?? 0),
            'unknown_category_count' => (int) ($summary['unknown_category_count'] ?? 0),
            'completion_sample_size' => (int) ($summary['completion_sample_size'] ?? 0),
            'average_completion_pct' => $summary['average_completion_pct'] ?? null,
            'categories' => $categories,
            'project_breakdown' => array_values($summary['project_breakdown'] ?? []),
            'population_definition' => "Activa IN ('1', 'NA') y CNC registrada",
            'source_relation' => 'programacion_semanal',
            'source_relations' => ['programacion_semanal', 'semanas_activas'],
            'cutoff_source_relation' => 'semanas_activas',
            'grain' => 'project_id + Semana + Consecutivo',
            'category_catalog_version' => '1.0',
            'aggregation_policy' => 'Conteo de actividades únicas; el cumplimiento medio es el promedio simple de porcentajes por actividad con compromiso válido.',
        ];
    }

    private function causalCategoryMeta(string $value): array
    {
        $original = trim(preg_replace('/\s+/', ' ', $value) ?? '');
        $key = $this->causalCategoryKey($original);
        $aliases = $this->causalCategoryAliases();
        return ['original' => $original, 'canonical' => $aliases[$key] ?? ($original ?: 'Sin categoría'), 'known' => isset($aliases[$key])];
    }

    private function causalCategorySqlPredicate(string $kind, string $category, array &$params): string
    {
        if (trim($category) === '' || !in_array($kind, ['cnp', 'cnc'], true)) {
            return '';
        }

        $canonical = $this->causalCategoryMeta($category)['canonical'];
        $acceptedKeys = array_keys(array_filter(
            $this->causalCategoryAliases(),
            static fn(string $label): bool => $label === $canonical,
        ));
        if ($acceptedKeys === []) {
            $acceptedKeys[] = $this->causalCategoryKey($category);
        }
        array_push($params, ...$acceptedKeys);
        $field = $kind === 'cnp' ? 'ps.Categoria_CNP' : 'ps.Categoria_CNC';
        $normalized = "LOWER(TRIM(COALESCE({$field}, '')))";
        foreach (['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n'] as $accent => $plain) {
            $normalized = "REPLACE({$normalized}, '{$accent}', '{$plain}')";
        }
        $placeholders = implode(',', array_fill(0, count($acceptedKeys), '?'));

        return " AND {$normalized} IN ({$placeholders})";
    }

    private function causalCategoryKey(string $value): string
    {
        $normalized = trim(preg_replace('/\s+/', ' ', $value) ?? '');
        $lower = function_exists('mb_strtolower') ? mb_strtolower($normalized, 'UTF-8') : strtolower($normalized);
        return strtr($lower, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n']);
    }

    private function causalCategoryAliases(): array
    {
        return [
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
    }

    private function programaCausalDetailPayload(array $rows, string $kind, string $category): array
    {
        $fields = $kind === 'cnp' ? ['flag' => 'is_cnp_population', 'category' => 'Categoria_CNP', 'cause' => 'CNP', 'observations' => 'Observaciones_CNP', 'route' => '/programacion-semanal/cnp'] : ['flag' => 'is_cnc_population', 'category' => 'Categoria_CNC', 'cause' => 'CNC', 'observations' => 'Observaciones_CNC', 'route' => '/programacion-semanal/cnc'];
        $categoryFilter = $this->causalCategoryMeta($category)['canonical'];
        $selected = [];
        foreach ($rows as $row) {
            if (($row[$fields['flag']] ?? 0) != 1) continue;
            $meta = $this->causalCategoryMeta((string) ($row[$fields['category']] ?? ''));
            if (trim($category) !== '' && $meta['canonical'] !== $categoryFilter) continue;
            $key = implode(':', [(int) ($row['project_id'] ?? 0), (int) ($row['Semana'] ?? 0), (int) ($row['Consecutivo'] ?? 0)]);
            $selected[$key] = $this->programaCausalActivity($row, $meta, $fields, $kind);
        }
        $activities = array_values($selected);
        usort($activities, static fn(array $a, array $b): int => [$a['project'], $a['semana'], $a['consecutivo']] <=> [$b['project'], $b['semana'], $b['consecutivo']]);
        $categories = [];
        $criticalByCategory = [];
        $projectBreakdown = [];
        $summary = [
            'total' => count($activities),
            'critical_count' => 0,
            'overdue_start_count' => 0,
            'due_next_7_days_count' => 0,
            'unassigned_responsible_count' => 0,
            'unassigned_subcontractor_count' => 0,
            'unknown_category_count' => 0,
            'zero_execution_count' => 0,
            'partial_execution_count' => 0,
            'severe_gap_count' => 0,
            'missing_observation_count' => 0,
            'completion_sample_size' => 0,
            'average_completion_pct' => null,
            'priority_counts' => [],
        ];
        $completionValues = [];
        foreach ($activities as $activity) {
            $categoryKey = (string) $activity['category_canonical'];
            $categories[$categoryKey] = ($categories[$categoryKey] ?? 0) + 1;
            $summary['unknown_category_count'] += $activity['category_known'] ? 0 : 1;
            $summary['critical_count'] += $activity['critical'] ? 1 : 0;
            $summary['overdue_start_count'] += $activity['start_status'] === 'overdue' ? 1 : 0;
            $summary['due_next_7_days_count'] += in_array($activity['start_status'], ['due_today', 'next_7_days'], true) ? 1 : 0;
            $summary['unassigned_responsible_count'] += $activity['responsible'] === '' ? 1 : 0;
            $summary['unassigned_subcontractor_count'] += $activity['subcontractor'] === '' ? 1 : 0;
            $summary['priority_counts'][$activity['priority']] = ($summary['priority_counts'][$activity['priority']] ?? 0) + 1;
            if ($kind === 'cnc') {
                $summary['zero_execution_count'] += $activity['execution_status'] === 'not_executed' ? 1 : 0;
                $summary['partial_execution_count'] += $activity['execution_status'] === 'partial' ? 1 : 0;
                $summary['severe_gap_count'] += is_numeric($activity['shortfall_pct']) && (float) $activity['shortfall_pct'] >= 50 ? 1 : 0;
                $summary['missing_observation_count'] += $activity['observations'] === '' ? 1 : 0;
                if (is_numeric($activity['completion_pct'])) {
                    $completionValues[] = (float) $activity['completion_pct'];
                }
            }
            if ($activity['critical']) {
                $criticalByCategory[$categoryKey] = ($criticalByCategory[$categoryKey] ?? 0) + 1;
            }

            $projectKey = (string) $activity['project_id'];
            if (!isset($projectBreakdown[$projectKey])) {
                $projectBreakdown[$projectKey] = [
                    'project_id' => $activity['project_id'],
                    'project' => $activity['project'],
                    'total' => 0,
                    'critical_count' => 0,
                    'overdue_start_count' => 0,
                    'unassigned_responsible_count' => 0,
                    'unassigned_subcontractor_count' => 0,
                    'zero_execution_count' => 0,
                    'partial_execution_count' => 0,
                    'severe_gap_count' => 0,
                ];
            }
            $projectBreakdown[$projectKey]['total']++;
            $projectBreakdown[$projectKey]['critical_count'] += $activity['critical'] ? 1 : 0;
            $projectBreakdown[$projectKey]['overdue_start_count'] += $activity['start_status'] === 'overdue' ? 1 : 0;
            $projectBreakdown[$projectKey]['unassigned_responsible_count'] += $activity['responsible'] === '' ? 1 : 0;
            $projectBreakdown[$projectKey]['unassigned_subcontractor_count'] += $activity['subcontractor'] === '' ? 1 : 0;
            if ($kind === 'cnc') {
                $projectBreakdown[$projectKey]['zero_execution_count'] += $activity['execution_status'] === 'not_executed' ? 1 : 0;
                $projectBreakdown[$projectKey]['partial_execution_count'] += $activity['execution_status'] === 'partial' ? 1 : 0;
                $projectBreakdown[$projectKey]['severe_gap_count'] += is_numeric($activity['shortfall_pct']) && (float) $activity['shortfall_pct'] >= 50 ? 1 : 0;
            }
        }
        $summary['completion_sample_size'] = count($completionValues);
        $summary['average_completion_pct'] = $completionValues === []
            ? null
            : round(array_sum($completionValues) / count($completionValues), 1);
        arsort($categories);
        $summary['categories'] = $categories;
        $summary['critical_by_category'] = $criticalByCategory;
        $summary['project_breakdown'] = $projectBreakdown;
        return ['summary' => $summary, 'empty' => $summary['total'] === 0, 'activities' => $activities];
    }

    private function programaCausalActivity(array $row, array $category, array $fields, string $kind): array
    {
        $projectId = (int) ($row['project_id'] ?? 0);
        $semana = (int) ($row['Semana'] ?? 0);
        $activity = html_entity_decode(strip_tags((string) ($row['Actividad'] ?? '')), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $activity = preg_replace('/\s+/u', ' ', trim($activity)) ?: 'Actividad sin nombre';
        $responsible = trim((string) ($row['Responsable_AIA'] ?? ''));
        $subcontractor = trim((string) ($row['Sub_Contratista'] ?? ''));
        $critical = (int) ($row['Critica'] ?? 0) === 1;
        $cutoff = $this->dateOrBlank($row['cutoff'] ?? '');
        $startDate = $this->dateOrBlank($row['Fecha_Inicio'] ?? '');
        $daysToStart = $cutoff !== '' && $startDate !== '' ? $this->dateDiffDays($cutoff, $startDate) : null;
        $startStatus = match (true) {
            $daysToStart === null => 'unknown',
            $daysToStart < 0 => 'overdue',
            $daysToStart === 0 => 'due_today',
            $daysToStart <= 7 => 'next_7_days',
            default => 'future',
        };
        $commitment = is_numeric($row['Compromiso'] ?? null) ? (float) $row['Compromiso'] : null;
        $executed = is_numeric($row['Ejecutado_Real'] ?? null) ? (float) $row['Ejecutado_Real'] : null;
        $completionPct = $commitment !== null && $commitment > 0 && $executed !== null
            ? round(max(0.0, ($executed / $commitment) * 100), 1)
            : null;
        $shortfall = $commitment !== null && $executed !== null
            ? round(max(0.0, $commitment - $executed), 2)
            : null;
        $shortfallPct = $commitment !== null && $commitment > 0 && $shortfall !== null
            ? round(($shortfall / $commitment) * 100, 1)
            : null;
        $executionStatus = match (true) {
            $completionPct === null => 'unknown',
            $executed !== null && $executed <= 0 => 'not_executed',
            $completionPct < 100 => 'partial',
            default => 'met',
        };
        $priority = $kind === 'cnc'
            ? match (true) {
                $critical && is_numeric($shortfallPct) && $shortfallPct >= 50 => 'critical',
                $critical || (is_numeric($shortfallPct) && $shortfallPct >= 50) => 'high',
                $executionStatus === 'partial' => 'warning',
                default => 'monitor',
            }
            : match (true) {
                $critical && in_array($startStatus, ['overdue', 'due_today'], true) => 'critical',
                $critical || $startStatus === 'overdue' => 'high',
                in_array($startStatus, ['due_today', 'next_7_days'], true) => 'warning',
                default => 'monitor',
            };
        return [
            'project_id' => $projectId, 'project' => (string) ($row['project'] ?? "Proyecto {$projectId}"),
            'semana' => $semana, 'consecutivo' => (int) ($row['Consecutivo'] ?? 0),
            'source_row_key' => implode(':', [$projectId, $semana, (int) ($row['Consecutivo'] ?? 0)]),
            'activity' => $activity,
            'location' => trim((string) ($row['Ubicacion'] ?? '')),
            'category_original' => $category['original'], 'category_canonical' => $category['canonical'],
            'category_known' => $category['known'], 'cause' => trim((string) ($row[$fields['cause']] ?? '')),
            'observations' => trim((string) ($row[$fields['observations']] ?? '')),
            'responsible' => $responsible,
            'subcontractor' => $subcontractor,
            'start_date' => $startDate ?: null, 'finish_date' => $this->dateOrBlank($row['Fecha_Fin'] ?? '') ?: null,
            'cutoff' => $cutoff ?: null,
            'days_to_start' => $daysToStart,
            'start_status' => $startStatus,
            'committed_quantity' => $commitment,
            'executed_quantity' => $executed,
            'completion_pct' => $completionPct,
            'shortfall_quantity' => $shortfall,
            'shortfall_pct' => $shortfallPct,
            'unit' => trim((string) ($row['Unidad'] ?? '')),
            'execution_status' => $executionStatus,
            'priority' => $priority,
            'critical' => $critical,
            'impact' => $this->programaCausalImpact($kind, $critical, $startStatus, $executionStatus, $shortfallPct),
            'recommended_action' => $this->programaCausalRecommendedAction($kind, $critical, $startStatus, $responsible, $subcontractor, $executionStatus, $shortfallPct),
            'operational_link' => null,
            'action_available' => false,
            'read_only' => true,
        ];
    }

    private function programaCausalImpact(
        string $kind,
        bool $critical,
        string $startStatus,
        string $executionStatus,
        ?float $shortfallPct,
    ): string
    {
        if ($kind === 'cnc') {
            $gap = $shortfallPct === null ? 'sin una brecha cuantificable' : sprintf('con una brecha de %.1f%% del compromiso', $shortfallPct);
            if ($critical && $executionStatus === 'not_executed') {
                return "Compromiso crítico sin ejecución, {$gap}; puede afectar la secuencia y la fecha final.";
            }
            if ($critical) {
                return "Compromiso crítico parcialmente ejecutado, {$gap}; reduce la holgura disponible.";
            }
            if ($executionStatus === 'not_executed') {
                return "El compromiso quedó sin ejecución, {$gap}; traslada toda la carga al plan de recuperación.";
            }
            if ($executionStatus === 'partial') {
                return "El compromiso se ejecutó parcialmente, {$gap}; la cantidad faltante debe reprogramarse.";
            }
            if ($executionStatus === 'unknown') {
                return 'La CNC está registrada, pero faltan cantidades comparables para medir la magnitud del incumplimiento.';
            }
            return 'La CNC permanece registrada aunque las cantidades indican cumplimiento; se debe validar la consistencia del cierre.';
        }
        if ($critical && in_array($startStatus, ['overdue', 'due_today'], true)) {
            return 'Actividad de ruta crítica no programada con inicio exigible; puede desplazar la fecha final.';
        }
        if ($critical) {
            return 'Actividad de ruta crítica fuera del compromiso; puede consumir holgura y afectar la fecha final.';
        }
        if ($startStatus === 'overdue') {
            return 'La actividad ya debía iniciar; reduce la reserva de trabajo y puede afectar actividades sucesoras.';
        }
        if (in_array($startStatus, ['due_today', 'next_7_days'], true)) {
            return 'La actividad debe iniciar dentro de los próximos 7 días y aún no está programada.';
        }
        if ($startStatus === 'unknown') {
            return 'Sin fecha de inicio válida no puede estimarse la urgencia de esta causa.';
        }
        return 'La actividad sigue fuera del compromiso; la causa debe cerrarse antes de entrar a ejecución.';
    }

    private function programaCausalRecommendedAction(
        string $kind,
        bool $critical,
        string $startStatus,
        string $responsible,
        string $subcontractor,
        string $executionStatus,
        ?float $shortfallPct,
    ): string {
        $actions = [];
        if ($responsible === '') {
            $actions[] = 'asignar Responsable AIA';
        }
        if ($subcontractor === '') {
            $actions[] = 'definir Subcontratista';
        }
        if ($kind === 'cnc') {
            if ($executionStatus === 'unknown') {
                $actions[] = 'corregir compromiso y ejecución real antes de decidir la recuperación';
            } elseif ($critical || (is_numeric($shortfallPct) && $shortfallPct >= 50)) {
                $actions[] = 'confirmar la causa raíz y acordar hoy cantidad, recursos y fecha de recuperación';
            } elseif ($executionStatus === 'partial') {
                $actions[] = 'reprogramar la cantidad faltante y confirmar capacidad para el siguiente corte';
            } else {
                $actions[] = 'validar la CNC y cerrar la inconsistencia del registro';
            }
        } elseif ($critical || in_array($startStatus, ['overdue', 'due_today'], true)) {
            $actions[] = 'resolver la causa y acordar una fecha de recuperación';
        } elseif ($startStatus === 'next_7_days') {
            $actions[] = 'cerrar la causa antes del próximo compromiso semanal';
        } elseif ($startStatus === 'unknown') {
            $actions[] = 'corregir la fecha planificada y revisar la causa';
        } else {
            $actions[] = 'hacer seguimiento al cierre de la causa';
        }

        return ucfirst(implode('; ', $actions)) . '.';
    }

    private function scheduleCompliancePct(float $real, float $planned): float
    {
        if ($planned <= 0) {
            return $real > 0 ? 100.0 : 0.0;
        }

        return round(min(150.0, max(0.0, ($real / $planned) * 100)), 1);
    }

    private function semanticMetricRange(float $value, string $vocabulary): array
    {
        $isCompliance = $vocabulary === 'compliance';
        if ($value < 70.0) {
            return ['key' => 'critical', 'label' => $isCompliance ? 'No Cumple' : 'Inaceptable', 'color_token' => 'status-critical'];
        }
        if ($value < 90.0) {
            return ['key' => 'warning', 'label' => $isCompliance ? 'Cumple Parcialmente' : 'Aceptable', 'color_token' => 'status-warning'];
        }
        return ['key' => 'success', 'label' => $isCompliance ? 'Cumple' : 'Excelente', 'color_token' => 'status-success'];
    }

    private function schedulePerformanceRange(float $real, float $planned): array
    {
        $performance = $this->scheduleCompliancePct($real, $planned);
        if ($performance < 95.0) return ['key' => 'critical', 'label' => 'Atrasado', 'color_token' => 'status-critical'];
        if ($performance > 105.0) return ['key' => 'success', 'label' => 'Adelantado', 'color_token' => 'status-success'];
        return ['key' => 'warning', 'label' => 'A Tiempo', 'color_token' => 'status-warning'];
    }

    private function programaProgressDetailPayload(array $trend): array
    {
        $context = $this->programaCurveContext($trend);
        $summary = $this->programaSnapshotSummary($context);
        $projectNames = $this->programaProjectNames($context['baseline'] ?? []);
        $activities = $this->programaProgressActivities($context, $projectNames);

        return ['summary' => $summary, 'groups' => $this->programaProgressGroups($activities), 'activities' => $activities];
    }

    private function programaProjectNames(array $rows): array
    {
        $ids = array_values(array_unique(array_filter(array_map(
            static fn(array $row): int => (int) ($row['project_id'] ?? 0),
            $rows,
        ))));
        if (!$ids) return [];

        $projects = $this->queryAll(
            'SELECT ID, Proyecto_Proceso FROM general_proyectos_procesos WHERE ID IN (' . $this->inClause($ids) . ')',
            $ids,
        );
        return array_column($projects, 'Proyecto_Proceso', 'ID');
    }

    private function programaActivitySnapshot(array $rows): array
    {
        $context = $this->programaCurveContext($rows);
        $summary = $this->programaSnapshotSummary($context);
        $projectNames = $this->programaProjectNames($context['baseline'] ?? []);
        $activities = $this->programaProgressActivities($context, $projectNames);
        $limit = 25;

        return [
            'metric_key' => 'pg_activity_progress_contribution',
            'source_relations' => ['programa_consolidado', 'semanas_activas'],
            'grain' => 'project_id + Semana + unique_id',
            'summary' => $summary,
            'total' => count($activities),
            'activities' => array_slice($activities, 0, $limit),
            'pagination' => [
                'limit' => $limit,
                'offset' => 0,
                'total' => count($activities),
                'returned_count' => min($limit, count($activities)),
                'has_more' => count($activities) > $limit,
                'next_offset' => min($limit, count($activities)),
            ],
            'detail_endpoint' => '/api/bi/report/programa-general/progress-detail',
        ];
    }

    private function programaSnapshotSummary(array $context): array
    {
        $totalWeight = max(1.0, (float) ($context['total_weight'] ?? 0));
        $realWeighted = 0.0;
        $plannedWeighted = 0.0;
        foreach ($context['baseline'] ?? [] as $row) {
            $projectId = (int) ($row['project_id'] ?? 0);
            $cutoff = (string) (($context['current_project_cutoffs'][$projectId] ?? '') ?: $this->effectiveSnapshotCutoff($row));
            $weight = $this->programaCurveWeight($row);
            $realWeighted += $weight * min(1.0, max(0.0, $this->number($row['Ejecutado'] ?? 0)));
            $plannedWeighted += $weight * $this->plannedProgressAtCutoff($row, $cutoff);
        }

        $real = round(($realWeighted / $totalWeight) * 100, 1);
        $planned = round(($plannedWeighted / $totalWeight) * 100, 1);
        $gap = round($real - $planned, 1);

        return [
            'cutoff' => (string) ($context['current_cutoff'] ?? ''),
            'cutoff_label' => (string) ($context['current_cutoff_label'] ?? ($context['current_cutoff'] ?? '')),
            'project_cutoffs' => $context['current_project_cutoffs'] ?? [],
            'real_pct' => $real,
            'theoretical_pct' => $planned,
            'compliance_pct' => $this->scheduleCompliancePct($real, $planned),
            'gap_pp' => $gap,
            'unfulfilled_plan_pct' => round(max(0.0, 100 - $this->scheduleCompliancePct($real, $planned)), 1),
            'status' => $gap < -0.05 ? 'Atrasado' : ($gap > 0.05 ? 'Adelantado' : 'En línea'),
        ];
    }

    private function programaProgressActivities(array $context, array $projectNames): array
    {
        $totalWeight = max(1.0, (float) ($context['total_weight'] ?? 0));
        $activities = [];
        foreach ($context['baseline'] ?? [] as $row) {
            $projectId = (int) ($row['project_id'] ?? 0);
            $cutoff = (string) (($context['current_project_cutoffs'][$projectId] ?? '') ?: $this->effectiveSnapshotCutoff($row));
            $weight = $this->programaCurveWeight($row);
            $real = min(1.0, max(0.0, $this->number($row['Ejecutado'] ?? 0)));
            $planned = $this->plannedProgressAtCutoff($row, $cutoff);
            $plannedStart = (string) ($row['Fecha_Inicio'] ?? '');
            $plannedFinish = (string) ($row['Fecha_Fin'] ?? '');
            $late = $plannedFinish !== '' && $cutoff !== '' && $plannedFinish < $cutoff && $real < 1.0;
            $activity = $this->programaDisplayText(
                html_entity_decode(strip_tags((string) ($row['Actividad'] ?? '')), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            );
            $activity = preg_replace('/\s+/u', ' ', trim($activity)) ?: 'Actividad sin nombre';
            $activities[] = [
                'project_id' => $projectId, 'project' => $this->programaDisplayText((string) ($projectNames[$projectId] ?? "Proyecto {$projectId}")),
                'week' => (int) ($row['Semana'] ?? 0),
                'unique_id' => (int) ($row['unique_id'] ?? 0), 'id' => (string) ($row['Id'] ?? ''),
                'activity_key' => $projectId . ':' . (int) ($row['unique_id'] ?? 0),
                'activity' => $activity, 'stage' => $this->programaDisplayText((string) ($row['Estado'] ?? '')) ?: 'Sin etapa',
                'planned_start' => $plannedStart, 'planned_finish' => $plannedFinish, 'cutoff' => $cutoff,
                'duration_days' => (int) round($weight),
                'weight_pct' => round(($weight / $totalWeight) * 100, 2),
                'real_pct' => round($real * 100, 1), 'planned_pct' => round($planned * 100, 1),
                'gap_pp' => round(($real - $planned) * 100, 1),
                'real_contribution_pp' => round(($real * $weight / $totalWeight) * 100, 2),
                'planned_contribution_pp' => round(($planned * $weight / $totalWeight) * 100, 2),
                'recoverable_pp' => round((max(0.0, $planned - $real) * $weight / $totalWeight) * 100, 2),
                'state' => $real >= 1 ? 'Completada' : ($real > 0 ? 'En progreso' : 'No iniciada'),
                'critical' => (int) ($row['Ruta_Critica'] ?? 0) === 1,
                'late' => $late,
                'observed_delay_days' => $late ? $this->dateDiffDays($plannedFinish, $cutoff) : 0,
                'responsible' => $this->programaDisplayText((string) ($row['responsable_aia'] ?? '')) ?: 'Sin asignar',
                'subcontractor' => $this->programaDisplayText((string) ($row['sub_contratista'] ?? '')) ?: 'Sin asignar',
                'blocker' => $this->programaProgressBlocker($row),
            ];
        }
        usort($activities, static fn(array $a, array $b): int =>
            $b['recoverable_pp'] <=> $a['recoverable_pp']
            ?: $b['critical'] <=> $a['critical']
            ?: $b['real_contribution_pp'] <=> $a['real_contribution_pp']
            ?: $a['planned_finish'] <=> $b['planned_finish']
            ?: $a['activity_key'] <=> $b['activity_key']
        );
        return $activities;
    }

    private function programaDisplayText(string $value): string
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

    private function programaProgressBlocker(array $row): string
    {
        $checks = ['Materiales' => 'Materiales', 'MdeO' => 'Mano de obra', 'Equipos' => 'Equipos', 'D_y_E' => 'Diseño', 'Predecesora' => 'Predecesora'];
        foreach ($checks as $field => $label) {
            if ($this->number($row[$field] ?? 1) < ($field === 'Predecesora' ? 0.5 : 1.0)) return $label;
        }
        return 'Sin bloqueo registrado';
    }

    private function programaProgressGroups(array $activities): array
    {
        return [
            'project' => $this->aggregateProgramaProgressGroups($activities, 'project'),
            'stage' => $this->aggregateProgramaProgressGroups($activities, 'stage'),
            'responsible' => $this->aggregateProgramaProgressGroups($activities, 'responsible'),
            'subcontractor' => $this->aggregateProgramaProgressGroups($activities, 'subcontractor'),
        ];
    }

    private function aggregateProgramaProgressGroups(array $activities, string $field): array
    {
        $groups = [];
        foreach ($activities as $activity) {
            $label = trim((string) ($activity[$field] ?? '')) ?: 'Sin asignar';
            $groups[$label] ??= ['label' => $label, 'activity_count' => 0, 'real_contribution_pp' => 0.0, 'planned_contribution_pp' => 0.0, 'recoverable_pp' => 0.0];
            $groups[$label]['activity_count']++;
            foreach (['real_contribution_pp', 'planned_contribution_pp', 'recoverable_pp'] as $metric) {
                $groups[$label][$metric] = round($groups[$label][$metric] + (float) ($activity[$metric] ?? 0), 2);
            }
        }
        usort($groups, static fn(array $a, array $b): int => $b['recoverable_pp'] <=> $a['recoverable_pp']);
        return array_values($groups);
    }

    private function programaCompliancePayload(array $trend, ?array $context = null): array
    {
        $context = $context ?? $this->programaCurveContext($trend);
        $progress = $this->programaProgressSeries($trend, $context);
        $summary = $this->programaComplianceSummary($progress, $context);
        $activities = $this->programaComplianceActivities($context);
        $summary['delay_days'] = $this->programaDelayDays($context['current_rows'] ?? []);
        $summary['critical_delayed_count'] = count(array_filter($activities, fn($row) => $row['critical'] && $row['late']));
        $summary['activities_explaining_gap'] = count($activities);
        $summary['explanation'] = $this->programaComplianceExplanation($summary);

        return [
            'summary' => $summary,
            'explanation' => $summary['explanation'],
            'activities' => $activities,
        ];
    }

    private function programaComplianceSummary(array $progress, array $context): array
    {
        $index = $this->lastNumericIndex($progress['real'] ?? []);
        $real = $index === null ? 0.0 : (float) ($progress['real'][$index] ?? 0);
        $planned = $index === null ? 0.0 : (float) ($progress['theoretical'][$index] ?? 0);
        $compliance = $this->scheduleCompliancePct($real, $planned);
        $gap = round($real - $planned, 1);
        $status = $gap < -0.05 ? 'Atrasado' : ($gap > 0.05 ? 'Adelantado' : 'En línea');

        return [
            'cutoff' => (string) ($progress['cutoff'] ?? ''),
            'cutoff_label' => (string) ($progress['cutoff_label'] ?? ($progress['cutoff'] ?? '')),
            'project_cutoffs' => $context['current_project_cutoffs'] ?? [],
            'real_pct' => round($real, 1),
            'theoretical_pct' => round($planned, 1),
            'compliance_pct' => $compliance,
            'gap_pp' => $gap,
            'unfulfilled_plan_pct' => round(max(0.0, 100 - $compliance), 1),
            'status' => $status,
        ];
    }

    private function programaComplianceActivities(array $context): array
    {
        $totalWeight = max(1.0, (float) ($context['total_weight'] ?? 0));
        $activities = [];
        foreach ($context['baseline'] ?? [] as $row) {
            $projectId = (int) ($row['project_id'] ?? 0);
            $cutoff = (string) (($context['current_project_cutoffs'][$projectId] ?? '') ?: $this->effectiveSnapshotCutoff($row));
            $planned = round($this->plannedProgressAtCutoff($row, $cutoff) * 100, 1);
            $real = round(min(1.0, max(0.0, $this->number($row['Ejecutado'] ?? 0))) * 100, 1);
            $gap = round($real - $planned, 1);
            if ($gap >= -0.05) {
                continue;
            }
            $critical = (int) ($row['Ruta_Critica'] ?? 0) === 1;
            $finish = (string) ($row['Fecha_Fin'] ?? '');
            $late = $cutoff !== '' && $finish !== '' && $finish < $cutoff && $real < 100;
            $delayDays = $late ? max(0, $this->dateDiffDays($finish, $cutoff)) : 0;
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
            $weight = $this->programaCurveWeight($row);
            $activity = html_entity_decode(strip_tags((string) ($row['Actividad'] ?? '')), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $activity = preg_replace('/\s+/u', ' ', trim($activity)) ?: 'Actividad sin nombre';
            $activities[] = [
                'project_id' => $projectId,
                'unique_id' => (int) ($row['unique_id'] ?? 0),
                'id' => (string) ($row['Id'] ?? ''),
                'activity' => $activity,
                'planned_start' => (string) ($row['Fecha_Inicio'] ?? ''),
                'planned_finish' => $finish,
                'cutoff' => $cutoff,
                'planned_pct' => $planned,
                'real_pct' => $real,
                'gap_pp' => $gap,
                'contribution_pp' => round(($gap * $weight) / $totalWeight, 2),
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

    private function programaComplianceExplanation(array $summary): array
    {
        $gap = (float) ($summary['gap_pp'] ?? 0);
        $gapText = number_format(abs($gap), 1, ',', '.');
        if ($gap < -0.05) {
            $headline = "Faltan {$gapText} pp de avance frente al plan del corte.";
            $implication = 'El proyecto necesita recuperar producción para volver al cronograma.';
        } elseif ($gap > 0.05) {
            $headline = "El avance supera el plan del corte por {$gapText} pp.";
            $implication = 'El rendimiento actual protege la fecha prevista si se mantiene.';
        } else {
            $headline = 'El avance real coincide con el avance teórico del corte.';
            $implication = 'El proyecto se mantiene alineado con el cronograma.';
        }

        return ['headline' => $headline, 'implication' => $implication, 'method' => 'Detalle ordenado por ruta crítica y aporte ponderado a la brecha.'];
    }

    private function programaObservedDelayPayload(array $context): array
    {
        $projectNames = $this->programaProjectNames($context['baseline'] ?? []);
        $activities = [];
        $populationCount = 0;
        foreach ($context['baseline'] ?? [] as $row) {
            if ((int) ($row['Titulo'] ?? 0) === 1) {
                continue;
            }
            $projectId = (int) ($row['project_id'] ?? 0);
            $cutoff = (string) (($context['current_project_cutoffs'][$projectId] ?? '') ?: $this->effectiveSnapshotCutoff($row));
            $finish = trim((string) ($row['Fecha_Fin'] ?? ''));
            $real = min(1.0, max(0.0, $this->number($row['Ejecutado'] ?? 0)));
            if ($real >= 1.0 || $this->dateFromString($finish) === null || $this->dateFromString($cutoff) === null) {
                continue;
            }
            $populationCount++;
            $delayDays = $this->dateDiffDays($finish, $cutoff);
            if ($delayDays <= 0) {
                continue;
            }

            $planned = round($this->plannedProgressAtCutoff($row, $cutoff) * 100, 1);
            $realPct = round($real * 100, 1);
            $critical = (int) ($row['Ruta_Critica'] ?? 0) === 1;
            $activity = html_entity_decode(strip_tags((string) ($row['Actividad'] ?? '')), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $activity = preg_replace('/\s+/u', ' ', trim($activity)) ?: 'Actividad sin nombre';
            $activities[] = [
                'project_id' => $projectId,
                'project' => (string) ($projectNames[$projectId] ?? "Proyecto {$projectId}"),
                'unique_id' => (int) ($row['unique_id'] ?? 0),
                'id' => (string) ($row['Id'] ?? ''),
                'activity' => $activity,
                'planned_start' => (string) ($row['Fecha_Inicio'] ?? ''),
                'planned_finish' => $finish,
                'cutoff' => $cutoff,
                'observed_delay_days' => (float) $delayDays,
                'progress_pct' => $realPct,
                'planned_pct' => $planned,
                'gap_pp' => round($realPct - $planned, 1),
                'critical' => $critical,
                'responsible' => trim((string) ($row['responsable_aia'] ?? '')) ?: 'Sin asignar',
                'subcontractor' => trim((string) ($row['sub_contratista'] ?? '')) ?: 'Sin asignar',
                'cause' => $critical ? 'Ruta crítica vencida' : 'Actividad vencida al corte',
                'implication' => $critical
                    ? 'Puede desplazar la fecha final; requiere plan de recuperación inmediato.'
                    : 'Acumula trabajo vencido y reduce la holgura disponible.',
            ];
        }

        usort($activities, static fn(array $left, array $right): int =>
            [$right['critical'], $right['observed_delay_days'], $left['activity']]
            <=> [$left['critical'], $left['observed_delay_days'], $right['activity']]
        );
        $totalDays = array_sum(array_column($activities, 'observed_delay_days'));
        $maxDays = $activities === [] ? 0.0 : max(array_column($activities, 'observed_delay_days'));

        return [
            'summary' => [
                'metric_key' => 'pg_observed_activity_delay_days',
                'definition' => 'Días calendario transcurridos desde el fin planificado hasta el corte para actividades vencidas e incompletas.',
                'population_count' => $populationCount,
                'delayed_activity_count' => count($activities),
                'critical_delayed_count' => count(array_filter($activities, static fn(array $row): bool => $row['critical'])),
                'total_observed_delay_days' => round((float) $totalDays, 1),
                'max_observed_delay_days' => round((float) $maxDays, 1),
                'aggregation' => 'count, sum and max by activity; no forecast',
                'project_cutoffs' => $context['current_project_cutoffs'] ?? [],
            ],
            'activities' => $activities,
        ];
    }

    private function programaDelayDays(array $rows): float
    {
        $maxDelay = 0;
        $nearestAhead = null;
        foreach ($rows as $row) {
            $done = min(1.0, max(0.0, $this->number($row['Ejecutado'] ?? 0))) >= 1.0;
            $finish = (string) ($row['Fecha_Fin'] ?? '');
            $cutoff = $this->effectiveSnapshotCutoff($row);
            if ($done || $finish === '' || $cutoff === '') {
                continue;
            }
            $days = $this->dateDiffDays($finish, $cutoff);
            if ($days > 0) {
                $maxDelay = max($maxDelay, $days);
            } else {
                $nearestAhead = $nearestAhead === null ? $days : max($nearestAhead, $days);
            }
        }

        return (float) ($maxDelay > 0 ? $maxDelay : ($nearestAhead ?? 0));
    }

    private function programaRadar(array $rows): array
    {
        $definitions = [
            'productividad' => [
                'name' => 'Productividad',
                'label' => 'Avance promedio válido',
                'formula' => 'PROMEDIO(MIN(P_Completado válido, 1)) × 100; válido cuando P_Completado es mayor o igual que 0.',
                'value' => fn(array $row): ?float => $this->radarProgressValue($row),
            ],
            'eficiencia' => [
                'name' => 'Eficiencia',
                'label' => 'Eficiencia de ejecución',
                'formula' => 'PROMEDIO(Ejecutado_Real / Compromiso por fila válida) × 100; no suma unidades distintas.',
                'value' => fn(array $row): ?float => $this->radarEfficiencyRatio($row),
            ],
            'desempeno' => [
                'name' => 'Desempeño',
                'label' => 'Desempeño PAC',
                'formula' => 'COUNT(PAC=1) / COUNT(PAC IN (0,1)) × 100.',
                'value' => fn(array $row): ?float => $this->radarPacValue($row),
            ],
        ];

        $axes = [];
        foreach ($definitions as $key => $definition) {
            $byProject = [];
            $numerator = 0.0;
            $denominator = 0;
            foreach ($rows as $row) {
                if (!$this->radarIsActive($row) || $this->radarIsTnp($row)) {
                    continue;
                }
                $value = $definition['value']($row);
                if ($value === null) {
                    continue;
                }

                $projectId = (int) ($row['project_id'] ?? 0);
                $byProject[$projectId] ??= ['project_id' => $projectId, 'project' => (string) ($row['project'] ?? "Proyecto {$projectId}"), 'numerator' => 0.0, 'denominator' => 0];
                $byProject[$projectId]['numerator'] += $value;
                $byProject[$projectId]['denominator']++;
                $numerator += $value;
                $denominator++;
            }

            $axes[$key] = $this->programaRadarAxis($definition, $numerator, $denominator, $byProject, $key === 'eficiencia');
        }

        $availableAxes = array_filter($axes, static fn(array $axis): bool => $axis['available']);
        return [
            'status' => count($availableAxes) === 3 ? 'available' : (count($availableAxes) > 0 ? 'partial' : 'unavailable'),
            'sample_size' => max(array_map(static fn(array $axis): int => $axis['sample_size'], $axes)),
            'axes' => $axes,
            'display_values' => array_values(array_map(static fn(array $axis): ?float => $axis['display_value'], $axes)),
        ];
    }

    private function programaRadarAxis(array $definition, float $numerator, int $denominator, array $byProject, bool $canExceedTarget): array
    {
        $minSample = 3;
        $available = $denominator >= $minSample;
        $rawValue = $available ? round(($numerator / $denominator) * 100, 1) : null;
        $displayValue = $rawValue === null ? null : round(min(100.0, $rawValue), 1);
        $overTarget = $canExceedTarget && $rawValue !== null && $rawValue > 100.0;
        $breakdown = [];
        foreach ($byProject as $project) {
            $projectAvailable = $project['denominator'] >= $minSample;
            $projectRaw = $projectAvailable ? round(($project['numerator'] / $project['denominator']) * 100, 1) : null;
            $breakdown[] = $project + [
                'sample_size' => $project['denominator'],
                'available' => $projectAvailable,
                'raw_value' => $projectRaw,
                'display_value' => $projectRaw === null ? null : round(min(100.0, $projectRaw), 1),
            ];
        }

        return [
            'name' => $definition['name'],
            'label' => $definition['label'],
            'raw_value' => $rawValue,
            'display_value' => $displayValue,
            'numerator' => round($numerator, 4),
            'denominator' => $denominator,
            'sample_size' => $denominator,
            'min_sample' => $minSample,
            'available' => $available,
            'source' => 'programacion_semanal',
            'formula' => $definition['formula'],
            'warning' => !$available
                ? "Muestra insuficiente: se requieren {$minSample} registros válidos; hay {$denominator}."
                : ($overTarget ? 'La eficiencia supera 100%; se conserva el valor bruto y la visualización se limita a 100%.' : null),
            'over_target' => $overTarget,
            'status' => $available ? $this->semanticMetricRange($displayValue ?? 0.0, 'performance') : ['key' => 'unavailable', 'label' => 'Sin muestra suficiente', 'color_token' => 'neutral-muted'],
            'project_breakdown' => $breakdown,
        ];
    }

    private function radarProgressValue(array $row): ?float
    {
        $value = $this->radarNumeric($row['P_Completado'] ?? null);
        return $value !== null && $value >= 0.0 ? min(1.0, $value) : null;
    }

    private function radarEfficiencyRatio(array $row): ?float
    {
        $commitment = $this->radarNumeric($row['Compromiso'] ?? null);
        $executed = $this->radarNumeric($row['Ejecutado_Real'] ?? null);
        if ($commitment === null || $executed === null || $commitment <= 0.0 || $executed < 0.0) {
            return null;
        }
        return $executed / $commitment;
    }

    private function radarPacValue(array $row): ?float
    {
        $pac = $this->radarNumeric($row['PAC'] ?? null);
        return $pac === 0.0 || $pac === 1.0 ? $pac : null;
    }

    private function radarNumeric(mixed $value): ?float
    {
        if (is_string($value)) {
            $value = str_replace(',', '.', trim($value));
        }
        return is_numeric($value) ? (float) $value : null;
    }

    private function radarIsTnp(array $row): bool
    {
        return $this->radarNumeric($row['Es_TNP'] ?? null) === 1.0;
    }

    private function radarIsActive(array $row): bool
    {
        return in_array(strtoupper(trim((string) ($row['Activa'] ?? ''))), ['1', 'NA'], true);
    }

    private function programaRadarRecord(array $row): array
    {
        $activity = html_entity_decode(strip_tags((string) ($row['Actividad'] ?? '')), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $activity = preg_replace('/\s+/u', ' ', trim($activity)) ?: 'Actividad sin nombre';
        $tnp = $this->radarIsTnp($row);
        $eligibility = [
            'productividad' => $this->programaRadarEligibility($this->radarProgressValue($row), $tnp, 'P_Completado debe ser un número mayor o igual que 0.'),
            'eficiencia' => $this->programaRadarEligibility($this->radarEfficiencyRatio($row), $tnp, 'Compromiso debe ser mayor que 0 y Ejecutado_Real no puede ser negativo.'),
            'desempeno' => $this->programaRadarEligibility($this->radarPacValue($row), $tnp, 'PAC debe ser 0 o 1.'),
        ];

        return [
            'project_id' => (int) ($row['project_id'] ?? 0),
            'project' => (string) ($row['project'] ?? 'Proyecto sin nombre'),
            'semana' => (int) ($row['Semana'] ?? 0),
            'cutoff' => $row['cutoff'] ?? null,
            'row_id' => (int) ($row['row_id'] ?? 0),
            'activity' => $activity,
            'unit' => trim((string) ($row['Unidad'] ?? '')),
            'commitment' => $this->radarNumeric($row['Compromiso'] ?? null),
            'executed' => $this->radarNumeric($row['Ejecutado_Real'] ?? null),
            'p_completed' => $this->radarNumeric($row['P_Completado'] ?? null),
            'pac' => $this->radarNumeric($row['PAC'] ?? null),
            'responsible' => trim((string) ($row['Responsable_AIA'] ?? '')),
            'subcontractor' => trim((string) ($row['Sub_Contratista'] ?? '')),
            'critical' => $this->radarNumeric($row['Critica'] ?? null) === 1.0,
            'tnp' => $tnp,
            'eligibility' => $eligibility,
            'exclusion_reasons' => array_map(static fn(array $item): ?string => $item['reason'], $eligibility),
        ];
    }

    private function programaRadarEligibility(?float $value, bool $tnp, string $invalidReason): array
    {
        if ($tnp) {
            return ['eligible' => false, 'reason' => 'Excluido porque Es_TNP=1.'];
        }
        return $value === null
            ? ['eligible' => false, 'reason' => $invalidReason]
            : ['eligible' => true, 'reason' => null];
    }

    private function dateDiffDays(string $from, string $to): int
    {
        try {
            $fromDate = new \DateTimeImmutable($from);
            $toDate = new \DateTimeImmutable($to);
            return (int) $fromDate->diff($toDate)->format('%r%a');
        } catch (\Throwable) {
            return 0;
        }
    }

    private function curvaSFilteredSql(string $where): string
    {
        $cutoff = 'COALESCE(sa.Fecha_Fin_Sem, sa.Fecha_Inicio_Sem)';
        $duration = '(DATEDIFF(pc.Fecha_Fin, pc.Fecha_Inicio) + 1)';

        return "WITH filtered AS (
            SELECT pc.project_id, pc.Semana, pc.Consecutivo_en_Programa AS unique_id,
                pc.Fecha_Inicio, pc.Fecha_Fin, pc.Ruta_Critica, pc.Ejecutado,
                {$cutoff} AS cutoff_date, {$duration} AS duration_days
            FROM programa_consolidado pc
            LEFT JOIN semanas_activas sa
              ON sa.project_id = pc.project_id
             AND sa.Semana = pc.Semana
            WHERE {$where}
              AND COALESCE(pc.Titulo, 0) = 0
              AND pc.Fecha_Inicio IS NOT NULL
              AND pc.Fecha_Fin IS NOT NULL
              AND DATEDIFF(pc.Fecha_Fin, pc.Fecha_Inicio) >= 0
              AND {$cutoff} IS NOT NULL
        ), points AS (
            SELECT DISTINCT cutoff_date FROM filtered
        ), latest_project_cutoffs AS (
            SELECT project_id, MAX(cutoff_date) AS cutoff_date
            FROM filtered
            GROUP BY project_id
        ), baseline AS (
            SELECT f.* FROM filtered f
            INNER JOIN latest_project_cutoffs latest
              ON latest.project_id = f.project_id
             AND latest.cutoff_date = f.cutoff_date
        ), active_project_cutoffs AS (
            SELECT points.cutoff_date AS point_date, f.project_id, MAX(f.cutoff_date) AS snapshot_cutoff
            FROM points
            INNER JOIN (SELECT DISTINCT project_id, cutoff_date FROM filtered) f
              ON f.cutoff_date <= points.cutoff_date
            GROUP BY points.cutoff_date, f.project_id
        ), aggregated AS (
            SELECT 0 AS project_id, points.cutoff_date,
                COUNT(baseline.unique_id) AS total_activities,
                SUM(CASE WHEN actual.unique_id IS NOT NULL THEN 1 ELSE 0 END) AS real_activities,
                SUM(baseline.duration_days) AS total_duration_days,
                SUM(LEAST(1.0, GREATEST(0.0, COALESCE(actual.Ejecutado, 0))) * baseline.duration_days) AS weighted_real_progress,
                SUM(CASE
                    WHEN active.snapshot_cutoff IS NULL THEN 0
                    WHEN DATEDIFF(active.snapshot_cutoff, baseline.Fecha_Inicio) + 1 <= 0 THEN 0
                    WHEN DATEDIFF(active.snapshot_cutoff, baseline.Fecha_Inicio) + 1 >= baseline.duration_days THEN baseline.duration_days
                    ELSE DATEDIFF(active.snapshot_cutoff, baseline.Fecha_Inicio) + 1
                END) AS weighted_theoretical_progress,
                SUM(CASE WHEN baseline.Ruta_Critica = 1
                    AND baseline.Fecha_Fin < active.snapshot_cutoff
                    AND LEAST(1.0, GREATEST(0.0, COALESCE(actual.Ejecutado, 0))) < 1
                    THEN 1 ELSE 0 END) AS critical_late,
                SUM(CASE WHEN baseline.Ruta_Critica = 1 THEN 1 ELSE 0 END) AS total_critical
            FROM points
            CROSS JOIN baseline
            LEFT JOIN active_project_cutoffs active
              ON active.point_date = points.cutoff_date
             AND active.project_id = baseline.project_id
            LEFT JOIN filtered actual
              ON actual.project_id = baseline.project_id
             AND actual.cutoff_date = active.snapshot_cutoff
             AND actual.unique_id = baseline.unique_id
            GROUP BY points.cutoff_date
        )
        SELECT q.*,
            CASE WHEN q.total_duration_days > 0 THEN q.weighted_real_progress / q.total_duration_days ELSE 0 END AS pct_avance_real,
            CASE WHEN q.total_duration_days > 0 THEN q.weighted_theoretical_progress / q.total_duration_days ELSE 0 END AS pct_avance_teorico,
            CASE WHEN q.total_duration_days > 0 THEN (q.weighted_real_progress - q.weighted_theoretical_progress) / q.total_duration_days ELSE 0 END AS pct_desviacion
        FROM aggregated q
        ORDER BY q.cutoff_date";
    }

    private function chart(string $type, array $labels, array $datasets, array $source): array
    {
        return [
            'type' => $type,
            'labels' => array_values($labels),
            'datasets' => $datasets,
            'source_relations' => $source['source_relations'] ?? [],
            'grain' => $source['grain'] ?? '',
        ];
    }

    private function dataset(string $label, array $data, string $color, array $dash = []): array
    {
        return [
            'label' => $label,
            'data' => array_map(fn($value) => $value === null ? null : $this->number($value), array_values($data)),
            'color' => $color,
            'dash' => $dash,
        ];
    }

    private function scoreValue(array $scorecard, string $kpi): float
    {
        foreach ($scorecard as $row) {
            if (($row['kpi'] ?? '') === $kpi) {
                return $this->number($row['value'] ?? 0);
            }
        }

        return 0.0;
    }

    private function curvaSLabels(array $data): array
    {
        if (empty($data)) {
            return [];
        }

        return array_map(function (array $row): string {
            $cutoff = $this->dateFromString((string) ($row['cutoff_date'] ?? ''));
            return $cutoff?->format('d/m/y') ?? 'Semana ' . (string) ($row['Semana'] ?? '');
        }, $data);
    }

    private function curvaSValues(array $data, string $field): array
    {
        return array_map(fn($row) => round($this->number($row[$field] ?? 0) * 100, 2), $data);
    }

    private function number($value): float
    {
        if (is_string($value)) {
            $value = str_replace(',', '.', $value);
        }

        return is_numeric($value) ? (float) $value : 0.0;
    }

    private function averagePct(array $values): float
    {
        if (!$values) {
            return 0.0;
        }

        return round((array_sum($values) / count($values)) * 100, 1);
    }

    private function inClause(array $ids): string
    {
        return implode(',', array_fill(0, count($ids), '?'));
    }

    private function kpi(string $name, float|int $value, string $unit, ?string $action): array
    {
        $status = 'OK';
        if ($action && str_contains(strtolower($action), 'crítico')) {
            $status = 'Crítico';
        } elseif ($action && (str_contains(strtolower($action), 'escalar') || str_contains(strtolower($action), 'alto'))) {
            $status = 'Alto riesgo';
        } elseif ($action && str_contains(strtolower($action), 'revisar')) {
            $status = 'Medio';
        }

        return [
            'kpi'    => $name,
            'value'  => $value,
            'unit'   => $unit,
            'trend'  => '→',
            'status' => $status,
            'action' => $action,
        ];
    }

    private function query(string $sql, array $params = []): array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    private function queryAll(string $sql, array $params = []): array
    {
        return $this->query($sql, $params);
    }

    /**
     * Get current week in Bogotá timezone.
     */
    public function currentWeekBogota(): string
    {
        $now = new \DateTime('now', new \DateTimeZone('America/Bogota'));
        return $now->format('W');
    }
}
