<?php

namespace App\Services\Auth;

use Database;

class UserPasswordService
{
    private $db;
    private $policy;

    public function __construct($db = null, ?PasswordPolicyService $policy = null)
    {
        $this->db = $db ?: Database::getInstance();
        $this->policy = $policy ?: new PasswordPolicyService();
    }

    public function changePasswordForUsername(
        string $username,
        string $password,
        string $confirm,
        bool $clearForcePasswordChange = true,
    ): array {
        $user = $this->findFirstByUsername($username);
        if ($user === null) {
            return ['success' => false, 'message' => 'Usuario no encontrado', 'fieldErrors' => []];
        }

        $currentHash = (string) ($user['password'] ?? '');
        $fieldErrors = $this->policy->validateFields($password, $confirm, $currentHash);
        if ($fieldErrors !== []) {
            return [
                'success' => false,
                'message' => $this->policy->validate($password, $confirm, $currentHash),
                'fieldErrors' => $fieldErrors,
            ];
        }

        try {
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            $sql = $clearForcePasswordChange
                ? 'UPDATE general_usuarios SET password = ?, force_password_change = 0 WHERE usuario = ?'
                : 'UPDATE general_usuarios SET password = ? WHERE usuario = ?';

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$newHash, (string) $user['usuario']]);

            return ['success' => true, 'message' => 'Contraseña actualizada correctamente.', 'fieldErrors' => []];
        } catch (\Throwable $e) {
            error_log('UserPasswordService::changePasswordForUsername ' . $e->getMessage());

            return ['success' => false, 'message' => 'Error al actualizar la contraseña.', 'fieldErrors' => []];
        }
    }

    public function findFirstByUsername(string $username): ?array
    {
        $username = trim($username);
        if ($username === '') {
            return null;
        }

        $stmt = $this->db->prepare('SELECT id, usuario, password FROM general_usuarios WHERE usuario = ? LIMIT 1');
        $stmt->execute([$username]);
        $row = $stmt->fetch();

        return $row ?: null;
    }
}
