<?php

namespace App\Controllers\Gestion;

use App\Controllers\BaseController;

class ProfesionalesController extends BaseController
{
    public function index()
    {
        // Validar autenticación
        $this->requireAuth();

        // Obtener variables de sesión
        $vars = $this->getSessionVars();
        extract($vars); // $dbName, $semana, $proyecto, $permiso, etc.

        // Cargar vista Profesionales (usa Handsontable para live editing)
        require PROJECT_ROOT . '/views/profesionales/profesionales.view.php';
    }
}
