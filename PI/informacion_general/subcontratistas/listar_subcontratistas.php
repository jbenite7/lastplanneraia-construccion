<?php
	require ("../../conexion.php");

    $db=$_GET['db'];
	$query = "SELECT COUNT(*) FROM $db"."_subcontratistas;";
	$resultado = mysqli_query($conexion, $query);
    $data=mysqli_fetch_assoc($resultado);
    $conteo=$data["COUNT(*)"];
    mysqli_close($conexion);
    if ($conteo==0){
        $arreglo1["data"][]=array("Id" => "","subcontratista" => "","correo_contacto" => "","NIT" => "","alcance" => "","tipo_proveedor" => ""); 
        echo json_encode($arreglo1);
    }else{
        require ("../../conexion.php");
        $query1 = "SELECT * FROM $db"."_subcontratistas;";
        $resultado1 = mysqli_query($conexion, $query1);
    
        if(!$resultado1){
            die("Error");
        } else{
            while($data=mysqli_fetch_assoc($resultado1)){
                $arreglo["data"][]=array_map("utf8_encode", $data);
            }
            $json_codificado = json_encode($arreglo, JSON_UNESCAPED_UNICODE);
            echo utf8_decode($json_codificado);
        }
        mysqli_free_result($resultado);

        mysqli_close($conexion);    
    }
?>

