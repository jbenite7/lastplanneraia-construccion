<?php

declare(strict_types=1);

namespace App\Services\Lps;

/**
 * Puerto de lectura de alertas (`lps_escalamientos`), scoped por proyecto. La implementación
 * legacy consulta vía TableResolver + Database::queryWithProject; los tests unitarios usan fakes.
 */
interface LpsAlertRepository
{
    public function findById(int $projectId, int $alertId): ?LpsAlertRecord;
}
