<?php

// 1. Cargar Autoloader de Composer
require_once __DIR__ . '/../vendor/autoload.php';

// 2. Cargar dependencias globales (Legacy support)
// Ajustamos la ruta para apuntar a la raíz del proyecto
define('PROJECT_ROOT', dirname(__DIR__));

if (file_exists(PROJECT_ROOT . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(PROJECT_ROOT);
    $dotenv->safeLoad();
}

// 3. Iniciar Sesión Centralizada
if (session_status() === PHP_SESSION_NONE) {
    // Configuración segura de cookies de sesión
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => false, // Solo HTTPS en producción
        'httponly' => true,
        'samesite' => 'Lax' // Cambiado de 'Strict' a 'Lax' para compatibilidad con localhost
    ]);
    session_start();
}

// 3.5 Verificar Sesión y Timeout (Protección Universal)
// Excluimos las rutas públicas para permitir inicio de sesión y configuración temprana del frontend
$requestUri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
$publicRoutes = ['/', '/login', '/password/update', '/runtime/frontend-config.js'];
if (!in_array($requestUri, $publicRoutes, true)) {
    \App\Core\SessionMiddleware::check();
}

// 4. Instanciar Conexión a Base de Datos (Singleton)
use App\Core\Router;
// Importamos la clase Database existente (Legacy)
require_once PROJECT_ROOT . '/src/Core/Database.php';

// Establecer la conexión globalmente si es necesario para código legacy mezclado
// $db = Database::getInstance()->getConnection();

// 5. Configurar Rutas
$router = new Router();

// --- ZONA DE RUTAS ---
// Root / Entry Points
$router->get('/', [\App\Controllers\Auth\LoginController::class, 'index']);

// Auth
$router->get('/login', [\App\Controllers\Auth\LoginController::class, 'index']);
$router->post('/login', [\App\Controllers\Auth\LoginController::class, 'login']);
$router->post('/password/update', [\App\Controllers\Auth\LoginController::class, 'updatePassword']);
$router->get('/logout', [\App\Controllers\Auth\LoginController::class, 'logout']);

// Project Selector (Phase 2)
$router->get('/proyectos', [\App\Controllers\Core\ProjectSelectorController::class, 'index']);
$router->post('/proyecto/seleccionar', [\App\Controllers\Core\ProjectSelectorController::class, 'select']);


// Programacion
$router->get('/programa-general', [\App\Controllers\Programacion\ProgramaGeneralController::class, 'index']);
$router->post('/programa-general/filtros', [\App\Controllers\Programacion\ProgramaGeneralController::class, 'getFilters']);
$router->get('/programa-general/set-filtro', [\App\Controllers\Programacion\ProgramaGeneralController::class, 'setFilter']);
$router->get('/programacion-semanal', [\App\Controllers\Programacion\ProgramacionSemanalController::class, 'index']);
$router->get('/programacion-semanal/cnp', [\App\Controllers\Programacion\ProgramacionSemanalController::class, 'cnp']);
$router->get('/programacion-semanal/cnc', [\App\Controllers\Programacion\ProgramacionSemanalController::class, 'cnc']);
$router->get('/programacion-semanal/cic', [\App\Controllers\Programacion\ProgramacionSemanalController::class, 'cic']);
$router->get('/programacion-intermedia', [\App\Controllers\Programacion\ProgramacionIntermediaController::class, 'index']);
$router->post('/programacion-intermedia/filtros', [\App\Controllers\Programacion\ProgramacionIntermediaController::class, 'getFilters']);
$router->get('/programacion-intermedia/set-filtro', [\App\Controllers\Programacion\ProgramacionIntermediaController::class, 'setFilter']);
$router->post('/programacion-intermedia/shared-constraints/preview', [\App\Controllers\Programacion\ProgramacionIntermediaController::class, 'previewSharedConstraints']);
$router->post('/programacion-intermedia/shared-constraints/apply', [\App\Controllers\Programacion\ProgramacionIntermediaController::class, 'applySharedConstraints']);
$router->get('/api/pi/list', [\App\Controllers\Programacion\ProgramacionIntermediaController::class, 'list']);
$router->post('/api/pi/save', [\App\Controllers\Programacion\ProgramacionIntermediaController::class, 'save']);
$router->get('/api/general/list', [\App\Controllers\Api\GeneralApiController::class, 'list']);
$router->post('/api/general/list', [\App\Controllers\Api\GeneralApiController::class, 'list']);
$router->post('/api/general/update', [\App\Controllers\Api\GeneralApiController::class, 'update']);
$router->post('/api/general/update-batch', [\App\Controllers\Api\GeneralApiController::class, 'updateBatch']);
$router->post('/api/general/import', [\App\Controllers\Api\GeneralApiController::class, 'importExcel']);
$router->post('/api/general/delete-update', [\App\Controllers\Api\GeneralApiController::class, 'deleteUpdate']);
$router->get('/api/general/codigos', [\App\Controllers\Api\GeneralApiController::class, 'getCodigos']);
$router->post('/api/indicadores/generar', [\App\Controllers\Api\IndicadoresApiController::class, 'generar']);
$router->get('/programa-general-actualizar', [\App\Controllers\Programacion\ProgramaGeneralActualizarController::class, 'index']);

// Gestion
$router->get('/pdc', [\App\Controllers\Gestion\PdcController::class, 'index']);
$router->get('/profesionales', [\App\Controllers\Gestion\ProfesionalesController::class, 'index']);
$router->get('/subcontratistas', [\App\Controllers\Gestion\SubcontratistasController::class, 'index']);
$router->get('/contratos', [\App\Controllers\Gestion\ContratosController::class, 'index']);
$router->get('/listado-actividades', [\App\Controllers\Gestion\ListadoActividadesController::class, 'index']);
$router->get('/indicadores', [\App\Controllers\Gestion\IndicadoresController::class, 'index']);

// Reportes (Nuevo Controller) - Allow GET and POST
// Reportes (Nuevo Controller) - Allow GET and POST
$router->get('/reportes/{tipo}', [\App\Controllers\Gestion\ReportController::class, 'generate']);
$router->post('/reportes/{tipo}', [\App\Controllers\Gestion\ReportController::class, 'generate']);

// Integracion
$router->get('/control-cambios', [\App\Controllers\Integracion\ControlCambiosController::class, 'index']);

// --- APIs (Migradas Fase 3) ---
// Api/Contratos
$router->post('/api/contratos/list', [\App\Controllers\Api\ContratosApiController::class, 'list']);
$router->post('/api/contratos/save', [\App\Controllers\Api\ContratosApiController::class, 'save']);
// Api/ListadoActividades
$router->post('/api/listado-actividades/list', [\App\Controllers\Api\ListadoActividadesApiController::class, 'list']);
$router->post('/api/listado-actividades/save', [\App\Controllers\Api\ListadoActividadesApiController::class, 'save']);
// Api/PDC
$router->post('/api/pdc/list', [\App\Controllers\Api\PdcApiController::class, 'list']);
$router->post('/api/pdc/save', [\App\Controllers\Api\PdcApiController::class, 'save']);
// Api/Profesionales
$router->post('/api/profesionales/list', [\App\Controllers\Api\ProfesionalesApiController::class, 'list']);
$router->post('/api/profesionales/save', [\App\Controllers\Api\ProfesionalesApiController::class, 'save']);
// Api/Subcontratistas
$router->post('/api/subcontratistas/list', [\App\Controllers\Api\SubcontratistasApiController::class, 'list']);
$router->post('/api/subcontratistas/save', [\App\Controllers\Api\SubcontratistasApiController::class, 'save']);
// Api/ControlCambios
$router->post('/api/control-cambios/list', [\App\Controllers\Api\ControlCambiosApiController::class, 'list']);
$router->post('/api/control-cambios/save', [\App\Controllers\Api\ControlCambiosApiController::class, 'save']);
// Api/Semanal (Fase 4)
$router->get('/api/semanal/list', [\App\Controllers\Api\SemanalApiController::class, 'list']);
$router->post('/api/semanal/list', [\App\Controllers\Api\SemanalApiController::class, 'list']);
$router->post('/api/semanal/save', [\App\Controllers\Api\SemanalApiController::class, 'save']);
// Api/CIC (Fase 4)
$router->post('/api/cic/list', [\App\Controllers\Api\CicApiController::class, 'list']);
$router->post('/api/cic/save', [\App\Controllers\Api\CicApiController::class, 'save']);
// Api/CNC (Fase 4)
$router->post('/api/cnc/list', [\App\Controllers\Api\CncApiController::class, 'list']);
$router->post('/api/cnc/save', [\App\Controllers\Api\CncApiController::class, 'save']);
$router->post('/api/cnc/reasons', [\App\Controllers\Api\CncApiController::class, 'reasons']);
// Api/CNP (Fase 4)
$router->post('/api/cnp/list', [\App\Controllers\Api\CnpApiController::class, 'list']);
$router->post('/api/cnp/save', [\App\Controllers\Api\CnpApiController::class, 'save']);
$router->post('/api/cnp/reprogramar', [\App\Controllers\Api\CnpApiController::class, 'reprogramar']);


// Core
$router->get('/api/notifications/unread', [\App\Controllers\Core\NotificationController::class, 'getUnread']);
$router->post('/api/notifications/read', [\App\Controllers\Core\NotificationController::class, 'markAsRead']);
$router->get('/dashboard', [\App\Controllers\Core\DashboardController::class, 'index']);
$router->get('/runtime/frontend-config.js', [\App\Controllers\Core\FrontendConfigController::class, 'javascript']);
$router->post('/context/week', [\App\Controllers\Core\ContextController::class, 'setWeek']);

$router->get('/legacy/cambiar_pagina.php', function() {
    require_once PROJECT_ROOT . '/src/Legacy/Endpoints/cambiar_pagina.php';
});
$router->post('/legacy/cambiar_pagina.php', function() {
    require_once PROJECT_ROOT . '/src/Legacy/Endpoints/cambiar_pagina.php';
});

// Endpoint faltante para Cargar Datos Generales (Cargos/Nombres)
$router->post('/legacy/funciones_generales/php/datosGeneralesPagina.php', function() {
    require_once PROJECT_ROOT . '/src/Legacy/datosGeneralesPagina.php';
});
$router->post('/legacy/funciones_generales/php/nueva_semana.php', function() {
    require_once PROJECT_ROOT . '/src/Legacy/nueva_semana.php';
});
$router->post('/legacy/funciones_generales/php/verificarCICActualizada.php', function() {
    require_once PROJECT_ROOT . '/src/Legacy/verificarCICActualizada.php';
});
$router->post('/legacy/funciones_generales/php/eliminar_semana.php', function() {
    require_once PROJECT_ROOT . '/src/Legacy/eliminar_semana.php';
});
$router->post('/legacy/funciones_generales/php/buscadorTabla.php', function() {
    require_once PROJECT_ROOT . '/src/Legacy/buscadorTabla.php';
});
$router->post('/legacy/pdc/actualizar_pdc.php', function() {
    require_once PROJECT_ROOT . '/src/Legacy/actualizar_pdc.php';
});

$router->post('/context/clear-week', [\App\Controllers\Core\ContextController::class, 'clearWeek']);

// --- FIN ZONA DE RUTAS ---

// 6. Despachar
try {
    $router->dispatch();
} catch (Exception $e) {
    // Manejo básico de errores 500
    error_log($e->getMessage());
    http_response_code(500);
    echo "<h1>Error Interno del Servidor</h1>";
    if (ini_get('display_errors')) {
        echo "<pre>{$e->getMessage()}</pre>";
    }
}
