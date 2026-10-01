<?php

declare(strict_types=1);

namespace App\Services\Lps;

use App\Security\DataScope\ProjectScope;

/**
 * Resuelve un {@see LpsTarget} server-authoritative a partir de un {@see LpsTargetRequest}
 * (T02-AC-011..020). El proyecto siempre sale de `ProjectScope`; la semana la propone el cliente
 * y el servidor la verifica con el adapter de módulo, o la toma de la alerta persistida cuando llega
 * `escalamiento_id` (S05-SOS 1.2; reemplaza a propósito D-T02-02, «la semana nunca viene del
 * cliente»).
 */
final class LpsTargetResolver
{
    private const MODULES = ['PG', 'PI', 'PS'];

    /** @var array<string, LpsActivityTargetAdapter> */
    private readonly array $activityAdapters;

    /** @param list<LpsActivityTargetAdapter> $activityAdapters */
    public function __construct(
        private readonly ProjectScope $scope,
        private readonly LpsAlertRepository $alerts,
        array $activityAdapters,
    ) {
        $map = [];
        foreach ($activityAdapters as $adapter) {
            $map[$adapter->moduleKey()] = $adapter;
        }
        $this->activityAdapters = $map;
    }

    public function resolve(LpsTargetRequest $request): LpsTarget
    {
        $hasAlert = $request->alertId !== null;
        $hasActivity = $request->activityId !== null;

        if ($hasAlert === $hasActivity) {
            throw new LpsTargetException(LpsApiError::validationFailed([
                'target' => 'alerta_id y consecutivo son variantes mutuamente excluyentes.',
            ]));
        }

        return $hasAlert
            ? $this->resolveAlertTarget($request)
            : $this->resolveActivityTarget($request);
    }

    private function resolveAlertTarget(LpsTargetRequest $request): LpsTarget
    {
        $alertId = $request->alertId;
        if ($alertId === null || $alertId <= 0) {
            throw new LpsTargetException(LpsApiError::validationFailed([
                'alerta_id' => 'Debe ser un entero positivo.',
            ]));
        }

        $alert = $this->alerts->findById($this->scope->projectId(), $alertId);
        if ($alert === null) {
            // Alerta ajena o inexistente responde igual (T02-AC-079): no hay rama distinguible.
            throw new LpsTargetException(LpsApiError::targetNotFound());
        }

        return LpsTarget::forAlert(
            $this->scope->projectId(),
            $alert->id,
            $alert->activityId,
            $alert->module,
            $alert->week,
            $alert->level,
            $alert->active,
        );
    }

    private function resolveActivityTarget(LpsTargetRequest $request): LpsTarget
    {
        $activityId = $request->activityId;
        if ($activityId === null || $activityId <= 0) {
            throw new LpsTargetException(LpsApiError::validationFailed([
                'consecutivo' => 'Debe ser un entero positivo.',
            ]));
        }

        $module = $request->module;
        $isLegacy = $module === null;
        $requestedWeek = $request->week;

        if ($requestedWeek !== null && $requestedWeek < 0) {
            throw new LpsTargetException(LpsApiError::validationFailed([
                'semana' => 'Debe ser un entero mayor o igual a 0.',
            ]));
        }

        if ($module !== null && !in_array($module, self::MODULES, true)) {
            throw new LpsTargetException(LpsApiError::validationFailed([
                'modulo' => 'Debe ser PG, PI o PS.',
            ]));
        }

        $escalamientoId = $this->resolveEscalamientoId($request);

        if ($escalamientoId !== null) {
            // La alerta fija la semana (y el módulo si no vino). Una semana distinta no se reconcilia.
            $alert = $this->alerts->findById($this->scope->projectId(), $escalamientoId);
            if (
                $alert === null
                || $alert->activityId !== $activityId
                || ($requestedWeek !== null && $requestedWeek !== $alert->week)
            ) {
                throw new LpsTargetException(LpsApiError::targetNotFound());
            }

            $week = $alert->week;

            if ($module === null) {
                $module = $alert->module;
            } elseif ($module !== $alert->module) {
                // PG y PI comparten tabla: una alerta de uno se abre desde el otro. Pero un módulo
                // distinto al de la alerta debe tener fila propia en esa semana.
                $adapter = $this->activityAdapters[$module] ?? null;
                if ($adapter === null || !$adapter->existsInWeek($this->scope->projectId(), $activityId, $week)) {
                    throw new LpsTargetException(LpsApiError::targetNotFound());
                }
            }
        } elseif ($requestedWeek === null) {
            throw new LpsTargetException(LpsApiError::validationFailed([
                'semana' => 'Requerida: la semana que se está viendo.',
            ]));
        } else {
            $week = $requestedWeek;

            if ($isLegacy) {
                $module = $this->resolveLegacyModule($activityId, $week);
            } else {
                $adapter = $this->activityAdapters[$module] ?? null;
                if ($adapter === null) {
                    throw new LpsTargetException(LpsApiError::serviceUnavailable());
                }

                if (!$adapter->existsInWeek($this->scope->projectId(), $activityId, $week)) {
                    throw new LpsTargetException(LpsApiError::targetNotFound());
                }
            }
        }

        return LpsTarget::forActivity(
            $this->scope->projectId(),
            $activityId,
            $module,
            $week,
            $escalamientoId,
            $isLegacy,
        );
    }

    /**
     * El camino legacy por `consecutivo` sin `modulo` existe sólo durante convivencia
     * (T02-AC-019): `lps_drawer.js` nunca lo envía para comentarios. Elige el primer módulo
     * (PG, PI, PS) donde la actividad existe en la semana verificada. Cero es una semana válida.
     */
    private function resolveLegacyModule(int $activityId, int $week): string
    {
        foreach (self::MODULES as $module) {
            $adapter = $this->activityAdapters[$module] ?? null;
            if ($adapter === null) {
                continue;
            }

            if ($adapter->existsInWeek($this->scope->projectId(), $activityId, $week)) {
                return $module;
            }
        }

        throw new LpsTargetException(LpsApiError::targetNotFound());
    }

    private function resolveEscalamientoId(LpsTargetRequest $request): ?int
    {
        if ($request->escalamientoId === null) {
            return null;
        }

        if ($request->escalamientoId <= 0) {
            throw new LpsTargetException(LpsApiError::validationFailed([
                'escalamiento_id' => 'Debe ser un entero positivo.',
            ]));
        }

        return $request->escalamientoId;
    }
}
