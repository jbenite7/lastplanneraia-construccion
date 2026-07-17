# Gates Sprint 04 — Programa General

## Técnicos

- `node tests/test_programa_general_sprint_contract.mjs`: PASS después de RED confirmado.
- `node --check public/js/modules/programa_general/hot.js`: PASS.
- `docker compose exec -T app php -l views/programa-general/programa_general.view.php`: PASS.
- `node scripts/design-system-audit.mjs`: PASS contra baseline; presupuesto PG en cero.
- `git diff --check` del slice: PASS.

## Runtime nativo

- Matriz dark/linen en 390x844, 1180x820 y 1440x900: PASS.
- Filtros y estado `aria-pressed`: PASS por click y teclado.
- Cards/tabla, persistencia y restauración: PASS.
- Consola, overflow y targets: PASS; entrega estable sin errores y dos eventos transitorios de estrés documentados.
- Evidencia y video nativos: PASS; seis capturas de matriz, cinco del flujo y MP4.
- Permisos: administrador PASS; `test.C` es solo lectura según contrato vigente, no rechazo de ruta. Observación registrada sin cambiar RBAC.

## Cierre

- Datos locales restaurados: PASS; hash vivo/snapshot `f42f80…be82`.
- Stage limitado a PG, prueba y expediente: PASS.
- Commit atómico sin push: PASS.
