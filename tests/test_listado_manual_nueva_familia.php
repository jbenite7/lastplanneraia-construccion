<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/Database.php';

use App\Controllers\Api\ListadoActividadesApiController;

if (!defined('PROJECT_ROOT')) {
    define('PROJECT_ROOT', dirname(__DIR__));
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$db = Database::getInstance();
$failed = 0;

function fail(string $message): void
{
    global $failed;
    echo "FAIL: {$message}\n";
    $failed++;
}

function pass(string $message): void
{
    echo "PASS: {$message}\n";
}

$projectId = 73;
$week = 9911;
$consecutivo = 911101;
$familiaNombre = 'TEST_REGISTRAR_FAMILIA_' . time();
$descripcion = 'Familia creada via registrar() con tipoContrato';
$fechaInicio = '2030-03-04';
$tipoContrato = 'MO';

try {
    $db->beginTransaction();

    $db->query('DELETE FROM actividades WHERE project_id = ? AND semanaActualizacion = ?', [$projectId, $week]);
    $db->query('DELETE FROM programa_consolidado WHERE project_id = ? AND Semana = ?', [$projectId, $week]);
    $db->query('DELETE FROM programa WHERE project_id = ? AND Consecutivo = ?', [$projectId, $consecutivo]);
    $db->query('DELETE FROM semanas_activas WHERE project_id = ? AND Semana = ?', [$projectId, $week]);

    $db->query(
        'INSERT INTO semanas_activas (project_id, Id, Semana, Fecha_Inicio_Sem, Fecha_Fin_Sem) VALUES (?, ?, ?, ?, ?)',
        [$projectId, $week, $week, '2030-03-01', '2030-03-07'],
    );
    $db->query(
        'INSERT INTO programa (project_id, Consecutivo, Id, Actividad, Titulo, Fecha_Inicio, Fecha_Fin) VALUES (?, ?, ?, ?, ?, ?, ?)',
        [$projectId, $consecutivo, 'X.1', 'Actividad de prueba para registrar', 0, $fechaInicio, '2030-03-05'],
    );
    $db->query(
        'INSERT INTO programa_consolidado (project_id, Consecutivo, Semana, Consecutivo_en_Programa, Id, Actividad, Titulo, Fecha_Inicio, Fecha_Fin) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
        [$projectId, $consecutivo, $week, $consecutivo, 'X.1', 'Actividad de prueba para registrar', 0, $fechaInicio, '2030-03-05'],
    );

    $_SESSION['usuario'] = 'jbenitez';
    $_SESSION['permiso'] = 'A';
    $_SESSION['permiso_canonico'] = 'A';
    $_SESSION['proyecto'] = 'Da Porto';
    $_SESSION['db'] = 'da_porto';
    $_SESSION['project_id'] = $projectId;
    $_SESSION['semana'] = $week;

    $_GET = ['db' => 'da_porto'];
    $_POST = [
        'opcion' => 'registrar',
        'Id' => '',
        'codigo' => '',
        'actividad' => $familiaNombre,
        'descripcionActividad' => $descripcion,
        'actividadInicio' => (string) $consecutivo,
        'fechaInicio' => $fechaInicio,
        'tipoContrato' => $tipoContrato,
    ];

    ob_start();
    (new ListadoActividadesApiController())->save();
    $payload = ob_get_clean();
    $decoded = json_decode($payload, true);

    if (!is_array($decoded) || ($decoded['respuesta'] ?? '') !== 'BIEN') {
        fail('registrar() no devolvio BIEN. payload=' . $payload);
    } else {
        pass('registrar() devolvio BIEN con tipoContrato=' . $tipoContrato);
    }

    $row = $db->query(
        'SELECT actividad, descripcionActividad, actividadInicio, fechaInicio, tipoContrato, project_id, semanaActualizacion FROM actividades WHERE project_id = ? AND semanaActualizacion = ? AND actividad = ?',
        [$projectId, $week, $familiaNombre],
    )->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        fail('La familia no se persistio en actividades.');
    } else {
        $row['tipoContrato'] === $tipoContrato
            ? pass('tipoContrato persistido: ' . $row['tipoContrato'])
            : fail('tipoContrato incorrecto: ' . var_export($row['tipoContrato'], true));
        $row['descripcionActividad'] === $descripcion
            ? pass('descripcionActividad persistida')
            : fail('descripcionActividad incorrecta');
        $row['fechaInicio'] === $fechaInicio
            ? pass('fechaInicio persistida: ' . $row['fechaInicio'])
            : fail('fechaInicio incorrecta: ' . var_export($row['fechaInicio'], true));
        (int) $row['project_id'] === $projectId
            ? pass('project_id persistido: ' . $row['project_id'])
            : fail('project_id incorrecto');
        (int) $row['semanaActualizacion'] === $week
            ? pass('semanaActualizacion persistida: ' . $row['semanaActualizacion'])
            : fail('semanaActualizacion incorrecta');
    }

    // Verificar que la duplicacion devuelve EXISTE
    $_POST['actividad'] = $familiaNombre;
    ob_start();
    (new ListadoActividadesApiController())->save();
    $payload2 = ob_get_clean();
    $decoded2 = json_decode($payload2, true);
    ($decoded2['respuesta'] ?? '') === 'EXISTE'
        ? pass('Duplicado devuelve EXISTE')
        : fail('Duplicado no devolvio EXISTE: ' . $payload2);

    // Verificar que sin tipoContrato devuelve VACIO
    $_POST['actividad'] = $familiaNombre . '_sin_tipo';
    $_POST['tipoContrato'] = '';
    ob_start();
    (new ListadoActividadesApiController())->save();
    $payload3 = ob_get_clean();
    $decoded3 = json_decode($payload3, true);
    ($decoded3['respuesta'] ?? '') === 'VACIO'
        ? pass('Sin tipoContrato devuelve VACIO')
        : fail('Sin tipoContrato no devolvio VACIO: ' . $payload3);
} catch (Throwable $e) {
    fail('Excepcion: ' . $e->getMessage());
} finally {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
}

echo $failed === 0 ? "=== Manual nueva familia E2E: OK ===\n" : "=== Manual nueva familia E2E: FAIL ===\n";
exit($failed === 0 ? 0 : 1);
