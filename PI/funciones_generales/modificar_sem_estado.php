<?php
    sleep(5);
    $query2="SELECT * FROM $db"."_programa_consolidado WHERE Semana=$semana";
    $resultado5= mysqli_query($conexion, $query2);
	if(!$resultado5){
        mysqli_error($conexion);
    } else{
        $query3="UPDATE $db"."_programa_consolidado SET Dias_Inicio= CASE";

        while($data=mysqli_fetch_assoc($resultado5)){
            $Id=$data["Consecutivo_en_Programa"];
            $actividad=$data["Actividad"];
            $hoy= $f_inicio_sem;
            $manana= date("Y-m-d",strtotime($data["Fecha_Inicio"]));
            $dias=(strtotime($manana)-strtotime($hoy))/86400;
            $dias=floor($dias);
            
            if($dias<0 || $dias==-0){
                $dias=0;
            }
            $query3 .=" WHEN Consecutivo_en_Programa='$Id' THEN '$dias'"; 
        }
        $query3 .=" END WHERE Titulo=0 AND Semana=$semana";
    };

    $resultado6=mysqli_query($conexion, $query3); 

    $fin_semana= date("Y-m-d",strtotime("$f_inicio_sem + 7 days"));
    
    $query4 = "UPDATE $db"."_programa_consolidado SET                                                
                                                 Estado= CASE
                                                    WHEN Fecha_Fin<'$fin_semana' AND Ejecutado=1 THEN 'Terminada' 
                                                    WHEN Fecha_Fin<'$f_inicio_sem' AND Ejecutado<1 THEN 'Atrasada' 
                                                    WHEN Fecha_Fin>='$f_inicio_sem' AND Fecha_Inicio<='$fin_semana' AND Dias_Inicio<=7 AND Estado_Restricciones!='NA' AND Estado_Restricciones<1 AND R1!='NA' AND Ejecutado=0 THEN 'No Puede Comenzar' 
                                                    WHEN (Fecha_Inicio>='$fin_semana' OR Fecha_Fin>='$fin_semana') AND Ejecutado=1 THEN 'Terminada Antes' 
                                                    WHEN Fecha_Fin>='$f_inicio_sem' AND Ejecutado<1 AND Ejecutado>0 THEN 'En Ejecución'
                                                    WHEN Fecha_Fin>='$f_inicio_sem' AND Fecha_Inicio<='$fin_semana' AND Dias_Inicio<=7 AND Estado_Restricciones!='NA' AND (Estado_Restricciones=1 OR R1='NA') AND Ejecutado=0 THEN 'Pendiente de Iniciar'
                                                    WHEN Dias_Inicio <= Lookahead AND Ejecutado=0 THEN 'Programación Intermedia'
                                                    ELSE 'No Requerida'
                                                 END   
                                                WHERE Titulo=0 AND Semana=$semana
                                                ";
    $resultado7=mysqli_multi_query($conexion, $query4); 


?>