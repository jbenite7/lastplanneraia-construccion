<?php

declare(strict_types=1);

namespace App\Services\Lps;

/**
 * Resultado inmutable de {@see LpsCrisisService::register()} (T02-AC-111/112). `wasActive` es la
 * señal de idempotencia: `true` cuando ya existía una alerta activa para el target y el registro
 * no creó una fila nueva ni tocó `nivel_actual`. El éxito de este resultado sólo significa que la
 * alerta/banderas quedaron aceptadas en base de datos — nunca que un mensaje se entregó ni que
 * hubo escalamiento jerárquico (T02-AC-112).
 */
final readonly class LpsCrisisRegisterResult
{
    public function __construct(
        public int $alertId,
        public bool $wasActive,
    ) {
    }
}
