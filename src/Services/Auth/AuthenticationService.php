<?php

declare(strict_types=1);

namespace App\Services\Auth;

use Database;

/**
 * Credenciales y transiciones de sesión compartidas por todos los puntos de
 * entrada de autenticación.
 */
class AuthenticationService
{
    private $db;

    public function __construct($db = null)
    {
        $this->db = $db ?? Database::getInstance();
    }

    /**
     * Conserva el soporte transitorio para hashes SHA-512 heredados y los
     * migra a password_hash al primer acceso correcto.
     *
     * @return array<string, mixed>|null
     */
    public function verifyCredentials(string $usuario, string $password): ?array
    {
        $stmt = $this->db->query(
            'SELECT * FROM general_usuarios WHERE usuario = ? LIMIT 1',
            [$usuario],
        );
        $data = $stmt->fetch();

        if (!$data) {
            return null;
        }

        if (password_verify($password, $data['password'])) {
            return $data;
        }

        if (hash_equals($data['password'], hash('sha512', $password))) {
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            $this->db->query(
                'UPDATE general_usuarios SET password = ? WHERE usuario = ?',
                [$newHash, $usuario],
            );

            return $data;
        }

        return null;
    }

    /** @param array<string, mixed> $user */
    public function beginPasswordChange(string $usuario, array $user): void
    {
        $this->regenerateSessionId();
        $_SESSION['usuario_temp'] = $usuario;
        $_SESSION['nombreUsuario'] = (string) ($user['nombre'] ?? '');
        $_SESSION['must_change_password'] = true;
    }

    /** @param array<string, mixed> $user */
    public function beginAuthenticatedSession(string $usuario, array $user): void
    {
        $this->regenerateSessionId();
        $_SESSION['usuario'] = $usuario;
        $_SESSION['nombreUsuario'] = (string) ($user['nombre'] ?? '');
        unset($_SESSION['usuario_temp']);
        unset($_SESSION['must_change_password']);
        $this->clearProjectContext();
    }

    public function clearProjectContext(): void
    {
        unset($_SESSION['proyecto']);
        unset($_SESSION['db']);
        unset($_SESSION['semana']);
        unset($_SESSION['permiso']);
        unset($_SESSION['pdcActivo']);
    }

    private function regenerateSessionId(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }
}
