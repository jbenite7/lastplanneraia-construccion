-- Patch: Tablas de mapeo PG → PDC (auto-generación)
-- Fecha: 2026-06-11
-- Fase 2 del plan de automatización del Plan de Compras
-- Mapea actividades del PG del proyecto a paquetes del PDC vía familias, estrategia y modalidad.
-- Las tablas `general_pdc_plantillas` y `general_pdc_plantilla_items` (Fase 1) se mantienen intactas.

-- ═══════════════════════════════════════════════════════════════════════════
-- 1. FAMILIAS CONSTRUCTIVAS
-- Agrupa actividades del PG en familias reconocibles (ACERO, CONCRETO, etc.)
-- ═══════════════════════════════════════════════════════════════════════════

CREATE TABLE IF NOT EXISTS `general_pdc_familias` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `codigo` VARCHAR(50) NOT NULL UNIQUE COMMENT 'Código corto: ACERO, CONCRETO, etc.',
    `nombre` VARCHAR(200) NOT NULL,
    `categoria` VARCHAR(100) COMMENT 'ESTRUCTURA, ACABADOS, INSTALACIONES, etc.',
    `orden` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ═══════════════════════════════════════════════════════════════════════════
-- 2. REGLAS DE MAPEO DE ACTIVIDADES
-- Cada regla asocia un patrón regex (sobre el nombre de actividad del PG)
-- con una familia y sugiere una modalidad de contrato.
-- ═══════════════════════════════════════════════════════════════════════════

CREATE TABLE IF NOT EXISTS `general_pdc_activity_rules` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `familia_id` INT NOT NULL,
    `patron_regex` VARCHAR(500) NOT NULL COMMENT 'Expresión regular para matchear nombre actividad PG',
    `modalidad_sugerida` VARCHAR(100) DEFAULT NULL COMMENT 'Nombre completo de modalidad sugerida; NULL si ambiguo',
    `confianza` TINYINT DEFAULT 80 COMMENT '0-100: 80+ auto, 70-79 review, <70 always ask',
    `prioridad` INT DEFAULT 100 COMMENT 'Más alto = se evalúa primero (reglas específicas antes que genéricas)',
    `descripcion` VARCHAR(500),
    `activa` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_pdc_activity_rule` (`familia_id`, `patron_regex`),
    FOREIGN KEY (`familia_id`) REFERENCES `general_pdc_familias`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ═══════════════════════════════════════════════════════════════════════════
-- 3. ALIASES DE PAQUETES
-- Maneja duplicados semánticos: "CONCRETO" canonical, "CONCRETO PARA ESTRUCTURA" alias
-- ═══════════════════════════════════════════════════════════════════════════

CREATE TABLE IF NOT EXISTS `general_pdc_paquete_aliases` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `alias` VARCHAR(300) NOT NULL UNIQUE COMMENT 'Nombre tal como aparece en PDC histórico',
    `canonical_id` INT NOT NULL COMMENT 'ID del paquete canonical en general_dias_procesos_contratacion',
    `notas` VARCHAR(500),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`canonical_id`) REFERENCES `general_dias_procesos_contratacion`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ═══════════════════════════════════════════════════════════════════════════
-- 4. OPCIONES DE CONTRATO POR FAMILIA
-- Una familia puede ofrecer múltiples modalidades: Suministro e Instalación,
-- Suministro, Mano de Obra o Mano de Obra y Suministro por separado.
-- Cada opción tiene días reales de fabricación, etc.
-- ═══════════════════════════════════════════════════════════════════════════

CREATE TABLE IF NOT EXISTS `general_pdc_family_contract_options` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `familia_id` INT NOT NULL,
    `tipo_contrato` INT NOT NULL COMMENT '1=Mano de Obra y Suministro por separado, 2=Suministro e Instalación',
    `tipo_paquete` VARCHAR(100) NOT NULL COMMENT 'Nombre completo de modalidad/paquete',
    `dias_elaboracion` INT DEFAULT 8,
    `dias_entrega` INT DEFAULT 10,
    `dias_recibo` INT DEFAULT 1 COMMENT 'Siempre 1 — constante confirmada',
    `dias_cuadros` INT DEFAULT 10,
    `dias_legalizacion` INT DEFAULT 10,
    `dias_fabricacion` INT DEFAULT 0,
    `dias_insumos` INT DEFAULT 0,
    `notas` TEXT,
    `activa` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_pdc_family_contract_option` (`familia_id`, `tipo_contrato`, `tipo_paquete`),
    FOREIGN KEY (`familia_id`) REFERENCES `general_pdc_familias`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ═══════════════════════════════════════════════════════════════════════════
-- 5. ITEMS DE OPCIÓN DE CONTRATO (PAQUETES)
-- Cada opción de contrato genera uno o más paquetes en el PDC.
-- ═══════════════════════════════════════════════════════════════════════════

CREATE TABLE IF NOT EXISTS `general_pdc_family_contract_option_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `option_id` INT NOT NULL,
    `tipo_contrato` INT DEFAULT NULL COMMENT 'NULL = hereda option; 1=Mano de Obra y Suministro por separado, 2=Suministro e Instalación',
    `tipo_paquete` VARCHAR(100) DEFAULT NULL COMMENT 'NULL = hereda option; nombre completo de modalidad/paquete',
    `paquete_nombre` VARCHAR(300) NOT NULL COMMENT 'Nombre del paquete como aparecerá en el PDC',
    `dias_proceso_id` INT DEFAULT NULL COMMENT 'FK a general_dias_procesos_contratacion (para heredar días reales)',
    `orden` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_pdc_option_item` (`option_id`, `tipo_paquete`, `paquete_nombre`),
    FOREIGN KEY (`option_id`) REFERENCES `general_pdc_family_contract_options`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`dias_proceso_id`) REFERENCES `general_dias_procesos_contratacion`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE `general_pdc_activity_rules`
    MODIFY `modalidad_sugerida` VARCHAR(100) DEFAULT NULL COMMENT 'Nombre completo de modalidad sugerida; NULL si ambiguo';

ALTER TABLE `general_pdc_family_contract_options`
    MODIFY `tipo_paquete` VARCHAR(100) NOT NULL COMMENT 'Nombre completo de modalidad/paquete';

ALTER TABLE `general_pdc_family_contract_option_items`
    MODIFY `tipo_paquete` VARCHAR(100) DEFAULT NULL COMMENT 'NULL = hereda option; nombre completo de modalidad/paquete';

UPDATE `general_pdc_activity_rules`
SET `modalidad_sugerida` = CASE `modalidad_sugerida`
    WHEN 'SI' THEN 'Suministro e Instalación'
    WHEN 'S' THEN 'Suministro'
    WHEN 'MO' THEN 'Mano de Obra'
    WHEN 'MO+S' THEN 'Mano de Obra y Suministro por separado'
    ELSE `modalidad_sugerida`
END;

UPDATE `general_pdc_family_contract_options`
SET `tipo_paquete` = CASE `tipo_paquete`
    WHEN 'SI' THEN 'Suministro e Instalación'
    WHEN 'S' THEN 'Suministro'
    WHEN 'MO' THEN 'Mano de Obra'
    WHEN 'MO+S' THEN 'Mano de Obra y Suministro por separado'
    ELSE `tipo_paquete`
END;

UPDATE `general_pdc_family_contract_option_items`
SET `tipo_paquete` = CASE `tipo_paquete`
    WHEN 'SI' THEN 'Suministro e Instalación'
    WHEN 'S' THEN 'Suministro'
    WHEN 'MO' THEN 'Mano de Obra'
    WHEN 'MO+S' THEN 'Mano de Obra y Suministro por separado'
    ELSE `tipo_paquete`
END;

UPDATE `general_pdc_family_contract_option_items`
SET `paquete_nombre` = 'MANO DE OBRA COLOCACION DE ACERO'
WHERE `paquete_nombre` = 'MO COLOCACION DE ACERO';

-- ═══════════════════════════════════════════════════════════════════════════
-- 6. ESTRATEGIA DEL PROYECTO POR FAMILIA
-- Almacena la decisión del usuario sobre qué modalidad usar para cada familia
-- en un proyecto y semana específicos.
-- ═══════════════════════════════════════════════════════════════════════════

CREATE TABLE IF NOT EXISTS `general_pdc_project_family_strategy` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `db_prefix` VARCHAR(50) NOT NULL COMMENT 'Prefijo del proyecto (ej: da_porto)',
    `semana` INT NOT NULL,
    `familia_id` INT NOT NULL,
    `option_id` INT NOT NULL COMMENT 'Opción de contrato elegida',
    `aplicada` TINYINT(1) DEFAULT 0 COMMENT '1 = ya se generaron los paquetes en el PDC',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_strategy` (`db_prefix`, `semana`, `familia_id`),
    FOREIGN KEY (`familia_id`) REFERENCES `general_pdc_familias`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`option_id`) REFERENCES `general_pdc_family_contract_options`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ═══════════════════════════════════════════════════════════════════════════
-- 7. VISTA DE INVENTARIO (consulta rápida)
-- ═══════════════════════════════════════════════════════════════════════════

CREATE OR REPLACE VIEW `v_pdc_inventory` AS
SELECT
    f.id AS familia_id,
    f.codigo AS familia_codigo,
    f.nombre AS familia_nombre,
    f.categoria,
    COALESCE(fcoi.tipo_contrato, fco.tipo_contrato) AS tipo_contrato,
    COALESCE(fcoi.tipo_paquete, fco.tipo_paquete) AS tipo_paquete,
    fco.dias_elaboracion,
    fco.dias_entrega,
    fco.dias_recibo,
    fco.dias_cuadros,
    fco.dias_legalizacion,
    fco.dias_fabricacion,
    fco.dias_insumos,
    fcoi.paquete_nombre,
    fcoi.orden,
    dpc.id AS dias_proceso_id,
    dpc.paqueteContratacion AS dias_proceso_nombre,
    dpc.diasElaboracionPliegos AS real_elaboracion,
    dpc.diasEntregaPliegos AS real_entrega,
    dpc.diasReciboPropuestas AS real_recibo,
    dpc.diasCuadrosComparativos AS real_cuadros,
    dpc.diasLegalizacionContrato AS real_legalizacion,
    dpc.diasFabricacion AS real_fabricacion,
    dpc.diasInsumosObra AS real_insumos
FROM general_pdc_familias f
JOIN general_pdc_family_contract_options fco ON fco.familia_id = f.id
JOIN general_pdc_family_contract_option_items fcoi ON fcoi.option_id = fco.id
LEFT JOIN general_dias_procesos_contratacion dpc ON dpc.id = fcoi.dias_proceso_id
WHERE fco.activa = 1;

-- ═══════════════════════════════════════════════════════════════════════════
-- SEED DATA — 42 Familias Constructivas
-- ═══════════════════════════════════════════════════════════════════════════

INSERT INTO `general_pdc_familias` (`codigo`, `nombre`, `categoria`, `orden`) VALUES
-- ESTRUCTURA
('PRELIMINARES', 'Preliminares de Obra', 'PRELIMINARES', 1),
('EXCAVACIONES', 'Excavaciones y Llenos', 'CIMENTACIONES', 2),
('PILOTEAJE', 'Piloteaje', 'CIMENTACIONES', 3),
('CONCRETO', 'Concreto (Estructura, Mampostería, Morteros)', 'ESTRUCTURA', 4),
('ACERO', 'Acero de Refuerzo y Estructural', 'ESTRUCTURA', 5),
('MAMPOSTERIA', 'Mampostería en Ladrillo', 'ESTRUCTURA', 6),
('REVOQUES', 'Revoques y Enfoscados', 'ACABADOS', 7),
('PUERTAS', 'Puertas y Accesorios', 'ACABADOS', 8),
('VENTANERIA', 'Ventanería en PVC y Aluminio', 'ACABADOS', 9),
('CARPINTERIA_MADERA', 'Carpintería en Madera', 'ACABADOS', 10),
('CARPINTERIA_METALICA', 'Carpintería Metálica', 'ACABADOS', 11),
('ENCHAPES', 'Pisos y Enchapes Cerámicos', 'ACABADOS', 12),
('PISOS_LAMINADOS', 'Pisos Laminados', 'ACABADOS', 13),
('PISOS_MADERA', 'Pisos y Enchapes en Madera', 'ACABADOS', 14),
('PINTURAS', 'Pinturas', 'ACABADOS', 15),
('CIELOS_RASOS', 'Cielos Rasos', 'ACABADOS', 16),
('IMPERMEABILIZACIONES', 'Impermeabilizaciones', 'ACABADOS', 17),
('MESONES', 'Mesones y Aparatos Sanitarios', 'ACABADOS', 18),
('ASCENSORES', 'Ascensores', 'ACABADOS', 19),
('FACHADA', 'Fachada (HPL, Vidrio, Aluminio)', 'ACABADOS', 20),
('LUMINARIAS', 'Luminarias y Artefactos Eléctricos', 'ACABADOS', 21),
('MAMPOSTERIA_FACHADA', 'Mampostería de Fachada', 'ACABADOS', 22),
('FILTROS', 'Filtros y Tapas', 'ACABADOS', 23),
-- INSTALACIONES
('RED_CONTRAINCENDIO', 'Red Contra Incendio', 'INSTALACIONES', 30),
('RED_ELECTRICA', 'Red Eléctrica', 'INSTALACIONES', 31),
('RED_HIDROSANITARIA', 'Redes Hidrosanitarias (Agua, Alcantarillado)', 'INSTALACIONES', 32),
('RED_GAS', 'Red de Gas', 'INSTALACIONES', 33),
('RED_RCI', 'Red RCI (Riesgo Cruzado Interior)', 'INSTALACIONES', 34),
('AIRE_ACONDICIONADO', 'Aire Acondicionado Central', 'INSTALACIONES', 35),
('VIDRIERIA', 'Vidriería', 'INSTALACIONES', 36),
-- URBANISMO
('PAISAJISMO', 'Paisajismo', 'URBANISMO', 40),
('NOMENCLATURA', 'Nomenclatura y Señalización', 'URBANISMO', 41),
('ENGRAMADOS', 'Engramados', 'URBANISMO', 42),
('MOBILIARIO', 'Mobiliario Urbano', 'URBANISMO', 43),
-- PROVISIONALES
('CAMPAMENTO', 'Campamento de Obra', 'PROVISIONALES', 50),
('VIGILANCIA', 'Vigilancia', 'PROVISIONALES', 51),
('PROVISIONALES_ELECTRICOS', 'Provisionales Eléctricos', 'PROVISIONALES', 52),
('PROVISIONALES_HS', 'Provisionales Hidrosanitarios', 'PROVISIONALES', 53),
('BAÑOS_PORTATILES', 'Baños Portátiles', 'PROVISIONALES', 54),
('PMT', 'Implementación PMT', 'PROVISIONALES', 55),
-- MANO DE OBRA
('ESTRUCTURA', 'Mano de Obra - Estructura', 'MANO DE OBRA', 60),
('ACABADOS', 'Mano de Obra - Acabados', 'MANO DE OBRA', 62),
('INSTALACIONES', 'Mano de Obra - Instalaciones', 'MANO DE OBRA', 63)
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), categoria = VALUES(categoria), orden = VALUES(orden);

-- ═══════════════════════════════════════════════════════════════════════════
-- SEED DATA — Reglas de mapeo (60-80 patrones regex)
-- Prioridad: específicas primero (100), genéricas después (10)
-- ═══════════════════════════════════════════════════════════════════════════

-- ESTRUCTURA
INSERT IGNORE INTO `general_pdc_activity_rules` (`familia_id`, `patron_regex`, `modalidad_sugerida`, `confianza`, `prioridad`, `descripcion`) VALUES
((SELECT id FROM general_pdc_familias WHERE codigo = 'CONCRETO'), '/CONCRETO.*ESTRUCTURA|ESTRUCTURA.*CONCRETO|VIGAS.*CONCRETO|COLUMNAS.*CONCRETO|LOSAS.*CONCRETO|PILAS.*CONCRETO/i', 'Mano de Obra y Suministro por separado', 90, 100, 'Concreto para estructura - Mano de Obra y Suministro por separado'),
((SELECT id FROM general_pdc_familias WHERE codigo = 'CONCRETO'), '/CONCRETO.*MAMPOSTERIA|MAMPOSTERIA.*CONCRETO|BLOQUE.*CONCRETO/i', 'Mano de Obra y Suministro por separado', 85, 100, 'Bloque de concreto para mampostería'),
((SELECT id FROM general_pdc_familias WHERE codigo = 'CONCRETO'), '/CONCRETO.*MORTERO|MORTERO.*CONCRETO|MORTEROS.*PISO/i', 'Mano de Obra y Suministro por separado', 85, 100, 'Morteros de piso'),
((SELECT id FROM general_pdc_familias WHERE codigo = 'CONCRETO'), '/CONCRETO.*PREPARADO|CONCRETO.*PREMEZCLADO/i', 'Suministro e Instalación', 90, 100, 'Concreto premezclado'),
((SELECT id FROM general_pdc_familias WHERE codigo = 'CONCRETO'), '/CONCRETO|DE CONCRETO/i', 'Mano de Obra y Suministro por separado', 80, 50, 'Concreto genérico'),
((SELECT id FROM general_pdc_familias WHERE codigo = 'ACERO'), '/ACERO.*REFUERZO|REFUERZO.*ACERO|ACERO.*ESTRUCTURAL/i', 'Mano de Obra y Suministro por separado', 95, 100, 'Acero de refuerzo - Mano de Obra y Suministro por separado por defecto'),
((SELECT id FROM general_pdc_familias WHERE codigo = 'ACERO'), '/ACERO|DE ACERO/i', 'Mano de Obra y Suministro por separado', 85, 50, 'Acero genérico'),
((SELECT id FROM general_pdc_familias WHERE codigo = 'MAMPOSTERIA'), '/MAMPOSTERIA.*LADRILLO|LADRILLO.*MAMPOSTERIA|MAMPOSTERIA/i', 'Mano de Obra y Suministro por separado', 90, 100, 'Mampostería en ladrillo'),
((SELECT id FROM general_pdc_familias WHERE codigo = 'PUERTAS'), '/PUERTA|PUERTAS|HOJA.*PUERTA|CERRAMIENTO.*PUERTA/i', 'Suministro e Instalación', 85, 100, 'Puertas y accesorios'),
((SELECT id FROM general_pdc_familias WHERE codigo = 'VENTANERIA'), '/VENTANA|VENTANERIA|VENTANAS|VENTANA.*PVC|VENTANA.*ALUMINIO/i', 'Suministro e Instalación', 90, 100, 'Ventanería'),
((SELECT id FROM general_pdc_familias WHERE codigo = 'ENCHAPES'), '/ENCHAPE|ENCHAPES|PISOS.*ENCHAPES|ENCHAPES.*CERAMICOS|AZULEJO/i', 'Mano de Obra y Suministro por separado', 90, 100, 'Enchapes cerámicos'),
((SELECT id FROM general_pdc_familias WHERE codigo = 'PISOS_LAMINADOS'), '/PISOS.*LAMINADOS|PISO.*LAMINADO|LAMINADO/i', 'Suministro e Instalación', 85, 100, 'Pisos laminados'),
((SELECT id FROM general_pdc_familias WHERE codigo = 'PISOS_MADERA'), '/PISOS.*MADERA|PISO.*MADERA|ENCHAPES.*MADERA|MADERA.*PISO/i', 'Mano de Obra y Suministro por separado', 85, 100, 'Pisos y enchapes en madera'),
((SELECT id FROM general_pdc_familias WHERE codigo = 'CARPINTERIA_MADERA'), '/CARPINTERIA.*MADERA|MADERA.*CARPINTERIA|COCINA.*MADERA|CLOSET.*MADERA/i', 'Mano de Obra y Suministro por separado', 90, 100, 'Carpintería en madera'),
((SELECT id FROM general_pdc_familias WHERE codigo = 'CARPINTERIA_METALICA'), '/CARPINTERIA.*METALICA|METALICA|HIERRO.*CARPINTERIA/i', 'Suministro', 85, 100, 'Carpintería metálica'),
((SELECT id FROM general_pdc_familias WHERE codigo = 'REVOQUES'), '/REVOQUE|REVOQUES|ENFOSCADO|REVOQUE.*MAYOR|MORTERO.*REVOQUE/i', 'Mano de Obra y Suministro por separado', 90, 100, 'Revoques y enfoscados'),
((SELECT id FROM general_pdc_familias WHERE codigo = 'PINTURAS'), '/PINTURA|PINTURAS|PINTURA.*INTERIOR|PINTURA.*EXTERIOR/i', 'Mano de Obra y Suministro por separado', 90, 100, 'Pinturas'),
((SELECT id FROM general_pdc_familias WHERE codigo = 'CIELOS_RASOS'), '/CIELO.*RASO|CIELOS.*RASOS|CIERRE.*TECHO|SANWICH.*PANEL/i', 'Mano de Obra y Suministro por separado', 90, 100, 'Cielos rasos'),
((SELECT id FROM general_pdc_familias WHERE codigo = 'IMPERMEABILIZACIONES'), '/IMPERMEABILIZACION|IMPERMEABILIZACIONES|IMPERMEAB/i', 'Mano de Obra y Suministro por separado', 90, 100, 'Impermeabilizaciones'),
((SELECT id FROM general_pdc_familias WHERE codigo = 'MESONES'), '/MESON|MESONES|APARATO.*SANITARIO|BAÑO.*EQUIPO/i', 'Suministro e Instalación', 85, 100, 'Mesones y aparatos sanitarios'),
((SELECT id FROM general_pdc_familias WHERE codigo = 'ASCENSORES'), '/ASCENSOR|ASCENSORES|ELEVADOR/i', 'Suministro e Instalación', 95, 100, 'Ascensores - Suministro e Instalación'),
((SELECT id FROM general_pdc_familias WHERE codigo = 'FACHADA'), '/FACHADA.*HPL|FACHADA.*VIDRIO|FACHADA.*ALUMINIO|PANEL.*FACHADA|FACHADA.*VENTILADA/i', 'Suministro e Instalación', 85, 100, 'Fachada'),
((SELECT id FROM general_pdc_familias WHERE codigo = 'LUMINARIAS'), '/LUMINARIA|LUMINARIAS|ARTEFACTO.*ELECTRICO|ILUMINACION/i', 'Suministro e Instalación', 90, 100, 'Luminarias y artefactos'),
((SELECT id FROM general_pdc_familias WHERE codigo = 'VIDRIERIA'), '/VIDRIO|VIDRIERIA|CRISTAL/i', 'Suministro e Instalación', 85, 100, 'Vidriería');

-- INSTALACIONES
INSERT IGNORE INTO `general_pdc_activity_rules` (`familia_id`, `patron_regex`, `modalidad_sugerida`, `confianza`, `prioridad`, `descripcion`) VALUES
((SELECT id FROM general_pdc_familias WHERE codigo = 'RED_CONTRAINCENDIO'), '/CONTRA.*INCENDIO|INCENDIO|RI|RCI.*INCENDIO|SPRINKLER|BOMBA.*INCENDIO/i', 'Suministro e Instalación', 95, 100, 'Red contra incendio'),
((SELECT id FROM general_pdc_familias WHERE codigo = 'RED_ELECTRICA'), '/RED.*ELECTRICA|ELECTRICIDAD|INSTALACION.*ELECTRICA|TABLEROS.*ELECTRICOS|TENDIDO.*ELECTRICO/i', 'Suministro e Instalación', 90, 100, 'Red eléctrica'),
((SELECT id FROM general_pdc_familias WHERE codigo = 'RED_HIDROSANITARIA'), '/HIDROSANITARIA|AGUA.*POTABLE|ALCANTARILLADO|RED.*AGUA|DESAGUE|SANITARIOS.*RED/i', 'Suministro e Instalación', 90, 100, 'Redes hidrosanitarias'),
((SELECT id FROM general_pdc_familias WHERE codigo = 'RED_GAS'), '/RED.*GAS|INSTALACION.*GAS|GAS.*NATURAL|GAS.*LP/i', 'Suministro e Instalación', 90, 100, 'Red de gas'),
((SELECT id FROM general_pdc_familias WHERE codigo = 'RED_RCI'), '/RCI|RIESGO.*CRUZADO|RED.*RIESGO/i', 'Suministro e Instalación', 85, 100, 'Red RCI'),
((SELECT id FROM general_pdc_familias WHERE codigo = 'AIRE_ACONDICIONADO'), '/AIRE.*ACONDICIONADO|A\/C|CLIMATIZACION|CHILLER|AHU|FAN.*COIL/i', 'Suministro e Instalación', 90, 100, 'Aire acondicionado central');

-- URBANISMO
INSERT IGNORE INTO `general_pdc_activity_rules` (`familia_id`, `patron_regex`, `modalidad_sugerida`, `confianza`, `prioridad`, `descripcion`) VALUES
((SELECT id FROM general_pdc_familias WHERE codigo = 'PAISAJISMO'), '/PAISAJISMO|JARDIN|PLANTACION|VERDE|ARBOL/i', 'Mano de Obra y Suministro por separado', 85, 100, 'Paisajismo'),
((SELECT id FROM general_pdc_familias WHERE codigo = 'NOMENCLATURA'), '/NOMENCLATURA|SEÑALIZACION|ROTULACION|IDENTIFICACION/i', 'Suministro e Instalación', 85, 100, 'Nomenclatura y señalización'),
((SELECT id FROM general_pdc_familias WHERE codigo = 'ENGRAMADOS'), '/ENGRAMADO|ENGRAMADOS|ACESO.*VEHICULAR/i', 'Suministro e Instalación', 85, 100, 'Engramados'),
((SELECT id FROM general_pdc_familias WHERE codigo = 'MOBILIARIO'), '/MOBILIARIO|BANCA|BANCAS|PELANCANAS/i', 'Suministro e Instalación', 80, 100, 'Mobiliario urbano');

-- PRELIMINARES
INSERT IGNORE INTO `general_pdc_activity_rules` (`familia_id`, `patron_regex`, `modalidad_sugerida`, `confianza`, `prioridad`, `descripcion`) VALUES
((SELECT id FROM general_pdc_familias WHERE codigo = 'PRELIMINARES'), '/PRELIMINAR|CERRAMIENTO.*PROVISIONAL|PROVISIONAL/i', 'Suministro', 80, 100, 'Preliminares de obra'),
((SELECT id FROM general_pdc_familias WHERE codigo = 'EXCAVACIONES'), '/EXCAVACION|EXCAVACIONES|LLENO|LLENOS|REPLANTEO/i', 'Suministro', 85, 100, 'Excavaciones y llenos'),
((SELECT id FROM general_pdc_familias WHERE codigo = 'PILOTEAJE'), '/PILOTE|PILOTEAJE|FRAGMENTACION/i', 'Suministro', 85, 100, 'Piloteaje'),
((SELECT id FROM general_pdc_familias WHERE codigo = 'FILTROS'), '/FILTRO|FILTROS|TAPA.*FILTRO|REJILLA/i', 'Suministro e Instalación', 80, 100, 'Filtros y tapas');

-- PROVISIONALES
INSERT IGNORE INTO `general_pdc_activity_rules` (`familia_id`, `patron_regex`, `modalidad_sugerida`, `confianza`, `prioridad`, `descripcion`) VALUES
((SELECT id FROM general_pdc_familias WHERE codigo = 'CAMPAMENTO'), '/CAMPAMENTO|OFFICE.*CONTAINER|MODULAR.*OFICINA/i', 'Suministro', 85, 100, 'Campamento de obra'),
((SELECT id FROM general_pdc_familias WHERE codigo = 'VIGILANCIA'), '/VIGILANCIA|SEGURIDAD.*OBRA|GUARDA.*DIA/i', 'Suministro', 85, 100, 'Vigilancia'),
((SELECT id FROM general_pdc_familias WHERE codigo = 'PROVISIONALES_ELECTRICOS'), '/PROVISIONALES.*ELECTRICAS|PROVISIONALES.*ELECTRICOS|AIRE.*PROVISIONAL/i', 'Suministro', 85, 100, 'Provisionales eléctricos'),
((SELECT id FROM general_pdc_familias WHERE codigo = 'PROVISIONALES_HS'), '/PROVISIONALES.*HIDROSANITARIAS|PROVISIONALES.*HS|AGUA.*PROVISIONAL/i', 'Suministro', 85, 100, 'Provisionales hidrosanitarios'),
((SELECT id FROM general_pdc_familias WHERE codigo = 'BAÑOS_PORTATILES'), '/BAÑO.*PORTATIL|BAÑOS.*PORTATILES|SANITARIO.*PROVISIONAL/i', 'Suministro', 90, 100, 'Baños portátiles'),
((SELECT id FROM general_pdc_familias WHERE codigo = 'PMT'), '/PMT|IMPLEMENTACION.*PMT|PLAN.*MOVILIDAD/i', 'Suministro', 80, 100, 'Implementación PMT');

-- MANO DE OBRA
INSERT IGNORE INTO `general_pdc_activity_rules` (`familia_id`, `patron_regex`, `modalidad_sugerida`, `confianza`, `prioridad`, `descripcion`) VALUES
((SELECT id FROM general_pdc_familias WHERE codigo = 'ESTRUCTURA'), '/MO.*ESTRUCTURA|MANO.*OBRA.*ESTRUCTURA|OPERARIO.*ESTRUCTURA/i', 'Mano de Obra', 85, 100, 'Mano de Obra - Estructura'),
((SELECT id FROM general_pdc_familias WHERE codigo = 'ACABADOS'), '/MO.*ACABADOS|MO.*REVOQUE|MO.*ENCHAPES|MO.*PINTURA|MANO.*OBRA.*ACABADOS/i', 'Mano de Obra', 85, 100, 'Mano de Obra - Acabados'),
((SELECT id FROM general_pdc_familias WHERE codigo = 'INSTALACIONES'), '/MO.*INSTALACION|MANO.*OBRA.*INSTALACION|OPERARIO.*INSTALACION/i', 'Mano de Obra', 85, 100, 'Mano de Obra - Instalaciones');

-- ═══════════════════════════════════════════════════════════════════════════
-- SEED DATA — Opciones de contrato por familia
-- Base mínima operativa con paquetes históricos reales.
-- ═══════════════════════════════════════════════════════════════════════════

INSERT INTO `general_pdc_family_contract_options` (
    `familia_id`, `tipo_contrato`, `tipo_paquete`,
    `dias_elaboracion`, `dias_entrega`, `dias_recibo`, `dias_cuadros`,
    `dias_legalizacion`, `dias_fabricacion`, `dias_insumos`, `notas`
)
SELECT f.id, seed.tipo_contrato, seed.tipo_paquete,
       seed.dias_elaboracion, seed.dias_entrega, seed.dias_recibo, seed.dias_cuadros,
       seed.dias_legalizacion, seed.dias_fabricacion, seed.dias_insumos, seed.notas
FROM (
    SELECT 'ACERO' codigo, 1 tipo_contrato, 'Mano de Obra y Suministro por separado' tipo_paquete, 7 dias_elaboracion, 5 dias_entrega, 1 dias_recibo, 25 dias_cuadros, 20 dias_legalizacion, 25 dias_fabricacion, 15 dias_insumos, 'Suministro de acero + mano de obra de colocación' notas
    UNION ALL SELECT 'CONCRETO', 1, 'Mano de Obra y Suministro por separado', 7, 5, 1, 25, 20, 8, 15, 'Concreto + mano de obra de estructura'
    UNION ALL SELECT 'MAMPOSTERIA', 1, 'Mano de Obra y Suministro por separado', 7, 20, 1, 30, 20, 30, 15, 'Ladrillo/bloque + mano de obra de mampostería'
    UNION ALL SELECT 'ENCHAPES', 1, 'Mano de Obra y Suministro por separado', 7, 20, 1, 30, 20, 60, 45, 'Suministro de enchapes + instalación'
    UNION ALL SELECT 'REVOQUES', 1, 'Mano de Obra y Suministro por separado', 7, 7, 1, 7, 10, 7, 7, 'Revoques con insumos y mano de obra'
    UNION ALL SELECT 'PINTURAS', 1, 'Mano de Obra y Suministro por separado', 10, 20, 1, 15, 15, 15, 7, 'Pinturas con suministro y aplicación'
    UNION ALL SELECT 'CIELOS_RASOS', 2, 'Suministro e Instalación', 10, 20, 1, 40, 30, 60, 30, 'Cielos rasos suministro e instalación'
    UNION ALL SELECT 'IMPERMEABILIZACIONES', 2, 'Suministro e Instalación', 10, 15, 1, 15, 15, 15, 7, 'Impermeabilizaciones suministro e instalación'
    UNION ALL SELECT 'PUERTAS', 2, 'Suministro e Instalación', 10, 10, 1, 20, 20, 45, 15, 'Puertas suministro e instalación'
    UNION ALL SELECT 'VENTANERIA', 2, 'Suministro e Instalación', 15, 30, 1, 45, 30, 60, 15, 'Ventanería suministro e instalación'
    UNION ALL SELECT 'CARPINTERIA_MADERA', 2, 'Suministro e Instalación', 10, 30, 1, 30, 30, 90, 30, 'Carpintería de madera suministro e instalación'
    UNION ALL SELECT 'CARPINTERIA_METALICA', 2, 'Suministro e Instalación', 10, 15, 1, 30, 30, 60, 15, 'Carpintería metálica suministro e instalación'
    UNION ALL SELECT 'ASCENSORES', 2, 'Suministro e Instalación', 15, 15, 1, 45, 40, 300, 20, 'Ascensores suministro e instalación'
    UNION ALL SELECT 'RED_CONTRAINCENDIO', 2, 'Suministro e Instalación', 15, 20, 1, 45, 30, 30, 15, 'Red de instalaciones: preguntar modalidad al usuario'
    UNION ALL SELECT 'RED_ELECTRICA', 2, 'Suministro e Instalación', 10, 15, 1, 45, 30, 30, 15, 'Red de instalaciones: preguntar modalidad al usuario'
    UNION ALL SELECT 'RED_HIDROSANITARIA', 2, 'Suministro e Instalación', 10, 15, 1, 45, 30, 30, 15, 'Red de instalaciones: preguntar modalidad al usuario'
    UNION ALL SELECT 'RED_GAS', 2, 'Suministro e Instalación', 10, 15, 1, 25, 26, 22, 15, 'Red de instalaciones: preguntar modalidad al usuario'
    UNION ALL SELECT 'CAMPAMENTO', 1, 'Suministro', 5, 5, 1, 10, 10, 10, 7, 'Campamento/almacén'
    UNION ALL SELECT 'VIGILANCIA', 1, 'Suministro', 5, 5, 1, 20, 10, 7, 7, 'Servicio de vigilancia'
    UNION ALL SELECT 'FILTROS', 2, 'Suministro e Instalación', 1, 3, 1, 5, 10, 0, 1, 'Filtros y tapas'
) seed
JOIN `general_pdc_familias` f ON f.codigo = seed.codigo
ON DUPLICATE KEY UPDATE
    dias_elaboracion = VALUES(dias_elaboracion),
    dias_entrega = VALUES(dias_entrega),
    dias_recibo = VALUES(dias_recibo),
    dias_cuadros = VALUES(dias_cuadros),
    dias_legalizacion = VALUES(dias_legalizacion),
    dias_fabricacion = VALUES(dias_fabricacion),
    dias_insumos = VALUES(dias_insumos),
    notas = VALUES(notas),
    activa = 1;

INSERT INTO `general_pdc_family_contract_option_items` (
    `option_id`, `tipo_contrato`, `tipo_paquete`, `paquete_nombre`, `dias_proceso_id`, `orden`
)
SELECT opt.id, seed.item_tipo_contrato, seed.item_tipo_paquete, seed.paquete_nombre,
       (
           SELECT dpc.id
           FROM `general_dias_procesos_contratacion` dpc
           WHERE dpc.paqueteContratacion LIKE seed.dias_like
           ORDER BY
               CASE WHEN dpc.diasElaboracionPliegos = 1
                         AND dpc.diasEntregaPliegos = 1
                         AND dpc.diasReciboPropuestas = 1
                         AND dpc.diasCuadrosComparativos = 1
                         AND dpc.diasLegalizacionContrato = 1
                         AND dpc.diasFabricacion = 1
                         AND dpc.diasInsumosObra = 1 THEN 1 ELSE 0 END,
               dpc.id
           LIMIT 1
       ) AS dias_proceso_id,
       seed.orden
FROM (
    SELECT 'ACERO' codigo, 1 option_tipo_contrato, 'Mano de Obra y Suministro por separado' option_tipo_paquete, 1 item_tipo_contrato, 'Suministro' item_tipo_paquete, 'ACERO DE REFUERZO' paquete_nombre, 'ACERO DE REFUERZO' dias_like, 1 orden
    UNION ALL SELECT 'ACERO', 1, 'Mano de Obra y Suministro por separado', 1, 'Mano de Obra', 'MANO DE OBRA COLOCACION DE ACERO', 'MO COLOCACION DE ACERO', 2
    UNION ALL SELECT 'CONCRETO', 1, 'Mano de Obra y Suministro por separado', 1, 'Suministro', 'CONCRETO', 'CONCRETO', 1
    UNION ALL SELECT 'CONCRETO', 1, 'Mano de Obra y Suministro por separado', 1, 'Mano de Obra', 'ESTRUCTURA EN CONCRETO', 'ESTRUCTURA EN CONCRETO', 2
    UNION ALL SELECT 'MAMPOSTERIA', 1, 'Mano de Obra y Suministro por separado', 1, 'Suministro', 'LADRILLO', 'LADRILLO', 1
    UNION ALL SELECT 'MAMPOSTERIA', 1, 'Mano de Obra y Suministro por separado', 1, 'Mano de Obra', 'MAMPOSTERIA', 'MAMPOSTER%', 2
    UNION ALL SELECT 'ENCHAPES', 1, 'Mano de Obra y Suministro por separado', 1, 'Suministro', 'PISOS Y ENCHAPES CERAMICOS', 'PISOS Y ENCHAPES CER%', 1
    UNION ALL SELECT 'ENCHAPES', 1, 'Mano de Obra y Suministro por separado', 1, 'Mano de Obra', 'ENCHAPES CERAMICOS', 'ENCHAPES CER%', 2
    UNION ALL SELECT 'REVOQUES', 1, 'Mano de Obra y Suministro por separado', 1, 'Mano de Obra', 'REVOQUE INTERIOR', 'REVOQUE INTERIOR', 1
    UNION ALL SELECT 'PINTURAS', 1, 'Mano de Obra y Suministro por separado', 1, 'Suministro', 'PINTURA INTERIOR', 'PINTURA INTERIOR', 1
    UNION ALL SELECT 'PINTURAS', 1, 'Mano de Obra y Suministro por separado', 1, 'Mano de Obra', 'ESTUCO Y PINTURA', 'ESTUCO Y PINTURA', 2
    UNION ALL SELECT 'CIELOS_RASOS', 2, 'Suministro e Instalación', 2, 'Suministro e Instalación', 'CIELOS RASOS', 'CIELOS RASOS', 1
    UNION ALL SELECT 'IMPERMEABILIZACIONES', 2, 'Suministro e Instalación', 2, 'Suministro e Instalación', 'IMPERMEABILIZACIONES', 'IMPERMEABILIZACIONES', 1
    UNION ALL SELECT 'PUERTAS', 2, 'Suministro e Instalación', 2, 'Suministro e Instalación', 'PUERTAS EN MADERA', 'PUERTAS EN MADERA', 1
    UNION ALL SELECT 'VENTANERIA', 2, 'Suministro e Instalación', 2, 'Suministro e Instalación', 'VENTANERIA', 'VENTANER%', 1
    UNION ALL SELECT 'CARPINTERIA_MADERA', 2, 'Suministro e Instalación', 2, 'Suministro e Instalación', 'CARPINTERIA DE MADERA', 'CARPINTER%A DE MADERA', 1
    UNION ALL SELECT 'CARPINTERIA_METALICA', 2, 'Suministro e Instalación', 2, 'Suministro e Instalación', 'CARPINTERIA METALICA', 'CARPINTER%A MET%LICA', 1
    UNION ALL SELECT 'ASCENSORES', 2, 'Suministro e Instalación', 2, 'Suministro e Instalación', 'ASCENSORES', 'ASCENSORES', 1
    UNION ALL SELECT 'RED_CONTRAINCENDIO', 2, 'Suministro e Instalación', 2, 'Suministro e Instalación', 'RED CONTRA INCENDIO, DETECCION Y EXTINCION', 'RED CONTRA INCENDIO%', 1
    UNION ALL SELECT 'RED_ELECTRICA', 2, 'Suministro e Instalación', 2, 'Suministro e Instalación', 'REDES ELECTRICAS, VOZ Y DATOS', 'REDES ELECTRICAS, VOZ Y DATOS', 1
    UNION ALL SELECT 'RED_HIDROSANITARIA', 2, 'Suministro e Instalación', 2, 'Suministro e Instalación', 'REDES HIDROSANITARIAS INTERNAS', 'REDES HIDROSANITARIAS INTERNAS', 1
    UNION ALL SELECT 'RED_GAS', 2, 'Suministro e Instalación', 2, 'Suministro e Instalación', 'RED DE GAS', 'RED DE GAS', 1
    UNION ALL SELECT 'CAMPAMENTO', 1, 'Suministro', 1, 'Suministro', 'CAMPAMENTO - ALMACEN', 'CAMPAMENTO - ALMACEN', 1
    UNION ALL SELECT 'VIGILANCIA', 1, 'Suministro', 1, 'Suministro', 'SERVICIO DE VIGILANCIA', 'SERVICIO DE VIGILANCIA', 1
    UNION ALL SELECT 'FILTROS', 2, 'Suministro e Instalación', 2, 'Suministro e Instalación', 'FILTROS', 'FILTROS', 1
) seed
JOIN `general_pdc_familias` f ON f.codigo = seed.codigo
JOIN `general_pdc_family_contract_options` opt
  ON opt.familia_id = f.id
 AND opt.tipo_contrato = seed.option_tipo_contrato
 AND opt.tipo_paquete = seed.option_tipo_paquete
ON DUPLICATE KEY UPDATE
    tipo_contrato = VALUES(tipo_contrato),
    dias_proceso_id = VALUES(dias_proceso_id),
    orden = VALUES(orden);

-- Alias principales para heredar duración real cuando el histórico trae nombres duplicados.
INSERT INTO `general_pdc_paquete_aliases` (`alias`, `canonical_id`, `notas`) VALUES
('CONCRETO PARA ESTRUCTURA', (SELECT id FROM general_dias_procesos_contratacion WHERE paqueteContratacion = 'CONCRETO' ORDER BY id LIMIT 1), 'Alias histórico de CONCRETO'),
('ACERO', (SELECT id FROM general_dias_procesos_contratacion WHERE paqueteContratacion = 'ACERO DE REFUERZO' ORDER BY id LIMIT 1), 'Alias histórico de ACERO DE REFUERZO'),
('ENCHAPES', (SELECT id FROM general_dias_procesos_contratacion WHERE paqueteContratacion LIKE 'PISOS Y ENCHAPES CER%' ORDER BY id LIMIT 1), 'Alias histórico de PISOS Y ENCHAPES CERAMICOS'),
('VENTANERIA', (SELECT id FROM general_dias_procesos_contratacion WHERE paqueteContratacion LIKE 'VENTANER%' ORDER BY id LIMIT 1), 'Alias sin tilde')
ON DUPLICATE KEY UPDATE canonical_id = VALUES(canonical_id), notas = VALUES(notas);
