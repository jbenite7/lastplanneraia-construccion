<?php session_start();
  require("../../conexion.php");

  // function verificar_resultado($resultado){
  //     if(!$resultado) $informacion["respuesta"] ="ERROR";
  //     else $informacion["respuesta"] = "BIEN";
  //     echo json_encode($informacion);
  // }

  $db=/*"cross"*/$_GET['db'];
  $opcion=/*"nueva_sem"*/$_POST["opcion"];
  $f_inicio_sem=date("Y-m-d",strtotime(/*"2020-01-03"*/$_POST["f_inicio_sem"]));
  $pdcActivo = $_SESSION['pdcActivo'];
  $permiso = $_SESSION['permiso'];

  // $db="brizaDelCabrero";
  // $opcion="nueva_sem";
  // $f_inicio_sem=date("Y-m-d",strtotime("2022-03-22"));
  // $pdcActivo=1;

  $query="SELECT  COUNT(*) FROM $db"."_semanas_activas";
  $resultado= mysqli_query($conexion, $query);
  if(!$resultado){
      $conteo=0;
  }else{
      $data=mysqli_fetch_assoc($resultado);
      $conteo=$data["COUNT(*)"];
      mysqli_free_result($resultado);
  }

  $queryVerificarSemanalConfirmada = "SELECT Semanal_Confirmada FROM $db"."_semanas_activas WHERE Semana=$conteo";
  $resultadoVerificarSemanalConfirmada= mysqli_query($conexion, $queryVerificarSemanalConfirmada);
  if(!$resultadoVerificarSemanalConfirmada){
    die("Error");
  }else{
    $dataVerificarSemanalConfirmada=mysqli_fetch_assoc($resultadoVerificarSemanalConfirmada);
    $semanalConfirmada = $dataVerificarSemanalConfirmada["Semanal_Confirmada"];
    if($semanalConfirmada == 0 && $permiso != "P"){
      $respuesta = array($conteo, $conteoPDC = 0, $ejecucionActualizada = 0, $semanalConfirmada);
      echo utf8_decode(json_encode($respuesta));
    }else{
      $semana_crear=$conteo+1;
      $f_fin_sem= date("Y-m-d",strtotime($f_inicio_sem."+ 6 days"));
      $fCreacionSemana= date("Y-m-d");


      $query3 ="INSERT INTO $db"."_semanas_activas (Id, Semana, Fecha_Inicio_Sem, Fecha_Fin_Sem, fechaCreacionSemana) VALUES (NULL, $semana_crear, '$f_inicio_sem', '$f_fin_sem', '$fCreacionSemana');";
      $resultado= mysqli_query($conexion, $query3);
      $errores="";
      //verificar_resultado($resultado, $errores);


      if($conteo==0){
          $query4 = "INSERT INTO $db"."_programa_consolidado(Consecutivo, Semana, Consecutivo_en_Programa, Id, Actividad, Titulo, Fecha_Inicio, Fecha_Fin, Ruta_Critica, medir_productividad, Ejecutado, Estado, Semanas_Inicio, Estado_Restricciones, D_y_E, Materiales, MdeO, Equipos, Predecesora, Pdto_Cons, Modelo, Responsable_AIA, Observaciones, Ult_Act_Est, Ult_Act_Restr, Ejecutado_Siguiente_Semana)";

          $query4 .= "SELECT NULL, $semana_crear, $db"."_programa.`Consecutivo`, $db"."_programa.`Id`, $db"."_programa.`Actividad`, $db"."_programa.`Titulo`, $db"."_programa.`Fecha_Inicio`, $db"."_programa.`Fecha_Fin`, $db"."_programa.`Ruta_Critica`, 0, $db"."_programa.`Ejecutado`, $db"."_programa.`Estado`, $db"."_programa.`Semanas_Inicio`, $db"."_programa.`Estado_Restricciones`, $db"."_programa.`D_y_E`, $db"."_programa.`Materiales`, $db"."_programa.`MdeO`, $db"."_programa.`Equipos`, $db"."_programa.`Predecesora`, $db"."_programa.`Pdto_Cons`, $db"."_programa.`Modelo`, $db"."_programa.`Responsable_AIA`, $db"."_programa.`Observaciones`, $db"."_programa.`Ult_Act_Est`, $db"."_programa.`Ult_Act_Restr`, $db"."_programa.`Ejecutado` FROM $db"."_programa;";

          $resultado2= mysqli_query($conexion, $query4);

          sleep(1);

          $query1 ="UPDATE $db"."_programa_consolidado SET Semanas_Inicio=NULL, medir_productividad=NULL, unidad=NULL, cantidad_ppto=NULL, codigo_actividad=NULL, Ejecutado=NULL, D_y_E=NULL, Materiales=NULL, MdeO=NULL, Equipos=NULL, Predecesora=NULL, Pdto_Cons=NULL, Modelo=NULL, Sub_Contratista=NULL, Responsable_AIA=NULL, Observaciones=NULL, Ult_Act_Est=NULL, Ult_Act_Restr=NULL, Activa=0, Ejecutado_Siguiente_Semana=NULL WHERE Semana=$semana_crear AND Titulo=1";
          $resultado1= mysqli_query($conexion, $query1);
      }else{

          $queryMaxSemanaProgramaConsolidado="SELECT  MAX(Semana) FROM $db"."_programa_consolidado";
          $resultadoMaxSemanaProgramaConsolidado= mysqli_query($conexion, $queryMaxSemanaProgramaConsolidado);
          if(!$resultadoMaxSemanaProgramaConsolidado){
            $dataMaxSemanaProgramaConsolidado = $conteo;
          }else{
            $dataMaxSemanaProgramaConsolidado=mysqli_fetch_assoc($resultadoMaxSemanaProgramaConsolidado);
            $maxSemanaProgramaConsolidado=$dataMaxSemanaProgramaConsolidado["MAX(Semana)"];
            mysqli_free_result($resultadoMaxSemanaProgramaConsolidado);
          }

          if($maxSemanaProgramaConsolidado == $semana_crear){

            $queryActualizarActividadesAsociadas = "UPDATE `$db"."_programa_consolidado` ";
            $queryActualizarActividadesAsociadas .= "INNER JOIN (SELECT * FROM $db"."_programa_consolidado WHERE Semana=$conteo AND Titulo=0) AS `tablaActividadesAsociadas` ";
            $queryActualizarActividadesAsociadas .= "ON `$db"."_programa_consolidado`.programaAnteriorAsociar = `tablaActividadesAsociadas`.Actividad ";
            $queryActualizarActividadesAsociadas .= "SET `$db"."_programa_consolidado`.Ejecutado = `tablaActividadesAsociadas`.Ejecutado, ";
            $queryActualizarActividadesAsociadas .= "`$db"."_programa_consolidado`.Estado = `tablaActividadesAsociadas`.Estado, ";
            $queryActualizarActividadesAsociadas .= "`$db"."_programa_consolidado`.Semanas_Inicio = `tablaActividadesAsociadas`.Semanas_Inicio, ";
            $queryActualizarActividadesAsociadas .= "`$db"."_programa_consolidado`.Estado_Restricciones = `tablaActividadesAsociadas`.Estado_Restricciones, ";
            $queryActualizarActividadesAsociadas .= "`$db"."_programa_consolidado`.D_y_E = `tablaActividadesAsociadas`.D_y_E, ";
            $queryActualizarActividadesAsociadas .= "`$db"."_programa_consolidado`.Materiales = `tablaActividadesAsociadas`.Materiales, ";
            $queryActualizarActividadesAsociadas .= "`$db"."_programa_consolidado`.MdeO = `tablaActividadesAsociadas`.MdeO, ";
            $queryActualizarActividadesAsociadas .= "`$db"."_programa_consolidado`.Equipos = `tablaActividadesAsociadas`.Equipos, ";
            $queryActualizarActividadesAsociadas .= "`$db"."_programa_consolidado`.Predecesora = `tablaActividadesAsociadas`.Predecesora, ";
            $queryActualizarActividadesAsociadas .= "`$db"."_programa_consolidado`.Pdto_Cons = `tablaActividadesAsociadas`.Pdto_Cons, ";
            $queryActualizarActividadesAsociadas .= "`$db"."_programa_consolidado`.Modelo = `tablaActividadesAsociadas`.Modelo, ";
            $queryActualizarActividadesAsociadas .= "`$db"."_programa_consolidado`.Sub_Contratista = `tablaActividadesAsociadas`.Sub_Contratista, ";
            $queryActualizarActividadesAsociadas .= "`$db"."_programa_consolidado`.Responsable_AIA = `tablaActividadesAsociadas`.Responsable_AIA, ";
            $queryActualizarActividadesAsociadas .= "`$db"."_programa_consolidado`.Observaciones = `tablaActividadesAsociadas`.Observaciones, ";
            $queryActualizarActividadesAsociadas .= "`$db"."_programa_consolidado`.Ult_Act_Est = `tablaActividadesAsociadas`.Ult_Act_Est, ";
            $queryActualizarActividadesAsociadas .= "`$db"."_programa_consolidado`.Ult_Act_Restr = `tablaActividadesAsociadas`.Ult_Act_Restr, ";
            $queryActualizarActividadesAsociadas .= "`$db"."_programa_consolidado`.Activa = `tablaActividadesAsociadas`.Activa, ";
            $queryActualizarActividadesAsociadas .= "`$db"."_programa_consolidado`.Ejecutado_Siguiente_Semana = `tablaActividadesAsociadas`.Ejecutado_Siguiente_Semana, ";
            $queryActualizarActividadesAsociadas .= "`$db"."_programa_consolidado`.codigo_actividad = `tablaActividadesAsociadas`.codigo_actividad, ";
            $queryActualizarActividadesAsociadas .= "`$db"."_programa_consolidado`.medir_productividad = `tablaActividadesAsociadas`.medir_productividad, ";
            $queryActualizarActividadesAsociadas .= "`$db"."_programa_consolidado`.cantidad_ppto = `tablaActividadesAsociadas`.cantidad_ppto, ";
            $queryActualizarActividadesAsociadas .= "`$db"."_programa_consolidado`.unidad = `tablaActividadesAsociadas`.unidad ";
            $queryActualizarActividadesAsociadas .= "WHERE `$db"."_programa_consolidado`.Semana=$semana_crear";

            // echo utf8_decode(json_encode($queryActualizarActividadesAsociadas));

            $resultadoActualizarActividadesAsociadas= mysqli_query($conexion, $queryActualizarActividadesAsociadas);

            if(!$resultadoActualizarActividadesAsociadas){
              die("Error");
            }else{
              $queryRegistrarReprogramacion = "UPDATE $db"."_semanas_activas SET reprogramacion=1, diferenciaEstructuraCron=(SELECT COUNT(*) FROM $db"."_programa_consolidado WHERE Semana=$semana_crear AND Fecha_Inicio IS NOT NULL AND Fecha_Fin IS NOT NULL AND Titulo != 1 AND (Ejecutado IS NULL OR Estado_Restricciones IS NULL OR programaAnteriorAsociar IS NOT NULL)) WHERE Semana=$semana_crear";

              $resultadoRegistrarReprogramacion= mysqli_query($conexion, $queryRegistrarReprogramacion);

              if(!$resultadoRegistrarReprogramacion){
                die("Error");
              }
            }

          }else{
            $query4 = "INSERT INTO $db"."_programa_consolidado(Consecutivo, Semana, Consecutivo_en_Programa, Id, Actividad, Titulo, Fecha_Inicio, Fecha_Fin, Ruta_Critica, unidad, cantidad_ppto, codigo_actividad, medir_productividad, Ejecutado, Estado, Semanas_Inicio, Estado_Restricciones, D_y_E, Materiales, MdeO, Equipos, Predecesora, Pdto_Cons, Modelo, Sub_Contratista, Responsable_AIA, Observaciones, Ult_Act_Est, Ult_Act_Restr, Ejecutado_Siguiente_Semana)";

            $query4 .= "SELECT NULL, $semana_crear, $db"."_programa_consolidado.`Consecutivo_en_Programa`, $db"."_programa_consolidado.`Id`, $db"."_programa_consolidado.`Actividad`, $db"."_programa_consolidado.`Titulo`, $db"."_programa_consolidado.`Fecha_Inicio`, $db"."_programa_consolidado.`Fecha_Fin`, $db"."_programa_consolidado.`Ruta_Critica`, $db"."_programa_consolidado.`unidad`, $db"."_programa_consolidado.`cantidad_ppto`, $db"."_programa_consolidado.`codigo_actividad`, $db"."_programa_consolidado.`medir_productividad`, (SELECT CASE WHEN $db"."_programa_consolidado.`Ejecutado` IS NULL THEN 0 ELSE $db"."_programa_consolidado.`Ejecutado` END), $db"."_programa_consolidado.`Estado`, $db"."_programa_consolidado.`Semanas_Inicio`, $db"."_programa_consolidado.`Estado_Restricciones`, $db"."_programa_consolidado.`D_y_E`, $db"."_programa_consolidado.`Materiales`, $db"."_programa_consolidado.`MdeO`, $db"."_programa_consolidado.`Equipos`, $db"."_programa_consolidado.`Predecesora`, $db"."_programa_consolidado.`Pdto_Cons`, $db"."_programa_consolidado.`Modelo`, $db"."_programa_consolidado.`Sub_Contratista`, $db"."_programa_consolidado.`Responsable_AIA`, $db"."_programa_consolidado.`Observaciones`, $db"."_programa_consolidado.`Ult_Act_Est`, $db"."_programa_consolidado.`Ult_Act_Restr`, $db"."_programa_consolidado.`Ejecutado_Siguiente_Semana` FROM $db"."_programa_consolidado WHERE Semana=$conteo;";

            $resultado2= mysqli_query($conexion, $query4);

            sleep(1);
          }



          $query1 ="UPDATE $db"."_programa_consolidado SET Semanas_Inicio=NULL, medir_productividad=NULL, unidad=NULL, cantidad_ppto=NULL, codigo_actividad=NULL, Ejecutado=NULL, D_y_E=NULL, Materiales=NULL, MdeO=NULL, Equipos=NULL, Predecesora=NULL, Pdto_Cons=NULL, Modelo=NULL, Sub_Contratista=NULL, Responsable_AIA=NULL, Observaciones=NULL, Ult_Act_Est=NULL, Ult_Act_Restr=NULL, Activa=0, Ejecutado_Siguiente_Semana=NULL WHERE Semana=$semana_crear AND Titulo=1";
          $resultado1= mysqli_query($conexion, $query1);

          $query1 ="UPDATE $db"."_programa_consolidado SET Ejecutado = 0, Estado_Restricciones = '0', D_y_E = '0', Materiales = '0', MdeO = '0', Equipos = '0', Predecesora = '0', Pdto_Cons = '0', Modelo = '0'  WHERE Ejecutado IS NULL AND Semana=$semana_crear AND Titulo=0";
          $resultado1= mysqli_query($conexion, $query1);

          sleep(1);

          $query5 ="SELECT Actividad, Consecutivo_En_Programa, Id, Ejecutado, Unidad, cantidad_ppto, Compromiso, Ejecutado_Real, Responsable_AIA, Sub_Contratista FROM $db"."_programacion_semanal WHERE Semana=$conteo AND (Activa='1' OR Activa='NA') AND (Ejecutado_Real IS NOT NULL AND Ejecutado_Real != '' AND Ejecutado_Real !=0)";
          $resultado5= mysqli_query($conexion, $query5);
          if(!$resultado5){
              die("Error");
          } else{
              $query6 = "";
              $conteo_actividades =0;
              while($data5=mysqli_fetch_assoc($resultado5)){
                  $arreglo["data"][]=array_map("utf8_encode", $data5);

                  $Actividad=$data5["Actividad"];
                  $Actividad=str_replace("'","\'",$Actividad);
                  $Actividad=str_replace('"','\"',$Actividad);
                  $Consecutivo_en_Programa=$data5["Consecutivo_En_Programa"];
                  $Id = $data5["Id"];
                  $Ejecutado=$data5["Ejecutado"];
                  $unidad=$data5["Unidad"];
                  $cantidad_ppto=$data5["cantidad_ppto"];
                  $Responsable_AIA=$data5["Responsable_AIA"];
                  $Sub_Contratista=$data5["Sub_Contratista"];

                  $Compromiso=$data5["Compromiso"];
                  $Ejecutado_Real=$data5["Ejecutado_Real"];


                  if($cantidad_ppto==0 || $cantidad_ppto==NULL || $cantidad_ppto==''){
                    $cantidad_ppto=100;
                  }
                  if($Ejecutado_Real==0 || $Ejecutado_Real==NULL || $Ejecutado_Real==''){
                    $Ejecutado_fin_semana= $Ejecutado;
                  }else{
                    $Ejecutado_fin_semana= ($Ejecutado_Real / $cantidad_ppto) + $Ejecutado;
                  }

                  $query6 .= "UPDATE $db"."_programa_consolidado SET Ejecutado='$Ejecutado_fin_semana', Responsable_AIA='$Responsable_AIA', Sub_Contratista='$Sub_Contratista' WHERE Semana=$semana_crear AND (Actividad='$Actividad' OR programaAnteriorAsociar='$Actividad') ; ";

                  $conteo_actividades++;
              }

              if($conteo_actividades == 0){
              }else{
                  $resultado6= mysqli_multi_query($conexion, $query6);
                  require ("../../conexion.php");
              }
              mysqli_free_result($resultado5);
          }
          sleep(1);



          if($pdcActivo == 1){
            $query7 = "SELECT * FROM `$db"."_actividades` WHERE semanaActualizacion = $conteo";
            //echo $query7;
            $resultado7= mysqli_query($conexion, $query7);
            if(!$resultado7){
              die("Error");
            }else{
              $query8 = "INSERT INTO `$db"."_actividades`( `codigo`, `actividad`, `descripcionActividad`, `actividadInicio`, `nombreActividadInicio`, `fechaInicio`, `tipoContrato`, `semanaActualizacion`, `SI1`, `paqueteSI1`, `SI2`, `paqueteSI2`, `SI3`, `paqueteSI3`, `SI4`, `paqueteSI4`, `SI5`, `paqueteSI5`, `S1`, `paqueteS1`, `S2`, `paqueteS2`, `S3`, `paqueteS3`, `S4`, `paqueteS4`, `S5`, `paqueteS5`, `MO1`, `paqueteMO1`, `MO2`, `paqueteMO2`, `MO3`, `paqueteMO3`, `MO4`, `paqueteMO4`, `MO5`, `paqueteMO5`) VALUES ";
              $conteo_actividades = 0;
              while($data7=mysqli_fetch_assoc($resultado7)){
                $codigo = $data7["codigo"];
                $actividad = $data7["actividad"];
                $actividad=str_replace('"','\"',$actividad);
								$actividad=str_replace("'","\'",$actividad);
                $descripcionActividad = $data7["descripcionActividad"];
                $actividadInicio = $data7["actividadInicio"];
                $nombreActividadInicio = $data7["nombreActividadInicio"];
                $nombreActividadInicio=str_replace('"','\"',$nombreActividadInicio);
								$nombreActividadInicio=str_replace("'","\'",$nombreActividadInicio);
                $fechaInicio = ($data7["fechaInicio"] == null || $data7["fechaInicio"] == "" || empty($data7["fechaInicio"])) ? "NULL" : "'" . $data7["fechaInicio"] . "'";
                if(empty($data7["tipoContrato"]) || $data7["tipoContrato"] == null || $data7["tipoContrato"] == ""){
                  $tipoContrato = "NULL";
                }else{
                  $tipoContrato = $data7["tipoContrato"];
                  $tipoContrato = "\"$tipoContrato\"";
                }

                $semanaActualizacion = $semana_crear;
                $SI1 = $data7["SI1"];
                $paqueteSI1 = $data7["paqueteSI1"];
                $SI2 = $data7["SI2"];
                $paqueteSI2 = $data7["paqueteSI2"];
                $SI3 = $data7["SI3"];
                $paqueteSI3 = $data7["paqueteSI3"];
                $SI4 = $data7["SI4"];
                $paqueteSI4 = $data7["paqueteSI4"];
                $SI5 = $data7["SI5"];
                $paqueteSI5 = $data7["paqueteSI5"];
                $S1 = $data7["S1"];
                $paqueteS1 = $data7["paqueteS1"];
                $S2 = $data7["S2"];
                $paqueteS2 = $data7["paqueteS2"];
                $S3 = $data7["S3"];
                $paqueteS3 = $data7["paqueteS3"];
                $S4 = $data7["S4"];
                $paqueteS4 = $data7["paqueteS4"];
                $S5 = $data7["S5"];
                $paqueteS5 = $data7["paqueteS5"];
                $MO1 = $data7["MO1"];
                $paqueteMO1 = $data7["paqueteMO1"];
                $MO2 = $data7["MO2"];
                $paqueteMO2 = $data7["paqueteMO2"];
                $MO3 = $data7["MO3"];
                $paqueteMO3 = $data7["paqueteMO3"];
                $MO4 = $data7["MO4"];
                $paqueteMO4 = $data7["paqueteMO4"];
                $MO5 = $data7["MO5"];
                $paqueteMO5 = $data7["paqueteMO5"];

                $query8 .= "($codigo, \"$actividad\", \"$descripcionActividad\", (SELECT CASE WHEN (SELECT COUNT(programaAnteriorAsociar) FROM $db"."_programa_consolidado WHERE Semana = $semana_crear AND programaAnteriorAsociar = \"$nombreActividadInicio\")>0 THEN (SELECT Actividad FROM $db"."_programa_consolidado WHERE Semana = $semana_crear AND programaAnteriorAsociar = \"$nombreActividadInicio\" ORDER BY Fecha_Inicio ASC LIMIT 1) WHEN (SELECT COUNT(Actividad) FROM $db"."_programa_consolidado WHERE Semana = $semana_crear AND Actividad = \"$nombreActividadInicio\")>0 THEN (SELECT Actividad FROM $db"."_programa_consolidado WHERE Semana = $semana_crear AND Actividad = \"$nombreActividadInicio\" ORDER BY Fecha_Inicio ASC LIMIT 1)  ELSE NULL END), (SELECT CASE WHEN (SELECT COUNT(programaAnteriorAsociar) FROM $db"."_programa_consolidado WHERE Semana = $semana_crear AND programaAnteriorAsociar = \"$nombreActividadInicio\")>0 THEN (SELECT Actividad FROM $db"."_programa_consolidado WHERE Semana = $semana_crear AND programaAnteriorAsociar = \"$nombreActividadInicio\" ORDER BY Fecha_Inicio ASC LIMIT 1) WHEN (SELECT COUNT(Actividad) FROM $db"."_programa_consolidado WHERE Semana = $semana_crear AND Actividad = \"$nombreActividadInicio\")>0 THEN (SELECT Actividad FROM $db"."_programa_consolidado WHERE Semana = $semana_crear AND Actividad = \"$nombreActividadInicio\" ORDER BY Fecha_Inicio ASC LIMIT 1)  ELSE NULL END), (SELECT CASE WHEN (SELECT COUNT(programaAnteriorAsociar) FROM $db"."_programa_consolidado WHERE Semana = $semana_crear AND programaAnteriorAsociar = \"$nombreActividadInicio\")>0 THEN (SELECT Fecha_Inicio FROM $db"."_programa_consolidado WHERE Semana = $semana_crear AND programaAnteriorAsociar = \"$nombreActividadInicio\" ORDER BY Fecha_Inicio ASC LIMIT 1) WHEN (SELECT COUNT(Actividad) FROM $db"."_programa_consolidado WHERE Semana = $semana_crear AND Actividad = \"$nombreActividadInicio\")>0 THEN (SELECT Fecha_Inicio FROM $db"."_programa_consolidado WHERE Semana = $semana_crear AND Actividad = \"$nombreActividadInicio\" ORDER BY Fecha_Inicio ASC LIMIT 1)  ELSE $fechaInicio END), " . $tipoContrato . ", $semana_crear, \"$SI1\", \"$paqueteSI1\", \"$SI2\", \"$paqueteSI2\", \"$SI3\", \"$paqueteSI3\", \"$SI4\", \"$paqueteSI4\", \"$SI5\", \"$paqueteSI5\", \"$S1\", \"$paqueteS1\", \"$S2\", \"$paqueteS2\", \"$S3\", \"$paqueteS3\", \"$S4\", \"$paqueteS4\", \"$S5\", \"$paqueteS5\", \"$MO1\", \"$paqueteMO1\", \"$MO2\", \"$paqueteMO2\", \"$MO3\", \"$paqueteMO3\", \"$MO4\", \"$paqueteMO4\", \"$MO5\", \"$paqueteMO5\"), ";

                $conteo_actividades ++;
              }
              if($conteo_actividades == 0){
                $conteoPDC = 0;
              }else{
                $query8 = substr($query8, 0, -2);
                //echo $query8;
                $resultado8= mysqli_query($conexion, $query8);
                
                sleep(1);

                $query10 = "SELECT * FROM `$db"."_pdc` WHERE semana = $conteo";

                $resultado10= mysqli_query($conexion, $query10);
                if(!$resultado10){
                  $conteoPDC = 0;
                  // die("Error");
                }else{
                  $conteoPDC = 1;

                  $query10 = "INSERT INTO `$db"."_pdc`(`semana`, `titulo`, `tipoPaquete`, `paqueteContratacion`, `contratos`, `numeroSubcontratos`, `subcontratoPaquete`, `estado`, `fechaElaboracionPliegos`, `diasElaboracionPliegos`, `fechaRealElaboracionPliegos`, `fechaIngresoLicify`, `diasIngresoLicify`, `fechaRealIngresoLicify`, `fechaEntregaPliegos`, `diasEntregaPliegos`, `fechaRealEntregaPliegos`, `fechaReciboPropuestas`, `diasReciboPropuestas`, `fechaRealReciboPropuestas`, `fechaCuadrosComparativos`, `diasCuadrosComparativos`, `fechaRealCuadrosComparativos`, `fechaLegalizacionContrato`, `diasLegalizacionContrato`, `fechaRealLegalizacionContrato`, `fechaFabricacion`, `diasFabricacion`, `fechaRealFabricacion`, `fechaInsumosObra`, `diasInsumosObra`, `fechaRealInsumosObra`, `fechaInicio`, `fechaInicioProyectada`, `fechaRealInicio`, `idProveedorAdjudicado`, `numeroContrato`, `fechaVencimientoPolizas`, `valorPresupuesto`, `valorPrimeraNegociacion`, `valorAdjudicado`, `valorAnticipo`, `valorReclamado`, `valorDevoluciones`, `observacionesContrato`)";

                  $query10 .= " SELECT $semana_crear, `titulo`, `tipoPaquete`, `paqueteContratacion`, `contratos`, `numeroSubcontratos`, `subcontratoPaquete`, `estado`, `fechaElaboracionPliegos`, `diasElaboracionPliegos`, `fechaRealElaboracionPliegos`, `fechaIngresoLicify`, `diasIngresoLicify`, `fechaRealIngresoLicify`, `fechaEntregaPliegos`, `diasEntregaPliegos`, `fechaRealEntregaPliegos`, `fechaReciboPropuestas`, `diasReciboPropuestas`, `fechaRealReciboPropuestas`, `fechaCuadrosComparativos`, `diasCuadrosComparativos`, `fechaRealCuadrosComparativos`, `fechaLegalizacionContrato`, `diasLegalizacionContrato`, `fechaRealLegalizacionContrato`, `fechaFabricacion`, `diasFabricacion`, `fechaRealFabricacion`, `fechaInsumosObra`, `diasInsumosObra`, `fechaRealInsumosObra`, `fechaInicio`, `fechaInicioProyectada`, `fechaRealInicio`, `idProveedorAdjudicado`, `numeroContrato`, `fechaVencimientoPolizas`, `valorPresupuesto`, `valorPrimeraNegociacion`, `valorAdjudicado`, `valorAnticipo`, `valorReclamado`, `valorDevoluciones`, `observacionesContrato` FROM `$db"."_pdc` WHERE `semana`= $conteo";

                  //echo $query7;
                  $resultado10= mysqli_query($conexion, $query10);
                  sleep(1);
                }
              }
            }

            $semana = $semana_crear;
            //require("actualizar_pdc_nueva_semana.php");
          }

      }

      $semana=$semana_crear;
      $ejecucionActualizada = 1;
      require("modificar_sem_estado.php");
    }


  }





?>
