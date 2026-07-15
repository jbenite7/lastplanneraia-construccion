-- =============================================================
-- Migration: BI Action Queue (Proactividad — Fase 6)
-- Table: bi_action_queue
-- Doc Section 7: toda acción debe tener dueño y fecha límite.
-- Si no tiene dueño ni fecha, no es acción — es comentario.
-- =============================================================

CREATE TABLE IF NOT EXISTS `bi_action_queue` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `project_id` INT NOT NULL,
    `semana` VARCHAR(10) NOT NULL COMMENT 'Week ID from semanas_activas.Semana',
    `action_type` ENUM(
        'liberar_restriccion',
        'ajustar_compromiso',
        'escalar_pdc',
        'intervenir_contratista',
        'balancear_responsable',
        'corregir_dato',
        'recovery_plan'
    ) NOT NULL,
    `entity_type` VARCHAR(30) NOT NULL COMMENT 'actividad, compromiso, pdc, contratista, responsable, curva',
    `entity_id` VARCHAR(100) NOT NULL COMMENT 'PK of the affected entity',
    `risk_id` INT COMMENT 'FK to bi_riesgos if the action was triggered by a risk',
    `owner` VARCHAR(100) COMMENT 'Responsible person or role',
    `due_date` DATE COMMENT 'Deadline for action completion',
    `recommended_action` TEXT NOT NULL COMMENT 'What to do (imperative language)',
    `expected_impact` TEXT COMMENT 'What improvement is expected',
    `evidence_json` JSON COMMENT 'Evidence that justifies the action',
    `status` ENUM('abierta', 'en_curso', 'cerrada') NOT NULL DEFAULT 'abierta',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `closed_at` DATETIME COMMENT 'When the action was closed',
    `closure_evidence` TEXT COMMENT 'Evidence provided at closure',
    INDEX `idx_action_project_semana` (`project_id`, `semana`),
    INDEX `idx_action_status` (`status`),
    INDEX `idx_action_due` (`due_date`),
    INDEX `idx_action_owner` (`owner`),
    INDEX `idx_action_type` (`action_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
