<?php

namespace App\Core\Lps;

class LpsService
{
    // --- Lógica de Programación Semanal (PS) ---

    public function getWeeklyPhaseKey(int $semanalConfirmada): string
    {
        return ($semanalConfirmada === 1) ? 'calificacion' : 'programacion';
    }

    public function isBlank($value): bool
    {
        if ($value === null) {
            return true;
        }

        $text = trim((string)$value);
        if ($text === '') {
            return true;
        }

        $lower = strtolower($text);
        return $lower === 'null';
    }

    public function toFloat($value, ?float $fallback = null): ?float
    {
        if ($this->isBlank($value)) {
            return $fallback;
        }

        $normalized = preg_replace('/\s+/', '', trim((string)$value));

        $commaPos = strrpos($normalized, ',');
        $dotPos = strrpos($normalized, '.');

        if ($commaPos !== false && $dotPos !== false) {
            if ($commaPos > $dotPos) {
                $normalized = str_replace('.', '', $normalized);
                $normalized = str_replace(',', '.', $normalized);
            } else {
                $normalized = str_replace(',', '', $normalized);
            }
        } elseif ($commaPos !== false) {
            $normalized = str_replace(',', '.', $normalized);
        }

        if (!is_numeric($normalized)) {
            return $fallback;
        }

        return (float)$normalized;
    }

    public function isActiveRow(array $row): bool
    {
        $activaRaw = $row['Activa'] ?? '';
        $activa = strtoupper(trim((string)$activaRaw));

        if ($activa === '' || $activa === 'NA' || $activa === '0' || $activa === 'N' || $activa === 'NO' || $activa === 'FALSE') {
            return false;
        }

        return true;
    }

    public function classifyWeeklyState(array $row, string $phaseKey): string
    {
        if (!$this->isActiveRow($row)) {
            return 'ps-no-activa';
        }

        $ejecutado = $this->toFloat($row['Ejecutado'] ?? null, 0.0);
        $compromiso = $this->toFloat($row['Compromiso'] ?? null, null);
        $ejecutadoReal = $this->toFloat($row['Ejecutado_Real'] ?? null, null);

        $hasCommitment = $compromiso !== null && $compromiso > 0;
        $isIncomplete = $ejecutado < 0.999;

        $libFlag = $this->toFloat($row['Prog_Sin_Restricciones_100'] ?? null, null);
        $sinLiberacion = $libFlag !== null ? ($libFlag > 0) : false;

        $critica = $this->toFloat($row['Critica'] ?? null, 0.0);
        $isCriticalRoute = ($critica >= 1);

        if ($phaseKey === 'programacion') {
            if (!$isIncomplete) {
                return 'ps-no-activa';
            }

            if ($hasCommitment) {
                return 'prog-lista-para-confirmar';
            }

            if ($sinLiberacion && $isCriticalRoute) {
                return 'prog-bloqueo-critico-sin-compromiso';
            }

            return 'prog-sin-compromiso';
        }

        if (!$hasCommitment || $ejecutadoReal === null) {
            return 'cal-sin-calificar';
        }

        if (($ejecutadoReal + 0.0001) < $compromiso) {
            return $isCriticalRoute
                ? 'cal-incumplida-critica'
                : 'cal-incumplida';
        }

        return 'cal-cumplida-control';
    }

    // --- Lógica de Programa General (PG) ---

    public function calculateGeneralStatus(
        $titulo,
        $ejecutado,
        $fechaInicioActividad,
        $fechaFinActividad,
        $fechaInicioSemana,
        $fechaFinSemana = null
    ): string {
        if ((int)$titulo === 1) {
            return 'Capítulo';
        }

        $ej = $this->toFloat($ejecutado, 0.0);
        $ej = $this->clamp($ej, 0.0, 1.0);

        if ($ej >= 0.999) {
            return 'Terminada';
        }

        $fiTs = $this->toTimestamp($fechaInicioActividad);
        $ffTs = $this->toTimestamp($fechaFinActividad);
        $fsTs = $this->toTimestamp($fechaInicioSemana);

        if ($fiTs === null || $ffTs === null || $fsTs === null) {
            return ($ej > 0.001) ? 'En Curso' : 'No Requerida';
        }

        if ($ffTs < $fiTs) {
            $ffTs = $fiTs;
        }

        $feTs = $this->toTimestamp($fechaFinSemana);
        if ($feTs === null) {
            $feTs = $fsTs + (6 * 86400);
        }

        $ejecutadoTeorico = $this->calculateTheoreticalProgress($fechaInicioActividad, $fechaFinActividad, $fechaInicioSemana);
        $delta = round($ejecutadoTeorico - $ej, 3);

        if ($delta > 0.001) {
            return 'Atrasada';
        }

        if ($ej <= 0.001 && $fiTs >= $fsTs && $fiTs <= $feTs) {
            return 'Debe Iniciar esta Semana';
        }

        if ($ej > 0.001) {
            if ($delta < -0.05) {
                return 'Adelantada';
            }

            return 'En Curso';
        }

        if ($ej <= 0.001 && $fiTs > $feTs) {
            $horizonEndTs = $feTs + (42 * 86400); // 42 days lookahead
            if ($fiTs <= $horizonEndTs) {
                return 'Actividad Futura';
            }

            return 'No Requerida';
        }

        return 'No Requerida';
    }

    public function calculateTheoreticalProgress($fechaInicioActividad, $fechaFinActividad, $fechaInicioSemana): float
    {
        $fiTs = $this->toTimestamp($fechaInicioActividad);
        $ffTs = $this->toTimestamp($fechaFinActividad);
        $fsTs = $this->toTimestamp($fechaInicioSemana);

        if ($fiTs === null || $ffTs === null || $fsTs === null) {
            return 0.0;
        }

        if ($ffTs < $fiTs) {
            $ffTs = $fiTs;
        }

        $duracionDias = max(1, (int)floor(($ffTs - $fiTs) / 86400) + 1);
        $diasTranscurridos = (int)floor(($fsTs - $fiTs) / 86400);

        if ($diasTranscurridos < 1) {
            return 0.0;
        }

        if ($diasTranscurridos > $duracionDias) {
            return 1.0;
        }

        return $this->clamp($diasTranscurridos / $duracionDias, 0.0, 1.0);
    }

    public function toTimestamp($dateValue): ?int
    {
        if ($dateValue === null) {
            return null;
        }

        $value = trim((string)$dateValue);
        if ($value === '') {
            return null;
        }

        $timestamp = strtotime($value);
        if ($timestamp === false) {
            return null;
        }

        return strtotime(date('Y-m-d', $timestamp));
    }

    public function clamp(float $value, float $min, float $max): float
    {
        return max($min, min($max, $value));
    }

    /**
     * Calcula proyecciones de ejecución para la semana actual.
     * Migrado de SemanalApiController -> calculateProjections()
     */
    public function calculateWeeklyProjections(array $data, string $fInicioSem, string $fFinSem): array
    {
        $fInicioAct = date("Y-m-d", strtotime($data['Fecha_Inicio']));
        $fFinAct = date("Y-m-d", strtotime($data['Fecha_Fin']));
        $ejecutado = (float)($data['Ejecutado'] ?? 0);

        $diasTotales = ((strtotime($fFinAct) - strtotime($fInicioAct)) / 86400) + 1;
        $diasTranscurridos = ((strtotime($fFinSem) - strtotime($fInicioAct)) / 86400) + 1;

        $faltaPorEjecutar = max(0, 1 - $ejecutado);

        $inicioOverlap = max(strtotime($fInicioSem), strtotime($fInicioAct));
        $finOverlap = min(strtotime($fFinSem), strtotime($fFinAct));
        
        $diasEnSemana = ($inicioOverlap <= $finOverlap) ? (($finOverlap - $inicioOverlap) / 86400) + 1 : 0;
        $diasRestantesDesdeInicioSemana = ((strtotime($fFinAct) - $inicioOverlap) / 86400) + 1;

        if ($diasTranscurridos <= 0) {
            $proyeccion = 0;
        } elseif ($diasRestantesDesdeInicioSemana <= $diasEnSemana) {
            $proyeccion = $faltaPorEjecutar;
        } else {
            $proyeccion = $faltaPorEjecutar * ($diasEnSemana / $diasRestantesDesdeInicioSemana);
        }

        $proyeccion = min($faltaPorEjecutar, max(0, $proyeccion));
        $data["proyeccionSemana"] = $proyeccion;

        if (($diasTranscurridos + $proyeccion) >= 1 && $diasTotales >= ($diasTranscurridos + $proyeccion)) {
            $data["Ejecutado_Fin_Semana"] = $ejecutado + $proyeccion;
        } elseif ($diasTotales < ($diasTranscurridos + $proyeccion) || ($ejecutado + $proyeccion) > 1) {
            $data["Ejecutado_Fin_Semana"] = 1;
        } else {
            $data["Ejecutado_Fin_Semana"] = $ejecutado;
        }
        
        $data["Ejecutado_Fin_Semana"] = min(1, (float)$data["Ejecutado_Fin_Semana"]);
        
        return $data;
    }

    /**
     * TEMPORARY FEATURE FLAG (2026-02):
     * Desactiva la medición de productividad para TODOS los proyectos.
     * Migrada de funciones_generales/php/productividad_temporal.php
     */
    public function disableProductivityMeasurementTemporarily($db): array
    {
        static $alreadyRunInRequest = false;

        if ($alreadyRunInRequest) {
            return [
                'status' => 'SKIPPED_ALREADY_RUN',
                'tables' => 0,
                'rows' => 0,
            ];
        }

        $alreadyRunInRequest = true;

        $summary = [
            'status' => 'OK',
            'tables' => 0,
            'rows' => 0,
        ];

        $queryTables = "SELECT c.TABLE_NAME
                        FROM information_schema.COLUMNS c
                        WHERE c.TABLE_SCHEMA = DATABASE()
                          AND c.COLUMN_NAME = 'medir_productividad'
                          AND (c.TABLE_NAME LIKE ? ESCAPE '\\\\' OR c.TABLE_NAME LIKE ? ESCAPE '\\\\')";

        $stmtTables = $db->query($queryTables, ['%\\_programacion\\_semanal', '%\\_programa\\_consolidado']);
        $tables = $stmtTables->fetchAll(\PDO::FETCH_COLUMN);

        foreach ($tables as $tableName) {
            if (!preg_match('/^[a-zA-Z0-9_]+$/', (string)$tableName)) {
                continue;
            }

            $sqlDisable = "UPDATE `{$tableName}`
                           SET medir_productividad = 0
                           WHERE medir_productividad IS NULL OR medir_productividad <> 0";

            $stmtUpdate = $db->query($sqlDisable);
            $summary['tables'] += 1;
            $summary['rows'] += (int)$stmtUpdate->rowCount();
        }

        return $summary;
    }
}
