<?php
	$server = "localhost";
	$user = "id11931347_jbenitez";/*id11931347_*/
	$password = "Jbe#1106z";//poner tu propia contraseña, si tienes una.
	$bd = "id11931347_jbenitez";
	$conexion = mysqli_connect($server, $user, $password, $bd);
	if (!$conexion){ 
		die('Error de Conexión: ' . mysqli_connect_errno());	
	}

//SELECT Fecha_Inicio_Sem FROM `cross_semanas_activas` WHERE Semana=(SELECT MAX(Semana) FROM `cross_semanas_activas`)
/*$query = "CREATE TABLE `general_usuarios_prueba` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `cargo` varchar(100) NOT NULL,
  `proyecto` varchar(50) NOT NULL,
  `permiso` varchar(50) NOT NULL,
  `usuario` varchar(20) NOT NULL,
  `password` varchar(200) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";*/

$query ="CREATE TABLE `general_proyectos_procesos` (
  `Id` int(11) NOT NULL,
  `Proyecto_Proceso` varchar(50) NOT NULL,
  `Base_de_Datos` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `general_proyectos_procesos` (`Id`, `Proyecto_Proceso`, `Base_de_Datos`) VALUES
(4, 'Paris Campestre', 'paris_campestre'),
(6, 'Clínica del Sur', 'clinica_del_sur'),
(7, 'BTS Toberin', 'bts_toberin'),
(8, 'MallPlaza Cali', 'mallplaza_cali'),
(9, 'Prueba', 'prueba');";
$resultado= mysqli_multi_query($conexion, $query);
//$data=mysqli_fetch_assoc($resultado);

    
?>

