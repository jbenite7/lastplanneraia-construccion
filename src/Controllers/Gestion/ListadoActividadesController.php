<?php

namespace App\Controllers\Gestion;

use App\Controllers\BaseController;
use App\Security\CsrfTokenManager;
use Throwable;

use TableResolver;
class ListadoActividadesController extends BaseController
{
    public function index()
    {
        // Validar autenticación
        $this->requireAuth();

        if (!$this->syncRequestedWeekContext()) {
            $this->syncMaxSemanaContext();
        }

        // Obtener variables de sesión
        $vars = $this->getSessionVars();
        $vars['csrfToken'] = CsrfTokenManager::generate('listado_actividades_save');
        extract($vars); // $dbName, $semana, $proyecto, $permiso, etc.

        // Cargar vista Listado de Actividades
        require PROJECT_ROOT . '/views/listado-actividades/listadoActividades.view.php';
    }

    private function syncMaxSemanaContext(): void
    {
        $dbName = (string) ($_SESSION['db'] ?? '');

        if ($dbName === '' || preg_match('/^[A-Za-z0-9_]+$/', $dbName) !== 1) {
            return;
        }

        try {
            $query = "SELECT MAX(Semana) FROM " . TableResolver::resolveByPrefix($dbName, 'semanas_activas') . "";
            $maxSemana = (int) $this->db->queryWithProject($query)->fetchColumn();

            $_SESSION['Max_Semana'] = $maxSemana;
            $_SESSION['semana'] = $maxSemana;
        } catch (Throwable $e) {
            error_log('ListadoActividadesController syncMaxSemanaContext error: ' . $e->getMessage());
        }
    }
}
