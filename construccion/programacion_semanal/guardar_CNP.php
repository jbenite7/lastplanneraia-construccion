<?php


require("../conexion.php");

$db=$_GET['db'];
$opcion=$_POST["opcion"];
$informacion=[];

if ($opcion == "modificar") {
    $Id=$_POST["Id"];
    $semana=$_POST["semana"];
    $Responsable_AIA=$_POST["Responsable_AIA"];
    $Categoria_CNP=$_POST["Categoria_CNC"];
    $CNP=$_POST["CNC"];
    $Observaciones_CNP=$_POST["Observaciones_CNC"];
    
} else if($opcion=="nueva_sem"){
    $f_inicio_sem=date("Y-m-d",strtotime($_POST["f_inicio_sem"]));
} else if($opcion=="eliminar_sem"){
    $semana=$_POST["semana"];
} else if($opcion=="reprogramar"){
    $Id=$_POST["Id"];
    $semana=$_POST["semana"];
} else if($opcion=="CNC"){
    $categoria=$_POST["categoria"];
} 


/*if ($opcion == "modificar") {
    $Id="1";
    $semana=1;
    $Sub_Contratista=utf8_decode("Atico");
    $Responsable_AIA=utf8_decode("Ana Maria Quintero");
    $Unidad=utf8_decode("m3");
    $Compromiso=1;
    $Ejecutado_Real=0;
} else if($opcion=="nueva_sem"){
    $f_inicio_sem=date("Y-m-d",strtotime($_POST["f_inicio_sem"]));
} else if($opcion=="eliminar"){
    $Id=$_POST["Id"];
    $semana=$_POST["semana"];
}else if($opcion=="nuevo"){
    $Id="1";
    $semana=1;
    $Actividad=utf8_decode("Losa");
    $Sub_Contratista=utf8_decode("Atico");
    $Responsable_AIA=utf8_decode("Ana Maria Quintero");
    $Unidad=utf8_decode("m3");
    $Compromiso=1;  
}else if($opcion=="autoprogramar"){
    $semana=3;   
};*/




switch($opcion){
    case 'modificar':
        modificar($Id, $semana, $Responsable_AIA, $Categoria_CNP, $CNP, $Observaciones_CNP, $db, $conexion);
        break;
        
    case 'nueva_sem':
        nueva_sem($f_inicio_sem, $db, $conexion);
        break;
    
    case 'eliminar_sem':
        eliminar_sem($semana, $db, $conexion);
        break;
    
    case 'reprogramar':
        reprogramar($Id, $semana, $db, $conexion);
        break;
        
    case 'CNC':
        CNC($categoria, $db, $conexion);
        break; 
        
}



function modificar($Id, $semana, $Responsable_AIA, $Categoria_CNP, $CNP, $Observaciones_CNP, $db, $conexion){
    
    $query= "UPDATE $db"."_programacion_semanal SET Responsable_AIA='$Responsable_AIA', Categoria_CNP='$Categoria_CNP', CNP='$CNP', Observaciones_CNP='$Observaciones_CNP' WHERE Consecutivo=$Id;";

    $resultado= mysqli_query($conexion, $query);
    verificar_resultado($resultado);
//    modificar_sem_rest($conexion);
//    modificar_estado_act($conexion);
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

function reprogramar($Id, $semana, $db, $conexion){
        $query="UPDATE $db"."_programacion_semanal SET Activa='1', Categoria_CNP=NULL, CNP=NULL, Observaciones_CNP=NULL WHERE Consecutivo=$Id";
        $resultado=mysqli_query($conexion, $query);
        verificar_resultado($resultado);
        mysqli_close($conexion);     
}

function CNC($categoria, $db, $conexion){
        $query="SELECT * FROM general_cnc WHERE Categoria_CNC='$categoria'";
        $resultado= mysqli_query($conexion, $query);
        $cadena="<option value=''></option>";
        while ($valores = mysqli_fetch_array($resultado)){
            $valores=$valores['CNC'];
            $cadena.= "<option value='$valores'>$valores</option>";
        };
        echo $cadena;
        mysqli_close($conexion);
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