<?php
declare(strict_types=1);
// @requiere: http

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';

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

/** @return array{codigo:int,json:array<string,mixed>|null,cacheControl:string|null} */
function pedirJson(string $url, array $opciones = []): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_HTTPHEADER => ['Accept: application/json'],
        CURLOPT_COOKIEJAR => $opciones['cookies'] ?? '',
        CURLOPT_COOKIEFILE => $opciones['cookies'] ?? '',
        CURLOPT_COOKIE => $opciones['cookie'] ?? '',
    ]);
    $bruto = curl_exec($ch);
    $codigo = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $tamanoCabeceras = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);

    $cabeceras = substr((string) $bruto, 0, $tamanoCabeceras);
    $cuerpo = substr((string) $bruto, $tamanoCabeceras);
    $json = json_decode($cuerpo, true);

    $cacheControl = null;
    if (preg_match('/^Cache-Control:\s*(.+)$/mi', $cabeceras, $match) === 1) {
        $cacheControl = trim($match[1]);
    }

    return ['codigo' => $codigo, 'json' => is_array($json) ? $json : null, 'cacheControl' => $cacheControl];
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
comprobar(($sinSesion['json']['state'] ?? null) === 'anonymous', 'sin sesión esperaba state=anonymous');
comprobar(($sinSesion['json']['authenticated'] ?? null) === false, 'sin sesión esperaba authenticated=false');
comprobar(($sinSesion['json']['reason'] ?? null) === 'missing_session', 'sin sesión debe explicar missing_session');
comprobar(array_key_exists('user', $sinSesion['json'] ?? []) && $sinSesion['json']['user'] === null, 'sin sesión user debe ser null');
comprobar(array_key_exists('project', $sinSesion['json'] ?? []) && $sinSesion['json']['project'] === null, 'sin sesión project debe ser null');
comprobar(array_key_exists('week', $sinSesion['json'] ?? []) && $sinSesion['json']['week'] === null, 'sin sesión week debe ser null');
comprobar(($sinSesion['json']['capabilities'] ?? null) === [], 'sin sesión capabilities debe ser un objeto vacío');
comprobar(
    array_key_exists('bi', $sinSesion['json']['navigation'] ?? []) && $sinSesion['json']['navigation']['bi'] === null,
    'sin sesión navigation.bi debe ser null',
);
comprobar(
    is_string($sinSesion['json']['csrfToken'] ?? null) && preg_match('/^[a-f0-9]{64}$/', $sinSesion['json']['csrfToken']) === 1,
    'sin sesión csrfToken debe ser un token hexadecimal de 64 caracteres',
);
comprobar(
    $sinSesion['cacheControl'] !== null && str_contains($sinSesion['cacheControl'], 'no-store'),
    'sin sesión debe responder Cache-Control: no-store',
);

// Sesión temporal de cambio de clave obligatorio: no es "sin sesión" (no debe
// mandar al login normal a pedir credenciales) ni "autenticada" (no expone
// usuario_temp ni capacidades reales).
$cookieCambioClave = sesionArtificial([
    'usuario_temp' => 'test.A',
    'nombreUsuario' => 'Test A',
    'must_change_password' => true,
]);
$cambioClave = pedirJson("{$base}/api/session", ['cookie' => $cookieCambioClave]);
comprobar(($cambioClave['json']['state'] ?? null) === 'password_change_required', 'sesión de cambio de clave debe reportar state=password_change_required');
comprobar(($cambioClave['json']['authenticated'] ?? null) === false, 'sesión de cambio de clave no está autenticada');
comprobar(
    array_key_exists('reason', $cambioClave['json'] ?? []) && $cambioClave['json']['reason'] === null,
    'sesión de cambio de clave no usa el vocabulario de anonymous en reason',
);
comprobar(
    array_key_exists('user', $cambioClave['json'] ?? []) && $cambioClave['json']['user'] === null,
    'sesión de cambio de clave no debe exponer usuario_temp como user',
);
comprobar(!array_key_exists('usuario_temp', $cambioClave['json'] ?? []), 'la respuesta no debe filtrar la clave de sesión usuario_temp');

// Con una sesión válida: trae usuario, rol, capacidades y token estable. La puerta
// de servicio tiene su propio contrato HTTP y puede estar cerrada en CI; esta prueba
// aísla el contrato de /api/session de esa configuración local.
$cookieValida = sesionArtificial([
    'usuario' => 'test.A',
    'nombreUsuario' => 'Test A',
    'permiso' => 'A',
    'project_id' => 73,
    'proyecto' => 'Da Porto',
    'db' => 'da_porto',
    'semana' => 1,
    'permiso_canonico' => 'A',
    'timeout' => time(),
]);
$conSesion = pedirJson("{$base}/api/session", ['cookie' => $cookieValida]);
$segundaLectura = pedirJson("{$base}/api/session", ['cookie' => $cookieValida]);

comprobar(($conSesion['json']['state'] ?? null) === 'authenticated', 'con sesión esperaba state=authenticated');
comprobar(($conSesion['json']['authenticated'] ?? null) === true, 'con sesión esperaba authenticated=true');
comprobar(($conSesion['json']['user']['username'] ?? null) === 'test.A', 'test.A debe conservar su username');
comprobar(($conSesion['json']['user']['displayName'] ?? null) === 'Test A', 'displayName debe conservar el nombre visible de la sesión');
comprobar(($conSesion['json']['user']['role'] ?? null) === 'A', 'test.A debe tener rol canónico A');
comprobar(($conSesion['json']['project']['id'] ?? null) === 73, 'project.id debe provenir de una membresía activa');
comprobar(($conSesion['json']['project']['name'] ?? null) === 'Da Porto', 'project.name debe conservar el proyecto cuyo scope quedó enlazado');
comprobar(is_string($conSesion['json']['project']['area'] ?? null), 'project.area debe viajar como cadena (spec T01 §8.2)');
comprobar(
    is_array($conSesion['json']['navigation']['groups'] ?? null) && count($conSesion['json']['navigation']['groups']) > 0,
    'con proyecto activo, navigation.groups debe traer al menos un grupo (rol A no tiene nada oculto)',
);
foreach ($conSesion['json']['navigation']['groups'] as $grupoNav) {
    comprobar(is_string($grupoNav['id'] ?? null) && is_string($grupoNav['label'] ?? null), 'cada grupo de navigation.groups debe traer id y label');
    comprobar(is_array($grupoNav['items'] ?? null) && count($grupoNav['items']) > 0, "el grupo '{$grupoNav['id']}' no debe viajar vacío");
    foreach ($grupoNav['items'] as $itemNav) {
        $esAccion = ($itemNav['action'] ?? false) === true;
        comprobar(
            $esAccion ? $itemNav['href'] === null : (is_string($itemNav['href'] ?? null) && str_starts_with($itemNav['href'], '/')),
            "el item '{$itemNav['id']}' debe traer href absoluto o ser una acción sin href",
        );
    }
}
comprobar(
    array_key_exists('reason', $conSesion['json'] ?? []) && $conSesion['json']['reason'] === null,
    'una sesión autenticada debe traer reason=null',
);
comprobar(($conSesion['json']['week']['current'] ?? null) === 1, 'con proyecto y semana en sesión, week.current debe reflejarla');
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
comprobar(($sesionVencida['json']['state'] ?? null) === 'anonymous', 'una sesión vencida debe reportar state=anonymous (el cliente la lee como expirada por su reason)');
comprobar(($sesionVencida['json']['authenticated'] ?? null) === false, 'una sesión vencida debe responder authenticated=false');
comprobar(($sesionVencida['json']['reason'] ?? null) === 'timeout', 'una sesión vencida debe explicar timeout');

$cookieHuerfana = sesionArtificial([
    'usuario' => '__usuario_inexistente_contrato__',
    'timeout' => time(),
]);
$sesionHuerfana = pedirJson("{$base}/api/session", ['cookie' => $cookieHuerfana]);
comprobar(($sesionHuerfana['json']['state'] ?? null) === 'anonymous', 'una sesión huérfana debe reportar state=anonymous');
comprobar(($sesionHuerfana['json']['authenticated'] ?? null) === false, 'una sesión de usuario inexistente debe responder authenticated=false');
comprobar(($sesionHuerfana['json']['reason'] ?? null) === 'stale_session', 'una sesión huérfana debe explicar stale_session');

// Una identidad válida con un proyecto sin membresía sigue autenticada, pero el
// contexto derivado del proyecto se descarta para volver al selector.
$cookieProyectoAjeno = sesionArtificial([
    'usuario' => 'test.A',
    'nombreUsuario' => 'Test A',
    'permiso' => 'A',
    'permiso_canonico' => 'A',
    'project_id' => 42,
    'proyecto' => 'Proyecto sin membresía',
    'db' => 'proyecto_ajeno',
    'semana' => 9,
    'timeout' => time(),
]);
$proyectoAjeno = pedirJson("{$base}/api/session", ['cookie' => $cookieProyectoAjeno]);
comprobar(($proyectoAjeno['json']['state'] ?? null) === 'authenticated', 'proyecto ajeno sigue siendo state=authenticated (autenticado sin proyecto)');
comprobar(($proyectoAjeno['json']['authenticated'] ?? null) === true, 'proyecto ajeno no debe borrar la autenticación válida');
comprobar(($proyectoAjeno['json']['user']['username'] ?? null) === 'test.A', 'proyecto ajeno debe conservar la identidad');
comprobar(array_key_exists('project', $proyectoAjeno['json'] ?? []) && $proyectoAjeno['json']['project'] === null, 'proyecto ajeno debe reportar project=null');
comprobar(array_key_exists('week', $proyectoAjeno['json'] ?? []) && $proyectoAjeno['json']['week'] === null, 'sin project activo, week debe ser null aunque la sesión traiga una semana');
comprobar(
    array_key_exists('reason', $proyectoAjeno['json'] ?? []) && $proyectoAjeno['json']['reason'] === null,
    'proyecto ajeno autenticado debe traer reason=null',
);

// Mutación cubierta: SessionApiController vuelve a validar la sesión o limpia el
// DataScopeContext después de que beginRequest() capturó la decisión del request.
if (session_status() === PHP_SESSION_NONE) {
    session_id(bin2hex(random_bytes(16)));
    session_start();
}
$_SERVER['HTTP_X_AIA_IDLE_REFRESH'] = 'skip';
$_SESSION = [
    'usuario' => 'test.A',
    'nombreUsuario' => 'Test A',
    'permiso' => 'A',
    'permiso_canonico' => 'A',
    'project_id' => 73,
    'proyecto' => 'Da Porto',
    'db' => 'da_porto',
    'semana' => 1,
    'timeout' => time(),
];

$razonCapturada = \App\Core\SessionMiddleware::beginRequest(false);
$scopeCapturado = \Database::getInstance()->dataScope()->current();
comprobar($razonCapturada === null, 'beginRequest debe capturar una sesión válida para el contrato en proceso');
comprobar($scopeCapturado instanceof \App\Security\DataScope\ProjectScope, 'beginRequest debe enlazar el scope antes del controlador');

// Desde este punto, una segunda validación sería timeout. El controlador debe usar
// la razón ya capturada y serializar sin tocar el scope enlazado.
$_SESSION['timeout'] = time() - \App\Core\SessionMiddleware::idleTimeoutSeconds() - 1;
ob_start();
(new \App\Controllers\Api\SessionApiController())->show();
$respuestaEnProceso = json_decode((string) ob_get_clean(), true);

comprobar(($respuestaEnProceso['authenticated'] ?? null) === true, 'el controlador no debe revalidar después de beginRequest');
comprobar(($respuestaEnProceso['user']['username'] ?? null) === 'test.A', 'el controlador no debe invalidar la identidad ya aceptada');
comprobar(($respuestaEnProceso['project']['id'] ?? null) === 73, 'el controlador debe serializar el proyecto del scope capturado');
comprobar(\App\Core\SessionMiddleware::requestFailureReason() === null, 'la razón request-scoped debe seguir siendo la capturada');
comprobar(\Database::getInstance()->dataScope()->current() === $scopeCapturado, 'el controlador debe conservar exactamente el mismo scope enlazado');

\Database::getInstance()->dataScope()->clear();
unset($_SERVER['HTTP_X_AIA_IDLE_REFRESH']);
if (session_status() === PHP_SESSION_ACTIVE) {
    $_SESSION = [];
    session_destroy();
}

echo $fallos === 0 ? "OK: contrato de /api/session\n" : "{$fallos} fallo(s)\n";
exit($fallos === 0 ? 0 : 1);
