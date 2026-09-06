<?php

declare(strict_types=1);

namespace App\Services\Lps;

/**
 * Comprueba la compatibilidad FK exacta que exige el schema vigente entre
 * `general_usuarios.Id` y `profesionales(project_id,id)` (ver
 * docs/superpowers/specs/2026-08-30-s25-escalamientos-react-design.md §ESC-R6). No busca por
 * nombre, correo ni cargo, y no repara ni crea perfiles.
 */
interface LpsActorCompatibilityChecker
{
    public function isCompatible(int $projectId, int $userId): bool;
}
