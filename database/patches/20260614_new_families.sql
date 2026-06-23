-- Patch: 18 nuevas familias constructivas + rename SANITARIOS + 44 regex rules + contract options
-- Fecha: 2026-06-14
-- Objetivo: agregar 18 familias nuevas (6 ACABADOS + 1 PRELIMINARES + 2 CIMENTACION + 1 EQUIPOS + 1 URBANISMO + 4 INSTALACIONES + rename SANITARIOS→APARATOS_SANITARIOS),
--           reglas regex contra texto normalizado, opciones de contrato e items/paquetes.

SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================================
-- 1. Nuevas familias constructivas (18 total)
-- ============================================================================

INSERT IGNORE INTO `general_pdc_familias` (`codigo`, `nombre`, `categoria`, `orden`, `siempre_revision`) VALUES
-- ACABADOS (orden 62-69)
('ASEO', 'Aseo y Entrega', 'ACABADOS', 62, 0),
('EQUIPOS_COCINA', 'Equipos de Cocina', 'ACABADOS', 63, 0),
('LAVAPLATOS', 'Lavaplatos', 'ACABADOS', 64, 0),
('LAVADERO', 'Lavadero Zona de Ropas', 'ACABADOS', 65, 0),
('ACCESORIOS_SANITARIOS', 'Accesorios Sanitarios', 'ACABADOS', 66, 0),
('PASAMANOS', 'Pasamanos y Barandas', 'ACABADOS', 67, 0),
('MUEBLES', 'Muebles', 'ACABADOS', 68, 0),
('RESANES', 'Resanes', 'ACABADOS', 69, 0),

-- PRELIMINARES (orden 9)
('DEMOLICIONES', 'Demoliciones y Desmontes', 'PRELIMINARES', 9, 0),

-- CIMENTACION (orden 19, 39)
('APUNTALAMIENTO', 'Apuntalamiento', 'CIMENTACION', 19, 0),
('INFRAESTRUCTURA_DRENES', 'Infraestructura y Drenes', 'CIMENTACION', 39, 0),

-- EQUIPOS (orden 108)
('EQUIPOS_ESPECIALES', 'Equipos Especiales', 'EQUIPOS', 108, 0),

-- URBANISMO (orden 85)
('SENALETICA', 'Senal etica', 'URBANISMO', 85, 0),

-- INSTALACIONES (orden 86-89)
('CCTV_SEGURIDAD', 'CCTV y Seguridad Electronica', 'INSTALACIONES', 86, 0),
('SONIDO_VIDEO', 'Sistema de Sonido y Video', 'INSTALACIONES', 87, 0),
('SISTEMA_DATOS', 'Sistema de Datos', 'INSTALACIONES', 88, 0),
('AUTOMATIZACION_BMS', 'Automatizacion y BMS', 'INSTALACIONES', 89, 0);

-- ============================================================================
-- 2. Renombrar SANITARIOS → APARATOS_SANITARIOS (UPDATE, NO DELETE)
-- ============================================================================

UPDATE `general_pdc_familias`
SET `codigo` = 'APARATOS_SANITARIOS', `nombre` = 'Aparatos Sanitarios'
WHERE `codigo` = 'SANITARIOS';

-- ============================================================================
-- 3. Reglas regex para nuevas familias (~44 reglas)
-- ============================================================================

-- 3a. Reglas primarias (1 regla por familia nueva)
INSERT IGNORE INTO `general_pdc_activity_rules` (`familia_id`, `patron_regex`, `modalidad_sugerida`, `confianza`, `prioridad`, `descripcion`)
SELECT f.id, seed.patron_regex, seed.modalidad_sugerida, seed.confianza, seed.prioridad, seed.descripcion
FROM (
    -- ACABADOS
    SELECT 'ASEO' codigo, '/ASEO|LIMPIEZA.*FINAL|ENTREGA.*APARTAMENTO|ENTREGA.*APTOS|ASEO.*GENERAL|LIMPIEZA.*OBRA/u' patron_regex, 'Mano de Obra y Suministro por separado' modalidad_sugerida, 85 confianza, 90 prioridad, 'Aseo y entrega' descripcion
    UNION ALL SELECT 'EQUIPOS_COCINA', '/CAMPANA.*EXTRACTORA|CAMPANA.*COCINA|CUBIERTA.*COCINA|ESTUFA|EQUIPO.*COCINA/u', 'Suministro e Instalación', 90, 110, 'Equipos de cocina'
    UNION ALL SELECT 'LAVAPLATOS', '/LAVAPLATOS|LAVA.*PLATOS|LAVAVAJILLAS/u', 'Suministro e Instalación', 92, 115, 'Lavaplatos'
    UNION ALL SELECT 'LAVADERO', '/LAVADERO|LAVANDERO|LAVADERO.*GRANITO|LAVADERO.*MARMOL|LAVADERO.*SINTETICO|ZONA.*ROPA|CUARTO.*LAVADO/u', 'Suministro e Instalación', 90, 110, 'Lavadero zona de ropas'
    UNION ALL SELECT 'ACCESORIOS_SANITARIOS', '/ACCESORIO.*BANO|ACCESORIO.*COCINA|ACCESORIO.*ROPA|TOALLERO|JABONERA|PAPELERA|BARRA.*AGARRE|PORTA.*PAPEL|PORTA.*TOALLA|GANCHERA|COLGADOR|ACCESORIO.*SANITARIO/u', 'Suministro e Instalación', 88, 100, 'Accesorios sanitarios'
    UNION ALL SELECT 'APARATOS_SANITARIOS', '/APARATO.*SANITARIO|APARATOS.*SANITARIOS|SANITARIO|LAVAMANOS|INODORO|GRIFERIA|DUCHA/u', 'Suministro e Instalación', 92, 110, 'Aparatos sanitarios'
    UNION ALL SELECT 'PASAMANOS', '/PASAMANOS|TALON.*PASAMANOS|BARANDA|BARRANDA|RIELES/u', 'Suministro e Instalación', 88, 100, 'Pasamanos y barandas'
    UNION ALL SELECT 'MUEBLES', '/MUEBLE|MUEBLES|FABRICACION.*MUEBLES|SUMINISTRO.*MUEBLES/u', 'Suministro e Instalación', 88, 100, 'Muebles'
    UNION ALL SELECT 'RESANES', '/RESAN|RESANES|RESANE.*PUNTO.*FIJO/u', 'Mano de Obra y Suministro por separado', 85, 90, 'Resanes'

    -- PRELIMINARES
    UNION ALL SELECT 'DEMOLICIONES', '/DEMOLICION|DESMONTE|DESMONTES/u', 'Mano de Obra y Suministro por separado', 88, 100, 'Demoliciones y desmontes'

    -- CIMENTACION
    UNION ALL SELECT 'APUNTALAMIENTO', '/PUNTAL|APUNTALAMIENTO|APUNTALAR/u', 'Suministro e Instalación', 88, 110, 'Apuntalamiento'
    UNION ALL SELECT 'INFRAESTRUCTURA_DRENES', '/CARCAMO|COLCHON.*DRENANTE|ROCA.*HINCADA|SUBRASANTE|GEOTEXTIL/u', 'Mano de Obra y Suministro por separado', 88, 100, 'Infraestructura y drenes'

    -- EQUIPOS
    UNION ALL SELECT 'EQUIPOS_ESPECIALES', '/EQUIPO.*ESPECIAL|EQUIPOS.*ESPECIAL|EQUIPOS.*RX|BHS|BANDA.*EQUIPAJE/u', 'Suministro e Instalación', 85, 90, 'Equipos especiales'

    -- URBANISMO
    UNION ALL SELECT 'SENALETICA', '/SENALETICA|SENAL.*HORIZONTAL/u', 'Suministro e Instalación', 88, 100, 'Senal etica'

    -- INSTALACIONES
    UNION ALL SELECT 'CCTV_SEGURIDAD', '/CCTV|CIRCUITO.*CERRADO.*TV|CONTROL.*ACCESO|INTRUSION|SEGURIDAD.*CONTROL|VIGILANCIA.*ELECTRONICA/u', 'Suministro e Instalación', 88, 110, 'CCTV y seguridad electronica'
    UNION ALL SELECT 'SONIDO_VIDEO', '/SONIDO|VIDEO|SISTEMA.*SONIDO/u', 'Suministro e Instalación', 88, 110, 'Sistema de sonido y video'
    UNION ALL SELECT 'SISTEMA_DATOS', '/SISTEMA.*DATOS|DUCTERIA|CUARTO.*DATOS/u', 'Suministro e Instalación', 85, 100, 'Sistema de datos'
    UNION ALL SELECT 'AUTOMATIZACION_BMS', '/AUTOMATIZACION|BMS|INTEGRACION.*CONTROL|MONITOREO.*CONTROL/u', 'Suministro e Instalación', 85, 100, 'Automatizacion y BMS'
) seed
INNER JOIN `general_pdc_familias` f ON f.codigo = seed.codigo;

-- 3b. Reglas complementarias (sinonimos, variantes historicas)
INSERT IGNORE INTO `general_pdc_activity_rules` (`familia_id`, `patron_regex`, `modalidad_sugerida`, `confianza`, `prioridad`, `descripcion`)
SELECT f.id, seed.patron_regex, seed.modalidad_sugerida, seed.confianza, seed.prioridad, seed.descripcion
FROM (
    -- ACABADOS complementarias
    SELECT 'ASEO' codigo, '/LIMPIEZA.*GENERAL|ASEO.*FINAL|ENTREGA.*FINAL/u' patron_regex, 'Mano de Obra y Suministro por separado' modalidad_sugerida, 82 confianza, 85 prioridad, 'Aseo sinonimos' descripcion
    UNION ALL SELECT 'EQUIPOS_COCINA', '/CAMPANA|HORNO.*EMBUTIR|COCINA.*INTEGRAL/u', 'Suministro e Instalación', 85, 100, 'Equipos cocina sinonimos'
    UNION ALL SELECT 'LAVAPLATOS', '/LAVAPLATOS.*INTEGRAL|FREGADERO/u', 'Suministro e Instalación', 88, 105, 'Lavaplatos sinonimos'
    UNION ALL SELECT 'LAVADERO', '/LAVADERO.*CONCRETO|TANQUE.*LAVAR|PILA.*LAVAR/u', 'Suministro e Instalación', 85, 100, 'Lavadero sinonimos'
    UNION ALL SELECT 'ACCESORIOS_SANITARIOS', '/ESPEJO.*BANO|REPISA.*BANO|PERCHA|GANCHOS.*BANO/u', 'Suministro e Instalación', 85, 95, 'Accesorios sinonimos'
    UNION ALL SELECT 'APARATOS_SANITARIOS', '/SANITARIO.*INTEGRAL|TANQUE.*SANITARIO|VALVULA.*DESCARGA|TAPA.*INODORO/u', 'Suministro e Instalación', 88, 105, 'Aparatos sanitarios sinonimos'
    UNION ALL SELECT 'PASAMANOS', '/PASAMANOS.*METAL|PASAMANOS.*MADERA|BARANDAL|BARANDA.*PROTECCION/u', 'Suministro e Instalación', 85, 95, 'Pasamanos sinonimos'
    UNION ALL SELECT 'MUEBLES', '/MUEBLE.*BAÑO|MUEBLE.*COCINA|MUEBLE.*INTEGRADO|ISLA.*COCINA/u', 'Suministro e Instalación', 85, 95, 'Muebles sinonimos'
    UNION ALL SELECT 'RESANES', '/RESANE.*MURO|RESANE.*LOSA|RESANAMIENTO|REPARACION.*SUPERFICIAL/u', 'Mano de Obra y Suministro por separado', 82, 85, 'Resanes sinonimos'

    -- PRELIMINARES complementarias
    UNION ALL SELECT 'DEMOLICIONES', '/DEMOLICION.*PARCIAL|DEMOLICION.*TOTAL|DESISTALACION|RETIRO.*ESTRUCTURA/u', 'Mano de Obra y Suministro por separado', 85, 95, 'Demoliciones sinonimos'

    -- CIMENTACION complementarias
    UNION ALL SELECT 'APUNTALAMIENTO', '/PUNTAL.*TEMPORAL|APUNTALAMIENTO.*ESTRUCTURAL|APEO|APEO.*MURO/u', 'Suministro e Instalación', 85, 105, 'Apuntalamiento sinonimos'
    UNION ALL SELECT 'INFRAESTRUCTURA_DRENES', '/DRENAJE.*PLUVIAL|TUBERIA.*DRENAJE|POZO.*HUMEDO|CAMARA.*INSPECCION/u', 'Mano de Obra y Suministro por separado', 85, 95, 'Infraestructura drenes sinonimos'

    -- EQUIPOS complementarias
    UNION ALL SELECT 'EQUIPOS_ESPECIALES', '/EQUIPO.*MEDICO|EQUIPO.*LABORATORIO|EQUIPO.*INDUSTRIAL/u', 'Suministro e Instalación', 82, 85, 'Equipos especiales sinonimos'

    -- URBANISMO complementarias
    UNION ALL SELECT 'SENALETICA', '/SENAL.*VERTICAL|SENAL.*VIAL|DEMARCACION.*VIAL|PINTURA.*VIAL/u', 'Suministro e Instalación', 85, 95, 'Senal etica sinonimos'

    -- INSTALACIONES complementarias
    UNION ALL SELECT 'CCTV_SEGURIDAD', '/CAMARA.*SEGURIDAD|GRABACION.*VIDEO|ALARMA.*INTRUSION|DETECTOR.*MOVIMIENTO/u', 'Suministro e Instalación', 85, 105, 'CCTV sinonimos'
    UNION ALL SELECT 'SONIDO_VIDEO', '/SISTEMA.*AUDIO|PROYECTOR|PANTALLA.*PROYECCION|PARLANTES|AMPLIFICADOR/u', 'Suministro e Instalación', 85, 105, 'Sonido video sinonimos'
    UNION ALL SELECT 'SISTEMA_DATOS', '/CABLEADO.*ESTRUCTURADO|PATCH.*PANEL|RACK.*SERVIDOR|SWITCH.*RED/u', 'Suministro e Instalación', 82, 95, 'Sistema datos sinonimos'
    UNION ALL SELECT 'AUTOMATIZACION_BMS', '/DOMOTICA|CONTROL.*HVAC|MONITOREO.*ENERGIA|SCADA/u', 'Suministro e Instalación', 82, 95, 'Automatizacion sinonimos'
) seed
INNER JOIN `general_pdc_familias` f ON f.codigo = seed.codigo;

-- ============================================================================
-- 4. Opciones de contrato para nuevas familias (12 familias, APARATOS hereda)
-- ============================================================================

INSERT IGNORE INTO `general_pdc_family_contract_options` (
    `familia_id`, `tipo_contrato`, `tipo_paquete`,
    `dias_elaboracion`, `dias_entrega`, `dias_recibo`, `dias_cuadros`,
    `dias_legalizacion`, `dias_fabricacion`, `dias_insumos`, `notas`
)
SELECT f.id, seed.tipo_contrato, seed.tipo_paquete,
       seed.dias_elaboracion, seed.dias_entrega, 1, seed.dias_cuadros,
       seed.dias_legalizacion, seed.dias_fabricacion, seed.dias_insumos, seed.notas
FROM (
    -- ACABADOS
    SELECT 'ASEO' codigo, 1 tipo_contrato, 'Mano de Obra y Suministro por separado' tipo_paquete, 8 dias_elaboracion, 7 dias_entrega, 5 dias_cuadros, 10 dias_legalizacion, 0 dias_fabricacion, 0 dias_insumos, 'Aseo y entrega MO+S' notas
    UNION ALL SELECT 'EQUIPOS_COCINA', 2, 'Suministro e Instalación', 8, 15, 10, 20, 0, 0, 'Equipos de cocina SI'
    UNION ALL SELECT 'LAVAPLATOS', 2, 'Suministro e Instalación', 8, 15, 10, 20, 0, 0, 'Lavaplatos SI'
    UNION ALL SELECT 'LAVADERO', 2, 'Suministro e Instalación', 8, 7, 5, 10, 0, 0, 'Lavadero SI'
    UNION ALL SELECT 'ACCESORIOS_SANITARIOS', 2, 'Suministro e Instalación', 8, 7, 5, 10, 0, 0, 'Accesorios sanitarios SI'
    UNION ALL SELECT 'PASAMANOS', 2, 'Suministro e Instalación', 8, 7, 5, 10, 0, 0, 'Pasamanos SI'
    UNION ALL SELECT 'MUEBLES', 2, 'Suministro e Instalación', 8, 15, 10, 20, 0, 0, 'Muebles SI'
    UNION ALL SELECT 'RESANES', 1, 'Mano de Obra y Suministro por separado', 8, 7, 5, 10, 0, 0, 'Resanes MO+S'

    -- PRELIMINARES
    UNION ALL SELECT 'DEMOLICIONES', 1, 'Mano de Obra y Suministro por separado', 8, 7, 5, 10, 0, 0, 'Demoliciones MO+S'

    -- CIMENTACION
    UNION ALL SELECT 'APUNTALAMIENTO', 2, 'Suministro e Instalación', 8, 7, 5, 10, 0, 0, 'Apuntalamiento SI'
    UNION ALL SELECT 'INFRAESTRUCTURA_DRENES', 1, 'Mano de Obra y Suministro por separado', 8, 7, 5, 10, 0, 0, 'Infraestructura drenes MO+S'

    -- EQUIPOS
    UNION ALL SELECT 'EQUIPOS_ESPECIALES', 2, 'Suministro e Instalación', 8, 15, 10, 20, 0, 0, 'Equipos especiales SI'

    -- URBANISMO
    UNION ALL SELECT 'SENALETICA', 2, 'Suministro e Instalación', 8, 7, 5, 10, 0, 0, 'Senal etica SI'

    -- INSTALACIONES
    UNION ALL SELECT 'CCTV_SEGURIDAD', 2, 'Suministro e Instalación', 8, 15, 10, 20, 0, 0, 'CCTV seguridad SI'
    UNION ALL SELECT 'SONIDO_VIDEO', 2, 'Suministro e Instalación', 8, 15, 10, 20, 0, 0, 'Sonido y video SI'
    UNION ALL SELECT 'SISTEMA_DATOS', 2, 'Suministro e Instalación', 8, 15, 10, 20, 0, 0, 'Sistema de datos SI'
    UNION ALL SELECT 'AUTOMATIZACION_BMS', 2, 'Suministro e Instalación', 8, 15, 10, 20, 0, 0, 'Automatizacion BMS SI'
) seed
INNER JOIN `general_pdc_familias` f ON f.codigo = seed.codigo;

-- APARATOS_SANITARIOS conserva su opcion existente (FK por id, no por codigo).

-- ============================================================================
-- 5. Items/paquetes por opcion para nuevas familias
-- ============================================================================

INSERT IGNORE INTO `general_pdc_family_contract_option_items` (
    `option_id`, `tipo_contrato`, `tipo_paquete`, `paquete_nombre`, `dias_proceso_id`, `orden`
)
SELECT opt.id,
       seed.item_tipo_contrato,
       seed.item_tipo_paquete,
       seed.paquete_nombre,
       (
           SELECT dpc.id
           FROM `general_dias_procesos_contratacion` dpc
           WHERE dpc.paqueteContratacion LIKE seed.dias_like
           ORDER BY
               CASE WHEN dpc.diasElaboracionPliegos = 1
                         AND dpc.diasEntregaPliegos = 1
                         AND dpc.diasReciboPropuestas = 1
                         AND dpc.diasCuadrosComparativos = 1
                         AND dpc.diasLegalizacionContrato = 1
                         AND dpc.diasFabricacion = 1
                         AND dpc.diasInsumosObra = 1 THEN 1 ELSE 0 END,
               dpc.id
           LIMIT 1
       ) AS dias_proceso_id,
       seed.orden
FROM (
    -- ASEO: MO+S → 2 paquetes (Suministro + Mano de Obra)
    SELECT 'ASEO' codigo, 'Mano de Obra y Suministro por separado' option_tipo_paquete, 1 item_tipo_contrato, 'Suministro' item_tipo_paquete, 'ASEO Y LIMPIEZA FINAL' paquete_nombre, 'ASEO%' dias_like, 1 orden
    UNION ALL SELECT 'ASEO', 'Mano de Obra y Suministro por separado', 1, 'Mano de Obra', 'MANO DE OBRA ASEO', 'MANO DE OBRA ASEO%', 2

    -- EQUIPOS_COCINA: SI → 1 paquete
    UNION ALL SELECT 'EQUIPOS_COCINA', 'Suministro e Instalación', 2, 'Suministro e Instalación', 'EQUIPOS DE COCINA', 'EQUIPOS DE COCINA%', 1

    -- LAVAPLATOS: SI → 1 paquete
    UNION ALL SELECT 'LAVAPLATOS', 'Suministro e Instalación', 2, 'Suministro e Instalación', 'LAVAPLATOS', 'LAVAPLATOS%', 1

    -- LAVADERO: SI → 1 paquete
    UNION ALL SELECT 'LAVADERO', 'Suministro e Instalación', 2, 'Suministro e Instalación', 'LAVADERO', 'LAVADERO%', 1

    -- ACCESORIOS_SANITARIOS: SI → 1 paquete
    UNION ALL SELECT 'ACCESORIOS_SANITARIOS', 'Suministro e Instalación', 2, 'Suministro e Instalación', 'ACCESORIOS SANITARIOS', 'ACCESORIOS SANITARIOS%', 1

    -- PASAMANOS: SI → 1 paquete
    UNION ALL SELECT 'PASAMANOS', 'Suministro e Instalación', 2, 'Suministro e Instalación', 'PASAMANOS Y BARANDAS', 'PASAMANOS%', 1

    -- MUEBLES: SI → 1 paquete
    UNION ALL SELECT 'MUEBLES', 'Suministro e Instalación', 2, 'Suministro e Instalación', 'MUEBLES', 'MUEBLES%', 1

    -- RESANES: MO+S → 2 paquetes
    UNION ALL SELECT 'RESANES', 'Mano de Obra y Suministro por separado', 1, 'Suministro', 'RESANES', 'RESANES%', 1
    UNION ALL SELECT 'RESANES', 'Mano de Obra y Suministro por separado', 1, 'Mano de Obra', 'MANO DE OBRA RESANES', 'MANO DE OBRA RESANES%', 2

    -- DEMOLICIONES: MO+S → 2 paquetes
    UNION ALL SELECT 'DEMOLICIONES', 'Mano de Obra y Suministro por separado', 1, 'Suministro', 'DEMOLICIONES Y DESMONTES', 'DEMOLICIONES%', 1
    UNION ALL SELECT 'DEMOLICIONES', 'Mano de Obra y Suministro por separado', 1, 'Mano de Obra', 'MANO DE OBRA DEMOLICIONES', 'MANO DE OBRA DEMOLICIONES%', 2

    -- APUNTALAMIENTO: SI → 1 paquete
    UNION ALL SELECT 'APUNTALAMIENTO', 'Suministro e Instalación', 2, 'Suministro e Instalación', 'APUNTALAMIENTO', 'APUNTALAMIENTO%', 1

    -- INFRAESTRUCTURA_DRENES: MO+S → 2 paquetes
    UNION ALL SELECT 'INFRAESTRUCTURA_DRENES', 'Mano de Obra y Suministro por separado', 1, 'Suministro', 'INFRAESTRUCTURA Y DRENES', 'INFRAESTRUCTURA%', 1
    UNION ALL SELECT 'INFRAESTRUCTURA_DRENES', 'Mano de Obra y Suministro por separado', 1, 'Mano de Obra', 'MANO DE OBRA DRENES', 'MANO DE OBRA DRENES%', 2

    -- EQUIPOS_ESPECIALES: SI → 1 paquete
    UNION ALL SELECT 'EQUIPOS_ESPECIALES', 'Suministro e Instalación', 2, 'Suministro e Instalación', 'EQUIPOS ESPECIALES', 'EQUIPOS ESPECIALES%', 1

    -- SENALETICA: SI → 1 paquete
    UNION ALL SELECT 'SENALETICA', 'Suministro e Instalación', 2, 'Suministro e Instalación', 'SENALETICA', 'SENALETICA%', 1

    -- CCTV_SEGURIDAD: SI → 1 paquete
    UNION ALL SELECT 'CCTV_SEGURIDAD', 'Suministro e Instalación', 2, 'Suministro e Instalación', 'CCTV Y SEGURIDAD', 'CCTV%', 1

    -- SONIDO_VIDEO: SI → 1 paquete
    UNION ALL SELECT 'SONIDO_VIDEO', 'Suministro e Instalación', 2, 'Suministro e Instalación', 'SISTEMA DE SONIDO Y VIDEO', 'SONIDO%VIDEO%', 1

    -- SISTEMA_DATOS: SI → 1 paquete
    UNION ALL SELECT 'SISTEMA_DATOS', 'Suministro e Instalación', 2, 'Suministro e Instalación', 'SISTEMA DE DATOS', 'SISTEMA DE DATOS%', 1

    -- AUTOMATIZACION_BMS: SI → 1 paquete
    UNION ALL SELECT 'AUTOMATIZACION_BMS', 'Suministro e Instalación', 2, 'Suministro e Instalación', 'AUTOMATIZACION Y BMS', 'AUTOMATIZACION%', 1
) seed
INNER JOIN `general_pdc_familias` f ON f.codigo = seed.codigo
INNER JOIN `general_pdc_family_contract_options` opt
    ON opt.familia_id = f.id
   AND opt.tipo_paquete = seed.option_tipo_paquete;

SET FOREIGN_KEY_CHECKS = 1;
