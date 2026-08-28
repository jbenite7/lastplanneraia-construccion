<?php
declare(strict_types=1);
// @requiere: http

/**
 * Contrato HTTP de bootstrap del shell React.
 *
 * Protege contra dos regresiones reales: que una SPA sin cookie reciba un
 * redirect/401 en vez de su estado inicial, y que una cookie con un usuario
 * inexistente o vencida siga pareciendo autenticada solo porque conserva
 * `usuario` en la sesión.
 */

$base = getenv('APP_URL') ?: 'http://localhost';
$fallos = 0;

/** @return array{codigo:int,json:array<string,mixed>|null} */
function pedirJson(string $url, array $opciones = []): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_HTTPHEADER => ['Accept: application/json'],
        CURLOPT_COOKIEJAR => $opciones['cookies'] ?? '',
        CURLOPT_COOKIEFILE => $opciones['cookies'] ?? '',
        CURLOPT_COOKIE => $opciones['cookie'] ?? '',
    ]);
    $cuerpo = curl_exec($ch);
    $codigo = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $json = json_decode((string) $cuerpo, true);

    return ['codigo' => $codigo, 'json' => is_array($json) ? $json : null];
}

function comprobar(bool $condicion, string $mensaje): void
{
    global $fallos;

    if ($condicion) {
        return;
    }

    echo "FALLO: {$mensaje}\n";
    $fallos++;
}

/** @param array<string,mixed> $sesion */
function sesionArtificial(array $sesion): string
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
        throw new RuntimeException('No se pudo preparar la sesión artificial para el contrato HTTP.');
    }

    return "PHPSESSID={$sessionId}";
}

// Sin sesión: responde 200 con el estado de bootstrap, no redirige ni da 401.
$sinSesion = pedirJson("{$base}/api/session");
comprobar($sinSesion['codigo'] === 200, "sin sesión esperaba 200, llegó {$sinSesion['codigo']}");
comprobar(($sinSesion['json']['authenticated'] ?? null) === false, 'sin sesión esperaba authenticated=false');
comprobar(array_key_exists('user', $sinSesion['json'] ?? []) && $sinSesion['json']['user'] === null, 'sin sesión user debe ser null');
comprobar(array_key_exists('project', $sinSesion['json'] ?? []) && $sinSesion['json']['project'] === null, 'sin sesión project debe ser null');
comprobar(($sinSesion['json']['capabilities'] ?? null) === [], 'sin sesión capabilities debe ser un objeto vacío');
comprobar(
    array_key_exists('bi', $sinSesion['json']['navigation'] ?? []) && $sinSesion['json']['navigation']['bi'] === null,
    'sin sesión navigation.bi debe ser null',
);
comprobar(
    is_string($sinSesion['json']['csrfToken'] ?? null) && preg_match('/^[a-f0-9]{64}$/', $sinSesion['json']['csrfToken']) === 1,
    'sin sesión csrfToken debe ser un token hexadecimal de 64 caracteres',
);

// Con una sesión válida: trae usuario, rol, capacidades y token estable. La puerta
// de servicio tiene su propio contrato HTTP y puede estar cerrada en CI; esta prueba
// aísla el contrato de /api/session de esa configuración local.
$cookieValida = sesionArtificial([
    'usuario' => 'test.A',
    'nombreUsuario' => 'Test A',
    'permiso' => 'A',
    'project_id' => 42,
    'proyecto' => 'Proyecto contractual',
    'timeout' => time(),
]);
$conSesion = pedirJson("{$base}/api/session", ['cookie' => $cookieValida]);
$segundaLectura = pedirJson("{$base}/api/session", ['cookie' => $cookieValida]);

comprobar(($conSesion['json']['authenticated'] ?? null) === true, 'con sesión esperaba authenticated=true');
comprobar(($conSesion['json']['user']['username'] ?? null) === 'test.A', 'test.A debe conservar su username');
comprobar(($conSesion['json']['user']['displayName'] ?? null) === 'Test A', 'displayName debe conservar el nombre visible de la sesión');
comprobar(($conSesion['json']['user']['role'] ?? null) === 'A', 'test.A debe tener rol canónico A');
comprobar(($conSesion['json']['project']['id'] ?? null) === 42, 'project.id debe ser entero y conservar el proyecto activo');
comprobar(($conSesion['json']['project']['name'] ?? null) === 'Proyecto contractual', 'project.name debe conservar el nombre del proyecto activo');
comprobar(array_key_exists('canManageWeeks', $conSesion['json']['capabilities'] ?? []), 'capabilities debe traer canManageWeeks');
comprobar(($conSesion['json']['capabilities']['canManageWeeks'] ?? null) === true, 'el rol A debe poder administrar semanas');
comprobar(is_bool($conSesion['json']['navigation']['bi']['visible'] ?? null), 'navigation.bi.visible debe ser booleano');
if (($conSesion['json']['navigation']['bi']['visible'] ?? false) === true) {
    comprobar(
        is_string($conSesion['json']['navigation']['bi']['href'] ?? null)
        && str_starts_with($conSesion['json']['navigation']['bi']['href'], '/bi/'),
        'navigation.bi visible debe entregar una ruta BI autorizada por servidor',
    );
} else {
    comprobar(
        array_key_exists('href', $conSesion['json']['navigation']['bi'] ?? [])
        && $conSesion['json']['navigation']['bi']['href'] === null,
        'navigation.bi oculta no debe exponer href',
    );
}
comprobar(
    ($conSesion['json']['csrfToken'] ?? null) === ($segundaLectura['json']['csrfToken'] ?? null),
    'csrfToken debe mantenerse estable dentro de la misma sesión',
);
foreach (['db', 'area', 'usuario_temp', '_csrf_tokens'] as $prohibido) {
    comprobar(!array_key_exists($prohibido, $conSesion['json'] ?? []), "la respuesta filtra '{$prohibido}', que es interno de sesión");
}

// Una cookie vencida o que conserva un usuario borrado no es autenticación válida.
$cookieVencida = sesionArtificial([
    'usuario' => 'test.A',
    'timeout' => time() - 3601,
]);
$sesionVencida = pedirJson("{$base}/api/session", ['cookie' => $cookieVencida]);
comprobar(($sesionVencida['json']['authenticated'] ?? null) === false, 'una sesión vencida debe responder authenticated=false');

$cookieHuerfana = sesionArtificial([
    'usuario' => '__usuario_inexistente_contrato__',
    'timeout' => time(),
]);
$sesionHuerfana = pedirJson("{$base}/api/session", ['cookie' => $cookieHuerfana]);
comprobar(($sesionHuerfana['json']['authenticated'] ?? null) === false, 'una sesión de usuario inexistente debe responder authenticated=false');

echo $fallos === 0 ? "OK: contrato de /api/session\n" : "{$fallos} fallo(s)\n";
exit($fallos === 0 ? 0 : 1);
