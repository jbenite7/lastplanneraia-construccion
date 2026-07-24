-- 20260724_pdc_v2_paquetes_contratacion.sql
-- PDC v2 / Fase A3: catálogo global de paquetes de contratación (general_*, sin project_id)
-- + asignación insumo→paquete por proyecto.
-- La asignación se clava por (project_id, descripcion_norm, unidad): el re-import hereda el paquete gratis.
-- El motor de sugerencias NO tiene tabla propia: agrega sobre pdc_insumo_paquete entre proyectos.
-- descripcion_norm es varchar(500) (igual que pdc_insumo_vinculos) para JOIN por igualdad; índices con prefijo 150.
-- Estado de un insumo: asignado (paquete_id NOT NULL, omitido=0) | omitido (paquete_id NULL, omitido=1) | sin fila.
-- Invariante de aplicación: omitido=1 ⟺ paquete_id IS NULL (una fila es asignación-a-paquete o omisión, nunca ambas).

CREATE TABLE IF NOT EXISTS `general_paquetes_contratacion` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `nombre` varchar(200) NOT NULL,
  `nombre_norm` varchar(200) NOT NULL,
  `tipo_negociacion` enum('a_todo_costo','mano_obra','suministro','consumibles') NOT NULL DEFAULT 'a_todo_costo',
  `activo` tinyint NOT NULL DEFAULT 1,
  `creado_por` varchar(100) NOT NULL DEFAULT '',
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_gpc_nombre_norm` (`nombre_norm`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pdc_insumo_paquete` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `project_id` int NOT NULL,
  `descripcion_norm` varchar(500) NOT NULL,
  `unidad` varchar(20) NOT NULL,
  `paquete_id` bigint DEFAULT NULL,
  `omitido` tinyint NOT NULL DEFAULT 0,
  `asignado_por` varchar(100) NOT NULL DEFAULT '',
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_pip_insumo` (`project_id`, `descripcion_norm`(150), `unidad`),
  KEY `idx_pip_paquete` (`project_id`, `paquete_id`),
  KEY `idx_pip_norm` (`descripcion_norm`(150), `unidad`),
  CONSTRAINT `fk_pip_paquete` FOREIGN KEY (`paquete_id`) REFERENCES `general_paquetes_contratacion` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
