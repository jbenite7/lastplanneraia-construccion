<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Support\ModuleRequestContext;
use Database;
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
        try {
            $context = ModuleRequestContext::resolve();
            $dbPrefix = $context['dbPrefix'];
            $semana = $context['semana'];
            $arreglo = ["data" => []];

            $this->requirePermission('lps.contratos.ver', 'No autorizado para consultar contratos.');

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
                    "OC1" => "", "paqueteOC1" => "", "OC2" => "", "paqueteOC2" => "", "OC3" => "", "paqueteOC3" => "", "OC4" => "", "paqueteOC4" => "", "OC5" => "", "paqueteOC5" => "",
                    "contratosAsociados" => "",
                ];
            } else {
                $queryData = "SELECT 
                        act.`Id`, act.`codigo`, act.`actividad`, act.`descripcionActividad`, act.`actividadInicio`, 
                        CONCAT(prog.`Actividad`, ' - (Inicia en: ', prog.`Fecha_Inicio`, ')') AS nombreActividadInicio, 
                        act.`fechaInicio`, act.`tipoContrato`, act.`semanaActualizacion`, 
                        act.`SI1`, act.`paqueteSI1`, act.`SI2`, act.`paqueteSI2`, act.`SI3`, act.`paqueteSI3`, act.`SI4`, act.`paqueteSI4`, act.`SI5`, act.`paqueteSI5`, 
                        act.`S1`, act.`paqueteS1`, act.`S2`, act.`paqueteS2`, act.`S3`, act.`paqueteS3`, act.`S4`, act.`paqueteS4`, act.`S5`, act.`paqueteS5`, 
                        act.`MO1`, act.`paqueteMO1`, act.`MO2`, act.`paqueteMO2`, act.`MO3`, act.`paqueteMO3`, act.`MO4`, act.`paqueteMO4`, act.`MO5`, act.`paqueteMO5`, 
                        act.`OC1`, act.`paqueteOC1`, act.`OC2`, act.`paqueteOC2`, act.`OC3`, act.`paqueteOC3`, act.`OC4`, act.`paqueteOC4`, act.`OC5`, act.`paqueteOC5` 
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
                        $contratosAsociadosSI = $this->escapeHtml($contratosAsociadosSI);
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
                        $contratosAsociadosS = $this->escapeHtml($contratosAsociadosS);
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
                        $contratosAsociadosMO = $this->escapeHtml($contratosAsociadosMO);
                        $contratosAsociadosMO = "<b class='ct-text-success'>- Mano de Obra: </b>" . $contratosAsociadosMO . ".<br>";
                    }

                    $contratosAsociadosOC = "";
                    for ($i = 1; $i <= 5; $i++) {
                        if (!empty($data["paqueteOC$i"])) {
                            $contratosAsociadosOC .= $data["paqueteOC$i"] . ", ";
                        }
                    }
                    if ($contratosAsociadosOC != "") {
                        $contratosAsociadosOC = substr($contratosAsociadosOC, 0, -2);
                        $contratosAsociadosOC = str_replace(';', ", ", $contratosAsociadosOC);
                        $contratosAsociadosOC = $this->escapeHtml($contratosAsociadosOC);
                        $contratosAsociadosOC = "<b class='ct-text-dark'>- Orden de Compra: </b>" . $contratosAsociadosOC . ".<br> ";
                    }

                    $data["contratosAsociados"] = $contratosAsociadosSI . $contratosAsociadosMO . $contratosAsociadosS . $contratosAsociadosOC;
                    $arreglo["data"][] = $data;
                }
            }

            $this->jsonResponse($arreglo);

        } catch (Throwable $e) {
            error_log("Error in ContratosApiController::list: " . $e->getMessage());
            $this->jsonError('No se pudo cargar la informacion de contratos.', 500, ["data" => []]);
        }
    }

    public function save()
    {
        try {
            $context = ModuleRequestContext::resolve();
            $dbPrefix = $context['dbPrefix'];
            $semanaActualizacion = $context['semana'];

            $this->requirePermission('lps.contratos.ver', 'No autorizado para consultar contratos.');

            $db = Database::getInstance();
            $opcion = $_POST["opcion"] ?? '';

            if ($_SERVER['REQUEST_METHOD'] == 'POST' && $opcion == "modificar") {
                $this->requirePermission('lps.contratos.editar', 'No autorizado para modificar contratos.');

                $Id = $_POST['Id'] ?? 0;
                $tipoContrato = $_POST['tipoContrato'] ?? '';
                $actividadModificar = !empty($_POST['actividadModificar']) ? trim($_POST['actividadModificar']) : '';
                $errores = '';

                $paquetes = [];
                $tipos = ['SI', 'S', 'MO', 'OC'];
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

                $modalidades = array_map('trim', explode(',', $tipoContrato));

                if (in_array('SI', $modalidades) && empty($paquetes['paqueteSI1']) && empty($paquetes['paqueteSI2']) && empty($paquetes['paqueteSI3']) && empty($paquetes['paqueteSI4']) && empty($paquetes['paqueteSI5'])) {
                    $errores .= "No se han asignado paquetes de contratación de Suministro e Instalación para la actividad; ";
                }
                if (in_array('MO', $modalidades) || in_array('S', $modalidades)) {
                    $hasMO = !empty($paquetes['paqueteMO1']) || !empty($paquetes['paqueteMO2']) || !empty($paquetes['paqueteMO3']) || !empty($paquetes['paqueteMO4']) || !empty($paquetes['paqueteMO5']);
                    $hasS = !empty($paquetes['paqueteS1']) || !empty($paquetes['paqueteS2']) || !empty($paquetes['paqueteS3']) || !empty($paquetes['paqueteS4']) || !empty($paquetes['paqueteS5']);
                    if (!$hasMO && !$hasS) {
                        $errores .= "No se han asignado paquetes de contratación de Suministro o de Mano de Obra para la actividad; ";
                    }
                }
                if (in_array('OC', $modalidades) && empty($paquetes['paqueteOC1']) && empty($paquetes['paqueteOC2']) && empty($paquetes['paqueteOC3']) && empty($paquetes['paqueteOC4']) && empty($paquetes['paqueteOC5'])) {
                    $errores .= "No se han asignado paquetes de contratación de Orden de Compra para la actividad; ";
                }

                if (!empty($errores)) {
                    $stmt = false;
                } else {
                    $queryUpdate = "UPDATE {$dbPrefix}_actividades SET 
                        SI1=?, paqueteSI1=?, SI2=?, paqueteSI2=?, SI3=?, paqueteSI3=?, SI4=?, paqueteSI4=?, SI5=?, paqueteSI5=?, 
                        S1=?, paqueteS1=?, S2=?, paqueteS2=?, S3=?, paqueteS3=?, S4=?, paqueteS4=?, S5=?, paqueteS5=?, 
                        MO1=?, paqueteMO1=?, MO2=?, paqueteMO2=?, MO3=?, paqueteMO3=?, MO4=?, paqueteMO4=?, MO5=?, paqueteMO5=?, 
                        OC1=?, paqueteOC1=?, OC2=?, paqueteOC2=?, OC3=?, paqueteOC3=?, OC4=?, paqueteOC4=?, OC5=?, paqueteOC5=?, 
                        semanaActualizacion=? 
                        WHERE Id=? AND semanaActualizacion=?";

                    $paramsUpdate = [
                        $paquetes['SI1'], $paquetes['paqueteSI1'], $paquetes['SI2'], $paquetes['paqueteSI2'], $paquetes['SI3'], $paquetes['paqueteSI3'], $paquetes['SI4'], $paquetes['paqueteSI4'], $paquetes['SI5'], $paquetes['paqueteSI5'],
                        $paquetes['S1'], $paquetes['paqueteS1'], $paquetes['S2'], $paquetes['paqueteS2'], $paquetes['S3'], $paquetes['paqueteS3'], $paquetes['S4'], $paquetes['paqueteS4'], $paquetes['S5'], $paquetes['paqueteS5'],
                        $paquetes['MO1'], $paquetes['paqueteMO1'], $paquetes['MO2'], $paquetes['paqueteMO2'], $paquetes['MO3'], $paquetes['paqueteMO3'], $paquetes['MO4'], $paquetes['paqueteMO4'], $paquetes['MO5'], $paquetes['paqueteMO5'],
                        $paquetes['OC1'], $paquetes['paqueteOC1'], $paquetes['OC2'], $paquetes['paqueteOC2'], $paquetes['OC3'], $paquetes['paqueteOC3'], $paquetes['OC4'], $paquetes['paqueteOC4'], $paquetes['OC5'], $paquetes['paqueteOC5'],
                        $semanaActualizacion, $Id, $semanaActualizacion,
                    ];

                    $stmt = $db->query($queryUpdate, $paramsUpdate);

                    if ($stmt) {
                        $db->logActivity('Contratos', 'MODIFICAR', "Se actualizaron los paquetes de contratación para la actividad: $actividadModificar (ID: $Id)", $dbPrefix);
                    }

                    $insertTargets = [
                        ['SI', 'Suministro e Instalación'],
                        ['MO', 'Mano de Obra'],
                        ['S', 'Suministro'],
                        ['OC', 'Orden de Compra'],
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
                                    $queryIns = "INSERT INTO general_dias_procesos_contratacion (paqueteContratacion, tipoPaquete, diasElaboracionPliegos, diasEntregaPliegos, diasReciboPropuestas, diasCuadrosComparativos, diasLegalizacionContrato, diasFabricacion, diasInsumosObra) VALUES (?, ?, 1, 1, 1, 1, 1, 1, 1)";
                                    $db->query($queryIns, [$pVal, $tipo]);
                                    $db->logActivity('Contratos', 'CREAR_DIAS_PROCESO', "Se creó configuración de días para el paquete: $pVal ($tipo)", $dbPrefix);
                                }
                            }
                        }
                    }
                }

                $this->verificar_resultado($stmt, $errores);

            } elseif ($opcion == "actualizarFechaInicio") {
                $this->actualizarFechaInicio($_POST["idActividad"] ?? '', $semanaActualizacion, $dbPrefix, $db);
            } elseif ($opcion == "actualizarListadoPaquetesContratacion") {
                $this->actualizarListadoPaquetesContratacion($_POST["tipoContrato"] ?? '', $dbPrefix, $db);
            } elseif ($opcion == "actualizarInsumosRecursos") {
                $this->actualizarInsumosRecursos($_POST["tipoContrato"] ?? '', $dbPrefix, $db, $semanaActualizacion);
            } else {
                $this->jsonError('Opción no válida.');
            }

        } catch (Throwable $e) {
            error_log("Error in ContratosApiController::save: " . $e->getMessage());
            $this->jsonError('No se pudo procesar la solicitud de contratos.', 500);
        }
    }

    public function autoAssign(): void
    {
        try {
            $context = ModuleRequestContext::resolve();
            $dbPrefix = $context['dbPrefix'];
            $semana = $context['semana'];

            $this->requirePermission('lps.contratos.editar', 'No autorizado para auto-asignar contratos.');

            $db = Database::getInstance();

            $stmt = $db->query(
                "SELECT Id, codigo, actividad, descripcionActividad, actividadInicio, fechaInicio, tipoContrato
                 FROM {$dbPrefix}_actividades
                 WHERE semanaActualizacion = ? AND (tipoContrato IS NULL OR tipoContrato = '')
                 ORDER BY Id ASC",
                [$semana]
            );
            $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($activities)) {
                $this->jsonResponse([
                    'respuesta' => 'BIEN',
                    'mensaje' => 'No hay actividades pendientes por asignar. Todas ya tienen tipo de contrato.',
                    'asignadas' => 0,
                    'sinMatch' => 0,
                    'total' => 0,
                    'sugerencias' => [],
                ]);
                return;
            }

            $matcher = new \App\Support\ActivityMatcher();
            $rules = $matcher->loadRules();
            $optionsByFamily = $this->loadFamilyContractOptions($db);

            $asignadas = 0;
            $sinMatch = 0;
            $sugerencias = [];

            foreach ($activities as $activity) {
                $actId = (int) $activity['Id'];

                $pgActivity = $this->loadPgActivityForContratos($db, $dbPrefix, $semana, $activity['actividadInicio'] ?? '');
                if ($pgActivity === null) {
                    $sinMatch++;
                    $sugerencias[] = [
                        'Id' => $actId,
                        'actividad' => $activity['actividad'] ?? '',
                        'match' => false,
                        'motivo' => 'Sin actividad vinculada en PG',
                    ];
                    continue;
                }

                $match = $matcher->matchActivity($pgActivity, $rules);
                if ($match === null) {
                    $sinMatch++;
                    $sugerencias[] = [
                        'Id' => $actId,
                        'actividad' => $activity['actividad'] ?? '',
                        'match' => false,
                        'motivo' => 'Sin familia detectada',
                    ];
                    continue;
                }

                $familyId = (int) $match['familia_id'];
                $family = $optionsByFamily[$familyId] ?? null;
                $options = $family['opciones'] ?? [];

                if (empty($options)) {
                    $sinMatch++;
                    $sugerencias[] = [
                        'Id' => $actId,
                        'actividad' => $activity['actividad'] ?? '',
                        'match' => true,
                        'familia' => $match['familia_nombre'] ?? '',
                        'familiaCodigo' => $match['familia_codigo'] ?? '',
                        'confianza' => (int) ($match['confianza'] ?? 0),
                        'motivo' => 'Familia sin opciones de contrato configuradas',
                    ];
                    continue;
                }

                $bestOption = $this->selectBestContratoOption($options, $match);
                if ($bestOption === null) {
                    $sinMatch++;
                    $sugerencias[] = [
                        'Id' => $actId,
                        'actividad' => $activity['actividad'] ?? '',
                        'match' => true,
                        'familia' => $match['familia_nombre'] ?? '',
                        'motivo' => 'No se pudo seleccionar opción de contrato',
                    ];
                    continue;
                }

                $tipoContratoInt = (int) $bestOption['tipo_contrato'];
                $tipoContrato = $this->intToModalityCode($tipoContratoInt);

                $result = $this->assignContratoToActivity($db, $dbPrefix, $actId, $tipoContrato, $bestOption['items'], $semana);
                if ($result) {
                    $asignadas++;
                    $sugerencias[] = [
                        'Id' => $actId,
                        'actividad' => $activity['actividad'] ?? '',
                        'match' => true,
                        'familia' => $match['familia_nombre'] ?? '',
                        'familiaCodigo' => $match['familia_codigo'] ?? '',
                        'confianza' => (int) ($match['confianza'] ?? 0),
                        'tipoContrato' => $tipoContrato,
                        'tipoContratoLabel' => $this->modalityCodeToLabel($tipoContrato),
                        'paquetes' => $this->formatAssignedPackages($bestOption['items']),
                        'asignada' => true,
                    ];
                } else {
                    $sinMatch++;
                    $sugerencias[] = [
                        'Id' => $actId,
                        'actividad' => $activity['actividad'] ?? '',
                        'match' => true,
                        'familia' => $match['familia_nombre'] ?? '',
                        'motivo' => 'Error al actualizar la actividad',
                    ];
                }
            }

            $db->logActivity('Contratos', 'AUTO_ASIGNAR', "Auto-asignó contratos: {$asignadas} asignadas, {$sinMatch} sin match", $dbPrefix);

            $this->jsonResponse([
                'respuesta' => 'BIEN',
                'asignadas' => $asignadas,
                'sinMatch' => $sinMatch,
                'total' => count($activities),
                'sugerencias' => $sugerencias,
            ]);

        } catch (Throwable $e) {
            error_log("Error en ContratosApiController@autoAssign: " . $e->getMessage());
            $this->jsonError('No se pudo auto-asignar contratos.', 500);
        }
    }
    private function actualizarFechaInicio($Id, $semana, $dbPrefix, $db)
    {
        $query = "SELECT Fecha_Inicio FROM {$dbPrefix}_programa_consolidado WHERE Semana = ? AND (Consecutivo_en_Programa = ? OR Actividad = ?) ORDER BY Fecha_Inicio ASC LIMIT 1";
        $stmt = $db->query($query, [$semana, $Id, $Id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->jsonResponse(["data" => $data ?: ["Fecha_Inicio" => ""]]);
    }

    private function actualizarListadoPaquetesContratacion($tipoContrato, $dbPrefix, $db)
    {
        $res = ["listadoMO" => "", "listadoS" => "", "listadoSI" => "", "listadoOC" => ""];
        $modalidades = array_map('trim', explode(',', $tipoContrato));

        if (in_array('MO', $modalidades) || in_array('S', $modalidades)) {
            $stmtMO = $db->query("SELECT paqueteContratacion FROM general_dias_procesos_contratacion WHERE tipoPaquete = 'Mano de Obra'");
            $scriptMO = "<option value=''></option>";
            while ($row = $stmtMO->fetch()) {
                $scriptMO .= "<option value='" . htmlspecialchars($row["paqueteContratacion"], ENT_QUOTES) . "'>" . htmlspecialchars($row["paqueteContratacion"], ENT_QUOTES) . "</option>";
            }
            $res["listadoMO"] = $scriptMO;

            $stmtS = $db->query("SELECT paqueteContratacion FROM general_dias_procesos_contratacion WHERE tipoPaquete = 'Suministro'");
            $scriptS = "<option value=''></option>";
            while ($row = $stmtS->fetch()) {
                $scriptS .= "<option value='" . htmlspecialchars($row["paqueteContratacion"], ENT_QUOTES) . "'>" . htmlspecialchars($row["paqueteContratacion"], ENT_QUOTES) . "</option>";
            }
            $res["listadoS"] = $scriptS;
        }

        if (in_array('SI', $modalidades)) {
            $stmtSI = $db->query("SELECT paqueteContratacion FROM general_dias_procesos_contratacion WHERE tipoPaquete = 'Suministro e Instalación'");
            $scriptSI = "<option value=''></option>";
            while ($row = $stmtSI->fetch()) {
                $scriptSI .= "<option value='" . htmlspecialchars($row["paqueteContratacion"], ENT_QUOTES) . "'>" . htmlspecialchars($row["paqueteContratacion"], ENT_QUOTES) . "</option>";
            }
            $res["listadoSI"] = $scriptSI;
        }

        if (in_array('OC', $modalidades)) {
            $stmtOC = $db->query("SELECT paqueteContratacion FROM general_dias_procesos_contratacion WHERE tipoPaquete = 'Orden de Compra'");
            $scriptOC = "<option value=''></option>";
            while ($row = $stmtOC->fetch()) {
                $scriptOC .= "<option value='" . htmlspecialchars($row["paqueteContratacion"], ENT_QUOTES) . "'>" . htmlspecialchars($row["paqueteContratacion"], ENT_QUOTES) . "</option>";
            }
            $res["listadoOC"] = $scriptOC;
        }
        $this->jsonResponse($res);
    }

    private function actualizarInsumosRecursos($tipoContrato, $dbPrefix, $db, $semana)
    {
        $res = ["listadoMO" => "", "listadoS" => "", "listadoSI" => "", "listadoOC" => ""];
        $modalidades = array_map('trim', explode(',', $tipoContrato));

        if (in_array('MO', $modalidades) || in_array('S', $modalidades)) {
            $queryMO = "SELECT MO1 FROM {$dbPrefix}_actividades WHERE semanaActualizacion = ? AND MO1 IS NOT NULL AND MO1 != '' UNION SELECT MO2 FROM {$dbPrefix}_actividades WHERE semanaActualizacion = ? AND MO2 IS NOT NULL AND MO2 != '' UNION SELECT MO3 FROM {$dbPrefix}_actividades WHERE semanaActualizacion = ? AND MO3 IS NOT NULL AND MO3 != '' UNION SELECT MO4 FROM {$dbPrefix}_actividades WHERE semanaActualizacion = ? AND MO4 IS NOT NULL AND MO4 != '' UNION SELECT MO5 FROM {$dbPrefix}_actividades WHERE semanaActualizacion = ? AND MO5 IS NOT NULL AND MO5 != ''";
            $insumosMO = $this->obtenerInsumosUnicos($db->query($queryMO, [$semana, $semana, $semana, $semana, $semana]));
            $res["listadoMO"] = $this->generarOpcionesInsumos($insumosMO);

            $queryS = "SELECT S1 FROM {$dbPrefix}_actividades WHERE semanaActualizacion = ? AND S1 IS NOT NULL AND S1 != '' UNION SELECT S2 FROM {$dbPrefix}_actividades WHERE semanaActualizacion = ? AND S2 IS NOT NULL AND S2 != '' UNION SELECT S3 FROM {$dbPrefix}_actividades WHERE semanaActualizacion = ? AND S3 IS NOT NULL AND S3 != '' UNION SELECT S4 FROM {$dbPrefix}_actividades WHERE semanaActualizacion = ? AND S4 IS NOT NULL AND S4 != '' UNION SELECT S5 FROM {$dbPrefix}_actividades WHERE semanaActualizacion = ? AND S5 IS NOT NULL AND S5 != ''";
            $insumosS = $this->obtenerInsumosUnicos($db->query($queryS, [$semana, $semana, $semana, $semana, $semana]));
            $res["listadoS"] = $this->generarOpcionesInsumos($insumosS);
        }

        if (in_array('SI', $modalidades)) {
            $querySI = "SELECT SI1 FROM {$dbPrefix}_actividades WHERE semanaActualizacion = ? AND SI1 IS NOT NULL AND SI1 != '' UNION SELECT SI2 FROM {$dbPrefix}_actividades WHERE semanaActualizacion = ? AND SI2 IS NOT NULL AND SI2 != '' UNION SELECT SI3 FROM {$dbPrefix}_actividades WHERE semanaActualizacion = ? AND SI3 IS NOT NULL AND SI3 != '' UNION SELECT SI4 FROM {$dbPrefix}_actividades WHERE semanaActualizacion = ? AND SI4 IS NOT NULL AND SI4 != '' UNION SELECT SI5 FROM {$dbPrefix}_actividades WHERE semanaActualizacion = ? AND SI5 IS NOT NULL AND SI5 != ''";
            $insumosSI = $this->obtenerInsumosUnicos($db->query($querySI, [$semana, $semana, $semana, $semana, $semana]));
            $res["listadoSI"] = $this->generarOpcionesInsumos($insumosSI);
        }

        if (in_array('OC', $modalidades)) {
            $queryOC = "SELECT OC1 FROM {$dbPrefix}_actividades WHERE semanaActualizacion = ? AND OC1 IS NOT NULL AND OC1 != '' UNION SELECT OC2 FROM {$dbPrefix}_actividades WHERE semanaActualizacion = ? AND OC2 IS NOT NULL AND OC2 != '' UNION SELECT OC3 FROM {$dbPrefix}_actividades WHERE semanaActualizacion = ? AND OC3 IS NOT NULL AND OC3 != '' UNION SELECT OC4 FROM {$dbPrefix}_actividades WHERE semanaActualizacion = ? AND OC4 IS NOT NULL AND OC4 != '' UNION SELECT OC5 FROM {$dbPrefix}_actividades WHERE semanaActualizacion = ? AND OC5 IS NOT NULL AND OC5 != ''";
            $insumosOC = $this->obtenerInsumosUnicos($db->query($queryOC, [$semana, $semana, $semana, $semana, $semana]));
            $res["listadoOC"] = $this->generarOpcionesInsumos($insumosOC);
        }
        $this->jsonResponse($res);
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
        $this->jsonResponse(["respuesta" => $respuesta, "mensaje" => $mensaje]);
    }

    private function jsonResponse(array $data): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    private function jsonError(string $message, int $httpCode = 400, array $extra = []): void
    {
        http_response_code($httpCode);
        $this->jsonResponse(array_merge([
            'respuesta' => 'ERROR',
            'mensaje' => $message,
        ], $extra));
    }

    private function requirePermission(string $permissionKey, string $message): void
    {
        require_once PROJECT_ROOT . '/src/Legacy/rbac_guard.php';
        rbac_guard_require_permission($permissionKey, ['message' => $message]);
    }

    private function escapeHtml(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    private function loadFamilyContractOptions($db): array
    {
        $stmt = $db->query(
            "SELECT f.id AS familia_id, f.codigo AS familia_codigo, f.nombre AS familia_nombre,
                    o.id AS option_id, o.tipo_contrato, o.tipo_paquete,
                    i.id AS item_id, COALESCE(i.tipo_contrato, o.tipo_contrato) AS item_tipo_contrato,
                    COALESCE(i.tipo_paquete, o.tipo_paquete) AS item_tipo_paquete, i.paquete_nombre
             FROM general_pdc_familias f
             LEFT JOIN general_pdc_family_contract_options o ON o.familia_id = f.id AND o.activa = 1
             LEFT JOIN general_pdc_family_contract_option_items i ON i.option_id = o.id
             ORDER BY f.orden ASC, o.tipo_paquete ASC, i.orden ASC"
        );

        $families = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $familyId = (int) $row['familia_id'];
            if (!isset($families[$familyId])) {
                $families[$familyId] = [
                    'familiaId' => $familyId,
                    'familiaCodigo' => $row['familia_codigo'],
                    'familiaNombre' => $row['familia_nombre'],
                    'opciones' => [],
                ];
            }
            if (empty($row['option_id'])) {
                continue;
            }
            $optionId = (int) $row['option_id'];
            if (!isset($families[$familyId]['opciones'][$optionId])) {
                $families[$familyId]['opciones'][$optionId] = [
                    'optionId' => $optionId,
                    'tipo_contrato' => (int) $row['tipo_contrato'],
                    'tipo_paquete' => $row['tipo_paquete'],
                    'items' => [],
                ];
            }
            if (!empty($row['item_id'])) {
                $families[$familyId]['opciones'][$optionId]['items'][] = [
                    'item_id' => (int) $row['item_id'],
                    'tipo_contrato' => (int) $row['item_tipo_contrato'],
                    'tipo_paquete' => $row['item_tipo_paquete'],
                    'paquete_nombre' => $row['paquete_nombre'],
                ];
            }
        }

        foreach ($families as &$family) {
            $family['opciones'] = array_values($family['opciones']);
        }
        unset($family);

        return $families;
    }

    private function loadPgActivityForContratos($db, string $dbPrefix, int $semana, string $actividadInicio): ?array
    {
        if (empty($actividadInicio)) {
            return null;
        }

        $stmt = $db->query(
            "SELECT Consecutivo, Consecutivo_en_Programa, Id, Actividad, Fecha_Inicio, COALESCE(Titulo, 0) AS Titulo
             FROM {$dbPrefix}_programa_consolidado
             WHERE Semana = ? AND Consecutivo_en_Programa = ? LIMIT 1",
            [$semana, $actividadInicio]
        );
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        $row['__capitulo'] = '';
        return $row;
    }

    private function selectBestContratoOption(array $options, array $match): ?array
    {
        $modalidadSugerida = $match['modalidad_sugerida'] ?? '';

        foreach ($options as $option) {
            if ($option['tipo_paquete'] === $modalidadSugerida && !empty($option['items'])) {
                return $option;
            }
        }

        foreach ($options as $option) {
            if (!empty($option['items'])) {
                return $option;
            }
        }

        return null;
    }

    private function assignContratoToActivity($db, string $dbPrefix, int $actId, string $tipoContrato, array $items, int $semana): bool
    {
        try {
            $prefixMap = [
                'Suministro e Instalación' => 'SI',
                'Suministro' => 'S',
                'Mano de Obra' => 'MO',
                'Orden de Compra' => 'OC',
            ];

            $updates = ['tipoContrato = ?'];
            $params = [$tipoContrato];

            $tipos = ['SI', 'S', 'MO', 'OC'];
            foreach ($tipos as $t) {
                for ($i = 1; $i <= 5; $i++) {
                    $updates[] = "paquete{$t}{$i} = NULL";
                    $updates[] = "{$t}{$i} = NULL";
                }
            }

            $packageCounts = ['SI' => 0, 'S' => 0, 'MO' => 0, 'OC' => 0];
            foreach ($items as $item) {
                $itemTipoPaquete = $item['tipo_paquete'] ?? '';
                $prefix = $prefixMap[$itemTipoPaquete] ?? null;
                if ($prefix === null) {
                    continue;
                }

                $paqueteNombre = trim((string) ($item['paquete_nombre'] ?? ''));
                if ($paqueteNombre === '') {
                    continue;
                }

                $slotIndex = $packageCounts[$prefix] + 1;
                if ($slotIndex > 5) {
                    continue;
                }

                $updates[] = "paquete{$prefix}{$slotIndex} = ?";
                $params[] = $paqueteNombre;
                $packageCounts[$prefix] = $slotIndex;
            }

            $params[] = $actId;
            $params[] = $semana;

            $sql = "UPDATE {$dbPrefix}_actividades SET " . implode(', ', $updates) . " WHERE Id = ? AND semanaActualizacion = ?";
            $db->query($sql, $params);

            return true;
        } catch (Throwable $e) {
            error_log("Error en assignContratoToActivity: " . $e->getMessage());
            return false;
        }
    }

    private function formatAssignedPackages(array $items): array
    {
        $packages = [];
        foreach ($items as $item) {
            $packages[] = [
                'tipoPaquete' => $item['tipo_paquete'] ?? '',
                'paqueteNombre' => $item['paquete_nombre'] ?? '',
            ];
        }
        return $packages;
    }

    /**
     * Convierte el entero tipo_contrato de general_pdc_family_contract_options
     * al codigo de modalidad separado por comas.
     */
    private function intToModalityCode(int $tipoContrato): string
    {
        return match ($tipoContrato) {
            1 => 'MO,S',
            2 => 'SI',
            3 => 'S',
            4 => 'MO',
            5 => 'OC',
            default => '',
        };
    }

    /**
     * Convierte el codigo de modalidad comma-separated a etiqueta legible.
     */
    private function modalityCodeToLabel(string $tipoContrato): string
    {
        $codes = explode(',', $tipoContrato);
        $labels = [];
        foreach ($codes as $code) {
            $code = trim($code);
            $labels[] = match ($code) {
                'SI' => 'Suministro e Instalación',
                'MO' => 'Mano de Obra',
                'S'  => 'Suministro',
                'OC' => 'Orden de Compra',
                default => $code,
            };
        }
        return implode(' + ', $labels);
    }
}
