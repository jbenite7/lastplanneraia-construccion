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
        $canManageWeeks = in_array($role, ['A', 'D', 'OT', 'R', 'DCV']);
        $canEditGeneralProgram = in_array($role, ['A', 'D', 'R', 'DCV']);
        $canEditWeeklyProgram = in_array($role, ['A', 'D', 'R', 'S', 'G', 'SG']);
        $canEditMediumTerm = in_array($role, ['A', 'D', 'R', 'DCV']);
        $canManageContracts = in_array($role, ['A', 'D', 'OT']);

        return [
            'isSystemAdmin' => $isSystemAdmin,
            'canManageWeeks' => $canManageWeeks,
            'canDeleteRows' => in_array($role, ['A', 'D']),
            'canEditGeneralProgram' => $canEditGeneralProgram,
            'canManageGeneralProgram' => $canEditGeneralProgram,
            'canEditPastGeneralProgram' => in_array($role, ['A', 'D']),
            'canEditWeeklyProgram' => $canEditWeeklyProgram,
            'canManageWeeklyProgram' => $canEditWeeklyProgram,
            'canEditMediumTerm' => $canEditMediumTerm,
            'canManageMediumTermProgram' => $canEditMediumTerm,
            'canEditConstraints' => in_array($role, ['A', 'D', 'R', 'DCV', 'S', 'G', 'SG', 'OT']),
            'canEditFinancial' => in_array($role, ['A', 'D', 'OT']),
            'canEditSST' => in_array($role, ['A', 'S', 'SG']),
            'canEditAmbiental' => in_array($role, ['A', 'G', 'SG']),
            'canManageContracts' => $canManageContracts,
            'canAutoDefineContracts' => $canManageContracts,
            'canManagePdC' => $canManageContracts,
            'canSeeReports' => true,
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
