<?php
	


$proyecto= ["clinica_del_sur", "paris_campestre", "mallplaza_cali", "bodega_latam", "concejo_bogota", "cedi_pasto", "parqueadero_alkosto", "camino_verde", "prueba"];


foreach($proyecto as $value){
    $server = "localhost";
	$user = "aia_fbenitez";/*id11931347_*/
	$password = "ta2AsW(2YU+_";//poner tu propia contraseña, si tienes una.
	$bd = "aia_mascerteza";
	$conexion = mysqli_connect($server, $user, $password, $bd);
	if (!$conexion){ 
		die('Error de Conexión: ' . mysqli_connect_errno());	
	}
    
    $query="CREATE TABLE `$value"."_cic` (
  `Id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `Semana` int(3) DEFAULT NULL,
  `subcontratista` varchar(200) DEFAULT NULL,
  `correo_contacto` varchar(200) DEFAULT NULL,
  `NIT` bigint(20) DEFAULT NULL,
  `alcance` varchar(200) DEFAULT NULL,
  `tipo_proveedor` varchar(200) DEFAULT NULL,
  `PAC` varchar(11) DEFAULT 'NA',
  `PAC_Acum` varchar(11) DEFAULT 'NA',
  `P_Completado` varchar(11) DEFAULT 'NA',
  `P_Completado_Acum` varchar(11) DEFAULT 'NA',
  `Calidad` varchar(11) NOT NULL DEFAULT 'NR',
  `Calidad_Acum` varchar(11) NOT NULL DEFAULT 'NR',
  `GSA` varchar(11) NOT NULL DEFAULT 'NR',
  `GSA_Acum` varchar(11) NOT NULL DEFAULT 'NR',
  `SST` varchar(11) NOT NULL DEFAULT 'NR',
  `SST_Acum` varchar(11) NOT NULL DEFAULT 'NR',
  `ADM` varchar(11) NOT NULL DEFAULT 'NR',
  `ADM_Acum` varchar(11) NOT NULL DEFAULT 'NR',
  `Cal_Integral` float DEFAULT NULL,
  `Cal_Integral_Acum` float DEFAULT NULL,
  `Observaciones` mediumtext DEFAULT NULL,
  `mdo_cal_1` varchar(5) DEFAULT 'NA',
  `mdo_cal_2` varchar(5) DEFAULT 'NA',
  `mdo_cal_3` varchar(5) DEFAULT 'NA',
  `mdo_adm_1` varchar(5) DEFAULT 'NA',
  `mdo_adm_2` varchar(5) DEFAULT 'NA',
  `mdo_adm_3` varchar(5) DEFAULT 'NA',
  `mdo_adm_4` varchar(5) DEFAULT 'NA',
  `mdo_adm_5` varchar(5) DEFAULT 'NA',
  `mdo_gsa_1` varchar(5) DEFAULT 'NA',
  `mdo_gsa_2` varchar(5) DEFAULT 'NA',
  `mdo_gsa_3` varchar(5) DEFAULT 'NA',
  `mdo_gsa_4` varchar(5) DEFAULT 'NA',
  `mdo_gsa_5` varchar(5) DEFAULT 'NA',
  `mdo_gsa_6` varchar(5) DEFAULT 'NA',
  `mdo_gsa_7` varchar(5) DEFAULT 'NA',
  `mdo_gsa_8` varchar(5) DEFAULT 'NA',
  `mdo_sst_1` varchar(5) DEFAULT 'NA',
  `mdo_sst_2` varchar(5) DEFAULT 'NA',
  `mdo_sst_3` varchar(5) DEFAULT 'NA',
  `mdo_sst_4` varchar(5) DEFAULT 'NA',
  `mdo_sst_5` varchar(5) DEFAULT 'NA',
  `mdo_sst_6` varchar(5) DEFAULT 'NA',
  `mdo_sst_7` varchar(5) DEFAULT 'NA',
  `mdo_sst_8` varchar(5) DEFAULT 'NA',
  `mdo_sst_9` varchar(5) DEFAULT 'NA',
  `mdo_sst_10` varchar(5) DEFAULT 'NA',
  `si_cal_1` varchar(5) DEFAULT 'NA',
  `si_cal_2` varchar(5) DEFAULT 'NA',
  `si_cal_3` varchar(5) DEFAULT 'NA',
  `si_adm_1` varchar(5) DEFAULT 'NA',
  `si_adm_2` varchar(5) DEFAULT 'NA',
  `si_adm_3` varchar(5) DEFAULT 'NA',
  `si_adm_4` varchar(5) DEFAULT 'NA',
  `si_adm_5` varchar(5) DEFAULT 'NA',
  `si_adm_6` varchar(5) DEFAULT 'NA',
  `si_gsa_1` varchar(5) DEFAULT 'NA',
  `si_gsa_2` varchar(5) DEFAULT 'NA',
  `si_gsa_3` varchar(5) DEFAULT 'NA',
  `si_gsa_4` varchar(5) DEFAULT 'NA',
  `si_gsa_5` varchar(5) DEFAULT 'NA',
  `si_gsa_6` varchar(5) DEFAULT 'NA',
  `si_gsa_7` varchar(5) DEFAULT 'NA',
  `si_gsa_8` varchar(5) DEFAULT 'NA',
  `si_gsa_9` varchar(5) DEFAULT 'NA',
  `si_gsa_10` varchar(5) DEFAULT 'NA',
  `si_gsa_11` varchar(5) DEFAULT 'NA',
  `si_gsa_12` varchar(5) DEFAULT 'NA',
  `si_gsa_13` varchar(5) DEFAULT 'NA',
  `si_gsa_14` varchar(5) DEFAULT 'NA',
  `si_sst_1` varchar(5) DEFAULT 'NA',
  `si_sst_2` varchar(5) DEFAULT 'NA',
  `si_sst_3` varchar(5) DEFAULT 'NA',
  `si_sst_4` varchar(5) DEFAULT 'NA',
  `si_sst_5` varchar(5) DEFAULT 'NA',
  `si_sst_6` varchar(5) DEFAULT 'NA',
  `si_sst_7` varchar(5) DEFAULT 'NA',
  `si_sst_8` varchar(5) DEFAULT 'NA',
  `si_sst_9` varchar(5) DEFAULT 'NA',
  `si_sst_10` varchar(5) DEFAULT 'NA'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `proyecto_cip`
--

CREATE TABLE `$value"."_cip` (
  `Id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `Semana` int(3) DEFAULT NULL,
  `profesional` varchar(50) DEFAULT NULL,
  `correo_contacto` varchar(50) DEFAULT NULL,
  `PAC` varchar(11) DEFAULT 'NA',
  `PAC_Acum` varchar(11) DEFAULT 'NA',
  `P_Completado` varchar(11) DEFAULT 'NA',
  `P_Completado_Acum` varchar(11) DEFAULT 'NA',
  `Act_Criticas_Cumplidas` varchar(11) DEFAULT 'NA',
  `Act_Criticas_Cumplidas_Acum` varchar(11) DEFAULT 'NA',
  `Act_No_Criticas_Cumplidas` varchar(11) DEFAULT 'NA',
  `Act_No_Criticas_Cumplidas_Acum` varchar(11) DEFAULT 'NA',
  `Act_Atrasadas_Cumplidas` varchar(11) DEFAULT 'NA',
  `Act_Atrasadas_Cumplidas_Acum` varchar(11) DEFAULT 'NA',
  `PAC_Consolidado` float DEFAULT NULL,
  `PAC_Consolidado_Acum` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `proyecto_indicadores_generales`
--

CREATE TABLE `$value"."_indicadores_generales` (
  `Id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `Semana` int(3) NOT NULL,
  `subcontratista_profesional` varchar(100) NOT NULL,
  `rol` varchar(100) NOT NULL,
  `PAC` varchar(100) NOT NULL DEFAULT 'NA',
  `PAC_Acum` varchar(100) NOT NULL DEFAULT 'NA',
  `P_Completado` varchar(100) NOT NULL DEFAULT 'NA',
  `P_Completado_Acum` varchar(100) NOT NULL DEFAULT 'NA',
  `CNC_Rendimiento` int(11) NOT NULL DEFAULT 0,
  `CNC_Rendimiento_Acum` int(11) NOT NULL DEFAULT 0,
  `CNC_Programacion` int(11) NOT NULL DEFAULT 0,
  `CNC_Programacion_Acum` int(11) NOT NULL DEFAULT 0,
  `CNC_MdeO` int(11) NOT NULL DEFAULT 0,
  `CNC_MdeO_Acum` int(11) NOT NULL DEFAULT 0,
  `CNC_Materiales` int(11) NOT NULL DEFAULT 0,
  `CNC_Materiales_Acum` int(11) NOT NULL DEFAULT 0,
  `CNC_Equipos` int(11) NOT NULL DEFAULT 0,
  `CNC_Equipos_Acum` int(11) NOT NULL DEFAULT 0,
  `CNC_Disenos` int(11) NOT NULL DEFAULT 0,
  `CNC_Disenos_Acum` int(11) NOT NULL DEFAULT 0,
  `CNC_Administrativas` int(11) NOT NULL DEFAULT 0,
  `CNC_Administrativas_Acum` int(11) NOT NULL DEFAULT 0,
  `Criticas_Comp` varchar(11) NOT NULL DEFAULT 'NA',
  `Criticas_Comp_Acum` varchar(11) NOT NULL DEFAULT 'NA',
  `No_Criticas_Comp` varchar(11) NOT NULL DEFAULT 'NA',
  `No_Criticas_Comp_Acum` varchar(11) NOT NULL DEFAULT 'NA',
  `Atrasadas_Criticas_Comp` varchar(11) NOT NULL DEFAULT 'NA',
  `Atrasadas_Criticas_Comp_Acum` varchar(11) NOT NULL DEFAULT 'NA',
  `Atrasadas_No_Criticas_Comp` varchar(11) NOT NULL DEFAULT 'NA',
  `Atrasadas_No_Criticas_Comp_Acum` varchar(11) NOT NULL DEFAULT 'NA',
  `Comp_Sin_Rest_100` varchar(11) NOT NULL DEFAULT 'NA',
  `Comp_Sin_Rest_100_Acum` varchar(11) NOT NULL DEFAULT 'NA',
  `Act_Inician_Sem_1` varchar(11) NOT NULL DEFAULT '0',
  `Act_0_Lib_Sem_1` varchar(11) NOT NULL DEFAULT '0',
  `Act_Par_Lib_Sem_1` varchar(11) NOT NULL DEFAULT '0',
  `Act_100_Lib_Sem_1` varchar(11) NOT NULL DEFAULT '0',
  `Act_Inician_Sem_2` varchar(11) NOT NULL DEFAULT '0',
  `Act_0_Lib_Sem_2` varchar(11) NOT NULL DEFAULT '0',
  `Act_Par_Lib_Sem_2` varchar(11) NOT NULL DEFAULT '0',
  `Act_100_Lib_Sem_2` varchar(11) NOT NULL DEFAULT '0',
  `Act_Inician_Sem_3` varchar(11) NOT NULL DEFAULT '0',
  `Act_0_Lib_Sem_3` varchar(11) NOT NULL DEFAULT '0',
  `Act_Par_Lib_Sem_3` varchar(11) NOT NULL DEFAULT '0',
  `Act_100_Lib_Sem_3` varchar(11) NOT NULL DEFAULT '0',
  `Act_Inician_Sem_4` varchar(11) NOT NULL DEFAULT '0',
  `Act_0_Lib_Sem_4` varchar(11) NOT NULL DEFAULT '0',
  `Act_Par_Lib_Sem_4` varchar(11) NOT NULL DEFAULT '0',
  `Act_100_Lib_Sem_4` varchar(11) NOT NULL DEFAULT '0',
  `Act_Inician_Sem_5` varchar(11) NOT NULL DEFAULT '0',
  `Act_0_Lib_Sem_5` varchar(11) NOT NULL DEFAULT '0',
  `Act_Par_Lib_Sem_5` varchar(11) NOT NULL DEFAULT '0',
  `Act_100_Lib_Sem_5` varchar(11) NOT NULL DEFAULT '0',
  `Act_Inician_Sem_6` varchar(11) NOT NULL DEFAULT '0',
  `Act_0_Lib_Sem_6` varchar(11) NOT NULL DEFAULT '0',
  `Act_Par_Lib_Sem_6` varchar(11) NOT NULL DEFAULT '0',
  `Act_100_Lib_Sem_6` varchar(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `proyecto_profesionales`
--

CREATE TABLE `$value"."_profesionales` (
  `id` int(3) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `nombre` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `cargo` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `proyecto_programa`
--

CREATE TABLE `$value"."_programa` (
  `Consecutivo` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `Id` varchar(500) DEFAULT NULL,
  `Actividad` varchar(500) DEFAULT NULL,
  `Titulo` int(11) DEFAULT NULL,
  `Fecha_Inicio` date DEFAULT NULL,
  `Fecha_Fin` date DEFAULT NULL,
  `Ruta_Critica` int(11) DEFAULT NULL,
  `Ejecutado` float DEFAULT 0,
  `Estado` varchar(50) DEFAULT NULL,
  `Semanas_Inicio` int(1) DEFAULT 0,
  `Estado_Restricciones` float DEFAULT 0,
  `D_y_E` float DEFAULT 0,
  `Materiales` float DEFAULT 0,
  `MdeO` float DEFAULT 0,
  `Equipos` float DEFAULT 0,
  `Predecesora` float DEFAULT 0,
  `Pdto_Cons` float DEFAULT 0,
  `Modelo` varchar(9) DEFAULT '0',
  `Responsable_AIA` varchar(100) DEFAULT NULL,
  `Observaciones` mediumtext DEFAULT NULL,
  `Ult_Act_Est` date DEFAULT NULL,
  `Ult_Act_Restr` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `proyecto_programacion_semanal`
--

CREATE TABLE `$value"."_programacion_semanal` (
  `Consecutivo` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `Semana` int(3) DEFAULT NULL,
  `Consecutivo_En_Programa` int(11) NOT NULL,
  `Id` varchar(500) DEFAULT NULL,
  `Actividad` varchar(500) DEFAULT NULL,
  `Descripcion` mediumtext DEFAULT NULL,
  `Ubicacion` mediumtext DEFAULT NULL,
  `Fecha_Inicio` date DEFAULT NULL,
  `Fecha_Fin` date DEFAULT NULL,
  `Sub_Contratista` varchar(200) DEFAULT NULL,
  `Responsable_AIA` varchar(200) DEFAULT NULL,
  `Empresa` varchar(200) NOT NULL DEFAULT 'AIA',
  `Ejecutado` float DEFAULT NULL,
  `medir_productividad` int(11) DEFAULT 0,
  `Unidad` varchar(10) DEFAULT NULL,
  `cantidad_ppto` int(11) DEFAULT NULL,
  `Compromiso` float DEFAULT NULL,
  `Ejecutado_Real` float DEFAULT NULL,
  `P_Completado` float DEFAULT NULL,
  `PAC` int(1) DEFAULT NULL,
  `Critica` int(1) DEFAULT NULL,
  `Atrasada` int(1) DEFAULT NULL,
  `Activa` varchar(3) DEFAULT NULL,
  `Prog_Sin_Restricciones_100` int(1) DEFAULT NULL,
  `Categoria_CNP` varchar(100) DEFAULT NULL,
  `CNP` varchar(100) DEFAULT NULL,
  `Observaciones_CNP` mediumtext DEFAULT NULL,
  `Categoria_CNC` varchar(100) DEFAULT NULL,
  `CNC` varchar(100) DEFAULT NULL,
  `Observaciones_CNC` mediumtext DEFAULT NULL,
  `Rendimientos` varchar(500) DEFAULT NULL,
  `codigo_actividad` varchar(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `proyecto_programa_consolidado`
--

CREATE TABLE `$value"."_programa_consolidado` (
  `Consecutivo` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `Semana` int(3) NOT NULL,
  `Consecutivo_en_Programa` int(11) NOT NULL,
  `Id` varchar(500) DEFAULT NULL,
  `Actividad` varchar(500) DEFAULT NULL,
  `Titulo` int(11) DEFAULT NULL,
  `Fecha_Inicio` date DEFAULT NULL,
  `Fecha_Fin` date DEFAULT NULL,
  `Ruta_Critica` int(11) DEFAULT NULL,
  `Ejecutado` float DEFAULT 0,
  `Estado` varchar(50) DEFAULT NULL,
  `Semanas_Inicio` int(10) DEFAULT 0,
  `Estado_Restricciones` float DEFAULT 0,
  `D_y_E` varchar(9) DEFAULT '0',
  `Materiales` varchar(9) DEFAULT '0',
  `MdeO` varchar(9) DEFAULT '0',
  `Equipos` varchar(9) DEFAULT '0',
  `Predecesora` varchar(9) DEFAULT '0',
  `Pdto_Cons` varchar(9) DEFAULT '0',
  `Modelo` varchar(9) DEFAULT '0',
  `Sub_Contratista` varchar(100) DEFAULT NULL,
  `Responsable_AIA` varchar(100) DEFAULT NULL,
  `Observaciones` mediumtext DEFAULT NULL,
  `Ult_Act_Est` date DEFAULT NULL,
  `Ult_Act_Restr` date DEFAULT NULL,
  `Activa` int(1) NOT NULL DEFAULT 0,
  `Ejecutado_Siguiente_Semana` float DEFAULT NULL,
  `codigo_actividad` varchar(11) DEFAULT NULL,
  `medir_productividad` int(11) DEFAULT 0,
  `cantidad_ppto` int(11) DEFAULT NULL,
  `unidad` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `proyecto_semanas_activas`
--

CREATE TABLE `$value"."_semanas_activas` (
  `Id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `Semana` int(11) NOT NULL,
  `Fecha_Inicio_Sem` date NOT NULL,
  `Fecha_Fin_Sem` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `proyecto_subcontratistas`
--

CREATE TABLE `$value"."_subcontratistas` (
  `Id` int(3) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `subcontratista` varchar(200) NOT NULL,
  `correo_contacto` varchar(200) NOT NULL,
  `NIT` varchar(20) NOT NULL,
  `alcance` varchar(200) NOT NULL,
  `tipo_proveedor` varchar(200) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
    $resultado = mysqli_multi_query($conexion, $query);

    if(!$resultado){
        die(mysqli_error($conexion));
    } else{
        /*while($data=mysqli_fetch_assoc($resultado)){
        $arreglo["data"][]=array_map("utf8_encode", $data);
        }
        $json_codificado = json_encode($arreglo, JSON_UNESCAPED_UNICODE);
        echo utf8_decode($json_codificado);*/
        echo "<li>Proyecto $value Creado<br>";
    } 
    mysqli_close($conexion);
    
}



?>