<?php

declare(strict_types=1);

namespace App\Security\DataScope;

use LogicException;

final readonly class SystemScopeRunner
{
    public function __construct(private DataScopeContext $context)
    {
    }

    public function run(string $reason, callable $operation): mixed
    {
        if ($this->context->current() !== null) {
            throw new LogicException('SystemScopeRunner exige un contexto de datos vacío.');
        }

        $this->context->bind(SystemScope::forMaintenance($reason));
        try {
            return $operation();
        } finally {
            $this->context->clear();
        }
    }
}
