<?php

declare(strict_types=1);

namespace App\Services\Lps;

use PDO;
use TableResolver;

/**
 * Implementación legacy de {@see LpsAlertRepository} sobre `lps_escalamientos`, scoped por
 * proyecto vía `queryWithProject`.
 */
final class LpsLegacyAlertRepository implements LpsAlertRepository
{
    public function __construct(private readonly \Database $db, private readonly string $dbPrefix)
    {
    }

    public function findById(int $projectId, int $alertId): ?LpsAlertRecord
    {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $this->dbPrefix)) {
            return null;
        }

        $table = TableResolver::resolveByPrefix($this->dbPrefix, 'lps_escalamientos');
        $row = $this->db->queryWithProject(
            "SELECT id, proyecto_id, unique_id, modulo, semana, nivel_actual, estado
             FROM `{$table}` WHERE proyecto_id = ? AND id = ? LIMIT 1",
            [$projectId, $alertId],
            $projectId,
        )->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return new LpsAlertRecord(
            (int) $row['id'],
            (int) $row['proyecto_id'],
            (int) $row['unique_id'],
            (string) ($row['modulo'] ?: 'PS'),
            (int) $row['semana'],
            (int) $row['nivel_actual'],
            $row['estado'] === 'Activo',
        );
    }
}
