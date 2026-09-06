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
        // El bundle del shell React (Tarea 12, S01) sirve la pantalla de la ruta oculta, pero
        // sus assets (`/app/assets/*.js`, `.css`) son peticiones aparte, igual que el CSS del
        // design system arriba: sin esta exención el segundo <script>/<link> del host oculto
        // caería en el cartel de mantenimiento. Solo el prefijo de assets — `/app` en sí sigue
        // cerrado, la SPA completa no se abre por esta vía.
        if (str_starts_with($uri, '/app/assets/')) {
            return true;
        }

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
