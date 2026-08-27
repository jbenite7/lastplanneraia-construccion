FROM mysql:8.0.40
COPY database/migrations/20260630_global_tables_contract.sql /docker-entrypoint-initdb.d/001-global-schema.sql
COPY database/patches/001_create_new_tables.sql /docker-entrypoint-initdb.d/002-rbac-schema.sql
COPY database/fixtures/design-system-ci.sql /docker-entrypoint-initdb.d/003-design-system-ci.sql
COPY database/migrations/20260702_semi_auto_global_tables.sql /docker-entrypoint-initdb.d/004-semi-auto-global.sql
COPY database/migrations/20260704_semi_auto_assistant_tables.sql /docker-entrypoint-initdb.d/005-semi-auto-assistant.sql
COPY database/migrations/20260703_contratos_slot_quantities_traceability.sql /docker-entrypoint-initdb.d/006-contract-quantities.sql
COPY database/migrations/20260705_actividad_programa_fuentes.sql /docker-entrypoint-initdb.d/007-activity-sources.sql
COPY database/migrations/002_bi_forecast_tables.sql /docker-entrypoint-initdb.d/008-bi-forecast.sql
COPY database/migrations/003_bi_action_queue.sql /docker-entrypoint-initdb.d/009-bi-action-queue.sql
COPY database/patches/20260612_pdc_familias_maestro.sql /docker-entrypoint-initdb.d/010-family-catalog-base.sql
COPY database/patches/20260701_da_porto_feedback_semi_auto.sql /docker-entrypoint-initdb.d/011-family-catalog-feedback.sql
COPY database/migrations/20260706_family_catalog_refactor.sql /docker-entrypoint-initdb.d/012-family-catalog-refactor.sql
COPY database/migrations/20260707_da_porto_jmc_family_patterns.sql /docker-entrypoint-initdb.d/013-family-patterns.sql
COPY database/migrations/20260708_contract_defaults_feedback.sql /docker-entrypoint-initdb.d/014-contract-defaults.sql
COPY database/migrations/20260709_inactivate_alias_contractual_families.sql /docker-entrypoint-initdb.d/015-contractual-aliases.sql
COPY database/migrations/20260710_equipment_families_require_review.sql /docker-entrypoint-initdb.d/016-equipment-review.sql
COPY database/migrations/20260711_apply_human_family_decisions.sql /docker-entrypoint-initdb.d/017-human-decisions.sql
COPY database/fixtures/design-system-ci-normalize.sql /docker-entrypoint-initdb.d/018-design-system-ci-normalize.sql
COPY database/fixtures/design-system-ci-pdc-v2.sql /docker-entrypoint-initdb.d/019-pdc-v2-schema.sql
COPY database/bi/001_bi_pg_semana.sql /docker-entrypoint-initdb.d/101-bi-view.sql
COPY database/bi/002_bi_pi_restricciones.sql /docker-entrypoint-initdb.d/102-bi-view.sql
COPY database/bi/003_bi_ps_compromisos.sql /docker-entrypoint-initdb.d/103-bi-view.sql
# 104 retirado el 2026-08-04 con el PDC v1: database/bi/004_bi_pdc_general.sql se borro y este COPY
# rompia el build de la imagen de CI. La vista bi_pdc_general no tiene lectores — el informe de
# compras se alimenta del PDC v2 (ver src/Services/ControlTowerService.php:522).
COPY database/bi/005_bi_cic_contratistas.sql /docker-entrypoint-initdb.d/105-bi-view.sql
COPY database/bi/006_bi_cip_responsables.sql /docker-entrypoint-initdb.d/106-bi-view.sql
COPY database/bi/007_bi_curva_s_duracion.sql /docker-entrypoint-initdb.d/107-bi-view.sql
COPY database/bi/008_bi_riesgos.sql /docker-entrypoint-initdb.d/108-bi-view.sql
COPY database/bi/009_bi_control_tower_summary.sql /docker-entrypoint-initdb.d/109-bi-view.sql
COPY database/bi/010_bi_lineage.sql /docker-entrypoint-initdb.d/110-bi-view.sql

# B-9 (2026-08-07): el fixture declaraba `general_proyectos_procesos` con un esquema derivado del
# real —sin AUTO_INCREMENT en la PK y sin `fechaInicioLineaBase`, `fechaFinLineaBase` ni
# `costoDiaRetraso`—, y por eso crear proyecto desde el panel de Admin moria con un 500 que
# `e2e/tests/admin/proyectos-crud.spec.mjs` no podia pasar por mas que se arreglara el codigo.
# En vez de parchear el fixture a mano, se aplica LA MIGRACION: una sola fuente de verdad, y de
# paso cada build de CI comprueba que la migracion hace lo que dice. Va al final, despues de que
# el fixture haya creado la tabla, porque sus ALTERs son condicionales.
COPY database/migrations/20260807_proyectos_lineabase_columns.sql /docker-entrypoint-initdb.d/120-proyectos-lineabase.sql

# 2026-08-24: `general_flags` llego con el interruptor del Control Tower (migracion del 20-08 +
# `FlagsService` + dos suites), pero la imagen de CI nunca la sembro. Resultado: `main` en rojo
# desde el 2026-08-21 con «Table 'lastplanneraia_ci.general_flags' doesn't exist» en
# test_bi_preview_gate.php (21/22) y test_flags_service.php (4/7) — 74 de 76 pasan y el paso
# muere ahi, ANTES del piloto visual, que por eso lleva sin correr en CI el mismo tiempo.
#
# Se aplica LA MIGRACION, no un CREATE TABLE a mano, por el mismo criterio que el bloque de
# arriba: una sola fuente de verdad, y cada build de CI comprueba de paso que la migracion hace
# lo que dice. Es idempotente (IF NOT EXISTS + INSERT IGNORE), asi que re-correrla no daña.
COPY database/migrations/20260820_general_flags.sql /docker-entrypoint-initdb.d/121-general-flags.sql

# La siembra de la linea base contractual, y va DESPUES de 120 y de 121 a proposito: aquella crea las
# columnas y esta las rellena. Sin este paso el CI tendria las columnas vacias, la fecha
# contractual saldria NULL y `baseline-drift` seguiria rojo aunque el calculo fuera correcto —
# que es exactamente lo que paso al implementar, y lo que obligo a que el sembrado fuera migracion
# en vez de script PHP. Es write-once: reejecutarla no pisa una linea base ya declarada.
COPY database/migrations/20260819_sembrar_linea_base_contractual.sql /docker-entrypoint-initdb.d/122-sembrar-linea-base.sql

# 2026-08-27: pi_shared_constraints llego con las 5 columnas de gestion de CT-7.3
# (ResponsableAsignado, FechaCompromiso, EstadoLiberacion, AsignadoPor, AsignadoEn — Task 4 del
# piloto, autorizado por Felipe, ver la cabecera de la propia migracion), pero el fixture de CI
# nunca la sembro. Mismo patron que B-9 (120) y general_flags (121) de arriba: sin esta linea,
# test_constraints_gestion_schema.php (Task 4) y cualquier prueba end-to-end del panel de
# gestion de Intermedia (Task 7/8) fallan en CI con columnas inexistentes, aunque el codigo y la
# base de dev esten correctos. Se aplica LA MIGRACION, idempotente (guardada por
# information_schema.COLUMNS), no un ALTER a mano.
COPY database/migrations/20260827_pi_shared_constraints_gestion.sql /docker-entrypoint-initdb.d/123-shared-constraints-gestion.sql

# 2026-08-27: pg_avance_edicion_manual llego el 2026-08-26 (commit 8559ba68, feat(pg): tabla de
# bitacora del avance editado a mano) -- ajena a la Torre piloto, pero mismo patron que 120/121/123
# de arriba: el fixture de CI nunca la sembro. Sin esta linea, test_bitacora_avance_endpoint.php
# falla en CUALQUIER PR contra main con "Table 'pg_avance_edicion_manual' doesn't exist", no solo
# en el de la Torre piloto. CREATE TABLE IF NOT EXISTS, idempotente.
COPY database/migrations/20260826_pg_avance_edicion_manual.sql /docker-entrypoint-initdb.d/124-avance-edicion-manual.sql

# 2026-08-27: Ejecutado_Carryover llego el 2026-08-25 (commit 7a408162, fix del arrastre de
# avance semanal) -- ajena a la Torre piloto, mismo patron otra vez: sin esta columna,
# WeeklyRealProgressCarryoverService (y CarryoverAvanceSemanalTest.php, PHPUnit) fallan en
# CUALQUIER PR contra main con "Unknown column 'Ejecutado_Carryover'". Aditiva, sin backfill.
COPY database/migrations/20260825_carryover_testigo.sql /docker-entrypoint-initdb.d/125-carryover-testigo.sql
