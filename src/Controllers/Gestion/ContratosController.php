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
