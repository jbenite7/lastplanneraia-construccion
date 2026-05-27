-- Migration: Remove 'Adelantada' state from all projects
-- 'Adelantada' (Advanced) is no longer a valid state.
-- All existing occurrences are merged into 'En Curso' (In Progress).
-- The backend (LpsService) no longer produces this state.
-- Created: 2026-05-27

-- Uses cursor over general_proyectos_procesos to patch ALL construction projects
-- (same pattern as 20260525_lps_drawers_construccion.sql)

DROP PROCEDURE IF EXISTS sp_remove_adelantada_state;
DELIMITER //
CREATE PROCEDURE sp_remove_adelantada_state()
BEGIN
    DECLARE v_prefix VARCHAR(100);
    DECLARE done INT DEFAULT FALSE;
    DECLARE cur CURSOR FOR
        SELECT Base_de_Datos
        FROM general_proyectos_procesos
        WHERE Activo = 1
          AND LENGTH(TRIM(Base_de_Datos)) > 0;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

    OPEN cur;
    read_loop: LOOP
        FETCH cur INTO v_prefix;
        IF done THEN
            LEAVE read_loop;
        END IF;

        SET @sql = CONCAT(
            'UPDATE `', v_prefix, '_programa_consolidado` ',
            'SET Estado = \'En Curso\' ',
            'WHERE Estado = \'Adelantada\''
        );
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END LOOP;
    CLOSE cur;
END //
DELIMITER ;

CALL sp_remove_adelantada_state();
DROP PROCEDURE IF EXISTS sp_remove_adelantada_state;
