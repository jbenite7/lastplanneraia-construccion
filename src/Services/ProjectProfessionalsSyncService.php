<?php

namespace App\Services;

use PDO;
use Throwable;
use TableResolver;

class ProjectProfessionalsSyncService
{
    private const ROLE_TO_CARGO = [
        'A' => 'Administrador',
        'D' => 'Director de Obra',
        'DCV' => 'Profesional Diseño y Construcción Virtual',
        'G' => 'Residente Ambiental',
        'OT' => 'Residente Oficina Técnica',
        'R' => 'Residente de Obra',
        'S' => 'Residente SST',
        'SG' => 'Residente SST + Ambiental',
    ];

    private $db;

    public function __construct($db = null)
    {
        $this->db = $db ?: \Database::getInstance();
    }

    public function syncProjectProfessionals(string $dbPrefix): array
    {
        $summary = [
            'inserted' => 0,
            'reactivated' => 0,
            'updated' => 0,
            'blocked' => 0,
            'deduplicated' => 0,
            'warnings' => [],
        ];

        $tProf = TableResolver::resolveByPrefix($dbPrefix, 'profesionales');
        $projectId = TableResolver::getProjectIdByPrefix($dbPrefix);

        $members = $this->fetchEligibleProjectMembers($dbPrefix);
        $seenMemberEmails = [];
        $currentMemberEmails = [];
        $handledDuplicateEmails = [];
        $startedTransaction = false;

        try {
            if (!$this->db->inTransaction()) {
                $this->db->beginTransaction();
                $startedTransaction = true;
            }

            $adminEmailStats = $this->fetchAdminEmailStats();
            $adminCanonicalNames = $this->fetchAdminCanonicalNames();
            $existingByEmail = $this->fetchExistingProfessionalsByEmail($dbPrefix, $summary);

            foreach ($members as $member) {
                $role = strtoupper(trim((string) ($member['role'] ?? '')));
                if (!isset(self::ROLE_TO_CARGO[$role])) {
                    continue;
                }

                $email = $this->normalizarEmail($member['email'] ?? '');
                $nombre = $adminCanonicalNames[$email] ?? $this->limpiarTexto($member['nombre'] ?? '');
                $usuario = trim((string) ($member['usuario'] ?? ''));
                $cargo = $this->limpiarTexto($member['cargo'] ?? '');
                if ($cargo === '') {
                    $cargo = self::ROLE_TO_CARGO[$role];
                }

                if ($nombre === '') {
                    $summary['warnings'][] = $this->buildMemberWarning($usuario, $role, 'no tiene nombre válido y no se sincronizó.');
                    continue;
                }

                if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $summary['warnings'][] = $this->buildMemberWarning($usuario !== '' ? $usuario : $nombre, $role, 'no tiene un correo válido y no se sincronizó.');
                    continue;
                }

                $currentMemberEmails[$email] = true;

                if (isset($seenMemberEmails[$email])) {
                    $summary['warnings'][] = "Hay más de un miembro asignado con el correo '{$email}'. Se omitieron duplicados durante la sincronización de Profesionales.";
                    continue;
                }

                $seenMemberEmails[$email] = true;
                $hasAdminDuplicates = ($adminEmailStats[$email] ?? 0) > 1;

                if ($hasAdminDuplicates) {
                    if (!isset($existingByEmail[$email])) {
                        $sql = "INSERT INTO {$tProf} (nombre, email, cargo, activo) VALUES (?, ?, ?, 0)";
                        [$sql, $params] = $this->db->insertProjectId($sql, $projectId ?? 0, [$nombre, $email, '']);
                        $this->db->query($sql, $params);

                        $existingByEmail[$email] = [
                            'id' => (int) $this->db->lastInsertId(),
                            'nombre' => $nombre,
                            'email' => $email,
                            'cargo' => '',
                            'activo' => 0,
                        ];
                        $summary['inserted']++;
                        $summary['blocked']++;
                    } else {
                        $blockedFields = [];
                        $blockedParams = [];

                        if ((int) ($existingByEmail[$email]['activo'] ?? 0) !== 0) {
                            $blockedFields[] = 'activo = ?';
                            $blockedParams[] = 0;
                            $existingByEmail[$email]['activo'] = 0;
                            $summary['blocked']++;
                        }

                        if ($this->limpiarTexto($existingByEmail[$email]['cargo'] ?? '') !== '') {
                            $blockedFields[] = 'cargo = ?';
                            $blockedParams[] = '';
                            $existingByEmail[$email]['cargo'] = '';
                            $summary['updated']++;
                        }

                        if ($this->limpiarTexto($existingByEmail[$email]['nombre'] ?? '') === '' && $nombre !== '') {
                            $blockedFields[] = 'nombre = ?';
                            $blockedParams[] = $nombre;
                            $existingByEmail[$email]['nombre'] = $nombre;
                            $summary['updated']++;
                        }

                        if (!empty($blockedFields)) {
                            $blockedParams[] = $projectId;
                            $blockedParams[] = $existingByEmail[$email]['id'];
                            $this->db->query(
                                "UPDATE {$tProf} SET " . implode(', ', $blockedFields) . ' WHERE project_id = ? AND id = ?',
                                $blockedParams,
                            );
                        }
                    }

                    if (!isset($handledDuplicateEmails[$email])) {
                        $summary['warnings'][] = "El correo '{$email}' está duplicado en Admin. El profesional quedó bloqueado en este proyecto hasta resolver el conflicto.";
                        $handledDuplicateEmails[$email] = true;
                    }

                    continue;
                }

                if (!isset($existingByEmail[$email])) {
                    $sql = "INSERT INTO {$tProf} (nombre, email, cargo, activo) VALUES (?, ?, ?, 1)";
                    [$sql, $params] = $this->db->insertProjectId($sql, $projectId ?? 0, [$nombre, $email, $cargo]);
                    $this->db->query($sql, $params);

                    $existingByEmail[$email] = [
                        'id' => (int) $this->db->lastInsertId(),
                        'nombre' => $nombre,
                        'email' => $email,
                        'cargo' => $cargo,
                        'activo' => 1,
                    ];
                    $summary['inserted']++;
                    continue;
                }

                $existing = $existingByEmail[$email];
                $fields = [];
                $params = [];
                $existingName = $this->limpiarTexto($existing['nombre'] ?? '');
                $nameChanged = false;

                if ($this->limpiarTexto($existing['cargo'] ?? '') !== $cargo) {
                    $fields[] = 'cargo = ?';
                    $params[] = $cargo;
                    $summary['updated']++;
                    $existing['cargo'] = $cargo;
                }

                if ($nombre !== '' && $existingName !== $nombre) {
                    $fields[] = 'nombre = ?';
                    $params[] = $nombre;
                    $summary['updated']++;
                    $existing['nombre'] = $nombre;
                    $nameChanged = true;
                }

                if (!empty($fields)) {
                    $params[] = $projectId;
                    $params[] = $existing['id'];
                    $this->db->query(
                        "UPDATE {$tProf} SET " . implode(', ', $fields) . ' WHERE project_id = ? AND id = ?',
                        $params,
                    );
                    if ($nameChanged) {
                        $this->replaceProfessionalDependencies($dbPrefix, $existingName, $existing['nombre']);
                    }
                    $existingByEmail[$email] = $existing;
                }
            }

            foreach ($existingByEmail as $email => $existing) {
                if ($email === '' || !isset($adminEmailStats[$email])) {
                    continue;
                }

                if (($adminEmailStats[$email] ?? 0) > 1) {
                    $duplicateFields = [];
                    $duplicateParams = [];

                    if ((int) ($existing['activo'] ?? 0) !== 0) {
                        $duplicateFields[] = 'activo = ?';
                        $duplicateParams[] = 0;
                        $summary['blocked']++;
                        $existing['activo'] = 0;
                    }

                    if ($this->limpiarTexto($existing['cargo'] ?? '') !== '') {
                        $duplicateFields[] = 'cargo = ?';
                        $duplicateParams[] = '';
                        $summary['updated']++;
                        $existing['cargo'] = '';
                    }

                    if (!empty($duplicateFields)) {
                        $duplicateParams[] = $projectId;
                        $duplicateParams[] = $existing['id'];
                        $this->db->query(
                            "UPDATE {$tProf} SET " . implode(', ', $duplicateFields) . ' WHERE project_id = ? AND id = ?',
                            $duplicateParams,
                        );
                    }
                    continue;
                }

                $fields = [];
                $params = [];
                $existingName = $this->limpiarTexto($existing['nombre'] ?? '');
                $canonicalName = $adminCanonicalNames[$email] ?? '';
                $nameChanged = false;

                if ($canonicalName !== '' && $existingName !== $canonicalName) {
                    $fields[] = 'nombre = ?';
                    $params[] = $canonicalName;
                    $summary['updated']++;
                    $existing['nombre'] = $canonicalName;
                    $nameChanged = true;
                }

                if (!isset($currentMemberEmails[$email]) && (int) ($existing['activo'] ?? 0) !== 0) {
                    $fields[] = 'activo = ?';
                    $params[] = 0;
                    $existing['activo'] = 0;
                    $summary['blocked']++;
                }

                if (!empty($fields)) {
                    $params[] = $projectId;
                    $params[] = $existing['id'];
                    $this->db->query(
                        "UPDATE {$tProf} SET " . implode(', ', $fields) . ' WHERE project_id = ? AND id = ?',
                        $params,
                    );

                    if ($nameChanged) {
                        $this->replaceProfessionalDependencies($dbPrefix, $existingName, $existing['nombre']);
                    }

                    $existingByEmail[$email] = $existing;
                }
            }

            if ($startedTransaction) {
                $this->db->commit();
            }
        } catch (Throwable $e) {
            if ($startedTransaction && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }

        $summary['warnings'] = array_values(array_unique($summary['warnings']));

        return $summary;
    }

    public function decorateProjectProfessionals(string $dbPrefix, array $rows): array
    {
        if (empty($rows)) {
            return $rows;
        }

        $adminEmailStats = $this->fetchAdminEmailStats();
        $currentMemberEmails = $this->fetchCurrentMemberEmails($dbPrefix);

        foreach ($rows as &$row) {
            $lockInfo = $this->resolveLockInfo(
                $this->normalizarEmail($row['email'] ?? ''),
                $adminEmailStats,
                $currentMemberEmails,
            );

            $row['is_admin_managed'] = $lockInfo['is_admin_managed'];
            $row['is_current_member'] = $lockInfo['is_current_member'];
            $row['is_blocked'] = $lockInfo['is_blocked'];
            $row['block_reason'] = $lockInfo['block_reason'];
        }

        return $rows;
    }

    public function getProfessionalLockInfo(string $dbPrefix, string $email): array
    {
        return $this->resolveLockInfo(
            $this->normalizarEmail($email),
            $this->fetchAdminEmailStats(),
            $this->fetchCurrentMemberEmails($dbPrefix),
        );
    }

    public function blockProfessionalByEmail(string $dbPrefix, string $email): bool
    {
        if (!preg_match('/^[A-Za-z0-9_\-]+$/', $dbPrefix)) {
            return false;
        }

        $normalizedEmail = $this->normalizarEmail($email);
        if ($normalizedEmail === '') {
            return false;
        }

        $tProf = TableResolver::resolveByPrefix($dbPrefix, 'profesionales');
        $projectId = TableResolver::getProjectIdByPrefix($dbPrefix);

        if (!$this->db->tableExists($tProf)) {
            return false;
        }

        $count = (int) $this->db->queryWithProject(
            "SELECT COUNT(*) FROM {$tProf} WHERE project_id = ? AND LOWER(TRIM(email)) = ?",
            [$projectId, $normalizedEmail],
            $projectId,
        )->fetchColumn();

        if ($count === 0) {
            return false;
        }

        $this->db->queryWithProject(
            "UPDATE {$tProf} SET activo = 0 WHERE project_id = ? AND LOWER(TRIM(email)) = ?",
            [$projectId, $normalizedEmail],
            $projectId,
        );

        return true;
    }

    public function resolveCanonicalProfessionalName(string $email, string $fallbackName = ''): string
    {
        $fallbackName = $this->limpiarTexto($fallbackName);
        $canonicalName = $this->fetchCanonicalAdminNameByEmail($this->normalizarEmail($email));

        return $canonicalName ?? $fallbackName;
    }

    private function fetchEligibleProjectMembers(string $dbPrefix): array
    {
        $roles = array_keys(self::ROLE_TO_CARGO);
        $placeholders = implode(', ', array_fill(0, count($roles), '?'));
        $params = array_merge([$dbPrefix], $roles);

        $sql = "SELECT pm.user_id, pm.role, u.nombre, u.email, u.usuario, u.cargo
                FROM project_members pm
                INNER JOIN general_usuarios u ON u.id = pm.user_id
                INNER JOIN general_proyectos_procesos p ON p.ID = pm.project_id
                WHERE p.Base_de_Datos = ?
                  AND pm.role IN ({$placeholders})
                ORDER BY pm.id ASC";

        return $this->db->query($sql, $params)->fetchAll(PDO::FETCH_ASSOC);
    }

    private function fetchCurrentMemberEmails(string $dbPrefix): array
    {
        $map = [];
        foreach ($this->fetchEligibleProjectMembers($dbPrefix) as $member) {
            $email = $this->normalizarEmail($member['email'] ?? '');
            if ($email !== '') {
                $map[$email] = true;
            }
        }

        return $map;
    }

    private function fetchAdminEmailStats(): array
    {
        $rows = $this->db->query(
            "SELECT LOWER(TRIM(email)) AS email_normalized, COUNT(*) AS total
             FROM general_usuarios
             WHERE email IS NOT NULL AND TRIM(email) != ''
             GROUP BY LOWER(TRIM(email))",
        )->fetchAll(PDO::FETCH_ASSOC);

        $stats = [];
        foreach ($rows as $row) {
            $email = $this->normalizarEmail($row['email_normalized'] ?? '');
            if ($email === '') {
                continue;
            }
            $stats[$email] = (int) ($row['total'] ?? 0);
        }

        return $stats;
    }

    private function fetchAdminCanonicalNames(): array
    {
        $rows = $this->db->query(
            "SELECT LOWER(TRIM(email)) AS email_normalized,
                    MAX(CASE WHEN nombre IS NOT NULL AND TRIM(nombre) != '' THEN TRIM(nombre) ELSE '' END) AS nombre
             FROM general_usuarios
             WHERE email IS NOT NULL AND TRIM(email) != ''
             GROUP BY LOWER(TRIM(email))
             HAVING COUNT(*) = 1
                AND MAX(CASE WHEN nombre IS NOT NULL AND TRIM(nombre) != '' THEN 1 ELSE 0 END) = 1",
        )->fetchAll(PDO::FETCH_ASSOC);

        $map = [];
        foreach ($rows as $row) {
            $email = $this->normalizarEmail($row['email_normalized'] ?? '');
            $nombre = $this->limpiarTexto($row['nombre'] ?? '');
            if ($email === '' || $nombre === '') {
                continue;
            }

            $map[$email] = $nombre;
        }

        return $map;
    }

    private function fetchCanonicalAdminNameByEmail(string $email): ?string
    {
        if ($email === '') {
            return null;
        }

        $row = $this->db->query(
            "SELECT COUNT(*) AS total,
                    MAX(CASE WHEN nombre IS NOT NULL AND TRIM(nombre) != '' THEN TRIM(nombre) ELSE '' END) AS nombre
             FROM general_usuarios
             WHERE LOWER(TRIM(email)) = ?",
            [$email],
        )->fetch(PDO::FETCH_ASSOC);

        if ((int) ($row['total'] ?? 0) !== 1) {
            return null;
        }

        $nombre = $this->limpiarTexto($row['nombre'] ?? '');

        return $nombre !== '' ? $nombre : null;
    }

    private function fetchExistingProfessionalsByEmail(string $dbPrefix, array &$summary): array
    {
        $this->consolidateExistingProfessionals($dbPrefix, $summary);

        $tProf = TableResolver::resolveByPrefix($dbPrefix, 'profesionales');
        $projectId = TableResolver::getProjectIdByPrefix($dbPrefix);

        $rows = $this->db->queryWithProject(
            "SELECT id, nombre, email, cargo, activo FROM {$tProf} WHERE project_id = ? ORDER BY id ASC",
            [$projectId],
            $projectId,
        )->fetchAll(PDO::FETCH_ASSOC);

        $map = [];
        foreach ($rows as $row) {
            $email = $this->normalizarEmail($row['email'] ?? '');
            if ($email === '') {
                continue;
            }

            if (isset($map[$email])) {
                $summary['warnings'][] = "La tabla Profesionales ya tiene más de un registro con el correo '{$email}'. La sincronización solo usó el primer registro encontrado.";
                continue;
            }

            $row['id'] = (int) ($row['id'] ?? 0);
            $row['email'] = $email;
            $map[$email] = $row;
        }

        return $map;
    }

    private function consolidateExistingProfessionals(string $dbPrefix, array &$summary): void
    {
        $tProf = TableResolver::resolveByPrefix($dbPrefix, 'profesionales');
        $projectId = TableResolver::getProjectIdByPrefix($dbPrefix);

        $rows = $this->db->queryWithProject(
            "SELECT id, nombre, email, cargo, activo FROM {$tProf} WHERE project_id = ? ORDER BY id ASC",
            [$projectId],
            $projectId,
        )->fetchAll(PDO::FETCH_ASSOC);

        $grouped = [];
        foreach ($rows as $row) {
            $email = $this->normalizarEmail($row['email'] ?? '');
            if ($email === '') {
                continue;
            }

            $row['id'] = (int) ($row['id'] ?? 0);
            $row['email'] = $email;
            $grouped[$email][] = $row;
        }

        foreach ($grouped as $email => $duplicates) {
            if (count($duplicates) <= 1) {
                continue;
            }

            $survivor = $this->chooseSurvivorProfessional($dbPrefix, $duplicates);
            $survivorName = $this->limpiarTexto($survivor['nombre'] ?? '');

            foreach ($duplicates as $candidate) {
                if ((int) $candidate['id'] === (int) $survivor['id']) {
                    continue;
                }

                $candidateName = $this->limpiarTexto($candidate['nombre'] ?? '');
                if ($survivorName === '' && $candidateName !== '') {
                    $this->db->queryWithProject(
                        "UPDATE {$tProf} SET nombre = ? WHERE project_id = ? AND id = ?",
                        [$candidateName, $projectId, $survivor['id']],
                        $projectId,
                    );
                    $survivorName = $candidateName;
                }

                $this->replaceProfessionalDependencies($dbPrefix, $candidateName, $survivorName);
                $this->db->queryWithProject("DELETE FROM {$tProf} WHERE project_id = ? AND id = ?", [$projectId, $candidate['id']], $projectId);
                $summary['deduplicated']++;
            }

            $summary['warnings'][] = "Se consolidaron registros duplicados del correo '{$email}' en Profesionales. Solo quedó un registro operativo.";
        }
    }

    private function chooseSurvivorProfessional(string $dbPrefix, array $duplicates): array
    {
        $scored = [];
        foreach ($duplicates as $row) {
            $row['dependency_count'] = $this->countProfessionalDependencies($dbPrefix, $this->limpiarTexto($row['nombre'] ?? ''));
            $scored[] = $row;
        }

        usort($scored, function (array $a, array $b): int {
            $dependencyDiff = ($b['dependency_count'] ?? 0) <=> ($a['dependency_count'] ?? 0);
            if ($dependencyDiff !== 0) {
                return $dependencyDiff;
            }

            return ((int) $a['id']) <=> ((int) $b['id']);
        });

        return $scored[0];
    }

    private function countProfessionalDependencies(string $dbPrefix, string $nombre): int
    {
        if ($nombre === '') {
            return 0;
        }

        $projectId = TableResolver::getProjectIdByPrefix($dbPrefix);
        $total = 0;
        foreach ($this->getProfessionalDependencyTables($dbPrefix) as $table => $column) {
            $total += (int) $this->db->query(
                "SELECT COUNT(*) FROM {$table} WHERE project_id = ? AND {$column} = ?",
                [$projectId, $nombre],
            )->fetchColumn();
        }

        return $total;
    }

    private function replaceProfessionalDependencies(string $dbPrefix, string $oldName, string $newName): void
    {
        $oldName = $this->limpiarTexto($oldName);
        $newName = $this->limpiarTexto($newName);

        if ($oldName === '' || $newName === '' || $oldName === $newName) {
            return;
        }

        $projectId = TableResolver::getProjectIdByPrefix($dbPrefix);
        foreach ($this->getProfessionalDependencyTables($dbPrefix) as $table => $column) {
            $this->db->query(
                "UPDATE {$table} SET {$column} = ? WHERE project_id = ? AND {$column} = ?",
                [$newName, $projectId, $oldName],
            );
        }
    }

    private function getProfessionalDependencyTables(string $dbPrefix): array
    {
        $tables = [
            TableResolver::resolveByPrefix($dbPrefix, 'programa') => 'Responsable_AIA',
            TableResolver::resolveByPrefix($dbPrefix, 'programacion_semanal') => 'Responsable_AIA',
            TableResolver::resolveByPrefix($dbPrefix, 'programa_consolidado') => 'Responsable_AIA',
            TableResolver::resolveByPrefix($dbPrefix, 'cip') => 'profesional',
            TableResolver::resolveByPrefix($dbPrefix, 'indicadores_generales') => 'subcontratista_profesional',
        ];

        $existingTables = [];
        foreach ($tables as $table => $column) {
            if ($this->db->tableExists($table)) {
                $existingTables[$table] = $column;
            }
        }

        return $existingTables;
    }

    private function limpiarTexto($valor): string
    {
        return preg_replace('/\s+/u', ' ', trim((string) $valor)) ?? '';
    }

    private function normalizarEmail($valor): string
    {
        $email = trim((string) $valor);
        return function_exists('mb_strtolower') ? mb_strtolower($email, 'UTF-8') : strtolower($email);
    }

    private function resolveLockInfo(string $email, array $adminEmailStats, array $currentMemberEmails): array
    {
        $isAdminManaged = ($email !== '' && isset($adminEmailStats[$email]));
        $isCurrentMember = ($email !== '' && isset($currentMemberEmails[$email]));
        $isAdminDuplicate = ($email !== '' && (($adminEmailStats[$email] ?? 0) > 1));

        if ($email === '') {
            return [
                'is_admin_managed' => false,
                'is_current_member' => false,
                'is_blocked' => false,
                'block_reason' => null,
            ];
        }

        if ($isAdminDuplicate) {
            return [
                'is_admin_managed' => true,
                'is_current_member' => $isCurrentMember,
                'is_blocked' => true,
                'block_reason' => 'Correo duplicado en Admin. Resuelve el conflicto antes de usar este profesional.',
            ];
        }

        if ($isAdminManaged && !$isCurrentMember) {
            return [
                'is_admin_managed' => true,
                'is_current_member' => false,
                'is_blocked' => true,
                'block_reason' => 'Usuario retirado del proyecto en Admin. El profesional quedó bloqueado en este proyecto.',
            ];
        }

        return [
            'is_admin_managed' => $isAdminManaged,
            'is_current_member' => $isCurrentMember,
            'is_blocked' => false,
            'block_reason' => null,
        ];
    }

    private function buildMemberWarning(string $memberLabel, string $role, string $reason): string
    {
        $label = trim($memberLabel);
        if ($label === '') {
            $label = 'Miembro sin identificar';
        }

        return "El miembro '{$label}' con rol {$role} {$reason}";
    }
}
