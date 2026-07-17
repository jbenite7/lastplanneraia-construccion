SET NAMES utf8mb4;

-- Feedback 2026-07-02:
-- - Red Electrica debe proponer Suministro + Mano de Obra por defecto.
-- - Pinturas debe quedar por defecto como Suministro e Instalacion, sin combinar MO + SI.

UPDATE `general_pdc_family_contract_options` o
JOIN `general_pdc_familias` f ON f.id = o.familia_id
SET o.activa = 0,
    o.notas = CONCAT(COALESCE(o.notas, ''), ' | Inactiva por feedback: Pinturas no permite combinacion MO + SI.')
WHERE f.codigo = 'PINTURAS'
  AND o.tipo_paquete <> 'Suministro e Instalación';

INSERT INTO `general_pdc_family_contract_options`
  (`familia_id`, `tipo_contrato`, `tipo_paquete`, `dias_elaboracion`, `dias_entrega`, `dias_recibo`, `dias_cuadros`, `dias_legalizacion`, `dias_fabricacion`, `dias_insumos`, `notas`, `activa`)
SELECT f.id, 2, 'Suministro e Instalación', 8, 10, 5, 10, 20, 0, 0, 'Pinturas SI por defecto: combinacion MO + SI no permitida.', 1
FROM `general_pdc_familias` f
WHERE f.codigo = 'PINTURAS'
ON DUPLICATE KEY UPDATE
  activa = 1,
  notas = VALUES(notas),
  dias_elaboracion = VALUES(dias_elaboracion),
  dias_entrega = VALUES(dias_entrega),
  dias_recibo = VALUES(dias_recibo),
  dias_cuadros = VALUES(dias_cuadros),
  dias_legalizacion = VALUES(dias_legalizacion),
  dias_fabricacion = VALUES(dias_fabricacion),
  dias_insumos = VALUES(dias_insumos);

INSERT INTO `general_pdc_family_contract_option_items`
  (`option_id`, `tipo_contrato`, `tipo_paquete`, `paquete_nombre`, `dias_proceso_id`, `orden`)
SELECT o.id, 2, 'Suministro e Instalación', 'PINTURAS', NULL, 1
FROM `general_pdc_family_contract_options` o
JOIN `general_pdc_familias` f ON f.id = o.familia_id
WHERE f.codigo = 'PINTURAS'
  AND o.tipo_paquete = 'Suministro e Instalación'
ON DUPLICATE KEY UPDATE
  tipo_contrato = VALUES(tipo_contrato),
  orden = VALUES(orden);

UPDATE `general_pdc_family_contract_options` o
JOIN `general_pdc_familias` f ON f.id = o.familia_id
SET o.tipo_contrato = 1,
    o.tipo_paquete = 'Mano de Obra y Suministro por separado',
    o.notas = 'Red Electrica por defecto: suministro de materiales + mano de obra.',
    o.activa = 1
WHERE f.codigo = 'RED_ELECTRICA';

DELETE i
FROM `general_pdc_family_contract_option_items` i
JOIN `general_pdc_family_contract_options` o ON o.id = i.option_id
JOIN `general_pdc_familias` f ON f.id = o.familia_id
WHERE f.codigo = 'RED_ELECTRICA';

INSERT INTO `general_pdc_family_contract_option_items`
  (`option_id`, `tipo_contrato`, `tipo_paquete`, `paquete_nombre`, `dias_proceso_id`, `orden`)
SELECT o.id, seed.tipo_contrato, seed.tipo_paquete, seed.paquete_nombre, NULL, seed.orden
FROM `general_pdc_family_contract_options` o
JOIN `general_pdc_familias` f ON f.id = o.familia_id
JOIN (
  SELECT 3 tipo_contrato, 'Suministro' tipo_paquete, 'MATERIALES RED ELECTRICA' paquete_nombre, 1 orden
  UNION ALL SELECT 4, 'Mano de Obra', 'MANO DE OBRA RED ELECTRICA', 2
) seed
WHERE f.codigo = 'RED_ELECTRICA'
  AND o.activa = 1;
