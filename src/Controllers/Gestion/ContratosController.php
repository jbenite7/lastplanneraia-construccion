<?php

namespace App\Controllers\Gestion;

use App\Controllers\BaseController;

class ContratosController extends BaseController
{
    public function index()
    {
        // Validar autenticación
        $this->requireAuth();

        $this->syncRequestedWeekContext();

        // Obtener variables de sesión
        $vars = $this->getSessionVars();
        extract($vars); // $dbName, $semana, $proyecto, $permiso, etc.

        // Cargar vista Contratos
        require PROJECT_ROOT . '/views/contratos/contratos.view.php';
    }
}
