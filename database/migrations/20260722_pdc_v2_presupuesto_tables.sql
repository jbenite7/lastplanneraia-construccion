-- 20260722_pdc_v2_presupuesto_tables.sql
-- PDC v2 / Fase A1: presupuesto importado, versionado por proyecto.
-- Convención tablas globales: project_id NOT NULL + índice liderado por project_id.

CREATE TABLE IF NOT EXISTS `pdc_presupuesto_versiones` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `project_id` int NOT NULL,
  `version_label` varchar(100) NOT NULL DEFAULT '',
  `archivo_nombre` varchar(255) NOT NULL,
  `archivo_hash` char(64) NOT NULL,
  `total_actividades` int NOT NULL DEFAULT 0,
  `total_insumos` int NOT NULL DEFAULT 0,
  `costo_total` decimal(18,2) NOT NULL DEFAULT 0,
  `activa` tinyint NOT NULL DEFAULT 0,
  `importado_por` varchar(100) NOT NULL DEFAULT '',
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_pdcpv_project_activa` (`project_id`, `activa`),
  KEY `idx_pdcpv_project_created` (`project_id`, `created_at`),
  KEY `idx_pdcpv_project_hash` (`project_id`, `archivo_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pdc_presupuesto_items` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `project_id` int NOT NULL,
  `version_id` bigint NOT NULL,
  `codigo` varchar(50) NOT NULL,
  `codigo_padre` varchar(50) DEFAULT NULL,
  `nivel` tinyint NOT NULL,
  `tipo_fila` enum('capitulo','subcapitulo','grupo','actividad') NOT NULL,
  `descripcion` varchar(500) NOT NULL DEFAULT '',
  `unidad` varchar(20) DEFAULT NULL,
  `cantidad` decimal(18,4) DEFAULT NULL,
  `id_apu` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_pdcpi_project_version_codigo` (`project_id`, `version_id`, `codigo`),
  KEY `idx_pdcpi_project_version_tipo` (`project_id`, `version_id`, `tipo_fila`),
  CONSTRAINT `fk_pdcpi_version` FOREIGN KEY (`version_id`) REFERENCES `pdc_presupuesto_versiones` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pdc_presupuesto_apu_insumos` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `project_id` int NOT NULL,
  `version_id` bigint NOT NULL,
  `item_id` bigint NOT NULL,
  `descripcion` varchar(500) NOT NULL,
  `tipo_insumo` varchar(100) NOT NULL DEFAULT '',
  `unidad` varchar(20) NOT NULL DEFAULT '',
  `cant_apu` decimal(18,6) DEFAULT NULL,
  `rendimiento` decimal(18,6) DEFAULT NULL,
  `cantidad_total` decimal(18,4) DEFAULT NULL,
  `valor_unitario` decimal(18,2) DEFAULT NULL,
  `valor_total` decimal(18,2) DEFAULT NULL,
  `iva` decimal(5,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_pdcpai_project_version_item` (`project_id`, `version_id`, `item_id`),
  KEY `idx_pdcpai_project_version_desc` (`project_id`, `version_id`, `descripcion`(191)),
  CONSTRAINT `fk_pdcpai_version` FOREIGN KEY (`version_id`) REFERENCES `pdc_presupuesto_versiones` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pdcpai_item` FOREIGN KEY (`item_id`) REFERENCES `pdc_presupuesto_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
