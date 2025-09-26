<?php

	require ("../conexion.php");
    $semana=$_GET["semana"];
    $db=$_GET['db'];

    $query="SELECT  COUNT(*) FROM $db"."_programacion_semanal WHERE Semana=$semana AND Activa='0'";
    $resultado= mysqli_query($conexion, $query);
    $data=mysqli_fetch_assoc($resultado);
    $conteo=$data["COUNT(*)"];
    mysqli_close($conexion);
    if ($conteo==0){
        $arreglo1["data"][]=array("Consecutivo" => "","Id" => "","Actividad" => "","Descripcion" => "","Clase" => "","Responsable_AIA" => "","Prog_Sin_Restricciones_100" => "","Categoria_CNP" => "","CNP" => "", "Observaciones_CNP" => ""); 
        echo json_encode($arreglo1);
    } else{
        require ("../conexion.php");
        $query2 = "SELECT * FROM $db"."_programacion_semanal WHERE (Semana=$semana AND Activa='0')";
        $resultado2 = mysqli_query($conexion, $query2);
        if(!$resultado2){
            die("Error");
        } else{
            while($data=mysqli_fetch_assoc($resultado2)){
            $arreglo["data"][]=array_map("utf8_encode", $data);
            }
            $json_codificado = json_encode($arreglo, JSON_UNESCAPED_UNICODE);
            echo utf8_decode($json_codificado);
            mysqli_close($conexion);
            mysqli_free_result($resultado);
        }     
    }
?>   

