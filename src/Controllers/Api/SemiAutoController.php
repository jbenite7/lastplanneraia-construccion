<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\SemiAutoService;
use App\Support\ModuleRequestContext;
use Throwable;

class SemiAutoController extends BaseController
{
    private SemiAutoService $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new SemiAutoService($this->db);
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

    public function previewPdc(): void
    {
        $this->preview(SemiAutoService::MODULE_PDC);
    }

    public function statusPdc(): void
    {
        $this->status(SemiAutoService::MODULE_PDC);
    }

    public function applyPdc(): void
    {
        $this->apply(SemiAutoService::MODULE_PDC);
    }

    public function undoPdc(): void
    {
        $this->undo(SemiAutoService::MODULE_PDC);
    }

    public function feedbackPdc(): void
    {
        $this->feedback(SemiAutoService::MODULE_PDC);
    }

    public function metricsPdc(): void
    {
        $this->metrics(SemiAutoService::MODULE_PDC);
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
