# Global Patch Equivalents

This directory contains global-table equivalents of the per-project SQL patches in `../`.

## Purpose

When migrating from per-project tables (`{prefix}_programa`, `{prefix}_actividades`, etc.) to global tables with `project_id`, the original patches need adapted versions that target the global schema.

## Patch Mapping

| # | Original Patch | Global Equivalent | Status |
|---|---|---|---|
| 1 | `001_create_new_tables.sql` | — | No change needed (only `general_*`/`rbac_*` tables) |
| 2 | `002_alter_existing_tables.sql` | — | No change needed (only `general_*` tables) |
| 3 | `003_seed_rbac_data.sql` | — | No change needed (only `rbac_*`/`general_*`) |
| 4 | `004_patch_rbac_role_permissions.sql` | — | No change needed (only `rbac_*`) |
| 5 | `20260325_add_force_password_change.sql` | — | No change needed (only `general_usuarios`) |
| 6 | `20260327_add_user_active_flag.sql` | — | No change needed (only `general_usuarios`) |
| 7 | `20260329_create_password_reset_tokens.sql` | — | Already global table (no prefix) |
| 8 | `20260525_lps_drawers_construccion.sql` | `20260525_lps_drawers_construccion_global.sql` | Applied-legacy: tables/columns exist from `001_create_global_tables.sql` |
| 9 | `20260527_rbac_g_s_sg_lps_read_permissions.sql` | — | No change needed (only `rbac_*`) |
| 10 | `20260527_rbac_semana_eliminar_permission.sql` | — | No change needed (only `rbac_*`) |
| 11 | `20260527_remove_adelantada_state.sql` | — | No change needed (only `general_proyectos_procesos`) |
| 12 | `20260527_remove_no_requerida_state.sql` | — | No change needed (only `general_proyectos_procesos`) |
| 13 | `20260527_rename_debe_iniciar.sql` | — | No change needed (only `general_proyectos_procesos`) |
| 14 | `20260528_add_fecha_ultimo_saneo.sql` | `20260528_add_fecha_ultimo_saneo_global.sql` | Column already in global `semanas_activas` |
| 15 | `20260528_patch_rbac_subcontratistas_editar.sql` | — | No change needed (only `rbac_*`) |
| 16 | `20260528_rbac_semana_crear_permission.sql` | — | No change needed (only `rbac_*`/`general_*`) |
| 17 | `20260529_create_auto_program_log.sql` | `20260529_create_auto_program_log_global.sql` | Global table exists from `001_create_global_tables.sql` |
| 18 | `20260529_monitor_cambios_pg_tracking.sql` | `20260529_monitor_cambios_pg_tracking_global.sql` | Global table exists from `001_create_global_tables.sql` |
| 19 | `20260602_add_reprogramada_por_usuario_flag.sql` | `20260602_add_reprogramada_por_usuario_flag_global.sql` | Column already in global `programacion_semanal` |
| 20 | `20260602_add_reprogramada_por_usuario_flag_production.sql` | `20260602_add_reprogramada_por_usuario_flag_production_global.sql` | Column already in global `programacion_semanal` |
| 21 | `20260609_drop_licify.sql` | `20260609_drop_licify_global.sql` | DROP columns from global `pdc` (no-op — never in global schema) |
| 22 | `20260610_pdc_plantillas.sql` | — | No change needed (only `general_*` tables) |
| 23 | `20260611_pdc_auto_generate_rbac.sql` | — | No change needed (only `rbac_*`) |
| 24 | `20260611_pdc_mapping.sql` | — | No change needed (only `general_*` tables) |
| 25 | `20260612_drop_licify_all_pdc_tables.sql` | `20260612_drop_licify_all_pdc_tables_global.sql` | DROP from global `pdc` (no-op) + general tables |
| 26 | `20260612_pdc_consolidado_full.sql` | `20260612_pdc_consolidado_full_global.sql` | RBAC + drop legacy tables + Licify cleanup |
| 27 | `20260612_pdc_familias_maestro.sql` | — | No change needed (only `general_*` tables) |
| 28 | `20260613_pdc_estrategia_agrupacion.sql` | — | No change needed (only `general_*` table) |
| 29 | `20260614_new_families.sql` | — | No change needed (only `general_*` tables) |
| 30 | `20260615_regex_expansions.sql` | — | No change needed (only `general_*` tables) |
| 31 | `20260619_create_matching_config.sql` | — | No change needed (only `general_*` table) |
| 32 | `20260622_add_tnp_cp_columns.sql` | `20260622_add_tnp_cp_columns_global.sql` | Columns already in global `programacion_semanal` |
| 33 | `20260622_create_decision_log.sql` | — | No change needed (only `general_*` table) |
| 34 | `20260623_auto_definir_contratos_setup.sql` | `20260623_auto_definir_contratos_setup_global.sql` | RBAC + columns verify on global `actividades` |
| 35 | `20260623_chapter_category_map.sql` | — | No change needed (only `general_*` table) |
| 36 | `20260623_create_auto_contrato_log.sql` | `20260623_create_auto_contrato_log_global.sql` | **Creates** global `auto_contrato_log` with `project_id` |
| 37 | `20260623_oc_add_columns.sql` | `20260623_oc_add_columns_global.sql` | Columns already in global `actividades` |
| 38 | `20260624_consolidar_cnc_cnp.sql` | `20260624_consolidar_cnc_cnp_global.sql` | Part 1 (migrate to `general_cnc`) applies; Part 2 (DROP per-project tables) is legacy |
| 39 | `20260624_fix_pdc_mojibake.sql` | — | No change needed (only `general_*` table) |
| 40 | `20260624_pc_cnc_cnp_catalog.sql` | — | No change needed (only `general_cnc`) |
| 41 | `20260624_pc_config_columns.sql` | — | No change needed (only `general_proyectos_procesos`) |
| 42 | `20260624_pc_reponer_tablas_faltantes.sql` | `20260624_pc_reponer_tablas_faltantes_global.sql` | All 16 global tables verified from `001_create_global_tables.sql` |
| 43 | `20260624_preconstruccion_schema.sql` | — | No change needed (only `general_proyectos_procesos`) |
| 44 | **Nuevo** | `20260706_add_project_id_to_programacion_semanal.sql` | **Agrega `project_id` a `programacion_semanal` en producción** |

## Pattern Summary

| Original Pattern | Global Equivalent |
|---|---|
| `CREATE TABLE {prefix}_X (...)` | Table already exists globally (skip/verify) |
| `ALTER TABLE {prefix}_X ADD COLUMN ...` | `ALTER TABLE X ADD COLUMN ...` with `IF NOT EXISTS` guard |
| `ALTER TABLE {prefix}_X DROP COLUMN ...` | `ALTER TABLE X DROP COLUMN ...` with existence check |
| Stored proc iterating projects to ALTER | Single ALTER on global table |
| `INSERT INTO general_X ...` | Same (already global) |
| `INSERT INTO rbac_X ...` | Same (already global) |
| `{db}_auto_contrato_log` (new table) | `CREATE TABLE auto_contrato_log` with `project_id` |
| DROP per-project `{prefix}_cnc` | No-op (not global tables) |

## Usage

Apply in order matching the original patch sequence when setting up the global schema:

```sql
SOURCE database/patches/global/20260525_lps_drawers_construccion_global.sql
SOURCE database/patches/global/20260528_add_fecha_ultimo_saneo_global.sql
-- ... etc.
```

These are safe to run after `001_create_global_tables.sql` has been applied — all ALTERs have `IF NOT EXISTS` guards.
