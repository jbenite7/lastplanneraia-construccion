<?php
	$server = "localhost";
	$user = "uasgrofcw1fgs";
	$password = "Las#0510!";//poner tu propia contraseña, si tienes una.
	$bd = "dbbfn7fojgsqao";
  // $server = "localhost";
	// $user = "id11931347_jbenitez";
	// $password = "Jbe#1106z";//poner tu propia contraseña, si tienes una.
	// $bd = "u889229807_lastplanner";
	$conexion = mysqli_connect($server, $user, $password, $bd);
	if (!$conexion){
		die('Error de Conexión: ' . mysqli_connect_errno());
	}

//SELECT Fecha_Inicio_Sem FROM `cross_semanas_activas` WHERE Semana=(SELECT MAX(Semana) FROM `cross_semanas_activas`)
?>
