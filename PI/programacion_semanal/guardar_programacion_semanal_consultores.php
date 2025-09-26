<?php
require("../conexion.php");

$db=/*"paris_campestre"*/$_GET['db'];
$opcion=/*"modificar"*/$_POST["opcion"];
$informacion=[];

if ($opcion == "modificar") {
    $Id=/*26*/$_POST["Id"];
    $semana=/*2*/$_POST["semana"];
    $Descripcion=/*"ARMADO DE ACERO"*/$_POST["Descripcion"];
    $Sub_Contratista=/*"AIA"*/$_POST["Sub_Contratista"];
    $Responsable_AIA=/*"Sergio Rendon"*/$_POST["Responsable_AIA"];
    $Unidad=/*"%"*/$_POST["Unidad"];
    $Compromiso=/*40*/$_POST["Compromiso"];
    $Ejecutado_Real=/*40*/$_POST["Real"];
    //$Categoria_CNC=/*40*/$_POST["Categoria_CNC"];
    $CNC=/*40*/$_POST["CNC"];
    $Observaciones_CNC=/*40*/$_POST["Observaciones_CNC"]; 
}else if ($opcion == "EstadoEjecucion") {
    $Id=/*143*/$_POST["Id"];
    $semana=/*2*/$_POST["semana"];
    $Ejecutado=/*1*/$_POST["Ejecutado"];
} else if($opcion=="nueva_sem"){
    $f_inicio_sem=date("Y-m-d",strtotime($_POST["f_inicio_sem"]));
} else if($opcion=="eliminar_sem"){
    $semana=$_POST["semana"];
} else if($opcion=="eliminar"){
    $Id=$_POST["Id"];
    $semana=$_POST["semana"];
    $Actividad=utf8_decode($_POST["Actividad"]);
    $Responsable_AIA=$_POST["Responsable_AIA"];
    //$Categoria_CNP=$_POST["Categoria_CNP"];
    $CNP=$_POST["CNP"];
    $Observaciones_CNP=$_POST["Observaciones_CNP"];
} else if($opcion=="duplicar"){
    $Id=$_POST["Id"];
    $semana=$_POST["semana"];
    $Actividad=utf8_decode($_POST["Actividad"]);
}else if($opcion=="nuevo"){
    $Id=/*"23-A"*/$_POST["Id1"];
    $semana=/*4*/$_POST["semana"]*100/100;
    $Actividad=/*""*/$_POST["Actividad"];
    $Descripcion=$_POST["Descripcion"];
    $Clase=/*""*/$_POST["Clase"];
    $Sub_Contratista=/*""*/$_POST["Sub_Contratista"];
    $Responsable_AIA=/*""*/$_POST["Responsable_AIA"];
    $Unidad=/*""*/$_POST["Unidad"];
    $Compromiso=/*0*/$_POST["Compromiso"];   
}else if($opcion=="autoprogramar"){
    $semana=/*20*/$_POST["semana"];  
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
    $semana=1;   
}*/
//echo "$semana <br> $db <br>" ;




switch($opcion){
    case 'modificar':
        modificar($Id, $semana, $Descripcion, $Sub_Contratista, $Responsable_AIA, $Unidad, $Compromiso, $Ejecutado_Real, /*$Categoria_CNC,*/ $CNC, $Observaciones_CNC, $db, $conexion);
        break;
        
    case 'EstadoEjecucion':
        EstadoEjecucion($Id, $semana, $Ejecutado, $db, $conexion);
        break;
        
    case 'nueva_sem':
        nueva_sem($f_inicio_sem, $db, $conexion);
        break;
    
    case 'eliminar_sem':
        eliminar_sem($semana, $db, $conexion);
        break;
    
    case 'eliminar':
        eliminar($Id, $semana, $Actividad, $Responsable_AIA, /*$Categoria_CNP,*/ $CNP, $Observaciones_CNP, $db, $conexion);
        break;
        
    case 'duplicar':
        duplicar($Id, $semana, $Actividad, $db, $conexion);
        break;
    
    case 'nuevo':
        agregar_actividad($Id, $semana, $Actividad, $Descripcion, $Clase, $Sub_Contratista, $Responsable_AIA, $Unidad, $Compromiso, $db, $conexion);
        break;
        
    case 'autoprogramar':
        autoprogramar($semana, $db, $conexion);
        break;    
}



function modificar($Id, $semana, $Descripcion, $Sub_Contratista, $Responsable_AIA, $Unidad, $Compromiso, $Ejecutado_Real, /*$Categoria_CNC,*/ $CNC, $Observaciones_CNC, $db, $conexion){
    if ($Compromiso >=0 && $Ejecutado_Real >=0 && $Compromiso!="" && $Ejecutado_Real !=""){
        $P_Completado=($Ejecutado_Real/$Compromiso);
        if($Ejecutado_Real<$Compromiso){
           $PAC=0; 
        } else{
            $PAC=1;
        }
    } else{
        if($Compromiso===""){
            $Compromiso="NULL";            
        };
        if($Ejecutado_Real===""){
            $Ejecutado_Real="NULL";            
        };
        $P_Completado="NULL";
        $PAC="NULL";       
    }
    if($PAC==1){
        $query= "UPDATE $db"."_programacion_semanal SET Descripcion='$Descripcion', Sub_Contratista='$Sub_Contratista', Responsable_AIA='$Responsable_AIA', Unidad='$Unidad', Compromiso=$Compromiso, Ejecutado_Real=$Ejecutado_Real, P_Completado=$P_Completado, PAC=$PAC, CNC=NULL, Observaciones_CNC=NULL WHERE Consecutivo=$Id;";
    }else{
        $query= "UPDATE $db"."_programacion_semanal SET Descripcion='$Descripcion', Sub_Contratista='$Sub_Contratista', Responsable_AIA='$Responsable_AIA', Unidad='$Unidad', Compromiso=$Compromiso, Ejecutado_Real=$Ejecutado_Real, P_Completado=$P_Completado, PAC=$PAC, CNC='$CNC', Observaciones_CNC='$Observaciones_CNC' WHERE Consecutivo=$Id;";
    }

    

    $resultado= mysqli_query($conexion, $query);
    verificar_resultado($resultado);
//    modificar_sem_rest($conexion);
//    modificar_estado_act($conexion);
    cerrar($conexion);
}

function EstadoEjecucion($Id, $semana, $Ejecutado, $db, $conexion){
    $query = "UPDATE $db"."_programa_consolidado SET Activa=1 WHERE Consecutivo_en_Programa='$Id' AND Semana=$semana;";
    $query1 = "UPDATE $db"."_programa_consolidado SET Ejecutado_Siguiente_Semana=$Ejecutado WHERE Consecutivo_en_Programa='$Id' AND Semana=$semana";
    $resultado= mysqli_query($conexion, $query);
    cerrar($conexion);
    require("../conexion.php");
    $resultado1= mysqli_query($conexion, $query1);
    verificar_resultado($resultado1);
    cerrar($conexion);
}

function nueva_sem($f_inicio_sem, $db, $conexion){
    require("../funciones_generales/nueva_semana.php");
    mysqli_close($conexion);
    require("../conexion.php");
    require("../funciones_generales/modificar_sem_estado.php");
}

function activar_checklists($semana, $db, $conexion){
    $query = "SELECT * FROM $db"."_programa_consolidado WHERE Semana=$semana AND (Categoria = 'tramites' OR Categoria = 'consultores' OR Categoria = 'periodicas_compuestas')";
    require("../conexion.php");
    $resultado=mysqli_query($conexion, $query);
    
    while($data=mysqli_fetch_assoc($resultado)){
        $consecutivo = $data["Consecutivo_en_Programa"];
        $checklist = $data["Checklist"];
        
        if($checklist==NULL){
        }else{
            require("../conexion.php");
            $query1 = "SELECT MAX(Consecutivo_Requerimiento) FROM $db"."_checklists WHERE Codigo_Tarea=$checklist";
            $resultado1=mysqli_query($conexion, $query1);
            $data1=mysqli_fetch_assoc($resultado1);
            $requerimientos=$data1["MAX(Consecutivo_Requerimiento)"];

            $query2 = "SELECT ";
            for($i=1; $i<=$requerimientos; $i++){

                require("../conexion.php");
                $query2 .= "(SELECT CASE WHEN R$i = 'NA' THEN 0 ELSE R$i END) AS 'valor$i'";
                if($i<$requerimientos){
                    $query2 .=", ";
                }
            }
            $query2 .= " FROM $db"."_programa_consolidado WHERE Consecutivo_en_Programa = $consecutivo";

            $resultado2=mysqli_query($conexion, $query2);
            $data2=mysqli_fetch_assoc($resultado2);

            $query3 = "UPDATE $db"."_programa_consolidado SET ";
            for($i=1; $i<=$requerimientos; $i++){
                $valor = $data2["valor$i"];

                require("../conexion.php");
                $query3 .= "R$i = $valor, ";
            }
            $query3 .="Estado_Restricciones=0 WHERE Consecutivo_en_Programa = $consecutivo"; 
            $resultado3=mysqli_query($conexion, $query3);
        }
    }
    $query4 = "UPDATE $db"."_programa_consolidado SET Estado_Restricciones=1 WHERE Categoria = 'periodicas_simples' OR Categoria = 'propias' OR ((Categoria = 'tramites' OR Categoria = 'consultores' OR Categoria = 'periodicas_compuestas') AND Checklist='')";
    $resultado4=mysqli_query($conexion, $query4);
}

function eliminar_sem($semana, $db, $conexion){    
    require("../funciones_generales/eliminar_semana.php");
}

function agregar_actividad($Id, $semana, $Actividad, $Descripcion, $Clase, $Sub_Contratista, $Responsable_AIA, $Unidad, $Compromiso, $db, $conexion){
    $query="SELECT MAX(Consecutivo_En_Programa) FROM $db"."_programacion_semanal WHERE Semana=$semana";
    $resultado= mysqli_query($conexion, $query);
    $data=mysqli_fetch_assoc($resultado);
    $query1="SELECT MAX(Consecutivo_en_Programa) FROM $db"."_programa_consolidado WHERE Semana=$semana";
    $resultado1= mysqli_query($conexion, $query1);
    $data1=mysqli_fetch_assoc($resultado1);
    require("../conexion.php");
    $consecutivo_en_programacion_semanal=$data["MAX(Consecutivo_En_Programa)"];
    $consecutivo_en_programa_consolidado=$data1["MAX(Consecutivo_en_Programa)"];
    if($consecutivo_en_programa_consolidado<=$consecutivo_en_programacion_semanal){
        $consecutivo_en_programa=$consecutivo_en_programacion_semanal + 1;
    }else{
        $consecutivo_en_programa=$consecutivo_en_programa_consolidado + 1;
    }
    $query1="INSERT INTO $db"."_programacion_semanal (Consecutivo, Semana, Consecutivo_En_Programa, Id, Actividad, Descripcion, Clase, Sub_Contratista, Responsable_AIA, Unidad, Compromiso, Critica, Atrasada, Activa, Prog_Sin_Restricciones_100) VALUES (NULL, $semana, $consecutivo_en_programa, '$Id', '$Actividad', '$Descripcion', 'consultores', '$Sub_Contratista', '$Responsable_AIA', '$Unidad', $Compromiso, 0, 0, 'NA', 0);";
    $resultado1= mysqli_multi_query($conexion, $query1);
    verificar_resultado($resultado1);
    cerrar($conexion);
}

function autoprogramar($semana, $db, $conexion){
    require("../funciones_generales/autoprogramar.php");
}

function eliminar($Id, $semana, $Actividad, $Responsable_AIA, /*$Categoria_CNP,*/ $CNP, $Observaciones_CNP, $db, $conexion){
    $query="SELECT Activa FROM $db"."_programacion_semanal WHERE Consecutivo=$Id";
    $resultado=mysqli_query($conexion, $query);
    $data=mysqli_fetch_assoc($resultado);
    $activa=$data["Activa"];
    if($activa=="NA"){
        $query1="DELETE FROM $db"."_programacion_semanal WHERE Consecutivo=$Id";
        $resultado1=mysqli_query($conexion, $query1);
        verificar_resultado($resultado1="OK");
        cerrar($conexion); 
    }else{
        $query1="UPDATE $db"."_programacion_semanal SET Unidad=NULL, Compromiso=NULL, Ejecutado_Real=NULL, P_Completado=NULL, PAC=NULL, Activa='0', Sub_Contratista=NULL, Responsable_AIA='$Responsable_AIA', CNP='$CNP', Observaciones_CNP='$Observaciones_CNP', CNC=NULL, Observaciones_CNC=NULL WHERE Consecutivo=$Id";
        $resultado1=mysqli_query($conexion, $query1);
        verificar_resultado($resultado1);
        cerrar($conexion); 
    }
    
}

function duplicar($Id, $semana, $Actividad, $db, $conexion){
    $query="INSERT INTO $db"."_programacion_semanal (Semana, Consecutivo_En_Programa, Id, Actividad, Clase, Critica, Atrasada, Activa, Prog_Sin_Restricciones_100) SELECT 
        $semana, 
        $db"."_programacion_semanal . Consecutivo_en_Programa, 
        $db"."_programacion_semanal . Id, 
        $db"."_programacion_semanal . Actividad,
        'consultores (duplicada)',
        0, 
        0,
        'NA',
        $db"."_programacion_semanal . Prog_Sin_Restricciones_100
        
        FROM $db"."_programacion_semanal WHERE Semana=$semana AND Consecutivo=$Id";
    $resultado=mysqli_query($conexion, $query);
    verificar_resultado($resultado);
    cerrar($conexion); 
    
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
    require("../conexion.php");
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
    }


    return $inicio_semana;
}
?>