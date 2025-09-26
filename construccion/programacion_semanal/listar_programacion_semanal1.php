<?php
	require ("../conexion.php");
    $semana=$_GET["semana"];
    $db=$_GET['db'];
		// $semana=11;
		// $db='brizaDelCabrero';

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

							//echo "<li> $Ejecutado";

							$diasLlevaInicial = ((strtotime($Fecha_Inicio_Sem)-strtotime($Fecha_Inicio_Act))/86400);
							$diasLleva = $diasLlevaInicial <= 0 ? 0 : $diasLlevaInicial;

							if($Fecha_Inicio_Act > $Fecha_Fin_Sem){
								$diasSemanaInicial = 0;
							}else{
								$diasSemanaInicial = $Fecha_Fin_Act <  $Fecha_Fin_Sem ? ((strtotime($Fecha_Fin_Act)-strtotime($Fecha_Inicio_Sem))/86400)+1 : 7;
							}

							if($Fecha_Inicio_Act >= $Fecha_Inicio_Sem && $Fecha_Fin_Act <= $Fecha_Fin_Sem){
								$diasSemanaInicial = ((strtotime($Fecha_Fin_Act)-strtotime($Fecha_Inicio_Act))/86400)+1;
							}
                            console.log($diasSemanaInicial);

							$data["diasSemanaInicial"] = $diasSemanaInicial;
							$diasSemana = $diasSemanaInicial <= 0 ? 0 : $diasSemanaInicial;
							$diasTotales = ((strtotime($Fecha_Fin_Act)-strtotime($Fecha_Inicio_Act))/86400)+1;
							$data["diasLleva"] = $diasLleva;
							$data["diasSemana"] = $diasSemana;
							$data["diasTotales"] = $diasTotales;
							console.log($diasSemana);
							console.log($diasTotales);
							console.log($diasLleva);
							

							$proyeccionSemana = $diasTotales <= $diasLleva ? (1 - $Ejecutado) : ((1 - $Ejecutado) / ($diasTotales - $diasLleva)) * ($diasSemana);

							$data["proyeccionSemana"] = $proyeccionSemana > 1 ? 1 : $proyeccionSemana;

	            if (($diasLleva+$diasSemana)>=1 && $diasTotales>=($diasLleva+$diasSemana)){

									//echo "<li> Semanas Faltantes: " . $semanasFaltantes;
									//echo "<li> Ejecutado: " . $Ejecutado;

									$data["Ejecutado_Fin_Semana"] = $Ejecutado + $proyeccionSemana;

	            }else if($diasTotales<($diasLleva+$diasSemana) || $data["Ejecutado_Fin_Semana"] > 1){
	                $data["Ejecutado_Fin_Semana"]=1;
	            }else if($diasLleva<1){
	                $data["Ejecutado_Fin_Semana"]=0;
	            }

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
