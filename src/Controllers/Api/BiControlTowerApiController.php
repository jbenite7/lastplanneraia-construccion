<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Security\DataScope\MultiProjectScope;
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

        // Mismo gate que las vistas. ErrorPage devuelve JSON para las rutas /api/*.
        if (!\App\Security\BiPreviewAccessPolicy::canOpen($_SESSION)) {
            \App\Core\ErrorPage::render(
                404,
                'Esta página no existe',
                'La dirección que abriste no corresponde a ninguna pantalla del producto.'
            );
            exit;
        }
    }

    // -----------------------------------------------------------------
    // 1. Control Tower (overview)
    // -----------------------------------------------------------------
    public function controlTower(): void
    {
        $this->requireAuth();
        $scope = $this->resolveProjectScope('bi:control-tower:overview');
        $projectIds = $scope->projectIds();
        $semana    = (string) ($_GET['semana'] ?? $_SESSION['semana'] ?? $this->bi->currentWeekBogota());
        $role      = $this->resolveRole($scope);
        $filters   = $this->resolveFilters();

        $brief = $this->bi->getBrief($scope, 'overview', $semana, $role, $filters);
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
        $scope = $this->resolveProjectScope('bi:control-tower:programa-general');
        $projectIds = $scope->projectIds();
        $semana    = (string) ($_GET['semana'] ?? $_SESSION['semana'] ?? $this->bi->currentWeekBogota());
        $role      = $this->resolveRole($scope);
        $filters   = $this->resolveFilters();

        $brief = $this->bi->getBrief($scope, 'programa-general', $semana, $role, $filters);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($brief, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function programaComplianceDetail(): void
    {
        $this->requireAuth();
        $scope = $this->resolveProjectScope('bi:control-tower:programa-compliance-detail');
        $semana = (string) ($_GET['semana'] ?? $_SESSION['semana'] ?? $this->bi->currentWeekBogota());
        $filters = $this->resolveFilters();
        $limit = max(1, min(100, (int) ($_GET['limit'] ?? 50)));
        $detail = $this->bi->getProgramaComplianceDetail($scope, $semana, $filters, $limit);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($detail, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function programaProgressDetail(): void
    {
        $this->requireAuth();
        $scope = $this->resolveProjectScope('bi:control-tower:programa-progress-detail');
        $semana = (string) ($_GET['semana'] ?? $_SESSION['semana'] ?? $this->bi->currentWeekBogota());
        $filters = $this->resolveFilters();
        $limit = max(1, min(100, (int) ($_GET['limit'] ?? 50)));
        $offset = max(0, (int) ($_GET['offset'] ?? 0));
        $sort = in_array((string) ($_GET['sort'] ?? 'all'), ['all', 'missing', 'earned'], true)
            ? (string) ($_GET['sort'] ?? 'all')
            : 'all';
        $criticalOnly = filter_var($_GET['critical_only'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $detail = $this->bi->getProgramaProgressDetail($scope, $semana, $filters, $limit, $offset, $sort, $criticalOnly);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($detail, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function programaDelayDetail(): void
    {
        $this->requireAuth();
        $scope = $this->resolveProjectScope('bi:control-tower:programa-delay-detail');
        $semana = (string) ($_GET['semana'] ?? $_SESSION['semana'] ?? $this->bi->currentWeekBogota());
        $limit = max(1, min(100, (int) ($_GET['limit'] ?? 50)));
        $offset = max(0, (int) ($_GET['offset'] ?? 0));
        $detail = $this->bi->getProgramaDelayDetail(
            $scope,
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
        $scope = $this->resolveProjectScope('bi:control-tower:programa-radar-detail');
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
            $scope,
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
        $scope = $this->resolveProjectScope('bi:control-tower:intermedia');
        $projectIds = $scope->projectIds();
        $semana    = (string) ($_GET['semana'] ?? $_SESSION['semana'] ?? $this->bi->currentWeekBogota());
        $role      = $this->resolveRole($scope);
        $filters   = $this->resolveFilters();

        $brief = $this->bi->getBrief($scope, 'intermedia', $semana, $role, $filters);
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
        $scope = $this->resolveProjectScope('bi:control-tower:semanal');
        $projectIds = $scope->projectIds();
        $semana    = (string) ($_GET['semana'] ?? $_SESSION['semana'] ?? $this->bi->currentWeekBogota());
        $role      = $this->resolveRole($scope);
        $filters   = $this->resolveFilters();

        $brief = $this->bi->getBrief($scope, 'semanal', $semana, $role, $filters);
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
        $scope = $this->resolveProjectScope('bi:control-tower:pdc');
        $projectIds = $scope->projectIds();
        $semana    = (string) ($_GET['semana'] ?? $_SESSION['semana'] ?? $this->bi->currentWeekBogota());
        $role      = $this->resolveRole($scope);
        $filters   = $this->resolveFilters();

        $brief = $this->bi->getBrief($scope, 'pdc', $semana, $role, $filters);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($brief, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Drill-down del panel de compras: un renglón por paso pendiente (fase B3).
     *
     * Los proyectos salen de resolveProjectIds() —que pasa por BiProjectScope— y nunca de un
     * project_id crudo del cliente: es lo que impide que una obra vea la contratación de otra.
     */
    public function pdcDetail(): void
    {
        $this->requireAuth();
        $scope = $this->resolveProjectScope('bi:control-tower:pdc-detail');

        $seguimiento = new \App\Services\Pdc\SeguimientoService(\Database::getInstance());
        $hoy = (new \DateTimeImmutable('today'))->format('Y-m-d');

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'respuesta' => 'BIEN',
            'hoy'       => $hoy,
            'paquetes'  => $seguimiento->detalleDestinos($scope, $hoy),
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // -----------------------------------------------------------------
    // 6. CIC (Contratistas)
    // -----------------------------------------------------------------
    public function cic(): void
    {
        $this->requireAuth();
        $scope = $this->resolveProjectScope('bi:control-tower:cic');
        $projectIds = $scope->projectIds();
        $semana    = (string) ($_GET['semana'] ?? $_SESSION['semana'] ?? $this->bi->currentWeekBogota());
        $role      = $this->resolveRole($scope);
        $filters   = $this->resolveFilters();

        $brief = $this->bi->getBrief($scope, 'cic', $semana, $role, $filters);
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
        $scope = $this->resolveProjectScope('bi:control-tower:cip');
        $projectIds = $scope->projectIds();
        $semana    = (string) ($_GET['semana'] ?? $_SESSION['semana'] ?? $this->bi->currentWeekBogota());
        $role      = $this->resolveRole($scope);
        $filters   = $this->resolveFilters();

        $brief = $this->bi->getBrief($scope, 'cip', $semana, $role, $filters);
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
        $scope = $this->resolveProjectScope('bi:control-tower:curva-s');
        $projectIds = $scope->projectIds();
        $semana    = (string) ($_GET['semana'] ?? $_SESSION['semana'] ?? $this->bi->currentWeekBogota());
        $role      = $this->resolveRole($scope);
        $filters   = $this->resolveFilters();

        $brief = $this->bi->getBrief($scope, 'curva-s', $semana, $role, $filters);
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

        $scope = $this->resolveProjectScope('bi:control-tower:weeks');
        $projectIds = $scope->projectIds();

        $db = \Database::getInstance();

        $in = implode(',', array_fill(0, count($projectIds), '?'));
        $stmt = $db->queryForProjects(
            $scope,
            "SELECT Semana, MIN(Fecha_Inicio_Sem) as Fecha_Inicio_Sem, MAX(Fecha_Fin_Sem) as Fecha_Fin_Sem
             FROM semanas_activas
             WHERE project_id IN ({$in})
             GROUP BY Semana
             HAVING COUNT(DISTINCT project_id) = ?
             ORDER BY Semana DESC",
            array_merge($projectIds, [count($projectIds)]),
        );

        $weeks = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['respuesta' => 'BIEN', 'weeks' => $weeks, 'multi_project' => count($projectIds) > 1], JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function filterOptions(): void
    {
        $this->requireAuth();

        $scope = $this->resolveProjectScope('bi:control-tower:filter-options');
        $semana = (string) ($_GET['semana'] ?? $_SESSION['semana'] ?? $this->bi->currentWeekBogota());
        $options = $this->bi->getFilterOptions($scope, $semana, $this->resolveFilters());

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['respuesta' => 'BIEN'] + $options, JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function programaCausalDetail(string $kind): never
    {
        $this->requireAuth();
        $scope = $this->resolveProjectScope('bi:control-tower:programa-' . $kind . '-detail');
        $semana = (string) ($_GET['semana'] ?? $_SESSION['semana'] ?? $this->bi->currentWeekBogota());
        $filters = $this->resolveFilters();
        $category = trim((string) ($_GET['category'] ?? ''));
        $limit = max(1, min(100, (int) ($_GET['limit'] ?? 100)));
        $offset = max(0, (int) ($_GET['offset'] ?? 0));
        $includeSummary = filter_var($_GET['include_summary'] ?? true, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true;
        $method = $kind === 'cnp' ? 'getProgramaCnpDetail' : 'getProgramaCncDetail';
        $detail = $this->bi->{$method}($scope, $semana, $filters, $category, $limit, $offset, $includeSummary);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($detail, JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function resolveProjectScope(string $reason): MultiProjectScope
    {
        $projectIdsRaw = $_GET['project_ids'] ?? $_GET['project_id'] ?? '';
        try {
            return $this->projectScope->scope($projectIdsRaw, $_SESSION, $reason);
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

    private function resolveRole(MultiProjectScope $scope): string
    {
        return $this->projectScope->reportRole($scope->projectIds(), $_SESSION);
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
