<?php

namespace App\Core\Notifications;

/**
 * Define los tipos de notificación con sus metadatos:
 * grupo al que pertenecen, título singular/plural
 * y roles destinatarios por defecto.
 */
class NotificationType
{
    // --- Group: pi_restriction_change ---
    public const PI_RESTRICTION_LOWERED = 'pi_restriction_lowered';
    public const PI_RESTRICTION_RAISED  = 'pi_restriction_raised';
    public const PI_FULL_LIBERATION     = 'pi_full_liberation';

    // --- Group: pi_shared_constraint ---
    public const PI_SHARED_APPLIED = 'pi_shared_applied';

    // --- Group: pi_assignment ---
    public const PI_RESPONSIBLE_ASSIGNED   = 'pi_responsible_assigned';
    public const PI_SUBCONTRACTOR_ASSIGNED = 'pi_subcontractor_assigned';

    // --- Group: pi_state_alert ---
    public const PI_BLOCKED_OVERDUE_CRITICAL = 'pi_blocked_overdue_critical';
    public const PI_BLOCKED_OVERDUE          = 'pi_blocked_overdue';
    public const PI_BLOCKED_DUE              = 'pi_blocked_due';
    public const PI_EXECUTION_BLOCKED        = 'pi_execution_blocked';

    // --- Group: report ---
    public const REPORT_RUN_ALL = 'report_run_all';

    // --- Group: ps_auto_program ---
    public const PS_AUTOPROGRAMMED_CNP_RESTRICTION = 'ps_autoprogrammed_cnp_restriction';

    /**
     * Mapa de metadatos por tipo.
     * group: grupo al que pertenece.
     * title: título singular.
     * title_plural: título cuando hay múltiples agrupadas.
     * roles: roles que reciben la notificación.
     */
    private static array $registry = [
        self::PI_RESTRICTION_LOWERED => [
            'group'        => NotificationGroup::PI_RESTRICTION_CHANGE,
            'title'        => 'Restricción bajó de nivel',
            'title_plural' => 'Restricciones bajaron de nivel',
            'roles'        => ['A', 'D', 'R'],
        ],
        self::PI_RESTRICTION_RAISED => [
            'group'        => NotificationGroup::PI_RESTRICTION_CHANGE,
            'title'        => 'Restricción liberada',
            'title_plural' => 'Restricciones liberadas',
            'roles'        => ['A', 'D', 'R'],
        ],
        self::PI_FULL_LIBERATION => [
            'group'        => NotificationGroup::PI_RESTRICTION_CHANGE,
            'title'        => 'Actividad completamente liberada',
            'title_plural' => 'Actividades completamente liberadas',
            'roles'        => ['A', 'D', 'R'],
        ],
        self::PI_SHARED_APPLIED => [
            'group'        => NotificationGroup::PI_SHARED_CONSTRAINT,
            'title'        => 'Restricción compartida aplicada',
            'title_plural' => 'Restricciones compartidas aplicadas',
            'roles'        => ['A', 'D', 'R', 'DCV'],
        ],
        self::PI_RESPONSIBLE_ASSIGNED => [
            'group'        => NotificationGroup::PI_ASSIGNMENT,
            'title'        => 'Asignado como Responsable AIA',
            'title_plural' => 'Nuevas asignaciones como Responsable AIA',
            'roles'        => ['A', 'D'],
        ],
        self::PI_SUBCONTRACTOR_ASSIGNED => [
            'group'        => NotificationGroup::PI_ASSIGNMENT,
            'title'        => 'Subcontratista asignado',
            'title_plural' => 'Subcontratistas asignados',
            'roles'        => ['A', 'D', 'R', 'DCV'],
        ],
        self::PI_BLOCKED_OVERDUE_CRITICAL => [
            'group'        => NotificationGroup::PI_STATE_ALERT,
            'title'        => '🔴 Bloqueada Vencida (Crítica)',
            'title_plural' => '🔴 Actividades bloqueadas vencidas (Críticas)',
            'roles'        => ['A', 'D', 'R', 'DCV'],
        ],
        self::PI_BLOCKED_OVERDUE => [
            'group'        => NotificationGroup::PI_STATE_ALERT,
            'title'        => '⚠️ Bloqueada Vencida',
            'title_plural' => '⚠️ Actividades bloqueadas vencidas',
            'roles'        => ['A', 'D', 'R', 'DCV'],
        ],
        self::PI_BLOCKED_DUE => [
            'group'        => NotificationGroup::PI_STATE_ALERT,
            'title'        => 'Debe Iniciar (Con Restricciones)',
            'title_plural' => 'Actividades deben iniciar con restricciones',
            'roles'        => ['R', 'DCV'],
        ],
        self::PI_EXECUTION_BLOCKED => [
            'group'        => NotificationGroup::PI_STATE_ALERT,
            'title'        => 'En Ejecución con restricciones abiertas',
            'title_plural' => 'Actividades en ejecución con restricciones',
            'roles'        => ['R', 'DCV'],
        ],

        // --- Group: report ---
        self::REPORT_RUN_ALL => [
            'group'        => NotificationGroup::REPORT,
            'title'        => 'Consolidación de reportes',
            'title_plural' => 'Consolidaciones de reportes',
            'roles'        => ['A'],
        ],

        // --- Group: ps_auto_program ---
        self::PS_AUTOPROGRAMMED_CNP_RESTRICTION => [
            'group'        => NotificationGroup::PS_AUTO_PROGRAM,
            'title'        => 'Actividad autodesprogramada por restricciones',
            'title_plural' => 'Actividades autodesprogramadas por restricciones',
            'roles'        => ['A', 'D', 'R', 'DCV'],
        ],
    ];

    /**
     * Obtiene los metadatos de un tipo de notificación.
     */
    public static function getMeta(string $type): ?array
    {
        return self::$registry[$type] ?? null;
    }

    /**
     * Obtiene el grupo al que pertenece un tipo.
     */
    public static function getGroup(string $type): ?string
    {
        return self::$registry[$type]['group'] ?? null;
    }

    /**
     * Obtiene los roles destinatarios de un tipo.
     */
    public static function getRoles(string $type): array
    {
        return self::$registry[$type]['roles'] ?? [];
    }

    /**
     * Obtiene el título apropiado según la cantidad.
     */
    public static function getTitle(string $type, int $count = 1): string
    {
        $meta = self::$registry[$type] ?? null;
        if (!$meta) {
            return 'Notificación';
        }

        return $count > 1
            ? ($meta['title_plural'] ?? $meta['title'])
            : $meta['title'];
    }
}
