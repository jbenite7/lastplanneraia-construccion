-- Esquema del Plan de Compras v2 para la imagen de base de datos de CI.
--
-- Por que existe este fichero en vez de una lista de migraciones. El v2 lo levantan 37 migraciones,
-- 22 de ellas en PHP, que `/docker-entrypoint-initdb.d` no puede ejecutar; y de las SQL, varias son
-- ALTER que dependen del orden. Ademas `20260729_pdc_v2_subpaquetes.sql` solo se aplica con el
-- cliente `mysql`, nunca por PDO (trampa ya medida, ver goals/pdc-preparar-b1/estado-olas.md). Por
-- eso se hornea el estado FINAL consolidado, igual que hace design-system-ci.sql: es un contrato
-- legible y deterministra, no una reproduccion de la historia.
--
-- Que arregla. Antes de este fichero, /plan-compras respondia con
-- «Table 'lastplanneraia_ci.pdc_presupuesto_versiones' doesn't exist» en el stack aislado: el
-- modulo que hoy ES el plan de compras de la empresa no se podia ejercitar en CI.
--
-- Contenido: solo ESTRUCTURA, sin una sola fila. La pantalla que CI retrata es la de importar
-- presupuesto vacia, que es justo el escenario del manifiesto plan-compras-v2.json (state: "empty").
--
-- Las 18 tablas son la clausura de dependencias medida el 2026-08-04: las 15 `pdc_*` mas los tres
-- catalogos globales a los que apuntan sus claves foraneas (general_maestro_insumos,
-- general_paquetes_contratacion, general_pasos_contratacion), que no dependen de nadie mas.
-- FOREIGN_KEY_CHECKS se desactiva para que el orden de creacion no importe.

SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS `general_maestro_insumos` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `codigo_sinco` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `descripcion` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion_norm` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `unidad` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo_insumo` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `agrupacion` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tipo_recurso` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `clasificado_por` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `clasificado_at` datetime DEFAULT NULL,
  `valor_unitario` decimal(18,4) DEFAULT NULL,
  `iva` decimal(5,2) DEFAULT NULL,
  `activo` tinyint NOT NULL DEFAULT '1',
  `creado_por` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `actualizado_por` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_gmi_norm_unidad` (`descripcion_norm`(191),`unidad`),
  UNIQUE KEY `uq_gmi_codigo_sinco` (`codigo_sinco`),
  KEY `idx_gmi_tipo` (`tipo_insumo`),
  KEY `idx_gmi_agrupacion` (`agrupacion`)
) ENGINE=InnoDB AUTO_INCREMENT=4422 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `general_paquetes_contratacion` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `nombre` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre_norm` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo_negociacion` enum('a_todo_costo','mano_obra','suministro','consumibles','no_aplica') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'a_todo_costo',
  `modalidad_contratacion` enum('contrato','orden_compra','consumo_directo','no_contratable') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'contrato',
  `admite_materiales` tinyint NOT NULL DEFAULT '0',
  `duracion_ref` int DEFAULT NULL,
  `activo` tinyint NOT NULL DEFAULT '1',
  `creado_por` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_gpc_nombre_norm` (`nombre_norm`),
  KEY `idx_gpc_modalidad` (`modalidad_contratacion`,`activo`),
  KEY `idx_gpc_duracion` (`duracion_ref`)
) ENGINE=InnoDB AUTO_INCREMENT=1940 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `general_pasos_contratacion` (
  `id` int NOT NULL AUTO_INCREMENT,
  `clave` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `col_legacy` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dias_sugeridos` int DEFAULT NULL,
  `peso_reparto` decimal(9,6) DEFAULT NULL,
  `orden_default` int NOT NULL DEFAULT '0',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `creado_por` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_gpc_clave` (`clave`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `pdc_correcciones_frente` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `project_id` int NOT NULL,
  `paquete_id` bigint NOT NULL,
  `unique_id_sugerido` int DEFAULT NULL,
  `unique_id_elegido` int NOT NULL,
  `capa_sugerida` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `confianza_sugerida` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `usuario` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_proj_paquete` (`project_id`,`paquete_id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `pdc_correcciones_motor` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `project_id` int NOT NULL,
  `descripcion_norm` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `unidad` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `paquete_sugerido` bigint DEFAULT NULL,
  `paquete_elegido` bigint DEFAULT NULL,
  `capa_sugerida` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `usuario` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_pcm_proyecto` (`project_id`,`descripcion_norm`(150),`unidad`),
  KEY `idx_pcm_capa` (`capa_sugerida`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `pdc_insumo_actividades` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `project_id` int NOT NULL,
  `version_id` bigint NOT NULL,
  `descripcion_norm` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `unidad` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `item_id` bigint NOT NULL,
  `codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `actividad` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `cantidad` decimal(18,4) NOT NULL DEFAULT '0.0000',
  `valor` decimal(18,2) NOT NULL DEFAULT '0.00',
  `unique_id` bigint DEFAULT NULL,
  `origen_amarre` enum('override','exacta','tokens','sin_frente') COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Cómo se resolvió unique_id (B1)',
  `evidencia_amarre` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `semana_amarre` int DEFAULT NULL COMMENT 'Semana del consolidado contra la que se resolvió',
  PRIMARY KEY (`id`),
  KEY `idx_pia_insumo` (`project_id`,`version_id`,`descripcion_norm`(150),`unidad`),
  KEY `idx_pia_item` (`project_id`,`item_id`),
  KEY `idx_pia_unique` (`unique_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2837 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `pdc_insumo_paquete` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `project_id` int NOT NULL,
  `descripcion_norm` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `unidad` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `paquete_id` bigint DEFAULT NULL,
  `subpaquete_id` bigint NOT NULL DEFAULT '0',
  `omitido` tinyint NOT NULL DEFAULT '0',
  `origen` enum('ia','exacta','reglas','tokens','indirectos','agrupacion','humano') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'humano',
  `confianza` enum('alta','media','baja') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `evidencia` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `confirmado_humano` tinyint NOT NULL DEFAULT '1',
  `asignado_por` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_pip_insumo` (`project_id`,`descripcion_norm`(150),`unidad`),
  KEY `idx_pip_paquete` (`project_id`,`paquete_id`),
  KEY `idx_pip_norm` (`descripcion_norm`(150),`unidad`),
  KEY `fk_pip_paquete` (`paquete_id`),
  KEY `idx_pip_subpaquete` (`project_id`,`subpaquete_id`),
  CONSTRAINT `fk_pip_paquete` FOREIGN KEY (`paquete_id`) REFERENCES `general_paquetes_contratacion` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=4192 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `pdc_insumo_vinculos` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `project_id` int NOT NULL,
  `version_id` bigint NOT NULL,
  `descripcion_norm` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `unidad` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion_original` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo_insumo` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `cantidad_total` decimal(18,4) NOT NULL DEFAULT '0.0000',
  `valor_total` decimal(18,2) NOT NULL DEFAULT '0.00',
  `apariciones` int NOT NULL DEFAULT '0',
  `maestro_id` bigint DEFAULT NULL,
  `estado` enum('pendiente','auto','confirmado') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pendiente',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_piv_version_insumo` (`project_id`,`version_id`,`descripcion_norm`(150),`unidad`),
  KEY `idx_piv_project_version_estado` (`project_id`,`version_id`,`estado`),
  KEY `fk_piv_version` (`version_id`),
  KEY `fk_piv_maestro` (`maestro_id`),
  CONSTRAINT `fk_piv_maestro` FOREIGN KEY (`maestro_id`) REFERENCES `general_maestro_insumos` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_piv_version` FOREIGN KEY (`version_id`) REFERENCES `pdc_presupuesto_versiones` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=62483 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `pdc_paquete_frente` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `project_id` int NOT NULL,
  `paquete_id` bigint NOT NULL,
  `subpaquete_id` bigint NOT NULL DEFAULT '0',
  `unique_id` int NOT NULL,
  `frente_nombre` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fecha_ancla` date NOT NULL,
  `semana_origen` int NOT NULL,
  `origen` enum('similitud','rama','correspondencia','humano') COLLATE utf8mb4_unicode_ci NOT NULL,
  `confianza` enum('alta','media','baja') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `evidencia` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `confirmado_humano` tinyint(1) NOT NULL DEFAULT '0',
  `asignado_por` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ppf_destino` (`project_id`,`paquete_id`,`subpaquete_id`),
  KEY `idx_ppf_proyecto_frente` (`project_id`,`unique_id`),
  KEY `fk_ppf_paquete` (`paquete_id`),
  CONSTRAINT `fk_ppf_paquete` FOREIGN KEY (`paquete_id`) REFERENCES `general_paquetes_contratacion` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=2203 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `pdc_plan_paquete` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `project_id` int NOT NULL,
  `paquete_id` bigint NOT NULL,
  `subpaquete_id` bigint NOT NULL DEFAULT '0',
  `unique_id` int DEFAULT NULL,
  `fecha_ancla` date DEFAULT NULL,
  `fecha_arranque` date DEFAULT NULL,
  `dias_totales` int DEFAULT NULL,
  `duracion_ref` int DEFAULT NULL,
  `duracion_provisional` tinyint(1) NOT NULL DEFAULT '0',
  `responsable_user_id` int DEFAULT NULL,
  `responsable_asignado_por` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `responsable_asignado_at` datetime DEFAULT NULL,
  `calculado_por` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ppp_destino` (`project_id`,`paquete_id`,`subpaquete_id`),
  KEY `idx_ppp_proyecto_arranque` (`project_id`,`fecha_arranque`),
  KEY `fk_ppp_paquete` (`paquete_id`),
  KEY `idx_ppp_responsable` (`project_id`,`responsable_user_id`),
  KEY `fk_ppp_responsable` (`responsable_user_id`),
  CONSTRAINT `fk_ppp_paquete` FOREIGN KEY (`paquete_id`) REFERENCES `general_paquetes_contratacion` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_ppp_responsable` FOREIGN KEY (`responsable_user_id`) REFERENCES `general_usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=9409 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `pdc_plan_paso` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `project_id` int NOT NULL,
  `paquete_id` bigint NOT NULL,
  `subpaquete_id` bigint NOT NULL DEFAULT '0',
  `orden` tinyint NOT NULL,
  `paso_id` int DEFAULT NULL,
  `paso` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `dias` int NOT NULL,
  `fecha_inicio` date DEFAULT NULL,
  `fecha_fin` date DEFAULT NULL,
  `fecha_real` date DEFAULT NULL,
  `registrado_por` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `registrado_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_pps_destino_paso` (`project_id`,`paquete_id`,`subpaquete_id`,`paso_id`),
  KEY `fk_pps_paquete` (`paquete_id`),
  KEY `fk_plan_paso_paso` (`paso_id`),
  KEY `idx_pps_destino_orden` (`project_id`,`paquete_id`,`subpaquete_id`,`orden`),
  CONSTRAINT `fk_plan_paso_paso` FOREIGN KEY (`paso_id`) REFERENCES `general_pasos_contratacion` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_pps_paquete` FOREIGN KEY (`paquete_id`) REFERENCES `general_paquetes_contratacion` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=65109 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `pdc_presupuesto_apu_insumos` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `project_id` int NOT NULL,
  `version_id` bigint NOT NULL,
  `item_id` bigint NOT NULL,
  `descripcion` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo_insumo` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `unidad` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `cant_apu` decimal(18,6) DEFAULT NULL,
  `rendimiento` decimal(18,6) DEFAULT NULL,
  `cantidad_total` decimal(18,4) DEFAULT NULL,
  `valor_unitario` decimal(18,2) DEFAULT NULL,
  `valor_total` decimal(18,2) DEFAULT NULL,
  `iva` decimal(5,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_pdcpai_project_version_item` (`project_id`,`version_id`,`item_id`),
  KEY `idx_pdcpai_project_version_desc` (`project_id`,`version_id`,`descripcion`(191)),
  KEY `fk_pdcpai_version` (`version_id`),
  KEY `fk_pdcpai_item` (`item_id`),
  CONSTRAINT `fk_pdcpai_item` FOREIGN KEY (`item_id`) REFERENCES `pdc_presupuesto_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pdcpai_version` FOREIGN KEY (`version_id`) REFERENCES `pdc_presupuesto_versiones` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7227 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `pdc_presupuesto_items` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `project_id` int NOT NULL,
  `version_id` bigint NOT NULL,
  `codigo` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `codigo_padre` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nivel` tinyint NOT NULL,
  `tipo_fila` enum('capitulo','subcapitulo','grupo','actividad') COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `unidad` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cantidad` decimal(18,4) DEFAULT NULL,
  `id_apu` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_pdcpi_project_version_codigo` (`project_id`,`version_id`,`codigo`),
  KEY `idx_pdcpi_project_version_tipo` (`project_id`,`version_id`,`tipo_fila`),
  KEY `fk_pdcpi_version` (`version_id`),
  CONSTRAINT `fk_pdcpi_version` FOREIGN KEY (`version_id`) REFERENCES `pdc_presupuesto_versiones` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7815 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `pdc_presupuesto_versiones` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `project_id` int NOT NULL,
  `version_label` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `version_numero` int NOT NULL DEFAULT '0',
  `archivo_nombre` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `archivo_hash` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contenido_hash` char(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `import_token` char(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `total_actividades` int NOT NULL DEFAULT '0',
  `total_insumos` int NOT NULL DEFAULT '0',
  `costo_total` decimal(18,2) NOT NULL DEFAULT '0.00',
  `activa` tinyint NOT NULL DEFAULT '0',
  `obsoleta` tinyint NOT NULL DEFAULT '0',
  `obsoleta_motivo` varchar(600) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `obsoleta_marcada_at` datetime DEFAULT NULL,
  `activa_unica` int GENERATED ALWAYS AS (if((`activa` = 1),`project_id`,NULL)) VIRTUAL,
  `importado_por` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_pdcpv_project_numero` (`project_id`,`version_numero`),
  UNIQUE KEY `uq_pdcpv_import_token` (`import_token`),
  UNIQUE KEY `uq_pdcpv_activa_unica` (`activa_unica`),
  KEY `idx_pdcpv_project_activa` (`project_id`,`activa`),
  KEY `idx_pdcpv_project_created` (`project_id`,`created_at`),
  KEY `idx_pdcpv_project_hash` (`project_id`,`archivo_hash`)
) ENGINE=InnoDB AUTO_INCREMENT=665 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `pdc_proyecto_pasos` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `project_id` int NOT NULL,
  `paso_id` int NOT NULL,
  `orden` int NOT NULL,
  `alias` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `dias_fijos` int DEFAULT NULL,
  `actualizado_por` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_pps_proyecto_paso` (`project_id`,`paso_id`),
  KEY `idx_pps_proyecto_orden` (`project_id`,`orden`),
  KEY `fk_pps_paso` (`paso_id`),
  CONSTRAINT `fk_pps_paso` FOREIGN KEY (`paso_id`) REFERENCES `general_pasos_contratacion` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=668 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `pdc_proyecto_pasos_historial` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `project_id` int NOT NULL,
  `configuracion` json NOT NULL,
  `pasos` smallint NOT NULL,
  `actualizado_por` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ppph_proyecto` (`project_id`,`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=142 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `pdc_rama_frente` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `project_id` int NOT NULL,
  `rama_norm` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ancla_nombre` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `confirmado_humano` tinyint(1) NOT NULL DEFAULT '1',
  `nota` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `asignado_por` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_proj_rama` (`project_id`,`rama_norm`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `pdc_subpaquete` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `project_id` int NOT NULL,
  `paquete_id` bigint NOT NULL,
  `nombre` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `modalidad_contratacion` enum('contrato','orden_compra','consumo_directo','no_contratable') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'contrato',
  `responsable_user_id` int DEFAULT NULL,
  `es_resto` tinyint(1) NOT NULL DEFAULT '0',
  `orden` smallint NOT NULL DEFAULT '0',
  `creado_por` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_psub_nombre` (`project_id`,`paquete_id`,`nombre`),
  KEY `idx_psub_proyecto_paquete` (`project_id`,`paquete_id`,`orden`),
  KEY `fk_psub_paquete` (`paquete_id`),
  CONSTRAINT `fk_psub_paquete` FOREIGN KEY (`paquete_id`) REFERENCES `general_paquetes_contratacion` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=620 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
