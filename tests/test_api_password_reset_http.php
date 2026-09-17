<?php

declare(strict_types=1);
// @requiere: http

/**
 * Prueba HTTP de enrutamiento seguro para `POST /api/auth/password/reset/validate` y
 * `POST /api/auth/password/reset`: solo ejercita caminos que nunca llegan a una mutación real
 * (token sintácticamente inválido, CSRF inválido, body inválido, autoridad de cliente rechazada,
 * método no permitido). Nunca envía a `update` un token con el formato válido de 64 hex —
 * cualquier token de ese formato podría existir en la base y activaría una mutación real.
 */

function requestJson(
    string $method,
    string $url,
    string $jar,
    array|string|null $body = null,
    array $headers = [],
): array {
    $curl = curl_init($url);
    if ($curl === false) {
        throw new RuntimeException('No se pudo iniciar HTTP S03');
    }
    $responseHeaders = [];
    $requestHeaders = array_merge(['Accept: application/json'], $headers);
    $options = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_COOKIEJAR => $jar,
        CURLOPT_COOKIEFILE => $jar,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_HEADERFUNCTION => static function ($handle, string $line) use (&$responseHeaders): int {
            $parts = explode(':', $line, 2);
            if (count($parts) === 2) {
                $responseHeaders[strtolower(trim($parts[0]))] = trim($parts[1]);
            }

            return strlen($line);
        },
    ];
    if ($body !== null) {
        $requestHeaders[] = 'Content-Type: application/json';
        $options[CURLOPT_POSTFIELDS] = is_string($body)
            ? $body
            : json_encode($body, JSON_THROW_ON_ERROR);
    }
    $options[CURLOPT_HTTPHEADER] = $requestHeaders;
    curl_setopt_array($curl, $options);
    $raw = curl_exec($curl);
    if ($raw === false) {
        $error = curl_error($curl);
        curl_close($curl);
        throw new RuntimeException("La aplicación no respondió: {$error}");
    }
    $result = [
        'code' => (int) curl_getinfo($curl, CURLINFO_HTTP_CODE),
        'type' => (string) curl_getinfo($curl, CURLINFO_CONTENT_TYPE),
        'headers' => $responseHeaders,
        'json' => json_decode((string) $raw, true),
    ];
    curl_close($curl);

    return $result;
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
$jar = tempnam(sys_get_temp_dir(), 's03_http_');
if ($jar === false) {
    fwrite(STDERR, "No se pudo crear cookie jar S03\n");
    exit(2);
}
$failures = 0;
try {
    $session = requestJson('GET', "{$base}/api/session", $jar);
    $csrf = is_string($session['json']['csrfToken'] ?? null) ? $session['json']['csrfToken'] : '';
    $invalidToken = 'not-a-token';

    $invalid = requestJson('POST', "{$base}/api/auth/password/reset/validate", $jar,
        ['token' => $invalidToken], ['X-CSRF-Token: ' . $csrf]);
    check($invalid['code'] === 200 && ($invalid['json']['state'] ?? '') === 'invalid', 'token mal formado no consulta DB');
    check(str_contains(strtolower($invalid['headers']['cache-control'] ?? ''), 'no-store'), 'validate no-store');
    check(strtolower($invalid['headers']['x-content-type-options'] ?? '') === 'nosniff', 'validate nosniff');

    $extra = requestJson('POST', "{$base}/api/auth/password/reset/validate", $jar,
        ['token' => $invalidToken, 'scope' => 'admin'], ['X-CSRF-Token: ' . $csrf]);
    check($extra['code'] === 422, 'validate rechaza autoridad cliente');

    $brokenJson = requestJson('POST', "{$base}/api/auth/password/reset/validate", $jar,
        '{token: not-json}', ['X-CSRF-Token: ' . $csrf]);
    check($brokenJson['code'] === 422, 'validate rechaza JSON roto');

    $listBody = requestJson('POST', "{$base}/api/auth/password/reset/validate", $jar,
        '["token"]', ['X-CSRF-Token: ' . $csrf]);
    check($listBody['code'] === 422, 'validate rechaza body tipo lista');

    $missingToken = requestJson('POST', "{$base}/api/auth/password/reset/validate", $jar,
        [], ['X-CSRF-Token: ' . $csrf]);
    check($missingToken['code'] === 422, 'validate exige el campo token');

    $badCsrfValidate = requestJson('POST', "{$base}/api/auth/password/reset/validate", $jar,
        ['token' => $invalidToken], ['X-CSRF-Token: invalid']);
    check($badCsrfValidate['code'] === 403 && ($badCsrfValidate['json']['code'] ?? '') === 'csrf_invalid', 'validate exige CSRF');

    $badCsrf = requestJson('POST', "{$base}/api/auth/password/reset", $jar, [
        'token' => $invalidToken, 'password' => 'Abcdef!', 'confirmPassword' => 'Abcdef!',
    ], ['X-CSRF-Token: invalid']);
    check($badCsrf['code'] === 403 && ($badCsrf['json']['code'] ?? '') === 'csrf_invalid', 'update exige CSRF');

    $invalidUpdate = requestJson('POST', "{$base}/api/auth/password/reset", $jar, [
        'token' => $invalidToken, 'password' => 'Abcdef!', 'confirmPassword' => 'Abcdef!',
    ], ['X-CSRF-Token: ' . $csrf]);
    check($invalidUpdate['code'] === 410 && ($invalidUpdate['json']['code'] ?? '') === 'reset_link_invalid', 'update corta token sintáctico');

    $missingFieldsUpdate = requestJson('POST', "{$base}/api/auth/password/reset", $jar,
        ['token' => $invalidToken], ['X-CSRF-Token: ' . $csrf]);
    check($missingFieldsUpdate['code'] === 422, 'update exige los tres campos');

    $wrongMethod = requestJson('GET', "{$base}/api/auth/password/reset/validate", $jar);
    check($wrongMethod['code'] === 405, 'método incorrecto conserva el 405 del router en validate');

    $wrongMethodUpdate = requestJson('GET', "{$base}/api/auth/password/reset", $jar);
    check($wrongMethodUpdate['code'] === 405, 'método incorrecto conserva el 405 del router en update');
} finally {
    unlink($jar);
}

exit($failures === 0 ? 0 : 1);
