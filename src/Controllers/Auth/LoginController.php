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
        // 1. Si ya hay sesión completa y no debe cambiar clave, redirigir al dashboard
        if (isset($_SESSION['usuario']) && !isset($_SESSION['must_change_password'])) {
            header('Location: /dashboard');
            exit();
        }

        // 2. Mostrar la vista
        // Las vistas legacy suelen requerir variables globales o paths relativos complejos.
        // Vamos a incluir la vista legacy, pero saneada.

        $timeoutNotice = ($_GET['timeout'] ?? '') === '1';
        $inactiveNotice = ($_GET['inactive'] ?? '') === '1';
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
        $timeoutNotice = false;
        $inactiveNotice = false;

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
                if (isset($data['activo']) && (int)$data['activo'] !== 1) {
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
                    $_SESSION['usuario_temp'] = $usuario;
                    $_SESSION['nombreUsuario'] = $data['nombre'];
                    $_SESSION['must_change_password'] = true;
                    
                    if (method_exists($this->db, 'logActivity')) {
                        $this->db->logActivity('Login', 'LOGIN_PENDIENTE_CLAVE', "Usuario $usuario requiere cambio de contraseña.");
                    }
                    
                    header("Location: /login");
                    exit();
                }

                // 4. Crear Sesión Parcial (Solo identidad, sin proyecto)
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

        if (empty($password) || strlen($password) < 6) {
            echo json_encode(['success' => false, 'message' => 'La contraseña debe tener al menos 6 caracteres']);
            exit();
        }

        if (!preg_match('/[A-Z]/', $password)) {
            echo json_encode(['success' => false, 'message' => 'Debe contener al menos una letra mayúscula']);
            exit();
        }

        if (!preg_match('/[^a-zA-Z0-9]/', $password)) {
            echo json_encode(['success' => false, 'message' => 'Debe contener al menos un carácter especial (!@#$%...)']);
            exit();
        }

        if ($password !== $confirm) {
            echo json_encode(['success' => false, 'message' => 'Las contraseñas no coinciden']);
            exit();
        }

        // Verificar que no sea la misma contraseña anterior
        $stmt = $this->db->prepare("SELECT password FROM general_usuarios WHERE usuario = ? LIMIT 1");
        $stmt->execute([$usuario]);
        $row = $stmt->fetch();

        if ($row) {
            $oldHash = $row['password'];
            if (password_verify($password, $oldHash) || hash_equals($oldHash, hash('sha512', $password))) {
                echo json_encode(['success' => false, 'message' => 'La nueva contraseña no puede ser igual a la anterior']);
                exit();
            }
        }

        try {
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $this->db->prepare("UPDATE general_usuarios SET password = ?, force_password_change = 0 WHERE usuario = ?");
            $stmt->execute([$newHash, $usuario]);

            // Elevar a sesión definitiva si estaba en temp
            if (isset($_SESSION['usuario_temp'])) {
                $_SESSION['usuario'] = $_SESSION['usuario_temp'];
                unset($_SESSION['usuario_temp']);
                
                // Limpiar variables de proyecto anterior si existen
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
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error al actualizar la contraseña: ' . $e->getMessage()]);
        }
        exit();
    }
}
