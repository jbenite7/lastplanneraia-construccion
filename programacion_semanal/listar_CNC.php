<?php

	require ("../conexion.php");
    $semana=$_GET["semana"];
    $db=$_GET['db'];

    $query="SELECT  COUNT(*) FROM $db"."_programacion_semanal WHERE (Semana=$semana AND PAC=0)";
    $resultado= mysqli_query($conexion, $query);
    $data=mysqli_fetch_assoc($resultado);
    $conteo=$data["COUNT(*)"];
    if ($conteo==0){
        $arreglo1["data"][]=array("Consecutivo" => "","Id" => "","Actividad" => "","Descripcion" => "","Ubicacion" => "","Categoria_CNC" => "","CNC" => "", "Observaciones_CNC" => ""); 
        echo json_encode($arreglo1);
    } else{
        $query2 = "SELECT * FROM $db"."_programacion_semanal WHERE (Semana=$semana AND PAC=0 AND (Activa=1 OR Activa='NA'))";
        $resultado2 = mysqli_query($conexion, $query2);
        if(!$resultado2){
            die("Error");
        } else{
            while($data=mysqli_fetch_assoc($resultado2)){
            $arreglo["data"][]=array_map("utf8_encode", $data);
            }
            $json_codificado = json_encode($arreglo, JSON_UNESCAPED_UNICODE);
            echo utf8_decode($json_codificado);
            mysqli_free_result($resultado);
        }     
    }
    mysqli_close($conexion);
?>

