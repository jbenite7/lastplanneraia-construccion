SET NAMES utf8mb4;

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
  UNIQUE KEY `uq_pdc_family_alias_normalized` (`alias_normalizado`),
  KEY `idx_pdc_family_alias_family` (`familia_id`, `activa`),
  KEY `idx_pdc_family_alias_legacy_family` (`alias_family_id`)
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
  UNIQUE KEY `uq_pdc_contractual_element` (`nombre_normalizado`, `tipo_paquete`, `paquete_nombre`),
  KEY `idx_pdc_contractual_family` (`familia_id`, `activa`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `general_pdc_family_rule_audit` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `rule_id` int DEFAULT NULL,
  `old_familia_id` int DEFAULT NULL,
  `new_familia_id` int DEFAULT NULL,
  `accion` varchar(80) NOT NULL,
  `motivo` varchar(500) DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `created_by` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_pdc_rule_audit_rule` (`rule_id`),
  KEY `idx_pdc_rule_audit_action` (`accion`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `general_pdc_familias`
  ADD COLUMN IF NOT EXISTS `activa` tinyint(1) NOT NULL DEFAULT 1 AFTER `siempre_revision`;

INSERT IGNORE INTO `general_pdc_familias` (`codigo`, `nombre`, `categoria`, `orden`, `siempre_revision`)
VALUES
  ('PISOS_ENCHAPES', 'Pisos y Enchapes', 'ACABADOS', 28, 0),
  ('RED_EXTINCION', 'Red de Extinción', 'INSTALACIONES', 50, 0);

INSERT IGNORE INTO `general_pdc_family_aliases`
  (`alias_nombre`, `alias_normalizado`, `familia_id`, `alias_family_id`, `fuente`, `notas`)
SELECT 'Enchapes Ceramicos en Muros', 'ENCHAPES CERAMICOS EN MUROS', canon.id, legacy.id,
       'matriz_validacion_humana', 'El usuario indicó que va dentro de Pisos y Enchapes.'
FROM `general_pdc_familias` canon
LEFT JOIN `general_pdc_familias` legacy ON legacy.`nombre` = 'Enchapes Ceramicos en Muros'
WHERE canon.`nombre` = 'Pisos y Enchapes'
LIMIT 1;

INSERT IGNORE INTO `general_pdc_family_aliases`
  (`alias_nombre`, `alias_normalizado`, `familia_id`, `alias_family_id`, `fuente`, `notas`)
SELECT 'Red RCI', 'RED RCI', canon.id, legacy.id,
       'matriz_validacion_humana', 'Red RCI y Red Contra Incendio - Piping son una sola familia.'
FROM `general_pdc_familias` canon
LEFT JOIN `general_pdc_familias` legacy ON legacy.`nombre` = 'Red RCI'
WHERE canon.`nombre` = 'Red de Extinción'
LIMIT 1;

INSERT IGNORE INTO `general_pdc_family_aliases`
  (`alias_nombre`, `alias_normalizado`, `familia_id`, `alias_family_id`, `fuente`, `notas`)
SELECT 'Red Contra Incendio - Piping', 'RED CONTRA INCENDIO PIPING', canon.id, legacy.id,
       'matriz_validacion_humana', 'Red RCI y Red Contra Incendio - Piping son una sola familia.'
FROM `general_pdc_familias` canon
LEFT JOIN `general_pdc_familias` legacy ON legacy.`nombre` = 'Red Contra Incendio - Piping'
WHERE canon.`nombre` = 'Red de Extinción'
LIMIT 1;

INSERT IGNORE INTO `general_pdc_contractual_elements`
  (`nombre`, `nombre_normalizado`, `tipo_paquete`, `paquete_nombre`, `familia_id`, `fuente`, `notas`)
VALUES
  ('Acero de Refuerzo y Estructural', 'ACERO DE REFUERZO Y ESTRUCTURAL', 'Suministro', 'ACERO DE REFUERZO', NULL, 'matriz_validacion_humana', 'Elemento contractual; se autogenera en Contratos.'),
  ('Aligerantes Perdidos y Recuperables', 'ALIGERANTES PERDIDOS Y RECUPERABLES', 'Suministro', 'ALIGERANTE LOSAS', NULL, 'matriz_validacion_humana', 'Elemento contractual; se autogenera en Contratos.'),
  ('Contenedores', 'CONTENEDORES', 'Suministro', 'CONTENEDORES', NULL, 'matriz_validacion_humana', 'Elemento contractual; se autogenera en Contratos.'),
  ('Encofrado y Obra Falsa', 'ENCOFRADO Y OBRA FALSA', 'Suministro e Instalación', 'ENCOFRADO Y OBRA FALSA', NULL, 'matriz_validacion_humana', 'Elemento contractual; se autogenera en Contratos.'),
  ('Equipos de Extincion', 'EQUIPOS DE EXTINCION', 'Suministro', 'EQUIPOS DE EXTINCION', NULL, 'matriz_validacion_humana', 'Elemento contractual; se autogenera en Contratos.'),
  ('Estuco', 'ESTUCO', 'Mano de Obra', 'ESTUCO', NULL, 'matriz_validacion_humana', 'Elemento contractual; se autogenera en Contratos.'),
  ('Fachada HPL, Vidrio y Aluminio', 'FACHADA HPL VIDRIO Y ALUMINIO', 'Suministro e Instalación', 'FACHADA HPL, VIDRIO Y ALUMINIO', NULL, 'matriz_validacion_humana', 'Elemento contractual; se autogenera en Contratos.'),
  ('Geodren', 'GEODREN', 'Suministro e Instalación', 'GEODREN', NULL, 'matriz_validacion_humana', 'Elemento contractual; se autogenera en Contratos.'),
  ('Losas de Cimentacion', 'LOSAS DE CIMENTACION', 'Mano de Obra', 'LOSAS DE CIMENTACION', NULL, 'matriz_validacion_humana', 'Elemento contractual; se autogenera en Contratos.'),
  ('Luminarias y Artefactos Electricos', 'LUMINARIAS Y ARTEFACTOS ELECTRICOS', 'Suministro', 'LUMINARIAS Y ARTEFACTOS ELECTRICOS', NULL, 'matriz_validacion_humana', 'Elemento contractual; se autogenera en Contratos.'),
  ('Mano de Obra - Acabados', 'MANO DE OBRA ACABADOS', 'Mano de Obra', 'ACABADOS', NULL, 'matriz_validacion_humana', 'Elemento contractual; se autogenera en Contratos.'),
  ('Mano de Obra - Cimentacion', 'MANO DE OBRA CIMENTACION', 'Mano de Obra', 'CIMENTACION', NULL, 'matriz_validacion_humana', 'Elemento contractual; se autogenera en Contratos.'),
  ('Mano de Obra - Estructura', 'MANO DE OBRA ESTRUCTURA', 'Mano de Obra', 'ESTRUCTURA', NULL, 'matriz_validacion_humana', 'Elemento contractual; se autogenera en Contratos.'),
  ('Mano de Obra - Excavaciones', 'MANO DE OBRA EXCAVACIONES', 'Mano de Obra', 'EXCAVACIONES', NULL, 'matriz_validacion_humana', 'Elemento contractual; se autogenera en Contratos.'),
  ('Mano de Obra - Instalaciones', 'MANO DE OBRA INSTALACIONES', 'Mano de Obra', 'INSTALACIONES', NULL, 'matriz_validacion_humana', 'Elemento contractual; se autogenera en Contratos.'),
  ('Mano de Obra - Mamposteria', 'MANO DE OBRA MAMPOSTERIA', 'Mano de Obra', 'MAMPOSTERIA', NULL, 'matriz_validacion_humana', 'Elemento contractual; se autogenera en Contratos.'),
  ('Mano de Obra - Urbanismo', 'MANO DE OBRA URBANISMO', 'Mano de Obra', 'URBANISMO', NULL, 'matriz_validacion_humana', 'Elemento contractual; se autogenera en Contratos.');

INSERT INTO `general_pdc_family_rule_audit`
  (`rule_id`, `old_familia_id`, `new_familia_id`, `accion`, `motivo`, `metadata`, `created_by`)
SELECT r.id, r.familia_id, a.familia_id, 'reasignar_alias_a_canonica',
       CONCAT('Regla reasignada desde alias ', lf.nombre, ' hacia familia canónica ', cf.nombre),
       JSON_OBJECT('alias', a.alias_nombre, 'legacy_family_name', lf.nombre, 'canonical_family_name', cf.nombre),
       'migration_20260706_family_catalog_refactor'
FROM `general_pdc_activity_rules` r
JOIN `general_pdc_family_aliases` a ON a.alias_family_id = r.familia_id AND a.activa = 1
JOIN `general_pdc_familias` lf ON lf.id = a.alias_family_id
JOIN `general_pdc_familias` cf ON cf.id = a.familia_id
LEFT JOIN `general_pdc_family_rule_audit` prev
  ON prev.rule_id = r.id
 AND prev.accion = 'reasignar_alias_a_canonica'
 AND prev.old_familia_id = r.familia_id
 AND prev.new_familia_id = a.familia_id
WHERE prev.id IS NULL;

UPDATE `general_pdc_activity_rules` r
JOIN `general_pdc_family_aliases` a ON a.alias_family_id = r.familia_id AND a.activa = 1
SET r.familia_id = a.familia_id;
