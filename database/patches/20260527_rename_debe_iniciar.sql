-- Migration: Rename 'Debe Iniciar esta Semana' → 'Debe Iniciar' and remove compound state
-- 'Debe Iniciar esta Semana' (Must Start This Week) is renamed to 'Debe Iniciar' for brevity.
-- 'Debe Iniciar esta Semana y Restricciones Pendientes' is eliminated entirely —
--   actividades with pending restrictions are distinguished by the restriction alert system,
--   not by a separate state string.
-- The backend (LpsService) now produces 'Debe Iniciar'.
-- Created: 2026-05-27

DROP PROCEDURE IF EXISTS sp_rename_debe_iniciar;
DELIMITER //
CREATE PROCEDURE sp_rename_debe_iniciar()
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
            'SET Estado = \'Debe Iniciar\' ',
            'WHERE Estado = \'Debe Iniciar esta Semana\' ',
            'OR Estado = \'Debe Iniciar esta Semana y Restricciones Pendientes\''
        );
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END LOOP;
    CLOSE cur;
END //
DELIMITER ;

CALL sp_rename_debe_iniciar();
DROP PROCEDURE IF EXISTS sp_rename_debe_iniciar;
