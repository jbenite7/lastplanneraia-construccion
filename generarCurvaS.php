<?php
session_start();
require ("conexion.php");


$query="TRUNCATE TABLE general_curvas";
//echo "$query <br>" ;

$resultado= mysqli_query($conexion, $query);

$query1="SELECT  * FROM general_proyectos_procesos WHERE Area='Construccion' AND Activo=1";
//echo "$query1 <br>" ;

$resultado1= mysqli_query($conexion, $query1);
$query2="INSERT INTO general_curvas (`Proyecto`, `fInicioProyecto`, `fFinProyecto`, `Semana`, `Fecha_Inicio_Sem`, `Fecha_Fin_Sem`,  `diasCompletadosReal`, `diasCompletadosTeorico`, `diasTotales`, `porcentajeCompletadoReal`, `porcentajeCompletadoTeorico`)";
while ($data1=mysqli_fetch_assoc($resultado1)){
    $Proyecto=$data1["Proyecto_Proceso"];
    //echo "<li> $Proyecto";
    $Base_de_Datos=$data1["Base_de_Datos"];

    $querySemanasProyecto="SELECT CEIL(((DATEDIFF((SELECT MAX(Fecha_Fin) FROM $Base_de_Datos"."_programa_consolidado WHERE Semana = (SELECT MAX(Semana) FROM $Base_de_Datos"."_semanas_activas)), MIN(Fecha_Inicio))+1)/7)) AS semanasProyecto FROM $Base_de_Datos"."_programa_consolidado";
    $resultadoSemanasProyecto= mysqli_query($conexion, $querySemanasProyecto);
    $dataSemanasProyecto=mysqli_fetch_assoc($resultadoSemanasProyecto);
    $semanasProyecto = $dataSemanasProyecto["semanasProyecto"];

    $query3="SELECT Semana, Fecha_Inicio_Sem, Fecha_Fin_Sem  FROM $Base_de_Datos"."_semanas_activas";
    $resultado3= mysqli_query($conexion, $query3);
    while ($data3=mysqli_fetch_assoc($resultado3)){
      $Semana=$data3["Semana"];
      $Fecha_Inicio_Sem=$data3["Fecha_Inicio_Sem"];
      $Fecha_Fin_Sem=$data3["Fecha_Fin_Sem"];
      // echo "<li>" . $Proyecto . ", " . $Semana . ", " . $Fecha_Inicio_Sem  . ", " . $Fecha_Fin_Sem  . " (real)";

      $query2 .= " SELECT '$Proyecto' AS Proyecto, MIN(Fecha_Inicio) AS fInicioProyecto, MAX(Fecha_Fin) AS fFinProyecto, Semana, Fecha_Inicio_Sem, Fecha_Fin_Sem, SUM(diasCompletadosReal) AS diasCompletadosReal, SUM(diasCompletadosTeorico) AS diasCompletadosTeorico, diasTotales, (SUM(diasCompletadosReal)/diasTotales) AS porcentajeCompletadoReal, (SUM(diasCompletadosTeorico)/diasTotales) AS porcentajeCompletadoTeorico FROM (SELECT $Base_de_Datos"."_programa_consolidado.`Consecutivo`, $Base_de_Datos"."_programa_consolidado.`Semana`, $Base_de_Datos"."_programa_consolidado.`Consecutivo_en_Programa`, $Base_de_Datos"."_programa_consolidado.`Id`, $Base_de_Datos"."_programa_consolidado.`Actividad`, $Base_de_Datos"."_programa_consolidado.`Fecha_Inicio` AS Fecha_Inicio, $Base_de_Datos"."_programa_consolidado.`Fecha_Fin` AS Fecha_Fin, $Base_de_Datos"."_programa_consolidado.`Ejecutado`, $Base_de_Datos"."_semanas_activas.`Fecha_Inicio_Sem` AS Fecha_Inicio_Sem, $Base_de_Datos"."_semanas_activas.`Fecha_Fin_Sem` AS Fecha_Fin_Sem,";

      $query2 .="(SELECT
      CASE
      WHEN (DATEDIFF($Base_de_Datos"."_semanas_activas.`Fecha_Inicio_Sem`, $Base_de_Datos"."_programa_consolidado.`Fecha_Inicio`)+1) <= 0 THEN 0
      WHEN (DATEDIFF($Base_de_Datos"."_semanas_activas.`Fecha_Inicio_Sem`, $Base_de_Datos"."_programa_consolidado.`Fecha_Inicio`)+1) >= (DATEDIFF($Base_de_Datos"."_programa_consolidado.`Fecha_Fin`, $Base_de_Datos"."_programa_consolidado.`Fecha_Inicio`)+1) THEN (DATEDIFF($Base_de_Datos"."_programa_consolidado.`Fecha_Fin`, $Base_de_Datos"."_programa_consolidado.`Fecha_Inicio`)+1)
      ELSE (DATEDIFF($Base_de_Datos"."_semanas_activas.`Fecha_Inicio_Sem`, $Base_de_Datos"."_programa_consolidado.`Fecha_Inicio`)+1)
      END) AS diasCompletadosTeorico,
      (DATEDIFF($Base_de_Datos"."_programa_consolidado.`Fecha_Fin`, $Base_de_Datos"."_programa_consolidado.`Fecha_Inicio`)+1) * $Base_de_Datos"."_programa_consolidado.`Ejecutado` AS diasCompletadosReal,
      (DATEDIFF($Base_de_Datos"."_programa_consolidado.`Fecha_Fin`, $Base_de_Datos"."_programa_consolidado.`Fecha_Inicio`)+1) AS diasTotalesActividad, (SELECT SUM(DATEDIFF($Base_de_Datos"."_programa_consolidado.`Fecha_Fin`, $Base_de_Datos"."_programa_consolidado.`Fecha_Inicio`)+1) FROM $Base_de_Datos"."_programa_consolidado WHERE $Base_de_Datos"."_programa_consolidado.`Semana`=$Semana AND $Base_de_Datos"."_programa_consolidado.`Titulo`=0) AS diasTotales
      FROM $Base_de_Datos"."_programa_consolidado
      LEFT JOIN $Base_de_Datos"."_semanas_activas
      ON $Base_de_Datos"."_programa_consolidado.`Semana` = $Base_de_Datos"."_semanas_activas.`Semana`
      WHERE $Base_de_Datos"."_programa_consolidado.`Titulo`=0 AND $Base_de_Datos"."_programa_consolidado.`Fecha_Inicio` IS NOT NULL AND $Base_de_Datos"."_programa_consolidado.`Fecha_Fin` IS NOT NULL AND $Base_de_Datos"."_programa_consolidado.`Semana`=$Semana) AS tabla GROUP BY tabla.`Semana` UNION ";
    }

    for ($i=($Semana+1); $i < ($semanasProyecto+1); $i++) {
      $Fecha_Inicio_Sem = date("Y-m-d",strtotime($Fecha_Fin_Sem . "+ 1 days"));
      $Fecha_Fin_Sem = date("Y-m-d",strtotime($Fecha_Inicio_Sem . "+ 6 days"));
      // echo "<li>" . $Proyecto . ", " . $i  . ", " . $Fecha_Inicio_Sem  . ", " . $Fecha_Fin_Sem  . " (proyectada)";

      $query2 .= " SELECT '$Proyecto' AS Proyecto, MIN(Fecha_Inicio) AS fInicioProyecto, MAX(Fecha_Fin) AS fFinProyecto, $i AS Semana, '$Fecha_Inicio_Sem' AS Fecha_Inicio_Sem, '$Fecha_Fin_Sem' AS Fecha_Fin_Sem, SUM(diasCompletadosReal) AS diasCompletadosReal, SUM(diasCompletadosTeorico) AS diasCompletadosTeorico, diasTotales, (SUM(diasCompletadosReal)/diasTotales) AS porcentajeCompletadoReal, (SUM(diasCompletadosTeorico)/diasTotales) AS porcentajeCompletadoTeorico FROM (SELECT $Base_de_Datos"."_programa_consolidado.`Consecutivo`, $Base_de_Datos"."_programa_consolidado.`Semana`, $Base_de_Datos"."_programa_consolidado.`Consecutivo_en_Programa`, $Base_de_Datos"."_programa_consolidado.`Id`, $Base_de_Datos"."_programa_consolidado.`Actividad`, $Base_de_Datos"."_programa_consolidado.`Fecha_Inicio` AS Fecha_Inicio, $Base_de_Datos"."_programa_consolidado.`Fecha_Fin` AS Fecha_Fin, $Base_de_Datos"."_programa_consolidado.`Ejecutado`, $Base_de_Datos"."_semanas_activas.`Fecha_Inicio_Sem` AS Fecha_Inicio_Sem, $Base_de_Datos"."_semanas_activas.`Fecha_Fin_Sem` AS Fecha_Fin_Sem,";

      $query2 .="(SELECT
      CASE
      WHEN (DATEDIFF('$Fecha_Inicio_Sem', $Base_de_Datos"."_programa_consolidado.`Fecha_Inicio`)+1) <= 0 THEN 0
      WHEN (DATEDIFF('$Fecha_Inicio_Sem', $Base_de_Datos"."_programa_consolidado.`Fecha_Inicio`)+1) >= (DATEDIFF($Base_de_Datos"."_programa_consolidado.`Fecha_Fin`, $Base_de_Datos"."_programa_consolidado.`Fecha_Inicio`)+1) THEN (DATEDIFF($Base_de_Datos"."_programa_consolidado.`Fecha_Fin`, $Base_de_Datos"."_programa_consolidado.`Fecha_Inicio`)+1)
      ELSE (DATEDIFF('$Fecha_Inicio_Sem', $Base_de_Datos"."_programa_consolidado.`Fecha_Inicio`)+1)
      END) AS diasCompletadosTeorico,

      NULL AS diasCompletadosReal,
      (DATEDIFF($Base_de_Datos"."_programa_consolidado.`Fecha_Fin`, $Base_de_Datos"."_programa_consolidado.`Fecha_Inicio`)+1) AS diasTotalesActividad, (SELECT SUM(DATEDIFF($Base_de_Datos"."_programa_consolidado.`Fecha_Fin`, $Base_de_Datos"."_programa_consolidado.`Fecha_Inicio`)+1) FROM $Base_de_Datos"."_programa_consolidado WHERE $Base_de_Datos"."_programa_consolidado.`Semana`=$Semana AND $Base_de_Datos"."_programa_consolidado.`Titulo`=0) AS diasTotales
      FROM $Base_de_Datos"."_programa_consolidado
      LEFT JOIN $Base_de_Datos"."_semanas_activas
      ON $Base_de_Datos"."_programa_consolidado.`Semana` = $Base_de_Datos"."_semanas_activas.`Semana`
      WHERE $Base_de_Datos"."_programa_consolidado.`Titulo`=0 AND $Base_de_Datos"."_programa_consolidado.`Fecha_Inicio` IS NOT NULL AND $Base_de_Datos"."_programa_consolidado.`Fecha_Fin` IS NOT NULL AND $Base_de_Datos"."_programa_consolidado.`Semana`=$Semana) AS tabla GROUP BY tabla.`Semana` UNION ";

    }
// //GROUP BY tabla.`Semana`
}
//
$query2 = substr($query2, 0, -7);
// echo "<li> $query2";
$resultado2= mysqli_query($conexion, $query2);
if(!$resultado2){
    die(mysqli_error($conexion));
} else{
  sleep(1);
  $query3 = "SELECT * FROM general_curvas";
  $resultado3 = mysqli_query($conexion, $query3);

  if(!$resultado3){
      die("Error");
  } else{
    $porcentajeCompletadoTeoricoAnterior=0;
    $ProyectoAnterior = null;
    $query4 = "";
    // $anterior_100 = 0;
    while($data4=mysqli_fetch_assoc($resultado3)){
      $semana = $data4["semana"];
      if(!$ProyectoAnterior){
      }else{
        if($data4["Proyecto"] != $ProyectoAnterior){
          $porcentajeCompletadoTeoricoAnterior=0;
        }
      }

      $porcentajeCompletadoTeorico = $data4["porcentajeCompletadoTeorico"];

      if($porcentajeCompletadoTeorico == 1 && $anterior_100 == 1){
        $data4["porcentajeCompletadoTeorico"] = "NULL";
      }

      if($porcentajeCompletadoTeorico >= 1){
        $anterior_100 = 1;
      }else{
        $anterior_100 = 0;
      }

      $ProyectoAnterior = $data4["Proyecto"];

      $diferenciaPorcentajeCompletadoTeorico = ($porcentajeCompletadoTeorico - $porcentajeCompletadoTeoricoAnterior);
      $porcentajeCompletadoTeoricoAnterior = $porcentajeCompletadoTeorico;

      $query4 .= "UPDATE general_curvas SET diferenciaPorcentajeCompletadoTeorico = $diferenciaPorcentajeCompletadoTeorico WHERE semana = $semana AND Proyecto = '$ProyectoAnterior'; ";
    }
    $resultado4 = mysqli_multi_query($conexion, $query4);

    mysqli_free_result($resultado1);
    mysqli_free_result($resultado2);
    mysqli_free_result($resultado3);
    if(!$resultado4){
        die("Error");
    } else{
      echo "<li>Curva S - OK";
      mysqli_free_result($resultado4);

      // Comienza La Curva S del PDC
      sleep(5);
      require ("conexion.php");


      $query="TRUNCATE TABLE general_curvas_pdc_apr";
      //echo "$query <br>" ;

      $resultado= mysqli_query($conexion, $query);

      $query1="SELECT  * FROM general_proyectos_procesos WHERE Area='Construccion' AND Activo=1 AND pdcActivo=1";
      //echo "$query1 <br>" ;

      $resultado1= mysqli_query($conexion, $query1);
      $num_registros_resultado1 = $resultado1->num_rows;
      //echo "$num_registros_resultado1 <br>" ;
      sleep(2);

      if($num_registros_resultado1==0){
        echo "<li>Curva S PDC APR - No hay proyectos";
      } else{
        $query2="INSERT INTO general_curvas_pdc_apr (`Proyecto`, `semana`, `maxSemana`, `Fecha_Inicio_Sem`, `Fecha_Fin_Sem`, `diasCompletadosReal`, `diasCompletadosTeorico`, `diasTotales`, `porcentajeCompletadoReal`, `porcentajeCompletadoTeorico`)";
        while ($data1=mysqli_fetch_assoc($resultado1)){
            $Proyecto=$data1["Proyecto_Proceso"];
            //echo "<li> $Proyecto";
            $Base_de_Datos=$data1["Base_de_Datos"];

            $querySemanasProyecto="SELECT CEIL(((DATEDIFF((SELECT MAX(Fecha_Fin) FROM $Base_de_Datos"."_programa_consolidado WHERE Semana = (SELECT MAX(Semana) FROM $Base_de_Datos"."_semanas_activas)), MIN(Fecha_Inicio))+1)/7)) AS semanasProyecto FROM $Base_de_Datos"."_programa_consolidado";
            $resultadoSemanasProyecto= mysqli_query($conexion, $querySemanasProyecto);
            $dataSemanasProyecto=mysqli_fetch_assoc($resultadoSemanasProyecto);
            $semanasProyecto = $dataSemanasProyecto["semanasProyecto"];

            $query3="SELECT Semana, Fecha_Inicio_Sem, Fecha_Fin_Sem  FROM $Base_de_Datos"."_semanas_activas";
            $resultado3= mysqli_query($conexion, $query3);
            while ($data3=mysqli_fetch_assoc($resultado3)){
              $Semana=$data3["Semana"];
              $Fecha_Inicio_Sem=$data3["Fecha_Inicio_Sem"];
              $Fecha_Fin_Sem=$data3["Fecha_Fin_Sem"];
              // echo "<li>" . $Proyecto . ", " . $Semana . ", " . $Fecha_Inicio_Sem  . ", " . $Fecha_Fin_Sem  . " (real)";

              $query2 .= " SELECT '$Proyecto' AS Proyecto, Semana, (SELECT MAX(`semana`) FROM $Base_de_Datos" . "_pdc) AS maxSemana,  Fecha_Inicio_Sem, Fecha_Fin_Sem, SUM(diasCompletadosReal) AS diasCompletadosReal, SUM(diasCompletadosTeorico) AS diasCompletadosTeorico, diasTotales, (SUM(diasCompletadosReal)/diasTotales) AS porcentajeCompletadoReal, (SUM(diasCompletadosTeorico)/diasTotales) AS porcentajeCompletadoTeorico FROM (SELECT $Base_de_Datos"."_pdc.`consecutivo`, $Base_de_Datos"."_pdc.`semana`, $Base_de_Datos"."_pdc.`paqueteContratacion`, $Base_de_Datos"."_pdc.`fechaElaboracionPliegos` AS Fecha_Inicio, $Base_de_Datos"."_pdc.`fechaFabricacion` AS Fecha_Fin, ((SELECT CASE WHEN $Base_de_Datos"."_pdc.`fechaRealInicio` IS NOT NULL THEN 7 WHEN $Base_de_Datos"."_pdc.`fechaRealInsumosObra` IS NOT NULL THEN 7 WHEN $Base_de_Datos"."_pdc.`fechaRealFabricacion` IS NOT NULL THEN 7 WHEN $Base_de_Datos"."_pdc.`fechaRealLegalizacionContrato` IS NOT NULL THEN 6 WHEN $Base_de_Datos"."_pdc.`fechaRealCuadrosComparativos` IS NOT NULL THEN 5 WHEN $Base_de_Datos"."_pdc.`fechaRealReciboPropuestas` IS NOT NULL THEN 4 WHEN $Base_de_Datos"."_pdc.`fechaRealEntregaPliegos` IS NOT NULL THEN 3 WHEN $Base_de_Datos"."_pdc.`fechaRealIngresoLicify` IS NOT NULL THEN 2 WHEN $Base_de_Datos"."_pdc.`fechaRealELaboracionPliegos` IS NOT NULL THEN 1 ELSE 0 END)/7) AS Ejecutado, $Base_de_Datos"."_semanas_activas.`Fecha_Inicio_Sem` AS Fecha_Inicio_Sem, $Base_de_Datos"."_semanas_activas.`Fecha_Fin_Sem` AS Fecha_Fin_Sem,";

              $query2 .="(DATEDIFF($Base_de_Datos"."_pdc.`fechaFabricacion`, $Base_de_Datos"."_pdc.`fechaElaboracionPliegos`)+1) * ((SELECT CASE WHEN $Base_de_Datos"."_pdc.`fechaFabricacion` <= '$Fecha_Inicio_Sem' THEN 7 WHEN $Base_de_Datos"."_pdc.`fechaInsumosObra` <= '$Fecha_Inicio_Sem' THEN 7 WHEN $Base_de_Datos"."_pdc.`fechaFabricacion` <= '$Fecha_Inicio_Sem' THEN 7 WHEN $Base_de_Datos"."_pdc.`fechaLegalizacionContrato` <= '$Fecha_Inicio_Sem' THEN 6 WHEN $Base_de_Datos"."_pdc.`fechaCuadrosComparativos` <= '$Fecha_Inicio_Sem' THEN 5 WHEN $Base_de_Datos"."_pdc.`fechaReciboPropuestas` <= '$Fecha_Inicio_Sem' THEN 4 WHEN $Base_de_Datos"."_pdc.`fechaEntregaPliegos` <= '$Fecha_Inicio_Sem' THEN 3 WHEN $Base_de_Datos"."_pdc.`fechaIngresoLicify` <= '$Fecha_Inicio_Sem' THEN 2 WHEN $Base_de_Datos"."_pdc.`fechaELaboracionPliegos` <= '$Fecha_Inicio_Sem' THEN 1 ELSE 0 END)/7) AS diasCompletadosTeorico,";

              $query2 .="(DATEDIFF($Base_de_Datos"."_pdc.`fechaFabricacion`, $Base_de_Datos"."_pdc.`fechaElaboracionPliegos`)+1) * ((SELECT CASE WHEN $Base_de_Datos"."_pdc.`fechaRealInicio` IS NOT NULL THEN 7 WHEN $Base_de_Datos"."_pdc.`fechaRealInsumosObra` IS NOT NULL THEN 7 WHEN $Base_de_Datos"."_pdc.`fechaRealFabricacion` IS NOT NULL THEN 7 WHEN $Base_de_Datos"."_pdc.`fechaRealLegalizacionContrato` IS NOT NULL THEN 6 WHEN $Base_de_Datos"."_pdc.`fechaRealCuadrosComparativos` IS NOT NULL THEN 5 WHEN $Base_de_Datos"."_pdc.`fechaRealReciboPropuestas` IS NOT NULL THEN 4 WHEN $Base_de_Datos"."_pdc.`fechaRealEntregaPliegos` IS NOT NULL THEN 3 WHEN $Base_de_Datos"."_pdc.`fechaRealIngresoLicify` IS NOT NULL THEN 2 WHEN $Base_de_Datos"."_pdc.`fechaRealELaboracionPliegos` IS NOT NULL THEN 1 ELSE 0 END)/7) AS diasCompletadosReal,";

              $query2 .="(DATEDIFF($Base_de_Datos"."_pdc.`fechaFabricacion`, $Base_de_Datos"."_pdc.`fechaElaboracionPliegos`)+1) AS diasTotalesActividad,";

              $query2 .="(SELECT SUM(DATEDIFF($Base_de_Datos"."_pdc.`fechaFabricacion`, $Base_de_Datos"."_pdc.`fechaElaboracionPliegos`)+1) FROM $Base_de_Datos"."_pdc WHERE $Base_de_Datos"."_pdc.`semana`=$Semana AND $Base_de_Datos"."_pdc.`titulo`=0) AS diasTotales

              FROM $Base_de_Datos"."_pdc
              LEFT JOIN $Base_de_Datos"."_semanas_activas
              ON $Base_de_Datos"."_pdc.`semana` = $Base_de_Datos"."_semanas_activas.`Semana`
              WHERE $Base_de_Datos"."_pdc.`titulo`=0 AND $Base_de_Datos"."_pdc.`fechaElaboracionPliegos` IS NOT NULL AND $Base_de_Datos"."_pdc.`fechaFabricacion` IS NOT NULL AND $Base_de_Datos"."_pdc.`semana`=$Semana) AS tabla GROUP BY tabla.`Semana` UNION ";
            }
            
            for ($i=($Semana+1); $i < ($semanasProyecto+1); $i++) {
              $Fecha_Inicio_Sem = date("Y-m-d",strtotime($Fecha_Fin_Sem . "+ 1 days"));
              $Fecha_Fin_Sem = date("Y-m-d",strtotime($Fecha_Inicio_Sem . "+ 6 days"));
              // echo "<li>" . $Proyecto . ", " . $i  . ", " . $Fecha_Inicio_Sem  . ", " . $Fecha_Fin_Sem  . " (proyectada)";

              $query2 .= " SELECT '$Proyecto' AS Proyecto, $i AS Semana, (SELECT MAX(`semana`) FROM $Base_de_Datos" . "_pdc) AS maxSemana,  '$Fecha_Inicio_Sem' AS Fecha_Inicio_Sem, '$Fecha_Fin_Sem' AS Fecha_Fin_Sem, SUM(diasCompletadosReal) AS diasCompletadosReal, SUM(diasCompletadosTeorico) AS diasCompletadosTeorico, diasTotales, (SUM(diasCompletadosReal)/diasTotales) AS porcentajeCompletadoReal, (SUM(diasCompletadosTeorico)/diasTotales) AS porcentajeCompletadoTeorico FROM (SELECT $Base_de_Datos"."_pdc.`consecutivo`, $Base_de_Datos"."_pdc.`semana`, $Base_de_Datos"."_pdc.`paqueteContratacion`, $Base_de_Datos"."_pdc.`fechaElaboracionPliegos` AS Fecha_Inicio, $Base_de_Datos"."_pdc.`fechaFabricacion` AS Fecha_Fin, ((SELECT CASE WHEN $Base_de_Datos"."_pdc.`fechaRealInicio` IS NOT NULL THEN 7 WHEN $Base_de_Datos"."_pdc.`fechaRealInsumosObra` IS NOT NULL THEN 7 WHEN $Base_de_Datos"."_pdc.`fechaRealFabricacion` IS NOT NULL THEN 7 WHEN $Base_de_Datos"."_pdc.`fechaRealLegalizacionContrato` IS NOT NULL THEN 6 WHEN $Base_de_Datos"."_pdc.`fechaRealCuadrosComparativos` IS NOT NULL THEN 5 WHEN $Base_de_Datos"."_pdc.`fechaRealReciboPropuestas` IS NOT NULL THEN 4 WHEN $Base_de_Datos"."_pdc.`fechaRealEntregaPliegos` IS NOT NULL THEN 3 WHEN $Base_de_Datos"."_pdc.`fechaRealIngresoLicify` IS NOT NULL THEN 2 WHEN $Base_de_Datos"."_pdc.`fechaRealELaboracionPliegos` IS NOT NULL THEN 1 ELSE 0 END)/7) AS Ejecutado, $Base_de_Datos"."_semanas_activas.`Fecha_Inicio_Sem` AS Fecha_Inicio_Sem, $Base_de_Datos"."_semanas_activas.`Fecha_Fin_Sem` AS Fecha_Fin_Sem,";

              $query2 .="(DATEDIFF($Base_de_Datos"."_pdc.`fechaFabricacion`, $Base_de_Datos"."_pdc.`fechaElaboracionPliegos`)+1) * ((SELECT CASE WHEN $Base_de_Datos"."_pdc.`fechaFabricacion` <= '$Fecha_Inicio_Sem' THEN 7 WHEN $Base_de_Datos"."_pdc.`fechaInsumosObra` <= '$Fecha_Inicio_Sem' THEN 7 WHEN $Base_de_Datos"."_pdc.`fechaFabricacion` <= '$Fecha_Inicio_Sem' THEN 7 WHEN $Base_de_Datos"."_pdc.`fechaLegalizacionContrato` <= '$Fecha_Inicio_Sem' THEN 6 WHEN $Base_de_Datos"."_pdc.`fechaCuadrosComparativos` <= '$Fecha_Inicio_Sem' THEN 5 WHEN $Base_de_Datos"."_pdc.`fechaReciboPropuestas` <= '$Fecha_Inicio_Sem' THEN 4 WHEN $Base_de_Datos"."_pdc.`fechaEntregaPliegos` <= '$Fecha_Inicio_Sem' THEN 3 WHEN $Base_de_Datos"."_pdc.`fechaIngresoLicify` <= '$Fecha_Inicio_Sem' THEN 2 WHEN $Base_de_Datos"."_pdc.`fechaELaboracionPliegos` <= '$Fecha_Inicio_Sem' THEN 1 ELSE 0 END)/7) AS diasCompletadosTeorico,";

              $query2 .="NULL AS diasCompletadosReal,";

              $query2 .="(DATEDIFF($Base_de_Datos"."_pdc.`fechaFabricacion`, $Base_de_Datos"."_pdc.`fechaElaboracionPliegos`)+1) AS diasTotalesActividad,";

              $query2 .="(SELECT SUM(DATEDIFF($Base_de_Datos"."_pdc.`fechaFabricacion`, $Base_de_Datos"."_pdc.`fechaElaboracionPliegos`)+1) FROM $Base_de_Datos"."_pdc WHERE $Base_de_Datos"."_pdc.`semana`=$Semana AND $Base_de_Datos"."_pdc.`titulo`=0) AS diasTotales

              FROM $Base_de_Datos"."_pdc
              LEFT JOIN $Base_de_Datos"."_semanas_activas
              ON $Base_de_Datos"."_pdc.`semana` = $Base_de_Datos"."_semanas_activas.`Semana`
              WHERE $Base_de_Datos"."_pdc.`titulo`=0 AND $Base_de_Datos"."_pdc.`fechaElaboracionPliegos` IS NOT NULL AND $Base_de_Datos"."_pdc.`fechaFabricacion` IS NOT NULL AND $Base_de_Datos"."_pdc.`semana`=$Semana) AS tabla GROUP BY tabla.`Semana` UNION ";
              //echo "<li> $query2";
            }
        // //GROUP BY tabla.`Semana`
        }
        //
        $query2 = substr($query2, 0, -7);
        //echo "<li> $query2";
        $resultado2= mysqli_query($conexion, $query2);
        if(!$resultado2){
          die(mysqli_error($conexion));
        } else{
          sleep(3);
          
          $query3 = "SELECT tablaPDC.`id`, tablaPDC.`Proyecto`, tablaPDC.`semana`, tablaPDC.`maxSemana`, tablaPDC.`Condicion`, tablaPDC.`Fecha_inicio_Sem`, tablaPDC.`Fecha_Fin_Sem`, tablaPDC.`diasCompletadosReal`, tablaPDC.`diasCompletadosTeorico`, tablaPDC.`diasTotales`, tablaPDC.`porcentajeCompletadoReal`, tablaPDC.`porcentajeCompletadoTeorico`, tablaGeneral.`diasCompletadosRealGeneral`, tablaGeneral.`diasCompletadosTeoricoGeneral`, tablaGeneral.`diasTotalesGeneral`, tablaGeneral.`porcentajeCompletadoRealGeneral`, tablaGeneral.`porcentajeCompletadoTeoricoGeneral` FROM (SELECT `id`, `Proyecto`, `semana`, `maxSemana`, CONCAT(`Proyecto`, '-', `semana`) AS Condicion, `Fecha_Inicio_Sem`, `Fecha_Fin_Sem`, `diasCompletadosReal`, `diasCompletadosTeorico`, `diasTotales`, `porcentajeCompletadoReal`, `porcentajeCompletadoTeorico` FROM general_curvas_pdc_apr) AS tablaPDC LEFT JOIN (SELECT `id` AS idGeneral, `Proyecto` AS ProyectoGeneral, `semana` AS semanaGeneral, 0 AS maxSemanaGeneral, CONCAT(`Proyecto`, '-', `semana`) AS CondicionGeneral, `Fecha_Inicio_Sem` AS Fecha_Inicio_SemGeneral, `Fecha_Fin_Sem` AS Fecha_Fin_SemGeneral, `diasCompletadosReal` AS diasCompletadosRealGeneral, `diasCompletadosTeorico` AS diasCompletadosTeoricoGeneral, `diasTotales` AS diasTotalesGeneral, `porcentajeCompletadoReal` AS porcentajeCompletadoRealGeneral, `porcentajeCompletadoTeorico` AS porcentajeCompletadoTeoricoGeneral FROM general_curvas) AS tablaGeneral ON tablaPDC.`Condicion` = tablaGeneral.`CondicionGeneral`";

          $resultado3 = mysqli_query($conexion, $query3);
          
          sleep(3);

          if(!$resultado3){
              die(mysqli_error($conexion));
          } else{
            $porcentajeCompletadoTeoricoAnterior=0;
            $porcentajeCompletadoTeoricoGeneralAnterior=0;
            $ProyectoAnterior = null;
            $anterior_100 = 0;
            $anterior_100_General = 0;
            $query4 = "";
              while($data4=mysqli_fetch_assoc($resultado3)){
                $semana = $data4["semana"];
                if(!$ProyectoAnterior){
                }else{
                  if($data4["Proyecto"] != $ProyectoAnterior){
                    $porcentajeCompletadoTeoricoAnterior=0;
                    $porcentajeCompletadoTeoricoGeneralAnterior=0;
                  }
                }

                $porcentajeCompletadoTeorico = $data4["porcentajeCompletadoTeorico"];
                $porcentajeCompletadoTeoricoGeneral = $data4["porcentajeCompletadoTeoricoGeneral"];
                $porcentajeCompletadoRealGeneral = $data4["porcentajeCompletadoRealGeneral"];

                if($porcentajeCompletadoTeorico == 1 && $anterior_100 == 1){
                  $data4["porcentajeCompletadoTeorico"] = "NULL";
                }

                if($porcentajeCompletadoTeorico >= 1){
                  $anterior_100 = 1;
                }else{
                  $anterior_100 = 0;
                }

                if($porcentajeCompletadoTeoricoGeneral == 1 && $anterior_100_General == 1){
                  $data4["porcentajeCompletadoTeoricoGeneral"] = "NULL";
                }

                if($porcentajeCompletadoTeoricoGeneral >= 1){
                  $anterior_100_General = 1;
                }else{
                  $anterior_100_General = 0;
                }

                $ProyectoAnterior = $data4["Proyecto"];

                $diferenciaPComplTeorico = round(($porcentajeCompletadoTeorico - $porcentajeCompletadoTeoricoAnterior),4);
                $porcentajeCompletadoTeoricoAnterior = $porcentajeCompletadoTeorico;

                $diferenciaPComplTeoricoGeneral = round(($porcentajeCompletadoTeoricoGeneral - $porcentajeCompletadoTeoricoGeneralAnterior),4);
                $porcentajeCompletadoTeoricoGeneralAnterior = $porcentajeCompletadoTeoricoGeneral;

                //echo "<li>porcentajeCompletadoTeorico = $porcentajeCompletadoTeorico, porcentajeCompletadoTeoricoGeneral = $porcentajeCompletadoTeoricoGeneral, porcentajeCompletadoRealGeneral = $porcentajeCompletadoRealGeneral";

              $query4 .= "UPDATE general_curvas_pdc_apr SET `porcentajeCompletadoTeoricoGeneral` = $porcentajeCompletadoTeoricoGeneral, `porcentajeCompletadoRealGeneral` = $porcentajeCompletadoRealGeneral, `diferenciaPorcentajeCompletadoTeorico` = $diferenciaPComplTeorico, `diferenciaPorcentajeCompletadoTeoricoGeneral` = $diferenciaPComplTeoricoGeneral WHERE `semana` = $semana AND `Proyecto` = '$ProyectoAnterior'; ";
            }
            require ("conexion.php");
            $resultado4 = mysqli_multi_query($conexion, $query4);

            mysqli_free_result($resultado1);
            mysqli_free_result($resultado2);
            mysqli_free_result($resultado3);

            sleep(3);

            if(!$resultado4){
                die(mysqli_error($conexion));
            } else{
              echo "<li>Curva S PDC APR - OK";
              mysqli_free_result($resultado4);
            }
          }
        }
      }
    }
  }
}
mysqli_close($conexion);

?>
