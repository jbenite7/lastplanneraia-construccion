<?php

namespace App\Controllers\Programacion;

use App\Controllers\BaseController;

use TableResolver;
class ProgramaGeneralActualizarController extends BaseController
{
    public function index()
    {
        // Validar autenticación
        $this->requireAuth();

        // Recuperación de contexto si la semana se perdió (Refresco)
        $dbName = $_SESSION['db'] ?? '';
        $semana = (int) ($_SESSION['semana'] ?? 0);

        // Autocuración y Blindaje de Contexto
        $maxOverall = 0;
        $maxConfirmed = null;
        $maxProgramWeek = 0;
        $semanalConfirmada = 0;
        $semanaBaseActualizacion = 0;
        $semanaObjetivoActualizacion = 1;

        if ($dbName !== '') {
            try {
                // Obtenemos el panorama real del proyecto actual
                $query = "SELECT
                            MAX(Semana) as max_overall,
                            MAX(CASE WHEN Semanal_Confirmada = 1 THEN Semana ELSE NULL END) as max_confirmed
                          FROM " . TableResolver::resolveByPrefix($dbName, 'semanas_activas') . "";
                $stmt = $this->db->queryWithProject($query);
                $res = $stmt->fetch();

                $maxOverall = (int) ($res['max_overall'] ?? 0);
                $maxConfirmed = ($res['max_confirmed'] !== null) ? (int) $res['max_confirmed'] : null;

                $stmtProgram = $this->db->queryWithProject("SELECT MAX(Semana) as max_program FROM " . TableResolver::resolveByPrefix($dbName, 'programa_consolidado') . "");
                $programRes = $stmtProgram->fetch();
                $maxProgramWeek = (int) ($programRes['max_program'] ?? 0);

                if ($maxOverall <= 0) {
                    $semanaBaseActualizacion = 0;
                    $semanaObjetivoActualizacion = max(1, $maxProgramWeek ?: 1);
                } elseif ($maxProgramWeek > $maxOverall) {
                    $semanaObjetivoActualizacion = $maxProgramWeek;
                    $semanaBaseActualizacion = max(1, min($maxOverall, $semanaObjetivoActualizacion - 1));
                } else {
                    $semanaBaseActualizacion = ($semana > 0 && $semana <= $maxOverall)
                        ? $semana
                        : (($maxConfirmed !== null) ? $maxConfirmed : $maxOverall);
                    $semanaObjetivoActualizacion = $semanaBaseActualizacion + 1;
                }

                $_SESSION['semana'] = $semanaBaseActualizacion;
                $semana = $semanaBaseActualizacion;

                // Obtener estado específico de la semana actual
                if ($semanaBaseActualizacion > 0) {
                    $stmtStatus = $this->db->queryWithProject("SELECT Semanal_Confirmada FROM " . TableResolver::resolveByPrefix($dbName, 'semanas_activas') . " WHERE Semana = ?", [$semanaBaseActualizacion]);
                    $semanalConfirmada = (int) ($stmtStatus->fetchColumn() ?: 0);
                }

            } catch (\Exception $e) {
                // Silencioso
            }
        }

        // Obtener variables de sesión actualizadas
        $vars = $this->getSessionVars();
        $vars['maxSemana'] = $maxOverall;
        $vars['semanalConfirmada'] = $semanalConfirmada;
        $vars['semanaBaseActualizacion'] = $semanaBaseActualizacion;
        $vars['semanaObjetivoActualizacion'] = $semanaObjetivoActualizacion;
        extract($vars); // $dbName, $semana, $proyecto, $permiso, $maxSemana, $semanalConfirmada etc.

        // Cargar vista Programa General Actualizar
        require PROJECT_ROOT . '/views/programa-general-actualizar/programaGeneralActualizar.view.php';
    }
}
