<?php
declare(strict_types=1);
// @requiere: datos-proyecto

// T02 Tarea 9 (docs/superpowers/plans/2026-08-30-t02-contexto-lps-react.md, brief
// task-9-brief.md): congela el contrato HTTP de NotificationController tras la tarea —
// CSRF nuevo en markAsRead, sobre aditivo `ok`, ausencia de `project_id`, validación de
// ID positivo y el 401 de SessionMiddleware — igual que test_lps_api_contract.php hace
// para LpsApiController, pero autocontenido en su propio archivo.
//
// Restricción global del plan: sin DDL/DML. `system_notifications` YA trae filas no
// leídas sembradas para varios usuarios de prueba (fixture del worktree, no creadas por
// este archivo) — este test las LEE (GET /unread) pero nunca ejecuta un `markAsRead` que
// pase el gate de CSRF + ID positivo, porque eso dispararía un UPDATE real (0 o 1 fila,
// pero UPDATE es DML de todas formas). Los casos "id ajeno/ya leído sigue siendo
// idempotente" (T02-AC-141) y "el predicado usa el usuario de sesión" (AC-140) los cubre
// tests/unit/NotificationInboxServiceTest.php con un repositorio fake, sin tocar la base.

const BASE = 'http://localhost';
const PROYECTO = 'PDC Sandbox E2E';

function sesion(string $usuario, bool $conProyecto = true): string
{
    $jar = tempnam(sys_get_temp_dir(), 'cookies_');
    $url = BASE . '/dev/entrar?u=' . urlencode($usuario) . ($conProyecto ? '&p=' . urlencode(PROYECTO) : '');
    [$code] = curlReq($url, null, $jar, []);
    if (!in_array($code, [200, 302], true)) {
        fwrite(STDERR, "ABORT: dev door cerrada (HTTP $code). Revisa DEV_DOOR en .env\n");
        exit(2);
    }
    return $jar;
}

/** @return array{0:int,1:string} */
function curlReq(string $url, ?array $post, string $jar, array $headers = []): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_COOKIEJAR => $jar, CURLOPT_COOKIEFILE => $jar,
        CURLOPT_HTTPHEADER => $headers,
    ]);
    if ($post !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post));
    }
    $body = (string) curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$code, $body];
}

/** @return array{0:int,1:array<string,mixed>} */
function jsonReq(string $url, ?array $post, string $jar, array $headers = []): array
{
    [$code, $body] = curlReq($url, $post, $jar, $headers);
    $decoded = json_decode($body, true);
    if (!is_array($decoded)) {
        fwrite(STDERR, "ABORT: respuesta no-JSON de $url (HTTP $code): " . substr($body, 0, 200) . "\n");
        exit(2);
    }
    return [$code, $decoded];
}

function csrfTokenReal(string $jar): string
{
    [$code, $data] = jsonReq(BASE . '/api/session', null, $jar);
    if ($code !== 200 || !is_string($data['csrfToken'] ?? null)) {
        fwrite(STDERR, "ABORT: no se pudo obtener csrfToken real de /api/session (HTTP $code)\n");
        exit(2);
    }
    return $data['csrfToken'];
}

$fallos = 0; $total = 0;
function afirmar(bool $condicion, string $mensaje): void
{
    global $fallos, $total;
    $total++;
    if (!$condicion) {
        $fallos++;
        echo "FALLO: $mensaje\n";
    }
}

// ---------------------------------------------------------------------------
// Sección 1: sin sesión — SessionMiddleware intercepta antes del controlador (T02-AC-137)
// ---------------------------------------------------------------------------

$jarVacio = tempnam(sys_get_temp_dir(), 'cookies_vacio_');
[$code, $data] = jsonReq(BASE . '/api/notifications/unread', null, $jarVacio, ['X-AIA-Expect-Json: 1']);
afirmar($code === 401, "GET /api/notifications/unread sin sesión debería responder HTTP 401 (fue $code)");
afirmar(($data['success'] ?? null) === false, 'sin sesión: success=false');
afirmar(($data['sessionExpired'] ?? null) === true, 'sin sesión: sessionExpired=true');
afirmar(($data['reason'] ?? null) === 'missing_session', 'sin sesión: reason=missing_session');

[$code, $data] = jsonReq(BASE . '/api/notifications/read', ['id' => 1], $jarVacio, ['X-AIA-Expect-Json: 1']);
afirmar($code === 401, "POST /api/notifications/read sin sesión debería responder HTTP 401 (fue $code)");
@unlink($jarVacio);

// ---------------------------------------------------------------------------
// Sección 2: GET /api/notifications/unread autenticado — forma, sin project_id, sin
// ProjectScope (T02-AC-137/142/143/179)
// ---------------------------------------------------------------------------

$jar = sesion('test.R');

[$code, $data] = jsonReq(BASE . '/api/notifications/unread', null, $jar);
afirmar($code === 200, "GET /api/notifications/unread autenticado debería responder HTTP 200 (fue $code)");
afirmar(($data['success'] ?? null) === true, 'GET unread trae success=true');
afirmar(($data['ok'] ?? null) === true, 'GET unread trae el aditivo ok=true (T02-AC-137)');
afirmar(is_array($data['data'] ?? null), 'GET unread trae "data" como arreglo');

$filas = $data['data'] ?? [];
afirmar(count($filas) > 0, 'la fixture del worktree trae notificaciones no leídas sembradas para test.R (no se crean aquí)');
foreach ($filas as $i => $fila) {
    afirmar(!array_key_exists('project_id', $fila), "fila $i de unread no debe traer project_id (T02-AC-142)");
    foreach (['id', 'type', 'title', 'message', 'item_count', 'created_at'] as $campo) {
        afirmar(array_key_exists($campo, $fila), "fila $i de unread debe traer el campo \"$campo\" (T02-AC-143)");
    }
    afirmar(is_int($fila['id']) && $fila['id'] > 0, "fila $i de unread trae id entero positivo");
}

// AC-179/D-T02-12: la bandeja es de identidad, no de proyecto — sigue respondiendo sin
// ProjectScope activo (sesión autenticada, ningún proyecto elegido todavía).
$jarSinProyecto = sesion('test.R', conProyecto: false);
[$code, $data] = jsonReq(BASE . '/api/notifications/unread', null, $jarSinProyecto);
afirmar($code === 200, "GET unread sin proyecto activo debería responder HTTP 200 igual (fue $code) — no exige ProjectScope");
afirmar(($data['ok'] ?? null) === true, 'GET unread sin proyecto activo sigue trayendo ok=true');
afirmar(is_array($data['data'] ?? null) && count($data['data']) === count($filas), 'GET unread sin proyecto activo trae la misma bandeja de identidad (no depende del proyecto)');

// ---------------------------------------------------------------------------
// Sección 3: POST /api/notifications/read — CSRF antes que cualquier otra cosa (T02-AC-139)
// ---------------------------------------------------------------------------

[$code, $data] = jsonReq(BASE . '/api/notifications/read', ['id' => 1], $jar);
afirmar($code === 403, "POST read sin _csrf_token debería responder HTTP 403 (fue $code)");
afirmar(($data['success'] ?? null) === false, 'POST read sin CSRF trae success=false');
afirmar(($data['ok'] ?? null) === false, 'POST read sin CSRF trae ok=false');
afirmar(($data['error']['code'] ?? null) === 'CSRF_INVALID', 'POST read sin CSRF trae error.code=CSRF_INVALID');

[$code, $data] = jsonReq(BASE . '/api/notifications/read', ['id' => 1], $jar, ['X-CSRF-Token: token-falso-de-otra-sesion']);
afirmar($code === 403, "POST read con CSRF inválido debería responder HTTP 403 (fue $code)");
afirmar(($data['error']['code'] ?? null) === 'CSRF_INVALID', 'POST read con CSRF ajeno también trae CSRF_INVALID');

// El token real de otra sesión (test.A) tampoco vale para la de test.R — CsrfTokenManager
// firma por sesión, no por form-key global.
$jarOtraSesion = sesion('test.A');
$tokenDeOtraSesion = csrfTokenReal($jarOtraSesion);
[$code, $data] = jsonReq(BASE . '/api/notifications/read', ['id' => 1], $jar, ["X-CSRF-Token: $tokenDeOtraSesion"]);
afirmar($code === 403, "POST read con el CSRF real de OTRA sesión debería responder HTTP 403 (fue $code)");
afirmar(($data['error']['code'] ?? null) === 'CSRF_INVALID', 'CSRF de otra sesión trae CSRF_INVALID, no se acepta cruzado');

// ---------------------------------------------------------------------------
// Sección 4: POST /api/notifications/read con CSRF real — validación de ID ANTES del
// repositorio (T02-AC-140). Ninguno de estos casos ejecuta el UPDATE: la validación de
// forma corta el flujo antes de llamar a NotificationService::markAsRead().
// ---------------------------------------------------------------------------

$csrfToken = csrfTokenReal($jar);

foreach ([0, -1, -999999999] as $idInvalido) {
    [$code, $data] = jsonReq(BASE . '/api/notifications/read', ['id' => $idInvalido], $jar, ["X-CSRF-Token: $csrfToken"]);
    afirmar($code === 400, "POST read con id=$idInvalido (CSRF válido) debería responder HTTP 400 (fue $code)");
    afirmar(($data['success'] ?? null) === false, "POST read con id=$idInvalido trae success=false");
    afirmar(($data['error']['code'] ?? null) === 'VALIDATION_FAILED', "POST read con id=$idInvalido trae error.code=VALIDATION_FAILED");
}

[$code, $data] = jsonReq(BASE . '/api/notifications/read', ['id' => 'no-es-un-id'], $jar, ["X-CSRF-Token: $csrfToken"]);
afirmar($code === 400, "POST read con id no numérico (CSRF válido) debería responder HTTP 400 (fue $code)");
afirmar(($data['error']['code'] ?? null) === 'VALIDATION_FAILED', 'POST read con id no numérico trae error.code=VALIDATION_FAILED');

[$code, $data] = jsonReq(BASE . '/api/notifications/read', [], $jar, ["X-CSRF-Token: $csrfToken"]);
afirmar($code === 400, "POST read sin campo id (CSRF válido) debería responder HTTP 400 (fue $code)");
afirmar(($data['error']['code'] ?? null) === 'VALIDATION_FAILED', 'POST read sin campo id trae error.code=VALIDATION_FAILED');

echo $fallos === 0 ? "OK ($total aserciones)\n" : "FALLOS: $fallos de $total\n";
exit($fallos === 0 ? 0 : 1);
