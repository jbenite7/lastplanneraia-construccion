<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
header('Content-Type: application/json');

/** @var Database $dbInstance */
if (!isset($dbInstance)) {
    require_once __DIR__ . "/conexion.php";
    $dbInstance = Database::getInstance();
}

use App\Security\DataScope\MissingProjectScope;
use App\Security\DataScope\ProjectScope;
use App\Services\RestrictionConfigResolver;
use App\Services\Shell\CrearSemanaComando;
use App\Services\Shell\DatabaseWeekAdministrationRepository;
use App\Services\Shell\ResultadoCreacionSemana;
use App\Services\Shell\WeekAdministrationService;

$db = $_GET['db'] ?? $_POST['db'] ?? '';
$f_inicio_sem_raw = $_POST["f_inicio_sem"] ?? '';
$f_inicio_sem = date("Y-m-d", strtotime($f_inicio_sem_raw));
$pdcActivo = $_SESSION['pdcActivo'] ?? 0;
$rolCanon = $_SESSION['permiso_canonico'] ?? '';
$esAdmin = ($rolCanon === 'A');

require_once PROJECT_ROOT . '/src/Legacy/rbac_guard.php';
rbac_guard_require_permission('lps.semana.crear');
legacy_require_csrf('lps_week_admin');

if (!preg_match('/^[a-zA-Z0-9_]+$/', $db)) {
    die(json_encode(["respuesta" => "ERROR", "mensaje" => "Nombre de base de datos inválido."]));
}

/**
 * Tarea 5 (T01): las reglas de crear semana se extrajeron a `WeekAdministrationService`. Este
 * script sigue siendo la puerta de entrada legada (`shell_week_admin.js` todavía la llama) pero
 * ya no ejecuta SQL propio — solo traduce sesión/CSRF/rol a un comando y el resultado tipado de
 * vuelta a la forma de respuesta histórica (`[conteo,0,0,confirmada]` para el bloqueo por semana
 * sin confirmar, `{respuesta:"ERROR",...}` para el resto de bloqueos, y el array posicional
 * `[semana, conteoPDC, ejecucionActualizada, semanalConfirmada]` para el éxito — igual que
 * producía `modificar_sem_estado.php`).
 */
try {
    $scope = $dbInstance->dataScope()->current();
    if (!$scope instanceof ProjectScope) {
        throw new MissingProjectScope('La operación requiere un proyecto activo.');
    }
    $projectId = $scope->projectId();

    $restrictionConfig = RestrictionConfigResolver::resolve($db);
    $isPreConstruccion = $restrictionConfig['isPreConstruccion'];

    $servicio = new WeekAdministrationService(new DatabaseWeekAdministrationRepository($dbInstance));
    $comando = new CrearSemanaComando($projectId, $f_inicio_sem, $isPreConstruccion, $esAdmin);
    $resultado = $servicio->crear($comando);

    if (!$resultado->exito) {
        if ($resultado->motivoBloqueo === ResultadoCreacionSemana::BLOQUEO_SEMANA_NO_CONFIRMADA) {
            // Forma histórica: el cliente lee semana=info[0] y confirmada=info[3].
            $filaConteo = $dbInstance
                ->queryWithProject(
                    "SELECT COUNT(*) AS conteo FROM " . TableResolver::resolveByPrefix($db, 'semanas_activas') . " WHERE project_id = ?",
                    [$projectId],
                    $projectId,
                )
                ->fetch();
            $conteoActual = $filaConteo['conteo'] ?? 0;
            echo json_encode([(int) $conteoActual, 0, 0, 0], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(["respuesta" => "ERROR", "mensaje" => $resultado->mensaje]);
        }

        return;
    }

    $semana = $resultado->semana;
    $conteoPDC = $pdcActivo;
    $ejecucionActualizada = 1;

    // `modificar_sem_estado.php` también sabe leer `Semanal_Confirmada` de la semana recién
    // creada para el cuarto elemento de la respuesta; una semana recién creada nunca viene
    // confirmada, así que es siempre 0 — igual que el script legado.
    $semanalConfirmada = 0;

    echo json_encode([$semana, $conteoPDC, $ejecucionActualizada, $semanalConfirmada]);
} catch (Exception $e) {
    echo json_encode(["respuesta" => "ERROR", "mensaje" => $e->getMessage()]);
}
