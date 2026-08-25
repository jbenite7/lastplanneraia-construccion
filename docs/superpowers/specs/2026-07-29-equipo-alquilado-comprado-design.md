---
capa: fuente
tipo: spec
estado: cerrado
fecha: 2026-07-29
areas: [proceso]
fuente: docs/superpowers/specs/2026-07-29-equipo-alquilado-comprado-design.md
resumen: goals/pdc-preparar-b1 - Origen: Comité del 2026-07-29 — petición de Tomás Trujillo, con motivo contable explícito. - Estado: implementado y en main (fila 5 del…
---

# PDC v2 — Equipo alquilado vs equipo comprado — Design

- **Fecha:** 2026-07-29
- **Ola:** 2 (después del lanzamiento)
- **Goal:** `goals/pdc-preparar-b1`
- **Origen:** Comité del 2026-07-29 — petición de Tomás Trujillo, con motivo contable explícito.
- **Estado:** **implementado y en `main`** (fila 5 del tablero, `e992301`). Los equipos existentes quedaron «sin clasificar» en la cola del maestro, como se decidió.

## Problema

El maestro de insumos tiene un tipo de recurso «Equipo» que mete en la misma bolsa lo que se alquila y lo
que se compra. Textual:

> «A contabilidad se le va a volver un meollo, porque tengo la categoría equipo, pero el insumo se tiene
> que llamar alquiler o se tiene que llamar compra, y contabilidad no sabe ni el manejo contable. El del
> alquiler es distinto al de la compra. Entonces creo que vamos a separar las categorías: hay insumo
> categoría equipo alquilado y hay insumo categoría equipo comprado.»

No es una preferencia de nomenclatura: es una decisión de política de la empresa que ya se está debatiendo
con el equipo de presupuestos, y el módulo va a ser donde se materialice.

## Decisiones cerradas en el grilleo

| Decisión | Valor | Consecuencia |
|---|---|---|
| Dónde vive | **Nuevo valor del tipo de recurso** | El enum ya se partió antes (Nómina de obra, etc.); se vuelve a partir. El motor de sugerencias **ya filtra candidatos por tipo de recurso**, así que la mejora se propaga sola |
| Los que ya existen | **Quedan «sin clasificar»** y entran a la cola de pendientes del maestro | Nadie afirma lo que no sabe. Divergencia deliberada con la opción barata (todos a «comprado») |

Descartadas: atributo aparte del insumo (el motor no lo miraría y contabilidad cruzaría dos campos) y
distinguir solo en el paquete (el maestro global se quedaría sin el dato).

## Alcance

### Entra

- Dos valores nuevos de tipo de recurso: **Equipo comprado** y **Equipo alquilado**.
- Un estado de tránsito, **Equipo (sin clasificar)**, al que migran todos los equipos existentes.
- Los «sin clasificar» aparecen en la **cola de pendientes del maestro**, que ya existe para los vínculos
  de insumos: se resuelven en masa con selección múltiple, no de uno en uno.
- El importador de presupuestos y el importador SINCO saben leer los valores nuevos.
- El motor de sugerencias los usa como cualquier otro tipo de recurso, sin código especial.
- **Las categorías de gastos generales** que Tomás ya maneja en su código se revisan en el mismo golpe:
  si el presupuesto las trae y el maestro no las distingue, se anota como hecho aparte.

### No entra

- Adivinar por el nombre del insumo. Descartado en el grilleo: escribiría datos sin confirmación humana,
  justo lo que el módulo evita en todo lo demás.
- Cambiar el manejo contable ni integrarse con contabilidad. Esto entrega el dato limpio; el uso es de
  ellos.

## Arquitectura

- **Migración** que amplía el enum de `tipo_recurso` y reetiqueta los equipos existentes a «sin
  clasificar». Reversible: la vuelta atrás los devuelve a «Equipo».
- La cola de pendientes reusa la pantalla del maestro que ya existe; no hay pantalla nueva.
- RBAC: clasificar es tocar el maestro global → capacidad de administración
  (`lps.pdc.maestro` / `lps.paquetes_contratacion.reglas`, a confirmar al escribir el plan), nunca la obra.

## Condición de hecho

1. Un insumo nuevo se puede crear como comprado o alquilado, y el valor sobrevive a recargar.
2. Todos los equipos preexistentes quedan en «sin clasificar» y aparecen en la cola, contados.
3. Clasificar 20 de golpe desde la cola funciona y la cola baja en 20.
4. El motor de sugerencias no ofrece un insumo alquilado como candidato de un paquete de compra.
5. Reimportar un presupuesto no devuelve a «sin clasificar» un insumo ya clasificado.
6. La migración tiene vuelta atrás probada.
7. Regresión: tests del maestro, del importador SINCO y del motor en verde.

## Riesgos

- **El tapón.** Dejar cientos de insumos «sin clasificar» es honesto pero pesado: hasta que alguien los
  clasifique, el motor tiene menos con qué filtrar. Va en la Ola 2 precisamente para que no bloquee el
  lanzamiento, pero hay que decidir explícitamente **si el módulo se puede usar con el tapón puesto** —
  y la respuesta por defecto de este spec es que sí: «sin clasificar» se comporta como el «Equipo» de
  hoy, ni mejor ni peor.
- **Partir un enum toca muchos sitios.** Ya se hizo antes (el bucket de indirectos se partió en A3.2) y
  entonces aparecieron paquetes arrastrando el tipo viejo. Ese antecedente está documentado en
  `docs/pdc-v2.md` §deudas de datos: releerlo antes de escribir el plan.

---

## Estado verificado — cerrado

Verificado contra el código el 2026-08-25. **`estado: cerrado` es una afirmación deliberada**, no el valor por defecto del backfill.

**Evidencia:** idem plan hermano

Criterio y método: [[docs/superpowers/plans/2026-08-25-estado-real-de-planes-y-specs]].
