<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Sirve el HTML construido de la SPA (`public/app/index.html`) inyectándole un bloque de
 * configuración de arranque que el bundle JavaScript lee pero nunca conoce de antemano.
 *
 * Por qué existe (Tarea 12, S01): la ruta oculta de mantenimiento es un secreto operativo —
 * si su valor apareciera en el bundle, cualquiera que abra las herramientas del navegador la
 * encontraría, para siempre. El reparto es: el bundle solo conoce la FORMA del dato
 * (`frontend/src/lib/runtime/configuracion.ts`), y el servidor decide el VALOR en cada
 * request y lo entrega ya resuelto, en HTML, nunca en el JavaScript versionado.
 */
class SpaHostRenderer
{
    private const MARCADOR = '<div id="root"></div>';

    /**
     * @param array<string,mixed> $config Se serializa tal cual — el llamador es responsable de
     *   que cumpla el contrato de `EsquemaConfiguracionRuntime` en el lado TypeScript.
     */
    public static function render(array $config = [], int $status = 200, string $method = 'GET'): void
    {
        $html = (string) file_get_contents(PROJECT_ROOT . '/public/app/index.html');

        if ($config !== []) {
            $json = json_encode(
                $config,
                JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT,
            );
            $bootstrap = '<script id="aia-runtime-config" type="application/json">' . $json . '</script>';
            $html = str_replace(self::MARCADOR, $bootstrap . self::MARCADOR, $html);
        }

        // Esta respuesta puede llevar un token CSRF vivo (`$config['csrfToken']`). Producción es
        // hosting compartido detrás de una capa de caché — sin este encabezado, una copia
        // cacheada de esta página dejaría ese token al alcance de quien pida después la misma
        // URL. Mismo encabezado que ya usa el resto del repo para respuestas con CSRF/sesión
        // (`AuthApiController`, `SessionApiController`, `SessionController`, …).
        // Esta respuesta puede llevar un token CSRF vivo (`$config['csrfToken']`). Producción es
        // hosting compartido detrás de una capa de caché — sin este encabezado, una copia
        // cacheada de esta página dejaría ese token al alcance de quien pida después la misma
        // URL. Mismo encabezado que ya usa el resto del repo para respuestas con CSRF/sesión
        // (`AuthApiController`, `SessionApiController`, `SessionController`, …). Explícito a
        // propósito, sin fiarse de que `session.cache_limiter` (que ya manda uno equivalente por
        // defecto en este proceso) siga configurado igual en cualquier entorno futuro.
        // Esta respuesta puede llevar un token CSRF vivo (`$config['csrfToken']`). Producción es
        // hosting compartido detrás de una capa de caché — sin este encabezado, una copia
        // cacheada de esta página dejaría ese token al alcance de quien pida después la misma
        // URL. Mismo encabezado que ya usa el resto del repo para respuestas con CSRF/sesión
        // (`AuthApiController`, `SessionApiController`, `SessionController`, …). Explícito a
        // propósito, sin fiarse de que `session.cache_limiter` (que ya manda uno equivalente,
        // sin `max-age=0`, por defecto en este proceso) siga configurado igual en cualquier
        // entorno futuro.
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

        http_response_code($status);

        if (strtoupper($method) !== 'HEAD') {
            echo $html;
        }
    }
}
