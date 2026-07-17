<?php

namespace App\Controllers\Api;

use Admin\Core\RoleManager;
use App\Controllers\BaseController;
use App\Security\CsrfTokenManager;
use App\Services\SemiAutoAssistantService;
use App\Services\SemiAutoService;
use App\Support\ModuleRequestContext;
use DomainException;
use Throwable;

class SemiAutoController extends BaseController
{
    private SemiAutoService $service;
    private SemiAutoAssistantService $assistant;

    public function __construct()
    {
        parent::__construct();
        $this->service = new SemiAutoService($this->db);
        $this->assistant = new SemiAutoAssistantService($this->db);
    }

    public function previewListado(): void
    {
        $this->preview(SemiAutoService::MODULE_LISTADO);
    }

    public function statusListado(): void
    {
        $this->status(SemiAutoService::MODULE_LISTADO);
    }

    public function applyListado(): void
    {
        $this->apply(SemiAutoService::MODULE_LISTADO);
    }

    public function undoListado(): void
    {
        $this->undo(SemiAutoService::MODULE_LISTADO);
    }

    public function feedbackListado(): void
    {
        $this->feedback(SemiAutoService::MODULE_LISTADO);
    }

    public function metricsListado(): void
    {
        $this->metrics(SemiAutoService::MODULE_LISTADO);
    }

    public function assistantInboxListado(): void
    {
        $this->assistantInbox(SemiAutoService::MODULE_LISTADO);
    }

    public function assistantAckListado(): void
    {
        $this->assistantAck(SemiAutoService::MODULE_LISTADO);
    }

    public function assistantFeedbackListado(): void
    {
        $this->assistantFeedback(SemiAutoService::MODULE_LISTADO);
    }

    public function learningCandidatesListado(): void
    {
        $this->learningCandidates(SemiAutoService::MODULE_LISTADO);
    }

    public function learningApproveListado(): void
    {
        $this->learningApprove(SemiAutoService::MODULE_LISTADO);
    }

    public function learningRejectListado(): void
    {
        $this->learningReject(SemiAutoService::MODULE_LISTADO);
    }

    public function previewContratos(): void
    {
        $this->preview(SemiAutoService::MODULE_CONTRATOS);
    }

    public function statusContratos(): void
    {
        $this->status(SemiAutoService::MODULE_CONTRATOS);
    }

    public function applyContratos(): void
    {
        $this->apply(SemiAutoService::MODULE_CONTRATOS);
    }

    public function undoContratos(): void
    {
        $this->undo(SemiAutoService::MODULE_CONTRATOS);
    }

    public function feedbackContratos(): void
    {
        $this->feedback(SemiAutoService::MODULE_CONTRATOS);
    }

    public function metricsContratos(): void
    {
        $this->metrics(SemiAutoService::MODULE_CONTRATOS);
    }

    public function assistantInboxContratos(): void
    {
        $this->assistantInbox(SemiAutoService::MODULE_CONTRATOS);
    }

    public function assistantAckContratos(): void
    {
        $this->assistantAck(SemiAutoService::MODULE_CONTRATOS);
    }

    public function assistantFeedbackContratos(): void
    {
        $this->assistantFeedback(SemiAutoService::MODULE_CONTRATOS);
    }

    public function learningCandidatesContratos(): void
    {
        $this->learningCandidates(SemiAutoService::MODULE_CONTRATOS);
    }

    public function learningApproveContratos(): void
    {
        $this->learningApprove(SemiAutoService::MODULE_CONTRATOS);
    }

    public function learningRejectContratos(): void
    {
        $this->learningReject(SemiAutoService::MODULE_CONTRATOS);
    }

    public function previewPdc(): void
    {
        $this->requirePdcCsrf();
        $this->preview(SemiAutoService::MODULE_PDC);
    }

    public function statusPdc(): void
    {
        $this->status(SemiAutoService::MODULE_PDC);
    }

    public function applyPdc(): void
    {
        $this->requirePdcCsrf();
        $this->apply(SemiAutoService::MODULE_PDC);
    }

    public function undoPdc(): void
    {
        $this->requirePdcCsrf();
        $this->undo(SemiAutoService::MODULE_PDC);
    }

    public function feedbackPdc(): void
    {
        $this->requirePdcCsrf();
        $this->feedback(SemiAutoService::MODULE_PDC);
    }

    public function metricsPdc(): void
    {
        $this->metrics(SemiAutoService::MODULE_PDC);
    }

    public function assistantInboxPdc(): void
    {
        $this->assistantInbox(SemiAutoService::MODULE_PDC);
    }

    public function assistantAckPdc(): void
    {
        $this->requirePdcCsrf();
        $this->assistantAck(SemiAutoService::MODULE_PDC);
    }

    public function assistantFeedbackPdc(): void
    {
        $this->requirePdcCsrf();
        $this->assistantFeedback(SemiAutoService::MODULE_PDC);
    }

    public function learningCandidatesPdc(): void
    {
        $this->learningCandidates(SemiAutoService::MODULE_PDC);
    }

    public function learningApprovePdc(): void
    {
        $this->requirePdcCsrf();
        $this->learningApprove(SemiAutoService::MODULE_PDC);
    }

    public function learningRejectPdc(): void
    {
        $this->requirePdcCsrf();
        $this->learningReject(SemiAutoService::MODULE_PDC);
    }

    private function preview(string $module): void
    {
        try {
            $this->authorizeModule($module, 'preview');
            $payload = $this->jsonPayload();
            $context = ModuleRequestContext::resolve();
            $runId = (string) ($payload['run_id'] ?? ($_POST['run_id'] ?? ''));
            $this->releaseSessionForPolling();
            $this->json($this->service->preview($module, $context, $runId));
        } catch (Throwable $e) {
            $this->jsonError('No se pudo generar la vista previa automática.', 500, $e);
        }
    }

    private function status(string $module): void
    {
        try {
            $this->authorizeModule($module, 'preview');
            $payload = $this->jsonPayload();
            $runId = (string) ($payload['run_id'] ?? ($_POST['run_id'] ?? ($_GET['run_id'] ?? '')));
            if ($runId === '') {
                $this->jsonError('Solicitud inválida.', 400);
                return;
            }
            $context = ModuleRequestContext::resolve();
            $this->releaseSessionForPolling();
            $this->json($this->service->status($module, $context, $runId));
        } catch (Throwable $e) {
            $this->jsonError('No se pudo consultar el estado del análisis.', 500, $e);
        }
    }

    private function apply(string $module): void
    {
        try {
            $this->authorizeModule($module, 'apply');
            $payload = $this->jsonPayload();
            $runId = (string) ($payload['run_id'] ?? '');
            $suggestionIds = $payload['suggestion_ids'] ?? [];
            if ($runId === '' || !is_array($suggestionIds)) {
                $this->jsonError('Solicitud inválida.', 400);
                return;
            }
            $this->json($this->service->apply($module, ModuleRequestContext::resolve(), $runId, $suggestionIds));
        } catch (Throwable $e) {
            $this->jsonError('No se pudieron aplicar las sugerencias.', 500, $e);
        }
    }

    private function undo(string $module): void
    {
        try {
            $this->authorizeModule($module, 'undo');
            $payload = $this->jsonPayload();
            $runId = (string) ($payload['run_id'] ?? ($_GET['run_id'] ?? ''));
            if ($runId === '') {
                $this->jsonError('Solicitud inválida.', 400);
                return;
            }
            $this->json($this->service->undo($module, ModuleRequestContext::resolve(), $runId));
        } catch (DomainException $e) {
            $this->jsonError($e->getMessage(), 409);
        } catch (Throwable $e) {
            $this->jsonError('No se pudo deshacer la corrida automática.', 500, $e);
        }
    }

    private function feedback(string $module): void
    {
        try {
            $this->authorizeModule($module, 'feedback');
            $this->json($this->service->feedback($module, ModuleRequestContext::resolve(), $this->jsonPayload()));
        } catch (Throwable $e) {
            $this->jsonError('No se pudo registrar el feedback.', 500, $e);
        }
    }

    private function metrics(string $module): void
    {
        try {
            $this->authorizeModule($module, 'metrics');
            $this->json($this->service->metrics($module, ModuleRequestContext::resolve()));
        } catch (Throwable $e) {
            $this->jsonError('No se pudieron consultar las métricas.', 500, $e);
        }
    }

    private function assistantInbox(string $module): void
    {
        try {
            $this->authorizeModule($module, 'metrics');
            $this->json($this->assistant->inbox($module, ModuleRequestContext::resolve()));
        } catch (Throwable $e) {
            $this->jsonError('No se pudo consultar el asistente AIA.', 500, $e);
        }
    }

    private function assistantAck(string $module): void
    {
        try {
            $this->authorizeModule($module, 'feedback');
            $this->json($this->assistant->ack($module, ModuleRequestContext::resolve(), $this->jsonPayload()));
        } catch (Throwable $e) {
            $this->jsonError('No se pudo actualizar la alerta del asistente.', 500, $e);
        }
    }

    private function assistantFeedback(string $module): void
    {
        try {
            $this->authorizeModule($module, 'feedback');
            $this->json($this->assistant->assistantFeedback($module, ModuleRequestContext::resolve(), $this->jsonPayload()));
        } catch (Throwable $e) {
            $this->jsonError('No se pudo registrar la retroalimentación del asistente.', 500, $e);
        }
    }

    private function learningCandidates(string $module): void
    {
        try {
            $this->authorizeModule($module, 'metrics');
            $this->json($this->assistant->learningCandidates($module, ModuleRequestContext::resolve()));
        } catch (Throwable $e) {
            $this->jsonError('No se pudieron consultar aprendizajes.', 500, $e);
        }
    }

    private function learningApprove(string $module): void
    {
        try {
            $this->authorizeAdminLearning($module);
            $this->json($this->assistant->approveLearning($module, ModuleRequestContext::resolve(), $this->jsonPayload()));
        } catch (Throwable $e) {
            $this->jsonError('No se pudo aprobar el aprendizaje.', 500, $e);
        }
    }

    private function learningReject(string $module): void
    {
        try {
            $this->authorizeAdminLearning($module);
            $this->json($this->assistant->rejectLearning($module, ModuleRequestContext::resolve(), $this->jsonPayload()));
        } catch (Throwable $e) {
            $this->jsonError('No se pudo rechazar el aprendizaje.', 500, $e);
        }
    }

    private function authorizeModule(string $module, string $action): void
    {
        $permission = match ($module) {
            SemiAutoService::MODULE_LISTADO => 'lps.listado_actividades.editar',
            SemiAutoService::MODULE_CONTRATOS => 'lps.contratos.auto_definir',
            SemiAutoService::MODULE_PDC => 'lps.pdc.auto_generar',
        };

        if ($action === 'metrics') {
            $permission = match ($module) {
                SemiAutoService::MODULE_LISTADO => 'lps.listado_actividades.ver',
                SemiAutoService::MODULE_CONTRATOS => 'lps.contratos.ver',
                SemiAutoService::MODULE_PDC => 'lps.pdc.ver',
            };
        }

        $this->authorizePermission($permission, 'No autorizado para operar automatización.');
    }

    private function authorizeAdminLearning(string $module): void
    {
        $this->authorizeModule($module, 'feedback');
        $role = $_SESSION['permiso_canonico'] ?? ($_SESSION['permiso'] ?? '');
        if (class_exists(RoleManager::class)) {
            $role = RoleManager::cleanCargo($role);
        }
        if (strtoupper((string) $role) !== 'A') {
            $this->jsonError('Solo un administrador puede gestionar aprendizajes.', 403);
            exit;
        }
    }

    private function jsonPayload(): array
    {
        $raw = file_get_contents('php://input');
        if ($raw === false || trim($raw) === '') {
            return $_POST;
        }
        $data = json_decode($raw, true);

        return is_array($data) ? $data : [];
    }

    private function releaseSessionForPolling(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
    }

    private function requirePdcCsrf(): void
    {
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['_csrf_token'] ?? '';
        if (CsrfTokenManager::validate($token, 'pdc_save')) {
            return;
        }

        $this->jsonError('Token CSRF inválido o ausente.', 403);
        exit;
    }

    private function json(array $payload, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    }

    private function jsonError(string $message, int $status = 400, ?Throwable $e = null): void
    {
        if ($e !== null) {
            error_log('[SemiAutoController] ' . $message . ' ' . $e->getMessage());
        }
        $this->json(['respuesta' => 'ERROR', 'mensaje' => $message], $status);
    }
}
