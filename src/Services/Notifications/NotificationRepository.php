<?php

declare(strict_types=1);

namespace App\Services\Notifications;

use Database;
use PDO;

/**
 * Implementación por defecto de {@see NotificationRepositoryInterface}: exactamente las dos
 * consultas que `NotificationService::getUnreadByUser()`/`markAsRead()` ejecutaban inline antes
 * de la Tarea 9 — sólo se movieron, no cambiaron de forma (mismo SQL, mismos binds).
 */
final class NotificationRepository implements NotificationRepositoryInterface
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function findUnreadByUser(string $userId): array
    {
        $sql = 'SELECT id, type, title, message, item_count, created_at, project_id
                FROM system_notifications
                WHERE user_id = :user_id AND is_read = 0
                ORDER BY created_at DESC';
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function markAsRead(int $notificationId, string $userId): bool
    {
        $sql = 'UPDATE system_notifications
                SET is_read = 1
                WHERE id = :id AND user_id = :user_id';
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $notificationId, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId);

        return $stmt->execute();
    }
}
