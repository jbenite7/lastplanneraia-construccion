<?php
	require ("../conexion.php");

  //$db=$_GET['db'];
	$query = "SELECT COUNT(*) FROM general_curvas;";
	$resultado = mysqli_query($conexion, $query);
    $data=mysqli_fetch_assoc($resultado);
    $conteo=$data["COUNT(*)"];
    if ($conteo==0){
        // $arreglo1["data"][]=array("Id" => "","subcontratista" => "","correo_contacto" => "","NIT" => "","alcance" => "","tipo_proveedor" => "");
        // echo json_encode($arreglo1);
    }else{
        $query1 = "SELECT * FROM general_curvas";
        $resultado1 = mysqli_query($conexion, $query1);

        if(!$resultado1){
            die("Error");
        } else{
					$porcentajeCompletadoTeoricoAnterior=0;
					$ProyectoAnterior = null;
					// $anterior_100 = 0;
            while($data=mysqli_fetch_assoc($resultado1)){
							if(!$ProyectoAnterior){
							}else{
								if($data["Proyecto"] != $ProyectoAnterior){
									$porcentajeCompletadoTeoricoAnterior=0;
								}
							}

							$porcentajeCompletadoTeorico = $data["porcentajeCompletadoTeorico"];

							if($porcentajeCompletadoTeorico == 1 && $anterior_100 == 1){
								$data["porcentajeCompletadoTeorico"] = "NULL";
							}

							if($porcentajeCompletadoTeorico >= 1){
								$anterior_100 = 1;
							}else{
								$anterior_100 = 0;
							}

							$ProyectoAnterior = $data["Proyecto"];

							$data["Fecha_Inicio_Sem"] = date("Y-m-d", strtotime($data["Fecha_Inicio_Sem"]));
							$data["Fecha_Fin_Sem"] = date("Y-m-d", strtotime($data["Fecha_Fin_Sem"]));

							$data["diferenciaPorcentajeCompletadoTeorico"] = ($porcentajeCompletadoTeorico - $porcentajeCompletadoTeoricoAnterior);
							$porcentajeCompletadoTeoricoAnterior = $porcentajeCompletadoTeorico;


              $arreglo["data"][]=array_map("utf8_encode", $data);
            }
            $json_codificado = json_encode($arreglo, JSON_UNESCAPED_UNICODE);
            echo utf8_decode($json_codificado);
        }
        mysqli_free_result($resultado1);
    }
    mysqli_close($conexion);
?>
