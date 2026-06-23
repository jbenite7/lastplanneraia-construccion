-- Patch: 20260623_chapter_category_map
-- Creates general_pdc_chapter_category_map table with seed data
-- mapping PG chapter keywords to family categories.
-- Idempotent: safe to run multiple times.

CREATE TABLE IF NOT EXISTS `general_pdc_chapter_category_map` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `chapter_keyword` VARCHAR(200) NOT NULL COMMENT 'Keyword to match inside chapter text (normalized uppercase, no accents)',
  `categoria` VARCHAR(100) NOT NULL COMMENT 'Family category to filter rules to',
  `prioridad` INT DEFAULT 100 COMMENT 'Higher = checked first',
  `activa` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_chapter_categoria` (`chapter_keyword`, `categoria`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `general_pdc_chapter_category_map` (`chapter_keyword`, `categoria`, `prioridad`) VALUES
  ('PRELIMINARES',         'PRELIMINARES',  100),
  ('MOVIMIENTO DE TIERRA', 'CIMENTACION',  100),
  ('EXCAVACION',           'CIMENTACION',  100),
  ('ESTRUCTURA',           'ESTRUCTURA',   100),
  ('ACABADOS',             'ACABADOS',     100),
  ('MAMPOSTERIA',          'MAMPOSTERIA',  100),
  ('REDES',                'INSTALACIONES', 100),
  ('RED ELECTRICA',        'INSTALACIONES', 100),
  ('RED HIDROSANITARIA',   'INSTALACIONES', 100),
  ('URBANISMO',            'URBANISMO',    100),
  ('VIAS',                 'URBANISMO',    100),
  ('ASCENSORES',           'ACABADOS',     100),
  ('REVOQUES',             'ACABADOS',     100),
  ('PISOS',                'ACABADOS',     100),
  ('ENCHAPES',             'ACABADOS',     100),
  ('VENTANERIA',           'ACABADOS',     100),
  ('CARPINTERIA',          'ACABADOS',     100),
  ('MESONES',              'ACABADOS',     100),
  ('ASEO',                 'ACABADOS',     100),
  ('ENTREGA',              'ACABADOS',     100),
  ('SKATE PARK',           'URBANISMO',    100),
  ('CONTENCION',           'CIMENTACION',  100),
  ('PILOTEAJE',            'CIMENTACION',  100);
