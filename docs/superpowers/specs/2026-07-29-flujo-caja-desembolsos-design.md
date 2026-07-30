# PDC v2 — Flujo de caja: curva de desembolsos por mes — Design

- **Fecha:** 2026-07-29
- **Ola:** 3 (lo grande)
- **Goal:** `goals/pdc-preparar-b1`
- **Origen:** Comité del 2026-07-29 — necesidad de negocio con dolor actual y nombre propio.
- **Estado:** **implementado** (2026-07-29), después de subpaquetes y por tanto repartiendo por destino
  contratable, no por paquete. Ver «Cómo quedó implementado».

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

## Cómo quedó implementado

- **`src/Services/Pdc/FlujoCajaService.php`**, derivado y sin almacenar nada. Sin migración, a
  propósito.
- **La unidad es el destino contratable** de `SubpaquetesService::destinos()`, así que si la obra
  partió «Pisos» en tres lotes con fechas distintas, la curva reparte por lote. Los dos specs se
  hicieron en ese orden justamente para no tener que rehacer el reparto.
- **El fin del frente no estaba en el modelo.** `pdc_paquete_frente` solo guarda `fecha_ancla` (el
  inicio). El fin se lee de `programa_consolidado.Fecha_Fin` por `unique_id` en la última semana
  consolidada — comprobado poblado en las 1.092 filas de Da Porto. Y el **inicio** también se lee del
  cronograma en vivo, no del `fecha_ancla` guardado: ese campo es una copia congelada del momento del
  amarre, y dibujar la curva sobre él la pondría sobre fechas que la obra ya movió.
- **El residuo del reparto va al último mes.** Con céntimos repartidos entre 20 meses, la suma de los
  redondeos se separa del total unos céntimos; en una tabla que va a comité, «la suma no da»
  desacredita todo aunque el error sea de $3. Así el punto 1 de la condición de hecho es exacto.
- **La exportación es CSV con `;` y BOM UTF-8**, no `.xlsx`, aunque PhpSpreadsheet ya sea dependencia:
  lo que viaja es una tabla de cuatro columnas, y así Excel la abre sin preguntar nada ni romper las
  tildes. El `;` es obligatorio porque el Excel en español lee la coma como decimal.
- **La advertencia va dentro del archivo**, en sus dos primeras filas, además de en la respuesta de la
  API (`FlujoCajaService::NOTA_METODO`). El archivo se reenvía por correo y se abre sin la pantalla al
  lado; sin eso, alguien lo lee como presupuesto de tesorería.
- Endpoints `GET /plan-compras/api/seguimiento/flujo-caja` y `…/flujo-caja.csv`, RBAC de lectura
  `lps.paquetes_contratacion.ver`.

## Verificación

`tests/test_pdc_v2_flujo_caja.php` — 31 asserts. La aritmética del reparto se prueba primero y sin
base de datos (frente de febrero a abril, un solo día, cruce de año, fin anterior al inicio, y que la
suma de los meses sea **exactamente** el valor repartido); después, sobre MySQL real (proyecto 999941),
los cinco puntos de la condición de hecho, incluido que mover un frente mueva la curva sin cambiar su
total, y que partir un paquete no cambie el total sino quién lo aporta.

## Lo que queda pendiente

**La pantalla.** El servicio, los endpoints y la exportación están; la vista de `pdc-app/` con la
tabla mensual, el desglose y el botón de exportar **no está construida**. La advertencia y el conteo
de excluidos ya viajan en la respuesta, listos para pintarse.
