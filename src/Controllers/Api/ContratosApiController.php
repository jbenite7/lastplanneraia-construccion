<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use Database;
use Exception;
use PDO;
use Throwable;

class ContratosApiController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function list()
    {
        $dbPrefix = $_GET['db'] ?? '';
        if (empty($dbPrefix) || !preg_match('/^[a-zA-Z0-9_]+$/', $dbPrefix)) {
            echo json_encode(["data" => [], "error" => "Parámetro de BD inválido."]);
            return;
        }

        $semana = filter_var($_GET['semana'] ?? 0, FILTER_VALIDATE_INT);
        $arreglo = ["data" => []];

        try {
            $db = Database::getInstance();
            $queryCount = "SELECT COUNT(*) as total FROM {$dbPrefix}_actividades WHERE semanaActualizacion = ? AND tipoContrato IS NOT NULL AND fechaInicio IS NOT NULL";
            $stmtCount = $db->query($queryCount, [$semana]);
            $conteo = $stmtCount->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

            if ($conteo == 0) {
                $arreglo["data"][] = [
                    "Id" => "", "codigo" => "", "actividad" => "", "descripcionActividad" => "", "actividadInicio" => "",
                    "nombreActividadInicio" => "", "fechaInicio" => "", "tipoContrato" => "", "semanaActualizacion" => "",
                    "SI1" => "", "paqueteSI1" => "", "SI2" => "", "paqueteSI2" => "", "SI3" => "", "paqueteSI3" => "",
                    "SI4" => "", "paqueteSI4" => "", "SI5" => "", "paqueteSI5" => "", "S1" => "", "paqueteS1" => "",
                    "S2" => "", "paqueteS2" => "", "S3" => "", "paqueteS3" => "", "S4" => "", "paqueteS4" => "",
                    "S5" => "", "paqueteS5" => "", "MO1" => "", "paqueteMO1" => "", "MO2" => "", "paqueteMO2" => "",
                    "MO3" => "", "paqueteMO3" => "", "MO4" => "", "paqueteMO4" => "", "MO5" => "", "paqueteMO5" => "",
                    "contratosAsociados" => "",
                ];
            } else {
                $queryData = "SELECT 
                        act.`Id`, act.`codigo`, act.`actividad`, act.`descripcionActividad`, act.`actividadInicio`, 
                        CONCAT(prog.`Actividad`, ' - (Inicia en: ', prog.`Fecha_Inicio`, ')') AS nombreActividadInicio, 
                        act.`fechaInicio`, act.`tipoContrato`, act.`semanaActualizacion`, 
                        act.`SI1`, act.`paqueteSI1`, act.`SI2`, act.`paqueteSI2`, act.`SI3`, act.`paqueteSI3`, act.`SI4`, act.`paqueteSI4`, act.`SI5`, act.`paqueteSI5`, 
                        act.`S1`, act.`paqueteS1`, act.`S2`, act.`paqueteS2`, act.`S3`, act.`paqueteS3`, act.`S4`, act.`paqueteS4`, act.`S5`, act.`paqueteS5`, 
                        act.`MO1`, act.`paqueteMO1`, act.`MO2`, act.`paqueteMO2`, act.`MO3`, act.`paqueteMO3`, act.`MO4`, act.`paqueteMO4`, act.`MO5`, act.`paqueteMO5` 
                    FROM {$dbPrefix}_actividades act 
                    LEFT JOIN {$dbPrefix}_programa_consolidado prog ON prog.`Consecutivo_en_Programa` = act.`actividadInicio` AND prog.`Semana` = act.`semanaActualizacion` 
                    WHERE act.semanaActualizacion = ? AND act.tipoContrato IS NOT NULL AND act.fechaInicio IS NOT NULL 
                    ORDER BY act.`Id`";

                $stmtData = $db->query($queryData, [$semana]);

                while ($data = $stmtData->fetch(PDO::FETCH_ASSOC)) {
                    $contratosAsociadosSI = "";
                    for ($i = 1; $i <= 5; $i++) {
                        if (!empty($data["paqueteSI$i"])) {
                            $contratosAsociadosSI .= $data["paqueteSI$i"] . ", ";
                        }
                    }
                    if ($contratosAsociadosSI != "") {
                        $contratosAsociadosSI = substr($contratosAsociadosSI, 0, -2);
                        $contratosAsociadosSI = str_replace(';', ", ", $contratosAsociadosSI);
                        $contratosAsociadosSI = "<b class='ct-text-danger'>- Suministro e Instalación: </b>" . $contratosAsociadosSI . ".<br>";
                    }

                    $contratosAsociadosS = "";
                    for ($i = 1; $i <= 5; $i++) {
                        if (!empty($data["paqueteS$i"])) {
                            $contratosAsociadosS .= $data["paqueteS$i"] . ", ";
                        }
                    }
                    if ($contratosAsociadosS != "") {
                        $contratosAsociadosS = substr($contratosAsociadosS, 0, -2);
                        $contratosAsociadosS = str_replace(';', ", ", $contratosAsociadosS);
                        $contratosAsociadosS = "<b class='ct-text-info'>- Suministro: </b>" . $contratosAsociadosS . ".<br> ";
                    }

                    $contratosAsociadosMO = "";
                    for ($i = 1; $i <= 5; $i++) {
                        if (!empty($data["paqueteMO$i"])) {
                            $contratosAsociadosMO .= $data["paqueteMO$i"] . ", ";
                        }
                    }
                    if ($contratosAsociadosMO != "") {
                        $contratosAsociadosMO = substr($contratosAsociadosMO, 0, -2);
                        $contratosAsociadosMO = str_replace(';', ", ", $contratosAsociadosMO);
                        $contratosAsociadosMO = "<b class='ct-text-success'>- Mano de Obra: </b>" . $contratosAsociadosMO . ".<br>";
                    }

                    $data["contratosAsociados"] = $contratosAsociadosSI . $contratosAsociadosMO . $contratosAsociadosS;
                    $arreglo["data"][] = $data;
                }
            }

            header('Content-Type: application/json');
            echo json_encode($arreglo, JSON_UNESCAPED_UNICODE);

        } catch (Throwable $e) {
            error_log("Error in ContratosApiController::list: " . $e->getMessage());
            echo json_encode(["data" => [], "error" => $e->getMessage()]);
        }
    }

    public function save()
    {
        require_once PROJECT_ROOT . '/src/Legacy/rbac_guard.php';

        $dbPrefix = $_GET['db'] ?? '';
        if (empty($dbPrefix) || !preg_match('/^[a-zA-Z0-9_]+$/', $dbPrefix)) {
            echo json_encode(["respuesta" => "ERROR", "mensaje" => "Parámetro de base de datos inválido."]);
            return;
        }

        try {
            rbac_guard_require_permission('lps.contratos.ver');

            $db = Database::getInstance();
            $opcion = $_POST["opcion"] ?? '';

            if ($_SERVER['REQUEST_METHOD'] == 'POST' && $opcion == "modificar") {
                rbac_guard_require_permission('lps.contratos.editar');

                $Id = $_POST['Id'] ?? 0;
                $tipoContrato = $_POST['tipoContrato'] ?? '';
                $actividadModificar = !empty($_POST['actividadModificar']) ? trim($_POST['actividadModificar']) : '';
                $errores = '';

                $semanaActualizacion = filter_var($_POST['semana'] ?? 0, FILTER_VALIDATE_INT);

                $paquetes = [];
                $tipos = ['SI', 'S', 'MO'];
                foreach ($tipos as $t) {
                    for ($i = 1; $i <= 5; $i++) {
                        $pKey = "paquete$t$i";
                        $iKey = "$t$i";
                        $pVal = $_POST[$pKey] ?? null;
                        $iVal = $_POST[$iKey] ?? null;
                        $res = $this->insumosPaquetes($pVal, $iVal);
                        $paquetes["paquete$t$i"] = $res[0];
                        $paquetes["$t$i"] = $res[1];
                    }
                }

                if ($tipoContrato == 2 && empty($paquetes['paqueteSI1']) && empty($paquetes['paqueteSI2']) && empty($paquetes['paqueteSI3']) && empty($paquetes['paqueteSI4']) && empty($paquetes['paqueteSI5'])) {
                    $errores .= "No se han asignado paquetes de contratación de Suministro e Instalación para la actividad; ";
                } elseif ($tipoContrato == 1) {
                    $hasMO = !empty($paquetes['paqueteMO1']) || !empty($paquetes['paqueteMO2']) || !empty($paquetes['paqueteMO3']) || !empty($paquetes['paqueteMO4']) || !empty($paquetes['paqueteMO5']);
                    $hasS = !empty($paquetes['paqueteS1']) || !empty($paquetes['paqueteS2']) || !empty($paquetes['paqueteS3']) || !empty($paquetes['paqueteS4']) || !empty($paquetes['paqueteS5']);
                    if (!$hasMO && !$hasS) {
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
                        $semanaActualizacion, $Id,
                    ];

                    $stmt = $db->query($queryUpdate, $paramsUpdate);

                    if ($stmt) {
                        $db->logActivity('Contratos', 'MODIFICAR', "Se actualizaron los paquetes de contratación para la actividad: $actividadModificar (ID: $Id)", $dbPrefix);
                    }

                    $insertTargets = [
                        ['SI', 'Suministro e Instalación'],
                        ['MO', 'Mano de Obra'],
                        ['S', 'Suministro'],
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
                                    $db->logActivity('Contratos', 'CREAR_DIAS_PROCESO', "Se creó configuración de días para el paquete: $pVal ($tipo)", $dbPrefix);
                                }
                            }
                        }
                    }
                }

                $this->verificar_resultado($stmt, $errores);

            } elseif ($opcion == "actualizarFechaInicio") {
                $this->actualizarFechaInicio($_POST["idActividad"] ?? '', filter_var($_POST["semana"] ?? 0, FILTER_VALIDATE_INT), $dbPrefix, $db);
            } elseif ($opcion == "actualizarListadoPaquetesContratacion") {
                $this->actualizarListadoPaquetesContratacion($_POST["tipoContrato"] ?? '', $dbPrefix, $db);
            } elseif ($opcion == "actualizarInsumosRecursos") {
                $this->actualizarInsumosRecursos($_POST["tipoContrato"] ?? '', $dbPrefix, $db);
            } else {
                echo json_encode(["respuesta" => "ERROR", "mensaje" => "Opción no válida"]);
            }

        } catch (Throwable $e) {
            error_log("Error in ContratosApiController::save: " . $e->getMessage());
            echo json_encode(["respuesta" => "ERROR", "mensaje" => $e->getMessage()]);
        }
    }

    private function actualizarFechaInicio($Id, $semana, $dbPrefix, $db)
    {
        $query = "SELECT Fecha_Inicio FROM {$dbPrefix}_programa_consolidado WHERE Consecutivo_en_Programa = ? AND Semana = ?";
        $stmt = $db->query($query, [$Id, $semana]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        echo json_encode(["data" => $data], JSON_UNESCAPED_UNICODE);
    }

    private function actualizarListadoPaquetesContratacion($tipoContrato, $dbPrefix, $db)
    {
        $res = ["listadoMO" => "", "listadoS" => "", "listadoSI" => ""];
        if ($tipoContrato == 1) {
            $stmtMO = $db->query("SELECT paqueteContratacion FROM general_dias_procesos_contratacion WHERE tipoPaquete = 'Mano de Obra'");
            $scriptMO = "<option value=''></option>";
            while ($row = $stmtMO->fetch()) {
                $scriptMO .= "<option value='".htmlspecialchars($row["paqueteContratacion"], ENT_QUOTES)."'>".htmlspecialchars($row["paqueteContratacion"], ENT_QUOTES)."</option>";
            }
            $res["listadoMO"] = $scriptMO;

            $stmtS = $db->query("SELECT paqueteContratacion FROM general_dias_procesos_contratacion WHERE tipoPaquete = 'Suministro'");
            $scriptS = "<option value=''></option>";
            while ($row = $stmtS->fetch()) {
                $scriptS .= "<option value='".htmlspecialchars($row["paqueteContratacion"], ENT_QUOTES)."'>".htmlspecialchars($row["paqueteContratacion"], ENT_QUOTES)."</option>";
            }
            $res["listadoS"] = $scriptS;
        } elseif ($tipoContrato == 2) {
            $stmtSI = $db->query("SELECT paqueteContratacion FROM general_dias_procesos_contratacion WHERE tipoPaquete = 'Suministro e Instalación'");
            $scriptSI = "<option value=''></option>";
            while ($row = $stmtSI->fetch()) {
                $scriptSI .= "<option value='".htmlspecialchars($row["paqueteContratacion"], ENT_QUOTES)."'>".htmlspecialchars($row["paqueteContratacion"], ENT_QUOTES)."</option>";
            }
            $res["listadoSI"] = $scriptSI;
        }
        echo json_encode($res, JSON_UNESCAPED_UNICODE);
    }

    private function actualizarInsumosRecursos($tipoContrato, $dbPrefix, $db)
    {
        $res = ["listadoMO" => "", "listadoS" => "", "listadoSI" => ""];
        if ($tipoContrato == 1) {
            $queryMO = "SELECT MO1 FROM {$dbPrefix}_actividades WHERE MO1 IS NOT NULL AND MO1 != '' UNION SELECT MO2 FROM {$dbPrefix}_actividades WHERE MO2 IS NOT NULL AND MO2 != '' UNION SELECT MO3 FROM {$dbPrefix}_actividades WHERE MO3 IS NOT NULL AND MO3 != '' UNION SELECT MO4 FROM {$dbPrefix}_actividades WHERE MO4 IS NOT NULL AND MO4 != '' UNION SELECT MO5 FROM {$dbPrefix}_actividades WHERE MO5 IS NOT NULL AND MO5 != ''";
            $insumosMO = $this->obtenerInsumosUnicos($db->query($queryMO));
            $res["listadoMO"] = $this->generarOpcionesInsumos($insumosMO);

            $queryS = "SELECT S1 FROM {$dbPrefix}_actividades WHERE S1 IS NOT NULL AND S1 != '' UNION SELECT S2 FROM {$dbPrefix}_actividades WHERE S2 IS NOT NULL AND S2 != '' UNION SELECT S3 FROM {$dbPrefix}_actividades WHERE S3 IS NOT NULL AND S3 != '' UNION SELECT S4 FROM {$dbPrefix}_actividades WHERE S4 IS NOT NULL AND S4 != '' UNION SELECT S5 FROM {$dbPrefix}_actividades WHERE S5 IS NOT NULL AND S5 != ''";
            $insumosS = $this->obtenerInsumosUnicos($db->query($queryS));
            $res["listadoS"] = $this->generarOpcionesInsumos($insumosS);
        } elseif ($tipoContrato == 2) {
            $querySI = "SELECT SI1 FROM {$dbPrefix}_actividades WHERE SI1 IS NOT NULL AND SI1 != '' UNION SELECT SI2 FROM {$dbPrefix}_actividades WHERE SI2 IS NOT NULL AND SI2 != '' UNION SELECT SI3 FROM {$dbPrefix}_actividades WHERE SI3 IS NOT NULL AND SI3 != '' UNION SELECT SI4 FROM {$dbPrefix}_actividades WHERE SI4 IS NOT NULL AND SI4 != '' UNION SELECT SI5 FROM {$dbPrefix}_actividades WHERE SI5 IS NOT NULL AND SI5 != ''";
            $insumosSI = $this->obtenerInsumosUnicos($db->query($querySI));
            $res["listadoSI"] = $this->generarOpcionesInsumos($insumosSI);
        }
        echo json_encode($res, JSON_UNESCAPED_UNICODE);
    }

    private function obtenerInsumosUnicos($stmt)
    {
        $insumos = [];
        while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
            if (empty($row[0])) {
                continue;
            }
            $parts = explode(";", $row[0]);
            foreach ($parts as $p) {
                $trimmed = trim($p);
                if ($trimmed !== '') {
                    $insumos[] = $trimmed;
                }
            }
        }

        return array_unique($insumos);
    }

    private function generarOpcionesInsumos($insumos)
    {
        $html = "<option value=''></option>";
        sort($insumos);
        foreach ($insumos as $i) {
            $html .= "<option value='" . htmlspecialchars($i, ENT_QUOTES) . "'>" . htmlspecialchars($i, ENT_QUOTES) . "</option>";
        }

        return $html;
    }

    private function insumosPaquetes($paquete, $insumos)
    {
        if (empty($paquete)) {
            return [null, null];
        }
        if (empty($insumos) || !is_array($insumos)) {
            return [$paquete, null];
        }

        return [$paquete, implode(";", $insumos)];
    }

    private function verificar_resultado($stmt, $errores)
    {
        $respuesta = ($stmt) ? "BIEN" : "ERROR";
        $mensaje = "";
        if (!empty($errores)) {
            $respuesta = "ERROR";
            $mensaje = $errores;
        }
        echo json_encode(["respuesta" => $respuesta, "mensaje" => $mensaje]);
    }
}
