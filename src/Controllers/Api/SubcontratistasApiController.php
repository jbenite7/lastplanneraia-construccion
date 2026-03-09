<?php

namespace App\Controllers\Api;

use PDO;
use Throwable;

class SubcontratistasApiController
{
    private $db;

    public function __construct()
    {
        $this->db = \Database::getInstance();
    }

    public function list(): void
    {
        $dbPrefix = $_GET['db'] ?? $_POST['db'] ?? '';

        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $dbPrefix)) {
            $this->json(["status" => "error", "message" => "Base de datos inválida ($dbPrefix)."]);
            return;
        }

        try {
            $tables = [
                "{$dbPrefix}_cic" => "subcontratista",
                "{$dbPrefix}_programacion_semanal" => "Sub_Contratista",
                "{$dbPrefix}_pdc" => "Sub_Contratista",
            ];

            $dependencyChecks = [];
            foreach ($tables as $tbl => $col) {
                if ($this->db->query("SHOW TABLES LIKE '$tbl'")->fetch()) {
                    if ($this->db->query("SHOW COLUMNS FROM $tbl LIKE '$col'")->fetch()) {
                        $dependencyChecks[] = "(SELECT COUNT(*) FROM $tbl WHERE $tbl.$col = s.subcontratista) > 0";
                    }
                }
            }

            $depSql = !empty($dependencyChecks) ? ", ( " . implode(" OR ", $dependencyChecks) . " ) as has_dependencies" : ", 0 as has_dependencies";

            $query = "SELECT s.Id, s.subcontratista, s.correo_contacto, s.NIT, s.alcance, s.tipo_proveedor, s.activo $depSql FROM {$dbPrefix}_subcontratistas s ORDER BY s.subcontratista ASC";
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
        $dbPrefix = $_GET['db'] ?? $_POST['db'] ?? '';
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
                case 'registrar':
                    $this->crear($dbPrefix);
                    break;
                case 'eliminar':
                    $this->eliminar($dbPrefix);
                    break;
                default:
                    $this->json(["status" => "error", "message" => "Opción no válida ($opcion)."]);
                    break;
            }
        } catch (Throwable $t) {
            $this->json(["status" => "error", "message" => "Error del servidor: " . $t->getMessage()]);
        }
    }

    private function guardar_cambios(string $dbPrefix): void
    {
        $changes = $_POST['cambios'] ?? [];
        if (empty($changes) && isset($_POST['id']) && isset($_POST['column'])) {
            $changes = [['id' => $_POST['id'], 'prop' => $_POST['column'], 'value' => $_POST['value']]];
        }

        if (empty($changes)) {
            $this->json(["status" => "success", "message" => "No hubo cambios."]);
            return;
        }

        $errores = [];
        $actualizados = 0;
        $allowed = ['subcontratista', 'correo_contacto', 'NIT', 'alcance', 'tipo_proveedor', 'activo'];

        foreach ($changes as $change) {
            $id = $change['id'];
            $columna = $change['prop'];
            $valor = $change['value'];

            if ($columna == 'NIT') {
                $stmtCheck = $this->db->query("SELECT COUNT(*) FROM {$dbPrefix}_subcontratistas WHERE NIT = ? AND Id != ?", [$valor, $id]);
                if ($stmtCheck->fetchColumn() > 0) {
                    $errores[] = "ID $id: El NIT '$valor' ya existe.";
                    continue;
                }
            }

            if ($columna == 'correo_contacto' && !empty($valor)) {
                if (!filter_var($valor, FILTER_VALIDATE_EMAIL)) {
                    $errores[] = "ID $id: Email inválido '$valor'";
                    continue;
                }
            }

            if ($columna == 'activo') {
                $valor = ($valor === 'true' || $valor === '1' || $valor === true || $valor === 1) ? 1 : 0;
            } else {
                $valor = trim($valor);
            }

            if ($columna == 'subcontratista') {
                $oldName = $this->db->query("SELECT subcontratista FROM {$dbPrefix}_subcontratistas WHERE Id = ?", [$id])->fetchColumn();
                if ($oldName && $oldName !== $valor) {
                    $stmtCheckName = $this->db->query("SELECT COUNT(*) FROM {$dbPrefix}_subcontratistas WHERE subcontratista = ? AND Id != ?", [$valor, $id]);
                    if ($stmtCheckName->fetchColumn() > 0) {
                        $errores[] = "ID $id: El Nombre '$valor' ya existe.";
                        continue;
                    }
                    $this->actualizar_dependencias_nombre($dbPrefix, $oldName, $valor);
                }
            }

            if (!in_array($columna, $allowed)) continue;

            if ($this->db->query("UPDATE {$dbPrefix}_subcontratistas SET $columna = ? WHERE Id = ?", [$valor, $id])) {
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
        $tables = ["{$dbPrefix}_cic" => "subcontratista", "{$dbPrefix}_programacion_semanal" => "Sub_Contratista", "{$dbPrefix}_pdc" => "Sub_Contratista"];
        foreach ($tables as $tbl => $col) {
            if ($this->db->query("SHOW TABLES LIKE '$tbl'")->fetch()) {
                if ($this->db->query("SHOW COLUMNS FROM $tbl LIKE '$col'")->fetch()) {
                    $this->db->query("UPDATE $tbl SET $col = ? WHERE $col = ?", [$newName, $oldName]);
                }
            }
        }
    }

    private function crear(string $dbPrefix): void
    {
        $nombre = trim($_POST['Subcontratista'] ?? $_POST['subcontratista'] ?? '');
        $correo = trim($_POST['Correo'] ?? $_POST['correo_contacto'] ?? '');
        $nit = trim($_POST['NIT'] ?? '');
        $alcance = trim($_POST['alcance'] ?? '');
        $tipo = trim($_POST['tipo_proveedor'] ?? '');

        if (empty($nombre)) {
            $this->json(["status" => "error", "message" => "El nombre es obligatorio."]);
            return;
        }

        if (!empty($nit)) {
            if ($this->db->query("SELECT COUNT(*) FROM {$dbPrefix}_subcontratistas WHERE NIT = ?", [$nit])->fetchColumn() > 0) {
                $this->json(["status" => "error", "message" => "El NIT '$nit' ya está registrado."]);
                return;
            }
        }

        if ($this->db->query("SELECT COUNT(*) FROM {$dbPrefix}_subcontratistas WHERE subcontratista = ?", [$nombre])->fetchColumn() > 0) {
            $this->json(["status" => "error", "message" => "El Subcontratista '$nombre' ya existe."]);
            return;
        }

        $res = $this->db->query("INSERT INTO {$dbPrefix}_subcontratistas (subcontratista, correo_contacto, NIT, alcance, tipo_proveedor, activo) VALUES (?, ?, ?, ?, ?, 1)", [
            $nombre, empty($correo) ? null : $correo, empty($nit) ? null : $nit, empty($alcance) ? null : $alcance, empty($tipo) ? null : $tipo
        ]);

        if ($res) {
            $this->json(["status" => "success", "id" => $this->db->lastInsertId(), "respuesta" => "BIEN", "message" => "Subcontratista creado."]);
        } else {
            $this->json(["status" => "error", "message" => "Error al crear subcontratista."]);
        }
    }

    private function eliminar(string $dbPrefix): void
    {
        $id = $_POST['Id'] ?? $_POST['id'] ?? '';
        $nombre = $this->db->query("SELECT subcontratista FROM {$dbPrefix}_subcontratistas WHERE Id = ?", [$id])->fetchColumn();

        if (!$nombre) {
            $this->json(['status' => 'error', 'message' => 'Subcontratista no encontrado']);
            return;
        }

        if ($this->tiene_dependencias($dbPrefix, $nombre)) {
            $this->json(["status" => "error", "message" => "No se puede eliminar: Tiene registros asociados."]);
            return;
        }

        if ($this->db->query("DELETE FROM {$dbPrefix}_subcontratistas WHERE Id = ?", [$id])) {
            $this->json(["status" => "success", "respuesta" => "BIEN", "message" => "Subcontratista eliminado."]);
        } else {
            $this->json(["status" => "error", "message" => "Error al eliminar."]);
        }
    }

    private function tiene_dependencias(string $dbPrefix, string $nombre): bool
    {
        $tables = ["{$dbPrefix}_cic" => "subcontratista", "{$dbPrefix}_programacion_semanal" => "Sub_Contratista", "{$dbPrefix}_pdc" => "Sub_Contratista"];
        foreach ($tables as $tbl => $col) {
            if ($this->db->query("SHOW TABLES LIKE '$tbl'")->fetch()) {
                if ($this->db->query("SHOW COLUMNS FROM $tbl LIKE '$col'")->fetch()) {
                    if ($this->db->query("SELECT COUNT(*) FROM $tbl WHERE $col = ?", [$nombre])->fetchColumn() > 0) return true;
                }
            }
        }
        return false;
    }

    private function json(array $data): void
    {
        header('Content-Type: application/json');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }
}
