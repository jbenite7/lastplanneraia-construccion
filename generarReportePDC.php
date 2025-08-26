<?php session_start();
require ("conexion.php");

$query="TRUNCATE TABLE general_informe_pdc";
//echo "$query <br>" ;
$resultado= mysqli_query($conexion, $query);
$query1 = "SELECT  * FROM general_proyectos_procesos WHERE Area='Construccion' AND Activo=1 AND pdcActivo=1";
//echo "$query1 <br>" ;
$resultado1 = mysqli_query($conexion, $query1);
$num_registros_resultado1 = $resultado1->num_rows;
if($num_registros_resultado1==0){
  echo "<li>Plan de Compras - No hay proyectos";
}else{
  $query2 = "INSERT INTO `general_informe_pdc`(`Proyecto`, `semana`, `Fecha_Inicio_Sem`, `Fecha_Fin_Sem`, `fechaHoy`, `maxSemana`, `Proyecto_maxSemana`, `tipoPaquete`, `paqueteContratacion`, `contratos`, `numeroSubcontratos`, `subcontratoPaquete`, `estado`, `fechaElaboracionPliegos`, `diasElaboracionPliegos`, `fechaRealElaboracionPliegos`, `fechaIngresoLicify`, `diasIngresoLicify`, `fechaRealIngresoLicify`, `fechaEntregaPliegos`, `diasEntregaPliegos`, `fechaRealEntregaPliegos`, `fechaReciboPropuestas`, `diasReciboPropuestas`, `fechaRealReciboPropuestas`, `fechaCuadrosComparativos`, `diasCuadrosComparativos`, `fechaRealCuadrosComparativos`, `fechaLegalizacionContrato`, `diasLegalizacionContrato`, `fechaRealLegalizacionContrato`, `fechaFabricacion`, `diasFabricacion`, `fechaRealFabricacion`, `fechaInsumosObra`, `diasInsumosObra`, `fechaRealInsumosObra`, `fechaInicio`, `fechaInicioProyectada`, `fechaRealInicio`, `idProveedorAdjudicado`, `proveedorAdjudicado`, `nitProveedorAdjudicado`, `numeroContrato`, `fechaVencimientoPolizas`, `valorPresupuesto`, `valorPrimeraNegociacion`, `valorAdjudicado`, `valorAnticipo`,  `valorReclamado`, `valorDevoluciones`, `observacionesContrato`)";
  while ($data1 = mysqli_fetch_assoc($resultado1)) {
    $Proyecto = $data1["Proyecto_Proceso"];
    //echo "<li> $Proyecto";
    $Base_de_Datos = $data1["Base_de_Datos"];

    // $query2 .= " SELECT '$Proyecto', `Semana`, `Actividad`, NULL, NULL, `Critica`, `Atrasada`, `Activa`, `cantidad_ppto`, `Cantidad_Sugerida`, `Compromiso`, `Ejecutado_Real`, `PAC`, `P_Completado`, `Categoria_CNP`, `CNP`, `Observaciones_CNP`, `Categoria_CNC`, `CNC`, `Observaciones_CNC`, `Responsable_AIA`, `Sub_Contratista` FROM $Base_de_Datos"."_programacion_semanal WHERE Semana<=((SELECT MAX(Semana) FROM $Base_de_Datos"."_programacion_semanal)) UNION ";
    $query2 .= " SELECT '$Proyecto' AS Proyecto, $Base_de_Datos" . "_pdc.`semana` AS semana, $Base_de_Datos" . "_semanas_activas.`Fecha_Inicio_Sem` AS Fecha_Inicio_Sem,  $Base_de_Datos" . "_semanas_activas.`Fecha_Fin_Sem` AS Fecha_Fin_Sem, DATE(NOW()) AS fechaHoy, (SELECT MAX(`semana`) FROM $Base_de_Datos" . "_pdc) AS maxSemana, CONCAT('$Proyecto (', (SELECT `Fecha_Fin_Sem` FROM $Base_de_Datos" . "_semanas_activas WHERE Semana = (SELECT MAX(`semana`) FROM $Base_de_Datos" . "_pdc)),')') AS Proyecto_maxSemana, ";

    $query2 .= "$Base_de_Datos" . "_pdc.`tipoPaquete` AS tipoPaquete, $Base_de_Datos" . "_pdc.`paqueteContratacion` AS paqueteContratacion, $Base_de_Datos" . "_pdc.`contratos` AS contratos, $Base_de_Datos" . "_pdc.`numeroSubcontratos` AS numeroSubcontratos, $Base_de_Datos" . "_pdc.`subcontratoPaquete` AS subcontratoPaquete, $Base_de_Datos" . "_pdc.`estado` AS estado, ";

    $query2 .= "$Base_de_Datos" . "_pdc.`fechaElaboracionPliegos` AS fechaElaboracionPliegos, $Base_de_Datos" . "_pdc.`diasElaboracionPliegos` AS diasElaboracionPliegos, $Base_de_Datos" . "_pdc.`fechaRealElaboracionPliegos` AS fechaRealElaboracionPliegos, ";

    $query2 .= "$Base_de_Datos" . "_pdc.`fechaIngresoLicify` AS fechaIngresoLicify, $Base_de_Datos" . "_pdc.`diasIngresoLicify` AS diasIngresoLicify, $Base_de_Datos" . "_pdc.`fechaRealIngresoLicify` AS fechaRealIngresoLicify, ";

    $query2 .= "$Base_de_Datos" . "_pdc.`fechaEntregaPliegos` AS fechaEntregaPliegos, $Base_de_Datos" . "_pdc.`diasEntregaPliegos` AS diasEntregaPliegos, $Base_de_Datos" . "_pdc.`fechaRealEntregaPliegos` AS fechaRealEntregaPliegos, ";

    $query2 .= "$Base_de_Datos" . "_pdc.`fechaReciboPropuestas` AS fechaReciboPropuestas, $Base_de_Datos" . "_pdc.`diasReciboPropuestas` AS diasReciboPropuestas, $Base_de_Datos" . "_pdc.`fechaRealReciboPropuestas` AS fechaRealReciboPropuestas, ";

    $query2 .= "$Base_de_Datos" . "_pdc.`fechaCuadrosComparativos` AS fechaCuadrosComparativos, $Base_de_Datos" . "_pdc.`diasCuadrosComparativos` AS diasCuadrosComparativos, $Base_de_Datos" . "_pdc.`fechaRealCuadrosComparativos` AS fechaRealCuadrosComparativos, ";

    $query2 .= "$Base_de_Datos" . "_pdc.`fechaLegalizacionContrato` AS fechaLegalizacionContrato, $Base_de_Datos" . "_pdc.`diasLegalizacionContrato` AS diasLegalizacionContrato, $Base_de_Datos" . "_pdc.`fechaRealLegalizacionContrato` AS fechaRealLegalizacionContrato, ";

    $query2 .= "$Base_de_Datos" . "_pdc.`fechaFabricacion` AS fechaFabricacion, $Base_de_Datos" . "_pdc.`diasFabricacion` AS diasFabricacion, $Base_de_Datos" . "_pdc.`fechaRealFabricacion` AS fechaRealFabricacion, ";

    $query2 .= "$Base_de_Datos" . "_pdc.`fechaInsumosObra` AS fechaInsumosObra, $Base_de_Datos" . "_pdc.`diasInsumosObra` AS diasInsumosObra, $Base_de_Datos" . "_pdc.`fechaRealInsumosObra` AS fechaRealInsumosObra, ";

    $query2 .= "$Base_de_Datos" . "_pdc.`fechaInicio` AS fechaInicio, $Base_de_Datos" . "_pdc.`fechaInicioProyectada` AS fechaInicioProyectada, $Base_de_Datos" . "_pdc.`fechaRealInicio` AS fechaRealInicio, $Base_de_Datos" . "_pdc.`idProveedorAdjudicado` AS idProveedorAdjudicado, $Base_de_Datos" . "_subcontratistas.`subcontratista` AS proveedorAdjudicado, $Base_de_Datos" . "_subcontratistas.`NIT` AS nitProveedorAdjudicado, ";

    $query2 .= "$Base_de_Datos" . "_pdc.`numeroContrato` AS numeroContrato, $Base_de_Datos" . "_pdc.`fechaVencimientoPolizas` AS fechaVencimientoPolizas, $Base_de_Datos" . "_pdc.`valorPresupuesto` AS valorPresupuesto, $Base_de_Datos" . "_pdc.`valorPrimeraNegociacion` AS valorPrimeraNegociacion, $Base_de_Datos" . "_pdc.`valorAdjudicado` AS valorAdjudicado, $Base_de_Datos" . "_pdc.`valorAnticipo` AS valorAnticipo, $Base_de_Datos" . "_pdc.`valorReclamado` AS valorReclamado, $Base_de_Datos" . "_pdc.`valorDevoluciones` AS valorDevoluciones, $Base_de_Datos" . "_pdc.`observacionesContrato` AS observacionesContrato ";

    $query2 .= "FROM $Base_de_Datos" . "_pdc ";

    $query2 .= "LEFT JOIN $Base_de_Datos" . "_semanas_activas ON $Base_de_Datos" . "_semanas_activas.`Semana`=$Base_de_Datos" . "_pdc.`semana` ";

    $query2 .= "LEFT JOIN $Base_de_Datos" . "_subcontratistas ON $Base_de_Datos" . "_subcontratistas.`Id`=$Base_de_Datos" . "_pdc.`idProveedorAdjudicado` ";

    $query2 .= "WHERE $Base_de_Datos" . "_pdc.`titulo`=0 AND $Base_de_Datos" . "_pdc.`semana`<=((SELECT MAX(`semana`) FROM $Base_de_Datos" . "_pdc)) UNION ";

  }
  $query2 = substr($query2, 0, -7);
  //echo "<li> $query2";
  $resultado2 = mysqli_query($conexion, $query2);
  if (!$resultado2) {
    die(mysqli_error($conexion));
  }
  else {
    echo "<li> Plan de Compras - OK";
  }
}

//mysqli_free_result($resultado);
mysqli_free_result($resultado1);
//mysqli_free_result($resultado2);

mysqli_close($conexion);

//session_destroy();

?>
