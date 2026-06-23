-- Patch: Catalogo maestro PDC por familias constructivas
-- Fecha: 2026-06-12
-- Fase 2 del plan de automatizacion del Plan de Compras
-- Objetivo: reemplazar el mapeo inicial por un catalogo limpio de 65+ familias,
-- reglas sobre texto normalizado y opciones de contrato completas.

SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================================
-- 1. Tablas base del motor PG -> PDC
-- ============================================================================

CREATE TABLE IF NOT EXISTS `general_pdc_familias` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `codigo` VARCHAR(50) NOT NULL UNIQUE COMMENT 'Codigo corto de familia constructiva',
    `nombre` VARCHAR(200) NOT NULL,
    `categoria` VARCHAR(100) COMMENT 'PRELIMINARES, CIMENTACION, ACABADOS, etc.',
    `orden` INT DEFAULT 0,
    `siempre_revision` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = nunca auto-seleccionar; requiere confirmacion manual',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `general_pdc_activity_rules` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `familia_id` INT NOT NULL,
    `patron_regex` VARCHAR(500) NOT NULL COMMENT 'Regex contra texto normalizado sin tildes y sin HTML',
    `modalidad_sugerida` VARCHAR(100) DEFAULT NULL COMMENT 'Modalidad sugerida visible en UI',
    `confianza` TINYINT DEFAULT 80 COMMENT '0-100: 80+ auto, 70-79 review, <70 manual',
    `prioridad` INT DEFAULT 100 COMMENT 'Mayor prioridad gana',
    `descripcion` VARCHAR(500),
    `activa` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_pdc_activity_rule` (`familia_id`, `patron_regex`),
    KEY `idx_pdc_activity_rules_family` (`familia_id`),
    CONSTRAINT `fk_pdc_activity_rules_family` FOREIGN KEY (`familia_id`) REFERENCES `general_pdc_familias`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `general_pdc_paquete_aliases` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `alias` VARCHAR(300) NOT NULL UNIQUE COMMENT 'Nombre historico del paquete',
    `canonical_id` INT NOT NULL COMMENT 'Paquete canonical en general_dias_procesos_contratacion',
    `notas` VARCHAR(500),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_pdc_alias_canonical` (`canonical_id`),
    CONSTRAINT `fk_pdc_alias_canonical` FOREIGN KEY (`canonical_id`) REFERENCES `general_dias_procesos_contratacion`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `general_pdc_family_contract_options` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `familia_id` INT NOT NULL,
    `tipo_contrato` INT NOT NULL COMMENT '1=MO/S separados, 2=Suministro e Instalacion',
    `tipo_paquete` VARCHAR(100) NOT NULL COMMENT 'Modalidad visible en UI',
    `dias_elaboracion` INT DEFAULT 8,
    `dias_entrega` INT DEFAULT 10,
    `dias_recibo` INT DEFAULT 1,
    `dias_cuadros` INT DEFAULT 10,
    `dias_legalizacion` INT DEFAULT 10,
    `dias_fabricacion` INT DEFAULT 0,
    `dias_insumos` INT DEFAULT 0,
    `notas` TEXT,
    `activa` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_pdc_family_contract_option` (`familia_id`, `tipo_contrato`, `tipo_paquete`),
    KEY `idx_pdc_family_contract_options_family` (`familia_id`),
    CONSTRAINT `fk_pdc_family_contract_options_family` FOREIGN KEY (`familia_id`) REFERENCES `general_pdc_familias`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `general_pdc_family_contract_option_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `option_id` INT NOT NULL,
    `tipo_contrato` INT DEFAULT NULL COMMENT 'NULL = hereda option; 1=MO/S separados, 2=SI',
    `tipo_paquete` VARCHAR(100) DEFAULT NULL COMMENT 'NULL = hereda option; S, MO o SI visible',
    `paquete_nombre` VARCHAR(300) NOT NULL COMMENT 'Nombre del paquete en PDC',
    `dias_proceso_id` INT DEFAULT NULL COMMENT 'FK opcional a general_dias_procesos_contratacion',
    `orden` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_pdc_option_item` (`option_id`, `tipo_paquete`, `paquete_nombre`),
    KEY `idx_pdc_option_item_option` (`option_id`),
    KEY `idx_pdc_option_item_dias` (`dias_proceso_id`),
    CONSTRAINT `fk_pdc_option_item_option` FOREIGN KEY (`option_id`) REFERENCES `general_pdc_family_contract_options`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_pdc_option_item_dias` FOREIGN KEY (`dias_proceso_id`) REFERENCES `general_dias_procesos_contratacion`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `general_pdc_project_family_strategy` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `db_prefix` VARCHAR(50) NOT NULL COMMENT 'Prefijo del proyecto',
    `semana` INT NOT NULL,
    `familia_id` INT NOT NULL,
    `option_id` INT NOT NULL COMMENT 'Opcion de contrato elegida',
    `aplicada` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_strategy` (`db_prefix`, `semana`, `familia_id`),
    KEY `idx_pdc_strategy_family` (`familia_id`),
    KEY `idx_pdc_strategy_option` (`option_id`),
    CONSTRAINT `fk_pdc_strategy_family` FOREIGN KEY (`familia_id`) REFERENCES `general_pdc_familias`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_pdc_strategy_option` FOREIGN KEY (`option_id`) REFERENCES `general_pdc_family_contract_options`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `general_dias_defaults_categoria` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `categoria` VARCHAR(100) NOT NULL UNIQUE,
    `dias_elaboracion` INT NOT NULL DEFAULT 8,
    `dias_entrega` INT NOT NULL DEFAULT 10,
    `dias_recibo` INT NOT NULL DEFAULT 1,
    `dias_cuadros` INT NOT NULL DEFAULT 10,
    `dias_legalizacion` INT NOT NULL DEFAULT 10,
    `dias_fabricacion` INT NOT NULL DEFAULT 0,
    `dias_insumos` INT NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET @pdc_has_siempre_revision := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'general_pdc_familias'
      AND column_name = 'siempre_revision'
);
SET @pdc_add_siempre_revision_sql := IF(
    @pdc_has_siempre_revision = 0,
    'ALTER TABLE `general_pdc_familias` ADD COLUMN `siempre_revision` TINYINT(1) NOT NULL DEFAULT 0 COMMENT ''1 = nunca auto-seleccionar; requiere confirmacion manual'' AFTER `orden`',
    'SELECT 1'
);
PREPARE pdc_add_siempre_revision_stmt FROM @pdc_add_siempre_revision_sql;
EXECUTE pdc_add_siempre_revision_stmt;
DEALLOCATE PREPARE pdc_add_siempre_revision_stmt;

-- ============================================================================
-- 2. Re-seed limpio
-- ============================================================================

DELETE FROM `general_pdc_project_family_strategy`;
DELETE FROM `general_pdc_family_contract_option_items`;
DELETE FROM `general_pdc_family_contract_options`;
DELETE FROM `general_pdc_activity_rules`;
DELETE FROM `general_pdc_paquete_aliases`;
DELETE FROM `general_pdc_familias`;

ALTER TABLE `general_pdc_familias` AUTO_INCREMENT = 1;
ALTER TABLE `general_pdc_activity_rules` AUTO_INCREMENT = 1;
ALTER TABLE `general_pdc_family_contract_options` AUTO_INCREMENT = 1;
ALTER TABLE `general_pdc_family_contract_option_items` AUTO_INCREMENT = 1;
ALTER TABLE `general_pdc_project_family_strategy` AUTO_INCREMENT = 1;

-- ============================================================================
-- 3. Catalogo maestro de familias
-- ============================================================================

INSERT INTO `general_pdc_familias` (`codigo`, `nombre`, `categoria`, `orden`, `siempre_revision`) VALUES
('PRELIMINARES', 'Preliminares de Obra', 'PRELIMINARES', 1, 0),
('CAMPAMENTO', 'Campamento de Obra', 'PRELIMINARES', 2, 1),
('VIGILANCIA', 'Vigilancia', 'PRELIMINARES', 3, 0),
('PROVISIONALES_ELECTRICOS', 'Provisionales Electricos', 'PRELIMINARES', 4, 0),
('PROVISIONALES_HS', 'Provisionales Hidrosanitarios', 'PRELIMINARES', 5, 0),
('BANOS_PORTATILES', 'Banos Portatiles', 'PRELIMINARES', 6, 0),
('PMT', 'Implementacion PMT', 'PRELIMINARES', 7, 0),
('PLAN_CALIDAD', 'Plan de Calidad', 'PRELIMINARES', 8, 0),

('EXCAVACIONES', 'Excavaciones y Movimiento de Tierra', 'CIMENTACION', 10, 0),
('EXCAVACION_MANUAL', 'Excavaciones Manuales', 'CIMENTACION', 11, 0),
('PILOTEAJE', 'Piloteaje y Micropilotes', 'CIMENTACION', 12, 0),
('PILAS_MECANICAS', 'Pilas Mecanicas', 'CIMENTACION', 13, 0),
('PILAS_EXCAVADAS', 'Pilas Excavadas a Mano', 'CIMENTACION', 14, 0),
('CIMENTACION_ZAPATAS', 'Zapatas de Cimentacion', 'CIMENTACION', 15, 0),
('CIMENTACION_LOSAS', 'Losas de Cimentacion', 'CIMENTACION', 16, 0),
('CIMENTACION_VIGAS', 'Vigas de Cimentacion', 'CIMENTACION', 17, 0),
('CONTENCIONES', 'Muros de Contencion', 'CIMENTACION', 18, 0),

('ESTRUCTURA_CONCRETO', 'Estructura en Concreto', 'ESTRUCTURA', 20, 0),
('ESTRUCTURA_ACERO', 'Acero de Refuerzo y Estructural', 'ESTRUCTURA', 21, 0),
('ENCOFRADO', 'Encofrado y Obra Falsa', 'ESTRUCTURA', 22, 0),
('ALIGERANTES', 'Aligerantes Perdidos y Recuperables', 'ESTRUCTURA', 23, 0),

('MAMPOSTERIA', 'Mamposteria en Ladrillo/Bloque Interior', 'MAMPOSTERIA', 30, 0),
('MAMPOSTERIA_FACHADA', 'Mamposteria de Fachada', 'MAMPOSTERIA', 31, 0),

('MORTEROS', 'Morteros de Nivelacion de Losas', 'ACABADOS', 40, 0),
('REVOQUES', 'Revoques y Panetes', 'ACABADOS', 41, 0),
('ESTUCO', 'Estuco', 'ACABADOS', 42, 0),
('PINTURAS', 'Pinturas Interiores y Exteriores', 'ACABADOS', 43, 0),
('PISOS', 'Pisos Ceramicos, Porcelanatos, Gres y Concreto Pulido', 'ACABADOS', 44, 0),
('PISOS_LAMINADOS', 'Pisos Laminados', 'ACABADOS', 45, 0),
('PISOS_MADERA', 'Pisos y Enchapes en Madera', 'ACABADOS', 46, 0),
('ENCHAPES', 'Enchapes Ceramicos en Muros', 'ACABADOS', 47, 0),
('CIELOS_RASOS', 'Cielos Rasos', 'ACABADOS', 48, 0),
('IMPERMEABILIZACIONES', 'Impermeabilizaciones', 'ACABADOS', 49, 0),
('MESONES_COCINA', 'Mesones de Cocina', 'ACABADOS', 50, 0),
('MESONES_BANO', 'Mesones de Bano', 'ACABADOS', 51, 0),
('SANITARIOS', 'Aparatos Sanitarios', 'ACABADOS', 52, 0),
('PUERTAS', 'Puertas y Accesorios', 'ACABADOS', 53, 0),
('VENTANERIA', 'Ventaneria PVC y Aluminio', 'ACABADOS', 54, 0),
('CARPINTERIA_MADERA', 'Carpinteria en Madera', 'ACABADOS', 55, 0),
('CARPINTERIA_METALICA', 'Carpinteria Metalica', 'ACABADOS', 56, 0),
('FACHADA', 'Fachada HPL, Vidrio y Aluminio', 'ACABADOS', 57, 0),
('LUMINARIAS', 'Luminarias y Artefactos Electricos', 'ACABADOS', 58, 0),
('VIDRIERIA', 'Vidrieria', 'ACABADOS', 59, 0),
('FILTROS', 'Filtros, Tapas y Rejillas', 'ACABADOS', 60, 0),
('ASCENSORES', 'Ascensores', 'ACABADOS', 61, 0),

('RED_ELECTRICA', 'Red Electrica', 'INSTALACIONES', 70, 0),
('RED_TELECOMUNICACIONES', 'Red de Telecomunicaciones', 'INSTALACIONES', 71, 0),
('RED_HIDROSANITARIA', 'Red Hidrosanitaria', 'INSTALACIONES', 72, 0),
('RED_GAS', 'Red de Gas', 'INSTALACIONES', 73, 0),
('RED_CONTRAINCENDIO', 'Red Contra Incendio - Piping', 'INSTALACIONES', 74, 0),
('DETECCION_INCENDIO', 'Deteccion de Incendio', 'INSTALACIONES', 75, 0),
('EQUIPOS_INCENDIO', 'Equipos de Extincion', 'INSTALACIONES', 76, 0),
('BOMBA_RCI', 'Bomba Red Contra Incendio', 'INSTALACIONES', 77, 0),
('RCI', 'Red RCI', 'INSTALACIONES', 78, 0),
('AIRE_ACONDICIONADO', 'Aire Acondicionado Central', 'INSTALACIONES', 79, 0),

('PAISAJISMO', 'Paisajismo', 'URBANISMO', 80, 0),
('NOMENCLATURA', 'Nomenclatura y Senalizacion', 'URBANISMO', 81, 0),
('ENGRAMADOS', 'Engramados', 'URBANISMO', 82, 0),
('MOBILIARIO', 'Mobiliario Urbano', 'URBANISMO', 83, 0),
('VIAS_PAVIMENTOS', 'Vias y Pavimentos', 'URBANISMO', 84, 0),

('ESTRUCTURA', 'Mano de Obra - Estructura', 'MANO DE OBRA', 90, 0),
('ACABADOS', 'Mano de Obra - Acabados', 'MANO DE OBRA', 92, 0),
('INSTALACIONES', 'Mano de Obra - Instalaciones', 'MANO DE OBRA', 93, 0),
('CIMENTACION', 'Mano de Obra - Cimentacion', 'MANO DE OBRA', 94, 0),
('URBANISMO', 'Mano de Obra - Urbanismo', 'MANO DE OBRA', 96, 0),

('BOMBA_CONCRETO', 'Bomba de Concreto', 'EQUIPOS', 100, 0),
('TORREGRUA', 'Torregrua', 'EQUIPOS', 101, 0),
('PLANTA_CONCRETO', 'Planta de Concreto', 'EQUIPOS', 102, 0),
('CONTENEDORES', 'Contenedores', 'EQUIPOS', 103, 0),
('MONTACARGAS', 'Montacargas', 'EQUIPOS', 104, 0),
('MOTORGRUA', 'Motorgrua', 'EQUIPOS', 105, 0),
('EXCAVADORA', 'Excavadora', 'EQUIPOS', 106, 0),
('VOLQUETA', 'Volqueta', 'EQUIPOS', 107, 0);

-- ============================================================================
-- 4. Defaults por categoria (fallback para duraciones sugeridas)
-- ============================================================================

INSERT INTO `general_dias_defaults_categoria` (`categoria`, `dias_elaboracion`, `dias_entrega`, `dias_recibo`, `dias_cuadros`, `dias_legalizacion`, `dias_fabricacion`, `dias_insumos`) VALUES
('PRELIMINARES', 8, 7, 1, 5, 10, 0, 0),
('CIMENTACION', 8, 7, 1, 5, 10, 0, 0),
('ESTRUCTURA', 8, 7, 1, 5, 10, 0, 0),
('MAMPOSTERIA', 8, 7, 1, 5, 10, 0, 0),
('ACABADOS', 8, 7, 1, 5, 10, 0, 0),
('INSTALACIONES', 8, 7, 1, 5, 10, 0, 0),
('URBANISMO', 8, 7, 1, 5, 10, 0, 0),
('MANO DE OBRA', 8, 11, 1, 12, 17, 30, 0),
('EQUIPOS', 10, 11, 1, 13, 20, 27, 0),
('INSUMOS', 8, 10, 1, 12, 15, 35, 0)
ON DUPLICATE KEY UPDATE
    dias_elaboracion = VALUES(dias_elaboracion),
    dias_entrega = VALUES(dias_entrega),
    dias_recibo = VALUES(dias_recibo),
    dias_cuadros = VALUES(dias_cuadros),
    dias_legalizacion = VALUES(dias_legalizacion),
    dias_fabricacion = VALUES(dias_fabricacion),
    dias_insumos = VALUES(dias_insumos);

-- ============================================================================
-- 5. Reglas regex contra texto normalizado
-- ============================================================================

INSERT INTO `general_pdc_activity_rules` (`familia_id`, `patron_regex`, `modalidad_sugerida`, `confianza`, `prioridad`, `descripcion`)
SELECT f.id, seed.patron_regex, seed.modalidad_sugerida, seed.confianza, seed.prioridad, seed.descripcion
FROM (
    SELECT 'CAMPAMENTO' codigo, '/CAMPAMENTO|ALMACEN.*OBRA|OFICINA.*OBRA|CONTAINER.*OFICINA|MODULAR.*OFICINA/u' patron_regex, 'Suministro' modalidad_sugerida, 90 confianza, 110 prioridad, 'Campamento siempre requiere revision' descripcion
    UNION ALL SELECT 'VIGILANCIA', '/VIGILANCIA|SEGURIDAD.*OBRA|GUARDA|PORTERIA/u', 'Suministro', 90, 100, 'Vigilancia'
    UNION ALL SELECT 'PROVISIONALES_ELECTRICOS', '/PROVISIONAL.*ELECTRIC|ELECTRIC.*PROVISIONAL|TABLERO.*PROVISIONAL|ACOMETIDA.*PROVISIONAL/u', 'Suministro', 90, 100, 'Provisionales electricos'
    UNION ALL SELECT 'PROVISIONALES_HS', '/PROVISIONAL.*HIDROSANIT|HIDROSANIT.*PROVISIONAL|AGUA.*PROVISIONAL|SANITAR.*PROVISIONAL/u', 'Suministro', 90, 100, 'Provisionales hidrosanitarios'
    UNION ALL SELECT 'BANOS_PORTATILES', '/BANO.*PORTATIL|BANOS.*PORTATILES|SANITARIO.*PORTATIL|UNIDAD.*SANITARIA.*PORTATIL/u', 'Suministro', 95, 110, 'Banos portatiles'
    UNION ALL SELECT 'PMT', '/PMT|PLAN.*MANEJO.*TRANSITO|MANEJO.*TRANSITO|IMPLEMENTACION.*PMT/u', 'Suministro', 90, 100, 'PMT'
    UNION ALL SELECT 'PLAN_CALIDAD', '/PLAN.*CALIDAD|CALIDAD.*OBRA|DOSSIER.*CALIDAD/u', 'Suministro', 85, 100, 'Plan de calidad'
    UNION ALL SELECT 'PRELIMINARES', '/PRELIMINAR|CERRAMIENTO.*PROVISIONAL|CERRAMIENTOS|REPLANTEO|LOCALIZACION.*REPLANTEO/u', 'Suministro', 80, 70, 'Preliminares genericos'

    UNION ALL SELECT 'EXCAVACION_MANUAL', '/EXCAVACION.*MANUAL|MANUAL.*EXCAVACION|EXCAVADO.*MANO|EXCAVADA.*MANO/u', 'Suministro', 95, 120, 'Excavacion manual'
    UNION ALL SELECT 'EXCAVACIONES', '/EXCAVACION|EXCAVACIONES|MOVIMIENTO.*TIERRA|LLENO|LLENOS|RELLENO|TERRAPLEN|CORTE.*TIERRA|PERFILACION/u', 'Suministro', 88, 90, 'Excavaciones y movimiento de tierra'
    UNION ALL SELECT 'PILAS_MECANICAS', '/PILA.*MECANICA|PILAS.*MECANICAS|CAISSON.*MECANICO/u', 'Suministro', 95, 120, 'Pilas mecanicas'
    UNION ALL SELECT 'PILAS_EXCAVADAS', '/PILA.*EXCAVADA|PILAS.*EXCAVADAS|PILA.*MANO|CAISSON.*MANO/u', 'Suministro', 95, 120, 'Pilas excavadas'
    UNION ALL SELECT 'PILOTEAJE', '/PILOTE|PILOTES|PILOTEAJE|MICROPILOTE|MICROPILOTES/u', 'Suministro', 92, 110, 'Piloteaje'
    UNION ALL SELECT 'CIMENTACION_ZAPATAS', '/ZAPATA|ZAPATAS|DADO.*CIMENTACION/u', 'Mano de Obra y Suministro por separado', 90, 100, 'Zapatas'
    UNION ALL SELECT 'CIMENTACION_LOSAS', '/LOSA.*CIMENTACION|LOSA.*FONDO|PLACA.*CIMENTACION|RADIER/u', 'Mano de Obra y Suministro por separado', 90, 100, 'Losas de cimentacion'
    UNION ALL SELECT 'CIMENTACION_VIGAS', '/VIGA.*CIMENTACION|VIGAS.*CIMENTACION|VIGA.*AMARRE|VIGA.*FUNDACION/u', 'Mano de Obra y Suministro por separado', 88, 100, 'Vigas de cimentacion'
    UNION ALL SELECT 'CONTENCIONES', '/MURO.*CONTENCION|CONTENCION|PANTALLA.*CONTENCION|MURO.*ANCLADO/u', 'Suministro e Instalación', 88, 100, 'Contenciones'

    UNION ALL SELECT 'ESTRUCTURA_ACERO', '/ACERO.*REFUERZO|REFUERZO.*ACERO|ACERO.*ESTRUCTURAL|VARILLA|MALLA.*ELECTROSOLDADA|FIGURADO.*ACERO/u', 'Mano de Obra y Suministro por separado', 95, 120, 'Acero de refuerzo'
    UNION ALL SELECT 'ESTRUCTURA_CONCRETO', '/CONCRETO.*ESTRUCTURA|ESTRUCTURA.*CONCRETO|COLUMNA|COLUMNAS|VIGA.*CONCRETO|VIGAS.*CONCRETO|LOSA.*CONCRETO|PLACAS.*CONCRETO|MURO.*CONCRETO/u', 'Mano de Obra y Suministro por separado', 93, 110, 'Estructura en concreto'
    UNION ALL SELECT 'ESTRUCTURA_CONCRETO', '/CONCRETO.*PREMEZCLADO|CONCRETO.*BOMBEADO|CONCRETO.*MR|CONCRETO.*PSI/u', 'Mano de Obra y Suministro por separado', 88, 100, 'Concreto estructural'
    UNION ALL SELECT 'ENCOFRADO', '/ENCOFRADO|FORMALETA|OBRA.*FALSA|CIMBRA/u', 'Suministro', 90, 100, 'Encofrado'
    UNION ALL SELECT 'ALIGERANTES', '/ALIGERANTE|ALIGERANTES|CASETON|CASETON|BOVEDILLA|POLIESTIRENO/u', 'Suministro', 90, 100, 'Aligerantes'

    UNION ALL SELECT 'MAMPOSTERIA_FACHADA', '/MAMPOSTERIA.*FACHADA|LADRILLO.*FACHADA|FACHADA.*LADRILLO|BLOQUE.*FACHADA/u', 'Mano de Obra y Suministro por separado', 95, 120, 'Mamposteria fachada'
    UNION ALL SELECT 'MAMPOSTERIA', '/MAMPOSTERIA|LADRILLO.*INTERIOR|BLOQUE.*CONCRETO|BLOQUE.*ARCILLA|MURO.*LADRILLO|MUROS.*MAMPOSTERIA/u', 'Mano de Obra y Suministro por separado', 92, 100, 'Mamposteria interior'

    UNION ALL SELECT 'MORTEROS', '/MORTERO.*NIVELACION|MORTERO.*PISO|AFINADO.*PISO|NIVELACION.*LOSA|MORTEROS/u', 'Mano de Obra y Suministro por separado', 92, 110, 'Morteros'
    UNION ALL SELECT 'REVOQUES', '/REVOQUE|REVOQUES|PANETE|PANETES|ENFOSCADO|FRISO/u', 'Mano de Obra y Suministro por separado', 92, 100, 'Revoques y panetes'
    UNION ALL SELECT 'ESTUCO', '/ESTUCO|ESTUCADO|MASILLA.*MURO/u', 'Mano de Obra y Suministro por separado', 90, 100, 'Estuco'
    UNION ALL SELECT 'PINTURAS', '/PINTURA|PINTURAS|VINILO|ESMALTE|TEXTURA.*PINTURA|PINTURA.*FACHADA/u', 'Mano de Obra y Suministro por separado', 92, 100, 'Pinturas'
    UNION ALL SELECT 'PISOS_LAMINADOS', '/PISO.*LAMINADO|PISOS.*LAMINADOS|LAMINADO.*PISO/u', 'Suministro e Instalación', 95, 120, 'Pisos laminados'
    UNION ALL SELECT 'PISOS_MADERA', '/PISO.*MADERA|PISOS.*MADERA|DECK.*MADERA|MADERA.*PISO/u', 'Mano de Obra y Suministro por separado', 92, 115, 'Pisos madera'
    UNION ALL SELECT 'PISOS', '/PISOS.*CERAMIC|PISO.*CERAMIC|PORCELANATO|GRES|BALDOSA|PISO.*CONCRETO|CONCRETO.*PULIDO|PISO.*INDUSTRIAL/u', 'Mano de Obra y Suministro por separado', 92, 100, 'Pisos'
    UNION ALL SELECT 'ENCHAPES', '/ENCHAPE|ENCHAPES|AZULEJO|CERAMICA.*MURO|MURO.*CERAMICA|REVESTIMIENTO.*CERAMICO/u', 'Mano de Obra y Suministro por separado', 92, 100, 'Enchapes muros'
    UNION ALL SELECT 'CIELOS_RASOS', '/CIELO.*RASO|CIELOS.*RASOS|DRYWALL.*CIELO|TECHO.*DRYWALL|PANEL.*YESO/u', 'Suministro e Instalación', 92, 100, 'Cielos rasos'
    UNION ALL SELECT 'IMPERMEABILIZACIONES', '/IMPERMEABILIZACION|IMPERMEABILIZACIONES|MANTO.*ASFALTICO|MEMBRANA.*IMPER|POLIURETANO.*IMPER/u', 'Suministro e Instalación', 92, 100, 'Impermeabilizaciones'
    UNION ALL SELECT 'MESONES_COCINA', '/MESON.*COCINA|MESONES.*COCINA|MESON.*GRANITO|MESON.*QUARZTONE|COCINA.*MESON/u', 'Suministro e Instalación', 95, 120, 'Mesones cocina'
    UNION ALL SELECT 'MESONES_BANO', '/MESON.*BANO|MESONES.*BANO|LAVAMANOS.*MESON|BANO.*MESON/u', 'Suministro e Instalación', 95, 120, 'Mesones bano'
    UNION ALL SELECT 'SANITARIOS', '/APARATO.*SANITARIO|APARATOS.*SANITARIOS|SANITARIO|LAVAMANOS|INODORO|GRIFERIA|DUCHA/u', 'Suministro e Instalación', 92, 110, 'Aparatos sanitarios'
    UNION ALL SELECT 'PUERTAS', '/PUERTA|PUERTAS|HOJA.*PUERTA|MARCO.*PUERTA|CERRADURA|CHAPA.*PUERTA/u', 'Suministro e Instalación', 90, 100, 'Puertas'
    UNION ALL SELECT 'VENTANERIA', '/VENTANA|VENTANAS|VENTANERIA|PVC.*VENTANA|ALUMINIO.*VENTANA|VIDRIO.*VENTANA/u', 'Suministro e Instalación', 92, 100, 'Ventaneria'
    UNION ALL SELECT 'CARPINTERIA_MADERA', '/CARPINTERIA.*MADERA|MADERA.*CARPINTERIA|CLOSET|CLOSET|MUEBLE.*COCINA|COCINA.*MADERA/u', 'Suministro e Instalación', 92, 100, 'Carpinteria madera'
    UNION ALL SELECT 'CARPINTERIA_METALICA', '/CARPINTERIA.*METALICA|METALICA|BARANDA.*METAL|PASAMANOS.*METAL|REJA|REJAS/u', 'Suministro e Instalación', 90, 100, 'Carpinteria metalica'
    UNION ALL SELECT 'FACHADA', '/FACHADA.*HPL|FACHADA.*VIDRIO|FACHADA.*ALUMINIO|FACHADA.*VENTILADA|PANEL.*FACHADA|HPL/u', 'Suministro e Instalación', 90, 100, 'Fachada'
    UNION ALL SELECT 'LUMINARIAS', '/LUMINARIA|LUMINARIAS|ARTEFACTO.*ELECTRICO|ILUMINACION|BALA.*LED|PANEL.*LED/u', 'Suministro e Instalación', 90, 100, 'Luminarias'
    UNION ALL SELECT 'VIDRIERIA', '/VIDRIO|VIDRIERIA|CRISTAL|ESPEJO|ESPEJOS/u', 'Suministro e Instalación', 86, 90, 'Vidrieria'
    UNION ALL SELECT 'FILTROS', '/FILTRO|FILTROS|TAPA.*FILTRO|REJILLA|REJILLAS|SUMIDERO/u', 'Suministro e Instalación', 86, 90, 'Filtros y rejillas'
    UNION ALL SELECT 'ASCENSORES', '/ASCENSOR|ASCENSORES|ELEVADOR|ELEVADORES/u', 'Suministro e Instalación', 98, 120, 'Ascensores'

    UNION ALL SELECT 'RED_TELECOMUNICACIONES', '/TELECOMUNICACION|TELECOMUNICACIONES|VOZ.*DATOS|DATOS.*VOZ|CCTV|FIBRA.*OPTICA|CABLEADO.*ESTRUCTURADO|RED.*DATOS/u', 'Suministro e Instalación', 95, 125, 'Telecomunicaciones'
    UNION ALL SELECT 'RED_ELECTRICA', '/RED.*ELECTRICA|ELECTRICIDAD|INSTALACION.*ELECTRICA|TABLERO.*ELECTRICO|ACOMETIDA.*ELECTRICA|TENDIDO.*ELECTRICO|MEDIA.*TENSION|BAJA.*TENSION/u', 'Suministro e Instalación', 92, 100, 'Red electrica'
    UNION ALL SELECT 'RED_HIDROSANITARIA', '/HIDROSANITARIA|HIDRAULICA|SANITARIA|AGUA.*POTABLE|ALCANTARILLADO|DESAGUE|RED.*AGUA|RED.*SANITARIA/u', 'Suministro e Instalación', 92, 100, 'Red hidrosanitaria'
    UNION ALL SELECT 'RED_GAS', '/RED.*GAS|GAS.*NATURAL|INSTALACION.*GAS|GASODUCTO/u', 'Suministro e Instalación', 92, 100, 'Red gas'
    UNION ALL SELECT 'BOMBA_RCI', '/BOMBA.*RCI|BOMBA.*CONTRA.*INCENDIO|EQUIPO.*BOMBEO.*INCENDIO|CUARTO.*BOMBAS.*INCENDIO/u', 'Suministro e Instalación', 98, 130, 'Bomba RCI'
    UNION ALL SELECT 'DETECCION_INCENDIO', '/DETECCION.*INCENDIO|ALARMA.*INCENDIO|SENSOR.*HUMO|DETECTOR.*HUMO|PANEL.*INCENDIO/u', 'Suministro e Instalación', 96, 125, 'Deteccion incendio'
    UNION ALL SELECT 'EQUIPOS_INCENDIO', '/EXTINTOR|EXTINTORES|GABINETE.*INCENDIO|EQUIPO.*EXTINCION|MANGUERA.*INCENDIO/u', 'Suministro e Instalación', 95, 120, 'Equipos incendio'
    UNION ALL SELECT 'RED_CONTRAINCENDIO', '/RED.*CONTRA.*INCENDIO|CONTRA.*INCENDIO|SPRINKLER|ROCIADOR|TUBERIA.*INCENDIO|SISTEMA.*INCENDIO/u', 'Suministro e Instalación', 92, 100, 'Red contra incendio piping'
    UNION ALL SELECT 'RCI', '/RCI|RED.*RCI|RIESGO.*CRUZADO/u', 'Suministro e Instalación', 86, 80, 'RCI generico'
    UNION ALL SELECT 'AIRE_ACONDICIONADO', '/AIRE.*ACONDICIONADO|CLIMATIZACION|CHILLER|FAN.*COIL|MINISPLIT|VENTILACION.*MECANICA/u', 'Suministro e Instalación', 92, 100, 'Aire acondicionado'

    UNION ALL SELECT 'PAISAJISMO', '/PAISAJISMO|JARDIN|JARDINERIA|ARBOL|ARBORIZACION|PLANTACION|ZONA.*VERDE/u', 'Mano de Obra y Suministro por separado', 88, 100, 'Paisajismo'
    UNION ALL SELECT 'NOMENCLATURA', '/NOMENCLATURA|SENALIZACION|SENALIZACION|ROTULACION|IDENTIFICACION|AVISO/u', 'Suministro e Instalación', 88, 100, 'Nomenclatura y senalizacion'
    UNION ALL SELECT 'ENGRAMADOS', '/ENGRAMADO|ENGRAMADOS|GRAMA|CESPED|PRADO/u', 'Suministro e Instalación', 88, 100, 'Engramados'
    UNION ALL SELECT 'MOBILIARIO', '/MOBILIARIO|BANCA|BANCAS|CANECAS|PAPELERA|BOLARDOS|JUEGOS.*INFANTILES/u', 'Suministro e Instalación', 84, 90, 'Mobiliario urbano'
    UNION ALL SELECT 'VIAS_PAVIMENTOS', '/VIA|VIAS|PAVIMENTO|PAVIMENTOS|ASFALTO|CARPETA.*ASFALTICA|SUBBASE|BASE.*GRANULAR|BORDILLO|ANDEN/u', 'Suministro e Instalación', 88, 100, 'Vias y pavimentos'

    UNION ALL SELECT 'CIMENTACION', '/MO.*CIMENTACION|MANO.*OBRA.*CIMENTACION|CUADRILLA.*CIMENTACION/u', 'Mano de Obra', 90, 120, 'MO cimentacion'
    UNION ALL SELECT 'ESTRUCTURA', '/MO.*ESTRUCTURA|MANO.*OBRA.*ESTRUCTURA|CUADRILLA.*ESTRUCTURA/u', 'Mano de Obra', 90, 110, 'MO estructura'
    UNION ALL SELECT 'INSTALACIONES', '/MO.*INSTALACION|MANO.*OBRA.*INSTALACION|CUADRILLA.*INSTALACION/u', 'Mano de Obra', 88, 100, 'MO instalaciones'
    UNION ALL SELECT 'URBANISMO', '/MO.*URBANISMO|MANO.*OBRA.*URBANISMO|CUADRILLA.*URBANISMO|^URBANISMO$/u', 'Mano de Obra', 88, 100, 'MO urbanismo'
    UNION ALL SELECT 'ACABADOS', '/MO.*ACABADOS|MANO.*OBRA.*ACABADOS|MO.*REVOQUE|MO.*PINTURA|MO.*ENCHAPE|CUADRILLA.*ACABADOS/u', 'Mano de Obra', 88, 90, 'MO acabados'

    UNION ALL SELECT 'BOMBA_CONCRETO', '/BOMBA.*CONCRETO|BOMBEO.*CONCRETO|LANZADORA.*CONCRETO/u', 'Suministro e Instalación', 95, 120, 'Bomba concreto'
    UNION ALL SELECT 'TORREGRUA', '/TORREGRUA|TORRE.*GRUA|GRUA.*TORRE/u', 'Suministro e Instalación', 95, 120, 'Torregrua'
    UNION ALL SELECT 'PLANTA_CONCRETO', '/PLANTA.*CONCRETO|PLANTA.*MEZCLA|BATCHING.*PLANT/u', 'Suministro e Instalación', 92, 110, 'Planta concreto'
    UNION ALL SELECT 'CONTENEDORES', '/CONTENEDOR|CONTENEDORES|CONTAINER/u', 'Suministro e Instalación', 88, 100, 'Contenedores'
    UNION ALL SELECT 'MONTACARGAS', '/MONTACARGAS|MONTACARGA/u', 'Suministro e Instalación', 88, 100, 'Montacargas'
    UNION ALL SELECT 'MOTORGRUA', '/MOTORGRUA|MOTOR.*GRUA|GRUA.*MOVIL/u', 'Suministro e Instalación', 88, 100, 'Motorgrua'
    UNION ALL SELECT 'EXCAVADORA', '/EXCAVADORA|RETROEXCAVADORA|MINICARGADOR/u', 'Suministro e Instalación', 86, 90, 'Excavadora'
    UNION ALL SELECT 'VOLQUETA', '/VOLQUETA|VOLQUETAS|CAMION.*VOLQUETA/u', 'Suministro e Instalación', 86, 90, 'Volqueta'
) seed
INNER JOIN `general_pdc_familias` f ON f.codigo = seed.codigo;

-- Reglas complementarias: sinonimos, breadcrumbs y variantes historicas.
INSERT IGNORE INTO `general_pdc_activity_rules` (`familia_id`, `patron_regex`, `modalidad_sugerida`, `confianza`, `prioridad`, `descripcion`)
SELECT f.id, seed.patron_regex, seed.modalidad_sugerida, seed.confianza, seed.prioridad, seed.descripcion
FROM (
    SELECT 'PRELIMINARES' codigo, '/CERRAMIENTO|CERCA.*PROVISIONAL|VALLA.*PROVISIONAL|LOCALIZACION|REPLANTEO/u' patron_regex, 'Suministro' modalidad_sugerida, 84 confianza, 85 prioridad, 'Preliminares por sinonimos' descripcion
    UNION ALL SELECT 'CAMPAMENTO', '/ALMACEN|BODEGA.*OBRA|OFICINA.*CAMPO|OFICINA.*TECNICA/u', 'Suministro', 88, 95, 'Campamento por sinonimos'
    UNION ALL SELECT 'VIGILANCIA', '/CASETA.*VIGILANCIA|CONTROL.*ACCESO|PORTERIA.*OBRA/u', 'Suministro', 86, 90, 'Vigilancia por sinonimos'
    UNION ALL SELECT 'PROVISIONALES_ELECTRICOS', '/ENERGIA.*PROVISIONAL|TRANSFORMADOR.*PROVISIONAL|RED.*PROVISIONAL.*ELECTRICA/u', 'Suministro', 88, 95, 'Energia provisional'
    UNION ALL SELECT 'PROVISIONALES_HS', '/ACUEDUCTO.*PROVISIONAL|ALCANTARILLADO.*PROVISIONAL|PUNTO.*AGUA.*PROVISIONAL/u', 'Suministro', 88, 95, 'Agua provisional'
    UNION ALL SELECT 'PLAN_CALIDAD', '/ENSAYO.*CALIDAD|LABORATORIO.*CALIDAD|CONTROL.*CALIDAD/u', 'Suministro', 78, 70, 'Control de calidad'

    UNION ALL SELECT 'EXCAVACIONES', '/DESCAPOTE|RETIRO.*MATERIAL|CARGUE.*RETIRO|NIVELACION.*TERRENO|TERRACEO/u', 'Suministro', 86, 95, 'Movimiento de tierra sinonimos'
    UNION ALL SELECT 'EXCAVACION_MANUAL', '/APIQUE|APIQUES|ZANJA.*MANUAL|BRECHA.*MANUAL/u', 'Suministro', 90, 110, 'Excavacion manual sinonimos'
    UNION ALL SELECT 'PILOTEAJE', '/PILOTE.*PREEXCAVADO|PILOTE.*HINCADO|MICROPILOTAJE|CAISSON/u', 'Suministro', 92, 115, 'Piloteaje variantes'
    UNION ALL SELECT 'CONTENCIONES', '/PANTALLA|MURO.*PANTALLA|ANCLAJE|ANCLAJES|SOIL.*NAIL/u', 'Suministro e Instalación', 88, 105, 'Contenciones variantes'
    UNION ALL SELECT 'CIMENTACION_ZAPATAS', '/CIMENTACION.*ZAPATA|ZAPATA.*AISLADA|ZAPATA.*CORRIDA/u', 'Mano de Obra y Suministro por separado', 92, 115, 'Zapatas especificas'
    UNION ALL SELECT 'CIMENTACION_LOSAS', '/CIMENTACION.*LOSA|LOSA.*MACIZA.*CIMENTACION|LOSA.*FUNDACION/u', 'Mano de Obra y Suministro por separado', 92, 115, 'Losas cimentacion especificas'
    UNION ALL SELECT 'CIMENTACION_VIGAS', '/CIMENTACION.*VIGA|VIGA.*RIOSTRA|VIGA.*ENLACE/u', 'Mano de Obra y Suministro por separado', 90, 110, 'Vigas cimentacion especificas'

    UNION ALL SELECT 'ESTRUCTURA_CONCRETO', '/ESCALERA.*CONCRETO|RAMPA.*CONCRETO|NUCLEO.*CONCRETO|TANQUE.*CONCRETO|PLACA.*ENTREPISO|VACIADO.*CONCRETO/u', 'Mano de Obra y Suministro por separado', 92, 115, 'Estructura concreto elementos'
    UNION ALL SELECT 'ESTRUCTURA_CONCRETO', '/^ESTRUCTURA$|CAPITULO.*ESTRUCTURA|ESTRUCTURA.*APORTICADA|SISTEMA.*ESTRUCTURAL/u', 'Mano de Obra y Suministro por separado', 86, 85, 'Estructura por capitulo'
    UNION ALL SELECT 'ESTRUCTURA_ACERO', '/HIERRO.*REFUERZO|MALLA|MALLA.*ELECTRO|DOVELA|CANASTILLA.*ACERO/u', 'Mano de Obra y Suministro por separado', 92, 110, 'Acero sinonimos'
    UNION ALL SELECT 'ENCOFRADO', '/FORMALETERIA|FORMALETA.*METALICA|FORMALETA.*MADERA|ANDAMIO.*ESTRUCTURA/u', 'Suministro', 88, 95, 'Formaleta sinonimos'
    UNION ALL SELECT 'ALIGERANTES', '/BLOQUELON|BOVEDILLA|ICOPOR|POLIESTIRENO|CASETA.*ALIGERANTE/u', 'Suministro', 88, 95, 'Aligerantes sinonimos'

    UNION ALL SELECT 'MAMPOSTERIA', '/^MAMPOSTERIA$|CAPITULO.*MAMPOSTERIA|MURO.*DIVISORIO|DIVISION.*LADRILLO|MURO.*BLOQUE/u', 'Mano de Obra y Suministro por separado', 90, 105, 'Mamposteria por capitulo'
    UNION ALL SELECT 'MAMPOSTERIA_FACHADA', '/FACHADA.*MAMPOSTERIA|LADRILLO.*A LA VISTA|LADRILLO.*PRENSADO/u', 'Mano de Obra y Suministro por separado', 92, 115, 'Mamposteria fachada variantes'
    UNION ALL SELECT 'MORTEROS', '/MORTERO.*AFINADO|AFINADO|RECRECIDO|CONTRAPISO/u', 'Mano de Obra y Suministro por separado', 90, 105, 'Morteros sinonimos'
    UNION ALL SELECT 'REVOQUES', '/^REVOQUES?$|CAPITULO.*REVOQUE|REVESTIMIENTO.*MORTERO|PALETEADO/u', 'Mano de Obra y Suministro por separado', 88, 90, 'Revoque por capitulo'
    UNION ALL SELECT 'ESTUCO', '/ESTUCO.*PLASTICO|ESTUCO.*TRADICIONAL|ALISADO.*MURO/u', 'Mano de Obra y Suministro por separado', 90, 105, 'Estuco variantes'
    UNION ALL SELECT 'PINTURAS', '/PINTURA.*INTERIOR|PINTURA.*EXTERIOR|PINTURA.*VINILO|PINTURA.*ESMALTE/u', 'Mano de Obra y Suministro por separado', 92, 110, 'Pintura variantes'
    UNION ALL SELECT 'PISOS', '/^PISOS$|CAPITULO.*PISOS|PISO.*PORCELANATO|CERAMICA.*PISO|PISO.*GRES|PISO.*BALDOSA/u', 'Mano de Obra y Suministro por separado', 90, 105, 'Pisos por capitulo'
    UNION ALL SELECT 'ENCHAPES', '/^ENCHAPES?$|CAPITULO.*ENCHAPE|ENCHAPE.*BANO|ENCHAPE.*COCINA|REVESTIMIENTO.*MURO/u', 'Mano de Obra y Suministro por separado', 92, 110, 'Enchapes variantes'
    UNION ALL SELECT 'CIELOS_RASOS', '/CIELO.*SUSPENDIDO|GYPSUM|SUPERBOARD|DRYWALL/u', 'Suministro e Instalación', 90, 105, 'Cielos variantes'
    UNION ALL SELECT 'IMPERMEABILIZACIONES', '/IMPERMEABILIZACION.*CUBIERTA|IMPERMEABILIZACION.*TERRAZA|CUBIERTA.*MANTO|TERRAZA.*MANTO/u', 'Suministro e Instalación', 92, 110, 'Impermeabilizaciones por ubicacion'
    UNION ALL SELECT 'PUERTAS', '/PUERTA.*MADERA|PUERTA.*METALICA|PUERTA.*CORTAFUEGO|PUERTA.*ACCESO/u', 'Suministro e Instalación', 90, 105, 'Puertas variantes'
    UNION ALL SELECT 'VENTANERIA', '/VENTANAL|DIVISION.*VIDRIO|MARCO.*ALUMINIO|PERFIL.*PVC/u', 'Suministro e Instalación', 88, 95, 'Ventaneria sinonimos'
    UNION ALL SELECT 'FACHADA', '/MURO.*CORTINA|FACHADA.*FLOTANTE|FACHADA.*SISTEMA|ENVOLVENTE/u', 'Suministro e Instalación', 88, 95, 'Fachada variantes'
    UNION ALL SELECT 'ASCENSORES', '/ASCENSOR.*PASAJEROS|ASCENSOR.*CARGA|ELEVADOR.*PASAJEROS/u', 'Suministro e Instalación', 98, 130, 'Ascensores especificos'

    UNION ALL SELECT 'RED_ELECTRICA', '/CANALIZACION.*ELECTRICA|BANDEJA.*PORTACABLE|SUBESTACION|PLANTA.*ELECTRICA|PUESTA.*TIERRA/u', 'Suministro e Instalación', 92, 110, 'Electricas variantes'
    UNION ALL SELECT 'RED_TELECOMUNICACIONES', '/CABLEADO|RACK.*COMUNICACIONES|PUNTO.*DATOS|CAMARA.*CCTV|CONTROL.*ACCESO/u', 'Suministro e Instalación', 94, 120, 'Telecom variantes'
    UNION ALL SELECT 'RED_HIDROSANITARIA', '/PVC.*SANITARIO|PPR|CPVC|TUBERIA.*SANITARIA|TUBERIA.*HIDRAULICA|CAJA.*INSPECCION/u', 'Suministro e Instalación', 90, 105, 'Hidrosanitaria variantes'
    UNION ALL SELECT 'RED_GAS', '/MEDIDOR.*GAS|PUNTO.*GAS|TUBERIA.*GAS|GAS.*DOMICILIARIO/u', 'Suministro e Instalación', 92, 110, 'Gas variantes'
    UNION ALL SELECT 'RED_CONTRAINCENDIO', '/TUBERIA.*RCI|ROCIADORES|SIAMESA|VALVULA.*INCENDIO/u', 'Suministro e Instalación', 92, 115, 'RCI piping variantes'
    UNION ALL SELECT 'DETECCION_INCENDIO', '/DETECTOR|SENSOR.*TEMPERATURA|ESTACION.*MANUAL|SIRENA.*ESTROBO/u', 'Suministro e Instalación', 95, 120, 'Deteccion incendio variantes'
    UNION ALL SELECT 'EQUIPOS_INCENDIO', '/GABINETE|MANGUERA|EXTINCION|HIDRANTE|EXTINTOR.*ABC/u', 'Suministro e Instalación', 94, 115, 'Equipos incendio variantes'
    UNION ALL SELECT 'AIRE_ACONDICIONADO', '/DUCTO.*AIRE|UNIDAD.*MANEJADORA|CASSETTE|CONDENSADORA|EVAPORADORA/u', 'Suministro e Instalación', 90, 105, 'Aire acondicionado variantes'

    UNION ALL SELECT 'PAISAJISMO', '/PASTO|PRADO|MATERA|ARBUSTO|SIEMBRA/u', 'Mano de Obra y Suministro por separado', 84, 90, 'Paisajismo sinonimos'
    UNION ALL SELECT 'NOMENCLATURA', '/SENAL.*VERTICAL|SENAL.*HORIZONTAL|DEMARCACION|PLACAS.*NOMENCLATURA/u', 'Suministro e Instalación', 88, 105, 'Senalizacion variantes'
    UNION ALL SELECT 'VIAS_PAVIMENTOS', '/ANDENES|SARDINEL|BORDILLOS|ADOQUIN|ADOQUINES|PLACA.*HUELLA/u', 'Suministro e Instalación', 88, 100, 'Urbanismo pavimentos variantes'

    UNION ALL SELECT 'ACABADOS', '/MANO.*OBRA.*PINTURA|MANO.*OBRA.*REVOQUE|MANO.*OBRA.*ENCHAPE|MANO.*OBRA.*PISO/u', 'Mano de Obra', 90, 105, 'MO acabados especificos'
    UNION ALL SELECT 'ESTRUCTURA', '/CUADRILLA.*CONCRETO|CUADRILLA.*ACERO|MANO.*OBRA.*CONCRETO/u', 'Mano de Obra', 90, 105, 'MO estructura especifica'
    UNION ALL SELECT 'INSTALACIONES', '/MANO.*OBRA.*ELECTRICA|MANO.*OBRA.*HIDROSANITARIA|MANO.*OBRA.*GAS/u', 'Mano de Obra', 90, 105, 'MO instalaciones especifica'

    UNION ALL SELECT 'BOMBA_CONCRETO', '/SERVICIO.*BOMBEO|BOMBEO.*PREMEZCLADO/u', 'Suministro e Instalación', 92, 110, 'Bombeo concreto sinonimos'
    UNION ALL SELECT 'TORREGRUA', '/GRUA.*TORRE|TORRE.*GRUA.*ALQUILER/u', 'Suministro e Instalación', 92, 110, 'Torregrua sinonimos'
    UNION ALL SELECT 'CONTENEDORES', '/ALQUILER.*CONTENEDOR|CONTAINER.*ALMACEN/u', 'Suministro e Instalación', 86, 95, 'Contenedores sinonimos'
) seed
INNER JOIN `general_pdc_familias` f ON f.codigo = seed.codigo;

-- ============================================================================
-- 6. Opciones de contrato por familia
-- ============================================================================

INSERT INTO `general_pdc_family_contract_options` (
    `familia_id`, `tipo_contrato`, `tipo_paquete`,
    `dias_elaboracion`, `dias_entrega`, `dias_recibo`, `dias_cuadros`,
    `dias_legalizacion`, `dias_fabricacion`, `dias_insumos`, `notas`
)
SELECT f.id, seed.tipo_contrato, seed.tipo_paquete,
       seed.dias_elaboracion, seed.dias_entrega, 1, seed.dias_cuadros,
       seed.dias_legalizacion, seed.dias_fabricacion, seed.dias_insumos, seed.notas
FROM (
    SELECT 'PRELIMINARES' codigo, 1 tipo_contrato, 'Suministro' tipo_paquete, 8 dias_elaboracion, 7 dias_entrega, 5 dias_cuadros, 10 dias_legalizacion, 7 dias_fabricacion, 0 dias_insumos, 'Cerramientos y preliminares' notas
    UNION ALL SELECT 'CAMPAMENTO', 1, 'Suministro', 8, 8, 5, 5, 15, 0, 'Campamento requiere revision manual'
    UNION ALL SELECT 'VIGILANCIA', 1, 'Suministro', 8, 8, 5, 15, 1, 0, 'Servicio de vigilancia'
    UNION ALL SELECT 'PROVISIONALES_ELECTRICOS', 1, 'Suministro', 8, 8, 5, 5, 7, 0, 'Provisionales electricos'
    UNION ALL SELECT 'PROVISIONALES_HS', 1, 'Suministro', 8, 8, 5, 5, 0, 0, 'Provisionales hidrosanitarios'
    UNION ALL SELECT 'BANOS_PORTATILES', 1, 'Suministro', 8, 8, 5, 5, 1, 0, 'Banos portatiles'
    UNION ALL SELECT 'PMT', 1, 'Suministro', 8, 8, 5, 5, 1, 0, 'Implementacion PMT'
    UNION ALL SELECT 'PLAN_CALIDAD', 1, 'Suministro', 8, 8, 5, 15, 1, 0, 'Plan de calidad'

    UNION ALL SELECT 'EXCAVACIONES', 1, 'Suministro', 8, 7, 5, 10, 0, 0, 'Excavaciones y llenos'
    UNION ALL SELECT 'EXCAVACION_MANUAL', 1, 'Suministro', 8, 7, 5, 10, 0, 0, 'Excavaciones manuales'
    UNION ALL SELECT 'PILOTEAJE', 1, 'Suministro', 8, 7, 5, 10, 0, 0, 'Piloteaje'
    UNION ALL SELECT 'PILAS_MECANICAS', 1, 'Suministro', 8, 7, 5, 10, 0, 0, 'Pilas mecanicas'
    UNION ALL SELECT 'PILAS_EXCAVADAS', 1, 'Suministro', 8, 7, 5, 10, 0, 0, 'Pilas excavadas'
    UNION ALL SELECT 'CIMENTACION_ZAPATAS', 1, 'Mano de Obra y Suministro por separado', 8, 7, 5, 10, 0, 0, 'Zapatas MO+S'
    UNION ALL SELECT 'CIMENTACION_LOSAS', 1, 'Mano de Obra y Suministro por separado', 8, 7, 5, 10, 0, 0, 'Losas de cimentacion MO+S'
    UNION ALL SELECT 'CIMENTACION_VIGAS', 1, 'Mano de Obra y Suministro por separado', 8, 7, 5, 10, 0, 0, 'Vigas de cimentacion MO+S'
    UNION ALL SELECT 'CONTENCIONES', 2, 'Suministro e Instalación', 8, 7, 5, 10, 0, 0, 'Muros de contencion'

    UNION ALL SELECT 'ESTRUCTURA_CONCRETO', 1, 'Mano de Obra y Suministro por separado', 8, 7, 5, 10, 0, 0, 'Concreto + mano de obra estructura'
    UNION ALL SELECT 'ESTRUCTURA_ACERO', 1, 'Mano de Obra y Suministro por separado', 7, 5, 25, 20, 25, 15, 'Acero de refuerzo + colocacion'
    UNION ALL SELECT 'ENCOFRADO', 1, 'Suministro', 8, 7, 5, 10, 0, 0, 'Encofrado y formaleta'
    UNION ALL SELECT 'ALIGERANTES', 1, 'Suministro', 8, 7, 5, 10, 0, 0, 'Aligerantes'

    UNION ALL SELECT 'MAMPOSTERIA', 1, 'Mano de Obra y Suministro por separado', 8, 7, 5, 10, 0, 0, 'Mamposteria interior MO+S'
    UNION ALL SELECT 'MAMPOSTERIA_FACHADA', 1, 'Mano de Obra y Suministro por separado', 8, 7, 5, 10, 0, 0, 'Mamposteria fachada MO+S'

    UNION ALL SELECT 'MORTEROS', 1, 'Mano de Obra y Suministro por separado', 8, 7, 5, 10, 0, 0, 'Morteros MO+S'
    UNION ALL SELECT 'REVOQUES', 1, 'Mano de Obra y Suministro por separado', 8, 7, 5, 10, 0, 0, 'Revoques MO+S'
    UNION ALL SELECT 'ESTUCO', 1, 'Mano de Obra y Suministro por separado', 8, 7, 5, 10, 0, 0, 'Estuco MO+S'
    UNION ALL SELECT 'PINTURAS', 1, 'Mano de Obra y Suministro por separado', 8, 7, 5, 10, 0, 0, 'Pinturas MO+S'
    UNION ALL SELECT 'PISOS', 1, 'Mano de Obra y Suministro por separado', 8, 7, 5, 10, 0, 0, 'Pisos MO+S'
    UNION ALL SELECT 'PISOS_LAMINADOS', 2, 'Suministro e Instalación', 8, 7, 5, 10, 0, 0, 'Pisos laminados SI'
    UNION ALL SELECT 'PISOS_MADERA', 1, 'Mano de Obra y Suministro por separado', 8, 7, 5, 10, 0, 0, 'Pisos madera MO+S'
    UNION ALL SELECT 'ENCHAPES', 1, 'Mano de Obra y Suministro por separado', 8, 7, 5, 10, 0, 0, 'Enchapes MO+S'
    UNION ALL SELECT 'CIELOS_RASOS', 2, 'Suministro e Instalación', 10, 20, 40, 30, 60, 30, 'Cielos rasos SI'
    UNION ALL SELECT 'IMPERMEABILIZACIONES', 2, 'Suministro e Instalación', 10, 15, 15, 15, 15, 7, 'Impermeabilizaciones SI'
    UNION ALL SELECT 'MESONES_COCINA', 2, 'Suministro e Instalación', 8, 7, 5, 10, 0, 0, 'Mesones cocina SI'
    UNION ALL SELECT 'MESONES_BANO', 2, 'Suministro e Instalación', 8, 7, 5, 10, 0, 0, 'Mesones bano SI'
    UNION ALL SELECT 'SANITARIOS', 2, 'Suministro e Instalación', 8, 7, 5, 10, 0, 0, 'Aparatos sanitarios SI'
    UNION ALL SELECT 'PUERTAS', 2, 'Suministro e Instalación', 10, 10, 20, 20, 45, 15, 'Puertas SI'
    UNION ALL SELECT 'VENTANERIA', 2, 'Suministro e Instalación', 15, 30, 45, 30, 60, 15, 'Ventaneria SI'
    UNION ALL SELECT 'CARPINTERIA_MADERA', 2, 'Suministro e Instalación', 10, 30, 30, 30, 90, 30, 'Carpinteria madera SI'
    UNION ALL SELECT 'CARPINTERIA_METALICA', 2, 'Suministro e Instalación', 10, 15, 30, 30, 60, 15, 'Carpinteria metalica SI'
    UNION ALL SELECT 'FACHADA', 2, 'Suministro e Instalación', 10, 20, 30, 30, 60, 15, 'Fachada SI'
    UNION ALL SELECT 'LUMINARIAS', 2, 'Suministro e Instalación', 8, 7, 5, 10, 0, 0, 'Luminarias SI'
    UNION ALL SELECT 'VIDRIERIA', 2, 'Suministro e Instalación', 8, 7, 5, 10, 0, 0, 'Vidrieria SI'
    UNION ALL SELECT 'FILTROS', 2, 'Suministro e Instalación', 5, 5, 5, 10, 0, 0, 'Filtros SI'
    UNION ALL SELECT 'ASCENSORES', 2, 'Suministro e Instalación', 15, 15, 45, 40, 300, 20, 'Ascensores SI'

    UNION ALL SELECT 'RED_ELECTRICA', 2, 'Suministro e Instalación', 8, 7, 5, 10, 0, 0, 'Red electrica SI'
    UNION ALL SELECT 'RED_TELECOMUNICACIONES', 2, 'Suministro e Instalación', 8, 7, 5, 10, 0, 0, 'Telecomunicaciones SI'
    UNION ALL SELECT 'RED_HIDROSANITARIA', 2, 'Suministro e Instalación', 8, 7, 5, 10, 0, 0, 'Red hidrosanitaria SI'
    UNION ALL SELECT 'RED_GAS', 2, 'Suministro e Instalación', 30, 7, 5, 10, 0, 0, 'Red gas SI'
    UNION ALL SELECT 'RED_CONTRAINCENDIO', 2, 'Suministro e Instalación', 8, 7, 5, 10, 0, 0, 'Red contra incendio piping SI'
    UNION ALL SELECT 'DETECCION_INCENDIO', 2, 'Suministro e Instalación', 8, 7, 5, 10, 0, 0, 'Deteccion incendio SI'
    UNION ALL SELECT 'EQUIPOS_INCENDIO', 2, 'Suministro e Instalación', 8, 7, 5, 10, 0, 0, 'Equipos incendio SI'
    UNION ALL SELECT 'BOMBA_RCI', 2, 'Suministro e Instalación', 8, 7, 5, 10, 0, 0, 'Bomba RCI SI'
    UNION ALL SELECT 'RCI', 2, 'Suministro e Instalación', 8, 7, 5, 10, 0, 0, 'RCI SI'
    UNION ALL SELECT 'AIRE_ACONDICIONADO', 2, 'Suministro e Instalación', 10, 10, 10, 10, 20, 0, 'Aire acondicionado SI'

    UNION ALL SELECT 'PAISAJISMO', 1, 'Mano de Obra y Suministro por separado', 8, 7, 5, 10, 0, 0, 'Paisajismo MO+S'
    UNION ALL SELECT 'NOMENCLATURA', 1, 'Suministro', 8, 7, 5, 10, 0, 0, 'Nomenclatura'
    UNION ALL SELECT 'ENGRAMADOS', 1, 'Suministro', 8, 7, 5, 10, 0, 0, 'Engramados'
    UNION ALL SELECT 'MOBILIARIO', 2, 'Suministro e Instalación', 8, 7, 5, 10, 0, 0, 'Mobiliario urbano SI'
    UNION ALL SELECT 'VIAS_PAVIMENTOS', 2, 'Suministro e Instalación', 8, 7, 5, 10, 0, 0, 'Vias y pavimentos SI'

    UNION ALL SELECT 'ESTRUCTURA', 1, 'Mano de Obra', 10, 15, 10, 20, 30, 0, 'Mano de obra estructura'
    UNION ALL SELECT 'ACABADOS', 1, 'Mano de Obra', 10, 15, 10, 20, 30, 0, 'Mano de obra acabados'
    UNION ALL SELECT 'INSTALACIONES', 1, 'Mano de Obra', 10, 15, 10, 20, 30, 0, 'Mano de obra instalaciones'
    UNION ALL SELECT 'CIMENTACION', 1, 'Mano de Obra', 10, 15, 10, 20, 30, 0, 'Mano de obra cimentacion'
    UNION ALL SELECT 'URBANISMO', 1, 'Mano de Obra', 10, 15, 10, 20, 30, 0, 'Mano de obra urbanismo'

    UNION ALL SELECT 'BOMBA_CONCRETO', 2, 'Suministro e Instalación', 10, 15, 10, 20, 30, 0, 'Bomba concreto'
    UNION ALL SELECT 'TORREGRUA', 2, 'Suministro e Instalación', 10, 15, 10, 20, 30, 0, 'Torregrua'
    UNION ALL SELECT 'PLANTA_CONCRETO', 2, 'Suministro e Instalación', 10, 15, 10, 20, 30, 0, 'Planta concreto'
    UNION ALL SELECT 'CONTENEDORES', 2, 'Suministro e Instalación', 10, 15, 10, 20, 30, 0, 'Contenedores'
    UNION ALL SELECT 'MONTACARGAS', 2, 'Suministro e Instalación', 10, 10, 10, 10, 20, 0, 'Montacargas'
    UNION ALL SELECT 'MOTORGRUA', 2, 'Suministro e Instalación', 10, 11, 13, 20, 27, 0, 'Motorgrua'
    UNION ALL SELECT 'EXCAVADORA', 2, 'Suministro e Instalación', 10, 11, 13, 20, 27, 0, 'Excavadora'
    UNION ALL SELECT 'VOLQUETA', 2, 'Suministro e Instalación', 10, 11, 13, 20, 27, 0, 'Volqueta'
) seed
INNER JOIN `general_pdc_familias` f ON f.codigo = seed.codigo;

-- ============================================================================
-- 7. Items/paquetes por opcion
-- ============================================================================

INSERT INTO `general_pdc_family_contract_option_items` (
    `option_id`, `tipo_contrato`, `tipo_paquete`, `paquete_nombre`, `dias_proceso_id`, `orden`
)
SELECT opt.id,
       seed.item_tipo_contrato,
       seed.item_tipo_paquete,
       seed.paquete_nombre,
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
    SELECT 'PRELIMINARES' codigo, 'Suministro' option_tipo_paquete, 1 item_tipo_contrato, 'Suministro' item_tipo_paquete, 'CERRAMIENTOS PROVISIONALES' paquete_nombre, 'CERRAMIENTO%' dias_like, 1 orden
    UNION ALL SELECT 'CAMPAMENTO', 'Suministro', 1, 'Suministro', 'CAMPAMENTO - ALMACEN', 'CAMPAMENTO%', 1
    UNION ALL SELECT 'VIGILANCIA', 'Suministro', 1, 'Suministro', 'SERVICIO DE VIGILANCIA', 'VIGILANCIA%', 1
    UNION ALL SELECT 'PROVISIONALES_ELECTRICOS', 'Suministro', 1, 'Suministro', 'PROVISIONALES ELECTRICOS', 'PROVISIONALES ELECTRIC%', 1
    UNION ALL SELECT 'PROVISIONALES_HS', 'Suministro', 1, 'Suministro', 'PROVISIONALES HIDROSANITARIOS', 'PROVISIONALES HIDROSANIT%', 1
    UNION ALL SELECT 'BANOS_PORTATILES', 'Suministro', 1, 'Suministro', 'BANOS PORTATILES', 'BA%OS PORTATILES', 1
    UNION ALL SELECT 'PMT', 'Suministro', 1, 'Suministro', 'IMPLEMENTACION PMT', 'PMT%', 1
    UNION ALL SELECT 'PLAN_CALIDAD', 'Suministro', 1, 'Suministro', 'PLAN DE CALIDAD', 'PLAN DE CALIDAD%', 1

    UNION ALL SELECT 'EXCAVACIONES', 'Suministro', 1, 'Suministro', 'EXCAVACIONES Y LLENOS', 'EXCAVACIONES%', 1
    UNION ALL SELECT 'EXCAVACION_MANUAL', 'Suministro', 1, 'Suministro', 'EXCAVACIONES MANUALES', 'EXCAVACIONES MANUALES%', 1
    UNION ALL SELECT 'PILOTEAJE', 'Suministro', 1, 'Suministro', 'PILOTEAJE', 'PILOTEAJE%', 1
    UNION ALL SELECT 'PILAS_MECANICAS', 'Suministro', 1, 'Suministro', 'PILAS MECANICAS', 'PILAS MECANICAS%', 1
    UNION ALL SELECT 'PILAS_EXCAVADAS', 'Suministro', 1, 'Suministro', 'PILAS EXCAVADAS', 'PILAS EXCAVADAS%', 1
    UNION ALL SELECT 'CIMENTACION_ZAPATAS', 'Mano de Obra y Suministro por separado', 1, 'Suministro', 'CONCRETO ZAPATAS', 'CONCRETO%', 1
    UNION ALL SELECT 'CIMENTACION_ZAPATAS', 'Mano de Obra y Suministro por separado', 1, 'Mano de Obra', 'MANO DE OBRA ZAPATAS', 'MANO DE OBRA%', 2
    UNION ALL SELECT 'CIMENTACION_LOSAS', 'Mano de Obra y Suministro por separado', 1, 'Suministro', 'CONCRETO LOSAS DE CIMENTACION', 'CONCRETO%', 1
    UNION ALL SELECT 'CIMENTACION_LOSAS', 'Mano de Obra y Suministro por separado', 1, 'Mano de Obra', 'MANO DE OBRA LOSAS DE CIMENTACION', 'MANO DE OBRA%', 2
    UNION ALL SELECT 'CIMENTACION_VIGAS', 'Mano de Obra y Suministro por separado', 1, 'Suministro', 'CONCRETO VIGAS DE CIMENTACION', 'CONCRETO%', 1
    UNION ALL SELECT 'CIMENTACION_VIGAS', 'Mano de Obra y Suministro por separado', 1, 'Mano de Obra', 'MANO DE OBRA VIGAS DE CIMENTACION', 'MANO DE OBRA%', 2
    UNION ALL SELECT 'CONTENCIONES', 'Suministro e Instalación', 2, 'Suministro e Instalación', 'MUROS DE CONTENCION', 'MUROS DE CONTENCION%', 1

    UNION ALL SELECT 'ESTRUCTURA_CONCRETO', 'Mano de Obra y Suministro por separado', 1, 'Suministro', 'CONCRETO', 'CONCRETO%', 1
    UNION ALL SELECT 'ESTRUCTURA_CONCRETO', 'Mano de Obra y Suministro por separado', 1, 'Mano de Obra', 'ESTRUCTURA EN CONCRETO', 'ESTRUCTURA EN CONCRETO%', 2
    UNION ALL SELECT 'ESTRUCTURA_ACERO', 'Mano de Obra y Suministro por separado', 1, 'Suministro', 'ACERO DE REFUERZO', 'ACERO DE REFUERZO%', 1
    UNION ALL SELECT 'ESTRUCTURA_ACERO', 'Mano de Obra y Suministro por separado', 1, 'Mano de Obra', 'MANO DE OBRA COLOCACION DE ACERO', 'MO COLOCACION DE ACERO%', 2
    UNION ALL SELECT 'ENCOFRADO', 'Suministro', 1, 'Suministro', 'ENCOFRADO Y FORMALETA', 'ENCOFRADO%', 1
    UNION ALL SELECT 'ALIGERANTES', 'Suministro', 1, 'Suministro', 'ALIGERANTES', 'ALIGERANTE%', 1

    UNION ALL SELECT 'MAMPOSTERIA', 'Mano de Obra y Suministro por separado', 1, 'Suministro', 'LADRILLO INTERIOR', 'LADRILLO INTERIOR%', 1
    UNION ALL SELECT 'MAMPOSTERIA', 'Mano de Obra y Suministro por separado', 1, 'Mano de Obra', 'MAMPOSTERIA', 'MAMPOSTER%', 2
    UNION ALL SELECT 'MAMPOSTERIA_FACHADA', 'Mano de Obra y Suministro por separado', 1, 'Suministro', 'LADRILLO DE FACHADA', 'LADRILLO DE FACHADA%', 1
    UNION ALL SELECT 'MAMPOSTERIA_FACHADA', 'Mano de Obra y Suministro por separado', 1, 'Mano de Obra', 'MAMPOSTERIA DE FACHADA', 'MAMPOSTERIA DE FACHADA%', 2

    UNION ALL SELECT 'MORTEROS', 'Mano de Obra y Suministro por separado', 1, 'Suministro', 'MORTEROS DE NIVELACION', 'MORTEROS%', 1
    UNION ALL SELECT 'MORTEROS', 'Mano de Obra y Suministro por separado', 1, 'Mano de Obra', 'MANO DE OBRA MORTEROS', 'MANO DE OBRA MORTEROS%', 2
    UNION ALL SELECT 'REVOQUES', 'Mano de Obra y Suministro por separado', 1, 'Suministro', 'MORTERO REVOQUE', 'MORTERO REVOQUE%', 1
    UNION ALL SELECT 'REVOQUES', 'Mano de Obra y Suministro por separado', 1, 'Mano de Obra', 'REVOQUE INTERIOR', 'REVOQUE INTERIOR%', 2
    UNION ALL SELECT 'ESTUCO', 'Mano de Obra y Suministro por separado', 1, 'Suministro', 'ESTUCO', 'ESTUCO%', 1
    UNION ALL SELECT 'ESTUCO', 'Mano de Obra y Suministro por separado', 1, 'Mano de Obra', 'MANO DE OBRA ESTUCO', 'MANO DE OBRA ESTUCO%', 2
    UNION ALL SELECT 'PINTURAS', 'Mano de Obra y Suministro por separado', 1, 'Suministro', 'PINTURA INTERIOR', 'PINTURA INTERIOR%', 1
    UNION ALL SELECT 'PINTURAS', 'Mano de Obra y Suministro por separado', 1, 'Mano de Obra', 'ESTUCO Y PINTURA', 'ESTUCO Y PINTURA%', 2
    UNION ALL SELECT 'PISOS', 'Mano de Obra y Suministro por separado', 1, 'Suministro', 'PISOS Y ENCHAPES CERAMICOS', 'PISOS Y ENCHAPES CER%', 1
    UNION ALL SELECT 'PISOS', 'Mano de Obra y Suministro por separado', 1, 'Mano de Obra', 'ENCHAPES CERAMICOS', 'ENCHAPES CER%', 2
    UNION ALL SELECT 'PISOS_LAMINADOS', 'Suministro e Instalación', 2, 'Suministro e Instalación', 'PISOS LAMINADOS', 'PISOS LAMINADOS%', 1
    UNION ALL SELECT 'PISOS_MADERA', 'Mano de Obra y Suministro por separado', 1, 'Suministro', 'PISOS EN MADERA', 'PISOS EN MADERA%', 1
    UNION ALL SELECT 'PISOS_MADERA', 'Mano de Obra y Suministro por separado', 1, 'Mano de Obra', 'INSTALACION PISOS EN MADERA', 'INSTALACION PISOS EN MADERA%', 2
    UNION ALL SELECT 'ENCHAPES', 'Mano de Obra y Suministro por separado', 1, 'Suministro', 'ENCHAPES CERAMICOS', 'ENCHAPES CER%', 1
    UNION ALL SELECT 'ENCHAPES', 'Mano de Obra y Suministro por separado', 1, 'Mano de Obra', 'MANO DE OBRA ENCHAPES', 'MANO DE OBRA ENCHAPES%', 2
    UNION ALL SELECT 'CIELOS_RASOS', 'Suministro e Instalación', 2, 'Suministro e Instalación', 'CIELOS RASOS', 'CIELOS RASOS%', 1
    UNION ALL SELECT 'IMPERMEABILIZACIONES', 'Suministro e Instalación', 2, 'Suministro e Instalación', 'IMPERMEABILIZACIONES', 'IMPERMEABILIZACIONES%', 1
    UNION ALL SELECT 'MESONES_COCINA', 'Suministro e Instalación', 2, 'Suministro e Instalación', 'MESONES DE COCINA', 'MESONES DE COCINA%', 1
    UNION ALL SELECT 'MESONES_BANO', 'Suministro e Instalación', 2, 'Suministro e Instalación', 'MESONES DE BANO', 'MESONES DE BA%O%', 1
    UNION ALL SELECT 'SANITARIOS', 'Suministro e Instalación', 2, 'Suministro e Instalación', 'APARATOS SANITARIOS', 'APARATOS SANITARIOS%', 1
    UNION ALL SELECT 'PUERTAS', 'Suministro e Instalación', 2, 'Suministro e Instalación', 'PUERTAS EN MADERA', 'PUERTAS EN MADERA%', 1
    UNION ALL SELECT 'VENTANERIA', 'Suministro e Instalación', 2, 'Suministro e Instalación', 'VENTANERIA', 'VENTANER%', 1
    UNION ALL SELECT 'CARPINTERIA_MADERA', 'Suministro e Instalación', 2, 'Suministro e Instalación', 'CARPINTERIA DE MADERA', 'CARPINTER%A DE MADERA%', 1
    UNION ALL SELECT 'CARPINTERIA_METALICA', 'Suministro e Instalación', 2, 'Suministro e Instalación', 'CARPINTERIA METALICA', 'CARPINTER%A MET%LICA%', 1
    UNION ALL SELECT 'FACHADA', 'Suministro e Instalación', 2, 'Suministro e Instalación', 'FACHADA', 'FACHADA%', 1
    UNION ALL SELECT 'LUMINARIAS', 'Suministro e Instalación', 2, 'Suministro e Instalación', 'LUMINARIAS', 'LUMINARIA%', 1
    UNION ALL SELECT 'VIDRIERIA', 'Suministro e Instalación', 2, 'Suministro e Instalación', 'VIDRIERIA', 'VIDRIERIA%', 1
    UNION ALL SELECT 'FILTROS', 'Suministro e Instalación', 2, 'Suministro e Instalación', 'FILTROS', 'FILTROS%', 1
    UNION ALL SELECT 'ASCENSORES', 'Suministro e Instalación', 2, 'Suministro e Instalación', 'ASCENSORES', 'ASCENSORES%', 1

    UNION ALL SELECT 'RED_ELECTRICA', 'Suministro e Instalación', 2, 'Suministro e Instalación', 'RED ELECTRICA', 'RED ELECTRICA%', 1
    UNION ALL SELECT 'RED_TELECOMUNICACIONES', 'Suministro e Instalación', 2, 'Suministro e Instalación', 'REDES DE VOZ Y DATOS', 'REDES ELECTRICAS, VOZ Y DATOS%', 1
    UNION ALL SELECT 'RED_HIDROSANITARIA', 'Suministro e Instalación', 2, 'Suministro e Instalación', 'REDES HIDROSANITARIAS INTERNAS', 'REDES HIDROSANITARIAS%', 1
    UNION ALL SELECT 'RED_GAS', 'Suministro e Instalación', 2, 'Suministro e Instalación', 'RED DE GAS', 'RED DE GAS%', 1
    UNION ALL SELECT 'RED_CONTRAINCENDIO', 'Suministro e Instalación', 2, 'Suministro e Instalación', 'RED CONTRA INCENDIO', 'RED CONTRA INCENDIO%', 1
    UNION ALL SELECT 'DETECCION_INCENDIO', 'Suministro e Instalación', 2, 'Suministro e Instalación', 'DETECCION DE INCENDIO', 'DETECCION DE INCENDIO%', 1
    UNION ALL SELECT 'EQUIPOS_INCENDIO', 'Suministro e Instalación', 2, 'Suministro e Instalación', 'EQUIPOS DE EXTINCION', 'EQUIPOS DE EXTINCION%', 1
    UNION ALL SELECT 'BOMBA_RCI', 'Suministro e Instalación', 2, 'Suministro e Instalación', 'BOMBA RED CONTRA INCENDIO', 'BOMBA%INCENDIO%', 1
    UNION ALL SELECT 'RCI', 'Suministro e Instalación', 2, 'Suministro e Instalación', 'RED RCI', 'RED RCI%', 1
    UNION ALL SELECT 'AIRE_ACONDICIONADO', 'Suministro e Instalación', 2, 'Suministro e Instalación', 'AIRE ACONDICIONADO', 'AIRE ACONDICIONADO%', 1

    UNION ALL SELECT 'PAISAJISMO', 'Mano de Obra y Suministro por separado', 1, 'Suministro', 'PAISAJISMO', 'PAISAJISMO%', 1
    UNION ALL SELECT 'PAISAJISMO', 'Mano de Obra y Suministro por separado', 1, 'Mano de Obra', 'MANO DE OBRA PAISAJISMO', 'MANO DE OBRA PAISAJISMO%', 2
    UNION ALL SELECT 'NOMENCLATURA', 'Suministro', 1, 'Suministro', 'NOMENCLATURA Y SENALIZACION', 'NOMENCLATURA%', 1
    UNION ALL SELECT 'ENGRAMADOS', 'Suministro', 1, 'Suministro', 'ENGRAMADOS', 'ENGRAMADOS%', 1
    UNION ALL SELECT 'MOBILIARIO', 'Suministro e Instalación', 2, 'Suministro e Instalación', 'MOBILIARIO URBANO', 'MOBILIARIO%', 1
    UNION ALL SELECT 'VIAS_PAVIMENTOS', 'Suministro e Instalación', 2, 'Suministro e Instalación', 'VIAS Y PAVIMENTOS', 'PAVIMENTOS%', 1

    UNION ALL SELECT 'ESTRUCTURA', 'Mano de Obra', 1, 'Mano de Obra', 'MANO DE OBRA ESTRUCTURA', 'MANO DE OBRA ESTRUCTURA%', 1
    UNION ALL SELECT 'ACABADOS', 'Mano de Obra', 1, 'Mano de Obra', 'MANO DE OBRA ACABADOS', 'MANO DE OBRA ACABADOS%', 1
    UNION ALL SELECT 'INSTALACIONES', 'Mano de Obra', 1, 'Mano de Obra', 'MANO DE OBRA INSTALACIONES', 'MANO DE OBRA INSTALACIONES%', 1
    UNION ALL SELECT 'CIMENTACION', 'Mano de Obra', 1, 'Mano de Obra', 'MANO DE OBRA CIMENTACION', 'MANO DE OBRA CIMENTACION%', 1
    UNION ALL SELECT 'URBANISMO', 'Mano de Obra', 1, 'Mano de Obra', 'MANO DE OBRA URBANISMO', 'MANO DE OBRA URBANISMO%', 1

    UNION ALL SELECT 'BOMBA_CONCRETO', 'Suministro e Instalación', 2, 'Suministro e Instalación', 'BOMBA DE CONCRETO', 'BOMBA DE CONCRETO%', 1
    UNION ALL SELECT 'TORREGRUA', 'Suministro e Instalación', 2, 'Suministro e Instalación', 'TORREGRUA', 'TORREGR%A%', 1
    UNION ALL SELECT 'PLANTA_CONCRETO', 'Suministro e Instalación', 2, 'Suministro e Instalación', 'PLANTA DE CONCRETO', 'PLANTA DE CONCRETO%', 1
    UNION ALL SELECT 'CONTENEDORES', 'Suministro e Instalación', 2, 'Suministro e Instalación', 'CONTENEDORES', 'CONTENEDORES%', 1
    UNION ALL SELECT 'MONTACARGAS', 'Suministro e Instalación', 2, 'Suministro e Instalación', 'MONTACARGAS', 'MONTACARGAS%', 1
    UNION ALL SELECT 'MOTORGRUA', 'Suministro e Instalación', 2, 'Suministro e Instalación', 'MOTORGRUA', 'MOTORGR%A%', 1
    UNION ALL SELECT 'EXCAVADORA', 'Suministro e Instalación', 2, 'Suministro e Instalación', 'EXCAVADORA', 'EXCAVADORA%', 1
    UNION ALL SELECT 'VOLQUETA', 'Suministro e Instalación', 2, 'Suministro e Instalación', 'VOLQUETA', 'VOLQUETA%', 1
) seed
INNER JOIN `general_pdc_familias` f ON f.codigo = seed.codigo
INNER JOIN `general_pdc_family_contract_options` opt
    ON opt.familia_id = f.id
   AND opt.tipo_paquete = seed.option_tipo_paquete;

-- ============================================================================
-- 8. Aliases historicos para duraciones reales
-- ============================================================================

INSERT INTO `general_pdc_paquete_aliases` (`alias`, `canonical_id`, `notas`)
SELECT seed.alias, MIN(dpc.id) AS canonical_id, seed.notas
FROM (
    SELECT 'CONCRETO PARA ESTRUCTURA' alias, 'CONCRETO%' dias_like, 'Alias historico de CONCRETO' notas
    UNION ALL SELECT 'ACERO', 'ACERO DE REFUERZO%', 'Alias historico de ACERO DE REFUERZO'
    UNION ALL SELECT 'MO COLOCACION DE ACERO', 'MANO DE OBRA COLOCACION DE ACERO%', 'Alias de mano de obra de acero'
    UNION ALL SELECT 'ENCHAPES', 'PISOS Y ENCHAPES CER%', 'Alias de pisos y enchapes'
    UNION ALL SELECT 'VENTANERIA', 'VENTANER%', 'Alias sin tilde'
    UNION ALL SELECT 'REDES ELECTRICAS, VOZ Y DATOS', 'REDES ELECTRICAS, VOZ Y DATOS%', 'Historico a separar entre electricidad y telecomunicaciones'
    UNION ALL SELECT 'CONTRAINCENDIO', 'RED CONTRA INCENDIO%', 'Alias red contra incendio'
    UNION ALL SELECT 'GAS', 'RED DE GAS%', 'Alias red de gas'
    UNION ALL SELECT 'HIDROSANITARIAS', 'REDES HIDROSANITARIAS%', 'Alias hidrosanitarias'
    UNION ALL SELECT 'MESONES', 'MESONES DE COCINA%', 'Alias mesones cocina'
    UNION ALL SELECT 'APARATOS', 'APARATOS SANITARIOS%', 'Alias aparatos sanitarios'
) seed
INNER JOIN `general_dias_procesos_contratacion` dpc ON dpc.paqueteContratacion LIKE seed.dias_like
GROUP BY seed.alias, seed.notas
ON DUPLICATE KEY UPDATE canonical_id = VALUES(canonical_id), notas = VALUES(notas);

-- ============================================================================
-- 9. Vista de inventario
-- ============================================================================

CREATE OR REPLACE VIEW `v_pdc_inventory` AS
SELECT
    f.id AS familia_id,
    f.codigo AS familia_codigo,
    f.nombre AS familia_nombre,
    f.categoria,
    f.orden AS familia_orden,
    f.siempre_revision,
    fco.id AS option_id,
    fco.tipo_contrato AS option_tipo_contrato,
    fco.tipo_paquete AS option_tipo_paquete,
    fco.dias_elaboracion,
    fco.dias_entrega,
    fco.dias_recibo,
    fco.dias_cuadros,
    fco.dias_legalizacion,
    fco.dias_fabricacion,
    fco.dias_insumos,
    COALESCE(fcoi.tipo_contrato, fco.tipo_contrato) AS item_tipo_contrato,
    COALESCE(fcoi.tipo_paquete, fco.tipo_paquete) AS item_tipo_paquete,
    fcoi.paquete_nombre,
    fcoi.orden AS item_orden,
    dpc.id AS dias_proceso_id,
    dpc.paqueteContratacion AS dias_proceso_nombre,
    dpc.diasElaboracionPliegos AS real_elaboracion,
    dpc.diasEntregaPliegos AS real_entrega,
    dpc.diasReciboPropuestas AS real_recibo,
    dpc.diasCuadrosComparativos AS real_cuadros,
    dpc.diasLegalizacionContrato AS real_legalizacion,
    dpc.diasFabricacion AS real_fabricacion,
    dpc.diasInsumosObra AS real_insumos
FROM `general_pdc_familias` f
LEFT JOIN `general_pdc_family_contract_options` fco ON fco.familia_id = f.id AND fco.activa = 1
LEFT JOIN `general_pdc_family_contract_option_items` fcoi ON fcoi.option_id = fco.id
LEFT JOIN `general_dias_procesos_contratacion` dpc ON dpc.id = fcoi.dias_proceso_id;

-- ============================================================================
-- 10. Checks de integridad esperados
-- ============================================================================

-- Debe retornar 0: familias sin opcion de contrato.
SELECT COUNT(*) AS familias_sin_opcion
FROM `general_pdc_familias` f
LEFT JOIN `general_pdc_family_contract_options` o ON o.familia_id = f.id
WHERE o.id IS NULL;

-- Debe retornar 0: reglas sin familia.
SELECT COUNT(*) AS reglas_huerfanas
FROM `general_pdc_activity_rules` r
LEFT JOIN `general_pdc_familias` f ON f.id = r.familia_id
WHERE f.id IS NULL;

SET FOREIGN_KEY_CHECKS = 1;
