# Goal — Biblia de flujos · T2 cascada LPS

**Slug:** `biblia-t2-cascada-lps`
**Fecha de apertura:** 2026-08-04
**Estado:** ABIERTO — segunda tanda; requiere T1 cerrada
**Prioridad:** impacto muy alto · esfuerzo alto → **segunda**

## Objetivo

Que el ciclo Last Planner —Programa General, actualizar cronograma, Programación Intermedia,
Programación Semanal y los submódulos CIC/CNC/CNP— tenga cada escenario descrito, verificado con
cita, y los que tocan permisos, mutan datos o cierran periodo cubiertos por prueba ejecutable.

Es el corazón del producto y, según `docs/CUSTOMER.md`, **el cuello de botella de los tres jobs a la
vez**: si el residente no registra bien y a tiempo, el director no ve patrones y la gerencia no
sostiene sus cifras.

## Condición de hecho

1. Cinco documentos en `docs/flujos/`: programa general, intermedia, semanal, aprendizaje y las
   invariantes de cascada.
2. El candado de semana está descrito con las **cinco salidas** de `LpsWeekEditPolicy::allows()`,
   incluida la contraintuitiva: una semana confirmada sigue siendo editable para calificar.
3. La herencia de actividades entre eslabones está descrita, incluido qué ocurre cuando la actividad
   de origen cambia después.
4. Los ocho estados operativos de Programación Intermedia tienen cada uno la condición de datos que
   lo produce; un estado inalcanzable es hallazgo.
5. El cálculo del PPC está descrito con sus casos borde (semana sin compromisos, cumplimiento
   parcial, actividades añadidas tras confirmar).
6. Los críticos tienen prueba en `e2e/tests/biblia/cascada-lps.spec.mjs` y la suite corre en verde,
   dejando el sandbox restaurado.
7. Los hallazgos están en `docs/EXPERIMENTS.md` con ICE, sin arreglar, y los que dependen de decidir
   *cuál es la conducta correcta* están marcados para el usuario.
8. `memoria/flujos/flujo-lps.md` pasa a enlazar la biblia como resumen de entrada, y
   `npm run test:wiki` sigue en verde.

## Fuera de alcance

Arreglar los hallazgos. Rehacer `memoria/flujos/flujo-lps.md` más allá de enlazar.

## Archivos de este goal

- [[goals/biblia-t2-cascada-lps/goal|goal.md]] — este archivo
- Plan: [[docs/superpowers/plans/2026-08-04-biblia-t2-cascada-lps|plan de la tanda]]
- Spec: [[docs/superpowers/specs/2026-08-04-biblia-de-flujos-design|diseño de la biblia]]
- Estado de todos los goals: [[estado|Estado de los goals]]
