<?php

namespace App\Controllers\Gestion;

use App\Controllers\BaseController;

class SubcontratistasController extends BaseController
{
    public function index()
    {
        // Validar autenticación
        $this->requireAuth();

        // Obtener variables de sesión
        $vars = $this->getSessionVars();
        extract($vars); // $dbName, $semana, $proyecto, $permiso, etc.

        // Cargar vista Subcontratistas
        require PROJECT_ROOT . '/views/subcontratistas/subcontratistas.view.php';
    }
}
