SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `semi_auto_learning_candidates` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `candidate_id` varchar(64) NOT NULL,
  `project_id` int NOT NULL,
  `module` varchar(40) NOT NULL,
  `semana` int DEFAULT NULL,
  `candidate_type` varchar(40) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'pending',
  `confidence` decimal(5,2) NOT NULL DEFAULT 0,
  `metadata` json DEFAULT NULL,
  `created_by` varchar(100) DEFAULT NULL,
  `reviewed_by` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `resolved_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_semi_auto_learning_candidate` (`candidate_id`),
  KEY `idx_semi_auto_learning_candidates_project` (`project_id`, `module`, `status`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `semi_auto_learning_rules` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `rule_id` varchar(64) NOT NULL,
  `project_id` int NOT NULL,
  `module` varchar(40) NOT NULL,
  `candidate_id` varchar(64) DEFAULT NULL,
  `rule_type` varchar(40) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'active',
  `confidence` decimal(5,2) NOT NULL DEFAULT 0,
  `metadata` json DEFAULT NULL,
  `created_by` varchar(100) DEFAULT NULL,
  `approved_by` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_semi_auto_learning_rule` (`rule_id`),
  KEY `idx_semi_auto_learning_rules_project` (`project_id`, `module`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `semi_auto_proactive_queue` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `item_id` varchar(64) NOT NULL,
  `project_id` int NOT NULL,
  `module` varchar(40) NOT NULL,
  `semana` int DEFAULT NULL,
  `item_type` varchar(40) NOT NULL,
  `severity` varchar(20) NOT NULL DEFAULT 'info',
  `title` varchar(255) NOT NULL,
  `message` text DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'pending',
  `confidence` decimal(5,2) NOT NULL DEFAULT 0,
  `source_module` varchar(40) DEFAULT NULL,
  `source_ref` varchar(100) DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `created_by` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `resolved_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_semi_auto_queue_item` (`item_id`),
  KEY `idx_semi_auto_queue_project` (`project_id`, `module`, `semana`, `status`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `semi_auto_assistant_feedback` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `feedback_id` varchar(64) NOT NULL,
  `project_id` int NOT NULL,
  `module` varchar(40) NOT NULL,
  `semana` int DEFAULT NULL,
  `run_id` varchar(64) DEFAULT NULL,
  `suggestion_id` varchar(64) DEFAULT NULL,
  `item_id` varchar(64) DEFAULT NULL,
  `feedback_type` varchar(40) NOT NULL,
  `rating` varchar(30) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `created_by` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_semi_auto_assistant_feedback` (`feedback_id`),
  KEY `idx_semi_auto_assistant_feedback_project` (`project_id`, `module`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
