---
capa: fuente
tipo: spec
estado: cerrado
fecha: 2026-07-29
areas: [proceso]
fuente: docs/superpowers/specs/2026-07-29-flujo-caja-desembolsos-design.md
resumen: goals/pdc-preparar-b1 - Origen: Comité del 2026-07-29 — necesidad de negocio con dolor actual y nombre propio. - Estado: implementado (2026-07-29), después de…
---

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
- ~~**Los paquetes sin frente o sin fechas quedan fuera de la curva, y la pantalla dice cuántos son y
  cuánto valen.**~~ **Corregido el 2026-07-30 por el dueño del producto:** «debería contar todo, lo que
  no se contrata distribuirlo en toda la duración de la obra». La curva cuenta el presupuesto entero.
  Ver «La curva cuenta el plan entero» abajo.

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

## La pantalla, construida (2026-07-30)

Esta sección decía «no está construida» y dejó de ser cierta el mismo día, tres líneas antes de la
verificación en pantalla que hay más abajo. Se corrige el 2026-08-04, después de que la contradicción
mandara a una sesión a rehacer trabajo ya hecho.

La vista es la pestaña «Flujo de caja» de Seguimiento (`pdc-app/src/pages/Seguimiento.tsx`), con la
advertencia del método arriba y sin plegar, el aviso de la parte provisional, el conteo de excluidos,
la fila de cifras con el botón de exportar y la tabla mensual con las tres columnas de origen. Las
palabras viven en `pdc-app/src/lib/flujoCaja.ts` y el reparto no se recalcula en el navegador.

## La curva cuenta el plan entero, en tres orígenes (2026-07-30)

Un flujo de caja que solo mira lo contratado no es el flujo de caja de la obra: la nómina y los
imprevistos también salen de caja, y todos los meses. Decisión del dueño del producto, textual:

> «Debería contar todo, lo que no se contrata distribuirlo en toda la duración de la obra.»

Cada peso del plan entra en la curva, pero por el camino que le corresponde y **dicho con su nombre**:

| Origen | Qué es | Cómo se reparte |
|---|---|---|
| `contratado` | Tiene frente amarrado y fechas propias | Lineal entre el inicio y el fin de **su** frente |
| `permanente` | No se le compra a nadie (nómina, imprevistos, provisiones) o no se contrata (ferretería contra almacén) | Lineal sobre **toda la duración de la obra** |
| `provisional` | Se va a contratar, pero nadie le ha amarrado un frente todavía | Lineal sobre toda la obra, **contado y mostrado aparte** |

**Por qué `provisional` va separado.** Se preguntó y se decidió en grilleo: `permanente` es un dato
correcto —ese gasto es continuo de verdad— y `provisional` es un **relleno que se va a mover** en
cuanto alguien amarre ese paquete. Mezclarlos daría una curva que se ve igual de firme en las dos
mitades, y cuando la parte provisional se reacomode nadie entendería por qué cambió. Hoy en Da Porto
son 0, pero en el aeropuerto van a ser muchos. Por eso la pantalla y el CSV llevan una columna propia,
la pantalla avisa de cuánto de la curva se moverá, y hay una cifra de «% con fecha propia» que dice
cuánto se puede creer de la forma de la curva.

**La duración de la obra** sale del cronograma (`MIN(Fecha_Inicio)` a `MAX(Fecha_Fin)` de la última
semana consolidada), que es la misma fuente del resto de la curva, con la línea base del proyecto como
respaldo. **Si no hay ninguna de las dos**, lo que no tiene frente propio sigue quedando declarado
fuera con su motivo: inventar un rango de fechas para que el total cuadre sería justo la mentira que
este módulo evita. Es el único caso que queda excluido, y hay un test que lo fija.

## Verificado en pantalla (2026-07-30)

Pestaña «Flujo de caja» de Seguimiento, en Da Porto, a 1180×820 y en dark:

- **22 meses** (la curva arranca en mayo 2026, cuando arranca la obra, no cuando arranca la primera
  contratación), y la suma de los meses es **$7.082.574.181**, igual al valor total del plan y al
  último acumulado.
- Desglose del pie: **$6.192.372.106 contratado + $890.202.075 de nómina y provisiones + $0
  provisional**, y «cubre el 100 % del valor del plan · 87,4 % con fecha propia».
- Sin errores de consola y sin desbordamiento horizontal a 1180 px exactos.
- El CSV trae las tres columnas, la duración de obra usada para el reparto, y los mismos números.

**Límite honesto:** el camino `provisional` no se pudo recorrer en pantalla con datos reales porque Da
Porto no tiene ningún paquete sin frente. Está cubierto por el test PHP (un destino de $7.000 sin
frente) y por Vitest (el texto del aviso y su concordancia en singular), no por observación.

---

## Estado verificado — cerrado

Verificado contra el código el 2026-08-25. **`estado: cerrado` es una afirmación deliberada**, no el valor por defecto del backfill.

**Evidencia:** src/Services/Pdc/FlujoCajaService.php y tests/test_pdc_v2_flujo_caja.php (31 asserts)

Criterio y método: [[docs/superpowers/plans/2026-08-25-estado-real-de-planes-y-specs]].
