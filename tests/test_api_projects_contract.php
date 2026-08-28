<?php

declare(strict_types=1);
// @requiere: http

/**
 * Contrato HTTP del selector de proyectos del shell React.
 *
 * Se prepara una sesión válida directamente para que este contrato no dependa
 * de que la puerta de desarrollo esté abierta. No contiene credenciales ni
 * secretos de las cuentas de fixture.
 */

$base = rtrim(getenv('APP_URL') ?: 'http://127.0.0.1', '/');
$fallos = 0;

/**
 * @param array<string, mixed>|null $cuerpo
 * @param list<string> $headers
 * @return array{codigo:int,json:array<string,mixed>|null}
 */
function pedirJsonProyectos(string $url, ?string $cookie = null, ?array $cuerpo = null, array $headers = []): array
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
        $opciones[CURLOPT_POSTFIELDS] = json_encode($cuerpo, JSON_THROW_ON_ERROR);
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

/** @param array<string, mixed> $sesion */
function sesionArtificialProyectos(array $sesion): string
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

    return "PHPSESSID={$sessionId}";
}

// La ruta privada debe usar SessionMiddleware: sin cookie no se confunde una
// sesión expirada con una lista vacía de proyectos.
$sinSesion = pedirJsonProyectos("{$base}/api/proyectos");
comprobarProyecto(
    'GET /api/proyectos sin sesión responde 401 JSON',
    $sinSesion['codigo'] === 401 && ($sinSesion['json']['success'] ?? null) === false,
);

$cookie = sesionArtificialProyectos([
    'usuario' => 'test.A',
    'nombreUsuario' => 'Test A',
    'timeout' => time(),
]);

// El bootstrap emite el token shell_api para esta misma sesión, sin abrir la
// puerta de desarrollo ni depender de una contraseña de fixture.
$bootstrap = pedirJsonProyectos("{$base}/api/session", $cookie);
$csrf = $bootstrap['json']['csrfToken'] ?? null;
comprobarProyecto(
    '/api/session entrega CSRF shell_api a la sesión de fixture',
    $bootstrap['codigo'] === 200 && ($bootstrap['json']['authenticated'] ?? null) === true && is_string($csrf) && $csrf !== '',
);

$lista = pedirJsonProyectos("{$base}/api/proyectos", $cookie);
$proyectos = $lista['json']['projects'] ?? null;
comprobarProyecto('GET /api/proyectos autenticado responde una lista', $lista['codigo'] === 200 && is_array($proyectos));
comprobarProyecto('la sesión de fixture ve al menos un proyecto', is_array($proyectos) && count($proyectos) > 0);

$primero = is_array($proyectos) ? ($proyectos[0] ?? null) : null;
comprobarProyecto(
    'cada proyecto tiene id entero, name y role de texto',
    is_array($primero)
        && is_int($primero['id'] ?? null)
        && is_string($primero['name'] ?? null)
        && is_string($primero['role'] ?? null),
);

$nombre = is_array($primero) ? (string) ($primero['name'] ?? '') : '';
$sinCsrf = pedirJsonProyectos("{$base}/api/proyectos/seleccionar", $cookie, ['name' => $nombre]);
comprobarProyecto(
    'POST /api/proyectos/seleccionar sin CSRF rechaza 403 JSON',
    $sinCsrf['codigo'] === 403
        && ($sinCsrf['json']['success'] ?? null) === false
        && is_string($sinCsrf['json']['message'] ?? null),
);

$csrfInvalido = pedirJsonProyectos(
    "{$base}/api/proyectos/seleccionar",
    $cookie,
    ['name' => $nombre],
    ['X-CSRF-Token: csrf-invalido'],
);
comprobarProyecto(
    'POST /api/proyectos/seleccionar con CSRF inválido rechaza 403 JSON',
    $csrfInvalido['codigo'] === 403
        && ($csrfInvalido['json']['success'] ?? null) === false
        && is_string($csrfInvalido['json']['message'] ?? null),
);

$headersCsrf = ['X-CSRF-Token: ' . (is_string($csrf) ? $csrf : '')];
$seleccion = pedirJsonProyectos("{$base}/api/proyectos/seleccionar", $cookie, ['name' => $nombre], $headersCsrf);
comprobarProyecto(
    'POST /api/proyectos/seleccionar válido responde éxito sin redirect',
    $seleccion['codigo'] === 200
        && ($seleccion['json']['success'] ?? null) === true
        && array_key_exists('message', $seleccion['json'] ?? [])
        && $seleccion['json']['message'] === null,
);

$rechazo = pedirJsonProyectos(
    "{$base}/api/proyectos/seleccionar",
    $cookie,
    ['name' => '__proyecto_no_autorizado_contrato__'],
    $headersCsrf,
);
comprobarProyecto(
    'una selección no autorizada tiene una falla JSON controlada',
    $rechazo['codigo'] === 200
        && ($rechazo['json']['success'] ?? null) === false
        && is_string($rechazo['json']['message'] ?? null)
        && $rechazo['json']['message'] !== '',
);

echo $fallos === 0 ? "OK: contrato de /api/proyectos\n" : "{$fallos} fallo(s)\n";
exit($fallos === 0 ? 0 : 1);
