---
capa: fuente
tipo: goal-doc
estado: vigente
fecha: 2026-08-04
areas: [pdc]
fuente: goals/biblia-t3-pdc/goal.md
resumen: Que el Plan de Compras v2 —presupuesto → maestro de insumos → paquetes de contratación → plan con fechas → seguimiento— tenga cada escenario descrito…
---

# Goal — Biblia de flujos · T3 PDC

**Slug:** `biblia-t3-pdc`
**Fecha de apertura:** 2026-08-04
**Estado:** CERRADO — tercera pasada hecha y verde
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

## Cierre formal

**Estado:** HECHO
**Fecha de cierre:** 2026-08-06

### Lo que se logró

El goal se rehízo a mitad de camino: la primera pasada describía el PDC v1, deprecado el mismo día
por el usuario, así que se retiró y se escribió `docs/flujos/compras-v2.md` sobre el PDC v2 vivo
(`/plan-compras`, 70 rutas, nueve `PlanCompras*Controller`, SPA en `pdc-app/src/`, nunca el bundle
compilado). 8 escenarios de autorización con `id` `PDC-*`, 3 pruebas en verde, cero hallazgos nuevos
en esta tanda. Confirmado que la acotación por `subpaquete_id` de `PlanFechasService` —la deuda que
`docs/pdc-v2.md` marcaba como el borrado más peligroso— está atendida. `memoria/mapas/pdc.md` enlaza
la biblia.

### Justificación del cierre

Las siete condiciones de hecho están cumplidas sobre el PDC v2 vigente: los documentos cubren la
cadena completa citando `pdc-app/src/`, las deudas de datos conocidas están como escenarios de
primera clase, los contratos `auto/*` compartidos están descritos una vez, las pruebas corren en
verde, y `npm run test:wiki` sigue en verde. Arreglar las deudas de datos queda fuera de alcance por
decisión explícita del goal. Ejecutado y registrado el 2026-08-04 (`memoria/log.md`), formalizado el
2026-08-06 tras verificar que el trabajo seguía vigente.

## Archivos de este goal

- [[goals/biblia-t3-pdc/goal|goal.md]] — este archivo
- Plan: [[docs/superpowers/plans/2026-08-04-biblia-t3-pdc|plan de la tanda]]
- Spec: [[docs/superpowers/specs/2026-08-04-biblia-de-flujos-design|diseño de la biblia]]
- Estado de todos los goals: [[estado|Estado de los goals]]
