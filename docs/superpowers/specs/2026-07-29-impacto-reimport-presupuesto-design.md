---
capa: fuente
tipo: spec
estado: vigente
fecha: 2026-07-29
areas: [proceso]
fuente: docs/superpowers/specs/2026-07-29-impacto-reimport-presupuesto-design.md
resumen: goals/pdc-preparar-b1 - Origen: Comité del 2026-07-29 — el «punto gris» que el dueño del producto no supo resolver en vivo. - Depende de: A1 (importador con…
---

# PDC v2 — Informe de impacto al recargar el presupuesto — Design

- **Fecha:** 2026-07-29
- **Ola:** 1 (antes del lanzamiento)
- **Goal:** `goals/pdc-preparar-b1`
- **Origen:** Comité del 2026-07-29 — el «punto gris» que el dueño del producto no supo resolver en vivo.
- **Depende de:** A1 (importador con versiones), A2 (maestro), A3 (asignación insumo↔paquete).
- **Estado:** **implementado y en `main`** (fila 2 del tablero, `31e9145`). El informe de impacto se muestra en la previsualización de la importación, antes de confirmar.

## Problema

El plan de compras se arma sobre una versión del presupuesto y **luego llega otra**. El caso real y
próximo es la transición del presupuesto «clase 1» al «clase 0» de Da Porto. Textual:

> «En la interfaz entre el clase uno y el clase cero puede pasar que hay insumos que se eliminan, insumos
> nuevos, e insumos que se empaquetan de una forma distinta. Por eso digo que hay un punto gris que yo
> todavía no tengo muy claro qué va a pasar.»

Hoy la herencia existe —al reimportar, las asignaciones a paquete se conservan y el auto-match corre otra
vez— pero **el usuario confirma la carga a ciegas**: no sabe cuánto de su trabajo va a quedar huérfano
hasta después de haberlo hecho.

La mitigación que el propio Tomás propuso (cargar solo preliminares + estructura + gastos generales para
no arrastrar los capítulos que van a cambiar) es un rodeo manual. Se documenta como táctica válida, pero
no es el arreglo.

## Decisión

**Informar antes de confirmar, sin decidir por el usuario.** La pantalla de importación ya tiene un paso
de previsualización (preview → confirmar, todo-o-nada, de A1). Ese paso gana un bloque de impacto sobre
el trabajo ya hecho.

Alternativas descartadas en el grilleo: *solo medir y documentar* (deja al piloto expuesto) y *aplicar la
mitigación manual* (rodeo, no arreglo).

## Alcance

### Entra

En la previsualización de una versión nueva, contra la versión activa, cuatro cifras y su detalle:

| Cifra | Qué cuenta | Por qué importa |
|---|---|---|
| Insumos nuevos sin paquete | Aparecen en la versión nueva y no tienen destino asignado | Es trabajo que se suma |
| Insumos que desaparecen y tenían paquete | Estaban asignados y ya no existen | Es trabajo que se pierde |
| Insumos que cambian de agrupación | Siguen existiendo pero su agrupación SINCO o su tipo de recurso cambió | El motor los va a sugerir distinto |
| Valor afectado | Suma del valor de los tres grupos | Traduce el impacto a pesos, no a conteos |

Cada cifra se puede desplegar a la lista de insumos que la componen, con su paquete actual.

El texto de confirmación pasa a decir qué se conserva y qué no, en palabras, antes del botón.

### No entra

- **Reglas automáticas de reagrupación.** Si un insumo cambió de agrupación, se señala; no se reasigna
  solo. Todo el módulo se sostiene sobre confirmación humana y esta pantalla no es la excepción.
- Fusionar versiones o importar parcialmente por capítulo.
- Deshacer una importación ya confirmada.

## Arquitectura

- **Backend:** el cálculo compara la versión candidata contra la activa reusando lo que ya existe —el
  comparativo de A1.6 (`GET /plan-compras/api/presupuesto/comparar`) resuelve el diff por insumo, y
  `pdc_insumo_paquete` dice quién tenía destino. El impacto es el cruce de las dos cosas; **no hay
  consulta nueva contra el presupuesto**, hay un join más.
- **Sin migraciones.** Es lectura pura sobre datos que ya están.
- **Frontend:** bloque nuevo en la pantalla de importación de `pdc-app/`, en el paso de previsualización.

## Condición de hecho

1. Con una versión candidata idéntica a la activa, las cuatro cifras dan cero y el texto lo dice.
2. Con una versión que añade un insumo, quita uno asignado y cambia la agrupación de un tercero, las
   cifras dan 1 · 1 · 1 y el detalle nombra exactamente esos tres.
3. El valor afectado coincide con la suma de los tres grupos, verificado contra la base.
4. Cancelar en la previsualización no escribe nada: la versión activa y las asignaciones quedan intactas.
5. Confirmar conserva las asignaciones de los insumos que siguen existiendo — el contrato de herencia de
   A3 no cambia.
6. Regresión: los tests PHP del importador y los e2e de import en verde.

## Riesgos

- **«Cambia de agrupación» puede ser ruidoso.** Si el export de SINCO reordena familias, podría marcar
  decenas de insumos que en la práctica no cambiaron de nada. Se mide contra el clase 0 real de Da Porto
  antes de dar el hecho por bueno; si el ruido es alto, se restringe a los que además tienen paquete.
- **El clase 0 todavía no existe.** La verificación del punto 2 se hace con una versión construida a
  mano a partir del clase 1. Es una prueba honesta del mecanismo, no del caso real: eso se comprueba
  cuando llegue el presupuesto de verdad, y hay que decirlo así en la bitácora.
