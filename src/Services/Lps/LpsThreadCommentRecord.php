<?php

declare(strict_types=1);

namespace App\Services\Lps;

/**
 * Snapshot inmutable de una fila de `lps_drawer_comentarios`, ya scoped por proyecto/target
 * (T02-AC-080..089). Es lo único que {@see LpsThreadService} y {@see LpsThreadPresenter}
 * consumen: ningún llamador vuelve a tocar el repositorio para pintar el hilo.
 */
final readonly class LpsThreadCommentRecord
{
    /** @param array{roles: list<string>}|null $mentions */
    public function __construct(
        public int $id,
        public ?int $parentId,
        public string $text,
        public string $createdAt,
        public int $authorUserId,
        public ?string $authorName,
        public ?string $authorRole,
        public ?array $mentions,
    ) {
    }

    public function isRoot(): bool
    {
        return $this->parentId === null;
    }
}
