<?php

declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Core\MaintenanceMode;
use App\Core\SpaHostRenderer;
use App\Security\CsrfTokenManager;
use App\Services\Auth\AuthenticationService;
use App\Services\Auth\ForcedPasswordChangeService;
use App\Services\Auth\UserPasswordService;
use Database;

/**
 * Entrada oculta de mantenimiento, servida como shell React (Tarea 12, S01) en vez del
 * `views/auth/login.view.php` legado que usaba `LoginController`. Replica exactamente las
 * mismas reglas de negocio que `LoginController::maintenanceLogin()` — credenciales, cuenta
 * activa, rol A global en un proyecto de Construcción activo — sin reintroducirlas ni
 * relajarlas: solo cambia cómo se sirve la respuesta.
 *
 * El rechazo es SIEMPRE genérico (401, mismo mensaje): csrf inválido, campos vacíos,
 * credenciales incorrectas, cuenta inactiva o rol insuficiente producen la misma respuesta.
 * Esa garantía la fijó la Tarea 5 para `/api/auth/login` y no se deshace aquí.
 */
class MaintenanceLoginController
{
    /** Clave propia del formulario oculto — nunca la de `AuthApiController::CSRF_FORM_KEY`. */
    private const CSRF_FORM_KEY = 'shell_maintenance_login';

    /**
     * Debe coincidir con `AuthApiController::CSRF_FORM_KEY`: el token que esta pantalla inyecta
     * para el estado `password_change_required` es el que `CambioClaveObligatorio` manda en la
     * cabecera `X-CSRF-Token` de `/api/auth/password/change|cancel` — un token distinto ahí
     * fallaría esas llamadas con 403.
     */
    private const CSRF_API_FORM_KEY = 'shell_api';

    private $db;
    private AuthenticationService $authentication;
    private ForcedPasswordChangeService $forcedPasswordChange;

    public function __construct(
        ?Database $db = null,
        ?AuthenticationService $authentication = null,
        ?ForcedPasswordChangeService $forcedPasswordChange = null,
    ) {
        $this->db = $db ?? Database::getInstance();
        $this->authentication = $authentication ?? new AuthenticationService($this->db);
        $this->forcedPasswordChange = $forcedPasswordChange ?? new ForcedPasswordChangeService(
            new UserPasswordService($this->db),
            $this->authentication,
        );
    }

    /**
     * GET del host oculto. Responde 200 siempre que llega aquí: si ya hay una sesión completa
     * redirige a `/proyectos` (303) en vez de mostrar el login de nuevo.
     */
    public function show(): void
    {
        if (isset($_SESSION['usuario']) && empty($_SESSION['must_change_password'])) {
            $this->redirect('/proyectos', 303);

            return;
        }

        $accion = $this->currentUri();

        if ($this->forcedPasswordChange->isPending()) {
            SpaHostRenderer::render([
                'mode' => 'maintenance',
                'action' => $accion,
                'error' => false,
                'state' => 'password_change_required',
                'csrfToken' => CsrfTokenManager::generate(self::CSRF_API_FORM_KEY),
            ]);

            return;
        }

        SpaHostRenderer::render([
            'mode' => 'maintenance',
            'action' => $accion,
            'error' => false,
            'state' => 'anonymous',
            'csrfToken' => CsrfTokenManager::generate(self::CSRF_FORM_KEY),
        ]);
    }

    /**
     * POST del host oculto. `$_POST['usuario']`/`$_POST['password']` son los nombres legacy que
     * manda el formulario nativo de `PantallaLogin` en modo mantenimiento — nunca JSON, nunca
     * `/api/auth/login`.
     */
    public function submit(): void
    {
        $accion = $this->currentUri();

        $token = $_POST['csrf_token'] ?? null;
        if (!CsrfTokenManager::validate(is_string($token) ? $token : null, self::CSRF_FORM_KEY)) {
            $this->rejectGeneric($accion);

            return;
        }

        $usuario = strtolower(trim((string) ($_POST['usuario'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');

        if ($usuario === '' || $password === '') {
            $this->rejectGeneric($accion);

            return;
        }

        $data = $this->authentication->verifyCredentials($usuario, $password);

        if (!$data) {
            if (method_exists($this->db, 'logActivity')) {
                $this->db->logActivity('Login', 'LOGIN_FALLIDO', "Credenciales incorrectas: {$usuario}");
            }
            $this->rejectGeneric($accion);

            return;
        }

        if (isset($data['activo']) && (int) $data['activo'] !== 1) {
            if (method_exists($this->db, 'logActivity')) {
                $this->db->logActivity('Login', 'LOGIN_BLOQUEADO_INACTIVO', "Intento de acceso con cuenta inactiva: {$usuario}");
            }
            $this->rejectGeneric($accion);

            return;
        }

        if (!$this->userHasGlobalAdminRole($usuario)) {
            if (method_exists($this->db, 'logActivity')) {
                $this->db->logActivity('Login', 'MAINTENANCE_BLOQUEADO', "Usuario {$usuario} sin rol A intentó acceso por ruta oculta");
            }
            $this->rejectGeneric($accion);

            return;
        }

        // El bypass debe existir ANTES del redirect pendiente: `/api/auth/password/change` y
        // `/api/auth/password/cancel` pasan por el mismo gate de `MaintenanceMode` que todo lo
        // demás, y sin este flag ese gate les devolvería el 503 público en vez de dejarlos pasar.
        $_SESSION['maintenance_bypass'] = true;

        if (isset($data['force_password_change']) && (int) $data['force_password_change'] === 1) {
            $this->authentication->beginPasswordChange($usuario, $data);

            if (method_exists($this->db, 'logActivity')) {
                $this->db->logActivity('Login', 'LOGIN_PENDIENTE_CLAVE', "Usuario {$usuario} requiere cambio de contraseña.");
            }

            $this->redirect($accion, 303);

            return;
        }

        $this->authentication->beginAuthenticatedSession($usuario, $data);

        if (method_exists($this->db, 'logActivity')) {
            $this->db->logActivity('Login', 'MAINTENANCE_LOGIN', "Admin {$usuario} accedió por ruta oculta durante mantenimiento");
        }

        $this->redirect('/proyectos', 303);
    }

    /**
     * Réplica exacta de `LoginController::userHasGlobalAdminRole()` — mismo join, misma
     * condición. No se reutiliza esa clase porque es privada y esta ruta no comparte más
     * estado con ella; divergir la consulta sería el riesgo real, así que se copia literal.
     */
    private function userHasGlobalAdminRole(string $usuario): bool
    {
        $stmt = $this->db->query(
            'SELECT COUNT(*) FROM project_members pm
             INNER JOIN general_usuarios u ON u.id = pm.user_id
             INNER JOIN general_proyectos_procesos p ON p.ID = pm.project_id
             WHERE u.usuario = ?
               AND pm.role = ?
               AND p.Area = ?
               AND p.Activo = 1',
            [$usuario, 'A', 'Construccion'],
        );

        return (int) $stmt->fetchColumn() > 0;
    }

    private function currentUri(): string
    {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? MaintenanceMode::SECRET_PATH, PHP_URL_PATH);

        return is_string($uri) && $uri !== '' ? $uri : MaintenanceMode::SECRET_PATH;
    }

    /**
     * Rechazo genérico: mismo status (401) y mismo cuerpo (`error: true`) sin importar la
     * causa — csrf, campos vacíos, credenciales, cuenta inactiva o rol insuficiente. Nunca
     * delata cuál de las cinco fue.
     */
    private function rejectGeneric(string $accion): void
    {
        SpaHostRenderer::render([
            'mode' => 'maintenance',
            'action' => $accion,
            'error' => true,
            'state' => 'anonymous',
            'csrfToken' => CsrfTokenManager::generate(self::CSRF_FORM_KEY),
        ], 401, 'POST');
    }

    /**
     * `protected`, no `private`: es el punto de extensión que hace observable el redirect en
     * pruebas sin depender de `headers_list()` — vacío bajo el SAPI CLI, así que no hay otra
     * forma de comprobar a dónde redirige `submit()` sin invocar un servidor HTTP real. Mismo
     * patrón que `AuthApiController::requestBody()`.
     */
    protected function redirect(string $location, int $status): void
    {
        http_response_code($status);
        header("Location: {$location}");
    }
}
