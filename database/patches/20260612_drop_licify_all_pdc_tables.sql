-- Patch: Eliminar columnas Licify en todas las tablas PDC
-- Fecha: 2026-06-12
-- Actualizado: 2026-06-12 — sin DELIMITER, compatible con phpMyAdmin
--
-- Politica:
-- - Idempotente: verifica existencia via information_schema antes de ALTER.
-- - No usa DELIMITER ni CREATE PROCEDURE.
-- - Compatible con phpMyAdmin, Adminer, mysql CLI, y cualquier runner SQL.
-- - Usa DATABASE() despues de USE explicito para evitar information_schema.

USE `dbhif4pdimjtxe`;

SELECT DATABASE() AS db_objetivo_patch_licify;

-- ============================================================================
-- PARTE 1: general_dias_procesos_contratacion.diasIngresoLicify
-- ============================================================================

SET @col_exists := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'general_dias_procesos_contratacion'
      AND COLUMN_NAME = 'diasIngresoLicify'
);
SET @sql := IF(@col_exists = 1,
    'ALTER TABLE `general_dias_procesos_contratacion` DROP COLUMN `diasIngresoLicify`',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ============================================================================
-- PARTE 2: general_informe_pdc
-- ============================================================================

SET @col_exists := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'general_informe_pdc'
      AND COLUMN_NAME = 'diasIngresoLicify'
);
SET @sql := IF(@col_exists = 1,
    'ALTER TABLE `general_informe_pdc` DROP COLUMN `diasIngresoLicify`',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'general_informe_pdc'
      AND COLUMN_NAME = 'fechaIngresoLicify'
);
SET @sql := IF(@col_exists = 1,
    'ALTER TABLE `general_informe_pdc` DROP COLUMN `fechaIngresoLicify`',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'general_informe_pdc'
      AND COLUMN_NAME = 'fechaRealIngresoLicify'
);
SET @sql := IF(@col_exists = 1,
    'ALTER TABLE `general_informe_pdc` DROP COLUMN `fechaRealIngresoLicify`',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ============================================================================
-- PARTE 3: Columnas Licify en tablas {prefix}_pdc por proyecto
-- ============================================================================

-- accesibilidadMetroA_pdc
SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'accesibilidadMetroA_pdc' AND COLUMN_NAME = 'fechaIngresoLicify');
SET @sql := IF(@col_exists = 1, 'ALTER TABLE `accesibilidadMetroA_pdc` DROP COLUMN `fechaIngresoLicify`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'accesibilidadMetroA_pdc' AND COLUMN_NAME = 'diasIngresoLicify');
SET @sql := IF(@col_exists = 1, 'ALTER TABLE `accesibilidadMetroA_pdc` DROP COLUMN `diasIngresoLicify`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'accesibilidadMetroA_pdc' AND COLUMN_NAME = 'fechaRealIngresoLicify');
SET @sql := IF(@col_exists = 1, 'ALTER TABLE `accesibilidadMetroA_pdc` DROP COLUMN `fechaRealIngresoLicify`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- accesibilidadMetroB_pdc
SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'accesibilidadMetroB_pdc' AND COLUMN_NAME = 'fechaIngresoLicify');
SET @sql := IF(@col_exists = 1, 'ALTER TABLE `accesibilidadMetroB_pdc` DROP COLUMN `fechaIngresoLicify`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'accesibilidadMetroB_pdc' AND COLUMN_NAME = 'diasIngresoLicify');
SET @sql := IF(@col_exists = 1, 'ALTER TABLE `accesibilidadMetroB_pdc` DROP COLUMN `diasIngresoLicify`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'accesibilidadMetroB_pdc' AND COLUMN_NAME = 'fechaRealIngresoLicify');
SET @sql := IF(@col_exists = 1, 'ALTER TABLE `accesibilidadMetroB_pdc` DROP COLUMN `fechaRealIngresoLicify`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- da_porto_pdc
SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'da_porto_pdc' AND COLUMN_NAME = 'fechaIngresoLicify');
SET @sql := IF(@col_exists = 1, 'ALTER TABLE `da_porto_pdc` DROP COLUMN `fechaIngresoLicify`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'da_porto_pdc' AND COLUMN_NAME = 'diasIngresoLicify');
SET @sql := IF(@col_exists = 1, 'ALTER TABLE `da_porto_pdc` DROP COLUMN `diasIngresoLicify`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'da_porto_pdc' AND COLUMN_NAME = 'fechaRealIngresoLicify');
SET @sql := IF(@col_exists = 1, 'ALTER TABLE `da_porto_pdc` DROP COLUMN `fechaRealIngresoLicify`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- homecenterMallplaza_pdc
SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'homecenterMallplaza_pdc' AND COLUMN_NAME = 'fechaIngresoLicify');
SET @sql := IF(@col_exists = 1, 'ALTER TABLE `homecenterMallplaza_pdc` DROP COLUMN `fechaIngresoLicify`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'homecenterMallplaza_pdc' AND COLUMN_NAME = 'diasIngresoLicify');
SET @sql := IF(@col_exists = 1, 'ALTER TABLE `homecenterMallplaza_pdc` DROP COLUMN `diasIngresoLicify`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'homecenterMallplaza_pdc' AND COLUMN_NAME = 'fechaRealIngresoLicify');
SET @sql := IF(@col_exists = 1, 'ALTER TABLE `homecenterMallplaza_pdc` DROP COLUMN `fechaRealIngresoLicify`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- laMasia_pdc
SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'laMasia_pdc' AND COLUMN_NAME = 'fechaIngresoLicify');
SET @sql := IF(@col_exists = 1, 'ALTER TABLE `laMasia_pdc` DROP COLUMN `fechaIngresoLicify`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'laMasia_pdc' AND COLUMN_NAME = 'diasIngresoLicify');
SET @sql := IF(@col_exists = 1, 'ALTER TABLE `laMasia_pdc` DROP COLUMN `diasIngresoLicify`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'laMasia_pdc' AND COLUMN_NAME = 'fechaRealIngresoLicify');
SET @sql := IF(@col_exists = 1, 'ALTER TABLE `laMasia_pdc` DROP COLUMN `fechaRealIngresoLicify`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- metrolineaConfinamientoDos_pdc
SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'metrolineaConfinamientoDos_pdc' AND COLUMN_NAME = 'fechaIngresoLicify');
SET @sql := IF(@col_exists = 1, 'ALTER TABLE `metrolineaConfinamientoDos_pdc` DROP COLUMN `fechaIngresoLicify`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'metrolineaConfinamientoDos_pdc' AND COLUMN_NAME = 'diasIngresoLicify');
SET @sql := IF(@col_exists = 1, 'ALTER TABLE `metrolineaConfinamientoDos_pdc` DROP COLUMN `diasIngresoLicify`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'metrolineaConfinamientoDos_pdc' AND COLUMN_NAME = 'fechaRealIngresoLicify');
SET @sql := IF(@col_exists = 1, 'ALTER TABLE `metrolineaConfinamientoDos_pdc` DROP COLUMN `fechaRealIngresoLicify`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- metrolineaDieciseisAscendente_pdc
SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'metrolineaDieciseisAscendente_pdc' AND COLUMN_NAME = 'fechaIngresoLicify');
SET @sql := IF(@col_exists = 1, 'ALTER TABLE `metrolineaDieciseisAscendente_pdc` DROP COLUMN `fechaIngresoLicify`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'metrolineaDieciseisAscendente_pdc' AND COLUMN_NAME = 'diasIngresoLicify');
SET @sql := IF(@col_exists = 1, 'ALTER TABLE `metrolineaDieciseisAscendente_pdc` DROP COLUMN `diasIngresoLicify`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'metrolineaDieciseisAscendente_pdc' AND COLUMN_NAME = 'fechaRealIngresoLicify');
SET @sql := IF(@col_exists = 1, 'ALTER TABLE `metrolineaDieciseisAscendente_pdc` DROP COLUMN `fechaRealIngresoLicify`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- metrolineaDieciseisDescendente_pdc
SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'metrolineaDieciseisDescendente_pdc' AND COLUMN_NAME = 'fechaIngresoLicify');
SET @sql := IF(@col_exists = 1, 'ALTER TABLE `metrolineaDieciseisDescendente_pdc` DROP COLUMN `fechaIngresoLicify`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'metrolineaDieciseisDescendente_pdc' AND COLUMN_NAME = 'diasIngresoLicify');
SET @sql := IF(@col_exists = 1, 'ALTER TABLE `metrolineaDieciseisDescendente_pdc` DROP COLUMN `diasIngresoLicify`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'metrolineaDieciseisDescendente_pdc' AND COLUMN_NAME = 'fechaRealIngresoLicify');
SET @sql := IF(@col_exists = 1, 'ALTER TABLE `metrolineaDieciseisDescendente_pdc` DROP COLUMN `fechaRealIngresoLicify`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- metrolineaDos_pdc
SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'metrolineaDos_pdc' AND COLUMN_NAME = 'fechaIngresoLicify');
SET @sql := IF(@col_exists = 1, 'ALTER TABLE `metrolineaDos_pdc` DROP COLUMN `fechaIngresoLicify`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'metrolineaDos_pdc' AND COLUMN_NAME = 'diasIngresoLicify');
SET @sql := IF(@col_exists = 1, 'ALTER TABLE `metrolineaDos_pdc` DROP COLUMN `diasIngresoLicify`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'metrolineaDos_pdc' AND COLUMN_NAME = 'fechaRealIngresoLicify');
SET @sql := IF(@col_exists = 1, 'ALTER TABLE `metrolineaDos_pdc` DROP COLUMN `fechaRealIngresoLicify`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- metrolineaMampDos_pdc
SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'metrolineaMampDos_pdc' AND COLUMN_NAME = 'fechaIngresoLicify');
SET @sql := IF(@col_exists = 1, 'ALTER TABLE `metrolineaMampDos_pdc` DROP COLUMN `fechaIngresoLicify`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'metrolineaMampDos_pdc' AND COLUMN_NAME = 'diasIngresoLicify');
SET @sql := IF(@col_exists = 1, 'ALTER TABLE `metrolineaMampDos_pdc` DROP COLUMN `diasIngresoLicify`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'metrolineaMampDos_pdc' AND COLUMN_NAME = 'fechaRealIngresoLicify');
SET @sql := IF(@col_exists = 1, 'ALTER TABLE `metrolineaMampDos_pdc` DROP COLUMN `fechaRealIngresoLicify`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- metrolineaMampSeis_pdc
SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'metrolineaMampSeis_pdc' AND COLUMN_NAME = 'fechaIngresoLicify');
SET @sql := IF(@col_exists = 1, 'ALTER TABLE `metrolineaMampSeis_pdc` DROP COLUMN `fechaIngresoLicify`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'metrolineaMampSeis_pdc' AND COLUMN_NAME = 'diasIngresoLicify');
SET @sql := IF(@col_exists = 1, 'ALTER TABLE `metrolineaMampSeis_pdc` DROP COLUMN `diasIngresoLicify`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'metrolineaMampSeis_pdc' AND COLUMN_NAME = 'fechaRealIngresoLicify');
SET @sql := IF(@col_exists = 1, 'ALTER TABLE `metrolineaMampSeis_pdc` DROP COLUMN `fechaRealIngresoLicify`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- metrolineaMampUno_pdc
SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'metrolineaMampUno_pdc' AND COLUMN_NAME = 'fechaIngresoLicify');
SET @sql := IF(@col_exists = 1, 'ALTER TABLE `metrolineaMampUno_pdc` DROP COLUMN `fechaIngresoLicify`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'metrolineaMampUno_pdc' AND COLUMN_NAME = 'diasIngresoLicify');
SET @sql := IF(@col_exists = 1, 'ALTER TABLE `metrolineaMampUno_pdc` DROP COLUMN `diasIngresoLicify`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'metrolineaMampUno_pdc' AND COLUMN_NAME = 'fechaRealIngresoLicify');
SET @sql := IF(@col_exists = 1, 'ALTER TABLE `metrolineaMampUno_pdc` DROP COLUMN `fechaRealIngresoLicify`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- metrolineaMurosDos_pdc
SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'metrolineaMurosDos_pdc' AND COLUMN_NAME = 'fechaIngresoLicify');
SET @sql := IF(@col_exists = 1, 'ALTER TABLE `metrolineaMurosDos_pdc` DROP COLUMN `fechaIngresoLicify`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'metrolineaMurosDos_pdc' AND COLUMN_NAME = 'diasIngresoLicify');
SET @sql := IF(@col_exists = 1, 'ALTER TABLE `metrolineaMurosDos_pdc` DROP COLUMN `diasIngresoLicify`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'metrolineaMurosDos_pdc' AND COLUMN_NAME = 'fechaRealIngresoLicify');
SET @sql := IF(@col_exists = 1, 'ALTER TABLE `metrolineaMurosDos_pdc` DROP COLUMN `fechaRealIngresoLicify`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- metrolineaSeis_pdc
SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'metrolineaSeis_pdc' AND COLUMN_NAME = 'fechaIngresoLicify');
SET @sql := IF(@col_exists = 1, 'ALTER TABLE `metrolineaSeis_pdc` DROP COLUMN `fechaIngresoLicify`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'metrolineaSeis_pdc' AND COLUMN_NAME = 'diasIngresoLicify');
SET @sql := IF(@col_exists = 1, 'ALTER TABLE `metrolineaSeis_pdc` DROP COLUMN `diasIngresoLicify`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'metrolineaSeis_pdc' AND COLUMN_NAME = 'fechaRealIngresoLicify');
SET @sql := IF(@col_exists = 1, 'ALTER TABLE `metrolineaSeis_pdc` DROP COLUMN `fechaRealIngresoLicify`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- metrolineaUno_pdc
SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'metrolineaUno_pdc' AND COLUMN_NAME = 'fechaIngresoLicify');
SET @sql := IF(@col_exists = 1, 'ALTER TABLE `metrolineaUno_pdc` DROP COLUMN `fechaIngresoLicify`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'metrolineaUno_pdc' AND COLUMN_NAME = 'diasIngresoLicify');
SET @sql := IF(@col_exists = 1, 'ALTER TABLE `metrolineaUno_pdc` DROP COLUMN `diasIngresoLicify`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'metrolineaUno_pdc' AND COLUMN_NAME = 'fechaRealIngresoLicify');
SET @sql := IF(@col_exists = 1, 'ALTER TABLE `metrolineaUno_pdc` DROP COLUMN `fechaRealIngresoLicify`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- milanCampestre_pdc
SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'milanCampestre_pdc' AND COLUMN_NAME = 'fechaIngresoLicify');
SET @sql := IF(@col_exists = 1, 'ALTER TABLE `milanCampestre_pdc` DROP COLUMN `fechaIngresoLicify`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'milanCampestre_pdc' AND COLUMN_NAME = 'diasIngresoLicify');
SET @sql := IF(@col_exists = 1, 'ALTER TABLE `milanCampestre_pdc` DROP COLUMN `diasIngresoLicify`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'milanCampestre_pdc' AND COLUMN_NAME = 'fechaRealIngresoLicify');
SET @sql := IF(@col_exists = 1, 'ALTER TABLE `milanCampestre_pdc` DROP COLUMN `fechaRealIngresoLicify`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- milan_campestre_torre_pdc
SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'milan_campestre_torre_pdc' AND COLUMN_NAME = 'fechaIngresoLicify');
SET @sql := IF(@col_exists = 1, 'ALTER TABLE `milan_campestre_torre_pdc` DROP COLUMN `fechaIngresoLicify`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'milan_campestre_torre_pdc' AND COLUMN_NAME = 'diasIngresoLicify');
SET @sql := IF(@col_exists = 1, 'ALTER TABLE `milan_campestre_torre_pdc` DROP COLUMN `diasIngresoLicify`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'milan_campestre_torre_pdc' AND COLUMN_NAME = 'fechaRealIngresoLicify');
SET @sql := IF(@col_exists = 1, 'ALTER TABLE `milan_campestre_torre_pdc` DROP COLUMN `fechaRealIngresoLicify`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- optimizacionJMC_pdc
SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'optimizacionJMC_pdc' AND COLUMN_NAME = 'fechaIngresoLicify');
SET @sql := IF(@col_exists = 1, 'ALTER TABLE `optimizacionJMC_pdc` DROP COLUMN `fechaIngresoLicify`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'optimizacionJMC_pdc' AND COLUMN_NAME = 'diasIngresoLicify');
SET @sql := IF(@col_exists = 1, 'ALTER TABLE `optimizacionJMC_pdc` DROP COLUMN `diasIngresoLicify`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'optimizacionJMC_pdc' AND COLUMN_NAME = 'fechaRealIngresoLicify');
SET @sql := IF(@col_exists = 1, 'ALTER TABLE `optimizacionJMC_pdc` DROP COLUMN `fechaRealIngresoLicify`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- prueba_pdc
SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'prueba_pdc' AND COLUMN_NAME = 'fechaIngresoLicify');
SET @sql := IF(@col_exists = 1, 'ALTER TABLE `prueba_pdc` DROP COLUMN `fechaIngresoLicify`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'prueba_pdc' AND COLUMN_NAME = 'diasIngresoLicify');
SET @sql := IF(@col_exists = 1, 'ALTER TABLE `prueba_pdc` DROP COLUMN `diasIngresoLicify`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'prueba_pdc' AND COLUMN_NAME = 'fechaRealIngresoLicify');
SET @sql := IF(@col_exists = 1, 'ALTER TABLE `prueba_pdc` DROP COLUMN `fechaRealIngresoLicify`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ============================================================================
-- VERIFICACION: debe retornar 0
-- ============================================================================

SELECT DATABASE() AS db_verificada, COUNT(*) AS columnas_licify_restantes
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND COLUMN_NAME IN ('fechaIngresoLicify', 'diasIngresoLicify', 'fechaRealIngresoLicify');
