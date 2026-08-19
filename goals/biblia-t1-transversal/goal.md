---
capa: fuente
tipo: goal-doc
estado: vigente
fecha: 2026-08-04
areas: [proceso]
fuente: goals/biblia-t1-transversal/goal.md
resumen: Que la entrada a la aplicación —autenticación, selección de proyecto y resolución de capacidades por rol— tenga cada escenario descrito con detalle atómico…
---

# Goal — Biblia de flujos · T1 transversal

**Slug:** `biblia-t1-transversal`
**Fecha de apertura:** 2026-08-04
**Estado:** CERRADO — primera pasada hecha y verde
**Prioridad:** impacto alto · esfuerzo bajo → **se ejecuta primero**

## Objetivo

Que la entrada a la aplicación —autenticación, selección de proyecto y resolución de capacidades
por rol— tenga cada escenario descrito con detalle atómico, verificado contra el código con cita, y
los críticos cubiertos por prueba ejecutable.

Va primera **no** por ser el corazón del negocio sino porque es barata y contamina todo lo demás:
cada escenario de las otras cuatro tandas empieza con «un rol X con un proyecto en sesión». Si esa
base tiene un hueco, las demás lo heredan.

## Condición de hecho

1. `docs/flujos/README.md` existe y declara la cláusula de autoridad —si biblia y código divergen es
   un bug de uno de los dos— y el formato del escenario.
2. Los tres documentos (`transversal-autenticacion`, `transversal-proyecto`, `transversal-rbac`)
   describen sus escenarios con `id` estable, incluidos los de error y permiso denegado.
3. Toda afirmación comprobable lleva cita `archivo:línea`; lo no comprobable en lectura está
   declarado como tal, no dado por bueno.
4. Las 17 capacidades de `RbacManager` tienen escenario, cada una con su tabla de roles leída del
   código y al menos un consumidor real citado.
5. Los escenarios críticos tienen prueba en `e2e/tests/biblia/transversal.spec.mjs`, titulada con su
   `id`, y la suite corre en verde.
6. Los hallazgos están en `docs/EXPERIMENTS.md` con ICE, **sin arreglar**.
7. `npm run test:wiki` en verde y `memoria/mapas/rbac-y-rutas.md` enlaza la biblia.

## Fuera de alcance

El panel `admin/` (otra aplicación, con su propio `RoleManager`) y arreglar los hallazgos.

## Cierre formal

**Estado:** HECHO
**Fecha de cierre:** 2026-08-06

### Lo que se logró

`docs/flujos/README.md` existe con la cláusula de autoridad y el formato del escenario. Los tres
documentos (`transversal-autenticacion`, `transversal-proyecto`, `transversal-rbac`) describen sus
escenarios con `id` estable, incluidos error y permiso denegado, con cita `archivo:línea`. Las 17
capacidades de `RbacManager` tienen escenario, tabla de roles leída del código y consumidor citado.
7 pruebas ejecutables en `e2e/tests/biblia/transversal.spec.mjs`, en verde. 10 hallazgos registrados
en `docs/EXPERIMENTS.md` con ICE, sin arreglar. `memoria/mapas/rbac-y-rutas.md` enlaza la biblia.

### Justificación del cierre

Las siete condiciones de hecho del goal están cumplidas: los tres documentos existen y citan
código, las pruebas corren en verde, los hallazgos quedan registrados sin arreglar (fuera de
alcance), y `npm run test:wiki` sigue en verde. Ejecutado y registrado el 2026-08-04
(`memoria/log.md`), formalizado el 2026-08-06 tras verificar que el trabajo seguía vigente.

## Archivos de este goal

- [[goals/biblia-t1-transversal/goal|goal.md]] — este archivo
- Plan: [[docs/superpowers/plans/2026-08-04-biblia-t1-transversal|plan de la tanda]]
- Spec: [[docs/superpowers/specs/2026-08-04-biblia-de-flujos-design|diseño de la biblia]]
- Estado de todos los goals: [[estado|Estado de los goals]]
