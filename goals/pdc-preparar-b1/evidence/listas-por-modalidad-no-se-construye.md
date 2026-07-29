# Listas de pasos por modalidad — no se construye (2026-07-29)

**Decisión:** no se implementa el diferido nº 1 de A4.1.

## Por qué

El spec [`2026-07-29-a41-diferidos-configuracion-pasos-design.md`](../../../docs/superpowers/specs/2026-07-29-a41-diferidos-configuracion-pasos-design.md)
lo condiciona a una precondición que hoy no se cumple:

> *«precondición — evidencia escrita de al menos una obra que necesite dos listas distintas. Sin esa
> evidencia, no se construye y se anota el porqué.»*

Esa evidencia no existe y no la puede producir esta sesión: hay que preguntárselo a las dos obras.
El propio spec registra que el ítem *«está aquí porque se registró, no porque alguien lo haya pedido
desde que se registró»* — se anotó el 2026-07-28 y nadie ha vuelto sobre él.

## Qué costaría construirlo a ciegas

Es el más caro de los cuatro diferidos. La configuración dejaría de ser **por obra** y pasaría a ser
**por obra × modalidad**, lo que cambia la forma de `pdc_proyecto_pasos` y de todo lo que la lee:

- `PasosContratacionService::deProyecto()` — hoy devuelve *la* lista de la obra; pasaría a necesitar
  saber de qué paquete se le pregunta.
- `PlanFechasService::calcular()` — hoy resuelve los pasos **una vez** antes del bucle de paquetes;
  pasaría a resolverlos por paquete.
- La pantalla de pasos, que hoy edita una lista, pasaría a editar cuatro.
- Y la copia entre obras (diferido nº 2, ya construido) tendría que copiar cuatro listas, no una.

## Qué desbloquea esto

Preguntar a las dos obras —Da Porto y el aeropuerto— si sus cuatro modalidades de contratación
siguen procesos **distintos**, y no solo duraciones distintas (que ya son configurables por paquete
vía `duracion_ref`, y desde hoy editables desde la pantalla).

La pregunta en simple, para quien la traslade:

> «Cuando ustedes contratan, ¿el camino es el mismo para todo? ¿Comprar materiales pasa por los
> mismos pasos que contratar una cuadrilla, o alguno se salta pasos o tiene pasos propios?»

Si alguna dice que sí, se anota aquí su respuesta y **se abre grilleo propio** antes de tocar nada:
el spec lo exige explícitamente porque cambia la forma del modelo de datos.

## Estado

**Pendiente de:** el dueño del producto, que es quien tiene el canal con las dos obras.

Los otros tres diferidos de A4.1 sí se construyeron: copiar la configuración entre obras (`efe8d5e`),
duraciones del catálogo editables (`20d6acf`) e historial de versiones.
