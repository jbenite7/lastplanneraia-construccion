<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Security\CsrfTokenManager;
use App\Services\ProjectAccessService;

class ProjectApiController
{
    private const CSRF_FORM_KEY = 'shell_api';

    private ProjectAccessService $projectAccess;

    public function __construct()
    {
        $this->projectAccess = new ProjectAccessService();
    }

    public function index(): void
    {
        $this->json();
        $usuario = $_SESSION['usuario'] ?? null;

        if (!is_string($usuario) || $usuario === '') {
            $this->respond(['success' => false, 'message' => 'Sesión no válida.'], 401);

            return;
        }

        $projects = [];
        foreach ($this->projectAccess->listForUser($usuario) as $project) {
            $projects[] = [
                'id' => (int) ($project['ID'] ?? 0),
                'name' => (string) ($project['Proyecto_Proceso'] ?? ''),
                'role' => (string) ($project['permiso'] ?? ''),
            ];
        }

        $this->respond(['projects' => $projects]);
    }

    public function select(): void
    {
        $this->json();
        $usuario = $_SESSION['usuario'] ?? null;

        if (!is_string($usuario) || $usuario === '') {
            $this->respond(['success' => false, 'message' => 'Sesión no válida.'], 401);

            return;
        }

        $csrf = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!is_string($csrf) || !CsrfTokenManager::validate($csrf, self::CSRF_FORM_KEY)) {
            $this->respond(['success' => false, 'message' => 'Token CSRF inválido o ausente.'], 403);

            return;
        }

        $body = json_decode((string) file_get_contents('php://input'), true);
        $name = is_array($body) ? trim((string) ($body['name'] ?? '')) : '';
        $result = $this->projectAccess->select($usuario, $name);

        if (!$result['success']) {
            // La API no distingue ausencia de membresía, proyecto inactivo o cierre
            // por perfil; así no transforma el selector en un oráculo de acceso.
            $this->respond(['success' => false, 'message' => 'No se pudo acceder al proyecto seleccionado.']);

            return;
        }

        $this->respond(['success' => true, 'message' => null]);
    }

    private function json(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    }

    /** @param array<string, mixed> $body */
    private function respond(array $body, int $status = 200): void
    {
        http_response_code($status);
        echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
