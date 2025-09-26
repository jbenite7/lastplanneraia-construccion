<?php

	require ("../conexion.php");
    $semana=/*7*/ $_GET['semana'];
    $db=/*"paris_campestre_pi"*/ $_GET['db'];
    

    $query="SELECT  COUNT(*) FROM $db"."_cic WHERE (Semana=$semana)";
    $resultado= mysqli_query($conexion, $query);
    $data=mysqli_fetch_assoc($resultado);
    $conteo=$data["COUNT(*)"];
    if($conteo>0){
        actualizar_PAC_subcontratistas($semana, $db, $conexion, $semana);
    }

    require ("../conexion.php");
    $query1="SELECT  COUNT(*) FROM $db"."_cip WHERE (Semana=$semana)";
    $resultado1= mysqli_query($conexion, $query1);
    $data1=mysqli_fetch_assoc($resultado1);
    $conteo1=$data1["COUNT(*)"];
    if($conteo1>0){
        actualizar_PAC_profesionales($semana, $db, $conexion, $semana);
    }
    
    require ("../conexion.php");
    $query1="SELECT * FROM $db"."_cic WHERE (Semana=$semana)";
    $resultado1= mysqli_query($conexion, $query1);
    $script_subcontratistas="";
    while ($data1 = mysqli_fetch_assoc($resultado1)){
        $subcontratista=$data1["subcontratista"];
        $script_subcontratistas .="AND Sub_Contratista != '$subcontratista' ";
        //echo $script_subcontratistas;
    }
    mysqli_close($conexion);

    require ("../conexion.php");
    $query1="SELECT * FROM $db"."_cip WHERE (Semana=$semana)";
    $resultado1= mysqli_query($conexion, $query1);
    $script_profesionales="";
    while ($data1 = mysqli_fetch_assoc($resultado1)){
        $profesional=$data1["profesional"];
        $script_profesionales .="AND Responsable_AIA != '$profesional' ";
        //echo $script_profesionales;
    }
    mysqli_close($conexion);

    generar_subcontratistas($semana, $db, $conexion, $conteo, $script_subcontratistas);
    generar_profesionales($semana, $db, $conexion, $conteo1, $script_profesionales);
    listar($db, $semana, $conexion);


    function generar_subcontratistas($semana, $db, $conexion, $conteo, $script_subcontratistas){
        require ("../conexion.php");
        $query2="SELECT DISTINCT Sub_Contratista FROM $db"."_programacion_semanal WHERE Semana=$semana $script_subcontratistas  AND Sub_Contratista !='' AND (Activa='1' OR Activa='NA') AND (PAC='1' OR PAC='0')";
        //echo $query2;
        $resultado2= mysqli_query($conexion, $query2); 
        while($data2=mysqli_fetch_assoc($resultado2)){
            $subcontratista=$data2["Sub_Contratista"];
            $query3="INSERT INTO $db"."_cic (Semana, subcontratista) VALUES (0, '$subcontratista');";
            //echo $query3;
            $resultado3= mysqli_query($conexion, $query3);
        }
        

        actualizar_PAC_subcontratistas($semana, $db, $conexion, $semana1=0);
        actualizar_integral_subcontratistas($semana, $db, $conexion);

    }

    function generar_profesionales($semana, $db, $conexion, $conteo1, $script_profesionales){
        require ("../conexion.php");
        $query2="SELECT DISTINCT Responsable_AIA FROM $db"."_programacion_semanal WHERE Semana=$semana $script_profesionales  AND Responsable_AIA !='' AND (Activa='1' OR Activa='NA') AND (PAC='1' OR PAC='0')";
        //echo $query2;
        $resultado2= mysqli_query($conexion, $query2); 
        while($data2=mysqli_fetch_assoc($resultado2)){
            $profesional=$data2["Responsable_AIA"];
            $query3="INSERT INTO $db"."_cip (Semana, profesional) VALUES (0, '$profesional');";
            //echo $query3;
            $resultado3= mysqli_query($conexion, $query3);     
        }    

        actualizar_PAC_profesionales($semana, $db, $conexion, $semana1=0);
        actualizar_integral_profesionales($semana, $db, $conexion);

    }

    function actualizar_PAC_subcontratistas($semana, $db, $conexion, $semana1){
        $query3 ="SELECT DISTINCT Sub_Contratista FROM $db"."_programacion_semanal WHERE Semana=$semana AND Sub_Contratista !='' AND (Activa='1' OR Activa='NA') AND (PAC='1' OR PAC='0')";
            $resultado3= mysqli_query($conexion, $query3);
            //$conteo1=mysqli_num_rows($resultado3);
            //echo $conteo1;
            $script ="";
            while($data1=mysqli_fetch_assoc($resultado3)){
                $subcontratista = $data1['Sub_Contratista'];
                $query4="SELECT (SELECT ROUND((SUM(P_Completado)/COUNT(P_Completado)),3) FROM $db"."_programacion_semanal WHERE Semana=$semana AND Sub_Contratista ='$subcontratista' AND (Activa=1 OR Activa='NA')) AS 'P_Completado', (SELECT ROUND((SUM(PAC)/COUNT(PAC)),3) FROM $db"."_programacion_semanal WHERE Semana=$semana AND Sub_Contratista ='$subcontratista' AND (Activa=1 OR Activa='NA')) AS 'PAC'";
                //echo $query4;
                $resultado4= mysqli_query($conexion, $query4);
                $data2=mysqli_fetch_assoc($resultado4);
                $PAC=$data2["PAC"];
                $P_Completado=$data2["P_Completado"];
                //echo $PAC, $P_Completado;
                
                $query5 ="UPDATE $db"."_cic INNER JOIN $db"."_subcontratistas ON $db"."_cic . subcontratista = $db"."_subcontratistas . subcontratista SET 
                    $db"."_cic . P_Completado = '$P_Completado', 

                    $db"."_cic . PAC = '$PAC', 

                    $db"."_cic . Semana = $semana, $db"."_cic . correo_contacto = $db"."_subcontratistas . correo_contacto, $db"."_cic . NIT = $db"."_subcontratistas . NIT, $db"."_cic . alcance = $db"."_subcontratistas . alcance, $db"."_cic . tipo_proveedor = $db"."_subcontratistas . tipo_proveedor WHERE $db"."_cic . subcontratista = '$subcontratista'  AND Semana=$semana1;";
                

                $resultado5= mysqli_query($conexion, $query5);
                
                
                //echo $query5 ."<br>" /*. $query4 ."<br>"*/;
                
                $script .="AND subcontratista != '$subcontratista' ";
            }
        
            $query6="DELETE FROM $db"."_cic WHERE Semana=$semana $script";
            //echo $query6 ."<br>";
            $resultado6= mysqli_query($conexion, $query6);
           
            mysqli_free_result($resultado3);
            
            mysqli_close($conexion);


    }
     function actualizar_PAC_profesionales($semana, $db, $conexion, $semana1){
        $query3 ="SELECT DISTINCT Responsable_AIA FROM $db"."_programacion_semanal WHERE Semana=$semana AND Responsable_AIA !='' AND (Activa='1' OR Activa='NA') AND (PAC='1' OR PAC='0')";
            $resultado3= mysqli_query($conexion, $query3);
            $script ="";
            while($data1=mysqli_fetch_assoc($resultado3)){
                $profesional = $data1['Responsable_AIA'];
                $query4="SELECT (SELECT ROUND((SUM(P_Completado)/COUNT(P_Completado)),3) FROM $db"."_programacion_semanal WHERE Semana=$semana AND Responsable_AIA ='$profesional' AND (Activa=1 OR Activa='NA')) AS 'P_Completado', (SELECT ROUND((SUM(PAC)/COUNT(PAC)),3) FROM $db"."_programacion_semanal WHERE Semana=$semana AND Responsable_AIA ='$profesional' AND (Activa=1 OR Activa='NA')) AS 'PAC'";
                //echo $query4 ."<br>";
                $resultado4= mysqli_query($conexion, $query4);
                $data2=mysqli_fetch_assoc($resultado4);
                $PAC=$data2["PAC"];
                $P_Completado=$data2["P_Completado"];
                //echo $PAC, $P_Completado;
                
                $query5 ="UPDATE $db"."_cip INNER JOIN $db"."_profesionales ON $db"."_cip . profesional = $db"."_profesionales . nombre SET 
                    $db"."_cip . P_Completado = $P_Completado, 

                    $db"."_cip . PAC = $PAC, 

                    $db"."_cip . Semana = $semana, $db"."_cip . correo_contacto = $db"."_profesionales . email WHERE $db"."_cip . profesional = '$profesional'  AND Semana=$semana1;";
                    
                $resultado5= mysqli_query($conexion, $query5);
                //echo $query5 ."<br>" . $query4 ."<br>";
                
                $script .="AND profesional != '$profesional' ";
            }
         
            $query6="DELETE FROM $db"."_cip WHERE Semana=$semana $script";
            //echo $query6 ."<br>";
            
            $resultado6= mysqli_query($conexion, $query6);
            
         
            mysqli_free_result($resultado3);
            mysqli_close($conexion);


    }

    function actualizar_integral_subcontratistas($semana, $db, $conexion){
        require("../conexion.php");
        $query5 ="SELECT * FROM $db"."_cic WHERE Semana=$semana;";
        $resultado5= mysqli_query($conexion, $query5);

        while ($cic = mysqli_fetch_assoc($resultado5)){
            $Id=$cic['Id'];
            $subcontratista=$cic['subcontratista'];
            
            $query6 ="SELECT (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_cic WHERE Semana<=$semana AND subcontratista='$subcontratista' AND PAC!='NA')=0 THEN NULL ELSE (SELECT ROUND(AVG(PAC),3) FROM $db"."_cic WHERE Semana<=$semana AND subcontratista='$subcontratista' AND PAC!='NA') END) AS 'PAC_Acum',
            
            (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_cic WHERE Semana<=$semana AND subcontratista='$subcontratista' AND P_Completado!='NA')=0 THEN NULL ELSE (SELECT ROUND(AVG(P_Completado),3) FROM $db"."_cic WHERE Semana<=$semana AND subcontratista='$subcontratista' AND P_Completado!='NA') END) AS 'P_Completado_Acum',
            
            (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_cic WHERE Semana<=$semana AND subcontratista='$subcontratista' AND Calidad!='NA')=0 THEN 'NA' ELSE (SELECT ROUND(AVG(Calidad),3) FROM $db"."_cic WHERE Semana<=$semana AND subcontratista='$subcontratista' AND Calidad!='NA') END) AS 'Calidad_Acum',
            
            (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_cic WHERE Semana<=$semana AND subcontratista='$subcontratista' AND GSA!='NA')=0 THEN 'NA' ELSE (SELECT ROUND(AVG(GSA),3) FROM $db"."_cic WHERE Semana<=$semana AND subcontratista='$subcontratista' AND GSA!='NA') END) AS 'GSA_Acum',
            
            (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_cic WHERE Semana<=$semana AND subcontratista='$subcontratista' AND SST!='NA')=0 THEN 'NA' ELSE (SELECT ROUND(AVG(SST),3) FROM $db"."_cic WHERE Semana<=$semana AND subcontratista='$subcontratista' AND SST!='NA') END) AS 'SST_Acum',
            
            (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_cic WHERE Semana<=$semana AND subcontratista='$subcontratista' AND ADM!='NA')=0 THEN 'NA' ELSE (SELECT ROUND(AVG(ADM),3) FROM $db"."_cic WHERE Semana<=$semana AND subcontratista='$subcontratista' AND ADM!='NA') END) AS 'ADM_Acum'
            
            ";
            
            $resultado6= mysqli_query($conexion, $query6);
            
            $data=mysqli_fetch_assoc($resultado6);
            $PAC_Acum=$data["PAC_Acum"];
            $P_Completado_Acum=$data["P_Completado_Acum"];
            $Calidad_Acum=$data["Calidad_Acum"];
            $GSA_Acum=$data["GSA_Acum"];
            $SST_Acum=$data["SST_Acum"];
            $ADM_Acum=$data["ADM_Acum"];
            
            
            
            $query6_1 = "UPDATE $db"."_cic SET 
                PAC_Acum = '$PAC_Acum', 
                
                P_Completado_Acum = '$P_Completado_Acum', 
                
                Calidad_Acum = '$Calidad_Acum',
                
                GSA_Acum = '$GSA_Acum',
                
                SST_Acum = '$SST_Acum',
                
                ADM_Acum = '$ADM_Acum'
                
                WHERE Id=$Id";

            $resultado6_1= mysqli_query($conexion, $query6_1); 
        
            
            $query7 ="SELECT * FROM $db"."_cic WHERE Id=$Id;";
            $resultado7= mysqli_query($conexion, $query7);
            $cic1 = mysqli_fetch_assoc($resultado7);
            
            $PAC=$cic1['PAC'];
            $PAC_acum=$cic1['PAC_Acum'];
            $calidad=$cic1['Calidad'];
            $calidad_acum=$cic1['Calidad_Acum'];
            $adm=$cic1['ADM'];
            $adm_acum=$cic1['ADM_Acum'];
            $gsa=$cic1['GSA'];
            $gsa_acum=$cic1['GSA_Acum'];
            $sst=$cic1['SST'];
            $sst_acum=$cic1['SST_Acum'];
            
            if($calidad=='NA'){
                if($sst=='NA'){
                    if($gsa=='NA'){
                        if($adm=='NA'){
                            $cal_integral=$PAC*(0.3+(0.7/7)*7);
                        }else{
                            $cal_integral=$PAC*(0.3+(0.6/4)*3)+$adm*(0.1+(0.6/4)*1);
                        }
                    }else{
                        if($adm=='NA'){
                            $cal_integral=$PAC*(0.3+(0.5/5)*3)+$gsa*(0.2+(0.5/5)*2);
                        }else{
                            $cal_integral=$PAC*(0.3+(0.4/6)*3)+$gsa*(0.2+(0.4/6)*2)+$adm*(0.1+(0.4/6)*1);
                        }
                    }
                }else{
                    if($gsa=='NA'){
                        if($adm=='NA'){
                            $cal_integral=$PAC*(0.3+(0.5/5)*3)+$sst*(0.2+(0.5/5)*2);
                        }else{
                            $cal_integral=$PAC*(0.3+(0.4/6)*3)+$sst*(0.2+(0.4/6)*2)+$adm*(0.1+(0.4/6)*1);
                        }
                    }else{
                        if($adm=='NA'){
                            $cal_integral=$PAC*(0.3+(0.3/7)*3)+$sst*(0.2+(0.3/7)*2)+$gsa*(0.2+(0.3/7)*2);
                        }else{
                            $cal_integral=$PAC*(0.3+(0.2/8)*3)+$sst*(0.2+(0.2/8)*2)+$gsa*(0.2+(0.2/8)*2)+$adm*(0.1+(0.2/8)*1);
                        }
                    }
                }
            }else{
                if($sst=='NA'){
                    if($gsa=='NA'){
                        if($adm=='NA'){
                            $cal_integral=$PAC*(0.3+(0.5/5)*3)+$calidad*(0.2+(0.5/5)*2);
                        }else{
                            $cal_integral=$PAC*(0.3+(0.4/6)*3)+$calidad*(0.2+(0.4/6)*2)+$adm*(0.1+(0.4/6)*1);
                        }
                    }else{
                        if($adm=='NA'){
                            $cal_integral=$PAC*(0.3+(0.3/7)*3)+$calidad*(0.2+(0.3/7)*2)+$gsa*(0.2+(0.3/7)*2);
                        }else{
                            $cal_integral=$PAC*(0.3+(0.2/8)*3)+$calidad*(0.2+(0.2/8)*2)+$gsa*(0.2+(0.2/8)*2)+$adm*(0.1+(0.2/8)*1);
                        }
                    }
                }else{
                    if($gsa=='NA'){
                        if($adm=='NA'){
                            $cal_integral=$PAC*(0.3+(0.3/7)*3)+$calidad*(0.2+(0.3/7)*2)+$sst*(0.2+(0.3/7)*2);
                        }else{
                            $cal_integral=$PAC*(0.3+(0.2/8)*3)+$calidad*(0.2+(0.2/8)*2)+$sst*(0.2+(0.2/8)*2)+$adm*(0.1+(0.2/8)*1);
                        }
                    }else{
                        if($adm=='NA'){
                            $cal_integral=$PAC*(0.3+(0.1/9)*3)+$calidad*(0.2+(0.1/9)*2)+$sst*(0.2+(0.1/9)*2)+$gsa*(0.2+(0.1/9)*2);
                        }else{
                            $cal_integral=$PAC*(0.3+(0.0/10)*3)+$calidad*(0.2+(0.0/10)*2)+$sst*(0.2+(0.0/10)*2)+$gsa*(0.2+(0.0/10)*2)+$adm*(0.1+(0.0/10)*1);
                        }
                    }
                }
            }
                                                
                                                
            if($calidad_acum=='NA'){
                if($sst_acum=='NA'){
                    if($gsa_acum=='NA'){
                        if($adm_acum=='NA'){
                            $cal_integral_acum=$PAC_acum*(0.3+(0.7/7)*7);
                        }else{
                            $cal_integral_acum=$PAC_acum*(0.3+(0.6/4)*3)+$adm_acum*(0.1+(0.6/4)*1);
                        }
                    }else{
                        if($adm_acum=='NA'){
                            $cal_integral_acum=$PAC_acum*(0.3+(0.5/5)*3)+$gsa_acum*(0.2+(0.5/5)*2);
                        }else{
                            $cal_integral_acum=$PAC_acum*(0.3+(0.4/6)*3)+$gsa_acum*(0.2+(0.4/6)*2)+$adm_acum*(0.1+(0.4/6)*1);
                        }
                    }
                }else{
                    if($gsa_acum=='NA'){
                        if($adm_acum=='NA'){
                            $cal_integral_acum=$PAC_acum*(0.3+(0.5/5)*3)+$sst_acum*(0.2+(0.5/5)*2);
                        }else{
                            $cal_integral_acum=$PAC_acum*(0.3+(0.4/6)*3)+$sst_acum*(0.2+(0.4/6)*2)+$adm_acum*(0.1+(0.4/6)*1);
                        }
                    }else{
                        if($adm_acum=='NA'){
                            $cal_integral_acum=$PAC_acum*(0.3+(0.3/7)*3)+$sst_acum*(0.2+(0.3/7)*2)+$gsa_acum*(0.2+(0.3/7)*2);
                        }else{
                            $cal_integral_acum=$PAC_acum*(0.3+(0.2/8)*3)+$sst_acum*(0.2+(0.2/8)*2)+$gsa_acum*(0.2+(0.2/8)*2)+$adm_acum*(0.1+(0.2/8)*1);
                        }
                    }
                }
            }else{
                if($sst_acum=='NA'){
                    if($gsa_acum=='NA'){
                        if($adm_acum=='NA'){
                            $cal_integral_acum=$PAC_acum*(0.3+(0.5/5)*3)+$calidad_acum*(0.2+(0.5/5)*2);
                        }else{
                            $cal_integral_acum=$PAC_acum*(0.3+(0.4/6)*3)+$calidad_acum*(0.2+(0.4/6)*2)+$adm_acum*(0.1+(0.4/6)*1);
                        }
                    }else{
                        if($adm_acum=='NA'){
                            $cal_integral_acum=$PAC_acum*(0.3+(0.3/7)*3)+$calidad_acum*(0.2+(0.3/7)*2)+$gsa_acum*(0.2+(0.3/7)*2);
                        }else{
                            $cal_integral_acum=$PAC_acum*(0.3+(0.2/8)*3)+$calidad_acum*(0.2+(0.2/8)*2)+$gsa_acum*(0.2+(0.2/8)*2)+$adm_acum*(0.1+(0.2/8)*1);
                        }
                    }
                }else{
                    if($gsa_acum=='NA'){
                        if($adm_acum=='NA'){
                            $cal_integral_acum=$PAC_acum*(0.3+(0.3/7)*3)+$calidad_acum*(0.2+(0.3/7)*2)+$sst_acum*(0.2+(0.3/7)*2);
                        }else{
                            $cal_integral_acum=$PAC_acum*(0.3+(0.2/8)*3)+$calidad_acum*(0.2+(0.2/8)*2)+$sst_acum*(0.2+(0.2/8)*2)+$adm_acum*(0.1+(0.2/8)*1);
                        }
                    }else{
                        if($adm_acum=='NA'){
                            $cal_integral_acum=$PAC_acum*(0.3+(0.1/9)*3)+$calidad_acum*(0.2+(0.1/9)*2)+$sst_acum*(0.2+(0.1/9)*2)+$gsa_acum*(0.2+(0.1/9)*2);
                        }else{
                            $cal_integral_acum=$PAC_acum*(0.3+(0.0/10)*3)+$calidad_acum*(0.2+(0.0/10)*2)+$sst_acum*(0.2+(0.0/10)*2)+$gsa_acum*(0.2+(0.0/10)*2)+$adm_acum*(0.1+(0.0/10)*1);
                        }
                    }
                }
            }
            
            //echo "<li>" . $PAC_acum . "<li>" . $calidad_acum . "<li>" . $gsa_acum . "<li>" . $sst_acum . "<li>" . $adm_acum . "<li>" . $cal_integral_acum ;
            
            $query7 = "UPDATE $db"."_cic SET Cal_Integral = ROUND($cal_integral,3), Cal_Integral_Acum = ROUND($cal_integral_acum,3) WHERE Id=ROUND($Id,3);";
            
            //echo $query7;
            $resultado7= mysqli_query($conexion, $query7); 
            
        };
            //echo $query8;  

            //$resultado4= mysqli_multi_query($conexion, $query4);
            mysqli_close($conexion);
            //mysqli_free_result($resultado);
    }

    function actualizar_integral_profesionales($semana, $db, $conexion){
        require("../conexion.php");
        $query5 ="SELECT * FROM $db"."_cip WHERE Semana=$semana;";
        $resultado5= mysqli_query($conexion, $query5);
        //$query6="";
        //$query7=""'NA'
        while ($cip = mysqli_fetch_assoc($resultado5)){
            $Profesional=$cip['profesional'];
            $query5_1="SELECT (SELECT CASE WHEN (SELECT COUNT(Critica) FROM $db"."_programacion_semanal WHERE Semana=$semana AND Responsable_AIA ='$Profesional' AND Activa=1 AND Critica=1 AND Atrasada=0)>0 THEN (SELECT ROUND((SELECT COUNT(Critica) FROM $db"."_programacion_semanal WHERE Semana=$semana AND Responsable_AIA ='$Profesional' AND Activa=1 AND Critica=1 AND Atrasada=0 AND PAC=1) / (SELECT COUNT(Critica) FROM $db"."_programacion_semanal WHERE Semana=$semana AND Responsable_AIA ='$Profesional' AND Activa=1 AND Critica=1 AND Atrasada=0),3)) ELSE 'NA' END) AS 'Act_Criticas_Cumplidas', 
            
            (SELECT CASE WHEN (SELECT COUNT(Critica) FROM $db"."_programacion_semanal WHERE Semana=$semana AND Responsable_AIA ='$Profesional' AND (Activa=1 OR Activa='NA') AND Critica=0 AND Atrasada=0)>0  THEN (SELECT ROUND((SELECT COUNT(Critica) FROM $db"."_programacion_semanal WHERE Semana=$semana AND Responsable_AIA ='$Profesional' AND (Activa=1 OR Activa='NA') AND Critica=0 AND Atrasada=0 AND PAC=1) / (SELECT COUNT(Critica) FROM $db"."_programacion_semanal WHERE Semana=$semana AND Responsable_AIA ='$Profesional' AND (Activa=1 OR Activa='NA') AND Critica=0 AND Atrasada=0),3)) ELSE 'NA' END) AS 'Act_No_Criticas_Cumplidas',
            
            (SELECT CASE WHEN (SELECT COUNT(Atrasada) FROM $db"."_programacion_semanal WHERE Semana=$semana AND Responsable_AIA ='$Profesional' AND Activa=1 AND Atrasada=1)>0  THEN (SELECT ROUND((SELECT COUNT(Atrasada) FROM $db"."_programacion_semanal WHERE Semana=$semana AND Responsable_AIA ='$Profesional' AND Activa=1 AND Atrasada=1 AND PAC=1) / (SELECT COUNT(Atrasada) FROM $db"."_programacion_semanal WHERE Semana=$semana AND Responsable_AIA ='$Profesional' AND Activa=1 AND Atrasada=1),3)) ELSE 'NA' END) AS 'Act_Atrasadas_Cumplidas'
            
            ";
            //echo $query5_1;
            $resultado5_1= mysqli_query($conexion, $query5_1);
            
            $data2=mysqli_fetch_assoc($resultado5_1);
            $Act_Criticas_Cumplidas=$data2["Act_Criticas_Cumplidas"];
            $Act_No_Criticas_Cumplidas=$data2["Act_No_Criticas_Cumplidas"];
            $Act_Atrasadas_Cumplidas=$data2["Act_Atrasadas_Cumplidas"];
                
            $query6 = "UPDATE $db"."_cip SET
                
                Act_Criticas_Cumplidas = '$Act_Criticas_Cumplidas',
                
                Act_No_Criticas_Cumplidas = '$Act_No_Criticas_Cumplidas',
                
                Act_Atrasadas_Cumplidas = '$Act_Atrasadas_Cumplidas'
                
                WHERE profesional='$Profesional' AND Semana=$semana;";
            //echo $query6;
            
            $resultado6= mysqli_multi_query($conexion, $query6);
            
            
            $query7= "SELECT (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_cip WHERE Semana<=$semana AND profesional='$Profesional' AND PAC!='NA')=0 THEN NULL ELSE (SELECT ROUND(AVG(PAC),3) FROM $db"."_cip WHERE Semana<=$semana AND profesional='$Profesional' AND PAC!='NA') END) AS 'PAC_Acum', 
            
            (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_cip WHERE Semana<=$semana AND profesional='$Profesional' AND P_Completado!='NA')=0 THEN NULL ELSE (SELECT ROUND(AVG(P_Completado),3) FROM $db"."_cip WHERE Semana<=$semana AND profesional='$Profesional' AND P_Completado!='NA') END) AS 'P_Completado_Acum', 
            
            (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_cip WHERE Semana<=$semana AND profesional='$Profesional' AND Act_Criticas_Cumplidas!='NA')=0 THEN 'NA' ELSE (SELECT ROUND(AVG(Act_Criticas_Cumplidas),3) FROM $db"."_cip WHERE Semana<=$semana AND profesional='$Profesional' AND Act_Criticas_Cumplidas!='NA') END) AS 'Act_Criticas_Cumplidas_Acum', 
            
            (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_cip WHERE Semana<=$semana AND profesional='$Profesional' AND Act_No_Criticas_Cumplidas!='NA')=0 THEN 'NA' ELSE (SELECT ROUND(AVG(Act_No_Criticas_Cumplidas),3) FROM $db"."_cip WHERE Semana<=$semana AND profesional='$Profesional' AND Act_No_Criticas_Cumplidas!='NA') END) AS 'Act_No_Criticas_Cumplidas_Acum',
            
            (SELECT CASE WHEN (SELECT COUNT(*) FROM $db"."_cip WHERE Semana<=$semana AND profesional='$Profesional' AND Act_Atrasadas_Cumplidas!='NA')=0 THEN 'NA' ELSE (SELECT ROUND(AVG(Act_Atrasadas_Cumplidas),3) FROM $db"."_cip WHERE Semana<=$semana AND profesional='$Profesional' AND Act_Atrasadas_Cumplidas!='NA') END) AS 'Act_Atrasadas_Cumplidas_Acum'";
            
            $resultado7= mysqli_query($conexion, $query7);
            
            $data3=mysqli_fetch_assoc($resultado7);
            
            $PAC_Acum=$data3["PAC_Acum"];
            $P_Completado_Acum=$data3["P_Completado_Acum"];
            $Act_Criticas_Cumplidas_Acum=$data3["Act_Criticas_Cumplidas_Acum"];
            $Act_No_Criticas_Cumplidas_Acum=$data3["Act_No_Criticas_Cumplidas_Acum"];
            $Act_Atrasadas_Cumplidas_Acum=$data3["Act_Atrasadas_Cumplidas_Acum"];
            
            //echo $Profesional . "<br>" . $Act_Criticas_Cumplidas_Acum . "<br>" . $Act_No_Criticas_Cumplidas_Acum . "<br>" . $Act_Atrasadas_Cumplidas_Acum . "<br>" ;
            
            $query7_1 = "UPDATE $db"."_cip SET
                PAC_Acum = $PAC_Acum,
                
                P_Completado_Acum = $P_Completado_Acum,
                
                Act_Criticas_Cumplidas_Acum = '$Act_Criticas_Cumplidas_Acum',
                
                Act_No_Criticas_Cumplidas_Acum = '$Act_No_Criticas_Cumplidas_Acum',
                
                Act_Atrasadas_Cumplidas_Acum = '$Act_Atrasadas_Cumplidas_Acum'
                
                WHERE profesional='$Profesional' AND Semana=$semana;";
            //echo $query7_1 ."<br><br>";
                
            $query8 = "SELECT (SELECT CASE
                    
                    WHEN (SELECT Act_Criticas_Cumplidas FROM $db"."_cip WHERE profesional='$Profesional' AND Semana=$semana)!='NA' AND (SELECT Act_No_Criticas_Cumplidas FROM $db"."_cip WHERE profesional='$Profesional' AND Semana=$semana)!='NA' AND (SELECT Act_Atrasadas_Cumplidas FROM $db"."_cip WHERE profesional='$Profesional' AND Semana=$semana)!='NA' THEN ROUND(((SELECT PAC FROM $db"."_cip WHERE profesional='$Profesional' AND Semana=$semana) * ((SELECT Act_Criticas_Cumplidas FROM $db"."_cip WHERE profesional='$Profesional' AND Semana=$semana) * 0.4 + (SELECT Act_No_Criticas_Cumplidas FROM $db"."_cip WHERE profesional='$Profesional' AND Semana=$semana) * 0.2 + (SELECT Act_Atrasadas_Cumplidas FROM $db"."_cip WHERE profesional='$Profesional' AND Semana=$semana) * 0.4)),3)
                    
                    WHEN (SELECT Act_Criticas_Cumplidas FROM $db"."_cip WHERE profesional='$Profesional' AND Semana=$semana)!='NA' AND (SELECT Act_No_Criticas_Cumplidas FROM $db"."_cip WHERE profesional='$Profesional' AND Semana=$semana)!='NA' AND (SELECT Act_Atrasadas_Cumplidas FROM $db"."_cip WHERE profesional='$Profesional' AND Semana=$semana)='NA' THEN ROUND(((SELECT PAC FROM $db"."_cip WHERE profesional='$Profesional' AND Semana=$semana) * ((SELECT Act_Criticas_Cumplidas FROM $db"."_cip WHERE profesional='$Profesional' AND Semana=$semana) * 0.6667 + (SELECT Act_No_Criticas_Cumplidas FROM $db"."_cip WHERE profesional='$Profesional' AND Semana=$semana) * 0.3333)),3)
                    
                    WHEN (SELECT Act_Criticas_Cumplidas FROM $db"."_cip WHERE profesional='$Profesional' AND Semana=$semana)!='NA' AND (SELECT Act_No_Criticas_Cumplidas FROM $db"."_cip WHERE profesional='$Profesional' AND Semana=$semana)='NA' AND (SELECT Act_Atrasadas_Cumplidas FROM $db"."_cip WHERE profesional='$Profesional' AND Semana=$semana)!='NA' THEN ROUND(((SELECT PAC FROM $db"."_cip WHERE profesional='$Profesional' AND Semana=$semana) * ((SELECT Act_Criticas_Cumplidas FROM $db"."_cip WHERE profesional='$Profesional' AND Semana=$semana) * 0.5 + (SELECT Act_Atrasadas_Cumplidas FROM $db"."_cip WHERE profesional='$Profesional' AND Semana=$semana) * 0.5)),3) 
                    
                    WHEN (SELECT Act_Criticas_Cumplidas FROM $db"."_cip WHERE profesional='$Profesional' AND Semana=$semana)='NA' AND (SELECT Act_No_Criticas_Cumplidas FROM $db"."_cip WHERE profesional='$Profesional' AND Semana=$semana)!='NA' AND (SELECT Act_Atrasadas_Cumplidas FROM $db"."_cip WHERE profesional='$Profesional' AND Semana=$semana)!='NA' THEN ROUND(((SELECT PAC FROM $db"."_cip WHERE profesional='$Profesional' AND Semana=$semana) * ((SELECT Act_No_Criticas_Cumplidas FROM $db"."_cip WHERE profesional='$Profesional' AND Semana=$semana) * 0.3333 + (SELECT Act_Atrasadas_Cumplidas FROM $db"."_cip WHERE profesional='$Profesional' AND Semana=$semana) * 0.6667)),3) 
                    
                    WHEN (SELECT Act_Criticas_Cumplidas FROM $db"."_cip WHERE profesional='$Profesional' AND Semana=$semana)!='NA' AND (SELECT Act_No_Criticas_Cumplidas FROM $db"."_cip WHERE profesional='$Profesional' AND Semana=$semana)='NA' AND (SELECT Act_Atrasadas_Cumplidas FROM $db"."_cip WHERE profesional='$Profesional' AND Semana=$semana)='NA' THEN ROUND(((SELECT PAC FROM $db"."_cip WHERE profesional='$Profesional' AND Semana=$semana) * ((SELECT Act_Criticas_Cumplidas FROM $db"."_cip WHERE profesional='$Profesional' AND Semana=$semana))),3) 
                    
                    WHEN (SELECT Act_Criticas_Cumplidas FROM $db"."_cip WHERE profesional='$Profesional' AND Semana=$semana)='NA' AND (SELECT Act_No_Criticas_Cumplidas FROM $db"."_cip WHERE profesional='$Profesional' AND Semana=$semana)!='NA' AND (SELECT Act_Atrasadas_Cumplidas FROM $db"."_cip WHERE profesional='$Profesional' AND Semana=$semana)='NA' THEN ROUND(((SELECT PAC FROM $db"."_cip WHERE profesional='$Profesional' AND Semana=$semana) * ((SELECT Act_No_Criticas_Cumplidas FROM $db"."_cip WHERE profesional='$Profesional' AND Semana=$semana))),3)
                    
                    WHEN (SELECT Act_Criticas_Cumplidas FROM $db"."_cip WHERE profesional='$Profesional' AND Semana=$semana)='NA' AND (SELECT Act_No_Criticas_Cumplidas FROM $db"."_cip WHERE profesional='$Profesional' AND Semana=$semana)='NA' AND (SELECT Act_Atrasadas_Cumplidas FROM $db"."_cip WHERE profesional='$Profesional' AND Semana=$semana)!='NA' THEN ROUND(((SELECT PAC FROM $db"."_cip WHERE profesional='$Profesional' AND Semana=$semana) * ((SELECT Act_Atrasadas_Cumplidas FROM $db"."_cip WHERE profesional='$Profesional' AND Semana=$semana))) ,3)
                    
                    END) AS 'PAC_Consolidado',
                    
                    
                    (SELECT CASE
                    
                    WHEN (SELECT Act_Criticas_Cumplidas_Acum FROM $db"."_cip WHERE profesional='$Profesional' AND Semana=$semana)!='NA' AND (SELECT Act_No_Criticas_Cumplidas_Acum FROM $db"."_cip WHERE profesional='$Profesional' AND Semana=$semana)!='NA' AND (SELECT Act_Atrasadas_Cumplidas_Acum FROM $db"."_cip WHERE profesional='$Profesional' AND Semana=$semana)!='NA' THEN ROUND(((SELECT PAC_Acum FROM $db"."_cip WHERE profesional='$Profesional' AND Semana=$semana) * ((SELECT Act_Criticas_Cumplidas_Acum FROM $db"."_cip WHERE profesional='$Profesional' AND Semana=$semana) * 0.4 + (SELECT Act_No_Criticas_Cumplidas_Acum FROM $db"."_cip WHERE profesional='$Profesional' AND Semana=$semana) * 0.2 + (SELECT Act_Atrasadas_Cumplidas_Acum FROM $db"."_cip WHERE profesional='$Profesional' AND Semana=$semana) * 0.4)),3) 
                    
                    WHEN (SELECT Act_Criticas_Cumplidas_Acum FROM $db"."_cip WHERE profesional='$Profesional' AND Semana=$semana)!='NA' AND (SELECT Act_No_Criticas_Cumplidas_Acum FROM $db"."_cip WHERE profesional='$Profesional' AND Semana=$semana)!='NA' AND (SELECT Act_Atrasadas_Cumplidas_Acum FROM $db"."_cip WHERE profesional='$Profesional' AND Semana=$semana)='NA' THEN ROUND(((SELECT PAC_Acum FROM $db"."_cip WHERE profesional='$Profesional' AND Semana=$semana) * ((SELECT Act_Criticas_Cumplidas_Acum FROM $db"."_cip WHERE profesional='$Profesional' AND Semana=$semana) * 0.6667 + (SELECT Act_No_Criticas_Cumplidas_Acum FROM $db"."_cip WHERE profesional='$Profesional' AND Semana=$semana) * 0.3333)),3)
                    
                    WHEN (SELECT Act_Criticas_Cumplidas_Acum FROM $db"."_cip WHERE profesional='$Profesional' AND Semana=$semana)!='NA' AND (SELECT Act_No_Criticas_Cumplidas_Acum FROM $db"."_cip WHERE profesional='$Profesional' AND Semana=$semana)='NA' AND (SELECT Act_Atrasadas_Cumplidas_Acum FROM $db"."_cip WHERE profesional='$Profesional' AND Semana=$semana)!='NA' THEN ROUND(((SELECT PAC_Acum FROM $db"."_cip WHERE profesional='$Profesional' AND Semana=$semana) * ((SELECT Act_Criticas_Cumplidas_Acum FROM $db"."_cip WHERE profesional='$Profesional' AND Semana=$semana) * 0.5 + (SELECT Act_Atrasadas_Cumplidas_Acum FROM $db"."_cip WHERE profesional='$Profesional' AND Semana=$semana) * 0.5)),3) 
                    
                    WHEN (SELECT Act_Criticas_Cumplidas_Acum FROM $db"."_cip WHERE profesional='$Profesional' AND Semana=$semana)='NA' AND (SELECT Act_No_Criticas_Cumplidas_Acum FROM $db"."_cip WHERE profesional='$Profesional' AND Semana=$semana)!='NA' AND (SELECT Act_Atrasadas_Cumplidas_Acum FROM $db"."_cip WHERE profesional='$Profesional' AND Semana=$semana)!='NA' THEN ROUND(((SELECT PAC_Acum FROM $db"."_cip WHERE profesional='$Profesional' AND Semana=$semana) * ((SELECT Act_No_Criticas_Cumplidas_Acum FROM $db"."_cip WHERE profesional='$Profesional' AND Semana=$semana) * 0.3333 + (SELECT Act_Atrasadas_Cumplidas_Acum FROM $db"."_cip WHERE profesional='$Profesional' AND Semana=$semana) * 0.6667)),3) 
                    
                    WHEN (SELECT Act_Criticas_Cumplidas_Acum FROM $db"."_cip WHERE profesional='$Profesional' AND Semana=$semana)!='NA' AND (SELECT Act_No_Criticas_Cumplidas_Acum FROM $db"."_cip WHERE profesional='$Profesional' AND Semana=$semana)='NA' AND (SELECT Act_Atrasadas_Cumplidas_Acum FROM $db"."_cip WHERE profesional='$Profesional' AND Semana=$semana)='NA' THEN ROUND(((SELECT PAC_Acum FROM $db"."_cip WHERE profesional='$Profesional' AND Semana=$semana) * ((SELECT Act_Criticas_Cumplidas_Acum FROM $db"."_cip WHERE profesional='$Profesional' AND Semana=$semana))),3) 
                    
                    WHEN (SELECT Act_Criticas_Cumplidas_Acum FROM $db"."_cip WHERE profesional='$Profesional' AND Semana=$semana)='NA' AND (SELECT Act_No_Criticas_Cumplidas_Acum FROM $db"."_cip WHERE profesional='$Profesional' AND Semana=$semana)!='NA' AND (SELECT Act_Atrasadas_Cumplidas_Acum FROM $db"."_cip WHERE profesional='$Profesional' AND Semana=$semana)='NA' THEN ROUND(((SELECT PAC_Acum FROM $db"."_cip WHERE profesional='$Profesional' AND Semana=$semana) * ((SELECT Act_No_Criticas_Cumplidas_Acum FROM $db"."_cip WHERE profesional='$Profesional' AND Semana=$semana))),3)
                    
                    WHEN (SELECT Act_Criticas_Cumplidas_Acum FROM $db"."_cip WHERE profesional='$Profesional' AND Semana=$semana)='NA' AND (SELECT Act_No_Criticas_Cumplidas_Acum FROM $db"."_cip WHERE profesional='$Profesional' AND Semana=$semana)='NA' AND (SELECT Act_Atrasadas_Cumplidas_Acum FROM $db"."_cip WHERE profesional='$Profesional' AND Semana=$semana)!='NA' THEN ROUND(((SELECT PAC_Acum FROM $db"."_cip WHERE profesional='$Profesional' AND Semana=$semana) * ((SELECT Act_Atrasadas_Cumplidas_Acum FROM $db"."_cip WHERE profesional='$Profesional' AND Semana=$semana))),3) 
                    
                    END) AS 'PAC_Consolidado_Acum'
                    
                    ";
            
            //echo $query7;
            $resultado7_1= mysqli_query($conexion, $query7_1);
            /*echo $query7_1 ."<br>";
            if(!$resultado7_1){
                die(mysqli_error($conexion))."<br><br>";
            } else{
                echo "OK"."<br><br>";
            }*/
            
            $resultado8= mysqli_query($conexion, $query8);
            //echo $query8 ."<br>";
            /*if(!$resultado8){
                die(mysqli_error($conexion))."<br><br>";
            } else{
                echo "OK"."<br><br>";
            }*/
            
            $data4=mysqli_fetch_assoc($resultado8);
            
            $PAC_Consolidado=$data4["PAC_Consolidado"];
            $PAC_Consolidado_Acum=$data4["PAC_Consolidado_Acum"];
            //echo $Profesional ."<br>". $PAC_Consolidado ."<br>". $PAC_Consolidado_Acum ."<br>";
            
            $query8_1 = "UPDATE $db"."_cip SET
                
                PAC_Consolidado = $PAC_Consolidado ,
                                            
                PAC_Consolidado_Acum = $PAC_Consolidado_Acum
                
                WHERE profesional='$Profesional' AND Semana=$semana;";
            
            $resultado8_1= mysqli_query($conexion, $query8_1);
            /*echo $query8_1 ."<br>";
            if(!$resultado8_1){
                die(mysqli_error($conexion))."<br><br>";
            } else{
                echo "OK"."<br><br>";
            }*/
            
        };
            

            //$resultado4= mysqli_multi_query($conexion, $query4);
            mysqli_close($conexion);
            //mysqli_free_result($resultado);
    }

    function listar($db, $semana, $conexion){
        require ("../conexion.php");
        $query7 = "SELECT COUNT(*) FROM $db"."_cic WHERE (Semana=$semana)";
        $resultado7 = mysqli_query($conexion, $query7);
        $data7=mysqli_fetch_assoc($resultado7);
        $conteo7=$data7["COUNT(*)"];
        if($conteo7==0){
            $arreglo["data"][]=array("Id" =>"", "Semana" => "", "subcontratista" => "", "correo_contacto" => "", "NIT" => "", "alcance" => "", "tipo_proveedor" => "", "PAC" => "", "P_Completado" => "", "Calidad" => "", "GSA" => "", "SST" => "", "ADM" => "", "Cal_Integral" => "", "Observaciones" => "", "mdo_cal_1" => "", "mdo_cal_2" => "", "mdo_cal_3" => "", "mdo_adm_1" => "", "mdo_adm_2" => "", "mdo_adm_3" => "", "mdo_adm_4" => "", "mdo_adm_5" => "", "mdo_gsa_1" => "", "mdo_gsa_2" => "", "mdo_gsa_3" => "", "mdo_gsa_4" => "", "mdo_gsa_5" => "", "mdo_gsa_6" => "", "mdo_gsa_7" => "", "mdo_gsa_8" => "", "mdo_sst_1" => "", "mdo_sst_2" => "", "mdo_sst_3" => "", "mdo_sst_4" => "", "mdo_sst_5" => "", "mdo_sst_6" => "", "mdo_sst_7" => "", "mdo_sst_8" => "", "mdo_sst_9" => "", "mdo_sst_10" => "", "si_cal_1" => "", "si_cal_2" => "", "si_cal_3" => "", "si_adm_1" => "", "si_adm_2" => "", "si_adm_3" => "", "si_adm_4" => "", "si_adm_5" => "", "si_adm_6" => "", "si_gsa_1" => "", "si_gsa_2" => "", "si_gsa_3" => "", "si_gsa_4" => "", "si_gsa_5" => "", "si_gsa_6" => "", "si_gsa_7" => "", "si_gsa_8" => "", "si_gsa_9" => "", "si_gsa_10" => "", "si_gsa_11" => "", "si_gsa_12" => "", "si_gsa_13" => "", "si_gsa_14" => "", "si_sst_1" => "", "si_sst_2" => "", "si_sst_3" => "", "si_sst_4" => "", "si_sst_5" => "", "si_sst_6" => "", "si_sst_7" => "", "si_sst_8" => "", "si_sst_9" => "", "si_sst_10" => "");   
            echo json_encode($arreglo);
        }else{
            $query7 = "SELECT * FROM $db"."_cic WHERE (Semana=$semana)";
            $resultado7 = mysqli_query($conexion, $query7);
            if(!$resultado7){
                die("Error");
            } else{
                while($data=mysqli_fetch_assoc($resultado7)){
                $arreglo["data"][]=array_map("utf8_encode", $data);
                }
                $json_codificado = json_encode($arreglo, JSON_UNESCAPED_UNICODE);
                echo utf8_decode($json_codificado);
            }
        mysqli_close($conexion);
        }  
    }
    
    

