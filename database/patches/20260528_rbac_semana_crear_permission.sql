-- =============================================================================
-- Patch: 20260528_rbac_semana_crear_permission.sql
-- Fix:   Agrega lps.semana.crear a la matriz RBAC (faltante en producción)
--        + role_intelligence mappings faltantes
--        + project_members para christian.rodriguez en Metrolinea Conf. E2
-- =============================================================================
--
-- Causa raíz:
--   La tabla rbac_permissions en producción no contenía el permiso
--   'lps.semana.crear'. Al tener la matriz RBAC poblada en DB,
--   RbacService::loadRolePermissionsFromDb() usaba solo la DB (con
--   hasExplicitAllow=true), ignorando el catálogo de fallback que sí
--   define este permiso. El resultado: can('lps.semana.crear') = false
--   para todos los roles, generando 403 en nueva_semana.php.
--
-- Idempotente: todas las inserciones usan IGNORE / NOT EXISTS.

-- 1. Agregar permiso lps.semana.crear a rbac_permissions
INSERT IGNORE INTO `rbac_permissions`
    (`permission_key`, `module_name`, `action_name`, `description`, `is_write`, `is_sensitive`, `created_at`, `updated_at`)
VALUES
    ('lps.semana.crear', 'lps', 'semana_crear', 'Crear nuevas semanas en el proyecto', 1, 0, NOW(), NOW());

-- 2. Asignar lps.semana.crear a roles que pueden crear semanas
INSERT IGNORE INTO `rbac_role_permissions`
    (`role_code`, `permission_key`, `allowed`, `source`, `created_at`, `updated_at`)
VALUES
    ('A',   'lps.semana.crear', 1, 'patch_20260528', NOW(), NOW()),
    ('D',   'lps.semana.crear', 1, 'patch_20260528', NOW(), NOW()),
    ('R',   'lps.semana.crear', 1, 'patch_20260528', NOW(), NOW()),
    ('DCV', 'lps.semana.crear', 1, 'patch_20260528', NOW(), NOW()),
    ('OT',  'lps.semana.crear', 1, 'patch_20260528', NOW(), NOW());

-- 3. Agregar mappings de role_intelligence para cargos comunes faltantes
INSERT IGNORE INTO `role_intelligence` (`cargo_title`, `suggested_role`, `updated_at`)
VALUES
    ('administrador',              'A',   NOW()),
    ('profesional diseno construccion virtual', 'DCV', NOW()),
    ('residente oficina tecnica',  'OT',  NOW()),
    ('director obra',              'D',   NOW()),
    ('profesional dcv',            'DCV', NOW());

-- 4. Agregar project_members para christian.rodriguez (usuario activo sin row)
--    Proyecto: Metrolinea Confinamiento Estación 2 (id=69), rol Director (D)
INSERT INTO `project_members` (`project_id`, `user_id`, `role`, `created_at`)
SELECT 69, u.id, 'D', NOW()
FROM `general_usuarios` u
WHERE u.usuario = 'christian.rodriguez'
  AND u.activo = 1
  AND NOT EXISTS (
    SELECT 1 FROM `project_members` pm
    WHERE pm.project_id = 69 AND pm.user_id = u.id
  )
LIMIT 1;

-- 5. (Opcional) Corregir discrepancia Juliana Cruz: R → DCV en project 72
--    Comentado: cambiar solo si se confirma que debe ser DCV
-- UPDATE `project_members`
-- SET role = 'DCV'
-- WHERE project_id = 72 AND user_id = (
--     SELECT id FROM `general_usuarios` WHERE usuario = 'juliana.cruz' LIMIT 1
-- ) AND role = 'R';
