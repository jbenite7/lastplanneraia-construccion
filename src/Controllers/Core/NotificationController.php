<?php

namespace App\Controllers\Core;

use App\Security\CsrfTokenManager;
use App\Services\Notifications\NotificationInboxService;
use App\Services\Notifications\NotificationRepository;

class NotificationController
{
    /**
     * Mismo form-key que `AuthApiController`/`SessionApiController` (`shell_api`): el token que
     * `/api/session` emite como `csrfToken` para el shell React (T02 Tarea 9, AC-139). No es un
     * token nuevo por endpoint — reutiliza el que el shell ya tiene desde que hay sesión.
     */
    private const CSRF_FORM_KEY = 'shell_api';

    private NotificationInboxService $notificationInbox;

    /**
     * `NotificationInboxService` (Fix round 1), no el viejo `App\Services\NotificationService`:
     * este controlador sólo necesita las dos operaciones de bandeja de identidad, y el servicio
     * legado sigue abriendo una conexión PDO real en su constructor (`Database::getInstance()`
     * incondicional) — construirlo aquí habría vuelto a acoplar este controlador a la base para
     * nada.
     */
    public function __construct()
    {
        $this->notificationInbox = new NotificationInboxService(new NotificationRepository());
    }

    /**
     * `SessionMiddleware::beginRequest()` ya exige sesión antes de que el router llegue aquí
     * (`/api/notifications/*` no está en `$publicRoutes` de `public/index.php`) y responde 401 con
     * el sobre `{success:false, sessionExpired:true, reason, redirect}` — ver
     * `tests/test_lps_api_contract.php` y `tests/test_notifications_api_contract.php`. La rama
     * `!$userId` → 403 que tenía este método antes de la Tarea 9 era código muerto (hallazgo T02
     * Tarea 1, anotado en `TASKS.md`): para llegar aquí, `$_SESSION['usuario']` ya está
     * garantizado, así que ninguna prueba podía alcanzarla. Se retira en vez de conservarse: una
     * rama que nunca puede dispararse documenta un contrato falso.
     */
    public function getUnread()
    {
        $userId = $_SESSION['usuario'];

        $notifications = $this->notificationInbox->getUnreadByUser($userId);

        header('Content-Type: application/json; charset=utf-8');
        // `ok` es aditivo (D-T02-08): `success` se conserva mientras conviva con el legacy
        // `notifications.js`, que sólo lee `res.success`/`res.data`.
        echo json_encode(['success' => true, 'ok' => true, 'data' => $notifications]);
    }

    /**
     * T02-AC-139: marcar leída exige CSRF del shell, contrato nuevo — antes este endpoint no lo
     * pedía (medido en vivo, ver el comentario histórico que traía `notificaciones.ts` antes de
     * esta tarea). `notifications.js` (legacy) nunca compone este POST en la práctica hoy: las
     * vistas migradas al sidebar nuevo ya no renderizan `#notificationBadge`/`#notificationList`
     * (`grep` sin resultados en `views/`), así que `initNotifications()` corta en su primer
     * `if (!badgeDesk && !badgeMob) return;` antes de registrar el click que dispara `markAsRead`.
     * Por eso el archivo legacy se deja intacto (brief Tarea 9, "Omite el camino legado JS si no
     * requirió cambio") en vez de enhebrar un token `shell_api` por 12 vistas para un botón que no
     * existe en el DOM actual.
     */
    public function markAsRead()
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!$this->hasValidCsrfToken()) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'ok' => false,
                'error' => [
                    'code' => 'CSRF_INVALID',
                    'message' => 'Token de seguridad inválido. Recarga la página e intenta de nuevo.',
                ],
            ]);
            return;
        }

        $userId = $_SESSION['usuario'];

        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        $rawId = is_array($data) ? ($data['id'] ?? null) : null;

        // AC-140: sólo un ID positivo es una forma válida. `filter_var(FILTER_VALIDATE_INT)` por
        // sí solo NO basta: acepta bool (`true` → 1) y float entero (`31.0` → 31) además de int
        // nativo o string numérica — se descartan aquí antes de llegar a `filter_var`, que sólo
        // queda para rechazar "31abc" y cadenas no numéricas.
        $notificationId = (is_int($rawId) || is_string($rawId))
            ? filter_var($rawId, FILTER_VALIDATE_INT)
            : false;

        if ($notificationId === false || $notificationId <= 0) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'ok' => false,
                'error' => [
                    'code' => 'VALIDATION_FAILED',
                    'message' => 'ID de notificación requerido.',
                ],
            ]);
            return;
        }

        $success = $this->notificationInbox->markAsRead($notificationId, $userId);

        echo json_encode(['success' => $success, 'ok' => $success]);
    }

    private function hasValidCsrfToken(): bool
    {
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;

        return CsrfTokenManager::validate(is_string($token) ? $token : null, self::CSRF_FORM_KEY);
    }
}
