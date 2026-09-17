<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Security\CsrfTokenManager;
use App\Services\Auth\PasswordResetService;
use Closure;
use JsonException;
use Throwable;

/**
 * Contrato JSON puro para `/api/auth/password/reset/validate` y `/api/auth/password/reset`,
 * consumido por el shell React (`frontend/src/lib/api/auth.ts`).
 *
 * El token viaja solo en el body y nunca se refleja en respuestas ni en logs. Un token con formato
 * distinto a hex minúscula de 64 caracteres se rechaza antes de llegar al servicio. Enlace
 * inválido, vencido, usado o de un usuario que ya no existe responden igual, sin identidad.
 * `scope` se fija a `app` del lado del servidor. La política de contraseña la decide el servicio:
 * aquí solo se traduce la primera clave de su `fieldErrors` al campo que espera el cliente.
 */
final class PasswordResetApiController
{
    private const TOKEN_PATTERN = '/^[a-f0-9]{64}$/D';
    private const INVALID_MESSAGE = 'El enlace no es válido o ya expiró. Solicita uno nuevo.';
    private const VALIDATE_UNAVAILABLE = 'No pudimos validar el enlace en este momento. Intenta nuevamente.';
    private const UPDATE_UNAVAILABLE = 'Error al actualizar la contraseña.';
    private const SUCCESS_MESSAGE = 'Contraseña restablecida correctamente.';

    /**
     * Clave de `fieldErrors` del servicio (`PasswordPolicyService::validateFields()`, propagada por
     * `UserPasswordService` y `PasswordResetService::reset()`) → campo que espera el cliente.
     */
    private const SERVICE_FIELDS = [
        'password' => 'password',
        'confirmation' => 'confirmPassword',
    ];

    /**
     * Único literal que queda: `UserPasswordService` devuelve la misma estructura
     * (`success:false`, `fieldErrors:[]`) para usuario inexistente y para fallo al guardar, así que
     * solo el mensaje los distingue. Si cambia, el usuario inexistente degrada a 503 (mismo body
     * genérico, sin identidad), nunca a una fuga.
     */
    private const USER_MISSING_MESSAGE = 'Usuario no encontrado';

    private PasswordResetService $service;
    private Closure $bodyReader;

    public function __construct(?PasswordResetService $service = null, ?callable $bodyReader = null)
    {
        $this->service = $service ?? new PasswordResetService();
        $this->bodyReader = $bodyReader === null
            ? static fn (): string => (string) file_get_contents('php://input')
            : Closure::fromCallable($bodyReader);
    }

    public function validateLink(): void
    {
        $this->headers();
        if (!$this->validCsrf()) {
            $this->respondCsrfInvalid();

            return;
        }

        $payload = $this->decodeStrictPayload(($this->bodyReader)());
        if ($payload === null || !$this->hasExactKeys($payload, ['token']) || !is_string($payload['token'])) {
            $this->respondError(422, 'validation_error', 'El enlace recibido no tiene un formato válido.', [
                'token' => 'El enlace recibido no tiene un formato válido.',
            ]);

            return;
        }

        $token = trim($payload['token']);
        if (preg_match(self::TOKEN_PATTERN, $token) !== 1) {
            $this->respondInvalidState();

            return;
        }

        try {
            $valid = $this->service->findValidToken($token, 'app') !== null;
        } catch (Throwable $error) {
            error_log('Password reset validate unavailable: ' . $error::class);
            $this->respondError(503, 'reset_unavailable', self::VALIDATE_UNAVAILABLE);

            return;
        }

        if (!$valid) {
            $this->respondInvalidState();

            return;
        }
        $this->respond(200, ['success' => true, 'state' => 'valid']);
    }

    public function update(): void
    {
        $this->headers();
        if (!$this->validCsrf()) {
            $this->respondCsrfInvalid();

            return;
        }

        $payload = $this->decodeStrictPayload(($this->bodyReader)());
        if ($payload === null
            || !$this->hasExactKeys($payload, ['token', 'password', 'confirmPassword'])
            || !is_string($payload['token'])
            || !is_string($payload['password'])
            || !is_string($payload['confirmPassword'])) {
            $this->respondError(422, 'validation_error', 'Revisa los campos.');

            return;
        }

        $token = trim($payload['token']);
        if (preg_match(self::TOKEN_PATTERN, $token) !== 1) {
            $this->respondInvalidLink();

            return;
        }

        try {
            $result = $this->service->reset($token, 'app', $payload['password'], $payload['confirmPassword']);
        } catch (Throwable $error) {
            error_log('Password reset update unavailable: ' . $error::class);
            $this->respondUpdateUnavailable();

            return;
        }

        if (($result['success'] ?? false) === true) {
            $this->respond(200, [
                'success' => true,
                'message' => self::SUCCESS_MESSAGE,
                'redirect' => '/login?reset=1',
            ]);

            return;
        }

        // Sin `fieldErrors`: solo lo produce la rama de token no vigente de `reset()` → 410.
        if (!array_key_exists('fieldErrors', $result)) {
            $this->respondInvalidLink();

            return;
        }

        $fieldErrors = $result['fieldErrors'];
        if (is_array($fieldErrors) && $fieldErrors !== []) {
            $serviceKey = array_key_first($fieldErrors);
            $messages = $fieldErrors[$serviceKey];
            $first = is_array($messages) ? reset($messages) : null;
            if (is_string($serviceKey) && isset(self::SERVICE_FIELDS[$serviceKey]) && is_string($first) && $first !== '') {
                $this->respondError(422, 'validation_error', $first, [self::SERVICE_FIELDS[$serviceKey] => $first]);

                return;
            }
        }

        if ($fieldErrors === [] && ($result['message'] ?? null) === self::USER_MISSING_MESSAGE) {
            $this->respondInvalidLink();

            return;
        }
        $this->respondUpdateUnavailable();
    }

    private function validCsrf(): bool
    {
        $csrf = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;

        return CsrfTokenManager::validate(is_string($csrf) ? $csrf : null, 'shell_api');
    }

    /**
     * Conjunto exacto de claves, sin importar el orden en que las serialice el cliente.
     *
     * @param array<string, mixed> $payload
     * @param list<string> $expected
     */
    private function hasExactKeys(array $payload, array $expected): bool
    {
        $actual = array_map('strval', array_keys($payload));
        sort($actual);
        sort($expected);

        return $actual === $expected;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function decodeStrictPayload(string $raw): ?array
    {
        try {
            $payload = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        return is_array($payload) && !array_is_list($payload) ? $payload : null;
    }

    private function respondCsrfInvalid(): void
    {
        $this->respondError(403, 'csrf_invalid', 'No fue posible validar la solicitud. Intenta nuevamente.');
    }

    private function respondInvalidState(): void
    {
        $this->respond(200, ['success' => true, 'state' => 'invalid', 'message' => self::INVALID_MESSAGE]);
    }

    private function respondInvalidLink(): void
    {
        $this->respondError(410, 'reset_link_invalid', self::INVALID_MESSAGE);
    }

    private function respondUpdateUnavailable(): void
    {
        $this->respondError(503, 'reset_unavailable', self::UPDATE_UNAVAILABLE);
    }

    /**
     * Copia de `PasswordRecoveryApiController::respondError()` (PR #44): claves planas `success`,
     * `code`, `message` y, si hay, `fieldErrors` como listas para consumidores de la forma vieja,
     * MÁS el bloque `error` (`codigo`, `mensaje`, `campos`) que es el único que lee
     * `frontend/src/lib/api/cliente.ts`. `campos` solo se emite si hay errores de campo: un arreglo
     * vacío de PHP se serializa como `[]` y `EsquemaCuerpoErrorApi` exige un objeto.
     *
     * @param array<string, string> $fieldErrors
     */
    private function respondError(int $status, string $code, string $message, array $fieldErrors = []): void
    {
        $payload = ['success' => false, 'code' => $code, 'message' => $message];
        $error = ['codigo' => $code, 'mensaje' => $message];
        if ($fieldErrors !== []) {
            $payload['fieldErrors'] = array_map(static fn (string $mensaje): array => [$mensaje], $fieldErrors);
            $error['campos'] = $fieldErrors;
        }
        $payload['error'] = $error;
        $this->respond($status, $payload);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function respond(int $status, array $payload): void
    {
        http_response_code($status);
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function headers(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('X-Content-Type-Options: nosniff');
    }
}
