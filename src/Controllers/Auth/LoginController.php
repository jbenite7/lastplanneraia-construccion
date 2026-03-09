<?php

namespace App\Controllers\Auth;

use Database;

class LoginController
{
    private $db;

    public function __construct()
    {
        // Obtener instancia de la base de datos (Singleton)
        $this->db = Database::getInstance();
    }

    public function index()
    {
        // 1. Si ya hay sesión, redirigir al dashboard
        if (isset($_SESSION['usuario'])) {
            header('Location: /dashboard');
            exit();
        }

        // 2. Mostrar la vista
        // Las vistas legacy suelen requerir variables globales o paths relativos complejos.
        // Vamos a incluir la vista legacy, pero saneada.

        $errores = ''; // Inicializar variable para la vista

        // Exponer $db para la vista legacy
        $db = $this->db;

        // Ajuste de rutas para que los includes de la vista funcionen
        // La vista espera estar en 'views/auth',
        // Mejor opción: Incluir la vista desde su ruta absoluta.
        require PROJECT_ROOT . '/views/auth/login.view.php';
    }

    public function login()
    {
        $errores = '';

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

            // 1. Buscar usuario (sin filtrar por proyecto)
            // Obtenemos el primer registro activo para validar contraseña
            $stmt = $this->db->prepare("SELECT * FROM general_usuarios WHERE usuario = ? LIMIT 1");
            $stmt->execute([$usuario]);
            $data = $stmt->fetch();

            $password_valida = false;

            if ($data) {
                // 2. Verificar contraseña
                if (password_verify($password, $data['password'])) {
                    $password_valida = true;
                } elseif (hash_equals($data['password'], hash('sha512', $password))) {
                    $password_valida = true;
                    // Migración transparente a BCRYPT
                    $newHash = password_hash($password, PASSWORD_DEFAULT);
                    // Actualizar contraseña para TODAS las entradas de este usuario
                    $updateQ = "UPDATE general_usuarios SET password = ? WHERE usuario = ?";
                    $stmtUpd = $this->db->prepare($updateQ);
                    $stmtUpd->execute([$newHash, $usuario]);
                }
            }

            if ($password_valida) {
                // 3. Crear Sesión Parcial (Solo identidad, sin proyecto)
                $_SESSION['usuario'] = $usuario;
                $_SESSION['nombreUsuario'] = $data['nombre'];

                // Limpiar variables de proyecto anterior si existen
                unset($_SESSION['proyecto']);
                unset($_SESSION['db']);
                unset($_SESSION['semana']);
                unset($_SESSION['permiso']);
                unset($_SESSION['pdcActivo']);

                // Log básico de ingreso
                if (method_exists($this->db, 'logActivity')) {
                    // Usamos una BD genérica o null para el log global
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
        session_destroy();
        header("Location: /login");
        exit();
    }

    // Método iniciarSesion eliminado ya que la lógica se movió a ProjectSelectorController::select()
}
