<?php

declare(strict_types=1);

namespace App\Services\Lps;

/**
 * Puerto de escritura de crisis sobre `lps_escalamientos` + banderas `alerta_crisis` en
 * `programa_consolidado`/`programacion_semanal`, scoped por proyecto. Deliberadamente granular
 * (en vez de dos métodos monolíticos "registrar"/"cerrar") para que {@see LpsCrisisService} pueda
 * envolver cada operación en una transacción explícita y para que el test unitario verifique, con
 * un fake espía, el orden exacto de llamadas — sin DDL/DML real (D-T02 restricción global).
 *
 * La implementación legacy consulta vía TableResolver + Database::queryWithProject; los tests
 * unitarios usan fakes en memoria.
 */
interface LpsCrisisRepository
{
    public function beginTransaction(): void;

    public function commit(): void;

    public function rollBack(): void;

    /** Alerta ACTIVA para el target (proyecto+actividad+semana), o null si no hay ninguna (T02-AC-111). */
    public function findActiveByTarget(int $projectId, int $activityId, int $week): ?LpsAlertRecord;

    /** Inserta una alerta nueva en estado Activo, nivel 1. Devuelve su id (T02-AC-110). */
    public function insertAlert(int $projectId, int $activityId, string $module, int $week, string $trigger): int;

    /** Marca (`true`) o limpia (`false`) `alerta_crisis` en consolidado y semanal para el target. */
    public function setCrisisFlag(int $projectId, int $activityId, int $week, bool $active): void;

    /**
     * Cierra una alerta ACTIVA del proyecto dado (T02-AC-123): `estado='Cerrado'`, justificación y
     * usuario de cierre. Devuelve `false` sin lanzar si la alerta ya no está activa (carrera
     * perdida entre el chequeo del controlador y el cierre) — la ausencia de fila afectada es la
     * señal, nunca una excepción.
     */
    public function closeAlert(int $projectId, int $alertId, int $userId, string $justification): bool;
}
