<?php

declare(strict_types=1);

namespace App\Security\DataScope;

final readonly class ScopedQuery
{
    /**
     * @param array<mixed> $params
     * @param list<string> $tables
     */
    public function __construct(
        public string $sql,
        public array $params,
        public array $tables,
    ) {
    }
}
