# PDC v2 · Fase B2 (segunda mitad) — Re-matching al reprogramar — Design

- **Fecha:** 2026-07-29
- **Ola:** 2 (después del lanzamiento)
- **Goal:** `goals/pdc-preparar-b1`
- **Origen:** roadmap maestro, fase B2. No lo pidió el comité: lo exige el uso real, porque los
  cronogramas se reprograman todas las semanas.
- **Hermano:** `2026-07-29-b2-semaforos-lookahead-design.md` (la primera mitad de B2).
- **Estado:** **implementado y en `main`** (fila 7a del tablero, `3a0da33`). La medición inicial recortó el alcance a la mitad y destapó un bug real: «Recalcular» no recogía la fecha nueva del frente.

## Problema

El plan de compras cuelga del cronograma: cada paquete está amarrado a un **frente** (subcapítulo), y de
las fechas de ese frente salen las fechas de contratación. La razón de amarrar por frente y no por
actividad la dio el propio dueño del producto:

> «Por lo general, en un cronograma, cuando se hace una reprogramación los capítulos no se mueven mucho.
> Lo que se mueven son las actividades.»

O sea: el amarre está diseñado para aguantar reprogramaciones. Pero **aguantar no es enterarse**. Cuando
el frente sí se mueve, hoy nadie avisa de que el plan de compras que se imprimió la semana pasada dejó de
ser cierto.

## Lo primero es medir, no construir

Los commits del 2026-07-29 ya tocaron esta zona (`92c5c13` «reprogramar la obra ya no borra lo que sí se
hizo», `a4d0c75` «recalcular ya no borra un paso que sí ocurrió», `bfe7055` «Fin programado vuelve a ser
el fin del último paso»). **Antes de escribir una línea, hay que medir qué hace hoy el sistema** cuando se
mueve un frente: ¿recalcula solo, recalcula al entrar, o no recalcula hasta que alguien pulsa?

Este spec asume el peor caso —que no avisa— y define lo que falta. Si la medición dice que parte ya
existe, el alcance se recorta y se anota; no se reimplementa.

## Alcance

### Entra

- **Detectar el desfase:** al cambiar las fechas de un frente en el cronograma, el plan de compras sabe
  que sus fechas derivadas quedaron viejas.
- **Mostrar el delta antes de aplicarlo**: qué paquetes se mueven, cuántos días y en qué dirección. El
  usuario ve el efecto y decide.
- **Recalcular conservando lo real.** Regla no negociable, ya establecida en B1: lo programado se
  recalcula, **lo ocurrido nunca se borra**. Un paso con `fecha_real` conserva su fecha real aunque su
  fecha programada se mueva.
- **Avisar en el tablero de vencimientos** cuando lo que se está mirando se calculó contra un cronograma
  que ya cambió.

### No entra

- Reamarrar paquetes solos a otro frente. Si un frente desaparece del cronograma, el paquete queda sin
  frente y **se pide confirmación humana**, como en todo el módulo.
- Notificaciones por correo.
- Historial de reprogramaciones.

## Arquitectura

- El recálculo ya existe (`PlanFechasService`); lo que falta es el **disparo** y el **antes/después**.
- El delta se calcula sobre lo que el recálculo produciría, sin escribirlo: es una simulación, y solo se
  persiste si el usuario confirma.
- Sin tablas nuevas previstas. Si hace falta marcar «este plan está desactualizado», se resuelve
  comparando la fecha del último recálculo contra la del último cambio del cronograma, antes que añadir
  una columna de estado que haya que mantener al día.

## Condición de hecho

1. Está escrito, con evidencia, qué hace hoy el sistema al mover un frente. Ese es el primer entregable.
2. Mover un frente en el cronograma hace que el plan lo diga, sin que nadie tenga que adivinarlo.
3. El delta muestra los paquetes afectados y sus días de corrimiento antes de aplicar; cancelar no
   escribe nada.
4. Un paso con fecha real conserva su fecha real después de recalcular. Verificado sobre datos, no sobre
   la intención.
5. Un frente eliminado deja su paquete sin frente y pide confirmación; no se reamarra solo.
6. Regresión: `tests/test_pdc_v2_plan_fechas.php` y `tests/test_pdc_v2_seguimiento.php` en verde, más los
   e2e `pdc-v2-plan`.

## Riesgos

- **La medición puede cambiar el spec.** Es el motivo de que el punto 1 sea un hecho y no un supuesto.
- **Recalcular es la operación que más daño puede hacer** en todo el módulo: toca las fechas de las que
  cuelga el trabajo de la obra. Por eso simula y pide confirmación en vez de aplicar y avisar.
