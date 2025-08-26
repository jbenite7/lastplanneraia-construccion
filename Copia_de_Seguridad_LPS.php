<?php
	$server = "localhost";
	$user = "aia_fbenitez";/*id11931347_*/
	$password = "ta2AsW(2YU+_";//poner tu propia contraseña, si tienes una.
	$bd = "aia_mascerteza";
	$conexion = mysqli_connect($server, $user, $password, $bd);
	if (!$conexion){ 
		die('Error de Conexión: ' . mysqli_connect_errno());	
	}

$proyecto= ["clinica_del_sur", "paris_campestre", "mallplaza_cali", "bodega_latam", "concejo_bogota", "cedi_pasto", "parqueadero_alkosto", "camino_verde", "prueba", "cross", "bts_toberin", "reserva_de_modelia"];


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
        $query1="INSERT INTO $value"."_indicadores_generales (`Id`, `Semana`, `subcontratista_profesional`, `rol`, `PAC`, `PAC_Acum`, `P_Completado`, `P_Completado_Acum`, `CNC_Rendimiento`, `CNC_Rendimiento_Acum`, `CNC_Programacion`, `CNC_Programacion_Acum`, `CNC_MdeO`, `CNC_MdeO_Acum`, `CNC_Materiales`, `CNC_Materiales_Acum`, `CNC_Equipos`,`CNC_Equipos_Acum`, `CNC_Disenos`, `CNC_Disenos_Acum`, `CNC_Administrativas`, `CNC_Administrativas_Acum`, `Criticas_Comp`, `Criticas_Comp_Acum`, `No_Criticas_Comp`, `No_Criticas_Comp_Acum`, `Atrasadas_Criticas_Comp`, `Atrasadas_Criticas_Comp_Acum`, `Atrasadas_No_Criticas_Comp`, `Atrasadas_No_Criticas_Comp_Acum`, `Comp_Sin_Rest_100`, `Comp_Sin_Rest_100_Acum`, `Act_Inician_Sem_1`, `Act_0_Lib_Sem_1`, `Act_Par_Lib_Sem_1`, `Act_100_Lib_Sem_1`, `Act_Inician_Sem_2`, `Act_0_Lib_Sem_2`, `Act_Par_Lib_Sem_2`, `Act_100_Lib_Sem_2`, `Act_Inician_Sem_3`, `Act_0_Lib_Sem_3`, `Act_Par_Lib_Sem_3`, `Act_100_Lib_Sem_3`, `Act_Inician_Sem_4`, `Act_0_Lib_Sem_4`, `Act_Par_Lib_Sem_4`, `Act_100_Lib_Sem_4`, `Act_Inician_Sem_5`, `Act_0_Lib_Sem_5`, `Act_Par_Lib_Sem_5`, `Act_100_Lib_Sem_5`, `Act_Inician_Sem_6`, `Act_0_Lib_Sem_6`, `Act_Par_Lib_Sem_6`, `Act_100_Lib_Sem_6`) VALUES\n";
        while($data=mysqli_fetch_assoc($resultado)){
            $arreglo=[ $data["Id"], $data["Semana"], $data["subcontratista_profesional"], $data["rol"], $data["PAC"], $data["PAC_Acum"], $data["P_Completado"], $data["P_Completado_Acum"], $data["CNC_Rendimiento"], $data["CNC_Rendimiento_Acum"], $data["CNC_Programacion"], $data["CNC_Programacion_Acum"], $data["CNC_MdeO"], $data["CNC_MdeO_Acum"], $data["CNC_Materiales"], $data["CNC_Materiales_Acum"], $data["CNC_Equipos"], $data["CNC_Equipos_Acum"], $data["CNC_Disenos"], $data["CNC_Disenos_Acum"], $data["CNC_Administrativas"], $data["CNC_Administrativas_Acum"], $data["Criticas_Comp"], $data["Criticas_Comp_Acum"], $data["No_Criticas_Comp"], $data["No_Criticas_Comp_Acum"], $data["Atrasadas_Criticas_Comp"], $data["Atrasadas_Criticas_Comp_Acum"], $data["Atrasadas_No_Criticas_Comp"], $data["Atrasadas_No_Criticas_Comp_Acum"], $data["Comp_Sin_Rest_100"], $data["Comp_Sin_Rest_100_Acum"]];
            
            $arreglo1=[ $data["Act_Inician_Sem_1"], $data["Act_0_Lib_Sem_1"], $data["Act_Par_Lib_Sem_1"], $data["Act_100_Lib_Sem_1"], $data["Act_Inician_Sem_2"], $data["Act_0_Lib_Sem_2"], $data["Act_Par_Lib_Sem_2"], $data["Act_100_Lib_Sem_2"], $data["Act_Inician_Sem_3"], $data["Act_0_Lib_Sem_3"], $data["Act_Par_Lib_Sem_3"], $data["Act_100_Lib_Sem_3"], $data["Act_Inician_Sem_4"], $data["Act_0_Lib_Sem_4"], $data["Act_Par_Lib_Sem_4"], $data["Act_100_Lib_Sem_4"], $data["Act_Inician_Sem_5"], $data["Act_0_Lib_Sem_5"], $data["Act_Par_Lib_Sem_5"], $data["Act_100_Lib_Sem_5"], $data["Act_Inician_Sem_6"], $data["Act_0_Lib_Sem_6"], $data["Act_Par_Lib_Sem_6"], $data["Act_100_Lib_Sem_6"]];
            
            $query1 .= "($arreglo[0], $arreglo[1], '$arreglo[2]', '$arreglo[3]', '$arreglo[4]', '$arreglo[5]', '$arreglo[6]', '$arreglo[7]', '$arreglo[8]', '$arreglo[9]', '$arreglo[10]', '$arreglo[11]', '$arreglo[12]', '$arreglo[13]', '$arreglo[14]', '$arreglo[15]', '$arreglo[16]', '$arreglo[17]', '$arreglo[18]', $arreglo[19], $arreglo[20], '$arreglo[21]', '$arreglo[22]', '$arreglo[23]', '$arreglo[24]', '$arreglo[25]', '$arreglo[26]', '$arreglo[27]', '$arreglo[28]', '$arreglo[29]', '$arreglo[30]', '$arreglo[31]', '$arreglo1[0]', '$arreglo1[1]', '$arreglo1[2]', '$arreglo1[3]', '$arreglo1[4]', '$arreglo1[5]', '$arreglo1[6]', '$arreglo1[7]', '$arreglo1[8]', '$arreglo1[9]', '$arreglo1[10]', '$arreglo1[11]', '$arreglo1[12]', '$arreglo1[13]', '$arreglo1[14]', '$arreglo1[15]', '$arreglo1[16]', '$arreglo1[17]', '$arreglo1[18]', '$arreglo1[19]', '$arreglo1[20]', '$arreglo1[21]', '$arreglo1[22]', '$arreglo1[23]'),\n";
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
    $contador=0;
    if(!$resultado){
        die(mysqli_error($conexion));
    } else{
        $query1="INSERT INTO $value"."_programa (`Consecutivo`, `Id`, `Actividad`, `Titulo`, `Fecha_Inicio`, `Fecha_Fin`, `Ruta_Critica`, `Ejecutado`, `Estado`, `Semanas_Inicio`, `Estado_Restricciones`, `D_y_E`, `Materiales`, `MdeO`, `Equipos`, `Predecesora`, `Pdto_Cons`, `Modelo`, `Responsable_AIA`, `Observaciones`, `Ult_Act_Est`, `Ult_Act_Restr`) VALUES\n";
        while($data=mysqli_fetch_assoc($resultado)){
            $arreglo = [$data["Consecutivo"], $data["Id"], $data["Actividad"], $data["Titulo"], $data["Fecha_Inicio"], $data["Fecha_Fin"], $data["Ruta_Critica"], $data["Ejecutado"], $data["Estado"], $data["Semanas_Inicio"], $data["Estado_Restricciones"], $data["D_y_E"], $data["Materiales"], $data["MdeO"], $data["Equipos"], $data["Predecesora"], $data["Pdto_Cons"], $data["Modelo"], $data["Responsable_AIA"], $data["Observaciones"], $data["Ult_Act_Est"], $data["Ult_Act_Restr"]];
            
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
            if($arreglo[20]=='' || $arreglo[20]=='0000-00-00'){
                $arreglo[20]='NULL';
                $arreglo20 ="$arreglo[20]";
            }else{
                $arreglo20 ="'$arreglo[20]'";
            }
            if($arreglo[21]==''|| $arreglo[21]=='0000-00-00'){
                $arreglo[21]='NULL';
                $arreglo21 ="$arreglo[21]";
            }else{
                $arreglo21 ="'$arreglo[21]'";
            }
            
            
            
            
            $query1 .= "($arreglo[0], '$arreglo[1]', '$arreglo[2]', '$arreglo[3]', $arreglo4, $arreglo5, $arreglo[6], '$arreglo[7]', '$arreglo[8]', '$arreglo[9]', '$arreglo[10]', '$arreglo[11]', '$arreglo[12]', '$arreglo[13]', '$arreglo[14]', '$arreglo[15]', '$arreglo[16]', '$arreglo[17]', '$arreglo[18]', '$arreglo[19]', $arreglo20, $arreglo21),\n";
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
        $query1="INSERT INTO $value"."_programacion_semanal (`Consecutivo`, `Semana`, `Consecutivo_En_Programa`, `Id`, `Actividad`, `Descripcion`, `Ubicacion`, `Fecha_Inicio`, `Fecha_Fin`, `Sub_Contratista`, `Responsable_AIA`, `Empresa`, `Ejecutado`, `medir_productividad`, `Unidad`, `cantidad_ppto`, `Compromiso`, `Ejecutado_Real`, `P_Completado`, `PAC`, `Critica`, `Atrasada`, `Activa`, `Prog_Sin_Restricciones_100`, `Categoria_CNP`, `CNP`, `Observaciones_CNP`, `Categoria_CNC`, `CNC`, `Observaciones_CNC`, `Rendimientos`, `codigo_actividad`) VALUES\n";
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
            
            $arreglo=[$data["Consecutivo"],$data["Semana"],$data["Consecutivo_En_Programa"],$data["Id"],$data["Actividad"],$data["Descripcion"],$data["Ubicacion"],$data["Fecha_Inicio"],$data["Fecha_Fin"],$data["Sub_Contratista"],$data["Responsable_AIA"],$data["Empresa"],$data["Ejecutado"],$data["medir_productividad"],$data["Unidad"],$data["cantidad_ppto"],$data["Compromiso"],$data["Ejecutado_Real"],$data["P_Completado"],$data["PAC"],$data["Critica"],$data["Atrasada"],$data["Activa"],$data["Prog_Sin_Restricciones_100"],$data["Categoria_CNP"],$data["CNP"],$data["Observaciones_CNP"],$data["Categoria_CNC"],$data["CNC"],$data["Observaciones_CNC"],$data["Rendimientos"],$data["codigo_actividad"]];
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
            if($arreglo[7]==''|| $arreglo[7]=='0000-00-00'){
                $arreglo[7]='NULL';
                $arreglo7 ="$arreglo[7]";
            }else{
                $arreglo7 ="'$arreglo[7]'";
            }
            if($arreglo[8]==''|| $arreglo[8]=='0000-00-00'){
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
            if($arreglo[10]==''){
                $arreglo[10]='NULL';
                $arreglo10 ="$arreglo[10]";
            }else{
                $arreglo10 ="'$arreglo[10]'";
            }
            if($arreglo[11]==''){
                $arreglo[11]='AIA';
                $arreglo11 ="'$arreglo[11]'";
            }else{
                $arreglo11 ="'$arreglo[11]'";
            }
            if($arreglo[12]==''){
                $arreglo[12]='NULL';
                $arreglo12 ="$arreglo[12]";
            }else{
                $arreglo12 =$arreglo[12];
            }
            if($arreglo[13]==''){
                $arreglo[13]=0;
                $arreglo13 =$arreglo[13];
            }else{
                $arreglo13 =$arreglo[13];
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
                $arreglo15 =$arreglo[15];
            }
            
            $query0="";
            for($i=24;$i<32;$i++){
                if($arreglo[$i]==''){
                    $arreglo[$i]='NULL';
                    $query0 .=" $arreglo[$i],";
                }else{
                    $query0 .=" '$arreglo[$i]',";
                }    
            }
            
            $query0 =substr($query0, 0, -1);

            if($contador==1000){
                $query2=";\nINSERT INTO $value"."_programacion_semanal (`Consecutivo`, `Semana`, `Consecutivo_En_Programa`, `Id`, `Actividad`, `Descripcion`, `Ubicacion`, `Fecha_Inicio`, `Fecha_Fin`, `Sub_Contratista`, `Responsable_AIA`, `Empresa`, `Ejecutado`, `medir_productividad`, `Unidad`, `cantidad_ppto`, `Compromiso`, `Ejecutado_Real`, `P_Completado`, `PAC`, `Critica`, `Atrasada`, `Activa`, `Prog_Sin_Restricciones_100`, `Categoria_CNP`, `CNP`, `Observaciones_CNP`, `Categoria_CNC`, `CNC`, `Observaciones_CNC`, `Rendimientos`, `codigo_actividad`) VALUES\n";
                $query1 =substr($query1, 0, -2);

                $query1 .= $query2 . "($arreglo[0], $arreglo[1], $arreglo[2], '$arreglo[3]', '$arreglo[4]', $arreglo5, $arreglo6, $arreglo7, $arreglo8, $arreglo9, $arreglo10, $arreglo11, $arreglo12, $arreglo13, $arreglo14, $arreglo15, $arreglo[16], $arreglo[17], $arreglo[18], $arreglo[19], $arreglo[20], $arreglo[21], '$arreglo[22]', $arreglo[23], $query0),\n";
                
                $contador=0;   
            }else{
                $query1 .="($arreglo[0], $arreglo[1], $arreglo[2], '$arreglo[3]', '$arreglo[4]', $arreglo5, $arreglo6, $arreglo7, $arreglo8, $arreglo9, $arreglo10, $arreglo11, $arreglo12, $arreglo13, $arreglo14, $arreglo15, $arreglo[16], $arreglo[17], $arreglo[18], $arreglo[19], $arreglo[20], $arreglo[21], '$arreglo[22]', $arreglo[23], $query0),\n";   
                
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
        $query1="INSERT INTO $value"."_programa_consolidado (`Consecutivo`, `Semana`, `Consecutivo_en_Programa`, `Id`, `Actividad`, `Titulo`, `Fecha_Inicio`, `Fecha_Fin`, `Ruta_Critica`, `Ejecutado`, `Estado`, `Semanas_Inicio`, `Estado_Restricciones`, `D_y_E`, `Materiales`, `MdeO`, `Equipos`, `Predecesora`, `Pdto_Cons`, `Modelo`, `Sub_Contratista`, `Responsable_AIA`, `Observaciones`, `Ult_Act_Est`, `Ult_Act_Restr`, `Activa`, `Ejecutado_Siguiente_Semana`, `codigo_actividad`, `medir_productividad`, `cantidad_ppto`, `unidad`) VALUES\n";
        while($data=mysqli_fetch_assoc($resultado)){
            $arreglo=[$data["Consecutivo"],$data["Semana"],$data["Consecutivo_en_Programa"],$data["Id"],$data["Actividad"],$data["Titulo"],$data["Fecha_Inicio"],$data["Fecha_Fin"],$data["Ruta_Critica"],$data["Ejecutado"],$data["Estado"],$data["Semanas_Inicio"],$data["Estado_Restricciones"],$data["D_y_E"],$data["Materiales"],$data["MdeO"],$data["Equipos"],$data["Predecesora"],$data["Pdto_Cons"],$data["Modelo"],$data["Sub_Contratista"],$data["Responsable_AIA"],$data["Observaciones"],$data["Ult_Act_Est"],$data["Ult_Act_Restr"],$data["Activa"],$data["Ejecutado_Siguiente_Semana"],$data["codigo_actividad"],$data["medir_productividad"],$data["cantidad_ppto"],$data["unidad"]];
            
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
            if($arreglo[9]==''){
                $arreglo[9]='NULL';
                $arreglo9 =$arreglo[9];
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
                $arreglo11 =$arreglo[11];
            }else{
                $arreglo11 =$arreglo[11];
            }
            
            
            $query0="";
            for($i=12;$i<23;$i++){
                if($arreglo[$i]==''){
                    $arreglo[$i]='NULL';
                    $query0 .=" $arreglo[$i],";
                }else{
                    $query0 .=" '$arreglo[$i]',";
                }    
            }
            
            
            
            if($arreglo[23]=='' || $arreglo[23]=='0000-00-00'){
                $arreglo[23]='NULL';
                $arreglo23 ="$arreglo[23]";
            }else{
                $arreglo23 ="'$arreglo[23]'";
            }
            if($arreglo[24]=='' || $arreglo[24]=='0000-00-00'){
                $arreglo[24]='NULL';
                $arreglo24 ="$arreglo[24]";
            }else{
                $arreglo24 ="'$arreglo[24]'";
            }
            if($arreglo[26]==''){
                $arreglo[26]='NULL';
                $arreglo26 ="$arreglo[26]";
            }else{
                $arreglo26 =$arreglo[26];
            }
            if($arreglo[27]==''){
                $arreglo[27]='NULL';
                $arreglo27 ="$arreglo[27]";
            }else{
                $arreglo27 ="'$arreglo[27]'";
            }
            if($arreglo[28]==''){
                $arreglo[28]=0;
                $arreglo28 =$arreglo[28];
            }else{
                $arreglo28 =$arreglo[28];
            }
            if($arreglo[29]==''){
                $arreglo[29]='NULL';
                $arreglo29 ="$arreglo[29]";
            }else{
                $arreglo29 =$arreglo[29];
            }
            if($arreglo[30]==''){
                $arreglo[30]='NULL';
                $arreglo30 ="$arreglo[30]";
            }else{
                $arreglo30 ="'$arreglo[30]'";
            }
            
            if($contador==1000){
                $query2=";\nINSERT INTO $value"."_programa_consolidado (`Consecutivo`, `Semana`, `Consecutivo_en_Programa`, `Id`, `Actividad`, `Titulo`, `Fecha_Inicio`, `Fecha_Fin`, `Ruta_Critica`, `Ejecutado`, `Estado`, `Semanas_Inicio`, `Estado_Restricciones`, `D_y_E`, `Materiales`, `MdeO`, `Equipos`, `Predecesora`, `Pdto_Cons`, `Modelo`, `Sub_Contratista`, `Responsable_AIA`, `Observaciones`, `Ult_Act_Est`, `Ult_Act_Restr`, `Activa`, `Ejecutado_Siguiente_Semana`, `codigo_actividad`, `medir_productividad`, `cantidad_ppto`, `unidad`) VALUES\n";
                
                $query1 =substr($query1, 0, -2);
                $query1 .= $query2 . "($arreglo[0], $arreglo[1], $arreglo[2], $arreglo3, '$arreglo[4]', '$arreglo[5]', $arreglo6, $arreglo7, '$arreglo[8]', $arreglo9, $arreglo10, $arreglo11, $query0";

                $query1 .=" $arreglo23, $arreglo24, $arreglo[25], $arreglo26, $arreglo27, $arreglo28, $arreglo29, $arreglo30),\n";
                
                $contador=0;
            }else{
                $query1 .= "($arreglo[0], $arreglo[1], $arreglo[2], $arreglo3, '$arreglo[4]', '$arreglo[5]', $arreglo6, $arreglo7, '$arreglo[8]', $arreglo9, $arreglo10, $arreglo11, $query0";

                $query1 .=" $arreglo23, $arreglo24, $arreglo[25], $arreglo26, $arreglo27, $arreglo28, $arreglo29, $arreglo30),\n";
                
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
            
            $query1 .= "($arreglo[0], '$arreglo[1]', '$arreglo[2]', '$arreglo[3]', '$arreglo[4]', '$arreglo[5]'),\n";
        }
        $query_salida .= substr($query1, 0, -2) . "; \n\n" ;
        
        mysqli_free_result($resultado);
    }

    
    $ar=fopen("copias_de_seguridad/$fechaActual"."_$value.sql", "a") or die("Error al crear");

    fwrite($ar, $query_salida);

    echo "<li>- Se creó correctamente el archivo '$fechaActual"."_$value.sql' \n\n";  
}

$query_salida = "--\n-- Tabla general_cnc\n--\n\n";
//cnc
    
$query="SELECT * FROM general_cnc"; 

$resultado = mysqli_query($conexion, $query);
if(!$resultado){
    die(mysqli_error($conexion));
} else{
    $query1="INSERT INTO general_cnc (`Id`, `Categoria_CNC`, `CNC`) VALUES\n";
    while($data=mysqli_fetch_assoc($resultado)){
        $arreglo=[ $data["Id"], $data["Categoria_CNC"], $data["CNC"]];

        $query1 .= "($arreglo[0], '$arreglo[1]', '$arreglo[2]'),\n";
    }
    $query_salida .= substr($query1, 0, -2) . "; \n\n" ;

    mysqli_free_result($resultado);
}

$ar=fopen("copias_de_seguridad/$fechaActual"."_general_cnc.sql", "a") or die("Error al crear");

fwrite($ar, $query_salida);

echo "<li>- Se creó correctamente el archivo '$fechaActual"."_general_cnc.sql' \n\n";


$query_salida = "--\n-- Tabla general_usuarios\n--\n\n";
//usuarios
    
$query="SELECT * FROM general_usuarios"; 

$resultado = mysqli_query($conexion, $query);
if(!$resultado){
    die(mysqli_error($conexion));
} else{
    $query1="INSERT INTO general_usuarios (`id`, `nombre`, `email`, `cargo`, `proyecto`, `permiso`, `usuario`, `password`) VALUES\n";
    while($data=mysqli_fetch_assoc($resultado)){
        $arreglo=[ $data["id"], $data["nombre"], $data["email"], $data["cargo"], $data["proyecto"], $data["permiso"], $data["usuario"], $data["password"]];

        $query1 .= "($arreglo[0], '$arreglo[1]', '$arreglo[2]', '$arreglo[3]', '$arreglo[4]', '$arreglo[5]', '$arreglo[6]', '$arreglo[7]'),\n";
    }
    $query_salida .= substr($query1, 0, -2) . "; \n\n" ;

    mysqli_free_result($resultado);
}

$ar=fopen("copias_de_seguridad/$fechaActual"."_general_usuarios.sql", "a") or die("Error al crear");

fwrite($ar, $query_salida);

echo "<li>- Se creó correctamente el archivo '$fechaActual"."_general_usuarios.sql' \n\n";

$query_salida = "--\n-- Tabla general_proyectos_procesos\n--\n\n";
//proyectos_procesos
    
$query="SELECT * FROM general_proyectos_procesos"; 

$resultado = mysqli_query($conexion, $query);
if(!$resultado){
    die(mysqli_error($conexion));
} else{
    $query1="INSERT INTO general_proyectos_procesos (`Id`, `Proyecto_Proceso`, `Base_de_Datos`, `Area`, `Activo`) VALUES\n";
    while($data=mysqli_fetch_assoc($resultado)){
        $arreglo=[ $data["Id"], $data["Proyecto_Proceso"], $data["Base_de_Datos"], $data["Area"], $data["Activo"]];

        $query1 .= "($arreglo[0], '$arreglo[1]', '$arreglo[2]', '$arreglo[3]', $arreglo[4]),\n";
    }
    $query_salida .= substr($query1, 0, -2) . "; \n\n" ;

    mysqli_free_result($resultado);
}

$ar=fopen("copias_de_seguridad/$fechaActual"."_general_proyectos_procesos.sql", "a") or die("Error al crear");

fwrite($ar, $query_salida);

echo "<li>- Se creó correctamente el archivo '$fechaActual"."_general_proyectos_procesos.sql' \n\n";


$query_salida = "--\n-- Tabla general_codigos_actividades\n--\n\n";
//codigos_actividades
    
$query="SELECT * FROM general_codigos_actividades"; 

$resultado = mysqli_query($conexion, $query);
if(!$resultado){
    die(mysqli_error($conexion));
} else{
    $query1="INSERT INTO general_codigos_actividades (`Id`, `codigo_actividad`, `actividad`, `unidad`) VALUES\n";
    while($data=mysqli_fetch_assoc($resultado)){
        $arreglo=[ $data["Id"], $data["codigo_actividad"], $data["actividad"], $data["unidad"]];

        $query1 .= "($arreglo[0], '$arreglo[1]', '$arreglo[2]', '$arreglo[3]'),\n";
    }
    $query_salida .= substr($query1, 0, -2) . "; \n\n" ;

    mysqli_free_result($resultado);
}

$ar=fopen("copias_de_seguridad/$fechaActual"."_general_codigos_actividades.sql", "a") or die("Error al crear");

fwrite($ar, $query_salida);

echo "<li>- Se creó correctamente el archivo '$fechaActual"."_general_codigos_actividades.sql' \n\n";

$query_salida = "--\n-- Tabla general_costos_cuadrillas\n--\n\n";
//costos_cuadrillas
    
$query="SELECT * FROM general_costos_cuadrillas"; 

$resultado = mysqli_query($conexion, $query);
if(!$resultado){
    die(mysqli_error($conexion));
} else{
    $query1="INSERT INTO general_costos_cuadrillas (`Id`, `Proyecto`, `Costo_Hora_oficial`, `Costo_Hora_Ayudante`) VALUES\n";
    while($data=mysqli_fetch_assoc($resultado)){
        $arreglo=[ $data["Id"], $data["Proyecto"], $data["Costo_Hora_oficial"], $data["Costo_Hora_Ayudante"]];

        $query1 .= "($arreglo[0], '$arreglo[1]', '$arreglo[2]', '$arreglo[3]'),\n";
    }
    $query_salida .= substr($query1, 0, -2) . "; \n\n" ;

    mysqli_free_result($resultado);
}

$ar=fopen("copias_de_seguridad/$fechaActual"."_general_costos_cuadrillas.sql", "a") or die("Error al crear");

fwrite($ar, $query_salida);

/*$nombre="$fechaActual"."_general_costos_cuadrillas.sql";
header('Content-Encoding: UTF-8');
header ("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header ("Last-Modified: " . gmdate("D,d M YH:i:s") . " GMT");
header ("Cache-Control: no-cache, must-revalidate");
header ("Pragma: no-cache");
header ("Content-type: application/x-msexcel;charset=UTF-8");
header ("Content-Disposition: attachment; filename=$nombre" );

echo $query_salida;*/

echo "<li>- Se creó correctamente el archivo '$fechaActual"."_general_costos_cuadrillas.sql' \n\n";

mysqli_close($conexion);



?>