<?php
require_once (__DIR__ . "/../conexion.php");
// El objeto $db (instancia de Database) ya está disponible desde conexion.php

$dbPrefix = $_GET['db'] ?? '';
// Validación estricta del prefijo de la base de datos
if (!preg_match('/^[a-zA-Z0-9_]+$/', $dbPrefix)) {
    die(json_encode(["error" => "Parámetro de base de datos inválido."]));
}

$opcion = $_POST["opcion"] ?? '';
$informacion = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $opcion == "modificar") {
    $Id = $_POST['Id'];
    $tipoContrato = $_POST['tipoContrato'];
    $actividadModificar = $_POST['actividadModificar'];
    $errores = '';

    $semanaActualizacion = filter_var($_POST['semana'] ?? 0, FILTER_VALIDATE_INT);

    // Procesar todos los paquetes e insumos de forma segura
    $paquetes = [];
    $tipos = ['SI', 'S', 'MO'];
    foreach ($tipos as $t) {
        for ($i = 1; $i <= 5; $i++) {
            $pKey = "paquete$t$i";
            $iKey = "$t$i";
            $pVal = $_POST[$pKey] ?? null;
            $iVal = $_POST[$iKey] ?? null;
            $res = insumosPaquetes($pVal, $iVal);
            $paquetes["paquete$t$i"] = $res[0];
            $paquetes["$t$i"] = $res[1];
        }
    }

    if ($tipoContrato == 2 && empty($paquetes['paqueteSI1']) && empty($paquetes['paqueteSI2']) && empty($paquetes['paqueteSI3']) && empty($paquetes['paqueteSI4']) && empty($paquetes['paqueteSI5'])) {
        $errores .= "No se han asignado paquetes de contratación de Suministro e Instalación para la actividad; ";
    } else if ($tipoContrato == 1) {
        $hasMO = !empty($paquetes['paqueteMO1']) || !empty($paquetes['paqueteMO2']) || !empty($paquetes['paqueteMO3']) || !empty($paquetes['paqueteMO4']) || !empty($paquetes['paqueteMO5']);
        $hasS = !empty($paquetes['paqueteS1']) || !empty($paquetes['paqueteS2']) || !empty($paquetes['paqueteS3']) || !empty($paquetes['paqueteS4']) || !empty($paquetes['paqueteS5']);
        if (!$hasMO && !hasS) {
            $errores .= "No se han asignado paquetes de contratación de Suministro o de Mano de Obra para la actividad; ";
        }
    }

    if (!empty($errores)) {
        $stmt = false;
    } else {
        $queryUpdate = "UPDATE {$dbPrefix}_actividades SET 
            SI1=?, paqueteSI1=?, SI2=?, paqueteSI2=?, SI3=?, paqueteSI3=?, SI4=?, paqueteSI4=?, SI5=?, paqueteSI5=?, 
            S1=?, paqueteS1=?, S2=?, paqueteS2=?, S3=?, paqueteS3=?, S4=?, paqueteS4=?, S5=?, paqueteS5=?, 
            MO1=?, paqueteMO1=?, MO2=?, paqueteMO2=?, MO3=?, paqueteMO3=?, MO4=?, paqueteMO4=?, MO5=?, paqueteMO5=?, 
            semanaActualizacion=? 
            WHERE Id=?";
        
        $paramsUpdate = [
            $paquetes['SI1'], $paquetes['paqueteSI1'], $paquetes['SI2'], $paquetes['paqueteSI2'], $paquetes['SI3'], $paquetes['paqueteSI3'], $paquetes['SI4'], $paquetes['paqueteSI4'], $paquetes['SI5'], $paquetes['paqueteSI5'],
            $paquetes['S1'], $paquetes['paqueteS1'], $paquetes['S2'], $paquetes['paqueteS2'], $paquetes['S3'], $paquetes['paqueteS3'], $paquetes['S4'], $paquetes['paqueteS4'], $paquetes['S5'], $paquetes['paqueteS5'],
            $paquetes['MO1'], $paquetes['paqueteMO1'], $paquetes['MO2'], $paquetes['paqueteMO2'], $paquetes['MO3'], $paquetes['paqueteMO3'], $paquetes['MO4'], $paquetes['paqueteMO4'], $paquetes['MO5'], $paquetes['paqueteMO5'],
            $semanaActualizacion, $Id
        ];

        $stmt = $db->query($queryUpdate, $paramsUpdate);

        // Insertar en general_dias_procesos_contratacion si no existe
        $insertTargets = [
            ['SI', 'Suministro e Instalación'],
            ['MO', 'Mano de Obra'],
            ['S', 'Suministro']
        ];

        foreach ($insertTargets as $target) {
            $prefix = $target[0];
            $tipo = $target[1];
            for ($i = 1; $i <= 5; $i++) {
                $pVal = $paquetes["paquete$prefix$i"];
                if (!empty($pVal)) {
                    $queryCheck = "SELECT 1 FROM general_dias_procesos_contratacion WHERE paqueteContratacion = ? AND tipoPaquete = ?";
                    $stmtCheck = $db->query($queryCheck, [$pVal, $tipo]);
                    if (!$stmtCheck->fetch()) {
                        $queryIns = "INSERT INTO general_dias_procesos_contratacion (paqueteContratacion, tipoPaquete, diasElaboracionPliegos, diasIngresoLicify, diasEntregaPliegos, diasReciboPropuestas, diasCuadrosComparativos, diasLegalizacionContrato, diasFabricacion, diasInsumosObra) VALUES (?, ?, 1, 1, 1, 1, 1, 1, 1, 1)";
                        $db->query($queryIns, [$pVal, $tipo]);
                    }
                }
            }
        }
    }

    verificar_resultado($stmt, $errores);

} else if ($opcion == "nueva_sem") {
    $f_inicio_sem = date("Y-m-d", strtotime($_POST["f_inicio_sem"]));
    nueva_sem($f_inicio_sem, $dbPrefix, $db);
} else if ($opcion == "eliminar_sem") {
    $semana = filter_var($_POST["semana"], FILTER_VALIDATE_INT);
    eliminar_sem($semana, $dbPrefix, $db);
} else if ($opcion == "eliminar") {
    $Id = $_POST["Id"];
    eliminar($Id, $dbPrefix, $db);
} else if ($opcion == "actualizarFechaInicio") {
    $Id = $_POST["idActividad"];
    $semana = filter_var($_POST["semana"], FILTER_VALIDATE_INT);
    actualizarFechaInicio($Id, $semana, $dbPrefix, $db);
} else if ($opcion == "actualizarListadoPaquetesContratacion") {
    $tipoContrato = $_POST["tipoContrato"];
    actualizarListadoPaquetesContratacion($tipoContrato, $dbPrefix, $db);
} else if ($opcion == "actualizarInsumosRecursos") {
    $tipoContrato = $_POST["tipoContrato"];
    actualizarInsumosRecursos($tipoContrato, $dbPrefix, $db);
}

function nueva_sem($f_inicio_sem, $dbPrefix, $db) {
    require(__DIR__ . "/../funciones_generales/nueva_semana.php");
    require(__DIR__ . "/../funciones_generales/modificar_sem_estado.php");
}

function eliminar_sem($semana, $dbPrefix, $db) {
    require(__DIR__ . "/../funciones_generales/eliminar_semana.php");
}

function eliminar($Id, $dbPrefix, $db) {
    $query = "DELETE FROM {$dbPrefix}_actividades WHERE Id = ?";
    $stmt = $db->query($query, [$Id]);
    verificar_resultado($stmt, '');
}

function actualizarFechaInicio($Id, $semana, $dbPrefix, $db) {
    $query = "SELECT Fecha_Inicio FROM {$dbPrefix}_programa_consolidado WHERE Consecutivo_en_Programa = ? AND Semana = ?";
    $stmt = $db->query($query, [$Id, $semana]);
    $data = $stmt->fetch();
    echo json_encode(["data" => $data], JSON_UNESCAPED_UNICODE);
}

function actualizarListadoPaquetesContratacion($tipoContrato, $dbPrefix, $db) {
    $res = ["listadoMO" => "", "listadoS" => "", "listadoSI" => ""];
    if ($tipoContrato == 1) {
        $stmtMO = $db->query("SELECT paqueteContratacion FROM general_dias_procesos_contratacion WHERE tipoPaquete = 'Mano de Obra'");
        $scriptMO = "<option value=''></option>";
        while ($row = $stmtMO->fetch()) {
            $scriptMO .= "<option value='{$row["paqueteContratacion"]}'>{$row["paqueteContratacion"]}</option>";
        }
        $res["listadoMO"] = $scriptMO;

        $stmtS = $db->query("SELECT paqueteContratacion FROM general_dias_procesos_contratacion WHERE tipoPaquete = 'Suministro'");
        $scriptS = "<option value=''></option>";
        while ($row = $stmtS->fetch()) {
            $scriptS .= "<option value='{$row["paqueteContratacion"]}'>{$row["paqueteContratacion"]}</option>";
        }
        $res["listadoS"] = $scriptS;
    } else if ($tipoContrato == 2) {
        $stmtSI = $db->query("SELECT paqueteContratacion FROM general_dias_procesos_contratacion WHERE tipoPaquete = 'Suministro e Instalación'");
        $scriptSI = "<option value=''></option>";
        while ($row = $stmtSI->fetch()) {
            $scriptSI .= "<option value='{$row["paqueteContratacion"]}'>{$row["paqueteContratacion"]}</option>";
        }
        $res["listadoSI"] = $scriptSI;
    }
    echo json_encode($res, JSON_UNESCAPED_UNICODE);
}

function actualizarInsumosRecursos($tipoContrato, $dbPrefix, $db) {
    $res = ["listadoMO" => "", "listadoS" => "", "listadoSI" => ""];
    if ($tipoContrato == 1) {
        $queryMO = "SELECT MO1 FROM {$dbPrefix}_actividades WHERE MO1 IS NOT NULL AND MO1 != '' UNION SELECT MO2 FROM {$dbPrefix}_actividades WHERE MO2 IS NOT NULL AND MO2 != '' UNION SELECT MO3 FROM {$dbPrefix}_actividades WHERE MO3 IS NOT NULL AND MO3 != '' UNION SELECT MO4 FROM {$dbPrefix}_actividades WHERE MO4 IS NOT NULL AND MO4 != '' UNION SELECT MO5 FROM {$dbPrefix}_actividades WHERE MO5 IS NOT NULL AND MO5 != ''";
        $insumosMO = obtenerInsumosUnicos($db->query($queryMO));
        $res["listadoMO"] = generarOpcionesInsumos($insumosMO);

        $queryS = "SELECT S1 FROM {$dbPrefix}_actividades WHERE S1 IS NOT NULL AND S1 != '' UNION SELECT S2 FROM {$dbPrefix}_actividades WHERE S2 IS NOT NULL AND S2 != '' UNION SELECT S3 FROM {$dbPrefix}_actividades WHERE S3 IS NOT NULL AND S3 != '' UNION SELECT S4 FROM {$dbPrefix}_actividades WHERE S4 IS NOT NULL AND S4 != '' UNION SELECT S5 FROM {$dbPrefix}_actividades WHERE S5 IS NOT NULL AND S5 != ''";
        $insumosS = obtenerInsumosUnicos($db->query($queryS));
        $res["listadoS"] = generarOpcionesInsumos($insumosS);
    } else if ($tipoContrato == 2) {
        $querySI = "SELECT SI1 FROM {$dbPrefix}_actividades WHERE SI1 IS NOT NULL AND SI1 != '' UNION SELECT SI2 FROM {$dbPrefix}_actividades WHERE SI2 IS NOT NULL AND SI2 != '' UNION SELECT SI3 FROM {$dbPrefix}_actividades WHERE SI3 IS NOT NULL AND SI3 != '' UNION SELECT SI4 FROM {$dbPrefix}_actividades WHERE SI4 IS NOT NULL AND SI4 != '' UNION SELECT SI5 FROM {$dbPrefix}_actividades WHERE SI5 IS NOT NULL AND SI5 != ''";
        $insumosSI = obtenerInsumosUnicos($db->query($querySI));
        $res["listadoSI"] = generarOpcionesInsumos($insumosSI);
    }
    echo json_encode($res, JSON_UNESCAPED_UNICODE);
}

function obtenerInsumosUnicos($stmt) {
    $insumos = [];
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        $parts = explode(";", $row[0]);
        foreach ($parts as $p) {
            if (!empty(trim($p))) $insumos[] = trim($p);
        }
    }
    return array_unique($insumos);
}

function generarOpcionesInsumos($insumos) {
    $html = "<option value=''></option>";
    sort($insumos);
    foreach ($insumos as $i) {
        $html .= "<option value='" . htmlspecialchars($i, ENT_QUOTES) . "'>" . htmlspecialchars($i, ENT_QUOTES) . "</option>";
    }
    return $html;
}

function insumosPaquetes($paquete, $insumos) {
    if (empty($paquete)) return [null, null];
    if (empty($insumos) || !is_array($insumos)) return [$paquete, null];
    return [$paquete, implode(";", $insumos)];
}

function verificar_resultado($stmt, $errores) {
    $respuesta = ($stmt) ? "BIEN" : "ERROR";
    if (!empty($errores)) $respuesta = $errores;
    echo json_encode(["respuesta" => $respuesta]);
}
?>
