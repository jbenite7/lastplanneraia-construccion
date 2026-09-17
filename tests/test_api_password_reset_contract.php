<?php

declare(strict_types=1);
// @requiere: puro

/**
 * Contrato puro (sin DB, sin SMTP) de `PasswordResetApiController`: `validateLink()` y `update()`.
 *
 * Garantías: scope fijo `app`; token con formato inválido rechazado antes del servicio; enlace
 * inválido/vencido/usado sin revelar identidad; errores de política del servicio → 422 con
 * `error.campos` `{password|confirmPassword: string}`; 410 idéntico para enlace inválido y usuario
 * inexistente; 503 seguro (sin detalle interno) para fallas y excepciones; CSRF `shell_api` y forma
 * exacta del body antes de tocar el servicio. Además contrasta los cuerpos de error reales contra
 * `tests/fixtures/api-password-reset-error-bodies.json` (lo lee `error.contrato.test.ts`); se
 * regenera solo con `LPS_REGENERAR_CUERPOS=1`.
 */

use App\Controllers\Api\PasswordResetApiController;
use App\Security\CsrfTokenManager;
use App\Services\Auth\PasswordResetService;

require_once __DIR__ . '/../vendor/autoload.php';

const TOKEN = 'a3f1c2d4e5b60718293a4b5c6d7e8f90a1b2c3d4e5f60718293a4b5c6d7e8f90';
const MENSAJE_INVALIDO = 'El enlace no es válido o ya expiró. Solicita uno nuevo.';

final class PasswordResetServiceFake extends PasswordResetService
{
    /** @var list<array{0:string,1:string}> */
    public array $findCalls = [];
    /** @var list<array{0:string,1:string,2:string,3:string}> */
    public array $resetCalls = [];

    /**
     * @param array<string,mixed>|\Throwable|null $tokenResult
     * @param array<string,mixed>|\Throwable $resetResult
     */
    public function __construct(
        private readonly array|\Throwable|null $tokenResult = null,
        private readonly array|\Throwable $resetResult = ['success' => false, 'message' => 'unused'],
    ) {
    }

    public function findValidToken(string $plainToken, string $scope): ?array
    {
        $this->findCalls[] = [$plainToken, $scope];
        if ($this->tokenResult instanceof \Throwable) {
            throw $this->tokenResult;
        }

        return $this->tokenResult;
    }

    public function reset(string $plainToken, string $scope, string $password, string $confirm): array
    {
        $this->resetCalls[] = [$plainToken, $scope, $password, $confirm];
        if ($this->resetResult instanceof \Throwable) {
            throw $this->resetResult;
        }

        return $this->resetResult;
    }

    public function totalCalls(): int
    {
        return count($this->findCalls) + count($this->resetCalls);
    }
}

/**
 * @return array{0:int,1:array<string,mixed>|null,2:string}
 */
function ejecutar(string $method, string $body, ?string $csrf, PasswordResetServiceFake $service): array
{
    $_SERVER['HTTP_X_CSRF_TOKEN'] = $csrf ?? '';
    http_response_code(200);
    ob_start();
    (new PasswordResetApiController($service, static fn (): string => $body))->{$method}();
    $raw = (string) ob_get_clean();

    return [(int) http_response_code(), json_decode($raw, true), $raw];
}

function check(bool $condition, string $label): void
{
    global $failures;
    echo ($condition ? 'OK: ' : 'FAIL: ') . $label . "\n";
    if (!$condition) {
        $failures++;
    }
}

/**
 * Bloque `error` anidado que lee `frontend/src/lib/api/cliente.ts`; `campos` solo si hay campos y
 * nunca claves en `null`.
 *
 * @param array<string,mixed>|null $body
 */
function bloqueError(?array $body, string $code, ?string $campo): bool
{
    $error = $body['error'] ?? null;
    if (!is_array($error) || ($error['codigo'] ?? null) !== $code || ($error['mensaje'] ?? null) !== ($body['message'] ?? false)) {
        return false;
    }
    if (($body['success'] ?? null) !== false || ($body['code'] ?? null) !== $code) {
        return false;
    }
    if ($campo === null) {
        return !array_key_exists('campos', $error) && !array_key_exists('fieldErrors', $body);
    }

    return is_array($error['campos'] ?? null)
        && array_keys($error['campos']) === [$campo]
        && is_string($error['campos'][$campo]);
}

function sinIdentidad(string $raw): bool
{
    foreach (['usuario', 'email', 'nombre', 'user_id', 'token_id', 'persona', TOKEN] as $fuga) {
        if (str_contains($raw, $fuga)) {
            return false;
        }
    }

    return true;
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION = [];
$csrf = CsrfTokenManager::generate('shell_api');
$failures = 0;
$capturas = [];

// Buffer externo: sin él, el primer `echo` de check() deja `headers_sent()` en true y los
// http_response_code() siguientes no surten efecto (ver test_api_password_recovery_contract.php).
ob_start();

$fila = ['token_id' => 7, 'user_id' => 9, 'usuario' => 'persona.x', 'nombre' => 'Persona X', 'email' => 'persona@example.test', 'activo' => 1];
$validateBody = json_encode(['token' => TOKEN]);
$updateBody = static fn (string $password, string $confirm): string => (string) json_encode(['token' => TOKEN, 'password' => $password, 'confirmPassword' => $confirm]);

// --- validateLink ---------------------------------------------------------------------------
$valido = new PasswordResetServiceFake($fila);
[$s, $b, $raw] = ejecutar('validateLink', (string) $validateBody, $csrf, $valido);
check($s === 200 && $b === ['success' => true, 'state' => 'valid'], 'validate: token vigente responde 200 state=valid exacto');
check($valido->findCalls === [[TOKEN, 'app']] && $valido->resetCalls === [], 'validate: un findValidToken(TOKEN, app)');
check(sinIdentidad($raw), 'validate: vigente no revela identidad ni token');

$nulo = new PasswordResetServiceFake(null);
[$s, $b, $raw] = ejecutar('validateLink', (string) $validateBody, $csrf, $nulo);
$cuerpoInvalido = ['success' => true, 'state' => 'invalid', 'message' => MENSAJE_INVALIDO];
check($s === 200 && $b === $cuerpoInvalido, 'validate: token no vigente responde 200 invalid exacto');
check(count($nulo->findCalls) === 1 && sinIdentidad($raw), 'validate: invalid con una sola consulta y sin identidad');

$recortado = new PasswordResetServiceFake($fila);
[$s, $b] = ejecutar('validateLink', '{"token":"  ' . TOKEN . ' "}', $csrf, $recortado);
check($s === 200 && $recortado->findCalls === [[TOKEN, 'app']], 'validate: token recortado antes del servicio');

foreach (['corto' => 'abc123', 'mayusculas' => strtoupper(TOKEN), 'no-hex' => str_repeat('z', 64), 'salto' => TOKEN . "\n0"] as $etiqueta => $malo) {
    $formato = new PasswordResetServiceFake($fila);
    [$s, $b, $raw] = ejecutar('validateLink', (string) json_encode(['token' => $malo]), $csrf, $formato);
    check($s === 200 && $b === $cuerpoInvalido && $formato->totalCalls() === 0, "validate: token con formato inválido ({$etiqueta}) responde invalid sin llamar al servicio");
}

$caido = new PasswordResetServiceFake(new RuntimeException('SQLSTATE secreto ' . TOKEN));
[$s, $b, $raw] = ejecutar('validateLink', (string) $validateBody, $csrf, $caido);
check($s === 503 && bloqueError($b, 'reset_unavailable', null), 'validate: excepción responde 503 reset_unavailable con bloque error');
check(!str_contains($raw, 'SQLSTATE') && !str_contains($raw, TOKEN), 'validate: 503 sin detalle interno ni token');
$capturas['503_reset_validate_unavailable'] = ['ruta' => '/api/auth/password/reset/validate', 'status' => $s, 'raw' => $raw];

$csrfMalo = new PasswordResetServiceFake($fila);
[$s, $b, $raw] = ejecutar('validateLink', (string) $validateBody, 'csrf-invalido', $csrfMalo);
check($s === 403 && bloqueError($b, 'csrf_invalid', null) && $csrfMalo->totalCalls() === 0, 'validate: CSRF inválido responde 403 sin llamar al servicio');

$csrfVacio = new PasswordResetServiceFake($fila);
[$s] = ejecutar('validateLink', (string) $validateBody, null, $csrfVacio);
check($s === 403 && $csrfVacio->totalCalls() === 0, 'validate: sin CSRF responde 403 sin llamar al servicio');

foreach ([
    'clave extra' => '{"token":"' . TOKEN . '","scope":"admin"}',
    'sin token' => '{}',
    'token no string' => '{"token":123}',
    'lista' => '["' . TOKEN . '"]',
    'JSON roto' => '{"token":',
    'vacío' => '',
] as $etiqueta => $cuerpo) {
    $forma = new PasswordResetServiceFake($fila);
    [$s, $b, $raw] = ejecutar('validateLink', $cuerpo, $csrf, $forma);
    check($s === 422 && bloqueError($b, 'validation_error', 'token') && $forma->totalCalls() === 0, "validate: forma inválida ({$etiqueta}) responde 422 sin llamar al servicio");
    check(!str_contains($raw, TOKEN), "validate: 422 ({$etiqueta}) no refleja el token");
}

// --- update ---------------------------------------------------------------------------------
$exito = new PasswordResetServiceFake(null, ['success' => true, 'message' => 'Contraseña restablecida correctamente.']);
[$s, $b, $raw] = ejecutar('update', $updateBody('Abcdef!', 'Abcdef!'), $csrf, $exito);
check($s === 200 && $b === ['success' => true, 'message' => 'Contraseña restablecida correctamente.', 'redirect' => '/login?reset=1'], 'update: éxito responde 200 con mensaje y redirect exactos');
check($exito->resetCalls === [[TOKEN, 'app', 'Abcdef!', 'Abcdef!']] && $exito->findCalls === [], 'update: un reset(TOKEN, app, Abcdef!, Abcdef!)');
check(sinIdentidad($raw), 'update: éxito sin identidad ni token');

$invertido = new PasswordResetServiceFake(null, ['success' => true, 'message' => 'Contraseña restablecida correctamente.']);
[$s] = ejecutar('update', '{"confirmPassword":"Abcdef!","password":"Abcdef!","token":"' . TOKEN . '"}', $csrf, $invertido);
check($s === 200 && $invertido->resetCalls === [[TOKEN, 'app', 'Abcdef!', 'Abcdef!']], 'update: claves en otro orden llegan al servicio');

$espacios = new PasswordResetServiceFake(null, ['success' => true, 'message' => 'Contraseña restablecida correctamente.']);
[$s] = ejecutar('update', $updateBody(' Abcdef! ', ' Abcdef! '), $csrf, $espacios);
check($espacios->resetCalls === [[TOKEN, 'app', ' Abcdef! ', ' Abcdef! ']], 'update: las contraseñas no se recortan');

$politica = [
    'longitud' => ['La contraseña debe tener al menos 6 caracteres', 'password', '422_reset_password_validation_error'],
    'mayuscula' => ['Debe contener al menos una letra mayúscula', 'password', null],
    'especial' => ['Debe contener al menos un carácter especial (!@#$%...)', 'password', null],
    'coincidencia' => ['Las contraseñas no coinciden', 'confirmPassword', '422_reset_confirm_validation_error'],
    'anterior' => ['La nueva contraseña no puede ser igual a la anterior', 'password', null],
];
foreach ($politica as $etiqueta => [$mensaje, $campo, $captura]) {
    $fake = new PasswordResetServiceFake(null, [
        'success' => false,
        'message' => $mensaje,
        'fieldErrors' => [$campo === 'confirmPassword' ? 'confirmation' : 'password' => [$mensaje]],
    ]);
    [$s, $b, $raw] = ejecutar('update', $updateBody('abc', 'abd'), $csrf, $fake);
    check(
        $s === 422 && bloqueError($b, 'validation_error', $campo) && ($b['error']['campos'][$campo] ?? null) === $mensaje && ($b['message'] ?? null) === $mensaje,
        "update: política {$etiqueta} responde 422 con error.campos.{$campo}",
    );
    check(count($fake->resetCalls) === 1, "update: política {$etiqueta} consulta el servicio una vez");
    check(!str_contains($raw, 'confirmation') && !str_contains($raw, TOKEN), "update: política {$etiqueta} sin clave confirmation ni token");
    if ($captura !== null) {
        $capturas[$captura] = ['ruta' => '/api/auth/password/reset', 'status' => $s, 'raw' => $raw];
    }
}

$enlaceInvalido = new PasswordResetServiceFake(null, ['success' => false, 'message' => MENSAJE_INVALIDO]);
[$s1, $b1, $raw1] = ejecutar('update', $updateBody('Abcdef!', 'Abcdef!'), $csrf, $enlaceInvalido);
$sinUsuario = new PasswordResetServiceFake(null, ['success' => false, 'message' => 'Usuario no encontrado', 'fieldErrors' => []]);
[$s2, $b2, $raw2] = ejecutar('update', $updateBody('Abcdef!', 'Abcdef!'), $csrf, $sinUsuario);
check($s1 === 410 && bloqueError($b1, 'reset_link_invalid', null) && ($b1['message'] ?? null) === MENSAJE_INVALIDO, 'update: enlace inválido responde 410 reset_link_invalid');
check($s2 === 410 && $raw1 === $raw2, 'update: usuario inexistente responde 410 byte a byte idéntico');
check(count($enlaceInvalido->resetCalls) === 1 && count($sinUsuario->resetCalls) === 1, 'update: 410 con un reset cada uno');
check(sinIdentidad($raw1), 'update: 410 sin identidad ni token');
$capturas['410_reset_link_invalid'] = ['ruta' => '/api/auth/password/reset', 'status' => $s1, 'raw' => $raw1];

$formatoMalo = new PasswordResetServiceFake(null, ['success' => true, 'message' => 'x']);
[$s, $b, $raw] = ejecutar('update', (string) json_encode(['token' => 'no-es-hex', 'password' => 'Abcdef!', 'confirmPassword' => 'Abcdef!']), $csrf, $formatoMalo);
check($s === 410 && $raw === $raw1 && $formatoMalo->totalCalls() === 0, 'update: token con formato inválido responde el mismo 410 sin llamar al servicio');

$indisponibles = [
    'almacenamiento' => ['success' => false, 'message' => 'Error al actualizar la contraseña.', 'fieldErrors' => []],
    'desconocido' => ['success' => false, 'message' => 'algo raro interno'],
    'sin mensaje' => ['success' => false],
    'excepción' => new RuntimeException('SQLSTATE secreto ' . TOKEN),
];
$raw503 = null;
foreach ($indisponibles as $etiqueta => $resultado) {
    $fake = new PasswordResetServiceFake(null, $resultado);
    [$s, $b, $raw] = ejecutar('update', $updateBody('Abcdef!', 'Abcdef!'), $csrf, $fake);
    check($s === 503 && bloqueError($b, 'reset_unavailable', null) && count($fake->resetCalls) === 1, "update: {$etiqueta} responde 503 reset_unavailable");
    check(!str_contains($raw, 'SQLSTATE') && !str_contains($raw, 'algo raro') && !str_contains($raw, TOKEN), "update: {$etiqueta} sin detalle interno ni token");
    check($raw503 === null || $raw503 === $raw, "update: {$etiqueta} con body 503 idéntico");
    $raw503 ??= $raw;
}
$capturas['503_reset_unavailable'] = ['ruta' => '/api/auth/password/reset', 'status' => 503, 'raw' => (string) $raw503];

$csrfUpdate = new PasswordResetServiceFake(null, ['success' => true, 'message' => 'x']);
[$s, $b, $raw] = ejecutar('update', $updateBody('Abcdef!', 'Abcdef!'), 'csrf-invalido', $csrfUpdate);
check($s === 403 && bloqueError($b, 'csrf_invalid', null) && $csrfUpdate->totalCalls() === 0, 'update: CSRF inválido responde 403 sin llamar al servicio');
$capturas['403_csrf_invalid'] = ['ruta' => '/api/auth/password/reset', 'status' => $s, 'raw' => $raw];

foreach ([
    'clave extra scope' => '{"token":"' . TOKEN . '","password":"Abcdef!","confirmPassword":"Abcdef!","scope":"admin"}',
    'falta confirmPassword' => '{"token":"' . TOKEN . '","password":"Abcdef!"}',
    'confirmation legado' => '{"token":"' . TOKEN . '","password":"Abcdef!","confirmation":"Abcdef!"}',
    'password no string' => '{"token":"' . TOKEN . '","password":123456,"confirmPassword":"Abcdef!"}',
    'lista' => '["' . TOKEN . '","Abcdef!","Abcdef!"]',
    'JSON roto' => '{"token":',
] as $etiqueta => $cuerpo) {
    $forma = new PasswordResetServiceFake(null, ['success' => true, 'message' => 'x']);
    [$s, $b, $raw] = ejecutar('update', $cuerpo, $csrf, $forma);
    check($s === 422 && bloqueError($b, 'validation_error', null) && $forma->totalCalls() === 0, "update: forma inválida ({$etiqueta}) responde 422 sin llamar al servicio");
    check(!str_contains($raw, TOKEN) && !str_contains($raw, 'Abcdef!'), "update: 422 ({$etiqueta}) no refleja token ni contraseña");
}

// --- Contrato PHP↔Zod: cuerpos de error reales, render en proceso (origen render-puro) --------
ksort($capturas);
$actual = [];
foreach ($capturas as $caso => $captura) {
    $actual[$caso] = [
        'ruta' => $captura['ruta'],
        'status' => $captura['status'],
        'origen' => 'render-puro',
        'cuerpo' => json_decode($captura['raw'], false),
    ];
}
$archivo = __DIR__ . '/fixtures/api-password-reset-error-bodies.json';
$serializado = json_encode($actual, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
if (getenv('LPS_REGENERAR_CUERPOS') === '1') {
    file_put_contents($archivo, $serializado);
    echo "INFO: regenerado {$archivo}\n";
}
check(
    is_file($archivo) && json_encode(json_decode((string) file_get_contents($archivo), false)) === json_encode(json_decode($serializado, false)),
    'los cuerpos de error coinciden con tests/fixtures/api-password-reset-error-bodies.json (regenerar con LPS_REGENERAR_CUERPOS=1 si el cambio es intencional)',
);

echo (string) ob_get_clean();
exit($failures === 0 ? 0 : 1);
