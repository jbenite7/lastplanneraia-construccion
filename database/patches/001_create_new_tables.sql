-- ============================================================================
-- 001_create_new_tables.sql
-- Migración Quirúrgica: Crear tablas nuevas (100% aditivo, idempotente)
-- Compatible: MySQL 8.0+ / 8.4.6 (Producción SiteGround)
-- Fecha: 2026-03-26
-- ============================================================================
-- ORDEN: Tablas padre primero, hijas después (respeta FKs)
-- POLÍTICA: CREATE TABLE IF NOT EXISTS → seguro re-ejecutar
-- ============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- -----------------------------------------------
-- 1. rbac_roles (tabla padre RBAC)
-- -----------------------------------------------
CREATE TABLE IF NOT EXISTS `rbac_roles` (
  `code` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_admin_area` tinyint(1) NOT NULL DEFAULT '0',
  `is_system_admin` tinyint(1) NOT NULL DEFAULT '0',
  `is_legacy` tinyint(1) NOT NULL DEFAULT '0',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` int NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------
-- 2. rbac_permissions (tabla padre permisos)
-- -----------------------------------------------
CREATE TABLE IF NOT EXISTS `rbac_permissions` (
  `permission_key` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `module_name` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `action_name` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_write` tinyint(1) NOT NULL DEFAULT '0',
  `is_sensitive` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`permission_key`),
  KEY `idx_rbac_permissions_module_action` (`module_name`,`action_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------
-- 3. rbac_role_permissions (tabla cruzada, depende de 1 y 2)
-- -----------------------------------------------
CREATE TABLE IF NOT EXISTS `rbac_role_permissions` (
  `role_code` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `permission_key` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `allowed` tinyint(1) NOT NULL DEFAULT '0',
  `source` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'seed',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`role_code`,`permission_key`),
  KEY `fk_rbac_role_permissions_permission` (`permission_key`),
  CONSTRAINT `fk_rbac_role_permissions_permission` FOREIGN KEY (`permission_key`) REFERENCES `rbac_permissions` (`permission_key`),
  CONSTRAINT `fk_rbac_role_permissions_role` FOREIGN KEY (`role_code`) REFERENCES `rbac_roles` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------
-- 4. notification_types (tabla padre notificaciones)
-- -----------------------------------------------
CREATE TABLE IF NOT EXISTS `notification_types` (
  `notification_code` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`notification_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------
-- 5. event_dictionary (depende de notification_types)
-- -----------------------------------------------
CREATE TABLE IF NOT EXISTS `event_dictionary` (
  `event_code` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `event_action` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `modulo_legacy` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `accion_legacy` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `severity` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'info',
  `auditable` tinyint(1) NOT NULL DEFAULT '1',
  `notification_code` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`event_code`,`event_action`),
  KEY `idx_event_dictionary_mod_legacy` (`modulo_legacy`,`accion_legacy`),
  KEY `fk_event_dictionary_notification_type` (`notification_code`),
  CONSTRAINT `fk_event_dictionary_notification_type` FOREIGN KEY (`notification_code`) REFERENCES `notification_types` (`notification_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------
-- 6. general_auditoria_acciones (auditoría centralizada)
-- -----------------------------------------------
CREATE TABLE IF NOT EXISTS `general_auditoria_acciones` (
  `id` int NOT NULL AUTO_INCREMENT,
  `fecha` datetime DEFAULT CURRENT_TIMESTAMP,
  `usuario` varchar(100) DEFAULT NULL,
  `id_sesion` varchar(100) DEFAULT NULL,
  `modulo` varchar(100) DEFAULT NULL,
  `accion` varchar(100) DEFAULT NULL,
  `event_code` varchar(120) DEFAULT NULL,
  `event_action` varchar(80) DEFAULT NULL,
  `event_result` varchar(20) DEFAULT NULL,
  `descripcion` text,
  `context_json` json DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `proyecto` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `usuario` (`usuario`),
  KEY `modulo` (`modulo`),
  KEY `fecha` (`fecha`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- -----------------------------------------------
-- 7. general_feature_flags (independiente)
-- -----------------------------------------------
CREATE TABLE IF NOT EXISTS `general_feature_flags` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `flag_key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `flag_value` tinyint(1) NOT NULL DEFAULT '0',
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `updated_by` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_flag_key` (`flag_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------
-- 7. project_members (independiente)
-- -----------------------------------------------
CREATE TABLE IF NOT EXISTS `project_members` (
  `id` int NOT NULL AUTO_INCREMENT,
  `project_id` int NOT NULL,
  `user_id` int NOT NULL,
  `role` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'U',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_project_user` (`project_id`,`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------
-- 8. role_intelligence (independiente)
-- -----------------------------------------------
CREATE TABLE IF NOT EXISTS `role_intelligence` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cargo_title` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `suggested_role` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cargo_title` (`cargo_title`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------
-- 9. role_notification_defaults (depende de rbac_roles + notification_types)
-- -----------------------------------------------
CREATE TABLE IF NOT EXISTS `role_notification_defaults` (
  `role_code` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `notification_code` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`role_code`,`notification_code`),
  KEY `fk_role_notification_type` (`notification_code`),
  CONSTRAINT `fk_role_notification_role` FOREIGN KEY (`role_code`) REFERENCES `rbac_roles` (`code`),
  CONSTRAINT `fk_role_notification_type` FOREIGN KEY (`notification_code`) REFERENCES `notification_types` (`notification_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------
-- 10. system_notifications (independiente)
-- -----------------------------------------------
CREATE TABLE IF NOT EXISTS `system_notifications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ID o Username del destinatario',
  `type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Categoría de alerta',
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `item_count` int unsigned NOT NULL DEFAULT '1' COMMENT 'Cantidad de eventos agrupados',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `project_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Atadura a proyecto específico',
  PRIMARY KEY (`id`),
  KEY `idx_unread_user` (`user_id`,`is_read`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_group_lookup` (`user_id`,`type`,`project_id`,`is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------
-- 11. password_history (nueva, no existe en dev ni prod)
-- -----------------------------------------------
CREATE TABLE IF NOT EXISTS `password_history` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_password_history_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================================
-- FIN Script 001 — 11 tablas, 100% IF NOT EXISTS
-- ============================================================================
