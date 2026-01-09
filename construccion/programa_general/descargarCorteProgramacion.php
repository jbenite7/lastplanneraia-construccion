<?php
	require_once (__DIR__ . "/../conexion.php");
	// El objeto $db (instancia de Database) ya está disponible desde conexion.php

	$dbPrefix = $_POST['db'] ?? '';
	// Validación estricta del prefijo de la base de datos
	if (!preg_match('/^[a-zA-Z0-9_]+$/', $dbPrefix)) {
		die(json_encode(["error" => "Parámetro de base de datos inválido."]));
	}

	$semana = filter_var($_POST['semana'] ?? 0, FILTER_VALIDATE_INT);

	$query = "SELECT Consecutivo_en_Programa as 'Consecutivo en Programa', Id, codigo_actividad as 'Codigo Actividad', Actividad, Fecha_Inicio as 'Fecha Inicio', Fecha_Fin as 'Fecha Fin', Ruta_Critica as 'Ruta Critica', unidad as 'Unidad', cantidad_ppto as 'Cantidad en Presupuesto', Ejecutado, Estado FROM {$dbPrefix}_programa_consolidado WHERE Semana = ? ORDER BY Consecutivo ASC, Consecutivo_en_Programa ASC, Id ASC";
	
	try {
		$stmt = $db->query($query, [$semana]);
		$listadoCorte = [];
		while($data = $stmt->fetch()){
			$listadoCorte[] = $data;
		}

		$filename = "corte_programacion_semana_" . $semana . ".json";
		$filepath = "cortesProgramacion/" . $filename;
		
		if (!file_exists('cortesProgramacion')) {
			mkdir('cortesProgramacion', 0777, true);
		}

		file_put_contents($filepath, json_encode($listadoCorte, JSON_UNESCAPED_UNICODE));
		echo json_encode("../programa_general/" . $filepath);

	} catch (Exception $e) {
		error_log("Error en descargarCorteProgramacion.php: " . $e->getMessage());
		echo json_encode(["error" => $e->getMessage()]);
	}
?>