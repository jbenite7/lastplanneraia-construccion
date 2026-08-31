<?php

declare(strict_types=1);

namespace App\Services\Shell;

/**
 * Salida de `WeekAdministrationService::crear()`. `exitosa()` y `bloqueada()` son las únicas
 * formas de construirlo — nunca ambos estados a la vez, así el controlador HTTP no tiene que
 * adivinar cuál campo mirar primero.
 */
final class ResultadoCreacionSemana
{
    public const BLOQUEO_CIC_PENDIENTE = 'cic_pendiente';
    public const BLOQUEO_SEMANA_NO_CONFIRMADA = 'semana_no_confirmada';
    public const BLOQUEO_PROGRAMA_MAESTRO_VACIO = 'programa_maestro_vacio';

    private function __construct(
        public readonly bool $exito,
        public readonly ?int $semana,
        public readonly ?string $fechaInicio,
        public readonly ?string $fechaFin,
        public readonly ?string $motivoBloqueo,
        public readonly ?string $mensaje,
    ) {
    }

    public static function exitosa(int $semana, string $fechaInicio, string $fechaFin): self
    {
        return new self(true, $semana, $fechaInicio, $fechaFin, null, null);
    }

    public static function bloqueada(string $motivo, string $mensaje): self
    {
        return new self(false, null, null, null, $motivo, $mensaje);
    }
}
