<?php

namespace App\Security;

use TableResolver;

final class LpsWeekEditPolicy
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function allows(string $dbPrefix, int $week, bool $qualification = false): bool
    {
        if ($dbPrefix === '' || $week <= 0) {
            return false;
        }

        $projectId = TableResolver::getProjectIdByPrefix($dbPrefix);
        if (!$projectId) {
            // Si no se puede resolver el proyecto, deniega — misma decisión que unifica
            // la Task 4 para todo el candado de semana: un candado que no sabe, cierra.
            return false;
        }
        $weeksTable = TableResolver::resolveByPrefix($dbPrefix, 'semanas_activas');
        $maxWeek = (int) $this->db->queryWithProject(
            "SELECT COALESCE(MAX(Semana), 0) FROM {$weeksTable} WHERE project_id = ?",
            [$projectId],
            $projectId,
        )->fetchColumn();
        $role = (new RbacService($this->db))->resolveCurrentRole();
        if (RbacCatalog::canEditLpsWeek($role, $week, $maxWeek)) {
            return true;
        }
        if (!$qualification || !RbacCatalog::canQualifyWeeklyCommitment($role)) {
            return false;
        }

        $confirmed = $this->db->queryWithProject(
            "SELECT Semanal_Confirmada FROM {$weeksTable} WHERE project_id = ? AND Semana = ?",
            [$projectId, $week],
            $projectId,
        )->fetchColumn();

        return (int) $confirmed === 1;
    }
}
