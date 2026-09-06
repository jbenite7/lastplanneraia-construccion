<?php

declare(strict_types=1);

namespace App\Services\Lps;

use Throwable;

/**
 * Orquesta el registro/cierre de crisis sobre un {@see LpsTarget} ya resuelto (T02-AC-105..129).
 * Igual que {@see LpsThreadService}, no decide capacidad ni elegibilidad de actor — eso ya lo
 * resolvió {@see LpsActionPolicy} antes de llegar aquí (`actions.notifyNext`/`actions.close`).
 * Este servicio sólo ejecuta las dos transacciones y nunca escala nivel ni llama
 * `LpsService::escalarAlertasActivas()`: el auto-escalamiento nocturno es un frente aparte
 * (T02-AC-113) y esta clase no tiene ninguna referencia a él.
 */
final class LpsCrisisService
{
    public function __construct(private readonly LpsCrisisRepository $repository)
    {
    }

    /**
     * Registra la alerta del target de forma idempotente (T02-AC-111): si ya hay una activa para
     * proyecto+actividad+semana, no inserta una fila nueva ni toca `nivel_actual` — sólo
     * reafirma las banderas. El éxito significa "alerta/banderas aceptadas", nunca "mensaje
     * entregado" ni "escalamiento jerárquico" (T02-AC-112).
     */
    public function register(LpsTarget $target, string $trigger): LpsCrisisRegisterResult
    {
        $this->repository->beginTransaction();

        try {
            $existing = $this->repository->findActiveByTarget($target->projectId, $target->activityId, $target->week);
            $wasActive = $existing !== null;
            $alertId = $wasActive
                ? $existing->id
                : $this->repository->insertAlert($target->projectId, $target->activityId, $target->module, $target->week, $trigger);

            $this->repository->setCrisisFlag($target->projectId, $target->activityId, $target->week, true);
            $this->repository->commit();

            return new LpsCrisisRegisterResult($alertId, $wasActive);
        } catch (Throwable $e) {
            $this->repository->rollBack();
            throw $e;
        }
    }

    /**
     * Cierra la alerta del target (T02-AC-122..127). El llamador ya validó que el target es una
     * alerta activa del mismo proyecto (`actions.close`); si de todos modos `closeAlert()` no
     * afecta ninguna fila (carrera perdida: la alerta se cerró entre la validación y este punto),
     * las banderas NO se tocan y el método devuelve `false` sin lanzar.
     */
    public function close(LpsTarget $target, int $userId, string $justification): bool
    {
        $this->repository->beginTransaction();

        try {
            $closed = $this->repository->closeAlert($target->projectId, $target->alertId, $userId, trim($justification));
            if ($closed) {
                $this->repository->setCrisisFlag($target->projectId, $target->activityId, $target->week, false);
            }

            $this->repository->commit();

            return $closed;
        } catch (Throwable $e) {
            $this->repository->rollBack();
            throw $e;
        }
    }
}
