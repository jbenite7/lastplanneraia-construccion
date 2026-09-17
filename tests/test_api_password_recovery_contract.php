<?php

declare(strict_types=1);
// @requiere: puro

/**
 * Contrato puro (sin DB, sin SMTP) de `PasswordRecoveryApiController::request()`: respuesta
 * genérica idéntica para enviado e ignorado, scope fijo `app` sin importar lo que mande el
 * cliente, 503 seguro tanto para el resultado `fallido` como para una excepción del servicio
 * (nunca su detalle en el body), 422 antes de tocar el servicio para forma inválida, y 403
 * cuando el CSRF no valida.
 */

use App\Controllers\Api\PasswordRecoveryApiController;
use App\Security\CsrfTokenManager;
use App\Services\Auth\PasswordResetService;

require_once __DIR__ . '/../vendor/autoload.php';

final class PasswordResetServiceFake extends PasswordResetService
{
    /** @var list<array{0:string,1:string}> */
    public array $calls = [];

    public function __construct(private readonly string|\Throwable $outcome)
    {
    }

    public function request(string $email, string $scope): string
    {
        $this->calls[] = [$email, $scope];
        if ($this->outcome instanceof \Throwable) {
            throw $this->outcome;
        }

        return $this->outcome;
    }
}

/**
 * @return array{0:int,1:array<string,mixed>|null}
 */
function ejecutar(string $body, ?string $csrf, PasswordResetServiceFake $service): array
{
    $_SERVER['HTTP_X_CSRF_TOKEN'] = $csrf ?? '';
    http_response_code(200);
    ob_start();
    (new PasswordRecoveryApiController($service, static fn (): string => $body))->request();
    $raw = (string) ob_get_clean();

    return [http_response_code(), json_decode($raw, true)];
}

function check(bool $condition, string $label): void
{
    global $failures;
    echo ($condition ? 'OK: ' : 'FAIL: ') . $label . "\n";
    if (!$condition) {
        $failures++;
    }
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION = [];
$csrf = CsrfTokenManager::generate('shell_api');
$failures = 0;

// Un buffer de salida envuelve TODO el script (no solo cada llamada a ejecutar()): los `echo`
// de check() cuentan como salida real e, igual que en una respuesta HTTP real, una vez que el
// cuerpo sale de verdad `headers_sent()` queda en true para el resto de la petición — aquí, el
// resto del proceso PHP — y los http_response_code()/header() posteriores dejan de surtir
// efecto en silencio (con solo un warning), atascando el status en el de la primera llamada.
ob_start();

$sent = new PasswordResetServiceFake(PasswordResetService::RESULTADO_ENVIADO);
[$sentStatus, $sentBody] = ejecutar('{"email":" persona@example.test "}', $csrf, $sent);
$ignored = new PasswordResetServiceFake(PasswordResetService::RESULTADO_IGNORADO);
[$ignoredStatus, $ignoredBody] = ejecutar('{"email":"persona@example.test"}', $csrf, $ignored);
check($sentStatus === 200 && $ignoredStatus === 200, 'enviado e ignorado responden 200');
check($sentBody === $ignoredBody, 'enviado e ignorado tienen body idéntico');
check($sent->calls === [['persona@example.test', 'app']], 'email recortado y scope app');

$failed = new PasswordResetServiceFake(PasswordResetService::RESULTADO_FALLIDO);
[$failedStatus, $failedBody] = ejecutar('{"email":"persona@example.test"}', $csrf, $failed);
check($failedStatus === 503 && ($failedBody['code'] ?? '') === 'recovery_unavailable', 'fallido responde 503 seguro');

$exception = new PasswordResetServiceFake(new RuntimeException('smtp secreto'));
[$exceptionStatus, $exceptionBody] = ejecutar('{"email":"persona@example.test"}', $csrf, $exception);
check($exceptionStatus === 503 && !str_contains((string) json_encode($exceptionBody), 'smtp secreto'), 'excepción responde 503 sin detalle');

$invalid = new PasswordResetServiceFake(PasswordResetService::RESULTADO_ENVIADO);
[$invalidStatus, $invalidBody] = ejecutar('{"email":"sin-formato"}', $csrf, $invalid);
check($invalidStatus === 422 && isset($invalidBody['fieldErrors']['email']), 'email inválido responde 422');
check($invalid->calls === [], '422 no llama al servicio');

$authority = new PasswordResetServiceFake(PasswordResetService::RESULTADO_ENVIADO);
[$authorityStatus] = ejecutar('{"email":"persona@example.test","scope":"admin"}', $csrf, $authority);
check($authorityStatus === 422 && $authority->calls === [], 'campo de autoridad se rechaza antes del servicio');

$blocked = new PasswordResetServiceFake(PasswordResetService::RESULTADO_ENVIADO);
[$blockedStatus, $blockedBody] = ejecutar('{"email":"persona@example.test"}', 'csrf-invalido', $blocked);
check($blockedStatus === 403 && ($blockedBody['code'] ?? '') === 'csrf_invalid', 'CSRF inválido responde 403');
check($blocked->calls === [], '403 no llama al servicio');

$brokenJson = new PasswordResetServiceFake(PasswordResetService::RESULTADO_ENVIADO);
[$brokenJsonStatus] = ejecutar('{"email":', $csrf, $brokenJson);
check($brokenJsonStatus === 422 && $brokenJson->calls === [], 'JSON roto responde 422 sin llamar al servicio');

$listPayload = new PasswordResetServiceFake(PasswordResetService::RESULTADO_ENVIADO);
[$listPayloadStatus] = ejecutar('["persona@example.test"]', $csrf, $listPayload);
check($listPayloadStatus === 422 && $listPayload->calls === [], 'body en forma de lista responde 422 sin llamar al servicio');

// Bloque `error` anidado: es el único que lee `frontend/src/lib/api/cliente.ts`. Sin él, la
// pantalla mostraba «/api/auth/password/forgot respondió 403» en vez del mensaje humano. `campos`
// debe ser un objeto `{campo: string}` (esquema `z.record(z.string(), z.string())`): un `[]` de PHP
// rompería el parseo del bloque entero, así que en 403/503 la clave no se emite.
function bloqueErrorValido(?array $body, string $code, bool $conCampos): bool
{
    $error = $body['error'] ?? null;
    if (!is_array($error) || ($error['codigo'] ?? null) !== $code || ($error['mensaje'] ?? null) !== ($body['message'] ?? false)) {
        return false;
    }
    if (!$conCampos) {
        return !array_key_exists('campos', $error);
    }

    return is_array($error['campos'] ?? null) && !array_is_list($error['campos']) && is_string($error['campos']['email'] ?? null);
}
check(bloqueErrorValido($blockedBody, 'csrf_invalid', false), '403 emite bloque error {codigo, mensaje} sin campos');
check(bloqueErrorValido($invalidBody, 'validation_error', true), '422 emite bloque error con campos.email como string');
check(bloqueErrorValido($failedBody, 'recovery_unavailable', false), '503 (fallido) emite bloque error sin campos');
check(bloqueErrorValido($exceptionBody, 'recovery_unavailable', false), '503 (excepción) emite bloque error sin campos');

echo (string) ob_get_clean();
exit($failures === 0 ? 0 : 1);
