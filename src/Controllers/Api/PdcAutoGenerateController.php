<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\SemiAutoService;
use App\Support\ModuleRequestContext;
use Throwable;

class PdcAutoGenerateController extends BaseController
{
    private SemiAutoService $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new SemiAutoService($this->db);
    }

    public function applyFromContratos(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $this->authorizePermission('lps.pdc.auto_generar', 'No autorizado para generar el Plan de Compras.');
            $context = ModuleRequestContext::resolve(['allow_zero_week' => false]);
            $preview = $this->service->preview(SemiAutoService::MODULE_PDC, $context);
            $readyIds = [];
            $counts = [
                'total' => 0,
                'listas' => 0,
                'por_revisar' => 0,
                'conflictos' => 0,
            ];

            foreach (($preview['suggestions'] ?? []) as $suggestion) {
                $counts['total']++;
                $status = (string) ($suggestion['analysis']['quality_gate']['status'] ?? '');
                if ($status === 'ready') {
                    $counts['listas']++;
                    if (!empty($suggestion['preselected'])) {
                        $readyIds[] = (string) $suggestion['suggestion_id'];
                    }
                    continue;
                }
                if ($status === 'review') {
                    $counts['por_revisar']++;
                    continue;
                }
                if ($status === 'conflict') {
                    $counts['conflictos']++;
                }
            }

            $applied = ['aplicadas' => 0, 'errores' => 0];
            if (!empty($readyIds)) {
                $applied = $this->service->apply(
                    SemiAutoService::MODULE_PDC,
                    $context,
                    (string) $preview['run_id'],
                    $readyIds,
                );
            }

            $aplicadas = (int) ($applied['aplicadas'] ?? 0);
            echo json_encode([
                'respuesta' => 'BIEN',
                'mensaje' => $aplicadas > 0
                    ? "Se actualizaron {$aplicadas} paquetes listos del Plan de Compras."
                    : 'No había paquetes listos para aplicar automáticamente. Revisa las propuestas pendientes.',
                'run_id' => $preview['run_id'] ?? null,
                'total' => $counts['total'],
                'listas' => $counts['listas'],
                'aplicadas' => $aplicadas,
                'por_revisar' => $counts['por_revisar'],
                'conflictos' => $counts['conflictos'],
                'errores' => (int) ($applied['errores'] ?? 0),
            ], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            error_log('Error en PdcAutoGenerateController@applyFromContratos: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'respuesta' => 'ERROR',
                'mensaje' => 'No se pudo actualizar el Plan de Compras y Contrataciones desde Paquetes de contratacion.',
            ], JSON_UNESCAPED_UNICODE);
        }
    }
}
