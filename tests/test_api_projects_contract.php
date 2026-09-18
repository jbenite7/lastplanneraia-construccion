<?php

declare(strict_types=1);
// @requiere: http

/**
 * Contrato HTTP seguro del selector de proyectos del shell React: `GET /api/proyectos` y
 * `POST /api/proyectos/seleccionar`.
 *
 * Ninguna prueba de este archivo selecciona un proyecto real ni escribe datos. El único caso que
 * llega a `ProjectAccessService::select()` usa un nombre sintético
 * (`__proyecto_no_autorizado_contrato__`) que no existe en ningún fixture: la consulta de
 * membresía no encuentra fila, el método corta en la primera rama de fallo (`failure()`), y ni la
 * sesión ni `general_actividad` (logActivity) se tocan — ver `src/Services/ProjectAccessService.php`.
 * Todo lo demás se corta antes de `select()`: sesión inválida, CSRF ausente/incorrecto o body
 * inválido responden sin llegar al servicio.
 *
 * Se prepara una sesión válida directamente para que este contrato no dependa de que la puerta de
 * desarrollo esté abierta. No contiene credenciales ni secretos de las cuentas de fixture. Toda
 * sesión temporal se elimina en `finally`, incluso si un caso falla.
 */

$base = rtrim(getenv('APP_URL') ?: 'http://127.0.0.1', '/');
$fallos = 0;

/**
 * @param array<string, mixed>|string|null $cuerpo
 * @param list<string> $headers
 * @return array{codigo:int,json:array<string,mixed>|null}
 */
function pedirJsonProyectos(string $url, ?string $cookie = null, array|string|null $cuerpo = null, array $headers = []): array
{
    $ch = curl_init($url);
    $httpHeaders = array_merge(['Accept: application/json'], $headers);
    $opciones = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_HTTPHEADER => $httpHeaders,
    ];

    if ($cookie !== null) {
        $opciones[CURLOPT_COOKIE] = $cookie;
    }

    if ($cuerpo !== null) {
        $httpHeaders[] = 'Content-Type: application/json';
        $opciones[CURLOPT_POST] = true;
        $opciones[CURLOPT_POSTFIELDS] = is_string($cuerpo) ? $cuerpo : json_encode($cuerpo, JSON_THROW_ON_ERROR);
        $opciones[CURLOPT_HTTPHEADER] = $httpHeaders;
    }

    curl_setopt_array($ch, $opciones);
    $respuesta = curl_exec($ch);
    $codigo = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $json = json_decode((string) $respuesta, true);

    return ['codigo' => $codigo, 'json' => is_array($json) ? $json : null];
}

function comprobarProyecto(string $descripcion, bool $condicion): void
{
    global $fallos;

    if ($condicion) {
        echo "OK: {$descripcion}\n";

        return;
    }

    $fallos++;
    echo "FALLO: {$descripcion}\n";
}

/**
 * @param array<string,mixed>|null $body
 */
function bloqueErrorHttpProyectos(?array $body, string $code): bool
{
    $error = $body['error'] ?? null;

    return ($body['success'] ?? null) === false
        && ($body['code'] ?? null) === $code
        && is_array($error)
        && ($error['codigo'] ?? null) === $code
        && is_string($error['mensaje'] ?? null)
        && ($error['mensaje'] ?? null) === ($body['message'] ?? null);
}

/**
 * @param array<string, mixed> $sesion
 * @return array{cookie:string,path:string}
 */
function sesionArtificialProyectos(array $sesion): array
{
    $sessionId = bin2hex(random_bytes(16));
    $codigo = <<<'PHP'
touch(sys_get_temp_dir() . '/sess_' . $argv[1]);
chmod(sys_get_temp_dir() . '/sess_' . $argv[1], 0666);
session_id($argv[1]);
session_start();
$_SESSION = json_decode($argv[2], true, 512, JSON_THROW_ON_ERROR);
session_write_close();
PHP;
    $comando = escapeshellarg(PHP_BINARY) . ' -r ' . escapeshellarg($codigo)
        . ' ' . escapeshellarg($sessionId)
        . ' ' . escapeshellarg(json_encode($sesion, JSON_THROW_ON_ERROR));
    exec($comando, $salida, $estado);
    if ($estado !== 0) {
        throw new RuntimeException('No se pudo preparar la sesión para el contrato de proyectos.');
    }

    return [
        'cookie' => "PHPSESSID={$sessionId}",
        'path' => sys_get_temp_dir() . '/sess_' . $sessionId,
    ];
}

$rutasSesion = [];

try {
    // La ruta privada debe usar SessionMiddleware: sin cookie no se confunde una
    // sesión expirada con una lista vacía de proyectos. `/api/proyectos*` no está en
    // `$publicRoutes`, así que `SessionMiddleware::finishUnauthorized()` corta antes de llegar al
    // controlador con su propia forma (`success`, `sessionExpired`, `reason`, `redirect`), distinta
    // del bloque `error` que emite `ProjectApiController::respondSessionInvalid()` — ese código de
    // defensa en profundidad nunca circula por HTTP y ya lo cubre el contrato puro.
    $sinSesion = pedirJsonProyectos("{$base}/api/proyectos");
    comprobarProyecto(
        'GET /api/proyectos sin sesión responde 401 controlado por SessionMiddleware',
        $sinSesion['codigo'] === 401
        && ($sinSesion['json']['success'] ?? null) === false
        && ($sinSesion['json']['sessionExpired'] ?? null) === true,
    );

    $sesion = sesionArtificialProyectos([
        'usuario' => 'test.A',
        'nombreUsuario' => 'Test A',
        'timeout' => time(),
    ]);
    $cookie = $sesion['cookie'];
    $rutasSesion[] = $sesion['path'];

    // El bootstrap emite el token shell_api para esta misma sesión, sin abrir la
    // puerta de desarrollo ni depender de una contraseña de fixture.
    $bootstrap = pedirJsonProyectos("{$base}/api/session", $cookie);
    $csrf = $bootstrap['json']['csrfToken'] ?? null;
    comprobarProyecto(
        '/api/session entrega CSRF shell_api a la sesión de fixture',
        $bootstrap['codigo'] === 200 && ($bootstrap['json']['authenticated'] ?? null) === true && is_string($csrf) && $csrf !== '',
    );

    $lista = pedirJsonProyectos("{$base}/api/proyectos", $cookie);
    $primero = $lista['json']['projects'][0] ?? null;
    comprobarProyecto(
        'GET /api/proyectos autenticado responde una lista',
        $lista['codigo'] === 200 && is_array($lista['json']['projects'] ?? null),
    );
    comprobarProyecto(
        'tarjeta HTTP completa',
        is_array($primero)
        && is_int($primero['id'] ?? null)
        && is_string($primero['name'] ?? null)
        && in_array($primero['area'] ?? null, ['Construccion', 'Pre-Construccion'], true)
        && ($primero['active'] ?? null) === true
        && is_string($primero['role'] ?? null)
        && is_string($primero['roleLabel'] ?? null),
    );
    comprobarProyecto(
        'GET incluye BI coherente',
        isset($lista['json']['navigation']['bi'])
        && is_bool($lista['json']['navigation']['bi']['visible'] ?? null),
    );

    $sinCsrf = pedirJsonProyectos("{$base}/api/proyectos/seleccionar", $cookie, ['name' => 'cualquiera']);
    comprobarProyecto(
        'POST /api/proyectos/seleccionar sin CSRF rechaza 403 con bloque error',
        $sinCsrf['codigo'] === 403 && bloqueErrorHttpProyectos($sinCsrf['json'], 'csrf_invalid'),
    );

    $csrfInvalido = pedirJsonProyectos(
        "{$base}/api/proyectos/seleccionar",
        $cookie,
        ['name' => 'cualquiera'],
        ['X-CSRF-Token: csrf-invalido'],
    );
    comprobarProyecto(
        'POST /api/proyectos/seleccionar con CSRF inválido rechaza 403 con bloque error',
        $csrfInvalido['codigo'] === 403 && bloqueErrorHttpProyectos($csrfInvalido['json'], 'csrf_invalid'),
    );

    $headersCsrf = ['X-CSRF-Token: ' . (is_string($csrf) ? $csrf : '')];

    $bodyRoto = pedirJsonProyectos("{$base}/api/proyectos/seleccionar", $cookie, '{"name":', $headersCsrf);
    comprobarProyecto(
        'POST con body JSON roto responde 422 validation_error',
        $bodyRoto['codigo'] === 422 && bloqueErrorHttpProyectos($bodyRoto['json'], 'validation_error'),
    );

    $claveExtra = pedirJsonProyectos(
        "{$base}/api/proyectos/seleccionar",
        $cookie,
        ['name' => 'cualquiera', 'project_id' => 1],
        $headersCsrf,
    );
    comprobarProyecto(
        'POST con clave extra project_id responde 422 validation_error',
        $claveExtra['codigo'] === 422 && bloqueErrorHttpProyectos($claveExtra['json'], 'validation_error'),
    );

    // Nombre sintético que no existe en ningún fixture: el SELECT de ProjectAccessService::select()
    // no encuentra fila y devuelve failure() de inmediato, sin escribir sesión ni loguear actividad.
    $rechazo = pedirJsonProyectos(
        "{$base}/api/proyectos/seleccionar",
        $cookie,
        ['name' => '__proyecto_no_autorizado_contrato__'],
        $headersCsrf,
    );
    comprobarProyecto(
        'una selección sintética no autorizada responde 200 con el literal común y route null',
        $rechazo['codigo'] === 200
        && ($rechazo['json']['success'] ?? null) === false
        && ($rechazo['json']['message'] ?? null) === 'No se pudo acceder al proyecto seleccionado.'
        && array_key_exists('route', $rechazo['json'] ?? [])
        && $rechazo['json']['route'] === null,
    );
} finally {
    foreach ($rutasSesion as $ruta) {
        if (is_file($ruta)) {
            unlink($ruta);
        }
    }
}

echo $fallos === 0 ? "OK: contrato de /api/proyectos\n" : "{$fallos} fallo(s)\n";
exit($fallos === 0 ? 0 : 1);
