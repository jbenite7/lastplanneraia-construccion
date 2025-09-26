<?php
require("../conexion.php");

$db=$_GET['db'];
$opcion=$_POST["opcion"];

// $db="brizaDelCabrero";
// $opcion="actualizarInsumosRecursos";

$informacion=[];

if($_SERVER['REQUEST_METHOD']== 'POST' && $opcion == "nuevo"){
    $inputConsecutivo=$_POST['inputConsecutivo'];
    $inputProyecto=$_POST['inputProyecto'];
    $inputDirector=$_POST['inputDirector'];
    $inputFechaSolicitud=$_POST['inputFechaSolicitud'];
    $inputSolicitanteCambio= empty($_POST['inputSolicitanteCambio']) ? null : $_POST['inputSolicitanteCambio'];
    $inputDetalleSolicitanteOtro= empty($_POST['inputDetalleSolicitanteOtro']) ? null : $_POST['inputDetalleSolicitanteOtro'];
    $inputPrioridad= empty($_POST['inputPrioridad']) ? null : $_POST['inputPrioridad'];
    if(empty($_POST['inputTipoCambioAlcance'])){
      $inputTipoCambioAlcance=0;
    }else{
      $inputTipoCambioAlcance=$_POST['inputTipoCambioAlcance'];
    }
    if(empty($_POST['inputTipoCambioCronograma'])){
      $inputTipoCambioCronograma=0;
    }else{
      $inputTipoCambioCronograma=$_POST['inputTipoCambioCronograma'];
    }
    if(empty($_POST['inputTipoCambioCosto'])){
      $inputTipoCambioCosto=0;
    }else{
      $inputTipoCambioCosto=$_POST['inputTipoCambioCosto'];
    }
    if(empty($_POST['inputTipoCambioCalidad'])){
      $inputTipoCambioCalidad=0;
    }else{
      $inputTipoCambioCalidad=$_POST['inputTipoCambioCalidad'];
    }
    if(empty($_POST['inputTipoCambioRiesgo'])){
      $inputTipoCambioRiesgo=0;
    }else{
      $inputTipoCambioRiesgo=$_POST['inputTipoCambioRiesgo'];
    }
    if(empty($_POST['inputTipoCambioRecurso'])){
      $inputTipoCambioRecurso=0;
    }else{
      $inputTipoCambioRecurso=$_POST['inputTipoCambioRecurso'];
    }
    $inputTipoCambio = '{\"tiposCambio\":{\"Alcance\":\"'.$inputTipoCambioAlcance.'\",\"Cronograma\":\"'.$inputTipoCambioCronograma.'\",\"Costo\":\"'.$inputTipoCambioCosto.'\",\"Calidad\":\"'.$inputTipoCambioCalidad.'\",\"Riesgo\":\"'.$inputTipoCambioRiesgo.'\",\"Recurso\":\"'.$inputTipoCambioRecurso.'\"}}';
    $inputResponsableSolucion= empty($_POST['inputResponsableSolucion']) ? null : $_POST['inputResponsableSolucion'];
    $inputDetalleResponsableSolucion= empty($_POST['inputDetalleResponsableSolucion']) ? null : $_POST['inputDetalleResponsableSolucion'];
    $inputJustificacion= empty($_POST['inputJustificacion']) ? null : $_POST['inputJustificacion'];
    $inputDescripcion= empty($_POST['inputDescripcion']) ? null : $_POST['inputDescripcion'];
    $inputIncidenciaAlcance= empty($_POST['inputIncidenciaAlcance']) ? null : $_POST['inputIncidenciaAlcance'];
    $inputTiempoCronograma= empty($_POST['inputTiempoCronograma']) ? 0 : floatval(str_replace(['$', ','], '', $_POST['inputTiempoCronograma']));
    $inputTiempoCronogramaAfectado= empty($_POST['inputTiempoCronogramaAfectado']) ? 0 : floatval(str_replace(['$', ','], '', $_POST['inputTiempoCronogramaAfectado']));
    $inputIncidenciaCronograma= empty($_POST['inputIncidenciaCronograma']) ? null : $_POST['inputIncidenciaCronograma'];
    $inputValorPresupuesto= empty($_POST['inputValorPresupuesto']) ? 0 : floatval(str_replace(['$', ','], '', $_POST['inputValorPresupuesto']));
    $inputCostoDirecto= empty($_POST['inputCostoDirecto']) ? 0 : floatval(str_replace(['$', ','], '', $_POST['inputCostoDirecto']));
    $inputCostoDirectoAIU= empty($_POST['inputCostoDirectoAIU']) ? 0 : floatval(str_replace(['$', ','], '', $_POST['inputCostoDirectoAIU']));
    $inputCostoDirectoAIUIVA= empty($_POST['inputCostoDirectoAIUIVA']) ? 0 : floatval(str_replace(['$', ','], '', $_POST['inputCostoDirectoAIUIVA']));
    $inputValorAprobado= empty($_POST['inputValorAprobado']) ? 0 : floatval(str_replace(['$', ','], '', $_POST['inputValorAprobado']));
    $inputIncidenciaPresupuesto= empty($_POST['inputIncidenciaPresupuesto']) ? null : $_POST['inputIncidenciaPresupuesto'];
    $inputIncidenciaCalidad= empty($_POST['inputIncidenciaCalidad']) ? null : $_POST['inputIncidenciaCalidad'];
    $inputIncidenciaRiesgo= empty($_POST['inputIncidenciaRiesgo']) ? null : $_POST['inputIncidenciaRiesgo'];
    $inputIncidenciaRecurso= empty($_POST['inputIncidenciaRecurso']) ? null : $_POST['inputIncidenciaRecurso'];
    $inputFechaEntregaInterventoria= empty($_POST['inputFechaEntregaInterventoria']) ? null : $_POST['inputFechaEntregaInterventoria'];
    $inputFechaTentativaDefinicion= empty($_POST['inputFechaTentativaDefinicion']) ? null : $_POST['inputFechaTentativaDefinicion'];
    $inputAprobacion= empty($_POST['inputAprobacion']) ? null : $_POST['inputAprobacion'];
    $inputFechaDefinicion= empty($_POST['inputFechaDefinicion']) ? null : $_POST['inputFechaDefinicion'];
    $soportes=$_POST['soportes'];
    $errores='';

     if(!empty($errores)){
      $resultado=false;
    } else {
      $query = "INSERT INTO $db"."_cambios (`id`, `solicitanteCambio`, `detalleSolicitanteOtro`, `fechaSolicitud`, `prioridad`, `tipoCambio`, `responsableSolucion`, `detalleResponsableSolucion`, `justificacion`, `descripcion`, `incidenciaAlcance`,`tiempoCronograma`, `tiempoCronogramaAfectado`, `incidenciaCronograma`, `valorPresupuesto`, `costoDirecto`, `costoDirectoAIU`, `costoDirectoAIUIVA`, `valorAprobado`, `incidenciaPresupuesto`, `incidenciaCalidad`, `incidenciaRiesgo`, `incidenciaRecurso`, `fechaTentativaDefinicion`, `fechaEntregaInterventoria`, `Observaciones`, `fechaDefinicion`, `aprobacion`, `soportes`) VALUES ($inputConsecutivo," . ($inputSolicitanteCambio === null ? "NULL" : "'". $inputSolicitanteCambio ."'") . "," . ($inputDetalleSolicitanteOtro === null ? "NULL" : "'". $inputDetalleSolicitanteOtro ."'") . "," . ($inputFechaSolicitud === null ? "NULL" : "'". $inputFechaSolicitud ."'") . "," . ($inputPrioridad === null ? "NULL" : "'". $inputPrioridad ."'") . ",'$inputTipoCambio'," . ($inputResponsableSolucion === null ? "NULL" : "'". $inputResponsableSolucion ."'") . "," . ($inputDetalleResponsableSolucion === null ? "NULL" : "'". $inputDetalleResponsableSolucion ."'") . "," . ($inputJustificacion === null ? "NULL" : "'". $inputJustificacion ."'") . "," . ($inputDescripcion === null ? "NULL" : "'". $inputDescripcion ."'") . "," . ($inputIncidenciaAlcance === null ? "NULL" : "'". $inputIncidenciaAlcance ."'") . ", $inputTiempoCronograma, $inputTiempoCronogramaAfectado, " . ($inputIncidenciaCronograma === null ? "NULL" : "'". $inputIncidenciaCronograma ."'") . ",$inputValorPresupuesto,$inputCostoDirecto,$inputCostoDirectoAIU,$inputCostoDirectoAIUIVA,$inputValorAprobado," . ($inputIncidenciaPresupuesto === null ? "NULL" : "'". $inputIncidenciaPresupuesto ."'") . "," . ($inputIncidenciaCalidad === null ? "NULL" : "'". $inputIncidenciaCalidad ."'") . "," . ($inputIncidenciaRiesgo === null ? "NULL" : "'". $inputIncidenciaRiesgo ."'") . "," . ($inputIncidenciaRecurso === null ? "NULL" : "'". $inputIncidenciaRecurso ."'") . "," . ($inputFechaTentativaDefinicion === null ? "NULL" : "'". $inputFechaTentativaDefinicion ."'") . "," . ($inputFechaEntregaInterventoria === null ? "NULL" : "'". $inputFechaEntregaInterventoria ."'") . ",NULL," . ($inputFechaDefinicion === null ? "NULL" : "'". $inputFechaDefinicion ."'") . "," . ($inputAprobacion === null ? "NULL" : "'". $inputAprobacion ."'") . "," . ($soportes === null ? "NULL" : "'". $soportes ."'").")";

      //echo $query;

      $resultado= mysqli_query($conexion, $query);
    }

    verificar_resultado($resultado, $errores);
    mysqli_close($conexion); 


}else if($_SERVER['REQUEST_METHOD']== 'POST' && $opcion == "modificar"){
  $inputConsecutivo=$_POST['inputConsecutivo'];
  $inputProyecto=$_POST['inputProyecto'];
  $inputDirector=$_POST['inputDirector'];
  $inputFechaSolicitud=$_POST['inputFechaSolicitud'];
  $inputSolicitanteCambio=$_POST['inputSolicitanteCambio'];
  $inputDetalleSolicitanteOtro= empty($_POST['inputDetalleSolicitanteOtro']) ? null : $_POST['inputDetalleSolicitanteOtro'];
  $inputPrioridad=$_POST['inputPrioridad'];
  if(empty($_POST['inputTipoCambioAlcance'])){
    $inputTipoCambioAlcance=0;
  }else{
    $inputTipoCambioAlcance=$_POST['inputTipoCambioAlcance'];
  }
  if(empty($_POST['inputTipoCambioCronograma'])){
    $inputTipoCambioCronograma=0;
  }else{
    $inputTipoCambioCronograma=$_POST['inputTipoCambioCronograma'];
  }
  if(empty($_POST['inputTipoCambioCosto'])){
    $inputTipoCambioCosto=0;
  }else{
    $inputTipoCambioCosto=$_POST['inputTipoCambioCosto'];
  }
  if(empty($_POST['inputTipoCambioCalidad'])){
    $inputTipoCambioCalidad=0;
  }else{
    $inputTipoCambioCalidad=$_POST['inputTipoCambioCalidad'];
  }
  if(empty($_POST['inputTipoCambioRiesgo'])){
    $inputTipoCambioRiesgo=0;
  }else{
    $inputTipoCambioRiesgo=$_POST['inputTipoCambioRiesgo'];
  }
  if(empty($_POST['inputTipoCambioRecurso'])){
    $inputTipoCambioRecurso=0;
  }else{
    $inputTipoCambioRecurso=$_POST['inputTipoCambioRecurso'];
  }
  $inputTipoCambio = '{\"tiposCambio\":{\"Alcance\":\"'.$inputTipoCambioAlcance.'\",\"Cronograma\":\"'.$inputTipoCambioCronograma.'\",\"Costo\":\"'.$inputTipoCambioCosto.'\",\"Calidad\":\"'.$inputTipoCambioCalidad.'\",\"Riesgo\":\"'.$inputTipoCambioRiesgo.'\",\"Recurso\":\"'.$inputTipoCambioRecurso.'\"}}';
  $inputResponsableSolucion= empty($_POST['inputResponsableSolucion']) ? null : $_POST['inputResponsableSolucion'];
  $inputDetalleResponsableSolucion= empty($_POST['inputDetalleResponsableSolucion']) ? null : $_POST['inputDetalleResponsableSolucion'];
  $inputJustificacion= empty($_POST['inputJustificacion']) ? null : $_POST['inputJustificacion'];
  $inputDescripcion= empty($_POST['inputDescripcion']) ? null : $_POST['inputDescripcion'];
  $inputIncidenciaAlcance= empty($_POST['inputIncidenciaAlcance']) ? null : $_POST['inputIncidenciaAlcance'];
  $inputTiempoCronograma= empty($_POST['inputTiempoCronograma']) ? 0 : floatval(str_replace(['$', ','], '', $_POST['inputTiempoCronograma']));
  $inputTiempoCronogramaAfectado= empty($_POST['inputTiempoCronogramaAfectado']) ? 0 : floatval(str_replace(['$', ','], '', $_POST['inputTiempoCronogramaAfectado']));
  $inputIncidenciaCronograma= empty($_POST['inputIncidenciaCronograma']) ? null : $_POST['inputIncidenciaCronograma'];
  $inputValorPresupuesto= empty($_POST['inputValorPresupuesto']) ? 0 : floatval(str_replace(['$', ','], '', $_POST['inputValorPresupuesto']));
  $inputCostoDirecto= empty($_POST['inputCostoDirecto']) ? 0 : floatval(str_replace(['$', ','], '', $_POST['inputCostoDirecto']));
  $inputCostoDirectoAIU= empty($_POST['inputCostoDirectoAIU']) ? 0 : floatval(str_replace(['$', ','], '', $_POST['inputCostoDirectoAIU']));
  $inputCostoDirectoAIUIVA= empty($_POST['inputCostoDirectoAIUIVA']) ? 0 : floatval(str_replace(['$', ','], '', $_POST['inputCostoDirectoAIUIVA']));
  $inputValorAprobado= empty($_POST['inputValorAprobado']) ? 0 : floatval(str_replace(['$', ','], '', $_POST['inputValorAprobado']));
  $inputIncidenciaPresupuesto= empty($_POST['inputIncidenciaPresupuesto']) ? null : $_POST['inputIncidenciaPresupuesto'];
  $inputIncidenciaCalidad= empty($_POST['inputIncidenciaCalidad']) ? null : $_POST['inputIncidenciaCalidad'];
  $inputIncidenciaRiesgo= empty($_POST['inputIncidenciaRiesgo']) ? null : $_POST['inputIncidenciaRiesgo'];
  $inputIncidenciaRecurso= empty($_POST['inputIncidenciaRecurso']) ? null : $_POST['inputIncidenciaRecurso'];
  $inputFechaEntregaInterventoria= empty($_POST['inputFechaEntregaInterventoria']) ? null : $_POST['inputFechaEntregaInterventoria'];
  $inputFechaTentativaDefinicion= empty($_POST['inputFechaTentativaDefinicion']) ? null : $_POST['inputFechaTentativaDefinicion'];
  $inputAprobacion=$_POST['inputAprobacion'];
  $inputFechaDefinicion= empty($_POST['inputFechaDefinicion']) ? null : $_POST['inputFechaDefinicion'];
  $soportes=$_POST['soportes'];
  $errores='';

   if(!empty($errores)){
    $resultado=false;
  } else {
    $query = "UPDATE $db"."_cambios SET `solicitanteCambio`=$inputSolicitanteCambio,`detalleSolicitanteOtro`=" . ($inputDetalleSolicitanteOtro === null ? "NULL" : "'". $inputDetalleSolicitanteOtro ."'") . ",`fechaSolicitud`=" . ($inputFechaSolicitud === null ? "NULL" : "'". $inputFechaSolicitud ."'") . ",`prioridad`=$inputPrioridad,`tipoCambio`='$inputTipoCambio',`responsableSolucion`=$inputResponsableSolucion,`detalleResponsableSolucion`=" . ($inputDetalleResponsableSolucion === null ? "NULL" : "'". $inputDetalleResponsableSolucion ."'") . ",`justificacion`=" . ($inputJustificacion === null ? "NULL" : "'". $inputJustificacion ."'") . ",`descripcion`=" . ($inputDescripcion === null ? "NULL" : "'". $inputDescripcion ."'") . ",`incidenciaAlcance`=" . ($inputIncidenciaAlcance === null ? "NULL" : "'". $inputIncidenciaAlcance ."'") . ", `tiempoCronograma`=$inputTiempoCronograma, `tiempoCronogramaAfectado`=$inputTiempoCronogramaAfectado, `incidenciaCronograma`=" . ($inputIncidenciaCronograma === null ? "NULL" : "'". $inputIncidenciaCronograma ."'") . ",`valorPresupuesto`=$inputValorPresupuesto,`costoDirecto`=$inputCostoDirecto,`costoDirectoAIU`=$inputCostoDirectoAIU,`costoDirectoAIUIVA`=$inputCostoDirectoAIUIVA,`valorAprobado`=$inputValorAprobado,`incidenciaPresupuesto`=" . ($inputIncidenciaPresupuesto === null ? "NULL" : "'". $inputIncidenciaPresupuesto ."'") . ",`incidenciaCalidad`=" . ($inputIncidenciaCalidad === null ? "NULL" : "'". $inputIncidenciaCalidad ."'") . ",`incidenciaRiesgo`=" . ($inputIncidenciaRiesgo === null ? "NULL" : "'". $inputIncidenciaRiesgo ."'") . ",`incidenciaRecurso`=" . ($inputIncidenciaRecurso === null ? "NULL" : "'". $inputIncidenciaRecurso ."'") . ",`fechaTentativaDefinicion`=" . ($inputFechaTentativaDefinicion === null ? "NULL" : "'". $inputFechaTentativaDefinicion ."'") . ",`fechaEntregaInterventoria`=" . ($inputFechaEntregaInterventoria === null ? "NULL" : "'". $inputFechaEntregaInterventoria ."'") . ",`Observaciones`=NULL,`fechaDefinicion`=" . ($inputFechaDefinicion === null ? "NULL" : "'". $inputFechaDefinicion ."'") . ",`aprobacion`=$inputAprobacion,`soportes`=" . ($soportes === null ? "NULL" : "'". $soportes ."'") . " WHERE `id`=$inputConsecutivo";

    //echo $query;

    $resultado= mysqli_query($conexion, $query);
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
}else if($_SERVER['REQUEST_METHOD']== 'POST' && $opcion=="obtenerNombreDirector"){
    obtenerNombreDirector($db, $conexion);
}else if($_SERVER['REQUEST_METHOD']== 'POST' && $opcion=="obtenerURLCambios"){
    obtenerURLCambios($db, $conexion);
}

function nueva_sem($f_inicio_sem, $db, $conexion){
    require("../funciones_generales/nueva_semana.php");
    //mysqli_close($conexion);
    //require("../conexion.php");
    require("../funciones_generales/modificar_sem_estado.php");
}

function obtenerNombreDirector($db, $conexion){
    $query="SELECT (nombre) FROM $db"."_profesionales WHERE cargo='Director de Obra' LIMIT 1";
    $resultado=mysqli_query($conexion, $query);
    if(!$resultado){
        die("Error");
    }else{
        $data=mysqli_fetch_assoc($resultado);
        $nombre=$data["nombre"];
        $json_codificado = json_encode($nombre, JSON_UNESCAPED_UNICODE);
        echo utf8_decode($json_codificado);
    }
    mysqli_close($conexion);
}

function obtenerURLCambios($db, $conexion){
  $query="SELECT (urlCambios) FROM general_proyectos_procesos WHERE Base_de_Datos='$db' LIMIT 1";
  $resultado=mysqli_query($conexion, $query);
  if(!$resultado){
      die("Error");
  }else{
      $data=mysqli_fetch_assoc($resultado);
      $urlCambios=$data["urlCambios"];
      $json_codificado = json_encode($urlCambios, JSON_UNESCAPED_UNICODE);
      echo utf8_decode($json_codificado);
  }
  mysqli_close($conexion);
}


function eliminar_sem($semana, $db, $conexion){
    require("../funciones_generales/eliminar_semana.php");
}

function eliminar($Id, $db, $conexion){
    $query="DELETE FROM $db"."_cambios WHERE id=$Id";
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
