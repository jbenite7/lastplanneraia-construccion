SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `actividad_programa_fuentes` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `project_id` int NOT NULL,
  `actividad_id` int NOT NULL,
  `semana` int NOT NULL,
  `programa_unique_id` int NOT NULL,
  `source_activity` varchar(500) NOT NULL,
  `source_start_date` date DEFAULT NULL,
  `context` varchar(255) DEFAULT NULL,
  `location_hint` varchar(255) DEFAULT NULL,
  `intervention_hint` varchar(255) DEFAULT NULL,
  `family_id` int DEFAULT NULL,
  `family_name` varchar(255) DEFAULT NULL,
  `match_rule` varchar(120) DEFAULT NULL,
  `confidence` decimal(5,2) DEFAULT NULL,
  `risk_flags` json DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_apf_activity_source` (`project_id`, `actividad_id`, `semana`, `programa_unique_id`),
  KEY `idx_apf_activity` (`project_id`, `semana`, `actividad_id`),
  KEY `idx_apf_programa` (`project_id`, `semana`, `programa_unique_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
