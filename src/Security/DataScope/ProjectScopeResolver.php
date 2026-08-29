<?php

declare(strict_types=1);

namespace App\Security\DataScope;

use App\Security\RbacService;

final class ProjectScopeResolver
{
    /** @var callable(string, int): (?array<string, mixed>) */
    private $lookup;

    public function __construct($source = null, ?RbacService $rbac = null)
    {
        $database = is_callable($source) ? null : ($source ?? \Database::getInstance());
        $this->lookup = is_callable($source)
            ? $source
            : static function (string $user, int $projectId) use ($database): ?array {
                $sql = <<<'SQL'
SELECT p.ID AS project_id, p.Activo, p.Area, p.Acceso, pm.role
FROM project_members pm
INNER JOIN general_usuarios u ON u.id = pm.user_id
INNER JOIN general_proyectos_procesos p ON p.ID = pm.project_id
WHERE u.usuario = ? AND p.ID = ?
  AND p.Activo = 1
  AND p.Area IN ('Construccion', 'Pre-Construccion')
LIMIT 1
SQL;
                $stmt = $database->prepare($sql);
                $stmt->execute([$user, $projectId]);
                $row = $stmt->fetch();

                return is_array($row) ? $row : null;
            };
        $this->rbac = $rbac ?? new RbacService($database);
    }

    private RbacService $rbac;

    /** @param array<string, mixed> $session */
    public function resolve(array $session): ?ProjectScope
    {
        $user = trim((string) ($session['usuario'] ?? ''));
        $projectId = filter_var(
            $session['project_id'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]],
        );
        if ($user === '' || $projectId === false) {
            return null;
        }

        $row = ($this->lookup)($user, $projectId);
        if (
            !is_array($row)
            || (int) ($row['project_id'] ?? 0) !== $projectId
            || (int) ($row['Activo'] ?? 0) !== 1
            || !in_array((string) ($row['Area'] ?? ''), ['Construccion', 'Pre-Construccion'], true)
        ) {
            return null;
        }

        return new ProjectScope(
            $projectId,
            $user,
            $this->rbac->normalizeRole((string) ($row['role'] ?? '')),
        );
    }
}
