# Goal — Biblia de flujos · T4 soporte

**Slug:** `biblia-t4-soporte`
**Fecha de apertura:** 2026-08-04
**Estado:** ABIERTO — cuarta tanda
**Prioridad:** impacto medio · esfuerzo medio → **cuarta**

## Objetivo

Que los módulos que alimentan la cascada sin gobernarla —contratos, listado de actividades,
subcontratistas, profesionales, control de cambios y escalamientos— tengan sus escenarios descritos,
verificados con cita, y los críticos con prueba ejecutable.

## Condición de hecho

1. Los documentos de `docs/flujos/` cubren los seis módulos con `id` de prefijo `SOP`.
2. La **invariante entre módulos** está descrita y comprobada: contratos, listado de actividades y
   PDC comparten los contratos `auto/preview`, `auto/apply`, `auto/undo`, `auto/feedback` y
   `auto/metrics` y el módulo `public/js/modules/semi_auto_review.js`. Los escenarios comprueban que
   los tres se comportan igual; cualquier divergencia es hallazgo.
3. Cada módulo tiene su escenario de rol permitido y rol denegado.
4. El aislamiento por `project_id` está comprobado en al menos una consulta de listado por módulo.
5. Los críticos tienen prueba ejecutable citando su `id`, y la suite corre en verde.
6. Los hallazgos están en `docs/EXPERIMENTS.md` con ICE, sin arreglar.
7. `npm run test:wiki` en verde con los mapas afectados enlazando la biblia.

## Fuera de alcance

`src/Legacy/` más allá de lo que estos módulos toquen de paso. Arreglar los hallazgos.

## Archivos de este goal

- [[goals/biblia-t4-soporte/goal|goal.md]] — este archivo
- Plan: [[docs/superpowers/plans/2026-08-04-biblia-t4-soporte|plan de la tanda]]
- Spec: [[docs/superpowers/specs/2026-08-04-biblia-de-flujos-design|diseño de la biblia]]
- Estado de todos los goals: [[estado|Estado de los goals]]
