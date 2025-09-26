<?php session_start();
$_SESSION["posicion_intermedia"]=$_GET["posicion_intermedia"];
//echo $_SESSION["posicion_intermedia"];
header("Location: programacion_intermedia.php");

?>
