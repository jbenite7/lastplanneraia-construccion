<?php

declare(strict_types=1);

namespace App\Services\Lps;

use PDO;
use TableResolver;
use Throwable;

/**
 * Implementación legacy de {@see LpsThreadRepository} sobre `lps_drawer_comentarios`, scoped por
 * proyecto vía `queryWithProject`. A diferencia de `LpsService::getActivityComments()` (que
 * seleccionaba `c.*` y filtraba sólo por `unique_id`/`semana`), esta versión: (a) enumera
 * columnas explícitas — nunca expone `project_id`/`proyecto_id`/`consecutivo_en_programa` a un
 * presenter, y (b) añade `c.project_id = ?` al WHERE, porque `unique_id`+`semana` no son únicos
 * entre proyectos en una tabla global y el filtro anterior dependía sólo del guard de
 * `queryWithProject`, no de la propia consulta.
 */
final class LpsLegacyThreadRepository implements LpsThreadRepository
{
    public function __construct(private readonly \Database $db, private readonly string $dbPrefix)
    {
    }

    public function findByTarget(int $projectId, int $activityId, int $week, ?int $escalamientoId): array
    {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $this->dbPrefix)) {
            return [];
        }

        $table = TableResolver::resolveByPrefix($this->dbPrefix, 'lps_drawer_comentarios');
        $params = [$projectId, $activityId, $week];
        $sql = "SELECT c.id, c.parent_id, c.comentario, c.created_at, c.usuario_id,
                       u.nombre AS autor_nombre, u.cargo AS autor_cargo, c.menciones
                FROM `{$table}` c
                LEFT JOIN `general_usuarios` u ON c.usuario_id = u.Id
                WHERE c.project_id = ? AND c.unique_id = ? AND c.semana = ?";

        if ($escalamientoId !== null) {
            $sql .= ' AND (c.escalamiento_id = ? OR c.escalamiento_id IS NULL)';
            $params[] = $escalamientoId;
        }

        $sql .= ' ORDER BY c.created_at ASC';

        try {
            $rows = $this->db->queryWithProject($sql, $params, $projectId)->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            error_log('LpsLegacyThreadRepository::findByTarget — ' . $e->getMessage());

            return [];
        }

        return array_map([$this, 'hydrate'], $rows);
    }

    public function findRootById(int $projectId, int $activityId, int $week, int $commentId): ?LpsThreadCommentRecord
    {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $this->dbPrefix)) {
            return null;
        }

        $table = TableResolver::resolveByPrefix($this->dbPrefix, 'lps_drawer_comentarios');

        try {
            $row = $this->db->queryWithProject(
                "SELECT c.id, c.parent_id, c.comentario, c.created_at, c.usuario_id,
                        u.nombre AS autor_nombre, u.cargo AS autor_cargo, c.menciones
                 FROM `{$table}` c
                 LEFT JOIN `general_usuarios` u ON c.usuario_id = u.Id
                 WHERE c.project_id = ? AND c.unique_id = ? AND c.semana = ? AND c.id = ? AND c.parent_id IS NULL
                 LIMIT 1",
                [$projectId, $activityId, $week, $commentId],
                $projectId,
            )->fetch(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            error_log('LpsLegacyThreadRepository::findRootById — ' . $e->getMessage());

            return null;
        }

        return $row ? $this->hydrate($row) : null;
    }

    public function insert(
        int $projectId,
        int $activityId,
        int $week,
        int $authorUserId,
        string $text,
        ?int $parentId,
        ?int $escalamientoId,
        ?array $mentions,
    ): int {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $this->dbPrefix)) {
            return 0;
        }

        try {
            $table = TableResolver::resolveByPrefix($this->dbPrefix, 'lps_drawer_comentarios');
            $mencionesJson = $mentions !== null ? json_encode($mentions) : null;

            $sql = "INSERT INTO `{$table}`
                      (proyecto_id, unique_id, consecutivo_en_programa, semana, usuario_id, comentario, parent_id, escalamiento_id, menciones)
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $params = [
                $projectId,
                $activityId,
                $activityId,
                $week,
                $authorUserId,
                $text,
                $parentId,
                $escalamientoId,
                $mencionesJson,
            ];
            [$sql, $params] = $this->db->insertProjectId($sql, $projectId, $params);
            $this->db->query($sql, $params);

            return (int) $this->db->lastInsertId();
        } catch (Throwable $e) {
            error_log('LpsLegacyThreadRepository::insert — ' . $e->getMessage());

            return 0;
        }
    }

    /** @param array<string, mixed> $row */
    private function hydrate(array $row): LpsThreadCommentRecord
    {
        $mentions = null;
        if (!empty($row['menciones'])) {
            $decoded = json_decode((string) $row['menciones'], true);
            if (is_array($decoded) && isset($decoded['roles']) && is_array($decoded['roles'])) {
                $mentions = ['roles' => array_values(array_map('strval', $decoded['roles']))];
            }
        }

        return new LpsThreadCommentRecord(
            (int) $row['id'],
            $row['parent_id'] !== null ? (int) $row['parent_id'] : null,
            (string) $row['comentario'],
            (string) $row['created_at'],
            (int) $row['usuario_id'],
            $row['autor_nombre'] !== null ? (string) $row['autor_nombre'] : null,
            $row['autor_cargo'] !== null ? (string) $row['autor_cargo'] : null,
            $mentions,
        );
    }
}
