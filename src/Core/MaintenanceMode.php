<?php

namespace App\Core;

class MaintenanceMode
{
    public const SECRET_PATH = '/_aia/operacion/7f3c9b';

    public static function isActive(): bool
    {
        return file_exists(PROJECT_ROOT . '/.maintenance');
    }

    /**
     * Rutas que se sirven aunque el mantenimiento este activo.
     *
     * Incluye los entrypoints CSS del design system: la ruta oculta sirve la pantalla de
     * entrada, pero sus hojas de estilo son peticiones aparte y las que pasan por PHP caian en
     * el cartel, devolviendo HTML con 503 donde el navegador esperaba CSS. Las rutas se piden a
     * su dueño en vez de copiarse aqui, que es como se desincronizaron la primera vez.
     */
    public static function isExemptRoute(string $uri): bool
    {
        $exentas = array_merge(
            [
                '/runtime/frontend-config.js',
                self::SECRET_PATH,
            ],
            \App\Controllers\Core\DesignSystemAssetController::publicRoutePaths(),
        );

        return in_array($uri, $exentas, true);
    }

    public static function renderPage(): void
    {
        http_response_code(503);
        readfile(PROJECT_ROOT . '/public/mantenimiento-aia.html');
        exit;
    }
}
