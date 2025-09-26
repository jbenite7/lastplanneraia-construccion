<?php


require("../conexion.php");

$db=$_GET['db'];
$opcion=$_POST["opcion"];
$informacion=[];

if ($opcion == "modificar") {
    $Id=$_POST["Id"];
    $semana=$_POST["semana"];
    //$Categoria_CNC=$_POST["Categoria_CNC"];
    $CNC=$_POST["CNC"];
    $Observaciones_CNC=$_POST["Observaciones_CNC"];
    
} else if($opcion=="nueva_sem"){
    $f_inicio_sem=date("Y-m-d",strtotime($_POST["f_inicio_sem"]));
} else if($opcion=="eliminar_sem"){
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
        modificar($Id, $semana/*, $Categoria_CNC*/, $CNC, $Observaciones_CNC, $db, $conexion);
        break;
        
    case 'nueva_sem':
        nueva_sem($f_inicio_sem, $db, $conexion);
        break;
    
    case 'eliminar_sem':
        eliminar_sem($semana, $db, $conexion);
        break;
    
    case 'CNC':
        CNC($categoria, $db, $conexion);
        break; 
        
}



function modificar($Id, $semana/*, $Categoria_CNC*/, $CNC, $Observaciones_CNC, $db, $conexion){
    
    /*$query= "UPDATE $db"."_programacion_semanal SET Categoria_CNC='$Categoria_CNC', CNC='$CNC', Observaciones_CNC='$Observaciones_CNC' WHERE Consecutivo=$Id;";*/
    $query= "UPDATE $db"."_programacion_semanal SET CNC='$CNC', Observaciones_CNC='$Observaciones_CNC' WHERE Consecutivo=$Id;";

    $resultado= mysqli_query($conexion, $query);
    verificar_resultado($resultado);
//    modificar_sem_rest($conexion);
//    modificar_estado_act($conexion);
    cerrar($conexion);
}

function nueva_sem($f_inicio_sem, $db, $conexion){
    require("../funciones_generales/nueva_semana.php");
    mysqli_close($conexion);
    require("../conexion.php");
    require("../funciones_generales/modificar_sem_estado.php");
}

function modificar_sem_rest($semana, $f_inicio_sem, $db, $conexion){
    require("../conexion.php");
    $query2="SELECT * FROM $db"."_programa_consolidado WHERE Semana=$semana";
    $resultado= mysqli_query($conexion, $query2);
	if(!$resultado){
        die("Error");
    } else{
        $query3="UPDATE $db"."_programa_consolidado SET Dias_Inicio= CASE";

        while($data=mysqli_fetch_array($resultado)){
            $Id=$data["Consecutivo_en_Programa"];
            $actividad=$data["Actividad"];
            $hoy= $f_inicio_sem;
            $mañana= date("Y-m-d",strtotime($data["Fecha_Inicio"]));
            $dias=(strtotime($mañana)-strtotime($hoy))/86400;
            $dias=floor($dias);
            
            if($dias<0 || $dias==-0){
                $dias=0;
            }
            $query3 .=" WHEN Consecutivo_en_Programa='$Id' THEN '$dias'"; 
                                                                
        }
        $query3 .=" END WHERE Titulo=0 AND Semana=$semana";
        //echo $query3;
    };
    require("../conexion.php");
    $resultado=mysqli_multi_query($conexion, $query3); 
    mysqli_close($conexion);
}

function modificar_estado_actual_general($semana, $f_inicio_sem, $db, $conexion){

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
                                                WHERE Titulo=0 AND Semana=$semana
                                                ";
    //echo $query;
    require("../conexion.php");
    $resultado=mysqli_multi_query($conexion, $query); 
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


function CNC($categoria, $db, $conexion){
        $query="SELECT * FROM general_cnc WHERE Categoria_CNC='$categoria'";
        $resultado= mysqli_query($conexion, $query);
        $cadena="<option value=''></option>";
        while ($valores = mysqli_fetch_array($resultado)){
            $valores=$valores['CNC'];
            $cadena.= "<option value='$valores'>$valores</option>";
        };
        echo $cadena;
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