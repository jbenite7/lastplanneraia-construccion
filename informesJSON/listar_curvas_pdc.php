<?php
	require ("../conexion.php");

  //$db=$_GET['db'];
	$query = "SELECT COUNT(*) FROM general_curvas_pdc;";
	$resultado = mysqli_query($conexion, $query);
    $data=mysqli_fetch_assoc($resultado);
    $conteo=$data["COUNT(*)"];
    if ($conteo==0){
        // $arreglo1["data"][]=array("Id" => "","subcontratista" => "","correo_contacto" => "","NIT" => "","alcance" => "","tipo_proveedor" => "");
        // echo json_encode($arreglo1);
    }else{
        $query1 = "SELECT tablaPDC.`id`, tablaPDC.`Proyecto`, tablaPDC.`semana`, tablaPDC.`Condicion`, tablaPDC.`Fecha_inicio_Sem`, tablaPDC.`Fecha_Fin_Sem`, tablaPDC.`diasCompletadosReal`, tablaPDC.`diasCompletadosTeorico`, tablaPDC.`diasTotales`, tablaPDC.`porcentajeCompletadoReal`, tablaPDC.`porcentajeCompletadoTeorico`, tablaGeneral.`diasCompletadosRealGeneral`, tablaGeneral.`diasCompletadosTeoricoGeneral`, tablaGeneral.`diasTotalesGeneral`, tablaGeneral.`porcentajeCompletadoRealGeneral`, tablaGeneral.`porcentajeCompletadoTeoricoGeneral` FROM (SELECT `id`, `Proyecto`, `semana`, CONCAT(`Proyecto`, '-', `semana`) AS Condicion, `Fecha_Inicio_Sem`, `Fecha_Fin_Sem`, `diasCompletadosReal`, `diasCompletadosTeorico`, `diasTotales`, `porcentajeCompletadoReal`, `porcentajeCompletadoTeorico` FROM general_curvas_pdc) AS tablaPDC LEFT JOIN (SELECT `id` AS idGeneral, `Proyecto` AS ProyectoGeneral, `semana` AS semanaGeneral, CONCAT(`Proyecto`, '-', `semana`) AS CondicionGeneral, `Fecha_Inicio_Sem` AS Fecha_Inicio_SemGeneral, `Fecha_Fin_Sem` AS Fecha_Fin_SemGeneral, `diasCompletadosReal` AS diasCompletadosRealGeneral, `diasCompletadosTeorico` AS diasCompletadosTeoricoGeneral, `diasTotales` AS diasTotalesGeneral, `porcentajeCompletadoReal` AS porcentajeCompletadoRealGeneral, `porcentajeCompletadoTeorico` AS porcentajeCompletadoTeoricoGeneral FROM general_curvas) AS tablaGeneral ON tablaPDC.`Condicion` = tablaGeneral.`CondicionGeneral`";
        $resultado1 = mysqli_query($conexion, $query1);

        if(!$resultado1){
            die("Error");
        } else{
					$porcentajeCompletadoTeoricoAnterior=0;
					$porcentajeCompletadoTeoricoGeneralAnterior=0;
					$ProyectoAnterior = null;
					$anterior_100 = 0;
					$anterior_100_General = 0;
            while($data=mysqli_fetch_assoc($resultado1)){
							if(!$ProyectoAnterior){
							}else{
								if($data["Proyecto"] != $ProyectoAnterior){
									$porcentajeCompletadoTeoricoAnterior=0;
									$porcentajeCompletadoTeoricoGeneralAnterior=0;
								}
							}

							$porcentajeCompletadoTeorico = $data["porcentajeCompletadoTeorico"];
							$porcentajeCompletadoTeoricoGeneral = $data["porcentajeCompletadoTeoricoGeneral"];

							if($porcentajeCompletadoTeorico == 1 && $anterior_100 == 1){
								$data["porcentajeCompletadoTeorico"] = "NULL";
							}

							if($porcentajeCompletadoTeorico >= 1){
								$anterior_100 = 1;
							}else{
								$anterior_100 = 0;
							}

							if($porcentajeCompletadoTeoricoGeneral == 1 && $anterior_100_General == 1){
								$data["porcentajeCompletadoTeoricoGeneral"] = "NULL";
							}

							if($porcentajeCompletadoTeoricoGeneral >= 1){
								$anterior_100_General = 1;
							}else{
								$anterior_100_General = 0;
							}

							$ProyectoAnterior = $data["Proyecto"];

							$data["Fecha_Inicio_Sem"] = date("Y-m-d", strtotime($data["Fecha_Inicio_Sem"]));
							$data["Fecha_Fin_Sem"] = date("Y-m-d", strtotime($data["Fecha_Fin_Sem"]));

							$data["diferenciaPorcentajeCompletadoTeorico"] = ($porcentajeCompletadoTeorico - $porcentajeCompletadoTeoricoAnterior);
							$porcentajeCompletadoTeoricoAnterior = $porcentajeCompletadoTeorico;

							$data["diferenciaPorcentajeCompletadoTeoricoGeneral"] = ($porcentajeCompletadoTeoricoGeneral - $porcentajeCompletadoTeoricoGeneralAnterior);
							$porcentajeCompletadoTeoricoGeneralAnterior = $porcentajeCompletadoTeoricoGeneral;


              $arreglo["data"][]=array_map("utf8_encode", $data);
            }
            $json_codificado = json_encode($arreglo, JSON_UNESCAPED_UNICODE);
            echo utf8_decode($json_codificado);
        }
        mysqli_free_result($resultado1);
    }
    mysqli_close($conexion);
?>
