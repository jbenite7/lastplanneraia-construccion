# Goal — Biblia de flujos · T5 lectura

**Slug:** `biblia-t5-lectura`
**Fecha de apertura:** 2026-08-04
**Estado:** CERRADO — quinta pasada hecha y verde
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

## Cierre formal

**Estado:** HECHO
**Fecha de cierre:** 2026-08-06

### Lo que se logró

`docs/flujos/lectura-bi.md` con 6 escenarios `id` `BI-*` cubriendo indicadores y Torre de Control, con
aislamiento por `project_id` comprobado en cada consulta (`BiProjectScope::resolve()` rechaza la
petición entera si algún proyecto pedido no está autorizado). Descrita la limitación conocida de
`/indicadores` como comportamiento aceptado: el Power BI publish-to-web no filtra por proyecto. Rol
denegado descrito para `G`, `S`, `SG`, `C`. Hallazgo `BI-003`: el filtro de «proyecto cerrado visible
para jefatura» está en tres sitios con tres criterios distintos, solo BI incluye el alias legado
`'P'`; sigue abierto en `docs/EXPERIMENTS.md`. Un segundo hallazgo relacionado, que `/indicadores`
ocultaba el informe solo en cliente, se cerró después en la campaña de seguridad (commit `4b1a2be0`).

### Justificación del cierre

Las siete condiciones de hecho están cumplidas: ambos módulos tienen escenarios citando código, el
aislamiento por proyecto está comprobado, la limitación de Power BI está descrita como aceptada, el
rol denegado está cubierto, y `npm run test:wiki` sigue en verde. Este goal no exigía prueba
ejecutable propia (dependía de T2 para sus cifras, ya cubierto). Ejecutado y registrado el
2026-08-04 (`memoria/log.md`), formalizado el 2026-08-06 tras verificar que el trabajo seguía
vigente.

## Archivos de este goal

- [[goals/biblia-t5-lectura/goal|goal.md]] — este archivo
- Plan: [[docs/superpowers/plans/2026-08-04-biblia-t5-lectura|plan de la tanda]]
- Spec: [[docs/superpowers/specs/2026-08-04-biblia-de-flujos-design|diseño de la biblia]]
- Estado de todos los goals: [[estado|Estado de los goals]]
