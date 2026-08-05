<?php

namespace App\Security;

use Database;

class RbacService
{
    private $db;
    private $tableExistsCache = [];
    private $rolePermissionsCache = [];

    public function __construct($db = null)
    {
        $this->db = $db ?: Database::getInstance();
    }

    public function normalizeRole(?string $role): string
    {
        $role = strtoupper(trim((string) $role));
        if ($role === '') {
            return RbacCatalog::DEFAULT_ROLE;
        }

        $aliases = RbacCatalog::roleAliases();
        if (isset($aliases[$role])) {
            $role = $aliases[$role];
        }

        if (!in_array($role, RbacCatalog::canonicalRoles(), true)) {
            return RbacCatalog::DEFAULT_ROLE;
        }

        return $role;
    }

    public function resolveCurrentRole(): string
    {
        $usuario = (string) ($_SESSION['usuario'] ?? ($_SESSION['admin_user']['usuario'] ?? ''));
        $projectName = $_SESSION['proyecto'] ?? null;
        $dbName = $_SESSION['db'] ?? null;

        $role = null;
        if ($usuario !== '') {
            $role = $this->resolveRoleFromProjectMembers($usuario, $projectName, $dbName);
        }

        if ($role === null) {
            $role = $_SESSION['permiso'] ?? ($_SESSION['admin_user']['permiso'] ?? null);
        }

        $canonical = $this->normalizeRole($role);

        $_SESSION['permiso_canonico'] = $canonical;
        if (isset($_SESSION['permiso'])) {
            $_SESSION['permiso'] = $canonical;
        }
        if (isset($_SESSION['admin_user']) && is_array($_SESSION['admin_user'])) {
            $_SESSION['admin_user']['permiso'] = $canonical;
        }

        return $canonical;
    }

    public function resolveRoleForUser(string $usuario, ?string $projectName = null, ?string $dbName = null): string
    {
        $usuario = trim($usuario);
        if ($usuario === '') {
            return RbacCatalog::DEFAULT_ROLE;
        }

        $role = $this->resolveRoleFromProjectMembers($usuario, $projectName, $dbName);

        return $this->normalizeRole($role);
    }

    public function userHasProjectAccess(string $usuario, string $projectName, ?string $dbName = null): bool
    {
        $usuario = trim($usuario);
        $projectName = trim($projectName);
        $dbName = trim((string) $dbName);

        if ($usuario === '' || ($projectName === '' && $dbName === '')) {
            return false;
        }

        if ($this->resolveRoleFromProjectMembers($usuario, $projectName, $dbName) !== null) {
            return true;
        }

        return false;
    }

    public function can(string $permissionKey, ?string $role = null): bool
    {
        $permissionKey = strtolower(trim($permissionKey));
        if ($permissionKey === '') {
            return false;
        }

        $role = $this->normalizeRole($role ?? $this->resolveCurrentRole());
        if ($role === 'V' && in_array($permissionKey, [
            'lps.pdc.editar',
        ], true)) {
            return false;
        }
        $map = $this->getPermissionMap($role);

        if (isset($map['*']) && $map['*'] === true) {
            return true;
        }

        return !empty($map[$permissionKey]);
    }

    public function getPermissionMap(?string $role = null): array
    {
        $role = $this->normalizeRole($role ?? $this->resolveCurrentRole());
        if (isset($this->rolePermissionsCache[$role])) {
            return $this->rolePermissionsCache[$role];
        }

        $permissions = $this->loadRolePermissionsFromDb($role);
        if (empty($permissions)) {
            $permissions = $this->loadRolePermissionsFromFallback($role);
        }

        $this->rolePermissionsCache[$role] = $permissions;

        return $permissions;
    }

    private function resolveRoleFromProjectMembers(string $usuario, ?string $projectName, ?string $dbName): ?string
    {
        if (!$this->tableExists('project_members')) {
            return null;
        }

        $conditions = [];
        $params = [$usuario];

        if (!empty($projectName)) {
            $conditions[] = 'p.Proyecto_Proceso = ?';
            $params[] = $projectName;
        }

        if (!empty($dbName)) {
            $conditions[] = 'p.Base_de_Datos = ?';
            $params[] = $dbName;
        }

        if (empty($conditions)) {
            $sql = "SELECT pm.role
                    FROM project_members pm
                    INNER JOIN general_usuarios u ON u.id = pm.user_id
                    WHERE u.usuario = ?
                    ORDER BY FIELD(pm.role, 'A', 'D', 'R', 'G', 'S', 'SG', 'DCV', 'OT', 'V', 'C') ASC,
                             pm.id DESC
                    LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$usuario]);
            $row = $stmt->fetch();

            if (!$row || empty($row['role'])) {
                return null;
            }

            return (string) $row['role'];
        }

        $sql = "SELECT pm.role
                FROM project_members pm
                INNER JOIN general_usuarios u ON u.id = pm.user_id
                INNER JOIN general_proyectos_procesos p ON p.ID = pm.project_id
                WHERE u.usuario = ?
                  AND (" . implode(' OR ', $conditions) . ")
                ORDER BY pm.id DESC
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();

        if (!$row || empty($row['role'])) {
            return null;
        }

        return (string) $row['role'];
    }

    private function loadRolePermissionsFromDb(string $role): array
    {
        if (!$this->tableExists('rbac_permissions') || !$this->tableExists('rbac_role_permissions')) {
            return [];
        }

        $sql = "SELECT p.permission_key, rp.allowed
                FROM rbac_permissions p
                LEFT JOIN rbac_role_permissions rp
                    ON rp.permission_key = p.permission_key
                   AND rp.role_code = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$role]);
        $rows = $stmt->fetchAll();

        if (empty($rows)) {
            return [];
        }

        $anyDbRow = false;
        $dbMap = [];
        foreach ($rows as $row) {
            $key = strtolower((string) ($row['permission_key'] ?? ''));
            if ($key === '') {
                continue;
            }
            $anyDbRow = true;

            if ($row['allowed'] !== null) {
                $dbMap[$key] = ((int) $row['allowed'] === 1);
            }
        }

        if (!$anyDbRow) {
            return [];
        }

        $fallbackMap = $this->loadRolePermissionsFromFallback($role);

        return array_merge($fallbackMap, $dbMap);
    }

    private function loadRolePermissionsFromFallback(string $role): array
    {
        $roleMap = RbacCatalog::fallbackPermissionsByRole();
        $granted = $roleMap[$role] ?? [];

        $map = [];
        if (in_array('*', $granted, true)) {
            $map['*'] = true;
            return $map;
        }

        foreach ($granted as $permissionKey) {
            $map[strtolower($permissionKey)] = true;
        }

        return $map;
    }

    private function tableExists(string $tableName): bool
    {
        if (isset($this->tableExistsCache[$tableName])) {
            return $this->tableExistsCache[$tableName];
        }

        $sql = "SELECT COUNT(*) AS total
                FROM information_schema.tables
                WHERE table_schema = DATABASE()
                  AND table_name = ?";

        $exists = false;
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$tableName]);
            $row = $stmt->fetch();
            $exists = ((int) ($row['total'] ?? 0) > 0);
        } catch (\Throwable $e) {
            $exists = false;
        }

        $this->tableExistsCache[$tableName] = $exists;

        return $exists;
    }
}
