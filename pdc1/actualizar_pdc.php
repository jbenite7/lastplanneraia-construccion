<?php
	require ("../conexion.php");

 	$db=$_POST['db'];
	$semana=$_POST['semana'];

	// $db="brizaDelCabrero";
	// $semana=1;
	$queryPDCActivo = "SELECT pdcActivo FROM general_proyectos_procesos WHERE Base_de_datos = '$db' AND Area= 'Construcción'";
	$resultadoPDCActivo = mysqli_query($conexion, $queryPDCActivo);
	$dataPDCActivo=mysqli_fetch_assoc($resultadoPDCActivo);
	$pdcActivo=$dataPDCActivo["pdcActivo"];

	if($pdcActivo == 1){
		$query = "SELECT COUNT(*) AS conteo FROM ".$db."_pdc WHERE titulo=0 AND semana = $semana AND fechaInicio IS NOT NULL";
		//echo $query;
		$resultado = mysqli_query($conexion, $query);
		$data=mysqli_fetch_assoc($resultado);
		$conteo=$data["conteo"];
		//echo $conteo;
		if ($conteo==0){
			$contratosVigentesSI = "";
			$contratosVigentesS = "";
			$contratosVigentesMO = "";

			$query1 = "DELETE FROM $db"."_pdc WHERE (titulo=1 AND semana = $semana) OR (titulo=0 AND fechaInicio IS NULL AND semana = $semana) ";
			$resultado1 = mysqli_query($conexion, $query1);
			if(!$resultado1){
				die(mysqli_error($conexion));
			}else{

				$query2 = insertarPaquetes($db, $semana, $contratosVigentesSI, $contratosVigentesS, $contratosVigentesMO);
				$resultado2 = mysqli_query($conexion, $query2);

				if(!$resultado2){
					die(mysqli_error($conexion));
				}else{
					sleep(1);
					$query3 = generarEstadoProceso($db, $semana, $conexion);
					if($query3 == ""){
						$informacion["respuesta"] = "BIEN";
						echo json_encode($informacion);
					}else{
						$resultado3 = mysqli_multi_query($conexion, $query3);
						if(!$resultado3){
							die(mysqli_error($conexion));
						}else{
							$informacion["respuesta"] = "BIEN";
							echo json_encode($informacion);
						}
					}
				}
			}
		}else{
			$query0_1 = "SELECT GROUP_CONCAT(CONCAT('CONCAT(paqueteContratacion, \'&\', tipoPaquete) != \'', paqueteContratacion, '&', tipoPaquete, '\'') SEPARATOR ' AND ') AS contratos
			FROM (SELECT DISTINCT paqueteSI1 AS paqueteContratacion, 'Suministro e Instalación' AS tipoPaquete FROM $db"."_actividades WHERE fechaInicio IS NOT NULL AND semanaActualizacion = $semana AND paqueteSI1 IS NOT NULL AND paqueteSI1 != ''
						UNION SELECT DISTINCT paqueteSI2 AS paqueteContratacion, 'Suministro e Instalación' AS tipoPaquete FROM $db"."_actividades WHERE fechaInicio IS NOT NULL AND semanaActualizacion = $semana AND paqueteSI2 IS NOT NULL AND paqueteSI2 != ''
						UNION SELECT DISTINCT paqueteSI3 AS paqueteContratacion, 'Suministro e Instalación' AS tipoPaquete FROM $db"."_actividades WHERE fechaInicio IS NOT NULL AND semanaActualizacion = $semana AND paqueteSI3 IS NOT NULL AND paqueteSI3 != ''
						UNION SELECT DISTINCT paqueteSI4 AS paqueteContratacion, 'Suministro e Instalación' AS tipoPaquete FROM $db"."_actividades WHERE fechaInicio IS NOT NULL AND semanaActualizacion = $semana AND paqueteSI4 IS NOT NULL AND paqueteSI4 != ''
						UNION SELECT DISTINCT paqueteSI5 AS paqueteContratacion, 'Suministro e Instalación' AS tipoPaquete FROM $db"."_actividades WHERE fechaInicio IS NOT NULL AND semanaActualizacion = $semana AND paqueteSI5 IS NOT NULL AND paqueteSI5 != ''
						UNION SELECT DISTINCT paqueteMO1 AS paqueteContratacion, 'Mano de Obra' AS tipoPaquete FROM $db"."_actividades WHERE fechaInicio IS NOT NULL AND semanaActualizacion = $semana AND paqueteMO1 IS NOT NULL AND paqueteMO1 != ''
						UNION SELECT DISTINCT paqueteMO2 AS paqueteContratacion, 'Mano de Obra' AS tipoPaquete FROM $db"."_actividades WHERE fechaInicio IS NOT NULL AND semanaActualizacion = $semana AND paqueteMO2 IS NOT NULL AND paqueteMO2 != ''
						UNION SELECT DISTINCT paqueteMO3 AS paqueteContratacion, 'Mano de Obra' AS tipoPaquete FROM $db"."_actividades WHERE fechaInicio IS NOT NULL AND semanaActualizacion = $semana AND paqueteMO3 IS NOT NULL AND paqueteMO3 != ''
						UNION SELECT DISTINCT paqueteMO4 AS paqueteContratacion, 'Mano de Obra' AS tipoPaquete FROM $db"."_actividades WHERE fechaInicio IS NOT NULL AND semanaActualizacion = $semana AND paqueteMO4 IS NOT NULL AND paqueteMO4 != ''
						UNION SELECT DISTINCT paqueteMO5 AS paqueteContratacion, 'Mano de Obra' AS tipoPaquete FROM $db"."_actividades WHERE fechaInicio IS NOT NULL AND semanaActualizacion = $semana AND paqueteMO5 IS NOT NULL AND paqueteMO5 != ''
						UNION SELECT DISTINCT paqueteS1 AS paqueteContratacion, 'Suministro' AS tipoPaquete FROM $db"."_actividades WHERE fechaInicio IS NOT NULL AND semanaActualizacion = $semana AND paqueteS1 IS NOT NULL AND paqueteS1 != ''
						UNION SELECT DISTINCT paqueteS2 AS paqueteContratacion, 'Suministro' AS tipoPaquete FROM $db"."_actividades WHERE fechaInicio IS NOT NULL AND semanaActualizacion = $semana AND paqueteS2 IS NOT NULL AND paqueteS2 != ''
						UNION SELECT DISTINCT paqueteS3 AS paqueteContratacion, 'Suministro' AS tipoPaquete FROM $db"."_actividades WHERE fechaInicio IS NOT NULL AND semanaActualizacion = $semana AND paqueteS3 IS NOT NULL AND paqueteS3 != ''
						UNION SELECT DISTINCT paqueteS4 AS paqueteContratacion, 'Suministro' AS tipoPaquete FROM $db"."_actividades WHERE fechaInicio IS NOT NULL AND semanaActualizacion = $semana AND paqueteS4 IS NOT NULL AND paqueteS4 != ''
						UNION SELECT DISTINCT paqueteS5 AS paqueteContratacion, 'Suministro' AS tipoPaquete FROM $db"."_actividades WHERE fechaInicio IS NOT NULL AND semanaActualizacion = $semana AND paqueteS5 IS NOT NULL AND paqueteS5 != '')
			AS Tabla";

			//echo $query0_1;
			$resultado0_1 = mysqli_query($conexion, $query0_1);
			//print_r($resultado0_1);
			if(!$resultado0_1){
				die(mysqli_error($conexion));
			} else{
				$data0_1=mysqli_fetch_assoc($resultado0_1);
				$contratosVigentes = $data0_1["contratos"];
				//echo "contratos: " . $contratosVigentes;
				if(empty($contratosVigentes)){
					$query0_2 = "DELETE FROM $db"."_pdc WHERE (titulo=0 AND semana = $semana) OR (titulo=1 AND semana = $semana)";
				}else{
					$query0_2 = "DELETE FROM $db"."_pdc WHERE (titulo=0 AND semana = $semana AND $contratosVigentes) OR (titulo=1 AND semana = $semana)";
				}
				$resultado0_2 = mysqli_query($conexion, $query0_2);

				$query1 ="SELECT * FROM $db"."_pdc WHERE titulo=0 AND semana = $semana";
				$resultado1 = mysqli_query($conexion, $query1);

				if(!$resultado1){
					die(mysqli_error($conexion));
				}else{
					$query2 = "";
					$contratosVigentesSI = "";
					$contratosVigentesS = "";
					$contratosVigentesMO = "";
					while($data=mysqli_fetch_assoc($resultado1)){
						$tipoPaquete = $data["tipoPaquete"];
						$paqueteContratacion = $data["paqueteContratacion"];
						if($tipoPaquete == "Suministro e Instalación"){
							$tipoContrato = 2;
							$grupo ="SI";
							$contratosVigentesSI .= "paqueteContratacion != '$paqueteContratacion' AND ";
						}else if($tipoPaquete == "Suministro"){
							$tipoContrato = 1;
							$grupo ="S";
							$contratosVigentesS .= "paqueteContratacion != '$paqueteContratacion' AND ";
						}else if($tipoPaquete == "Mano de Obra"){
							$tipoContrato = 1;
							$grupo ="MO";
							$contratosVigentesMO .= "paqueteContratacion != '$paqueteContratacion' AND ";
						}


						$query2 .= "UPDATE $db"."_pdc SET semana = $semana, ";

						$query2 .= "contratos=(SELECT GROUP_CONCAT(REPLACE(actividad, ';', '; ') SEPARATOR '; ') FROM (SELECT actividad, descripcionActividad, fechaInicio, tipoContrato, semanaActualizacion, ".$grupo."1 AS contrato, paquete".$grupo."1 AS paqueteContratacion FROM $db"."_actividades WHERE semanaActualizacion = $semana AND tipoContrato=$tipoContrato AND paquete".$grupo."1 = '$paqueteContratacion' UNION SELECT actividad, descripcionActividad, fechaInicio, tipoContrato, semanaActualizacion, ".$grupo."2 AS contrato, paquete".$grupo."2 AS paqueteContratacion FROM $db"."_actividades WHERE semanaActualizacion = $semana AND tipoContrato=$tipoContrato AND paquete".$grupo."2 = '$paqueteContratacion' UNION SELECT actividad, descripcionActividad, fechaInicio, tipoContrato, semanaActualizacion, ".$grupo."3 AS contrato, paquete".$grupo."3 AS paqueteContratacion FROM $db"."_actividades WHERE semanaActualizacion = $semana AND tipoContrato=$tipoContrato AND paquete".$grupo."3 = '$paqueteContratacion' UNION SELECT actividad, descripcionActividad, fechaInicio, tipoContrato, semanaActualizacion, ".$grupo."4 AS contrato, paquete".$grupo."4 AS paqueteContratacion FROM $db"."_actividades WHERE semanaActualizacion = $semana AND tipoContrato=$tipoContrato AND paquete".$grupo."4 = '$paqueteContratacion' UNION SELECT actividad, descripcionActividad, fechaInicio, tipoContrato, semanaActualizacion, ".$grupo."5 AS contrato, paquete".$grupo."5 AS paqueteContratacion FROM $db"."_actividades WHERE semanaActualizacion = $semana AND tipoContrato=$tipoContrato AND paquete".$grupo."5 = '$paqueteContratacion') AS Tabla), ";

						$query2 .= "fechaInicio=(SELECT MIN(fechaInicio) FROM (SELECT actividad, descripcionActividad, fechaInicio, tipoContrato, semanaActualizacion, ".$grupo."1 AS contrato, paquete".$grupo."1 AS paqueteContratacion FROM $db"."_actividades WHERE semanaActualizacion = $semana AND tipoContrato=$tipoContrato AND paquete".$grupo."1 = '$paqueteContratacion' UNION SELECT actividad, descripcionActividad, fechaInicio, tipoContrato, semanaActualizacion, ".$grupo."2 AS contrato, paquete".$grupo."2 AS paqueteContratacion FROM $db"."_actividades WHERE semanaActualizacion = $semana AND tipoContrato=$tipoContrato AND paquete".$grupo."2 = '$paqueteContratacion' UNION SELECT actividad, descripcionActividad, fechaInicio, tipoContrato, semanaActualizacion, ".$grupo."3 AS contrato, paquete".$grupo."3 AS paqueteContratacion FROM $db"."_actividades WHERE semanaActualizacion = $semana AND tipoContrato=$tipoContrato AND paquete".$grupo."3 = '$paqueteContratacion' UNION SELECT actividad, descripcionActividad, fechaInicio, tipoContrato, semanaActualizacion, ".$grupo."4 AS contrato, paquete".$grupo."4 AS paqueteContratacion FROM $db"."_actividades WHERE semanaActualizacion = $semana AND tipoContrato=$tipoContrato AND paquete".$grupo."4 = '$paqueteContratacion' UNION SELECT actividad, descripcionActividad, fechaInicio, tipoContrato, semanaActualizacion, ".$grupo."5 AS contrato, paquete".$grupo."5 AS paqueteContratacion FROM $db"."_actividades WHERE semanaActualizacion = $semana AND tipoContrato=$tipoContrato AND paquete".$grupo."5 = '$paqueteContratacion') AS Tabla) ";

						$query2 .= "WHERE semana = $semana AND tipoPaquete='$tipoPaquete' AND paqueteContratacion='$paqueteContratacion'; ";

					}

					if($query2 == ""){
						$query2 = insertarPaquetes($db, $semana, $contratosVigentesSI, $contratosVigentesS, $contratosVigentesMO);
						$resultado2 = mysqli_query($conexion, $query2);

						if(!$resultado2){
							die(mysqli_error($conexion));
						}else{
							sleep(1);
							$queryDuplicar = crearSubcontratosDuplicados($db, $semana, $conexion);
							if(empty($queryDuplicar)){
								$resultadoDuplicar = "OK";
							}else{
								$resultadoDuplicar = mysqli_query($conexion, $queryDuplicar);
								if(!$resultadoDuplicar){
									die(mysqli_error($conexion));
								}else{
									$resultadoDuplicar = "OK";
								}
							}
							
							if($resultadoDuplicar != "OK"){
								die(mysqli_error($conexion));
							}else{
								$query3 = generarEstadoProceso($db, $semana, $conexion);
								if($query3 == ""){
									$informacion["respuesta"] = "BIEN";
									echo json_encode($informacion);
								}else{
									$resultado3 = mysqli_multi_query($conexion, $query3);
									if(!$resultado3){
										die(mysqli_error($conexion));
									}else{
										$informacion["respuesta"] = "BIEN";
										echo json_encode($informacion);
									}
								}
							}
						}
					}else{
						$resultado2 = mysqli_multi_query($conexion, $query2);
						sleep(1);
						require ("../conexion.php");

						if(!$resultado2){
							die(mysqli_error($conexion));
						}else{
							if($contratosVigentesSI != ""){
								$contratosVigentesSI = "WHERE " . substr($contratosVigentesSI,0,-4);
							}
							if($contratosVigentesS != ""){
								$contratosVigentesS = "WHERE " . substr($contratosVigentesS,0,-4);
							}
							if($contratosVigentesMO != ""){
								$contratosVigentesMO = "WHERE " . substr($contratosVigentesMO,0,-4);
							}

							$query3 = insertarPaquetes($db, $semana, $contratosVigentesSI, $contratosVigentesS, $contratosVigentesMO);

							//echo $query3;
							$resultado3 = mysqli_query($conexion, $query3);
							if(!$resultado3){
								die(mysqli_error($conexion));
							}else{
								sleep(1);
								$queryDuplicar = crearSubcontratosDuplicados($db, $semana, $conexion);
								if(empty($queryDuplicar)){
									$resultadoDuplicar = "OK";
								}else{
									$resultadoDuplicar = mysqli_query($conexion, $queryDuplicar);
									if(!$resultadoDuplicar){
										die(mysqli_error($conexion));
									}else{
										$resultadoDuplicar = "OK";
									}
								}

								if($resultadoDuplicar != "OK"){
									die(mysqli_error($conexion));
								}else{
									$query4 = generarEstadoProceso($db, $semana, $conexion);
									if($query4 == ""){
										$informacion["respuesta"] = "BIEN";
										echo json_encode($informacion);
									}else{
										$resultado4 = mysqli_multi_query($conexion, $query4);
										if(!$resultado4){
											die(mysqli_error($conexion));
										}else{
											$informacion["respuesta"] = "BIEN";
											echo json_encode($informacion);
										}
									}
								}
							}
						}
					}
				}
			}
			mysqli_free_result($resultado1);
		}
	}else{
		$informacion["respuesta"] = "BIEN";
		echo json_encode($informacion);
	}



  	mysqli_close($conexion);

	function insertarPaquetes($db, $semana, $contratosVigentesSI, $contratosVigentesS, $contratosVigentesMO){
		$query1 ="INSERT INTO $db"."_pdc (consecutivo, titulo, semana, tipoPaquete, paqueteContratacion, contratos, fechaInicio, diasElaboracionPliegos, diasIngresoLicify, diasEntregaPliegos, diasReciboPropuestas, diasCuadrosComparativos, diasLegalizacionContrato, diasFabricacion, diasInsumosObra, fechaElaboracionPliegos, fechaIngresoLicify, fechaEntregaPliegos, fechaReciboPropuestas, fechaCuadrosComparativos, fechaLegalizacionContrato, fechaFabricacion, fechaInsumosObra)";

		$query1 .=" SELECT NULL AS consecutivo, 1 AS titulo, $semana AS semana, 'Suministro e Instalación' AS tipoPaquete, 'Suministro e Instalación' AS paqueteContratacion, NULL AS contratos, NULL AS fechaInicio, NULL AS diasElaboracionPliegos, NULL AS diasIngresoLicify, NULL AS diasEntregaPliegos, NULL AS diasReciboPropuestas, NULL AS diasCuadrosComparativos, NULL AS diasLegalizacionContrato, NULL AS diasFabricacion, NULL AS diasInsumosObra, NULL AS fechaElaboracionPliegos, NULL AS fechaIngresoLicify, NULL AS fechaEntregaPliegos, NULL AS fechaReciboPropuestas, NULL AS fechaCuadrosComparativos, NULL AS fechaLegalizacionContrato, NULL AS fechaFabricacion, NULL AS fechaInsumosObra";

		$query1 .= " UNION SELECT NULL AS consecutivo, 0 AS titulo, $semana AS semana, 'Suministro e Instalación' AS tipoPaquete, paqueteContratacion, GROUP_CONCAT(actividad SEPARATOR '; ') AS contratos, MIN(fechaInicio) AS fechaInicio, diasElaboracionPliegos, diasIngresoLicify, diasEntregaPliegos, diasReciboPropuestas, diasCuadrosComparativos, diasLegalizacionContrato, diasFabricacion, diasInsumosObra, DATE_SUB(fechaInicio, INTERVAL (diasInsumosObra + diasFabricacion + diasLegalizacionContrato + diasCuadrosComparativos + diasReciboPropuestas + diasEntregaPliegos + diasIngresoLicify + diasElaboracionPliegos) DAY) AS fechaElaboracionPliegos, DATE_SUB(fechaInicio, INTERVAL (diasInsumosObra + diasFabricacion + diasLegalizacionContrato + diasCuadrosComparativos + diasReciboPropuestas + diasEntregaPliegos + diasIngresoLicify) DAY) AS fechaIngresoLicify, DATE_SUB(fechaInicio, INTERVAL (diasInsumosObra + diasFabricacion + diasLegalizacionContrato + diasCuadrosComparativos + diasReciboPropuestas + diasEntregaPliegos) DAY) AS fechaEntregaPliegos, DATE_SUB(fechaInicio, INTERVAL (diasInsumosObra + diasFabricacion + diasLegalizacionContrato + diasCuadrosComparativos + diasReciboPropuestas) DAY) AS fechaReciboPropuestas, DATE_SUB(fechaInicio, INTERVAL (diasInsumosObra + diasFabricacion + diasLegalizacionContrato + diasCuadrosComparativos) DAY) AS fechaCuadrosComparativos, DATE_SUB(fechaInicio, INTERVAL (diasInsumosObra + diasFabricacion + diasLegalizacionContrato) DAY) AS fechaLegalizacionContrato, DATE_SUB(fechaInicio, INTERVAL (diasInsumosObra + diasFabricacion) DAY) AS fechaFabricacion, DATE_SUB(fechaInicio, INTERVAL diasInsumosObra DAY) AS fechaInsumosObra FROM (SELECT `$db"."_actividades`.actividad AS actividad, `$db"."_actividades`.descripcionActividad AS descripcionActividad, `$db"."_actividades`.fechaInicio AS fechaInicio, `$db"."_actividades`.tipoContrato AS tipoContrato, `$db"."_actividades`.semanaActualizacion AS semanaActualizacion, `$db"."_actividades`.SI1 AS contrato, `$db"."_actividades`.paqueteSI1 AS paqueteContratacion, `general_dias_procesos_contratacion`.diasElaboracionPliegos AS diasElaboracionPliegos, `general_dias_procesos_contratacion`.diasIngresoLicify AS diasIngresoLicify, `general_dias_procesos_contratacion`.diasEntregaPliegos AS diasEntregaPliegos, `general_dias_procesos_contratacion`.diasReciboPropuestas AS diasReciboPropuestas, `general_dias_procesos_contratacion`.diasCuadrosComparativos AS diasCuadrosComparativos, `general_dias_procesos_contratacion`.diasLegalizacionContrato AS diasLegalizacionContrato, `general_dias_procesos_contratacion`.diasFabricacion AS diasFabricacion, `general_dias_procesos_contratacion`.diasInsumosObra AS diasInsumosObra FROM `$db"."_actividades` INNER JOIN `general_dias_procesos_contratacion` ON `$db"."_actividades`.paqueteSI1 = `general_dias_procesos_contratacion`.paqueteContratacion  WHERE fechaInicio IS NOT NULL AND semanaActualizacion = $semana AND paqueteSI1 IS NOT NULL AND paqueteSI1 != '' AND `general_dias_procesos_contratacion`.tipoPaquete='Suministro e Instalación' UNION SELECT `$db"."_actividades`.actividad AS actividad, `$db"."_actividades`.descripcionActividad AS descripcionActividad, `$db"."_actividades`.fechaInicio AS fechaInicio, `$db"."_actividades`.tipoContrato AS tipoContrato, `$db"."_actividades`.semanaActualizacion AS semanaActualizacion, `$db"."_actividades`.SI2 AS contrato, `$db"."_actividades`.paqueteSI2 AS paqueteContratacion, `general_dias_procesos_contratacion`.diasElaboracionPliegos AS diasElaboracionPliegos, `general_dias_procesos_contratacion`.diasIngresoLicify AS diasIngresoLicify, `general_dias_procesos_contratacion`.diasEntregaPliegos AS diasEntregaPliegos, `general_dias_procesos_contratacion`.diasReciboPropuestas AS diasReciboPropuestas, `general_dias_procesos_contratacion`.diasCuadrosComparativos AS diasCuadrosComparativos, `general_dias_procesos_contratacion`.diasLegalizacionContrato AS diasLegalizacionContrato, `general_dias_procesos_contratacion`.diasFabricacion AS diasFabricacion, `general_dias_procesos_contratacion`.diasInsumosObra AS diasInsumosObra FROM `$db"."_actividades` INNER JOIN `general_dias_procesos_contratacion` ON `$db"."_actividades`.paqueteSI2 = `general_dias_procesos_contratacion`.paqueteContratacion  WHERE fechaInicio IS NOT NULL AND semanaActualizacion = $semana AND paqueteSI2 IS NOT NULL AND paqueteSI2 != '' AND `general_dias_procesos_contratacion`.tipoPaquete='Suministro e Instalación' UNION SELECT `$db"."_actividades`.actividad AS actividad, `$db"."_actividades`.descripcionActividad AS descripcionActividad, `$db"."_actividades`.fechaInicio AS fechaInicio, `$db"."_actividades`.tipoContrato AS tipoContrato, `$db"."_actividades`.semanaActualizacion AS semanaActualizacion, `$db"."_actividades`.SI3 AS contrato, `$db"."_actividades`.paqueteSI3 AS paqueteContratacion, `general_dias_procesos_contratacion`.diasElaboracionPliegos AS diasElaboracionPliegos, `general_dias_procesos_contratacion`.diasIngresoLicify AS diasIngresoLicify, `general_dias_procesos_contratacion`.diasEntregaPliegos AS diasEntregaPliegos, `general_dias_procesos_contratacion`.diasReciboPropuestas AS diasReciboPropuestas, `general_dias_procesos_contratacion`.diasCuadrosComparativos AS diasCuadrosComparativos, `general_dias_procesos_contratacion`.diasLegalizacionContrato AS diasLegalizacionContrato, `general_dias_procesos_contratacion`.diasFabricacion AS diasFabricacion, `general_dias_procesos_contratacion`.diasInsumosObra AS diasInsumosObra FROM `$db"."_actividades` INNER JOIN `general_dias_procesos_contratacion` ON `$db"."_actividades`.paqueteSI3 = `general_dias_procesos_contratacion`.paqueteContratacion  WHERE fechaInicio IS NOT NULL AND semanaActualizacion = $semana AND paqueteSI3 IS NOT NULL AND paqueteSI3 != '' AND `general_dias_procesos_contratacion`.tipoPaquete='Suministro e Instalación' UNION SELECT `$db"."_actividades`.actividad AS actividad, `$db"."_actividades`.descripcionActividad AS descripcionActividad, `$db"."_actividades`.fechaInicio AS fechaInicio, `$db"."_actividades`.tipoContrato AS tipoContrato, `$db"."_actividades`.semanaActualizacion AS semanaActualizacion, `$db"."_actividades`.SI4 AS contrato, `$db"."_actividades`.paqueteSI4 AS paqueteContratacion, `general_dias_procesos_contratacion`.diasElaboracionPliegos AS diasElaboracionPliegos, `general_dias_procesos_contratacion`.diasIngresoLicify AS diasIngresoLicify, `general_dias_procesos_contratacion`.diasEntregaPliegos AS diasEntregaPliegos, `general_dias_procesos_contratacion`.diasReciboPropuestas AS diasReciboPropuestas, `general_dias_procesos_contratacion`.diasCuadrosComparativos AS diasCuadrosComparativos, `general_dias_procesos_contratacion`.diasLegalizacionContrato AS diasLegalizacionContrato, `general_dias_procesos_contratacion`.diasFabricacion AS diasFabricacion, `general_dias_procesos_contratacion`.diasInsumosObra AS diasInsumosObra FROM `$db"."_actividades` INNER JOIN `general_dias_procesos_contratacion` ON `$db"."_actividades`.paqueteSI4 = `general_dias_procesos_contratacion`.paqueteContratacion  WHERE fechaInicio IS NOT NULL AND semanaActualizacion = $semana AND paqueteSI4 IS NOT NULL AND paqueteSI4 != '' AND `general_dias_procesos_contratacion`.tipoPaquete='Suministro e Instalación' UNION SELECT `$db"."_actividades`.actividad AS actividad, `$db"."_actividades`.descripcionActividad AS descripcionActividad, `$db"."_actividades`.fechaInicio AS fechaInicio, `$db"."_actividades`.tipoContrato AS tipoContrato, `$db"."_actividades`.semanaActualizacion AS semanaActualizacion, `$db"."_actividades`.SI5 AS contrato, `$db"."_actividades`.paqueteSI5 AS paqueteContratacion, `general_dias_procesos_contratacion`.diasElaboracionPliegos AS diasElaboracionPliegos, `general_dias_procesos_contratacion`.diasIngresoLicify AS diasIngresoLicify, `general_dias_procesos_contratacion`.diasEntregaPliegos AS diasEntregaPliegos, `general_dias_procesos_contratacion`.diasReciboPropuestas AS diasReciboPropuestas, `general_dias_procesos_contratacion`.diasCuadrosComparativos AS diasCuadrosComparativos, `general_dias_procesos_contratacion`.diasLegalizacionContrato AS diasLegalizacionContrato, `general_dias_procesos_contratacion`.diasFabricacion AS diasFabricacion, `general_dias_procesos_contratacion`.diasInsumosObra AS diasInsumosObra FROM `$db"."_actividades` INNER JOIN `general_dias_procesos_contratacion` ON `$db"."_actividades`.paqueteSI5 = `general_dias_procesos_contratacion`.paqueteContratacion  WHERE fechaInicio IS NOT NULL AND semanaActualizacion = $semana AND paqueteSI5 IS NOT NULL AND paqueteSI5 != '' AND `general_dias_procesos_contratacion`.tipoPaquete='Suministro e Instalación') AS Tabla $contratosVigentesSI GROUP BY paqueteContratacion";

		$query1 .=" UNION SELECT NULL AS consecutivo, 1 AS titulo, $semana AS semana, 'Mano de Obra' AS tipoPaquete, 'Mano de Obra' AS paqueteContratacion, NULL AS contratos, NULL AS fechaInicio, NULL AS diasElaboracionPliegos, NULL AS diasIngresoLicify, NULL AS diasEntregaPliegos, NULL AS diasReciboPropuestas, NULL AS diasCuadrosComparativos, NULL AS diasLegalizacionContrato, NULL AS diasFabricacion, NULL AS diasInsumosObra, NULL AS fechaElaboracionPliegos, NULL AS fechaIngresoLicify, NULL AS fechaEntregaPliegos, NULL AS fechaReciboPropuestas, NULL AS fechaCuadrosComparativos, NULL AS fechaLegalizacionContrato, NULL AS fechaFabricacion, NULL AS fechaInsumosObra";

		$query1 .= " UNION SELECT NULL AS consecutivo, 0 AS titulo, $semana AS semana, 'Mano de Obra' AS tipoPaquete, paqueteContratacion, GROUP_CONCAT(actividad SEPARATOR '; ') AS contratos, MIN(fechaInicio) AS fechaInicio, diasElaboracionPliegos, diasIngresoLicify, diasEntregaPliegos, diasReciboPropuestas, diasCuadrosComparativos, diasLegalizacionContrato, diasFabricacion, diasInsumosObra, DATE_SUB(fechaInicio, INTERVAL (diasInsumosObra + diasFabricacion + diasLegalizacionContrato + diasCuadrosComparativos + diasReciboPropuestas + diasEntregaPliegos + diasIngresoLicify + diasElaboracionPliegos) DAY) AS fechaElaboracionPliegos, DATE_SUB(fechaInicio, INTERVAL (diasInsumosObra + diasFabricacion + diasLegalizacionContrato + diasCuadrosComparativos + diasReciboPropuestas + diasEntregaPliegos + diasIngresoLicify) DAY) AS fechaIngresoLicify, DATE_SUB(fechaInicio, INTERVAL (diasInsumosObra + diasFabricacion + diasLegalizacionContrato + diasCuadrosComparativos + diasReciboPropuestas + diasEntregaPliegos) DAY) AS fechaEntregaPliegos, DATE_SUB(fechaInicio, INTERVAL (diasInsumosObra + diasFabricacion + diasLegalizacionContrato + diasCuadrosComparativos + diasReciboPropuestas) DAY) AS fechaReciboPropuestas, DATE_SUB(fechaInicio, INTERVAL (diasInsumosObra + diasFabricacion + diasLegalizacionContrato + diasCuadrosComparativos) DAY) AS fechaCuadrosComparativos, DATE_SUB(fechaInicio, INTERVAL (diasInsumosObra + diasFabricacion + diasLegalizacionContrato) DAY) AS fechaLegalizacionContrato, DATE_SUB(fechaInicio, INTERVAL (diasInsumosObra + diasFabricacion) DAY) AS fechaFabricacion, DATE_SUB(fechaInicio, INTERVAL diasInsumosObra DAY) AS fechaInsumosObra FROM (SELECT `$db"."_actividades`.actividad AS actividad, `$db"."_actividades`.descripcionActividad AS descripcionActividad, `$db"."_actividades`.fechaInicio AS fechaInicio, `$db"."_actividades`.tipoContrato AS tipoContrato, `$db"."_actividades`.semanaActualizacion AS semanaActualizacion, `$db"."_actividades`.MO1 AS contrato, `$db"."_actividades`.paqueteMO1 AS paqueteContratacion, `general_dias_procesos_contratacion`.diasElaboracionPliegos AS diasElaboracionPliegos, `general_dias_procesos_contratacion`.diasIngresoLicify AS diasIngresoLicify, `general_dias_procesos_contratacion`.diasEntregaPliegos AS diasEntregaPliegos, `general_dias_procesos_contratacion`.diasReciboPropuestas AS diasReciboPropuestas, `general_dias_procesos_contratacion`.diasCuadrosComparativos AS diasCuadrosComparativos, `general_dias_procesos_contratacion`.diasLegalizacionContrato AS diasLegalizacionContrato, `general_dias_procesos_contratacion`.diasFabricacion AS diasFabricacion, `general_dias_procesos_contratacion`.diasInsumosObra AS diasInsumosObra FROM `$db"."_actividades` INNER JOIN `general_dias_procesos_contratacion` ON `$db"."_actividades`.paqueteMO1 = `general_dias_procesos_contratacion`.paqueteContratacion  WHERE fechaInicio IS NOT NULL AND semanaActualizacion = $semana AND paqueteMO1 IS NOT NULL AND paqueteMO1 != '' AND `general_dias_procesos_contratacion`.tipoPaquete='Mano de Obra' UNION SELECT `$db"."_actividades`.actividad AS actividad, `$db"."_actividades`.descripcionActividad AS descripcionActividad, `$db"."_actividades`.fechaInicio AS fechaInicio, `$db"."_actividades`.tipoContrato AS tipoContrato, `$db"."_actividades`.semanaActualizacion AS semanaActualizacion, `$db"."_actividades`.MO2 AS contrato, `$db"."_actividades`.paqueteMO2 AS paqueteContratacion, `general_dias_procesos_contratacion`.diasElaboracionPliegos AS diasElaboracionPliegos, `general_dias_procesos_contratacion`.diasIngresoLicify AS diasIngresoLicify, `general_dias_procesos_contratacion`.diasEntregaPliegos AS diasEntregaPliegos, `general_dias_procesos_contratacion`.diasReciboPropuestas AS diasReciboPropuestas, `general_dias_procesos_contratacion`.diasCuadrosComparativos AS diasCuadrosComparativos, `general_dias_procesos_contratacion`.diasLegalizacionContrato AS diasLegalizacionContrato, `general_dias_procesos_contratacion`.diasFabricacion AS diasFabricacion, `general_dias_procesos_contratacion`.diasInsumosObra AS diasInsumosObra FROM `$db"."_actividades` INNER JOIN `general_dias_procesos_contratacion` ON `$db"."_actividades`.paqueteMO2 = `general_dias_procesos_contratacion`.paqueteContratacion  WHERE fechaInicio IS NOT NULL AND semanaActualizacion = $semana AND paqueteMO2 IS NOT NULL AND paqueteMO2 != '' AND `general_dias_procesos_contratacion`.tipoPaquete='Mano de Obra' UNION SELECT `$db"."_actividades`.actividad AS actividad, `$db"."_actividades`.descripcionActividad AS descripcionActividad, `$db"."_actividades`.fechaInicio AS fechaInicio, `$db"."_actividades`.tipoContrato AS tipoContrato, `$db"."_actividades`.semanaActualizacion AS semanaActualizacion, `$db"."_actividades`.MO3 AS contrato, `$db"."_actividades`.paqueteMO3 AS paqueteContratacion, `general_dias_procesos_contratacion`.diasElaboracionPliegos AS diasElaboracionPliegos, `general_dias_procesos_contratacion`.diasIngresoLicify AS diasIngresoLicify, `general_dias_procesos_contratacion`.diasEntregaPliegos AS diasEntregaPliegos, `general_dias_procesos_contratacion`.diasReciboPropuestas AS diasReciboPropuestas, `general_dias_procesos_contratacion`.diasCuadrosComparativos AS diasCuadrosComparativos, `general_dias_procesos_contratacion`.diasLegalizacionContrato AS diasLegalizacionContrato, `general_dias_procesos_contratacion`.diasFabricacion AS diasFabricacion, `general_dias_procesos_contratacion`.diasInsumosObra AS diasInsumosObra FROM `$db"."_actividades` INNER JOIN `general_dias_procesos_contratacion` ON `$db"."_actividades`.paqueteMO3 = `general_dias_procesos_contratacion`.paqueteContratacion  WHERE fechaInicio IS NOT NULL AND semanaActualizacion = $semana AND paqueteMO3 IS NOT NULL AND paqueteMO3 != '' AND `general_dias_procesos_contratacion`.tipoPaquete='Mano de Obra' UNION SELECT `$db"."_actividades`.actividad AS actividad, `$db"."_actividades`.descripcionActividad AS descripcionActividad, `$db"."_actividades`.fechaInicio AS fechaInicio, `$db"."_actividades`.tipoContrato AS tipoContrato, `$db"."_actividades`.semanaActualizacion AS semanaActualizacion, `$db"."_actividades`.MO4 AS contrato, `$db"."_actividades`.paqueteMO4 AS paqueteContratacion, `general_dias_procesos_contratacion`.diasElaboracionPliegos AS diasElaboracionPliegos, `general_dias_procesos_contratacion`.diasIngresoLicify AS diasIngresoLicify, `general_dias_procesos_contratacion`.diasEntregaPliegos AS diasEntregaPliegos, `general_dias_procesos_contratacion`.diasReciboPropuestas AS diasReciboPropuestas, `general_dias_procesos_contratacion`.diasCuadrosComparativos AS diasCuadrosComparativos, `general_dias_procesos_contratacion`.diasLegalizacionContrato AS diasLegalizacionContrato, `general_dias_procesos_contratacion`.diasFabricacion AS diasFabricacion, `general_dias_procesos_contratacion`.diasInsumosObra AS diasInsumosObra FROM `$db"."_actividades` INNER JOIN `general_dias_procesos_contratacion` ON `$db"."_actividades`.paqueteMO4 = `general_dias_procesos_contratacion`.paqueteContratacion  WHERE fechaInicio IS NOT NULL AND semanaActualizacion = $semana AND paqueteMO4 IS NOT NULL AND paqueteMO4 != '' AND `general_dias_procesos_contratacion`.tipoPaquete='Mano de Obra' UNION SELECT `$db"."_actividades`.actividad AS actividad, `$db"."_actividades`.descripcionActividad AS descripcionActividad, `$db"."_actividades`.fechaInicio AS fechaInicio, `$db"."_actividades`.tipoContrato AS tipoContrato, `$db"."_actividades`.semanaActualizacion AS semanaActualizacion, `$db"."_actividades`.MO5 AS contrato, `$db"."_actividades`.paqueteMO5 AS paqueteContratacion, `general_dias_procesos_contratacion`.diasElaboracionPliegos AS diasElaboracionPliegos, `general_dias_procesos_contratacion`.diasIngresoLicify AS diasIngresoLicify, `general_dias_procesos_contratacion`.diasEntregaPliegos AS diasEntregaPliegos, `general_dias_procesos_contratacion`.diasReciboPropuestas AS diasReciboPropuestas, `general_dias_procesos_contratacion`.diasCuadrosComparativos AS diasCuadrosComparativos, `general_dias_procesos_contratacion`.diasLegalizacionContrato AS diasLegalizacionContrato, `general_dias_procesos_contratacion`.diasFabricacion AS diasFabricacion, `general_dias_procesos_contratacion`.diasInsumosObra AS diasInsumosObra FROM `$db"."_actividades` INNER JOIN `general_dias_procesos_contratacion` ON `$db"."_actividades`.paqueteMO5 = `general_dias_procesos_contratacion`.paqueteContratacion  WHERE fechaInicio IS NOT NULL AND semanaActualizacion = $semana AND paqueteMO5 IS NOT NULL AND paqueteMO5 != '' AND `general_dias_procesos_contratacion`.tipoPaquete='Mano de Obra') AS Tabla $contratosVigentesMO GROUP BY paqueteContratacion";

		$query1 .=" UNION SELECT NULL AS consecutivo, 1 AS titulo, $semana AS semana, 'Suministro' AS tipoPaquete, 'Suministro' AS paqueteContratacion, NULL AS contratos, NULL AS fechaInicio, NULL AS diasElaboracionPliegos, NULL AS diasIngresoLicify, NULL AS diasEntregaPliegos, NULL AS diasReciboPropuestas, NULL AS diasCuadrosComparativos, NULL AS diasLegalizacionContrato, NULL AS diasFabricacion, NULL AS diasInsumosObra, NULL AS fechaElaboracionPliegos, NULL AS fechaIngresoLicify, NULL AS fechaEntregaPliegos, NULL AS fechaReciboPropuestas, NULL AS fechaCuadrosComparativos, NULL AS fechaLegalizacionContrato, NULL AS fechaFabricacion, NULL AS fechaInsumosObra";

		$query1 .= " UNION SELECT NULL AS consecutivo, 0 AS titulo, $semana AS semana, 'Suministro' AS tipoPaquete, paqueteContratacion, GROUP_CONCAT(actividad SEPARATOR '; ') AS contratos, MIN(fechaInicio) AS fechaInicio, diasElaboracionPliegos, diasIngresoLicify, diasEntregaPliegos, diasReciboPropuestas, diasCuadrosComparativos, diasLegalizacionContrato, diasFabricacion, diasInsumosObra, DATE_SUB(fechaInicio, INTERVAL (diasInsumosObra + diasFabricacion + diasLegalizacionContrato + diasCuadrosComparativos + diasReciboPropuestas + diasEntregaPliegos + diasIngresoLicify + diasElaboracionPliegos) DAY) AS fechaElaboracionPliegos, DATE_SUB(fechaInicio, INTERVAL (diasInsumosObra + diasFabricacion + diasLegalizacionContrato + diasCuadrosComparativos + diasReciboPropuestas + diasEntregaPliegos + diasIngresoLicify) DAY) AS fechaIngresoLicify, DATE_SUB(fechaInicio, INTERVAL (diasInsumosObra + diasFabricacion + diasLegalizacionContrato + diasCuadrosComparativos + diasReciboPropuestas + diasEntregaPliegos) DAY) AS fechaEntregaPliegos, DATE_SUB(fechaInicio, INTERVAL (diasInsumosObra + diasFabricacion + diasLegalizacionContrato + diasCuadrosComparativos + diasReciboPropuestas) DAY) AS fechaReciboPropuestas, DATE_SUB(fechaInicio, INTERVAL (diasInsumosObra + diasFabricacion + diasLegalizacionContrato + diasCuadrosComparativos) DAY) AS fechaCuadrosComparativos, DATE_SUB(fechaInicio, INTERVAL (diasInsumosObra + diasFabricacion + diasLegalizacionContrato) DAY) AS fechaLegalizacionContrato, DATE_SUB(fechaInicio, INTERVAL (diasInsumosObra + diasFabricacion) DAY) AS fechaFabricacion, DATE_SUB(fechaInicio, INTERVAL diasInsumosObra DAY) AS fechaInsumosObra FROM (SELECT `$db"."_actividades`.actividad AS actividad, `$db"."_actividades`.descripcionActividad AS descripcionActividad, `$db"."_actividades`.fechaInicio AS fechaInicio, `$db"."_actividades`.tipoContrato AS tipoContrato, `$db"."_actividades`.semanaActualizacion AS semanaActualizacion, `$db"."_actividades`.S1 AS contrato, `$db"."_actividades`.paqueteS1 AS paqueteContratacion, `general_dias_procesos_contratacion`.diasElaboracionPliegos AS diasElaboracionPliegos, `general_dias_procesos_contratacion`.diasIngresoLicify AS diasIngresoLicify, `general_dias_procesos_contratacion`.diasEntregaPliegos AS diasEntregaPliegos, `general_dias_procesos_contratacion`.diasReciboPropuestas AS diasReciboPropuestas, `general_dias_procesos_contratacion`.diasCuadrosComparativos AS diasCuadrosComparativos, `general_dias_procesos_contratacion`.diasLegalizacionContrato AS diasLegalizacionContrato, `general_dias_procesos_contratacion`.diasFabricacion AS diasFabricacion, `general_dias_procesos_contratacion`.diasInsumosObra AS diasInsumosObra FROM `$db"."_actividades` INNER JOIN `general_dias_procesos_contratacion` ON `$db"."_actividades`.paqueteS1 = `general_dias_procesos_contratacion`.paqueteContratacion  WHERE fechaInicio IS NOT NULL AND semanaActualizacion = $semana AND paqueteS1 IS NOT NULL AND paqueteS1 != '' AND `general_dias_procesos_contratacion`.tipoPaquete='Suministro' UNION SELECT `$db"."_actividades`.actividad AS actividad, `$db"."_actividades`.descripcionActividad AS descripcionActividad, `$db"."_actividades`.fechaInicio AS fechaInicio, `$db"."_actividades`.tipoContrato AS tipoContrato, `$db"."_actividades`.semanaActualizacion AS semanaActualizacion, `$db"."_actividades`.S2 AS contrato, `$db"."_actividades`.paqueteS2 AS paqueteContratacion, `general_dias_procesos_contratacion`.diasElaboracionPliegos AS diasElaboracionPliegos, `general_dias_procesos_contratacion`.diasIngresoLicify AS diasIngresoLicify, `general_dias_procesos_contratacion`.diasEntregaPliegos AS diasEntregaPliegos, `general_dias_procesos_contratacion`.diasReciboPropuestas AS diasReciboPropuestas, `general_dias_procesos_contratacion`.diasCuadrosComparativos AS diasCuadrosComparativos, `general_dias_procesos_contratacion`.diasLegalizacionContrato AS diasLegalizacionContrato, `general_dias_procesos_contratacion`.diasFabricacion AS diasFabricacion, `general_dias_procesos_contratacion`.diasInsumosObra AS diasInsumosObra FROM `$db"."_actividades` INNER JOIN `general_dias_procesos_contratacion` ON `$db"."_actividades`.paqueteS2 = `general_dias_procesos_contratacion`.paqueteContratacion  WHERE fechaInicio IS NOT NULL AND semanaActualizacion = $semana AND paqueteS2 IS NOT NULL AND paqueteS2 != '' AND `general_dias_procesos_contratacion`.tipoPaquete='Suministro' UNION SELECT `$db"."_actividades`.actividad AS actividad, `$db"."_actividades`.descripcionActividad AS descripcionActividad, `$db"."_actividades`.fechaInicio AS fechaInicio, `$db"."_actividades`.tipoContrato AS tipoContrato, `$db"."_actividades`.semanaActualizacion AS semanaActualizacion, `$db"."_actividades`.S3 AS contrato, `$db"."_actividades`.paqueteS3 AS paqueteContratacion, `general_dias_procesos_contratacion`.diasElaboracionPliegos AS diasElaboracionPliegos, `general_dias_procesos_contratacion`.diasIngresoLicify AS diasIngresoLicify, `general_dias_procesos_contratacion`.diasEntregaPliegos AS diasEntregaPliegos, `general_dias_procesos_contratacion`.diasReciboPropuestas AS diasReciboPropuestas, `general_dias_procesos_contratacion`.diasCuadrosComparativos AS diasCuadrosComparativos, `general_dias_procesos_contratacion`.diasLegalizacionContrato AS diasLegalizacionContrato, `general_dias_procesos_contratacion`.diasFabricacion AS diasFabricacion, `general_dias_procesos_contratacion`.diasInsumosObra AS diasInsumosObra FROM `$db"."_actividades` INNER JOIN `general_dias_procesos_contratacion` ON `$db"."_actividades`.paqueteS3 = `general_dias_procesos_contratacion`.paqueteContratacion  WHERE fechaInicio IS NOT NULL AND semanaActualizacion = $semana AND paqueteS3 IS NOT NULL AND paqueteS3 != '' AND `general_dias_procesos_contratacion`.tipoPaquete='Suministro' UNION SELECT `$db"."_actividades`.actividad AS actividad, `$db"."_actividades`.descripcionActividad AS descripcionActividad, `$db"."_actividades`.fechaInicio AS fechaInicio, `$db"."_actividades`.tipoContrato AS tipoContrato, `$db"."_actividades`.semanaActualizacion AS semanaActualizacion, `$db"."_actividades`.S4 AS contrato, `$db"."_actividades`.paqueteS4 AS paqueteContratacion, `general_dias_procesos_contratacion`.diasElaboracionPliegos AS diasElaboracionPliegos, `general_dias_procesos_contratacion`.diasIngresoLicify AS diasIngresoLicify, `general_dias_procesos_contratacion`.diasEntregaPliegos AS diasEntregaPliegos, `general_dias_procesos_contratacion`.diasReciboPropuestas AS diasReciboPropuestas, `general_dias_procesos_contratacion`.diasCuadrosComparativos AS diasCuadrosComparativos, `general_dias_procesos_contratacion`.diasLegalizacionContrato AS diasLegalizacionContrato, `general_dias_procesos_contratacion`.diasFabricacion AS diasFabricacion, `general_dias_procesos_contratacion`.diasInsumosObra AS diasInsumosObra FROM `$db"."_actividades` INNER JOIN `general_dias_procesos_contratacion` ON `$db"."_actividades`.paqueteS4 = `general_dias_procesos_contratacion`.paqueteContratacion  WHERE fechaInicio IS NOT NULL AND semanaActualizacion = $semana AND paqueteS4 IS NOT NULL AND paqueteS4 != '' AND `general_dias_procesos_contratacion`.tipoPaquete='Suministro' UNION SELECT `$db"."_actividades`.actividad AS actividad, `$db"."_actividades`.descripcionActividad AS descripcionActividad, `$db"."_actividades`.fechaInicio AS fechaInicio, `$db"."_actividades`.tipoContrato AS tipoContrato, `$db"."_actividades`.semanaActualizacion AS semanaActualizacion, `$db"."_actividades`.S5 AS contrato, `$db"."_actividades`.paqueteS5 AS paqueteContratacion, `general_dias_procesos_contratacion`.diasElaboracionPliegos AS diasElaboracionPliegos, `general_dias_procesos_contratacion`.diasIngresoLicify AS diasIngresoLicify, `general_dias_procesos_contratacion`.diasEntregaPliegos AS diasEntregaPliegos, `general_dias_procesos_contratacion`.diasReciboPropuestas AS diasReciboPropuestas, `general_dias_procesos_contratacion`.diasCuadrosComparativos AS diasCuadrosComparativos, `general_dias_procesos_contratacion`.diasLegalizacionContrato AS diasLegalizacionContrato, `general_dias_procesos_contratacion`.diasFabricacion AS diasFabricacion, `general_dias_procesos_contratacion`.diasInsumosObra AS diasInsumosObra FROM `$db"."_actividades` INNER JOIN `general_dias_procesos_contratacion` ON `$db"."_actividades`.paqueteS5 = `general_dias_procesos_contratacion`.paqueteContratacion  WHERE fechaInicio IS NOT NULL AND semanaActualizacion = $semana AND paqueteS5 IS NOT NULL AND paqueteS5 != '' AND `general_dias_procesos_contratacion`.tipoPaquete='Suministro') AS Tabla $contratosVigentesS GROUP BY paqueteContratacion ORDER BY tipoPaquete DESC, fechaInicio ASC";

		return $query1;
		//echo $query1;
	}

	function crearSubcontratosDuplicados($db, $semana, $conexion){
		$query = "SELECT * FROM $db"."_pdc WHERE semana=$semana AND titulo=0 AND numeroSubcontratos > 1";
		$resultado = mysqli_query($conexion, $query);
		if(!$resultado){
			die(mysqli_error($conexion));
			$script = "Esto era";
		}else{
			$queryDuplicarFinal = "INSERT INTO $db"."_pdc (semana, titulo, tipoPaquete, paqueteContratacion, contratos, subcontratoPaquete, estado, fechaElaboracionPliegos, diasIngresoLicify, fechaEntregaPliegos, diasEntregaPliegos, fechaReciboPropuestas, diasReciboPropuestas, fechaCuadrosComparativos, diasCuadrosComparativos, fechaLegalizacionContrato, diasLegalizacionContrato, fechaFabricacion, diasFabricacion, fechaInsumosObra, diasInsumosObra, fechaInicio) SELECT * FROM (";
			$queryDuplicar = "";
			while($data=mysqli_fetch_assoc($resultado)){
				$consecutivo = $data["consecutivo"];
				$numeroSubcontratos = $data["numeroSubcontratos"];
				$paqueteContratacion = $data["paqueteContratacion"];
				$queryInfoSubcontratosPaquete = "SELECT COUNT(*), MAX(subcontratoPaquete) FROM $db"."_pdc WHERE semana=$semana AND titulo=0 AND paqueteContratacion = '$paqueteContratacion'";
				$resultadoInfoSubcontratosPaquete = mysqli_query($conexion, $queryInfoSubcontratosPaquete);
				$dataInfoSubcontratosPaquete=mysqli_fetch_assoc($resultadoInfoSubcontratosPaquete);
				$conteoSubcontratosPaquete=$dataInfoSubcontratosPaquete["COUNT(*)"];
				$maxSubcontratosPaquete=$dataInfoSubcontratosPaquete["MAX(subcontratoPaquete)"];
				//echo "$conteoSubcontratosPaquete, $maxSubcontratosPaquetes" . "<br>";
				if($conteoSubcontratosPaquete < $numeroSubcontratos){
					for($i=($conteoSubcontratosPaquete+1); $i<(1+$numeroSubcontratos); $i++){
						$queryDuplicar .= "SELECT semana, titulo, tipoPaquete, paqueteContratacion, contratos, ($maxSubcontratosPaquete + 1), estado, fechaElaboracionPliegos, diasIngresoLicify, fechaEntregaPliegos, diasEntregaPliegos, fechaReciboPropuestas, diasReciboPropuestas, fechaCuadrosComparativos, diasCuadrosComparativos, fechaLegalizacionContrato, diasLegalizacionContrato, fechaFabricacion, diasFabricacion, fechaInsumosObra, diasInsumosObra, fechaInicio FROM $db"."_pdc WHERE consecutivo = $consecutivo UNION ";
						$maxSubcontratosPaquete++;
					}
				}
			}
			if(empty($queryDuplicar)){
				return "";
			}else{
				$queryDuplicar = substr($queryDuplicar,0,-7);
				$queryDuplicarFinal .= $queryDuplicar . ") AS tabla";
				return $queryDuplicarFinal;
			}
		}
	}

	function generarEstadoProceso($db, $semana, $conexion){
		$script = "";

		$query = "SELECT * FROM $db"."_pdc WHERE semana=$semana AND titulo=0 AND fechaInicio IS NOT NULL";

		$resultado = mysqli_query($conexion, $query);

		$queryFechaActual = "SELECT Fecha_Inicio_Sem FROM $db"."_semanas_activas WHERE Semana=$semana";
		$resultadoFechaActual = mysqli_query($conexion, $queryFechaActual);
		$dataFechaActual=mysqli_fetch_assoc($resultadoFechaActual);
		$fechaActual = date('Y-m-d', strtotime($dataFechaActual["Fecha_Inicio_Sem"]));

		if(!$resultado){
			die(mysqli_error($conexion));
			$script = "Esto era";
		}else{
			while($data=mysqli_fetch_assoc($resultado)){
				$fechaElaboracionPliegosInicial = date('Y-m-d', strtotime($data["fechaElaboracionPliegos"]));

				$data["fechaElaboracionPliegos"] = date('Y-m-d', strtotime($data["fechaInicio"] . "- " . ($data["diasElaboracionPliegos"] + $data["diasIngresoLicify"] + $data["diasEntregaPliegos"] + $data["diasReciboPropuestas"] + $data["diasCuadrosComparativos"] + $data["diasLegalizacionContrato"] + $data["diasFabricacion"] + $data["diasInsumosObra"]) . " days"));

				$data["fechaIngresoLicify"] = date('Y-m-d', strtotime($data["fechaInicio"] . "- " . ($data["diasIngresoLicify"] + $data["diasEntregaPliegos"] + $data["diasReciboPropuestas"] + $data["diasCuadrosComparativos"] + $data["diasLegalizacionContrato"] + $data["diasFabricacion"] + $data["diasInsumosObra"]) . " days"));

				$data["fechaEntregaPliegos"] = date('Y-m-d', strtotime($data["fechaInicio"] . "- " . ($data["diasEntregaPliegos"] + $data["diasReciboPropuestas"] + $data["diasCuadrosComparativos"] + $data["diasLegalizacionContrato"] + $data["diasFabricacion"] + $data["diasInsumosObra"]) . " days"));

				$data["fechaReciboPropuestas"] = date('Y-m-d', strtotime($data["fechaInicio"] . "- " . ($data["diasReciboPropuestas"] + $data["diasCuadrosComparativos"] + $data["diasLegalizacionContrato"] + $data["diasFabricacion"] + $data["diasInsumosObra"]) . " days"));

				$data["fechaCuadrosComparativos"] = date('Y-m-d', strtotime($data["fechaInicio"] . "- " . ($data["diasCuadrosComparativos"] + $data["diasLegalizacionContrato"] + $data["diasFabricacion"] + $data["diasInsumosObra"]) . " days"));

				$data["fechaLegalizacionContrato"] = date('Y-m-d', strtotime($data["fechaInicio"] . "- " . ($data["diasLegalizacionContrato"] + $data["diasFabricacion"] + $data["diasInsumosObra"]) . " days"));

				$data["fechaFabricacion"] = date('Y-m-d', strtotime($data["fechaInicio"] . "- " . ($data["diasFabricacion"] + $data["diasInsumosObra"]) . " days"));

				$data["fechaInsumosObra"] = date('Y-m-d', strtotime($data["fechaInicio"] . "- " . ($data["diasInsumosObra"]) . " days"));

				if($fechaElaboracionPliegosInicial != $data["fechaElaboracionPliegos"]){
					$scriptActualizarFechas = "`fechaElaboracionPliegos`='".$data["fechaElaboracionPliegos"]."', `fechaIngresoLicify`='".$data["fechaIngresoLicify"]."', `fechaEntregaPliegos`='".$data["fechaEntregaPliegos"]."', `fechaReciboPropuestas`='".$data["fechaReciboPropuestas"]."', `fechaCuadrosComparativos`='".$data["fechaCuadrosComparativos"]."', `fechaLegalizacionContrato`='".$data["fechaLegalizacionContrato"]."', `fechaFabricacion`='".$data["fechaFabricacion"]."', `fechaInsumosObra`='".$data["fechaInsumosObra"]."', ";
				}else{
					$scriptActualizarFechas = "";
				}

				$posicion = -1;
				$deberiaHoy = -1;
				$fechaEvaluar = "";
				$diagnostico = "";

				$pasos = array(
					array($data["fechaRealElaboracionPliegos"], $data["fechaElaboracionPliegos"], "Elaborando pliegos del contrato"),
					array($data["fechaRealIngresoLicify"], $data["fechaIngresoLicify"], "Ingresando el contrato a Licify"),
					array($data["fechaRealEntregaPliegos"], $data["fechaEntregaPliegos"], "Entregando pliegos a los proveedores invitados"),
					array($data["fechaRealReciboPropuestas"], $data["fechaReciboPropuestas"], "Recibiendo propuestas de los proveedores invitados"),
					array($data["fechaRealCuadrosComparativos"], $data["fechaCuadrosComparativos"], "Elaborando cuadros comparativos, análisis y adjudicación del contrato"),
					array($data["fechaRealLegalizacionContrato"], $data["fechaLegalizacionContrato"], "En proceso de legalización del contrato"),
					array($data["fechaRealFabricacion"], $data["fechaFabricacion"], "En periodo de fabricación, producción, importaciones, transportes, movilización, etc"),
					array($data["fechaRealInsumosObra"], $data["fechaInsumosObra"], "En proceso de llegada de recursos, insumos y personal a la obra"),
					array($data["fechaRealInicio"], $data["fechaInicio"], "Proceso de contratación finalizado y actividades del contrato iniciadas")
				);

				for ($i = 0; $i < 9; $i++) {
					if ($pasos[$i][0] != "") {
						$posicion = $i;
					}
					$fechaEvaluar = $pasos[$i][1];
					//echo "<li>$fechaEvaluar, $fechaActual, " . ($fechaEvaluar <= $fechaActual);
					if ($fechaEvaluar <= $fechaActual) {
						$deberiaHoy = $i;
					}
				}

				if ($posicion == -1) {
			    $estadoProceso = "Proceso de contratación no iniciado";
			    if ($deberiaHoy == -1) {
			      $deberiaProceso = "";
			    } else {
			      $deberiaProceso = $pasos[$deberiaHoy][2];
			    }
			  } else {
			    $estadoProceso = $pasos[$posicion][2];
			    if ($deberiaHoy == -1) {
			      $deberiaProceso = "";
			    } else {
			      $deberiaProceso = $pasos[$deberiaHoy][2];
			    }
			  }
				//echo "<li>Paquete: ".$data["paqueteContratacion"].", Estado: $posicion, Debería: $deberiaHoy<br>";

				if ($posicion >= $deberiaHoy) {
			    $diagnostico = "A tiempo";
			    // if (($posicion == -1 && $deberiaHoy == -1)) {
			    //   $diagnostico = "A tiempo";
			    // } else if ($pasos[$posicion][0] <= $pasos[$posicion][1]) {
			    //   $diagnostico = "A tiempo";
			    // } else {
			    //   $diagnostico = "Atrasado!!";
					// 	echo "<li> $diagnostico";
			    // }
			  } else {
			    $diagnostico = "Atrasado!!";
			  }


				if ($estadoProceso == $pasos[8][2]) {
			    if ($pasos[8][0] > $pasos[8][1]) {
			      $diagnostico = "Terminado con retrasos";
			      $estadoProceso = "Terminado con retrasos";
			    } else {
			      $diagnostico = "Terminado a tiempo";
			      $estadoProceso = "Terminado a tiempo";
			    }
			  } else {
			    $estadoProceso = "$diagnostico; $estadoProceso";
			  }

				//echo "<li>Paquete: ".$data["paqueteContratacion"].", Estado: $estadoProceso, Debería: $deberiaProceso, Diagnóstico: $diagnostico<br><br>";

				$script .= "UPDATE $db"."_pdc SET $scriptActualizarFechas estado = '$estadoProceso' WHERE semana = $semana AND consecutivo = ".$data["consecutivo"]."; ";
			}
		}
		return $script;
	}
?>

