# Sistema de Unique IDs en Tablas Globales

## Propósito

El sistema de `unique_id` permite rastrear una actividad a través de las diferentes capas del Last Planner System:

- **Programa General (PG)** → plan maestro
- **Programa Consolidado** → lookahead / ventanas semanales
- **Programación Semanal (PS)** → compromisos semanales
- **Entidades hijas** → comentarios, escalamientos, tracking, restricciones compartidas

## Identificadores

Tres tipos de identificadores conviven según la tabla:

| Tipo | Propósito | Tablas |
|------|-----------|--------|
| `unique_id` | Intersección de actividad entre tablas (FK CASCADE) | programa, programa_consolidado, programacion_semanal, lps_drawer_comentarios, lps_escalamientos, pg_tracking, pi_shared_constraint_links, auto_program_log |
| `row_id` | Identificador de fila individual (PK real en tablas con múltiples filas por actividad) | programa_consolidado, programacion_semanal |
| `pdc_row_id` | Similar a `row_id` pero específico de tablas PDC | pdc, papelera_pdc |

### Columnas legacy sincronizadas por triggers

| Tabla | unique_id/row_id | Columna legacy sincronizada |
|-------|-----------------|---------------------------|
| programa | `unique_id` | `Consecutivo` |
| programa_consolidado | `row_id`, `unique_id` | `Consecutivo`, `Consecutivo_en_Programa` |
| programacion_semanal | `row_id`, `unique_id` | `Consecutivo`, `Consecutivo_En_Programa` |
| lps_drawer_comentarios | `unique_id` | `consecutivo_en_programa` |
| lps_escalamientos | `unique_id` | `consecutivo_en_programa` |
| pg_tracking | `unique_id` | `consecutivo_en_programa` |
| pi_shared_constraint_links | `unique_id` | `ConsecutivoEnPrograma` |
| auto_program_log | `unique_id` | `consecutivo` |
| pdc | `pdc_row_id` | `consecutivo` |
| papelera_pdc | `pdc_row_id` | `consecutivo` |

Los triggers `trg_{tabla}_unique_id_INSERT` y `trg_{tabla}_unique_id_UPDATE` mantienen ambas columnas sincronizadas automáticamente.

## Relaciones FK

Todas las tablas con `unique_id` tienen FK CASCADE hacia `programa(project_id, unique_id)`:

```
programa (project_id, unique_id)
  ├── programa_consolidado (fk_pc__programa__unique_id)
  ├── programacion_semanal (fk_ps__programa__unique_id)
  ├── lps_drawer_comentarios (fk_ldc__programa__unique_id)
  ├── lps_escalamientos (fk_le__programa__unique_id)
  ├── pg_tracking (fk_pgt__programa__unique_id)
  ├── pi_shared_constraint_links (fk_pscl__programa__unique_id)
  └── auto_program_log (fk_apl__programa__unique_id)
```

## Secuencias

`program_unique_id_sequences(project_id, next_unique_id)` es la tabla de secuencias que genera el próximo `unique_id` disponible por proyecto.

## Tablas SIN unique_id (id secuencial simple por proyecto)

| Tabla | PK | Scoped por |
|-------|----|-----------|
| actividades | `Id` (int, auto_increment) | `project_id` |
| cambios | `id` (int, auto_increment) | `project_id` |
| cic | `Id` (int, auto_increment) | `project_id` |
| cip | `Id` (int, auto_increment) | `project_id` |
| subcontratistas | `Id` (int, auto_increment) | `project_id` |
| profesionales | `id` (int, auto_increment) | `project_id` |
| semanas_activas | `Id` (int, auto_increment) | `project_id` |
| pi_shared_constraints | `Id` (bigint, auto_increment) | `project_id` |
| semi_auto_* | `id` (int, auto_increment) | `project_id` |
| indicadores_generales | (sin PK única — compuesta por proyecto+seman) | `project_id` |
| auto_contrato_log | `id` (int, auto_increment) | `project_id` |

Estas tablas usan `id` auto-incremental por proyecto. El `id` NO es una clave única global — solo es única dentro del mismo `project_id`. Las consultas SIEMPRE deben incluir `WHERE project_id = ?`.

## Migración de referencia

La migración completa está en:
- `database/migrations/20260703_program_unique_id_refactor.php` (aplicación de columnas + triggers + FK)
- `database/migrations/20260630_global_tables_contract.sql` (contrato canónico con todas las tablas)

## Reglas de query

1. **JOIN entre tablas con unique_id**: usar `ON a.project_id = b.project_id AND a.unique_id = b.unique_id`
2. **JOIN entre tabla con unique_id y tabla sin unique_id**: usar `ON a.project_id = b.project_id AND a.id_col = b.id_col`
3. **Siempre** incluir `WHERE project_id = ?` aunque se use `queryWithProject()` (defense-in-depth)
4. No asumir que `id` es único global — siempre scoped por `project_id`