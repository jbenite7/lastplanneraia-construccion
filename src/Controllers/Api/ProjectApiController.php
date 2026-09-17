<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Security\CsrfTokenManager;
use App\Services\ProjectAccessService;
use App\View\Components\BiAccessComponent;
use Closure;
use JsonException;
use Throwable;

/**
 * Contrato JSON de `/api/proyectos` y `/api/proyectos/seleccionar`, consumido por el shell React
 * a través de `frontend/src/lib/api/proyectos.ts`.
 *
 * Toda la autorización sigue en `ProjectAccessService` (membresía, proyecto activo, `Acceso`,
 * RBAC y scope) y el destino lo decide `ProjectLandingService`: este adaptador solo traduce a
 * HTTP. Los colaboradores se inyectan para que `tests/test_api_projects_pure_contract.php` pueda
 * correr sin base de datos.
 *
 * Dos decisiones que se leen mal si no se explican:
 *
 * - **El rechazo de selección NO es un error de transporte.** Responde 200 con
 *   `{success:false, message, route:null}` exacto, sin `code` ni bloque `error`, porque
 *   `EsquemaResultadoSeleccionProyecto` es `.strict()` y cualquier clave extra lo invalidaría.
 *   El mensaje es siempre el mismo literal: la API no distingue ausencia de membresía, proyecto
 *   inactivo o cierre por perfil, así que no se puede usar como oráculo de acceso.
 * - **El resto de los errores (401/403/422/500) sí llevan el bloque `error`** (`codigo`,
 *   `mensaje`, `campos?`), la forma que lee `frontend/src/lib/api/cliente.ts`, con el mismo
 *   patrón de `PasswordRecoveryApiController::respondError()`: `campos` solo aparece si hay
 *   errores de campo, porque un arreglo vacío se serializa como `[]` y rompería el esquema.
 */
class ProjectApiController
{
    private const CSRF_FORM_KEY = 'shell_api';
    private const REJECTION_MESSAGE = 'No se pudo acceder al proyecto seleccionado.';

    private ProjectAccessService $projectAccess;
    private Closure $bodyReader;
    private Closure $biResolver;

    public function __construct(
        ?ProjectAccessService $projectAccess = null,
        ?callable $bodyReader = null,
        ?callable $biResolver = null,
    ) {
        $this->projectAccess = $projectAccess ?? new ProjectAccessService();
        $this->bodyReader = $bodyReader === null
            ? static fn (): string => (string) file_get_contents('php://input')
            : Closure::fromCallable($bodyReader);
        $this->biResolver = $biResolver === null
            ? static function (): array {
                $visible = BiAccessComponent::canAccessAny();

                return ['visible' => $visible, 'href' => $visible ? BiAccessComponent::globalUrl() : null];
            }
            : Closure::fromCallable($biResolver);
    }

    public function index(): void
    {
        $this->json();
        $usuario = $this->sessionUser();
        if ($usuario === null) {
            $this->respondSessionInvalid();

            return;
        }

        $projects = [];
        foreach ($this->projectAccess->listForUser($usuario) as $project) {
            $projects[] = [
                'id' => (int) ($project['ID'] ?? 0),
                'name' => (string) ($project['Proyecto_Proceso'] ?? ''),
                'area' => (string) ($project['Area'] ?? ''),
                'active' => (int) ($project['Activo'] ?? 0) === 1,
                'role' => (string) ($project['permiso'] ?? ''),
                'roleLabel' => (string) ($project['rol_nombre'] ?? ''),
            ];
        }

        $bi = $this->resolveBiNavigation();
        if ($bi === null) {
            // Una navegación incoherente se corta aquí: servirla como lista 200 solo movería
            // el fallo al parseo de Zod, en la pantalla y sin nombre propio.
            $this->respondError(500, 'invalid_navigation', 'No se pudo preparar la navegación del shell.');

            return;
        }

        $this->respond(200, ['projects' => $projects, 'navigation' => ['bi' => $bi]]);
    }

    public function select(): void
    {
        $this->json();
        $usuario = $this->sessionUser();
        if ($usuario === null) {
            $this->respondSessionInvalid();

            return;
        }

        $csrf = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        if (!CsrfTokenManager::validate(is_string($csrf) ? $csrf : null, self::CSRF_FORM_KEY)) {
            $this->respondError(403, 'csrf_invalid', 'No fue posible validar la solicitud. Intenta nuevamente.');

            return;
        }

        $payload = $this->decodeStrictPayload(($this->bodyReader)());
        $nameValue = is_array($payload) ? ($payload['name'] ?? null) : null;
        $name = is_string($nameValue) ? trim($nameValue) : '';
        if ($payload === null || array_keys($payload) !== ['name'] || !is_string($nameValue) || $name === '') {
            $this->respondError(
                422,
                'validation_error',
                'Selecciona un proyecto de la lista.',
                ['name' => 'Selecciona un proyecto de la lista.'],
            );

            return;
        }

        $result = $this->projectAccess->select($usuario, $name);

        if ($result['success'] !== true) {
            $this->respond(200, ['success' => false, 'message' => self::REJECTION_MESSAGE, 'route' => null]);

            return;
        }

        $route = $result['route'] ?? null;
        if (!is_string($route) || !$this->isSafeInternalPath($route)) {
            $this->respondError(500, 'invalid_landing', 'No se pudo abrir el proyecto seleccionado.');

            return;
        }

        $this->respond(200, ['success' => true, 'message' => null, 'route' => $route]);
    }

    private function sessionUser(): ?string
    {
        $usuario = $_SESSION['usuario'] ?? null;

        return is_string($usuario) && $usuario !== '' ? $usuario : null;
    }

    /**
     * Normaliza la salida del resolver a una de las dos ramas que acepta el cliente:
     * `{visible:true, href:<path interno>}` o `{visible:false, href:null}`. Devuelve `null`
     * cuando el resolver es incoherente o falla — el llamador responde 500.
     *
     * @return array{visible:bool,href:string|null}|null
     */
    private function resolveBiNavigation(): ?array
    {
        try {
            $bi = ($this->biResolver)();
        } catch (Throwable $error) {
            error_log('Bi navigation unavailable: ' . $error::class);

            return null;
        }

        if (!is_array($bi) || ($bi['visible'] ?? null) !== true) {
            return ['visible' => false, 'href' => null];
        }

        $href = $bi['href'] ?? null;
        if (!is_string($href) || !$this->isSafeInternalPath($href)) {
            return null;
        }

        return ['visible' => true, 'href' => $href];
    }

    /** Mismo path interno que acepta `EsquemaRutaInterna` del cliente. */
    private function isSafeInternalPath(string $path): bool
    {
        return preg_match('~^/(?!/)[^\x00-\x1F\x7F\\\\]+$~D', $path) === 1;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function decodeStrictPayload(string $raw): ?array
    {
        try {
            $payload = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        return is_array($payload) && !array_is_list($payload) ? $payload : null;
    }

    private function respondSessionInvalid(): void
    {
        $this->respondError(401, 'session_invalid', 'Tu sesión no está activa. Vuelve a iniciar sesión.');
    }

    /**
     * @param array<string, string> $fieldErrors
     */
    private function respondError(int $status, string $code, string $message, array $fieldErrors = []): void
    {
        $error = ['codigo' => $code, 'mensaje' => $message];
        if ($fieldErrors !== []) {
            $error['campos'] = $fieldErrors;
        }

        $this->respond($status, ['success' => false, 'code' => $code, 'message' => $message, 'error' => $error]);
    }

    private function json(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    }

    /** @param array<string, mixed> $body */
    private function respond(int $status, array $body): void
    {
        http_response_code($status);
        echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
