<?php
	require_once __DIR__ . '/vendor/autoload.php';

	$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
	$dotenv->load();

	$server = $_ENV['DB_HOST'];
	$user = $_ENV['DB_USER'];
	$password = $_ENV['DB_PASS'];//poner tu propia contraseña, si tienes una.
	$bd = $_ENV['DB_NAME'];
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
