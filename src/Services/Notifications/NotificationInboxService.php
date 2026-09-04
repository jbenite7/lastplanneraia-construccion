<?php

declare(strict_types=1);

namespace App\Services\Notifications;

/**
 * Bandeja de identidad del usuario autenticado (T02 Tarea 9, AC-137..143).
 *
 * **Fix round 1 (hallazgo de revisión):** esta clase vivía como dos métodos añadidos a
 * `App\Services\NotificationService`, con un `?NotificationRepositoryInterface $repository`
 * inyectable en su constructor. Eso NO aislaba nada: `NotificationService::__construct()` seguía
 * resolviendo `$this->db = Database::getInstance()` **incondicionalmente** (heredado del resto de
 * la clase — `emit()`/`create()`/`getUsersByRoleForProject()` siguen operando directo sobre
 * `$this->db`), y `Database::__construct()` (`src/Core/Database.php:60-82`) abre una conexión PDO
 * real de inmediato, con `die()` si falla. `NotificationInboxServiceTest.php` (`#[Group('puro')]`)
 * pasaba entonces sólo porque la base de datos resultaba estar disponible en el entorno, no
 * porque el servicio estuviera aislado — exactamente lo que ese grupo promete que NO hace falta.
 *
 * Esta clase, separada, sólo conoce `NotificationRepositoryInterface` — cero referencias a
 * `Database`, mismo patrón que `LpsThreadService` (`src/Services/Lps/LpsThreadService.php:17`,
 * `__construct(private readonly LpsThreadRepository $repository)`). `NotificationService` sigue
 * existiendo tal como estaba antes de la Tarea 9 (`emit`/`create`/`emitToRoles`/
 * `getUsersByRoleForProject`/`findUsernameByName`, todos fuera del alcance de T02-AC-137..152) —
 * no comparte constructor ni estado con esta clase.
 */
final class NotificationInboxService
{
    public function __construct(private readonly NotificationRepositoryInterface $repository)
    {
    }

    /**
     * Nunca expone `project_id`/db-prefix a React (AC-142, D-T02-12: es compatibilidad de
     * agrupación interna, no algo que el cliente deba ver) — se descarta aquí, después de leer
     * del repositorio.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getUnreadByUser(string $userId): array
    {
        $rows = $this->repository->findUnreadByUser($userId);

        return array_map(static function (array $row): array {
            unset($row['project_id']);
            return $row;
        }, $rows);
    }

    /**
     * T02-AC-140: sólo acepta un ID positivo y el predicado siempre es el usuario de sesión —
     * nunca uno declarado por el cliente. Un id <= 0 se rechaza sin tocar el repositorio (defensa
     * en profundidad: `NotificationController` ya valida la forma antes de llegar aquí, pero el
     * servicio no confía ciegamente en su único llamador). AC-141 (no enumerativo) lo sigue
     * garantizando el repositorio: `markAsRead()` no distingue "0 filas" de "1 fila".
     */
    public function markAsRead(int $notificationId, string $userId): bool
    {
        if ($notificationId <= 0) {
            return false;
        }

        return $this->repository->markAsRead($notificationId, $userId);
    }
}
