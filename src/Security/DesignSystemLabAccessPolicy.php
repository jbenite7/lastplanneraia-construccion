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
            // The laboratory is global, so its capability must not depend on
            // the project that a user happened to select most recently.
            //
            // No catch here on purpose. Until 2026-09-02 a \Throwable became DEFAULT_ROLE, which
            // turns any resolution failure into a silent 403 — indistinguishable from a genuine
            // denial. That is exactly how the ProjectSqlGuard regression stayed hidden for four
            // days while it took the CI visual lane down. A broken role lookup must fail loudly.
            return (new RbacService())->resolveRoleForUser($username);
        }

        return strtoupper(trim((string) (
            $session['permiso']
            ?? ($session['admin_user']['permiso'] ?? RbacCatalog::DEFAULT_ROLE)
        )));
    }
}
