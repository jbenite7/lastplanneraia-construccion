<?php

namespace App\Services;

use App\Core\Lps\LpsService;
use Database;
use PDO;
use Throwable;

class ProjectLandingService
{
    private $db;
    private LpsService $lpsService;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->lpsService = new LpsService();
    }

    public function resolve(string $dbName, string $role): array
    {
        $normalizedRole = $this->normalizeRole($role);

        if (!$this->isValidDbPrefix($dbName)) {
            return [
                'route' => $this->fallbackRouteForRole($normalizedRole),
                'module' => 'fallback',
                'week' => 0,
                'hasActiveWeeks' => false,
                'maxActiveWeek' => 0,
                'maxConfirmedWeek' => null,
                'reason' => 'invalid-db-prefix',
            ];
        }

        $weekMetadata = $this->getWeekMetadata($dbName);
        $maxActiveWeek = $weekMetadata['maxActiveWeek'];
        $maxConfirmedWeek = $weekMetadata['maxConfirmedWeek'];

        if ($maxActiveWeek <= 0) {
            return [
                'route' => '/programa-general-actualizar',
                'module' => 'programa-general-actualizar',
                'week' => 0,
                'hasActiveWeeks' => false,
                'maxActiveWeek' => 0,
                'maxConfirmedWeek' => null,
                'reason' => 'no-active-weeks',
            ];
        }

        $preferredContext = $this->determinePreferredContext($dbName, $weekMetadata);

        return [
            'route' => $this->routeForRoleAndModule($normalizedRole, $preferredContext['module']),
            'module' => $preferredContext['module'],
            'week' => $preferredContext['week'],
            'hasActiveWeeks' => true,
            'maxActiveWeek' => $maxActiveWeek,
            'maxConfirmedWeek' => $maxConfirmedWeek,
            'reason' => $preferredContext['reason'],
        ];
    }

    public function sanitizeWeek(string $dbName, int $week): array
    {
        if (!$this->isValidDbPrefix($dbName)) {
            return [
                'week' => 0,
                'hasActiveWeeks' => false,
                'maxActiveWeek' => 0,
                'maxConfirmedWeek' => null,
            ];
        }

        $weekMetadata = $this->getWeekMetadata($dbName);
        $maxActiveWeek = $weekMetadata['maxActiveWeek'];
        $maxConfirmedWeek = $weekMetadata['maxConfirmedWeek'];
        $weeks = $weekMetadata['weeks'];

        if ($maxActiveWeek <= 0) {
            return [
                'week' => 0,
                'hasActiveWeeks' => false,
                'maxActiveWeek' => 0,
                'maxConfirmedWeek' => null,
            ];
        }

        $safeWeek = $week;
        if ($safeWeek <= 0 || !isset($weeks[$safeWeek])) {
            $preferredContext = $this->determinePreferredContext($dbName, $weekMetadata);
            $safeWeek = (int) $preferredContext['week'];
        }

        return [
            'week' => $safeWeek,
            'hasActiveWeeks' => true,
            'maxActiveWeek' => $maxActiveWeek,
            'maxConfirmedWeek' => $maxConfirmedWeek,
        ];
    }

    public function normalizeRole(string $role): string
    {
        $role = strtoupper(trim($role));

        if ($role === 'P') {
            return 'D';
        }

        if ($role === 'U' || $role === '') {
            return 'V';
        }

        return $role;
    }

    private function getWeekMetadata(string $dbName): array
    {
        $weeks = [];
        $maxConfirmedWeek = null;

        try {
            $query = "SELECT Semana, Semanal_Confirmada FROM {$dbName}_semanas_activas ORDER BY Semana ASC";
            $rows = $this->db->query($query)->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            error_log('ProjectLandingService week metadata error: ' . $e->getMessage());

            return [
                'weeks' => [],
                'maxActiveWeek' => 0,
                'maxConfirmedWeek' => null,
            ];
        }

        foreach ($rows as $row) {
            $weekNumber = (int) ($row['Semana'] ?? 0);
            if ($weekNumber <= 0) {
                continue;
            }

            $weeks[$weekNumber] = [
                'confirmed' => (int) ($row['Semanal_Confirmada'] ?? 0) === 1,
            ];

            if ($weeks[$weekNumber]['confirmed']) {
                $maxConfirmedWeek = $weekNumber;
            }
        }

        $maxActiveWeek = empty($weeks) ? 0 : max(array_keys($weeks));

        return [
            'weeks' => $weeks,
            'maxActiveWeek' => $maxActiveWeek,
            'maxConfirmedWeek' => $maxConfirmedWeek,
        ];
    }

    private function hasPendingCalificacion(string $dbName, int $week): bool
    {
        if ($week <= 0) {
            return false;
        }

        try {
            $query = "SELECT Activa, Ejecutado, Compromiso, Ejecutado_Real, Critica, Prog_Sin_Restricciones_100, Categoria_CNC, CNC FROM {$dbName}_programacion_semanal WHERE Semana = ?";
            $rows = $this->db->query($query, [$week])->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            error_log('ProjectLandingService pending calificacion check error: ' . $e->getMessage());

            return false;
        }

        foreach ($rows as $row) {
            if (!$this->lpsService->isActiveRow($row)) {
                continue;
            }

            if ($this->lpsService->classifyWeeklyState($row, 'calificacion') === 'cal-sin-calificar') {
                return true;
            }

            if ($this->hasMissingRequiredCnc($row)) {
                return true;
            }
        }

        return false;
    }

    private function determinePreferredContext(string $dbName, array $weekMetadata): array
    {
        $maxActiveWeek = (int) ($weekMetadata['maxActiveWeek'] ?? 0);

        $weeks = $weekMetadata['weeks'] ?? [];
        $weekNumbers = array_keys($weeks);
        rsort($weekNumbers, SORT_NUMERIC);

        $highestPendingCalificacionWeek = null;
        $highestOpenWeek = null;

        foreach ($weekNumbers as $weekNumber) {
            $weekNumber = (int) $weekNumber;
            $week = $weeks[$weekNumber] ?? null;

            if ($week === null) {
                continue;
            }

            if (($week['confirmed'] ?? false) === true) {
                if ($highestPendingCalificacionWeek === null && $this->hasPendingCalificacion($dbName, $weekNumber)) {
                    $highestPendingCalificacionWeek = $weekNumber;
                }

                continue;
            }

            if ($highestOpenWeek === null) {
                $highestOpenWeek = $weekNumber;
            }
        }

        if ($highestOpenWeek !== null && ($highestPendingCalificacionWeek === null || $highestOpenWeek >= $highestPendingCalificacionWeek)) {
            return [
                'week' => $highestOpenWeek,
                'module' => 'programacion-semanal',
                'reason' => 'highest-open-week',
            ];
        }

        if ($highestPendingCalificacionWeek !== null) {
            return [
                'week' => $highestPendingCalificacionWeek,
                'module' => 'programacion-semanal',
                'reason' => 'highest-confirmed-week-pending-calificacion',
            ];
        }

        return [
            'week' => $maxActiveWeek,
            'module' => 'programacion-semanal',
            'reason' => 'latest-week-confirmed-without-pending-or-open-activities',
        ];
    }

    private function hasMissingRequiredCnc(array $row): bool
    {
        $compromiso = $this->lpsService->toFloat($row['Compromiso'] ?? null, null);
        $ejecutadoReal = $this->lpsService->toFloat($row['Ejecutado_Real'] ?? null, null);

        if ($compromiso === null || $ejecutadoReal === null || $compromiso <= 0) {
            return false;
        }

        if (($ejecutadoReal + 0.0001) >= $compromiso) {
            return false;
        }

        return $this->lpsService->isBlank($row['Categoria_CNC'] ?? null)
            || $this->lpsService->isBlank($row['CNC'] ?? null);
    }

    private function routeForRoleAndModule(string $role, string $preferredModule): string
    {
        if (in_array($role, ['G', 'S', 'SG'], true)) {
            return '/programacion-semanal/cic';
        }

        if ($role === 'C') {
            return '/programacion-semanal';
        }

        if ($preferredModule === 'programacion-semanal') {
            return '/programacion-semanal';
        }

        return '/programa-general';
    }

    private function fallbackRouteForRole(string $role): string
    {
        if (in_array($role, ['G', 'S', 'SG'], true)) {
            return '/programacion-semanal/cic';
        }

        if ($role === 'C') {
            return '/programacion-semanal';
        }

        return '/programa-general';
    }

    private function isValidDbPrefix(string $dbName): bool
    {
        return $dbName !== '' && preg_match('/^[A-Za-z0-9_]+$/', $dbName) === 1;
    }
}
