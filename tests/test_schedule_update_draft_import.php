<?php

declare(strict_types=1);
// @requiere: db


use Admin\Models\Project;
use App\Controllers\Api\GeneralApiController;
use App\Security\CsrfTokenManager;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/support/SessionScopeHarness.php';

if (!defined('PROJECT_ROOT')) {
    define('PROJECT_ROOT', dirname(__DIR__));
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$database = Database::getInstance();
$projectModel = new Project($database);
$projectId = 0;
$fixtureOverride = getenv('LPS_SCHEDULE_IMPORT_XLSX');
$ownsXlsx = !is_string($fixtureOverride) || $fixtureOverride === '';
$xlsxPath = $ownsXlsx ? tempnam(sys_get_temp_dir(), 'lps_schedule_update_') : $fixtureOverride;
$expectedDraftRows = 2;
$failures = 0;
$originalSession = $_SESSION;
$originalGet = $_GET;
$originalPost = $_POST;
$originalFiles = $_FILES;
$originalServer = $_SERVER;

function scheduleUpdateAssert(bool $condition, string $message): void
{
    global $failures;

    echo ($condition ? 'PASS: ' : 'FAIL: ') . $message . PHP_EOL;
    if (!$condition) {
        $failures++;
    }
}

function runScheduleImport(string $dbPrefix, string $xlsxPath, string $baselineStart): array
{
    $_GET = [
        'db' => $dbPrefix,
        'semana' => 1,
        'f_inicio_sem' => $baselineStart,
    ];
    $_FILES = [
        'archivoExcel' => [
            'name' => 'cronograma-da-porto.xlsx',
            'type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'tmp_name' => $xlsxPath,
            'error' => UPLOAD_ERR_OK,
            'size' => filesize($xlsxPath),
        ],
    ];
    $_SERVER['HTTP_X_CSRF_TOKEN'] = CsrfTokenManager::generate('programa_general_save');

    http_response_code(200);
    ob_start();
    (new GeneralApiController())->importExcel();
    $payload = (string) ob_get_clean();

    return [json_decode($payload, true), $payload];
}

try {
    if ($xlsxPath === false) {
        throw new RuntimeException('No se pudo reservar el XLSX temporal.');
    }

    $suffix = bin2hex(random_bytes(4));
    $projectName = "IT Schedule Draft {$suffix}";
    $baselineStart = '2026-07-13';
    $baselineEnd = '2027-12-31';
    $created = $projectModel->create([
        'nombre' => $projectName,
        'area' => 'Construccion',
        'activo' => 1,
        'acceso' => 1,
        'pdc_activo' => 0,
        'fecha_inicio_lb' => $baselineStart,
        'fecha_fin_lb' => $baselineEnd,
        'costo_retraso' => 0,
        'url_cambios' => null,
    ]);
    if (!$created) {
        throw new RuntimeException('No se pudo crear el proyecto temporal.');
    }

    $project = $database->query(
        'SELECT Id, Base_de_Datos FROM general_proyectos_procesos WHERE Proyecto_Proceso = ? LIMIT 1',
        [$projectName],
    )->fetch(PDO::FETCH_ASSOC);
    $projectId = (int) ($project['Id'] ?? 0);
    $dbPrefix = (string) ($project['Base_de_Datos'] ?? '');
    if ($projectId <= 0 || $dbPrefix === '') {
        throw new RuntimeException('El proyecto temporal no quedó identificable.');
    }

    if ($ownsXlsx) {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->fromArray([
            ['Número de Esquema', 'Nombre de tarea', 'Resumen', 'Comienzo', 'Fin', 'Tareas críticas'],
            ['1', 'CRONOGRAMA DE PRUEBA', 'Sí', '2026-07-13', '2027-12-31', 'Sí'],
            ['1.1', 'ACTIVIDAD DE PRUEBA', 'No', '2026-07-13', '2026-07-19', 'No'],
        ]);
        (new Xlsx($spreadsheet))->save($xlsxPath);
        $spreadsheet->disconnectWorksheets();
    } else {
        $fixtureRows = IOFactory::load($xlsxPath)->getActiveSheet()->toArray();
        array_shift($fixtureRows);
        $expectedDraftRows = count(array_filter(
            $fixtureRows,
            static fn(array $row): bool => trim((string) ($row[0] ?? '')) !== '',
        ));
    }

    $userRow = $database->query(
        'SELECT id, usuario FROM general_usuarios WHERE activo = 1 ORDER BY (usuario = ?) DESC LIMIT 1',
        ['jbenitez'],
    )->fetch(PDO::FETCH_ASSOC);
    $activeUser = (string) ($userRow['usuario'] ?? '');
    $activeUserId = (int) ($userRow['id'] ?? 0);
    if ($activeUser === '' || $activeUserId <= 0) {
        throw new RuntimeException('No hay un usuario activo para la sesión del test.');
    }

    // El alcance se resuelve contra `project_members`: sin membresía real, el escenario no existe
    // en producción y el resolver —con razón— no devuelve nada.
    $database->query(
        'INSERT INTO project_members (project_id, user_id, role) VALUES (?, ?, ?)',
        [$projectId, $activeUserId, 'A'],
    );

    $_SESSION = [
        'usuario' => $activeUser,
        'permiso' => 'A',
        'permiso_canonico' => 'A',
        'proyecto' => $projectName,
        'db' => $dbPrefix,
        'project_id' => $projectId,
        'semana' => 1,
        'area' => 'Construccion',
        'timeout' => time(),
    ];
    $_SERVER['HTTP_ACCEPT'] = 'application/json';
    $_SERVER['HTTP_X_AIA_EXPECT_JSON'] = '1';

    // El controlador se invoca directamente, sin SessionMiddleware: el alcance hay que enlazarlo
    // aquí, con el mismo resolver que usa producción.
    SessionScopeHarness::requireFromSession($database);

    [$firstDecoded, $firstPayload] = runScheduleImport($dbPrefix, $xlsxPath, $baselineStart);
    [$secondDecoded, $secondPayload] = runScheduleImport($dbPrefix, $xlsxPath, $baselineStart);

    $draftRows = (int) $database->query(
        'SELECT COUNT(*) FROM programa_consolidado WHERE project_id = ? AND Semana = 2',
        [$projectId],
    )->fetchColumn();
    $activeWeek = (int) $database->query(
        'SELECT COUNT(*) FROM semanas_activas WHERE project_id = ? AND Semana = 2',
        [$projectId],
    )->fetchColumn();
    $foreignKeyChecks = (int) $database->query('SELECT @@FOREIGN_KEY_CHECKS')->fetchColumn();

    // `nueva_semana.php` exige su propio token por POST (`lps_week_admin`), distinto del que usa
    // importExcel por cabecera. Sin él corta con `exit` y el test moría sin llegar a sus asserts.
    $_GET = ['db' => $dbPrefix];
    $_POST = [
        'opcion' => 'nueva_sem',
        'f_inicio_sem' => '2026-07-20',
        '_csrf_token' => CsrfTokenManager::generate('lps_week_admin'),
    ];
    $dbInstance = $database;
    ob_start();
    require PROJECT_ROOT . '/src/Legacy/nueva_semana.php';
    $newWeekPayload = (string) ob_get_clean();
    $newWeekCreated = (int) $database->query(
        'SELECT COUNT(*) FROM semanas_activas WHERE project_id = ? AND Semana = 2',
        [$projectId],
    )->fetchColumn();
    $rowsAfterActivation = (int) $database->query(
        'SELECT COUNT(*) FROM programa_consolidado WHERE project_id = ? AND Semana = 2',
        [$projectId],
    )->fetchColumn();

    scheduleUpdateAssert(
        is_array($firstDecoded) && ($firstDecoded['respuesta'] ?? null) === 'BIEN',
        'Given semana 1 activa, when se importa el cronograma base, then la importación responde BIEN; payload=' . $firstPayload,
    );
    scheduleUpdateAssert(
        is_array($secondDecoded) && ($secondDecoded['respuesta'] ?? null) === 'BIEN',
        'Given semana 1 activa, when se importa una actualización, then el borrador de semana 2 responde BIEN; payload=' . $secondPayload,
    );
    scheduleUpdateAssert(
        $draftRows === $expectedDraftRows,
        "El borrador conserva las {$expectedDraftRows} filas importadas en semana 2.",
    );
    scheduleUpdateAssert($activeWeek === 0, 'La actualización permanece borrador hasta crear la semana 2.');
    scheduleUpdateAssert($foreignKeyChecks === 1, 'La conexión restaura FOREIGN_KEY_CHECKS después de importar.');
    scheduleUpdateAssert(
        $newWeekCreated === 1,
        'Nueva Semana activa el borrador como semana 2; payload=' . $newWeekPayload,
    );
    scheduleUpdateAssert(
        $rowsAfterActivation === $expectedDraftRows,
        'Nueva Semana conserva las filas del cronograma actualizado.',
    );
} catch (Throwable $e) {
    scheduleUpdateAssert(false, 'Excepción inesperada: ' . $e->getMessage());
} finally {
    if ($projectId > 0) {
        try {
            $projectModel->delete($projectId);
        } catch (Throwable $cleanupError) {
            scheduleUpdateAssert(false, 'No se pudo limpiar el proyecto temporal: ' . $cleanupError->getMessage());
        }
    }

    if ($ownsXlsx && is_string($xlsxPath) && is_file($xlsxPath)) {
        @unlink($xlsxPath);
    }

    $database->setProjectContext(null);
    $_SESSION = $originalSession;
    $_GET = $originalGet;
    $_POST = $originalPost;
    $_FILES = $originalFiles;
    $_SERVER = $originalServer;
}

if ($failures > 0) {
    echo "=== Schedule update draft import: FAIL ===\n";
    exit(1);
}

echo "=== Schedule update draft import: OK ===\n";
