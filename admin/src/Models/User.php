<?php

namespace Admin\Models;

use Admin\Core\RoleManager;

class User
{
    private $db;
    private $table = 'general_usuarios';

    private const ROLE_ORDER_SQL = "FIELD(pm.role, 'A', 'D', 'R', 'G', 'S', 'SG', 'DCV', 'OT', 'V', 'C')";

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Check if a user has a specific permission or role.
     *
     * @param string $permission The permission or role name
     * @param array $user The user data (optional, defaults to checking the current instance if it were an object)
     * @return bool
     */
    public static function can($user, $permission)
    {
        if (!$user || !isset($user['permiso'])) {
            return false;
        }

        $role = strtoupper(trim((string)$user['permiso']));
        if ($role === 'P') {
            $role = 'D';
        } elseif ($role === 'U') {
            $role = 'V';
        }

        $permission = strtoupper(trim((string)$permission));
        if ($permission === 'P') {
            $permission = 'D';
        } elseif ($permission === 'U') {
            $permission = 'V';
        }

        // Admin has all permissions (A = Global Admin)
        if ($role === 'A') {
            return true;
        }

        // Exact match
        if ($role === $permission) {
            return true;
        }

        // Logic for multiple roles could be added here
        return false;
    }

    private function getHighestRoleForUser($userId)
    {
        $stmt = $this->db->query(
            "SELECT pm.role
             FROM project_members pm
             INNER JOIN general_proyectos_procesos p ON p.Id = pm.project_id
             WHERE pm.user_id = ?
               AND p.Area = 'Construccion'
             ORDER BY " . self::ROLE_ORDER_SQL . " ASC
             LIMIT 1",
            [$userId]
        );
        $row = $stmt->fetch();

        return $row ? (string)$row['role'] : 'V';
    }

    /**
     * Find a user by their ID.
     *
     * @param int $id
     * @return array|false
     */
    public function find($id)
    {
        $stmt = $this->db->query("SELECT * FROM {$this->table} WHERE id = ? LIMIT 1", [$id]);
        $user = $stmt->fetch();
        if ($user) {
            $user['permiso'] = $this->getHighestRoleForUser($user['id']);
        }
        return $user;
    }

    /**
     * Find a user by their username.
     *
     * @param string $username
     * @return array|false
     */
    public function findByUsername($username)
    {
        $stmt = $this->db->query("SELECT * FROM {$this->table} WHERE usuario = ? LIMIT 1", [$username]);
        $user = $stmt->fetch();
        if ($user) {
            $user['permiso'] = $this->getHighestRoleForUser($user['id']);
        }
        return $user;
    }

    /**
     * Find a user by their email.
     *
     * @param string $email
     * @return array|false
     */
    public function findByEmail($email)
    {
        if (empty($email)) {
            return false;
        }
        $stmt = $this->db->query("SELECT * FROM {$this->table} WHERE email = ? LIMIT 1", [$email]);
        $user = $stmt->fetch();
        if ($user) {
            $user['permiso'] = $this->getHighestRoleForUser($user['id']);
        }
        return $user;
    }

    /**
     * Find a user by their full name.
     *
     * @param string $name
     * @return array|false
     */
    public function findByName($name)
    {
        if (empty($name)) {
            return false;
        }
        $stmt = $this->db->query("SELECT * FROM {$this->table} WHERE nombre = ? LIMIT 1", [$name]);
        $user = $stmt->fetch();
        if ($user) {
            $user['permiso'] = $this->getHighestRoleForUser($user['id']);
        }
        return $user;
    }

    /**
     * Get the total number of users.
     *
     * @return int
     */
    public function count()
    {
        $stmt = $this->db->query("SELECT COUNT(*) as total FROM {$this->table}");
        $row = $stmt->fetch();

        return (int) ($row['total'] ?? 0);
    }

    /**
     * Get all users.
     *
     * @return array
     */
    public function getAll()
    {
        $stmt = $this->db->query(
            "SELECT u.*,
                    (SELECT pm.role
                     FROM project_members pm
                     INNER JOIN general_proyectos_procesos p1 ON p1.Id = pm.project_id
                     WHERE pm.user_id = u.id
                       AND p1.Area = 'Construccion'
                     ORDER BY " . self::ROLE_ORDER_SQL . " ASC
                     LIMIT 1) AS permiso,
                    (SELECT COUNT(*)
                     FROM project_members pm2
                     INNER JOIN general_proyectos_procesos p2 ON p2.Id = pm2.project_id
                     WHERE pm2.user_id = u.id
                       AND p2.Area = 'Construccion') AS projects_count
             FROM {$this->table} u
             ORDER BY u.nombre ASC"
        );

        return $stmt->fetchAll();
    }

    /**
     * Get project assignments for a user.
     *
     * @param int $userId
     * @return array
     */
    public function getProjectAssignments($userId)
    {
        $stmt = $this->db->query(
            "SELECT pm.project_id,
                    pm.role,
                    p.Proyecto_Proceso,
                    p.Activo
             FROM project_members pm
             INNER JOIN general_proyectos_procesos p ON p.Id = pm.project_id
             WHERE pm.user_id = ?
               AND p.Area = 'Construccion'
             ORDER BY p.Proyecto_Proceso ASC",
            [$userId]
        );

        return $stmt->fetchAll();
    }

    /**
     * Create a new user.
     *
     * @param array $data
     * @return bool
     */
    public function create($data)
    {
        $this->db->beginTransaction();
        try {
            $sql = "INSERT INTO {$this->table} (nombre, email, cargo, usuario, password)
                    VALUES (?, ?, ?, ?, ?)";

            $password = password_hash($data['password'], PASSWORD_DEFAULT);

            $this->db->query($sql, [
                $data['nombre'],
                $data['email'],
                $data['cargo'],
                $data['usuario'],
                $password,
            ]);

            $userId = $this->db->lastInsertId();

            $this->syncProjectMembers((int)$userId, $data['assignments'] ?? []);

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    /**
     * Update an existing user.
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update($id, $data)
    {
        $this->db->beginTransaction();
        try {
            $fields = [
                'nombre = ?',
                'email = ?',
                'cargo = ?',
                'usuario = ?',
            ];

            $params = [
                $data['nombre'],
                $data['email'],
                $data['cargo'],
                $data['usuario'],
            ];

            // If password is provided, update it too
            if (!empty($data['password'])) {
                $fields[] = 'password = ?';
                $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
            }

            $params[] = $id;
            $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE id = ?";
            
            $this->db->query($sql, $params);

            if (array_key_exists('assignments', $data)) {
                $this->syncProjectMembers((int)$id, $data['assignments']);
            }

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    /**
     * Synchronize the user's roles in project_members.
     */
    private function syncProjectMembers(int $userId, array $assignments): void
    {
        $this->db->query("DELETE FROM project_members WHERE user_id = ?", [$userId]);

        if (empty($assignments)) {
            return;
        }

        $validProjectIds = $this->getValidConstructionProjectIds();
        $insertSql = "INSERT INTO project_members (project_id, user_id, role) VALUES (?, ?, ?)";
        $seen = [];

        foreach ($assignments as $assignment) {
            if (!is_array($assignment)) {
                continue;
            }

            $projectId = (int)($assignment['project_id'] ?? 0);
            if ($projectId <= 0 || !isset($validProjectIds[$projectId]) || isset($seen[$projectId])) {
                continue;
            }

            $role = $this->normalizeRole((string)($assignment['role'] ?? 'V'));
            $this->db->query($insertSql, [$projectId, $userId, $role]);
            $seen[$projectId] = true;
        }
    }

    private function getValidConstructionProjectIds(): array
    {
        $stmt = $this->db->query("SELECT Id FROM general_proyectos_procesos WHERE Area = 'Construccion'");
        $rows = $stmt->fetchAll();
        $result = [];

        foreach ($rows as $row) {
            $projectId = (int)($row['Id'] ?? 0);
            if ($projectId > 0) {
                $result[$projectId] = true;
            }
        }

        return $result;
    }

    private function normalizeRole(string $role): string
    {
        $normalized = RoleManager::normalizeRole($role);
        if ($normalized === 'P') {
            return 'D';
        }
        if ($normalized === 'U') {
            return 'V';
        }

        return $normalized ?: 'V';
    }

    /**
     * Delete a user.
     *
     * @param int $id
     * @return bool
     */
    public function delete($id)
    {
        if ($this->getDeletionBlockReason((int)$id) !== null) {
            return false;
        }

        $this->db->beginTransaction();
        try {
            $this->db->query("DELETE FROM project_members WHERE user_id = ?", [$id]);
            $this->db->query("DELETE FROM {$this->table} WHERE id = ?", [$id]);
            $this->db->commit();

            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();

            return false;
        }
    }

    public function getDeletionBlockReason(int $id): ?string
    {
        if ($id <= 0) {
            return 'Usuario inválido.';
        }

        $user = $this->find($id);
        if (!$user) {
            return 'Usuario no encontrado.';
        }

        $activeAssignments = (int)$this->db->query(
            "SELECT COUNT(*) FROM project_members WHERE user_id = ?",
            [$id]
        )->fetchColumn();

        if ($activeAssignments > 0) {
            return 'No se puede eliminar: el usuario sigue asignado a uno o más proyectos. Retíralo de los proyectos para bloquearlo en cada uno.';
        }

        $email = $this->normalizeEmail($user['email'] ?? '');
        if ($email === '') {
            return null;
        }

        $projectRefs = $this->findProjectProfessionalReferences($email);
        if (!empty($projectRefs)) {
            $sample = implode(', ', array_slice($projectRefs, 0, 3));
            $suffix = count($projectRefs) > 3 ? ', ...' : '';
            return "No se puede eliminar: el usuario tiene historial en Profesionales de proyecto(s): {$sample}{$suffix}. Debe mantenerse para conservar la trazabilidad.";
        }

        return null;
    }

    private function findProjectProfessionalReferences(string $email): array
    {
        $references = [];
        $projects = $this->db->query(
            "SELECT Proyecto_Proceso, Base_de_Datos
             FROM general_proyectos_procesos
             WHERE Area = 'Construccion'
               AND Base_de_Datos IS NOT NULL
               AND TRIM(Base_de_Datos) != ''"
        )->fetchAll();

        foreach ($projects as $project) {
            $dbPrefix = trim((string)($project['Base_de_Datos'] ?? ''));
            if ($dbPrefix === '' || !preg_match('/^[A-Za-z0-9_\-]+$/', $dbPrefix)) {
                continue;
            }

            $tableName = "{$dbPrefix}_profesionales";
            if (!$this->tableExists($tableName)) {
                continue;
            }

            $hasReference = (int)$this->db->query(
                "SELECT COUNT(*) FROM {$tableName} WHERE LOWER(TRIM(email)) = ?",
                [$email]
            )->fetchColumn();

            if ($hasReference > 0) {
                $references[] = trim((string)($project['Proyecto_Proceso'] ?? $dbPrefix));
            }
        }

        return $references;
    }

    /**
     * Check if a user can be removed from a project.
     *
     * @return string|null Error message or null if OK
     */
    public function canRemoveFromProject(int $userId, int $projectId): ?string
    {
        // 2. Get project info
        $project = $this->db->query(
            "SELECT Id, Activo, Base_de_Datos FROM general_proyectos_procesos WHERE Id = ?",
            [$projectId]
        )->fetch();

        if (!$project) {
            return 'Proyecto no encontrado.';
        }

        // 3. Inactive project → always removable
        if ((int)($project['Activo'] ?? 0) === 0) {
            return null;
        }

        // 4. Active project → check for activities
        $dbPrefix = trim((string)($project['Base_de_Datos'] ?? ''));
        if ($dbPrefix === '') {
            return null; // No tables, no activities
        }

        $user = $this->find($userId);
        if (!$user) {
            return 'Usuario no encontrado.';
        }

        $userName = trim((string)($user['nombre'] ?? ''));
        $userEmail = $this->normalizeEmail($user['email'] ?? '');

        return $this->checkUserActivityInProject(
            $dbPrefix, $userName, $userEmail
        );
    }

    /**
     * Check if a user has activity in a project's tables.
     */
    private function checkUserActivityInProject(
        string $dbPrefix,
        string $userName,
        string $userEmail
    ): ?string {
        // A) Check {prefix}_profesionales (activo = 1)
        $profTable = "{$dbPrefix}_profesionales";
        if ($this->tableExists($profTable)) {
            $found = $this->findProfessionalMatch(
                $profTable, $userName, $userEmail
            );
            if ($found) {
                return "No se puede quitar: el usuario está como profesional activo en el proyecto ('{$found}').";
            }
        }

        // B) Check {prefix}_programa_consolidado
        $consTable = "{$dbPrefix}_programa_consolidado";
        if ($this->tableExists($consTable)) {
            $found = $this->findResponsableMatch(
                $consTable, $userName, $userEmail
            );
            if ($found) {
                return "No se puede quitar: el usuario tiene actividades en el programa consolidado.";
            }
        }

        // C) Check {prefix}_programacion_semanal
        $semTable = "{$dbPrefix}_programacion_semanal";
        if ($this->tableExists($semTable)) {
            $found = $this->findResponsableMatch(
                $semTable, $userName, $userEmail
            );
            if ($found) {
                return "No se puede quitar: el usuario tiene actividades en la programación semanal.";
            }
        }

        return null;
    }

    private function tableExists(string $table): bool
    {
        $quotedTable = $this->db->quote($table);
        return (bool)$this->db->query(
            "SHOW TABLES LIKE " . $quotedTable
        )->fetch();
    }

    /**
     * Search by name first; if duplicates, disambiguate by email.
     */
    private function findProfessionalMatch(
        string $table,
        string $name,
        string $email
    ): ?string {
        if (empty($name)) {
            return null;
        }

        $rows = $this->db->query(
            "SELECT nombre, email FROM {$table} WHERE activo = 1 AND TRIM(nombre) = ?",
            [$name]
        )->fetchAll();

        if (count($rows) === 1) {
            return $rows[0]['nombre'];
        }

        if (count($rows) > 1 && !empty($email)) {
            foreach ($rows as $row) {
                $rowEmail = $this->normalizeEmail($row['email'] ?? '');
                if ($rowEmail === $email) {
                    return $row['nombre'];
                }
            }
        }

        return count($rows) > 0 ? $rows[0]['nombre'] : null;
    }

    /**
     * Search Responsable_AIA by name; fallback to email on duplicates.
     */
    private function findResponsableMatch(
        string $table,
        string $name,
        string $email
    ): bool {
        if (empty($name)) {
            return false;
        }

        // 1. Search by Name
        $count = (int)$this->db->query(
            "SELECT COUNT(*) FROM {$table} WHERE TRIM(Responsable_AIA) = ?",
            [$name]
        )->fetchColumn();

        if ($count > 0) {
            return true;
        }

        // 2. Fallback to Email
        if (!empty($email)) {
            $count = (int)$this->db->query(
                "SELECT COUNT(*) FROM {$table} WHERE TRIM(Responsable_AIA) = ?",
                [$email]
            )->fetchColumn();
        }

        return $count > 0;
    }

    /**
     * Remove a user from a specific project.
     */
    public function removeFromProject(int $userId, int $projectId): bool
    {
        return (bool)$this->db->query(
            "DELETE FROM project_members WHERE user_id = ? AND project_id = ?",
            [$userId, $projectId]
        );
    }

    private function normalizeEmail($email): string
    {
        $value = trim((string)$email);
        return function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);
    }

    /**
     * Force password change for all active users.
     *
     * @return int Number of affected rows
     */
    public function forcePasswordChangeForAll()
    {
        $sql = "UPDATE {$this->table} SET force_password_change = 1";
        $stmt = $this->db->query($sql);

        return $stmt->rowCount();
    }

    /**
     * Get statistics about password changes.
     *
     * @return array [total_active, completed, pending]
     */
    public function getPasswordChangeStats()
    {
        // total_active (considering all for now, maybe filtered by state in future)
        $total = $this->count();

        $sqlPending = "SELECT COUNT(*) as pending FROM {$this->table} WHERE force_password_change = 1";
        $pending = (int) $this->db->query($sqlPending)->fetchColumn();

        return [
            'total' => $total,
            'pending' => $pending,
            'completed' => $total - $pending,
        ];
    }
}
