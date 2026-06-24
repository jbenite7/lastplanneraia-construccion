-- =====================================================================
-- PARCHE PRODUCCION: COLUMNAS OC EN TABLA _actividades
-- FECHA: 2026-06-23
-- ALCANCE: solo proyectos con general_proyectos_procesos.Area = 'Construccion'
--
-- Agrega 10 columnas a todas las tablas {prefix}_actividades:
--   - OC1            VARCHAR(200) DEFAULT NULL
--   - paqueteOC1     VARCHAR(200) DEFAULT NULL
--   - OC2            VARCHAR(200) DEFAULT NULL
--   - paqueteOC2     VARCHAR(200) DEFAULT NULL
--   - OC3            VARCHAR(200) DEFAULT NULL
--   - paqueteOC3     VARCHAR(200) DEFAULT NULL
--   - OC4            VARCHAR(200) DEFAULT NULL
--   - paqueteOC4     VARCHAR(200) DEFAULT NULL
--   - OC5            VARCHAR(200) DEFAULT NULL
--   - paqueteOC5     VARCHAR(200) DEFAULT NULL
--
-- Politica:
-- - Aditivo e idempotente (IF NOT EXISTS).
-- - No elimina datos ni columnas.
-- - No depende de prefijos hardcodeados.
-- - Solo parchea prefijos validos [A-Za-z0-9_].
-- - Solo parchea proyectos que tienen tabla base:
--   <prefijo>_actividades.
-- =====================================================================

SET NAMES utf8mb4;

DROP PROCEDURE IF EXISTS add_oc_columns;

DELIMITER $$

CREATE PROCEDURE add_oc_columns()
BEGIN
    DECLARE done INT DEFAULT 0;
    DECLARE v_prefix VARCHAR(128);
    DECLARE v_table_name VARCHAR(192);
    DECLARE v_has_table INT DEFAULT 0;
    DECLARE v_col_exists INT DEFAULT 0;

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

        SET v_table_name = CONCAT(v_prefix, '_actividades');

        -- Verificar que la tabla existe
        SELECT COUNT(*) INTO v_has_table
        FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = v_table_name;

        IF v_has_table > 0 THEN

            -- ---------------------------------------------------------
            -- 1. Columna OC1
            -- ---------------------------------------------------------
            SELECT COUNT(*) INTO v_col_exists
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = v_table_name
              AND COLUMN_NAME = 'OC1';

            IF v_col_exists = 0 THEN
                SET @sql = CONCAT(
                    'ALTER TABLE `', v_table_name, '` ',
                    'ADD COLUMN `OC1` VARCHAR(200) DEFAULT NULL ',
                    'AFTER `paqueteMO5`'
                );
                PREPARE stmt FROM @sql;
                EXECUTE stmt;
                DEALLOCATE PREPARE stmt;
            END IF;

            -- ---------------------------------------------------------
            -- 2. Columna paqueteOC1
            -- ---------------------------------------------------------
            SELECT COUNT(*) INTO v_col_exists
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = v_table_name
              AND COLUMN_NAME = 'paqueteOC1';

            IF v_col_exists = 0 THEN
                SET @sql = CONCAT(
                    'ALTER TABLE `', v_table_name, '` ',
                    'ADD COLUMN `paqueteOC1` VARCHAR(200) DEFAULT NULL ',
                    'AFTER `OC1`'
                );
                PREPARE stmt FROM @sql;
                EXECUTE stmt;
                DEALLOCATE PREPARE stmt;
            END IF;

            -- ---------------------------------------------------------
            -- 3. Columna OC2
            -- ---------------------------------------------------------
            SELECT COUNT(*) INTO v_col_exists
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = v_table_name
              AND COLUMN_NAME = 'OC2';

            IF v_col_exists = 0 THEN
                SET @sql = CONCAT(
                    'ALTER TABLE `', v_table_name, '` ',
                    'ADD COLUMN `OC2` VARCHAR(200) DEFAULT NULL ',
                    'AFTER `paqueteOC1`'
                );
                PREPARE stmt FROM @sql;
                EXECUTE stmt;
                DEALLOCATE PREPARE stmt;
            END IF;

            -- ---------------------------------------------------------
            -- 4. Columna paqueteOC2
            -- ---------------------------------------------------------
            SELECT COUNT(*) INTO v_col_exists
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = v_table_name
              AND COLUMN_NAME = 'paqueteOC2';

            IF v_col_exists = 0 THEN
                SET @sql = CONCAT(
                    'ALTER TABLE `', v_table_name, '` ',
                    'ADD COLUMN `paqueteOC2` VARCHAR(200) DEFAULT NULL ',
                    'AFTER `OC2`'
                );
                PREPARE stmt FROM @sql;
                EXECUTE stmt;
                DEALLOCATE PREPARE stmt;
            END IF;

            -- ---------------------------------------------------------
            -- 5. Columna OC3
            -- ---------------------------------------------------------
            SELECT COUNT(*) INTO v_col_exists
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = v_table_name
              AND COLUMN_NAME = 'OC3';

            IF v_col_exists = 0 THEN
                SET @sql = CONCAT(
                    'ALTER TABLE `', v_table_name, '` ',
                    'ADD COLUMN `OC3` VARCHAR(200) DEFAULT NULL ',
                    'AFTER `paqueteOC2`'
                );
                PREPARE stmt FROM @sql;
                EXECUTE stmt;
                DEALLOCATE PREPARE stmt;
            END IF;

            -- ---------------------------------------------------------
            -- 6. Columna paqueteOC3
            -- ---------------------------------------------------------
            SELECT COUNT(*) INTO v_col_exists
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = v_table_name
              AND COLUMN_NAME = 'paqueteOC3';

            IF v_col_exists = 0 THEN
                SET @sql = CONCAT(
                    'ALTER TABLE `', v_table_name, '` ',
                    'ADD COLUMN `paqueteOC3` VARCHAR(200) DEFAULT NULL ',
                    'AFTER `OC3`'
                );
                PREPARE stmt FROM @sql;
                EXECUTE stmt;
                DEALLOCATE PREPARE stmt;
            END IF;

            -- ---------------------------------------------------------
            -- 7. Columna OC4
            -- ---------------------------------------------------------
            SELECT COUNT(*) INTO v_col_exists
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = v_table_name
              AND COLUMN_NAME = 'OC4';

            IF v_col_exists = 0 THEN
                SET @sql = CONCAT(
                    'ALTER TABLE `', v_table_name, '` ',
                    'ADD COLUMN `OC4` VARCHAR(200) DEFAULT NULL ',
                    'AFTER `paqueteOC3`'
                );
                PREPARE stmt FROM @sql;
                EXECUTE stmt;
                DEALLOCATE PREPARE stmt;
            END IF;

            -- ---------------------------------------------------------
            -- 8. Columna paqueteOC4
            -- ---------------------------------------------------------
            SELECT COUNT(*) INTO v_col_exists
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = v_table_name
              AND COLUMN_NAME = 'paqueteOC4';

            IF v_col_exists = 0 THEN
                SET @sql = CONCAT(
                    'ALTER TABLE `', v_table_name, '` ',
                    'ADD COLUMN `paqueteOC4` VARCHAR(200) DEFAULT NULL ',
                    'AFTER `OC4`'
                );
                PREPARE stmt FROM @sql;
                EXECUTE stmt;
                DEALLOCATE PREPARE stmt;
            END IF;

            -- ---------------------------------------------------------
            -- 9. Columna OC5
            -- ---------------------------------------------------------
            SELECT COUNT(*) INTO v_col_exists
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = v_table_name
              AND COLUMN_NAME = 'OC5';

            IF v_col_exists = 0 THEN
                SET @sql = CONCAT(
                    'ALTER TABLE `', v_table_name, '` ',
                    'ADD COLUMN `OC5` VARCHAR(200) DEFAULT NULL ',
                    'AFTER `paqueteOC4`'
                );
                PREPARE stmt FROM @sql;
                EXECUTE stmt;
                DEALLOCATE PREPARE stmt;
            END IF;

            -- ---------------------------------------------------------
            -- 10. Columna paqueteOC5
            -- ---------------------------------------------------------
            SELECT COUNT(*) INTO v_col_exists
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = v_table_name
              AND COLUMN_NAME = 'paqueteOC5';

            IF v_col_exists = 0 THEN
                SET @sql = CONCAT(
                    'ALTER TABLE `', v_table_name, '` ',
                    'ADD COLUMN `paqueteOC5` VARCHAR(200) DEFAULT NULL ',
                    'AFTER `OC5`'
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

CALL add_oc_columns();

DROP PROCEDURE IF EXISTS add_oc_columns;
