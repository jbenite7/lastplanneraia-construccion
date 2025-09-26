<?php
	$server = "localhost";
	$user = "u889229807_aiapilps";
	$password = "Jbe#1106z";//poner tu propia contraseña, si tienes una.
	$bd = "u889229807_pilastplanner";
    /*$server = "localhost";
	$user = "id11931347_jbenitez";
	$password = "Jbe#1106z";//poner tu propia contraseña, si tienes una.
	$bd = "id11931347_jbenitez";*/
	$conexion = mysqli_connect($server, $user, $password, $bd);
	if (!$conexion){
		die('Error de Conexión: ' . mysqli_connect_errno());
	}

//SELECT Fecha_Inicio_Sem FROM `cross_semanas_activas` WHERE Semana=(SELECT MAX(Semana) FROM `cross_semanas_activas`)
?>
