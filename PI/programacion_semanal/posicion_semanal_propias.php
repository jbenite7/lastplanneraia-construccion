<?php session_start();
$_SESSION["posicion_semanal"]=$_GET["posicion_semanal"];
//echo $_SESSION["posicion_intermedia"];
header("Location: programacion_semanal_propias.php");

?>
