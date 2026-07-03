SET NAMES utf8mb4;

DELIMITER $$

DROP PROCEDURE IF EXISTS add_contratos_quantity_column$$
CREATE PROCEDURE add_contratos_quantity_column(
  IN p_column_name varchar(64),
  IN p_after_column varchar(64)
)
BEGIN
  IF NOT EXISTS (
    SELECT 1
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'actividades'
      AND COLUMN_NAME = p_column_name
  ) THEN
    SET @sql = CONCAT(
      'ALTER TABLE `actividades` ADD COLUMN `',
      p_column_name,
      '` tinyint NOT NULL DEFAULT 1 AFTER `',
      p_after_column,
      '`'
    );
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
  END IF;
END$$

CALL add_contratos_quantity_column('cantidadSI1', 'paqueteSI1')$$
CALL add_contratos_quantity_column('cantidadSI2', 'paqueteSI2')$$
CALL add_contratos_quantity_column('cantidadSI3', 'paqueteSI3')$$
CALL add_contratos_quantity_column('cantidadSI4', 'paqueteSI4')$$
CALL add_contratos_quantity_column('cantidadSI5', 'paqueteSI5')$$
CALL add_contratos_quantity_column('cantidadS1', 'paqueteS1')$$
CALL add_contratos_quantity_column('cantidadS2', 'paqueteS2')$$
CALL add_contratos_quantity_column('cantidadS3', 'paqueteS3')$$
CALL add_contratos_quantity_column('cantidadS4', 'paqueteS4')$$
CALL add_contratos_quantity_column('cantidadS5', 'paqueteS5')$$
CALL add_contratos_quantity_column('cantidadMO1', 'paqueteMO1')$$
CALL add_contratos_quantity_column('cantidadMO2', 'paqueteMO2')$$
CALL add_contratos_quantity_column('cantidadMO3', 'paqueteMO3')$$
CALL add_contratos_quantity_column('cantidadMO4', 'paqueteMO4')$$
CALL add_contratos_quantity_column('cantidadMO5', 'paqueteMO5')$$
CALL add_contratos_quantity_column('cantidadOC1', 'paqueteOC1')$$
CALL add_contratos_quantity_column('cantidadOC2', 'paqueteOC2')$$
CALL add_contratos_quantity_column('cantidadOC3', 'paqueteOC3')$$
CALL add_contratos_quantity_column('cantidadOC4', 'paqueteOC4')$$
CALL add_contratos_quantity_column('cantidadOC5', 'paqueteOC5')$$

DROP PROCEDURE IF EXISTS add_contratos_quantity_column$$

DELIMITER ;

CREATE TABLE IF NOT EXISTS `contratos_trazabilidad` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `project_id` int NOT NULL,
  `actividad_id` int NOT NULL,
  `semana` int NOT NULL,
  `usuario` varchar(120) DEFAULT NULL,
  `origen` varchar(40) NOT NULL DEFAULT 'manual',
  `campos_cambiados` json NOT NULL,
  `antes` json DEFAULT NULL,
  `despues` json DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_contratos_traza_project_week` (`project_id`, `semana`),
  KEY `idx_contratos_traza_activity` (`project_id`, `actividad_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
