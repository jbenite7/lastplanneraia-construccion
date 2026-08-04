# Goal — Biblia de flujos · T5 lectura

**Slug:** `biblia-t5-lectura`
**Fecha de apertura:** 2026-08-04
**Estado:** ABIERTO — quinta tanda
**Prioridad:** impacto medio · esfuerzo bajo → **quinta**

## Objetivo

Que los módulos de consulta —indicadores y Torre de Control BI— tengan sus escenarios descritos,
verificados con cita, y los críticos con prueba ejecutable.

Va última pese a su esfuerzo bajo porque **depende de las anteriores**: sus cifras son la salida de
lo que registran los módulos operativos, y describir una cifra sin haber descrito su origen sería
describir un espejismo.

## Condición de hecho

1. Los documentos de `docs/flujos/` cubren ambos módulos con `id` de prefijo `BI`.
2. El aislamiento por `project_id` está comprobado en cada consulta de datos, y el escenario dice
   qué debería ver un usuario de otro proyecto: nada.
3. Está descrita la limitación conocida de `/indicadores`: embebe un Power BI *publish-to-web* que
   **no filtra por proyecto** y es público por enlace; todos los proyectos ven el mismo informe.
   Es comportamiento aceptado, no bug, y la biblia lo dice como tal.
4. Está descrito que los roles `G`, `S`, `SG` y `C` no ven ese informe, con su escenario de rol
   denegado.
5. La coherencia entre las cifras de BI y lo que registran CIC/CNC/CNP está descrita como
   dependencia, citando los `id` `APR-*` de T2 en vez de duplicarlos.
6. Los hallazgos están en `docs/EXPERIMENTS.md` con ICE, sin arreglar.
7. `npm run test:wiki` en verde.

## Fuera de alcance

Migrar a Power BI Embedded para lograr el filtrado por proyecto: es una decisión de coste
(capacidad Azure) que ya está registrada como pendiente, no un hallazgo de esta tanda.

## Archivos de este goal

- [[goals/biblia-t5-lectura/goal|goal.md]] — este archivo
- Plan: [[docs/superpowers/plans/2026-08-04-biblia-t5-lectura|plan de la tanda]]
- Spec: [[docs/superpowers/specs/2026-08-04-biblia-de-flujos-design|diseño de la biblia]]
- Estado de todos los goals: [[estado|Estado de los goals]]
