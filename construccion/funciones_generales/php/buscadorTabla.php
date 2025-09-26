<?php session_start();
$_SESSION["buscadorTabla"]=$_POST["buscadorTabla"];
$json_codificado = json_encode($_SESSION["buscadorTabla"], JSON_UNESCAPED_UNICODE);
echo utf8_decode($json_codificado);
?>
