<?php

declare(strict_types=1);

namespace App\Services\Lps;

use PDO;
use TableResolver;

/**
 * Implementación legacy de {@see LpsCrisisRepository} sobre `lps_escalamientos` +
 * `programa_consolidado`/`programacion_semanal`, scoped por proyecto vía `queryWithProject`. Los
 * mismos cuatro pasos que antes vivían inline en `LpsService::registerCrisisAlert()`/
 * `closeCrisisAlert()`, ahora expuestos uno por uno para que {@see LpsCrisisService} controle la
 * transacción explícitamente.
 */
final class LpsLegacyCrisisRepository implements LpsCrisisRepository
{
    public function __construct(private readonly \Database $db, private readonly string $dbPrefix)
    {
    }

    public function beginTransaction(): void
    {
        $this->db->beginTransaction();
    }

    public function commit(): void
    {
        $this->db->commit();
    }

    public function rollBack(): void
    {
        if ($this->db->inTransaction()) {
            $this->db->rollBack();
        }
    }

    public function findActiveByTarget(int $projectId, int $activityId, int $week): ?LpsAlertRecord
    {
        if (!$this->validPrefix()) {
            return null;
        }

        $table = TableResolver::resolveByPrefix($this->dbPrefix, 'lps_escalamientos');
        $row = $this->db->queryWithProject(
            "SELECT id, proyecto_id, unique_id, modulo, semana, nivel_actual, estado
             FROM `{$table}` WHERE proyecto_id = ? AND unique_id = ? AND semana = ? AND estado = 'Activo' LIMIT 1",
            [$projectId, $activityId, $week],
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

    public function insertAlert(int $projectId, int $activityId, string $module, int $week, string $trigger): int
    {
        if (!$this->validPrefix()) {
            return 0;
        }

        $table = TableResolver::resolveByPrefix($this->dbPrefix, 'lps_escalamientos');
        $sql = "INSERT INTO `{$table}`
                 (proyecto_id, semana, unique_id, consecutivo_en_programa, modulo, trigger_origen, nivel_actual, estado)
                 VALUES (?, ?, ?, ?, ?, ?, 1, 'Activo')";
        [$sql, $params] = $this->db->insertProjectId($sql, $projectId, [$projectId, $week, $activityId, $activityId, $module, $trigger]);
        $this->db->query($sql, $params);

        return (int) $this->db->lastInsertId();
    }

    public function setCrisisFlag(int $projectId, int $activityId, int $week, bool $active): void
    {
        if (!$this->validPrefix()) {
            return;
        }

        $flag = $active ? 1 : 0;
        $tProgCons = TableResolver::resolveByPrefix($this->dbPrefix, 'programa_consolidado');
        $tProgSemanal = TableResolver::resolveByPrefix($this->dbPrefix, 'programacion_semanal');

        $this->db->queryWithProject(
            "UPDATE `{$tProgCons}` SET alerta_crisis = ? WHERE unique_id = ? AND Semana = ?",
            [$flag, $activityId, $week],
            $projectId,
        );
        $this->db->queryWithProject(
            "UPDATE `{$tProgSemanal}` SET alerta_crisis = ? WHERE unique_id = ? AND Semana = ?",
            [$flag, $activityId, $week],
            $projectId,
        );
    }

    public function closeAlert(int $projectId, int $alertId, int $userId, string $justification): bool
    {
        if (!$this->validPrefix()) {
            return false;
        }

        $table = TableResolver::resolveByPrefix($this->dbPrefix, 'lps_escalamientos');
        $stmt = $this->db->queryWithProject(
            "UPDATE `{$table}`
             SET estado = 'Cerrado', fecha_cierre = CURRENT_TIMESTAMP, usuario_cierre_id = ?, justificacion_cierre = ?
             WHERE id = ? AND proyecto_id = ? AND estado = 'Activo'",
            [$userId, $justification, $alertId, $projectId],
            $projectId,
        );

        return $stmt->rowCount() > 0;
    }

    private function validPrefix(): bool
    {
        return (bool) preg_match('/^[a-zA-Z0-9_]+$/', $this->dbPrefix);
    }
}
