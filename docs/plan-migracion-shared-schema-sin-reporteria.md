# Plan Maestro de Migracion a Esquema Compartido (Sin Tablas de Reporteria)

> 🚀 **Arquitectura Futura / Ready for Development**

## 1) Objetivo

Eliminar el modelo actual de **tablas por prefijo de proyecto** (ej. `proyectoX_programacion_semanal`) y migrar a un modelo **compartido por `project_id`**, sin tablas de reporteria persistente.

Resultado esperado:

- No crear 10+ tablas por cada proyecto nuevo.
- Mantener funcionalidad actual (Curva S, Curva S PDC, CIC/CIP, indicadores).
- Reducir deuda tecnica y costo operativo.
- Mejorar seguridad e integridad de datos (FKs, indices, constraints).

Nota: para la estrategia de datos zero-loss y checklist numerico de Go/No-Go, ver `docs/plan-migracion-datos-zero-loss.md`.

---

## 2) Alcance

Incluye:

- Diseno y creacion de esquema compartido.
- Migracion de datos de tablas legacy a tablas compartidas.
- Refactor de acceso a datos para dejar de interpolar nombres dinamicos.
- Reemplazo de reporteria persistida por calculo on-demand (+ cache opcional).
- Plan de corte, validacion, rollback y decommission.

No incluye (en esta fase):

- Reescritura total de logica de negocio.
- Cambio de UI/UX.
- Optimizaciones avanzadas (solo las necesarias para estabilidad).

---

## 3) Supuestos y decisiones

1. MySQL/InnoDB como motor principal.
2. Las tablas globales `general_proyectos_procesos` y `general_usuarios` se normalizan progresivamente hacia `projects` y `users`.
3. Se prioriza continuidad operativa con una ventana de corte corta.
4. Se elimina dependencia de tablas de reporteria (`general_curvas`, `general_curvas_pdc`, etc.) y se migra a calculo on-demand.

---

## 4) Arquitectura destino (resumen)

### 4.1 Entidades base

- `projects`
- `users`
- `project_members`
- `audit_actions`

### 4.2 Tablas operativas compartidas (con `project_id`)

- `project_programa`
- `project_programa_consolidado`
- `project_programacion_semanal`
- `project_semanas_activas`
- `project_subcontratistas`
- `project_profesionales`
- `project_cic`
- `project_cip`
- `project_pdc`
- `project_papelera_pdc`
- `project_cambios`
- `project_actividades`
- `project_indicadores_generales`

### 4.3 Catalogos y soporte

- `cat_cnc`
- `cat_codigos_actividades`
- `cat_dias_procesos_contratacion`
- `project_costos_cuadrillas`
- `project_cuadrillas_tipicas`

---

## 5) Estrategia para eliminar tablas de reporteria

### 5.1 Politica

- No persistir curvas ni reportes agregados en tablas dedicadas.
- Calcular reportes desde tablas operativas compartidas.
- Aplicar cache por proyecto/semana/rango si el tiempo de respuesta supera SLA.

### 5.2 Curva S / Curva S PDC

- **Fuente de verdad:** `project_programa_consolidado`, `project_semanas_activas`, `project_pdc`.
- **Calculo:** query agregada por semana.
- **Cache recomendado:** TTL 5-30 min, invalidacion por cambios en semana activa o procesos de carga.

### 5.3 SLA sugerido

- P95 endpoint Curva S <= 2.5s
- P95 endpoint Curva S PDC <= 2.5s
- Si supera SLA por 2 sprints consecutivos: activar materializacion parcial (no tablas permanentes por proyecto).

---

## 6) Plan por fases (detallado)

## Fase 0 - Preparacion y control de riesgo (1 semana)

### Objetivos

- Congelar alcance.
- Asegurar backups y ruta de rollback.
- Definir criterios de exito.

### Tareas

1. Inventario final de tablas legacy por prefijo de proyecto.
2. Congelar cambios de esquema en rama principal.
3. Definir ventana de mantenimiento para cutover.
4. Generar backup completo verificado (restore probado).
5. Definir matriz de riesgos y responsables (DBA, backend, QA, DevOps).

### Entregables

- Checklist de pre-migracion firmado.
- Backup validado + prueba de restauracion documentada.
- Plan de comunicacion interna.

### Go/No-Go

- No avanzar si no hay restore exitoso en entorno de prueba.

---

## Fase 1 - Diseno de esquema compartido e indices (1-2 semanas)

### Objetivos

- Crear DDL definitivo.
- Asegurar integridad y rendimiento base.

### Tareas

1. Definir DDL de todas las tablas compartidas (`project_id` obligatorio).
2. Definir constraints:
   - FKs a `projects(id)`.
   - `UNIQUE(project_id, clave_negocio)` donde aplique.
3. Definir indices operativos:
   - `(project_id, semana)` en tablas semanales.
   - Indices por columnas de filtrado frecuente (`sub_contratista`, `responsable_aia`, `titulo`, etc.).
4. Homologar charset/collation (`utf8mb4`).
5. Definir convenciones de tipos:
   - Montos en `DECIMAL`.
   - Flags en `TINYINT(1)`.
   - Fechas en `DATE`/`DATETIME`.

### Entregables

- Script DDL versionado (`migrations/001_shared_schema.sql`).
- Diccionario de datos por tabla/campo.
- Matriz de indices y su justificacion.

### Go/No-Go

- No avanzar si hay ambiguedad de tipos o claves unicas sin definir.

---

## Fase 2 - Pipeline de migracion de datos (2 semanas)

### Objetivos

- Migrar datos legacy -> shared de forma idempotente y auditable.

### Tareas

1. Crear tabla de control de migracion (`migration_runs`) con estado por proyecto.
2. Crear proceso ETL por bloque funcional:
   - Programa/Programa consolidado
   - Programacion semanal
   - Semanas activas
   - Subcontratistas/Profesionales
   - CIC/CIP
   - PDC/Papelera PDC
   - Indicadores generales
3. Definir claves de deduplicacion por tabla.
4. Registrar conteos por tabla antes/despues.
5. Ejecutar migracion en staging con volumen realista.

### Reglas de seguridad ETL

- Solo `INSERT ... ON DUPLICATE KEY UPDATE` o `MERGE` equivalente.
- Sin borrados masivos en primera corrida.
- Log por lote con trazabilidad de errores.

### Entregables

- Script ETL (`scripts/migrate_legacy_to_shared.php`).
- Reporte de reconciliacion por proyecto/tabla.
- Reintentos seguros por proyecto (idempotencia).

### Go/No-Go

- No avanzar si la diferencia de conteo por tabla > 0.5% sin explicacion.

---

## Fase 3 - Refactor de aplicacion (2-4 semanas)

### Objetivos

- Eliminar SQL con tablas dinamicas `{$dbPrefix}_...`.
- Centralizar acceso en repositorios/servicios.

### Tareas

1. Crear capa de acceso compartida por dominio:
   - `ProgramRepository`
   - `WeeklyPlanRepository`
   - `PdcRepository`
   - `CicRepository`
   - `IndicatorsRepository`
2. Sustituir queries dinamicas por queries con `WHERE project_id = ?`.
3. Introducir `ProjectContext` seguro (project_id validado por permiso).
4. Mantener pruebas de regresion en endpoints criticos.
5. Eliminar llamadas a funciones de creacion/renombre/borrado de tablas por proyecto.

### Entregables

- PRs por modulo funcional (small batches).
- Cobertura de pruebas en rutas criticas.
- Lista de endpoints migrados.

### Go/No-Go

- No avanzar a corte si hay endpoints legacy aun escribiendo en tablas por prefijo.

---

## Fase 4 - Reporteria sin tablas persistentes (1-2 semanas)

### Objetivos

- Sustituir `general_curvas*` y tablas de reporte por consultas operativas.

### Tareas

1. Implementar servicios de consulta:
   - Curva S
   - Curva S PDC
   - Reportes consolidados por semana
2. Agregar cache opcional por llave:
   - `curve_s:{project_id}:{semana_max}`
   - `curve_s_pdc:{project_id}:{semana_max}`
3. Instrumentar metricas de rendimiento:
   - Latencia p50/p95
   - Errores por endpoint
4. Validar equivalencia estadistica contra reportes legacy.

### Entregables

- Endpoints/report services en produccion.
- Dashboard de performance.
- Politica de cache y invalidacion documentada.

### Go/No-Go

- No eliminar tablas legacy de reporteria si no se cumple SLA minimo acordado.

---

## Fase 5 - Cutover y decommission (1 semana)

### Objetivos

- Cambiar fuente oficial a shared schema.
- Retirar legado de forma segura.

### Tareas

1. Activar feature flag `shared_schema_enabled=true`.
2. Ventana corta de congelamiento de escritura (si es necesaria).
3. Ejecutar validaciones post-corte:
   - Conteos
   - KPI principales
   - Flujos de negocio (smoke tests)
4. Marcar tablas legacy como read-only temporal.
5. Decommission por etapas:
   - Semana 1: sin escrituras
   - Semana 2: sin lecturas
   - Semana 3: backup final + DROP controlado

### Entregables

- Acta de cutover.
- Evidencia de smoke tests.
- Plan de borrado definitivo firmado.

### Go/No-Go

- Revertir flag si hay errores funcionales criticos o desvio fuerte en KPI.

---

## 7) Plan de validacion funcional y de datos

## 7.1 Validaciones de datos

- Conteo por `project_id` y `semana` en cada tabla clave.
- Muestreo de 30 registros por tabla (comparacion campo a campo).
- Integridad referencial sin huérfanos.
- Validacion de nullability y tipos.

## 7.2 Validaciones funcionales

- Login + seleccion de proyecto.
- Programa general y programacion semanal.
- PDC CRUD.
- CIC/CIP calculos.
- Indicadores y curvas.

## 7.3 Validaciones de rendimiento

- Benchmark antes/despues en endpoints top 10.
- EXPLAIN de queries pesadas.
- Revisión de indices faltantes con slow query log.

---

## 8) Rollback plan (obligatorio)

## 8.1 Rollback rapido (aplicacion)

- Toggle `shared_schema_enabled=false`.
- Volver a rutas de lectura legacy.

## 8.2 Rollback de datos

- No destruir tablas legacy hasta estabilizacion (>2 semanas).
- Si falla migracion, restaurar backup y repetir por proyecto.

## 8.3 Criterios de rollback

- Error funcional severo sin workaround en < 60 min.
- Degradacion de performance > 40% sostenida.
- Inconsistencia de datos critica en flujos core.

---

## 9) Riesgos principales y mitigaciones

1. **Desalineacion de esquemas legacy**
   - Mitigacion: diccionario por proyecto + mapeo explicito por ETL.
2. **Queries lentas al quitar reporteria persistente**
   - Mitigacion: indices, cache, optimizacion incremental.
3. **Regresion funcional en modulos legacy**
   - Mitigacion: feature flags + pruebas de regresion por modulo.
4. **Corte con ventana insuficiente**
   - Mitigacion: ensayo completo en staging con reloj real.

---

## 10) Cronograma sugerido (8-12 semanas)

- Semanas 1-2: Fase 0 + Fase 1
- Semanas 3-4: Fase 2
- Semanas 5-8: Fase 3
- Semanas 9-10: Fase 4
- Semanas 11-12: Fase 5 + estabilizacion

---

## 11) Definition of Done

Se considera completada la migracion cuando:

1. No se crean ni consultan tablas `{$prefijo}_*` en codigo productivo.
2. Curva S y Curva S PDC funcionan on-demand cumpliendo SLA.
3. Integridad referencial activa en tablas compartidas.
4. Tablas de reporteria legacy deshabilitadas o eliminadas.
5. Manual operativo y runbook actualizados.

---

## 12) Checklist ejecutivo de arranque (para cuando inicies)

- [ ] Confirmar equipo y responsables por fase.
- [ ] Aprobar DDL final y criterios de exito.
- [ ] Preparar entorno staging fiel a produccion.
- [ ] Ejecutar migracion piloto con 1 proyecto.
- [ ] Revisar resultados y ajustar ETL.
- [ ] Programar ventana de cutover.

---

## 13) Recomendacion final

Para este caso, la mejor estrategia es:

- **Primero:** shared schema + refactor de acceso.
- **Despues:** reporteria on-demand con cache.
- **Solo si hay necesidad real:** materializacion selectiva (no volver a modelo de tablas por proyecto).
