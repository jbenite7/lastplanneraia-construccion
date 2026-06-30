# Arquitectura De Tablas Globales

## Estado

La app usa un modelo unificado de tablas operativas compartidas por `project_id`.
El modelo anterior de tablas por proyecto (`{prefix}_tabla`) queda soportado solo
como rollback temporal.

Fuente canonica del DDL:

- `database/migrations/20260630_global_tables_contract.sql`

Tablas del contrato:

- `actividades`
- `auto_contrato_log`
- `auto_program_log`
- `cambios`
- `cic`
- `cip`
- `indicadores_generales`
- `lps_drawer_comentarios`
- `lps_escalamientos`
- `papelera_pdc`
- `pdc`
- `pg_tracking`
- `pi_shared_constraint_links`
- `pi_shared_constraints`
- `profesionales`
- `programa`
- `programa_consolidado`
- `programacion_semanal`
- `semanas_activas`
- `subcontratistas`

Todas deben existir en la base activa y tener columna `project_id`.

## Resolucion En Runtime

`src/Core/Database.php` es la capa de compatibilidad.

- Con `USE_GLOBAL_TABLES=true`, cualquier SQL que use `{prefix}_tabla` para una
  tabla del contrato se reescribe a la tabla global y se filtra por `project_id`.
- Si `USE_GLOBAL_TABLES` no esta definido, el modo global se activa cuando existe
  `semanas_activas`.
- Con `USE_GLOBAL_TABLES=false`, el resolver intenta la tabla legacy directa
  `{prefix}_tabla`; si no existe, usa `zleg_{prefix}_tabla`.

El codigo productivo no debe crear tablas `{prefix}_*` cuando global esta activo.
`admin/src/Models/Project.php` mantiene la creacion legacy solo para rollback OFF.

## IDs

Las tablas con PK compuesta `project_id + id/consecutivo` reciben IDs por proyecto
en el resolver cuando el `INSERT` no provee ese valor. Las tablas con
`AUTO_INCREMENT` global dejan que MySQL asigne el ID.

`INSERT ... SELECT` tambien esta cubierto: el resolver agrega `project_id`, genera
IDs por proyecto cuando aplica y filtra la fuente si la fuente tambien es global.

## Lifecycle De Proyectos

En modo global:

- Crear proyecto no crea tablas por proyecto.
- Renombrar prefijo no renombra tablas operativas.
- Eliminar proyecto borra filas por `project_id` en las 20 tablas globales y luego
  elimina el registro de `general_proyectos_procesos`.
- Integridad admin valida la existencia de las 20 tablas globales, no tablas
  prefijadas.

## Rollback Temporal

Rollback runtime:

```bash
docker compose exec -e USE_GLOBAL_TABLES=false app php ...
```

El resolver OFF permite leer/escribir archivos legacy `zleg_{prefix}_tabla` cuando
no existe `{prefix}_tabla`. Esto permite drill ON -> OFF -> ON sin recrear aliases.

No se debe dejar rollback OFF activo indefinidamente: es un puente temporal para
incidentes y comparacion.

## Migracion Y Reconciliacion

Backfill idempotente desde archivos `zleg_*`:

```bash
docker compose exec app php database/migrations/20260630_backfill_global_tables_from_zleg.php
docker compose exec app php database/migrations/20260630_backfill_global_tables_from_zleg.php --apply
```

El dry-run debe terminar en:

```text
No hay filas faltantes para backfill.
```

Gate de reconciliacion:

```bash
docker compose exec app php tests/test_global_table_reconciliation.php
```

Este gate verifica que no existan claves legacy `zleg_*` sin equivalente global
por `project_id`.

## Gates Obligatorios

```bash
docker compose exec app php tests/test_global_table_safety.php
docker compose exec app php tests/test_global_table_reconciliation.php
npx playwright test tests/browser/full-app-flow.spec.mjs --workers=1
npx playwright test tests/browser/preconstruccion-full-cycle.mjs --workers=1
npx playwright test tests/browser/test-pdc.mjs tests/browser/auto-definir-contratos.mjs --workers=1
docker compose exec app php tests/test_auto_definir_contratos.php
docker compose exec app php tests/test_pi_shared_payload_smoke.php
docker compose exec app php tests/test_weekly_governance.php
docker compose exec app vendor/bin/phpstan analyse src admin/src --memory-limit=1G
```

Para auditoria dinamica SQL, activar `mysql.general_log` en modo TABLE, correr la
suite principal y verificar cero queries a tablas `{prefix}_*` migradas.

## Suite E2E Reutilizable

La fuente de verdad de proyectos esta en:

- `tests/browser/fixtures/projects.mjs`

Para agregar otro proyecto con modulos existentes, agregar un objeto a `PROJECTS`.
Los flujos compartidos viven en:

- `tests/browser/support/session.mjs`
- `tests/browser/support/assertions.mjs`
- `tests/browser/support/dbSnapshot.mjs`
- `tests/browser/support/moduleFlows.mjs`

La suite principal es:

- `tests/browser/full-app-flow.spec.mjs`
