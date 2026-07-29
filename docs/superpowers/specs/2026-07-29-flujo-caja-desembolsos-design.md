# PDC v2 — Flujo de caja: curva de desembolsos por mes — Design

- **Fecha:** 2026-07-29
- **Ola:** 3 (lo grande)
- **Goal:** `goals/pdc-preparar-b1`
- **Origen:** Comité del 2026-07-29 — necesidad de negocio con dolor actual y nombre propio.
- **Estado:** aprobado en grilleo, pendiente de plan.

## Problema

La empresa no está entregando flujo de caja de sus proyectos, y le está costando en comité. Textual:

> «Nos cascan en comité EPI porque no hemos entregado flujo de caja de Da Porto, y Víctor sale a preguntar
> que si Last Planner no lo hace, para entregárselo a él.»

Y lo que hace que sea barato:

> «Es un flujo de caja aproximado, es muy sencillo, porque es que desde los mismos paquetes vos le decís:
> es Pisos, y ya, prorratealo.»

El dato ya está: cada paquete tiene un valor, y desde A4 tiene fechas derivadas del frente al que está
amarrado. Lo que falta es repartir uno sobre el otro y sumar por mes. Felipe lo situó bien en la reunión:
es lo que MS Project hace asociando recursos al cronograma «de forma muy poco intuitiva», y aquí ya está
hecha la parte difícil.

## Decisión

**Curva mensual de desembolsos, sin condiciones de pago.**

Cada paquete reparte su valor a lo largo de las fechas de su frente en el cronograma, y la suma por mes es
la curva del proyecto, exportable.

Descartadas: la curva con anticipos, cortes y retenciones (mucho más útil para tesorería, pero exige un
modelo de pagos que hoy no existe en ninguna parte del sistema — es una fase propia, no un parámetro) y
exportar los datos crudos para armar la curva fuera (entrega valor en días, pero deja el trabajo
repetitivo en manos de una persona cada mes, que es justo el problema).

## Alcance

### Entra

- **Reparto:** el valor de cada paquete se distribuye de forma **lineal** entre el inicio y el fin de su
  frente. Lineal es una decisión declarada, no un descuido: es lo que significa «aproximado», y una curva
  en S sería fingir una precisión que no tenemos.
- **Curva mensual del proyecto:** una fila por mes, con el desembolso previsto y el acumulado.
- **Desglose:** poder ver de qué paquetes se compone el mes.
- **Exportable** a Excel, porque va a viajar a un comité que no entra a la aplicación.
- **Los paquetes sin frente o sin fechas quedan fuera de la curva, y la pantalla dice cuántos son y
  cuánto valen.** Una curva que calla lo que no incluye es una curva que miente.

### No entra

- Anticipos, cortes de obra, retenciones, plazos de pago.
- Flujo de ingresos: esto es solo la salida de dinero por contratación.
- Contabilidad real ni comparación contra lo ejecutado.
- Multiproyecto: la curva es de un proyecto.

## Arquitectura

- **Cálculo derivado, no almacenado.** Se computa al pedirlo desde paquetes + fechas. Guardarlo obligaría
  a invalidarlo cada vez que alguien recalcula el plan, y sería la primera cosa que se queda vieja.
- Servicio nuevo en `src/Services/Pdc/`, endpoint de lectura bajo el submódulo de Seguimiento, y vista en
  `pdc-app/` con la tabla mensual y su exportación.
- Sin migraciones.

## Condición de hecho

1. La suma de todos los meses de la curva es igual a la suma del valor de los paquetes incluidos.
2. Un paquete cuyo frente va de febrero a abril aporta a esos tres meses en proporción a los días, y a
   ningún otro.
3. Los paquetes excluidos están contados y valorados en pantalla, y la suma de incluidos + excluidos es
   el valor total del plan.
4. La exportación abre en Excel con los mismos números que la pantalla.
5. Cambiar la fecha de un frente y recalcular mueve la curva de forma coherente.
6. Regresión: tests del plan de fechas en verde.

## Riesgos

- **«Aproximado» se olvida rápido.** Esta curva va a llegar a un comité de dirección y alguien la va a
  tratar como presupuesto de tesorería. La pantalla y la exportación deben decir, en el propio documento,
  que el reparto es lineal y que no considera condiciones de pago.
- **Depende de la cobertura del plan.** Con 11 de 96 paquetes con fecha, la curva cubre el 54 % por valor.
  Es útil, pero solo si se dice: por eso el punto 3 es un hecho y no un detalle.
- **Si la Ola 3 trae subpaquetes**, la curva pasa a repartir por subpaquete. Los dos specs tienen que
  entrar en ese orden, o rehacer esto.
