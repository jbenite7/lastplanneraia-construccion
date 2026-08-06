<?php

namespace App\Controllers\Programacion;

use App\Controllers\BaseController;
use App\Security\CsrfTokenManager;

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
                // El aislamiento por proyecto va explícito en cada consulta, no delegado a la
                // reescritura de queryWithProject: estas semanas deciden sobre cuál se opera.
                $projectId = TableResolver::getProjectIdByPrefix($dbName);

                // Obtenemos el panorama real del proyecto actual
                $query = "SELECT
                            MAX(Semana) as max_overall,
                            MAX(CASE WHEN Semanal_Confirmada = 1 THEN Semana ELSE NULL END) as max_confirmed
                          FROM " . TableResolver::resolveByPrefix($dbName, 'semanas_activas') . "
                          WHERE project_id = ?";
                $stmt = $this->db->queryWithProject($query, [$projectId]);
                $res = $stmt->fetch();

                $maxOverall = (int) ($res['max_overall'] ?? 0);
                $maxConfirmed = ($res['max_confirmed'] !== null) ? (int) $res['max_confirmed'] : null;

                $stmtProgram = $this->db->queryWithProject("SELECT MAX(Semana) as max_program FROM " . TableResolver::resolveByPrefix($dbName, 'programa_consolidado') . " WHERE project_id = ?", [$projectId]);
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
                    $stmtStatus = $this->db->queryWithProject("SELECT Semanal_Confirmada FROM " . TableResolver::resolveByPrefix($dbName, 'semanas_activas') . " WHERE Semana = ? AND project_id = ?", [$semanaBaseActualizacion, $projectId]);
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
        $vars['csrfToken'] = CsrfTokenManager::generate('programa_general_save');
        extract($vars); // $dbName, $semana, $proyecto, $permiso, $maxSemana, $semanalConfirmada etc.

        // Shell sidebar (DS-027): semanas del proyecto para el chip de contexto.
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
                error_log('Error cargando semanas para el shell Actualizar Cronograma: ' . $e->getMessage());
            }
        }
        $shellActive = 'actualizar-cronograma';
        $shellModuleLabel = 'Actualizar Cronograma';

        // Cargar vista Programa General Actualizar
        require PROJECT_ROOT . '/views/programa-general-actualizar/programaGeneralActualizar.view.php';
    }
}
