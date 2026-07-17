<?php

declare(strict_types=1);

namespace App\Services\Bi;

/**
 * BI Action Recommendation Service.
 *
 * Converts risks into actionable recommendations with owner, due date,
 * expected impact, and evidence.
 *
 * Doc Section 7: toda acción debe tener dueño y fecha límite.
 * Si no tiene dueño ni fecha, no es acción — es comentario.
 */
class ActionRecommendationService
{
    private \Database $db;

    public function __construct()
    {
        $this->db = \Database::getInstance();
    }

    /**
     * Generate recommended actions for a report based on its data.
     *
     * @return array[] Each with: action, owner, due_date, expected_impact, evidence, action_type, status
     */
    public function recommend(
        string $reportKey,
        array $data,
        array|int $projectIds,
        string $semana,
        array $filters = [],
    ): array
    {
        $actions = match ($reportKey) {
            'overview'            => $this->actionsFromSummary($data),
            'programa-general'    => $this->actionsFromPG($data),
            'intermedia'          => $this->actionsFromPI($data),
            'semanal'             => $this->actionsFromPS($data),
            'pdc'                 => $this->actionsFromPDC($data),
            'cic'                 => $this->actionsFromCIC($data),
            'cip'                 => $this->actionsFromCIP($data),
            'curva-s'             => $this->actionsFromCurvaS($data),
            default               => [],
        };

        $scope = array_values(array_unique(array_filter(array_map(
            'intval',
            is_array($projectIds) ? $projectIds : [$projectIds],
        ))));

        return $this->enrichActions($actions, $scope, $semana, $filters);
    }

    // -----------------------------------------------------------------
    // Per-report action generators
    // -----------------------------------------------------------------

    private function actionsFromSummary(array $data): array
    {
        $s = $data[0] ?? [];
        $actions = [];
        $blocked = (int)($s['hard_restriction_blocked_count'] ?? 0);
        $atRisk  = (int)($s['weekly_commitments_at_risk_count'] ?? 0);

        if ($blocked > 0) {
            $actions[] = $this->makeAction(
                "Liberar {$blocked} actividades con restricciones duras incompletas",
                'Residente / Planeación',
                '+2 días',
                'Reducir bloqueos en la ventana Lookahead',
                "{$blocked} actividades no cumplen los umbrales de restricciones duras",
                'liberar_restriccion'
            );
        }
        if ($atRisk > 0) {
            $actions[] = $this->makeAction(
                "Revisar {$atRisk} compromisos en riesgo de incumplimiento",
                'Residente',
                '+1 día',
                'Subir PAC esperado de la semana',
                "{$atRisk} compromisos tienen fulfillment_alert activo",
                'ajustar_compromiso'
            );
        }
        return $actions;
    }

    private function actionsFromPG(array $data): array
    {
        $actions = [];
        $criticalLate = array_filter($data, fn($r) => ($r['is_critical_late'] ?? 0) == 1);
        $notReadyInWindow = array_filter($data, fn($r) =>
            ($r['hard_restrictions_ready'] ?? 0) == 0
            && ($r['is_lookahead_window'] ?? 0) == 1
        );

        foreach (array_slice($criticalLate, 0, 3) as $row) {
            $act = $row['Actividad'] ?? $row['Id'] ?? '?';
            $actions[] = $this->makeAction(
                "Recuperar actividad crítica atrasada: {$act}",
                $row['responsable_aia'] ?? 'Residente',
                '+3 días',
                'Proteger fecha final del proyecto',
                "Ruta crítica, fecha fin vencida, ejecución incompleta",
                'recovery_plan',
                $this->projectIdFromRow($row),
            );
        }
        foreach (array_slice($notReadyInWindow, 0, 3) as $row) {
            $act = $row['Actividad'] ?? $row['Id'] ?? '?';
            $actions[] = $this->makeAction(
                "Completar restricciones de: {$act}",
                $row['responsable_aia'] ?? 'Residente',
                '+5 días',
                'Permitir que la actividad entre a Programación Semanal',
                "Restricciones duras incompletas, inicia en {$row['Semanas_Inicio']} semanas",
                'liberar_restriccion',
                $this->projectIdFromRow($row),
            );
        }
        return $actions;
    }

    private function actionsFromPI(array $data): array
    {
        $actions = [];
        $notReady = array_filter($data, fn($r) => ($r['is_hard'] ?? 0) == 1 && ($r['is_ready'] ?? 0) == 0);

        foreach (array_slice($notReady, 0, 5) as $row) {
            $type = $row['restriction_type'] ?? '?';
            $act = $row['Actividad'] ?? $row['Id'] ?? '?';
            $actions[] = $this->makeAction(
                "Liberar {$type} en: {$act}",
                $row['responsible'] ?? $row['subcontractor'] ?? 'Residente',
                '+4 días',
                "Reducir bloqueo de {$type} en la ventana Lookahead",
                "{$type} = {$row['restriction_value']}, requiere {$row['required_threshold']}",
                'liberar_restriccion',
                $this->projectIdFromRow($row),
            );
        }
        return $actions;
    }

    private function actionsFromPS(array $data): array
    {
        $actions = [];
        $atRisk = array_filter($data, fn($r) => ($r['fulfillment_alert'] ?? 0) == 1);

        foreach (array_slice($atRisk, 0, 5) as $row) {
            $act = $row['Actividad'] ?? $row['Id'] ?? '?';
            $reason = [];
            if (($row['missing_responsible'] ?? 0) == 1) $reason[] = 'sin responsable';
            if (($row['missing_subcontractor'] ?? 0) == 1) $reason[] = 'sin contratista';
            if (($row['PAC'] ?? 1) == 0) $reason[] = 'PAC=0';
            if (empty($reason)) $reason[] = 'critico con riesgo';

            $actions[] = $this->makeAction(
                "Revisar compromiso: {$act}",
                $row['responsible'] ?? 'Residente',
                '+1 día',
                'Reducir riesgo de incumplimiento semanal',
                implode(', ', $reason),
                'ajustar_compromiso',
                $this->projectIdFromRow($row),
            );
        }
        return $actions;
    }

    private function actionsFromPDC(array $data): array
    {
        $actions = [];
        $notReady = array_filter($data, fn($r) => ($r['listo_para_iniciar'] ?? 1) == 0);
        $needsConfig = array_filter($data, fn($r) => ($r['necesita_configuracion'] ?? 0) == 1);

        foreach (array_slice($notReady, 0, 3) as $row) {
            $paq = $row['paqueteContratacion'] ?? $row['consecutivo'] ?? '?';
            $actions[] = $this->makeAction(
                "Escalar paquete PDC no listo: {$paq}",
                'Residente / Compras',
                '+3 días',
                'Evitar que la actividad asociada pase a CNP',
                "fechaRealInsumosObra > fechaInicio o nula",
                'escalar_pdc',
                $this->projectIdFromRow($row),
            );
        }
        foreach (array_slice($needsConfig, 0, 3) as $row) {
            $paq = $row['paqueteContratacion'] ?? $row['consecutivo'] ?? '?';
            $actions[] = $this->makeAction(
                "Completar duraciones del paquete PDC: {$paq}",
                'Planeación / Compras',
                '+5 días',
                'Mejorar predictividad del PDC',
                'Duraciones default o incompletas detectadas',
                'corregir_dato',
                $this->projectIdFromRow($row),
            );
        }
        return $actions;
    }

    private function actionsFromCIC(array $data): array
    {
        $actions = [];
        $atRisk = array_filter($data, fn($r) => ($r['alert_contractor_future_risk'] ?? 0) == 1);

        foreach (array_slice($atRisk, 0, 3) as $row) {
            $name = $row['subcontratista'] ?? '?';
            $actions[] = $this->makeAction(
                "Revisar contratista en alerta: {$name}",
                'Director / Residente',
                '+5 días',
                'Evaluar riesgo para futuras asignaciones',
                "Cal_Integral_Acum = {$row['Cal_Integral_Acum']}, por debajo del umbral 50",
                'intervenir_contratista',
                $this->projectIdFromRow($row),
            );
        }
        return $actions;
    }

    private function actionsFromCIP(array $data): array
    {
        $actions = [];
        $atRisk = array_filter($data, fn($r) => ($r['fulfillment_alert'] ?? 0) == 1);

        foreach (array_slice($atRisk, 0, 3) as $row) {
            $name = $row['Responsable_AIA'] ?? '?';
            $load = (int)($row['number_of_commitments'] ?? 0);
            $actions[] = $this->makeAction(
                "Balancear carga del responsable: {$name}",
                'Director / Residente líder',
                '+3 días',
                'Reducir riesgo de saturación y mejorar cumplimiento',
                "{$load} compromisos activos, fulfillment_alert activo",
                'balancear_responsable',
                $this->projectIdFromRow($row),
            );
        }
        return $actions;
    }

    private function actionsFromCurvaS(array $data): array
    {
        $s = $data[0] ?? [];
        $actions = [];
        $desv = (float)($s['pct_desviacion'] ?? 0);

        if ($desv < -0.05) {
            $actions[] = $this->makeAction(
                'Ejecutar plan de recuperación de Curva S',
                'Director de obra',
                '+7 días',
                'Recuperar desviación frente a la curva teórica por duración',
                "Desviación: " . round($desv * 100, 1) . "% por debajo de la curva teórica",
                'recovery_plan'
            );
        }
        return $actions;
    }

    // -----------------------------------------------------------------
    // Action factory
    // -----------------------------------------------------------------

    private function enrichActions(array $actions, array $projectIds, string $semana, array $filters): array
    {
        $cutoffs = $this->resolveCutoffs($projectIds, $semana, $filters);

        return array_map(function (array $action) use ($projectIds, $semana, $cutoffs): array {
            $sourceProjectId = (int) ($action['project_id'] ?? 0);
            $projectId = $sourceProjectId > 0
                ? $sourceProjectId
                : (count($projectIds) === 1 ? $projectIds[0] : null);
            $action['project_ids'] = $projectIds;
            $action['project_id'] = $projectId;
            $action['scope'] = $projectId === null ? 'consolidated' : 'project';
            $action['semana'] = $semana;
            $action['due_date'] = $this->absoluteDueDate(
                (string) ($action['due_date'] ?? ''),
                $projectId === null ? null : ($cutoffs[$projectId] ?? null),
            );
            $action['due_dates_by_project'] = $projectId === null
                ? $this->dueDatesByProject((string) ($action['due_date_relative'] ?? $action['due_date'] ?? ''), $cutoffs)
                : [];

            return $action;
        }, $actions);
    }

    private function dueDatesByProject(string $relativeDueDate, array $cutoffs): array
    {
        $dates = [];
        foreach ($cutoffs as $projectId => $cutoff) {
            $date = $this->absoluteDueDate($relativeDueDate, $cutoff);
            if ($date !== null) {
                $dates[(int) $projectId] = $date;
            }
        }

        return $dates;
    }

    private function resolveCutoffs(array $projectIds, string $semana, array $filters): array
    {
        if ($projectIds === []) {
            return [];
        }

        $in = implode(',', array_fill(0, count($projectIds), '?'));
        $filters = $this->normalizeDateFilters($filters);
        $where = '';
        $params = $projectIds;
        if ($this->hasDateRange($filters)) {
            $where = ' AND COALESCE(Fecha_Fin_Sem, Fecha_Inicio_Sem) BETWEEN ? AND ?';
            $params[] = $filters['desde'] ?: '1000-01-01';
            $params[] = $filters['hasta'] ?: '9999-12-31';
        } elseif ($semana !== '') {
            $where = ' AND Semana = ?';
            $params[] = $semana;
        } else {
            return [];
        }

        $stmt = $this->db->prepare(
            "SELECT project_id, MAX(COALESCE(Fecha_Fin_Sem, Fecha_Inicio_Sem)) AS cutoff
             FROM semanas_activas
             WHERE project_id IN ({$in}){$where}
             GROUP BY project_id",
        );
        $stmt->execute($params);
        $cutoffs = [];
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $value = $row['cutoff'] ?? null;
            if (is_string($value) && $value !== '') {
                $cutoffs[(int) $row['project_id']] = new \DateTimeImmutable($value);
            }
        }

        return $cutoffs;
    }

    private function absoluteDueDate(string $relativeDueDate, ?\DateTimeImmutable $cutoff): ?string
    {
        if ($cutoff === null || !preg_match('/\+(\d+)\s+d[ií]as/u', $relativeDueDate, $matches)) {
            return null;
        }

        return $cutoff->modify('+' . (int) $matches[1] . ' days')->format('Y-m-d');
    }

    private function projectIdFromRow(array $row): ?int
    {
        $projectId = (int) ($row['project_id'] ?? 0);
        return $projectId > 0 ? $projectId : null;
    }

    private function normalizeDateFilters(array $filters): array
    {
        return [
            'desde' => $this->dateOrBlank($filters['desde'] ?? $filters['fecha_desde'] ?? ''),
            'hasta' => $this->dateOrBlank($filters['hasta'] ?? $filters['fecha_hasta'] ?? ''),
        ];
    }

    private function hasDateRange(array $filters): bool
    {
        return $filters['desde'] !== '' || $filters['hasta'] !== '';
    }

    private function dateOrBlank(mixed $value): string
    {
        $value = trim((string) $value);
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : '';
    }

    private function makeAction(
        string $action,
        string $owner,
        string $dueDate,
        string $expectedImpact,
        string $evidence,
        string $actionType,
        ?int $projectId = null,
    ): array {
        return [
            'action'          => $action,
            'owner'           => $owner,
            'due_date'        => $dueDate,
            'due_date_relative' => $dueDate,
            'expected_impact' => $expectedImpact,
            'evidence'        => $evidence,
            'action_type'     => $actionType,
            'status'          => 'abierta',
            'project_id'      => $projectId,
        ];
    }

    /**
     * Persist an action to bi_action_queue.
     */
    public function createAction(array $params): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO bi_action_queue
             (project_id, semana, action_type, entity_type, entity_id, owner, due_date,
              recommended_action, expected_impact, evidence_json, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'abierta')"
        );
        $stmt->execute([
            $params['project_id'],
            $params['semana'],
            $params['action_type'],
            $params['entity_type'],
            $params['entity_id'],
            $params['owner'] ?? null,
            $params['due_date'] ?? null,
            $params['recommended_action'] ?? '',
            $params['expected_impact'] ?? '',
            json_encode($params['evidence'] ?? []),
        ]);
        return (int)$this->db->lastInsertId();
    }

    /**
     * Close an action with evidence.
     */
    public function closeAction(int $actionId, string $closureEvidence): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE bi_action_queue SET status = 'cerrada', closed_at = NOW(), closure_evidence = ? WHERE id = ?"
        );
        return $stmt->execute([$closureEvidence, $actionId]);
    }
}
