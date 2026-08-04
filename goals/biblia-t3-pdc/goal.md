# Goal — Biblia de flujos · T3 PDC

**Slug:** `biblia-t3-pdc`
**Fecha de apertura:** 2026-08-04
**Estado:** ABIERTO — tercera tanda
**Prioridad:** impacto alto · esfuerzo medio → **tercera**

## Objetivo

Que el Plan de Compras v2 —presupuesto → maestro de insumos → paquetes de contratación → plan con
fechas → seguimiento— tenga cada escenario descrito, verificado con cita, y los críticos con prueba
ejecutable.

Esfuerzo medio y no alto porque parte con ventaja: `docs/pdc-v2.md` ya documenta el modelo de
dominio, las fases A1–A4 y las deudas de datos conocidas.

## Condición de hecho

1. Los documentos de `docs/flujos/` cubren la cadena completa con `id` estables de prefijo `PDC`.
2. Las **deudas de datos ya documentadas** en `docs/pdc-v2.md` están como escenarios de primera
   clase, no como notas al pie: cada una dice qué debería pasar frente a lo que pasa.
3. El comportamiento de la SPA se describe leyendo `pdc-app/src/`, **nunca el bundle compilado** de
   `public/pdc-app/`, y el documento lo advierte.
4. Los contratos compartidos `auto/preview`, `auto/apply`, `auto/undo`, `auto/feedback` y
   `auto/metrics` están descritos aquí una vez, y T4 los cita en vez de duplicarlos.
5. Los críticos tienen prueba ejecutable citando su `id`, y la suite corre en verde.
6. Los hallazgos están en `docs/EXPERIMENTS.md` con ICE, sin arreglar.
7. `memoria/mapas/pdc.md` enlaza la biblia y `npm run test:wiki` sigue en verde.

## Fuera de alcance

Arreglar las deudas de datos, que son decisión del usuario y algunas tocan datos ya repartidos.

## Archivos de este goal

- [[goals/biblia-t3-pdc/goal|goal.md]] — este archivo
- Plan: [[docs/superpowers/plans/2026-08-04-biblia-t3-pdc|plan de la tanda]]
- Spec: [[docs/superpowers/specs/2026-08-04-biblia-de-flujos-design|diseño de la biblia]]
- Estado de todos los goals: [[estado|Estado de los goals]]
