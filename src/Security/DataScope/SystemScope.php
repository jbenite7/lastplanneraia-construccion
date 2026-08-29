<?php

declare(strict_types=1);

namespace App\Security\DataScope;

use InvalidArgumentException;

final readonly class SystemScope
{
    private function __construct(private string $reason)
    {
    }

    public static function forMaintenance(string $reason): self
    {
        $reason = trim($reason);
        if ($reason === '') {
            throw new InvalidArgumentException('SystemScope exige una razón auditable.');
        }
        return new self($reason);
    }

    public function reason(): string
    {
        return $this->reason;
    }
}
