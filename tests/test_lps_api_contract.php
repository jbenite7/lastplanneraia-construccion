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
// Sección 3b (T02 Tarea 5, AC-076/080/081/087/088/089/104): hilo tipado — GET aditivo y
// validación de POST que falla ANTES de tocar el repositorio (sin DML: la excepción de
// LpsThreadService::addComment se dispara desde un SELECT, nunca desde el INSERT).
// ---------------------------------------------------------------------------

// unique_id=3 existe en programa_consolidado (Titulo=0, Semana=1) y en programacion_semanal
// para 'PDC Sandbox E2E' (ID 990100) — fixture ya sembrada, no se crea nada aquí.
const ACTIVIDAD_PG_SEMBRADA = 3;

[$code, $data] = jsonReq(BASE . '/api/lps/comments?consecutivo=' . ACTIVIDAD_PG_SEMBRADA . '&modulo=PG', null, $jar);
afirmar($code === 200, "GET comments consecutivo+modulo típico debería responder HTTP 200 (fue $code)");
afirmar(($data['respuesta'] ?? null) === 'OK', 'GET comments consecutivo+modulo trae respuesta=OK');
afirmar(($data['ok'] ?? null) === true, 'GET comments aditivo trae ok=true (T02-AC-080)');
afirmar(is_array($data['data'] ?? null), 'GET comments aditivo conserva "data" para legacy (T02-AC-080)');
afirmar(is_array($data['comments'] ?? null), 'GET comments aditivo trae "comments" tipado para React (T02-AC-081)');
afirmar(is_array($data['target'] ?? null), 'GET comments aditivo trae "target" (T02-AC-081)');
afirmar(($data['target']['kind'] ?? null) === 'activity', 'target.kind es "activity" para consecutivo+modulo');
afirmar(($data['target']['module'] ?? null) === 'PG', 'target.module refleja el módulo resuelto');
afirmar(($data['target']['week'] ?? null) === 1, 'target.week viene del servidor (Semana=1 sembrada), no de la sesión');
afirmar(is_array($data['actions'] ?? null), 'GET comments aditivo trae "actions" (T02-AC-081)');
afirmar(($data['actions']['read'] ?? null) === true, 'actions.read es true para test.R con permiso de ver');
afirmar(!array_key_exists('crisisAlert', $data), 'un target de actividad (no alerta) no trae "crisisAlert"');

[$code, $data] = jsonReq(BASE . '/api/lps/comments?consecutivo=' . ACTIVIDAD_PG_SEMBRADA . '&modulo=ZZ', null, $jar);
afirmar($code === 422, "GET comments con módulo fuera de PG/PI/PS debería responder HTTP 422 (fue $code)");
afirmar(($data['error']['code'] ?? null) === 'VALIDATION_FAILED', 'módulo inválido trae error.code=VALIDATION_FAILED (T02-AC-011)');

[$code, $data] = jsonReq(BASE . '/api/lps/comments?alerta_id=999999999', null, $jar);
afirmar($code === 404, "GET comments con alerta_id inexistente debería responder HTTP 404 (fue $code)");
afirmar(($data['error']['code'] ?? null) === 'LPS_TARGET_NOT_FOUND', 'alerta inexistente trae error.code=LPS_TARGET_NOT_FOUND (T02-AC-079)');
afirmar(($data['ok'] ?? null) === false, 'error tipado trae ok=false');

// Token CSRF real del formulario del drawer (form-key 'lps_drawer'), igual que
// tests/test_csrf_lps_api.php — para probar las validaciones de POST que SÍ pasan el gate de
// CSRF pero fallan después, sin escribir en la base (ninguno de estos casos llega al INSERT).
$total++;
[, $html] = curlReq(BASE . '/programacion-semanal', null, $jar);
if (!preg_match('/<meta name="lps-drawer-csrf-token" content="([a-f0-9]{64})"/', $html, $m)) {
    $fallos++;
    echo "FALLO meta: /programacion-semanal no emite lps-drawer-csrf-token\n";
} else {
    $csrfToken = $m[1];

    // La fixture 'PDC Sandbox E2E' no siembra ninguna fila en `profesionales` (comprobado en
    // vivo: 0 filas para project_id 990100), así que CUALQUIER actor —incluido test.R, que sí
    // tiene lps.programacion_semanal.editar— es PROFILE_REQUIRED aquí. Eso bloquea la escritura
    // ANTES de llegar al repositorio (T02-AC-100), así que target tipado + parent_id/comentario
    // inválidos quedan inalcanzables por HTTP en esta fixture; esa validación de
    // LpsThreadService::addComment queda caracterizada a nivel unitario en
    // tests/unit/LpsThreadServiceTest.php (fakes con actor ya elegible), no aquí.
    [$code, $data] = jsonReq(BASE . '/api/lps/comments/add', [
        'consecutivo' => (string) ACTIVIDAD_PG_SEMBRADA,
        'modulo' => 'PG',
        'comentario' => 'censo t02 — actor sin fila profesionales',
        '_csrf_token' => $csrfToken,
    ], $jar);
    afirmar($code === 409, "POST comments/add con actor sin fila profesionales debería responder HTTP 409 (fue $code, sin DML)");
    afirmar(($data['error']['code'] ?? null) === 'PROFILE_REQUIRED', 'actor incompatible trae error.code=PROFILE_REQUIRED (T02-AC-099/100)');
    afirmar(($data['ok'] ?? null) === false, 'PROFILE_REQUIRED trae ok=false');

    // Camino legacy puro (sin modulo/alerta_id): conserva el mensaje literal histórico aunque
    // ahora también traiga ok/error de forma aditiva (D-T02-08).
    [$code, $data] = jsonReq(BASE . '/api/lps/comments/add', [
        'consecutivo' => '0',
        'comentario' => 'censo t02',
        '_csrf_token' => $csrfToken,
    ], $jar);
    afirmar($code === 200, "POST comments/add legacy puro con consecutivo=0 debería responder HTTP 200 (fue $code)");
    afirmar(($data['mensaje'] ?? null) === 'Comentario y actividad requeridos.', 'mensaje legacy literal se conserva byte a byte (D-T02-08)');
    afirmar(($data['ok'] ?? null) === false, 'el sobre legacy también trae ok=false de forma aditiva');
}

// ---------------------------------------------------------------------------
// Sección 3c (T02 Tarea 6, AC-105..129): crisis/register y crisis/close — validación tipada que
// falla ANTES de tocar el repositorio de escritura (sin DML: `trigger`/`justificacion` inválidos
// se rechazan antes de resolver el target, y un target inexistente sólo dispara un SELECT).
//
// D E L I B E R A D O: esta sección NO prueba el camino de éxito de crisis/register. A diferencia
// de comments/add, `actions.notifyNext` (la puerta de registro) NO exige actor compatible con
// `profesionales` (D-T02-09) — sólo capacidad de edición, que test.R sí tiene. Un
// consecutivo+modulo+trigger válidos aquí SÍ escribiría en la base (INSERT + 2 UPDATE), violando
// la restricción global "sin DDL/DML". Ese camino queda cubierto sólo a nivel unitario, con dobles,
// en tests/unit/LpsCrisisServiceTest.php — ver también task-6-report.md.
// ---------------------------------------------------------------------------

if (isset($csrfToken)) {
    [$code, $data] = jsonReq(BASE . '/api/lps/crisis/register', [
        'consecutivo' => (string) ACTIVIDAD_PG_SEMBRADA,
        'modulo' => 'PG',
        'trigger' => 'AUTO-DESCONOCIDO',
        '_csrf_token' => $csrfToken,
    ], $jar);
    afirmar($code === 422, "POST crisis/register con trigger fuera del enum debería responder HTTP 422 (fue $code)");
    afirmar(($data['error']['code'] ?? null) === 'VALIDATION_FAILED', 'trigger inválido trae error.code=VALIDATION_FAILED (T02-AC-109)');
    afirmar(array_key_exists('trigger', $data['error']['fields'] ?? []), 'trigger inválido señala el campo "trigger" en error.fields');

    [$code, $data] = jsonReq(BASE . '/api/lps/crisis/register', [
        'consecutivo' => (string) ACTIVIDAD_PG_SEMBRADA,
        'modulo' => 'ZZ',
        'trigger' => 'MANUAL',
        '_csrf_token' => $csrfToken,
    ], $jar);
    afirmar($code === 422, "POST crisis/register con módulo fuera de PG/PI/PS debería responder HTTP 422 (fue $code)");
    afirmar(($data['error']['code'] ?? null) === 'VALIDATION_FAILED', 'módulo inválido en crisis/register trae error.code=VALIDATION_FAILED');

    [$code, $data] = jsonReq(BASE . '/api/lps/crisis/register', [
        'alerta_id' => '999999999',
        'trigger' => 'MANUAL',
        '_csrf_token' => $csrfToken,
    ], $jar);
    afirmar($code === 404, "POST crisis/register con alerta_id inexistente debería responder HTTP 404 (fue $code, sin DML: SELECT sin filas)");
    afirmar(($data['error']['code'] ?? null) === 'LPS_TARGET_NOT_FOUND', 'alerta inexistente en crisis/register trae error.code=LPS_TARGET_NOT_FOUND');

    [$code, $data] = jsonReq(BASE . '/api/lps/crisis/close', [
        'alerta_id' => '1',
        'justificacion' => str_repeat('x', 50),
        '_csrf_token' => $csrfToken,
    ], $jar);
    afirmar($code === 422, "POST crisis/close con justificación corta debería responder HTTP 422 (fue $code)");
    afirmar(($data['error']['code'] ?? null) === 'VALIDATION_FAILED', 'justificación corta trae error.code=VALIDATION_FAILED (T02-AC-124)');
    afirmar(array_key_exists('justificacion', $data['error']['fields'] ?? []), 'justificación corta señala el campo "justificacion" en error.fields');

    [$code, $data] = jsonReq(BASE . '/api/lps/crisis/close', [
        'alerta_id' => '999999999',
        'justificacion' => str_repeat('x', 100),
        '_csrf_token' => $csrfToken,
    ], $jar);
    afirmar($code === 404, "POST crisis/close con alerta_id inexistente debería responder HTTP 404 (fue $code, sin DML: SELECT sin filas)");
    afirmar(($data['error']['code'] ?? null) === 'LPS_TARGET_NOT_FOUND', 'alerta inexistente en crisis/close trae error.code=LPS_TARGET_NOT_FOUND');
} else {
    $fallos++;
    echo "FALLO: sección 3c no pudo obtener el token CSRF real (bloque de sección 3b falló antes)\n";
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
