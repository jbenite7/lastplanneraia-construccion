<?php
	require_once (__DIR__ . "/../conexion.php");
	// Nota: El archivo conexion.php ya inicializa un objeto $db de la clase Database.
	// Para evitar conflictos, renombraremos la variable local que contenía el prefijo de la base de datos.

	$dbPrefix = $_GET['db'] ?? '';
	// Validación estricta del prefijo de la base de datos para prevenir inyección SQL en nombres de tablas
	if (!preg_match('/^[a-zA-Z0-9_]+$/', $dbPrefix)) {
		die(json_encode(["error" => "Parámetro de base de datos inválido."]));
	}

	$semana = filter_var($_GET['semana'] ?? 0, FILTER_VALIDATE_INT);
	$activa_no_requeridas = filter_var($_GET["activa_no_requeridas"] ?? 0, FILTER_VALIDATE_INT);
	$activa_lookahead = filter_var($_GET["activa_lookahead"] ?? 0, FILTER_VALIDATE_INT);
	$activa_no_iniciadas = filter_var($_GET["activa_no_iniciadas"] ?? 0, FILTER_VALIDATE_INT);
	$activa_a_tiempo = filter_var($_GET["activa_a_tiempo"] ?? 0, FILTER_VALIDATE_INT);
	$activa_atrasadas = filter_var($_GET["activa_atrasadas"] ?? 0, FILTER_VALIDATE_INT);
	$activa_terminadas = filter_var($_GET["activa_terminadas"] ?? 0, FILTER_VALIDATE_INT);

	$script = "";
	if ($activa_no_requeridas == 1) {
		$script .= "AND ((Semanas_Inicio>6 AND Ejecutado=0 AND Estado='No Requerida') ";
	}

	if ($activa_lookahead == 1) {
		if ($script == "") {
			$script .= "AND ((Semanas_Inicio>0 AND Semanas_Inicio<=6 AND Ejecutado=0  AND Estado='En Liberación de Restricciones') ";
		} else {
			$script .= "OR (Semanas_Inicio>0 AND Semanas_Inicio<=6 AND Ejecutado=0  AND Estado='En Liberación de Restricciones') ";
		}
	}
	if ($activa_no_iniciadas == 1) {
		if ($script == "") {
			$script .= "AND ((Semanas_Inicio<=0 AND Ejecutado=0  AND (Estado='Debe Iniciar esta Semana' OR Estado='Debe Iniciar esta Semana y Restricciones Pendientes')) ";
		} else {
			$script .= "OR (Semanas_Inicio<=0 AND Ejecutado=0  AND (Estado='Debe Iniciar esta Semana' OR Estado='Debe Iniciar esta Semana y Restricciones Pendientes')) ";
		}
	}
	if ($activa_a_tiempo == 1) {
		if ($script == "") {
			$script .= "AND ((Ejecutado>0 AND Ejecutado<1  AND Estado='A Tiempo') ";
		} else {
			$script .= "OR (Ejecutado>0 AND Ejecutado<1  AND Estado='A Tiempo') ";
		}
	}
	if ($activa_atrasadas == 1) {
		if ($script == "") {
			$script .= "AND ((Ejecutado>=0 AND Ejecutado<1  AND (Estado='Atrasada' OR Estado='Ya Debió Iniciar y Restricciones Pendientes')) ";
		} else {
			$script .= "OR (Ejecutado>=0 AND Ejecutado<1  AND (Estado='Atrasada' OR Estado='Ya Debió Iniciar y Restricciones Pendientes')) ";
		}
	}
	if ($activa_terminadas == 1) {
		if ($script == "") {
			$script .= "AND ((Ejecutado=1  AND (Estado='Terminada' OR Estado='Terminada Antes')) ";
		} else {
			$script .= "OR (Ejecutado=1  AND (Estado='Terminada' OR Estado='Terminada Antes')) ";
		}
	}

	if ($script != "") {
		$script .= ")";
	}

	// Usamos el objeto global $db (instancia de Database) para las consultas seguras.
	$queryCount = "SELECT COUNT(*) as total FROM {$dbPrefix}_programa_consolidado WHERE Semana = ? AND Fecha_Inicio IS NOT NULL AND Fecha_Fin IS NOT NULL AND Titulo != 1 $script";
	$stmtCount = $db->query($queryCount, [$semana]);
	$rowCount = $stmtCount->fetch();

	$conteo = $rowCount['total'] ?? 0;

	if ($conteo == 0) {
		$arreglo1["data"][] = array(
			"Consecutivo" => "", "Semana" => "", "Consecutivo_en_Programa" => "", "Id" => "", "Actividad" => "",
			"Titulo" => "", "Semanas_Inicio" => "", "Fecha_Inicio" => "", "Fecha_Fin" => "", "Ruta_Critica" => "",
			"unidad" => "", "cantidad_ppto" => "", "medir_productividad" => "", "codigo_actividad" => "",
			"Ejecutado_Teorico" => "", "Ejecutado" => "", "Estado" => "", "Estado_Restricciones" => "",
			"Responsable_AIA" => "", "Sub_Contratista" => "", "boton" => ""
		);
		header('Content-Type: application/json');
		echo json_encode($arreglo1);
	} else {
		$querySemanas = "SELECT Fecha_Inicio_Sem, Fecha_Fin_Sem FROM {$dbPrefix}_semanas_activas WHERE Semana = ?";
		$stmtSemanas = $db->query($querySemanas, [$semana]);
		$dataSemanas = $stmtSemanas->fetch();

		$Fecha_Inicio_Sem = date("Y-m-d", strtotime($dataSemanas["Fecha_Inicio_Sem"]));
		$Fecha_Fin_Sem = date("Y-m-d", strtotime($dataSemanas["Fecha_Fin_Sem"]));

		$queryData = "SELECT * FROM {$dbPrefix}_programa_consolidado WHERE Semana = ? AND Fecha_Inicio IS NOT NULL AND Fecha_Fin IS NOT NULL $script ORDER BY Consecutivo ASC, Consecutivo_en_Programa ASC, Id ASC";
		$stmtData = $db->query($queryData, [$semana]);

		$arreglo = ["data" => []];
		while ($data1 = $stmtData->fetch()) {
			$titulo = $data1['Titulo'];
			$Fecha_Inicio_Act = date("Y-m-d", strtotime($data1['Fecha_Inicio']));
			$Fecha_Fin_Act = date("Y-m-d", strtotime($data1['Fecha_Fin']));

			$data1["boton"] = ($titulo == 1) ? "No Boton" : "Boton";

			$diasLleva = ((strtotime($Fecha_Inicio_Sem) - strtotime($Fecha_Inicio_Act)) / 86400);
			$diasTotales = ((strtotime($Fecha_Fin_Act) - strtotime($Fecha_Inicio_Act)) / 86400) + 1;

			if ($titulo == 1) {
				$data1["Ejecutado_Teorico"] = NULL;
			} else if ($diasLleva >= 1 && $diasTotales >= $diasLleva) {
				$data1["Ejecutado_Teorico"] = ($diasLleva / $diasTotales);
			} else if ($diasTotales < $diasLleva) {
				$data1["Ejecutado_Teorico"] = 1;
			} else if ($diasLleva < 1) {
				$data1["Ejecutado_Teorico"] = 0;
			}

			$arreglo["data"][] = $data1;
		}

		header('Content-Type: application/json');
		echo json_encode($arreglo, JSON_UNESCAPED_UNICODE);
	}
?>

