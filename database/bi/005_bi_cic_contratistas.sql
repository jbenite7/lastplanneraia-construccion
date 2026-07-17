-- =============================================================
-- BI View 005: bi_cic_contratistas
-- Grain: project_id + Semana + subcontratista
-- Question: ¿Qué contratistas son confiables?
-- Source: cic + subcontratistas
-- =============================================================
CREATE OR REPLACE VIEW `bi_cic_contratistas` AS
SELECT
    c.project_id,
    c.Semana,
    c.subcontratista,
    c.NIT,
    s.alcance,
    s.tipo_proveedor,
    c.correo_contacto,

    -- Cumplimiento
    c.PAC,
    c.PAC_Acum,
    c.P_Completado,
    c.P_Completado_Acum,

    -- Dimensiones de evaluación integral
    c.Calidad,
    c.Calidad_Acum,
    c.GSA,
    c.GSA_Acum,
    c.SST,
    c.SST_Acum,
    c.ADM,
    c.ADM_Acum,

    -- Score integral
    c.Cal_Integral,
    c.Cal_Integral_Acum,

    -- Aprobación de proveedores (NUEVO — Power BI)
    CASE
        WHEN c.Cal_Integral >= 70 THEN 'Aprobado'
        WHEN c.Cal_Integral >= 50 THEN 'Seguimiento'
        WHEN c.Cal_Integral IS NOT NULL THEN 'No Aceptado'
        ELSE 'Sin calificar'
    END AS aprobacion_status,

    -- Alerta de riesgo futuro (BI-only, no bloquea operación)
    CASE
        WHEN c.Cal_Integral_Acum IS NOT NULL
         AND c.Cal_Integral_Acum < 50
        THEN 1 ELSE 0
    END AS alert_contractor_future_risk,

    c.Observaciones

FROM cic c
LEFT JOIN subcontratistas s
    ON c.project_id = s.project_id
   AND c.subcontratista = s.subcontratista
   AND s.activo = 1
ORDER BY c.project_id, c.Semana, c.Cal_Integral DESC;
