-- =====================================================================
-- PARCHE PRODUCCION: COLUMNAS PC RESTRICCION NOMBRE EN PROYECTOS
-- FECHA: 2026-06-24
-- ALCANCE: general_proyectos_procesos (tabla global, NO per-prefijo)
--
-- Agrega 3 columnas a general_proyectos_procesos:
--   - pc_restr_2_nombre   VARCHAR(100) DEFAULT NULL   AFTER Area
--   - pc_restr_3_nombre   VARCHAR(100) DEFAULT NULL   AFTER pc_restr_2_nombre
--   - pc_restr_4_nombre   VARCHAR(100) DEFAULT NULL   AFTER pc_restr_3_nombre
--
-- Politica:
-- - Aditivo e idempotente (information_schema check + prepared stmt).
-- - No elimina datos ni columnas.
-- - No modifica valores de Area en filas existentes.
--
-- =====================================================================
-- AUDITORIA: Parches existentes que filtran por Area = 'CONSTRUCCION'
-- =====================================================================
-- Los siguientes parches aplican WHERE UPPER(TRIM(Area)) = 'CONSTRUCCION'
-- y por tanto SKIP proyectos de pre-construccion (Area = 'PRE-CONSTRUCCION'):
--
-- 1. 20260525_lps_drawers_construccion.sql
--    - Crea tablas _lps_escalamientos, _lps_drawer_comentarios,
--      _pi_shared_constraints, _pi_shared_constraint_links.
--    - Cursor filtra Area = 'CONSTRUCCION'.
--    - SAFE para PC: no toca proyectos con Area != 'CONSTRUCCION'.
--
-- 2. 20260622_add_tnp_cp_columns.sql
--    - Agrega Es_TNP, Categoria_CP, CP, Observaciones_CP a
--      {prefix}_programacion_semanal.
--    - Cursor filtra Area = 'CONSTRUCCION'.
--    - SAFE para PC: no toca proyectos con Area != 'CONSTRUCCION'.
--
-- 3. 20260623_oc_add_columns.sql
--    - Agrega 10 columnas OC a {prefix}_actividades.
--    - Cursor filtra Area = 'CONSTRUCCION'.
--    - SAFE para PC: no toca proyectos con Area != 'CONSTRUCCION'.
--
-- Adicionalmente, estos parches iteran TODOS los proyectos (sin filtro
-- Area) pero solo modifican columnas/valores genericos que aplican a
-- cualquier area (estados, fechas, flags). NO rompen pre-construccion:
--   - 002_alter_existing_tables.sql          (PK/cols en tablas globales)
--   - 20260527_rename_debe_iniciar.sql       (rename col en _programa_consolidado)
--   - 20260527_remove_adelantada_state.sql   (UPDATE estado en _programa_consolidado)
--   - 20260527_remove_no_requerida_state.sql (UPDATE estado en _programa_consolidado)
--   - 20260528_add_fecha_ultimo_saneo.sql    (ADD col fecha_ultimo_saneo)
--   - 20260602_add_reprogramada_por_usuario_flag.sql (ADD col flag)
--
-- CONCLUSION: Todos los parches CONSTRUCCION-specific usan el filtro
-- Area = 'CONSTRUCCION' y NO afectan proyectos de pre-construccion.
-- Los parches sin filtro solo tocan columnas/valores genericos.
-- =====================================================================
-- WHITELIST toggle_status (Project.php ~line 856):
-- Tras aplicar este parche, agregar los 3 campos al array $allowedFields
-- en admin/src/Models/Project.php:updateField():
--   'pc_restr_2_nombre', 'pc_restr_3_nombre', 'pc_restr_4_nombre'
-- =====================================================================

SET NAMES utf8mb4;

-- -----------------------------------------------
-- 1. pc_restr_2_nombre (AFTER Area)
-- -----------------------------------------------
SET @tbl_exists = (SELECT COUNT(*) FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'general_proyectos_procesos');

SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'general_proyectos_procesos'
    AND COLUMN_NAME = 'pc_restr_2_nombre');
SET @sql = IF(@tbl_exists = 0, 'SELECT "general_proyectos_procesos no existe, skip" AS info',
    IF(@col_exists = 0,
    'ALTER TABLE `general_proyectos_procesos` ADD COLUMN `pc_restr_2_nombre` VARCHAR(100) DEFAULT NULL AFTER `Area`',
    'SELECT "pc_restr_2_nombre already exists" AS info'));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- -----------------------------------------------
-- 2. pc_restr_3_nombre (AFTER pc_restr_2_nombre)
-- -----------------------------------------------
SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'general_proyectos_procesos'
    AND COLUMN_NAME = 'pc_restr_3_nombre');
SET @sql = IF(@tbl_exists = 0, 'SELECT "skip" AS info',
    IF(@col_exists = 0,
    'ALTER TABLE `general_proyectos_procesos` ADD COLUMN `pc_restr_3_nombre` VARCHAR(100) DEFAULT NULL AFTER `pc_restr_2_nombre`',
    'SELECT "pc_restr_3_nombre already exists" AS info'));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- -----------------------------------------------
-- 3. pc_restr_4_nombre (AFTER pc_restr_3_nombre)
-- -----------------------------------------------
SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'general_proyectos_procesos'
    AND COLUMN_NAME = 'pc_restr_4_nombre');
SET @sql = IF(@tbl_exists = 0, 'SELECT "skip" AS info',
    IF(@col_exists = 0,
    'ALTER TABLE `general_proyectos_procesos` ADD COLUMN `pc_restr_4_nombre` VARCHAR(100) DEFAULT NULL AFTER `pc_restr_3_nombre`',
    'SELECT "pc_restr_4_nombre already exists" AS info'));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================================================
-- FIN Parche 20260624 — pc_restr_{2,3,4}_nombre en general_proyectos_procesos
-- ============================================================================
