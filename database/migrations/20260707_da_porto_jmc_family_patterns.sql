SET NAMES utf8mb4;

INSERT IGNORE INTO `general_pdc_familias`
  (`codigo`, `nombre`, `categoria`, `orden`, `siempre_revision`, `activa`)
VALUES
  ('CIMENTACIONES', 'Cimentaciones', 'CIMENTACION', 140, 0, 1),
  ('ESTABILIZACION_SUELO', 'Estabilización del Suelo', 'CIMENTACION', 150, 0, 1),
  ('TOPOGRAFIA', 'Topografía', 'PRELIMINARES', 12, 0, 1),
  ('MESONES', 'Mesones de Cocina y Baños', 'ACABADOS', 360, 0, 1);

UPDATE `general_pdc_familias`
SET `nombre` = 'Aseo'
WHERE `codigo` = 'ASEO';

UPDATE `general_pdc_familias`
SET `nombre` = 'Red de Detección de Incendio'
WHERE `codigo` = 'DETECCION_INCENDIO';

UPDATE `general_pdc_familias`
SET `nombre` = 'Red de Extinción de Incendios'
WHERE `codigo` = 'RED_EXTINCION';

UPDATE `general_pdc_familias`
SET `nombre` = 'Red Eléctrica'
WHERE `codigo` = 'RED_ELECTRICA';

UPDATE `general_pdc_familias`
SET `nombre` = 'Red de Telecomunicaciones'
WHERE `codigo` = 'RED_TELECOMUNICACIONES';

INSERT IGNORE INTO `general_pdc_family_aliases`
  (`alias_nombre`, `alias_normalizado`, `familia_id`, `alias_family_id`, `fuente`, `notas`)
SELECT alias_nombre, alias_normalizado, canon.id, legacy.id, 'feedback_da_porto_jmc_20260702', notas
FROM (
  SELECT 'Losas de Cimentacion' alias_nombre, 'LOSAS DE CIMENTACION' alias_normalizado, 'CIMENTACIONES' canon_codigo, 'CIMENTACION_LOSAS' legacy_codigo, 'Losa de cimentación se agrupa en Cimentaciones.' notas
  UNION ALL SELECT 'Vigas de Cimentacion', 'VIGAS DE CIMENTACION', 'CIMENTACIONES', 'CIMENTACION_VIGAS', 'Vigas de cimentación se agrupan en Cimentaciones.'
  UNION ALL SELECT 'Zapatas de Cimentacion', 'ZAPATAS DE CIMENTACION', 'CIMENTACIONES', 'CIMENTACION_ZAPATAS', 'Zapatas se agrupan en Cimentaciones.'
  UNION ALL SELECT 'Piloteaje y Micropilotes', 'PILOTEAJE Y MICROPILOTES', 'ESTABILIZACION_SUELO', 'PILOTEAJE', 'Pilotaje, inclusiones y micropilotes pertenecen a Estabilización del Suelo.'
  UNION ALL SELECT 'Pilas Mecanicas', 'PILAS MECANICAS', 'ESTABILIZACION_SUELO', 'PILAS_MECANICAS', 'Pilas se tratan como estabilización del suelo.'
  UNION ALL SELECT 'Pilas Excavadas a Mano', 'PILAS EXCAVADAS A MANO', 'ESTABILIZACION_SUELO', 'PILAS_EXCAVADAS', 'Pilas se tratan como estabilización del suelo.'
  UNION ALL SELECT 'Espejos', 'ESPEJOS', 'CARPINTERIA_METALICA', 'ESPEJOS', 'Espejos se gestionan dentro de Carpintería metálica.'
  UNION ALL SELECT 'Barandas de Balcon', 'BARANDAS DE BALCON', 'CARPINTERIA_METALICA', 'BARANDAS_BALCON', 'Barandas de balcones se gestionan dentro de Carpintería metálica.'
  UNION ALL SELECT 'Ventaneria PVC y Aluminio', 'VENTANERIA PVC Y ALUMINIO', 'CARPINTERIA_METALICA', 'VENTANERIA', 'Ventanería se gestiona dentro de Carpintería metálica.'
  UNION ALL SELECT 'Pasamanos Tubulares y Cerrajeria', 'PASAMANOS TUBULARES Y CERRAJERIA', 'CARPINTERIA_METALICA', 'PASAMANOS_CERRAJERIA', 'Pasamanos se gestionan dentro de Carpintería metálica.'
  UNION ALL SELECT 'Mesones de Cocina', 'MESONES DE COCINA', 'MESONES', 'MESONES_COCINA', 'Mesones de cocina y baños se unifican.'
  UNION ALL SELECT 'Mesones de Bano', 'MESONES DE BANO', 'MESONES', 'MESONES_BANO', 'Mesones de cocina y baños se unifican.'
) moved
JOIN `general_pdc_familias` canon ON canon.codigo = moved.canon_codigo
JOIN `general_pdc_familias` legacy ON legacy.codigo = moved.legacy_codigo;

INSERT INTO `general_pdc_family_rule_audit`
  (`rule_id`, `old_familia_id`, `new_familia_id`, `accion`, `motivo`, `metadata`, `created_by`)
SELECT r.id, r.familia_id, a.familia_id, 'feedback_alias_a_canonica',
       CONCAT('Regla reasignada por feedback Da Porto/JMC desde ', legacy.nombre, ' hacia ', canon.nombre),
       JSON_OBJECT('alias', a.alias_nombre, 'legacy_family_name', legacy.nombre, 'canonical_family_name', canon.nombre),
       'migration_20260707_da_porto_jmc_family_patterns'
FROM `general_pdc_activity_rules` r
JOIN `general_pdc_family_aliases` a ON a.alias_family_id = r.familia_id AND a.fuente = 'feedback_da_porto_jmc_20260702'
JOIN `general_pdc_familias` legacy ON legacy.id = a.alias_family_id
JOIN `general_pdc_familias` canon ON canon.id = a.familia_id
LEFT JOIN `general_pdc_family_rule_audit` prev
  ON prev.rule_id = r.id
 AND prev.accion = 'feedback_alias_a_canonica'
 AND prev.old_familia_id = r.familia_id
 AND prev.new_familia_id = a.familia_id
WHERE prev.id IS NULL;

UPDATE `general_pdc_activity_rules` r
JOIN `general_pdc_family_aliases` a ON a.alias_family_id = r.familia_id AND a.fuente = 'feedback_da_porto_jmc_20260702'
SET r.familia_id = a.familia_id;

UPDATE `general_pdc_familias`
SET activa = 0
WHERE codigo IN (
  'CIMENTACION_LOSAS',
  'CIMENTACION_VIGAS',
  'CIMENTACION_ZAPATAS',
  'PILOTEAJE',
  'PILAS_MECANICAS',
  'PILAS_EXCAVADAS',
  'ESPEJOS',
  'BARANDAS_BALCON',
  'VENTANERIA',
  'PASAMANOS_CERRAJERIA',
  'MESONES_COCINA',
  'MESONES_BANO'
);

UPDATE `general_pdc_contractual_elements`
SET activa = 0,
    notas = CASE
      WHEN COALESCE(notas, '') LIKE '%Reubicado por feedback Da Porto/JMC:%' THEN notas
      ELSE CONCAT(COALESCE(notas, ''), ' | Reubicado por feedback Da Porto/JMC: pertenece a Cimentaciones.')
    END
WHERE nombre_normalizado = 'LOSAS DE CIMENTACION';

INSERT INTO `general_pdc_activity_rules`
  (`familia_id`, `patron_regex`, `modalidad_sugerida`, `confianza`, `prioridad`, `descripcion`, `activa`)
SELECT f.id, '/LOCALIZACION|REPLANTEO|TOPOGRAFIA|LEVANTAMIENTO.*TOPOGRAFICO/u', 'Mano de Obra', 92, 220,
       'Feedback Da Porto/JMC: localización y replanteo pertenecen a Topografía.', 1
FROM `general_pdc_familias` f
WHERE f.codigo = 'TOPOGRAFIA'
  AND NOT EXISTS (
    SELECT 1 FROM `general_pdc_activity_rules` r
    WHERE r.familia_id = f.id AND r.patron_regex = '/LOCALIZACION|REPLANTEO|TOPOGRAFIA|LEVANTAMIENTO.*TOPOGRAFICO/u'
  );

INSERT INTO `general_pdc_activity_rules`
  (`familia_id`, `patron_regex`, `modalidad_sugerida`, `confianza`, `prioridad`, `descripcion`, `activa`)
SELECT f.id, '/PILOTAJE|PILOTE|PILOTES|MICROPILOTE|MICROPILOTES|MICROPILOTES.*INSERTOS|INCLUSION|INCLUSIONES|PILAS/u',
       'Mano de Obra y Suministro por separado', 92, 220,
       'Feedback Da Porto/JMC: pilotaje, inclusiones y micropilotes se agrupan en Estabilización del Suelo.', 1
FROM `general_pdc_familias` f
WHERE f.codigo = 'ESTABILIZACION_SUELO'
  AND NOT EXISTS (
    SELECT 1 FROM `general_pdc_activity_rules` r
    WHERE r.familia_id = f.id AND r.patron_regex = '/PILOTAJE|PILOTE|PILOTES|MICROPILOTE|MICROPILOTES|MICROPILOTES.*INSERTOS|INCLUSION|INCLUSIONES|PILAS/u'
  );

INSERT INTO `general_pdc_activity_rules`
  (`familia_id`, `patron_regex`, `modalidad_sugerida`, `confianza`, `prioridad`, `descripcion`, `activa`)
SELECT f.id, '/CIMENTACION|CIMENTACIONES|LOSA.*CIMENTACION|LOSA.*FUNDACION|ZAPATA|VIGA.*CIMENTACION|VIGA.*FUNDACION/u',
       'Mano de Obra y Suministro por separado', 91, 215,
       'Feedback Da Porto/JMC: losa, viga y zapata de cimentación se agrupan en Cimentaciones.', 1
FROM `general_pdc_familias` f
WHERE f.codigo = 'CIMENTACIONES'
  AND NOT EXISTS (
    SELECT 1 FROM `general_pdc_activity_rules` r
    WHERE r.familia_id = f.id AND r.patron_regex = '/CIMENTACION|CIMENTACIONES|LOSA.*CIMENTACION|LOSA.*FUNDACION|ZAPATA|VIGA.*CIMENTACION|VIGA.*FUNDACION/u'
  );

INSERT INTO `general_pdc_activity_rules`
  (`familia_id`, `patron_regex`, `modalidad_sugerida`, `confianza`, `prioridad`, `descripcion`, `activa`)
SELECT f.id, '/MESON|MESONES|MESON.*COCINA|MESON.*BANO|LAVAMANOS.*MESON|QUARZTONE|GRANITO/u',
       'Suministro e Instalación', 91, 215,
       'Feedback Da Porto/JMC: mesones de cocina y baños se unifican.', 1
FROM `general_pdc_familias` f
WHERE f.codigo = 'MESONES'
  AND NOT EXISTS (
    SELECT 1 FROM `general_pdc_activity_rules` r
    WHERE r.familia_id = f.id AND r.patron_regex = '/MESON|MESONES|MESON.*COCINA|MESON.*BANO|LAVAMANOS.*MESON|QUARZTONE|GRANITO/u'
  );

INSERT INTO `general_pdc_activity_rules`
  (`familia_id`, `patron_regex`, `modalidad_sugerida`, `confianza`, `prioridad`, `descripcion`, `activa`)
SELECT f.id, '/CARPINTERIA.*METALICA|ESPEJO|ESPEJOS|BARANDA|BARANDAS|VENTANA|VENTANERIA|PASAMANOS|CERRAJERIA|ALUMINIO.*VENTANA|VIDRIO.*VENTANA/u',
       'Suministro e Instalación', 91, 215,
       'Feedback Da Porto/JMC: espejos, barandas, ventanería y pasamanos se agrupan en Carpintería metálica.', 1
FROM `general_pdc_familias` f
WHERE f.codigo = 'CARPINTERIA_METALICA'
  AND NOT EXISTS (
    SELECT 1 FROM `general_pdc_activity_rules` r
    WHERE r.familia_id = f.id AND r.patron_regex = '/CARPINTERIA.*METALICA|ESPEJO|ESPEJOS|BARANDA|BARANDAS|VENTANA|VENTANERIA|PASAMANOS|CERRAJERIA|ALUMINIO.*VENTANA|VIDRIO.*VENTANA/u'
  );

INSERT INTO `general_pdc_activity_rules`
  (`familia_id`, `patron_regex`, `modalidad_sugerida`, `confianza`, `prioridad`, `descripcion`, `activa`)
SELECT f.id, '/RED.*EXTINCION|RED.*CONTRA.*INCENDIO|SISTEMA.*EXTINCION|TUBERIA.*EXTINCION|TUBERIA.*INCENDIO|ROCIADOR|ROCIADORES|SPRINKLER/u',
       'Suministro e Instalación', 94, 230,
       'Feedback Da Porto/JMC: la red/sistema de extinción se separa de equipos de extinción.', 1
FROM `general_pdc_familias` f
WHERE f.codigo = 'RED_EXTINCION'
  AND NOT EXISTS (
    SELECT 1 FROM `general_pdc_activity_rules` r
    WHERE r.familia_id = f.id AND r.patron_regex = '/RED.*EXTINCION|RED.*CONTRA.*INCENDIO|SISTEMA.*EXTINCION|TUBERIA.*EXTINCION|TUBERIA.*INCENDIO|ROCIADOR|ROCIADORES|SPRINKLER/u'
  );

INSERT IGNORE INTO `general_pdc_family_contract_options`
  (`familia_id`, `tipo_contrato`, `tipo_paquete`, `dias_elaboracion`, `dias_entrega`, `dias_recibo`, `dias_cuadros`, `dias_legalizacion`, `dias_fabricacion`, `dias_insumos`, `notas`, `activa`)
SELECT f.id, 1, 'Mano de Obra y Suministro por separado', 8, 10, 5, 10, 10, 0, 0, 'Cimentaciones S+MO', 1
FROM `general_pdc_familias` f WHERE f.codigo = 'CIMENTACIONES';

INSERT IGNORE INTO `general_pdc_family_contract_options`
  (`familia_id`, `tipo_contrato`, `tipo_paquete`, `dias_elaboracion`, `dias_entrega`, `dias_recibo`, `dias_cuadros`, `dias_legalizacion`, `dias_fabricacion`, `dias_insumos`, `notas`, `activa`)
SELECT f.id, 1, 'Mano de Obra y Suministro por separado', 8, 10, 5, 10, 10, 0, 0, 'Estabilización del suelo S+MO', 1
FROM `general_pdc_familias` f WHERE f.codigo = 'ESTABILIZACION_SUELO';

INSERT IGNORE INTO `general_pdc_family_contract_options`
  (`familia_id`, `tipo_contrato`, `tipo_paquete`, `dias_elaboracion`, `dias_entrega`, `dias_recibo`, `dias_cuadros`, `dias_legalizacion`, `dias_fabricacion`, `dias_insumos`, `notas`, `activa`)
SELECT f.id, 4, 'Mano de Obra', 3, 2, 2, 2, 2, 0, 0, 'Topografía MO', 1
FROM `general_pdc_familias` f WHERE f.codigo = 'TOPOGRAFIA';

INSERT IGNORE INTO `general_pdc_family_contract_options`
  (`familia_id`, `tipo_contrato`, `tipo_paquete`, `dias_elaboracion`, `dias_entrega`, `dias_recibo`, `dias_cuadros`, `dias_legalizacion`, `dias_fabricacion`, `dias_insumos`, `notas`, `activa`)
SELECT f.id, 2, 'Suministro e Instalación', 8, 7, 5, 10, 20, 15, 0, 'Mesones SI', 1
FROM `general_pdc_familias` f WHERE f.codigo = 'MESONES';

INSERT IGNORE INTO `general_pdc_family_contract_options`
  (`familia_id`, `tipo_contrato`, `tipo_paquete`, `dias_elaboracion`, `dias_entrega`, `dias_recibo`, `dias_cuadros`, `dias_legalizacion`, `dias_fabricacion`, `dias_insumos`, `notas`, `activa`)
SELECT f.id, 2, 'Suministro e Instalación', 8, 15, 5, 10, 20, 15, 0, 'Red de extinción SI', 1
FROM `general_pdc_familias` f WHERE f.codigo = 'RED_EXTINCION';

INSERT IGNORE INTO `general_pdc_family_contract_option_items`
  (`option_id`, `tipo_contrato`, `tipo_paquete`, `paquete_nombre`, `dias_proceso_id`, `orden`)
SELECT o.id, o.tipo_contrato, o.tipo_paquete, item.paquete_nombre, NULL, item.orden
FROM `general_pdc_family_contract_options` o
JOIN `general_pdc_familias` f ON f.id = o.familia_id
JOIN (
  SELECT 'CIMENTACIONES' codigo, 'Suministro' tipo_paquete, 'MATERIALES CIMENTACIONES' paquete_nombre, 1 orden
  UNION ALL SELECT 'CIMENTACIONES', 'Mano de Obra', 'MANO DE OBRA CIMENTACIONES', 2
  UNION ALL SELECT 'ESTABILIZACION_SUELO', 'Suministro', 'MATERIALES ESTABILIZACION DEL SUELO', 1
  UNION ALL SELECT 'ESTABILIZACION_SUELO', 'Mano de Obra', 'MANO DE OBRA ESTABILIZACION DEL SUELO', 2
  UNION ALL SELECT 'TOPOGRAFIA', 'Mano de Obra', 'TOPOGRAFIA', 1
  UNION ALL SELECT 'MESONES', 'Suministro e Instalación', 'MESONES DE COCINA Y BAÑOS', 1
  UNION ALL SELECT 'RED_EXTINCION', 'Suministro e Instalación', 'RED DE EXTINCION DE INCENDIOS', 1
) item ON item.codigo = f.codigo
WHERE o.activa = 1;
