<?php

namespace App\Core;

class MaintenanceMode
{
    public const SECRET_PATH = '/_aia/operacion/7f3c9b';

    public static function isActive(): bool
    {
        return file_exists(PROJECT_ROOT . '/.maintenance');
    }

    public static function isExemptRoute(string $uri): bool
    {
        return in_array($uri, [
            '/runtime/frontend-config.js',
            self::SECRET_PATH,
        ], true);
    }

    public static function renderPage(): void
    {
        http_response_code(503);
        readfile(PROJECT_ROOT . '/public/mantenimiento-aia.html');
        exit;
    }
}
