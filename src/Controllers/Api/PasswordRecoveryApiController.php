<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Security\CsrfTokenManager;
use App\Services\Auth\PasswordResetService;
use Closure;
use JsonException;
use Throwable;

/**
 * Contrato JSON puro para `/api/auth/password/forgot`, consumido por el shell React.
 *
 * Responde con el mismo mensaje genérico tanto si el correo existe como si no
 * (`PasswordResetService::RESULTADO_ENVIADO`/`RESULTADO_IGNORADO`), para no permitir enumerar
 * cuentas por esta vía. `scope` se fija siempre a `app` del lado del servidor: cualquier valor
 * que mande el cliente en el body se descarta antes de llegar al servicio.
 */
final class PasswordRecoveryApiController
{
    private const GENERIC_MESSAGE = 'Si el correo existe y está habilitado, enviaremos un enlace de restablecimiento en unos minutos.';
    private const UNAVAILABLE_MESSAGE = 'No pudimos enviar el correo en este momento por un problema técnico. Vuelve a intentarlo en unos minutos; si sigue fallando, avisa al administrador.';

    private PasswordResetService $service;
    private Closure $bodyReader;

    public function __construct(?PasswordResetService $service = null, ?callable $bodyReader = null)
    {
        $this->service = $service ?? new PasswordResetService();
        $this->bodyReader = $bodyReader === null
            ? static fn (): string => (string) file_get_contents('php://input')
            : Closure::fromCallable($bodyReader);
    }

    public function request(): void
    {
        $this->headers();
        $csrf = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        if (!CsrfTokenManager::validate(is_string($csrf) ? $csrf : null, 'shell_api')) {
            $this->respond(403, [
                'success' => false,
                'code' => 'csrf_invalid',
                'message' => 'No fue posible validar la solicitud. Intenta nuevamente.',
            ]);

            return;
        }

        $payload = $this->decodeStrictPayload(($this->bodyReader)());
        $emailValue = is_array($payload) ? ($payload['email'] ?? null) : null;
        $email = is_string($emailValue) ? trim($emailValue) : '';
        if ($payload === null || array_keys($payload) !== ['email'] || !is_string($emailValue) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->respond(422, [
                'success' => false,
                'code' => 'validation_error',
                'message' => 'Revisa el correo electrónico.',
                'fieldErrors' => ['email' => ['Ingresa un correo electrónico válido.']],
            ]);

            return;
        }

        try {
            $outcome = $this->service->request($email, 'app');
        } catch (Throwable $error) {
            error_log('Password recovery unavailable: ' . $error::class);
            $this->respondUnavailable();

            return;
        }

        if (in_array($outcome, [PasswordResetService::RESULTADO_ENVIADO, PasswordResetService::RESULTADO_IGNORADO], true)) {
            $this->respond(200, ['success' => true, 'message' => self::GENERIC_MESSAGE]);

            return;
        }
        $this->respondUnavailable();
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

    private function respondUnavailable(): void
    {
        $this->respond(503, [
            'success' => false,
            'code' => 'recovery_unavailable',
            'message' => self::UNAVAILABLE_MESSAGE,
        ]);
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
    }
}
