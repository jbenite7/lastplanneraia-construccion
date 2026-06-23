-- Patch: Expansiones regex en 6 familias existentes
-- Fecha: 2026-06-15
-- Objetivo: agregar reglas complementarias para capturar actividades adicionales
--           en ESTRUCTURA_CONCRETO, VIAS_PAVIMENTOS, CIELOS_RASOS, FACHADA,
--           RED_ELECTRICA y CARPINTERIA_METALICA.

SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================================
-- Expansiones regex (6 reglas nuevas)
-- ============================================================================

INSERT IGNORE INTO `general_pdc_activity_rules` (`familia_id`, `patron_regex`, `modalidad_sugerida`, `confianza`, `prioridad`, `descripcion`)
SELECT f.id, seed.patron_regex, seed.modalidad_sugerida, seed.confianza, seed.prioridad, seed.descripcion
FROM (
    -- ESTRUCTURA_CONCRETO: agregar CUBIERTA|ESCALERAS|RAMPAS
    SELECT 'ESTRUCTURA_CONCRETO' codigo, '/CUBIERTA|ESCALERAS|RAMPAS/u' patron_regex, 'Mano de Obra y Suministro por separado' modalidad_sugerida, 80 confianza, 75 prioridad, 'Estructura concreto expansion: cubiertas, escaleras, rampas' descripcion

    -- VIAS_PAVIMENTOS: agregar ASFALT|MEZCLA.*ASFALTICA|SUBRASANTE|BASE.*ASFALTICA|PLATAFORMA.*CONCRETO
    UNION ALL SELECT 'VIAS_PAVIMENTOS', '/ASFALT|MEZCLA.*ASFALTICA|SUBRASANTE|BASE.*ASFALTICA|PLATAFORMA.*CONCRETO/u', 'Suministro e Instalación', 85, 90, 'Vias pavimentos expansion: asfalticos, subrasante, plataforma concreto'

    -- CIELOS_RASOS: agregar ^CIELOS$
    UNION ALL SELECT 'CIELOS_RASOS', '/^CIELOS$/u', 'Suministro e Instalación', 82, 85, 'Cielos rasos expansion: match exacto CIELOS'

    -- FACHADA: agregar FACHADA.*METECNO|ALUCOBOND|MUROS.*EXTERIORES|FACHADAS
    UNION ALL SELECT 'FACHADA', '/FACHADA.*METECNO|ALUCOBOND|MUROS.*EXTERIORES|FACHADAS/u', 'Suministro e Instalación', 85, 90, 'Fachada expansion: Metecno, Alucobond, muros exteriores'

    -- RED_ELECTRICA: agregar SUICHES|CABLE.*COBRE
    UNION ALL SELECT 'RED_ELECTRICA', '/SUICHES|CABLE.*COBRE/u', 'Suministro e Instalación', 82, 85, 'Red electrica expansion: switches, cable cobre'

    -- CARPINTERIA_METALICA: agregar DIVISIONES.*BANO|DIVISION.*BANO
    UNION ALL SELECT 'CARPINTERIA_METALICA', '/DIVISIONES.*BANO|DIVISION.*BANO/u', 'Suministro e Instalación', 85, 95, 'Carpinteria metalica expansion: divisiones de bano'
) seed
INNER JOIN `general_pdc_familias` f ON f.codigo = seed.codigo;

SET FOREIGN_KEY_CHECKS = 1;
