-- Patch: Monitor de Cambios PG/PI -> PS
-- Crea tabla ligera de snapshots para detectar cambios en programa_consolidado
-- Se ejecuta por proyecto (reemplazar {db} en el script de aplicación)

-- La tabla se crea por proyecto porque cada proyecto tiene su propio prefijo de BD
-- Este script es una plantilla; se aplica dinámicamente para cada proyecto

CREATE TABLE IF NOT EXISTS `{db}_pg_tracking` (
    `consecutivo_en_programa` INT NOT NULL,
    `semana` INT NOT NULL,
    `fecha_inicio` DATE DEFAULT NULL,
    `fecha_fin` DATE DEFAULT NULL,
    `estado` VARCHAR(100) DEFAULT NULL,
    `restricciones_hash` CHAR(32) DEFAULT NULL COMMENT 'MD5 de columnas restriccion concatenadas',
    `fechas_hash` CHAR(32) DEFAULT NULL COMMENT 'MD5 de fecha_inicio+fecha_fin',
    `estado_hash` CHAR(32) DEFAULT NULL COMMENT 'MD5 del estado',
    `titulo` TINYINT(1) NOT NULL DEFAULT '0',
    `ultimo_detectado` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`consecutivo_en_programa`, `semana`),
    KEY `idx_semana` (`semana`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
