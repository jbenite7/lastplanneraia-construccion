-- =====================================================================
-- SEED: Proyecto Pre-Construccion - Aeropuerto Regional
-- FECHA: 2026-06-24 (Updated 2026-06-25: 16 standard tables + PC columns)
-- BASE DE DATOS: lastplanneraia_dev
--
-- Politica:
-- - INSERT IGNORE para idempotencia (re-ejecutable sin duplicar).
-- - No elimina datos ni tablas existentes.
-- - No modifica proyectos existentes.
-- - Crea tablas IF NOT EXISTS.
--
-- Uso:
--   docker compose exec -T db sh -lc 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE"' \
--     < database/seeds/preconstruccion_seed.sql
--
-- Verificacion post-ejecucion:
--   docker compose exec -T db sh -lc \
--     'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE" -e "$1"' _ \
--     "SELECT Id, Proyecto_Proceso, Base_de_Datos, Area FROM general_proyectos_procesos WHERE Area='Pre-Construccion'"
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================================
-- PARTE 1: Registrar proyecto en general_proyectos_procesos
-- =====================================================================

INSERT IGNORE INTO `general_proyectos_procesos` (
    `Proyecto_Proceso`,
    `Base_de_Datos`,
    `Area`,
    `pc_restr_2_nombre`,
    `pc_restr_3_nombre`,
    `pc_restr_4_nombre`,
    `Activo`,
    `Acceso`,
    `pdcActivo`,
    `fechaInicioLineaBase`,
    `fechaFinLineaBase`,
    `costoDiaRetraso`,
    `urlCambios`
) VALUES (
    'Aeropuerto Regional PC',
    'da_aeropuerto_pc',
    'Pre-Construccion',
    'Permisos Ambientales',
    'Disenos',
    'Apropiacion Presupuestal',
    1,
    1,
    0,
    '2026-07-01',
    '2026-12-31',
    8000000,
    NULL
);

-- =====================================================================
-- PARTE 2: Crear las 16 tablas estandar + columnas PC
-- =====================================================================
-- Las columnas PC (restriccion_pc_*, Reprogramada_Por_Usuario, etc.)
-- se incluyen directamente en CREATE TABLE para simplicidad.
-- CREATE TABLE IF NOT EXISTS garantiza idempotencia.

-- -----------------------------------------------
-- 2.1 da_aeropuerto_pc_actividades
-- -----------------------------------------------
CREATE TABLE IF NOT EXISTS `da_aeropuerto_pc_actividades` (
  `Id` int(4) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `codigo` int(4) NOT NULL,
  `actividad` varchar(300) NOT NULL,
  `descripcionActividad` mediumtext DEFAULT NULL,
  `actividadInicio` varchar(500) DEFAULT NULL,
  `nombreActividadInicio` varchar(500) DEFAULT NULL,
  `fechaInicio` date DEFAULT NULL,
  `tipoContrato` varchar(10) DEFAULT NULL,
  `semanaActualizacion` int(11) DEFAULT NULL,
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
  `paqueteMO5` varchar(200) DEFAULT NULL,
  `OC1` varchar(200) DEFAULT NULL,
  `paqueteOC1` varchar(200) DEFAULT NULL,
  `OC2` varchar(200) DEFAULT NULL,
  `paqueteOC2` varchar(200) DEFAULT NULL,
  `OC3` varchar(200) DEFAULT NULL,
  `paqueteOC3` varchar(200) DEFAULT NULL,
  `OC4` varchar(200) DEFAULT NULL,
  `paqueteOC4` varchar(200) DEFAULT NULL,
  `OC5` varchar(200) DEFAULT NULL,
  `paqueteOC5` varchar(200) DEFAULT NULL,
  `numeroSubcontratos` tinyint NOT NULL DEFAULT 1,
  `confianza_deteccion` decimal(5,2) DEFAULT NULL,
  `ultimo_auto_definir` datetime DEFAULT NULL,
  `fechaInicioProyectada` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- -----------------------------------------------
-- 2.2 da_aeropuerto_pc_auto_program_log
-- -----------------------------------------------
CREATE TABLE IF NOT EXISTS `da_aeropuerto_pc_auto_program_log` (
    `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `semana` INT NOT NULL,
    `consecutivo` INT NOT NULL,
    `accion` ENUM('comprometer','descomprometer','insert_cnp') NOT NULL,
    `detalle` TEXT,
    `categoria_cnp` VARCHAR(100) DEFAULT NULL,
    `cnp` VARCHAR(100) DEFAULT NULL,
    `creado_en` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_semana` (`semana`),
    KEY `idx_consecutivo` (`consecutivo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------
-- 2.3 da_aeropuerto_pc_cambios
-- -----------------------------------------------
CREATE TABLE IF NOT EXISTS `da_aeropuerto_pc_cambios` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `solicitanteCambio` int(11) DEFAULT NULL,
  `detalleSolicitanteOtro` longtext DEFAULT NULL,
  `fechaSolicitud` date DEFAULT NULL,
  `prioridad` int(11) DEFAULT NULL,
  `tipoCambio` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `responsableSolucion` int(11) DEFAULT NULL,
  `detalleResponsableSolucion` longtext DEFAULT NULL,
  `justificacion` longtext DEFAULT NULL,
  `descripcion` longtext DEFAULT NULL,
  `incidenciaAlcance` longtext DEFAULT NULL,
  `tiempoCronograma` float DEFAULT NULL,
  `tiempoCronogramaAfectado` float DEFAULT NULL,
  `incidenciaCronograma` longtext DEFAULT NULL,
  `valorPresupuesto` float DEFAULT NULL,
  `costoDirecto` float DEFAULT NULL,
  `costoDirectoAIU` float DEFAULT NULL,
  `costoDirectoAIUIVA` float DEFAULT NULL,
  `valorAprobado` float DEFAULT NULL,
  `incidenciaPresupuesto` longtext DEFAULT NULL,
  `incidenciaCalidad` longtext DEFAULT NULL,
  `incidenciaRiesgo` longtext DEFAULT NULL,
  `incidenciaRecurso` longtext DEFAULT NULL,
  `fechaTentativaDefinicion` date DEFAULT NULL,
  `fechaEntregaInterventoria` date DEFAULT NULL,
  `Observaciones` longtext DEFAULT NULL,
  `fechaDefinicion` date DEFAULT NULL,
  `aprobacion` int(11) DEFAULT NULL,
  `soportes` longtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- -----------------------------------------------
-- 2.4 da_aeropuerto_pc_cic
-- -----------------------------------------------
CREATE TABLE IF NOT EXISTS `da_aeropuerto_pc_cic` (
  `Id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `Semana` int(3) DEFAULT NULL,
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
  `Observaciones` mediumtext DEFAULT NULL,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- -----------------------------------------------
-- 2.5 da_aeropuerto_pc_lps_escalamientos
-- -----------------------------------------------
CREATE TABLE IF NOT EXISTS `da_aeropuerto_pc_lps_escalamientos` (
    `id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `proyecto_id` int NOT NULL,
    `semana` int NOT NULL,
    `consecutivo_en_programa` int NOT NULL,
    `modulo` ENUM('PG','PI','PS') NOT NULL,
    `trigger_origen` varchar(50) NOT NULL,
    `nivel_actual` tinyint NOT NULL DEFAULT 1,
    `estado` ENUM('Activo','Mitigado','Cerrado') NOT NULL DEFAULT 'Activo',
    `fecha_detonacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `fecha_ultimo_escalamiento` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    `fecha_cierre` timestamp NULL DEFAULT NULL,
    `usuario_cierre_id` int DEFAULT NULL,
    `justificacion_cierre` mediumtext,
    KEY `idx_semana_consecutivo` (`semana`,`consecutivo_en_programa`),
    KEY `idx_estado_nivel` (`estado`,`nivel_actual`),
    KEY `idx_proyecto` (`proyecto_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------
-- 2.6 da_aeropuerto_pc_lps_drawer_comentarios
-- -----------------------------------------------
CREATE TABLE IF NOT EXISTS `da_aeropuerto_pc_lps_drawer_comentarios` (
    `id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `proyecto_id` int NOT NULL,
    `consecutivo_en_programa` int NOT NULL,
    `semana` int NOT NULL,
    `usuario_id` int NOT NULL,
    `comentario` mediumtext NOT NULL,
    `escalamiento_id` int DEFAULT NULL,
    `parent_id` int DEFAULT NULL,
    `menciones` json DEFAULT NULL,
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_comentario_actividad` (`consecutivo_en_programa`,`semana`),
    KEY `idx_parent` (`parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------
-- 2.7 da_aeropuerto_pc_pdc
-- -----------------------------------------------
CREATE TABLE IF NOT EXISTS `da_aeropuerto_pc_pdc` (
  `consecutivo` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `semana` int(11) NOT NULL,
  `titulo` int(1) NOT NULL,
  `tipoPaquete` varchar(200) NOT NULL,
  `paqueteContratacion` varchar(200) DEFAULT NULL,
  `contratos` varchar(200) DEFAULT NULL,
  `numeroSubcontratos` int(11) DEFAULT 1,
  `subcontratoPaquete` int(11) NOT NULL DEFAULT 1,
  `estado` varchar(200) DEFAULT NULL,
  `fechaElaboracionPliegos` date DEFAULT NULL,
  `diasElaboracionPliegos` int(11) DEFAULT NULL,
  `fechaRealElaboracionPliegos` date DEFAULT NULL,
  `fechaEntregaPliegos` date DEFAULT NULL,
  `diasEntregaPliegos` int(11) DEFAULT NULL,
  `fechaRealEntregaPliegos` date DEFAULT NULL,
  `fechaReciboPropuestas` date DEFAULT NULL,
  `diasReciboPropuestas` int(11) DEFAULT NULL,
  `fechaRealReciboPropuestas` date DEFAULT NULL,
  `fechaCuadrosComparativos` date DEFAULT NULL,
  `diasCuadrosComparativos` int(11) DEFAULT NULL,
  `fechaRealCuadrosComparativos` date DEFAULT NULL,
  `fechaLegalizacionContrato` date DEFAULT NULL,
  `diasLegalizacionContrato` int(11) DEFAULT NULL,
  `fechaRealLegalizacionContrato` date DEFAULT NULL,
  `fechaFabricacion` date DEFAULT NULL,
  `diasFabricacion` int(11) DEFAULT NULL,
  `fechaRealFabricacion` date DEFAULT NULL,
  `fechaInsumosObra` date DEFAULT NULL,
  `diasInsumosObra` int(11) DEFAULT NULL,
  `fechaRealInsumosObra` date DEFAULT NULL,
  `fechaInicio` date DEFAULT NULL,
  `fechaInicioProyectada` date DEFAULT NULL,
  `fechaRealInicio` date DEFAULT NULL,
  `idProveedorAdjudicado` int(11) DEFAULT NULL,
  `numeroContrato` varchar(50) DEFAULT NULL,
  `aplicaPolizas` int(1) NOT NULL DEFAULT 1,
  `fechaVencimientoPolizas` date DEFAULT NULL,
  `valorPresupuesto` float DEFAULT NULL,
  `valorPrimeraNegociacion` float DEFAULT NULL,
  `valorAdjudicado` float DEFAULT NULL,
  `valorAnticipo` float DEFAULT NULL,
  `valorReclamado` float DEFAULT NULL,
  `valorDevoluciones` float DEFAULT NULL,
  `observacionesContrato` mediumtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- -----------------------------------------------
-- 2.8 da_aeropuerto_pc_pg_tracking
-- -----------------------------------------------
CREATE TABLE IF NOT EXISTS `da_aeropuerto_pc_pg_tracking` (
    `consecutivo_en_programa` INT NOT NULL,
    `semana` INT NOT NULL,
    `fecha_inicio` DATE DEFAULT NULL,
    `fecha_fin` DATE DEFAULT NULL,
    `estado` VARCHAR(100) DEFAULT NULL,
    `restricciones_hash` CHAR(32) DEFAULT NULL,
    `fechas_hash` CHAR(32) DEFAULT NULL,
    `estado_hash` CHAR(32) DEFAULT NULL,
    `titulo` TINYINT(1) NOT NULL DEFAULT 0,
    `ultimo_detectado` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`consecutivo_en_programa`, `semana`),
    KEY `idx_semana` (`semana`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------
-- 2.9 da_aeropuerto_pc_profesionales
-- -----------------------------------------------
CREATE TABLE IF NOT EXISTS `da_aeropuerto_pc_profesionales` (
  `id` int(3) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `nombre` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `cargo` varchar(100) NOT NULL,
  `activo` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- -----------------------------------------------
-- 2.10 da_aeropuerto_pc_programa (con columnas PC incluidas)
-- -----------------------------------------------
CREATE TABLE IF NOT EXISTS `da_aeropuerto_pc_programa` (
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
    `restriccion_pc_1` VARCHAR(10) DEFAULT '0%',
    `restriccion_pc_2` VARCHAR(10) DEFAULT '0%',
    `restriccion_pc_3` VARCHAR(10) DEFAULT '0%',
    `restriccion_pc_4` VARCHAR(10) DEFAULT '0%',
    `Responsable_AIA` varchar(100) DEFAULT NULL,
    `Observaciones` mediumtext DEFAULT NULL,
    `Ult_Act_Est` date DEFAULT NULL,
    `Ult_Act_Restr` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- -----------------------------------------------
-- 2.11 da_aeropuerto_pc_programa_consolidado (con columnas PC incluidas)
-- -----------------------------------------------
CREATE TABLE IF NOT EXISTS `da_aeropuerto_pc_programa_consolidado` (
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
    `Estado` varchar(100) DEFAULT NULL,
    `Semanas_Inicio` int(10) DEFAULT 0,
    `Estado_Restricciones` float NOT NULL DEFAULT 0,
    `D_y_E` varchar(9) NOT NULL DEFAULT '0',
    `Materiales` varchar(9) NOT NULL DEFAULT '0',
    `MdeO` varchar(9) NOT NULL DEFAULT '0',
    `Equipos` varchar(9) NOT NULL DEFAULT '0',
    `Predecesora` varchar(9) NOT NULL DEFAULT '0',
    `Pdto_Cons` varchar(9) NOT NULL DEFAULT '0',
    `Modelo` varchar(9) NOT NULL DEFAULT '0',
    `restriccion_pc_1` VARCHAR(10) DEFAULT '0%',
    `restriccion_pc_2` VARCHAR(10) DEFAULT '0%',
    `restriccion_pc_3` VARCHAR(10) DEFAULT '0%',
    `restriccion_pc_4` VARCHAR(10) DEFAULT '0%',
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
    `unidad` varchar(20) DEFAULT NULL,
    `programaAnteriorAsociar` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- -----------------------------------------------
-- 2.12 da_aeropuerto_pc_programacion_semanal (con columnas PC incluidas)
-- -----------------------------------------------
CREATE TABLE IF NOT EXISTS `da_aeropuerto_pc_programacion_semanal` (
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
  `Cantidad_Sugerida` float DEFAULT NULL,
  `Compromiso` float DEFAULT NULL,
  `Ejecutado_Real` float DEFAULT NULL,
  `P_Completado` float DEFAULT NULL,
  `PAC` int(1) DEFAULT NULL,
  `Critica` int(1) DEFAULT NULL,
  `Atrasada` int(1) DEFAULT NULL,
  `Activa` varchar(3) DEFAULT NULL,
  `Reprogramada_Por_Usuario` TINYINT(1) NOT NULL DEFAULT 0,
  `Es_TNP` TINYINT(1) NOT NULL DEFAULT 0,
  `Categoria_CP` VARCHAR(100) DEFAULT NULL,
  `CP` VARCHAR(255) DEFAULT NULL,
  `Observaciones_CP` TEXT DEFAULT NULL,
  `Prog_Sin_Restricciones_100` int(1) DEFAULT NULL,
  `Categoria_CNP` varchar(100) DEFAULT NULL,
  `CNP` varchar(100) DEFAULT NULL,
  `Observaciones_CNP` mediumtext DEFAULT NULL,
  `Categoria_CNC` varchar(100) DEFAULT NULL,
  `CNC` varchar(100) DEFAULT NULL,
  `Observaciones_CNC` mediumtext DEFAULT NULL,
  `Rendimientos` varchar(500) DEFAULT NULL,
  `codigo_actividad` varchar(11) DEFAULT NULL,
  `alerta_crisis` TINYINT(1) NOT NULL DEFAULT 0,
  `reprogramaciones_semanales` INT NOT NULL DEFAULT 0,
  INDEX `idx_crisis_semanal` (`Semana`, `alerta_crisis`),
  INDEX `idx_consecutivo_semanal` (`Consecutivo_En_Programa`, `Semana`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- -----------------------------------------------
-- 2.13 da_aeropuerto_pc_pi_shared_constraints
-- -----------------------------------------------
CREATE TABLE IF NOT EXISTS `da_aeropuerto_pc_pi_shared_constraints` (
    `Id` bigint unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `Semana` int NOT NULL,
    `Restriccion` varchar(40) NOT NULL,
    `ValorObjetivo` varchar(20) NOT NULL,
    `Nota` text,
    `CreadoPor` varchar(120) DEFAULT NULL,
    `CreadoEn` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `ActualizadoEn` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_semana` (`Semana`),
    KEY `idx_restriccion` (`Restriccion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------
-- 2.14 da_aeropuerto_pc_pi_shared_constraint_links
-- -----------------------------------------------
CREATE TABLE IF NOT EXISTS `da_aeropuerto_pc_pi_shared_constraint_links` (
    `Id` bigint unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `SharedConstraintId` bigint unsigned NOT NULL,
    `Semana` int NOT NULL,
    `ConsecutivoEnPrograma` varchar(64) NOT NULL,
    `ValorAplicado` varchar(20) NOT NULL,
    `OverrideLocal` tinyint(1) NOT NULL DEFAULT 0,
    `AplicadoEn` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_shared` (`SharedConstraintId`),
    KEY `idx_semana_consecutivo` (`Semana`,`ConsecutivoEnPrograma`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------
-- 2.15 da_aeropuerto_pc_semanas_activas (con columna PC)
-- -----------------------------------------------
CREATE TABLE IF NOT EXISTS `da_aeropuerto_pc_semanas_activas` (
  `Id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `Semana` int(11) NOT NULL,
  `Fecha_Inicio_Sem` date NOT NULL,
  `Fecha_Fin_Sem` date NOT NULL,
  `Semanal_Confirmada` int(1) DEFAULT 0,
  `fechaCierreCompromisos` date DEFAULT NULL,
  `fecha_ultimo_saneo` DATETIME NULL DEFAULT NULL,
  `fechaCreacionSemana` date DEFAULT NULL,
  `reprogramacion` int(11) NOT NULL DEFAULT 0,
  `diferenciaEstructuraCron` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- -----------------------------------------------
-- 2.16 da_aeropuerto_pc_subcontratistas
-- -----------------------------------------------
CREATE TABLE IF NOT EXISTS `da_aeropuerto_pc_subcontratistas` (
  `Id` int(3) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `subcontratista` varchar(200) NOT NULL,
  `correo_contacto` varchar(200) NOT NULL,
  `NIT` bigint(10) NOT NULL,
  `alcance` varchar(200) NOT NULL,
  `tipo_proveedor` varchar(200) NOT NULL,
  `activo` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- =====================================================================
-- PARTE 3: Datos semilla - Actividades del programa
-- =====================================================================
-- 20 actividades de pre-construccion para un aeropuerto regional.
-- Fechas: julio 2026 - diciembre 2026 (6 meses).

INSERT IGNORE INTO `da_aeropuerto_pc_programa` (
    `Id`, `Actividad`, `Titulo`, `Fecha_Inicio`, `Fecha_Fin`,
    `Ruta_Critica`, `Ejecutado`, `Estado`, `Semanas_Inicio`,
    `Estado_Restricciones`, `D_y_E`, `Materiales`, `MdeO`, `Equipos`,
    `Predecesora`, `Pdto_Cons`, `Modelo`, `restriccion_pc_1`,
    `restriccion_pc_2`, `restriccion_pc_3`, `restriccion_pc_4`,
    `Responsable_AIA`, `Observaciones`
) VALUES
('1', 'Estudios Topograficos', 1, '2026-07-01', '2026-07-14', 1, 0, 'En Ejecucion', 0, 100, 100, 100, 100, 100, 0, 100, '0', '100%', '100%', '100%', '100%', 'Ing. Carlos Mendez', 'Levantamiento topografico completo del predio'),
('2', 'Estudios Geotecnicos', 1, '2026-07-08', '2026-07-28', 1, 0, 'En Ejecucion', 0, 100, 100, 100, 100, 100, 1, 100, '0', '100%', '100%', '100%', '100%', 'Ing. Carlos Mendez', 'Sondajes y ensayos de suelos'),
('3', 'Gestion Permisos Ambientales', 1, '2026-07-01', '2026-09-30', 1, 0, 'En Ejecucion', 0, 0, 0, 0, 0, 0, 0, 0, '0', '100%', '0%', '100%', '100%', 'Dra. Maria Lopez', 'Tramite ante Corpoboyaca - ETA y Licencia'),
('4', 'Diseno Arquitectonico', 1, '2026-07-15', '2026-09-15', 1, 0, 'No Iniciado', 0, 0, 0, 0, 0, 0, 0, 0, '0', '100%', '100%', '0%', '100%', 'Arq. Andres Garcia', 'Planos arquitectonicos terminal aerea'),
('5', 'Diseno Estructural', 1, '2026-08-01', '2026-10-01', 1, 0, 'No Iniciado', 0, 0, 0, 0, 0, 0, 4, 0, '0', '100%', '100%', '0%', '100%', 'Ing. Roberto Diaz', 'Calculo estructural y planos'),
('6', 'Diseno Mecanico-Electrico', 1, '2026-08-15', '2026-10-15', 1, 0, 'No Iniciado', 0, 0, 0, 0, 0, 0, 4, 0, '0', '100%', '100%', '0%', '100%', 'Ing. Luis Ramirez', 'Instalaciones M&E'),
('7', 'Diseno Sanitario', 0, '2026-08-15', '2026-10-15', 0, 0, 'No Iniciado', 0, 0, 0, 0, 0, 0, 4, 0, '0', '100%', '100%', '0%', '100%', 'Ing. Luis Ramirez', 'Acueducto, alcantarillado, agua caliente'),
('8', 'Diseno Vial y Accesos', 0, '2026-08-01', '2026-09-30', 0, 0, 'No Iniciado', 0, 0, 0, 0, 0, 0, 1, 0, '0', '100%', '100%', '0%', '100%', 'Ing. Carlos Mendez', 'Intersecciones y accesos al aeropuerto'),
('9', 'Estudio de Impacto Vial', 0, '2026-08-01', '2026-09-15', 0, 0, 'No Iniciado', 0, 0, 0, 0, 0, 0, 8, 0, '0', '100%', '0%', '0%', '100%', 'Ing. Carlos Mendez', 'Para concepto DAP'),
('10', 'Gestion Permisos Municipales', 0, '2026-09-01', '2026-11-30', 1, 0, 'No Iniciado', 0, 0, 0, 0, 0, 0, 4, 0, '0', '100%', '100%', '0%', '0%', 'Lda. Patricia Rojas', 'Licencia de construccion y conceptos'),
('11', 'Gestion Permisos Aeroportuarios', 0, '2026-08-01', '2026-11-30', 1, 0, 'No Iniciado', 0, 0, 0, 0, 0, 0, 0, 0, '0', '100%', '0%', '100%', '0%', 'Lda. Patricia Rojas', 'Concepto favorable Aerocivil'),
('12', 'Presupuesto Detallado', 0, '2026-09-15', '2026-10-31', 0, 0, 'No Iniciado', 0, 0, 0, 0, 0, 0, 4, 0, '0', '100%', '100%', '0%', '0%', 'Ing. Sandra Morales', 'Presupuesto por capitulos'),
('13', 'Plan de Contratacion', 0, '2026-10-01', '2026-10-31', 0, 0, 'No Iniciado', 0, 0, 0, 0, 0, 0, 12, 0, '0', '100%', '100%', '0%', '0%', 'Ing. Sandra Morales', 'Estrategia de contratacion por paquetes'),
('14', 'Estudios Ambientales Complementarios', 0, '2026-07-15', '2026-09-15', 0, 0, 'No Iniciado', 0, 0, 0, 0, 0, 0, 0, 0, '0', '100%', '0%', '100%', '100%', 'Dra. Maria Lopez', 'Bioacustica, calidad de aire'),
('15', 'Mobilizacion del Equipo de Obra', 0, '2026-11-01', '2026-11-30', 0, 0, 'No Iniciado', 0, 0, 0, 0, 0, 0, 10, 0, '0', '100%', '100%', '100%', '0%', 'Residente Juan Perez', 'Oficina provisional y vallado'),
('16', 'Preparacion del Terreno', 0, '2026-12-01', '2026-12-31', 0, 0, 'No Iniciado', 0, 0, 0, 0, 0, 0, 15, 0, '0', '100%', '100%', '100%', '0%', 'Residente Juan Perez', 'Descapote y nivelacion general'),
('17', 'Movimiento de Tierras', 0, '2026-12-01', '2026-12-31', 1, 0, 'No Iniciado', 0, 0, 0, 0, 0, 0, 15, 0, '0', '100%', '100%', '100%', '0%', 'Residente Juan Perez', 'Corte y relleno topografico'),
('18', 'Diseno Paisajistico', 0, '2026-09-01', '2026-10-31', 0, 0, 'No Iniciado', 0, 0, 0, 0, 0, 0, 4, 0, '0', '100%', '100%', '0%', '100%', 'Arq. Andres Garcia', 'Zonas verdes y paisajismo exterior'),
('19', 'Diseno Interior Terminal', 0, '2026-09-01', '2026-10-31', 0, 0, 'No Iniciado', 0, 0, 0, 0, 0, 0, 4, 0, '0', '100%', '100%', '0%', '100%', 'Arq. Andres Garcia', 'Interior terminal de pasajeros'),
('20', 'Apropiacion Presupuestal Final', 0, '2026-11-01', '2026-12-15', 0, 0, 'No Iniciado', 0, 0, 0, 0, 0, 0, 12, 0, '0', '100%', '100%', '100%', '0%', 'Ing. Sandra Morales', 'Cierre de apropiacion y pase a obra');

-- =====================================================================
-- PARTE 4: Datos semilla - Programa Consolidado (Semana 1)
-- =====================================================================

INSERT IGNORE INTO `da_aeropuerto_pc_programa_consolidado` (
    `Semana`, `Consecutivo_en_Programa`, `Id`, `Actividad`, `Titulo`,
    `Fecha_Inicio`, `Fecha_Fin`, `Ruta_Critica`, `Ejecutado`,
    `Estado`, `Semanas_Inicio`, `Estado_Restricciones`,
    `D_y_E`, `Materiales`, `MdeO`, `Equipos`,
    `Predecesora`, `Pdto_Cons`, `Modelo`,
    `Sub_Contratista`, `Responsable_AIA`, `Observaciones`,
    `Activa`, `codigo_actividad`
) VALUES
(1, 1, '1', 'Estudios Topograficos', 1, '2026-07-01', '2026-07-14', 1, 0, 'En Ejecucion', 0, 100, '100%', '100%', '100%', '100%', '0', 100, '0', NULL, 'Ing. Carlos Mendez', 'Actividad en curso semana 1', 1, 'PC-001'),
(1, 2, '2', 'Estudios Geotecnicos', 1, '2026-07-08', '2026-07-28', 1, 0, 'En Ejecucion', 0, 100, '100%', '100%', '100%', '100%', '1', 100, '0', NULL, 'Ing. Carlos Mendez', 'Actividad en curso semana 1', 1, 'PC-002'),
(1, 3, '3', 'Gestion Permisos Ambientales', 1, '2026-07-01', '2026-09-30', 1, 0, 'En Ejecucion', 0, 0, '0%', '0%', '0%', '0%', '0', 0, '0', NULL, 'Dra. Maria Lopez', 'Tramite en curso', 1, 'PC-003'),
(1, 4, '4', 'Diseno Arquitectonico', 1, '2026-07-15', '2026-09-15', 1, 0, 'No Iniciado', 0, 0, '0%', '0%', '0%', '0%', '0', 0, '0', NULL, 'Arq. Andres Garcia', 'Esperando estudios topograficos', 0, 'PC-004'),
(1, 5, '5', 'Diseno Estructural', 1, '2026-08-01', '2026-10-01', 1, 0, 'No Iniciado', 0, 0, '0%', '0%', '0%', '0%', '4', 0, '0', NULL, 'Ing. Roberto Diaz', 'Depende de diseno arquitectonico', 0, 'PC-005'),
(1, 14, '14', 'Estudios Ambientales Complementarios', 0, '2026-07-15', '2026-09-15', 0, 0, 'No Iniciado', 0, 0, '0%', '0%', '0%', '0%', '0', 0, '0', NULL, 'Dra. Maria Lopez', 'Bioacustica y calidad de aire', 0, 'PC-014'),
(1, 15, '15', 'Mobilizacion del Equipo de Obra', 0, '2026-11-01', '2026-11-30', 0, 0, 'No Iniciado', 0, 0, '0%', '0%', '0%', '0%', '10', 0, '0', NULL, 'Residente Juan Perez', 'Depende de permisos municipales', 0, 'PC-015'),
(1, 17, '17', 'Movimiento de Tierras', 0, '2026-12-01', '2026-12-31', 1, 0, 'No Iniciado', 0, 0, '0%', '0%', '0%', '0%', '15', 0, '0', NULL, 'Residente Juan Perez', 'Critico - ruta de construccion', 0, 'PC-017');

-- =====================================================================
-- PARTE 5: Datos semilla - Semanas Activas
-- =====================================================================
INSERT IGNORE INTO `da_aeropuerto_pc_semanas_activas` (
    `Semana`, `Fecha_Inicio_Sem`, `Fecha_Fin_Sem`,
    `Semanal_Confirmada`, `fechaCierreCompromisos`,
    `fechaCreacionSemana`, `reprogramacion`, `diferenciaEstructuraCron`
) VALUES
(1, '2026-07-01', '2026-07-07', 1, '2026-07-04', '2026-06-30', 0, 0),
(2, '2026-07-08', '2026-07-14', 1, '2026-07-11', '2026-07-07', 0, 0),
(3, '2026-07-15', '2026-07-21', 0, NULL, '2026-07-14', 0, 0);

-- =====================================================================
-- PARTE 6: Datos semilla - Profesionales
-- =====================================================================
INSERT IGNORE INTO `da_aeropuerto_pc_profesionales` (
    `nombre`, `email`, `cargo`, `activo`
) VALUES
('Ing. Carlos Mendez', 'cmendez@aia.com.co', 'Director de Proyecto', 1),
('Dra. Maria Lopez', 'mlopez@aia.com.co', 'Profesional Ambiental', 1),
('Arq. Andres Garcia', 'agarcia@aia.com.co', 'Arquitecto', 1),
('Ing. Roberto Diaz', 'rdiaz@aia.com.co', 'Ingeniero Estructural', 1),
('Ing. Luis Ramirez', 'lramirez@aia.com.co', 'Ingeniero Mecanico-Electrico', 1),
('Ing. Sandra Morales', 'smorales@aia.com.co', 'Control de Costos', 1),
('Lda. Patricia Rojas', 'projas@aia.com.co', 'Abogada Ambiental', 1),
('Residente Juan Perez', 'jperez@aia.com.co', 'Residente de Obra', 1);

-- =====================================================================
-- PARTE 7: Datos semilla - Subcontratistas
-- =====================================================================
INSERT IGNORE INTO `da_aeropuerto_pc_subcontratistas` (
    `subcontratista`, `correo_contacto`, `NIT`, `alcance`, `tipo_proveedor`, `activo`
) VALUES
('Geotecnica del Norte SAS', 'contacto@geotecnanorte.com', 890123456, 'Estudios geotecnicos y topograficos', 'Consultoria', 1),
('Ambiental Total LTDA', 'info@ambientaltotal.com', 890234567, 'Estudios y gestiones ambientales', 'Consultoria', 1),
('Construcciones Aeropuertarias SA', 'proyectos@construaero.com', 890345678, 'Obra civil y movimientos de tierra', 'Contratista', 1);

-- =====================================================================
-- PARTE 8: Datos semilla - Programacion Semanal (Semana 1)
-- =====================================================================
INSERT IGNORE INTO `da_aeropuerto_pc_programacion_semanal` (
    `Semana`, `Consecutivo_En_Programa`, `Id`, `Actividad`,
    `Fecha_Inicio`, `Fecha_Fin`, `Sub_Contratista`, `Responsable_AIA`,
    `Empresa`, `Activa`
) VALUES
(1, 1, '1', 'Estudios Topograficos', '2026-07-01', '2026-07-14', NULL, 'Ing. Carlos Mendez', 'AIA', '1'),
(1, 2, '2', 'Estudios Geotecnicos', '2026-07-08', '2026-07-28', 'Geotecnica del Norte SAS', 'Ing. Carlos Mendez', 'AIA', '1'),
(1, 3, '3', 'Gestion Permisos Ambientales', '2026-07-01', '2026-09-30', NULL, 'Dra. Maria Lopez', 'AIA', '1'),
(1, 4, '4', 'Diseno Arquitectonico', '2026-07-15', '2026-09-15', NULL, 'Arq. Andres Garcia', 'AIA', '0'),
(1, 5, '5', 'Diseno Estructural', '2026-08-01', '2026-10-01', NULL, 'Ing. Roberto Diaz', 'AIA', '0'),
(1, 6, '6', 'Diseno Mecanico-Electrico', '2026-08-15', '2026-10-15', NULL, 'Ing. Luis Ramirez', 'AIA', '0'),
(1, 7, '7', 'Diseno Sanitario', '2026-08-15', '2026-10-15', NULL, 'Ing. Luis Ramirez', 'AIA', '0'),
(1, 8, '8', 'Diseno Vial y Accesos', '2026-08-01', '2026-09-30', NULL, 'Ing. Carlos Mendez', 'AIA', '0'),
(1, 9, '9', 'Estudio de Impacto Vial', '2026-08-01', '2026-09-15', NULL, 'Ing. Carlos Mendez', 'AIA', '0'),
(1, 10, '10', 'Gestion Permisos Municipales', '2026-09-01', '2026-11-30', NULL, 'Lda. Patricia Rojas', 'AIA', '0'),
(1, 11, '11', 'Gestion Permisos Aeroportuarios', '2026-08-01', '2026-11-30', NULL, 'Lda. Patricia Rojas', 'AIA', '0'),
(1, 12, '12', 'Presupuesto Detallado', '2026-09-15', '2026-10-31', NULL, 'Ing. Sandra Morales', 'AIA', '0'),
(1, 13, '13', 'Plan de Contratacion', '2026-10-01', '2026-10-31', NULL, 'Ing. Sandra Morales', 'AIA', '0'),
(1, 14, '14', 'Estudios Ambientales Complementarios', '2026-07-15', '2026-09-15', NULL, 'Dra. Maria Lopez', 'AIA', '0'),
(1, 15, '15', 'Mobilizacion del Equipo de Obra', '2026-11-01', '2026-11-30', 'Construcciones Aeropuertarias SA', 'Residente Juan Perez', 'AIA', '0'),
(1, 16, '16', 'Preparacion del Terreno', '2026-12-01', '2026-12-31', 'Construcciones Aeropuertarias SA', 'Residente Juan Perez', 'AIA', '0'),
(1, 17, '17', 'Movimiento de Tierras', '2026-12-01', '2026-12-31', 'Construcciones Aeropuertarias SA', 'Residente Juan Perez', 'AIA', '0'),
(1, 18, '18', 'Diseno Paisajistico', '2026-09-01', '2026-10-31', NULL, 'Arq. Andres Garcia', 'AIA', '0'),
(1, 19, '19', 'Diseno Interior Terminal', '2026-09-01', '2026-10-31', NULL, 'Arq. Andres Garcia', 'AIA', '0'),
(1, 20, '20', 'Apropiacion Presupuestal Final', '2026-11-01', '2026-12-15', NULL, 'Ing. Sandra Morales', 'AIA', '0');

-- =====================================================================
-- PARTE 9: Datos semilla - CIC
-- =====================================================================
INSERT IGNORE INTO `da_aeropuerto_pc_cic` (
    `Semana`, `subcontratista`, `correo_contacto`, `NIT`, `alcance`, `tipo_proveedor`
) VALUES
(1, 'Geotecnica del Norte SAS', 'contacto@geotecnanorte.com', '890123456', 'Estudios geotecnicos y topograficos', 'Consultoria'),
(1, 'Ambiental Total LTDA', 'info@ambientaltotal.com', '890234567', 'Estudios y gestiones ambientales', 'Consultoria'),
(1, 'Construcciones Aeropuertarias SA', 'proyectos@construaero.com', '890345678', 'Obra civil y movimientos de tierra', 'Contratista');

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
-- VERIFICACION POST-EJECUCION
-- =====================================================================
-- 1. Proyecto: SELECT Id, Proyecto_Proceso, Base_de_Datos, Area FROM general_proyectos_procesos WHERE Area = 'Pre-Construccion';
-- 2. 16 tablas: SHOW TABLES LIKE 'da_aeropuerto_pc_%';
-- 3. Columnas PC en _programa: SHOW COLUMNS FROM da_aeropuerto_pc_programa LIKE 'restriccion_pc_%';
-- 4. Columnas PC en _programacion_semanal: SHOW COLUMNS FROM da_aeropuerto_pc_programacion_semanal LIKE 'Reprogramada%';
-- =====================================================================
