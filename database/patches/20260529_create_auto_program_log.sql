-- Auto-Program Log: traza de acciones automáticas del nuevo autoprogramar simple
-- Cada proyecto tiene su propia tabla con prefijo {Base_de_Datos}_

-- Esta tabla es creada dinámicamente por ProgramChangeDetector si no existe,
-- pero se deja como patch para que la estructura quede documentada.

-- Columnas:
--   id             INT AUTO_INCREMENT PK
--   semana         INT  → semana activa
--   consecutivo    INT  → Consecutivo_en_Programa
--   accion         ENUM('comprometer','descomprometer','insert_cnp')
--   detalle        TEXT → descripción legible de lo que ocurrió
--   categoria_cnp  VARCHAR(100) → solo para insert_cnp
--   cnp            VARCHAR(100) → solo para insert_cnp
--   creado_en      TIMESTAMP DEFAULT CURRENT_TIMESTAMP

CREATE TABLE IF NOT EXISTS `{db}_auto_program_log` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `semana` INT NOT NULL,
    `consecutivo` INT NOT NULL,
    `accion` ENUM('comprometer','descomprometer','insert_cnp') NOT NULL,
    `detalle` TEXT,
    `categoria_cnp` VARCHAR(100) DEFAULT NULL,
    `cnp` VARCHAR(100) DEFAULT NULL,
    `creado_en` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_semana` (`semana`),
    KEY `idx_consecutivo` (`consecutivo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
