<?php

namespace App\Services\Auth;

class PasswordPolicyService
{
    /**
     * Mismas cinco reglas que `validate()` (longitud mínima 6, mayúscula, carácter especial,
     * confirmación coincidente, distinta a la clave anterior), pero acumuladas por campo en vez
     * de devolver solo el primer mensaje. `password` agrupa las reglas propias de la clave
     * nueva; `confirmation`, el desajuste con la repetición.
     *
     * @return array<string,list<string>>
     */
    public function validateFields(string $password, string $confirm, ?string $currentHash): array
    {
        $errors = [];

        foreach ($this->evaluate($password, $confirm, $currentHash) as [$field, $message]) {
            $errors[$field][] = $message;
        }

        return $errors;
    }

    /**
     * Conserva el comportamiento legacy: el primer mensaje que hubiera fallado, en el mismo
     * orden de siempre (longitud, mayúscula, especial, confirmación, igualdad con la clave
     * anterior). S02/S03 siguen consumiendo esta forma.
     */
    public function validate(string $password, string $confirm, ?string $currentHash = null): ?string
    {
        foreach ($this->evaluate($password, $confirm, $currentHash) as [, $message]) {
            return $message;
        }

        return null;
    }

    /**
     * @return list<array{0:string,1:string}>
     */
    private function evaluate(string $password, string $confirm, ?string $currentHash): array
    {
        $checks = [];

        if ($password === '' || strlen($password) < 6) {
            $checks[] = ['password', 'La contraseña debe tener al menos 6 caracteres'];
        }

        if (!preg_match('/[A-Z]/', $password)) {
            $checks[] = ['password', 'Debe contener al menos una letra mayúscula'];
        }

        if (!preg_match('/[^a-zA-Z0-9]/', $password)) {
            $checks[] = ['password', 'Debe contener al menos un carácter especial (!@#$%...)'];
        }

        if ($password !== $confirm) {
            $checks[] = ['confirmation', 'Las contraseñas no coinciden'];
        }

        if ($currentHash !== null && $currentHash !== '') {
            if (password_verify($password, $currentHash) || hash_equals($currentHash, hash('sha512', $password))) {
                $checks[] = ['password', 'La nueva contraseña no puede ser igual a la anterior'];
            }
        }

        return $checks;
    }
}
