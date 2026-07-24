<?php

namespace App\Controllers\Programacion;

use App\Controllers\BaseController;
use App\Security\CsrfTokenManager;
use App\Services\ProjectLandingService;

use TableResolver;
class ProgramacionSemanalController extends BaseController
{
    public function index()
    {
        $this->requireAuth();
        $this->healWeeklyContext();

        $dbName = $_SESSION['db'] ?? '';
        $semana = (int) ($_SESSION['semana'] ?? 0);
        $proyecto = $_SESSION['proyecto'] ?? '';
        $nombreUsuario = $_SESSION['nombreUsuario'] ?? '';
        $permiso = $_SESSION['permiso'] ?? ''; // Requerido para lógica de permisos en vistas
        $pdcActivo = $_SESSION['pdcActivo'] ?? '';
        $area = $_SESSION['area'] ?? 'Construccion';
        $csrfToken = CsrfTokenManager::generate('semanal_save');

        $subcontratistas = [];
        $profesionales = [];
        $categoriasCnc = [];

        if (!empty($dbName) && preg_match('/^[A-Za-z0-9_]+$/', $dbName)) {
            try {
                $stmtSub = $this->db->queryWithProject("SELECT subcontratista FROM " . TableResolver::resolveByPrefix($dbName, 'subcontratistas') . " WHERE activo = 1 ORDER BY subcontratista ASC");
                $subcontratistas = $stmtSub->fetchAll();

                $stmtProf = $this->db->queryWithProject("SELECT nombre FROM " . TableResolver::resolveByPrefix($dbName, 'profesionales') . " WHERE Activo = 1 ORDER BY nombre ASC");
                $profesionales = $stmtProf->fetchAll();

                $stmtCnc = $this->db->query("SELECT DISTINCT Categoria_CNC FROM general_cnc ORDER BY Categoria_CNC ASC");
                $categoriasCnc = $stmtCnc->fetchAll();
            } catch (\Throwable $e) {
                error_log('Error cargando listas de programación semanal: ' . $e->getMessage());
                $subcontratistas = [];
                $profesionales = [];
                $categoriasCnc = [];
            }
        }

        // Shell sidebar (DS-027): semanas del proyecto para el chip de contexto.
        $shellWeeks = [];
        if (!empty($dbName) && preg_match('/^[a-zA-Z0-9_]+$/', $dbName)) {
            try {
                $tSaShell = TableResolver::resolveByPrefix($dbName, 'semanas_activas');
                $projectIdShell = TableResolver::getProjectIdByPrefix($dbName);
                $stmtShellWeeks = $this->db->queryWithProject(
                    "SELECT Semana, Fecha_Inicio_Sem, Fecha_Fin_Sem FROM {$tSaShell} WHERE project_id = ? ORDER BY Semana DESC",
                    [$projectIdShell]
                );
                $shellWeeks = $stmtShellWeeks->fetchAll(\PDO::FETCH_ASSOC) ?: [];
            } catch (\Throwable $e) {
                error_log('Error cargando semanas para el shell Programación Semanal: ' . $e->getMessage());
            }
        }
        $shellActive = 'programacion-semanal';
        $shellModuleLabel = 'Programación Semanal';

        require PROJECT_ROOT . '/views/programacion-semanal/programacion_semanal.view.php';
    }

    public function cnp()
    {
        $this->requireAuth();
        $this->healWeeklyContext();

        $dbName = $_SESSION['db'] ?? '';
        $semana = (int) ($_SESSION['semana'] ?? 0);
        $proyecto = $_SESSION['proyecto'] ?? '';
        $nombreUsuario = $_SESSION['nombreUsuario'] ?? '';
        $area = $_SESSION['area'] ?? 'Construccion';

        require PROJECT_ROOT . '/views/programacion-semanal/CNP.view.php';
    }

    public function cnc()
    {
        $this->requireAuth();
        $this->healWeeklyContext();

        $dbName = $_SESSION['db'] ?? '';
        $semana = (int) ($_SESSION['semana'] ?? 0);
        $proyecto = $_SESSION['proyecto'] ?? '';
        $nombreUsuario = $_SESSION['nombreUsuario'] ?? '';
        $area = $_SESSION['area'] ?? 'Construccion';

        require PROJECT_ROOT . '/views/programacion-semanal/CNC.view.php';
    }

    public function cic()
    {
        $this->requireAuth();
        $this->healWeeklyContext();

        $dbName = $_SESSION['db'] ?? '';
        $semana = (int) ($_SESSION['semana'] ?? 0);
        $proyecto = $_SESSION['proyecto'] ?? '';
        $nombreUsuario = $_SESSION['nombreUsuario'] ?? '';

        require PROJECT_ROOT . '/views/programacion-semanal/CIC.view.php';
    }

    private function healWeeklyContext(): void
    {
        $dbName = (string) ($_SESSION['db'] ?? '');

        if ($dbName === '' || preg_match('/^[A-Za-z0-9_]+$/', $dbName) !== 1) {
            header('Location: /proyectos');
            exit;
        }

        $currentWeek = (int) ($_SESSION['semana'] ?? 0);
        $landingService = new ProjectLandingService();
        $context = $landingService->sanitizeWeek($dbName, $currentWeek);

        if (!$context['hasActiveWeeks']) {
            $_SESSION['semana'] = 0;
            header('Location: /programa-general-actualizar');
            exit;
        }

        if ($currentWeek !== (int) $context['week']) {
            $_SESSION['semana'] = (int) $context['week'];
        }
    }
}
