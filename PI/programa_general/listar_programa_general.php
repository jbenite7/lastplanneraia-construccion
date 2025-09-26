<?php
	require ("../conexion.php");
    $db=/*"proyectos_inmobiliarios"*/$_GET['db'];
    $semana=/*1*/$_GET["semana"];
    $activa_no_requeridas=/*0*/$_GET["activa_no_requeridas"];
    $activa_lookahead=/*0*/$_GET["activa_lookahead"];
    $activa_no_iniciadas=/*1*/$_GET["activa_no_iniciadas"];
    $activa_en_ejecucion=/*1*/$_GET["activa_en_ejecucion"];
    $activa_terminadas=/*1*/$_GET["activa_terminadas"];

    $script="";
    if($activa_no_requeridas==1){
        $script .= "AND ((Dias_Inicio>7 AND Lookahead<Dias_Inicio AND Ejecutado=0) ";
    }
    if($activa_lookahead==1){
        if($script==""){
            $script .= "AND ((Dias_Inicio>7 AND Lookahead>=Dias_Inicio AND Ejecutado=0) ";
        }else{
            $script .= "OR (Dias_Inicio>7 AND Lookahead>=Dias_Inicio AND Ejecutado=0) ";
        }
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

    $query="SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana $script";
    $resultado= mysqli_query($conexion, $query);
    $data=mysqli_fetch_assoc($resultado);
    $conteo=$data["COUNT(*)"];
    mysqli_close($conexion);
    if ($conteo==0){
        $arreglo1["data"][]=array("Consecutivo" => "","Semana" => "","Consecutivo_en_Programa" => "","Id" => "","Actividad" => "","Titulo" => "","Dias_Inicio" =>"","Fecha_Inicio" => "","Fecha_Fin" => "", "Ruta_Critica" => "", "Ejecutado" => "","Estado" => "","Categoria" =>"","Lookahead" => "","Periodicidad" => "", "boton" =>""); 
        echo json_encode($arreglo1);
    }else{
        require ("../conexion.php");
        $query1 = "SELECT * FROM $db"."_programa_consolidado WHERE Semana=$semana $script";
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

