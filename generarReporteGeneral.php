<?php session_start();
require ("conexion.php");


$query="TRUNCATE TABLE general_informe_consolidado";
//echo "$query <br>" ;

$resultado= mysqli_query($conexion, $query);

$query1="SELECT  * FROM general_proyectos_procesos WHERE Area='Construccion' AND Activo=1";
//echo "$query1 <br>" ;

$resultado1= mysqli_query($conexion, $query1);
$query2="INSERT INTO general_informe_consolidado (`Proyecto`, `Semana`, `maxSemana`, `Proyecto_maxSemana`, `Actividad`, `Fecha_Inicio`, `Fecha_Fin`, `Fecha_Inicio_Sem`, `Fecha_Fin_Sem`, `Critica`, `Atrasada`, `Activa`, `Ejecutado`, `cantidad_ppto`, `Cantidad_Sugerida`, `Compromiso`, `Unidad`, `Ejecutado_Real`, `PAC`, `P_Completado`, `Categoria_CNP`, `CNP`, `Observaciones_CNP`, `Categoria_CNC`, `CNC`, `Observaciones_CNC`, `Responsable_AIA`, `Sub_Contratista`)";
while ($data1=mysqli_fetch_assoc($resultado1)){
    $Proyecto=$data1["Proyecto_Proceso"];
    //echo "<li> $Proyecto";
    $Base_de_Datos=$data1["Base_de_Datos"];

    // $query2 .= " SELECT '$Proyecto', `Semana`, `Actividad`, NULL, NULL, `Critica`, `Atrasada`, `Activa`, `cantidad_ppto`, `Cantidad_Sugerida`, `Compromiso`, `Ejecutado_Real`, `PAC`, `P_Completado`, `Categoria_CNP`, `CNP`, `Observaciones_CNP`, `Categoria_CNC`, `CNC`, `Observaciones_CNC`, `Responsable_AIA`, `Sub_Contratista` FROM $Base_de_Datos"."_programacion_semanal WHERE Semana<=((SELECT MAX(Semana) FROM $Base_de_Datos"."_programacion_semanal)) UNION ";

    $query2 .= " SELECT '$Proyecto', $Base_de_Datos"."_programacion_semanal.`Semana`, (SELECT MAX($Base_de_Datos"."_programacion_semanal.`Semana`) FROM $Base_de_Datos"."_programacion_semanal), CONCAT('$Proyecto (', (SELECT `Fecha_Fin_Sem` FROM $Base_de_Datos"."_semanas_activas WHERE Semana = (SELECT MAX($Base_de_Datos"."_programacion_semanal.`Semana`) FROM $Base_de_Datos"."_programacion_semanal)),')'), $Base_de_Datos"."_programacion_semanal.`Actividad`,$Base_de_Datos"."_programacion_semanal.`Fecha_Inicio`, $Base_de_Datos"."_programacion_semanal.`Fecha_Fin`, $Base_de_Datos"."_semanas_activas.`Fecha_Inicio_Sem`, $Base_de_Datos"."_semanas_activas.`Fecha_Fin_Sem`, $Base_de_Datos"."_programacion_semanal.`Critica`, $Base_de_Datos"."_programacion_semanal.`Atrasada`, $Base_de_Datos"."_programacion_semanal.`Activa`, $Base_de_Datos"."_programacion_semanal.`Ejecutado`, $Base_de_Datos"."_programacion_semanal.`cantidad_ppto`, $Base_de_Datos"."_programacion_semanal.`Cantidad_Sugerida`, $Base_de_Datos"."_programacion_semanal.`Compromiso`, $Base_de_Datos"."_programacion_semanal.`unidad`, $Base_de_Datos"."_programacion_semanal.`Ejecutado_Real`, $Base_de_Datos"."_programacion_semanal.`PAC`, $Base_de_Datos"."_programacion_semanal.`P_Completado`, $Base_de_Datos"."_programacion_semanal.`Categoria_CNP`, $Base_de_Datos"."_programacion_semanal.`CNP`, $Base_de_Datos"."_programacion_semanal.`Observaciones_CNP`, $Base_de_Datos"."_programacion_semanal.`Categoria_CNC`, $Base_de_Datos"."_programacion_semanal.`CNC`, $Base_de_Datos"."_programacion_semanal.`Observaciones_CNC`, $Base_de_Datos"."_programacion_semanal.`Responsable_AIA`, $Base_de_Datos"."_programacion_semanal.`Sub_Contratista` FROM $Base_de_Datos"."_programacion_semanal LEFT JOIN $Base_de_Datos"."_semanas_activas ON $Base_de_Datos"."_semanas_activas.`Semana`=$Base_de_Datos"."_programacion_semanal.`Semana` WHERE $Base_de_Datos"."_programacion_semanal.`Semana`>=((SELECT MAX($Base_de_Datos"."_programacion_semanal.`Semana`) FROM $Base_de_Datos"."_programacion_semanal)-12) UNION ";


}
$query2 = substr($query2, 0, -7);
//echo "<li> $query2";
$resultado2= mysqli_query($conexion, $query2);
if(!$resultado2){
    die(mysqli_error($conexion));
} else{
    
    $query3 = "DELETE FROM general_informe_consolidado WHERE `Fecha_Inicio_Sem`=NULL OR `Fecha_Fin_Sem`=NULL";
    //echo "<li> $query3";
    $resultado3 = mysqli_query($conexion, $query3);

    if(!$resultado3){
        die(mysqli_error($conexion));
    } else{
        echo "<li>Programación Semanal - OK";
    }
}
    //mysqli_free_result($resultado);
    //mysqli_free_result($resultado1);
    //mysqli_free_result($resultado2);*/

    mysqli_close($conexion);

//session_destroy();

?>
