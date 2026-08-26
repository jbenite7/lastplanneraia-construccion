<?php

namespace App\Services;

use App\Core\Lps\LpsService;
use App\Services\ProgramaConsolidadoNormalizationService;
use TableResolver;

class WeeklyRealProgressCarryoverService
{
    private $db;
    private LpsService $lpsService;

    private ProgramaConsolidadoNormalizationService $normalizationService;

    public function __construct($db = null, ?LpsService $lpsService = null)
    {
        $this->db = $db ?: \Database::getInstance();
        $this->lpsService = $lpsService ?: new LpsService();
        $this->normalizationService = new ProgramaConsolidadoNormalizationService($this->db);
    }

    public function syncWeek(string $dbPrefix, int $sourceWeek, int $targetWeek, ?int $sourceProgramId = null): array
    {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $dbPrefix)) {
            throw new \InvalidArgumentException('Base de datos inválida.');
        }

        if ($sourceWeek <= 0 || $targetWeek <= 0) {
            return ['updatedProgramIds' => [], 'updatedRows' => 0];
        }

        $projectId = TableResolver::getProjectIdByPrefix($dbPrefix);
        $tProgConsolidado = TableResolver::resolveByPrefix($dbPrefix, 'programa_consolidado');

        $sourcePrograms = $this->loadSourcePrograms($dbPrefix, $sourceWeek, $projectId);
        $targetRows = $this->loadTargetRows($dbPrefix, $targetWeek, $projectId);

        if (empty($targetRows)) {
            return ['updatedProgramIds' => [], 'updatedRows' => 0];
        }

        $weeklyGroups = $this->loadWeeklyGroups($dbPrefix, $sourceWeek, $projectId, $sourceProgramId);
        $filterActivityKey = $this->resolveFilterActivityKey($sourcePrograms, $sourceProgramId);

        $updatedProgramIds = [];
        $updatedRows = 0;

        foreach ($targetRows as $targetRow) {
            $resolution = $this->resolveTargetSource($targetRow, $sourcePrograms, $weeklyGroups, $sourceProgramId, $filterActivityKey);
            if ($resolution === null) {
                continue;
            }

            $baseProgram = $resolution['baseProgram'];
            $overlayGroup = $resolution['overlayGroup'];

            $baseRatio = $this->resolveBaseRatio($baseProgram, $targetRow);
            $finalRatio = $baseRatio;

            if ($overlayGroup !== null) {
                $finalRatio = $this->clampRatio($baseRatio + $overlayGroup['real_ratio_sum']);
                $responsable = $overlayGroup['responsable_aia'];
                $subcontratista = $overlayGroup['sub_contratista'];
                $unidad = $overlayGroup['unidad'];
                $cantidadPpto = $overlayGroup['cantidad_ppto'];
            } else {
                $responsable = $this->normalizeNullableText($baseProgram['Responsable_AIA'] ?? null);
                $subcontratista = $this->normalizeNullableText($baseProgram['Sub_Contratista'] ?? null);
                ['unit' => $unidad, 'quantity' => $cantidadPpto] = $this->normalizeMeasurement(
                    $baseProgram['unidad'] ?? null,
                    $baseProgram['cantidad_ppto'] ?? null,
                );
            }

            // ── Preserve user-edited fields ──────────────────────────────────
            // If the target row's current value differs from what the carryover
            // would set, the user manually edited it — preserve their change.
            // This prevents the auto- updateBatch on page entry from silently
            // reverting user edits (the reported bug).

            // Ejecutado: se compara contra el testigo — el último valor que escribió
            // este mismo servicio. Si `Ejecutado` sigue igual al testigo, nadie lo tocó
            // desde entonces y se puede recalcular; si difiere, lo movió el residente
            // y manda él.
            //
            // Corregido el 2026-08-25. Hasta hoy la comparación era contra `baseRatio`
            // (el acumulado de la semana origen), y eso es justamente lo que no distingue:
            // la celda destino ya trae sumado el avance de la semanal, así que SIEMPRE
            // difiere del origen en cuanto hay algo reportado. Desde la segunda corrida el
            // servicio confundía su propia escritura con una edición ajena y congelaba la
            // fila — un avance reportado tarde no volvía a entrar nunca. Medido en
            // producción y cubierto por `tests/unit/CarryoverAvanceSemanalTest.php`.
            $targetCurrentRatio = $this->lpsService->toFloat($targetRow['Ejecutado'] ?? null, null);
            $testigo = $this->lpsService->toFloat($targetRow['Ejecutado_Carryover'] ?? null, null);

            if ($testigo !== null) {
                $preserveRatio = abs(($targetCurrentRatio ?? 0.0) - $testigo) > 0.001;
            } else {
                // Fila que nunca pasó por el arrastre nuevo (anterior a la migración).
                // Sin testigo hay que deducir quién escribió, y solo hay dos huellas que
                // delatan al propio arrastre: que el valor siga igual al de la semana
                // origen (nunca se arrastró), o que coincida con lo que el arrastre
                // escribiría ahora mismo (lo escribió él). En cualquiera de los dos casos
                // se puede recalcular y, de paso, queda fijado el testigo.
                //
                // Si no coincide con ninguna, es ambiguo — puede ser una edición del
                // residente o un arrastre viejo ya desfasado — y ahí manda el residente:
                // se preserva y el testigo se queda en NULL.
                $preserveRatio = (
                    $targetCurrentRatio !== null
                    && $targetCurrentRatio > 0
                    && abs($targetCurrentRatio - $baseRatio) > 0.001
                    && abs($targetCurrentRatio - $finalRatio) > 0.001
                );
            }

            $effectiveRatio = $preserveRatio ? $targetCurrentRatio : $finalRatio;

            // El testigo solo se mueve cuando manda el arrastre. Si se preservó una
            // edición, se deja como estaba: sobrescribirlo con el valor del residente
            // haría que la corrida siguiente lo tomara por escritura propia y la pisara.
            $testigoAEscribir = $preserveRatio ? $testigo : $effectiveRatio;

            // Unidad: normalize both sides for comparison
            $targetUnit = trim((string) ($targetRow['unidad'] ?? ''));
            $carryoverUnit = trim((string) ($unidad ?? ''));
            $preserveUnit = $targetUnit !== ''
                && strtolower($targetUnit) !== strtolower($carryoverUnit);
            $effectiveUnit = $preserveUnit ? $targetUnit : $carryoverUnit;

            // Cantidad PPTO
            $targetQty = $this->lpsService->toFloat($targetRow['cantidad_ppto'] ?? null, null);
            $preserveQty = $targetQty !== null
                && ($cantidadPpto === null || abs($targetQty - $cantidadPpto) > 0.001);
            // Only preserve if target has a genuine quantity (not the default null)
            if ($preserveQty && $targetQty > 0) {
                $effectiveQty = $targetQty;
            } else {
                $effectiveQty = $cantidadPpto;
            }

            // Responsable_AIA
            $targetResp = $this->normalizeNullableText($targetRow['Responsable_AIA'] ?? null);
            $carryoverResp = $this->normalizeNullableText($responsable);
            $preserveResp = $targetResp !== null
                && strcasecmp((string) $targetResp, (string) $carryoverResp) !== 0;
            $effectiveResp = $preserveResp ? $targetResp : $responsable;

            // Sub_Contratista
            $targetSub = $this->normalizeNullableText($targetRow['Sub_Contratista'] ?? null);
            $carryoverSub = $this->normalizeNullableText($subcontratista);
            $preserveSub = $targetSub !== null
                && strcasecmp((string) $targetSub, (string) $carryoverSub) !== 0;
            $effectiveSub = $preserveSub ? $targetSub : $subcontratista;

            $this->db->queryWithProject(
                "UPDATE {$tProgConsolidado}
                 SET Ejecutado = ?,
                     Ejecutado_Siguiente_Semana = ?,
                     Ejecutado_Carryover = ?,
                     Responsable_AIA = ?, Sub_Contratista = ?, unidad = ?, cantidad_ppto = ?
                 WHERE Semana = ? AND row_id = ?",
                [
                    $effectiveRatio,
                    $effectiveRatio,
                    $testigoAEscribir,
                    $effectiveResp,
                    $effectiveSub,
                    $effectiveUnit,
                    $effectiveQty,
                    $targetWeek,
                    $targetRow['row_id'] ?? $targetRow['Consecutivo'],
                ],
                $projectId,
            );

            $updatedRows++;
            $updatedProgramIds[(int) ($targetRow['unique_id'] ?? $targetRow['Consecutivo_en_Programa'])] = true;
        }

        $this->normalizationService->normalizeChapters($dbPrefix, $targetWeek);

        return [
            'updatedProgramIds' => array_map('intval', array_keys($updatedProgramIds)),
            'updatedRows' => $updatedRows,
        ];
    }

    private function loadSourcePrograms(string $dbPrefix, int $sourceWeek, ?int $projectId = null): array
    {
        $t = TableResolver::resolveByPrefix($dbPrefix, 'programa_consolidado');
        $rows = $this->db->queryWithProject(
            "SELECT unique_id AS Consecutivo_en_Programa,
                    unique_id,
                    Actividad, Ejecutado, Ejecutado_Siguiente_Semana, unidad, cantidad_ppto, Responsable_AIA, Sub_Contratista
             FROM {$t}
             WHERE Semana = ? AND Titulo = 0",
            [$sourceWeek],
            $projectId,
        )->fetchAll();

        $byId = [];
        $byActivity = [];

        foreach ($rows as $row) {
            $programId = (int) ($row['unique_id'] ?? $row['Consecutivo_en_Programa'] ?? 0);
            if ($programId <= 0) {
                continue;
            }

            $activityKey = $this->normalizeActivityKey($row['Actividad'] ?? null);
            $row['_activity_key'] = $activityKey;
            $byId[$programId] = $row;

            if ($activityKey !== '' && !isset($byActivity[$activityKey])) {
                $byActivity[$activityKey] = $row;
            }
        }

        return ['byId' => $byId, 'byActivity' => $byActivity];
    }

    private function loadTargetRows(string $dbPrefix, int $targetWeek, ?int $projectId = null): array
    {
        $t = TableResolver::resolveByPrefix($dbPrefix, 'programa_consolidado');
        return $this->db->queryWithProject(
            "SELECT row_id AS Consecutivo,
                    row_id,
                    unique_id AS Consecutivo_en_Programa,
                    unique_id,
                    Actividad, programaAnteriorAsociar,
                    Ejecutado, Ejecutado_Siguiente_Semana, Ejecutado_Carryover,
                    unidad, cantidad_ppto, Responsable_AIA, Sub_Contratista
             FROM {$t}
             WHERE Semana = ? AND Titulo = 0",
            [$targetWeek],
            $projectId,
        )->fetchAll();
    }

    private function loadWeeklyGroups(string $dbPrefix, int $sourceWeek, ?int $projectId = null, ?int $sourceProgramId = null): array
    {
        $t = TableResolver::resolveByPrefix($dbPrefix, 'programacion_semanal');
        $params = [$sourceWeek];
        $sql = "SELECT unique_id AS Consecutivo_En_Programa,
                       unique_id,
                       Actividad, Unidad, cantidad_ppto, Ejecutado_Real, Responsable_AIA, Sub_Contratista
                FROM {$t}
                WHERE Semana = ? AND (Activa = '1' OR Activa = 'NA')";

        if ($sourceProgramId !== null) {
            $sql .= " AND unique_id = ?";
            $params[] = $sourceProgramId;
        }

        $rows = $this->db->queryWithProject($sql, $params, $projectId)->fetchAll();

        $byId = [];
        $byActivity = [];

        foreach ($rows as $row) {
            $programId = (int) ($row['unique_id'] ?? $row['Consecutivo_En_Programa'] ?? 0);
            if ($programId <= 0) {
                continue;
            }

            $activityKey = $this->normalizeActivityKey($row['Actividad'] ?? null);

            if (!isset($byId[$programId])) {
                $byId[$programId] = $this->createEmptyWeeklyGroup($activityKey);
            }
            $this->appendWeeklyRowToGroup($byId[$programId], $row, $activityKey);

            if ($activityKey !== '') {
                if (!isset($byActivity[$activityKey])) {
                    $byActivity[$activityKey] = $this->createEmptyWeeklyGroup($activityKey);
                }
                $this->appendWeeklyRowToGroup($byActivity[$activityKey], $row, $activityKey);
            }
        }

        foreach ($byId as &$group) {
            $this->finalizeWeeklyGroup($group);
        }
        unset($group);

        foreach ($byActivity as &$group) {
            $this->finalizeWeeklyGroup($group);
        }
        unset($group);

        return ['byId' => $byId, 'byActivity' => $byActivity];
    }

    private function createEmptyWeeklyGroup(string $activityKey): array
    {
        return [
            'activity_key' => $activityKey,
            'real_ratio_sum' => 0.0,
            'responsables' => [],
            'subcontratistas' => [],
            'measurement_signatures' => [],
            'measurement_values' => [],
            'responsable_aia' => null,
            'sub_contratista' => null,
            'unidad' => '%',
            'cantidad_ppto' => null,
        ];
    }

    private function appendWeeklyRowToGroup(array &$group, array $row, string $activityKey): void
    {
        $group['activity_key'] = $activityKey;

        ['unit' => $unit, 'quantity' => $quantity, 'signature' => $signature] = $this->normalizeMeasurement(
            $row['Unidad'] ?? null,
            $row['cantidad_ppto'] ?? null,
            true,
        );

        $real = $this->lpsService->toFloat($row['Ejecutado_Real'] ?? null, 0.0);
        $real = max(0.0, $real ?? 0.0);

        $ratio = ($unit !== '%' && $quantity !== null && $quantity > 0)
            ? ($real / $quantity)
            : ($real / 100);

        $group['real_ratio_sum'] += $ratio;
        $group['measurement_signatures'][$signature] = true;
        $group['measurement_values'][$signature] = ['unit' => $unit, 'quantity' => $quantity];

        $responsable = $this->normalizeNullableText($row['Responsable_AIA'] ?? null);
        if ($responsable !== null) {
            $group['responsables'][$responsable] = $responsable;
        }

        $subcontratista = $this->normalizeNullableText($row['Sub_Contratista'] ?? null);
        if ($subcontratista !== null) {
            $group['subcontratistas'][$subcontratista] = $subcontratista;
        }
    }

    private function finalizeWeeklyGroup(array &$group): void
    {
        $signatures = array_keys($group['measurement_signatures']);

        if (count($signatures) === 1) {
            $measurement = $group['measurement_values'][$signatures[0]];
            $group['unidad'] = $measurement['unit'];
            $group['cantidad_ppto'] = $measurement['quantity'];
        } else {
            $group['unidad'] = '%';
            $group['cantidad_ppto'] = null;
        }

        $group['real_ratio_sum'] = $this->roundRatio($group['real_ratio_sum']);
        $group['responsable_aia'] = $this->joinDistinctValues($group['responsables']);
        $group['sub_contratista'] = $this->joinDistinctValues($group['subcontratistas']);
    }

    private function resolveFilterActivityKey(array $sourcePrograms, ?int $sourceProgramId): ?string
    {
        if ($sourceProgramId === null) {
            return null;
        }

        return $sourcePrograms['byId'][$sourceProgramId]['_activity_key'] ?? null;
    }

    private function resolveTargetSource(
        array $targetRow,
        array $sourcePrograms,
        array $weeklyGroups,
        ?int $sourceProgramId,
        ?string $filterActivityKey,
    ): ?array {
        $mappingKey = $this->normalizeActivityKey($targetRow['programaAnteriorAsociar'] ?? null);
        $targetActivityKey = $this->normalizeActivityKey($targetRow['Actividad'] ?? null);
        $targetProgramId = (int) ($targetRow['unique_id'] ?? $targetRow['Consecutivo_en_Programa'] ?? 0);

        $baseProgram = null;
        $overlayGroup = null;
        $sourceKey = null;
        $isNameMatch = false;

        if ($mappingKey !== '' && $mappingKey !== '*no asociada*' && isset($sourcePrograms['byActivity'][$mappingKey])) {
            $baseProgram = $sourcePrograms['byActivity'][$mappingKey];
            $overlayGroup = $weeklyGroups['byActivity'][$mappingKey] ?? null;
            $sourceKey = $mappingKey;
            $isNameMatch = true;
        } elseif ($targetProgramId > 0 && isset($sourcePrograms['byId'][$targetProgramId])) {
            $baseProgram = $sourcePrograms['byId'][$targetProgramId];
            $overlayGroup = $weeklyGroups['byId'][$targetProgramId] ?? null;
            $sourceKey = $targetProgramId;
        } elseif ($targetActivityKey !== '' && isset($sourcePrograms['byActivity'][$targetActivityKey])) {
            $baseProgram = $sourcePrograms['byActivity'][$targetActivityKey];
            $overlayGroup = $weeklyGroups['byActivity'][$targetActivityKey] ?? null;
            $sourceKey = $targetActivityKey;
            $isNameMatch = true;
        }

        if ($baseProgram === null) {
            return null;
        }

        if ($sourceProgramId !== null) {
            $matchesFilter = false;
            if ($isNameMatch) {
                $matchesFilter = ($filterActivityKey !== null && $sourceKey === $filterActivityKey);
            } else {
                $matchesFilter = ((int) $sourceKey === $sourceProgramId);
            }

            if (!$matchesFilter) {
                return null;
            }
        }

        return ['baseProgram' => $baseProgram, 'overlayGroup' => $overlayGroup];
    }

    private function resolveBaseRatio(array $baseProgram, array $targetRow): float
    {
        // Hotfix 2026-06-04: preferir Ejecutado (valor visible en UI de PG) sobre
        // Ejecutado_Siguiente_Semana. Este último puede contener un valor viejo
        // heredado de semanas anteriores y desincronizar el carryover (síntoma:
        // 90% + 10% quedaba en 65% en lugar de 100%).
        $base = $this->lpsService->toFloat($baseProgram['Ejecutado'] ?? null, null);
        if ($base === null) {
            $base = $this->lpsService->toFloat($baseProgram['Ejecutado_Siguiente_Semana'] ?? null, null);
        }
        if ($base === null) {
            $base = $this->lpsService->toFloat($targetRow['Ejecutado'] ?? null, null);
        }
        if ($base === null) {
            $base = $this->lpsService->toFloat($targetRow['Ejecutado_Siguiente_Semana'] ?? null, 0.0);
        }

        return $this->clampRatio($base ?? 0.0);
    }

    private function normalizeActivityKey($value): string
    {
        $text = html_entity_decode(strip_tags((string) ($value ?? '')), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', trim($text));
        return function_exists('mb_strtolower') ? mb_strtolower($text, 'UTF-8') : strtolower($text);
    }

    private function normalizeMeasurement($unitValue, $quantityValue, bool $includeSignature = false): array
    {
        $unit = trim((string) ($unitValue ?? ''));
        $quantity = $this->lpsService->toFloat($quantityValue ?? null, null);

        if ($quantity !== null) {
            $quantity = round($quantity, 4);
            if ($quantity <= 0) {
                $quantity = null;
            }
        }

        if ($unit === '' || $unit === '%' || $quantity === null) {
            $normalized = ['unit' => '%', 'quantity' => null];
        } else {
            $normalized = ['unit' => $unit, 'quantity' => $quantity];
        }

        if ($includeSignature) {
            $normalized['signature'] = $normalized['unit'] . '|' . ($normalized['quantity'] === null ? '' : number_format($normalized['quantity'], 4, '.', ''));
        }

        return $normalized;
    }

    private function normalizeNullableText($value): ?string
    {
        if ($this->lpsService->isBlank($value)) {
            return null;
        }

        $text = preg_replace('/\s+/u', ' ', trim((string) $value));
        return ($text === '') ? null : $text;
    }

    private function joinDistinctValues(array $values): ?string
    {
        $items = array_values(array_filter($values, static fn($value) => $value !== null && $value !== ''));
        if (empty($items)) {
            return null;
        }

        return implode(', ', $items);
    }

    private function roundRatio(float $value): float
    {
        return round($value, 6);
    }

    private function clampRatio(float $value): float
    {
        if ($value < 0) {
            return 0.0;
        }

        if ($value > 1) {
            return 1.0;
        }

        return $this->roundRatio($value);
    }
}
