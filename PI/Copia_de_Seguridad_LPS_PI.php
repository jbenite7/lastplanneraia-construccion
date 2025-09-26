<?php
	$server = "localhost";
	$user = "aia_fbenitez";/*id11931347_*/
	$password = "ta2AsW(2YU+_";//poner tu propia contraseña, si tienes una.
	$bd = "aia_mascerteza";
	$conexion = mysqli_connect($server, $user, $password, $bd);
	if (!$conexion){ 
		die('Error de Conexión: ' . mysqli_connect_errno());	
	}

$proyecto= ["camino_verde_pi", "paris_campestre_pi", "bosque_campestre_pi", "ciudad_campestre_pi", "milan_campestre_pi"];


foreach($proyecto as $value){
    $query_salida="";
    $fechaActual = date('d-m-Y');
    
    $query_salida .= "--\n-- Copia de Seguridad Proyecto: $value - Fecha: '$fechaActual'"."\n--\n\n";
    
    $fechaActual = date('Ymd');
    
    $query_salida .= "--\n-- Tabla $value"."_cic\n--\n\n";
    //cic 
    
    $query="SELECT * FROM $value"."_cic"; 

    $resultado = mysqli_query($conexion, $query);
    if(!$resultado){
        die(mysqli_error($conexion));
    } else{
        $query1="INSERT INTO $value"."_cic (`Id`, `Semana`, `subcontratista`, `correo_contacto`, `NIT`, `alcance`, `tipo_proveedor`, `PAC`, `PAC_Acum`, `P_Completado`, `P_Completado_Acum`, `Calidad`, `Calidad_Acum`, `GSA`, `GSA_Acum`, `SST`, `SST_Acum`, `ADM`, `ADM_Acum`, `Cal_Integral`,`Cal_Integral_Acum`, `Observaciones`, `mdo_cal_1`, `mdo_cal_2`, `mdo_cal_3`, `mdo_adm_1`, `mdo_adm_2`, `mdo_adm_3`, `mdo_adm_4`, `mdo_adm_5`, `mdo_gsa_1`, `mdo_gsa_2`, `mdo_gsa_3`, `mdo_gsa_4`, `mdo_gsa_5`, `mdo_gsa_6`, `mdo_gsa_7`, `mdo_gsa_8`, `mdo_sst_1`, `mdo_sst_2`,`mdo_sst_3`, `mdo_sst_4`, `mdo_sst_5`, `mdo_sst_6`, `mdo_sst_7`, `mdo_sst_8`, `mdo_sst_9`, `mdo_sst_10`, `si_cal_1`, `si_cal_2`, `si_cal_3`, `si_adm_1`, `si_adm_2`, `si_adm_3`, `si_adm_4`, `si_adm_5`, `si_adm_6`, `si_gsa_1`, `si_gsa_2`, `si_gsa_3`,`si_gsa_4`, `si_gsa_5`, `si_gsa_6`, `si_gsa_7`, `si_gsa_8`, `si_gsa_9`, `si_gsa_10`, `si_gsa_11`, `si_gsa_12`, `si_gsa_13`, `si_gsa_14`, `si_sst_1`, `si_sst_2`, `si_sst_3`, `si_sst_4`, `si_sst_5`, `si_sst_6`, `si_sst_7`, `si_sst_8`, `si_sst_9`, `si_sst_10`) VALUES\n";
        while($data=mysqli_fetch_assoc($resultado)){
            $arreglo=[ $data["Id"], $data["Semana"], $data["subcontratista"], $data["correo_contacto"], $data["NIT"], $data["alcance"], $data["tipo_proveedor"], $data["PAC"], $data["PAC_Acum"], $data["P_Completado"], $data["P_Completado_Acum"], $data["Calidad"], $data["Calidad_Acum"], $data["GSA"], $data["GSA_Acum"], $data["SST"], $data["SST_Acum"], $data["ADM"], $data["ADM_Acum"], $data["Cal_Integral"], $data["Cal_Integral_Acum"], $data["Observaciones"], $data["mdo_cal_1"], $data["mdo_cal_2"], $data["mdo_cal_3"], $data["mdo_adm_1"], $data["mdo_adm_2"], $data["mdo_adm_3"], $data["mdo_adm_4"], $data["mdo_adm_5"], $data["mdo_gsa_1"], $data["mdo_gsa_2"], $data["mdo_gsa_3"], $data["mdo_gsa_4"], $data["mdo_gsa_5"], $data["mdo_gsa_6"], $data["mdo_gsa_7"], $data["mdo_gsa_8"], $data["mdo_sst_1"], $data["mdo_sst_2"], $data["mdo_sst_3"], $data["mdo_sst_4"], $data["mdo_sst_5"], $data["mdo_sst_6"], $data["mdo_sst_7"], $data["mdo_sst_8"], $data["mdo_sst_9"], $data["mdo_sst_10"]];
            
            $arreglo1=[$data["si_cal_1"], $data["si_cal_2"], $data["si_cal_3"],$data["si_adm_1"], $data["si_adm_2"], $data["si_adm_3"], $data["si_adm_4"], $data["si_adm_5"], $data["si_adm_6"], $data["si_gsa_1"], $data["si_gsa_2"], $data["si_gsa_3"], $data["si_gsa_4"], $data["si_gsa_5"], $data["si_gsa_6"], $data["si_gsa_7"], $data["si_gsa_8"], $data["si_gsa_9"], $data["si_gsa_10"], $data["si_gsa_11"], $data["si_gsa_12"], $data["si_gsa_13"], $data["si_gsa_14"], $data["si_sst_1"], $data["si_sst_2"], $data["si_sst_3"], $data["si_sst_4"], $data["si_sst_5"], $data["si_sst_6"], $data["si_sst_7"], $data["si_sst_8"], $data["si_sst_9"], $data["si_sst_10"]];
            
            $query1 .= "($arreglo[0], $arreglo[1], '$arreglo[2]', '$arreglo[3]', $arreglo[4], '$arreglo[5]', '$arreglo[6]', '$arreglo[7]', '$arreglo[8]', '$arreglo[9]', '$arreglo[10]', '$arreglo[11]', '$arreglo[12]', '$arreglo[13]', '$arreglo[14]', '$arreglo[15]', '$arreglo[16]', '$arreglo[17]', '$arreglo[18]', $arreglo[19], $arreglo[20], '$arreglo[21]', '$arreglo[22]', '$arreglo[23]', '$arreglo[24]', '$arreglo[25]', '$arreglo[26]', '$arreglo[27]', '$arreglo[28]', '$arreglo[29]', '$arreglo[30]', '$arreglo[31]', '$arreglo[32]', '$arreglo[33]', '$arreglo[34]', '$arreglo[35]', '$arreglo[36]', '$arreglo[37]', '$arreglo[38]', '$arreglo[39]', '$arreglo[40]', '$arreglo[41]', '$arreglo[42]', '$arreglo[43]', '$arreglo[44]', '$arreglo[45]', '$arreglo[46]', '$arreglo[47]', '$arreglo1[0]', '$arreglo1[1]', '$arreglo1[2]', '$arreglo1[3]', '$arreglo1[4]', '$arreglo1[5]', '$arreglo1[6]', '$arreglo1[7]', '$arreglo1[8]', '$arreglo1[9]', '$arreglo1[10]', '$arreglo1[11]', '$arreglo1[12]', '$arreglo1[13]', '$arreglo1[14]', '$arreglo1[15]', '$arreglo1[16]', '$arreglo1[17]', '$arreglo1[18]', '$arreglo1[19]', '$arreglo1[20]', '$arreglo1[21]', '$arreglo1[22]', '$arreglo1[23]', '$arreglo1[24]', '$arreglo1[25]', '$arreglo1[26]', '$arreglo1[27]', '$arreglo1[28]', '$arreglo1[29]', '$arreglo1[30]', '$arreglo1[31]', '$arreglo1[32]'),\n";
        }
        $query_salida .= substr($query1, 0, -2) . ";\n\n" ;
        
        mysqli_free_result($resultado);
    } 
    
    $query_salida .= "--\n-- Tabla $value"."_cip\n--\n\n";
    //cip
    
    $query="SELECT * FROM $value"."_cip"; 

    $resultado = mysqli_query($conexion, $query);
    if(!$resultado){
        die(mysqli_error($conexion));
    } else{
        $query1="INSERT INTO $value"."_cip (`Id`, `Semana`, `profesional`, `correo_contacto`, `PAC`, `PAC_Acum`, `P_Completado`, `P_Completado_Acum`, `Act_Criticas_Cumplidas`, `Act_Criticas_Cumplidas_Acum`, `Act_No_Criticas_Cumplidas`, `Act_No_Criticas_Cumplidas_Acum`, `Act_Atrasadas_Cumplidas`, `Act_Atrasadas_Cumplidas_Acum`, `PAC_Consolidado`, `PAC_Consolidado_Acum`) VALUES\n";
        while($data=mysqli_fetch_assoc($resultado)){
            $arreglo=[ $data["Id"], $data["Semana"], $data["profesional"], $data["correo_contacto"], $data["PAC"], $data["PAC_Acum"], $data["P_Completado"], $data["P_Completado_Acum"], $data["Act_Criticas_Cumplidas"], $data["Act_Criticas_Cumplidas_Acum"], $data["Act_No_Criticas_Cumplidas"], $data["Act_No_Criticas_Cumplidas_Acum"], $data["Act_Atrasadas_Cumplidas"], $data["Act_Atrasadas_Cumplidas_Acum"], $data["PAC_Consolidado"], $data["PAC_Consolidado_Acum"]];            
            
            $query1 .= "($arreglo[0], $arreglo[1], '$arreglo[2]', '$arreglo[3]', '$arreglo[4]', '$arreglo[5]', '$arreglo[6]', '$arreglo[7]', '$arreglo[8]', '$arreglo[9]', '$arreglo[10]', '$arreglo[11]', '$arreglo[12]', '$arreglo[13]', '$arreglo[14]', '$arreglo[15]'),\n";
        }
        $query_salida .= substr($query1, 0, -2) . "; \n\n" ;
        
        mysqli_free_result($resultado);
    }
    
    $query_salida .= "--\n-- Tabla $value"."_indicadores_generales\n--\n\n";
    //indicadores_generales
    
    $query="SELECT * FROM $value"."_indicadores_generales"; 

    $resultado = mysqli_query($conexion, $query);
    if(!$resultado){
        die(mysqli_error($conexion));
    } else{
        $query1="INSERT INTO $value"."_indicadores_generales (`Id`, `Semana`, `subcontratista_profesional`, `rol`, `PAC`, `PAC_Acum`, `P_Completado`, `P_Completado_Acum`, `CNC_Rendimiento`, `CNC_Rendimiento_Acum`, `CNC_Programacion`, `CNC_Programacion_Acum`, `CNC_MdeO`, `CNC_MdeO_Acum`, `CNC_Materiales`, `CNC_Materiales_Acum`, `CNC_Equipos`,`CNC_Equipos_Acum`, `CNC_Disenos`, `CNC_Disenos_Acum`, `CNC_Administrativas`, `CNC_Administrativas_Acum`, `Criticas_Comp`, `Criticas_Comp_Acum`, `No_Criticas_Comp`, `No_Criticas_Comp_Acum`, `Atrasadas_Comp`, `Atrasadas_Comp_Acum`, `Comp_Sin_Rest_100`, `Comp_Sin_Rest_100_Acum`, `Act_Inician_Sem_1`, `Act_0_Lib_Sem_1`, `Act_Par_Lib_Sem_1`, `Act_100_Lib_Sem_1`, `Act_Inician_Sem_2`, `Act_0_Lib_Sem_2`, `Act_Par_Lib_Sem_2`, `Act_100_Lib_Sem_2`, `Act_Inician_Sem_3`, `Act_0_Lib_Sem_3`, `Act_Par_Lib_Sem_3`, `Act_100_Lib_Sem_3`, `Act_Inician_Sem_4`, `Act_0_Lib_Sem_4`, `Act_Par_Lib_Sem_4`, `Act_100_Lib_Sem_4`, `Act_Inician_Sem_5`, `Act_0_Lib_Sem_5`, `Act_Par_Lib_Sem_5`, `Act_100_Lib_Sem_5`, `Act_Inician_Sem_6`, `Act_0_Lib_Sem_6`, `Act_Par_Lib_Sem_6`, `Act_100_Lib_Sem_6`) VALUES\n";
        while($data=mysqli_fetch_assoc($resultado)){
            $arreglo=[ $data["Id"], $data["Semana"], $data["subcontratista_profesional"], $data["rol"], $data["PAC"], $data["PAC_Acum"], $data["P_Completado"], $data["P_Completado_Acum"], $data["CNC_Rendimiento"], $data["CNC_Rendimiento_Acum"], $data["CNC_Programacion"], $data["CNC_Programacion_Acum"], $data["CNC_MdeO"], $data["CNC_MdeO_Acum"], $data["CNC_Materiales"], $data["CNC_Materiales_Acum"], $data["CNC_Equipos"], $data["CNC_Equipos_Acum"], $data["CNC_Disenos"], $data["CNC_Disenos_Acum"], $data["CNC_Administrativas"], $data["CNC_Administrativas_Acum"], $data["Criticas_Comp"], $data["Criticas_Comp_Acum"], $data["No_Criticas_Comp"], $data["No_Criticas_Comp_Acum"], $data["Atrasadas_Comp"], $data["Atrasadas_Comp_Acum"], $data["Comp_Sin_Rest_100"], $data["Comp_Sin_Rest_100_Acum"]];
            
            $arreglo1=[ $data["Act_Inician_Sem_1"], $data["Act_0_Lib_Sem_1"], $data["Act_Par_Lib_Sem_1"], $data["Act_100_Lib_Sem_1"], $data["Act_Inician_Sem_2"], $data["Act_0_Lib_Sem_2"], $data["Act_Par_Lib_Sem_2"], $data["Act_100_Lib_Sem_2"], $data["Act_Inician_Sem_3"], $data["Act_0_Lib_Sem_3"], $data["Act_Par_Lib_Sem_3"], $data["Act_100_Lib_Sem_3"], $data["Act_Inician_Sem_4"], $data["Act_0_Lib_Sem_4"], $data["Act_Par_Lib_Sem_4"], $data["Act_100_Lib_Sem_4"], $data["Act_Inician_Sem_5"], $data["Act_0_Lib_Sem_5"], $data["Act_Par_Lib_Sem_5"], $data["Act_100_Lib_Sem_5"], $data["Act_Inician_Sem_6"], $data["Act_0_Lib_Sem_6"], $data["Act_Par_Lib_Sem_6"], $data["Act_100_Lib_Sem_6"]];
            
            $query1 .= "($arreglo[0], $arreglo[1], '$arreglo[2]', '$arreglo[3]', '$arreglo[4]', '$arreglo[5]', '$arreglo[6]', '$arreglo[7]', '$arreglo[8]', '$arreglo[9]', '$arreglo[10]', '$arreglo[11]', '$arreglo[12]', '$arreglo[13]', '$arreglo[14]', '$arreglo[15]', '$arreglo[16]', '$arreglo[17]', '$arreglo[18]', $arreglo[19], $arreglo[20], '$arreglo[21]', '$arreglo[22]', '$arreglo[23]', '$arreglo[24]', '$arreglo[25]', '$arreglo[26]', '$arreglo[27]', '$arreglo[28]', '$arreglo[29]', '$arreglo1[0]', '$arreglo1[1]', '$arreglo1[2]', '$arreglo1[3]', '$arreglo1[4]', '$arreglo1[5]', '$arreglo1[6]', '$arreglo1[7]', '$arreglo1[8]', '$arreglo1[9]', '$arreglo1[10]', '$arreglo1[11]', '$arreglo1[12]', '$arreglo1[13]', '$arreglo1[14]', '$arreglo1[15]', '$arreglo1[16]', '$arreglo1[17]', '$arreglo1[18]', '$arreglo1[19]', '$arreglo1[20]', '$arreglo1[21]', '$arreglo1[22]', '$arreglo1[23]'),\n";
        }
        $query_salida .= substr($query1, 0, -2) . "; \n\n" ;
        
        mysqli_free_result($resultado);
    } 
    
    $query_salida .= "--\n-- Tabla $value"."_profesionales\n--\n\n";
    //profesionales
    
    $query="SELECT * FROM $value"."_profesionales"; 

    $resultado = mysqli_query($conexion, $query);
    if(!$resultado){
        die(mysqli_error($conexion));
    } else{
        $query1="INSERT INTO $value"."_profesionales (`id`, `nombre`, `email`, `cargo`) VALUES\n";
        while($data=mysqli_fetch_assoc($resultado)){
            $arreglo=[ $data["id"], $data["nombre"], $data["email"], $data["cargo"]];
            
            $query1 .= "($arreglo[0], '$arreglo[1]', '$arreglo[2]', '$arreglo[3]'),\n";
        }
        $query_salida .= substr($query1, 0, -2) . "; \n\n" ;
        
        mysqli_free_result($resultado);
    }
    
    $query_salida .= "--\n-- Tabla $value"."_programa\n--\n\n";
    //programa
    
    $query="SELECT * FROM $value"."_programa"; 

    $resultado = mysqli_query($conexion, $query);
    if(!$resultado){
        die(mysqli_error($conexion));
    } else{
        $query1="INSERT INTO $value"."_programa (`Consecutivo`, `Id`, `Actividad`, `Titulo`, `Fecha_Inicio`, `Fecha_Fin`, `Ruta_Critica`, `Ejecutado`, `Estado`, `Dias_Inicio`, `Categoria`, `Lookahead`, `Periodicidad`, `Checklist`, `R1`, `R2`, `R3`, `R4`, `R5`, `R6`, `R7`, `R8`, `R9`, `R10`, `R11`, `R12`, `R13`, `R14`, `R15`, `R16`, `R17`, `R18`, `R19`, `R20`, `R21`, `R22`, `R23`, `R24`, `R25`) VALUES\n";
        while($data=mysqli_fetch_assoc($resultado)){
            $arreglo=[$data["Consecutivo"],$data["Id"],$data["Actividad"],$data["Titulo"],$data["Fecha_Inicio"],$data["Fecha_Fin"],$data["Ruta_Critica"],$data["Ejecutado"],$data["Estado"],$data["Dias_Inicio"],$data["Categoria"],$data["Lookahead"],$data["Periodicidad"],$data["Checklist"],$data["R1"],$data["R2"],$data["R3"],$data["R4"],$data["R5"],$data["R6"],$data["R7"],$data["R8"],$data["R9"],$data["R10"],$data["R11"],$data["R12"],$data["R13"],$data["R14"],$data["R15"],$data["R16"],$data["R17"],$data["R18"],$data["R19"],$data["R20"],$data["R21"],$data["R22"],$data["R23"],$data["R24"],$data["R25"]];
            
            $arreglo[2]=str_replace("'", "\'", $arreglo[2]);
            
            if($arreglo[4]==''|| $arreglo[4]=='0000-00-00'){
                $arreglo[4]='NULL';
                $arreglo4 ="$arreglo[4]";
            }else{
                $arreglo4 ="'$arreglo[4]'";
            }
            if($arreglo[5]=='' || $arreglo[5]=='0000-00-00'){
                $arreglo[5]='NULL';
                $arreglo5 ="$arreglo[5]";
            }else{
                $arreglo5 ="'$arreglo[5]'";
            }
            if($arreglo[6]==''){
                $arreglo[6]='NULL';
                $arreglo6 ="$arreglo[6]";
            }else{
                $arreglo6 =$arreglo[6];
            }
            if($arreglo[7]==''){
                $arreglo[7]='NULL';
                $arreglo7 ="$arreglo[7]";
            }else{
                $arreglo7 =$arreglo[7];
            }
            if($arreglo[8]==''){
                $arreglo[8]='NULL';
                $arreglo8 ="$arreglo[8]";
            }else{
                $arreglo8 ="'$arreglo[8]'";
            }
            if($arreglo[9]==''){
                $arreglo[9]='NULL';
                $arreglo9 ="$arreglo[9]";
            }else{
                $arreglo9 =$arreglo[9];
            }
            if($arreglo[10]==''){
                $arreglo[10]='NULL';
                $arreglo10 ="$arreglo[10]";
            }else{
                $arreglo10 ="'$arreglo[10]'";
            }
            if($arreglo[11]==''){
                $arreglo[11]='NULL';
                $arreglo11 ="$arreglo[11]";
            }else{
                $arreglo11 ="'$arreglo[11]'";
            }
            if($arreglo[12]==''){
                $arreglo[12]='NA';
                $arreglo12 ="'$arreglo[12]'";
            }else{
                $arreglo12 ="'$arreglo[12]'";
            }
            if($arreglo[13]==''){
                $arreglo[13]='NULL';
                $arreglo13 ="$arreglo[13]";
            }else{
                $arreglo13 ="'$arreglo[13]'";
            }
            
            $query0="";
            for($i=14;$i<39;$i++){
                if($arreglo[$i]==''){
                    $arreglo[$i]='NA';
                    $query0 .=" '$arreglo[$i]',";
                }else{
                    $query0 .=" '$arreglo[$i]',";
                }  
            }
            
            $query0 =substr($query0, 0, -1);
            
            
            $query1 .= "($arreglo[0], '$arreglo[1]', '$arreglo[2]', '$arreglo[3]', $arreglo4, $arreglo5, $arreglo6, $arreglo7, $arreglo8, $arreglo9, $arreglo10, $arreglo11, $arreglo12, $arreglo13, $query0),\n";
        }
        $query_salida .= substr($query1, 0, -2) . "; \n\n" ;
        
        mysqli_free_result($resultado);
    }
    
    $query_salida .= "--\n-- Tabla $value"."_programacion_semanal\n--\n\n";
    //programacion_semanal
    
    $query="SELECT * FROM $value"."_programacion_semanal"; 

    $resultado = mysqli_query($conexion, $query);
    $contador=0;
    if(!$resultado){
        die(mysqli_error($conexion));
    } else{
        $query1="INSERT INTO $value"."_programacion_semanal (`Consecutivo`, `Semana`, `Consecutivo_En_Programa`, `Id`, `Actividad`, `Descripcion`, `Clase`, `Sub_Contratista`, `Responsable_AIA`, `Unidad`, `Compromiso`, `Ejecutado_Real`, `P_Completado`, `PAC`, `Critica`, `Atrasada`, `Activa`, `Prog_Sin_Restricciones_100`, `Categoria_CNP`, `CNP`, `Observaciones_CNP`, `Categoria_CNC`, `CNC`, `Observaciones_CNC`) VALUES\n";
        while($data=mysqli_fetch_assoc($resultado)){
            if($data["Compromiso"]==''){
                $data["Compromiso"]='NULL';
            }
            if($data["Ejecutado_Real"]==''){
                $data["Ejecutado_Real"]='NULL';
            }
            if($data["P_Completado"]==''){
                $data["P_Completado"]='NULL';
            }
            if($data["PAC"]==''){
                $data["PAC"]='NULL';
            }
            
            $arreglo=[$data["Consecutivo"],$data["Semana"],$data["Consecutivo_En_Programa"],$data["Id"],$data["Actividad"],$data["Descripcion"],$data["Clase"],$data["Sub_Contratista"],$data["Responsable_AIA"],$data["Unidad"],$data["Compromiso"],$data["Ejecutado_Real"],$data["P_Completado"],$data["PAC"],$data["Critica"],$data["Atrasada"],$data["Activa"],$data["Prog_Sin_Restricciones_100"],$data["Categoria_CNP"],$data["CNP"],$data["Observaciones_CNP"],$data["Categoria_CNC"],$data["CNC"],$data["Observaciones_CNC"]];
            ;
            
            $arreglo[4]=str_replace("'", "\'", $arreglo[4]);
            
            if($arreglo[5]==''){
                $arreglo[5]='NULL';
                $arreglo5 ="$arreglo[5]";
            }else{
                $arreglo5 ="'$arreglo[5]'";
            }
            if($arreglo[6]==''){
                $arreglo[6]='NULL';
                $arreglo6 ="$arreglo[6]";
            }else{
                $arreglo6 ="'$arreglo[6]'";
            }          
            
            if($arreglo[7]==''){
                $arreglo[7]='NULL';
                $arreglo7 ="$arreglo[7]";
            }else{
                $arreglo7 ="'$arreglo[7]'";
            }
            if($arreglo[8]==''){
                $arreglo[8]='NULL';
                $arreglo8 ="$arreglo[8]";
            }else{
                $arreglo8 ="'$arreglo[8]'";
            }
            if($arreglo[9]==''){
                $arreglo[9]='NULL';
                $arreglo9 ="$arreglo[9]";
            }else{
                $arreglo9 ="'$arreglo[9]'";
            }
            
            $query0="";
            for($i=18;$i<24;$i++){
                if($arreglo[$i]==''){
                    $arreglo[$i]='NULL';
                    $query0 .=" $arreglo[$i],";
                }else{
                    $query0 .=" '$arreglo[$i]',";
                }    
            }
            
            $query0 =substr($query0, 0, -1);

            if($contador==1000){
                $query2=";\nINSERT INTO $value"."_programacion_semanal (`Consecutivo`, `Semana`, `Consecutivo_En_Programa`, `Id`, `Actividad`, `Descripcion`, `Clase`, `Sub_Contratista`, `Responsable_AIA`, `Unidad`, `Compromiso`, `Ejecutado_Real`, `P_Completado`, `PAC`, `Critica`, `Atrasada`, `Activa`, `Prog_Sin_Restricciones_100`, `Categoria_CNP`, `CNP`, `Observaciones_CNP`, `Categoria_CNC`, `CNC`, `Observaciones_CNC`) VALUES\n";
                $query1 =substr($query1, 0, -2);

                $query1 .= $query2 . "($arreglo[0], $arreglo[1], $arreglo[2], '$arreglo[3]', '$arreglo[4]', $arreglo5, $arreglo6, $arreglo7, $arreglo8, $arreglo9, $arreglo[10], $arreglo[11], $arreglo[12], $arreglo[13], $arreglo[14], $arreglo[15], '$arreglo[16]', $arreglo[17], $query0),\n";
                
                $contador=0;   
            }else{
                $query1 .="($arreglo[0], $arreglo[1], $arreglo[2], '$arreglo[3]', '$arreglo[4]', $arreglo5, $arreglo6, $arreglo7, $arreglo8, $arreglo9, $arreglo[10], $arreglo[11], $arreglo[12], $arreglo[13], $arreglo[14], $arreglo[15], '$arreglo[16]', $arreglo[17], $query0),\n";   
                
                $contador=$contador+1;
            }
        }
        $query_salida .= substr($query1, 0, -2) . "; \n\n" ;
        
        mysqli_free_result($resultado);
    } 
    
    $query_salida .= "--\n-- Tabla $value"."_programa_consolidado\n--\n\n";
    //programa_consolidado
    
    $query="SELECT * FROM $value"."_programa_consolidado"; 

    $resultado = mysqli_query($conexion, $query);
    $contador=0;
    if(!$resultado){
        die(mysqli_error($conexion));
    } else{
        $query1="INSERT INTO $value"."_programa_consolidado (`Consecutivo`, `Semana`, `Consecutivo_en_Programa`, `Id`, `Actividad`, `Titulo`, `Fecha_Inicio`, `Fecha_Fin`, `Ruta_Critica`, `Ejecutado`, `Estado`, `Dias_Inicio`, `Categoria`, `Lookahead`, `Periodicidad`, `Checklist`, `Relevancia`, `Ejecutado_Siguiente_Semana`, `Estado_Restricciones`, `R1`, `R2`, `R3`, `R4`, `R5`, `R6`, `R7`, `R8`, `R9`, `R10`, `R11`, `R12`, `R13`, `R14`, `R15`, `R16`, `R17`, `R18`, `R19`, `R20`, `R21`, `R22`, `R23`, `R24`, `R25`, `Observaciones`, `comprometer_semanal`) VALUES\n";
        while($data=mysqli_fetch_assoc($resultado)){
            $arreglo=[$data["Consecutivo"],$data["Semana"],$data["Consecutivo_en_Programa"],$data["Id"],$data["Actividad"],$data["Titulo"],$data["Fecha_Inicio"],$data["Fecha_Fin"],$data["Ruta_Critica"],$data["Ejecutado"],$data["Estado"],$data["Dias_Inicio"],$data["Categoria"],$data["Lookahead"],$data["Periodicidad"],$data["Checklist"],$data["Relevancia"],$data["Ejecutado_Siguiente_Semana"],$data["Estado_Restricciones"],$data["R1"],$data["R2"],$data["R3"],$data["R4"],$data["R5"],$data["R6"],$data["R7"],$data["R8"],$data["R9"],$data["R10"],$data["R11"],$data["R12"],$data["R13"],$data["R14"],$data["R15"],$data["R16"],$data["R17"],$data["R18"],$data["R19"],$data["R20"],$data["R21"],$data["R22"],$data["R23"],$data["R24"],$data["R25"],$data["Observaciones"],$data["comprometer_semanal"]];
            
            $arreglo[4]=str_replace("'", "\'", $arreglo[4]);
            
            if($arreglo[3]==''){
                $arreglo[3]='NULL';
                $arreglo3 ="$arreglo[3]";
            }else{
                $arreglo3 ="'$arreglo[3]'";
            }
            if($arreglo[6]=='' || $arreglo[6]=='0000-00-00'){
                $arreglo[6]='NULL';
                $arreglo6 ="$arreglo[6]";
            }else{
                $arreglo6 ="'$arreglo[6]'";
            }
            if($arreglo[7]=='' || $arreglo[7]=='0000-00-00'){
                $arreglo[7]='NULL';
                $arreglo7 ="$arreglo[7]";
            }else{
                $arreglo7 ="'$arreglo[7]'";
            }
            if($arreglo[8]==''){
                $arreglo[8]='NULL';
                $arreglo8 ="$arreglo[8]";
            }else{
                $arreglo8 =$arreglo[8];
            }
            if($arreglo[9]==''){
                $arreglo[9]='NULL';
                $arreglo9 ="$arreglo[9]";
            }else{
                $arreglo9 =$arreglo[9];
            }
            if($arreglo[11]==''){
                $arreglo[11]='NULL';
                $arreglo11 ="$arreglo[11]";
            }else{
                $arreglo11 =$arreglo[11];
            }
            if($arreglo[12]==''){
                $arreglo[12]='NULL';
                $arreglo12 ="$arreglo[12]";
            }else{
                $arreglo12 ="'$arreglo[12]'";
            }
            if($arreglo[13]==''){
                $arreglo[13]='NULL';
                $arreglo13 ="$arreglo[13]";
            }else{
                $arreglo13 ="'$arreglo[13]'";
            }
            if($arreglo[14]==''){
                $arreglo[14]='NULL';
                $arreglo14 ="$arreglo[14]";
            }else{
                $arreglo14 ="'$arreglo[14]'";
            }
            if($arreglo[15]==''){
                $arreglo[15]='NULL';
                $arreglo15 ="$arreglo[15]";
            }else{
                $arreglo15 ="'$arreglo[15]'";
            }
            if($arreglo[16]==''){
                $arreglo[16]='NA';
                $arreglo16 ="'$arreglo[16]'";
            }else{
                $arreglo16 ="'$arreglo[16]'";
            }
            if($arreglo[17]==''){
                $arreglo[17]='NULL';
                $arreglo17 ="$arreglo[17]";
            }else{
                $arreglo17 ="'$arreglo[17]'";
            }
            
            $query0="";
            for($i=18;$i<44;$i++){
                if($arreglo[$i]==''){
                    $arreglo[$i]='NA';
                    $query0 .=" '$arreglo[$i]',";
                }else{
                    $query0 .=" '$arreglo[$i]',";
                }    
            }
            
            if($arreglo[44]==''){
                $arreglo[44]='NULL';
                $arreglo44 ="$arreglo[44]";
            }else{
                $arreglo44 ="'$arreglo[44]'";
            }
            
            
            if($contador==1000){
                $query2=";\n INSERT INTO $value"."_programa_consolidado (`Consecutivo`, `Semana`, `Consecutivo_en_Programa`, `Id`, `Actividad`, `Titulo`, `Fecha_Inicio`, `Fecha_Fin`, `Ruta_Critica`, `Ejecutado`, `Estado`, `Dias_Inicio`, `Categoria`, `Lookahead`, `Periodicidad`, `Checklist`, `Relevancia`, `Ejecutado_Siguiente_Semana`, `Estado_Restricciones`, `R1`, `R2`, `R3`, `R4`, `R5`, `R6`, `R7`, `R8`, `R9`, `R10`, `R11`, `R12`, `R13`, `R14`, `R15`, `R16`, `R17`, `R18`, `R19`, `R20`, `R21`, `R22`, `R23`, `R24`, `R25`, `Observaciones`, `comprometer_semanal`) VALUES\n";
                
                
                $query1 =substr($query1, 0, -2);
                $query1 .= $query2 . "($arreglo[0], $arreglo[1], $arreglo[2], $arreglo3, '$arreglo[4]', $arreglo[5], $arreglo6, $arreglo7, $arreglo8, $arreglo9, '$arreglo[10]', $arreglo11, $arreglo12, $arreglo13, $arreglo14, $arreglo15, $arreglo16, $arreglo17, $query0 $arreglo44, $arreglo[45]),\n";
                
                $contador=0;
                
            }else{
                $query1 .= "($arreglo[0], $arreglo[1], $arreglo[2], $arreglo3, '$arreglo[4]', $arreglo[5], $arreglo6, $arreglo7, $arreglo8, $arreglo9, '$arreglo[10]', $arreglo11, $arreglo12, $arreglo13, $arreglo14, $arreglo15, $arreglo16, $arreglo17, $query0 $arreglo44, $arreglo[45]),\n";
                
                $contador=$contador+1;
                
            }
            
        }
        $query_salida .= substr($query1, 0, -2) . "; \n\n" ;
        
        mysqli_free_result($resultado);
    }
    
    $query_salida .= "--\n-- Tabla $value"."_semanas_activas\n--\n\n";
    //semanas_activas
    
    $query="SELECT * FROM $value"."_semanas_activas"; 

    $resultado = mysqli_query($conexion, $query);
    if(!$resultado){
        die(mysqli_error($conexion));
    } else{
        $query1="INSERT INTO $value"."_semanas_activas (`Id`, `Semana`, `Fecha_Inicio_Sem`, `Fecha_Fin_Sem`) VALUES\n";
        while($data=mysqli_fetch_assoc($resultado)){
            $arreglo=[ $data["Id"], $data["Semana"], $data["Fecha_Inicio_Sem"], $data["Fecha_Fin_Sem"]];
            
            $query1 .= "($arreglo[0], '$arreglo[1]', '$arreglo[2]', '$arreglo[3]'),\n";
        }
        $query_salida .= substr($query1, 0, -2) . "; \n\n" ;
        
        mysqli_free_result($resultado);
    }
    
    $query_salida .= "--\n-- Tabla $value"."_subcontratistas\n--\n\n";
    //subcontratistas
    
    $query="SELECT * FROM $value"."_subcontratistas"; 

    $resultado = mysqli_query($conexion, $query);
    if(!$resultado){
        die(mysqli_error($conexion));
    } else{
        $query1="INSERT INTO $value"."_subcontratistas (`Id`, `subcontratista`, `correo_contacto`, `NIT`, `alcance`, `tipo_proveedor`) VALUES\n";
        while($data=mysqli_fetch_assoc($resultado)){
            $arreglo=[ $data["Id"], $data["subcontratista"], $data["correo_contacto"], $data["NIT"], $data["alcance"], $data["tipo_proveedor"]];
            
            if($arreglo[3]==''){
                $arreglo[3]='NULL';
                $arreglo3 ="$arreglo[3]";
            }else{
                $arreglo3 ="'$arreglo[3]'";
            }
            
            $query1 .= "($arreglo[0], '$arreglo[1]', '$arreglo[2]', '$arreglo[3]', '$arreglo[4]', '$arreglo[5]'),\n";
        }
        $query_salida .= substr($query1, 0, -2) . "; \n\n" ;
        
        mysqli_free_result($resultado);
    }
    
    $query_salida .= "--\n-- Tabla $value"."_checklists\n--\n\n";
    //checklists
    
    $query="SELECT * FROM $value"."_checklists"; 

    $resultado = mysqli_query($conexion, $query);
    if(!$resultado){
        die(mysqli_error($conexion));
    } else{
        $query1="INSERT INTO $value"."_checklists (`Id`, `Tarea`, `Codigo_Tarea`, `Consecutivo_Requerimiento`, `Requerimiento`, `clase`, `url`, `Semana_url`) VALUES\n";
        while($data=mysqli_fetch_assoc($resultado)){
            $arreglo=[ $data["Id"], $data["Tarea"], $data["Codigo_Tarea"], $data["Consecutivo_Requerimiento"], $data["Requerimiento"], $data["clase"], $data["url"], $data["Semana_url"]];
            
            if($arreglo[5]==''){
                $arreglo[5]='NULL';
                $arreglo5 ="$arreglo[5]";
            }else{
                $arreglo5 ="'$arreglo[5]'";
            }
            if($arreglo[6]==''){
                $arreglo[6]='NULL';
                $arreglo6 ="$arreglo[6]";
            }else{
                $arreglo6 ="'$arreglo[6]'";
            }
            if($arreglo[7]==''){
                $arreglo[7]='NULL';
                $arreglo7 ="$arreglo[7]";
            }else{
                $arreglo7 =$arreglo[7];
            }
            
            $query1 .= "($arreglo[0], '$arreglo[1]', $arreglo[2], $arreglo[3], '$arreglo[4]', $arreglo5, $arreglo6, $arreglo7),\n";
        }
        $query_salida .= substr($query1, 0, -2) . "; \n\n" ;
        
        mysqli_free_result($resultado);
    }
    
    $ar=fopen("copias_de_seguridad/$fechaActual"."_$value.sql", "a") or die("Error al crear");

    fwrite($ar, $query_salida);

    echo "<li>- Se creó correctamente el archivo '$fechaActual"."_$value.sql' \n\n";  
}
mysqli_close($conexion);
//echo $query_salida;


?>