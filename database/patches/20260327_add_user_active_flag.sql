SET @tbl_exists = (
    SELECT COUNT(*)
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'general_usuarios'
);

SET @col_exists = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'general_usuarios'
      AND COLUMN_NAME = 'activo'
);

SET @sql = IF(
    @tbl_exists = 0,
    'SELECT "general_usuarios no existe, skip" AS info',
    IF(
        @col_exists = 0,
        'ALTER TABLE `general_usuarios` ADD COLUMN `activo` TINYINT(1) NOT NULL DEFAULT 1',
        'SELECT "activo already exists" AS info'
    )
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
