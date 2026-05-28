<?php

/**
 * Front Controller - Panel Administrativo
 * Este es el único punto de entrada a la aplicación.
 */

error_log("Solicitud recibida en index.php: " . $_SERVER['REQUEST_URI']);

// 1. Cargar el Autoloader de Composer para las dependencias
require_once __DIR__ . '/../../vendor/autoload.php';

// 2. Cargar las variables de entorno (.env)
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

// 3. Configuración del sistema de logs
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php_error.log');

// 4. Configuración de zona horaria
date_default_timezone_set('America/Bogota');

// 5. Inicializar Seguridad y Sesión
use Admin\Core\Security;

Security::initSession();

// 6. Inicializar la Base de Datos (Singleton)
if (file_exists(__DIR__ . '/../../src/Core/Database.php')) {
    require_once __DIR__ . '/../../src/Core/Database.php';
    try {
        // Aseguramos que la conexión se establezca
        $db = \Database::getInstance();
    } catch (\Exception $e) {
        error_log("Error inicializando base de datos: " . $e->getMessage());
    }
} else {
    error_log("Error: No se encontró el archivo Database.php en " . __DIR__ . '/../../src/Core/Database.php');
}

// 7. Inicializar el Router y definir rutas
use Admin\Core\Router;

$router = new Router();

// Rutas de Autenticación
$router->add('GET', '/login', 'AuthController@loginView');
$router->add('POST', '/login', 'AuthController@login');
$router->add('GET', '/password/forgot', 'PasswordResetController@forgotView');
$router->add('POST', '/password/forgot', 'PasswordResetController@sendResetLink');
$router->add('GET', '/password/reset', 'PasswordResetController@resetView');
$router->add('POST', '/password/reset', 'PasswordResetController@resetPassword');
$router->add('GET', '/logout', 'AuthController@logout');

// Rutas Protegidas

$router->add('GET', '/', 'DashboardController@index');

$router->add('GET', '/dashboard', 'DashboardController@index');
$router->add('POST', '/dashboard/toggle-console-logs', 'DashboardController@toggleConsoleLogs');
$router->add('POST', '/dashboard/forzar-cambio-clave', 'DashboardController@forcePasswordChange');
$router->add('POST', '/dashboard/run-reportes', 'DashboardController@runReportes');




// Gestión de Usuarios

$router->add('GET', '/usuarios', 'UserController@index');

$router->add('GET', '/usuarios/crear', 'UserController@create');

$router->add('GET', '/usuarios/sugerir-usuario', 'UserController@suggestUsername');

$router->add('GET', '/usuarios/cargos', 'UserController@getCargos');

$router->add('POST', '/usuarios/guardar', 'UserController@store');

$router->add('GET', '/usuarios/editar', 'UserController@edit');

$router->add('POST', '/usuarios/actualizar', 'UserController@update');

$router->add('POST', '/usuarios/toggle-active', 'UserController@toggleActive');

$router->add('POST', '/usuarios/toggle-force-password-change', 'UserController@toggleForcePasswordChange');

$router->add('POST', '/usuarios/revocar-todos-proyectos', 'UserController@revokeAllProjects');

$router->add('POST', '/usuarios/eliminar', 'UserController@delete');

$router->add('POST', '/usuarios/quitar-proyecto', 'UserController@removeProject');



// Gestión de Proyectos

$router->add('GET', '/proyectos', 'ProjectController@index');
$router->add('GET', '/proyectos/crear', 'ProjectController@create');
$router->add('POST', '/proyectos/guardar', 'ProjectController@store');
$router->add('GET', '/proyectos/editar', 'ProjectController@edit');
$router->add('POST', '/proyectos/actualizar', 'ProjectController@update');
$router->add('POST', '/proyectos/eliminar', 'ProjectController@delete');
$router->add('POST', '/proyectos/limpiar-huerfanas', 'ProjectController@cleanupOrphans');
$router->add('POST', '/proyectos/respaldo-completo', 'ProjectController@fullBackup');
$router->add('GET', '/proyectos/respaldar', 'ProjectController@backup');
$router->add('POST', '/proyectos/toggle-status', 'ProjectController@toggleStatus');

// Gestión de Miembros de Proyectos
$router->add('GET', '/proyectos/miembros', 'ProjectController@members');
$router->add('POST', '/proyectos/miembros/agregar', 'ProjectController@addMember');
$router->add('POST', '/proyectos/miembros/quitar', 'ProjectController@removeMember');
$router->add('GET', '/proyectos/sugerir-rol', 'ProjectController@suggestRole');



// Ejecutar el ruteo

$router->dispatch();
