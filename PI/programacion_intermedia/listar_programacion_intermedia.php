<?php


	require ("../conexion.php");

    $db=/*"cross"*/$_GET['db'];
    $semana=/*2*/$_GET["semana"];
    
    
//    echo $fin_intermedia;
//    (Dias_Inicio<=6) AND 
	$query = "SELECT * FROM $db"."_programa_consolidado WHERE Semana=$semana AND Titulo=0 AND (Estado_Restricciones<1 OR Ejecutado<1)";
	$resultado = mysqli_query($conexion, $query);

	if(!$resultado){
        die("Error");
    } else{
        while($data=mysqli_fetch_assoc($resultado)){
            $titulo=$data['Titulo'];
            if($titulo==1){
                $data["boton"]="No Boton";
            }else{
                $data["boton"]="Boton";
                }
            $arreglo["data"][]=array_map("utf8_encode", $data);
        }
        $json_codificado = json_encode($arreglo, JSON_UNESCAPED_UNICODE);
        echo utf8_decode($json_codificado);
    }
    mysqli_free_result($resultado);

    mysqli_close($conexion);