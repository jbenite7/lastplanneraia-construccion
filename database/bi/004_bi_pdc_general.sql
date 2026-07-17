-- =============================================================
-- BI View 004: bi_pdc_general
-- Grain: project_id + semana + consecutivo
-- Question: ¿Compras permitirá ejecutar?
-- Source: pdc
-- General view (no split by front/tower/stage)
-- =============================================================
CREATE OR REPLACE VIEW `bi_pdc_general` AS
SELECT
    project_id,
    semana,
    consecutivo,
    titulo,
    tipoPaquete,
    paqueteContratacion,
    contratos,
    estado,
    numeroSubcontratos,
    subcontratoPaquete,

    -- Fechas planeadas vs reales
    fechaElaboracionPliegos,
    diasElaboracionPliegos,
    fechaRealElaboracionPliegos,
    fechaEntregaPliegos,
    diasEntregaPliegos,
    fechaRealEntregaPliegos,
    fechaReciboPropuestas,
    diasReciboPropuestas,
    fechaRealReciboPropuestas,
    fechaCuadrosComparativos,
    diasCuadrosComparativos,
    fechaRealCuadrosComparativos,
    fechaLegalizacionContrato,
    diasLegalizacionContrato,
    fechaRealLegalizacionContrato,
    fechaFabricacion,
    diasFabricacion,
    fechaRealFabricacion,
    fechaInsumosObra,
    diasInsumosObra,
    fechaRealInsumosObra,

    -- Fechas de inicio
    fechaInicio,
    fechaInicioProyectada,
    fechaRealInicio,

    -- Valores
    valorPresupuesto,
    valorPrimeraNegociacion,
    valorAdjudicado,
    valorAnticipo,
    valorReclamado,
    valorDevoluciones,

    -- Proveedor
    idProveedorAdjudicado,
    numeroContrato,

    -- Flags derivados
    CASE
        WHEN titulo = 1 THEN NULL
        WHEN fechaRealInsumosObra IS NOT NULL
         AND fechaInicio IS NOT NULL
         AND fechaRealInsumosObra <= fechaInicio
        THEN 1 ELSE 0
    END AS listo_para_iniciar,

    CASE
        WHEN titulo = 1 THEN NULL
        WHEN diasElaboracionPliegos IS NULL
          OR diasEntregaPliegos IS NULL
          OR diasFabricacion IS NULL
          OR diasInsumosObra IS NULL
        THEN 1 ELSE 0
    END AS necesita_configuracion,

    -- Días delta entre estado esperado y real (basado en insumos en obra)
    CASE
        WHEN titulo = 1 THEN NULL
        WHEN fechaRealInsumosObra IS NULL OR fechaInsumosObra IS NULL THEN NULL
        ELSE DATEDIFF(COALESCE(fechaRealInsumosObra, CURDATE()), fechaInsumosObra)
    END AS dias_delta_simple,

    -- Días delta para inicio
    CASE
        WHEN titulo = 1 THEN NULL
        WHEN fechaRealInicio IS NULL OR fechaInicio IS NULL THEN NULL
        ELSE DATEDIFF(COALESCE(fechaRealInicio, CURDATE()), fechaInicio)
    END AS dias_delta_inicio,

    observacionesContrato

FROM pdc
WHERE titulo = 0  -- solo paquetes, no títulos
ORDER BY project_id, semana, consecutivo;
