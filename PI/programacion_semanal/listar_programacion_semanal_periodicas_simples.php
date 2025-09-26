<?php
	require ("../conexion.php");
    $semana=/*20*/$_GET["semana"];
    $db=/*"prueba"*/$_GET['db'];

    $query="SELECT  COUNT(*) FROM $db"."_programacion_semanal WHERE Semana=$semana";
    //echo "$query2 <br>" ;
    $resultado= mysqli_query($conexion, $query);
    $data=mysqli_fetch_assoc($resultado);
    $conteo=$data["COUNT(*)"];
    //echo "$conteo <br>" ;
    if ($conteo==0){
    }else{
        $query1="SELECT * FROM $db"."_programacion_semanal WHERE Semana=$semana";
        $resultado1= mysqli_query($conexion, $query1);
        $query3="";
        while ($data1=mysqli_fetch_assoc($resultado1)){
            $Consecutivo_En_Programa=$data1["Consecutivo_En_Programa"];
            $Activa=$data1["Activa"];
            if($Activa!='NA'){
                $query3 .= "UPDATE $db"."_programacion_semanal SET Prog_Sin_Restricciones_100=(SELECT CASE WHEN $db"."_programa_consolidado . Estado_Restricciones<1 THEN 1 ELSE 0 END FROM $db"."_programa_consolidado WHERE Semana=$semana AND Consecutivo_en_Programa=$Consecutivo_En_Programa) WHERE Semana=$semana AND Consecutivo_En_Programa=$Consecutivo_En_Programa AND Activa!='NA';";
            }else{
                $query3 .= "UPDATE $db"."_programacion_semanal SET Prog_Sin_Restricciones_100=0 WHERE Semana=$semana AND Consecutivo_En_Programa=$Consecutivo_En_Programa;";
            }
        };
        
        //echo $query3;
        $resultado3 = mysqli_multi_query($conexion, $query3);
        mysqli_close($conexion);
    } 

    require("../conexion.php");
    $query4="SELECT  COUNT(*) FROM $db"."_programacion_semanal WHERE Semana='$semana' AND (Activa='1' OR Activa='NA')  AND (Clase='periodicas_simples' OR Clase='periodicas_simples (duplicada)')";
    $resultado4= mysqli_query($conexion, $query4);
    $data=mysqli_fetch_assoc($resultado4);
    $conteo=$data["COUNT(*)"];
    mysqli_close($conexion);
    if ($conteo==0){
        $arreglo1["data"][]=array("Consecutivo" => "","Id" => "","Actividad" => "","Prog_Sin_Restricciones_100" => "","Descripcion" =>"","Clase" => "","Sub_Contratista" => "","Responsable_AIA" => "", "Unidad" => "","Compromiso" => "","Ejecutado_Real" => "","P_Completado" => "","PAC" => "","Activa" => ""); 
        echo json_encode($arreglo1);
    } else{
        require ("../conexion.php");
        $query5 = "SELECT * FROM $db"."_programacion_semanal WHERE Semana='$semana' AND (Activa='1' OR Activa='NA')  AND (Clase='periodicas_simples' OR Clase='periodicas_simples (duplicada)') ORDER BY Consecutivo_En_Programa ASC";
        $resultado5 = mysqli_query($conexion, $query5);
        if(!$resultado5){
            die("Error");
        } else{
            while($data=mysqli_fetch_assoc($resultado5)){
            $arreglo["data"][]=array_map("utf8_encode", $data);
            }
            $json_codificado = json_encode($arreglo, JSON_UNESCAPED_UNICODE);
            echo utf8_decode($json_codificado);
            mysqli_close($conexion);
            mysqli_free_result($resultado);
        }     
    }
?>
    

