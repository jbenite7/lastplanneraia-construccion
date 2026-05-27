-- =====================================================================
-- PARCHE PRODUCCION: LPS ESCALAMIENTOS, DRAWERS Y PI SHARED TABLES
-- FECHA: 2026-05-25
-- BASE OBJETIVO: dbhif4pdimjtxe / SiteGround
-- ALCANCE: solo proyectos con general_proyectos_procesos.Area = 'Construccion'
--          omite proyectos de otras areas, incluidos PI.
--
-- Politica:
-- - Aditivo e idempotente.
-- - No elimina datos ni columnas.
-- - No depende de prefijos hardcodeados.
-- - Solo parchea prefijos validos [A-Za-z0-9_].
-- - Solo parchea proyectos que tienen tablas base de construccion:
--   <prefijo>_programa_consolidado y <prefijo>_programacion_semanal.
-- =====================================================================

SET NAMES utf8mb4;

DROP PROCEDURE IF EXISTS patch_lps_drawers_construccion;

DELIMITER $$

CREATE PROCEDURE patch_lps_drawers_construccion()
BEGIN
    DECLARE done INT DEFAULT 0;
    DECLARE v_prefix VARCHAR(128);
    DECLARE v_table_consolidado VARCHAR(192);
    DECLARE v_table_semanal VARCHAR(192);
    DECLARE v_has_consolidado INT DEFAULT 0;
    DECLARE v_has_semanal INT DEFAULT 0;
    DECLARE v_col_exists INT DEFAULT 0;
    DECLARE v_idx_exists INT DEFAULT 0;
    DECLARE v_ref_col_exists INT DEFAULT 0;

    DECLARE cur CURSOR FOR
        SELECT DISTINCT TRIM(`Base_de_Datos`) AS db_prefix
        FROM `general_proyectos_procesos`
        WHERE UPPER(TRIM(`Area`)) = 'CONSTRUCCION'
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

        SET v_table_consolidado = CONCAT(v_prefix, '_programa_consolidado');
        SET v_table_semanal = CONCAT(v_prefix, '_programacion_semanal');

        SELECT COUNT(*) INTO v_has_consolidado
        FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = v_table_consolidado;

        SELECT COUNT(*) INTO v_has_semanal
        FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = v_table_semanal;

        IF v_has_consolidado > 0 AND v_has_semanal > 0 THEN
            -- ---------------------------------------------------------
            -- 1. Tabla de historial y estado de escalamientos LPS
            -- ---------------------------------------------------------
            SET @sql = CONCAT(
                'CREATE TABLE IF NOT EXISTS `', v_prefix, '_lps_escalamientos` (',
                ' `id` INT AUTO_INCREMENT,',
                ' `proyecto_id` INT NOT NULL COMMENT ''ID de la obra actual'',',
                ' `semana` INT NOT NULL COMMENT ''Semana en la que se detono o esta activa'',',
                ' `consecutivo_en_programa` INT NOT NULL COMMENT ''ID de la actividad en consolidado'',',
                ' `modulo` ENUM(''PG'', ''PI'', ''PS'') NOT NULL COMMENT ''Nivel de planificacion donde se detecta'',',
                ' `trigger_origen` VARCHAR(50) NOT NULL COMMENT ''Codigo del disparador: PG-1, PG-2, PI-1, PI-2, PS-1, PS-2, PS-3'',',
                ' `nivel_actual` TINYINT NOT NULL DEFAULT 1 COMMENT ''1: Residente, 2: Director, 3: Coordinador Integracion, 4: G. Construccion, 5: G. General'',',
                ' `estado` ENUM(''Activo'', ''Mitigado'', ''Cerrado'') NOT NULL DEFAULT ''Activo'',',
                ' `fecha_detonacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,',
                ' `fecha_ultimo_escalamiento` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,',
                ' `fecha_cierre` TIMESTAMP NULL DEFAULT NULL,',
                ' `usuario_cierre_id` INT NULL COMMENT ''Usuario de general_usuarios que cierra la alerta'',',
                ' `justificacion_cierre` MEDIUMTEXT NULL COMMENT ''Justificacion obligatoria (>100 caracteres)'',',
                ' PRIMARY KEY (`id`),',
                ' KEY `idx_semana_consecutivo` (`semana`, `consecutivo_en_programa`),',
                ' KEY `idx_estado_nivel` (`estado`, `nivel_actual`),',
                ' KEY `idx_proyecto` (`proyecto_id`)',
                ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
            );
            PREPARE stmt FROM @sql;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;

            -- ---------------------------------------------------------
            -- 2. Tabla de bitacora y comentarios en drawers LPS
            -- ---------------------------------------------------------
            SET @sql = CONCAT(
                'CREATE TABLE IF NOT EXISTS `', v_prefix, '_lps_drawer_comentarios` (',
                ' `id` INT AUTO_INCREMENT,',
                ' `proyecto_id` INT NOT NULL,',
                ' `consecutivo_en_programa` INT NOT NULL,',
                ' `semana` INT NOT NULL,',
                ' `usuario_id` INT NOT NULL COMMENT ''Autor del comentario (general_usuarios)'',',
                ' `comentario` MEDIUMTEXT NOT NULL,',
                ' `escalamiento_id` INT DEFAULT NULL COMMENT ''Nulo si es comentario general de bitacora, ID si vincula a hilo de crisis'',',
                ' `parent_id` INT DEFAULT NULL COMMENT ''Autorreferencia para soporte de hilos anidados'',',
                ' `menciones` JSON DEFAULT NULL COMMENT ''Metadatos de roles AIA notificados'',',
                ' `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,',
                ' PRIMARY KEY (`id`),',
                ' KEY `idx_comentario_actividad` (`consecutivo_en_programa`, `semana`),',
                ' KEY `idx_parent` (`parent_id`),',
                ' CONSTRAINT `fk_', v_prefix, '_lpsc_escal` FOREIGN KEY (`escalamiento_id`) REFERENCES `', v_prefix, '_lps_escalamientos` (`id`) ON DELETE SET NULL,',
                ' CONSTRAINT `fk_', v_prefix, '_lpsc_parent` FOREIGN KEY (`parent_id`) REFERENCES `', v_prefix, '_lps_drawer_comentarios` (`id`) ON DELETE CASCADE',
                ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
            );
            PREPARE stmt FROM @sql;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;

            -- ---------------------------------------------------------
            -- 3. Columnas e indices en <prefijo>_programa_consolidado
            -- ---------------------------------------------------------
            SELECT COUNT(*) INTO v_col_exists
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = v_table_consolidado
              AND COLUMN_NAME = 'alerta_crisis';

            IF v_col_exists = 0 THEN
                SELECT COUNT(*) INTO v_ref_col_exists
                FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = v_table_consolidado
                  AND COLUMN_NAME = 'programaAnteriorAsociar';

                IF v_ref_col_exists > 0 THEN
                    SET @sql = CONCAT('ALTER TABLE `', v_table_consolidado, '` ADD COLUMN `alerta_crisis` TINYINT(1) NOT NULL DEFAULT 0 AFTER `programaAnteriorAsociar`');
                ELSE
                    SET @sql = CONCAT('ALTER TABLE `', v_table_consolidado, '` ADD COLUMN `alerta_crisis` TINYINT(1) NOT NULL DEFAULT 0');
                END IF;
                PREPARE stmt FROM @sql;
                EXECUTE stmt;
                DEALLOCATE PREPARE stmt;
            END IF;

            SELECT COUNT(*) INTO v_col_exists
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = v_table_consolidado
              AND COLUMN_NAME = 'reprogramaciones_acumuladas';

            IF v_col_exists = 0 THEN
                SET @sql = CONCAT('ALTER TABLE `', v_table_consolidado, '` ADD COLUMN `reprogramaciones_acumuladas` INT NOT NULL DEFAULT 0 AFTER `alerta_crisis`');
                PREPARE stmt FROM @sql;
                EXECUTE stmt;
                DEALLOCATE PREPARE stmt;
            END IF;

            SELECT COUNT(*) INTO v_col_exists
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = v_table_consolidado
              AND COLUMN_NAME = 'dias_reprogramacion_acumulada';

            IF v_col_exists = 0 THEN
                SET @sql = CONCAT('ALTER TABLE `', v_table_consolidado, '` ADD COLUMN `dias_reprogramacion_acumulada` INT NOT NULL DEFAULT 0 AFTER `reprogramaciones_acumuladas`');
                PREPARE stmt FROM @sql;
                EXECUTE stmt;
                DEALLOCATE PREPARE stmt;
            END IF;

            SELECT COUNT(*) INTO v_idx_exists
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = v_table_consolidado
              AND INDEX_NAME = 'idx_crisis_hot';

            IF v_idx_exists = 0 THEN
                SET @sql = CONCAT('ALTER TABLE `', v_table_consolidado, '` ADD INDEX `idx_crisis_hot` (`Semana`, `alerta_crisis`)');
                PREPARE stmt FROM @sql;
                EXECUTE stmt;
                DEALLOCATE PREPARE stmt;
            END IF;

            SELECT COUNT(*) INTO v_idx_exists
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = v_table_consolidado
              AND INDEX_NAME = 'idx_consecutivo_consolidado';

            IF v_idx_exists = 0 THEN
                SET @sql = CONCAT('ALTER TABLE `', v_table_consolidado, '` ADD INDEX `idx_consecutivo_consolidado` (`Consecutivo_en_Programa`, `Semana`)');
                PREPARE stmt FROM @sql;
                EXECUTE stmt;
                DEALLOCATE PREPARE stmt;
            END IF;

            -- ---------------------------------------------------------
            -- 4. Columnas e indices en <prefijo>_programacion_semanal
            -- ---------------------------------------------------------
            SELECT COUNT(*) INTO v_col_exists
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = v_table_semanal
              AND COLUMN_NAME = 'alerta_crisis';

            IF v_col_exists = 0 THEN
                SELECT COUNT(*) INTO v_ref_col_exists
                FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = v_table_semanal
                  AND COLUMN_NAME = 'codigo_actividad';

                IF v_ref_col_exists > 0 THEN
                    SET @sql = CONCAT('ALTER TABLE `', v_table_semanal, '` ADD COLUMN `alerta_crisis` TINYINT(1) NOT NULL DEFAULT 0 AFTER `codigo_actividad`');
                ELSE
                    SET @sql = CONCAT('ALTER TABLE `', v_table_semanal, '` ADD COLUMN `alerta_crisis` TINYINT(1) NOT NULL DEFAULT 0');
                END IF;
                PREPARE stmt FROM @sql;
                EXECUTE stmt;
                DEALLOCATE PREPARE stmt;
            END IF;

            SELECT COUNT(*) INTO v_col_exists
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = v_table_semanal
              AND COLUMN_NAME = 'reprogramaciones_semanales';

            IF v_col_exists = 0 THEN
                SET @sql = CONCAT('ALTER TABLE `', v_table_semanal, '` ADD COLUMN `reprogramaciones_semanales` INT NOT NULL DEFAULT 0 AFTER `alerta_crisis`');
                PREPARE stmt FROM @sql;
                EXECUTE stmt;
                DEALLOCATE PREPARE stmt;
            END IF;

            SELECT COUNT(*) INTO v_idx_exists
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = v_table_semanal
              AND INDEX_NAME = 'idx_crisis_semanal';

            IF v_idx_exists = 0 THEN
                SET @sql = CONCAT('ALTER TABLE `', v_table_semanal, '` ADD INDEX `idx_crisis_semanal` (`Semana`, `alerta_crisis`)');
                PREPARE stmt FROM @sql;
                EXECUTE stmt;
                DEALLOCATE PREPARE stmt;
            END IF;

            SELECT COUNT(*) INTO v_idx_exists
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = v_table_semanal
              AND INDEX_NAME = 'idx_consecutivo_semanal';

            IF v_idx_exists = 0 THEN
                SET @sql = CONCAT('ALTER TABLE `', v_table_semanal, '` ADD INDEX `idx_consecutivo_semanal` (`Consecutivo_En_Programa`, `Semana`)');
                PREPARE stmt FROM @sql;
                EXECUTE stmt;
                DEALLOCATE PREPARE stmt;
            END IF;

            -- ---------------------------------------------------------
            -- 5. Tablas PI shared para Programacion Intermedia del
            --    mismo proyecto de Construccion. No aplica a Area='PI'.
            -- ---------------------------------------------------------
            SET @sql = CONCAT(
                'CREATE TABLE IF NOT EXISTS `', v_prefix, '_pi_shared_constraints` (',
                ' `Id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,',
                ' `Semana` INT NOT NULL,',
                ' `Restriccion` VARCHAR(40) NOT NULL,',
                ' `ValorObjetivo` VARCHAR(20) NOT NULL,',
                ' `Nota` TEXT NULL,',
                ' `CreadoPor` VARCHAR(120) NULL,',
                ' `CreadoEn` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,',
                ' `ActualizadoEn` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,',
                ' INDEX `idx_semana` (`Semana`),',
                ' INDEX `idx_restriccion` (`Restriccion`)',
                ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
            );
            PREPARE stmt FROM @sql;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;

            SET @sql = CONCAT(
                'CREATE TABLE IF NOT EXISTS `', v_prefix, '_pi_shared_constraint_links` (',
                ' `Id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,',
                ' `SharedConstraintId` BIGINT UNSIGNED NOT NULL,',
                ' `Semana` INT NOT NULL,',
                ' `ConsecutivoEnPrograma` VARCHAR(64) NOT NULL,',
                ' `ValorAplicado` VARCHAR(20) NOT NULL,',
                ' `OverrideLocal` TINYINT(1) NOT NULL DEFAULT 0,',
                ' `AplicadoEn` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,',
                ' INDEX `idx_shared` (`SharedConstraintId`),',
                ' INDEX `idx_semana_consecutivo` (`Semana`, `ConsecutivoEnPrograma`)',
                ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
            );
            PREPARE stmt FROM @sql;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        END IF;
    END LOOP;

    CLOSE cur;
END$$

DELIMITER ;

CALL patch_lps_drawers_construccion();

DROP PROCEDURE IF EXISTS patch_lps_drawers_construccion;

-- ---------------------------------------------------------------------
-- 6. Catalogo global de causas metodologicas LPS
-- ---------------------------------------------------------------------
INSERT INTO `general_cnc` (`Categoria_CNC`, `CNC`)
SELECT 'Mano de Obra', 'Insuficiencia de Mano de Obra en Frente (AIA)'
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM `general_cnc`
    WHERE `Categoria_CNC` = 'Mano de Obra'
      AND `CNC` = 'Insuficiencia de Mano de Obra en Frente (AIA)'
);

INSERT INTO `general_cnc` (`Categoria_CNC`, `CNC`)
SELECT 'Materiales', 'Retraso en Despacho de Material Crítico (AIA)'
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM `general_cnc`
    WHERE `Categoria_CNC` = 'Materiales'
      AND `CNC` = 'Retraso en Despacho de Material Crítico (AIA)'
);

INSERT INTO `general_cnc` (`Categoria_CNC`, `CNC`)
SELECT 'Diseños', 'Falta de Definición Arquitectónica o de Ingeniería (AIA)'
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM `general_cnc`
    WHERE `Categoria_CNC` = 'Diseños'
      AND `CNC` = 'Falta de Definición Arquitectónica o de Ingeniería (AIA)'
);

-- ---------------------------------------------------------------------
-- 7. Verificacion rapida post-parche
-- ---------------------------------------------------------------------
SELECT
    'OK: parche LPS/drawers aplicado a proyectos Area=Construccion con tablas base existentes' AS resultado,
    COUNT(DISTINCT TRIM(`Base_de_Datos`)) AS proyectos_construccion_detectados
FROM `general_proyectos_procesos`
WHERE UPPER(TRIM(`Area`)) = 'CONSTRUCCION'
  AND `Base_de_Datos` IS NOT NULL
  AND TRIM(`Base_de_Datos`) <> ''
  AND TRIM(`Base_de_Datos`) REGEXP '^[A-Za-z0-9_]+$';
