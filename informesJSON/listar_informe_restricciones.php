<?php
	require ("../conexion.php");

  //$db=$_GET['db'];
	$query = "SELECT COUNT(*) FROM general_informe_restricciones_consolidado;";
	$resultado = mysqli_query($conexion, $query);
    $data=mysqli_fetch_assoc($resultado);
    $conteo=$data["COUNT(*)"];
    if ($conteo==0){
        // $arreglo1["data"][]=array("Id" => "","subcontratista" => "","correo_contacto" => "","NIT" => "","alcance" => "","tipo_proveedor" => "");
        // echo json_encode($arreglo1);
    }else{
        $query1 = "SELECT * FROM general_informe_restricciones_consolidado;";
        $resultado1 = mysqli_query($conexion, $query1);

        if(!$resultado1){
            die("Error");
        } else{
            while($data=mysqli_fetch_assoc($resultado1)){
							$data["Fecha_Inicio"] = date("Y-m-d", strtotime($data["Fecha_Inicio"]));
							$data["Fecha_Inicio_Sem"] = date("Y-m-d", strtotime($data["Fecha_Inicio_Sem"]));
							$data["Fecha_Fin"] = date("Y-m-d", strtotime($data["Fecha_Fin"]));
							$data["Fecha_Fin_Sem"] = date("Y-m-d", strtotime($data["Fecha_Fin_Sem"]));

							$data["Semana_Inicio_Actividad"] = date("Y-m-d", strtotime($data["Fecha_Inicio_Sem"] . " + " . $data["Semanas_Inicio"] . " weeks"));

              $arreglo["data"][]=array_map("utf8_encode", $data);
            }
            $json_codificado = json_encode($arreglo, JSON_UNESCAPED_UNICODE);
            echo utf8_decode($json_codificado);
        }
        mysqli_free_result($resultado1);
    }
    mysqli_close($conexion);
?>
