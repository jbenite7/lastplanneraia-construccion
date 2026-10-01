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

    public function existsInWeek(int $projectId, int $activityId, int $week): bool
    {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $this->dbPrefix)) {
            return false;
        }

        $table = TableResolver::resolveByPrefix($this->dbPrefix, 'programacion_semanal');
        $row = $this->db->queryWithProject(
            "SELECT 1 FROM `{$table}` WHERE project_id = ? AND unique_id = ? AND Semana = ? LIMIT 1",
            [$projectId, $activityId, $week],
            $projectId,
        )->fetch(PDO::FETCH_ASSOC);

        return $row !== false;
    }
}
