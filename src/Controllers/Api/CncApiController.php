<?php

namespace App\Controllers\Api;

use App\Security\LpsWeekEditPolicy;
use PDO;
use Throwable;

use TableResolver;
class CncApiController
{
    private $db;

    public function __construct()
    {
        $this->db = \Database::getInstance();
    }

    public function list(): void
    {
        require_once PROJECT_ROOT . '/src/Legacy/rbac_guard.php';
        rbac_guard_require_permission('lps.cnc.ver');
        $dbPrefix = $_SESSION['db'] ?? '';
        $semana = filter_var($_POST['semana'] ?? $_GET['semana'] ?? 0, FILTER_VALIDATE_INT);

        if (!$dbPrefix || $semana <= 0) {
            $this->jsonError("Sesión expirada o semana inválida.");
            return;
        }

        try {
            $projectId = $this->projectId($dbPrefix);
            $query = "SELECT * FROM " . TableResolver::resolveByPrefix($dbPrefix, 'programacion_semanal') . " WHERE project_id = ? AND Semana = ? AND Activa = 1 AND Categoria_CNC IS NOT NULL";
            $data = $this->db->queryWithProject($query, [$projectId, $semana], $projectId)->fetchAll(PDO::FETCH_ASSOC);

            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(["data" => $data], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $t) {
            $this->jsonError("Error CNC List: " . $t->getMessage());
        }
    }

    public function save(): void
    {
        require_once PROJECT_ROOT . '/src/Legacy/rbac_guard.php';
        rbac_guard_require_permission('lps.cnc.editar');
        legacy_require_csrf('cnc');
        $dbPrefix = $_SESSION['db'] ?? '';
        $id = filter_var($_POST['Consecutivo'] ?? $_POST['Id'] ?? null, FILTER_VALIDATE_INT);
        $week = filter_var($_POST['semana'] ?? null, FILTER_VALIDATE_INT);
        $category = trim((string) ($_POST['Categoria_CNC'] ?? ''));
        $cause = trim((string) ($_POST['CNC'] ?? ''));
        $observation = trim((string) ($_POST['Observaciones_CNC'] ?? ''));

        if (!$dbPrefix || !$id || !$week || $category === '' || $cause === '') {
            $this->jsonError("Datos insuficientes para guardar CNC.");
            return;
        }
        if (in_array($cause, ['Otra', 'Otra...', 'Otros', 'Otros...'], true) && $observation === '') {
            $this->jsonError('Debe explicar la causa cuando selecciona Otra.', 422);
            return;
        }
        if (!(new LpsWeekEditPolicy($this->db))->allows($dbPrefix, (int) $week)) {
            $this->jsonError('La semana histórica no permite esta operación para su rol.', 403);
            return;
        }

        try {
            $projectId = $this->projectId($dbPrefix);
            $table = TableResolver::resolveByPrefix($dbPrefix, 'programacion_semanal');
            $exists = $this->db->queryWithProject(
                "SELECT 1 FROM {$table} WHERE project_id = ? AND row_id = ? AND Semana = ? AND Activa = 1 AND Categoria_CNC IS NOT NULL",
                [$projectId, $id, $week],
                $projectId
            )->fetchColumn();
            if (!$exists) {
                $this->jsonError('La causa ya no está disponible para edición.');
                return;
            }

            $query = "UPDATE {$table} SET Categoria_CNC = ?, CNC = ?, Observaciones_CNC = ? WHERE project_id = ? AND row_id = ? AND Semana = ? AND Activa = 1";
            $res = $this->db->queryWithProject($query, [$category, $cause, $observation, $projectId, $id, $week], $projectId);
            $this->jsonResponse($res ? 'BIEN' : 'ERROR');
        } catch (Throwable $t) {
            $this->jsonError("Error CNC Save: " . $t->getMessage());
        }
    }

    public function reasons(): void
    {
        require_once PROJECT_ROOT . '/src/Legacy/rbac_guard.php';
        rbac_guard_require_permission('lps.cnc.ver');
        $categoria = $_POST["categoria"] ?? '';
        $query = "SELECT CNC FROM general_cnc WHERE Categoria_CNC = ? ORDER BY CNC ASC";
        $data = $this->db->query($query, [$categoria])->fetchAll(PDO::FETCH_ASSOC);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
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
