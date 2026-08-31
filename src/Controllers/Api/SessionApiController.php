<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\SessionMiddleware;
use App\Security\CsrfTokenManager;
use App\Security\DataScope\ProjectScope;
use App\Security\RbacManager;
use App\Security\RbacService;
use App\Services\Shell\ShellNavigationService;
use App\View\Components\BiAccessComponent;

/**
 * Bootstrap canónico del shell React (spec T01 §8).
 *
 * Esta ruta queda fuera del guard global porque consultar el estado antes de
 * entrar es el flujo normal. Aun así usa el validador central de sesión: una
 * cookie vencida, inactiva, huérfana o de cambio de clave pendiente nunca se
 * presenta como autenticada.
 *
 * `state` solo acepta `anonymous`, `password_change_required` o
 * `authenticated` (spec §8.2). Los cinco motivos que hoy produce
 * `SessionMiddleware` (`missing_session`, `timeout`, `inactive`,
 * `stale_session`, `session_unverified`) viajan todos bajo `state=anonymous`
 * en `reason`; el cliente decide si eso es "nunca hubo sesión" o "hubo una y
 * expiró" — ver `frontend/src/lib/api/esquemas/arranque.ts`.
 */
class SessionApiController
{
    private const CSRF_FORM_KEY = 'shell_api';

    public function show(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

        $reason = SessionMiddleware::requestFailureReason();
        $estado = self::estadoParaRazon($reason);

        if (!$estado['authenticated']) {
            $this->respondNotAuthenticated($estado['state'], $estado['reason']);

            return;
        }

        $usuario = $_SESSION['usuario'];
        $scope = \Database::getInstance()->dataScope()->current();
        $rol = $scope instanceof ProjectScope
            ? $scope->role()
            : (new RbacService())->normalizeRole((string) ($_SESSION['permiso'] ?? ''));

        $project = $this->activeProject();
        $bi = $this->biNavigation($rol);

        echo json_encode([
            'state' => 'authenticated',
            'authenticated' => true,
            'reason' => null,
            'user' => [
                'username' => $usuario,
                'displayName' => (string) ($_SESSION['nombreUsuario'] ?? $usuario),
                'role' => $rol,
            ],
            'project' => $project,
            'capabilities' => RbacManager::getCapabilities($rol),
            'navigation' => [
                'bi' => $bi,
                // El manifiesto es server-authoritative (spec T01 §10.2): un ítem no
                // autorizado no viaja. `$project` ya viene ligado al `ProjectScope`
                // capturado, así que `area` es la del mismo proyecto que autorizó `$rol`.
                // Sin `ProjectScope` activo (autenticado pero sin proyecto elegido) no hay
                // membresía que autorice nada: lista vacía real, no un manifiesto fabricado.
                'groups' => $project !== null
                    ? ShellNavigationService::build($rol, $project['area'], $bi)
                    : [],
            ],
            'week' => $this->activeWeek(),
            'csrfToken' => CsrfTokenManager::generate(self::CSRF_FORM_KEY),
        ]);
    }

    /**
     * Mapeo puro razón → estado de arranque. Sin dependencias de sesión ni de
     * base de datos a propósito: es lo que hace testeable en `puro` el
     * contrato de `password_change_required`, `inactive` y
     * `session_unverified` ("fallo recuperable de servidor") sin forzar una
     * sesión artificial ni una caída real de la base de datos.
     *
     * @return array{state:string, authenticated:bool, reason:string|null}
     */
    public static function estadoParaRazon(?string $reason): array
    {
        if ($reason === null) {
            return ['state' => 'authenticated', 'authenticated' => true, 'reason' => null];
        }

        if ($reason === SessionMiddleware::REASON_PASSWORD_CHANGE_REQUIRED) {
            return ['state' => 'password_change_required', 'authenticated' => false, 'reason' => null];
        }

        return ['state' => 'anonymous', 'authenticated' => false, 'reason' => $reason];
    }

    private function respondNotAuthenticated(string $state, ?string $reason): void
    {
        echo json_encode([
            'state' => $state,
            'authenticated' => false,
            'reason' => $reason,
            'user' => null,
            'project' => null,
            'capabilities' => new \stdClass(),
            'navigation' => [
                'bi' => null,
                // Sin sesión válida no hay rol que autorice nada: lista vacía real, no un
                // manifiesto fabricado para disimular la ausencia (spec T01 §8.2).
                'groups' => [],
            ],
            'week' => null,
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

    /** @return array{id:int,name:string,area:string}|null */
    private function activeProject(): ?array
    {
        $scope = \Database::getInstance()->dataScope()->current();
        if (!$scope instanceof ProjectScope || !isset($_SESSION['proyecto'])) {
            return null;
        }

        return [
            'id' => $scope->projectId(),
            'name' => (string) $_SESSION['proyecto'],
            // Mismo default que `views/partials/shell_sidebar.php` (`$shellArea`): sin dato
            // en sesión se asume Construcción, la fase más larga y la que no oculta nada.
            'area' => (string) ($_SESSION['area'] ?? 'Construccion'),
        ];
    }

    /**
     * `null` sin proyecto activo o sin semana elegida (`semana` en sesión es
     * `0`/ausente): el selector semanal completo — opciones y acciones de
     * crear/eliminar — lo compone `WeekAdministrationService` en la Tarea 5;
     * aquí solo viaja la semana actualmente activa, consistente con lo que
     * `BaseController`/`ContextController` ya guardan en sesión hoy.
     *
     * @return array{current:int}|null
     */
    private function activeWeek(): ?array
    {
        $scope = \Database::getInstance()->dataScope()->current();
        if (!$scope instanceof ProjectScope) {
            return null;
        }

        $semana = (int) ($_SESSION['semana'] ?? 0);
        if ($semana <= 0) {
            return null;
        }

        return ['current' => $semana];
    }
}
