-- =====================================================================
-- PARCHE DE BASE DE DATOS: SISTEMA DE ESCALAMIENTOS Y DRAWERS LPS
-- FECHA: 2026-05-21
-- PROYECTO: Prueba (prefijo prueba_)
-- =====================================================================

-- 1. Tabla de Historial y Estado de Escalamientos (prueba_lps_escalamientos)
CREATE TABLE IF NOT EXISTS `prueba_lps_escalamientos` (
  `id` INT AUTO_INCREMENT,
  `proyecto_id` INT NOT NULL COMMENT 'ID de la obra actual',
  `semana` INT NOT NULL COMMENT 'Semana en la que se detonó o está activa',
  `consecutivo_en_programa` INT NOT NULL COMMENT 'ID de la actividad en consolidado',
  `modulo` ENUM('PG', 'PI', 'PS') NOT NULL COMMENT 'Nivel de planificación donde se detecta',
  `trigger_origen` VARCHAR(50) NOT NULL COMMENT 'Código del disparador: PG-1, PG-2, PI-1, PI-2, PS-1, PS-2, PS-3',
  `nivel_actual` TINYINT NOT NULL DEFAULT 1 COMMENT '1: Residente, 2: Director, 3: Coordinador Integración, 4: G. Construcción, 5: G. General',
  `estado` ENUM('Activo', 'Mitigado', 'Cerrado') NOT NULL DEFAULT 'Activo',
  `fecha_detonacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `fecha_ultimo_escalamiento` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `fecha_cierre` TIMESTAMP NULL DEFAULT NULL,
  `usuario_cierre_id` INT NULL COMMENT 'Usuario de general_usuarios que cierra la alerta',
  `justificacion_cierre` MEDIUMTEXT NULL COMMENT 'Justificación obligatoria (>100 caracteres)',
  PRIMARY KEY (`id`),
  KEY `idx_semana_consecutivo` (`semana`, `consecutivo_en_programa`),
  KEY `idx_estado_nivel` (`estado`, `nivel_actual`),
  KEY `idx_proyecto` (`proyecto_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Tabla de Bitácora y Comentarios en Hilos (prueba_lps_drawer_comentarios)
CREATE TABLE IF NOT EXISTS `prueba_lps_drawer_comentarios` (
  `id` INT AUTO_INCREMENT,
  `proyecto_id` INT NOT NULL,
  `consecutivo_en_programa` INT NOT NULL,
  `semana` INT NOT NULL,
  `usuario_id` INT NOT NULL COMMENT 'Autor del comentario (general_usuarios)',
  `comentario` MEDIUMTEXT NOT NULL,
  `escalamiento_id` INT DEFAULT NULL COMMENT 'Nulo si es un comentario general de bitácora, ID si vincula a un hilo de crisis',
  `parent_id` INT DEFAULT NULL COMMENT 'Autorreferencia para soporte de hilos anidados (Slack style)',
  `menciones` JSON DEFAULT NULL COMMENT 'Metadatos de roles AIA notificados',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_comentario_actividad` (`consecutivo_en_programa`, `semana`),
  KEY `idx_parent` (`parent_id`),
  CONSTRAINT `fk_prueba_comentario_escalamiento` FOREIGN KEY (`escalamiento_id`) REFERENCES `prueba_lps_escalamientos` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_prueba_comentario_parent` FOREIGN KEY (`parent_id`) REFERENCES `prueba_lps_drawer_comentarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Columnas y alertas en prueba_programa_consolidado
SET @col_consolidado_alerta = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'prueba_programa_consolidado' AND COLUMN_NAME = 'alerta_crisis'
);
SET @sql_alt1 = IF(@col_consolidado_alerta = 0,
    'ALTER TABLE `prueba_programa_consolidado` ADD COLUMN `alerta_crisis` TINYINT(1) NOT NULL DEFAULT 0 AFTER `programaAnteriorAsociar`',
    'SELECT "alerta_crisis already exists in consolidado" AS info'
);
PREPARE stmt1 FROM @sql_alt1; EXECUTE stmt1; DEALLOCATE PREPARE stmt1;

SET @col_consolidado_repro = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'prueba_programa_consolidado' AND COLUMN_NAME = 'reprogramaciones_acumuladas'
);
SET @sql_alt2 = IF(@col_consolidado_repro = 0,
    'ALTER TABLE `prueba_programa_consolidado` ADD COLUMN `reprogramaciones_acumuladas` INT NOT NULL DEFAULT 0 AFTER `alerta_crisis`',
    'SELECT "reprogramaciones_acumuladas already exists in consolidado" AS info'
);
PREPARE stmt2 FROM @sql_alt2; EXECUTE stmt2; DEALLOCATE PREPARE stmt2;

SET @col_consolidado_dias = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'prueba_programa_consolidado' AND COLUMN_NAME = 'dias_reprogramacion_acumulada'
);
SET @sql_alt3 = IF(@col_consolidado_dias = 0,
    'ALTER TABLE `prueba_programa_consolidado` ADD COLUMN `dias_reprogramacion_acumulada` INT NOT NULL DEFAULT 0 AFTER `reprogramaciones_acumuladas`',
    'SELECT "dias_reprogramacion_acumulada already exists in consolidado" AS info'
);
PREPARE stmt3 FROM @sql_alt3; EXECUTE stmt3; DEALLOCATE PREPARE stmt3;

-- Índices en prueba_programa_consolidado
SET @idx_consolidado_crisis = (
    SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'prueba_programa_consolidado' AND INDEX_NAME = 'idx_crisis_hot'
);
SET @sql_idx1 = IF(@idx_consolidado_crisis = 0,
    'ALTER TABLE `prueba_programa_consolidado` ADD INDEX `idx_crisis_hot` (`Semana`, `alerta_crisis`)',
    'SELECT "idx_crisis_hot already exists" AS info'
);
PREPARE stmt_idx1 FROM @sql_idx1; EXECUTE stmt_idx1; DEALLOCATE PREPARE stmt_idx1;

SET @idx_consolidado_consec = (
    SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'prueba_programa_consolidado' AND INDEX_NAME = 'idx_consecutivo_consolidado'
);
SET @sql_idx2 = IF(@idx_consolidado_consec = 0,
    'ALTER TABLE `prueba_programa_consolidado` ADD INDEX `idx_consecutivo_consolidado` (`Consecutivo_en_Programa`, `Semana`)',
    'SELECT "idx_consecutivo_consolidado already exists" AS info'
);
PREPARE stmt_idx2 FROM @sql_idx2; EXECUTE stmt_idx2; DEALLOCATE PREPARE stmt_idx2;


-- 4. Columnas en prueba_programacion_semanal
SET @col_semanal_alerta = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'prueba_programacion_semanal' AND COLUMN_NAME = 'alerta_crisis'
);
SET @sql_alt4 = IF(@col_semanal_alerta = 0,
    'ALTER TABLE `prueba_programacion_semanal` ADD COLUMN `alerta_crisis` TINYINT(1) NOT NULL DEFAULT 0 AFTER `codigo_actividad`',
    'SELECT "alerta_crisis already exists in semanal" AS info'
);
PREPARE stmt4 FROM @sql_alt4; EXECUTE stmt4; DEALLOCATE PREPARE stmt4;

SET @col_semanal_repro = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'prueba_programacion_semanal' AND COLUMN_NAME = 'reprogramaciones_semanales'
);
SET @sql_alt5 = IF(@col_semanal_repro = 0,
    'ALTER TABLE `prueba_programacion_semanal` ADD COLUMN `reprogramaciones_semanales` INT NOT NULL DEFAULT 0 AFTER `alerta_crisis`',
    'SELECT "reprogramaciones_semanales already exists in semanal" AS info'
);
PREPARE stmt5 FROM @sql_alt5; EXECUTE stmt5; DEALLOCATE PREPARE stmt5;

-- Índices en prueba_programacion_semanal
SET @idx_semanal_crisis = (
    SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'prueba_programacion_semanal' AND INDEX_NAME = 'idx_crisis_semanal'
);
SET @sql_idx3 = IF(@idx_semanal_crisis = 0,
    'ALTER TABLE `prueba_programacion_semanal` ADD INDEX `idx_crisis_semanal` (`Semana`, `alerta_crisis`)',
    'SELECT "idx_crisis_semanal already exists" AS info'
);
PREPARE stmt_idx3 FROM @sql_idx3; EXECUTE stmt_idx3; DEALLOCATE PREPARE stmt_idx3;

SET @idx_semanal_consec = (
    SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'prueba_programacion_semanal' AND INDEX_NAME = 'idx_consecutivo_semanal'
);
SET @sql_idx4 = IF(@idx_semanal_consec = 0,
    'ALTER TABLE `prueba_programacion_semanal` ADD INDEX `idx_consecutivo_semanal` (`Consecutivo_En_Programa`, `Semana`)',
    'SELECT "idx_consecutivo_semanal already exists" AS info'
);
PREPARE stmt_idx4 FROM @sql_idx4; EXECUTE stmt_idx4; DEALLOCATE PREPARE stmt_idx4;


-- 5. Catálogo de Causas Metodológicas en general_cnc (Usando columnas correctas: Categoria_CNC y CNC)
INSERT INTO `general_cnc` (`Categoria_CNC`, `CNC`)
SELECT 'Mano de Obra', 'Insuficiencia de Mano de Obra en Frente (AIA)'
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM `general_cnc` WHERE `Categoria_CNC` = 'Mano de Obra' AND `CNC` = 'Insuficiencia de Mano de Obra en Frente (AIA)'
);

INSERT INTO `general_cnc` (`Categoria_CNC`, `CNC`)
SELECT 'Materiales', 'Retraso en Despacho de Material Crítico (AIA)'
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM `general_cnc` WHERE `Categoria_CNC` = 'Materiales' AND `CNC` = 'Retraso en Despacho de Material Crítico (AIA)'
);

INSERT INTO `general_cnc` (`Categoria_CNC`, `CNC`)
SELECT 'Diseños', 'Falta de Definición Arquitectónica o de Ingeniería (AIA)'
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM `general_cnc` WHERE `Categoria_CNC` = 'Diseños' AND `CNC` = 'Falta de Definición Arquitectónica o de Ingeniería (AIA)'
);
