<?php
	require ("../conexion.php");

  $db=$_GET['db'];
	$semana=$_GET['semana'];
	$query = "SELECT COUNT(*) FROM $db"."_actividades WHERE semanaActualizacion=$semana AND tipoContrato IS NOT NULL AND fechaInicio IS NOT NULL;";
	$resultado = mysqli_query($conexion, $query);
    $data=mysqli_fetch_assoc($resultado);
    $conteo=$data["COUNT(*)"];
    if ($conteo==0){
        $arreglo1["data"][]=array("Id" => "","codigo" => "","actividad" => "","descripcionActividad" => "","actividadInicio" => "","nombreActividadInicio" => "","fechaInicio" => "","tipoContrato" => "","semanaActualizacion" => "","SI1" => "","paqueteSI1" => "","SI2" => "","paqueteSI2" => "","SI3" => "","paqueteSI3" => "","SI4" => "","paqueteSI4" => "","SI5" => "","paqueteSI5" => "","S1" => "","paqueteS1" => "","S2" => "","paqueteS2" => "","S3" => "","paqueteS3" => "","S4" => "","paqueteS4" => "","S5" => "","paqueteS5" => "","MO1" => "","paqueteMO1" => "","MO2" => "","paqueteMO2" => "","MO3" => "","paqueteMO3" => "","MO4" => "","paqueteMO4" => "","MO5" => "","paqueteMO5" => "","contratosAsociados" => "");
        echo json_encode($arreglo1);
    }else{
        $query1 = "SELECT $db"."_actividades.`Id`, $db"."_actividades.`codigo`, $db"."_actividades.`actividad`, $db"."_actividades.`descripcionActividad`, $db"."_actividades.`actividadInicio`, CONCAT($db"."_programa_consolidado.`Actividad`, ' - (Inicia en: ', $db"."_programa_consolidado.`Fecha_Inicio`, ')') AS nombreActividadInicio, $db"."_actividades.`fechaInicio`, $db"."_actividades.`tipoContrato`, $db"."_actividades.`semanaActualizacion`, $db"."_actividades.`SI1`, $db"."_actividades.`paqueteSI1`, $db"."_actividades.`SI2`, $db"."_actividades.`paqueteSI2`, $db"."_actividades.`SI3`, $db"."_actividades.`paqueteSI3`, $db"."_actividades.`SI4`, $db"."_actividades.`paqueteSI4`, $db"."_actividades.`SI5`, $db"."_actividades.`paqueteSI5`, $db"."_actividades.`S1`, $db"."_actividades.`paqueteS1`, $db"."_actividades.`S2`, $db"."_actividades.`paqueteS2`, $db"."_actividades.`S3`, $db"."_actividades.`paqueteS3`, $db"."_actividades.`S4`, $db"."_actividades.`paqueteS4`, $db"."_actividades.`S5`, $db"."_actividades.`paqueteS5`, $db"."_actividades.`MO1`, $db"."_actividades.`paqueteMO1`, $db"."_actividades.`MO2`, $db"."_actividades.`paqueteMO2`, $db"."_actividades.`MO3`, $db"."_actividades.`paqueteMO3`, $db"."_actividades.`MO4`, $db"."_actividades.`paqueteMO4`, $db"."_actividades.`MO5`, $db"."_actividades.`paqueteMO5` FROM $db"."_actividades LEFT JOIN $db"."_programa_consolidado  ON $db"."_programa_consolidado.`Consecutivo_en_Programa` = $db"."_actividades.`actividadInicio` AND $db"."_programa_consolidado.`Semana` = $db"."_actividades.`semanaActualizacion` WHERE $db"."_actividades.semanaActualizacion=$semana AND $db"."_actividades.tipoContrato IS NOT NULL AND $db"."_actividades.fechaInicio IS NOT NULL ORDER BY $db"."_actividades.`Id`";
				//echo $query1;
        $resultado1 = mysqli_query($conexion, $query1);

        if(!$resultado1){
            die(mysqli_error($conexion));
        } else{
            while($data=mysqli_fetch_assoc($resultado1)){
								$contratosAsociadosSI="";
								if($data["paqueteSI1"]=="" || $data["paqueteSI1"]==NULL){
								}else{
									$contratosAsociadosSI .= $data["paqueteSI1"] . ", ";
								}
								if($data["paqueteSI2"]=="" || $data["paqueteSI2"]==NULL){
								}else{
									$contratosAsociadosSI .= $data["paqueteSI2"] . ", ";
								}
								if($data["paqueteSI3"]=="" || $data["paqueteSI3"]==NULL){
								}else{
									$contratosAsociadosSI .= $data["paqueteSI3"] . ", ";
								}
								if($data["paqueteSI4"]=="" || $data["paqueteSI4"]==NULL){
								}else{
									$contratosAsociadosSI .= $data["paqueteSI4"] . ", ";
								}
								if($data["paqueteSI5"]=="" || $data["paqueteSI5"]==NULL){
								}else{
									$contratosAsociadosSI .= $data["paqueteSI5"] . ", ";
								}
								if($contratosAsociadosSI !=""){
									$contratosAsociadosSI = substr($contratosAsociadosSI, 0, -2);
									$contratosAsociadosSI = str_replace(';', ", ", $contratosAsociadosSI);
									$contratosAsociadosSI = "<b style='color: red'>- Suministro e Instalación: </b>" . $contratosAsociadosSI . ".<br>";
								}

								$contratosAsociadosS = "";
								if($data["paqueteS1"]=="" || $data["paqueteS1"]==NULL){
								}else{
									$contratosAsociadosS .= $data["paqueteS1"] . ", ";
								}
								if($data["paqueteS2"]=="" || $data["paqueteS2"]==NULL){
								}else{
									$contratosAsociadosS .= $data["paqueteS2"] . ", ";
								}
								if($data["paqueteS3"]=="" || $data["paqueteS3"]==NULL){
								}else{
									$contratosAsociadosS .= $data["paqueteS3"] . ", ";
								}
								if($data["paqueteS4"]=="" || $data["paqueteS4"]==NULL){
								}else{
									$contratosAsociadosS .= $data["paqueteS4"] . ", ";
								}
								if($data["paqueteS5"]=="" || $data["paqueteS5"]==NULL){
								}else{
									$contratosAsociadosS .= $data["paqueteS5"] . ", ";
								}
								if($contratosAsociadosS !=""){
									$contratosAsociadosS = substr($contratosAsociadosS, 0, -2);
									$contratosAsociadosSI = str_replace(';', ", ", $contratosAsociadosSI);
									$contratosAsociadosS = "<b style='color: blue'>- Suministro: </b>" . $contratosAsociadosS . ".<br> ";
								}

								$contratosAsociadosMO="";
								if($data["paqueteMO1"]=="" || $data["paqueteMO1"]==NULL){
								}else{
									$contratosAsociadosMO .= $data["paqueteMO1"] . ", ";
								}
								if($data["paqueteMO2"]=="" || $data["paqueteMO2"]==NULL){
								}else{
									$contratosAsociadosMO .= $data["paqueteMO2"] . ", ";
								}
								if($data["paqueteMO3"]=="" || $data["paqueteMO3"]==NULL){
								}else{
									$contratosAsociadosMO .= $data["paqueteMO3"] . ", ";
								}
								if($data["paqueteMO4"]=="" || $data["paqueteMO4"]==NULL){
								}else{
									$contratosAsociadosMO .= $data["paqueteMO4"] . ", ";
								}
								if($data["paqueteMO5"]=="" || $data["paqueteMO5"]==NULL){
								}else{
									$contratosAsociadosMO .= $data["paqueteMO5"] . ", ";
								}
								if($contratosAsociadosMO !=""){
									$contratosAsociadosMO = substr($contratosAsociadosMO, 0, -2);
									$contratosAsociadosSI = str_replace(';', ", ", $contratosAsociadosSI);
									$contratosAsociadosMO = "<b style='color: green'>- Mano de Obra: </b>" . $contratosAsociadosMO .".<br>";
								}
								$data["contratosAsociados"]= $contratosAsociadosSI . $contratosAsociadosMO . $contratosAsociadosS;
                $arreglo["data"][]=array_map("utf8_encode", $data);
            }
            $json_codificado = json_encode($arreglo, JSON_UNESCAPED_UNICODE);
            echo utf8_decode($json_codificado);
        }
        mysqli_free_result($resultado1);
    }
    mysqli_close($conexion);
?>
