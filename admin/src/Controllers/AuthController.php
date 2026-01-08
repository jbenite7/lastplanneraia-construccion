<?php

namespace Admin\Controllers;

use Admin\Models\User;
use Admin\Core\Security;
use Database;

class AuthController extends BaseController
{
    private $userModel;

    public function __construct()
    {
        $this->userModel = new User(Database::getInstance());
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
            'csrf_token' => Security::generateCsrfToken()
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
                // Set session
                $_SESSION['admin_user'] = [
                    'id' => $user['id'],
                    'nombre' => $user['nombre'],
                    'usuario' => $user['usuario'],
                    'permiso' => $user['permiso']
                ];

                Security::regenerateSession();

                $this->json(['success' => true, 'redirect' => '/admin/']);
            }
        }

        $this->json(['success' => false, 'message' => 'Credenciales incorrectas']);
    }

    /**
     * Logout.
     */
    public function logout()
    {
        unset($_SESSION['admin_user']);
        session_destroy();
        header('Location: /admin/login');
        exit;
    }
}