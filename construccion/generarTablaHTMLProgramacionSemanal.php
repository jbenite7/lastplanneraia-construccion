<?php session_start();
require ("conexion.php");




$query="SELECT  (MAX(P_Completado) - MIN(P_Completado)) AS rango, (1+3.332*LOG10(COUNT(P_Completado))) AS numeroClases, COUNT(P_Completado) AS numeroDatos FROM general_informe_consolidado WHERE P_Completado IS NOT NULL AND (Activa=1 OR Activa='NA')";
//echo "$query1 <br>" ;

$resultado= mysqli_query($conexion, $query);
$data=mysqli_fetch_assoc($resultado);
$rango = $data["rango"];
$numeroClases = $data["numeroClases"];
$numeroDatos = $data["numeroDatos"];
echo "$rango, $numeroClases, $numeroDatos";

mysqli_close($conexion);
session_destroy();

?>
