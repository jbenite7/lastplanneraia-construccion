<?php

namespace App\Controllers\Integracion;

use App\Controllers\BaseController;

use TableResolver;

class ControlCambiosController extends BaseController
{
    public function index()
    {
        // Validar autenticación
        $this->requireAuth();

        // Obtener variables de sesión
        $vars = $this->getSessionVars();
        extract($vars); // $dbName, $semana, $proyecto, $permiso, etc.

        // Shell sidebar (DS-027): semanas del proyecto para el chip de contexto
        // (Control de Cambios no es week-scoped, pero se provee para paridad de flyouts).
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
                error_log('Error cargando semanas para el shell Control de Cambios: ' . $e->getMessage());
            }
        }
        $shellActive = 'control-cambios';
        $shellModuleLabel = 'Control de Cambios';

        // Cargar vista Control de Cambios
        require PROJECT_ROOT . '/views/control-cambios/controlCambios.view.php';
    }
}
