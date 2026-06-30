# validar-migracion-project-schema

## Estado Actual

Estado: cerrado para el corte de tablas globales por `project_id`.

El plan original proponia tablas destino `project_*`. Esa direccion quedo
superseded por el contrato final implementado:

- 20 tablas operativas globales con el mismo nombre funcional (`programa`,
  `programa_consolidado`, `pdc`, etc.).
- Todas las tablas operativas incluyen `project_id`.
- `Database` reescribe consultas legacy `{prefix}_tabla` hacia tabla global y
  filtro `project_id`.
- Rollback temporal OFF usa `{prefix}_tabla` si existe, o `zleg_{prefix}_tabla`
  si la tabla legacy directa no existe.

Documento canonico:

- `docs/global-tables-architecture.md`

DDL canonico:

- `database/migrations/20260630_global_tables_contract.sql`

## Evidencia De Cierre

Gates agregados:

- `tests/test_global_table_safety.php`
- `tests/test_global_table_reconciliation.php`
- `database/migrations/20260630_backfill_global_tables_from_zleg.php`

Validaciones ejecutadas:

- `docker compose exec app php tests/test_global_table_safety.php` -> OK
- `docker compose exec app php tests/test_global_table_reconciliation.php` -> OK
- `docker compose exec app php database/migrations/20260630_backfill_global_tables_from_zleg.php` -> sin filas faltantes
- `npx playwright test tests/browser/full-app-flow.spec.mjs --workers=1` -> 25 passed
- `npx playwright test tests/browser/preconstruccion-full-cycle.mjs --workers=1` -> 21 passed
- `npx playwright test tests/browser/test-pdc.mjs tests/browser/auto-definir-contratos.mjs --workers=1` -> 6 passed, 1 skipped esperado
- `docker compose exec app php tests/test_auto_definir_contratos.php` -> 21 passed
- `docker compose exec app php tests/test_pi_shared_payload_smoke.php` -> OK
- `docker compose exec app php tests/test_weekly_governance.php` -> OK
- `docker compose exec app vendor/bin/phpstan analyse src admin/src --memory-limit=1G` -> OK

Auditoria dinamica:

- `mysql.general_log` durante `full-app-flow.spec.mjs`
- Resultado: 0 queries legacy prohibidas a tablas migradas `da_porto_*` /
  `da_aeropuerto_pc_*`.

## Pendientes Fuera De Este Corte

No forman parte del corte actual:

- Eliminar definitivamente rollback OFF.
- Eliminar archivos `zleg_*` luego de ventana formal de retencion.
- Refactors de repositorios dedicados si se decide separar `Database` en capas
  especificas por modulo.
- Sustituir reporterias persistidas `general_*` por calculo on-demand.
