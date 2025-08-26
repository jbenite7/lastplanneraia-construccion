<?php
 session_start();
require ("conexion.php");


$query="TRUNCATE TABLE general_curvas_pdc";
//echo "$query <br>" ;

$resultado= mysqli_query($conexion, $query);

$query1="SELECT  * FROM general_proyectos_procesos WHERE Area='Construccion' AND Activo=1 AND pdcActivo=1";
//echo "$query1 <br>" ;

$resultado1= mysqli_query($conexion, $query1);

sleep(2);

$query2="INSERT INTO general_curvas_pdc (`Proyecto`, `Semana`, `Fecha_Inicio_Sem`, `Fecha_Fin_Sem`, `diasCompletadosReal`, `diasCompletadosTeorico`, `diasTotales`, `porcentajeCompletadoReal`, `porcentajeCompletadoTeorico`)";
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

      $query2 .= " SELECT '$Proyecto' AS Proyecto, Semana, Fecha_Inicio_Sem, Fecha_Fin_Sem, SUM(diasCompletadosReal) AS diasCompletadosReal, SUM(diasCompletadosTeorico) AS diasCompletadosTeorico, diasTotales, (SUM(diasCompletadosReal)/diasTotales) AS porcentajeCompletadoReal, (SUM(diasCompletadosTeorico)/diasTotales) AS porcentajeCompletadoTeorico FROM (SELECT $Base_de_Datos"."_pdc.`consecutivo`, $Base_de_Datos"."_pdc.`semana`, $Base_de_Datos"."_pdc.`paqueteContratacion`, $Base_de_Datos"."_pdc.`fechaElaboracionPliegos` AS Fecha_Inicio, $Base_de_Datos"."_pdc.`fechaInicio` AS Fecha_Fin, ((SELECT CASE WHEN $Base_de_Datos"."_pdc.`fechaRealInicio` IS NOT NULL THEN 9 WHEN $Base_de_Datos"."_pdc.`fechaRealInsumosObra` IS NOT NULL THEN 8 WHEN $Base_de_Datos"."_pdc.`fechaRealFabricacion` IS NOT NULL THEN 7 WHEN $Base_de_Datos"."_pdc.`fechaRealLegalizacionContrato` IS NOT NULL THEN 6 WHEN $Base_de_Datos"."_pdc.`fechaRealCuadrosComparativos` IS NOT NULL THEN 5 WHEN $Base_de_Datos"."_pdc.`fechaRealReciboPropuestas` IS NOT NULL THEN 4 WHEN $Base_de_Datos"."_pdc.`fechaRealEntregaPliegos` IS NOT NULL THEN 3 WHEN $Base_de_Datos"."_pdc.`fechaRealIngresoLicify` IS NOT NULL THEN 2 WHEN $Base_de_Datos"."_pdc.`fechaRealELaboracionPliegos` IS NOT NULL THEN 1 ELSE 0 END)/9) AS Ejecutado, $Base_de_Datos"."_semanas_activas.`Fecha_Inicio_Sem` AS Fecha_Inicio_Sem, $Base_de_Datos"."_semanas_activas.`Fecha_Fin_Sem` AS Fecha_Fin_Sem,";

      $query2 .="(DATEDIFF($Base_de_Datos"."_pdc.`fechaInicio`, $Base_de_Datos"."_pdc.`fechaElaboracionPliegos`)+1) * ((SELECT CASE WHEN $Base_de_Datos"."_pdc.`fechaInicio` <= '$Fecha_Inicio_Sem' THEN 9 WHEN $Base_de_Datos"."_pdc.`fechaInsumosObra` <= '$Fecha_Inicio_Sem' THEN 8 WHEN $Base_de_Datos"."_pdc.`fechaFabricacion` <= '$Fecha_Inicio_Sem' THEN 7 WHEN $Base_de_Datos"."_pdc.`fechaLegalizacionContrato` <= '$Fecha_Inicio_Sem' THEN 6 WHEN $Base_de_Datos"."_pdc.`fechaCuadrosComparativos` <= '$Fecha_Inicio_Sem' THEN 5 WHEN $Base_de_Datos"."_pdc.`fechaReciboPropuestas` <= '$Fecha_Inicio_Sem' THEN 4 WHEN $Base_de_Datos"."_pdc.`fechaEntregaPliegos` <= '$Fecha_Inicio_Sem' THEN 3 WHEN $Base_de_Datos"."_pdc.`fechaIngresoLicify` <= '$Fecha_Inicio_Sem' THEN 2 WHEN $Base_de_Datos"."_pdc.`fechaELaboracionPliegos` <= '$Fecha_Inicio_Sem' THEN 1 ELSE 0 END)/9) AS diasCompletadosTeorico,";

      $query2 .="(DATEDIFF($Base_de_Datos"."_pdc.`fechaInicio`, $Base_de_Datos"."_pdc.`fechaElaboracionPliegos`)+1) * ((SELECT CASE WHEN $Base_de_Datos"."_pdc.`fechaRealInicio` IS NOT NULL THEN 9 WHEN $Base_de_Datos"."_pdc.`fechaRealInsumosObra` IS NOT NULL THEN 8 WHEN $Base_de_Datos"."_pdc.`fechaRealFabricacion` IS NOT NULL THEN 7 WHEN $Base_de_Datos"."_pdc.`fechaRealLegalizacionContrato` IS NOT NULL THEN 6 WHEN $Base_de_Datos"."_pdc.`fechaRealCuadrosComparativos` IS NOT NULL THEN 5 WHEN $Base_de_Datos"."_pdc.`fechaRealReciboPropuestas` IS NOT NULL THEN 4 WHEN $Base_de_Datos"."_pdc.`fechaRealEntregaPliegos` IS NOT NULL THEN 3 WHEN $Base_de_Datos"."_pdc.`fechaRealIngresoLicify` IS NOT NULL THEN 2 WHEN $Base_de_Datos"."_pdc.`fechaRealELaboracionPliegos` IS NOT NULL THEN 1 ELSE 0 END)/9) AS diasCompletadosReal,";

      $query2 .="(DATEDIFF($Base_de_Datos"."_pdc.`fechaInicio`, $Base_de_Datos"."_pdc.`fechaElaboracionPliegos`)+1) AS diasTotalesActividad,";

      $query2 .="(SELECT SUM(DATEDIFF($Base_de_Datos"."_pdc.`fechaInicio`, $Base_de_Datos"."_pdc.`fechaElaboracionPliegos`)+1) FROM $Base_de_Datos"."_pdc WHERE $Base_de_Datos"."_pdc.`semana`=$Semana AND $Base_de_Datos"."_pdc.`titulo`=0) AS diasTotales

      FROM $Base_de_Datos"."_pdc
      LEFT JOIN $Base_de_Datos"."_semanas_activas
      ON $Base_de_Datos"."_pdc.`semana` = $Base_de_Datos"."_semanas_activas.`Semana`
      WHERE $Base_de_Datos"."_pdc.`titulo`=0 AND $Base_de_Datos"."_pdc.`fechaElaboracionPliegos` IS NOT NULL AND $Base_de_Datos"."_pdc.`fechaInicio` IS NOT NULL AND $Base_de_Datos"."_pdc.`semana`=$Semana) AS tabla GROUP BY tabla.`Semana` UNION ";
    }

    for ($i=($Semana+1); $i < ($semanasProyecto+1); $i++) {
      $Fecha_Inicio_Sem = date("Y-m-d",strtotime($Fecha_Fin_Sem . "+ 1 days"));
      $Fecha_Fin_Sem = date("Y-m-d",strtotime($Fecha_Inicio_Sem . "+ 6 days"));
      // echo "<li>" . $Proyecto . ", " . $i  . ", " . $Fecha_Inicio_Sem  . ", " . $Fecha_Fin_Sem  . " (proyectada)";

      $query2 .= " SELECT '$Proyecto' AS Proyecto, $i AS Semana, '$Fecha_Inicio_Sem' AS Fecha_Inicio_Sem, '$Fecha_Fin_Sem' AS Fecha_Fin_Sem, SUM(diasCompletadosReal) AS diasCompletadosReal, SUM(diasCompletadosTeorico) AS diasCompletadosTeorico, diasTotales, (SUM(diasCompletadosReal)/diasTotales) AS porcentajeCompletadoReal, (SUM(diasCompletadosTeorico)/diasTotales) AS porcentajeCompletadoTeorico FROM (SELECT $Base_de_Datos"."_pdc.`consecutivo`, $Base_de_Datos"."_pdc.`semana`, $Base_de_Datos"."_pdc.`paqueteContratacion`, $Base_de_Datos"."_pdc.`fechaElaboracionPliegos` AS Fecha_Inicio, $Base_de_Datos"."_pdc.`fechaInicio` AS Fecha_Fin, ((SELECT CASE WHEN $Base_de_Datos"."_pdc.`fechaRealInicio` IS NOT NULL THEN 9 WHEN $Base_de_Datos"."_pdc.`fechaRealInsumosObra` IS NOT NULL THEN 8 WHEN $Base_de_Datos"."_pdc.`fechaRealFabricacion` IS NOT NULL THEN 7 WHEN $Base_de_Datos"."_pdc.`fechaRealLegalizacionContrato` IS NOT NULL THEN 6 WHEN $Base_de_Datos"."_pdc.`fechaRealCuadrosComparativos` IS NOT NULL THEN 5 WHEN $Base_de_Datos"."_pdc.`fechaRealReciboPropuestas` IS NOT NULL THEN 4 WHEN $Base_de_Datos"."_pdc.`fechaRealEntregaPliegos` IS NOT NULL THEN 3 WHEN $Base_de_Datos"."_pdc.`fechaRealIngresoLicify` IS NOT NULL THEN 2 WHEN $Base_de_Datos"."_pdc.`fechaRealELaboracionPliegos` IS NOT NULL THEN 1 ELSE 0 END)/9) AS Ejecutado, $Base_de_Datos"."_semanas_activas.`Fecha_Inicio_Sem` AS Fecha_Inicio_Sem, $Base_de_Datos"."_semanas_activas.`Fecha_Fin_Sem` AS Fecha_Fin_Sem,";

      $query2 .="(DATEDIFF($Base_de_Datos"."_pdc.`fechaInicio`, $Base_de_Datos"."_pdc.`fechaElaboracionPliegos`)+1) * ((SELECT CASE WHEN $Base_de_Datos"."_pdc.`fechaInicio` <= '$Fecha_Inicio_Sem' THEN 9 WHEN $Base_de_Datos"."_pdc.`fechaInsumosObra` <= '$Fecha_Inicio_Sem' THEN 8 WHEN $Base_de_Datos"."_pdc.`fechaFabricacion` <= '$Fecha_Inicio_Sem' THEN 7 WHEN $Base_de_Datos"."_pdc.`fechaLegalizacionContrato` <= '$Fecha_Inicio_Sem' THEN 6 WHEN $Base_de_Datos"."_pdc.`fechaCuadrosComparativos` <= '$Fecha_Inicio_Sem' THEN 5 WHEN $Base_de_Datos"."_pdc.`fechaReciboPropuestas` <= '$Fecha_Inicio_Sem' THEN 4 WHEN $Base_de_Datos"."_pdc.`fechaEntregaPliegos` <= '$Fecha_Inicio_Sem' THEN 3 WHEN $Base_de_Datos"."_pdc.`fechaIngresoLicify` <= '$Fecha_Inicio_Sem' THEN 2 WHEN $Base_de_Datos"."_pdc.`fechaELaboracionPliegos` <= '$Fecha_Inicio_Sem' THEN 1 ELSE 0 END)/9) AS diasCompletadosTeorico,";

      $query2 .="NULL AS diasCompletadosReal,";

      $query2 .="(DATEDIFF($Base_de_Datos"."_pdc.`fechaInicio`, $Base_de_Datos"."_pdc.`fechaElaboracionPliegos`)+1) AS diasTotalesActividad,";

      $query2 .="(SELECT SUM(DATEDIFF($Base_de_Datos"."_pdc.`fechaInicio`, $Base_de_Datos"."_pdc.`fechaElaboracionPliegos`)+1) FROM $Base_de_Datos"."_pdc WHERE $Base_de_Datos"."_pdc.`semana`=$Semana AND $Base_de_Datos"."_pdc.`titulo`=0) AS diasTotales

      FROM $Base_de_Datos"."_pdc
      LEFT JOIN $Base_de_Datos"."_semanas_activas
      ON $Base_de_Datos"."_pdc.`semana` = $Base_de_Datos"."_semanas_activas.`Semana`
      WHERE $Base_de_Datos"."_pdc.`titulo`=0 AND $Base_de_Datos"."_pdc.`fechaElaboracionPliegos` IS NOT NULL AND $Base_de_Datos"."_pdc.`fechaInicio` IS NOT NULL AND $Base_de_Datos"."_pdc.`semana`=$Semana) AS tabla GROUP BY tabla.`Semana` UNION ";

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
  echo "<li>Curva S PDC - OK";

  sleep(2);
  
  $query3 = "SELECT tablaPDC.`id`, tablaPDC.`Proyecto`, tablaPDC.`semana`, tablaPDC.`Condicion`, tablaPDC.`Fecha_inicio_Sem`, tablaPDC.`Fecha_Fin_Sem`, tablaPDC.`diasCompletadosReal`, tablaPDC.`diasCompletadosTeorico`, tablaPDC.`diasTotales`, tablaPDC.`porcentajeCompletadoReal`, tablaPDC.`porcentajeCompletadoTeorico`, tablaGeneral.`diasCompletadosRealGeneral`, tablaGeneral.`diasCompletadosTeoricoGeneral`, tablaGeneral.`diasTotalesGeneral`, tablaGeneral.`porcentajeCompletadoRealGeneral`, tablaGeneral.`porcentajeCompletadoTeoricoGeneral` FROM (SELECT `id`, `Proyecto`, `semana`, CONCAT(`Proyecto`, '-', `semana`) AS Condicion, `Fecha_Inicio_Sem`, `Fecha_Fin_Sem`, `diasCompletadosReal`, `diasCompletadosTeorico`, `diasTotales`, `porcentajeCompletadoReal`, `porcentajeCompletadoTeorico` FROM general_curvas_pdc) AS tablaPDC LEFT JOIN (SELECT `id` AS idGeneral, `Proyecto` AS ProyectoGeneral, `semana` AS semanaGeneral, CONCAT(`Proyecto`, '-', `semana`) AS CondicionGeneral, `Fecha_Inicio_Sem` AS Fecha_Inicio_SemGeneral, `Fecha_Fin_Sem` AS Fecha_Fin_SemGeneral, `diasCompletadosReal` AS diasCompletadosRealGeneral, `diasCompletadosTeorico` AS diasCompletadosTeoricoGeneral, `diasTotales` AS diasTotalesGeneral, `porcentajeCompletadoReal` AS porcentajeCompletadoRealGeneral, `porcentajeCompletadoTeorico` AS porcentajeCompletadoTeoricoGeneral FROM general_curvas) AS tablaGeneral ON tablaPDC.`Condicion` = tablaGeneral.`CondicionGeneral`";

  $resultado3 = mysqli_query($conexion, $query3);
  
  sleep(2);

  if(!$resultado3){
      die(mysqli_error($conexion));
  } else{
    echo "<li>Curva S PDC - OK";
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

        $diferenciaPorcentajeCompletadoTeorico = ($porcentajeCompletadoTeorico - $porcentajeCompletadoTeoricoAnterior);
        $porcentajeCompletadoTeoricoAnterior = $porcentajeCompletadoTeorico;

        $diferenciaPorcentajeCompletadoTeoricoGeneral = ($porcentajeCompletadoTeoricoGeneral - $porcentajeCompletadoTeoricoGeneralAnterior);
        $porcentajeCompletadoTeoricoGeneralAnterior = $porcentajeCompletadoTeoricoGeneral;

      $query4 .= "UPDATE general_curvas_pdc SET porcentajeCompletadoTeoricoGeneral = $porcentajeCompletadoTeoricoGeneral, porcentajeCompletadoRealGeneral = $porcentajeCompletadoRealGeneral, diferenciaPorcentajeCompletadoTeorico = $diferenciaPorcentajeCompletadoTeorico, diferenciaPorcentajeCompletadoTeoricoGeneral = $diferenciaPorcentajeCompletadoTeoricoGeneral WHERE semana = $semana AND Proyecto = '$ProyectoAnterior'; ";
    }
    $resultado4 = mysqli_multi_query($conexion, $query4);

    sleep(2);

    if(!$resultado4){
        die(mysqli_error($conexion));
    } else{
      echo "<li>Curva S PDC - OK";
    }
  }
}

mysqli_free_result($resultado1);
mysqli_free_result($resultado3);

mysqli_close($conexion);

// session_destroy();

?>
