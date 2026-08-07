<?php

namespace App\Controllers\Gestion;

use App\Controllers\BaseController;
use App\Security\CsrfTokenManager;

use TableResolver;

class SubcontratistasController extends BaseController
{
    public function index()
    {
        // Validar autenticación
        $this->requireAuth();

        // Obtener variables de sesión
        $vars = $this->getSessionVars();
        extract($vars); // $dbName, $semana, $proyecto, $permiso, etc.

        // Shell sidebar (DS-027): semanas del proyecto para el chip de contexto
        // (Subcontratistas no es week-scoped, pero se provee para paridad de flyouts).
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
                error_log('Error cargando semanas para el shell Subcontratistas: ' . $e->getMessage());
            }
        }
        $shellActive = 'subcontratistas';
        $shellModuleLabel = 'Subcontratistas';
        $csrfToken = CsrfTokenManager::generate('subcontratistas');

        // Cargar vista Subcontratistas
        require PROJECT_ROOT . '/views/subcontratistas/subcontratistas.view.php';
    }
}
