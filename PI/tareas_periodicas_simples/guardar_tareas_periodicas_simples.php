<?php
require("../conexion.php");

$db=/*"proyectos_inmobiliarios"*/$_GET['db'];
$opcion=/*"nueva_sem"*/$_POST["opcion"];
$informacion=[];

if ($opcion == "modificar" || $opcion == "registrar") {

    $Id=/*20*/$_POST["Id"];
    $Periodicidad =/*50*/$_POST["Periodicidad"];
    $Relevancia =/*10*/$_POST["Relevancia"];
    $Ejecutado =/*10*/$_POST["Ejecutado"];
    $Observaciones =/*10*/$_POST["Observaciones"];
    $semana=/*1*/$_GET["semana"];

} else if ($opcion=="modificargrupo"){
    $Id=$_POST["Id"];
    $script=utf8_decode($_POST["Id"]);
    $script1=utf8_decode($_POST["Id1"]);
    $Ejecutado = $_POST["Ejecutado"];
    $semana=$_GET["semana"];
} else if($opcion=="nueva_sem"){
    $f_inicio_sem=date("Y-m-d",strtotime(/*"2020-03-24"*/$_POST["f_inicio_sem"]));
}else if($opcion=="eliminar_sem"){
    $semana=$_POST["semana"];
};

switch($opcion){
    case 'modificar':
        modificar($Periodicidad, $Relevancia, $Ejecutado, $Observaciones, $Id, $semana, fecha_inicio_sem($semana, $db, $conexion), $db, $conexion);
        break;
        
    case 'modificargrupo':
        modificargrupo($Ejecutado, $script, $script1, $semana, fecha_inicio_sem($semana, $db, $conexion), $db, $conexion);
        break;
        
    case 'nueva_sem':
        nueva_sem($f_inicio_sem, $db, $conexion);
        break;
    
    case 'eliminar_sem':
        eliminar_sem($semana, $db, $conexion);
        break;
}

function modificar($Periodicidad, $Relevancia, $Ejecutado, $Observaciones, $Id, $semana, $inicio_semana, $db, $conexion){
    //$query = "UPDATE $db"."_programa_consolidado SET Activa=1 WHERE Consecutivo_en_Programa='$Id' AND Semana=$semana;";
    $query1 = "UPDATE $db"."_programa_consolidado SET Periodicidad='$Periodicidad', Relevancia='$Relevancia', Ejecutado='$Ejecutado', Observaciones='$Observaciones' WHERE Consecutivo_en_Programa='$Id' AND Semana=$semana";
    //echo $query1;
    $resultado= mysqli_query($conexion, $query);
    cerrar($conexion);
    require("../conexion.php");
    $resultado1= mysqli_query($conexion, $query1);
    verificar_resultado($resultado1);
    cerrar($conexion);
    require("../conexion.php");
    modificar_estado_act($Id, $semana, $inicio_semana, $db, $conexion);
    cerrar($conexion);
}
function modificar_estado_act($Id, $semana, $f_inicio_sem, $db, $conexion){

    $fin_semana= date("Y-m-d",strtotime("$f_inicio_sem + 7 days"));
    
    $query = "UPDATE $db"."_programa_consolidado SET                                                
                                                 Estado= CASE
                                                    WHEN Fecha_Fin<'$fin_semana' AND Ejecutado=1 THEN 'Terminada' 
                                                    WHEN Fecha_Fin<'$f_inicio_sem' AND Ejecutado<1 THEN 'Atrasada' 
                                                    WHEN Fecha_Fin>='$f_inicio_sem' AND Fecha_Inicio<='$fin_semana' AND Dias_Inicio<=7 AND Estado_Restricciones!='NA' AND Estado_Restricciones<1 AND R1!='NA' AND Ejecutado=0 THEN 'No Puede Comenzar' 
                                                    WHEN (Fecha_Inicio>='$fin_semana' OR Fecha_Fin>='$fin_semana') AND Ejecutado=1 THEN 'Terminada Antes' 
                                                    WHEN Fecha_Fin>='$f_inicio_sem' AND Ejecutado<1 AND Ejecutado>0 THEN 'En Ejecución'
                                                    WHEN Fecha_Fin>='$f_inicio_sem' AND Fecha_Inicio<='$fin_semana' AND Dias_Inicio<=7 AND Estado_Restricciones!='NA' AND (Estado_Restricciones=1 OR R1='NA') AND Ejecutado=0 THEN 'Pendiente de Iniciar'
                                                    WHEN Dias_Inicio <= Lookahead AND Ejecutado=0 THEN 'Porgramación Intermedia'
                                                    ELSE 'No Requerida'
                                                 END   
                                                WHERE Titulo=0 AND Consecutivo_en_Programa=$Id AND Semana=$semana
                                                ";
    //echo $query;                            
    $resultado=mysqli_multi_query($conexion, $query); 
}

function modificargrupo($Ejecutado, $script, $script1, $semana, $inicio_semana, $db, $conexion){
    $query = "UPDATE $db"."_programa_consolidado SET Activa=1 WHERE $script1 AND Semana=$semana;";
    $query1 = "UPDATE $db"."_programa_consolidado SET Ejecutado=$Ejecutado WHERE $script1 AND Semana=$semana";
    $resultado= mysqli_multi_query($conexion, $query);
    $resultado1= mysqli_multi_query($conexion, $query1);
    verificar_resultado($resultado);
    require("../conexion.php");

    $fin_semana= date("Y-m-d",strtotime("$inicio_semana + 7 days"));
    $query2 = "UPDATE $db"."_programa_consolidado SET                                                
                                                 Estado= CASE
                                                    WHEN Fecha_Fin<'$fin_semana' AND Ejecutado=1 THEN 'Terminada' 
                                                    WHEN Fecha_Fin<'$f_inicio_sem' AND Ejecutado<1 THEN 'Atrasada' 
                                                    WHEN Fecha_Fin>='$f_inicio_sem' AND Fecha_Inicio<='$fin_semana' AND Dias_Inicio<=7 AND Estado_Restricciones!='NA' AND Estado_Restricciones<1 AND R1!='NA' AND Ejecutado=0 THEN 'No Puede Comenzar' 
                                                    WHEN (Fecha_Inicio>='$fin_semana' OR Fecha_Fin>='$fin_semana') AND Ejecutado=1 THEN 'Terminada Antes' 
                                                    WHEN Fecha_Fin>='$f_inicio_sem' AND Ejecutado<1 AND Ejecutado>0 THEN 'En Ejecución'
                                                    WHEN Fecha_Fin>='$f_inicio_sem' AND Fecha_Inicio<='$fin_semana' AND Dias_Inicio<=7 AND Estado_Restricciones!='NA' AND (Estado_Restricciones=1 OR R1='NA') AND Ejecutado=0 THEN 'Pendiente de Iniciar'
                                                    WHEN Dias_Inicio <= Lookahead AND Ejecutado=0 THEN 'Porgramación Intermedia'
                                                    ELSE 'No Requerida'
                                                 END  
                                                WHERE Titulo=0 AND $script1 AND Semana=$semana
                                                ";
    //echo $query;                            
    $resultado2=mysqli_multi_query($conexion, $query2); 
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