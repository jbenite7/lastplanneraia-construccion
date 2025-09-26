<?php
	require ("../conexion.php");
    $semana=$_GET["semana"];
    $db=$_GET['db'];
	//$semana=9;
	//$db='metrolineaDos';

    $query4="SELECT  COUNT(*) FROM $db"."_programacion_semanal WHERE Semana='$semana' AND (Activa='1' OR Activa='NA')";
    $resultado4= mysqli_query($conexion, $query4);
    $data=mysqli_fetch_assoc($resultado4);
    $conteo=$data["COUNT(*)"];
    if ($conteo==0){
        $arreglo1["data"][]=array("Consecutivo" => "","Id" => "","Actividad" => "","Fecha_Inicio" => "","Fecha_Fin" => "","Prog_Sin_Restricciones_100" => "","Descripcion" =>"","Ubicacion" => "","Ejecutado" => "","Ejecutado_Fin_Semana" => "","Sub_Contratista" => "","Responsable_AIA" => "","Empresa" => "","medir_productividad" => "", "Unidad" => "", "cantidad_ppto" => "","Compromiso" => "","Ejecutado_Real" => "","P_Completado" => "","PAC" => "","Activa" => "","Categoria_CNC" => "","CNC" => "","Observaciones_CNC" => "","Rendimientos" => "","codigo_actividad" => "", "proyeccionSemana" => "", "diasSemanaInicial" => "", "diasLleva" => "", "diasSemana" => "", "diasTotales" => "");
        echo json_encode($arreglo1);
    } else{
        $query_= "SELECT Fecha_Inicio_Sem, Fecha_Fin_Sem FROM $db"."_semanas_activas WHERE Semana=$semana";
        $resultado_ = mysqli_query($conexion, $query_);
        $data_=mysqli_fetch_assoc($resultado_);
        $Fecha_Inicio_Sem=date("Y-m-d",strtotime($data_["Fecha_Inicio_Sem"]));
        $Fecha_Fin_Sem=date("Y-m-d",strtotime($data_["Fecha_Fin_Sem"]));

        $query5 = "SELECT * FROM $db"."_programacion_semanal WHERE Semana='$semana' AND (Activa='1' OR Activa='NA') ORDER BY Consecutivo_En_Programa ASC, Activa ASC, Consecutivo ASC";
        $resultado5 = mysqli_query($conexion, $query5);
        if(!$resultado5){
            die("Error");
        } else{
            while($data=mysqli_fetch_assoc($resultado5)){
	            $Fecha_Inicio_Act=date("Y-m-d",strtotime($data['Fecha_Inicio']));
	            $Fecha_Fin_Act=date("Y-m-d",strtotime($data['Fecha_Fin']));
							$Ejecutado = $data['Ejecutado'];
                            $Consecutivo = $data['Consecutivo'];
                            
							
							$diasTotales = ((strtotime($Fecha_Fin_Act)-strtotime($Fecha_Inicio_Act))/86400)+1;
							$diasTranscurridos = ((strtotime($Fecha_Fin_Sem)-strtotime($Fecha_Inicio_Act))/86400)+1;
							/*echo "<li> Consecutivo: $Consecutivo";
							echo ", diasTranscurridos: " . $diasTranscurridos;
							echo ", diasTotales: " . $diasTotales;
							echo ", diferencia: " . $diasTotales - $diasTranscurridos;*/
							if($diasTranscurridos <= 0){
							    $proyeccionSemana = 0;
							}elseif($diasTranscurridos <= 7){
							    $proyeccionSemana = $diasTranscurridos/$diasTotales;
							}else{
							    if(($diasTotales - $diasTranscurridos) >= 7){
							        $proyeccionSemana = 7/$diasTotales;
							    }elseif($diasTotales - $diasTranscurridos < 0){
							        $proyeccionSemana = 1 - $Ejecutado;
							    }else{
							        $proyeccionSemana = 1 - (($diasTotales - $diasTranscurridos)/$diasTotales);
							    }
							}

							$data["proyeccionSemana"] = $proyeccionSemana > 1 ? 1 : $proyeccionSemana;
							//echo ", proyeccionSemana: " . $data["proyeccionSemana"];

	            if (($diasTranscurridos+$proyeccionSemana)>=1 && $diasTotales>=($diasTranscurridos+$proyeccionSemana)){

					//echo "<li> Semanas Faltantes: " . $semanasFaltantes;
					//echo "<li> Ejecutado: " . $Ejecutado;

					$data["Ejecutado_Fin_Semana"] = $Ejecutado + $proyeccionSemana;

	            }else if($diasTotales<($diasTranscurridos+$proyeccionSemana) || ($Ejecutado + $proyeccionSemana) > 1){
	                $data["Ejecutado_Fin_Semana"]=1;
	            }else if($diasTranscurridos<1){
	                $data["Ejecutado_Fin_Semana"]=0;
	            }
	            //echo "<li> Consecutivo: $Consecutivo";
	            //echo "<li> proyeccionSemana: " . $data["proyeccionSemana"];
	            //echo "<li> Ejecutado_Fin_Semana: " . $data["Ejecutado_Fin_Semana"] . "<br><br>";

							// echo "<li>Actividad: ". $data["Actividad"] ." - Ejecutado: $Ejecutado - Días Totales: $diasTotales - Días Lleva: $diasLleva - Días Faltantes: $diasFaltantes - Ejecutado Fin Semana: " . $data["Ejecutado_Fin_Semana"];

							$arreglo["data"][]=array_map("utf8_encode", $data);
            }
            //print_r($arreglo);

            $json_codificado = json_encode($arreglo, JSON_UNESCAPED_UNICODE);
            echo utf8_decode($json_codificado);
            mysqli_free_result($resultado5);
        }
    }
    mysqli_close($conexion);
?>
