<?php

namespace Admin\Controllers;

use Admin\Core\Security;
use Admin\Models\User;
use App\Security\EventService;
use App\Security\RbacService;

class AuthController extends BaseController
{
    private $userModel;
    private $events;
    private $rbac;

    public function __construct()
    {
        $db = \Database::getInstance();
        $this->userModel = new User($db);
        $this->events = new EventService($db);
        $this->rbac = new RbacService($db);
    }

    /**
     * Show login view.
     */
    public function loginView()
    {
        if (isset($_SESSION['admin_user'])) {
            header('Location: /admin/');
            exit;
        }

        $this->render('login', [
            'title' => 'Iniciar Sesión - Admin Panel',
            'csrf_token' => Security::generateCsrfToken(),
            'inactive_notice' => (($_GET['inactive'] ?? '') === '1'),
        ], false);
    }

    /**
     * Handle login POST request.
     */
    public function login()
    {
        // Validate CSRF
        if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->json(['success' => false, 'message' => 'Token CSRF inválido']);
        }

        $username = trim($_POST['usuario'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            $this->json(['success' => false, 'message' => 'Usuario y contraseña son requeridos']);
        }

        $user = $this->userModel->findByUsername($username);

        if ($user) {
            $password_valida = false;

            // Support both BCRYPT and SHA-512 (legacy)
            if (password_verify($password, $user['password'])) {
                $password_valida = true;
            } elseif (hash_equals($user['password'], hash('sha512', $password))) {
                $password_valida = true;
            }

            if ($password_valida) {
                if (isset($user['activo']) && (int)$user['activo'] !== 1) {
                    $this->events->emit(
                        'seguridad.auth',
                        'login_bloqueado_inactivo',
                        "Intento de acceso bloqueado por cuenta inactiva: $username",
                        ['scope' => 'admin'],
                        null,
                        'warning'
                    );

                    $this->json(['success' => false, 'message' => 'Tu cuenta está inactiva. Contacta al administrador.']);
                }

                // Set session
                $_SESSION['admin_user'] = [
                    'id' => $user['id'],
                    'nombre' => $user['nombre'],
                    'usuario' => $user['usuario'],
                    'permiso' => $this->rbac->normalizeRole($user['permiso']),
                ];

                Security::regenerateSession();

                // Auditoría
                $this->events->emit('seguridad.auth', 'login_exitoso', 'Inicio de sesion exitoso (admin)', ['scope' => 'admin']);

                $this->json(['success' => true, 'redirect' => '/admin/']);
            }
        }

        // Auditoría de fallo
        $this->events->emit(
            'seguridad.auth',
            'login_fallido',
            "Intento de acceso fallido para el usuario: $username",
            ['scope' => 'admin'],
            null,
            'error'
        );

        $this->json(['success' => false, 'message' => 'Credenciales incorrectas']);
    }

    /**
     * Logout.
     */
    public function logout()
    {
        // Auditoría antes de destruir la sesión
        $this->events->emit('seguridad.auth', 'logout', 'Cierre de sesion (admin)', ['scope' => 'admin']);

        unset($_SESSION['admin_user']);
        session_destroy();
        header('Location: /admin/login');
        exit;
    }
}
