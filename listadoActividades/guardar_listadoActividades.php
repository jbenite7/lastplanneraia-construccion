<?php session_start();
require("../conexion.php");

$db=/*"concejo_bogota_pc"¨*/$_GET['db'];
$opcion=/*"modificar"¨*/$_POST["opcion"];
$informacion=[];
// $_POST['Id']=6;
// $_POST['codigo']='';
// $_POST['actividad']="Mortero de Piso en Concreto";
// $_POST['descripcionActividad']="Vaciado y nivelación de pisos en concreto";
// $_POST['fechaInicio']="2021-12-15";
// $_POST['tipoContrato']=1;


if ($_SERVER['REQUEST_METHOD']== 'POST' && $opcion == "registrar"){

    if(empty($_POST['codigo'])){
        $codigo='';
    } else{
        $codigo=$_POST['codigo'];
    }

    if(empty($_POST['actividad'])){
        $Actividad='';
    } else{
        $Actividad=filter_var(($_POST['actividad']), FILTER_SANITIZE_STRING);
    }

    if (empty($_POST['descripcionActividad'])){
        $descripcionActividad='';
    } else{
        $descripcionActividad=filter_var(($_POST['descripcionActividad']), FILTER_SANITIZE_STRING);
    }

    if(empty($_POST['actividadInicio'])){
        $actividadInicio="NULL";
    } else{
        $actividadInicio= "'" . $_POST['actividadInicio'] . "'";
    }

    if(empty($_POST['fechaInicio'])){
     $fechaInicio="NULL";
    } else{
     $fechaInicio= "'" . date("Y-m-d",strtotime($_POST["fechaInicio"])) . "'";
    }

    if(empty($_POST['tipoContrato'])){
        $tipoContrato='';
    } else{
        $tipoContrato=$_POST['tipoContrato'];
    }

    if(empty($_POST['semana'])){
        $semana='';
    } else{
        $semana=$_POST['semana'];
    }

    $errores='';

    if(empty($Actividad) or  empty($descripcionActividad) or empty($fechaInicio) or empty($tipoContrato) or empty($semana)){
        $errores .='Debe rellenar todos los campos';
        $resultado=false;

    } else {
        $query= "SELECT COUNT(*) FROM $db"."_actividades WHERE actividad = '$Actividad' LIMIT 1";

        $resultado= mysqli_query($conexion, $query);
        $data=mysqli_fetch_assoc($resultado);
        $conteo=$data["COUNT(*)"];

        if($conteo > 0){
            $errores .= 'La actividad que estás intentando registrar ya existe';
        }

        $query= "SELECT MAX(codigo) FROM $db"."_actividades";
        $resultado= mysqli_query($conexion, $query);
        $data=mysqli_fetch_assoc($resultado);
        $codigo=$data["MAX(codigo)"];

        if(empty($codigo)){
          $codigo=0;
        }
        $codigo++;

        if($errores==''){
            $query = "INSERT INTO $db"."_actividades (id, codigo, actividad, descripcionActividad, actividadInicio, nombreActividadInicio, fechaInicio, tipoContrato, semanaActualizacion) VALUES (null, $codigo, '$Actividad', '$descripcionActividad', $actividadInicio, (SELECT Actividad FROM $db"."_programa_consolidado WHERE Semana = $semana AND Actividad = $actividadInicio), $fechaInicio, $tipoContrato, $semana)";

            $resultado= mysqli_query($conexion, $query);
        }
    }

    verificar_resultado($resultado, $errores);
    mysqli_close($conexion);
}else if($opcion=="modificar"){
    $Id=$_POST['Id'];

    if(empty($_POST['codigo'])){
        $codigo='';
    } else{
        $codigo=$_POST['codigo'];
    }

    if(empty($_POST['Actividad'])){
        $Actividad='';
    } else{
        $Actividad=filter_var(($_POST['Actividad']), FILTER_SANITIZE_STRING);
    }

    if (empty($_POST['descripcionActividad'])){
        $descripcionActividad='';
    } else{
        $descripcionActividad=filter_var(($_POST['descripcionActividad']), FILTER_SANITIZE_STRING);
    }

    if(empty($_POST['actividadInicio'])){
        $actividadInicio="NULL";
    } else{
        $actividadInicio= "'" . $_POST['actividadInicio'] . "'";
    }

    if(empty($_POST['fechaInicio'])){
     $fechaInicio="NULL";
    } else{
     $fechaInicio= "'" . date("Y-m-d",strtotime($_POST["fechaInicio"])) . "'";
    }

    if(empty($_POST['tipoContrato'])){
        $tipoContrato='';
    } else{
        $tipoContrato=$_POST['tipoContrato'];
    }

    if(empty($_POST['semana'])){
        $semana='';
    } else{
        $semana=$_POST['semana'];
    }

    $errores='';
    //echo "$Id, $codigo, $Actividad, $descripcionActividad, $actividadInicio, $fechaInicio, $tipoContrato";

    if(empty($Actividad) or  empty($descripcionActividad) or empty($fechaInicio) or empty($tipoContrato) or empty($semana)){
        $errores .='Debe rellenar todos los campos';
        $resultado=false;

    } else {
      $query = "UPDATE $db"."_actividades SET actividad='$Actividad', descripcionActividad='$descripcionActividad', actividadInicio=$actividadInicio, nombreActividadInicio=(SELECT Actividad FROM $db"."_programa_consolidado WHERE Semana = $semana AND Actividad = $actividadInicio), fechaInicio=$fechaInicio, tipoContrato=$tipoContrato , semanaActualizacion=$semana WHERE Id=$Id";
      $resultado= mysqli_query($conexion, $query);
    }

    verificar_resultado($resultado, $errores);
    mysqli_close($conexion);


}else if($opcion=="eliminar"){
    $Id=/*4*/$_POST["Id"];
    eliminar($Id, $db, $conexion);
}else if($opcion=="actualizarFechaInicio"){
    $Id=/*4*/$_POST["idActividad"];
    $nombreActividad=/*4*/$_POST["nombreActividad"];
    $semana=$_POST["semana"];
    actualizarFechaInicio($Id, $nombreActividad, $semana, $db, $conexion);
}else if($opcion == "cargarExcel"){
  $archivoExcel=$_FILES["archivoExcel"];
  cargarExcel($conexion, $db, $archivoExcel);
}

function eliminar($Id, $db, $conexion){
  $query="DELETE FROM $db"."_actividades WHERE Id=$Id";
  $resultado=mysqli_query($conexion, $query);
  $errores='';
  verificar_resultado($resultado="OK", $errores);
  mysqli_close($conexion);
}

function actualizarFechaInicio($Id, $nombreActividad, $semana, $db, $conexion){
  $query="SELECT (Fecha_Inicio) FROM $db"."_programa_consolidado WHERE Actividad='$Id' AND Semana=$semana";
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

function cargarExcel($conexion, $db, $archivoExcel){
  $semanaActualizacion = $_SESSION["Max_Semana"];
  $info = new SplFileInfo($archivoExcel["name"]);
  $extension = pathinfo($info->getFilename(), PATHINFO_EXTENSION);
  if($extension == "csv"){
    $filename = $archivoExcel['tmp_name'];
	  $handle = fopen($filename, "r");
    $numeroFila = 0;
    $query = "TRUNCATE TABLE ".$db."_actividades";
    $resultado=mysqli_query($conexion, $query);
    if(!$resultado){
    } else{
      $query = "INSERT INTO ".$db."_actividades (Id, codigo, actividad, descripcionActividad, semanaActualizacion) VALUES ";
      while(($data = fgetcsv($handle, 10000, ";"))!==FALSE){
        if($numeroFila != 0){
          $query .= "('', $numeroFila, '".utf8_encode($data[0])."', '".utf8_encode($data[1])."', $semanaActualizacion), ";
        }
        $numeroFila++;
      }
      $query = substr($query,0,-2);
      $resultado=mysqli_query($conexion, $query);
      if(!$resultado){
        $errores = "No carga desde excel";
      } else{
        $errores = "";
      }
    }

    verificar_resultado($resultado, $errores);
    mysqli_close($conexion);
  }
}

function verificar_resultado($resultado, $errores){
    if(!$resultado){
        $informacion["respuesta"] ="ERROR";
    }
    if($errores ==''){
        $informacion["respuesta"] = "BIEN";
    }
    if ($errores=='Debe rellenar todos los campos'){
        $informacion["respuesta"] = "VACIO";
    }
    if ($errores=='La actividad que estás intentando registrar ya existe'){
        $informacion["respuesta"] = "EXISTE";
    }
    if ($errores=='No se puede eliminar esta actividad'){
        $informacion["respuesta"] = "NO_ELIMINAR";
    }
    echo json_encode($informacion);
}

function cerrar($conexion){
    mysqli_close($conexion);
}
?>
