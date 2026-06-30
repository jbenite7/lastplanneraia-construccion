SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `semi_auto_runs` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `run_id` varchar(64) NOT NULL,
  `project_id` int NOT NULL,
  `module` varchar(40) NOT NULL,
  `semana` int NOT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'previewed',
  `requested_by` varchar(100) DEFAULT NULL,
  `total_suggestions` int NOT NULL DEFAULT 0,
  `preselected_count` int NOT NULL DEFAULT 0,
  `applied_count` int NOT NULL DEFAULT 0,
  `rejected_count` int NOT NULL DEFAULT 0,
  `error_count` int NOT NULL DEFAULT 0,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_semi_auto_runs_run` (`run_id`),
  KEY `idx_semi_auto_runs_project_module` (`project_id`, `module`, `semana`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `semi_auto_suggestions` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `suggestion_id` varchar(64) NOT NULL,
  `run_id` varchar(64) NOT NULL,
  `project_id` int NOT NULL,
  `module` varchar(40) NOT NULL,
  `target_table` varchar(80) NOT NULL,
  `target_pk` varchar(80) DEFAULT NULL,
  `action` varchar(40) NOT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'previewed',
  `confidence` decimal(5,2) NOT NULL DEFAULT 0,
  `confidence_band` varchar(20) NOT NULL DEFAULT 'low',
  `title` varchar(255) NOT NULL,
  `reason` varchar(500) DEFAULT NULL,
  `match_source` varchar(120) DEFAULT NULL,
  `preselected` tinyint(1) NOT NULL DEFAULT 0,
  `current_payload` json DEFAULT NULL,
  `proposed_payload` json DEFAULT NULL,
  `diff_payload` json DEFAULT NULL,
  `apply_payload` json DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_semi_auto_suggestion` (`suggestion_id`),
  KEY `idx_semi_auto_suggestions_run` (`run_id`, `status`),
  KEY `idx_semi_auto_suggestions_project` (`project_id`, `module`, `confidence_band`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `semi_auto_decisions` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `decision_id` varchar(64) NOT NULL,
  `run_id` varchar(64) NOT NULL,
  `suggestion_id` varchar(64) DEFAULT NULL,
  `project_id` int NOT NULL,
  `module` varchar(40) NOT NULL,
  `decision` varchar(30) NOT NULL,
  `before_payload` json DEFAULT NULL,
  `after_payload` json DEFAULT NULL,
  `result_payload` json DEFAULT NULL,
  `decided_by` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_semi_auto_decision` (`decision_id`),
  KEY `idx_semi_auto_decisions_run` (`run_id`, `decision`),
  KEY `idx_semi_auto_decisions_project` (`project_id`, `module`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `semi_auto_feedback` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `project_id` int NOT NULL,
  `module` varchar(40) NOT NULL,
  `run_id` varchar(64) DEFAULT NULL,
  `suggestion_id` varchar(64) DEFAULT NULL,
  `feedback_type` varchar(40) NOT NULL,
  `original_payload` json DEFAULT NULL,
  `corrected_payload` json DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_semi_auto_feedback_project` (`project_id`, `module`, `created_at`),
  KEY `idx_semi_auto_feedback_suggestion` (`suggestion_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `semi_auto_project_config` (
  `project_id` int NOT NULL,
  `module` varchar(40) NOT NULL,
  `high_threshold` decimal(5,2) NOT NULL DEFAULT 80,
  `medium_threshold` decimal(5,2) NOT NULL DEFAULT 50,
  `learning_scope` varchar(30) NOT NULL DEFAULT 'project',
  `config_payload` json DEFAULT NULL,
  `updated_by` varchar(100) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`project_id`, `module`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `general_pdc_chapter_category_map` (
  `id` int NOT NULL AUTO_INCREMENT,
  `chapter_keyword` varchar(150) NOT NULL,
  `categoria` varchar(80) NOT NULL,
  `prioridad` int NOT NULL DEFAULT 50,
  `activa` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_pdc_chapter_category` (`chapter_keyword`, `categoria`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `general_pdc_chapter_category_map` (`chapter_keyword`, `categoria`, `prioridad`) VALUES
('PRELIMINARES', 'PRELIMINARES', 100),
('CIMENTACION', 'CIMENTACION', 100),
('ESTRUCTURA', 'ESTRUCTURA', 100),
('MAMPOSTERIA', 'MAMPOSTERIA', 100),
('INSTALACIONES', 'INSTALACIONES', 100),
('ACABADOS', 'ACABADOS', 100),
('URBANISMO', 'URBANISMO', 100),
('EQUIPOS', 'EQUIPOS', 100);

SET @strategy_project_col := (
  SELECT COUNT(*)
  FROM information_schema.columns
  WHERE table_schema = DATABASE()
    AND table_name = 'general_pdc_project_family_strategy'
    AND column_name = 'project_id'
);
SET @strategy_project_sql := IF(
  @strategy_project_col = 0,
  'ALTER TABLE `general_pdc_project_family_strategy` ADD COLUMN `project_id` int DEFAULT NULL AFTER `id`',
  'SELECT 1'
);
PREPARE strategy_project_stmt FROM @strategy_project_sql;
EXECUTE strategy_project_stmt;
DEALLOCATE PREPARE strategy_project_stmt;

UPDATE `general_pdc_project_family_strategy` s
JOIN `general_proyectos_procesos` p ON p.`Base_de_Datos` = s.`db_prefix`
SET s.`project_id` = p.`Id`
WHERE s.`project_id` IS NULL;

SET @strategy_project_idx := (
  SELECT COUNT(*)
  FROM information_schema.statistics
  WHERE table_schema = DATABASE()
    AND table_name = 'general_pdc_project_family_strategy'
    AND index_name = 'idx_pdc_strategy_project'
);
SET @strategy_project_idx_sql := IF(
  @strategy_project_idx = 0,
  'CREATE INDEX `idx_pdc_strategy_project` ON `general_pdc_project_family_strategy` (`project_id`, `semana`, `familia_id`)',
  'SELECT 1'
);
PREPARE strategy_project_idx_stmt FROM @strategy_project_idx_sql;
EXECUTE strategy_project_idx_stmt;
DEALLOCATE PREPARE strategy_project_idx_stmt;

INSERT IGNORE INTO `rbac_permissions`
  (`permission_key`, `module_name`, `action_name`, `description`, `is_write`, `is_sensitive`)
VALUES
  ('lps.listado_actividades.editar', 'lps', 'listado_actividades_editar', 'Editar listado de actividades', 1, 0),
  ('lps.contratos.auto_definir', 'lps', 'contratos_auto_definir', 'Auto-definir contratos con preview y confianza', 1, 0),
  ('lps.pdc.auto_generar', 'lps', 'pdc_auto_generar', 'Auto-generar PDC desde el programa general', 1, 0);

INSERT IGNORE INTO `rbac_role_permissions`
  (`role_code`, `permission_key`, `allowed`, `source`)
VALUES
  ('A', 'lps.listado_actividades.editar', 1, 'semi_auto_migration'),
  ('A', 'lps.contratos.auto_definir', 1, 'semi_auto_migration'),
  ('A', 'lps.pdc.auto_generar', 1, 'semi_auto_migration'),
  ('D', 'lps.listado_actividades.editar', 1, 'semi_auto_migration'),
  ('D', 'lps.contratos.auto_definir', 1, 'semi_auto_migration'),
  ('D', 'lps.pdc.auto_generar', 1, 'semi_auto_migration'),
  ('R', 'lps.listado_actividades.editar', 1, 'semi_auto_migration'),
  ('R', 'lps.contratos.auto_definir', 1, 'semi_auto_migration'),
  ('R', 'lps.pdc.auto_generar', 1, 'semi_auto_migration'),
  ('OT', 'lps.listado_actividades.editar', 1, 'semi_auto_migration'),
  ('OT', 'lps.contratos.auto_definir', 1, 'semi_auto_migration'),
  ('OT', 'lps.pdc.auto_generar', 1, 'semi_auto_migration');
