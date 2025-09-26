<?php
    $query="SELECT * FROM $db"."_semanas_activas WHERE Semana=$semana";
    //echo "$query <br>" ;
    $resultado= mysqli_query($conexion, $query);
    $data=mysqli_fetch_assoc($resultado);
    $inicio_sem=date($data['Fecha_Inicio_Sem']);
    $fin_sem=date($data['Fecha_Fin_Sem']);
    //echo "$inicio_sem <br> $fin_sem <br>" ;

    $query1="SELECT DISTINCT(Consecutivo_En_Programa) FROM $db"."_programacion_semanal WHERE Semana=$semana";
    $resultado1= mysqli_query($conexion, $query1);
    $script1="";
    while ($data1=mysqli_fetch_assoc($resultado1)){
        $Consecutivo_En_Programa=$data1["Consecutivo_En_Programa"];
        $script1 .="AND Consecutivo_En_Programa!=$Consecutivo_En_Programa ";
    }

    $query1_1 = "INSERT INTO $db"."_programacion_semanal (Semana, Consecutivo_En_Programa, Id, Actividad, Descripcion, Clase, Sub_Contratista, Responsable_AIA, Unidad, Compromiso, Critica, Atrasada, Activa, Prog_Sin_Restricciones_100) SELECT 
    $semana, 
    $db"."_programacion_semanal . Consecutivo_en_Programa, 
    $db"."_programacion_semanal . Id, 
    $db"."_programacion_semanal . Actividad,
    CONCAT($db"."_programacion_semanal . Descripcion, ' (No cumplida en la semana anterior)'),
    $db"."_programacion_semanal . Clase,
    $db"."_programacion_semanal . Sub_Contratista,
    $db"."_programacion_semanal . Responsable_AIA,
    $db"."_programacion_semanal . Unidad,
    CASE WHEN ($db"."_programacion_semanal . Compromiso - $db"."_programacion_semanal . Ejecutado_Real)>0 THEN ($db"."_programacion_semanal . Compromiso - $db"."_programacion_semanal . Ejecutado_Real) ELSE NULL END,
    $db"."_programacion_semanal . Critica,
    $db"."_programacion_semanal . Atrasada,
    CASE WHEN ($db"."_programacion_semanal . Activa)='NA' THEN 'NA' ELSE '1' END,
    $db"."_programacion_semanal . Prog_Sin_Restricciones_100

    FROM $db"."_programacion_semanal WHERE Semana=($semana-1) AND (Activa!='NA' OR (Clase='Checklist Tramites' OR Clase='Checklist Consultores' OR Clase='Checklist TPC')) AND (PAC IS NULL OR PAC = 0) $script1";

    //echo "$query1_1 <br>";
    $resultado1_1 = mysqli_query($conexion, $query1_1);
    //echo $query1_1;


    require("../conexion.php");
    $query2="SELECT  COUNT(*) FROM $db"."_programacion_semanal WHERE Semana=$semana";
    //echo "$query2 <br>" ;
    $resultado2= mysqli_query($conexion, $query2);
    $data=mysqli_fetch_assoc($resultado2);
    $conteo=$data["COUNT(*)"];
    //echo "$conteo <br>" ;
    if ($conteo==0){
        //require("../conexion.php");
        $query3 = "INSERT INTO $db"."_programacion_semanal (Semana, Consecutivo_En_Programa, Id, Actividad, Clase, Critica, Atrasada, Activa, Prog_Sin_Restricciones_100) SELECT 
        $semana, 
        $db"."_programa_consolidado . Consecutivo_en_Programa, 
        $db"."_programa_consolidado . Id, 
        $db"."_programa_consolidado . Actividad,
        $db"."_programa_consolidado . Categoria, 
        $db"."_programa_consolidado . Ruta_Critica, 
        CASE WHEN $db"."_programa_consolidado . Estado='Atrasada' THEN '1' ELSE '0' END, 
        '1', 
        CASE WHEN $db"."_programa_consolidado . Estado_Restricciones<1 THEN 1 ELSE 0 END

        FROM $db"."_programa_consolidado WHERE Semana=$semana AND Titulo=0 AND Estado!='Terminada Antes' AND Estado!='OK' AND (Estado='Atrasada' OR (Fecha_Fin>='$inicio_sem' AND Fecha_Inicio<='$fin_sem'))";
        //echo "$query3 <br>";
        $resultado3 = mysqli_query($conexion, $query3);
        verificar_resultado($resultado3);
        cerrar($conexion);
    }else{

        $query3="SELECT DISTINCT(Consecutivo_En_Programa), Activa, Clase FROM $db"."_programacion_semanal WHERE Semana=$semana";
        //echo "$query3 <br>";
        $resultado3= mysqli_query($conexion, $query3);
        $query4="";
        $script_consecutivos="";
        while ($data1=mysqli_fetch_assoc($resultado3)){
            $Consecutivo_En_Programa=$data1["Consecutivo_En_Programa"];
            $Activa=$data1["Activa"];
            $Clase=$data1["Clase"];
            //echo "<li> $Consecutivo_En_Programa, $Activa";
            
            if($Activa!='NA'){
                $query4 .= "UPDATE $db"."_programacion_semanal SET Prog_Sin_Restricciones_100=(SELECT CASE WHEN $db"."_programa_consolidado . Estado_Restricciones<1 THEN 1 ELSE 0 END FROM $db"."_programa_consolidado WHERE Semana=$semana AND Consecutivo_en_Programa=$Consecutivo_En_Programa) WHERE Semana=$semana AND Consecutivo_En_Programa=$Consecutivo_En_Programa AND Activa!='NA';";
            }else{
                $query4 .= "UPDATE $db"."_programacion_semanal SET Prog_Sin_Restricciones_100=0 WHERE Semana=$semana AND Consecutivo_En_Programa=$Consecutivo_En_Programa;";
            }

            if($Activa != 'NA' && $Clase !='Checklist Tramites' && $Clase !='Checklist Consultores' && $Clase !='Checklist TPC'){
                $script_consecutivos .= "AND Consecutivo_En_Programa!=$Consecutivo_En_Programa ";
            }


            //echo "$query4 <br>";
            //echo "$script_consecutivos <br>";
        };


        $resultado4 = mysqli_multi_query($conexion, $query4);


        $query5 = "INSERT INTO $db"."_programacion_semanal (Semana, Consecutivo_En_Programa, Id, Actividad, Clase, Critica, Atrasada, Activa, Prog_Sin_Restricciones_100) SELECT 
        $semana, 
        $db"."_programa_consolidado . Consecutivo_en_Programa, 
        $db"."_programa_consolidado . Id, 
        $db"."_programa_consolidado . Actividad, 
        $db"."_programa_consolidado . Categoria,
        $db"."_programa_consolidado . Ruta_Critica, 
        CASE WHEN $db"."_programa_consolidado . Estado='Atrasada' THEN '1' ELSE '0' END, 
        '1', 
        CASE WHEN $db"."_programa_consolidado . Estado_Restricciones<1 THEN 1 ELSE 0 END

        FROM $db"."_programa_consolidado WHERE Semana=$semana AND Titulo=0 AND Estado!='Terminada Antes' AND Estado!='OK' AND (Estado='Atrasada' OR (Fecha_Fin>='$inicio_sem' AND Fecha_Inicio<='$fin_sem') OR Estado_Restricciones=1) $script_consecutivos";
        //echo "$query5 <br>";

        require("../conexion.php");
        $resultado5 = mysqli_query($conexion, $query5);

        $query6="SELECT DISTINCT($db"."_programacion_semanal.Consecutivo_En_Programa),$db"."_programacion_semanal.Activa,$db"."_programa_consolidado.Estado_Restricciones,$db"."_programa_consolidado.Ejecutado,$db"."_programa_consolidado.Dias_Inicio,$db"."_programa_consolidado.Fecha_Inicio,$db"."_programa_consolidado.Fecha_Fin,$db"."_programa_consolidado.Periodicidad ,$db"."_programacion_semanal.Clase
        FROM $db"."_programacion_semanal 
        INNER JOIN $db"."_programa_consolidado 
        ON $db"."_programacion_semanal.Consecutivo_En_Programa=$db"."_programa_consolidado.Consecutivo_en_Programa 
        WHERE $db"."_programacion_semanal.Semana=$semana AND $db"."_programa_consolidado.Semana=$semana";
        //echo $query6;
        $resultado6 = mysqli_query($conexion, $query6);

        $f_inicio_sem = fecha_inicio_sem($semana, $db, $conexion);

        $script_eliminar="";
        while ($data2=mysqli_fetch_assoc($resultado6)){
            $consecutivo=$data2["Consecutivo_En_Programa"];
            $estado_restricciones=$data2["Estado_Restricciones"];
            $ejecutado=$data2["Ejecutado"];
            $dias_inicio=$data2["Dias_Inicio"];
            $activa=$data2["Activa"];

            //echo "<li>$consecutivo, $estado_restricciones, $ejecutado, $dias_inicio, $activa, $fecha_inicio, $fecha_fin, $clase,$dias_transcurridos, $periodicidad, modulo=$modulo";
            if(($ejecutado==1 || $dias_inicio>7) && $activa!='NA'){
                $script_eliminar .="OR Consecutivo_En_Programa=$consecutivo ";
            }
        }
        $script_eliminar= substr($script_eliminar,3);
        $script_eliminar= substr($script_eliminar,0,-1);
        //echo $script_eliminar;
        if($script_eliminar==''){

        }else{
            $query7="DELETE FROM `$db"."_programacion_semanal` WHERE Semana=$semana AND ($script_eliminar)";
            //echo $query7;
            $resultado7 = mysqli_query($conexion, $query7);    
        }

        verificar_resultado($resultado3);
        cerrar($conexion);

    }  
    



?>