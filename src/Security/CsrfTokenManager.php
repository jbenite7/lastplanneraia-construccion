<?php

namespace App\Security;

class CsrfTokenManager
{
    private const SESSION_KEY = '_csrf_tokens';

    public static function generate(string $formKey): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION[self::SESSION_KEY][$formKey])) {
            $_SESSION[self::SESSION_KEY][$formKey] = bin2hex(random_bytes(32));
        }

        return (string) $_SESSION[self::SESSION_KEY][$formKey];
    }

    public static function validate(?string $token, string $formKey): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $sessionToken = $_SESSION[self::SESSION_KEY][$formKey] ?? '';

        if (!is_string($token) || $token === '' || !is_string($sessionToken) || $sessionToken === '') {
            return false;
        }

        return hash_equals($sessionToken, $token);
    }
}
