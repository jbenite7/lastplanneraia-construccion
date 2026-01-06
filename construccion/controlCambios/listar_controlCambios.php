<?php
	require_once (__DIR__ . "/../conexion.php");
	// El objeto $db (instancia de Database) ya está disponible desde conexion.php

	$dbPrefix = $_GET['db'] ?? '';
	// Validación estricta del prefijo de la base de datos
	if (!preg_match('/^[a-zA-Z0-9_]+$/', $dbPrefix)) {
		die(json_encode(["error" => "Parámetro de base de datos inválido."]));
	}

	$queryCount = "SELECT COUNT(*) as total FROM {$dbPrefix}_cambios";
	$stmtCount = $db->query($queryCount);
	$rowCount = $stmtCount->fetch();

	$conteo = $rowCount['total'] ?? 0;

	if ($conteo == 0) {
		$arreglo1["data"][] = array(
			"id" => "", "solicitanteCambio" => "", "detalleSolicitanteOtro" => "", "fechaSolicitud" => "",
			"prioridad" => "", "tipoCambio" => "", "responsableSolucion" => "", "detalleResponsableSolucion" => "",
			"justificacion" => "", "descripcion" => "", "incidenciaAlcance" => "", "tiempoCronograma" => "",
			"tiempoCronogramaAfectado" => "", "incidenciaCronograma" => "", "costoDirecto" => "", "valorPresupuesto" => "",
			"costoDirectoAIU" => "", "costoDirectoAIUIVA" => "", "valorAprobado" => "", "incidenciaPresupuesto" => "",
			"incidenciaCalidad" => "", "incidenciaRiesgo" => "", "incidenciaRecurso" => "", "fechaTentativaDefinicion" => "",
			"fechaEntregaInterventoria" => "", "Observaciones" => "", "fechaDefinicion" => "", "aprobacion" => "",
			"soportes" => "{\"soportes\": [{\"consecutivo\":1,\"descripcion\":\"\",\"link\":\"\"}]}"
		);
		header('Content-Type: application/json');
		echo json_encode($arreglo1);
	} else {
		$queryData = "SELECT 
				`id`, `solicitanteCambio`, `detalleSolicitanteOtro`, `fechaSolicitud`, `prioridad`, `tipoCambio`, 
				`responsableSolucion`, `detalleResponsableSolucion`, `justificacion`, `descripcion`, 
				`incidenciaAlcance`, `tiempoCronograma`, `tiempoCronogramaAfectado`, `incidenciaCronograma`, 
				`valorPresupuesto`, `costoDirecto`, `costoDirectoAIU`, `costoDirectoAIUIVA`, `valorAprobado`, 
				`incidenciaPresupuesto`, `incidenciaCalidad`, `incidenciaRiesgo`, `incidenciaRecurso`, 
				`fechaTentativaDefinicion`, `fechaEntregaInterventoria`, `Observaciones`, `fechaDefinicion`, 
				`aprobacion`, `soportes` 
			FROM {$dbPrefix}_cambios";
		
		$stmtData = $db->query($queryData);

		$arreglo = ["data" => []];
		while ($data = $stmtData->fetch()) {
			$arreglo["data"][] = $data;
		}

		header('Content-Type: application/json');
		echo json_encode($arreglo, JSON_UNESCAPED_UNICODE);
	}
?>
