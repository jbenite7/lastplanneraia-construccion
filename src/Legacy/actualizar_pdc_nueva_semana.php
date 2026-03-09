<?php

session_start();
require_once __DIR__ . "/../../conexion.php";

/** @var Database $db */
$db = Database::getInstance();

$dbName = $_GET['db'] ?? $_POST['db'] ?? '';
$semana = (int)($_POST['semana'] ?? 0);

if (!preg_match('/^[a-zA-Z0-9_]+$/', $dbName)) {
    die(json_encode(["respuesta" => "ERROR", "mensaje" => "Nombre de base de datos inválido."]));
}

try {
    $sqlConteo = "SELECT COUNT(*) AS conteo FROM {$dbName}_pdc WHERE titulo = 0 AND semana = ? AND fechaInicio IS NOT NULL";
    $stmtConteo = $db->query($sqlConteo, [$semana]);
    $dataConteo = $stmtConteo->fetch();
    $conteo = (int)($dataConteo["conteo"] ?? 0);

    if ($conteo === 0) {
        $contratosVigentesSI = "";
        $contratosVigentesS = "";
        $contratosVigentesMO = "";

        $sqlDelete = "DELETE FROM {$dbName}_pdc WHERE (titulo = 1 AND semana = ?) OR (titulo = 0 AND fechaInicio IS NULL AND semana = ?)";
        $db->query($sqlDelete, [$semana, $semana]);

        insertarPaquetes($db, $dbName, $semana, $contratosVigentesSI, $contratosVigentesS, $contratosVigentesMO);

        sleep(1);
        generarEstadoProceso($db, $dbName, $semana);

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
                $contratosVigentesSI .= "paqueteContratacion != " . $db->quote($paqueteContratacion) . " AND ";
            } elseif ($tipoPaquete == "Suministro") {
                $tipoContrato = 1;
                $grupo = "S";
                $contratosVigentesS .= "paqueteContratacion != " . $db->quote($paqueteContratacion) . " AND ";
            } elseif ($tipoPaquete == "Mano de Obra") {
                $tipoContrato = 1;
                $grupo = "MO";
                $contratosVigentesMO .= "paqueteContratacion != " . $db->quote($paqueteContratacion) . " AND ";
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
            insertarPaquetes($db, $dbName, $semana, $contratosVigentesSI, $contratosVigentesS, $contratosVigentesMO);
            sleep(1);
            crearSubcontratosDuplicados($db, $dbName, $semana);
            generarEstadoProceso($db, $dbName, $semana);
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

            insertarPaquetes($db, $dbName, $semana, $contratosVigentesSI, $contratosVigentesS, $contratosVigentesMO);
            sleep(1);
            crearSubcontratosDuplicados($db, $dbName, $semana);
            generarEstadoProceso($db, $dbName, $semana);
        }

        $db->logActivity('Sistema', 'PDC_ACTUALIZAR', "PDC actualizado para semana $semana");
        echo json_encode(["respuesta" => "BIEN"]);
    }

} catch (Exception $e) {
    error_log("Error en actualizar_pdc_nueva_semana.php: " . $e->getMessage());
    echo json_encode(["respuesta" => "ERROR", "mensaje" => $e->getMessage()]);
}

/**
 * Funciones de soporte adaptadas
 */

function insertarPaquetes($db, $dbName, $semana, $cvSI, $cvS, $cvMO)
{
    // Definición de tipos de contrato
    $tipos = [
        ['Suministro e Instalación', 'SI', 2],
        ['Mano de Obra', 'MO', 1],
        ['Suministro', 'S', 1],
    ];

    foreach ($tipos as $t) {
        list($label, $prefix, $tipoId) = $t;

        // Insertar título
        $db->query("INSERT INTO {$dbName}_pdc (titulo, semana, tipoPaquete, paqueteContratacion) VALUES (1, ?, ?, ?)", [$semana, $label, $label]);

        // Determinar filtro dinámico
        $whereClause = "";
        if ($prefix === 'SI') {
            $whereClause = $cvSI;
        } elseif ($prefix === 'S') {
            $whereClause = $cvS;
        } elseif ($prefix === 'MO') {
            $whereClause = $cvMO;
        }

        $sqlInsert = <<<SQL
INSERT INTO {$dbName}_pdc (titulo, semana, tipoPaquete, paqueteContratacion, contratos, fechaInicio, 
              diasElaboracionPliegos, diasIngresoLicify, diasEntregaPliegos, diasReciboPropuestas, 
              diasCuadrosComparativos, diasLegalizacionContrato, diasFabricacion, diasInsumosObra,
              fechaElaboracionPliegos, fechaIngresoLicify, fechaEntregaPliegos, fechaReciboPropuestas, 
              fechaCuadrosComparativos, fechaLegalizacionContrato, fechaFabricacion, fechaInsumosObra)
SELECT 0, ?, ?, paqueteContratacion, GROUP_CONCAT(actividad SEPARATOR '; '), MIN(fechaInicio),
       diasElaboracionPliegos, diasIngresoLicify, diasEntregaPliegos, diasReciboPropuestas,
       diasCuadrosComparativos, diasLegalizacionContrato, diasFabricacion, diasInsumosObra,
       DATE_SUB(MIN(fechaInicio), INTERVAL (IFNULL(diasInsumosObra,0) + IFNULL(diasFabricacion,0) + IFNULL(diasLegalizacionContrato,0) + IFNULL(diasCuadrosComparativos,0) + IFNULL(diasReciboPropuestas,0) + IFNULL(diasEntregaPliegos,0) + IFNULL(diasIngresoLicify,0) + IFNULL(diasElaboracionPliegos,0)) DAY),
       DATE_SUB(MIN(fechaInicio), INTERVAL (IFNULL(diasInsumosObra,0) + IFNULL(diasFabricacion,0) + IFNULL(diasLegalizacionContrato,0) + IFNULL(diasCuadrosComparativos,0) + IFNULL(diasReciboPropuestas,0) + IFNULL(diasEntregaPliegos,0) + IFNULL(diasIngresoLicify,0)) DAY),
       DATE_SUB(MIN(fechaInicio), INTERVAL (IFNULL(diasInsumosObra,0) + IFNULL(diasFabricacion,0) + IFNULL(diasLegalizacionContrato,0) + IFNULL(diasCuadrosComparativos,0) + IFNULL(diasReciboPropuestas,0) + IFNULL(diasEntregaPliegos,0)) DAY),
       DATE_SUB(MIN(fechaInicio), INTERVAL (IFNULL(diasInsumosObra,0) + IFNULL(diasFabricacion,0) + IFNULL(diasLegalizacionContrato,0) + IFNULL(diasCuadrosComparativos,0) + IFNULL(diasReciboPropuestas,0)) DAY),
       DATE_SUB(MIN(fechaInicio), INTERVAL (IFNULL(diasInsumosObra,0) + IFNULL(diasFabricacion,0) + IFNULL(diasLegalizacionContrato,0) + IFNULL(diasCuadrosComparativos,0)) DAY),
       DATE_SUB(MIN(fechaInicio), INTERVAL (IFNULL(diasInsumosObra,0) + IFNULL(diasFabricacion,0) + IFNULL(diasLegalizacionContrato,0)) DAY),
       DATE_SUB(MIN(fechaInicio), INTERVAL (IFNULL(diasInsumosObra,0) + IFNULL(diasFabricacion,0)) DAY),
       DATE_SUB(MIN(fechaInicio), INTERVAL IFNULL(diasInsumosObra,0) DAY)
FROM (
    SELECT actividad, fechaInicio, paquete{$prefix}1 AS paqueteContratacion FROM {$dbName}_actividades WHERE semanaActualizacion = ? AND tipoContrato = ? AND paquete{$prefix}1 IS NOT NULL AND paquete{$prefix}1 != ''
    UNION SELECT actividad, fechaInicio, paquete{$prefix}2 AS paqueteContratacion FROM {$dbName}_actividades WHERE semanaActualizacion = ? AND tipoContrato = ? AND paquete{$prefix}2 IS NOT NULL AND paquete{$prefix}2 != ''
    UNION SELECT actividad, fechaInicio, paquete{$prefix}3 AS paqueteContratacion FROM {$dbName}_actividades WHERE semanaActualizacion = ? AND tipoContrato = ? AND paquete{$prefix}3 IS NOT NULL AND paquete{$prefix}3 != ''
    UNION SELECT actividad, fechaInicio, paquete{$prefix}4 AS paqueteContratacion FROM {$dbName}_actividades WHERE semanaActualizacion = ? AND tipoContrato = ? AND paquete{$prefix}4 IS NOT NULL AND paquete{$prefix}4 != ''
    UNION SELECT actividad, fechaInicio, paquete{$prefix}5 AS paqueteContratacion FROM {$dbName}_actividades WHERE semanaActualizacion = ? AND tipoContrato = ? AND paquete{$prefix}5 IS NOT NULL AND paquete{$prefix}5 != ''
) AS SubAct
LEFT JOIN general_paquetes_contratacion AS gpc ON SubAct.paqueteContratacion = gpc.paqueteContratacion
$whereClause
GROUP BY paqueteContratacion
SQL;

        $db->query($sqlInsert, [
            $semana, $label,
            $semana, $tipoId,
            $semana, $tipoId,
            $semana, $tipoId,
            $semana, $tipoId,
            $semana, $tipoId,
        ]);
    }
}

function crearSubcontratosDuplicados($db, $dbName, $semana)
{
    $stmt = $db->query("SELECT * FROM {$dbName}_pdc WHERE semana = ? AND titulo = 0 AND numeroSubcontratos > 1", [$semana]);
    $items = $stmt->fetchAll();

    foreach ($items as $data) {
        $consecutivo = $data["consecutivo"];
        $numeroSubcontratos = (int)$data["numeroSubcontratos"];
        $paqueteContratacion = $data["paqueteContratacion"];

        $stmtInfo = $db->query("SELECT COUNT(*) as conteo, MAX(subcontratoPaquete) as maxSub FROM {$dbName}_pdc WHERE semana = ? AND titulo = 0 AND paqueteContratacion = ?", [$semana, $paqueteContratacion]);
        $info = $stmtInfo->fetch();
        $conteoActual = (int)$info["conteo"];
        $maxSub = (int)$info["maxSub"];

        if ($conteoActual < $numeroSubcontratos) {
            for ($i = $conteoActual + 1; $i <= $numeroSubcontratos; $i++) {
                $maxSub++;
                $sqlDup = "INSERT INTO {$dbName}_pdc (semana, titulo, tipoPaquete, paqueteContratacion, contratos, subcontratoPaquete, estado, 
                           fechaElaboracionPliegos, diasIngresoLicify, fechaEntregaPliegos, diasEntregaPliegos, fechaReciboPropuestas, 
                           diasReciboPropuestas, fechaCuadrosComparativos, diasCuadrosComparativos, fechaLegalizacionContrato, 
                           diasLegalizacionContrato, fechaFabricacion, diasFabricacion, fechaInsumosObra, diasInsumosObra, fechaInicio) 
                           SELECT semana, titulo, tipoPaquete, paqueteContratacion, contratos, ?, estado, 
                                  fechaElaboracionPliegos, diasIngresoLicify, fechaEntregaPliegos, diasEntregaPliegos, fechaReciboPropuestas, 
                                  diasReciboPropuestas, fechaCuadrosComparativos, diasCuadrosComparativos, fechaLegalizacionContrato, 
                                  diasLegalizacionContrato, fechaFabricacion, diasFabricacion, fechaInsumosObra, diasInsumosObra, fechaInicio 
                           FROM {$dbName}_pdc WHERE consecutivo = ?";
                $db->query($sqlDup, [$maxSub, $consecutivo]);
            }
        }
    }
}

function generarEstadoProceso($db, $dbName, $semana)
{
    $stmt = $db->query("SELECT * FROM {$dbName}_pdc WHERE semana = ? AND titulo = 0 AND fechaInicio IS NOT NULL", [$semana]);
    $actividades = $stmt->fetchAll();

    $stmtFecha = $db->query("SELECT Fecha_Inicio_Sem FROM {$dbName}_semanas_activas WHERE Semana = ?", [$semana]);
    $dataFecha = $stmtFecha->fetch();
    $fechaActual = date('Y-m-d', strtotime($dataFecha["Fecha_Inicio_Sem"] ?? 'now'));

    foreach ($actividades as $data) {
        $fechaInicio = $data["fechaInicio"];
        $consecutivo = $data["consecutivo"];

        // Cálculos de fechas teóricas basados en duraciones
        $duraciones = [
            'elaboracion' => (int)$data["diasElaboracionPliegos"],
            'licify' => (int)$data["diasIngresoLicify"],
            'entrega' => (int)$data["diasEntregaPliegos"],
            'recibo' => (int)$data["diasReciboPropuestas"],
            'cuadros' => (int)$data["diasCuadrosComparativos"],
            'legalizacion' => (int)$data["diasLegalizacionContrato"],
            'fabricacion' => (int)$data["diasFabricacion"],
            'insumos' => (int)$data["diasInsumosObra"],
        ];

        $totalDias = array_sum($duraciones);

        $fechasCalculadas = [
            'fechaElaboracionPliegos' => date('Y-m-d', strtotime("$fechaInicio - $totalDias days")),
            'fechaIngresoLicify'      => date('Y-m-d', strtotime("$fechaInicio - " . ($totalDias - $duraciones['elaboracion']) . " days")),
            'fechaEntregaPliegos'     => date('Y-m-d', strtotime("$fechaInicio - " . ($totalDias - $duraciones['elaboracion'] - $duraciones['licify']) . " days")),
            'fechaReciboPropuestas'    => date('Y-m-d', strtotime("$fechaInicio - " . ($totalDias - $duraciones['elaboracion'] - $duraciones['licify'] - $duraciones['entrega']) . " days")),
            'fechaCuadrosComparativos' => date('Y-m-d', strtotime("$fechaInicio - " . ($duraciones['cuadros'] + $duraciones['legalizacion'] + $duraciones['fabricacion'] + $duraciones['insumos']) . " days")),
            'fechaLegalizacionContrato'=> date('Y-m-d', strtotime("$fechaInicio - " . ($duraciones['legalizacion'] + $duraciones['fabricacion'] + $duraciones['insumos']) . " days")),
            'fechaFabricacion'        => date('Y-m-d', strtotime("$fechaInicio - " . ($duraciones['fabricacion'] + $duraciones['insumos']) . " days")),
            'fechaInsumosObra'        => date('Y-m-d', strtotime("$fechaInicio - " . $duraciones['insumos'] . " days")),
        ];

        // Determinar estado
        $pasos = [
            [$data["fechaRealElaboracionPliegos"],  $fechasCalculadas['fechaElaboracionPliegos'], "Elaborando pliegos del contrato"],
            [$data["fechaRealIngresoLicify"],       $fechasCalculadas['fechaIngresoLicify'],      "Ingresando el contrato a Licify"],
            [$data["fechaRealEntregaPliegos"],      $fechasCalculadas['fechaEntregaPliegos'],     "Entregando pliegos a los proveedores invitados"],
            [$data["fechaRealReciboPropuestas"],    $fechasCalculadas['fechaReciboPropuestas'],   "Recibiendo propuestas de los proveedores invitados"],
            [$data["fechaRealCuadrosComparativos"], $fechasCalculadas['fechaCuadrosComparativos'],"Elaborando cuadros comparativos, análisis y adjudicación del contrato"],
            [$data["fechaRealLegalizacionContrato"],$fechasCalculadas['fechaLegalizacionContrato'],"En proceso de legalización del contrato"],
            [$data["fechaRealFabricacion"],         $fechasCalculadas['fechaFabricacion'],        "En periodo de fabricación, producción, importaciones, transportes, movilización, etc"],
            [$data["fechaRealInsumosObra"],         $fechasCalculadas['fechaInsumosObra'],        "En proceso de llegada de recursos, insumos y personal a la obra"],
            [$data["fechaRealInicio"],              $fechaInicio,                                 "Proceso de contratación finalizado y actividades del contrato iniciadas"],
        ];

        $posicion = -1;
        $deberiaHoy = -1;

        for ($i = 0; $i < 9; $i++) {
            if (!empty($pasos[$i][0])) {
                $posicion = $i;
            }
            if ($pasos[$i][1] <= $fechaActual) {
                $deberiaHoy = $i;
            }
        }

        $diagnostico = ($posicion >= $deberiaHoy) ? "A tiempo" : "Atrasado!!";

        if ($posicion === 8) {
            $estadoFinal = ($pasos[8][0] > $pasos[8][1]) ? "Terminado con retrasos" : "Terminado a tiempo";
        } else {
            $estadoFinal = "$diagnostico; " . ($posicion === -1 ? "Proceso de contratación no iniciado" : $pasos[$posicion][2]);
        }

        $sqlAct = "UPDATE {$dbName}_pdc SET 
                   fechaElaboracionPliegos = ?, fechaIngresoLicify = ?, fechaEntregaPliegos = ?, 
                   fechaReciboPropuestas = ?, fechaCuadrosComparativos = ?, fechaLegalizacionContrato = ?, 
                   fechaFabricacion = ?, fechaInsumosObra = ?, estado = ?
                   WHERE consecutivo = ?";

        $db->query($sqlAct, [
            $fechasCalculadas['fechaElaboracionPliegos'], $fechasCalculadas['fechaIngresoLicify'],
            $fechasCalculadas['fechaEntregaPliegos'], $fechasCalculadas['fechaReciboPropuestas'],
            $fechasCalculadas['fechaCuadrosComparativos'], $fechasCalculadas['fechaLegalizacionContrato'],
            $fechasCalculadas['fechaFabricacion'], $fechasCalculadas['fechaInsumosObra'],
            $estadoFinal, $consecutivo,
        ]);
    }
}
