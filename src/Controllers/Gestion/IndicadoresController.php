<?php

namespace App\Controllers\Gestion;

use App\Controllers\BaseController;

use TableResolver;

class IndicadoresController extends BaseController
{
    public function index()
    {
        // Validar autenticación
        $this->requireAuth();
        $this->syncRequestedWeekContext();

        // Obtener variables de sesión
        $vars = $this->getSessionVars();
        extract($vars); // $dbName, $semana, $proyecto, $permiso, etc.

        // Shell sidebar (DS-027): semanas del proyecto para el chip de contexto
        // (Indicadores no es week-scoped; se provee para paridad de flyouts).
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
                error_log('Error cargando semanas para el shell Indicadores: ' . $e->getMessage());
            }
        }
        // C-46: Max_Semana resuelto en servidor para el bloque .encabezado.
        ['maxSemana' => $maxSemana] = $this->getWeekStatusVars(
            (string) ($vars['dbName'] ?? ''),
            (int) ($vars['semana'] ?? 0)
        );

        $shellActive = 'indicadores';
        $shellModuleLabel = 'Indicadores LPS';

        // Cargar vista Indicadores
        require PROJECT_ROOT . '/views/indicadores/indicadores.view.php';
    }
}
