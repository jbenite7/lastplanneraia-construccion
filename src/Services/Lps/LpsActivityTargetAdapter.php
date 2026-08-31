<?php

declare(strict_types=1);

namespace App\Services\Lps;

/**
 * Puerto por módulo (PG/PI/PS) que el resolver usa para validar que una actividad pertenece al
 * proyecto activo y para obtener su semana autoritativa. Las implementaciones legacy (temporales,
 * mientras exista convivencia) leen `programa_consolidado`/`programacion_semanal` vía
 * TableResolver + Database::queryWithProject; los tests unitarios del resolver usan fakes.
 */
interface LpsActivityTargetAdapter
{
    /** Módulo que resuelve este adapter: 'PG', 'PI' o 'PS'. */
    public function moduleKey(): string;

    /**
     * Devuelve la semana autoritativa de la actividad si pertenece al proyecto, o null si no
     * existe/no es de este proyecto. Cero es una semana válida (Pre-Construcción).
     */
    public function resolveWeek(int $projectId, int $activityId): ?int;
}
