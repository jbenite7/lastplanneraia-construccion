-- Add default quantity to guided family contract package items.

SET @has_cantidad_default := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'general_pdc_family_contract_option_items'
      AND COLUMN_NAME = 'cantidad_default'
);

SET @add_cantidad_default_sql := IF(
    @has_cantidad_default = 0,
    'ALTER TABLE `general_pdc_family_contract_option_items` ADD COLUMN `cantidad_default` INT NOT NULL DEFAULT 1 AFTER `paquete_nombre`',
    'SELECT 1'
);

PREPARE add_cantidad_default_stmt FROM @add_cantidad_default_sql;
EXECUTE add_cantidad_default_stmt;
DEALLOCATE PREPARE add_cantidad_default_stmt;
