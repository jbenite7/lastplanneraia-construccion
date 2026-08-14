-- Synthetic local-CI fixture only. Every identity, email and operational row below is invented.
-- It is intentionally small but spans construction, pre-construction, BI, RBAC,
-- Handsontable/Contratos, semi-auto and legacy-to-global reconciliation contracts.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Mirror the canonical identity compatibility installed by
-- database/migrations/20260703_program_unique_id_refactor.php. Control-run
-- log entries use consecutivo=0 and must keep unique_id NULL so the real FK
-- continues to reject positive orphan identities.
DELIMITER $$
DROP TRIGGER IF EXISTS `trg_auto_program_log_unique_id_INSERT`$$
CREATE TRIGGER `trg_auto_program_log_unique_id_INSERT` BEFORE INSERT ON `auto_program_log` FOR EACH ROW BEGIN IF NEW.`consecutivo` > 0 AND EXISTS (SELECT 1 FROM `programa` p WHERE p.`project_id` <=> NEW.`project_id` AND p.`unique_id` = NEW.`consecutivo`) THEN SET NEW.`unique_id` = NEW.`consecutivo`; ELSEIF NEW.`unique_id` IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `programa` p WHERE p.`project_id` <=> NEW.`project_id` AND p.`unique_id` = NEW.`unique_id`) THEN SET NEW.`unique_id` = NULL; ELSEIF NEW.`consecutivo` <= 0 THEN SET NEW.`unique_id` = NULL; END IF; END$$
DROP TRIGGER IF EXISTS `trg_auto_program_log_unique_id_UPDATE`$$
CREATE TRIGGER `trg_auto_program_log_unique_id_UPDATE` BEFORE UPDATE ON `auto_program_log` FOR EACH ROW BEGIN IF NEW.`consecutivo` > 0 AND EXISTS (SELECT 1 FROM `programa` p WHERE p.`project_id` <=> NEW.`project_id` AND p.`unique_id` = NEW.`consecutivo`) THEN SET NEW.`unique_id` = NEW.`consecutivo`; ELSEIF NEW.`unique_id` IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `programa` p WHERE p.`project_id` <=> NEW.`project_id` AND p.`unique_id` = NEW.`unique_id`) THEN SET NEW.`unique_id` = NULL; ELSEIF NEW.`consecutivo` <= 0 THEN SET NEW.`unique_id` = NULL; END IF; END$$
DELIMITER ;

CREATE TABLE IF NOT EXISTS `general_usuarios` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `cargo` varchar(100) NOT NULL,
  `usuario` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `force_password_change` tinyint(1) NOT NULL DEFAULT 0,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ci_usuario` (`usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `general_proyectos_procesos` (
  `Id` int NOT NULL,
  `Proyecto_Proceso` varchar(100) NOT NULL,
  `Base_de_Datos` varchar(50) NOT NULL,
  `Area` varchar(50) NOT NULL,
  `pc_restr_2_nombre` varchar(100) DEFAULT NULL,
  `pc_restr_3_nombre` varchar(100) DEFAULT NULL,
  `pc_restr_4_nombre` varchar(100) DEFAULT NULL,
  `Activo` tinyint(1) NOT NULL DEFAULT 1,
  `Acceso` tinyint(1) NOT NULL DEFAULT 1,
  `pdcActivo` tinyint(1) NOT NULL DEFAULT 0,
  `urlCambios` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `uq_ci_project_prefix` (`Base_de_Datos`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `general_codigos_actividades` (
  `codigo_actividad` varchar(11) NOT NULL,
  `actividad` varchar(200) NOT NULL,
  `unidad` varchar(20) NOT NULL,
  PRIMARY KEY (`codigo_actividad`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `general_cnc` (
  `id` int NOT NULL,
  `Categoria_CNC` varchar(100) NOT NULL,
  `CNC` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ci_cnc_category` (`Categoria_CNC`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `general_dias_procesos_contratacion` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tipoPaquete` varchar(100) NOT NULL,
  `paqueteContratacion` varchar(200) NOT NULL,
  `diasElaboracionPliegos` int NOT NULL,
  `diasEntregaPliegos` int NOT NULL,
  `diasReciboPropuestas` int NOT NULL,
  `diasCuadrosComparativos` int NOT NULL,
  `diasLegalizacionContrato` int NOT NULL,
  `diasFabricacion` int NOT NULL,
  `diasInsumosObra` int NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ci_package_duration` (`tipoPaquete`, `paqueteContratacion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `general_pdc_familias` (
  `id` int NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) NOT NULL,
  `nombre` varchar(200) NOT NULL,
  `categoria` varchar(100) DEFAULT NULL,
  `orden` int NOT NULL DEFAULT 0,
  `siempre_revision` tinyint(1) NOT NULL DEFAULT 0,
  `activa` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ci_family_code` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `general_pdc_activity_rules` (
  `id` int NOT NULL AUTO_INCREMENT,
  `familia_id` int NOT NULL,
  `patron_regex` varchar(500) NOT NULL,
  `modalidad_sugerida` varchar(100) DEFAULT NULL,
  `confianza` tinyint NOT NULL DEFAULT 80,
  `prioridad` int NOT NULL DEFAULT 100,
  `descripcion` varchar(500) DEFAULT NULL,
  `activa` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ci_rule` (`familia_id`, `patron_regex`),
  CONSTRAINT `fk_ci_rule_family` FOREIGN KEY (`familia_id`) REFERENCES `general_pdc_familias` (`id`)
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

CREATE TABLE IF NOT EXISTS `general_pdc_family_contract_options` (
  `id` int NOT NULL AUTO_INCREMENT,
  `familia_id` int NOT NULL,
  `tipo_contrato` int NOT NULL,
  `tipo_paquete` varchar(100) NOT NULL,
  `dias_elaboracion` int NOT NULL,
  `dias_entrega` int NOT NULL,
  `dias_recibo` int NOT NULL,
  `dias_cuadros` int NOT NULL,
  `dias_legalizacion` int NOT NULL,
  `dias_fabricacion` int NOT NULL,
  `dias_insumos` int NOT NULL,
  `notas` text DEFAULT NULL,
  `activa` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ci_family_contract_option` (`familia_id`, `tipo_contrato`, `tipo_paquete`),
  CONSTRAINT `fk_ci_option_family` FOREIGN KEY (`familia_id`) REFERENCES `general_pdc_familias` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `general_pdc_family_contract_option_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `option_id` int NOT NULL,
  `tipo_contrato` int DEFAULT NULL,
  `tipo_paquete` varchar(100) DEFAULT NULL,
  `paquete_nombre` varchar(300) NOT NULL,
  `cantidad_default` int NOT NULL DEFAULT 1,
  `dias_proceso_id` int DEFAULT NULL,
  `orden` int NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ci_family_contract_option_item` (`option_id`, `tipo_paquete`, `paquete_nombre`),
  CONSTRAINT `fk_ci_item_option` FOREIGN KEY (`option_id`) REFERENCES `general_pdc_family_contract_options` (`id`),
  CONSTRAINT `fk_ci_item_duration` FOREIGN KEY (`dias_proceso_id`) REFERENCES `general_dias_procesos_contratacion` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `general_pdc_project_family_strategy` (
  `id` int NOT NULL AUTO_INCREMENT,
  `project_id` int DEFAULT NULL,
  `db_prefix` varchar(50) NOT NULL,
  `semana` int NOT NULL,
  `familia_id` int NOT NULL,
  `option_id` int NOT NULL,
  `aplicada` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ci_strategy` (`db_prefix`, `semana`, `familia_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `general_pdc_family_aliases` (
  `id` int NOT NULL AUTO_INCREMENT,
  `alias_nombre` varchar(200) NOT NULL,
  `alias_normalizado` varchar(220) NOT NULL,
  `familia_id` int NOT NULL,
  `alias_family_id` int DEFAULT NULL,
  `fuente` varchar(120) DEFAULT NULL,
  `notas` text DEFAULT NULL,
  `activa` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ci_alias` (`alias_normalizado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `general_pdc_contractual_elements` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(200) NOT NULL,
  `nombre_normalizado` varchar(220) NOT NULL,
  `tipo_paquete` varchar(100) NOT NULL,
  `paquete_nombre` varchar(300) NOT NULL,
  `familia_id` int DEFAULT NULL,
  `fuente` varchar(120) DEFAULT NULL,
  `notas` text DEFAULT NULL,
  `activa` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ci_contractual_element` (`nombre_normalizado`, `tipo_paquete`, `paquete_nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `general_matching_config` (
  `id` int NOT NULL,
  `config_key` varchar(64) NOT NULL,
  `config_value` decimal(5,2) NOT NULL,
  `updated_at` timestamp NOT NULL,
  `updated_by` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ci_matching_key` (`config_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `general_decision_log` (
  `id` bigint NOT NULL,
  `proyecto_id` varchar(50) NOT NULL,
  `semana_objetivo` int NOT NULL,
  `actividad_consecutivo` varchar(20) NOT NULL,
  `actividad_nombre` text NOT NULL,
  `actividad_tokens` json NOT NULL,
  `actividad_posicion_pg` int NOT NULL,
  `actividad_vecinos` json DEFAULT NULL,
  `actividad_capitulo` varchar(100) DEFAULT NULL,
  `engine_usado` varchar(30) NOT NULL,
  `proceso_sugerido` varchar(200) DEFAULT NULL,
  `confianza` decimal(5,4) NOT NULL,
  `regla_aplicada` varchar(50) DEFAULT NULL,
  `candidatos_alternativos` json DEFAULT NULL,
  `explicacion` text DEFAULT NULL,
  `decision_usuario` varchar(20) NOT NULL,
  `proceso_final` varchar(200) NOT NULL,
  `proceso_final_id` int DEFAULT NULL,
  `usuario_id` varchar(20) DEFAULT NULL,
  `timestamp_utc` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `general_curvas` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `project_id` int NOT NULL,
  `Proyecto` varchar(100) NOT NULL,
  `fInicioProyecto` date DEFAULT NULL,
  `fFinProyecto` date DEFAULT NULL,
  `Semana` int NOT NULL,
  `Fecha_Inicio_Sem` date DEFAULT NULL,
  `Fecha_Fin_Sem` date DEFAULT NULL,
  `diasCompletadosReal` decimal(18,6) DEFAULT NULL,
  `diasCompletadosTeorico` decimal(18,6) DEFAULT NULL,
  `diasCompletadosLineaBase` decimal(18,6) DEFAULT NULL,
  `diasTotales` decimal(18,6) DEFAULT NULL,
  `diasTotalesLineaBase` decimal(18,6) DEFAULT NULL,
  `porcentajeCompletadoReal` decimal(18,8) DEFAULT NULL,
  `porcentajeCompletadoTeorico` decimal(18,8) DEFAULT NULL,
  `porcentajeCompletadoLineaBase` decimal(18,8) DEFAULT NULL,
  `diferenciaPorcentajeCompletadoTeorico` decimal(18,8) DEFAULT NULL,
  `diferenciaPorcentajeCompletadoLineaBase` decimal(18,8) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_general_curvas_project_week` (`project_id`, `Semana`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `general_curvas_pdc` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `project_id` int NOT NULL,
  `Proyecto` varchar(100) NOT NULL,
  `semana` int NOT NULL,
  `maxSemana` int DEFAULT NULL,
  `Fecha_Inicio_Sem` date DEFAULT NULL,
  `Fecha_Fin_Sem` date DEFAULT NULL,
  `diasCompletadosReal` decimal(18,6) DEFAULT NULL,
  `diasCompletadosTeorico` decimal(18,6) DEFAULT NULL,
  `diasTotales` decimal(18,6) DEFAULT NULL,
  `porcentajeCompletadoReal` decimal(18,8) DEFAULT NULL,
  `porcentajeCompletadoTeorico` decimal(18,8) DEFAULT NULL,
  `porcentajeCompletadoTeoricoGeneral` decimal(18,8) DEFAULT NULL,
  `porcentajeCompletadoRealGeneral` decimal(18,8) DEFAULT NULL,
  `diferenciaPorcentajeCompletadoTeorico` decimal(18,8) DEFAULT NULL,
  `diferenciaPorcentajeCompletadoTeoricoGeneral` decimal(18,8) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_general_curvas_pdc_project_week` (`project_id`, `semana`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `general_informe_consolidado` AS
SELECT
  CAST(0 AS UNSIGNED) AS `id`, ps.`project_id`, CAST('' AS CHAR(100)) AS `Proyecto`,
  ps.`Semana`, CAST(0 AS SIGNED) AS `maxSemana`, CAST('' AS CHAR(180)) AS `Proyecto_maxSemana`,
  ps.`Actividad`, ps.`Fecha_Inicio`, ps.`Fecha_Fin`, ps.`Fecha_Inicio` AS `Fecha_Inicio_Sem`,
  ps.`Fecha_Fin` AS `Fecha_Fin_Sem`, ps.`Critica`, ps.`Atrasada`, ps.`Activa`, ps.`Ejecutado`,
  ps.`cantidad_ppto`, ps.`Cantidad_Sugerida`, ps.`Compromiso`, ps.`Unidad`, ps.`Ejecutado_Real`,
  ps.`PAC`, ps.`P_Completado`, ps.`Categoria_CNP`, ps.`CNP`, ps.`Observaciones_CNP`,
  ps.`Categoria_CNC`, ps.`CNC`, ps.`Observaciones_CNC`, ps.`Responsable_AIA`, ps.`Sub_Contratista`
FROM `programacion_semanal` ps WHERE 1 = 0;
ALTER TABLE `general_informe_consolidado`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT,
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_general_informe_consolidado_project_week` (`project_id`, `Semana`);

CREATE TABLE IF NOT EXISTS `general_informe_restricciones_consolidado` AS
SELECT
  CAST(0 AS UNSIGNED) AS `id`, pc.`project_id`, CAST('' AS CHAR(100)) AS `Proyecto`, pc.`Semana`,
  pc.`Fecha_Inicio` AS `Fecha_Inicio_Sem`, pc.`Fecha_Fin` AS `Fecha_Fin_Sem`, pc.`Actividad`,
  pc.`Fecha_Inicio`, pc.`Fecha_Fin`, pc.`Semanas_Inicio`, CAST('' AS CHAR(100)) AS `Restriccion`,
  CAST('' AS CHAR(20)) AS `valorRestriccion`, pc.`Ejecutado` AS `estadoActividad`
FROM `programa_consolidado` pc WHERE 1 = 0;
ALTER TABLE `general_informe_restricciones_consolidado`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT,
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_general_informe_restricciones_project_week` (`project_id`, `Semana`);

CREATE TABLE IF NOT EXISTS `general_informe_pdc` AS
SELECT
  CAST(0 AS UNSIGNED) AS `id`, p.`project_id`, CAST('' AS CHAR(100)) AS `Proyecto`, p.`semana`,
  p.`fechaInicio` AS `Fecha_Inicio_Sem`, p.`fechaInicio` AS `Fecha_Fin_Sem`,
  p.`fechaInicio` AS `fechaHoy`, CAST(0 AS SIGNED) AS `maxSemana`,
  CAST('' AS CHAR(180)) AS `Proyecto_maxSemana`, p.`tipoPaquete`, p.`paqueteContratacion`,
  p.`contratos`, p.`numeroSubcontratos`, p.`subcontratoPaquete`, p.`estado`,
  p.`fechaElaboracionPliegos`, p.`diasElaboracionPliegos`, p.`fechaRealElaboracionPliegos`,
  p.`fechaEntregaPliegos`, p.`diasEntregaPliegos`, p.`fechaRealEntregaPliegos`,
  p.`fechaReciboPropuestas`, p.`diasReciboPropuestas`, p.`fechaRealReciboPropuestas`,
  p.`fechaCuadrosComparativos`, p.`diasCuadrosComparativos`, p.`fechaRealCuadrosComparativos`,
  p.`fechaLegalizacionContrato`, p.`diasLegalizacionContrato`, p.`fechaRealLegalizacionContrato`,
  p.`fechaFabricacion`, p.`diasFabricacion`, p.`fechaRealFabricacion`, p.`fechaInsumosObra`,
  p.`diasInsumosObra`, p.`fechaRealInsumosObra`, p.`fechaInicio`, p.`fechaInicioProyectada`,
  p.`fechaRealInicio`, p.`idProveedorAdjudicado`, CAST('' AS CHAR(200)) AS `proveedorAdjudicado`,
  CAST('' AS CHAR(40)) AS `nitProveedorAdjudicado`, p.`numeroContrato`, p.`fechaVencimientoPolizas`,
  p.`valorPresupuesto`, p.`valorPrimeraNegociacion`, p.`valorAdjudicado`, p.`valorAnticipo`,
  p.`valorReclamado`, p.`valorDevoluciones`, p.`observacionesContrato`
FROM `pdc` p WHERE 1 = 0;
ALTER TABLE `general_informe_pdc`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT,
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_general_informe_pdc_project_week` (`project_id`, `semana`);

CREATE TABLE IF NOT EXISTS `general_informe_subcontratistas` AS
SELECT
  c.`Id` AS `id`, c.`project_id`, CAST('' AS CHAR(100)) AS `Proyecto`, c.`Semana`,
  CAST(0 AS SIGNED) AS `maxSemana`, CAST('' AS CHAR(180)) AS `Proyecto_maxSemana`,
  CAST(NULL AS DATE) AS `Fecha_Inicio_Sem`, CAST(NULL AS DATE) AS `Fecha_Fin_Sem`,
  c.`subcontratista`, c.`correo_contacto`, c.`NIT`, c.`alcance`, c.`tipo_proveedor`, c.`PAC`,
  c.`PAC_Acum`, c.`P_Completado`, c.`P_Completado_Acum`, c.`Calidad`, c.`Calidad_Acum`,
  c.`GSA`, c.`GSA_Acum`, c.`SST`, c.`SST_Acum`, c.`ADM`, c.`ADM_Acum`, c.`Cal_Integral`,
  c.`Cal_Integral_Acum`, c.`Observaciones`, c.`mdo_cal_1`, c.`mdo_cal_2`, c.`mdo_cal_3`,
  c.`mdo_adm_1`, c.`mdo_adm_2`, c.`mdo_adm_3`, c.`mdo_adm_4`, c.`mdo_adm_5`,
  c.`mdo_gsa_1`, c.`mdo_gsa_2`, c.`mdo_gsa_3`, c.`mdo_gsa_4`, c.`mdo_gsa_5`, c.`mdo_gsa_6`,
  c.`mdo_gsa_7`, c.`mdo_gsa_8`, c.`mdo_sst_1`, c.`mdo_sst_2`, c.`mdo_sst_3`, c.`mdo_sst_4`,
  c.`mdo_sst_5`, c.`mdo_sst_6`, c.`mdo_sst_7`, c.`mdo_sst_8`, c.`mdo_sst_9`, c.`mdo_sst_10`,
  c.`si_cal_1`, c.`si_cal_2`, c.`si_cal_3`, c.`si_adm_1`, c.`si_adm_2`, c.`si_adm_3`,
  c.`si_adm_4`, c.`si_adm_5`, c.`si_adm_6`, c.`si_gsa_1`, c.`si_gsa_2`, c.`si_gsa_3`,
  c.`si_gsa_4`, c.`si_gsa_5`, c.`si_gsa_6`, c.`si_gsa_7`, c.`si_gsa_8`, c.`si_gsa_9`,
  c.`si_gsa_10`, c.`si_gsa_11`, c.`si_gsa_12`, c.`si_gsa_13`, c.`si_gsa_14`, c.`si_sst_1`,
  c.`si_sst_2`, c.`si_sst_3`, c.`si_sst_4`, c.`si_sst_5`, c.`si_sst_6`, c.`si_sst_7`,
  c.`si_sst_8`, c.`si_sst_9`, c.`si_sst_10`
FROM `cic` c WHERE 1 = 0;
ALTER TABLE `general_informe_subcontratistas`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT,
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_general_informe_sub_project_week` (`project_id`, `Semana`);

-- A real reconciliation gate needs a non-empty archived legacy source.
CREATE TABLE `zleg_da_porto_programa` (
  `Consecutivo` int NOT NULL,
  `Id` varchar(500) DEFAULT NULL,
  `Actividad` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`Consecutivo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `zleg_da_porto_actividades` (
  `Id` int NOT NULL,
  `codigo` int NOT NULL,
  `actividad` varchar(300) NOT NULL,
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `general_usuarios`
  (`id`, `nombre`, `email`, `cargo`, `usuario`, `password`, `force_password_change`, `activo`)
VALUES
  (1, 'CI Administrador', 'admin@ci.invalid', 'Administrador', 'test.A', '$2y$10$vdbXz3NfKDv5Ctyr/ijIVOk9uhCsjNC1dMG3MxMQmBJr/yCLlueJO', 0, 1),
  (2, 'CI Residente', 'resident@ci.invalid', 'Residente de Obra', 'test.R', '$2y$10$vdbXz3NfKDv5Ctyr/ijIVOk9uhCsjNC1dMG3MxMQmBJr/yCLlueJO', 0, 1),
  (3, 'CI Contratista', 'contractor@ci.invalid', 'Subcontratista', 'test.C', '$2y$10$vdbXz3NfKDv5Ctyr/ijIVOk9uhCsjNC1dMG3MxMQmBJr/yCLlueJO', 0, 1),
  (4, 'CI Visualizador', 'viewer@ci.invalid', 'Visualizador', 'test.V', '$2y$10$vdbXz3NfKDv5Ctyr/ijIVOk9uhCsjNC1dMG3MxMQmBJr/yCLlueJO', 0, 1),
  (5, 'CI Oficina Tecnica', 'technical@ci.invalid', 'Oficina Tecnica', 'test.OT', '$2y$10$vdbXz3NfKDv5Ctyr/ijIVOk9uhCsjNC1dMG3MxMQmBJr/yCLlueJO', 0, 1),
  -- ROLE_CASES de programacion-semanal-roles-phases.mjs incluye el rol D; sin este usuario y su
  -- membresia (mas abajo) ese caso no podia siquiera autenticar en el stack aislado.
  (6, 'CI Director de Obra', 'director@ci.invalid', 'Director de Obra', 'test.D', '$2y$10$vdbXz3NfKDv5Ctyr/ijIVOk9uhCsjNC1dMG3MxMQmBJr/yCLlueJO', 0, 1);

INSERT INTO `general_proyectos_procesos`
  (`Id`, `Proyecto_Proceso`, `Base_de_Datos`, `Area`, `pc_restr_2_nombre`, `pc_restr_3_nombre`, `pc_restr_4_nombre`, `Activo`, `Acceso`, `pdcActivo`)
VALUES
  (73, 'Da Porto', 'da_porto', 'Construccion', NULL, NULL, NULL, 1, 1, 1),
  (68, 'Optimización Aeropuerto JMC', 'optimizacionJMC', 'Construccion', NULL, NULL, NULL, 1, 1, 1),
  (75, 'Aeropuerto Regional PC', 'da_aeropuerto_pc', 'Pre-Construccion', 'Presupuesto', 'Contratacion', 'Tramites', 1, 1, 0),
  (76, 'Preconstrucción Da Porto', 'preconstruccion_da_porto_pc', 'Pre-Construccion', NULL, NULL, NULL, 1, 1, 0);

INSERT INTO `rbac_roles`
  (`code`, `name`, `description`, `is_admin_area`, `is_system_admin`, `is_legacy`, `status`, `sort_order`, `created_at`, `updated_at`)
VALUES
  ('A', 'Administrador', 'Synthetic CI administrator', 1, 1, 0, 1, 10, '2026-01-01 00:00:00', '2026-01-01 00:00:00'),
  ('D', 'Director', 'Synthetic CI director', 0, 0, 0, 1, 20, '2026-01-01 00:00:00', '2026-01-01 00:00:00'),
  ('R', 'Residente', 'Synthetic CI resident', 0, 0, 0, 1, 30, '2026-01-01 00:00:00', '2026-01-01 00:00:00'),
  ('OT', 'Oficina Tecnica', 'Synthetic CI technical office', 0, 0, 0, 1, 40, '2026-01-01 00:00:00', '2026-01-01 00:00:00'),
  ('C', 'Subcontratista', 'Synthetic CI contractor', 0, 0, 0, 1, 90, '2026-01-01 00:00:00', '2026-01-01 00:00:00'),
  ('V', 'Visualizador', 'Synthetic CI read-only role', 0, 0, 0, 1, 95, '2026-01-01 00:00:00', '2026-01-01 00:00:00');

INSERT INTO `rbac_permissions`
  (`permission_key`, `module_name`, `action_name`, `description`, `is_write`, `is_sensitive`, `created_at`, `updated_at`)
VALUES
  ('lps.programa_general.ver', 'lps', 'programa_general_ver', 'CI view program', 0, 0, '2026-01-01 00:00:00', '2026-01-01 00:00:00'),
  ('lps.programa_general.editar', 'lps', 'programa_general_editar', 'CI edit program', 1, 0, '2026-01-01 00:00:00', '2026-01-01 00:00:00'),
  ('lps.programacion_intermedia.ver', 'lps', 'programacion_intermedia_ver', 'CI view PI', 0, 0, '2026-01-01 00:00:00', '2026-01-01 00:00:00'),
  ('lps.programacion_semanal.ver', 'lps', 'programacion_semanal_ver', 'CI view PS', 0, 0, '2026-01-01 00:00:00', '2026-01-01 00:00:00'),
  ('lps.listado_actividades.ver', 'lps', 'listado_actividades_ver', 'CI view listado', 0, 0, '2026-01-01 00:00:00', '2026-01-01 00:00:00'),
  ('lps.contratos.ver', 'lps', 'contratos_ver', 'CI view contracts', 0, 0, '2026-01-01 00:00:00', '2026-01-01 00:00:00'),
  ('lps.contratos.editar', 'lps', 'contratos_editar', 'CI edit contracts', 1, 0, '2026-01-01 00:00:00', '2026-01-01 00:00:00'),
  ('lps.pdc.ver', 'lps', 'pdc_ver', 'CI view PDC', 0, 0, '2026-01-01 00:00:00', '2026-01-01 00:00:00'),
  ('lps.cnc.ver', 'lps', 'cnc_ver', 'CI view CNC', 0, 0, '2026-01-01 00:00:00', '2026-01-01 00:00:00'),
  ('lps.cic.ver', 'lps', 'cic_ver', 'CI view CIC', 0, 0, '2026-01-01 00:00:00', '2026-01-01 00:00:00'),
  ('lps.reportes.generar', 'lps', 'reportes_generar', 'CI generate reports', 0, 0, '2026-01-01 00:00:00', '2026-01-01 00:00:00'),
  ('lps.listado_actividades.editar', 'lps', 'listado_actividades_editar', 'Editar listado de actividades', 1, 0, '2026-01-01 00:00:00', '2026-01-01 00:00:00'),
  ('lps.contratos.auto_definir', 'lps', 'contratos_auto_definir', 'Auto-definir contratos con preview y confianza', 1, 0, '2026-01-01 00:00:00', '2026-01-01 00:00:00'),
  ('lps.pdc.auto_generar', 'lps', 'pdc_auto_generar', 'Auto-generar PDC desde el programa general', 1, 0, '2026-01-01 00:00:00', '2026-01-01 00:00:00');

INSERT INTO `rbac_role_permissions`
  (`role_code`, `permission_key`, `allowed`, `source`, `created_at`, `updated_at`)
SELECT role_code, permission_key, 1, 'synthetic_ci', '2026-01-01 00:00:00', '2026-01-01 00:00:00'
FROM (
  SELECT 'A' role_code UNION ALL SELECT 'R' UNION ALL SELECT 'V'
) roles
CROSS JOIN (
  SELECT 'lps.programa_general.ver' permission_key UNION ALL
  SELECT 'lps.programacion_intermedia.ver' UNION ALL SELECT 'lps.programacion_semanal.ver' UNION ALL
  SELECT 'lps.listado_actividades.ver' UNION ALL SELECT 'lps.contratos.ver' UNION ALL
  SELECT 'lps.pdc.ver' UNION ALL SELECT 'lps.cnc.ver' UNION ALL SELECT 'lps.cic.ver' UNION ALL
  SELECT 'lps.reportes.generar'
) permissions;

INSERT INTO `rbac_role_permissions`
  (`role_code`, `permission_key`, `allowed`, `source`, `created_at`, `updated_at`)
VALUES
  ('A', 'lps.programa_general.editar', 1, 'synthetic_ci', '2026-01-01 00:00:00', '2026-01-01 00:00:00'),
  ('R', 'lps.programa_general.editar', 1, 'synthetic_ci', '2026-01-01 00:00:00', '2026-01-01 00:00:00'),
  ('A', 'lps.contratos.editar', 1, 'synthetic_ci', '2026-01-01 00:00:00', '2026-01-01 00:00:00'),
  ('A', 'lps.listado_actividades.editar', 1, 'semi_auto_migration', '2026-01-01 00:00:00', '2026-01-01 00:00:00'),
  ('A', 'lps.contratos.auto_definir', 1, 'semi_auto_migration', '2026-01-01 00:00:00', '2026-01-01 00:00:00'),
  ('A', 'lps.pdc.auto_generar', 1, 'semi_auto_migration', '2026-01-01 00:00:00', '2026-01-01 00:00:00'),
  ('D', 'lps.listado_actividades.editar', 1, 'semi_auto_migration', '2026-01-01 00:00:00', '2026-01-01 00:00:00'),
  ('D', 'lps.contratos.auto_definir', 1, 'semi_auto_migration', '2026-01-01 00:00:00', '2026-01-01 00:00:00'),
  ('D', 'lps.pdc.auto_generar', 1, 'semi_auto_migration', '2026-01-01 00:00:00', '2026-01-01 00:00:00'),
  ('R', 'lps.listado_actividades.editar', 1, 'semi_auto_migration', '2026-01-01 00:00:00', '2026-01-01 00:00:00'),
  ('R', 'lps.contratos.auto_definir', 1, 'semi_auto_migration', '2026-01-01 00:00:00', '2026-01-01 00:00:00'),
  ('R', 'lps.pdc.auto_generar', 1, 'semi_auto_migration', '2026-01-01 00:00:00', '2026-01-01 00:00:00'),
  ('OT', 'lps.listado_actividades.editar', 1, 'semi_auto_migration', '2026-01-01 00:00:00', '2026-01-01 00:00:00'),
  ('OT', 'lps.contratos.auto_definir', 1, 'semi_auto_migration', '2026-01-01 00:00:00', '2026-01-01 00:00:00'),
  ('OT', 'lps.pdc.auto_generar', 1, 'semi_auto_migration', '2026-01-01 00:00:00', '2026-01-01 00:00:00');

INSERT INTO `project_members` (`id`, `project_id`, `user_id`, `role`, `created_at`)
VALUES
  (1, 73, 1, 'A', '2026-01-01 00:00:00'),
  (2, 73, 2, 'R', '2026-01-01 00:00:00'),
  (3, 73, 3, 'C', '2026-01-01 00:00:00'),
  (4, 73, 4, 'V', '2026-01-01 00:00:00'),
  (5, 73, 5, 'OT', '2026-01-01 00:00:00'),
  (6, 75, 1, 'A', '2026-01-01 00:00:00'),
  (7, 75, 2, 'R', '2026-01-01 00:00:00'),
  (8, 75, 4, 'V', '2026-01-01 00:00:00'),
  (9, 68, 1, 'A', '2026-01-01 00:00:00'),
  -- test.R en JMC: el caso "rol R histórico solo puede calificar el compromiso confirmado" abre
  -- sesion con este rol sobre el proyecto 68, que hasta ahora solo tenia a test.A como miembro.
  (11, 68, 2, 'R', '2026-01-01 00:00:00'),
  -- test.D en Da Porto: ROLE_CASES itera los cuatro roles sobre DA_PORTO (73).
  (12, 73, 6, 'D', '2026-01-01 00:00:00'),
  (10, 76, 1, 'A', '2026-01-01 00:00:00');

INSERT INTO `general_codigos_actividades` (`codigo_actividad`, `actividad`, `unidad`)
VALUES ('CI-001', 'Synthetic concrete activity', 'm3'), ('CI-002', 'Synthetic electrical activity', 'ml');

INSERT INTO `general_cnc` (`id`, `Categoria_CNC`, `CNC`)
VALUES (1, 'Programacion', 'Coordinacion pendiente'), (2, 'Materiales', 'Entrega incompleta');

INSERT INTO `general_dias_procesos_contratacion`
  (`id`, `tipoPaquete`, `paqueteContratacion`, `diasElaboracionPliegos`, `diasEntregaPliegos`, `diasReciboPropuestas`, `diasCuadrosComparativos`, `diasLegalizacionContrato`, `diasFabricacion`, `diasInsumosObra`)
VALUES
  (1, 'Suministro e Instalación', 'PINTURAS CI', 8, 7, 1, 5, 10, 14, 3),
  (2, 'Suministro', 'MATERIALES ELECTRICOS CI', 8, 7, 1, 5, 10, 7, 2),
  (3, 'Mano de Obra', 'MANO DE OBRA ELECTRICA CI', 8, 7, 1, 5, 10, 1, 1),
  (4, 'Orden de Compra', 'EQUIPO MENOR CI', 8, 7, 1, 5, 10, 7, 2),
  (5, 'Suministro e Instalación', 'ESTÁNDAR CI', 8, 7, 1, 5, 10, 7, 2);

INSERT INTO `general_pdc_familias`
  (`id`, `codigo`, `nombre`, `categoria`, `orden`, `siempre_revision`, `activa`, `created_at`, `updated_at`)
VALUES
  (1, 'PRELIMINARES', 'Preliminares', 'PRELIMINARES', 1, 0, 1, '2026-01-01 00:00:00', '2026-01-01 00:00:00'),
  (2, 'PINTURAS', 'Pinturas', 'ACABADOS', 2, 0, 1, '2026-01-01 00:00:00', '2026-01-01 00:00:00'),
  (3, 'RED_ELECTRICA', 'Red Electrica', 'INSTALACIONES', 3, 0, 1, '2026-01-01 00:00:00', '2026-01-01 00:00:00');

INSERT INTO `general_pdc_activity_rules`
  (`id`, `familia_id`, `patron_regex`, `modalidad_sugerida`, `confianza`, `prioridad`, `descripcion`, `activa`, `created_at`)
VALUES
  (1, 1, '/PRELIMINARES|CAMPAMENTO/i', 'Suministro', 90, 100, 'Synthetic preliminaries rule', 1, '2026-01-01 00:00:00'),
  (2, 2, '/PINTURA/i', 'Suministro e Instalación', 90, 100, 'Synthetic paints rule', 1, '2026-01-01 00:00:00'),
  (3, 3, '/ELECTRIC/i', 'Mano de Obra y Suministro por separado', 90, 100, 'Synthetic electrical rule', 1, '2026-01-01 00:00:00');

INSERT INTO `general_pdc_chapter_category_map`
  (`id`, `chapter_keyword`, `categoria`, `prioridad`, `activa`, `created_at`)
VALUES
  (1, 'PRELIMINARES', 'PRELIMINARES', 100, 1, '2026-01-01 00:00:00'),
  (2, 'CIMENTACION', 'CIMENTACION', 100, 1, '2026-01-01 00:00:00'),
  (3, 'ESTRUCTURA', 'ESTRUCTURA', 100, 1, '2026-01-01 00:00:00'),
  (4, 'MAMPOSTERIA', 'MAMPOSTERIA', 100, 1, '2026-01-01 00:00:00'),
  (5, 'INSTALACIONES', 'INSTALACIONES', 100, 1, '2026-01-01 00:00:00'),
  (6, 'ACABADOS', 'ACABADOS', 100, 1, '2026-01-01 00:00:00'),
  (7, 'URBANISMO', 'URBANISMO', 100, 1, '2026-01-01 00:00:00'),
  (8, 'EQUIPOS', 'EQUIPOS', 100, 1, '2026-01-01 00:00:00');

INSERT INTO `general_pdc_family_contract_options`
  (`id`, `familia_id`, `tipo_contrato`, `tipo_paquete`, `dias_elaboracion`, `dias_entrega`, `dias_recibo`, `dias_cuadros`, `dias_legalizacion`, `dias_fabricacion`, `dias_insumos`, `activa`, `created_at`, `updated_at`)
VALUES
  (1, 2, 2, 'Suministro e Instalación', 8, 7, 1, 5, 10, 14, 3, 1, '2026-01-01 00:00:00', '2026-01-01 00:00:00'),
  (2, 3, 1, 'Mano de Obra y Suministro por separado', 8, 7, 1, 5, 10, 7, 2, 1, '2026-01-01 00:00:00', '2026-01-01 00:00:00');

INSERT INTO `general_pdc_family_contract_option_items`
  (`id`, `option_id`, `tipo_contrato`, `tipo_paquete`, `paquete_nombre`, `cantidad_default`, `dias_proceso_id`, `orden`, `created_at`)
VALUES
  (1, 1, 2, 'Suministro e Instalación', 'PINTURAS CI', 1, 1, 1, '2026-01-01 00:00:00'),
  (2, 2, 1, 'Suministro', 'MATERIALES ELECTRICOS CI', 1, 2, 1, '2026-01-01 00:00:00'),
  (3, 2, 1, 'Mano de Obra', 'MANO DE OBRA ELECTRICA CI', 1, 3, 2, '2026-01-01 00:00:00');

INSERT INTO `general_pdc_project_family_strategy`
  (`id`, `project_id`, `db_prefix`, `semana`, `familia_id`, `option_id`, `aplicada`, `created_at`, `updated_at`)
VALUES (1, 73, 'da_porto', 1, 2, 1, 1, '2026-01-01 00:00:00', '2026-01-01 00:00:00');

INSERT INTO `general_matching_config` (`id`, `config_key`, `config_value`, `updated_at`, `updated_by`)
VALUES
  (1, 'high_threshold', 0.90, '2026-01-01 00:00:00', 1),
  (2, 'medium_threshold', 0.70, '2026-01-01 00:00:00', 1),
  (3, 'chapter_threshold', 0.70, '2026-01-01 00:00:00', 1);

INSERT INTO `semanas_activas`
  (`project_id`, `Id`, `Semana`, `Fecha_Inicio_Sem`, `Fecha_Fin_Sem`, `Semanal_Confirmada`, `fechaCreacionSemana`)
VALUES
  (73, 1, 1, '2026-07-06', '2026-07-12', 0, '2026-07-01'),
  -- Semana 2 de Da Porto, deliberadamente SIN filas en programacion_semanal: sostiene el caso
  -- "semana sin actividades no fabrica filas ni tarjetas". Sube Max_Semana de Da Porto a 2 (antes
  -- 1); revisado que ningun golden ni gate depende de que sea exactamente 1 (grep por "maxWeek",
  -- y full-app-flow.spec.mjs / los .visual.mjs navegan por route explicita, no por historicidad
  -- —la regla `maxWeek - 2` no se activa hasta maxWeek=3—). Ver spec 2026-08-14.
  (73, 2, 2, '2026-07-13', '2026-07-19', 0, '2026-07-08'),
  -- JMC crece hacia atras (semanas 1-4), nunca hacia adelante: la 5 sigue siendo la maxima y
  -- abierta, que es lo que da por bueno `full-operational-cycle.spec.mjs`. Las cuatro nuevas van
  -- confirmadas para que `programacion-semanal-roles-phases.mjs` tenga semana historica (3) y
  -- semana de calificacion (4) sin fijar ningun literal — las deriva del propio `#Max_Semana` y
  -- `#Semanal_Confirmada` que la vista ya expone. Ver docs/superpowers/specs/
  -- 2026-08-14-fixture-ci-semanal-roles-design.md.
  (68, 2, 1, '2026-06-29', '2026-07-05', 1, '2026-06-24'),
  (68, 3, 2, '2026-07-06', '2026-07-12', 1, '2026-07-01'),
  (68, 4, 3, '2026-07-13', '2026-07-19', 1, '2026-07-08'),
  (68, 5, 4, '2026-07-20', '2026-07-26', 1, '2026-07-15'),
  (68, 1, 5, '2026-07-27', '2026-08-02', 0, '2026-07-01'),
  (75, 1, 3, '2026-07-20', '2026-07-26', 0, '2026-07-01'),
  (76, 1, 1, '2026-07-06', '2026-07-12', 0, '2026-07-01');

INSERT INTO `profesionales` (`project_id`, `id`, `nombre`, `email`, `cargo`, `activo`)
VALUES
  (73, 1, 'Profesional CI Construccion', 'professional73@ci.invalid', 'Residente Oficina Tecnica', 1),
  (68, 1, 'Profesional CI JMC', 'professional68@ci.invalid', 'Residente Oficina Tecnica', 1),
  (75, 1, 'Profesional CI Preconstruccion', 'professional75@ci.invalid', 'Gerente de Proyecto', 1),
  (76, 1, 'Profesional CI Preconstruccion Da Porto', 'professional76@ci.invalid', 'Gerente de Proyecto', 1);

INSERT INTO `subcontratistas`
  (`project_id`, `Id`, `subcontratista`, `correo_contacto`, `NIT`, `alcance`, `tipo_proveedor`, `activo`)
VALUES
  (73, 1, 'Proveedor CI Construccion', 'supplier73@ci.invalid', 900000073, 'Instalaciones sinteticas', 'Mano de Obra', 1),
  (68, 1, 'Proveedor CI JMC', 'supplier68@ci.invalid', 900000068, 'Obras sinteticas', 'Mano de Obra', 1),
  (75, 1, 'Consultor CI Preconstruccion', 'supplier75@ci.invalid', 900000075, 'Diseno sintetico', 'Consultor', 1),
  (76, 1, 'Interesado CI Da Porto', 'supplier76@ci.invalid', 900000076, 'Gestion sintetica', 'Consultor', 1);

INSERT INTO `programa`
  (`project_id`, `unique_id`, `Consecutivo`, `Id`, `Actividad`, `Titulo`, `Fecha_Inicio`, `Fecha_Fin`, `Ruta_Critica`, `Ejecutado`, `Estado`, `Semanas_Inicio`, `Estado_Restricciones`, `D_y_E`, `Materiales`, `MdeO`, `Equipos`, `Predecesora`, `Pdto_Cons`, `Modelo`, `Responsable_AIA`, `restriccion_pc_1`, `restriccion_pc_2`, `restriccion_pc_3`, `restriccion_pc_4`)
VALUES
  (73, 101, 1, '1.1', 'Pintura sintetica nivel uno', 0, '2026-07-06', '2026-07-19', 1, 0.25, 'En Curso', 0, 0.80, 1, 1, 1, 1, 0.5, 1, '1', 'Profesional CI Construccion', '0%', '0%', '0%', '0%'),
  (73, 102, 2, '1.2', 'Red electrica sintetica', 0, '2026-07-13', '2026-08-02', 0, 0.10, 'Actividad Futura', 1, 0.60, 1, 0.5, 0.5, 1, 0.5, 1, '1', 'Profesional CI Construccion', '0%', '0%', '0%', '0%'),
  (73, 103, 101, 'CI.FK.101', 'Ancla sintetica de identidad semanal', 0, '2026-07-06', '2026-07-06', 0, 0.00, 'No Requerida', 0, 1.00, 1, 1, 1, 1, 0, 0, '0', 'Profesional CI Construccion', '0%', '0%', '0%', '0%'),
  (68, 11058, 1, 'JMC.1', 'Actividad sintetica JMC', 0, '2026-07-27', '2026-08-09', 1, 0.20, 'En Curso', 0, 0.80, 1, 1, 1, 1, 0, 0, '1', 'Profesional CI JMC', '0%', '0%', '0%', '0%'),
  -- Semana 3 (historica confirmada) y semana 4 (calificacion): sostienen a
  -- programacion-semanal-roles-phases.mjs. La de Consecutivo 5 es la fila de CNP (Activa=0 en
  -- `programacion_semanal`, mas abajo), no una tabla aparte.
  (68, 11060, 3, 'JMC.3', 'Actividad sintetica JMC semana 3', 0, '2026-07-13', '2026-07-19', 0, 0.30, 'En Curso', 0, 0.80, 1, 1, 1, 1, 0, 0, '1', 'Profesional CI JMC', '0%', '0%', '0%', '0%'),
  (68, 11061, 4, 'JMC.4', 'Actividad sintetica JMC semana 4', 0, '2026-07-20', '2026-07-26', 0, 0.40, 'En Curso', 0, 0.80, 1, 1, 1, 1, 0, 0, '1', 'Profesional CI JMC', '0%', '0%', '0%', '0%'),
  (68, 11062, 5, 'JMC.5', 'Causa no programacion sintetica JMC', 0, '2026-07-20', '2026-07-26', 0, 0.00, 'No Requerida', 0, 1.00, 1, 1, 1, 1, 0, 0, '0', 'Profesional CI JMC', '0%', '0%', '0%', '0%'),
  (68, 11059, 2, 'JMC.2', 'Actividad sintetica JMC dos', 0, '2026-07-27', '2026-08-16', 0, 0.00, 'Actividad Futura', 0, 0.50, 1, 1, 1, 1, 0, 0, '0', 'Profesional CI JMC', '0%', '0%', '0%', '0%'),
  (75, 201, 1, 'PC.1', 'Diseno sintetico de especialidad', 0, '2026-07-20', '2026-08-09', 1, 0.20, 'En Ejecucion', 0, 0.50, 0, 0, 0, 0, 0, 0, '0', 'Profesional CI Preconstruccion', '100%', '50%', '50%', '0%'),
  (75, 202, 2, 'PC.2', 'Presupuesto sintetico coordinado', 0, '2026-07-27', '2026-08-16', 0, 0.00, 'Actividad Futura', 1, 0.25, 0, 0, 0, 0, 0, 0, '0', 'Profesional CI Preconstruccion', '100%', '0%', '0%', '0%'),
  (76, 7601, 1, 'PC.DP.1', 'Actividad sintetica Preconstruccion Da Porto', 0, '2026-07-06', '2026-07-19', 1, 0.20, 'En Ejecucion', 0, 0.50, 0, 0, 0, 0, 0, 0, '0', 'Profesional CI Preconstruccion Da Porto', '100%', '0%', '0%', '0%'),
  (76, 7602, 2, 'PC.DP.2', 'Actividad sintetica Preconstruccion dos', 0, '2026-07-06', '2026-07-26', 0, 0.00, 'Actividad Futura', 0, 0.25, 0, 0, 0, 0, 0, 0, '0', 'Profesional CI Preconstruccion Da Porto', '100%', '0%', '0%', '0%');

INSERT INTO `programa_consolidado`
  (`project_id`, `row_id`, `Consecutivo`, `Semana`, `unique_id`, `Consecutivo_en_Programa`, `Id`, `Actividad`, `Titulo`, `Fecha_Inicio`, `Fecha_Fin`, `Ruta_Critica`, `Ejecutado`, `Estado`, `Semanas_Inicio`, `Estado_Restricciones`, `D_y_E`, `Materiales`, `MdeO`, `Equipos`, `Predecesora`, `Pdto_Cons`, `Modelo`, `Sub_Contratista`, `Responsable_AIA`, `Activa`, `codigo_actividad`, `medir_productividad`, `cantidad_ppto`, `unidad`, `restriccion_pc_1`, `restriccion_pc_2`, `restriccion_pc_3`, `restriccion_pc_4`)
VALUES
  (73, 1001, 1, 1, 101, 1, '1.1', 'Pintura sintetica nivel uno', 0, '2026-07-06', '2026-07-19', 1, 0.25, 'En Curso', 0, 0.80, '1', '1', '1', '1', '0.5', '1', '1', 'Proveedor CI Construccion', 'Profesional CI Construccion', 1, 'CI-001', 1, 100, 'm2', '0%', '0%', '0%', '0%'),
  (73, 1002, 2, 1, 102, 2, '1.2', 'Red electrica sintetica', 0, '2026-07-13', '2026-08-02', 0, 0.10, 'Actividad Futura', 1, 0.60, '1', '0.5', '0.5', '1', '0.5', '1', '1', 'Proveedor CI Construccion', 'Profesional CI Construccion', 1, 'CI-002', 1, 200, 'ml', '0%', '0%', '0%', '0%'),
  (68, 11001, 1, 5, 11058, 1, 'JMC.1', 'Actividad sintetica JMC', 0, '2026-07-27', '2026-08-09', 1, 0.20, 'En Curso', 0, 0.80, '1', '1', '1', '1', '0', '0', '0', 'Proveedor CI JMC', 'Profesional CI JMC', 1, 'CI-001', 1, 100, 'm2', '0%', '0%', '0%', '0%'),
  (68, 11002, 2, 5, 11059, 2, 'JMC.2', 'Actividad sintetica JMC dos', 0, '2026-07-27', '2026-08-16', 0, 0.00, 'Actividad Futura', 0, 0.50, '1', '1', '1', '1', '0', '0', '0', 'Proveedor CI JMC', 'Profesional CI JMC', 1, 'CI-002', 1, 200, 'ml', '0%', '0%', '0%', '0%'),
  -- Sin esta pareja de filas, ProgramChangeDetector::run() (disparado automaticamente por
  -- changeMonitor.js en cada carga de pagina, sin mirar si la semana esta confirmada) trata las
  -- filas de programacion_semanal de las semanas 3 y 4 como huerfanas -no tienen match en
  -- programa_consolidado por unique_id+Semana- y las BORRA en el primer render. Medido el
  -- 2026-08-14: la fila de CNP (Activa=0) no corre este riesgo porque loadPsConsecutivos() solo
  -- mira Activa IN ('1','NA').
  (68, 11003, 3, 3, 11060, 3, 'JMC.2', 'Actividad sintetica JMC semana 3', 0, '2026-07-13', '2026-07-19', 0, 0.30, 'En Curso', 0, 0.80, '1', '1', '1', '1', '0', '0', '0', 'Proveedor CI JMC', 'Profesional CI JMC', 1, 'CI-001', 1, 100, 'm2', '0%', '0%', '0%', '0%'),
  (68, 11004, 4, 4, 11061, 4, 'JMC.3', 'Actividad sintetica JMC semana 4', 0, '2026-07-20', '2026-07-26', 0, 0.40, 'En Curso', 0, 0.80, '1', '1', '1', '1', '0', '0', '0', 'Proveedor CI JMC', 'Profesional CI JMC', 1, 'CI-001', 1, 100, 'm2', '0%', '0%', '0%', '0%'),
  (75, 2001, 1, 3, 201, 1, 'PC.1', 'Diseno sintetico de especialidad', 0, '2026-07-20', '2026-08-09', 1, 0.20, 'En Ejecucion', 0, 0.50, '0', '0', '0', '0', '0', '0', '0', 'Consultor CI Preconstruccion', 'Profesional CI Preconstruccion', 1, 'CI-001', 1, 50, 'und', '100%', '50%', '50%', '0%'),
  (75, 2002, 2, 3, 202, 2, 'PC.2', 'Presupuesto sintetico coordinado', 0, '2026-07-27', '2026-08-16', 0, 0.00, 'Actividad Futura', 1, 0.25, '0', '0', '0', '0', '0', '0', '0', 'Consultor CI Preconstruccion', 'Profesional CI Preconstruccion', 1, 'CI-002', 1, 60, 'und', '100%', '0%', '0%', '0%'),
  (76, 76001, 1, 1, 7601, 1, 'PC.DP.1', 'Actividad sintetica Preconstruccion Da Porto', 0, '2026-07-06', '2026-07-19', 1, 0.20, 'En Ejecucion', 0, 0.50, '0', '0', '0', '0', '0', '0', '0', 'Interesado CI Da Porto', 'Profesional CI Preconstruccion Da Porto', 1, 'CI-001', 1, 50, 'und', '100%', '0%', '0%', '0%'),
  (76, 76002, 2, 1, 7602, 2, 'PC.DP.2', 'Actividad sintetica Preconstruccion dos', 0, '2026-07-06', '2026-07-26', 0, 0.00, 'Actividad Futura', 0, 0.25, '0', '0', '0', '0', '0', '0', '0', 'Interesado CI Da Porto', 'Profesional CI Preconstruccion Da Porto', 1, 'CI-002', 1, 60, 'und', '100%', '0%', '0%', '0%');

INSERT INTO `programacion_semanal`
  (`project_id`, `row_id`, `Consecutivo`, `Semana`, `unique_id`, `Consecutivo_En_Programa`, `Id`, `Actividad`, `Descripcion`, `Ubicacion`, `Fecha_Inicio`, `Fecha_Fin`, `Sub_Contratista`, `Responsable_AIA`, `Empresa`, `Ejecutado`, `Unidad`, `cantidad_ppto`, `Compromiso`, `Ejecutado_Real`, `P_Completado`, `PAC`, `Critica`, `Atrasada`, `Activa`, `Es_TNP`, `Prog_Sin_Restricciones_100`, `Categoria_CNC`, `CNC`, `Observaciones_CNC`)
VALUES
  -- `Critica=0`: hasta el 2026-08-14 era 1, y esa era la unica fila de Da Porto — hacia que el
  -- chip "Ruta Critica" de la leyenda de alertas contara distinto de cero, y el caso 383 («filtro
  -- sin resultados conserva dropdown y modales operables») exige que los cinco chips lean (0).
  -- Sin consumidor conocido que dependa de que sea critica (revisado: legend-honesty lee tokens
  -- CSS, no esta fila; ningun golden visual la referencia). Ver spec 2026-08-14.
  (73, 3001, 1, 1, 101, 1, '1.1', 'Pintura sintetica nivel uno', 'Compromiso CI', 'Nivel 1', '2026-07-06', '2026-07-12', 'Proveedor CI Construccion', 'Profesional CI Construccion', 'AIA', 0.25, 'm2', 100, 25, 20, 0.80, 0, 0, 0, '1', 0, 1, 'Programacion', 'Coordinacion pendiente', 'Registro sintetico'),
  (68, 3002, 1, 5, 11058, 1, 'JMC.1', 'Actividad semanal sintetica JMC', 'Compromiso JMC CI', 'Nivel 1', '2026-07-27', '2026-08-02', 'Proveedor CI JMC', 'Profesional CI JMC', 'AIA', 0.20, 'm2', 100, 25, 20, 0.80, 0, 1, 0, '1', 0, 1, 'Programacion', 'Coordinacion pendiente', 'Registro sintetico JMC'),
  -- Semana 3 (historica confirmada): responsables + Compromiso > 0, precondicion de
  -- `esCompromisoConfirmado` en programacion-semanal-roles-phases.mjs.
  (68, 3003, 2, 3, 11060, 3, 'JMC.2', 'Actividad semanal sintetica JMC semana 3', 'Compromiso JMC CI semana 3', 'Nivel 1', '2026-07-13', '2026-07-19', 'Proveedor CI JMC', 'Profesional CI JMC', 'AIA', 0.30, 'm2', 100, 30, 24, 0.80, 0, 0, 0, '1', 0, 1, 'Programacion', 'Coordinacion pendiente', 'Registro sintetico JMC semana 3'),
  -- Semana 4 (calificacion, la que abren openJmcQualification y resolveConfirmedWeek):
  -- Compromiso=50 y Es_TNP=0 a proposito. El caso "API semanal rechaza fase, CNC incompleta..."
  -- postea Real=39 esperando 422 por incumplimiento sin CNC — esa rama del controlador
  -- (SemanalApiController.php:322) se salta entera si Es_TNP=1, y solo dispara si
  -- Real(39) < Compromiso. Compromiso=25 (como en las demas filas synteticas) habria hecho que
  -- 39 < 25 fuera falso y el 422 no ocurriera por el motivo que la prueba dice probar.
  (68, 3004, 3, 4, 11061, 4, 'JMC.3', 'Actividad semanal sintetica JMC semana 4', 'Compromiso JMC CI semana 4', 'Nivel 1', '2026-07-20', '2026-07-26', 'Proveedor CI JMC', 'Profesional CI JMC', 'AIA', 0.40, 'm2', 100, 50, 20, 0.80, 0, 0, 0, '1', 0, 1, 'Programacion', 'Coordinacion pendiente', 'Registro sintetico JMC semana 4'),
  -- Fila de CNP (Activa=0, semana 4 confirmada): /api/semanal/list filtra Activa IN ('1','NA'),
  -- asi que esta fila nunca aparece ahi y no interfiere con la de arriba; /api/cnp/list filtra
  -- exactamente Activa=0. Sostiene el caso "API CNP no reprograma una semana confirmada", movido
  -- de un proyecto 27 que no existe en este fixture al 68 (decision de la sesion, 2026-08-14).
  (68, 3005, 4, 4, 11062, 5, 'JMC.4', 'Causa no programacion sintetica JMC', 'Causa no programacion JMC', 'Nivel 1', '2026-07-20', '2026-07-26', 'Proveedor CI JMC', 'Profesional CI JMC', 'AIA', 0.00, 'm2', 100, 0, 0, 0.00, 0, 0, 0, '0', 0, 0, NULL, NULL, NULL),
  (75, 4001, 1, 3, 201, 1, 'PC.1', 'Diseno sintetico de especialidad', 'Compromiso PC CI', 'Mesa tecnica', '2026-07-20', '2026-07-26', 'Consultor CI Preconstruccion', 'Profesional CI Preconstruccion', 'AIA', 0.20, 'und', 50, 10, 8, 0.80, 1, 1, 0, '1', 0, 1, NULL, NULL, NULL),
  (76, 6001, 1, 1, 7601, 1, 'PC.DP.1', 'Actividad semanal sintetica Preconstruccion', 'Compromiso PC Da Porto', 'Mesa tecnica', '2026-07-06', '2026-07-12', 'Interesado CI Da Porto', 'Profesional CI Preconstruccion Da Porto', 'AIA', 0.20, 'und', 50, 10, 8, 0.80, 1, 1, 0, '1', 0, 1, NULL, NULL, NULL);

INSERT INTO `actividades`
  (`project_id`, `Id`, `codigo`, `actividad`, `descripcionActividad`, `actividadInicio`, `nombreActividadInicio`, `fechaInicio`, `tipoContrato`, `semanaActualizacion`, `SI1`, `paqueteSI1`, `S1`, `paqueteS1`, `MO1`, `paqueteMO1`, `numeroSubcontratos`, `confianza_deteccion`, `fechaInicioProyectada`)
VALUES
  (73, 1, 1001, 'Pinturas', 'Actividad contractual sintetica', '101', 'Pintura sintetica nivel uno', '2026-07-06', 'SI', 1, 'Pintura', 'PINTURAS CI', NULL, NULL, NULL, NULL, 1, 95.00, '2026-07-06'),
  (73, 2, 1002, 'Red Electrica', 'Actividad contractual sintetica', '102', 'Red electrica sintetica', '2026-07-13', 'S,MO', 1, NULL, NULL, 'Cableado', 'MATERIALES ELECTRICOS CI', 'Instalacion', 'MANO DE OBRA ELECTRICA CI', 2, 90.00, '2026-07-13'),
  (68, 1, 11058, 'JMC Suministro', 'Actividad contractual sintetica JMC', '11058', 'Actividad sintetica JMC', '2026-07-27', 'S,MO', 5, 'JMC Material', 'JMC MATERIAL CI', NULL, NULL, 'JMC Instalacion', 'JMC MANO DE OBRA CI', 2, 95.00, '2026-07-27');

INSERT INTO `pdc`
  (`project_id`, `pdc_row_id`, `consecutivo`, `semana`, `titulo`, `tipoPaquete`, `paqueteContratacion`, `contratos`, `numeroSubcontratos`, `subcontratoPaquete`, `estado`, `fechaElaboracionPliegos`, `diasElaboracionPliegos`, `fechaEntregaPliegos`, `diasEntregaPliegos`, `fechaReciboPropuestas`, `diasReciboPropuestas`, `fechaCuadrosComparativos`, `diasCuadrosComparativos`, `fechaLegalizacionContrato`, `diasLegalizacionContrato`, `fechaFabricacion`, `diasFabricacion`, `fechaInsumosObra`, `diasInsumosObra`, `fechaInicio`, `fechaInicioProyectada`, `fechaRealInsumosObra`, `idProveedorAdjudicado`, `valorPresupuesto`, `observacionesContrato`)
VALUES
  (73, 5001, 1, 1, 0, 'Suministro e Instalación', 'PINTURAS CI', 'Pinturas', 1, 1, 'En proceso', '2026-06-01', 8, '2026-06-09', 7, '2026-06-16', 1, '2026-06-17', 5, '2026-06-22', 10, '2026-07-02', 14, '2026-07-16', 3, '2026-07-20', '2026-07-20', '2026-07-18', 1, 25000000, 'Paquete sintetico CI');

INSERT INTO `cic`
  (`project_id`, `Id`, `Semana`, `subcontratista`, `correo_contacto`, `NIT`, `alcance`, `tipo_proveedor`, `PAC`, `PAC_Acum`, `P_Completado`, `P_Completado_Acum`, `Calidad`, `Calidad_Acum`, `GSA`, `GSA_Acum`, `SST`, `SST_Acum`, `ADM`, `ADM_Acum`, `Cal_Integral`, `Cal_Integral_Acum`, `Observaciones`)
VALUES
  (73, 1, 1, 'Proveedor CI Construccion', 'supplier73@ci.invalid', '900000073', 'Instalaciones sinteticas', 'Mano de Obra', '0.80', '0.75', '0.80', '0.75', '80', '75', '80', '75', '80', '75', '80', '75', 80, 75, 'Evaluacion sintetica'),
  (68, 1, 5, 'Proveedor CI JMC', 'supplier68@ci.invalid', '900000068', 'Obras sinteticas', 'Mano de Obra', '0.85', '0.80', '0.85', '0.80', '85', '80', '85', '80', '85', '80', '85', '80', 85, 80, 'Evaluacion sintetica JMC'),
  (75, 1, 3, 'Consultor CI Preconstruccion', 'supplier75@ci.invalid', '900000075', 'Diseno sintetico', 'Consultor', '0.90', '0.85', '0.90', '0.85', '90', '85', '90', '85', '90', '85', '90', '85', 90, 85, 'Evaluacion sintetica PC'),
  (76, 1, 1, 'Interesado CI Da Porto', 'supplier76@ci.invalid', '900000076', 'Gestion sintetica', 'Consultor', '0.90', '0.85', '0.90', '0.85', '90', '85', '90', '85', '90', '85', '90', '85', 90, 85, 'Evaluacion sintetica Preconstruccion');

INSERT INTO `cip`
  (`Id`, `project_id`, `Semana`, `profesional`, `correo_contacto`, `PAC`, `PAC_Acum`, `P_Completado`, `P_Completado_Acum`, `Act_Criticas_Cumplidas`, `Act_No_Criticas_Cumplidas`, `Act_Atrasadas_Cumplidas`, `Act_Criticas_Cumplidas_Acum`, `Act_No_Criticas_Cumplidas_Acum`, `Act_Atrasadas_Cumplidas_Acum`, `PAC_Consolidado`, `PAC_Consolidado_Acum`)
VALUES
  (1, 73, 1, 'Profesional CI Construccion', 'professional73@ci.invalid', '0.80', '0.75', '0.80', '0.75', '1', '1', '0', '1', '1', '0', '0.80', '0.75'),
  (2, 68, 5, 'Profesional CI JMC', 'professional68@ci.invalid', '0.85', '0.80', '0.85', '0.80', '1', '1', '0', '1', '1', '0', '0.85', '0.80'),
  (3, 75, 3, 'Profesional CI Preconstruccion', 'professional75@ci.invalid', '0.90', '0.85', '0.90', '0.85', '1', '1', '0', '1', '1', '0', '0.90', '0.85'),
  (4, 76, 1, 'Profesional CI Preconstruccion Da Porto', 'professional76@ci.invalid', '0.90', '0.85', '0.90', '0.85', '1', '1', '0', '1', '1', '0', '0.90', '0.85');

INSERT INTO `pi_shared_constraints`
  (`project_id`, `Id`, `Semana`, `Restriccion`, `ValorObjetivo`, `Nota`, `CreadoPor`, `CreadoEn`, `ActualizadoEn`)
VALUES (73, 1, 1, 'Materiales', '1', 'Restriccion sintetica', 'test.A', '2026-01-01 00:00:00', '2026-01-01 00:00:00');

INSERT INTO `pi_shared_constraint_links`
  (`project_id`, `Id`, `SharedConstraintId`, `Semana`, `unique_id`, `ConsecutivoEnPrograma`, `ValorAplicado`, `OverrideLocal`, `AplicadoEn`)
VALUES (73, 1, 1, 1, 101, 1, '1', 0, '2026-01-01 00:00:00');

INSERT INTO `indicadores_generales`
  (`id`, `project_id`, `Semana`, `subcontratista_profesional`, `rol`, `PAC`, `P_Completado`)
VALUES
  (1, 73, 1, 'Proveedor CI Construccion', 'Subcontratista', '0.80', '0.80'),
  (2, 75, 3, 'Profesional CI Preconstruccion', 'Profesional', '0.90', '0.90');

INSERT INTO `zleg_da_porto_programa` (`Consecutivo`, `Id`, `Actividad`)
VALUES (1, '1.1', 'Pintura sintetica nivel uno');

INSERT INTO `zleg_da_porto_actividades` (`Id`, `codigo`, `actividad`)
VALUES (1, 1001, 'Pinturas');

SET FOREIGN_KEY_CHECKS = 1;
