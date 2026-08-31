<?php

declare(strict_types=1);

namespace App\Services\Shell;

final class ResultadoEliminacionSemana
{
    public const BLOQUEO_SIN_SEMANAS = 'sin_semanas';
    public const BLOQUEO_NO_ES_LA_ULTIMA = 'no_es_la_ultima';

    private function __construct(
        public readonly bool $exito,
        public readonly ?int $semanaEliminada,
        public readonly ?int $nuevaSemanaMaxima,
        public readonly ?string $motivoBloqueo,
        public readonly ?string $mensaje,
    ) {
    }

    public static function exitosa(int $semanaEliminada, int $nuevaSemanaMaxima): self
    {
        return new self(true, $semanaEliminada, $nuevaSemanaMaxima, null, null);
    }

    public static function bloqueada(string $motivo, string $mensaje): self
    {
        return new self(false, null, null, $motivo, $mensaje);
    }
}
