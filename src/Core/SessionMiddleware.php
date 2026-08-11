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

        // Auto-establecer contexto de proyecto para tablas globales (USE_GLOBAL_TABLES=true)
        if (isset($_SESSION['db']) && $_SESSION['db'] !== '') {
            try {
                $projectId = \TableResolver::getProjectIdByPrefix($_SESSION['db']);
                if ($projectId) {
                    \Database::getInstance()->setProjectContext($projectId);
                }
            } catch (\Throwable $e) {
                error_log('SessionMiddleware: No se pudo establecer contexto de proyecto: ' . $e->getMessage());
            }
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

        if (in_array($header, ['1', 'true', 'json'], true)) {
            return true;
        }

        // Cubre a cualquier consumidor (fetch, $.ajax con dataType 'json', etc.) que pida
        // JSON por la cabecera estándar `Accept`, sin depender de que además mande la
        // cabecera propietaria X-AIA-Expect-Json. Aditivo: no cambia el comportamiento de
        // quien ya manda la cabecera propietaria, y no afecta a navegaciones normales de
        // página, cuyo Accept es text/html primero.
        $accept = strtolower((string) ($_SERVER['HTTP_ACCEPT'] ?? ''));

        return str_contains($accept, 'application/json');
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
