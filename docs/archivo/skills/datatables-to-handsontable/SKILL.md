---
capa: fuente
tipo: guia
estado: vigente
fecha: 2026-09-03
areas: [design-system, proceso]
tags: [archivo]
fuente: docs/archivo/skills/datatables-to-handsontable/SKILL.md
resumen: Treat this as a behavior and data-contract migration, not a library swap. Confirm the Handsontable version and license terms from current project sources…
name: datatables-to-handsontable
description: Use when migrating an existing DataTables grid to Handsontable, especially with PHP, MySQL, cPanel, or shared-hosting constraints.
---

# DataTables to Handsontable

Treat this as a behavior and data-contract migration, not a library swap. Confirm the Handsontable version and license terms from current project sources before implementation.

## Discover and map

Inventory DataTables configuration, server-side processing, columns and renderers, filtering, ordering, pagination, selection, export, responsive behavior, extensions, events, accessibility, and saved user state. Trace every Ajax endpoint and SQL query.

Create a parity matrix with these columns:

| Existing behavior | DataTables source | Handsontable approach | Backend impact | Parity | Acceptance test |
|---|---|---|---|---|---|

Mark parity as direct, partial, redesigned, or intentionally removed. Get agreement on gaps before editing.

## Design the migration

Define versioned JSON contracts for reads and writes, stable row identifiers, editable fields, validation errors, optimistic locking or conflict behavior, and batch limits. Decide from measured dataset size whether filtering, ordering, and paging remain server-side.

For PHP/MySQL endpoints:

- Require authentication and row-level authorization.
- Validate JSON shape and editable-field allowlists.
- Protect state changes with CSRF controls appropriate to the application.
- Use PDO prepared statements for values and allowlists for SQL identifiers.
- Escape rendered HTML, neutralize spreadsheet formula injection in exports, and return consistent status/error envelopes.
- Use transactions for batch edits and report per-row conflicts without silently losing data.

## Implement incrementally

1. Add a feature flag or route that preserves the DataTables path.
2. Ship a read-only Handsontable grid against the agreed contract.
3. Add renderers, selection, filtering, ordering, and export according to the parity matrix.
4. Add validated writes and conflict handling last.
5. Test representative data volume, permissions, keyboard use, browsers, PHP limits, and failure recovery.

Avoid requiring server-side Node.js, workers, or Docker on shared hosting. Bundle only assets permitted by the project's dependency and licensing strategy.

## Release and rollback

Canary the new grid, monitor endpoint errors and save conflicts, and keep schemas backward-compatible through the rollback window. Document the switch that restores DataTables, database reversal steps if any, asset/cache cleanup, owners, and rollback triggers. Remove the old path only after parity acceptance and a stable observation period.
