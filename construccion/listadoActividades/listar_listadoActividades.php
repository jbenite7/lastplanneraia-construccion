<?php
	require ("../conexion.php");

  $db=/*"concejo_bogota_pc"*/$_GET['db'];
	$semana=/*18*/$_GET['semana'];
	$query = "SELECT COUNT(*) FROM $db"."_actividades WHERE semanaActualizacion=$semana;";
	$resultado = mysqli_query($conexion, $query);
    $data=mysqli_fetch_assoc($resultado);
    $conteo=$data["COUNT(*)"];
    if ($conteo==0){
        $arreglo1["data"][]=array("Id" => "","codigo" => "","actividad" => "","descripcionActividad" => "","actividadInicio" => "","nombreActividadInicio" => "","fechaInicio" => "","tipoContrato" => "","semanaActualizacion" => "");
        echo json_encode($arreglo1);
    }else{
        $query1 = "SELECT $db"."_actividades.`Id`, $db"."_actividades.`codigo`, $db"."_actividades.`actividad`, $db"."_actividades.`descripcionActividad`, $db"."_actividades.`actividadInicio`, CONCAT($db"."_programa_consolidado.`Id`, '. ', $db"."_programa_consolidado.`Actividad`, ' (Inicia en: ', $db"."_programa_consolidado.`Fecha_Inicio`, ')') AS nombreActividadInicio, $db"."_actividades.`fechaInicio`, $db"."_actividades.`tipoContrato`, $db"."_actividades.`semanaActualizacion` FROM $db"."_actividades LEFT JOIN $db"."_programa_consolidado  ON $db"."_programa_consolidado.`Actividad` = $db"."_actividades.`actividadInicio` AND $db"."_programa_consolidado.`Semana` = $db"."_actividades.`semanaActualizacion` WHERE semanaActualizacion=$semana ORDER BY $db"."_actividades.`Id`";
				//echo $query1;
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
