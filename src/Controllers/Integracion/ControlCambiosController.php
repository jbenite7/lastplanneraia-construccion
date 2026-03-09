<?php

namespace App\Controllers\Integracion;

use App\Controllers\BaseController;

class ControlCambiosController extends BaseController
{
    public function index()
    {
        // Validar autenticación
        $this->requireAuth();

        // Obtener variables de sesión
        $vars = $this->getSessionVars();
        extract($vars); // $dbName, $semana, $proyecto, $permiso, etc.

        // Cargar vista Control de Cambios
        require PROJECT_ROOT . '/views/control-cambios/controlCambios.view.php';
    }
}
