-- =====================================================================
-- PARCHE PRODUCCION: CONSOLIDAR CNC/CNP EN general_cnc
-- FECHA: 2026-06-24
-- ALCANCE: general_cnc (catalogo global) + DROP {prefix}_cnc/_cnp
--
-- Problema:
--   CNC y CNP comparten la misma estructura y logica. El catalogo
--   global ya es general_cnc (lo usan CncApiController::reasons() y
--   ProgramacionSemanalController). Los registros van en columnas de
--   _programacion_semanal (Categoria_CNC/CNC/Observaciones_CNC y
--   Categoria_CNP/CNP/Observaciones_CNP).
--
--   Las tablas per-proyecto {prefix}_cnc y {prefix}_cnp son
--   VESTIGIALES: ningun codigo PHP las lee. Las creo
--   createPreConstructionTables() para PC pero el sistema nunca las
--   uso. Solo da_aeropuerto_pc las tiene, con 2 filas en cada una.
--
-- Accion:
--   1. Migrar causas unicas de {prefix}_cnc/_cnp a general_cnc
--      (INSERT ... WHERE NOT EXISTS para idempotencia).
--   2. DROP TABLE IF EXISTS {prefix}_cnc y {prefix}_cnp para todos
--      los proyectos que las tengan.
--   3. El codigo (createPreConstructionTables) se actualiza aparte
--      para que no vuelva a crearlas.
--
-- Politica:
-- - No pierde datos: migrar antes de borrar.
-- - Idempotente: INSERT WHERE NOT EXISTS + DROP IF EXISTS.
-- =====================================================================

SET NAMES utf8mb4;

-- =====================================================================
-- PARTE 1: Migrar causas unicas a general_cnc
--          (guardado con information_schema: si la tabla fuente ya
--           fue borrada en una ejecucion previa, se skip sin error)
-- =====================================================================

DROP PROCEDURE IF EXISTS migrar_cnc_cnp_a_general;

DELIMITER $$

CREATE PROCEDURE migrar_cnc_cnp_a_general()
BEGIN
    DECLARE v_has_table INT DEFAULT 0;

    -- 1a. Causas de {prefix}_cnc (CNC) que no existen en general_cnc
    SELECT COUNT(*) INTO v_has_table
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'da_aeropuerto_pc_cnc';

    IF v_has_table > 0 THEN
        SET @sql = CONCAT(
            'INSERT INTO `general_cnc` (`Categoria_CNC`, `CNC`, `Area`) ',
            'SELECT DISTINCT t.categoria, t.causa, ''Pre-Construccion'' ',
            'FROM `da_aeropuerto_pc_cnc` t ',
            'WHERE NOT EXISTS (',
            '  SELECT 1 FROM `general_cnc` g ',
            '  WHERE g.Categoria_CNC = t.categoria ',
            '    AND g.CNC = t.causa ',
            '    AND g.Area = ''Pre-Construccion''',
            ')'
        );
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;

    -- 1b. Causas de {prefix}_cnp (CNP) que no existen en general_cnc
    --     CNC y CNP comparten catalogo: la causa de CNP se inserta como
    --     una causa mas del catalogo global.
    SELECT COUNT(*) INTO v_has_table
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'da_aeropuerto_pc_cnp';

    IF v_has_table > 0 THEN
        SET @sql = CONCAT(
            'INSERT INTO `general_cnc` (`Categoria_CNC`, `CNC`, `Area`) ',
            'SELECT DISTINCT t.categoria, t.causa, ''Pre-Construccion'' ',
            'FROM `da_aeropuerto_pc_cnp` t ',
            'WHERE NOT EXISTS (',
            '  SELECT 1 FROM `general_cnc` g ',
            '  WHERE g.Categoria_CNC = t.categoria ',
            '    AND g.CNC = t.causa ',
            '    AND g.Area = ''Pre-Construccion''',
            ')'
        );
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$

DELIMITER ;

CALL migrar_cnc_cnp_a_general();

DROP PROCEDURE IF EXISTS migrar_cnc_cnp_a_general;

-- =====================================================================
-- PARTE 2: DROP tablas per-proyecto _cnc y _cnp
-- =====================================================================

DROP PROCEDURE IF EXISTS drop_per_project_cnc_cnp;

DELIMITER $$

CREATE PROCEDURE drop_per_project_cnc_cnp()
BEGIN
    DECLARE done INT DEFAULT 0;
    DECLARE v_prefix VARCHAR(128);
    DECLARE v_table_name VARCHAR(192);
    DECLARE v_has_table INT DEFAULT 0;

    DECLARE cur CURSOR FOR
        SELECT DISTINCT TRIM(`Base_de_Datos`) AS db_prefix
        FROM `general_proyectos_procesos`
        WHERE `Base_de_Datos` IS NOT NULL
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

        -- DROP {prefix}_cnc si existe
        SET v_table_name = CONCAT(v_prefix, '_cnc');
        SELECT COUNT(*) INTO v_has_table
        FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = v_table_name;

        IF v_has_table > 0 THEN
            SET @sql = CONCAT('DROP TABLE `', v_table_name, '`');
            PREPARE stmt FROM @sql;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        END IF;

        -- DROP {prefix}_cnp si existe
        SET v_table_name = CONCAT(v_prefix, '_cnp');
        SELECT COUNT(*) INTO v_has_table
        FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = v_table_name;

        IF v_has_table > 0 THEN
            SET @sql = CONCAT('DROP TABLE `', v_table_name, '`');
            PREPARE stmt FROM @sql;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        END IF;

    END LOOP;

    CLOSE cur;
END$$

DELIMITER ;

CALL drop_per_project_cnc_cnp();

DROP PROCEDURE IF EXISTS drop_per_project_cnc_cnp;

-- =====================================================================
-- FIN Parche 20260624 — Consolidar CNC/CNP en general_cnc
-- =====================================================================
