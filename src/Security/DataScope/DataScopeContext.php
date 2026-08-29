<?php

declare(strict_types=1);

namespace App\Security\DataScope;

use LogicException;

final class DataScopeContext
{
    private ProjectScope|MultiProjectScope|SystemScope|null $scope = null;

    public function bind(ProjectScope|MultiProjectScope|SystemScope $scope): void
    {
        if ($this->scope !== null) {
            throw new LogicException('El alcance ya estaba enlazado; limpia antes de reutilizar el proceso.');
        }
        $this->scope = $scope;
    }

    public function current(): ProjectScope|MultiProjectScope|SystemScope|null
    {
        return $this->scope;
    }

    public function clear(): void
    {
        $this->scope = null;
    }
}
