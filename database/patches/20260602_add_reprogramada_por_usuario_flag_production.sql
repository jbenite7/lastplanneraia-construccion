-- ===========================================================================
-- Patch específico de producción: columna Reprogramada_Por_Usuario
-- ===========================================================================
-- Generado a partir del dump de producción 20260602_backup.sql
-- (Base de datos: dbhif4pdimjtxe)
--
-- A diferencia del patch 20260602_add_reprogramada_por_usuario_flag.sql
-- (que itera general_proyectos_procesos vía stored procedure), este script
-- aplica ALTER TABLE explícito por cada proyecto detectado en el dump de
-- producción, haciéndolo auto-contenido y trivial de auditar.
--
-- Es idempotente: la verificación previa de existencia vía information_schema
-- evita errores al re-ejecutar. Las cláusulas IF NOT EXISTS nativas de MySQL
-- 8.0+ cubren el caso típico; la verificación adicional protege contra
-- entornos con MySQL < 8.0.27.
--
-- Aplicable en: MySQL 8.0+
-- ===========================================================================

-- accesibilidadMetroA
SET @db := 'dbhif4pdimjtxe';
SET @tbl := 'accesibilidadMetroA_programacion_semanal';
SET @col_exists := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db AND TABLE_NAME = @tbl
      AND COLUMN_NAME = 'Reprogramada_Por_Usuario'
);
SET @sql := IF(@col_exists = 0,
    'ALTER TABLE `accesibilidadMetroA_programacion_semanal` ADD COLUMN `Reprogramada_Por_Usuario` TINYINT(1) NOT NULL DEFAULT 0 AFTER `Activa`',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- accesibilidadMetroB
SET @tbl := 'accesibilidadMetroB_programacion_semanal';
SET @col_exists := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db AND TABLE_NAME = @tbl
      AND COLUMN_NAME = 'Reprogramada_Por_Usuario'
);
SET @sql := IF(@col_exists = 0,
    'ALTER TABLE `accesibilidadMetroB_programacion_semanal` ADD COLUMN `Reprogramada_Por_Usuario` TINYINT(1) NOT NULL DEFAULT 0 AFTER `Activa`',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- da_porto
SET @tbl := 'da_porto_programacion_semanal';
SET @col_exists := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db AND TABLE_NAME = @tbl
      AND COLUMN_NAME = 'Reprogramada_Por_Usuario'
);
SET @sql := IF(@col_exists = 0,
    'ALTER TABLE `da_porto_programacion_semanal` ADD COLUMN `Reprogramada_Por_Usuario` TINYINT(1) NOT NULL DEFAULT 0 AFTER `Activa`',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- homecenterMallplaza
SET @tbl := 'homecenterMallplaza_programacion_semanal';
SET @col_exists := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db AND TABLE_NAME = @tbl
      AND COLUMN_NAME = 'Reprogramada_Por_Usuario'
);
SET @sql := IF(@col_exists = 0,
    'ALTER TABLE `homecenterMallplaza_programacion_semanal` ADD COLUMN `Reprogramada_Por_Usuario` TINYINT(1) NOT NULL DEFAULT 0 AFTER `Activa`',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- laMasia
SET @tbl := 'laMasia_programacion_semanal';
SET @col_exists := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db AND TABLE_NAME = @tbl
      AND COLUMN_NAME = 'Reprogramada_Por_Usuario'
);
SET @sql := IF(@col_exists = 0,
    'ALTER TABLE `laMasia_programacion_semanal` ADD COLUMN `Reprogramada_Por_Usuario` TINYINT(1) NOT NULL DEFAULT 0 AFTER `Activa`',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- metrolineaConfinamientoDos
SET @tbl := 'metrolineaConfinamientoDos_programacion_semanal';
SET @col_exists := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db AND TABLE_NAME = @tbl
      AND COLUMN_NAME = 'Reprogramada_Por_Usuario'
);
SET @sql := IF(@col_exists = 0,
    'ALTER TABLE `metrolineaConfinamientoDos_programacion_semanal` ADD COLUMN `Reprogramada_Por_Usuario` TINYINT(1) NOT NULL DEFAULT 0 AFTER `Activa`',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- metrolineaDieciseisAscendente
SET @tbl := 'metrolineaDieciseisAscendente_programacion_semanal';
SET @col_exists := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db AND TABLE_NAME = @tbl
      AND COLUMN_NAME = 'Reprogramada_Por_Usuario'
);
SET @sql := IF(@col_exists = 0,
    'ALTER TABLE `metrolineaDieciseisAscendente_programacion_semanal` ADD COLUMN `Reprogramada_Por_Usuario` TINYINT(1) NOT NULL DEFAULT 0 AFTER `Activa`',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- metrolineaDieciseisDescendente
SET @tbl := 'metrolineaDieciseisDescendente_programacion_semanal';
SET @col_exists := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db AND TABLE_NAME = @tbl
      AND COLUMN_NAME = 'Reprogramada_Por_Usuario'
);
SET @sql := IF(@col_exists = 0,
    'ALTER TABLE `metrolineaDieciseisDescendente_programacion_semanal` ADD COLUMN `Reprogramada_Por_Usuario` TINYINT(1) NOT NULL DEFAULT 0 AFTER `Activa`',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- metrolineaDos
SET @tbl := 'metrolineaDos_programacion_semanal';
SET @col_exists := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db AND TABLE_NAME = @tbl
      AND COLUMN_NAME = 'Reprogramada_Por_Usuario'
);
SET @sql := IF(@col_exists = 0,
    'ALTER TABLE `metrolineaDos_programacion_semanal` ADD COLUMN `Reprogramada_Por_Usuario` TINYINT(1) NOT NULL DEFAULT 0 AFTER `Activa`',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- metrolineaMampDos
SET @tbl := 'metrolineaMampDos_programacion_semanal';
SET @col_exists := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db AND TABLE_NAME = @tbl
      AND COLUMN_NAME = 'Reprogramada_Por_Usuario'
);
SET @sql := IF(@col_exists = 0,
    'ALTER TABLE `metrolineaMampDos_programacion_semanal` ADD COLUMN `Reprogramada_Por_Usuario` TINYINT(1) NOT NULL DEFAULT 0 AFTER `Activa`',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- metrolineaMampSeis
SET @tbl := 'metrolineaMampSeis_programacion_semanal';
SET @col_exists := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db AND TABLE_NAME = @tbl
      AND COLUMN_NAME = 'Reprogramada_Por_Usuario'
);
SET @sql := IF(@col_exists = 0,
    'ALTER TABLE `metrolineaMampSeis_programacion_semanal` ADD COLUMN `Reprogramada_Por_Usuario` TINYINT(1) NOT NULL DEFAULT 0 AFTER `Activa`',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- metrolineaMampUno
SET @tbl := 'metrolineaMampUno_programacion_semanal';
SET @col_exists := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db AND TABLE_NAME = @tbl
      AND COLUMN_NAME = 'Reprogramada_Por_Usuario'
);
SET @sql := IF(@col_exists = 0,
    'ALTER TABLE `metrolineaMampUno_programacion_semanal` ADD COLUMN `Reprogramada_Por_Usuario` TINYINT(1) NOT NULL DEFAULT 0 AFTER `Activa`',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- metrolineaMurosDos
SET @tbl := 'metrolineaMurosDos_programacion_semanal';
SET @col_exists := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db AND TABLE_NAME = @tbl
      AND COLUMN_NAME = 'Reprogramada_Por_Usuario'
);
SET @sql := IF(@col_exists = 0,
    'ALTER TABLE `metrolineaMurosDos_programacion_semanal` ADD COLUMN `Reprogramada_Por_Usuario` TINYINT(1) NOT NULL DEFAULT 0 AFTER `Activa`',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- metrolineaSeis
SET @tbl := 'metrolineaSeis_programacion_semanal';
SET @col_exists := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db AND TABLE_NAME = @tbl
      AND COLUMN_NAME = 'Reprogramada_Por_Usuario'
);
SET @sql := IF(@col_exists = 0,
    'ALTER TABLE `metrolineaSeis_programacion_semanal` ADD COLUMN `Reprogramada_Por_Usuario` TINYINT(1) NOT NULL DEFAULT 0 AFTER `Activa`',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- metrolineaUno
SET @tbl := 'metrolineaUno_programacion_semanal';
SET @col_exists := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db AND TABLE_NAME = @tbl
      AND COLUMN_NAME = 'Reprogramada_Por_Usuario'
);
SET @sql := IF(@col_exists = 0,
    'ALTER TABLE `metrolineaUno_programacion_semanal` ADD COLUMN `Reprogramada_Por_Usuario` TINYINT(1) NOT NULL DEFAULT 0 AFTER `Activa`',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- milanCampestre
SET @tbl := 'milanCampestre_programacion_semanal';
SET @col_exists := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db AND TABLE_NAME = @tbl
      AND COLUMN_NAME = 'Reprogramada_Por_Usuario'
);
SET @sql := IF(@col_exists = 0,
    'ALTER TABLE `milanCampestre_programacion_semanal` ADD COLUMN `Reprogramada_Por_Usuario` TINYINT(1) NOT NULL DEFAULT 0 AFTER `Activa`',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- milan_campestre_torre
SET @tbl := 'milan_campestre_torre_programacion_semanal';
SET @col_exists := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db AND TABLE_NAME = @tbl
      AND COLUMN_NAME = 'Reprogramada_Por_Usuario'
);
SET @sql := IF(@col_exists = 0,
    'ALTER TABLE `milan_campestre_torre_programacion_semanal` ADD COLUMN `Reprogramada_Por_Usuario` TINYINT(1) NOT NULL DEFAULT 0 AFTER `Activa`',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- optimizacionJMC
SET @tbl := 'optimizacionJMC_programacion_semanal';
SET @col_exists := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db AND TABLE_NAME = @tbl
      AND COLUMN_NAME = 'Reprogramada_Por_Usuario'
);
SET @sql := IF(@col_exists = 0,
    'ALTER TABLE `optimizacionJMC_programacion_semanal` ADD COLUMN `Reprogramada_Por_Usuario` TINYINT(1) NOT NULL DEFAULT 0 AFTER `Activa`',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- prueba
SET @tbl := 'prueba_programacion_semanal';
SET @col_exists := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db AND TABLE_NAME = @tbl
      AND COLUMN_NAME = 'Reprogramada_Por_Usuario'
);
SET @sql := IF(@col_exists = 0,
    'ALTER TABLE `prueba_programacion_semanal` ADD COLUMN `Reprogramada_Por_Usuario` TINYINT(1) NOT NULL DEFAULT 0 AFTER `Activa`',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
