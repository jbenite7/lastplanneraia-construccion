-- ============================================================================
-- 004_patch_rbac_role_permissions.sql
-- Re-inserta todos los role→permission mapeos con INSERT IGNORE.
-- Seguro re-ejecutar: solo inserta lo que falte.
-- ============================================================================

SET NAMES utf8mb4;

INSERT IGNORE INTO `rbac_role_permissions` (`role_code`, `permission_key`, `allowed`, `source`, `created_at`, `updated_at`) VALUES
-- Rol A (Administrador) - Acceso total
('A','admin.auditoria.ver',1,'phase1_seed',NOW(),NOW()),
('A','admin.miembros.gestionar',1,'phase1_seed',NOW(),NOW()),
('A','admin.panel.acceso',1,'phase1_seed',NOW(),NOW()),
('A','admin.permisos.gestionar',1,'phase1_seed',NOW(),NOW()),
('A','admin.proyectos.gestionar',1,'phase1_seed',NOW(),NOW()),
('A','admin.usuarios.gestionar',1,'phase1_seed',NOW(),NOW()),
('A','lps.cic.editar',1,'phase1_seed',NOW(),NOW()),
('A','lps.cic.ver',1,'phase1_seed',NOW(),NOW()),
('A','lps.cnc.editar',1,'phase1_seed',NOW(),NOW()),
('A','lps.cnc.ver',1,'phase1_seed',NOW(),NOW()),
('A','lps.cnp.editar',1,'phase1_seed',NOW(),NOW()),
('A','lps.cnp.ver',1,'phase1_seed',NOW(),NOW()),
('A','lps.contratos.editar',1,'phase1_seed',NOW(),NOW()),
('A','lps.contratos.ver',1,'phase1_seed',NOW(),NOW()),
('A','lps.control_cambios.editar',1,'phase1_seed',NOW(),NOW()),
('A','lps.control_cambios.ver',1,'phase1_seed',NOW(),NOW()),
('A','lps.indicadores.ver',1,'phase1_seed',NOW(),NOW()),
('A','lps.listado_actividades.editar',1,'phase1_seed',NOW(),NOW()),
('A','lps.listado_actividades.ver',1,'phase1_seed',NOW(),NOW()),
('A','lps.paquetes_contratacion.editar',1,'phase1_seed',NOW(),NOW()),
('A','lps.paquetes_contratacion.ver',1,'phase1_seed',NOW(),NOW()),
('A','lps.pdc.editar',1,'phase1_seed',NOW(),NOW()),
('A','lps.pdc.ver',1,'phase1_seed',NOW(),NOW()),
('A','lps.profesionales.editar',1,'phase1_seed',NOW(),NOW()),
('A','lps.profesionales.ver',1,'phase1_seed',NOW(),NOW()),
('A','lps.programa_general_actualizar.editar',1,'phase1_seed',NOW(),NOW()),
('A','lps.programa_general_actualizar.ver',1,'phase1_seed',NOW(),NOW()),
('A','lps.programa_general.editar',1,'phase1_seed',NOW(),NOW()),
('A','lps.programa_general.ver',1,'phase1_seed',NOW(),NOW()),
('A','lps.programacion_intermedia.editar',1,'phase1_seed',NOW(),NOW()),
('A','lps.programacion_intermedia.ver',1,'phase1_seed',NOW(),NOW()),
('A','lps.programacion_semanal.editar',1,'phase1_seed',NOW(),NOW()),
('A','lps.programacion_semanal.ver',1,'phase1_seed',NOW(),NOW()),
('A','lps.reportes.generar',1,'phase1_seed',NOW(),NOW()),
('A','lps.subcontratistas.editar',1,'phase1_seed',NOW(),NOW()),
('A','lps.subcontratistas.ver',1,'phase1_seed',NOW(),NOW()),
('A','notificaciones.admin_seguridad',1,'phase1_seed',NOW(),NOW()),
('A','notificaciones.cic_especialidad',1,'phase1_seed',NOW(),NOW()),
('A','notificaciones.ejecutivas',1,'phase1_seed',NOW(),NOW()),
('A','notificaciones.gestion_pdc_contratos',1,'phase1_seed',NOW(),NOW()),
('A','notificaciones.lps_operativas',1,'phase1_seed',NOW(),NOW()),
('A','notificaciones.preferencias_personales',1,'phase1_seed',NOW(),NOW()),
('A','lps.semana.crear',1,'phase1_seed',NOW(),NOW()),
('A','notificaciones.resumen_semanal',1,'phase1_seed',NOW(),NOW());