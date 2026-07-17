-- =============================================================
-- BI View 003: bi_ps_compromisos
-- Grain: project_id + Semana + Consecutivo (commitment row)
-- Question: ¿Se hará?
-- Source: programacion_semanal + programa_consolidado
-- =============================================================
CREATE OR REPLACE VIEW `bi_ps_compromisos` AS
SELECT
    ps.project_id,
    ps.Semana,
    ps.Consecutivo AS row_id,
    ps.Consecutivo_En_Programa,
    ps.Id,
    ps.Actividad,
    ps.Descripcion,
    ps.Ubicacion,
    ps.Fecha_Inicio,
    ps.Fecha_Fin,
    ps.Sub_Contratista AS subcontractor,
    ps.Responsable_AIA AS responsible,
    ps.Empresa,
    ps.Unidad,
    ps.Compromiso,
    ps.Ejecutado_Real,
    ps.P_Completado,
    ps.PAC,
    ps.Critica AS critical,
    ps.Atrasada AS late,
    ps.Activa,
    ps.Es_TNP AS is_TNP,

    -- Causas de No Programación
    ps.Categoria_CNP,
    ps.CNP,
    ps.Observaciones_CNP,

    -- Causas de No Cumplimiento
    ps.Categoria_CNC,
    ps.CNC,
    ps.Observaciones_CNC,

    -- Flags derivados
    CASE WHEN ps.CNP IS NOT NULL AND ps.CNP != '' THEN 1 ELSE 0 END AS has_CNP,
    CASE WHEN ps.CNC IS NOT NULL AND ps.CNC != '' THEN 1 ELSE 0 END AS has_CNC,
    CASE WHEN ps.Activa = '0' AND ps.CNP IS NOT NULL AND ps.CNP != '' THEN 1 ELSE 0 END AS is_cnp_population,
    CASE WHEN ps.Activa IN ('1', 'NA') AND ps.CNC IS NOT NULL AND ps.CNC != '' THEN 1 ELSE 0 END AS is_cnc_population,
    CASE WHEN ps.Activa IN ('1', 'NA') THEN 1 ELSE 0 END AS is_commitment_population,
    CASE WHEN ps.Responsable_AIA IS NULL OR ps.Responsable_AIA = '' THEN 1 ELSE 0 END AS missing_responsible,
    CASE WHEN ps.Sub_Contratista IS NULL OR ps.Sub_Contratista = '' THEN 1 ELSE 0 END AS missing_subcontractor,

    -- ¿El compromiso está listo? (tiene responsable, contratista, y no es TNP)
    CASE
        WHEN ps.Es_TNP = 1 THEN 0
        WHEN ps.Responsable_AIA IS NOT NULL
         AND ps.Responsable_AIA != ''
         AND ps.Sub_Contratista IS NOT NULL
         AND ps.Sub_Contratista != ''
        THEN 1 ELSE 0
    END AS commitment_ready,

    -- Alerta de cumplimiento: compromiso crítico con PAC bajo o sin responsable
    CASE
        WHEN ps.Critica = 1
         AND (ps.PAC = 0 OR ps.P_Completado IS NULL OR ps.P_Completado < 0.5
              OR ps.Responsable_AIA IS NULL OR ps.Responsable_AIA = ''
              OR ps.Sub_Contratista IS NULL OR ps.Sub_Contratista = '')
        THEN 1 ELSE 0
    END AS fulfillment_alert,

    -- Sin evidencia historica, la probabilidad esperada permanece desconocida.
    -- Se conserva la columna para compatibilidad con consumidores existentes.
    NULL AS pac_expected_baseline,

    -- Datos del programa para contexto
    pc.Ruta_Critica,
    pc.Estado AS pg_estado,
    pc.Semanas_Inicio,
    pc.Ejecutado AS pg_ejecutado,

    -- Reprogramaciones
    ps.Reprogramada_Por_Usuario,
    ps.reprogramaciones_semanales

FROM programacion_semanal ps
LEFT JOIN programa_consolidado pc
    ON ps.project_id = pc.project_id
   AND ps.Semana = pc.Semana
   AND ps.Consecutivo_En_Programa = pc.Consecutivo_en_Programa
WHERE ps.Activa IN ('0', '1', 'NA')
ORDER BY ps.project_id, ps.Semana, ps.Consecutivo;
