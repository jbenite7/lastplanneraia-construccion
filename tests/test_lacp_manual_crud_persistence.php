<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';

if (!defined('PROJECT_ROOT')) {
    define('PROJECT_ROOT', dirname(__DIR__));
}

require_once __DIR__ . '/../src/Controllers/Api/ListadoActividadesApiController.php';
require_once __DIR__ . '/../src/Controllers/Api/ContratosApiController.php';
require_once __DIR__ . '/../src/Controllers/Api/PdcApiController.php';

use App\Controllers\Api\ContratosApiController;
use App\Controllers\Api\ListadoActividadesApiController;
use App\Controllers\Api\PdcApiController;
use App\Security\CsrfTokenManager;

$failed = 0;
$completed = false;
$db = null;
$projectId = 987656;
$week = 1;
$prefix = 'lacp_manual_test';

register_shutdown_function(static function () use (&$completed, &$failed, &$db, $projectId, $prefix): void {
    if (!$completed) {
        if ($db instanceof Database) {
            lcpCleanup($db, $projectId, $prefix);
        }
        fwrite(STDERR, "LACP manual CRUD persistence ended before its assertions completed.\n");
        exit(1);
    }
});

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
ob_start();

function lcpPass(string $message): void
{
    echo "  PASS: {$message}\n";
}

function lcpFail(string $message): void
{
    global $failed;
    echo "  FAIL: {$message}\n";
    $failed++;
}

function lcpCleanup(Database $db, int $projectId, string $prefix): void
{
    foreach ([
        'papelera_pdc',
        'pdc',
        'actividad_programa_fuentes',
        'actividades',
        'programa_consolidado',
        'programa',
        'semanas_activas',
        'semi_auto_decisions',
        'semi_auto_suggestions',
        'semi_auto_runs',
        'contratos_trazabilidad',
    ] as $table) {
        $db->query("DELETE FROM {$table} WHERE project_id = ?", [$projectId]);
    }
    $db->query(
        "DELETE FROM general_dias_procesos_contratacion
         WHERE paqueteContratacion IN ('E2E MANUAL SI', 'E2E MANUAL MO', 'E2E PDC MANUAL')",
    );
    $db->query('DELETE FROM general_proyectos_procesos WHERE Id = ? OR Base_de_Datos = ?', [$projectId, $prefix]);
}

function lcpResetRequest(string $prefix, int $projectId, int $week, array $post = [], array $get = []): void
{
    $_SESSION['usuario'] = 'qa-lacp-manual';
    $_SESSION['permiso'] = 'A';
    $_SESSION['permiso_canonico'] = 'A';
    $_SESSION['db'] = $prefix;
    $_SESSION['project_id'] = $projectId;
    $_SESSION['proyecto'] = 'LACP Manual CRUD Test';
    $_SESSION['semana'] = $week;
    $_SESSION['pdcActivo'] = 1;
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_SERVER['HTTP_ACCEPT'] = 'application/json';
    $_POST = array_merge([
        'db' => $prefix,
        'semana' => $week,
        '_csrf_token' => CsrfTokenManager::generate('pdc_save'),
    ], $post);
    $_GET = $get;
}

function lcpCall(object $controller, string $method, string $prefix, int $projectId, int $week, array $post = [], array $get = [])
{
    lcpResetRequest($prefix, $projectId, $week, $post, $get);
    ob_start();
    $controller->$method();
    $raw = ob_get_clean();
    $decoded = json_decode((string) $raw, true);
    return $decoded ?? $raw;
}

function lcpAssertResponseOk($response, string $message): void
{
    if (is_array($response) && (($response['respuesta'] ?? '') === 'BIEN' || ($response['status'] ?? '') === 'success')) {
        lcpPass($message);
        return;
    }
    if ($response === 'OK') {
        lcpPass($message);
        return;
    }
    lcpFail($message . ' falló: ' . json_encode($response, JSON_UNESCAPED_UNICODE));
}

echo "=== LACP manual CRUD persistence ===\n";

try {
    $db = Database::getInstance();
    lcpCleanup($db, $projectId, $prefix);

    $db->query(
        "INSERT INTO general_proyectos_procesos (Id, Proyecto_Proceso, Base_de_Datos, Area, Activo, Acceso, pdcActivo)
         VALUES (?, 'LACP Manual CRUD Test', ?, 'Construccion', 1, 1, 1)",
        [$projectId, $prefix],
    );
    $db->query(
        "INSERT INTO semanas_activas (project_id, Id, Semana, Fecha_Inicio_Sem, Fecha_Fin_Sem)
         VALUES (?, 1, ?, '2030-02-01', '2030-02-07')",
        [$projectId, $week],
    );
    $db->query(
        "INSERT INTO programa
         (project_id, unique_id, Consecutivo, Id, Actividad, Titulo, Fecha_Inicio, Fecha_Fin)
         VALUES (?, 1001, 1001, '1.1', 'Actividad base manual', 0, '2030-02-10', '2030-02-12')",
        [$projectId],
    );
    $db->query(
        "INSERT INTO programa_consolidado
         (project_id, row_id, Consecutivo, Semana, unique_id, Consecutivo_en_Programa, Id, Actividad, Titulo, Fecha_Inicio, Fecha_Fin, Activa)
         VALUES (?, 1, 1, ?, 1001, 1001, '1.1', 'Actividad base manual', 0, '2030-02-10', '2030-02-12', 1)",
        [$projectId, $week],
    );

    $listado = new ListadoActividadesApiController();
    $createActivity = lcpCall($listado, 'save', $prefix, $projectId, $week, [
        'opcion' => 'registrar',
        'actividad' => 'E2E Manual Actividad',
        'descripcionActividad' => 'Actividad creada manualmente para prueba.',
        'fechaInicio' => '2030-02-10',
        'tipoContrato' => 'SI',
        'actividadInicio' => '1001',
    ]);
    lcpAssertResponseOk($createActivity, 'Listado permite crear actividad manual');

    $activity = $db->query(
        "SELECT * FROM actividades WHERE project_id = ? AND semanaActualizacion = ? AND actividad = ? LIMIT 1",
        [$projectId, $week, 'E2E Manual Actividad'],
    )->fetch(PDO::FETCH_ASSOC);
    $activityId = (int) ($activity['Id'] ?? 0);
    $activityId > 0 ? lcpPass('La actividad manual queda en BD') : lcpFail('La actividad manual no quedó en BD');

    $editActivity = lcpCall($listado, 'save', $prefix, $projectId, $week, [
        'opcion' => 'modificar',
        'Id' => $activityId,
        'Actividad' => 'E2E Manual Actividad Editada',
        'descripcionActividad' => 'Actividad editada manualmente para prueba.',
        'fechaInicio' => '2030-02-11',
        'tipoContrato' => 'SI',
        'actividadInicio' => '1001',
    ]);
    lcpAssertResponseOk($editActivity, 'Listado permite editar actividad manual');

    $listResponse = lcpCall($listado, 'list', $prefix, $projectId, $week);
    $listedActivity = false;
    foreach (($listResponse['data'] ?? []) as $row) {
        if ((int) ($row['Id'] ?? 0) === $activityId && ($row['actividad'] ?? '') === 'E2E Manual Actividad Editada') {
            $listedActivity = true;
            break;
        }
    }
    $listedActivity ? lcpPass('Listado recarga la actividad editada') : lcpFail('Listado no recargó la actividad editada');

    $contratos = new ContratosApiController();
    $contractPayload = [
        'opcion' => 'modificar',
        'Id' => $activityId,
        'actividadModificar' => 'E2E Manual Actividad Editada',
        'tipoContrato' => 'SI,MO',
        'paqueteSI1' => 'E2E MANUAL SI',
        'SI1' => '',
        'cantidadSI1' => '1',
        'paqueteMO1' => 'E2E MANUAL MO',
        'MO1' => '',
        'cantidadMO1' => '2',
    ];
    $contractMissingDurations = lcpCall($contratos, 'save', $prefix, $projectId, $week, $contractPayload);
    (is_array($contractMissingDurations) && ($contractMissingDurations['respuesta'] ?? '') === 'DURACIONES_REQUERIDAS')
        ? lcpPass('Contratos bloquea paquetes sin duraciones explicitas')
        : lcpFail('Contratos no bloqueo paquetes sin duraciones: ' . json_encode($contractMissingDurations, JSON_UNESCAPED_UNICODE));

    $durationPayload = [
        [
            'tipoPaquete' => 'Suministro e Instalación',
            'paqueteContratacion' => 'E2E MANUAL SI',
            'diasElaboracionPliegos' => 1,
            'diasEntregaPliegos' => 1,
            'diasReciboPropuestas' => 1,
            'diasCuadrosComparativos' => 1,
            'diasLegalizacionContrato' => 1,
            'diasFabricacion' => 1,
            'diasInsumosObra' => 1,
        ],
        [
            'tipoPaquete' => 'Mano de Obra',
            'paqueteContratacion' => 'E2E MANUAL MO',
            'diasElaboracionPliegos' => 2,
            'diasEntregaPliegos' => 2,
            'diasReciboPropuestas' => 2,
            'diasCuadrosComparativos' => 2,
            'diasLegalizacionContrato' => 2,
            'diasFabricacion' => 2,
            'diasInsumosObra' => 2,
        ],
    ];
    $durationSave = lcpCall($contratos, 'save', $prefix, $projectId, $week, [
        'opcion' => 'guardarDuracionesContratacion',
        'duraciones' => json_encode($durationPayload, JSON_UNESCAPED_UNICODE),
    ]);
    lcpAssertResponseOk($durationSave, 'Contratos guarda duraciones faltantes');

    $contractSave = lcpCall($contratos, 'save', $prefix, $projectId, $week, $contractPayload);
    lcpAssertResponseOk($contractSave, 'Contratos permite guardar paquetes manuales');

    $contractList = lcpCall($contratos, 'list', $prefix, $projectId, $week);
    $listedContract = false;
    foreach (($contractList['data'] ?? []) as $row) {
        if ((int) ($row['Id'] ?? 0) === $activityId
            && ($row['paqueteSI1'] ?? '') === 'E2E MANUAL SI'
            && ($row['paqueteMO1'] ?? '') === 'E2E MANUAL MO'
            && (int) ($row['cantidadMO1'] ?? 0) === 2) {
            $listedContract = true;
            break;
        }
    }
    $listedContract ? lcpPass('Contratos recarga paquetes manuales con cantidades') : lcpFail('Contratos no recargó paquetes manuales con cantidades');

    $traceRow = $db->query(
        "SELECT campos_cambiados FROM contratos_trazabilidad WHERE project_id = ? AND actividad_id = ? AND semana = ? ORDER BY id DESC LIMIT 1",
        [$projectId, $activityId, $week],
    )->fetch(PDO::FETCH_ASSOC);
    $traceFields = json_decode((string) ($traceRow['campos_cambiados'] ?? '[]'), true);
    (is_array($traceFields) && in_array('cantidadMO1', $traceFields, true))
        ? lcpPass('Contratos registra trazabilidad semanal de cantidades')
        : lcpFail('Contratos no registro trazabilidad de cantidades');

    $editActivityWithContract = lcpCall($listado, 'updateCell', $prefix, $projectId, $week, [
        'id' => $activityId,
        'prop' => 'fechaInicio',
        'value' => '2030-02-12',
    ]);
    lcpAssertResponseOk($editActivityWithContract, 'Listado permite cambiar fecha de actividad con contratos');

    $dateTraceRow = $db->query(
        "SELECT campos_cambiados FROM contratos_trazabilidad WHERE project_id = ? AND actividad_id = ? AND semana = ? ORDER BY id DESC LIMIT 1",
        [$projectId, $activityId, $week],
    )->fetch(PDO::FETCH_ASSOC);
    $dateTraceFields = json_decode((string) ($dateTraceRow['campos_cambiados'] ?? '[]'), true);
    (is_array($dateTraceFields) && in_array('fechaInicio', $dateTraceFields, true))
        ? lcpPass('Contratos registra trazabilidad semanal de fecha de inicio')
        : lcpFail('Contratos no registro trazabilidad de fecha de inicio');

    $pdc = new PdcApiController();
    $pdcCreate = lcpCall($pdc, 'save', $prefix, $projectId, $week, [
        'opcion' => 'guardar_actividad_pdc',
        'tipoPaquete' => 'Suministro e Instalación',
        'paqueteContratacion' => 'E2E PDC MANUAL',
        'contratos' => 'E2E Manual Actividad Editada',
        'fechaElaboracionPliegos' => '2030-01-20',
        'diasElaboracionPliegos' => '2',
        'fechaEntregaPliegos' => '2030-01-22',
        'diasEntregaPliegos' => '3',
        'fechaReciboPropuestas' => '2030-01-25',
        'diasReciboPropuestas' => '4',
        'fechaCuadrosComparativos' => '2030-01-29',
        'diasCuadrosComparativos' => '5',
        'fechaLegalizacionContrato' => '2030-02-03',
        'diasLegalizacionContrato' => '6',
        'fechaFabricacion' => '2030-02-09',
        'diasFabricacion' => '7',
        'fechaInsumosObra' => '2030-02-16',
        'diasInsumosObra' => '8',
        'fechaInicio' => '2030-02-20',
    ]);
    lcpAssertResponseOk($pdcCreate, 'PDC permite crear paquete manual');

    $pdcRow = $db->query(
        "SELECT * FROM pdc WHERE project_id = ? AND semana = ? AND paqueteContratacion = ? LIMIT 1",
        [$projectId, $week, 'E2E PDC MANUAL'],
    )->fetch(PDO::FETCH_ASSOC);
    $pdcId = (int) ($pdcRow['consecutivo'] ?? 0);
    $pdcId > 0 ? lcpPass('El paquete PDC manual queda en BD') : lcpFail('El paquete PDC manual no quedó en BD');

    $pdcEdit = lcpCall($pdc, 'save', $prefix, $projectId, $week, [
        'opcion' => 'modificar',
        'Id' => $pdcId,
        'idProveedorExistente' => '',
        'numeroContrato' => 'E2E-001',
        'aplicaPolizas' => '1',
        'fechaVencimientoPolizas' => '2030-03-01',
        'valorPresupuesto' => '1000',
        'valorPrimeraNegociacion' => '950',
        'valorAdjudicado' => '900',
        'valorAnticipo' => '100',
        'valorReclamado' => '0',
        'valorDevoluciones' => '0',
        'diasElaboracionPliegos' => '2',
        'diasEntregaPliegos' => '3',
        'diasReciboPropuestas' => '4',
        'diasCuadrosComparativos' => '5',
        'diasLegalizacionContrato' => '6',
        'diasFabricacion' => '7',
        'diasInsumosObra' => '8',
        'fechaElaboracionPliegos' => '2030-01-20',
        'fechaEntregaPliegos' => '2030-01-22',
        'fechaReciboPropuestas' => '2030-01-25',
        'fechaCuadrosComparativos' => '2030-01-29',
        'fechaLegalizacionContrato' => '2030-02-03',
        'fechaFabricacion' => '2030-02-09',
        'fechaInsumosObra' => '2030-02-16',
        'fechaInicioProyectadaContrato' => '2030-02-20',
        'fechaRealElaboracionPliegos' => '2030-01-21',
        'fechaRealEntregaPliegos' => '',
        'fechaRealReciboPropuestas' => '',
        'fechaRealCuadrosComparativos' => '',
        'fechaRealLegalizacionContrato' => '',
        'fechaRealFabricacion' => '',
        'fechaRealInsumosObra' => '',
        'fechaRealInicioProyectadaContrato' => '',
        'observacionesContrato' => 'Observación manual E2E',
        'estadoProceso' => 'Proceso de contratación iniciado',
    ]);
    lcpAssertResponseOk($pdcEdit, 'PDC permite editar paquete manual');

    $pdcList = lcpCall($pdc, 'list', $prefix, $projectId, $week);
    $listedPdc = false;
    foreach (($pdcList['data'] ?? []) as $row) {
        if ((int) ($row['consecutivo'] ?? 0) === $pdcId
            && ($row['numeroContrato'] ?? '') === 'E2E-001'
            && ($row['observacionesContrato'] ?? '') === 'Observación manual E2E') {
            $listedPdc = true;
            break;
        }
    }
    $listedPdc ? lcpPass('PDC recarga la edición manual') : lcpFail('PDC no recargó la edición manual');
} catch (Throwable $e) {
    lcpFail($e->getMessage());
} finally {
    if (isset($db)) {
        lcpCleanup($db, $projectId, $prefix);
    }
}

echo "=== LACP manual CRUD persistence: {$failed} failed ===\n";
$completed = true;
exit($failed === 0 ? 0 : 1);
