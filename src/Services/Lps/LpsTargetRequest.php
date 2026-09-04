<?php

declare(strict_types=1);

namespace App\Services\Lps;

/**
 * Entrada tipada del resolver. El controlador ya extrajo/filtró estos valores del request (nunca
 * $_SESSION) antes de construirla; el resolver no lee superglobales.
 */
final readonly class LpsTargetRequest
{
    public function __construct(
        public ?int $activityId = null,
        public ?string $module = null,
        public ?int $alertId = null,
        public ?int $escalamientoId = null,
    ) {
    }
}
