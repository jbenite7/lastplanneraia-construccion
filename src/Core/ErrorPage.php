<?php

namespace App\Core;

/**
 * Respuesta de error del producto.
 *
 * Sustituye a los tres cuerpos pelados que había antes: `404 Not Found` y
 * `405 Method Not Allowed` en `Router::dispatch()`, y el `<h1>` suelto de
 * `public/index.php`. Los tres echaban al usuario fuera del producto — sin
 * marca, sin tema y sin una ruta de vuelta — y la `404` era la única fila
 * bloqueante del roadmap del 2026-08-05.
 *
 * Distingue navegador de API a propósito. La app tiene decenas de rutas
 * `/api/*` consumidas por `fetch`, y devolverles una página entera de HTML es
 * peor que el texto pelado que había: un cliente que hace `response.json()`
 * recibe una pared de marcado. Por eso `/api/*` sigue recibiendo un cuerpo
 * mínimo, ahora en JSON válido en vez de texto suelto.
 */
class ErrorPage
{
    public static function render(int $codigo, string $titulo, string $mensaje): void
    {
        if (!headers_sent()) {
            http_response_code($codigo);
        }

        if (self::esPeticionDeApi()) {
            if (!headers_sent()) {
                header('Content-Type: application/json; charset=utf-8');
            }
            echo json_encode(
                ['error' => ['codigo' => $codigo, 'mensaje' => $titulo]],
                JSON_UNESCAPED_UNICODE
            );

            return;
        }

        if (!headers_sent()) {
            header('Content-Type: text/html; charset=utf-8');
        }

        // Las tres variables las lee la plantilla.
        $view = dirname(__DIR__, 2) . '/views/errors/error.view.php';
        if (!is_file($view)) {
            // Sin plantilla no se deja al usuario con una página en blanco.
            echo '<h1>' . htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') . '</h1>';

            return;
        }

        require $view;
    }

    private static function esPeticionDeApi(): bool
    {
        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '');
        if (($pos = strpos($uri, '?')) !== false) {
            $uri = substr($uri, 0, $pos);
        }

        if (str_starts_with($uri, '/api/')) {
            return true;
        }

        $accept = (string) ($_SERVER['HTTP_ACCEPT'] ?? '');
        if ($accept !== '' && str_contains($accept, 'application/json') && !str_contains($accept, 'text/html')) {
            return true;
        }

        return strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
    }
}
