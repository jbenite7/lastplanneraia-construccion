<?php

namespace App\Controllers\Gestion;

use App\Controllers\BaseController;
use App\Security\RbacService;

use TableResolver;

class ContratosController extends BaseController
{
    public function index()
    {
        // Validar autenticación
        $this->requireAuth();
        $this->authorizePermission('lps.contratos.ver', 'No autorizado para consultar contratos.');

        $this->syncRequestedWeekContext();

        // Obtener variables de sesión
        $vars = $this->getSessionVars();
		$rbac = new RbacService($this->db);
		$vars['canEditContracts'] = $rbac->can('lps.contratos.editar');
		$vars['canAutoDefineContracts'] = $rbac->can('lps.contratos.auto_definir');
        if (($vars['area'] ?? 'Construccion') === 'Pre-Construccion') {
            http_response_code(404);
            echo '<h1>Modulo no disponible</h1><p>Contratos no esta disponible para proyectos de preconstruccion.</p>';
            return;
        }
        extract($vars); // $dbName, $semana, $proyecto, $permiso, etc.

        // Shell sidebar (DS-027): semanas del proyecto para el chip de contexto
        // y los flyouts de semana del rail.
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
                error_log('Error cargando semanas para el shell Contratos: ' . $e->getMessage());
            }
        }
        $shellActive = 'contratos';
        $shellModuleLabel = 'Paquetes de Contratación';

        // Cargar vista Contratos
        require PROJECT_ROOT . '/views/contratos/contratos.view.php';
    }
}
