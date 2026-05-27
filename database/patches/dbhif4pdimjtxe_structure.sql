-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost
-- Tiempo de generación: 22-05-2026 a las 21:47:16
-- Versión del servidor: 8.4.6-6
-- Versión de PHP: 8.2.31

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `dbhif4pdimjtxe`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `accesibilidadMetroA_actividades`
--

CREATE TABLE `accesibilidadMetroA_actividades` (
  `Id` int NOT NULL,
  `codigo` int NOT NULL,
  `actividad` varchar(300) NOT NULL,
  `descripcionActividad` mediumtext,
  `actividadInicio` varchar(500) DEFAULT NULL,
  `nombreActividadInicio` varchar(500) DEFAULT NULL,
  `fechaInicio` date DEFAULT NULL,
  `tipoContrato` varchar(10) DEFAULT NULL,
  `semanaActualizacion` int DEFAULT NULL,
  `SI1` varchar(200) DEFAULT NULL,
  `paqueteSI1` varchar(200) DEFAULT NULL,
  `SI2` varchar(200) DEFAULT NULL,
  `paqueteSI2` varchar(200) DEFAULT NULL,
  `SI3` varchar(200) DEFAULT NULL,
  `paqueteSI3` varchar(200) DEFAULT NULL,
  `SI4` varchar(200) DEFAULT NULL,
  `paqueteSI4` varchar(200) DEFAULT NULL,
  `SI5` varchar(200) DEFAULT NULL,
  `paqueteSI5` varchar(200) DEFAULT NULL,
  `S1` varchar(200) DEFAULT NULL,
  `paqueteS1` varchar(200) DEFAULT NULL,
  `S2` varchar(200) DEFAULT NULL,
  `paqueteS2` varchar(200) DEFAULT NULL,
  `S3` varchar(200) DEFAULT NULL,
  `paqueteS3` varchar(200) DEFAULT NULL,
  `S4` varchar(200) DEFAULT NULL,
  `paqueteS4` varchar(200) DEFAULT NULL,
  `S5` varchar(200) DEFAULT NULL,
  `paqueteS5` varchar(200) DEFAULT NULL,
  `MO1` varchar(200) DEFAULT NULL,
  `paqueteMO1` varchar(200) DEFAULT NULL,
  `MO2` varchar(200) DEFAULT NULL,
  `paqueteMO2` varchar(200) DEFAULT NULL,
  `MO3` varchar(200) DEFAULT NULL,
  `paqueteMO3` varchar(200) DEFAULT NULL,
  `MO4` varchar(200) DEFAULT NULL,
  `paqueteMO4` varchar(200) DEFAULT NULL,
  `MO5` varchar(200) DEFAULT NULL,
  `paqueteMO5` varchar(200) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `accesibilidadMetroA_cambios`
--

CREATE TABLE `accesibilidadMetroA_cambios` (
  `id` int NOT NULL,
  `solicitanteCambio` int DEFAULT NULL,
  `detalleSolicitanteOtro` longtext,
  `fechaSolicitud` date DEFAULT NULL,
  `prioridad` int DEFAULT NULL,
  `tipoCambio` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `responsableSolucion` int DEFAULT NULL,
  `detalleResponsableSolucion` longtext,
  `justificacion` longtext,
  `descripcion` longtext,
  `incidenciaAlcance` longtext,
  `tiempoCronograma` float DEFAULT NULL,
  `tiempoCronogramaAfectado` float DEFAULT NULL,
  `incidenciaCronograma` longtext,
  `valorPresupuesto` float DEFAULT NULL,
  `costoDirecto` float DEFAULT NULL,
  `costoDirectoAIU` float DEFAULT NULL,
  `costoDirectoAIUIVA` float DEFAULT NULL,
  `valorAprobado` float DEFAULT NULL,
  `incidenciaPresupuesto` longtext,
  `incidenciaCalidad` longtext,
  `incidenciaRiesgo` longtext,
  `incidenciaRecurso` longtext,
  `fechaTentativaDefinicion` date DEFAULT NULL,
  `fechaEntregaInterventoria` date DEFAULT NULL,
  `Observaciones` longtext,
  `fechaDefinicion` date DEFAULT NULL,
  `aprobacion` int DEFAULT NULL,
  `soportes` longtext
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `accesibilidadMetroA_cic`
--

CREATE TABLE `accesibilidadMetroA_cic` (
  `Id` int NOT NULL,
  `Semana` int DEFAULT NULL,
  `subcontratista` varchar(200) DEFAULT NULL,
  `correo_contacto` varchar(200) DEFAULT NULL,
  `NIT` varchar(10) DEFAULT NULL,
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
  `Observaciones` mediumtext,
  `mdo_cal_1` varchar(5) DEFAULT 'NR',
  `mdo_cal_2` varchar(5) DEFAULT 'NR',
  `mdo_cal_3` varchar(5) DEFAULT 'NR',
  `mdo_adm_1` varchar(5) DEFAULT 'NR',
  `mdo_adm_2` varchar(5) DEFAULT 'NR',
  `mdo_adm_3` varchar(5) DEFAULT 'NR',
  `mdo_adm_4` varchar(5) DEFAULT 'NR',
  `mdo_adm_5` varchar(5) DEFAULT 'NR',
  `mdo_gsa_1` varchar(5) DEFAULT 'NR',
  `mdo_gsa_2` varchar(5) DEFAULT 'NR',
  `mdo_gsa_3` varchar(5) DEFAULT 'NR',
  `mdo_gsa_4` varchar(5) DEFAULT 'NR',
  `mdo_gsa_5` varchar(5) DEFAULT 'NR',
  `mdo_gsa_6` varchar(5) DEFAULT 'NR',
  `mdo_gsa_7` varchar(5) DEFAULT 'NR',
  `mdo_gsa_8` varchar(5) DEFAULT 'NR',
  `mdo_sst_1` varchar(5) DEFAULT 'NR',
  `mdo_sst_2` varchar(5) DEFAULT 'NR',
  `mdo_sst_3` varchar(5) DEFAULT 'NR',
  `mdo_sst_4` varchar(5) DEFAULT 'NR',
  `mdo_sst_5` varchar(5) DEFAULT 'NR',
  `mdo_sst_6` varchar(5) DEFAULT 'NR',
  `mdo_sst_7` varchar(5) DEFAULT 'NR',
  `mdo_sst_8` varchar(5) DEFAULT 'NR',
  `mdo_sst_9` varchar(5) DEFAULT 'NR',
  `mdo_sst_10` varchar(5) DEFAULT 'NR',
  `si_cal_1` varchar(5) DEFAULT 'NR',
  `si_cal_2` varchar(5) DEFAULT 'NR',
  `si_cal_3` varchar(5) DEFAULT 'NR',
  `si_adm_1` varchar(5) DEFAULT 'NR',
  `si_adm_2` varchar(5) DEFAULT 'NR',
  `si_adm_3` varchar(5) DEFAULT 'NR',
  `si_adm_4` varchar(5) DEFAULT 'NR',
  `si_adm_5` varchar(5) DEFAULT 'NR',
  `si_adm_6` varchar(5) DEFAULT 'NR',
  `si_gsa_1` varchar(5) DEFAULT 'NR',
  `si_gsa_2` varchar(5) DEFAULT 'NR',
  `si_gsa_3` varchar(5) DEFAULT 'NR',
  `si_gsa_4` varchar(5) DEFAULT 'NR',
  `si_gsa_5` varchar(5) DEFAULT 'NR',
  `si_gsa_6` varchar(5) DEFAULT 'NR',
  `si_gsa_7` varchar(5) DEFAULT 'NR',
  `si_gsa_8` varchar(5) DEFAULT 'NR',
  `si_gsa_9` varchar(5) DEFAULT 'NR',
  `si_gsa_10` varchar(5) DEFAULT 'NR',
  `si_gsa_11` varchar(5) DEFAULT 'NR',
  `si_gsa_12` varchar(5) DEFAULT 'NR',
  `si_gsa_13` varchar(5) DEFAULT 'NR',
  `si_gsa_14` varchar(5) DEFAULT 'NR',
  `si_sst_1` varchar(5) DEFAULT 'NR',
  `si_sst_2` varchar(5) DEFAULT 'NR',
  `si_sst_3` varchar(5) DEFAULT 'NR',
  `si_sst_4` varchar(5) DEFAULT 'NR',
  `si_sst_5` varchar(5) DEFAULT 'NR',
  `si_sst_6` varchar(5) DEFAULT 'NR',
  `si_sst_7` varchar(5) DEFAULT 'NR',
  `si_sst_8` varchar(5) DEFAULT 'NR',
  `si_sst_9` varchar(5) DEFAULT 'NR',
  `si_sst_10` varchar(5) DEFAULT 'NR'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `accesibilidadMetroA_pdc`
--

CREATE TABLE `accesibilidadMetroA_pdc` (
  `consecutivo` int NOT NULL,
  `semana` int NOT NULL,
  `titulo` int NOT NULL,
  `tipoPaquete` varchar(200) NOT NULL,
  `paqueteContratacion` varchar(200) DEFAULT NULL,
  `contratos` varchar(200) DEFAULT NULL,
  `numeroSubcontratos` int DEFAULT '1',
  `subcontratoPaquete` int NOT NULL DEFAULT '1',
  `estado` varchar(200) DEFAULT NULL,
  `fechaElaboracionPliegos` date DEFAULT NULL,
  `diasElaboracionPliegos` int DEFAULT NULL,
  `fechaRealElaboracionPliegos` date DEFAULT NULL,
  `fechaIngresoLicify` date DEFAULT NULL,
  `diasIngresoLicify` int DEFAULT NULL,
  `fechaRealIngresoLicify` date DEFAULT NULL,
  `fechaEntregaPliegos` date DEFAULT NULL,
  `diasEntregaPliegos` int DEFAULT NULL,
  `fechaRealEntregaPliegos` date DEFAULT NULL,
  `fechaReciboPropuestas` date DEFAULT NULL,
  `diasReciboPropuestas` int DEFAULT NULL,
  `fechaRealReciboPropuestas` date DEFAULT NULL,
  `fechaCuadrosComparativos` date DEFAULT NULL,
  `diasCuadrosComparativos` int DEFAULT NULL,
  `fechaRealCuadrosComparativos` date DEFAULT NULL,
  `fechaLegalizacionContrato` date DEFAULT NULL,
  `diasLegalizacionContrato` int DEFAULT NULL,
  `fechaRealLegalizacionContrato` date DEFAULT NULL,
  `fechaFabricacion` date DEFAULT NULL,
  `diasFabricacion` int DEFAULT NULL,
  `fechaRealFabricacion` date DEFAULT NULL,
  `fechaInsumosObra` date DEFAULT NULL,
  `diasInsumosObra` int DEFAULT NULL,
  `fechaRealInsumosObra` date DEFAULT NULL,
  `fechaInicio` date DEFAULT NULL,
  `fechaInicioProyectada` date DEFAULT NULL,
  `fechaRealInicio` date DEFAULT NULL,
  `idProveedorAdjudicado` int DEFAULT NULL,
  `numeroContrato` varchar(50) DEFAULT NULL,
  `aplicaPolizas` int NOT NULL DEFAULT '1',
  `fechaVencimientoPolizas` date DEFAULT NULL,
  `valorPresupuesto` float DEFAULT NULL,
  `valorPrimeraNegociacion` float DEFAULT NULL,
  `valorAdjudicado` float DEFAULT NULL,
  `valorAnticipo` float DEFAULT NULL,
  `valorReclamado` float DEFAULT NULL,
  `valorDevoluciones` float DEFAULT NULL,
  `observacionesContrato` mediumtext
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `accesibilidadMetroA_pi_shared_constraints`
--

CREATE TABLE `accesibilidadMetroA_pi_shared_constraints` (
  `Id` bigint UNSIGNED NOT NULL,
  `Semana` int NOT NULL,
  `Restriccion` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `ValorObjetivo` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `Nota` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `CreadoPor` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `CreadoEn` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ActualizadoEn` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `accesibilidadMetroA_pi_shared_constraint_links`
--

CREATE TABLE `accesibilidadMetroA_pi_shared_constraint_links` (
  `Id` bigint UNSIGNED NOT NULL,
  `SharedConstraintId` bigint UNSIGNED NOT NULL,
  `Semana` int NOT NULL,
  `ConsecutivoEnPrograma` bigint NOT NULL,
  `ValorAplicado` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `OverrideLocal` tinyint(1) NOT NULL DEFAULT '0',
  `AplicadoEn` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `accesibilidadMetroA_profesionales`
--

CREATE TABLE `accesibilidadMetroA_profesionales` (
  `id` int NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `cargo` varchar(100) NOT NULL,
  `activo` int NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `accesibilidadMetroA_programa`
--

CREATE TABLE `accesibilidadMetroA_programa` (
  `Consecutivo` int NOT NULL,
  `Id` varchar(500) DEFAULT NULL,
  `Actividad` varchar(500) DEFAULT NULL,
  `Titulo` int DEFAULT NULL,
  `Fecha_Inicio` date DEFAULT NULL,
  `Fecha_Fin` date DEFAULT NULL,
  `Ruta_Critica` int DEFAULT NULL,
  `Ejecutado` float DEFAULT '0',
  `Estado` varchar(50) DEFAULT NULL,
  `Semanas_Inicio` int DEFAULT '0',
  `Estado_Restricciones` float DEFAULT '0',
  `D_y_E` float DEFAULT '0',
  `Materiales` float DEFAULT '0',
  `MdeO` float DEFAULT '0',
  `Equipos` float DEFAULT '0',
  `Predecesora` float DEFAULT '0',
  `Pdto_Cons` float DEFAULT '0',
  `Modelo` varchar(9) DEFAULT '0',
  `Responsable_AIA` varchar(100) DEFAULT NULL,
  `Observaciones` mediumtext,
  `Ult_Act_Est` date DEFAULT NULL,
  `Ult_Act_Restr` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `accesibilidadMetroA_programacion_semanal`
--

CREATE TABLE `accesibilidadMetroA_programacion_semanal` (
  `Consecutivo` int NOT NULL,
  `Semana` int DEFAULT NULL,
  `Consecutivo_En_Programa` int NOT NULL,
  `Id` varchar(500) DEFAULT NULL,
  `Actividad` varchar(500) DEFAULT NULL,
  `Descripcion` mediumtext,
  `Ubicacion` mediumtext,
  `Fecha_Inicio` date DEFAULT NULL,
  `Fecha_Fin` date DEFAULT NULL,
  `Sub_Contratista` varchar(200) DEFAULT NULL,
  `Responsable_AIA` varchar(200) DEFAULT NULL,
  `Empresa` varchar(200) NOT NULL DEFAULT 'AIA',
  `Ejecutado` float DEFAULT NULL,
  `medir_productividad` int DEFAULT '0',
  `Unidad` varchar(10) DEFAULT NULL,
  `cantidad_ppto` int DEFAULT NULL,
  `Cantidad_Sugerida` float DEFAULT NULL,
  `Compromiso` float DEFAULT NULL,
  `Ejecutado_Real` float DEFAULT NULL,
  `P_Completado` float DEFAULT NULL,
  `PAC` int DEFAULT NULL,
  `Critica` int DEFAULT NULL,
  `Atrasada` int DEFAULT NULL,
  `Activa` varchar(3) DEFAULT NULL,
  `Prog_Sin_Restricciones_100` int DEFAULT NULL,
  `Categoria_CNP` varchar(100) DEFAULT NULL,
  `CNP` varchar(100) DEFAULT NULL,
  `Observaciones_CNP` mediumtext,
  `Categoria_CNC` varchar(100) DEFAULT NULL,
  `CNC` varchar(100) DEFAULT NULL,
  `Observaciones_CNC` mediumtext,
  `Rendimientos` varchar(500) DEFAULT NULL,
  `codigo_actividad` varchar(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `accesibilidadMetroA_programa_consolidado`
--

CREATE TABLE `accesibilidadMetroA_programa_consolidado` (
  `Consecutivo` int NOT NULL,
  `Semana` int NOT NULL,
  `Consecutivo_en_Programa` int NOT NULL,
  `Id` varchar(500) DEFAULT NULL,
  `Actividad` varchar(500) DEFAULT NULL,
  `Titulo` int DEFAULT NULL,
  `Fecha_Inicio` date DEFAULT NULL,
  `Fecha_Fin` date DEFAULT NULL,
  `Ruta_Critica` int DEFAULT NULL,
  `Ejecutado` float DEFAULT '0',
  `Estado` varchar(100) DEFAULT NULL,
  `Semanas_Inicio` int DEFAULT '0',
  `Estado_Restricciones` float NOT NULL DEFAULT '0',
  `D_y_E` varchar(9) NOT NULL DEFAULT '0',
  `Materiales` varchar(9) NOT NULL DEFAULT '0',
  `MdeO` varchar(9) NOT NULL DEFAULT '0',
  `Equipos` varchar(9) NOT NULL DEFAULT '0',
  `Predecesora` varchar(9) NOT NULL DEFAULT '0',
  `Pdto_Cons` varchar(9) NOT NULL DEFAULT '0',
  `Modelo` varchar(9) NOT NULL DEFAULT '0',
  `Sub_Contratista` varchar(100) DEFAULT NULL,
  `Responsable_AIA` varchar(100) DEFAULT NULL,
  `Observaciones` mediumtext,
  `Ult_Act_Est` date DEFAULT NULL,
  `Ult_Act_Restr` date DEFAULT NULL,
  `Activa` int NOT NULL DEFAULT '0',
  `Ejecutado_Siguiente_Semana` float DEFAULT NULL,
  `codigo_actividad` varchar(11) DEFAULT NULL,
  `medir_productividad` int DEFAULT '0',
  `cantidad_ppto` int DEFAULT NULL,
  `unidad` varchar(20) DEFAULT NULL,
  `programaAnteriorAsociar` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `accesibilidadMetroA_semanas_activas`
--

CREATE TABLE `accesibilidadMetroA_semanas_activas` (
  `Id` int NOT NULL,
  `Semana` int NOT NULL,
  `Fecha_Inicio_Sem` date NOT NULL,
  `Fecha_Fin_Sem` date NOT NULL,
  `Semanal_Confirmada` int DEFAULT '0',
  `fechaCierreCompromisos` date DEFAULT NULL,
  `fechaCreacionSemana` date DEFAULT NULL,
  `reprogramacion` int NOT NULL DEFAULT '0',
  `diferenciaEstructuraCron` int NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `accesibilidadMetroA_subcontratistas`
--

CREATE TABLE `accesibilidadMetroA_subcontratistas` (
  `Id` int NOT NULL,
  `subcontratista` varchar(200) NOT NULL,
  `correo_contacto` varchar(200) NOT NULL,
  `NIT` bigint NOT NULL,
  `alcance` varchar(200) NOT NULL,
  `tipo_proveedor` varchar(200) NOT NULL,
  `activo` int NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `accesibilidadMetroB_actividades`
--

CREATE TABLE `accesibilidadMetroB_actividades` (
  `Id` int NOT NULL,
  `codigo` int NOT NULL,
  `actividad` varchar(300) NOT NULL,
  `descripcionActividad` mediumtext,
  `actividadInicio` varchar(500) DEFAULT NULL,
  `nombreActividadInicio` varchar(500) DEFAULT NULL,
  `fechaInicio` date DEFAULT NULL,
  `tipoContrato` varchar(10) DEFAULT NULL,
  `semanaActualizacion` int DEFAULT NULL,
  `SI1` varchar(200) DEFAULT NULL,
  `paqueteSI1` varchar(200) DEFAULT NULL,
  `SI2` varchar(200) DEFAULT NULL,
  `paqueteSI2` varchar(200) DEFAULT NULL,
  `SI3` varchar(200) DEFAULT NULL,
  `paqueteSI3` varchar(200) DEFAULT NULL,
  `SI4` varchar(200) DEFAULT NULL,
  `paqueteSI4` varchar(200) DEFAULT NULL,
  `SI5` varchar(200) DEFAULT NULL,
  `paqueteSI5` varchar(200) DEFAULT NULL,
  `S1` varchar(200) DEFAULT NULL,
  `paqueteS1` varchar(200) DEFAULT NULL,
  `S2` varchar(200) DEFAULT NULL,
  `paqueteS2` varchar(200) DEFAULT NULL,
  `S3` varchar(200) DEFAULT NULL,
  `paqueteS3` varchar(200) DEFAULT NULL,
  `S4` varchar(200) DEFAULT NULL,
  `paqueteS4` varchar(200) DEFAULT NULL,
  `S5` varchar(200) DEFAULT NULL,
  `paqueteS5` varchar(200) DEFAULT NULL,
  `MO1` varchar(200) DEFAULT NULL,
  `paqueteMO1` varchar(200) DEFAULT NULL,
  `MO2` varchar(200) DEFAULT NULL,
  `paqueteMO2` varchar(200) DEFAULT NULL,
  `MO3` varchar(200) DEFAULT NULL,
  `paqueteMO3` varchar(200) DEFAULT NULL,
  `MO4` varchar(200) DEFAULT NULL,
  `paqueteMO4` varchar(200) DEFAULT NULL,
  `MO5` varchar(200) DEFAULT NULL,
  `paqueteMO5` varchar(200) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `accesibilidadMetroB_cambios`
--

CREATE TABLE `accesibilidadMetroB_cambios` (
  `id` int NOT NULL,
  `solicitanteCambio` int DEFAULT NULL,
  `detalleSolicitanteOtro` longtext,
  `fechaSolicitud` date DEFAULT NULL,
  `prioridad` int DEFAULT NULL,
  `tipoCambio` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `responsableSolucion` int DEFAULT NULL,
  `detalleResponsableSolucion` longtext,
  `justificacion` longtext,
  `descripcion` longtext,
  `incidenciaAlcance` longtext,
  `tiempoCronograma` float DEFAULT NULL,
  `tiempoCronogramaAfectado` float DEFAULT NULL,
  `incidenciaCronograma` longtext,
  `valorPresupuesto` float DEFAULT NULL,
  `costoDirecto` float DEFAULT NULL,
  `costoDirectoAIU` float DEFAULT NULL,
  `costoDirectoAIUIVA` float DEFAULT NULL,
  `valorAprobado` float DEFAULT NULL,
  `incidenciaPresupuesto` longtext,
  `incidenciaCalidad` longtext,
  `incidenciaRiesgo` longtext,
  `incidenciaRecurso` longtext,
  `fechaTentativaDefinicion` date DEFAULT NULL,
  `fechaEntregaInterventoria` date DEFAULT NULL,
  `Observaciones` longtext,
  `fechaDefinicion` date DEFAULT NULL,
  `aprobacion` int DEFAULT NULL,
  `soportes` longtext
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `accesibilidadMetroB_cic`
--

CREATE TABLE `accesibilidadMetroB_cic` (
  `Id` int NOT NULL,
  `Semana` int DEFAULT NULL,
  `subcontratista` varchar(200) DEFAULT NULL,
  `correo_contacto` varchar(200) DEFAULT NULL,
  `NIT` varchar(10) DEFAULT NULL,
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
  `Observaciones` mediumtext,
  `mdo_cal_1` varchar(5) DEFAULT 'NR',
  `mdo_cal_2` varchar(5) DEFAULT 'NR',
  `mdo_cal_3` varchar(5) DEFAULT 'NR',
  `mdo_adm_1` varchar(5) DEFAULT 'NR',
  `mdo_adm_2` varchar(5) DEFAULT 'NR',
  `mdo_adm_3` varchar(5) DEFAULT 'NR',
  `mdo_adm_4` varchar(5) DEFAULT 'NR',
  `mdo_adm_5` varchar(5) DEFAULT 'NR',
  `mdo_gsa_1` varchar(5) DEFAULT 'NR',
  `mdo_gsa_2` varchar(5) DEFAULT 'NR',
  `mdo_gsa_3` varchar(5) DEFAULT 'NR',
  `mdo_gsa_4` varchar(5) DEFAULT 'NR',
  `mdo_gsa_5` varchar(5) DEFAULT 'NR',
  `mdo_gsa_6` varchar(5) DEFAULT 'NR',
  `mdo_gsa_7` varchar(5) DEFAULT 'NR',
  `mdo_gsa_8` varchar(5) DEFAULT 'NR',
  `mdo_sst_1` varchar(5) DEFAULT 'NR',
  `mdo_sst_2` varchar(5) DEFAULT 'NR',
  `mdo_sst_3` varchar(5) DEFAULT 'NR',
  `mdo_sst_4` varchar(5) DEFAULT 'NR',
  `mdo_sst_5` varchar(5) DEFAULT 'NR',
  `mdo_sst_6` varchar(5) DEFAULT 'NR',
  `mdo_sst_7` varchar(5) DEFAULT 'NR',
  `mdo_sst_8` varchar(5) DEFAULT 'NR',
  `mdo_sst_9` varchar(5) DEFAULT 'NR',
  `mdo_sst_10` varchar(5) DEFAULT 'NR',
  `si_cal_1` varchar(5) DEFAULT 'NR',
  `si_cal_2` varchar(5) DEFAULT 'NR',
  `si_cal_3` varchar(5) DEFAULT 'NR',
  `si_adm_1` varchar(5) DEFAULT 'NR',
  `si_adm_2` varchar(5) DEFAULT 'NR',
  `si_adm_3` varchar(5) DEFAULT 'NR',
  `si_adm_4` varchar(5) DEFAULT 'NR',
  `si_adm_5` varchar(5) DEFAULT 'NR',
  `si_adm_6` varchar(5) DEFAULT 'NR',
  `si_gsa_1` varchar(5) DEFAULT 'NR',
  `si_gsa_2` varchar(5) DEFAULT 'NR',
  `si_gsa_3` varchar(5) DEFAULT 'NR',
  `si_gsa_4` varchar(5) DEFAULT 'NR',
  `si_gsa_5` varchar(5) DEFAULT 'NR',
  `si_gsa_6` varchar(5) DEFAULT 'NR',
  `si_gsa_7` varchar(5) DEFAULT 'NR',
  `si_gsa_8` varchar(5) DEFAULT 'NR',
  `si_gsa_9` varchar(5) DEFAULT 'NR',
  `si_gsa_10` varchar(5) DEFAULT 'NR',
  `si_gsa_11` varchar(5) DEFAULT 'NR',
  `si_gsa_12` varchar(5) DEFAULT 'NR',
  `si_gsa_13` varchar(5) DEFAULT 'NR',
  `si_gsa_14` varchar(5) DEFAULT 'NR',
  `si_sst_1` varchar(5) DEFAULT 'NR',
  `si_sst_2` varchar(5) DEFAULT 'NR',
  `si_sst_3` varchar(5) DEFAULT 'NR',
  `si_sst_4` varchar(5) DEFAULT 'NR',
  `si_sst_5` varchar(5) DEFAULT 'NR',
  `si_sst_6` varchar(5) DEFAULT 'NR',
  `si_sst_7` varchar(5) DEFAULT 'NR',
  `si_sst_8` varchar(5) DEFAULT 'NR',
  `si_sst_9` varchar(5) DEFAULT 'NR',
  `si_sst_10` varchar(5) DEFAULT 'NR'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `accesibilidadMetroB_pdc`
--

CREATE TABLE `accesibilidadMetroB_pdc` (
  `consecutivo` int NOT NULL,
  `semana` int NOT NULL,
  `titulo` int NOT NULL,
  `tipoPaquete` varchar(200) NOT NULL,
  `paqueteContratacion` varchar(200) DEFAULT NULL,
  `contratos` varchar(200) DEFAULT NULL,
  `numeroSubcontratos` int DEFAULT '1',
  `subcontratoPaquete` int NOT NULL DEFAULT '1',
  `estado` varchar(200) DEFAULT NULL,
  `fechaElaboracionPliegos` date DEFAULT NULL,
  `diasElaboracionPliegos` int DEFAULT NULL,
  `fechaRealElaboracionPliegos` date DEFAULT NULL,
  `fechaIngresoLicify` date DEFAULT NULL,
  `diasIngresoLicify` int DEFAULT NULL,
  `fechaRealIngresoLicify` date DEFAULT NULL,
  `fechaEntregaPliegos` date DEFAULT NULL,
  `diasEntregaPliegos` int DEFAULT NULL,
  `fechaRealEntregaPliegos` date DEFAULT NULL,
  `fechaReciboPropuestas` date DEFAULT NULL,
  `diasReciboPropuestas` int DEFAULT NULL,
  `fechaRealReciboPropuestas` date DEFAULT NULL,
  `fechaCuadrosComparativos` date DEFAULT NULL,
  `diasCuadrosComparativos` int DEFAULT NULL,
  `fechaRealCuadrosComparativos` date DEFAULT NULL,
  `fechaLegalizacionContrato` date DEFAULT NULL,
  `diasLegalizacionContrato` int DEFAULT NULL,
  `fechaRealLegalizacionContrato` date DEFAULT NULL,
  `fechaFabricacion` date DEFAULT NULL,
  `diasFabricacion` int DEFAULT NULL,
  `fechaRealFabricacion` date DEFAULT NULL,
  `fechaInsumosObra` date DEFAULT NULL,
  `diasInsumosObra` int DEFAULT NULL,
  `fechaRealInsumosObra` date DEFAULT NULL,
  `fechaInicio` date DEFAULT NULL,
  `fechaInicioProyectada` date DEFAULT NULL,
  `fechaRealInicio` date DEFAULT NULL,
  `idProveedorAdjudicado` int DEFAULT NULL,
  `numeroContrato` varchar(50) DEFAULT NULL,
  `aplicaPolizas` int NOT NULL DEFAULT '1',
  `fechaVencimientoPolizas` date DEFAULT NULL,
  `valorPresupuesto` float DEFAULT NULL,
  `valorPrimeraNegociacion` float DEFAULT NULL,
  `valorAdjudicado` float DEFAULT NULL,
  `valorAnticipo` float DEFAULT NULL,
  `valorReclamado` float DEFAULT NULL,
  `valorDevoluciones` float DEFAULT NULL,
  `observacionesContrato` mediumtext
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `accesibilidadMetroB_pi_shared_constraints`
--

CREATE TABLE `accesibilidadMetroB_pi_shared_constraints` (
  `Id` bigint UNSIGNED NOT NULL,
  `Semana` int NOT NULL,
  `Restriccion` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `ValorObjetivo` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `Nota` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `CreadoPor` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `CreadoEn` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ActualizadoEn` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `accesibilidadMetroB_pi_shared_constraint_links`
--

CREATE TABLE `accesibilidadMetroB_pi_shared_constraint_links` (
  `Id` bigint UNSIGNED NOT NULL,
  `SharedConstraintId` bigint UNSIGNED NOT NULL,
  `Semana` int NOT NULL,
  `ConsecutivoEnPrograma` bigint NOT NULL,
  `ValorAplicado` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `OverrideLocal` tinyint(1) NOT NULL DEFAULT '0',
  `AplicadoEn` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `accesibilidadMetroB_profesionales`
--

CREATE TABLE `accesibilidadMetroB_profesionales` (
  `id` int NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `cargo` varchar(100) NOT NULL,
  `activo` int NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `accesibilidadMetroB_programa`
--

CREATE TABLE `accesibilidadMetroB_programa` (
  `Consecutivo` int NOT NULL,
  `Id` varchar(500) DEFAULT NULL,
  `Actividad` varchar(500) DEFAULT NULL,
  `Titulo` int DEFAULT NULL,
  `Fecha_Inicio` date DEFAULT NULL,
  `Fecha_Fin` date DEFAULT NULL,
  `Ruta_Critica` int DEFAULT NULL,
  `Ejecutado` float DEFAULT '0',
  `Estado` varchar(50) DEFAULT NULL,
  `Semanas_Inicio` int DEFAULT '0',
  `Estado_Restricciones` float DEFAULT '0',
  `D_y_E` float DEFAULT '0',
  `Materiales` float DEFAULT '0',
  `MdeO` float DEFAULT '0',
  `Equipos` float DEFAULT '0',
  `Predecesora` float DEFAULT '0',
  `Pdto_Cons` float DEFAULT '0',
  `Modelo` varchar(9) DEFAULT '0',
  `Responsable_AIA` varchar(100) DEFAULT NULL,
  `Observaciones` mediumtext,
  `Ult_Act_Est` date DEFAULT NULL,
  `Ult_Act_Restr` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `accesibilidadMetroB_programacion_semanal`
--

CREATE TABLE `accesibilidadMetroB_programacion_semanal` (
  `Consecutivo` int NOT NULL,
  `Semana` int DEFAULT NULL,
  `Consecutivo_En_Programa` int NOT NULL,
  `Id` varchar(500) DEFAULT NULL,
  `Actividad` varchar(500) DEFAULT NULL,
  `Descripcion` mediumtext,
  `Ubicacion` mediumtext,
  `Fecha_Inicio` date DEFAULT NULL,
  `Fecha_Fin` date DEFAULT NULL,
  `Sub_Contratista` varchar(200) DEFAULT NULL,
  `Responsable_AIA` varchar(200) DEFAULT NULL,
  `Empresa` varchar(200) NOT NULL DEFAULT 'AIA',
  `Ejecutado` float DEFAULT NULL,
  `medir_productividad` int DEFAULT '0',
  `Unidad` varchar(10) DEFAULT NULL,
  `cantidad_ppto` int DEFAULT NULL,
  `Cantidad_Sugerida` float DEFAULT NULL,
  `Compromiso` float DEFAULT NULL,
  `Ejecutado_Real` float DEFAULT NULL,
  `P_Completado` float DEFAULT NULL,
  `PAC` int DEFAULT NULL,
  `Critica` int DEFAULT NULL,
  `Atrasada` int DEFAULT NULL,
  `Activa` varchar(3) DEFAULT NULL,
  `Prog_Sin_Restricciones_100` int DEFAULT NULL,
  `Categoria_CNP` varchar(100) DEFAULT NULL,
  `CNP` varchar(100) DEFAULT NULL,
  `Observaciones_CNP` mediumtext,
  `Categoria_CNC` varchar(100) DEFAULT NULL,
  `CNC` varchar(100) DEFAULT NULL,
  `Observaciones_CNC` mediumtext,
  `Rendimientos` varchar(500) DEFAULT NULL,
  `codigo_actividad` varchar(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `accesibilidadMetroB_programa_consolidado`
--

CREATE TABLE `accesibilidadMetroB_programa_consolidado` (
  `Consecutivo` int NOT NULL,
  `Semana` int NOT NULL,
  `Consecutivo_en_Programa` int NOT NULL,
  `Id` varchar(500) DEFAULT NULL,
  `Actividad` varchar(500) DEFAULT NULL,
  `Titulo` int DEFAULT NULL,
  `Fecha_Inicio` date DEFAULT NULL,
  `Fecha_Fin` date DEFAULT NULL,
  `Ruta_Critica` int DEFAULT NULL,
  `Ejecutado` float DEFAULT '0',
  `Estado` varchar(100) DEFAULT NULL,
  `Semanas_Inicio` int DEFAULT '0',
  `Estado_Restricciones` float NOT NULL DEFAULT '0',
  `D_y_E` varchar(9) NOT NULL DEFAULT '0',
  `Materiales` varchar(9) NOT NULL DEFAULT '0',
  `MdeO` varchar(9) NOT NULL DEFAULT '0',
  `Equipos` varchar(9) NOT NULL DEFAULT '0',
  `Predecesora` varchar(9) NOT NULL DEFAULT '0',
  `Pdto_Cons` varchar(9) NOT NULL DEFAULT '0',
  `Modelo` varchar(9) NOT NULL DEFAULT '0',
  `Sub_Contratista` varchar(100) DEFAULT NULL,
  `Responsable_AIA` varchar(100) DEFAULT NULL,
  `Observaciones` mediumtext,
  `Ult_Act_Est` date DEFAULT NULL,
  `Ult_Act_Restr` date DEFAULT NULL,
  `Activa` int NOT NULL DEFAULT '0',
  `Ejecutado_Siguiente_Semana` float DEFAULT NULL,
  `codigo_actividad` varchar(11) DEFAULT NULL,
  `medir_productividad` int DEFAULT '0',
  `cantidad_ppto` int DEFAULT NULL,
  `unidad` varchar(20) DEFAULT NULL,
  `programaAnteriorAsociar` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `accesibilidadMetroB_semanas_activas`
--

CREATE TABLE `accesibilidadMetroB_semanas_activas` (
  `Id` int NOT NULL,
  `Semana` int NOT NULL,
  `Fecha_Inicio_Sem` date NOT NULL,
  `Fecha_Fin_Sem` date NOT NULL,
  `Semanal_Confirmada` int DEFAULT '0',
  `fechaCierreCompromisos` date DEFAULT NULL,
  `fechaCreacionSemana` date DEFAULT NULL,
  `reprogramacion` int NOT NULL DEFAULT '0',
  `diferenciaEstructuraCron` int NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `accesibilidadMetroB_subcontratistas`
--

CREATE TABLE `accesibilidadMetroB_subcontratistas` (
  `Id` int NOT NULL,
  `subcontratista` varchar(200) NOT NULL,
  `correo_contacto` varchar(200) NOT NULL,
  `NIT` bigint NOT NULL,
  `alcance` varchar(200) NOT NULL,
  `tipo_proveedor` varchar(200) NOT NULL,
  `activo` int NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `da_porto_actividades`
--

CREATE TABLE `da_porto_actividades` (
  `Id` int NOT NULL,
  `codigo` int NOT NULL,
  `actividad` varchar(300) NOT NULL,
  `descripcionActividad` mediumtext,
  `actividadInicio` varchar(500) DEFAULT NULL,
  `nombreActividadInicio` varchar(500) DEFAULT NULL,
  `fechaInicio` date DEFAULT NULL,
  `tipoContrato` varchar(10) DEFAULT NULL,
  `semanaActualizacion` int DEFAULT NULL,
  `SI1` varchar(200) DEFAULT NULL,
  `paqueteSI1` varchar(200) DEFAULT NULL,
  `SI2` varchar(200) DEFAULT NULL,
  `paqueteSI2` varchar(200) DEFAULT NULL,
  `SI3` varchar(200) DEFAULT NULL,
  `paqueteSI3` varchar(200) DEFAULT NULL,
  `SI4` varchar(200) DEFAULT NULL,
  `paqueteSI4` varchar(200) DEFAULT NULL,
  `SI5` varchar(200) DEFAULT NULL,
  `paqueteSI5` varchar(200) DEFAULT NULL,
  `S1` varchar(200) DEFAULT NULL,
  `paqueteS1` varchar(200) DEFAULT NULL,
  `S2` varchar(200) DEFAULT NULL,
  `paqueteS2` varchar(200) DEFAULT NULL,
  `S3` varchar(200) DEFAULT NULL,
  `paqueteS3` varchar(200) DEFAULT NULL,
  `S4` varchar(200) DEFAULT NULL,
  `paqueteS4` varchar(200) DEFAULT NULL,
  `S5` varchar(200) DEFAULT NULL,
  `paqueteS5` varchar(200) DEFAULT NULL,
  `MO1` varchar(200) DEFAULT NULL,
  `paqueteMO1` varchar(200) DEFAULT NULL,
  `MO2` varchar(200) DEFAULT NULL,
  `paqueteMO2` varchar(200) DEFAULT NULL,
  `MO3` varchar(200) DEFAULT NULL,
  `paqueteMO3` varchar(200) DEFAULT NULL,
  `MO4` varchar(200) DEFAULT NULL,
  `paqueteMO4` varchar(200) DEFAULT NULL,
  `MO5` varchar(200) DEFAULT NULL,
  `paqueteMO5` varchar(200) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `da_porto_cambios`
--

CREATE TABLE `da_porto_cambios` (
  `id` int NOT NULL,
  `solicitanteCambio` int DEFAULT NULL,
  `detalleSolicitanteOtro` longtext,
  `fechaSolicitud` date DEFAULT NULL,
  `prioridad` int DEFAULT NULL,
  `tipoCambio` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `responsableSolucion` int DEFAULT NULL,
  `detalleResponsableSolucion` longtext,
  `justificacion` longtext,
  `descripcion` longtext,
  `incidenciaAlcance` longtext,
  `tiempoCronograma` float DEFAULT NULL,
  `tiempoCronogramaAfectado` float DEFAULT NULL,
  `incidenciaCronograma` longtext,
  `valorPresupuesto` float DEFAULT NULL,
  `costoDirecto` float DEFAULT NULL,
  `costoDirectoAIU` float DEFAULT NULL,
  `costoDirectoAIUIVA` float DEFAULT NULL,
  `valorAprobado` float DEFAULT NULL,
  `incidenciaPresupuesto` longtext,
  `incidenciaCalidad` longtext,
  `incidenciaRiesgo` longtext,
  `incidenciaRecurso` longtext,
  `fechaTentativaDefinicion` date DEFAULT NULL,
  `fechaEntregaInterventoria` date DEFAULT NULL,
  `Observaciones` longtext,
  `fechaDefinicion` date DEFAULT NULL,
  `aprobacion` int DEFAULT NULL,
  `soportes` longtext
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `da_porto_cic`
--

CREATE TABLE `da_porto_cic` (
  `Id` int NOT NULL,
  `Semana` int DEFAULT NULL,
  `subcontratista` varchar(200) DEFAULT NULL,
  `correo_contacto` varchar(200) DEFAULT NULL,
  `NIT` varchar(10) DEFAULT NULL,
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
  `Observaciones` mediumtext,
  `mdo_cal_1` varchar(5) DEFAULT 'NR',
  `mdo_cal_2` varchar(5) DEFAULT 'NR',
  `mdo_cal_3` varchar(5) DEFAULT 'NR',
  `mdo_adm_1` varchar(5) DEFAULT 'NR',
  `mdo_adm_2` varchar(5) DEFAULT 'NR',
  `mdo_adm_3` varchar(5) DEFAULT 'NR',
  `mdo_adm_4` varchar(5) DEFAULT 'NR',
  `mdo_adm_5` varchar(5) DEFAULT 'NR',
  `mdo_gsa_1` varchar(5) DEFAULT 'NR',
  `mdo_gsa_2` varchar(5) DEFAULT 'NR',
  `mdo_gsa_3` varchar(5) DEFAULT 'NR',
  `mdo_gsa_4` varchar(5) DEFAULT 'NR',
  `mdo_gsa_5` varchar(5) DEFAULT 'NR',
  `mdo_gsa_6` varchar(5) DEFAULT 'NR',
  `mdo_gsa_7` varchar(5) DEFAULT 'NR',
  `mdo_gsa_8` varchar(5) DEFAULT 'NR',
  `mdo_sst_1` varchar(5) DEFAULT 'NR',
  `mdo_sst_2` varchar(5) DEFAULT 'NR',
  `mdo_sst_3` varchar(5) DEFAULT 'NR',
  `mdo_sst_4` varchar(5) DEFAULT 'NR',
  `mdo_sst_5` varchar(5) DEFAULT 'NR',
  `mdo_sst_6` varchar(5) DEFAULT 'NR',
  `mdo_sst_7` varchar(5) DEFAULT 'NR',
  `mdo_sst_8` varchar(5) DEFAULT 'NR',
  `mdo_sst_9` varchar(5) DEFAULT 'NR',
  `mdo_sst_10` varchar(5) DEFAULT 'NR',
  `si_cal_1` varchar(5) DEFAULT 'NR',
  `si_cal_2` varchar(5) DEFAULT 'NR',
  `si_cal_3` varchar(5) DEFAULT 'NR',
  `si_adm_1` varchar(5) DEFAULT 'NR',
  `si_adm_2` varchar(5) DEFAULT 'NR',
  `si_adm_3` varchar(5) DEFAULT 'NR',
  `si_adm_4` varchar(5) DEFAULT 'NR',
  `si_adm_5` varchar(5) DEFAULT 'NR',
  `si_adm_6` varchar(5) DEFAULT 'NR',
  `si_gsa_1` varchar(5) DEFAULT 'NR',
  `si_gsa_2` varchar(5) DEFAULT 'NR',
  `si_gsa_3` varchar(5) DEFAULT 'NR',
  `si_gsa_4` varchar(5) DEFAULT 'NR',
  `si_gsa_5` varchar(5) DEFAULT 'NR',
  `si_gsa_6` varchar(5) DEFAULT 'NR',
  `si_gsa_7` varchar(5) DEFAULT 'NR',
  `si_gsa_8` varchar(5) DEFAULT 'NR',
  `si_gsa_9` varchar(5) DEFAULT 'NR',
  `si_gsa_10` varchar(5) DEFAULT 'NR',
  `si_gsa_11` varchar(5) DEFAULT 'NR',
  `si_gsa_12` varchar(5) DEFAULT 'NR',
  `si_gsa_13` varchar(5) DEFAULT 'NR',
  `si_gsa_14` varchar(5) DEFAULT 'NR',
  `si_sst_1` varchar(5) DEFAULT 'NR',
  `si_sst_2` varchar(5) DEFAULT 'NR',
  `si_sst_3` varchar(5) DEFAULT 'NR',
  `si_sst_4` varchar(5) DEFAULT 'NR',
  `si_sst_5` varchar(5) DEFAULT 'NR',
  `si_sst_6` varchar(5) DEFAULT 'NR',
  `si_sst_7` varchar(5) DEFAULT 'NR',
  `si_sst_8` varchar(5) DEFAULT 'NR',
  `si_sst_9` varchar(5) DEFAULT 'NR',
  `si_sst_10` varchar(5) DEFAULT 'NR'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `da_porto_pdc`
--

CREATE TABLE `da_porto_pdc` (
  `consecutivo` int NOT NULL,
  `semana` int NOT NULL,
  `titulo` int NOT NULL,
  `tipoPaquete` varchar(200) NOT NULL,
  `paqueteContratacion` varchar(200) DEFAULT NULL,
  `contratos` varchar(200) DEFAULT NULL,
  `numeroSubcontratos` int DEFAULT '1',
  `subcontratoPaquete` int NOT NULL DEFAULT '1',
  `estado` varchar(200) DEFAULT NULL,
  `fechaElaboracionPliegos` date DEFAULT NULL,
  `diasElaboracionPliegos` int DEFAULT NULL,
  `fechaRealElaboracionPliegos` date DEFAULT NULL,
  `fechaIngresoLicify` date DEFAULT NULL,
  `diasIngresoLicify` int DEFAULT NULL,
  `fechaRealIngresoLicify` date DEFAULT NULL,
  `fechaEntregaPliegos` date DEFAULT NULL,
  `diasEntregaPliegos` int DEFAULT NULL,
  `fechaRealEntregaPliegos` date DEFAULT NULL,
  `fechaReciboPropuestas` date DEFAULT NULL,
  `diasReciboPropuestas` int DEFAULT NULL,
  `fechaRealReciboPropuestas` date DEFAULT NULL,
  `fechaCuadrosComparativos` date DEFAULT NULL,
  `diasCuadrosComparativos` int DEFAULT NULL,
  `fechaRealCuadrosComparativos` date DEFAULT NULL,
  `fechaLegalizacionContrato` date DEFAULT NULL,
  `diasLegalizacionContrato` int DEFAULT NULL,
  `fechaRealLegalizacionContrato` date DEFAULT NULL,
  `fechaFabricacion` date DEFAULT NULL,
  `diasFabricacion` int DEFAULT NULL,
  `fechaRealFabricacion` date DEFAULT NULL,
  `fechaInsumosObra` date DEFAULT NULL,
  `diasInsumosObra` int DEFAULT NULL,
  `fechaRealInsumosObra` date DEFAULT NULL,
  `fechaInicio` date DEFAULT NULL,
  `fechaInicioProyectada` date DEFAULT NULL,
  `fechaRealInicio` date DEFAULT NULL,
  `idProveedorAdjudicado` int DEFAULT NULL,
  `numeroContrato` varchar(50) DEFAULT NULL,
  `aplicaPolizas` int NOT NULL DEFAULT '1',
  `fechaVencimientoPolizas` date DEFAULT NULL,
  `valorPresupuesto` float DEFAULT NULL,
  `valorPrimeraNegociacion` float DEFAULT NULL,
  `valorAdjudicado` float DEFAULT NULL,
  `valorAnticipo` float DEFAULT NULL,
  `valorReclamado` float DEFAULT NULL,
  `valorDevoluciones` float DEFAULT NULL,
  `observacionesContrato` mediumtext
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `da_porto_profesionales`
--

CREATE TABLE `da_porto_profesionales` (
  `id` int NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `cargo` varchar(100) NOT NULL,
  `activo` int NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `da_porto_programa`
--

CREATE TABLE `da_porto_programa` (
  `Consecutivo` int NOT NULL,
  `Id` varchar(500) DEFAULT NULL,
  `Actividad` varchar(500) DEFAULT NULL,
  `Titulo` int DEFAULT NULL,
  `Fecha_Inicio` date DEFAULT NULL,
  `Fecha_Fin` date DEFAULT NULL,
  `Ruta_Critica` int DEFAULT NULL,
  `Ejecutado` float DEFAULT '0',
  `Estado` varchar(50) DEFAULT NULL,
  `Semanas_Inicio` int DEFAULT '0',
  `Estado_Restricciones` float DEFAULT '0',
  `D_y_E` float DEFAULT '0',
  `Materiales` float DEFAULT '0',
  `MdeO` float DEFAULT '0',
  `Equipos` float DEFAULT '0',
  `Predecesora` float DEFAULT '0',
  `Pdto_Cons` float DEFAULT '0',
  `Modelo` varchar(9) DEFAULT '0',
  `Responsable_AIA` varchar(100) DEFAULT NULL,
  `Observaciones` mediumtext,
  `Ult_Act_Est` date DEFAULT NULL,
  `Ult_Act_Restr` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `da_porto_programacion_semanal`
--

CREATE TABLE `da_porto_programacion_semanal` (
  `Consecutivo` int NOT NULL,
  `Semana` int DEFAULT NULL,
  `Consecutivo_En_Programa` int NOT NULL,
  `Id` varchar(500) DEFAULT NULL,
  `Actividad` varchar(500) DEFAULT NULL,
  `Descripcion` mediumtext,
  `Ubicacion` mediumtext,
  `Fecha_Inicio` date DEFAULT NULL,
  `Fecha_Fin` date DEFAULT NULL,
  `Sub_Contratista` varchar(200) DEFAULT NULL,
  `Responsable_AIA` varchar(200) DEFAULT NULL,
  `Empresa` varchar(200) NOT NULL DEFAULT 'AIA',
  `Ejecutado` float DEFAULT NULL,
  `medir_productividad` int DEFAULT '0',
  `Unidad` varchar(10) DEFAULT NULL,
  `cantidad_ppto` int DEFAULT NULL,
  `Cantidad_Sugerida` float DEFAULT NULL,
  `Compromiso` float DEFAULT NULL,
  `Ejecutado_Real` float DEFAULT NULL,
  `P_Completado` float DEFAULT NULL,
  `PAC` int DEFAULT NULL,
  `Critica` int DEFAULT NULL,
  `Atrasada` int DEFAULT NULL,
  `Activa` varchar(3) DEFAULT NULL,
  `Prog_Sin_Restricciones_100` int DEFAULT NULL,
  `Categoria_CNP` varchar(100) DEFAULT NULL,
  `CNP` varchar(100) DEFAULT NULL,
  `Observaciones_CNP` mediumtext,
  `Categoria_CNC` varchar(100) DEFAULT NULL,
  `CNC` varchar(100) DEFAULT NULL,
  `Observaciones_CNC` mediumtext,
  `Rendimientos` varchar(500) DEFAULT NULL,
  `codigo_actividad` varchar(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `da_porto_programa_consolidado`
--

CREATE TABLE `da_porto_programa_consolidado` (
  `Consecutivo` int NOT NULL,
  `Semana` int NOT NULL,
  `Consecutivo_en_Programa` int NOT NULL,
  `Id` varchar(500) DEFAULT NULL,
  `Actividad` varchar(500) DEFAULT NULL,
  `Titulo` int DEFAULT NULL,
  `Fecha_Inicio` date DEFAULT NULL,
  `Fecha_Fin` date DEFAULT NULL,
  `Ruta_Critica` int DEFAULT NULL,
  `Ejecutado` float DEFAULT '0',
  `Estado` varchar(100) DEFAULT NULL,
  `Semanas_Inicio` int DEFAULT '0',
  `Estado_Restricciones` float NOT NULL DEFAULT '0',
  `D_y_E` varchar(9) NOT NULL DEFAULT '0',
  `Materiales` varchar(9) NOT NULL DEFAULT '0',
  `MdeO` varchar(9) NOT NULL DEFAULT '0',
  `Equipos` varchar(9) NOT NULL DEFAULT '0',
  `Predecesora` varchar(9) NOT NULL DEFAULT '0',
  `Pdto_Cons` varchar(9) NOT NULL DEFAULT '0',
  `Modelo` varchar(9) NOT NULL DEFAULT '0',
  `Sub_Contratista` varchar(100) DEFAULT NULL,
  `Responsable_AIA` varchar(100) DEFAULT NULL,
  `Observaciones` mediumtext,
  `Ult_Act_Est` date DEFAULT NULL,
  `Ult_Act_Restr` date DEFAULT NULL,
  `Activa` int NOT NULL DEFAULT '0',
  `Ejecutado_Siguiente_Semana` float DEFAULT NULL,
  `codigo_actividad` varchar(11) DEFAULT NULL,
  `medir_productividad` int DEFAULT '0',
  `cantidad_ppto` int DEFAULT NULL,
  `unidad` varchar(20) DEFAULT NULL,
  `programaAnteriorAsociar` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `da_porto_semanas_activas`
--

CREATE TABLE `da_porto_semanas_activas` (
  `Id` int NOT NULL,
  `Semana` int NOT NULL,
  `Fecha_Inicio_Sem` date NOT NULL,
  `Fecha_Fin_Sem` date NOT NULL,
  `Semanal_Confirmada` int DEFAULT '0',
  `fechaCierreCompromisos` date DEFAULT NULL,
  `fechaCreacionSemana` date DEFAULT NULL,
  `reprogramacion` int NOT NULL DEFAULT '0',
  `diferenciaEstructuraCron` int NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `da_porto_subcontratistas`
--

CREATE TABLE `da_porto_subcontratistas` (
  `Id` int NOT NULL,
  `subcontratista` varchar(200) NOT NULL,
  `correo_contacto` varchar(200) NOT NULL,
  `NIT` bigint NOT NULL,
  `alcance` varchar(200) NOT NULL,
  `tipo_proveedor` varchar(200) NOT NULL,
  `activo` int NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `event_dictionary`
--

CREATE TABLE `event_dictionary` (
  `event_code` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `event_action` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `modulo_legacy` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `accion_legacy` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `severity` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'info',
  `auditable` tinyint(1) NOT NULL DEFAULT '1',
  `notification_code` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `general_auditoria_acciones`
--

CREATE TABLE `general_auditoria_acciones` (
  `id` int NOT NULL,
  `fecha` datetime DEFAULT CURRENT_TIMESTAMP,
  `usuario` varchar(100) DEFAULT NULL,
  `id_sesion` varchar(100) DEFAULT NULL,
  `modulo` varchar(100) DEFAULT NULL,
  `accion` varchar(100) DEFAULT NULL,
  `event_code` varchar(120) DEFAULT NULL,
  `event_action` varchar(80) DEFAULT NULL,
  `event_result` varchar(20) DEFAULT NULL,
  `descripcion` text,
  `context_json` json DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `proyecto` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `general_cnc`
--

CREATE TABLE `general_cnc` (
  `Id` int NOT NULL,
  `Categoria_CNC` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `CNC` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `general_codigos_actividades`
--

CREATE TABLE `general_codigos_actividades` (
  `Id` int NOT NULL,
  `codigo_actividad` varchar(11) NOT NULL,
  `actividad` varchar(200) NOT NULL,
  `unidad` varchar(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `general_costos_cuadrillas`
--

CREATE TABLE `general_costos_cuadrillas` (
  `Id` int NOT NULL,
  `Proyecto` varchar(100) NOT NULL,
  `Costo_Hora_Oficial` float DEFAULT NULL,
  `Costo_Hora_Ayudante` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `general_cuadrillas_tipicas`
--

CREATE TABLE `general_cuadrillas_tipicas` (
  `Id` int NOT NULL,
  `proyecto` varchar(200) NOT NULL,
  `codigo_actividad` varchar(200) NOT NULL,
  `oficiales_tipica` int NOT NULL,
  `ayudantes_tipica` int NOT NULL,
  `rendimiento_tipica` float NOT NULL,
  `numero_cuadrillas_tipicas` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `general_curvas`
--

CREATE TABLE `general_curvas` (
  `id` int NOT NULL,
  `Proyecto` varchar(200) NOT NULL,
  `fInicioProyecto` date NOT NULL,
  `fFinProyecto` date NOT NULL,
  `semana` int NOT NULL,
  `Fecha_Inicio_Sem` date NOT NULL,
  `Fecha_Fin_Sem` date NOT NULL,
  `diasCompletadosReal` float NOT NULL,
  `diasCompletadosTeorico` float NOT NULL,
  `diasCompletadosLineaBase` float NOT NULL,
  `diasTotales` float NOT NULL,
  `diasTotalesLineaBase` float NOT NULL,
  `porcentajeCompletadoReal` float NOT NULL,
  `porcentajeCompletadoTeorico` float NOT NULL,
  `porcentajeCompletadoLineaBase` float NOT NULL,
  `diferenciaPorcentajeCompletadoTeorico` float NOT NULL,
  `diferenciaPorcentajeCompletadoLineaBase` float NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `general_curvas_pdc`
--

CREATE TABLE `general_curvas_pdc` (
  `id` int NOT NULL,
  `Proyecto` varchar(200) NOT NULL,
  `semana` int NOT NULL,
  `Fecha_Inicio_Sem` date NOT NULL,
  `Fecha_Fin_Sem` date NOT NULL,
  `diasCompletadosReal` float DEFAULT NULL,
  `diasCompletadosTeorico` float DEFAULT NULL,
  `diasTotales` float DEFAULT NULL,
  `porcentajeCompletadoReal` float DEFAULT NULL,
  `porcentajeCompletadoTeorico` float DEFAULT NULL,
  `porcentajeCompletadoTeoricoGeneral` float DEFAULT NULL,
  `porcentajeCompletadoRealGeneral` float DEFAULT NULL,
  `diferenciaPorcentajeCompletadoTeorico` float DEFAULT NULL,
  `diferenciaPorcentajeCompletadoTeoricoGeneral` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `general_curvas_pdc_apr`
--

CREATE TABLE `general_curvas_pdc_apr` (
  `id` int NOT NULL,
  `Proyecto` varchar(200) NOT NULL,
  `semana` int NOT NULL,
  `maxSemana` int NOT NULL,
  `Fecha_Inicio_Sem` date NOT NULL,
  `Fecha_Fin_Sem` date NOT NULL,
  `diasCompletadosReal` float NOT NULL,
  `diasCompletadosTeorico` float NOT NULL,
  `diasTotales` float NOT NULL,
  `porcentajeCompletadoReal` float NOT NULL,
  `porcentajeCompletadoTeorico` float NOT NULL,
  `porcentajeCompletadoTeoricoGeneral` float NOT NULL,
  `porcentajeCompletadoRealGeneral` float NOT NULL,
  `diferenciaPorcentajeCompletadoTeorico` float NOT NULL,
  `diferenciaPorcentajeCompletadoTeoricoGeneral` float NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `general_dias_procesos_contratacion`
--

CREATE TABLE `general_dias_procesos_contratacion` (
  `id` int NOT NULL,
  `paqueteContratacion` varchar(500) NOT NULL,
  `tipoPaquete` varchar(200) NOT NULL,
  `diasElaboracionPliegos` int DEFAULT '1',
  `diasIngresoLicify` int DEFAULT '1',
  `diasEntregaPliegos` int DEFAULT '1',
  `diasReciboPropuestas` int DEFAULT '1',
  `diasCuadrosComparativos` int DEFAULT '1',
  `diasLegalizacionContrato` int DEFAULT '1',
  `diasFabricacion` int DEFAULT '1',
  `diasInsumosObra` int DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `general_feature_flags`
--

CREATE TABLE `general_feature_flags` (
  `id` int UNSIGNED NOT NULL,
  `flag_key` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `flag_value` tinyint(1) NOT NULL DEFAULT '0',
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `updated_by` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `general_informe_consolidado`
--

CREATE TABLE `general_informe_consolidado` (
  `id` int NOT NULL,
  `Proyecto` varchar(100) NOT NULL,
  `Semana` int NOT NULL,
  `maxSemana` int NOT NULL,
  `Proyecto_maxSemana` varchar(500) NOT NULL,
  `Actividad` varchar(500) NOT NULL,
  `Fecha_Inicio` date DEFAULT NULL,
  `Fecha_Fin` date DEFAULT NULL,
  `Fecha_Inicio_Sem` date DEFAULT NULL,
  `Fecha_Fin_Sem` date DEFAULT NULL,
  `Critica` int NOT NULL,
  `Atrasada` int NOT NULL,
  `Activa` varchar(5) NOT NULL,
  `Ejecutado` float NOT NULL,
  `cantidad_ppto` float DEFAULT NULL,
  `Unidad` varchar(20) DEFAULT NULL,
  `Cantidad_Sugerida` float DEFAULT NULL,
  `Compromiso` float DEFAULT NULL,
  `Ejecutado_Real` float DEFAULT NULL,
  `PAC` float DEFAULT NULL,
  `P_Completado` float DEFAULT NULL,
  `Categoria_CNP` varchar(200) DEFAULT NULL,
  `CNP` varchar(200) DEFAULT NULL,
  `Observaciones_CNP` varchar(200) DEFAULT NULL,
  `Categoria_CNC` varchar(200) DEFAULT NULL,
  `CNC` varchar(200) DEFAULT NULL,
  `Observaciones_CNC` varchar(200) DEFAULT NULL,
  `Responsable_AIA` varchar(200) DEFAULT NULL,
  `Sub_Contratista` varchar(200) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `general_informe_pdc`
--

CREATE TABLE `general_informe_pdc` (
  `id` int NOT NULL,
  `Proyecto` varchar(200) NOT NULL,
  `semana` int NOT NULL,
  `Fecha_Inicio_Sem` date NOT NULL,
  `Fecha_Fin_Sem` date NOT NULL,
  `fechaHoy` date NOT NULL,
  `maxSemana` int NOT NULL,
  `Proyecto_maxSemana` varchar(200) NOT NULL,
  `tipoPaquete` varchar(200) NOT NULL,
  `paqueteContratacion` varchar(200) NOT NULL,
  `contratos` varchar(500) NOT NULL,
  `numeroSubcontratos` int DEFAULT '1',
  `subcontratoPaquete` int NOT NULL DEFAULT '1',
  `estado` varchar(500) DEFAULT NULL,
  `fechaElaboracionPliegos` date DEFAULT NULL,
  `diasElaboracionPliegos` int DEFAULT NULL,
  `fechaRealElaboracionPliegos` date DEFAULT NULL,
  `fechaIngresoLicify` date DEFAULT NULL,
  `diasIngresoLicify` int DEFAULT NULL,
  `fechaRealIngresoLicify` date DEFAULT NULL,
  `fechaEntregaPliegos` date DEFAULT NULL,
  `diasEntregaPliegos` int DEFAULT NULL,
  `fechaRealEntregaPliegos` date DEFAULT NULL,
  `fechaReciboPropuestas` date DEFAULT NULL,
  `diasReciboPropuestas` int DEFAULT NULL,
  `fechaRealReciboPropuestas` date DEFAULT NULL,
  `fechaCuadrosComparativos` date DEFAULT NULL,
  `diasCuadrosComparativos` int DEFAULT NULL,
  `fechaRealCuadrosComparativos` date DEFAULT NULL,
  `fechaLegalizacionContrato` date DEFAULT NULL,
  `diasLegalizacionContrato` int DEFAULT NULL,
  `fechaRealLegalizacionContrato` date DEFAULT NULL,
  `fechaFabricacion` date DEFAULT NULL,
  `diasFabricacion` int DEFAULT NULL,
  `fechaRealFabricacion` date DEFAULT NULL,
  `fechaInsumosObra` date DEFAULT NULL,
  `diasInsumosObra` int DEFAULT NULL,
  `fechaRealInsumosObra` date DEFAULT NULL,
  `fechaInicio` date NOT NULL,
  `fechaInicioProyectada` date DEFAULT NULL,
  `fechaRealInicio` date DEFAULT NULL,
  `idProveedorAdjudicado` int DEFAULT NULL,
  `proveedorAdjudicado` varchar(200) DEFAULT NULL,
  `nitProveedorAdjudicado` int DEFAULT NULL,
  `numeroContrato` varchar(50) DEFAULT NULL,
  `fechaVencimientoPolizas` date DEFAULT NULL,
  `valorPresupuesto` float DEFAULT NULL,
  `valorPrimeraNegociacion` float DEFAULT NULL,
  `valorAdjudicado` float DEFAULT NULL,
  `valorAnticipo` float DEFAULT NULL,
  `valorReclamado` float DEFAULT NULL,
  `valorDevoluciones` float DEFAULT NULL,
  `observacionesContrato` mediumtext
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `general_informe_restricciones_consolidado`
--

CREATE TABLE `general_informe_restricciones_consolidado` (
  `id` int NOT NULL,
  `Proyecto` varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `Semana` int NOT NULL,
  `Fecha_Inicio_Sem` date NOT NULL,
  `Fecha_Fin_Sem` date NOT NULL,
  `Actividad` varchar(500) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `Fecha_Inicio` date NOT NULL,
  `Fecha_Fin` date NOT NULL,
  `Semanas_Inicio` int NOT NULL,
  `Restriccion` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `valorRestriccion` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `estadoActividad` float NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `general_informe_subcontratistas`
--

CREATE TABLE `general_informe_subcontratistas` (
  `id` int NOT NULL,
  `Proyecto` varchar(100) NOT NULL,
  `Semana` int NOT NULL,
  `maxSemana` int NOT NULL,
  `Proyecto_maxSemana` varchar(500) NOT NULL,
  `Fecha_Inicio_Sem` date DEFAULT NULL,
  `Fecha_Fin_Sem` date DEFAULT NULL,
  `subcontratista` varchar(200) DEFAULT NULL,
  `correo_contacto` varchar(200) DEFAULT NULL,
  `NIT` bigint DEFAULT NULL,
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
  `Cal_Integral` varchar(11) NOT NULL DEFAULT 'NR',
  `Cal_Integral_Acum` varchar(11) NOT NULL DEFAULT 'NR',
  `Observaciones` mediumtext,
  `mdo_cal_1` varchar(5) DEFAULT 'NR',
  `mdo_cal_2` varchar(5) DEFAULT 'NR',
  `mdo_cal_3` varchar(5) DEFAULT 'NR',
  `mdo_adm_1` varchar(5) DEFAULT 'NR',
  `mdo_adm_2` varchar(5) DEFAULT 'NR',
  `mdo_adm_3` varchar(5) DEFAULT 'NR',
  `mdo_adm_4` varchar(5) DEFAULT 'NR',
  `mdo_adm_5` varchar(5) DEFAULT 'NR',
  `mdo_gsa_1` varchar(5) DEFAULT 'NR',
  `mdo_gsa_2` varchar(5) DEFAULT 'NR',
  `mdo_gsa_3` varchar(5) DEFAULT 'NR',
  `mdo_gsa_4` varchar(5) DEFAULT 'NR',
  `mdo_gsa_5` varchar(5) DEFAULT 'NR',
  `mdo_gsa_6` varchar(5) DEFAULT 'NR',
  `mdo_gsa_7` varchar(5) DEFAULT 'NR',
  `mdo_gsa_8` varchar(5) DEFAULT 'NR',
  `mdo_sst_1` varchar(5) DEFAULT 'NR',
  `mdo_sst_2` varchar(5) DEFAULT 'NR',
  `mdo_sst_3` varchar(5) DEFAULT 'NR',
  `mdo_sst_4` varchar(5) DEFAULT 'NR',
  `mdo_sst_5` varchar(5) DEFAULT 'NR',
  `mdo_sst_6` varchar(5) DEFAULT 'NR',
  `mdo_sst_7` varchar(5) DEFAULT 'NR',
  `mdo_sst_8` varchar(5) DEFAULT 'NR',
  `mdo_sst_9` varchar(5) DEFAULT 'NR',
  `mdo_sst_10` varchar(5) DEFAULT 'NR',
  `si_cal_1` varchar(5) DEFAULT 'NR',
  `si_cal_2` varchar(5) DEFAULT 'NR',
  `si_cal_3` varchar(5) DEFAULT 'NR',
  `si_adm_1` varchar(5) DEFAULT 'NR',
  `si_adm_2` varchar(5) DEFAULT 'NR',
  `si_adm_3` varchar(5) DEFAULT 'NR',
  `si_adm_4` varchar(5) DEFAULT 'NR',
  `si_adm_5` varchar(5) DEFAULT 'NR',
  `si_adm_6` varchar(5) DEFAULT 'NR',
  `si_gsa_1` varchar(5) DEFAULT 'NR',
  `si_gsa_2` varchar(5) DEFAULT 'NR',
  `si_gsa_3` varchar(5) DEFAULT 'NR',
  `si_gsa_4` varchar(5) DEFAULT 'NR',
  `si_gsa_5` varchar(5) DEFAULT 'NR',
  `si_gsa_6` varchar(5) DEFAULT 'NR',
  `si_gsa_7` varchar(5) DEFAULT 'NR',
  `si_gsa_8` varchar(5) DEFAULT 'NR',
  `si_gsa_9` varchar(5) DEFAULT 'NR',
  `si_gsa_10` varchar(5) DEFAULT 'NR',
  `si_gsa_11` varchar(5) DEFAULT 'NR',
  `si_gsa_12` varchar(5) DEFAULT 'NR',
  `si_gsa_13` varchar(5) DEFAULT 'NR',
  `si_gsa_14` varchar(5) DEFAULT 'NR',
  `si_sst_1` varchar(5) DEFAULT 'NR',
  `si_sst_2` varchar(5) DEFAULT 'NR',
  `si_sst_3` varchar(5) DEFAULT 'NR',
  `si_sst_4` varchar(5) DEFAULT 'NR',
  `si_sst_5` varchar(5) DEFAULT 'NR',
  `si_sst_6` varchar(5) DEFAULT 'NR',
  `si_sst_7` varchar(5) DEFAULT 'NR',
  `si_sst_8` varchar(5) DEFAULT 'NR',
  `si_sst_9` varchar(5) DEFAULT 'NR',
  `si_sst_10` varchar(5) DEFAULT 'NR'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `general_proyectos_procesos`
--

CREATE TABLE `general_proyectos_procesos` (
  `Id` int NOT NULL,
  `Proyecto_Proceso` varchar(50) NOT NULL,
  `Base_de_Datos` varchar(30) NOT NULL,
  `Area` varchar(50) NOT NULL,
  `Activo` int NOT NULL DEFAULT '1',
  `Acceso` int NOT NULL DEFAULT '1',
  `pdcActivo` int NOT NULL DEFAULT '0',
  `fechaInicioLineaBase` date DEFAULT NULL,
  `fechaFinLineaBase` date DEFAULT NULL,
  `costoDiaRetraso` float NOT NULL DEFAULT '5000000',
  `urlCambios` longtext
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `general_usuarios`
--

CREATE TABLE `general_usuarios` (
  `id` int NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `cargo` varchar(100) NOT NULL,
  `usuario` varchar(20) NOT NULL,
  `password` varchar(200) NOT NULL,
  `force_password_change` tinyint(1) NOT NULL DEFAULT '0',
  `activo` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `homecenterMallplaza_actividades`
--

CREATE TABLE `homecenterMallplaza_actividades` (
  `Id` int NOT NULL,
  `codigo` int NOT NULL,
  `actividad` varchar(300) NOT NULL,
  `descripcionActividad` mediumtext,
  `actividadInicio` varchar(500) DEFAULT NULL,
  `nombreActividadInicio` varchar(500) DEFAULT NULL,
  `fechaInicio` date DEFAULT NULL,
  `tipoContrato` varchar(10) DEFAULT NULL,
  `semanaActualizacion` int DEFAULT NULL,
  `SI1` varchar(200) DEFAULT NULL,
  `paqueteSI1` varchar(200) DEFAULT NULL,
  `SI2` varchar(200) DEFAULT NULL,
  `paqueteSI2` varchar(200) DEFAULT NULL,
  `SI3` varchar(200) DEFAULT NULL,
  `paqueteSI3` varchar(200) DEFAULT NULL,
  `SI4` varchar(200) DEFAULT NULL,
  `paqueteSI4` varchar(200) DEFAULT NULL,
  `SI5` varchar(200) DEFAULT NULL,
  `paqueteSI5` varchar(200) DEFAULT NULL,
  `S1` varchar(200) DEFAULT NULL,
  `paqueteS1` varchar(200) DEFAULT NULL,
  `S2` varchar(200) DEFAULT NULL,
  `paqueteS2` varchar(200) DEFAULT NULL,
  `S3` varchar(200) DEFAULT NULL,
  `paqueteS3` varchar(200) DEFAULT NULL,
  `S4` varchar(200) DEFAULT NULL,
  `paqueteS4` varchar(200) DEFAULT NULL,
  `S5` varchar(200) DEFAULT NULL,
  `paqueteS5` varchar(200) DEFAULT NULL,
  `MO1` varchar(200) DEFAULT NULL,
  `paqueteMO1` varchar(200) DEFAULT NULL,
  `MO2` varchar(200) DEFAULT NULL,
  `paqueteMO2` varchar(200) DEFAULT NULL,
  `MO3` varchar(200) DEFAULT NULL,
  `paqueteMO3` varchar(200) DEFAULT NULL,
  `MO4` varchar(200) DEFAULT NULL,
  `paqueteMO4` varchar(200) DEFAULT NULL,
  `MO5` varchar(200) DEFAULT NULL,
  `paqueteMO5` varchar(200) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `homecenterMallplaza_cic`
--

CREATE TABLE `homecenterMallplaza_cic` (
  `Id` int NOT NULL,
  `Semana` int DEFAULT NULL,
  `subcontratista` varchar(200) DEFAULT NULL,
  `correo_contacto` varchar(200) DEFAULT NULL,
  `NIT` varchar(10) DEFAULT NULL,
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
  `Observaciones` mediumtext,
  `mdo_cal_1` varchar(5) DEFAULT 'NR',
  `mdo_cal_2` varchar(5) DEFAULT 'NR',
  `mdo_cal_3` varchar(5) DEFAULT 'NR',
  `mdo_adm_1` varchar(5) DEFAULT 'NR',
  `mdo_adm_2` varchar(5) DEFAULT 'NR',
  `mdo_adm_3` varchar(5) DEFAULT 'NR',
  `mdo_adm_4` varchar(5) DEFAULT 'NR',
  `mdo_adm_5` varchar(5) DEFAULT 'NR',
  `mdo_gsa_1` varchar(5) DEFAULT 'NR',
  `mdo_gsa_2` varchar(5) DEFAULT 'NR',
  `mdo_gsa_3` varchar(5) DEFAULT 'NR',
  `mdo_gsa_4` varchar(5) DEFAULT 'NR',
  `mdo_gsa_5` varchar(5) DEFAULT 'NR',
  `mdo_gsa_6` varchar(5) DEFAULT 'NR',
  `mdo_gsa_7` varchar(5) DEFAULT 'NR',
  `mdo_gsa_8` varchar(5) DEFAULT 'NR',
  `mdo_sst_1` varchar(5) DEFAULT 'NR',
  `mdo_sst_2` varchar(5) DEFAULT 'NR',
  `mdo_sst_3` varchar(5) DEFAULT 'NR',
  `mdo_sst_4` varchar(5) DEFAULT 'NR',
  `mdo_sst_5` varchar(5) DEFAULT 'NR',
  `mdo_sst_6` varchar(5) DEFAULT 'NR',
  `mdo_sst_7` varchar(5) DEFAULT 'NR',
  `mdo_sst_8` varchar(5) DEFAULT 'NR',
  `mdo_sst_9` varchar(5) DEFAULT 'NR',
  `mdo_sst_10` varchar(5) DEFAULT 'NR',
  `si_cal_1` varchar(5) DEFAULT 'NR',
  `si_cal_2` varchar(5) DEFAULT 'NR',
  `si_cal_3` varchar(5) DEFAULT 'NR',
  `si_adm_1` varchar(5) DEFAULT 'NR',
  `si_adm_2` varchar(5) DEFAULT 'NR',
  `si_adm_3` varchar(5) DEFAULT 'NR',
  `si_adm_4` varchar(5) DEFAULT 'NR',
  `si_adm_5` varchar(5) DEFAULT 'NR',
  `si_adm_6` varchar(5) DEFAULT 'NR',
  `si_gsa_1` varchar(5) DEFAULT 'NR',
  `si_gsa_2` varchar(5) DEFAULT 'NR',
  `si_gsa_3` varchar(5) DEFAULT 'NR',
  `si_gsa_4` varchar(5) DEFAULT 'NR',
  `si_gsa_5` varchar(5) DEFAULT 'NR',
  `si_gsa_6` varchar(5) DEFAULT 'NR',
  `si_gsa_7` varchar(5) DEFAULT 'NR',
  `si_gsa_8` varchar(5) DEFAULT 'NR',
  `si_gsa_9` varchar(5) DEFAULT 'NR',
  `si_gsa_10` varchar(5) DEFAULT 'NR',
  `si_gsa_11` varchar(5) DEFAULT 'NR',
  `si_gsa_12` varchar(5) DEFAULT 'NR',
  `si_gsa_13` varchar(5) DEFAULT 'NR',
  `si_gsa_14` varchar(5) DEFAULT 'NR',
  `si_sst_1` varchar(5) DEFAULT 'NR',
  `si_sst_2` varchar(5) DEFAULT 'NR',
  `si_sst_3` varchar(5) DEFAULT 'NR',
  `si_sst_4` varchar(5) DEFAULT 'NR',
  `si_sst_5` varchar(5) DEFAULT 'NR',
  `si_sst_6` varchar(5) DEFAULT 'NR',
  `si_sst_7` varchar(5) DEFAULT 'NR',
  `si_sst_8` varchar(5) DEFAULT 'NR',
  `si_sst_9` varchar(5) DEFAULT 'NR',
  `si_sst_10` varchar(5) DEFAULT 'NR'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `homecenterMallplaza_pdc`
--

CREATE TABLE `homecenterMallplaza_pdc` (
  `consecutivo` int NOT NULL,
  `semana` int NOT NULL,
  `titulo` int NOT NULL,
  `tipoPaquete` varchar(200) NOT NULL,
  `paqueteContratacion` varchar(200) DEFAULT NULL,
  `contratos` varchar(200) DEFAULT NULL,
  `numeroSubcontratos` int DEFAULT '1',
  `subcontratoPaquete` int NOT NULL DEFAULT '1',
  `estado` varchar(200) DEFAULT NULL,
  `fechaElaboracionPliegos` date DEFAULT NULL,
  `diasElaboracionPliegos` int DEFAULT NULL,
  `fechaRealElaboracionPliegos` date DEFAULT NULL,
  `fechaIngresoLicify` date DEFAULT NULL,
  `diasIngresoLicify` int DEFAULT NULL,
  `fechaRealIngresoLicify` date DEFAULT NULL,
  `fechaEntregaPliegos` date DEFAULT NULL,
  `diasEntregaPliegos` int DEFAULT NULL,
  `fechaRealEntregaPliegos` date DEFAULT NULL,
  `fechaReciboPropuestas` date DEFAULT NULL,
  `diasReciboPropuestas` int DEFAULT NULL,
  `fechaRealReciboPropuestas` date DEFAULT NULL,
  `fechaCuadrosComparativos` date DEFAULT NULL,
  `diasCuadrosComparativos` int DEFAULT NULL,
  `fechaRealCuadrosComparativos` date DEFAULT NULL,
  `fechaLegalizacionContrato` date DEFAULT NULL,
  `diasLegalizacionContrato` int DEFAULT NULL,
  `fechaRealLegalizacionContrato` date DEFAULT NULL,
  `fechaFabricacion` date DEFAULT NULL,
  `diasFabricacion` int DEFAULT NULL,
  `fechaRealFabricacion` date DEFAULT NULL,
  `fechaInsumosObra` date DEFAULT NULL,
  `diasInsumosObra` int DEFAULT NULL,
  `fechaRealInsumosObra` date DEFAULT NULL,
  `fechaInicio` date DEFAULT NULL,
  `fechaInicioProyectada` date DEFAULT NULL,
  `fechaRealInicio` date DEFAULT NULL,
  `idProveedorAdjudicado` int DEFAULT NULL,
  `numeroContrato` varchar(50) DEFAULT NULL,
  `aplicaPolizas` int NOT NULL DEFAULT '1',
  `fechaVencimientoPolizas` date DEFAULT NULL,
  `valorPresupuesto` float DEFAULT NULL,
  `valorPrimeraNegociacion` float DEFAULT NULL,
  `valorAdjudicado` float DEFAULT NULL,
  `valorAnticipo` float DEFAULT NULL,
  `valorReclamado` float DEFAULT NULL,
  `valorDevoluciones` float DEFAULT NULL,
  `observacionesContrato` mediumtext
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `homecenterMallplaza_pi_shared_constraints`
--

CREATE TABLE `homecenterMallplaza_pi_shared_constraints` (
  `Id` bigint UNSIGNED NOT NULL,
  `Semana` int NOT NULL,
  `Restriccion` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `ValorObjetivo` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `Nota` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `CreadoPor` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `CreadoEn` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ActualizadoEn` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `homecenterMallplaza_pi_shared_constraint_links`
--

CREATE TABLE `homecenterMallplaza_pi_shared_constraint_links` (
  `Id` bigint UNSIGNED NOT NULL,
  `SharedConstraintId` bigint UNSIGNED NOT NULL,
  `Semana` int NOT NULL,
  `ConsecutivoEnPrograma` bigint NOT NULL,
  `ValorAplicado` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `OverrideLocal` tinyint(1) NOT NULL DEFAULT '0',
  `AplicadoEn` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `homecenterMallplaza_profesionales`
--

CREATE TABLE `homecenterMallplaza_profesionales` (
  `id` int NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `cargo` varchar(100) NOT NULL,
  `activo` int NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `homecenterMallplaza_programa`
--

CREATE TABLE `homecenterMallplaza_programa` (
  `Consecutivo` int NOT NULL,
  `Id` varchar(500) DEFAULT NULL,
  `Actividad` varchar(500) DEFAULT NULL,
  `Titulo` int DEFAULT NULL,
  `Fecha_Inicio` date DEFAULT NULL,
  `Fecha_Fin` date DEFAULT NULL,
  `Ruta_Critica` int DEFAULT NULL,
  `Ejecutado` float DEFAULT '0',
  `Estado` varchar(50) DEFAULT NULL,
  `Semanas_Inicio` int DEFAULT '0',
  `Estado_Restricciones` float DEFAULT '0',
  `D_y_E` float DEFAULT '0',
  `Materiales` float DEFAULT '0',
  `MdeO` float DEFAULT '0',
  `Equipos` float DEFAULT '0',
  `Predecesora` float DEFAULT '0',
  `Pdto_Cons` float DEFAULT '0',
  `Modelo` varchar(9) DEFAULT '0',
  `Responsable_AIA` varchar(100) DEFAULT NULL,
  `Observaciones` mediumtext,
  `Ult_Act_Est` date DEFAULT NULL,
  `Ult_Act_Restr` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `homecenterMallplaza_programacion_semanal`
--

CREATE TABLE `homecenterMallplaza_programacion_semanal` (
  `Consecutivo` int NOT NULL,
  `Semana` int DEFAULT NULL,
  `Consecutivo_En_Programa` int NOT NULL,
  `Id` varchar(500) DEFAULT NULL,
  `Actividad` varchar(500) DEFAULT NULL,
  `Descripcion` mediumtext,
  `Ubicacion` mediumtext,
  `Fecha_Inicio` date DEFAULT NULL,
  `Fecha_Fin` date DEFAULT NULL,
  `Sub_Contratista` varchar(200) DEFAULT NULL,
  `Responsable_AIA` varchar(200) DEFAULT NULL,
  `Empresa` varchar(200) NOT NULL DEFAULT 'AIA',
  `Ejecutado` float DEFAULT NULL,
  `medir_productividad` int DEFAULT '0',
  `Unidad` varchar(10) DEFAULT NULL,
  `cantidad_ppto` int DEFAULT NULL,
  `Cantidad_Sugerida` float DEFAULT NULL,
  `Compromiso` float DEFAULT NULL,
  `Ejecutado_Real` float DEFAULT NULL,
  `P_Completado` float DEFAULT NULL,
  `PAC` int DEFAULT NULL,
  `Critica` int DEFAULT NULL,
  `Atrasada` int DEFAULT NULL,
  `Activa` varchar(3) DEFAULT NULL,
  `Prog_Sin_Restricciones_100` int DEFAULT NULL,
  `Categoria_CNP` varchar(100) DEFAULT NULL,
  `CNP` varchar(100) DEFAULT NULL,
  `Observaciones_CNP` mediumtext,
  `Categoria_CNC` varchar(100) DEFAULT NULL,
  `CNC` varchar(100) DEFAULT NULL,
  `Observaciones_CNC` mediumtext,
  `Rendimientos` varchar(500) DEFAULT NULL,
  `codigo_actividad` varchar(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `homecenterMallplaza_programa_consolidado`
--

CREATE TABLE `homecenterMallplaza_programa_consolidado` (
  `Consecutivo` int NOT NULL,
  `Semana` int NOT NULL,
  `Consecutivo_en_Programa` int NOT NULL,
  `Id` varchar(500) DEFAULT NULL,
  `Actividad` varchar(500) DEFAULT NULL,
  `Titulo` int DEFAULT NULL,
  `Fecha_Inicio` date DEFAULT NULL,
  `Fecha_Fin` date DEFAULT NULL,
  `Ruta_Critica` int DEFAULT NULL,
  `Ejecutado` float DEFAULT '0',
  `Estado` varchar(100) DEFAULT NULL,
  `Semanas_Inicio` int DEFAULT '0',
  `Estado_Restricciones` float DEFAULT '0',
  `D_y_E` varchar(9) DEFAULT '0',
  `Materiales` varchar(9) DEFAULT '0',
  `MdeO` varchar(9) DEFAULT '0',
  `Equipos` varchar(9) DEFAULT '0',
  `Predecesora` varchar(9) DEFAULT '0',
  `Pdto_Cons` varchar(9) DEFAULT '0',
  `Modelo` varchar(9) DEFAULT '0',
  `Sub_Contratista` varchar(100) DEFAULT NULL,
  `Responsable_AIA` varchar(100) DEFAULT NULL,
  `Observaciones` mediumtext,
  `Ult_Act_Est` date DEFAULT NULL,
  `Ult_Act_Restr` date DEFAULT NULL,
  `Activa` int NOT NULL DEFAULT '0',
  `Ejecutado_Siguiente_Semana` float DEFAULT NULL,
  `codigo_actividad` varchar(11) DEFAULT NULL,
  `medir_productividad` int DEFAULT '0',
  `cantidad_ppto` int DEFAULT NULL,
  `unidad` varchar(20) DEFAULT NULL,
  `programaAnteriorAsociar` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `homecenterMallplaza_semanas_activas`
--

CREATE TABLE `homecenterMallplaza_semanas_activas` (
  `Id` int NOT NULL,
  `Semana` int NOT NULL,
  `Fecha_Inicio_Sem` date NOT NULL,
  `Fecha_Fin_Sem` date NOT NULL,
  `Semanal_Confirmada` int DEFAULT '0',
  `fechaCierreCompromisos` date DEFAULT NULL,
  `fechaCreacionSemana` date DEFAULT NULL,
  `reprogramacion` int NOT NULL DEFAULT '0',
  `diferenciaEstructuraCron` int NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `laMasia_actividades`
--

CREATE TABLE `laMasia_actividades` (
  `Id` int NOT NULL,
  `codigo` int NOT NULL,
  `actividad` varchar(300) NOT NULL,
  `descripcionActividad` mediumtext,
  `actividadInicio` varchar(500) DEFAULT NULL,
  `nombreActividadInicio` varchar(500) DEFAULT NULL,
  `fechaInicio` date DEFAULT NULL,
  `tipoContrato` varchar(10) DEFAULT NULL,
  `semanaActualizacion` int DEFAULT NULL,
  `SI1` varchar(200) DEFAULT NULL,
  `paqueteSI1` varchar(200) DEFAULT NULL,
  `SI2` varchar(200) DEFAULT NULL,
  `paqueteSI2` varchar(200) DEFAULT NULL,
  `SI3` varchar(200) DEFAULT NULL,
  `paqueteSI3` varchar(200) DEFAULT NULL,
  `SI4` varchar(200) DEFAULT NULL,
  `paqueteSI4` varchar(200) DEFAULT NULL,
  `SI5` varchar(200) DEFAULT NULL,
  `paqueteSI5` varchar(200) DEFAULT NULL,
  `S1` varchar(200) DEFAULT NULL,
  `paqueteS1` varchar(200) DEFAULT NULL,
  `S2` varchar(200) DEFAULT NULL,
  `paqueteS2` varchar(200) DEFAULT NULL,
  `S3` varchar(200) DEFAULT NULL,
  `paqueteS3` varchar(200) DEFAULT NULL,
  `S4` varchar(200) DEFAULT NULL,
  `paqueteS4` varchar(200) DEFAULT NULL,
  `S5` varchar(200) DEFAULT NULL,
  `paqueteS5` varchar(200) DEFAULT NULL,
  `MO1` varchar(200) DEFAULT NULL,
  `paqueteMO1` varchar(200) DEFAULT NULL,
  `MO2` varchar(200) DEFAULT NULL,
  `paqueteMO2` varchar(200) DEFAULT NULL,
  `MO3` varchar(200) DEFAULT NULL,
  `paqueteMO3` varchar(200) DEFAULT NULL,
  `MO4` varchar(200) DEFAULT NULL,
  `paqueteMO4` varchar(200) DEFAULT NULL,
  `MO5` varchar(200) DEFAULT NULL,
  `paqueteMO5` varchar(200) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `laMasia_cic`
--

CREATE TABLE `laMasia_cic` (
  `Id` int NOT NULL,
  `Semana` int DEFAULT NULL,
  `subcontratista` varchar(200) DEFAULT NULL,
  `correo_contacto` varchar(200) DEFAULT NULL,
  `NIT` varchar(10) DEFAULT NULL,
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
  `Observaciones` mediumtext,
  `mdo_cal_1` varchar(5) DEFAULT 'NR',
  `mdo_cal_2` varchar(5) DEFAULT 'NR',
  `mdo_cal_3` varchar(5) DEFAULT 'NR',
  `mdo_adm_1` varchar(5) DEFAULT 'NR',
  `mdo_adm_2` varchar(5) DEFAULT 'NR',
  `mdo_adm_3` varchar(5) DEFAULT 'NR',
  `mdo_adm_4` varchar(5) DEFAULT 'NR',
  `mdo_adm_5` varchar(5) DEFAULT 'NR',
  `mdo_gsa_1` varchar(5) DEFAULT 'NR',
  `mdo_gsa_2` varchar(5) DEFAULT 'NR',
  `mdo_gsa_3` varchar(5) DEFAULT 'NR',
  `mdo_gsa_4` varchar(5) DEFAULT 'NR',
  `mdo_gsa_5` varchar(5) DEFAULT 'NR',
  `mdo_gsa_6` varchar(5) DEFAULT 'NR',
  `mdo_gsa_7` varchar(5) DEFAULT 'NR',
  `mdo_gsa_8` varchar(5) DEFAULT 'NR',
  `mdo_sst_1` varchar(5) DEFAULT 'NR',
  `mdo_sst_2` varchar(5) DEFAULT 'NR',
  `mdo_sst_3` varchar(5) DEFAULT 'NR',
  `mdo_sst_4` varchar(5) DEFAULT 'NR',
  `mdo_sst_5` varchar(5) DEFAULT 'NR',
  `mdo_sst_6` varchar(5) DEFAULT 'NR',
  `mdo_sst_7` varchar(5) DEFAULT 'NR',
  `mdo_sst_8` varchar(5) DEFAULT 'NR',
  `mdo_sst_9` varchar(5) DEFAULT 'NR',
  `mdo_sst_10` varchar(5) DEFAULT 'NR',
  `si_cal_1` varchar(5) DEFAULT 'NR',
  `si_cal_2` varchar(5) DEFAULT 'NR',
  `si_cal_3` varchar(5) DEFAULT 'NR',
  `si_adm_1` varchar(5) DEFAULT 'NR',
  `si_adm_2` varchar(5) DEFAULT 'NR',
  `si_adm_3` varchar(5) DEFAULT 'NR',
  `si_adm_4` varchar(5) DEFAULT 'NR',
  `si_adm_5` varchar(5) DEFAULT 'NR',
  `si_adm_6` varchar(5) DEFAULT 'NR',
  `si_gsa_1` varchar(5) DEFAULT 'NR',
  `si_gsa_2` varchar(5) DEFAULT 'NR',
  `si_gsa_3` varchar(5) DEFAULT 'NR',
  `si_gsa_4` varchar(5) DEFAULT 'NR',
  `si_gsa_5` varchar(5) DEFAULT 'NR',
  `si_gsa_6` varchar(5) DEFAULT 'NR',
  `si_gsa_7` varchar(5) DEFAULT 'NR',
  `si_gsa_8` varchar(5) DEFAULT 'NR',
  `si_gsa_9` varchar(5) DEFAULT 'NR',
  `si_gsa_10` varchar(5) DEFAULT 'NR',
  `si_gsa_11` varchar(5) DEFAULT 'NR',
  `si_gsa_12` varchar(5) DEFAULT 'NR',
  `si_gsa_13` varchar(5) DEFAULT 'NR',
  `si_gsa_14` varchar(5) DEFAULT 'NR',
  `si_sst_1` varchar(5) DEFAULT 'NR',
  `si_sst_2` varchar(5) DEFAULT 'NR',
  `si_sst_3` varchar(5) DEFAULT 'NR',
  `si_sst_4` varchar(5) DEFAULT 'NR',
  `si_sst_5` varchar(5) DEFAULT 'NR',
  `si_sst_6` varchar(5) DEFAULT 'NR',
  `si_sst_7` varchar(5) DEFAULT 'NR',
  `si_sst_8` varchar(5) DEFAULT 'NR',
  `si_sst_9` varchar(5) DEFAULT 'NR',
  `si_sst_10` varchar(5) DEFAULT 'NR'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `laMasia_pdc`
--

CREATE TABLE `laMasia_pdc` (
  `consecutivo` int NOT NULL,
  `semana` int NOT NULL,
  `titulo` int NOT NULL,
  `tipoPaquete` varchar(200) NOT NULL,
  `paqueteContratacion` varchar(200) DEFAULT NULL,
  `contratos` varchar(200) DEFAULT NULL,
  `numeroSubcontratos` int DEFAULT '1',
  `subcontratoPaquete` int NOT NULL DEFAULT '1',
  `estado` varchar(200) DEFAULT NULL,
  `fechaElaboracionPliegos` date DEFAULT NULL,
  `diasElaboracionPliegos` int DEFAULT NULL,
  `fechaRealElaboracionPliegos` date DEFAULT NULL,
  `fechaIngresoLicify` date DEFAULT NULL,
  `diasIngresoLicify` int DEFAULT NULL,
  `fechaRealIngresoLicify` date DEFAULT NULL,
  `fechaEntregaPliegos` date DEFAULT NULL,
  `diasEntregaPliegos` int DEFAULT NULL,
  `fechaRealEntregaPliegos` date DEFAULT NULL,
  `fechaReciboPropuestas` date DEFAULT NULL,
  `diasReciboPropuestas` int DEFAULT NULL,
  `fechaRealReciboPropuestas` date DEFAULT NULL,
  `fechaCuadrosComparativos` date DEFAULT NULL,
  `diasCuadrosComparativos` int DEFAULT NULL,
  `fechaRealCuadrosComparativos` date DEFAULT NULL,
  `fechaLegalizacionContrato` date DEFAULT NULL,
  `diasLegalizacionContrato` int DEFAULT NULL,
  `fechaRealLegalizacionContrato` date DEFAULT NULL,
  `fechaFabricacion` date DEFAULT NULL,
  `diasFabricacion` int DEFAULT NULL,
  `fechaRealFabricacion` date DEFAULT NULL,
  `fechaInsumosObra` date DEFAULT NULL,
  `diasInsumosObra` int DEFAULT NULL,
  `fechaRealInsumosObra` date DEFAULT NULL,
  `fechaInicio` date DEFAULT NULL,
  `fechaInicioProyectada` date DEFAULT NULL,
  `fechaRealInicio` date DEFAULT NULL,
  `idProveedorAdjudicado` int DEFAULT NULL,
  `numeroContrato` varchar(50) DEFAULT NULL,
  `aplicaPolizas` int NOT NULL DEFAULT '1',
  `fechaVencimientoPolizas` date DEFAULT NULL,
  `valorPresupuesto` float DEFAULT NULL,
  `valorPrimeraNegociacion` float DEFAULT NULL,
  `valorAdjudicado` float DEFAULT NULL,
  `valorAnticipo` float DEFAULT NULL,
  `valorReclamado` float DEFAULT NULL,
  `valorDevoluciones` float DEFAULT NULL,
  `observacionesContrato` mediumtext
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `laMasia_pi_shared_constraints`
--

CREATE TABLE `laMasia_pi_shared_constraints` (
  `Id` bigint UNSIGNED NOT NULL,
  `Semana` int NOT NULL,
  `Restriccion` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `ValorObjetivo` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `Nota` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `CreadoPor` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `CreadoEn` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ActualizadoEn` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `laMasia_pi_shared_constraint_links`
--

CREATE TABLE `laMasia_pi_shared_constraint_links` (
  `Id` bigint UNSIGNED NOT NULL,
  `SharedConstraintId` bigint UNSIGNED NOT NULL,
  `Semana` int NOT NULL,
  `ConsecutivoEnPrograma` bigint NOT NULL,
  `ValorAplicado` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `OverrideLocal` tinyint(1) NOT NULL DEFAULT '0',
  `AplicadoEn` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `laMasia_profesionales`
--

CREATE TABLE `laMasia_profesionales` (
  `id` int NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `cargo` varchar(100) NOT NULL,
  `activo` int NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `laMasia_programa`
--

CREATE TABLE `laMasia_programa` (
  `Consecutivo` int NOT NULL,
  `Id` varchar(500) DEFAULT NULL,
  `Actividad` varchar(500) DEFAULT NULL,
  `Titulo` int DEFAULT NULL,
  `Fecha_Inicio` date DEFAULT NULL,
  `Fecha_Fin` date DEFAULT NULL,
  `Ruta_Critica` int DEFAULT NULL,
  `Ejecutado` float DEFAULT '0',
  `Estado` varchar(50) DEFAULT NULL,
  `Semanas_Inicio` int DEFAULT '0',
  `Estado_Restricciones` float DEFAULT '0',
  `D_y_E` float DEFAULT '0',
  `Materiales` float DEFAULT '0',
  `MdeO` float DEFAULT '0',
  `Equipos` float DEFAULT '0',
  `Predecesora` float DEFAULT '0',
  `Pdto_Cons` float DEFAULT '0',
  `Modelo` varchar(9) DEFAULT '0',
  `Responsable_AIA` varchar(100) DEFAULT NULL,
  `Observaciones` mediumtext,
  `Ult_Act_Est` date DEFAULT NULL,
  `Ult_Act_Restr` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `laMasia_programacion_semanal`
--

CREATE TABLE `laMasia_programacion_semanal` (
  `Consecutivo` int NOT NULL,
  `Semana` int DEFAULT NULL,
  `Consecutivo_En_Programa` int NOT NULL,
  `Id` varchar(500) DEFAULT NULL,
  `Actividad` varchar(500) DEFAULT NULL,
  `Descripcion` mediumtext,
  `Ubicacion` mediumtext,
  `Fecha_Inicio` date DEFAULT NULL,
  `Fecha_Fin` date DEFAULT NULL,
  `Sub_Contratista` varchar(200) DEFAULT NULL,
  `Responsable_AIA` varchar(200) DEFAULT NULL,
  `Empresa` varchar(200) NOT NULL DEFAULT 'AIA',
  `Ejecutado` float DEFAULT NULL,
  `medir_productividad` int DEFAULT '0',
  `Unidad` varchar(10) DEFAULT NULL,
  `cantidad_ppto` int DEFAULT NULL,
  `Cantidad_Sugerida` float DEFAULT NULL,
  `Compromiso` float DEFAULT NULL,
  `Ejecutado_Real` float DEFAULT NULL,
  `P_Completado` float DEFAULT NULL,
  `PAC` int DEFAULT NULL,
  `Critica` int DEFAULT NULL,
  `Atrasada` int DEFAULT NULL,
  `Activa` varchar(3) DEFAULT NULL,
  `Prog_Sin_Restricciones_100` int DEFAULT NULL,
  `Categoria_CNP` varchar(100) DEFAULT NULL,
  `CNP` varchar(100) DEFAULT NULL,
  `Observaciones_CNP` mediumtext,
  `Categoria_CNC` varchar(100) DEFAULT NULL,
  `CNC` varchar(100) DEFAULT NULL,
  `Observaciones_CNC` mediumtext,
  `Rendimientos` varchar(500) DEFAULT NULL,
  `codigo_actividad` varchar(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `laMasia_programa_consolidado`
--

CREATE TABLE `laMasia_programa_consolidado` (
  `Consecutivo` int NOT NULL,
  `Semana` int NOT NULL,
  `Consecutivo_en_Programa` int NOT NULL,
  `Id` varchar(500) DEFAULT NULL,
  `Actividad` varchar(500) DEFAULT NULL,
  `Titulo` int DEFAULT NULL,
  `Fecha_Inicio` date DEFAULT NULL,
  `Fecha_Fin` date DEFAULT NULL,
  `Ruta_Critica` int DEFAULT NULL,
  `Ejecutado` float DEFAULT '0',
  `Estado` varchar(100) DEFAULT NULL,
  `Semanas_Inicio` int DEFAULT '0',
  `Estado_Restricciones` float DEFAULT '0',
  `D_y_E` varchar(9) DEFAULT '0',
  `Materiales` varchar(9) DEFAULT '0',
  `MdeO` varchar(9) DEFAULT '0',
  `Equipos` varchar(9) DEFAULT '0',
  `Predecesora` varchar(9) DEFAULT '0',
  `Pdto_Cons` varchar(9) DEFAULT '0',
  `Modelo` varchar(9) DEFAULT '0',
  `Sub_Contratista` varchar(100) DEFAULT NULL,
  `Responsable_AIA` varchar(100) DEFAULT NULL,
  `Observaciones` mediumtext,
  `Ult_Act_Est` date DEFAULT NULL,
  `Ult_Act_Restr` date DEFAULT NULL,
  `Activa` int NOT NULL DEFAULT '0',
  `Ejecutado_Siguiente_Semana` float DEFAULT NULL,
  `codigo_actividad` varchar(11) DEFAULT NULL,
  `medir_productividad` int DEFAULT '0',
  `cantidad_ppto` int DEFAULT NULL,
  `unidad` varchar(20) DEFAULT NULL,
  `programaAnteriorAsociar` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `laMasia_semanas_activas`
--

CREATE TABLE `laMasia_semanas_activas` (
  `Id` int NOT NULL,
  `Semana` int NOT NULL,
  `Fecha_Inicio_Sem` date NOT NULL,
  `Fecha_Fin_Sem` date NOT NULL,
  `Semanal_Confirmada` int DEFAULT '0',
  `fechaCierreCompromisos` date DEFAULT NULL,
  `fechaCreacionSemana` date DEFAULT NULL,
  `reprogramacion` int NOT NULL DEFAULT '0',
  `diferenciaEstructuraCron` int NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `laMasia_subcontratistas`
--

CREATE TABLE `laMasia_subcontratistas` (
  `Id` int NOT NULL,
  `subcontratista` varchar(200) NOT NULL,
  `correo_contacto` varchar(200) NOT NULL,
  `NIT` bigint NOT NULL,
  `alcance` varchar(200) NOT NULL,
  `tipo_proveedor` varchar(200) NOT NULL,
  `activo` int NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metrolineaConfinamientoDos_actividades`
--

CREATE TABLE `metrolineaConfinamientoDos_actividades` (
  `Id` int NOT NULL,
  `codigo` int NOT NULL,
  `actividad` varchar(300) NOT NULL,
  `descripcionActividad` mediumtext,
  `actividadInicio` varchar(500) DEFAULT NULL,
  `nombreActividadInicio` varchar(500) DEFAULT NULL,
  `fechaInicio` date DEFAULT NULL,
  `tipoContrato` varchar(10) DEFAULT NULL,
  `semanaActualizacion` int DEFAULT NULL,
  `SI1` varchar(200) DEFAULT NULL,
  `paqueteSI1` varchar(200) DEFAULT NULL,
  `SI2` varchar(200) DEFAULT NULL,
  `paqueteSI2` varchar(200) DEFAULT NULL,
  `SI3` varchar(200) DEFAULT NULL,
  `paqueteSI3` varchar(200) DEFAULT NULL,
  `SI4` varchar(200) DEFAULT NULL,
  `paqueteSI4` varchar(200) DEFAULT NULL,
  `SI5` varchar(200) DEFAULT NULL,
  `paqueteSI5` varchar(200) DEFAULT NULL,
  `S1` varchar(200) DEFAULT NULL,
  `paqueteS1` varchar(200) DEFAULT NULL,
  `S2` varchar(200) DEFAULT NULL,
  `paqueteS2` varchar(200) DEFAULT NULL,
  `S3` varchar(200) DEFAULT NULL,
  `paqueteS3` varchar(200) DEFAULT NULL,
  `S4` varchar(200) DEFAULT NULL,
  `paqueteS4` varchar(200) DEFAULT NULL,
  `S5` varchar(200) DEFAULT NULL,
  `paqueteS5` varchar(200) DEFAULT NULL,
  `MO1` varchar(200) DEFAULT NULL,
  `paqueteMO1` varchar(200) DEFAULT NULL,
  `MO2` varchar(200) DEFAULT NULL,
  `paqueteMO2` varchar(200) DEFAULT NULL,
  `MO3` varchar(200) DEFAULT NULL,
  `paqueteMO3` varchar(200) DEFAULT NULL,
  `MO4` varchar(200) DEFAULT NULL,
  `paqueteMO4` varchar(200) DEFAULT NULL,
  `MO5` varchar(200) DEFAULT NULL,
  `paqueteMO5` varchar(200) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metrolineaConfinamientoDos_cambios`
--

CREATE TABLE `metrolineaConfinamientoDos_cambios` (
  `id` int NOT NULL,
  `solicitanteCambio` int DEFAULT NULL,
  `detalleSolicitanteOtro` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `fechaSolicitud` date DEFAULT NULL,
  `prioridad` int DEFAULT NULL,
  `tipoCambio` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `responsableSolucion` int DEFAULT NULL,
  `detalleResponsableSolucion` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `justificacion` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `descripcion` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `incidenciaAlcance` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `tiempoCronograma` float DEFAULT NULL,
  `tiempoCronogramaAfectado` float DEFAULT NULL,
  `incidenciaCronograma` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `valorPresupuesto` float DEFAULT NULL,
  `costoDirecto` float DEFAULT NULL,
  `costoDirectoAIU` float DEFAULT NULL,
  `costoDirectoAIUIVA` float DEFAULT NULL,
  `valorAprobado` float DEFAULT NULL,
  `incidenciaPresupuesto` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `incidenciaCalidad` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `incidenciaRiesgo` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `incidenciaRecurso` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `fechaTentativaDefinicion` date DEFAULT NULL,
  `fechaEntregaInterventoria` date DEFAULT NULL,
  `Observaciones` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `fechaDefinicion` date DEFAULT NULL,
  `aprobacion` int DEFAULT NULL,
  `soportes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metrolineaConfinamientoDos_cic`
--

CREATE TABLE `metrolineaConfinamientoDos_cic` (
  `Id` int NOT NULL,
  `Semana` int DEFAULT NULL,
  `subcontratista` varchar(200) DEFAULT NULL,
  `correo_contacto` varchar(200) DEFAULT NULL,
  `NIT` varchar(10) DEFAULT NULL,
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
  `Observaciones` mediumtext,
  `mdo_cal_1` varchar(5) DEFAULT 'NR',
  `mdo_cal_2` varchar(5) DEFAULT 'NR',
  `mdo_cal_3` varchar(5) DEFAULT 'NR',
  `mdo_adm_1` varchar(5) DEFAULT 'NR',
  `mdo_adm_2` varchar(5) DEFAULT 'NR',
  `mdo_adm_3` varchar(5) DEFAULT 'NR',
  `mdo_adm_4` varchar(5) DEFAULT 'NR',
  `mdo_adm_5` varchar(5) DEFAULT 'NR',
  `mdo_gsa_1` varchar(5) DEFAULT 'NR',
  `mdo_gsa_2` varchar(5) DEFAULT 'NR',
  `mdo_gsa_3` varchar(5) DEFAULT 'NR',
  `mdo_gsa_4` varchar(5) DEFAULT 'NR',
  `mdo_gsa_5` varchar(5) DEFAULT 'NR',
  `mdo_gsa_6` varchar(5) DEFAULT 'NR',
  `mdo_gsa_7` varchar(5) DEFAULT 'NR',
  `mdo_gsa_8` varchar(5) DEFAULT 'NR',
  `mdo_sst_1` varchar(5) DEFAULT 'NR',
  `mdo_sst_2` varchar(5) DEFAULT 'NR',
  `mdo_sst_3` varchar(5) DEFAULT 'NR',
  `mdo_sst_4` varchar(5) DEFAULT 'NR',
  `mdo_sst_5` varchar(5) DEFAULT 'NR',
  `mdo_sst_6` varchar(5) DEFAULT 'NR',
  `mdo_sst_7` varchar(5) DEFAULT 'NR',
  `mdo_sst_8` varchar(5) DEFAULT 'NR',
  `mdo_sst_9` varchar(5) DEFAULT 'NR',
  `mdo_sst_10` varchar(5) DEFAULT 'NR',
  `si_cal_1` varchar(5) DEFAULT 'NR',
  `si_cal_2` varchar(5) DEFAULT 'NR',
  `si_cal_3` varchar(5) DEFAULT 'NR',
  `si_adm_1` varchar(5) DEFAULT 'NR',
  `si_adm_2` varchar(5) DEFAULT 'NR',
  `si_adm_3` varchar(5) DEFAULT 'NR',
  `si_adm_4` varchar(5) DEFAULT 'NR',
  `si_adm_5` varchar(5) DEFAULT 'NR',
  `si_adm_6` varchar(5) DEFAULT 'NR',
  `si_gsa_1` varchar(5) DEFAULT 'NR',
  `si_gsa_2` varchar(5) DEFAULT 'NR',
  `si_gsa_3` varchar(5) DEFAULT 'NR',
  `si_gsa_4` varchar(5) DEFAULT 'NR',
  `si_gsa_5` varchar(5) DEFAULT 'NR',
  `si_gsa_6` varchar(5) DEFAULT 'NR',
  `si_gsa_7` varchar(5) DEFAULT 'NR',
  `si_gsa_8` varchar(5) DEFAULT 'NR',
  `si_gsa_9` varchar(5) DEFAULT 'NR',
  `si_gsa_10` varchar(5) DEFAULT 'NR',
  `si_gsa_11` varchar(5) DEFAULT 'NR',
  `si_gsa_12` varchar(5) DEFAULT 'NR',
  `si_gsa_13` varchar(5) DEFAULT 'NR',
  `si_gsa_14` varchar(5) DEFAULT 'NR',
  `si_sst_1` varchar(5) DEFAULT 'NR',
  `si_sst_2` varchar(5) DEFAULT 'NR',
  `si_sst_3` varchar(5) DEFAULT 'NR',
  `si_sst_4` varchar(5) DEFAULT 'NR',
  `si_sst_5` varchar(5) DEFAULT 'NR',
  `si_sst_6` varchar(5) DEFAULT 'NR',
  `si_sst_7` varchar(5) DEFAULT 'NR',
  `si_sst_8` varchar(5) DEFAULT 'NR',
  `si_sst_9` varchar(5) DEFAULT 'NR',
  `si_sst_10` varchar(5) DEFAULT 'NR'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metrolineaConfinamientoDos_pdc`
--

CREATE TABLE `metrolineaConfinamientoDos_pdc` (
  `consecutivo` int NOT NULL,
  `semana` int NOT NULL,
  `titulo` int NOT NULL,
  `tipoPaquete` varchar(200) NOT NULL,
  `paqueteContratacion` varchar(200) DEFAULT NULL,
  `contratos` varchar(200) DEFAULT NULL,
  `numeroSubcontratos` int DEFAULT '1',
  `subcontratoPaquete` int NOT NULL DEFAULT '1',
  `estado` varchar(200) DEFAULT NULL,
  `fechaElaboracionPliegos` date DEFAULT NULL,
  `diasElaboracionPliegos` int DEFAULT NULL,
  `fechaRealElaboracionPliegos` date DEFAULT NULL,
  `fechaIngresoLicify` date DEFAULT NULL,
  `diasIngresoLicify` int DEFAULT NULL,
  `fechaRealIngresoLicify` date DEFAULT NULL,
  `fechaEntregaPliegos` date DEFAULT NULL,
  `diasEntregaPliegos` int DEFAULT NULL,
  `fechaRealEntregaPliegos` date DEFAULT NULL,
  `fechaReciboPropuestas` date DEFAULT NULL,
  `diasReciboPropuestas` int DEFAULT NULL,
  `fechaRealReciboPropuestas` date DEFAULT NULL,
  `fechaCuadrosComparativos` date DEFAULT NULL,
  `diasCuadrosComparativos` int DEFAULT NULL,
  `fechaRealCuadrosComparativos` date DEFAULT NULL,
  `fechaLegalizacionContrato` date DEFAULT NULL,
  `diasLegalizacionContrato` int DEFAULT NULL,
  `fechaRealLegalizacionContrato` date DEFAULT NULL,
  `fechaFabricacion` date DEFAULT NULL,
  `diasFabricacion` int DEFAULT NULL,
  `fechaRealFabricacion` date DEFAULT NULL,
  `fechaInsumosObra` date DEFAULT NULL,
  `diasInsumosObra` int DEFAULT NULL,
  `fechaRealInsumosObra` date DEFAULT NULL,
  `fechaInicio` date DEFAULT NULL,
  `fechaInicioProyectada` date DEFAULT NULL,
  `fechaRealInicio` date DEFAULT NULL,
  `idProveedorAdjudicado` int DEFAULT NULL,
  `numeroContrato` varchar(50) DEFAULT NULL,
  `aplicaPolizas` int NOT NULL DEFAULT '1',
  `fechaVencimientoPolizas` date DEFAULT NULL,
  `valorPresupuesto` float DEFAULT NULL,
  `valorPrimeraNegociacion` float DEFAULT NULL,
  `valorAdjudicado` float DEFAULT NULL,
  `valorAnticipo` float DEFAULT NULL,
  `valorReclamado` float DEFAULT NULL,
  `valorDevoluciones` float DEFAULT NULL,
  `observacionesContrato` mediumtext
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metrolineaConfinamientoDos_pi_shared_constraints`
--

CREATE TABLE `metrolineaConfinamientoDos_pi_shared_constraints` (
  `Id` bigint UNSIGNED NOT NULL,
  `Semana` int NOT NULL,
  `Restriccion` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `ValorObjetivo` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `Nota` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `CreadoPor` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `CreadoEn` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ActualizadoEn` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metrolineaConfinamientoDos_pi_shared_constraint_links`
--

CREATE TABLE `metrolineaConfinamientoDos_pi_shared_constraint_links` (
  `Id` bigint UNSIGNED NOT NULL,
  `SharedConstraintId` bigint UNSIGNED NOT NULL,
  `Semana` int NOT NULL,
  `ConsecutivoEnPrograma` bigint NOT NULL,
  `ValorAplicado` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `OverrideLocal` tinyint(1) NOT NULL DEFAULT '0',
  `AplicadoEn` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metrolineaConfinamientoDos_profesionales`
--

CREATE TABLE `metrolineaConfinamientoDos_profesionales` (
  `id` int NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `cargo` varchar(100) NOT NULL,
  `activo` int NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metrolineaConfinamientoDos_programa`
--

CREATE TABLE `metrolineaConfinamientoDos_programa` (
  `Consecutivo` int NOT NULL,
  `Id` varchar(500) DEFAULT NULL,
  `Actividad` varchar(500) DEFAULT NULL,
  `Titulo` int DEFAULT NULL,
  `Fecha_Inicio` date DEFAULT NULL,
  `Fecha_Fin` date DEFAULT NULL,
  `Ruta_Critica` int DEFAULT NULL,
  `Ejecutado` float DEFAULT '0',
  `Estado` varchar(50) DEFAULT NULL,
  `Semanas_Inicio` int DEFAULT '0',
  `Estado_Restricciones` float DEFAULT '0',
  `D_y_E` float DEFAULT '0',
  `Materiales` float DEFAULT '0',
  `MdeO` float DEFAULT '0',
  `Equipos` float DEFAULT '0',
  `Predecesora` float DEFAULT '0',
  `Pdto_Cons` float DEFAULT '0',
  `Modelo` varchar(9) DEFAULT '0',
  `Responsable_AIA` varchar(100) DEFAULT NULL,
  `Observaciones` mediumtext,
  `Ult_Act_Est` date DEFAULT NULL,
  `Ult_Act_Restr` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metrolineaConfinamientoDos_programacion_semanal`
--

CREATE TABLE `metrolineaConfinamientoDos_programacion_semanal` (
  `Consecutivo` int NOT NULL,
  `Semana` int DEFAULT NULL,
  `Consecutivo_En_Programa` int NOT NULL,
  `Id` varchar(500) DEFAULT NULL,
  `Actividad` varchar(500) DEFAULT NULL,
  `Descripcion` mediumtext,
  `Ubicacion` mediumtext,
  `Fecha_Inicio` date DEFAULT NULL,
  `Fecha_Fin` date DEFAULT NULL,
  `Sub_Contratista` varchar(200) DEFAULT NULL,
  `Responsable_AIA` varchar(200) DEFAULT NULL,
  `Empresa` varchar(200) NOT NULL DEFAULT 'AIA',
  `Ejecutado` float DEFAULT NULL,
  `medir_productividad` int DEFAULT '0',
  `Unidad` varchar(10) DEFAULT NULL,
  `cantidad_ppto` int DEFAULT NULL,
  `Cantidad_Sugerida` float DEFAULT NULL,
  `Compromiso` float DEFAULT NULL,
  `Ejecutado_Real` float DEFAULT NULL,
  `P_Completado` float DEFAULT NULL,
  `PAC` int DEFAULT NULL,
  `Critica` int DEFAULT NULL,
  `Atrasada` int DEFAULT NULL,
  `Activa` varchar(3) DEFAULT NULL,
  `Prog_Sin_Restricciones_100` int DEFAULT NULL,
  `Categoria_CNP` varchar(100) DEFAULT NULL,
  `CNP` varchar(100) DEFAULT NULL,
  `Observaciones_CNP` mediumtext,
  `Categoria_CNC` varchar(100) DEFAULT NULL,
  `CNC` varchar(100) DEFAULT NULL,
  `Observaciones_CNC` mediumtext,
  `Rendimientos` varchar(500) DEFAULT NULL,
  `codigo_actividad` varchar(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metrolineaConfinamientoDos_programa_consolidado`
--

CREATE TABLE `metrolineaConfinamientoDos_programa_consolidado` (
  `Consecutivo` int NOT NULL,
  `Semana` int NOT NULL,
  `Consecutivo_en_Programa` int NOT NULL,
  `Id` varchar(500) DEFAULT NULL,
  `Actividad` varchar(500) DEFAULT NULL,
  `Titulo` int DEFAULT NULL,
  `Fecha_Inicio` date DEFAULT NULL,
  `Fecha_Fin` date DEFAULT NULL,
  `Ruta_Critica` int DEFAULT NULL,
  `Ejecutado` float DEFAULT '0',
  `Estado` varchar(100) DEFAULT NULL,
  `Semanas_Inicio` int DEFAULT '0',
  `Estado_Restricciones` float NOT NULL DEFAULT '0',
  `D_y_E` varchar(9) NOT NULL DEFAULT '0',
  `Materiales` varchar(9) NOT NULL DEFAULT '0',
  `MdeO` varchar(9) NOT NULL DEFAULT '0',
  `Equipos` varchar(9) NOT NULL DEFAULT '0',
  `Predecesora` varchar(9) NOT NULL DEFAULT '0',
  `Pdto_Cons` varchar(9) NOT NULL DEFAULT '0',
  `Modelo` varchar(9) NOT NULL DEFAULT '0',
  `Sub_Contratista` varchar(100) DEFAULT NULL,
  `Responsable_AIA` varchar(100) DEFAULT NULL,
  `Observaciones` mediumtext,
  `Ult_Act_Est` date DEFAULT NULL,
  `Ult_Act_Restr` date DEFAULT NULL,
  `Activa` int NOT NULL DEFAULT '0',
  `Ejecutado_Siguiente_Semana` float DEFAULT NULL,
  `codigo_actividad` varchar(11) DEFAULT NULL,
  `medir_productividad` int DEFAULT '0',
  `cantidad_ppto` int DEFAULT NULL,
  `unidad` varchar(20) DEFAULT NULL,
  `programaAnteriorAsociar` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metrolineaConfinamientoDos_semanas_activas`
--

CREATE TABLE `metrolineaConfinamientoDos_semanas_activas` (
  `Id` int NOT NULL,
  `Semana` int NOT NULL,
  `Fecha_Inicio_Sem` date NOT NULL,
  `Fecha_Fin_Sem` date NOT NULL,
  `Semanal_Confirmada` int DEFAULT '0',
  `fechaCierreCompromisos` date DEFAULT NULL,
  `fechaCreacionSemana` date DEFAULT NULL,
  `reprogramacion` int NOT NULL DEFAULT '0',
  `diferenciaEstructuraCron` int NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metrolineaConfinamientoDos_subcontratistas`
--

CREATE TABLE `metrolineaConfinamientoDos_subcontratistas` (
  `Id` int NOT NULL,
  `subcontratista` varchar(200) NOT NULL,
  `correo_contacto` varchar(200) NOT NULL,
  `NIT` bigint NOT NULL,
  `alcance` varchar(200) NOT NULL,
  `tipo_proveedor` varchar(200) NOT NULL,
  `activo` int NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metrolineaDieciseisAscendente_actividades`
--

CREATE TABLE `metrolineaDieciseisAscendente_actividades` (
  `Id` int NOT NULL,
  `codigo` int NOT NULL,
  `actividad` varchar(300) NOT NULL,
  `descripcionActividad` mediumtext,
  `actividadInicio` varchar(500) DEFAULT NULL,
  `nombreActividadInicio` varchar(500) DEFAULT NULL,
  `fechaInicio` date DEFAULT NULL,
  `tipoContrato` varchar(10) DEFAULT NULL,
  `semanaActualizacion` int DEFAULT NULL,
  `SI1` varchar(200) DEFAULT NULL,
  `paqueteSI1` varchar(200) DEFAULT NULL,
  `SI2` varchar(200) DEFAULT NULL,
  `paqueteSI2` varchar(200) DEFAULT NULL,
  `SI3` varchar(200) DEFAULT NULL,
  `paqueteSI3` varchar(200) DEFAULT NULL,
  `SI4` varchar(200) DEFAULT NULL,
  `paqueteSI4` varchar(200) DEFAULT NULL,
  `SI5` varchar(200) DEFAULT NULL,
  `paqueteSI5` varchar(200) DEFAULT NULL,
  `S1` varchar(200) DEFAULT NULL,
  `paqueteS1` varchar(200) DEFAULT NULL,
  `S2` varchar(200) DEFAULT NULL,
  `paqueteS2` varchar(200) DEFAULT NULL,
  `S3` varchar(200) DEFAULT NULL,
  `paqueteS3` varchar(200) DEFAULT NULL,
  `S4` varchar(200) DEFAULT NULL,
  `paqueteS4` varchar(200) DEFAULT NULL,
  `S5` varchar(200) DEFAULT NULL,
  `paqueteS5` varchar(200) DEFAULT NULL,
  `MO1` varchar(200) DEFAULT NULL,
  `paqueteMO1` varchar(200) DEFAULT NULL,
  `MO2` varchar(200) DEFAULT NULL,
  `paqueteMO2` varchar(200) DEFAULT NULL,
  `MO3` varchar(200) DEFAULT NULL,
  `paqueteMO3` varchar(200) DEFAULT NULL,
  `MO4` varchar(200) DEFAULT NULL,
  `paqueteMO4` varchar(200) DEFAULT NULL,
  `MO5` varchar(200) DEFAULT NULL,
  `paqueteMO5` varchar(200) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metrolineaDieciseisAscendente_cambios`
--

CREATE TABLE `metrolineaDieciseisAscendente_cambios` (
  `id` int NOT NULL,
  `solicitanteCambio` int DEFAULT NULL,
  `detalleSolicitanteOtro` longtext,
  `fechaSolicitud` date DEFAULT NULL,
  `prioridad` int DEFAULT NULL,
  `tipoCambio` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `responsableSolucion` int DEFAULT NULL,
  `detalleResponsableSolucion` longtext,
  `justificacion` longtext,
  `descripcion` longtext,
  `incidenciaAlcance` longtext,
  `tiempoCronograma` float DEFAULT NULL,
  `tiempoCronogramaAfectado` float DEFAULT NULL,
  `incidenciaCronograma` longtext,
  `valorPresupuesto` float DEFAULT NULL,
  `costoDirecto` float DEFAULT NULL,
  `costoDirectoAIU` float DEFAULT NULL,
  `costoDirectoAIUIVA` float DEFAULT NULL,
  `valorAprobado` float DEFAULT NULL,
  `incidenciaPresupuesto` longtext,
  `incidenciaCalidad` longtext,
  `incidenciaRiesgo` longtext,
  `incidenciaRecurso` longtext,
  `fechaTentativaDefinicion` date DEFAULT NULL,
  `fechaEntregaInterventoria` date DEFAULT NULL,
  `Observaciones` longtext,
  `fechaDefinicion` date DEFAULT NULL,
  `aprobacion` int DEFAULT NULL,
  `soportes` longtext
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metrolineaDieciseisAscendente_cic`
--

CREATE TABLE `metrolineaDieciseisAscendente_cic` (
  `Id` int NOT NULL,
  `Semana` int DEFAULT NULL,
  `subcontratista` varchar(200) DEFAULT NULL,
  `correo_contacto` varchar(200) DEFAULT NULL,
  `NIT` varchar(10) DEFAULT NULL,
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
  `Observaciones` mediumtext,
  `mdo_cal_1` varchar(5) DEFAULT 'NR',
  `mdo_cal_2` varchar(5) DEFAULT 'NR',
  `mdo_cal_3` varchar(5) DEFAULT 'NR',
  `mdo_adm_1` varchar(5) DEFAULT 'NR',
  `mdo_adm_2` varchar(5) DEFAULT 'NR',
  `mdo_adm_3` varchar(5) DEFAULT 'NR',
  `mdo_adm_4` varchar(5) DEFAULT 'NR',
  `mdo_adm_5` varchar(5) DEFAULT 'NR',
  `mdo_gsa_1` varchar(5) DEFAULT 'NR',
  `mdo_gsa_2` varchar(5) DEFAULT 'NR',
  `mdo_gsa_3` varchar(5) DEFAULT 'NR',
  `mdo_gsa_4` varchar(5) DEFAULT 'NR',
  `mdo_gsa_5` varchar(5) DEFAULT 'NR',
  `mdo_gsa_6` varchar(5) DEFAULT 'NR',
  `mdo_gsa_7` varchar(5) DEFAULT 'NR',
  `mdo_gsa_8` varchar(5) DEFAULT 'NR',
  `mdo_sst_1` varchar(5) DEFAULT 'NR',
  `mdo_sst_2` varchar(5) DEFAULT 'NR',
  `mdo_sst_3` varchar(5) DEFAULT 'NR',
  `mdo_sst_4` varchar(5) DEFAULT 'NR',
  `mdo_sst_5` varchar(5) DEFAULT 'NR',
  `mdo_sst_6` varchar(5) DEFAULT 'NR',
  `mdo_sst_7` varchar(5) DEFAULT 'NR',
  `mdo_sst_8` varchar(5) DEFAULT 'NR',
  `mdo_sst_9` varchar(5) DEFAULT 'NR',
  `mdo_sst_10` varchar(5) DEFAULT 'NR',
  `si_cal_1` varchar(5) DEFAULT 'NR',
  `si_cal_2` varchar(5) DEFAULT 'NR',
  `si_cal_3` varchar(5) DEFAULT 'NR',
  `si_adm_1` varchar(5) DEFAULT 'NR',
  `si_adm_2` varchar(5) DEFAULT 'NR',
  `si_adm_3` varchar(5) DEFAULT 'NR',
  `si_adm_4` varchar(5) DEFAULT 'NR',
  `si_adm_5` varchar(5) DEFAULT 'NR',
  `si_adm_6` varchar(5) DEFAULT 'NR',
  `si_gsa_1` varchar(5) DEFAULT 'NR',
  `si_gsa_2` varchar(5) DEFAULT 'NR',
  `si_gsa_3` varchar(5) DEFAULT 'NR',
  `si_gsa_4` varchar(5) DEFAULT 'NR',
  `si_gsa_5` varchar(5) DEFAULT 'NR',
  `si_gsa_6` varchar(5) DEFAULT 'NR',
  `si_gsa_7` varchar(5) DEFAULT 'NR',
  `si_gsa_8` varchar(5) DEFAULT 'NR',
  `si_gsa_9` varchar(5) DEFAULT 'NR',
  `si_gsa_10` varchar(5) DEFAULT 'NR',
  `si_gsa_11` varchar(5) DEFAULT 'NR',
  `si_gsa_12` varchar(5) DEFAULT 'NR',
  `si_gsa_13` varchar(5) DEFAULT 'NR',
  `si_gsa_14` varchar(5) DEFAULT 'NR',
  `si_sst_1` varchar(5) DEFAULT 'NR',
  `si_sst_2` varchar(5) DEFAULT 'NR',
  `si_sst_3` varchar(5) DEFAULT 'NR',
  `si_sst_4` varchar(5) DEFAULT 'NR',
  `si_sst_5` varchar(5) DEFAULT 'NR',
  `si_sst_6` varchar(5) DEFAULT 'NR',
  `si_sst_7` varchar(5) DEFAULT 'NR',
  `si_sst_8` varchar(5) DEFAULT 'NR',
  `si_sst_9` varchar(5) DEFAULT 'NR',
  `si_sst_10` varchar(5) DEFAULT 'NR'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metrolineaDieciseisAscendente_pdc`
--

CREATE TABLE `metrolineaDieciseisAscendente_pdc` (
  `consecutivo` int NOT NULL,
  `semana` int NOT NULL,
  `titulo` int NOT NULL,
  `tipoPaquete` varchar(200) NOT NULL,
  `paqueteContratacion` varchar(200) DEFAULT NULL,
  `contratos` varchar(200) DEFAULT NULL,
  `numeroSubcontratos` int DEFAULT '1',
  `subcontratoPaquete` int NOT NULL DEFAULT '1',
  `estado` varchar(200) DEFAULT NULL,
  `fechaElaboracionPliegos` date DEFAULT NULL,
  `diasElaboracionPliegos` int DEFAULT NULL,
  `fechaRealElaboracionPliegos` date DEFAULT NULL,
  `fechaIngresoLicify` date DEFAULT NULL,
  `diasIngresoLicify` int DEFAULT NULL,
  `fechaRealIngresoLicify` date DEFAULT NULL,
  `fechaEntregaPliegos` date DEFAULT NULL,
  `diasEntregaPliegos` int DEFAULT NULL,
  `fechaRealEntregaPliegos` date DEFAULT NULL,
  `fechaReciboPropuestas` date DEFAULT NULL,
  `diasReciboPropuestas` int DEFAULT NULL,
  `fechaRealReciboPropuestas` date DEFAULT NULL,
  `fechaCuadrosComparativos` date DEFAULT NULL,
  `diasCuadrosComparativos` int DEFAULT NULL,
  `fechaRealCuadrosComparativos` date DEFAULT NULL,
  `fechaLegalizacionContrato` date DEFAULT NULL,
  `diasLegalizacionContrato` int DEFAULT NULL,
  `fechaRealLegalizacionContrato` date DEFAULT NULL,
  `fechaFabricacion` date DEFAULT NULL,
  `diasFabricacion` int DEFAULT NULL,
  `fechaRealFabricacion` date DEFAULT NULL,
  `fechaInsumosObra` date DEFAULT NULL,
  `diasInsumosObra` int DEFAULT NULL,
  `fechaRealInsumosObra` date DEFAULT NULL,
  `fechaInicio` date DEFAULT NULL,
  `fechaInicioProyectada` date DEFAULT NULL,
  `fechaRealInicio` date DEFAULT NULL,
  `idProveedorAdjudicado` int DEFAULT NULL,
  `numeroContrato` varchar(50) DEFAULT NULL,
  `aplicaPolizas` int NOT NULL DEFAULT '1',
  `fechaVencimientoPolizas` date DEFAULT NULL,
  `valorPresupuesto` float DEFAULT NULL,
  `valorPrimeraNegociacion` float DEFAULT NULL,
  `valorAdjudicado` float DEFAULT NULL,
  `valorAnticipo` float DEFAULT NULL,
  `valorReclamado` float DEFAULT NULL,
  `valorDevoluciones` float DEFAULT NULL,
  `observacionesContrato` mediumtext
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metrolineaDieciseisAscendente_pi_shared_constraints`
--

CREATE TABLE `metrolineaDieciseisAscendente_pi_shared_constraints` (
  `Id` bigint UNSIGNED NOT NULL,
  `Semana` int NOT NULL,
  `Restriccion` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `ValorObjetivo` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `Nota` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `CreadoPor` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `CreadoEn` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ActualizadoEn` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metrolineaDieciseisAscendente_pi_shared_constraint_links`
--

CREATE TABLE `metrolineaDieciseisAscendente_pi_shared_constraint_links` (
  `Id` bigint UNSIGNED NOT NULL,
  `SharedConstraintId` bigint UNSIGNED NOT NULL,
  `Semana` int NOT NULL,
  `ConsecutivoEnPrograma` bigint NOT NULL,
  `ValorAplicado` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `OverrideLocal` tinyint(1) NOT NULL DEFAULT '0',
  `AplicadoEn` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metrolineaDieciseisAscendente_profesionales`
--

CREATE TABLE `metrolineaDieciseisAscendente_profesionales` (
  `id` int NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `cargo` varchar(100) NOT NULL,
  `activo` int NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metrolineaDieciseisAscendente_programa`
--

CREATE TABLE `metrolineaDieciseisAscendente_programa` (
  `Consecutivo` int NOT NULL,
  `Id` varchar(500) DEFAULT NULL,
  `Actividad` varchar(500) DEFAULT NULL,
  `Titulo` int DEFAULT NULL,
  `Fecha_Inicio` date DEFAULT NULL,
  `Fecha_Fin` date DEFAULT NULL,
  `Ruta_Critica` int DEFAULT NULL,
  `Ejecutado` float DEFAULT '0',
  `Estado` varchar(50) DEFAULT NULL,
  `Semanas_Inicio` int DEFAULT '0',
  `Estado_Restricciones` float DEFAULT '0',
  `D_y_E` float DEFAULT '0',
  `Materiales` float DEFAULT '0',
  `MdeO` float DEFAULT '0',
  `Equipos` float DEFAULT '0',
  `Predecesora` float DEFAULT '0',
  `Pdto_Cons` float DEFAULT '0',
  `Modelo` varchar(9) DEFAULT '0',
  `Responsable_AIA` varchar(100) DEFAULT NULL,
  `Observaciones` mediumtext,
  `Ult_Act_Est` date DEFAULT NULL,
  `Ult_Act_Restr` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metrolineaDieciseisAscendente_programacion_semanal`
--

CREATE TABLE `metrolineaDieciseisAscendente_programacion_semanal` (
  `Consecutivo` int NOT NULL,
  `Semana` int DEFAULT NULL,
  `Consecutivo_En_Programa` int NOT NULL,
  `Id` varchar(500) DEFAULT NULL,
  `Actividad` varchar(500) DEFAULT NULL,
  `Descripcion` mediumtext,
  `Ubicacion` mediumtext,
  `Fecha_Inicio` date DEFAULT NULL,
  `Fecha_Fin` date DEFAULT NULL,
  `Sub_Contratista` varchar(200) DEFAULT NULL,
  `Responsable_AIA` varchar(200) DEFAULT NULL,
  `Empresa` varchar(200) NOT NULL DEFAULT 'AIA',
  `Ejecutado` float DEFAULT NULL,
  `medir_productividad` int DEFAULT '0',
  `Unidad` varchar(10) DEFAULT NULL,
  `cantidad_ppto` int DEFAULT NULL,
  `Cantidad_Sugerida` float DEFAULT NULL,
  `Compromiso` float DEFAULT NULL,
  `Ejecutado_Real` float DEFAULT NULL,
  `P_Completado` float DEFAULT NULL,
  `PAC` int DEFAULT NULL,
  `Critica` int DEFAULT NULL,
  `Atrasada` int DEFAULT NULL,
  `Activa` varchar(3) DEFAULT NULL,
  `Prog_Sin_Restricciones_100` int DEFAULT NULL,
  `Categoria_CNP` varchar(100) DEFAULT NULL,
  `CNP` varchar(100) DEFAULT NULL,
  `Observaciones_CNP` mediumtext,
  `Categoria_CNC` varchar(100) DEFAULT NULL,
  `CNC` varchar(100) DEFAULT NULL,
  `Observaciones_CNC` mediumtext,
  `Rendimientos` varchar(500) DEFAULT NULL,
  `codigo_actividad` varchar(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metrolineaDieciseisAscendente_programa_consolidado`
--

CREATE TABLE `metrolineaDieciseisAscendente_programa_consolidado` (
  `Consecutivo` int NOT NULL,
  `Semana` int NOT NULL,
  `Consecutivo_en_Programa` int NOT NULL,
  `Id` varchar(500) DEFAULT NULL,
  `Actividad` varchar(500) DEFAULT NULL,
  `Titulo` int DEFAULT NULL,
  `Fecha_Inicio` date DEFAULT NULL,
  `Fecha_Fin` date DEFAULT NULL,
  `Ruta_Critica` int DEFAULT NULL,
  `Ejecutado` float DEFAULT '0',
  `Estado` varchar(100) DEFAULT NULL,
  `Semanas_Inicio` int DEFAULT '0',
  `Estado_Restricciones` float NOT NULL DEFAULT '0',
  `D_y_E` varchar(9) NOT NULL DEFAULT '0',
  `Materiales` varchar(9) NOT NULL DEFAULT '0',
  `MdeO` varchar(9) NOT NULL DEFAULT '0',
  `Equipos` varchar(9) NOT NULL DEFAULT '0',
  `Predecesora` varchar(9) NOT NULL DEFAULT '0',
  `Pdto_Cons` varchar(9) NOT NULL DEFAULT '0',
  `Modelo` varchar(9) NOT NULL DEFAULT '0',
  `Sub_Contratista` varchar(100) DEFAULT NULL,
  `Responsable_AIA` varchar(100) DEFAULT NULL,
  `Observaciones` mediumtext,
  `Ult_Act_Est` date DEFAULT NULL,
  `Ult_Act_Restr` date DEFAULT NULL,
  `Activa` int NOT NULL DEFAULT '0',
  `Ejecutado_Siguiente_Semana` float DEFAULT NULL,
  `codigo_actividad` varchar(11) DEFAULT NULL,
  `medir_productividad` int DEFAULT '0',
  `cantidad_ppto` int DEFAULT NULL,
  `unidad` varchar(20) DEFAULT NULL,
  `programaAnteriorAsociar` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metrolineaDieciseisAscendente_semanas_activas`
--

CREATE TABLE `metrolineaDieciseisAscendente_semanas_activas` (
  `Id` int NOT NULL,
  `Semana` int NOT NULL,
  `Fecha_Inicio_Sem` date NOT NULL,
  `Fecha_Fin_Sem` date NOT NULL,
  `Semanal_Confirmada` int DEFAULT '0',
  `fechaCierreCompromisos` date DEFAULT NULL,
  `fechaCreacionSemana` date DEFAULT NULL,
  `reprogramacion` int NOT NULL DEFAULT '0',
  `diferenciaEstructuraCron` int NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metrolineaDieciseisAscendente_subcontratistas`
--

CREATE TABLE `metrolineaDieciseisAscendente_subcontratistas` (
  `Id` int NOT NULL,
  `subcontratista` varchar(200) NOT NULL,
  `correo_contacto` varchar(200) NOT NULL,
  `NIT` bigint NOT NULL,
  `alcance` varchar(200) NOT NULL,
  `tipo_proveedor` varchar(200) NOT NULL,
  `activo` int NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metrolineaDieciseisDescendente_actividades`
--

CREATE TABLE `metrolineaDieciseisDescendente_actividades` (
  `Id` int NOT NULL,
  `codigo` int NOT NULL,
  `actividad` varchar(300) NOT NULL,
  `descripcionActividad` mediumtext,
  `actividadInicio` varchar(500) DEFAULT NULL,
  `nombreActividadInicio` varchar(500) DEFAULT NULL,
  `fechaInicio` date DEFAULT NULL,
  `tipoContrato` varchar(10) DEFAULT NULL,
  `semanaActualizacion` int DEFAULT NULL,
  `SI1` varchar(200) DEFAULT NULL,
  `paqueteSI1` varchar(200) DEFAULT NULL,
  `SI2` varchar(200) DEFAULT NULL,
  `paqueteSI2` varchar(200) DEFAULT NULL,
  `SI3` varchar(200) DEFAULT NULL,
  `paqueteSI3` varchar(200) DEFAULT NULL,
  `SI4` varchar(200) DEFAULT NULL,
  `paqueteSI4` varchar(200) DEFAULT NULL,
  `SI5` varchar(200) DEFAULT NULL,
  `paqueteSI5` varchar(200) DEFAULT NULL,
  `S1` varchar(200) DEFAULT NULL,
  `paqueteS1` varchar(200) DEFAULT NULL,
  `S2` varchar(200) DEFAULT NULL,
  `paqueteS2` varchar(200) DEFAULT NULL,
  `S3` varchar(200) DEFAULT NULL,
  `paqueteS3` varchar(200) DEFAULT NULL,
  `S4` varchar(200) DEFAULT NULL,
  `paqueteS4` varchar(200) DEFAULT NULL,
  `S5` varchar(200) DEFAULT NULL,
  `paqueteS5` varchar(200) DEFAULT NULL,
  `MO1` varchar(200) DEFAULT NULL,
  `paqueteMO1` varchar(200) DEFAULT NULL,
  `MO2` varchar(200) DEFAULT NULL,
  `paqueteMO2` varchar(200) DEFAULT NULL,
  `MO3` varchar(200) DEFAULT NULL,
  `paqueteMO3` varchar(200) DEFAULT NULL,
  `MO4` varchar(200) DEFAULT NULL,
  `paqueteMO4` varchar(200) DEFAULT NULL,
  `MO5` varchar(200) DEFAULT NULL,
  `paqueteMO5` varchar(200) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metrolineaDieciseisDescendente_cambios`
--

CREATE TABLE `metrolineaDieciseisDescendente_cambios` (
  `id` int NOT NULL,
  `solicitanteCambio` int DEFAULT NULL,
  `detalleSolicitanteOtro` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `fechaSolicitud` date DEFAULT NULL,
  `prioridad` int DEFAULT NULL,
  `tipoCambio` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `responsableSolucion` int DEFAULT NULL,
  `detalleResponsableSolucion` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `justificacion` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `descripcion` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `incidenciaAlcance` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `tiempoCronograma` float DEFAULT NULL,
  `tiempoCronogramaAfectado` float DEFAULT NULL,
  `incidenciaCronograma` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `valorPresupuesto` float DEFAULT NULL,
  `costoDirecto` float DEFAULT NULL,
  `costoDirectoAIU` float DEFAULT NULL,
  `costoDirectoAIUIVA` float DEFAULT NULL,
  `valorAprobado` float DEFAULT NULL,
  `incidenciaPresupuesto` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `incidenciaCalidad` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `incidenciaRiesgo` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `incidenciaRecurso` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `fechaTentativaDefinicion` date DEFAULT NULL,
  `fechaEntregaInterventoria` date DEFAULT NULL,
  `Observaciones` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `fechaDefinicion` date DEFAULT NULL,
  `aprobacion` int DEFAULT NULL,
  `soportes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metrolineaDieciseisDescendente_cic`
--

CREATE TABLE `metrolineaDieciseisDescendente_cic` (
  `Id` int NOT NULL,
  `Semana` int DEFAULT NULL,
  `subcontratista` varchar(200) DEFAULT NULL,
  `correo_contacto` varchar(200) DEFAULT NULL,
  `NIT` varchar(10) DEFAULT NULL,
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
  `Observaciones` mediumtext,
  `mdo_cal_1` varchar(5) DEFAULT 'NR',
  `mdo_cal_2` varchar(5) DEFAULT 'NR',
  `mdo_cal_3` varchar(5) DEFAULT 'NR',
  `mdo_adm_1` varchar(5) DEFAULT 'NR',
  `mdo_adm_2` varchar(5) DEFAULT 'NR',
  `mdo_adm_3` varchar(5) DEFAULT 'NR',
  `mdo_adm_4` varchar(5) DEFAULT 'NR',
  `mdo_adm_5` varchar(5) DEFAULT 'NR',
  `mdo_gsa_1` varchar(5) DEFAULT 'NR',
  `mdo_gsa_2` varchar(5) DEFAULT 'NR',
  `mdo_gsa_3` varchar(5) DEFAULT 'NR',
  `mdo_gsa_4` varchar(5) DEFAULT 'NR',
  `mdo_gsa_5` varchar(5) DEFAULT 'NR',
  `mdo_gsa_6` varchar(5) DEFAULT 'NR',
  `mdo_gsa_7` varchar(5) DEFAULT 'NR',
  `mdo_gsa_8` varchar(5) DEFAULT 'NR',
  `mdo_sst_1` varchar(5) DEFAULT 'NR',
  `mdo_sst_2` varchar(5) DEFAULT 'NR',
  `mdo_sst_3` varchar(5) DEFAULT 'NR',
  `mdo_sst_4` varchar(5) DEFAULT 'NR',
  `mdo_sst_5` varchar(5) DEFAULT 'NR',
  `mdo_sst_6` varchar(5) DEFAULT 'NR',
  `mdo_sst_7` varchar(5) DEFAULT 'NR',
  `mdo_sst_8` varchar(5) DEFAULT 'NR',
  `mdo_sst_9` varchar(5) DEFAULT 'NR',
  `mdo_sst_10` varchar(5) DEFAULT 'NR',
  `si_cal_1` varchar(5) DEFAULT 'NR',
  `si_cal_2` varchar(5) DEFAULT 'NR',
  `si_cal_3` varchar(5) DEFAULT 'NR',
  `si_adm_1` varchar(5) DEFAULT 'NR',
  `si_adm_2` varchar(5) DEFAULT 'NR',
  `si_adm_3` varchar(5) DEFAULT 'NR',
  `si_adm_4` varchar(5) DEFAULT 'NR',
  `si_adm_5` varchar(5) DEFAULT 'NR',
  `si_adm_6` varchar(5) DEFAULT 'NR',
  `si_gsa_1` varchar(5) DEFAULT 'NR',
  `si_gsa_2` varchar(5) DEFAULT 'NR',
  `si_gsa_3` varchar(5) DEFAULT 'NR',
  `si_gsa_4` varchar(5) DEFAULT 'NR',
  `si_gsa_5` varchar(5) DEFAULT 'NR',
  `si_gsa_6` varchar(5) DEFAULT 'NR',
  `si_gsa_7` varchar(5) DEFAULT 'NR',
  `si_gsa_8` varchar(5) DEFAULT 'NR',
  `si_gsa_9` varchar(5) DEFAULT 'NR',
  `si_gsa_10` varchar(5) DEFAULT 'NR',
  `si_gsa_11` varchar(5) DEFAULT 'NR',
  `si_gsa_12` varchar(5) DEFAULT 'NR',
  `si_gsa_13` varchar(5) DEFAULT 'NR',
  `si_gsa_14` varchar(5) DEFAULT 'NR',
  `si_sst_1` varchar(5) DEFAULT 'NR',
  `si_sst_2` varchar(5) DEFAULT 'NR',
  `si_sst_3` varchar(5) DEFAULT 'NR',
  `si_sst_4` varchar(5) DEFAULT 'NR',
  `si_sst_5` varchar(5) DEFAULT 'NR',
  `si_sst_6` varchar(5) DEFAULT 'NR',
  `si_sst_7` varchar(5) DEFAULT 'NR',
  `si_sst_8` varchar(5) DEFAULT 'NR',
  `si_sst_9` varchar(5) DEFAULT 'NR',
  `si_sst_10` varchar(5) DEFAULT 'NR'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metrolineaDieciseisDescendente_pdc`
--

CREATE TABLE `metrolineaDieciseisDescendente_pdc` (
  `consecutivo` int NOT NULL,
  `semana` int NOT NULL,
  `titulo` int NOT NULL,
  `tipoPaquete` varchar(200) NOT NULL,
  `paqueteContratacion` varchar(200) DEFAULT NULL,
  `contratos` varchar(200) DEFAULT NULL,
  `numeroSubcontratos` int DEFAULT '1',
  `subcontratoPaquete` int NOT NULL DEFAULT '1',
  `estado` varchar(200) DEFAULT NULL,
  `fechaElaboracionPliegos` date DEFAULT NULL,
  `diasElaboracionPliegos` int DEFAULT NULL,
  `fechaRealElaboracionPliegos` date DEFAULT NULL,
  `fechaIngresoLicify` date DEFAULT NULL,
  `diasIngresoLicify` int DEFAULT NULL,
  `fechaRealIngresoLicify` date DEFAULT NULL,
  `fechaEntregaPliegos` date DEFAULT NULL,
  `diasEntregaPliegos` int DEFAULT NULL,
  `fechaRealEntregaPliegos` date DEFAULT NULL,
  `fechaReciboPropuestas` date DEFAULT NULL,
  `diasReciboPropuestas` int DEFAULT NULL,
  `fechaRealReciboPropuestas` date DEFAULT NULL,
  `fechaCuadrosComparativos` date DEFAULT NULL,
  `diasCuadrosComparativos` int DEFAULT NULL,
  `fechaRealCuadrosComparativos` date DEFAULT NULL,
  `fechaLegalizacionContrato` date DEFAULT NULL,
  `diasLegalizacionContrato` int DEFAULT NULL,
  `fechaRealLegalizacionContrato` date DEFAULT NULL,
  `fechaFabricacion` date DEFAULT NULL,
  `diasFabricacion` int DEFAULT NULL,
  `fechaRealFabricacion` date DEFAULT NULL,
  `fechaInsumosObra` date DEFAULT NULL,
  `diasInsumosObra` int DEFAULT NULL,
  `fechaRealInsumosObra` date DEFAULT NULL,
  `fechaInicio` date DEFAULT NULL,
  `fechaInicioProyectada` date DEFAULT NULL,
  `fechaRealInicio` date DEFAULT NULL,
  `idProveedorAdjudicado` int DEFAULT NULL,
  `numeroContrato` varchar(50) DEFAULT NULL,
  `aplicaPolizas` int NOT NULL DEFAULT '1',
  `fechaVencimientoPolizas` date DEFAULT NULL,
  `valorPresupuesto` float DEFAULT NULL,
  `valorPrimeraNegociacion` float DEFAULT NULL,
  `valorAdjudicado` float DEFAULT NULL,
  `valorAnticipo` float DEFAULT NULL,
  `valorReclamado` float DEFAULT NULL,
  `valorDevoluciones` float DEFAULT NULL,
  `observacionesContrato` mediumtext
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metrolineaDieciseisDescendente_pi_shared_constraints`
--

CREATE TABLE `metrolineaDieciseisDescendente_pi_shared_constraints` (
  `Id` bigint UNSIGNED NOT NULL,
  `Semana` int NOT NULL,
  `Restriccion` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `ValorObjetivo` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `Nota` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `CreadoPor` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `CreadoEn` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ActualizadoEn` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metrolineaDieciseisDescendente_pi_shared_constraint_links`
--

CREATE TABLE `metrolineaDieciseisDescendente_pi_shared_constraint_links` (
  `Id` bigint UNSIGNED NOT NULL,
  `SharedConstraintId` bigint UNSIGNED NOT NULL,
  `Semana` int NOT NULL,
  `ConsecutivoEnPrograma` bigint NOT NULL,
  `ValorAplicado` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `OverrideLocal` tinyint(1) NOT NULL DEFAULT '0',
  `AplicadoEn` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metrolineaDieciseisDescendente_profesionales`
--

CREATE TABLE `metrolineaDieciseisDescendente_profesionales` (
  `id` int NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `cargo` varchar(100) NOT NULL,
  `activo` int NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metrolineaDieciseisDescendente_programa`
--

CREATE TABLE `metrolineaDieciseisDescendente_programa` (
  `Consecutivo` int NOT NULL,
  `Id` varchar(500) DEFAULT NULL,
  `Actividad` varchar(500) DEFAULT NULL,
  `Titulo` int DEFAULT NULL,
  `Fecha_Inicio` date DEFAULT NULL,
  `Fecha_Fin` date DEFAULT NULL,
  `Ruta_Critica` int DEFAULT NULL,
  `Ejecutado` float DEFAULT '0',
  `Estado` varchar(50) DEFAULT NULL,
  `Semanas_Inicio` int DEFAULT '0',
  `Estado_Restricciones` float DEFAULT '0',
  `D_y_E` float DEFAULT '0',
  `Materiales` float DEFAULT '0',
  `MdeO` float DEFAULT '0',
  `Equipos` float DEFAULT '0',
  `Predecesora` float DEFAULT '0',
  `Pdto_Cons` float DEFAULT '0',
  `Modelo` varchar(9) DEFAULT '0',
  `Responsable_AIA` varchar(100) DEFAULT NULL,
  `Observaciones` mediumtext,
  `Ult_Act_Est` date DEFAULT NULL,
  `Ult_Act_Restr` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metrolineaDieciseisDescendente_programacion_semanal`
--

CREATE TABLE `metrolineaDieciseisDescendente_programacion_semanal` (
  `Consecutivo` int NOT NULL,
  `Semana` int DEFAULT NULL,
  `Consecutivo_En_Programa` int NOT NULL,
  `Id` varchar(500) DEFAULT NULL,
  `Actividad` varchar(500) DEFAULT NULL,
  `Descripcion` mediumtext,
  `Ubicacion` mediumtext,
  `Fecha_Inicio` date DEFAULT NULL,
  `Fecha_Fin` date DEFAULT NULL,
  `Sub_Contratista` varchar(200) DEFAULT NULL,
  `Responsable_AIA` varchar(200) DEFAULT NULL,
  `Empresa` varchar(200) NOT NULL DEFAULT 'AIA',
  `Ejecutado` float DEFAULT NULL,
  `medir_productividad` int DEFAULT '0',
  `Unidad` varchar(10) DEFAULT NULL,
  `cantidad_ppto` int DEFAULT NULL,
  `Cantidad_Sugerida` float DEFAULT NULL,
  `Compromiso` float DEFAULT NULL,
  `Ejecutado_Real` float DEFAULT NULL,
  `P_Completado` float DEFAULT NULL,
  `PAC` int DEFAULT NULL,
  `Critica` int DEFAULT NULL,
  `Atrasada` int DEFAULT NULL,
  `Activa` varchar(3) DEFAULT NULL,
  `Prog_Sin_Restricciones_100` int DEFAULT NULL,
  `Categoria_CNP` varchar(100) DEFAULT NULL,
  `CNP` varchar(100) DEFAULT NULL,
  `Observaciones_CNP` mediumtext,
  `Categoria_CNC` varchar(100) DEFAULT NULL,
  `CNC` varchar(100) DEFAULT NULL,
  `Observaciones_CNC` mediumtext,
  `Rendimientos` varchar(500) DEFAULT NULL,
  `codigo_actividad` varchar(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metrolineaDieciseisDescendente_programa_consolidado`
--

CREATE TABLE `metrolineaDieciseisDescendente_programa_consolidado` (
  `Consecutivo` int NOT NULL,
  `Semana` int NOT NULL,
  `Consecutivo_en_Programa` int NOT NULL,
  `Id` varchar(500) DEFAULT NULL,
  `Actividad` varchar(500) DEFAULT NULL,
  `Titulo` int DEFAULT NULL,
  `Fecha_Inicio` date DEFAULT NULL,
  `Fecha_Fin` date DEFAULT NULL,
  `Ruta_Critica` int DEFAULT NULL,
  `Ejecutado` float DEFAULT '0',
  `Estado` varchar(100) DEFAULT NULL,
  `Semanas_Inicio` int DEFAULT '0',
  `Estado_Restricciones` float NOT NULL DEFAULT '0',
  `D_y_E` varchar(9) NOT NULL DEFAULT '0',
  `Materiales` varchar(9) NOT NULL DEFAULT '0',
  `MdeO` varchar(9) NOT NULL DEFAULT '0',
  `Equipos` varchar(9) NOT NULL DEFAULT '0',
  `Predecesora` varchar(9) NOT NULL DEFAULT '0',
  `Pdto_Cons` varchar(9) NOT NULL DEFAULT '0',
  `Modelo` varchar(9) NOT NULL DEFAULT '0',
  `Sub_Contratista` varchar(100) DEFAULT NULL,
  `Responsable_AIA` varchar(100) DEFAULT NULL,
  `Observaciones` mediumtext,
  `Ult_Act_Est` date DEFAULT NULL,
  `Ult_Act_Restr` date DEFAULT NULL,
  `Activa` int NOT NULL DEFAULT '0',
  `Ejecutado_Siguiente_Semana` float DEFAULT NULL,
  `codigo_actividad` varchar(11) DEFAULT NULL,
  `medir_productividad` int DEFAULT '0',
  `cantidad_ppto` int DEFAULT NULL,
  `unidad` varchar(20) DEFAULT NULL,
  `programaAnteriorAsociar` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metrolineaDieciseisDescendente_semanas_activas`
--

CREATE TABLE `metrolineaDieciseisDescendente_semanas_activas` (
  `Id` int NOT NULL,
  `Semana` int NOT NULL,
  `Fecha_Inicio_Sem` date NOT NULL,
  `Fecha_Fin_Sem` date NOT NULL,
  `Semanal_Confirmada` int DEFAULT '0',
  `fechaCierreCompromisos` date DEFAULT NULL,
  `fechaCreacionSemana` date DEFAULT NULL,
  `reprogramacion` int NOT NULL DEFAULT '0',
  `diferenciaEstructuraCron` int NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metrolineaDieciseisDescendente_subcontratistas`
--

CREATE TABLE `metrolineaDieciseisDescendente_subcontratistas` (
  `Id` int NOT NULL,
  `subcontratista` varchar(200) NOT NULL,
  `correo_contacto` varchar(200) NOT NULL,
  `NIT` bigint NOT NULL,
  `alcance` varchar(200) NOT NULL,
  `tipo_proveedor` varchar(200) NOT NULL,
  `activo` int NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metrolineaDos_actividades`
--

CREATE TABLE `metrolineaDos_actividades` (
  `Id` int NOT NULL,
  `codigo` int NOT NULL,
  `actividad` varchar(300) NOT NULL,
  `descripcionActividad` mediumtext,
  `actividadInicio` varchar(500) DEFAULT NULL,
  `nombreActividadInicio` varchar(500) DEFAULT NULL,
  `fechaInicio` date DEFAULT NULL,
  `tipoContrato` varchar(10) DEFAULT NULL,
  `semanaActualizacion` int DEFAULT NULL,
  `SI1` varchar(200) DEFAULT NULL,
  `paqueteSI1` varchar(200) DEFAULT NULL,
  `SI2` varchar(200) DEFAULT NULL,
  `paqueteSI2` varchar(200) DEFAULT NULL,
  `SI3` varchar(200) DEFAULT NULL,
  `paqueteSI3` varchar(200) DEFAULT NULL,
  `SI4` varchar(200) DEFAULT NULL,
  `paqueteSI4` varchar(200) DEFAULT NULL,
  `SI5` varchar(200) DEFAULT NULL,
  `paqueteSI5` varchar(200) DEFAULT NULL,
  `S1` varchar(200) DEFAULT NULL,
  `paqueteS1` varchar(200) DEFAULT NULL,
  `S2` varchar(200) DEFAULT NULL,
  `paqueteS2` varchar(200) DEFAULT NULL,
  `S3` varchar(200) DEFAULT NULL,
  `paqueteS3` varchar(200) DEFAULT NULL,
  `S4` varchar(200) DEFAULT NULL,
  `paqueteS4` varchar(200) DEFAULT NULL,
  `S5` varchar(200) DEFAULT NULL,
  `paqueteS5` varchar(200) DEFAULT NULL,
  `MO1` varchar(200) DEFAULT NULL,
  `paqueteMO1` varchar(200) DEFAULT NULL,
  `MO2` varchar(200) DEFAULT NULL,
  `paqueteMO2` varchar(200) DEFAULT NULL,
  `MO3` varchar(200) DEFAULT NULL,
  `paqueteMO3` varchar(200) DEFAULT NULL,
  `MO4` varchar(200) DEFAULT NULL,
  `paqueteMO4` varchar(200) DEFAULT NULL,
  `MO5` varchar(200) DEFAULT NULL,
  `paqueteMO5` varchar(200) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metrolineaDos_cambios`
--

CREATE TABLE `metrolineaDos_cambios` (
  `id` int NOT NULL,
  `solicitanteCambio` int DEFAULT NULL,
  `detalleSolicitanteOtro` longtext,
  `fechaSolicitud` date DEFAULT NULL,
  `prioridad` int DEFAULT NULL,
  `tipoCambio` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `responsableSolucion` int DEFAULT NULL,
  `detalleResponsableSolucion` longtext,
  `justificacion` longtext,
  `descripcion` longtext,
  `incidenciaAlcance` longtext,
  `tiempoCronograma` float DEFAULT NULL,
  `tiempoCronogramaAfectado` float DEFAULT NULL,
  `incidenciaCronograma` longtext,
  `valorPresupuesto` float DEFAULT NULL,
  `costoDirecto` float DEFAULT NULL,
  `costoDirectoAIU` float DEFAULT NULL,
  `costoDirectoAIUIVA` float DEFAULT NULL,
  `valorAprobado` float DEFAULT NULL,
  `incidenciaPresupuesto` longtext,
  `incidenciaCalidad` longtext,
  `incidenciaRiesgo` longtext,
  `incidenciaRecurso` longtext,
  `fechaTentativaDefinicion` date DEFAULT NULL,
  `fechaEntregaInterventoria` date DEFAULT NULL,
  `Observaciones` longtext,
  `fechaDefinicion` date DEFAULT NULL,
  `aprobacion` int DEFAULT NULL,
  `soportes` longtext
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metrolineaDos_cic`
--

CREATE TABLE `metrolineaDos_cic` (
  `Id` int NOT NULL,
  `Semana` int DEFAULT NULL,
  `subcontratista` varchar(200) DEFAULT NULL,
  `correo_contacto` varchar(200) DEFAULT NULL,
  `NIT` varchar(10) DEFAULT NULL,
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
  `Observaciones` mediumtext,
  `mdo_cal_1` varchar(5) DEFAULT 'NR',
  `mdo_cal_2` varchar(5) DEFAULT 'NR',
  `mdo_cal_3` varchar(5) DEFAULT 'NR',
  `mdo_adm_1` varchar(5) DEFAULT 'NR',
  `mdo_adm_2` varchar(5) DEFAULT 'NR',
  `mdo_adm_3` varchar(5) DEFAULT 'NR',
  `mdo_adm_4` varchar(5) DEFAULT 'NR',
  `mdo_adm_5` varchar(5) DEFAULT 'NR',
  `mdo_gsa_1` varchar(5) DEFAULT 'NR',
  `mdo_gsa_2` varchar(5) DEFAULT 'NR',
  `mdo_gsa_3` varchar(5) DEFAULT 'NR',
  `mdo_gsa_4` varchar(5) DEFAULT 'NR',
  `mdo_gsa_5` varchar(5) DEFAULT 'NR',
  `mdo_gsa_6` varchar(5) DEFAULT 'NR',
  `mdo_gsa_7` varchar(5) DEFAULT 'NR',
  `mdo_gsa_8` varchar(5) DEFAULT 'NR',
  `mdo_sst_1` varchar(5) DEFAULT 'NR',
  `mdo_sst_2` varchar(5) DEFAULT 'NR',
  `mdo_sst_3` varchar(5) DEFAULT 'NR',
  `mdo_sst_4` varchar(5) DEFAULT 'NR',
  `mdo_sst_5` varchar(5) DEFAULT 'NR',
  `mdo_sst_6` varchar(5) DEFAULT 'NR',
  `mdo_sst_7` varchar(5) DEFAULT 'NR',
  `mdo_sst_8` varchar(5) DEFAULT 'NR',
  `mdo_sst_9` varchar(5) DEFAULT 'NR',
  `mdo_sst_10` varchar(5) DEFAULT 'NR',
  `si_cal_1` varchar(5) DEFAULT 'NR',
  `si_cal_2` varchar(5) DEFAULT 'NR',
  `si_cal_3` varchar(5) DEFAULT 'NR',
  `si_adm_1` varchar(5) DEFAULT 'NR',
  `si_adm_2` varchar(5) DEFAULT 'NR',
  `si_adm_3` varchar(5) DEFAULT 'NR',
  `si_adm_4` varchar(5) DEFAULT 'NR',
  `si_adm_5` varchar(5) DEFAULT 'NR',
  `si_adm_6` varchar(5) DEFAULT 'NR',
  `si_gsa_1` varchar(5) DEFAULT 'NR',
  `si_gsa_2` varchar(5) DEFAULT 'NR',
  `si_gsa_3` varchar(5) DEFAULT 'NR',
  `si_gsa_4` varchar(5) DEFAULT 'NR',
  `si_gsa_5` varchar(5) DEFAULT 'NR',
  `si_gsa_6` varchar(5) DEFAULT 'NR',
  `si_gsa_7` varchar(5) DEFAULT 'NR',
  `si_gsa_8` varchar(5) DEFAULT 'NR',
  `si_gsa_9` varchar(5) DEFAULT 'NR',
  `si_gsa_10` varchar(5) DEFAULT 'NR',
  `si_gsa_11` varchar(5) DEFAULT 'NR',
  `si_gsa_12` varchar(5) DEFAULT 'NR',
  `si_gsa_13` varchar(5) DEFAULT 'NR',
  `si_gsa_14` varchar(5) DEFAULT 'NR',
  `si_sst_1` varchar(5) DEFAULT 'NR',
  `si_sst_2` varchar(5) DEFAULT 'NR',
  `si_sst_3` varchar(5) DEFAULT 'NR',
  `si_sst_4` varchar(5) DEFAULT 'NR',
  `si_sst_5` varchar(5) DEFAULT 'NR',
  `si_sst_6` varchar(5) DEFAULT 'NR',
  `si_sst_7` varchar(5) DEFAULT 'NR',
  `si_sst_8` varchar(5) DEFAULT 'NR',
  `si_sst_9` varchar(5) DEFAULT 'NR',
  `si_sst_10` varchar(5) DEFAULT 'NR'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metrolineaDos_pdc`
--

CREATE TABLE `metrolineaDos_pdc` (
  `consecutivo` int NOT NULL,
  `semana` int NOT NULL,
  `titulo` int NOT NULL,
  `tipoPaquete` varchar(200) NOT NULL,
  `paqueteContratacion` varchar(200) DEFAULT NULL,
  `contratos` varchar(200) DEFAULT NULL,
  `numeroSubcontratos` int DEFAULT '1',
  `subcontratoPaquete` int NOT NULL DEFAULT '1',
  `estado` varchar(200) DEFAULT NULL,
  `fechaElaboracionPliegos` date DEFAULT NULL,
  `diasElaboracionPliegos` int DEFAULT NULL,
  `fechaRealElaboracionPliegos` date DEFAULT NULL,
  `fechaIngresoLicify` date DEFAULT NULL,
  `diasIngresoLicify` int DEFAULT NULL,
  `fechaRealIngresoLicify` date DEFAULT NULL,
  `fechaEntregaPliegos` date DEFAULT NULL,
  `diasEntregaPliegos` int DEFAULT NULL,
  `fechaRealEntregaPliegos` date DEFAULT NULL,
  `fechaReciboPropuestas` date DEFAULT NULL,
  `diasReciboPropuestas` int DEFAULT NULL,
  `fechaRealReciboPropuestas` date DEFAULT NULL,
  `fechaCuadrosComparativos` date DEFAULT NULL,
  `diasCuadrosComparativos` int DEFAULT NULL,
  `fechaRealCuadrosComparativos` date DEFAULT NULL,
  `fechaLegalizacionContrato` date DEFAULT NULL,
  `diasLegalizacionContrato` int DEFAULT NULL,
  `fechaRealLegalizacionContrato` date DEFAULT NULL,
  `fechaFabricacion` date DEFAULT NULL,
  `diasFabricacion` int DEFAULT NULL,
  `fechaRealFabricacion` date DEFAULT NULL,
  `fechaInsumosObra` date DEFAULT NULL,
  `diasInsumosObra` int DEFAULT NULL,
  `fechaRealInsumosObra` date DEFAULT NULL,
  `fechaInicio` date DEFAULT NULL,
  `fechaInicioProyectada` date DEFAULT NULL,
  `fechaRealInicio` date DEFAULT NULL,
  `idProveedorAdjudicado` int DEFAULT NULL,
  `numeroContrato` varchar(50) DEFAULT NULL,
  `aplicaPolizas` int NOT NULL DEFAULT '1',
  `fechaVencimientoPolizas` date DEFAULT NULL,
  `valorPresupuesto` float DEFAULT NULL,
  `valorPrimeraNegociacion` float DEFAULT NULL,
  `valorAdjudicado` float DEFAULT NULL,
  `valorAnticipo` float DEFAULT NULL,
  `valorReclamado` float DEFAULT NULL,
  `valorDevoluciones` float DEFAULT NULL,
  `observacionesContrato` mediumtext
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metrolineaDos_pi_shared_constraints`
--

CREATE TABLE `metrolineaDos_pi_shared_constraints` (
  `Id` bigint UNSIGNED NOT NULL,
  `Semana` int NOT NULL,
  `Restriccion` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `ValorObjetivo` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `Nota` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `CreadoPor` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `CreadoEn` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ActualizadoEn` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metrolineaDos_pi_shared_constraint_links`
--

CREATE TABLE `metrolineaDos_pi_shared_constraint_links` (
  `Id` bigint UNSIGNED NOT NULL,
  `SharedConstraintId` bigint UNSIGNED NOT NULL,
  `Semana` int NOT NULL,
  `ConsecutivoEnPrograma` bigint NOT NULL,
  `ValorAplicado` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `OverrideLocal` tinyint(1) NOT NULL DEFAULT '0',
  `AplicadoEn` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metrolineaDos_profesionales`
--

CREATE TABLE `metrolineaDos_profesionales` (
  `id` int NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `cargo` varchar(100) NOT NULL,
  `activo` int NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metrolineaDos_programa`
--

CREATE TABLE `metrolineaDos_programa` (
  `Consecutivo` int NOT NULL,
  `Id` varchar(500) DEFAULT NULL,
  `Actividad` varchar(500) DEFAULT NULL,
  `Titulo` int DEFAULT NULL,
  `Fecha_Inicio` date DEFAULT NULL,
  `Fecha_Fin` date DEFAULT NULL,
  `Ruta_Critica` int DEFAULT NULL,
  `Ejecutado` float DEFAULT '0',
  `Estado` varchar(50) DEFAULT NULL,
  `Semanas_Inicio` int DEFAULT '0',
  `Estado_Restricciones` float DEFAULT '0',
  `D_y_E` float DEFAULT '0',
  `Materiales` float DEFAULT '0',
  `MdeO` float DEFAULT '0',
  `Equipos` float DEFAULT '0',
  `Predecesora` float DEFAULT '0',
  `Pdto_Cons` float DEFAULT '0',
  `Modelo` varchar(9) DEFAULT '0',
  `Responsable_AIA` varchar(100) DEFAULT NULL,
  `Observaciones` mediumtext,
  `Ult_Act_Est` date DEFAULT NULL,
  `Ult_Act_Restr` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metrolineaDos_programacion_semanal`
--

CREATE TABLE `metrolineaDos_programacion_semanal` (
  `Consecutivo` int NOT NULL,
  `Semana` int DEFAULT NULL,
  `Consecutivo_En_Programa` int NOT NULL,
  `Id` varchar(500) DEFAULT NULL,
  `Actividad` varchar(500) DEFAULT NULL,
  `Descripcion` mediumtext,
  `Ubicacion` mediumtext,
  `Fecha_Inicio` date DEFAULT NULL,
  `Fecha_Fin` date DEFAULT NULL,
  `Sub_Contratista` varchar(200) DEFAULT NULL,
  `Responsable_AIA` varchar(200) DEFAULT NULL,
  `Empresa` varchar(200) NOT NULL DEFAULT 'AIA',
  `Ejecutado` float DEFAULT NULL,
  `medir_productividad` int DEFAULT '0',
  `Unidad` varchar(10) DEFAULT NULL,
  `cantidad_ppto` int DEFAULT NULL,
  `Cantidad_Sugerida` float DEFAULT NULL,
  `Compromiso` float DEFAULT NULL,
  `Ejecutado_Real` float DEFAULT NULL,
  `P_Completado` float DEFAULT NULL,
  `PAC` int DEFAULT NULL,
  `Critica` int DEFAULT NULL,
  `Atrasada` int DEFAULT NULL,
  `Activa` varchar(3) DEFAULT NULL,
  `Prog_Sin_Restricciones_100` int DEFAULT NULL,
  `Categoria_CNP` varchar(100) DEFAULT NULL,
  `CNP` varchar(100) DEFAULT NULL,
  `Observaciones_CNP` mediumtext,
  `Categoria_CNC` varchar(100) DEFAULT NULL,
  `CNC` varchar(100) DEFAULT NULL,
  `Observaciones_CNC` mediumtext,
  `Rendimientos` varchar(500) DEFAULT NULL,
  `codigo_actividad` varchar(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metrolineaDos_programa_consolidado`
--

CREATE TABLE `metrolineaDos_programa_consolidado` (
  `Consecutivo` int NOT NULL,
  `Semana` int NOT NULL,
  `Consecutivo_en_Programa` int NOT NULL,
  `Id` varchar(500) DEFAULT NULL,
  `Actividad` varchar(500) DEFAULT NULL,
  `Titulo` int DEFAULT NULL,
  `Fecha_Inicio` date DEFAULT NULL,
  `Fecha_Fin` date DEFAULT NULL,
  `Ruta_Critica` int DEFAULT NULL,
  `Ejecutado` float DEFAULT '0',
  `Estado` varchar(100) DEFAULT NULL,
  `Semanas_Inicio` int DEFAULT '0',
  `Estado_Restricciones` float NOT NULL DEFAULT '0',
  `D_y_E` varchar(9) NOT NULL DEFAULT '0',
  `Materiales` varchar(9) NOT NULL DEFAULT '0',
  `MdeO` varchar(9) NOT NULL DEFAULT '0',
  `Equipos` varchar(9) NOT NULL DEFAULT '0',
  `Predecesora` varchar(9) NOT NULL DEFAULT '0',
  `Pdto_Cons` varchar(9) NOT NULL DEFAULT '0',
  `Modelo` varchar(9) NOT NULL DEFAULT '0',
  `Sub_Contratista` varchar(100) DEFAULT NULL,
  `Responsable_AIA` varchar(100) DEFAULT NULL,
  `Observaciones` mediumtext,
  `Ult_Act_Est` date DEFAULT NULL,
  `Ult_Act_Restr` date DEFAULT NULL,
  `Activa` int NOT NULL DEFAULT '0',
  `Ejecutado_Siguiente_Semana` float DEFAULT NULL,
  `codigo_actividad` varchar(11) DEFAULT NULL,
  `medir_productividad` int DEFAULT '0',
  `cantidad_ppto` int DEFAULT NULL,
  `unidad` varchar(20) DEFAULT NULL,
  `programaAnteriorAsociar` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metrolineaDos_semanas_activas`
--

CREATE TABLE `metrolineaDos_semanas_activas` (
  `Id` int NOT NULL,
  `Semana` int NOT NULL,
  `Fecha_Inicio_Sem` date NOT NULL,
  `Fecha_Fin_Sem` date NOT NULL,
  `Semanal_Confirmada` int DEFAULT '0',
  `fechaCierreCompromisos` date DEFAULT NULL,
  `fechaCreacionSemana` date DEFAULT NULL,
  `reprogramacion` int NOT NULL DEFAULT '0',
  `diferenciaEstructuraCron` int NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metrolineaDos_subcontratistas`
--

CREATE TABLE `metrolineaDos_subcontratistas` (
  `Id` int NOT NULL,
  `subcontratista` varchar(200) NOT NULL,
  `correo_contacto` varchar(200) NOT NULL,
  `NIT` bigint NOT NULL,
  `alcance` varchar(200) NOT NULL,
  `tipo_proveedor` varchar(200) NOT NULL,
  `activo` int NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metrolineaMampDos_actividades`
--

CREATE TABLE `metrolineaMampDos_actividades` (
  `Id` int NOT NULL,
  `codigo` int NOT NULL,
  `actividad` varchar(300) NOT NULL,
  `descripcionActividad` mediumtext,
  `actividadInicio` varchar(500) DEFAULT NULL,
  `nombreActividadInicio` varchar(500) DEFAULT NULL,
  `fechaInicio` date DEFAULT NULL,
  `tipoContrato` varchar(10) DEFAULT NULL,
  `semanaActualizacion` int DEFAULT NULL,
  `SI1` varchar(200) DEFAULT NULL,
  `paqueteSI1` varchar(200) DEFAULT NULL,
  `SI2` varchar(200) DEFAULT NULL,
  `paqueteSI2` varchar(200) DEFAULT NULL,
  `SI3` varchar(200) DEFAULT NULL,
  `paqueteSI3` varchar(200) DEFAULT NULL,
  `SI4` varchar(200) DEFAULT NULL,
  `paqueteSI4` varchar(200) DEFAULT NULL,
  `SI5` varchar(200) DEFAULT NULL,
  `paqueteSI5` varchar(200) DEFAULT NULL,
  `S1` varchar(200) DEFAULT NULL,
  `paqueteS1` varchar(200) DEFAULT NULL,
  `S2` varchar(200) DEFAULT NULL,
  `paqueteS2` varchar(200) DEFAULT NULL,
  `S3` varchar(200) DEFAULT NULL,
  `paqueteS3` varchar(200) DEFAULT NULL,
  `S4` varchar(200) DEFAULT NULL,
  `paqueteS4` varchar(200) DEFAULT NULL,
  `S5` varchar(200) DEFAULT NULL,
  `paqueteS5` varchar(200) DEFAULT NULL,
  `MO1` varchar(200) DEFAULT NULL,
  `paqueteMO1` varchar(200) DEFAULT NULL,
  `MO2` varchar(200) DEFAULT NULL,
  `paqueteMO2` varchar(200) DEFAULT NULL,
  `MO3` varchar(200) DEFAULT NULL,
  `paqueteMO3` varchar(200) DEFAULT NULL,
  `MO4` varchar(200) DEFAULT NULL,
  `paqueteMO4` varchar(200) DEFAULT NULL,
  `MO5` varchar(200) DEFAULT NULL,
  `paqueteMO5` varchar(200) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metrolineaMampDos_cambios`
--

CREATE TABLE `metrolineaMampDos_cambios` (
  `id` int NOT NULL,
  `solicitanteCambio` int DEFAULT NULL,
  `detalleSolicitanteOtro` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `fechaSolicitud` date DEFAULT NULL,
  `prioridad` int DEFAULT NULL,
  `tipoCambio` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `responsableSolucion` int DEFAULT NULL,
  `detalleResponsableSolucion` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `justificacion` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `descripcion` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `incidenciaAlcance` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `tiempoCronograma` float DEFAULT NULL,
  `tiempoCronogramaAfectado` float DEFAULT NULL,
  `incidenciaCronograma` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `valorPresupuesto` float DEFAULT NULL,
  `costoDirecto` float DEFAULT NULL,
  `costoDirectoAIU` float DEFAULT NULL,
  `costoDirectoAIUIVA` float DEFAULT NULL,
  `valorAprobado` float DEFAULT NULL,
  `incidenciaPresupuesto` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `incidenciaCalidad` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `incidenciaRiesgo` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `incidenciaRecurso` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `fechaTentativaDefinicion` date DEFAULT NULL,
  `fechaEntregaInterventoria` date DEFAULT NULL,
  `Observaciones` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `fechaDefinicion` date DEFAULT NULL,
  `aprobacion` int DEFAULT NULL,
  `soportes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metrolineaMampDos_cic`
--

CREATE TABLE `metrolineaMampDos_cic` (
  `Id` int NOT NULL,
  `Semana` int DEFAULT NULL,
  `subcontratista` varchar(200) DEFAULT NULL,
  `correo_contacto` varchar(200) DEFAULT NULL,
  `NIT` varchar(10) DEFAULT NULL,
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
  `Observaciones` mediumtext,
  `mdo_cal_1` varchar(5) DEFAULT 'NR',
  `mdo_cal_2` varchar(5) DEFAULT 'NR',
  `mdo_cal_3` varchar(5) DEFAULT 'NR',
  `mdo_adm_1` varchar(5) DEFAULT 'NR',
  `mdo_adm_2` varchar(5) DEFAULT 'NR',
  `mdo_adm_3` varchar(5) DEFAULT 'NR',
  `mdo_adm_4` varchar(5) DEFAULT 'NR',
  `mdo_adm_5` varchar(5) DEFAULT 'NR',
  `mdo_gsa_1` varchar(5) DEFAULT 'NR',
  `mdo_gsa_2` varchar(5) DEFAULT 'NR',
  `mdo_gsa_3` varchar(5) DEFAULT 'NR',
  `mdo_gsa_4` varchar(5) DEFAULT 'NR',
  `mdo_gsa_5` varchar(5) DEFAULT 'NR',
  `mdo_gsa_6` varchar(5) DEFAULT 'NR',
  `mdo_gsa_7` varchar(5) DEFAULT 'NR',
  `mdo_gsa_8` varchar(5) DEFAULT 'NR',
  `mdo_sst_1` varchar(5) DEFAULT 'NR',
  `mdo_sst_2` varchar(5) DEFAULT 'NR',
  `mdo_sst_3` varchar(5) DEFAULT 'NR',
  `mdo_sst_4` varchar(5) DEFAULT 'NR',
  `mdo_sst_5` varchar(5) DEFAULT 'NR',
  `mdo_sst_6` varchar(5) DEFAULT 'NR',
  `mdo_sst_7` varchar(5) DEFAULT 'NR',
  `mdo_sst_8` varchar(5) DEFAULT 'NR',
  `mdo_sst_9` varchar(5) DEFAULT 'NR',
  `mdo_sst_10` varchar(5) DEFAULT 'NR',
  `si_cal_1` varchar(5) DEFAULT 'NR',
  `si_cal_2` varchar(5) DEFAULT 'NR',
  `si_cal_3` varchar(5) DEFAULT 'NR',
  `si_adm_1` varchar(5) DEFAULT 'NR',
  `si_adm_2` varchar(5) DEFAULT 'NR',
  `si_adm_3` varchar(5) DEFAULT 'NR',
  `si_adm_4` varchar(5) DEFAULT 'NR',
  `si_adm_5` varchar(5) DEFAULT 'NR',
  `si_adm_6` varchar(5) DEFAULT 'NR',
  `si_gsa_1` varchar(5) DEFAULT 'NR',
  `si_gsa_2` varchar(5) DEFAULT 'NR',
  `si_gsa_3` varchar(5) DEFAULT 'NR',
  `si_gsa_4` varchar(5) DEFAULT 'NR',
  `si_gsa_5` varchar(5) DEFAULT 'NR',
  `si_gsa_6` varchar(5) DEFAULT 'NR',
  `si_gsa_7` varchar(5) DEFAULT 'NR',
  `si_gsa_8` varchar(5) DEFAULT 'NR',
  `si_gsa_9` varchar(5) DEFAULT 'NR',
  `si_gsa_10` varchar(5) DEFAULT 'NR',
  `si_gsa_11` varchar(5) DEFAULT 'NR',
  `si_gsa_12` varchar(5) DEFAULT 'NR',
  `si_gsa_13` varchar(5) DEFAULT 'NR',
  `si_gsa_14` varchar(5) DEFAULT 'NR',
  `si_sst_1` varchar(5) DEFAULT 'NR',
  `si_sst_2` varchar(5) DEFAULT 'NR',
  `si_sst_3` varchar(5) DEFAULT 'NR',
  `si_sst_4` varchar(5) DEFAULT 'NR',
  `si_sst_5` varchar(5) DEFAULT 'NR',
  `si_sst_6` varchar(5) DEFAULT 'NR',
  `si_sst_7` varchar(5) DEFAULT 'NR',
  `si_sst_8` varchar(5) DEFAULT 'NR',
  `si_sst_9` varchar(5) DEFAULT 'NR',
  `si_sst_10` varchar(5) DEFAULT 'NR'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metrolineaMampDos_pdc`
--

CREATE TABLE `metrolineaMampDos_pdc` (
  `consecutivo` int NOT NULL,
  `semana` int NOT NULL,
  `titulo` int NOT NULL,
  `tipoPaquete` varchar(200) NOT NULL,
  `paqueteContratacion` varchar(200) DEFAULT NULL,
  `contratos` varchar(200) DEFAULT NULL,
  `numeroSubcontratos` int DEFAULT '1',
  `subcontratoPaquete` int NOT NULL DEFAULT '1',
  `estado` varchar(200) DEFAULT NULL,
  `fechaElaboracionPliegos` date DEFAULT NULL,
  `diasElaboracionPliegos` int DEFAULT NULL,
  `fechaRealElaboracionPliegos` date DEFAULT NULL,
  `fechaIngresoLicify` date DEFAULT NULL,
  `diasIngresoLicify` int DEFAULT NULL,
  `fechaRealIngresoLicify` date DEFAULT NULL,
  `fechaEntregaPliegos` date DEFAULT NULL,
  `diasEntregaPliegos` int DEFAULT NULL,
  `fechaRealEntregaPliegos` date DEFAULT NULL,
  `fechaReciboPropuestas` date DEFAULT NULL,
  `diasReciboPropuestas` int DEFAULT NULL,
  `fechaRealReciboPropuestas` date DEFAULT NULL,
  `fechaCuadrosComparativos` date DEFAULT NULL,
  `diasCuadrosComparativos` int DEFAULT NULL,
  `fechaRealCuadrosComparativos` date DEFAULT NULL,
  `fechaLegalizacionContrato` date DEFAULT NULL,
  `diasLegalizacionContrato` int DEFAULT NULL,
  `fechaRealLegalizacionContrato` date DEFAULT NULL,
  `fechaFabricacion` date DEFAULT NULL,
  `diasFabricacion` int DEFAULT NULL,
  `fechaRealFabricacion` date DEFAULT NULL,
  `fechaInsumosObra` date DEFAULT NULL,
  `diasInsumosObra` int DEFAULT NULL,
  `fechaRealInsumosObra` date DEFAULT NULL,
  `fechaInicio` date DEFAULT NULL,
  `fechaInicioProyectada` date DEFAULT NULL,
  `fechaRealInicio` date DEFAULT NULL,
  `idProveedorAdjudicado` int DEFAULT NULL,
  `numeroContrato` varchar(50) DEFAULT NULL,
  `aplicaPolizas` int NOT NULL DEFAULT '1',
  `fechaVencimientoPolizas` date DEFAULT NULL,
  `valorPresupuesto` float DEFAULT NULL,
  `valorPrimeraNegociacion` float DEFAULT NULL,
  `valorAdjudicado` float DEFAULT NULL,
  `valorAnticipo` float DEFAULT NULL,
  `valorReclamado` float DEFAULT NULL,
  `valorDevoluciones` float DEFAULT NULL,
  `observacionesContrato` mediumtext
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metrolineaMampDos_pi_shared_constraints`
--

CREATE TABLE `metrolineaMampDos_pi_shared_constraints` (
  `Id` bigint UNSIGNED NOT NULL,
  `Semana` int NOT NULL,
  `Restriccion` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `ValorObjetivo` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `Nota` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `CreadoPor` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `CreadoEn` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ActualizadoEn` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metrolineaMampDos_pi_shared_constraint_links`
--

CREATE TABLE `metrolineaMampDos_pi_shared_constraint_links` (
  `Id` bigint UNSIGNED NOT NULL,
  `SharedConstraintId` bigint UNSIGNED NOT NULL,
  `Semana` int NOT NULL,
  `ConsecutivoEnPrograma` bigint NOT NULL,
  `ValorAplicado` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `OverrideLocal` tinyint(1) NOT NULL DEFAULT '0',
  `AplicadoEn` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metrolineaMampDos_profesionales`
--

CREATE TABLE `metrolineaMampDos_profesionales` (
  `id` int NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `cargo` varchar(100) NOT NULL,
  `activo` int NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metrolineaMampDos_programa`
--

CREATE TABLE `metrolineaMampDos_programa` (
  `Consecutivo` int NOT NULL,
  `Id` varchar(500) DEFAULT NULL,
  `Actividad` varchar(500) DEFAULT NULL,
  `Titulo` int DEFAULT NULL,
  `Fecha_Inicio` date DEFAULT NULL,
  `Fecha_Fin` date DEFAULT NULL,
  `Ruta_Critica` int DEFAULT NULL,
  `Ejecutado` float DEFAULT '0',
  `Estado` varchar(50) DEFAULT NULL,
  `Semanas_Inicio` int DEFAULT '0',
  `Estado_Restricciones` float DEFAULT '0',
  `D_y_E` float DEFAULT '0',
  `Materiales` float DEFAULT '0',
  `MdeO` float DEFAULT '0',
  `Equipos` float DEFAULT '0',
  `Predecesora` float DEFAULT '0',
  `Pdto_Cons` float DEFAULT '0',
  `Modelo` varchar(9) DEFAULT '0',
  `Responsable_AIA` varchar(100) DEFAULT NULL,
  `Observaciones` mediumtext,
  `Ult_Act_Est` date DEFAULT NULL,
  `Ult_Act_Restr` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metrolineaMampDos_programacion_semanal`
--

CREATE TABLE `metrolineaMampDos_programacion_semanal` (
  `Consecutivo` int NOT NULL,
  `Semana` int DEFAULT NULL,
  `Consecutivo_En_Programa` int NOT NULL,
  `Id` varchar(500) DEFAULT NULL,
  `Actividad` varchar(500) DEFAULT NULL,
  `Descripcion` mediumtext,
  `Ubicacion` mediumtext,
  `Fecha_Inicio` date DEFAULT NULL,
  `Fecha_Fin` date DEFAULT NULL,
  `Sub_Contratista` varchar(200) DEFAULT NULL,
  `Responsable_AIA` varchar(200) DEFAULT NULL,
  `Empresa` varchar(200) NOT NULL DEFAULT 'AIA',
  `Ejecutado` float DEFAULT NULL,
  `medir_productividad` int DEFAULT '0',
  `Unidad` varchar(10) DEFAULT NULL,
  `cantidad_ppto` int DEFAULT NULL,
  `Cantidad_Sugerida` float DEFAULT NULL,
  `Compromiso` float DEFAULT NULL,
  `Ejecutado_Real` float DEFAULT NULL,
  `P_Completado` float DEFAULT NULL,
  `PAC` int DEFAULT NULL,
  `Critica` int DEFAULT NULL,
  `Atrasada` int DEFAULT NULL,
  `Activa` varchar(3) DEFAULT NULL,
  `Prog_Sin_Restricciones_100` int DEFAULT NULL,
  `Categoria_CNP` varchar(100) DEFAULT NULL,
  `CNP` varchar(100) DEFAULT NULL,
  `Observaciones_CNP` mediumtext,
  `Categoria_CNC` varchar(100) DEFAULT NULL,
  `CNC` varchar(100) DEFAULT NULL,
  `Observaciones_CNC` mediumtext,
  `Rendimientos` varchar(500) DEFAULT NULL,
  `codigo_actividad` varchar(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metrolineaMampDos_programa_consolidado`
--

CREATE TABLE `metrolineaMampDos_programa_consolidado` (
  `Consecutivo` int NOT NULL,
  `Semana` int NOT NULL,
  `Consecutivo_en_Programa` int NOT NULL,
  `Id` varchar(500) DEFAULT NULL,
  `Actividad` varchar(500) DEFAULT NULL,
  `Titulo` int DEFAULT NULL,
  `Fecha_Inicio` date DEFAULT NULL,
  `Fecha_Fin` date DEFAULT NULL,
  `Ruta_Critica` int DEFAULT NULL,
  `Ejecutado` float DEFAULT '0',
  `Estado` varchar(100) DEFAULT NULL,
  `Semanas_Inicio` int DEFAULT '0',
  `Estado_Restricciones` float NOT NULL DEFAULT '0',
  `D_y_E` varchar(9) NOT NULL DEFAULT '0',
  `Materiales` varchar(9) NOT NULL DEFAULT '0',
  `MdeO` varchar(9) NOT NULL DEFAULT '0',
  `Equipos` varchar(9) NOT NULL DEFAULT '0',
  `Predecesora` varchar(9) NOT NULL DEFAULT '0',
  `Pdto_Cons` varchar(9) NOT NULL DEFAULT '0',
  `Modelo` varchar(9) NOT NULL DEFAULT '0',
  `Sub_Contratista` varchar(100) DEFAULT NULL,
  `Responsable_AIA` varchar(100) DEFAULT NULL,
  `Observaciones` mediumtext,
  `Ult_Act_Est` date DEFAULT NULL,
  `Ult_Act_Restr` date DEFAULT NULL,
  `Activa` int NOT NULL DEFAULT '0',
  `Ejecutado_Siguiente_Semana` float DEFAULT NULL,
  `codigo_actividad` varchar(11) DEFAULT NULL,
  `medir_productividad` int DEFAULT '0',
  `cantidad_ppto` int DEFAULT NULL,
  `unidad` varchar(20) DEFAULT NULL,
  `programaAnteriorAsociar` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metrolineaMampDos_semanas_activas`
--

CREATE TABLE `metrolineaMampDos_semanas_activas` (
  `Id` int NOT NULL,
  `Semana` int NOT NULL,
  `Fecha_Inicio_Sem` date NOT NULL,
  `Fecha_Fin_Sem` date NOT NULL,
  `Semanal_Confirmada` int DEFAULT '0',
  `fechaCierreCompromisos` date DEFAULT NULL,
  `fechaCreacionSemana` date DEFAULT NULL,
  `reprogramacion` int NOT NULL DEFAULT '0',
  `diferenciaEstructuraCron` int NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metrolineaMampDos_subcontratistas`
--

CREATE TABLE `metrolineaMampDos_subcontratistas` (
  `Id` int NOT NULL,
  `subcontratista` varchar(200) NOT NULL,
  `correo_contacto` varchar(200) NOT NULL,
  `NIT` bigint NOT NULL,
  `alcance` varchar(200) NOT NULL,
  `tipo_proveedor` varchar(200) NOT NULL,
  `activo` int NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metrolineaMampSeis_actividades`
--

CREATE TABLE `metrolineaMampSeis_actividades` (
  `Id` int NOT NULL,
  `codigo` int NOT NULL,
  `actividad` varchar(300) NOT NULL,
  `descripcionActividad` mediumtext,
  `actividadInicio` varchar(500) DEFAULT NULL,
  `nombreActividadInicio` varchar(500) DEFAULT NULL,
  `fechaInicio` date DEFAULT NULL,
  `tipoContrato` varchar(10) DEFAULT NULL,
  `semanaActualizacion` int DEFAULT NULL,
  `SI1` varchar(200) DEFAULT NULL,
  `paqueteSI1` varchar(200) DEFAULT NULL,
  `SI2` varchar(200) DEFAULT NULL,
  `paqueteSI2` varchar(200) DEFAULT NULL,
  `SI3` varchar(200) DEFAULT NULL,
  `paqueteSI3` varchar(200) DEFAULT NULL,
  `SI4` varchar(200) DEFAULT NULL,
  `paqueteSI4` varchar(200) DEFAULT NULL,
  `SI5` varchar(200) DEFAULT NULL,
  `paqueteSI5` varchar(200) DEFAULT NULL,
  `S1` varchar(200) DEFAULT NULL,
  `paqueteS1` varchar(200) DEFAULT NULL,
  `S2` varchar(200) DEFAULT NULL,
  `paqueteS2` varchar(200) DEFAULT NULL,
  `S3` varchar(200) DEFAULT NULL,
  `paqueteS3` varchar(200) DEFAULT NULL,
  `S4` varchar(200) DEFAULT NULL,
  `paqueteS4` varchar(200) DEFAULT NULL,
  `S5` varchar(200) DEFAULT NULL,
  `paqueteS5` varchar(200) DEFAULT NULL,
  `MO1` varchar(200) DEFAULT NULL,
  `paqueteMO1` varchar(200) DEFAULT NULL,
  `MO2` varchar(200) DEFAULT NULL,
  `paqueteMO2` varchar(200) DEFAULT NULL,
  `MO3` varchar(200) DEFAULT NULL,
  `paqueteMO3` varchar(200) DEFAULT NULL,
  `MO4` varchar(200) DEFAULT NULL,
  `paqueteMO4` varchar(200) DEFAULT NULL,
  `MO5` varchar(200) DEFAULT NULL,
  `paqueteMO5` varchar(200) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metrolineaMampSeis_cambios`
--

CREATE TABLE `metrolineaMampSeis_cambios` (
  `id` int NOT NULL,
  `solicitanteCambio` int DEFAULT NULL,
  `detalleSolicitanteOtro` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `fechaSolicitud` date DEFAULT NULL,
  `prioridad` int DEFAULT NULL,
  `tipoCambio` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `responsableSolucion` int DEFAULT NULL,
  `detalleResponsableSolucion` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `justificacion` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `descripcion` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `incidenciaAlcance` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `tiempoCronograma` float DEFAULT NULL,
  `tiempoCronogramaAfectado` float DEFAULT NULL,
  `incidenciaCronograma` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `valorPresupuesto` float DEFAULT NULL,
  `costoDirecto` float DEFAULT NULL,
  `costoDirectoAIU` float DEFAULT NULL,
  `costoDirectoAIUIVA` float DEFAULT NULL,
  `valorAprobado` float DEFAULT NULL,
  `incidenciaPresupuesto` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `incidenciaCalidad` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `incidenciaRiesgo` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `incidenciaRecurso` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `fechaTentativaDefinicion` date DEFAULT NULL,
  `fechaEntregaInterventoria` date DEFAULT NULL,
  `Observaciones` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `fechaDefinicion` date DEFAULT NULL,
  `aprobacion` int DEFAULT NULL,
  `soportes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metrolineaMampSeis_cic`
--

CREATE TABLE `metrolineaMampSeis_cic` (
  `Id` int NOT NULL,
  `Semana` int DEFAULT NULL,
  `subcontratista` varchar(200) DEFAULT NULL,
  `correo_contacto` varchar(200) DEFAULT NULL,
  `NIT` varchar(10) DEFAULT NULL,
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
  `Observaciones` mediumtext,
  `mdo_cal_1` varchar(5) DEFAULT 'NR',
  `mdo_cal_2` varchar(5) DEFAULT 'NR',
  `mdo_cal_3` varchar(5) DEFAULT 'NR',
  `mdo_adm_1` varchar(5) DEFAULT 'NR',
  `mdo_adm_2` varchar(5) DEFAULT 'NR',
  `mdo_adm_3` varchar(5) DEFAULT 'NR',
  `mdo_adm_4` varchar(5) DEFAULT 'NR',
  `mdo_adm_5` varchar(5) DEFAULT 'NR',
  `mdo_gsa_1` varchar(5) DEFAULT 'NR',
  `mdo_gsa_2` varchar(5) DEFAULT 'NR',
  `mdo_gsa_3` varchar(5) DEFAULT 'NR',
  `mdo_gsa_4` varchar(5) DEFAULT 'NR',
  `mdo_gsa_5` varchar(5) DEFAULT 'NR',
  `mdo_gsa_6` varchar(5) DEFAULT 'NR',
  `mdo_gsa_7` varchar(5) DEFAULT 'NR',
  `mdo_gsa_8` varchar(5) DEFAULT 'NR',
  `mdo_sst_1` varchar(5) DEFAULT 'NR',
  `mdo_sst_2` varchar(5) DEFAULT 'NR',
  `mdo_sst_3` varchar(5) DEFAULT 'NR',
  `mdo_sst_4` varchar(5) DEFAULT 'NR',
  `mdo_sst_5` varchar(5) DEFAULT 'NR',
  `mdo_sst_6` varchar(5) DEFAULT 'NR',
  `mdo_sst_7` varchar(5) DEFAULT 'NR',
  `mdo_sst_8` varchar(5) DEFAULT 'NR',
  `mdo_sst_9` varchar(5) DEFAULT 'NR',
  `mdo_sst_10` varchar(5) DEFAULT 'NR',
  `si_cal_1` varchar(5) DEFAULT 'NR',
  `si_cal_2` varchar(5) DEFAULT 'NR',
  `si_cal_3` varchar(5) DEFAULT 'NR',
  `si_adm_1` varchar(5) DEFAULT 'NR',
  `si_adm_2` varchar(5) DEFAULT 'NR',
  `si_adm_3` varchar(5) DEFAULT 'NR',
  `si_adm_4` varchar(5) DEFAULT 'NR',
  `si_adm_5` varchar(5) DEFAULT 'NR',
  `si_adm_6` varchar(5) DEFAULT 'NR',
  `si_gsa_1` varchar(5) DEFAULT 'NR',
  `si_gsa_2` varchar(5) DEFAULT 'NR',
  `si_gsa_3` varchar(5) DEFAULT 'NR',
  `si_gsa_4` varchar(5) DEFAULT 'NR',
  `si_gsa_5` varchar(5) DEFAULT 'NR',
  `si_gsa_6` varchar(5) DEFAULT 'NR',
  `si_gsa_7` varchar(5) DEFAULT 'NR',
  `si_gsa_8` varchar(5) DEFAULT 'NR',
  `si_gsa_9` varchar(5) DEFAULT 'NR',
  `si_gsa_10` varchar(5) DEFAULT 'NR',
  `si_gsa_11` varchar(5) DEFAULT 'NR',
  `si_gsa_12` varchar(5) DEFAULT 'NR',
  `si_gsa_13` varchar(5) DEFAULT 'NR',
  `si_gsa_14` varchar(5) DEFAULT 'NR',
  `si_sst_1` varchar(5) DEFAULT 'NR',
  `si_sst_2` varchar(5) DEFAULT 'NR',
  `si_sst_3` varchar(5) DEFAULT 'NR',
  `si_sst_4` varchar(5) DEFAULT 'NR',
  `si_sst_5` varchar(5) DEFAULT 'NR',
  `si_sst_6` varchar(5) DEFAULT 'NR',
  `si_sst_7` varchar(5) DEFAULT 'NR',
  `si_sst_8` varchar(5) DEFAULT 'NR',
  `si_sst_9` varchar(5) DEFAULT 'NR',
  `si_sst_10` varchar(5) DEFAULT 'NR'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metrolineaMampSeis_pdc`
--

CREATE TABLE `metrolineaMampSeis_pdc` (
  `consecutivo` int NOT NULL,
  `semana` int NOT NULL,
  `titulo` int NOT NULL,
  `tipoPaquete` varchar(200) NOT NULL,
  `paqueteContratacion` varchar(200) DEFAULT NULL,
  `contratos` varchar(200) DEFAULT NULL,
  `numeroSubcontratos` int DEFAULT '1',
  `subcontratoPaquete` int NOT NULL DEFAULT '1',
  `estado` varchar(200) DEFAULT NULL,
  `fechaElaboracionPliegos` date DEFAULT NULL,
  `diasElaboracionPliegos` int DEFAULT NULL,
  `fechaRealElaboracionPliegos` date DEFAULT NULL,
  `fechaIngresoLicify` date DEFAULT NULL,
  `diasIngresoLicify` int DEFAULT NULL,
  `fechaRealIngresoLicify` date DEFAULT NULL,
  `fechaEntregaPliegos` date DEFAULT NULL,
  `diasEntregaPliegos` int DEFAULT NULL,
  `fechaRealEntregaPliegos` date DEFAULT NULL,
  `fechaReciboPropuestas` date DEFAULT NULL,
  `diasReciboPropuestas` int DEFAULT NULL,
  `fechaRealReciboPropuestas` date DEFAULT NULL,
  `fechaCuadrosComparativos` date DEFAULT NULL,
  `diasCuadrosComparativos` int DEFAULT NULL,
  `fechaRealCuadrosComparativos` date DEFAULT NULL,
  `fechaLegalizacionContrato` date DEFAULT NULL,
  `diasLegalizacionContrato` int DEFAULT NULL,
  `fechaRealLegalizacionContrato` date DEFAULT NULL,
  `fechaFabricacion` date DEFAULT NULL,
  `diasFabricacion` int DEFAULT NULL,
  `fechaRealFabricacion` date DEFAULT NULL,
  `fechaInsumosObra` date DEFAULT NULL,
  `diasInsumosObra` int DEFAULT NULL,
  `fechaRealInsumosObra` date DEFAULT NULL,
  `fechaInicio` date DEFAULT NULL,
  `fechaInicioProyectada` date DEFAULT NULL,
  `fechaRealInicio` date DEFAULT NULL,
  `idProveedorAdjudicado` int DEFAULT NULL,
  `numeroContrato` varchar(50) DEFAULT NULL,
  `aplicaPolizas` int NOT NULL DEFAULT '1',
  `fechaVencimientoPolizas` date DEFAULT NULL,
  `valorPresupuesto` float DEFAULT NULL,
  `valorPrimeraNegociacion` float DEFAULT NULL,
  `valorAdjudicado` float DEFAULT NULL,
  `valorAnticipo` float DEFAULT NULL,
  `valorReclamado` float DEFAULT NULL,
  `valorDevoluciones` float DEFAULT NULL,
  `observacionesContrato` mediumtext
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metrolineaMampSeis_pi_shared_constraints`
--

CREATE TABLE `metrolineaMampSeis_pi_shared_constraints` (
  `Id` bigint UNSIGNED NOT NULL,
  `Semana` int NOT NULL,
  `Restriccion` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `ValorObjetivo` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `Nota` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `CreadoPor` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `CreadoEn` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ActualizadoEn` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metrolineaMampSeis_pi_shared_constraint_links`
--

CREATE TABLE `metrolineaMampSeis_pi_shared_constraint_links` (
  `Id` bigint UNSIGNED NOT NULL,
  `SharedConstraintId` bigint UNSIGNED NOT NULL,
  `Semana` int NOT NULL,
  `ConsecutivoEnPrograma` bigint NOT NULL,
  `ValorAplicado` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `OverrideLocal` tinyint(1) NOT NULL DEFAULT '0',
  `AplicadoEn` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metrolineaMampSeis_profesionales`
--

CREATE TABLE `metrolineaMampSeis_profesionales` (
  `id` int NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `cargo` varchar(100) NOT NULL,
  `activo` int NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metrolineaMampSeis_programa`
--

CREATE TABLE `metrolineaMampSeis_programa` (
  `Consecutivo` int NOT NULL,
  `Id` varchar(500) DEFAULT NULL,
  `Actividad` varchar(500) DEFAULT NULL,
  `Titulo` int DEFAULT NULL,
  `Fecha_Inicio` date DEFAULT NULL,
  `Fecha_Fin` date DEFAULT NULL,
  `Ruta_Critica` int DEFAULT NULL,
  `Ejecutado` float DEFAULT '0',
  `Estado` varchar(50) DEFAULT NULL,
  `Semanas_Inicio` int DEFAULT '0',
  `Estado_Restricciones` float DEFAULT '0',
  `D_y_E` float DEFAULT '0',
  `Materiales` float DEFAULT '0',
  `MdeO` float DEFAULT '0',
  `Equipos` float DEFAULT '0',
  `Predecesora` float DEFAULT '0',
  `Pdto_Cons` float DEFAULT '0',
  `Modelo` varchar(9) DEFAULT '0',
  `Responsable_AIA` varchar(100) DEFAULT NULL,
  `Observaciones` mediumtext,
  `Ult_Act_Est` date DEFAULT NULL,
  `Ult_Act_Restr` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metrolineaMampSeis_programacion_semanal`
--

CREATE TABLE `metrolineaMampSeis_programacion_semanal` (
  `Consecutivo` int NOT NULL,
  `Semana` int DEFAULT NULL,
  `Consecutivo_En_Programa` int NOT NULL,
  `Id` varchar(500) DEFAULT NULL,
  `Actividad` varchar(500) DEFAULT NULL,
  `Descripcion` mediumtext,
  `Ubicacion` mediumtext,
  `Fecha_Inicio` date DEFAULT NULL,
  `Fecha_Fin` date DEFAULT NULL,
  `Sub_Contratista` varchar(200) DEFAULT NULL,
  `Responsable_AIA` varchar(200) DEFAULT NULL,
  `Empresa` varchar(200) NOT NULL DEFAULT 'AIA',
  `Ejecutado` float DEFAULT NULL,
  `medir_productividad` int DEFAULT '0',
  `Unidad` varchar(10) DEFAULT NULL,
  `cantidad_ppto` int DEFAULT NULL,
  `Cantidad_Sugerida` float DEFAULT NULL,
  `Compromiso` float DEFAULT NULL,
  `Ejecutado_Real` float DEFAULT NULL,
  `P_Completado` float DEFAULT NULL,
  `PAC` int DEFAULT NULL,
  `Critica` int DEFAULT NULL,
  `Atrasada` int DEFAULT NULL,
  `Activa` varchar(3) DEFAULT NULL,
  `Prog_Sin_Restricciones_100` int DEFAULT NULL,
  `Categoria_CNP` varchar(100) DEFAULT NULL,
  `CNP` varchar(100) DEFAULT NULL,
  `Observaciones_CNP` mediumtext,
  `Categoria_CNC` varchar(100) DEFAULT NULL,
  `CNC` varchar(100) DEFAULT NULL,
  `Observaciones_CNC` mediumtext,
  `Rendimientos` varchar(500) DEFAULT NULL,
  `codigo_actividad` varchar(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metrolineaMampSeis_programa_consolidado`
--

CREATE TABLE `metrolineaMampSeis_programa_consolidado` (
  `Consecutivo` int NOT NULL,
  `Semana` int NOT NULL,
  `Consecutivo_en_Programa` int NOT NULL,
  `Id` varchar(500) DEFAULT NULL,
  `Actividad` varchar(500) DEFAULT NULL,
  `Titulo` int DEFAULT NULL,
  `Fecha_Inicio` date DEFAULT NULL,
  `Fecha_Fin` date DEFAULT NULL,
  `Ruta_Critica` int DEFAULT NULL,
  `Ejecutado` float DEFAULT '0',
  `Estado` varchar(100) DEFAULT NULL,
  `Semanas_Inicio` int DEFAULT '0',
  `Estado_Restricciones` float NOT NULL DEFAULT '0',
  `D_y_E` varchar(9) NOT NULL DEFAULT '0',
  `Materiales` varchar(9) NOT NULL DEFAULT '0',
  `MdeO` varchar(9) NOT NULL DEFAULT '0',
  `Equipos` varchar(9) NOT NULL DEFAULT '0',
  `Predecesora` varchar(9) NOT NULL DEFAULT '0',
  `Pdto_Cons` varchar(9) NOT NULL DEFAULT '0',
  `Modelo` varchar(9) NOT NULL DEFAULT '0',
  `Sub_Contratista` varchar(100) DEFAULT NULL,
  `Responsable_AIA` varchar(100) DEFAULT NULL,
  `Observaciones` mediumtext,
  `Ult_Act_Est` date DEFAULT NULL,
  `Ult_Act_Restr` date DEFAULT NULL,
  `Activa` int NOT NULL DEFAULT '0',
  `Ejecutado_Siguiente_Semana` float DEFAULT NULL,
  `codigo_actividad` varchar(11) DEFAULT NULL,
  `medir_productividad` int DEFAULT '0',
  `cantidad_ppto` int DEFAULT NULL,
  `unidad` varchar(20) DEFAULT NULL,
  `programaAnteriorAsociar` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metrolineaMampSeis_semanas_activas`
--

CREATE TABLE `metrolineaMampSeis_semanas_activas` (
  `Id` int NOT NULL,
  `Semana` int NOT NULL,
  `Fecha_Inicio_Sem` date NOT NULL,
  `Fecha_Fin_Sem` date NOT NULL,
  `Semanal_Confirmada` int DEFAULT '0',
  `fechaCierreCompromisos` date DEFAULT NULL,
  `fechaCreacionSemana` date DEFAULT NULL,
  `reprogramacion` int NOT NULL DEFAULT '0',
  `diferenciaEstructuraCron` int NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metrolineaMampSeis_subcontratistas`
--

CREATE TABLE `metrolineaMampSeis_subcontratistas` (
  `Id` int NOT NULL,
  `subcontratista` varchar(200) NOT NULL,
  `correo_contacto` varchar(200) NOT NULL,
  `NIT` bigint NOT NULL,
  `alcance` varchar(200) NOT NULL,
  `tipo_proveedor` varchar(200) NOT NULL,
  `activo` int NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metrolineaMampUno_actividades`
--

CREATE TABLE `metrolineaMampUno_actividades` (
  `Id` int NOT NULL,
  `codigo` int NOT NULL,
  `actividad` varchar(300) NOT NULL,
  `descripcionActividad` mediumtext,
  `actividadInicio` varchar(500) DEFAULT NULL,
  `nombreActividadInicio` varchar(500) DEFAULT NULL,
  `fechaInicio` date DEFAULT NULL,
  `tipoContrato` varchar(10) DEFAULT NULL,
  `semanaActualizacion` int DEFAULT NULL,
  `SI1` varchar(200) DEFAULT NULL,
  `paqueteSI1` varchar(200) DEFAULT NULL,
  `SI2` varchar(200) DEFAULT NULL,
  `paqueteSI2` varchar(200) DEFAULT NULL,
  `SI3` varchar(200) DEFAULT NULL,
  `paqueteSI3` varchar(200) DEFAULT NULL,
  `SI4` varchar(200) DEFAULT NULL,
  `paqueteSI4` varchar(200) DEFAULT NULL,
  `SI5` varchar(200) DEFAULT NULL,
  `paqueteSI5` varchar(200) DEFAULT NULL,
  `S1` varchar(200) DEFAULT NULL,
  `paqueteS1` varchar(200) DEFAULT NULL,
  `S2` varchar(200) DEFAULT NULL,
  `paqueteS2` varchar(200) DEFAULT NULL,
  `S3` varchar(200) DEFAULT NULL,
  `paqueteS3` varchar(200) DEFAULT NULL,
  `S4` varchar(200) DEFAULT NULL,
  `paqueteS4` varchar(200) DEFAULT NULL,
  `S5` varchar(200) DEFAULT NULL,
  `paqueteS5` varchar(200) DEFAULT NULL,
  `MO1` varchar(200) DEFAULT NULL,
  `paqueteMO1` varchar(200) DEFAULT NULL,
  `MO2` varchar(200) DEFAULT NULL,
  `paqueteMO2` varchar(200) DEFAULT NULL,
  `MO3` varchar(200) DEFAULT NULL,
  `paqueteMO3` varchar(200) DEFAULT NULL,
  `MO4` varchar(200) DEFAULT NULL,
  `paqueteMO4` varchar(200) DEFAULT NULL,
  `MO5` varchar(200) DEFAULT NULL,
  `paqueteMO5` varchar(200) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metrolineaMampUno_cambios`
--

CREATE TABLE `metrolineaMampUno_cambios` (
  `id` int NOT NULL,
  `solicitanteCambio` int DEFAULT NULL,
  `detalleSolicitanteOtro` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `fechaSolicitud` date DEFAULT NULL,
  `prioridad` int DEFAULT NULL,
  `tipoCambio` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `responsableSolucion` int DEFAULT NULL,
  `detalleResponsableSolucion` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `justificacion` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `descripcion` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `incidenciaAlcance` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `tiempoCronograma` float DEFAULT NULL,
  `tiempoCronogramaAfectado` float DEFAULT NULL,
  `incidenciaCronograma` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `valorPresupuesto` float DEFAULT NULL,
  `costoDirecto` float DEFAULT NULL,
  `costoDirectoAIU` float DEFAULT NULL,
  `costoDirectoAIUIVA` float DEFAULT NULL,
  `valorAprobado` float DEFAULT NULL,
  `incidenciaPresupuesto` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `incidenciaCalidad` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `incidenciaRiesgo` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `incidenciaRecurso` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `fechaTentativaDefinicion` date DEFAULT NULL,
  `fechaEntregaInterventoria` date DEFAULT NULL,
  `Observaciones` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `fechaDefinicion` date DEFAULT NULL,
  `aprobacion` int DEFAULT NULL,
  `soportes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metrolineaMampUno_cic`
--

CREATE TABLE `metrolineaMampUno_cic` (
  `Id` int NOT NULL,
  `Semana` int DEFAULT NULL,
  `subcontratista` varchar(200) DEFAULT NULL,
  `correo_contacto` varchar(200) DEFAULT NULL,
  `NIT` varchar(10) DEFAULT NULL,
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
  `Observaciones` mediumtext,
  `mdo_cal_1` varchar(5) DEFAULT 'NR',
  `mdo_cal_2` varchar(5) DEFAULT 'NR',
  `mdo_cal_3` varchar(5) DEFAULT 'NR',
  `mdo_adm_1` varchar(5) DEFAULT 'NR',
  `mdo_adm_2` varchar(5) DEFAULT 'NR',
  `mdo_adm_3` varchar(5) DEFAULT 'NR',
  `mdo_adm_4` varchar(5) DEFAULT 'NR',
  `mdo_adm_5` varchar(5) DEFAULT 'NR',
  `mdo_gsa_1` varchar(5) DEFAULT 'NR',
  `mdo_gsa_2` varchar(5) DEFAULT 'NR',
  `mdo_gsa_3` varchar(5) DEFAULT 'NR',
  `mdo_gsa_4` varchar(5) DEFAULT 'NR',
  `mdo_gsa_5` varchar(5) DEFAULT 'NR',
  `mdo_gsa_6` varchar(5) DEFAULT 'NR',
  `mdo_gsa_7` varchar(5) DEFAULT 'NR',
  `mdo_gsa_8` varchar(5) DEFAULT 'NR',
  `mdo_sst_1` varchar(5) DEFAULT 'NR',
  `mdo_sst_2` varchar(5) DEFAULT 'NR',
  `mdo_sst_3` varchar(5) DEFAULT 'NR',
  `mdo_sst_4` varchar(5) DEFAULT 'NR',
  `mdo_sst_5` varchar(5) DEFAULT 'NR',
  `mdo_sst_6` varchar(5) DEFAULT 'NR',
  `mdo_sst_7` varchar(5) DEFAULT 'NR',
  `mdo_sst_8` varchar(5) DEFAULT 'NR',
  `mdo_sst_9` varchar(5) DEFAULT 'NR',
  `mdo_sst_10` varchar(5) DEFAULT 'NR',
  `si_cal_1` varchar(5) DEFAULT 'NR',
  `si_cal_2` varchar(5) DEFAULT 'NR',
  `si_cal_3` varchar(5) DEFAULT 'NR',
  `si_adm_1` varchar(5) DEFAULT 'NR',
  `si_adm_2` varchar(5) DEFAULT 'NR',
  `si_adm_3` varchar(5) DEFAULT 'NR',
  `si_adm_4` varchar(5) DEFAULT 'NR',
  `si_adm_5` varchar(5) DEFAULT 'NR',
  `si_adm_6` varchar(5) DEFAULT 'NR',
  `si_gsa_1` varchar(5) DEFAULT 'NR',
  `si_gsa_2` varchar(5) DEFAULT 'NR',
  `si_gsa_3` varchar(5) DEFAULT 'NR',
  `si_gsa_4` varchar(5) DEFAULT 'NR',
  `si_gsa_5` varchar(5) DEFAULT 'NR',
  `si_gsa_6` varchar(5) DEFAULT 'NR',
  `si_gsa_7` varchar(5) DEFAULT 'NR',
  `si_gsa_8` varchar(5) DEFAULT 'NR',
  `si_gsa_9` varchar(5) DEFAULT 'NR',
  `si_gsa_10` varchar(5) DEFAULT 'NR',
  `si_gsa_11` varchar(5) DEFAULT 'NR',
  `si_gsa_12` varchar(5) DEFAULT 'NR',
  `si_gsa_13` varchar(5) DEFAULT 'NR',
  `si_gsa_14` varchar(5) DEFAULT 'NR',
  `si_sst_1` varchar(5) DEFAULT 'NR',
  `si_sst_2` varchar(5) DEFAULT 'NR',
  `si_sst_3` varchar(5) DEFAULT 'NR',
  `si_sst_4` varchar(5) DEFAULT 'NR',
  `si_sst_5` varchar(5) DEFAULT 'NR',
  `si_sst_6` varchar(5) DEFAULT 'NR',
  `si_sst_7` varchar(5) DEFAULT 'NR',
  `si_sst_8` varchar(5) DEFAULT 'NR',
  `si_sst_9` varchar(5) DEFAULT 'NR',
  `si_sst_10` varchar(5) DEFAULT 'NR'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metrolineaMampUno_pdc`
--

CREATE TABLE `metrolineaMampUno_pdc` (
  `consecutivo` int NOT NULL,
  `semana` int NOT NULL,
  `titulo` int NOT NULL,
  `tipoPaquete` varchar(200) NOT NULL,
  `paqueteContratacion` varchar(200) DEFAULT NULL,
  `contratos` varchar(200) DEFAULT NULL,
  `numeroSubcontratos` int DEFAULT '1',
  `subcontratoPaquete` int NOT NULL DEFAULT '1',
  `estado` varchar(200) DEFAULT NULL,
  `fechaElaboracionPliegos` date DEFAULT NULL,
  `diasElaboracionPliegos` int DEFAULT NULL,
  `fechaRealElaboracionPliegos` date DEFAULT NULL,
  `fechaIngresoLicify` date DEFAULT NULL,
  `diasIngresoLicify` int DEFAULT NULL,
  `fechaRealIngresoLicify` date DEFAULT NULL,
  `fechaEntregaPliegos` date DEFAULT NULL,
  `diasEntregaPliegos` int DEFAULT NULL,
  `fechaRealEntregaPliegos` date DEFAULT NULL,
  `fechaReciboPropuestas` date DEFAULT NULL,
  `diasReciboPropuestas` int DEFAULT NULL,
  `fechaRealReciboPropuestas` date DEFAULT NULL,
  `fechaCuadrosComparativos` date DEFAULT NULL,
  `diasCuadrosComparativos` int DEFAULT NULL,
  `fechaRealCuadrosComparativos` date DEFAULT NULL,
  `fechaLegalizacionContrato` date DEFAULT NULL,
  `diasLegalizacionContrato` int DEFAULT NULL,
  `fechaRealLegalizacionContrato` date DEFAULT NULL,
  `fechaFabricacion` date DEFAULT NULL,
  `diasFabricacion` int DEFAULT NULL,
  `fechaRealFabricacion` date DEFAULT NULL,
  `fechaInsumosObra` date DEFAULT NULL,
  `diasInsumosObra` int DEFAULT NULL,
  `fechaRealInsumosObra` date DEFAULT NULL,
  `fechaInicio` date DEFAULT NULL,
  `fechaInicioProyectada` date DEFAULT NULL,
  `fechaRealInicio` date DEFAULT NULL,
  `idProveedorAdjudicado` int DEFAULT NULL,
  `numeroContrato` varchar(50) DEFAULT NULL,
  `aplicaPolizas` int NOT NULL DEFAULT '1',
  `fechaVencimientoPolizas` date DEFAULT NULL,
  `valorPresupuesto` float DEFAULT NULL,
  `valorPrimeraNegociacion` float DEFAULT NULL,
  `valorAdjudicado` float DEFAULT NULL,
  `valorAnticipo` float DEFAULT NULL,
  `valorReclamado` float DEFAULT NULL,
  `valorDevoluciones` float DEFAULT NULL,
  `observacionesContrato` mediumtext
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metrolineaMampUno_pi_shared_constraints`
--

CREATE TABLE `metrolineaMampUno_pi_shared_constraints` (
  `Id` bigint UNSIGNED NOT NULL,
  `Semana` int NOT NULL,
  `Restriccion` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `ValorObjetivo` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `Nota` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `CreadoPor` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `CreadoEn` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ActualizadoEn` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metrolineaMampUno_pi_shared_constraint_links`
--

CREATE TABLE `metrolineaMampUno_pi_shared_constraint_links` (
  `Id` bigint UNSIGNED NOT NULL,
  `SharedConstraintId` bigint UNSIGNED NOT NULL,
  `Semana` int NOT NULL,
  `ConsecutivoEnPrograma` bigint NOT NULL,
  `ValorAplicado` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `OverrideLocal` tinyint(1) NOT NULL DEFAULT '0',
  `AplicadoEn` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metrolineaMampUno_profesionales`
--

CREATE TABLE `metrolineaMampUno_profesionales` (
  `id` int NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `cargo` varchar(100) NOT NULL,
  `activo` int NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metrolineaMampUno_programa`
--

CREATE TABLE `metrolineaMampUno_programa` (
  `Consecutivo` int NOT NULL,
  `Id` varchar(500) DEFAULT NULL,
  `Actividad` varchar(500) DEFAULT NULL,
  `Titulo` int DEFAULT NULL,
  `Fecha_Inicio` date DEFAULT NULL,
  `Fecha_Fin` date DEFAULT NULL,
  `Ruta_Critica` int DEFAULT NULL,
  `Ejecutado` float DEFAULT '0',
  `Estado` varchar(50) DEFAULT NULL,
  `Semanas_Inicio` int DEFAULT '0',
  `Estado_Restricciones` float DEFAULT '0',
  `D_y_E` float DEFAULT '0',
  `Materiales` float DEFAULT '0',
  `MdeO` float DEFAULT '0',
  `Equipos` float DEFAULT '0',
  `Predecesora` float DEFAULT '0',
  `Pdto_Cons` float DEFAULT '0',
  `Modelo` varchar(9) DEFAULT '0',
  `Responsable_AIA` varchar(100) DEFAULT NULL,
  `Observaciones` mediumtext,
  `Ult_Act_Est` date DEFAULT NULL,
  `Ult_Act_Restr` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metrolineaMampUno_programacion_semanal`
--

CREATE TABLE `metrolineaMampUno_programacion_semanal` (
  `Consecutivo` int NOT NULL,
  `Semana` int DEFAULT NULL,
  `Consecutivo_En_Programa` int NOT NULL,
  `Id` varchar(500) DEFAULT NULL,
  `Actividad` varchar(500) DEFAULT NULL,
  `Descripcion` mediumtext,
  `Ubicacion` mediumtext,
  `Fecha_Inicio` date DEFAULT NULL,
  `Fecha_Fin` date DEFAULT NULL,
  `Sub_Contratista` varchar(200) DEFAULT NULL,
  `Responsable_AIA` varchar(200) DEFAULT NULL,
  `Empresa` varchar(200) NOT NULL DEFAULT 'AIA',
  `Ejecutado` float DEFAULT NULL,
  `medir_productividad` int DEFAULT '0',
  `Unidad` varchar(10) DEFAULT NULL,
  `cantidad_ppto` int DEFAULT NULL,
  `Cantidad_Sugerida` float DEFAULT NULL,
  `Compromiso` float DEFAULT NULL,
  `Ejecutado_Real` float DEFAULT NULL,
  `P_Completado` float DEFAULT NULL,
  `PAC` int DEFAULT NULL,
  `Critica` int DEFAULT NULL,
  `Atrasada` int DEFAULT NULL,
  `Activa` varchar(3) DEFAULT NULL,
  `Prog_Sin_Restricciones_100` int DEFAULT NULL,
  `Categoria_CNP` varchar(100) DEFAULT NULL,
  `CNP` varchar(100) DEFAULT NULL,
  `Observaciones_CNP` mediumtext,
  `Categoria_CNC` varchar(100) DEFAULT NULL,
  `CNC` varchar(100) DEFAULT NULL,
  `Observaciones_CNC` mediumtext,
  `Rendimientos` varchar(500) DEFAULT NULL,
  `codigo_actividad` varchar(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metrolineaMampUno_programa_consolidado`
--

CREATE TABLE `metrolineaMampUno_programa_consolidado` (
  `Consecutivo` int NOT NULL,
  `Semana` int NOT NULL,
  `Consecutivo_en_Programa` int NOT NULL,
  `Id` varchar(500) DEFAULT NULL,
  `Actividad` varchar(500) DEFAULT NULL,
  `Titulo` int DEFAULT NULL,
  `Fecha_Inicio` date DEFAULT NULL,
  `Fecha_Fin` date DEFAULT NULL,
  `Ruta_Critica` int DEFAULT NULL,
  `Ejecutado` float DEFAULT '0',
  `Estado` varchar(100) DEFAULT NULL,
  `Semanas_Inicio` int DEFAULT '0',
  `Estado_Restricciones` float NOT NULL DEFAULT '0',
  `D_y_E` varchar(9) NOT NULL DEFAULT '0',
  `Materiales` varchar(9) NOT NULL DEFAULT '0',
  `MdeO` varchar(9) NOT NULL DEFAULT '0',
  `Equipos` varchar(9) NOT NULL DEFAULT '0',
  `Predecesora` varchar(9) NOT NULL DEFAULT '0',
  `Pdto_Cons` varchar(9) NOT NULL DEFAULT '0',
  `Modelo` varchar(9) NOT NULL DEFAULT '0',
  `Sub_Contratista` varchar(100) DEFAULT NULL,
  `Responsable_AIA` varchar(100) DEFAULT NULL,
  `Observaciones` mediumtext,
  `Ult_Act_Est` date DEFAULT NULL,
  `Ult_Act_Restr` date DEFAULT NULL,
  `Activa` int NOT NULL DEFAULT '0',
  `Ejecutado_Siguiente_Semana` float DEFAULT NULL,
  `codigo_actividad` varchar(11) DEFAULT NULL,
  `medir_productividad` int DEFAULT '0',
  `cantidad_ppto` int DEFAULT NULL,
  `unidad` varchar(20) DEFAULT NULL,
  `programaAnteriorAsociar` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metrolineaMampUno_semanas_activas`
--

CREATE TABLE `metrolineaMampUno_semanas_activas` (
  `Id` int NOT NULL,
  `Semana` int NOT NULL,
  `Fecha_Inicio_Sem` date NOT NULL,
  `Fecha_Fin_Sem` date NOT NULL,
  `Semanal_Confirmada` int DEFAULT '0',
  `fechaCierreCompromisos` date DEFAULT NULL,
  `fechaCreacionSemana` date DEFAULT NULL,
  `reprogramacion` int NOT NULL DEFAULT '0',
  `diferenciaEstructuraCron` int NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metrolineaMampUno_subcontratistas`
--

CREATE TABLE `metrolineaMampUno_subcontratistas` (
  `Id` int NOT NULL,
  `subcontratista` varchar(200) NOT NULL,
  `correo_contacto` varchar(200) NOT NULL,
  `NIT` bigint NOT NULL,
  `alcance` varchar(200) NOT NULL,
  `tipo_proveedor` varchar(200) NOT NULL,
  `activo` int NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metrolineaMurosDos_actividades`
--

CREATE TABLE `metrolineaMurosDos_actividades` (
  `Id` int NOT NULL,
  `codigo` int NOT NULL,
  `actividad` varchar(300) NOT NULL,
  `descripcionActividad` mediumtext,
  `actividadInicio` varchar(500) DEFAULT NULL,
  `nombreActividadInicio` varchar(500) DEFAULT NULL,
  `fechaInicio` date DEFAULT NULL,
  `tipoContrato` varchar(10) DEFAULT NULL,
  `semanaActualizacion` int DEFAULT NULL,
  `SI1` varchar(200) DEFAULT NULL,
  `paqueteSI1` varchar(200) DEFAULT NULL,
  `SI2` varchar(200) DEFAULT NULL,
  `paqueteSI2` varchar(200) DEFAULT NULL,
  `SI3` varchar(200) DEFAULT NULL,
  `paqueteSI3` varchar(200) DEFAULT NULL,
  `SI4` varchar(200) DEFAULT NULL,
  `paqueteSI4` varchar(200) DEFAULT NULL,
  `SI5` varchar(200) DEFAULT NULL,
  `paqueteSI5` varchar(200) DEFAULT NULL,
  `S1` varchar(200) DEFAULT NULL,
  `paqueteS1` varchar(200) DEFAULT NULL,
  `S2` varchar(200) DEFAULT NULL,
  `paqueteS2` varchar(200) DEFAULT NULL,
  `S3` varchar(200) DEFAULT NULL,
  `paqueteS3` varchar(200) DEFAULT NULL,
  `S4` varchar(200) DEFAULT NULL,
  `paqueteS4` varchar(200) DEFAULT NULL,
  `S5` varchar(200) DEFAULT NULL,
  `paqueteS5` varchar(200) DEFAULT NULL,
  `MO1` varchar(200) DEFAULT NULL,
  `paqueteMO1` varchar(200) DEFAULT NULL,
  `MO2` varchar(200) DEFAULT NULL,
  `paqueteMO2` varchar(200) DEFAULT NULL,
  `MO3` varchar(200) DEFAULT NULL,
  `paqueteMO3` varchar(200) DEFAULT NULL,
  `MO4` varchar(200) DEFAULT NULL,
  `paqueteMO4` varchar(200) DEFAULT NULL,
  `MO5` varchar(200) DEFAULT NULL,
  `paqueteMO5` varchar(200) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metrolineaMurosDos_cambios`
--

CREATE TABLE `metrolineaMurosDos_cambios` (
  `id` int NOT NULL,
  `solicitanteCambio` int DEFAULT NULL,
  `detalleSolicitanteOtro` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `fechaSolicitud` date DEFAULT NULL,
  `prioridad` int DEFAULT NULL,
  `tipoCambio` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `responsableSolucion` int DEFAULT NULL,
  `detalleResponsableSolucion` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `justificacion` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `descripcion` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `incidenciaAlcance` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `tiempoCronograma` float DEFAULT NULL,
  `tiempoCronogramaAfectado` float DEFAULT NULL,
  `incidenciaCronograma` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `valorPresupuesto` float DEFAULT NULL,
  `costoDirecto` float DEFAULT NULL,
  `costoDirectoAIU` float DEFAULT NULL,
  `costoDirectoAIUIVA` float DEFAULT NULL,
  `valorAprobado` float DEFAULT NULL,
  `incidenciaPresupuesto` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `incidenciaCalidad` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `incidenciaRiesgo` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `incidenciaRecurso` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `fechaTentativaDefinicion` date DEFAULT NULL,
  `fechaEntregaInterventoria` date DEFAULT NULL,
  `Observaciones` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `fechaDefinicion` date DEFAULT NULL,
  `aprobacion` int DEFAULT NULL,
  `soportes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metrolineaMurosDos_cic`
--

CREATE TABLE `metrolineaMurosDos_cic` (
  `Id` int NOT NULL,
  `Semana` int DEFAULT NULL,
  `subcontratista` varchar(200) DEFAULT NULL,
  `correo_contacto` varchar(200) DEFAULT NULL,
  `NIT` varchar(10) DEFAULT NULL,
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
  `Observaciones` mediumtext,
  `mdo_cal_1` varchar(5) DEFAULT 'NR',
  `mdo_cal_2` varchar(5) DEFAULT 'NR',
  `mdo_cal_3` varchar(5) DEFAULT 'NR',
  `mdo_adm_1` varchar(5) DEFAULT 'NR',
  `mdo_adm_2` varchar(5) DEFAULT 'NR',
  `mdo_adm_3` varchar(5) DEFAULT 'NR',
  `mdo_adm_4` varchar(5) DEFAULT 'NR',
  `mdo_adm_5` varchar(5) DEFAULT 'NR',
  `mdo_gsa_1` varchar(5) DEFAULT 'NR',
  `mdo_gsa_2` varchar(5) DEFAULT 'NR',
  `mdo_gsa_3` varchar(5) DEFAULT 'NR',
  `mdo_gsa_4` varchar(5) DEFAULT 'NR',
  `mdo_gsa_5` varchar(5) DEFAULT 'NR',
  `mdo_gsa_6` varchar(5) DEFAULT 'NR',
  `mdo_gsa_7` varchar(5) DEFAULT 'NR',
  `mdo_gsa_8` varchar(5) DEFAULT 'NR',
  `mdo_sst_1` varchar(5) DEFAULT 'NR',
  `mdo_sst_2` varchar(5) DEFAULT 'NR',
  `mdo_sst_3` varchar(5) DEFAULT 'NR',
  `mdo_sst_4` varchar(5) DEFAULT 'NR',
  `mdo_sst_5` varchar(5) DEFAULT 'NR',
  `mdo_sst_6` varchar(5) DEFAULT 'NR',
  `mdo_sst_7` varchar(5) DEFAULT 'NR',
  `mdo_sst_8` varchar(5) DEFAULT 'NR',
  `mdo_sst_9` varchar(5) DEFAULT 'NR',
  `mdo_sst_10` varchar(5) DEFAULT 'NR',
  `si_cal_1` varchar(5) DEFAULT 'NR',
  `si_cal_2` varchar(5) DEFAULT 'NR',
  `si_cal_3` varchar(5) DEFAULT 'NR',
  `si_adm_1` varchar(5) DEFAULT 'NR',
  `si_adm_2` varchar(5) DEFAULT 'NR',
  `si_adm_3` varchar(5) DEFAULT 'NR',
  `si_adm_4` varchar(5) DEFAULT 'NR',
  `si_adm_5` varchar(5) DEFAULT 'NR',
  `si_adm_6` varchar(5) DEFAULT 'NR',
  `si_gsa_1` varchar(5) DEFAULT 'NR',
  `si_gsa_2` varchar(5) DEFAULT 'NR',
  `si_gsa_3` varchar(5) DEFAULT 'NR',
  `si_gsa_4` varchar(5) DEFAULT 'NR',
  `si_gsa_5` varchar(5) DEFAULT 'NR',
  `si_gsa_6` varchar(5) DEFAULT 'NR',
  `si_gsa_7` varchar(5) DEFAULT 'NR',
  `si_gsa_8` varchar(5) DEFAULT 'NR',
  `si_gsa_9` varchar(5) DEFAULT 'NR',
  `si_gsa_10` varchar(5) DEFAULT 'NR',
  `si_gsa_11` varchar(5) DEFAULT 'NR',
  `si_gsa_12` varchar(5) DEFAULT 'NR',
  `si_gsa_13` varchar(5) DEFAULT 'NR',
  `si_gsa_14` varchar(5) DEFAULT 'NR',
  `si_sst_1` varchar(5) DEFAULT 'NR',
  `si_sst_2` varchar(5) DEFAULT 'NR',
  `si_sst_3` varchar(5) DEFAULT 'NR',
  `si_sst_4` varchar(5) DEFAULT 'NR',
  `si_sst_5` varchar(5) DEFAULT 'NR',
  `si_sst_6` varchar(5) DEFAULT 'NR',
  `si_sst_7` varchar(5) DEFAULT 'NR',
  `si_sst_8` varchar(5) DEFAULT 'NR',
  `si_sst_9` varchar(5) DEFAULT 'NR',
  `si_sst_10` varchar(5) DEFAULT 'NR'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metrolineaMurosDos_pdc`
--

CREATE TABLE `metrolineaMurosDos_pdc` (
  `consecutivo` int NOT NULL,
  `semana` int NOT NULL,
  `titulo` int NOT NULL,
  `tipoPaquete` varchar(200) NOT NULL,
  `paqueteContratacion` varchar(200) DEFAULT NULL,
  `contratos` varchar(200) DEFAULT NULL,
  `numeroSubcontratos` int DEFAULT '1',
  `subcontratoPaquete` int NOT NULL DEFAULT '1',
  `estado` varchar(200) DEFAULT NULL,
  `fechaElaboracionPliegos` date DEFAULT NULL,
  `diasElaboracionPliegos` int DEFAULT NULL,
  `fechaRealElaboracionPliegos` date DEFAULT NULL,
  `fechaIngresoLicify` date DEFAULT NULL,
  `diasIngresoLicify` int DEFAULT NULL,
  `fechaRealIngresoLicify` date DEFAULT NULL,
  `fechaEntregaPliegos` date DEFAULT NULL,
  `diasEntregaPliegos` int DEFAULT NULL,
  `fechaRealEntregaPliegos` date DEFAULT NULL,
  `fechaReciboPropuestas` date DEFAULT NULL,
  `diasReciboPropuestas` int DEFAULT NULL,
  `fechaRealReciboPropuestas` date DEFAULT NULL,
  `fechaCuadrosComparativos` date DEFAULT NULL,
  `diasCuadrosComparativos` int DEFAULT NULL,
  `fechaRealCuadrosComparativos` date DEFAULT NULL,
  `fechaLegalizacionContrato` date DEFAULT NULL,
  `diasLegalizacionContrato` int DEFAULT NULL,
  `fechaRealLegalizacionContrato` date DEFAULT NULL,
  `fechaFabricacion` date DEFAULT NULL,
  `diasFabricacion` int DEFAULT NULL,
  `fechaRealFabricacion` date DEFAULT NULL,
  `fechaInsumosObra` date DEFAULT NULL,
  `diasInsumosObra` int DEFAULT NULL,
  `fechaRealInsumosObra` date DEFAULT NULL,
  `fechaInicio` date DEFAULT NULL,
  `fechaInicioProyectada` date DEFAULT NULL,
  `fechaRealInicio` date DEFAULT NULL,
  `idProveedorAdjudicado` int DEFAULT NULL,
  `numeroContrato` varchar(50) DEFAULT NULL,
  `aplicaPolizas` int NOT NULL DEFAULT '1',
  `fechaVencimientoPolizas` date DEFAULT NULL,
  `valorPresupuesto` float DEFAULT NULL,
  `valorPrimeraNegociacion` float DEFAULT NULL,
  `valorAdjudicado` float DEFAULT NULL,
  `valorAnticipo` float DEFAULT NULL,
  `valorReclamado` float DEFAULT NULL,
  `valorDevoluciones` float DEFAULT NULL,
  `observacionesContrato` mediumtext
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metrolineaMurosDos_profesionales`
--

CREATE TABLE `metrolineaMurosDos_profesionales` (
  `id` int NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `cargo` varchar(100) NOT NULL,
  `activo` int NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metrolineaMurosDos_programa`
--

CREATE TABLE `metrolineaMurosDos_programa` (
  `Consecutivo` int NOT NULL,
  `Id` varchar(500) DEFAULT NULL,
  `Actividad` varchar(500) DEFAULT NULL,
  `Titulo` int DEFAULT NULL,
  `Fecha_Inicio` date DEFAULT NULL,
  `Fecha_Fin` date DEFAULT NULL,
  `Ruta_Critica` int DEFAULT NULL,
  `Ejecutado` float DEFAULT '0',
  `Estado` varchar(50) DEFAULT NULL,
  `Semanas_Inicio` int DEFAULT '0',
  `Estado_Restricciones` float DEFAULT '0',
  `D_y_E` float DEFAULT '0',
  `Materiales` float DEFAULT '0',
  `MdeO` float DEFAULT '0',
  `Equipos` float DEFAULT '0',
  `Predecesora` float DEFAULT '0',
  `Pdto_Cons` float DEFAULT '0',
  `Modelo` varchar(9) DEFAULT '0',
  `Responsable_AIA` varchar(100) DEFAULT NULL,
  `Observaciones` mediumtext,
  `Ult_Act_Est` date DEFAULT NULL,
  `Ult_Act_Restr` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metrolineaMurosDos_programacion_semanal`
--

CREATE TABLE `metrolineaMurosDos_programacion_semanal` (
  `Consecutivo` int NOT NULL,
  `Semana` int DEFAULT NULL,
  `Consecutivo_En_Programa` int NOT NULL,
  `Id` varchar(500) DEFAULT NULL,
  `Actividad` varchar(500) DEFAULT NULL,
  `Descripcion` mediumtext,
  `Ubicacion` mediumtext,
  `Fecha_Inicio` date DEFAULT NULL,
  `Fecha_Fin` date DEFAULT NULL,
  `Sub_Contratista` varchar(200) DEFAULT NULL,
  `Responsable_AIA` varchar(200) DEFAULT NULL,
  `Empresa` varchar(200) NOT NULL DEFAULT 'AIA',
  `Ejecutado` float DEFAULT NULL,
  `medir_productividad` int DEFAULT '0',
  `Unidad` varchar(10) DEFAULT NULL,
  `cantidad_ppto` int DEFAULT NULL,
  `Cantidad_Sugerida` float DEFAULT NULL,
  `Compromiso` float DEFAULT NULL,
  `Ejecutado_Real` float DEFAULT NULL,
  `P_Completado` float DEFAULT NULL,
  `PAC` int DEFAULT NULL,
  `Critica` int DEFAULT NULL,
  `Atrasada` int DEFAULT NULL,
  `Activa` varchar(3) DEFAULT NULL,
  `Prog_Sin_Restricciones_100` int DEFAULT NULL,
  `Categoria_CNP` varchar(100) DEFAULT NULL,
  `CNP` varchar(100) DEFAULT NULL,
  `Observaciones_CNP` mediumtext,
  `Categoria_CNC` varchar(100) DEFAULT NULL,
  `CNC` varchar(100) DEFAULT NULL,
  `Observaciones_CNC` mediumtext,
  `Rendimientos` varchar(500) DEFAULT NULL,
  `codigo_actividad` varchar(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metrolineaMurosDos_programa_consolidado`
--

CREATE TABLE `metrolineaMurosDos_programa_consolidado` (
  `Consecutivo` int NOT NULL,
  `Semana` int NOT NULL,
  `Consecutivo_en_Programa` int NOT NULL,
  `Id` varchar(500) DEFAULT NULL,
  `Actividad` varchar(500) DEFAULT NULL,
  `Titulo` int DEFAULT NULL,
  `Fecha_Inicio` date DEFAULT NULL,
  `Fecha_Fin` date DEFAULT NULL,
  `Ruta_Critica` int DEFAULT NULL,
  `Ejecutado` float DEFAULT '0',
  `Estado` varchar(100) DEFAULT NULL,
  `Semanas_Inicio` int DEFAULT '0',
  `Estado_Restricciones` float NOT NULL DEFAULT '0',
  `D_y_E` varchar(9) NOT NULL DEFAULT '0',
  `Materiales` varchar(9) NOT NULL DEFAULT '0',
  `MdeO` varchar(9) NOT NULL DEFAULT '0',
  `Equipos` varchar(9) NOT NULL DEFAULT '0',
  `Predecesora` varchar(9) NOT NULL DEFAULT '0',
  `Pdto_Cons` varchar(9) NOT NULL DEFAULT '0',
  `Modelo` varchar(9) NOT NULL DEFAULT '0',
  `Sub_Contratista` varchar(100) DEFAULT NULL,
  `Responsable_AIA` varchar(100) DEFAULT NULL,
  `Observaciones` mediumtext,
  `Ult_Act_Est` date DEFAULT NULL,
  `Ult_Act_Restr` date DEFAULT NULL,
  `Activa` int NOT NULL DEFAULT '0',
  `Ejecutado_Siguiente_Semana` float DEFAULT NULL,
  `codigo_actividad` varchar(11) DEFAULT NULL,
  `medir_productividad` int DEFAULT '0',
  `cantidad_ppto` int DEFAULT NULL,
  `unidad` varchar(20) DEFAULT NULL,
  `programaAnteriorAsociar` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metrolineaMurosDos_semanas_activas`
--

CREATE TABLE `metrolineaMurosDos_semanas_activas` (
  `Id` int NOT NULL,
  `Semana` int NOT NULL,
  `Fecha_Inicio_Sem` date NOT NULL,
  `Fecha_Fin_Sem` date NOT NULL,
  `Semanal_Confirmada` int DEFAULT '0',
  `fechaCierreCompromisos` date DEFAULT NULL,
  `fechaCreacionSemana` date DEFAULT NULL,
  `reprogramacion` int NOT NULL DEFAULT '0',
  `diferenciaEstructuraCron` int NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metrolineaMurosDos_subcontratistas`
--

CREATE TABLE `metrolineaMurosDos_subcontratistas` (
  `Id` int NOT NULL,
  `subcontratista` varchar(200) NOT NULL,
  `correo_contacto` varchar(200) NOT NULL,
  `NIT` bigint NOT NULL,
  `alcance` varchar(200) NOT NULL,
  `tipo_proveedor` varchar(200) NOT NULL,
  `activo` int NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metrolineaSeis_actividades`
--

CREATE TABLE `metrolineaSeis_actividades` (
  `Id` int NOT NULL,
  `codigo` int NOT NULL,
  `actividad` varchar(300) NOT NULL,
  `descripcionActividad` mediumtext,
  `actividadInicio` varchar(500) DEFAULT NULL,
  `nombreActividadInicio` varchar(500) DEFAULT NULL,
  `fechaInicio` date DEFAULT NULL,
  `tipoContrato` varchar(10) DEFAULT NULL,
  `semanaActualizacion` int DEFAULT NULL,
  `SI1` varchar(200) DEFAULT NULL,
  `paqueteSI1` varchar(200) DEFAULT NULL,
  `SI2` varchar(200) DEFAULT NULL,
  `paqueteSI2` varchar(200) DEFAULT NULL,
  `SI3` varchar(200) DEFAULT NULL,
  `paqueteSI3` varchar(200) DEFAULT NULL,
  `SI4` varchar(200) DEFAULT NULL,
  `paqueteSI4` varchar(200) DEFAULT NULL,
  `SI5` varchar(200) DEFAULT NULL,
  `paqueteSI5` varchar(200) DEFAULT NULL,
  `S1` varchar(200) DEFAULT NULL,
  `paqueteS1` varchar(200) DEFAULT NULL,
  `S2` varchar(200) DEFAULT NULL,
  `paqueteS2` varchar(200) DEFAULT NULL,
  `S3` varchar(200) DEFAULT NULL,
  `paqueteS3` varchar(200) DEFAULT NULL,
  `S4` varchar(200) DEFAULT NULL,
  `paqueteS4` varchar(200) DEFAULT NULL,
  `S5` varchar(200) DEFAULT NULL,
  `paqueteS5` varchar(200) DEFAULT NULL,
  `MO1` varchar(200) DEFAULT NULL,
  `paqueteMO1` varchar(200) DEFAULT NULL,
  `MO2` varchar(200) DEFAULT NULL,
  `paqueteMO2` varchar(200) DEFAULT NULL,
  `MO3` varchar(200) DEFAULT NULL,
  `paqueteMO3` varchar(200) DEFAULT NULL,
  `MO4` varchar(200) DEFAULT NULL,
  `paqueteMO4` varchar(200) DEFAULT NULL,
  `MO5` varchar(200) DEFAULT NULL,
  `paqueteMO5` varchar(200) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metrolineaSeis_cambios`
--

CREATE TABLE `metrolineaSeis_cambios` (
  `id` int NOT NULL,
  `solicitanteCambio` int DEFAULT NULL,
  `detalleSolicitanteOtro` longtext,
  `fechaSolicitud` date DEFAULT NULL,
  `prioridad` int DEFAULT NULL,
  `tipoCambio` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `responsableSolucion` int DEFAULT NULL,
  `detalleResponsableSolucion` longtext,
  `justificacion` longtext,
  `descripcion` longtext,
  `incidenciaAlcance` longtext,
  `tiempoCronograma` float DEFAULT NULL,
  `tiempoCronogramaAfectado` float DEFAULT NULL,
  `incidenciaCronograma` longtext,
  `valorPresupuesto` float DEFAULT NULL,
  `costoDirecto` float DEFAULT NULL,
  `costoDirectoAIU` float DEFAULT NULL,
  `costoDirectoAIUIVA` float DEFAULT NULL,
  `valorAprobado` float DEFAULT NULL,
  `incidenciaPresupuesto` longtext,
  `incidenciaCalidad` longtext,
  `incidenciaRiesgo` longtext,
  `incidenciaRecurso` longtext,
  `fechaTentativaDefinicion` date DEFAULT NULL,
  `fechaEntregaInterventoria` date DEFAULT NULL,
  `Observaciones` longtext,
  `fechaDefinicion` date DEFAULT NULL,
  `aprobacion` int DEFAULT NULL,
  `soportes` longtext
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metrolineaSeis_cic`
--

CREATE TABLE `metrolineaSeis_cic` (
  `Id` int NOT NULL,
  `Semana` int DEFAULT NULL,
  `subcontratista` varchar(200) DEFAULT NULL,
  `correo_contacto` varchar(200) DEFAULT NULL,
  `NIT` varchar(10) DEFAULT NULL,
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
  `Observaciones` mediumtext,
  `mdo_cal_1` varchar(5) DEFAULT 'NR',
  `mdo_cal_2` varchar(5) DEFAULT 'NR',
  `mdo_cal_3` varchar(5) DEFAULT 'NR',
  `mdo_adm_1` varchar(5) DEFAULT 'NR',
  `mdo_adm_2` varchar(5) DEFAULT 'NR',
  `mdo_adm_3` varchar(5) DEFAULT 'NR',
  `mdo_adm_4` varchar(5) DEFAULT 'NR',
  `mdo_adm_5` varchar(5) DEFAULT 'NR',
  `mdo_gsa_1` varchar(5) DEFAULT 'NR',
  `mdo_gsa_2` varchar(5) DEFAULT 'NR',
  `mdo_gsa_3` varchar(5) DEFAULT 'NR',
  `mdo_gsa_4` varchar(5) DEFAULT 'NR',
  `mdo_gsa_5` varchar(5) DEFAULT 'NR',
  `mdo_gsa_6` varchar(5) DEFAULT 'NR',
  `mdo_gsa_7` varchar(5) DEFAULT 'NR',
  `mdo_gsa_8` varchar(5) DEFAULT 'NR',
  `mdo_sst_1` varchar(5) DEFAULT 'NR',
  `mdo_sst_2` varchar(5) DEFAULT 'NR',
  `mdo_sst_3` varchar(5) DEFAULT 'NR',
  `mdo_sst_4` varchar(5) DEFAULT 'NR',
  `mdo_sst_5` varchar(5) DEFAULT 'NR',
  `mdo_sst_6` varchar(5) DEFAULT 'NR',
  `mdo_sst_7` varchar(5) DEFAULT 'NR',
  `mdo_sst_8` varchar(5) DEFAULT 'NR',
  `mdo_sst_9` varchar(5) DEFAULT 'NR',
  `mdo_sst_10` varchar(5) DEFAULT 'NR',
  `si_cal_1` varchar(5) DEFAULT 'NR',
  `si_cal_2` varchar(5) DEFAULT 'NR',
  `si_cal_3` varchar(5) DEFAULT 'NR',
  `si_adm_1` varchar(5) DEFAULT 'NR',
  `si_adm_2` varchar(5) DEFAULT 'NR',
  `si_adm_3` varchar(5) DEFAULT 'NR',
  `si_adm_4` varchar(5) DEFAULT 'NR',
  `si_adm_5` varchar(5) DEFAULT 'NR',
  `si_adm_6` varchar(5) DEFAULT 'NR',
  `si_gsa_1` varchar(5) DEFAULT 'NR',
  `si_gsa_2` varchar(5) DEFAULT 'NR',
  `si_gsa_3` varchar(5) DEFAULT 'NR',
  `si_gsa_4` varchar(5) DEFAULT 'NR',
  `si_gsa_5` varchar(5) DEFAULT 'NR',
  `si_gsa_6` varchar(5) DEFAULT 'NR',
  `si_gsa_7` varchar(5) DEFAULT 'NR',
  `si_gsa_8` varchar(5) DEFAULT 'NR',
  `si_gsa_9` varchar(5) DEFAULT 'NR',
  `si_gsa_10` varchar(5) DEFAULT 'NR',
  `si_gsa_11` varchar(5) DEFAULT 'NR',
  `si_gsa_12` varchar(5) DEFAULT 'NR',
  `si_gsa_13` varchar(5) DEFAULT 'NR',
  `si_gsa_14` varchar(5) DEFAULT 'NR',
  `si_sst_1` varchar(5) DEFAULT 'NR',
  `si_sst_2` varchar(5) DEFAULT 'NR',
  `si_sst_3` varchar(5) DEFAULT 'NR',
  `si_sst_4` varchar(5) DEFAULT 'NR',
  `si_sst_5` varchar(5) DEFAULT 'NR',
  `si_sst_6` varchar(5) DEFAULT 'NR',
  `si_sst_7` varchar(5) DEFAULT 'NR',
  `si_sst_8` varchar(5) DEFAULT 'NR',
  `si_sst_9` varchar(5) DEFAULT 'NR',
  `si_sst_10` varchar(5) DEFAULT 'NR'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metrolineaSeis_pdc`
--

CREATE TABLE `metrolineaSeis_pdc` (
  `consecutivo` int NOT NULL,
  `semana` int NOT NULL,
  `titulo` int NOT NULL,
  `tipoPaquete` varchar(200) NOT NULL,
  `paqueteContratacion` varchar(200) DEFAULT NULL,
  `contratos` varchar(200) DEFAULT NULL,
  `numeroSubcontratos` int DEFAULT '1',
  `subcontratoPaquete` int NOT NULL DEFAULT '1',
  `estado` varchar(200) DEFAULT NULL,
  `fechaElaboracionPliegos` date DEFAULT NULL,
  `diasElaboracionPliegos` int DEFAULT NULL,
  `fechaRealElaboracionPliegos` date DEFAULT NULL,
  `fechaIngresoLicify` date DEFAULT NULL,
  `diasIngresoLicify` int DEFAULT NULL,
  `fechaRealIngresoLicify` date DEFAULT NULL,
  `fechaEntregaPliegos` date DEFAULT NULL,
  `diasEntregaPliegos` int DEFAULT NULL,
  `fechaRealEntregaPliegos` date DEFAULT NULL,
  `fechaReciboPropuestas` date DEFAULT NULL,
  `diasReciboPropuestas` int DEFAULT NULL,
  `fechaRealReciboPropuestas` date DEFAULT NULL,
  `fechaCuadrosComparativos` date DEFAULT NULL,
  `diasCuadrosComparativos` int DEFAULT NULL,
  `fechaRealCuadrosComparativos` date DEFAULT NULL,
  `fechaLegalizacionContrato` date DEFAULT NULL,
  `diasLegalizacionContrato` int DEFAULT NULL,
  `fechaRealLegalizacionContrato` date DEFAULT NULL,
  `fechaFabricacion` date DEFAULT NULL,
  `diasFabricacion` int DEFAULT NULL,
  `fechaRealFabricacion` date DEFAULT NULL,
  `fechaInsumosObra` date DEFAULT NULL,
  `diasInsumosObra` int DEFAULT NULL,
  `fechaRealInsumosObra` date DEFAULT NULL,
  `fechaInicio` date DEFAULT NULL,
  `fechaInicioProyectada` date DEFAULT NULL,
  `fechaRealInicio` date DEFAULT NULL,
  `idProveedorAdjudicado` int DEFAULT NULL,
  `numeroContrato` varchar(50) DEFAULT NULL,
  `aplicaPolizas` int NOT NULL DEFAULT '1',
  `fechaVencimientoPolizas` date DEFAULT NULL,
  `valorPresupuesto` float DEFAULT NULL,
  `valorPrimeraNegociacion` float DEFAULT NULL,
  `valorAdjudicado` float DEFAULT NULL,
  `valorAnticipo` float DEFAULT NULL,
  `valorReclamado` float DEFAULT NULL,
  `valorDevoluciones` float DEFAULT NULL,
  `observacionesContrato` mediumtext
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metrolineaSeis_pi_shared_constraints`
--

CREATE TABLE `metrolineaSeis_pi_shared_constraints` (
  `Id` bigint UNSIGNED NOT NULL,
  `Semana` int NOT NULL,
  `Restriccion` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `ValorObjetivo` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `Nota` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `CreadoPor` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `CreadoEn` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ActualizadoEn` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metrolineaSeis_pi_shared_constraint_links`
--

CREATE TABLE `metrolineaSeis_pi_shared_constraint_links` (
  `Id` bigint UNSIGNED NOT NULL,
  `SharedConstraintId` bigint UNSIGNED NOT NULL,
  `Semana` int NOT NULL,
  `ConsecutivoEnPrograma` bigint NOT NULL,
  `ValorAplicado` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `OverrideLocal` tinyint(1) NOT NULL DEFAULT '0',
  `AplicadoEn` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metrolineaSeis_profesionales`
--

CREATE TABLE `metrolineaSeis_profesionales` (
  `id` int NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `cargo` varchar(100) NOT NULL,
  `activo` int NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metrolineaSeis_programa`
--

CREATE TABLE `metrolineaSeis_programa` (
  `Consecutivo` int NOT NULL,
  `Id` varchar(500) DEFAULT NULL,
  `Actividad` varchar(500) DEFAULT NULL,
  `Titulo` int DEFAULT NULL,
  `Fecha_Inicio` date DEFAULT NULL,
  `Fecha_Fin` date DEFAULT NULL,
  `Ruta_Critica` int DEFAULT NULL,
  `Ejecutado` float DEFAULT '0',
  `Estado` varchar(50) DEFAULT NULL,
  `Semanas_Inicio` int DEFAULT '0',
  `Estado_Restricciones` float DEFAULT '0',
  `D_y_E` float DEFAULT '0',
  `Materiales` float DEFAULT '0',
  `MdeO` float DEFAULT '0',
  `Equipos` float DEFAULT '0',
  `Predecesora` float DEFAULT '0',
  `Pdto_Cons` float DEFAULT '0',
  `Modelo` varchar(9) DEFAULT '0',
  `Responsable_AIA` varchar(100) DEFAULT NULL,
  `Observaciones` mediumtext,
  `Ult_Act_Est` date DEFAULT NULL,
  `Ult_Act_Restr` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metrolineaSeis_programacion_semanal`
--

CREATE TABLE `metrolineaSeis_programacion_semanal` (
  `Consecutivo` int NOT NULL,
  `Semana` int DEFAULT NULL,
  `Consecutivo_En_Programa` int NOT NULL,
  `Id` varchar(500) DEFAULT NULL,
  `Actividad` varchar(500) DEFAULT NULL,
  `Descripcion` mediumtext,
  `Ubicacion` mediumtext,
  `Fecha_Inicio` date DEFAULT NULL,
  `Fecha_Fin` date DEFAULT NULL,
  `Sub_Contratista` varchar(200) DEFAULT NULL,
  `Responsable_AIA` varchar(200) DEFAULT NULL,
  `Empresa` varchar(200) NOT NULL DEFAULT 'AIA',
  `Ejecutado` float DEFAULT NULL,
  `medir_productividad` int DEFAULT '0',
  `Unidad` varchar(10) DEFAULT NULL,
  `cantidad_ppto` int DEFAULT NULL,
  `Cantidad_Sugerida` float DEFAULT NULL,
  `Compromiso` float DEFAULT NULL,
  `Ejecutado_Real` float DEFAULT NULL,
  `P_Completado` float DEFAULT NULL,
  `PAC` int DEFAULT NULL,
  `Critica` int DEFAULT NULL,
  `Atrasada` int DEFAULT NULL,
  `Activa` varchar(3) DEFAULT NULL,
  `Prog_Sin_Restricciones_100` int DEFAULT NULL,
  `Categoria_CNP` varchar(100) DEFAULT NULL,
  `CNP` varchar(100) DEFAULT NULL,
  `Observaciones_CNP` mediumtext,
  `Categoria_CNC` varchar(100) DEFAULT NULL,
  `CNC` varchar(100) DEFAULT NULL,
  `Observaciones_CNC` mediumtext,
  `Rendimientos` varchar(500) DEFAULT NULL,
  `codigo_actividad` varchar(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metrolineaSeis_programa_consolidado`
--

CREATE TABLE `metrolineaSeis_programa_consolidado` (
  `Consecutivo` int NOT NULL,
  `Semana` int NOT NULL,
  `Consecutivo_en_Programa` int NOT NULL,
  `Id` varchar(500) DEFAULT NULL,
  `Actividad` varchar(500) DEFAULT NULL,
  `Titulo` int DEFAULT NULL,
  `Fecha_Inicio` date DEFAULT NULL,
  `Fecha_Fin` date DEFAULT NULL,
  `Ruta_Critica` int DEFAULT NULL,
  `Ejecutado` float DEFAULT '0',
  `Estado` varchar(100) DEFAULT NULL,
  `Semanas_Inicio` int DEFAULT '0',
  `Estado_Restricciones` float NOT NULL DEFAULT '0',
  `D_y_E` varchar(9) NOT NULL DEFAULT '0',
  `Materiales` varchar(9) NOT NULL DEFAULT '0',
  `MdeO` varchar(9) NOT NULL DEFAULT '0',
  `Equipos` varchar(9) NOT NULL DEFAULT '0',
  `Predecesora` varchar(9) NOT NULL DEFAULT '0',
  `Pdto_Cons` varchar(9) NOT NULL DEFAULT '0',
  `Modelo` varchar(9) NOT NULL DEFAULT '0',
  `Sub_Contratista` varchar(100) DEFAULT NULL,
  `Responsable_AIA` varchar(100) DEFAULT NULL,
  `Observaciones` mediumtext,
  `Ult_Act_Est` date DEFAULT NULL,
  `Ult_Act_Restr` date DEFAULT NULL,
  `Activa` int NOT NULL DEFAULT '0',
  `Ejecutado_Siguiente_Semana` float DEFAULT NULL,
  `codigo_actividad` varchar(11) DEFAULT NULL,
  `medir_productividad` int DEFAULT '0',
  `cantidad_ppto` int DEFAULT NULL,
  `unidad` varchar(20) DEFAULT NULL,
  `programaAnteriorAsociar` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metrolineaSeis_semanas_activas`
--

CREATE TABLE `metrolineaSeis_semanas_activas` (
  `Id` int NOT NULL,
  `Semana` int NOT NULL,
  `Fecha_Inicio_Sem` date NOT NULL,
  `Fecha_Fin_Sem` date NOT NULL,
  `Semanal_Confirmada` int DEFAULT '0',
  `fechaCierreCompromisos` date DEFAULT NULL,
  `fechaCreacionSemana` date DEFAULT NULL,
  `reprogramacion` int NOT NULL DEFAULT '0',
  `diferenciaEstructuraCron` int NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metrolineaSeis_subcontratistas`
--

CREATE TABLE `metrolineaSeis_subcontratistas` (
  `Id` int NOT NULL,
  `subcontratista` varchar(200) NOT NULL,
  `correo_contacto` varchar(200) NOT NULL,
  `NIT` bigint NOT NULL,
  `alcance` varchar(200) NOT NULL,
  `tipo_proveedor` varchar(200) NOT NULL,
  `activo` int NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metrolineaUno_actividades`
--

CREATE TABLE `metrolineaUno_actividades` (
  `Id` int NOT NULL,
  `codigo` int NOT NULL,
  `actividad` varchar(300) NOT NULL,
  `descripcionActividad` mediumtext,
  `actividadInicio` varchar(500) DEFAULT NULL,
  `nombreActividadInicio` varchar(500) DEFAULT NULL,
  `fechaInicio` date DEFAULT NULL,
  `tipoContrato` varchar(10) DEFAULT NULL,
  `semanaActualizacion` int DEFAULT NULL,
  `SI1` varchar(200) DEFAULT NULL,
  `paqueteSI1` varchar(200) DEFAULT NULL,
  `SI2` varchar(200) DEFAULT NULL,
  `paqueteSI2` varchar(200) DEFAULT NULL,
  `SI3` varchar(200) DEFAULT NULL,
  `paqueteSI3` varchar(200) DEFAULT NULL,
  `SI4` varchar(200) DEFAULT NULL,
  `paqueteSI4` varchar(200) DEFAULT NULL,
  `SI5` varchar(200) DEFAULT NULL,
  `paqueteSI5` varchar(200) DEFAULT NULL,
  `S1` varchar(200) DEFAULT NULL,
  `paqueteS1` varchar(200) DEFAULT NULL,
  `S2` varchar(200) DEFAULT NULL,
  `paqueteS2` varchar(200) DEFAULT NULL,
  `S3` varchar(200) DEFAULT NULL,
  `paqueteS3` varchar(200) DEFAULT NULL,
  `S4` varchar(200) DEFAULT NULL,
  `paqueteS4` varchar(200) DEFAULT NULL,
  `S5` varchar(200) DEFAULT NULL,
  `paqueteS5` varchar(200) DEFAULT NULL,
  `MO1` varchar(200) DEFAULT NULL,
  `paqueteMO1` varchar(200) DEFAULT NULL,
  `MO2` varchar(200) DEFAULT NULL,
  `paqueteMO2` varchar(200) DEFAULT NULL,
  `MO3` varchar(200) DEFAULT NULL,
  `paqueteMO3` varchar(200) DEFAULT NULL,
  `MO4` varchar(200) DEFAULT NULL,
  `paqueteMO4` varchar(200) DEFAULT NULL,
  `MO5` varchar(200) DEFAULT NULL,
  `paqueteMO5` varchar(200) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metrolineaUno_cambios`
--

CREATE TABLE `metrolineaUno_cambios` (
  `id` int NOT NULL,
  `solicitanteCambio` int DEFAULT NULL,
  `detalleSolicitanteOtro` longtext,
  `fechaSolicitud` date DEFAULT NULL,
  `prioridad` int DEFAULT NULL,
  `tipoCambio` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `responsableSolucion` int DEFAULT NULL,
  `detalleResponsableSolucion` longtext,
  `justificacion` longtext,
  `descripcion` longtext,
  `incidenciaAlcance` longtext,
  `tiempoCronograma` float DEFAULT NULL,
  `tiempoCronogramaAfectado` float DEFAULT NULL,
  `incidenciaCronograma` longtext,
  `valorPresupuesto` float DEFAULT NULL,
  `costoDirecto` float DEFAULT NULL,
  `costoDirectoAIU` float DEFAULT NULL,
  `costoDirectoAIUIVA` float DEFAULT NULL,
  `valorAprobado` float DEFAULT NULL,
  `incidenciaPresupuesto` longtext,
  `incidenciaCalidad` longtext,
  `incidenciaRiesgo` longtext,
  `incidenciaRecurso` longtext,
  `fechaTentativaDefinicion` date DEFAULT NULL,
  `fechaEntregaInterventoria` date DEFAULT NULL,
  `Observaciones` longtext,
  `fechaDefinicion` date DEFAULT NULL,
  `aprobacion` int DEFAULT NULL,
  `soportes` longtext
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metrolineaUno_cic`
--

CREATE TABLE `metrolineaUno_cic` (
  `Id` int NOT NULL,
  `Semana` int DEFAULT NULL,
  `subcontratista` varchar(200) DEFAULT NULL,
  `correo_contacto` varchar(200) DEFAULT NULL,
  `NIT` varchar(10) DEFAULT NULL,
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
  `Observaciones` mediumtext,
  `mdo_cal_1` varchar(5) DEFAULT 'NR',
  `mdo_cal_2` varchar(5) DEFAULT 'NR',
  `mdo_cal_3` varchar(5) DEFAULT 'NR',
  `mdo_adm_1` varchar(5) DEFAULT 'NR',
  `mdo_adm_2` varchar(5) DEFAULT 'NR',
  `mdo_adm_3` varchar(5) DEFAULT 'NR',
  `mdo_adm_4` varchar(5) DEFAULT 'NR',
  `mdo_adm_5` varchar(5) DEFAULT 'NR',
  `mdo_gsa_1` varchar(5) DEFAULT 'NR',
  `mdo_gsa_2` varchar(5) DEFAULT 'NR',
  `mdo_gsa_3` varchar(5) DEFAULT 'NR',
  `mdo_gsa_4` varchar(5) DEFAULT 'NR',
  `mdo_gsa_5` varchar(5) DEFAULT 'NR',
  `mdo_gsa_6` varchar(5) DEFAULT 'NR',
  `mdo_gsa_7` varchar(5) DEFAULT 'NR',
  `mdo_gsa_8` varchar(5) DEFAULT 'NR',
  `mdo_sst_1` varchar(5) DEFAULT 'NR',
  `mdo_sst_2` varchar(5) DEFAULT 'NR',
  `mdo_sst_3` varchar(5) DEFAULT 'NR',
  `mdo_sst_4` varchar(5) DEFAULT 'NR',
  `mdo_sst_5` varchar(5) DEFAULT 'NR',
  `mdo_sst_6` varchar(5) DEFAULT 'NR',
  `mdo_sst_7` varchar(5) DEFAULT 'NR',
  `mdo_sst_8` varchar(5) DEFAULT 'NR',
  `mdo_sst_9` varchar(5) DEFAULT 'NR',
  `mdo_sst_10` varchar(5) DEFAULT 'NR',
  `si_cal_1` varchar(5) DEFAULT 'NR',
  `si_cal_2` varchar(5) DEFAULT 'NR',
  `si_cal_3` varchar(5) DEFAULT 'NR',
  `si_adm_1` varchar(5) DEFAULT 'NR',
  `si_adm_2` varchar(5) DEFAULT 'NR',
  `si_adm_3` varchar(5) DEFAULT 'NR',
  `si_adm_4` varchar(5) DEFAULT 'NR',
  `si_adm_5` varchar(5) DEFAULT 'NR',
  `si_adm_6` varchar(5) DEFAULT 'NR',
  `si_gsa_1` varchar(5) DEFAULT 'NR',
  `si_gsa_2` varchar(5) DEFAULT 'NR',
  `si_gsa_3` varchar(5) DEFAULT 'NR',
  `si_gsa_4` varchar(5) DEFAULT 'NR',
  `si_gsa_5` varchar(5) DEFAULT 'NR',
  `si_gsa_6` varchar(5) DEFAULT 'NR',
  `si_gsa_7` varchar(5) DEFAULT 'NR',
  `si_gsa_8` varchar(5) DEFAULT 'NR',
  `si_gsa_9` varchar(5) DEFAULT 'NR',
  `si_gsa_10` varchar(5) DEFAULT 'NR',
  `si_gsa_11` varchar(5) DEFAULT 'NR',
  `si_gsa_12` varchar(5) DEFAULT 'NR',
  `si_gsa_13` varchar(5) DEFAULT 'NR',
  `si_gsa_14` varchar(5) DEFAULT 'NR',
  `si_sst_1` varchar(5) DEFAULT 'NR',
  `si_sst_2` varchar(5) DEFAULT 'NR',
  `si_sst_3` varchar(5) DEFAULT 'NR',
  `si_sst_4` varchar(5) DEFAULT 'NR',
  `si_sst_5` varchar(5) DEFAULT 'NR',
  `si_sst_6` varchar(5) DEFAULT 'NR',
  `si_sst_7` varchar(5) DEFAULT 'NR',
  `si_sst_8` varchar(5) DEFAULT 'NR',
  `si_sst_9` varchar(5) DEFAULT 'NR',
  `si_sst_10` varchar(5) DEFAULT 'NR'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metrolineaUno_pdc`
--

CREATE TABLE `metrolineaUno_pdc` (
  `consecutivo` int NOT NULL,
  `semana` int NOT NULL,
  `titulo` int NOT NULL,
  `tipoPaquete` varchar(200) NOT NULL,
  `paqueteContratacion` varchar(200) DEFAULT NULL,
  `contratos` varchar(200) DEFAULT NULL,
  `numeroSubcontratos` int DEFAULT '1',
  `subcontratoPaquete` int NOT NULL DEFAULT '1',
  `estado` varchar(200) DEFAULT NULL,
  `fechaElaboracionPliegos` date DEFAULT NULL,
  `diasElaboracionPliegos` int DEFAULT NULL,
  `fechaRealElaboracionPliegos` date DEFAULT NULL,
  `fechaIngresoLicify` date DEFAULT NULL,
  `diasIngresoLicify` int DEFAULT NULL,
  `fechaRealIngresoLicify` date DEFAULT NULL,
  `fechaEntregaPliegos` date DEFAULT NULL,
  `diasEntregaPliegos` int DEFAULT NULL,
  `fechaRealEntregaPliegos` date DEFAULT NULL,
  `fechaReciboPropuestas` date DEFAULT NULL,
  `diasReciboPropuestas` int DEFAULT NULL,
  `fechaRealReciboPropuestas` date DEFAULT NULL,
  `fechaCuadrosComparativos` date DEFAULT NULL,
  `diasCuadrosComparativos` int DEFAULT NULL,
  `fechaRealCuadrosComparativos` date DEFAULT NULL,
  `fechaLegalizacionContrato` date DEFAULT NULL,
  `diasLegalizacionContrato` int DEFAULT NULL,
  `fechaRealLegalizacionContrato` date DEFAULT NULL,
  `fechaFabricacion` date DEFAULT NULL,
  `diasFabricacion` int DEFAULT NULL,
  `fechaRealFabricacion` date DEFAULT NULL,
  `fechaInsumosObra` date DEFAULT NULL,
  `diasInsumosObra` int DEFAULT NULL,
  `fechaRealInsumosObra` date DEFAULT NULL,
  `fechaInicio` date DEFAULT NULL,
  `fechaInicioProyectada` date DEFAULT NULL,
  `fechaRealInicio` date DEFAULT NULL,
  `idProveedorAdjudicado` int DEFAULT NULL,
  `numeroContrato` varchar(50) DEFAULT NULL,
  `aplicaPolizas` int NOT NULL DEFAULT '1',
  `fechaVencimientoPolizas` date DEFAULT NULL,
  `valorPresupuesto` float DEFAULT NULL,
  `valorPrimeraNegociacion` float DEFAULT NULL,
  `valorAdjudicado` float DEFAULT NULL,
  `valorAnticipo` float DEFAULT NULL,
  `valorReclamado` float DEFAULT NULL,
  `valorDevoluciones` float DEFAULT NULL,
  `observacionesContrato` mediumtext
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metrolineaUno_pi_shared_constraints`
--

CREATE TABLE `metrolineaUno_pi_shared_constraints` (
  `Id` bigint UNSIGNED NOT NULL,
  `Semana` int NOT NULL,
  `Restriccion` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `ValorObjetivo` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `Nota` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `CreadoPor` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `CreadoEn` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ActualizadoEn` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metrolineaUno_pi_shared_constraint_links`
--

CREATE TABLE `metrolineaUno_pi_shared_constraint_links` (
  `Id` bigint UNSIGNED NOT NULL,
  `SharedConstraintId` bigint UNSIGNED NOT NULL,
  `Semana` int NOT NULL,
  `ConsecutivoEnPrograma` bigint NOT NULL,
  `ValorAplicado` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `OverrideLocal` tinyint(1) NOT NULL DEFAULT '0',
  `AplicadoEn` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metrolineaUno_profesionales`
--

CREATE TABLE `metrolineaUno_profesionales` (
  `id` int NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `cargo` varchar(100) NOT NULL,
  `activo` int NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metrolineaUno_programa`
--

CREATE TABLE `metrolineaUno_programa` (
  `Consecutivo` int NOT NULL,
  `Id` varchar(500) DEFAULT NULL,
  `Actividad` varchar(500) DEFAULT NULL,
  `Titulo` int DEFAULT NULL,
  `Fecha_Inicio` date DEFAULT NULL,
  `Fecha_Fin` date DEFAULT NULL,
  `Ruta_Critica` int DEFAULT NULL,
  `Ejecutado` float DEFAULT '0',
  `Estado` varchar(50) DEFAULT NULL,
  `Semanas_Inicio` int DEFAULT '0',
  `Estado_Restricciones` float DEFAULT '0',
  `D_y_E` float DEFAULT '0',
  `Materiales` float DEFAULT '0',
  `MdeO` float DEFAULT '0',
  `Equipos` float DEFAULT '0',
  `Predecesora` float DEFAULT '0',
  `Pdto_Cons` float DEFAULT '0',
  `Modelo` varchar(9) DEFAULT '0',
  `Responsable_AIA` varchar(100) DEFAULT NULL,
  `Observaciones` mediumtext,
  `Ult_Act_Est` date DEFAULT NULL,
  `Ult_Act_Restr` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metrolineaUno_programacion_semanal`
--

CREATE TABLE `metrolineaUno_programacion_semanal` (
  `Consecutivo` int NOT NULL,
  `Semana` int DEFAULT NULL,
  `Consecutivo_En_Programa` int NOT NULL,
  `Id` varchar(500) DEFAULT NULL,
  `Actividad` varchar(500) DEFAULT NULL,
  `Descripcion` mediumtext,
  `Ubicacion` mediumtext,
  `Fecha_Inicio` date DEFAULT NULL,
  `Fecha_Fin` date DEFAULT NULL,
  `Sub_Contratista` varchar(200) DEFAULT NULL,
  `Responsable_AIA` varchar(200) DEFAULT NULL,
  `Empresa` varchar(200) NOT NULL DEFAULT 'AIA',
  `Ejecutado` float DEFAULT NULL,
  `medir_productividad` int DEFAULT '0',
  `Unidad` varchar(10) DEFAULT NULL,
  `cantidad_ppto` int DEFAULT NULL,
  `Cantidad_Sugerida` float DEFAULT NULL,
  `Compromiso` float DEFAULT NULL,
  `Ejecutado_Real` float DEFAULT NULL,
  `P_Completado` float DEFAULT NULL,
  `PAC` int DEFAULT NULL,
  `Critica` int DEFAULT NULL,
  `Atrasada` int DEFAULT NULL,
  `Activa` varchar(3) DEFAULT NULL,
  `Prog_Sin_Restricciones_100` int DEFAULT NULL,
  `Categoria_CNP` varchar(100) DEFAULT NULL,
  `CNP` varchar(100) DEFAULT NULL,
  `Observaciones_CNP` mediumtext,
  `Categoria_CNC` varchar(100) DEFAULT NULL,
  `CNC` varchar(100) DEFAULT NULL,
  `Observaciones_CNC` mediumtext,
  `Rendimientos` varchar(500) DEFAULT NULL,
  `codigo_actividad` varchar(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metrolineaUno_programa_consolidado`
--

CREATE TABLE `metrolineaUno_programa_consolidado` (
  `Consecutivo` int NOT NULL,
  `Semana` int NOT NULL,
  `Consecutivo_en_Programa` int NOT NULL,
  `Id` varchar(500) DEFAULT NULL,
  `Actividad` varchar(500) DEFAULT NULL,
  `Titulo` int DEFAULT NULL,
  `Fecha_Inicio` date DEFAULT NULL,
  `Fecha_Fin` date DEFAULT NULL,
  `Ruta_Critica` int DEFAULT NULL,
  `Ejecutado` float DEFAULT '0',
  `Estado` varchar(100) DEFAULT NULL,
  `Semanas_Inicio` int DEFAULT '0',
  `Estado_Restricciones` float NOT NULL DEFAULT '0',
  `D_y_E` varchar(9) NOT NULL DEFAULT '0',
  `Materiales` varchar(9) NOT NULL DEFAULT '0',
  `MdeO` varchar(9) NOT NULL DEFAULT '0',
  `Equipos` varchar(9) NOT NULL DEFAULT '0',
  `Predecesora` varchar(9) NOT NULL DEFAULT '0',
  `Pdto_Cons` varchar(9) NOT NULL DEFAULT '0',
  `Modelo` varchar(9) NOT NULL DEFAULT '0',
  `Sub_Contratista` varchar(100) DEFAULT NULL,
  `Responsable_AIA` varchar(100) DEFAULT NULL,
  `Observaciones` mediumtext,
  `Ult_Act_Est` date DEFAULT NULL,
  `Ult_Act_Restr` date DEFAULT NULL,
  `Activa` int NOT NULL DEFAULT '0',
  `Ejecutado_Siguiente_Semana` float DEFAULT NULL,
  `codigo_actividad` varchar(11) DEFAULT NULL,
  `medir_productividad` int DEFAULT '0',
  `cantidad_ppto` int DEFAULT NULL,
  `unidad` varchar(20) DEFAULT NULL,
  `programaAnteriorAsociar` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metrolineaUno_semanas_activas`
--

CREATE TABLE `metrolineaUno_semanas_activas` (
  `Id` int NOT NULL,
  `Semana` int NOT NULL,
  `Fecha_Inicio_Sem` date NOT NULL,
  `Fecha_Fin_Sem` date NOT NULL,
  `Semanal_Confirmada` int DEFAULT '0',
  `fechaCierreCompromisos` date DEFAULT NULL,
  `fechaCreacionSemana` date DEFAULT NULL,
  `reprogramacion` int NOT NULL DEFAULT '0',
  `diferenciaEstructuraCron` int NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metrolineaUno_subcontratistas`
--

CREATE TABLE `metrolineaUno_subcontratistas` (
  `Id` int NOT NULL,
  `subcontratista` varchar(200) NOT NULL,
  `correo_contacto` varchar(200) NOT NULL,
  `NIT` bigint NOT NULL,
  `alcance` varchar(200) NOT NULL,
  `tipo_proveedor` varchar(200) NOT NULL,
  `activo` int NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `milanCampestre_actividades`
--

CREATE TABLE `milanCampestre_actividades` (
  `Id` int NOT NULL,
  `codigo` int NOT NULL,
  `actividad` varchar(300) NOT NULL,
  `descripcionActividad` mediumtext,
  `actividadInicio` varchar(500) DEFAULT NULL,
  `nombreActividadInicio` varchar(500) DEFAULT NULL,
  `fechaInicio` date DEFAULT NULL,
  `tipoContrato` varchar(10) DEFAULT NULL,
  `semanaActualizacion` int DEFAULT NULL,
  `SI1` varchar(200) DEFAULT NULL,
  `paqueteSI1` varchar(200) DEFAULT NULL,
  `SI2` varchar(200) DEFAULT NULL,
  `paqueteSI2` varchar(200) DEFAULT NULL,
  `SI3` varchar(200) DEFAULT NULL,
  `paqueteSI3` varchar(200) DEFAULT NULL,
  `SI4` varchar(200) DEFAULT NULL,
  `paqueteSI4` varchar(200) DEFAULT NULL,
  `SI5` varchar(200) DEFAULT NULL,
  `paqueteSI5` varchar(200) DEFAULT NULL,
  `S1` varchar(200) DEFAULT NULL,
  `paqueteS1` varchar(200) DEFAULT NULL,
  `S2` varchar(200) DEFAULT NULL,
  `paqueteS2` varchar(200) DEFAULT NULL,
  `S3` varchar(200) DEFAULT NULL,
  `paqueteS3` varchar(200) DEFAULT NULL,
  `S4` varchar(200) DEFAULT NULL,
  `paqueteS4` varchar(200) DEFAULT NULL,
  `S5` varchar(200) DEFAULT NULL,
  `paqueteS5` varchar(200) DEFAULT NULL,
  `MO1` varchar(200) DEFAULT NULL,
  `paqueteMO1` varchar(200) DEFAULT NULL,
  `MO2` varchar(200) DEFAULT NULL,
  `paqueteMO2` varchar(200) DEFAULT NULL,
  `MO3` varchar(200) DEFAULT NULL,
  `paqueteMO3` varchar(200) DEFAULT NULL,
  `MO4` varchar(200) DEFAULT NULL,
  `paqueteMO4` varchar(200) DEFAULT NULL,
  `MO5` varchar(200) DEFAULT NULL,
  `paqueteMO5` varchar(200) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `milanCampestre_cambios`
--

CREATE TABLE `milanCampestre_cambios` (
  `id` int NOT NULL,
  `solicitanteCambio` int DEFAULT NULL,
  `detalleSolicitanteOtro` longtext,
  `fechaSolicitud` date DEFAULT NULL,
  `prioridad` int DEFAULT NULL,
  `tipoCambio` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `responsableSolucion` int DEFAULT NULL,
  `detalleResponsableSolucion` longtext,
  `justificacion` longtext,
  `descripcion` longtext,
  `incidenciaAlcance` longtext,
  `tiempoCronograma` float DEFAULT NULL,
  `tiempoCronogramaAfectado` float DEFAULT NULL,
  `incidenciaCronograma` longtext,
  `valorPresupuesto` float DEFAULT NULL,
  `costoDirecto` float DEFAULT NULL,
  `costoDirectoAIU` float DEFAULT NULL,
  `costoDirectoAIUIVA` float DEFAULT NULL,
  `valorAprobado` float DEFAULT NULL,
  `incidenciaPresupuesto` longtext,
  `incidenciaCalidad` longtext,
  `incidenciaRiesgo` longtext,
  `incidenciaRecurso` longtext,
  `fechaTentativaDefinicion` date DEFAULT NULL,
  `fechaEntregaInterventoria` date DEFAULT NULL,
  `Observaciones` longtext,
  `fechaDefinicion` date DEFAULT NULL,
  `aprobacion` int DEFAULT NULL,
  `soportes` longtext
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `milanCampestre_cic`
--

CREATE TABLE `milanCampestre_cic` (
  `Id` int NOT NULL,
  `Semana` int DEFAULT NULL,
  `subcontratista` varchar(200) DEFAULT NULL,
  `correo_contacto` varchar(200) DEFAULT NULL,
  `NIT` varchar(10) DEFAULT NULL,
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
  `Observaciones` mediumtext,
  `mdo_cal_1` varchar(5) DEFAULT 'NR',
  `mdo_cal_2` varchar(5) DEFAULT 'NR',
  `mdo_cal_3` varchar(5) DEFAULT 'NR',
  `mdo_adm_1` varchar(5) DEFAULT 'NR',
  `mdo_adm_2` varchar(5) DEFAULT 'NR',
  `mdo_adm_3` varchar(5) DEFAULT 'NR',
  `mdo_adm_4` varchar(5) DEFAULT 'NR',
  `mdo_adm_5` varchar(5) DEFAULT 'NR',
  `mdo_gsa_1` varchar(5) DEFAULT 'NR',
  `mdo_gsa_2` varchar(5) DEFAULT 'NR',
  `mdo_gsa_3` varchar(5) DEFAULT 'NR',
  `mdo_gsa_4` varchar(5) DEFAULT 'NR',
  `mdo_gsa_5` varchar(5) DEFAULT 'NR',
  `mdo_gsa_6` varchar(5) DEFAULT 'NR',
  `mdo_gsa_7` varchar(5) DEFAULT 'NR',
  `mdo_gsa_8` varchar(5) DEFAULT 'NR',
  `mdo_sst_1` varchar(5) DEFAULT 'NR',
  `mdo_sst_2` varchar(5) DEFAULT 'NR',
  `mdo_sst_3` varchar(5) DEFAULT 'NR',
  `mdo_sst_4` varchar(5) DEFAULT 'NR',
  `mdo_sst_5` varchar(5) DEFAULT 'NR',
  `mdo_sst_6` varchar(5) DEFAULT 'NR',
  `mdo_sst_7` varchar(5) DEFAULT 'NR',
  `mdo_sst_8` varchar(5) DEFAULT 'NR',
  `mdo_sst_9` varchar(5) DEFAULT 'NR',
  `mdo_sst_10` varchar(5) DEFAULT 'NR',
  `si_cal_1` varchar(5) DEFAULT 'NR',
  `si_cal_2` varchar(5) DEFAULT 'NR',
  `si_cal_3` varchar(5) DEFAULT 'NR',
  `si_adm_1` varchar(5) DEFAULT 'NR',
  `si_adm_2` varchar(5) DEFAULT 'NR',
  `si_adm_3` varchar(5) DEFAULT 'NR',
  `si_adm_4` varchar(5) DEFAULT 'NR',
  `si_adm_5` varchar(5) DEFAULT 'NR',
  `si_adm_6` varchar(5) DEFAULT 'NR',
  `si_gsa_1` varchar(5) DEFAULT 'NR',
  `si_gsa_2` varchar(5) DEFAULT 'NR',
  `si_gsa_3` varchar(5) DEFAULT 'NR',
  `si_gsa_4` varchar(5) DEFAULT 'NR',
  `si_gsa_5` varchar(5) DEFAULT 'NR',
  `si_gsa_6` varchar(5) DEFAULT 'NR',
  `si_gsa_7` varchar(5) DEFAULT 'NR',
  `si_gsa_8` varchar(5) DEFAULT 'NR',
  `si_gsa_9` varchar(5) DEFAULT 'NR',
  `si_gsa_10` varchar(5) DEFAULT 'NR',
  `si_gsa_11` varchar(5) DEFAULT 'NR',
  `si_gsa_12` varchar(5) DEFAULT 'NR',
  `si_gsa_13` varchar(5) DEFAULT 'NR',
  `si_gsa_14` varchar(5) DEFAULT 'NR',
  `si_sst_1` varchar(5) DEFAULT 'NR',
  `si_sst_2` varchar(5) DEFAULT 'NR',
  `si_sst_3` varchar(5) DEFAULT 'NR',
  `si_sst_4` varchar(5) DEFAULT 'NR',
  `si_sst_5` varchar(5) DEFAULT 'NR',
  `si_sst_6` varchar(5) DEFAULT 'NR',
  `si_sst_7` varchar(5) DEFAULT 'NR',
  `si_sst_8` varchar(5) DEFAULT 'NR',
  `si_sst_9` varchar(5) DEFAULT 'NR',
  `si_sst_10` varchar(5) DEFAULT 'NR'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `milanCampestre_pdc`
--

CREATE TABLE `milanCampestre_pdc` (
  `consecutivo` int NOT NULL,
  `semana` int NOT NULL,
  `titulo` int NOT NULL,
  `tipoPaquete` varchar(200) NOT NULL,
  `paqueteContratacion` varchar(200) DEFAULT NULL,
  `contratos` varchar(200) DEFAULT NULL,
  `numeroSubcontratos` int DEFAULT '1',
  `subcontratoPaquete` int NOT NULL DEFAULT '1',
  `estado` varchar(200) DEFAULT NULL,
  `fechaElaboracionPliegos` date DEFAULT NULL,
  `diasElaboracionPliegos` int DEFAULT NULL,
  `fechaRealElaboracionPliegos` date DEFAULT NULL,
  `fechaIngresoLicify` date DEFAULT NULL,
  `diasIngresoLicify` int DEFAULT NULL,
  `fechaRealIngresoLicify` date DEFAULT NULL,
  `fechaEntregaPliegos` date DEFAULT NULL,
  `diasEntregaPliegos` int DEFAULT NULL,
  `fechaRealEntregaPliegos` date DEFAULT NULL,
  `fechaReciboPropuestas` date DEFAULT NULL,
  `diasReciboPropuestas` int DEFAULT NULL,
  `fechaRealReciboPropuestas` date DEFAULT NULL,
  `fechaCuadrosComparativos` date DEFAULT NULL,
  `diasCuadrosComparativos` int DEFAULT NULL,
  `fechaRealCuadrosComparativos` date DEFAULT NULL,
  `fechaLegalizacionContrato` date DEFAULT NULL,
  `diasLegalizacionContrato` int DEFAULT NULL,
  `fechaRealLegalizacionContrato` date DEFAULT NULL,
  `fechaFabricacion` date DEFAULT NULL,
  `diasFabricacion` int DEFAULT NULL,
  `fechaRealFabricacion` date DEFAULT NULL,
  `fechaInsumosObra` date DEFAULT NULL,
  `diasInsumosObra` int DEFAULT NULL,
  `fechaRealInsumosObra` date DEFAULT NULL,
  `fechaInicio` date DEFAULT NULL,
  `fechaInicioProyectada` date DEFAULT NULL,
  `fechaRealInicio` date DEFAULT NULL,
  `idProveedorAdjudicado` int DEFAULT NULL,
  `numeroContrato` varchar(50) DEFAULT NULL,
  `aplicaPolizas` int NOT NULL DEFAULT '1',
  `fechaVencimientoPolizas` date DEFAULT NULL,
  `valorPresupuesto` float DEFAULT NULL,
  `valorPrimeraNegociacion` float DEFAULT NULL,
  `valorAdjudicado` float DEFAULT NULL,
  `valorAnticipo` float DEFAULT NULL,
  `valorReclamado` float DEFAULT NULL,
  `valorDevoluciones` float DEFAULT NULL,
  `observacionesContrato` mediumtext
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `milanCampestre_pi_shared_constraints`
--

CREATE TABLE `milanCampestre_pi_shared_constraints` (
  `Id` bigint UNSIGNED NOT NULL,
  `Semana` int NOT NULL,
  `Restriccion` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `ValorObjetivo` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `Nota` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `CreadoPor` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `CreadoEn` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ActualizadoEn` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `milanCampestre_pi_shared_constraint_links`
--

CREATE TABLE `milanCampestre_pi_shared_constraint_links` (
  `Id` bigint UNSIGNED NOT NULL,
  `SharedConstraintId` bigint UNSIGNED NOT NULL,
  `Semana` int NOT NULL,
  `ConsecutivoEnPrograma` bigint NOT NULL,
  `ValorAplicado` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `OverrideLocal` tinyint(1) NOT NULL DEFAULT '0',
  `AplicadoEn` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `milanCampestre_profesionales`
--

CREATE TABLE `milanCampestre_profesionales` (
  `id` int NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `cargo` varchar(100) NOT NULL,
  `activo` int NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `milanCampestre_programa`
--

CREATE TABLE `milanCampestre_programa` (
  `Consecutivo` int NOT NULL,
  `Id` varchar(500) DEFAULT NULL,
  `Actividad` varchar(500) DEFAULT NULL,
  `Titulo` int DEFAULT NULL,
  `Fecha_Inicio` date DEFAULT NULL,
  `Fecha_Fin` date DEFAULT NULL,
  `Ruta_Critica` int DEFAULT NULL,
  `Ejecutado` float DEFAULT '0',
  `Estado` varchar(50) DEFAULT NULL,
  `Semanas_Inicio` int DEFAULT '0',
  `Estado_Restricciones` float DEFAULT '0',
  `D_y_E` float DEFAULT '0',
  `Materiales` float DEFAULT '0',
  `MdeO` float DEFAULT '0',
  `Equipos` float DEFAULT '0',
  `Predecesora` float DEFAULT '0',
  `Pdto_Cons` float DEFAULT '0',
  `Modelo` varchar(9) DEFAULT '0',
  `Responsable_AIA` varchar(100) DEFAULT NULL,
  `Observaciones` mediumtext,
  `Ult_Act_Est` date DEFAULT NULL,
  `Ult_Act_Restr` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `milanCampestre_programacion_semanal`
--

CREATE TABLE `milanCampestre_programacion_semanal` (
  `Consecutivo` int NOT NULL,
  `Semana` int DEFAULT NULL,
  `Consecutivo_En_Programa` int NOT NULL,
  `Id` varchar(500) DEFAULT NULL,
  `Actividad` varchar(500) DEFAULT NULL,
  `Descripcion` mediumtext,
  `Ubicacion` mediumtext,
  `Fecha_Inicio` date DEFAULT NULL,
  `Fecha_Fin` date DEFAULT NULL,
  `Sub_Contratista` varchar(200) DEFAULT NULL,
  `Responsable_AIA` varchar(200) DEFAULT NULL,
  `Empresa` varchar(200) NOT NULL DEFAULT 'AIA',
  `Ejecutado` float DEFAULT NULL,
  `medir_productividad` int DEFAULT '0',
  `Unidad` varchar(10) DEFAULT NULL,
  `cantidad_ppto` int DEFAULT NULL,
  `Cantidad_Sugerida` float DEFAULT NULL,
  `Compromiso` float DEFAULT NULL,
  `Ejecutado_Real` float DEFAULT NULL,
  `P_Completado` float DEFAULT NULL,
  `PAC` int DEFAULT NULL,
  `Critica` int DEFAULT NULL,
  `Atrasada` int DEFAULT NULL,
  `Activa` varchar(3) DEFAULT NULL,
  `Prog_Sin_Restricciones_100` int DEFAULT NULL,
  `Categoria_CNP` varchar(100) DEFAULT NULL,
  `CNP` varchar(100) DEFAULT NULL,
  `Observaciones_CNP` mediumtext,
  `Categoria_CNC` varchar(100) DEFAULT NULL,
  `CNC` varchar(100) DEFAULT NULL,
  `Observaciones_CNC` mediumtext,
  `Rendimientos` varchar(500) DEFAULT NULL,
  `codigo_actividad` varchar(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `milanCampestre_programa_consolidado`
--

CREATE TABLE `milanCampestre_programa_consolidado` (
  `Consecutivo` int NOT NULL,
  `Semana` int NOT NULL,
  `Consecutivo_en_Programa` int NOT NULL,
  `Id` varchar(500) DEFAULT NULL,
  `Actividad` varchar(500) DEFAULT NULL,
  `Titulo` int DEFAULT NULL,
  `Fecha_Inicio` date DEFAULT NULL,
  `Fecha_Fin` date DEFAULT NULL,
  `Ruta_Critica` int DEFAULT NULL,
  `Ejecutado` float DEFAULT '0',
  `Estado` varchar(100) DEFAULT NULL,
  `Semanas_Inicio` int DEFAULT '0',
  `Estado_Restricciones` float NOT NULL DEFAULT '0',
  `D_y_E` varchar(9) NOT NULL DEFAULT '0',
  `Materiales` varchar(9) NOT NULL DEFAULT '0',
  `MdeO` varchar(9) NOT NULL DEFAULT '0',
  `Equipos` varchar(9) NOT NULL DEFAULT '0',
  `Predecesora` varchar(9) NOT NULL DEFAULT '0',
  `Pdto_Cons` varchar(9) NOT NULL DEFAULT '0',
  `Modelo` varchar(9) NOT NULL DEFAULT '0',
  `Sub_Contratista` varchar(100) DEFAULT NULL,
  `Responsable_AIA` varchar(100) DEFAULT NULL,
  `Observaciones` mediumtext,
  `Ult_Act_Est` date DEFAULT NULL,
  `Ult_Act_Restr` date DEFAULT NULL,
  `Activa` int NOT NULL DEFAULT '0',
  `Ejecutado_Siguiente_Semana` float DEFAULT NULL,
  `codigo_actividad` varchar(11) DEFAULT NULL,
  `medir_productividad` int DEFAULT '0',
  `cantidad_ppto` int DEFAULT NULL,
  `unidad` varchar(20) DEFAULT NULL,
  `programaAnteriorAsociar` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `milanCampestre_semanas_activas`
--

CREATE TABLE `milanCampestre_semanas_activas` (
  `Id` int NOT NULL,
  `Semana` int NOT NULL,
  `Fecha_Inicio_Sem` date NOT NULL,
  `Fecha_Fin_Sem` date NOT NULL,
  `Semanal_Confirmada` int DEFAULT '0',
  `fechaCierreCompromisos` date DEFAULT NULL,
  `fechaCreacionSemana` date DEFAULT NULL,
  `reprogramacion` int NOT NULL DEFAULT '0',
  `diferenciaEstructuraCron` int NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `milanCampestre_subcontratistas`
--

CREATE TABLE `milanCampestre_subcontratistas` (
  `Id` int NOT NULL,
  `subcontratista` varchar(200) NOT NULL,
  `correo_contacto` varchar(200) NOT NULL,
  `NIT` bigint NOT NULL,
  `alcance` varchar(200) NOT NULL,
  `tipo_proveedor` varchar(200) NOT NULL,
  `activo` int NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `milan_campestre_torre_actividades`
--

CREATE TABLE `milan_campestre_torre_actividades` (
  `Id` int NOT NULL,
  `codigo` int NOT NULL,
  `actividad` varchar(300) NOT NULL,
  `descripcionActividad` mediumtext,
  `actividadInicio` varchar(500) DEFAULT NULL,
  `nombreActividadInicio` varchar(500) DEFAULT NULL,
  `fechaInicio` date DEFAULT NULL,
  `tipoContrato` varchar(10) DEFAULT NULL,
  `semanaActualizacion` int DEFAULT NULL,
  `SI1` varchar(200) DEFAULT NULL,
  `paqueteSI1` varchar(200) DEFAULT NULL,
  `SI2` varchar(200) DEFAULT NULL,
  `paqueteSI2` varchar(200) DEFAULT NULL,
  `SI3` varchar(200) DEFAULT NULL,
  `paqueteSI3` varchar(200) DEFAULT NULL,
  `SI4` varchar(200) DEFAULT NULL,
  `paqueteSI4` varchar(200) DEFAULT NULL,
  `SI5` varchar(200) DEFAULT NULL,
  `paqueteSI5` varchar(200) DEFAULT NULL,
  `S1` varchar(200) DEFAULT NULL,
  `paqueteS1` varchar(200) DEFAULT NULL,
  `S2` varchar(200) DEFAULT NULL,
  `paqueteS2` varchar(200) DEFAULT NULL,
  `S3` varchar(200) DEFAULT NULL,
  `paqueteS3` varchar(200) DEFAULT NULL,
  `S4` varchar(200) DEFAULT NULL,
  `paqueteS4` varchar(200) DEFAULT NULL,
  `S5` varchar(200) DEFAULT NULL,
  `paqueteS5` varchar(200) DEFAULT NULL,
  `MO1` varchar(200) DEFAULT NULL,
  `paqueteMO1` varchar(200) DEFAULT NULL,
  `MO2` varchar(200) DEFAULT NULL,
  `paqueteMO2` varchar(200) DEFAULT NULL,
  `MO3` varchar(200) DEFAULT NULL,
  `paqueteMO3` varchar(200) DEFAULT NULL,
  `MO4` varchar(200) DEFAULT NULL,
  `paqueteMO4` varchar(200) DEFAULT NULL,
  `MO5` varchar(200) DEFAULT NULL,
  `paqueteMO5` varchar(200) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `milan_campestre_torre_cambios`
--

CREATE TABLE `milan_campestre_torre_cambios` (
  `id` int NOT NULL,
  `solicitanteCambio` int DEFAULT NULL,
  `detalleSolicitanteOtro` longtext,
  `fechaSolicitud` date DEFAULT NULL,
  `prioridad` int DEFAULT NULL,
  `tipoCambio` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `responsableSolucion` int DEFAULT NULL,
  `detalleResponsableSolucion` longtext,
  `justificacion` longtext,
  `descripcion` longtext,
  `incidenciaAlcance` longtext,
  `tiempoCronograma` float DEFAULT NULL,
  `tiempoCronogramaAfectado` float DEFAULT NULL,
  `incidenciaCronograma` longtext,
  `valorPresupuesto` float DEFAULT NULL,
  `costoDirecto` float DEFAULT NULL,
  `costoDirectoAIU` float DEFAULT NULL,
  `costoDirectoAIUIVA` float DEFAULT NULL,
  `valorAprobado` float DEFAULT NULL,
  `incidenciaPresupuesto` longtext,
  `incidenciaCalidad` longtext,
  `incidenciaRiesgo` longtext,
  `incidenciaRecurso` longtext,
  `fechaTentativaDefinicion` date DEFAULT NULL,
  `fechaEntregaInterventoria` date DEFAULT NULL,
  `Observaciones` longtext,
  `fechaDefinicion` date DEFAULT NULL,
  `aprobacion` int DEFAULT NULL,
  `soportes` longtext
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `milan_campestre_torre_cic`
--

CREATE TABLE `milan_campestre_torre_cic` (
  `Id` int NOT NULL,
  `Semana` int DEFAULT NULL,
  `subcontratista` varchar(200) DEFAULT NULL,
  `correo_contacto` varchar(200) DEFAULT NULL,
  `NIT` varchar(10) DEFAULT NULL,
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
  `Observaciones` mediumtext,
  `mdo_cal_1` varchar(5) DEFAULT 'NR',
  `mdo_cal_2` varchar(5) DEFAULT 'NR',
  `mdo_cal_3` varchar(5) DEFAULT 'NR',
  `mdo_adm_1` varchar(5) DEFAULT 'NR',
  `mdo_adm_2` varchar(5) DEFAULT 'NR',
  `mdo_adm_3` varchar(5) DEFAULT 'NR',
  `mdo_adm_4` varchar(5) DEFAULT 'NR',
  `mdo_adm_5` varchar(5) DEFAULT 'NR',
  `mdo_gsa_1` varchar(5) DEFAULT 'NR',
  `mdo_gsa_2` varchar(5) DEFAULT 'NR',
  `mdo_gsa_3` varchar(5) DEFAULT 'NR',
  `mdo_gsa_4` varchar(5) DEFAULT 'NR',
  `mdo_gsa_5` varchar(5) DEFAULT 'NR',
  `mdo_gsa_6` varchar(5) DEFAULT 'NR',
  `mdo_gsa_7` varchar(5) DEFAULT 'NR',
  `mdo_gsa_8` varchar(5) DEFAULT 'NR',
  `mdo_sst_1` varchar(5) DEFAULT 'NR',
  `mdo_sst_2` varchar(5) DEFAULT 'NR',
  `mdo_sst_3` varchar(5) DEFAULT 'NR',
  `mdo_sst_4` varchar(5) DEFAULT 'NR',
  `mdo_sst_5` varchar(5) DEFAULT 'NR',
  `mdo_sst_6` varchar(5) DEFAULT 'NR',
  `mdo_sst_7` varchar(5) DEFAULT 'NR',
  `mdo_sst_8` varchar(5) DEFAULT 'NR',
  `mdo_sst_9` varchar(5) DEFAULT 'NR',
  `mdo_sst_10` varchar(5) DEFAULT 'NR',
  `si_cal_1` varchar(5) DEFAULT 'NR',
  `si_cal_2` varchar(5) DEFAULT 'NR',
  `si_cal_3` varchar(5) DEFAULT 'NR',
  `si_adm_1` varchar(5) DEFAULT 'NR',
  `si_adm_2` varchar(5) DEFAULT 'NR',
  `si_adm_3` varchar(5) DEFAULT 'NR',
  `si_adm_4` varchar(5) DEFAULT 'NR',
  `si_adm_5` varchar(5) DEFAULT 'NR',
  `si_adm_6` varchar(5) DEFAULT 'NR',
  `si_gsa_1` varchar(5) DEFAULT 'NR',
  `si_gsa_2` varchar(5) DEFAULT 'NR',
  `si_gsa_3` varchar(5) DEFAULT 'NR',
  `si_gsa_4` varchar(5) DEFAULT 'NR',
  `si_gsa_5` varchar(5) DEFAULT 'NR',
  `si_gsa_6` varchar(5) DEFAULT 'NR',
  `si_gsa_7` varchar(5) DEFAULT 'NR',
  `si_gsa_8` varchar(5) DEFAULT 'NR',
  `si_gsa_9` varchar(5) DEFAULT 'NR',
  `si_gsa_10` varchar(5) DEFAULT 'NR',
  `si_gsa_11` varchar(5) DEFAULT 'NR',
  `si_gsa_12` varchar(5) DEFAULT 'NR',
  `si_gsa_13` varchar(5) DEFAULT 'NR',
  `si_gsa_14` varchar(5) DEFAULT 'NR',
  `si_sst_1` varchar(5) DEFAULT 'NR',
  `si_sst_2` varchar(5) DEFAULT 'NR',
  `si_sst_3` varchar(5) DEFAULT 'NR',
  `si_sst_4` varchar(5) DEFAULT 'NR',
  `si_sst_5` varchar(5) DEFAULT 'NR',
  `si_sst_6` varchar(5) DEFAULT 'NR',
  `si_sst_7` varchar(5) DEFAULT 'NR',
  `si_sst_8` varchar(5) DEFAULT 'NR',
  `si_sst_9` varchar(5) DEFAULT 'NR',
  `si_sst_10` varchar(5) DEFAULT 'NR'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `milan_campestre_torre_pdc`
--

CREATE TABLE `milan_campestre_torre_pdc` (
  `consecutivo` int NOT NULL,
  `semana` int NOT NULL,
  `titulo` int NOT NULL,
  `tipoPaquete` varchar(200) NOT NULL,
  `paqueteContratacion` varchar(200) DEFAULT NULL,
  `contratos` varchar(200) DEFAULT NULL,
  `numeroSubcontratos` int DEFAULT '1',
  `subcontratoPaquete` int NOT NULL DEFAULT '1',
  `estado` varchar(200) DEFAULT NULL,
  `fechaElaboracionPliegos` date DEFAULT NULL,
  `diasElaboracionPliegos` int DEFAULT NULL,
  `fechaRealElaboracionPliegos` date DEFAULT NULL,
  `fechaIngresoLicify` date DEFAULT NULL,
  `diasIngresoLicify` int DEFAULT NULL,
  `fechaRealIngresoLicify` date DEFAULT NULL,
  `fechaEntregaPliegos` date DEFAULT NULL,
  `diasEntregaPliegos` int DEFAULT NULL,
  `fechaRealEntregaPliegos` date DEFAULT NULL,
  `fechaReciboPropuestas` date DEFAULT NULL,
  `diasReciboPropuestas` int DEFAULT NULL,
  `fechaRealReciboPropuestas` date DEFAULT NULL,
  `fechaCuadrosComparativos` date DEFAULT NULL,
  `diasCuadrosComparativos` int DEFAULT NULL,
  `fechaRealCuadrosComparativos` date DEFAULT NULL,
  `fechaLegalizacionContrato` date DEFAULT NULL,
  `diasLegalizacionContrato` int DEFAULT NULL,
  `fechaRealLegalizacionContrato` date DEFAULT NULL,
  `fechaFabricacion` date DEFAULT NULL,
  `diasFabricacion` int DEFAULT NULL,
  `fechaRealFabricacion` date DEFAULT NULL,
  `fechaInsumosObra` date DEFAULT NULL,
  `diasInsumosObra` int DEFAULT NULL,
  `fechaRealInsumosObra` date DEFAULT NULL,
  `fechaInicio` date DEFAULT NULL,
  `fechaInicioProyectada` date DEFAULT NULL,
  `fechaRealInicio` date DEFAULT NULL,
  `idProveedorAdjudicado` int DEFAULT NULL,
  `numeroContrato` varchar(50) DEFAULT NULL,
  `aplicaPolizas` int NOT NULL DEFAULT '1',
  `fechaVencimientoPolizas` date DEFAULT NULL,
  `valorPresupuesto` float DEFAULT NULL,
  `valorPrimeraNegociacion` float DEFAULT NULL,
  `valorAdjudicado` float DEFAULT NULL,
  `valorAnticipo` float DEFAULT NULL,
  `valorReclamado` float DEFAULT NULL,
  `valorDevoluciones` float DEFAULT NULL,
  `observacionesContrato` mediumtext
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `milan_campestre_torre_profesionales`
--

CREATE TABLE `milan_campestre_torre_profesionales` (
  `id` int NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `cargo` varchar(100) NOT NULL,
  `activo` int NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `milan_campestre_torre_programa`
--

CREATE TABLE `milan_campestre_torre_programa` (
  `Consecutivo` int NOT NULL,
  `Id` varchar(500) DEFAULT NULL,
  `Actividad` varchar(500) DEFAULT NULL,
  `Titulo` int DEFAULT NULL,
  `Fecha_Inicio` date DEFAULT NULL,
  `Fecha_Fin` date DEFAULT NULL,
  `Ruta_Critica` int DEFAULT NULL,
  `Ejecutado` float DEFAULT '0',
  `Estado` varchar(50) DEFAULT NULL,
  `Semanas_Inicio` int DEFAULT '0',
  `Estado_Restricciones` float DEFAULT '0',
  `D_y_E` float DEFAULT '0',
  `Materiales` float DEFAULT '0',
  `MdeO` float DEFAULT '0',
  `Equipos` float DEFAULT '0',
  `Predecesora` float DEFAULT '0',
  `Pdto_Cons` float DEFAULT '0',
  `Modelo` varchar(9) DEFAULT '0',
  `Responsable_AIA` varchar(100) DEFAULT NULL,
  `Observaciones` mediumtext,
  `Ult_Act_Est` date DEFAULT NULL,
  `Ult_Act_Restr` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `milan_campestre_torre_programacion_semanal`
--

CREATE TABLE `milan_campestre_torre_programacion_semanal` (
  `Consecutivo` int NOT NULL,
  `Semana` int DEFAULT NULL,
  `Consecutivo_En_Programa` int NOT NULL,
  `Id` varchar(500) DEFAULT NULL,
  `Actividad` varchar(500) DEFAULT NULL,
  `Descripcion` mediumtext,
  `Ubicacion` mediumtext,
  `Fecha_Inicio` date DEFAULT NULL,
  `Fecha_Fin` date DEFAULT NULL,
  `Sub_Contratista` varchar(200) DEFAULT NULL,
  `Responsable_AIA` varchar(200) DEFAULT NULL,
  `Empresa` varchar(200) NOT NULL DEFAULT 'AIA',
  `Ejecutado` float DEFAULT NULL,
  `medir_productividad` int DEFAULT '0',
  `Unidad` varchar(10) DEFAULT NULL,
  `cantidad_ppto` int DEFAULT NULL,
  `Cantidad_Sugerida` float DEFAULT NULL,
  `Compromiso` float DEFAULT NULL,
  `Ejecutado_Real` float DEFAULT NULL,
  `P_Completado` float DEFAULT NULL,
  `PAC` int DEFAULT NULL,
  `Critica` int DEFAULT NULL,
  `Atrasada` int DEFAULT NULL,
  `Activa` varchar(3) DEFAULT NULL,
  `Prog_Sin_Restricciones_100` int DEFAULT NULL,
  `Categoria_CNP` varchar(100) DEFAULT NULL,
  `CNP` varchar(100) DEFAULT NULL,
  `Observaciones_CNP` mediumtext,
  `Categoria_CNC` varchar(100) DEFAULT NULL,
  `CNC` varchar(100) DEFAULT NULL,
  `Observaciones_CNC` mediumtext,
  `Rendimientos` varchar(500) DEFAULT NULL,
  `codigo_actividad` varchar(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `milan_campestre_torre_programa_consolidado`
--

CREATE TABLE `milan_campestre_torre_programa_consolidado` (
  `Consecutivo` int NOT NULL,
  `Semana` int NOT NULL,
  `Consecutivo_en_Programa` int NOT NULL,
  `Id` varchar(500) DEFAULT NULL,
  `Actividad` varchar(500) DEFAULT NULL,
  `Titulo` int DEFAULT NULL,
  `Fecha_Inicio` date DEFAULT NULL,
  `Fecha_Fin` date DEFAULT NULL,
  `Ruta_Critica` int DEFAULT NULL,
  `Ejecutado` float DEFAULT '0',
  `Estado` varchar(100) DEFAULT NULL,
  `Semanas_Inicio` int DEFAULT '0',
  `Estado_Restricciones` float NOT NULL DEFAULT '0',
  `D_y_E` varchar(9) NOT NULL DEFAULT '0',
  `Materiales` varchar(9) NOT NULL DEFAULT '0',
  `MdeO` varchar(9) NOT NULL DEFAULT '0',
  `Equipos` varchar(9) NOT NULL DEFAULT '0',
  `Predecesora` varchar(9) NOT NULL DEFAULT '0',
  `Pdto_Cons` varchar(9) NOT NULL DEFAULT '0',
  `Modelo` varchar(9) NOT NULL DEFAULT '0',
  `Sub_Contratista` varchar(100) DEFAULT NULL,
  `Responsable_AIA` varchar(100) DEFAULT NULL,
  `Observaciones` mediumtext,
  `Ult_Act_Est` date DEFAULT NULL,
  `Ult_Act_Restr` date DEFAULT NULL,
  `Activa` int NOT NULL DEFAULT '0',
  `Ejecutado_Siguiente_Semana` float DEFAULT NULL,
  `codigo_actividad` varchar(11) DEFAULT NULL,
  `medir_productividad` int DEFAULT '0',
  `cantidad_ppto` int DEFAULT NULL,
  `unidad` varchar(20) DEFAULT NULL,
  `programaAnteriorAsociar` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `milan_campestre_torre_semanas_activas`
--

CREATE TABLE `milan_campestre_torre_semanas_activas` (
  `Id` int NOT NULL,
  `Semana` int NOT NULL,
  `Fecha_Inicio_Sem` date NOT NULL,
  `Fecha_Fin_Sem` date NOT NULL,
  `Semanal_Confirmada` int DEFAULT '0',
  `fechaCierreCompromisos` date DEFAULT NULL,
  `fechaCreacionSemana` date DEFAULT NULL,
  `reprogramacion` int NOT NULL DEFAULT '0',
  `diferenciaEstructuraCron` int NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `milan_campestre_torre_subcontratistas`
--

CREATE TABLE `milan_campestre_torre_subcontratistas` (
  `Id` int NOT NULL,
  `subcontratista` varchar(200) NOT NULL,
  `correo_contacto` varchar(200) NOT NULL,
  `NIT` bigint NOT NULL,
  `alcance` varchar(200) NOT NULL,
  `tipo_proveedor` varchar(200) NOT NULL,
  `activo` int NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `notification_types`
--

CREATE TABLE `notification_types` (
  `notification_code` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `optimizacionJMC_actividades`
--

CREATE TABLE `optimizacionJMC_actividades` (
  `Id` int NOT NULL,
  `codigo` int NOT NULL,
  `actividad` varchar(300) NOT NULL,
  `descripcionActividad` mediumtext,
  `actividadInicio` varchar(500) DEFAULT NULL,
  `nombreActividadInicio` varchar(500) DEFAULT NULL,
  `fechaInicio` date DEFAULT NULL,
  `tipoContrato` varchar(10) DEFAULT NULL,
  `semanaActualizacion` int DEFAULT NULL,
  `SI1` varchar(200) DEFAULT NULL,
  `paqueteSI1` varchar(200) DEFAULT NULL,
  `SI2` varchar(200) DEFAULT NULL,
  `paqueteSI2` varchar(200) DEFAULT NULL,
  `SI3` varchar(200) DEFAULT NULL,
  `paqueteSI3` varchar(200) DEFAULT NULL,
  `SI4` varchar(200) DEFAULT NULL,
  `paqueteSI4` varchar(200) DEFAULT NULL,
  `SI5` varchar(200) DEFAULT NULL,
  `paqueteSI5` varchar(200) DEFAULT NULL,
  `S1` varchar(200) DEFAULT NULL,
  `paqueteS1` varchar(200) DEFAULT NULL,
  `S2` varchar(200) DEFAULT NULL,
  `paqueteS2` varchar(200) DEFAULT NULL,
  `S3` varchar(200) DEFAULT NULL,
  `paqueteS3` varchar(200) DEFAULT NULL,
  `S4` varchar(200) DEFAULT NULL,
  `paqueteS4` varchar(200) DEFAULT NULL,
  `S5` varchar(200) DEFAULT NULL,
  `paqueteS5` varchar(200) DEFAULT NULL,
  `MO1` varchar(200) DEFAULT NULL,
  `paqueteMO1` varchar(200) DEFAULT NULL,
  `MO2` varchar(200) DEFAULT NULL,
  `paqueteMO2` varchar(200) DEFAULT NULL,
  `MO3` varchar(200) DEFAULT NULL,
  `paqueteMO3` varchar(200) DEFAULT NULL,
  `MO4` varchar(200) DEFAULT NULL,
  `paqueteMO4` varchar(200) DEFAULT NULL,
  `MO5` varchar(200) DEFAULT NULL,
  `paqueteMO5` varchar(200) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `optimizacionJMC_cambios`
--

CREATE TABLE `optimizacionJMC_cambios` (
  `id` int NOT NULL,
  `solicitanteCambio` int DEFAULT NULL,
  `detalleSolicitanteOtro` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `fechaSolicitud` date DEFAULT NULL,
  `prioridad` int DEFAULT NULL,
  `tipoCambio` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `responsableSolucion` int DEFAULT NULL,
  `detalleResponsableSolucion` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `justificacion` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `descripcion` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `incidenciaAlcance` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `tiempoCronograma` float DEFAULT NULL,
  `tiempoCronogramaAfectado` float DEFAULT NULL,
  `incidenciaCronograma` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `valorPresupuesto` float DEFAULT NULL,
  `costoDirecto` float DEFAULT NULL,
  `costoDirectoAIU` float DEFAULT NULL,
  `costoDirectoAIUIVA` float DEFAULT NULL,
  `valorAprobado` float DEFAULT NULL,
  `incidenciaPresupuesto` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `incidenciaCalidad` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `incidenciaRiesgo` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `incidenciaRecurso` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `fechaTentativaDefinicion` date DEFAULT NULL,
  `fechaEntregaInterventoria` date DEFAULT NULL,
  `Observaciones` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `fechaDefinicion` date DEFAULT NULL,
  `aprobacion` int DEFAULT NULL,
  `soportes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `optimizacionJMC_cic`
--

CREATE TABLE `optimizacionJMC_cic` (
  `Id` int NOT NULL,
  `Semana` int DEFAULT NULL,
  `subcontratista` varchar(200) DEFAULT NULL,
  `correo_contacto` varchar(200) DEFAULT NULL,
  `NIT` varchar(10) DEFAULT NULL,
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
  `Observaciones` mediumtext,
  `mdo_cal_1` varchar(5) DEFAULT 'NR',
  `mdo_cal_2` varchar(5) DEFAULT 'NR',
  `mdo_cal_3` varchar(5) DEFAULT 'NR',
  `mdo_adm_1` varchar(5) DEFAULT 'NR',
  `mdo_adm_2` varchar(5) DEFAULT 'NR',
  `mdo_adm_3` varchar(5) DEFAULT 'NR',
  `mdo_adm_4` varchar(5) DEFAULT 'NR',
  `mdo_adm_5` varchar(5) DEFAULT 'NR',
  `mdo_gsa_1` varchar(5) DEFAULT 'NR',
  `mdo_gsa_2` varchar(5) DEFAULT 'NR',
  `mdo_gsa_3` varchar(5) DEFAULT 'NR',
  `mdo_gsa_4` varchar(5) DEFAULT 'NR',
  `mdo_gsa_5` varchar(5) DEFAULT 'NR',
  `mdo_gsa_6` varchar(5) DEFAULT 'NR',
  `mdo_gsa_7` varchar(5) DEFAULT 'NR',
  `mdo_gsa_8` varchar(5) DEFAULT 'NR',
  `mdo_sst_1` varchar(5) DEFAULT 'NR',
  `mdo_sst_2` varchar(5) DEFAULT 'NR',
  `mdo_sst_3` varchar(5) DEFAULT 'NR',
  `mdo_sst_4` varchar(5) DEFAULT 'NR',
  `mdo_sst_5` varchar(5) DEFAULT 'NR',
  `mdo_sst_6` varchar(5) DEFAULT 'NR',
  `mdo_sst_7` varchar(5) DEFAULT 'NR',
  `mdo_sst_8` varchar(5) DEFAULT 'NR',
  `mdo_sst_9` varchar(5) DEFAULT 'NR',
  `mdo_sst_10` varchar(5) DEFAULT 'NR',
  `si_cal_1` varchar(5) DEFAULT 'NR',
  `si_cal_2` varchar(5) DEFAULT 'NR',
  `si_cal_3` varchar(5) DEFAULT 'NR',
  `si_adm_1` varchar(5) DEFAULT 'NR',
  `si_adm_2` varchar(5) DEFAULT 'NR',
  `si_adm_3` varchar(5) DEFAULT 'NR',
  `si_adm_4` varchar(5) DEFAULT 'NR',
  `si_adm_5` varchar(5) DEFAULT 'NR',
  `si_adm_6` varchar(5) DEFAULT 'NR',
  `si_gsa_1` varchar(5) DEFAULT 'NR',
  `si_gsa_2` varchar(5) DEFAULT 'NR',
  `si_gsa_3` varchar(5) DEFAULT 'NR',
  `si_gsa_4` varchar(5) DEFAULT 'NR',
  `si_gsa_5` varchar(5) DEFAULT 'NR',
  `si_gsa_6` varchar(5) DEFAULT 'NR',
  `si_gsa_7` varchar(5) DEFAULT 'NR',
  `si_gsa_8` varchar(5) DEFAULT 'NR',
  `si_gsa_9` varchar(5) DEFAULT 'NR',
  `si_gsa_10` varchar(5) DEFAULT 'NR',
  `si_gsa_11` varchar(5) DEFAULT 'NR',
  `si_gsa_12` varchar(5) DEFAULT 'NR',
  `si_gsa_13` varchar(5) DEFAULT 'NR',
  `si_gsa_14` varchar(5) DEFAULT 'NR',
  `si_sst_1` varchar(5) DEFAULT 'NR',
  `si_sst_2` varchar(5) DEFAULT 'NR',
  `si_sst_3` varchar(5) DEFAULT 'NR',
  `si_sst_4` varchar(5) DEFAULT 'NR',
  `si_sst_5` varchar(5) DEFAULT 'NR',
  `si_sst_6` varchar(5) DEFAULT 'NR',
  `si_sst_7` varchar(5) DEFAULT 'NR',
  `si_sst_8` varchar(5) DEFAULT 'NR',
  `si_sst_9` varchar(5) DEFAULT 'NR',
  `si_sst_10` varchar(5) DEFAULT 'NR'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `optimizacionJMC_pdc`
--

CREATE TABLE `optimizacionJMC_pdc` (
  `consecutivo` int NOT NULL,
  `semana` int NOT NULL,
  `titulo` int NOT NULL,
  `tipoPaquete` varchar(200) NOT NULL,
  `paqueteContratacion` varchar(200) DEFAULT NULL,
  `contratos` varchar(200) DEFAULT NULL,
  `numeroSubcontratos` int DEFAULT '1',
  `subcontratoPaquete` int NOT NULL DEFAULT '1',
  `estado` varchar(200) DEFAULT NULL,
  `fechaElaboracionPliegos` date DEFAULT NULL,
  `diasElaboracionPliegos` int DEFAULT NULL,
  `fechaRealElaboracionPliegos` date DEFAULT NULL,
  `fechaIngresoLicify` date DEFAULT NULL,
  `diasIngresoLicify` int DEFAULT NULL,
  `fechaRealIngresoLicify` date DEFAULT NULL,
  `fechaEntregaPliegos` date DEFAULT NULL,
  `diasEntregaPliegos` int DEFAULT NULL,
  `fechaRealEntregaPliegos` date DEFAULT NULL,
  `fechaReciboPropuestas` date DEFAULT NULL,
  `diasReciboPropuestas` int DEFAULT NULL,
  `fechaRealReciboPropuestas` date DEFAULT NULL,
  `fechaCuadrosComparativos` date DEFAULT NULL,
  `diasCuadrosComparativos` int DEFAULT NULL,
  `fechaRealCuadrosComparativos` date DEFAULT NULL,
  `fechaLegalizacionContrato` date DEFAULT NULL,
  `diasLegalizacionContrato` int DEFAULT NULL,
  `fechaRealLegalizacionContrato` date DEFAULT NULL,
  `fechaFabricacion` date DEFAULT NULL,
  `diasFabricacion` int DEFAULT NULL,
  `fechaRealFabricacion` date DEFAULT NULL,
  `fechaInsumosObra` date DEFAULT NULL,
  `diasInsumosObra` int DEFAULT NULL,
  `fechaRealInsumosObra` date DEFAULT NULL,
  `fechaInicio` date DEFAULT NULL,
  `fechaInicioProyectada` date DEFAULT NULL,
  `fechaRealInicio` date DEFAULT NULL,
  `idProveedorAdjudicado` int DEFAULT NULL,
  `numeroContrato` varchar(50) DEFAULT NULL,
  `aplicaPolizas` int NOT NULL DEFAULT '1',
  `fechaVencimientoPolizas` date DEFAULT NULL,
  `valorPresupuesto` float DEFAULT NULL,
  `valorPrimeraNegociacion` float DEFAULT NULL,
  `valorAdjudicado` float DEFAULT NULL,
  `valorAnticipo` float DEFAULT NULL,
  `valorReclamado` float DEFAULT NULL,
  `valorDevoluciones` float DEFAULT NULL,
  `observacionesContrato` mediumtext
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `optimizacionJMC_pi_shared_constraints`
--

CREATE TABLE `optimizacionJMC_pi_shared_constraints` (
  `Id` bigint UNSIGNED NOT NULL,
  `Semana` int NOT NULL,
  `Restriccion` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `ValorObjetivo` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `Nota` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `CreadoPor` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `CreadoEn` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ActualizadoEn` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `optimizacionJMC_pi_shared_constraint_links`
--

CREATE TABLE `optimizacionJMC_pi_shared_constraint_links` (
  `Id` bigint UNSIGNED NOT NULL,
  `SharedConstraintId` bigint UNSIGNED NOT NULL,
  `Semana` int NOT NULL,
  `ConsecutivoEnPrograma` bigint NOT NULL,
  `ValorAplicado` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `OverrideLocal` tinyint(1) NOT NULL DEFAULT '0',
  `AplicadoEn` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `optimizacionJMC_profesionales`
--

CREATE TABLE `optimizacionJMC_profesionales` (
  `id` int NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `cargo` varchar(100) NOT NULL,
  `activo` int NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `optimizacionJMC_programa`
--

CREATE TABLE `optimizacionJMC_programa` (
  `Consecutivo` int NOT NULL,
  `Id` varchar(500) DEFAULT NULL,
  `Actividad` varchar(500) DEFAULT NULL,
  `Titulo` int DEFAULT NULL,
  `Fecha_Inicio` date DEFAULT NULL,
  `Fecha_Fin` date DEFAULT NULL,
  `Ruta_Critica` int DEFAULT NULL,
  `Ejecutado` float DEFAULT '0',
  `Estado` varchar(50) DEFAULT NULL,
  `Semanas_Inicio` int DEFAULT '0',
  `Estado_Restricciones` float DEFAULT '0',
  `D_y_E` float DEFAULT '0',
  `Materiales` float DEFAULT '0',
  `MdeO` float DEFAULT '0',
  `Equipos` float DEFAULT '0',
  `Predecesora` float DEFAULT '0',
  `Pdto_Cons` float DEFAULT '0',
  `Modelo` varchar(9) DEFAULT '0',
  `Responsable_AIA` varchar(100) DEFAULT NULL,
  `Observaciones` mediumtext,
  `Ult_Act_Est` date DEFAULT NULL,
  `Ult_Act_Restr` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `optimizacionJMC_programacion_semanal`
--

CREATE TABLE `optimizacionJMC_programacion_semanal` (
  `Consecutivo` int NOT NULL,
  `Semana` int DEFAULT NULL,
  `Consecutivo_En_Programa` int NOT NULL,
  `Id` varchar(500) DEFAULT NULL,
  `Actividad` varchar(500) DEFAULT NULL,
  `Descripcion` mediumtext,
  `Ubicacion` mediumtext,
  `Fecha_Inicio` date DEFAULT NULL,
  `Fecha_Fin` date DEFAULT NULL,
  `Sub_Contratista` varchar(200) DEFAULT NULL,
  `Responsable_AIA` varchar(200) DEFAULT NULL,
  `Empresa` varchar(200) NOT NULL DEFAULT 'AIA',
  `Ejecutado` float DEFAULT NULL,
  `medir_productividad` int DEFAULT '0',
  `Unidad` varchar(10) DEFAULT NULL,
  `cantidad_ppto` int DEFAULT NULL,
  `Cantidad_Sugerida` float DEFAULT NULL,
  `Compromiso` float DEFAULT NULL,
  `Ejecutado_Real` float DEFAULT NULL,
  `P_Completado` float DEFAULT NULL,
  `PAC` int DEFAULT NULL,
  `Critica` int DEFAULT NULL,
  `Atrasada` int DEFAULT NULL,
  `Activa` varchar(3) DEFAULT NULL,
  `Prog_Sin_Restricciones_100` int DEFAULT NULL,
  `Categoria_CNP` varchar(100) DEFAULT NULL,
  `CNP` varchar(100) DEFAULT NULL,
  `Observaciones_CNP` mediumtext,
  `Categoria_CNC` varchar(100) DEFAULT NULL,
  `CNC` varchar(100) DEFAULT NULL,
  `Observaciones_CNC` mediumtext,
  `Rendimientos` varchar(500) DEFAULT NULL,
  `codigo_actividad` varchar(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `optimizacionJMC_programa_consolidado`
--

CREATE TABLE `optimizacionJMC_programa_consolidado` (
  `Consecutivo` int NOT NULL,
  `Semana` int NOT NULL,
  `Consecutivo_en_Programa` int NOT NULL,
  `Id` varchar(500) DEFAULT NULL,
  `Actividad` varchar(500) DEFAULT NULL,
  `Titulo` int DEFAULT NULL,
  `Fecha_Inicio` date DEFAULT NULL,
  `Fecha_Fin` date DEFAULT NULL,
  `Ruta_Critica` int DEFAULT NULL,
  `Ejecutado` float DEFAULT '0',
  `Estado` varchar(100) DEFAULT NULL,
  `Semanas_Inicio` int DEFAULT '0',
  `Estado_Restricciones` float NOT NULL DEFAULT '0',
  `D_y_E` varchar(9) NOT NULL DEFAULT '0',
  `Materiales` varchar(9) NOT NULL DEFAULT '0',
  `MdeO` varchar(9) NOT NULL DEFAULT '0',
  `Equipos` varchar(9) NOT NULL DEFAULT '0',
  `Predecesora` varchar(9) NOT NULL DEFAULT '0',
  `Pdto_Cons` varchar(9) NOT NULL DEFAULT '0',
  `Modelo` varchar(9) NOT NULL DEFAULT '0',
  `Sub_Contratista` varchar(100) DEFAULT NULL,
  `Responsable_AIA` varchar(100) DEFAULT NULL,
  `Observaciones` mediumtext,
  `Ult_Act_Est` date DEFAULT NULL,
  `Ult_Act_Restr` date DEFAULT NULL,
  `Activa` int NOT NULL DEFAULT '0',
  `Ejecutado_Siguiente_Semana` float DEFAULT NULL,
  `codigo_actividad` varchar(11) DEFAULT NULL,
  `medir_productividad` int DEFAULT '0',
  `cantidad_ppto` int DEFAULT NULL,
  `unidad` varchar(20) DEFAULT NULL,
  `programaAnteriorAsociar` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `optimizacionJMC_semanas_activas`
--

CREATE TABLE `optimizacionJMC_semanas_activas` (
  `Id` int NOT NULL,
  `Semana` int NOT NULL,
  `Fecha_Inicio_Sem` date NOT NULL,
  `Fecha_Fin_Sem` date NOT NULL,
  `Semanal_Confirmada` int DEFAULT '0',
  `fechaCierreCompromisos` date DEFAULT NULL,
  `fechaCreacionSemana` date DEFAULT NULL,
  `reprogramacion` int NOT NULL DEFAULT '0',
  `diferenciaEstructuraCron` int NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `optimizacionJMC_subcontratistas`
--

CREATE TABLE `optimizacionJMC_subcontratistas` (
  `Id` int NOT NULL,
  `subcontratista` varchar(200) NOT NULL,
  `correo_contacto` varchar(200) NOT NULL,
  `NIT` bigint NOT NULL,
  `alcance` varchar(200) NOT NULL,
  `tipo_proveedor` varchar(200) NOT NULL,
  `activo` int NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `password_history`
--

CREATE TABLE `password_history` (
  `id` int UNSIGNED NOT NULL,
  `user_id` int NOT NULL,
  `password_hash` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` int NOT NULL,
  `scope` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `token_hash` char(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `requested_ip` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `project_members`
--

CREATE TABLE `project_members` (
  `id` int NOT NULL,
  `project_id` int NOT NULL,
  `user_id` int NOT NULL,
  `role` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'U',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `prueba_actividades`
--

CREATE TABLE `prueba_actividades` (
  `Id` int NOT NULL,
  `codigo` int NOT NULL,
  `actividad` varchar(300) NOT NULL,
  `descripcionActividad` mediumtext,
  `actividadInicio` varchar(500) DEFAULT NULL,
  `nombreActividadInicio` varchar(500) DEFAULT NULL,
  `fechaInicio` date DEFAULT NULL,
  `tipoContrato` varchar(10) DEFAULT NULL,
  `semanaActualizacion` int DEFAULT NULL,
  `SI1` varchar(200) DEFAULT NULL,
  `paqueteSI1` varchar(200) DEFAULT NULL,
  `SI2` varchar(200) DEFAULT NULL,
  `paqueteSI2` varchar(200) DEFAULT NULL,
  `SI3` varchar(200) DEFAULT NULL,
  `paqueteSI3` varchar(200) DEFAULT NULL,
  `SI4` varchar(200) DEFAULT NULL,
  `paqueteSI4` varchar(200) DEFAULT NULL,
  `SI5` varchar(200) DEFAULT NULL,
  `paqueteSI5` varchar(200) DEFAULT NULL,
  `S1` varchar(200) DEFAULT NULL,
  `paqueteS1` varchar(200) DEFAULT NULL,
  `S2` varchar(200) DEFAULT NULL,
  `paqueteS2` varchar(200) DEFAULT NULL,
  `S3` varchar(200) DEFAULT NULL,
  `paqueteS3` varchar(200) DEFAULT NULL,
  `S4` varchar(200) DEFAULT NULL,
  `paqueteS4` varchar(200) DEFAULT NULL,
  `S5` varchar(200) DEFAULT NULL,
  `paqueteS5` varchar(200) DEFAULT NULL,
  `MO1` varchar(200) DEFAULT NULL,
  `paqueteMO1` varchar(200) DEFAULT NULL,
  `MO2` varchar(200) DEFAULT NULL,
  `paqueteMO2` varchar(200) DEFAULT NULL,
  `MO3` varchar(200) DEFAULT NULL,
  `paqueteMO3` varchar(200) DEFAULT NULL,
  `MO4` varchar(200) DEFAULT NULL,
  `paqueteMO4` varchar(200) DEFAULT NULL,
  `MO5` varchar(200) DEFAULT NULL,
  `paqueteMO5` varchar(200) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `prueba_cambios`
--

CREATE TABLE `prueba_cambios` (
  `id` int NOT NULL,
  `solicitanteCambio` int DEFAULT NULL,
  `detalleSolicitanteOtro` longtext,
  `fechaSolicitud` date DEFAULT NULL,
  `prioridad` int DEFAULT NULL,
  `tipoCambio` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `responsableSolucion` int DEFAULT NULL,
  `detalleResponsableSolucion` longtext,
  `justificacion` longtext,
  `descripcion` longtext,
  `incidenciaAlcance` longtext,
  `tiempoCronograma` float DEFAULT NULL,
  `tiempoCronogramaAfectado` float DEFAULT NULL,
  `incidenciaCronograma` longtext,
  `valorPresupuesto` float DEFAULT NULL,
  `costoDirecto` float DEFAULT NULL,
  `costoDirectoAIU` float DEFAULT NULL,
  `costoDirectoAIUIVA` float DEFAULT NULL,
  `valorAprobado` float DEFAULT NULL,
  `incidenciaPresupuesto` longtext,
  `incidenciaCalidad` longtext,
  `incidenciaRiesgo` longtext,
  `incidenciaRecurso` longtext,
  `fechaTentativaDefinicion` date DEFAULT NULL,
  `fechaEntregaInterventoria` date DEFAULT NULL,
  `Observaciones` longtext,
  `fechaDefinicion` date DEFAULT NULL,
  `aprobacion` int DEFAULT NULL,
  `soportes` longtext
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `prueba_cic`
--

CREATE TABLE `prueba_cic` (
  `Id` int NOT NULL,
  `Semana` int DEFAULT NULL,
  `subcontratista` varchar(200) DEFAULT NULL,
  `correo_contacto` varchar(200) DEFAULT NULL,
  `NIT` varchar(10) DEFAULT NULL,
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
  `Observaciones` mediumtext,
  `mdo_cal_1` varchar(5) DEFAULT 'NR',
  `mdo_cal_2` varchar(5) DEFAULT 'NR',
  `mdo_cal_3` varchar(5) DEFAULT 'NR',
  `mdo_adm_1` varchar(5) DEFAULT 'NR',
  `mdo_adm_2` varchar(5) DEFAULT 'NR',
  `mdo_adm_3` varchar(5) DEFAULT 'NR',
  `mdo_adm_4` varchar(5) DEFAULT 'NR',
  `mdo_adm_5` varchar(5) DEFAULT 'NR',
  `mdo_gsa_1` varchar(5) DEFAULT 'NR',
  `mdo_gsa_2` varchar(5) DEFAULT 'NR',
  `mdo_gsa_3` varchar(5) DEFAULT 'NR',
  `mdo_gsa_4` varchar(5) DEFAULT 'NR',
  `mdo_gsa_5` varchar(5) DEFAULT 'NR',
  `mdo_gsa_6` varchar(5) DEFAULT 'NR',
  `mdo_gsa_7` varchar(5) DEFAULT 'NR',
  `mdo_gsa_8` varchar(5) DEFAULT 'NR',
  `mdo_sst_1` varchar(5) DEFAULT 'NR',
  `mdo_sst_2` varchar(5) DEFAULT 'NR',
  `mdo_sst_3` varchar(5) DEFAULT 'NR',
  `mdo_sst_4` varchar(5) DEFAULT 'NR',
  `mdo_sst_5` varchar(5) DEFAULT 'NR',
  `mdo_sst_6` varchar(5) DEFAULT 'NR',
  `mdo_sst_7` varchar(5) DEFAULT 'NR',
  `mdo_sst_8` varchar(5) DEFAULT 'NR',
  `mdo_sst_9` varchar(5) DEFAULT 'NR',
  `mdo_sst_10` varchar(5) DEFAULT 'NR',
  `si_cal_1` varchar(5) DEFAULT 'NR',
  `si_cal_2` varchar(5) DEFAULT 'NR',
  `si_cal_3` varchar(5) DEFAULT 'NR',
  `si_adm_1` varchar(5) DEFAULT 'NR',
  `si_adm_2` varchar(5) DEFAULT 'NR',
  `si_adm_3` varchar(5) DEFAULT 'NR',
  `si_adm_4` varchar(5) DEFAULT 'NR',
  `si_adm_5` varchar(5) DEFAULT 'NR',
  `si_adm_6` varchar(5) DEFAULT 'NR',
  `si_gsa_1` varchar(5) DEFAULT 'NR',
  `si_gsa_2` varchar(5) DEFAULT 'NR',
  `si_gsa_3` varchar(5) DEFAULT 'NR',
  `si_gsa_4` varchar(5) DEFAULT 'NR',
  `si_gsa_5` varchar(5) DEFAULT 'NR',
  `si_gsa_6` varchar(5) DEFAULT 'NR',
  `si_gsa_7` varchar(5) DEFAULT 'NR',
  `si_gsa_8` varchar(5) DEFAULT 'NR',
  `si_gsa_9` varchar(5) DEFAULT 'NR',
  `si_gsa_10` varchar(5) DEFAULT 'NR',
  `si_gsa_11` varchar(5) DEFAULT 'NR',
  `si_gsa_12` varchar(5) DEFAULT 'NR',
  `si_gsa_13` varchar(5) DEFAULT 'NR',
  `si_gsa_14` varchar(5) DEFAULT 'NR',
  `si_sst_1` varchar(5) DEFAULT 'NR',
  `si_sst_2` varchar(5) DEFAULT 'NR',
  `si_sst_3` varchar(5) DEFAULT 'NR',
  `si_sst_4` varchar(5) DEFAULT 'NR',
  `si_sst_5` varchar(5) DEFAULT 'NR',
  `si_sst_6` varchar(5) DEFAULT 'NR',
  `si_sst_7` varchar(5) DEFAULT 'NR',
  `si_sst_8` varchar(5) DEFAULT 'NR',
  `si_sst_9` varchar(5) DEFAULT 'NR',
  `si_sst_10` varchar(5) DEFAULT 'NR'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `prueba_pdc`
--

CREATE TABLE `prueba_pdc` (
  `consecutivo` int NOT NULL,
  `semana` int NOT NULL,
  `titulo` int NOT NULL,
  `tipoPaquete` varchar(200) NOT NULL,
  `paqueteContratacion` varchar(200) DEFAULT NULL,
  `contratos` varchar(200) DEFAULT NULL,
  `numeroSubcontratos` int DEFAULT '1',
  `subcontratoPaquete` int NOT NULL DEFAULT '1',
  `estado` varchar(200) DEFAULT NULL,
  `fechaElaboracionPliegos` date DEFAULT NULL,
  `diasElaboracionPliegos` int DEFAULT NULL,
  `fechaRealElaboracionPliegos` date DEFAULT NULL,
  `fechaIngresoLicify` date DEFAULT NULL,
  `diasIngresoLicify` int DEFAULT NULL,
  `fechaRealIngresoLicify` date DEFAULT NULL,
  `fechaEntregaPliegos` date DEFAULT NULL,
  `diasEntregaPliegos` int DEFAULT NULL,
  `fechaRealEntregaPliegos` date DEFAULT NULL,
  `fechaReciboPropuestas` date DEFAULT NULL,
  `diasReciboPropuestas` int DEFAULT NULL,
  `fechaRealReciboPropuestas` date DEFAULT NULL,
  `fechaCuadrosComparativos` date DEFAULT NULL,
  `diasCuadrosComparativos` int DEFAULT NULL,
  `fechaRealCuadrosComparativos` date DEFAULT NULL,
  `fechaLegalizacionContrato` date DEFAULT NULL,
  `diasLegalizacionContrato` int DEFAULT NULL,
  `fechaRealLegalizacionContrato` date DEFAULT NULL,
  `fechaFabricacion` date DEFAULT NULL,
  `diasFabricacion` int DEFAULT NULL,
  `fechaRealFabricacion` date DEFAULT NULL,
  `fechaInsumosObra` date DEFAULT NULL,
  `diasInsumosObra` int DEFAULT NULL,
  `fechaRealInsumosObra` date DEFAULT NULL,
  `fechaInicio` date DEFAULT NULL,
  `fechaInicioProyectada` date DEFAULT NULL,
  `fechaRealInicio` date DEFAULT NULL,
  `idProveedorAdjudicado` int DEFAULT NULL,
  `numeroContrato` varchar(50) DEFAULT NULL,
  `aplicaPolizas` int NOT NULL DEFAULT '1',
  `fechaVencimientoPolizas` date DEFAULT NULL,
  `valorPresupuesto` float DEFAULT NULL,
  `valorPrimeraNegociacion` float DEFAULT NULL,
  `valorAdjudicado` float DEFAULT NULL,
  `valorAnticipo` float DEFAULT NULL,
  `valorReclamado` float DEFAULT NULL,
  `valorDevoluciones` float DEFAULT NULL,
  `observacionesContrato` mediumtext
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `prueba_pi_shared_constraints`
--

CREATE TABLE `prueba_pi_shared_constraints` (
  `Id` bigint UNSIGNED NOT NULL,
  `Semana` int NOT NULL,
  `Restriccion` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `ValorObjetivo` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `Nota` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `CreadoPor` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `CreadoEn` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ActualizadoEn` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `prueba_pi_shared_constraint_links`
--

CREATE TABLE `prueba_pi_shared_constraint_links` (
  `Id` bigint UNSIGNED NOT NULL,
  `SharedConstraintId` bigint UNSIGNED NOT NULL,
  `Semana` int NOT NULL,
  `ConsecutivoEnPrograma` bigint NOT NULL,
  `ValorAplicado` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `OverrideLocal` tinyint(1) NOT NULL DEFAULT '0',
  `AplicadoEn` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `prueba_profesionales`
--

CREATE TABLE `prueba_profesionales` (
  `id` int NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `cargo` varchar(100) NOT NULL,
  `activo` int NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `prueba_programa`
--

CREATE TABLE `prueba_programa` (
  `Consecutivo` int NOT NULL,
  `Id` varchar(500) DEFAULT NULL,
  `Actividad` varchar(500) DEFAULT NULL,
  `Titulo` int DEFAULT NULL,
  `Fecha_Inicio` date DEFAULT NULL,
  `Fecha_Fin` date DEFAULT NULL,
  `Ruta_Critica` int DEFAULT NULL,
  `Ejecutado` float DEFAULT '0',
  `Estado` varchar(50) DEFAULT NULL,
  `Semanas_Inicio` int DEFAULT '0',
  `Estado_Restricciones` float DEFAULT '0',
  `D_y_E` float DEFAULT '0',
  `Materiales` float DEFAULT '0',
  `MdeO` float DEFAULT '0',
  `Equipos` float DEFAULT '0',
  `Predecesora` float DEFAULT '0',
  `Pdto_Cons` float DEFAULT '0',
  `Modelo` varchar(9) DEFAULT '0',
  `Responsable_AIA` varchar(100) DEFAULT NULL,
  `Observaciones` mediumtext,
  `Ult_Act_Est` date DEFAULT NULL,
  `Ult_Act_Restr` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `prueba_programacion_semanal`
--

CREATE TABLE `prueba_programacion_semanal` (
  `Consecutivo` int NOT NULL,
  `Semana` int DEFAULT NULL,
  `Consecutivo_En_Programa` int NOT NULL,
  `Id` varchar(500) DEFAULT NULL,
  `Actividad` varchar(500) DEFAULT NULL,
  `Descripcion` mediumtext,
  `Ubicacion` mediumtext,
  `Fecha_Inicio` date DEFAULT NULL,
  `Fecha_Fin` date DEFAULT NULL,
  `Sub_Contratista` varchar(200) DEFAULT NULL,
  `Responsable_AIA` varchar(200) DEFAULT NULL,
  `Empresa` varchar(200) NOT NULL DEFAULT 'AIA',
  `Ejecutado` float DEFAULT NULL,
  `medir_productividad` int DEFAULT '0',
  `Unidad` varchar(10) DEFAULT NULL,
  `cantidad_ppto` int DEFAULT NULL,
  `Cantidad_Sugerida` float DEFAULT NULL,
  `Compromiso` float DEFAULT NULL,
  `Ejecutado_Real` float DEFAULT NULL,
  `P_Completado` float DEFAULT NULL,
  `PAC` int DEFAULT NULL,
  `Critica` int DEFAULT NULL,
  `Atrasada` int DEFAULT NULL,
  `Activa` varchar(3) DEFAULT NULL,
  `Prog_Sin_Restricciones_100` int DEFAULT NULL,
  `Categoria_CNP` varchar(100) DEFAULT NULL,
  `CNP` varchar(100) DEFAULT NULL,
  `Observaciones_CNP` mediumtext,
  `Categoria_CNC` varchar(100) DEFAULT NULL,
  `CNC` varchar(100) DEFAULT NULL,
  `Observaciones_CNC` mediumtext,
  `Rendimientos` varchar(500) DEFAULT NULL,
  `codigo_actividad` varchar(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `prueba_programa_consolidado`
--

CREATE TABLE `prueba_programa_consolidado` (
  `Consecutivo` int NOT NULL,
  `Semana` int NOT NULL,
  `Consecutivo_en_Programa` int NOT NULL,
  `Id` varchar(500) DEFAULT NULL,
  `Actividad` varchar(500) DEFAULT NULL,
  `Titulo` int DEFAULT NULL,
  `Fecha_Inicio` date DEFAULT NULL,
  `Fecha_Fin` date DEFAULT NULL,
  `Ruta_Critica` int DEFAULT NULL,
  `Ejecutado` float DEFAULT '0',
  `Estado` varchar(100) DEFAULT NULL,
  `Semanas_Inicio` int DEFAULT '0',
  `Estado_Restricciones` float NOT NULL DEFAULT '0',
  `D_y_E` varchar(9) NOT NULL DEFAULT '0',
  `Materiales` varchar(9) NOT NULL DEFAULT '0',
  `MdeO` varchar(9) NOT NULL DEFAULT '0',
  `Equipos` varchar(9) NOT NULL DEFAULT '0',
  `Predecesora` varchar(9) NOT NULL DEFAULT '0',
  `Pdto_Cons` varchar(9) NOT NULL DEFAULT '0',
  `Modelo` varchar(9) NOT NULL DEFAULT '0',
  `Sub_Contratista` varchar(100) DEFAULT NULL,
  `Responsable_AIA` varchar(100) DEFAULT NULL,
  `Observaciones` mediumtext,
  `Ult_Act_Est` date DEFAULT NULL,
  `Ult_Act_Restr` date DEFAULT NULL,
  `Activa` int NOT NULL DEFAULT '0',
  `Ejecutado_Siguiente_Semana` float DEFAULT NULL,
  `codigo_actividad` varchar(11) DEFAULT NULL,
  `medir_productividad` int DEFAULT '0',
  `cantidad_ppto` int DEFAULT NULL,
  `unidad` varchar(20) DEFAULT NULL,
  `programaAnteriorAsociar` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `prueba_semanas_activas`
--

CREATE TABLE `prueba_semanas_activas` (
  `Id` int NOT NULL,
  `Semana` int NOT NULL,
  `Fecha_Inicio_Sem` date NOT NULL,
  `Fecha_Fin_Sem` date NOT NULL,
  `Semanal_Confirmada` int DEFAULT '0',
  `fechaCierreCompromisos` date DEFAULT NULL,
  `fechaCreacionSemana` date DEFAULT NULL,
  `reprogramacion` int NOT NULL DEFAULT '0',
  `diferenciaEstructuraCron` int NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `prueba_subcontratistas`
--

CREATE TABLE `prueba_subcontratistas` (
  `Id` int NOT NULL,
  `subcontratista` varchar(200) NOT NULL,
  `correo_contacto` varchar(200) NOT NULL,
  `NIT` bigint NOT NULL,
  `alcance` varchar(200) NOT NULL,
  `tipo_proveedor` varchar(200) NOT NULL,
  `activo` int NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rbac_permissions`
--

CREATE TABLE `rbac_permissions` (
  `permission_key` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `module_name` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `action_name` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_write` tinyint(1) NOT NULL DEFAULT '0',
  `is_sensitive` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rbac_roles`
--

CREATE TABLE `rbac_roles` (
  `code` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_admin_area` tinyint(1) NOT NULL DEFAULT '0',
  `is_system_admin` tinyint(1) NOT NULL DEFAULT '0',
  `is_legacy` tinyint(1) NOT NULL DEFAULT '0',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` int NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rbac_role_permissions`
--

CREATE TABLE `rbac_role_permissions` (
  `role_code` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `permission_key` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `allowed` tinyint(1) NOT NULL DEFAULT '0',
  `source` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'seed',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `role_intelligence`
--

CREATE TABLE `role_intelligence` (
  `id` int NOT NULL,
  `cargo_title` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `suggested_role` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `role_notification_defaults`
--

CREATE TABLE `role_notification_defaults` (
  `role_code` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `notification_code` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `system_notifications`
--

CREATE TABLE `system_notifications` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ID o Username del destinatario',
  `type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Categoría de alerta',
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `item_count` int UNSIGNED NOT NULL DEFAULT '1' COMMENT 'Cantidad de eventos agrupados',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `project_id` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Atadura a proyecto específico'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `accesibilidadMetroA_actividades`
--
ALTER TABLE `accesibilidadMetroA_actividades`
  ADD PRIMARY KEY (`Id`);

--
-- Indices de la tabla `accesibilidadMetroA_cambios`
--
ALTER TABLE `accesibilidadMetroA_cambios`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `accesibilidadMetroA_cic`
--
ALTER TABLE `accesibilidadMetroA_cic`
  ADD PRIMARY KEY (`Id`);

--
-- Indices de la tabla `accesibilidadMetroA_pdc`
--
ALTER TABLE `accesibilidadMetroA_pdc`
  ADD PRIMARY KEY (`consecutivo`);

--
-- Indices de la tabla `accesibilidadMetroA_pi_shared_constraints`
--
ALTER TABLE `accesibilidadMetroA_pi_shared_constraints`
  ADD PRIMARY KEY (`Id`),
  ADD KEY `idx_semana` (`Semana`),
  ADD KEY `idx_restriccion` (`Restriccion`);

--
-- Indices de la tabla `accesibilidadMetroA_pi_shared_constraint_links`
--
ALTER TABLE `accesibilidadMetroA_pi_shared_constraint_links`
  ADD PRIMARY KEY (`Id`),
  ADD KEY `idx_shared` (`SharedConstraintId`),
  ADD KEY `idx_semana_consecutivo` (`Semana`,`ConsecutivoEnPrograma`);

--
-- Indices de la tabla `accesibilidadMetroA_profesionales`
--
ALTER TABLE `accesibilidadMetroA_profesionales`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `accesibilidadMetroA_programa`
--
ALTER TABLE `accesibilidadMetroA_programa`
  ADD PRIMARY KEY (`Consecutivo`);

--
-- Indices de la tabla `accesibilidadMetroA_programacion_semanal`
--
ALTER TABLE `accesibilidadMetroA_programacion_semanal`
  ADD PRIMARY KEY (`Consecutivo`);

--
-- Indices de la tabla `accesibilidadMetroA_programa_consolidado`
--
ALTER TABLE `accesibilidadMetroA_programa_consolidado`
  ADD PRIMARY KEY (`Consecutivo`);

--
-- Indices de la tabla `accesibilidadMetroA_semanas_activas`
--
ALTER TABLE `accesibilidadMetroA_semanas_activas`
  ADD PRIMARY KEY (`Id`);

--
-- Indices de la tabla `accesibilidadMetroA_subcontratistas`
--
ALTER TABLE `accesibilidadMetroA_subcontratistas`
  ADD PRIMARY KEY (`Id`);

--
-- Indices de la tabla `accesibilidadMetroB_actividades`
--
ALTER TABLE `accesibilidadMetroB_actividades`
  ADD PRIMARY KEY (`Id`);

--
-- Indices de la tabla `accesibilidadMetroB_cambios`
--
ALTER TABLE `accesibilidadMetroB_cambios`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `accesibilidadMetroB_cic`
--
ALTER TABLE `accesibilidadMetroB_cic`
  ADD PRIMARY KEY (`Id`);

--
-- Indices de la tabla `accesibilidadMetroB_pdc`
--
ALTER TABLE `accesibilidadMetroB_pdc`
  ADD PRIMARY KEY (`consecutivo`);

--
-- Indices de la tabla `accesibilidadMetroB_pi_shared_constraints`
--
ALTER TABLE `accesibilidadMetroB_pi_shared_constraints`
  ADD PRIMARY KEY (`Id`),
  ADD KEY `idx_semana` (`Semana`),
  ADD KEY `idx_restriccion` (`Restriccion`);

--
-- Indices de la tabla `accesibilidadMetroB_pi_shared_constraint_links`
--
ALTER TABLE `accesibilidadMetroB_pi_shared_constraint_links`
  ADD PRIMARY KEY (`Id`),
  ADD KEY `idx_shared` (`SharedConstraintId`),
  ADD KEY `idx_semana_consecutivo` (`Semana`,`ConsecutivoEnPrograma`);

--
-- Indices de la tabla `accesibilidadMetroB_profesionales`
--
ALTER TABLE `accesibilidadMetroB_profesionales`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `accesibilidadMetroB_programa`
--
ALTER TABLE `accesibilidadMetroB_programa`
  ADD PRIMARY KEY (`Consecutivo`);

--
-- Indices de la tabla `accesibilidadMetroB_programacion_semanal`
--
ALTER TABLE `accesibilidadMetroB_programacion_semanal`
  ADD PRIMARY KEY (`Consecutivo`);

--
-- Indices de la tabla `accesibilidadMetroB_programa_consolidado`
--
ALTER TABLE `accesibilidadMetroB_programa_consolidado`
  ADD PRIMARY KEY (`Consecutivo`);

--
-- Indices de la tabla `accesibilidadMetroB_semanas_activas`
--
ALTER TABLE `accesibilidadMetroB_semanas_activas`
  ADD PRIMARY KEY (`Id`);

--
-- Indices de la tabla `accesibilidadMetroB_subcontratistas`
--
ALTER TABLE `accesibilidadMetroB_subcontratistas`
  ADD PRIMARY KEY (`Id`);

--
-- Indices de la tabla `da_porto_actividades`
--
ALTER TABLE `da_porto_actividades`
  ADD PRIMARY KEY (`Id`);

--
-- Indices de la tabla `da_porto_cambios`
--
ALTER TABLE `da_porto_cambios`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `da_porto_cic`
--
ALTER TABLE `da_porto_cic`
  ADD PRIMARY KEY (`Id`);

--
-- Indices de la tabla `da_porto_pdc`
--
ALTER TABLE `da_porto_pdc`
  ADD PRIMARY KEY (`consecutivo`);

--
-- Indices de la tabla `da_porto_profesionales`
--
ALTER TABLE `da_porto_profesionales`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `da_porto_programa`
--
ALTER TABLE `da_porto_programa`
  ADD PRIMARY KEY (`Consecutivo`);

--
-- Indices de la tabla `da_porto_programacion_semanal`
--
ALTER TABLE `da_porto_programacion_semanal`
  ADD PRIMARY KEY (`Consecutivo`);

--
-- Indices de la tabla `da_porto_programa_consolidado`
--
ALTER TABLE `da_porto_programa_consolidado`
  ADD PRIMARY KEY (`Consecutivo`);

--
-- Indices de la tabla `da_porto_semanas_activas`
--
ALTER TABLE `da_porto_semanas_activas`
  ADD PRIMARY KEY (`Id`);

--
-- Indices de la tabla `da_porto_subcontratistas`
--
ALTER TABLE `da_porto_subcontratistas`
  ADD PRIMARY KEY (`Id`);

--
-- Indices de la tabla `event_dictionary`
--
ALTER TABLE `event_dictionary`
  ADD PRIMARY KEY (`event_code`,`event_action`),
  ADD KEY `idx_event_dictionary_mod_legacy` (`modulo_legacy`,`accion_legacy`),
  ADD KEY `fk_event_dictionary_notification_type` (`notification_code`);

--
-- Indices de la tabla `general_auditoria_acciones`
--
ALTER TABLE `general_auditoria_acciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario` (`usuario`),
  ADD KEY `modulo` (`modulo`),
  ADD KEY `fecha` (`fecha`);

--
-- Indices de la tabla `general_cnc`
--
ALTER TABLE `general_cnc`
  ADD PRIMARY KEY (`Id`);

--
-- Indices de la tabla `general_codigos_actividades`
--
ALTER TABLE `general_codigos_actividades`
  ADD PRIMARY KEY (`Id`);

--
-- Indices de la tabla `general_costos_cuadrillas`
--
ALTER TABLE `general_costos_cuadrillas`
  ADD PRIMARY KEY (`Id`);

--
-- Indices de la tabla `general_cuadrillas_tipicas`
--
ALTER TABLE `general_cuadrillas_tipicas`
  ADD PRIMARY KEY (`Id`);

--
-- Indices de la tabla `general_curvas`
--
ALTER TABLE `general_curvas`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `general_curvas_pdc`
--
ALTER TABLE `general_curvas_pdc`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `general_curvas_pdc_apr`
--
ALTER TABLE `general_curvas_pdc_apr`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `general_dias_procesos_contratacion`
--
ALTER TABLE `general_dias_procesos_contratacion`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `general_feature_flags`
--
ALTER TABLE `general_feature_flags`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_flag_key` (`flag_key`);

--
-- Indices de la tabla `general_informe_consolidado`
--
ALTER TABLE `general_informe_consolidado`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `general_informe_pdc`
--
ALTER TABLE `general_informe_pdc`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `general_informe_restricciones_consolidado`
--
ALTER TABLE `general_informe_restricciones_consolidado`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `general_informe_subcontratistas`
--
ALTER TABLE `general_informe_subcontratistas`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `general_proyectos_procesos`
--
ALTER TABLE `general_proyectos_procesos`
  ADD PRIMARY KEY (`Id`);

--
-- Indices de la tabla `general_usuarios`
--
ALTER TABLE `general_usuarios`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `homecenterMallplaza_actividades`
--
ALTER TABLE `homecenterMallplaza_actividades`
  ADD PRIMARY KEY (`Id`);

--
-- Indices de la tabla `homecenterMallplaza_cic`
--
ALTER TABLE `homecenterMallplaza_cic`
  ADD PRIMARY KEY (`Id`);

--
-- Indices de la tabla `homecenterMallplaza_pdc`
--
ALTER TABLE `homecenterMallplaza_pdc`
  ADD PRIMARY KEY (`consecutivo`);

--
-- Indices de la tabla `homecenterMallplaza_pi_shared_constraints`
--
ALTER TABLE `homecenterMallplaza_pi_shared_constraints`
  ADD PRIMARY KEY (`Id`),
  ADD KEY `idx_semana` (`Semana`),
  ADD KEY `idx_restriccion` (`Restriccion`);

--
-- Indices de la tabla `homecenterMallplaza_pi_shared_constraint_links`
--
ALTER TABLE `homecenterMallplaza_pi_shared_constraint_links`
  ADD PRIMARY KEY (`Id`),
  ADD KEY `idx_shared` (`SharedConstraintId`),
  ADD KEY `idx_semana_consecutivo` (`Semana`,`ConsecutivoEnPrograma`);

--
-- Indices de la tabla `homecenterMallplaza_profesionales`
--
ALTER TABLE `homecenterMallplaza_profesionales`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `homecenterMallplaza_programa`
--
ALTER TABLE `homecenterMallplaza_programa`
  ADD PRIMARY KEY (`Consecutivo`);

--
-- Indices de la tabla `homecenterMallplaza_programacion_semanal`
--
ALTER TABLE `homecenterMallplaza_programacion_semanal`
  ADD PRIMARY KEY (`Consecutivo`);

--
-- Indices de la tabla `homecenterMallplaza_programa_consolidado`
--
ALTER TABLE `homecenterMallplaza_programa_consolidado`
  ADD PRIMARY KEY (`Consecutivo`);

--
-- Indices de la tabla `homecenterMallplaza_semanas_activas`
--
ALTER TABLE `homecenterMallplaza_semanas_activas`
  ADD PRIMARY KEY (`Id`);

--
-- Indices de la tabla `laMasia_actividades`
--
ALTER TABLE `laMasia_actividades`
  ADD PRIMARY KEY (`Id`);

--
-- Indices de la tabla `laMasia_cic`
--
ALTER TABLE `laMasia_cic`
  ADD PRIMARY KEY (`Id`);

--
-- Indices de la tabla `laMasia_pdc`
--
ALTER TABLE `laMasia_pdc`
  ADD PRIMARY KEY (`consecutivo`);

--
-- Indices de la tabla `laMasia_pi_shared_constraints`
--
ALTER TABLE `laMasia_pi_shared_constraints`
  ADD PRIMARY KEY (`Id`),
  ADD KEY `idx_semana` (`Semana`),
  ADD KEY `idx_restriccion` (`Restriccion`);

--
-- Indices de la tabla `laMasia_pi_shared_constraint_links`
--
ALTER TABLE `laMasia_pi_shared_constraint_links`
  ADD PRIMARY KEY (`Id`),
  ADD KEY `idx_shared` (`SharedConstraintId`),
  ADD KEY `idx_semana_consecutivo` (`Semana`,`ConsecutivoEnPrograma`);

--
-- Indices de la tabla `laMasia_profesionales`
--
ALTER TABLE `laMasia_profesionales`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `laMasia_programa`
--
ALTER TABLE `laMasia_programa`
  ADD PRIMARY KEY (`Consecutivo`);

--
-- Indices de la tabla `laMasia_programacion_semanal`
--
ALTER TABLE `laMasia_programacion_semanal`
  ADD PRIMARY KEY (`Consecutivo`);

--
-- Indices de la tabla `laMasia_programa_consolidado`
--
ALTER TABLE `laMasia_programa_consolidado`
  ADD PRIMARY KEY (`Consecutivo`);

--
-- Indices de la tabla `laMasia_semanas_activas`
--
ALTER TABLE `laMasia_semanas_activas`
  ADD PRIMARY KEY (`Id`);

--
-- Indices de la tabla `laMasia_subcontratistas`
--
ALTER TABLE `laMasia_subcontratistas`
  ADD PRIMARY KEY (`Id`);

--
-- Indices de la tabla `metrolineaConfinamientoDos_actividades`
--
ALTER TABLE `metrolineaConfinamientoDos_actividades`
  ADD PRIMARY KEY (`Id`);

--
-- Indices de la tabla `metrolineaConfinamientoDos_cambios`
--
ALTER TABLE `metrolineaConfinamientoDos_cambios`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `metrolineaConfinamientoDos_cic`
--
ALTER TABLE `metrolineaConfinamientoDos_cic`
  ADD PRIMARY KEY (`Id`);

--
-- Indices de la tabla `metrolineaConfinamientoDos_pdc`
--
ALTER TABLE `metrolineaConfinamientoDos_pdc`
  ADD PRIMARY KEY (`consecutivo`);

--
-- Indices de la tabla `metrolineaConfinamientoDos_pi_shared_constraints`
--
ALTER TABLE `metrolineaConfinamientoDos_pi_shared_constraints`
  ADD PRIMARY KEY (`Id`),
  ADD KEY `idx_semana` (`Semana`),
  ADD KEY `idx_restriccion` (`Restriccion`);

--
-- Indices de la tabla `metrolineaConfinamientoDos_pi_shared_constraint_links`
--
ALTER TABLE `metrolineaConfinamientoDos_pi_shared_constraint_links`
  ADD PRIMARY KEY (`Id`),
  ADD KEY `idx_shared` (`SharedConstraintId`),
  ADD KEY `idx_semana_consecutivo` (`Semana`,`ConsecutivoEnPrograma`);

--
-- Indices de la tabla `metrolineaConfinamientoDos_profesionales`
--
ALTER TABLE `metrolineaConfinamientoDos_profesionales`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `metrolineaConfinamientoDos_programa`
--
ALTER TABLE `metrolineaConfinamientoDos_programa`
  ADD PRIMARY KEY (`Consecutivo`);

--
-- Indices de la tabla `metrolineaConfinamientoDos_programacion_semanal`
--
ALTER TABLE `metrolineaConfinamientoDos_programacion_semanal`
  ADD PRIMARY KEY (`Consecutivo`);

--
-- Indices de la tabla `metrolineaConfinamientoDos_programa_consolidado`
--
ALTER TABLE `metrolineaConfinamientoDos_programa_consolidado`
  ADD PRIMARY KEY (`Consecutivo`);

--
-- Indices de la tabla `metrolineaConfinamientoDos_semanas_activas`
--
ALTER TABLE `metrolineaConfinamientoDos_semanas_activas`
  ADD PRIMARY KEY (`Id`);

--
-- Indices de la tabla `metrolineaConfinamientoDos_subcontratistas`
--
ALTER TABLE `metrolineaConfinamientoDos_subcontratistas`
  ADD PRIMARY KEY (`Id`);

--
-- Indices de la tabla `metrolineaDieciseisAscendente_actividades`
--
ALTER TABLE `metrolineaDieciseisAscendente_actividades`
  ADD PRIMARY KEY (`Id`);

--
-- Indices de la tabla `metrolineaDieciseisAscendente_cambios`
--
ALTER TABLE `metrolineaDieciseisAscendente_cambios`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `metrolineaDieciseisAscendente_cic`
--
ALTER TABLE `metrolineaDieciseisAscendente_cic`
  ADD PRIMARY KEY (`Id`);

--
-- Indices de la tabla `metrolineaDieciseisAscendente_pdc`
--
ALTER TABLE `metrolineaDieciseisAscendente_pdc`
  ADD PRIMARY KEY (`consecutivo`);

--
-- Indices de la tabla `metrolineaDieciseisAscendente_pi_shared_constraints`
--
ALTER TABLE `metrolineaDieciseisAscendente_pi_shared_constraints`
  ADD PRIMARY KEY (`Id`),
  ADD KEY `idx_semana` (`Semana`),
  ADD KEY `idx_restriccion` (`Restriccion`);

--
-- Indices de la tabla `metrolineaDieciseisAscendente_pi_shared_constraint_links`
--
ALTER TABLE `metrolineaDieciseisAscendente_pi_shared_constraint_links`
  ADD PRIMARY KEY (`Id`),
  ADD KEY `idx_shared` (`SharedConstraintId`),
  ADD KEY `idx_semana_consecutivo` (`Semana`,`ConsecutivoEnPrograma`);

--
-- Indices de la tabla `metrolineaDieciseisAscendente_profesionales`
--
ALTER TABLE `metrolineaDieciseisAscendente_profesionales`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `metrolineaDieciseisAscendente_programa`
--
ALTER TABLE `metrolineaDieciseisAscendente_programa`
  ADD PRIMARY KEY (`Consecutivo`);

--
-- Indices de la tabla `metrolineaDieciseisAscendente_programacion_semanal`
--
ALTER TABLE `metrolineaDieciseisAscendente_programacion_semanal`
  ADD PRIMARY KEY (`Consecutivo`);

--
-- Indices de la tabla `metrolineaDieciseisAscendente_programa_consolidado`
--
ALTER TABLE `metrolineaDieciseisAscendente_programa_consolidado`
  ADD PRIMARY KEY (`Consecutivo`);

--
-- Indices de la tabla `metrolineaDieciseisAscendente_semanas_activas`
--
ALTER TABLE `metrolineaDieciseisAscendente_semanas_activas`
  ADD PRIMARY KEY (`Id`);

--
-- Indices de la tabla `metrolineaDieciseisAscendente_subcontratistas`
--
ALTER TABLE `metrolineaDieciseisAscendente_subcontratistas`
  ADD PRIMARY KEY (`Id`);

--
-- Indices de la tabla `metrolineaDieciseisDescendente_actividades`
--
ALTER TABLE `metrolineaDieciseisDescendente_actividades`
  ADD PRIMARY KEY (`Id`);

--
-- Indices de la tabla `metrolineaDieciseisDescendente_cambios`
--
ALTER TABLE `metrolineaDieciseisDescendente_cambios`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `metrolineaDieciseisDescendente_cic`
--
ALTER TABLE `metrolineaDieciseisDescendente_cic`
  ADD PRIMARY KEY (`Id`);

--
-- Indices de la tabla `metrolineaDieciseisDescendente_pdc`
--
ALTER TABLE `metrolineaDieciseisDescendente_pdc`
  ADD PRIMARY KEY (`consecutivo`);

--
-- Indices de la tabla `metrolineaDieciseisDescendente_pi_shared_constraints`
--
ALTER TABLE `metrolineaDieciseisDescendente_pi_shared_constraints`
  ADD PRIMARY KEY (`Id`),
  ADD KEY `idx_semana` (`Semana`),
  ADD KEY `idx_restriccion` (`Restriccion`);

--
-- Indices de la tabla `metrolineaDieciseisDescendente_pi_shared_constraint_links`
--
ALTER TABLE `metrolineaDieciseisDescendente_pi_shared_constraint_links`
  ADD PRIMARY KEY (`Id`),
  ADD KEY `idx_shared` (`SharedConstraintId`),
  ADD KEY `idx_semana_consecutivo` (`Semana`,`ConsecutivoEnPrograma`);

--
-- Indices de la tabla `metrolineaDieciseisDescendente_profesionales`
--
ALTER TABLE `metrolineaDieciseisDescendente_profesionales`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `metrolineaDieciseisDescendente_programa`
--
ALTER TABLE `metrolineaDieciseisDescendente_programa`
  ADD PRIMARY KEY (`Consecutivo`);

--
-- Indices de la tabla `metrolineaDieciseisDescendente_programacion_semanal`
--
ALTER TABLE `metrolineaDieciseisDescendente_programacion_semanal`
  ADD PRIMARY KEY (`Consecutivo`);

--
-- Indices de la tabla `metrolineaDieciseisDescendente_programa_consolidado`
--
ALTER TABLE `metrolineaDieciseisDescendente_programa_consolidado`
  ADD PRIMARY KEY (`Consecutivo`);

--
-- Indices de la tabla `metrolineaDieciseisDescendente_semanas_activas`
--
ALTER TABLE `metrolineaDieciseisDescendente_semanas_activas`
  ADD PRIMARY KEY (`Id`);

--
-- Indices de la tabla `metrolineaDieciseisDescendente_subcontratistas`
--
ALTER TABLE `metrolineaDieciseisDescendente_subcontratistas`
  ADD PRIMARY KEY (`Id`);

--
-- Indices de la tabla `metrolineaDos_actividades`
--
ALTER TABLE `metrolineaDos_actividades`
  ADD PRIMARY KEY (`Id`);

--
-- Indices de la tabla `metrolineaDos_cambios`
--
ALTER TABLE `metrolineaDos_cambios`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `metrolineaDos_cic`
--
ALTER TABLE `metrolineaDos_cic`
  ADD PRIMARY KEY (`Id`);

--
-- Indices de la tabla `metrolineaDos_pdc`
--
ALTER TABLE `metrolineaDos_pdc`
  ADD PRIMARY KEY (`consecutivo`);

--
-- Indices de la tabla `metrolineaDos_pi_shared_constraints`
--
ALTER TABLE `metrolineaDos_pi_shared_constraints`
  ADD PRIMARY KEY (`Id`),
  ADD KEY `idx_semana` (`Semana`),
  ADD KEY `idx_restriccion` (`Restriccion`);

--
-- Indices de la tabla `metrolineaDos_pi_shared_constraint_links`
--
ALTER TABLE `metrolineaDos_pi_shared_constraint_links`
  ADD PRIMARY KEY (`Id`),
  ADD KEY `idx_shared` (`SharedConstraintId`),
  ADD KEY `idx_semana_consecutivo` (`Semana`,`ConsecutivoEnPrograma`);

--
-- Indices de la tabla `metrolineaDos_profesionales`
--
ALTER TABLE `metrolineaDos_profesionales`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `metrolineaDos_programa`
--
ALTER TABLE `metrolineaDos_programa`
  ADD PRIMARY KEY (`Consecutivo`);

--
-- Indices de la tabla `metrolineaDos_programacion_semanal`
--
ALTER TABLE `metrolineaDos_programacion_semanal`
  ADD PRIMARY KEY (`Consecutivo`);

--
-- Indices de la tabla `metrolineaDos_programa_consolidado`
--
ALTER TABLE `metrolineaDos_programa_consolidado`
  ADD PRIMARY KEY (`Consecutivo`);

--
-- Indices de la tabla `metrolineaDos_semanas_activas`
--
ALTER TABLE `metrolineaDos_semanas_activas`
  ADD PRIMARY KEY (`Id`);

--
-- Indices de la tabla `metrolineaDos_subcontratistas`
--
ALTER TABLE `metrolineaDos_subcontratistas`
  ADD PRIMARY KEY (`Id`);

--
-- Indices de la tabla `metrolineaMampDos_actividades`
--
ALTER TABLE `metrolineaMampDos_actividades`
  ADD PRIMARY KEY (`Id`);

--
-- Indices de la tabla `metrolineaMampDos_cambios`
--
ALTER TABLE `metrolineaMampDos_cambios`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `metrolineaMampDos_cic`
--
ALTER TABLE `metrolineaMampDos_cic`
  ADD PRIMARY KEY (`Id`);

--
-- Indices de la tabla `metrolineaMampDos_pdc`
--
ALTER TABLE `metrolineaMampDos_pdc`
  ADD PRIMARY KEY (`consecutivo`);

--
-- Indices de la tabla `metrolineaMampDos_pi_shared_constraints`
--
ALTER TABLE `metrolineaMampDos_pi_shared_constraints`
  ADD PRIMARY KEY (`Id`),
  ADD KEY `idx_semana` (`Semana`),
  ADD KEY `idx_restriccion` (`Restriccion`);

--
-- Indices de la tabla `metrolineaMampDos_pi_shared_constraint_links`
--
ALTER TABLE `metrolineaMampDos_pi_shared_constraint_links`
  ADD PRIMARY KEY (`Id`),
  ADD KEY `idx_shared` (`SharedConstraintId`),
  ADD KEY `idx_semana_consecutivo` (`Semana`,`ConsecutivoEnPrograma`);

--
-- Indices de la tabla `metrolineaMampDos_profesionales`
--
ALTER TABLE `metrolineaMampDos_profesionales`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `metrolineaMampDos_programa`
--
ALTER TABLE `metrolineaMampDos_programa`
  ADD PRIMARY KEY (`Consecutivo`);

--
-- Indices de la tabla `metrolineaMampDos_programacion_semanal`
--
ALTER TABLE `metrolineaMampDos_programacion_semanal`
  ADD PRIMARY KEY (`Consecutivo`);

--
-- Indices de la tabla `metrolineaMampDos_programa_consolidado`
--
ALTER TABLE `metrolineaMampDos_programa_consolidado`
  ADD PRIMARY KEY (`Consecutivo`);

--
-- Indices de la tabla `metrolineaMampDos_semanas_activas`
--
ALTER TABLE `metrolineaMampDos_semanas_activas`
  ADD PRIMARY KEY (`Id`);

--
-- Indices de la tabla `metrolineaMampDos_subcontratistas`
--
ALTER TABLE `metrolineaMampDos_subcontratistas`
  ADD PRIMARY KEY (`Id`);

--
-- Indices de la tabla `metrolineaMampSeis_actividades`
--
ALTER TABLE `metrolineaMampSeis_actividades`
  ADD PRIMARY KEY (`Id`);

--
-- Indices de la tabla `metrolineaMampSeis_cambios`
--
ALTER TABLE `metrolineaMampSeis_cambios`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `metrolineaMampSeis_cic`
--
ALTER TABLE `metrolineaMampSeis_cic`
  ADD PRIMARY KEY (`Id`);

--
-- Indices de la tabla `metrolineaMampSeis_pdc`
--
ALTER TABLE `metrolineaMampSeis_pdc`
  ADD PRIMARY KEY (`consecutivo`);

--
-- Indices de la tabla `metrolineaMampSeis_pi_shared_constraints`
--
ALTER TABLE `metrolineaMampSeis_pi_shared_constraints`
  ADD PRIMARY KEY (`Id`),
  ADD KEY `idx_semana` (`Semana`),
  ADD KEY `idx_restriccion` (`Restriccion`);

--
-- Indices de la tabla `metrolineaMampSeis_pi_shared_constraint_links`
--
ALTER TABLE `metrolineaMampSeis_pi_shared_constraint_links`
  ADD PRIMARY KEY (`Id`),
  ADD KEY `idx_shared` (`SharedConstraintId`),
  ADD KEY `idx_semana_consecutivo` (`Semana`,`ConsecutivoEnPrograma`);

--
-- Indices de la tabla `metrolineaMampSeis_profesionales`
--
ALTER TABLE `metrolineaMampSeis_profesionales`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `metrolineaMampSeis_programa`
--
ALTER TABLE `metrolineaMampSeis_programa`
  ADD PRIMARY KEY (`Consecutivo`);

--
-- Indices de la tabla `metrolineaMampSeis_programacion_semanal`
--
ALTER TABLE `metrolineaMampSeis_programacion_semanal`
  ADD PRIMARY KEY (`Consecutivo`);

--
-- Indices de la tabla `metrolineaMampSeis_programa_consolidado`
--
ALTER TABLE `metrolineaMampSeis_programa_consolidado`
  ADD PRIMARY KEY (`Consecutivo`);

--
-- Indices de la tabla `metrolineaMampSeis_semanas_activas`
--
ALTER TABLE `metrolineaMampSeis_semanas_activas`
  ADD PRIMARY KEY (`Id`);

--
-- Indices de la tabla `metrolineaMampSeis_subcontratistas`
--
ALTER TABLE `metrolineaMampSeis_subcontratistas`
  ADD PRIMARY KEY (`Id`);

--
-- Indices de la tabla `metrolineaMampUno_actividades`
--
ALTER TABLE `metrolineaMampUno_actividades`
  ADD PRIMARY KEY (`Id`);

--
-- Indices de la tabla `metrolineaMampUno_cambios`
--
ALTER TABLE `metrolineaMampUno_cambios`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `metrolineaMampUno_cic`
--
ALTER TABLE `metrolineaMampUno_cic`
  ADD PRIMARY KEY (`Id`);

--
-- Indices de la tabla `metrolineaMampUno_pdc`
--
ALTER TABLE `metrolineaMampUno_pdc`
  ADD PRIMARY KEY (`consecutivo`);

--
-- Indices de la tabla `metrolineaMampUno_pi_shared_constraints`
--
ALTER TABLE `metrolineaMampUno_pi_shared_constraints`
  ADD PRIMARY KEY (`Id`),
  ADD KEY `idx_semana` (`Semana`),
  ADD KEY `idx_restriccion` (`Restriccion`);

--
-- Indices de la tabla `metrolineaMampUno_pi_shared_constraint_links`
--
ALTER TABLE `metrolineaMampUno_pi_shared_constraint_links`
  ADD PRIMARY KEY (`Id`),
  ADD KEY `idx_shared` (`SharedConstraintId`),
  ADD KEY `idx_semana_consecutivo` (`Semana`,`ConsecutivoEnPrograma`);

--
-- Indices de la tabla `metrolineaMampUno_profesionales`
--
ALTER TABLE `metrolineaMampUno_profesionales`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `metrolineaMampUno_programa`
--
ALTER TABLE `metrolineaMampUno_programa`
  ADD PRIMARY KEY (`Consecutivo`);

--
-- Indices de la tabla `metrolineaMampUno_programacion_semanal`
--
ALTER TABLE `metrolineaMampUno_programacion_semanal`
  ADD PRIMARY KEY (`Consecutivo`);

--
-- Indices de la tabla `metrolineaMampUno_programa_consolidado`
--
ALTER TABLE `metrolineaMampUno_programa_consolidado`
  ADD PRIMARY KEY (`Consecutivo`);

--
-- Indices de la tabla `metrolineaMampUno_semanas_activas`
--
ALTER TABLE `metrolineaMampUno_semanas_activas`
  ADD PRIMARY KEY (`Id`);

--
-- Indices de la tabla `metrolineaMampUno_subcontratistas`
--
ALTER TABLE `metrolineaMampUno_subcontratistas`
  ADD PRIMARY KEY (`Id`);

--
-- Indices de la tabla `metrolineaMurosDos_actividades`
--
ALTER TABLE `metrolineaMurosDos_actividades`
  ADD PRIMARY KEY (`Id`);

--
-- Indices de la tabla `metrolineaMurosDos_cambios`
--
ALTER TABLE `metrolineaMurosDos_cambios`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `metrolineaMurosDos_cic`
--
ALTER TABLE `metrolineaMurosDos_cic`
  ADD PRIMARY KEY (`Id`);

--
-- Indices de la tabla `metrolineaMurosDos_pdc`
--
ALTER TABLE `metrolineaMurosDos_pdc`
  ADD PRIMARY KEY (`consecutivo`);

--
-- Indices de la tabla `metrolineaMurosDos_profesionales`
--
ALTER TABLE `metrolineaMurosDos_profesionales`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `metrolineaMurosDos_programa`
--
ALTER TABLE `metrolineaMurosDos_programa`
  ADD PRIMARY KEY (`Consecutivo`);

--
-- Indices de la tabla `metrolineaMurosDos_programacion_semanal`
--
ALTER TABLE `metrolineaMurosDos_programacion_semanal`
  ADD PRIMARY KEY (`Consecutivo`);

--
-- Indices de la tabla `metrolineaMurosDos_programa_consolidado`
--
ALTER TABLE `metrolineaMurosDos_programa_consolidado`
  ADD PRIMARY KEY (`Consecutivo`);

--
-- Indices de la tabla `metrolineaMurosDos_semanas_activas`
--
ALTER TABLE `metrolineaMurosDos_semanas_activas`
  ADD PRIMARY KEY (`Id`);

--
-- Indices de la tabla `metrolineaMurosDos_subcontratistas`
--
ALTER TABLE `metrolineaMurosDos_subcontratistas`
  ADD PRIMARY KEY (`Id`);

--
-- Indices de la tabla `metrolineaSeis_actividades`
--
ALTER TABLE `metrolineaSeis_actividades`
  ADD PRIMARY KEY (`Id`);

--
-- Indices de la tabla `metrolineaSeis_cambios`
--
ALTER TABLE `metrolineaSeis_cambios`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `metrolineaSeis_cic`
--
ALTER TABLE `metrolineaSeis_cic`
  ADD PRIMARY KEY (`Id`);

--
-- Indices de la tabla `metrolineaSeis_pdc`
--
ALTER TABLE `metrolineaSeis_pdc`
  ADD PRIMARY KEY (`consecutivo`);

--
-- Indices de la tabla `metrolineaSeis_pi_shared_constraints`
--
ALTER TABLE `metrolineaSeis_pi_shared_constraints`
  ADD PRIMARY KEY (`Id`),
  ADD KEY `idx_semana` (`Semana`),
  ADD KEY `idx_restriccion` (`Restriccion`);

--
-- Indices de la tabla `metrolineaSeis_pi_shared_constraint_links`
--
ALTER TABLE `metrolineaSeis_pi_shared_constraint_links`
  ADD PRIMARY KEY (`Id`),
  ADD KEY `idx_shared` (`SharedConstraintId`),
  ADD KEY `idx_semana_consecutivo` (`Semana`,`ConsecutivoEnPrograma`);

--
-- Indices de la tabla `metrolineaSeis_profesionales`
--
ALTER TABLE `metrolineaSeis_profesionales`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `metrolineaSeis_programa`
--
ALTER TABLE `metrolineaSeis_programa`
  ADD PRIMARY KEY (`Consecutivo`);

--
-- Indices de la tabla `metrolineaSeis_programacion_semanal`
--
ALTER TABLE `metrolineaSeis_programacion_semanal`
  ADD PRIMARY KEY (`Consecutivo`);

--
-- Indices de la tabla `metrolineaSeis_programa_consolidado`
--
ALTER TABLE `metrolineaSeis_programa_consolidado`
  ADD PRIMARY KEY (`Consecutivo`);

--
-- Indices de la tabla `metrolineaSeis_semanas_activas`
--
ALTER TABLE `metrolineaSeis_semanas_activas`
  ADD PRIMARY KEY (`Id`);

--
-- Indices de la tabla `metrolineaSeis_subcontratistas`
--
ALTER TABLE `metrolineaSeis_subcontratistas`
  ADD PRIMARY KEY (`Id`);

--
-- Indices de la tabla `metrolineaUno_actividades`
--
ALTER TABLE `metrolineaUno_actividades`
  ADD PRIMARY KEY (`Id`);

--
-- Indices de la tabla `metrolineaUno_cambios`
--
ALTER TABLE `metrolineaUno_cambios`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `metrolineaUno_cic`
--
ALTER TABLE `metrolineaUno_cic`
  ADD PRIMARY KEY (`Id`);

--
-- Indices de la tabla `metrolineaUno_pdc`
--
ALTER TABLE `metrolineaUno_pdc`
  ADD PRIMARY KEY (`consecutivo`);

--
-- Indices de la tabla `metrolineaUno_pi_shared_constraints`
--
ALTER TABLE `metrolineaUno_pi_shared_constraints`
  ADD PRIMARY KEY (`Id`),
  ADD KEY `idx_semana` (`Semana`),
  ADD KEY `idx_restriccion` (`Restriccion`);

--
-- Indices de la tabla `metrolineaUno_pi_shared_constraint_links`
--
ALTER TABLE `metrolineaUno_pi_shared_constraint_links`
  ADD PRIMARY KEY (`Id`),
  ADD KEY `idx_shared` (`SharedConstraintId`),
  ADD KEY `idx_semana_consecutivo` (`Semana`,`ConsecutivoEnPrograma`);

--
-- Indices de la tabla `metrolineaUno_profesionales`
--
ALTER TABLE `metrolineaUno_profesionales`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `metrolineaUno_programa`
--
ALTER TABLE `metrolineaUno_programa`
  ADD PRIMARY KEY (`Consecutivo`);

--
-- Indices de la tabla `metrolineaUno_programacion_semanal`
--
ALTER TABLE `metrolineaUno_programacion_semanal`
  ADD PRIMARY KEY (`Consecutivo`);

--
-- Indices de la tabla `metrolineaUno_programa_consolidado`
--
ALTER TABLE `metrolineaUno_programa_consolidado`
  ADD PRIMARY KEY (`Consecutivo`);

--
-- Indices de la tabla `metrolineaUno_semanas_activas`
--
ALTER TABLE `metrolineaUno_semanas_activas`
  ADD PRIMARY KEY (`Id`);

--
-- Indices de la tabla `metrolineaUno_subcontratistas`
--
ALTER TABLE `metrolineaUno_subcontratistas`
  ADD PRIMARY KEY (`Id`);

--
-- Indices de la tabla `milanCampestre_actividades`
--
ALTER TABLE `milanCampestre_actividades`
  ADD PRIMARY KEY (`Id`);

--
-- Indices de la tabla `milanCampestre_cambios`
--
ALTER TABLE `milanCampestre_cambios`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `milanCampestre_cic`
--
ALTER TABLE `milanCampestre_cic`
  ADD PRIMARY KEY (`Id`);

--
-- Indices de la tabla `milanCampestre_pdc`
--
ALTER TABLE `milanCampestre_pdc`
  ADD PRIMARY KEY (`consecutivo`);

--
-- Indices de la tabla `milanCampestre_pi_shared_constraints`
--
ALTER TABLE `milanCampestre_pi_shared_constraints`
  ADD PRIMARY KEY (`Id`),
  ADD KEY `idx_semana` (`Semana`),
  ADD KEY `idx_restriccion` (`Restriccion`);

--
-- Indices de la tabla `milanCampestre_pi_shared_constraint_links`
--
ALTER TABLE `milanCampestre_pi_shared_constraint_links`
  ADD PRIMARY KEY (`Id`),
  ADD KEY `idx_shared` (`SharedConstraintId`),
  ADD KEY `idx_semana_consecutivo` (`Semana`,`ConsecutivoEnPrograma`);

--
-- Indices de la tabla `milanCampestre_profesionales`
--
ALTER TABLE `milanCampestre_profesionales`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `milanCampestre_programa`
--
ALTER TABLE `milanCampestre_programa`
  ADD PRIMARY KEY (`Consecutivo`);

--
-- Indices de la tabla `milanCampestre_programacion_semanal`
--
ALTER TABLE `milanCampestre_programacion_semanal`
  ADD PRIMARY KEY (`Consecutivo`);

--
-- Indices de la tabla `milanCampestre_programa_consolidado`
--
ALTER TABLE `milanCampestre_programa_consolidado`
  ADD PRIMARY KEY (`Consecutivo`);

--
-- Indices de la tabla `milanCampestre_semanas_activas`
--
ALTER TABLE `milanCampestre_semanas_activas`
  ADD PRIMARY KEY (`Id`);

--
-- Indices de la tabla `milanCampestre_subcontratistas`
--
ALTER TABLE `milanCampestre_subcontratistas`
  ADD PRIMARY KEY (`Id`);

--
-- Indices de la tabla `milan_campestre_torre_actividades`
--
ALTER TABLE `milan_campestre_torre_actividades`
  ADD PRIMARY KEY (`Id`);

--
-- Indices de la tabla `milan_campestre_torre_cambios`
--
ALTER TABLE `milan_campestre_torre_cambios`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `milan_campestre_torre_cic`
--
ALTER TABLE `milan_campestre_torre_cic`
  ADD PRIMARY KEY (`Id`);

--
-- Indices de la tabla `milan_campestre_torre_pdc`
--
ALTER TABLE `milan_campestre_torre_pdc`
  ADD PRIMARY KEY (`consecutivo`);

--
-- Indices de la tabla `milan_campestre_torre_profesionales`
--
ALTER TABLE `milan_campestre_torre_profesionales`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `milan_campestre_torre_programa`
--
ALTER TABLE `milan_campestre_torre_programa`
  ADD PRIMARY KEY (`Consecutivo`);

--
-- Indices de la tabla `milan_campestre_torre_programacion_semanal`
--
ALTER TABLE `milan_campestre_torre_programacion_semanal`
  ADD PRIMARY KEY (`Consecutivo`);

--
-- Indices de la tabla `milan_campestre_torre_programa_consolidado`
--
ALTER TABLE `milan_campestre_torre_programa_consolidado`
  ADD PRIMARY KEY (`Consecutivo`);

--
-- Indices de la tabla `milan_campestre_torre_semanas_activas`
--
ALTER TABLE `milan_campestre_torre_semanas_activas`
  ADD PRIMARY KEY (`Id`);

--
-- Indices de la tabla `milan_campestre_torre_subcontratistas`
--
ALTER TABLE `milan_campestre_torre_subcontratistas`
  ADD PRIMARY KEY (`Id`);

--
-- Indices de la tabla `notification_types`
--
ALTER TABLE `notification_types`
  ADD PRIMARY KEY (`notification_code`);

--
-- Indices de la tabla `optimizacionJMC_actividades`
--
ALTER TABLE `optimizacionJMC_actividades`
  ADD PRIMARY KEY (`Id`);

--
-- Indices de la tabla `optimizacionJMC_cambios`
--
ALTER TABLE `optimizacionJMC_cambios`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `optimizacionJMC_cic`
--
ALTER TABLE `optimizacionJMC_cic`
  ADD PRIMARY KEY (`Id`);

--
-- Indices de la tabla `optimizacionJMC_pdc`
--
ALTER TABLE `optimizacionJMC_pdc`
  ADD PRIMARY KEY (`consecutivo`);

--
-- Indices de la tabla `optimizacionJMC_pi_shared_constraints`
--
ALTER TABLE `optimizacionJMC_pi_shared_constraints`
  ADD PRIMARY KEY (`Id`),
  ADD KEY `idx_semana` (`Semana`),
  ADD KEY `idx_restriccion` (`Restriccion`);

--
-- Indices de la tabla `optimizacionJMC_pi_shared_constraint_links`
--
ALTER TABLE `optimizacionJMC_pi_shared_constraint_links`
  ADD PRIMARY KEY (`Id`),
  ADD KEY `idx_shared` (`SharedConstraintId`),
  ADD KEY `idx_semana_consecutivo` (`Semana`,`ConsecutivoEnPrograma`);

--
-- Indices de la tabla `optimizacionJMC_profesionales`
--
ALTER TABLE `optimizacionJMC_profesionales`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `optimizacionJMC_programa`
--
ALTER TABLE `optimizacionJMC_programa`
  ADD PRIMARY KEY (`Consecutivo`);

--
-- Indices de la tabla `optimizacionJMC_programacion_semanal`
--
ALTER TABLE `optimizacionJMC_programacion_semanal`
  ADD PRIMARY KEY (`Consecutivo`);

--
-- Indices de la tabla `optimizacionJMC_programa_consolidado`
--
ALTER TABLE `optimizacionJMC_programa_consolidado`
  ADD PRIMARY KEY (`Consecutivo`);

--
-- Indices de la tabla `optimizacionJMC_semanas_activas`
--
ALTER TABLE `optimizacionJMC_semanas_activas`
  ADD PRIMARY KEY (`Id`);

--
-- Indices de la tabla `optimizacionJMC_subcontratistas`
--
ALTER TABLE `optimizacionJMC_subcontratistas`
  ADD PRIMARY KEY (`Id`);

--
-- Indices de la tabla `password_history`
--
ALTER TABLE `password_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_password_history_user` (`user_id`);

--
-- Indices de la tabla `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_password_reset_token_hash` (`token_hash`),
  ADD KEY `idx_password_reset_lookup` (`scope`,`used_at`,`expires_at`),
  ADD KEY `idx_password_reset_user_scope` (`user_id`,`scope`,`used_at`);

--
-- Indices de la tabla `project_members`
--
ALTER TABLE `project_members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_project_user` (`project_id`,`user_id`);

--
-- Indices de la tabla `prueba_actividades`
--
ALTER TABLE `prueba_actividades`
  ADD PRIMARY KEY (`Id`);

--
-- Indices de la tabla `prueba_cambios`
--
ALTER TABLE `prueba_cambios`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `prueba_cic`
--
ALTER TABLE `prueba_cic`
  ADD PRIMARY KEY (`Id`);

--
-- Indices de la tabla `prueba_pdc`
--
ALTER TABLE `prueba_pdc`
  ADD PRIMARY KEY (`consecutivo`);

--
-- Indices de la tabla `prueba_pi_shared_constraints`
--
ALTER TABLE `prueba_pi_shared_constraints`
  ADD PRIMARY KEY (`Id`),
  ADD KEY `idx_semana` (`Semana`),
  ADD KEY `idx_restriccion` (`Restriccion`);

--
-- Indices de la tabla `prueba_pi_shared_constraint_links`
--
ALTER TABLE `prueba_pi_shared_constraint_links`
  ADD PRIMARY KEY (`Id`),
  ADD KEY `idx_shared` (`SharedConstraintId`),
  ADD KEY `idx_semana_consecutivo` (`Semana`,`ConsecutivoEnPrograma`);

--
-- Indices de la tabla `prueba_profesionales`
--
ALTER TABLE `prueba_profesionales`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `prueba_programa`
--
ALTER TABLE `prueba_programa`
  ADD PRIMARY KEY (`Consecutivo`);

--
-- Indices de la tabla `prueba_programacion_semanal`
--
ALTER TABLE `prueba_programacion_semanal`
  ADD PRIMARY KEY (`Consecutivo`);

--
-- Indices de la tabla `prueba_programa_consolidado`
--
ALTER TABLE `prueba_programa_consolidado`
  ADD PRIMARY KEY (`Consecutivo`);

--
-- Indices de la tabla `prueba_semanas_activas`
--
ALTER TABLE `prueba_semanas_activas`
  ADD PRIMARY KEY (`Id`);

--
-- Indices de la tabla `prueba_subcontratistas`
--
ALTER TABLE `prueba_subcontratistas`
  ADD PRIMARY KEY (`Id`);

--
-- Indices de la tabla `rbac_permissions`
--
ALTER TABLE `rbac_permissions`
  ADD PRIMARY KEY (`permission_key`),
  ADD KEY `idx_rbac_permissions_module_action` (`module_name`,`action_name`);

--
-- Indices de la tabla `rbac_roles`
--
ALTER TABLE `rbac_roles`
  ADD PRIMARY KEY (`code`);

--
-- Indices de la tabla `rbac_role_permissions`
--
ALTER TABLE `rbac_role_permissions`
  ADD PRIMARY KEY (`role_code`,`permission_key`),
  ADD KEY `fk_rbac_role_permissions_permission` (`permission_key`);

--
-- Indices de la tabla `role_intelligence`
--
ALTER TABLE `role_intelligence`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cargo_title` (`cargo_title`);

--
-- Indices de la tabla `role_notification_defaults`
--
ALTER TABLE `role_notification_defaults`
  ADD PRIMARY KEY (`role_code`,`notification_code`),
  ADD KEY `fk_role_notification_type` (`notification_code`);

--
-- Indices de la tabla `system_notifications`
--
ALTER TABLE `system_notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_unread_user` (`user_id`,`is_read`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_group_lookup` (`user_id`,`type`,`project_id`,`is_read`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `accesibilidadMetroA_actividades`
--
ALTER TABLE `accesibilidadMetroA_actividades`
  MODIFY `Id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `accesibilidadMetroA_cambios`
--
ALTER TABLE `accesibilidadMetroA_cambios`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `accesibilidadMetroA_cic`
--
ALTER TABLE `accesibilidadMetroA_cic`
  MODIFY `Id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `accesibilidadMetroA_pdc`
--
ALTER TABLE `accesibilidadMetroA_pdc`
  MODIFY `consecutivo` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `accesibilidadMetroA_pi_shared_constraints`
--
ALTER TABLE `accesibilidadMetroA_pi_shared_constraints`
  MODIFY `Id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `accesibilidadMetroA_pi_shared_constraint_links`
--
ALTER TABLE `accesibilidadMetroA_pi_shared_constraint_links`
  MODIFY `Id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `accesibilidadMetroA_profesionales`
--
ALTER TABLE `accesibilidadMetroA_profesionales`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `accesibilidadMetroA_programa`
--
ALTER TABLE `accesibilidadMetroA_programa`
  MODIFY `Consecutivo` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `accesibilidadMetroA_programacion_semanal`
--
ALTER TABLE `accesibilidadMetroA_programacion_semanal`
  MODIFY `Consecutivo` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `accesibilidadMetroA_programa_consolidado`
--
ALTER TABLE `accesibilidadMetroA_programa_consolidado`
  MODIFY `Consecutivo` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `accesibilidadMetroA_semanas_activas`
--
ALTER TABLE `accesibilidadMetroA_semanas_activas`
  MODIFY `Id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `accesibilidadMetroA_subcontratistas`
--
ALTER TABLE `accesibilidadMetroA_subcontratistas`
  MODIFY `Id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `accesibilidadMetroB_actividades`
--
ALTER TABLE `accesibilidadMetroB_actividades`
  MODIFY `Id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `accesibilidadMetroB_cambios`
--
ALTER TABLE `accesibilidadMetroB_cambios`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `accesibilidadMetroB_cic`
--
ALTER TABLE `accesibilidadMetroB_cic`
  MODIFY `Id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `accesibilidadMetroB_pdc`
--
ALTER TABLE `accesibilidadMetroB_pdc`
  MODIFY `consecutivo` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `accesibilidadMetroB_pi_shared_constraints`
--
ALTER TABLE `accesibilidadMetroB_pi_shared_constraints`
  MODIFY `Id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `accesibilidadMetroB_pi_shared_constraint_links`
--
ALTER TABLE `accesibilidadMetroB_pi_shared_constraint_links`
  MODIFY `Id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `accesibilidadMetroB_profesionales`
--
ALTER TABLE `accesibilidadMetroB_profesionales`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `accesibilidadMetroB_programa`
--
ALTER TABLE `accesibilidadMetroB_programa`
  MODIFY `Consecutivo` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `accesibilidadMetroB_programacion_semanal`
--
ALTER TABLE `accesibilidadMetroB_programacion_semanal`
  MODIFY `Consecutivo` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `accesibilidadMetroB_programa_consolidado`
--
ALTER TABLE `accesibilidadMetroB_programa_consolidado`
  MODIFY `Consecutivo` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `accesibilidadMetroB_semanas_activas`
--
ALTER TABLE `accesibilidadMetroB_semanas_activas`
  MODIFY `Id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `accesibilidadMetroB_subcontratistas`
--
ALTER TABLE `accesibilidadMetroB_subcontratistas`
  MODIFY `Id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `da_porto_actividades`
--
ALTER TABLE `da_porto_actividades`
  MODIFY `Id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `da_porto_cambios`
--
ALTER TABLE `da_porto_cambios`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `da_porto_cic`
--
ALTER TABLE `da_porto_cic`
  MODIFY `Id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `da_porto_pdc`
--
ALTER TABLE `da_porto_pdc`
  MODIFY `consecutivo` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `da_porto_profesionales`
--
ALTER TABLE `da_porto_profesionales`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `da_porto_programa`
--
ALTER TABLE `da_porto_programa`
  MODIFY `Consecutivo` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `da_porto_programacion_semanal`
--
ALTER TABLE `da_porto_programacion_semanal`
  MODIFY `Consecutivo` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `da_porto_programa_consolidado`
--
ALTER TABLE `da_porto_programa_consolidado`
  MODIFY `Consecutivo` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `da_porto_semanas_activas`
--
ALTER TABLE `da_porto_semanas_activas`
  MODIFY `Id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `da_porto_subcontratistas`
--
ALTER TABLE `da_porto_subcontratistas`
  MODIFY `Id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `general_auditoria_acciones`
--
ALTER TABLE `general_auditoria_acciones`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `general_cnc`
--
ALTER TABLE `general_cnc`
  MODIFY `Id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `general_codigos_actividades`
--
ALTER TABLE `general_codigos_actividades`
  MODIFY `Id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `general_costos_cuadrillas`
--
ALTER TABLE `general_costos_cuadrillas`
  MODIFY `Id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `general_cuadrillas_tipicas`
--
ALTER TABLE `general_cuadrillas_tipicas`
  MODIFY `Id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `general_curvas`
--
ALTER TABLE `general_curvas`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `general_curvas_pdc`
--
ALTER TABLE `general_curvas_pdc`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `general_curvas_pdc_apr`
--
ALTER TABLE `general_curvas_pdc_apr`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `general_dias_procesos_contratacion`
--
ALTER TABLE `general_dias_procesos_contratacion`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `general_feature_flags`
--
ALTER TABLE `general_feature_flags`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `general_informe_consolidado`
--
ALTER TABLE `general_informe_consolidado`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `general_informe_pdc`
--
ALTER TABLE `general_informe_pdc`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `general_informe_restricciones_consolidado`
--
ALTER TABLE `general_informe_restricciones_consolidado`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `general_informe_subcontratistas`
--
ALTER TABLE `general_informe_subcontratistas`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `general_proyectos_procesos`
--
ALTER TABLE `general_proyectos_procesos`
  MODIFY `Id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `general_usuarios`
--
ALTER TABLE `general_usuarios`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `homecenterMallplaza_actividades`
--
ALTER TABLE `homecenterMallplaza_actividades`
  MODIFY `Id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `homecenterMallplaza_cic`
--
ALTER TABLE `homecenterMallplaza_cic`
  MODIFY `Id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `homecenterMallplaza_pdc`
--
ALTER TABLE `homecenterMallplaza_pdc`
  MODIFY `consecutivo` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `homecenterMallplaza_pi_shared_constraints`
--
ALTER TABLE `homecenterMallplaza_pi_shared_constraints`
  MODIFY `Id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `homecenterMallplaza_pi_shared_constraint_links`
--
ALTER TABLE `homecenterMallplaza_pi_shared_constraint_links`
  MODIFY `Id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `homecenterMallplaza_profesionales`
--
ALTER TABLE `homecenterMallplaza_profesionales`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `homecenterMallplaza_programa`
--
ALTER TABLE `homecenterMallplaza_programa`
  MODIFY `Consecutivo` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `homecenterMallplaza_programacion_semanal`
--
ALTER TABLE `homecenterMallplaza_programacion_semanal`
  MODIFY `Consecutivo` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `homecenterMallplaza_programa_consolidado`
--
ALTER TABLE `homecenterMallplaza_programa_consolidado`
  MODIFY `Consecutivo` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `homecenterMallplaza_semanas_activas`
--
ALTER TABLE `homecenterMallplaza_semanas_activas`
  MODIFY `Id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `laMasia_actividades`
--
ALTER TABLE `laMasia_actividades`
  MODIFY `Id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `laMasia_cic`
--
ALTER TABLE `laMasia_cic`
  MODIFY `Id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `laMasia_pdc`
--
ALTER TABLE `laMasia_pdc`
  MODIFY `consecutivo` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `laMasia_pi_shared_constraints`
--
ALTER TABLE `laMasia_pi_shared_constraints`
  MODIFY `Id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `laMasia_pi_shared_constraint_links`
--
ALTER TABLE `laMasia_pi_shared_constraint_links`
  MODIFY `Id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `laMasia_profesionales`
--
ALTER TABLE `laMasia_profesionales`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `laMasia_programa`
--
ALTER TABLE `laMasia_programa`
  MODIFY `Consecutivo` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `laMasia_programacion_semanal`
--
ALTER TABLE `laMasia_programacion_semanal`
  MODIFY `Consecutivo` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `laMasia_programa_consolidado`
--
ALTER TABLE `laMasia_programa_consolidado`
  MODIFY `Consecutivo` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `laMasia_semanas_activas`
--
ALTER TABLE `laMasia_semanas_activas`
  MODIFY `Id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `laMasia_subcontratistas`
--
ALTER TABLE `laMasia_subcontratistas`
  MODIFY `Id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metrolineaConfinamientoDos_actividades`
--
ALTER TABLE `metrolineaConfinamientoDos_actividades`
  MODIFY `Id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metrolineaConfinamientoDos_cambios`
--
ALTER TABLE `metrolineaConfinamientoDos_cambios`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metrolineaConfinamientoDos_cic`
--
ALTER TABLE `metrolineaConfinamientoDos_cic`
  MODIFY `Id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metrolineaConfinamientoDos_pdc`
--
ALTER TABLE `metrolineaConfinamientoDos_pdc`
  MODIFY `consecutivo` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metrolineaConfinamientoDos_pi_shared_constraints`
--
ALTER TABLE `metrolineaConfinamientoDos_pi_shared_constraints`
  MODIFY `Id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metrolineaConfinamientoDos_pi_shared_constraint_links`
--
ALTER TABLE `metrolineaConfinamientoDos_pi_shared_constraint_links`
  MODIFY `Id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metrolineaConfinamientoDos_profesionales`
--
ALTER TABLE `metrolineaConfinamientoDos_profesionales`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metrolineaConfinamientoDos_programa`
--
ALTER TABLE `metrolineaConfinamientoDos_programa`
  MODIFY `Consecutivo` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metrolineaConfinamientoDos_programacion_semanal`
--
ALTER TABLE `metrolineaConfinamientoDos_programacion_semanal`
  MODIFY `Consecutivo` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metrolineaConfinamientoDos_programa_consolidado`
--
ALTER TABLE `metrolineaConfinamientoDos_programa_consolidado`
  MODIFY `Consecutivo` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metrolineaConfinamientoDos_semanas_activas`
--
ALTER TABLE `metrolineaConfinamientoDos_semanas_activas`
  MODIFY `Id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metrolineaConfinamientoDos_subcontratistas`
--
ALTER TABLE `metrolineaConfinamientoDos_subcontratistas`
  MODIFY `Id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metrolineaDieciseisAscendente_actividades`
--
ALTER TABLE `metrolineaDieciseisAscendente_actividades`
  MODIFY `Id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metrolineaDieciseisAscendente_cambios`
--
ALTER TABLE `metrolineaDieciseisAscendente_cambios`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metrolineaDieciseisAscendente_cic`
--
ALTER TABLE `metrolineaDieciseisAscendente_cic`
  MODIFY `Id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metrolineaDieciseisAscendente_pdc`
--
ALTER TABLE `metrolineaDieciseisAscendente_pdc`
  MODIFY `consecutivo` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metrolineaDieciseisAscendente_pi_shared_constraints`
--
ALTER TABLE `metrolineaDieciseisAscendente_pi_shared_constraints`
  MODIFY `Id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metrolineaDieciseisAscendente_pi_shared_constraint_links`
--
ALTER TABLE `metrolineaDieciseisAscendente_pi_shared_constraint_links`
  MODIFY `Id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metrolineaDieciseisAscendente_profesionales`
--
ALTER TABLE `metrolineaDieciseisAscendente_profesionales`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metrolineaDieciseisAscendente_programa`
--
ALTER TABLE `metrolineaDieciseisAscendente_programa`
  MODIFY `Consecutivo` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metrolineaDieciseisAscendente_programacion_semanal`
--
ALTER TABLE `metrolineaDieciseisAscendente_programacion_semanal`
  MODIFY `Consecutivo` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metrolineaDieciseisAscendente_programa_consolidado`
--
ALTER TABLE `metrolineaDieciseisAscendente_programa_consolidado`
  MODIFY `Consecutivo` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metrolineaDieciseisAscendente_semanas_activas`
--
ALTER TABLE `metrolineaDieciseisAscendente_semanas_activas`
  MODIFY `Id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metrolineaDieciseisAscendente_subcontratistas`
--
ALTER TABLE `metrolineaDieciseisAscendente_subcontratistas`
  MODIFY `Id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metrolineaDieciseisDescendente_actividades`
--
ALTER TABLE `metrolineaDieciseisDescendente_actividades`
  MODIFY `Id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metrolineaDieciseisDescendente_cambios`
--
ALTER TABLE `metrolineaDieciseisDescendente_cambios`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metrolineaDieciseisDescendente_cic`
--
ALTER TABLE `metrolineaDieciseisDescendente_cic`
  MODIFY `Id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metrolineaDieciseisDescendente_pdc`
--
ALTER TABLE `metrolineaDieciseisDescendente_pdc`
  MODIFY `consecutivo` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metrolineaDieciseisDescendente_pi_shared_constraints`
--
ALTER TABLE `metrolineaDieciseisDescendente_pi_shared_constraints`
  MODIFY `Id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metrolineaDieciseisDescendente_pi_shared_constraint_links`
--
ALTER TABLE `metrolineaDieciseisDescendente_pi_shared_constraint_links`
  MODIFY `Id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metrolineaDieciseisDescendente_profesionales`
--
ALTER TABLE `metrolineaDieciseisDescendente_profesionales`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metrolineaDieciseisDescendente_programa`
--
ALTER TABLE `metrolineaDieciseisDescendente_programa`
  MODIFY `Consecutivo` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metrolineaDieciseisDescendente_programacion_semanal`
--
ALTER TABLE `metrolineaDieciseisDescendente_programacion_semanal`
  MODIFY `Consecutivo` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metrolineaDieciseisDescendente_programa_consolidado`
--
ALTER TABLE `metrolineaDieciseisDescendente_programa_consolidado`
  MODIFY `Consecutivo` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metrolineaDieciseisDescendente_semanas_activas`
--
ALTER TABLE `metrolineaDieciseisDescendente_semanas_activas`
  MODIFY `Id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metrolineaDieciseisDescendente_subcontratistas`
--
ALTER TABLE `metrolineaDieciseisDescendente_subcontratistas`
  MODIFY `Id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metrolineaDos_actividades`
--
ALTER TABLE `metrolineaDos_actividades`
  MODIFY `Id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metrolineaDos_cambios`
--
ALTER TABLE `metrolineaDos_cambios`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metrolineaDos_cic`
--
ALTER TABLE `metrolineaDos_cic`
  MODIFY `Id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metrolineaDos_pdc`
--
ALTER TABLE `metrolineaDos_pdc`
  MODIFY `consecutivo` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metrolineaDos_pi_shared_constraints`
--
ALTER TABLE `metrolineaDos_pi_shared_constraints`
  MODIFY `Id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metrolineaDos_pi_shared_constraint_links`
--
ALTER TABLE `metrolineaDos_pi_shared_constraint_links`
  MODIFY `Id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metrolineaDos_profesionales`
--
ALTER TABLE `metrolineaDos_profesionales`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metrolineaDos_programa`
--
ALTER TABLE `metrolineaDos_programa`
  MODIFY `Consecutivo` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metrolineaDos_programacion_semanal`
--
ALTER TABLE `metrolineaDos_programacion_semanal`
  MODIFY `Consecutivo` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metrolineaDos_programa_consolidado`
--
ALTER TABLE `metrolineaDos_programa_consolidado`
  MODIFY `Consecutivo` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metrolineaDos_semanas_activas`
--
ALTER TABLE `metrolineaDos_semanas_activas`
  MODIFY `Id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metrolineaDos_subcontratistas`
--
ALTER TABLE `metrolineaDos_subcontratistas`
  MODIFY `Id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metrolineaMampDos_actividades`
--
ALTER TABLE `metrolineaMampDos_actividades`
  MODIFY `Id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metrolineaMampDos_cambios`
--
ALTER TABLE `metrolineaMampDos_cambios`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metrolineaMampDos_cic`
--
ALTER TABLE `metrolineaMampDos_cic`
  MODIFY `Id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metrolineaMampDos_pdc`
--
ALTER TABLE `metrolineaMampDos_pdc`
  MODIFY `consecutivo` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metrolineaMampDos_pi_shared_constraints`
--
ALTER TABLE `metrolineaMampDos_pi_shared_constraints`
  MODIFY `Id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metrolineaMampDos_pi_shared_constraint_links`
--
ALTER TABLE `metrolineaMampDos_pi_shared_constraint_links`
  MODIFY `Id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metrolineaMampDos_profesionales`
--
ALTER TABLE `metrolineaMampDos_profesionales`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metrolineaMampDos_programa`
--
ALTER TABLE `metrolineaMampDos_programa`
  MODIFY `Consecutivo` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metrolineaMampDos_programacion_semanal`
--
ALTER TABLE `metrolineaMampDos_programacion_semanal`
  MODIFY `Consecutivo` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metrolineaMampDos_programa_consolidado`
--
ALTER TABLE `metrolineaMampDos_programa_consolidado`
  MODIFY `Consecutivo` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metrolineaMampDos_semanas_activas`
--
ALTER TABLE `metrolineaMampDos_semanas_activas`
  MODIFY `Id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metrolineaMampDos_subcontratistas`
--
ALTER TABLE `metrolineaMampDos_subcontratistas`
  MODIFY `Id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metrolineaMampSeis_actividades`
--
ALTER TABLE `metrolineaMampSeis_actividades`
  MODIFY `Id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metrolineaMampSeis_cambios`
--
ALTER TABLE `metrolineaMampSeis_cambios`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metrolineaMampSeis_cic`
--
ALTER TABLE `metrolineaMampSeis_cic`
  MODIFY `Id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metrolineaMampSeis_pdc`
--
ALTER TABLE `metrolineaMampSeis_pdc`
  MODIFY `consecutivo` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metrolineaMampSeis_pi_shared_constraints`
--
ALTER TABLE `metrolineaMampSeis_pi_shared_constraints`
  MODIFY `Id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metrolineaMampSeis_pi_shared_constraint_links`
--
ALTER TABLE `metrolineaMampSeis_pi_shared_constraint_links`
  MODIFY `Id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metrolineaMampSeis_profesionales`
--
ALTER TABLE `metrolineaMampSeis_profesionales`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metrolineaMampSeis_programa`
--
ALTER TABLE `metrolineaMampSeis_programa`
  MODIFY `Consecutivo` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metrolineaMampSeis_programacion_semanal`
--
ALTER TABLE `metrolineaMampSeis_programacion_semanal`
  MODIFY `Consecutivo` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metrolineaMampSeis_programa_consolidado`
--
ALTER TABLE `metrolineaMampSeis_programa_consolidado`
  MODIFY `Consecutivo` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metrolineaMampSeis_semanas_activas`
--
ALTER TABLE `metrolineaMampSeis_semanas_activas`
  MODIFY `Id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metrolineaMampSeis_subcontratistas`
--
ALTER TABLE `metrolineaMampSeis_subcontratistas`
  MODIFY `Id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metrolineaMampUno_actividades`
--
ALTER TABLE `metrolineaMampUno_actividades`
  MODIFY `Id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metrolineaMampUno_cambios`
--
ALTER TABLE `metrolineaMampUno_cambios`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metrolineaMampUno_cic`
--
ALTER TABLE `metrolineaMampUno_cic`
  MODIFY `Id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metrolineaMampUno_pdc`
--
ALTER TABLE `metrolineaMampUno_pdc`
  MODIFY `consecutivo` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metrolineaMampUno_pi_shared_constraints`
--
ALTER TABLE `metrolineaMampUno_pi_shared_constraints`
  MODIFY `Id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metrolineaMampUno_pi_shared_constraint_links`
--
ALTER TABLE `metrolineaMampUno_pi_shared_constraint_links`
  MODIFY `Id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metrolineaMampUno_profesionales`
--
ALTER TABLE `metrolineaMampUno_profesionales`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metrolineaMampUno_programa`
--
ALTER TABLE `metrolineaMampUno_programa`
  MODIFY `Consecutivo` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metrolineaMampUno_programacion_semanal`
--
ALTER TABLE `metrolineaMampUno_programacion_semanal`
  MODIFY `Consecutivo` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metrolineaMampUno_programa_consolidado`
--
ALTER TABLE `metrolineaMampUno_programa_consolidado`
  MODIFY `Consecutivo` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metrolineaMampUno_semanas_activas`
--
ALTER TABLE `metrolineaMampUno_semanas_activas`
  MODIFY `Id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metrolineaMampUno_subcontratistas`
--
ALTER TABLE `metrolineaMampUno_subcontratistas`
  MODIFY `Id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metrolineaMurosDos_actividades`
--
ALTER TABLE `metrolineaMurosDos_actividades`
  MODIFY `Id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metrolineaMurosDos_cambios`
--
ALTER TABLE `metrolineaMurosDos_cambios`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metrolineaMurosDos_cic`
--
ALTER TABLE `metrolineaMurosDos_cic`
  MODIFY `Id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metrolineaMurosDos_pdc`
--
ALTER TABLE `metrolineaMurosDos_pdc`
  MODIFY `consecutivo` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metrolineaMurosDos_profesionales`
--
ALTER TABLE `metrolineaMurosDos_profesionales`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metrolineaMurosDos_programa`
--
ALTER TABLE `metrolineaMurosDos_programa`
  MODIFY `Consecutivo` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metrolineaMurosDos_programacion_semanal`
--
ALTER TABLE `metrolineaMurosDos_programacion_semanal`
  MODIFY `Consecutivo` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metrolineaMurosDos_programa_consolidado`
--
ALTER TABLE `metrolineaMurosDos_programa_consolidado`
  MODIFY `Consecutivo` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metrolineaMurosDos_semanas_activas`
--
ALTER TABLE `metrolineaMurosDos_semanas_activas`
  MODIFY `Id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metrolineaMurosDos_subcontratistas`
--
ALTER TABLE `metrolineaMurosDos_subcontratistas`
  MODIFY `Id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metrolineaSeis_actividades`
--
ALTER TABLE `metrolineaSeis_actividades`
  MODIFY `Id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metrolineaSeis_cambios`
--
ALTER TABLE `metrolineaSeis_cambios`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metrolineaSeis_cic`
--
ALTER TABLE `metrolineaSeis_cic`
  MODIFY `Id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metrolineaSeis_pdc`
--
ALTER TABLE `metrolineaSeis_pdc`
  MODIFY `consecutivo` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metrolineaSeis_pi_shared_constraints`
--
ALTER TABLE `metrolineaSeis_pi_shared_constraints`
  MODIFY `Id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metrolineaSeis_pi_shared_constraint_links`
--
ALTER TABLE `metrolineaSeis_pi_shared_constraint_links`
  MODIFY `Id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metrolineaSeis_profesionales`
--
ALTER TABLE `metrolineaSeis_profesionales`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metrolineaSeis_programa`
--
ALTER TABLE `metrolineaSeis_programa`
  MODIFY `Consecutivo` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metrolineaSeis_programacion_semanal`
--
ALTER TABLE `metrolineaSeis_programacion_semanal`
  MODIFY `Consecutivo` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metrolineaSeis_programa_consolidado`
--
ALTER TABLE `metrolineaSeis_programa_consolidado`
  MODIFY `Consecutivo` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metrolineaSeis_semanas_activas`
--
ALTER TABLE `metrolineaSeis_semanas_activas`
  MODIFY `Id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metrolineaSeis_subcontratistas`
--
ALTER TABLE `metrolineaSeis_subcontratistas`
  MODIFY `Id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metrolineaUno_actividades`
--
ALTER TABLE `metrolineaUno_actividades`
  MODIFY `Id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metrolineaUno_cambios`
--
ALTER TABLE `metrolineaUno_cambios`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metrolineaUno_cic`
--
ALTER TABLE `metrolineaUno_cic`
  MODIFY `Id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metrolineaUno_pdc`
--
ALTER TABLE `metrolineaUno_pdc`
  MODIFY `consecutivo` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metrolineaUno_pi_shared_constraints`
--
ALTER TABLE `metrolineaUno_pi_shared_constraints`
  MODIFY `Id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metrolineaUno_pi_shared_constraint_links`
--
ALTER TABLE `metrolineaUno_pi_shared_constraint_links`
  MODIFY `Id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metrolineaUno_profesionales`
--
ALTER TABLE `metrolineaUno_profesionales`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metrolineaUno_programa`
--
ALTER TABLE `metrolineaUno_programa`
  MODIFY `Consecutivo` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metrolineaUno_programacion_semanal`
--
ALTER TABLE `metrolineaUno_programacion_semanal`
  MODIFY `Consecutivo` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metrolineaUno_programa_consolidado`
--
ALTER TABLE `metrolineaUno_programa_consolidado`
  MODIFY `Consecutivo` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metrolineaUno_semanas_activas`
--
ALTER TABLE `metrolineaUno_semanas_activas`
  MODIFY `Id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metrolineaUno_subcontratistas`
--
ALTER TABLE `metrolineaUno_subcontratistas`
  MODIFY `Id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `milanCampestre_actividades`
--
ALTER TABLE `milanCampestre_actividades`
  MODIFY `Id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `milanCampestre_cambios`
--
ALTER TABLE `milanCampestre_cambios`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `milanCampestre_cic`
--
ALTER TABLE `milanCampestre_cic`
  MODIFY `Id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `milanCampestre_pdc`
--
ALTER TABLE `milanCampestre_pdc`
  MODIFY `consecutivo` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `milanCampestre_pi_shared_constraints`
--
ALTER TABLE `milanCampestre_pi_shared_constraints`
  MODIFY `Id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `milanCampestre_pi_shared_constraint_links`
--
ALTER TABLE `milanCampestre_pi_shared_constraint_links`
  MODIFY `Id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `milanCampestre_profesionales`
--
ALTER TABLE `milanCampestre_profesionales`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `milanCampestre_programa`
--
ALTER TABLE `milanCampestre_programa`
  MODIFY `Consecutivo` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `milanCampestre_programacion_semanal`
--
ALTER TABLE `milanCampestre_programacion_semanal`
  MODIFY `Consecutivo` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `milanCampestre_programa_consolidado`
--
ALTER TABLE `milanCampestre_programa_consolidado`
  MODIFY `Consecutivo` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `milanCampestre_semanas_activas`
--
ALTER TABLE `milanCampestre_semanas_activas`
  MODIFY `Id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `milanCampestre_subcontratistas`
--
ALTER TABLE `milanCampestre_subcontratistas`
  MODIFY `Id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `milan_campestre_torre_actividades`
--
ALTER TABLE `milan_campestre_torre_actividades`
  MODIFY `Id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `milan_campestre_torre_cambios`
--
ALTER TABLE `milan_campestre_torre_cambios`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `milan_campestre_torre_cic`
--
ALTER TABLE `milan_campestre_torre_cic`
  MODIFY `Id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `milan_campestre_torre_pdc`
--
ALTER TABLE `milan_campestre_torre_pdc`
  MODIFY `consecutivo` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `milan_campestre_torre_profesionales`
--
ALTER TABLE `milan_campestre_torre_profesionales`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `milan_campestre_torre_programa`
--
ALTER TABLE `milan_campestre_torre_programa`
  MODIFY `Consecutivo` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `milan_campestre_torre_programacion_semanal`
--
ALTER TABLE `milan_campestre_torre_programacion_semanal`
  MODIFY `Consecutivo` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `milan_campestre_torre_programa_consolidado`
--
ALTER TABLE `milan_campestre_torre_programa_consolidado`
  MODIFY `Consecutivo` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `milan_campestre_torre_semanas_activas`
--
ALTER TABLE `milan_campestre_torre_semanas_activas`
  MODIFY `Id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `milan_campestre_torre_subcontratistas`
--
ALTER TABLE `milan_campestre_torre_subcontratistas`
  MODIFY `Id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `optimizacionJMC_actividades`
--
ALTER TABLE `optimizacionJMC_actividades`
  MODIFY `Id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `optimizacionJMC_cambios`
--
ALTER TABLE `optimizacionJMC_cambios`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `optimizacionJMC_cic`
--
ALTER TABLE `optimizacionJMC_cic`
  MODIFY `Id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `optimizacionJMC_pdc`
--
ALTER TABLE `optimizacionJMC_pdc`
  MODIFY `consecutivo` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `optimizacionJMC_pi_shared_constraints`
--
ALTER TABLE `optimizacionJMC_pi_shared_constraints`
  MODIFY `Id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `optimizacionJMC_pi_shared_constraint_links`
--
ALTER TABLE `optimizacionJMC_pi_shared_constraint_links`
  MODIFY `Id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `optimizacionJMC_profesionales`
--
ALTER TABLE `optimizacionJMC_profesionales`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `optimizacionJMC_programa`
--
ALTER TABLE `optimizacionJMC_programa`
  MODIFY `Consecutivo` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `optimizacionJMC_programacion_semanal`
--
ALTER TABLE `optimizacionJMC_programacion_semanal`
  MODIFY `Consecutivo` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `optimizacionJMC_programa_consolidado`
--
ALTER TABLE `optimizacionJMC_programa_consolidado`
  MODIFY `Consecutivo` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `optimizacionJMC_semanas_activas`
--
ALTER TABLE `optimizacionJMC_semanas_activas`
  MODIFY `Id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `optimizacionJMC_subcontratistas`
--
ALTER TABLE `optimizacionJMC_subcontratistas`
  MODIFY `Id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `password_history`
--
ALTER TABLE `password_history`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `project_members`
--
ALTER TABLE `project_members`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `prueba_actividades`
--
ALTER TABLE `prueba_actividades`
  MODIFY `Id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `prueba_cambios`
--
ALTER TABLE `prueba_cambios`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `prueba_cic`
--
ALTER TABLE `prueba_cic`
  MODIFY `Id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `prueba_pdc`
--
ALTER TABLE `prueba_pdc`
  MODIFY `consecutivo` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `prueba_pi_shared_constraints`
--
ALTER TABLE `prueba_pi_shared_constraints`
  MODIFY `Id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `prueba_pi_shared_constraint_links`
--
ALTER TABLE `prueba_pi_shared_constraint_links`
  MODIFY `Id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `prueba_profesionales`
--
ALTER TABLE `prueba_profesionales`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `prueba_programa`
--
ALTER TABLE `prueba_programa`
  MODIFY `Consecutivo` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `prueba_programacion_semanal`
--
ALTER TABLE `prueba_programacion_semanal`
  MODIFY `Consecutivo` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `prueba_programa_consolidado`
--
ALTER TABLE `prueba_programa_consolidado`
  MODIFY `Consecutivo` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `prueba_semanas_activas`
--
ALTER TABLE `prueba_semanas_activas`
  MODIFY `Id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `prueba_subcontratistas`
--
ALTER TABLE `prueba_subcontratistas`
  MODIFY `Id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `role_intelligence`
--
ALTER TABLE `role_intelligence`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `system_notifications`
--
ALTER TABLE `system_notifications`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `event_dictionary`
--
ALTER TABLE `event_dictionary`
  ADD CONSTRAINT `fk_event_dictionary_notification_type` FOREIGN KEY (`notification_code`) REFERENCES `notification_types` (`notification_code`);

--
-- Filtros para la tabla `rbac_role_permissions`
--
ALTER TABLE `rbac_role_permissions`
  ADD CONSTRAINT `fk_rbac_role_permissions_permission` FOREIGN KEY (`permission_key`) REFERENCES `rbac_permissions` (`permission_key`),
  ADD CONSTRAINT `fk_rbac_role_permissions_role` FOREIGN KEY (`role_code`) REFERENCES `rbac_roles` (`code`);

--
-- Filtros para la tabla `role_notification_defaults`
--
ALTER TABLE `role_notification_defaults`
  ADD CONSTRAINT `fk_role_notification_role` FOREIGN KEY (`role_code`) REFERENCES `rbac_roles` (`code`),
  ADD CONSTRAINT `fk_role_notification_type` FOREIGN KEY (`notification_code`) REFERENCES `notification_types` (`notification_code`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
