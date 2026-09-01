<?php

declare(strict_types=1);

namespace App\Services\Lps;

/**
 * Traduce capacidad RBAC + elegibilidad del actor + estado del target en acciones efectivas
 * (D-T02-03, D-T02-09, ESC-R5/R6/R7). Sólo booleanos y `actorWriteBlock`; ninguna matriz de roles
 * cruza al cliente.
 */
final class LpsActionPolicy
{
    private const MAX_LEVEL = 5;

    /**
     * @return array{read: bool, comment: bool, notifyNext: bool, close: bool, actorWriteBlock: string}
     */
    public function evaluate(LpsTarget $target, bool $canRead, bool $canEdit, string $actorEligibility): array
    {
        ['terminal' => $terminal, 'staleAlert' => $staleAlert, 'activeAlert' => $activeAlert] = $this->targetState($target);

        $actorWriteBlock = match (true) {
            !$canEdit => 'forbidden',
            $actorEligibility === LpsActorEligibility::FORBIDDEN => 'forbidden',
            $actorEligibility === LpsActorEligibility::PROFILE_REQUIRED => 'profile_required',
            default => 'none',
        };

        return [
            'read' => $canRead,
            // Comentar/cerrar están ligados a la FK de actor: PROFILE_REQUIRED los bloquea a los dos.
            'comment' => $canEdit && $actorWriteBlock === 'none',
            // Avisar/registrar no persiste el actor legacy: sólo lo bloquean RBAC y la jerarquía
            // terminal o una alerta ya cerrada (stale), nunca la elegibilidad del actor.
            'notifyNext' => $canEdit && !($terminal || $staleAlert),
            'close' => $canEdit && $actorWriteBlock === 'none' && $activeAlert,
            'actorWriteBlock' => $actorWriteBlock,
        ];
    }

    /**
     * Distingue POR QUÉ `notifyNext` salió en `false`, para que el llamador (controlador) pueda
     * elegir el {@see LpsApiError} correcto sin recalcular ni adivinar por exclusión (hallazgo de
     * revisión de la Tarea 6): `forbidden` es RBAC, `stale` es alerta ya cerrada, `terminal` es
     * nivel 5 sin superior al que escalar. Null si `notifyNext` en realidad es `true` — llamarlo
     * en ese caso es un error del llamador, no un estado válido de negocio.
     *
     * @return 'forbidden'|'stale'|'terminal'|null
     */
    public function notifyNextBlockReason(LpsTarget $target, bool $canEdit): ?string
    {
        if (!$canEdit) {
            return 'forbidden';
        }

        ['terminal' => $terminal, 'staleAlert' => $staleAlert] = $this->targetState($target);

        return match (true) {
            $staleAlert => 'stale',
            $terminal => 'terminal',
            default => null,
        };
    }

    /** @return array{terminal: bool, staleAlert: bool, activeAlert: bool} */
    private function targetState(LpsTarget $target): array
    {
        $isAlert = $target->isAlert();

        return [
            'terminal' => $isAlert && $target->alertLevel !== null && $target->alertLevel >= self::MAX_LEVEL,
            'staleAlert' => $isAlert && !$target->alertActive,
            'activeAlert' => $isAlert && $target->alertActive,
        ];
    }
}
