<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Security\CsrfTokenManager;
use App\Security\DataScope\ProjectScope;
use App\Security\RbacService;
use App\Services\RestrictionConfigResolver;
use App\Services\Shell\CrearSemanaComando;
use App\Services\Shell\DatabaseWeekAdministrationRepository;
use App\Services\Shell\EliminarSemanaComando;
use App\Services\Shell\ResultadoCreacionSemana;
use App\Services\Shell\ResultadoEliminacionSemana;
use App\Services\Shell\WeekAdministrationService;
use App\Services\Shell\WeekContextService;

/**
 * T01-API-06/07: los dos adaptadores tipados de administración de semana (crear,
 * eliminar-la-última) que la Tarea 5 agrega. Nunca aceptan `db`, prefijo ni proyecto del
 * cliente — el proyecto sale siempre de `ProjectScope`, la única autoridad. La lógica de negocio
 * vive en `WeekAdministrationService`; este controlador es transporte: CSRF, capacidad,
 * membresía/alcance, forma del cuerpo y el mapeo típado 4xx/5xx.
 */
class WeekContextApiController extends BaseController
{
    private const CSRF_FORM_KEY = 'shell_api';

    public function crear(): void
    {
        $this->requireAuth();
        $this->sendJsonHeaders();

        if (!$this->hasValidCsrfToken()) {
            $this->fallar(403, 'CSRF_INVALID', 'Token de seguridad inválido o ausente.');

            return;
        }

        $scope = $this->db->dataScope()->current();
        if (!$scope instanceof ProjectScope) {
            $this->fallar(409, 'NO_PROJECT_SCOPE', 'La operación requiere un proyecto activo.');

            return;
        }

        $rol = $scope->role();
        $rbac = new RbacService($this->db);
        if (!$rbac->can('lps.semana.crear')) {
            $this->fallar(403, 'FORBIDDEN', 'Tu rol no tiene permiso para crear semanas.');

            return;
        }

        $payload = json_decode((string) file_get_contents('php://input'), true);
        $startsOn = is_array($payload) ? ($payload['startsOn'] ?? null) : null;
        if (!is_string($startsOn) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $startsOn) || strtotime($startsOn) === false) {
            $this->fallar(422, 'INVALID_STARTS_ON', 'startsOn debe ser una fecha YYYY-MM-DD válida.');

            return;
        }

        $area = (string) ($_SESSION['area'] ?? 'Construccion');
        $preConstruccion = RestrictionConfigResolver::resolveByArea($area)['isPreConstruccion'];
        $esAdmin = $rol === 'A';

        $comando = new CrearSemanaComando($scope->projectId(), $startsOn, $preConstruccion, $esAdmin);

        try {
            $resultado = $this->servicio()->crear($comando);
        } catch (\Throwable $e) {
            error_log('WeekContextApiController::crear — error inesperado: ' . $e->getMessage());
            $this->fallar(500, 'WEEK_CREATE_FAILED', 'No se pudo crear la semana.');

            return;
        }

        if (!$resultado->exito) {
            $codigo = match ($resultado->motivoBloqueo) {
                ResultadoCreacionSemana::BLOQUEO_CIC_PENDIENTE => 'CIC_PENDIENTE',
                ResultadoCreacionSemana::BLOQUEO_SEMANA_NO_CONFIRMADA => 'SEMANA_NO_CONFIRMADA',
                ResultadoCreacionSemana::BLOQUEO_PROGRAMA_MAESTRO_VACIO => 'PROGRAMA_MAESTRO_VACIO',
                default => 'WEEK_CREATE_BLOCKED',
            };
            $this->fallar(409, $codigo, (string) $resultado->mensaje);

            return;
        }

        http_response_code(201);
        echo json_encode([
            'ok' => true,
            'week' => ['number' => $resultado->semana, 'startsOn' => $resultado->fechaInicio, 'endsOn' => $resultado->fechaFin],
        ], JSON_UNESCAPED_UNICODE);
    }

    public function eliminarUltima(): void
    {
        $this->requireAuth();
        $this->sendJsonHeaders();

        if (!$this->hasValidCsrfToken()) {
            $this->fallar(403, 'CSRF_INVALID', 'Token de seguridad inválido o ausente.');

            return;
        }

        $scope = $this->db->dataScope()->current();
        if (!$scope instanceof ProjectScope) {
            $this->fallar(409, 'NO_PROJECT_SCOPE', 'La operación requiere un proyecto activo.');

            return;
        }

        $rbac = new RbacService($this->db);
        if (!$rbac->can('lps.semana.eliminar')) {
            $this->fallar(403, 'FORBIDDEN', 'Tu rol no tiene permiso para eliminar semanas.');

            return;
        }

        $payload = json_decode((string) file_get_contents('php://input'), true);
        $semana = is_array($payload) ? ($payload['week'] ?? null) : null;
        if (!is_int($semana) && !(is_numeric($semana) && (int) $semana == $semana)) {
            $this->fallar(422, 'INVALID_WEEK', 'week debe ser un entero positivo.');

            return;
        }
        $semana = (int) $semana;
        if ($semana <= 0) {
            $this->fallar(422, 'INVALID_WEEK', 'week debe ser un entero positivo.');

            return;
        }

        $comando = new EliminarSemanaComando($scope->projectId(), $semana);

        try {
            $resultado = $this->servicio()->eliminarUltima($comando);
        } catch (\Throwable $e) {
            error_log('WeekContextApiController::eliminarUltima — error inesperado: ' . $e->getMessage());
            $this->fallar(500, 'WEEK_DELETE_FAILED', 'No se pudo eliminar la semana.');

            return;
        }

        if (!$resultado->exito) {
            $status = $resultado->motivoBloqueo === ResultadoEliminacionSemana::BLOQUEO_SIN_SEMANAS ? 404 : 409;
            $codigo = $resultado->motivoBloqueo === ResultadoEliminacionSemana::BLOQUEO_SIN_SEMANAS
                ? 'NO_ACTIVE_WEEKS'
                : 'WEEK_NOT_LAST';
            $this->fallar($status, $codigo, (string) $resultado->mensaje);

            return;
        }

        if ($semana === (int) ($_SESSION['semana'] ?? 0)) {
            // La semana activa en sesión era la eliminada: cae a la nueva máxima (o a 0 si el
            // proyecto se quedó sin semanas), igual que hacía `shell_week_admin.js` en el cliente.
            $_SESSION['semana'] = max(0, $resultado->nuevaSemanaMaxima ?? 0);
            session_write_close();
        }

        echo json_encode([
            'ok' => true,
            'deletedWeek' => $resultado->semanaEliminada,
            'maxWeek' => $resultado->nuevaSemanaMaxima,
        ], JSON_UNESCAPED_UNICODE);
    }

    private function servicio(): WeekAdministrationService
    {
        return new WeekAdministrationService(new DatabaseWeekAdministrationRepository($this->db));
    }

    private function hasValidCsrfToken(): bool
    {
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;

        return CsrfTokenManager::validate(is_string($token) ? $token : null, self::CSRF_FORM_KEY);
    }

    private function fallar(int $status, string $code, string $message): void
    {
        http_response_code($status);
        echo json_encode(['ok' => false, 'error' => ['code' => $code, 'message' => $message]], JSON_UNESCAPED_UNICODE);
    }

    private function sendJsonHeaders(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    }
}
