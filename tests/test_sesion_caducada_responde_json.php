<?php

declare(strict_types=1);

// @requiere: puro

/**
 * Verifica que `SessionMiddleware` anuncie una sesión caducada como error JSON (401) a
 * cualquier petición que pida JSON por `Accept`, no solo a las que mandan la cabecera
 * propietaria `X-AIA-Expect-Json`. Antes de este cambio, una petición de grilla sin esa
 * cabecera recibía `Location: /login`, un fetch/$.ajax la seguía, y el HTML del login
 * llegaba donde se esperaban datos — el trabajo se perdía sin un error entendible.
 *
 * Aditivo: X-AIA-Expect-Json sigue funcionando igual que antes.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\SessionMiddleware;

$fallos = 0;
$total = 0;

/**
 * Invoca el método privado `expectsJsonResponse()` con el $_SERVER indicado.
 *
 * @param array<string,string> $server
 */
function evaluarExpectsJson(array $server): bool
{
    $backup = $_SERVER;
    foreach (['HTTP_X_AIA_EXPECT_JSON', 'HTTP_ACCEPT'] as $clave) {
        unset($_SERVER[$clave]);
    }
    foreach ($server as $clave => $valor) {
        $_SERVER[$clave] = $valor;
    }

    $reflection = new ReflectionClass(SessionMiddleware::class);
    $metodo = $reflection->getMethod('expectsJsonResponse');
    $metodo->setAccessible(true);
    $resultado = (bool) $metodo->invoke(null);

    $_SERVER = $backup;

    return $resultado;
}

function comprobar(string $descripcion, bool $obtenido, bool $esperado): void
{
    global $fallos, $total;
    $total++;

    if ($obtenido === $esperado) {
        echo "  OK   {$descripcion}\n";

        return;
    }

    $fallos++;
    echo '  FAIL ' . $descripcion
        . ' (esperado ' . var_export($esperado, true)
        . ', obtenido ' . var_export($obtenido, true) . ")\n";
}

echo "=== SessionMiddleware: sesión caducada se anuncia como JSON, no como HTML ===\n";

comprobar(
    'una grilla que pide JSON por Accept (fetch/$.ajax) recibe JSON, sin la cabecera propietaria',
    evaluarExpectsJson(['HTTP_ACCEPT' => 'application/json, text/javascript, */*; q=0.01']),
    true,
);

comprobar(
    'Accept con application/json entre otros tipos también cuenta',
    evaluarExpectsJson(['HTTP_ACCEPT' => 'text/plain, application/json']),
    true,
);

comprobar(
    'la cabecera propietaria X-AIA-Expect-Json=1 sigue funcionando igual que antes',
    evaluarExpectsJson(['HTTP_X_AIA_EXPECT_JSON' => '1']),
    true,
);

comprobar(
    'una navegación normal de página (Accept: text/html primero) sigue recibiendo la redirección',
    evaluarExpectsJson(['HTTP_ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8']),
    false,
);

comprobar(
    'sin cabecera Accept ni X-AIA-Expect-Json, se mantiene la redirección (comportamiento previo)',
    evaluarExpectsJson([]),
    false,
);

echo "\n";

if ($fallos > 0) {
    echo "FAIL: {$fallos} de {$total} comprobaciones fallaron\n";
    exit(1);
}

echo "OK: {$total} comprobaciones pasaron\n";
exit(0);
