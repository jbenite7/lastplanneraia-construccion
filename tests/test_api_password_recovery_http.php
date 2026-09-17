<?php

declare(strict_types=1);
// @requiere: http

/**
 * Prueba HTTP de enrutamiento seguro para `POST /api/auth/password/forgot`: solo ejercita
 * caminos que nunca llegan a `PasswordResetService` (CSRF inválido, email inválido, campo de
 * autoridad rechazado, método no permitido). Nunca usa un email válido junto a CSRF válido sin
 * campo extra, porque ese camino sí invocaría el servicio real de correo.
 */

function requestJson(string $method, string $url, string $jar, ?array $body = null, array $headers = []): array
{
    $ch = curl_init($url);
    if ($ch === false) {
        throw new RuntimeException('No se pudo iniciar HTTP S02');
    }
    $responseHeaders = [];
    $httpHeaders = array_merge(['Accept: application/json'], $headers);
    $options = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_COOKIEJAR => $jar,
        CURLOPT_COOKIEFILE => $jar,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HEADERFUNCTION => static function ($handle, string $line) use (&$responseHeaders): int {
            $parts = explode(':', $line, 2);
            if (count($parts) === 2) {
                $responseHeaders[strtolower(trim($parts[0]))] = trim($parts[1]);
            }
            return strlen($line);
        },
    ];
    if ($body !== null) {
        $httpHeaders[] = 'Content-Type: application/json';
        $options[CURLOPT_POSTFIELDS] = json_encode($body, JSON_THROW_ON_ERROR);
    }
    $options[CURLOPT_HTTPHEADER] = $httpHeaders;
    curl_setopt_array($ch, $options);
    $raw = curl_exec($ch);
    if ($raw === false) {
        $error = curl_error($ch);
        curl_close($ch);
        throw new RuntimeException("La aplicación no respondió: {$error}");
    }
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $type = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    curl_close($ch);
    return [
        'code' => $code,
        'type' => $type,
        'headers' => $responseHeaders,
        'json' => json_decode((string) $raw, true),
    ];
}

function check(bool $condition, string $label): void
{
    global $failures;
    echo ($condition ? 'OK: ' : 'FAIL: ') . $label . "\n";
    if (!$condition) {
        $failures++;
    }
}

$base = rtrim(getenv('APP_URL') ?: 'http://127.0.0.1', '/');
$jar = tempnam(sys_get_temp_dir(), 's02_http_');
if ($jar === false) {
    fwrite(STDERR, "No se pudo crear cookie jar S02\n");
    exit(2);
}
$failures = 0;
try {
    $session = requestJson('GET', "{$base}/api/session", $jar);
    $csrf = $session['json']['csrfToken'] ?? '';

    $badCsrf = requestJson('POST', "{$base}/api/auth/password/forgot", $jar, [
        'email' => 'persona@example.test',
    ], ['X-CSRF-Token: invalid']);
    check($badCsrf['code'] === 403 && ($badCsrf['json']['code'] ?? '') === 'csrf_invalid', 'CSRF inválido');

    $badEmail = requestJson('POST', "{$base}/api/auth/password/forgot", $jar, [
        'email' => 'sin-formato',
    ], ['X-CSRF-Token: ' . $csrf]);
    check($badEmail['code'] === 422 && isset($badEmail['json']['fieldErrors']['email']), 'email inválido');
    check(str_contains(strtolower($badEmail['headers']['cache-control'] ?? ''), 'no-store'), 'respuesta no-store');

    $authorityField = requestJson('POST', "{$base}/api/auth/password/forgot", $jar, [
        'email' => 'persona@example.test', 'scope' => 'admin',
    ], ['X-CSRF-Token: ' . $csrf]);
    check($authorityField['code'] === 422, 'scope cliente rechazado');

    $wrongMethod = requestJson('GET', "{$base}/api/auth/password/forgot", $jar);
    check($wrongMethod['code'] === 405, 'GET conserva el 405 controlado del router');
} finally {
    unlink($jar);
}

exit($failures === 0 ? 0 : 1);
