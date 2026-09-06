<?php

// @requiere: http

/**
 * Comprueba la frontera desde Apache: las rutas del shell (`/app`, y desde la Tarea 13 también
 * `/`/`/login` en GET/HEAD) devuelven el HTML inicial sin autenticar, sus assets se sirven como
 * archivos, y las cuatro rutas que NUNCA deben cruzar al host SPA
 * (`/password/forgot`, `/password/reset`, `/api/*`, `/app/assets*`) siguen siendo del sitio PHP.
 * `POST /login` también sigue siendo del sitio PHP mientras dure la ventana de rollback de la
 * Tarea 13 — moverlo habría hecho el rollback irreversible.
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

/** @return array{codigo:int,cabeceras:string,cuerpo:string} */
function pedirFronteraSpaConMetodo(string $url, string $metodo, array $campos = []): array
{
    $curl = curl_init($url);
    $opciones = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_CUSTOMREQUEST => strtoupper($metodo),
    ];

    if (strtoupper($metodo) === 'POST') {
        $opciones[CURLOPT_POST] = true;
        $opciones[CURLOPT_POSTFIELDS] = http_build_query($campos);
    }

    if (strtoupper($metodo) === 'HEAD') {
        $opciones[CURLOPT_NOBODY] = true;
    }

    curl_setopt_array($curl, $opciones);
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

    // --- El corte de la Tarea 13: GET/HEAD '/' y '/login' ahora sirven el shell React. ---
    $raiz = pedirFronteraSpa("{$base}/");
    comprobarFronteraSpa($raiz['codigo'] === 200, "GET / debe responder 200, llegó {$raiz['codigo']}");
    comprobarFronteraSpa(str_contains($raiz['cuerpo'], '<div id="root"></div>'), 'GET / debe devolver el HTML del shell React');
    comprobarFronteraSpa(!str_contains($raiz['cuerpo'], 'id="loginForm"'), 'GET / ya no debe devolver el formulario PHP legado');

    $loginGet = pedirFronteraSpa("{$base}/login");
    comprobarFronteraSpa($loginGet['codigo'] === 200, "GET /login debe responder 200, llegó {$loginGet['codigo']}");
    comprobarFronteraSpa(str_contains($loginGet['cuerpo'], '<div id="root"></div>'), 'GET /login debe devolver el HTML del shell React');
    comprobarFronteraSpa(!str_contains($loginGet['cuerpo'], 'id="loginForm"'), 'GET /login ya no debe devolver el formulario PHP legado');

    $loginHead = pedirFronteraSpaConMetodo("{$base}/login", 'HEAD');
    comprobarFronteraSpa($loginHead['codigo'] === 200, "HEAD /login debe responder 200, llegó {$loginHead['codigo']}");
    comprobarFronteraSpa($loginHead['cuerpo'] === '', 'HEAD /login no debe llevar cuerpo');

    // --- POST /login sigue en el legado durante la ventana de rollback: si React se quedara
    // con el POST, quitar '/login' del mapa dejaría de ser un rollback real. ---
    $loginPost = pedirFronteraSpaConMetodo("{$base}/login", 'POST', []);
    comprobarFronteraSpa($loginPost['codigo'] === 200, "POST /login debe seguir llegando al adaptador legado, llegó {$loginPost['codigo']}");
    comprobarFronteraSpa(str_contains($loginPost['cuerpo'], 'id="loginForm"'), 'POST /login debe seguir devolviendo el formulario PHP legado con errores');
    comprobarFronteraSpa(!str_contains($loginPost['cuerpo'], '<div id="root"></div>'), 'POST /login no debe devolver el HTML del shell React');

    // --- Las cuatro rutas que NUNCA deben pasar al host SPA. ---
    $forgot = pedirFronteraSpa("{$base}/password/forgot");
    comprobarFronteraSpa($forgot['codigo'] === 200, "/password/forgot debe conservar su 200, llegó {$forgot['codigo']}");
    comprobarFronteraSpa(!str_contains($forgot['cuerpo'], '<div id="root"></div>'), '/password/forgot no debe ser robada por el shell SPA (S02 sin migrar)');

    $reset = pedirFronteraSpa("{$base}/password/reset");
    comprobarFronteraSpa($reset['codigo'] === 200, "/password/reset debe conservar su 200, llegó {$reset['codigo']}");
    comprobarFronteraSpa(!str_contains($reset['cuerpo'], '<div id="root"></div>'), '/password/reset no debe ser robada por el shell SPA (S03 sin migrar)');

    $apiSession = pedirFronteraSpa("{$base}/api/session");
    comprobarFronteraSpa(!str_contains($apiSession['cabeceras'], 'text/html'), '/api/session debe seguir respondiendo JSON, no el HTML del shell');
    comprobarFronteraSpa(!str_contains($apiSession['cuerpo'], '<div id="root"></div>'), '/api/session no debe devolver el HTML del shell');
} catch (Throwable $error) {
    echo "FALLO: {$error->getMessage()}\n";
    $fallos++;
}

echo $fallos === 0 ? "OK: frontera SPA/PHP por HTTP\n" : "{$fallos} fallo(s)\n";
exit($fallos === 0 ? 0 : 1);
