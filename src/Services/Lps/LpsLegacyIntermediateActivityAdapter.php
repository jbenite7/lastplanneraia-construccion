<?php

declare(strict_types=1);

namespace App\Services\Lps;

use PDO;
use TableResolver;

/**
 * Adapter temporal de convivencia para el módulo PI (Programación Intermedia). Ver
 * {@see LpsLegacyGeneralActivityAdapter}: comparte tabla y validación con PG a propósito, porque
 * `programa_consolidado` no tiene una columna que distinga los dos módulos hoy.
 */
final class LpsLegacyIntermediateActivityAdapter implements LpsActivityTargetAdapter
{
    public function __construct(private readonly \Database $db, private readonly string $dbPrefix)
    {
    }

    public function moduleKey(): string
    {
        return 'PI';
    }

    public function resolveWeek(int $projectId, int $activityId): ?int
    {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $this->dbPrefix)) {
            return null;
        }

        $table = TableResolver::resolveByPrefix($this->dbPrefix, 'programa_consolidado');
        $row = $this->db->queryWithProject(
            "SELECT Semana FROM `{$table}` WHERE project_id = ? AND unique_id = ? AND Titulo = 0 LIMIT 1",
            [$projectId, $activityId],
            $projectId,
        )->fetch(PDO::FETCH_ASSOC);

        return $row ? (int) $row['Semana'] : null;
    }
}
