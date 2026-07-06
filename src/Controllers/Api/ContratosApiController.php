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
            $projectId = (int) $context['projectId'];
            $semana = $context['semana'];
            $arreglo = ["data" => []];

            $this->requireConstructionArea();
            $this->requirePermission('lps.contratos.ver', 'No autorizado para consultar contratos.');

            $db = Database::getInstance();
            $contractColumns = $this->contractSlotFieldNames();
            $queryCount = "SELECT COUNT(*) as total
                FROM actividades
                WHERE project_id = ?
                  AND semanaActualizacion = ?
                  AND fechaInicio IS NOT NULL";
            $stmtCount = $db->query($queryCount, [$projectId, $semana]);
            $conteo = $stmtCount->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

            if ($conteo == 0) {
                $emptyRow = [
                    "Id" => "", "codigo" => "", "actividad" => "", "descripcionActividad" => "", "actividadInicio" => "",
                    "nombreActividadInicio" => "", "fechaInicio" => "", "tipoContrato" => "", "semanaActualizacion" => "",
                    "contratosAsociados" => "",
                ];
                foreach ($contractColumns as $column) {
                    $emptyRow[$column] = "";
                }
                $arreglo["data"][] = $emptyRow;
            } else {
                $contractSelect = $this->selectColumnsSql($contractColumns, 'act');
                $queryData = "SELECT
                        act.`Id`, act.`codigo`, act.`actividad`, act.`descripcionActividad`, act.`actividadInicio`,
                        CONCAT(prog.`Actividad`, ' - (Inicia en: ', prog.`Fecha_Inicio`, ')') AS nombreActividadInicio,
                        act.`fechaInicio`, act.`tipoContrato`, act.`semanaActualizacion`,
                        {$contractSelect}
                    FROM actividades act
                    LEFT JOIN programa_consolidado prog
                      ON prog.`project_id` = act.`project_id`
                     AND prog.`unique_id` = act.`actividadInicio`
                     AND prog.`Semana` = act.`semanaActualizacion`
                    WHERE act.`project_id` = ?
                      AND act.semanaActualizacion = ?
                      AND act.fechaInicio IS NOT NULL
                    ORDER BY act.`Id`";

                $stmtData = $db->query($queryData, [$projectId, $semana]);

                while ($data = $stmtData->fetch(PDO::FETCH_ASSOC)) {
                    $data["tipoContrato"] = $this->inferTipoContratoFromPackages((string) ($data["tipoContrato"] ?? ''), $data);
                    $data["contratosAsociados"] =
                        $this->formatPackageSummary($data, 'SI', 'Suministro e Instalación', 'ct-text-danger')
                        . $this->formatPackageSummary($data, 'MO', 'Mano de Obra', 'ct-text-success')
                        . $this->formatPackageSummary($data, 'S', 'Suministro', 'ct-text-info')
                        . $this->formatPackageSummary($data, 'OC', 'Orden de Compra', 'ct-text-dark');
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
            $projectId = (int) $context['projectId'];
            $semanaActualizacion = $context['semana'];

            $this->requireConstructionArea();
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
                        $qKey = "cantidad$t$i";
                        $pVal = $_POST[$pKey] ?? null;
                        $iVal = $_POST[$iKey] ?? null;
                        $res = $this->insumosPaquetes($pVal, $iVal);
                        $paquetes["paquete$t$i"] = $res[0];
                        $paquetes["$t$i"] = $res[1];
                        $paquetes[$qKey] = $this->normalizePackageQuantity($_POST[$qKey] ?? 1, $res[0]);
                    }
                }

                $tipoContrato = $this->inferTipoContratoFromPackages((string) $tipoContrato, $paquetes);
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
                    $missingDurations = $this->missingDurationRows($db, $paquetes);
                    if ($missingDurations !== []) {
                        $defaultsByType = $this->standardDurationsByType($db);
                        $missingWithDefaults = $this->attachDefaultDurations($missingDurations, $defaultsByType);
                        $this->jsonResponse([
                            'respuesta' => 'DURACIONES_REQUERIDAS',
                            'mensaje' => 'Define los dias de contratacion para continuar con el guardado. Se sugieren los valores estandar de cada modalidad; puedes ajustarlos antes de guardar.',
                            'paquetes' => $missingWithDefaults,
                        ]);
                        return;
                    }

                    $beforeSnapshot = $this->loadContractTraceSnapshot($db, $projectId, (int) $Id, $semanaActualizacion);
                    $contractFields = $this->contractSlotFieldNames();
                    $setClauses = [];
                    $paramsUpdate = [];
                    foreach ($contractFields as $field) {
                        $setClauses[] = "`$field`=?";
                        $paramsUpdate[] = $paquetes[$field] ?? null;
                    }
                    $setClauses[] = "tipoContrato=?";
                    $setClauses[] = "semanaActualizacion=?";
                    $paramsUpdate[] = $tipoContrato;
                    $paramsUpdate[] = $semanaActualizacion;

                    $queryUpdate = "UPDATE actividades SET
                        " . implode(', ', $setClauses) . "
                        WHERE project_id=? AND Id=? AND semanaActualizacion=?";
                    $paramsUpdate[] = $projectId;
                    $paramsUpdate[] = $Id;
                    $paramsUpdate[] = $semanaActualizacion;

                    $stmt = $db->query($queryUpdate, $paramsUpdate);

                    if ($stmt) {
                        $db->logActivity('Contratos', 'MODIFICAR', "Se actualizaron los paquetes de contratación para la actividad: $actividadModificar (ID: $Id)", $dbPrefix);
                        $afterSnapshot = $this->loadContractTraceSnapshot($db, $projectId, (int) $Id, $semanaActualizacion);
                        $this->recordContractTrace($db, $projectId, (int) $Id, $semanaActualizacion, $beforeSnapshot, $afterSnapshot, 'manual');
                    }
                }

                $this->verificar_resultado($stmt, $errores);

            } elseif ($opcion == "actualizarFechaInicio") {
                $this->actualizarFechaInicio($_POST["idActividad"] ?? '', $semanaActualizacion, $projectId, $db);
            } elseif ($opcion == "guardarDuracionesContratacion") {
                $this->guardarDuracionesContratacion($db);
            } elseif ($opcion == "actualizarListadoPaquetesContratacion") {
                $this->actualizarListadoPaquetesContratacion($_POST["tipoContrato"] ?? '', $dbPrefix, $db);
            } elseif ($opcion == "actualizarInsumosRecursos") {
                $this->actualizarInsumosRecursos($_POST["tipoContrato"] ?? '', $projectId, $db, $semanaActualizacion);
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
            $projectId = (int) $context['projectId'];
            $semana = $context['semana'];

            $this->requireConstructionArea();
            $this->requirePermission('lps.contratos.editar', 'No autorizado para auto-asignar contratos.');

            $db = Database::getInstance();
            $packageFilter = $this->packagePresenceSql($this->packageFieldNames());

            $stmt = $db->query(
                "SELECT Id, codigo, actividad, descripcionActividad, actividadInicio, fechaInicio, tipoContrato
                 FROM actividades
                 WHERE project_id = ?
                   AND semanaActualizacion = ?
                   AND (tipoContrato IS NULL OR tipoContrato = '')
                   AND NOT ($packageFilter)
                 ORDER BY Id ASC",
                [$projectId, $semana]
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

                $pgActivity = $this->loadPgActivityForContratos($db, $projectId, $semana, $activity['actividadInicio'] ?? '');
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

                $result = $this->assignContratoToActivity($db, $projectId, $actId, $tipoContrato, $bestOption['items'], $semana);
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

    private function actualizarFechaInicio($Id, $semana, int $projectId, $db)
    {
        $query = "SELECT Fecha_Inicio FROM programa_consolidado WHERE project_id = ? AND Semana = ? AND (unique_id = ? OR Actividad = ?) ORDER BY Fecha_Inicio ASC LIMIT 1";
        $stmt = $db->query($query, [$projectId, $semana, $Id, $Id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->jsonResponse(["data" => $data ?: ["Fecha_Inicio" => ""]]);
    }

    private function actualizarListadoPaquetesContratacion($tipoContrato, $dbPrefix, $db)
    {
        $res = ["listadoMO" => "", "listadoS" => "", "listadoSI" => "", "listadoOC" => ""];
        $modalidades = array_map('trim', explode(',', $tipoContrato));

        if (in_array('MO', $modalidades) || in_array('S', $modalidades)) {
            $stmtMO = $db->query("SELECT DISTINCT TRIM(paqueteContratacion) AS paqueteContratacion FROM general_dias_procesos_contratacion WHERE tipoPaquete = 'Mano de Obra' AND paqueteContratacion IS NOT NULL AND TRIM(paqueteContratacion) != '' ORDER BY paqueteContratacion");
            $scriptMO = "<option value=''></option>";
            while ($row = $stmtMO->fetch()) {
                $scriptMO .= "<option value='" . htmlspecialchars($row["paqueteContratacion"], ENT_QUOTES) . "'>" . htmlspecialchars($row["paqueteContratacion"], ENT_QUOTES) . "</option>";
            }
            $res["listadoMO"] = $scriptMO;

            $stmtS = $db->query("SELECT DISTINCT TRIM(paqueteContratacion) AS paqueteContratacion FROM general_dias_procesos_contratacion WHERE tipoPaquete = 'Suministro' AND paqueteContratacion IS NOT NULL AND TRIM(paqueteContratacion) != '' ORDER BY paqueteContratacion");
            $scriptS = "<option value=''></option>";
            while ($row = $stmtS->fetch()) {
                $scriptS .= "<option value='" . htmlspecialchars($row["paqueteContratacion"], ENT_QUOTES) . "'>" . htmlspecialchars($row["paqueteContratacion"], ENT_QUOTES) . "</option>";
            }
            $res["listadoS"] = $scriptS;
        }

        if (in_array('SI', $modalidades)) {
            $stmtSI = $db->query("SELECT DISTINCT TRIM(paqueteContratacion) AS paqueteContratacion FROM general_dias_procesos_contratacion WHERE tipoPaquete = 'Suministro e Instalación' AND paqueteContratacion IS NOT NULL AND TRIM(paqueteContratacion) != '' ORDER BY paqueteContratacion");
            $scriptSI = "<option value=''></option>";
            while ($row = $stmtSI->fetch()) {
                $scriptSI .= "<option value='" . htmlspecialchars($row["paqueteContratacion"], ENT_QUOTES) . "'>" . htmlspecialchars($row["paqueteContratacion"], ENT_QUOTES) . "</option>";
            }
            $res["listadoSI"] = $scriptSI;
        }

        if (in_array('OC', $modalidades)) {
            $stmtOC = $db->query("SELECT DISTINCT TRIM(paqueteContratacion) AS paqueteContratacion FROM general_dias_procesos_contratacion WHERE tipoPaquete = 'Orden de Compra' AND paqueteContratacion IS NOT NULL AND TRIM(paqueteContratacion) != '' ORDER BY paqueteContratacion");
            $scriptOC = "<option value=''></option>";
            while ($row = $stmtOC->fetch()) {
                $scriptOC .= "<option value='" . htmlspecialchars($row["paqueteContratacion"], ENT_QUOTES) . "'>" . htmlspecialchars($row["paqueteContratacion"], ENT_QUOTES) . "</option>";
            }
            $res["listadoOC"] = $scriptOC;
        }
        $this->jsonResponse($res);
    }

    private function actualizarInsumosRecursos($tipoContrato, int $projectId, $db, $semana)
    {
        $res = ["listadoMO" => "", "listadoS" => "", "listadoSI" => "", "listadoOC" => ""];
        $modalidades = array_map('trim', explode(',', $tipoContrato));

        if (in_array('MO', $modalidades) || in_array('S', $modalidades)) {
            $queryMO = "SELECT MO1 FROM actividades WHERE project_id = ? AND semanaActualizacion = ? AND MO1 IS NOT NULL AND MO1 != ''
                UNION SELECT MO2 FROM actividades WHERE project_id = ? AND semanaActualizacion = ? AND MO2 IS NOT NULL AND MO2 != ''
                UNION SELECT MO3 FROM actividades WHERE project_id = ? AND semanaActualizacion = ? AND MO3 IS NOT NULL AND MO3 != ''
                UNION SELECT MO4 FROM actividades WHERE project_id = ? AND semanaActualizacion = ? AND MO4 IS NOT NULL AND MO4 != ''
                UNION SELECT MO5 FROM actividades WHERE project_id = ? AND semanaActualizacion = ? AND MO5 IS NOT NULL AND MO5 != ''";
            $insumosMO = $this->obtenerInsumosUnicos($db->query($queryMO, [$projectId, $semana, $projectId, $semana, $projectId, $semana, $projectId, $semana, $projectId, $semana]));
            $res["listadoMO"] = $this->generarOpcionesInsumos($insumosMO);

            $queryS = "SELECT S1 FROM actividades WHERE project_id = ? AND semanaActualizacion = ? AND S1 IS NOT NULL AND S1 != ''
                UNION SELECT S2 FROM actividades WHERE project_id = ? AND semanaActualizacion = ? AND S2 IS NOT NULL AND S2 != ''
                UNION SELECT S3 FROM actividades WHERE project_id = ? AND semanaActualizacion = ? AND S3 IS NOT NULL AND S3 != ''
                UNION SELECT S4 FROM actividades WHERE project_id = ? AND semanaActualizacion = ? AND S4 IS NOT NULL AND S4 != ''
                UNION SELECT S5 FROM actividades WHERE project_id = ? AND semanaActualizacion = ? AND S5 IS NOT NULL AND S5 != ''";
            $insumosS = $this->obtenerInsumosUnicos($db->query($queryS, [$projectId, $semana, $projectId, $semana, $projectId, $semana, $projectId, $semana, $projectId, $semana]));
            $res["listadoS"] = $this->generarOpcionesInsumos($insumosS);
        }

        if (in_array('SI', $modalidades)) {
            $querySI = "SELECT SI1 FROM actividades WHERE project_id = ? AND semanaActualizacion = ? AND SI1 IS NOT NULL AND SI1 != ''
                UNION SELECT SI2 FROM actividades WHERE project_id = ? AND semanaActualizacion = ? AND SI2 IS NOT NULL AND SI2 != ''
                UNION SELECT SI3 FROM actividades WHERE project_id = ? AND semanaActualizacion = ? AND SI3 IS NOT NULL AND SI3 != ''
                UNION SELECT SI4 FROM actividades WHERE project_id = ? AND semanaActualizacion = ? AND SI4 IS NOT NULL AND SI4 != ''
                UNION SELECT SI5 FROM actividades WHERE project_id = ? AND semanaActualizacion = ? AND SI5 IS NOT NULL AND SI5 != ''";
            $insumosSI = $this->obtenerInsumosUnicos($db->query($querySI, [$projectId, $semana, $projectId, $semana, $projectId, $semana, $projectId, $semana, $projectId, $semana]));
            $res["listadoSI"] = $this->generarOpcionesInsumos($insumosSI);
        }

        if (in_array('OC', $modalidades)) {
            $queryOC = "SELECT OC1 FROM actividades WHERE project_id = ? AND semanaActualizacion = ? AND OC1 IS NOT NULL AND OC1 != ''
                UNION SELECT OC2 FROM actividades WHERE project_id = ? AND semanaActualizacion = ? AND OC2 IS NOT NULL AND OC2 != ''
                UNION SELECT OC3 FROM actividades WHERE project_id = ? AND semanaActualizacion = ? AND OC3 IS NOT NULL AND OC3 != ''
                UNION SELECT OC4 FROM actividades WHERE project_id = ? AND semanaActualizacion = ? AND OC4 IS NOT NULL AND OC4 != ''
                UNION SELECT OC5 FROM actividades WHERE project_id = ? AND semanaActualizacion = ? AND OC5 IS NOT NULL AND OC5 != ''";
            $insumosOC = $this->obtenerInsumosUnicos($db->query($queryOC, [$projectId, $semana, $projectId, $semana, $projectId, $semana, $projectId, $semana, $projectId, $semana]));
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

    private function contractSlotFieldNames(): array
    {
        $fields = [];
        foreach (['SI', 'S', 'MO', 'OC'] as $prefix) {
            for ($i = 1; $i <= 5; $i++) {
                $fields[] = "{$prefix}{$i}";
                $fields[] = "paquete{$prefix}{$i}";
                $fields[] = "cantidad{$prefix}{$i}";
            }
        }

        return $fields;
    }

    private function selectColumnsSql(array $fields, string $alias): string
    {
        return implode(', ', array_map(static fn ($field) => "{$alias}.`{$field}`", $fields));
    }

    private function normalizePackageQuantity($rawValue, $package): int
    {
        if (trim((string) ($package ?? '')) === '') {
            return 1;
        }

        $quantity = filter_var($rawValue, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        return $quantity === false ? 1 : min(99, (int) $quantity);
    }

    private function packageTypeLabel(string $prefix): string
    {
        return match ($prefix) {
            'SI' => 'Suministro e Instalación',
            'MO' => 'Mano de Obra',
            'S' => 'Suministro',
            'OC' => 'Orden de Compra',
            default => $prefix,
        };
    }

    private function durationFields(): array
    {
        return [
            'diasElaboracionPliegos',
            'diasEntregaPliegos',
            'diasReciboPropuestas',
            'diasCuadrosComparativos',
            'diasLegalizacionContrato',
            'diasFabricacion',
            'diasInsumosObra',
        ];
    }

    /**
     * Lee las duraciones ESTÁNDAR de cada tipo de paquete y devuelve
     * un mapa tipoPaquete => [campo => valor]. Si falta una fila ESTÁNDAR,
     * el tipo no aparece en el mapa (la UI usará default 1, 1, 1, 1, 1, 1, 1).
     */
    private function standardDurationsByType(Database $db): array
    {
        $fieldNames = implode(', ', $this->durationFields());
        $rows = $db->query(
            "SELECT tipoPaquete, {$fieldNames}
             FROM general_dias_procesos_contratacion
             WHERE paqueteContratacion LIKE 'ESTÁNDAR%'"
        )->fetchAll(PDO::FETCH_ASSOC);

        $map = [];
        foreach ($rows as $r) {
            $map[$r['tipoPaquete']] = $r;
        }
        return $map;
    }

    /**
     * Adjunta a cada paquete faltante los valores ESTÁNDAR sugeridos
     * (diasElaboracionPliegos, etc.) según su tipoPaquete. Si no hay
     * ESTÁNDAR para ese tipo, usa el fallback neutro 1,1,1,1,1,1,1.
     */
    private function attachDefaultDurations(array $missing, array $defaultsByType): array
    {
        $fields = $this->durationFields();
        $neutral = array_fill_keys($fields, 1);

        $out = [];
        foreach ($missing as $item) {
            $type = $item['tipoPaquete'] ?? '';
            $defaults = $defaultsByType[$type] ?? $neutral;
            $row = array_merge($item, $defaults);
            $out[] = $row;
        }
        return $out;
    }

    private function missingDurationRows(Database $db, array $paquetes): array
    {
        $missing = [];
        $seen = [];
        foreach (['SI', 'MO', 'S', 'OC'] as $prefix) {
            for ($i = 1; $i <= 5; $i++) {
                $package = trim((string) ($paquetes["paquete{$prefix}{$i}"] ?? ''));
                if ($package === '') {
                    continue;
                }
                $type = $this->packageTypeLabel($prefix);
                $key = mb_strtolower($type . '|' . $package, 'UTF-8');
                if (isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;

                $row = $db->query(
                    "SELECT diasElaboracionPliegos, diasEntregaPliegos, diasReciboPropuestas,
                            diasCuadrosComparativos, diasLegalizacionContrato, diasFabricacion, diasInsumosObra
                     FROM general_dias_procesos_contratacion
                     WHERE paqueteContratacion = ? AND tipoPaquete = ?
                     LIMIT 1",
                    [$package, $type]
                )->fetch(PDO::FETCH_ASSOC);

                if (!$row) {
                    $missing[] = [
                        'tipoPaquete' => $type,
                        'paqueteContratacion' => $package,
                    ];
                    continue;
                }

                foreach ($this->durationFields() as $field) {
                    if (!isset($row[$field]) || (int) $row[$field] < 0) {
                        $missing[] = [
                            'tipoPaquete' => $type,
                            'paqueteContratacion' => $package,
                        ];
                        break;
                    }
                }
            }
        }

        return $missing;
    }

    private function guardarDuracionesContratacion(Database $db): void
    {
        $this->requirePermission('lps.contratos.editar', 'No autorizado para modificar contratos.');
        $raw = $_POST['duraciones'] ?? '[]';
        $items = is_string($raw) ? json_decode($raw, true) : $raw;
        if (!is_array($items)) {
            $this->jsonError('Duraciones invalidas.');
            return;
        }

        $fields = $this->durationFields();
        foreach ($items as $item) {
            $package = trim((string) ($item['paqueteContratacion'] ?? ''));
            $type = trim((string) ($item['tipoPaquete'] ?? ''));
            if ($package === '' || $type === '') {
                $this->jsonError('Falta paquete o tipo de paquete.');
                return;
            }

            $values = [];
            foreach ($fields as $field) {
                $value = filter_var($item[$field] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
                if ($value === false) {
                    $this->jsonError('Todas las duraciones deben ser enteros iguales o mayores a cero.');
                    return;
                }
                $values[$field] = (int) $value;
            }

            $exists = $db->query(
                "SELECT 1 FROM general_dias_procesos_contratacion
                 WHERE paqueteContratacion = ? AND tipoPaquete = ?
                 LIMIT 1",
                [$package, $type]
            )->fetchColumn();

            if ($exists) {
                $db->query(
                    "UPDATE general_dias_procesos_contratacion
                     SET diasElaboracionPliegos=?, diasEntregaPliegos=?, diasReciboPropuestas=?,
                         diasCuadrosComparativos=?, diasLegalizacionContrato=?, diasFabricacion=?, diasInsumosObra=?
                     WHERE paqueteContratacion = ? AND tipoPaquete = ?",
                    [
                        $values['diasElaboracionPliegos'],
                        $values['diasEntregaPliegos'],
                        $values['diasReciboPropuestas'],
                        $values['diasCuadrosComparativos'],
                        $values['diasLegalizacionContrato'],
                        $values['diasFabricacion'],
                        $values['diasInsumosObra'],
                        $package,
                        $type,
                    ]
                );
            } else {
                $db->query(
                    "INSERT INTO general_dias_procesos_contratacion
                        (paqueteContratacion, tipoPaquete, diasElaboracionPliegos, diasEntregaPliegos,
                         diasReciboPropuestas, diasCuadrosComparativos, diasLegalizacionContrato,
                         diasFabricacion, diasInsumosObra)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [
                        $package,
                        $type,
                        $values['diasElaboracionPliegos'],
                        $values['diasEntregaPliegos'],
                        $values['diasReciboPropuestas'],
                        $values['diasCuadrosComparativos'],
                        $values['diasLegalizacionContrato'],
                        $values['diasFabricacion'],
                        $values['diasInsumosObra'],
                    ]
                );
            }
        }

        $this->jsonResponse(['respuesta' => 'BIEN']);
    }

    private function loadContractTraceSnapshot(Database $db, int $projectId, int $activityId, int $week): array
    {
        $fields = array_merge(
            ['actividadInicio', 'fechaInicio', 'tipoContrato'],
            $this->contractSlotFieldNames()
        );
        $row = $db->query(
            "SELECT " . $this->selectColumnsSql($fields, 'act') . "
             FROM actividades act
             WHERE act.project_id = ? AND act.Id = ? AND act.semanaActualizacion = ?
             LIMIT 1",
            [$projectId, $activityId, $week]
        )->fetch(PDO::FETCH_ASSOC);

        return $row ?: [];
    }

    private function recordContractTrace(Database $db, int $projectId, int $activityId, int $week, array $before, array $after, string $origin): void
    {
        $changed = [];
        foreach (array_unique(array_merge(array_keys($before), array_keys($after))) as $field) {
            if ((string) ($before[$field] ?? '') !== (string) ($after[$field] ?? '')) {
                $changed[] = $field;
            }
        }

        if ($changed === []) {
            return;
        }

        $user = $_SESSION['usuario'] ?? $_SESSION['nombreUsuario'] ?? $_SESSION['user'] ?? null;
        $db->query(
            "INSERT INTO contratos_trazabilidad
                (project_id, actividad_id, semana, usuario, origen, campos_cambiados, antes, despues)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $projectId,
                $activityId,
                $week,
                $user,
                $origin,
                json_encode($changed, JSON_UNESCAPED_UNICODE),
                json_encode($before, JSON_UNESCAPED_UNICODE),
                json_encode($after, JSON_UNESCAPED_UNICODE),
            ]
        );
    }

    private function packageFieldNames(): array
    {
        $fields = [];
        foreach (['SI', 'S', 'MO', 'OC'] as $prefix) {
            for ($i = 1; $i <= 5; $i++) {
                $fields[] = "paquete$prefix$i";
            }
        }

        return $fields;
    }

    private function packagePresenceSql(array $fields, string $alias = ''): string
    {
        $checks = [];
        foreach ($fields as $field) {
            $qualifiedField = $alias === '' ? "`$field`" : "$alias.`$field`";
            $checks[] = "NULLIF(TRIM(COALESCE($qualifiedField, '')), '') IS NOT NULL";
        }

        return implode(' OR ', $checks);
    }

    private function inferTipoContratoFromPackages(string $tipoContrato, array $source): string
    {
        $order = ['SI', 'MO', 'S', 'OC'];
        $selected = [];

        foreach (explode(',', $tipoContrato) as $code) {
            $code = strtoupper(trim($code));
            if (in_array($code, $order, true)) {
                $selected[$code] = true;
            }
        }

        foreach ($order as $prefix) {
            if ($this->hasPackageForPrefix($source, $prefix)) {
                $selected[$prefix] = true;
            }
        }

        $result = [];
        foreach ($order as $code) {
            if (!empty($selected[$code])) {
                $result[] = $code;
            }
        }

        return implode(',', $result);
    }

    private function hasPackageForPrefix(array $source, string $prefix): bool
    {
        for ($i = 1; $i <= 5; $i++) {
            $value = $source["paquete$prefix$i"] ?? '';
            if (trim((string) $value) !== '') {
                return true;
            }
        }

        return false;
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

    private function requireConstructionArea(): void
    {
        if (($_SESSION['area'] ?? 'Construccion') !== 'Pre-Construccion') {
            return;
        }

        $this->jsonError('Contratos no esta disponible para proyectos de preconstruccion.', 403);
        exit;
    }

    private function escapeHtml(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    private function formatUniquePackageNames(array|string $values): string
    {
        $seen = [];
        $packages = [];
        foreach ((array) $values as $value) {
            foreach (explode(';', (string) $value) as $package) {
                $package = trim($package);
                if ($package === '') {
                    continue;
                }

                $key = strtolower(preg_replace('/\s+/', ' ', $package));
                if (isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;
                $packages[] = $package;
            }
        }

        return implode(', ', $packages);
    }

    private function formatPackageSummary(array $data, string $prefix, string $label, string $className): string
    {
        $packages = [];
        for ($i = 1; $i <= 5; $i++) {
            $package = trim((string) ($data["paquete{$prefix}{$i}"] ?? ''));
            if ($package === '') {
                continue;
            }
            $key = mb_strtolower($package, 'UTF-8');
            $quantity = max(1, (int) ($data["cantidad{$prefix}{$i}"] ?? 1));
            if (!isset($packages[$key])) {
                $packages[$key] = ['name' => $package, 'quantity' => 0];
            }
            $packages[$key]['quantity'] += $quantity;
        }

        if ($packages === []) {
            return '';
        }

        $parts = [];
        foreach ($packages as $package) {
            $text = $package['name'];
            if ((int) $package['quantity'] > 1) {
                $text .= ' x' . (int) $package['quantity'];
            }
            $parts[] = $this->escapeHtml($text);
        }

        return "<b class='{$className}'>- {$label}: </b>" . implode(', ', $parts) . ".<br>";
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

    private function loadPgActivityForContratos($db, int $projectId, int $semana, string $actividadInicio): ?array
    {
        if (empty($actividadInicio)) {
            return null;
        }

        $stmt = $db->query(
            "SELECT row_id AS Consecutivo,
                    unique_id AS Consecutivo_en_Programa,
                    unique_id,
                    Id, Actividad, Fecha_Inicio, COALESCE(Titulo, 0) AS Titulo
             FROM programa_consolidado
             WHERE project_id = ? AND Semana = ? AND unique_id = ? LIMIT 1",
            [$projectId, $semana, $actividadInicio]
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

    private function assignContratoToActivity($db, int $projectId, int $actId, string $tipoContrato, array $items, int $semana): bool
    {
        try {
            $prefixMap = [
                'Suministro e Instalación' => 'SI',
                'Suministro' => 'S',
                'Mano de Obra' => 'MO',
                'Orden de Compra' => 'OC',
                'Equipos' => 'SI',
                'Equipo' => 'SI',
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

            $params[] = $projectId;
            $params[] = $actId;
            $params[] = $semana;

            $sql = "UPDATE actividades SET " . implode(', ', $updates) . " WHERE project_id = ? AND Id = ? AND semanaActualizacion = ?";
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
            6 => 'E',
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
                'E' => 'Equipos',
                default => $code,
            };
        }
        return implode(' + ', $labels);
    }

}
