<?php

/**
 * Front Controller - Panel Administrativo
 * Este es el único punto de entrada a la aplicación.
 */

// 1. Cargar el Autoloader de Composer para las dependencias
require_once __DIR__ . '/../../vendor/autoload.php';

// 2. Cargar las variables de entorno (.env)
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

// 3. Configuración del sistema de logs (Seguridad Día 1)
ini_set('display_errors', 0); // No mostrar errores al usuario final
ini_set('log_errors', 1);     // Guardar errores en un archivo
ini_set('error_log', __DIR__ . '/../logs/php_error.log'); // Ruta del archivo de logs

// 4. Configuración de zona horaria
date_default_timezone_set('America/Bogota');

// 5. Iniciar la sesión con parámetros de seguridad (Día 3)
session_start([
    'cookie_httponly' => true,
    'cookie_secure'   => isset($_SERVER['HTTPS']),
    'cookie_samesite' => 'Lax',
]);

// 6. Inicializar la Base de Datos (Singleton)
require_once __DIR__ . '/../../construccion/src/Database.php';
$db = Database::getInstance();

// 7. Inicializar el Router y procesar la petición
use Admin\Core\Router;

$router = new Router();

// Aquí definiremos las rutas más adelante
$route = $_GET['route'] ?? 'dashboard';

// Por ahora, un simple mensaje de prueba hasta configurar las vistas
echo "<h1>Panel Administrativo AIA</h1>";
echo "<p>Estructura segura cargada correctamente.</p>";
echo "<p>Ruta actual: " . htmlspecialchars($route) . "</p>";
