<?php
	require_once (__DIR__ . "/../conexion.php");
	// Nota: El archivo conexion.php ya inicializa un objeto $db de la clase Database.
	// Para evitar conflictos, renombraremos la variable local que contenía el prefijo de la base de datos.

	$dbPrefix = $_GET['db'] ?? '';
	// Validación estricta del prefijo de la base de datos
	if (!preg_match('/^[a-zA-Z0-9_]+$/', $dbPrefix)) {
		die(json_encode(["error" => "Parámetro de base de datos inválido."]));
	}

	$semana = filter_var($_GET['semana'] ?? 0, FILTER_VALIDATE_INT);

	$queryCount = "SELECT COUNT(*) as total FROM {$dbPrefix}_programacion_semanal WHERE Semana = ? AND (Activa = '1' OR Activa = 'NA')";
	$stmtCount = $db->query($queryCount, [$semana]);
	$rowCount = $stmtCount->fetch();

	$conteo = $rowCount['total'] ?? 0;

	if ($conteo == 0) {
		$arreglo1["data"][] = array(
			"Consecutivo" => "", "Id" => "", "Actividad" => "", "Fecha_Inicio" => "", "Fecha_Fin" => "",
			"Prog_Sin_Restricciones_100" => "", "Descripcion" => "", "Ubicacion" => "", "Ejecutado" => "",
			"Ejecutado_Fin_Semana" => "", "Sub_Contratista" => "", "Responsable_AIA" => "", "Empresa" => "",
			"medir_productividad" => "", "Unidad" => "", "cantidad_ppto" => "", "Compromiso" => "",
			"Ejecutado_Real" => "", "P_Completado" => "", "PAC" => "", "Activa" => "", "Categoria_CNC" => "",
			"CNC" => "", "Observaciones_CNC" => "", "Rendimientos" => "", "codigo_actividad" => "",
			"proyeccionSemana" => "", "diasSemanaInicial" => "", "diasLleva" => "", "diasSemana" => "", "diasTotales" => ""
		);
		header('Content-Type: application/json');
		echo json_encode($arreglo1);
	} else {
		$querySemanas = "SELECT Fecha_Inicio_Sem, Fecha_Fin_Sem FROM {$dbPrefix}_semanas_activas WHERE Semana = ?";
		$stmtSemanas = $db->query($querySemanas, [$semana]);
		$dataSemanas = $stmtSemanas->fetch();

		$Fecha_Inicio_Sem = date("Y-m-d", strtotime($dataSemanas["Fecha_Inicio_Sem"]));
		$Fecha_Fin_Sem = date("Y-m-d", strtotime($dataSemanas["Fecha_Fin_Sem"]));

		$queryData = "SELECT * FROM {$dbPrefix}_programacion_semanal WHERE Semana = ? AND (Activa = '1' OR Activa = 'NA') ORDER BY Consecutivo_En_Programa ASC, Activa ASC, Consecutivo ASC";
		$stmtData = $db->query($queryData, [$semana]);

		$arreglo = ["data" => []];
		while ($data = $stmtData->fetch()) {
			$Fecha_Inicio_Act = date("Y-m-d", strtotime($data['Fecha_Inicio']));
			$Fecha_Fin_Act = date("Y-m-d", strtotime($data['Fecha_Fin']));
			$Ejecutado = $data['Ejecutado'];

			$diasTotales = ((strtotime($Fecha_Fin_Act) - strtotime($Fecha_Inicio_Act)) / 86400) + 1;
			$diasTranscurridos = ((strtotime($Fecha_Fin_Sem) - strtotime($Fecha_Inicio_Act)) / 86400) + 1;

			if ($diasTranscurridos <= 0) {
				$proyeccionSemana = 0;
			} elseif ($diasTranscurridos <= 7) {
				$proyeccionSemana = $diasTranscurridos / $diasTotales;
			} else {
				if (($diasTotales - $diasTranscurridos) >= 7) {
					$proyeccionSemana = 7 / $diasTotales;
				} elseif ($diasTotales - $diasTranscurridos < 0) {
					$proyeccionSemana = 1 - $Ejecutado;
				} else {
					$proyeccionSemana = 1 - (($diasTotales - $diasTranscurridos) / $diasTotales);
				}
			}

			$data["proyeccionSemana"] = $proyeccionSemana > 1 ? 1 : $proyeccionSemana;

			if (($diasTranscurridos + $proyeccionSemana) >= 1 && $diasTotales >= ($diasTranscurridos + $proyeccionSemana)) {
				$data["Ejecutado_Fin_Semana"] = $Ejecutado + $proyeccionSemana;
			} else if ($diasTotales < ($diasTranscurridos + $proyeccionSemana) || ($Ejecutado + $proyeccionSemana) > 1) {
				$data["Ejecutado_Fin_Semana"] = 1;
			} else if ($diasTranscurridos < 1) {
				$data["Ejecutado_Fin_Semana"] = 0;
			}

			$arreglo["data"][] = $data;
		}

		header('Content-Type: application/json');
		echo json_encode($arreglo, JSON_UNESCAPED_UNICODE);
	}
?>
