<?php

    require("../conexion.php");

    $db=$_GET['db'];
	$query = "SELECT * FROM $db"."_programa WHERE Titulo=0;";
	$resultado = mysqli_query($conexion, $query);

	if(!$resultado){
        die("Error");
    } else{
        while($data=mysqli_fetch_assoc($resultado)){
            $arreglo["data"][]=array_map("utf8_encode", $data);
        }
        echo json_encode($arreglo);
    }
    mysqli_free_result($resultado);

    mysqli_close($conexion);

