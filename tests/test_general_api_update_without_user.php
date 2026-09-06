<?php

declare(strict_types=1);
// @requiere: db


use App\Controllers\Api\GeneralApiController;
use App\Security\CsrfTokenManager;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/support/SessionScopeHarness.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$db = Database::getInstance();
$failures = 0;
$originalSession = $_SESSION;
$originalGet = $_GET;
$originalPost = $_POST;
$originalServer = $_SERVER;
$warnings = [];
$transactionStarted = false;
$auditLogPath = tempnam(sys_get_temp_dir(), 'general_api_audit_');
$originalErrorLog = ini_get('error_log');

function generalApiUserAssert(bool $condition, string $message): void
{
    global $failures;

    echo ($condition ? 'PASS: ' : 'FAIL: ') . $message . PHP_EOL;
    if (!$condition) {
        $failures++;
    }
}

try {
    if ($auditLogPath === false) {
        throw new RuntimeException('No se pudo reservar el log temporal de auditoría.');
    }

    ini_set('error_log', $auditLogPath);
    $memberships = $db->query(
        "SELECT p.ID AS project_id, p.Proyecto_Proceso AS project_name, p.Base_de_Datos AS db_prefix,
                u.usuario, u.nombre, pm.role
         FROM project_members pm
         INNER JOIN general_usuarios u ON u.id = pm.user_id
         INNER JOIN general_proyectos_procesos p ON p.ID = pm.project_id
         WHERE u.activo = 1 AND pm.role IN ('A', 'D', 'R', 'DCV')
         ORDER BY FIELD(pm.role, 'A', 'D', 'R', 'DCV'), pm.id"
    )->fetchAll(PDO::FETCH_ASSOC);

    $fixture = null;
    foreach ($memberships as $membership) {
        $projectId = (int) ($membership['project_id'] ?? 0);
        $dbPrefix = (string) ($membership['db_prefix'] ?? '');
        if ($projectId <= 0 || $dbPrefix === '') {
            continue;
        }

        // El alcance se resuelve desde $_SESSION con el mismo resolver que usa SessionMiddleware,
        // así que la sesión de esta membresía tiene que existir ya para buscar su fila. Antes
        // bastaba `setProjectContext()`, que leía una $_SESSION todavía vacía y se quedaba callado:
        // el contexto no se enlazaba y la consulta de abajo moría sin decir por qué.
        $_SESSION = [
            'usuario' => (string) $membership['usuario'],
            'permiso' => (string) $membership['role'],
            'permiso_canonico' => (string) $membership['role'],
            'project_id' => $projectId,
            'db' => $dbPrefix,
            'timeout' => time(),
        ];
        if (!SessionScopeHarness::bindFromSession($db)) {
            continue;
        }

        $row = $db->queryWithProject(
            'SELECT unique_id, Id, Ejecutado, codigo_actividad, unidad, cantidad_ppto, Fecha_Inicio, Fecha_Fin, Semana
             FROM programa_consolidado
             WHERE project_id = ? AND Titulo = 0
             ORDER BY Semana DESC, row_id ASC
             LIMIT 1',
            [$projectId],
            $projectId,
        )->fetch(PDO::FETCH_ASSOC);

        if (is_array($row)) {
            $fixture = ['membership' => $membership, 'row' => $row];
            break;
        }
    }

    if ($fixture === null) {
        throw new RuntimeException('No hay una actividad editable asociada a un usuario con permisos de escritura.');
    }

    $membership = $fixture['membership'];
    $row = $fixture['row'];
    $projectId = (int) $membership['project_id'];
    $dbPrefix = (string) $membership['db_prefix'];
    $week = (int) $row['Semana'];

    $_SESSION = [
        'usuario' => (string) $membership['usuario'],
        'nombreUsuario' => (string) ($membership['nombre'] ?? ''),
        'permiso' => (string) $membership['role'],
        'permiso_canonico' => (string) $membership['role'],
        'proyecto' => (string) $membership['project_name'],
        'db' => $dbPrefix,
        'project_id' => $projectId,
        'semana' => $week,
        'timeout' => time(),
    ];
    $_GET = ['db' => $dbPrefix, 'semana' => $week];
    $_POST = [
        'unique_id' => (string) $row['unique_id'],
        'Consecutivo_en_Programa' => (string) $row['unique_id'],
        'Id' => (string) ($row['Id'] ?? ''),
        'Ejecutado' => (string) (((float) ($row['Ejecutado'] ?? 0)) * 100),
        'EjecutadoRatio' => (string) ((float) ($row['Ejecutado'] ?? 0)),
        'codigo_actividad' => (string) ($row['codigo_actividad'] ?? ''),
        'unidad' => (string) ($row['unidad'] ?? '%'),
        'cantidad_ppto' => (string) ($row['cantidad_ppto'] ?? ''),
        'Fecha_Inicio' => (string) ($row['Fecha_Inicio'] ?? ''),
        'Fecha_Fin' => (string) ($row['Fecha_Fin'] ?? ''),
    ];
    $_SERVER['HTTP_ACCEPT'] = 'application/json';
    $_SERVER['HTTP_X_AIA_EXPECT_JSON'] = '1';
    $_SERVER['HTTP_X_CSRF_TOKEN'] = CsrfTokenManager::generate('programa_general_save');
    // La sesión definitiva del escenario sustituye a la del sondeo: hay que re-enlazar el alcance.
    SessionScopeHarness::requireFromSession($db);
    $payloadOmitsUser = !array_key_exists('user', $_POST);
    $sessionUser = (string) $_SESSION['usuario'];

    set_error_handler(
        static function (int $severity, string $message) use (&$warnings): bool {
            if ($severity === E_WARNING) {
                $warnings[] = $message;
                return true;
            }

            return false;
        },
    );

    $db->beginTransaction();
    $transactionStarted = true;
    http_response_code(200);
    ob_start();
    (new GeneralApiController())->update();
    $payloadWithoutUser = (string) ob_get_clean();
    $decodedWithoutUser = json_decode($payloadWithoutUser, true);

    $_POST['user'] = 'post-spoof-must-not-be-audited';
    http_response_code(200);
    ob_start();
    (new GeneralApiController())->update();
    $payloadWithSpoof = (string) ob_get_clean();
    $decodedWithSpoof = json_decode($payloadWithSpoof, true);
    $auditLog = (string) file_get_contents($auditLogPath);

    generalApiUserAssert($payloadOmitsUser, 'Given: el payload operacional omite user');
    generalApiUserAssert(
        is_array($decodedWithoutUser) && ($decodedWithoutUser['respuesta'] ?? null) === 'BIEN',
        'When: el controlador procesa el guardado autenticado sin user',
    );
    generalApiUserAssert(
        is_array($decodedWithSpoof) && ($decodedWithSpoof['respuesta'] ?? null) === 'BIEN',
        'When: el controlador ignora un user enviado por POST',
    );
    generalApiUserAssert(
        $warnings === [],
        'Then: el guardado no emite warnings; capturados=' . json_encode($warnings, JSON_UNESCAPED_UNICODE),
    );
    generalApiUserAssert(
        substr_count($auditLog, "usuario={$sessionUser}") === 4,
        'Then: INICIO y FINAL auditan siempre al usuario autenticado de sesión',
    );
    generalApiUserAssert(
        !str_contains($auditLog, 'post-spoof-must-not-be-audited'),
        'Then: PGAudit nunca confía en user recibido por POST',
    );
} catch (Throwable $exception) {
    generalApiUserAssert(false, 'Excepción inesperada: ' . $exception->getMessage());
} finally {
    restore_error_handler();
    if ($transactionStarted && $db->inTransaction()) {
        $db->rollBack();
    }
    $db->setProjectContext(null);
    $_SESSION = $originalSession;
    $_GET = $originalGet;
    $_POST = $originalPost;
    $_SERVER = $originalServer;
    ini_set('error_log', is_string($originalErrorLog) ? $originalErrorLog : '');
    if (is_string($auditLogPath) && is_file($auditLogPath)) {
        unlink($auditLogPath);
    }
}

echo $failures === 0
    ? "=== General API update without user: OK ===\n"
    : "=== General API update without user: FAIL ({$failures}) ===\n";
exit($failures === 0 ? 0 : 1);
