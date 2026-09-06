<?php

declare(strict_types=1);

namespace App\Services\Lps;

use PDO;
use TableResolver;

/**
 * Adapter temporal de convivencia para el módulo PS (Programación Semanal), sobre
 * `programacion_semanal`.
 */
final class LpsLegacyWeeklyActivityAdapter implements LpsActivityTargetAdapter
{
    public function __construct(private readonly \Database $db, private readonly string $dbPrefix)
    {
    }

    public function moduleKey(): string
    {
        return 'PS';
    }

    public function resolveWeek(int $projectId, int $activityId): ?int
    {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $this->dbPrefix)) {
            return null;
        }

        $table = TableResolver::resolveByPrefix($this->dbPrefix, 'programacion_semanal');
        $row = $this->db->queryWithProject(
            "SELECT Semana FROM `{$table}` WHERE project_id = ? AND unique_id = ? LIMIT 1",
            [$projectId, $activityId],
            $projectId,
        )->fetch(PDO::FETCH_ASSOC);

        return $row ? (int) $row['Semana'] : null;
    }
}
