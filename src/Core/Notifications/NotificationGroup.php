<?php

namespace App\Core\Notifications;

/**
 * Define los grupos de notificaciones.
 * Un grupo agrupa tipos relacionados para consolidar
 * alertas similares en una sola notificación.
 */
class NotificationGroup
{
    public const PI_RESTRICTION_CHANGE = 'pi_restriction_change';
    public const PI_SHARED_CONSTRAINT  = 'pi_shared_constraint';
    public const PI_ASSIGNMENT         = 'pi_assignment';
    public const PI_STATE_ALERT        = 'pi_state_alert';
    public const REPORT                = 'report';
}
