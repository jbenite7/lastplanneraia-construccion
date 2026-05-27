-- =====================================================================
-- PARCHE PRODUCCION: RBAC LECTURA LPS PARA ROLES SST/AMBIENTAL
-- FECHA: 2026-05-27
-- BASE OBJETIVO: dbhif4pdimjtxe / SiteGround
--
-- Contexto:
-- - El codigo local ahora valida permisos server-side en endpoints PG/PI/PS.
-- - Los roles G, S y SG conservan edicion de CIC, pero necesitan lectura
--   explicita en Programa General, Programacion Intermedia y Semanal.
--
-- Politica:
-- - Idempotente y seguro de re-ejecutar.
-- - No elimina datos.
-- - Solo inserta/actualiza permisos puntuales para G, S y SG.
-- =====================================================================

SET NAMES utf8mb4;

START TRANSACTION;

-- ---------------------------------------------------------------------
-- 1. Asegurar roles objetivo si el seed base no estuviera completo.
-- ---------------------------------------------------------------------
INSERT IGNORE INTO `rbac_roles`
(`code`, `name`, `description`, `is_admin_area`, `is_system_admin`, `is_legacy`, `status`, `sort_order`, `created_at`, `updated_at`)
VALUES
('G', 'Ambiental', 'Edita CIC y consulta PG/PI/PS', 0, 0, 0, 1, 60, NOW(), NOW()),
('S', 'SST', 'Edita CIC y consulta PG/PI/PS', 0, 0, 0, 1, 70, NOW(), NOW()),
('SG', 'SST + Ambiental', 'Edita CIC y consulta PG/PI/PS', 0, 0, 0, 1, 80, NOW(), NOW());

-- ---------------------------------------------------------------------
-- 2. Asegurar permisos de lectura usados por las validaciones backend.
-- ---------------------------------------------------------------------
INSERT INTO `rbac_permissions`
(`permission_key`, `module_name`, `action_name`, `description`, `is_write`, `is_sensitive`, `created_at`, `updated_at`)
VALUES
('lps.programa_general.ver', 'lps', 'programa_general_ver', 'Ver programa general', 0, 0, NOW(), NOW()),
('lps.programacion_intermedia.ver', 'lps', 'programacion_intermedia_ver', 'Ver programacion intermedia', 0, 0, NOW(), NOW()),
('lps.programacion_semanal.ver', 'lps', 'programacion_semanal_ver', 'Ver programacion semanal', 0, 0, NOW(), NOW())
ON DUPLICATE KEY UPDATE
  `updated_at` = `updated_at`;

-- ---------------------------------------------------------------------
-- 3. Conceder lectura PG/PI/PS a G, S y SG.
-- ---------------------------------------------------------------------
INSERT INTO `rbac_role_permissions`
(`role_code`, `permission_key`, `allowed`, `source`, `created_at`, `updated_at`)
VALUES
('G', 'lps.programa_general.ver', 1, 'prod_patch_20260527', NOW(), NOW()),
('G', 'lps.programacion_intermedia.ver', 1, 'prod_patch_20260527', NOW(), NOW()),
('G', 'lps.programacion_semanal.ver', 1, 'prod_patch_20260527', NOW(), NOW()),
('S', 'lps.programa_general.ver', 1, 'prod_patch_20260527', NOW(), NOW()),
('S', 'lps.programacion_intermedia.ver', 1, 'prod_patch_20260527', NOW(), NOW()),
('S', 'lps.programacion_semanal.ver', 1, 'prod_patch_20260527', NOW(), NOW()),
('SG', 'lps.programa_general.ver', 1, 'prod_patch_20260527', NOW(), NOW()),
('SG', 'lps.programacion_intermedia.ver', 1, 'prod_patch_20260527', NOW(), NOW()),
('SG', 'lps.programacion_semanal.ver', 1, 'prod_patch_20260527', NOW(), NOW())
ON DUPLICATE KEY UPDATE
  `allowed` = 1,
  `source` = 'prod_patch_20260527',
  `updated_at` = NOW();

COMMIT;

-- ---------------------------------------------------------------------
-- 4. Verificacion post-parche.
-- Resultado esperado: 3 permisos por cada rol G, S y SG.
-- ---------------------------------------------------------------------
SELECT
  `role_code`,
  COUNT(*) AS `permisos_core_lps_lectura`
FROM `rbac_role_permissions`
WHERE `role_code` IN ('G', 'S', 'SG')
  AND `permission_key` IN (
    'lps.programa_general.ver',
    'lps.programacion_intermedia.ver',
    'lps.programacion_semanal.ver'
  )
  AND `allowed` = 1
GROUP BY `role_code`
ORDER BY FIELD(`role_code`, 'G', 'S', 'SG');

-- =====================================================================
-- FIN PARCHE 20260527
-- =====================================================================
