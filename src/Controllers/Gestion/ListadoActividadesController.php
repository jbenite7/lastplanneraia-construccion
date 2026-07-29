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

        // Shell sidebar (DS-027): semanas del proyecto para el chip de contexto
        // y los flyouts de semana del rail.
        $shellWeeks = [];
        if (!empty($dbName) && preg_match('/^[a-zA-Z0-9_]+$/', $dbName)) {
            try {
                $tSaShell = TableResolver::resolveByPrefix($dbName, 'semanas_activas');
                $projectIdShell = TableResolver::getProjectIdByPrefix($dbName);
                $stmtShellWeeks = $this->db->queryWithProject(
                    "SELECT Semana, Fecha_Inicio_Sem, Fecha_Fin_Sem FROM {$tSaShell} WHERE project_id = ? ORDER BY Semana DESC",
                    [$projectIdShell]
                );
                $shellWeeks = $stmtShellWeeks->fetchAll(\PDO::FETCH_ASSOC) ?: [];
            } catch (Throwable $e) {
                error_log('Error cargando semanas para el shell Listado de Actividades: ' . $e->getMessage());
            }
        }
        // Sin item activo en el rail, a proposito. El 2026-07-29 se retiraron
        // «Familias de Actividades» y «Paquetes de Contratacion» del sidebar
        // (son la interfaz del PDC viejo; ver views/partials/shell_sidebar.php),
        // pero la ruta se dejo servida y accesible por su direccion. Este
        // controlador seguia pidiendo 'listado-actividades' como item activo, y
        // DesignSystemComponent lanza InvalidArgumentException cuando el activo
        // no esta entre los items renderizados: la ruta devolvia «Error Interno
        // del Servidor» con status 200 —el error sale despues del <head>, asi
        // que las cabeceras ya estaban enviadas—. Una ruta sin entrada en el
        // rail no debe marcar ninguna como actual.
        $shellActive = '';
        $shellModuleLabel = 'Familias de Actividades';

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
