<?php

namespace App\Controllers\Gestion;

use App\Controllers\BaseController;
use App\Security\CsrfTokenManager;

use TableResolver;
class PdcController extends BaseController
{
    public function index()
    {
        // Validar autenticación
        $this->requireAuth();

        $this->syncRequestedWeekContext();

        $dbName = (string) ($_SESSION['db'] ?? '');
        $semana = (int) ($_SESSION['semana'] ?? 0);

        if ($dbName !== '') {
            try {
                $stmt = $this->db->queryWithProject("SELECT MAX(Semana) AS maxSemana FROM " . TableResolver::resolveByPrefix($dbName, 'semanas_activas') . "");
                $maxSemana = (int) ($stmt->fetch()['maxSemana'] ?? 0);

                if ($maxSemana > 0 && ($semana <= 0 || $semana > $maxSemana)) {
                    $_SESSION['semana'] = $maxSemana;
                }
            } catch (\Throwable $e) {
                error_log('PdcController::index week recovery error: ' . $e->getMessage());
            }
        }

        $autoSyncPdcOnLoad = !empty($_SESSION['pdc_sync_on_load']);
        $pdcSyncOrigin = (string) ($_SESSION['pdc_sync_origin'] ?? '');
        unset($_SESSION['pdc_sync_on_load'], $_SESSION['pdc_sync_origin']);
        $_SESSION['seccion'] = 'planCompras';

        // Obtener variables de sesión
        $vars = $this->getSessionVars();
        $vars['autoSyncPdcOnLoad'] = $autoSyncPdcOnLoad;
        $vars['pdcSyncOrigin'] = $pdcSyncOrigin;
        $vars['csrfToken'] = CsrfTokenManager::generate('pdc_save');
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
            } catch (\Throwable $e) {
                error_log('Error cargando semanas para el shell PDC: ' . $e->getMessage());
            }
        }
        $shellActive = 'plan-compras';
        $shellModuleLabel = 'Plan de Compras';

        // Cargar vista PDC (refactorizada con estilos 2026)
        require PROJECT_ROOT . '/views/pdc/pdc.view.php';
    }
}
