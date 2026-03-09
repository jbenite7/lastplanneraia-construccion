# Plan Maestro LPS - Programacion Intermedia y Restricciones Compartidas

> ⏸️ **Aplazado Oficialmente para el Próximo Macro-Sprint**

## 1. Objetivo

Implementar un sistema de Programacion Intermedia alineado con Lean Construction y Last Planner System (LPS) que:

- reduzca manualidad operativa,
- detecte cuellos de botella antes del impacto en obra,
- garantice asignacion clara de responsables y subcontratistas,
- convierta la liberacion de restricciones en un flujo accionable y trazable,
- habilite gestion de restricciones compartidas para multiples actividades.

## 2. Alcance

### 2.1 Alcance funcional

- Gestion de restricciones por actividad (PI HOT).
- Recalculo en vivo de `% Liberacion`.
- Asignacion obligatoria y trazable de `Responsable AIA` y `Sub-Contratista`.
- Estandar operativo de observaciones.
- Restricciones compartidas (RC) con aplicacion en lote.
- Dashboard operativo de cuellos de botella.

### 2.2 Alcance tecnico inmediato

- `construccion/js/modules/programacion_intermedia/hot.js`
- `construccion/programacion_intermedia/views/programacion_intermedia.view.handsontable.php`
- `construccion/programacion_intermedia/guardar_programacion_intermedia.php`
- `construccion/programacion_intermedia/listar_programacion_intermedia.php`

### 2.3 Alcance transversal de estabilidad

- `construccion/js/modules/programa_general/hot.js`
- `construccion/programa_general/views/programa_general.view.handsontable.php`

## 3. Principios operativos Lean/LPS

- **Make Ready primero:** una actividad no entra a compromiso semanal si no esta lista.
- **Un dueno por restriccion:** cero ambiguedad de responsabilidad.
- **Fechas compromiso trazables:** cada restriccion tiene fecha objetivo.
- **Gestion por excepcion:** foco en vencidas, criticas y de alto impacto.
- **Aprendizaje continuo:** medir causas de no cumplimiento y ajustar reglas.

## 4. Cadencia de gestion

- **Lunes (Lookahead 6 semanas):** deteccion de restricciones de alto impacto.
- **Miercoles (mesa de desbloqueo):** resolucion de vencidas y criticas.
- **Viernes (compromisos semanales):** solo actividades `Ready` entran al plan.
- **Diario (huddle 10-15 min):** bloqueos del dia y proximas 48h.

## 5. Reglas de negocio definitivas

### 5.1 Escalas de restriccion

- Escala larga: `0% | 33% | 66% | 100% | N/A` para `D_y_E`, `Materiales`, `MdeO`, `Equipos`.
- Escala corta: `0% | 50% | 100% | N/A` para `Predecesora`, `Pdto_Cons`, `Modelo`.

### 5.2 Representacion y persistencia

- UI muestra siempre porcentaje.
- Backend persiste ratio canonico (`33% -> 0.33`, `50% -> 0.5`, `100% -> 1`, `N/A`).

### 5.3 Formula `% Liberacion`

- Promedio de restricciones validas (`!= N/A`).
- Si todas son `N/A`, resultado = `100%`.
- Calculo en vivo en frontend al editar y recalc backend al guardar.

### 5.4 Gate Ready

- Sin responsable o sin subcontratista en ventana `Semanas_Inicio <= 3` => `Not Ready`.
- Restriccion critica abierta en `T-1` => `Not Ready`.
- Excepciones se registran con justificacion y duenio aprobador.

## 6. Restricciones compartidas (RC)

### 6.1 Definicion

Una restriccion que aplica simultaneamente a multiples actividades (ej. suministro para varios pisos, disponibilidad de cuadrilla para toda una torre).

## 7. Modelo de datos recomendado

### 7.1 Fase inicial (compatibilidad)

Mantener `*_programa_consolidado` y agregar metadatos de control:

- `ready_gate` (bool)
- `ready_reason` (texto corto)
- `last_update_at` (timestamp)
- `aging_days` (int)
- `risk_score` (int)

### 7.2 Fase escalable (normalizada)

- `shared_constraints`
  - `id`, `code`, `project_id`, `semana`, `restriction_type`, `scope_json`, `value_ratio`, `status`, `owner_id`, `subcontractor_id`, `due_date`, `note_last`, `created_by`, `created_at`, `updated_at`
- `shared_constraint_links`
  - `id`, `shared_constraint_id`, `activity_id`, `override_local`, `inherited_ratio`, `applied_at`
- `shared_constraint_history`
  - `id`, `shared_constraint_id`, `event_type`, `from_value`, `to_value`, `user_id`, `note`, `created_at`
- `assignment_matrix`
  - `id`, `scope_key`, `default_owner_id`, `default_subcontractor_id`, `active`

## 8. APIs objetivo

### 8.1 Mantener y fortalecer

- `guardar_programacion_intermedia.php`:
  - normalizar entrada `%/ratio/N/A`,
  - devolver `estado_restricciones`, `estado`, `semanas_inicio`.

### 8.2 Nuevas APIs RC

- `POST /programacion-intermedia/shared-constraints/create`
- `POST /programacion-intermedia/shared-constraints/preview`
- `POST /programacion-intermedia/shared-constraints/apply`
- `POST /programacion-intermedia/shared-constraints/override`
- `GET /programacion-intermedia/shared-constraints/list`

## 9. UX minima obligatoria

- Dropdown de restricciones abre con 1 click.
- Valores de restricciones visibles siempre como `%`.
- `% Liberacion` se recalcula al instante.
- Diferenciacion visual editable/read-only.
- Header compacto sin superposicion.
- Badge de guardado/error integrado al toolbar (sin elementos flotantes).
- Sin saltos de scroll al guardar.

### 10.3 Manejo de Datos (Backend / Base de Datos)

Actualmente, las columnas `Sub_Contratista` y `Responsable_AIA` de `_programa_consolidado` guardan un solo valor plano como `VARCHAR`. Para soportar N registros sin quebrar las consultas legacy subyacentes:

1. **Fase 1 (Aproche de Bajo Impacto - JSON Array):**
    - Las columnas se mantendrán en texto pero persistirán arreglos codificados (ej. `["Construcciones JFB SAS", "Estructuras LTDA"]`).
    - Durante la lectura en el frontend, el parser convertirá el JSON/Texto alzado en un arreglo real de JS para rellenar los Selects.
2. **Impacto en el Cumplimiento (Gate Ready):** La regla de validación de asignación múltiple es equivalente a la simple: si el Array/Cadena está vacío `[]`, la actividad flaggea como *Not Ready*.
3. **Restricciones Compartidas (RC):** Si se levanta una RC sobre una actividad multi-responsable, la advertencia/restricción impacta al conjunto completo asignado a la celda hasta que el bloqueo se libere.

## 11. Observaciones efectivas

### 11.1 Estructura obligatoria

`Causa | Accion | Responsable | Fecha compromiso | Evidencia`

### 11.2 Reglas de obligatoriedad

Exigir observacion cuando:

- baja una restriccion,
- cambia a estado critico,
- se incumple fecha compromiso.

### 11.3 Historial

- Celda muestra resumen corto de ultima accion.
- Historial completo en modal/panel lateral.

## 12. Cuellos de botella y alertas

### 12.1 Score de riesgo (0-100)

- Urgencia por `Semanas_Inicio`.
- Brecha de liberacion.
- Falta de asignacion.
- Aging sin actualizacion.

### 12.2 Semaforo operativo

- Rojo: alto riesgo.
- Ambar: riesgo medio.
- Verde: controlado.
- Azul: liberada/control.

## 13. KPIs

- `% Ready` en T-2/T-1.
- `% restricciones liberadas a tiempo`.
- Backlog vencido (cantidad y aging).
- PPC semanal.
- Tiempo medio de desbloqueo.
- Top causas de no cumplimiento.

## 14. Plan por fases (12 semanas)

### Fase 0 (Semanas 1-2): Estabilizacion PI

- Cerrar UX de tabla.
- Consolidar `%` consistente UI/payload.
- Recalculo en vivo `% Liberacion`.
- Anti-salto confirmado PI/PG.

### Fase 1 (Semanas 3-5): Make Ready robusto

- Gate Ready automatico.
- Validaciones de asignacion.
- Observaciones estructuradas.
- KPI base operativo.

### Fase 2 (Semanas 6-8): RC MVP

- Crear RC + preview + apply en lote.
- Recalculo masivo `% Liberacion`.
- Historial inicial.

### Fase 3 (Semanas 9-12): Escalamiento

- Overrides locales.
- Alertas SLA.
- Pareto de causas.
- Ajuste de umbrales por proyecto.

## 15. Criterios de aceptacion por entrega

- Dropdown 1 click funcional en toda restriccion editable.
- Celdas de restriccion siempre en `%`.
- `% Liberacion` cambia en vivo y coincide con backend tras guardar.
- Sin salto de viewport al guardar.
- RC aplica a N actividades con preview y trazabilidad.
- Observacion estructurada forzada en eventos criticos.

## 16. Riesgos y mitigaciones

- **Riesgo:** datos inconsistentes por captura manual.
  - **Mitigacion:** normalizacion estricta en frontend y backend.
- **Riesgo:** exceso de carga operativa.
  - **Mitigacion:** autocompletado + RC + plantillas.
- **Riesgo:** promedio oculta cuello critico.
  - **Mitigacion:** gate Ready y semaforo por restriccion critica.
