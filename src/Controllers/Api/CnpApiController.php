<?php

namespace App\Controllers\Api;

use App\Security\LpsWeekEditPolicy;
use PDO;
use Throwable;

use TableResolver;
class CnpApiController
{
    private $db;

    public function __construct()
    {
        $this->db = \Database::getInstance();
    }

    public function list(): void
    {
        require_once PROJECT_ROOT . '/src/Legacy/rbac_guard.php';
        rbac_guard_require_permission('lps.cnp.ver');
        $dbPrefix = $_SESSION['db'] ?? '';
        $semana = filter_var($_POST['semana'] ?? $_GET['semana'] ?? 0, FILTER_VALIDATE_INT);

        if (!$dbPrefix || $semana <= 0) {
            $this->jsonError("Sesión expirada o semana inválida.");
            return;
        }

        try {
            $projectId = $this->projectId($dbPrefix);
            $query = "SELECT * FROM " . TableResolver::resolveByPrefix($dbPrefix, 'programacion_semanal') . " WHERE project_id = ? AND Semana = ? AND Activa = 0";
            $data = $this->db->queryWithProject($query, [$projectId, $semana], $projectId)->fetchAll(PDO::FETCH_ASSOC);

            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(["data" => $data], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $t) {
            $this->jsonError("Error CNP List: " . $t->getMessage());
        }
    }

    public function save(): void
    {
        require_once PROJECT_ROOT . '/src/Legacy/rbac_guard.php';
        rbac_guard_require_permission('lps.cnp.editar');
        $dbPrefix = $_SESSION['db'] ?? '';
        $id = filter_var($_POST['Consecutivo'] ?? $_POST['Id'] ?? null, FILTER_VALIDATE_INT);
        $week = filter_var($_POST['semana'] ?? null, FILTER_VALIDATE_INT);
        $category = trim((string) ($_POST['Categoria_CNP'] ?? $_POST['Categoria_CNC'] ?? ''));
        $cause = trim((string) ($_POST['CNP'] ?? $_POST['CNC'] ?? ''));
        $observation = trim((string) ($_POST['Observaciones_CNP'] ?? $_POST['Observaciones_CNC'] ?? ''));
        $responsible = trim((string) ($_POST['Responsable_AIA'] ?? ''));

        if (!$dbPrefix || !$id || !$week || $category === '' || $cause === '') {
            $this->jsonError("Datos insuficientes para guardar CNP.");
            return;
        }
        if (!$this->requireWeekEditPolicy($dbPrefix, (int) $week)) {
            return;
        }

        try {
            $projectId = $this->projectId($dbPrefix);
            $table = TableResolver::resolveByPrefix($dbPrefix, 'programacion_semanal');
            $exists = $this->db->queryWithProject(
                "SELECT 1 FROM {$table} WHERE project_id = ? AND row_id = ? AND Semana = ? AND Activa = 0",
                [$projectId, $id, $week],
                $projectId
            )->fetchColumn();
            if (!$exists) {
                $this->jsonError('La causa ya no está disponible para edición.');
                return;
            }

            $query = "UPDATE {$table} SET Responsable_AIA = ?, Categoria_CNP = ?, CNP = ?, Observaciones_CNP = ? WHERE project_id = ? AND row_id = ? AND Semana = ? AND Activa = 0";
            $res = $this->db->queryWithProject($query, [$responsible, $category, $cause, $observation, $projectId, $id, $week], $projectId);
            $this->jsonResponse($res ? 'BIEN' : 'ERROR');
        } catch (Throwable $t) {
            $this->jsonError("Error CNP Save: " . $t->getMessage());
        }
    }

    public function reprogramar(): void
    {
        require_once PROJECT_ROOT . '/src/Legacy/rbac_guard.php';
        rbac_guard_require_permission('lps.cnp.editar');
        $dbPrefix = $_SESSION['db'] ?? '';
        $id = filter_var($_POST['Id'] ?? null, FILTER_VALIDATE_INT);
        $week = filter_var($_POST['semana'] ?? null, FILTER_VALIDATE_INT);

        if (!$dbPrefix || !$id || !$week) {
            $this->jsonError("Datos insuficientes para reprogramar.");
            return;
        }
        if (!$this->requireWeekEditPolicy($dbPrefix, (int) $week)) {
            return;
        }

        try {
            $projectId = $this->projectId($dbPrefix);
            $this->db->beginTransaction();
            $weekTable = TableResolver::resolveByPrefix($dbPrefix, 'semanas_activas');
            $confirmed = $this->db->queryWithProject(
                "SELECT Semanal_Confirmada FROM {$weekTable} WHERE project_id = ? AND Semana = ? FOR UPDATE",
                [$projectId, $week],
                $projectId
            )->fetchColumn();
            if ((int) $confirmed === 1) {
                $this->db->rollBack();
                $this->jsonError('No se puede reprogramar una semana confirmada.', 409);
                return;
            }
            $table = TableResolver::resolveByPrefix($dbPrefix, 'programacion_semanal');
            $query = "UPDATE {$table} SET Activa = 1, Categoria_CNP = NULL, CNP = NULL, Observaciones_CNP = NULL, Reprogramada_Por_Usuario = 1 WHERE project_id = ? AND row_id = ? AND Semana = ? AND Activa = 0";
            $res = $this->db->queryWithProject($query, [$projectId, $id, $week], $projectId);
            if (!$res || $res->rowCount() !== 1) {
                $this->db->rollBack();
                $this->jsonError('La actividad ya no está disponible para reprogramar.');
                return;
            }
            $this->db->commit();
            $this->jsonResponse('BIEN');
        } catch (Throwable $t) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $this->jsonError("Error CNP Reprogramar: " . $t->getMessage());
        }
    }

    private function requireWeekEditPolicy(string $dbPrefix, int $week): bool
    {
        if ((new LpsWeekEditPolicy($this->db))->allows($dbPrefix, $week)) {
            return true;
        }
        $this->jsonError('La semana histórica no permite esta operación para su rol.', 403);
        return false;
    }

    private function jsonResponse(string $res): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(["respuesta" => $res], JSON_UNESCAPED_UNICODE);
    }

    private function jsonError(string $msg, int $status = 422): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(["respuesta" => "ERROR", "mensaje" => $msg], JSON_UNESCAPED_UNICODE);
    }

    private function projectId(string $dbPrefix): int
    {
        $projectId = TableResolver::getProjectIdByPrefix($dbPrefix);
        if (!$projectId) {
            throw new \RuntimeException('Proyecto no encontrado.');
        }

        return $projectId;
    }
}
