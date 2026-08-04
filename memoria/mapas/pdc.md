---
tipo: mapa
estado: vigente
fecha: 2026-08-02
areas: [pdc]
fuente: sesion
resumen: "Plan de Compras v2: dónde vive la SPA, dónde el PHP, y qué leer antes de tocarlo"
---
# Mapa · Plan de Compras (PDC v2)

## Qué manda

[[docs/pdc-v2]] — modelo de dominio (presupuesto → maestro de insumos → paquetes → plan con
fechas), fases A1–A4, deudas de datos conocidas y trampas ya medidas. **Se lee antes de tocar
cualquier cosa del PDC**, no después.

De dónde viene el módulo, cuando hace falta el porqué y no el cómo:
[[docs/superpowers/specs/2026-07-21-stack-plan-de-compras-design|la spec del stack]] eligió la
pila, [[docs/superpowers/plans/2026-07-22-roadmap-pdc-v2|el roadmap]] ordenó las fases, y
[[docs/pdca-automatizacion-plan-compras|el PDCA de junio]] es el ciclo previo del que salió el
diagnóstico. Los datos de arranque están en
[[database/seeds/biblioteca_maestra_pdc_source_of_truth_v1_0|la biblioteca maestra v1.0]].

## Dónde vive

- SPA en `pdc-app/` (React + Vite + AG Grid); publica su bundle en `public/pdc-app/`.
- PHP en `src/Services/Pdc/`.
- Los contratos `auto/preview`, `auto/apply`, `auto/undo`, `auto/feedback` y `auto/metrics` se
  comparten con Listado de Actividades y Contratos. Reutiliza
  `public/js/modules/semi_auto_review.js`; no montes un flujo paralelo sin evidencia de que hace
  falta.

## Antes de correr nada

- [[pdc-e2e-sandbox]] — los e2e van contra el proyecto sacrificable 990100, no contra Da Porto.
- [[stack-principal-migraciones-pdc-pendientes]] — replayar las migraciones exige todo el DDL
  antes que los seeds; el orden cronológico por nombre de archivo revienta.
- [[dos-stacks-docker]] — hay dos stacks con base propia; el equivocado escribe en la de otra
  sesión.

## Estado

`/contratos`, `/listado-actividades` y `/pdc` ya usan el shell sidebar
([[compras-migrado-shell-sidebar]]). Las dos primeras superficies quedaron retiradas del producto
por el goal `retiro-listado-contratos`; ver [[estado|Estado de los goals]] para el resto del
recorrido A1–A4 y B1.

## Goals que trabajaron esta área

Recorrido A1–A4 y B1, todos cerrados:

- [[goals/pdc-a41-pasos-configurables/goal|pdc-a41-pasos-configurables]] — pasos de contratación configurables por obra.
- [[goals/pdc-a42-frentes-cobertura/goal|pdc-a42-frentes-cobertura]] — cada paquete sabe a qué frente del cronograma pertenece.
- [[goals/pdc-tanda2-plan-verdad/goal|pdc-tanda2-plan-verdad]] — cobertura real, vencidos visibles, amarre por niveles de confianza.
- [[goals/pdc-revision-ux/goal|pdc-revision-ux]] y [[goals/pdc-tanda34-pulido/goal|pdc-tanda34-pulido]] — los hallazgos de usabilidad del recorrido del dueño de producto y su pulido.
- [[goals/pdc-preparar-b1/goal|pdc-preparar-b1]] — la salida a producción en tres olas; absorbió [[goals/pdc-responsable-usuario/goal|pdc-responsable-usuario]].
- [[goals/retiro-listado-contratos/goal|retiro-listado-contratos]] — retiró las dos superficies viejas, lo que dejó sin objeto a [[goals/validar-migracion-handsontable/goal|validar-migracion-handsontable]].

Estado y matices en [[estado|Estado de los goals]].

## Vecinos

[[arquitectura]] para el modelo de datos · [[qa-y-gates]] para las suites e2e.
