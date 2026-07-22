<?php

namespace App\Controllers\Api;

use App\Security\CsrfTokenManager;
use App\Security\RbacService;

/**
 * API JSON del Plan de Compras v2 (isla React).
 * Envelope propio del módulo: {"ok":true,"data":...} | {"ok":false,"error":{code,message}}.
 * La sesión ya está garantizada por SessionMiddleware global (public/index.php).
 */
class PlanComprasApiController
{
    private $db;

    public function __construct()
    {
        $this->db = \Database::getInstance();
    }

    /** GET /plan-compras/api/contexto */
    public function contexto(): void
    {
        if (!$this->can('lps.pdc.ver')) {
            $this->fail('FORBIDDEN', 'No autorizado para consultar el plan de compras.', 403);
            return;
        }

        $projectId = (int) ($_SESSION['project_id'] ?? 0);
        if ($projectId <= 0) {
            $this->fail('NO_PROJECT', 'No hay proyecto activo. Selecciona un proyecto.', 409);
            return;
        }

        $this->ok([
            'projectId' => $projectId,
            'proyectoNombre' => (string) ($_SESSION['proyecto'] ?? ''),
            'usuario' => (string) ($_SESSION['nombreUsuario'] ?? ($_SESSION['usuario'] ?? '')),
            'rol' => (string) ($_SESSION['permiso_canonico'] ?? ($_SESSION['permiso'] ?? '')),
            'csrfToken' => CsrfTokenManager::generate('plan_compras_v2'),
        ]);
    }

    private function can(string $permissionKey): bool
    {
        return (new RbacService($this->db))->can($permissionKey);
    }

    private function ok(array $data): void
    {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode(['ok' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
    }

    private function fail(string $code, string $message, int $status): void
    {
        if (!headers_sent()) {
            http_response_code($status);
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode(
            ['ok' => false, 'error' => ['code' => $code, 'message' => $message]],
            JSON_UNESCAPED_UNICODE
        );
    }
}
