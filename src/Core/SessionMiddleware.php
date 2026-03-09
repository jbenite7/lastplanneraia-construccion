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

        // Gestión de timeout de inactividad (3600 segundos = 1 hora)
        $inactividad = 3600;
        if (isset($_SESSION["timeout"])) {
            $sessionTTL = time() - $_SESSION["timeout"];
            if ($sessionTTL > $inactividad) {
                session_unset();
                session_destroy();
                echo "<script>alert('Se cerrará la sesión por un tiempo de inactividad mayor a 1 hora.');window.location.href='/login';</script>";
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
