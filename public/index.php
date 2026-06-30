<?php

// 0. Configurar zona horaria a America/Bogota de forma consistente
date_default_timezone_set('America/Bogota');

// 1. Cargar Autoloader de Composer
require_once __DIR__ . '/../vendor/autoload.php';

// 2. Cargar dependencias globales (Legacy support)
// Ajustamos la ruta para apuntar a la raíz del proyecto
define('PROJECT_ROOT', dirname(__DIR__));

// 2.5 Iniciar Sesión Centralizada (necesaria antes del check de mantenimiento)
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => false,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// 2.6 Maintenance mode check — permite bypass si el usuario tiene sesión admin (maintenance_bypass)
use App\Core\MaintenanceMode;

$requestUri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '/';
if (
    MaintenanceMode::isActive()
    && !MaintenanceMode::isExemptRoute($requestUri)
    && empty($_SESSION['maintenance_bypass'])
) {
    MaintenanceMode::renderPage();
}

if (file_exists(PROJECT_ROOT . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(PROJECT_ROOT);
    $dotenv->safeLoad();
}

// 3.5 Verificar Sesión y Timeout (Protección Universal)
$publicRoutes = ['/', '/login', '/password/forgot', '/password/reset', '/password/update', '/runtime/frontend-config.js', MaintenanceMode::SECRET_PATH];
if (!in_array($requestUri, $publicRoutes, true)) {
    \App\Core\SessionMiddleware::check();
}

// 4. Instanciar Conexión a Base de Datos (Singleton)
use App\Core\Router;

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
$router->get('/password/forgot', [\App\Controllers\Auth\PasswordResetController::class, 'forgot']);
$router->post('/password/forgot', [\App\Controllers\Auth\PasswordResetController::class, 'sendLink']);
$router->get('/password/reset', [\App\Controllers\Auth\PasswordResetController::class, 'reset']);
$router->post('/password/reset', [\App\Controllers\Auth\PasswordResetController::class, 'update']);
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
$router->get('/programacion-intermedia/set-view-all', [\App\Controllers\Programacion\ProgramacionIntermediaController::class, 'setViewAll']);
$router->post('/programacion-intermedia/shared-constraints/preview', [\App\Controllers\Programacion\ProgramacionIntermediaController::class, 'previewSharedConstraints']);
$router->post('/programacion-intermedia/shared-constraints/apply', [\App\Controllers\Programacion\ProgramacionIntermediaController::class, 'applySharedConstraints']);
$router->get('/api/pi/list', [\App\Controllers\Programacion\ProgramacionIntermediaController::class, 'list']);
$router->post('/api/pi/save', [\App\Controllers\Programacion\ProgramacionIntermediaController::class, 'save']);
$router->get('/api/general/list', [\App\Controllers\Api\GeneralApiController::class, 'list']);
$router->post('/api/general/list', [\App\Controllers\Api\GeneralApiController::class, 'list']);
$router->get('/api/general/restriction-config', [\App\Controllers\Api\GeneralApiController::class, 'restrictionConfig']);
$router->post('/api/general/update', [\App\Controllers\Api\GeneralApiController::class, 'update']);
$router->post('/api/general/update-batch', [\App\Controllers\Api\GeneralApiController::class, 'updateBatch']);
$router->post('/api/general/import', [\App\Controllers\Api\GeneralApiController::class, 'importExcel']);
$router->post('/api/general/delete-update', [\App\Controllers\Api\GeneralApiController::class, 'deleteUpdate']);
$router->get('/api/general/codigos', [\App\Controllers\Api\GeneralApiController::class, 'getCodigos']);
$router->post('/api/general/auto-associate', [\App\Controllers\Api\GeneralApiController::class, 'autoAssociate']);
$router->post('/api/general/decision-log', [\App\Controllers\Api\GeneralApiController::class, 'decisionLog']);
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
$router->post('/api/contratos/auto-assign', [\App\Controllers\Api\ContratosApiController::class, 'autoAssign']);
$router->post('/api/contratos/auto-define', [\App\Controllers\Api\ContratosApiController::class, 'autoDefine']);
$router->post('/api/contratos/auto-define/apply', [\App\Controllers\Api\ContratosApiController::class, 'autoDefineApply']);
$router->post('/api/contratos/auto-define/undo', [\App\Controllers\Api\ContratosApiController::class, 'autoDefineUndo']);
$router->post('/api/contratos/auto-define/reanalyze', [\App\Controllers\Api\ContratosApiController::class, 'autoDefineReanalyze']);
$router->post('/api/contratos/auto/preview', [\App\Controllers\Api\SemiAutoController::class, 'previewContratos']);
$router->post('/api/contratos/auto/status', [\App\Controllers\Api\SemiAutoController::class, 'statusContratos']);
$router->post('/api/contratos/auto/apply', [\App\Controllers\Api\SemiAutoController::class, 'applyContratos']);
$router->post('/api/contratos/auto/undo', [\App\Controllers\Api\SemiAutoController::class, 'undoContratos']);
$router->post('/api/contratos/auto/feedback', [\App\Controllers\Api\SemiAutoController::class, 'feedbackContratos']);
$router->post('/api/contratos/auto/metrics', [\App\Controllers\Api\SemiAutoController::class, 'metricsContratos']);
// Api/ListadoActividades
$router->get('/api/listado-actividades/template', [\App\Controllers\Api\ListadoActividadesApiController::class, 'downloadTemplate']);
$router->post('/api/listado-actividades/list', [\App\Controllers\Api\ListadoActividadesApiController::class, 'list']);
$router->post('/api/listado-actividades/save', [\App\Controllers\Api\ListadoActividadesApiController::class, 'save']);
$router->post('/api/listado-actividades/auto-generate', [\App\Controllers\Api\ListadoActividadesApiController::class, 'autoGenerate']);
$router->post('/api/listado-actividades/auto/preview', [\App\Controllers\Api\SemiAutoController::class, 'previewListado']);
$router->post('/api/listado-actividades/auto/status', [\App\Controllers\Api\SemiAutoController::class, 'statusListado']);
$router->post('/api/listado-actividades/auto/apply', [\App\Controllers\Api\SemiAutoController::class, 'applyListado']);
$router->post('/api/listado-actividades/auto/undo', [\App\Controllers\Api\SemiAutoController::class, 'undoListado']);
$router->post('/api/listado-actividades/auto/feedback', [\App\Controllers\Api\SemiAutoController::class, 'feedbackListado']);
$router->post('/api/listado-actividades/auto/metrics', [\App\Controllers\Api\SemiAutoController::class, 'metricsListado']);
// Api/PDC
$router->post('/api/pdc/list', [\App\Controllers\Api\PdcApiController::class, 'list']);
$router->post('/api/pdc/save', [\App\Controllers\Api\PdcApiController::class, 'save']);
$router->get('/api/pdc/duracion-sugerida', [\App\Controllers\Api\PdcApiController::class, 'duracionSugerida']);
// Api/PDC Plantillas
$router->get('/api/pdc/plantillas', [\App\Controllers\Api\PdcPlantillaController::class, 'list']);
$router->get('/api/pdc/plantillas/{id}', [\App\Controllers\Api\PdcPlantillaController::class, 'show']);
$router->get('/api/pdc/plantillas/{id}/items', [\App\Controllers\Api\PdcPlantillaController::class, 'items']);
$router->get('/api/pdc/categorias-recurso', [\App\Controllers\Api\PdcPlantillaController::class, 'categorias']);
$router->post('/api/pdc/auto/apply-from-actividades', [\App\Controllers\Api\PdcAutoGenerateController::class, 'applyFromActividades']);
$router->post('/api/pdc/auto/preview', [\App\Controllers\Api\SemiAutoController::class, 'previewPdc']);
$router->post('/api/pdc/auto/status', [\App\Controllers\Api\SemiAutoController::class, 'statusPdc']);
$router->post('/api/pdc/auto/apply', [\App\Controllers\Api\SemiAutoController::class, 'applyPdc']);
$router->post('/api/pdc/auto/undo', [\App\Controllers\Api\SemiAutoController::class, 'undoPdc']);
$router->post('/api/pdc/auto/feedback', [\App\Controllers\Api\SemiAutoController::class, 'feedbackPdc']);
$router->post('/api/pdc/auto/metrics', [\App\Controllers\Api\SemiAutoController::class, 'metricsPdc']);
// Api/PG Breadcrumb
$router->post('/api/pg/breadcrumb-estandarizar', [\App\Controllers\Api\PgBreadcrumbController::class, 'standardize']);
$router->post('/api/pg/breadcrumb-preview', [\App\Controllers\Api\PgBreadcrumbController::class, 'preview']);
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
$router->post('/api/semanal/auto-program', [\App\Controllers\Api\SemanalApiController::class, 'autoProgram']);
$router->get('/api/semanal/auto-program-log', [\App\Controllers\Api\SemanalApiController::class, 'getAutoProgramLog']);
$router->get('/api/semanal/tnp-actividades', [\App\Controllers\Api\SemanalApiController::class, 'getTnpActivities']);
$router->post('/api/semanal/tnp-actividades', [\App\Controllers\Api\SemanalApiController::class, 'getTnpActivities']);
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

// Api/LPS Contextual Drawers & Escalamientos
$router->get('/api/lps/comments', [\App\Controllers\Api\LpsApiController::class, 'comments']);
$router->post('/api/lps/comments', [\App\Controllers\Api\LpsApiController::class, 'addComment']);
$router->post('/api/lps/comments/add', [\App\Controllers\Api\LpsApiController::class, 'addComment']);
$router->post('/api/lps/crisis', [\App\Controllers\Api\LpsApiController::class, 'registerCrisis']);
$router->post('/api/lps/crisis/register', [\App\Controllers\Api\LpsApiController::class, 'registerCrisis']);
$router->post('/api/lps/crisis/close', [\App\Controllers\Api\LpsApiController::class, 'closeCrisis']);
$router->get('/dashboard/escalamientos', [\App\Controllers\Core\DashboardController::class, 'escalamientos']);


// Core
$router->get('/api/notifications/unread', [\App\Controllers\Core\NotificationController::class, 'getUnread']);
$router->post('/api/notifications/read', [\App\Controllers\Core\NotificationController::class, 'markAsRead']);
$router->get('/dashboard', [\App\Controllers\Core\DashboardController::class, 'index']);
$router->get('/runtime/frontend-config.js', [\App\Controllers\Core\FrontendConfigController::class, 'javascript']);
$router->post('/session/touch', [\App\Controllers\Core\SessionController::class, 'touch']);
$router->post('/context/week', [\App\Controllers\Core\ContextController::class, 'setWeek']);

$router->get('/legacy/cambiar_pagina.php', function () {
    require_once PROJECT_ROOT . '/src/Legacy/Endpoints/cambiar_pagina.php';
});
$router->post('/legacy/cambiar_pagina.php', function () {
    require_once PROJECT_ROOT . '/src/Legacy/Endpoints/cambiar_pagina.php';
});

// Endpoint faltante para Cargar Datos Generales (Cargos/Nombres)
$router->post('/legacy/funciones_generales/php/datosGeneralesPagina.php', function () {
    require_once PROJECT_ROOT . '/src/Legacy/datosGeneralesPagina.php';
});
$router->post('/legacy/funciones_generales/php/nueva_semana.php', function () {
    require_once PROJECT_ROOT . '/src/Legacy/nueva_semana.php';
});
$router->post('/legacy/funciones_generales/php/verificarCICActualizada.php', function () {
    require_once PROJECT_ROOT . '/src/Legacy/verificarCICActualizada.php';
});
$router->post('/legacy/funciones_generales/php/eliminar_semana.php', function () {
    require_once PROJECT_ROOT . '/src/Legacy/eliminar_semana.php';
});
$router->post('/legacy/funciones_generales/php/buscadorTabla.php', function () {
    require_once PROJECT_ROOT . '/src/Legacy/buscadorTabla.php';
});
$router->post('/legacy/pdc/actualizar_pdc.php', function () {
    require_once PROJECT_ROOT . '/src/Legacy/actualizar_pdc.php';
});

$router->post('/context/clear-week', [\App\Controllers\Core\ContextController::class, 'clearWeek']);

// Maintenance Secret Access (ruta oculta para admins durante mantenimiento)
$router->get(MaintenanceMode::SECRET_PATH, [\App\Controllers\Auth\LoginController::class, 'index']);
$router->post(MaintenanceMode::SECRET_PATH, [\App\Controllers\Auth\LoginController::class, 'maintenanceLogin']);

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
