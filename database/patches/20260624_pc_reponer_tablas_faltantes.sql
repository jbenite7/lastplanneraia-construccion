-- =====================================================================
-- PARCHE PRODUCCION: REPONER TABLAS FALTANTES EN PROYECTOS ACTIVOS
-- FECHA: 2026-06-24
-- ALCANCE: general_proyectos_procesos WHERE Activo = 1
--          AND Area IN ('Construccion', 'Pre-Construccion')
--
-- Problema:
--   Auditoria de 47 proyectos contra set estandar de 16 tablas:
--     - 23 inactivos con 0 tablas (SKIP)
--     - 10 inactivos con tablas parciales (SKIP)
--     -  5 PI con 0 tablas (ELIMINADOS de general_proyectos_procesos)
--     -  8 activos parciales (12-15 tablas)
--     -  1 activo completo (da_porto)
--   Causa: createPreConstructionTables() omitia tablas; parches
--   20260525 (LPS) y 20260622 (TNP/CP) filtran Area='CONSTRUCCION'.
--
-- Accion:
--   Por cada prefijo Activo=1 (Construccion o Pre-Construccion),
--   CREATE TABLE IF NOT EXISTS para las 16 tablas estandar.
--   IF NOT EXISTS garantiza que las tablas existentes no se tocan.
--   Tambien anade fecha_ultimo_saneo a _semanas_activas si falta.
--
-- Politica:
-- - Aditivo e idempotente (CREATE TABLE IF NOT EXISTS).
-- - No elimina datos ni columnas ni tablas.
-- - Solo proyectos Activo=1, Area IN ('Construccion','Pre-Construccion').
-- - Solo prefijos validos [A-Za-z0-9_]+.
-- =====================================================================

SET NAMES utf8mb4;

DROP PROCEDURE IF EXISTS reponer_tablas_activos;

DELIMITER $$

CREATE PROCEDURE reponer_tablas_activos()
BEGIN
    DECLARE done INT DEFAULT 0;
    DECLARE v_prefix VARCHAR(128);
    DECLARE v_table_name VARCHAR(192);
    DECLARE v_col_exists INT DEFAULT 0;

    DECLARE cur CURSOR FOR
        SELECT DISTINCT TRIM(`Base_de_Datos`) AS db_prefix
        FROM `general_proyectos_procesos`
        WHERE `Activo` = 1
          AND UPPER(TRIM(`Area`)) IN ('CONSTRUCCION', 'PRE-CONSTRUCCION')
          AND `Base_de_Datos` IS NOT NULL
          AND TRIM(`Base_de_Datos`) <> ''
          AND TRIM(`Base_de_Datos`) REGEXP '^[A-Za-z0-9_]+$'
        ORDER BY db_prefix;

    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;

    OPEN cur;

    read_loop: LOOP
        FETCH cur INTO v_prefix;
        IF done = 1 THEN
            LEAVE read_loop;
        END IF;

        -- =========================================================
        -- 1. {prefix}_actividades
        -- =========================================================
        SET @sql = CONCAT(
            'CREATE TABLE IF NOT EXISTS `', v_prefix, '_actividades` (',
            ' `Id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,',
            ' `codigo` int NOT NULL,',
            ' `actividad` varchar(300) NOT NULL,',
            ' `descripcionActividad` mediumtext,',
            ' `actividadInicio` varchar(500) DEFAULT NULL,',
            ' `nombreActividadInicio` varchar(500) DEFAULT NULL,',
            ' `fechaInicio` date DEFAULT NULL,',
            ' `tipoContrato` varchar(10) DEFAULT NULL,',
            ' `semanaActualizacion` int DEFAULT NULL,',
            ' `SI1` varchar(200) DEFAULT NULL,',
            ' `paqueteSI1` varchar(200) DEFAULT NULL,',
            ' `SI2` varchar(200) DEFAULT NULL,',
            ' `paqueteSI2` varchar(200) DEFAULT NULL,',
            ' `SI3` varchar(200) DEFAULT NULL,',
            ' `paqueteSI3` varchar(200) DEFAULT NULL,',
            ' `SI4` varchar(200) DEFAULT NULL,',
            ' `paqueteSI4` varchar(200) DEFAULT NULL,',
            ' `SI5` varchar(200) DEFAULT NULL,',
            ' `paqueteSI5` varchar(200) DEFAULT NULL,',
            ' `S1` varchar(200) DEFAULT NULL,',
            ' `paqueteS1` varchar(200) DEFAULT NULL,',
            ' `S2` varchar(200) DEFAULT NULL,',
            ' `paqueteS2` varchar(200) DEFAULT NULL,',
            ' `S3` varchar(200) DEFAULT NULL,',
            ' `paqueteS3` varchar(200) DEFAULT NULL,',
            ' `S4` varchar(200) DEFAULT NULL,',
            ' `paqueteS4` varchar(200) DEFAULT NULL,',
            ' `S5` varchar(200) DEFAULT NULL,',
            ' `paqueteS5` varchar(200) DEFAULT NULL,',
            ' `MO1` varchar(200) DEFAULT NULL,',
            ' `paqueteMO1` varchar(200) DEFAULT NULL,',
            ' `MO2` varchar(200) DEFAULT NULL,',
            ' `paqueteMO2` varchar(200) DEFAULT NULL,',
            ' `MO3` varchar(200) DEFAULT NULL,',
            ' `paqueteMO3` varchar(200) DEFAULT NULL,',
            ' `MO4` varchar(200) DEFAULT NULL,',
            ' `paqueteMO4` varchar(200) DEFAULT NULL,',
            ' `MO5` varchar(200) DEFAULT NULL,',
            ' `paqueteMO5` varchar(200) DEFAULT NULL,',
            ' `OC1` varchar(200) DEFAULT NULL,',
            ' `paqueteOC1` varchar(200) DEFAULT NULL,',
            ' `OC2` varchar(200) DEFAULT NULL,',
            ' `paqueteOC2` varchar(200) DEFAULT NULL,',
            ' `OC3` varchar(200) DEFAULT NULL,',
            ' `paqueteOC3` varchar(200) DEFAULT NULL,',
            ' `OC4` varchar(200) DEFAULT NULL,',
            ' `paqueteOC4` varchar(200) DEFAULT NULL,',
            ' `OC5` varchar(200) DEFAULT NULL,',
            ' `paqueteOC5` varchar(200) DEFAULT NULL,',
            ' `numeroSubcontratos` tinyint NOT NULL DEFAULT 1,',
            ' `confianza_deteccion` decimal(5,2) DEFAULT NULL,',
            ' `ultimo_auto_definir` datetime DEFAULT NULL,',
            ' `fechaInicioProyectada` date DEFAULT NULL',
            ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb3'
        );
        PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

        -- =========================================================
        -- 2. {prefix}_auto_program_log
        -- =========================================================
        SET @sql = CONCAT(
            'CREATE TABLE IF NOT EXISTS `', v_prefix, '_auto_program_log` (',
            ' `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,',
            ' `semana` INT NOT NULL,',
            ' `consecutivo` INT NOT NULL,',
            ' `accion` ENUM(''comprometer'',''descomprometer'',''insert_cnp'') NOT NULL,',
            ' `detalle` TEXT,',
            ' `categoria_cnp` VARCHAR(100) DEFAULT NULL,',
            ' `cnp` VARCHAR(100) DEFAULT NULL,',
            ' `creado_en` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,',
            ' KEY `idx_semana` (`semana`),',
            ' KEY `idx_consecutivo` (`consecutivo`)',
            ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
        PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

        -- =========================================================
        -- 3. {prefix}_cambios
        -- =========================================================
        SET @sql = CONCAT(
            'CREATE TABLE IF NOT EXISTS `', v_prefix, '_cambios` (',
            ' `id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,',
            ' `solicitanteCambio` int DEFAULT NULL,',
            ' `detalleSolicitanteOtro` longtext,',
            ' `fechaSolicitud` date DEFAULT NULL,',
            ' `prioridad` int DEFAULT NULL,',
            ' `tipoCambio` longtext,',
            ' `responsableSolucion` int DEFAULT NULL,',
            ' `detalleResponsableSolucion` longtext,',
            ' `justificacion` longtext,',
            ' `descripcion` longtext,',
            ' `incidenciaAlcance` longtext,',
            ' `tiempoCronograma` float DEFAULT NULL,',
            ' `tiempoCronogramaAfectado` float DEFAULT NULL,',
            ' `incidenciaCronograma` longtext,',
            ' `valorPresupuesto` float DEFAULT NULL,',
            ' `costoDirecto` float DEFAULT NULL,',
            ' `costoDirectoAIU` float DEFAULT NULL,',
            ' `costoDirectoAIUIVA` float DEFAULT NULL,',
            ' `valorAprobado` float DEFAULT NULL,',
            ' `incidenciaPresupuesto` longtext,',
            ' `incidenciaCalidad` longtext,',
            ' `incidenciaRiesgo` longtext,',
            ' `incidenciaRecurso` longtext,',
            ' `fechaTentativaDefinicion` date DEFAULT NULL,',
            ' `fechaEntregaInterventoria` date DEFAULT NULL,',
            ' `Observaciones` longtext,',
            ' `fechaDefinicion` date DEFAULT NULL,',
            ' `aprobacion` int DEFAULT NULL,',
            ' `soportes` longtext',
            ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb3'
        );
        PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

        -- =========================================================
        -- 4. {prefix}_cic
        -- =========================================================
        SET @sql = CONCAT(
            'CREATE TABLE IF NOT EXISTS `', v_prefix, '_cic` (',
            ' `Id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,',
            ' `Semana` int(3) DEFAULT NULL,',
            ' `subcontratista` varchar(200) DEFAULT NULL,',
            ' `correo_contacto` varchar(200) DEFAULT NULL,',
            ' `NIT` varchar(10) DEFAULT NULL,',
            ' `alcance` varchar(200) DEFAULT NULL,',
            ' `tipo_proveedor` varchar(200) DEFAULT NULL,',
            ' `PAC` varchar(11) DEFAULT ''NA'',',
            ' `PAC_Acum` varchar(11) DEFAULT ''NA'',',
            ' `P_Completado` varchar(11) DEFAULT ''NA'',',
            ' `P_Completado_Acum` varchar(11) DEFAULT ''NA'',',
            ' `Calidad` varchar(11) NOT NULL DEFAULT ''NR'',',
            ' `Calidad_Acum` varchar(11) NOT NULL DEFAULT ''NR'',',
            ' `GSA` varchar(11) NOT NULL DEFAULT ''NR'',',
            ' `GSA_Acum` varchar(11) NOT NULL DEFAULT ''NR'',',
            ' `SST` varchar(11) NOT NULL DEFAULT ''NR'',',
            ' `SST_Acum` varchar(11) NOT NULL DEFAULT ''NR'',',
            ' `ADM` varchar(11) NOT NULL DEFAULT ''NR'',',
            ' `ADM_Acum` varchar(11) NOT NULL DEFAULT ''NR'',',
            ' `Cal_Integral` float DEFAULT NULL,',
            ' `Cal_Integral_Acum` float DEFAULT NULL,',
            ' `Observaciones` mediumtext DEFAULT NULL,',
            ' `mdo_cal_1` varchar(5) DEFAULT ''NR'',',
            ' `mdo_cal_2` varchar(5) DEFAULT ''NR'',',
            ' `mdo_cal_3` varchar(5) DEFAULT ''NR'',',
            ' `mdo_adm_1` varchar(5) DEFAULT ''NR'',',
            ' `mdo_adm_2` varchar(5) DEFAULT ''NR'',',
            ' `mdo_adm_3` varchar(5) DEFAULT ''NR'',',
            ' `mdo_adm_4` varchar(5) DEFAULT ''NR'',',
            ' `mdo_adm_5` varchar(5) DEFAULT ''NR'',',
            ' `mdo_gsa_1` varchar(5) DEFAULT ''NR'',',
            ' `mdo_gsa_2` varchar(5) DEFAULT ''NR'',',
            ' `mdo_gsa_3` varchar(5) DEFAULT ''NR'',',
            ' `mdo_gsa_4` varchar(5) DEFAULT ''NR'',',
            ' `mdo_gsa_5` varchar(5) DEFAULT ''NR'',',
            ' `mdo_gsa_6` varchar(5) DEFAULT ''NR'',',
            ' `mdo_gsa_7` varchar(5) DEFAULT ''NR'',',
            ' `mdo_gsa_8` varchar(5) DEFAULT ''NR'',',
            ' `mdo_sst_1` varchar(5) DEFAULT ''NR'',',
            ' `mdo_sst_2` varchar(5) DEFAULT ''NR'',',
            ' `mdo_sst_3` varchar(5) DEFAULT ''NR'',',
            ' `mdo_sst_4` varchar(5) DEFAULT ''NR'',',
            ' `mdo_sst_5` varchar(5) DEFAULT ''NR'',',
            ' `mdo_sst_6` varchar(5) DEFAULT ''NR'',',
            ' `mdo_sst_7` varchar(5) DEFAULT ''NR'',',
            ' `mdo_sst_8` varchar(5) DEFAULT ''NR'',',
            ' `mdo_sst_9` varchar(5) DEFAULT ''NR'',',
            ' `mdo_sst_10` varchar(5) DEFAULT ''NR'',',
            ' `si_cal_1` varchar(5) DEFAULT ''NR'',',
            ' `si_cal_2` varchar(5) DEFAULT ''NR'',',
            ' `si_cal_3` varchar(5) DEFAULT ''NR'',',
            ' `si_adm_1` varchar(5) DEFAULT ''NR'',',
            ' `si_adm_2` varchar(5) DEFAULT ''NR'',',
            ' `si_adm_3` varchar(5) DEFAULT ''NR'',',
            ' `si_adm_4` varchar(5) DEFAULT ''NR'',',
            ' `si_adm_5` varchar(5) DEFAULT ''NR'',',
            ' `si_adm_6` varchar(5) DEFAULT ''NR'',',
            ' `si_gsa_1` varchar(5) DEFAULT ''NR'',',
            ' `si_gsa_2` varchar(5) DEFAULT ''NR'',',
            ' `si_gsa_3` varchar(5) DEFAULT ''NR'',',
            ' `si_gsa_4` varchar(5) DEFAULT ''NR'',',
            ' `si_gsa_5` varchar(5) DEFAULT ''NR'',',
            ' `si_gsa_6` varchar(5) DEFAULT ''NR'',',
            ' `si_gsa_7` varchar(5) DEFAULT ''NR'',',
            ' `si_gsa_8` varchar(5) DEFAULT ''NR'',',
            ' `si_gsa_9` varchar(5) DEFAULT ''NR'',',
            ' `si_gsa_10` varchar(5) DEFAULT ''NR'',',
            ' `si_gsa_11` varchar(5) DEFAULT ''NR'',',
            ' `si_gsa_12` varchar(5) DEFAULT ''NR'',',
            ' `si_gsa_13` varchar(5) DEFAULT ''NR'',',
            ' `si_gsa_14` varchar(5) DEFAULT ''NR'',',
            ' `si_sst_1` varchar(5) DEFAULT ''NR'',',
            ' `si_sst_2` varchar(5) DEFAULT ''NR'',',
            ' `si_sst_3` varchar(5) DEFAULT ''NR'',',
            ' `si_sst_4` varchar(5) DEFAULT ''NR'',',
            ' `si_sst_5` varchar(5) DEFAULT ''NR'',',
            ' `si_sst_6` varchar(5) DEFAULT ''NR'',',
            ' `si_sst_7` varchar(5) DEFAULT ''NR'',',
            ' `si_sst_8` varchar(5) DEFAULT ''NR'',',
            ' `si_sst_9` varchar(5) DEFAULT ''NR'',',
            ' `si_sst_10` varchar(5) DEFAULT ''NR''',
            ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci'
        );
        PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

        -- =========================================================
        -- 5. {prefix}_lps_escalamientos
        -- =========================================================
        SET @sql = CONCAT(
            'CREATE TABLE IF NOT EXISTS `', v_prefix, '_lps_escalamientos` (',
            ' `id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,',
            ' `proyecto_id` int NOT NULL,',
            ' `semana` int NOT NULL,',
            ' `consecutivo_en_programa` int NOT NULL,',
            ' `modulo` ENUM(''PG'',''PI'',''PS'') NOT NULL,',
            ' `trigger_origen` varchar(50) NOT NULL,',
            ' `nivel_actual` tinyint NOT NULL DEFAULT 1,',
            ' `estado` ENUM(''Activo'',''Mitigado'',''Cerrado'') NOT NULL DEFAULT ''Activo'',',
            ' `fecha_detonacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP,',
            ' `fecha_ultimo_escalamiento` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,',
            ' `fecha_cierre` timestamp NULL DEFAULT NULL,',
            ' `usuario_cierre_id` int DEFAULT NULL,',
            ' `justificacion_cierre` mediumtext,',
            ' KEY `idx_semana_consecutivo` (`semana`,`consecutivo_en_programa`),',
            ' KEY `idx_estado_nivel` (`estado`,`nivel_actual`),',
            ' KEY `idx_proyecto` (`proyecto_id`)',
            ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
        PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

        -- =========================================================
        -- 6. {prefix}_lps_drawer_comentarios (sin FK para idempotencia)
        -- =========================================================
        SET @sql = CONCAT(
            'CREATE TABLE IF NOT EXISTS `', v_prefix, '_lps_drawer_comentarios` (',
            ' `id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,',
            ' `proyecto_id` int NOT NULL,',
            ' `consecutivo_en_programa` int NOT NULL,',
            ' `semana` int NOT NULL,',
            ' `usuario_id` int NOT NULL,',
            ' `comentario` mediumtext NOT NULL,',
            ' `escalamiento_id` int DEFAULT NULL,',
            ' `parent_id` int DEFAULT NULL,',
            ' `menciones` json DEFAULT NULL,',
            ' `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,',
            ' KEY `idx_comentario_actividad` (`consecutivo_en_programa`,`semana`),',
            ' KEY `idx_parent` (`parent_id`)',
            ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
        PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

        -- =========================================================
        -- 7. {prefix}_pdc
        -- =========================================================
        SET @sql = CONCAT(
            'CREATE TABLE IF NOT EXISTS `', v_prefix, '_pdc` (',
            ' `consecutivo` int NOT NULL AUTO_INCREMENT PRIMARY KEY,',
            ' `semana` int NOT NULL,',
            ' `titulo` int NOT NULL,',
            ' `tipoPaquete` varchar(200) NOT NULL,',
            ' `paqueteContratacion` varchar(200) DEFAULT NULL,',
            ' `contratos` varchar(200) DEFAULT NULL,',
            ' `numeroSubcontratos` int DEFAULT 1,',
            ' `subcontratoPaquete` int NOT NULL DEFAULT 1,',
            ' `estado` varchar(200) DEFAULT NULL,',
            ' `fechaElaboracionPliegos` date DEFAULT NULL,',
            ' `diasElaboracionPliegos` int DEFAULT NULL,',
            ' `fechaRealElaboracionPliegos` date DEFAULT NULL,',
            ' `fechaEntregaPliegos` date DEFAULT NULL,',
            ' `diasEntregaPliegos` int DEFAULT NULL,',
            ' `fechaRealEntregaPliegos` date DEFAULT NULL,',
            ' `fechaReciboPropuestas` date DEFAULT NULL,',
            ' `diasReciboPropuestas` int DEFAULT NULL,',
            ' `fechaRealReciboPropuestas` date DEFAULT NULL,',
            ' `fechaCuadrosComparativos` date DEFAULT NULL,',
            ' `diasCuadrosComparativos` int DEFAULT NULL,',
            ' `fechaRealCuadrosComparativos` date DEFAULT NULL,',
            ' `fechaLegalizacionContrato` date DEFAULT NULL,',
            ' `diasLegalizacionContrato` int DEFAULT NULL,',
            ' `fechaRealLegalizacionContrato` date DEFAULT NULL,',
            ' `fechaFabricacion` date DEFAULT NULL,',
            ' `diasFabricacion` int DEFAULT NULL,',
            ' `fechaRealFabricacion` date DEFAULT NULL,',
            ' `fechaInsumosObra` date DEFAULT NULL,',
            ' `diasInsumosObra` int DEFAULT NULL,',
            ' `fechaRealInsumosObra` date DEFAULT NULL,',
            ' `fechaInicio` date DEFAULT NULL,',
            ' `fechaInicioProyectada` date DEFAULT NULL,',
            ' `fechaRealInicio` date DEFAULT NULL,',
            ' `idProveedorAdjudicado` int DEFAULT NULL,',
            ' `numeroContrato` varchar(50) DEFAULT NULL,',
            ' `aplicaPolizas` int NOT NULL DEFAULT 1,',
            ' `fechaVencimientoPolizas` date DEFAULT NULL,',
            ' `valorPresupuesto` float DEFAULT NULL,',
            ' `valorPrimeraNegociacion` float DEFAULT NULL,',
            ' `valorAdjudicado` float DEFAULT NULL,',
            ' `valorAnticipo` float DEFAULT NULL,',
            ' `valorReclamado` float DEFAULT NULL,',
            ' `valorDevoluciones` float DEFAULT NULL,',
            ' `observacionesContrato` mediumtext',
            ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb3'
        );
        PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

        -- =========================================================
        -- 8. {prefix}_pg_tracking
        -- =========================================================
        SET @sql = CONCAT(
            'CREATE TABLE IF NOT EXISTS `', v_prefix, '_pg_tracking` (',
            ' `consecutivo_en_programa` INT NOT NULL,',
            ' `semana` INT NOT NULL,',
            ' `fecha_inicio` DATE DEFAULT NULL,',
            ' `fecha_fin` DATE DEFAULT NULL,',
            ' `estado` VARCHAR(100) DEFAULT NULL,',
            ' `restricciones_hash` CHAR(32) DEFAULT NULL,',
            ' `fechas_hash` CHAR(32) DEFAULT NULL,',
            ' `estado_hash` CHAR(32) DEFAULT NULL,',
            ' `titulo` TINYINT(1) NOT NULL DEFAULT 0,',
            ' `ultimo_detectado` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,',
            ' PRIMARY KEY (`consecutivo_en_programa`, `semana`),',
            ' KEY `idx_semana` (`semana`)',
            ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
        PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

        -- =========================================================
        -- 9. {prefix}_pi_shared_constraints
        --    (modulo PI = Programacion Intermedia dentro de proyectos
        --    de Construccion. NO confundir con proyectos tipo PI,
        --    que fueron eliminados.)
        -- =========================================================
        SET @sql = CONCAT(
            'CREATE TABLE IF NOT EXISTS `', v_prefix, '_pi_shared_constraints` (',
            ' `Id` bigint unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,',
            ' `Semana` int NOT NULL,',
            ' `Restriccion` varchar(40) NOT NULL,',
            ' `ValorObjetivo` varchar(20) NOT NULL,',
            ' `Nota` text,',
            ' `CreadoPor` varchar(120) DEFAULT NULL,',
            ' `CreadoEn` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,',
            ' `ActualizadoEn` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,',
            ' KEY `idx_semana` (`Semana`),',
            ' KEY `idx_restriccion` (`Restriccion`)',
            ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
        PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

        -- =========================================================
        -- 10. {prefix}_pi_shared_constraint_links
        -- =========================================================
        SET @sql = CONCAT(
            'CREATE TABLE IF NOT EXISTS `', v_prefix, '_pi_shared_constraint_links` (',
            ' `Id` bigint unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,',
            ' `SharedConstraintId` bigint unsigned NOT NULL,',
            ' `Semana` int NOT NULL,',
            ' `ConsecutivoEnPrograma` varchar(64) NOT NULL,',
            ' `ValorAplicado` varchar(20) NOT NULL,',
            ' `OverrideLocal` tinyint(1) NOT NULL DEFAULT 0,',
            ' `AplicadoEn` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,',
            ' KEY `idx_shared` (`SharedConstraintId`),',
            ' KEY `idx_semana_consecutivo` (`Semana`,`ConsecutivoEnPrograma`)',
            ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
        PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

        -- =========================================================
        -- 11. {prefix}_profesionales
        -- =========================================================
        SET @sql = CONCAT(
            'CREATE TABLE IF NOT EXISTS `', v_prefix, '_profesionales` (',
            ' `id` int(3) NOT NULL AUTO_INCREMENT PRIMARY KEY,',
            ' `nombre` varchar(100) NOT NULL,',
            ' `email` varchar(100) NOT NULL,',
            ' `cargo` varchar(100) NOT NULL,',
            ' `Activo` int(11) NOT NULL DEFAULT 1',
            ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci'
        );
        PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

        -- =========================================================
        -- 12. {prefix}_programa
        -- =========================================================
        SET @sql = CONCAT(
            'CREATE TABLE IF NOT EXISTS `', v_prefix, '_programa` (',
            ' `Consecutivo` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,',
            ' `Id` varchar(500) DEFAULT NULL,',
            ' `Actividad` varchar(500) DEFAULT NULL,',
            ' `Titulo` int(11) DEFAULT NULL,',
            ' `Fecha_Inicio` date DEFAULT NULL,',
            ' `Fecha_Fin` date DEFAULT NULL,',
            ' `Ruta_Critica` int(11) DEFAULT NULL,',
            ' `Ejecutado` float DEFAULT 0,',
            ' `Estado` varchar(50) DEFAULT NULL,',
            ' `Semanas_Inicio` int(1) DEFAULT 0,',
            ' `Estado_Restricciones` float DEFAULT 0,',
            ' `D_y_E` float DEFAULT 0,',
            ' `Materiales` float DEFAULT 0,',
            ' `MdeO` float DEFAULT 0,',
            ' `Equipos` float DEFAULT 0,',
            ' `Predecesora` float DEFAULT 0,',
            ' `Pdto_Cons` float DEFAULT 0,',
            ' `Modelo` varchar(9) DEFAULT ''0'',',
            ' `Responsable_AIA` varchar(100) DEFAULT NULL,',
            ' `Observaciones` mediumtext DEFAULT NULL,',
            ' `Ult_Act_Est` date DEFAULT NULL,',
            ' `Ult_Act_Restr` date DEFAULT NULL',
            ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci'
        );
        PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

        -- =========================================================
        -- 13. {prefix}_programa_consolidado
        -- =========================================================
        SET @sql = CONCAT(
            'CREATE TABLE IF NOT EXISTS `', v_prefix, '_programa_consolidado` (',
            ' `Consecutivo` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,',
            ' `Semana` int(3) NOT NULL,',
            ' `Consecutivo_en_Programa` int(11) NOT NULL,',
            ' `Id` varchar(500) DEFAULT NULL,',
            ' `Actividad` varchar(500) DEFAULT NULL,',
            ' `Titulo` int(11) DEFAULT NULL,',
            ' `Fecha_Inicio` date DEFAULT NULL,',
            ' `Fecha_Fin` date DEFAULT NULL,',
            ' `Ruta_Critica` int(11) DEFAULT NULL,',
            ' `Ejecutado` float DEFAULT 0,',
            ' `Estado` varchar(100) DEFAULT NULL,',
            ' `Semanas_Inicio` int(10) DEFAULT 0,',
            ' `Estado_Restricciones` float NOT NULL DEFAULT 0,',
            ' `D_y_E` varchar(9) NOT NULL DEFAULT ''0'',',
            ' `Materiales` varchar(9) NOT NULL DEFAULT ''0'',',
            ' `MdeO` varchar(9) NOT NULL DEFAULT ''0'',',
            ' `Equipos` varchar(9) NOT NULL DEFAULT ''0'',',
            ' `Predecesora` varchar(9) NOT NULL DEFAULT ''0'',',
            ' `Pdto_Cons` varchar(9) NOT NULL DEFAULT ''0'',',
            ' `Modelo` varchar(9) NOT NULL DEFAULT ''0'',',
            ' `Sub_Contratista` varchar(100) DEFAULT NULL,',
            ' `Responsable_AIA` varchar(100) DEFAULT NULL,',
            ' `Observaciones` mediumtext DEFAULT NULL,',
            ' `Ult_Act_Est` date DEFAULT NULL,',
            ' `Ult_Act_Restr` date DEFAULT NULL,',
            ' `Activa` int(1) NOT NULL DEFAULT 0,',
            ' `Ejecutado_Siguiente_Semana` float DEFAULT NULL,',
            ' `codigo_actividad` varchar(11) DEFAULT NULL,',
            ' `medir_productividad` int(11) DEFAULT 0,',
            ' `cantidad_ppto` int(11) DEFAULT NULL,',
            ' `unidad` varchar(20) DEFAULT NULL,',
            ' `programaAnteriorAsociar` varchar(500) DEFAULT NULL',
            ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci'
        );
        PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

        -- =========================================================
        -- 14. {prefix}_programacion_semanal
        -- =========================================================
        SET @sql = CONCAT(
            'CREATE TABLE IF NOT EXISTS `', v_prefix, '_programacion_semanal` (',
            ' `Consecutivo` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,',
            ' `Semana` int(3) DEFAULT NULL,',
            ' `Consecutivo_En_Programa` int(11) NOT NULL,',
            ' `Id` varchar(500) DEFAULT NULL,',
            ' `Actividad` varchar(500) DEFAULT NULL,',
            ' `Descripcion` mediumtext DEFAULT NULL,',
            ' `Ubicacion` mediumtext DEFAULT NULL,',
            ' `Fecha_Inicio` date DEFAULT NULL,',
            ' `Fecha_Fin` date DEFAULT NULL,',
            ' `Sub_Contratista` varchar(200) DEFAULT NULL,',
            ' `Responsable_AIA` varchar(200) DEFAULT NULL,',
            ' `Empresa` varchar(200) NOT NULL DEFAULT ''AIA'',',
            ' `Ejecutado` float DEFAULT NULL,',
            ' `medir_productividad` int(11) DEFAULT 0,',
            ' `Unidad` varchar(10) DEFAULT NULL,',
            ' `cantidad_ppto` int(11) DEFAULT NULL,',
            ' `Cantidad_Sugerida` float DEFAULT NULL,',
            ' `Compromiso` float DEFAULT NULL,',
            ' `Ejecutado_Real` float DEFAULT NULL,',
            ' `P_Completado` float DEFAULT NULL,',
            ' `PAC` int(1) DEFAULT NULL,',
            ' `Critica` int(1) DEFAULT NULL,',
            ' `Atrasada` int(1) DEFAULT NULL,',
            ' `Activa` varchar(3) DEFAULT NULL,',
            ' `Reprogramada_Por_Usuario` TINYINT(1) NOT NULL DEFAULT 0,',
            ' `Es_TNP` TINYINT(1) NOT NULL DEFAULT 0,',
            ' `Categoria_CP` VARCHAR(100) DEFAULT NULL,',
            ' `CP` VARCHAR(255) DEFAULT NULL,',
            ' `Observaciones_CP` TEXT DEFAULT NULL,',
            ' `Prog_Sin_Restricciones_100` int(1) DEFAULT NULL,',
            ' `Categoria_CNP` varchar(100) DEFAULT NULL,',
            ' `CNP` varchar(100) DEFAULT NULL,',
            ' `Observaciones_CNP` mediumtext DEFAULT NULL,',
            ' `Categoria_CNC` varchar(100) DEFAULT NULL,',
            ' `CNC` varchar(100) DEFAULT NULL,',
            ' `Observaciones_CNC` mediumtext DEFAULT NULL,',
            ' `Rendimientos` varchar(500) DEFAULT NULL,',
            ' `codigo_actividad` varchar(11) DEFAULT NULL,',
            ' `alerta_crisis` TINYINT(1) NOT NULL DEFAULT 0,',
            ' `reprogramaciones_semanales` INT NOT NULL DEFAULT 0,',
            ' INDEX `idx_crisis_semanal` (`Semana`, `alerta_crisis`),',
            ' INDEX `idx_consecutivo_semanal` (`Consecutivo_En_Programa`, `Semana`)',
            ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci'
        );
        PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

        -- =========================================================
        -- 15. {prefix}_semanas_activas + ALTER fecha_ultimo_saneo
        -- =========================================================
        SET @sql = CONCAT(
            'CREATE TABLE IF NOT EXISTS `', v_prefix, '_semanas_activas` (',
            ' `Id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,',
            ' `Semana` int(11) NOT NULL,',
            ' `Fecha_Inicio_Sem` date NOT NULL,',
            ' `Fecha_Fin_Sem` date NOT NULL,',
            ' `Semanal_Confirmada` int(1) DEFAULT 0,',
            ' `fechaCierreCompromisos` date DEFAULT NULL,',
            ' `fecha_ultimo_saneo` DATETIME NULL DEFAULT NULL,',
            ' `fechaCreacionSemana` date DEFAULT NULL,',
            ' `reprogramacion` int(11) NOT NULL DEFAULT 0,',
            ' `diferenciaEstructuraCron` int(11) NOT NULL DEFAULT 0',
            ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci'
        );
        PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

        -- ALTER: fecha_ultimo_saneo si la tabla ya existia sin ella
        SET v_table_name = CONCAT(v_prefix, '_semanas_activas');
        SELECT COUNT(*) INTO v_col_exists
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = v_table_name
          AND COLUMN_NAME = 'fecha_ultimo_saneo';

        IF v_col_exists = 0 THEN
            SET @sql = CONCAT(
                'ALTER TABLE `', v_table_name, '` ',
                'ADD COLUMN `fecha_ultimo_saneo` DATETIME NULL DEFAULT NULL ',
                'AFTER `fechaCierreCompromisos`'
            );
            PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
        END IF;

        -- =========================================================
        -- 16. {prefix}_subcontratistas
        -- =========================================================
        SET @sql = CONCAT(
            'CREATE TABLE IF NOT EXISTS `', v_prefix, '_subcontratistas` (',
            ' `Id` int(3) NOT NULL AUTO_INCREMENT PRIMARY KEY,',
            ' `subcontratista` varchar(200) NOT NULL,',
            ' `correo_contacto` varchar(200) NOT NULL,',
            ' `NIT` bigint(10) NOT NULL,',
            ' `alcance` varchar(200) NOT NULL,',
            ' `tipo_proveedor` varchar(200) NOT NULL,',
            ' `activo` int(11) NOT NULL DEFAULT 1',
            ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci'
        );
        PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

    END LOOP;

    CLOSE cur;
END$$

DELIMITER ;

CALL reponer_tablas_activos();

DROP PROCEDURE IF EXISTS reponer_tablas_activos;

-- =====================================================================
-- FIN Parche 20260624 — Repone tablas faltantes en proyectos ACTIVOS
-- =====================================================================
