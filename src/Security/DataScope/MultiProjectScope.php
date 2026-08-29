<?php

declare(strict_types=1);

namespace App\Security\DataScope;

use InvalidArgumentException;

final readonly class MultiProjectScope
{
    /** @var list<int> */
    private array $projectIds;
    private string $user;
    private string $role;
    private string $reason;

    /** @param array<int, int> $projectIds */
    public function __construct(array $projectIds, string $user, string $role, string $reason)
    {
        $user = trim($user);
        $role = trim($role);
        $reason = trim($reason);

        if ($projectIds === []) {
            throw new InvalidArgumentException('MultiProjectScope exige al menos un project_id.');
        }
        foreach ($projectIds as $projectId) {
            if (!is_int($projectId) || $projectId <= 0) {
                throw new InvalidArgumentException('MultiProjectScope exige project_id positivos.');
            }
        }
        if ($user === '') {
            throw new InvalidArgumentException('MultiProjectScope exige un usuario.');
        }
        if ($role === '') {
            throw new InvalidArgumentException('MultiProjectScope exige un rol.');
        }
        if ($reason === '') {
            throw new InvalidArgumentException('MultiProjectScope exige una razón auditable.');
        }

        $projectIds = array_values(array_unique($projectIds, SORT_REGULAR));
        sort($projectIds, SORT_NUMERIC);

        $this->projectIds = $projectIds;
        $this->user = $user;
        $this->role = $role;
        $this->reason = $reason;
    }

    /** @return list<int> */
    public function projectIds(): array
    {
        return $this->projectIds;
    }

    public function allows(int $projectId): bool
    {
        return in_array($projectId, $this->projectIds, true);
    }

    public function reason(): string
    {
        return $this->reason;
    }
}
