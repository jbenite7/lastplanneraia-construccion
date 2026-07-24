<?php

namespace App\Controllers\Gestion;

use App\Controllers\BaseController;

use TableResolver;

class ProfesionalesController extends BaseController
{
    public function index()
    {
        // Validar autenticación
        $this->requireAuth();

        // Obtener variables de sesión
        $vars = $this->getSessionVars();
        extract($vars); // $dbName, $semana, $proyecto, $permiso, etc.

        // Shell sidebar (DS-027): semanas del proyecto para el chip de contexto
        // (Profesionales no es week-scoped, pero se provee para paridad de flyouts).
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
                error_log('Error cargando semanas para el shell Profesionales: ' . $e->getMessage());
            }
        }
        $shellActive = 'profesionales';
        $shellModuleLabel = 'Profesionales';

        // Cargar vista Profesionales (usa Handsontable para live editing)
        require PROJECT_ROOT . '/views/profesionales/profesionales.view.php';
    }
}
