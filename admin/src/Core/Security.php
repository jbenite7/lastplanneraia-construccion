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
            // Configuración de cookies de sesión
            session_set_cookie_params([
                'lifetime' => 0,
                'path' => '/admin/',
                'domain' => $_SERVER['HTTP_HOST'],
                'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
                'httponly' => true,
                'samesite' => 'Lax'
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
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Valida si el token CSRF es correcto.
     */
    public static function validateCsrfToken($token)
    {
        if (!isset($_SESSION['csrf_token']) || empty($token)) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Regenera el ID de sesión para prevenir Session Fixation.
     */
    public static function regenerateSession()
    {
        session_regenerate_id(true);
    }
}
