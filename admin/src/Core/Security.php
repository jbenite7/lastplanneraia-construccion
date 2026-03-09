<?php

namespace Admin\Core;

class Security
{
    /**
     * Inicia la sesión de forma segura.
     */
    public static function initSession()
    {
        if (session_status() === PHP_SESSION_NONE) {
            // En local (localhost), a veces el dominio con puerto causa problemas
            $isLocal = in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1']);

            // Configuración simplificada para máxima compatibilidad en MAMP/Local
            session_set_cookie_params([
                'lifetime' => 0,
                'path' => '/', // Cambiado de /admin/ a / para evitar problemas de alcance
                'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
                'httponly' => true,
                'samesite' => 'Lax',
            ]);

            ini_set('session.use_strict_mode', 1);
            ini_set('session.use_only_cookies', 1);

            session_start();
        }
    }

    /**
     * Genera un token CSRF y lo guarda en la sesión.
     */
    public static function generateCsrfToken()
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            error_log("CSRF: Nuevo token generado: " . $_SESSION['csrf_token']);
        }

        return $_SESSION['csrf_token'];
    }

    /**
     * Valida si el token CSRF es correcto.
     */
    public static function validateCsrfToken($token)
    {
        $sessionToken = $_SESSION['csrf_token'] ?? '';

        if (empty($sessionToken) || empty($token)) {
            error_log("CSRF Error: Token faltante. Sesión: '$sessionToken', Enviado: '$token'");

            return false;
        }

        $isValid = hash_equals($sessionToken, $token);

        if (!$isValid) {
            error_log("CSRF Error: No coinciden. Sesión: '$sessionToken', Enviado: '$token'");
        }

        return $isValid;
    }

    /**
     * Regenera el ID de sesión para prevenir Session Fixation.
     */
    public static function regenerateSession()
    {
        session_regenerate_id(true);
    }
}
