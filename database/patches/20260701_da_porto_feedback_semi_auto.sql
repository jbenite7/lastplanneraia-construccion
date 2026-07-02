SET NAMES utf8mb4;

-- Feedback Da Porto: ajustes globales del modelo de semi-automatizacion.
-- Incremental e idempotente: no borra catalogos, solo agrega/actualiza reglas y opciones.

INSERT INTO `general_pdc_familias` (`codigo`, `nombre`, `categoria`, `orden`, `siempre_revision`) VALUES
('REVOQUE_HUMEDO', 'Revoque Humedo', 'ACABADOS', 411, 0),
('REVOQUE_SECO', 'Revoque Seco', 'ACABADOS', 412, 0),
('ESPEJOS', 'Espejos', 'ACABADOS', 591, 0),
('CABINAS_BANO', 'Cabinas de Bano', 'ACABADOS', 592, 0),
('BARANDAS_BALCON', 'Barandas de Balcon', 'ACABADOS', 593, 0),
('PASAMANOS_CERRAJERIA', 'Pasamanos Tubulares y Cerrajeria', 'ACABADOS', 594, 0),
('PLANTA_ELECTRICA', 'Planta Electrica', 'INSTALACIONES', 713, 0),
('MALACATE', 'Malacate', 'EQUIPOS', 1045, 0),
('GRIFERIAS_INCRUSTACIONES', 'Griferias e Incrustaciones', 'ACABADOS', 606, 0),
('GEODREN', 'Geodren', 'CIMENTACION', 185, 0),
('ASEO', 'Aseo', 'ACABADOS', 620, 1),
('BOTADA_ESCOMBROS', 'Botada de Escombros', 'URBANISMO', 845, 1),
('AMENIDADES_CUBIERTA', 'Amenidades Especiales de Cubierta', 'ACABADOS', 621, 1)
ON DUPLICATE KEY UPDATE
    nombre = VALUES(nombre),
    categoria = VALUES(categoria),
    orden = VALUES(orden),
    siempre_revision = VALUES(siempre_revision);

UPDATE `general_pdc_familias`
SET `siempre_revision` = 0
WHERE `codigo` IN (
    'RED_ELECTRICA',
    'RED_GAS',
    'PINTURAS',
    'PLANTA_ELECTRICA'
);

UPDATE `general_pdc_familias`
SET `siempre_revision` = 1
WHERE `codigo` IN (
    'CAMPAMENTO',
    'RED_TELECOMUNICACIONES',
    'ASEO',
    'BOTADA_ESCOMBROS',
    'AMENIDADES_CUBIERTA'
);

UPDATE `general_pdc_family_contract_options` o
JOIN `general_pdc_familias` f ON f.id = o.familia_id
SET o.activa = 0
WHERE f.codigo IN (
    'SANITARIOS', 'GRIFERIAS_INCRUSTACIONES', 'PINTURAS', 'PLANTA_ELECTRICA',
    'PASAMANOS_CERRAJERIA', 'MALACATE', 'TORREGRUA', 'ASEO', 'BOTADA_ESCOMBROS',
    'CARPINTERIA_MADERA', 'PAISAJISMO', 'RED_HIDROSANITARIA', 'RED_GAS'
);

INSERT INTO `general_pdc_activity_rules`
    (`familia_id`, `patron_regex`, `modalidad_sugerida`, `confianza`, `prioridad`, `descripcion`, `activa`)
SELECT f.id, seed.patron_regex, seed.modalidad_sugerida, seed.confianza, seed.prioridad, seed.descripcion, 1
FROM (
    SELECT 'REVOQUE_HUMEDO' codigo, '/REVOQUE.*HUMEDO|PANETE.*HUMEDO|REVOQUE.*MORTERO|FRISO.*HUMEDO/u' patron_regex, 'Mano de Obra y Suministro por separado' modalidad_sugerida, 92 confianza, 160 prioridad, 'Da Porto: revoque humedo separado' descripcion
    UNION ALL SELECT 'REVOQUE_SECO', '/REVOQUE.*SECO|DRYWALL.*REVOQUE|MURO.*DRYWALL|PANEL.*YESO.*MURO/u', 'Todo costo', 92, 165, 'Da Porto: revoque seco separado'
    UNION ALL SELECT 'PLANTA_ELECTRICA', '/PLANTA.*ELECTRICA|GENERADOR.*ELECTRIC|TRANSFERENCIA.*ELECTRICA|EQUIPO.*RESPALDO.*ELECTRICO/u', 'Suministro e Instalación', 92, 180, 'Planta electrica separada de red electrica'
    UNION ALL SELECT 'CABINAS_BANO', '/CABINA.*BANO|CABINAS.*BANO|DIVISION.*BANO|PUERTA.*DUCHA|MAMPARA.*BANO/u', 'Suministro e Instalación', 92, 170, 'Da Porto: cabinas como actividad independiente'
    UNION ALL SELECT 'ESPEJOS', '/ESPEJO|ESPEJOS/u', 'Suministro e Instalación', 92, 170, 'Da Porto: espejos como actividad independiente'
    UNION ALL SELECT 'BARANDAS_BALCON', '/BARANDA.*BALCON|BARANDAS.*BALCON|BALCON.*BARANDA|BARANDA.*VIDRIO/u', 'Suministro e Instalación', 90, 165, 'Barandas de balcon asociadas a carpinteria metalica'
    UNION ALL SELECT 'PASAMANOS_CERRAJERIA', '/PASAMANOS|PASAMANOS.*TUBULAR|BARANDA.*ESCALERA|CERRAJERIA.*ZONA/u', 'Mano de Obra y Suministro por separado', 90, 165, 'Pasamanos escaleras con suministro y mano de obra'
    UNION ALL SELECT 'MALACATE', '/MALACATE|ELEVADOR.*OBRA|MONTACARGAS.*OBRA/u', 'Equipos', 90, 160, 'Malacate como equipo operativo'
    UNION ALL SELECT 'GEODREN', '/GEODREN|GEO.*DREN|DREN.*GEOCOMPUESTO|DRENAJE.*MURO/u', 'Mano de Obra y Suministro por separado', 90, 170, 'Da Porto: geodren separado'
    UNION ALL SELECT 'ASEO', '/ASEO.*APARTAMENTO|ASEO.*OBRA|ASEO.*FINAL|LIMPIEZA.*OBRA|LIMPIEZA.*APARTAMENTO/u', 'Mano de Obra', 88, 150, 'Aseo por defecto como mano de obra'
    UNION ALL SELECT 'BOTADA_ESCOMBROS', '/BOTADA.*ESCOMBRO|RETIRO.*ESCOMBRO|ESCOMBRO|DISPOSICION.*ESCOMBRO/u', 'Orden de Compra', 88, 155, 'Botada de escombros a necesidad'
    UNION ALL SELECT 'AMENIDADES_CUBIERTA', '/JACUZZI|HIDROMASAJE|ZONA.*DISFRUTE|BBQ|PISCINA|DECK.*CUBIERTA/u', 'Todo costo', 60, 150, 'Da Porto: amenidades manuales por diseno'
    UNION ALL SELECT 'GRIFERIAS_INCRUSTACIONES', '/GRIFERIA|GRIFERIAS|INCRUSTACION|INCRUSTACIONES|MEZCLADOR|DUCHA/u', 'Orden de Compra', 92, 175, 'Griferias e incrustaciones como orden de compra'
    UNION ALL SELECT 'SANITARIOS', '/APARATO.*SANITARIO|APARATOS.*SANITARIOS|SANITARIO|LAVAMANOS|INODORO|ORINAL/u', 'Mano de Obra y Suministro por separado', 92, 150, 'Aparatos sanitarios con suministro y mano de obra'
    UNION ALL SELECT 'CARPINTERIA_MADERA', '/CARPINTERIA.*MADERA|MADERA.*CARPINTERIA|CLOSET|MUEBLE.*COCINA|COCINA.*MADERA/u', 'Mano de Obra y Suministro por separado', 92, 150, 'Da Porto: carpinteria madera dividida'
    UNION ALL SELECT 'PINTURAS', '/PINTURA|PINTURAS|VINILO|ESMALTE|TEXTURA.*PINTURA|PINTURA.*FACHADA/u', 'Suministro e Instalación', 92, 145, 'Pinturas SI por eficiencia y control del material'
    UNION ALL SELECT 'PAISAJISMO', '/PAISAJISMO|JARDIN|JARDINERIA|ARBOL|ARBORIZACION|PLANTACION|ZONA.*VERDE|ENGRAMADO|ENGRAMADOS/u', 'Todo costo', 88, 145, 'Da Porto: paisajismo a todo costo'
    UNION ALL SELECT 'RED_HIDROSANITARIA', '/HIDROSANITARIA|HIDRAULICA|SANITARIA|AGUA.*POTABLE|ALCANTARILLADO|DESAGUE|RED.*AGUA|RED.*SANITARIA/u', 'Mano de Obra y Suministro por separado', 88, 145, 'Da Porto: hidrosanitaria por administracion'
    UNION ALL SELECT 'RED_GAS', '/RED.*GAS|GAS.*NATURAL|INSTALACION.*GAS|GASODUCTO|GAS.*DOMICILIARIO/u', 'Todo costo', 90, 145, 'Red de gas a todo costo'
    UNION ALL SELECT 'RED_CONTRAINCENDIO', '/RED.*CONTRA.*INCENDIO|CONTRA.*INCENDIO|SPRINKLER|ROCIADOR|TUBERIA.*INCENDIO|SISTEMA.*INCENDIO/u', 'Todo costo', 92, 145, 'Da Porto: contra incendio todo costo'
    UNION ALL SELECT 'DETECCION_INCENDIO', '/DETECCION.*INCENDIO|ALARMA.*INCENDIO|SENSOR.*HUMO|DETECTOR.*HUMO|PANEL.*INCENDIO/u', 'Todo costo', 94, 150, 'Da Porto: deteccion separada'
    UNION ALL SELECT 'EQUIPOS_INCENDIO', '/EXTINTOR|EXTINTORES|GABINETE.*INCENDIO|EQUIPO.*EXTINCION|MANGUERA.*INCENDIO/u', 'Todo costo', 92, 145, 'Da Porto: extincion separada'
) seed
JOIN `general_pdc_familias` f ON f.codigo = seed.codigo
ON DUPLICATE KEY UPDATE
    modalidad_sugerida = VALUES(modalidad_sugerida),
    confianza = VALUES(confianza),
    prioridad = VALUES(prioridad),
    descripcion = VALUES(descripcion),
    activa = 1;

INSERT INTO `general_pdc_family_contract_options`
    (`familia_id`, `tipo_contrato`, `tipo_paquete`, `dias_elaboracion`, `dias_entrega`, `dias_recibo`, `dias_cuadros`, `dias_legalizacion`, `dias_fabricacion`, `dias_insumos`, `notas`, `activa`)
SELECT f.id, seed.tipo_contrato, seed.tipo_paquete, seed.dias_elaboracion, seed.dias_entrega, seed.dias_recibo,
       seed.dias_cuadros, seed.dias_legalizacion, seed.dias_fabricacion, seed.dias_insumos, seed.notas, 1
FROM (
    SELECT 'REVOQUE_HUMEDO' codigo, 1 tipo_contrato, 'Mano de Obra y Suministro por separado' tipo_paquete, 8 dias_elaboracion, 7 dias_entrega, 5 dias_recibo, 10 dias_cuadros, 0 dias_legalizacion, 0 dias_fabricacion, 0 dias_insumos, 'Da Porto: revoque humedo MO+S' notas
    UNION ALL SELECT 'REVOQUE_SECO', 2, 'Todo costo', 8, 15, 5, 10, 20, 15, 0, 'Da Porto: revoque seco todo costo'
    UNION ALL SELECT 'ESPEJOS', 2, 'Suministro e Instalación', 8, 7, 5, 10, 15, 10, 0, 'Da Porto: espejos independientes'
    UNION ALL SELECT 'CABINAS_BANO', 2, 'Suministro e Instalación', 8, 15, 5, 10, 20, 15, 0, 'Da Porto: cabinas independientes'
    UNION ALL SELECT 'BARANDAS_BALCON', 2, 'Suministro e Instalación', 8, 20, 5, 15, 25, 20, 0, 'Barandas balcon por carpinteria metalica'
    UNION ALL SELECT 'PASAMANOS_CERRAJERIA', 1, 'Mano de Obra y Suministro por separado', 8, 15, 5, 15, 20, 15, 0, 'Pasamanos escaleras S+MO'
    UNION ALL SELECT 'PLANTA_ELECTRICA', 2, 'Suministro e Instalación', 15, 30, 10, 20, 45, 45, 0, 'Planta electrica SI por defecto'
    UNION ALL SELECT 'MALACATE', 6, 'Equipos', 8, 10, 5, 10, 20, 0, 0, 'Equipo operativo'
    UNION ALL SELECT 'GEODREN', 1, 'Mano de Obra y Suministro por separado', 8, 7, 5, 10, 0, 0, 0, 'Da Porto: geodren MO+S'
    UNION ALL SELECT 'ASEO', 4, 'Mano de Obra', 8, 7, 5, 10, 0, 0, 0, 'Da Porto: aseo requiere decision'
    UNION ALL SELECT 'BOTADA_ESCOMBROS', 5, 'Orden de Compra', 5, 5, 1, 5, 0, 0, 0, 'Da Porto: botada por evento/origen'
    UNION ALL SELECT 'AMENIDADES_CUBIERTA', 2, 'Todo costo', 10, 20, 10, 20, 30, 30, 0, 'Da Porto: amenidades por diseno'
    UNION ALL SELECT 'GRIFERIAS_INCRUSTACIONES', 5, 'Orden de Compra', 8, 7, 5, 10, 0, 0, 0, 'Griferias e incrustaciones por orden de compra'
    UNION ALL SELECT 'SANITARIOS', 1, 'Mano de Obra y Suministro por separado', 8, 7, 5, 10, 0, 0, 0, 'Aparatos sanitarios S+MO'
    UNION ALL SELECT 'CARPINTERIA_MADERA', 1, 'Mano de Obra y Suministro por separado', 10, 30, 10, 20, 60, 45, 0, 'Da Porto: fabricacion/suministro + instalacion'
    UNION ALL SELECT 'PINTURAS', 2, 'Suministro e Instalación', 8, 10, 5, 10, 20, 0, 0, 'Pinturas SI por defecto'
    UNION ALL SELECT 'CIELOS_RASOS', 2, 'Todo costo', 10, 20, 40, 30, 60, 30, 0, 'Da Porto: cielos todo costo'
    UNION ALL SELECT 'IMPERMEABILIZACIONES', 2, 'Todo costo', 10, 15, 15, 15, 15, 7, 0, 'Da Porto: impermeabilizacion todo costo por garantia'
    UNION ALL SELECT 'PAISAJISMO', 2, 'Todo costo', 8, 15, 5, 10, 20, 10, 0, 'Da Porto: paisajismo todo costo'
    UNION ALL SELECT 'RED_HIDROSANITARIA', 1, 'Mano de Obra y Suministro por separado', 8, 10, 5, 10, 10, 0, 0, 'Da Porto: administracion con suministro propio'
    UNION ALL SELECT 'RED_GAS', 2, 'Todo costo', 30, 7, 5, 10, 0, 0, 0, 'Red de gas a todo costo'
    UNION ALL SELECT 'RED_CONTRAINCENDIO', 2, 'Todo costo', 8, 15, 5, 10, 20, 15, 0, 'Da Porto: piping RCI todo costo'
    UNION ALL SELECT 'DETECCION_INCENDIO', 2, 'Todo costo', 8, 15, 5, 10, 20, 15, 0, 'Da Porto: deteccion todo costo'
    UNION ALL SELECT 'EQUIPOS_INCENDIO', 2, 'Todo costo', 8, 15, 5, 10, 20, 15, 0, 'Da Porto: extincion todo costo'
    UNION ALL SELECT 'TORREGRUA', 6, 'Equipos', 8, 10, 5, 10, 20, 0, 0, 'Equipo operativo'
) seed
JOIN `general_pdc_familias` f ON f.codigo = seed.codigo
ON DUPLICATE KEY UPDATE
    dias_elaboracion = VALUES(dias_elaboracion),
    dias_entrega = VALUES(dias_entrega),
    dias_recibo = VALUES(dias_recibo),
    dias_cuadros = VALUES(dias_cuadros),
    dias_legalizacion = VALUES(dias_legalizacion),
    dias_fabricacion = VALUES(dias_fabricacion),
    dias_insumos = VALUES(dias_insumos),
    notas = VALUES(notas),
    activa = 1;

INSERT INTO `general_pdc_family_contract_option_items`
    (`option_id`, `tipo_contrato`, `tipo_paquete`, `paquete_nombre`, `dias_proceso_id`, `orden`)
SELECT opt.id, seed.item_tipo_contrato, seed.item_tipo_paquete, seed.paquete_nombre, NULL, seed.orden
FROM (
    SELECT 'REVOQUE_HUMEDO' codigo, 'Mano de Obra y Suministro por separado' option_tipo_paquete, 3 item_tipo_contrato, 'Suministro' item_tipo_paquete, 'MORTERO REVOQUE HUMEDO' paquete_nombre, 1 orden
    UNION ALL SELECT 'REVOQUE_HUMEDO', 'Mano de Obra y Suministro por separado', 4, 'Mano de Obra', 'REVOQUE HUMEDO', 2
    UNION ALL SELECT 'REVOQUE_SECO', 'Todo costo', 2, 'Todo costo', 'REVOQUE SECO', 1
    UNION ALL SELECT 'ESPEJOS', 'Suministro e Instalación', 2, 'Suministro e Instalación', 'ESPEJOS', 1
    UNION ALL SELECT 'CABINAS_BANO', 'Suministro e Instalación', 2, 'Suministro e Instalación', 'CABINAS DE BANO', 1
    UNION ALL SELECT 'BARANDAS_BALCON', 'Suministro e Instalación', 2, 'Suministro e Instalación', 'CARPINTERIA METALICA - BARANDAS DE BALCON', 1
    UNION ALL SELECT 'PASAMANOS_CERRAJERIA', 'Mano de Obra y Suministro por separado', 3, 'Suministro', 'PASAMANOS ESCALERAS', 1
    UNION ALL SELECT 'PASAMANOS_CERRAJERIA', 'Mano de Obra y Suministro por separado', 4, 'Mano de Obra', 'INSTALACION PASAMANOS ESCALERAS', 2
    UNION ALL SELECT 'PLANTA_ELECTRICA', 'Suministro e Instalación', 2, 'Suministro e Instalación', 'PLANTA ELECTRICA', 1
    UNION ALL SELECT 'MALACATE', 'Equipos', 6, 'Equipos', 'MALACATE', 1
    UNION ALL SELECT 'GEODREN', 'Mano de Obra y Suministro por separado', 3, 'Suministro', 'GEODREN', 1
    UNION ALL SELECT 'GEODREN', 'Mano de Obra y Suministro por separado', 4, 'Mano de Obra', 'MANO DE OBRA GEODREN', 2
    UNION ALL SELECT 'ASEO', 'Mano de Obra', 4, 'Mano de Obra', 'ASEO APARTAMENTOS Y OBRA', 1
    UNION ALL SELECT 'BOTADA_ESCOMBROS', 'Orden de Compra', 5, 'Orden de Compra', 'BOTADA DE ESCOMBROS', 1
    UNION ALL SELECT 'AMENIDADES_CUBIERTA', 'Todo costo', 2, 'Todo costo', 'AMENIDADES CUBIERTA', 1
    UNION ALL SELECT 'GRIFERIAS_INCRUSTACIONES', 'Orden de Compra', 5, 'Orden de Compra', 'GRIFERIAS E INCRUSTACIONES', 1
    UNION ALL SELECT 'SANITARIOS', 'Mano de Obra y Suministro por separado', 3, 'Suministro', 'APARATOS SANITARIOS', 1
    UNION ALL SELECT 'SANITARIOS', 'Mano de Obra y Suministro por separado', 4, 'Mano de Obra', 'INSTALACION APARATOS SANITARIOS', 2
    UNION ALL SELECT 'CARPINTERIA_MADERA', 'Mano de Obra y Suministro por separado', 3, 'Suministro', 'CARPINTERIA MADERA - FABRICACION Y SUMINISTRO', 1
    UNION ALL SELECT 'CARPINTERIA_MADERA', 'Mano de Obra y Suministro por separado', 4, 'Mano de Obra', 'CARPINTERIA MADERA - INSTALACION', 2
    UNION ALL SELECT 'PINTURAS', 'Suministro e Instalación', 2, 'Suministro e Instalación', 'PINTURAS', 1
    UNION ALL SELECT 'CIELOS_RASOS', 'Todo costo', 2, 'Todo costo', 'CIELOS RASOS', 1
    UNION ALL SELECT 'IMPERMEABILIZACIONES', 'Todo costo', 2, 'Todo costo', 'IMPERMEABILIZACIONES', 1
    UNION ALL SELECT 'PAISAJISMO', 'Todo costo', 2, 'Todo costo', 'PAISAJISMO Y ENGRAMADOS', 1
    UNION ALL SELECT 'RED_HIDROSANITARIA', 'Mano de Obra y Suministro por separado', 3, 'Suministro', 'MATERIALES HIDROSANITARIOS', 1
    UNION ALL SELECT 'RED_HIDROSANITARIA', 'Mano de Obra y Suministro por separado', 4, 'Mano de Obra', 'MANO DE OBRA HIDROSANITARIA', 2
    UNION ALL SELECT 'RED_GAS', 'Todo costo', 2, 'Todo costo', 'RED DE GAS', 1
    UNION ALL SELECT 'RED_CONTRAINCENDIO', 'Todo costo', 2, 'Todo costo', 'RED CONTRA INCENDIO', 1
    UNION ALL SELECT 'DETECCION_INCENDIO', 'Todo costo', 2, 'Todo costo', 'DETECCION DE INCENDIO', 1
    UNION ALL SELECT 'EQUIPOS_INCENDIO', 'Todo costo', 2, 'Todo costo', 'EQUIPOS DE EXTINCION', 1
    UNION ALL SELECT 'TORREGRUA', 'Equipos', 6, 'Equipos', 'TORRE GRUA', 1
    UNION ALL SELECT 'ESTRUCTURA_CONCRETO', 'Mano de Obra y Suministro por separado', 5, 'Orden de Compra', 'CONCRETO', 1
    UNION ALL SELECT 'ESTRUCTURA_ACERO', 'Mano de Obra y Suministro por separado', 5, 'Orden de Compra', 'ACERO DE REFUERZO', 1
) seed
JOIN `general_pdc_familias` f ON f.codigo = seed.codigo
JOIN `general_pdc_family_contract_options` opt
  ON opt.familia_id = f.id
 AND opt.tipo_paquete = seed.option_tipo_paquete
ON DUPLICATE KEY UPDATE
    tipo_contrato = VALUES(tipo_contrato),
    tipo_paquete = VALUES(tipo_paquete),
    paquete_nombre = VALUES(paquete_nombre),
    dias_proceso_id = VALUES(dias_proceso_id),
    orden = VALUES(orden);

DELETE legacy_item
FROM `general_pdc_family_contract_option_items` legacy_item
JOIN `general_pdc_family_contract_options` o ON o.id = legacy_item.option_id
JOIN `general_pdc_familias` f ON f.id = o.familia_id
JOIN `general_pdc_family_contract_option_items` canonical_item
  ON canonical_item.option_id = legacy_item.option_id
 AND canonical_item.paquete_nombre = legacy_item.paquete_nombre
 AND canonical_item.tipo_paquete = 'Orden de Compra'
WHERE f.codigo IN ('ESTRUCTURA_CONCRETO', 'ESTRUCTURA_ACERO')
  AND legacy_item.paquete_nombre IN ('CONCRETO', 'ACERO DE REFUERZO')
  AND legacy_item.tipo_paquete <> 'Orden de Compra';
