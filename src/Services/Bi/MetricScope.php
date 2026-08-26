<?php

declare(strict_types=1);

namespace App\Services\Bi;

use InvalidArgumentException;

/**
 * Alcance de ejecucion de una metrica: que proyectos y que rango de fechas cubre.
 *
 * Task 2 (Ola 1, Torre de Control piloto): superficie minima YAGNI -- solo lo que
 * `MetricExecutor` necesita para aislar por `project_id` y fechar el corte. No es
 * un DTO de filtros de negocio (eso vive en `filters` del catalogo).
 */
final class MetricScope
{
    /** @var list<int> */
    private readonly array $projectIds;

    /**
     * @param list<mixed> $projectIds proyectos incluidos en el calculo, nunca vacio
     */
    public function __construct(
        array $projectIds,
        private readonly ?string $startDate = null,
        private readonly ?string $endDate = null,
        private readonly ?string $week = null,
    ) {
        if ($projectIds === []) {
            throw new InvalidArgumentException('MetricScope requiere al menos un project_id.');
        }

        $normalized = [];
        foreach ($projectIds as $projectId) {
            if (!is_int($projectId) && !(is_string($projectId) && ctype_digit($projectId))) {
                throw new InvalidArgumentException('MetricScope solo acepta project_id numericos.');
            }
            $normalized[] = (int) $projectId;
        }

        $this->projectIds = array_values(array_unique($normalized));
    }

    /** @return list<int> */
    public function projectIds(): array
    {
        return $this->projectIds;
    }

    public function startDate(): ?string
    {
        return $this->startDate;
    }

    public function endDate(): ?string
    {
        return $this->endDate;
    }

    /**
     * Identificador de negocio de semana (ej. `'25'`), columna `Semana` en
     * `bi_ps_compromisos`/`programacion_semanal`. No es una fecha de calendario --
     * dominio distinto de `startDate`/`endDate`, por eso vive en campo separado.
     */
    public function week(): ?string
    {
        return $this->week;
    }

    /**
     * Fecha de corte para `basis()`: el fin del rango explicito si existe, si no la
     * fecha de hoy. Siempre un string no vacio -- `basis()` nunca queda con `corte`
     * mudo.
     */
    public function cutoff(): string
    {
        return $this->endDate ?? date('Y-m-d');
    }
}
