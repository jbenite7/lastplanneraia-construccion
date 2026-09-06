<?php

declare(strict_types=1);

namespace App\Services\Lps;

use App\Security\RbacCatalog;

/**
 * Orquesta lectura y escritura del hilo sobre un {@see LpsTarget} ya resuelto (D-T02-06/07/09,
 * T02-AC-084..092). No decide capacidad ni elegibilidad de actor — eso ya lo resolvió
 * {@see LpsActionPolicy} antes de llegar aquí; este servicio sólo aplica las reglas propias del
 * hilo: raíz válida, sin reply-a-reply, y menciones normalizadas.
 */
final class LpsThreadService
{
    public function __construct(private readonly LpsThreadRepository $repository)
    {
    }

    /**
     * Fila plana del target (raíces y respuestas mezcladas, orden `created_at` ascendente). El
     * presenter arma el árbol de un solo nivel; el servicio no decide forma de salida.
     *
     * @return list<LpsThreadCommentRecord>
     */
    public function read(LpsTarget $target): array
    {
        return $this->repository->findByTarget(
            $target->projectId,
            $target->activityId,
            $target->week,
            $target->escalamientoId,
        );
    }

    /**
     * @param array{roles?: list<string>}|null $rawMentions crudo del cliente, sin validar
     * @return int ID del comentario nuevo (positivo) o 0 si la inserción falló
     *
     * @throws LpsTargetException VALIDATION_FAILED si `parentId` no es una raíz del mismo target
     *                            (T02-AC-087/088/089)
     */
    public function addComment(
        LpsTarget $target,
        int $authorUserId,
        string $text,
        ?int $parentId,
        ?array $rawMentions,
    ): int {
        if ($parentId !== null) {
            $root = $this->repository->findRootById($target->projectId, $target->activityId, $target->week, $parentId);
            if ($root === null) {
                throw new LpsTargetException(LpsApiError::validationFailed([
                    'parent_id' => 'Debe ser la raíz de un comentario del mismo target; no se admiten respuestas a respuestas.',
                ]));
            }
        }

        return $this->repository->insert(
            $target->projectId,
            $target->activityId,
            $target->week,
            $authorUserId,
            $text,
            $parentId,
            $target->escalamientoId,
            self::normalizeMentions($rawMentions),
        );
    }

    /**
     * Reduce las menciones del cliente a `{roles: string[]}` con roles canónicos deduplicados
     * (T02-AC-090). Un token que no sea un rol canónico simplemente se descarta — nunca se
     * convierte en destinatario (T02-AC-091); esto es sólo metadata, nunca dispara
     * `NotificationService` (T02-AC-092, D-T02-07).
     *
     * @param array{roles?: mixed}|null $raw
     * @return array{roles: list<string>}|null
     */
    public static function normalizeMentions(?array $raw): ?array
    {
        if ($raw === null || empty($raw['roles']) || !is_array($raw['roles'])) {
            return null;
        }

        $canonical = RbacCatalog::canonicalRoles();
        $seen = [];
        $roles = [];
        foreach ($raw['roles'] as $role) {
            if (!is_string($role)) {
                continue;
            }
            $role = strtoupper(trim($role));
            if (!in_array($role, $canonical, true) || isset($seen[$role])) {
                continue;
            }
            $seen[$role] = true;
            $roles[] = $role;
        }

        return $roles !== [] ? ['roles' => $roles] : null;
    }
}
