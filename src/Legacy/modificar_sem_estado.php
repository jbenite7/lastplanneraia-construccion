<?php

/** @var Database $dbInstance */
// $dbInstance ya está disponible si es llamado desde nueva_semana.php
if (!isset($dbInstance)) {
    // Fallback if called from elsewhere and provided as $db (object)
    if (isset($db) && is_object($db)) {
        $dbInstance = $db;
    } else {
        require_once __DIR__ . "/conexion.php";
        $dbInstance = Database::getInstance();
    }
}

require_once __DIR__ . '/estado_programa_general.php';

use App\Services\RestrictionConfigResolver;

// Resolve table names via TableResolver
$tProgConsolidado = TableResolver::resolveByPrefix($dbName, 'programa_consolidado');
$tSemanasActivas = TableResolver::resolveByPrefix($dbName, 'semanas_activas');

try {
    $sqlSelect = "SELECT * FROM {$tProgConsolidado} WHERE Semana = ?";
    $stmt = $dbInstance->queryWithProject($sqlSelect, [$semana]);
    $actividades = $stmt->fetchAll();

    if (count($actividades) > 0) {
        $restrictionConfig = isset($dbName) ? RestrictionConfigResolver::resolve($dbName) : null;
        $projectArea = $restrictionConfig['area'] ?? 'Construccion';

        foreach ($actividades as $data) {
            $Id = $data["unique_id"] ?? $data["Consecutivo_en_Programa"];
            $Titulo = (int) ($data["Titulo"] ?? 0);
            $Estado_Restricciones = '0';

            if ($Titulo === 0) {
                $Estado_Restricciones = RestrictionConfigResolver::calculateEstadoRestricciones($data, $projectArea);
            }

            $semanas_val = pg_calculate_week_offset($data["Fecha_Inicio"] ?? null, $f_inicio_sem);

            $sqlUpdateActividad = "UPDATE {$tProgConsolidado}
                                   SET Semanas_Inicio = ?, Estado_Restricciones = ?
                                   WHERE unique_id = ? AND Semana = ?";

            $valEstadoRestricciones = ($Titulo === 1) ? 0 : $Estado_Restricciones;
            $valSemanasInicio = $semanas_val;

            $dbInstance->queryWithProject($sqlUpdateActividad, [
                $valSemanasInicio,
                $valEstadoRestricciones,
                $Id,
                $semana,
            ]);
        }
    }

    usleep(500000); // Reemplaza sleep(0.5) que causaba advertencia de depreciación

    $dbInstance->queryWithProject("UPDATE {$tProgConsolidado} SET Ruta_Critica = 0 WHERE Titulo = 1 AND Semana = ?", [$semana]);

    $normalizationService = $normalizationService ?? new \App\Services\ProgramaConsolidadoNormalizationService($dbInstance);
    $normalizationService->normalizeChapters($dbName, $semana);
    $dbInstance->queryWithProject("UPDATE {$tProgConsolidado} SET Semanas_Inicio = 0 WHERE Fecha_Inicio IS NULL AND Fecha_Fin IS NULL AND Titulo = 1 AND Semana = ?", [$semana]);

    $fechaFinSemana = $f_fin_sem ?? null;
    if (empty($fechaFinSemana)) {
        $stmtSemana = $dbInstance->queryWithProject("SELECT Fecha_Fin_Sem FROM {$tSemanasActivas} WHERE Semana = ?", [$semana]);
        $dataSemana = $stmtSemana->fetch();
        $fechaFinSemana = $dataSemana['Fecha_Fin_Sem'] ?? null;
    }

    $sqlEstadoRows = "SELECT unique_id, unique_id AS Consecutivo_en_Programa, Titulo, Ejecutado, Fecha_Inicio, Fecha_Fin
                      FROM {$tProgConsolidado}
                      WHERE Semana = ?";
    $stmtEstadoRows = $dbInstance->queryWithProject($sqlEstadoRows, [$semana]);
    $rowsEstado = $stmtEstadoRows->fetchAll();

    foreach ($rowsEstado as $rowEstado) {
        $estado = pg_calculate_status(
            $rowEstado['Titulo'] ?? 0,
            $rowEstado['Ejecutado'] ?? 0,
            $rowEstado['Fecha_Inicio'] ?? null,
            $rowEstado['Fecha_Fin'] ?? null,
            $f_inicio_sem,
            $fechaFinSemana,
        );

        $dbInstance->queryWithProject(
            "UPDATE {$tProgConsolidado} SET Estado = ? WHERE unique_id = ? AND Semana = ?",
            [$estado, $rowEstado['unique_id'], $semana],
        );
    }

    // Ensure variables are set, defaulting to 0/null if not inherited
    $conteoPDC = $conteoPDC ?? 0;
    $semanalConfirmada = $semanalConfirmada ?? 0;
    $ejecucionActualizada = $ejecucionActualizada ?? 0;

    $respuesta = [$semana, $conteoPDC, $ejecucionActualizada, $semanalConfirmada];
    echo json_encode($respuesta);

} catch (Exception $e) {
    error_log("Error en modificar_sem_estado.php: " . $e->getMessage());
    echo json_encode(["respuesta" => "ERROR", "mensaje" => "Error al modificar estado: " . $e->getMessage()]);
}
