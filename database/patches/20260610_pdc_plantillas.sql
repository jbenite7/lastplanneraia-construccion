-- Patch: Infraestructura de Plantillas PDC
-- Fecha: 2026-06-10
-- Fase 1 del plan de automatización del Plan de Compras
-- Creación de tablas para plantillas predefinidas por tipo de obra

-- 1. Categorías de recurso
CREATE TABLE IF NOT EXISTS `general_pdc_categoria_recurso` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nombre` VARCHAR(100) NOT NULL,
    `orden` INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `general_pdc_categoria_recurso` (`nombre`, `orden`) VALUES
('A TODO COSTO', 1),
('Mano de Obra', 2),
('Equipos de Obra', 3),
('Insumos', 4),
('Contratos Logísticos', 5)
ON DUPLICATE KEY UPDATE `orden` = VALUES(`orden`);

-- 2. Plantillas maestras
CREATE TABLE IF NOT EXISTS `general_pdc_plantillas` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nombre` VARCHAR(200) NOT NULL,
    `descripcion` TEXT,
    `tipo_obra` VARCHAR(100) COMMENT 'residencial, comercial, vial, industrial, salud',
    `activa` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Items de cada plantilla
CREATE TABLE IF NOT EXISTS `general_pdc_plantilla_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `plantilla_id` INT NOT NULL,
    `capitulo` VARCHAR(200),
    `actividad` VARCHAR(300) NOT NULL,
    `tipo_contrato` INT NOT NULL COMMENT '1=Mano de Obra y Suministro por separado, 2=Suministro e Instalación',
    `tipo_paquete` VARCHAR(50) COMMENT 'Nombre completo de modalidad/paquete',
    `paquete_sugerido` VARCHAR(200),
    `dias_elaboracion` INT DEFAULT 8,
    `dias_entrega` INT DEFAULT 10,
    `dias_recibo` INT DEFAULT 1,
    `dias_cuadros` INT DEFAULT 10,
    `dias_legalizacion` INT DEFAULT 10,
    `dias_fabricacion` INT DEFAULT 0,
    `dias_insumos` INT DEFAULT 0,
    `orden` INT DEFAULT 0,
    FOREIGN KEY (`plantilla_id`) REFERENCES `general_pdc_plantillas`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ═══════════════════════════════════════════════════════════════════════════
-- SEED DATA — Plantilla 1: Residencial Multifamiliar
-- Basada en datos históricos de Da Porto Torre 3 y Crysta2
-- ═══════════════════════════════════════════════════════════════════════════

INSERT INTO `general_pdc_plantillas` (`id`, `nombre`, `descripcion`, `tipo_obra`, `activa`) VALUES
(1, 'Residencial Multifamiliar', 'Plantilla para proyectos residenciales tipo torre. Basada en Da Porto Torre 3 y Crysta2.', 'residencial', 1);

INSERT INTO `general_pdc_plantilla_items` (`plantilla_id`, `capitulo`, `actividad`, `tipo_contrato`, `tipo_paquete`, `paquete_sugerido`, `dias_elaboracion`, `dias_entrega`, `dias_recibo`, `dias_cuadros`, `dias_legalizacion`, `dias_fabricacion`, `dias_insumos`, `orden`) VALUES
-- PRELIMINARES (A TODO COSTO)
(1, 'PRELIMINARES', 'Cerramientos Provisionales', 1, 'Suministro', 'Cerramientos', 1, 1, 1, 1, 1, 7, 0, 1),
(1, 'PRELIMINARES', 'Provisionales Eléctricas', 1, 'Suministro', 'Provisionales Eléctricas', 1, 1, 1, 1, 1, 7, 0, 2),
(1, 'PRELIMINARES', 'Provisionales Hidrosanitarias', 1, 'Suministro', 'Provisionales Hidrosanitarias', 8, 8, 1, 5, 5, 0, 0, 3),
(1, 'PRELIMINARES', 'Campamento de Obra', 1, 'Suministro', 'Campamento', 8, 8, 1, 5, 5, 15, 0, 4),
(1, 'PRELIMINARES', 'Baños Portátiles', 1, 'Suministro', 'Baños Portátiles', 8, 8, 1, 5, 5, 1, 0, 5),
(1, 'PRELIMINARES', 'Implementación PMT', 1, 'Suministro', 'PMT', 8, 8, 1, 5, 5, 1, 0, 6),
(1, 'PRELIMINARES', 'Vigilancia', 1, 'Suministro', 'Vigilancia', 8, 8, 1, 5, 15, 1, 0, 7),
(1, 'PRELIMINARES', 'Plan de Calidad', 1, 'Suministro', 'Plan de Calidad', 8, 8, 1, 5, 15, 1, 0, 8),

-- CIMENTACIONES (A TODO COSTO)
(1, 'CIMENTACIONES', 'Excavaciones y Llenos', 1, 'Suministro', 'Excavaciones', 8, 7, 1, 5, 10, 0, 0, 9),
(1, 'CIMENTACIONES', 'Piloteaje', 1, 'Suministro', 'Piloteaje', 8, 7, 1, 5, 10, 0, 0, 10),

-- ESTRUCTURA (A TODO COSTO)
(1, 'ESTRUCTURA', 'Filtros', 1, 'Suministro', 'Filtros', 5, 5, 1, 5, 10, 0, 0, 11),
(1, 'ESTRUCTURA', 'Estructura en Concreto', 1, 'Suministro', 'Estructura Concreto', 8, 7, 1, 5, 10, 0, 0, 12),
(1, 'ESTRUCTURA', 'Impermeabilizaciones', 1, 'Suministro', 'Impermeabilizaciones', 8, 7, 1, 5, 10, 0, 0, 13),
(1, 'ESTRUCTURA', 'Redes Hidrosanitarias', 1, 'Suministro', 'Redes Hidrosanitarias', 8, 7, 1, 5, 10, 0, 0, 14),
(1, 'ESTRUCTURA', 'Muros de Contención', 1, 'Suministro', 'Muros Contención', 8, 7, 1, 5, 10, 0, 0, 15),

-- MAMPOSTERÍA (A TODO COSTO)
(1, 'MAMPOSTERÍA', 'Mampostería en Ladrillo', 1, 'Mano de Obra y Suministro por separado', 'Mampostería', 8, 7, 1, 5, 10, 0, 0, 16),

-- ACABADOS (A TODO COSTO)
(1, 'ACABADOS', 'Revoques', 1, 'Mano de Obra y Suministro por separado', 'Revoques', 8, 7, 1, 5, 10, 0, 0, 17),
(1, 'ACABADOS', 'Red Contraincendio', 2, 'Suministro e Instalación', 'Contraincendio', 8, 7, 1, 5, 10, 0, 0, 18),
(1, 'ACABADOS', 'Red de Gas', 2, 'Suministro e Instalación', 'Gas', 30, 7, 1, 5, 10, 0, 0, 19),
(1, 'ACABADOS', 'Red Eléctrica', 2, 'Suministro e Instalación', 'Eléctrica', 8, 7, 1, 5, 10, 0, 0, 20),
(1, 'ACABADOS', 'Pisos y Enchapes', 1, 'Mano de Obra y Suministro por separado', 'Pisos', 8, 7, 1, 5, 10, 0, 0, 21),
(1, 'ACABADOS', 'Cielos', 1, 'Mano de Obra y Suministro por separado', 'Cielos', 8, 7, 1, 5, 10, 0, 0, 22),
(1, 'ACABADOS', 'Pinturas', 1, 'Mano de Obra y Suministro por separado', 'Pinturas', 8, 7, 1, 5, 10, 0, 0, 23),
(1, 'ACABADOS', 'Mesones y Aparatos', 1, 'Mano de Obra y Suministro por separado', 'Mesones', 8, 7, 1, 5, 10, 0, 0, 24),
(1, 'ACABADOS', 'Carpintería Madera', 1, 'Mano de Obra y Suministro por separado', 'Carpintería Madera', 8, 7, 1, 5, 10, 0, 0, 25),
(1, 'ACABADOS', 'Carpintería Metálica', 1, 'Suministro', 'Carpintería Metálica', 8, 7, 1, 5, 10, 0, 0, 26),
(1, 'ACABADOS', 'Ascensores', 2, 'Suministro e Instalación', 'Ascensores', 8, 7, 1, 5, 10, 300, 0, 27),

-- URBANISMO (A TODO COSTO)
(1, 'URBANISMO', 'Paisajismo', 1, 'Mano de Obra y Suministro por separado', 'Paisajismo', 8, 7, 1, 5, 10, 0, 0, 28),
(1, 'URBANISMO', 'Nomenclatura', 1, 'Suministro', 'Nomenclatura', 8, 7, 1, 5, 10, 0, 0, 29),
(1, 'URBANISMO', 'Engramados', 1, 'Suministro', 'Engramados', 8, 7, 1, 5, 10, 0, 0, 30),
(1, 'URBANISMO', 'Vías y Pavimentos', 2, 'Suministro e Instalación', 'Pavimentos', 8, 7, 1, 5, 10, 0, 0, 31),

-- MANO DE OBRA
(1, 'Mano de Obra', 'Estructura Mano de Obra', 1, 'Mano de Obra', 'Mano de Obra Estructura', 10, 15, 1, 10, 20, 30, 0, 32),
(1, 'Mano de Obra', 'Mampostería Mano de Obra', 1, 'Mano de Obra', 'Mano de Obra Mampostería', 10, 15, 1, 10, 20, 30, 0, 33),
(1, 'Mano de Obra', 'Revoque Mano de Obra', 1, 'Mano de Obra', 'Mano de Obra Revoque', 10, 15, 1, 10, 20, 30, 0, 34),
(1, 'Mano de Obra', 'Morteros de Piso Mano de Obra', 1, 'Mano de Obra', 'Mano de Obra Morteros', 10, 15, 1, 10, 20, 30, 0, 35),
(1, 'Mano de Obra', 'Enchapes Mano de Obra', 1, 'Mano de Obra', 'Mano de Obra Enchapes', 10, 15, 1, 10, 20, 30, 0, 36),
(1, 'Mano de Obra', 'Urbanismo Mano de Obra', 1, 'Mano de Obra', 'Mano de Obra Urbanismo', 10, 15, 1, 10, 20, 30, 0, 37),

-- EQUIPOS
(1, 'Equipos', 'Bomba de Concreto', 2, 'Suministro e Instalación', 'Bomba Concreto', 10, 15, 1, 10, 20, 30, 0, 38),
(1, 'Equipos', 'Torregrúa', 2, 'Suministro e Instalación', 'Torregrúa', 10, 15, 1, 10, 20, 30, 0, 39),
(1, 'Equipos', 'Contenedores', 2, 'Suministro e Instalación', 'Contenedores', 10, 15, 1, 10, 20, 30, 0, 40),
(1, 'Equipos', 'Planta de Concreto', 2, 'Suministro e Instalación', 'Planta Concreto', 10, 15, 1, 10, 20, 30, 0, 41),

-- INSUMOS
(1, 'Insumos', 'Enchapes', 2, 'Suministro e Instalación', 'Insumo Enchapes', 10, 15, 1, 10, 20, 30, 0, 42),
(1, 'Insumos', 'Ladrillo de Fachada', 2, 'Suministro e Instalación', 'Insumo Ladrillo Fachada', 10, 15, 1, 10, 20, 30, 0, 43),
(1, 'Insumos', 'Ladrillo Interior', 2, 'Suministro e Instalación', 'Insumo Ladrillo Interior', 10, 15, 1, 10, 20, 30, 0, 44),
(1, 'Insumos', 'Bloque de Concreto', 2, 'Suministro e Instalación', 'Insumo Bloque', 10, 15, 1, 10, 20, 30, 0, 45),
(1, 'Insumos', 'Materiales Eléctricos', 2, 'Suministro e Instalación', 'Insumo Eléctricos', 10, 15, 1, 10, 20, 30, 0, 46),
(1, 'Insumos', 'Aparatos Sanitarios', 2, 'Suministro e Instalación', 'Insumo Sanitarios', 10, 15, 1, 10, 20, 30, 0, 47),
(1, 'Insumos', 'Concreto', 2, 'Suministro e Instalación', 'Insumo Concreto', 10, 15, 1, 10, 20, 30, 0, 48),
(1, 'Insumos', 'Acero', 2, 'Suministro e Instalación', 'Insumo Acero', 10, 15, 1, 10, 20, 30, 0, 49),
(1, 'Insumos', 'Aires Acondicionados', 2, 'Suministro e Instalación', 'Insumo Aires Acondicionados', 10, 15, 1, 10, 20, 30, 0, 50);

-- ═══════════════════════════════════════════════════════════════════════════
-- SEED DATA — Plantilla 2: Comercial/Corporativo
-- Basada en datos históricos de JMC T1
-- ═══════════════════════════════════════════════════════════════════════════

INSERT INTO `general_pdc_plantillas` (`id`, `nombre`, `descripcion`, `tipo_obra`, `activa`) VALUES
(2, 'Comercial / Corporativo', 'Plantilla para oficinas, centros comerciales y corporativos. Basada en JMC T1.', 'comercial', 1);

INSERT INTO `general_pdc_plantilla_items` (`plantilla_id`, `capitulo`, `actividad`, `tipo_contrato`, `tipo_paquete`, `paquete_sugerido`, `dias_elaboracion`, `dias_entrega`, `dias_recibo`, `dias_cuadros`, `dias_legalizacion`, `dias_fabricacion`, `dias_insumos`, `orden`) VALUES
-- PRELIMINARES
(2, 'PRELIMINARES', 'Cerramientos Provisionales', 1, 'Suministro', 'Cerramientos', 10, 10, 1, 10, 10, 7, 0, 1),
(2, 'PRELIMINARES', 'Campamento de Obra', 1, 'Suministro', 'Campamento', 10, 10, 1, 10, 10, 15, 0, 2),
(2, 'PRELIMINARES', 'Baños Portátiles', 1, 'Suministro', 'Baños Portátiles', 10, 10, 1, 10, 10, 1, 0, 3),
(2, 'PRELIMINARES', 'Implementación PMT', 1, 'Suministro', 'PMT', 10, 10, 1, 10, 10, 1, 0, 4),
(2, 'PRELIMINARES', 'Vigilancia', 1, 'Suministro', 'Vigilancia', 10, 10, 1, 10, 10, 1, 0, 5),

-- CIMENTACIONES
(2, 'CIMENTACIONES', 'Excavaciones y Llenos', 1, 'Suministro', 'Excavaciones', 10, 10, 1, 10, 10, 0, 0, 6),
(2, 'CIMENTACIONES', 'Piloteaje', 1, 'Suministro', 'Piloteaje', 10, 10, 1, 10, 10, 0, 0, 7),

-- ESTRUCTURA
(2, 'ESTRUCTURA', 'Estructura en Concreto', 1, 'Suministro', 'Estructura Concreto', 10, 10, 1, 10, 10, 0, 0, 8),
(2, 'ESTRUCTURA', 'Impermeabilizaciones', 1, 'Suministro', 'Impermeabilizaciones', 10, 10, 1, 10, 10, 0, 0, 9),
(2, 'ESTRUCTURA', 'Muros de Contención', 1, 'Suministro', 'Muros Contención', 10, 10, 1, 10, 10, 0, 0, 10),

-- MAMPOSTERÍA
(2, 'MAMPOSTERÍA', 'Mampostería en Ladrillo', 1, 'Mano de Obra y Suministro por separado', 'Mampostería', 10, 10, 1, 10, 10, 0, 0, 11),

-- ACABADOS
(2, 'ACABADOS', 'Revoques', 1, 'Mano de Obra y Suministro por separado', 'Revoques', 10, 10, 1, 10, 10, 0, 0, 12),
(2, 'ACABADOS', 'Red Contraincendio', 2, 'Suministro e Instalación', 'Contraincendio', 10, 10, 1, 10, 10, 0, 0, 13),
(2, 'ACABADOS', 'Red de Gas', 2, 'Suministro e Instalación', 'Gas', 10, 10, 1, 10, 10, 0, 0, 14),
(2, 'ACABADOS', 'Red Eléctrica', 2, 'Suministro e Instalación', 'Eléctrica', 10, 10, 1, 10, 10, 0, 0, 15),
(2, 'ACABADOS', 'Pisos y Enchapes', 1, 'Mano de Obra y Suministro por separado', 'Pisos', 10, 10, 1, 10, 10, 0, 0, 16),
(2, 'ACABADOS', 'Cielos', 1, 'Mano de Obra y Suministro por separado', 'Cielos', 10, 10, 1, 10, 10, 0, 0, 17),
(2, 'ACABADOS', 'Pinturas', 1, 'Mano de Obra y Suministro por separado', 'Pinturas', 10, 10, 1, 10, 10, 0, 0, 18),
(2, 'ACABADOS', 'Carpintería Madera', 1, 'Mano de Obra y Suministro por separado', 'Carpintería Madera', 10, 10, 1, 10, 10, 0, 0, 19),
(2, 'ACABADOS', 'Carpintería Metálica', 1, 'Suministro', 'Carpintería Metálica', 10, 10, 1, 10, 10, 0, 0, 20),
(2, 'ACABADOS', 'Ascensores', 2, 'Suministro e Instalación', 'Ascensores', 10, 10, 1, 10, 10, 300, 0, 21),
(2, 'ACABADOS', 'Aire Acondicionado Central', 2, 'Suministro e Instalación', 'Aire Acondicionado', 10, 10, 1, 10, 10, 20, 0, 22),
(2, 'ACABADOS', 'Sistema de Seguridad', 2, 'Suministro e Instalación', 'Seguridad', 10, 10, 1, 10, 10, 10, 0, 23),
(2, 'ACABADOS', 'Rotulación y Señalización', 2, 'Suministro e Instalación', 'Rotulación', 10, 10, 1, 10, 10, 15, 0, 24),

-- INSTALACIONES ESPECIALES
(2, 'INSTALACIONES', 'Red Contraincendio', 2, 'Suministro e Instalación', 'Contraincendio', 10, 10, 1, 10, 10, 0, 0, 25),
(2, 'INSTALACIONES', 'Red de Gas', 2, 'Suministro e Instalación', 'Gas', 10, 10, 1, 10, 10, 0, 0, 26),
(2, 'INSTALACIONES', 'Red Eléctrica', 2, 'Suministro e Instalación', 'Eléctrica', 10, 10, 1, 10, 10, 0, 0, 27),
(2, 'INSTALACIONES', 'Redes Hidrosanitarias', 2, 'Suministro e Instalación', 'Hidrosanitarias', 10, 10, 1, 10, 10, 0, 0, 28),

-- MANO DE OBRA
(2, 'Mano de Obra', 'Estructura Mano de Obra', 1, 'Mano de Obra', 'Mano de Obra Estructura', 10, 10, 1, 10, 10, 30, 0, 29),
(2, 'Mano de Obra', 'Mampostería Mano de Obra', 1, 'Mano de Obra', 'Mano de Obra Mampostería', 10, 10, 1, 10, 10, 30, 0, 30),
(2, 'Mano de Obra', 'Revoque Mano de Obra', 1, 'Mano de Obra', 'Mano de Obra Revoque', 10, 10, 1, 10, 10, 30, 0, 31),
(2, 'Mano de Obra', 'Pintura Mano de Obra', 1, 'Mano de Obra', 'Mano de Obra Pintura', 10, 10, 1, 10, 10, 30, 0, 32),

-- EQUIPOS
(2, 'Equipos', 'Bomba de Concreto', 2, 'Suministro e Instalación', 'Bomba Concreto', 10, 10, 1, 10, 10, 30, 0, 33),
(2, 'Equipos', 'Torregrúa', 2, 'Suministro e Instalación', 'Torregrúa', 10, 10, 1, 10, 10, 30, 0, 34),
(2, 'Equipos', 'Montacargas', 2, 'Suministro e Instalación', 'Montacargas', 10, 10, 1, 10, 10, 20, 0, 35),

-- INSUMOS
(2, 'Insumos', 'Concreto', 2, 'Suministro e Instalación', 'Insumo Concreto', 10, 10, 1, 10, 20, 30, 0, 36),
(2, 'Insumos', 'Acero', 2, 'Suministro e Instalación', 'Insumo Acero', 10, 10, 1, 10, 20, 30, 0, 37),
(2, 'Insumos', 'Materiales Eléctricos', 2, 'Suministro e Instalación', 'Insumo Eléctricos', 10, 10, 1, 10, 20, 30, 0, 38),
(2, 'Insumos', 'Aparatos Sanitarios', 2, 'Suministro e Instalación', 'Insumo Sanitarios', 10, 10, 1, 10, 20, 30, 0, 39),
(2, 'Insumos', 'Luminarias', 2, 'Suministro e Instalación', 'Insumo Luminarias', 10, 10, 1, 10, 20, 15, 0, 40);

-- ═══════════════════════════════════════════════════════════════════════════
-- SEED DATA — Plantilla 3: Vial / Infraestructura (genérica)
-- Duraciones basadas en medianas globales de proyectos históricos
-- ═══════════════════════════════════════════════════════════════════════════

INSERT INTO `general_pdc_plantillas` (`id`, `nombre`, `descripcion`, `tipo_obra`, `activa`) VALUES
(3, 'Vial / Infraestructura', 'Plantilla genérica para obras viales, puentes e infraestructura.', 'vial', 1);

INSERT INTO `general_pdc_plantilla_items` (`plantilla_id`, `capitulo`, `actividad`, `tipo_contrato`, `tipo_paquete`, `paquete_sugerido`, `dias_elaboracion`, `dias_entrega`, `dias_recibo`, `dias_cuadros`, `dias_legalizacion`, `dias_fabricacion`, `dias_insumos`, `orden`) VALUES
-- MOVIMIENTO DE TIERRA
(3, 'MOVIMIENTO DE TIERRA', 'Corte', 2, 'Suministro e Instalación', 'Corte', 8, 10, 1, 10, 10, 0, 0, 1),
(3, 'MOVIMIENTO DE TIERRA', 'Relleo', 2, 'Suministro e Instalación', 'Relleo', 8, 10, 1, 10, 10, 0, 0, 2),
(3, 'MOVIMIENTO DE TIERRA', 'Terraplén', 2, 'Suministro e Instalación', 'Terraplén', 8, 10, 1, 10, 10, 0, 0, 3),

-- OBRAS DE DRENAJE
(3, 'OBRAS DE DRENAJE', 'Alcantarillado', 2, 'Suministro e Instalación', 'Alcantarillado', 8, 10, 1, 10, 10, 0, 0, 4),
(3, 'OBRAS DE DRENAJE', 'Red de Agua Potable', 2, 'Suministro e Instalación', 'Agua Potable', 8, 10, 1, 10, 10, 0, 0, 5),
(3, 'OBRAS DE DRENAJE', 'Obras Complementarias', 2, 'Suministro e Instalación', 'Obras Complementarias', 8, 10, 1, 10, 10, 0, 0, 6),

-- PAVIMENTO
(3, 'PAVIMENTO', 'Subbase', 2, 'Suministro e Instalación', 'Subbase', 8, 10, 1, 10, 10, 0, 0, 7),
(3, 'PAVIMENTO', 'Base', 2, 'Suministro e Instalación', 'Base', 8, 10, 1, 10, 10, 0, 0, 8),
(3, 'PAVIMENTO', 'Carpeta Asfáltica', 2, 'Suministro e Instalación', 'Carpeta Asfáltica', 8, 10, 1, 10, 10, 0, 0, 9),

-- SEÑALIZACIÓN
(3, 'SEÑALIZACIÓN', 'Señalización Vertical', 2, 'Suministro e Instalación', 'Señalización Vertical', 8, 10, 1, 10, 10, 15, 0, 10),
(3, 'SEÑALIZACIÓN', 'Señalización Horizontal', 2, 'Suministro e Instalación', 'Señalización Horizontal', 8, 10, 1, 10, 10, 5, 0, 11),
(3, 'SEÑALIZACIÓN', 'Semáforos', 2, 'Suministro e Instalación', 'Semáforos', 8, 10, 1, 10, 10, 30, 0, 12),

-- OBRAS DE ARTE
(3, 'OBRAS DE ARTE', 'Puentes', 2, 'Suministro e Instalación', 'Puentes', 10, 15, 1, 12, 15, 60, 0, 13),
(3, 'OBRAS DE ARTE', 'Muros de Contención', 2, 'Suministro e Instalación', 'Muros Contención', 10, 15, 1, 12, 15, 0, 0, 14),
(3, 'OBRAS DE ARTE', 'Bordillos y Aceras', 2, 'Suministro e Instalación', 'Bordillos', 8, 10, 1, 10, 10, 0, 0, 15),

-- MANO DE OBRA
(3, 'Mano de Obra', 'Movimiento de Tierra Mano de Obra', 1, 'Mano de Obra', 'Mano de Obra Movimiento de Tierra', 8, 11, 1, 12, 17, 30, 0, 16),
(3, 'Mano de Obra', 'Pavimento Mano de Obra', 1, 'Mano de Obra', 'Mano de Obra Pavimento', 8, 11, 1, 12, 17, 30, 0, 17),

-- EQUIPOS
(3, 'Equipos', 'Excavadora', 2, 'Suministro e Instalación', 'Excavadora', 10, 11, 1, 13, 20, 27, 0, 18),
(3, 'Equipos', 'Motorgrúa', 2, 'Suministro e Instalación', 'Motorgrúa', 10, 11, 1, 13, 20, 27, 0, 19),
(3, 'Equipos', 'Volqueta', 2, 'Suministro e Instalación', 'Volqueta', 10, 11, 1, 13, 20, 27, 0, 20),

-- INSUMOS
(3, 'Insumos', 'Concreto', 2, 'Suministro e Instalación', 'Insumo Concreto', 8, 10, 1, 12, 15, 35, 0, 21),
(3, 'Insumos', 'Asfalto', 2, 'Suministro e Instalación', 'Insumo Asfalto', 8, 10, 1, 12, 15, 35, 0, 22),
(3, 'Insumos', 'Acero', 2, 'Suministro e Instalación', 'Insumo Acero', 8, 10, 1, 12, 15, 35, 0, 23);

-- Normalización visible: las APIs y vistas no deben mostrar abreviaturas de modalidad.
UPDATE `general_pdc_plantilla_items`
SET `tipo_paquete` = CASE `tipo_paquete`
    WHEN 'SI' THEN 'Suministro e Instalación'
    WHEN 'S' THEN 'Suministro'
    WHEN 'MO' THEN 'Mano de Obra'
    WHEN 'MO+S' THEN 'Mano de Obra y Suministro por separado'
    ELSE `tipo_paquete`
END;

UPDATE `general_pdc_plantilla_items`
SET `actividad` = REPLACE(`actividad`, ' MO', ' Mano de Obra')
WHERE `actividad` LIKE '% MO';

UPDATE `general_pdc_plantilla_items`
SET `paquete_sugerido` = REPLACE(`paquete_sugerido`, 'MO ', 'Mano de Obra ')
WHERE `paquete_sugerido` LIKE 'MO %';
