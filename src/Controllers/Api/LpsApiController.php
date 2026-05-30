<?php

namespace App\Controllers\Api;

use App\Services\LpsService;
use PDO;
use Throwable;

class LpsApiController
{
    private $db;
    private $lpsService;

    public function __construct()
    {
        $this->db = \Database::getInstance();
        $this->lpsService = new LpsService();
    }

    private function getContext(): array
    {
        $dbPrefix = $_SESSION['db'] ?? '';
        $semana = (int) ($_SESSION['semana'] ?? 0);
        $proyecto = $_SESSION['proyecto'] ?? '';

        if (!$dbPrefix || $semana <= 0 || !$proyecto) {
            return [];
        }

        // Consultar ID de usuario
        $userStmt = $this->db->prepare("SELECT Id FROM general_usuarios WHERE usuario = ? LIMIT 1");
        $userStmt->execute([$_SESSION['usuario'] ?? '']);
        $user = $userStmt->fetch(PDO::FETCH_ASSOC);
        $usuarioId = $user ? (int) $user['Id'] : 0;

        // Consultar ID de proyecto
        $projStmt = $this->db->prepare("SELECT ID FROM general_proyectos_procesos WHERE Proyecto_Proceso = ? AND Area = 'Construccion' LIMIT 1");
        $projStmt->execute([$proyecto]);
        $proj = $projStmt->fetch(PDO::FETCH_ASSOC);
        $proyectoId = $proj ? (int) $proj['ID'] : 0;

        return [
            'dbPrefix' => $dbPrefix,
            'semana' => $semana,
            'usuarioId' => $usuarioId,
            'proyectoId' => $proyectoId,
        ];
    }

    public function comments(): void
    {
        require_once PROJECT_ROOT . '/src/Legacy/rbac_guard.php';
        rbac_guard_require_permission('lps.programacion_semanal.ver');
        header('Content-Type: application/json; charset=utf-8');
        $context = $this->getContext();
        if (empty($context)) {
            echo json_encode(["respuesta" => "ERROR", "mensaje" => "Sesión expirada o contexto inválido."], JSON_UNESCAPED_UNICODE);
            return;
        }

        $consecutivo = filter_var($_GET['consecutivo'] ?? 0, FILTER_VALIDATE_INT);
        $escalamientoId = isset($_GET['escalamiento_id']) ? filter_var($_GET['escalamiento_id'], FILTER_VALIDATE_INT) : null;

        if ($consecutivo <= 0) {
            echo json_encode(["respuesta" => "ERROR", "mensaje" => "Actividad inválida."], JSON_UNESCAPED_UNICODE);
            return;
        }

        try {
            $comments = $this->lpsService->getActivityComments($context['dbPrefix'], $consecutivo, $context['semana'], $escalamientoId);
            echo json_encode(["respuesta" => "OK", "data" => $comments], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $t) {
            echo json_encode(["respuesta" => "ERROR", "mensaje" => $t->getMessage()], JSON_UNESCAPED_UNICODE);
        }
    }

    public function addComment(): void
    {
        require_once PROJECT_ROOT . '/src/Legacy/rbac_guard.php';
        rbac_guard_require_permission('lps.programacion_semanal.editar');
        header('Content-Type: application/json; charset=utf-8');
        $context = $this->getContext();
        if (empty($context)) {
            echo json_encode(["respuesta" => "ERROR", "mensaje" => "Sesión expirada o contexto inválido."], JSON_UNESCAPED_UNICODE);
            return;
        }

        $consecutivo = filter_var($_POST['consecutivo'] ?? 0, FILTER_VALIDATE_INT);
        $comentario = trim($_POST['comentario'] ?? '');
        $parentId = !empty($_POST['parent_id']) ? filter_var($_POST['parent_id'], FILTER_VALIDATE_INT) : null;
        $escalamientoId = !empty($_POST['escalamiento_id']) ? filter_var($_POST['escalamiento_id'], FILTER_VALIDATE_INT) : null;
        $menciones = !empty($_POST['menciones']) ? json_decode($_POST['menciones'], true) : null;

        if ($consecutivo <= 0 || empty($comentario)) {
            echo json_encode(["respuesta" => "ERROR", "mensaje" => "Comentario y actividad requeridos."], JSON_UNESCAPED_UNICODE);
            return;
        }

        try {
            $commentId = $this->lpsService->addActivityComment(
                $context['dbPrefix'],
                $context['proyectoId'],
                $consecutivo,
                $context['semana'],
                $context['usuarioId'],
                $comentario,
                $parentId ? (int) $parentId : null,
                $escalamientoId ? (int) $escalamientoId : null,
                $menciones,
            );

            if ($commentId > 0) {
                echo json_encode(["respuesta" => "OK", "comment_id" => $commentId], JSON_UNESCAPED_UNICODE);
            } else {
                echo json_encode(["respuesta" => "ERROR", "mensaje" => "No se pudo registrar el comentario."], JSON_UNESCAPED_UNICODE);
            }
        } catch (Throwable $t) {
            echo json_encode(["respuesta" => "ERROR", "mensaje" => $t->getMessage()], JSON_UNESCAPED_UNICODE);
        }
    }

    public function registerCrisis(): void
    {
        require_once PROJECT_ROOT . '/src/Legacy/rbac_guard.php';
        rbac_guard_require_permission('lps.programacion_semanal.editar');
        header('Content-Type: application/json; charset=utf-8');
        $context = $this->getContext();
        if (empty($context)) {
            echo json_encode(["respuesta" => "ERROR", "mensaje" => "Sesión expirada o contexto inválido."], JSON_UNESCAPED_UNICODE);
            return;
        }

        $consecutivo = filter_var($_POST['consecutivo'] ?? 0, FILTER_VALIDATE_INT);
        $modulo = $_POST['modulo'] ?? '';
        $trigger = $_POST['trigger'] ?? 'MANUAL';

        if ($consecutivo <= 0 || !in_array($modulo, ['PG', 'PI', 'PS'], true)) {
            echo json_encode(["respuesta" => "ERROR", "mensaje" => "Módulo y actividad requeridos."], JSON_UNESCAPED_UNICODE);
            return;
        }

        try {
            $res = $this->lpsService->registerCrisisAlert(
                $context['dbPrefix'],
                $context['proyectoId'],
                $context['semana'],
                $consecutivo,
                $modulo,
                $trigger,
            );

            echo json_encode(["respuesta" => $res ? "OK" : "ERROR", "mensaje" => $res ? "Alerta registrada" : "No se pudo registrar la alerta"], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $t) {
            echo json_encode(["respuesta" => "ERROR", "mensaje" => $t->getMessage()], JSON_UNESCAPED_UNICODE);
        }
    }

    public function closeCrisis(): void
    {
        require_once PROJECT_ROOT . '/src/Legacy/rbac_guard.php';
        rbac_guard_require_permission('lps.programacion_semanal.editar');
        header('Content-Type: application/json; charset=utf-8');
        $context = $this->getContext();
        if (empty($context)) {
            echo json_encode(["respuesta" => "ERROR", "mensaje" => "Sesión expirada o contexto inválido."], JSON_UNESCAPED_UNICODE);
            return;
        }

        $alertaId = filter_var($_POST['alerta_id'] ?? 0, FILTER_VALIDATE_INT);
        $justificacion = trim($_POST['justificacion'] ?? '');

        if ($alertaId <= 0 || mb_strlen($justificacion) < 100) {
            echo json_encode(["respuesta" => "ERROR", "mensaje" => "Justificación obligatoria de al menos 100 caracteres."], JSON_UNESCAPED_UNICODE);
            return;
        }

        try {
            $res = $this->lpsService->closeCrisisAlert(
                $context['dbPrefix'],
                (int) $alertaId,
                $context['usuarioId'],
                $justificacion,
            );

            echo json_encode(["respuesta" => $res ? "OK" : "ERROR", "mensaje" => $res ? "Crisis mitigada exitosamente" : "No se pudo mitigar la crisis"], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $t) {
            echo json_encode(["respuesta" => "ERROR", "mensaje" => $t->getMessage()], JSON_UNESCAPED_UNICODE);
        }
    }
}
