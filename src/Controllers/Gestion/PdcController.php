<?php

namespace App\Controllers\Gestion;

use App\Controllers\BaseController;

class PdcController extends BaseController
{
    public function index()
    {
        // Validar autenticación
        $this->requireAuth();

        // Obtener variables de sesión
        $vars = $this->getSessionVars();
        extract($vars); // $dbName, $semana, $proyecto, $permiso, etc.

        // Cargar vista PDC (refactorizada con estilos 2026)
        require PROJECT_ROOT . '/views/pdc/pdc.view.php';
    }
}
