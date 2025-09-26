<?php

require("../conexion.php");

$db=/*"camino_verde"*/$_POST['db'];
$opcion=/*"restricciones"*/$_POST["opcion"];
$informacion=[];

if ($opcion == "modificar") {
  $semana=$_POST["semana"];
  $Id=$_POST["Id"];
  $contratos=verificarNulo($_POST["actividadesDelContrato"]);
  $fechaActual=verificarNulo(importarFecha($_POST["fechaActual"]));
  $fechaInicioContrato=verificarNulo(importarFecha($_POST["fechaInicioContrato"]));
  $estadoProceso=verificarNulo($_POST["estadoProceso"]);

  $diasElaboracionPliegos=verificarNulo($_POST["diasElaboracionPliegos"]);
  $fechaElaboracionPliegosTeorica=verificarNulo(importarFecha($_POST["fechaElaboracionPliegosTeorica"]));
  $fechaRealElaboracionPliegos=verificarNulo(importarFecha($_POST["fechaRealElaboracionPliegos"]));

  $diasIngresoLicify=verificarNulo($_POST["diasIngresoLicify"]);
  $fechaIngresoLicifyTeorica=verificarNulo(importarFecha($_POST["fechaIngresoLicifyTeorica"]));
  $fechaRealIngresoLicify=verificarNulo(importarFecha($_POST["fechaRealIngresoLicify"]));

  $diasEntregaPliegos=verificarNulo($_POST["diasEntregaPliegos"]);
  $fechaEntregaPliegosTeorica=verificarNulo(importarFecha($_POST["fechaEntregaPliegosTeorica"]));
  $fechaRealEntregaPliegos=verificarNulo(importarFecha($_POST["fechaRealEntregaPliegos"]));

  $diasReciboPropuestas=verificarNulo($_POST["diasReciboPropuestas"]);
  $fechaReciboPropuestasTeorica=verificarNulo(importarFecha($_POST["fechaReciboPropuestasTeorica"]));
  $fechaRealReciboPropuestas=verificarNulo(importarFecha($_POST["fechaRealReciboPropuestas"]));

  $diasCuadrosComparativos=verificarNulo($_POST["diasCuadrosComparativos"]);
  $fechaCuadrosComparativosTeorica=verificarNulo(importarFecha($_POST["fechaCuadrosComparativosTeorica"]));
  $fechaRealCuadrosComparativos=verificarNulo(importarFecha($_POST["fechaRealCuadrosComparativos"]));

  $diasLegalizacionContrato=verificarNulo($_POST["diasLegalizacionContrato"]);
  $fechaLegalizacionContratoTeorica=verificarNulo(importarFecha($_POST["fechaLegalizacionContratoTeorica"]));
  $fechaRealLegalizacionContrato=verificarNulo(importarFecha($_POST["fechaRealLegalizacionContrato"]));

  $diasFabricacion=verificarNulo($_POST["diasFabricacion"]);
  $fechaFabricacionTeorica=verificarNulo(importarFecha($_POST["fechaFabricacionTeorica"]));
  $fechaRealFabricacion=verificarNulo(importarFecha($_POST["fechaRealFabricacion"]));

  $diasInsumosObra=verificarNulo($_POST["diasInsumosObra"]);
  $fechaInsumosObraTeorica=verificarNulo(importarFecha($_POST["fechaInsumosObraTeorica"]));
  $fechaRealInsumosObra=verificarNulo(importarFecha($_POST["fechaRealInsumosObra"]));

  $fechaInicioTeorica=verificarNulo(importarFecha($_POST["fechaInicioProyectadaContratoTeorica"]));
  $fechaInicioProyectada=verificarNulo(importarFecha($_POST["fechaInicioProyectadaContrato"]));
  $fechaRealInicio=verificarNulo(importarFecha($_POST["fechaRealInicioProyectadaContrato"]));

  $observacionesContrato=verificarNulo($_POST["observacionesContrato"]);

  $idProveedorExistente=verificarNulo($_POST['idProveedorExistente']);

  if(empty($_POST['activoInformacionAdjudicacionProveedor'])){
      $activoInformacionAdjudicacionProveedor='';
  } else{
      $activoInformacionAdjudicacionProveedor=verificarNulo($_POST['activoInformacionAdjudicacionProveedor']);
  }

  $Subcontratista=verificarNulo(filter_var(($_POST['subcontratistaAdjudicado']), FILTER_SANITIZE_STRING));

  $email=verificarNulo(filter_var(($_POST['correoAdjudicado']), FILTER_SANITIZE_EMAIL));

  $confirmar_correo=verificarNulo(filter_var(($_POST['correoAdjudicado']), FILTER_SANITIZE_EMAIL));

  $NIT=verificarNulo($_POST['nitAdjudicado']);

  $alcance=verificarNulo($_POST['actividadesDelContrato']);

  $tipo_proveedor=verificarNulo($_POST['tipoPaquete']);
  if($tipo_proveedor == "'Suministro'"){
    $tipo_proveedor = "'Suministro de Materiales, Herramientas o Equipos'";
  }

  if(empty($_POST['activoInformacionAdjudicacionContrato'])){
      $activoInformacionAdjudicacionContrato='';
  } else{
      $activoInformacionAdjudicacionContrato=verificarNulo($_POST['activoInformacionAdjudicacionContrato']);
  }

  $aplicaPolizas=verificarNulo($_POST['aplicaPolizas']);

  $numeroContrato=verificarNulo($_POST['numeroContrato']);

  $fechaVencimientoPolizas=verificarNulo(importarFecha($_POST["fechaVencimientoPolizas"]));

  $valorPresupuesto=verificarNulo((str_replace('$', '', str_replace(',', '', $_POST['valorPresupuesto']))));

  $valorPrimeraNegociacion=verificarNulo((str_replace('$', '', str_replace(',', '', $_POST['valorPrimeraNegociacion']))));

  $valorAdjudicado=verificarNulo((str_replace('$', '', str_replace(',', '', $_POST['valorAdjudicado']))));
  
  $valorAnticipo=verificarNulo((str_replace('$', '', str_replace(',', '', $_POST['valorAnticipo']))));

  $valorReclamado=verificarNulo((str_replace('$', '', str_replace(',', '', $_POST['valorReclamado']))));

  $valorDevoluciones=verificarNulo((str_replace('$', '', str_replace(',', '', $_POST['valorDevoluciones']))));

}else if($opcion=="nueva_sem"){
  $f_inicio_sem=date("Y-m-d",strtotime($_POST["f_inicio_sem"]));
}else if($opcion=="eliminar_sem"){
  $semana=$_POST["semana"];
}else if($opcion=="recalcularProcesoContratacion"){
  $diasTotales = $_POST["diasTotales"];
  $diasInsumosObra = $_POST["diasInsumosObra"];
  $diasFabricacion = $_POST["diasFabricacion"];
  $diasLegalizacionContrato = $_POST["diasLegalizacionContrato"];
  $diasCuadrosComparativos = $_POST["diasCuadrosComparativos"];
  $diasReciboPropuestas = $_POST["diasReciboPropuestas"];
  $diasEntregaPliegos = $_POST["diasEntregaPliegos"];
  $diasIngresoLicify = $_POST["diasIngresoLicify"];
  $diasElaboracionPliegos = $_POST["diasElaboracionPliegos"];

  $fechaInicioContrato = importarFecha($_POST["fechaInicioContrato"]);
  $fechaActual = importarFecha($_POST["fechaActual"]);
  $fechaRealInicioProyectadaContrato = importarFecha($_POST["fechaRealInicioProyectadaContrato"]);
  $fechaRealInsumosObra = importarFecha($_POST["fechaRealInsumosObra"]);
  $fechaRealFabricacion = importarFecha($_POST["fechaRealFabricacion"]);
  $fechaRealLegalizacionContrato = importarFecha($_POST["fechaRealLegalizacionContrato"]);
  $fechaRealCuadrosComparativos = importarFecha($_POST["fechaRealCuadrosComparativos"]);
  $fechaRealReciboPropuestas = importarFecha($_POST["fechaRealReciboPropuestas"]);
  $fechaRealEntregaPliegos = importarFecha($_POST["fechaRealEntregaPliegos"]);
  $fechaRealIngresoLicify = importarFecha($_POST["fechaRealIngresoLicify"]);
  if($_POST["fechaRealElaboracionPliegos"] != "No Aplica"){
    $fechaRealElaboracionPliegos = importarFecha($_POST["fechaRealElaboracionPliegos"]);
  }else{
    $fechaRealElaboracionPliegos = $_POST["fechaRealElaboracionPliegos"];
  }

}else if($opcion=="verificarProveedor"){
  $base = $_POST['base'];
  if(empty($_POST['idProveedorExistente'])){
      $idProveedorExistente='';
  } else{
      $idProveedorExistente=$_POST['idProveedorExistente'];
  }
  if(empty($_POST['nitAdjudicado'])){
      $nitAdjudicado='';
  } else{
      $nitAdjudicado=filter_var(($_POST['nitAdjudicado']), FILTER_SANITIZE_STRING);
  }
  if(empty($_POST['subcontratistaAdjudicado'])){
      $subcontratistaAdjudicado='';
  } else{
      $subcontratistaAdjudicado=filter_var(($_POST['subcontratistaAdjudicado']), FILTER_SANITIZE_STRING);
  }
  if(empty($_POST['correoAdjudicado'])){
      $correoAdjudicado='';
  } else{
      $correoAdjudicado=filter_var(($_POST['correoAdjudicado']), FILTER_SANITIZE_EMAIL);
  }
  if(empty($_POST['actividadesDelContrato'])){
      $actividadesDelContrato='';
  } else{
      $actividadesDelContrato=filter_var(($_POST['actividadesDelContrato']), FILTER_SANITIZE_STRING);
  }
  if(empty($_POST['tipoPaquete'])){
      $tipoPaquete='';
  } else{
      $tipoPaquete=filter_var(($_POST['tipoPaquete']), FILTER_SANITIZE_STRING);
  }
}else if($opcion=="guardar_DefinirContratos"){
  $numeroSubcontratosConsolidado=$_POST["numeroSubcontratos"];
  $numeroSubcontratosArray=(json_decode($numeroSubcontratosConsolidado, true));
  $numeroSubcontratosArray=$numeroSubcontratosArray["numeroSubcontratos"];
}else if($opcion=="eliminar"){
  $Id=/*4*/$_POST["Id"];
  $paqueteContratacion=/*4*/$_POST["paqueteContratacion"];
  $semana=/*4*/$_POST["semana"];
};
//echo $D_y_E, $Materiales, $MdeO, $Equipos, $Predecesora, $Pdto_Cons, $Modelo, $Responsable_AIA, $Observaciones, $Id;


switch($opcion){
  case 'modificar':
    modificar($db, $conexion, $semana, $Id, $contratos, $estadoProceso, $fechaActual, $fechaInicioContrato, $diasElaboracionPliegos, $fechaElaboracionPliegosTeorica, $fechaRealElaboracionPliegos, $diasIngresoLicify, $fechaIngresoLicifyTeorica, $fechaRealIngresoLicify, $diasEntregaPliegos, $fechaEntregaPliegosTeorica, $fechaRealEntregaPliegos, $diasReciboPropuestas, $fechaReciboPropuestasTeorica, $fechaRealReciboPropuestas, $diasCuadrosComparativos, $fechaCuadrosComparativosTeorica, $fechaRealCuadrosComparativos, $diasLegalizacionContrato, $fechaLegalizacionContratoTeorica, $fechaRealLegalizacionContrato, $diasFabricacion, $fechaFabricacionTeorica, $fechaRealFabricacion, $diasInsumosObra, $fechaInsumosObraTeorica, $fechaRealInsumosObra, $fechaInicioTeorica, $fechaInicioProyectada, $fechaRealInicio, $observacionesContrato, $idProveedorExistente, $activoInformacionAdjudicacionProveedor, $Subcontratista, $email, $confirmar_correo, $NIT, $alcance, $tipo_proveedor, $activoInformacionAdjudicacionContrato, $numeroContrato, $aplicaPolizas, $fechaVencimientoPolizas, $valorPresupuesto, $valorPrimeraNegociacion, $valorAdjudicado, $valorAnticipo, $valorReclamado, $valorDevoluciones);

    // echo "Numero Contrato = $numeroContrato, Valor PPTO = $valorPresupuesto, Valor Adjudicado = $valorAdjudicado";
    break;

  case 'nueva_sem':
    nueva_sem($f_inicio_sem, $db, $conexion);
    break;

  case 'eliminar_sem':
    eliminar_sem($semana, $db, $conexion);
    break;

  case 'restricciones':
    restricciones($conexion, $semana, $db, $nombre);
    break;

  case 'recalcularProcesoContratacion':
    recalcularProcesoContratacion($conexion, $diasTotales, $diasInsumosObra, $diasFabricacion,  $diasLegalizacionContrato, $diasCuadrosComparativos, $diasReciboPropuestas, $diasEntregaPliegos, $diasIngresoLicify, $diasElaboracionPliegos, $fechaInicioContrato, $fechaActual, $fechaRealInicioProyectadaContrato, $fechaRealInsumosObra, $fechaRealFabricacion,  $fechaRealLegalizacionContrato, $fechaRealCuadrosComparativos, $fechaRealReciboPropuestas, $fechaRealEntregaPliegos, $fechaRealIngresoLicify, $fechaRealElaboracionPliegos);
    break;

  case 'verificarProveedor':
    verificarProveedor($base, $idProveedorExistente, $nitAdjudicado, $subcontratistaAdjudicado, $correoAdjudicado, $actividadesDelContrato, $tipoPaquete, $db, $conexion);
    break;

  case 'guardar_DefinirContratos':
    guardar_DefinirContratos($numeroSubcontratosArray, $conexion, $db);
    break;
  
  case 'eliminar':
    eliminar($Id, $paqueteContratacion, $semana, $db, $conexion);
    break;
}

function importarFecha($fecha){
  if(!$fecha){
    $fechaFinal = null;
  } else{
    $fechaFinal = date('Y-m-d', strtotime($fecha));
  }
  return($fechaFinal);
}

function verificarNulo($input){
  if(!$input){
    if($input == '0'){
      $inputFinal = 0;
    }else{
      $inputFinal = "NULL";
    }
  }else{
    if(is_numeric($input)){
      $inputFinal = $input;
    }else{
      $inputFinal = "'$input'";
    }
  }
  return($inputFinal);
}

function calcularFechaTeorica($dias, $fecha, $fechaReal){
  if(!$fechaReal){
    $fechaFinal = date('Y-m-d', strtotime("$fecha + $dias days"));
  } else{
    $fechaFinal = date('Y-m-d', strtotime("$fechaReal + $dias days"));
  }
  return($fechaFinal);
}

function recalcularProcesoContratacion($conexion, $diasTotales, $diasInsumosObra, $diasFabricacion,  $diasLegalizacionContrato, $diasCuadrosComparativos, $diasReciboPropuestas, $diasEntregaPliegos, $diasIngresoLicify, $diasElaboracionPliegos, $fechaInicioContrato, $fechaActual, $fechaRealInicioProyectadaContrato, $fechaRealInsumosObra, $fechaRealFabricacion,  $fechaRealLegalizacionContrato, $fechaRealCuadrosComparativos, $fechaRealReciboPropuestas, $fechaRealEntregaPliegos, $fechaRealIngresoLicify, $fechaRealElaboracionPliegos){

  if($fechaRealElaboracionPliegos == "No Aplica"){
    $data["fechaElaboracionPliegos"] = date("Y-m-d",strtotime("$fechaInicioContrato - $diasTotales days"));
    $fechaRealElaboracionPliegos = null;
  }else{
    if($fechaRealElaboracionPliegos != null){
      $data["fechaElaboracionPliegos"] = date("Y-m-d",strtotime("$fechaInicioContrato - $diasTotales days"));
    }else{
      $data["fechaElaboracionPliegos"] = date("Y-m-d",strtotime("$fechaActual"));
    }
  }
  $data["fechaIngresoLicify"] = calcularFechaTeorica($diasElaboracionPliegos, $data["fechaElaboracionPliegos"], $fechaRealElaboracionPliegos);
  $data["fechaEntregaPliegos"] = calcularFechaTeorica($diasIngresoLicify, $data["fechaIngresoLicify"], $fechaRealIngresoLicify);
  $data["fechaReciboPropuestas"] = calcularFechaTeorica($diasEntregaPliegos, $data["fechaEntregaPliegos"], $fechaRealEntregaPliegos);
  $data["fechaCuadrosComparativos"] = calcularFechaTeorica($diasReciboPropuestas, $data["fechaReciboPropuestas"], $fechaRealReciboPropuestas);
  $data["fechaLegalizacionContrato"] = calcularFechaTeorica($diasCuadrosComparativos, $data["fechaCuadrosComparativos"], $fechaRealCuadrosComparativos);
  $data["fechaFabricacion"] = calcularFechaTeorica($diasLegalizacionContrato, $data["fechaLegalizacionContrato"], $fechaRealLegalizacionContrato);
  $data["fechaInsumosObra"] = calcularFechaTeorica($diasFabricacion, $data["fechaFabricacion"], $fechaRealFabricacion);
  $data["fechaInicioProyectada"] = calcularFechaTeorica($diasInsumosObra, $data["fechaInsumosObra"], $fechaRealInsumosObra);

  $arreglo["data"][]=array_map("utf8_encode", $data);
  $json_codificado = json_encode($arreglo, JSON_UNESCAPED_UNICODE);
  echo utf8_decode($json_codificado);
}

function verificarProveedor($base, $idProveedorExistente, $nitAdjudicado, $subcontratistaAdjudicado, $correoAdjudicado, $actividadesDelContrato, $tipoPaquete, $db, $conexion){
  if($base == 'nitAdjudicado'){
    $scriptBase = "NIT='$nitAdjudicado'";
  }else if($base == 'subcontratistaAdjudicado'){
    $scriptBase = "subcontratista='$subcontratistaAdjudicado'";
  }else if($base == 'correoAdjudicado'){
    $scriptBase = "correo_contacto='$correoAdjudicado'";
  }else if($base == 'actividadesDelContrato'){
    $scriptBase = "alcance='$actividadesDelContrato'";
  }else if($base == 'idProveedorExistente'){
    $scriptBase = "Id='$idProveedorExistente'";
  }

  $query="SELECT COUNT(*) FROM ".$db."_subcontratistas WHERE $scriptBase LIMIT 1";
  $resultado = mysqli_query($conexion, $query);
  if(!$resultado){
    die(mysqli_error($conexion));
  }else{
    $data=mysqli_fetch_assoc($resultado);
    $conteo=$data["COUNT(*)"];
    if($conteo == 0){
     $json_codificado = json_encode("No Existe");
    }else{
     $query1 = "SELECT * FROM ".$db."_subcontratistas WHERE $scriptBase LIMIT 1";
     $resultado1 = mysqli_query($conexion, $query1);
     if(!$resultado1){
       die(mysqli_error($conexion));
     }else{
       while($data1=mysqli_fetch_assoc($resultado1)){
         $arreglo["data"][]=array_map("utf8_encode", $data1);
       }
       $json_codificado = json_encode($arreglo, JSON_UNESCAPED_UNICODE);
     }
    }
    echo utf8_decode($json_codificado);
  }
  mysqli_close($conexion);
}

function modificar($db, $conexion, $semana, $Id, $contratos, $estadoProceso, $fechaActual, $fechaInicioContrato, $diasElaboracionPliegos, $fechaElaboracionPliegosTeorica, $fechaRealElaboracionPliegos, $diasIngresoLicify, $fechaIngresoLicifyTeorica, $fechaRealIngresoLicify, $diasEntregaPliegos, $fechaEntregaPliegosTeorica, $fechaRealEntregaPliegos, $diasReciboPropuestas, $fechaReciboPropuestasTeorica, $fechaRealReciboPropuestas, $diasCuadrosComparativos, $fechaCuadrosComparativosTeorica, $fechaRealCuadrosComparativos, $diasLegalizacionContrato, $fechaLegalizacionContratoTeorica, $fechaRealLegalizacionContrato, $diasFabricacion, $fechaFabricacionTeorica, $fechaRealFabricacion, $diasInsumosObra, $fechaInsumosObraTeorica, $fechaRealInsumosObra, $fechaInicioTeorica, $fechaInicioProyectada, $fechaRealInicio, $observacionesContrato, $idProveedorExistente, $activoInformacionAdjudicacionProveedor, $Subcontratista, $email, $confirmar_correo, $NIT, $alcance, $tipo_proveedor, $activoInformacionAdjudicacionContrato, $numeroContrato, $aplicaPolizas, $fechaVencimientoPolizas, $valorPresupuesto, $valorPrimeraNegociacion, $valorAdjudicado, $valorAnticipo, $valorReclamado, $valorDevoluciones){
  $errores = "";
  if($activoInformacionAdjudicacionProveedor == 1){
    if($Subcontratista === "NULL" || $email === "NULL" || $confirmar_correo === "NULL" || $NIT === "NULL" || $alcance === "NULL" || $tipo_proveedor === "NULL"){
      $errores .= 'Debe rellenar todos los campos de la sección <b>"Información del Proveedor Adjudicado"</b>.<br>';
    }else{
      $query1= "SELECT (SELECT COUNT(*) FROM $db"."_subcontratistas WHERE id=$idProveedorExistente) AS conteo, (SELECT MAX(Id) FROM $db"."_subcontratistas) AS ultimoId";

      $resultado1= mysqli_query($conexion, $query1);
      $data1=mysqli_fetch_assoc($resultado1);
      $conteo=$data1["conteo"];
      $ultimoId=$data1["ultimoId"];

      if($email <> $confirmar_correo){
         $errores .= 'Por favor confirmar correctamente la dirección de correo.<br>';
      }else{
        if(!filter_var(str_replace("'", "", $email), FILTER_VALIDATE_EMAIL)){
          $errores .= 'Por favor asignar una dirección de correo real en la <b>Información del Proveedor Adjudicado</b> (ej. correo@aia.com.co).<br>';
        }
      }

      $longitudNIT = strlen((string)abs($NIT));
      if($longitudNIT < 7 || $longitudNIT > 10){
        $errores .= 'Por favor digite un NIT de 7 a 10 dígitos, sin dígito de verificación y sin guiones o puntos.<br>';
      }

      $arrayNIT = array("0", "1", "2", "3", "4", "5", "6", "7", "8", "9", "00", "11", "22", "33", "44", "55", "66", "77", "88", "99", "000", "111", "222", "333", "444", "555", "666", "777", "888", "999", "0000", "1111", "2222", "3333", "4444", "5555", "6666", "7777", "8888", "9999", "00000", "11111", "22222", "33333", "44444", "55555", "66666", "77777", "88888", "99999", "000000", "111111", "222222", "333333", "444444", "555555", "666666", "777777", "888888", "999999", "0000000", "1111111", "2222222", "3333333", "4444444", "5555555", "6666666", "7777777", "8888888", "9999999", "00000000", "11111111", "22222222", "33333333", "44444444", "55555555", "66666666", "77777777", "88888888", "99999999", "000000000", "111111111", "222222222", "333333333", "444444444", "555555555", "666666666", "777777777", "888888888", "999999999", "0000000000", "1111111111", "2222222222", "3333333333", "4444444444", "5555555555", "6666666666", "7777777777", "8888888888", "9999999999", "12", "123", "1234", "12345", "123456", "1234567", "12345678", "123456789", "1234567890", "01", "012", "0123", "01234", "012345", "0123456", "01234567", "012345678", "0123456789");
      if(in_array((string)$NIT, $arrayNIT)){
        $errores .= 'Por favor digite el NIT real del subcontratista.<br>';
      }

      if($errores == ""){
        if($conteo>0){
          $idProveedorAdjudicado = $idProveedorExistente;

          $query2 = "SELECT * FROM $db"."_subcontratistas WHERE id=$idProveedorAdjudicado";
          $resultado2= mysqli_query($conexion, $query2);
          $data2=mysqli_fetch_assoc($resultado2);
          $Subcontratista_Anterior=$data2["subcontratista"];
          $alcanceAnterior=verificarNulo($data2["alcance"]);

          if($alcanceAnterior == $alcance){
            $alcanceFinal = $alcanceAnterior;
          }else{
            if($alcanceAnterior == ""){
              $alcanceFinal = $alcance;
            }else{
              $alcanceFinal = "$alcanceAnterior; $alcance";
              $alcanceFinal = verificarNulo(str_replace("'", '', $alcanceFinal));
            }

          }

          $query3 = "UPDATE $db"."_subcontratistas SET subcontratista=$Subcontratista, correo_contacto=$email, NIT=$NIT, alcance=$alcanceFinal, tipo_proveedor=$tipo_proveedor WHERE id=$idProveedorAdjudicado";

          $resultado3= mysqli_query($conexion, $query3);

          $query4 = "UPDATE $db"."_programacion_semanal SET Sub_Contratista=$Subcontratista WHERE Sub_Contratista='$Subcontratista_Anterior';UPDATE $db"."_programa_consolidado SET Sub_Contratista=$Subcontratista WHERE Sub_Contratista='$Subcontratista_Anterior';UPDATE $db"."_cic SET subcontratista=$Subcontratista WHERE subcontratista='$Subcontratista_Anterior';UPDATE $db"."_indicadores_generales SET subcontratista_profesional=$Subcontratista WHERE subcontratista_profesional='$Subcontratista_Anterior';";

          $resultado4= mysqli_multi_query($conexion, $query4);
        }else{
          $idProveedorAdjudicado = $ultimoId + 1;

          $query2 = "INSERT INTO $db"."_subcontratistas (id, subcontratista, correo_contacto, NIT, alcance, tipo_proveedor) VALUES ($idProveedorAdjudicado, $Subcontratista, $confirmar_correo, $NIT, $alcance, $tipo_proveedor)";

          $resultado2= mysqli_query($conexion, $query2);
        }
      }
    }
  }else{
    $errores .= "";
    $idProveedorAdjudicado = "NULL";
  }
  
  if($activoInformacionAdjudicacionContrato == 1){
    if($numeroContrato === "NULL" || ($aplicaPolizas == 1 && $fechaVencimientoPolizas === "NULL") || $valorPrimeraNegociacion === "NULL" || $valorAdjudicado === "NULL" || $valorAnticipo === "NULL" || $valorReclamado === "NULL" || $valorDevoluciones === "NULL"){
      $errores .= 'Debe rellenar todos los campos de la sección <b>"Seguimiento al Contrato"</b>.<br>';
    }else if($valorPresupuesto <= 0){
      $errores .= 'El <b>Valor en Presupuesto</b> debe ser mayor a 0.';
    }else if($valorAdjudicado <= 0){
      $errores .= 'El <b>Valor Adjudicado</b> debe ser mayor a 0.';
    }else if($valorPrimeraNegociacion <= 0){
      $errores .= 'El <b>Valor Primera Negociación</b> debe ser mayor a 0.';
    }else{
      $errores .= $errores;
    }
  }else{
    if($valorPresupuesto === "NULL"){
      $errores .= 'Debe ingresar el Valor en Presupuesto en la sección <b>"Descripción del Proceso"</b>.<br>';
    }else{
      $errores .= $errores;
    }
  }

  if($errores == ""){

    $query = "UPDATE ".$db."_pdc SET `semana` = $semana, `contratos` = $contratos, `estado` = $estadoProceso, `fechaElaboracionPliegos` = $fechaElaboracionPliegosTeorica,`diasElaboracionPliegos` = $diasElaboracionPliegos,`fechaRealElaboracionPliegos` = $fechaRealElaboracionPliegos,`fechaIngresoLicify` = $fechaIngresoLicifyTeorica,`diasIngresoLicify` = $diasIngresoLicify,`fechaRealIngresoLicify` = $fechaRealIngresoLicify,`fechaEntregaPliegos` = $fechaEntregaPliegosTeorica,`diasEntregaPliegos` = $diasEntregaPliegos,`fechaRealEntregaPliegos` = $fechaRealEntregaPliegos,`fechaReciboPropuestas` = $fechaReciboPropuestasTeorica,`diasReciboPropuestas` = $diasReciboPropuestas,`fechaRealReciboPropuestas` = $fechaRealReciboPropuestas,`fechaCuadrosComparativos` = $fechaCuadrosComparativosTeorica,`diasCuadrosComparativos` = $diasCuadrosComparativos,`fechaRealCuadrosComparativos` = $fechaRealCuadrosComparativos,`fechaLegalizacionContrato` = $fechaLegalizacionContratoTeorica,`diasLegalizacionContrato` = $diasLegalizacionContrato,`fechaRealLegalizacionContrato` = $fechaRealLegalizacionContrato,`fechaFabricacion` = $fechaFabricacionTeorica,`diasFabricacion` = $diasFabricacion,`fechaRealFabricacion` = $fechaRealFabricacion,`fechaInsumosObra` = $fechaInsumosObraTeorica,`diasInsumosObra` = $diasInsumosObra,`fechaRealInsumosObra` = $fechaRealInsumosObra,`fechaInicio` = $fechaInicioContrato,`fechaInicioProyectada` = $fechaInicioProyectada,`fechaRealInicio` = $fechaRealInicio,`numeroContrato` = $numeroContrato, `aplicaPolizas` = $aplicaPolizas, `fechaVencimientoPolizas` = $fechaVencimientoPolizas, `valorPresupuesto` = $valorPresupuesto, `valorPrimeraNegociacion` = $valorPrimeraNegociacion, `valorAdjudicado` = $valorAdjudicado, `valorAnticipo` = $valorAnticipo, `valorReclamado` = $valorReclamado, `valorDevoluciones` = $valorDevoluciones, `idProveedorAdjudicado` = $idProveedorAdjudicado,`observacionesContrato` = $observacionesContrato WHERE `consecutivo`= $Id";

    require("../conexion.php");
    $resultado=mysqli_query($conexion, $query);

    if(!$resultado){
      //die(mysqli_error($conexion));
      $errores="error de conexion";
    }else{
      $errores='OK';
    }
  }
  mysqli_close($conexion);
  $json_codificado = json_encode($errores);
  echo utf8_decode($json_codificado);
}

function modificar_estado_act($Id, $semana, $inicio_semana, $db, $conexion){

    $fin_semana= date("Y-m-d",strtotime("$inicio_semana + 6 days"));

    $query = "UPDATE $db"."_programa_consolidado SET
       Estado= CASE
          WHEN Ejecutado = 1 AND ROUND((SELECT CASE WHEN (DATEDIFF(Fecha_Fin, Fecha_Inicio)+1) >= DATEDIFF('$inicio_semana', Fecha_Inicio) AND DATEDIFF('$inicio_semana', Fecha_Inicio) >= 1 THEN (DATEDIFF('$inicio_semana', Fecha_Inicio) / (DATEDIFF(Fecha_Fin, Fecha_Inicio)+1)) WHEN (DATEDIFF(Fecha_Fin, Fecha_Inicio)+1) < DATEDIFF('$inicio_semana', Fecha_Inicio) THEN 1 WHEN DATEDIFF('$inicio_semana', Fecha_Inicio) < 1 THEN 0 END) - Ejecutado,3) < 0 THEN 'Terminada Antes'

          WHEN Ejecutado = 1 AND ROUND((SELECT CASE WHEN (DATEDIFF(Fecha_Fin, Fecha_Inicio)+1) >= DATEDIFF('$inicio_semana', Fecha_Inicio) AND DATEDIFF('$inicio_semana', Fecha_Inicio) >= 1 THEN (DATEDIFF('$inicio_semana', Fecha_Inicio) / (DATEDIFF(Fecha_Fin, Fecha_Inicio)+1)) WHEN (DATEDIFF(Fecha_Fin, Fecha_Inicio)+1) < DATEDIFF('$inicio_semana', Fecha_Inicio) THEN 1 WHEN DATEDIFF('$inicio_semana', Fecha_Inicio) < 1 THEN 0 END) - Ejecutado,3) = 0 THEN 'Terminada'

          WHEN Ejecutado < 1 AND Ejecutado >= 0 AND ROUND((SELECT CASE WHEN (DATEDIFF(Fecha_Fin, Fecha_Inicio)+1) >= DATEDIFF('$inicio_semana', Fecha_Inicio) AND DATEDIFF('$inicio_semana', Fecha_Inicio) >= 1 THEN (DATEDIFF('$inicio_semana', Fecha_Inicio) / (DATEDIFF(Fecha_Fin, Fecha_Inicio)+1)) WHEN (DATEDIFF(Fecha_Fin, Fecha_Inicio)+1) < DATEDIFF('$inicio_semana', Fecha_Inicio) THEN 1 WHEN DATEDIFF('$inicio_semana', Fecha_Inicio) < 1 THEN 0 END) - Ejecutado,3) > 0 THEN 'Atrasada'

          WHEN Ejecutado < 1 AND Ejecutado > 0 AND ROUND((SELECT CASE WHEN (DATEDIFF(Fecha_Fin, Fecha_Inicio)+1) >= DATEDIFF('$inicio_semana', Fecha_Inicio) AND DATEDIFF('$inicio_semana', Fecha_Inicio) >= 1 THEN (DATEDIFF('$inicio_semana', Fecha_Inicio) / (DATEDIFF(Fecha_Fin, Fecha_Inicio)+1)) WHEN (DATEDIFF(Fecha_Fin, Fecha_Inicio)+1) < DATEDIFF('$inicio_semana', Fecha_Inicio) THEN 1 WHEN DATEDIFF('$inicio_semana', Fecha_Inicio) < 1 THEN 0 END) - Ejecutado,3) <= 0 THEN 'A Tiempo'

          WHEN Semanas_Inicio <= 0 AND Estado_Restricciones = 1 AND ROUND((SELECT CASE WHEN (DATEDIFF(Fecha_Fin, Fecha_Inicio)+1) >= DATEDIFF('$inicio_semana', Fecha_Inicio) AND DATEDIFF('$inicio_semana', Fecha_Inicio) >= 1 THEN (DATEDIFF('$inicio_semana', Fecha_Inicio) / (DATEDIFF(Fecha_Fin, Fecha_Inicio)+1)) WHEN (DATEDIFF(Fecha_Fin, Fecha_Inicio)+1) < DATEDIFF('$inicio_semana', Fecha_Inicio) THEN 1 WHEN DATEDIFF('$inicio_semana', Fecha_Inicio) < 1 THEN 0 END),3) = 0 AND Ejecutado=0 THEN 'Debe Iniciar esta Semana'

          WHEN Semanas_Inicio <= 0 AND Estado_Restricciones < 1 AND ROUND((SELECT CASE WHEN (DATEDIFF(Fecha_Fin, Fecha_Inicio)+1) >= DATEDIFF('$inicio_semana', Fecha_Inicio) AND DATEDIFF('$inicio_semana', Fecha_Inicio) >= 1 THEN (DATEDIFF('$inicio_semana', Fecha_Inicio) / (DATEDIFF(Fecha_Fin, Fecha_Inicio)+1)) WHEN (DATEDIFF(Fecha_Fin, Fecha_Inicio)+1) < DATEDIFF('$inicio_semana', Fecha_Inicio) THEN 1 WHEN DATEDIFF('$inicio_semana', Fecha_Inicio) < 1 THEN 0 END) - Ejecutado,3) > 0 AND Ejecutado=0 THEN 'Ya Debió Iniciar y Restricciones Pendientes'

          WHEN Semanas_Inicio <= 0 AND Estado_Restricciones < 1 AND ROUND((SELECT CASE WHEN (DATEDIFF(Fecha_Fin, Fecha_Inicio)+1) >= DATEDIFF('$inicio_semana', Fecha_Inicio) AND DATEDIFF('$inicio_semana', Fecha_Inicio) >= 1 THEN (DATEDIFF('$inicio_semana', Fecha_Inicio) / (DATEDIFF(Fecha_Fin, Fecha_Inicio)+1)) WHEN (DATEDIFF(Fecha_Fin, Fecha_Inicio)+1) < DATEDIFF('$inicio_semana', Fecha_Inicio) THEN 1 WHEN DATEDIFF('$inicio_semana', Fecha_Inicio) < 1 THEN 0 END),3) = 0 AND Ejecutado=0 THEN 'Debe Iniciar esta Semana y Restricciones Pendientes'

          WHEN Semanas_Inicio > 0 AND Semanas_Inicio <= 6 AND Ejecutado = 0 THEN 'En Liberación de Restricciones'

          WHEN Semanas_Inicio > 0 AND Semanas_Inicio <= 6 AND Ejecutado > 0 THEN 'A Tiempo'

          ELSE 'No Requerida'
       END
      WHERE Titulo=0 AND Consecutivo_en_Programa='$Id' AND Semana=$semana";
    //echo $query;
    $resultado=mysqli_multi_query($conexion, $query);
    mysqli_close($conexion);
}

function guardar_DefinirContratos($numeroSubcontratosArray, $conexion, $db){
  $query = "";
  foreach($numeroSubcontratosArray AS $data){
    $consecutivo = $data["consecutivo"];
    $numeroSubcontratos = $data["numeroSubcontratos"];
    $query .= "UPDATE ".$db."_pdc SET numeroSubcontratos = $numeroSubcontratos WHERE consecutivo = $consecutivo; ";
  }
  //$json_codificado = json_encode($numeroSubcontratosConsolidado[0]);
  if(empty($query)){
    echo utf8_decode(json_encode("sinModificaciones"));
  }else{
    $resultado=mysqli_multi_query($conexion, $query);
    if(!$resultado){
      //die(mysqli_error($conexion));
      echo utf8_decode(json_encode("error de conexion"));
    }else{
      echo utf8_decode(json_encode("conModificaciones"));
    }
  }
}

function eliminar($Id, $paqueteContratacion, $semana, $db, $conexion){
  $query="DELETE FROM $db"."_pdc WHERE consecutivo=$Id";
  $resultado=mysqli_query($conexion, $query);
  if(!$resultado){
    die(mysqli_error($conexion));
  }else{
    $query2="UPDATE $db"."_pdc SET numeroSubcontratos=(SELECT (SELECT numeroSubcontratos FROM $db"."_pdc WHERE paqueteContratacion='$paqueteContratacion' AND subcontratoPaquete=1 AND semana=$semana)-1) WHERE paqueteContratacion='$paqueteContratacion' AND subcontratoPaquete=1 AND semana=$semana";
    $resultado2=mysqli_query($conexion, $query2);
    if(!$resultado2){
      die(mysqli_error($conexion));
    }else{
      $errores='';
      verificar_resultado($resultado2="OK", $errores);
    }
  }
  mysqli_close($conexion);
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


function verificar_resultado($resultado){
    if(!$resultado) $informacion["respuesta"] ="ERROR";
    else $informacion["respuesta"] = "BIEN";
    echo json_encode($informacion);
}

function cerrar($conexion){
    mysqli_close($conexion);
}

function fecha_inicio_sem($semana, $db, $conexion){
    //require("../conexion.php");
    $query="SELECT COUNT(*) FROM $db"."_semanas_activas";
    $resultado= mysqli_query($conexion, $query);
    $data=mysqli_fetch_assoc($resultado);
    $conteo=$data["COUNT(*)"];

    if($conteo==0){
        $inicio_semana=date("Y-m-d");

    }else{
        $query1="SELECT Fecha_Inicio_Sem, Fecha_Fin_Sem FROM $db"."_semanas_activas WHERE Semana=$semana";
        $resultado1= mysqli_query($conexion, $query1);
        $data1=mysqli_fetch_assoc($resultado1);
        $inicio_semana=$data1["Fecha_Inicio_Sem"];
        //echo $inicio_semana;
    }


    return $inicio_semana;
}





?>
