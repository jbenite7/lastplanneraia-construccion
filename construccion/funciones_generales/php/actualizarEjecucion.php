<?php session_start();
  require("../../conexion.php");

  $db=/*"cross"*/$_POST['db'];
  $semana=/*"nueva_sem"*/$_POST["semana"];
  $semanaAnterior = ($semana - 1);

  // $query_f_inicio_sem = "SELECT * FROM $db"."_semanas_activas WHERE Semana=$semana";
  // $resultado_f_inicio_sem= mysqli_query($conexion, $query_f_inicio_sem);
  // $data_f_inicio_sem=mysqli_fetch_assoc($resultado_f_inicio_sem);

  // $f_inicio_sem = date("Y-m-d",strtotime($data_f_inicio_sem["Fecha_Inicio_Sem"]));
  $f_inicio_sem = date("Y-m-d",strtotime($_POST["f_inicio_sem"]));



  $query ="SELECT Actividad, Consecutivo_En_Programa, Id, Ejecutado, Unidad, cantidad_ppto, Compromiso, Ejecutado_Real, Responsable_AIA, Sub_Contratista FROM $db"."_programacion_semanal WHERE Semana=$semanaAnterior AND (Activa='1' OR Activa='NA')";
  $resultado= mysqli_query($conexion, $query);
  if(!$resultado){
    die("Error");
  }else{
    $query1 = "";
    $conteo_actividades = 0;
    while($data=mysqli_fetch_assoc($resultado)){
      $arreglo["data"][]=array_map("utf8_encode", $data);

      $Actividad=$data["Actividad"];
      $Actividad=str_replace("'","\'",$Actividad);
      $Actividad=str_replace('"','\"',$Actividad);
      $Consecutivo_en_Programa=$data["Consecutivo_En_Programa"];
      $Id = $data["Id"];
      $Ejecutado=$data["Ejecutado"];
      $unidad=$data["Unidad"];
      $cantidad_ppto=$data["cantidad_ppto"];
      $Responsable_AIA=$data["Responsable_AIA"];
      $Sub_Contratista=$data["Sub_Contratista"];

      $Compromiso=$data["Compromiso"];
      $Ejecutado_Real=$data["Ejecutado_Real"];


      if($cantidad_ppto==0 || $cantidad_ppto==NULL || $cantidad_ppto==''){
        $cantidad_ppto=100;
      }
      if($Ejecutado_Real==0 || $Ejecutado_Real==NULL || $Ejecutado_Real==''){
        $Ejecutado_fin_semana= $Ejecutado;
      }else{
        $Ejecutado_fin_semana= ($Ejecutado_Real / $cantidad_ppto) + $Ejecutado;
      }

      $query1 .= "UPDATE $db"."_programa_consolidado SET Ejecutado='$Ejecutado_fin_semana', Responsable_AIA='$Responsable_AIA', Sub_Contratista='$Sub_Contratista' WHERE Semana=$semana AND (Actividad='$Actividad' OR programaAnteriorAsociar='$Actividad') ; ";

      $conteo_actividades++;
    }

    $conteoPDC = 0;
    if($conteo_actividades == 0){
      $ejecucionActualizada = 0;
      $semanalConfirmada = 1;
      $respuesta = array($semana, $conteoPDC, $ejecucionActualizada, $semanalConfirmada);
      echo utf8_decode(json_encode($respuesta));
    }else{
      $resultado1= mysqli_multi_query($conexion, $query1);
      require ("../../conexion.php");
      sleep(1);
      $ejecucionActualizada = 1;
      $semanalConfirmada = 1;
      require("modificar_sem_estado.php");
    }
    mysqli_free_result($resultado);
  }



?>
