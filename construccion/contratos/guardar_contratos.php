<?php
require("../conexion.php");

$db=$_GET['db'];
$opcion=$_POST["opcion"];

// $db="brizaDelCabrero";
// $opcion="actualizarInsumosRecursos";

$informacion=[];
// $_POST['Id']=6;
// $_POST['codigo']='';
// $_POST['actividad']="Mortero de Piso en Concreto";
// $_POST['descripcionActividad']="Vaciado y nivelación de pisos en concreto";
// $_POST['fechaInicio']="2021-12-15";
// $_POST['tipoContrato']=1;


if($_SERVER['REQUEST_METHOD']== 'POST' && $opcion == "modificar"){
    $Id=$_POST['Id'];
    $tipoContrato=$_POST['tipoContrato'];
    $actividadModificar=$_POST['actividadModificar'];
    $errores='';

    if(empty($_POST['semana'])){
        $semanaActualizacion='';
    } else{
        $semanaActualizacion=$_POST['semana'];
    }


    $paqueteSI1=insumosPaquetes($paquete = $_POST['paqueteSI1'], $insumo = !isset($_POST['SI1']) ? "NULL" : $_POST['SI1'])[0];
    $SI1=insumosPaquetes($paquete = $_POST['paqueteSI1'], $insumo = !isset($_POST['SI1']) ? "NULL" : $_POST['SI1'])[1];

    $paqueteSI2=insumosPaquetes($paquete = $_POST['paqueteSI2'], $insumo = !isset($_POST['SI2']) ? "NULL" : $_POST['SI2'])[0];
    $SI2=insumosPaquetes($paquete = $_POST['paqueteSI2'], $insumo = !isset($_POST['SI2']) ? "NULL" : $_POST['SI2'])[1];

    $paqueteSI3=insumosPaquetes($paquete = $_POST['paqueteSI3'], $insumo = !isset($_POST['SI3']) ? "NULL" : $_POST['SI3'])[0];
    $SI3=insumosPaquetes($paquete = $_POST['paqueteSI3'], $insumo = !isset($_POST['SI3']) ? "NULL" : $_POST['SI3'])[1];

    $paqueteSI4=insumosPaquetes($paquete = $_POST['paqueteSI4'], $insumo = !isset($_POST['SI4']) ? "NULL" : $_POST['SI4'])[0];
    $SI4=insumosPaquetes($paquete = $_POST['paqueteSI4'], $insumo = !isset($_POST['SI4']) ? "NULL" : $_POST['SI4'])[1];

    $paqueteSI5=insumosPaquetes($paquete = $_POST['paqueteSI5'], $insumo = !isset($_POST['SI5']) ? "NULL" : $_POST['SI5'])[0];
    $SI5=insumosPaquetes($paquete = $_POST['paqueteSI5'], $insumo = !isset($_POST['SI5']) ? "NULL" : $_POST['SI5'])[1];

    $paqueteS1=insumosPaquetes($paquete = $_POST['paqueteS1'], $insumo = !isset($_POST['S1']) ? "NULL" : $_POST['S1'])[0];
    $S1=insumosPaquetes($paquete = $_POST['paqueteS1'], $insumo = !isset($_POST['S1']) ? "NULL" : $_POST['S1'])[1];

    $paqueteS2=insumosPaquetes($paquete = $_POST['paqueteS2'], $insumo = !isset($_POST['S2']) ? "NULL" : $_POST['S2'])[0];
    $S2=insumosPaquetes($paquete = $_POST['paqueteS2'], $insumo = !isset($_POST['S2']) ? "NULL" : $_POST['S2'])[1];

    $paqueteS3=insumosPaquetes($paquete = $_POST['paqueteS3'], $insumo = !isset($_POST['S3']) ? "NULL" : $_POST['S3'])[0];
    $S3=insumosPaquetes($paquete = $_POST['paqueteS3'], $insumo = !isset($_POST['S3']) ? "NULL" : $_POST['S3'])[1];

    $paqueteS4=insumosPaquetes($paquete = $_POST['paqueteS4'], $insumo = !isset($_POST['S4']) ? "NULL" : $_POST['S4'])[0];
    $S4=insumosPaquetes($paquete = $_POST['paqueteS4'], $insumo = !isset($_POST['S4']) ? "NULL" : $_POST['S4'])[1];

    $paqueteS5=insumosPaquetes($paquete = $_POST['paqueteS5'], $insumo = !isset($_POST['S5']) ? "NULL" : $_POST['S5'])[0];
    $S5=insumosPaquetes($paquete = $_POST['paqueteS5'], $insumo = !isset($_POST['S5']) ? "NULL" : $_POST['S5'])[1];

    $paqueteMO1=insumosPaquetes($paquete = $_POST['paqueteMO1'], $insumo = !isset($_POST['MO1']) ? "NULL" : $_POST['MO1'])[0];
    $MO1=insumosPaquetes($paquete = $_POST['paqueteMO1'], $insumo = !isset($_POST['MO1']) ? "NULL" : $_POST['MO1'])[1];

    $paqueteMO2=insumosPaquetes($paquete = $_POST['paqueteMO2'], $insumo = !isset($_POST['MO2']) ? "NULL" : $_POST['MO2'])[0];
    $MO2=insumosPaquetes($paquete = $_POST['paqueteMO2'], $insumo = !isset($_POST['MO2']) ? "NULL" : $_POST['MO2'])[1];

    $paqueteMO3=insumosPaquetes($paquete = $_POST['paqueteMO3'], $insumo = !isset($_POST['MO3']) ? "NULL" : $_POST['MO3'])[0];
    $MO3=insumosPaquetes($paquete = $_POST['paqueteMO3'], $insumo = !isset($_POST['MO3']) ? "NULL" : $_POST['MO3'])[1];

    $paqueteMO4=insumosPaquetes($paquete = $_POST['paqueteMO4'], $insumo = !isset($_POST['MO4']) ? "NULL" : $_POST['MO4'])[0];
    $MO4=insumosPaquetes($paquete = $_POST['paqueteMO4'], $insumo = !isset($_POST['MO4']) ? "NULL" : $_POST['MO4'])[1];

    $paqueteMO5=insumosPaquetes($paquete = $_POST['paqueteMO5'], $insumo = !isset($_POST['MO5']) ? "NULL" : $_POST['MO5'])[0];
    $MO5=insumosPaquetes($paquete = $_POST['paqueteMO5'], $insumo = !isset($_POST['MO5']) ? "NULL" : $_POST['MO5'])[1];

    if($paqueteSI1 == "NULL" && $paqueteSI2 == "NULL" && $paqueteSI3 == "NULL" && $paqueteSI4 == "NULL" && $paqueteSI5 == "NULL" && $tipoContrato == 2){
      $errores .= "No se han asignado paquetes de contratación de Suministro e Instalación para la actividad; ";
    }else{
      if($paqueteMO1 == "NULL" && $paqueteMO2 == "NULL" && $paqueteMO3 == "NULL" && $paqueteMO4 == "NULL" && $paqueteMO5 == "NULL" && $paqueteS1 == "NULL" && $paqueteS2 == "NULL" && $paqueteS3 == "NULL" && $paqueteS4 == "NULL" && $paqueteS5 == "NULL" && $tipoContrato == 1){
        $errores .= "No se han asignado paquetes de contratación de Suministro o de Mano de Obra para la actividad; ";
      }else{
      }
    }


    //echo "$Id, $codigo, $Actividad, $descripcionActividad, $actividadInicio, $fechaInicio, $tipoContrato";

    if(!empty($errores)){
      $resultado=false;
    } else {
      $query = "UPDATE $db"."_actividades SET SI1=$SI1, paqueteSI1=$paqueteSI1, SI2=$SI2, paqueteSI2=$paqueteSI2, SI3=$SI3, paqueteSI3=$paqueteSI3, SI4=$SI4, paqueteSI4=$paqueteSI4, SI5=$SI5, paqueteSI5=$paqueteSI5, S1=$S1, paqueteS1=$paqueteS1, S2=$S2, paqueteS2=$paqueteS2, S3=$S3, paqueteS3=$paqueteS3, S4=$S4, paqueteS4=$paqueteS4, S5=$S5, paqueteS5=$paqueteS5, MO1=$MO1, paqueteMO1=$paqueteMO1, MO2=$MO2, paqueteMO2=$paqueteMO2, MO3=$MO3, paqueteMO3=$paqueteMO3, MO4=$MO4, paqueteMO4=$paqueteMO4, MO5=$MO5, paqueteMO5=$paqueteMO5, semanaActualizacion=$semanaActualizacion WHERE Id=$Id";

      $resultado= mysqli_query($conexion, $query);

      $query1 = "INSERT INTO `general_dias_procesos_contratacion`(`id`, `paqueteContratacion`, `tipoPaquete`, `diasElaboracionPliegos`, `diasIngresoLicify`, `diasEntregaPliegos`, `diasReciboPropuestas`, `diasCuadrosComparativos`, `diasLegalizacionContrato`, `diasFabricacion`, `diasInsumosObra`)
      SELECT
      NULL, $paqueteSI1,'Suministro e Instalación',1,1,1,1,1,1,1,1 WHERE NOT EXISTS (SELECT `paqueteContratacion` FROM `general_dias_procesos_contratacion` WHERE (`paqueteContratacion`=$paqueteSI1 OR $paqueteSI1 = '' OR $paqueteSI1 IS NULL) AND `tipoPaquete` = 'Suministro e Instalación') UNION
      SELECT
      NULL, $paqueteSI2,'Suministro e Instalación',1,1,1,1,1,1,1,1 WHERE NOT EXISTS (SELECT `paqueteContratacion` FROM `general_dias_procesos_contratacion` WHERE (`paqueteContratacion`=$paqueteSI2 OR $paqueteSI2 = '' OR $paqueteSI2 IS NULL) AND `tipoPaquete` = 'Suministro e Instalación') UNION
      SELECT
      NULL, $paqueteSI3,'Suministro e Instalación',1,1,1,1,1,1,1,1 WHERE NOT EXISTS (SELECT `paqueteContratacion` FROM `general_dias_procesos_contratacion` WHERE (`paqueteContratacion`=$paqueteSI3 OR $paqueteSI3 = '' OR $paqueteSI3 IS NULL) AND `tipoPaquete` = 'Suministro e Instalación') UNION
      SELECT
      NULL, $paqueteSI4,'Suministro e Instalación',1,1,1,1,1,1,1,1 WHERE NOT EXISTS (SELECT `paqueteContratacion` FROM `general_dias_procesos_contratacion` WHERE (`paqueteContratacion`=$paqueteSI4 OR $paqueteSI4 = '' OR $paqueteSI4 IS NULL) AND `tipoPaquete` = 'Suministro e Instalación') UNION
      SELECT
      NULL, $paqueteSI5,'Suministro e Instalación',1,1,1,1,1,1,1,1 WHERE NOT EXISTS (SELECT `paqueteContratacion` FROM `general_dias_procesos_contratacion` WHERE (`paqueteContratacion`=$paqueteSI5 OR $paqueteSI5 = '' OR $paqueteSI5 IS NULL) AND `tipoPaquete` = 'Suministro e Instalación') UNION
      SELECT
      NULL, $paqueteMO1,'Mano de Obra',1,1,1,1,1,1,1,1 WHERE NOT EXISTS (SELECT `paqueteContratacion` FROM `general_dias_procesos_contratacion` WHERE (`paqueteContratacion`=$paqueteMO1 OR $paqueteMO1 = '' OR $paqueteMO1 IS NULL) AND `tipoPaquete` = 'Mano de Obra') UNION
      SELECT
      NULL, $paqueteMO2,'Mano de Obra',1,1,1,1,1,1,1,1 WHERE NOT EXISTS (SELECT `paqueteContratacion` FROM `general_dias_procesos_contratacion` WHERE (`paqueteContratacion`=$paqueteMO2 OR $paqueteMO2 = '' OR $paqueteMO2 IS NULL) AND `tipoPaquete` = 'Mano de Obra') UNION
      SELECT
      NULL, $paqueteMO3,'Mano de Obra',1,1,1,1,1,1,1,1 WHERE NOT EXISTS (SELECT `paqueteContratacion` FROM `general_dias_procesos_contratacion` WHERE (`paqueteContratacion`=$paqueteMO3 OR $paqueteMO3 = '' OR $paqueteMO3 IS NULL) AND `tipoPaquete` = 'Mano de Obra') UNION
      SELECT
      NULL, $paqueteMO4,'Mano de Obra',1,1,1,1,1,1,1,1 WHERE NOT EXISTS (SELECT `paqueteContratacion` FROM `general_dias_procesos_contratacion` WHERE (`paqueteContratacion`=$paqueteMO4 OR $paqueteMO4 = '' OR $paqueteMO4 IS NULL) AND `tipoPaquete` = 'Mano de Obra') UNION
      SELECT
      NULL, $paqueteMO5,'Mano de Obra',1,1,1,1,1,1,1,1 WHERE NOT EXISTS (SELECT `paqueteContratacion` FROM `general_dias_procesos_contratacion` WHERE (`paqueteContratacion`=$paqueteMO5 OR $paqueteMO5 = '' OR $paqueteMO5 IS NULL) AND `tipoPaquete` = 'Mano de Obra') UNION
      SELECT
      NULL, $paqueteS1,'Suministro',1,1,1,1,1,1,1,1 WHERE NOT EXISTS (SELECT `paqueteContratacion` FROM `general_dias_procesos_contratacion` WHERE (`paqueteContratacion`=$paqueteS1 OR $paqueteS1 = '' OR $paqueteS1 IS NULL) AND `tipoPaquete` = 'Suministro') UNION
      SELECT
      NULL, $paqueteS2,'Suministro',1,1,1,1,1,1,1,1 WHERE NOT EXISTS (SELECT `paqueteContratacion` FROM `general_dias_procesos_contratacion` WHERE (`paqueteContratacion`=$paqueteS2 OR $paqueteS2 = '' OR $paqueteS2 IS NULL) AND `tipoPaquete` = 'Suministro') UNION
      SELECT
      NULL, $paqueteS3,'Suministro',1,1,1,1,1,1,1,1 WHERE NOT EXISTS (SELECT `paqueteContratacion` FROM `general_dias_procesos_contratacion` WHERE (`paqueteContratacion`=$paqueteS3 OR $paqueteS3 = '' OR $paqueteS3 IS NULL) AND `tipoPaquete` = 'Suministro') UNION
      SELECT
      NULL, $paqueteS4,'Suministro',1,1,1,1,1,1,1,1 WHERE NOT EXISTS (SELECT `paqueteContratacion` FROM `general_dias_procesos_contratacion` WHERE (`paqueteContratacion`=$paqueteS4 OR $paqueteS4 = '' OR $paqueteS4 IS NULL) AND `tipoPaquete` = 'Suministro') UNION
      SELECT
      NULL, $paqueteS5,'Suministro',1,1,1,1,1,1,1,1 WHERE NOT EXISTS (SELECT `paqueteContratacion` FROM `general_dias_procesos_contratacion` WHERE (`paqueteContratacion`=$paqueteS5 OR $paqueteS5 = '' OR $paqueteS5 IS NULL) AND `tipoPaquete` = 'Suministro')";

      $resultado1= mysqli_query($conexion, $query1);
    }

    verificar_resultado($resultado, $errores);
    mysqli_close($conexion);


}else if($opcion=="nueva_sem"){
    $f_inicio_sem=/*date("Y-m-d",strtotime("2019-11-26"));*/date("Y-m-d",strtotime($_POST["f_inicio_sem"]));
    nueva_sem($f_inicio_sem, $db, $conexion);
}else if($opcion=="eliminar_sem"){
    $semana=$_POST["semana"];
    eliminar_sem($semana, $db, $conexion);
}else if($opcion=="eliminar"){
    $Id=/*4*/$_POST["Id"];
    eliminar($Id, $db, $conexion);
}else if($opcion=="actualizarFechaInicio"){
    $Id=/*4*/$_POST["idActividad"];
    $nombreActividad=/*4*/$_POST["nombreActividad"];
    $semana=$_POST["semana"];
    actualizarFechaInicio($Id, $nombreActividad, $semana, $db, $conexion);
}else if($opcion == "actualizarListadoPaquetesContratacion"){
  $tipoContrato = $_POST["tipoContrato"];
  actualizarListadoPaquetesContratacion($tipoContrato, $db, $conexion);
}else if($opcion == "actualizarInsumosRecursos"){
  $tipoContrato = $_POST["tipoContrato"];
  // $tipoContrato = 2;
  actualizarInsumosRecursos($tipoContrato, $db, $conexion);
}

function nueva_sem($f_inicio_sem, $db, $conexion){
    require("../funciones_generales/nueva_semana.php");
    //mysqli_close($conexion);
    //require("../conexion.php");
    require("../funciones_generales/modificar_sem_estado.php");
}


function eliminar_sem($semana, $db, $conexion){
    require("../funciones_generales/eliminar_semana.php");
}

function eliminar($Id, $db, $conexion){
  $query="DELETE FROM $db"."_actividades WHERE Id=$Id";
  $resultado=mysqli_query($conexion, $query);
  $errores='';
  verificar_resultado($resultado="OK", $errores);
  mysqli_close($conexion);
}

function actualizarFechaInicio($Id, $nombreActividad, $semana, $db, $conexion){
  $query="SELECT (Fecha_Inicio) FROM $db"."_programa_consolidado WHERE Consecutivo_en_Programa=$Id AND Semana=$semana";
  $resultado=mysqli_query($conexion, $query);
  if(!$resultado){
      die("Error");
  } else{
    while($data=mysqli_fetch_assoc($resultado)){
        $arreglo["data"]=array_map("utf8_encode", $data);
    }
    $json_codificado = json_encode($arreglo, JSON_UNESCAPED_UNICODE);
    echo utf8_decode($json_codificado);
  }
  mysqli_close($conexion);
}

function actualizarListadoPaquetesContratacion($tipoContrato, $db, $conexion){
  if ($tipoContrato == 1) {
    $tipoContratoMO = "tipoPaquete = 'Mano de Obra'";
    $tipoContratoS = "tipoPaquete = 'Suministro'";
    $queryMO = "SELECT * FROM general_dias_procesos_contratacion WHERE $tipoContratoMO";
    $queryS = "SELECT * FROM general_dias_procesos_contratacion WHERE $tipoContratoS";
    $resultadoMO=mysqli_query($conexion, $queryMO);
    if(!$resultadoMO){
        die("Error");
    } else{
      $scriptMO = "<option value=''></option>";
      while($dataMO=mysqli_fetch_assoc($resultadoMO)){
        $scriptMO .= "<option value='" . $dataMO["paqueteContratacion"] . "'>" . $dataMO["paqueteContratacion"] . "</option>";
      }
    }

    $resultadoS=mysqli_query($conexion, $queryS);
    if(!$resultadoS){
        die("Error");
    } else{
      $scriptS = "<option value=''></option>";
      while($dataS=mysqli_fetch_assoc($resultadoS)){
        $scriptS .= "<option value='" . $dataS["paqueteContratacion"] . "'>" . $dataS["paqueteContratacion"] . "</option>";
      }
    }
    $scriptSI = "";
  }else if ($tipoContrato == 2) {
    $tipoContratoSI = "tipoPaquete = 'Suministro e Instalación'";
    $querySI = "SELECT * FROM general_dias_procesos_contratacion WHERE $tipoContratoSI";
    $resultadoSI=mysqli_query($conexion, $querySI);
    if(!$resultadoSI){
        die("Error");
    } else{
      $scriptSI = "<option value=''></option>";
      while($dataSI=mysqli_fetch_assoc($resultadoSI)){
        $scriptSI .= "<option value='" . $dataSI["paqueteContratacion"] . "'>" . $dataSI["paqueteContratacion"] . "</option>";
      }
    }
    $scriptMO = "";
    $scriptS = "";
  }
  $arreglo["listadoMO"] = $scriptMO;
  $arreglo["listadoS"] = $scriptS;
  $arreglo["listadoSI"] = $scriptSI;

  mysqli_close($conexion);
  $json_codificado = json_encode($arreglo, JSON_UNESCAPED_UNICODE);
  echo $json_codificado;

}

function actualizarInsumosRecursos($tipoContrato, $db, $conexion){
  if ($tipoContrato == 1) {
    $queryMO = "SELECT DISTINCT MO1 FROM $db"."_actividades WHERE MO1 IS NOT NULL AND MO1 != '' UNION SELECT DISTINCT MO2 FROM $db"."_actividades WHERE MO2 IS NOT NULL AND MO2 != '' UNION SELECT DISTINCT MO3 FROM $db"."_actividades WHERE MO3 IS NOT NULL AND MO3 != '' UNION SELECT DISTINCT MO4 FROM $db"."_actividades WHERE MO4 IS NOT NULL AND MO4 != '' UNION SELECT DISTINCT MO5 FROM $db"."_actividades WHERE MO5 IS NOT NULL AND MO5 != '' ORDER BY MO1 ASC";
    $queryS = "SELECT DISTINCT S1 FROM $db"."_actividades WHERE S1 IS NOT NULL AND S1 != '' UNION SELECT DISTINCT S2 FROM $db"."_actividades WHERE S2 IS NOT NULL AND S2 != '' UNION SELECT DISTINCT S3 FROM $db"."_actividades WHERE S3 IS NOT NULL AND S3 != '' UNION SELECT DISTINCT S4 FROM $db"."_actividades WHERE S4 IS NOT NULL AND S4 != '' UNION SELECT DISTINCT S5 FROM $db"."_actividades WHERE S5 IS NOT NULL AND S5 != '' ORDER BY S1 ASC";
    $resultadoMO=mysqli_query($conexion, $queryMO);
    if(!$resultadoMO){
        die("Error");
    } else{
      $scriptMO = "<option value=''></option>";
      $dataInsumo = array();
      while($dataMO=mysqli_fetch_assoc($resultadoMO)){
        $dataVariables = explode(";", $dataMO["MO1"]);
        foreach ($dataVariables as $var) {
          array_push($dataInsumo, $var);
        }
      }
      $dataInsumo = (array_unique($dataInsumo));
      foreach ($dataInsumo as $insumo) {
        $scriptMO .= "<option value='" . $insumo . "'>" . $insumo . "</option>";
      }
    }

    $resultadoS=mysqli_query($conexion, $queryS);
    if(!$resultadoS){
        die("Error");
    } else{
      $scriptS = "<option value=''></option>";
      $dataInsumo = array();
      while($dataS=mysqli_fetch_assoc($resultadoS)){
        $dataVariables = explode(";", $dataS["S1"]);
        foreach ($dataVariables as $var) {
          array_push($dataInsumo, $var);
        }
      }
      $dataInsumo = (array_unique($dataInsumo));
      foreach ($dataInsumo as $insumo) {
        $scriptS .= "<option value='" . $insumo . "'>" . $insumo . "</option>";
      }
    }
    $scriptSI = "";
  }else if ($tipoContrato == 2) {
    $querySI = "SELECT DISTINCT SI1 FROM $db"."_actividades WHERE SI1 IS NOT NULL AND SI1 != '' UNION SELECT DISTINCT SI2 FROM $db"."_actividades WHERE SI2 IS NOT NULL AND SI2 != '' UNION SELECT DISTINCT SI3 FROM $db"."_actividades WHERE SI3 IS NOT NULL AND SI3 != '' UNION SELECT DISTINCT SI4 FROM $db"."_actividades WHERE SI4 IS NOT NULL AND SI4 != '' UNION SELECT DISTINCT SI5 FROM $db"."_actividades WHERE SI5 IS NOT NULL AND SI5 != '' ORDER BY SI1 ASC";
    $resultadoSI=mysqli_query($conexion, $querySI);
    if(!$resultadoSI){
        die("Error");
    } else{
      $scriptSI = "<option value=''></option>";
      $dataInsumo = array();
      while($dataSI=mysqli_fetch_assoc($resultadoSI)){
        $dataVariables = explode(";", $dataSI["SI1"]);
        foreach ($dataVariables as $var) {
          array_push($dataInsumo, $var);
        }
      }
      $dataInsumo = (array_unique($dataInsumo));
      foreach ($dataInsumo as $insumo) {
        $scriptSI .= "<option value='" . $insumo . "'>" . $insumo . "</option>";
      }
    }
    $scriptMO = "";
    $scriptS = "";
  }
  $arreglo["listadoMO"] = $scriptMO;
  $arreglo["listadoS"] = $scriptS;
  $arreglo["listadoSI"] = $scriptSI;

  mysqli_close($conexion);
  $json_codificado = json_encode($arreglo, JSON_UNESCAPED_UNICODE);
  echo $json_codificado;

}

function insumosPaquetes($paquete, $insumo){
  if(!$paquete || empty($paquete)){
      $insumoFinal= "NULL";
      $paqueteFinal= "NULL";
  }else{
    if(!isset($insumo) || !$insumo || empty($insumo) || $insumo == "NULL"){
      $insumoFinal= "NULL";
    }else{
      $insumoArray = "";
      foreach ($insumo as $insumo) {
        if($paquete == ''){
        }else{
          $insumoArray .= "$insumo;";
        }
      }
      $insumoFinal = "'" . substr($insumoArray, 0, -1) . "'";
    }
    $paqueteFinal= "'" . $paquete . "'";
  }
  $array = array($paqueteFinal, $insumoFinal);
  return $array;
}

function verificar_resultado($resultado, $errores){
    if(!$resultado){
        $informacion["respuesta"] ="ERROR";
    }
    if($errores ==''){
        $informacion["respuesta"] = "BIEN";
    }else if ($errores=='Debe rellenar todos los campos'){
        $informacion["respuesta"] = "VACIO";
    }else if ($errores=='La actividad que estás intentando registrar ya existe'){
        $informacion["respuesta"] = "EXISTE";
    }else if ($errores=='No se puede eliminar esta actividad'){
        $informacion["respuesta"] = "NO_ELIMINAR";
    }else{
        $informacion["respuesta"] = $errores;
    }

    echo json_encode($informacion);
}

function cerrar($conexion){
    mysqli_close($conexion);
}
?>
