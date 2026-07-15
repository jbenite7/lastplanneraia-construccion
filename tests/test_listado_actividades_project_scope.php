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

function listadoScopeFail(string $message): void
{
    global $failed;
    echo "FAIL: {$message}\n";
    $failed++;
}

function listadoScopePass(string $message): void
{
    echo "PASS: {$message}\n";
}

$projectA = 73;
$projectB = 58;
$week = 9901;
$consecutivo = 910101;

$projectRows = $db->query(
    'SELECT ID, Proyecto_Proceso, Base_de_Datos FROM general_proyectos_procesos WHERE ID IN (?, ?)',
    [$projectA, $projectB],
)->fetchAll(PDO::FETCH_ASSOC);

if (count($projectRows) < 2) {
    echo "SKIP: No existen dos proyectos base para validar alcance.\n";
    exit(0);
}

try {
    $db->beginTransaction();

    foreach ([$projectA, $projectB] as $projectId) {
        $db->query('DELETE FROM actividades WHERE project_id = ? AND semanaActualizacion = ?', [$projectId, $week]);
        $db->query('DELETE FROM programa_consolidado WHERE project_id = ? AND Semana = ?', [$projectId, $week]);
        $db->query('DELETE FROM programa WHERE project_id = ? AND Consecutivo = ?', [$projectId, $consecutivo]);
        $db->query('DELETE FROM semanas_activas WHERE project_id = ? AND Semana = ?', [$projectId, $week]);
    }

    $db->query(
        'INSERT INTO semanas_activas (project_id, Id, Semana, Fecha_Inicio_Sem, Fecha_Fin_Sem) VALUES (?, ?, ?, ?, ?)',
        [$projectA, $week, $week, '2030-01-01', '2030-01-07'],
    );
    $db->query(
        'INSERT INTO semanas_activas (project_id, Id, Semana, Fecha_Inicio_Sem, Fecha_Fin_Sem) VALUES (?, ?, ?, ?, ?)',
        [$projectB, $week, $week, '2030-01-01', '2030-01-07'],
    );
    $db->query(
        'INSERT INTO semanas_activas (project_id, Id, Semana, Fecha_Inicio_Sem, Fecha_Fin_Sem) VALUES (?, ?, ?, ?, ?)',
        [$projectA, $week + 1, $week + 1, '2030-01-08', '2030-01-14'],
    );

    $db->query(
        'INSERT INTO programa (project_id, Consecutivo, Id, Actividad, Titulo, Fecha_Inicio, Fecha_Fin) VALUES (?, ?, ?, ?, ?, ?, ?)',
        [$projectA, $consecutivo, 'A.1', '<b>PROYECTO A AISLADO</b><br>', 0, '2030-01-03', '2030-01-04'],
    );
    $db->query(
        'INSERT INTO programa (project_id, Consecutivo, Id, Actividad, Titulo, Fecha_Inicio, Fecha_Fin) VALUES (?, ?, ?, ?, ?, ?, ?)',
        [$projectB, $consecutivo, 'B.1', 'PROYECTO B NO DEBE APARECER', 0, '2030-01-01', '2030-01-02'],
    );

    $db->query(
        'INSERT INTO programa_consolidado (project_id, Consecutivo, Semana, Consecutivo_en_Programa, Id, Actividad, Titulo, Fecha_Inicio, Fecha_Fin) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
        [$projectA, $consecutivo, $week, $consecutivo, 'A.1', '<b>PROYECTO A AISLADO</b><br>', 0, '2030-01-03', '2030-01-04'],
    );
    $db->query(
        'INSERT INTO programa_consolidado (project_id, Consecutivo, Semana, Consecutivo_en_Programa, Id, Actividad, Titulo, Fecha_Inicio, Fecha_Fin) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
        [$projectB, $consecutivo, $week, $consecutivo, 'B.1', 'PROYECTO B NO DEBE APARECER', 0, '2030-01-01', '2030-01-02'],
    );

    $db->query(
        'INSERT INTO actividades (project_id, Id, codigo, actividad, descripcionActividad, actividadInicio, fechaInicio, tipoContrato, semanaActualizacion) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
        [$projectA, 9901, 1, 'ACTIVIDAD A', 'DESCRIPCION A', (string) $consecutivo, '2030-01-03', 'S', $week],
    );

    $_SESSION['usuario'] = 'jbenitez';
    $_SESSION['permiso'] = 'A';
    $_SESSION['permiso_canonico'] = 'A';
    $_SESSION['proyecto'] = 'Da Porto';
    $_SESSION['db'] = 'da_porto';
    $_SESSION['project_id'] = $projectA;
    $_SESSION['semana'] = $week;

    $_GET = [];
    $_POST = [];

    ob_start();
    (new ListadoActividadesApiController())->list();
    $payload = ob_get_clean();
    $decoded = json_decode($payload, true);

    if (!is_array($decoded) || !isset($decoded['data'][0]['nombreActividadInicio'])) {
        listadoScopeFail('La respuesta del listado no tuvo la forma esperada.');
    } else {
        $visibleName = (string) $decoded['data'][0]['nombreActividadInicio'];
        str_contains($visibleName, 'PROYECTO A AISLADO')
            ? listadoScopePass('El listado usa el programa del proyecto activo.')
            : listadoScopeFail('El listado no devolvió el programa del proyecto activo.');

        !str_contains($visibleName, 'PROYECTO B NO DEBE APARECER')
            ? listadoScopePass('El listado no cruza textos del otro proyecto.')
            : listadoScopeFail('El listado mezcló el programa de otro proyecto.');

        !preg_match('/<[^>]+>/', $visibleName)
            ? listadoScopePass('La API entrega el inicio en obra como texto seguro.')
            : listadoScopeFail('La API expuso HTML crudo en el inicio en obra.');
    }
} catch (Throwable $e) {
    listadoScopeFail($e->getMessage());
} finally {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
}

echo $failed === 0 ? "=== Listado actividades scope: OK ===\n" : "=== Listado actividades scope: FAIL ===\n";
exit($failed === 0 ? 0 : 1);
