<?php

namespace App\Core;

class SessionMiddleware
{
    private const IDLE_TIMEOUT_SECONDS = 3600;

    public static function idleTimeoutSeconds(): int
    {
        return self::IDLE_TIMEOUT_SECONDS;
    }

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
            self::finishUnauthorized('/login', 'missing_session');
        }

        try {
            $db = \Database::getInstance();
            $stmt = $db->prepare("SELECT activo FROM general_usuarios WHERE usuario = ? LIMIT 1");
            $stmt->execute([(string) $_SESSION['usuario']]);
            $user = $stmt->fetch();

            if ($user && isset($user['activo']) && (int) $user['activo'] !== 1) {
                session_unset();
                session_destroy();
                self::finishUnauthorized('/login?inactive=1', 'inactive');
            }
        } catch (\Throwable $e) {
            error_log('Error validando estado activo de la sesión: ' . $e->getMessage());
        }

        // Gestión de timeout de inactividad (3600 segundos = 1 hora)
        $inactividad = self::idleTimeoutSeconds();
        if (isset($_SESSION["timeout"])) {
            $sessionTTL = time() - (int) $_SESSION["timeout"];
            if ($sessionTTL >= $inactividad) {
                session_unset();
                session_destroy();
                self::finishUnauthorized('/login?timeout=1', 'timeout');
            }
        }

        // Actualizar timestamp de última actividad
        if (self::shouldRefreshTimeout()) {
            $_SESSION["timeout"] = time();
        }

        // Actualizar semana en sesión si viene por parámetro GET (patrón legacy)
        if (isset($_GET['semana'])) {
            $_SESSION['semana'] = (int) $_GET['semana'];
        }
    }

    private static function shouldRefreshTimeout(): bool
    {
        $header = strtolower((string) ($_SERVER['HTTP_X_AIA_IDLE_REFRESH'] ?? ''));

        return !in_array($header, ['0', 'false', 'skip'], true);
    }

    private static function expectsJsonResponse(): bool
    {
        $header = strtolower((string) ($_SERVER['HTTP_X_AIA_EXPECT_JSON'] ?? ''));

        return in_array($header, ['1', 'true', 'json'], true);
    }

    private static function finishUnauthorized(string $redirectUrl, string $reason): void
    {
        if (self::expectsJsonResponse()) {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'sessionExpired' => true,
                'reason' => $reason,
                'redirect' => $redirectUrl,
            ]);
            exit;
        }

        header('Location: ' . $redirectUrl);
        exit;
    }
}
