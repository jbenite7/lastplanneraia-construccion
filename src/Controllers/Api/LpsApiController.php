<?php

namespace App\Controllers\Api;

use App\Security\DataScope\ProjectScope;
use App\Security\RbacService;
use App\Services\Lps\LpsActionPolicy;
use App\Services\Lps\LpsActorEligibility;
use App\Services\Lps\LpsApiError;
use App\Services\Lps\LpsLegacyActorCompatibilityChecker;
use App\Services\Lps\LpsLegacyAlertRepository;
use App\Services\Lps\LpsLegacyGeneralActivityAdapter;
use App\Services\Lps\LpsLegacyIntermediateActivityAdapter;
use App\Services\Lps\LpsLegacyThreadRepository;
use App\Services\Lps\LpsLegacyWeeklyActivityAdapter;
use App\Services\Lps\LpsTarget;
use App\Services\Lps\LpsTargetException;
use App\Services\Lps\LpsTargetRequest;
use App\Services\Lps\LpsTargetResolver;
use App\Services\Lps\LpsThreadPresenter;
use App\Services\Lps\LpsThreadService;
use App\Services\LpsService;
use PDO;
use Throwable;

class LpsApiController
{
    private $db;
    private $lpsService;

    public function __construct()
    {
        $this->db = \Database::getInstance();
        $this->lpsService = new LpsService();
    }

    private function getContext(): array
    {
        $dbPrefix = $_SESSION['db'] ?? '';
        $semana = (int) ($_SESSION['semana'] ?? 0);
        $proyecto = $_SESSION['proyecto'] ?? '';

        if (!$dbPrefix || $semana <= 0 || !$proyecto) {
            return [];
        }

        // Consultar ID de usuario
        $userStmt = $this->db->query("SELECT Id FROM general_usuarios WHERE usuario = ? LIMIT 1", [$_SESSION['usuario'] ?? '']);
        $user = $userStmt->fetch(PDO::FETCH_ASSOC);
        $usuarioId = $user ? (int) $user['Id'] : 0;

        // Consultar ID de proyecto
        $projStmt = $this->db->query("SELECT ID FROM general_proyectos_procesos WHERE Proyecto_Proceso = ? AND Area = 'Construccion' LIMIT 1", [$proyecto]);
        $proj = $projStmt->fetch(PDO::FETCH_ASSOC);
        $proyectoId = $proj ? (int) $proj['ID'] : 0;

        return [
            'dbPrefix' => $dbPrefix,
            'semana' => $semana,
            'usuarioId' => $usuarioId,
            'proyectoId' => $proyectoId,
        ];
    }

    /**
     * GET /api/lps/comments (T02-AC-075..104). Resuelve un target server-authoritative (por
     * `alerta_id`, o por `consecutivo`+`modulo`, o legacy por `consecutivo` solo) y añade el
     * sobre aditivo (`ok`, `target`, `actions`, `comments`, `crisisAlert`, `meta`) sin tocar las
     * claves que `lps_drawer.js` ya lee de `respuesta`/`data`.
     *
     * El camino puramente legacy (sin `alerta_id` ni `modulo`, exactamente lo que envía
     * `lps_drawer.js` hoy) conserva dos comportamientos byte-a-byte, caracterizados en
     * `tests/test_lps_api_contract.php` ANTES de esta tarea: `consecutivo<=0` responde el
     * mensaje literal "Actividad inválida." y un `consecutivo` que no existe en ningún módulo
     * responde `OK` con `data` vacío en vez del 404 `LPS_TARGET_NOT_FOUND` que el contrato nuevo
     * usa para las llamadas tipadas de React.
     */
    public function comments(): void
    {
        require_once PROJECT_ROOT . '/src/Legacy/rbac_guard.php';
        rbac_guard_require_permission('lps.programacion_semanal.ver');
        header('Content-Type: application/json; charset=utf-8');
        $context = $this->getContext();
        if (empty($context)) {
            echo json_encode(["respuesta" => "ERROR", "mensaje" => "Sesión expirada o contexto inválido."], JSON_UNESCAPED_UNICODE);
            return;
        }

        $scope = $this->db->dataScope()->current();
        if (!$scope instanceof ProjectScope) {
            $this->renderApiError(LpsApiError::sessionRequired());
            return;
        }

        $alertaIdRaw = $_GET['alerta_id'] ?? null;
        $consecutivoRaw = $_GET['consecutivo'] ?? null;
        $modulo = (isset($_GET['modulo']) && $_GET['modulo'] !== '') ? strtoupper(trim((string) $_GET['modulo'])) : null;
        $escalamientoId = !empty($_GET['escalamiento_id']) ? filter_var($_GET['escalamiento_id'], FILTER_VALIDATE_INT) : null;
        $isPureLegacy = $alertaIdRaw === null && $modulo === null;

        if ($isPureLegacy) {
            $consecutivo = filter_var($consecutivoRaw ?? 0, FILTER_VALIDATE_INT);
            if ($consecutivo === false || $consecutivo <= 0) {
                $this->renderLegacyError('Actividad inválida.', ['consecutivo' => 'Debe ser un entero positivo.']);
                return;
            }
        }

        $request = $this->buildTargetRequest($consecutivoRaw, $modulo, $alertaIdRaw, $escalamientoId);

        try {
            $target = $this->buildTargetResolver($scope, $context['dbPrefix'])->resolve($request);
            $this->renderThreadSnapshot($target, $context);
        } catch (LpsTargetException $e) {
            if ($isPureLegacy && $e->apiError()->code === 'LPS_TARGET_NOT_FOUND') {
                // Lectura legacy pura de una actividad inexistente: OK/data vacío (caracterizado
                // en test_lps_api_contract.php), no un 404 tipado.
                echo json_encode([
                    'respuesta' => 'OK',
                    'ok' => true,
                    'data' => [],
                    'comments' => [],
                    'meta' => ['requestId' => bin2hex(random_bytes(8))],
                ], JSON_UNESCAPED_UNICODE);

                return;
            }
            $this->renderApiError($e->apiError());
        } catch (Throwable $t) {
            error_log('LpsApiController::comments — ' . $t->getMessage());
            $this->renderApiError(LpsApiError::readFailed());
        }
    }

    /** @param array{dbPrefix: string, semana: int, usuarioId: int, proyectoId: int} $context */
    private function renderThreadSnapshot(LpsTarget $target, array $context): void
    {
        $rbac = new RbacService($this->db);
        $canRead = $rbac->can('lps.programacion_semanal.ver');
        $canEdit = $rbac->can('lps.programacion_semanal.editar');
        $eligibility = new LpsActorEligibility(new LpsLegacyActorCompatibilityChecker($this->db, $context['dbPrefix']));
        $actorEligibility = $eligibility->evaluate($target->projectId, $context['usuarioId'], $canEdit);
        $actions = (new LpsActionPolicy())->evaluate($target, $canRead, $canEdit, $actorEligibility);

        $threadService = new LpsThreadService(new LpsLegacyThreadRepository($this->db, $context['dbPrefix']));
        $flat = $threadService->read($target);
        $presenter = new LpsThreadPresenter();

        $payload = [
            'respuesta' => 'OK',
            'ok' => true,
            'data' => $presenter->presentLegacy($flat),
            'comments' => $presenter->presentReact($flat),
            'target' => $this->targetToArray($target),
            'actions' => $actions,
            'meta' => ['requestId' => bin2hex(random_bytes(8))],
        ];

        if ($target->isAlert()) {
            $payload['crisisAlert'] = [
                'id' => $target->alertId,
                'active' => $target->alertActive,
                'level' => $target->alertLevel,
            ];
        }

        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    }

    /**
     * POST /api/lps/comments/add y alias /api/lps/comments (T02-AC-093..104). El actor de
     * comentario se resuelve en servidor (`context['usuarioId']`, nunca del POST); si
     * `actorWriteBlock` no es `none`, la escritura se bloquea con `PROFILE_REQUIRED` o
     * `CAPABILITY_REQUIRED` antes de tocar el repositorio.
     */
    public function addComment(): void
    {
        require_once PROJECT_ROOT . '/src/Legacy/rbac_guard.php';
        rbac_guard_require_permission('lps.programacion_semanal.editar');
        legacy_require_csrf('lps_drawer');
        header('Content-Type: application/json; charset=utf-8');
        $context = $this->getContext();
        if (empty($context)) {
            echo json_encode(["respuesta" => "ERROR", "mensaje" => "Sesión expirada o contexto inválido."], JSON_UNESCAPED_UNICODE);
            return;
        }

        $scope = $this->db->dataScope()->current();
        if (!$scope instanceof ProjectScope) {
            $this->renderApiError(LpsApiError::sessionRequired());
            return;
        }

        $alertaIdRaw = $_POST['alerta_id'] ?? null;
        $consecutivoRaw = $_POST['consecutivo'] ?? null;
        $modulo = (isset($_POST['modulo']) && $_POST['modulo'] !== '') ? strtoupper(trim((string) $_POST['modulo'])) : null;
        $comentario = trim($_POST['comentario'] ?? '');
        $parentId = !empty($_POST['parent_id']) ? filter_var($_POST['parent_id'], FILTER_VALIDATE_INT) : null;
        $escalamientoId = !empty($_POST['escalamiento_id']) ? filter_var($_POST['escalamiento_id'], FILTER_VALIDATE_INT) : null;
        $mencionesRaw = !empty($_POST['menciones']) ? json_decode((string) $_POST['menciones'], true) : null;
        $isPureLegacy = $alertaIdRaw === null && $modulo === null;

        // T02-AC-096/097: recorta y exige no vacío, sin imponer un límite nuevo. Preserva el
        // mensaje literal legacy para el camino que `lps_drawer.js` ya usa.
        if ($comentario === '' || ($isPureLegacy && filter_var($consecutivoRaw ?? 0, FILTER_VALIDATE_INT) <= 0)) {
            if ($isPureLegacy) {
                $this->renderLegacyError('Comentario y actividad requeridos.', ['comentario' => 'Requerido.', 'consecutivo' => 'Requerido.']);
                return;
            }
            $this->renderApiError(LpsApiError::validationFailed(['comentario' => 'Requerido y no puede estar vacío.']));
            return;
        }

        $request = $this->buildTargetRequest($consecutivoRaw, $modulo, $alertaIdRaw, $escalamientoId);

        try {
            $target = $this->buildTargetResolver($scope, $context['dbPrefix'])->resolve($request);

            $rbac = new RbacService($this->db);
            $canRead = $rbac->can('lps.programacion_semanal.ver');
            $canEdit = $rbac->can('lps.programacion_semanal.editar');
            $eligibility = new LpsActorEligibility(new LpsLegacyActorCompatibilityChecker($this->db, $context['dbPrefix']));
            $actorEligibility = $eligibility->evaluate($target->projectId, $context['usuarioId'], $canEdit);
            $actions = (new LpsActionPolicy())->evaluate($target, $canRead, $canEdit, $actorEligibility);

            if (!$actions['comment']) {
                // T02-AC-100: PROFILE_REQUIRED bloquea sólo la escritura; la lectura sigue abierta.
                $error = $actions['actorWriteBlock'] === 'profile_required'
                    ? LpsApiError::profileRequired()
                    : LpsApiError::capabilityRequired();
                $this->renderApiError($error);

                return;
            }

            $mentions = LpsThreadService::normalizeMentions(is_array($mencionesRaw) ? $mencionesRaw : null);
            $threadService = new LpsThreadService(new LpsLegacyThreadRepository($this->db, $context['dbPrefix']));
            $commentId = $threadService->addComment($target, $context['usuarioId'], $comentario, $parentId ? (int) $parentId : null, $mentions);

            if ($commentId > 0) {
                // T02-AC-093/101: comment_id se conserva para legacy; data.commentId es aditivo.
                echo json_encode([
                    'respuesta' => 'OK',
                    'ok' => true,
                    'comment_id' => $commentId,
                    'data' => ['commentId' => $commentId],
                    'target' => $this->targetToArray($target),
                    'meta' => ['requestId' => bin2hex(random_bytes(8))],
                ], JSON_UNESCAPED_UNICODE);
            } else {
                $this->renderApiError(LpsApiError::serviceUnavailable());
            }
        } catch (LpsTargetException $e) {
            $this->renderApiError($e->apiError());
        } catch (Throwable $t) {
            error_log('LpsApiController::addComment — ' . $t->getMessage());
            $this->renderApiError(LpsApiError::serviceUnavailable());
        }
    }

    private function buildTargetRequest(mixed $consecutivoRaw, ?string $modulo, mixed $alertaIdRaw, int|false|null $escalamientoId): LpsTargetRequest
    {
        $consecutivo = $consecutivoRaw !== null ? filter_var($consecutivoRaw, FILTER_VALIDATE_INT) : null;
        $alertaId = $alertaIdRaw !== null ? filter_var($alertaIdRaw, FILTER_VALIDATE_INT) : null;

        return new LpsTargetRequest(
            activityId: ($consecutivo !== null && $consecutivo !== false && $consecutivo > 0) ? (int) $consecutivo : null,
            module: $modulo,
            alertId: ($alertaId !== null && $alertaId !== false && $alertaId > 0) ? (int) $alertaId : null,
            escalamientoId: ($escalamientoId !== null && $escalamientoId !== false) ? (int) $escalamientoId : null,
        );
    }

    private function buildTargetResolver(ProjectScope $scope, string $dbPrefix): LpsTargetResolver
    {
        return new LpsTargetResolver(
            $scope,
            new LpsLegacyAlertRepository($this->db, $dbPrefix),
            [
                new LpsLegacyGeneralActivityAdapter($this->db, $dbPrefix),
                new LpsLegacyIntermediateActivityAdapter($this->db, $dbPrefix),
                new LpsLegacyWeeklyActivityAdapter($this->db, $dbPrefix),
            ],
        );
    }

    /** @return array<string, mixed> */
    private function targetToArray(LpsTarget $target): array
    {
        $data = [
            'kind' => $target->kind,
            'activityId' => $target->activityId,
            'module' => $target->module,
            'week' => $target->week,
        ];

        if ($target->isAlert()) {
            $data['alertId'] = $target->alertId;
        }

        return $data;
    }

    private function renderApiError(LpsApiError $error): void
    {
        http_response_code($error->httpStatus);
        echo json_encode([
            'respuesta' => 'ERROR',
            'ok' => false,
            'mensaje' => $error->message,
            'error' => [
                'code' => $error->code,
                'message' => $error->message,
                'fields' => $error->fields,
            ],
            'meta' => ['requestId' => bin2hex(random_bytes(8))],
        ], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Error del camino legacy puro: conserva `mensaje` byte a byte (lo que
     * `test_lps_api_contract.php` ya caracterizó, HTTP 200 incluido — nunca llama
     * `http_response_code`) y añade `ok`/`error` de forma aditiva (D-T02-08).
     *
     * @param array<string, string> $fields
     */
    private function renderLegacyError(string $mensaje, array $fields = []): void
    {
        echo json_encode([
            'respuesta' => 'ERROR',
            'ok' => false,
            'mensaje' => $mensaje,
            'error' => [
                'code' => 'VALIDATION_FAILED',
                'message' => $mensaje,
                'fields' => $fields,
            ],
            'meta' => ['requestId' => bin2hex(random_bytes(8))],
        ], JSON_UNESCAPED_UNICODE);
    }

    public function registerCrisis(): void
    {
        require_once PROJECT_ROOT . '/src/Legacy/rbac_guard.php';
        rbac_guard_require_permission('lps.programacion_semanal.editar');
        legacy_require_csrf('lps_drawer');
        header('Content-Type: application/json; charset=utf-8');
        $context = $this->getContext();
        if (empty($context)) {
            echo json_encode(["respuesta" => "ERROR", "mensaje" => "Sesión expirada o contexto inválido."], JSON_UNESCAPED_UNICODE);
            return;
        }

        $consecutivo = filter_var($_POST['consecutivo'] ?? 0, FILTER_VALIDATE_INT);
        $modulo = $_POST['modulo'] ?? '';
        $trigger = $_POST['trigger'] ?? 'MANUAL';

        if ($consecutivo <= 0 || !in_array($modulo, ['PG', 'PI', 'PS'], true)) {
            echo json_encode(["respuesta" => "ERROR", "mensaje" => "Módulo y actividad requeridos."], JSON_UNESCAPED_UNICODE);
            return;
        }

        try {
            $res = $this->lpsService->registerCrisisAlert(
                $context['dbPrefix'],
                $context['proyectoId'],
                $context['semana'],
                $consecutivo,
                $modulo,
                $trigger,
            );

            echo json_encode(["respuesta" => $res ? "OK" : "ERROR", "mensaje" => $res ? "Alerta registrada" : "No se pudo registrar la alerta"], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $t) {
            echo json_encode(["respuesta" => "ERROR", "mensaje" => $t->getMessage()], JSON_UNESCAPED_UNICODE);
        }
    }

    public function closeCrisis(): void
    {
        require_once PROJECT_ROOT . '/src/Legacy/rbac_guard.php';
        rbac_guard_require_permission('lps.programacion_semanal.editar');
        legacy_require_csrf('lps_drawer');
        header('Content-Type: application/json; charset=utf-8');
        $context = $this->getContext();
        if (empty($context)) {
            echo json_encode(["respuesta" => "ERROR", "mensaje" => "Sesión expirada o contexto inválido."], JSON_UNESCAPED_UNICODE);
            return;
        }

        $alertaId = filter_var($_POST['alerta_id'] ?? 0, FILTER_VALIDATE_INT);
        $justificacion = trim($_POST['justificacion'] ?? '');

        if ($alertaId <= 0 || mb_strlen($justificacion) < 100) {
            echo json_encode(["respuesta" => "ERROR", "mensaje" => "Justificación obligatoria de al menos 100 caracteres."], JSON_UNESCAPED_UNICODE);
            return;
        }

        try {
            $res = $this->lpsService->closeCrisisAlert(
                $context['dbPrefix'],
                (int) $alertaId,
                $context['usuarioId'],
                $justificacion,
            );

            echo json_encode(["respuesta" => $res ? "OK" : "ERROR", "mensaje" => $res ? "Crisis mitigada exitosamente" : "No se pudo mitigar la crisis"], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $t) {
            echo json_encode(["respuesta" => "ERROR", "mensaje" => $t->getMessage()], JSON_UNESCAPED_UNICODE);
        }
    }
}
