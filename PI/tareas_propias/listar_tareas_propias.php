<?php
	require ("../conexion.php");
    $db=/*"proyectos_inmobiliarios"*/$_GET['db'];
    $semana=/*1*/$_GET["semana"];
    $activa_lookahead=/*1*/$_GET["activa_lookahead"];
    $activa_no_iniciadas=/*1*/$_GET["activa_no_iniciadas"];
    $activa_en_ejecucion=/*1*/$_GET["activa_en_ejecucion"];
    $activa_terminadas=/*1*/$_GET["activa_terminadas"];

    $script="";
    if($activa_lookahead==1){
        $script .= "AND ((Dias_Inicio>7) ";
    }
    if($activa_no_iniciadas==1){
        if($script==""){
            $script .= "AND ((Dias_Inicio<=7 AND Ejecutado=0) ";
        }else{
            $script .= "OR (Dias_Inicio<=7 AND Ejecutado=0) ";
        }
    }
    if($activa_en_ejecucion==1){
        if($script==""){
            $script .= "AND ((Ejecutado>0 AND Ejecutado<1) ";
        }else{
            $script .= "OR (Ejecutado>0 AND Ejecutado<1) ";
        }
    }
    if($activa_terminadas==1){
        if($script==""){
            $script .= "AND ((Ejecutado=1) ";
        }else{
            $script .= "OR (Ejecutado=1) ";
        }
    }
    if($script==""){  
    }else{
        $script .= ")";
    }
        
    $query="SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana AND Titulo=0 AND Dias_Inicio<=Lookahead AND Categoria = 'propias' $script";
    $resultado= mysqli_query($conexion, $query);
    $data=mysqli_fetch_assoc($resultado);
    $conteo=$data["COUNT(*)"];
    mysqli_close($conexion);
    if ($conteo==0){
        $arreglo1["data"][]=array("Consecutivo_en_Programa" => "","Id" => "","Actividad" => "","Titulo" => "","Dias_Inicio" =>"","Lookahead" => "","Periodicidad" => "","Checklist" => "","Relevancia" => "", "Ejecutado" => "","Estado" => "","Estado_Restricciones" => "","R1" => "","R2" => "","R3" => "","R4" => "","R5" => "","R6" => "","R7" => "","R8" => "","R9" => "","R10" => "","R11" => "","R12" => "","R13" => "","R14" => "","R15" => "","R16" => "","R17" => "","R18" => "","R19" => "","R20" => "","R21" => "","R22" => "","R23" => "","R24" => "","R25" => "","Observaciones" => ""); 
        echo json_encode($arreglo1);
    }else{
        require ("../conexion.php");
        $query1 = "SELECT * FROM $db"."_programa_consolidado WHERE Semana=$semana AND Titulo=0 AND Dias_Inicio<=Lookahead AND Categoria = 'propias' $script";
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

        mysqli_close($conexion);
    }
    

?>

