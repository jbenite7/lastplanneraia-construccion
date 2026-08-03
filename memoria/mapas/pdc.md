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

## Vecinos

[[arquitectura]] para el modelo de datos · [[qa-y-gates]] para las suites e2e.
