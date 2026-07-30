-- 20260729_pdc_v2_subpaquetes.sql
-- PDC v2 / Ola 3 — Subpaquetes: del paquete de preconstrucción al contrato real de la obra.
--
-- El plan de compras se arma en preconstrucción con paquetes grandes (35 paquetes cubren el 86,8 %
-- del presupuesto de Da Porto), pero la obra no contrata así: «Pisos» son porcelanato, tableta gres
-- y cerámica, con proveedores y marcas distintos. Un paquete puede partirse en N subpaquetes, cada
-- uno con sus insumos, su modalidad, su frente y su responsable. El paquete grande se conserva y
-- resume; el que se contrata es el subpaquete.
--
-- QUÉ NO HACE, a propósito (decisiones del comité del 2026-07-29 y del grilleo del mismo día):
--   · No hay subpaquetes de subpaquetes: un solo nivel.
--   · Los subpaquetes NO suben al catálogo global `general_paquetes_contratacion`. Son casuística de
--     una obra, y el comité pidió expresamente que lo que se cree en obra no toque el maestro.
--   · Un insumo no se reparte entre dos subpaquetes. La regla «un insumo, un destino» que sostiene
--     el módulo desde A3 sigue viva en `uq_pip_insumo (project_id, descripcion_norm, unidad)`, que
--     esta migración NO toca: `subpaquete_id` afina el destino, no lo duplica.
--
-- ------------------------------------------------------------------------------------------------
-- POR QUÉ `subpaquete_id BIGINT NOT NULL DEFAULT 0` Y NO UNA COLUMNA NULABLE
-- ------------------------------------------------------------------------------------------------
-- `0` significa «el paquete mismo, sin partir». No es un id: es el valor centinela que hace que un
-- paquete sin partir siga teniendo EXACTAMENTE una fila por (proyecto, paquete) igual que antes.
--
-- Con `NULL` en su lugar, esto se rompe en silencio: en un índice UNIQUE de MySQL dos `NULL` se
-- consideran distintos, así que `uq_ppp_proyecto_paquete (project_id, paquete_id, subpaquete_id)`
-- dejaría de detectar el duplicado y el `ON DUPLICATE KEY UPDATE` de `PlanFechasService::calcular()`
-- no se dispararía: cada recálculo INSERTARÍA una cabecera nueva en vez de actualizar la suya, y el
-- DELETE de pasos sobrantes empezaría a borrar filas recién escritas. Es el mismo fallo que A4.1 ya
-- pagó una vez con `paso_id` (ver la nota de `20260728_pdc_v2_pasos_configurables.php`), y no se
-- vuelve a pagar.
--
-- Precio aceptado y escrito: `subpaquete_id` no lleva FOREIGN KEY a `pdc_subpaquete`, porque `0` no
-- existe en esa tabla. La integridad la sostienen el servicio (que solo escribe ids que acaba de
-- leer) y el `ON DELETE` explícito al borrar un subpaquete. Un FK con centinela exigiría una fila
-- fantasma con id 0 en `pdc_subpaquete`, que es justo el «subpaquete de compatibilidad» que el
-- alcance prohíbe.
--
-- Convergencia además de idempotencia (misma disciplina que 20260728_pdc_v2_plan_fechas.sql): todo
-- va tras guardas de `information_schema`, así que el archivo lleva cualquier punto de partida al
-- esquema correcto y una segunda corrida es un no-op real.
--
-- OJO: los nombres de FOREIGN KEY son únicos en TODO el esquema, no por tabla (ya hizo fallar con
-- un 1826 a `fk_pps_paso`). Los de aquí van prefijados con `psub`/`pip`/`ppf`/`ppp`/`pps` y
-- comprobados contra `TABLE_CONSTRAINTS`, que es global, no contra `STATISTICS` de una tabla.
--
-- ------------------------------------------------------------------------------------------------
-- CÓMO SE APLICA: con el cliente `mysql`, NO con PDO
-- ------------------------------------------------------------------------------------------------
--     docker compose exec -T db mysql -uroot -p"$DB_PASS" "$DB_NAME" < database/migrations/20260729_pdc_v2_subpaquetes.sql
--
-- `DELIMITER $$` es una directiva del CLIENTE `mysql`, no una sentencia SQL: el servidor no la
-- conoce. Pasar este archivo por `PDO::exec()` falla con un 1064 apuntando a esa línea, y se lee
-- como que el SQL está mal escrito cuando lo que está mal es el camino. Comprobado, no supuesto.
--
-- No es una particularidad de este archivo: es la convención de todas las migraciones del repo que
-- necesitan guardas de convergencia — `20260724_pdc_v2_version_numero_unique.sql`,
-- `20260728_pdc_v2_plan_fechas.sql`, `20260729_pdc_v2_seguimiento_avance.sql` y
-- `20260703_contratos_slot_quantities_traceability.sql` usan las mismas. Quien despliegue las aplica
-- igual que esta.

CREATE TABLE IF NOT EXISTS pdc_subpaquete (
  id BIGINT NOT NULL AUTO_INCREMENT,
  project_id INT NOT NULL,
  paquete_id BIGINT NOT NULL,
  nombre VARCHAR(255) NOT NULL,
  -- Modalidad propia y libre, incluidas las que no generan proceso: la obra puede descubrir que una
  -- parte del paquete se compra por orden de compra o que es una provisión. Cuando eso pasa, parte
  -- del valor del paquete grande deja de entrar al plan y al flujo de caja, y la pantalla lo declara
  -- («de $1.000M, $300M no entran al plan porque…»). Callarlo sería la mentira que el módulo evita.
  modalidad_contratacion ENUM('contrato','orden_compra','consumo_directo','no_contratable')
    NOT NULL DEFAULT 'contrato',
  responsable_user_id INT NULL,
  -- El lote «Resto»: nace solo al partir un paquete y recoge los insumos que nadie movió. Existe
  -- para que un paquete partido NUNCA se contrate él mismo, y así ninguna vista tenga que decidir si
  -- la fila del sombrilla cuenta como contrato o como total. Es 0 o 1 por paquete.
  es_resto TINYINT(1) NOT NULL DEFAULT 0,
  orden SMALLINT NOT NULL DEFAULT 0,
  creado_por VARCHAR(100) NOT NULL DEFAULT '',
  updated_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  -- Dos lotes del mismo paquete no pueden llamarse igual en la misma obra: el nombre es lo que la
  -- oficina técnica usa para hablar de ellos.
  UNIQUE KEY uq_psub_nombre (project_id, paquete_id, nombre),
  KEY idx_psub_proyecto_paquete (project_id, paquete_id, orden),
  CONSTRAINT fk_psub_paquete FOREIGN KEY (paquete_id)
    REFERENCES general_paquetes_contratacion (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DELIMITER $$

DROP PROCEDURE IF EXISTS pdc_v2_migra_subpaquetes$$
CREATE PROCEDURE pdc_v2_migra_subpaquetes()
BEGIN
  -- (1) `pdc_insumo_paquete`: a qué lote va el insumo. 0 = el paquete no está partido.
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pdc_insumo_paquete'
      AND COLUMN_NAME = 'subpaquete_id'
  ) THEN
    ALTER TABLE `pdc_insumo_paquete`
      ADD COLUMN `subpaquete_id` BIGINT NOT NULL DEFAULT 0 AFTER `paquete_id`,
      ADD KEY `idx_pip_subpaquete` (`project_id`, `subpaquete_id`);
  END IF;

  -- (2) `pdc_paquete_frente`: el amarre pasa a ser por destino contratable. Un lote sin amarre
  --     propio NO tiene fila aquí: hereda el frente del paquete (la fila con subpaquete_id = 0).
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pdc_paquete_frente'
      AND COLUMN_NAME = 'subpaquete_id'
  ) THEN
    ALTER TABLE `pdc_paquete_frente`
      ADD COLUMN `subpaquete_id` BIGINT NOT NULL DEFAULT 0 AFTER `paquete_id`;
  END IF;
  -- El UNIQUE se reemplaza en dos pasos y con guarda propia: si el ALTER de arriba fue no-op porque
  -- la columna ya estaba, el índice puede seguir siendo el viejo (entorno a medio migrar).
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pdc_paquete_frente'
      AND INDEX_NAME = 'uq_ppf_destino'
  ) THEN
    ALTER TABLE `pdc_paquete_frente`
      ADD UNIQUE KEY `uq_ppf_destino` (`project_id`, `paquete_id`, `subpaquete_id`);
  END IF;
  IF EXISTS (
    SELECT 1 FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pdc_paquete_frente'
      AND INDEX_NAME = 'uq_ppf_proyecto_paquete'
  ) THEN
    ALTER TABLE `pdc_paquete_frente` DROP INDEX `uq_ppf_proyecto_paquete`;
  END IF;

  -- (3) `pdc_plan_paquete`: una cabecera por destino contratable.
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pdc_plan_paquete'
      AND COLUMN_NAME = 'subpaquete_id'
  ) THEN
    ALTER TABLE `pdc_plan_paquete`
      ADD COLUMN `subpaquete_id` BIGINT NOT NULL DEFAULT 0 AFTER `paquete_id`;
  END IF;
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pdc_plan_paquete'
      AND INDEX_NAME = 'uq_ppp_destino'
  ) THEN
    ALTER TABLE `pdc_plan_paquete`
      ADD UNIQUE KEY `uq_ppp_destino` (`project_id`, `paquete_id`, `subpaquete_id`);
  END IF;
  IF EXISTS (
    SELECT 1 FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pdc_plan_paquete'
      AND INDEX_NAME = 'uq_ppp_proyecto_paquete'
  ) THEN
    ALTER TABLE `pdc_plan_paquete` DROP INDEX `uq_ppp_proyecto_paquete`;
  END IF;

  -- (4) `pdc_plan_paso`: los pasos del proceso cuelgan del destino contratable, no del paquete. Es
  --     lo que hace que el avance real de B1 (`fecha_real`) sea el del lote que de verdad se
  --     contrata. La identidad sigue siendo el PASO (`paso_id`) y no su posición, por la misma razón
  --     que en A4.1: meter un paso en medio no debe sobrescribir la fila del vecino.
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pdc_plan_paso'
      AND COLUMN_NAME = 'subpaquete_id'
  ) THEN
    ALTER TABLE `pdc_plan_paso`
      ADD COLUMN `subpaquete_id` BIGINT NOT NULL DEFAULT 0 AFTER `paquete_id`;
  END IF;
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pdc_plan_paso'
      AND INDEX_NAME = 'uq_pps_destino_paso'
  ) THEN
    ALTER TABLE `pdc_plan_paso`
      ADD UNIQUE KEY `uq_pps_destino_paso` (`project_id`, `paquete_id`, `subpaquete_id`, `paso_id`);
  END IF;
  IF EXISTS (
    SELECT 1 FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pdc_plan_paso'
      AND INDEX_NAME = 'uq_pps_proyecto_paquete_paso'
  ) THEN
    ALTER TABLE `pdc_plan_paso` DROP INDEX `uq_pps_proyecto_paquete_paso`;
  END IF;
  -- El índice de orden pasa a incluir el lote: sin él, listar los pasos de un destino en un paquete
  -- muy partido recorre las filas de todos sus hermanos.
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pdc_plan_paso'
      AND INDEX_NAME = 'idx_pps_destino_orden'
  ) THEN
    ALTER TABLE `pdc_plan_paso`
      ADD KEY `idx_pps_destino_orden` (`project_id`, `paquete_id`, `subpaquete_id`, `orden`);
  END IF;
  IF EXISTS (
    SELECT 1 FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pdc_plan_paso'
      AND INDEX_NAME = 'idx_pps_proyecto_paquete_orden'
  ) THEN
    ALTER TABLE `pdc_plan_paso` DROP INDEX `idx_pps_proyecto_paquete_orden`;
  END IF;
END$$

CALL pdc_v2_migra_subpaquetes()$$
DROP PROCEDURE IF EXISTS pdc_v2_migra_subpaquetes$$

DELIMITER ;

-- Sin backfill. Todas las filas existentes se quedan en `subpaquete_id = 0`, que es exactamente lo
-- que significan: paquetes sin partir. Un proyecto sin ningún paquete partido tiene que producir el
-- MISMO plan fila a fila que antes de esta migración, y se comprueba comparando contra
-- `goals/pdc-preparar-b1/evidence/linea-base-plan-antes-subpaquetes.txt`.
