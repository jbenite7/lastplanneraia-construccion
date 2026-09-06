<?php

declare(strict_types=1);

namespace App\Services\Lps;

/**
 * Enum cerrado de `trigger` para POST /api/lps/crisis/register (T02-AC-109). `MANUAL` es el botón
 * de registro directo; los `SOS-*` son los que emite `triggerEscalate()` de `lps_drawer.js` según
 * el rol superior al que escala (Residente/Director/Coordinador de Integración/Gerente de
 * Construcción/Gerente General). Ningún otro valor es válido — el servidor nunca infiere un
 * trigger nuevo a partir de texto libre.
 */
final class LpsCrisisTrigger
{
    public const MANUAL = 'MANUAL';
    public const SOS_RES = 'SOS-RES';
    public const SOS_DIR = 'SOS-DIR';
    public const SOS_COO = 'SOS-COO';
    public const SOS_GER = 'SOS-GER';

    private const VALUES = [self::MANUAL, self::SOS_RES, self::SOS_DIR, self::SOS_COO, self::SOS_GER];

    public static function isValid(string $trigger): bool
    {
        return in_array($trigger, self::VALUES, true);
    }
}
