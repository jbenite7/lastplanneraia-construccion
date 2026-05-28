-- Agrega columna fecha_ultimo_saneo a {prefix}_semanas_activas
-- Permite a sanear() saber si ya se ejecutó y si hay cambios nuevos desde entonces
DROP PROCEDURE IF EXISTS add_fecha_ultimo_saneo_col;
DELIMITER ;;
CREATE PROCEDURE add_fecha_ultimo_saneo_col()
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE v_prefix VARCHAR(255);
    DECLARE v_table_name VARCHAR(512);
    DECLARE v_table_exists INT;
    DECLARE cur CURSOR FOR SELECT DISTINCT TRIM(Base_de_Datos) FROM general_proyectos_procesos;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

    OPEN cur;
    read_loop: LOOP
        FETCH cur INTO v_prefix;
        IF done THEN LEAVE read_loop; END IF;

        SET v_table_name = CONCAT(v_prefix, '_semanas_activas');

        SELECT COUNT(*) INTO v_table_exists
        FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = v_table_name;

        IF v_table_exists > 0 THEN
            SET @sql = CONCAT(
                'ALTER TABLE `', v_table_name, '`
                 ADD COLUMN `fecha_ultimo_saneo` DATETIME NULL DEFAULT NULL
                 AFTER `fechaCierreCompromisos`'
            );

            PREPARE stmt FROM @sql;

            BEGIN
                DECLARE CONTINUE HANDLER FOR 1060 BEGIN END;
                EXECUTE stmt;
            END;

            DEALLOCATE PREPARE stmt;
        END IF;
    END LOOP;
    CLOSE cur;
END;;
DELIMITER ;
CALL add_fecha_ultimo_saneo_col();
DROP PROCEDURE IF EXISTS add_fecha_ultimo_saneo_col;
