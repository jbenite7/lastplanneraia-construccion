<?php

namespace App\Services;

use App\Core\Notifications\NotificationType;
use Database;
use PDO;

class NotificationService
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Obtener notificaciones no leídas de un usuario.
     */
    public function getUnreadByUser($userId)
    {
        $sql = "SELECT id, type, title, message, item_count,
                       created_at, project_id
                FROM system_notifications
                WHERE user_id = :user_id AND is_read = 0
                ORDER BY created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Marcar una notificación como leída.
     */
    public function markAsRead($notificationId, $userId)
    {
        $sql = "UPDATE system_notifications
                SET is_read = 1
                WHERE id = :id AND user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $notificationId);
        $stmt->bindParam(':user_id', $userId);
        return $stmt->execute();
    }

    /**
     * Insertar una notificación simple (sin agrupación).
     */
    public function create(
        $userId,
        $type,
        $title,
        $message,
        $projectId = null,
    ) {
        $sql = "INSERT INTO system_notifications
                (user_id, type, title, message, project_id)
                VALUES (:user_id, :type, :title,
                        :message, :project_id)";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':type', $type);
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':message', $message);
        $stmt->bindParam(':project_id', $projectId);
        return $stmt->execute();
    }

    /**
     * Emitir notificación con agrupación inteligente.
     *
     * Si ya existe una notificación NO leída del mismo
     * user_id + type + project_id, se actualiza el
     * mensaje, el título (plural) y el item_count.
     * Si no existe, se inserta una nueva.
     */
    public function emit(
        string $userId,
        string $type,
        string $message,
        ?string $projectId = null,
    ): bool {
        $meta = NotificationType::getMeta($type);
        if (!$meta) {
            return false;
        }

        // Buscar notificación agrupable existente
        $existing = $this->findGroupable(
            $userId,
            $type,
            $projectId,
        );

        if ($existing) {
            return $this->updateGrouped(
                (int) $existing['id'],
                $type,
                $message,
                (int) $existing['item_count'],
            );
        }

        $title = NotificationType::getTitle($type, 1);
        return $this->create(
            $userId,
            $type,
            $title,
            $message,
            $projectId,
        );
    }

    /**
     * Emitir notificación a todos los roles definidos
     * para el tipo dado. Requiere lista de usuarios
     * por rol del proyecto.
     *
     * @param array $usersByRole ['A' => ['u1'], 'D' => ['u2'], ...]
     */
    public function emitToRoles(
        string $type,
        string $message,
        array $usersByRole,
        ?string $projectId = null,
    ): int {
        $roles = NotificationType::getRoles($type);
        $notified = [];
        $count = 0;

        foreach ($roles as $role) {
            $users = $usersByRole[$role] ?? [];
            foreach ($users as $userId) {
                $uid = trim((string) $userId);
                if ($uid === '' || isset($notified[$uid])) {
                    continue;
                }
                if ($this->emit($uid, $type, $message, $projectId)) {
                    $count++;
                }
                $notified[$uid] = true;
            }
        }

        return $count;
    }

    /**
     * Obtener mapa [rol => [usernames]] para un proyecto.
     *
     * @param string $dbPrefix Base_de_Datos del proyecto
     * @return array ['A'=>['u1'], 'D'=>['u2'], ...]
     */
    public function getUsersByRoleForProject(
        string $dbPrefix,
    ): array {
        $sql = "SELECT u.usuario, pm.role
                FROM project_members pm
                INNER JOIN general_usuarios u
                    ON u.id = pm.user_id
                INNER JOIN general_proyectos_procesos p
                    ON p.ID = pm.project_id
                WHERE p.Base_de_Datos = :db";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':db', $dbPrefix);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $map = [];
        foreach ($rows as $row) {
            $role = strtoupper(trim($row['role'] ?? ''));
            $user = trim($row['usuario'] ?? '');
            if ($role === '' || $user === '') {
                continue;
            }
            $map[$role][] = $user;
        }

        return $map;
    }

    /**
     * Buscar el username dado un nombre o sufijo parcial
     * (útil para mapear campos como Responsable_AIA).
     */
    public function findUsernameByName(string $name): ?string
    {
        $name = trim($name);
        if ($name === '') {
            return null;
        }

        // Buscar coincidencias que empiecen por el nombre (ej. "Juan Felipe Benitez R.")
        // NOTA: Algunas veces guardan el nombre con la inicial del apellido
        $sql = "SELECT usuario FROM general_usuarios WHERE nombre LIKE :name LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':name', $name . '%');
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row && !empty($row['usuario'])) {
            return $row['usuario'];
        }

        // Si no se encuentra con %, intentar buscar la primera palabra
        $parts = explode(' ', $name);
        if (count($parts) > 1) {
            $firstName = $parts[0] . ' ' . $parts[1]; // Nombre y primer apellido típicamente o 2 nombres
            $sql = "SELECT usuario FROM general_usuarios WHERE nombre LIKE :name LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':name', $firstName . '%');
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row && !empty($row['usuario'])) {
                return $row['usuario'];
            }
        }

        return null;
    }

    // --------------------------------------------------
    // Métodos internos
    // --------------------------------------------------

    /**
     * Buscar notificación no leída agrupable.
     */
    private function findGroupable(
        string $userId,
        string $type,
        ?string $projectId,
    ): ?array {
        if ($projectId !== null) {
            $sql = "SELECT id, item_count
                    FROM system_notifications
                    WHERE user_id = :uid
                      AND type = :type
                      AND project_id = :pid
                      AND is_read = 0
                    LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':uid', $userId);
            $stmt->bindParam(':type', $type);
            $stmt->bindParam(':pid', $projectId);
        } else {
            $sql = "SELECT id, item_count
                    FROM system_notifications
                    WHERE user_id = :uid
                      AND type = :type
                      AND project_id IS NULL
                      AND is_read = 0
                    LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':uid', $userId);
            $stmt->bindParam(':type', $type);
        }

        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Actualizar notificación existente sumando al grupo.
     */
    private function updateGrouped(
        int $id,
        string $type,
        string $newMessage,
        int $currentCount,
    ): bool {
        $nextCount = $currentCount + 1;
        $title = NotificationType::getTitle($type, $nextCount);

        $sql = "UPDATE system_notifications
                SET title = :title,
                    message = :message,
                    item_count = :cnt,
                    created_at = CURRENT_TIMESTAMP
                WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':message', $newMessage);
        $stmt->bindParam(':cnt', $nextCount, PDO::PARAM_INT);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
