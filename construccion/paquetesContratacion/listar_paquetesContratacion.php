<?php
	require ("../conexion.php");

  $db=$_GET['db'];
	$Semana=$_GET['Semana'];

	// $db="clinicaVeterinaria";
	// $Semana=3;

	$query = "SELECT ((SELECT COUNT(*) FROM $db"."_actividades WHERE paqueteSI1 IS NOT NULL AND paqueteSI1 != '') + (SELECT COUNT(*) FROM $db"."_actividades WHERE paqueteSI2 IS NOT NULL AND paqueteSI2 != '') + (SELECT COUNT(*) FROM $db"."_actividades WHERE paqueteSI3 IS NOT NULL AND paqueteSI3 != '') + (SELECT COUNT(*) FROM $db"."_actividades WHERE paqueteSI4 IS NOT NULL AND paqueteSI4 != '') + (SELECT COUNT(*) FROM $db"."_actividades WHERE paqueteSI5 IS NOT NULL AND paqueteSI5 != '') + (SELECT COUNT(*) FROM $db"."_actividades WHERE paqueteMO1 IS NOT NULL AND paqueteMO1 != '') + (SELECT COUNT(*) FROM $db"."_actividades WHERE paqueteMO2 IS NOT NULL AND paqueteMO2 != '') + (SELECT COUNT(*) FROM $db"."_actividades WHERE paqueteMO3 IS NOT NULL AND paqueteMO3 != '') + (SELECT COUNT(*) FROM $db"."_actividades WHERE paqueteMO4 IS NOT NULL AND paqueteMO4 != '') + (SELECT COUNT(*) FROM $db"."_actividades WHERE paqueteMO5 IS NOT NULL AND paqueteMO5 != '') + (SELECT COUNT(*) FROM $db"."_actividades WHERE paqueteS1 IS NOT NULL AND paqueteS1 != '') + (SELECT COUNT(*) FROM $db"."_actividades WHERE paqueteS2 IS NOT NULL AND paqueteS2 != '') + (SELECT COUNT(*) FROM $db"."_actividades WHERE paqueteS3 IS NOT NULL AND paqueteS3 != '') + (SELECT COUNT(*) FROM $db"."_actividades WHERE paqueteS4 IS NOT NULL AND paqueteS4 != '') + (SELECT COUNT(*) FROM $db"."_actividades WHERE paqueteS5 IS NOT NULL AND paqueteS5 != '')) AS conteo";
	//echo $query;
	$resultado = mysqli_query($conexion, $query);
  $data=mysqli_fetch_assoc($resultado);
  $conteo=$data["conteo"];
	//echo $conteo;
  if ($conteo==0){
      $arreglo1["data"][]=array("tipoPaquete" => "","paqueteContratacion" => "","contratos" => "","fechaInicio" => "");
      echo json_encode($arreglo1);
  }else{
			$query1 =" SELECT 'Suministro e Instalación' AS tipoPaquete, NULL AS paqueteContratacion, NULL AS contratos, NULL AS fechaInicio, 1 AS titulo";

			$query1 .= " UNION SELECT 'Suministro e Instalación' AS tipoPaquete, paqueteContratacion, GROUP_CONCAT(contrato SEPARATOR '; ') AS contratos, MIN(fechaInicio) AS fechaInicio, 0 AS titulo FROM (SELECT actividad, descripcionActividad, fechaInicio, tipoContrato, semanaActualizacion, SI1 AS contrato, paqueteSI1 AS paqueteContratacion FROM $db"."_actividades WHERE paqueteSI1 IS NOT NULL AND paqueteSI1 != '' UNION SELECT actividad, descripcionActividad, fechaInicio, tipoContrato, semanaActualizacion, SI2 AS contrato, paqueteSI2 AS paqueteContratacion FROM $db"."_actividades WHERE paqueteSI2 IS NOT NULL AND paqueteSI2 != '' UNION SELECT actividad, descripcionActividad, fechaInicio, tipoContrato, semanaActualizacion, SI3 AS contrato, paqueteSI3 AS paqueteContratacion FROM $db"."_actividades WHERE paqueteSI3 IS NOT NULL AND paqueteSI3 != '' UNION SELECT actividad, descripcionActividad, fechaInicio, tipoContrato, semanaActualizacion, SI4 AS contrato, paqueteSI4 AS paqueteContratacion FROM $db"."_actividades WHERE paqueteSI4 IS NOT NULL AND paqueteSI4 != '' UNION SELECT actividad, descripcionActividad, fechaInicio, tipoContrato, semanaActualizacion, SI5 AS contrato, paqueteSI5 AS paqueteContratacion FROM $db"."_actividades WHERE paqueteSI5 IS NOT NULL AND paqueteSI5 != '') AS Tabla GROUP BY paqueteContratacion";

			$query1 .=" UNION SELECT 'Mano de Obra' AS tipoPaquete, NULL AS paqueteContratacion, NULL AS contratos, NULL AS fechaInicio, 1 AS titulo";

			$query1 .= " UNION SELECT 'Mano de Obra' AS tipoPaquete, paqueteContratacion, GROUP_CONCAT(contrato SEPARATOR '; ') AS contratos, MIN(fechaInicio) AS fechaInicio, 0 AS titulo FROM (SELECT actividad, descripcionActividad, fechaInicio, tipoContrato, semanaActualizacion, MO1 AS contrato, paqueteMO1 AS paqueteContratacion FROM $db"."_actividades WHERE paqueteMO1 IS NOT NULL AND paqueteMO1 != '' UNION SELECT actividad, descripcionActividad, fechaInicio, tipoContrato, semanaActualizacion, MO2 AS contrato, paqueteMO2 AS paqueteContratacion FROM $db"."_actividades WHERE paqueteMO2 IS NOT NULL AND paqueteMO2 != '' UNION SELECT actividad, descripcionActividad, fechaInicio, tipoContrato, semanaActualizacion, MO3 AS contrato, paqueteMO3 AS paqueteContratacion FROM $db"."_actividades WHERE paqueteMO3 IS NOT NULL AND paqueteMO3 != '' UNION SELECT actividad, descripcionActividad, fechaInicio, tipoContrato, semanaActualizacion, MO4 AS contrato, paqueteMO4 AS paqueteContratacion FROM $db"."_actividades WHERE paqueteMO4 IS NOT NULL AND paqueteMO4 != '' UNION SELECT actividad, descripcionActividad, fechaInicio, tipoContrato, semanaActualizacion, MO5 AS contrato, paqueteMO5 AS paqueteContratacion FROM $db"."_actividades WHERE paqueteMO5 IS NOT NULL AND paqueteMO5 != '') AS Tabla GROUP BY paqueteContratacion";

			$query1 .=" UNION SELECT 'Suministro' AS tipoPaquete, NULL AS paqueteContratacion, NULL AS contratos, NULL AS fechaInicio, 1 AS titulo";

			$query1 .= " UNION SELECT 'Suministro' AS tipoPaquete, paqueteContratacion, GROUP_CONCAT(contrato SEPARATOR '; ') AS contratos, MIN(fechaInicio) AS fechaInicio, 0 AS titulo FROM (SELECT actividad, descripcionActividad, fechaInicio, tipoContrato, semanaActualizacion, S1 AS contrato, paqueteS1 AS paqueteContratacion FROM $db"."_actividades WHERE paqueteS1 IS NOT NULL AND paqueteS1 != '' UNION SELECT actividad, descripcionActividad, fechaInicio, tipoContrato, semanaActualizacion, S2 AS contrato, paqueteS2 AS paqueteContratacion FROM $db"."_actividades WHERE paqueteS2 IS NOT NULL AND paqueteS2 != '' UNION SELECT actividad, descripcionActividad, fechaInicio, tipoContrato, semanaActualizacion, S3 AS contrato, paqueteS3 AS paqueteContratacion FROM $db"."_actividades WHERE paqueteS3 IS NOT NULL AND paqueteS3 != '' UNION SELECT actividad, descripcionActividad, fechaInicio, tipoContrato, semanaActualizacion, S4 AS contrato, paqueteS4 AS paqueteContratacion FROM $db"."_actividades WHERE paqueteS4 IS NOT NULL AND paqueteS4 != '' UNION SELECT actividad, descripcionActividad, fechaInicio, tipoContrato, semanaActualizacion, S5 AS contrato, paqueteS5 AS paqueteContratacion FROM $db"."_actividades WHERE paqueteS5 IS NOT NULL AND paqueteS5 != '') AS Tabla GROUP BY paqueteContratacion ORDER BY tipoPaquete DESC, fechaInicio ASC";
			 // echo $query1;
      $resultado1 = mysqli_query($conexion, $query1);

      if(!$resultado1){
          die(mysqli_error($conexion));
      } else{
          while($data=mysqli_fetch_assoc($resultado1)){
              $arreglo["data"][]=array_map("utf8_encode", $data);
          }
          $json_codificado = json_encode($arreglo, JSON_UNESCAPED_UNICODE);
          echo utf8_decode($json_codificado);
      }
      mysqli_free_result($resultado1);
  }
  mysqli_close($conexion);
?>
