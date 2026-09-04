<?php

declare(strict_types=1);

namespace App\Services\Lps;

/**
 * Convierte la fila plana del repositorio en un árbol de un solo nivel (T02-AC-084/085/086):
 * raíces por `created_at` ascendente, cada una con sus respuestas propias en el mismo orden. Una
 * respuesta cuyo `parent_id` no apunta a una raíz presente en el lote (reply-a-reply, o huérfana)
 * se excluye en silencio — nunca se inventa un tercer nivel.
 *
 * Dos vistas del mismo árbol, no dos consultas:
 * - {@see presentLegacy()} conserva `usuario_id` porque `lps_drawer.js` lo lee para detectar
 *   comentarios de "Sistema" (`c.usuario_id === 0`); es la clave leída por el legacy que
 *   D-T02-08 obliga a conservar.
 * - {@see presentReact()} nunca expone `usuario_id`, `project_id`, prefijo ni SQL (T02-AC-083).
 */
final class LpsThreadPresenter
{
    /** @param list<LpsThreadCommentRecord> $flat @return list<array<string, mixed>> */
    public function presentLegacy(array $flat): array
    {
        return $this->buildTree($flat, includeUserId: true);
    }

    /** @param list<LpsThreadCommentRecord> $flat @return list<array<string, mixed>> */
    public function presentReact(array $flat): array
    {
        return $this->buildTree($flat, includeUserId: false);
    }

    /** @param list<LpsThreadCommentRecord> $flat @return list<array<string, mixed>> */
    private function buildTree(array $flat, bool $includeUserId): array
    {
        $roots = [];
        foreach ($flat as $row) {
            if ($row->isRoot()) {
                $entry = $this->item($row, $includeUserId);
                $entry['respuestas'] = [];
                $roots[$row->id] = $entry;
            }
        }

        foreach ($flat as $row) {
            if (!$row->isRoot() && isset($roots[$row->parentId])) {
                $roots[$row->parentId]['respuestas'][] = $this->item($row, $includeUserId);
            }
        }

        return array_values($roots);
    }

    /** @return array<string, mixed> */
    private function item(LpsThreadCommentRecord $row, bool $includeUserId): array
    {
        $item = [
            'id' => $row->id,
            'comentario' => $row->text,
            'created_at' => $row->createdAt,
            'autor_nombre' => $row->authorName,
            'autor_cargo' => $row->authorRole,
            'menciones' => $row->mentions,
        ];

        if ($includeUserId) {
            $item['usuario_id'] = $row->authorUserId;
        }

        return $item;
    }
}
