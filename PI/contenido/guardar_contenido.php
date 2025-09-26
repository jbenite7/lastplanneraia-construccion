<?php

require("../conexion.php");

$db=$_GET['db'];
$opcion=$_POST["opcion"];
$informacion=[];

if ($opcion == "modificar" || $opcion == "registrar") {
    $Id=$_POST["Id"];
    $Ejecutado = $_POST["Ejecutado"];
} else if ($opcion=="modificargrupo"){
    $Id=$_POST["Id"];
    $script=utf8_decode($_POST["Id"]);
    $Ejecutado = $_POST["Ejecutado"];
} else if ($opcion=="nueva_sem"){
    $f_inicio_sem=/*date("Y-m-d",strtotime("2019-11-26"));*/date("Y-m-d",strtotime($_POST["f_inicio_sem"]));
};



switch($opcion){
    case 'modificar':
        modificar($Ejecutado, $Id, $db, $conexion);
        break;
    
    case 'eliminar':
        eliminar($Id, $db, $conexion);
        break;
        
    case 'modificargrupo':
        modificargrupo($Ejecutado, $script, $db, $conexion);
        break;
        
    case 'nueva_sem':
        nueva_sem($f_inicio_sem, $db, $conexion);
        break;
}



function modificar($Ejecutado, $Id, $db, $conexion){
    $query= "UPDATE $db"."_programa SET Ejecutado=$Ejecutado WHERE Id='$Id'";
    $resultado= mysqli_query($conexion, $query);
    verificar_resultado($resultado);
    cerrar($conexion);
}

function eliminar($Id, $db, $conexion){
    $query="DELETE FROM $db"."_programa WHERE Id='$Id'";
    $resultado=mysqli_query($conexion, $query);
    verificar_resultado($resultado);
    cerrar($conexion);
}

function modificargrupo($Ejecutado, $script, $db, $conexion){
    $query="UPDATE $db"."_programa SET Ejecutado=$Ejecutado WHERE $script";                             
    $resultado=mysqli_query($conexion, $query);
    verificar_resultado($resultado);
    cerrar($conexion);
}

function nueva_sem($f_inicio_sem, $db, $conexion){
    require("../funciones_generales/nueva_semana.php");
    mysqli_close($conexion);
    require("../conexion.php");
    require("../funciones_generales/modificar_sem_estado.php");
}

function verificar_resultado($resultado){
    if(!$resultado) $informacion["respuesta"] ="ERROR";
    else $informacion["respuesta"] = "BIEN";
    echo json_encode($informacion);
}

function cerrar($conexion){
    mysqli_close($conexion);
}





?>