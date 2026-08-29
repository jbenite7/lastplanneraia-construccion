<?php

declare(strict_types=1);

namespace App\Security\DataScope;

use InvalidArgumentException;

final readonly class ProjectScope
{
    private int $projectId;
    private string $user;
    private string $role;

    public function __construct(int $projectId, string $user, string $role)
    {
        $user = trim($user);
        $role = trim($role);

        if ($projectId <= 0) {
            throw new InvalidArgumentException('ProjectScope exige un project_id positivo.');
        }
        if ($user === '') {
            throw new InvalidArgumentException('ProjectScope exige un usuario.');
        }
        if ($role === '') {
            throw new InvalidArgumentException('ProjectScope exige un rol.');
        }

        $this->projectId = $projectId;
        $this->user = $user;
        $this->role = $role;
    }

    public function projectId(): int
    {
        return $this->projectId;
    }

    public function user(): string
    {
        return $this->user;
    }

    public function role(): string
    {
        return $this->role;
    }
}
