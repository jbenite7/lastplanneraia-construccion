<?php

declare(strict_types=1);

namespace App\Controllers\Bi;

use App\Controllers\BaseController;
use App\Services\ControlTowerService;
use App\Support\BiProjectScope;
use DomainException;

/**
 * BI Control Tower — View Controller.
 *
 * Renders 8 HTML dashboard views under /bi/.
 * All require lps.indicadores.ver.
 * Mobile-first, Chart.js CDN, vanilla JS.
 */
class BiViewController extends BaseController
{
    private ControlTowerService $bi;
    private BiProjectScope $projectScope;

    public function __construct()
    {
        parent::__construct();
        $this->bi = new ControlTowerService();
        $this->projectScope = new BiProjectScope($this->db);
    }

    /**
     * Shared render: fetch data, inject into view, render with layout.
     */
    private function renderView(string $reportKey, string $viewFile): void
    {
        $this->requireAuth();

        $projectIdsRaw = $_GET['project_ids'] ?? $_GET['project_id'] ?? [];
        try {
            $projectIds = $this->projectScope->resolve($projectIdsRaw, $_SESSION);
        } catch (DomainException $exception) {
            $this->abortUnauthorizedProjectScope($exception->getMessage());
        }

        $semana = (string) ($_GET['semana'] ?? $_SESSION['semana'] ?? $this->bi->currentWeekBogota());
        $role      = $this->projectScope->reportRole($projectIds, $_SESSION);
        $filters   = $this->resolveFilters();

        $brief = $this->bi->getBrief($reportKey, $projectIds, $semana, $role, $filters);

        // Inject data for JS hydration
        $initialData = json_encode($brief, JSON_UNESCAPED_UNICODE);

        // Pass variables to view
        $viewData = [
            'reportKey'   => $reportKey,
            'projectIds'  => $projectIds,
            'projectId'   => $projectIds[0] ?? 0,
            'semana'      => $semana,
            'role'        => $role,
            'filters'     => $filters,
            'brief'       => $brief,
            'initialData' => $initialData,
            'viewFile'    => $viewFile,
        ];

        extract($viewData);
        require_once __DIR__ . '/../../../views/bi/_layout.php';
    }

    // -----------------------------------------------------------------
    // 8 view actions
    // -----------------------------------------------------------------

    public function controlTower(): void
    {
        $this->renderView('overview', 'control-tower');
    }

    public function programaGeneral(): void
    {
        $this->renderView('programa-general', 'control-tower');
    }

    public function intermedia(): void
    {
        $this->renderView('intermedia', 'control-tower');
    }

    public function semanal(): void
    {
        $this->renderView('semanal', 'control-tower');
    }

    public function pdc(): void
    {
        $this->renderView('pdc', 'control-tower');
    }

    public function contratistas(): void
    {
        $this->renderView('cic', 'control-tower');
    }

    public function responsables(): void
    {
        $this->renderView('cip', 'control-tower');
    }

    public function curvaS(): void
    {
        $this->renderView('curva-s', 'control-tower');
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

    private function abortUnauthorizedProjectScope(string $message): never
    {
        http_response_code(403);
        $safeMessage = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
        echo "<h1>Error 403</h1><p>{$safeMessage}</p>";
        exit;
    }
}
