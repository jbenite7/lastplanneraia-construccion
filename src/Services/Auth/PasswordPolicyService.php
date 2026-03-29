<?php

namespace App\Services\Auth;

class PasswordPolicyService
{
    public function validate(string $password, string $confirm, ?string $currentHash = null): ?string
    {
        if ($password === '' || strlen($password) < 6) {
            return 'La contraseña debe tener al menos 6 caracteres';
        }

        if (!preg_match('/[A-Z]/', $password)) {
            return 'Debe contener al menos una letra mayúscula';
        }

        if (!preg_match('/[^a-zA-Z0-9]/', $password)) {
            return 'Debe contener al menos un carácter especial (!@#$%...)';
        }

        if ($password !== $confirm) {
            return 'Las contraseñas no coinciden';
        }

        if ($currentHash !== null && $currentHash !== '') {
            if (password_verify($password, $currentHash) || hash_equals($currentHash, hash('sha512', $password))) {
                return 'La nueva contraseña no puede ser igual a la anterior';
            }
        }

        return null;
    }
}
