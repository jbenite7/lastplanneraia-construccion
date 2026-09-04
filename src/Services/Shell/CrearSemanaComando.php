<?php

declare(strict_types=1);

namespace App\Services\Shell;

/** Entrada inmutable de `WeekAdministrationService::crear()`. */
final class CrearSemanaComando
{
    public function __construct(
        public readonly int $projectId,
        public readonly string $fechaInicio,
        public readonly bool $preConstruccion,
        public readonly bool $esAdmin,
    ) {
    }
}
