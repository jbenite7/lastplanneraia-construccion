<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\SessionMiddleware;
use App\Security\CsrfTokenManager;
use App\Security\RbacManager;
use App\Security\RbacService;
use App\View\Components\BiAccessComponent;

/**
 * Estado mínimo de arranque del shell React.
 *
 * Esta ruta queda fuera del guard global porque consultar el estado antes de
 * entrar es el flujo normal. Aun así usa el validador central de sesión: una
 * cookie vencida, inactiva o huérfana nunca se presenta como autenticada.
 */
class SessionApiController
{
    private const CSRF_FORM_KEY = 'shell_api';

    public function show(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

        if (SessionMiddleware::validationFailureReason() !== null) {
            $this->respondAnonymous();

            return;
        }

        $usuario = $_SESSION['usuario'];
        $rol = (new RbacService())->normalizeRole((string) ($_SESSION['permiso'] ?? ''));

        echo json_encode([
            'authenticated' => true,
            'user' => [
                'username' => $usuario,
                'displayName' => (string) ($_SESSION['nombreUsuario'] ?? $usuario),
                'role' => $rol,
            ],
            'project' => $this->activeProject(),
            'capabilities' => RbacManager::getCapabilities($rol),
            'navigation' => [
                'bi' => $this->biNavigation($rol),
            ],
            'csrfToken' => CsrfTokenManager::generate(self::CSRF_FORM_KEY),
        ]);
    }

    private function respondAnonymous(): void
    {
        echo json_encode([
            'authenticated' => false,
            'user' => null,
            'project' => null,
            'capabilities' => new \stdClass(),
            'navigation' => [
                'bi' => null,
            ],
            'csrfToken' => CsrfTokenManager::generate(self::CSRF_FORM_KEY),
        ]);
    }

    /** @return array{visible:bool,href:string|null} */
    private function biNavigation(string $rol): array
    {
        // La isla no reproduce gates de BI: el servidor resuelve tanto acceso
        // como el destino contextual (módulo inicial, proyecto y semana).
        if (!BiAccessComponent::canAccess()) {
            return ['visible' => false, 'href' => null];
        }

        return [
            'visible' => true,
            'href' => BiAccessComponent::url(BiAccessComponent::defaultModuleForRole($rol)),
        ];
    }

    /** @return array{id:int,name:string}|null */
    private function activeProject(): ?array
    {
        $projectId = (int) ($_SESSION['project_id'] ?? 0);
        if ($projectId <= 0 || !isset($_SESSION['proyecto'])) {
            return null;
        }

        return [
            'id' => $projectId,
            'name' => (string) $_SESSION['proyecto'],
        ];
    }
}
