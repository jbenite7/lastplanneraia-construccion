<?php

declare(strict_types=1);
// @requiere: http

/**
 * Contrato HTTP público del login/logout que consume el shell React.
 *
 * Este test debe fallar si se elimina cualquiera de estas garantías: el
 * bootstrap de CSRF en /api/session, rechazo 403 de mutaciones sin token,
 * rechazo no-enumerable de credenciales y cierre efectivo de la sesión.
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
    // intentar autenticar o destruir la sesión.
    $sinToken = requestJson("{$base}/api/auth/login", $galletas, [
        'username' => 'noexiste-shell-api',
        'password' => bin2hex(random_bytes(24)),
    ]);
    comprobar(
        'POST /api/auth/login sin CSRF responde 403 JSON controlado',
        $sinToken['codigo'] === 403 && ($sinToken['json']['success'] ?? null) === false,
    );

    $tokenInvalido = requestJson("{$base}/api/auth/login", $galletas, [
        'username' => 'noexiste-shell-api',
        'password' => bin2hex(random_bytes(24)),
    ], ['X-CSRF-Token: token-invalido']);
    comprobar(
        'POST /api/auth/login con CSRF inválido responde 403 JSON controlado',
        $tokenInvalido['codigo'] === 403 && ($tokenInvalido['json']['success'] ?? null) === false,
    );

    // Un usuario de fixture existente y uno inexistente deben recibir la misma
    // respuesta. Las claves erróneas se generan en tiempo de ejecución: no hay
    // secretos de cuentas dentro del test.
    $headersCsrf = ['X-CSRF-Token: ' . (is_string($csrf) ? $csrf : '')];
    $inexistente = requestJson("{$base}/api/auth/login", $galletas, [
        'username' => 'noexiste-shell-api',
        'password' => bin2hex(random_bytes(24)),
    ], $headersCsrf);
    $existente = requestJson("{$base}/api/auth/login", $galletas, [
        'username' => 'test.A',
        'password' => bin2hex(random_bytes(24)),
    ], $headersCsrf);
    $mensajeInexistente = $inexistente['json']['message'] ?? null;
    $mensajeExistente = $existente['json']['message'] ?? null;
    comprobar(
        'credenciales inválidas reciben una respuesta genérica y no enumeran cuentas',
        $inexistente['codigo'] === 401
            && $existente['codigo'] === 401
            && ($inexistente['json']['success'] ?? null) === false
            && ($existente['json']['success'] ?? null) === false
            && ($inexistente['json']['mustChangePassword'] ?? null) === false
            && ($existente['json']['mustChangePassword'] ?? null) === false
            && is_string($mensajeInexistente)
            && $mensajeInexistente !== ''
            && $mensajeInexistente === $mensajeExistente,
    );

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
