<?php

namespace App\Core;

final class AppEnvironment
{
    private const ALLOWED = ['development', 'testing', 'production'];

    public static function current(): string
    {
        return self::normalize($_ENV['APP_ENV'] ?? getenv('APP_ENV') ?: null);
    }

    public static function normalize(?string $environment): string
    {
        $normalized = strtolower(trim((string) $environment));

        return in_array($normalized, self::ALLOWED, true) ? $normalized : 'production';
    }

    public static function allowsInternalTools(): bool
    {
        return in_array(self::current(), ['development', 'testing'], true);
    }
}
