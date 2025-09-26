<?php
require("../conexion.php");

$db=/*"concejo_bogota_pc"*/$_GET['db'];
$opcion=/*"modificar"*/$_POST["opcion"];
$informacion=[];
// $_POST['Id']=6;
// $_POST['codigo']='';
// $_POST['actividad']="Mortero de Piso en Concreto";
// $_POST['descripcionActividad']="Vaciado y nivelación de pisos en concreto";
// $_POST['fechaInicio']="2021-12-15";
// $_POST['tipoContrato']=1;


if($_SERVER['REQUEST_METHOD']== 'POST' && $opcion == "modificar"){
    $Id=$_POST['Id'];
    $errores='';

    if(empty($_POST['Semana'])){
        $semanaActualizacion='';
    } else{
        $semanaActualizacion=$_POST['Semana'];
    }

    if(!$_POST['SI1'] || empty($_POST['SI1'])){
      $SI1=NULL;
      $paqueteSI1=NULL;
    }else{
        if(!$_POST['paqueteSI1'] || empty($_POST['paqueteSI1'])){
            $errores .= 'No está definido el paquete de contratación al que pertenece el contrato "' . $_POST['SI1'] . '"; ';
            $SI1=NULL;
            $paqueteSI1=NULL;
        } else{
            $SI1=$_POST['SI1'];
            $paqueteSI1=$_POST['paqueteSI1'];
        }
    }

    if(!$_POST['SI2'] || empty($_POST['SI2'])){
      $SI2=NULL;
      $paqueteSI2=NULL;
    }else{
        if(!$_POST['paqueteSI2'] || empty($_POST['paqueteSI2'])){
            $errores .= 'No está definido el paquete de contratación al que pertenece el contrato "' . $_POST['SI2'] . '"; ';
            $SI2=NULL;
            $paqueteSI2=NULL;
        } else{
            $SI2=$_POST['SI2'];
            $paqueteSI2=$_POST['paqueteSI2'];
        }
    }

    if(!$_POST['SI3'] || empty($_POST['SI3'])){
      $SI3=NULL;
      $paqueteSI3=NULL;
    }else{
        if(!$_POST['paqueteSI3'] || empty($_POST['paqueteSI3'])){
            $errores .= 'No está definido el paquete de contratación al que pertenece el contrato "' . $_POST['SI3'] . '"; ';
            $SI3=NULL;
            $paqueteSI3=NULL;
        } else{
            $SI3=$_POST['SI3'];
            $paqueteSI3=$_POST['paqueteSI3'];
        }
    }

    if(!$_POST['SI4'] || empty($_POST['SI4'])){
      $SI4=NULL;
      $paqueteSI4=NULL;
    }else{
        if(!$_POST['paqueteSI4'] || empty($_POST['paqueteSI4'])){
            $errores .= 'No está definido el paquete de contratación al que pertenece el contrato "' . $_POST['SI4'] . '"; ';
            $SI4=NULL;
            $paqueteSI4=NULL;
        } else{
            $SI4=$_POST['SI4'];
            $paqueteSI4=$_POST['paqueteSI4'];
        }
    }

    if(!$_POST['SI5'] || empty($_POST['SI5'])){
      $SI5=NULL;
      $paqueteSI5=NULL;
    }else{
        if(!$_POST['paqueteSI5'] || empty($_POST['paqueteSI5'])){
            $errores .= 'No está definido el paquete de contratación al que pertenece el contrato "' . $_POST['SI5'] . '"; ';
            $SI5=NULL;
            $paqueteSI5=NULL;
        } else{
            $SI5=$_POST['SI5'];
            $paqueteSI5=$_POST['paqueteSI5'];
        }
    }

    if(!$_POST['S1'] || empty($_POST['S1'])){
      $S1=NULL;
      $paqueteS1=NULL;
    }else{
        if(!$_POST['paqueteS1'] || empty($_POST['paqueteS1'])){
            $errores .= 'No está definido el paquete de contratación al que pertenece el contrato "' . $_POST['S1'] . '"; ';
            $S1=NULL;
            $paqueteS1=NULL;
        } else{
            $S1=$_POST['S1'];
            $paqueteS1=$_POST['paqueteS1'];
        }
    }

    if(!$_POST['S2'] || empty($_POST['S2'])){
      $S2=NULL;
      $paqueteS2=NULL;
    }else{
        if(!$_POST['paqueteS2'] || empty($_POST['paqueteS2'])){
            $errores .= 'No está definido el paquete de contratación al que pertenece el contrato "' . $_POST['S2'] . '"; ';
            $S2=NULL;
            $paqueteS2=NULL;
        } else{
            $S2=$_POST['S2'];
            $paqueteS2=$_POST['paqueteS2'];
        }
    }

    if(!$_POST['S3'] || empty($_POST['S3'])){
      $S3=NULL;
      $paqueteS3=NULL;
    }else{
        if(!$_POST['paqueteS3'] || empty($_POST['paqueteS3'])){
            $errores .= 'No está definido el paquete de contratación al que pertenece el contrato "' . $_POST['S3'] . '"; ';
            $S3=NULL;
            $paqueteS3=NULL;
        } else{
            $S3=$_POST['S3'];
            $paqueteS3=$_POST['paqueteS3'];
        }
    }

    if(!$_POST['S4'] || empty($_POST['S4'])){
      $S4=NULL;
      $paqueteS4=NULL;
    }else{
        if(!$_POST['paqueteS4'] || empty($_POST['paqueteS4'])){
            $errores .= 'No está definido el paquete de contratación al que pertenece el contrato "' . $_POST['S4'] . '"; ';
            $S4=NULL;
            $paqueteS4=NULL;
        } else{
            $S4=$_POST['S4'];
            $paqueteS4=$_POST['paqueteS4'];
        }
    }

    if(!$_POST['S5'] || empty($_POST['S5'])){
      $S5=NULL;
      $paqueteS5=NULL;
    }else{
        if(!$_POST['paqueteS5'] || empty($_POST['paqueteS5'])){
            $errores .= 'No está definido el paquete de contratación al que pertenece el contrato "' . $_POST['S5'] . '"; ';
            $S5=NULL;
            $paqueteS5=NULL;
        } else{
            $S5=$_POST['S5'];
            $paqueteS5=$_POST['paqueteS5'];
        }
    }

    if(!$_POST['MO1'] || empty($_POST['MO1'])){
      $MO1=NULL;
      $paqueteMO1=NULL;
    }else{
        if(!$_POST['paqueteMO1'] || empty($_POST['paqueteMO1'])){
            $errores .= 'No está definido el paquete de contratación al que pertenece el contrato "' . $_POST['MO1'] . '"; ';
            $MO1=NULL;
            $paqueteMO1=NULL;
        } else{
            $MO1=$_POST['MO1'];
            $paqueteMO1=$_POST['paqueteMO1'];
        }
    }

    if(!$_POST['MO2'] || empty($_POST['MO2'])){
      $MO2=NULL;
      $paqueteMO2=NULL;
    }else{
        if(!$_POST['paqueteMO2'] || empty($_POST['paqueteMO2'])){
            $errores .= 'No está definido el paquete de contratación al que pertenece el contrato "' . $_POST['MO2'] . '"; ';
            $MO2=NULL;
            $paqueteMO2=NULL;
        } else{
            $MO2=$_POST['MO2'];
            $paqueteMO2=$_POST['paqueteMO2'];
        }
    }

    if(!$_POST['MO3'] || empty($_POST['MO3'])){
      $MO3=NULL;
      $paqueteMO3=NULL;
    }else{
        if(!$_POST['paqueteMO3'] || empty($_POST['paqueteMO3'])){
            $errores .= 'No está definido el paquete de contratación al que pertenece el contrato "' . $_POST['MO3'] . '"; ';
            $MO3=NULL;
            $paqueteMO3=NULL;
        } else{
            $MO3=$_POST['MO3'];
            $paqueteMO3=$_POST['paqueteMO3'];
        }
    }

    if(!$_POST['MO4'] || empty($_POST['MO4'])){
      $MO4=NULL;
      $paqueteMO4=NULL;
    }else{
        if(!$_POST['paqueteMO4'] || empty($_POST['paqueteMO4'])){
            $errores .= 'No está definido el paquete de contratación al que pertenece el contrato "' . $_POST['MO4'] . '"; ';
            $MO4=NULL;
            $paqueteMO4=NULL;
        } else{
            $MO4=$_POST['MO4'];
            $paqueteMO4=$_POST['paqueteMO4'];
        }
    }

    if(!$_POST['MO5'] || empty($_POST['MO5'])){
      $MO5=NULL;
      $paqueteMO5=NULL;
    }else{
        if(!$_POST['paqueteMO5'] || empty($_POST['paqueteMO5'])){
            $errores .= 'No está definido el paquete de contratación al que pertenece el contrato "' . $_POST['MO5'] . '"; ';
            $MO5=NULL;
            $paqueteMO5=NULL;
        } else{
            $MO5=$_POST['MO5'];
            $paqueteMO5=$_POST['paqueteMO5'];
        }
    }



    //echo "$Id, $codigo, $Actividad, $descripcionActividad, $actividadInicio, $fechaInicio, $tipoContrato";

    if(!empty($errores)){
      $resultado=false;
    } else {
      $query = "UPDATE $db"."_actividades SET SI1='$SI1', paqueteSI1='$paqueteSI1', SI2='$SI2', paqueteSI2='$paqueteSI2', SI3='$SI3', paqueteSI3='$paqueteSI3', SI4='$SI4', paqueteSI4='$paqueteSI4', SI5='$SI5', paqueteSI5='$paqueteSI5', S1='$S1', paqueteS1='$paqueteS1', S2='$S2', paqueteS2='$paqueteS2', S3='$S3', paqueteS3='$paqueteS3', S4='$S4', paqueteS4='$paqueteS4', S5='$S5', paqueteS5='$paqueteS5', MO1='$MO1', paqueteMO1='$paqueteMO1', MO2='$MO2', paqueteMO2='$paqueteMO2', MO3='$MO3', paqueteMO3='$paqueteMO3', MO4='$MO4', paqueteMO4='$paqueteMO4', MO5='$MO5', paqueteMO5='$paqueteMO5', semanaActualizacion=$semanaActualizacion WHERE Id=$Id";
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
