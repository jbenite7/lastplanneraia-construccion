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
        $isAlert = $target->isAlert();
        $terminal = $isAlert && $target->alertLevel !== null && $target->alertLevel >= self::MAX_LEVEL;
        $activeAlert = $isAlert && $target->alertActive;
        $staleAlert = $isAlert && !$target->alertActive;

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
}
