<?php

declare(strict_types=1);

namespace App\Services\Lps;

/**
 * Contrato cerrado por S25 (docs/superpowers/specs/2026-08-30-s25-escalamientos-react-design.md
 * §ESC-R6): `eligible|profile_required|forbidden`. `forbidden` es la falta de capacidad RBAC de
 * escritura; `profile_required` es un actor sin fila `profesionales` compatible bajo el schema
 * actual. Ninguno de los dos repara identidad ni busca por texto.
 */
final class LpsActorEligibility
{
    public const ELIGIBLE = 'eligible';
    public const PROFILE_REQUIRED = 'profile_required';
    public const FORBIDDEN = 'forbidden';

    public function __construct(private readonly LpsActorCompatibilityChecker $checker)
    {
    }

    public function evaluate(int $projectId, int $userId, bool $canWrite): string
    {
        if (!$canWrite) {
            return self::FORBIDDEN;
        }

        if ($userId <= 0) {
            return self::PROFILE_REQUIRED;
        }

        return $this->checker->isCompatible($projectId, $userId)
            ? self::ELIGIBLE
            : self::PROFILE_REQUIRED;
    }
}
