<?php


	require ("../conexion.php");
    $db=/*"cross"*/$_GET['db'];
    $semana=/*1*/$_GET["semana"];
    $activa_lookahead=/*1*/$_GET["activa_lookahead"];
    $activa_no_iniciadas=/*0*/$_GET["activa_no_iniciadas"];
    $activa_en_ejecucion_pendientes=/*0*/$_GET["activa_en_ejecucion_pendientes"];
    $activa_en_ejecucion_terminadas=/*0*/$_GET["activa_en_ejecucion_terminadas"];

    $script="";
    if($activa_lookahead==1){
        $script .= "AND ((Semanas_Inicio>0 AND Semanas_Inicio<=6 AND Ejecutado=0) ";
    }
    if($activa_no_iniciadas==1){
        if($script==""){
            $script .= "AND ((Semanas_Inicio<=0 AND Ejecutado=0) ";
        }else{
            $script .= "OR (Semanas_Inicio<=0 AND Ejecutado=0) ";
        }
    }
    if($activa_en_ejecucion_pendientes==1){
        if($script==""){
            $script .= "AND ((Ejecutado>0 AND Ejecutado<1 AND Estado_Restricciones<1) ";
        }else{
            $script .= "OR (Ejecutado>0 AND Ejecutado<1 AND Estado_Restricciones<1) ";
        }
    }
    if($activa_en_ejecucion_terminadas==1){
        if($script==""){
            $script .= "AND ((Ejecutado>0 AND Ejecutado<1 AND Estado_Restricciones=1) ";
        }else{
            $script .= "OR (Ejecutado>0 AND Ejecutado<1 AND Estado_Restricciones=1) ";
        }
    }
    if($script==""){
    }else{
        $script .= ")";
    }
    $query="SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Fecha_Inicio IS NOT NULL AND Fecha_Fin IS NOT NULL AND Semanas_Inicio<=6 AND Ejecutado<1 AND Titulo=0 $script";
    $resultado= mysqli_query($conexion, $query);
    $data=mysqli_fetch_assoc($resultado);
    $conteo=$data["COUNT(*)"];
    if ($conteo==0){
        $arreglo1["data"][]=array("Consecutivo" => "","Semana" => "","Consecutivo_en_Programa" => "","Id" => "","Actividad" => "","Titulo" => "","Semanas_Inicio" =>"","Fecha_Inicio" => "","Fecha_Fin" => "", "Ruta_Critica" => "", "Ejecutado" => "","Estado" => "","Estado_Restricciones" =>"", "Responsable_AIA" =>"", "Observaciones" =>"", "boton" =>"");
        echo json_encode($arreglo1);
    }else{
        $query1 = "SELECT * FROM $db"."_programa_consolidado WHERE Semana=$semana AND Fecha_Inicio IS NOT NULL AND Fecha_Fin IS NOT NULL AND Semanas_Inicio<=6 AND Ejecutado<1 AND Titulo=0 $script ORDER BY Semanas_Inicio ASC";
        //OR (Semana=$semana AND Titulo=1)
        $resultado1 = mysqli_query($conexion, $query1);

        if(!$resultado1){
            die("Error");
        } else{
            while($data1=mysqli_fetch_assoc($resultado1)){
                $titulo=$data1['Titulo'];
                if($titulo==1){
                    $data1["boton"]="No Boton";
                }else{
                    $data1["boton"]="Boton";
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
