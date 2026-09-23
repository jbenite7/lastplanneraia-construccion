<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Security\DataScope\ProjectScope;
use App\Services\ProgramaGeneralContextService;
use Throwable;

class ProgramaGeneralContextApiController extends BaseController
{
    private ProgramaGeneralContextService $contextService;

    public function __construct(?ProgramaGeneralContextService $contextService = null)
    {
        parent::__construct();
        $this->contextService = $contextService ?? new ProgramaGeneralContextService($this->db);
    }

    public function show(): void
    {
        $this->requireAuth();
        $this->authorizePermission('lps.programa_general.ver');

        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

        $scope = \Database::getInstance()->dataScope()->current();
        if (!$scope instanceof ProjectScope) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'error' => 'No hay un ProjectScope activo.',
            ]);
            return;
        }

        try {
            $context = $this->contextService->build($scope, $_SESSION);
            echo json_encode([
                'success' => true,
                'data' => $context,
            ]);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Error al obtener el contexto de Programa General: ' . $e->getMessage(),
            ]);
        }
    }
}
