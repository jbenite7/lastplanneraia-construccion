<?php
	


$proyecto= ["bodega_latam", "concejo_bogota", "cedi_pasto", "parqueadero_alkosto", "camino_verde", "prueba"];


foreach($proyecto as $value){
    $server = "localhost";
	$user = "aia_fbenitez";/*id11931347_*/
	$password = "ta2AsW(2YU+_";//poner tu propia contraseña, si tienes una.
	$bd = "aia_mascerteza";
	$conexion = mysqli_connect($server, $user, $password, $bd);
	if (!$conexion){ 
		die('Error de Conexión: ' . mysqli_connect_errno());	
	}
    
    $query="DROP TABLE $value"."_cic;
            DROP TABLE $value"."_cip;
            DROP TABLE $value"."_indicadores_generales;
            DROP TABLE $value"."_profesionales;
            DROP TABLE $value"."_programa;
            DROP TABLE $value"."_programacion_semanal;
            DROP TABLE $value"."_programa_consolidado;
            DROP TABLE $value"."_semanas_activas;
            DROP TABLE $value"."_subcontratistas";
    $resultado = mysqli_multi_query($conexion, $query);

    if(!$resultado){
        die(mysqli_error($conexion));
    } else{
        /*while($data=mysqli_fetch_assoc($resultado)){
        $arreglo["data"][]=array_map("utf8_encode", $data);
        }
        $json_codificado = json_encode($arreglo, JSON_UNESCAPED_UNICODE);
        echo utf8_decode($json_codificado);*/
        echo "<li>Proyecto $value Eliminado<br>";
    } 
    mysqli_close($conexion);
    
}



?>