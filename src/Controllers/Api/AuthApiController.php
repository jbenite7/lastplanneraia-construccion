<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Security\CsrfTokenManager;
use App\Services\Auth\AuthenticationService;
use Database;

/**
 * Entrada y salida JSON para el shell React.
 *
 * Reutiliza el mismo AuthenticationService que las rutas de formulario para
 * que la verificación de hash y la transición de sesión no diverjan.
 */
class AuthApiController
{
    private const CSRF_FORM_KEY = 'shell_api';

    private $db;
    private AuthenticationService $authentication;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->authentication = new AuthenticationService($this->db);
    }

    public function login(): void
    {
        $this->sendJsonHeaders();

        if (!$this->hasValidCsrfToken()) {
            $this->respond(403, [
                'success' => false,
                'mustChangePassword' => false,
                'message' => 'Solicitud no permitida.',
            ]);

            return;
        }

        $payload = json_decode((string) file_get_contents('php://input'), true);
        $usuario = is_array($payload) ? strtolower(trim((string) ($payload['username'] ?? ''))) : '';
        $password = is_array($payload) ? (string) ($payload['password'] ?? '') : '';

        if ($usuario === '' || $password === '') {
            $this->respond(400, [
                'success' => false,
                'mustChangePassword' => false,
                'message' => 'Escribe tu usuario y tu contraseña.',
            ]);

            return;
        }

        $user = $this->authentication->verifyCredentials($usuario, $password);

        // La inactividad se responde igual que una credencial incorrecta para
        // que este endpoint no revele qué cuentas existen o están habilitadas.
        if (!$user || (isset($user['activo']) && (int) $user['activo'] !== 1)) {
            if ($user && method_exists($this->db, 'logActivity')) {
                $this->db->logActivity('Login', 'LOGIN_BLOQUEADO_INACTIVO', "Intento de acceso con cuenta inactiva: {$usuario}");
            } elseif (method_exists($this->db, 'logActivity')) {
                $this->db->logActivity('Login', 'LOGIN_FALLIDO', "Credenciales incorrectas: {$usuario}");
            }

            $this->respond(401, [
                'success' => false,
                'mustChangePassword' => false,
                'message' => 'Usuario o contraseña incorrectos.',
            ]);

            return;
        }

        if (isset($user['force_password_change']) && $user['force_password_change'] == 1) {
            $this->authentication->beginPasswordChange($usuario, $user);
            if (method_exists($this->db, 'logActivity')) {
                $this->db->logActivity('Login', 'LOGIN_PENDIENTE_CLAVE', "Usuario {$usuario} requiere cambio de contraseña.");
            }

            $this->respond(200, [
                'success' => true,
                'mustChangePassword' => true,
                'message' => null,
            ]);

            return;
        }

        $this->authentication->beginAuthenticatedSession($usuario, $user);
        if (method_exists($this->db, 'logActivity')) {
            $this->db->logActivity('Login', 'LOGIN_FASE_1', "Usuario autenticado: {$usuario}");
        }

        $this->respond(200, [
            'success' => true,
            'mustChangePassword' => false,
            'message' => null,
        ]);
    }

    public function logout(): void
    {
        $this->sendJsonHeaders();

        if (!$this->hasValidCsrfToken()) {
            $this->respond(403, [
                'success' => false,
                'message' => 'Solicitud no permitida.',
            ]);

            return;
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION = [];
        session_destroy();

        $params = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 42000,
            'path' => $params['path'] ?: '/',
            'domain' => $params['domain'],
            'secure' => (bool) $params['secure'],
            'httponly' => (bool) $params['httponly'],
            'samesite' => $params['samesite'] ?? 'Lax',
        ]);

        $this->respond(200, ['success' => true]);
    }

    private function hasValidCsrfToken(): bool
    {
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;

        return CsrfTokenManager::validate(is_string($token) ? $token : null, self::CSRF_FORM_KEY);
    }

    /** @param array<string, bool|string|null> $payload */
    private function respond(int $status, array $payload): void
    {
        http_response_code($status);
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function sendJsonHeaders(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    }
}
