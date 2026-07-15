-- Oficina Tecnica califica exclusivamente Gestion del Contrato en CIC.
INSERT IGNORE INTO rbac_permissions
    (permission_key, module_name, action_name, description, is_write, is_sensitive)
VALUES
    ('lps.cic.editar', 'lps', 'cic_editar', 'Editar CIC', 1, 0);

INSERT INTO rbac_role_permissions
    (role_code, permission_key, allowed, source)
VALUES
    ('OT', 'lps.cic.editar', 1, 'cic_discipline_policy')
ON DUPLICATE KEY UPDATE
    allowed = VALUES(allowed),
    source = VALUES(source);
