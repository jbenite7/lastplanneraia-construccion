-- Siembra la linea base contractual de los proyectos que YA tienen cronograma y no la declararon.
--
-- AUTORIZACION: Felipe, 2026-08-19, directa en el chat de la sesion ejecutora — «autorizo crear la
-- migracion y ejecutarla contra dev». Registrada ademas por la coordinadora en
-- decisiones/linea-base-contractual-coordinadora.md, donde eligio esta opcion con las otras dos y
-- sus costos delante. `database/migrations/**` esta protegido por .claude/gate.yaml y exige esa
-- autorizacion explicita; se cita aqui porque sin ella este archivo no deberia existir.
--
-- POR QUÉ ES UNA MIGRACIÓN Y NO UN SCRIPT PHP: el sembrado automático vive en `nueva_semana.php`,
-- y el fixture del CI inserta filas directo en la base sin pasar por ahí. Resultado: en CI ningún
-- proyecto tenía línea base declarada, la fecha contractual salía NULL y
-- `test_bi_programa_general_chart_values` seguía en rojo aunque el cálculo estuviera bien.
-- `database/fixtures/design-system-ci.Dockerfile` SÍ aplica migraciones (su línea 41 carga
-- justamente `20260807_proyectos_lineabase_columns.sql`, que creó estas columnas y no las rellenó),
-- así que una migración cubre CI y producción con un solo mecanismo, sin tocar el fixture ni el test.
--
-- WRITE-ONCE: el WHERE excluye a quien ya la tenga. Si alguien la corrigió a mano, manda la suya.
-- Reejecutar esto no pisa nada: es idempotente por construcción, no por suerte.
--
-- QUÉ ES ESTA FECHA, dicho sin adornos: el primer corte registrado del programa, que es «cuándo
-- empezamos a registrar» y no «qué se prometió en el contrato». Decisión de Felipe del 2026-08-19,
-- tomada con esa advertencia delante: se siembran automáticamente y quien tenga la real la corrige.

UPDATE general_proyectos_procesos p
  JOIN (
        SELECT c.project_id,
               MIN(c.Fecha_Inicio) AS inicio,
               MAX(c.Fecha_Fin)    AS fin
          FROM programa_consolidado c
          JOIN (SELECT project_id, MIN(Semana) AS primera
                  FROM programa_consolidado
                 GROUP BY project_id) pr
            ON pr.project_id = c.project_id
           AND pr.primera    = c.Semana
         WHERE c.Fecha_Inicio IS NOT NULL
           AND c.Fecha_Fin    IS NOT NULL
         GROUP BY c.project_id
       ) pc ON pc.project_id = p.Id
   SET p.fechaInicioLineaBase = pc.inicio,
       p.fechaFinLineaBase    = pc.fin
 WHERE p.fechaInicioLineaBase IS NULL
    OR p.fechaFinLineaBase    IS NULL;
