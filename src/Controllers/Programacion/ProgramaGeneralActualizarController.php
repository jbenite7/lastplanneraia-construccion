<?php

namespace App\Controllers\Programacion;

use App\Controllers\BaseController;

class ProgramaGeneralActualizarController extends BaseController
{
    public function index()
    {
        // Validar autenticación
        $this->requireAuth();

        // Obtener variables de sesión
        $vars = $this->getSessionVars();
        extract($vars); // $dbName, $semana, $proyecto, $permiso, etc.

        // Cargar vista Programa General Actualizar
        require PROJECT_ROOT . '/views/programa-general-actualizar/programaGeneralActualizar.view.php';
    }
}
