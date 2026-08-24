<?php

declare(strict_types=1);

namespace App\Controllers\Bi;

use App\Controllers\BaseController;
use App\Services\ControlTowerService;
use App\Support\BiProjectScope;
use DomainException;
use TableResolver;

/**
 * BI Control Tower — View Controller.
 *
 * Renders 8 HTML dashboard views under /bi/.
 * All require lps.indicadores.ver.
 * Mobile-first, Chart.js CDN, vanilla JS.
 */
class BiViewController extends BaseController
{
    /**
     * Shell sidebar (DS-027): etiqueta de la context-bar por hoja ($reportKey).
     * Debe reflejar los 8 reportKey que produce cada acción pública de este controlador.
     */
    private const SHELL_MODULE_LABELS = [
        'overview'         => 'Resumen Ejecutivo',
        'programa-general' => 'Programa General',
        'curva-s'          => 'Curva S',
        'intermedia'       => 'Prog. Intermedia (6 Sem)',
        'semanal'          => 'Programación Semanal',
        'pdc'              => 'Plan de Compras',
        'cic'              => 'Proveedores (CIC)',
        'cip'              => 'Responsables (CIP)',
    ];

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
        // El módulo está oculto mientras se desarrolla: solo Admin lo abre por URL.
        // 404 y no 403, para no confirmar que la pantalla existe.
        if (!\App\Security\BiPreviewAccessPolicy::canOpen($_SESSION)) {
            \App\Core\ErrorPage::render(
                404,
                'Esta página no existe',
                'La dirección que abriste no corresponde a ninguna pantalla del producto. Puede que el enlace esté viejo o mal copiado.'
            );
            exit;
        }

        $this->requireAuth();

        $projectIdsRaw = $_GET['project_ids'] ?? $_GET['project_id'] ?? [];
        try {
            $projectIds = $this->projectScope->resolve($projectIdsRaw, $_SESSION);
        } catch (DomainException $exception) {
            $this->abortUnauthorizedProjectScope($exception->getMessage());
        }

        $semana = (string) ($_GET['semana'] ?? $_SESSION['semana'] ?? $this->bi->currentWeekBogota());
        $role      = $this->projectScope->reportRole($projectIds, $_SESSION);

        // El Admin no tiene audiencia fija: recuerda su última elección para que el
        // enlace de entrada del sidebar aterrice ahí la próxima vez (Tarea 3,
        // docs/superpowers/specs/2026-08-24-reparto-lienzos-por-rol-design.md).
        if ($role === 'A') {
            $_SESSION['bi_admin_last_module'] = $reportKey;
        }

        $filters   = $this->resolveFilters();

        $brief = $this->bi->getBrief($reportKey, $projectIds, $semana, $role, $filters);

        // Inject data for JS hydration
        $initialData = json_encode($brief, JSON_UNESCAPED_UNICODE);

        // Shell sidebar (DS-027): Control Tower consume el shell canónico dark.
        $shellActive = 'control-tower';
        $shellModuleLabel = self::SHELL_MODULE_LABELS[$reportKey] ?? 'Control Tower - Informes';
        $shellWeeks = $this->loadShellWeeks();

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
            'shellActive'      => $shellActive,
            'shellModuleLabel' => $shellModuleLabel,
            'shellWeeks'       => $shellWeeks,
        ];

        extract($viewData);
        require_once __DIR__ . '/../../../views/bi/_layout.php';
    }

    /**
     * Shell sidebar (DS-027): semanas del proyecto para el chip de contexto.
     * Copia el patrón de ProgramacionSemanalController::loadShellWeeks().
     *
     * @return array<int, array{Semana: int, Fecha_Inicio_Sem: ?string, Fecha_Fin_Sem: ?string}>
     */
    private function loadShellWeeks(): array
    {
        $shellWeeks = [];
        $dbName = (string) ($_SESSION['db'] ?? '');
        if ($dbName !== '' && preg_match('/^[a-zA-Z0-9_]+$/', $dbName)) {
            try {
                $tSaShell = TableResolver::resolveByPrefix($dbName, 'semanas_activas');
                $projectIdShell = TableResolver::getProjectIdByPrefix($dbName);
                $stmtShellWeeks = $this->db->queryWithProject(
                    "SELECT Semana, Fecha_Inicio_Sem, Fecha_Fin_Sem FROM {$tSaShell} WHERE project_id = ? ORDER BY Semana DESC",
                    [$projectIdShell]
                );
                $shellWeeks = $stmtShellWeeks->fetchAll(\PDO::FETCH_ASSOC) ?: [];
            } catch (\Throwable $e) {
                error_log('Error cargando semanas para el shell Control Tower: ' . $e->getMessage());
            }
        }

        return $shellWeeks;
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
        $this->maybeRedirectToOwnScope();
        $this->renderView('cip', 'control-tower');
    }

    /**
     * El Residente aterriza en Responsables viendo solo sus propios compromisos
     * (confirmado con Felipe, 2026-08-24), a menos que ya haya elegido explícitamente
     * un filtro (`resp`) o pedido ver toda la obra (`alcance=obra`).
     */
    private function maybeRedirectToOwnScope(): void
    {
        if (isset($_GET['resp']) || ($_GET['alcance'] ?? '') === 'obra') {
            return;
        }

        $usuario = (string) ($_SESSION['usuario'] ?? '');
        $rol = strtoupper(trim((string) ($_SESSION['permiso'] ?? '')));
        if ($rol !== 'R' || $usuario === '') {
            return;
        }

        $projectId = (int) ($_SESSION['project_id'] ?? 0);
        $nombre = $this->resolveOwnProfessionalName($usuario, $projectId);
        if ($nombre === null) {
            return;
        }

        $query = $_GET;
        $query['resp'] = $nombre;
        header('Location: /bi/responsables?' . http_build_query($query));
        exit;
    }

    /**
     * Nombre en `profesionales` de quien está en sesión, cruzando por email contra
     * `general_usuarios`. Null si no hay cruce (usuario sin profesional, o email
     * distinto entre las dos tablas). Usado por el filtro por defecto de Responsables
     * para el Residente (Tarea 4, 2026-08-24).
     */
    protected function resolveOwnProfessionalName(string $usuario, int $projectId): ?string
    {
        $usuario = trim($usuario);
        if ($usuario === '' || $projectId <= 0) {
            return null;
        }

        $dbName = (string) ($_SESSION['db'] ?? '');
        if ($dbName === '' || !preg_match('/^[a-zA-Z0-9_]+$/', $dbName)) {
            return null;
        }

        try {
            $tProf = TableResolver::resolveByPrefix($dbName, 'profesionales');
            $fila = $this->db->query(
                "SELECT p.nombre FROM {$tProf} p
                 INNER JOIN general_usuarios u ON u.email = p.email
                 WHERE u.usuario = ? AND p.project_id = ? AND p.email <> ''
                 LIMIT 1",
                [$usuario, $projectId]
            )->fetch();
        } catch (\Throwable $e) {
            error_log('Error resolviendo nombre propio para Responsables: ' . $e->getMessage());
            return null;
        }

        return $fila !== false ? (string) $fila['nombre'] : null;
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
