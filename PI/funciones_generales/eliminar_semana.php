<?php

$query3="DELETE FROM $db"."_semanas_activas WHERE Semana=$semana;";
$resultado= mysqli_query($conexion, $query3);

$query4="DELETE FROM $db"."_programa_consolidado WHERE Semana=$semana;";
$query5="DELETE FROM $db"."_programacion_semanal WHERE Semana=$semana;";
$query6="DELETE FROM $db"."_cic WHERE Semana=$semana;";
$query7="DELETE FROM $db"."_cip WHERE Semana=$semana;";
$query8="DELETE FROM $db"."_indicadores_generales WHERE Semana=$semana;";

$resultado1= mysqli_query($conexion, $query4);
$resultado2= mysqli_query($conexion, $query5);
$resultado3= mysqli_query($conexion, $query6);
$resultado4= mysqli_query($conexion, $query7);
$resultado5= mysqli_query($conexion, $query8);

mysqli_close($conexion);  

?>