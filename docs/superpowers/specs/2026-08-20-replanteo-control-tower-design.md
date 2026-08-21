---
capa: fuente
tipo: spec
estado: vigente
fecha: 2026-08-20
areas: [bi, rbac, datos, design-system]
fuente: brainstorming con Felipe bajo el método `antes-del-almuerzo`, 2026-08-20
resumen: "Replanteo completo de la Control Tower: el catálogo de métricas pasa de papel a ley, las restricciones del lookahead se vuelven el indicador principal, Power BI se jubila y la Torre pasa a escribir"
revisado: 2026-08-20 contra la data real de dev y producción
project: lps-aia
---

# Replanteo de la Control Tower — Diseño

> **Esta es una spec maestra.** El alcance decidido no cabe en un solo plan de implementación:
> son seis frentes con dependencias entre sí. Aquí se detalla el contrato completo de cada frente
> y se especifica **la fase 1 a nivel ejecutable**. Cada fase siguiente toma su propia spec
> derivada de esta antes de planificarse.
>
> Insumos: [[2026-08-20-inventario-control-tower]] (qué existe hoy) ·
> [[2026-08-20-decisiones-control-tower]] (las 66 decisiones y su porqué) ·
> [[2026-08-20-replanteo-control-tower-notas]] (hallazgos del recorrido) ·
> [[2026-08-20-que-data-tenemos]] (**medición contra la base real**, que corrigió varias decisiones).

## 1. El problema

La Control Tower existe, tiene ocho hojas, diecinueve métricas catalogadas y unas dos mil líneas de
maquinaria narrativa —pronóstico, riesgo, recomendación, linaje— y **lleva meses oculta de la
navegación**. En paralelo, la gerencia usa un informe de Power BI de cuatro páginas alimentado por
esta misma aplicación.

Felipe nombró tres males (D1): no cuenta una historia, no se confía en las cifras, y se ve y se
siente mal. La desconfianza se descompone en tres causas distintas (D2):

1. **No se sabe de dónde sale el número.** El linaje se calcula, viaja en cada respuesta del API y
   el navegador lo descarta sin pintarlo.
2. **Cada quien lo calcula distinto.** Dos motores producen las mismas cifras: Power BI recalcula en
   DAX, la Torre recalcula en PHP, y el catálogo de métricas solo *describe* — no manda. Nada obliga
   a que los tres coincidan.
3. **El dato llega tarde o incompleto**, y la pantalla lo presenta como si estuviera completo. El
   caso extremo, medido: la «Calificación Integral» de proveedores declara cinco componentes
   ponderados, cuatro de ellos tienen valor en menos del 7% de las filas, y el integral se publica
   igual en 171 de 323.

**Lo que la medición del 2026-08-20 descartó como causa:** la captura. Se registra el PAC en el 92,3%
de las actividades comprometidas y la causa en el 89,4% de las incumplidas. **El problema no es que
la obra no registre: es que lo registrado se presenta mal.**

## 2. Objetivo

Que la Control Tower sea el único lugar donde se consultan las cifras de LPS y PDC en AIA, que cada
cifra pueda defenderse sola frente a una pregunta incómoda en comité, y que la pantalla principal
produzca una acción concreta con dueño y fecha en lugar de una consulta.

**Condición de hecho del replanteo completo:** un director de obra abre la Torre un martes, ve qué
restricciones van a matar sus compromisos, asigna responsable y fecha a las que no lo tienen sin
salir de la pantalla, y puede responder «¿de dónde sale ese número?» con un clic. Y las
actividades que entran a la semana sin análisis de restricciones dejan de ser el 69%.

## 3. Estado epistémico — qué está verificado y qué no

| Afirmación | Estado |
|---|---|
| Inventario de las 8 hojas, 19 métricas, endpoints y servicios | **Verificado** contra el código, 2026-08-20 |
| Las 4 páginas del informe Power BI y sus indicadores | **Verificado** por recorrido en pantalla, 2026-08-20 |
| El filtro de Power BI no gobierna toda la página | **Verificado** por prueba directa: al filtrar una causa, tres visuales no se movieron |
| El linaje viaja al navegador y no se pinta | **Verificado**: `'lineage' => $lineage` en cada respuesta, cero referencias en `bi-spa.js` |
| No existe costo real causado en el sistema | **Verificado**: hay presupuesto, APU y valor comprometido; ninguna tabla de costo causado, facturado o pagado |
| Las restricciones no tienen responsable ni fecha comprometida | **Verificado** contra `pi_shared_constraints` y `002_bi_pi_restricciones.sql` |
| «Sin gestionar» = valor cero, la casilla nunca se movió | **Verificado con datos**: 68,9% de actividades-semana con las cinco restricciones intactas, 20,1% con análisis parcial. El patrón mixto prueba que el cero es «no se analizó» y no «no aplica» |
| Que analizar restricciones mejora el cumplimiento | **Evidencia débil**: 53,2% de PAC en las no analizadas contra 57,5% en las analizadas, sobre muestra parcial. No alcanza para sostenerlo en comité |
| Que la captura de programación semanal está sana | **Verificado**: 92,3% de PAC sobre comprometidas, 89,4% de causa sobre incumplidas, 90,1% de causa sobre no programadas |
| Que existe un plan de compras con fechas | **Refutado**: `pdc_plan_paso`, `pdc_plan_paquete` y `pdc_subpaquete` en cero en todas las obras, en dev y en producción |
| Que la obra necesita lo aquí decidido | **NO verificado.** Ver supuestos |
| Goodhart y los contrapesos (D36, D32) | **Criterio propio.** Las 186 fuentes consultadas no lo respaldan. Es lo primero que cede si el caso lo contradice |

## 4. Supuestos declarados

1. **La obra no fue entrevistada, y se decidió avanzar sin hacerlo.** El método `antes-del-almuerzo` exige de tres a cinco
   conversaciones con quienes usan el tablero. **Se hizo una, con Felipe en los roles de residente y
   director** ([[2026-08-20-entrevista-obra-felipe]]), y **el 2026-08-20 Felipe decidió avanzar sin
   entrevistar a un residente ni a un director distintos de él.**

   **Esto es un riesgo aceptado, no un pendiente.** Lo que se acepta, dicho con precisión:

   - Las decisiones D9, D33, D35 y D10 quedaron **confirmadas por una sola voz, que además es la de
     quien diseñó el tablero.** No es validación independiente.
   - D36 y D91 quedaron **en entredicho y sin resolver**: el contrapeso al PAC no aplica a quien no
     elige su compromiso, y nadie más ha dicho si eso es general o es el caso de Felipe.
   - D86 se tomó **contra la evidencia** de la única entrevista disponible.
   - Cinco necesidades (D86 a D91) salieron de una conversación de veinte minutos. **Es probable que
     una segunda voz destape otras cinco**, y esas se descubrirán construyendo, que es más caro.

   **Dónde se valida entonces.** La validación se traslada de la conversación al comportamiento
   medido, y ya tiene instrumento: el criterio de muerte de 15 —el porcentaje de actividades que
   entran a la semana sin análisis de restricciones— y la medición temprana de la respuesta al
   correo a las 4 semanas. **Si esas dos no se mueven, la hipótesis de este replanteo era falsa**, y
   se sabrá sin haber entrevistado a nadie. Es más lento y más caro que preguntar, y es la vía que
   queda.

   **La puerta sigue abierta:** el guion está probado y listo en [[2026-08-20-entrevistas-obra]]. Si
   en algún momento hay veinte minutos de un residente, se corre y manda sobre lo aquí decidido.

2. **El correo automático se construye sin haber validado a mano** que alguien responde (D50,
   tomada contra la recomendación del método).
3. **Se asume que las cifras históricas son correctas.** No se auditó un período contra la realidad
   de obra. La fase 1 incluye una verificación mínima de esto.
4. **Se asume que alguien carga el plan de compras en una semana** (D63). **No falta construir nada:
   la pantalla está desplegada y el motor propone frente para 55 de 58 paquetes; falta que alguien
   entre a amarrar y recalcular** — son dos o tres horas de una persona con permiso de editar
   paquetes de contratación. Es adopción, no desarrollo, y **un supuesto de adopción sin dueño
   nombrado no se cierra nunca**. **Cerrado el 2026-08-20 (D73): lo carga Felipe esta semana.** Si no llega, la hoja de 8.6 nace vacía
   y su vacío nombra la acción.
5. **La medición se hizo contra la base de desarrollo**, con verificación puntual en producción por
   SSH en solo lectura. Los porcentajes de llenado son señal confiable; los conteos absolutos de dev
   pueden diferir de producción. **No se trajo ninguna copia de producción.**
6. **El calendario de reuniones quedó confirmado con Felipe el 2026-08-20** (D67 a D70) y está en
   8.0. Lo que sigue abierto es **el día del comité general de gerencia**, y que ese calendario lo
   confirme la obra y no solo la gerencia. Ver [[2026-08-20-ritual-y-reuniones]].

## 5. Alcance

### Entra

- El catálogo de métricas pasa de descriptivo a **ejecutable**: la definición manda el cálculo (D5).
- La trazabilidad se pinta detrás de cada cifra (D48).
- Toda cifra declara **de qué se está parando** cuando el dato está incompleto (D6).
- La hoja de restricciones del lookahead se reconstruye y se vuelve el indicador principal (D9, D55).
- La Torre pasa a **escribir**: asignar responsable y fecha a una restricción (D33).
- Las ocho hojas se rediseñan según las decisiones D17–D47. **Ninguna se apaga** (D11).
- Cuatro métricas nuevas al catálogo (D58).
- **Higiene de datos previa (F0)**: vista de responsables, origen del eje de Productividad,
  atribución de causas sin truncar, mojibake y campos muertos.
- Power BI se jubila, incluido `/indicadores` (D8, D56).
- Un lienzo por audiencia: gerencia y obra (D52).
- Correo automático con la señal (D50).

### No entra

- **La vista de cliente o socio** (D53): se difiere hasta que el cimiento esté puesto.
- **Conectar contabilidad** para el índice de costo del valor ganado (D27): frente propio.
- **Simulador de escenarios general** (D25): solo análisis de riesgo ahora; el what-if acotado a
  restricciones va después del cimiento.
- **Interruptor por obra**: el interruptor sigue siendo global.
- Telemetría de aperturas: el criterio de muerte se mide por las restricciones sin dueño (D51).
- Capa de BI genérica, bodega de datos o programa de gobierno de tableros.

## 6. Arquitectura del cimiento — el catálogo ejecutable

### El problema concreto

`MetricDictionaryService` declara, por ejemplo, para `ps_weekly_fulfillment`: una definición, una
fórmula en texto, una fuente (`bi_ps_compromisos`), un grano, una política de corte, filtros y una
política de agregación. **Nada de eso se ejecuta.** `ControlTowerService` calcula la misma métrica
con su propio SQL. Si alguien cambia el SQL y no el catálogo, el catálogo miente y nadie se entera.

### La solución

El catálogo se convierte en la **única fuente de la consulta**. Cada métrica declara, en datos y no
en prosa:

```
metric_key            identificador estable
report_key            hoja a la que pertenece
source                vista o tabla de ejecución (bi_ps_compromisos, …)
select                expresión de agregación (COUNT(DISTINCT …), SUM(…)/NULLIF(…,0)*100)
filters[]             condiciones obligatorias, como pares columna/operador/valor
grain[]               columnas que definen el grano
cutoff                política de corte (semana seleccionada, rango, fin de semana activa)
completeness[]        qué hace falta para considerar la cifra completa (ver 6.2)
supports_multi_project / supports_date_range
counterweight         métrica que debe mostrarse siempre al lado (D36) — puede ser nula
version               versión del contrato de la métrica
```

Un único **ejecutor de métricas** toma esa declaración, arma la consulta con sentencias preparadas
y devuelve valor, grano y completitud. `ControlTowerService` deja de escribir SQL de métricas y pasa
a pedirle al ejecutor.

**Regla dura:** si una métrica se puede calcular por dos caminos, la spec está mal. Una métrica,
una declaración, un ejecutor.

**Segunda regla dura, que sale de los errores de 19:** ninguna métrica se declara sin declarar
también su **denominador**. Un porcentaje sin su base no es una cifra, es una insinuación. El
ejecutor devuelve siempre la base junto al valor (ver 6.2).

**Métrica nueva que nace del vacío detectado:** `compras.duracion_real_paso`, por tipo de paquete,
fuente `pdc_plan_paso.fecha_real`, **en estado `descriptiva` hasta que haya al menos 20 pasos
cerrados por tipo**. Es la que empieza a construir la memoria de duraciones que AIA no tiene, y el
umbral evita publicar una mediana de tres datos.

### 6.1 Migración métrica por métrica, sin bloquear la pantalla

Las diecinueve no se migran de golpe. Cada métrica pasa por tres estados:

| Estado | Qué significa |
|---|---|
| `descriptiva` | Como hoy: el catálogo describe, `ControlTowerService` calcula. Estado de partida |
| `en_paridad` | Los dos caminos corren y **una prueba compara sus resultados**. Discrepancia = falla |
| `ejecutable` | El catálogo manda; el SQL viejo se borra |

Ninguna métrica pasa a `ejecutable` sin haber estado `en_paridad` con resultado idéntico sobre al
menos cuatro semanas reales de al menos dos obras. **La prueba de paridad es el entregable, no un
paso opcional** — es lo que impide cambiar de motor y de resultado a la vez.

### 6.2 Declaración de completitud (D6)

Cada métrica declara qué necesita para estar completa. El ejecutor devuelve siempre, junto al valor:

```
value                 la cifra
basis                 { obras_incluidas, obras_esperadas, corte, filas_usadas }
completeness          completa | parcial | insuficiente
missing[]             qué falta, en lenguaje de negocio
```

Reglas de presentación, obligatorias en toda hoja:

- `completa` → la cifra se muestra normal.
- `parcial` → la cifra se muestra **con su base declarada al lado**, en texto:
  «calculado con 8 de 12 obras, corte al viernes 8 de agosto».
- `insuficiente` → **la cifra no se muestra.** En su lugar, qué falta y quién lo carga.

**Caso obligatorio de aplicación:** la calificación integral de proveedores (D44). Sus cinco
componentes son partes declaradas de la métrica; con cuatro vacíos, el integral es `insuficiente` y
no se publica. Los componentes con dato se muestran por separado.

Medido el 2026-08-20 sobre 323 filas de `bi_cic_contratistas`, cuántas traen valor mayor que cero:

| Componente | Peso declarado | Con valor |
|---|---|---|
| PAC | 30% | 229 (71%) |
| Calidad | 20% | **5 (1,5%)** |
| Social-Ambiental | 20% | **23 (7%)** |
| SST | 20% | **6 (1,9%)** |
| Administración | 10% | **6 (1,9%)** |
| Calificación integral | — | **171 (53%)** |

Es decir: **hoy se publica en 171 filas un número que en la práctica es el PAC con otro nombre.**

### 6.3 Trazabilidad visible (D48)

El bloque `lineage` ya viaja en cada respuesta. Se pinta:

- Cada cifra del lienzo lleva un control discreto «de dónde sale esto».
- Al abrirlo: nombre, definición en una frase, fórmula en lenguaje de negocio, fuente, grano,
  política de corte, versión del contrato, limitaciones conocidas y la base de este cálculo
  concreto (el `basis` de 6.2).
- El control es alcanzable por teclado y anunciable por lector de pantalla. No depende de hover:
  **la lección del informe de Power BI, donde la curva S vive en un tooltip y no existe para nadie
  que proyecte, capture o toque.**

## 7. El modelo de datos de restricciones — prerrequisito con gate propio

### 7.1 Lo que hay hoy, verificado

- `bi_pi_restricciones` es **una vista**, no una tabla. Desnormaliza cinco columnas de
  `programa_consolidado` (D_y_E, Materiales, MdeO, y las demás restricciones duras) como filas, con
  `restriction_value` entre 0 y 1 contra un `required_threshold` de 1.0, más `is_ready` e `is_hard`.
- `pi_shared_constraints` tiene: `project_id`, `Id`, `Semana`, `Restriccion`, `ValorObjetivo`,
  `Nota`, `CreadoPor`, `CreadoEn`, `ActualizadoEn`.
- `pi_shared_constraint_links` la enlaza con actividades del programa.

**No existe: responsable asignado, fecha comprometida de liberación, ni estado de liberación.**

### 7.2 La consecuencia

Los tres estados que muestra Power BI —liberada, en proceso, sin gestionar— **no son un campo de
estado**: son lecturas del valor numérico (1.0, intermedio, 0). Por eso «sin gestionar» significa
literalmente *la casilla nunca se movió*.

Verificado el 2026-08-20 sobre 45.600 actividades-semana:

| Patrón sobre las cinco restricciones duras | Cantidad | % |
|---|---|---|
| **Ninguna tocada** | 31.396 | **68,9%** |
| Mixto — hubo análisis real | 9.182 | 20,1% |
| Todas listas | 5.022 | 11,0% |

**El patrón mixto es la prueba de que el cero significa «no se analizó» y no «no aplica»**: cuando
alguien abre el análisis, marca unas restricciones y deja otras. Si el cero fuera «no aplica», lo
mixto sería la norma y no el 20%.

Y por eso **D30 y D33 no se pueden construir sobre el modelo actual**: no se puede asignar
responsable ni fecha a algo que no tiene dónde guardarlos.

### 7.3 El cambio necesario

Se agregan a `pi_shared_constraints`:

| Columna | Tipo | Para qué |
|---|---|---|
| `ResponsableAsignado` | varchar, nulo | Quién responde por liberarla |
| `FechaCompromiso` | date, nulo | Cuándo se comprometió a liberarla |
| `EstadoLiberacion` | enum: `sin_gestionar`, `en_gestion`, `liberada`, `no_aplica` | Estado explícito, en vez de inferido del valor. **`en_gestion` no es decorativo (D87): es el avance parcial, y se muestra.** «Recortar el problema paulatinamente» es la gestión que la obra dice que haría, y un tablero que solo cuenta liberadas la castiga |
| `AsignadoPor` / `AsignadoEn` | varchar / datetime, nulos | Auditoría de quién asignó |

**Este cambio es una migración de esquema y se rige por el contrato del repositorio**
(`docs/global-tables-architecture.md` y AGENTS.md): dry-run primero, gate de Plannotator, respaldo
verificable, estrategia de restauración y reconciliación posterior. **No se ejecuta como parte de
una tarea de interfaz.**

Compatibilidad: `EstadoLiberacion` se rellena en la migración a partir del valor actual
(`0` → `sin_gestionar`, intermedio → `en_gestion`, `>= 1.0` → `liberada`), de modo que el número
histórico no cambia el día de la migración. Verificar que el total reconstruido coincide con el que
hoy muestra Power BI **antes** de dar la migración por buena.

## 8. Las ocho hojas

Cada hoja declara: la decisión que habilita, quién actúa, qué se muestra primero, y qué baja a
desglose. Todo elemento no listado como «lienzo» va a desglose o desaparece.

### 8.0 El ritual — cuándo se usa cada hoja

Medido el 2026-08-20 contra `semanas_activas` y el dominio (ver [[2026-08-20-ritual-y-reuniones]]).
**La semana no arranca el lunes: cada obra tiene su día ancla** — la obra 70 siempre martes, la 65
viernes, la 62 jueves. Cualquier diseño que asuma lunes pone el correo y la semana por defecto en el
día equivocado para la mayoría de las obras.

**Confirmado con Felipe el 2026-08-20** (D67 a D70). Ya no son supuestos:

| Reunión | Ritmo | Hoja que se proyecta | Acción | Quién registra |
|---|---|---|---|---|
| **Semanal de obra**, en su día ancla | Semanal, **una sola sentada**: se cierra la semana que termina y se compromete la que entra | **8.3 Intermedia** y **8.4 Semanal**, seguidas | Liberar restricciones, asignar dueños, comprometer | El residente, en vivo |
| **Diaria de obra**, corta y sin computador | Diaria | **8.4**, solo el riesgo de incumplimiento | Salvar el compromiso que va a caerse | El residente |
| **Comité de gerencia por obra** | Por obra, en su día | **8.2 Programa General** | Evaluar el riesgo de la fecha de entrega de esa obra | — |
| **Comité general de gerencia** | **Semanal, los lunes**; compara todas las obras | **8.1 Resumen Ejecutivo** | Decidir en qué obra meterse | — |
| **Comité de compras** | **Existe, pero sin día fijo: varía por obra** | **8.6** y **8.7** | Destrabar el paso vencido | Compras |
| Rendición al cliente | Mensual o por hito | **8.5 Curva S** | Conversación de contrato | — |

**Que el comité de compras no tenga día fijo es la razón de fondo de D76**: una señal que depende de
una reunión sin calendario no puede ser un correo de calendario.

**Solo 8.3 y 8.4 se proyectan en la reunión semanal de obra. 8.2 y 8.5 son de ritmo mensual; 8.6 y
8.7 son del comité de compras; 8.1 llega por correo a gerencia; 8.8 no se proyecta nunca.**

Hoy cada hoja declara su decisión y ninguna declara su momento. Sin momento, la hoja se abre cuando
alguien se acuerda — que es exactamente como la Torre lleva meses.

### 8.1 Resumen Ejecutivo — audiencia gerencia

- **Decisión (D17):** en qué obra meterse esta semana. Acción: llamar hoy a ese director.
- **Lienzo:** titular narrativo que afirma qué pasó y por qué (D19) · **panorama de obras**: una
  fila por obra con su señal de restricciones, su desviación y su tendencia (D18) · acciones
  recomendadas, cada una con nombre y fecha (D20).
- **Se retiran del lienzo:** las dos gráficas actuales (PAC contra programado, PPC semanal), que
  repiten lo que ya está en Semanal.
- **Regla:** es la única hoja que compara obras entre sí.
- **Su reunión existe: el comité general de gerencia** (D67), donde se comparan todas las obras.
  Esta hoja **se proyecta ahí**. Confirmado el 2026-08-20; corrige el supuesto anterior de la spec,
  que la daba por inexistente y ponía a la hoja en riesgo de cementerio. **Es semanal, los lunes**
  (D81), así que las señales de gerencia deben estar puestas antes del fin de semana.

### 8.2 Programa General — audiencia obra y dirección

- **Decisión (D21):** el director reordena la ventana de seis semanas · gerencia y dirección evalúan
  el riesgo de la fecha de entrega · valor ganado.
- **Titular (D23):** el pronóstico P50 de terminación, **siempre con su margen de incertidumbre**.
- **El número de desviación (D24):** en palabras —«88 días de retraso»—, **y además la fecha
  proyectada de terminación contra la comprometida**. Nunca signo ni color solos.
- **Radar (D22):** se conserva, con escala corregida y mayor tamaño. Cada eje conserva su
  «Cómo se calcula». **Prerrequisito (D62): verificar de dónde sale hoy el eje de Productividad**,
  porque `medir_productividad` está en 0% de llenado y el radar pinta un valor igual. O sale de otra
  fuente —y entonces el catálogo miente sobre su propio origen, que es lo que 6 viene a matar— o el
  eje muestra algo distinto de lo que su nombre dice. El eje no se conserva hasta aclararlo.
- **Riesgo (D26):** combinado — restricciones sin liberar y su antigüedad, ponderadas por si caen en
  ruta crítica. La pantalla debe explicar la ponderación en una frase.
- **Valor ganado (D27, acotado por D66):** solo desempeño de cronograma en plata, con el presupuesto
  y APU existentes. **La pantalla declara explícitamente que no incluye costo real.** Y se calcula
  **solo donde hay presupuesto cargado**: hoy dos obras (27 y 73, 523 ítems cada una). El programa
  **no tiene columna de valor, precio ni peso** —`cantidad_ppto` está en 223 filas—, así que la cifra
  sale de cruzar insumos con actividades, no del programa. Donde no haya presupuesto, la métrica es
  `insuficiente` según 6.2 y no se muestra.
- **Causas de no cumplimiento:** aquí solo el titular; el detalle vive en Semanal (D39).
- **Ritmo: mensual o por hito**, en revisión de gerencia. No se evalúa con el criterio de muerte de
  15, que es semanal.
- **Desglose:** aporte por actividad, retraso observado, detalle de radar, detalle de cumplimiento.

### 8.3 Programación Intermedia — la hoja del indicador principal

- **Decisión:** liberar hoy la restricción que va a matar el compromiso de dentro de tres semanas.
- **Las dos lecturas del cero, separadas y rotuladas (D59).** No se mezclan nunca:
  - **Adherencia al método**, como cifra dura: «el 69% de las actividades entró a la semana sin
    análisis de restricciones». Se sostiene sola, no necesita correlación, y la acción es abrir ese
    análisis antes del lunes.
  - **Señal predictiva**, marcada como estimación con su nivel de certeza. **No se rotula «estas van
    a fallar»**: la evidencia disponible da 53,2% de PAC en las no analizadas contra 57,5% en las
    analizadas, y eso no aguanta un comité.
- **Lienzo, en orden (D28, D30):**
  1. **Alarma de huérfanas:** las restricciones sin análisis ni dueño, con la acción de asignarlas.
  2. Titular narrativo: qué está pasando con el lookahead y por qué.
  3. **Lista de restricciones por liberar, ordenada por urgencia**: restricción, actividad que
     bloquea, responsable, fecha comprometida, días de vencida, actividades afectadas, y si la
     actividad está en ruta crítica.
  4. Semáforo por semanas para iniciar (0 a 6), reconstruido desde Power BI (D55, D58).
  5. Pareto de restricciones no liberadas, como contexto (D34).
- **Los tres estados se ven (D87):** sin gestionar · en gestión · liberada. Quien recortó de tres
  problemas a uno tiene crédito visible.
- **Cada restricción muestra su encadenamiento (D88):** qué actividades cuelgan de ella y cómo eso
  empuja la fecha de entrega. Es lo que permite decidir cuál liberar primero, y fue un pedido
  explícito de la obra. **Riesgo declarado:** exige que la ruta crítica esté bien mantenida en el
  programa.
- **Cada alerta trae su acción sugerida y a quién acudir (D89).** «Materiales sin liberar: llame al
  proveedor, o escale a compras.» Sale del hallazgo más incómodo de la entrevista: *«sabía que se
  iba a caer y no hice nada porque no sabía cómo resolverlo»*. Señalar el problema no basta si quien
  lo ve no sabe qué hacer; `ActionRecommendationService` ya existe y hoy casi no se usa.
- **Acción en pantalla (D33):** asignar responsable y fecha a una restricción sin salir de la hoja.
- **Contrapeso de captura (D32):** junto al ranking de causas, quién lo registró y cuántas quedaron
  sin causa. Con una nota explícita de que cada responsable registra la suya.
- **La atribución no se trunca (D65).** El catálogo distingue «Actividad predecesora incompleta / no
  ejecutada **(obra)**» (502 veces) de «**(subcontratista)**» (297) y de la variante sin atribuir
  (224). No eran duplicados: **la gráfica corta el texto justo donde está la atribución de culpa**,
  que es el dato más político del tablero. Solo la tercera variante es deuda de catálogo.
- **Abierto:** confirmar con Felipe si el titular narrativo va arriba de la lista o al revés.

### 8.4 Programación Semanal — audiencia obra

- **Decisión (D35):** principal, **el director prepara la reunión semanal de su obra, que cae en el
  día ancla de esa obra** — no el lunes: medido, viernes en 38 semanas, martes en 36, lunes en 34,
  jueves en 28, miércoles en 19. Secundarias: el residente revisa sus compromisos a diario; gerencia
  compara entre obras.
- **El riesgo de incumplimiento (D38) es la señal de la reunión diaria**, no de la semanal: entre una
  reunión y otra es lo primero que ve el residente, y por eso debe caber en el móvil.
- **Protagonista (D38): «Riesgo de incumplimiento» por compromiso.** Es la métrica hoy llamada
  `ps_pac_expected`, mal bautizada: su fórmula real es 25% histórico del contratista + 20% del
  responsable + 15% criticidad + 20% restricciones + 10% avance + 10% CNC, y estima **compromiso por
  compromiso cuál se va a caer**. Hoy está marcada `planned_for_programacion_semanal` — calculada y
  nunca integrada. No proyecta con menos de tres observaciones históricas: en ese caso la métrica es
  `insuficiente` según 6.2 y no se muestra.
- **PAC (D37):** «17 de 20 compromisos» como cifra principal, el porcentaje al lado, sin decimales
  de más. El compromiso es binario.
- **Contrapeso (D36):** el conteo de compromisos nunca se separa del porcentaje, y se muestra la
  variación contra la semana anterior para destapar el encogimiento gradual del compromiso.
- **Límite de interpretación del PAC (D91).** El contrapeso de D36 se diseñó contra la trampa de
  comprometerse a menos. **A un residente al que le ordenan comprometerse sin criterio esa trampa no
  está a su alcance**, y su PAC bajo mide presión de arriba, no su planificación. La hoja no puede
  presentarse como evaluación de quien no eligió su compromiso. Evidencia:
  [[2026-08-20-entrevista-obra-felipe]].
- **La captura de esta hoja está sana y no se toca (D60):** 92,3% de PAC sobre comprometidas, 89,4%
  de causa sobre las incumplidas, 90,1% de causa sobre las no programadas. Cualquier diseño que
  parta de «aquí no se registra nada» está partiendo de una medición mal hecha.
- **Causas de no cumplimiento (D39):** viven aquí, con su desglose a subcausa **por clic, no por
  hover**.
- **La hoja se diseña para proyectarse** en el comité, y admite filtro por persona en desglose.

### 8.5 Curva S

- **Decisión (D40):** rendición hacia arriba y hacia afuera, y disparar la replanificación cuando la
  brecha pasa un umbral. El umbral se define con Felipe antes de construir.
- **Lienzo (D41):** teórica, real, y proyección a fecha probable de entrega **con banda de
  incertidumbre**, con la misma matemática del P50 de 8.2.
- **Ritmo: mensual o por hito**, en rendición al cliente. No se evalúa con el criterio de muerte de
  15, que es semanal.

### 8.6 Plan de Compras

> **Corregido el 2026-08-20.** Esta hoja no espera desarrollo: **espera dos clics.** La cadena
> paquetes → amarre a frente → «Recalcular» **está construida y desplegada en producción**, y el
> motor propone frente para **55 de los 58 paquetes contratables** de Da Porto. Lo que no ha
> ocurrido es que alguien entre a `#/ensamble/plan`: `pdc_paquete_frente` está en cero. Cargar el
> plan son **2 o 3 horas de una persona** con permiso de editar paquetes de contratación, sin una
> línea de código. **Es adopción, no trabajo pendiente** — y un supuesto de adopción sin dueño
> nombrado es exactamente el error que el método señala. D63 es realista **solo con nombre y fecha**.

- **Decisión (D42):** compras destraba el paso vencido de hoy · gerencia vigila la cobertura del
  presupuesto · el director anticipa qué le va a faltar.
- **Lienzo:** lista de pasos de contratación vencidos, con responsable y días de vencido —gemelo de
  la lista de restricciones— · cobertura por conteo y por valor · escala de vencimiento.
- **Escala (D43):** ya vencido · esta semana · en 2 · en 3 · en 6 semanas · **sin fecha, tratada
  como alarma**, igual que las restricciones huérfanas.
- **«Vencido» no es lo mismo que «sin avance registrado».** El plan nace con la fecha real en blanco
  en todos los pasos, así que la regla de D43, aplicada tal cual, **pintaría de rojo contratos ya
  firmados** el primer día. Un paso con fecha planeada vencida y sin fecha real se muestra como
  **sin avance registrado** hasta que la obra haya registrado al menos un avance en ese paquete.
- **La escala se rotula «planeado», no «estimado».** Sale del catálogo de duraciones de AIA
  (`general_dias_procesos_contratacion`, 216 paquetes con siete duraciones cada uno), que es
  **criterio experto vigente y diferenciado por paquete** — no un número genérico. Lo que aún no
  existe es el contraste con la ejecución real, y por eso «planeado» y no «estimado». **43 de los 62
  paquetes de Da Porto traen su duración de referencia propia; los otros 19 caen a la mediana de la
  empresa** y se marcan como **duración provisional**, que no se lee con la misma autoridad.
- **Contrapeso obligatorio (D36):** al lado de la escala, el contador **«pasos cerrados con fecha
  real / pasos planeados»**. Es lo que empieza a construir la memoria que hoy no existe, y lo que
  impide leer un calendario de catálogo como si fuera una predicción calibrada.
- **Tercera cifra en la cobertura: paquetes sin responsable.** Sin responsable no hay a quién llamar
  cuando un paso vence, y la hoja no pasa el test de la decisión.
- **Si la hoja nace vacía, el vacío nombra la acción:** «58 paquetes esperan fechas — amarrar y
  recalcular en Ensamble › Plan», con enlace directo. Un «no hay datos» es museo; un «falta este
  botón, aquí» es una decisión que alguien toma antes del almuerzo.

### 8.7 Proveedores

- **Decisión (D45):** a quién no volver a contratar · a quién apretar esta semana · insumo del
  comité de compras.
- **Calificación integral (D44):** **no se publica hasta tener sus cinco componentes.** Se muestran
  por separado los que tienen dato; el integral aparece inhabilitado, declarando qué falta y quién
  lo carga. Es el caso de aplicación obligatoria de 6.2.

### 8.8 Responsables

> **Riesgo asumido y declarado (D86).** Felipe confirmó en la entrevista que **la causa se cambia
> cuando el jefe la va a ver** —«ha pasado»— y aun así decidió conservar que el jefe vea a su equipo
> completo. En consecuencia, **el contrapeso de D32 es obligatorio en esta hoja y en el ranking de
> causas**, y ese ranking no puede sostener por sí solo ninguna decisión sobre personas.

> **Esta hoja no se proyecta en ninguna reunión, por diseño.** D46 y D47 la hacen de consulta
> personal y de conversación uno a uno. Su distribución es un correo al jefe directo; nunca entra al
> lienzo de la reunión semanal. Proyectarla es la reconvención pública que D47 prohíbe.

> **Bloqueante de esta hoja (D61):** la vista `bi_cip_responsables` devuelve **una sola fila**,
> mientras `Responsable_AIA` está lleno en 5.223 filas del programa. Es defecto de la vista, no falta
> de datos. **La hoja no se construye hasta arreglarla.**

- **Propósito declarado en la propia pantalla (D47): ver quién necesita ayuda, no quién falla.** La
  acción sugerida es descargar o destrabar, nunca reconvenir.
- **Visibilidad (D46):** cada quien la suya; el jefe ve su equipo. **Nunca todos ven a todos.**
  Se implementa como filtro de datos en el servidor, no como ocultamiento en el cliente.
- Junto a la alerta se muestran siempre las restricciones abiertas y la carga de compromisos de esa
  persona, que es lo que suele explicar el cumplimiento bajo.

## 9. Filtros y períodos

| Decisión | Regla |
|---|---|
| D13 | **El rango de fechas manda.** «Semana 10» es un atajo que rellena el rango. Un solo motor de período por debajo |
| D14 | Por defecto, **la semana en curso**, resuelta **por obra contra su propia fecha de inicio de semana**, no contra el lunes calendario. En el día ancla, la hoja Semanal abre con la semana que cierra y ofrece el paso a la que entra |
| D15 | Por defecto, **las obras que corresponden al cargo**: gerencia todas las suyas, el director la suya |
| D16 | La barra principal queda con **obra y período**. Subcontratista, responsable y etapa bajan al desglose |

**Regla dura, de la lección de Power BI:** todo filtro activo gobierna **toda** la hoja, o la hoja
declara junto a cada cifra que no la gobierna. No se admite una pantalla donde media cifra habla de
tres actividades y la otra media de la obra entera.

## 10. La Torre pasa a escribir

Consecuencia de D33, y el cambio de naturaleza más grande de este replanteo.

- **Qué se puede escribir:** responsable y fecha comprometida de una restricción, y su estado de
  liberación. Nada más.
- **Quién:** roles con capacidad de gestionar la programación intermedia de esa obra. Se resuelve
  con `RbacManager` y la capacidad correspondiente, normalizando el rol con
  `RbacService::normalizeRole()`. **No se inventa una capacidad nueva sin pasar por `RbacCatalog`.**
- **Cómo:** mutación autenticada con CSRF, sentencias preparadas a través de `Database`, aislada por
  `project_id`, con registro de quién asignó y cuándo (`AsignadoPor`, `AsignadoEn`).
- **Verificación obligatoria:** un rol permitido y un rol denegado, por la puerta de servicio de
  desarrollo (`/dev/entrar`), según el routing de RBAC de AGENTS.md.

## 11. Jubilación de Power BI

- **Orden obligatorio (D55):** primero se reconstruye en la Torre la hoja de Liberación de
  Restricciones —semáforo por semanas para iniciar, liberación general, pareto y actividades
  afectadas—; **solo después** se retira el informe.
- `/indicadores` también se jubila (D56), en su propio momento, después de la Torre.
- Antes de retirar cada uno: verificar que toda cifra que la gerencia usaba tiene equivalente en la
  Torre, y que coincide. Una discrepancia detectada en ese momento es una ganancia, no un problema
  — es exactamente lo que la desconfianza anunciaba.
- La página de SharePoint se conserva como enlace a la Torre durante un período de transición, para
  no romper el hábito de golpe.

## 11.b La distribución: por evento, nunca por calendario

Decisión de Felipe, D76. **Ningún correo de la Torre se dispara por fecha del calendario.** Un correo
de calendario es un recordatorio, y los recordatorios se filtran; el método reserva el empuje para la
señal excepcional. Lo hizo inevitable D70: si el comité de compras no tiene día fijo, su señal
tampoco puede tenerlo.

**Los cuatro disparadores** (D77), todos anticipatorios — avisan mientras todavía se puede actuar:

| Evento | A quién | Por qué es accionable |
|---|---|---|
| Una restricción se queda sin dueño a **tres semanas** de iniciar | Director y residente de esa obra | Es la señal del indicador principal, con margen para liberarla |
| Un paso de contratación entra en **ventana de vencer** | Compras | Reemplaza el correo de calendario que el comité sin día fijo no permitía |
| Un compromiso queda marcado en **riesgo alto de incumplir** | El responsable | La señal que avisa el martes en vez de contar el viernes |
| Una obra **cruza un umbral de desviación** en su fecha de entrega | Gerencia | Algo se salió de cauce en esta obra, antes del comité general |

**Reglas de redacción y de freno:**

- **La certeza se declara siempre.** «Tres compromisos en riesgo alto», nunca «tres compromisos van a
  fallar». Cumple D59: lo predictivo se rotula como estimación, no como hecho.
- **Cada aviso lleva su marcador histórico de acierto** (D80): «de los 10 que marqué en riesgo el mes
  pasado, fallaron 7». Es lo único que convierte un pronóstico en algo a lo que vale la pena hacerle
  caso, y empieza a construir desde el día uno la calibración que hoy no existe en AIA.
- **Un solo correo diario por persona** (D78), agrupando todo lo suyo. El día que lleguen seis
  correos, alguien crea una regla de bandeja y el canal se pierde para siempre.
- **El correo de víspera se conserva** (D79) como resumen para el director, el día antes de la
  reunión semanal de su obra. Es otro trabajo: el de evento avisa cuando pasa algo, el de víspera
  prepara la reunión.
- **Cada línea del correo lleva enlace directo a la acción**, no a la portada del módulo.
- **La señal va por dos canales: correo y notificación dentro de la aplicación** (D90). Fue un
  pedido explícito de la obra, y la campana ya existe para restricciones.
- **Cada aviso trae su acción sugerida y a quién acudir** (D89), no solo el hecho.

**Riesgo de gobernanza:** el correo nombra responsables. Va a director y residente de la obra; en la
primera versión, a nadie más. Ampliarlo hacia gerencia o hacia el subcontratista es una decisión
aparte, no una comodidad.

## 12. Diseño visual

Se rige por `DESIGN.md` y `docs/design-system/`. Reglas propias de este replanteo, que el sistema de
diseño no cubre:

- **Todo en reposo, en gris.** Color saturado solo donde hay anomalía y en una fracción mínima de la
  pantalla. Es la corrección directa del informe actual, donde todo está en rojo y por eso nada
  avisa.
- **Nunca codificar solo por color.** Siempre con signo, ícono o palabra.
- **Títulos que afirman, no que rotulan.** «El cumplimiento cayó por personal insuficiente del
  subcontratista», no «Causas de no cumplimiento».
- **Nada valioso vive en un hover.** Todo lo que importa se alcanza por clic o teclado, se proyecta
  y sale en una captura.
- **Contexto obligatorio:** ninguna cifra sola. Siempre contra meta, contra período anterior o
  contra umbral.
- Un lienzo por audiencia (D52): gerencia y obra, **entendidos como composiciones de hojas
  compartidas, no como productos aparte**. Regla dura: **una hoja, un diseño.** Dark, 1180×820 como
  viewport canónico de validación.

## 13. Fases

Cada fase se publica antes de abrir la siguiente, según el gate de cierre de frente de AGENTS.md.

| # | Fase | Contenido | Condición de hecho |
|---|---|---|---|
| **F0** | **Higiene de datos** | Arreglar la vista de responsables · aclarar el origen del eje de Productividad · corregir el mojibake del catálogo de causas · dejar de truncar la atribución · decidir los campos muertos · **retirar las tablas del PDC v1, con su gate de borrado** | Ninguna cifra de la Torre viene de una fuente que no es la que declara, y ningún texto de causa se corta donde dice de quién es la culpa |
| **F1** | **Cimiento** | Ejecutor de métricas · las 19 métricas de `descriptiva` a `ejecutable` con prueba de paridad · declaración de completitud · trazabilidad pintada · calificación de proveedores inhabilitada · **T1 con su gemelo en el servicio: una clase por hoja detrás de una interfaz común** | Toda cifra de la Torre sale del catálogo, ninguna se calcula dos veces, cada una responde «de dónde salgo» con un clic |
| **F2** | **Restricciones** | Migración de esquema con su gate · métricas nuevas · hoja de Intermedia reconstruida · alarma de huérfanas · lista accionable · escritura desde la Torre | Un director asigna responsable y fecha a una restricción sin salir de la hoja, y el conteo de huérfanas baja |
| **F3** | **Narrativa** | Resumen Ejecutivo con panorama de obras · acciones con dueño · riesgo de incumplimiento por compromiso rebautizado e integrado · Semanal rediseñada | La hoja abre con una frase que afirma qué pasó y por qué, y debajo qué hacer y quién |
| **F4** | **Salir del escondite** | Interruptor encendido · entrada en navegación · **disparador por evento** (no existe hoy: el único correo del sistema es el de contraseña y las alertas de restricciones son campana dentro de la app) · **los cuatro avisos de D77**, agrupados en **un solo correo diario por persona** · **correo de víspera** como resumen para el director, el día antes de la reunión de su obra | El módulo está en el menú, **la señal llega cuando pasa algo y no cuando toca reunirse**, y se mide qué porcentaje de las huérfanas listadas tiene dueño 48 horas después |
| **F5** | **Jubilación** | Liberación de Restricciones completa · retiro del informe Power BI · después, `/indicadores` | Una sola casa para las cifras, **y una persona distinta a Felipe ha hecho, documentado, un deploy y un cambio de métrica por catálogo** |
| **F6** | **Diferidos** | What-if acotado a restricciones · vista de cliente · contabilidad para el índice de costo | Cada uno con su propia spec |

**F0 y F1 son las únicas especificadas aquí a nivel ejecutable.** F2 en adelante derivan su propia
spec. F0 es barata y desbloquea: sin ella, tres hojas muestran cifras cuyo origen no se puede
defender, que es exactamente lo que este replanteo viene a curar.

### El frente aparte: el retiro de las tablas del PDC v1 (D64)

**Corregido el 2026-08-20 contra producción.** Esta sección afirmaba que la tabla `pdc` guardaba
«409 planes de compras completos» y que era «el único historial de desempeño de compras que existe».
**Las dos cosas son falsas**, y salieron de contar filas sin mirar el llenado de las columnas.

Lo que hay de verdad en esas 409 filas:

| Obra | Filas | Con fecha planeada | Con fecha real | Con valor o contrato |
|---|---|---|---|---|
| Optimización Aeropuerto JMC (68) y Milán Campestre Torre 19 (74) | 273 | **0** | **0** | **0** |
| Prueba (27) | 126 | 126 | **4** (todas del mismo día, en pares duplicados) | **0** |

Las seis columnas de valor —presupuesto, primera negociación, adjudicado, anticipo, reclamado,
devoluciones— están en NULL en las 409. `general_informe_pdc` y `bi_pdc_general` repiten el mismo
esqueleto. **Son plantilla, no historial.**

Consecuencias directas:

1. **No hay duraciones reales, ni desvío de precio, ni cumplimiento por proveedor que extraer.** No
   se construye ningún importador ni pronóstico de compras alimentado por el v1: no hay entrada.
2. **El frente deja de ser «rescate» y pasa a ser «retiro de las tablas del PDC v1»**, y sale de los
   diferidos: es higiene, y va en F0 o como parte de F5.
3. El archivo histórico se reduce a la estructura de las tablas más **el CSV de las 126 filas
   planeadas de «Prueba», que Felipe confirmó como borrador de un plan real y pidió conservar**
   (D83): es lo único con contenido de las 409 filas, y sirve de referencia para el primer plan de
   Da Porto.
4. Al retiro se suman las hermanas `general_informe_pdc`, `bi_pdc_general`, `papelera_pdc` y
   `backup_licify_general_informe_pdc_20260612`. **La comprobación de que ningún informe vivo de
   Power BI las lee es prerrequisito del borrado, no un supuesto** (D84): el retiro espera a esa
   verificación.

**El gate de borrado se mantiene intacto** (D64): que el contenido sea plantilla no releva el visto
explícito de Felipe, el respaldo verificable, la comprobación de que el archivo se lee fuera de
producción, ni el plan de restauración. Nada de esto se ejecutó.

**Respondido el 2026-08-20 (D82): no hay historial de ejecución en ninguna parte.** JMC y Milán
**deben construir su plan en Last Planner AIA y está pendiente**; sus 273 filas vacías son plantillas
esperando a que alguien las llene. No hubo Excel ni Licify que rescatar.

**Pero eso no significa que AIA no tenga memoria de duraciones — la tiene, y es vigente** (D85). Hay
que distinguir dos cosas que esta spec confundió:

| | Existe | Estado |
|---|---|---|
| **Memoria experta**: cuánto se sabe que tarda contratar cada paquete | **Sí**, y es rica | `general_dias_procesos_contratacion`: **216 paquetes con siete duraciones cada uno** —pliegos, entrega, propuestas, cuadros comparativos, legalización, fabricación, insumos en obra—, diferenciadas por paquete y por tipo. Más `duracion_ref` en 169 de los 221 paquetes del catálogo |
| **Memoria medida**: cuánto tardó de verdad la última vez | **No** | Nadie ha contrastado nunca lo planeado con lo ocurrido |

El catálogo **no es optimismo genérico**: es criterio acumulado y diferenciado —ascensores 300 días
de fabricación, carpintería de madera 90, baños portátiles 0; cuadros comparativos entre 2 y 45 días
según el paquete— y es lo que alimenta el motor que propone las fechas. **De los 62 paquetes de Da
Porto, 43 traen su duración de referencia propia**; los otros 19 caen a la mediana de la empresa y
son los que la hoja marca como duración provisional (8.6).

Lo que falta, entonces, no es la memoria: **es cerrar el lazo.** El contador de «pasos cerrados con
fecha real» de 8.6 y el marcador de acierto de 11.b no construyen la memoria desde cero — **sirven
para calibrar la que ya existe**, y para descubrir en qué paquetes el criterio de la empresa acierta
y en cuáles se quedó viejo.

## 14. Verificación

Por fase, y con salida real de comandos:

- **F0:** conteo de filas que devuelve la vista de responsables antes y después · consulta que
  demuestre de qué columna sale el eje de Productividad · recuento de causas con la atribución
  completa, sin truncar.
- **F1:** prueba de paridad por métrica (viejo motor contra catálogo, cuatro semanas, dos obras) ·
  `docker compose exec app php scripts/run-php-tests.php --nivel=puro` y `--nivel=http` ·
  `vendor/bin/phpstan analyse src admin/src` · revisión en navegador de la trazabilidad, alcanzable
  por teclado.
- **F2:** dry-run de la migración, con conteo antes y después · reconciliación del total de
  restricciones contra lo que hoy muestra Power BI · rol permitido y rol denegado para la escritura ·
  prueba de que escribir, recargar y recuperar el estado funciona.
- **F3 a F5:** las suites completas más validación en navegador de la ruta afectada, a 1180×820, en
  dark, revisando consola y red.

Ninguna baseline visual se regenera para forzar un resultado verde.

## 15. Criterio de muerte (D51)

**A los 90 días de publicar F2: si el porcentaje de actividades que entran a la semana sin análisis
de restricciones no bajó, la Torre no cambió el comportamiento y se rehace o se apaga.**

**La línea base se mide el día en que se publica F2, no antes**, y queda anotada en el ledger del
frente. Hoy, en la base de desarrollo, ese porcentaje es **68,9%** — 31.396 de 45.600
actividades-semana con las cinco restricciones intactas. La cifra de producción puede diferir; la que
manda es la del día de publicación.

Se mide contando registros, sin instrumentar aperturas — que además darían cero por el correo de F4
y llevarían a una conclusión falsa.

**Por qué esta métrica y no otra:** es la única que no se puede maquillar sin hacer el trabajo. Subir
el PAC se logra comprometiéndose a menos; bajar el porcentaje de actividades sin análisis exige
abrir el análisis.

**Y un segundo indicador, temprano: a las 4 semanas de F4**, qué porcentaje de las restricciones
huérfanas listadas en los correos gana dueño en 48 horas. **El umbral es 30%** (D75): si no se
alcanza, el correo se apaga antes de invertir más. El método pide medir la respuesta al empuje, no la
apertura; este avisa a los 28 días con el mismo dato que el de 90.

Triaje a los 90 días, cuadrante uso contra relevancia: alto uso y alta relevancia, mantener; bajo
uso y alta relevancia, es problema de distribución, no de diseño; baja relevancia, archivar avisando
a quien lo pidió.

## 16. Riesgos

| Riesgo | Qué lo dispara | Mitigación |
|---|---|---|
| **La obra no quiere lo que decidimos** | El supuesto 1, ahora **riesgo aceptado**: se avanza sin entrevistar a un residente ni a un director | No hay mitigación previa. Se detecta después, por comportamiento: si a los 90 días el porcentaje de actividades sin análisis de restricciones no baja, y si a las 4 semanas el correo no mueve a nadie, la hipótesis era falsa. **El costo de equivocarse ya no son veinte minutos: es F1 y F2 construidas** |
| **El plan de compras no llega en una semana** | El supuesto 4 | La hoja 8.6 nace vacía declarándolo. No se disimula con datos de ejemplo |
| **El indicador principal se lee como predicción** | D59 | La adherencia y la estimación van separadas y rotuladas. Nunca «estas van a fallar» con la evidencia actual |
| **La paridad de métricas destapa que las cifras de hoy están mal** | F1 | Es una ganancia disfrazada de problema. Documentar cada discrepancia y decidir cuál es la correcta antes de migrar |
| **La migración de esquema toca datos vivos** | F2 | Gate de Plannotator, respaldo verificable, dry-run y reconciliación. No se ejecuta desde una tarea de interfaz |
| **La Torre escribiendo abre un hueco de seguridad** | F2 | CSRF, capacidad, aislamiento por proyecto, auditoría, y prueba de rol permitido y denegado |
| **Tres lienzos con una sola persona detrás** | D52 | **Medido el 2026-08-20: el segundo lienzo cuesta cerca del 5% del primero**, porque la unidad de construcción es la hoja. El riesgo real no es este: es el bus factor, en la fila de abajo |
| **Felipe ausente dos semanas** | Una sola persona construye, despliega y mantiene | Tres condiciones: T1 con su gemelo en el servicio · F5 no se ejecuta hasta que otra persona haya hecho un deploy y un cambio de métrica por catálogo · un runbook de una página por hoja |
| **La revisión de gerencia no existe como reunión** | 8.1 depende de ella y nadie la evidencia | Confirmar con Felipe antes de F3. Si no existe, 8.1 se diseña como correo con pantalla detrás, no como hoja proyectada |
| **El catálogo de duraciones nunca se contrastó con una duración real** | 8.6 | El catálogo es criterio experto vigente (216 paquetes, 7 duraciones cada uno), **no una estimación floja**; lo que falta es el lazo de vuelta. Rotular la escala como «planeado», marcar los 19 paquetes de Da Porto que caen a la mediana, y publicar el contador de pasos cerrados con fecha real para empezar a calibrarlo |
| **El ranking de causas está contaminado** | D86: el jefe ve a su equipo y la causa se maquilla —confirmado, no sospechado | Contrapeso obligatorio de D32 al lado del ranking, y prohibición de sostener decisiones sobre personas solo con él |
| **La cadena de encadenamientos depende de la ruta crítica** | D88 | Si la ruta crítica no está bien mantenida en el programa, el efecto dominó que se muestre será falso. Verificar antes de publicarlo |
| **El correo no lo lee nadie** (medición temprana) | D50, F4 | A las 4 semanas de F4: si menos del porcentaje que fije Felipe —propuesta, 30%— de las huérfanas listadas gana dueño en 48 horas, el correo se apaga antes de invertir más |
| **El pronóstico P50 se equivoca en el titular** | D23 | Contrastar contra obras cerradas antes de F3. Si no acierta, baja de titular |
| **`bi-spa.js` de 4.199 líneas** | Todas las fases | T1: se parte por hoja antes de tocar nada |

## 17. Lo que no se construye

- Telemetría de aperturas en la primera versión.
- Automatizar más distribución antes de medir la respuesta al correo.
- Bodega de datos, capa de BI genérica o programa formal de gobierno de tableros.
- Métricas agregadas al lienzo «porque el dato ya está».
- Comparaciones entre obras que no son comparables sin normalizar.
- Cualquier cifra que dependa de costo real causado, mientras no exista la fuente.
- **Variantes por audiencia dentro de una hoja compartida.** Es la única forma de duplicación que
  esta arquitectura permite por descuido, y la única que triplicaría el costo de mantenimiento.
- Un importador o un pronóstico de compras alimentado por el PDC v1: no hay entrada que importar.

## 18. Lo que queda abierto

Ninguno bloquea F0. El primero **quedó resuelto el 2026-08-20**; los demás siguen abiertos.

1. **Resuelto (ver [[2026-08-20-sostenibilidad-lienzos]]).** D52 y D11 no chocan: un **lienzo** es una
   composición —puerta de entrada por rol, hoja de aterrizaje y lista de hojas montadas—, y la
   **hoja** es la unidad de construcción. Gerencia monta cuatro hojas (Resumen Ejecutivo como
   aterrizaje, Programa General, Curva S, Proveedores); obra monta siete (Programación Intermedia
   como aterrizaje, Programa General, Semanal, Curva S, Plan de Compras, Proveedores, Responsables).
   Las ocho hojas de D11 viven, cada una en al menos un lienzo. Programa General, Curva S y
   Proveedores se construyen **una vez** y se montan en los dos, **sin variante por audiencia**: el
   alcance lo deciden los filtros de proyecto y período, y una necesidad nueva de una audiencia es un
   desglose, no una copia. Responsables no se monta en gerencia (D46, D47). Medido contra el código
   —57% de la interfaz es motor compartido y el servidor despacha por hoja, nunca por audiencia—
   **el segundo lienzo cuesta cerca del 5% del primero**. Lo que sí triplicaría el costo, y por eso
   queda prohibido en 17, es la variante por audiencia dentro de una hoja compartida. **El costo que
   D52 advertía no está en los lienzos: está en el bus factor**, tratado en 16.
2. **Umbral de replanificación de la Curva S** (8.5): a partir de qué brecha se convoca a rehacer el
   programa.
3. **Resuelto por el ritual (ver [[2026-08-20-ritual-y-reuniones]]):** en Intermedia, **la lista de
   restricciones va arriba del titular**. En la reunión se trabaja la lista y se asignan dueños en
   vivo; el titular narrativo sirve a quien llega sin contexto y encabeza el correo, no la pantalla
   proyectada.
4. **Los campos muertos** —`Categoria_CP`, `CP`, `alerta_crisis`, `reprogramaciones_semanales`, todos
   en 0% de llenado— se retiran o alguien debería estar llenándolos. Decisión de proceso, se toma
   en F0.
5. **Resueltas el 2026-08-20** las ocho preguntas de ritual y reuniones (D67 a D70, D75, D81 y la
   distribución completa en D76 a D80). **Queda una sola: quién más debería recibir el correo de
   víspera** además de director y residente. La spec asume que nadie más en la primera versión.
6. **Si «Prueba» fue el borrador de Da Porto**, y si JMC y Milán tuvieron su plan de compras en Excel
   o Licify — porque entonces el historial de compras existe, pero fuera de la base.

## 19. Errores de medición cometidos en este diseño

Se dejan escritos porque son exactamente el error que un tablero puede cometer en grande, y porque
la spec pide que las cifras se puedan defender.

1. **Contar los ceros como campos vacíos.** Un PAC en cero no es un dato faltante: es el
   incumplimiento, que es el dato que importa. Filtrar «distinto de vacío» lo borra de la cuenta.
2. **Usar el denominador equivocado.** El PAC solo aplica a actividades comprometidas; la causa de no
   cumplimiento, solo a las comprometidas que fallaron. Medido sobre el programa entero, una captura
   sana del 90% parece un abandono del 22%.

3. **Contar filas como si fueran datos.** Se afirmó que la tabla `pdc` guardaba «409 planes de
   compras completos» y que era «el único historial de desempeño de compras que existe», habiendo
   mirado solo los nombres de las columnas y el número de filas. Medido: 273 de esas filas no tienen
   una sola fecha, valor ni proveedor, y las seis columnas de valor están vacías en las 409. **Son
   plantilla.** Es la trampa 4 del método —el dato histórico puede no ser confiable— aplicada a quien
   escribió la spec.

Los dos primeros juntos produjeron un diagnóstico falso —«hay que arreglar la captura antes que el tablero»—
que estuvo a punto de reordenar todo el replanteo. Lo corrigió Felipe.

**Regla que sale de ahí, y que aplica al ejecutor de métricas de 6:** ninguna métrica se declara sin
declarar también su denominador. Un porcentaje sin su base no es una cifra, es una insinuación.
