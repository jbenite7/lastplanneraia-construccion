<?php

declare(strict_types=1);

namespace App\Services\Lps;

use App\Security\DataScope\ProjectScope;

/**
 * Resuelve un {@see LpsTarget} server-authoritative a partir de un {@see LpsTargetRequest}
 * (T02-AC-011..020). El proyecto siempre sale de `ProjectScope`; la semana siempre sale del
 * adapter de módulo o de la alerta persistida, nunca del cliente.
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

        if ($isLegacy) {
            [$module, $week] = $this->resolveLegacyModule($activityId);
        } else {
            if (!in_array($module, self::MODULES, true)) {
                throw new LpsTargetException(LpsApiError::validationFailed([
                    'modulo' => 'Debe ser PG, PI o PS.',
                ]));
            }

            $adapter = $this->activityAdapters[$module] ?? null;
            if ($adapter === null) {
                throw new LpsTargetException(LpsApiError::serviceUnavailable());
            }

            $week = $adapter->resolveWeek($this->scope->projectId(), $activityId);
            if ($week === null) {
                throw new LpsTargetException(LpsApiError::targetNotFound());
            }
        }

        $escalamientoId = $this->resolveLegacyEscalamiento($request, $activityId, $week);

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
     * (T02-AC-019): `lps_drawer.js` nunca lo envía para comentarios. Cero es una semana válida
     * (Pre-Construcción), así que la señal de "no existe" es `null`, no `0`.
     *
     * @return array{0: string, 1: int}
     */
    private function resolveLegacyModule(int $activityId): array
    {
        foreach (self::MODULES as $module) {
            $adapter = $this->activityAdapters[$module] ?? null;
            if ($adapter === null) {
                continue;
            }

            $week = $adapter->resolveWeek($this->scope->projectId(), $activityId);
            if ($week !== null) {
                return [$module, $week];
            }
        }

        throw new LpsTargetException(LpsApiError::targetNotFound());
    }

    private function resolveLegacyEscalamiento(LpsTargetRequest $request, int $activityId, int $week): ?int
    {
        if ($request->escalamientoId === null) {
            return null;
        }

        if ($request->escalamientoId <= 0) {
            throw new LpsTargetException(LpsApiError::validationFailed([
                'escalamiento_id' => 'Debe ser un entero positivo.',
            ]));
        }

        $alert = $this->alerts->findById($this->scope->projectId(), $request->escalamientoId);
        if ($alert === null || $alert->activityId !== $activityId || $alert->week !== $week) {
            throw new LpsTargetException(LpsApiError::targetNotFound());
        }

        return $request->escalamientoId;
    }
}
