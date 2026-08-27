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
     * Gate de acceso compartido por renderView() y renderCtPiloto(): el módulo está oculto
     * mientras se desarrolla, solo lo abren los roles/flag que resuelve BiPreviewAccessPolicy,
     * y hace falta sesión. 404 y no 403, para no confirmar que la pantalla existe.
     */
    private function assertBiPreviewAccessible(): void
    {
        if (!\App\Security\BiPreviewAccessPolicy::canOpen($_SESSION)) {
            \App\Core\ErrorPage::render(
                404,
                'Esta página no existe',
                'La dirección que abriste no corresponde a ninguna pantalla del producto. Puede que el enlace esté viejo o mal copiado.'
            );
            exit;
        }

        $this->requireAuth();
    }

    /**
     * Shared render: fetch data, inject into view, render with layout.
     */
    private function renderView(string $reportKey, string $viewFile): void
    {
        $this->assertBiPreviewAccessible();

        $projectIdsRaw = $_GET['project_ids'] ?? $_GET['project_id'] ?? [];
        try {
            $projectIds = $this->projectScope->resolve($projectIdsRaw, $_SESSION);
        } catch (DomainException $exception) {
            $this->abortUnauthorizedProjectScope($exception->getMessage());
        }

        $semana = (string) ($_GET['semana'] ?? $_SESSION['semana'] ?? $this->bi->currentWeekBogota());
        $role      = $this->projectScope->reportRole($projectIds, $_SESSION);

        if ($reportKey === 'cip') {
            $this->maybeRedirectToOwnScope($projectIds, $role);
        }

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
            // Task 7 paso 6: expone la bandera a bi-spa.js (window.__CT_PILOTO_ENABLED__ en
            // _layout.php) para que switchView('intermedia') pueda navegar de verdad a
            // /bi/intermedia en vez de solo alternar visibilidad. Compartido por las 8 hojas
            // porque las 8 usan este mismo _layout.php — no solo la de Intermedia.
            'ctPilotoEnabled'  => self::ctPilotoEnabled(),
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
        if (self::ctPilotoEnabled()) {
            $this->renderCtPiloto();
            return;
        }

        $this->renderView('intermedia', 'control-tower');
    }

    /**
     * Shell piloto de la isla React de la Torre (Task 6). Sirve views/bi/control-tower-piloto.php
     * en vez de views/bi/_layout.php + bi-spa.js — únicamente para la hoja Intermedia, y solo con
     * CT_PILOTO=1 en .env. Pasa por el MISMO gate de acceso que renderView() (Admin/D/R según
     * BiPreviewAccessPolicy + sesión activa): la bandera cambia qué se sirve, nunca quién entra.
     *
     * Bootstrap mínimo a propósito (YAGNI): solo el CSRF que necesita ct-app/src/lib/api.ts para
     * su primer POST. Task 7 decide qué más agrega el bootstrap cuando construya la pantalla real.
     */
    private function renderCtPiloto(): void
    {
        $this->assertBiPreviewAccessible();

        $bootstrap = [
            'csrfToken' => \App\Security\CsrfTokenManager::generate('ct_piloto'),
        ];
        $bootstrapJson = json_encode(
            $bootstrap,
            JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE
        );
        // json_encode() puede devolver false (p.ej. secuencia inválida de bytes); sin este
        // respaldo, window.__CT_BOOTSTRAP__ = ; queda como sintaxis JS rota y el bundle
        // entero no arranca. '{}' deja el bootstrap vacío en vez de tronar la página.
        if ($bootstrapJson === false) {
            $bootstrapJson = '{}';
        }

        // Shell sidebar (DS-027): mismo id que renderView() fija para las otras 7 hojas — sin
        // esto, shell_sidebar.php no resalta la entrada de Control Tower y, en el caso borde de
        // project_id=0 sin acceso, la entrada desaparece del todo (ver su comentario en línea ~85).
        $shellActive = 'control-tower';

        // Mismo mecanismo de cache-busting que PlanComprasController::index(): filemtime() del
        // bundle construido, no un contador a mano.
        $bundlePath = PROJECT_ROOT . '/public/ct-app/assets/ct.js';
        $assetVersion = is_file($bundlePath) ? (int) filemtime($bundlePath) : 0;

        // tokens.css evoluciona con lps-aia, no con el bundle: cache-busting propio.
        $tokensPath = PROJECT_ROOT . '/public/css/tokens.css';
        $tokensVersion = is_file($tokensPath) ? (int) filemtime($tokensPath) : 0;

        require PROJECT_ROOT . '/views/bi/control-tower-piloto.php';
    }

    /**
     * Lee CT_PILOTO de .env siguiendo el mismo patrón que DevDoor::flagEnabled()/env()
     * (src/Core/DevDoor.php): $_ENV primero, getenv() como respaldo, comparación estricta
     * contra '1'. Sin la bandera (no seteada o distinta de '1'), intermedia() no cambia de
     * comportamiento — ninguna otra hoja del controlador la consulta.
     */
    private static function ctPilotoEnabled(): bool
    {
        return (string) (self::env('CT_PILOTO') ?? '') === '1';
    }

    private static function env(string $key): ?string
    {
        if (isset($_ENV[$key])) {
            return (string) $_ENV[$key];
        }

        $value = getenv($key);

        return $value === false ? null : $value;
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

    /**
     * El Residente aterriza en Responsables viendo solo sus propios compromisos
     * (confirmado con Felipe, 2026-08-24), a menos que ya haya elegido explícitamente
     * un filtro (`resp`) o pedido ver toda la obra (`alcance=obra`).
     *
     * Se invoca DESDE renderView(), después del gate de acceso (BiPreviewAccessPolicy
     * + requireAuth) y con el mismo $projectIds/$role que ya resolvió esa función —
     * nunca antes del gate, y nunca contra $_SESSION directamente, porque el alcance
     * mostrado puede diferir del de sesión (ej. ?project_ids=70 con la 68 en sesión).
     * Corrección Tarea 4, revisión 2026-08-24.
     *
     * @param array<int,int> $projectIds
     */
    private function maybeRedirectToOwnScope(array $projectIds, string $role): void
    {
        if (isset($_GET['resp']) || ($_GET['alcance'] ?? '') === 'obra') {
            return;
        }

        // 'MULTI' significa selección de más de un proyecto: no hay un único
        // proyecto contra el cual resolver el nombre propio, así que no se aplica
        // el filtro por defecto.
        if ($role !== 'R' || count($projectIds) !== 1) {
            return;
        }

        $usuario = (string) ($_SESSION['usuario'] ?? '');
        if ($usuario === '') {
            return;
        }

        $nombre = $this->resolveOwnProfessionalName($usuario, $projectIds[0]);
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
