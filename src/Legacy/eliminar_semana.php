<?php

session_start();
require_once __DIR__ . '/rbac_guard.php';
rbac_guard_require_permission('lps.semana.eliminar');
legacy_require_csrf('lps_week_admin');
require_once __DIR__ . "/conexion.php";

/** @var Database $dbInstance */
$dbInstance = Database::getInstance();

$dbName = $_GET['db'] ?? $_POST['db'] ?? '';
$semana = (int) ($_POST["semana"] ?? 0);

if (!preg_match('/^[a-zA-Z0-9_]+$/', $dbName)) {
    die(json_encode(["respuesta" => "ERROR", "mensaje" => "Nombre de base de datos inválido."]));
}

// Resolve table names via TableResolver
$tSemanasActivas = TableResolver::resolveByPrefix($dbName, 'semanas_activas');
$tProgConsolidado = TableResolver::resolveByPrefix($dbName, 'programa_consolidado');
$tProgSemanal = TableResolver::resolveByPrefix($dbName, 'programacion_semanal');
$tCic = TableResolver::resolveByPrefix($dbName, 'cic');

$scope = $dbInstance->dataScope()->current();
if (!$scope instanceof \App\Security\DataScope\ProjectScope) {
    throw new \App\Security\DataScope\MissingProjectScope('La operación requiere un proyecto activo.');
}
$projectId = $scope->projectId();

try {
    // 1. Verificar si es la última semana
    $stmtMax = $dbInstance->queryWithProject("SELECT MAX(Semana) AS maxSemana FROM {$tSemanasActivas} WHERE project_id = ?", [$projectId], $projectId);
    $dataMax = $stmtMax->fetch();
    $maxSemana = (int) ($dataMax["maxSemana"] ?? 0);

    if ($maxSemana > $semana) {
        $arreglo = [
            "maxSemana" => $maxSemana,
            "puedeEliminar" => "NO",
            "mensaje" => "Solo se puede eliminar la última semana activa para mantener la integridad de los datos.",
        ];
        echo json_encode($arreglo, JSON_UNESCAPED_UNICODE);
    } else {
        // 2. Realizar eliminación en cascada de la semana seleccionada (y superiores por seguridad)
        // `pdc` y `actividades` salieron de la cascada el 2026-08-04: eran las tablas del PDC v1.
        $tablas = [
            "{$tSemanasActivas}" => "Semana",
            "{$tProgConsolidado}" => "Semana",
            "{$tProgSemanal}" => "Semana",
            "{$tCic}" => "Semana",
        ];

        foreach ($tablas as $tabla => $columna) {
            $dbInstance->queryWithProject("DELETE FROM $tabla WHERE project_id = ? AND $columna >= ?", [$projectId, $semana], $projectId);
        }

        $dbInstance->logActivity('Sistema', 'ELIMINAR_SEMANA', "Eliminación de semana $semana y superiores en proyecto $dbName");

        $arreglo = [
            "maxSemana" => $maxSemana,
            "puedeEliminar" => "SI",
        ];
        echo json_encode($arreglo, JSON_UNESCAPED_UNICODE);
    }

} catch (Exception $e) {
    error_log("Error en eliminar_semana.php: " . $e->getMessage());
    echo json_encode(["respuesta" => "ERROR", "mensaje" => "Error al eliminar la semana."]);
}
