<?php

namespace App\Controllers\Auth;

use App\Core\MaintenanceMode;
use App\Services\Auth\AuthenticationService;
use App\Services\Auth\UserPasswordService;
use Database;

class LoginController
{
    private $db;
    private $passwords;
    private $authentication;

    public function __construct()
    {
        // Obtener instancia de la base de datos (Singleton)
        $this->db = Database::getInstance();
        $this->passwords = new UserPasswordService($this->db);
        $this->authentication = new AuthenticationService($this->db);
    }

    public function index()
    {
        if (isset($_SESSION['usuario']) && !isset($_SESSION['must_change_password'])) {
            header('Location: /dashboard');
            exit();
        }

        $requestUri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '/';
        $formAction = $requestUri === MaintenanceMode::SECRET_PATH
            ? MaintenanceMode::SECRET_PATH
            : '/login';

        $timeoutNotice = ($_GET['timeout'] ?? '') === '1';
        $inactiveNotice = ($_GET['inactive'] ?? '') === '1';
        $resetNotice = ($_GET['reset'] ?? '') === '1';
        $errores = '';

        $db = $this->db;
        require PROJECT_ROOT . '/views/auth/login.view.php';
    }

    public function login()
    {
        $errores = '';
        $timeoutNotice = false;
        $inactiveNotice = false;
        $resetNotice = false;

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Sanitizar usuario
            $usuario = htmlspecialchars(strtolower($_POST['usuario'] ?? ''));
            $password = $_POST['password'] ?? '';

            if (empty($usuario) || empty($password)) {
                $errores .= "<li>Usuario y contraseña son obligatorios.</li>";
                $db = $this->db;
                require PROJECT_ROOT . '/views/auth/login.view.php';

                return;
            }

            $data = $this->authentication->verifyCredentials($usuario, $password);

            if ($data) {
                if (isset($data['activo']) && (int) $data['activo'] !== 1) {
                    $errores .= "<li>Tu cuenta está inactiva. Contacta al administrador.</li>";
                    if (method_exists($this->db, 'logActivity')) {
                        $this->db->logActivity('Login', 'LOGIN_BLOQUEADO_INACTIVO', "Intento de acceso con cuenta inactiva: $usuario");
                    }

                    $db = $this->db;
                    require PROJECT_ROOT . '/views/auth/login.view.php';

                    return;
                }

                // 3. Verificar requisito de cambio de contraseña ANTES de crear sesión completa
                if (isset($data['force_password_change']) && $data['force_password_change'] == 1) {
                    $this->authentication->beginPasswordChange($usuario, $data);

                    if (method_exists($this->db, 'logActivity')) {
                        $this->db->logActivity('Login', 'LOGIN_PENDIENTE_CLAVE', "Usuario $usuario requiere cambio de contraseña.");
                    }

                    header("Location: /login");
                    exit();
                }

                // 4. Crear Sesión Parcial (Solo identidad, sin proyecto)
                $this->authentication->beginAuthenticatedSession($usuario, $data);

                // Log básico de ingreso
                if (method_exists($this->db, 'logActivity')) {
                    $this->db->logActivity('Login', 'LOGIN_FASE_1', "Usuario autenticado: $usuario");
                }

                // 4. Redirigir al Selector de Proyectos (Fase 2)
                header("Location: /proyectos");
                exit();

            } else {
                $errores .= "Error: <li>Usuario o contraseña incorrectos.</li>";
                if (method_exists($this->db, 'logActivity')) {
                    $this->db->logActivity('Login', 'LOGIN_FALLIDO', "Credenciales incorrectas: $usuario");
                }
            }

            // Si llegamos aquí, hubo errores
            $db = $this->db;
            require PROJECT_ROOT . '/views/auth/login.view.php';
        }
    }

    public function logout()
    {
        $query = [];

        if (($_GET['timeout'] ?? '') === '1') {
            $query['timeout'] = '1';
        }

        session_unset();
        session_destroy();
        $redirectUrl = '/login';

        if (!empty($query)) {
            $redirectUrl .= '?' . http_build_query($query);
        }

        header("Location: {$redirectUrl}");
        exit();
    }

    /**
     * Abandonar el cambio obligatorio de contraseña y volver al formulario de login.
     *
     * La sesión pendiente (`usuario_temp` + `must_change_password`) es previa a la
     * autenticación completa, así que descartarla entera es lo seguro. Si no hay cambio
     * pendiente no toca nada: así esta ruta pública no sirve para cerrarle la sesión a un
     * usuario ya autenticado desde fuera.
     */
    public function cancelPasswordChange()
    {
        $redirectUrl = '/login';

        if (!empty($_SESSION['must_change_password'])) {
            $usuario = $_SESSION['usuario_temp'] ?? $_SESSION['usuario'] ?? 'desconocido';

            if (method_exists($this->db, 'logActivity')) {
                $this->db->logActivity('Login', 'CAMBIO_CLAVE_CANCELADO', "Usuario $usuario abandonó el cambio obligatorio de contraseña.");
            }

            $maintenanceActive = MaintenanceMode::isActive();

            session_unset();
            session_destroy();

            // Durante mantenimiento, /login muestra la página de mantenimiento: devolver al
            // usuario a la ruta oculta, que sí está exenta y vuelve a mostrar el formulario.
            if ($maintenanceActive) {
                $redirectUrl = MaintenanceMode::SECRET_PATH;
            }
        }

        header("Location: {$redirectUrl}");
        exit();
    }

    // Método iniciarSesion eliminado ya que la lógica se movió a ProjectSelectorController::select()

    /**
     * Actualizar la contraseña del usuario (Requisito de Seguridad).
     */
    public function updatePassword()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || (!isset($_SESSION['usuario']) && !isset($_SESSION['usuario_temp']))) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Acceso no permitido']);
            exit();
        }

        $password = $_POST['password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        $usuario = $_SESSION['usuario'] ?? $_SESSION['usuario_temp'];

        header('Content-Type: application/json');

        try {
            $result = $this->passwords->changePasswordForUsername((string) $usuario, $password, $confirm, true);
            if (!$result['success']) {
                echo json_encode($result);
                exit();
            }

            if (isset($_SESSION['usuario_temp'])) {
                $_SESSION['usuario'] = $_SESSION['usuario_temp'];
                unset($_SESSION['usuario_temp']);

                if (MaintenanceMode::isActive()) {
                    $_SESSION['maintenance_bypass'] = true;
                }

                unset($_SESSION['proyecto']);
                unset($_SESSION['db']);
                unset($_SESSION['semana']);
                unset($_SESSION['permiso']);
                unset($_SESSION['pdcActivo']);
            }

            // Limpiar flag de sesión
            unset($_SESSION['must_change_password']);

            if (method_exists($this->db, 'logActivity')) {
                $this->db->logActivity('Seguridad', 'CAMBIO_CLAVE', "Usuario $usuario actualizó su contraseña por requerimiento.");
            }

            echo json_encode(['success' => true, 'message' => 'Contraseña actualizada correctamente. Bienvenido.']);
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'message' => 'Error al actualizar la contraseña: ' . $e->getMessage()]);
        }
        exit();
    }

    private function userHasGlobalAdminRole(string $usuario): bool
    {
        $stmt = $this->db->queryWithProject(
            "SELECT COUNT(*) FROM project_members pm
             INNER JOIN general_usuarios u ON u.id = pm.user_id
             INNER JOIN general_proyectos_procesos p ON p.ID = pm.project_id
             WHERE u.usuario = ?
               AND pm.role = 'A'
               AND p.Area = 'Construccion'
               AND p.Activo = 1",
            [$usuario]
        );
        return (int) $stmt->fetchColumn() > 0;
    }

    public function maintenanceLogin()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit;
        }

        $usuario = htmlspecialchars(strtolower($_POST['usuario'] ?? ''));
        $password = $_POST['password'] ?? '';

        if (empty($usuario) || empty($password)) {
            MaintenanceMode::renderPage();
        }

        $data = $this->authentication->verifyCredentials($usuario, $password);

        if (!$data) {
            MaintenanceMode::renderPage();
        }

        if (isset($data['activo']) && (int) $data['activo'] !== 1) {
            if (method_exists($this->db, 'logActivity')) {
                $this->db->logActivity('Login', 'LOGIN_BLOQUEADO_INACTIVO', "Intento de acceso con cuenta inactiva: $usuario");
            }
            MaintenanceMode::renderPage();
        }

        if (!$this->userHasGlobalAdminRole($usuario)) {
            if (method_exists($this->db, 'logActivity')) {
                $this->db->logActivity('Login', 'MAINTENANCE_BLOQUEADO', "Usuario $usuario sin rol A intentó acceso por ruta oculta");
            }
            MaintenanceMode::renderPage();
        }

        $_SESSION['maintenance_bypass'] = true;

        if (isset($data['force_password_change']) && $data['force_password_change'] == 1) {
            $this->authentication->beginPasswordChange($usuario, $data);

            if (method_exists($this->db, 'logActivity')) {
                $this->db->logActivity('Login', 'LOGIN_PENDIENTE_CLAVE', "Usuario $usuario requiere cambio de contraseña.");
            }

            header("Location: " . MaintenanceMode::SECRET_PATH);
            exit();
        }

        $this->authentication->beginAuthenticatedSession($usuario, $data);

        if (method_exists($this->db, 'logActivity')) {
            $this->db->logActivity('Login', 'MAINTENANCE_LOGIN', "Admin $usuario accedió por ruta oculta durante mantenimiento");
        }

        header("Location: /proyectos");
        exit();
    }
}
