<?php

/**
 * Front Controller - Panel Administrativo
 * Este es el único punto de entrada a la aplicación.
 */

error_log("Solicitud recibida en index.php: " . $_SERVER['REQUEST_URI']);

// 1. Cargar el Autoloader de Composer para las dependencias
require_once __DIR__ . '/../../vendor/autoload.php';

// 1.5 Definir constante de raíz del proyecto
define('ADMIN_PROJECT_ROOT', __DIR__ . '/../..');

// 2. Cargar las variables de entorno (.env)
$dotenv = Dotenv\Dotenv::createImmutable(ADMIN_PROJECT_ROOT);
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
$router->add('POST', '/dashboard/toggle-maintenance', 'DashboardController@toggleMaintenance');
$router->add('POST', '/dashboard/forzar-cambio-clave', 'DashboardController@forcePasswordChange');
$router->add('POST', '/dashboard/run-reportes', 'DashboardController@runReportes');
$router->add('GET', '/dashboard/report-progress', 'DashboardController@reportProgress');




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

// Configuración de Matching
$router->add('GET', '/matching/config', 'ConfigController@index');
$router->add('POST', '/matching/config', 'ConfigController@update');
$router->add('GET', '/matching/family-catalog', 'FamilyCatalogController@index');
$router->add('POST', '/matching/family-catalog/family', 'FamilyCatalogController@saveFamily');
$router->add('POST', '/matching/family-catalog/alias', 'FamilyCatalogController@saveAlias');
$router->add('POST', '/matching/family-catalog/contractual', 'FamilyCatalogController@saveContractualElement');
$router->add('POST', '/matching/family-catalog/contract-option', 'FamilyCatalogController@saveContractOption');
$router->add('POST', '/matching/family-catalog/rule', 'FamilyCatalogController@saveRuleAssignment');
$router->add('POST', '/matching/family-catalog/approve', 'FamilyCatalogController@approveCatalogItem');
$router->add('POST', '/matching/family-catalog/resolve-decision', 'FamilyCatalogController@resolvePendingDecision');
$router->add('POST', '/matching/family-catalog/import', 'FamilyCatalogController@importCatalog');
$router->add('GET', '/matching/family-catalog/export', 'FamilyCatalogController@exportCatalog');

// Mantenimiento del Plan de Compras
$router->add('GET', '/pdc/limpieza', 'PdcMaintenanceController@index');
$router->add('GET', '/pdc/limpieza/conteos', 'PdcMaintenanceController@counts');
$router->add('POST', '/pdc/limpieza/ejecutar', 'PdcMaintenanceController@run');


// Ejecutar el ruteo

$router->dispatch();
