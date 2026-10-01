<?php

declare(strict_types=1);

namespace App\Services\Lps;

/**
 * Puerto por módulo (PG/PI/PS) que el resolver usa para validar que una actividad pertenece al
 * proyecto activo y para verificar la semana que propone el cliente. Las implementaciones legacy (temporales,
 * mientras exista convivencia) leen `programa_consolidado`/`programacion_semanal` vía
 * TableResolver + Database::queryWithProject; los tests unitarios del resolver usan fakes.
 */
interface LpsActivityTargetAdapter
{
    /** Módulo que resuelve este adapter: 'PG', 'PI' o 'PS'. */
    public function moduleKey(): string;

    /**
     * Verifica que la actividad existe en el proyecto y en esa semana exacta. El cliente propone la
     * semana y el servidor la verifica (S05-SOS 1.2). Cero es una semana válida (Pre-Construcción).
     */
    public function existsInWeek(int $projectId, int $activityId, int $week): bool;
}
