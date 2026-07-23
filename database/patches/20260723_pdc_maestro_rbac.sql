-- 20260723_pdc_maestro_rbac.sql — idempotente.
INSERT IGNORE INTO `rbac_permissions`
    (`permission_key`, `module_name`, `action_name`, `description`, `is_write`, `is_sensitive`, `created_at`, `updated_at`)
VALUES
    ('lps.pdc.maestro', 'lps', 'pdc_maestro', 'Administrar el maestro global de insumos del plan de compras v2', 1, 1, NOW(), NOW());

INSERT IGNORE INTO `rbac_role_permissions`
    (`role_code`, `permission_key`, `allowed`, `source`, `created_at`, `updated_at`)
VALUES
    ('A', 'lps.pdc.maestro', 1, 'patch_20260723', NOW(), NOW()),
    ('D', 'lps.pdc.maestro', 1, 'patch_20260723', NOW(), NOW());
