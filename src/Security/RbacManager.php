<?php

namespace App\Security;

class RbacManager
{
    /**
     * Traduce el rol legacy a un mapa de capacidades.
     */
    public static function getCapabilities(string $role): array
    {
        $role = strtoupper(trim($role));

        $isSystemAdmin = ($role === 'A');
        $isDirector = ($role === 'D');
        $isResident = ($role === 'R');
        $isSST = ($role === 'S');
        $isAmbiental = ($role === 'G');
        $isSSTAmbiental = ($role === 'SG');
        $isOficinaTecnica = ($role === 'OT');
        $isProfesionalDCV = ($role === 'DCV');
        $isVisualizer = ($role === 'V');
        $isSubcontractor = ($role === 'C');
        // Un solo nombre por capacidad: los pares canEdit*/canManage* eran alias exactos y el
        // vocabulario prometía una distinción «editar» vs «gestionar» que el código nunca hizo
        // (RBAC-A, 2026-08-10). Sobrevive `canManage*` porque es el nombre que consume el runtime.
        return [
            'isSystemAdmin' => $isSystemAdmin,
            RbacCatalog::PERM_INTERNAL_DESIGN_SYSTEM_VIEW => $isSystemAdmin,
            // Ampliado a Director de Obra el 2026-08-20 (decisión de Felipe): el módulo sigue
            // oculto para el resto de roles mientras se desarrolla.
            RbacCatalog::PERM_INTERNAL_BI_PREVIEW => $isSystemAdmin || $isDirector,
            'canManageWeeks' => in_array($role, ['A', 'D', 'OT', 'R', 'DCV']),
            'canManageGeneralProgram' => in_array($role, ['A', 'D', 'R', 'DCV']),
            'canEditPastGeneralProgram' => in_array($role, ['A', 'D']),
            'canManageWeeklyProgram' => in_array($role, ['A', 'D', 'R', 'S', 'G', 'SG']),
            'canManageMediumTermProgram' => in_array($role, ['A', 'D', 'R', 'DCV']),
            'canEditConstraints' => in_array($role, ['A', 'D', 'R', 'DCV', 'S', 'G', 'SG', 'OT']),
            'canEditFinancial' => in_array($role, ['A', 'D', 'OT']),
            'canEditSST' => in_array($role, ['A', 'S', 'SG']),
            'canEditAmbiental' => in_array($role, ['A', 'G', 'SG']),
            'canManagePdC' => in_array($role, ['A', 'D', 'OT', 'R']),
            'isExternal' => ($role === 'C'),
            'isReadOnly' => $isVisualizer || $isSubcontractor,
        ];
    }

    /**
     * Verifica si un rol tiene una capacidad específica.
     */
    public static function hasCapability(string $role, string $capability): bool
    {
        $caps = self::getCapabilities($role);
        return $caps[$capability] ?? false;
    }
}
