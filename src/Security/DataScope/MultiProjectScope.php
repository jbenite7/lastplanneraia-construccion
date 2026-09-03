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

    /**
     * El tipo de valor se declara `mixed` a propósito, no por dejadez: esta clase es una
     * frontera de autorización y sus ids llegan de sesión y de query string. Con
     * `array<int, int>` el `is_int()` de abajo quedaba muerto según el propio contrato
     * —PHPStan lo reportaba como `function.alreadyNarrowedType`— y la salida barata era
     * quitar la comprobación. Se hizo al revés: el contrato dice la verdad (entra
     * cualquier cosa), la comprobación queda viva y la lista validada se construye
     * explícitamente, así que `list<int>` se sostiene por construcción y no por promesa.
     *
     * @param array<int, mixed> $projectIds
     */
    public function __construct(array $projectIds, string $user, string $role, string $reason)
    {
        $user = trim($user);
        $role = trim($role);
        $reason = trim($reason);

        if ($projectIds === []) {
            throw new InvalidArgumentException('MultiProjectScope exige al menos un project_id.');
        }
        $validados = [];
        foreach ($projectIds as $projectId) {
            if (!is_int($projectId) || $projectId <= 0) {
                throw new InvalidArgumentException('MultiProjectScope exige project_id positivos.');
            }
            $validados[] = $projectId;
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

        $validados = array_values(array_unique($validados, SORT_REGULAR));
        sort($validados, SORT_NUMERIC);

        $this->projectIds = $validados;
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

    /**
     * `user()` y `role()` existen por simetría con `ProjectScope`, que las tiene desde
     * siempre. Aquí faltaban, así que las dos propiedades quedaban validadas y guardadas
     * sin que nadie pudiera leerlas —`property.onlyWritten` en PHPStan—. Se exponen en vez
     * de borrarlas porque son el rastro auditable del alcance: quién pidió estas obras y
     * con qué rol, junto a `reason()`.
     *
     * OJO, no sustituyen a `BiProjectScope::reportRole()`: ese recalcula el rol sobre los
     * ids YA deduplicados, y la deduplicación puede bajar el conteo a uno y cambiar la
     * respuesta de `MULTI` al rol real. Son dos valores distintos por diseño.
     */
    public function user(): string
    {
        return $this->user;
    }

    public function role(): string
    {
        return $this->role;
    }

    public function reason(): string
    {
        return $this->reason;
    }
}
