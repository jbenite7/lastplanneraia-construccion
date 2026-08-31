<?php

// @requiere: http

/**
 * Comprueba la frontera desde Apache: las rutas del shell devuelven el HTML
 * inicial sin autenticar, sus assets se sirven como archivos y /login sigue
 * perteneciendo al sitio PHP.
 */

declare(strict_types=1);

$base = rtrim(getenv('APP_URL') ?: 'http://127.0.0.1', '/');
$fallos = 0;

/** @return array{codigo:int,cabeceras:string,cuerpo:string} */
function pedirFronteraSpa(string $url): array
{
    $curl = curl_init($url);
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_TIMEOUT => 20,
    ]);

    $respuesta = curl_exec($curl);
    if ($respuesta === false) {
        $error = curl_error($curl);
        curl_close($curl);
        throw new RuntimeException("La aplicación no respondió: {$error}");
    }

    $codigo = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    $tamanoCabeceras = (int) curl_getinfo($curl, CURLINFO_HEADER_SIZE);
    curl_close($curl);

    return [
        'codigo' => $codigo,
        'cabeceras' => substr($respuesta, 0, $tamanoCabeceras),
        'cuerpo' => substr($respuesta, $tamanoCabeceras),
    ];
}

function comprobarFronteraSpa(bool $condicion, string $mensaje): void
{
    global $fallos;

    if ($condicion) {
        return;
    }

    echo "FALLO: {$mensaje}\n";
    $fallos++;
}

try {
    $shell = pedirFronteraSpa("{$base}/app");
    comprobarFronteraSpa($shell['codigo'] === 200, "/app anonimo debe responder 200, llegó {$shell['codigo']}");
    comprobarFronteraSpa(str_contains($shell['cuerpo'], '<div id="root"></div>'), '/app debe devolver el HTML del shell');

    $loginSpa = pedirFronteraSpa("{$base}/app/login");
    comprobarFronteraSpa($loginSpa['codigo'] === 200, "/app/login debe responder el shell, llegó {$loginSpa['codigo']}");
    comprobarFronteraSpa(str_contains($loginSpa['cuerpo'], '<div id="root"></div>'), '/app/login debe devolver el HTML del shell');

    // Refresh / deep-link: pedir directo (sin pasar por el enrutador cliente de React) una
    // subruta profunda del shell debe devolver el mismo HTML — así se comporta una recarga F5
    // sobre cualquier pantalla React, no solo sobre /app o /app/login.
    $deepLink = pedirFronteraSpa("{$base}/app/proyectos/73/semana/6");
    comprobarFronteraSpa($deepLink['codigo'] === 200, "el deep-link/refresh profundo bajo /app debe responder 200, llegó {$deepLink['codigo']}");
    comprobarFronteraSpa(str_contains($deepLink['cuerpo'], '<div id="root"></div>'), 'el deep-link/refresh profundo bajo /app debe devolver el HTML del shell');

    $assets = glob(__DIR__ . '/../public/app/assets/*');
    $asset = $assets[0] ?? null;
    comprobarFronteraSpa($asset !== null, 'el bundle debe incluir al menos un asset para probar su entrega');
    if ($asset !== null) {
        $respuestaAsset = pedirFronteraSpa("{$base}/app/assets/" . rawurlencode(basename($asset)));
        comprobarFronteraSpa($respuestaAsset['codigo'] === 200, 'el asset del bundle debe responder 200');
        comprobarFronteraSpa(!str_contains($respuestaAsset['cuerpo'], '<div id="root"></div>'), 'un asset no debe devolver el HTML del shell');
    }

    $directorioAssets = pedirFronteraSpa("{$base}/app/assets");
    comprobarFronteraSpa($directorioAssets['codigo'] === 404, '/app/assets debe responder 404 controlado, no redirigir a /login');
    comprobarFronteraSpa(!str_contains($directorioAssets['cabeceras'], 'Location: /login'), '/app/assets no debe redirigir a /login');
    comprobarFronteraSpa(!str_contains($directorioAssets['cuerpo'], '<div id="root"></div>'), '/app/assets no debe devolver el HTML del shell');

    $loginLegacy = pedirFronteraSpa("{$base}/login");
    comprobarFronteraSpa($loginLegacy['codigo'] === 200, "/login legado debe conservar su 200, llegó {$loginLegacy['codigo']}");
    comprobarFronteraSpa(str_contains($loginLegacy['cuerpo'], 'id="loginForm"'), '/login debe seguir devolviendo el formulario PHP legado');
    comprobarFronteraSpa(!str_contains($loginLegacy['cuerpo'], '<div id="root"></div>'), '/login no debe ser robado por el shell SPA');
} catch (Throwable $error) {
    echo "FALLO: {$error->getMessage()}\n";
    $fallos++;
}

echo $fallos === 0 ? "OK: frontera SPA/PHP por HTTP\n" : "{$fallos} fallo(s)\n";
exit($fallos === 0 ? 0 : 1);
