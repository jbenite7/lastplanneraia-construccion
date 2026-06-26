<?php

namespace App\Controllers\Gestion;

use App\Controllers\BaseController;

use TableResolver;
class PdcController extends BaseController
{
    public function index()
    {
        // Validar autenticación
        $this->requireAuth();

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
        extract($vars); // $dbName, $semana, $proyecto, $permiso, etc.

        // Cargar vista PDC (refactorizada con estilos 2026)
        require PROJECT_ROOT . '/views/pdc/pdc.view.php';
    }
}
