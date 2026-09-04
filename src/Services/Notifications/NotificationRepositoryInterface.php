<?php

declare(strict_types=1);

namespace App\Services\Notifications;

/**
 * Frontera inyectable de `NotificationService` hacia `system_notifications` (T02 Tarea 9,
 * AC-137..151). Existe para que la bandeja de identidad (leer no leídas / marcar leída) se
 * pueda probar sin base de datos — nivel `puro` — inyectando un fake, tal como pide el brief
 * ("With fake identity repository"). El resto de `NotificationService` (emit/create/grouping)
 * sigue hablando directo con `Database::getInstance()`: no forma parte del alcance de esta tarea.
 */
interface NotificationRepositoryInterface
{
    /**
     * Filas crudas (snake_case, con `project_id`) de las notificaciones no leídas del usuario,
     * más recientes primero. La proyección hacia el contrato público (quitar `project_id`) es
     * responsabilidad de `NotificationService`, no del repositorio.
     *
     * @return array<int, array<string, mixed>>
     */
    public function findUnreadByUser(string $userId): array;

    /**
     * Marca una notificación como leída bajo el predicado `id AND user_id` — nunca sólo `id`
     * (AC-140). Un id ajeno, inexistente o ya leído no afecta ninguna fila pero de todas formas
     * devuelve `true`: `execute()` sólo informa si el UPDATE se ejecutó sin error, no cuántas filas
     * tocó, y esa es precisamente la propiedad no enumerativa que pide AC-141 — no se cambia aquí.
     */
    public function markAsRead(int $notificationId, string $userId): bool;
}
