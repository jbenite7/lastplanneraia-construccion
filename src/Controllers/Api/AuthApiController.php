<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Security\CsrfTokenManager;
use App\Services\Auth\AuthenticationService;
use App\Services\Auth\ForcedPasswordChangeService;
use App\Services\Auth\UserPasswordService;
use Database;

/**
 * Entrada y salida JSON para el shell React.
 *
 * Reutiliza el mismo AuthenticationService que las rutas de formulario para
 * que la verificación de hash y la transición de sesión no diverjan, y
 * ForcedPasswordChangeService (Tarea 4, S01) para el cambio obligatorio de
 * contraseña, sin reimplementar esa lógica aquí.
 *
 * Los tres parámetros del constructor son opcionales para permitir dobles en
 * pruebas unitarias sin tocar `Database::getInstance()`: cuando los tres
 * llegan inyectados, el constructor nunca abre una conexión real.
 */
class AuthApiController
{
    private const CSRF_FORM_KEY = 'shell_api';

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

    public function login(): void
    {
        $this->sendJsonHeaders();

        if (!$this->hasValidCsrfToken()) {
            $this->respondError(403, 'csrf_invalid', 'Solicitud no permitida.');

            return;
        }

        $payload = json_decode($this->requestBody(), true);
        $usuario = is_array($payload) ? strtolower(trim((string) ($payload['username'] ?? ''))) : '';
        $password = is_array($payload) ? (string) ($payload['password'] ?? '') : '';

        $fieldErrors = [];
        if ($usuario === '') {
            $fieldErrors['username'] = 'Escribe tu usuario.';
        }
        if ($password === '') {
            $fieldErrors['password'] = 'Escribe tu contraseña.';
        }
        if ($fieldErrors !== []) {
            $this->respondError(422, 'validation_error', 'Escribe tu usuario y tu contraseña.', $fieldErrors);

            return;
        }

        $user = $this->authentication->verifyCredentials($usuario, $password);

        // La inactividad se responde igual que una credencial incorrecta para
        // que este endpoint no revele qué cuentas existen o están habilitadas.
        if (!$user || (isset($user['activo']) && (int) $user['activo'] !== 1)) {
            if ($user && method_exists($this->db, 'logActivity')) {
                $this->db->logActivity('Login', 'LOGIN_BLOQUEADO_INACTIVO', "Intento de acceso con cuenta inactiva: {$usuario}");
            } elseif (method_exists($this->db, 'logActivity')) {
                $this->db->logActivity('Login', 'LOGIN_FALLIDO', "Credenciales incorrectas: {$usuario}");
            }

            $this->respondError(401, 'invalid_credentials', 'Usuario o contraseña incorrectos.');

            return;
        }

        if (isset($user['force_password_change']) && $user['force_password_change'] == 1) {
            $this->authentication->beginPasswordChange($usuario, $user);
            if (method_exists($this->db, 'logActivity')) {
                $this->db->logActivity('Login', 'LOGIN_PENDIENTE_CLAVE', "Usuario {$usuario} requiere cambio de contraseña.");
            }

            $this->respond(200, ['success' => true, 'next' => 'password_change', 'message' => null]);

            return;
        }

        $this->authentication->beginAuthenticatedSession($usuario, $user);
        if (method_exists($this->db, 'logActivity')) {
            $this->db->logActivity('Login', 'LOGIN_FASE_1', "Usuario autenticado: {$usuario}");
        }

        $this->respond(200, ['success' => true, 'next' => 'projects', 'message' => null]);
    }

    /**
     * Completa un cambio de contraseña obligatorio ya pendiente.
     *
     * Nunca acepta `username`, `project_id`, `db`, prefijo ni rol en el cuerpo: la identidad
     * viene de `usuario_temp` en sesión, resuelta por ForcedPasswordChangeService, exactamente
     * como en el flujo de formulario legacy.
     */
    public function changePassword(): void
    {
        $this->sendJsonHeaders();

        if (!$this->hasValidCsrfToken()) {
            $this->respondError(403, 'csrf_invalid', 'Solicitud no permitida.');

            return;
        }

        if (!$this->forcedPasswordChange->isPending()) {
            $this->respondError(401, 'password_change_not_pending', 'No hay un cambio de contraseña pendiente.');

            return;
        }

        $payload = json_decode($this->requestBody(), true);
        $password = is_array($payload) ? (string) ($payload['password'] ?? '') : '';
        $confirmation = is_array($payload) ? (string) ($payload['confirmation'] ?? '') : '';

        try {
            $result = $this->forcedPasswordChange->change($password, $confirmation);
        } catch (\Throwable $e) {
            // Deliberadamente sin username, contraseña, confirmación, cookie ni CSRF: el mensaje
            // de la excepción es lo único que viaja al log.
            error_log('AuthApiController::changePassword ' . $e->getMessage());
            $this->respondError(500, 'internal_error', 'No se pudo actualizar la contraseña.');

            return;
        }

        if (!$result['success']) {
            $fieldErrors = $result['fieldErrors'];
            if ($fieldErrors !== []) {
                $this->respondError(
                    422,
                    'validation_error',
                    (string) ($result['message'] ?? 'Revisa los campos marcados.'),
                    $this->flattenFieldErrors($fieldErrors),
                );

                return;
            }

            $this->respondError(500, 'internal_error', 'No se pudo actualizar la contraseña.');

            return;
        }

        $this->respond(200, ['success' => true, 'next' => 'projects']);
    }

    /**
     * Abandona el cambio de contraseña obligatorio. Idempotente: responde éxito tanto si había
     * un cambio pendiente como si no, porque el cliente no tiene forma de saberlo de antemano y
     * no necesita distinguirlo — es no-op sobre una sesión completa (ver
     * ForcedPasswordChangeService::cancel()).
     */
    public function cancelPasswordChange(): void
    {
        $this->sendJsonHeaders();

        if (!$this->hasValidCsrfToken()) {
            $this->respondError(403, 'csrf_invalid', 'Solicitud no permitida.');

            return;
        }

        $this->forcedPasswordChange->cancel();

        $this->respond(200, ['success' => true, 'next' => 'login']);
    }

    public function logout(): void
    {
        $this->sendJsonHeaders();

        if (!$this->hasValidCsrfToken()) {
            $this->respond(403, [
                'success' => false,
                'message' => 'Solicitud no permitida.',
            ]);

            return;
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION = [];
        session_destroy();

        $params = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 42000,
            'path' => $params['path'],
            'domain' => $params['domain'],
            'secure' => (bool) $params['secure'],
            'httponly' => (bool) $params['httponly'],
            'samesite' => $params['samesite'],
        ]);

        $this->respond(200, ['success' => true]);
    }

    /**
     * Punto de extensión que hace observable el cuerpo de la petición en pruebas: por defecto
     * lee `php://input` (lo que hace la app real), pero un doble de prueba puede sobreescribirlo
     * sin recurrir a un wrapper de stream, que `php://` no permite rebind por request.
     */
    protected function requestBody(): string
    {
        return (string) file_get_contents('php://input');
    }

    private function hasValidCsrfToken(): bool
    {
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;

        return CsrfTokenManager::validate(is_string($token) ? $token : null, self::CSRF_FORM_KEY);
    }

    /**
     * Aplana `array<string,list<string>>` a `array<string,string>`: el contrato de cliente
     * (`frontend/src/lib/api/cliente.ts`) espera un único mensaje por campo, mientras
     * `PasswordPolicyService::validateFields()` acumula todas las reglas que falló ese campo.
     * Se decide unir con `; ` en vez de quedarse solo con la primera regla: perder las demás
     * reglas violadas obligaría al usuario a corregir y reenviar una por una.
     *
     * @param array<string,list<string>> $fieldErrors
     * @return array<string,string>
     */
    private function flattenFieldErrors(array $fieldErrors): array
    {
        $flattened = [];
        foreach ($fieldErrors as $field => $messages) {
            $flattened[$field] = implode('; ', array_map('strval', $messages));
        }

        return $flattened;
    }

    /**
     * @param array<string,mixed> $payload
     */
    private function respond(int $status, array $payload): void
    {
        http_response_code($status);
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Puente de coexistencia entre dos envoltorios de error, deliberado, no diseño final.
     *
     * Publica las claves planas de nivel superior (`success`, `code`, `message`, `fieldErrors`,
     * `redirect`, `correlationId`) para consumidores legados, y además un bloque `error`
     * anidado (`codigo`, `mensaje`, `campos`) porque es el que `frontend/src/lib/api/cliente.ts`
     * (`pedir()`) sabe leer: sin él, un 422 de este endpoint le llega al cliente como mensaje
     * genérico `"<ruta> respondió <status>"`, sin ningún `fieldErrors` — el aplanado con `'; '`
     * quedaría producido y nunca visto. Es aditivo (`EsquemaCuerpoErrorApi` es `.passthrough()`)
     * y sigue la convención ya vigente en `NotificationController`, `ContextController`,
     * `PlanComprasJsonRespuestas` y `LpsApiController`, que es la anidada — la plana es el estilo
     * viejo propio de `Api/*ApiController`. Se retira el día que ya no quede ningún consumidor de
     * la forma plana leyendo estas respuestas.
     *
     * @param array<string,string> $fieldErrors
     */
    private function respondError(int $status, string $code, string $message, array $fieldErrors = []): void
    {
        $this->respond($status, [
            'success' => false,
            'code' => $code,
            'message' => $message,
            'fieldErrors' => $fieldErrors,
            'redirect' => null,
            'correlationId' => null,
            'error' => [
                'codigo' => $code,
                'mensaje' => $message,
                'campos' => $fieldErrors,
            ],
        ]);
    }

    private function sendJsonHeaders(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    }
}
