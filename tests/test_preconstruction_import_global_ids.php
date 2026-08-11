<?php

declare(strict_types=1);
// @requiere: db


use Admin\Models\Project;
use App\Controllers\Api\GeneralApiController;
use App\Security\CsrfTokenManager;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

require_once __DIR__ . '/../vendor/autoload.php';

if (!defined('PROJECT_ROOT')) {
    define('PROJECT_ROOT', dirname(__DIR__));
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$db = Database::getInstance();
$projectModel = new Project($db);
$projectId = 0;
$xlsxPath = tempnam(sys_get_temp_dir(), 'lps_pc_import_');
$failures = 0;
$originalSession = $_SESSION;
$originalGet = $_GET;
$originalFiles = $_FILES;
$originalServer = $_SERVER;

function assertImport(bool $condition, string $message): void
{
    global $failures;

    echo ($condition ? 'PASS: ' : 'FAIL: ') . $message . PHP_EOL;
    if (!$condition) {
        $failures++;
    }
}

try {
    if ($xlsxPath === false) {
        throw new RuntimeException('No se pudo reservar el XLSX temporal.');
    }

    $suffix = bin2hex(random_bytes(4));
    $projectName = "IT PC {$suffix}";
    $baselineStart = date('Y-m-d');
    $baselineEnd = date('Y-m-d', strtotime($baselineStart . ' +30 days'));
    $created = $projectModel->create([
        'nombre' => $projectName,
        'area' => 'Pre-Construccion',
        'activo' => 1,
        'acceso' => 1,
        'pdc_activo' => 0,
        'fecha_inicio_lb' => $baselineStart,
        'fecha_fin_lb' => $baselineEnd,
        'costo_retraso' => 0,
        'url_cambios' => null,
        'pc_restr_2_nombre' => null,
        'pc_restr_3_nombre' => null,
        'pc_restr_4_nombre' => null,
    ]);
    if (!$created) {
        throw new RuntimeException('No se pudo crear el proyecto temporal.');
    }

    $project = $db->query(
        'SELECT Id, Base_de_Datos FROM general_proyectos_procesos WHERE Proyecto_Proceso = ? LIMIT 1',
        [$projectName],
    )->fetch(PDO::FETCH_ASSOC);
    $projectId = (int) ($project['Id'] ?? 0);
    $dbPrefix = (string) ($project['Base_de_Datos'] ?? '');
    if ($projectId <= 0 || $dbPrefix === '') {
        throw new RuntimeException('El proyecto temporal no quedó identificable.');
    }

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->fromArray([
        ['Número de esquema', 'Nombre de tarea', 'Unique ID', 'Resumen', 'Comienzo', 'Fin', 'Tareas críticas'],
        ['1', 'Actividad mínima importada', 101, 'No', '2026-07-09', '2026-07-10', 'Sí'],
    ]);
    $sheet->getStyle('E2:F2')->getNumberFormat()->setFormatCode('yyyy-mm-dd');
    (new Xlsx($spreadsheet))->save($xlsxPath);
    $spreadsheet->disconnectWorksheets();

    $activeUser = $db->query(
        'SELECT usuario FROM general_usuarios WHERE activo = 1 ORDER BY (usuario = ?) DESC LIMIT 1',
        ['jbenitez'],
    )->fetchColumn();
    if (!is_string($activeUser) || $activeUser === '') {
        throw new RuntimeException('No hay un usuario activo para la sesión del test.');
    }

    $_SESSION = [
        'usuario' => $activeUser,
        'permiso' => 'A',
        'permiso_canonico' => 'A',
        'proyecto' => $projectName,
        'db' => $dbPrefix,
        'project_id' => $projectId,
        'semana' => 1,
        'area' => 'Pre-Construccion',
        'timeout' => time(),
    ];
    $_GET = [
        'db' => $dbPrefix,
        'semana' => 1,
        'f_inicio_sem' => $baselineStart,
    ];
    $_FILES = [
        'archivoExcel' => [
            'name' => 'cronograma-minimo.xlsx',
            'type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'tmp_name' => $xlsxPath,
            'error' => UPLOAD_ERR_OK,
            'size' => filesize($xlsxPath),
        ],
    ];
    $_SERVER['HTTP_ACCEPT'] = 'application/json';
    $_SERVER['HTTP_X_AIA_EXPECT_JSON'] = '1';
    $_SERVER['HTTP_X_CSRF_TOKEN'] = CsrfTokenManager::generate('programa_general_save');

    http_response_code(200);
    ob_start();
    (new GeneralApiController())->importExcel();
    $payload = (string) ob_get_clean();
    $decoded = json_decode($payload, true);
    $response = is_array($decoded) ? ($decoded['respuesta'] ?? null) : null;

    assertImport(
        $response === 'BIEN',
        'importExcel() debe responder BIEN; payload=' . $payload,
    );

    $row = $db->query(
        'SELECT row_id, Consecutivo FROM programa_consolidado WHERE project_id = ? AND Semana = 1 AND unique_id = ? LIMIT 1',
        [$projectId, 101],
    )->fetch(PDO::FETCH_ASSOC);
    assertImport(is_array($row), 'programa_consolidado conserva la fila importada');

    if (is_array($row)) {
        assertImport((int) $row['row_id'] > 0, 'row_id queda asignado');
        assertImport((int) $row['Consecutivo'] > 0, 'Consecutivo queda asignado');
    }
} catch (Throwable $e) {
    assertImport(false, 'Excepción inesperada: ' . $e->getMessage());
} finally {
    if ($projectId > 0) {
        try {
            $projectModel->delete($projectId);
        } catch (Throwable $cleanupError) {
            assertImport(false, 'No se pudo limpiar el proyecto temporal: ' . $cleanupError->getMessage());
        }
    }

    if (is_string($xlsxPath) && is_file($xlsxPath)) {
        @unlink($xlsxPath);
    }

    $db->setProjectContext(null);
    $_SESSION = $originalSession;
    $_GET = $originalGet;
    $_FILES = $originalFiles;
    $_SERVER = $originalServer;
}

if ($failures > 0) {
    echo "=== Preconstruction import global IDs: FAIL ===\n";
    exit(1);
}

echo "=== Preconstruction import global IDs: OK ===\n";
