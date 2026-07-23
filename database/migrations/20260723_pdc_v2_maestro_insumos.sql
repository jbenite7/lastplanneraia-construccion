-- 20260723_pdc_v2_maestro_insumos.sql
-- PDC v2 / Fase A2: catálogo global de insumos (general_*, sin project_id)
-- y vínculos insumo-consolidado ↔ maestro por proyecto/versión.

CREATE TABLE IF NOT EXISTS `general_maestro_insumos` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `descripcion` varchar(500) NOT NULL,
  `descripcion_norm` varchar(500) NOT NULL,
  `unidad` varchar(20) NOT NULL,
  `tipo_insumo` varchar(100) NOT NULL DEFAULT '',
  `activo` tinyint NOT NULL DEFAULT 1,
  `creado_por` varchar(100) NOT NULL DEFAULT '',
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_gmi_norm_unidad` (`descripcion_norm`(191), `unidad`),
  KEY `idx_gmi_tipo` (`tipo_insumo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pdc_insumo_vinculos` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `project_id` int NOT NULL,
  `version_id` bigint NOT NULL,
  `descripcion_norm` varchar(500) NOT NULL,
  `unidad` varchar(20) NOT NULL,
  `descripcion_original` varchar(500) NOT NULL,
  `tipo_insumo` varchar(100) NOT NULL DEFAULT '',
  `cantidad_total` decimal(18,4) NOT NULL DEFAULT 0,
  `valor_total` decimal(18,2) NOT NULL DEFAULT 0,
  `apariciones` int NOT NULL DEFAULT 0,
  `maestro_id` bigint DEFAULT NULL,
  `estado` enum('pendiente','auto','confirmado') NOT NULL DEFAULT 'pendiente',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_piv_version_insumo` (`project_id`, `version_id`, `descripcion_norm`(150), `unidad`),
  KEY `idx_piv_project_version_estado` (`project_id`, `version_id`, `estado`),
  CONSTRAINT `fk_piv_version` FOREIGN KEY (`version_id`) REFERENCES `pdc_presupuesto_versiones` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_piv_maestro` FOREIGN KEY (`maestro_id`) REFERENCES `general_maestro_insumos` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
