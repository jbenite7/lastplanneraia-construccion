<?php

declare(strict_types=1);
// @requiere: http

/**
 * Contrato HTTP público de `/api/auth/*` que consume el shell React: login, cambio de
 * contraseña obligatorio y su cancelación, más logout.
 *
 * Este test debe fallar si se elimina cualquiera de estas garantías: el bootstrap de CSRF en
 * /api/session, rechazo 403 de mutaciones sin token, forma 422 con `fieldErrors` en validación,
 * rechazo no-enumerable de credenciales, `password_change_not_pending` cuando no hay cambio en
 * curso, cancelación idempotente que no destruye una sesión completa, y cierre efectivo de la
 * sesión.
 *
 * Deliberadamente NO crea usuarios: crear y borrar filas de `general_usuarios` como fixture
 * chocaría con la restricción del frente de no usar DDL/DML como evidencia y con la
 * instrucción explícita de no tocar `usuarios`/credenciales. Los escenarios que necesitan una
 * sesión de cambio de contraseña pendiente o una sesión completa la *forjan* como archivo de
 * sesión PHP (técnica ya usada en tests/test_api_session_contract.php), nunca como fila de
 * base de datos. Las ramas que solo existen con una cuenta `force_password_change=1` —el
 * `next:'password_change'` de login y el `password_change_not_pending` del cambio sobre una
 * cuenta *distinta* a la que forjamos aquí— están cubiertas por
 * tests/unit/AuthApiControllerTest.php con dobles.
 */

$base = rtrim(getenv('APP_URL') ?: 'http://localhost', '/');
$fallos = 0;
$galletas = tempnam(sys_get_temp_dir(), 'auth_api_');

if ($galletas === false) {
    fwrite(STDERR, "ABORT: no se pudo crear el cookie jar temporal\n");
    exit(2);
}

/**
 * @param array<string, mixed>|null $cuerpo
 * @param list<string> $headers
 * @return array{codigo:int,json:array<string,mixed>|null}
 */
function requestJson(string $url, string $galletas, ?array $cuerpo = null, array $headers = []): array
{
    $ch = curl_init($url);
    $httpHeaders = array_merge(['Accept: application/json'], $headers);
    $opciones = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_COOKIEJAR => $galletas,
        CURLOPT_COOKIEFILE => $galletas,
        CURLOPT_HTTPHEADER => $httpHeaders,
    ];

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

    return [
        'codigo' => $codigo,
        'json' => json_decode((string) $respuesta, true),
    ];
}

/**
 * Variante de requestJson() para una cookie forjada (raw `Cookie:` header, sin jar de
 * archivo): la usan los escenarios con sesión pendiente/completa forjada más abajo.
 *
 * @param array<string, mixed>|null $cuerpo
 * @return array{codigo:int,json:array<string,mixed>|null,cookies:string}
 */
function requestJsonConCookie(string $url, string $cookie, ?array $cuerpo = null, array $headers = []): array
{
    $ch = curl_init($url);
    $httpHeaders = array_merge(['Accept: application/json'], $headers);
    $opciones = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_COOKIE => $cookie,
        CURLOPT_HTTPHEADER => $httpHeaders,
    ];

    if ($cuerpo !== null) {
        $httpHeaders[] = 'Content-Type: application/json';
        $opciones[CURLOPT_POST] = true;
        $opciones[CURLOPT_POSTFIELDS] = json_encode($cuerpo, JSON_THROW_ON_ERROR);
        $opciones[CURLOPT_HTTPHEADER] = $httpHeaders;
    }

    curl_setopt_array($ch, $opciones);
    $respuesta = curl_exec($ch);
    $codigo = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $tamanoCabeceras = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);

    $cabeceras = substr((string) $respuesta, 0, $tamanoCabeceras);
    $cuerpoRespuesta = substr((string) $respuesta, $tamanoCabeceras);

    return [
        'codigo' => $codigo,
        'json' => json_decode($cuerpoRespuesta, true),
        'cookies' => $cabeceras,
    ];
}

/**
 * Forja un archivo de sesión PHP con contenido arbitrario, sin pasar por login ni por la base
 * de datos. Misma técnica que tests/test_api_session_contract.php.
 *
 * @param array<string,mixed> $sesion
 */
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

function comprobar(string $descripcion, bool $condicion): void
{
    global $fallos;

    if ($condicion) {
        echo "OK: {$descripcion}\n";
        return;
    }

    $fallos++;
    echo "FALLO: {$descripcion}\n";
}

try {
    // El token se obtiene siempre por la ruta pública de bootstrap; no por un
    // campo HTML ni por un secreto de fixture.
    $sesionInicial = requestJson("{$base}/api/session", $galletas);
    $csrf = $sesionInicial['json']['csrfToken'] ?? null;
    comprobar(
        '/api/session entrega un token CSRF para una sesión anónima',
        $sesionInicial['codigo'] === 200
            && ($sesionInicial['json']['authenticated'] ?? null) === false
            && is_string($csrf)
            && $csrf !== '',
    );

    // Cada mutación rechaza tanto la ausencia como un token ajeno antes de
    // intentar autenticar, cambiar contraseña o cancelar.
    foreach (['/api/auth/login', '/api/auth/password/change', '/api/auth/password/cancel'] as $ruta) {
        $sinToken = requestJson("{$base}{$ruta}", $galletas, []);
        comprobar(
            "POST {$ruta} sin CSRF responde 403 con code=csrf_invalid",
            $sinToken['codigo'] === 403
                && ($sinToken['json']['success'] ?? null) === false
                && ($sinToken['json']['code'] ?? null) === 'csrf_invalid',
        );

        $tokenInvalido = requestJson("{$base}{$ruta}", $galletas, [], ['X-CSRF-Token: token-invalido']);
        comprobar(
            "POST {$ruta} con CSRF inválido responde 403 con code=csrf_invalid",
            $tokenInvalido['codigo'] === 403
                && ($tokenInvalido['json']['success'] ?? null) === false
                && ($tokenInvalido['json']['code'] ?? null) === 'csrf_invalid',
        );
    }

    $headersCsrf = ['X-CSRF-Token: ' . (is_string($csrf) ? $csrf : '')];

    // Forma inválida: los dos campos obligatorios ausentes deben listarse en fieldErrors.
    $vacio = requestJson("{$base}/api/auth/login", $galletas, [], $headersCsrf);
    comprobar(
        'POST /api/auth/login con forma inválida responde 422 validation_error',
        $vacio['codigo'] === 422
            && ($vacio['json']['success'] ?? null) === false
            && ($vacio['json']['code'] ?? null) === 'validation_error'
            && isset($vacio['json']['fieldErrors']['username'])
            && isset($vacio['json']['fieldErrors']['password']),
    );

    // Puente de coexistencia: el mismo error también viaja anidado bajo `error`, que es la
    // forma que `frontend/src/lib/api/cliente.ts` (`pedir()`) realmente lee. Sin este bloque el
    // cliente perdería el fieldErrors entero detrás de un mensaje genérico.
    comprobar(
        'el 422 de /api/auth/login también publica el bloque error.{codigo,mensaje,campos}',
        is_array($vacio['json']['error'] ?? null)
            && ($vacio['json']['error']['codigo'] ?? null) === 'validation_error'
            && ($vacio['json']['error']['mensaje'] ?? null) === ($vacio['json']['message'] ?? null)
            && ($vacio['json']['error']['campos'] ?? null) === ($vacio['json']['fieldErrors'] ?? null)
            && is_string($vacio['json']['error']['campos']['username'] ?? null)
            && is_string($vacio['json']['error']['campos']['password'] ?? null),
    );

    // Un usuario de fixture existente (con clave equivocada generada en tiempo de ejecución,
    // nunca un secreto real) y uno inexistente deben recibir la misma respuesta.
    $inexistente = requestJson("{$base}/api/auth/login", $galletas, [
        'username' => 'noexiste-shell-api',
        'password' => bin2hex(random_bytes(24)),
    ], $headersCsrf);
    $existente = requestJson("{$base}/api/auth/login", $galletas, [
        'username' => 'test.A',
        'password' => bin2hex(random_bytes(24)),
    ], $headersCsrf);
    comprobar(
        'credenciales inválidas responden 401 invalid_credentials y no enumeran cuentas',
        $inexistente['codigo'] === 401
            && $existente['codigo'] === 401
            && ($inexistente['json']['code'] ?? null) === 'invalid_credentials'
            && ($existente['json']['code'] ?? null) === 'invalid_credentials'
            && $inexistente['json'] === $existente['json'],
    );

    // Sin ningún cambio de contraseña en curso, /api/auth/password/change responde
    // password_change_not_pending — sin necesidad de una cuenta force_password_change=1.
    $sinPendiente = requestJson("{$base}/api/auth/password/change", $galletas, [
        'password' => 'Nueva!123',
        'confirmation' => 'Nueva!123',
    ], $headersCsrf);
    comprobar(
        'POST /api/auth/password/change sin cambio pendiente responde 401 password_change_not_pending',
        $sinPendiente['codigo'] === 401
            && ($sinPendiente['json']['success'] ?? null) === false
            && ($sinPendiente['json']['code'] ?? null) === 'password_change_not_pending',
    );

    // Cancelar es idempotente: éxito también cuando no había nada pendiente.
    $cancelSinPendiente = requestJson("{$base}/api/auth/password/cancel", $galletas, [], $headersCsrf);
    comprobar(
        'POST /api/auth/password/cancel sin cambio pendiente responde éxito idempotente',
        $cancelSinPendiente['codigo'] === 200
            && $cancelSinPendiente['json'] === ['success' => true, 'next' => 'login'],
    );

    // --- Escenarios con sesión forjada (sin login real, sin fila en general_usuarios) ---

    // Una sesión de cambio de contraseña pendiente, con una clave nueva que no cumple la
    // política, debe listar los errores por campo sin persistir nada (la validación corta
    // antes del UPDATE).
    $cookiePendiente = sesionArtificial([
        'usuario_temp' => 'test.A',
        'nombreUsuario' => 'Test A',
        'must_change_password' => true,
    ]);
    $bootstrapPendiente = requestJsonConCookie("{$base}/api/session", $cookiePendiente);
    $csrfPendiente = $bootstrapPendiente['json']['csrfToken'] ?? null;
    comprobar(
        'con sesión de cambio pendiente, el bootstrap no expone usuario_temp',
        !array_key_exists('usuario_temp', $bootstrapPendiente['json'] ?? [])
            && ($bootstrapPendiente['json']['state'] ?? null) === 'password_change_required'
            && is_string($csrfPendiente)
            && $csrfPendiente !== '',
    );

    $cambioInvalido = requestJsonConCookie("{$base}/api/auth/password/change", $cookiePendiente, [
        'password' => 'debil',
        'confirmation' => 'debil',
    ], ['X-CSRF-Token: ' . (is_string($csrfPendiente) ? $csrfPendiente : '')]);
    comprobar(
        'cambio de contraseña con forma inválida responde 422 validation_error con fieldErrors',
        $cambioInvalido['codigo'] === 422
            && ($cambioInvalido['json']['success'] ?? null) === false
            && ($cambioInvalido['json']['code'] ?? null) === 'validation_error'
            && isset($cambioInvalido['json']['fieldErrors']['password'])
            && is_string($cambioInvalido['json']['fieldErrors']['password']),
    );

    // Una sesión ya completa (forjada, sin login real) no debe ser destruida por cancelar el
    // cambio de contraseña: cancelar sobre una sesión completa es no-op.
    $cookieCompleta = sesionArtificial([
        'usuario' => 'test.A',
        'nombreUsuario' => 'Test A',
    ]);
    $bootstrapCompleto = requestJsonConCookie("{$base}/api/session", $cookieCompleta);
    $csrfCompleto = $bootstrapCompleto['json']['csrfToken'] ?? null;
    comprobar(
        'la sesión completa forjada arranca autenticada',
        ($bootstrapCompleto['json']['authenticated'] ?? null) === true
            && is_string($csrfCompleto)
            && $csrfCompleto !== '',
    );

    $cancelSobreCompleta = requestJsonConCookie(
        "{$base}/api/auth/password/cancel",
        $cookieCompleta,
        [],
        ['X-CSRF-Token: ' . (is_string($csrfCompleto) ? $csrfCompleto : '')],
    );
    comprobar(
        'cancelar sobre una sesión completa responde éxito idempotente igualmente',
        $cancelSobreCompleta['codigo'] === 200
            && ($cancelSobreCompleta['json']['success'] ?? null) === true,
    );

    $sesionTrasCancelar = requestJsonConCookie("{$base}/api/session", $cookieCompleta);
    comprobar(
        'cancelar sobre una sesión completa NO la destruye',
        ($sesionTrasCancelar['json']['authenticated'] ?? null) === true
            && ($sesionTrasCancelar['json']['user']['username'] ?? null) === 'test.A',
    );

    // --- Logout ---

    $logoutSinToken = requestJson("{$base}/api/auth/logout", $galletas, []);
    comprobar(
        'POST /api/auth/logout sin CSRF responde 403 JSON controlado',
        $logoutSinToken['codigo'] === 403 && ($logoutSinToken['json']['success'] ?? null) === false,
    );

    $logout = requestJson("{$base}/api/auth/logout", $galletas, [], $headersCsrf);
    comprobar(
        'POST /api/auth/logout es idempotente y responde success=true',
        $logout['codigo'] === 200 && ($logout['json']['success'] ?? null) === true,
    );

    $sesionFinal = requestJson("{$base}/api/session", $galletas);
    comprobar(
        'después de logout /api/session reporta una sesión anónima',
        $sesionFinal['codigo'] === 200 && ($sesionFinal['json']['authenticated'] ?? null) === false,
    );
} finally {
    @unlink($galletas);
}

echo $fallos === 0 ? "OK: contrato de /api/auth\n" : "{$fallos} fallo(s)\n";
exit($fallos === 0 ? 0 : 1);
