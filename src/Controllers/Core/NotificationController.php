<?php

namespace App\Controllers\Core;

use App\Services\NotificationService;

class NotificationController
{
    private $notificationService;

    public function __construct()
    {
        $this->notificationService = new NotificationService();
    }

    public function getUnread()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $userId = $_SESSION['usuario'] ?? null;

        if (!$userId) {
            http_response_code(403);
            echo json_encode(['error' => 'No autorizado']);
            return;
        }

        $notifications = $this->notificationService->getUnreadByUser($userId);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => true, 'data' => $notifications]);
    }

    public function markAsRead()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $userId = $_SESSION['usuario'] ?? null;

        if (!$userId) {
            http_response_code(403);
            echo json_encode(['error' => 'No autorizado']);
            return;
        }

        // Get JSON payload
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        $notificationId = $data['id'] ?? null;

        if (!$notificationId) {
            http_response_code(400);
            echo json_encode(['error' => 'ID de notificación requerido']);
            return;
        }

        $success = $this->notificationService->markAsRead($notificationId, $userId);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => $success]);
    }
}
