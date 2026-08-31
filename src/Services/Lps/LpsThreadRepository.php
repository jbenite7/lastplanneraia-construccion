<?php

declare(strict_types=1);

namespace App\Services\Lps;

/**
 * Puerto de lectura/escritura del hilo (`lps_drawer_comentarios`), scoped por proyecto y por
 * target resuelto (nunca por `consecutivo`/`semana` sueltos del cliente). La implementación
 * legacy consulta vía TableResolver + Database::queryWithProject; los tests unitarios usan fakes.
 */
interface LpsThreadRepository
{
    /**
     * Fila plana de raíces y respuestas del target, ordenada por `created_at` ascendente
     * (T02-AC-084/085). Construir el árbol de un solo nivel es responsabilidad del presenter,
     * no del repositorio.
     *
     * @return list<LpsThreadCommentRecord>
     */
    public function findByTarget(int $projectId, int $activityId, int $week, ?int $escalamientoId): array;

    /**
     * Resuelve un comentario sólo si es una RAÍZ (`parent_id IS NULL`) del mismo target
     * (mismo proyecto, actividad y semana). Cubre T02-AC-087/088/089 en una sola consulta: un
     * `parent_id` ajeno a proyecto/actividad/semana, o que apunte a una respuesta en vez de una
     * raíz, resuelve `null` por igual.
     */
    public function findRootById(int $projectId, int $activityId, int $week, int $commentId): ?LpsThreadCommentRecord;

    /**
     * Inserta un comentario/respuesta y devuelve su ID nuevo, o 0 si la inserción falló.
     *
     * @param array{roles: list<string>}|null $mentions
     */
    public function insert(
        int $projectId,
        int $activityId,
        int $week,
        int $authorUserId,
        string $text,
        ?int $parentId,
        ?int $escalamientoId,
        ?array $mentions,
    ): int;
}
