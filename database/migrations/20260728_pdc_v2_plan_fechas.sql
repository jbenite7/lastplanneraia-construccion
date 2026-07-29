-- 20260728_pdc_v2_plan_fechas.sql
-- PDC v2 / Fase A4: el plan de compras con fechas.
--
-- (A) pdc_paquete_frente — el amarre. Un paquete apunta a un encabezado del cronograma por su
--     unique_id, que es estable ante reprogramaciones (medido: los 273 de la semana 1 son los
--     mismos en la semana 4). Se guarda la fecha que tenía ese frente al amarrar para poder
--     detectar después que se movió.
-- (B) pdc_plan_paquete — la cabecera del plan calculado.
-- (C) pdc_plan_paso — una fila por paso del proceso. Tabla hija y no siete columnas porque B1
--     pondrá la fecha real junto a la programada sin rehacer el modelo.
--
-- Convención de fronteras entre pasos (contrato con B1 · Seguimiento — no cambiar sin migrar datos):
-- el intervalo de cada paso es MEDIO ABIERTO, `[fecha_inicio, fecha_fin)`. `fecha_fin` es la
-- frontera en la que el paso entrega el testigo al siguiente, no el último día trabajado: por eso
-- coincide exactamente con la `fecha_inicio` del paso siguiente y no hay ningún día de holgura
-- entre pasos. De ahí se siguen las tres propiedades que el consumidor puede dar por ciertas:
--   1. `dias` = DATEDIFF(fecha_fin, fecha_inicio), sin sumar ni restar uno.
--   2. la suma de los siete `dias` es exactamente el intervalo completo del proceso
--      (`pdc_plan_paquete.fecha_arranque` → `pdc_plan_paquete.fecha_ancla`).
--   3. la `fecha_fin` del último paso ES `fecha_ancla`: el día en que el insumo se necesita en obra.
-- Al comparar avance real contra programado, un paso va a tiempo si se cerró ANTES de su
-- `fecha_fin`. No leer `fecha_fin` como «último día del paso» ni contar `fin - inicio + 1`: ese día
-- pertenece al paso siguiente y contarlo dos veces infla el proceso en siete días.
--
-- Las tres referencian su catálogo padre (paquete_id → general_paquetes_contratacion),
-- igual que el resto de tablas PDC (ver fk_pip_paquete en 20260724_pdc_v2_paquetes_contratacion.sql).
--
-- Convergencia, no solo idempotencia (hallazgo Importante del review):
-- una versión anterior de este archivo (sin las tres FK y con el índice de más
-- `idx_pps_proyecto_paquete` en pdc_plan_paso) llegó a aplicarse en al menos un entorno. Con
-- `CREATE TABLE IF NOT EXISTS` a secas, volver a correr la versión corregida ahí es un no-op
-- silencioso: las tablas ya existen, nada falla, y ese entorno queda divergente (sin FK) para
-- siempre. Por eso, igual que en 20260724_pdc_v2_version_numero_unique.sql, además del
-- `CREATE TABLE IF NOT EXISTS` (para el clon nuevo) se agrega un procedimiento con guardas de
-- `information_schema` que, si las tablas ya existían, añade las FK que falten y quita el índice
-- redundante — sin tocar filas. Así el archivo converge al esquema correcto sea cual sea el punto
-- de partida, y una tercera ejecución sigue sin hacer nada (no-op real, no accidental).

CREATE TABLE IF NOT EXISTS pdc_paquete_frente (
  id BIGINT NOT NULL AUTO_INCREMENT,
  project_id INT NOT NULL,
  paquete_id BIGINT NOT NULL,
  unique_id INT NOT NULL,
  frente_nombre VARCHAR(500) NOT NULL,
  fecha_ancla DATE NOT NULL,
  semana_origen INT NOT NULL,
  origen ENUM('similitud','rama','humano') NOT NULL DEFAULT 'humano',
  confianza ENUM('alta','media','baja') NULL,
  evidencia VARCHAR(500) NOT NULL DEFAULT '',
  confirmado_humano TINYINT(1) NOT NULL DEFAULT 0,
  asignado_por VARCHAR(100) NOT NULL DEFAULT '',
  updated_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_ppf_proyecto_paquete (project_id, paquete_id),
  KEY idx_ppf_proyecto_frente (project_id, unique_id),
  CONSTRAINT fk_ppf_paquete FOREIGN KEY (paquete_id) REFERENCES general_paquetes_contratacion (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pdc_plan_paquete (
  id BIGINT NOT NULL AUTO_INCREMENT,
  project_id INT NOT NULL,
  paquete_id BIGINT NOT NULL,
  unique_id INT NOT NULL,
  fecha_ancla DATE NOT NULL,
  fecha_arranque DATE NOT NULL,
  dias_totales INT NOT NULL,
  duracion_ref INT NULL,
  duracion_provisional TINYINT(1) NOT NULL DEFAULT 0,
  responsable VARCHAR(100) NOT NULL DEFAULT '',
  calculado_por VARCHAR(100) NOT NULL DEFAULT '',
  updated_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_ppp_proyecto_paquete (project_id, paquete_id),
  KEY idx_ppp_proyecto_arranque (project_id, fecha_arranque),
  CONSTRAINT fk_ppp_paquete FOREIGN KEY (paquete_id) REFERENCES general_paquetes_contratacion (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pdc_plan_paso (
  id BIGINT NOT NULL AUTO_INCREMENT,
  project_id INT NOT NULL,
  paquete_id BIGINT NOT NULL,
  orden TINYINT NOT NULL,
  paso VARCHAR(60) NOT NULL,
  dias INT NOT NULL,
  fecha_inicio DATE NOT NULL,
  fecha_fin DATE NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_pps_proyecto_paquete_orden (project_id, paquete_id, orden),
  CONSTRAINT fk_pps_paquete FOREIGN KEY (paquete_id) REFERENCES general_paquetes_contratacion (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DELIMITER $$

DROP PROCEDURE IF EXISTS pdc_v2_converge_plan_fechas$$
CREATE PROCEDURE pdc_v2_converge_plan_fechas()
BEGIN
  -- Las tres FK sobre paquete_id: si el CREATE TABLE de arriba fue no-op (tabla ya existía sin
  -- ellas, por venir de la versión vieja del archivo), se agregan aquí. `information_schema`
  -- guarda por nombre de constraint, así que reejecutar tampoco duplica el ALTER.
  IF EXISTS (
    SELECT 1 FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pdc_paquete_frente'
  ) AND NOT EXISTS (
    SELECT 1 FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pdc_paquete_frente'
      AND CONSTRAINT_NAME = 'fk_ppf_paquete' AND CONSTRAINT_TYPE = 'FOREIGN KEY'
  ) THEN
    ALTER TABLE `pdc_paquete_frente`
      ADD CONSTRAINT `fk_ppf_paquete` FOREIGN KEY (`paquete_id`)
        REFERENCES `general_paquetes_contratacion` (`id`) ON DELETE RESTRICT;
  END IF;

  IF EXISTS (
    SELECT 1 FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pdc_plan_paquete'
  ) AND NOT EXISTS (
    SELECT 1 FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pdc_plan_paquete'
      AND CONSTRAINT_NAME = 'fk_ppp_paquete' AND CONSTRAINT_TYPE = 'FOREIGN KEY'
  ) THEN
    ALTER TABLE `pdc_plan_paquete`
      ADD CONSTRAINT `fk_ppp_paquete` FOREIGN KEY (`paquete_id`)
        REFERENCES `general_paquetes_contratacion` (`id`) ON DELETE RESTRICT;
  END IF;

  IF EXISTS (
    SELECT 1 FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pdc_plan_paso'
  ) AND NOT EXISTS (
    SELECT 1 FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pdc_plan_paso'
      AND CONSTRAINT_NAME = 'fk_pps_paquete' AND CONSTRAINT_TYPE = 'FOREIGN KEY'
  ) THEN
    ALTER TABLE `pdc_plan_paso`
      ADD CONSTRAINT `fk_pps_paquete` FOREIGN KEY (`paquete_id`)
        REFERENCES `general_paquetes_contratacion` (`id`) ON DELETE RESTRICT;
  END IF;

  -- Índice redundante de la versión vieja: `idx_pps_proyecto_paquete (project_id, paquete_id)`
  -- es prefijo exacto de `uq_pps_proyecto_paquete_orden (project_id, paquete_id, orden)`, así que
  -- InnoDB ya lo cubre por la regla de prefijo izquierdo. Si quedó de la versión vieja, se quita;
  -- no borra datos, solo el índice.
  IF EXISTS (
    SELECT 1 FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pdc_plan_paso'
      AND INDEX_NAME = 'idx_pps_proyecto_paquete'
  ) THEN
    ALTER TABLE `pdc_plan_paso` DROP INDEX `idx_pps_proyecto_paquete`;
  END IF;
END$$

CALL pdc_v2_converge_plan_fechas()$$
DROP PROCEDURE IF EXISTS pdc_v2_converge_plan_fechas$$

DELIMITER ;
