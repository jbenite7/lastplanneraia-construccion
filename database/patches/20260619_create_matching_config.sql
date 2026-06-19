CREATE TABLE IF NOT EXISTS `general_matching_config` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `config_key` VARCHAR(64) NOT NULL UNIQUE,
  `config_value` DECIMAL(5,2) NOT NULL,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_by` INT DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `general_matching_config` (config_key, config_value) VALUES
  ('high_threshold', 0.90),
  ('medium_threshold', 0.70),
  ('chapter_threshold', 0.70)
ON DUPLICATE KEY UPDATE config_value = VALUES(config_value);
