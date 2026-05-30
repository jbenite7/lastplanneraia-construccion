<?php

use App\Support\ModuleRequestContext;

session_start();
require_once __DIR__ . "/conexion.php";
require_once PROJECT_ROOT . '/src/Legacy/rbac_guard.php';
require_once __DIR__ . '/_pdc_functions.php';

header('Content-Type: application/json; charset=utf-8');

/** @var Database $db */
$db = Database::getInstance();

try {
    $context = ModuleRequestContext::resolve();
    $dbName = $context['dbPrefix'];
    $semana = (int) ($_POST['semana'] ?? ($_GET['semana'] ?? $context['semana']));

    rbac_guard_require_permission('lps.pdc.editar', [
        'message' => 'No autorizado para actualizar el plan de compras.',
    ]);

    // 1. CHEQUEO DE VALIDACIÓN ORIGINAL (PDC ACTIVO)
    // Extraído del código fuente original actualizar_pdc.php
    $sqlPdcActivo = "SELECT pdcActivo FROM general_proyectos_procesos WHERE Base_de_datos = ? AND Area = 'Construcción'";
    $stmtPdcActivo = $db->query($sqlPdcActivo, [$dbName]);
    $dataPdcActivo = $stmtPdcActivo->fetch();
    $pdcActivo = (int) ($dataPdcActivo["pdcActivo"] ?? 0);

    // Solo ejecuta la lógica pesada si pdcActivo está habilitado (1), igual que el archivo legacy
    if ($pdcActivo === 1) {

        $sqlConteo = "SELECT COUNT(*) AS conteo FROM {$dbName}_pdc WHERE titulo = 0 AND semana = ? AND fechaInicio IS NOT NULL";
        $stmtConteo = $db->query($sqlConteo, [$semana]);
        $dataConteo = $stmtConteo->fetch();
        $conteo = (int) ($dataConteo["conteo"] ?? 0);

        if ($conteo === 0) {
            $contratosVigentesSI = "";
            $contratosVigentesS = "";
            $contratosVigentesMO = "";

            $sqlDelete = "DELETE FROM {$dbName}_pdc WHERE (titulo = 1 AND semana = ?) OR (titulo = 0 AND fechaInicio IS NULL AND semana = ?)";
            $db->query($sqlDelete, [$semana, $semana]);

            pdc_insertarPaquetes($db, $dbName, $semana, $contratosVigentesSI, $contratosVigentesS, $contratosVigentesMO);

            sleep(1);
            pdc_generarEstadoProceso($db, $dbName, $semana);

            $db->logActivity('Sistema', 'PDC_ACTUALIZAR', "Actualización de PDC para nueva semana $semana iniciada (conteo 0)");
            echo json_encode(["respuesta" => "BIEN"]);

        } else {
            $sqlVigentes = <<<SQL
SELECT GROUP_CONCAT(CONCAT("CONCAT(paqueteContratacion, '&', tipoPaquete) != '", paqueteContratacion, "&", tipoPaquete, "'") SEPARATOR ' AND ') AS contratos
FROM (SELECT DISTINCT paqueteSI1 AS paqueteContratacion, 'Suministro e Instalación' AS tipoPaquete FROM {$dbName}_actividades WHERE fechaInicio IS NOT NULL AND semanaActualizacion = ? AND paqueteSI1 IS NOT NULL AND paqueteSI1 != ''
            UNION SELECT DISTINCT paqueteSI2 AS paqueteContratacion, 'Suministro e Instalación' AS tipoPaquete FROM {$dbName}_actividades WHERE fechaInicio IS NOT NULL AND semanaActualizacion = ? AND paqueteSI2 IS NOT NULL AND paqueteSI2 != ''
            UNION SELECT DISTINCT paqueteSI3 AS paqueteContratacion, 'Suministro e Instalación' AS tipoPaquete FROM {$dbName}_actividades WHERE fechaInicio IS NOT NULL AND semanaActualizacion = ? AND paqueteSI3 IS NOT NULL AND paqueteSI3 != ''
            UNION SELECT DISTINCT paqueteSI4 AS paqueteContratacion, 'Suministro e Instalación' AS tipoPaquete FROM {$dbName}_actividades WHERE fechaInicio IS NOT NULL AND semanaActualizacion = ? AND paqueteSI4 IS NOT NULL AND paqueteSI4 != ''
            UNION SELECT DISTINCT paqueteSI5 AS paqueteContratacion, 'Suministro e Instalación' AS tipoPaquete FROM {$dbName}_actividades WHERE fechaInicio IS NOT NULL AND semanaActualizacion = ? AND paqueteSI5 IS NOT NULL AND paqueteSI5 != ''
            UNION SELECT DISTINCT paqueteMO1 AS paqueteContratacion, 'Mano de Obra' AS tipoPaquete FROM {$dbName}_actividades WHERE fechaInicio IS NOT NULL AND semanaActualizacion = ? AND paqueteMO1 IS NOT NULL AND paqueteMO1 != ''
            UNION SELECT DISTINCT paqueteMO2 AS paqueteContratacion, 'Mano de Obra' AS tipoPaquete FROM {$dbName}_actividades WHERE fechaInicio IS NOT NULL AND semanaActualizacion = ? AND paqueteMO2 IS NOT NULL AND paqueteMO2 != ''
            UNION SELECT DISTINCT paqueteMO3 AS paqueteContratacion, 'Mano de Obra' AS tipoPaquete FROM {$dbName}_actividades WHERE fechaInicio IS NOT NULL AND semanaActualizacion = ? AND paqueteMO3 IS NOT NULL AND paqueteMO3 != ''
            UNION SELECT DISTINCT paqueteMO4 AS paqueteContratacion, 'Mano de Obra' AS tipoPaquete FROM {$dbName}_actividades WHERE fechaInicio IS NOT NULL AND semanaActualizacion = ? AND paqueteMO4 IS NOT NULL AND paqueteMO4 != ''
            UNION SELECT DISTINCT paqueteMO5 AS paqueteContratacion, 'Mano de Obra' AS tipoPaquete FROM {$dbName}_actividades WHERE fechaInicio IS NOT NULL AND semanaActualizacion = ? AND paqueteMO5 IS NOT NULL AND paqueteMO5 != ''
            UNION SELECT DISTINCT paqueteS1 AS paqueteContratacion, 'Suministro' AS tipoPaquete FROM {$dbName}_actividades WHERE fechaInicio IS NOT NULL AND semanaActualizacion = ? AND paqueteS1 IS NOT NULL AND paqueteS1 != ''
            UNION SELECT DISTINCT paqueteS2 AS paqueteContratacion, 'Suministro' AS tipoPaquete FROM {$dbName}_actividades WHERE fechaInicio IS NOT NULL AND semanaActualizacion = ? AND paqueteS2 IS NOT NULL AND paqueteS2 != ''
            UNION SELECT DISTINCT paqueteS3 AS paqueteContratacion, 'Suministro' AS tipoPaquete FROM {$dbName}_actividades WHERE fechaInicio IS NOT NULL AND semanaActualizacion = ? AND paqueteS3 IS NOT NULL AND paqueteS3 != ''
            UNION SELECT DISTINCT paqueteS4 AS paqueteContratacion, 'Suministro' AS tipoPaquete FROM {$dbName}_actividades WHERE fechaInicio IS NOT NULL AND semanaActualizacion = ? AND paqueteS4 IS NOT NULL AND paqueteS4 != ''
            UNION SELECT DISTINCT paqueteS5 AS paqueteContratacion, 'Suministro' AS tipoPaquete FROM {$dbName}_actividades WHERE fechaInicio IS NOT NULL AND semanaActualizacion = ? AND paqueteS5 IS NOT NULL AND paqueteS5 != '')
AS Tabla
SQL;

            $paramsVigentes = array_fill(0, 15, $semana);
            $stmtVigentes = $db->query($sqlVigentes, $paramsVigentes);
            $dataVigentes = $stmtVigentes->fetch();
            $contratosVigentes = $dataVigentes["contratos"] ?? '';

            if (empty($contratosVigentes)) {
                $db->query("DELETE FROM {$dbName}_pdc WHERE (titulo = 0 AND semana = ?) OR (titulo = 1 AND semana = ?)", [$semana, $semana]);
            } else {
                $db->query("DELETE FROM {$dbName}_pdc WHERE (titulo = 0 AND semana = ? AND $contratosVigentes) OR (titulo = 1 AND semana = ?)", [$semana, $semana]);
            }

            $stmtPdc = $db->query("SELECT * FROM {$dbName}_pdc WHERE titulo = 0 AND semana = ?", [$semana]);
            $actividadesPdc = $stmtPdc->fetchAll();

            $queryUpdate = false;
            $contratosVigentesSI = "";
            $contratosVigentesS = "";
            $contratosVigentesMO = "";

            foreach ($actividadesPdc as $data) {
                $queryUpdate = true;
                $tipoPaquete = $data["tipoPaquete"];
                $paqueteContratacion = $data["paqueteContratacion"];

                if ($tipoPaquete == "Suministro e Instalación") {
                    $tipoContrato = 2;
                    $grupo = "SI";
                    $contratosVigentesSI .= "SubAct.paqueteContratacion != " . $db->quote($paqueteContratacion) . " AND ";
                } elseif ($tipoPaquete == "Suministro") {
                    $tipoContrato = 1;
                    $grupo = "S";
                    $contratosVigentesS .= "SubAct.paqueteContratacion != " . $db->quote($paqueteContratacion) . " AND ";
                } elseif ($tipoPaquete == "Mano de Obra") {
                    $tipoContrato = 1;
                    $grupo = "MO";
                    $contratosVigentesMO .= "SubAct.paqueteContratacion != " . $db->quote($paqueteContratacion) . " AND ";
                }

                $sqlUpdate = "UPDATE {$dbName}_pdc SET 
                    contratos = (
                        SELECT GROUP_CONCAT(REPLACE(actividad, ';', '; ') SEPARATOR '; ')
                        FROM (
                            SELECT actividad FROM {$dbName}_actividades WHERE semanaActualizacion = ? AND tipoContrato = ? AND paquete{$grupo}1 = ?
                            UNION SELECT actividad FROM {$dbName}_actividades WHERE semanaActualizacion = ? AND tipoContrato = ? AND paquete{$grupo}2 = ?
                            UNION SELECT actividad FROM {$dbName}_actividades WHERE semanaActualizacion = ? AND tipoContrato = ? AND paquete{$grupo}3 = ?
                            UNION SELECT actividad FROM {$dbName}_actividades WHERE semanaActualizacion = ? AND tipoContrato = ? AND paquete{$grupo}4 = ?
                            UNION SELECT actividad FROM {$dbName}_actividades WHERE semanaActualizacion = ? AND tipoContrato = ? AND paquete{$grupo}5 = ?
                        ) AS Tabla
                    ),
                    fechaInicio = (
                        SELECT MIN(fechaInicio) 
                        FROM (
                            SELECT fechaInicio FROM {$dbName}_actividades WHERE semanaActualizacion = ? AND tipoContrato = ? AND paquete{$grupo}1 = ?
                            UNION SELECT fechaInicio FROM {$dbName}_actividades WHERE semanaActualizacion = ? AND tipoContrato = ? AND paquete{$grupo}2 = ?
                            UNION SELECT fechaInicio FROM {$dbName}_actividades WHERE semanaActualizacion = ? AND tipoContrato = ? AND paquete{$grupo}3 = ?
                            UNION SELECT fechaInicio FROM {$dbName}_actividades WHERE semanaActualizacion = ? AND tipoContrato = ? AND paquete{$grupo}4 = ?
                            UNION SELECT fechaInicio FROM {$dbName}_actividades WHERE semanaActualizacion = ? AND tipoContrato = ? AND paquete{$grupo}5 = ?
                        ) AS Tabla
                    )
                    WHERE semana = ? AND tipoPaquete = ? AND paqueteContratacion = ?";

                $paramsUpdate = [
                    $semana, $tipoContrato, $paqueteContratacion,
                    $semana, $tipoContrato, $paqueteContratacion,
                    $semana, $tipoContrato, $paqueteContratacion,
                    $semana, $tipoContrato, $paqueteContratacion,
                    $semana, $tipoContrato, $paqueteContratacion,
                    $semana, $tipoContrato, $paqueteContratacion,
                    $semana, $tipoContrato, $paqueteContratacion,
                    $semana, $tipoContrato, $paqueteContratacion,
                    $semana, $tipoContrato, $paqueteContratacion,
                    $semana, $tipoContrato, $paqueteContratacion,
                    $semana, $tipoPaquete, $paqueteContratacion,
                ];

                $db->query($sqlUpdate, $paramsUpdate);
            }

            if (!$queryUpdate) {
                pdc_insertarPaquetes($db, $dbName, $semana, $contratosVigentesSI, $contratosVigentesS, $contratosVigentesMO);
                sleep(1);
                pdc_crearSubcontratosDuplicados($db, $dbName, $semana);
                pdc_generarEstadoProceso($db, $dbName, $semana);
            } else {
                sleep(1);
                if ($contratosVigentesSI != "") {
                    $contratosVigentesSI = "WHERE " . rtrim($contratosVigentesSI, " AND ");
                }
                if ($contratosVigentesS != "") {
                    $contratosVigentesS = "WHERE " . rtrim($contratosVigentesS, " AND ");
                }
                if ($contratosVigentesMO != "") {
                    $contratosVigentesMO = "WHERE " . rtrim($contratosVigentesMO, " AND ");
                }

                pdc_insertarPaquetes($db, $dbName, $semana, $contratosVigentesSI, $contratosVigentesS, $contratosVigentesMO);
                sleep(1);
                pdc_crearSubcontratosDuplicados($db, $dbName, $semana);
                pdc_generarEstadoProceso($db, $dbName, $semana);
            }

            $db->logActivity('Sistema', 'PDC_ACTUALIZAR', "PDC actualizado para semana $semana");
            echo json_encode(["respuesta" => "BIEN"]);
        }

    } else {
        // En caso originario, si pdcActivo != 1, devuelve "BIEN" y no hace nada
        echo json_encode(["respuesta" => "BIEN"]);
    }

} catch (Throwable $e) {
    error_log("Error en actualizar_pdc.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["respuesta" => "ERROR", "mensaje" => "No se pudo actualizar el plan de compras."], JSON_UNESCAPED_UNICODE);
}

// (Funciones PDC extraídas a _pdc_functions.php)
