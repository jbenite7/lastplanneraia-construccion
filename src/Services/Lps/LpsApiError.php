<?php

declare(strict_types=1);

namespace App\Services\Lps;

/**
 * Forma de error aditiva del contrato T02 (docs/superpowers/specs/2026-08-30-t02-contexto-lps-react-design.md
 * §Errores LPS). Inmutable: cada código tiene su fábrica y su HTTP status fijos ahí, para que
 * ningún llamador invente un par status/code nuevo.
 */
final readonly class LpsApiError
{
    private function __construct(
        public int $httpStatus,
        public string $code,
        public string $message,
        public array $fields = [],
    ) {
    }

    public static function sessionRequired(): self
    {
        return new self(401, 'SESSION_REQUIRED', 'Sesión inválida.');
    }

    public static function capabilityRequired(): self
    {
        return new self(403, 'CAPABILITY_REQUIRED', 'Acción no autorizada.');
    }

    public static function csrfInvalid(): self
    {
        return new self(403, 'CSRF_INVALID', 'Token inválido.');
    }

    /**
     * Un target ajeno o inexistente responde exactamente igual (T02-AC-079): no hay forma de
     * distinguir "no existe" de "no es tuyo" desde la respuesta.
     */
    public static function targetNotFound(): self
    {
        return new self(404, 'LPS_TARGET_NOT_FOUND', 'No fue posible completar la acción.');
    }

    public static function targetStale(): self
    {
        return new self(409, 'LPS_TARGET_STALE', 'El contexto de la alerta cambió; recarga la actividad.');
    }

    public static function profileRequired(): self
    {
        return new self(409, 'PROFILE_REQUIRED', 'La bitácora queda disponible en modo lectura.');
    }

    /** @param array<string, string> $fields */
    public static function validationFailed(array $fields = []): self
    {
        return new self(422, 'VALIDATION_FAILED', 'Datos inválidos.', $fields);
    }

    public static function readFailed(): self
    {
        return new self(500, 'LPS_READ_FAILED', 'No fue posible leer la información.');
    }

    public static function serviceUnavailable(): self
    {
        return new self(503, 'SERVICE_UNAVAILABLE', 'Intenta de nuevo más tarde.');
    }
}
