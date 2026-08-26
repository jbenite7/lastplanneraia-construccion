<?php

// 0. Configurar zona horaria a America/Bogota de forma consistente
date_default_timezone_set('America/Bogota');

// 1. Cargar Autoloader de Composer
require_once __DIR__ . '/../vendor/autoload.php';

// 2. Cargar dependencias globales (Legacy support)
// Ajustamos la ruta para apuntar a la raíz del proyecto
define('PROJECT_ROOT', dirname(__DIR__));

require_once PROJECT_ROOT . '/src/Core/Database.php';
require_once PROJECT_ROOT . '/src/Core/TableResolver.php';

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
$publicRoutes = ['/', '/login', '/login/cancelar', '/password/forgot', '/password/reset', '/password/update', '/runtime/frontend-config.js', '/runtime/css/aia-design-system.css', '/runtime/css/design-system/lab-entrypoint.css', '/runtime/css/design-system/entrypoints/core.css', '/runtime/css/design-system/entrypoints/attach-jquery-ui.css', '/runtime/css/design-system/entrypoints/attach-anychart.css', '/runtime/css/design-system/entrypoints/attach-select2.css', '/runtime/css/design-system/entrypoints/attach-sweetalert2.css', '/runtime/css/design-system/entrypoints/attach-handsontable.css', MaintenanceMode::SECRET_PATH];

// Puerta de servicio de desarrollo: solo existe si el candado triple lo permite
// (APP_ENV development/testing + petición local + DEV_DOOR=1). Ver src/Core/DevDoor.php.
$devDoorIsOpen = \App\Core\DevDoor::isOpen();
if ($devDoorIsOpen) {
    $publicRoutes[] = '/dev/entrar';
}

if (!in_array($requestUri, $publicRoutes, true)) {
    \App\Core\SessionMiddleware::check();
}

// 4. Instanciar Conexión a Base de Datos (Singleton)
use App\Core\Router;

// Establecer la conexión globalmente si es necesario para código legacy mezclado
// $db = Database::getInstance()->getConnection();

// 5. Configurar Rutas
$router = new Router();

function deprecatedJson(string $replacement): void
{
    http_response_code(410);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'respuesta' => 'DEPRECATED',
        'mensaje' => "Esta ruta fue reemplazada. Use {$replacement}.",
        'replacement' => $replacement,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

// --- ZONA DE RUTAS ---
if (\App\Core\AppEnvironment::allowsInternalTools()) {
    $router->get('/internal/design-system', [
        \App\Controllers\Internal\DesignSystemLabController::class,
        'index',
    ]);
}

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
$router->get('/login/cancelar', [\App\Controllers\Auth\LoginController::class, 'cancelPasswordChange']);
$router->get('/logout', [\App\Controllers\Auth\LoginController::class, 'logout']);

// Project Selector (Phase 2)
$router->get('/proyectos', [\App\Controllers\Core\ProjectSelectorController::class, 'index']);
$router->post('/proyecto/seleccionar', [\App\Controllers\Core\ProjectSelectorController::class, 'select']);

// Puerta de servicio de desarrollo. Fuera de desarrollo la ruta NO se registra: el router
// responde 404, que es lo correcto — un 403 confirmaría que el endpoint existe.
if ($devDoorIsOpen) {
    $router->get('/dev/entrar', [\App\Controllers\Core\DevDoorController::class, 'enter']);
}


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
$router->get('/plan-compras', [\App\Controllers\Gestion\PlanComprasController::class, 'index']);
$router->get('/profesionales', [\App\Controllers\Gestion\ProfesionalesController::class, 'index']);
$router->get('/subcontratistas', [\App\Controllers\Gestion\SubcontratistasController::class, 'index']);
$router->get('/indicadores', [\App\Controllers\Gestion\IndicadoresController::class, 'index']);

// Reportes (Nuevo Controller) - Allow GET and POST
// Reportes (Nuevo Controller) - Allow GET and POST
$router->get('/reportes/{tipo}', [\App\Controllers\Gestion\ReportController::class, 'generate']);
$router->post('/reportes/{tipo}', [\App\Controllers\Gestion\ReportController::class, 'generate']);

// Integracion
$router->get('/control-cambios', [\App\Controllers\Integracion\ControlCambiosController::class, 'index']);

// --- APIs (Migradas Fase 3) ---
// Api/Plan de Compras v2 (isla React)
$router->get('/plan-compras/api/contexto', [\App\Controllers\Api\PlanComprasApiController::class, 'contexto']);
$router->post('/plan-compras/api/presupuesto/preview', [\App\Controllers\Api\PlanComprasImportController::class, 'preview']);
$router->post('/plan-compras/api/presupuesto/confirmar', [\App\Controllers\Api\PlanComprasImportController::class, 'confirmar']);
$router->get('/plan-compras/api/presupuesto/versiones', [\App\Controllers\Api\PlanComprasImportController::class, 'versiones']);
$router->post('/plan-compras/api/presupuesto/activar', [\App\Controllers\Api\PlanComprasImportController::class, 'activar']);
$router->get('/plan-compras/api/presupuesto/impacto-version', [\App\Controllers\Api\PlanComprasImportController::class, 'impactoVersion']);
$router->get('/plan-compras/api/presupuesto/arbol', [\App\Controllers\Api\PlanComprasImportController::class, 'arbol']);
$router->get('/plan-compras/api/presupuesto/comparar', [\App\Controllers\Api\PlanComprasImportController::class, 'comparar']);

// Api/Plan de Compras v2 — maestro de insumos (A2)
$router->get('/plan-compras/api/maestro', [\App\Controllers\Api\PlanComprasMaestroController::class, 'catalogo']);
$router->get('/plan-compras/api/maestro/vinculos', [\App\Controllers\Api\PlanComprasMaestroController::class, 'vinculos']);
$router->get('/plan-compras/api/maestro/sugerencias', [\App\Controllers\Api\PlanComprasMaestroController::class, 'sugerencias']);
$router->post('/plan-compras/api/maestro/vinculos/generar', [\App\Controllers\Api\PlanComprasMaestroController::class, 'generar']);
$router->post('/plan-compras/api/maestro/vinculos/confirmar', [\App\Controllers\Api\PlanComprasMaestroController::class, 'confirmar']);
$router->post('/plan-compras/api/maestro/crear-desde-pendientes', [\App\Controllers\Api\PlanComprasMaestroController::class, 'crearDesdePendientes']);
$router->post('/plan-compras/api/maestro', [\App\Controllers\Api\PlanComprasMaestroController::class, 'crearManual']);
$router->post('/plan-compras/api/maestro/desactivar', [\App\Controllers\Api\PlanComprasMaestroController::class, 'desactivar']);
$router->post('/plan-compras/api/maestro/reactivar', [\App\Controllers\Api\PlanComprasMaestroController::class, 'reactivar']);
$router->post('/plan-compras/api/maestro/importar/preview', [\App\Controllers\Api\PlanComprasMaestroImportController::class, 'preview']);
$router->post('/plan-compras/api/maestro/importar/confirmar', [\App\Controllers\Api\PlanComprasMaestroImportController::class, 'confirmar']);
// Cola de equipos sin clasificar (Ola 2): alquilado vs comprado. Escritura con lps.pdc.maestro.
$router->get('/plan-compras/api/maestro/equipos', [\App\Controllers\Api\PlanComprasMaestroController::class, 'equipos']);
$router->post('/plan-compras/api/maestro/equipos/clasificar', [\App\Controllers\Api\PlanComprasMaestroController::class, 'clasificarEquipos']);
// Api/Plan de Compras v2 — paquetes de contratación (A3)
$router->get('/plan-compras/api/paquetes', [\App\Controllers\Api\PlanComprasPaquetesController::class, 'catalogo']);
$router->get('/plan-compras/api/paquetes/insumos', [\App\Controllers\Api\PlanComprasPaquetesController::class, 'insumos']);
$router->get('/plan-compras/api/paquetes/sugerencias', [\App\Controllers\Api\PlanComprasPaquetesController::class, 'sugerencias']);
$router->get('/plan-compras/api/paquetes/candidatos', [\App\Controllers\Api\PlanComprasPaquetesController::class, 'candidatos']);
$router->get('/plan-compras/api/paquetes/resumen', [\App\Controllers\Api\PlanComprasPaquetesController::class, 'resumen']);
$router->get('/plan-compras/api/paquetes/insumo-actividades', [\App\Controllers\Api\PlanComprasPaquetesController::class, 'insumoActividades']);
$router->post('/plan-compras/api/paquetes', [\App\Controllers\Api\PlanComprasPaquetesController::class, 'crear']);
$router->post('/plan-compras/api/paquetes/asignar', [\App\Controllers\Api\PlanComprasPaquetesController::class, 'asignar']);
$router->post('/plan-compras/api/paquetes/omitir', [\App\Controllers\Api\PlanComprasPaquetesController::class, 'omitir']);
$router->post('/plan-compras/api/paquetes/desasignar', [\App\Controllers\Api\PlanComprasPaquetesController::class, 'desasignar']);
// A3.3 — auto-asignación acotada: preview de qué despacharía el motor solo y qué deja al humano.
$router->get('/plan-compras/api/paquetes/plan-auto', [\App\Controllers\Api\PlanComprasPaquetesController::class, 'planAuto']);
$router->post('/plan-compras/api/paquetes/auto-asignar', [\App\Controllers\Api\PlanComprasPaquetesController::class, 'autoAsignar']);
// Api/Plan de Compras v2 — plan de compras con fechas (A4). El bare "/plan" va después de los
// sufijados a propósito (ver brief de la Task 8): aunque FastRoute resuelve rutas estáticas por
// hashmap exacto y el orden no cambia el resultado, se preserva el orden pedido por consistencia.
$router->get('/plan-compras/api/plan/frentes', [\App\Controllers\Api\PlanComprasPlanController::class, 'frentes']);
$router->get('/plan-compras/api/plan/sugerencias', [\App\Controllers\Api\PlanComprasPlanController::class, 'sugerencias']);
$router->get('/plan-compras/api/plan/anclas', [\App\Controllers\Api\PlanComprasPlanController::class, 'anclas']);
$router->get('/plan-compras/api/plan/correspondencias', [\App\Controllers\Api\PlanComprasPlanController::class, 'correspondencias']);
$router->post('/plan-compras/api/plan/correspondencias', [\App\Controllers\Api\PlanComprasPlanController::class, 'guardarCorrespondencia']);
$router->get('/plan-compras/api/plan/desfases', [\App\Controllers\Api\PlanComprasPlanController::class, 'desfases']);
// B2 — el desfase se mira antes de aplicarse: simular no escribe, aplicar solo lo confirmado.
$router->get('/plan-compras/api/plan/reprogramacion/simular', [\App\Controllers\Api\PlanComprasPlanController::class, 'simularReprogramacion']);
$router->post('/plan-compras/api/plan/reprogramacion/aplicar', [\App\Controllers\Api\PlanComprasPlanController::class, 'aplicarReprogramacion']);
$router->get('/plan-compras/api/plan/responsables', [\App\Controllers\Api\PlanComprasPlanController::class, 'responsables']);
$router->get('/plan-compras/api/plan', [\App\Controllers\Api\PlanComprasPlanController::class, 'plan']);
$router->post('/plan-compras/api/plan/amarrar', [\App\Controllers\Api\PlanComprasPlanController::class, 'amarrar']);
$router->post('/plan-compras/api/plan/desamarrar', [\App\Controllers\Api\PlanComprasPlanController::class, 'desamarrar']);
$router->post('/plan-compras/api/plan/calcular', [\App\Controllers\Api\PlanComprasPlanController::class, 'calcular']);
$router->post('/plan-compras/api/plan/responsable', [\App\Controllers\Api\PlanComprasPlanController::class, 'responsable']);
// A4.1 — pasos del proceso de contratación configurables por proyecto. La sufijada va antes que la
// desnuda por consistencia con el resto del bloque (FastRoute resuelve estáticas por hashmap exacto).
$router->get('/plan-compras/api/plan/pasos', [\App\Controllers\Api\PlanComprasPlanController::class, 'pasos']);
$router->post('/plan-compras/api/plan/pasos/restablecer', [\App\Controllers\Api\PlanComprasPlanController::class, 'restablecerPasos']);
// A4.1 · diferido nº 2 — copiar la configuración de otra obra. Copia puntual, no vínculo vivo.
$router->get('/plan-compras/api/plan/pasos/historial', [\App\Controllers\Api\PlanComprasPlanController::class, 'historialPasos']);
$router->get('/plan-compras/api/plan/pasos/origenes', [\App\Controllers\Api\PlanComprasPlanController::class, 'origenesPasos']);
$router->get('/plan-compras/api/plan/pasos/copia-preview', [\App\Controllers\Api\PlanComprasPlanController::class, 'previewCopiaPasos']);
$router->post('/plan-compras/api/plan/pasos/copiar', [\App\Controllers\Api\PlanComprasPlanController::class, 'copiarPasos']);
// A4.1 · diferido nº 4 — las duraciones del catalogo, editables sin entrar a la base.
$router->get('/plan-compras/api/plan/duraciones', [\App\Controllers\Api\PlanComprasPlanController::class, 'duraciones']);
$router->post('/plan-compras/api/plan/duraciones', [\App\Controllers\Api\PlanComprasPlanController::class, 'guardarDuracion']);
$router->post('/plan-compras/api/plan/pasos', [\App\Controllers\Api\PlanComprasPlanController::class, 'guardarPasos']);
// PDC v2 · Fase B1 — Seguimiento. Las rutas con segmento van antes que la desnuda, igual que en el
// bloque del plan, para no depender de como resuelva el router los prefijos.
$router->get('/plan-compras/api/seguimiento/vencimientos', [\App\Controllers\Api\PlanComprasSeguimientoController::class, 'vencimientos']);
$router->get('/plan-compras/api/seguimiento/paquete', [\App\Controllers\Api\PlanComprasSeguimientoController::class, 'paquete']);
$router->post('/plan-compras/api/seguimiento/paso', [\App\Controllers\Api\PlanComprasSeguimientoController::class, 'paso']);
$router->get('/plan-compras/api/seguimiento', [\App\Controllers\Api\PlanComprasSeguimientoController::class, 'resumen']);
// Flujo de caja (Ola 3). Va bajo `seguimiento` porque es una lectura del plan en operación, no una
// pieza del ensamble. El `.csv` se registra ANTES que la ruta sin extensión: FastRoute casa por
// literal exacto, pero mantenerlos juntos y en este orden evita que alguien inserte más tarde un
// parámetro que se coma la extensión.
$router->get('/plan-compras/api/seguimiento/flujo-caja.csv', [\App\Controllers\Api\PlanComprasSubpaquetesController::class, 'flujoCajaCsv']);
$router->get('/plan-compras/api/seguimiento/flujo-caja', [\App\Controllers\Api\PlanComprasSubpaquetesController::class, 'flujoCaja']);
// Subpaquetes de obra (Ola 3).
$router->get('/plan-compras/api/subpaquetes/destinos', [\App\Controllers\Api\PlanComprasSubpaquetesController::class, 'destinos']);
$router->get('/plan-compras/api/subpaquetes', [\App\Controllers\Api\PlanComprasSubpaquetesController::class, 'listar']);
$router->post('/plan-compras/api/subpaquetes/partir', [\App\Controllers\Api\PlanComprasSubpaquetesController::class, 'partir']);
$router->post('/plan-compras/api/subpaquetes/agregar', [\App\Controllers\Api\PlanComprasSubpaquetesController::class, 'agregar']);
$router->post('/plan-compras/api/subpaquetes/actualizar', [\App\Controllers\Api\PlanComprasSubpaquetesController::class, 'actualizar']);
$router->post('/plan-compras/api/subpaquetes/eliminar', [\App\Controllers\Api\PlanComprasSubpaquetesController::class, 'eliminar']);
$router->post('/plan-compras/api/subpaquetes/mover', [\App\Controllers\Api\PlanComprasSubpaquetesController::class, 'mover']);
// Api/PG Breadcrumb
$router->post('/api/pg/breadcrumb-estandarizar', [\App\Controllers\Api\PgBreadcrumbController::class, 'standardize']);
$router->post('/api/pg/breadcrumb-preview', [\App\Controllers\Api\PgBreadcrumbController::class, 'preview']);
// Api/Profesionales
$router->get('/api/profesionales/list', [\App\Controllers\Api\ProfesionalesApiController::class, 'list']);
$router->post('/api/profesionales/list', [\App\Controllers\Api\ProfesionalesApiController::class, 'list']);
$router->post('/api/profesionales/save', [\App\Controllers\Api\ProfesionalesApiController::class, 'save']);
// Api/Subcontratistas
$router->get('/api/subcontratistas/list', [\App\Controllers\Api\SubcontratistasApiController::class, 'list']);
$router->post('/api/subcontratistas/list', [\App\Controllers\Api\SubcontratistasApiController::class, 'list']);
$router->post('/api/subcontratistas/save', [\App\Controllers\Api\SubcontratistasApiController::class, 'save']);
// Api/ControlCambios
$router->post('/api/control-cambios/list', [\App\Controllers\Api\ControlCambiosApiController::class, 'list']);
$router->post('/api/control-cambios/save', [\App\Controllers\Api\ControlCambiosApiController::class, 'save']);
// Api/Semanal (Fase 4)
$router->get('/api/semanal/list', [\App\Controllers\Api\SemanalApiController::class, 'list']);
$router->post('/api/semanal/list', [\App\Controllers\Api\SemanalApiController::class, 'list']);
    $router->post('/api/semanal/save', [\App\Controllers\Api\SemanalApiController::class, 'save']);
    $router->post('/api/semanal/reabrir', [\App\Controllers\Api\SemanalApiController::class, 'reabrir']);
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
$router->get('/runtime/css/aia-design-system.css', [\App\Controllers\Core\DesignSystemAssetController::class, 'main']);
$router->get('/runtime/css/design-system/lab-entrypoint.css', [\App\Controllers\Core\DesignSystemAssetController::class, 'laboratory']);
$router->get('/runtime/css/design-system/entrypoints/core.css', [\App\Controllers\Core\DesignSystemAssetController::class, 'core']);
$router->get('/runtime/css/design-system/entrypoints/attach-jquery-ui.css', [\App\Controllers\Core\DesignSystemAssetController::class, 'attachJqueryUi']);
$router->get('/runtime/css/design-system/entrypoints/attach-anychart.css', [\App\Controllers\Core\DesignSystemAssetController::class, 'attachAnychart']);
$router->get('/runtime/css/design-system/entrypoints/attach-select2.css', [\App\Controllers\Core\DesignSystemAssetController::class, 'attachSelect2']);
$router->get('/runtime/css/design-system/entrypoints/attach-sweetalert2.css', [\App\Controllers\Core\DesignSystemAssetController::class, 'attachSweetalert2']);
$router->get('/runtime/css/design-system/entrypoints/attach-handsontable.css', [\App\Controllers\Core\DesignSystemAssetController::class, 'attachHandsontable']);
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
$router->post('/context/clear-week', [\App\Controllers\Core\ContextController::class, 'clearWeek']);

// Maintenance Secret Access (ruta oculta para admins durante mantenimiento)
$router->get(MaintenanceMode::SECRET_PATH, [\App\Controllers\Auth\LoginController::class, 'index']);
$router->post(MaintenanceMode::SECRET_PATH, [\App\Controllers\Auth\LoginController::class, 'maintenanceLogin']);

// --- BI Control Tower API ---
$router->get('/api/bi/control-tower', [\App\Controllers\Api\BiControlTowerApiController::class, 'controlTower']);
$router->get('/api/bi/projects', [\App\Controllers\Api\BiControlTowerApiController::class, 'projects']);
$router->get('/api/bi/weeks', [\App\Controllers\Api\BiControlTowerApiController::class, 'weeks']);
$router->get('/api/bi/filter-options', [\App\Controllers\Api\BiControlTowerApiController::class, 'filterOptions']);
$router->get('/api/bi/report/programa-general', [\App\Controllers\Api\BiControlTowerApiController::class, 'programaGeneral']);
$router->get('/api/bi/report/programa-general/compliance-detail', [\App\Controllers\Api\BiControlTowerApiController::class, 'programaComplianceDetail']);
$router->get('/api/bi/report/programa-general/progress-detail', [\App\Controllers\Api\BiControlTowerApiController::class, 'programaProgressDetail']);
$router->get('/api/bi/report/programa-general/delay-detail', [\App\Controllers\Api\BiControlTowerApiController::class, 'programaDelayDetail']);
$router->get('/api/bi/report/programa-general/radar-detail', [\App\Controllers\Api\BiControlTowerApiController::class, 'programaRadarDetail']);
$router->get('/api/bi/report/programa-general/cnp-detail', [\App\Controllers\Api\BiControlTowerApiController::class, 'programaCnpDetail']);
$router->get('/api/bi/report/programa-general/cnc-detail', [\App\Controllers\Api\BiControlTowerApiController::class, 'programaCncDetail']);
$router->get('/api/bi/report/intermedia', [\App\Controllers\Api\BiControlTowerApiController::class, 'intermedia']);
$router->get('/api/bi/report/semanal', [\App\Controllers\Api\BiControlTowerApiController::class, 'semanal']);
$router->get('/api/bi/report/pdc', [\App\Controllers\Api\BiControlTowerApiController::class, 'pdc']);
$router->get('/api/bi/report/pdc/detail', [\App\Controllers\Api\BiControlTowerApiController::class, 'pdcDetail']);
$router->get('/api/bi/report/cic', [\App\Controllers\Api\BiControlTowerApiController::class, 'cic']);
$router->get('/api/bi/report/cip', [\App\Controllers\Api\BiControlTowerApiController::class, 'cip']);
$router->get('/api/bi/report/curva-s', [\App\Controllers\Api\BiControlTowerApiController::class, 'curvaS']);
$router->get('/api/bi/lineage', [\App\Controllers\Api\BiControlTowerApiController::class, 'lineage']);
$router->post('/api/bi/control-tower/restricciones/{id}/gestion', [\App\Controllers\Api\BiConstraintWriteController::class, 'gestion']);

// --- BI Control Tower Dashboard Views ---
$router->get('/bi/control-tower', [\App\Controllers\Bi\BiViewController::class, 'controlTower']);
$router->get('/bi/programa-general', [\App\Controllers\Bi\BiViewController::class, 'programaGeneral']);
$router->get('/bi/intermedia', [\App\Controllers\Bi\BiViewController::class, 'intermedia']);
$router->get('/bi/semanal', [\App\Controllers\Bi\BiViewController::class, 'semanal']);
$router->get('/bi/pdc', [\App\Controllers\Bi\BiViewController::class, 'pdc']);
$router->get('/bi/contratistas', [\App\Controllers\Bi\BiViewController::class, 'contratistas']);
$router->get('/bi/responsables', [\App\Controllers\Bi\BiViewController::class, 'responsables']);
$router->get('/bi/curva-s', [\App\Controllers\Bi\BiViewController::class, 'curvaS']);

// --- FIN ZONA DE RUTAS ---

// 6. Despachar
try {
    $router->dispatch();
} catch (Exception $e) {
    error_log($e->getMessage());
    // El detalle solo se muestra donde ya se mostraba —con display_errors—, pero
    // ahora dentro de la página del producto en vez de sobre un <h1> pelado. En
    // produccion display_errors esta apagado y el usuario ve el mensaje generico,
    // que es lo que evita filtrar internos.
    \App\Core\ErrorPage::render(
        500,
        'Algo se rompió de nuestro lado',
        ini_get('display_errors')
            ? $e->getMessage()
            : 'El error quedó registrado. Puedes volver e intentarlo otra vez.'
    );
}
