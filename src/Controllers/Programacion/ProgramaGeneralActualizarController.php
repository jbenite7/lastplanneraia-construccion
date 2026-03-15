<?php

namespace App\Controllers\Programacion;

use App\Controllers\BaseController;

class ProgramaGeneralActualizarController extends BaseController
{
    public function index()
    {
        // Validar autenticación
        $this->requireAuth();

        // Recuperación de contexto si la semana se perdió (Refresco)
        $dbName = $_SESSION['db'] ?? '';
        $semana = (int)($_SESSION['semana'] ?? 0);

        // Autocuración y Blindaje de Contexto
        $maxOverall = 0;
        $maxConfirmed = null;
        $semanalConfirmada = 0;

        if ($dbName !== '') {
            try {
                // Obtenemos el panorama real del proyecto actual
                $query = "SELECT 
                            MAX(Semana) as max_overall,
                            MAX(CASE WHEN Semanal_Confirmada = 1 THEN Semana ELSE NULL END) as max_confirmed
                          FROM {$dbName}_semanas_activas";
                $stmt = $this->db->query($query);
                $res = $stmt->fetch();
                
                $maxOverall = (int)($res['max_overall'] ?? 0);
                $maxConfirmed = ($res['max_confirmed'] !== null) ? (int)$res['max_confirmed'] : null;
                
                // Si la semana en sesión es inválida (0, negativa o mayor al máximo del proyecto),
                // aplicamos el Smart Default para este módulo.
                if ($semana <= 0 || $semana > $maxOverall) {
                    $_SESSION['semana'] = ($maxConfirmed !== null) ? $maxConfirmed : $maxOverall;
                    $semana = $_SESSION['semana'];
                }

                // Obtener estado específico de la semana actual
                $stmtStatus = $this->db->prepare("SELECT Semanal_Confirmada FROM {$dbName}_semanas_activas WHERE Semana = ?");
                $stmtStatus->execute([$semana]);
                $semanalConfirmada = (int)($stmtStatus->fetchColumn() ?: 0);

            } catch (\Exception $e) {
                // Silencioso
            }
        }

        // Obtener variables de sesión actualizadas
        $vars = $this->getSessionVars();
        $vars['maxSemana'] = $maxOverall;
        $vars['semanalConfirmada'] = $semanalConfirmada;
        extract($vars); // $dbName, $semana, $proyecto, $permiso, $maxSemana, $semanalConfirmada etc.

        // Cargar vista Programa General Actualizar
        require PROJECT_ROOT . '/views/programa-general-actualizar/programaGeneralActualizar.view.php';
    }
}
