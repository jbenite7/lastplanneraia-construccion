<?php

namespace App\Security;

use App\Core\AppEnvironment;

final class DesignSystemLabAccessPolicy
{
    public static function status(array $session, ?string $environment = null): int
    {
        $environment = $environment === null
            ? AppEnvironment::current()
            : AppEnvironment::normalize($environment);
        if (!in_array($environment, ['development', 'testing'], true)) {
            return 404;
        }

        $role = self::resolveRole($session);

        return RbacManager::hasCapability($role, RbacCatalog::PERM_INTERNAL_DESIGN_SYSTEM_VIEW)
            ? 200
            : 403;
    }

    private static function resolveRole(array $session): string
    {
        $username = trim((string) (
            $session['usuario']
            ?? ($session['admin_user']['usuario'] ?? '')
        ));

        if ($username !== '') {
            try {
                // The laboratory is global, so its capability must not depend on
                // the project that a user happened to select most recently.
                return (new RbacService())->resolveRoleForUser($username);
            } catch (\Throwable) {
                return RbacCatalog::DEFAULT_ROLE;
            }
        }

        return strtoupper(trim((string) (
            $session['permiso']
            ?? ($session['admin_user']['permiso'] ?? RbacCatalog::DEFAULT_ROLE)
        )));
    }
}
