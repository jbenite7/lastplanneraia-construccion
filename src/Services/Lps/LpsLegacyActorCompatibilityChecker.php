<?php

declare(strict_types=1);

namespace App\Services\Lps;

use PDO;
use TableResolver;

/**
 * Comprueba la FK exacta que exige el schema vigente hoy: `profesionales(project_id, id)`
 * (`database/migrations/20260630_global_tables_contract.sql:542-550`). No busca por nombre,
 * correo ni cargo.
 */
final class LpsLegacyActorCompatibilityChecker implements LpsActorCompatibilityChecker
{
    public function __construct(private readonly \Database $db, private readonly string $dbPrefix)
    {
    }

    public function isCompatible(int $projectId, int $userId): bool
    {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $this->dbPrefix)) {
            return false;
        }

        $table = TableResolver::resolveByPrefix($this->dbPrefix, 'profesionales');
        $row = $this->db->queryWithProject(
            "SELECT id FROM `{$table}` WHERE project_id = ? AND id = ? LIMIT 1",
            [$projectId, $userId],
            $projectId,
        )->fetch(PDO::FETCH_ASSOC);

        return (bool) $row;
    }
}
