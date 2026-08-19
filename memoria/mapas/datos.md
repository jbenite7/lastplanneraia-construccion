---
capa: wiki
tipo: mapa
estado: vigente
fecha: 2026-08-19
areas: [datos]
fuente: docs/global-tables-architecture.md, AGENTS.md, las trampas del área
resumen: "Tablas globales aisladas por project_id: qué manda antes de tocar schema, y por qué casi todo bug de datos parece de código"
---

# Mapa · Datos

## Qué manda

`docs/global-tables-architecture.md` — **se lee antes de tocar schema, migraciones, backfills,
limpieza o lifecycle de proyectos**. Y [[AGENTS|AGENTS.md]] §Arquitectura y datos, que fija el
procedimiento: dry-run primero; cualquier aplicación o borrado exige gate, respaldo verificable,
estrategia de restauración y reconciliación posterior.

## La idea que ordena el área

**Tablas globales compartidas, aisladas por `project_id` en toda consulta operativa.**
`Base_de_Datos`, `dbPrefix` y `{prefix}_*` son compatibilidad histórica, no permiso para escribir
SQL dinámico nuevo. El acceso va por `src/Core/Database.php`, con prepared statements y nada más.

## Antes de tocar

- `docker compose exec app php tests/test_global_table_safety.php` y
  `…/test_global_table_reconciliation.php` son los dos comandos canónicos del área.
- **Hay dos stacks Docker con MySQL propio.** Conectarse al equivocado explica más «bugs» de datos
  de los que parece.

## Trampas

- [[dos-stacks-docker]] — el error más caro del área, porque no falla: responde otra cosa.
- [[el-healthcheck-de-db-responde-al-servidor-temporal]] — verde contra el servidor equivocado.
- [[stack-principal-migraciones-pdc-pendientes]] — replayar migraciones PDC contra HEAD tiene coste.
- [[mojibake-es-dato-no-codigo]] — se persiguió en el código y estaba en la base.
- [[fixture-huerfano-de-programa-consolidado]] y
  [[fijar-un-dato-de-la-base-en-un-test-lo-podre]] — sembrar mal es sembrar deuda.

## Dónde vive

- [[integracion]] — procesa reportes externos con `ReportProcessor`.
- [[espejo-y-reparacion-unique-id]] — la operación producción→local→pruebas del 2026-08-12.
- [[no-enriquecer-daporto-para-medir]] — por qué no se toca el proyecto real para tener línea base.

## El área, en una tabla

<!-- Vista nativa de Obsidian Bases. Si no renderiza, el contenido de arriba sigue siendo
     legible: los plugins y las vistas amplifican, no sostienen. -->
![[area-datos.base]]

## Vecinos

[[arquitectura]] para dónde vive la capa de datos · [[entorno-y-despliegue]] para los stacks ·
[[pdc]], que es el módulo con más deuda de datos conocida.
