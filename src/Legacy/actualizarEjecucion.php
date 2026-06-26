<?php

session_start();
require_once __DIR__ . "/conexion.php";

/** @var Database $db */
$db = Database::getInstance();

$dbName = $_GET['db'] ?? $_POST['db'] ?? '';
$semana = (int) ($_POST["semana"] ?? 0);
$semanaAnterior = $semana - 1;
$f_inicio_sem = date("Y-m-d", strtotime($_POST["f_inicio_sem"] ?? 'now'));

// Validar nombre de base de datos para evitar inyección en identificadores de tabla
if (!preg_match('/^[a-zA-Z0-9_]+$/', $dbName)) {
    die("Error: Nombre de base de datos inválido.");
}

// Resolve table names via TableResolver
$tProgSemanal = TableResolver::resolveByPrefix($dbName, 'programacion_semanal');
$tProgConsolidado = TableResolver::resolveByPrefix($dbName, 'programa_consolidado');

// Set project context for queryWithProject auto-injection
$projectId = TableResolver::getProjectIdByPrefix($dbName);
if ($projectId) {
    $db->setProjectContext($projectId);
}

try {
    $sqlSelect = "SELECT Actividad, Consecutivo_En_Programa, Id, Ejecutado, Unidad, cantidad_ppto, Compromiso, Ejecutado_Real, Responsable_AIA, Sub_Contratista
                  FROM {$tProgSemanal}
                  WHERE Semana = ? AND (Activa = '1' OR Activa = 'NA')";

    $stmt = $db->queryWithProject($sqlSelect, [$semanaAnterior]);
    $actividades = $stmt->fetchAll();

    $conteo_actividades = count($actividades);

    if ($conteo_actividades === 0) {
        $ejecucionActualizada = 0;
        $semanalConfirmada = 1;
        $respuesta = [$semana, 0, $ejecucionActualizada, $semanalConfirmada];
        echo json_encode($respuesta);
    } else {
        foreach ($actividades as $data) {
            $Actividad = $data["Actividad"];
            $Ejecutado = (float) ($data["Ejecutado"] ?? 0);
            $cantidad_ppto = (float) ($data["cantidad_ppto"] ?? 0);
            $Responsable_AIA = $data["Responsable_AIA"] ?? null;
            $Sub_Contratista = $data["Sub_Contratista"] ?? null;
            $Ejecutado_Real = (float) ($data["Ejecutado_Real"] ?? 0);

            if ($cantidad_ppto <= 0) {
                // Fallback: actividades tipo % usan 100 como base de cálculo
                $cantidad_ppto = 100;
            }

            if ($Ejecutado_Real == 0) {
                $Ejecutado_fin_semana = $Ejecutado;
            } else {
                $Ejecutado_fin_semana = (($Ejecutado_Real / $cantidad_ppto) * 100) + $Ejecutado;
            }

            $sqlUpdate = "UPDATE {$tProgConsolidado}
                          SET Ejecutado = ?, Responsable_AIA = ?, Sub_Contratista = ?
                          WHERE Semana = ? AND (Actividad = ? OR programaAnteriorAsociar = ?)";

            $db->queryWithProject($sqlUpdate, [
                $Ejecutado_fin_semana,
                $Responsable_AIA,
                $Sub_Contratista,
                $semana,
                $Actividad,
                $Actividad,
            ]);
        }

        $ejecucionActualizada = 1;
        $semanalConfirmada = 1;
        require("modificar_sem_estado.php");
    }

} catch (Exception $e) {
    error_log("Error en actualizarEjecucion.php: " . $e->getMessage());
    die("Error al actualizar la ejecución.");
}
