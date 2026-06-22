-- =====================================================================
-- PARCHE PRODUCCION: COLUMNAS TNP Y CP EN PROGRAMACION SEMANAL
-- FECHA: 2026-06-22
-- ALCANCE: solo proyectos con general_proyectos_procesos.Area = 'Construccion'
--
-- Agrega 4 columnas a todas las tablas {prefix}_programacion_semanal:
--   - Es_TNP           TINYINT(1)  NOT NULL DEFAULT 0   (bandera TNP)
--   - Categoria_CP     VARCHAR(100) DEFAULT NULL         (categoria CP)
--   - CP               VARCHAR(255) DEFAULT NULL         (codigo CP)
--   - Observaciones_CP TEXT         DEFAULT NULL         (notas CP)
--
-- Politica:
-- - Aditivo e idempotente (IF NOT EXISTS).
-- - No elimina datos ni columnas.
-- - No depende de prefijos hardcodeados.
-- - Solo parchea prefijos validos [A-Za-z0-9_].
-- - Solo parchea proyectos que tienen tabla base:
--   <prefijo>_programacion_semanal.
-- =====================================================================

SET NAMES utf8mb4;

DROP PROCEDURE IF EXISTS add_tnp_cp_columns;

DELIMITER $$

CREATE PROCEDURE add_tnp_cp_columns()
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

        SET v_table_name = CONCAT(v_prefix, '_programacion_semanal');

        -- Verificar que la tabla existe
        SELECT COUNT(*) INTO v_has_table
        FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = v_table_name;

        IF v_has_table > 0 THEN
            -- ---------------------------------------------------------
            -- 1. Columna Es_TNP (flag booleano TNP)
            -- ---------------------------------------------------------
            SELECT COUNT(*) INTO v_col_exists
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = v_table_name
              AND COLUMN_NAME = 'Es_TNP';

            IF v_col_exists = 0 THEN
                SET @sql = CONCAT(
                    'ALTER TABLE `', v_table_name, '` ',
                    'ADD COLUMN `Es_TNP` TINYINT(1) NOT NULL DEFAULT 0 ',
                    'AFTER `Activa`'
                );
                PREPARE stmt FROM @sql;
                EXECUTE stmt;
                DEALLOCATE PREPARE stmt;
            END IF;

            -- ---------------------------------------------------------
            -- 2. Columna Categoria_CP
            -- ---------------------------------------------------------
            SELECT COUNT(*) INTO v_col_exists
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = v_table_name
              AND COLUMN_NAME = 'Categoria_CP';

            IF v_col_exists = 0 THEN
                SET @sql = CONCAT(
                    'ALTER TABLE `', v_table_name, '` ',
                    'ADD COLUMN `Categoria_CP` VARCHAR(100) DEFAULT NULL ',
                    'AFTER `Es_TNP`'
                );
                PREPARE stmt FROM @sql;
                EXECUTE stmt;
                DEALLOCATE PREPARE stmt;
            END IF;

            -- ---------------------------------------------------------
            -- 3. Columna CP
            -- ---------------------------------------------------------
            SELECT COUNT(*) INTO v_col_exists
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = v_table_name
              AND COLUMN_NAME = 'CP';

            IF v_col_exists = 0 THEN
                SET @sql = CONCAT(
                    'ALTER TABLE `', v_table_name, '` ',
                    'ADD COLUMN `CP` VARCHAR(255) DEFAULT NULL ',
                    'AFTER `Categoria_CP`'
                );
                PREPARE stmt FROM @sql;
                EXECUTE stmt;
                DEALLOCATE PREPARE stmt;
            END IF;

            -- ---------------------------------------------------------
            -- 4. Columna Observaciones_CP
            -- ---------------------------------------------------------
            SELECT COUNT(*) INTO v_col_exists
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = v_table_name
              AND COLUMN_NAME = 'Observaciones_CP';

            IF v_col_exists = 0 THEN
                SET @sql = CONCAT(
                    'ALTER TABLE `', v_table_name, '` ',
                    'ADD COLUMN `Observaciones_CP` TEXT DEFAULT NULL ',
                    'AFTER `CP`'
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

CALL add_tnp_cp_columns();

DROP PROCEDURE IF EXISTS add_tnp_cp_columns;
