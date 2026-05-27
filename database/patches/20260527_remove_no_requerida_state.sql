-- Migration: Remove 'No Requerida' state from all projects
-- 'No Requerida' (Not Required) is no longer a valid state.
-- - Rows with null dates + no ejecutado become 'Sin Datos' (LpsService line 147)
-- - Rows with far-future dates become 'Actividad Futura' (LpsService lines 180/183)
-- The backend (LpsService) no longer produces 'No Requerida'.
-- Created: 2026-05-27

DROP PROCEDURE IF EXISTS sp_remove_no_requerida_state;
DELIMITER //
CREATE PROCEDURE sp_remove_no_requerida_state()
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

        -- A future batch recalculation will move null-date rows to 'Sin Datos'
        SET @sql = CONCAT(
            'UPDATE `', v_prefix, '_programa_consolidado` ',
            'SET Estado = \'Actividad Futura\' ',
            'WHERE Estado = \'No Requerida\''
        );
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END LOOP;
    CLOSE cur;
END //
DELIMITER ;

CALL sp_remove_no_requerida_state();
DROP PROCEDURE IF EXISTS sp_remove_no_requerida_state;
