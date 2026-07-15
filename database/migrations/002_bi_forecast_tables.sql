-- =============================================================
-- Migration: BI Forecast Tables (Predictividad — Fase 5)
-- Tables: bi_forecast_runs, bi_forecast_predictions, bi_prediction_outcomes
-- =============================================================

CREATE TABLE IF NOT EXISTS `bi_forecast_runs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `project_id` INT NOT NULL,
    `semana_origen` VARCHAR(10) NOT NULL COMMENT 'Week ID from semanas_activas.Semana',
    `horizonte` ENUM('1w','2w','4w','6w','end') NOT NULL COMMENT 'Prediction horizon',
    `model_version` VARCHAR(20) NOT NULL DEFAULT 'PAC_BASELINE_1.0',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_forecast_run_project` (`project_id`, `semana_origen`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `bi_forecast_predictions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `forecast_run_id` INT NOT NULL,
    `entity_type` VARCHAR(30) NOT NULL COMMENT 'actividad, compromiso, pdc, contratista, responsable, curva',
    `entity_id` VARCHAR(100) NOT NULL COMMENT 'PK of the predicted entity',
    `prediction_type` VARCHAR(30) NOT NULL COMMENT 'pac_expected, avance, restriccion, pdc_ready, fecha_final',
    `predicted_value` DECIMAL(10,4),
    `predicted_probability` DECIMAL(5,4) COMMENT '0-1 probability for binary predictions',
    `confidence` DECIMAL(5,4) COMMENT 'Data completeness confidence (0-1)',
    `features_snapshot_json` JSON COMMENT 'Full feature vector at prediction time',
    `risk_score` INT COMMENT 'Computed risk score (0-100)',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_forecast_pred_run` (`forecast_run_id`),
    INDEX `idx_forecast_pred_entity` (`entity_type`, `entity_id`),
    FOREIGN KEY (`forecast_run_id`) REFERENCES `bi_forecast_runs`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `bi_prediction_outcomes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `prediction_id` INT NOT NULL,
    `observed_value` DECIMAL(10,4) COMMENT 'Actual observed value when the event occurred',
    `observed_at` DATE COMMENT 'Date when the outcome was observed',
    `prediction_error` DECIMAL(10,4) COMMENT 'abs(predicted - observed)',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_pred_outcome_pred` (`prediction_id`),
    FOREIGN KEY (`prediction_id`) REFERENCES `bi_forecast_predictions`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
