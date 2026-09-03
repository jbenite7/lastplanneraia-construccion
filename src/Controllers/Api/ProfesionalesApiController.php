<?php

namespace App\Controllers\Api;

use App\Core\Lps\LpsService;
use App\Services\ProjectProfessionalsSyncService;
use App\Security\RbacManager;
use PDO;
use Throwable;

use TableResolver;
class ProfesionalesApiController
{
    private const ALLOWED_CARGOS = [
        'Administrador',
        'Residente de Obra',
        'Residente SST',
        'Residente Ambiental',
        'Residente Oficina Técnica',
        'Profesional Diseño y Construcción Virtual',
        'Maestro de Obra',
        'Almacenista',
        'Director de Obra',
        'Residente SST + Ambiental',
        'Coordinador de Obras',
        'Gerente de Proyecto',
    ];

    private $db;
    private LpsService $lpsService;
    private RbacManager $rbac;
    private ProjectProfessionalsSyncService $syncService;

    public function __construct()
    {
        $this->db = \Database::getInstance();
        $this->lpsService = new LpsService();
        $this->rbac = new RbacManager();
        $this->syncService = new ProjectProfessionalsSyncService($this->db);
    }

    public function list(): void
    {
        require_once PROJECT_ROOT . '/src/Legacy/rbac_guard.php';
        rbac_guard_require_permission('lps.profesionales.ver');
        $dbPrefix = $_GET['db'] ?? '';

        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $dbPrefix)) {
            $this->json(["status" => "error", "message" => "Base de datos inválida ($dbPrefix)."]);
            return;
        }

        try {
            $syncSummary = $this->syncService->syncProjectProfessionals($dbPrefix);
            $projectId = $this->projectId($dbPrefix);

            $tables = [
                "" . TableResolver::resolveByPrefix($dbPrefix, 'programa') . "" => "Responsable_AIA",
                "" . TableResolver::resolveByPrefix($dbPrefix, 'cip') . "" => "profesional",
                "" . TableResolver::resolveByPrefix($dbPrefix, 'programacion_semanal') . "" => "Responsable_AIA",
                "" . TableResolver::resolveByPrefix($dbPrefix, 'programa_consolidado') . "" => "Responsable_AIA",
            ];

            $dependencyChecks = [];
            foreach ($tables as $tbl => $col) {
                if ($this->tableHasColumn($tbl, $col)) {
                    $dependencyChecks[] = "(SELECT COUNT(*) FROM $tbl WHERE $tbl.project_id = ? AND $tbl.$col = p.nombre) > 0";
                }
            }

            $depSql = !empty($dependencyChecks) ? ", ( " . implode(" OR ", $dependencyChecks) . " ) as has_dependencies" : ", 0 as has_dependencies";

            $query = "SELECT p.id, p.nombre, p.email, p.cargo, p.activo $depSql FROM " . TableResolver::resolveByPrefix($dbPrefix, 'profesionales') . " p WHERE p.project_id = ? AND p.nombre IS NOT NULL AND TRIM(p.nombre) != '' ORDER BY p.id ASC";
            $params = array_fill(0, count($dependencyChecks), $projectId);
            $params[] = $projectId;
            $data = $this->db->query($query, $params)->fetchAll(PDO::FETCH_ASSOC);
            $data = $this->syncService->decorateProjectProfessionals($dbPrefix, $data);

            foreach ($data as &$row) {
                $row['activo'] = (bool) $row['activo'];
                $row['has_dependencies'] = (bool) $row['has_dependencies'];
                $row['is_admin_managed'] = (bool) ($row['is_admin_managed'] ?? false);
                $row['is_current_member'] = (bool) ($row['is_current_member'] ?? false);
                $row['is_blocked'] = (bool) ($row['is_blocked'] ?? false);
                $row['can_edit_identity'] = !$row['is_admin_managed'] && !$row['is_blocked'];
                $row['can_edit_active'] = !$row['is_blocked'] && (!$row['is_admin_managed'] || $row['is_current_member']);
                $row['identity_edit_reason'] = $this->resolverMotivoEdicionIdentidad($row);
                $row['active_edit_reason'] = $this->resolverMotivoEdicionActivo($row);
                $row['can_delete'] = !$row['has_dependencies'] && !$row['is_admin_managed'] && !$row['is_blocked'];
                $row['delete_reason'] = $this->resolverMotivoEliminacion($row);
            }

            $response = ["status" => "success", "data" => $data];
            if (!empty($syncSummary['warnings'])) {
                $response['sync_warnings'] = $syncSummary['warnings'];
            }
            if (($syncSummary['inserted'] + $syncSummary['reactivated'] + $syncSummary['updated'] + ($syncSummary['blocked'] ?? 0) + ($syncSummary['deduplicated'] ?? 0)) > 0) {
                $response['sync_summary'] = $syncSummary;
            }

            $this->json($response);
        } catch (Throwable $t) {
            $this->json(["status" => "error", "message" => "Error del servidor: " . $t->getMessage()]);
        }
    }

    public function save(): void
    {
        require_once PROJECT_ROOT . '/src/Legacy/rbac_guard.php';
        rbac_guard_require_permission('lps.profesionales.editar');
        legacy_require_csrf('profesionales');
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
        $projectId = $this->projectId($dbPrefix);
        $changes = $_POST['cambios'] ?? [];
        if (empty($changes)) {
            $this->json(["status" => "success", "message" => "No hubo cambios."]);
            return;
        }

        $errores = [];
        $actualizados = 0;
        $allowed = ['nombre', 'email', 'cargo', 'activo'];

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
            $actual = $this->obtenerProfesional($dbPrefix, $id);
            if (!$actual) {
                $errores[] = "Profesional ID $id no encontrado.";
                continue;
            }

            $lockInfo = $this->syncService->getProfessionalLockInfo($dbPrefix, (string) ($actual['email'] ?? ''));
            $columnasCambiadas = array_keys($rowChanges);
            $soloActivo = !empty($columnasCambiadas) && count(array_diff($columnasCambiadas, ['activo'])) === 0;

            if (!empty($lockInfo['is_blocked'])) {
                $errores[] = $lockInfo['block_reason'] ?: 'Este profesional está bloqueado y no puede editarse desde este módulo.';
                continue;
            }

            if (!empty($lockInfo['is_admin_managed'])) {
                if (!$soloActivo) {
                    $errores[] = 'Este profesional se sincroniza desde Admin. Aqui solo puedes cambiar Activo.';
                    continue;
                }

                $activo = $this->normalizarBooleano($rowChanges['activo'] ?? $actual['activo']);
                $resultado = $this->db->queryWithProject(
                    "UPDATE " . TableResolver::resolveByPrefix($dbPrefix, 'profesionales') . " SET activo = ? WHERE project_id = ? AND id = ?",
                    [$activo, $projectId, $id],
                    $projectId,
                );

                if ($resultado) {
                    $actualizados++;
                } else {
                    $errores[] = "Error actualizando el estado activo del profesional '{$actual['nombre']}'.";
                }

                continue;
            }

            $actualizado = $this->aplicarCambiosProfesional($actual, $rowChanges);
            $actualizado['nombre'] = $this->syncService->resolveCanonicalProfessionalName(
                $actualizado['email'],
                $actualizado['nombre'],
            );
            $erroresFila = $this->validarProfesional($dbPrefix, $actualizado, $id);
            if (!empty($erroresFila)) {
                $errores = array_merge($errores, $erroresFila);
                continue;
            }

            $resultado = $this->db->queryWithProject(
                "UPDATE " . TableResolver::resolveByPrefix($dbPrefix, 'profesionales') . " SET nombre = ?, email = ?, cargo = ?, activo = ? WHERE project_id = ? AND id = ?",
                [$actualizado['nombre'], $actualizado['email'], $actualizado['cargo'], $actualizado['activo'], $projectId, $id],
                $projectId,
            );

            if ($resultado) {
                if ($this->normalizarTexto($actual['nombre']) !== $this->normalizarTexto($actualizado['nombre'])) {
                    $this->actualizar_dependencias_nombre($dbPrefix, $actual['nombre'], $actualizado['nombre']);
                }
                $actualizados++;
            } else {
                $errores[] = "Error actualizando el profesional '{$actualizado['nombre']}'.";
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
        $tablesToUpdate = [
            "" . TableResolver::resolveByPrefix($dbPrefix, 'programa') . "" => "Responsable_AIA",
            "" . TableResolver::resolveByPrefix($dbPrefix, 'programacion_semanal') . "" => "Responsable_AIA",
            "" . TableResolver::resolveByPrefix($dbPrefix, 'programa_consolidado') . "" => "Responsable_AIA",
            "" . TableResolver::resolveByPrefix($dbPrefix, 'cip') . "" => "profesional",
            "" . TableResolver::resolveByPrefix($dbPrefix, 'indicadores_generales') . "" => "subcontratista_profesional",
        ];

        foreach ($tablesToUpdate as $tbl => $col) {
            if ($this->tableHasColumn($tbl, $col)) {
                $this->db->query("UPDATE $tbl SET $col = ? WHERE project_id = ? AND $col = ?", [$newName, $projectId, $oldName]);
            }
        }
    }

    private function crear(string $dbPrefix): void
    {
        $projectId = $this->projectId($dbPrefix);
        $data = $this->sanitizarProfesional([
            'nombre' => $_POST['nombre'] ?? '',
            'email' => $_POST['email'] ?? '',
            'cargo' => $_POST['cargo'] ?? '',
            'activo' => 1,
        ]);
        $data['nombre'] = $this->syncService->resolveCanonicalProfessionalName($data['email'], $data['nombre']);

        $errores = $this->validarProfesional($dbPrefix, $data);
        if (!empty($errores)) {
            $this->json(["status" => "error", "message" => implode("\n", $errores), "errors" => $errores]);
            return;
        }

        $res = $this->db->queryWithProject(
            "INSERT INTO " . TableResolver::resolveByPrefix($dbPrefix, 'profesionales') . " (project_id, nombre, email, cargo, activo) VALUES (?, ?, ?, ?, 1)",
            [$projectId, $data['nombre'], $data['email'], $data['cargo']],
            $projectId,
        );

        if ($res) {
            $id = $this->db->queryWithProject(
                "SELECT id FROM " . TableResolver::resolveByPrefix($dbPrefix, 'profesionales') . " WHERE project_id = ? AND email = ? ORDER BY id DESC LIMIT 1",
                [$projectId, $data['email']],
                $projectId,
            )->fetchColumn();
            $this->json(["status" => "success", "id" => $id ?: $this->db->lastInsertId(), "message" => "Profesional creado."]);
        } else {
            $this->json(["status" => "error", "message" => "Error al crear profesional."]);
        }
    }

    private function eliminar(string $dbPrefix): void
    {
        $projectId = $this->projectId($dbPrefix);
        $id = $_POST['id'] ?? 0;
        $profesional = $this->obtenerProfesional($dbPrefix, (int) $id);
        $nombre = $profesional['nombre'] ?? null;

        if (!$nombre) {
            $this->json(["status" => "error", "message" => "Profesional no encontrado."]);
            return;
        }

        $lockInfo = $this->syncService->getProfessionalLockInfo($dbPrefix, (string) ($profesional['email'] ?? ''));
        if (!empty($lockInfo['is_admin_managed'])) {
            $mensaje = !empty($lockInfo['is_blocked'])
                ? ($lockInfo['block_reason'] ?: 'Este profesional está bloqueado y no puede eliminarse.')
                : 'No se puede eliminar: el profesional está administrado desde Admin. Retíralo del proyecto para bloquearlo.';
            $this->json(["status" => "error", "message" => $mensaje]);
            return;
        }

        $tables = [
            "" . TableResolver::resolveByPrefix($dbPrefix, 'programa') . "" => "Responsable_AIA",
            "" . TableResolver::resolveByPrefix($dbPrefix, 'cip') . "" => "profesional",
            "" . TableResolver::resolveByPrefix($dbPrefix, 'programacion_semanal') . "" => "Responsable_AIA",
            "" . TableResolver::resolveByPrefix($dbPrefix, 'programa_consolidado') . "" => "Responsable_AIA",
        ];

        foreach ($tables as $tbl => $col) {
            if ($this->tableHasColumn($tbl, $col)) {
                if ($this->db->query("SELECT COUNT(*) FROM $tbl WHERE project_id = ? AND $col = ?", [$projectId, $nombre])->fetchColumn() > 0) {
                    $this->json(["status" => "error", "message" => "No se puede eliminar: Tiene registros asociados."]);
                    return;
                }
            }
        }

        if ($this->db->queryWithProject("DELETE FROM " . TableResolver::resolveByPrefix($dbPrefix, 'profesionales') . " WHERE project_id = ? AND id = ?", [$projectId, $id], $projectId)) {
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

    private function obtenerProfesional(string $dbPrefix, int $id): ?array
    {
        $projectId = $this->projectId($dbPrefix);
        $stmt = $this->db->queryWithProject(
            "SELECT id, nombre, email, cargo, activo FROM " . TableResolver::resolveByPrefix($dbPrefix, 'profesionales') . " WHERE project_id = ? AND id = ?",
            [$projectId, $id],
            $projectId,
        );
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    private function aplicarCambiosProfesional(array $actual, array $changes): array
    {
        $mezclado = [
            'nombre' => array_key_exists('nombre', $changes) ? $changes['nombre'] : $actual['nombre'],
            'email' => array_key_exists('email', $changes) ? $changes['email'] : $actual['email'],
            'cargo' => array_key_exists('cargo', $changes) ? $changes['cargo'] : $actual['cargo'],
            'activo' => array_key_exists('activo', $changes) ? $changes['activo'] : $actual['activo'],
        ];

        return $this->sanitizarProfesional($mezclado);
    }

    private function sanitizarProfesional(array $data): array
    {
        return [
            'nombre' => $this->limpiarTexto($data['nombre'] ?? ''),
            'email' => $this->normalizarEmail($data['email'] ?? ''),
            'cargo' => $this->limpiarTexto($data['cargo'] ?? ''),
            'activo' => $this->normalizarBooleano($data['activo'] ?? 1),
        ];
    }

    private function validarProfesional(string $dbPrefix, array $data, ?int $excludeId = null): array
    {
        $errores = [];

        if ($data['nombre'] === '') {
            $errores[] = 'El nombre es obligatorio.';
        }

        if ($data['email'] === '') {
            $errores[] = 'El correo es obligatorio.';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errores[] = 'El correo no tiene un formato válido.';
        }

        if ($data['cargo'] === '') {
            $errores[] = 'El cargo es obligatorio.';
        } elseif (!in_array($data['cargo'], self::ALLOWED_CARGOS, true)) {
            $errores[] = 'El cargo seleccionado no es válido.';
        }

        foreach ($this->buscarDuplicadosProfesional($dbPrefix, $data, $excludeId) as $error) {
            $errores[] = $error;
        }

        $lockInfo = $this->syncService->getProfessionalLockInfo($dbPrefix, $data['email']);
        if (!empty($lockInfo['is_admin_managed'])) {
            $errores[] = !empty($lockInfo['is_blocked'])
                ? ($lockInfo['block_reason'] ?: 'El correo corresponde a un profesional bloqueado para este proyecto.')
                : 'El correo corresponde a un profesional administrado desde Admin. Gestiona sus cambios allí.';
        }

        return array_values(array_unique($errores));
    }

    private function buscarDuplicadosProfesional(string $dbPrefix, array $data, ?int $excludeId = null): array
    {
        $errores = [];
        $projectId = $this->projectId($dbPrefix);
        $params = [$projectId];
        $sql = "SELECT id, email FROM " . TableResolver::resolveByPrefix($dbPrefix, 'profesionales') . " WHERE project_id = ?";

        if ($excludeId !== null) {
            $sql .= ' AND id != ?';
            $params[] = $excludeId;
        }

        $rows = $this->db->queryWithProject($sql, $params, $projectId)->fetchAll(PDO::FETCH_ASSOC);
        $emailNormalizado = $this->normalizarEmail($data['email']);

        foreach ($rows as $row) {
            if ($emailNormalizado !== '' && $this->normalizarEmail($row['email'] ?? '') === $emailNormalizado) {
                $errores[] = 'Ya existe un profesional con ese correo.';
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

    private function normalizarBooleano($valor): int
    {
        return ($valor === 'true' || $valor === '1' || $valor === 1 || $valor === true) ? 1 : 0;
    }

    private function resolverMotivoEliminacion(array $row): ?string
    {
        if (!empty($row['has_dependencies'])) {
            return 'No se puede eliminar: tiene registros asociados.';
        }

        if (!empty($row['is_blocked'])) {
            return $row['block_reason'] ?? 'Este profesional está bloqueado.';
        }

        if (!empty($row['is_admin_managed'])) {
            return 'No se puede eliminar: el profesional está administrado desde Admin.';
        }

        return null;
    }

    private function resolverMotivoEdicionIdentidad(array $row): ?string
    {
        if (!empty($row['is_blocked'])) {
            return $row['block_reason'] ?? 'Este profesional está bloqueado.';
        }

        if (!empty($row['is_admin_managed'])) {
            return 'Este profesional se sincroniza desde Admin. Aqui solo puedes cambiar Activo.';
        }

        return null;
    }

    private function resolverMotivoEdicionActivo(array $row): ?string
    {
        if (!empty($row['is_blocked'])) {
            return $row['block_reason'] ?? 'Este profesional está bloqueado.';
        }

        return null;
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

        // Por Database::columnExists(): consultar `information_schema` con query() lanza
        // DomainException desde que ProjectSqlGuard cerró las tablas calificadas por schema.
        return $this->db->columnExists($table, $column);
    }
}
