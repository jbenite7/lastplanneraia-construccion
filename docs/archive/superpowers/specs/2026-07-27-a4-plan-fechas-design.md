---
capa: fuente
tipo: spec
estado: cerrado
fecha: 2026-07-27
areas: [proceso]
tags: [archivo]
fuente: docs/archive/superpowers/specs/2026-07-27-a4-plan-fechas-design.md
resumen: A4 · El plan de compras con fechas — diseño
---

# A4 · El plan de compras con fechas — diseño

**Fecha:** 2026-07-27 · **Fase:** A4 (cierra el submódulo de Ensamble) · **Estado:** aprobado, pendiente de plan

## Por qué

Al terminar A3.6 el plan de compras sabe *qué* se contrata: 102 paquetes con 394 insumos asignados,
99,7 % de cobertura. Lo que no sabe es **cuándo**. Sin fecha, un plan de compras no se puede operar:
nadie sabe qué contratación va tarde ni cuál toca arrancar esta semana.

A4 pone la fecha. Cada paquete se amarra a un frente del cronograma, hereda su fecha de inicio en
obra y desde ahí se resta hacia atrás el proceso de contratación. El resultado es accionable desde el
primer día: en Da Porto, el concreto se necesita el **18-ago-2026** en `ESTRUCTURA` y su proceso dura
87 días, así que debió arrancar el **23-may-2026** — hace 65 días.

## Lo que la medición cambió respecto al roadmap

El roadmap asumía un amarre **por código** entre presupuesto y cronograma. No es ejecutable:

| Qué se midió | Resultado |
|---|---|
| `codigo_actividad` poblado en el cronograma | **0 de 273 filas** |
| Cruce presupuesto ↔ cronograma por código | **0 de 403 actividades** |
| Cruce por texto exacto normalizado | **1 de 403** |

La causa es estructural: el cronograma no es el presupuesto a otra escala. Tiene 273 filas —242
actividades y 31 encabezados— frente a 403 actividades de presupuesto, y su propio árbol de frentes
(`LOCALIZACIÓN Y REPLANTEO` cuelga de `[PRELIMINARES, DAPORTO TORRE 3]`, con el capítulo embebido en
HTML dentro del campo `Actividad`).

Donde sí se hablan es en los **31 encabezados**, que son el índice de un plan de compras:
`PRELIMINARES`, `MOVIMIENTO DE TIERRA`, `ESTRUCTURA`, `MAMPOSTERÍA`, `REDES`, `RED ELÉCTRICA`,
`REVOQUE TRADICIONAL`, `ESTUCO`, `PINTURA`, `PISOS Y ENCHAPES`, `VENTANERÍA`, `CARPINTERIA EN MADERA`,
`MESONES DE COCINA`, `URBANISMO`, `VIAS INTERNAS`… con fechas escalonadas de **2026-05-25** a
**2027-12-23**. Contra ellos, 18 de 96 paquetes encuentran candidato automático; contra las 242 hojas,
solo 8 — y con un vocabulario incomparable (`VACIADO LOSA PISO 3` frente a `Suministro CONCRETO`).

**El `unique_id` es estable.** Los 273 de la semana 1 son los mismos 273 en la semana 4, sin altas ni
bajas, y ninguna fecha cambió. El amarre sobrevive a las reprogramaciones.

## Decisiones (chat + grilleo del 2026-07-27, 13 preguntas)

| Decisión | |
|---|---|
| Nivel del amarre | Paquete ↔ **encabezado** del cronograma (frente de obra), por `unique_id` |
| Cómo se construye | **Asistente con propuesta**, como el de insumos, con procedencia |
| Frentes homónimos | Se elige el `unique_id` concreto, mostrando la fecha de cada uno |
| Qué fecha manda | **La más temprana** de los frentes donde el paquete se necesita |
| Entregas repetidas | **Solo la primera**; el histórico es del submódulo de Seguimiento |
| Paquete sin frente | **Sin fecha**, en una lista de pendientes por cuantía |
| Unidad de tiempo | **Días calendario** |
| Proceso ya vencido | **En rojo, con los días de retraso, primero en la lista** |
| Pasos del proceso | Los **siete fijos** del catálogo; configurables cuando una obra lo pida |
| Granularidad | **Una fecha por paso** |
| Persistencia | **Guardadas**, anotando de qué fecha del cronograma salieron |
| Reprogramación | **Avisar del desfase**; aplicar es un acto explícito |
| Responsable | Se elige de los usuarios del proyecto, en blanco al principio |
| Duración faltante | **Mediana de su tipo**, marcada como provisional |
| Dónde vive | Pestaña **«Plan»** al final de Ensamble |

> Conflicto resuelto: en el chat se eligió «la más temprana y además avisar de las otras»; en el
> grilleo, «solo la primera, sin mencionar las demás». Prevalece la segunda por ser la pregunta
> específica sobre presentación. La decisión de fondo —manda la más temprana— es idéntica en ambas.

## Modelo de datos

Tres tablas nuevas, todas operativas (`project_id int NOT NULL`, índice liderado por `project_id`,
`utf8mb4_unicode_ci`), según `docs/global-tables-architecture.md`.

**`pdc_paquete_frente`** — el amarre. Una fila por paquete y proyecto.

```
project_id, paquete_id, unique_id, frente_nombre, fecha_ancla,
semana_origen, origen, confianza, evidencia, confirmado_humano,
asignado_por, updated_at
UNIQUE (project_id, paquete_id)
```

`fecha_ancla` y `semana_origen` guardan qué decía el cronograma cuando se amarró: es lo que permite
detectar después que el frente se movió. La procedencia replica la de `pdc_insumo_paquete` — mismo
patrón, misma tasa de acierto medible.

**`pdc_plan_paquete`** — la cabecera del plan calculado.

```
project_id, paquete_id, unique_id, fecha_ancla, fecha_arranque,
dias_totales, duracion_ref, duracion_provisional, responsable,
calculado_por, updated_at
UNIQUE (project_id, paquete_id)
```

`duracion_provisional` marca que los días salieron de la mediana del tipo y no del catálogo de AIA.

**`pdc_plan_paso`** — el detalle, una fila por paso.

```
project_id, paquete_id, orden, paso, dias, fecha_inicio, fecha_fin
UNIQUE (project_id, paquete_id, orden)
```

Tabla hija en vez de siete columnas: B1 pondrá la fecha real junto a la programada sin rehacer el
modelo, y si algún día los pasos se vuelven configurables, la estructura ya lo admite.

## Cómo se calcula

Los siete pasos, en orden, con las duraciones de `general_dias_procesos_contratacion` a través del
`duracion_ref` que A3.3-B6 ya dejó puesto en 159 de 216 paquetes:

`Elaboración de pliegos → Entrega de pliegos → Recibo de propuestas → Cuadros comparativos →
Legalización → Fabricación → Insumos en obra`

El último paso **termina** en la fecha del frente. Desde ahí se resta hacia atrás en días calendario.
Sobre el concreto (87 días) anclado en `ESTRUCTURA` el 18-ago-2026:

```
Elaboración de pliegos    7 d   2026-05-23 → 2026-05-30
Entrega de pliegos        5 d   2026-05-30 → 2026-06-04
Recibo de propuestas      7 d   2026-06-04 → 2026-06-11
Cuadros comparativos     25 d   2026-06-11 → 2026-07-06
Legalización             20 d   2026-07-06 → 2026-07-26
Fabricación               8 d   2026-07-26 → 2026-08-03
Insumos en obra          15 d   2026-08-03 → 2026-08-18
```

**Dónde entra «la más temprana».** El amarre es uno solo por paquete, así que no hay varias fechas
que comparar al calcular: la regla gobierna **qué frente se propone**. El asistente recorre la rama
que A3.4 construyó —insumo → actividad → grupo → subcapítulo—, reúne los frentes de todos los
subcapítulos donde el paquete tiene insumos y propone **el que arranca antes**, porque es el que
marca cuándo tiene que estar listo el contrato. Si el concreto aparece en subestructura (ago-2026) y
en urbanismo (2027), se propone el frente de agosto. La persona puede elegir otro.

**Duraciones faltantes.** 29 de los 96 paquetes de Da Porto que generan proceso no tienen
`duracion_ref` — el **29,2 % del valor** ($7.368M), con `Sum + Inst RED ELECTRICA` ($1.555M) a la
cabeza. Se resuelven en dos pasos: los partidos **heredan del pariente** del que salieron (`Suministro
PUERTAS EN MADERA` toma la de `CARPINTERÍA DE MADERA`, 190 días), y el resto cae a la **mediana de su
tipo** — 104 a todo costo, 95 suministro, 72 mano de obra — marcada como provisional.

## Componentes

**`PlanFechasService`** (nuevo, en `src/Services/Pdc/`). Una responsabilidad: convertir amarres en
fechas. Métodos: `frentesDisponibles()` (los encabezados de la semana activa, con HTML ya limpiado),
`sugerirFrentes()` (la propuesta del asistente), `amarrar()`, `calcular()`, `plan()` y `desfases()`.
No toca `PaquetesService`, que ya es grande: la única dependencia es la cadena de ancestros, que se
consulta, no se duplica.

**Endpoints** bajo `/plan-compras/api/plan/` con el envelope `{ok,data|error}` de siempre: `GET
/frentes`, `GET /sugerencias`, `POST /amarrar`, `POST /calcular`, `GET /` (el plan), `POST
/responsable`. RBAC: lectura con `lps.pdc.ver`, escritura con `lps.paquetes_contratacion.editar`.

**SPA:** pestaña «Plan» en Ensamble. Una fila por paquete —frente, arranque, necesidad en obra,
responsable, estado— con los vencidos arriba y en rojo; los pasos al desplegar; y dos listas aparte:
lo que no tiene frente y lo que tiene desfase por aplicar. El asistente de amarre reutiliza el patrón
de `PaquetesAsistente`.

## Verificación

- **TDD en las tres capas**, test primero, como en A3.6.
- **PHP sobre el MySQL real**: la resta hacia atrás da las fechas exactas del ejemplo del concreto;
  el último paso termina en la fecha ancla; un paquete en varios frentes toma el más temprano; sin
  frente no se inventa fecha; la duración provisional queda marcada; el desfase se detecta cuando la
  `fecha_ancla` guardada difiere de la del cronograma.
- **Vitest** para la lógica de la vista (orden por vencimiento, cálculo de días de retraso).
- **Gates** `test_global_table_safety` y `test_global_table_reconciliation`, PHPStan, y
  `test_pdc_v2_brecha_daporto` **debe seguir en 7**: A4 no toca las reglas del motor.
- **La prueba de verdad**: generar el plan de Da Porto y comprobar que el concreto sale primero con
  sus 65 días de retraso, y que las fechas del resto son coherentes con el cronograma.
- Verificación visual en el navegador integrado (`localhost:8091`, no 8081).

## Riesgos

| Riesgo | Mitigación |
|---|---|
| 78 de 96 paquetes sin candidato automático de frente | El asistente ordena por cuantía: los primeros 20 cubren el grueso del valor. La procedencia mide cuánto acierta para la siguiente obra |
| Las medianas provisionales se toman por dato real | Van marcadas en la tabla y en la vista, y se listan aparte para afinarlas |
| El plan envejece cuando el cronograma se consolida | La `fecha_ancla` guardada permite detectar el desfase y avisar; aplicar es explícito |
| El HTML del campo `Actividad` cambia de forma | El limpiado vive en un solo sitio con su test; si cambia, falla ahí y no en veinte lugares |
| A4 crece más de lo que cabe en una fase | El amarre y el cálculo son independientes: si el plan se alarga, el amarre se puede entregar solo y el cálculo después |
