<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\ControlTowerService;
use App\Support\BiProjectScope;
use DomainException;

/**
 * BI Control Tower — API Controller.
 *
 * Exposes 8 JSON endpoints, one per LPS module + overview.
 * All require lps.indicadores.ver and a valid session.
 *
 * Shape: { respuesta, project_id, semana, report_key, role,
 *          executive_brief, scorecard, drivers, risks,
 *          recommended_actions, lineage }
 */
class BiControlTowerApiController extends BaseController
{
    private ControlTowerService $bi;
    private BiProjectScope $projectScope;

    public function __construct()
    {
        parent::__construct();
        $this->bi = new ControlTowerService();
        $this->projectScope = new BiProjectScope($this->db);
    }

    // -----------------------------------------------------------------
    // 1. Control Tower (overview)
    // -----------------------------------------------------------------
    public function controlTower(): void
    {
        $this->requireAuth();
        $projectIds = $this->resolveProjectIds();
        $semana    = (string) ($_GET['semana'] ?? $_SESSION['semana'] ?? $this->bi->currentWeekBogota());
        $role      = $this->resolveRole();
        $filters   = $this->resolveFilters();

        $brief = $this->bi->getBrief('overview', $projectIds, $semana, $role, $filters);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($brief, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // -----------------------------------------------------------------
    // 2. Programa General
    // -----------------------------------------------------------------
    public function programaGeneral(): void
    {
        $this->requireAuth();
        $projectIds = $this->resolveProjectIds();
        $semana    = (string) ($_GET['semana'] ?? $_SESSION['semana'] ?? $this->bi->currentWeekBogota());
        $role      = $this->resolveRole();
        $filters   = $this->resolveFilters();

        $brief = $this->bi->getBrief('programa-general', $projectIds, $semana, $role, $filters);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($brief, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function programaComplianceDetail(): void
    {
        $this->requireAuth();
        $projectIds = $this->resolveProjectIds();
        $semana = (string) ($_GET['semana'] ?? $_SESSION['semana'] ?? $this->bi->currentWeekBogota());
        $filters = $this->resolveFilters();
        $limit = max(1, min(100, (int) ($_GET['limit'] ?? 50)));
        $detail = $this->bi->getProgramaComplianceDetail($projectIds, $semana, $filters, $limit);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($detail, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function programaProgressDetail(): void
    {
        $this->requireAuth();
        $projectIds = $this->resolveProjectIds();
        $semana = (string) ($_GET['semana'] ?? $_SESSION['semana'] ?? $this->bi->currentWeekBogota());
        $filters = $this->resolveFilters();
        $limit = max(1, min(100, (int) ($_GET['limit'] ?? 50)));
        $offset = max(0, (int) ($_GET['offset'] ?? 0));
        $sort = in_array((string) ($_GET['sort'] ?? 'all'), ['all', 'missing', 'earned'], true)
            ? (string) ($_GET['sort'] ?? 'all')
            : 'all';
        $criticalOnly = filter_var($_GET['critical_only'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $detail = $this->bi->getProgramaProgressDetail($projectIds, $semana, $filters, $limit, $offset, $sort, $criticalOnly);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($detail, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function programaDelayDetail(): void
    {
        $this->requireAuth();
        $projectIds = $this->resolveProjectIds();
        $semana = (string) ($_GET['semana'] ?? $_SESSION['semana'] ?? $this->bi->currentWeekBogota());
        $limit = max(1, min(100, (int) ($_GET['limit'] ?? 50)));
        $offset = max(0, (int) ($_GET['offset'] ?? 0));
        $detail = $this->bi->getProgramaDelayDetail(
            $projectIds,
            $semana,
            $this->resolveFilters(),
            $limit,
            $offset,
        );

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($detail, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function programaRadarDetail(): void
    {
        $this->requireAuth();
        $projectIds = $this->resolveProjectIds();
        $semana = (string) ($_GET['semana'] ?? $_SESSION['semana'] ?? $this->bi->currentWeekBogota());
        $axis = trim((string) ($_GET['axis'] ?? 'productividad'));
        if (!in_array($axis, ['productividad', 'eficiencia', 'desempeno'], true)) {
            http_response_code(400);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['respuesta' => 'ERROR', 'message' => 'Eje de radar inválido.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $limit = max(1, min(100, (int) ($_GET['limit'] ?? 50)));
        $offset = max(0, (int) ($_GET['offset'] ?? 0));
        $detail = $this->bi->getProgramaRadarDetail(
            $projectIds,
            $semana,
            $this->resolveFilters(),
            $axis,
            $limit,
            $offset,
        );

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($detail, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function programaCnpDetail(): void
    {
        $this->programaCausalDetail('cnp');
    }

    public function programaCncDetail(): void
    {
        $this->programaCausalDetail('cnc');
    }

    // -----------------------------------------------------------------
    // 3. Programación Intermedia (Restricciones)
    // -----------------------------------------------------------------
    public function intermedia(): void
    {
        $this->requireAuth();
        $projectIds = $this->resolveProjectIds();
        $semana    = (string) ($_GET['semana'] ?? $_SESSION['semana'] ?? $this->bi->currentWeekBogota());
        $role      = $this->resolveRole();
        $filters   = $this->resolveFilters();

        $brief = $this->bi->getBrief('intermedia', $projectIds, $semana, $role, $filters);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($brief, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // -----------------------------------------------------------------
    // 4. Programación Semanal
    // -----------------------------------------------------------------
    public function semanal(): void
    {
        $this->requireAuth();
        $projectIds = $this->resolveProjectIds();
        $semana    = (string) ($_GET['semana'] ?? $_SESSION['semana'] ?? $this->bi->currentWeekBogota());
        $role      = $this->resolveRole();
        $filters   = $this->resolveFilters();

        $brief = $this->bi->getBrief('semanal', $projectIds, $semana, $role, $filters);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($brief, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // -----------------------------------------------------------------
    // 5. PDC
    // -----------------------------------------------------------------
    public function pdc(): void
    {
        $this->requireAuth();
        $projectIds = $this->resolveProjectIds();
        $semana    = (string) ($_GET['semana'] ?? $_SESSION['semana'] ?? $this->bi->currentWeekBogota());
        $role      = $this->resolveRole();
        $filters   = $this->resolveFilters();

        $brief = $this->bi->getBrief('pdc', $projectIds, $semana, $role, $filters);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($brief, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // -----------------------------------------------------------------
    // 6. CIC (Contratistas)
    // -----------------------------------------------------------------
    public function cic(): void
    {
        $this->requireAuth();
        $projectIds = $this->resolveProjectIds();
        $semana    = (string) ($_GET['semana'] ?? $_SESSION['semana'] ?? $this->bi->currentWeekBogota());
        $role      = $this->resolveRole();
        $filters   = $this->resolveFilters();

        $brief = $this->bi->getBrief('cic', $projectIds, $semana, $role, $filters);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($brief, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // -----------------------------------------------------------------
    // 7. CIP (Responsables)
    // -----------------------------------------------------------------
    public function cip(): void
    {
        $this->requireAuth();
        $projectIds = $this->resolveProjectIds();
        $semana    = (string) ($_GET['semana'] ?? $_SESSION['semana'] ?? $this->bi->currentWeekBogota());
        $role      = $this->resolveRole();
        $filters   = $this->resolveFilters();

        $brief = $this->bi->getBrief('cip', $projectIds, $semana, $role, $filters);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($brief, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // -----------------------------------------------------------------
    // 8. Curva S
    // -----------------------------------------------------------------
    public function curvaS(): void
    {
        $this->requireAuth();
        $projectIds = $this->resolveProjectIds();
        $semana    = (string) ($_GET['semana'] ?? $_SESSION['semana'] ?? $this->bi->currentWeekBogota());
        $role      = $this->resolveRole();
        $filters   = $this->resolveFilters();

        $brief = $this->bi->getBrief('curva-s', $projectIds, $semana, $role, $filters);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($brief, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // -----------------------------------------------------------------
    // 9. Lineage (metadata)
    // -----------------------------------------------------------------
    public function lineage(): void
    {
        $this->requireAuth();
        $this->assertAnyProjectAccess();
        $key = $_GET['metric_key'] ?? null;
        $lineageService = new \App\Services\Bi\LineageService();

        if ($key) {
            $result = $lineageService->getForMetric($key);
        } else {
            $result = $lineageService->listAllMetricKeys();
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['respuesta' => 'BIEN', 'lineage' => $result], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // -----------------------------------------------------------------
    // 10. Projects — list active projects for the sidebar dropdown
    // -----------------------------------------------------------------
    public function projects(): void
    {
        $this->requireAuth();
        $projects = $this->projectScope->authorizedProjects($_SESSION);
        if ($projects === []) {
            $this->abortUnauthorizedProjectScope('No tienes proyectos autorizados para Control Tower.');
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['respuesta' => 'BIEN', 'projects' => $projects], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // -----------------------------------------------------------------
    // 11. Weeks — list available weeks for selected projects
    // -----------------------------------------------------------------
    public function weeks(): void
    {
        $this->requireAuth();

        $projectIds = $this->resolveProjectIds();

        $db = \Database::getInstance();

        if (count($projectIds) === 1) {
            $stmt = $db->prepare(
                "SELECT Semana, Fecha_Inicio_Sem, Fecha_Fin_Sem
                 FROM semanas_activas
                 WHERE project_id = ?
                 ORDER BY Semana DESC",
            );
            $stmt->execute([$projectIds[0]]);
        } else {
            // Multi-project: show unified weeks (intersection)
            $in = implode(',', array_fill(0, count($projectIds), '?'));
            $stmt = $db->prepare(
                "SELECT Semana, MIN(Fecha_Inicio_Sem) as Fecha_Inicio_Sem, MAX(Fecha_Fin_Sem) as Fecha_Fin_Sem
                 FROM semanas_activas
                 WHERE project_id IN ({$in})
                 GROUP BY Semana
                 HAVING COUNT(DISTINCT project_id) = ?
                 ORDER BY Semana DESC",
            );
            $stmt->execute(array_merge($projectIds, [count($projectIds)]));
        }

        $weeks = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['respuesta' => 'BIEN', 'weeks' => $weeks, 'multi_project' => count($projectIds) > 1], JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function filterOptions(): void
    {
        $this->requireAuth();

        $projectIds = $this->resolveProjectIds();
        $semana = (string) ($_GET['semana'] ?? $_SESSION['semana'] ?? $this->bi->currentWeekBogota());
        $options = $this->bi->getFilterOptions($projectIds, $semana, $this->resolveFilters());

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['respuesta' => 'BIEN'] + $options, JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function programaCausalDetail(string $kind): never
    {
        $this->requireAuth();
        $projectIds = $this->resolveProjectIds();
        $semana = (string) ($_GET['semana'] ?? $_SESSION['semana'] ?? $this->bi->currentWeekBogota());
        $filters = $this->resolveFilters();
        $category = trim((string) ($_GET['category'] ?? ''));
        $limit = max(1, min(100, (int) ($_GET['limit'] ?? 100)));
        $offset = max(0, (int) ($_GET['offset'] ?? 0));
        $includeSummary = filter_var($_GET['include_summary'] ?? true, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true;
        $method = $kind === 'cnp' ? 'getProgramaCnpDetail' : 'getProgramaCncDetail';
        $detail = $this->bi->{$method}($projectIds, $semana, $filters, $category, $limit, $offset, $includeSummary);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($detail, JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function resolveProjectIds(): array
    {
        $projectIdsRaw = $_GET['project_ids'] ?? $_GET['project_id'] ?? '';
        try {
            return $this->projectScope->resolve($projectIdsRaw, $_SESSION);
        } catch (DomainException $exception) {
            $this->abortUnauthorizedProjectScope($exception->getMessage());
        }
    }

    private function resolveFilters(): array
    {
        return [
            'desde' => $_GET['desde'] ?? $_GET['fecha_desde'] ?? '',
            'hasta' => $_GET['hasta'] ?? $_GET['fecha_hasta'] ?? '',
            'sub' => $_GET['sub'] ?? $_GET['subcontratista'] ?? '',
            'resp' => $_GET['resp'] ?? $_GET['responsable'] ?? '',
            'etapa' => $_GET['etapa'] ?? '',
        ];
    }

    private function resolveRole(): string
    {
        return $this->projectScope->reportRole($this->resolveProjectIds(), $_SESSION);
    }

    private function assertAnyProjectAccess(): void
    {
        if (!$this->projectScope->hasAnyAccess($_SESSION)) {
            $this->abortUnauthorizedProjectScope(
                'No tienes proyectos autorizados para Control Tower.',
            );
        }
    }

    private function abortUnauthorizedProjectScope(string $message): never
    {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Acceso denegado. ' . $message], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
