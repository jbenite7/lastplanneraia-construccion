<?php
	require_once (__DIR__ . "/../conexion.php");
	// El objeto $db (instancia de Database) ya está disponible desde conexion.php

	$dbPrefix = $_GET['db'] ?? '';
	// Validación estricta del prefijo de la base de datos
	if (!preg_match('/^[a-zA-Z0-9_]+$/', $dbPrefix)) {
		die(json_encode(["error" => "Parámetro de base de datos inválido."]));
	}

	$semana = filter_var($_GET['semana'] ?? 0, FILTER_VALIDATE_INT);

	$queryCount = "SELECT COUNT(*) as total FROM {$dbPrefix}_actividades WHERE semanaActualizacion = ? AND tipoContrato IS NOT NULL AND fechaInicio IS NOT NULL";
	$stmtCount = $db->query($queryCount, [$semana]);
	$rowCount = $stmtCount->fetch();

	$conteo = $rowCount['total'] ?? 0;

	if ($conteo == 0) {
		$arreglo1["data"][] = array(
			"Id" => "", "codigo" => "", "actividad" => "", "descripcionActividad" => "", "actividadInicio" => "",
			"nombreActividadInicio" => "", "fechaInicio" => "", "tipoContrato" => "", "semanaActualizacion" => "",
			"SI1" => "", "paqueteSI1" => "", "SI2" => "", "paqueteSI2" => "", "SI3" => "", "paqueteSI3" => "",
			"SI4" => "", "paqueteSI4" => "", "SI5" => "", "paqueteSI5" => "", "S1" => "", "paqueteS1" => "",
			"S2" => "", "paqueteS2" => "", "S3" => "", "paqueteS3" => "", "S4" => "", "paqueteS4" => "",
			"S5" => "", "paqueteS5" => "", "MO1" => "", "paqueteMO1" => "", "MO2" => "", "paqueteMO2" => "",
			"MO3" => "", "paqueteMO3" => "", "MO4" => "", "paqueteMO4" => "", "MO5" => "", "paqueteMO5" => "",
			"contratosAsociados" => ""
		);
		header('Content-Type: application/json');
		echo json_encode($arreglo1);
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

		$arreglo = ["data" => []];
		while ($data = $stmtData->fetch()) {
			$contratosAsociadosSI = "";
			for ($i = 1; $i <= 5; $i++) {
				if (!empty($data["paqueteSI$i"])) {
					$contratosAsociadosSI .= $data["paqueteSI$i"] . ", ";
				}
			}
			if ($contratosAsociadosSI != "") {
				$contratosAsociadosSI = substr($contratosAsociadosSI, 0, -2);
				$contratosAsociadosSI = str_replace(';', ", ", $contratosAsociadosSI);
				$contratosAsociadosSI = "<b style='color: red'>- Suministro e Instalación: </b>" . $contratosAsociadosSI . ".<br>";
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
				$contratosAsociadosS = "<b style='color: blue'>- Suministro: </b>" . $contratosAsociadosS . ".<br> ";
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
				$contratosAsociadosMO = "<b style='color: green'>- Mano de Obra: </b>" . $contratosAsociadosMO . ".<br>";
			}

			$data["contratosAsociados"] = $contratosAsociadosSI . $contratosAsociadosMO . $contratosAsociadosS;
			$arreglo["data"][] = $data;
		}

		header('Content-Type: application/json');
		echo json_encode($arreglo, JSON_UNESCAPED_UNICODE);
	}
?>
