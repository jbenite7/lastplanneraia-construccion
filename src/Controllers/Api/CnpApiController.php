<?php

namespace App\Controllers\Api;

use PDO;
use Throwable;

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
            $query = "SELECT * FROM {$dbPrefix}_programacion_semanal WHERE Semana = ? AND Activa = 0";
            $data = $this->db->query($query, [$semana])->fetchAll(PDO::FETCH_ASSOC);

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
        $id = $_POST["Consecutivo"] ?? null;

        if (!$dbPrefix || !$id) {
            $this->jsonError("Datos insuficientes para guardar CNP.");
            return;
        }

        try {
            $query = "UPDATE {$dbPrefix}_programacion_semanal SET Categoria_CNP = ?, CNP = ?, Observaciones_CNP = ? WHERE Consecutivo = ?";
            $res = $this->db->query($query, [$_POST["Categoria_CNP"], $_POST["CNP"], $_POST["Observaciones_CNP"] ?? '', $id]);
            $this->jsonResponse($res ? "BIEN" : "ERROR");
        } catch (Throwable $t) {
            $this->jsonError("Error CNP Save: " . $t->getMessage());
        }
    }

    public function reprogramar(): void
    {
        require_once PROJECT_ROOT . '/src/Legacy/rbac_guard.php';
        rbac_guard_require_permission('lps.cnp.editar');
        $dbPrefix = $_SESSION['db'] ?? '';
        $id = $_POST["Id"] ?? null;

        if (!$dbPrefix || !$id) {
            $this->jsonError("Datos insuficientes para reprogramar.");
            return;
        }

        try {
            $query = "UPDATE {$dbPrefix}_programacion_semanal SET Activa='1', Categoria_CNP=NULL, CNP=NULL, Observaciones_CNP=NULL, Reprogramada_Por_Usuario=1 WHERE Consecutivo=?";
            $res = $this->db->query($query, [$id]);
            $this->jsonResponse($res ? "BIEN" : "ERROR");
        } catch (Throwable $t) {
            $this->jsonError("Error CNP Reprogramar: " . $t->getMessage());
        }
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
