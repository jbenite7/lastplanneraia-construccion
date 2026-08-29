<?php

namespace App\Controllers\Api;

use PDO;
use Throwable;

use TableResolver;
class SubcontratistasApiController
{
    private $db;

    public function __construct()
    {
        $this->db = \Database::getInstance();
    }

    public function list(): void
    {
        require_once PROJECT_ROOT . '/src/Legacy/rbac_guard.php';
        rbac_guard_require_permission('lps.subcontratistas.ver');
        $dbPrefix = $_GET['db'] ?? $_POST['db'] ?? '';

        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $dbPrefix)) {
            $this->json(["status" => "error", "message" => "Base de datos inválida ($dbPrefix)."]);
            return;
        }

        try {
            $projectId = $this->projectId($dbPrefix);
            // La tabla `pdc` salio de aqui el 2026-08-04 con el retiro del PDC v1: dejo de estar en
            // la lista blanca de TableResolver y la tabla se elimino, asi que resolverla lanzaba
            // InvalidArgumentException y este endpoint devolvia
            // «Error del servidor: Invalid table type: pdc» — la pantalla no cargaba nada.
            $tables = [
                "" . TableResolver::resolveByPrefix($dbPrefix, 'cic') . "" => "subcontratista",
                "" . TableResolver::resolveByPrefix($dbPrefix, 'programacion_semanal') . "" => "Sub_Contratista",
            ];

            $dependencyChecks = [];
            foreach ($tables as $tbl => $col) {
                if ($this->tableHasColumn($tbl, $col)) {
                        $dependencyChecks[] = "(SELECT COUNT(*) FROM $tbl WHERE $tbl.project_id = ? AND $tbl.$col = s.subcontratista) > 0";
                }
            }

            $depSql = !empty($dependencyChecks) ? ", ( " . implode(" OR ", $dependencyChecks) . " ) as has_dependencies" : ", 0 as has_dependencies";

            $query = "SELECT s.Id, s.subcontratista, s.correo_contacto, s.NIT, s.alcance, s.tipo_proveedor, s.activo $depSql FROM " . TableResolver::resolveByPrefix($dbPrefix, 'subcontratistas') . " s WHERE s.project_id = ? ORDER BY s.subcontratista ASC";
            $params = array_fill(0, count($dependencyChecks), $projectId);
            $params[] = $projectId;
            $data = $this->db->query($query, $params)->fetchAll(PDO::FETCH_ASSOC);

            foreach ($data as &$row) {
                $row['activo'] = (bool) $row['activo'];
                $row['has_dependencies'] = (bool) $row['has_dependencies'];
            }

            $this->json(["status" => "success", "data" => $data]);
        } catch (Throwable $t) {
            $this->json(["status" => "error", "message" => "Error del servidor: " . $t->getMessage()]);
        }
    }

    public function save(): void
    {
        require_once PROJECT_ROOT . '/src/Legacy/rbac_guard.php';
        rbac_guard_require_permission('lps.subcontratistas.editar');
        legacy_require_csrf('subcontratistas');
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
        $projectId = $this->projectId($dbPrefix);
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

        $changesById = [];
        foreach ($changes as $change) {
            $id = (int) ($change['id'] ?? 0);
            $columna = $change['prop'] ?? '';

            if ($id <= 0) {
                $errores[] = 'Registro inválido para actualizar.';
                continue;
            }

            if (!in_array($columna, $allowed, true)) {
                $errores[] = "Columna no permitida: $columna";
                continue;
            }

            $changesById[$id][$columna] = $change['value'] ?? null;
        }

        foreach ($changesById as $id => $rowChanges) {
            $actual = $this->obtenerSubcontratista($dbPrefix, $id);
            if (!$actual) {
                $errores[] = "Subcontratista ID $id no encontrado.";
                continue;
            }

            $actualizado = $this->aplicarCambiosSubcontratista($actual, $rowChanges);
            $erroresFila = $this->validarSubcontratista($dbPrefix, $actualizado, $id);
            if (!empty($erroresFila)) {
                $errores = array_merge($errores, $erroresFila);
                continue;
            }

            $resultado = $this->db->queryWithProject(
                "UPDATE " . TableResolver::resolveByPrefix($dbPrefix, 'subcontratistas') . " SET subcontratista = ?, correo_contacto = ?, NIT = ?, alcance = ?, tipo_proveedor = ?, activo = ? WHERE project_id = ? AND Id = ?",
                [
                    $actualizado['subcontratista'],
                    $actualizado['correo_contacto'],
                    $actualizado['NIT'],
                    $actualizado['alcance'],
                    $actualizado['tipo_proveedor'],
                    $actualizado['activo'],
                    $projectId,
                    $id,
                ],
                $projectId,
            );

            if ($resultado) {
                if ($this->normalizarTexto($actual['subcontratista']) !== $this->normalizarTexto($actualizado['subcontratista'])) {
                    $this->actualizar_dependencias_nombre($dbPrefix, $actual['subcontratista'], $actualizado['subcontratista']);
                }
                $actualizados++;
            } else {
                $errores[] = "Error actualizando el subcontratista '{$actualizado['subcontratista']}'.";
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
        $projectId = $this->projectId($dbPrefix);
        $tables = ["" . TableResolver::resolveByPrefix($dbPrefix, 'cic') . "" => "subcontratista", "" . TableResolver::resolveByPrefix($dbPrefix, 'programacion_semanal') . "" => "Sub_Contratista"];
        foreach ($tables as $tbl => $col) {
            if ($this->tableHasColumn($tbl, $col)) {
                    $this->db->query("UPDATE $tbl SET $col = ? WHERE project_id = ? AND $col = ?", [$newName, $projectId, $oldName]);
            }
        }
    }

    private function crear(string $dbPrefix): void
    {
        $projectId = $this->projectId($dbPrefix);
        $data = $this->sanitizarSubcontratista([
            'subcontratista' => $_POST['Subcontratista'] ?? $_POST['subcontratista'] ?? '',
            'correo_contacto' => $_POST['Correo'] ?? $_POST['correo_contacto'] ?? '',
            'NIT' => $_POST['NIT'] ?? '',
            'alcance' => $_POST['alcance'] ?? '',
            'tipo_proveedor' => $_POST['tipo_proveedor'] ?? '',
            'activo' => 1,
        ]);

        $errores = $this->validarSubcontratista($dbPrefix, $data);
        if (!empty($errores)) {
            $this->json(["status" => "error", "message" => implode("\n", $errores), "errors" => $errores]);
            return;
        }

        $res = $this->db->queryWithProject("INSERT INTO " . TableResolver::resolveByPrefix($dbPrefix, 'subcontratistas') . " (project_id, subcontratista, correo_contacto, NIT, alcance, tipo_proveedor, activo) VALUES (?, ?, ?, ?, ?, ?, 1)", [
            $projectId,
            $data['subcontratista'],
            $data['correo_contacto'],
            $data['NIT'],
            $data['alcance'],
            $data['tipo_proveedor'],
        ], $projectId);

        if ($res) {
            $id = $this->db->queryWithProject(
                "SELECT Id FROM " . TableResolver::resolveByPrefix($dbPrefix, 'subcontratistas') . " WHERE project_id = ? AND correo_contacto = ? ORDER BY Id DESC LIMIT 1",
                [$projectId, $data['correo_contacto']],
                $projectId,
            )->fetchColumn();
            $this->json(["status" => "success", "id" => $id ?: $this->db->lastInsertId(), "respuesta" => "BIEN", "message" => "Subcontratista creado."]);
        } else {
            $this->json(["status" => "error", "message" => "Error al crear subcontratista."]);
        }
    }

    private function eliminar(string $dbPrefix): void
    {
        $projectId = $this->projectId($dbPrefix);
        $id = $_POST['Id'] ?? $_POST['id'] ?? '';
        $nombre = $this->db->queryWithProject(
            "SELECT subcontratista FROM " . TableResolver::resolveByPrefix($dbPrefix, 'subcontratistas') . " WHERE project_id = ? AND Id = ?",
            [$projectId, $id],
            $projectId,
        )->fetchColumn();

        if (!$nombre) {
            $this->json(['status' => 'error', 'message' => 'Subcontratista no encontrado']);
            return;
        }

        if ($this->tiene_dependencias($dbPrefix, $nombre)) {
            $this->json(["status" => "error", "message" => "No se puede eliminar: Tiene registros asociados."]);
            return;
        }

        if ($this->db->queryWithProject("DELETE FROM " . TableResolver::resolveByPrefix($dbPrefix, 'subcontratistas') . " WHERE project_id = ? AND Id = ?", [$projectId, $id], $projectId)) {
            $this->json(["status" => "success", "respuesta" => "BIEN", "message" => "Subcontratista eliminado."]);
        } else {
            $this->json(["status" => "error", "message" => "Error al eliminar."]);
        }
    }

    private function tiene_dependencias(string $dbPrefix, string $nombre): bool
    {
        $projectId = $this->projectId($dbPrefix);
        $tables = ["" . TableResolver::resolveByPrefix($dbPrefix, 'cic') . "" => "subcontratista", "" . TableResolver::resolveByPrefix($dbPrefix, 'programacion_semanal') . "" => "Sub_Contratista"];
        foreach ($tables as $tbl => $col) {
            if ($this->tableHasColumn($tbl, $col)) {
                    if ($this->db->query("SELECT COUNT(*) FROM $tbl WHERE project_id = ? AND $col = ?", [$projectId, $nombre])->fetchColumn() > 0) {
                        return true;
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

    private function obtenerSubcontratista(string $dbPrefix, int $id): ?array
    {
        $projectId = $this->projectId($dbPrefix);
        $stmt = $this->db->queryWithProject(
            "SELECT Id, subcontratista, correo_contacto, NIT, alcance, tipo_proveedor, activo FROM " . TableResolver::resolveByPrefix($dbPrefix, 'subcontratistas') . " WHERE project_id = ? AND Id = ?",
            [$projectId, $id],
            $projectId,
        );
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    private function aplicarCambiosSubcontratista(array $actual, array $changes): array
    {
        $mezclado = [
            'subcontratista' => array_key_exists('subcontratista', $changes) ? $changes['subcontratista'] : $actual['subcontratista'],
            'correo_contacto' => array_key_exists('correo_contacto', $changes) ? $changes['correo_contacto'] : $actual['correo_contacto'],
            'NIT' => array_key_exists('NIT', $changes) ? $changes['NIT'] : $actual['NIT'],
            'alcance' => array_key_exists('alcance', $changes) ? $changes['alcance'] : $actual['alcance'],
            'tipo_proveedor' => array_key_exists('tipo_proveedor', $changes) ? $changes['tipo_proveedor'] : $actual['tipo_proveedor'],
            'activo' => array_key_exists('activo', $changes) ? $changes['activo'] : $actual['activo'],
        ];

        return $this->sanitizarSubcontratista($mezclado);
    }

    private function sanitizarSubcontratista(array $data): array
    {
        return [
            'subcontratista' => $this->limpiarTexto($data['subcontratista'] ?? ''),
            'correo_contacto' => $this->normalizarEmail($data['correo_contacto'] ?? ''),
            'NIT' => $this->limpiarTexto($data['NIT'] ?? ''),
            'alcance' => $this->limpiarTexto($data['alcance'] ?? ''),
            'tipo_proveedor' => $this->limpiarTexto($data['tipo_proveedor'] ?? ''),
            'activo' => $this->normalizarBooleano($data['activo'] ?? 1),
        ];
    }

    private function getAllowedProviderTypes(): array
    {
        $area = $_SESSION['area'] ?? $_GET['area'] ?? 'Construccion';

        if ($area === 'Pre-Construccion' || $area === 'PC') {
            return [
                'Socio',
                'Ventas',
                'Gerencia',
                'Diseñador',
                'Consultor',
                'Entidad',
                'Interventoría',
                'Cliente',
                'Inversionista',
                'Promotor',
            ];
        }

        return [
            'Mano de Obra',
            'Suministro e Instalación',
            'Suministro de Materiales, Herramientas o Equipos',
        ];
    }

    private function validarSubcontratista(string $dbPrefix, array $data, ?int $excludeId = null): array
    {
        $errores = [];

        if ($data['subcontratista'] === '') {
            $errores[] = 'El nombre del subcontratista es obligatorio.';
        }

        if ($data['correo_contacto'] === '') {
            $errores[] = 'El correo de contacto es obligatorio.';
        } elseif (!filter_var($data['correo_contacto'], FILTER_VALIDATE_EMAIL)) {
            $errores[] = 'El correo de contacto no tiene un formato válido.';
        }

        if ($data['NIT'] === '') {
            $errores[] = 'El NIT es obligatorio.';
        }

        if ($data['alcance'] === '') {
            $errores[] = 'El alcance es obligatorio.';
        }

        if ($data['tipo_proveedor'] === '') {
            $errores[] = 'El tipo de proveedor es obligatorio.';
        } elseif (!in_array($data['tipo_proveedor'], $this->getAllowedProviderTypes(), true)) {
            $errores[] = 'El tipo de proveedor seleccionado no es válido.';
        }

        foreach ($this->buscarDuplicadosSubcontratista($dbPrefix, $data, $excludeId) as $error) {
            $errores[] = $error;
        }

        return array_values(array_unique($errores));
    }

    private function buscarDuplicadosSubcontratista(string $dbPrefix, array $data, ?int $excludeId = null): array
    {
        $errores = [];
        $projectId = $this->projectId($dbPrefix);
        $params = [$projectId];
        $sql = "SELECT Id, subcontratista, correo_contacto, NIT FROM " . TableResolver::resolveByPrefix($dbPrefix, 'subcontratistas') . " WHERE project_id = ?";

        if ($excludeId !== null) {
            $sql .= ' AND Id != ?';
            $params[] = $excludeId;
        }

        $rows = $this->db->queryWithProject($sql, $params, $projectId)->fetchAll(PDO::FETCH_ASSOC);
        $nombreNormalizado = $this->normalizarTexto($data['subcontratista']);
        $correoNormalizado = $this->normalizarEmail($data['correo_contacto']);
        $nitNormalizado = $this->normalizarNitComparacion($data['NIT']);

        foreach ($rows as $row) {
            if ($nombreNormalizado !== '' && $this->normalizarTexto($row['subcontratista'] ?? '') === $nombreNormalizado) {
                $errores[] = 'Ya existe un subcontratista con ese nombre.';
            }

            if ($correoNormalizado !== '' && $this->normalizarEmail($row['correo_contacto'] ?? '') === $correoNormalizado) {
                $errores[] = 'Ya existe un subcontratista con ese correo.';
            }

            if ($nitNormalizado !== '' && $this->normalizarNitComparacion($row['NIT'] ?? '') === $nitNormalizado) {
                $errores[] = 'Ya existe un subcontratista con ese NIT.';
            }
        }

        return array_values(array_unique($errores));
    }

    private function limpiarTexto($valor): string
    {
        return preg_replace('/\s+/u', ' ', trim((string) $valor)) ?? '';
    }

    private function normalizarTexto($valor): string
    {
        return function_exists('mb_strtolower')
            ? mb_strtolower($this->limpiarTexto($valor), 'UTF-8')
            : strtolower($this->limpiarTexto($valor));
    }

    private function normalizarEmail($valor): string
    {
        $email = trim((string) $valor);
        return function_exists('mb_strtolower') ? mb_strtolower($email, 'UTF-8') : strtolower($email);
    }

    private function normalizarNitComparacion($valor): string
    {
        $nit = trim((string) $valor);
        return preg_replace('/[^a-zA-Z0-9]/', '', $nit) ?? '';
    }

    private function normalizarBooleano($valor): int
    {
        return ($valor === 'true' || $valor === '1' || $valor === true || $valor === 1) ? 1 : 0;
    }

    private function projectId(string $dbPrefix): int
    {
        $projectId = TableResolver::getProjectIdByPrefix($dbPrefix);
        if (!$projectId) {
            throw new \RuntimeException('Proyecto no encontrado.');
        }

        return $projectId;
    }

    private function tableHasColumn(string $table, string $column): bool
    {
        if (preg_match('/^[A-Za-z0-9_]+$/', $table) !== 1 || preg_match('/^[A-Za-z0-9_]+$/', $column) !== 1) {
            return false;
        }

        return (int) $this->db->query(
            'SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [$table, $column],
        )->fetchColumn() > 0;
    }
}
