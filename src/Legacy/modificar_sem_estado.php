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


try {
    $sqlSelect = "SELECT * FROM {$dbName}_programa_consolidado WHERE Semana = ?";
    $stmt = $dbInstance->query($sqlSelect, [$semana]);
    $actividades = $stmt->fetchAll();

    if (count($actividades) > 0) {
        foreach ($actividades as $data) {
            $Id = $data["Consecutivo_en_Programa"];
            $Titulo = (int)($data["Titulo"] ?? 0);
            $Estado_Restricciones = '0';

            if ($Titulo === 0) {
                $campos = ["D_y_E", "Materiales", "MdeO", "Equipos", "Predecesora", "Pdto_Cons", "Modelo"];
                $conteo_rest = 0;
                $suma_rest = 0;

                foreach ($campos as $campo) {
                    $valor = $data[$campo];
                    if ($valor !== "N/A" && $valor !== null) {
                        $conteo_rest++;
                        $suma_rest += round((float)$valor, 5);
                    }
                }

                if ($conteo_rest === 0) {
                    $Estado_Restricciones = 1;
                } else {
                    $Estado_Restricciones = round(($suma_rest / $conteo_rest), 5);
                }
            }

            $semanas_val = pg_calculate_week_offset($data["Fecha_Inicio"] ?? null, $f_inicio_sem);

            $sqlUpdateActividad = "UPDATE {$dbName}_programa_consolidado 
                                   SET Semanas_Inicio = ?, Estado_Restricciones = ? 
                                   WHERE Consecutivo_en_Programa = ? AND Semana = ?";

            $valEstadoRestricciones = ($Titulo === 1) ? 0 : $Estado_Restricciones;
            $valSemanasInicio = $semanas_val;

            $dbInstance->query($sqlUpdateActividad, [
                $valSemanasInicio,
                $valEstadoRestricciones,
                $Id,
                $semana,
            ]);
        }
    }

    usleep(500000); // Reemplaza sleep(0.5) que causaba advertencia de depreciación

    $dbInstance->query("UPDATE {$dbName}_programa_consolidado SET Ruta_Critica = 0 WHERE Titulo = 1 AND Semana = ?", [$semana]);

    $dbInstance->query("UPDATE {$dbName}_programa_consolidado SET Ejecutado = 0, Semanas_Inicio = 0 WHERE Fecha_Inicio IS NULL AND Fecha_Fin IS NULL AND Titulo = 1 AND Semana = ?", [$semana]);

    $fechaFinSemana = $f_fin_sem ?? null;
    if (empty($fechaFinSemana)) {
        $stmtSemana = $dbInstance->query("SELECT Fecha_Fin_Sem FROM {$dbName}_semanas_activas WHERE Semana = ?", [$semana]);
        $dataSemana = $stmtSemana->fetch();
        $fechaFinSemana = $dataSemana['Fecha_Fin_Sem'] ?? null;
    }

    $sqlEstadoRows = "SELECT Consecutivo_en_Programa, Titulo, Ejecutado, Fecha_Inicio, Fecha_Fin
                      FROM {$dbName}_programa_consolidado
                      WHERE Semana = ?";
    $stmtEstadoRows = $dbInstance->query($sqlEstadoRows, [$semana]);
    $rowsEstado = $stmtEstadoRows->fetchAll();

    foreach ($rowsEstado as $rowEstado) {
        $estado = pg_calculate_status(
            $rowEstado['Titulo'] ?? 0,
            $rowEstado['Ejecutado'] ?? 0,
            $rowEstado['Fecha_Inicio'] ?? null,
            $rowEstado['Fecha_Fin'] ?? null,
            $f_inicio_sem,
            $fechaFinSemana
        );

        $dbInstance->query(
            "UPDATE {$dbName}_programa_consolidado SET Estado = ? WHERE Consecutivo_en_Programa = ? AND Semana = ?",
            [$estado, $rowEstado['Consecutivo_en_Programa'], $semana]
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
