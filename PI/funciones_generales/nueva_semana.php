<?php

    $query="SELECT  COUNT(*) FROM $db"."_semanas_activas";
    $resultado= mysqli_query($conexion, $query);
    if(!$resultado){
        $conteo=0;
    }else{
        $data=mysqli_fetch_assoc($resultado);
        $conteo=$data["COUNT(*)"]; 
        mysqli_free_result($resultado);
    }
    
    $semana_crear=$conteo+1;
    $f_fin_sem= date("Y-m-d",strtotime($f_inicio_sem."+ 7 days"));
    

    $query3 ="INSERT INTO $db"."_semanas_activas (Id, Semana, Fecha_Inicio_Sem, Fecha_Fin_Sem) VALUES (NULL, $semana_crear, '$f_inicio_sem', '$f_fin_sem' );";
    $resultado= mysqli_multi_query($conexion, $query3);
    $errores="";
    verificar_resultado($resultado, $errores);
    


    if($conteo==0){
        $query1 ="UPDATE $db"."_programa SET Id=NULL, Fecha_Inicio=NULL, Fecha_Fin=NULL, Ruta_Critica=NULL, Ejecutado=NULL, Estado=NULL, Dias_Inicio=NULL, Categoria=NULL, Lookahead=NULL, Periodicidad=NULL, R1=NULL, R2=NULL, R3=NULL, R4=NULL, R5=NULL, R6=NULL, R7=NULL, R8=NULL, R9=NULL, R10=NULL, R11=NULL, R12=NULL, R13=NULL, R14=NULL, R15=NULL, R16=NULL, R17=NULL, R18=NULL, R19=NULL, R20=NULL, R21=NULL, R22=NULL, R23=NULL, R24=NULL, R25=NULL  WHERE Titulo=1;";
        $resultado1= mysqli_query($conexion, $query1);
        //sleep(2);

        $query4="INSERT INTO $db"."_programa_consolidado(Consecutivo, Semana, Consecutivo_en_Programa, Id, Actividad, Titulo, Fecha_Inicio, Fecha_Fin, Ruta_Critica, Ejecutado, Estado, Dias_Inicio, Categoria, Lookahead, Periodicidad, Ejecutado_Siguiente_Semana, Checklist, Relevancia, Estado_Restricciones, R1, R2, R3, R4, R5, R6, R7, R8, R9, R10, R11, R12, R13, R14, R15, R16, R17, R18, R19, R20, R21, R22, R23, R24, R25, Observaciones) 

            SELECT NULL, $semana_crear, Consecutivo, Id, Actividad, Titulo, Fecha_Inicio, Fecha_Fin, Ruta_Critica, Ejecutado, Estado, Dias_Inicio, Categoria, Lookahead, Periodicidad, Ejecutado, Checklist, 'NA', 0, R1, R2, R3, R4, R5, R6, R7, R8, R9, R10, R11, R12, R13, R14, R15, R16, R17, R18, R19, R20, R21, R22, R23, R24, R25, NULL FROM $db"."_programa;";

        $resultado2= mysqli_query($conexion, $query4);
        //sleep(2);
        activar_checklists(1, $db, $conexion);
    }else{
        $query4="INSERT INTO $db"."_programa_consolidado(Consecutivo, Semana, Consecutivo_en_Programa, Id, Actividad, Titulo, Fecha_Inicio, Fecha_Fin, Ruta_Critica, Ejecutado, Estado, Dias_Inicio, Categoria, Lookahead, Periodicidad, Ejecutado_Siguiente_Semana, Checklist, Relevancia, Estado_Restricciones, R1, R2, R3, R4, R5, R6, R7, R8, R9, R10, R11, R12, R13, R14, R15, R16, R17, R18, R19, R20, R21, R22, R23, R24, R25, Observaciones) 

            SELECT NULL, $semana_crear, Consecutivo_en_Programa, Id, Actividad, Titulo, Fecha_Inicio, Fecha_Fin, Ruta_Critica, Ejecutado, Estado, Dias_Inicio, Categoria, Lookahead, Periodicidad, Ejecutado_Siguiente_Semana, Checklist, Relevancia, Estado_Restricciones, R1, R2, R3, R4, R5, R6, R7, R8, R9, R10, R11, R12, R13, R14, R15, R16, R17, R18, R19, R20, R21, R22, R23, R24, R25, Observaciones FROM $db"."_programa_consolidado WHERE Semana=$conteo;";

        $resultado2= mysqli_query($conexion, $query4);
    }



    $semana=$semana_crear;

    $query5="SELECT * FROM $db"."_programa_consolidado WHERE Semana=$semana AND (Categoria='periodicas_simples' OR Categoria='periodicas_compuestas')";
    //echo $query5; 
    $resultado3= mysqli_query($conexion, $query5);
    //echo "'$f_inicio_sem', '$f_fin_sem'";
    $query6="";
    while($data=mysqli_fetch_assoc($resultado3)){
        $Consecutivo=$data["Consecutivo"];
        $Fecha_Inicio=date("Y-m-d",strtotime($data["Fecha_Inicio"]));
        $Fecha_Fin=date("Y-m-d",strtotime($data["Fecha_Fin"]));
        $Periodicidad=$data["Periodicidad"];
        $Ejecutado=$data["Ejecutado"];
        $Categoria=$data["Categoria"];
        //echo "<li>Consecutivo=$Consecutivo, Fecha_Inicio=$Fecha_Inicio, Fecha_Fin=$Fecha_Fin, Periodicidad=$Periodicidad";
        $Fecha_Inicio_Modificada=$Fecha_Inicio;
        $Fecha_Fin_Modificada=$Fecha_Fin;

        $j=0;
        if($Categoria=='periodicas_compuestas'){
            for($i=1; $i<26; $i++){
                if($data["R$i"]!='NA'){
                    $j=$j+1;
                }else{
                    $j=$j;
                    $i=26;
                }
            }
        }
        //echo "<li>$Consecutivo -> $Categoria -> $j";

        
        while(((strtotime($f_inicio_sem)-strtotime($Fecha_Inicio_Modificada))/86400)>=0 && $Ejecutado<=0.75 && $Periodicidad!=0 && $Periodicidad!='NA'){
            $Fecha_Inicio_Modificada=date("Y-m-d",strtotime("$Fecha_Inicio_Modificada + $Periodicidad days"));
            $Fecha_Fin_Modificada=date("Y-m-d",strtotime("$Fecha_Fin_Modificada + $Periodicidad days"));
            //echo "<li>$Consecutivo, $Ejecutado, $Fecha_Inicio_Modificada, $Fecha_Fin_Modificada";
        }
        //echo "<li>$Consecutivo, Fecha_Inicio=$Fecha_Inicio, Fecha_Fin=$Fecha_Fin, Periodicidad=$Periodicidad, Fecha_Inicio_Modificada=$Fecha_Inicio_Modificada, Fecha_Fin_Modificada=$Fecha_Fin_Modificada <br>";
        if($Fecha_Inicio != $Fecha_Inicio_Modificada){
            $script =", Ejecutado=0";
        }else{
            $script ="";
        }
        if($j>0 && ($Fecha_Inicio != $Fecha_Inicio_Modificada)){
            for($i=1;$i<($j+1);$i++){
                $script .=", R$i=0";
            }
        }
        $query6 .= "UPDATE $db"."_programa_consolidado SET Fecha_Inicio='$Fecha_Inicio_Modificada', Fecha_Fin='$Fecha_Fin_Modificada' $script WHERE Semana=$semana AND Consecutivo=$Consecutivo;";


    }
    //echo "<li>$query6";
    $resultado4= mysqli_multi_query($conexion, $query6);
    mysqli_free_result($resultado3);



?>