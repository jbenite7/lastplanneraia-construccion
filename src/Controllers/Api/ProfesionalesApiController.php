<?php

namespace App\Controllers\Api;

use App\Core\Lps\LpsService;
use App\Security\RbacManager;
use PDO;
use Throwable;

class ProfesionalesApiController
{
    private $db;
    private LpsService $lpsService;
    private RbacManager $rbac;

    public function __construct()
    {
        $this->db = \Database::getInstance();
        $this->lpsService = new LpsService();
        $this->rbac = new RbacManager();
    }

    public function list(): void
    {
        $dbPrefix = $_GET['db'] ?? '';

        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $dbPrefix)) {
            $this->json(["status" => "error", "message" => "Base de datos inválida ($dbPrefix)."]);
            return;
        }

        try {
            $tables = [
                "{$dbPrefix}_cip" => "profesional",
                "{$dbPrefix}_programacion_semanal" => "Responsable_AIA",
                "{$dbPrefix}_programa_consolidado" => "Responsable_AIA",
            ];

            $dependencyChecks = [];
            foreach ($tables as $tbl => $col) {
                $check = $this->db->query("SHOW TABLES LIKE '$tbl'")->fetch();
                if ($check) {
                    $dependencyChecks[] = "(SELECT COUNT(*) FROM $tbl WHERE $tbl.$col = p.nombre) > 0";
                }
            }

            $depSql = !empty($dependencyChecks) ? ", ( " . implode(" OR ", $dependencyChecks) . " ) as has_dependencies" : ", 0 as has_dependencies";

            $query = "SELECT p.id, p.nombre, p.email, p.cargo, p.activo $depSql FROM {$dbPrefix}_profesionales p WHERE p.nombre IS NOT NULL AND TRIM(p.nombre) != '' ORDER BY p.id ASC";
            $data = $this->db->query($query)->fetchAll(PDO::FETCH_ASSOC);

            foreach ($data as &$row) {
                $row['activo'] = (bool)$row['activo'];
                $row['has_dependencies'] = (bool)$row['has_dependencies'];
            }

            $this->json(["status" => "success", "data" => $data]);
        } catch (Throwable $t) {
            $this->json(["status" => "error", "message" => "Error del servidor: " . $t->getMessage()]);
        }
    }

    public function save(): void
    {
        $dbPrefix = $_GET['db'] ?? '';
        $opcion = $_POST["opcion"] ?? '';

        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $dbPrefix)) {
            $this->json(["status" => "error", "message" => "Base de datos inválida ($dbPrefix)."]);
            return;
        }

        try {
            switch ($opcion) {
                case 'guardar_cambios':
                    $this->guardar_cambios($dbPrefix);
                    break;
                case 'crear':
                    $this->crear($dbPrefix);
                    break;
                case 'eliminar':
                    $this->eliminar($dbPrefix);
                    break;
                default:
                    $this->json(["status" => "error", "message" => "Opción no válida."]);
                    break;
            }
        } catch (Throwable $t) {
            $this->json(["status" => "error", "message" => "Error del servidor: " . $t->getMessage()]);
        }
    }

    private function guardar_cambios(string $dbPrefix): void
    {
        $changes = $_POST['cambios'] ?? [];
        if (empty($changes)) {
            $this->json(["status" => "success", "message" => "No hubo cambios."]);
            return;
        }

        $errores = [];
        $actualizados = 0;
        $allowed = ['nombre', 'email', 'cargo', 'activo'];

        foreach ($changes as $change) {
            $id = $change['id'];
            $columna = $change['prop'];
            $valor = $change['value'];

            if ($columna == 'email' && !empty($valor)) {
                if (!filter_var($valor, FILTER_VALIDATE_EMAIL)) {
                    $errores[] = "ID $id: Email inválido '$valor'";
                    continue;
                }
                $stmtCheck = $this->db->query("SELECT COUNT(*) FROM {$dbPrefix}_profesionales WHERE email = ? AND id != ?", [$valor, $id]);
                if ($stmtCheck->fetchColumn() > 0) {
                    $errores[] = "ID $id: El correo '$valor' ya está en uso por otro usuario.";
                    continue;
                }
            }

            if ($columna == 'activo') {
                $valor = ($valor === 'true' || $valor === '1' || $valor === true) ? 1 : 0;
            } else {
                $valor = trim($valor);
            }

            if ($columna == 'nombre') {
                $oldName = $this->db->query("SELECT nombre FROM {$dbPrefix}_profesionales WHERE id = ?", [$id])->fetchColumn();
                if ($oldName && $oldName !== $valor) {
                    $this->actualizar_dependencias_nombre($dbPrefix, $oldName, $valor);
                }
            }

            if (!in_array($columna, $allowed)) {
                $errores[] = "Columna no permitida: $columna";
                continue;
            }

            if ($this->db->query("UPDATE {$dbPrefix}_profesionales SET $columna = ? WHERE id = ?", [$valor, $id])) {
                $actualizados++;
            } else {
                $errores[] = "Error actualizando ID $id columna $columna";
            }
        }

        if (count($errores) > 0) {
            $this->json(["status" => "warning", "message" => "Se guardaron $actualizados cambios con errores.", "errors" => $errores]);
        } else {
            $this->json(["status" => "success", "message" => "Cambios guardados correctamente."]);
        }
    }

    private function actualizar_dependencias_nombre(string $dbPrefix, string $oldName, string $newName): void
    {
        $tablesToUpdate = [
            "{$dbPrefix}_programacion_semanal" => "Responsable_AIA",
            "{$dbPrefix}_programa_consolidado" => "Responsable_AIA",
            "{$dbPrefix}_cip" => "profesional",
            "{$dbPrefix}_indicadores_generales" => "subcontratista_profesional",
        ];

        foreach ($tablesToUpdate as $tbl => $col) {
            if ($this->db->query("SHOW TABLES LIKE '$tbl'")->fetch()) {
                $this->db->query("UPDATE $tbl SET $col = ? WHERE $col = ?", [$newName, $oldName]);
            }
        }
    }

    private function crear(string $dbPrefix): void
    {
        $nombre = trim($_POST['nombre'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $cargo = trim($_POST['cargo'] ?? '');

        if (empty($nombre)) {
            $this->json(["status" => "error", "message" => "El nombre es obligatorio."]);
            return;
        }

        if (!empty($email)) {
            $stmt = $this->db->query("SELECT COUNT(*) FROM {$dbPrefix}_profesionales WHERE email = ?", [$email]);
            if ($stmt->fetchColumn() > 0) {
                $this->json(["status" => "error", "message" => "El correo '$email' ya está registrado."]);
                return;
            }
        }

        $res = $this->db->query("INSERT INTO {$dbPrefix}_profesionales (nombre, email, cargo, activo) VALUES (?, ?, ?, 1)", [
            $nombre, empty($email) ? null : $email, empty($cargo) ? null : $cargo
        ]);

        if ($res) {
            $this->json(["status" => "success", "id" => $this->db->lastInsertId(), "message" => "Profesional creado."]);
        } else {
            $this->json(["status" => "error", "message" => "Error al crear profesional."]);
        }
    }

    private function eliminar(string $dbPrefix): void
    {
        $id = $_POST['id'] ?? 0;
        $nombre = $this->db->query("SELECT nombre FROM {$dbPrefix}_profesionales WHERE id = ?", [$id])->fetchColumn();

        if (!$nombre) {
            $this->json(["status" => "error", "message" => "Profesional no encontrado."]);
            return;
        }

        $tables = [
            "{$dbPrefix}_cip" => "profesional",
            "{$dbPrefix}_programacion_semanal" => "Responsable_AIA",
            "{$dbPrefix}_programa_consolidado" => "Responsable_AIA",
        ];

        foreach ($tables as $tbl => $col) {
            if ($this->db->query("SHOW TABLES LIKE '$tbl'")->fetch()) {
                if ($this->db->query("SELECT COUNT(*) FROM $tbl WHERE $col = ?", [$nombre])->fetchColumn() > 0) {
                    $this->json(["status" => "error", "message" => "No se puede eliminar: Tiene registros asociados."]);
                    return;
                }
            }
        }

        if ($this->db->query("DELETE FROM {$dbPrefix}_profesionales WHERE id = ?", [$id])) {
            $this->db->logActivity('Profesionales', 'ELIMINAR', "Eliminó profesional: $nombre", $dbPrefix);
            $this->json(["status" => "success", "message" => "Profesional eliminado."]);
        } else {
            $this->json(["status" => "error", "message" => "Error al eliminar."]);
        }
    }

    private function json(array $data): void
    {
        header('Content-Type: application/json');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
