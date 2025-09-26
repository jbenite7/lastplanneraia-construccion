<?php session_start();
$_SESSION["posicion_contratos"]=$_GET["posicion_contratos"];
//echo $_SESSION["posicion_intermedia"];
header("Location: contratos.php");

?>
