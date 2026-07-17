<?php

declare(strict_types=1);

namespace App\Support;

use App\Security\RbacService;
use Database;
use DomainException;
use PDO;

final class BiProjectScope
{
    private Database $db;
    private RbacService $rbac;
    private ?array $projects = null;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?? Database::getInstance();
        $this->rbac = new RbacService($this->db);
    }

    public function resolve($requestedRaw, array $session): array
    {
        $requested = self::normalizeProjectIds($requestedRaw);
        $allowed = $this->authorizedProjectIds($session);

        if ($requested !== []) {
            if (array_diff($requested, $allowed) !== []) {
                throw new DomainException('No tienes permiso para consultar esos proyectos.');
            }

            return $requested;
        }

        $sessionProjectId = (int) ($session['project_id'] ?? 0);
        if ($sessionProjectId > 0 && in_array($sessionProjectId, $allowed, true)) {
            return [$sessionProjectId];
        }

        if ($allowed !== []) {
            return [$allowed[0]];
        }

        throw new DomainException('No tienes proyectos autorizados para Control Tower.');
    }

    public function authorizedProjects(array $session): array
    {
        if ($this->projects !== null) {
            return $this->projects;
        }

        $usuario = trim((string) ($session['usuario'] ?? ''));
        if ($usuario === '') {
            return $this->projects = [];
        }

        $stmt = $this->db->prepare(
            "SELECT p.ID AS project_id, p.Proyecto_Proceso AS nombre, pm.role
             FROM project_members pm
             INNER JOIN general_usuarios u ON u.id = pm.user_id
             INNER JOIN general_proyectos_procesos p ON p.ID = pm.project_id
             WHERE u.usuario = ?
               AND p.Area IN ('Construccion', 'Pre-Construccion')
               AND p.Activo = 1
               AND (p.Acceso = 1 OR pm.role IN ('A', 'D', 'P'))
             ORDER BY p.Proyecto_Proceso",
        );
        $stmt->execute([$usuario]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $this->projects = [];
        foreach ($rows as $row) {
            if (!$this->rbac->can('lps.indicadores.ver', (string) ($row['role'] ?? ''))) {
                continue;
            }

            unset($row['role']);
            $this->projects[] = $row;
        }

        return $this->projects;
    }

    public static function normalizeProjectIds($raw): array
    {
        if (is_array($raw)) {
            $values = $raw;
        } elseif (is_string($raw) && str_contains($raw, ',')) {
            $values = explode(',', $raw);
        } else {
            $values = $raw ? [$raw] : [];
        }

        $normalized = [];
        foreach ($values as $value) {
            $id = (int) $value;
            if ($id > 0) {
                $normalized[$id] = $id;
            }
        }

        return array_values($normalized);
    }

    public function hasAnyAccess(array $session): bool
    {
        return $this->authorizedProjects($session) !== [];
    }

    public function canAccessProject(int $projectId, array $session): bool
    {
        if ($projectId <= 0) {
            return false;
        }

        return in_array($projectId, $this->authorizedProjectIds($session), true);
    }

    public function reportRole(array $projectIds, array $session): string
    {
        if (count($projectIds) !== 1) {
            return 'MULTI';
        }

        $usuario = trim((string) ($session['usuario'] ?? ''));
        if ($usuario === '') {
            return $this->rbac->normalizeRole(
                (string) ($session['permiso_canonico'] ?? $session['permiso'] ?? 'V'),
            );
        }

        $stmt = $this->db->prepare(
            "SELECT pm.role
             FROM project_members pm
             INNER JOIN general_usuarios u ON u.id = pm.user_id
             WHERE u.usuario = ? AND pm.project_id = ?
             LIMIT 1",
        );
        $stmt->execute([$usuario, (int) $projectIds[0]]);
        $role = $stmt->fetchColumn();

        return $this->rbac->normalizeRole(is_string($role) ? $role : 'V');
    }

    private function authorizedProjectIds(array $session): array
    {
        return array_values(array_map(
            'intval',
            array_column($this->authorizedProjects($session), 'project_id'),
        ));
    }

}
