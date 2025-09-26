<?php

require("../conexion.php");

$db=/*"paris_campestre"*/$_GET['db'];
$opcion=/*"modificar"*/$_POST["opcion"];
$informacion=[];

/*if ($opcion == "modificar") {
    $Id='2';
    $D_y_E= 1;
    $Materiales=0.33333 ;
    $MdeO= 0.66666;
    $Equipos= 0;
    $Predecesora= 1;
    $Pdto_Cons= 0;
    $Modelo= "0";
    $Responsable_AIA= "Mauricio Martinez" ;
    $Observaciones= "Hola Mundo" ;
} else if($opcion=="nueva_sem"){
    $f_inicio_sem=date("Y-m-d",strtotime("2019-11-26"));;
};*/

if ($opcion == "modificar") {
    $semana=/*2*/$_GET["semana"];
    $Id=/*28*/$_POST["Id"];
    $D_y_E=/*1*/$_POST["D_y_E"];
    $Materiales=/*1*/$_POST["Materiales"];
    $MdeO=/*"N/A"*/$_POST["MdeO"];
    $Equipos=/*0.66*/$_POST["Equipos"];
    $Predecesora=/*"N/A"*/$_POST["Predecesora"];
    $Pdto_Cons=/*1*/$_POST["Pdto_Cons"];
    $Modelo=/*1*/$_POST["Modelo"];
    if($D_y_E=="N/A"){
    }else{
        $D_y_E=round($D_y_E*100/100,2);
    }
    if($Materiales=="N/A"){
    }else{
        $Materiales=round($Materiales*100/100,2);
    }
    if($MdeO=="N/A"){
    }else{
        $MdeO=round($MdeO*100/100,2);
    }
    if($Equipos=="N/A"){
    }else{
        $Equipos=round($Equipos*100/100,2);
    }
    if($Predecesora=="N/A"){
    }else{
        $Predecesora=round($Predecesora*100/100,2);
    }
    if($Pdto_Cons=="N/A"){
    }else{
        $Pdto_Cons=round($Pdto_Cons*100/100,2);
    }
    if($Modelo=="N/A"){
    }else{
        $Modelo=round($Modelo*100/100,2);
    }
    $Responsable_AIA="Sergio Rendon"/*$_POST["Responsable_AIA"]*/;
    $Observaciones="ñ"/*$_POST["Observaciones"]*/;
} else if($opcion=="nueva_sem"){
    $f_inicio_sem=date("Y-m-d",strtotime($_POST["f_inicio_sem"]));
} else if($opcion=="eliminar_sem"){
    $semana=$_POST["semana"];
};
//echo $D_y_E, $Materiales, $MdeO, $Equipos, $Predecesora, $Pdto_Cons, $Modelo, $Responsable_AIA, $Observaciones, $Id;




switch($opcion){
    case 'modificar':
        modificar($D_y_E, $Materiales, $MdeO, $Equipos, $Predecesora, $Pdto_Cons, $Modelo, $Responsable_AIA, $Observaciones, $Id, $semana, fecha_inicio_sem($semana, $db, $conexion), $db, $conexion);
        break;
        
    case 'nueva_sem':
        nueva_sem($f_inicio_sem, $db, $conexion);
        break;
    
    case 'eliminar_sem':
        eliminar_sem($semana, $db, $conexion);
        break;
}



function modificar($D_y_E, $Materiales, $MdeO, $Equipos, $Predecesora, $Pdto_Cons, $Modelo, $Responsable_AIA, $Observaciones, $Id, $semana, $inicio_semana, $db, $conexion){
    
    //echo $inicio_semana;
    
    $query = "UPDATE $db"."_programa_consolidado SET Activa=1 WHERE Consecutivo_en_Programa='$Id' AND Semana=$semana;";
    $query1 = "UPDATE $db"."_programa_consolidado SET D_y_E='$D_y_E', Materiales='$Materiales', MdeO='$MdeO', Equipos='$Equipos', Predecesora='$Predecesora', Pdto_Cons='$Pdto_Cons', Modelo='$Modelo', Responsable_AIA='$Responsable_AIA', Observaciones='$Observaciones' WHERE Consecutivo_en_Programa='$Id' AND Semana=$semana";

    $resultado= mysqli_query($conexion, $query);
    require("../conexion.php");
    $resultado1= mysqli_query($conexion, $query1);
    modificar_rest($Id, $semana, $inicio_semana, $db, $conexion);
    require("../conexion.php");
    modificar_estado_act($Id, $semana, $inicio_semana, $db, $conexion);
    cerrar($conexion);
}
function modificar_rest($Id, $semana, $inicio_semana, $db, $conexion){
    require("../conexion.php");
    $query2="SELECT * FROM $db"."_programa_consolidado WHERE Semana=$semana AND Consecutivo_en_Programa=$Id";
    $resultado= mysqli_query($conexion, $query2);
	if(!$resultado){
        die("Error");
    } else{
        $query3="";
        while($data=mysqli_fetch_array($resultado)){
            $Id=$data["Consecutivo_en_Programa"];







            $conteo=0;
            $suma=0;
            if($D_y_E2=="N/A"){
                $conteo=$conteo+0;
                $suma=$suma+0;
            }else{
                $conteo=$conteo+1;
                $suma=$suma + round($D_y_E2 , 5);
            }
            if($Materiales2=="N/A"){
                $conteo=$conteo+0;
                $suma=$suma+0;
            }else{
                $conteo=$conteo+1;
                $suma=$suma + round($Materiales2 , 5);
            }
            if($MdeO2=="N/A"){
                $conteo=$conteo+0;
                $suma=$suma+0;
            }else{
                $conteo=$conteo+1;
                $suma=$suma + round($MdeO2 , 5);
            }
            if($Equipos2=="N/A"){
                $conteo=$conteo+0;
                $suma=$suma+0;
            }else{
                $conteo=$conteo+1;
                $suma=$suma + round($Equipos2 , 5);
            }
            if($Predecesora2=="N/A"){
                $conteo=$conteo+0;
                $suma=$suma+0;
            }else{
                $conteo=$conteo+1;
                $suma=$suma + round($Predecesora2 , 5);
            }
            if($Pdto_Cons2=="N/A"){
                $conteo=$conteo+0;
                $suma=$suma+0;
            }else{
                $conteo=$conteo+1;
                $suma=$suma + round($Pdto_Cons2 , 5);
            }

                $conteo=$conteo+0;
                $suma=$suma+0;
            }else{
                $conteo=$conteo+1;
                $suma=$suma + round($Modelo2 , 5);
            }
            //echo $conteo . "<br>" . $suma;
            if($conteo==0){
                $Estado_Restricciones=1;
            }else{
                $Estado_Restricciones=round(($suma/$conteo),5);
            }
         
            $query3 .="UPDATE $db"."_programa_consolidado SET Estado_Restricciones=$Estado_Restricciones WHERE Consecutivo_en_Programa=$Id AND Titulo=0 AND Semana=$semana;";
                                                                
        }
        //echo $query3;
    };
    require("../conexion.php");
    $resultado=mysqli_multi_query($conexion, $query3); 
    mysqli_close($conexion);
}
function modificar_estado_act($Id, $semana, $inicio_semana, $db, $conexion){

    $fin_semana= date("Y-m-d",strtotime("$inicio_semana + 7 days"));
    
    $query = "UPDATE $db"."_programa_consolidado SET                                                
                                                 Estado= CASE
                                                    WHEN Fecha_Fin<'$fin_semana' AND Ejecutado=1 THEN 'Terminada' 
                                                    WHEN Fecha_Fin<'$f_inicio_sem' AND Ejecutado<1 THEN 'Atrasada' 
                                                    WHEN (Fecha_Inicio<'$fin_semana') AND Ejecutado=2 THEN 'No Puede Comenzar' 
                                                    WHEN (Fecha_Inicio>='$fin_semana' OR Fecha_Fin>='$fin_semana') AND Ejecutado=1 THEN 'Terminada Antes' 
                                                    WHEN Fecha_Fin>='$f_inicio_sem' AND Ejecutado<1 AND Ejecutado>0 THEN 'En Ejecución'
                                                    WHEN Fecha_Fin>='$f_inicio_sem' AND Fecha_Inicio<='$fin_semana' AND Dias_Inicio<=7 AND Ejecutado=0 THEN 'Pendiente de Iniciar'
                                                    WHEN Dias_Inicio <= Lookahead AND Ejecutado=0 THEN 'Porgramación Intermedia'
                                                    ELSE 'No Requerida'
                                                 END   
                                                WHERE Titulo=0 AND Consecutivo_en_Programa=$Id AND Semana=$semana
                                                ";
    //echo $query;                            
    $resultado=mysqli_multi_query($conexion, $query); 
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
        //echo $inicio_semana;
    }


    return $inicio_semana;
}





?>