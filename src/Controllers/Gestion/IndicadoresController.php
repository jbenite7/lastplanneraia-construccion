<?php

namespace App\Controllers\Gestion;

use App\Controllers\BaseController;

class IndicadoresController extends BaseController
{
    public function index()
    {
        // Validar autenticación
        $this->requireAuth();

        // Obtener variables de sesión
        $vars = $this->getSessionVars();
        extract($vars); // $dbName, $semana, $proyecto, $permiso, etc.

        // Cargar vista Indicadores
        require PROJECT_ROOT . '/views/indicadores/indicadores.view.php';
    }
}
