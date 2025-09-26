<?php
	require ("../conexion.php");
    $db=/*"prueba"*/$_GET['db'];
    $semana=/*7*/$_GET["semana"];

    $query="SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=" . ($semana+1) . " AND Fecha_Inicio IS NOT NULL AND Fecha_Fin IS NOT NULL AND Titulo != 1 AND (Ejecutado IS NULL OR Estado_Restricciones IS NULL OR programaAnteriorAsociar = '*No Asociada*')";
    $resultado= mysqli_query($conexion, $query);
    $data=mysqli_fetch_assoc($resultado);
    $conteo=$data["COUNT(*)"];
    if ($conteo==0){
        $arreglo1["data"][]=array("Consecutivo" => "","Semana" => "","Consecutivo_en_Programa" => "","Id" => "","Actividad" => "","Titulo" => "","Semanas_Inicio" =>"","Fecha_Inicio" => "","Fecha_Fin" => "", "Ruta_Critica" => "", "unidad" => "", "cantidad_ppto" => "", "medir_productividad" => "", "codigo_actividad" => "", "Ejecutado_Teorico" =>"", "Ejecutado" => "","Estado" => "","Estado_Restricciones" =>"","Responsable_AIA" => "","Sub_Contratista" => "","programaAnteriorAsociar" => "", "boton" =>"");
        echo json_encode($arreglo1);
    }else{
        $query_= "SELECT Fecha_Inicio_Sem, Fecha_Fin_Sem FROM $db"."_semanas_activas WHERE Semana=$semana";
        $resultado_ = mysqli_query($conexion, $query_);
        $data_=mysqli_fetch_assoc($resultado_);
        $Fecha_Inicio_Sem=date("Y-m-d",strtotime($data_["Fecha_Inicio_Sem"]));
        $Fecha_Fin_Sem=date("Y-m-d",strtotime($data_["Fecha_Fin_Sem"]));

        $query1 = "SELECT * FROM $db"."_programa_consolidado WHERE Semana=" . ($semana+1) . " AND Fecha_Inicio IS NOT NULL AND Fecha_Fin IS NOT NULL AND Titulo != 1 AND (Ejecutado IS NULL OR Estado_Restricciones IS NULL OR programaAnteriorAsociar = '*No Asociada*') ORDER BY Semanas_Inicio ASC, Consecutivo ASC, Consecutivo_en_Programa ASC, Id ASC";
        $resultado1 = mysqli_query($conexion, $query1);

        if(!$resultado1){
            die("Error");
        } else{
            while($data1=mysqli_fetch_assoc($resultado1)){
                $titulo=$data1['Titulo'];
                $Fecha_Inicio_Act=date("Y-m-d",strtotime($data1['Fecha_Inicio']));
                $Fecha_Fin_Act=date("Y-m-d",strtotime($data1['Fecha_Fin']));
                if($titulo==1){
                    $data1["boton"]="No Boton";
                }else{
                    $data1["boton"]="Boton";
                }

                //echo "<li> $titulo, $Fecha_Inicio_Sem, $Fecha_Fin_Sem, $Fecha_Inicio_Act, $Fecha_Fin_Act <br>";

                $diasLleva = ((strtotime($Fecha_Inicio_Sem)-strtotime($Fecha_Inicio_Act))/86400);
                $diasTotales = ((strtotime($Fecha_Fin_Act)-strtotime($Fecha_Inicio_Act))/86400)+1;


                if($titulo==1 /*&& $data1['Fecha_Inicio']==NULL && $data1['Fecha_Fin']==NULL*/){
                    $data1["Ejecutado_Teorico"]= NULL;
                }else if ($diasLleva>=1 && $diasTotales>=$diasLleva){
                    $data1["Ejecutado_Teorico"]= ($diasLleva / $diasTotales);
                }else if($diasTotales<$diasLleva){
                    $data1["Ejecutado_Teorico"]= 1;
                }else if($diasLleva<1){
                    $data1["Ejecutado_Teorico"]=0;
                }

                $arreglo["data"][]=array_map("utf8_encode", $data1);
            }
            $json_codificado = json_encode($arreglo, JSON_UNESCAPED_UNICODE);
            echo utf8_decode($json_codificado);
        }
        mysqli_free_result($resultado);
    }
    mysqli_close($conexion);


?>
