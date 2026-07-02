-- Aplica decisiones humanas del checklist de familias.
-- Objetivo: /listado-actividades/ solo crea familias operativas; contratos/PDC
-- reciben compras, equipos, insumos y paquetes especializados.

INSERT INTO `general_pdc_familias`
  (`codigo`, `nombre`, `categoria`, `orden`, `siempre_revision`, `activa`)
VALUES
  ('SEGURIDAD_CONTROL', 'Seguridad y Control', 'INSTALACIONES', 472, 0, 1),
  ('DOTACION_ZONAS_COMUNES', 'Dotación Zonas Comunes', 'DOTACION', 622, 0, 1)
ON DUPLICATE KEY UPDATE
  `nombre` = VALUES(`nombre`),
  `categoria` = VALUES(`categoria`),
  `siempre_revision` = VALUES(`siempre_revision`),
  `activa` = 1;

UPDATE `general_pdc_familias`
SET `activa` = 1,
    `siempre_revision` = 0,
    `nombre` = 'Aseo'
WHERE `codigo` = 'ASEO';

UPDATE `general_pdc_familias`
SET `activa` = 0,
    `siempre_revision` = 0
WHERE `codigo` IN (
  'AMENIDADES_CUBIERTA',
  'BOMBA_CONCRETO',
  'BOTADA_ESCOMBROS',
  'CAMPAMENTO',
  'EXCAVADORA',
  'MALACATE',
  'MONTACARGAS',
  'MOTORGRUA',
  'PLANTA_CONCRETO',
  'TORREGRUA',
  'VOLQUETA'
);

UPDATE `general_pdc_activity_rules` r
JOIN `general_pdc_familias` f ON f.id = r.familia_id
SET r.activa = 0
WHERE f.codigo IN (
  'BOMBA_CONCRETO',
  'BOTADA_ESCOMBROS',
  'CAMPAMENTO',
  'EXCAVADORA',
  'MALACATE',
  'MONTACARGAS',
  'MOTORGRUA',
  'PLANTA_CONCRETO',
  'TORREGRUA',
  'VOLQUETA'
);

UPDATE `general_pdc_activity_rules` r
JOIN `general_pdc_familias` f ON f.id = r.familia_id
JOIN `general_pdc_familias` target ON target.codigo = 'DOTACION_ZONAS_COMUNES'
SET r.familia_id = target.id,
    r.modalidad_sugerida = 'Orden de Compra',
    r.confianza = GREATEST(r.confianza, 88),
    r.descripcion = 'Amenidades de cubierta se tratan como compra especializada de Dotación Zonas Comunes.',
    r.activa = 1
WHERE f.codigo = 'AMENIDADES_CUBIERTA';

UPDATE `general_pdc_activity_rules` r
JOIN `general_pdc_familias` f ON f.id = r.familia_id
SET r.patron_regex = CASE
      WHEN r.patron_regex LIKE '%RACK%' OR r.patron_regex LIKE '%PUNTO%'
        THEN '/CABLEADO.*ESTRUCTURADO|RACK.*COMUNICACIONES|PUNTO.*DATOS|RED.*DATOS/u'
      ELSE '/TELECOMUNICACION|TELECOMUNICACIONES|VOZ.*DATOS|DATOS.*VOZ|FIBRA.*OPTICA|CABLEADO.*ESTRUCTURADO|RED.*DATOS/u'
    END,
    r.descripcion = CASE
      WHEN r.patron_regex LIKE '%RACK%' OR r.patron_regex LIKE '%PUNTO%'
        THEN 'Telecomunicaciones: cableado estructurado, racks y puntos de datos.'
      ELSE 'Telecomunicaciones sin CCTV ni control de acceso.'
    END
WHERE f.codigo = 'RED_TELECOMUNICACIONES'
  AND r.patron_regex LIKE '%CCTV%';

UPDATE `general_pdc_familias`
SET `siempre_revision` = 0,
    `activa` = 1
WHERE `codigo` = 'RED_TELECOMUNICACIONES';

INSERT INTO `general_pdc_activity_rules`
  (`familia_id`, `patron_regex`, `modalidad_sugerida`, `confianza`, `prioridad`, `descripcion`, `activa`)
SELECT f.id,
       '/SEGURIDAD.*CONTROL|CONTROL.*SEGURIDAD|CCTV|CAMARA|CÁMARA|CONTROL.*ACCESO|TORNIQUETE|VERIPASS|DOMO|PTZ|SISTEMA.*SEGURIDAD/u',
       'Suministro e Instalación',
       92,
       226,
       'Seguridad y control: CCTV, camaras, control de acceso y equipos asociados.',
       1
FROM `general_pdc_familias` f
WHERE f.codigo = 'SEGURIDAD_CONTROL'
ON DUPLICATE KEY UPDATE
  `modalidad_sugerida` = VALUES(`modalidad_sugerida`),
  `confianza` = VALUES(`confianza`),
  `prioridad` = VALUES(`prioridad`),
  `descripcion` = VALUES(`descripcion`),
  `activa` = 1;

INSERT INTO `general_pdc_family_aliases`
  (`alias_nombre`, `alias_normalizado`, `familia_id`, `alias_family_id`, `fuente`, `notas`, `activa`)
SELECT 'Amenidades Especiales de Cubierta',
       'AMENIDADES ESPECIALES DE CUBIERTA',
       canonical.id,
       legacy.id,
       'decision_humana_20260702',
       'Compra especializada agrupada bajo Dotación Zonas Comunes.',
       1
FROM `general_pdc_familias` canonical
LEFT JOIN `general_pdc_familias` legacy ON legacy.codigo = 'AMENIDADES_CUBIERTA'
WHERE canonical.codigo = 'DOTACION_ZONAS_COMUNES'
ON DUPLICATE KEY UPDATE
  `familia_id` = VALUES(`familia_id`),
  `alias_family_id` = VALUES(`alias_family_id`),
  `fuente` = VALUES(`fuente`),
  `notas` = VALUES(`notas`),
  `activa` = 1;

INSERT INTO `general_pdc_contractual_elements`
  (`nombre`, `nombre_normalizado`, `tipo_paquete`, `paquete_nombre`, `familia_id`, `fuente`, `notas`, `activa`)
SELECT seed.nombre,
       seed.nombre_normalizado,
       seed.tipo_paquete,
       seed.paquete_nombre,
       f.id,
       'decision_humana_20260702',
       seed.notas,
       1
FROM (
  SELECT 'Bomba de Concreto' nombre, 'BOMBA DE CONCRETO' nombre_normalizado, 'Equipos' tipo_paquete, 'BOMBA DE CONCRETO' paquete_nombre, NULL familia_codigo, 'Equipo/recurso para Contratos.' notas
  UNION ALL SELECT 'Excavadora', 'EXCAVADORA', 'Equipos', 'EXCAVADORA', NULL, 'Equipo/recurso para Contratos.'
  UNION ALL SELECT 'Malacate', 'MALACATE', 'Equipos', 'MALACATE', NULL, 'Equipo/recurso para Contratos.'
  UNION ALL SELECT 'Montacargas', 'MONTACARGAS', 'Equipos', 'MONTACARGAS', NULL, 'Equipo/recurso para Contratos.'
  UNION ALL SELECT 'Motorgrua', 'MOTORGRUA', 'Equipos', 'MOTORGRUA', NULL, 'Equipo/recurso para Contratos.'
  UNION ALL SELECT 'Planta de Concreto', 'PLANTA DE CONCRETO', 'Equipos', 'PLANTA DE CONCRETO', NULL, 'Equipo/recurso para Contratos.'
  UNION ALL SELECT 'Torregrua', 'TORREGRUA', 'Equipos', 'TORREGRUA', NULL, 'Equipo/recurso para Contratos.'
  UNION ALL SELECT 'Volqueta', 'VOLQUETA', 'Equipos', 'VOLQUETA', NULL, 'Equipo/recurso para Contratos.'
  UNION ALL SELECT 'Botada de Escombros', 'BOTADA DE ESCOMBROS', 'Orden de Compra', 'RETIRO Y DISPOSICION DE ESCOMBROS', NULL, 'Contrato/servicio, no familia operativa.'
  UNION ALL SELECT 'Campamento de Obra', 'CAMPAMENTO DE OBRA', 'Suministro', 'CAMPAMENTO DE OBRA', 'PRELIMINARES', 'Contrato de Preliminares.'
  UNION ALL SELECT 'Amenidades Especiales de Cubierta', 'AMENIDADES ESPECIALES DE CUBIERTA', 'Orden de Compra', 'AMENIDADES ESPECIALES DE CUBIERTA', 'DOTACION_ZONAS_COMUNES', 'Compra especializada de Dotación Zonas Comunes.'
) seed
LEFT JOIN `general_pdc_familias` f ON f.codigo = seed.familia_codigo
ON DUPLICATE KEY UPDATE
  `tipo_paquete` = VALUES(`tipo_paquete`),
  `paquete_nombre` = VALUES(`paquete_nombre`),
  `familia_id` = VALUES(`familia_id`),
  `fuente` = VALUES(`fuente`),
  `notas` = VALUES(`notas`),
  `activa` = 1;

INSERT INTO `general_pdc_family_contract_options`
  (`familia_id`, `tipo_contrato`, `tipo_paquete`, `dias_elaboracion`, `dias_entrega`, `dias_recibo`, `dias_cuadros`, `dias_legalizacion`, `dias_fabricacion`, `dias_insumos`, `notas`, `activa`)
SELECT f.id, 2, 'Suministro e Instalación', 8, 10, 5, 10, 20, 10, 0, 'Seguridad y Control SI.', 1
FROM `general_pdc_familias` f
WHERE f.codigo = 'SEGURIDAD_CONTROL'
ON DUPLICATE KEY UPDATE
  `tipo_contrato` = VALUES(`tipo_contrato`),
  `tipo_paquete` = VALUES(`tipo_paquete`),
  `notas` = VALUES(`notas`),
  `activa` = 1;

INSERT INTO `general_pdc_family_contract_options`
  (`familia_id`, `tipo_contrato`, `tipo_paquete`, `dias_elaboracion`, `dias_entrega`, `dias_recibo`, `dias_cuadros`, `dias_legalizacion`, `dias_fabricacion`, `dias_insumos`, `notas`, `activa`)
SELECT f.id, 5, 'Orden de Compra', 8, 15, 5, 10, 10, 20, 20, 'Dotación Zonas Comunes como compra especializada.', 1
FROM `general_pdc_familias` f
WHERE f.codigo = 'DOTACION_ZONAS_COMUNES'
ON DUPLICATE KEY UPDATE
  `tipo_contrato` = VALUES(`tipo_contrato`),
  `tipo_paquete` = VALUES(`tipo_paquete`),
  `notas` = VALUES(`notas`),
  `activa` = 1;

INSERT INTO `general_pdc_family_contract_option_items`
  (`option_id`, `tipo_contrato`, `tipo_paquete`, `paquete_nombre`, `dias_proceso_id`, `orden`)
SELECT o.id, o.tipo_contrato, o.tipo_paquete, item.paquete_nombre, NULL, item.orden
FROM `general_pdc_family_contract_options` o
JOIN `general_pdc_familias` f ON f.id = o.familia_id
JOIN (
  SELECT 'SEGURIDAD_CONTROL' codigo, 'SEGURIDAD Y CONTROL' paquete_nombre, 1 orden
  UNION ALL SELECT 'DOTACION_ZONAS_COMUNES', 'DOTACION ZONAS COMUNES', 1
) item ON item.codigo = f.codigo
ON DUPLICATE KEY UPDATE
  `tipo_contrato` = VALUES(`tipo_contrato`),
  `tipo_paquete` = VALUES(`tipo_paquete`),
  `orden` = VALUES(`orden`);
