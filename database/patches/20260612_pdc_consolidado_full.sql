-- ----------------------------------------------------------------------------
-- PDC Consolidado Full - Migración Fase 1 → Fase 2
-- Fecha: 2026-06-12
-- Propósito: Drop tablas legacy, cleanup columnas Licify, validar tablas/core
-- Idempotencia: SÍ (DROP/CREATE IF NOT EXISTS, INSERT IGNORE, dynamic SQL)
-- ----------------------------------------------------------------------------

-- ============================================================================
-- PARTE 1: RBAC Permissions (si no existen)
-- ============================================================================
INSERT IGNORE INTO rbac_permissions (permission_key, module_name, action_name, description, is_write, is_sensitive)
VALUES ('lps.pdc.auto_generar', 'pdc', 'auto_generar', 'Permite auto-generar paquetes PDC desde sugerencias', 1, 0);

INSERT IGNORE INTO rbac_role_permissions (role_code, permission_key, allowed, source)
SELECT code, 'lps.pdc.auto_generar', 1, 'migration_patch_20260612'
FROM rbac_roles WHERE code IN ('A', 'D', 'OT');

-- ============================================================================
-- PARTE 2: Drop Tablas Legacy Plantilla (Fase 1 → 2)
-- Orden: hijos primero, luego padres
-- ============================================================================
DROP TABLE IF EXISTS general_pdc_plantilla_items;
DROP TABLE IF EXISTS general_pdc_plantillas;
DROP TABLE IF EXISTS general_pdc_categoria_recurso;

-- ============================================================================
-- PARTE 3: Cleanup Columnas Licify (dynamic SQL para tablas *_pdc)
-- ============================================================================
-- Drop columnas de tablas project_pdc usando cursor dinámico
SET @sql_stmt VARCHAR(1000);
SET @tabla VARCHAR(64);
SET @columna VARCHAR(64);

DECLARE cur CURSOR FOR
  SELECT TABLE_NAME, COLUMN_NAME
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME LIKE '%_pdc'
    AND TABLE_NAME NOT LIKE 'general_%'
    AND COLUMN_NAME IN ('fechaIngresoLicify', 'diasIngresoLicify', 'fechaRealIngresoLicify');

OPEN cur;
read_loop: LOOP
  FETCH cur INTO @tabla, @columna;
  IF done THEN LEAVE read_loop; END IF;
  SET @sql_stmt = CONCAT('ALTER TABLE `', @tabla, '` DROP COLUMN `', @columna, '`');
  PREPARE stmt FROM @sql_stmt;
  EXECUTE stmt;
  DEALLOCATE PREPARE stmt;
END LOOP;
CLOSE cur;

-- Drop columnas de general_dias_procesos_contratacion si existen
SET @sql_stmt2 VARCHAR(1000);
SET @columna2 VARCHAR(64);

DECLARE cur2 CURSOR FOR
  SELECT COLUMN_NAME
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'general_dias_procesos_contratacion'
    AND COLUMN_NAME IN ('fechaIngresoLicify', 'diasIngresoLicify', 'fechaRealIngresoLicify');

OPEN cur2;
read_loop2: LOOP
  FETCH cur2 INTO @columna2;
  IF done THEN LEAVE read_loop2; END IF;
  SET @sql_stmt2 = CONCAT('ALTER TABLE `general_dias_procesos_contratacion` DROP COLUMN `', @columna2, '`');
  PREPARE stmt2 FROM @sql_stmt2;
  EXECUTE stmt2;
  DEALLOCATE PREPARE stmt2;
END LOOP;
CLOSE cur2;

-- ============================================================================
-- PARTE 4: Validaciones y Health Check (solo lectura)
-- ============================================================================
SELECT '--- VALIDACIÓN: Tablas core PDC existen ---' AS seccion;
SELECT COUNT(*) AS familias FROM general_pdc_familias;
SELECT COUNT(*) AS activity_rules FROM general_pdc_activity_rules;
SELECT COUNT(*) AS paquete_aliases FROM general_pdc_paquete_aliases;
SELECT COUNT(*) AS family_contract_options FROM general_pdc_family_contract_options;
SELECT COUNT(*) AS family_contract_option_items FROM general_pdc_family_contract_option_items;
SELECT COUNT(*) AS project_family_strategy FROM general_pdc_project_family_strategy;
SELECT COUNT(*) AS dias_defaults_categoria FROM general_dias_defaults_categoria;

SELECT '--- VALIDACIÓN: Tablas legacy eliminadas ---' AS seccion;
SELECT
  (SELECT COUNT(*) FROM information_schema.tables WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'general_pdc_plantillas') AS plantillas,
  (SELECT COUNT(*) FROM information_schema.tables WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'general_pdc_plantilla_items') AS plantilla_items,
  (SELECT COUNT(*) FROM information_schema.tables WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'general_pdc_categoria_recurso') AS categoria_recurso;

SELECT '--- VALIDACIÓN: Permisos RBAC ---' AS seccion;
SELECT COUNT(*) AS permiso_auto_generar FROM rbac_permissions WHERE permission_key = 'lps.pdc.auto_generar';
SELECT COUNT(*) AS role_assignments FROM rbac_role_permissions WHERE permission_key = 'lps.pdc.auto_generar';

SELECT '--- VALIDACIÓN: Columnas Licify eliminadas ---' AS seccion;
SELECT COUNT(*) AS licify_columns_restantes
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND COLUMN_NAME IN ('fechaIngresoLicify', 'diasIngresoLicify', 'fechaRealIngresoLicify');

SELECT '--- VALIDACIÓN: Tablas proyecto PDC existen ---' AS seccion;
SELECT
  (SELECT COUNT(*) FROM information_schema.tables WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'prueba_pdc') AS prueba_pdc,
  (SELECT COUNT(*) FROM information_schema.tables WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'optimizacionJMC_pdc') AS optimizacionJMC_pdc,
  (SELECT COUNT(*) FROM information_schema.tables WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'da_porto_pdc') AS da_porto_pdc,
  (SELECT COUNT(*) FROM information_schema.tables WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'milan_campestre_torre_pdc') AS milan_pdc;

SELECT '--- PATCH PDC CONSOLIDADO COMPLETADO ---' AS resultado;