<?php

declare(strict_types=1);

namespace App\Services\Lps;

use PDO;
use TableResolver;

/**
 * Adapter temporal de convivencia para el módulo PG (Programa General). `programa_consolidado`
 * no distingue PG de PI con una columna propia: las dos vistas leen la misma tabla, sólo cambia
 * el recorte de semanas que arma cada módulo. Mientras eso no tenga una decisión de producto
 * propia, PG e PI validan idéntico: la actividad (`unique_id`, `Titulo = 0`) existe en el
 * proyecto y existe en la `Semana` que propone el cliente (el servidor la verifica, S05-SOS 1.2).
 */
final class LpsLegacyGeneralActivityAdapter implements LpsActivityTargetAdapter
{
    public function __construct(private readonly \Database $db, private readonly string $dbPrefix)
    {
    }

    public function moduleKey(): string
    {
        return 'PG';
    }

    public function existsInWeek(int $projectId, int $activityId, int $week): bool
    {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $this->dbPrefix)) {
            return false;
        }

        $table = TableResolver::resolveByPrefix($this->dbPrefix, 'programa_consolidado');
        $row = $this->db->queryWithProject(
            "SELECT 1 FROM `{$table}` WHERE project_id = ? AND unique_id = ? AND Semana = ? AND Titulo = 0 LIMIT 1",
            [$projectId, $activityId, $week],
            $projectId,
        )->fetch(PDO::FETCH_ASSOC);

        return $row !== false;
    }
}
