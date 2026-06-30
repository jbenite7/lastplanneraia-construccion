<?php

namespace App\Controllers\Api;

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
            $query = "SELECT * FROM " . TableResolver::resolveByPrefix($dbPrefix, 'programacion_semanal') . " WHERE Semana = ? AND Activa = 1 AND Categoria_CNC IS NOT NULL";
            $data = $this->db->queryWithProject($query, [$semana])->fetchAll(PDO::FETCH_ASSOC);

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
        $dbPrefix = $_SESSION['db'] ?? '';
        $id = $_POST["Consecutivo"] ?? null;

        if (!$dbPrefix || !$id) {
            $this->jsonError("Datos insuficientes para guardar CNC.");
            return;
        }

        try {
            $query = "UPDATE " . TableResolver::resolveByPrefix($dbPrefix, 'programacion_semanal') . " SET Categoria_CNC = ?, CNC = ?, Observaciones_CNC = ? WHERE Consecutivo = ?";
            $res = $this->db->queryWithProject($query, [$_POST["Categoria_CNC"], $_POST["CNC"], $_POST["Observaciones_CNC"] ?? '', $id]);
            $this->jsonResponse($res ? "BIEN" : "ERROR");
        } catch (Throwable $t) {
            $this->jsonError("Error CNC Save: " . $t->getMessage());
        }
    }

    public function reasons(): void
    {
        require_once PROJECT_ROOT . '/src/Legacy/rbac_guard.php';
        rbac_guard_require_permission('lps.cnc.ver');
        $categoria = $_POST["categoria"] ?? '';
        $area = $_POST["area"] ?? 'Construccion';
        $query = "SELECT CNC FROM general_cnc WHERE Categoria_CNC = ? AND Area = ?";
        $data = $this->db->queryWithProject($query, [$categoria, $area])->fetchAll(PDO::FETCH_ASSOC);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    private function jsonResponse(string $res): void
    {
        echo json_encode(["respuesta" => $res], JSON_UNESCAPED_UNICODE);
    }

    private function jsonError(string $msg): void
    {
        echo json_encode(["respuesta" => "ERROR", "mensaje" => $msg], JSON_UNESCAPED_UNICODE);
    }
}
