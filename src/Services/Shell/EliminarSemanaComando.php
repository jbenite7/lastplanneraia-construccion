<?php

declare(strict_types=1);

namespace App\Services\Shell;

/** Entrada inmutable de `WeekAdministrationService::eliminarUltima()`. */
final class EliminarSemanaComando
{
    public function __construct(
        public readonly int $projectId,
        public readonly int $semanaSolicitada,
    ) {
    }
}
