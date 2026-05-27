-- =====================================================================
-- PARCHE: RBAC permiso lps.semana.eliminar para acciones de borrado
-- FECHA: 2026-05-27
-- BASE OBJETIVO: Produccion + Local
--
-- Contexto:
-- - eliminar_semana.php validaba lps.semana.crear, que es incorrecto.
-- - Se crea permiso especifico lps.semana.eliminar.
-- - Roles que ven el boton en UI: A, D, R, OT.
--
-- Politica:
-- - Idempotente y seguro de re-ejecutar.
-- - No elimina datos.
-- =====================================================================

SET NAMES utf8mb4;
START TRANSACTION;

-- 1. Registrar el nuevo permiso en el catalogo
INSERT INTO `rbac_permissions`
(`permission_key`, `module_name`, `action_name`, `description`, `is_write`, `is_sensitive`, `created_at`, `updated_at`)
VALUES
('lps.semana.eliminar', 'lps', 'semana_eliminar', 'Eliminar semanas del proyecto', 1, 0, NOW(), NOW())
ON DUPLICATE KEY UPDATE
  `updated_at` = `updated_at`;

-- 2. Conceder el permiso a los roles que ven el boton eliminar en la UI
INSERT INTO `rbac_role_permissions`
(`role_code`, `permission_key`, `allowed`, `source`, `created_at`, `updated_at`)
VALUES
('A', 'lps.semana.eliminar', 1, 'patch_20260527', NOW(), NOW()),
('D', 'lps.semana.eliminar', 1, 'patch_20260527', NOW(), NOW()),
('R', 'lps.semana.eliminar', 1, 'patch_20260527', NOW(), NOW()),
('OT', 'lps.semana.eliminar', 1, 'patch_20260527', NOW(), NOW())
ON DUPLICATE KEY UPDATE
  `allowed` = 1,
  `source` = 'patch_20260527',
  `updated_at` = NOW();

COMMIT;

-- 3. Verificacion post-parche
SELECT
  rp.role_code,
  COUNT(*) AS permisos_concedidos
FROM `rbac_role_permissions` rp
WHERE rp.`permission_key` = 'lps.semana.eliminar'
  AND rp.`allowed` = 1
GROUP BY rp.role_code
ORDER BY FIELD(rp.role_code, 'A', 'D', 'R', 'OT');
