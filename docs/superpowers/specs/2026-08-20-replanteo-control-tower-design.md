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

1. **La obra no fue entrevistada.** El método `antes-del-almuerzo` exige de tres a cinco
   conversaciones con quienes usan el tablero. Felipe declaró «yo soy la obra» y respondió por ella.
   **Cualquier hallazgo de un residente o director que contradiga lo aquí decidido manda sobre esta
   spec.**
2. **El correo automático se construye sin haber validado a mano** que alguien responde (D50,
   tomada contra la recomendación del método).
3. **Se asume que las cifras históricas son correctas.** No se auditó un período contra la realidad
   de obra. La fase 1 incluye una verificación mínima de esto.
4. **Se asume que el plan de compras con fechas existirá en una semana** (D63). Hoy no existe en
   ninguna obra. Si no llega, la hoja de 8.6 nace vacía y hay que decirlo en pantalla.
5. **La medición se hizo contra la base de desarrollo**, con verificación puntual en producción por
   SSH en solo lectura. Los porcentajes de llenado son señal confiable; los conteos absolutos de dev
   pueden diferir de producción. **No se trajo ninguna copia de producción.**

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
| `EstadoLiberacion` | enum: `sin_gestionar`, `en_gestion`, `liberada`, `no_aplica` | Estado explícito, en vez de inferido del valor |
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

### 8.1 Resumen Ejecutivo — audiencia gerencia

- **Decisión (D17):** en qué obra meterse esta semana. Acción: llamar hoy a ese director.
- **Lienzo:** titular narrativo que afirma qué pasó y por qué (D19) · **panorama de obras**: una
  fila por obra con su señal de restricciones, su desviación y su tendencia (D18) · acciones
  recomendadas, cada una con nombre y fecha (D20).
- **Se retiran del lienzo:** las dos gráficas actuales (PAC contra programado, PPC semanal), que
  repiten lo que ya está en Semanal.
- **Regla:** es la única hoja que compara obras entre sí.

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
- **Acción en pantalla (D33):** asignar responsable y fecha a una restricción sin salir de la hoja.
- **Contrapeso de captura (D32):** junto al ranking de causas, quién lo registró y cuántas quedaron
  sin causa. Con una nota explícita de que cada responsable registra la suya.
- **La atribución no se trunca (D65).** El catálogo distingue «Actividad predecesora incompleta / no
  ejecutada **(obra)**» (502 veces) de «**(subcontratista)**» (297) y de la variante sin atribuir
  (224). No eran duplicados: **la gráfica corta el texto justo donde está la atribución de culpa**,
  que es el dato más político del tablero. Solo la tercera variante es deuda de catálogo.
- **Abierto:** confirmar con Felipe si el titular narrativo va arriba de la lista o al revés.

### 8.4 Programación Semanal — audiencia obra

- **Decisión (D35):** principal, el director prepara el comité del lunes. Secundarias: el residente
  revisa sus compromisos a diario; gerencia compara entre obras.
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

### 8.6 Plan de Compras

> **Supuesto que sostiene esta hoja (D63):** hoy **no existe plan con fechas en ninguna obra** —
> `pdc_plan_paso`, `pdc_plan_paquete` y `pdc_subpaquete` en cero, en dev y en producción. Lo que sí
> existe en Da Porto (proyecto 73) es presupuesto (523 ítems) y **58 paquetes de contratación con 278
> insumos asignados, todos confirmados a mano**. Se asume que el calendario estará cargado en una
> semana. **Si no llega, esta hoja nace vacía y debe decirlo en pantalla, no disimularlo.**

- **Decisión (D42):** compras destraba el paso vencido de hoy · gerencia vigila la cobertura del
  presupuesto · el director anticipa qué le va a faltar.
- **Lienzo:** lista de pasos de contratación vencidos, con responsable y días de vencido —gemelo de
  la lista de restricciones— · cobertura por conteo y por valor · escala de vencimiento.
- **Escala (D43):** ya vencido · esta semana · en 2 · en 3 · en 6 semanas · **sin fecha, tratada
  como alarma**, igual que las restricciones huérfanas.

### 8.7 Proveedores

- **Decisión (D45):** a quién no volver a contratar · a quién apretar esta semana · insumo del
  comité de compras.
- **Calificación integral (D44):** **no se publica hasta tener sus cinco componentes.** Se muestran
  por separado los que tienen dato; el integral aparece inhabilitado, declarando qué falta y quién
  lo carga. Es el caso de aplicación obligatoria de 6.2.

### 8.8 Responsables

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
| D14 | Por defecto, **la semana en curso** |
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
- Un lienzo por audiencia (D52): gerencia y obra. Dark, 1180×820 como viewport canónico de
  validación.

## 13. Fases

Cada fase se publica antes de abrir la siguiente, según el gate de cierre de frente de AGENTS.md.

| # | Fase | Contenido | Condición de hecho |
|---|---|---|---|
| **F0** | **Higiene de datos** | Arreglar la vista de responsables · aclarar el origen del eje de Productividad · corregir el mojibake del catálogo de causas · dejar de truncar la atribución · decidir los campos muertos | Ninguna cifra de la Torre viene de una fuente que no es la que declara, y ningún texto de causa se corta donde dice de quién es la culpa |
| **F1** | **Cimiento** | Ejecutor de métricas · las 19 métricas de `descriptiva` a `ejecutable` con prueba de paridad · declaración de completitud · trazabilidad pintada · calificación de proveedores inhabilitada | Toda cifra de la Torre sale del catálogo, ninguna se calcula dos veces, y cada una responde «de dónde salgo» con un clic |
| **F2** | **Restricciones** | Migración de esquema con su gate · métricas nuevas · hoja de Intermedia reconstruida · alarma de huérfanas · lista accionable · escritura desde la Torre | Un director asigna responsable y fecha a una restricción sin salir de la hoja, y el conteo de huérfanas baja |
| **F3** | **Narrativa** | Resumen Ejecutivo con panorama de obras · acciones con dueño · riesgo de incumplimiento por compromiso rebautizado e integrado · Semanal rediseñada | La hoja abre con una frase que afirma qué pasó y por qué, y debajo qué hacer y quién |
| **F4** | **Salir del escondite** | Interruptor encendido · entrada en navegación · correo automático | El módulo está en el menú y la señal llega por correo |
| **F5** | **Jubilación** | Liberación de Restricciones completa · retiro del informe Power BI · después, `/indicadores` | Una sola casa para las cifras |
| **F6** | **Diferidos** | What-if acotado a restricciones · vista de cliente · contabilidad para el índice de costo · **rescate del historial del PDC v1** | Cada uno con su propia spec |

**F0 y F1 son las únicas especificadas aquí a nivel ejecutable.** F2 en adelante derivan su propia
spec. F0 es barata y desbloquea: sin ella, tres hojas muestran cifras cuyo origen no se puede
defender, que es exactamente lo que este replanteo viene a curar.

### El frente aparte: el historial del PDC v1 (D64)

La tabla `pdc` guarda **409 planes de compras completos** —fechas planeadas y reales de cada paso,
proveedor adjudicado, número de contrato, pólizas, y valores de presupuesto, negociación,
adjudicación, anticipo, reclamado y devoluciones— de tres obras: Prueba (136), Optimización
Aeropuerto JMC (162) y Milán Campestre Torre 19 (111). **Da Porto no está ahí**, y ninguna obra tiene
las dos generaciones del plan.

El código del PDC v1 se eliminó del repositorio el 2026-08-04, así que esos datos **están vivos en
producción y huérfanos de aplicación**. Es el único historial de desempeño de compras que existe.

Felipe decidió consultarlo y sacarlo de producción. **«Sacar» es borrado**, así que ese frente exige,
en este orden: visto explícito de Felipe · respaldo verificable · extracción a un archivo histórico ·
**comprobación de que ese archivo se lee fuera de producción** · y solo entonces el retiro, con plan
de restauración. Nada de esto se ejecutó en esta sesión.

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

Triaje a los 90 días, cuadrante uso contra relevancia: alto uso y alta relevancia, mantener; bajo
uso y alta relevancia, es problema de distribución, no de diseño; baja relevancia, archivar avisando
a quien lo pidió.

## 16. Riesgos

| Riesgo | Qué lo dispara | Mitigación |
|---|---|---|
| **La obra no quiere lo que decidimos** | El supuesto 1 | Las tres conversaciones siguen pendientes. Hacerlas antes de F2, que es donde se gasta de verdad |
| **El plan de compras no llega en una semana** | El supuesto 4 | La hoja 8.6 nace vacía declarándolo. No se disimula con datos de ejemplo |
| **El indicador principal se lee como predicción** | D59 | La adherencia y la estimación van separadas y rotuladas. Nunca «estas van a fallar» con la evidencia actual |
| **La paridad de métricas destapa que las cifras de hoy están mal** | F1 | Es una ganancia disfrazada de problema. Documentar cada discrepancia y decidir cuál es la correcta antes de migrar |
| **La migración de esquema toca datos vivos** | F2 | Gate de Plannotator, respaldo verificable, dry-run y reconciliación. No se ejecuta desde una tarea de interfaz |
| **La Torre escribiendo abre un hueco de seguridad** | F2 | CSRF, capacidad, aislamiento por proyecto, auditoría, y prueba de rol permitido y denegado |
| **Tres lienzos con una sola persona detrás** | D52 | El cliente ya se difirió. Si el mantenimiento ahoga, se colapsan gerencia y obra en uno con puerta de entrada distinta |
| **El correo no lo lee nadie** | D50 | Medir respuesta al correo desde el primer envío. Si a las cuatro semanas nadie responde, apagarlo antes de invertir más |
| **El pronóstico P50 se equivoca en el titular** | D23 | Contrastar contra obras cerradas antes de F3. Si no acierta, baja de titular |
| **`bi-spa.js` de 4.199 líneas** | Todas las fases | T1: se parte por hoja antes de tocar nada |

## 17. Lo que no se construye

- Telemetría de aperturas en la primera versión.
- Automatizar más distribución antes de medir la respuesta al correo.
- Bodega de datos, capa de BI genérica o programa formal de gobierno de tableros.
- Métricas agregadas al lienzo «porque el dato ya está».
- Comparaciones entre obras que no son comparables sin normalizar.
- Cualquier cifra que dependa de costo real causado, mientras no exista la fuente.

## 18. Lo que queda abierto

Ninguno bloquea F0. El primero bloquea la planificación de F1 en adelante.

1. **Contradicción entre D52 y D11, y hay que resolverla con Felipe.** D52 decidió «un lienzo propio
   por audiencia», rechazando explícitamente «las mismas hojas con puerta de entrada distinta». Pero
   D11 dejó las ocho hojas como obligatorias. Las dos juntas no definen **qué hojas tiene el lienzo
   de gerencia y cuáles el de obra**, ni qué pasa con una hoja que ambas necesitan —Curva S,
   Proveedores— si son productos separados. Tres salidas posibles:
   - Cada lienzo tiene su subconjunto, y las hojas compartidas se construyen una vez y se montan en
     los dos. Es lo más cercano a lo decidido sin duplicar trabajo.
   - Se revisa D52 hacia la puerta de entrada compartida, asumiendo que el método pierde aquí contra
     el costo de mantenimiento con una sola persona.
   - Se reduce el alcance de hojas por lienzo, revisando D11.
2. **Umbral de replanificación de la Curva S** (8.5): a partir de qué brecha se convoca a rehacer el
   programa.
3. **Orden del titular y la lista en Intermedia** (D28, 8.3): si la narrativa va arriba de la lista de
   restricciones o al revés. Se resuelve al diseñar F2.
4. **Los campos muertos** —`Categoria_CP`, `CP`, `alerta_crisis`, `reprogramaciones_semanales`, todos
   en 0% de llenado— se retiran o alguien debería estar llenándolos. Decisión de proceso, se toma
   en F0.

## 19. Errores de medición cometidos en este diseño

Se dejan escritos porque son exactamente el error que un tablero puede cometer en grande, y porque
la spec pide que las cifras se puedan defender.

1. **Contar los ceros como campos vacíos.** Un PAC en cero no es un dato faltante: es el
   incumplimiento, que es el dato que importa. Filtrar «distinto de vacío» lo borra de la cuenta.
2. **Usar el denominador equivocado.** El PAC solo aplica a actividades comprometidas; la causa de no
   cumplimiento, solo a las comprometidas que fallaron. Medido sobre el programa entero, una captura
   sana del 90% parece un abandono del 22%.

Los dos juntos produjeron un diagnóstico falso —«hay que arreglar la captura antes que el tablero»—
que estuvo a punto de reordenar todo el replanteo. Lo corrigió Felipe.

**Regla que sale de ahí, y que aplica al ejecutor de métricas de 6:** ninguna métrica se declara sin
declarar también su denominador. Un porcentaje sin su base no es una cifra, es una insinuación.
