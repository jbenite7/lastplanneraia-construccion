<?php

function pdc_insertarPaquetes($db, $dbName, $semana, $cvSI, $cvS, $cvMO, $cvOC = '')
{
    $tipos = [
        ['Suministro e Instalación', 'SI', 2],
        ['Mano de Obra', 'MO', 1],
        ['Suministro', 'S', 1],
        ['Orden de Compra', 'OC', 1],
    ];

    foreach ($tipos as $t) {
        list($label, $prefix, $tipoId) = $t;

        $db->query("INSERT IGNORE INTO {$dbName}_pdc (titulo, semana, tipoPaquete, paqueteContratacion) VALUES (1, ?, ?, ?)", [$semana, $label, $label]);

        $whereClause = "";
        if ($prefix === 'SI') {
            $whereClause = $cvSI;
        } elseif ($prefix === 'S') {
            $whereClause = $cvS;
        } elseif ($prefix === 'MO') {
            $whereClause = $cvMO;
        } elseif ($prefix === 'OC') {
            $whereClause = $cvOC;
        }

        $sqlInsert = <<<SQL
INSERT INTO {$dbName}_pdc (titulo, semana, tipoPaquete, paqueteContratacion, contratos, fechaInicio, 
              diasElaboracionPliegos, diasEntregaPliegos, diasReciboPropuestas, 
              diasCuadrosComparativos, diasLegalizacionContrato, diasFabricacion, diasInsumosObra,
              fechaElaboracionPliegos, fechaEntregaPliegos, fechaReciboPropuestas, 
              fechaCuadrosComparativos, fechaLegalizacionContrato, fechaFabricacion, fechaInsumosObra)
SELECT 0, ?, ?, SubAct.paqueteContratacion, GROUP_CONCAT(SubAct.actividad SEPARATOR '; '), MIN(SubAct.fechaInicio),
       MAX(gpc.diasElaboracionPliegos), MAX(gpc.diasEntregaPliegos), MAX(gpc.diasReciboPropuestas),
       MAX(gpc.diasCuadrosComparativos), MAX(gpc.diasLegalizacionContrato), MAX(gpc.diasFabricacion), MAX(gpc.diasInsumosObra),
       DATE_SUB(MIN(SubAct.fechaInicio), INTERVAL (IFNULL(MAX(gpc.diasInsumosObra),0) + IFNULL(MAX(gpc.diasFabricacion),0) + IFNULL(MAX(gpc.diasLegalizacionContrato),0) + IFNULL(MAX(gpc.diasCuadrosComparativos),0) + IFNULL(MAX(gpc.diasReciboPropuestas),0) + IFNULL(MAX(gpc.diasEntregaPliegos),0) + IFNULL(MAX(gpc.diasElaboracionPliegos),0)) DAY),
       DATE_SUB(MIN(SubAct.fechaInicio), INTERVAL (IFNULL(MAX(gpc.diasInsumosObra),0) + IFNULL(MAX(gpc.diasFabricacion),0) + IFNULL(MAX(gpc.diasLegalizacionContrato),0) + IFNULL(MAX(gpc.diasCuadrosComparativos),0) + IFNULL(MAX(gpc.diasReciboPropuestas),0) + IFNULL(MAX(gpc.diasEntregaPliegos),0)) DAY),
       DATE_SUB(MIN(SubAct.fechaInicio), INTERVAL (IFNULL(MAX(gpc.diasInsumosObra),0) + IFNULL(MAX(gpc.diasFabricacion),0) + IFNULL(MAX(gpc.diasLegalizacionContrato),0) + IFNULL(MAX(gpc.diasCuadrosComparativos),0) + IFNULL(MAX(gpc.diasReciboPropuestas),0)) DAY),
       DATE_SUB(MIN(SubAct.fechaInicio), INTERVAL (IFNULL(MAX(gpc.diasInsumosObra),0) + IFNULL(MAX(gpc.diasFabricacion),0) + IFNULL(MAX(gpc.diasLegalizacionContrato),0) + IFNULL(MAX(gpc.diasCuadrosComparativos),0)) DAY),
       DATE_SUB(MIN(SubAct.fechaInicio), INTERVAL (IFNULL(MAX(gpc.diasInsumosObra),0) + IFNULL(MAX(gpc.diasFabricacion),0) + IFNULL(MAX(gpc.diasLegalizacionContrato),0)) DAY),
       DATE_SUB(MIN(SubAct.fechaInicio), INTERVAL (IFNULL(MAX(gpc.diasInsumosObra),0) + IFNULL(MAX(gpc.diasFabricacion),0)) DAY),
       DATE_SUB(MIN(SubAct.fechaInicio), INTERVAL IFNULL(MAX(gpc.diasInsumosObra),0) DAY)
FROM (
    SELECT actividad, fechaInicio, paquete{$prefix}1 AS paqueteContratacion FROM {$dbName}_actividades WHERE semanaActualizacion = ? AND tipoContrato = ? AND paquete{$prefix}1 IS NOT NULL AND paquete{$prefix}1 != ''
    UNION SELECT actividad, fechaInicio, paquete{$prefix}2 AS paqueteContratacion FROM {$dbName}_actividades WHERE semanaActualizacion = ? AND tipoContrato = ? AND paquete{$prefix}2 IS NOT NULL AND paquete{$prefix}2 != ''
    UNION SELECT actividad, fechaInicio, paquete{$prefix}3 AS paqueteContratacion FROM {$dbName}_actividades WHERE semanaActualizacion = ? AND tipoContrato = ? AND paquete{$prefix}3 IS NOT NULL AND paquete{$prefix}3 != ''
    UNION SELECT actividad, fechaInicio, paquete{$prefix}4 AS paqueteContratacion FROM {$dbName}_actividades WHERE semanaActualizacion = ? AND tipoContrato = ? AND paquete{$prefix}4 IS NOT NULL AND paquete{$prefix}4 != ''
    UNION SELECT actividad, fechaInicio, paquete{$prefix}5 AS paqueteContratacion FROM {$dbName}_actividades WHERE semanaActualizacion = ? AND tipoContrato = ? AND paquete{$prefix}5 IS NOT NULL AND paquete{$prefix}5 != ''
) AS SubAct
LEFT JOIN general_dias_procesos_contratacion AS gpc ON SubAct.paqueteContratacion = gpc.paqueteContratacion AND gpc.tipoPaquete = ?
$whereClause
GROUP BY SubAct.paqueteContratacion
SQL;

        $db->query($sqlInsert, [
            $semana, $label,
            $semana, $tipoId,
            $semana, $tipoId,
            $semana, $tipoId,
            $semana, $tipoId,
            $semana, $tipoId,
            $label,
        ]);
    }
}

function pdc_crearSubcontratosDuplicados($db, $dbName, $semana)
{
    $stmt = $db->query("SELECT * FROM {$dbName}_pdc WHERE semana = ? AND titulo = 0 AND numeroSubcontratos > 1", [$semana]);
    $items = $stmt->fetchAll();

    foreach ($items as $data) {
        $consecutivo = $data["consecutivo"];
        $numeroSubcontratos = (int) $data["numeroSubcontratos"];
        $paqueteContratacion = $data["paqueteContratacion"];

        $stmtInfo = $db->query("SELECT COUNT(*) as conteo, MAX(subcontratoPaquete) as maxSub FROM {$dbName}_pdc WHERE semana = ? AND titulo = 0 AND paqueteContratacion = ?", [$semana, $paqueteContratacion]);
        $info = $stmtInfo->fetch();
        $conteoActual = (int) $info["conteo"];
        $maxSub = (int) $info["maxSub"];

        if ($conteoActual < $numeroSubcontratos) {
            for ($i = $conteoActual + 1; $i <= $numeroSubcontratos; $i++) {
                $maxSub++;
                $sqlDup = "INSERT INTO {$dbName}_pdc (semana, titulo, tipoPaquete, paqueteContratacion, contratos, subcontratoPaquete, estado, 
                           fechaElaboracionPliegos, fechaEntregaPliegos, diasEntregaPliegos, fechaReciboPropuestas, 
                           diasReciboPropuestas, fechaCuadrosComparativos, diasCuadrosComparativos, fechaLegalizacionContrato, 
                           diasLegalizacionContrato, fechaFabricacion, diasFabricacion, fechaInsumosObra, diasInsumosObra, fechaInicio) 
                           SELECT semana, titulo, tipoPaquete, paqueteContratacion, contratos, ?, estado, 
                                  fechaElaboracionPliegos, fechaEntregaPliegos, diasEntregaPliegos, fechaReciboPropuestas, 
                                  diasReciboPropuestas, fechaCuadrosComparativos, diasCuadrosComparativos, fechaLegalizacionContrato, 
                                  diasLegalizacionContrato, fechaFabricacion, diasFabricacion, fechaInsumosObra, diasInsumosObra, fechaInicio 
                           FROM {$dbName}_pdc WHERE consecutivo = ?";
                $db->query($sqlDup, [$maxSub, $consecutivo]);
            }
        }
    }
}

function pdc_generarEstadoProceso($db, $dbName, $semana)
{
    $stmt = $db->query("SELECT * FROM {$dbName}_pdc WHERE semana = ? AND titulo = 0 AND fechaInicio IS NOT NULL", [$semana]);
    $actividades = $stmt->fetchAll();

    $stmtFecha = $db->query("SELECT Fecha_Inicio_Sem FROM {$dbName}_semanas_activas WHERE Semana = ?", [$semana]);
    $dataFecha = $stmtFecha->fetch();
    $fechaActual = date('Y-m-d', strtotime($dataFecha["Fecha_Inicio_Sem"] ?? 'now'));

    foreach ($actividades as $data) {
        $fechaInicio = $data["fechaInicio"];
        $consecutivo = $data["consecutivo"];

        $duraciones = [
            'elaboracion' => (int) $data["diasElaboracionPliegos"],
            'entrega' => (int) $data["diasEntregaPliegos"],
            'recibo' => (int) $data["diasReciboPropuestas"],
            'cuadros' => (int) $data["diasCuadrosComparativos"],
            'legalizacion' => (int) $data["diasLegalizacionContrato"],
            'fabricacion' => (int) $data["diasFabricacion"],
            'insumos' => (int) $data["diasInsumosObra"],
        ];

        $totalDias = array_sum($duraciones);

        $fechasCalculadas = [
            'fechaElaboracionPliegos' => date('Y-m-d', strtotime("$fechaInicio - $totalDias days")),
            'fechaEntregaPliegos'     => date('Y-m-d', strtotime("$fechaInicio - " . ($totalDias - $duraciones['elaboracion']) . " days")),
            'fechaReciboPropuestas'    => date('Y-m-d', strtotime("$fechaInicio - " . ($totalDias - $duraciones['elaboracion'] - $duraciones['entrega']) . " days")),
            'fechaCuadrosComparativos' => date('Y-m-d', strtotime("$fechaInicio - " . ($duraciones['cuadros'] + $duraciones['legalizacion'] + $duraciones['fabricacion'] + $duraciones['insumos']) . " days")),
            'fechaLegalizacionContrato' => date('Y-m-d', strtotime("$fechaInicio - " . ($duraciones['legalizacion'] + $duraciones['fabricacion'] + $duraciones['insumos']) . " days")),
            'fechaFabricacion'        => date('Y-m-d', strtotime("$fechaInicio - " . ($duraciones['fabricacion'] + $duraciones['insumos']) . " days")),
            'fechaInsumosObra'        => date('Y-m-d', strtotime("$fechaInicio - " . $duraciones['insumos'] . " days")),
        ];

        $pasos = [
            [$data["fechaRealElaboracionPliegos"],  $fechasCalculadas['fechaElaboracionPliegos'], "Elaborando pliegos del contrato"],
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

        for ($i = 0; $i < 8; $i++) {
            if (!empty($pasos[$i][0])) {
                $posicion = $i;
            }
            if ($pasos[$i][1] <= $fechaActual) {
                $deberiaHoy = $i;
            }
        }

        $diagnostico = ($posicion >= $deberiaHoy) ? "En Curso" : "Atrasado!!";

        if ($posicion === 7) {
            $estadoFinal = ($pasos[7][0] > $pasos[7][1]) ? "Terminado con retrasos" : "Terminado a tiempo";
        } else {
            $estadoFinal = "$diagnostico; " . ($posicion === -1 ? "Proceso de contratación no iniciado" : $pasos[$posicion][2]);
        }

        $sqlAct = "UPDATE {$dbName}_pdc SET 
                   fechaElaboracionPliegos = ?, fechaEntregaPliegos = ?, 
                   fechaReciboPropuestas = ?, fechaCuadrosComparativos = ?, fechaLegalizacionContrato = ?, 
                   fechaFabricacion = ?, fechaInsumosObra = ?, estado = ?
                   WHERE consecutivo = ?";

        $db->query($sqlAct, [
            $fechasCalculadas['fechaElaboracionPliegos'],
            $fechasCalculadas['fechaEntregaPliegos'], $fechasCalculadas['fechaReciboPropuestas'],
            $fechasCalculadas['fechaCuadrosComparativos'], $fechasCalculadas['fechaLegalizacionContrato'],
            $fechasCalculadas['fechaFabricacion'], $fechasCalculadas['fechaInsumosObra'],
            $estadoFinal, $consecutivo,
        ]);
    }
}
