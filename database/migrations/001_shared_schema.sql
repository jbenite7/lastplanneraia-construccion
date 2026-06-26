-- ============================================================
-- 001_shared_schema.sql
-- Shared Schema DDL — Migracion a tablas project_*
-- Fecha: 2026-06-26
-- Proposito: Crear el esquema compartido (project_*) que
--   reemplaza las tablas por prefijo de proyecto.
--   Contiene ~22 tablas: projects, catalogs, project_* operativas.
-- Uso: mysql -u app -p last_planner < database/migrations/001_shared_schema.sql
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- Table 1: projects
-- Source: general_proyectos_procesos
-- ============================================================
CREATE TABLE IF NOT EXISTS `projects` (
    `id` INT UNSIGNED AUTO_INCREMENT,
    `code` VARCHAR(100) NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `area` VARCHAR(50) DEFAULT NULL,
    `is_active` TINYINT(1) DEFAULT 0,
    `pdc_active` TINYINT(1) DEFAULT 0,
    `linea_base_start` DATE DEFAULT NULL,
    `linea_base_end` DATE DEFAULT NULL,
    `costo_dia_retraso` DECIMAL(14,2) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_projects_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Table 2: audit_actions
-- Source: general_auditoria_acciones
-- ============================================================
CREATE TABLE IF NOT EXISTS `audit_actions` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT,
    `project_id` INT UNSIGNED DEFAULT NULL,
    `fecha` DATETIME DEFAULT NULL,
    `usuario` VARCHAR(100) DEFAULT NULL,
    `id_sesion` VARCHAR(100) DEFAULT NULL,
    `modulo` VARCHAR(100) DEFAULT NULL,
    `accion` VARCHAR(100) DEFAULT NULL,
    `event_code` VARCHAR(120) DEFAULT NULL,
    `event_action` VARCHAR(80) DEFAULT NULL,
    `event_result` VARCHAR(20) DEFAULT NULL,
    `descripcion` TEXT DEFAULT NULL,
    `context_json` JSON DEFAULT NULL,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    PRIMARY KEY (`id`),
    INDEX `idx_audit_actions_project_id` (`project_id`),
    INDEX `idx_audit_actions_usuario` (`usuario`),
    INDEX `idx_audit_actions_fecha` (`fecha`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Table 3: cat_cnc
-- Source: general_cnc
-- ============================================================
CREATE TABLE IF NOT EXISTS `cat_cnc` (
    `id` INT UNSIGNED AUTO_INCREMENT,
    `category` VARCHAR(100) NOT NULL,
    `code` VARCHAR(100) NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_cat_cnc_category_code` (`category`, `code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Table 4: cat_codigos_actividades
-- Source: general_codigos_actividades
-- ============================================================
CREATE TABLE IF NOT EXISTS `cat_codigos_actividades` (
    `id` INT UNSIGNED AUTO_INCREMENT,
    `codigo` VARCHAR(20) DEFAULT NULL,
    `actividad` VARCHAR(300) DEFAULT NULL,
    `unidad` VARCHAR(50) DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Table 5: cat_dias_procesos_contratacion
-- Source: general_dias_procesos_contratacion
-- ============================================================
CREATE TABLE IF NOT EXISTS `cat_dias_procesos_contratacion` (
    `id` INT UNSIGNED AUTO_INCREMENT,
    `paquete_contratacion` VARCHAR(200) DEFAULT NULL,
    `tipo_paquete` VARCHAR(200) DEFAULT NULL,
    `dias_elaboracion_pliegos` INT DEFAULT NULL,
    `dias_entrega_pliegos` INT DEFAULT NULL,
    `dias_recibo_propuestas` INT DEFAULT NULL,
    `dias_cuadros_comparativos` INT DEFAULT NULL,
    `dias_legalizacion_contrato` INT DEFAULT NULL,
    `dias_fabricacion` INT DEFAULT NULL,
    `dias_insumos_obra` INT DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Table 6: project_lps_drawer_comentarios
-- Source: {prefix}_lps_drawer_comentarios
-- ============================================================
CREATE TABLE IF NOT EXISTS `project_lps_drawer_comentarios` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT,
    `project_id` INT UNSIGNED NOT NULL,
    `legacy_id` INT DEFAULT NULL,
    `consecutivo_en_programa` INT NOT NULL,
    `semana` INT NOT NULL,
    `usuario_id` INT NOT NULL,
    `comentario` MEDIUMTEXT NOT NULL,
    `escalamiento_id` INT DEFAULT NULL,
    `parent_id` INT DEFAULT NULL,
    `menciones` JSON DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_plps_drawer_comentarios_project_legacy` (`project_id`, `legacy_id`),
    INDEX `idx_plps_drawer_comentarios_project_semana` (`project_id`, `semana`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Table 7: project_lps_escalamientos
-- Source: {prefix}_lps_escalamientos
-- ============================================================
CREATE TABLE IF NOT EXISTS `project_lps_escalamientos` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT,
    `project_id` INT UNSIGNED NOT NULL,
    `legacy_id` INT DEFAULT NULL,
    `semana` INT NOT NULL,
    `consecutivo_en_programa` INT NOT NULL,
    `modulo` ENUM('PG','PI','PS') NOT NULL,
    `trigger_origen` VARCHAR(50) NOT NULL,
    `nivel_actual` TINYINT DEFAULT 1,
    `estado` ENUM('Activo','Mitigado','Cerrado') DEFAULT 'Activo',
    `fecha_detonacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `fecha_ultimo_escalamiento` TIMESTAMP NULL DEFAULT NULL,
    `fecha_cierre` TIMESTAMP NULL DEFAULT NULL,
    `usuario_cierre_id` INT DEFAULT NULL,
    `justificacion_cierre` MEDIUMTEXT DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_plps_escalamientos_project_legacy` (`project_id`, `legacy_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Table 8: project_pi_shared_constraints
-- Source: {prefix}_pi_shared_constraints
-- ============================================================
CREATE TABLE IF NOT EXISTS `project_pi_shared_constraints` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT,
    `project_id` INT UNSIGNED NOT NULL,
    `legacy_id` BIGINT UNSIGNED DEFAULT NULL,
    `semana` INT NOT NULL,
    `restriccion` VARCHAR(40) NOT NULL,
    `valor_objetivo` VARCHAR(20) NOT NULL,
    `nota` TEXT DEFAULT NULL,
    `creado_por` VARCHAR(120) DEFAULT NULL,
    `creado_en` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `actualizado_en` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_pi_shared_constraints_project_legacy` (`project_id`, `legacy_id`),
    INDEX `idx_pi_shared_constraints_project_semana` (`project_id`, `semana`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Table 9: project_pi_shared_constraint_links
-- Source: {prefix}_pi_shared_constraint_links
-- ============================================================
CREATE TABLE IF NOT EXISTS `project_pi_shared_constraint_links` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT,
    `project_id` INT UNSIGNED NOT NULL,
    `legacy_id` BIGINT UNSIGNED DEFAULT NULL,
    `shared_constraint_id` BIGINT UNSIGNED NOT NULL,
    `semana` INT NOT NULL,
    `consecutivo_en_programa` BIGINT NOT NULL,
    `valor_aplicado` VARCHAR(20) NOT NULL,
    `override_local` TINYINT(1) DEFAULT 0,
    `aplicado_en` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_pi_shared_constraint_links_project_legacy` (`project_id`, `legacy_id`),
    INDEX `idx_pi_shared_constraint_links_project_semana` (`project_id`, `semana`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Table 10: project_auto_program_log
-- Source: {prefix}_auto_program_log
-- ============================================================
CREATE TABLE IF NOT EXISTS `project_auto_program_log` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT,
    `project_id` INT UNSIGNED NOT NULL,
    `legacy_id` INT DEFAULT NULL,
    `semana` INT NOT NULL,
    `consecutivo` INT NOT NULL,
    `accion` ENUM('comprometer','descomprometer','insert_cnp') NOT NULL,
    `detalle` TEXT DEFAULT NULL,
    `categoria_cnp` VARCHAR(100) DEFAULT NULL,
    `cnp` VARCHAR(100) DEFAULT NULL,
    `creado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_auto_program_log_project_legacy` (`project_id`, `legacy_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- FOREIGN KEY CONSTRAINTS
-- ============================================================
ALTER TABLE `audit_actions` ADD CONSTRAINT `fk_audit_actions_project` FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE RESTRICT;

ALTER TABLE `project_lps_drawer_comentarios` ADD CONSTRAINT `fk_plps_drawer_comentarios_project` FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE RESTRICT;

ALTER TABLE `project_lps_escalamientos` ADD CONSTRAINT `fk_plps_escalamientos_project` FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE RESTRICT;

ALTER TABLE `project_pi_shared_constraints` ADD CONSTRAINT `fk_pi_shared_constraints_project` FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE RESTRICT;

ALTER TABLE `project_pi_shared_constraint_links` ADD CONSTRAINT `fk_pi_shared_constraint_links_project` FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE RESTRICT;

ALTER TABLE `project_auto_program_log` ADD CONSTRAINT `fk_auto_program_log_project` FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE RESTRICT;

SET FOREIGN_KEY_CHECKS = 1;
-- Tables created: projects, audit_actions, cat_cnc, cat_codigos_actividades, cat_dias_procesos_contratacion, project_lps_drawer_comentarios, project_lps_escalamientos, project_pi_shared_constraints, project_pi_shared_constraint_links, project_auto_program_log
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- Table 1: project_costos_cuadrillas
-- Source: general_costos_cuadrillas
-- ============================================================
CREATE TABLE IF NOT EXISTS `project_costos_cuadrillas` (
    `id` INT UNSIGNED AUTO_INCREMENT,
    `project_id` INT UNSIGNED NOT NULL,
    `costo_hora_oficial` DECIMAL(10,2) DEFAULT NULL,
    `costo_hora_ayudante` DECIMAL(10,2) DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_pcostos_project` (`project_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Table 2: project_cuadrillas_tipicas
-- Source: general_cuadrillas_tipicas
-- ============================================================
CREATE TABLE IF NOT EXISTS `project_cuadrillas_tipicas` (
    `id` INT UNSIGNED AUTO_INCREMENT,
    `project_id` INT UNSIGNED NOT NULL,
    `codigo_actividad` VARCHAR(200) NOT NULL,
    `oficiales_tipica` INT NOT NULL,
    `ayudantes_tipica` INT NOT NULL,
    `rendimiento_tipica` DECIMAL(10,2) NOT NULL,
    `numero_cuadrillas_tipicas` INT NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_pcuadrillas_project_actividad` (`project_id`, `codigo_actividad`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Table 3: project_semanas_activas
-- Source: {prefix}_semanas_activas
-- ============================================================
CREATE TABLE IF NOT EXISTS `project_semanas_activas` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT,
    `project_id` INT UNSIGNED NOT NULL,
    `legacy_id` INT DEFAULT NULL,
    `semana` INT NOT NULL,
    `fecha_inicio_sem` DATE NOT NULL,
    `fecha_fin_sem` DATE NOT NULL,
    `semanal_confirmada` TINYINT(1) DEFAULT 0,
    `fecha_cierre_compromisos` DATE DEFAULT NULL,
    `fecha_ultimo_saneo` DATETIME DEFAULT NULL,
    `fecha_creacion_semana` DATE DEFAULT NULL,
    `reprogramacion` TINYINT(1) DEFAULT 0,
    `diferencia_estructura_cron` INT DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_psemanas_project_semana` (`project_id`, `semana`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Table 4: project_subcontratistas
-- Source: {prefix}_subcontratistas
-- ============================================================
CREATE TABLE IF NOT EXISTS `project_subcontratistas` (
    `id` INT UNSIGNED AUTO_INCREMENT,
    `project_id` INT UNSIGNED NOT NULL,
    `legacy_id` INT DEFAULT NULL,
    `subcontratista` VARCHAR(200) NOT NULL,
    `correo_contacto` VARCHAR(200) DEFAULT NULL,
    `nit` VARCHAR(20) DEFAULT NULL,
    `alcance` VARCHAR(200) DEFAULT NULL,
    `tipo_proveedor` VARCHAR(200) DEFAULT NULL,
    `activo` TINYINT(1) DEFAULT 1,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_psubcontratistas_project_nombre` (`project_id`, `subcontratista`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Table 5: project_profesionales
-- Source: {prefix}_profesionales
-- ============================================================
CREATE TABLE IF NOT EXISTS `project_profesionales` (
    `id` INT UNSIGNED AUTO_INCREMENT,
    `project_id` INT UNSIGNED NOT NULL,
    `legacy_id` INT DEFAULT NULL,
    `nombre` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) DEFAULT NULL,
    `cargo` VARCHAR(100) DEFAULT NULL,
    `activo` TINYINT(1) DEFAULT 1,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_pprofesionales_project_nombre` (`project_id`, `nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Table 6: project_programa
-- Source: {prefix}_programa
-- ============================================================
CREATE TABLE IF NOT EXISTS `project_programa` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT,
    `project_id` INT UNSIGNED NOT NULL,
    `legacy_id` INT DEFAULT NULL,
    `consecutivo` INT NOT NULL,
    `id_actividad` VARCHAR(500) DEFAULT NULL,
    `actividad` VARCHAR(500) DEFAULT NULL,
    `titulo` INT DEFAULT NULL,
    `fecha_inicio` DATE DEFAULT NULL,
    `fecha_fin` DATE DEFAULT NULL,
    `ruta_critica` TINYINT(1) DEFAULT NULL,
    `ejecutado` DECIMAL(10,2) DEFAULT 0.00,
    `estado` VARCHAR(50) DEFAULT NULL,
    `semanas_inicio` INT DEFAULT 0,
    `estado_restricciones` DECIMAL(10,2) DEFAULT 0.00,
    `dy_e` DECIMAL(10,2) DEFAULT 0.00,
    `materiales` DECIMAL(10,2) DEFAULT 0.00,
    `mde_o` DECIMAL(10,2) DEFAULT 0.00,
    `equipos` DECIMAL(10,2) DEFAULT 0.00,
    `predecesora` DECIMAL(10,2) DEFAULT 0.00,
    `pdto_cons` DECIMAL(10,2) DEFAULT 0.00,
    `modelo` VARCHAR(9) DEFAULT '0',
    `responsable_aia` VARCHAR(100) DEFAULT NULL,
    `observaciones` MEDIUMTEXT DEFAULT NULL,
    `ult_act_est` DATE DEFAULT NULL,
    `ult_act_restr` DATE DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_pprograma_project_legacy` (`project_id`, `legacy_id`),
    INDEX `idx_pprograma_project_consecutivo` (`project_id`, `consecutivo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Table 7: project_programa_consolidado
-- Source: {prefix}_programa_consolidado
-- ============================================================
CREATE TABLE IF NOT EXISTS `project_programa_consolidado` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT,
    `project_id` INT UNSIGNED NOT NULL,
    `legacy_id` INT DEFAULT NULL,
    `consecutivo` INT NOT NULL,
    `semana` INT NOT NULL,
    `consecutivo_en_programa` INT NOT NULL,
    `id_actividad` VARCHAR(500) DEFAULT NULL,
    `actividad` VARCHAR(500) DEFAULT NULL,
    `titulo` INT DEFAULT NULL,
    `fecha_inicio` DATE DEFAULT NULL,
    `fecha_fin` DATE DEFAULT NULL,
    `ruta_critica` TINYINT(1) DEFAULT NULL,
    `ejecutado` DECIMAL(10,2) DEFAULT 0.00,
    `estado` VARCHAR(100) DEFAULT NULL,
    `semanas_inicio` INT DEFAULT 0,
    `estado_restricciones` DECIMAL(10,2) DEFAULT 0.00,
    `dy_e` VARCHAR(9) DEFAULT '0',
    `materiales` VARCHAR(9) DEFAULT '0',
    `mde_o` VARCHAR(9) DEFAULT '0',
    `equipos` VARCHAR(9) DEFAULT '0',
    `predecesora` VARCHAR(9) DEFAULT '0',
    `pdto_cons` VARCHAR(9) DEFAULT '0',
    `modelo` VARCHAR(9) DEFAULT '0',
    `sub_contratista` VARCHAR(100) DEFAULT NULL,
    `responsable_aia` VARCHAR(100) DEFAULT NULL,
    `observaciones` MEDIUMTEXT DEFAULT NULL,
    `ult_act_est` DATE DEFAULT NULL,
    `ult_act_restr` DATE DEFAULT NULL,
    `activa` TINYINT(1) DEFAULT 0,
    `ejecutado_siguiente_semana` DECIMAL(10,2) DEFAULT NULL,
    `codigo_actividad` VARCHAR(11) DEFAULT NULL,
    `medir_productividad` TINYINT(1) DEFAULT 0,
    `cantidad_ppto` INT DEFAULT NULL,
    `unidad` VARCHAR(20) DEFAULT NULL,
    `programa_anterior_asociar` VARCHAR(500) DEFAULT NULL,
    `alerta_crisis` TINYINT(1) DEFAULT 0,
    `reprogramaciones_acumuladas` INT DEFAULT 0,
    `dias_reprogramacion_acumulada` INT DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_ppconsolidado_project_semana_consecutivo` (`project_id`, `semana`, `consecutivo_en_programa`),
    INDEX `idx_ppconsolidado_project_semana` (`project_id`, `semana`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Table 8: project_programacion_semanal
-- Source: {prefix}_programacion_semanal
-- ============================================================
CREATE TABLE IF NOT EXISTS `project_programacion_semanal` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT,
    `project_id` INT UNSIGNED NOT NULL,
    `legacy_id` INT DEFAULT NULL,
    `consecutivo` INT NOT NULL,
    `semana` INT DEFAULT NULL,
    `consecutivo_en_programa` INT NOT NULL,
    `id_actividad` VARCHAR(500) DEFAULT NULL,
    `actividad` VARCHAR(500) DEFAULT NULL,
    `descripcion` MEDIUMTEXT DEFAULT NULL,
    `ubicacion` MEDIUMTEXT DEFAULT NULL,
    `fecha_inicio` DATE DEFAULT NULL,
    `fecha_fin` DATE DEFAULT NULL,
    `sub_contratista` VARCHAR(200) DEFAULT NULL,
    `responsable_aia` VARCHAR(200) DEFAULT NULL,
    `empresa` VARCHAR(200) DEFAULT 'AIA',
    `ejecutado` DECIMAL(10,2) DEFAULT NULL,
    `medir_productividad` TINYINT(1) DEFAULT 0,
    `unidad` VARCHAR(10) DEFAULT NULL,
    `cantidad_ppto` INT DEFAULT NULL,
    `cantidad_sugerida` DECIMAL(10,2) DEFAULT NULL,
    `compromiso` DECIMAL(10,2) DEFAULT NULL,
    `ejecutado_real` DECIMAL(10,2) DEFAULT NULL,
    `p_completado` DECIMAL(10,2) DEFAULT NULL,
    `pac` TINYINT(1) DEFAULT NULL,
    `critica` TINYINT(1) DEFAULT NULL,
    `atrasada` TINYINT(1) DEFAULT NULL,
    `activa` VARCHAR(3) DEFAULT NULL,
    `reprogramada_por_usuario` TINYINT(1) DEFAULT 0,
    `prog_sin_restricciones_100` TINYINT(1) DEFAULT NULL,
    `categoria_cnp` VARCHAR(100) DEFAULT NULL,
    `cnp` VARCHAR(100) DEFAULT NULL,
    `observaciones_cnp` MEDIUMTEXT DEFAULT NULL,
    `categoria_cnc` VARCHAR(100) DEFAULT NULL,
    `cnc` VARCHAR(100) DEFAULT NULL,
    `observaciones_cnc` MEDIUMTEXT DEFAULT NULL,
    `rendimientos` VARCHAR(500) DEFAULT NULL,
    `codigo_actividad` VARCHAR(11) DEFAULT NULL,
    `alerta_crisis` TINYINT(1) DEFAULT 0,
    `reprogramaciones_semanales` INT DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_ppsemanal_project_semana_consecutivo` (`project_id`, `semana`, `consecutivo_en_programa`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Table 9: project_pdc
-- Source: {prefix}_pdc
-- ============================================================
CREATE TABLE IF NOT EXISTS `project_pdc` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT,
    `project_id` INT UNSIGNED NOT NULL,
    `legacy_id` INT DEFAULT NULL,
    `consecutivo` INT NOT NULL,
    `semana` INT NOT NULL,
    `titulo` INT NOT NULL,
    `tipo_paquete` VARCHAR(200) NOT NULL,
    `paquete_contratacion` VARCHAR(200) DEFAULT NULL,
    `contratos` VARCHAR(200) DEFAULT NULL,
    `numero_subcontratos` INT DEFAULT 1,
    `subcontrato_paquete` INT DEFAULT 1,
    `estado` VARCHAR(200) DEFAULT NULL,
    `fecha_elaboracion_pliegos` DATE DEFAULT NULL,
    `dias_elaboracion_pliegos` INT DEFAULT NULL,
    `fecha_real_elaboracion_pliegos` DATE DEFAULT NULL,
    `fecha_entrega_pliegos` DATE DEFAULT NULL,
    `dias_entrega_pliegos` INT DEFAULT NULL,
    `fecha_real_entrega_pliegos` DATE DEFAULT NULL,
    `fecha_recibo_propuestas` DATE DEFAULT NULL,
    `dias_recibo_propuestas` INT DEFAULT NULL,
    `fecha_real_recibo_propuestas` DATE DEFAULT NULL,
    `fecha_cuadros_comparativos` DATE DEFAULT NULL,
    `dias_cuadros_comparativos` INT DEFAULT NULL,
    `fecha_real_cuadros_comparativos` DATE DEFAULT NULL,
    `fecha_legalizacion_contrato` DATE DEFAULT NULL,
    `dias_legalizacion_contrato` INT DEFAULT NULL,
    `fecha_real_legalizacion_contrato` DATE DEFAULT NULL,
    `fecha_fabricacion` DATE DEFAULT NULL,
    `dias_fabricacion` INT DEFAULT NULL,
    `fecha_real_fabricacion` DATE DEFAULT NULL,
    `fecha_insumos_obra` DATE DEFAULT NULL,
    `dias_insumos_obra` INT DEFAULT NULL,
    `fecha_real_insumos_obra` DATE DEFAULT NULL,
    `fecha_inicio` DATE DEFAULT NULL,
    `fecha_inicio_proyectada` DATE DEFAULT NULL,
    `fecha_real_inicio` DATE DEFAULT NULL,
    `id_proveedor_adjudicado` INT DEFAULT NULL,
    `numero_contrato` VARCHAR(50) DEFAULT NULL,
    `aplica_polizas` TINYINT(1) DEFAULT 1,
    `fecha_vencimiento_polizas` DATE DEFAULT NULL,
    `valor_presupuesto` DECIMAL(14,2) DEFAULT NULL,
    `valor_primera_negociacion` DECIMAL(14,2) DEFAULT NULL,
    `valor_adjudicado` DECIMAL(14,2) DEFAULT NULL,
    `valor_anticipo` DECIMAL(14,2) DEFAULT NULL,
    `valor_reclamado` DECIMAL(14,2) DEFAULT NULL,
    `valor_devoluciones` DECIMAL(14,2) DEFAULT NULL,
    `observaciones_contrato` MEDIUMTEXT DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_ppdc_project_semana_titulo_paquete` (`project_id`, `semana`, `titulo`, `paquete_contratacion`),
    INDEX `idx_ppdc_project_semana` (`project_id`, `semana`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Table 10: project_cic
-- Source: {prefix}_cic
-- ============================================================
CREATE TABLE IF NOT EXISTS `project_cic` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT,
    `project_id` INT UNSIGNED NOT NULL,
    `legacy_id` INT DEFAULT NULL,
    `semana` INT DEFAULT NULL,
    `subcontratista` VARCHAR(200) DEFAULT NULL,
    `correo_contacto` VARCHAR(200) DEFAULT NULL,
    `nit` VARCHAR(10) DEFAULT NULL,
    `alcance` VARCHAR(200) DEFAULT NULL,
    `tipo_proveedor` VARCHAR(200) DEFAULT NULL,
    `pac` VARCHAR(11) DEFAULT 'NA',
    `pac_acum` VARCHAR(11) DEFAULT 'NA',
    `p_completado` VARCHAR(11) DEFAULT 'NA',
    `p_completado_acum` VARCHAR(11) DEFAULT 'NA',
    `calidad` VARCHAR(11) DEFAULT 'NR',
    `calidad_acum` VARCHAR(11) DEFAULT 'NR',
    `gsa` VARCHAR(11) DEFAULT 'NR',
    `gsa_acum` VARCHAR(11) DEFAULT 'NR',
    `sst` VARCHAR(11) DEFAULT 'NR',
    `sst_acum` VARCHAR(11) DEFAULT 'NR',
    `adm` VARCHAR(11) DEFAULT 'NR',
    `adm_acum` VARCHAR(11) DEFAULT 'NR',
    `cal_integral` DECIMAL(5,2) DEFAULT NULL,
    `cal_integral_acum` DECIMAL(5,2) DEFAULT NULL,
    `observaciones` MEDIUMTEXT DEFAULT NULL,
    `mdo_calidad` VARCHAR(5) DEFAULT 'NR',
    `mdo_calidad_acum` VARCHAR(5) DEFAULT 'NR',
    `mdo_gsa` VARCHAR(5) DEFAULT 'NR',
    `mdo_gsa_acum` VARCHAR(5) DEFAULT 'NR',
    `mdo_sst` VARCHAR(5) DEFAULT 'NR',
    `mdo_sst_acum` VARCHAR(5) DEFAULT 'NR',
    `mdo_adm` VARCHAR(5) DEFAULT 'NR',
    `mdo_adm_acum` VARCHAR(5) DEFAULT 'NR',
    `mdo_cal_integral` VARCHAR(5) DEFAULT 'NR',
    `mdo_cal_integral_acum` VARCHAR(5) DEFAULT 'NR',
    `mdo_pac` VARCHAR(5) DEFAULT 'NR',
    `mdo_pac_acum` VARCHAR(5) DEFAULT 'NR',
    `mdo_p_completado` VARCHAR(5) DEFAULT 'NR',
    `mdo_p_completado_acum` VARCHAR(5) DEFAULT 'NR',
    `si_calidad` VARCHAR(5) DEFAULT 'NR',
    `si_calidad_acum` VARCHAR(5) DEFAULT 'NR',
    `si_gsa` VARCHAR(5) DEFAULT 'NR',
    `si_gsa_acum` VARCHAR(5) DEFAULT 'NR',
    `si_sst` VARCHAR(5) DEFAULT 'NR',
    `si_sst_acum` VARCHAR(5) DEFAULT 'NR',
    `si_adm` VARCHAR(5) DEFAULT 'NR',
    `si_adm_acum` VARCHAR(5) DEFAULT 'NR',
    `si_cal_integral` VARCHAR(5) DEFAULT 'NR',
    `si_cal_integral_acum` VARCHAR(5) DEFAULT 'NR',
    `si_pac` VARCHAR(5) DEFAULT 'NR',
    `si_pac_acum` VARCHAR(5) DEFAULT 'NR',
    `si_p_completado` VARCHAR(5) DEFAULT 'NR',
    `si_p_completado_acum` VARCHAR(5) DEFAULT 'NR',
    `mdo_rendimiento` VARCHAR(5) DEFAULT 'NR',
    `mdo_rendimiento_acum` VARCHAR(5) DEFAULT 'NR',
    `mdo_si` VARCHAR(5) DEFAULT 'NR',
    `mdo_si_acum` VARCHAR(5) DEFAULT 'NR',
    `si_rendimiento` VARCHAR(5) DEFAULT 'NR',
    `si_rendimiento_acum` VARCHAR(5) DEFAULT 'NR',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_pcic_project_semana_subcontratista` (`project_id`, `semana`, `subcontratista`),
    INDEX `idx_pcic_project_semana` (`project_id`, `semana`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Table 11: project_cambios
-- Source: {prefix}_cambios
-- ============================================================
CREATE TABLE IF NOT EXISTS `project_cambios` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT,
    `project_id` INT UNSIGNED NOT NULL,
    `legacy_id` INT DEFAULT NULL,
    `solicitante_cambio` INT DEFAULT NULL,
    `detalle_solicitante_otro` LONGTEXT DEFAULT NULL,
    `fecha_solicitud` DATE DEFAULT NULL,
    `prioridad` INT DEFAULT NULL,
    `tipo_cambio` LONGTEXT DEFAULT NULL,
    `responsable_solucion` INT DEFAULT NULL,
    `detalle_responsable_solucion` LONGTEXT DEFAULT NULL,
    `justificacion` LONGTEXT DEFAULT NULL,
    `descripcion` LONGTEXT DEFAULT NULL,
    `incidencia_alcance` LONGTEXT DEFAULT NULL,
    `tiempo_cronograma` DECIMAL(10,2) DEFAULT NULL,
    `tiempo_cronograma_afectado` DECIMAL(10,2) DEFAULT NULL,
    `incidencia_cronograma` LONGTEXT DEFAULT NULL,
    `valor_presupuesto` DECIMAL(14,2) DEFAULT NULL,
    `costo_directo` DECIMAL(14,2) DEFAULT NULL,
    `costo_directo_aiu` DECIMAL(14,2) DEFAULT NULL,
    `costo_directo_aiu_iva` DECIMAL(14,2) DEFAULT NULL,
    `valor_aprobado` DECIMAL(14,2) DEFAULT NULL,
    `incidencia_presupuesto` LONGTEXT DEFAULT NULL,
    `incidencia_calidad` LONGTEXT DEFAULT NULL,
    `incidencia_riesgo` LONGTEXT DEFAULT NULL,
    `incidencia_recurso` LONGTEXT DEFAULT NULL,
    `fecha_tentativa_definicion` DATE DEFAULT NULL,
    `fecha_entrega_interventoria` DATE DEFAULT NULL,
    `observaciones` LONGTEXT DEFAULT NULL,
    `fecha_definicion` DATE DEFAULT NULL,
    `aprobacion` TINYINT(1) DEFAULT NULL,
    `soportes` LONGTEXT DEFAULT NULL,
    PRIMARY KEY (`id`),
    INDEX `idx_pcambios_project_fecha_solicitud` (`project_id`, `fecha_solicitud`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Table 12: project_actividades
-- Source: {prefix}_actividades
-- ============================================================
CREATE TABLE IF NOT EXISTS `project_actividades` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT,
    `project_id` INT UNSIGNED NOT NULL,
    `legacy_id` INT DEFAULT NULL,
    `codigo` INT NOT NULL,
    `actividad` VARCHAR(300) NOT NULL,
    `descripcion_actividad` MEDIUMTEXT DEFAULT NULL,
    `actividad_inicio` VARCHAR(500) DEFAULT NULL,
    `nombre_actividad_inicio` VARCHAR(500) DEFAULT NULL,
    `fecha_inicio` DATE DEFAULT NULL,
    `tipo_contrato` VARCHAR(10) DEFAULT NULL,
    `semana_actualizacion` INT DEFAULT NULL,
    `SI1` VARCHAR(200) DEFAULT NULL,
    `paquete_si1` VARCHAR(200) DEFAULT NULL,
    `SI2` VARCHAR(200) DEFAULT NULL,
    `paquete_si2` VARCHAR(200) DEFAULT NULL,
    `SI3` VARCHAR(200) DEFAULT NULL,
    `paquete_si3` VARCHAR(200) DEFAULT NULL,
    `SI4` VARCHAR(200) DEFAULT NULL,
    `paquete_si4` VARCHAR(200) DEFAULT NULL,
    `SI5` VARCHAR(200) DEFAULT NULL,
    `paquete_si5` VARCHAR(200) DEFAULT NULL,
    `S1` VARCHAR(200) DEFAULT NULL,
    `paquete_s1` VARCHAR(200) DEFAULT NULL,
    `S2` VARCHAR(200) DEFAULT NULL,
    `paquete_s2` VARCHAR(200) DEFAULT NULL,
    `S3` VARCHAR(200) DEFAULT NULL,
    `paquete_s3` VARCHAR(200) DEFAULT NULL,
    `S4` VARCHAR(200) DEFAULT NULL,
    `paquete_s4` VARCHAR(200) DEFAULT NULL,
    `S5` VARCHAR(200) DEFAULT NULL,
    `paquete_s5` VARCHAR(200) DEFAULT NULL,
    `MO1` VARCHAR(200) DEFAULT NULL,
    `paquete_mo1` VARCHAR(200) DEFAULT NULL,
    `MO2` VARCHAR(200) DEFAULT NULL,
    `paquete_mo2` VARCHAR(200) DEFAULT NULL,
    `MO3` VARCHAR(200) DEFAULT NULL,
    `paquete_mo3` VARCHAR(200) DEFAULT NULL,
    `MO4` VARCHAR(200) DEFAULT NULL,
    `paquete_mo4` VARCHAR(200) DEFAULT NULL,
    `MO5` VARCHAR(200) DEFAULT NULL,
    `paquete_mo5` VARCHAR(200) DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_pactividades_project_codigo_actividad_inicio` (`project_id`, `codigo`, `actividad_inicio`, `fecha_inicio`),
    INDEX `idx_pactividades_project_codigo` (`project_id`, `codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- FOREIGN KEY CONSTRAINTS
-- ============================================================
ALTER TABLE `project_costos_cuadrillas` ADD CONSTRAINT `fk_pcostos_project` FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE RESTRICT;

ALTER TABLE `project_cuadrillas_tipicas` ADD CONSTRAINT `fk_pcuadrillas_project` FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE RESTRICT;

ALTER TABLE `project_semanas_activas` ADD CONSTRAINT `fk_psemanas_project` FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE RESTRICT;

ALTER TABLE `project_subcontratistas` ADD CONSTRAINT `fk_psubcontratistas_project` FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE RESTRICT;

ALTER TABLE `project_profesionales` ADD CONSTRAINT `fk_pprofesionales_project` FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE RESTRICT;

ALTER TABLE `project_programa` ADD CONSTRAINT `fk_pprograma_project` FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE RESTRICT;

ALTER TABLE `project_programa_consolidado` ADD CONSTRAINT `fk_ppconsolidado_project` FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE RESTRICT;

ALTER TABLE `project_programacion_semanal` ADD CONSTRAINT `fk_ppsemanal_project` FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE RESTRICT;

ALTER TABLE `project_pdc` ADD CONSTRAINT `fk_ppdc_project` FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE RESTRICT;

ALTER TABLE `project_cic` ADD CONSTRAINT `fk_pcic_project` FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE RESTRICT;

ALTER TABLE `project_cambios` ADD CONSTRAINT `fk_pcambios_project` FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE RESTRICT;

ALTER TABLE `project_actividades` ADD CONSTRAINT `fk_pactividades_project` FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE RESTRICT;

-- ============================================================
-- ADDITIONAL INDEXES
-- ============================================================
CREATE INDEX idx_psemanas_semana ON `project_semanas_activas`(`project_id`, `semana`);
CREATE INDEX idx_ppconsolidado_semana ON `project_programa_consolidado`(`project_id`, `semana`);
CREATE INDEX idx_ppsemanal_semana ON `project_programacion_semanal`(`project_id`, `semana`);
CREATE INDEX idx_ppdc_semana ON `project_pdc`(`project_id`, `semana`);
CREATE INDEX idx_pcic_semana ON `project_cic`(`project_id`, `semana`);
CREATE INDEX idx_pcic_subcontratista ON `project_cic`(`project_id`, `subcontratista`);
CREATE INDEX idx_psubcontratistas_nombre ON `project_subcontratistas`(`project_id`, `subcontratista`);
CREATE INDEX idx_pactividades_codigo ON `project_actividades`(`project_id`, `codigo`);
CREATE INDEX idx_pcambios_fecha ON `project_cambios`(`project_id`, `fecha_solicitud`);
CREATE INDEX idx_pprograma_consecutivo ON `project_programa`(`project_id`, `consecutivo`);

SET FOREIGN_KEY_CHECKS = 1;
-- Tables created: project_costos_cuadrillas, project_cuadrillas_tipicas, project_semanas_activas, project_subcontratistas, project_profesionales, project_programa, project_programa_consolidado, project_programacion_semanal, project_pdc, project_cic, project_cambios, project_actividades
