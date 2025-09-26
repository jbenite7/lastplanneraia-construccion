<?php session_start();
$_SESSION["posicion_general"]=$_GET["posicion_general"];
//echo $_SESSION["posicion_intermedia"];
header("Location: programa_general.php");

?>
