<?php

namespace App\Controllers\Gestion;

use App\Controllers\BaseController;
use App\Security\RbacService;

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

        // Cargar vista Contratos
        require PROJECT_ROOT . '/views/contratos/contratos.view.php';
    }
}
