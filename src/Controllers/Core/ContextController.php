<?php

declare(strict_types=1);

namespace App\Controllers\Core;

use App\Controllers\BaseController;
use App\Security\CsrfTokenManager;
use App\Security\DataScope\ProjectScope;
use App\Security\RbacService;
use App\Services\Shell\DatabaseWeekAdministrationRepository;
use App\Services\Shell\WeekContextService;

/**
 * Seleccionar y limpiar la semana activa (T01-API-04/05). Completado en la Tarea 5: el contrato
 * original (`nueva_semana.php`/spec T01 §9) no traía CSRF ni verificaba que la semana
 * perteneciera al proyecto activo — ambos se agregan aquí, y la respuesta pasa de
 * `{success,message}` a devolver el contexto de semana canónico y refrescado (mismo shape que
 * `SessionApiController::activeWeek()`), para que el cliente nunca tenga que adivinar el estado
 * tras la mutación.
 */
class ContextController extends BaseController
{
    private const CSRF_FORM_KEY = 'shell_api';

    public function setWeek()
    {
        $this->requireAuth();
        header('Content-Type: application/json; charset=utf-8');

        if (!$this->hasValidCsrfToken()) {
            $this->fallar(403, 'CSRF_INVALID', 'Token de seguridad inválido o ausente.');

            return;
        }

        $input = json_decode((string) file_get_contents('php://input'), true);
        $semana = is_array($input) ? ($input['semana'] ?? null) : ($_POST['semana'] ?? null);

        if ($semana === null || !is_numeric($semana) || (int) $semana <= 0) {
            $this->fallar(400, 'INVALID_WEEK', 'Semana inválida.');

            return;
        }

        $scope = $this->db->dataScope()->current();
        if (!$scope instanceof ProjectScope) {
            $this->fallar(409, 'NO_PROJECT_SCOPE', 'La operación requiere un proyecto activo.');

            return;
        }

        $semana = (int) $semana;
        $servicio = $this->weekContextService();
        if (!$servicio->semanaPerteneceAlProyecto($scope->projectId(), $semana)) {
            $this->fallar(404, 'WEEK_NOT_FOUND', 'Esa semana no pertenece al proyecto activo.');

            return;
        }

        $_SESSION['semana'] = $semana;
        session_write_close();

        $rol = $scope->role();
        echo json_encode(['ok' => true, 'week' => $servicio->contextoActual($scope->projectId(), $semana, $rol)], JSON_UNESCAPED_UNICODE);
    }

    public function clearWeek()
    {
        $this->requireAuth();
        header('Content-Type: application/json; charset=utf-8');

        if (!$this->hasValidCsrfToken()) {
            $this->fallar(403, 'CSRF_INVALID', 'Token de seguridad inválido o ausente.');

            return;
        }

        $_SESSION['semana'] = 0;
        session_write_close();

        echo json_encode(['ok' => true, 'week' => null], JSON_UNESCAPED_UNICODE);
    }

    private function hasValidCsrfToken(): bool
    {
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;

        return CsrfTokenManager::validate(is_string($token) ? $token : null, self::CSRF_FORM_KEY);
    }

    private function weekContextService(): WeekContextService
    {
        return new WeekContextService($this->db, new DatabaseWeekAdministrationRepository($this->db));
    }

    private function fallar(int $status, string $code, string $message): void
    {
        http_response_code($status);
        echo json_encode(['ok' => false, 'error' => ['code' => $code, 'message' => $message]], JSON_UNESCAPED_UNICODE);
    }
}
