-- =====================================================================
-- PARCHE PRODUCCION: COLUMNA AREA + COLUMNAS RESTRICCION PC
-- FECHA: 2026-06-24
-- ALCANCE:
--   1. Agrega columna `Area` a general_proyectos_procesos (si falta).
--   2. Agrega 4 columnas restriccion_pc_1..4 a {prefix}_programa y
--      {prefix}_programa_consolidado para proyectos PRE-CONSTRUCCION.
--
-- Politica:
-- - Aditivo e idempotente (IF NOT EXISTS via information_schema).
-- - No elimina datos ni columnas.
-- - No depende de prefijos hardcodeados.
-- - Solo parchea prefijos validos [A-Za-z0-9_].
-- =====================================================================

SET NAMES utf8mb4;

-- =====================================================================
-- PARTE 1: Agregar columna Area a general_proyectos_procesos
-- =====================================================================
SET @tbl_exists = (SELECT COUNT(*) FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'general_proyectos_procesos');

SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'general_proyectos_procesos'
    AND COLUMN_NAME = 'Area');

SET @sql = IF(@tbl_exists = 0,
    'SELECT "general_proyectos_procesos no existe, skip" AS info',
    IF(@col_exists = 0,
    'ALTER TABLE `general_proyectos_procesos` ADD COLUMN `Area` VARCHAR(50) DEFAULT ''Construccion'' AFTER `Proyecto_Proceso`',
    'SELECT "Area already exists on general_proyectos_procesos" AS info'));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Backfill NULL / empty values to 'Construccion'
SET @tbl_exists = (SELECT COUNT(*) FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'general_proyectos_procesos');

SET @sql = IF(@tbl_exists = 0,
    'SELECT "skip backfill, table missing" AS info',
    'UPDATE `general_proyectos_procesos` SET `Area` = ''Construccion'' WHERE `Area` IS NULL OR TRIM(`Area`) = ''''');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =====================================================================
-- PARTE 2: Agregar columnas restriccion_pc_1..4 a tablas de proyectos
--           PRE-CONSTRUCCION (_programa y _programa_consolidado)
-- =====================================================================

DROP PROCEDURE IF EXISTS add_restriccion_pc_columns;

DELIMITER $$

CREATE PROCEDURE add_restriccion_pc_columns()
BEGIN
    DECLARE done INT DEFAULT 0;
    DECLARE v_prefix VARCHAR(128);
    DECLARE v_table_name VARCHAR(192);
    DECLARE v_has_table INT DEFAULT 0;
    DECLARE v_col_exists INT DEFAULT 0;

    DECLARE cur CURSOR FOR
        SELECT DISTINCT TRIM(`Base_de_Datos`) AS db_prefix
        FROM `general_proyectos_procesos`
        WHERE UPPER(TRIM(`Area`)) = 'PRE-CONSTRUCCION'
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

        -- ---------------------------------------------------------
        -- Procesar tabla {prefix}_programa
        -- ---------------------------------------------------------
        SET v_table_name = CONCAT(v_prefix, '_programa');

        SELECT COUNT(*) INTO v_has_table
        FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = v_table_name;

        IF v_has_table > 0 THEN
            -- restriccion_pc_1
            SELECT COUNT(*) INTO v_col_exists
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = v_table_name
              AND COLUMN_NAME = 'restriccion_pc_1';

            IF v_col_exists = 0 THEN
                SET @sql = CONCAT(
                    'ALTER TABLE `', v_table_name, '` ',
                    'ADD COLUMN `restriccion_pc_1` VARCHAR(10) DEFAULT ''0%'' ',
                    'AFTER `Modelo`'
                );
                PREPARE stmt FROM @sql;
                EXECUTE stmt;
                DEALLOCATE PREPARE stmt;
            END IF;

            -- restriccion_pc_2
            SELECT COUNT(*) INTO v_col_exists
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = v_table_name
              AND COLUMN_NAME = 'restriccion_pc_2';

            IF v_col_exists = 0 THEN
                SET @sql = CONCAT(
                    'ALTER TABLE `', v_table_name, '` ',
                    'ADD COLUMN `restriccion_pc_2` VARCHAR(10) DEFAULT ''0%'' ',
                    'AFTER `restriccion_pc_1`'
                );
                PREPARE stmt FROM @sql;
                EXECUTE stmt;
                DEALLOCATE PREPARE stmt;
            END IF;

            -- restriccion_pc_3
            SELECT COUNT(*) INTO v_col_exists
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = v_table_name
              AND COLUMN_NAME = 'restriccion_pc_3';

            IF v_col_exists = 0 THEN
                SET @sql = CONCAT(
                    'ALTER TABLE `', v_table_name, '` ',
                    'ADD COLUMN `restriccion_pc_3` VARCHAR(10) DEFAULT ''0%'' ',
                    'AFTER `restriccion_pc_2`'
                );
                PREPARE stmt FROM @sql;
                EXECUTE stmt;
                DEALLOCATE PREPARE stmt;
            END IF;

            -- restriccion_pc_4
            SELECT COUNT(*) INTO v_col_exists
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = v_table_name
              AND COLUMN_NAME = 'restriccion_pc_4';

            IF v_col_exists = 0 THEN
                SET @sql = CONCAT(
                    'ALTER TABLE `', v_table_name, '` ',
                    'ADD COLUMN `restriccion_pc_4` VARCHAR(10) DEFAULT ''0%'' ',
                    'AFTER `restriccion_pc_3`'
                );
                PREPARE stmt FROM @sql;
                EXECUTE stmt;
                DEALLOCATE PREPARE stmt;
            END IF;
        END IF;

        -- ---------------------------------------------------------
        -- Procesar tabla {prefix}_programa_consolidado
        -- ---------------------------------------------------------
        SET v_table_name = CONCAT(v_prefix, '_programa_consolidado');

        SELECT COUNT(*) INTO v_has_table
        FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = v_table_name;

        IF v_has_table > 0 THEN
            -- restriccion_pc_1
            SELECT COUNT(*) INTO v_col_exists
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = v_table_name
              AND COLUMN_NAME = 'restriccion_pc_1';

            IF v_col_exists = 0 THEN
                SET @sql = CONCAT(
                    'ALTER TABLE `', v_table_name, '` ',
                    'ADD COLUMN `restriccion_pc_1` VARCHAR(10) DEFAULT ''0%'' ',
                    'AFTER `Modelo`'
                );
                PREPARE stmt FROM @sql;
                EXECUTE stmt;
                DEALLOCATE PREPARE stmt;
            END IF;

            -- restriccion_pc_2
            SELECT COUNT(*) INTO v_col_exists
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = v_table_name
              AND COLUMN_NAME = 'restriccion_pc_2';

            IF v_col_exists = 0 THEN
                SET @sql = CONCAT(
                    'ALTER TABLE `', v_table_name, '` ',
                    'ADD COLUMN `restriccion_pc_2` VARCHAR(10) DEFAULT ''0%'' ',
                    'AFTER `restriccion_pc_1`'
                );
                PREPARE stmt FROM @sql;
                EXECUTE stmt;
                DEALLOCATE PREPARE stmt;
            END IF;

            -- restriccion_pc_3
            SELECT COUNT(*) INTO v_col_exists
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = v_table_name
              AND COLUMN_NAME = 'restriccion_pc_3';

            IF v_col_exists = 0 THEN
                SET @sql = CONCAT(
                    'ALTER TABLE `', v_table_name, '` ',
                    'ADD COLUMN `restriccion_pc_3` VARCHAR(10) DEFAULT ''0%'' ',
                    'AFTER `restriccion_pc_2`'
                );
                PREPARE stmt FROM @sql;
                EXECUTE stmt;
                DEALLOCATE PREPARE stmt;
            END IF;

            -- restriccion_pc_4
            SELECT COUNT(*) INTO v_col_exists
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = v_table_name
              AND COLUMN_NAME = 'restriccion_pc_4';

            IF v_col_exists = 0 THEN
                SET @sql = CONCAT(
                    'ALTER TABLE `', v_table_name, '` ',
                    'ADD COLUMN `restriccion_pc_4` VARCHAR(10) DEFAULT ''0%'' ',
                    'AFTER `restriccion_pc_3`'
                );
                PREPARE stmt FROM @sql;
                EXECUTE stmt;
                DEALLOCATE PREPARE stmt;
            END IF;
        END IF;

    END LOOP;

    CLOSE cur;
END$$

DELIMITER ;

CALL add_restriccion_pc_columns();

DROP PROCEDURE IF EXISTS add_restriccion_pc_columns;

-- =====================================================================
-- FIN Parche 20260624 — Area + Restricciones PC (idempotente)
-- =====================================================================
