<?php

namespace App\Controllers\Gestion;

use App\Controllers\BaseController;

class ListadoActividadesController extends BaseController
{
    public function index()
    {
        // Validar autenticación
        $this->requireAuth();

        // Obtener variables de sesión
        $vars = $this->getSessionVars();
        extract($vars); // $dbName, $semana, $proyecto, $permiso, etc.

        // Cargar vista Listado de Actividades
        require PROJECT_ROOT . '/views/listado-actividades/listadoActividades.view.php';
    }
}
