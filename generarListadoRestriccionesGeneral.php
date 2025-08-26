<?php session_start();
require ("conexion.php");
//$proyecto=$_GET["P"];

$query="TRUNCATE TABLE general_informe_restricciones_consolidado";
//echo "$query <br>" ;

$resultado= mysqli_query($conexion, $query);

$query1="SELECT  * FROM general_proyectos_procesos WHERE Area='Construccion' AND Activo=1";
//echo "$query1 <br>" ;

$resultado1= mysqli_query($conexion, $query1);

echo "<li>Liberación de Restricciones";

while ($data1=mysqli_fetch_assoc($resultado1)){
    $Proyecto=$data1["Proyecto_Proceso"];
    //echo "<li> $Proyecto";
    $Base_de_Datos=$data1["Base_de_Datos"];

    $query2="INSERT INTO general_informe_restricciones_consolidado (`Proyecto`, `Semana`, `Fecha_Inicio_Sem`, `Fecha_Fin_Sem`, `Actividad`, `Fecha_Inicio`, `Fecha_Fin`, `Semanas_Inicio`, `Restriccion`, `valorRestriccion`, `estadoActividad`) ";

		$query2 .= "SELECT '$Proyecto', $Base_de_Datos"."_programa_consolidado.`Semana`, $Base_de_Datos"."_semanas_activas.`Fecha_Inicio_Sem`, $Base_de_Datos"."_semanas_activas.`Fecha_Fin_Sem`, $Base_de_Datos"."_programa_consolidado.`Actividad`, $Base_de_Datos"."_programa_consolidado.`Fecha_Inicio`, $Base_de_Datos"."_programa_consolidado.`Fecha_Fin`, $Base_de_Datos"."_programa_consolidado.`Semanas_Inicio`, (SELECT 'D_y_E'), (SELECT CASE WHEN $Base_de_Datos"."_programa_consolidado.`D_y_E` IS NULL THEN '0%' WHEN $Base_de_Datos"."_programa_consolidado.`D_y_E`='N/A' THEN 'N/A' ELSE CONCAT(FLOOR($Base_de_Datos"."_programa_consolidado.`D_y_E` * 100),'%') END), $Base_de_Datos"."_programa_consolidado.`Ejecutado` FROM $Base_de_Datos"."_programa_consolidado LEFT JOIN $Base_de_Datos"."_semanas_activas ON $Base_de_Datos"."_semanas_activas.`Semana` = $Base_de_Datos"."_programa_consolidado.`Semana` WHERE $Base_de_Datos"."_programa_consolidado.`D_y_E`!='N/A' AND $Base_de_Datos"."_programa_consolidado.`Titulo`=0 AND $Base_de_Datos"."_programa_consolidado.`Semanas_Inicio`<7 AND $Base_de_Datos"."_programa_consolidado.`Ejecutado`<1 AND $Base_de_Datos"."_programa_consolidado.`Semana`>=((SELECT MAX($Base_de_Datos"."_programa_consolidado.`Semana`) FROM $Base_de_Datos"."_programa_consolidado)-3) UNION ";

		$query2 .= "SELECT '$Proyecto', $Base_de_Datos"."_programa_consolidado.`Semana`, $Base_de_Datos"."_semanas_activas.`Fecha_Inicio_Sem`, $Base_de_Datos"."_semanas_activas.`Fecha_Fin_Sem`, $Base_de_Datos"."_programa_consolidado.`Actividad`, $Base_de_Datos"."_programa_consolidado.`Fecha_Inicio`, $Base_de_Datos"."_programa_consolidado.`Fecha_Fin`, $Base_de_Datos"."_programa_consolidado.`Semanas_Inicio`, (SELECT 'Materiales'), (SELECT CASE WHEN $Base_de_Datos"."_programa_consolidado.`Materiales` IS NULL THEN '0%' WHEN $Base_de_Datos"."_programa_consolidado.`Materiales`='N/A' THEN 'N/A' ELSE CONCAT(FLOOR($Base_de_Datos"."_programa_consolidado.`Materiales` * 100),'%') END), $Base_de_Datos"."_programa_consolidado.`Ejecutado` FROM $Base_de_Datos"."_programa_consolidado LEFT JOIN $Base_de_Datos"."_semanas_activas ON $Base_de_Datos"."_semanas_activas.`Semana` = $Base_de_Datos"."_programa_consolidado.`Semana` WHERE $Base_de_Datos"."_programa_consolidado.`Materiales`!='N/A' AND $Base_de_Datos"."_programa_consolidado.`Titulo`=0 AND $Base_de_Datos"."_programa_consolidado.`Semanas_Inicio`<7 AND $Base_de_Datos"."_programa_consolidado.`Ejecutado`<1 AND $Base_de_Datos"."_programa_consolidado.`Semana`>=((SELECT MAX($Base_de_Datos"."_programa_consolidado.`Semana`) FROM $Base_de_Datos"."_programa_consolidado)-3) UNION ";

		$query2 .= "SELECT '$Proyecto', $Base_de_Datos"."_programa_consolidado.`Semana`, $Base_de_Datos"."_semanas_activas.`Fecha_Inicio_Sem`, $Base_de_Datos"."_semanas_activas.`Fecha_Fin_Sem`, $Base_de_Datos"."_programa_consolidado.`Actividad`, $Base_de_Datos"."_programa_consolidado.`Fecha_Inicio`, $Base_de_Datos"."_programa_consolidado.`Fecha_Fin`, $Base_de_Datos"."_programa_consolidado.`Semanas_Inicio`, (SELECT 'MdeO'), (SELECT CASE WHEN $Base_de_Datos"."_programa_consolidado.`MdeO` IS NULL THEN '0%' WHEN $Base_de_Datos"."_programa_consolidado.`MdeO`='N/A' THEN 'N/A' ELSE CONCAT(FLOOR(`MdeO` * 100),'%') END), $Base_de_Datos"."_programa_consolidado.`Ejecutado` FROM $Base_de_Datos"."_programa_consolidado LEFT JOIN $Base_de_Datos"."_semanas_activas ON $Base_de_Datos"."_semanas_activas.`Semana` = $Base_de_Datos"."_programa_consolidado.`Semana` WHERE $Base_de_Datos"."_programa_consolidado.`MdeO`!='N/A' AND $Base_de_Datos"."_programa_consolidado.`Titulo`=0 AND $Base_de_Datos"."_programa_consolidado.`Semanas_Inicio`<7 AND $Base_de_Datos"."_programa_consolidado.`Ejecutado`<1 AND $Base_de_Datos"."_programa_consolidado.`Semana`>=((SELECT MAX($Base_de_Datos"."_programa_consolidado.`Semana`) FROM $Base_de_Datos"."_programa_consolidado)-3) UNION ";

    $query2 .= "SELECT '$Proyecto', $Base_de_Datos"."_programa_consolidado.`Semana`, $Base_de_Datos"."_semanas_activas.`Fecha_Inicio_Sem`, $Base_de_Datos"."_semanas_activas.`Fecha_Fin_Sem`, $Base_de_Datos"."_programa_consolidado.`Actividad`, $Base_de_Datos"."_programa_consolidado.`Fecha_Inicio`, $Base_de_Datos"."_programa_consolidado.`Fecha_Fin`, $Base_de_Datos"."_programa_consolidado.`Semanas_Inicio`, (SELECT 'Equipos'), (SELECT CASE WHEN $Base_de_Datos"."_programa_consolidado.`Equipos` IS NULL THEN '0%' WHEN $Base_de_Datos"."_programa_consolidado.`Equipos`='N/A' THEN 'N/A' ELSE CONCAT(FLOOR(`Equipos` * 100),'%') END), $Base_de_Datos"."_programa_consolidado.`Ejecutado` FROM $Base_de_Datos"."_programa_consolidado LEFT JOIN $Base_de_Datos"."_semanas_activas ON $Base_de_Datos"."_semanas_activas.`Semana` = $Base_de_Datos"."_programa_consolidado.`Semana` WHERE $Base_de_Datos"."_programa_consolidado.`Equipos`!='N/A' AND $Base_de_Datos"."_programa_consolidado.`Titulo`=0 AND $Base_de_Datos"."_programa_consolidado.`Semanas_Inicio`<7 AND $Base_de_Datos"."_programa_consolidado.`Ejecutado`<1 AND $Base_de_Datos"."_programa_consolidado.`Semana`>=((SELECT MAX($Base_de_Datos"."_programa_consolidado.`Semana`) FROM $Base_de_Datos"."_programa_consolidado)-3) UNION ";

		$query2 .= "SELECT '$Proyecto', $Base_de_Datos"."_programa_consolidado.`Semana`, $Base_de_Datos"."_semanas_activas.`Fecha_Inicio_Sem`, $Base_de_Datos"."_semanas_activas.`Fecha_Fin_Sem`, $Base_de_Datos"."_programa_consolidado.`Actividad`, $Base_de_Datos"."_programa_consolidado.`Fecha_Inicio`, $Base_de_Datos"."_programa_consolidado.`Fecha_Fin`, $Base_de_Datos"."_programa_consolidado.`Semanas_Inicio`, (SELECT 'Predecesora'), (SELECT CASE WHEN $Base_de_Datos"."_programa_consolidado.`Predecesora` IS NULL THEN '0%' WHEN $Base_de_Datos"."_programa_consolidado.`Predecesora`='N/A' THEN 'N/A' ELSE CONCAT(FLOOR(`Predecesora` * 100),'%') END), $Base_de_Datos"."_programa_consolidado.`Ejecutado` FROM $Base_de_Datos"."_programa_consolidado LEFT JOIN $Base_de_Datos"."_semanas_activas ON $Base_de_Datos"."_semanas_activas.`Semana` = $Base_de_Datos"."_programa_consolidado.`Semana` WHERE $Base_de_Datos"."_programa_consolidado.`Predecesora`!='N/A' AND $Base_de_Datos"."_programa_consolidado.`Titulo`=0 AND $Base_de_Datos"."_programa_consolidado.`Semanas_Inicio`<7 AND $Base_de_Datos"."_programa_consolidado.`Ejecutado`<1 AND $Base_de_Datos"."_programa_consolidado.`Semana`>=((SELECT MAX($Base_de_Datos"."_programa_consolidado.`Semana`) FROM $Base_de_Datos"."_programa_consolidado)-3) UNION ";
    //
		$query2 .= "SELECT '$Proyecto', $Base_de_Datos"."_programa_consolidado.`Semana`, $Base_de_Datos"."_semanas_activas.`Fecha_Inicio_Sem`, $Base_de_Datos"."_semanas_activas.`Fecha_Fin_Sem`, $Base_de_Datos"."_programa_consolidado.`Actividad`, $Base_de_Datos"."_programa_consolidado.`Fecha_Inicio`, $Base_de_Datos"."_programa_consolidado.`Fecha_Fin`, $Base_de_Datos"."_programa_consolidado.`Semanas_Inicio`, (SELECT 'Pdto_Cons'), (SELECT CASE WHEN $Base_de_Datos"."_programa_consolidado.`Pdto_Cons` IS NULL THEN '0%' WHEN $Base_de_Datos"."_programa_consolidado.`Pdto_Cons`='N/A' THEN 'N/A' ELSE CONCAT(FLOOR(`Pdto_Cons` * 100),'%') END), $Base_de_Datos"."_programa_consolidado.`Ejecutado` FROM $Base_de_Datos"."_programa_consolidado LEFT JOIN $Base_de_Datos"."_semanas_activas ON $Base_de_Datos"."_semanas_activas.`Semana` = $Base_de_Datos"."_programa_consolidado.`Semana` WHERE $Base_de_Datos"."_programa_consolidado.`Pdto_Cons`!='N/A' AND $Base_de_Datos"."_programa_consolidado.`Titulo`=0 AND $Base_de_Datos"."_programa_consolidado.`Semanas_Inicio`<7 AND $Base_de_Datos"."_programa_consolidado.`Ejecutado`<1 AND $Base_de_Datos"."_programa_consolidado.`Semana`>=((SELECT MAX($Base_de_Datos"."_programa_consolidado.`Semana`) FROM $Base_de_Datos"."_programa_consolidado)-3) UNION ";

		$query2 .= "SELECT '$Proyecto', $Base_de_Datos"."_programa_consolidado.`Semana`, $Base_de_Datos"."_semanas_activas.`Fecha_Inicio_Sem`, $Base_de_Datos"."_semanas_activas.`Fecha_Fin_Sem`, $Base_de_Datos"."_programa_consolidado.`Actividad`, $Base_de_Datos"."_programa_consolidado.`Fecha_Inicio`, $Base_de_Datos"."_programa_consolidado.`Fecha_Fin`, $Base_de_Datos"."_programa_consolidado.`Semanas_Inicio`, (SELECT 'Modelo'), (SELECT CASE WHEN $Base_de_Datos"."_programa_consolidado.`Modelo` IS NULL THEN '0%' WHEN $Base_de_Datos"."_programa_consolidado.`Modelo`='N/A' THEN 'N/A' ELSE CONCAT(FLOOR(`Modelo` * 100),'%') END), $Base_de_Datos"."_programa_consolidado.`Ejecutado` FROM $Base_de_Datos"."_programa_consolidado LEFT JOIN $Base_de_Datos"."_semanas_activas ON $Base_de_Datos"."_semanas_activas.`Semana` = $Base_de_Datos"."_programa_consolidado.`Semana` WHERE $Base_de_Datos"."_programa_consolidado.`Modelo`!='N/A' AND $Base_de_Datos"."_programa_consolidado.`Titulo`=0 AND $Base_de_Datos"."_programa_consolidado.`Semanas_Inicio`<7 AND $Base_de_Datos"."_programa_consolidado.`Ejecutado`<1 AND $Base_de_Datos"."_programa_consolidado.`Semana`>=((SELECT MAX($Base_de_Datos"."_programa_consolidado.`Semana`) FROM $Base_de_Datos"."_programa_consolidado)-3) UNION ";

    $query2 = substr($query2, 0, -7);
    //echo "<li> $query2";
    $resultado2 = mysqli_query($conexion, $query2);
    if(!$resultado2){
        die("<li>$Proyecto - " . mysqli_error($conexion));
    } else{
      $query3 = "DELETE FROM general_informe_restricciones_consolidado WHERE `Fecha_Inicio_Sem`=NULL OR `Fecha_Fin_Sem`=NULL";

      $resultado3 = mysqli_query($conexion, $query3);

      if(!$resultado3){
        die("<li>$Proyecto - " . mysqli_error($conexion));
      } else{
        echo "<li>$Proyecto - OK";
      }
    }
}


//mysqli_free_result($resultado);
//mysqli_free_result($resultado1);
//mysqli_free_result($resultado2);
//mysqli_free_result($resultado3);

mysqli_close($conexion);

//session_destroy();

?>
