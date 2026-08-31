<?php

session_start();
require_once __DIR__ . '/rbac_guard.php';
rbac_guard_require_permission('lps.semana.eliminar');
legacy_require_csrf('lps_week_admin');
require_once __DIR__ . "/conexion.php";

/** @var Database $dbInstance */
$dbInstance = Database::getInstance();

use App\Security\DataScope\MissingProjectScope;
use App\Security\DataScope\ProjectScope;
use App\Services\Shell\DatabaseWeekAdministrationRepository;
use App\Services\Shell\EliminarSemanaComando;
use App\Services\Shell\ResultadoEliminacionSemana;
use App\Services\Shell\WeekAdministrationService;

$dbName = $_GET['db'] ?? $_POST['db'] ?? '';
$semana = (int) ($_POST["semana"] ?? 0);

if (!preg_match('/^[a-zA-Z0-9_]+$/', $dbName)) {
    die(json_encode(["respuesta" => "ERROR", "mensaje" => "Nombre de base de datos inválido."]));
}

/**
 * Tarea 5 (T01): igual que `nueva_semana.php`, la cascada de eliminar se extrajo a
 * `WeekAdministrationService`. Este script traduce sesión/proyecto a un comando y el resultado
 * tipado de vuelta a la forma histórica (`{maxSemana,puedeEliminar}`).
 */
try {
    $scope = $dbInstance->dataScope()->current();
    if (!$scope instanceof ProjectScope) {
        throw new MissingProjectScope('La operación requiere un proyecto activo.');
    }
    $projectId = $scope->projectId();

    $servicio = new WeekAdministrationService(new DatabaseWeekAdministrationRepository($dbInstance));
    $resultado = $servicio->eliminarUltima(new EliminarSemanaComando($projectId, $semana));

    if (!$resultado->exito) {
        if ($resultado->motivoBloqueo === ResultadoEliminacionSemana::BLOQUEO_NO_ES_LA_ULTIMA) {
            $maxSemanaActual = (new DatabaseWeekAdministrationRepository($dbInstance))->semanaMaxima($projectId);
            echo json_encode([
                "maxSemana" => $maxSemanaActual,
                "puedeEliminar" => "NO",
                "mensaje" => "Solo se puede eliminar la última semana activa para mantener la integridad de los datos.",
            ], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(["respuesta" => "ERROR", "mensaje" => $resultado->mensaje]);
        }

        return;
    }

    echo json_encode([
        "maxSemana" => $resultado->semanaEliminada,
        "puedeEliminar" => "SI",
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    error_log("Error en eliminar_semana.php: " . $e->getMessage());
    echo json_encode(["respuesta" => "ERROR", "mensaje" => "Error al eliminar la semana."]);
}
