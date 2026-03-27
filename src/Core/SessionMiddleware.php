<?php

namespace App\Core;

class SessionMiddleware
{
    /**
     * Verifica que el usuario esté autenticado y gestiona el timeout de sesión.
     *
     * Redirige a /login si:
     * - No hay sesión activa
     * - El timeout de inactividad (1 hora) ha expirado
     *
     * También actualiza la semana en sesión si viene por GET.
     */
    public static function check()
    {
        // Iniciar sesión si no está activa
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Validar que el usuario esté autenticado
        if (!isset($_SESSION['usuario'])) {
            header('Location: /login');
            exit;
        }

        try {
            $db = \Database::getInstance();
            $stmt = $db->prepare("SELECT activo FROM general_usuarios WHERE usuario = ? LIMIT 1");
            $stmt->execute([(string)$_SESSION['usuario']]);
            $user = $stmt->fetch();

            if ($user && isset($user['activo']) && (int)$user['activo'] !== 1) {
                session_unset();
                session_destroy();
                header('Location: /login?inactive=1');
                exit;
            }
        } catch (\Throwable $e) {
            error_log('Error validando estado activo de la sesión: ' . $e->getMessage());
        }

        // Gestión de timeout de inactividad (3600 segundos = 1 hora)
        $inactividad = 3600; // 1 hora de inactividad (Timeout Phase 1)
        if (isset($_SESSION["timeout"])) {
            $sessionTTL = time() - $_SESSION["timeout"];
            if ($sessionTTL > $inactividad) {
                session_unset();
                session_destroy();
                header('Location: /login?timeout=1');
                exit;
            }
        }

        // Actualizar timestamp de última actividad
        $_SESSION["timeout"] = time();

        // Actualizar semana en sesión si viene por parámetro GET (patrón legacy)
        if (isset($_GET['semana'])) {
            $_SESSION['semana'] = (int)$_GET['semana'];
        }
    }
}
