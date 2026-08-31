<?php
declare(strict_types=1);
// @requiere: datos-proyecto

// T02 Tarea 1 (docs/superpowers/plans/2026-08-30-t02-contexto-lps-react.md): congela el
// contrato HTTP actual de LpsApiController y NotificationController — sobres de
// respuesta, códigos y el gate de CSRF — ANTES de tocar nada. Es
// caracterización, no verificación de comportamiento nuevo: cada aserción documenta lo
// que el código YA hace hoy, para que las tareas 5, 6, 9, 10 y 11 puedan extender este
// mismo archivo con los sobres aditivos sin perder la fotografía de partida.
//
// Restricción global del plan: sin DDL/DML. Todos los casos aquí ejercitan rutas de
// validación que fallan ANTES de tocar la base de datos (consecutivo/alerta_id <= 0,
// token CSRF ausente) o lecturas puras (SELECT sin escritura). Ninguno crea, modifica
// ni borra una fila.

const BASE = 'http://localhost';
const PROYECTO = 'PDC Sandbox E2E';

function sesion(string $usuario): string {
    $jar = tempnam(sys_get_temp_dir(), 'cookies_');
    $url = BASE . '/dev/entrar?u=' . urlencode($usuario) . '&p=' . urlencode(PROYECTO);
    [$code] = curlReq($url, null, $jar, []);
    if (!in_array($code, [200, 302], true)) {
        fwrite(STDERR, "ABORT: dev door cerrada (HTTP $code). Revisa DEV_DOOR en .env\n");
        exit(2);
    }
    return $jar;
}

/** @return array{0:int,1:string} */
function curlReq(string $url, ?array $post, string $jar, array $headers = []): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_COOKIEJAR => $jar, CURLOPT_COOKIEFILE => $jar,
        CURLOPT_HTTPHEADER => $headers,
    ]);
    if ($post !== null) { curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post)); }
    $body = (string) curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$code, $body];
}

/** @return array{0:int,1:array<string,mixed>} */
function jsonReq(string $url, ?array $post, string $jar, array $headers = []): array {
    [$code, $body] = curlReq($url, $post, $jar, $headers);
    $decoded = json_decode($body, true);
    if (!is_array($decoded)) {
        fwrite(STDERR, "ABORT: respuesta no-JSON de $url (HTTP $code): " . substr($body, 0, 200) . "\n");
        exit(2);
    }
    return [$code, $decoded];
}

$fallos = 0; $total = 0;
function afirmar(bool $condicion, string $mensaje): void {
    global $fallos, $total;
    $total++;
    if (!$condicion) {
        $fallos++;
        echo "FALLO: $mensaje\n";
    }
}

$jar = sesion('test.R'); // rol con permiso de edición sobre programación semanal

// ---------------------------------------------------------------------------
// Sección 1: GET /api/lps/comments — sobre de error en validación (sin llegar a DB)
// ---------------------------------------------------------------------------

[$code, $data] = jsonReq(BASE . '/api/lps/comments?consecutivo=0', null, $jar);
afirmar($code === 200, "GET comments consecutivo=0 debería responder HTTP 200 (fue $code)");
afirmar(($data['respuesta'] ?? null) === 'ERROR', 'GET comments consecutivo=0 debe traer respuesta=ERROR');
afirmar(($data['mensaje'] ?? null) === 'Actividad inválida.', 'GET comments consecutivo=0 debe traer el mensaje exacto de validación');
afirmar(!array_key_exists('data', $data), 'el sobre de error de comments no debe traer clave "data"');

[$code, $data] = jsonReq(BASE . '/api/lps/comments', null, $jar);
afirmar($code === 200, "GET comments sin parámetro debería responder HTTP 200 (fue $code)");
afirmar(($data['respuesta'] ?? null) === 'ERROR', 'GET comments sin consecutivo cae en el mismo error de validación (default 0)');

// ---------------------------------------------------------------------------
// Sección 2: lectura pura con consecutivo inexistente — SELECT sin filas, sin DML
// ---------------------------------------------------------------------------

[$code, $data] = jsonReq(BASE . '/api/lps/comments?consecutivo=999999999', null, $jar);
afirmar($code === 200, "GET comments consecutivo inexistente debería responder HTTP 200 (fue $code)");
afirmar(($data['respuesta'] ?? null) === 'OK', 'GET comments consecutivo inexistente sigue respondiendo OK (lectura pura sin filas)');
afirmar(is_array($data['data'] ?? null), 'GET comments consecutivo inexistente trae "data" como arreglo');
afirmar(($data['data'] ?? ['x']) === [], 'GET comments consecutivo inexistente trae "data" vacío');

// ---------------------------------------------------------------------------
// Sección 3: mutaciones sin token CSRF — bloqueadas antes de tocar el servicio
// ---------------------------------------------------------------------------

$mutacionesSinCsrf = [
    ['/api/lps/comments/add', ['consecutivo' => '1', 'comentario' => 'censo t02']],
    ['/api/lps/crisis/register', ['consecutivo' => '1', 'modulo' => 'PS']],
    ['/api/lps/crisis/close', ['alerta_id' => '1', 'justificacion' => str_repeat('x', 100)]],
];
foreach ($mutacionesSinCsrf as [$ruta, $payload]) {
    [$code, $data] = jsonReq(BASE . $ruta, $payload, $jar);
    afirmar($code === 403, "POST $ruta sin CSRF debería responder HTTP 403 (fue $code)");
    afirmar(($data['respuesta'] ?? null) === 'ERROR', "POST $ruta sin CSRF debe traer respuesta=ERROR");
    afirmar(($data['success'] ?? null) === false, "POST $ruta sin CSRF debe traer success=false");
    afirmar(
        ($data['mensaje'] ?? null) === 'Token de seguridad inválido. Recargue la página e intente de nuevo.',
        "POST $ruta sin CSRF debe traer el mensaje exacto del gate CSRF (src/Legacy/rbac_guard.php:98)",
    );
}

// ---------------------------------------------------------------------------
// Sección 4: /api/notifications/* — sobre de sesión ausente (sin cookie de sesión)
// ---------------------------------------------------------------------------

// Medido en vivo: SessionMiddleware::check() intercepta ANTES de que la petición
// llegue a NotificationController, así que el sobre real de "sin sesión" es el del
// middleware (401 sessionExpired/missing_session), no el 403 "No autorizado" que
// NotificationController::getUnread()/markAsRead() emitirían si el middleware los
// dejara pasar sin $_SESSION['usuario']. Esa rama del controlador queda inalcanzable
// hoy por esta puerta — se documenta como hallazgo en el reporte, no se corrige aquí.
$jarVacio = tempnam(sys_get_temp_dir(), 'cookies_vacio_');
[$code, $data] = jsonReq(BASE . '/api/notifications/unread', null, $jarVacio, ['X-AIA-Expect-Json: 1']);
afirmar($code === 401, "GET /api/notifications/unread sin sesión debería responder HTTP 401 (fue $code)");
afirmar(($data['success'] ?? null) === false, 'GET /api/notifications/unread sin sesión debe traer success=false');
afirmar(($data['sessionExpired'] ?? null) === true, 'GET /api/notifications/unread sin sesión debe traer sessionExpired=true');
afirmar(($data['reason'] ?? null) === 'missing_session', 'GET /api/notifications/unread sin sesión debe traer reason=missing_session');
afirmar(($data['redirect'] ?? null) === '/login', 'GET /api/notifications/unread sin sesión debe traer redirect=/login');

[$code, $data] = jsonReq(BASE . '/api/notifications/read', ['id' => '1'], $jarVacio, ['X-AIA-Expect-Json: 1']);
afirmar($code === 401, "POST /api/notifications/read sin sesión debería responder HTTP 401 (fue $code)");
afirmar(($data['reason'] ?? null) === 'missing_session', 'POST /api/notifications/read sin sesión debe traer reason=missing_session');
@unlink($jarVacio);

echo $fallos === 0 ? "OK ($total aserciones)\n" : "FALLOS: $fallos de $total\n";
exit($fallos === 0 ? 0 : 1);
