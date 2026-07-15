<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Controllers\Api\ListadoActividadesApiController;
use App\Security\RbacCatalog;
use App\Security\RbacService;

if (!defined('PROJECT_ROOT')) {
    define('PROJECT_ROOT', dirname(__DIR__));
}
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
ob_start();

$failures = [];

function listadoBackendAssert(bool $condition, string $message): void
{
    global $failures;
    if (!$condition) {
        $failures[] = $message;
        fwrite(STDERR, "FAIL: {$message}\n");
        return;
    }

    echo "PASS: {$message}\n";
}

function listadoBackendCall(string $method, array $post, array $files = [], int $week = 9937): array
{
    $_SESSION = [
        'usuario' => 'qa-listado-backend',
        'permiso' => 'A',
        'permiso_canonico' => 'A',
        'proyecto' => 'Da Porto',
        'db' => 'da_porto',
        'project_id' => 73,
        'semana' => $week,
    ];
    $_GET = ['db' => 'da_porto', 'semana' => (string) $week];
    $_POST = $post;
    $_FILES = $files;
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_SERVER['HTTP_ACCEPT'] = 'application/json';
    http_response_code(200);

    ob_start();
    (new ListadoActividadesApiController())->{$method}();
    $raw = (string) ob_get_clean();
    $decoded = json_decode($raw, true);

    listadoBackendAssert(is_array($decoded), "{$method} devuelve JSON valido: {$raw}");
    if (is_array($decoded)) {
        $decoded['_status'] = http_response_code();
    }
    return is_array($decoded) ? $decoded : [];
}

function listadoCsvFixture(string $contents): array
{
    $path = tempnam(sys_get_temp_dir(), 'listado-csv-');
    file_put_contents($path, $contents);
    return [$path, [
        'name' => 'familias.csv', 'tmp_name' => $path,
        'error' => UPLOAD_ERR_OK, 'size' => filesize($path), 'type' => 'text/csv',
    ]];
}

$db = Database::getInstance();
$fallback = RbacCatalog::fallbackPermissionsByRole();
$viewerPermissions = $fallback['V'] ?? [];
$rbac = new RbacService($db);

listadoBackendAssert(
    in_array('lps.listado_actividades.ver', $viewerPermissions, true),
    'V conserva permiso de consulta del Listado.',
);
listadoBackendAssert(
    !in_array('lps.listado_actividades.editar', $viewerPermissions, true),
    'V no recibe permiso de edicion del Listado en el catalogo.',
);
listadoBackendAssert(
    !$rbac->can('lps.listado_actividades.editar', 'V'),
    'El servicio RBAC rechaza edicion de Listado para V.',
);

try {
    $db->beginTransaction();
    $db->query(
        "INSERT INTO rbac_role_permissions (role_code, permission_key, allowed, source)
         VALUES ('V', 'lps.listado_actividades.editar', 1, 'test')
         ON DUPLICATE KEY UPDATE allowed = 1, source = 'test'",
    );
    listadoBackendAssert(
        !(new RbacService($db))->can('lps.listado_actividades.editar', 'V'),
        'V sigue readOnly aunque exista una concesion editable residual en base de datos.',
    );
} finally {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
}

$csvWeek = 9938;
$csvPaths = [];
try {
    $db->query('DELETE FROM actividades WHERE project_id = 73 AND semanaActualizacion = ?', [$csvWeek]);
    $db->query(
        "INSERT INTO actividades (project_id, Id, codigo, actividad, descripcionActividad, semanaActualizacion)
         VALUES (73, 993801, 1, 'CSV_SENTINEL', 'Debe sobrevivir errores', ?)",
        [$csvWeek],
    );
    [$emptyPath, $emptyFile] = listadoCsvFixture("actividad;descripcionActividad\n");
    $csvPaths[] = $emptyPath;
    $emptyResponse = listadoBackendCall('save', ['opcion' => 'cargarExcel'], ['archivoExcel' => $emptyFile], $csvWeek);
    listadoBackendAssert(($emptyResponse['respuesta'] ?? '') !== 'BIEN', 'CSV sin filas utiles se rechaza.');
    $sentinelCount = (int) $db->query(
        "SELECT COUNT(*) FROM actividades WHERE project_id = 73 AND semanaActualizacion = ? AND actividad = 'CSV_SENTINEL'",
        [$csvWeek],
    )->fetchColumn();
    listadoBackendAssert($sentinelCount === 1, 'CSV invalido no borra registros existentes.');
    [$badPath, $badFile] = listadoCsvFixture("familia;detalle\nUno;Dos\n");
    $csvPaths[] = $badPath;
    $badResponse = listadoBackendCall('save', ['opcion' => 'cargarExcel'], ['archivoExcel' => $badFile], $csvWeek);
    listadoBackendAssert(($badResponse['respuesta'] ?? '') !== 'BIEN', 'CSV con cabeceras desconocidas se rechaza.');

    [$validPath, $validFile] = listadoCsvFixture(
        "actividad;descripcionActividad\nFamilia CSV Uno;Descripcion uno\nFamilia CSV Dos;Descripcion dos\n",
    );
    $csvPaths[] = $validPath;
    $validResponse = listadoBackendCall('save', ['opcion' => 'cargarExcel'], ['archivoExcel' => $validFile], $csvWeek);
    listadoBackendAssert(($validResponse['respuesta'] ?? '') === 'BIEN', 'CSV valido se importa.');
    $csvRows = $db->query(
        'SELECT actividad, descripcionActividad FROM actividades WHERE project_id = 73 AND semanaActualizacion = ? ORDER BY codigo',
        [$csvWeek],
    )->fetchAll(PDO::FETCH_ASSOC);
    listadoBackendAssert(count($csvRows) === 2, 'CSV valido reemplaza el listado por sus filas utiles.');
    listadoBackendAssert(($csvRows[0]['actividad'] ?? '') === 'Familia CSV Uno', 'CSV conserva los valores importados.');
} finally {
    $db->query('DELETE FROM actividades WHERE project_id = 73 AND semanaActualizacion = ?', [$csvWeek]);
    foreach ($csvPaths as $path) {
        if (is_file($path)) unlink($path);
    }
}

try {
    $db->beginTransaction();
    $db->query(
        "INSERT INTO actividades
            (project_id, Id, codigo, actividad, descripcionActividad, fechaInicio, tipoContrato, semanaActualizacion)
         VALUES (73, 993701, 993701, 'TEST_BACKEND_MODALIDAD', 'Contrato backend', '2031-01-10', 'S', 9937)",
    );
    $activityId = (int) $db->query(
        "SELECT Id FROM actividades
         WHERE project_id = 73 AND semanaActualizacion = 9937 AND actividad = 'TEST_BACKEND_MODALIDAD'",
    )->fetchColumn();

    $invalidCodeResponse = listadoBackendCall('updateCell', [
        'id' => $activityId,
        'prop' => 'codigo',
        'value' => '993701-E2E',
    ]);
    listadoBackendAssert(
        ($invalidCodeResponse['respuesta'] ?? '') === 'ERROR' && ($invalidCodeResponse['_status'] ?? 0) === 422,
        'updateCell rechaza un codigo no entero como error de validacion 422.',
    );

    $response = listadoBackendCall('updateCell', [
        'id' => $activityId,
        'prop' => 'tipoContrato',
        'value' => ' oc, mo,MO ',
    ]);
    listadoBackendAssert(
        ($response['respuesta'] ?? '') === 'BIEN' && ($response['valor'] ?? '') === 'MO,OC',
        'updateCell normaliza modalidades validas, duplicados y orden canonico.',
    );
    $storedType = (string) $db->query(
        'SELECT tipoContrato FROM actividades WHERE project_id = 73 AND Id = ?',
        [$activityId],
    )->fetchColumn();
    listadoBackendAssert($storedType === 'MO,OC', 'updateCell persiste la modalidad normalizada.');

    $exclusiveResponse = listadoBackendCall('updateCell', [
        'id' => $activityId,
        'prop' => 'tipoContrato',
        'value' => 'MO,SI,S,OC',
    ]);
    listadoBackendAssert(
        ($exclusiveResponse['respuesta'] ?? '') === 'BIEN' && ($exclusiveResponse['valor'] ?? '') === 'SI',
        'updateCell conserva SI como modalidad exclusiva.',
    );
    $exclusiveStoredType = (string) $db->query(
        'SELECT tipoContrato FROM actividades WHERE project_id = 73 AND Id = ?',
        [$activityId],
    )->fetchColumn();
    listadoBackendAssert($exclusiveStoredType === 'SI', 'updateCell persiste SI sin modalidades incompatibles.');

    $sameCellResponse = listadoBackendCall('updateCell', [
        'id' => $activityId,
        'prop' => 'tipoContrato',
        'value' => 'SI',
    ]);
    listadoBackendAssert(
        ($sameCellResponse['respuesta'] ?? '') === 'BIEN',
        'Edicion inline sin cambios es idempotente y no genera un falso conflicto.',
    );

    $invalidResponse = listadoBackendCall('updateCell', [
        'id' => $activityId,
        'prop' => 'tipoContrato',
        'value' => 'MO,X',
    ]);
    listadoBackendAssert(
        ($invalidResponse['respuesta'] ?? '') === 'ERROR',
        'updateCell rechaza codigos de modalidad desconocidos.',
    );
    $typeAfterInvalid = (string) $db->query(
        'SELECT tipoContrato FROM actividades WHERE project_id = 73 AND Id = ?',
        [$activityId],
    )->fetchColumn();
    listadoBackendAssert($typeAfterInvalid === 'SI', 'Una modalidad invalida no modifica el registro.');

    $db->query(
        "INSERT INTO semanas_activas
            (project_id, Id, Semana, Fecha_Inicio_Sem, Fecha_Fin_Sem)
         VALUES (73, 9937, 9937, '2031-01-13', '2031-01-19')",
    );
    $db->query(
        "INSERT INTO programa
            (project_id, unique_id, Consecutivo, Id, Actividad, Titulo, Fecha_Inicio, Fecha_Fin)
         VALUES (73, 9937001, 9937001, 'T.1', 'Actividad inicio backend', 0,
                 '2031-01-15', '2031-01-16')",
    );
    $db->query(
        "INSERT INTO programa_consolidado
            (project_id, row_id, Consecutivo, Semana, unique_id, Consecutivo_en_Programa,
             Id, Actividad, Titulo, Fecha_Inicio, Fecha_Fin, Activa)
         VALUES (73, 9937001, 9937001, 9937, 9937001, 9937001,
                 'T.1', 'Actividad inicio backend', 0, '2031-01-15', '2031-01-16', 1)",
    );
    if (!method_exists(ListadoActividadesApiController::class, 'updateCard')) {
        listadoBackendAssert(false, 'Existe guardado atomico para la tarjeta mobile.');
    } else {
        $cardResponse = listadoBackendCall('updateCard', [
            'id' => $activityId,
            'actividadInicio' => '9937001',
            'tipoContrato' => 'MO,S',
        ]);
        listadoBackendAssert(($cardResponse['respuesta'] ?? '') === 'BIEN', 'Tarjeta mobile guarda actividad y modalidad juntas.');
        $cardRow = $db->query(
            'SELECT actividadInicio, fechaInicio, tipoContrato FROM actividades WHERE project_id = 73 AND Id = ?',
            [$activityId],
        )->fetch(PDO::FETCH_ASSOC);
        listadoBackendAssert(($cardRow['fechaInicio'] ?? '') === '2031-01-15' && ($cardRow['tipoContrato'] ?? '') === 'MO,S', 'Guardado mobile persiste fecha derivada y modalidades.');

        $sameCardResponse = listadoBackendCall('updateCard', [
            'id' => $activityId,
            'actividadInicio' => '9937001',
            'tipoContrato' => 'MO,S',
        ]);
        listadoBackendAssert(
            ($sameCardResponse['respuesta'] ?? '') === 'BIEN',
            'Guardar mobile sin cambios es idempotente y no genera un falso conflicto.',
        );
    }
    $legacyModifyResponse = listadoBackendCall('save', [
        'opcion' => 'modificar', 'Id' => $activityId,
        'Actividad' => 'TEST_BACKEND_MODALIDAD', 'descripcionActividad' => 'Contrato backend',
        'actividadInicio' => '9937001', 'fechaInicio' => '2040-12-31',
        'tipoContrato' => 'SI,MO',
    ]);
    $legacyModified = $db->query(
        'SELECT fechaInicio, tipoContrato FROM actividades WHERE project_id = 73 AND Id = ?',
        [$activityId],
    )->fetch(PDO::FETCH_ASSOC);
    listadoBackendAssert(($legacyModifyResponse['respuesta'] ?? '') === 'BIEN', 'Modificar legacy conserva un contrato seguro.');
    listadoBackendAssert(
        ($legacyModified['fechaInicio'] ?? '') === '2031-01-15' && ($legacyModified['tipoContrato'] ?? '') === 'SI',
        'Modificar legacy deriva fecha del cronograma y aplica exclusividad SI.',
    );
    $registerResponse = listadoBackendCall('save', [
        'opcion' => 'registrar',
        'actividad' => 'TEST_BACKEND_REGISTRAR_MODALIDAD',
        'descripcionActividad' => 'Alta normalizada',
        'actividadInicio' => '9937001',
        'fechaInicio' => '2031-01-15',
        'tipoContrato' => ' oc,MO,mo ',
    ]);
    listadoBackendAssert(
        ($registerResponse['respuesta'] ?? '') === 'BIEN',
        'registrar acepta una combinacion valida de modalidades.',
    );
    $registeredType = (string) $db->query(
        "SELECT tipoContrato FROM actividades
         WHERE project_id = 73 AND semanaActualizacion = 9937
           AND actividad = 'TEST_BACKEND_REGISTRAR_MODALIDAD'",
    )->fetchColumn();
    listadoBackendAssert($registeredType === 'MO,OC', 'registrar persiste modalidades normalizadas.');

    $syncResponse = listadoBackendCall('save', [
        'opcion' => 'registrar',
        'actividad' => 'TEST_BACKEND_REGISTRAR_FECHA',
        'descripcionActividad' => 'Alta sincronizada con cronograma',
        'actividadInicio' => '9937001',
        'fechaInicio' => '2040-12-31',
        'tipoContrato' => 'SI,MO',
    ]);
    listadoBackendAssert(
        ($syncResponse['respuesta'] ?? '') === 'BIEN',
        'registrar crea el alta vinculada a una actividad valida del cronograma.',
    );
    $syncedRow = $db->query(
        "SELECT actividadInicio, nombreActividadInicio, fechaInicio, tipoContrato
         FROM actividades
         WHERE project_id = 73 AND semanaActualizacion = 9937
           AND actividad = 'TEST_BACKEND_REGISTRAR_FECHA'",
    )->fetch(PDO::FETCH_ASSOC);
    listadoBackendAssert(
        (string) ($syncedRow['actividadInicio'] ?? '') === '9937001'
            && (string) ($syncedRow['fechaInicio'] ?? '') === '2031-01-15',
        'registrar deriva la fecha desde la actividad de inicio, no desde el navegador.',
    );
    listadoBackendAssert(
        str_contains((string) ($syncedRow['nombreActividadInicio'] ?? ''), 'Actividad inicio backend'),
        'registrar persiste el nombre seguro de la actividad de inicio.',
    );
    listadoBackendAssert(($syncedRow['tipoContrato'] ?? '') === 'SI', 'registrar aplica exclusividad SI en el alta.');
} finally {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
}

$output = (string) ob_get_clean();
echo $output;
exit($failures === [] ? 0 : 1);
