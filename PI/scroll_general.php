<?php session_start();
$_SESSION["scroll"]=$_GET["scroll"];
$seccion=$_SESSION["seccion"];
$semana=$_SESSION["semana"];
//echo $_SESSION["scroll"];
header("Location: cambiar_pagina.php?seccion=$seccion&semana=$semana");

?>
