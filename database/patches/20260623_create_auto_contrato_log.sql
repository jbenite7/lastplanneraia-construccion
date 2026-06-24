-- Auto-Contrato Log: traza auditable de auto-definición de contratos
-- Cada proyecto tiene su propia tabla con prefijo {Base_de_Datos}_
--
-- Esta tabla es creada dinámicamente por ContratosApiController si no existe,
-- pero se deja como patch para que la estructura quede documentada.
--
-- Columnas:
--   id                      INT AUTO_INCREMENT PK
--   semana                  INT  → semana activa
--   Id_actividad            INT  → Id de la actividad en {db}_actividades
--   accion                  ENUM('asignar','deshacer')
--   tipo_contrato           VARCHAR(10)  → código de modalidad (e.g. "SI", "MO,S")
--   paquetes                JSON         → arreglo de paquetes asignados
--   confianza               DECIMAL(5,2) → confianza de la detección
--   fecha_inicio_proyectada DATE         → fechaInicioProyectada calculada
--   num_proveedores         TINYINT      → numeroSubcontratos
--   usuario                 VARCHAR(100) → $_SESSION['usuario'] ?? 'sistema'
--   batch_id                VARCHAR(36)  → UUID v4 simple por corrida (uniqid)
--   creado_en               TIMESTAMP DEFAULT CURRENT_TIMESTAMP

CREATE TABLE IF NOT EXISTS `{db}_auto_contrato_log` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `semana` INT NOT NULL,
    `Id_actividad` INT NOT NULL,
    `accion` ENUM('asignar','deshacer') NOT NULL,
    `tipo_contrato` VARCHAR(10) DEFAULT NULL,
    `paquetes` JSON DEFAULT NULL,
    `confianza` DECIMAL(5,2) DEFAULT NULL,
    `fecha_inicio_proyectada` DATE DEFAULT NULL,
    `num_proveedores` TINYINT DEFAULT NULL,
    `usuario` VARCHAR(100) DEFAULT NULL,
    `batch_id` VARCHAR(36) NOT NULL,
    `creado_en` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_batch_id` (`batch_id`),
    KEY `idx_semana` (`semana`),
    KEY `idx_Id_actividad` (`Id_actividad`),
    KEY `idx_creado_en` (`creado_en`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
