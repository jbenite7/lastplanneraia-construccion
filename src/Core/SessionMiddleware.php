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
        $reason = self::validationFailureReason();
        if ($reason !== null) {
            self::finishUnauthorized(
                $reason === 'inactive' ? '/login?inactive=1' : ($reason === 'timeout' ? '/login?timeout=1' : '/login'),
                $reason,
            );
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

    /**
     * Comprueba la misma sesión que protegen las rutas privadas, pero sin
     * responder ni redirigir. Las rutas públicas de bootstrap pueden así
     * informar "no autenticado" sin duplicar las reglas de usuario activo y
     * timeout.
     *
     * @return string|null El motivo de invalidez, o null cuando la sesión es válida.
     */
    public static function validationFailureReason(): ?string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $usuario = $_SESSION['usuario'] ?? null;
        if (!is_string($usuario) || $usuario === '') {
            return 'missing_session';
        }

        try {
            $db = \Database::getInstance();
            $stmt = $db->prepare('SELECT activo FROM general_usuarios WHERE usuario = ? LIMIT 1');
            $stmt->execute([$usuario]);
            $user = $stmt->fetch();
        } catch (\Throwable $e) {
            error_log('Error validando estado activo de la sesión: ' . $e->getMessage());
            self::invalidateSession();

            return 'session_unverified';
        }

        if (!$user) {
            self::invalidateSession();

            return 'stale_session';
        }

        if ((int) ($user['activo'] ?? 0) !== 1) {
            self::invalidateSession();

            return 'inactive';
        }

        if (isset($_SESSION['timeout'])) {
            $sessionTTL = time() - (int) $_SESSION['timeout'];
            if ($sessionTTL >= self::idleTimeoutSeconds()) {
                self::invalidateSession();

                return 'timeout';
            }
        }

        if (self::shouldRefreshTimeout()) {
            $_SESSION['timeout'] = time();
        }

        return null;
    }

    private static function invalidateSession(): void
    {
        $_SESSION = [];
        session_destroy();
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
