# Auditoria Unique ID / scoped ID

Fuente revisada: `src/Core/Database.php`, `database/migrations/20260630_global_tables_contract.sql`, `database/migrations/001_create_global_tables.sql` y migraciones semi-auto/actividad_programa_fuentes.

## Contrato recomendado

Toda tabla de datos de proyecto debe tener `project_id` y sus consultas runtime deben filtrar por `WHERE project_id = ?`. Cuando la UI muestra un consecutivo humano, ese consecutivo debe ser unico dentro del proyecto, no global.

## Tablas LPS principales

| Tabla | ID operativo | Clave/alcance esperado | Estado |
|---|---:|---|---|
| `semanas_activas` | `Id`, `Semana` | `PRIMARY (project_id, Id)`, `UNIQUE (project_id, Semana)` | Correcto en contrato global. |
| `programa` | `unique_id`, `Consecutivo` | `PRIMARY (project_id, Consecutivo)`, `UNIQUE (project_id, unique_id)` | Correcto; `program_unique_id_sequences` reserva `unique_id` por proyecto. |
| `programa_consolidado` | `row_id`, `Consecutivo`, `unique_id` | `PRIMARY (project_id, Consecutivo)`, indices por `project_id + Semana + unique_id/row_id` | Correcto; `row_id` es el ID moderno para acciones puntuales. |
| `programacion_semanal` | `row_id`, `Consecutivo`, `unique_id` | `PRIMARY (project_id, Consecutivo)`, indices por `project_id + Semana + unique_id/row_id` | Correcto; APIs deben editar por `project_id + row_id`. |
| `pdc` | `pdc_row_id`, `consecutivo` | `PRIMARY (project_id, consecutivo)`, indice `project_id + pdc_row_id` | Correcto; `pdc_row_id` es el ID estable moderno y `consecutivo` queda como companion legacy. |
| `papelera_pdc` | `pdc_row_id`, `consecutivo` | `PRIMARY (project_id, consecutivo)` | Correcto para archivo. |
| `cambios` | `id` | `PRIMARY (project_id, id)` | Normalizado por `20260704_cambios_project_scope.php`; el controlador genera `id` por proyecto si el formulario no lo envia. |
| `cic` | `Id` | Debe tratarse como `project_id + Id`; metricas por `project_id + Semana + subcontratista` | El contrato nuevo usa `project_id`; algunos esquemas antiguos tienen `PRIMARY Id`, por eso las queries deben filtrar siempre. |
| `cip` | `Id` | Debe tratarse como `project_id + Id`; metricas por `project_id + Semana + profesional` | Igual que `cic`: filtrar siempre por proyecto. |
| `indicadores_generales` | `id` | Debe tratarse como `project_id + Semana + subcontratista_profesional + rol` | ID fisico puede ser global; las consultas runtime deben usar `project_id`. |
| `actividades` | `Id` | `project_id + Id` | Correcto en contrato global; `Database::insertProjectId()` conoce este ID scoped. |
| `profesionales` | `id` | `PRIMARY (project_id, id)` | Correcto en contrato global; dependencias se reemplazan por nombre dentro del proyecto. |
| `subcontratistas` | `Id` | `PRIMARY (project_id, Id)`, `UNIQUE (project_id, subcontratista)` | Correcto en contrato global. |

## Tablas de trazabilidad y automatizacion

| Tabla | ID operativo | Clave/alcance esperado | Estado |
|---|---:|---|---|
| `actividad_programa_fuentes` | `id` y `project_id + actividad_id + semana + programa_unique_id` | Llave de negocio scoped por proyecto | Tiene `PRIMARY id` global; riesgo bajo si se usa la llave unica scoped, pero conviene no depender de `id` como identificador visible. |
| `auto_contrato_log` | `id`, `batch_id` | Log por `project_id + id`, `batch_id` global | OK para auditoria; no usar `id` sin `project_id` en runtime. |
| `auto_program_log` | `id`, `unique_id` | `UNIQUE (project_id, semana, consecutivo, accion)` | OK; `id` es tecnico. |
| `pg_tracking` | `consecutivo_en_programa`, `semana` | `project_id + consecutivo_en_programa + semana` | OK para seguimiento. |
| `lps_drawer_comentarios` | `id` | Comentarios por `project_id + consecutivo + semana` | `id` es tecnico; filtrar por `project_id`. |
| `lps_escalamientos` | `id` | Escalamientos por `project_id + consecutivo + semana` | `id` es tecnico; filtrar por `project_id`. |
| `pi_shared_constraints` | `Id` | Restriccion compartida por `project_id + Id` | OK si runtime filtra por proyecto. |
| `pi_shared_constraint_links` | `Id` | Link por `project_id + Id` | OK si runtime filtra por proyecto. |
| `semi_auto_runs` | `run_id` | `run_id` global, siempre combinado con `project_id` en consultas | OK. |
| `semi_auto_suggestions` | `suggestion_id` | `suggestion_id` global, siempre combinado con `project_id` en consultas | OK. |
| `semi_auto_decisions` | `decision_id` | `decision_id` global, siempre combinado con `project_id` en consultas | OK. |
| `semi_auto_feedback` | `id` | Feedback por `project_id + module + run/suggestion` | OK; `id` tecnico. |
| `semi_auto_project_config` | `project_id + module` | `PRIMARY (project_id, module)` | Correcto. |
| `semi_auto_learning_candidates` | `candidate_id` | `candidate_id` global, consultas por `project_id` | OK. |
| `semi_auto_learning_rules` | `rule_id` | `rule_id` global, consultas por `project_id` | OK. |
| `semi_auto_proactive_queue` | `item_id` | `item_id` global, consultas por `project_id` | OK. |
| `semi_auto_assistant_feedback` | `feedback_id` | `feedback_id` global, consultas por `project_id` | OK. |

## Regla de implementacion

- Para editar o borrar filas visibles al usuario: usar `WHERE project_id = ? AND <id_visible> = ?`.
- Para inserts en tablas con ID visible scoped: calcular `MAX(id) + 1` dentro del proyecto o usar `Database::insertProjectId()` solo si la tabla fisica permite ese contrato.
- Para logs con UUID/string global (`run_id`, `suggestion_id`, `decision_id`): mantener `project_id` en todo `SELECT/UPDATE/DELETE` aunque el identificador parezca unico.
- No usar `{prefix}_*` en runtime; solo migraciones, respaldos o tablas archivadas.
