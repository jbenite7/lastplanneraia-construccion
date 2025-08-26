<?php
require("../../conexion.php");

$db=/*"cross"*/$_GET['db'];
$opcion=/*"nueva_sem"*/$_POST["opcion"];
$semana=$_POST["semana"];

$query = "SELECT MAX(Semana) AS maxSemana FROM $db"."_semanas_activas";
$resultado= mysqli_query($conexion, $query);
$data= mysqli_fetch_assoc($resultado);
$maxSemana = $data["maxSemana"];

if($maxSemana > $semana){
  $arreglo["maxSemana"]=$maxSemana;
  $arreglo["puedeEliminar"]="NO";
  echo utf8_decode(json_encode($arreglo));
}else{
  $query3="DELETE FROM $db"."_semanas_activas WHERE Semana>=$semana;";
  $resultado= mysqli_query($conexion, $query3);

  $query4="DELETE FROM $db"."_programa_consolidado WHERE Semana>=$semana;";
  $query5="DELETE FROM $db"."_programacion_semanal WHERE Semana>=$semana;";
  $query6="DELETE FROM $db"."_cic WHERE Semana>=$semana;";
  // $query7="DELETE FROM $db"."_cip WHERE Semana>=$semana;";
  // $query8="DELETE FROM $db"."_indicadores_generales WHERE Semana>=$semana;";
  $query9="DELETE FROM $db"."_pdc WHERE semana>=$semana;";
  $query10="DELETE FROM $db"."_actividades WHERE semanaActualizacion>=$semana;";

  $resultado1= mysqli_query($conexion, $query4);
  $resultado2= mysqli_query($conexion, $query5);
  $resultado3= mysqli_query($conexion, $query6);
  // $resultado4= mysqli_query($conexion, $query7);
  // $resultado5= mysqli_query($conexion, $query8);
  $resultado6= mysqli_query($conexion, $query9);
  $resultado7= mysqli_query($conexion, $query10);

  mysqli_close($conexion);

  $arreglo["maxSemana"]=$maxSemana;
  $arreglo["puedeEliminar"]="SI";
  echo utf8_decode(json_encode($arreglo));
}
?>
