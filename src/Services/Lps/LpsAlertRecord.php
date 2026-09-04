<?php

declare(strict_types=1);

namespace App\Services\Lps;

/**
 * Snapshot inmutable de una fila de `lps_escalamientos`, ya scoped por proyecto. Es lo único que
 * el resolver de target-por-alerta consume: toda la ruta legacy (actividad/semana/módulo) se
 * deriva de aquí, nunca del cliente.
 */
final readonly class LpsAlertRecord
{
    public function __construct(
        public int $id,
        public int $projectId,
        public int $activityId,
        public string $module,
        public int $week,
        public int $level,
        public bool $active,
    ) {
    }
}
