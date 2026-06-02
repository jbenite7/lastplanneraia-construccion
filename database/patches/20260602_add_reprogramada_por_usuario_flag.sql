-- Agrega columna Reprogramada_Por_Usuario a {prefix}_programacion_semanal
-- Permite al cascade distinguir entre actividades autoprogramadas y actividades
-- reactivadas manualmente por el usuario desde el módulo CNP.
-- 0 = autoprogramada por el sistema (o desprogramada) → el cascade puede autodescomprometerla
-- 1 = reactivada manualmente por el usuario desde CNP → inmunidad ante autodescompromiso
DROP PROCEDURE IF EXISTS add_reprogramada_por_usuario_col;
DELIMITER ;;
CREATE PROCEDURE add_reprogramada_por_usuario_col()
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE v_prefix VARCHAR(255);
    DECLARE v_table_name VARCHAR(512);
    DECLARE v_table_exists INT;
    DECLARE v_column_exists INT;
    DECLARE cur CURSOR FOR SELECT DISTINCT TRIM(Base_de_Datos) FROM general_proyectos_procesos;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

    OPEN cur;
    read_loop: LOOP
        FETCH cur INTO v_prefix;
        IF done THEN LEAVE read_loop; END IF;

        SET v_table_name = CONCAT(v_prefix, '_programacion_semanal');

        SELECT COUNT(*) INTO v_table_exists
        FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = v_table_name;

        IF v_table_exists > 0 THEN
            SELECT COUNT(*) INTO v_column_exists
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = v_table_name
              AND COLUMN_NAME = 'Reprogramada_Por_Usuario';

            IF v_column_exists = 0 THEN
                SET @sql = CONCAT(
                    'ALTER TABLE `', v_table_name, '` ',
                    'ADD COLUMN `Reprogramada_Por_Usuario` TINYINT(1) NOT NULL DEFAULT 0 ',
                    'AFTER `Activa`'
                );

                PREPARE stmt FROM @sql;
                EXECUTE stmt;
                DEALLOCATE PREPARE stmt;
            END IF;
        END IF;
    END LOOP;
    CLOSE cur;
END;;
DELIMITER ;
CALL add_reprogramada_por_usuario_col();
DROP PROCEDURE IF EXISTS add_reprogramada_por_usuario_col;
