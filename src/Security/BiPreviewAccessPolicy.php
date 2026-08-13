<?php

namespace App\Security;

/**
 * El módulo BI (Control Tower) está oculto de la navegación mientras se desarrolla.
 * Sus rutas siguen vivas, pero solo las abre Admin, que entra por URL directa.
 *
 * El rol se resuelve por usuario y no por el proyecto seleccionado, igual que en
 * DesignSystemLabAccessPolicy: la condición es global, no depende de en qué proyecto
 * estuviera el visitante la última vez.
 *
 * Ver docs/superpowers/specs/2026-08-13-ocultar-control-tower-design.md
 */
final class BiPreviewAccessPolicy
{
    /**
     * @param array<string,mixed> $session
     * @param string|null $roleOverride Solo para pruebas: evita tocar la base de datos.
     */
    public static function canOpen(array $session, ?string $roleOverride = null): bool
    {
        $role = $roleOverride === null
            ? self::resolveRole($session)
            : (new RbacService())->normalizeRole($roleOverride);

        return RbacManager::hasCapability($role, RbacCatalog::PERM_INTERNAL_BI_PREVIEW);
    }

    /**
     * @param array<string,mixed> $session
     */
    private static function resolveRole(array $session): string
    {
        $username = trim((string) (
            $session['usuario']
            ?? ($session['admin_user']['usuario'] ?? '')
        ));

        if ($username !== '') {
            try {
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
